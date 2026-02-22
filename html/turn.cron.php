#!/usr/bin/env php
<?php
// Prevent concurrent runs — exit immediately if another instance is running
$lockFp = fopen(__DIR__ . '/.turn.cron.lock', 'c');
if(!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)){
	exit(0);
}

include(__DIR__ . "/connect.inc.php");
include(__DIR__ . "/userfunctions.inc.php");

include(__DIR__ . "/turnfunctions.inc.php");
ResetTurnTimer();

function ProcessIncome(){
	$sql = "SELECT PlayerID FROM players";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$income = GetUserIncome($row->PlayerID);
		AddResources($row->PlayerID,1,$income->Metal);
	//	echo "Adding ".$income["Metal"]." Metal to user: ".$row->PlayerID."\n";
		AddResources($row->PlayerID,2,$income->Mineral);
	//	echo "Adding ".$income["Mineral"]." Mineral to user: ".$row->PlayerID."\n";
		AddResources($row->PlayerID,3,$income->Astrium);
	//	echo "Adding ".$income["Astrium"]." Astrium to user: ".$row->PlayerID."\n";
	}
}

ProcessIncome();
?>