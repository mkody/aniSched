<?php
function graphql ($host, $query, $variables, $token) {
    // Cleanup
    $query = preg_replace('/\s+/S', ' ', $query);

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $host,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => '{"query": "' . trim($query) . '", "variables": ' . $variables . '}',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . trim($token),
            'Content-Type: application/json; charset=utf-8'
        )
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response);
}

function unsetValue(array $array, $value, $strict = TRUE) {
    if (($key = array_search($value, $array, $strict)) !== FALSE) {
        unset($array[$key]);
    }
    return $array;
}

function _dur ($dur) {
    if ($dur == null) return 30;
    elseif ($dur <= 5) return 5;
    elseif ($dur <= 10) return 10;
    elseif ($dur <= 15) return 15;
    elseif ($dur <= 30) return 30;
    else return $dur;
}

function _showHour ($m) {
    // Display time
    // If the minutes are over 60,
    // PHP will add an another hour by itself
    global $startHour;
    $date = new DateTime('2001-01-01');
    $date->setTime($startHour, $m, 00);
    return $date->format('H:i');
}

function _icon ($domain) {
    switch ($domain) {
        case 'crunchyroll.com':
        case 'www.crunchyroll.com':
            return 'icons/crunchyroll.png';
        case 'animedigitalnetwork.fr':
        case 'www.animedigitalnetwork.fr':
            return 'icons/adn.png';
        case 'wakanim.tv':
        case 'www.wakanim.tv':
            return 'icons/wakanim.png';
        case 'primevideo.com':
        case 'www.primevideo.com':
            return 'icons/primevideo.png';
        case 'netflix.com':
        case 'www.netflix.com':
            return 'icons/netflix.png';
        case 'nyaa.si':
        case 'www.nyaa.si':
            return 'icons/nyaasi.png';
        case 'twist.moe':
        case 'www.twist.moe':
            return 'icons/twist.png';
        case 'aniwatch.me':
        case 'www.aniwatch.me':
            return 'icons/aniwatch.png';
        default:
            return 'https://www.google.com/s2/favicons?domain=' . $domain;
    }
}

function _malSync ($str) {
    // Extract the URL from the notes made by malSync
    $re = '/malSync::(.*)::/';
    preg_match($re, $str, $matches, PREG_OFFSET_CAPTURE, 0);
    $url = base64_decode($matches[1][0]);
    if (substr($url, 0, 1) == '{') {
       $url = json_decode($url, true)['u'];
    }
    $domain = parse_url($url, PHP_URL_HOST);
    return [
      'url' => $url,
      'icon' => _icon($domain)
    ];
}
