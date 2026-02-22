<?php 
include("connect.inc.php");
include("userfunctions.inc.php");
for($i=0;$i<20;$i++){
	$sql = "INSERT INTO ships(Name,Type,PlanetID,PlayerID) VALUES('MotherShip',7,'".$_GET["id"]."',1)";
	mysql_query($sql);
}
?>
