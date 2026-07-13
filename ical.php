<?php
require 'config.php';
require 'funcs.php';

// @NOTE(kody): This file mirrors a lot of what's done in index.php
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename="anisched.ics"');

// template
function _icalEvent($show, $dtStart, $isNew) {
  $dur = _dur($show->media->duration);
  $dtEnd = clone $dtStart;
  $dtEnd->modify("+{$dur} minutes");

  // If there's an English title and it's not the same as the Romaji one...
  $romaji = $show->media->title->romaji;
  $english = $show->media->title->english ?? null;
  $summary = $romaji;
  if ($english && strtolower($english) !== strtolower($romaji)) {
    $summary .= ' (' . $english . ')';
  }

  // Emoji to indicate status
  $tags = '';
  if (property_exists($show->media, 'episodes') &&
    $show->media->episodes &&
    property_exists($show, 'episode') &&
    $show->episode == $show->media->episodes &&
    $show->episode != 1
  ) {
    $tags .= '🔚';
  }
  if ($isNew) $tags .= '🆕';
  if (property_exists($show, 'status') && $show->status === 'REPEATING') $tags .= '🔁';
  if ($tags) $summary = $tags . ' ' . $summary;

  // Progress
  $ep = $show->episode;
  $total = $show->media->episodes ? $show->media->episodes : '?';
  $desc = "Episode {$ep}/{$total}";

  // Stable UID: media id + episode + scheduled date
  $uid = $show->media->id . '-ep' . $ep . '-' . $dtStart->format('Ymd') . '@anisched';

  $out = "BEGIN:VEVENT\r\n";
  $out .= _icalFold("DTSTART:" . $dtStart->format('Ymd\THis'));
  $out .= _icalFold("DTEND:" . $dtEnd->format('Ymd\THis'));
  $out .= _icalFold("SUMMARY:" . _icalEsc($summary));
  $out .= _icalFold("DESCRIPTION:" . _icalEsc($desc));
  $out .= _icalFold("URL:https://anilist.co/anime/" . $show->media->id);
  $out .= _icalFold("UID:" . $uid);
  $out .= "END:VEVENT\r\n";

  return $out;
}

$shows = json_decode(file_get_contents(__DIR__ . '/shows.json'));
$startDate = new DateTime('@' . $shows->dates->start);
$endDate = new DateTime('@' . $shows->dates->end);
$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($startDate, $interval, $endDate);
$utcDiff = (new DateTimeZone($tz))->getOffset(new DateTime('now', new DateTimeZone('UTC')));

$events = [];
$curStartHour = $startHour;

foreach ($period as $dt) {
  $m = 0;
  $i = 0;
  $maxTS = $dt->getTimestamp() + 86400; // Get maximum timestamp for today

  if ($dt->format('N') == 6) { // Change min and start hour on Saturday
    $curStartHour = $weekendStartHour;

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

  foreach ($shows->airing as $show) {
    // If it airs today
    if ($show->airingAt < $maxTS) {
      // Add your difference with UTC + 1 hour for the release delay
      $air = $show->airingAt + $utcDiff + 3600;

      while (true) {
        $dt->setTime($curStartHour, $m, 0);

        if ($air <= $dt->getTimestamp()) {
          // If it can be added directly now in the schedule, do it
          $events[] = _icalEvent($show, clone $dt, true);
          $shows->airing = unsetValue($shows->airing, $show);

          $m += _dur($show->media->duration);
          $i++;
          break;
        } elseif ($i < ($min - 1) && count($shows->catchup) > 0) {
          // Or try to put an off-season anime to fill the time
          // if we've not reached $min yet and if there's some left
          foreach ($shows->catchup as $showC) {
            $events[] = _icalEvent($showC, clone $dt, false);
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
          $dt->setTime($curStartHour, $m, 0);

          // And then proceed like normal
          $events[] = _icalEvent($show, clone $dt, true);
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
    $dt->setTime($curStartHour, $m, 0);
    foreach ($shows->catchup as $show) {
      $events[] = _icalEvent($show, clone $dt, false);
      $shows->catchup = unsetValue($shows->catchup, $show);

      $m += _dur($show->media->duration);
      $i++;
      break;
    }
  }
}

echo "BEGIN:VCALENDAR\r\n";
echo "VERSION:2.0\r\n";
echo "PRODID:-//aniSched//aniSched//EN\r\n";
echo "CALSCALE:GREGORIAN\r\n";
echo "METHOD:PUBLISH\r\n";
echo _icalFold("X-WR-CALNAME:aniSched");
echo _icalFold("X-WR-TIMEZONE:{$tz}");

foreach ($events as $e) {
  echo $e;
}

echo "END:VCALENDAR\r\n";
