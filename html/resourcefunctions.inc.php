<?php
class ResourceBundle{
	var $Metal = 0;
	var $Mineral = 0;
	var $Astrium = 0;
	
	// Resource types: 1=Metal, 2=Mineral, 3=Astrium
	function Add($Amount,$Type){
		switch($Type){
			case "1": // Metal
				$this->Metal += $Amount;
				break;
			case "2": // Mineral
				$this->Mineral += $Amount;
				break;
			case "3": // Astrium
				$this->Astrium += $Amount;
				break;
		}
	}
	
	function AddAll($Metal,$Mineral,$Astrium){
		$this->Add($Metal,1);
		$this->Add($Mineral,2);
		$this->Add($Astrium,3);
	}
	function RoundIt(){
		$this->Metal = round($this->Metal,0);
		$this->Mineral = round($this->Mineral,0);
		$this->Astrium = round($this->Astrium,0);
	}
	// Resource types: 0=All, 1=Metal, 2=Mineral, 3=Astrium
	function Percentage($Amount,$Type){
		switch($Type){
			case "0": // All resources
				$this->Metal *= $Amount;
				$this->Mineral *= $Amount;
				$this->Astrium *= $Amount;
				break;
			case "1": // Metal
				$this->Metal *= $Amount;
				break;
			case "2": // Mineral
				$this->Mineral *= $Amount;
				break;
			case "3": // Astrium
				$this->Astrium *= $Amount;
				break;
		}
		$this->RoundIt();
	}
}
function DeductResources($PlayerID,$Type,$Amount){
	switch($Type){
	case "1":// Metal
			$sql = "SELECT Metal FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) break;
			if($row->Metal >= $Amount){
				$new = $row->Metal - $Amount;
				$sql = "UPDATE players SET Metal = '$new' WHERE(PlayerID = '$PlayerID')";
				$res = mysqli_query($GLOBALS["conn"], $sql);
				return true;
			}
			break;
		case "2":// Mineral
			$sql = "SELECT Mineral FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) break;
			if($row->Mineral >= $Amount){
				$new = $row->Mineral - $Amount;
				$sql = "UPDATE players SET Mineral = '$new' WHERE(PlayerID = '$PlayerID')";
				$res = mysqli_query($GLOBALS["conn"], $sql);
				return true;
			}
			break;
		case "3":// Astrium
			$sql = "SELECT Astrium FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) break;
			if($row->Astrium >= $Amount){
				$new = $row->Astrium - $Amount;
				$sql = "UPDATE players SET Astrium = '$new' WHERE(PlayerID = '$PlayerID')";
				$res = mysqli_query($GLOBALS["conn"], $sql);
				return true;
			}
			break;
	}
	return false;
}
function AddResources($PlayerID,$Type,$Amount){
	switch($Type){
	case "1":// Metal
			$sql = "SELECT Metal FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) break;
			$new = $row->Metal + $Amount;
			$sql = "UPDATE players SET Metal = '$new' WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			break;
		case "2":// Mineral
			$sql = "SELECT Mineral FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) break;
			$new = $row->Mineral + $Amount;
			$sql = "UPDATE players SET Mineral = '$new' WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			break;
		case "3":// Astrium
			$sql = "SELECT Astrium FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if(!$row) break;
			$new = $row->Astrium + $Amount;
			$sql = "UPDATE players SET Astrium = '$new' WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			break;
	}
	return false;
}
function HasSufficientResources($PlayerID,$Type,$Amount){
	switch($Type){
	case "1":// Metal
			$sql = "SELECT Metal FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if($row && $row->Metal >= $Amount){
				return true;
			}
			break;
		case "2":// Mineral
			$sql = "SELECT Mineral FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if($row && $row->Mineral >= $Amount){
				return true;
			}
			break;
		case "3":// Astrium
			$sql = "SELECT Astrium FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$row = mysqli_fetch_object($res);
			if($row && $row->Astrium >= $Amount){
				return true;
			}
			break;
	}
	return false;
}
function GetPlayerResources($PlayerID){
	$sql = "SELECT Metal, Mineral, Astrium FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($res);
	$stuff = array();
	if(!$row) return $stuff;
	$stuff["Metal"] = $row->Metal;
	$stuff["Mineral"] = $row->Mineral;
	$stuff["Astrium"] = $row->Astrium;
	return $stuff;
}

