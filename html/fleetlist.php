<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$myPID = GetPlayerIDFromName($username);
$_fleets = [];
$_refPlanets = [];
$_refSystems = [];
$_fleetIDs = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT * FROM fleets WHERE PlayerID='$myPID'");
while($row = mysqli_fetch_object($res)){
	$_fleets[] = $row;
	$_fleetIDs[] = (int)$row->FleetID;
	foreach(['Location','Destination'] as $f){
		$v = $row->$f ?? '';
		if(substr($v,0,2)==='P:') $_refPlanets[(int)substr($v,2)] = true;
		elseif(substr($v,0,2)==='S:') $_refSystems[(int)substr($v,2)] = true;
	}
}

// Bulk fetch planet/system names
$_pNames = [];
if($_refPlanets){
	$ids = implode(',',array_keys($_refPlanets));
	$r = mysqli_query($GLOBALS["conn"], "SELECT PlanetID, COALESCE(Name, DefaultName) AS Name FROM planets WHERE PlanetID IN($ids)");
	while($row = mysqli_fetch_object($r)) $_pNames[(int)$row->PlanetID] = $row->Name;
}
$_sNames = [];
if($_refSystems){
	$ids = implode(',',array_keys($_refSystems));
	$r = mysqli_query($GLOBALS["conn"], "SELECT SystemID, COALESCE(Name, DefaultName) AS Name FROM Systems WHERE SystemID IN($ids)");
	while($row = mysqli_fetch_object($r)) $_sNames[(int)$row->SystemID] = $row->Name;
}

// Bulk fetch ship stats per fleet: counts by type, total HP
$_fleetStats = [];
if($_fleetIDs){
	$fids = implode(',', $_fleetIDs);
	$r = mysqli_query($GLOBALS["conn"], "SELECT FleetID, Type, COUNT(*) AS cnt, SUM(HP) AS totalHP FROM ships WHERE FleetID IN($fids) GROUP BY FleetID, Type");
	while($row = mysqli_fetch_object($r)){
		$fid = (int)$row->FleetID;
		if(!isset($_fleetStats[$fid])) $_fleetStats[$fid] = ['types' => [], 'hp' => 0, 'total' => 0];
		$_fleetStats[$fid]['types'][(int)$row->Type] = (int)$row->cnt;
		$_fleetStats[$fid]['hp'] += (int)$row->totalHP;
		$_fleetStats[$fid]['total'] += (int)$row->cnt;
	}
}

// Bulk fetch AP from ship_types
$_typeAP = [];
$r = mysqli_query($GLOBALS["conn"], "SELECT Type, AP, Name FROM ship_types");
while($row = mysqli_fetch_object($r)){
	$_typeAP[(int)$row->Type] = (int)$row->AP;
	$_typeNames[(int)$row->Type] = $row->Name;
}

// Calculate AP per fleet
foreach($_fleetStats as $fid => &$_fs){
	$_fs['ap'] = 0;
	foreach($_fs['types'] as $t => $cnt){
		$_fs['ap'] += ($_typeAP[$t] ?? 0) * $cnt;
	}
}
unset($_fs);

// Ship type display order
$_typeOrder = [1 => 'SC', 2 => 'TR', 3 => 'CO', 4 => 'FR', 5 => 'CR', 6 => 'WS', 7 => 'MS', 8 => 'FI'];
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Fleets</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Fleets (<?php echo count($_fleets); ?>)</h2>
<?php if(count($_fleets) == 0): ?>
<p>You have no fleets.</p>
<?php endif; ?>
<?php foreach($_fleets as $fl):
	$fid = (int)$fl->FleetID;
	$name = $fl->Name != '' ? $fl->Name : 'Fleet '.$fl->FleetID;
	$loc = $fl->Location ?? '';
	$dest = $fl->Destination ?? '';
	$locStr = '';
	if($loc !== ''){
		$id = (int)substr($loc,2);
		if(substr($loc,0,2)==='P:') $locStr = 'Orbiting <a href="planet.php?id='.$id.'">' . h($_pNames[$id] ?? 'Unknown') . '</a>';
		elseif(substr($loc,0,2)==='S:') $locStr = 'In <a href="system.php?id='.$id.'">' . h($_sNames[$id] ?? 'Unknown') . '</a> System';
		elseif(substr($loc,0,2)==='X:') $locStr = 'In Sector <a href="sector.php?id='.$id.'">'.$id.'</a>';
	} elseif($dest !== ''){
		$did = (int)substr($dest,2);
		$dname = h($_pNames[$did] ?? 'Unknown');
		$strat = ['','for colonisation','to attack','to invade'][$fl->Strategy] ?? '';
		$locStr = 'Moving to <a href="planet.php?id='.$did.'">'.$dname.'</a>' . ($strat ? ' '.$strat : '');
	}
	$stats = $_fleetStats[$fid] ?? ['types' => [], 'hp' => 0, 'ap' => 0, 'total' => 0];
?>
<div class="panel" style="width:400px; margin-bottom:8px;">
	<h3><a href="fleet.php?id=<?php echo $fid; ?>"><?php echo h($name); ?></a></h3>
	<p><?php echo $locStr; ?>
	<?php if($dest !== '' && $fl->TTF > 0): ?>
		<small style="color:#888;">— ETA: <?php echo $fl->TTF; ?> min</small>
	<?php endif; ?>
	</p>
	<p><strong>Ships:</strong> <?php echo $stats['total']; ?>
	<?php
	$_classParts = [];
	foreach($_typeOrder as $_tid => $_abbr){
		if(isset($stats['types'][$_tid]) && $stats['types'][$_tid] > 0){
			$_classParts[] = $stats['types'][$_tid] . ' ' . h($_typeNames[$_tid] ?? $_abbr);
		}
	}
	if($_classParts):
	?>
	<br/><small style="color:#aaa;"><?php echo implode(' &middot; ', $_classParts); ?></small>
	<?php endif; ?>
	</p>
	<p><strong>HP:</strong> <?php echo number_format($stats['hp']); ?> &nbsp; <strong>AP:</strong> <?php echo number_format($stats['ap']); ?></p>
</div>
<?php endforeach; ?>
</body>
</html>
