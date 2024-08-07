<?php
if (PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) die('cli only');
require_once __DIR__ . '/funcs.php';

// Don't worry too much, it's to keep strtotime working properly
date_default_timezone_set('UTC');

function sortByAirTime($a, $b) {
    $a = $a->airingAt;
    $b = $b->airingAt;

    if ($a == $b) return 0;
    return ($a < $b) ? -1 : 1;
}

// Load our access token from the login
$accessToken = file_get_contents(__DIR__ . '/.token');
// Create object where our schedule is saved
$j = new stdClass();
// Set bounds for airing schedule
if (date('w') == 1) $startDate = (int) strtotime('today');
else $startDate = (int) strtotime('last monday');
$endDate = $startDate + 604800;
$j->dates = array(
    'start' => $startDate,
    'end' => $endDate
);

// Ger our AniChart user and highlighted shows
$query = file_get_contents(__DIR__ . '/queries/anichart.graphql');
$response = graphql('https://graphql.anilist.co', $query, '{}', $accessToken);

// Save user id and shows in green
$user = $response->data->AniChartUser->user->id;
$hls = $response->data->AniChartUser->highlights;
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
$response = graphql('https://graphql.anilist.co', $query, json_encode($variables), $accessToken);

// Save anything that isn't a movie or manga from our watch list
$shows = [];
$wL = $response->data->Page->mediaList;
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
$response = graphql('https://graphql.anilist.co', $query, json_encode($variables), $accessToken);

// Make our Sunday schedule from what's airing
$airing = [];
$sch = $response->data->Page->airingSchedules;
foreach($sch as $s) {
    // Skip movies
    if ($s->media->format == 'MOVIE') continue;

    // If we're late by more than one (aired) episode, skip
    if (array_key_exists($s->media->id, $shows) &&
        $shows[$s->media->id]->progress < ($s->episode - 1)) continue;

    // Add to array
    $airing[$s->media->id] = $s;
}

// Make our catch up schedule with what's left
$catchup = [];
foreach($shows as $s) {
    // If it's already in the sunday list, add notes and skip
    if (array_key_exists($s->media->id, $airing)) {
        $airing[$s->media->id]->notes = $s->notes;
        continue;
    }

    // Check if it's not a currently airing entry (not found via aniChart)
    if ($s->media->nextAiringEpisode != null &&
        $s->media->nextAiringEpisode->airingAt != null &&
        $s->media->nextAiringEpisode->episode = ($s->progress + 1) &&
        $s->media->nextAiringEpisode->airingAt < $endDate) {

        // Create our pseudo-airing object
        $a = new stdClass();
        $a->id = 0;
        $a->airingAt = $s->media->nextAiringEpisode->airingAt;
        $a->episode = $s->progress + 1;
        $a->media = $s->media;
        $a->notes = $s->notes;
        $airing[$s->media->id] = $a;
        continue;
    }

    // Skip if future episode didn't air yet
    if ($s->media->nextAiringEpisode != null &&
        $s->media->nextAiringEpisode->episode <= ($s->progress + 1)) continue;

    // Uniformize
    $s->episode = $s->progress + 1;

    // Add to array
    $catchup[$s->media->id] = $s;
}

$j->catchup = array_values($catchup);
$j->airing = array_values($airing);
usort($j->airing, 'sortByAirTime');

// Save everything
file_put_contents(__DIR__ . '/shows.json', json_encode($j));
echo "done\n";
