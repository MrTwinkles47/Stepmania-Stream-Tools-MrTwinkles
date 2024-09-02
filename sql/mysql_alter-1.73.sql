--SQL changes for upgrades from 1.71/1.72 to 1.73:

-- From mysql_Alter-1.7x.sql
ALTER TABLE `sm_scores` CHANGE `survive_seconds` `survive_seconds` DECIMAL(12,6) NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `life_remaining_seconds` `life_remaining_seconds` DECIMAL(12,6) NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `w1` `w1` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `w2` `w2` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `w3` `w3` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `w4` `w4` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `w5` `w5` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `miss` `miss` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `held` `held` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `notes` `notes` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `mines` `mines` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `taps_holds` `taps_holds` SMALLINT(6) UNSIGNED NULL DEFAULT NULL;
ALTER TABLE `sm_scores` CHANGE `max_combo` `max_combo` INT(10) NULL DEFAULT NULL;

--Add Webhooks table
CREATE TABLE `sm_webhooks` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `url` varchar(2048) NOT NULL,
  `criteria` varchar(20) DEFAULT NULL,
  `qualifier` int(11) DEFAULT NULL,
  `jwt` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `sm_webhooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

ALTER TABLE `sm_webhooks`
  ADD PRIMARY KEY (`id`) USING BTREE;
  

-- force a rebuild of the song cache
UPDATE `sm_songs` SET `checksum` = NULL;
