<?php
require 'funcs.php';

// sup
$shows = json_decode(file_get_contents(__DIR__ . '/shows.json'));
$startHour = 14;
$sat = 0;
$sun = 0;

// template
function _printShow ($show, $time) {
?>
                <tr>
                    <td>
                        <time><?= _showHour($time) ?></time>
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
            <a href="index.php">Daily view</a>
        </section>
        <section class="navbar-center">
            <h1>aniSched</h1>
        </section>
        <section class="navbar-section">
        </section>
    </header>
    <div class="container">
        <h3>Saturday <small>(catch up)</small></h3>
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
    foreach($shows->catchup as $show) {
        _printShow($show, $sat);

        if ($show->media->duration == null) $sat += 30;
        elseif ($show->media->duration <= 5) $sat += 5;
        elseif ($show->media->duration <= 10) $sat += 10;
        elseif ($show->media->duration <= 15) $sat += 15;
        elseif ($show->media->duration <= 30) $sat += 30;
        else $sat += $show->media->duration;

        // Break after 2 hours
        if ($sat >= 2*60 && $sat < 3*60) {
?>
                <tr>
                    <td>
                        <?= _showHour($sat) ?>
                    </td>
                    <td>
                        &nbsp;
                    </td>
                    <td>
                        ~ Break ~
                    </td>
                </tr>
<?php
            $sat = 3*60;
        // Break before going to eat
        } else if ($sat >= 4.5*60 && $sat < 7*60) {
?>
                <tr>
                    <td>
                        <?= _showHour($sat) ?>
                    </td>
                    <td>
                        &nbsp;
                    </td>
                    <td>
                        ~ Break ~
                    </td>
                </tr>
<?php
            // Moved up because I have thing to do
            $sat = 8.5*60;
        }
    }
?>
            </tbody>
        </table>

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
    foreach($shows->airing as $show) {
        _printShow($show, $sun);

        if ($show->media->duration == null) $sun += 30;
        elseif ($show->media->duration <= 5) $sun += 5;
        elseif ($show->media->duration <= 10) $sun += 10;
        elseif ($show->media->duration <= 15) $sun += 15;
        elseif ($show->media->duration <= 30) $sun += 30;
        else $sun += $show->media->duration;

        // Break after 2 hours
        if ($sun >= 2*60 && $sun < 3*60) {
?>
                <tr>
                    <td>
                        <?= _showHour($sun) ?>
                    </td>
                    <td>
                        &nbsp;
                    </td>
                    <td>
                        ~ Break ~
                    </td>
                </tr>
<?php
            $sun = 3*60;
        // Break before going to eat
        } else if ($sun >= 4.5*60 && $sun < 7*60) {
?>
                <tr>
                    <td>
                        <?= _showHour($sun) ?>
                    </td>
                    <td>
                        &nbsp;
                    </td>
                    <td>
                        ~ Break ~
                    </td>
                </tr>
<?php
            $sun = 7*60;
        }
    }
?>
            </tbody>
        </table>
    </div>
</body>
</html>
