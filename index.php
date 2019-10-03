<?php
// sup
$shows = json_decode(file_get_contents(__DIR__ . '/shows.json'));
$sat = 0;
$sun = 0;

function _showHour ($m) {
    $date = new DateTime('2001-01-01');
    $date->setTime(14, $m, 00);
    return $date->format('H:i');
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
        </section>
        <section class="navbar-center">
            <h1>aniSched</h1>
        </section>
        <section class="navbar-section">
        </section>
    </header>
    <div class="container">
        <h3>Saturday <small>(catch-up)</small></h3>
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
    foreach($shows->saturday as $show) {
?>
                <tr>
                    <td><?= _showHour($sat) ?></td>
                    <td><?= $show->progress + 1 ?>/<?= $show->media->episodes ? $show->media->episodes : '?' ?></td>
                    <td>
                        <a target="_blank"
                            href="https://anilist.co/anime/<?= $show->media->id ?>">
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
        if ($show->media->duration == null) $sat += 30;
        elseif ($show->media->duration <= 15) $sat += 15;
        elseif ($show->media->duration <= 30) $sat += 30;
        else $sat += $show->media->duration;

        if ($sat >= 3*60 && $sat < 5*60) {
?>
                <tr>
                    <td><?= _showHour($sat) ?></td>
                    <td>&nbsp;</td>
                    <td>~ Break ~</td>
                </tr>
<?php
            $sat = 8.5*60;
        }
    }
?>
            </tbody>
        </table>

        <hr>

        <h3>Sunday <small>(airing)</small></h3>
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
    foreach($shows->sunday as $show) {
?>
                <tr>
                    <td><?= _showHour($sun) ?></td>
                    <td><?= $show->episode ?>/<?= $show->media->episodes ? $show->media->episodes : '?' ?></td>
                    <td>
                        <a target="_blank"
                            href="https://anilist.co/anime/<?= $show->media->id ?>">
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
        if ($show->media->duration == null) $sun += 30;
        elseif ($show->media->duration <= 15) $sun += 15;
        elseif ($show->media->duration <= 30) $sun += 30;
        else $sun += $show->media->duration;

        if ($sun >= 3*60 && $sun < 5*60) {
?>
                <tr>
                    <td><?= _showHour($sun) ?></td>
                    <td>&nbsp;</td>
                    <td>~ Break ~</td>
                </tr>
<?php
            $sun = 6*60;
        }
    }
?>
            </tbody>
        </table>
    </div>
</body>
</html>
