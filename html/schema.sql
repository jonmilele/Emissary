-- ============================================
-- Database schema for emissary
-- Reconstructed from PHP source code
-- ============================================

CREATE DATABASE IF NOT EXISTS `emissary`;
USE `emissary`;

-- ----------------------------
-- Players
-- ----------------------------
CREATE TABLE `players` (
  `PlayerID` INT AUTO_INCREMENT PRIMARY KEY,
  `UserName` VARCHAR(50) NOT NULL,
  `Password` VARBINARY(255) NOT NULL,
  `Email` VARCHAR(100) DEFAULT NULL,
  `Location` VARCHAR(100) DEFAULT NULL,
  `DateJoined` DATETIME DEFAULT NULL,
  `Country` VARCHAR(50) DEFAULT NULL,
  `SetupStage` INT DEFAULT 0,
  `TeamID` INT DEFAULT 0,
  `Metal` INT DEFAULT 0,
  `Mineral` INT DEFAULT 0,
  `Astrium` INT DEFAULT 0,
  `Credits` INT DEFAULT 0,
  `HomePlanetID` INT DEFAULT 0,
  `LastLogin` DATETIME DEFAULT NULL,
  `LoginMetal` INT DEFAULT 0,
  `LoginMineral` INT DEFAULT 0,
  `LoginAstrium` INT DEFAULT 0,
  `LoginPlanets` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Teams
-- ----------------------------
CREATE TABLE `teams` (
  `TeamID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(100) NOT NULL,
  `Colour` VARCHAR(20) DEFAULT '255,255,255',
  `LeaderID` INT DEFAULT 0,
  `VoteActive` TINYINT DEFAULT 0,
  `VoteTurnsLeft` INT DEFAULT 0,
  `LastElectionTurn` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Team leader election votes (active)
-- ----------------------------
CREATE TABLE `team_votes` (
  `TeamID` INT NOT NULL,
  `VoterID` INT NOT NULL,
  `CandidateID` INT NOT NULL,
  PRIMARY KEY (`TeamID`, `VoterID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Election motions (no-confidence)
-- ----------------------------
CREATE TABLE `election_motions` (
  `TeamID` INT PRIMARY KEY,
  `ProposerID` INT NOT NULL,
  `CreatedAt` DATETIME NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Election motion seconds
-- ----------------------------
CREATE TABLE `election_motion_seconds` (
  `TeamID` INT NOT NULL,
  `PlayerID` INT NOT NULL,
  PRIMARY KEY (`TeamID`, `PlayerID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Team join requests
-- ----------------------------
CREATE TABLE `team_join_requests` (
  `RequestID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlayerID` INT NOT NULL,
  `TeamID` INT NOT NULL,
  `RequestedAt` DATETIME NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Team election history
-- ----------------------------
CREATE TABLE `team_election_history` (
  `ElectionID` INT AUTO_INCREMENT PRIMARY KEY,
  `TeamID` INT NOT NULL,
  `WinnerID` INT NOT NULL,
  `Votes` INT DEFAULT 0,
  `RunnerUpID` INT DEFAULT 0,
  `RunnerUpVotes` INT DEFAULT 0,
  `TotalVoters` INT DEFAULT 0,
  `ResolvedAt` DATETIME NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Sectors (10x10 galaxy grid)
-- ----------------------------
CREATE TABLE `sectors` (
  `SectorID` INT PRIMARY KEY,
  `Name` VARCHAR(100) DEFAULT NULL,
  `MajOwner` INT DEFAULT 0,
  `MajTeamID` INT DEFAULT 0,
  `GridCoords` VARCHAR(10) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Systems (star systems within sectors)
-- ----------------------------
CREATE TABLE `Systems` (
  `SystemID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(100) NOT NULL,
  `Orbits` INT DEFAULT 1,
  `PlayerID` INT DEFAULT 0,
  `TeamID` INT DEFAULT 0,
  `SectorID` INT DEFAULT 0,
  `Coords` VARCHAR(20) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Planets
-- ----------------------------
CREATE TABLE `planets` (
  `PlanetID` INT AUTO_INCREMENT PRIMARY KEY,
  `Name` VARCHAR(100) NOT NULL,
  `Orbit` INT DEFAULT 1,
  `System` INT DEFAULT 0,
  `Size` INT DEFAULT 1,
  `PlayerID` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Planet types (size definitions)
-- ----------------------------
CREATE TABLE `planet_types` (
  `Type` INT PRIMARY KEY,
  `Grids` INT DEFAULT 0,
  `xstart` INT DEFAULT 0,
  `ystart` INT DEFAULT 0,
  `rowsquares` INT DEFAULT 0,
  `income` VARCHAR(30) DEFAULT '0:0:0'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Building types (lookup)
-- ----------------------------
CREATE TABLE `building_types` (
  `Type` INT PRIMARY KEY,
  `Name` VARCHAR(50) NOT NULL,
  `HP` INT DEFAULT 0,
  `AP` INT DEFAULT 0,
  `Metal` INT DEFAULT 0,
  `Mineral` INT DEFAULT 0,
  `Astrium` INT DEFAULT 0,
  `Turns` INT DEFAULT 1,
  `Colour` VARCHAR(20) DEFAULT '255,255,255'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Buildings (placed on planet grids)
-- ----------------------------
CREATE TABLE `buildings` (
  `BuildingID` INT AUTO_INCREMENT PRIMARY KEY,
  `Type` INT DEFAULT 0,
  `PlanetID` INT DEFAULT 0,
  `GridSquare` INT DEFAULT 0,
  `HP` INT DEFAULT 0,
  `PlayerID` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Buildings under construction
-- ----------------------------
CREATE TABLE `cbuildings` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `Type` INT DEFAULT 0,
  `PlayerID` INT DEFAULT 0,
  `PlanetID` INT DEFAULT 0,
  `Grid` INT DEFAULT 0,
  `TTF` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Ship types (lookup)
-- ----------------------------
CREATE TABLE `ship_types` (
  `Type` INT PRIMARY KEY,
  `Name` VARCHAR(50) NOT NULL,
  `HP` INT DEFAULT 0,
  `AP` INT DEFAULT 0,
  `Metal` INT DEFAULT 0,
  `Mineral` INT DEFAULT 0,
  `Astrium` INT DEFAULT 0,
  `Turns` INT DEFAULT 1
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Ships
-- ----------------------------
CREATE TABLE `ships` (
  `ShipID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlayerID` INT DEFAULT 0,
  `Type` INT DEFAULT 0,
  `PlanetID` INT DEFAULT 0,
  `FleetID` INT DEFAULT 0,
  `Name` VARCHAR(100) DEFAULT '',
  `HP` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Ships under construction
-- ----------------------------
CREATE TABLE `cships` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlayerID` INT DEFAULT 0,
  `Type` INT DEFAULT 0,
  `Yard` VARCHAR(20) DEFAULT NULL,
  `TTF` INT DEFAULT 0,
  `Name` VARCHAR(100) DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Ship build queue
-- ----------------------------
CREATE TABLE `qships` (
  `ShipID` INT AUTO_INCREMENT PRIMARY KEY,
  `Type` INT DEFAULT 0,
  `Name` VARCHAR(100) DEFAULT '',
  `Yard` VARCHAR(20) DEFAULT NULL,
  `QueuePosition` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Fleets
-- ----------------------------
CREATE TABLE `fleets` (
  `FleetID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlayerID` INT DEFAULT 0,
  `Location` VARCHAR(30) DEFAULT NULL,
  `Destination` VARCHAR(30) DEFAULT NULL,
  `MovingFrom` VARCHAR(30) DEFAULT NULL,
  `Strategy` INT DEFAULT 0,
  `TTF` INT DEFAULT 0,
  `Name` VARCHAR(100) DEFAULT ''
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Battles
-- ----------------------------
CREATE TABLE `battles` (
  `BattleID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlanetID` INT DEFAULT 0,
  `Defender` INT DEFAULT 0,
  `Attacker` INT DEFAULT 0,
  `Winner` INT DEFAULT 0,
  `Date` DATETIME DEFAULT NULL,
  `Log` TEXT DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Game log (legacy — replaced by alerts)
-- ----------------------------
CREATE TABLE `gamelog` (
  `LogID` INT AUTO_INCREMENT PRIMARY KEY,
  `Time` INT DEFAULT 0,
  `PlayerID` INT DEFAULT 0,
  `Code` INT DEFAULT 0,
  `Data` VARCHAR(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Alerts (player activity log & notifications)
-- ----------------------------
CREATE TABLE `alerts` (
  `AlertID` INT AUTO_INCREMENT PRIMARY KEY,
  `PlayerID` INT NOT NULL,
  `Category` VARCHAR(20) NOT NULL DEFAULT 'system',
  `Message` VARCHAR(500) NOT NULL,
  `LinkURL` VARCHAR(255) DEFAULT NULL,
  `IsRead` TINYINT DEFAULT 0,
  `CreatedAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_player_read` (`PlayerID`, `IsRead`),
  INDEX `idx_player_created` (`PlayerID`, `CreatedAt`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Auctions
-- ----------------------------
CREATE TABLE `auctions` (
  `AuctionID` INT AUTO_INCREMENT PRIMARY KEY,
  `OpenTo` INT DEFAULT 0,
  `Code` INT DEFAULT 0,
  `Data` VARCHAR(100) DEFAULT NULL,
  `Seller` INT DEFAULT 0,
  `StartTime` INT DEFAULT 0,
  `Turns` INT DEFAULT 0,
  `StartBid` INT DEFAULT 0,
  `CurrentBid` INT DEFAULT 0,
  `HighBidder` INT DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Game settings (key-value store, replaces flat files)
-- ----------------------------
CREATE TABLE `game_settings` (
  `setting_key` VARCHAR(64) PRIMARY KEY,
  `setting_value` TEXT DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ----------------------------
-- Known systems (per-player explored systems)
-- ----------------------------
CREATE TABLE `known_systems` (
  `PlayerID` INT NOT NULL,
  `SystemID` INT NOT NULL,
  PRIMARY KEY (`PlayerID`, `SystemID`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- ============================================
-- Reference data
-- ============================================

-- Planet types (4 sizes found in planet.php)
INSERT INTO `planet_types` (`Type`, `Grids`, `xstart`, `ystart`, `rowsquares`, `income`) VALUES
(1, 100, 100, 50, 10, '100:50:10'),
(2, 121, 75, 75, 11, '120:60:12'),
(3, 81, 130, 85, 9, '80:40:8'),
(4, 144, 75, 25, 12, '150:75:15');

-- Building types (inferred from code references)
-- Types: 1=Factory, 2=Laboratory, 3=Harvester, 4=Shipyard,
--        5=Hangar, 6=Shield, 7=Pulse Cannon, 8=Gigashield, 9=Missile Silo
INSERT INTO `building_types` (`Type`, `Name`, `HP`, `AP`, `Metal`, `Mineral`, `Astrium`, `Turns`, `Colour`) VALUES
(1, 'Factory',       500, 0,   100, 50,  0, 1, '255,0,0'),
(2, 'Laboratory',    500, 0,   150, 100, 0, 1, '0,102,255'),
(3, 'Harvester',     500, 0,   100, 50,  0, 1, '0,255,0'),
(4, 'Shipyard',      1000, 0,  200, 150, 0, 2, '255,153,0'),
(5, 'Hangar',        800, 0,   150, 100, 0, 1, '255,255,255'),
(6, 'Shield',        2000, 0,  300, 200, 10, 2, '255,255,0'),
(7, 'Pulse Cannon',  1500, 2000, 250, 200, 10, 2, '204,0,255'),
(8, 'Gigashield',    5000, 0,  500, 400, 50, 3, '255,153,255'),
(9, 'Missile Silo',  3000, 6000, 400, 300, 50, 3, '153,153,153');

-- Ship types (inferred from ShipBundle class and code)
-- Types: 2=Transport, 3=Coloniser, 4=Frigate, 5=Cruiser,
--        6=Warship, 7=Mothership, 8=Fighter
INSERT INTO `ship_types` (`Type`, `Name`, `HP`, `AP`, `Metal`, `Mineral`, `Astrium`, `Turns`) VALUES
(2, 'Transport',   500,  0,    100, 50,  0,  1),
(3, 'Coloniser',   500,  0,    200, 100, 10, 2),
(4, 'Frigate',     1000, 500,  300, 200, 10, 2),
(5, 'Cruiser',     2000, 1000, 500, 300, 20, 3),
(6, 'Warship',     4000, 2000, 800, 500, 50, 5),
(7, 'Mothership',  8000, 4000, 1500, 1000, 100, 10),
(8, 'Fighter',     300,  200,  50,  30,  0,  1);

-- Seed 100 sectors with grid coordinates (10x10 galaxy)
INSERT INTO `sectors` (`SectorID`, `GridCoords`) VALUES
(1,'1.1'),(2,'2.1'),(3,'3.1'),(4,'4.1'),(5,'5.1'),(6,'6.1'),(7,'7.1'),(8,'8.1'),(9,'9.1'),(10,'10.1'),
(11,'1.2'),(12,'2.2'),(13,'3.2'),(14,'4.2'),(15,'5.2'),(16,'6.2'),(17,'7.2'),(18,'8.2'),(19,'9.2'),(20,'10.2'),
(21,'1.3'),(22,'2.3'),(23,'3.3'),(24,'4.3'),(25,'5.3'),(26,'6.3'),(27,'7.3'),(28,'8.3'),(29,'9.3'),(30,'10.3'),
(31,'1.4'),(32,'2.4'),(33,'3.4'),(34,'4.4'),(35,'5.4'),(36,'6.4'),(37,'7.4'),(38,'8.4'),(39,'9.4'),(40,'10.4'),
(41,'1.5'),(42,'2.5'),(43,'3.5'),(44,'4.5'),(45,'5.5'),(46,'6.5'),(47,'7.5'),(48,'8.5'),(49,'9.5'),(50,'10.5'),
(51,'1.6'),(52,'2.6'),(53,'3.6'),(54,'4.6'),(55,'5.6'),(56,'6.6'),(57,'7.6'),(58,'8.6'),(59,'9.6'),(60,'10.6'),
(61,'1.7'),(62,'2.7'),(63,'3.7'),(64,'4.7'),(65,'5.7'),(66,'6.7'),(67,'7.7'),(68,'8.7'),(69,'9.7'),(70,'10.7'),
(71,'1.8'),(72,'2.8'),(73,'3.8'),(74,'4.8'),(75,'5.8'),(76,'6.8'),(77,'7.8'),(78,'8.8'),(79,'9.8'),(80,'10.8'),
(81,'1.9'),(82,'2.9'),(83,'3.9'),(84,'4.9'),(85,'5.9'),(86,'6.9'),(87,'7.9'),(88,'8.9'),(89,'9.9'),(90,'10.9'),
(91,'1.10'),(92,'2.10'),(93,'3.10'),(94,'4.10'),(95,'5.10'),(96,'6.10'),(97,'7.10'),(98,'8.10'),(99,'9.10'),(100,'10.10');

-- Default game settings (configurable via admin panel)
INSERT INTO `game_settings` (`setting_key`, `setting_value`) VALUES
('home_hp_multiplier', '1.5'),
('home_income_multiplier', '2'),
('buy_planet_metal', '2000'),
('buy_planet_mineral', '1000'),
('buy_planet_astrium', '200'),
('harvester_bonus', '0.05'),
('election_duration', '5'),
('election_auto_interval', '100'),
('election_motion_threshold', '25'),
('starting_metal', '500'),
('starting_mineral', '250'),
('starting_astrium', '50'),
('planet_weapon_hit_chance', '3');
