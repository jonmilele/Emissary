<?php

// HTML escaping shorthand
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

include_once("fleetfunctions.inc.php");
include_once("planetfunctions.inc.php");
include_once("buildingfunctions.inc.php");
include_once("turnfunctions.inc.php");
include_once("alertfunctions.inc.php");
include_once("resourcefunctions.inc.php");
function untag($string,$tag,$mode){
	$tmpval="";
	$preg="/<".$tag.">(.*?)<\/".$tag.">/si";
	preg_match_all($preg,$string,$tags);
	foreach ($tags[1] as $tmpcont){
			if ($mode==1){$tmpval[]=$tmpcont;}
			else {$tmpval.=$tmpcont;}
			}
	return $tmpval;
}
function load($filelocation){
	if (file_exists($filelocation))
	{
		$newfile = fopen($filelocation,"r");
		$file_content = fread($newfile, filesize($filelocation));
		fclose($newfile);
		return $file_content;
	}
}
function UntagGroup($string,$tag){
	$preg="/<".$tag.">(.*?)<\/".$tag.">/si";
	preg_match_all($preg,$string,$tags);
	return $tags[0];	
}

function ReadTopLevel($file, $tag){
	$info=untag(load($file),$tag,1);
	
	return $info;
}
function ReadTagContent($tags, $tag){
	
	$name=untag($tags,$tag,0);
	return $name;
}
//
// userfunctions.inc.php
// Contains user-specific functions.
//
function GetPlayerIDFromName($username){

	$query = "SELECT PlayerID FROM players WHERE(UserName='$username')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$result = mysqli_fetch_object($notresult) or die(mysqli_error($GLOBALS["conn"]));
	
	return $result->PlayerID;
}
function GetPlayerNameFromID($PlayerID,$Else = "No User"){
	if($PlayerID==0){
		return $Else;
	}
	$query = "SELECT UserName FROM players WHERE(PlayerID='$PlayerID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$result = mysqli_fetch_object($notresult) or die(mysqli_error($GLOBALS["conn"]));
	if($result->UserName!=""){
		return $result->UserName;
	}else{
		return $Else;
	}
}

function GetNumberOfPlanets($username){
	
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM planets WHERE(PlayerID = '$username')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
			
			return $total_count;
}
class Planet{
	var $PlanetID;
	var $Name;
	var $DefaultName;
	var $Orbit;
	var $System;
	var $Size;
	var $PlayerID;
}

function GetPlanetList($PlayerID){
	if($PlayerID<1){
		return;
	}
	$Planets = array();
	
	$query = "SELECT * FROM planets WHERE(PlayerID='$PlayerID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$Planet = new Planet();
		$Planet->PlanetID = $row->PlanetID;
		$Planet->DefaultName = $row->DefaultName ?? null;
		$Planet->Name = $row->Name ?? $row->DefaultName;
		$Planet->System = $row->System;
		$Planet->Orbit = $row->Orbit;
		$Planet->Size = $row->Size;
		$Planet->PlayerID = $row->PlayerID;
		
		$Planets[$row->PlanetID] = $Planet;
	}
	return $Planets;	
}

