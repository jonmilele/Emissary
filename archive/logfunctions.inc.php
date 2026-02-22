<?php
function Report($PlayerID,$Code,$Data){
	$sql = "INSERT INTO gamelog(Time,PlayerID,Code,Data) VALUES('".time()."','$PlayerID','$Code','$Data')";
	$res = mysql_query($sql) or die(mysql_error());
}

function GetLastTenReportsString($PlayerID){
	$return = "";
	$sql = "SELECT * FROM gamelog WHERE(PlayerID = '$PlayerID') ORDER BY Time DESC LIMIT 0,10";
	$res = mysql_query($sql) or die(mysql_error());
	while($row = mysql_fetch_object($res))
	{
		switch($row->Code){
			case "1":
				$ar = split(":",$row->Data); 
				$return .= "A ".GetShipTypeString($ar[0])." was completed on <a href=\"planet.php?id=".$ar[1]."\">".GetPlanetNameFromID($ar[1])."</a><br/>";
				break;
			case "2":
				$ar = split(":",$row->Data);
				$return .= GetGridContentString($ar[0])." Completed on <a href=\"planet.php?id=".$ar[2]."\">".GetPlanetNameFromID($ar[2])."</a><br/>";
				break;
			case "3":
				$ar = split(":",$row->Data);
				$post = "";
				switch($ar[1]){
					case "0":
						$post = "";
						break;
					case "1":
						$post = " for colonisation";
						break;
					case "2":
						$post = " to attack";
						break;
					case "3":
						$post = " to invade";
						break;
				}
				$return .= "Fleet ".$ar[0]." Arrived at <a href=\"planet.php?id=".$ar[2]."\">".GetPlanetNameFromID($ar[2])."</a>".$post."<br/>";
				break;
			case "4":
				$sqlq = "SELECT * FROM battles WHERE(BattleID = '".$row->Data."')";
				$resq = mysql_query($sqlq) or die(mysql_error());
				$rowq = mysql_fetch_object($resq);
				if($PlayerID==$rowq->Winner){
					$return .= "You have won the battle of ".GetPlanetNameFromID($rowq->PlanetID)."<br/>";
				}else{
					$return .= "You have lost the battle of ".GetPlanetNameFromID($rowq->PlanetID)."<br/>";
				}
				break;
			case "5":
				$return .= "You have invaded <a href=\"planet.php?id=".$row->Data."\">".GetPlanetNameFromID($row->Data)."</a><br/>";
				break;
			case "6":
				$return .= "<a href=\"planet.php?id=".$row->Data."\">".GetPlanetNameFromID($row->Data)."</a> was invaded<br/>";
				break;
		}
	}
	return $return;
}

?>