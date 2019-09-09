<?php
if (file_exists(__DIR__ . '/token.txt')) die('already logged in');

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/secrets.php';

$token = null;
$query = [
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code'
];
$loginURL = 'https://anilist.co/api/v2/oauth/authorize?' . urldecode(http_build_query($query));

if (isset($_GET['code'])) {
    $http = new GuzzleHttp\Client;

    $response = $http->post('https://anilist.co/api/v2/oauth/token', [
        'form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $_GET['code'],
        ],
        'headers' => [
            'Accept' => 'application/json'
        ]
    ]);

    $token = json_decode($response->getBody())->access_token;
    file_put_contents(__DIR__ . '/token.txt', $token);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Login - aniSched</title>
</head>
<body>
<?php if ($token) { ?>
    token saved
<?php } else { ?>
    <a href="<?= $loginURL ?>">Login</a>
<?php } ?>

</body>
</html>
