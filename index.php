<?php
require 'funcs.php';

// sup
$startHour = 21;
$weekendStartHour = 14;
$min = 2; // how much anime should you *at least* watch every day

// template
function _printShow ($show, $time, $isNew=false) {
?>
                <tr>
                    <td>
                        <?= $time->format('H:i') . "\n" ?>
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
                            <?php
                            if ($isNew) echo '<small>[NEW]</small> ';
                            echo $show->media->title->romaji;
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
$startDate = new DateTime('@' . $shows->dates->start);
$endDate = new DateTime('@' . $shows->dates->end);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($startDate, $interval, $endDate);

foreach ($period as $dt) {
    $m = 0;
    $i = 0;

    if ($dt->format('N') >= 6) {
        $min += ceil(count($shows->catchup) / 2);
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
    foreach ($shows->airing as $show) {
        $air = $show->airingAt + 7200; // Add 2 hours for the release delay
        $dt->setTime($startHour, $m, 00);
        if ($air < $dt->getTimestamp()) {
            _printShow($show, $dt, true);
            $shows->airing = unsetValue($shows->airing, $show);

            $m += _dur($show->media->duration);
            $i++;
        }
    }

    foreach ($shows->catchup as $show) {
        $dt->setTime($startHour, $m, 00);

        if ($i < $min) {
            _printShow($show, $dt);
            $shows->catchup = unsetValue($shows->catchup, $show);

            $m += _dur($show->media->duration);
            $i++;
        }
    }

    // Sunday leftovers
    if ($dt->format('N') == 7) {
        foreach ($shows->airing as $show) {
            $dt->setTimestamp($show->airingAt + 7200); // Add 2 hours for the release delay
            _printShow($show, $dt, true);
            $shows->airing = unsetValue($shows->airing, $show);
            $m += _dur($show->media->duration);
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
