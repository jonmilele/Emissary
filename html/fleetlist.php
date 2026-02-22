<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Fleets</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Fleets:</h2>
<?php
$myPID = GetPlayerIDFromName($username);
$_fleets = [];
$_refPlanets = [];
$_refSystems = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT * FROM fleets WHERE PlayerID='$myPID'");
while($row = mysqli_fetch_object($res)){
	$_fleets[] = $row;
	// Collect referenced planet/system IDs for bulk lookup
	foreach(['Location','Destination'] as $f){
		$v = $row->$f ?? '';
		if(substr($v,0,2)==='P:') $_refPlanets[(int)substr($v,2)] = true;
		elseif(substr($v,0,2)==='S:') $_refSystems[(int)substr($v,2)] = true;
	}
}
// Bulk fetch names
$_pNames = [];
if($_refPlanets){
	$ids = implode(',',array_keys($_refPlanets));
	$r = mysqli_query($GLOBALS["conn"], "SELECT PlanetID, Name FROM planets WHERE PlanetID IN($ids)");
	while($row = mysqli_fetch_object($r)) $_pNames[(int)$row->PlanetID] = $row->Name;
}
$_sNames = [];
if($_refSystems){
	$ids = implode(',',array_keys($_refSystems));
	$r = mysqli_query($GLOBALS["conn"], "SELECT SystemID, Name FROM Systems WHERE SystemID IN($ids)");
	while($row = mysqli_fetch_object($r)) $_sNames[(int)$row->SystemID] = $row->Name;
}
foreach($_fleets as $fl){
	$name = $fl->Name != '' ? $fl->Name : 'Fleet '.$fl->FleetID;
	// Build location string inline
	$loc = $fl->Location ?? '';
	$dest = $fl->Destination ?? '';
	$locStr = '';
	if($loc !== ''){
		$id = (int)substr($loc,2);
		if(substr($loc,0,2)==='P:') $locStr = 'Orbiting <a href="planet.php?id='.$id.'">' . ($_pNames[$id] ?? 'Unknown') . '</a>';
		elseif(substr($loc,0,2)==='S:') $locStr = 'In <a href="system.php?id='.$id.'">' . ($_sNames[$id] ?? 'Unknown') . '</a> System';
		elseif(substr($loc,0,2)==='X:') $locStr = 'In Sector <a href="sector.php?id='.$id.'">'.$id.'</a>';
	} elseif($dest !== ''){
		$did = (int)substr($dest,2);
		$dname = $_pNames[$did] ?? 'Unknown';
		$strat = ['','for colonisation','to attack','to invade'][$fl->Strategy] ?? '';
		$locStr = 'Moving to <a href="planet.php?id='.$did.'">'.$dname.'</a>' . ($strat ? ' '.$strat : '') . ' - ETA: '.$fl->TTF.' min';
	}
?>
<p><a href="fleet.php?id=<?php echo $fl->FleetID; ?>"><?php echo htmlspecialchars($name); ?></a> - <?php echo $locStr; ?></p>
<?php
}
?>
<p>&nbsp;</p>
</body>
</html>
