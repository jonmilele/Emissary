#!/usr/bin/env php
<?php
include(__DIR__ . "/connect.inc.php");
include(__DIR__ . "/userfunctions.inc.php");

$fp = fopen(__DIR__ . "/turntime.txt","w");
fwrite($fp,time());
fclose($fp);

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