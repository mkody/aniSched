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
                        <time><?= $time->format('H:i') ?></time>
                    </td>
                    <td>
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
                            if ($show->episode == $show->media->episodes && $show->episode != 1) echo '<small class="tag">[END]</small> ';
                            if ($isNew) echo '<small class="tag">[NEW]</small> ';
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

    $maxTS = $dt->getTimestamp() + 86400; // Get maximum timestamp for today

    if ($dt->format('N') == 6) { // Change min and start hour on Saturday
        $min += floor(count($shows->catchup) / 2);
        $startHour = $weekendStartHour;
    }
?>
        <h3 id="<?= $dt->format("d") ?>"><?= $dt->format("l d") ?></h3>
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
        // If it airs today
        if ($show->airingAt < $maxTS) {
            $air = $show->airingAt + 3600; // Add 1 hour for the release delay

            while (true) {
                $dt->setTime($startHour, $m, 00);

                if ($air < $dt->getTimestamp()) {
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
                    // No more off-season anime left or we got our minimum? Then just add it
                    $dt->setTimestamp($air);
                    _printShow($show, $dt, true);
                    $shows->airing = unsetValue($shows->airing, $show);

                    $m += _dur($show->media->duration);
                    $i++;
                    break;
                }
            }
        }
    }

    // In case no new episode today fill with off-season anime if there's some and
    // - either we didnt' reach the minimum today
    // - or we're sunday and we dump the rest
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
