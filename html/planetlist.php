<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo h($username);?>'s Planets</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2><?php echo h($username);?>'s Planets</h2>
<?php
$myPID = GetPlayerIDFromName($username);
$Planets = GetPlanetList($myPID);
$_pids = array_keys($Planets);
if(count($_pids) > 0){
	$_pidList = implode(',', array_map('intval', $_pids));

	// Home planet
	$_homePID = 0;
	$res = mysqli_query($GLOBALS["conn"], "SELECT HomePlanetID FROM players WHERE PlayerID='$myPID'");
	$row = mysqli_fetch_object($res);
	if($row) $_homePID = (int)$row->HomePlanetID;

	// Building counts per planet per type
	$_bldCounts = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT PlanetID, Type, COUNT(*) AS cnt FROM buildings WHERE PlanetID IN($_pidList) GROUP BY PlanetID, Type");
	while($row = mysqli_fetch_object($res)){
		$_bldCounts[(int)$row->PlanetID][(int)$row->Type] = (int)$row->cnt;
	}

	// Fleets in orbit per planet (player's fleets)
	$_hasFleets = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT SUBSTRING(Location,3) AS pid, COUNT(*) AS cnt FROM fleets WHERE PlayerID='$myPID' AND Location LIKE 'P:%' GROUP BY pid");
	while($row = mysqli_fetch_object($res)){
		$_hasFleets[(int)$row->pid] = (int)$row->cnt;
	}

	// Unassigned ships per planet
	$_uShips = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT PlanetID, COUNT(*) AS cnt FROM ships WHERE PlanetID IN($_pidList) AND FleetID=0 GROUP BY PlanetID");
	while($row = mysqli_fetch_object($res)){
		$_uShips[(int)$row->PlanetID] = (int)$row->cnt;
	}

	// Busy shipyard grids (have ships under construction)
	$_busyYards = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT SUBSTRING_INDEX(Yard,':',1) AS pid, COUNT(DISTINCT Yard) AS cnt FROM cships WHERE SUBSTRING_INDEX(Yard,':',1) IN($_pidList) GROUP BY pid");
	while($row = mysqli_fetch_object($res)){
		$_busyYards[(int)$row->pid] = (int)$row->cnt;
	}
}
foreach($Planets as $key=>$Planet){
	$pid = $Planet->PlanetID;
	$shields = ($_bldCounts[$pid][6] ?? 0) + ($_bldCounts[$pid][8] ?? 0);
	$weapons = ($_bldCounts[$pid][7] ?? 0) + ($_bldCounts[$pid][9] ?? 0);
	$shipyards = $_bldCounts[$pid][4] ?? 0;
	$hasFleet = isset($_hasFleets[$pid]);
	$uTotal = $_uShips[$pid] ?? 0;
	$busyYards = $_busyYards[$pid] ?? 0;
	$idleYards = $shipyards - $busyYards;
?>
<div class="ship">
<p><a href="planet.php?id=<?php echo $pid; ?>"><?php echo h($Planet->Name); ?></a><?php if($_homePID == $pid): ?> <strong style="color:#FFFF00;">[Home]</strong><?php endif; ?><?php if($hasFleet){?>&nbsp;<img title="Has Fleets" align="absmiddle" src="images/ship.gif"><?php }?><?php if($shields > 0){?>&nbsp;<img title="Has Shields" align="absmiddle" src="images/shieldcount.img.php?id=<?php echo $pid; ?>"><?php }?><?php if($weapons > 0){?>&nbsp;<img align="absmiddle" src="images/weapon.gif"><?php } ?><br>
<?php if($uTotal > 0){ echo $uTotal." Unassigned Ship(s)<br/>"; } ?>
<?php if($weapons > 0){ echo $weapons; ?> weapon(s) - <?php echo $_bldCounts[$pid][7] ?? 0; ?> Pulse Cannons, <?php echo $_bldCounts[$pid][9] ?? 0; ?> Missile Silos<br/><?php }?>
<?php if($shipyards > 0){ echo $shipyards; ?> shipyard(s) - <?php if($idleYards > 0){ echo $idleYards."/".$shipyards; ?> idle<?php }else{?><span style="color: #FF0000;">All Busy</span><?php }?><br/><?php }?>
</p>
</div><?php
}
?>
</body>
</html>
