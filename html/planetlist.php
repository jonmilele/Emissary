<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo $username;?>'s Planets</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2><?php echo $username;?>'s Planets</h2>
<?php
$Planets = GetPlanetList(GetPlayerIDFromName($username));
foreach($Planets as $key=>$Planet){
?>
<div class="ship">
<p><a href="planet.php?id=<?php echo $Planet->PlanetID; ?>"><?php echo $Planet->Name; ?></a><?php if(HasFleets($Planet->PlanetID)){?>&nbsp;<img title="Has Fleets" align="absmiddle" src="images/ship.gif"><?php }?><?php if(HasShields($Planet->PlanetID)){?>&nbsp;<img title="Has Shields" align="absmiddle" src="images/shieldcount.img.php?id=<?php echo $Planet->PlanetID; ?>"><?php }?><?php if(HasWeapons($Planet->PlanetID)){?>&nbsp;<img align="absmiddle" src="images/weapon.gif"><?php } ?><br>
<?php
$uships = GetUnassignedShips($Planet->PlanetID);
if($uships["Total"]>0){
	echo $uships["Total"]." Unassigned Ship(s)<br/>";
}
?>
<?php if (HasWeapons($Planet->PlanetID)){ echo CountWeapons($Planet->PlanetID); ?> weapon(s) - <?php echo CountBuildingsofType($Planet->PlanetID,7)?> Pulse Cannons, <?php echo CountBuildingsofType($Planet->PlanetID,9)?> Missile Silos<br/><?php }?>
<?php if (CountBuildingsofType($Planet->PlanetID,4)>0){ echo CountBuildingsofType($Planet->PlanetID,4); ?> shipyard(s) - <?php if(IdleShipyards($Planet->PlanetID)>0){ echo IdleShipyards($Planet->PlanetID)."/".CountBuildingsofType($Planet->PlanetID,4); ?> idle<?php }else{?><span style="color: #FF0000;">All Busy</span><?php }?><br/><?php }?>
</p>
</div><?php
}
?>
</body>
</html>
