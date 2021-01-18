# aniSched

- Put this project in a webserver with PHP
- Make sure `token.txt` can't be reached
- Make sure PHP can write in the folder
- Pull nodejs deps for our base CSS (`yarn` or `npm i`)
- Copy `secrets.php.dist` to `secrets.php` and edit it
  - Set `$client_id` and `$client_secret` from your [AniList app](https://anilist.co/settings/developer)
  - Set `$redirect_uri` to the URL to redirect back to the `login.php` page
- Go to `/login.php` in your browser and login to AniList
- Launch `php fetch.php` in CLI
- Open `/index.php` in your browser to see the results
- Add a cron to run `fetch.php` every Monday morning

## Goals

#### `fetch.php`

- To be run on Mondays
- Load animes I marked green in AniChart
- Get the airing schedule from AniList and filter with the marked ones
- Get the rest of my watch list (normally the "off-season" anime)

#### `index.php`

- Weekday: Start at 9pm (`$startHour`)
- Weekend: Start at 2pm (`$weekendStartHour`)
- Add the shows airing on the day and with the time they should be online (air time + 1h)
  - If there's a gap between two new airing shows, try to fill with off-season anime if appropriate
- If there's less than two new shows on a weekday (`$min`), pull from off-season anime
  - On weekends, `$min` is set to half the count of the remaining off-season anime for the week

#### `weekend.php`

- Sunday for currently airing anime, Saturday for the rest
- Start at 2pm (`$startHour`, "h")
- Add breaks:
  - Between h+2 to h+3 (snacks)
  - Between h+4.5 to h+7 (diner)  
    (did bump resume time to 8.5 on Saturday because I have a radio show)

