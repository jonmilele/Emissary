<?php
// Prevent direct browser access — this file must only be included by admin/index.php
if(!defined('ADMIN_TOOLS_ACCESS')){
	http_response_code(403);
	exit('Forbidden');
}
include_once(__DIR__ . "/../connect.inc.php");
include_once(__DIR__ . "/../userfunctions.inc.php");

define('GAME_ROOT', realpath(__DIR__ . '/..'));

function CheckClashes($SectorID,$x,$y){
	$Systems = GetSystemsInSector($SectorID);
	echo "Checking Coords: ".$x.",".$y."<br/>";
	foreach($Systems as $k=>$System){
		$coords = explode("/",$System->Coords);		
		$sx = $coords[0]*50;
		$sy = $coords[1]*50;
		echo "Checking against Coords :".$sx.",".$sy."<br/>";
		if((($x<$sx)&&($x>$sx-25))&&(($y<$sy)&&($y>$sy-25))){
			echo "Clash with ".$System->Name." in Quadrant A<br/>";
			return true;
		}
		if((($x>$sx)&&($x<$sx+25))&&(($y<$sy)&&($y>$sy-25))){
			echo "Clash with ".$System->Name." in Quadrant B<br/>";
			return true;
		}
		if((($x>$sx)&&($x<$sx+25))&&(($y>$sy)&&($y<$sy+25))){
			echo "Clash with ".$System->Name." in Quadrant C<br/>";
			return true;
		}
		if((($x<$sx)&&($x>$sx-25))&&(($y>$sy)&&($y<$sy+25))){
			echo "Clash with ".$System->Name." in Quadrant D<br/>";
			return true;
		}
	}
	return false;
}

function AddPlanet($SystemID,$SystemName,$Orbit){
	$size = rand(1,3);
	$name = $SystemName." ".$Orbit;
	$query = "INSERT INTO planets(DefaultName,Orbit,`System`,Size) VALUES('$name','$Orbit','$SystemID','$size')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	$newPlanetID = mysqli_insert_id($GLOBALS["conn"]);
	$pBoons = AssignPlanetBoons($newPlanetID);
	$gBoons = AssignPlanetGridBoons($newPlanetID);
	$pBoonStr = !empty($pBoons) ? ' [' . implode(',', array_map('GetPlanetBoonName', $pBoons)) . ']' : '';
	echo "Added Planet: ".$name." ($gBoons grid boons)$pBoonStr<br/>";
}
function CreateCoords($SectorID){
		while(true){
			$x = round(rand(5,495)/50,1);
			$y = round(rand(5,495)/50,1);
			if(!CheckClashes($SectorID,$x*50,$y*50)){
				break;
			}
		}
		return $x."/".$y;
}
function PopulateSector($SectorID){
	include_once(GAME_ROOT . "/turnfunctions.inc.php");
	$count = 1;
	$_firstSystemName = '';
	if(!IsSector($SectorID)){
		$query = "INSERT INTO sectors(SectorID) VALUES('$SectorID')";
		$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	}
	$Systems = array();
	$namesFile = GAME_ROOT . "/userdata/names.txt";
	$sys = @file($namesFile);
	if(!$sys){
		echo "Error: Cannot read names.txt<br/>";
		return $Systems;
	}
	$start = (int)GetGameSetting('names_counter', 1);
	$count = $start;
	echo "sys[1] = ".$sys[$start]."<br/>";
	echo "Start: ".$start."<br/>";
	for($i = $start;$i<$start+10;$i++){
		if(!isset($sys[$i]) || $sys[$i] == ""){
			break;
		}
		echo "i: ".$i."<br/>";
		echo "Sys i: ".$sys[$i]."<br/>";
		$syst = trim($sys[$i]);
		
		echo "Trying to add system: ".$syst."<br/>";
		$orbits = rand(1,2);

		$coords = CreateCoords($SectorID);
		$query = "INSERT INTO Systems(DefaultName,Orbits,SectorID,Coords) VALUES('$syst','$orbits','$SectorID','$coords')";
		$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
		$system = mysqli_insert_id($GLOBALS["conn"]);
		echo "Added System: ".$syst."<br/>";
		if($_firstSystemName == '') $_firstSystemName = $syst;
		for($j=1;$j<=$orbits;$j++){
			AddPlanet($system,$syst,$j);
		}
		$count++;	
	}

	// Auto-name sector from first system
	if($_firstSystemName != ''){
		$_escName = mysqli_real_escape_string($GLOBALS["conn"], $_firstSystemName . ' Sector');
		mysqli_query($GLOBALS["conn"], "UPDATE sectors SET Name='$_escName' WHERE SectorID='$SectorID' AND (Name IS NULL OR Name = '')");
	}
	echo "Count: ".$count;
	SetGameSetting('names_counter', $count);
	return $Systems;
}
function ClearSector($SectorID){
	$query = "DELETE FROM Systems WHERE(SectorID = '$SectorID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	echo "Sector $SectorID cleared";
}
function ClearPlanets(){
	$query = "DELETE FROM planets WHERE(PlanetID > 6)";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	echo "Planets cleared";
}
function ResetPassword($PlayerID,$NewPass){
	$crypttext = password_hash($NewPass, PASSWORD_DEFAULT);
	$query = "UPDATE players SET Password = '$crypttext' WHERE(PlayerID = '$PlayerID')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
}

// Ownership is now calculated on-the-fly. This function is retained for admin use only.
function CalcOwners(){
	echo "Ownership is now computed on-the-fly from planet data. No stored values to update.";
}

?>