function IsPlanet($PlanetID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM planets WHERE(PlanetID = '$PlanetID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	
	if($total_count>0)
		return true;
	else
		return false;
}

function OwnsPlanet($username,$PlanetID){
	$query = "SELECT PlayerID FROM planets WHERE(PlanetID='$PlanetID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return false;
	if($row->PlayerID==GetPlayerIDFromName($username)){
		return true;
	}else{
		return false;
	}
}

function GetPlanetNameFromID($PlanetID){
	if(!$PlanetID) return "Unknown";
	$query = "SELECT Name, DefaultName FROM planets WHERE(PlanetID='$PlanetID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return "Unknown";
	return $row->Name ?? $row->DefaultName ?? "Unknown";
}
function GetPlanetPictureFromID($PlanetID){
	global $username;
	if(OwnsPlanet($username,$PlanetID)){
		return "planetimage.img.php?id=".$PlanetID;
	}
	// Team members also see the full grid overlay
	$query = "SELECT PlayerID, Size FROM planets WHERE(PlanetID='$PlanetID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return "images/planets/1.jpg";
	if($row->PlayerID > 0){
		$viewerTeam = PlayerTeam(GetPlayerIDFromName($username));
		$ownerTeam = PlayerTeam($row->PlayerID);
		if($viewerTeam > 0 && $viewerTeam == $ownerTeam){
			return "planetimage.img.php?id=".$PlanetID;
		}
	}
	return "images/planets/".$row->Size.".jpg";
}

function GetPlanet($PlanetID){
	$query = "SELECT * FROM planets WHERE(PlanetID='$PlanetID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return null;
	$Planet = new Planet();
	$Planet->PlanetID = $row->PlanetID;
	$Planet->DefaultName = $row->DefaultName ?? null;
	$Planet->Name = $row->Name ?? $row->DefaultName;
	$Planet->System = $row->System;
	$Planet->Orbit = $row->Orbit;
	$Planet->Size = $row->Size;
	$Planet->PlayerID = $row->PlayerID;
	
	return $Planet;	
}
function IsSystem($SystemID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM Systems WHERE(SystemID = '$SystemID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	
	if($total_count>0)
		return true;
	else
		return false;
}

class System{
	var $SystemID;
	var $Name;
	var $DefaultName;
	var $Orbits;
	var $PlayerID;
	var $TeamID;
	var $Coords;
	var $SectorID;
}

function GetSystemList($username){
	$Planets = array();
	
	$query = "SELECT * FROM Systems WHERE(PlayerID='".GetPlayerIDFromName($username)."')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){		
		$Planets[$row->SystemID] = GetSystem($row->SystemID);
	}
	return $Planets;	
}
function GetSystem($SystemID){
	$query = "SELECT * FROM Systems WHERE(SystemID='$SystemID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return null;
	$Planet = new System();
	$Planet->SystemID = $row->SystemID;
	$Planet->DefaultName = $row->DefaultName ?? null;
	$Planet->Name = $row->Name ?? $row->DefaultName;
	$Planet->Orbits = $row->Orbits;
	$Planet->PlayerID = $row->PlayerID;
	$Planet->TeamID = $row->TeamID ?? 0;
	$Planet->SectorID = $row->SectorID;
	$Planet->Coords = $row->Coords;
	
	return $Planet;	
}

function CheckSystemMajOwner($SystemID){
	$Players = array();
	$Teams = array();
	$query = "SELECT p.PlayerID, pl.TeamID FROM planets p LEFT JOIN players pl ON p.PlayerID = pl.PlayerID WHERE(p.`System`='$SystemID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->PlayerID!=0){
			$Players[$row->PlayerID]["Player"] = $row->PlayerID;
			$Players[$row->PlayerID]["Count"] = ($Players[$row->PlayerID]["Count"] ?? 0) + 1;
			// Track team ownership
			$tid = (int)($row->TeamID ?? 0);
			if($tid > 0){
				$Teams[$tid] = ($Teams[$tid] ?? 0) + 1;
			}
		}
	}
	// Player majority
	$largestcount = 0;
	$player = 0;
	foreach($Players as $k=>$Player){
		if($Player["Count"]>$largestcount){
			$largestcount = $Player["Count"];
			$player = $Player["Player"];
		}
	}
	//Check for joint majority
	foreach($Players as $k=>$Player){
		if($Player["Player"]!=$player){
			if($Player["Count"]==$largestcount){
				$player=0;
			}
		}
	}
	// Team majority
	$teamLargest = 0;
	$majTeam = 0;
	foreach($Teams as $tid=>$cnt){
		if($cnt > $teamLargest){
			$teamLargest = $cnt;
			$majTeam = $tid;
		}
	}
	foreach($Teams as $tid=>$cnt){
		if($tid != $majTeam && $cnt == $teamLargest){
			$majTeam = 0;
			break;
		}
	}
	$query = "UPDATE Systems SET PlayerID = $player, TeamID = $majTeam WHERE(SystemID='$SystemID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	
	return $player;
}

function PlanetsInSystem($SystemID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM planets WHERE(`System` = '$SystemID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}

function ListPlanetsInSystem($SystemID){
$Planets = array();
	
	$query = "SELECT * FROM planets WHERE(`System`='$SystemID') ORDER BY Orbit ASC";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$Planet = new Planet();
		$Planet->PlanetID = $row->PlanetID;
		$Planet->DefaultName = $row->DefaultName ?? null;
		$Planet->Name = $row->Name ?? $row->DefaultName;
		$Planet->System = $row->System;
		$Planet->Orbit = $row->Orbit;
		$Planet->Size = $row->Size;
		$Planet->PlayerID = $row->PlayerID;
		
		$Planets[$row->PlanetID] = $Planet;
	}
	return $Planets;
}

function GetSystemPictureFromID($SystemID){
	$query = "SELECT Orbits FROM Systems WHERE(SystemID='$SystemID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return "images/systems/1.jpg";
	return "images/systems/".$row->Orbits.".jpg";
}
function GetSystemNameFromID($SystemID){
	$query = "SELECT Name, DefaultName FROM Systems WHERE(SystemID='$SystemID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	if(!$row) return "Unknown";
	return $row->Name ?? $row->DefaultName ?? "Unknown";
}
function GetKnownSystems($username){
	$Systems = array();
	$pid = GetPlayerIDFromName($username);
	$sql = "SELECT SystemID FROM known_systems WHERE PlayerID = '" . mysqli_real_escape_string($GLOBALS["conn"], $pid) . "'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		while($row = mysqli_fetch_object($res)){
			$System = GetSystem($row->SystemID);
			if($System) $Systems[$System->SystemID] = $System;
		}
	}
	return $Systems;
}
function AddKnownSystem($PlayerID, $SystemID){
	$pid = mysqli_real_escape_string($GLOBALS["conn"], $PlayerID);
	$sid = mysqli_real_escape_string($GLOBALS["conn"], $SystemID);
	$sql = "INSERT IGNORE INTO known_systems(PlayerID, SystemID) VALUES('$pid', '$sid')";
	mysqli_query($GLOBALS["conn"], $sql);
}
function GetSectorPictureFromID($SectorID){
	return "sectorimage.img.php?id=".$SectorID;
}

function GetSectorName($SectorID){
	$sql = "SELECT Name FROM sectors WHERE SectorID='" . (int)$SectorID . "'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if($row && $row->Name) return $row->Name;
	return "Sector " . (int)$SectorID;
}

// --- System Renaming ---

function CanRenameSystem($SystemID, $PlayerID){
	if($PlayerID <= 0) return false;
	$sid = (int)$SystemID;
	$pid = (int)$PlayerID;
	// All owned planets in the system must belong to this player, and there must be at least one
	$sql = "SELECT PlayerID FROM planets WHERE `System` = '$sid' AND PlayerID > 0";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if(!$res || mysqli_num_rows($res) == 0) return false;
	while($row = mysqli_fetch_object($res)){
		if((int)$row->PlayerID != $pid) return false;
	}
	return true;
}

