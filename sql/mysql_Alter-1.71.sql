ALTER TABLE sm_songsplayed 
ADD COLUMN steps_hash VARCHAR(50) AFTER difficulty DEFAULT NULL,
ADD COLUMN player_guid text AFTER username DEFAULT NULL;

ALTER TABLE sm_scores
ADD COLUMN steps_hash VARCHAR(50) AFTER difficulty DEFAULT NULL;

ALTER TABLE sm_requests
CHANGE state state enum('requested','canceled','completed','skipped','demanded') DEFAULT 'requested',