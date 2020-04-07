<?php
// sup
$startHour = 20;
$weekendStartHour = 14;
$min = 2; // how much anime should you *at least* watch every day

// functions
function unsetValue(array $array, $value, $strict = TRUE) {
    if (($key = array_search($value, $array, $strict)) !== FALSE) {
        unset($array[$key]);
    }
    return $array;
}

function _malSync ($str) {
    // Extract the URL from the notes made by malSync
    $re = '/malSync::(.*)::/';
    preg_match($re, $str, $matches, PREG_OFFSET_CAPTURE, 0);
    $url = base64_decode($matches[1][0]);
    $domain = parse_url($url, PHP_URL_HOST);
    return [
      "url" => $url,
      "icon" => 'https://www.google.com/s2/favicons?domain=' . $domain
    ];
}

function _printShow ($show, $time, $isNew=false) {
    // Template for a show line in the table
?>
                <tr>
                    <td>
                        <?= $time->format('H:i') ?>
                    </td>
                    <td>
<?php
        if (strpos($show->notes, 'malSync::') !== false) {
            $mal = _malSync($show->notes);
?>
                        <a href="<?= $mal['url'] ?>">
                          <img class="icon" src="<?= $mal['icon'] ?>">
    <?php // Intentionally leaving spaces to indent in output
        } else $mal = null;
?>
                        <?= $show->episode ?>/<?= $show->media->episodes ? $show->media->episodes . "\n" : "?\n" ?>
<?php if ($mal) { ?>
                        </a><?php echo "\n"; } ?>
                    </td>
                    <td>
                        <a target="_blank" href="https://anilist.co/anime/<?= $show->media->id ?>">
                            <?= $isNew ? '<small>[NEW]</small>' : '' ?>
                            <?php echo $show->media->title->romaji;
                            // If there's an English title and it's not the same as the Romaji one...
                            if ($show->media->title->english &&
                                strtolower($show->media->title->english) != strtolower($show->media->title->romaji))
                                echo " <small>(" . $show->media->title->english . ")</small>\n";
                            else echo "\n"; ?>
                        </a>
                    </td>
                </tr>
<?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>aniSched</title>

    <link rel="stylesheet" href="node_modules/spectre.css/dist/spectre.min.css">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="navbar">
        <section class="navbar-section">
            <a href="weekend.php">Weekend view</a>
        </section>
        <section class="navbar-center">
            <h1>aniSched</h1>
        </section>
        <section class="navbar-section">
        </section>
    </header>
    <div class="container">
<?php
$shows = json_decode(file_get_contents(__DIR__ . '/shows.json'));
$startDate = new DateTime('@' . strtotime('last monday'));
$endDate = new DateTime('@' . strtotime('next monday'));
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($startDate, $interval, $endDate);

foreach ($period as $dt) {
    $m = 0;
    $i = 0;

    if ($dt->format('N') >= 6) {
        $min += ceil(count($shows->saturday) / 2);
        $startHour = $weekendStartHour;
    }
?>
        <h3><?= $dt->format("l d") ?></h3>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th class="tcol">Time</th>
                    <th class="tcol">Episode</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
<?php
    foreach ($shows->sunday as $show) {
        $air = $show->airingAt;
        $dt->setTime($startHour, $m, 00);
        if ($air < $dt->getTimestamp()) {
            _printShow($show, $dt, true);
            $shows->sunday = unsetValue($shows->sunday, $show);

            $m += $show->media->duration;
            $i++;
        }
    }

    foreach ($shows->saturday as $show) {
        $dt->setTime($startHour, $m, 00);

        if ($i < $min) {
            _printShow($show, $dt);
            $shows->saturday = unsetValue($shows->saturday, $show);

            $m += $show->media->duration;
            $i++;
        }
    }

    // Sunday leftovers
    if ($dt->format('N') == 7) {
        foreach ($shows->sunday as $show) {
            $dt->setTimestamp($show->airingAt + 7200); // Add 2 hours for the release delay
            _printShow($show, $dt, true);
            $shows->sunday = unsetValue($shows->sunday, $show);
            $m += $show->media->duration;
        }
    }
?>
            </tbody>
        </table>
<?php
}
?>
    </div>
</body>
</html>
