# What is this?

These scrapers parse all local StepMania data required to run the request system. A pre-configured PHP cli environment is required. For Windows systems, convenient .bat files are provided.

## Scrape Songs Cache

This script parses the song cache files to add/update songs to the sm_songs and sm_notedata tables.

## Banner uploading

This script finds the first image file in each pack folder, formats the name to match the name of the pack, and uploads it to the server.

## Scrape Stats

The stats scraper is responsible for automatically grabbing all your highscores and recently played songs from your Stats.xml file(s) (usually at the song evaluation screen). The data is used to automatically complete open song requests and provide information for random commands (top, gitgud, etc.). Configure the active local profile(s) or USB profile(s) in your config.php file.

# Usage (first-run)
* Start StepMania and allow it to build its song cache files
* Run "scrape songs.bat"
    * A note on deleting songs or song packs/groups: StepMania does not automatically remove the associated cache files after a song is deleted. Make sure you delete the song's cache file before running the scraper. I recommend disabling 'Fast Load' in StepMania's settings.
* Run "scrape stats.bat" to upload your profile's high scores and songs played records to the DB. Once the script says "Done.", you may close it.
* Run "upload banners.bat" to upload your pack/group banners to the server to later be used as song background images on the request board.

# Usage
* When you add or remove songs/packs from StepMania:
    * Run the "scrape songs.bat" again to update the online songlist  
    * Run "upload banners.bat" to upload new pack/group banners
* Run "scrape stats.bat" everytime you stream. This script is also responsible for completing/marking-off requests on the request board.
