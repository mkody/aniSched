# aniSched

- Put this project in a webserver
- Make sure `token.txt` can't be reached
- Make sure PHP can write in the folder
- Pull nodejs deps for our base CSS (`yarn` / `npm i`)
- Copy `secrets.php.dist` to `secrets.php` and fill in
- Go to `/login.php` and login to AniList
- Launch `fetch.php` in CLI
- Open `/index.php` in your browser to see the results
- Add a cron to run `fetch.php` every Monday morning

## Goals

#### `fetch.php`

- Build list on Monday
- Load animes I marked green in AniChart
- Get the airing schedule from AniList and filter with the marked ones
- Get the rest of my watch list

#### `index.php`

- Weekday: Start at 9pm (`$startHour`)
- Weekend: Start at 2pm (`$weekendStartHour`)
- Add the shows airing on the day they should be available
- If there's less than two shows on a day (`$min`), pull from my remaining watch list
- Dump the rest on Sunday if they couldn't fit before

#### `weekend.php`

- Sunday for currently airing anime, Saturday for the rest
- Start at 2pm (`$startHour`)
- Add breaks:
  - Between 2 to 3 hours since start (snacks)
  - Between 4.5 to 7 hours since start (diner)  
    (did bump resume time to 8.5 on Saturday because I have a radio show)

