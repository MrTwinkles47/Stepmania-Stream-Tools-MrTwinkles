# Stepmania-Stream-Tools-MrTwinkles
# SMRequests Fork
A tool for viewer song requests for live streaming StepMania 5 on Twitch
## Getting Started
**Check out the new [wiki](https://github.com/MrTwinkles47/Stepmania-Stream-Tools-MrTwinkles/wiki/Getting-Started)!**

---
## SMRequests features
* Public songlist webpage that supports searching and display of additional song/chart information.
* Viewers can request songs via Twitch chat.
* Viewers can specify steps-type or difficulties for a request
* Script for song scraping, which iterates through the SM5 Cache directory to index packs, songs, and chart metadata.
* Automatic request completion when using the stats scraper.
* Scraping of the Stats.xml files in the LocalProfile and USB profile directories.
* Support for multiple channels/broadcasters with a single SM5 instance and song database.
* Supports "offline" mode, for SM5 machines with no network access.
* On-stream request board to show current and completed requests, and information about the request, such as steps-type, difficulty, or request type.
* Broadcaster controls for the request board to complete, skip, or ban requests.
* Moderator control for toggling requests on or off via chat commands.
* Script for uploading banner images for each song pack for use with the request board.
* Random requests based on songs played and scores: !top, !random, !gitgud
* Ability to ban songs from being requested or being included in random commands. 
* On-stream session stats including recent scores, high score lists, and requestors.
* Ability to whitelist or ban users from making requests.
* Broadcaster can limit requests by steps-type and/or difficulty level.
---
## Limitations/Known Bugs
  * Only 4-panel "dance" mode is supported. Other modes that are supported by SM5 can be implemented, but they are not as of now.
  * Weird things may happen with random commands, if you start with a brand new profile Stats.xml file.
  * Stats.xml files from other judgement modes in Simply Love (FA+/Casual) are not supported.
  * Currently only one SM5 profile per broadcaster is preferred. The system will function with multiple SM5 profiles (ex. pad profile and a KB profle), but score based commands or calculating top songs may give odd results. 
  * StepMania 5 does not remove associated song cache files on song deletion. If you delete a song/pack, it is recommended to also delete the corresponding cache file(s) too. Disabling 'Fast Load' is also recommended.
  * The song request widget/board requires at least one song to continue to update automatically.
  * Sometimes the PHP-CLI scripts will hang. Pressing "enter" will gently encourage the script to get back to work.

## Milestones
 - [x] Multiple broadcaster support
 - [x] Stats.xml scraping
 - [x] Support for steps type and difficulties in requests
 - [x] Offline mode - support dedicated SM5 machines with no network access
 - [x] USB profile support
 - [ ] Songlist re-re-write
 - [ ] Fix custom chat bot
 - [ ] Docker support / Electron app

---
---

# Stepmania-Stream-Tools (From ddrDave's original fork. Sone information below might be out-of-date.)
Tools and utilities for interacting with Stepmania 5 to provide added features for live streaming.

### 0. Stepmania Scene Switching and Song Output
On my stream, I have OBS automatically switch between a "song select/evalution" scene (which shows the whole screen capture), calories burned, face camera, etc) and a "gameplay" scene, which only shows the Player 1 side of the video capture, as well as the input overlay, overhead camera, and current heart rate reading. The way this is accomplished is by having Stepmania output text to a specific text file when it switches to or from one of those screens.

I also output the currently-being-played song title to a different text file. This allows me to "check off" songs that have been requested, as soon as the song starts. This requires the use of a python script I wrote on the computer running Stepmania to watch for changes to this file, and send them off to a php script on my remote web server to parse. Details in the relevant readme.

### 0. Pulsoid Food/Calories Web Widget
I use Pulsoid (free) to display my current heart rate BPM on stream from my Wahoo Tickr heart rate strap. Pulsoid also offers a "calories burned" counter. I copied and modified that page to instead display total calories burned in relation to common food items, similar to DDR A.

### 0. DDR Input Indicator
I use an OBS plugin called **[Input Overlay](https://obsproject.com/forum/resources/input-overlay.552/)** to achieve this - I had to make a custom config file and two custom graphics for this, which I'll include here in the repo. The other key factor here is you need to get the keyboard inputs from your stepmania machine onto your streaming machine, or this won't work. So I use a piece of free software called **[Input Director](https://www.inputdirector.com)** to mirror the keyboard inputs from the Stepmania PC to the streaming PC. There's virtually no latency. Install the software on both PCs, setup your steaming PC as a slave and use the software on your Stepmania PC to "Mirror keyboard input across slaves".
