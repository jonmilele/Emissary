#!/usr/bin/env php
<?php
// Prevent concurrent runs — exit immediately if another instance is running
$lockFp = fopen(__DIR__ . '/.turn.cron.lock', 'c');
if(!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)){
	exit(0);
}

include_once(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");
include_once(__DIR__ . "/alertfunctions.inc.php");

include_once(__DIR__ . "/turnfunctions.inc.php");
ResetTurnTimer();

function ProcessIncome(){
	$sql = "SELECT PlayerID FROM players";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$income = GetUserIncome($row->PlayerID);
		if($income->Metal > 0 || $income->Mineral > 0 || $income->Astrium > 0){
			AddResources($row->PlayerID,1,$income->Metal);
			AddResources($row->PlayerID,2,$income->Mineral);
			AddResources($row->PlayerID,3,$income->Astrium);
			AddAlert($row->PlayerID, 'economy', 'Income received: '.$income->Metal.' Metal, '.$income->Mineral.' Mineral, '.$income->Astrium.' Astrium');
		}
	}
}

ProcessIncome();
ProcessElectionCountdowns();
PurgeOldAlerts(30);
?>
