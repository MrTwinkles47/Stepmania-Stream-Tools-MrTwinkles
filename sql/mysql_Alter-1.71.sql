-- SQL changes for upgrades from 1.70 to 1.71:

-- add new columns for future functionallity
ALTER TABLE sm_songsplayed 
ADD COLUMN steps_hash VARCHAR(50) AFTER difficulty,
ADD COLUMN player_guid text AFTER username;

ALTER TABLE sm_scores
ADD COLUMN steps_hash VARCHAR(50) AFTER difficulty;
--
-- change request_type from ENUM to TEXT
ALTER TABLE sm_requests
CHANGE request_type request_type text;
--
-- add song_id indexes to speed up some request queries
ALTER TABLE sm_notedata
ADD INDEX `song_id` (`song_id`) USING BTREE;

ALTER TABLE sm_scores
ADD INDEX `song_id` (`song_id`) USING BTREE;

ALTER TABLE sm_songsplayed
ADD INDEX `song_id` (`song_id`) USING BTREE;
--
-- force a rebuild of the song cache
UPDATE `sm_songs` SET `checksum` = NULL;

-- Due to fixes for proper UTF-8 connection to the db,
-- we will need to do some utf-8 convertions of every table, column that would contain malformed utf8 strings
-- UPDATE [tabe] SET [column] = CONVERT(cast(CONVERT([column] USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_broadcaster` SET `broadcaster` = CONVERT(cast(CONVERT(`broadcaster` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_notedata` SET `song_dir` = CONVERT(cast(CONVERT(`song_dir` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_notedata` SET `chart_name` = CONVERT(cast(CONVERT(`chart_name` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_notedata` SET `description` = CONVERT(cast(CONVERT(`description` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_notedata` SET `chartstyle` = CONVERT(cast(CONVERT(`chartstyle` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_notedata` SET `credit` = CONVERT(cast(CONVERT(`credit` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_notedata` SET `stepfile_name` = CONVERT(cast(CONVERT(`stepfile_name` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_requestors` SET `name` = CONVERT(cast(CONVERT(`name` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_requests` SET `requestor` = CONVERT(cast(CONVERT(`requestor` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_scores` SET `song_dir` = CONVERT(cast(CONVERT(`song_dir` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_scores` SET `title` = CONVERT(cast(CONVERT(`title` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_scores` SET `pack` = CONVERT(cast(CONVERT(`pack` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_scores` SET `username` = CONVERT(cast(CONVERT(`username` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songs` SET `song_dir` = CONVERT(cast(CONVERT(`song_dir` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songs` SET `title` = CONVERT(cast(CONVERT(`title` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songs` SET `subtitle` = CONVERT(cast(CONVERT(`subtitle` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songs` SET `artist` = CONVERT(cast(CONVERT(`artist` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songs` SET `pack` = CONVERT(cast(CONVERT(`pack` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songs` SET `credit` = CONVERT(cast(CONVERT(`credit` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songsplayed` SET `song_dir` = CONVERT(cast(CONVERT(`song_dir` USING latin1) AS BINARY) USING utf8mb4);
UPDATE `sm_songsplayed` SET `username` = CONVERT(cast(CONVERT(`username` USING latin1) AS BINARY) USING utf8mb4);
