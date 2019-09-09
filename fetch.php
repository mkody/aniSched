<?php
if (PHP_SAPI !== 'cli' || isset($_SERVER['HTTP_USER_AGENT'])) die('cli only');
require_once __DIR__ . '/vendor/autoload.php';

$accessToken = file_get_contents(__DIR__ . '/token.txt');

$j = json_decode(file_get_contents('shows.json'));
$startDate = (int) strtotime('last monday');
$endDate = (int) strtotime('next monday');
$http = new GuzzleHttp\Client;

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

$user = json_decode($response->getBody())->data->AniChartUser->user->id;
$hls = json_decode($response->getBody())->data->AniChartUser->highlights;
$ids = [];
foreach($hls as $show => $status) {
    if ($status == 'green') $ids[] = (int) $show;
}

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

$shows = [];
$sch = json_decode($response->getBody())->data->Page->airingSchedules;
foreach($sch as $s) {
    if ($s->media->format == 'MOVIE') continue;
    $shows[$s->media->id] = $s;
}

$j->sunday = array_values($shows);



$query = file_get_contents(__DIR__ . '/queries/watchlist_notairing.graphql');
$variables = [
    "user" => $user,
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

$shows = [];
$sch = json_decode($response->getBody())->data->Page->mediaList;
foreach($sch as $s) {
    if ($s->media->format == 'MOVIE' ||
        $s->media->format == 'MANGA' ||
        in_array($s->media->id, $ids)) continue;
    $shows[$s->media->id] = $s;
}

$j->saturday = array_values($shows);

file_put_contents('shows.json', json_encode($j));
echo "done";