function PlanetaryIncome($PlanetID){
	return GetPlanetIncome($PlanetID); //Get base income
}

function GetUserCredits($PlayerID){
	$sql = "SELECT Credits FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($res);
	return $row ? $row->Credits : 0;
}
function AddUserCredits($PlayerID,$Amount){
	$sql = "SELECT Credits FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	$row = mysqli_fetch_object($res);
	if(!$row) return;
	$new = $row->Credits + $Amount;
	$sql = "UPDATE players SET Credits = '$new' WHERE(PlayerID = '$PlayerID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
}
function DeductUserCredits($PlayerID,$Amount){
	$sql = "SELECT Credits FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return false;
	if($row->Credits >= $Amount){
		$new = $row->Credits - $Amount;
		$sql = "UPDATE players SET Credits = '$new' WHERE(PlayerID = '$PlayerID')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		return true;
	}
	return false;
}

function GetUserIncome($PlayerID){
	$total = new ResourceBundle();
	$homePlanetID = GetHomePlanet($PlayerID);
	
	$Planets = GetPlanetList($PlayerID);
	foreach($Planets as $key=>$Planet){
		$income = GetPlanetIncome($Planet->PlanetID);
		// Home world gets multiplied resource production
		if($Planet->PlanetID == $homePlanetID){
			$mult = (float)GetGameSetting('home_income_multiplier', 2);
			$income->Percentage($mult, 0);
		}
		$total->AddAll($income->Metal,$income->Mineral,$income->Astrium);
	}
	return $total;
}

function IsAuction($AuctionID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM auctions WHERE(AuctionID = '$AuctionID')";
	$rescount=mysqli_query($GLOBALS["conn"], $sql);
	if ($rescount)
		if ($rowcount = mysqli_fetch_object($rescount))
			$total_count = $rowcount->count;
	
	if($total_count>0)
		return true;
	else
		return false;
}

function ListTeamAuctions($TeamID){
	$return = "";
	$tid = (int)$TeamID;
	if($tid < 1) return $return;
	$sql = "SELECT * FROM auctions WHERE OpenTo = '$tid'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	// Auction codes: 1=Ship, 2=Resources (not implemented), 3=Planet
	while($row = mysqli_fetch_object($res)){
		$end = (int)$row->StartTime + ((int)$row->Turns * 1800);
		if(time() >= $end) continue; // Skip expired (awaiting cron resolution)
		$lotDesc = '';
		switch($row->Code){
			case "1": // Ship auction
				$sqlship = "SELECT * FROM ships WHERE(ShipID = '".$row->Data."')";
				$resship = mysqli_query($GLOBALS["conn"], $sqlship);
				$rowship = mysqli_fetch_object($resship);
				$lotDesc = "Ship: ".GetShipTypeString($rowship->Type);
				break;
			case "2": // Resource auction (not implemented)
				break;
			case "3": // Planet auction
				$lotDesc = "Planet: ".h(GetPlanetNameFromID($row->Data));
				break;
		}
		if($lotDesc){
			$return .= "Auction of ".$lotDesc;
			$return .= " — Bid: ".((int)$row->CurrentBid > 0 ? $row->CurrentBid."C" : $row->StartBid."C (start)");
			$return .= " — Ends: ".date("M j, H:i", $end);
			$return .= " [<a href=\"auction.php?id=".$row->AuctionID."\">View</a>]<br>";
		}
	}
	return $return;
}