function IsSystemNameUnique($Name, $ExcludeSystemID = 0){
	$name = mysqli_real_escape_string($GLOBALS["conn"], $Name);
	$eid = (int)$ExcludeSystemID;
	// Check against both user-assigned names and default names
	$sql = "SELECT SystemID FROM Systems WHERE (Name = '$name' OR (Name IS NULL AND DefaultName = '$name'))";
	if($eid > 0) $sql .= " AND SystemID != $eid";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return $res && mysqli_num_rows($res) == 0;
}

function LoadForbiddenWords(){
	$file = __DIR__ . '/data/forbidden_words.txt';
	if(!file_exists($file)) return [];
	$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$words = [];
	foreach($lines as $line){
		$w = trim(strtolower($line));
		if($w !== '' && $w[0] !== '#') $words[] = $w;
	}
	return $words;
}

function ContainsForbiddenWord($Name){
	$words = LoadForbiddenWords();
	$lower = strtolower($Name);
	foreach($words as $word){
		if(strpos($lower, $word) !== false) return true;
	}
	return false;
}

function RenameSystem($SystemID, $NewName, $PlayerID){
	$sid = (int)$SystemID;
	$pid = (int)$PlayerID;
	if(!CanRenameSystem($sid, $pid)) return 'You must be the sole owner of all planets in this system.';
	$name = trim($NewName);
	if(strlen($name) < 2 || strlen($name) > 50) return 'Name must be between 2 and 50 characters.';
	if(!preg_match('/^[a-zA-Z0-9 \-\']+$/', $name)) return 'Name may only contain letters, numbers, spaces, hyphens and apostrophes.';
	if(ContainsForbiddenWord($name)) return 'That name contains a forbidden word.';
	if(!IsSystemNameUnique($name, $sid)) return 'A system with that name already exists.';
	$eName = mysqli_real_escape_string($GLOBALS["conn"], $name);
	mysqli_query($GLOBALS["conn"], "UPDATE Systems SET Name = '$eName' WHERE SystemID = '$sid'");
	// Auto-update planet default names to match new system name (does not touch custom names)
	mysqli_query($GLOBALS["conn"], "UPDATE planets SET DefaultName = CONCAT('$eName', ' ', Orbit) WHERE `System` = '$sid'");
	return true;
}

function RevertSystemName($SystemID){
	$sid = (int)$SystemID;
	// Revert planet default names to original system default name
	$_sysRes = mysqli_query($GLOBALS["conn"], "SELECT DefaultName FROM Systems WHERE SystemID = '$sid'");
	$_sysRow = mysqli_fetch_object($_sysRes);
	if($_sysRow && $_sysRow->DefaultName){
		$eDefault = mysqli_real_escape_string($GLOBALS["conn"], $_sysRow->DefaultName);
		mysqli_query($GLOBALS["conn"], "UPDATE planets SET DefaultName = CONCAT('$eDefault', ' ', Orbit) WHERE `System` = '$sid'");
	}
	mysqli_query($GLOBALS["conn"], "UPDATE Systems SET Name = NULL WHERE SystemID = '$sid'");
}

// --- Planet Renaming ---

function IsPlanetNameUnique($Name, $ExcludePlanetID = 0){
	$name = mysqli_real_escape_string($GLOBALS["conn"], $Name);
	$eid = (int)$ExcludePlanetID;
	$sql = "SELECT PlanetID FROM planets WHERE (Name = '$name' OR (Name IS NULL AND DefaultName = '$name'))";
	if($eid > 0) $sql .= " AND PlanetID != $eid";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return $res && mysqli_num_rows($res) == 0;
}

function RenamePlanet($PlanetID, $NewName, $PlayerID){
	$pid = (int)$PlanetID;
	$plid = (int)$PlayerID;
	// Must own the planet
	$sql = "SELECT PlayerID FROM planets WHERE PlanetID = '$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row || (int)$row->PlayerID != $plid || $plid == 0) return 'You do not own this planet.';
	$name = trim($NewName);
	if(strlen($name) < 2 || strlen($name) > 50) return 'Name must be between 2 and 50 characters.';
	if(!preg_match('/^[a-zA-Z0-9 \-\']+$/', $name)) return 'Name may only contain letters, numbers, spaces, hyphens and apostrophes.';
	if(ContainsForbiddenWord($name)) return 'That name contains a forbidden word.';
	if(!IsPlanetNameUnique($name, $pid)) return 'A planet with that name already exists.';
	$eName = mysqli_real_escape_string($GLOBALS["conn"], $name);
	mysqli_query($GLOBALS["conn"], "UPDATE planets SET Name = '$eName' WHERE PlanetID = '$pid'");
	return true;
}

function RevertPlanetName($PlanetID){
	$pid = (int)$PlanetID;
	mysqli_query($GLOBALS["conn"], "UPDATE planets SET Name = NULL WHERE PlanetID = '$pid'");
}

function RenameSector($SectorID, $NewName, $PlayerID){
	$sid = (int)$SectorID;
	$pid = (int)$PlayerID;
	// Only the majority controller (by system count) can rename
	$sql = "SELECT MajOwner FROM sectors WHERE SectorID='$sid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row || (int)$row->MajOwner != $pid || $pid == 0) return false;
	$name = mysqli_real_escape_string($GLOBALS["conn"], trim($NewName));
	if(strlen($name) < 1 || strlen($name) > 100) return false;
	$sql = "UPDATE sectors SET Name='$name' WHERE SectorID='$sid'";
	mysqli_query($GLOBALS["conn"], $sql);
	return true;
}

