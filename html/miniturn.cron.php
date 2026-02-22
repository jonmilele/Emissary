#!/usr/bin/env php
<?php
// Prevent concurrent runs — exit immediately if another instance is running
$lockFp = fopen(__DIR__ . '/.miniturn.cron.lock', 'c');
if(!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)){
	exit(0);
}

include_once(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");

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
		$sql = "UPDATE fleets SET TTF = '0', Location = '".$row->Destination."', MovingFrom = '', Destination = '', Strategy = '' WHERE(FleetID = '$FleetID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		Report($row->PlayerID,3,$FleetID.":".$row->Strategy.":".substr($row->Destination,2,strlen($row->Destination)-2));
		if($row->Strategy==2){
			AttackPlanet($FleetID,substr($row->Destination,2,strlen($row->Destination)-2),false);
		}
		elseif($row->Strategy==3){
			AttackPlanet($FleetID,substr($row->Destination,2,strlen($row->Destination)-2),true);
		}
		elseif($row->Strategy==1){
			if(CanColonise($PlanetID,$FleetID)){
				$username = GetPlayerNameFromID($PlayerID);
				Colonise(substr($row->Destination,2,strlen($row->Destination)-2));
			}
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
		$sql = "INSERT INTO ships(PlayerID,Type,PlanetID,Name,HP) VALUES('".$row->PlayerID."','".$row->Type."','".$planet."','".$row->Name."','$HP')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$id = mysqli_insert_id($GLOBALS["conn"]);
		$sql = "DELETE FROM cships WHERE(ID = '$ShipID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		Report($row->PlayerID,1,$row->Type.":".$planet);
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
		Report($row->PlayerID,2,$row->Type.":".$row->Grid.":".$row->PlanetID);
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
