<?php
if (PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) die('cli only');
require_once __DIR__ . '/vendor/autoload.php';

function sortByAirTime($a, $b) {
    $a = $a->airingAt;
    $b = $b->airingAt;

    if ($a == $b) return 0;
    return ($a < $b) ? -1 : 1;
}

// Load up the HTTP client
$http = new GuzzleHttp\Client;
// Load our access token from the login
$accessToken = file_get_contents(__DIR__ . '/token.txt');
// Create object where our schedule is saved
$j = new stdClass();
// Set bounds for airing schedule
$startDate = (int) strtotime('last monday');
$endDate = (int) strtotime('next monday');
$j->dates = array(
    'start' => $startDate,
    'end' => $endDate
);

// Ger our AniChart user and highlighted shows
$query = file_get_contents(__DIR__ . '/queries/anichart.graphql');
$response = $http->request('POST', 'https://graphql.anilist.co', [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
    'json' => [
        'query' => $query,
        'variables' => [],
    ]
]);

// Save user id and shows in green
$user = json_decode($response->getBody())->data->AniChartUser->user->id;
$hls = json_decode($response->getBody())->data->AniChartUser->highlights;
$ids = [];
foreach($hls as $show => $status) {
    if ($status == 'green') $ids[] = (int) $show;
}

// Get our watch list
$query = file_get_contents(__DIR__ . '/queries/watchlist.graphql');
$variables = [
    "user" => $user,
    "page" => 1
];
$response = $http->request('POST', 'https://graphql.anilist.co', [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
    'json' => [
        'query' => $query,
        'variables' => $variables,
    ]
]);

// Save anything that isn't a movie or manga from our watch list
$shows = [];
$wL = json_decode($response->getBody())->data->Page->mediaList;
foreach($wL as $s) {
    if ($s->media->format == 'MOVIE' ||
        $s->media->format == 'MANGA') continue;
    $shows[$s->media->id] = $s;
}

// Get the list of shows airing in our set time span
$query = file_get_contents(__DIR__ . '/queries/airing.graphql');
$variables = [
    "weekStart" => $startDate,
    "weekEnd" => $endDate,
    "page" => 1,
    "listIds" => $ids
];
$response = $http->request('POST', 'https://graphql.anilist.co', [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
    ],
    'json' => [
        'query' => $query,
        'variables' => $variables,
    ]
]);

// Make our Sunday schedule from what's airing
$sun = [];
$sch = json_decode($response->getBody())->data->Page->airingSchedules;
foreach($sch as $s) {
    // Skip movies
    if ($s->media->format == 'MOVIE') continue;

    // If we're late by more than one (aired) episode, skip
    if (array_key_exists($s->media->id, $shows) &&
        $shows[$s->media->id]->progress < ($s->episode - 1)) continue;

    // Add to array
    $sun[$s->media->id] = $s;
}

$j->sunday = array_values($sun);
usort($j->sunday, 'sortByAirTime');

// Make our Saturday schedule with what's left
$sat = [];
foreach($shows as $s) {
    // If it's already in the sunday list, add notes and skip
    if (array_key_exists($s->media->id, $sun)) {
        $sun[$s->media->id]->notes = $s->notes;
        continue;
    }

    // Skip if future episode didn't air yet
    if ($s->media->nextAiringEpisode != null &&
        $s->media->nextAiringEpisode->episode <= $s->progress + 1) continue;

    // Uniformize
    $s->episode = $s->progress + 1;

    // Add to array
    $sat[$s->media->id] = $s;
}

$j->saturday = array_values($sat);

// Save everything
file_put_contents(__DIR__ . '/shows.json', json_encode($j));
echo "done\n";