function GetSystemsInSector($SectorID){
	$Planets = array();
	
	$query = "SELECT * FROM Systems WHERE(SectorID='$SectorID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$Planets[$row->SystemID] = GetSystem($row->SystemID);
	}
	return $Planets;
}
function SystemsInSector($SectorID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM Systems WHERE(SectorID = '$SectorID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}
function PlanetsInSector($SectorID){
	$total_count = 0;

	$Systems = GetSystemsInSector($SectorID);
	foreach($Systems as $k=>$System){
		$total_count += PlanetsInSystem($System->SystemID);
	}

	return $total_count;
}
function IsSector($SectorID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM sectors WHERE(SectorID = '$SectorID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	
	if($total_count>0)
		return true;
	else
		return false;
}

function PlayersInTeam($TeamID){
$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM players WHERE(TeamID = '$TeamID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}
function ListPlayersInTeam($TeamID){
	$Players = array();
	$query = "SELECT * FROM players WHERE(TeamID='$TeamID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->TeamID!=0){
			$Players[$row->PlayerID] = $row->PlayerID;
		}
	}
	return $Players;
}
function GetNumberOfPlanetsInTeam($TeamID){
	$total = 0;
	$Players = ListPlayersInTeam($TeamID);
	foreach($Players as $k=>$Player){
		$total += GetNumberOfPlanets($Player);
	}
	return $total;
}

function PlayerTeam($PlayerID){
	if($PlayerID==0) return 0;
	$query = "SELECT TeamID FROM players WHERE(PlayerID='$PlayerID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->TeamID : 0;
}

function TeamNameFromID($TeamID){
	if(!$TeamID) return "No Team";
	$query = "SELECT Name FROM teams WHERE(TeamID='$TeamID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->Name : "No Team";
}

function CalcMajOwner($SectorID){
	$Players = array();
	$Teams = array();
	$query = "SELECT * FROM Systems WHERE(SectorID='$SectorID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->PlayerID!=0){
			$Players[$row->PlayerID]["Player"] = $row->PlayerID;
			$Players[$row->PlayerID]["Count"] = ($Players[$row->PlayerID]["Count"] ?? 0) + 1;
		}
		// Track team ownership from stored TeamID
		$tid = (int)($row->TeamID ?? 0);
		if($tid > 0){
			$Teams[$tid] = ($Teams[$tid] ?? 0) + 1;
		}
	}
	// Player majority
	$largestcount = 0;
	$player = 0;
	foreach($Players as $k=>$Player){
		if($Player["Count"]>$largestcount){
			$largestcount = $Player["Count"];
			$player = $Player["Player"];
		}
	}
	foreach($Players as $k=>$Player){
		if($Player["Player"]!=$player){
			if($Player["Count"]==$largestcount){
				$player=0;
			}
		}
	}
	// Team majority
	$teamLargest = 0;
	$majTeam = 0;
	foreach($Teams as $tid=>$cnt){
		if($cnt > $teamLargest){
			$teamLargest = $cnt;
			$majTeam = $tid;
		}
	}
	foreach($Teams as $tid=>$cnt){
		if($tid != $majTeam && $cnt == $teamLargest){
			$majTeam = 0;
			break;
		}
	}
	$query = "UPDATE sectors SET MajOwner = $player, MajTeamID = $majTeam WHERE(SectorID='$SectorID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	
	return $player;
}
function GetTeamColour($TeamID){
	if(!$TeamID) return "128,128,128";
	$query = "SELECT Colour FROM teams WHERE(TeamID='$TeamID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row ? $row->Colour : "128,128,128";
}
function GetSectorMajOwnerTeam($SectorID){
	$teams = array();
	$sql= "SELECT * FROM Systems WHERE(SectorID = '$SectorID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		if($row->PlayerID!=0){
				$Team = PlayerTeam($row->PlayerID);
				if(!isset($teams[$Team])){
					//echo "[1] Team: ".$Team."<br/>";
					$teams[$Team]["Team"] = $Team;
					$teams[$Team]["Count"] = 1;
				}else{
					//echo "[2] Team Add: ".$Team."<br/>";
					$teams[$Team]["Count"] +=1;
				}
		}
	}
	$largestcount = 0;
	$theteam = "";
	foreach($teams as $team){
		if($team["Count"]>$largestcount){
			//echo "[3] Larger: ".$team["Team"]."<br/>";
			$largestcount = $team["Count"];
			$theteam = $team["Team"];
		}
	}
	//echo "[3.1] Largest: ".$theteam."<br/>";
	foreach($teams as $team){
		if($team["Team"]!=$theteam){
			if($team["Count"]==$largestcount){
				//echo "[4] Dup: ".$team["Team"]."<br/>";
				$theteam = "";
				break;
			}
		}
	}
	//echo "[5] Returning: ".$team["Team"]."<br/>";
	return $theteam;
}

function GetSectorStakeHolders($SectorID){
	$players = array();
	$sql= "SELECT * FROM Systems WHERE(SectorID = '$SectorID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		//echo "s,";
		$sql= "SELECT * FROM planets WHERE(`System` = '".$row->SystemID."')";
		$res=mysqli_query($GLOBALS["conn"], $sql);
		while($row2 = mysqli_fetch_object($res)){
			if($row2->PlayerID!=0){
				$Player = GetPlayerNameFromID($row2->PlayerID);
				if(!isset($players[$Player])){
					$players[$Player] = $Player;
				}
			}
		}
	}
	return sizeof($players);
}

