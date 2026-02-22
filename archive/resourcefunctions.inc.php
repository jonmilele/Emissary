<?php
class ResourceBundle{
	var $Metal = 0;
	var $Mineral = 0;
	var $Astrium = 0;
	
	function Add($Amount,$Type){
		switch($Type){
			case "1":
				$this->Metal += $Amount;
				break;
			case "2":
				$this->Mineral += $Amount;
				break;
			case "3":
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
	function Percentage($Amount,$Type){
		switch($Type){
			case "0":
				$this->Metal *= $Amount;
				$this->Mineral *= $Amount;
				$this->Astrium *= $Amount;
				break;
			case "1":
				$this->Metal *= $Amount;
				break;
			case "2":
				$this->Mineral *= $Amount;
				break;
			case "3":
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
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			if($row->Metal >= $Amount){
				$new = $row->Metal - $Amount;
				$sql = "UPDATE players SET Metal = '$new' WHERE(PlayerID = '$PlayerID')";
				$res = mysql_query($sql);
				return true;
			}
			break;
		case "2":// Mineral
			$sql = "SELECT Mineral FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			if($row->Mineral >= $Amount){
				$new = $row->Mineral - $Amount;
				$sql = "UPDATE players SET Mineral = '$new' WHERE(PlayerID = '$PlayerID')";
				$res = mysql_query($sql);
				return true;
			}
			break;
		case "3":// Astrium
			$sql = "SELECT Astrium FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			if($row->Astrium >= $Amount){
				$new = $row->Astrium - $Amount;
				$sql = "UPDATE players SET Astrium = '$new' WHERE(PlayerID = '$PlayerID')";
				$res = mysql_query($sql);
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
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);

			$new = $row->Metal + $Amount;
			$sql = "UPDATE players SET Metal = '$new' WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			break;
		case "2":// Mineral
			$sql = "SELECT Mineral FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			
			$new = $row->Mineral + $Amount;
			$sql = "UPDATE players SET Mineral = '$new' WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			break;
		case "3":// Astrium
			$sql = "SELECT Astrium FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);

			$new = $row->Astrium + $Amount;
			$sql = "UPDATE players SET Astrium = '$new' WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			break;
	}
	return false;
}
function HasSufficientResources($PlayerID,$Type,$Amount){
	switch($Type){
		case "1":// Metal
			$sql = "SELECT Metal FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			if($row->Metal >= $Amount){
				return true;
			}
			break;
		case "2":// Mineral
			$sql = "SELECT Mineral FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			if($row->Mineral >= $Amount){
				return true;
			}
			break;
		case "3":// Astrium
			$sql = "SELECT Astrium FROM players WHERE(PlayerID = '$PlayerID')";
			$res = mysql_query($sql);
			$row = mysql_fetch_object($res);
			if($row->Astrium >= $Amount){
				return true;
			}
			break;
	}
	return false;
}
function GetPlayerResources($PlayerID){
	$sql = "SELECT Metal, Mineral, Astrium FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_object($res);
	$stuff = array();
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
	$res = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_object($res);
	return $row->Credits;
}
function AddUserCredits($PlayerID,$Amount){
	$sql = "SELECT Credits FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysql_query($sql) or die(mysql_error());
	$row = mysql_fetch_object($res);
	$new = $row->Credits + $Amount;
	$sql = "UPDATE players SET Credits = '$new' WHERE(PlayerID = '$PlayerID')";
	$res = mysql_query($sql);
}
function DeductUserCredits($PlayerID,$Amount){
	$sql = "SELECT Credits FROM players WHERE(PlayerID = '$PlayerID')";
	$res = mysql_query($sql);
	$row = mysql_fetch_object($res);
	if($row->Credits >= $Amount){
		$new = $row->Credits - $Amount;
		$sql = "UPDATE players SET Credits = '$new' WHERE(PlayerID = '$PlayerID')";
		$res = mysql_query($sql);
		return true;
	}
	return false;
}

function GetUserIncome($PlayerID){
	$total = new ResourceBundle();
	
	$Planets = GetPlanetList($PlayerID);
	foreach($Planets as $key=>$Planet){
		$income = GetPlanetIncome($Planet->PlanetID);
		$total->AddAll($income->Metal,$income->Mineral,$income->Astrium);
	}
	return $total;
}

function IsAuction($AuctionID){
	$total_count = 0;

	//echo $filterdate." ";
	$sql= "SELECT COUNT(*) AS count FROM auctions WHERE(AuctionID = '$AuctionID')";
	$rescount=mysql_query($sql);
	if ($rescount)
		if ($rowcount = mysql_fetch_object($rescount))
			$total_count = $rowcount->count;
	
	if($total_count>0)
		return true;
	else
		return false;
}

function ListPublicAuctions(){
	$return = "";
	$sql = "SELECT * FROM auctions WHERE(OpenTo = '1')";
	$res = mysql_query($sql);
	while($row = mysql_fetch_object($res)){
		switch($row->Code){
			case "1":
				$sqlship = "SELECT * FROM ships WHERE(ShipID = '".$row->Data."')";
				$resship = mysql_query($sqlship);
				$rowship = mysql_fetch_object($resship);
				$return .= "Auction of ".GetShipTypeString($rowship->Type)." [<a href=\"auction.php?id=".$row->AuctionID."\">View Auction</a>]";
				break;
			case "2":
				break;
		}
	}
	return $return;
}

function GetAuction($AuctionID){
	$sql = "SELECT * FROM auctions WHERE(AuctionID = '$AuctionID')";
	$res = mysql_query($sql);
	return mysql_fetch_object($res);
}

?>