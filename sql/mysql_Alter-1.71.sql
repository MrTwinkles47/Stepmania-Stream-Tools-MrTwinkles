ALTER TABLE sm_songsplayed 
ADD COLUMN IF NOT EXISTS steps_hash VARCHAR(50) AFTER difficulty,
ADD COLUMN IF NOT EXISTS player_guid text AFTER username;

ALTER TABLE sm_scores
ADD COLUMN IF NOT EXISTS steps_hash VARCHAR(50) AFTER difficulty;

ALTER TABLE sm_requests
CHANGE state state enum('requested','canceled','completed','skipped','demanded') DEFAULT 'requested';