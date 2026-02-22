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
	$size = rand(1,2);
	$name = $SystemName." ".$Orbit;
	$query = "INSERT INTO planets(Name,Orbit,`System`,Size) VALUES('$name','$Orbit','$SystemID','$size')";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	echo "Added Planet: ".$name."<br/>";
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
	$count = 1;
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
	$start = (int)trim($sys[0]);
	$count = $start;
	echo "sys[1] = ".$sys[1]."<br/>";
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
		$query = "INSERT INTO Systems(Name,Orbits,SectorID,Coords) VALUES('$syst','$orbits','$SectorID','$coords')";
		$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
		$system = mysqli_insert_id($GLOBALS["conn"]);
		echo "Added System: ".$syst."<br/>";
		for($j=1;$j<=$orbits;$j++){
			AddPlanet($system,$syst,$j);
		}
		$count++;	
	}

	echo "Count: ".$count;
	$sys[0] = $count."\n";
	file_put_contents($namesFile, implode('', $sys));
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

function CalcOwners(){
	$query = "SELECT SectorID FROM sectors";
	$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($notresult)){
		CalcMajOwner($row->SectorID);
	}
}

?>
