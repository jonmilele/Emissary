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
ResolveExpiredAuctions();
PurgeOldAlerts(30);

function ResolveExpiredAuctions(){
	$now = time();
	$sql = "SELECT * FROM auctions";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($auc = mysqli_fetch_object($res)){
		$end = (int)$auc->StartTime + ((int)$auc->Turns * 1800);
		if($now < $end) continue; // Not yet expired

		$auctionID = (int)$auc->AuctionID;
		$seller = (int)$auc->Seller;
		$winner = (int)$auc->HighBidder;
		$bid = (int)$auc->CurrentBid;

		if($winner > 0 && $bid > 0){
			// Pay the seller
			AddUserCredits($seller, $bid);

			// Auction codes: 1=Ship, 3=Planet
			switch((int)$auc->Code){
				case 1: // Ship — transfer ownership
					$shipID = (int)$auc->Data;
					mysqli_query($GLOBALS["conn"], "UPDATE ships SET PlayerID='$winner', FleetID=0 WHERE ShipID='$shipID'");
					AddAlert($winner, 'trade', 'You won a ship auction for '.$bid.'C.');
					AddAlert($seller, 'trade', 'Your ship auction sold for '.$bid.'C.');
					break;
				case 3: // Planet — transfer ownership
					$planetID = (int)$auc->Data;
					// Check if this was the seller's home world before transfer
					$wasHome = IsHomePlanet($seller, $planetID);
					mysqli_query($GLOBALS["conn"], "UPDATE planets SET PlayerID='$winner' WHERE PlanetID='$planetID'");
					// Reassign buildings on the planet to the new owner
					mysqli_query($GLOBALS["conn"], "UPDATE buildings SET PlayerID='$winner' WHERE PlanetID='$planetID'");
					// Cancel any in-progress construction by old owner
					mysqli_query($GLOBALS["conn"], "DELETE FROM cbuildings WHERE PlanetID='$planetID' AND PlayerID='$seller'");
					// If seller lost their home world, clear it so they must pick a new one
					if($wasHome){
						mysqli_query($GLOBALS["conn"], "UPDATE players SET HomePlanetID=0 WHERE PlayerID='$seller'");
					}
					$pName = GetPlanetNameFromID($planetID);
					AddAlert($winner, 'trade', 'You won planet '.$pName.' at auction for '.$bid.'C.', 'planet.php?id='.$planetID);
					AddAlert($seller, 'trade', 'Your planet '.$pName.' sold at auction for '.$bid.'C.');
					if($wasHome){
						AddAlert($seller, 'system', 'Your home world was sold! You must set a new home world.', 'planetlist.php');
					}
					break;
			}
		} else {
			// No bids — notify seller
			// Auction codes: 1=Ship, 3=Planet
			switch((int)$auc->Code){
				case 1:
					AddAlert($seller, 'trade', 'Your ship auction ended with no bids.');
					break;
				case 3:
					$pName = GetPlanetNameFromID((int)$auc->Data);
					AddAlert($seller, 'trade', 'Your auction for planet '.$pName.' ended with no bids.');
					break;
			}
		}

		// Remove the resolved auction
		mysqli_query($GLOBALS["conn"], "DELETE FROM auctions WHERE AuctionID='$auctionID'");
	}
}
?>