function ListSectorStakeHolders($SectorID){
	$players = array();
	$sql= "SELECT * FROM Systems WHERE(SectorID = '$SectorID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		$sql= "SELECT * FROM planets WHERE(`System` = '".$row->SystemID."')";
		$res=mysqli_query($GLOBALS["conn"], $sql);
		while($row2 = mysqli_fetch_object($res)){
			if($row2->PlayerID!=0){
				$Player = GetPlayerNameFromID($row2->PlayerID);
				if(!isset($players[$Player])){
					$players[$Player]["Name"] = $Player;
					$players[$Player]["ID"] = $row2->PlayerID;
					$players[$Player]["Count"] = 1;
					$players[$Player]["Planets"] = array();
				}else{
					$players[$Player]["Count"] +=1;
				}
				$players[$Player]["Planets"][] = array(
					"PlanetID" => $row2->PlanetID,
					"Name" => GetPlanetNameFromID($row2->PlanetID)
				);
			}
		}
	}
	return $players;
}

function ClearHomePage(){
	global $username;
}

function PlayerProfileLink($PlayerID){
	if($PlayerID>0){
		return "<a href=\"player.php?id=".(int)$PlayerID."\">".h(GetPlayerNameFromID($PlayerID))."</a>";
	}
	else{
		return h(GetPlayerNameFromID($PlayerID));
	}
}

function PrintMessage($msg){
	if(!empty($msg)){
		echo "<div class=\"message\">";
		echo h($msg);
		echo "</div>";
	}
}

// ============================================
// Team Management Functions
// ============================================

function GetTeamLeader($TeamID){
	if(!$TeamID) return 0;
	$sql = "SELECT LeaderID FROM teams WHERE TeamID='" . mysqli_real_escape_string($GLOBALS["conn"], $TeamID) . "'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	return $row ? (int)$row->LeaderID : 0;
}

function IsTeamLeader($PlayerID){
	$TeamID = PlayerTeam($PlayerID);
	if(!$TeamID) return false;
	return GetTeamLeader($TeamID) == $PlayerID;
}

function GetTeamInfo($TeamID){
	if(!$TeamID) return null;
	$sql = "SELECT * FROM teams WHERE TeamID='" . mysqli_real_escape_string($GLOBALS["conn"], $TeamID) . "'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return mysqli_fetch_object($res);
}

function ListAllTeams(){
	$teams = array();
	$sql = "SELECT * FROM teams ORDER BY Name ASC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$teams[] = $row;
	}
	return $teams;
}

function GetTeamColourPresets(){
	return [
		'255,50,50',    // Red
		'50,100,255',   // Blue
		'50,200,50',    // Green
		'255,255,50',   // Yellow
		'255,150,0',    // Orange
		'180,50,255',   // Purple
		'0,220,220',    // Cyan
		'255,100,200',  // Pink
		'255,255,255',  // White
		'160,160,160',  // Silver
		'100,60,30',    // Brown
		'0,150,0',      // Dark Green
		'0,50,150',     // Navy
		'150,0,0',      // Maroon
		'200,200,50',   // Gold
		'100,200,150',  // Teal
	];
}

function IsTeamColourTaken($Colour, $ExcludeTeamID = 0){
	$Colour = mysqli_real_escape_string($GLOBALS["conn"], $Colour);
	$sql = "SELECT TeamID FROM teams WHERE Colour='$Colour'";
	if($ExcludeTeamID > 0){
		$sql .= " AND TeamID != '" . (int)$ExcludeTeamID . "'";
	}
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return mysqli_num_rows($res) > 0;
}

function CreateTeam($PlayerID, $Name, $Colour){
	$Name = mysqli_real_escape_string($GLOBALS["conn"], $Name);
	$Colour = mysqli_real_escape_string($GLOBALS["conn"], $Colour);
	$pid = (int)$PlayerID;
	$sql = "INSERT INTO teams(Name, Colour, LeaderID) VALUES('$Name', '$Colour', '$pid')";
	mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	$TeamID = mysqli_insert_id($GLOBALS["conn"]);
	$sql = "UPDATE players SET TeamID='$TeamID' WHERE PlayerID='$pid'";
	mysqli_query($GLOBALS["conn"], $sql);
	return $TeamID;
}

function LeaveTeam($PlayerID){
	$pid = (int)$PlayerID;
	$TeamID = PlayerTeam($pid);
	if(!$TeamID) return;
	// Remove player from team
	$sql = "UPDATE players SET TeamID=0 WHERE PlayerID='$pid'";
	mysqli_query($GLOBALS["conn"], $sql);
	// Remove any active votes by this player
	$sql = "DELETE FROM team_votes WHERE TeamID='$TeamID' AND VoterID='$pid'";
	mysqli_query($GLOBALS["conn"], $sql);
	// Check remaining members
	$remaining = PlayersInTeam($TeamID);
	if($remaining == 0){
		// Last member — delete team and related data
		mysqli_query($GLOBALS["conn"], "DELETE FROM teams WHERE TeamID='$TeamID'");
		mysqli_query($GLOBALS["conn"], "DELETE FROM team_votes WHERE TeamID='$TeamID'");
		mysqli_query($GLOBALS["conn"], "DELETE FROM team_join_requests WHERE TeamID='$TeamID'");
		return;
	}
	// If leader left, reassign to oldest remaining member
	if(GetTeamLeader($TeamID) == $pid){
		$sql = "SELECT PlayerID FROM players WHERE TeamID='$TeamID' ORDER BY DateJoined ASC LIMIT 1";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		$newLeader = $row ? (int)$row->PlayerID : 0;
		$sql = "UPDATE teams SET LeaderID='$newLeader' WHERE TeamID='$TeamID'";
		mysqli_query($GLOBALS["conn"], $sql);
	}
}

