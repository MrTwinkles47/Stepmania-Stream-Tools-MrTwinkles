-- SQL changes for upgrades from 1.72 to 1.73:

-- add csong time column to broadcaster
ALTER TABLE `sm_broadcaster` 
ADD COLUMN `song_time` time AFTER `stepstype`;
