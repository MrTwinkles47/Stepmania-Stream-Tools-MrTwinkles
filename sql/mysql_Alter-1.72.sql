-- SQL changes for upgrades from 1.71 to 1.72:

-- rename columns to match charthash function
ALTER TABLE `sm_songsplayed` 
CHANGE COLUMN `steps_hash` `charthash` VARCHAR(50);

ALTER TABLE `sm_scores` 
CHANGE COLUMN `steps_hash` `charthash` VARCHAR(50);

-- add charthash column to notedata
ALTER TABLE `sm_notedata` 
ADD COLUMN `charthash` VARCHAR(50) AFTER `chartstyle`;

-- add new columns from Stats file
ALTER TABLE `sm_songsplayed` 
ADD COLUMN `profile_id` TEXT AFTER `player_guid`;
ALTER TABLE `sm_songsplayed`
ADD COLUMN `profile_type` TEXT AFTER `profile_id`;

ALTER TABLE `sm_scores` 
ADD COLUMN `profile_id` TEXT AFTER `username`;
ALTER TABLE `sm_scores`
ADD COLUMN `profile_type` TEXT AFTER `profile_id`;

-- force a rebuild of the song cache
UPDATE `sm_songs` SET `checksum` = NULL;