function RequestJoinTeam($PlayerID, $TeamID){
	$pid = (int)$PlayerID;
	$tid = (int)$TeamID;
	// Check player not already in a team
	if(PlayerTeam($pid) > 0) return false;
	// Check no existing pending request
	if(GetPlayerJoinRequest($pid)) return false;
	$sql = "INSERT INTO team_join_requests(PlayerID, TeamID, RequestedAt) VALUES('$pid', '$tid', NOW())";
	mysqli_query($GLOBALS["conn"], $sql);
	return true;
}

function GetPlayerJoinRequest($PlayerID){
	$pid = (int)$PlayerID;
	$sql = "SELECT * FROM team_join_requests WHERE PlayerID='$pid' LIMIT 1";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return mysqli_fetch_object($res);
}

function CancelJoinRequest($PlayerID){
	$pid = (int)$PlayerID;
	$sql = "DELETE FROM team_join_requests WHERE PlayerID='$pid'";
	mysqli_query($GLOBALS["conn"], $sql);
}

function GetPendingJoinRequests($TeamID){
	$requests = array();
	$tid = (int)$TeamID;
	$sql = "SELECT * FROM team_join_requests WHERE TeamID='$tid' ORDER BY RequestedAt ASC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$requests[] = $row;
	}
	return $requests;
}

function ApproveJoinRequest($RequestID){
	$rid = (int)$RequestID;
	$sql = "SELECT * FROM team_join_requests WHERE RequestID='$rid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$req = mysqli_fetch_object($res);
	if(!$req) return false;
	// Set player's team
	$sql = "UPDATE players SET TeamID='" . (int)$req->TeamID . "' WHERE PlayerID='" . (int)$req->PlayerID . "'";
	mysqli_query($GLOBALS["conn"], $sql);
	// Delete the request
	$sql = "DELETE FROM team_join_requests WHERE RequestID='$rid'";
	mysqli_query($GLOBALS["conn"], $sql);
	return true;
}

function DenyJoinRequest($RequestID){
	$rid = (int)$RequestID;
	$sql = "DELETE FROM team_join_requests WHERE RequestID='$rid'";
	mysqli_query($GLOBALS["conn"], $sql);
}

function StartLeaderVote($TeamID){
	$tid = (int)$TeamID;
	// Clear any active motion
	ClearMotion($tid);
	// Clear any existing votes
	$sql = "DELETE FROM team_votes WHERE TeamID='$tid'";
	mysqli_query($GLOBALS["conn"], $sql);
	// Set vote active with configurable turn countdown
	$duration = (int)GetGameSetting('election_duration', 5);
	$turnCounter = (int)GetGameSetting('turn_counter', 0);
	$sql = "UPDATE teams SET VoteActive=1, VoteTurnsLeft='$duration', LastElectionTurn='$turnCounter' WHERE TeamID='$tid'";
	mysqli_query($GLOBALS["conn"], $sql);
}

function CastLeaderVote($TeamID, $VoterID, $CandidateID){
	$tid = (int)$TeamID;
	$vid = (int)$VoterID;
	$cid = (int)$CandidateID;
	$sql = "INSERT INTO team_votes(TeamID, VoterID, CandidateID) VALUES('$tid','$vid','$cid')
		ON DUPLICATE KEY UPDATE CandidateID='$cid'";
	mysqli_query($GLOBALS["conn"], $sql);
}

