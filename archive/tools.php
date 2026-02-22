<?php
include("connect.inc.php");
include("userfunctions.inc.php");
function CheckClashes($SectorID,$x,$y){
	$Systems = GetSystemsInSector($SectorID);
	echo "Checking Coords: ".$x.",".$y."<br/>";
	foreach($Systems as $k=>$System){
		$coords = split("/",$System->Coords);		
		$sx = $coords[0]*50;
		$sy = $coords[1]*50;
		echo "Checking against Coords :".$sx.",".$sy."<br/>";
		//Check quadrant A
		if((($x<$sx)&&($x>$sx-25))&&(($y<$sy)&&($y>$sy-25))){
			echo "Clash with ".$System->Name." in Quadrant A<br/>";
			return true;
		}
		//Check Quadrant B
		if((($x>$sx)&&($x<$sx+25))&&(($y<$sy)&&($y>$sy-25))){
			echo "Clash with ".$System->Name." in Quadrant B<br/>";
			return true;
		}
		//Check Quadrant C
		if((($x>$sx)&&($x<$sx+25))&&(($y>$sy)&&($y<$sy+25))){
			echo "Clash with ".$System->Name." in Quadrant C<br/>";
			return true;
		}
		//Check Quadrant D
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
	$query = "INSERT INTO planets(Name,Orbit,System,Size) VALUES('$name','$Orbit','$SystemID','$size')";
	$notresult = mysql_query($query) or die(mysql_error());
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
		$notresult = mysql_query($query) or die(mysql_error());
	}
	$Systems = array();
	$sys = @file("userdata/names.txt");
	$start = $sys[0];
	$start = substr($start,0,strlen($start)-1);
	$count = $start;
	echo "sys[1] = ".$sys[1]."<br/>";
	echo "Start: ".$start."<br/>";
	for($i = $start;$i<$start+10;$i++){
		if($sys[$i] == ""){
			break;
		}
		echo "i: ".$i."<br/>";
		echo "Sys i: ".$sys[$i]."<br/>";
		$syst = $sys[$i];
		
		if($count2==11){
			break;
		}	
		
		$syst = substr($syst,0,strlen($syst)-1);
		echo "Trying to add system: ".$syst."<br/>";
		$orbits = rand(1,2);

		$coords = CreateCoords($SectorID);
		$query = "INSERT INTO Systems(Name,Orbits,SectorID,Coords) VALUES('$syst','$orbits','$SectorID','$coords')";
		$notresult = mysql_query($query) or die(mysql_error());
		$system = mysql_insert_id();
		echo "Added System: ".$syst."<br/>";
		for($j=1;$j<=$orbits;$j++){
			AddPlanet($system,$syst,$j);
		}
		$count++;	
	}

	echo "Count: ".$count;
	$sys[0] = $count."\n";
	$fp = fopen("userdata/names.txt",w);
	$data = implode('',$sys);
	fwrite($fp,$data,strlen($data));
	fclose($fp);
	return $Systems;
}
function ClearSector($SectorID){
	$query = "DELETE FROM Systems WHERE(SectorID = '$SectorID')";
	$notresult = mysql_query($query) or die(mysql_error());
	echo "Sector $SectorID cleared";
}
function ClearPlanets(){
	$query = "DELETE FROM planets WHERE(PlanetID > 6)";
	$notresult = mysql_query($query) or die(mysql_error());
	echo "Planets cleared";
}
function ResetPassword($PlayerID,$NewPass){
	$iv = mcrypt_create_iv (mcrypt_get_iv_size (MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
    $key = "nonnyrules";
    $text = $NewPass;
    $crypttext = mcrypt_encrypt (MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
	$query = "UPDATE players SET Password = '$crypttext' WHERE(PlayerID = '$PlayerID')";
	$notresult = mysql_query($query) or die(mysql_error());
}

function CalcOwners(){
	$query = "SELECT SectorID FROM sectors";
	$notresult = mysql_query($query) or die(mysql_error());
	while($row = mysql_fetch_object($notresult)){
		CalcMajOwner($row->SectorID);
	}
}

switch($_GET['action']){
	case "populate_sector":
		PopulateSector($_GET['id']);
		break;
	case "clear_sector":
		ClearSector($_GET['id']);
		break;
	case "clear_planets":
		ClearPlanets();
		break;
	case "reset_password":
		ResetPassword($_GET['id'],$_GET['new']);
		break;
	case "owners":
		CalcOwners();
		break;
}
?>