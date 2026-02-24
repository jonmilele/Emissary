#!/usr/bin/env php
<?php
// Prevent concurrent runs — exit immediately if another instance is running
$lockFp = fopen(__DIR__ . '/.miniturn.cron.lock', 'c');
if(!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)){
	exit(0);
}

include_once(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");
include_once(__DIR__ . "/alertfunctions.inc.php");

function DropFleetTTF($FleetID){
	global $username;
	$sql = "SELECT * FROM fleets WHERE(FleetID = '$FleetID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	
	if($row->TTF>1){
		$newTtf = $row->TTF;
		$newTtf = $newTtf-1;
		$sql = "UPDATE fleets SET TTF = '$newTtf' WHERE(FleetID = '$FleetID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);	
		return $newTtf;
	}else{
		$sql = "UPDATE fleets SET TTF = '0', Location = '".$row->Destination."', MovingFrom = '', Destination = '', Strategy = '0' WHERE(FleetID = '$FleetID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$_arrPlanet = substr($row->Destination,2,strlen($row->Destination)-2);
		$_stratLabels = ['0'=>'','1'=>' for colonisation','2'=>' to attack','3'=>' to invade'];
		$_stratPost = $_stratLabels[$row->Strategy] ?? '';
		AddAlert($row->PlayerID, 'fleet', 'Fleet '.GetFleetName($FleetID).' arrived at '.GetPlanetNameFromID($_arrPlanet).$_stratPost, 'planet.php?id='.$_arrPlanet);
		// Strategy: 0=orbit, 1=colonise, 2=attack, 3=invade
		switch($row->Strategy){
			case 1: // Colonise
				$PlanetID = substr($row->Destination,2,strlen($row->Destination)-2);
				$PlayerID = $row->PlayerID;
				$username = GetPlayerNameFromID($PlayerID);
				if(CanColonise($PlanetID,$FleetID)){
					Colonise($PlanetID);
				}
				break;
			case 2: // Attack
				AttackPlanet($FleetID,substr($row->Destination,2,strlen($row->Destination)-2),false);
				break;
			case 3: // Invade
				AttackPlanet($FleetID,substr($row->Destination,2,strlen($row->Destination)-2),true);
				break;
		}
	}
}

function DropShipTTF($ShipID){
	$sql = "SELECT * FROM cships WHERE(ID = '$ShipID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	
	if($row->TTF>1){
		$newTtf = $row->TTF;
		$newTtf = $newTtf-1;
		$sql = "UPDATE cships SET TTF = '$newTtf' WHERE(ID = '$ShipID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		
	//	$fp = fopen("minicron.txt","a");
	//	fwrite($fp,"Updating Ship: ".$ShipID." - ".$newTtf."\n");
	//	fclose($fp);
		
		return $newTtf;
	}else{
		$HP = GetShipTypeDefaultHP($row->Type);
		$planetar = explode(":",$row->Yard);
		$planet = $planetar[0];
		$grid = $planetar[1];
		$reg = GenerateShipRegistration($row->Type);
		$regEsc = mysqli_real_escape_string($GLOBALS["conn"], $reg);
		$sql = "INSERT INTO ships(PlayerID,Type,PlanetID,Name,HP,Registration) VALUES('".$row->PlayerID."','".$row->Type."','".$planet."','".$row->Name."','$HP','$regEsc')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$id = mysqli_insert_id($GLOBALS["conn"]);
		$sql = "DELETE FROM cships WHERE(ID = '$ShipID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		AddAlert($row->PlayerID, 'construction', 'A '.GetShipTypeString($row->Type).' was completed on '.GetPlanetNameFromID($planet), 'planet.php?id='.$planet);
		if(ShipsInQueue($planet,$grid)){
			//echo "Ships in Queue\n";
			$ship = GetNextShipInQueue($planet,$grid);
			//echo "Next Ship ".$ship."\n";
			$query = "SELECT * FROM qships WHERE(ShipID = '$ship')";
			$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
			$srow = mysqli_fetch_object($notresult);
			if(CreateShip($srow->Type,$planet,$grid,$srow->Name,$row->PlayerID)>0){
			//	echo "Ship Created\n";
				$query = "DELETE FROM qships WHERE(ShipID = '$ship')";
				$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
				ShiftQueue($planet,$grid);
			}
		}
		return 0;
	}
}

function ResumePausedQueues(){
	$sql = "SELECT * FROM buildings WHERE(Type = '4')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	
	while($row = mysqli_fetch_object($res)){
		$Planet = GetPlanet($row->PlanetID);
		//echo "Checking yard ".$row->BuildingID."\n";
		if(ShipsInQueue($row->PlanetID,$row->GridSquare)&&!ConstructingShip($row->PlanetID,$row->GridSquare)){
			//echo "Ships in Queue,no construction ".$row->PlanetID."\n";
			$ship = GetNextShipInQueue($row->PlanetID,$row->GridSquare);
			//echo "Next Ship ".$ship."\n";
			$query = "SELECT * FROM qships WHERE(ShipID = '$ship')";
			$notresult = mysqli_query($GLOBALS["conn"], $query) or die(mysqli_error($GLOBALS["conn"]));
			$srow = mysqli_fetch_object($notresult);
			if(CreateShip($srow->Type,$row->PlanetID,$row->GridSquare,$srow->Name,$Planet->PlayerID)>0){
				//echo "Resuming Queue\n";
				$qry = "DELETE FROM qships WHERE(ShipID = '$ship')";
				$result = mysqli_query($GLOBALS["conn"], $qry) or die(mysqli_error($GLOBALS["conn"]));
				ShiftQueue($row->PlanetID,$row->GridSquare);
			}
		}
	}
}

function DropBuildingTTF($ShipID){
	$sql = "SELECT * FROM cbuildings WHERE(ID = '$ShipID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	
	if($row->TTF>1){
		$newTtf = $row->TTF;
		$newTtf = $newTtf-1;
		$sql = "UPDATE cbuildings SET TTF = '$newTtf' WHERE(ID = '$ShipID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		
	//	$fp = fopen("minicron.txt","a");
	//	fwrite($fp,"Updating Building: ".$ShipID." - ".$newTtf."\n");
	//	fclose($fp);
		
		return $newTtf;
	}else{
		$sql = "INSERT INTO buildings(Type,PlanetID,GridSquare,HP) VALUES('".$row->Type."','".$row->PlanetID."','".$row->Grid."','".GetBldDefaultHP($row->Type)."')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$id = mysqli_insert_id($GLOBALS["conn"]);
		$sql = "DELETE FROM cbuildings WHERE(ID = '$ShipID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		AddAlert($row->PlayerID, 'construction', GetGridContentString($row->Type).' completed on '.GetPlanetNameFromID($row->PlanetID), 'planet.php?id='.$row->PlanetID);
		return 0;
	}
}

function ProcessShipConstruction(){
	$sql = "SELECT * FROM cships";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		//echo "Dropping TTF for ship: ".$row->ID."\n";
		DropShipTTF($row->ID);
	}
}

function ProcessBuildingConstruction(){
	$sql = "SELECT * FROM cbuildings";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		//echo "Dropping TTF for ship: ".$row->ID."\n";
		DropBuildingTTF($row->ID);
	}
}

function ProcessFleetMovements(){
	$sql = "SELECT * FROM fleets WHERE(TTF>0)";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
	//	echo "Dropping TTF for fleet: ".$row->FleetID."\n";
		DropFleetTTF($row->FleetID);
	}
}


// Run
ProcessShipConstruction();
ProcessBuildingConstruction();
ProcessFleetMovements();
ResumePausedQueues();
?>