function CancelAuction($AuctionID, $PlayerID){
	$auc = GetAuction($AuctionID);
	if(!$auc) return false;
	if((int)$auc->Seller != (int)$PlayerID) return false;
	$end = (int)$auc->StartTime + ((int)$auc->Turns * 1800);
	if(time() >= $end) return false; // Already expired

	// Refund high bidder if any
	if((int)$auc->HighBidder > 0 && (int)$auc->CurrentBid > 0){
		AddUserCredits((int)$auc->HighBidder, (int)$auc->CurrentBid);
		include_once(__DIR__ . "/alertfunctions.inc.php");
		AddAlert((int)$auc->HighBidder, 'trade', 'Auction #'.$AuctionID.' was cancelled. '.$auc->CurrentBid.'C refunded.');
	}

	// Set 24h cooldown for non-resource auctions (Code != 2)
	if((int)$auc->Code != 2){
		SetAuctionCooldown((int)$auc->Code, $auc->Data);
	}

	mysqli_query($GLOBALS["conn"], "DELETE FROM auctions WHERE AuctionID='".(int)$AuctionID."'");
	return true;
}

function HasAuctionCooldown($Code, $Data){
	$code = (int)$Code;
	$data = mysqli_real_escape_string($GLOBALS["conn"], $Data);
	$sql = "SELECT CooldownUntil FROM auction_cooldowns WHERE Code='$code' AND Data='$data'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return false;
	if(time() < (int)$row->CooldownUntil) return true;
	// Expired cooldown — clean up
	mysqli_query($GLOBALS["conn"], "DELETE FROM auction_cooldowns WHERE Code='$code' AND Data='$data'");
	return false;
}

function SetAuctionCooldown($Code, $Data){
	$code = (int)$Code;
	$data = mysqli_real_escape_string($GLOBALS["conn"], $Data);
	$until = time() + 86400; // 24 hours
	mysqli_query($GLOBALS["conn"], "REPLACE INTO auction_cooldowns(Code, Data, CooldownUntil) VALUES('$code','$data','$until')");
}

function GetAuction($AuctionID){
	$sql = "SELECT * FROM auctions WHERE(AuctionID = '$AuctionID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	return mysqli_fetch_object($res);
}

// Returns ranking info for a player: ['rank' => N, 'totalValue' => X, 'totalPlayers' => Y]
// Or if no TargetPlayerID given, returns full sorted array of all players.
function GetPlayerRanking($TargetPlayerID = 0){
	$mcv = (int)GetGameSetting('metal_credit_value', 1);
	$ncv = (int)GetGameSetting('mineral_credit_value', 10);
	$acv = (int)GetGameSetting('astrium_credit_value', 100);

	$players = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT PlayerID, Metal, Mineral, Astrium, Credits FROM players WHERE PlayerID > 0");
	while($row = mysqli_fetch_object($res)){
		$pid = (int)$row->PlayerID;
		$resourceValue = ((int)$row->Metal * $mcv) + ((int)$row->Mineral * $ncv) + ((int)$row->Astrium * $acv) + (int)$row->Credits;
		$planetValue = 0;
		$pRes = mysqli_query($GLOBALS["conn"], "SELECT PlanetID FROM planets WHERE PlayerID='$pid'");
		while($pRow = mysqli_fetch_object($pRes)){
			$planetValue += GetPlanetValue((int)$pRow->PlanetID);
		}
		$players[] = ['pid' => $pid, 'totalValue' => $resourceValue + $planetValue, 'resourceValue' => $resourceValue, 'planetValue' => $planetValue];
	}
	usort($players, function($a, $b){ return $b['totalValue'] - $a['totalValue']; });

	if($TargetPlayerID > 0){
		$rank = 0;
		$lastVal = -1;
		$displayRank = 0;
		foreach($players as $p){
			$rank++;
			if($p['totalValue'] !== $lastVal){ $displayRank = $rank; $lastVal = $p['totalValue']; }
			if($p['pid'] == (int)$TargetPlayerID){
				return ['rank' => $displayRank, 'totalValue' => $p['totalValue'], 'resourceValue' => $p['resourceValue'], 'planetValue' => $p['planetValue'], 'totalPlayers' => count($players)];
			}
		}
		return ['rank' => 0, 'totalValue' => 0, 'resourceValue' => 0, 'planetValue' => 0, 'totalPlayers' => count($players)];
	}
	return $players;
}

?>
