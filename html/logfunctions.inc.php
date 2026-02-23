<?php
function Report($PlayerID,$Code,$Data){
	$sql = "INSERT INTO gamelog(Time,PlayerID,Code,Data) VALUES('".time()."','$PlayerID','$Code','$Data')";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
}

function GetLastTenReportsString($PlayerID){
	$return = "";
	$sql = "SELECT * FROM gamelog WHERE(PlayerID = '$PlayerID') ORDER BY Time DESC LIMIT 0,10";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	// Log codes: 1=Ship completed, 2=Building completed, 3=Fleet arrived, 4=Battle result, 5=You invaded, 6=You were invaded
	while($row = mysqli_fetch_object($res))
	{
		switch($row->Code){
			case "1": // Ship completed (Data = "ShipType:PlanetID")
				$ar = explode(":",$row->Data); 
				$return .= "A ".h(GetShipTypeString($ar[0]))." was completed on <a href=\"planet.php?id=".(int)$ar[1]."\">".h(GetPlanetNameFromID($ar[1]))."</a><br/>";
				break;
			case "2": // Building completed (Data = "BuildingType:Grid:PlanetID")
				$ar = explode(":",$row->Data);
				$return .= h(GetGridContentString($ar[0]))." Completed on <a href=\"planet.php?id=".(int)$ar[2]."\">".h(GetPlanetNameFromID($ar[2]))."</a><br/>";
				break;
			case "3": // Fleet arrived (Data = "FleetID:Strategy:PlanetID")
				$ar = explode(":",$row->Data);
				$post = "";
				// Strategy: 0=orbit, 1=colonise, 2=attack, 3=invade
				switch($ar[1]){
					case "0": // Orbit
						$post = "";
						break;
					case "1": // Colonise
						$post = " for colonisation";
						break;
					case "2": // Attack
						$post = " to attack";
						break;
					case "3": // Invade
						$post = " to invade";
						break;
				}
				$return .= "Fleet ".(int)$ar[0]." Arrived at <a href=\"planet.php?id=".(int)$ar[2]."\">".h(GetPlanetNameFromID($ar[2]))."</a>".$post."<br/>";
				break;
			case "4": // Battle result (Data = BattleID)
				$sqlq = "SELECT * FROM battles WHERE(BattleID = '".$row->Data."')";
				$resq = mysqli_query($GLOBALS["conn"], $sqlq) or die(mysqli_error($GLOBALS["conn"]));
				$rowq = mysqli_fetch_object($resq);
				if($PlayerID==$rowq->Winner){
					$return .= "You have won the battle of ".h(GetPlanetNameFromID($rowq->PlanetID))."<br/>";
				}else{
					$return .= "You have lost the battle of ".h(GetPlanetNameFromID($rowq->PlanetID))."<br/>";
				}
				break;
			case "5": // You invaded a planet (Data = PlanetID)
				$return .= "You have invaded <a href=\"planet.php?id=".(int)$row->Data."\">".h(GetPlanetNameFromID($row->Data))."</a><br/>";
				break;
			case "6": // Your planet was invaded (Data = PlanetID)
				$return .= "<a href=\"planet.php?id=".(int)$row->Data."\">".h(GetPlanetNameFromID($row->Data))."</a> was invaded<br/>";
				break;
		}
	}
	return $return;
}

?>