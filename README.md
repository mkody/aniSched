# aniSched

- Put this project in a webserver with PHP
- Make sure `.token` can't be reached
- Make sure PHP can write in the folder
- Pull nodejs deps for our base CSS (`yarn` or `npm i`)
- Copy `config.php.dist` to `config.php` and edit it
  - Set `$client_id` and `$client_secret` from your [AniList app](https://anilist.co/settings/developer)
  - Set `$redirect_uri` to the URL to redirect back to the `login.php` page
- Go to `/login.php` in your browser and login to AniList
- Launch `php fetch.php` in CLI
- Open `/index.php` in your browser to see the results
- Add a cron to run `fetch.php` every Monday morning (past 00:00 UTC)
- Make the changes you want in `config.php` to fit to your liking

## Goals

#### `fetch.php`

- To be run on Mondays
- Load animes I marked green in AniChart
- Get the airing schedule from AniList and filter with the marked ones
- Get the rest of my watch list (normally the "off-season" anime)

#### `index.php`

- Weekday: Start at `$startHour` (ie. `21` (9pm))
- Weekend: Start at `$weekendStartHour` (ie. `14` (2pm))
- Add the shows airing on the day and with the time they should be online (air time + 1h)
  - If there's a gap between two new airing shows, try to fill with off-season anime if appropriate
- If there's less than two new shows on a weekday (`$min`), pull from off-season anime
  - On weekends, `$min` is set to half the count of the remaining off-season anime for the week
