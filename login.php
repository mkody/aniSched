<?php
if (file_exists(__DIR__ . '/.token')) die('already logged in');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/funcs.php';

$token = null;
$query = [
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code'
];
$loginURL = 'https://anilist.co/api/v2/oauth/authorize?' . urldecode(http_build_query($query));

if (isset($_GET['code'])) {
    $tk = fopen(__DIR__ . '/.token', 'w') or die('Unable to write!');

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://anilist.co/api/v2/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'redirect_uri' => $redirect_uri,
            'code' => $_GET['code'],
        ],
        CURLOPT_HTTPHEADER => array(
            'Accept: application/json'
        )
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $token = json_decode($response)->access_token;
    fwrite($tk, $token);
    fclose($tk);
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
