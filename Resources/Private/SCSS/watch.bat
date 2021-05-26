SET SASS_SETTINGS=--style compressed --no-source-map

sass %SASS_SETTINGS% --watch ^
    chat.scss:../../Public/CSS/chat.css ^
    newmessages.scss:../../Public/CSS/newmessages.css
