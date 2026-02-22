<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");

if(isset($_GET['action'])){
	if(($_GET['action'] ?? "")=="build"){
		$PlanetID = ($_GET["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$BuildingType = ($_GET["id"] ?? "");
			$Grid = ($_GET["grid"] ?? "");
			$ret = Build($PlanetID,$BuildingType,$Grid);
		}
		if($ret>0){
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}else{
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid."&msg=Insufficient+Resources");
		}
	}
	if(($_GET['action'] ?? "")=="repair"){
		$PlanetID = ($_GET["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Grid = ($_GET["grid"] ?? "");
			Repair(GetBldIDFromGrid($PlanetID,$Grid));
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);	
		}else{
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid."&msg=You+don't+own+this+planet");
		}
	}
	if(($_GET['action'] ?? "")=="demolish"){
		$PlanetID = ($_GET["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Grid = ($_GET["grid"] ?? "");
			$sql = "DELETE FROM buildings WHERE(GridSquare = '$Grid')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
		}
		header("Location: planet.php?id=".$PlanetID."&msg=Building+Demolished");
	}
	if(($_GET['action'] ?? "")=="cancel"){
		$PlanetID = ($_GET["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Grid = ($_GET["grid"] ?? "");
			$sql = "DELETE FROM cbuildings WHERE(Grid = '$Grid')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
		}
		header("Location: planet.php?id=".$PlanetID."&msg=Building+Cancelled");
	}
	if(($_GET['action'] ?? "")=="consship"){
		$PlanetID = ($_POST["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Type = ($_POST["type"] ?? "");
			$Grid = ($_POST["grid"] ?? "");
			$Name = "";
			if(($_POST["name"] ?? "")==""){
				$Name = GetShipTypeString($Type);
			}else{
				$Name = ($_POST["name"] ?? "");
			}
			$ret = CreateShip($Type,$PlanetID,$Grid,$Name);
		}
		if($ret>0){
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}else{
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid."&msg=Insufficient+Resources");
		}
		
	}
	if(($_GET['action'] ?? "")=="addtoqueue"){
		$PlanetID = ($_POST["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Type = ($_POST["type"] ?? "");
			$Grid = ($_POST["grid"] ?? "");
			$Name = "";
			if(($_POST["name"] ?? "")==""){
				$Name = GetShipTypeString($Type);
			}else{
				$Name = ($_POST["name"] ?? "");
			}
			if(GetQueueSize($PlanetID,$Grid)>=10){
				header("Location: building.php?planet=".$PlanetID."&id=".$Grid."&msg=Queue+is+full");
			}else{
				AddToQueue($PlanetID,$Grid,$Type,$Name);
				header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
			}
		}
	}
	if(($_GET['action'] ?? "")=="createfleet"){
		$PlanetID = ($_GET["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Name = GetDefaultFleetName(GetPlayerIDFromName($username));
			CreateFleet($PlanetID,$Name,true);
		}
		header("Location: planet.php?dorefresh=1&id=".$PlanetID);
	}
	if(($_GET['action'] ?? "")=="addtofleet"){
		$PlanetID = ($_GET["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$FleetID = ($_POST["fleet"] ?? "");
			if($FleetID == "new"){
				$Name = ($_POST["name"] ?? "");
				if($Name==""){
					$Name = GetDefaultFleetName(GetPlayerIDFromName($username));
				}
				$FleetID = CreateFleet($PlanetID,$Name,false);
			}
			if(($_POST["transports"] ?? "")>0){
				for($i=0;$i<($_POST["transports"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,2);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
			if(($_POST["colonisers"] ?? "")>0){
				for($i=0;$i<($_POST["colonisers"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,3);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
			if(($_POST["frigates"] ?? "")>0){
				for($i=0;$i<($_POST["frigates"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,4);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
			if(($_POST["cruisers"] ?? "")>0){
				for($i=0;$i<($_POST["cruisers"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,5);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
			if(($_POST["warships"] ?? "")>0){
				for($i=0;$i<($_POST["warships"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,6);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
			if(($_POST["motherships"] ?? "")>0){
				for($i=0;$i<($_POST["motherships"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,7);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
			if(($_POST["fighters"] ?? "")>0){
				for($i=0;$i<($_POST["fighters"] ?? "");$i++){
					$ShipID = GetRandomUnassignedShipOfType($PlanetID,8);
					AddShipToFleet($ShipID,$FleetID);
				}
			}
		}
		header("Location: planet.php?dorefresh=1&id=".$PlanetID);
	}
}

if(!IsPlanet(($_GET['planet'] ?? ""))){
	echo "Not a valid planet ID";
}else{
	if(!OwnsPlanet($username,($_GET['planet'] ?? ""))){
		$edit = false;
	}else{
		$edit = true;
	}
	$PlanetID = ($_GET['planet'] ?? "");
	$Grid = ($_GET['id'] ?? "");
	$Planet = GetPlanet($PlanetID);
	$GridContents = GetGridContents($PlanetID,$Grid);
	$hp = GetBldHP($PlanetID,$Grid);
	$default_hp = GetBldDefaultHP($GridContents);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo GetPlanetNameFromID($PlanetID); ?> - Grid: <?php echo $Grid; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php");?>
<p>Return to: <a href="planet.php?id=<?php echo $PlanetID; ?>"><?php echo GetPlanetNameFromID($PlanetID); ?></a></p>
<h2>Grid: <?php echo $Grid; ?> </h2>
<?php if(ConstructingBuilding($PlanetID,$Grid))
{
$bldg = BuildingUnderConstruction($PlanetID,$Grid);
?>
<div class="panel" style="width:400px;">
  <p>Constructing: <?php echo GetGridContentString($bldg["Type"]); ?><br>
    Time Left: <?php echo $bldg["TTF"]; ?> minutes </p>
  <p><a href="building.php?action=cancel&planet=<?php echo $PlanetID; ?>&grid=<?php echo $Grid; ?>">Cancel</a> </p>
</div>
<?php }else{ ?>
<p>This grid contains: <?php echo GetGridContentString($GridContents); ?></p>
<?php if(($GridContents>0)&&(edit)){?>
<p>HP: <?php echo $hp."/".$default_hp; ?> [<a href="building.php?action=repair&planet=<?php echo $PlanetID; ?>&grid=<?php echo $Grid; ?>">Repair</a>]</p>
<p><a href="building.php?action=demolish&planet=<?php echo $PlanetID; ?>&grid=<?php echo $Grid; ?>">Demolish</a></p>
<p><?php PrintGridFunctions($PlanetID,$Grid); ?></p>
<?php } ?>
<?php if((!$GridContents>0)&&(edit)){?>
<h3>Build on this grid:</h3>
<?php
	$sql = "SELECT * FROM building_types ORDER BY Type ASC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
?>
<p><a href="building.php?action=build&planet=<?php echo $PlanetID; ?>&grid=<?php echo $Grid; ?>&id=<?php echo $row->Type; ?>"><?php echo $row->Name; ?></a> 
  - <?php echo $row->Metal; ?> Metal, <?php echo $row->Mineral; ?> Mineral - <?php echo $row->Turns; ?> turn(s)</p>
<?php } ?>
<?php } ?></body>
</html>
<?php } ?>
<?php } // Construction else ?>