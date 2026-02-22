<?php 
include("connect.inc.php");
include_once("userfunctions.inc.php");
for($i=0;$i<20;$i++){
	$sql = "INSERT INTO ships(Name,Type,PlanetID,PlayerID) VALUES('MotherShip',7,'".($_GET["id"] ?? "")."',1)";
	mysqli_query($GLOBALS["conn"], $sql);
}
?>