function ResolveLeaderVote($TeamID){
	$tid = (int)$TeamID;
	// Tally votes
	$sql = "SELECT CandidateID, COUNT(*) AS cnt FROM team_votes WHERE TeamID='$tid' GROUP BY CandidateID ORDER BY cnt DESC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$results = array();
	while($row = mysqli_fetch_object($res)){
		$results[] = $row;
	}
	$totalVoters = 0;
	foreach($results as $r) $totalVoters += (int)$r->cnt;

	if(count($results) == 0){
		// No votes cast — keep current leader
		$sql = "UPDATE teams SET VoteActive=0, VoteTurnsLeft=0 WHERE TeamID='$tid'";
		mysqli_query($GLOBALS["conn"], $sql);
		$sql = "DELETE FROM team_votes WHERE TeamID='$tid'";
		mysqli_query($GLOBALS["conn"], $sql);
		return;
	}

	// Find winner; break ties by oldest DateJoined
	$topCount = (int)$results[0]->cnt;
	$tied = array();
	foreach($results as $r){
		if((int)$r->cnt == $topCount) $tied[] = (int)$r->CandidateID;
	}
	if(count($tied) == 1){
		$winnerID = $tied[0];
	} else {
		// Tie-break: oldest DateJoined
		$ids = implode(',', $tied);
		$sql = "SELECT PlayerID FROM players WHERE PlayerID IN ($ids) ORDER BY DateJoined ASC LIMIT 1";
		$res2 = mysqli_query($GLOBALS["conn"], $sql);
		$row2 = mysqli_fetch_object($res2);
		$winnerID = $row2 ? (int)$row2->PlayerID : $tied[0];
	}

	// Runner-up
	$runnerUpID = 0;
	$runnerUpVotes = 0;
	foreach($results as $r){
		if((int)$r->CandidateID != $winnerID){
			$runnerUpID = (int)$r->CandidateID;
			$runnerUpVotes = (int)$r->cnt;
			break;
		}
	}

	// Update leader
	$sql = "UPDATE teams SET LeaderID='$winnerID', VoteActive=0, VoteTurnsLeft=0 WHERE TeamID='$tid'";
	mysqli_query($GLOBALS["conn"], $sql);

	// Notify team members
	$teamName = TeamNameFromID($tid);
	$winnerName = GetPlayerNameFromID($winnerID);
	$memberSql = "SELECT PlayerID FROM players WHERE TeamID='$tid'";
	$memberRes = mysqli_query($GLOBALS["conn"], $memberSql);
	while($mrow = mysqli_fetch_object($memberRes)){
		AddAlert((int)$mrow->PlayerID, 'team', $winnerName.' elected as leader of '.$teamName, 'teams.php');
	}

	// Record in history
	$winnerVotes = $topCount;
	$sql = "INSERT INTO team_election_history(TeamID, WinnerID, Votes, RunnerUpID, RunnerUpVotes, TotalVoters, ResolvedAt)
		VALUES('$tid','$winnerID','$winnerVotes','$runnerUpID','$runnerUpVotes','$totalVoters',NOW())";
	mysqli_query($GLOBALS["conn"], $sql);

	// Clear votes
	$sql = "DELETE FROM team_votes WHERE TeamID='$tid'";
	mysqli_query($GLOBALS["conn"], $sql);
}

function GetVoteStatus($TeamID){
	$tid = (int)$TeamID;
	$status = array("votes" => array(), "tally" => array());
	// Who voted for whom
	$sql = "SELECT VoterID, CandidateID FROM team_votes WHERE TeamID='$tid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$status["votes"][(int)$row->VoterID] = (int)$row->CandidateID;
	}
	// Tally
	$sql = "SELECT CandidateID, COUNT(*) AS cnt FROM team_votes WHERE TeamID='$tid' GROUP BY CandidateID ORDER BY cnt DESC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$status["tally"][(int)$row->CandidateID] = (int)$row->cnt;
	}
	return $status;
}

function GetElectionHistory($TeamID){
	$history = array();
	$tid = (int)$TeamID;
	$sql = "SELECT * FROM team_election_history WHERE TeamID='$tid' ORDER BY ResolvedAt DESC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$history[] = $row;
	}
	return $history;
}

// ============================================
// Home World Functions
// ============================================

function GetHomePlanet($PlayerID){
	$pid = (int)$PlayerID;
	$sql = "SELECT HomePlanetID FROM players WHERE PlayerID='$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	return $row ? (int)$row->HomePlanetID : 0;
}

function SetHomePlanet($PlayerID, $PlanetID){
	$pid = (int)$PlayerID;
	$plid = (int)$PlanetID;
	// Verify ownership
	if(!OwnsPlanet(GetPlayerNameFromID($pid), $plid)) return false;
	$sql = "UPDATE players SET HomePlanetID='$plid' WHERE PlayerID='$pid'";
	mysqli_query($GLOBALS["conn"], $sql);
	return true;
}

function IsHomePlanet($PlayerID, $PlanetID){
	return GetHomePlanet($PlayerID) == (int)$PlanetID;
}

function NeedsHomePlanet($PlayerID){
	$pid = (int)$PlayerID;
	$numPlanets = GetNumberOfPlanets($pid);
	if($numPlanets == 0) return false; // No planets = game over, not needs-home
	$home = GetHomePlanet($pid);
	if($home == 0) return true;
	// Check if home planet is still owned
	if(!OwnsPlanet(GetPlayerNameFromID($pid), $home)) return true;
	return false;
}

function BuyPlanet($PlayerID){
	$pid = (int)$PlayerID;
	$costMetal = (int)GetGameSetting('buy_planet_metal', 2000);
	$costMineral = (int)GetGameSetting('buy_planet_mineral', 1000);
	$costAstrium = (int)GetGameSetting('buy_planet_astrium', 200);
	if(!HasSufficientResources($pid, 1, $costMetal)) return 0;
	if(!HasSufficientResources($pid, 2, $costMineral)) return 0;
	if(!HasSufficientResources($pid, 3, $costAstrium)) return 0;
	// Find unclaimed planet (same logic as AssignStartingPlanet)
	$sql = "SELECT p.PlanetID FROM planets p
	        INNER JOIN Systems s ON p.`System` = s.SystemID
	        WHERE p.PlayerID = 0
	        AND s.SystemID NOT IN (SELECT DISTINCT `System` FROM planets WHERE PlayerID > 0)
	        ORDER BY RAND() LIMIT 1";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row){
		$sql = "SELECT PlanetID FROM planets WHERE PlayerID = 0 ORDER BY RAND() LIMIT 1";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
	}
	if(!$row) return 0; // No unclaimed planets
	$planetID = (int)$row->PlanetID;
	DeductResources($pid, 1, $costMetal);
	DeductResources($pid, 2, $costMineral);
	DeductResources($pid, 3, $costAstrium);
	$sql = "UPDATE planets SET PlayerID='$pid' WHERE PlanetID='$planetID'";
	mysqli_query($GLOBALS["conn"], $sql);
	// Set as home planet
	$sql = "UPDATE players SET HomePlanetID='$planetID' WHERE PlayerID='$pid'";
	mysqli_query($GLOBALS["conn"], $sql);
	// Recalculate ownership
	$planet = GetPlanet($planetID);
	if($planet){
		CheckSystemMajOwner($planet->System);
		$sys = GetSystem($planet->System);
		if($sys) CalcMajOwner($sys->SectorID);
	}
	return $planetID;
}

