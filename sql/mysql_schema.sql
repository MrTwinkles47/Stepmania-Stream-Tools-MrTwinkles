-- phpMyAdmin SQL Dump
-- version 4.9.5
-- https://www.phpmyadmin.net/
--
-- Generation Time: Feb 22, 2021 at 09:08 PM
-- Server version: 10.3.27-MariaDB
-- PHP Version: 7.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- --------------------------------------------------------

--
-- Table structure for table `sm_broadcaster`
--

CREATE TABLE `sm_broadcaster` (
  `id` int(11) NOT NULL,
  `broadcaster` text DEFAULT NULL,
  `stepstype` text DEFAULT NULL,
  `meter_min` varchar(11) DEFAULT NULL,
  `meter_max` varchar(11) DEFAULT NULL,
  `request_toggle` enum('ON','OFF') DEFAULT NULL,
  `message` mediumtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `sm_grade_tiers`
--

CREATE TABLE `sm_grade_tiers` (
  `percentdp` double(7,2) DEFAULT NULL,
  `ddr_tier` text DEFAULT NULL,
  `ddr_grade` varchar(50) DEFAULT NULL,
  `itg_tier` text DEFAULT NULL,
  `itg_grade` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `sm_grade_tiers`
--

REPLACE INTO `sm_grade_tiers` (`percentdp`, `ddr_tier`, `ddr_grade`, `itg_tier`, `itg_grade`) VALUES
(1.00, 'Tier01', 'AAA+', 'Tier01', '★★★★'),
(0.99, 'Tier02', 'AAA', 'Tier02', '★★★'),
(0.98, NULL, 'AA+', 'Tier03', '★★'),
(0.96, NULL, 'AA+', 'Tier04', '★'),
(0.95, 'Tier03', 'AA+', NULL, 'S+'),
(0.94, NULL, 'AA', 'Tier05', 'S+'),
(0.92, NULL, 'AA', 'Tier06', 'S'),
(0.90, 'Tier04', 'AA', NULL, 'S-'),
(0.89, 'Tier05', 'AA-', 'Tier07', 'S-'),
(0.86, NULL, 'A+', 'Tier08', 'A+'),
(0.85, 'Tier06', 'A+', NULL, 'A'),
(0.83, NULL, 'A', 'Tier09', 'A'),
(0.80, 'Tier07', 'A', 'Tier10', 'A-'),
(0.79, 'Tier08', 'A-', NULL, 'B+'),
(0.76, NULL, 'B+', 'Tier11', 'B+'),
(0.75, 'Tier09', 'B+', NULL, 'B'),
(0.72, NULL, 'B', 'Tier12', 'B'),
(0.70, 'Tier10', 'B', NULL, 'B-'),
(0.69, 'Tier11', 'B-', 'Tier13', 'B-'),
(0.68, NULL, 'C+', NULL, 'C+'),
(0.65, 'Tier12', 'C+', NULL, 'C+'),
(0.64, NULL, 'C', 'Tier14', 'C+'),
(0.60, 'Tier13', 'C', 'Tier15', 'C-'),
(0.59, 'Tier14', 'C-', NULL, 'C-'),
(0.55, 'Tier15', 'D+', 'Tier16', 'C-'),
(0.00, 'Tier17', 'D', NULL, 'D'),
(-99999.00, NULL, 'FAILED', 'Tier17', 'D'),
(0.50, 'Tier16', 'D', NULL, 'D');

-- --------------------------------------------------------

--
-- Table structure for table `sm_notedata`
--

CREATE TABLE `sm_notedata` (
  `id` int(11) NOT NULL,
  `song_id` mediumint(9) DEFAULT NULL,
  `song_dir` mediumtext DEFAULT NULL,
  `chart_name` text DEFAULT NULL,
  `stepstype` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `chartstyle` text DEFAULT NULL,
  `charthash` varchar(50) DEFAULT NULL,
  `difficulty` text DEFAULT NULL,
  `meter` int(11) DEFAULT NULL,
  `radar_values` text DEFAULT NULL,
  `credit` text DEFAULT NULL,
  `display_bpm` varchar(50) DEFAULT NULL,
  `stepfile_name` mediumtext DEFAULT NULL,
  `datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sm_requestors`
--

CREATE TABLE `sm_requestors` (
  `id` int(11) NOT NULL,
  `twitchid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `whitelisted` enum('true','false') DEFAULT 'false',
  `banned` enum('true','false') DEFAULT 'false',
  `dateadded` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sm_requests`
--

CREATE TABLE `sm_requests` (
  `id` int(11) NOT NULL,
  `song_id` int(11) DEFAULT NULL,
  `request_time` datetime DEFAULT NULL,
  `requestor` varchar(255) DEFAULT NULL,
  `twitch_tier` varchar(255) DEFAULT NULL,
  `broadcaster` tinytext DEFAULT NULL,
  `state` enum('requested','canceled','completed','skipped') DEFAULT 'requested',
  `request_type` text DEFAULT NULL,
  `stepstype` tinytext DEFAULT NULL,
  `difficulty` tinytext DEFAULT NULL,
  `timestamp` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sm_scores`
--

CREATE TABLE `sm_scores` (
  `id` int(11) NOT NULL,
  `song_dir` text DEFAULT NULL,
  `song_id` int(11) DEFAULT NULL,
  `title` text DEFAULT NULL,
  `pack` text DEFAULT NULL,
  `stepstype` mediumtext DEFAULT NULL,
  `difficulty` text DEFAULT NULL,
  `charthash` VARCHAR(50) DEFAULT NULL,
  `username` tinytext DEFAULT NULL,
  `profile_id` text DEFAULT NULL,
  `profile_type` text DEFAULT NULL,
  `grade` tinytext DEFAULT NULL,
  `score` bigint(20) DEFAULT NULL,
  `percentdp` decimal(10,6) DEFAULT NULL,
  `modifiers` text DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `survive_seconds` decimal(12,6) DEFAULT NULL,
  `life_remaining_seconds` decimal(12,6) DEFAULT NULL,
  `disqualified` tinyint(4) DEFAULT NULL,
  `max_combo` int(10) DEFAULT NULL,
  `stage_award` text DEFAULT NULL,
  `peak_combo_award` text DEFAULT NULL,
  `player_guid` text DEFAULT NULL,
  `machine_guid` text DEFAULT NULL,
  `hit_mine` smallint(6) DEFAULT NULL,
  `avoid_mine` smallint(6) DEFAULT NULL,
  `checkpoint_miss` smallint(6) DEFAULT NULL,
  `miss` smallint(6) UNSIGNED DEFAULT NULL,
  `w5` smallint(6) UNSIGNED DEFAULT NULL,
  `w4` smallint(6) UNSIGNED DEFAULT NULL,
  `w3` smallint(6) UNSIGNED DEFAULT NULL,
  `w2` smallint(6) UNSIGNED DEFAULT NULL,
  `w1` smallint(6) UNSIGNED DEFAULT NULL,
  `checkpoint_hit` smallint(6) DEFAULT NULL,
  `let_go` smallint(6) DEFAULT NULL,
  `held` smallint(6) UNSIGNED DEFAULT NULL,
  `missed_hold` smallint(6) DEFAULT NULL,
  `stream` decimal(10,6) DEFAULT NULL,
  `voltage` decimal(10,6) DEFAULT NULL,
  `air` decimal(10,6) DEFAULT NULL,
  `freeze` decimal(10,6) DEFAULT NULL,
  `chaos` decimal(10,6) DEFAULT NULL,
  `notes` smallint(6) UNSIGNED DEFAULT NULL,
  `taps_holds` smallint(6) UNSIGNED DEFAULT NULL,
  `jumps` smallint(6) DEFAULT NULL,
  `holds` smallint(6) DEFAULT NULL,
  `mines` smallint(6) UNSIGNED DEFAULT NULL,
  `hands` smallint(6) DEFAULT NULL,
  `rolls` smallint(6) DEFAULT NULL,
  `lifts` smallint(6) DEFAULT NULL,
  `fakes` smallint(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sm_songs`
--

CREATE TABLE `sm_songs` (
  `id` int(11) NOT NULL,
  `title` mediumtext DEFAULT NULL,
  `subtitle` mediumtext DEFAULT NULL,
  `artist` mediumtext DEFAULT NULL,
  `pack` mediumtext DEFAULT NULL,
  `strippedtitle` mediumtext DEFAULT NULL,
  `strippedsubtitle` mediumtext DEFAULT NULL,
  `strippedartist` mediumtext DEFAULT NULL,
  `song_dir` mediumtext DEFAULT NULL,
  `credit` text DEFAULT NULL,
  `display_bpm` varchar(50) DEFAULT NULL,
  `music_length` decimal(10,0) DEFAULT NULL,
  `bga` tinyint(4) DEFAULT NULL,
  `installed` tinyint(4) DEFAULT NULL,
  `banned` tinyint(4) DEFAULT 0,
  `added` datetime DEFAULT NULL,
  `checksum` varchar(50) DEFAULT NULL,
  `scraper` tinyint(4) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sm_songsplayed`
--

CREATE TABLE `sm_songsplayed` (
  `id` int(11) NOT NULL,
  `song_id` int(11) DEFAULT NULL,
  `song_dir` text DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `stepstype` text DEFAULT NULL,
  `difficulty` text DEFAULT NULL,
  `charthash` VARCHAR(50) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `player_guid` text DEFAULT NULL,
  `profile_id` text DEFAULT NULL,
  `profile_type` text DEFAULT NULL,
  `numplayed` int(11) DEFAULT NULL,
  `lastplayed` datetime DEFAULT NULL,
  `datetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Create Table `sm_webhooks`
--
CREATE TABLE `sm_webhooks` (
  `id` int(11) NOT NULL,
  `type` int(11) NOT NULL,
  `url` varchar(2048) NOT NULL,
  `criteria` varchar(20) DEFAULT NULL,
  `qualifier` int(11) DEFAULT NULL,
  `jwt` varchar(512) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `sm_broadcaster`
--
ALTER TABLE `sm_broadcaster`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `sm_notedata`
--
ALTER TABLE `sm_notedata`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD INDEX `song_id` (`song_id`) USING BTREE;

--
-- Indexes for table `sm_requestors`
--
ALTER TABLE `sm_requestors`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `sm_requests`
--
ALTER TABLE `sm_requests`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD INDEX `song_id` (`song_id`) USING BTREE;

--
-- Indexes for table `sm_scores`
--
ALTER TABLE `sm_scores`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD INDEX `song_id` (`song_id`) USING BTREE;

--
-- Indexes for table `sm_songs`
--
ALTER TABLE `sm_songs`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- Indexes for table `sm_songsplayed`
--
ALTER TABLE `sm_songsplayed`
  ADD PRIMARY KEY (`id`) USING BTREE,
  ADD INDEX `song_id` (`song_id`) USING BTREE;

--
-- Indexes for table `sm_webhooks`
--
ALTER TABLE `sm_webhooks`
  ADD PRIMARY KEY (`id`) USING BTREE;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `sm_broadcaster`
--
ALTER TABLE `sm_broadcaster`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_notedata`
--
ALTER TABLE `sm_notedata`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_requestors`
--
ALTER TABLE `sm_requestors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_requests`
--
ALTER TABLE `sm_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_scores`
--
ALTER TABLE `sm_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_songs`
--
ALTER TABLE `sm_songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sm_songsplayed`
--
ALTER TABLE `sm_songsplayed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

--
-- AUTO_INCREMENT for table `sm_webhooks`
--
ALTER TABLE `sm_webhooks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
