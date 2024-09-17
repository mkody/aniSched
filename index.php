<?php
require 'config.php';
require 'funcs.php';

// template
function _printShow ($show, $time, $isNew=false) {
?>
                <tr>
                    <td class="tags"><?php
                            if ($show->episode == $show->media->episodes && $show->episode != 1) echo '&#x1F51A;';
                            if ($isNew) echo '&#x1F195;';
                            if (property_exists($show, 'status') && $show->status == 'REPEATING') echo '&#x1F501;';
                    ?></td>
                    <td class="time">
                        <time><?= $time->format('H:i') ?></time>
                    </td>
                    <td class="progress">
<?php
        if (strpos($show->notes, 'malSync::') !== false) {
            $mal = _malSync($show->notes);
?>
                        <a href="<?= $mal['url'] ?>">
                            <img class="icon" src="<?= $mal['icon'] ?>">
    <?php // Intentionally leaving spaces to indent in output
        } else {
            $mal = null;
?>
                        <i class="noicon"></i>
<?php
        }
?>
                        <?= $show->episode ?>/<?= $show->media->episodes ? $show->media->episodes . "\n" : "?\n" ?>
<?php if ($mal) { ?>
                        </a><?php echo "\n"; } ?>
                    </td>
                    <td>
                        <a target="_blank" href="https://anilist.co/anime/<?= $show->media->id ?>">
                            <?php
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
    <link rel="stylesheet" href="styles.css?v=1">
</head>
<body>
    <header class="navbar">
        <section class="navbar-section hide-md">
            <!-- Left for alignment -->
        </section>
        <section class="navbar-center">
            <h1>aniSched</h1>
        </section>
        <section class="navbar-section custom-links">
<?php foreach($customLinks as $title => $href) { ?>
            <span><a href="<?= $href ?>"><?= $title ?></a></span>
<?php } ?>
        </section>
    </header>
    <div class="container">
<?php
$shows = json_decode(file_get_contents(__DIR__ . '/shows.json'));
$startDate = new DateTime('@' . $shows->dates->start);
$endDate = new DateTime('@' . $shows->dates->end);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($startDate, $interval, $endDate);
$utcDiff = (new DateTimeZone($tz))->getOffset((new DateTime('now', (new DateTimeZone('UTC')))));

foreach ($period as $dt) {
    $m = 0;
    $i = 0;

    $maxTS = $dt->getTimestamp() + 86400; // Get maximum timestamp for today

    if ($dt->format('N') == 6) { // Change min and start hour on Saturday
        $startHour = $weekendStartHour;

        // We'll try to even out Staturday and Sunday
        $airingSat = 0;
        foreach ($shows->airing as $show) {
            if ($show->airingAt < $maxTS) $airingSat++;
        }
        $airingSun = count($shows->airing) - $airingSat;

        // I'm trying to make the number of shows close to even on Sat. and Sun.
        $min = floor((count($shows->catchup) + $airingSun) / 2);
    }

    if ($dt->format('N') == 7) { // No min on Sunday, just dump the rest
        $min = 99;
    }
?>
        <h3 id="<?= $dt->format("d") ?>"><?= $dt->format("l d") ?></h3>
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th class="tags">&nbsp;</th>
                    <th class="time">Time</th>
                    <th class="progress">Episode</th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
<?php
    foreach ($shows->airing as $show) {
        // If it airs today
        if ($show->airingAt < $maxTS) {
            // Add your difference with UTC + 1 hour for the release delay
            $air = $show->airingAt + $utcDiff + 3600;

            while (true) {
                $dt->setTime($startHour, $m, 00);

                if ($air <= $dt->getTimestamp()) {
                    // If it can be added directly now in the schedule, do it
                    _printShow($show, $dt, true);
                    $shows->airing = unsetValue($shows->airing, $show);

                    $m += _dur($show->media->duration);
                    $i++;
                    break;
                } else if ($i < ($min - 1) && count($shows->catchup) > 0) {
                    // Or try to put an off-season anime to fill the time
                    // if we've not reached $min yet and if there's some left
                    foreach ($shows->catchup as $showC) {
                        _printShow($showC, $dt);
                        $shows->catchup = unsetValue($shows->catchup, $showC);

                        $m += _dur($showC->media->duration);
                        $i++;
                        break;
                    }
                } else {
                    // No more off-season anime left or we got our minimum?
                    // Then just add it at when it should be up
                    // First we shift our time in the schedule
                    $diffShift = $air - $dt->getTimestamp();
                    $m += floor($diffShift / 60);
                    $dt->setTime($startHour, $m, 00);

                    // And then proceed like normal
                    _printShow($show, $dt, true);
                    $shows->airing = unsetValue($shows->airing, $show);

                    $m += _dur($show->media->duration);
                    $i++;
                    break;
                }
            }
        }
    }

    // In case of no new episode that day, fill with off-season anime if
    // we didn't reach the minimum today
    while (count($shows->catchup) > 0 && ($i < $min || $dt->format('N') == 7)) {
        $dt->setTime($startHour, $m, 00);
        foreach ($shows->catchup as $show) {
            _printShow($show, $dt);
            $shows->catchup = unsetValue($shows->catchup, $show);

            $m += _dur($show->media->duration);
            $i++;
            break;
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
