# Chat Bot Setup
*Note: This is legacy information visit [the wiki](https://github.com/MrTwinkles47/Stepmania-Stream-Tools-MrTwinkles/wiki/Twitch-Chatbot-Setup) for current bot setup information

## StreamElements and NightBot
*StreamElements and NightBot do not support importing of custom bot commands.*

1. Replace [URL] with your webiste URL and replace [KEY] with your security key.
2. Create a new command and paste the response from the text file.
3. Set user level, cooldowns, and command aliases.

## Make your own !random[whatever] commands
* The random commands function by RegEx matching in pack name and chart credit fields.
* Put your URL-encoded expression in the command after `random=`.
* EXAMPLE: Let's say you wanted to make a command that picks a random official DDR/DS song:
    1. Your pack names all have `Dance Dance Revolution` or `Dancing Stage` in them.
    2. URL-encode your names: `Dance+Dance+Revolution`,`Dancing+Stage`.
    3. Add the regex 'OR' character ('|', URL-encoded to '%7C'): `Dance+Dance+Revolution%7CDancing+Stage`
    4. Put this in the URL after `random=`: `random=Dance+Dance+Revolution%7CDancing+Stage`
    5. Final command (using SE variables): `${urlfetch https://[URL]/rand_request.php?security_key=[KEY]&broadcaster=${channel}&user=${user}&tier=${user.level}&game=${game}&random=Dance+Dance+Revolution%7CDancing+Stage&song=${queryescape ${1:|0}}}`