function ProcessElectionCountdowns(){
	// Increment global turn counter
	$turnCounter = (int)GetGameSetting('turn_counter', 0) + 1;
	SetGameSetting('turn_counter', $turnCounter);

	// Decrement VoteTurnsLeft for active elections, resolve if 0
	$sql = "SELECT TeamID FROM teams WHERE VoteActive=1 AND VoteTurnsLeft > 0";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$tid = (int)$row->TeamID;
		$sql2 = "UPDATE teams SET VoteTurnsLeft = VoteTurnsLeft - 1 WHERE TeamID='$tid'";
		mysqli_query($GLOBALS["conn"], $sql2);
	}
	$sql = "SELECT TeamID FROM teams WHERE VoteActive=1 AND VoteTurnsLeft <= 0";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		ResolveLeaderVote((int)$row->TeamID);
	}

	// Auto-elections: trigger for teams that haven't had one in N turns
	$interval = (int)GetGameSetting('election_auto_interval', 100);
	if($interval > 0){
		$sql = "SELECT TeamID FROM teams WHERE VoteActive=0";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		while($row = mysqli_fetch_object($res)){
			$tid = (int)$row->TeamID;
			if(PlayersInTeam($tid) < 2) continue;
			$team = GetTeamInfo($tid);
			$lastElection = (int)$team->LastElectionTurn;
			if(($turnCounter - $lastElection) >= $interval){
				StartLeaderVote($tid);
			}
		}
	}
}

// ============================================
// Election Motion Functions
// ============================================

function RaiseElectionMotion($TeamID, $PlayerID){
	$tid = (int)$TeamID;
	$pid = (int)$PlayerID;
	// Can't raise motion if election already active or motion already exists
	$team = GetTeamInfo($tid);
	if(!$team || $team->VoteActive) return false;
	if(GetActiveMotion($tid)) return false;
	// Must be a member of the team
	if(PlayerTeam($pid) != $tid) return false;
	$sql = "INSERT INTO election_motions(TeamID, ProposerID, CreatedAt) VALUES('$tid', '$pid', NOW())";
	mysqli_query($GLOBALS["conn"], $sql);
	// Proposer automatically seconds
	$sql = "INSERT INTO election_motion_seconds(TeamID, PlayerID) VALUES('$tid', '$pid')";
	mysqli_query($GLOBALS["conn"], $sql);
	// Check if threshold already met (small teams)
	CheckMotionThreshold($tid);
	return true;
}

function SecondElectionMotion($TeamID, $PlayerID){
	$tid = (int)$TeamID;
	$pid = (int)$PlayerID;
	if(!GetActiveMotion($tid)) return false;
	if(PlayerTeam($pid) != $tid) return false;
	$sql = "INSERT IGNORE INTO election_motion_seconds(TeamID, PlayerID) VALUES('$tid', '$pid')";
	mysqli_query($GLOBALS["conn"], $sql);
	CheckMotionThreshold($tid);
	return true;
}

function CheckMotionThreshold($TeamID){
	$tid = (int)$TeamID;
	$totalMembers = PlayersInTeam($tid);
	if($totalMembers < 2) return;
	$threshold = (float)GetGameSetting('election_motion_threshold', 25) / 100;
	$needed = max(1, ceil($totalMembers * $threshold));
	$seconds = CountMotionSeconds($tid);
	if($seconds >= $needed){
		StartLeaderVote($tid); // This also clears the motion
	}
}

function GetActiveMotion($TeamID){
	$tid = (int)$TeamID;
	$sql = "SELECT * FROM election_motions WHERE TeamID='$tid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return mysqli_fetch_object($res);
}

function GetMotionSeconds($TeamID){
	$seconds = array();
	$tid = (int)$TeamID;
	$sql = "SELECT PlayerID FROM election_motion_seconds WHERE TeamID='$tid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$seconds[] = (int)$row->PlayerID;
	}
	return $seconds;
}

function CountMotionSeconds($TeamID){
	$tid = (int)$TeamID;
	$sql = "SELECT COUNT(*) AS cnt FROM election_motion_seconds WHERE TeamID='$tid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	return $row ? (int)$row->cnt : 0;
}

function ClearMotion($TeamID){
	$tid = (int)$TeamID;
	mysqli_query($GLOBALS["conn"], "DELETE FROM election_motions WHERE TeamID='$tid'");
	mysqli_query($GLOBALS["conn"], "DELETE FROM election_motion_seconds WHERE TeamID='$tid'");
}

function HasSecondedMotion($TeamID, $PlayerID){
	$tid = (int)$TeamID;
	$pid = (int)$PlayerID;
	$sql = "SELECT 1 FROM election_motion_seconds WHERE TeamID='$tid' AND PlayerID='$pid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return mysqli_num_rows($res) > 0;
}

function ResignAsLeader($PlayerID){
	$pid = (int)$PlayerID;
	$tid = PlayerTeam($pid);
	if(!$tid) return false;
	if(GetTeamLeader($tid) != $pid) return false;
	// Start election immediately
	StartLeaderVote($tid);
	return true;
}
