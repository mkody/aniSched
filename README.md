# aniSched

- Put this project in a webserver with PHP
- Make sure `.token` can't be reached
- Make sure PHP can write in the folder
- Pull nodejs deps for our base CSS (`pnpm i`)
- Copy `config.php.dist` to `config.php` and edit it
  - Set `$client_id` and `$client_secret` from your [AniList app](https://anilist.co/settings/developer)
  - Set `$redirect_uri` to the URL to redirect back to the `login.php` page
- Go to `/login.php` in your browser and login to AniList
  - If you need to re-login, delete `.token` and repeat this step
- Launch `php fetch.php` in CLI
- Open `/index.php` in your browser to see the results
- Add a cron to run `fetch.php` every Monday morning (past 00:00 UTC)
- Make the changes you want in `config.php` to fit to your liking

## Goals

#### `fetch.php`

- To be run on Mondays
- Loads animes marked green in AniChart
- Gets the airing schedule from AniList and filters with the marked ones
- Gets the rest of the watch list (normally the "off-season" anime)
- Saves the results to `shows.json` (you may use this data for other purposes)

#### `index.php`

- Shows a schedule for the week
  - Weekday: Start at `$startHour` (ie. `21` (9pm))
  - Weekend: Start at `$weekendStartHour` (ie. `14` (2pm))
- It adds the shows airing on the day and with the time they should be online (air time + 1h)
  - If there's a gap between two new airing shows, it might try to fill with off-season anime if appropriate
- If there's less than `$min` (ie. `2`) new shows on a weekday, pull from off-season anime
  - On Saturday, `$min` is set to half the count of the remaining off-season anime for the week
  - Sunday then gets the rest

#### `ical.php`

- Works like `index.php` but outputs an iCal file instead of HTML, just subscribe to this URL in your calendar app (tested with Google Calendar)
