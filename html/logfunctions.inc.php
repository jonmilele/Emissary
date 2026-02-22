<?php
function Report($PlayerID,$Code,$Data){
	$sql = "INSERT INTO gamelog(Time,PlayerID,Code,Data) VALUES('".time()."','$PlayerID','$Code','$Data')";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
}

function GetLastTenReportsString($PlayerID){
	$return = "";
	$sql = "SELECT * FROM gamelog WHERE(PlayerID = '$PlayerID') ORDER BY Time DESC LIMIT 0,10";
	$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
	while($row = mysqli_fetch_object($res))
	{
		switch($row->Code){
			case "1":
				$ar = explode(":",$row->Data); 
				$return .= "A ".h(GetShipTypeString($ar[0]))." was completed on <a href=\"planet.php?id=".(int)$ar[1]."\">".h(GetPlanetNameFromID($ar[1]))."</a><br/>";
				break;
			case "2":
				$ar = explode(":",$row->Data);
				$return .= h(GetGridContentString($ar[0]))." Completed on <a href=\"planet.php?id=".(int)$ar[2]."\">".h(GetPlanetNameFromID($ar[2]))."</a><br/>";
				break;
			case "3":
				$ar = explode(":",$row->Data);
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
				$return .= "Fleet ".(int)$ar[0]." Arrived at <a href=\"planet.php?id=".(int)$ar[2]."\">".h(GetPlanetNameFromID($ar[2]))."</a>".$post."<br/>";
				break;
			case "4":
				$sqlq = "SELECT * FROM battles WHERE(BattleID = '".$row->Data."')";
				$resq = mysqli_query($GLOBALS["conn"], $sqlq) or die(mysqli_error($GLOBALS["conn"]));
				$rowq = mysqli_fetch_object($resq);
				if($PlayerID==$rowq->Winner){
					$return .= "You have won the battle of ".h(GetPlanetNameFromID($rowq->PlanetID))."<br/>";
				}else{
					$return .= "You have lost the battle of ".h(GetPlanetNameFromID($rowq->PlanetID))."<br/>";
				}
				break;
			case "5":
				$return .= "You have invaded <a href=\"planet.php?id=".(int)$row->Data."\">".h(GetPlanetNameFromID($row->Data))."</a><br/>";
				break;
			case "6":
				$return .= "<a href=\"planet.php?id=".(int)$row->Data."\">".h(GetPlanetNameFromID($row->Data))."</a> was invaded<br/>";
				break;
		}
	}
	return $return;
}

?>