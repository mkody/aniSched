# aniSched

### Goals
- Load animes I marked in AniChart (at least green, maybe the yellow too as optional)
- Get the airing schedule of anilist and filter with those I set in green
- Generate a "schedule" for me to watch sunday (starting 14:00) with episodes who already aired and the number of the episode
- Use my current anime list to know the number of the next episode
- Make it possible to make the saturday list automatically by setting a show and when I start it


### Notes
example params (i want to check like 8 days before and 8 days after if they aired)
```
{
  "weekStart": 1567382400,
  "weekEnd": 1568678400,
  "page": 1,
  "listIds": [21127, 87497, 97668, 97832, 98444, 98549, 98762, 99263, 99423, 99693, 99734, 100112, 100166, 100248, 100722, 100723, 100744, 100785, 100815, 101166, 101167, 101281, 101313, 101759, 101773, 101814, 101921, 102680, 102882, 102976, 103139, 103572, 103874, 103900, 104147, 104157, 104200, 104252, 104276, 104325, 104723, 105074, 105310, 105333, 105334, 105893, 105914, 105932, 106051, 106239, 106286, 106893, 107068, 107138, 107490, 107961, 108147, 108553, 108945, 109089, 109190, 109492, 110229]
}
```

how i extracted the list of ids from the `highlight` object in anichartuser saved in `temp1`
```
let list = {
  "green": [],
  "yellow": [],
  "red": []
}
for (const [key, value] of Object.entries(temp1)) {
  list[value].push(parseInt(key, 10))
}
console.log(list.green)
```
