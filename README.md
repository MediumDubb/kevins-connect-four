# Welcome to Kevin's Connect4!
This is a light weight PHP-OOP project that I was assigned by a mentor a while back and never did :D

## Configs
If you wish to set this up on your local machine, here are some notes for you.
### .env keys 

DB_SERVER

DB_USERNAME

DB_PASSWORD

DB_CHARSET

DB_NAME

## SASSY Comp
```sh 
sass --watch theme/src/app.scss:public/assets/css/dist/app.min.css --style compressed
``` 

Notes so far:
refactoring everything all at once like a muppet because I have no focus.
No more entry controller only a page controller that displays the only page this app has.
All major logic is now in the GameApiController.
Merging the form and game javascript logic into one file too since this is now a SPA