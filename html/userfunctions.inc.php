<?php

include("fleetfunctions.inc.php");
include("planetfunctions.inc.php");
include("buildingfunctions.inc.php");
include_once("turnfunctions.inc.php");
include("logfunctions.inc.php");
include("resourcefunctions.inc.php");
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
		$Planet->Name = $row->Name;
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
	if($row->PlayerID==GetPlayerIDFromName($username)){
		return true;
	}else{
		return false;
	}
}

function GetPlanetNameFromID($PlanetID){
	$query = "SELECT Name FROM planets WHERE(PlanetID='$PlanetID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	
	return $row->Name;
}
function GetPlanetPictureFromID($PlanetID){
	global $username;
	if(OwnsPlanet($username,$PlanetID)){
		return "planetimage.img.php?id=".$PlanetID;
	}else{
		$query = "SELECT Size FROM planets WHERE(PlanetID='$PlanetID')";
	
		$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
		$row = mysqli_fetch_object($notresult);
		return "images/planets/".$row->Size.".jpg";
	}
}

function GetPlanet($PlanetID){
	$query = "SELECT * FROM planets WHERE(PlanetID='$PlanetID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	$Planet = new Planet();
	$Planet->PlanetID = $row->PlanetID;
	$Planet->Name = $row->Name;
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
	var $Orbits;
	var $PlayerID;
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
	$Planet = new System();
	$Planet->SystemID = $row->SystemID;
	$Planet->Name = $row->Name;
	$Planet->Orbits = $row->Orbits;
	$Planet->PlayerID = $row->PlayerID;
	$Planet->SectorID = $row->SectorID;
	$Planet->Coords = $row->Coords;
	
	return $Planet;	
}

function CheckSystemMajOwner($SystemID){
	$Players = array();
	$query = "SELECT * FROM planets WHERE(System='$SystemID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->PlayerID!=0){
		$Players[$row->PlayerID]["Player"] = $row->PlayerID;
		$Players[$row->PlayerID]["Count"] += 1;
		}
	}
	$largestcount = 0;
	$player = 0;
	foreach($Players as $k=>$Player){
		if($Player["Count"]>$largestcount){
			$largestcount = $Player["Count"];
			$player = $Player["Player"];
		}
	}
	//Check for joint majority
	$dup = false;
	foreach($Players as $k=>$Player){
		if($Player["Player"]!=$player){
			if($Player["Count"]==$largestcount){
				$dup = true;
				$player=0;	
			}
		}
	}
	//echo "Setting System Player: ".$player;
	$query = "UPDATE Systems SET PlayerID = $player WHERE(SystemID='$SystemID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	
	return $player;
}

function PlanetsInSystem($SystemID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM planets WHERE(System = '$SystemID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	return $total_count;
}

function ListPlanetsInSystem($SystemID){
$Planets = array();
	
	$query = "SELECT * FROM planets WHERE(System='$SystemID') ORDER BY Orbit ASC";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		$Planet = new Planet();
		$Planet->PlanetID = $row->PlanetID;
		$Planet->Name = $row->Name;
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
	return "images/systems/".$row->Orbits.".jpg";
}
function GetSystemNameFromID($SystemID){
	$query = "SELECT Name FROM Systems WHERE(SystemID='$SystemID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	
	return $row->Name;
}
function GetKnownSystems($username){
	$Systems = array();
	$sys = @file(__DIR__ . "/userdata/knownsystems/".GetPlayerIDFromName($username).".txt");
	foreach($sys as $key=>$syst){
		$System = GetSystem($syst);
		$Systems[$System->SystemID] = $System;
	}
	return $Systems;
}
function GetSectorPictureFromID($SectorID){
	return "sectorimage.img.php?id=".$SectorID;
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
	$query = "SELECT TeamID FROM players WHERE(PlayerID='$PlayerID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row->TeamID;
}

function TeamNameFromID($TeamID){
	$query = "SELECT Name FROM teams WHERE(TeamID='$TeamID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row->Name;
}

function CalcMajOwner($SectorID){
	$Players = array();
	$query = "SELECT * FROM Systems WHERE(SectorID='$SectorID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		if($row->PlayerID!=0){
			$Players[$row->PlayerID]["Player"] = $row->PlayerID;
			$Players[$row->PlayerID]["Count"] += 1;
		}
	}
	$largestcount = 0;
	$player = 0;
	foreach($Players as $k=>$Player){
		if($Player["Count"]>$largestcount){
			$largestcount = $Player["Count"];
			$player = $Player["Player"];
		}
	}
	//Check for joint majority
	$dup = false;
	foreach($Players as $k=>$Player){
		if($Player["Player"]!=$player){
			if($Player["Count"]==$largestcount){
				$dup = true;
				$player=0;	
			}
		}
	}
	//echo "Setting Player: ".$player;
	$query = "UPDATE sectors SET MajOwner = $player WHERE(SectorID='$SectorID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	
	return $player;
}
function GetTeamColour($TeamID){
	$query = "SELECT Colour FROM teams WHERE(TeamID='$TeamID')";

	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($notresult);
	return $row->Colour;
}
function GetSectorMajOwnerTeam($SectorID){
	$teams = array();
	$sql= "SELECT * FROM Systems WHERE(SectorID = '$SectorID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($rescount)){
		if($row->PlayerID!=0){
				$Team = PlayerTeam($row->PlayerID);
				if($teams[$Team]==""){
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
		$sql= "SELECT * FROM planets WHERE(System = '".$row->SystemID."')";
		$res=mysqli_query($GLOBALS["conn"], $sql);
		while($row2 = mysqli_fetch_object($res)){
			if($row2->PlayerID!=0){
				$Player = GetPlayerNameFromID($row2->PlayerID);
				if($players[$Player]==""){
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
		//echo "s,";
		$sql= "SELECT * FROM planets WHERE(System = '".$row->SystemID."')";
		$res=mysqli_query($GLOBALS["conn"], $sql);
		while($row2 = mysqli_fetch_object($res)){
			if($row2->PlayerID!=0){
				$Player = GetPlayerNameFromID($row2->PlayerID);
				if($players[$Player]["Name"]==""){
					$players[$Player]["Name"] = $Player;
					$players[$Player]["ID"] = $row2->PlayerID;
					$players[$Player]["Count"] = 1;
				}else{
					$players[$Player]["Count"] +=1;
				}
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
		return "<a href=\"player.php?id=".$PlayerID."\">".GetPlayerNameFromID($PlayerID)."</a>";
	}
	else{
		return GetPlayerNameFromID($PlayerID);
	}
}

function PrintMessage($msg){
	if(!empty($msg)){
		echo "<div class=\"message\">";
		echo $msg;
		echo "</div>";
	}
}
?>