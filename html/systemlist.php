<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$myPID = GetPlayerIDFromName($username);
$homePlanetID = GetHomePlanet($myPID);
$homeMult = (float)GetGameSetting('home_income_multiplier', 2);
$_myTeamID = (int)PlayerTeam($myPID);

// All systems where player has at least one planet
$_mySysRes = mysqli_query($GLOBALS["conn"],
	"SELECT DISTINCT s.SystemID, COALESCE(s.Name, s.DefaultName) AS Name, s.SectorID, s.PlayerID AS SysOwner, s.TeamID AS SysTeamID
	 FROM Systems s JOIN planets p ON p.`System` = s.SystemID
	 WHERE p.PlayerID = '$myPID' ORDER BY Name ASC");
$_mySystems = [];
while($row = mysqli_fetch_object($_mySysRes)) $_mySystems[] = $row;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo h($username);?>'s Systems</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>

<h2>Your Systems (<?php echo count($_mySystems); ?>)</h2>
<div class="side">
<?php foreach($_mySystems as $_sys):
	$sysID = (int)$_sys->SystemID;
	$planets = ListPlanetsInSystem($sysID);
	$totalPlanets = count($planets);

	$myCount = 0;
	$uncolonised = 0;
	$alliedPlayers = [];
	$rivalPlayers = [];
	$totalMetal = 0;
	$totalMineral = 0;
	$totalAstrium = 0;
	$_planetIDs = [];

	foreach($planets as $planet){
		$_planetIDs[] = (int)$planet->PlanetID;
		if((int)$planet->PlayerID == $myPID){
			$myCount++;
			$income = GetPlanetIncome($planet->PlanetID);
			if((int)$planet->PlanetID == $homePlanetID){
				$income->Percentage($homeMult, 0);
			}
			$totalMetal += $income->Metal;
			$totalMineral += $income->Mineral;
			$totalAstrium += $income->Astrium;
	} elseif($planet->PlayerID == 0){
			$uncolonised++;
		} else {
			$opid = (int)$planet->PlayerID;
			$opTeam = PlayerTeam($opid);
			$isAlly = ($_myTeamID > 0 && $opTeam == $_myTeamID);
			$bucket = $isAlly ? 'alliedPlayers' : 'rivalPlayers';
			if(!isset($$bucket[$opid])){
				$$bucket[$opid] = ['name' => GetPlayerNameFromID($opid), 'count' => 0, 'team' => $opTeam];
			}
			$$bucket[$opid]['count']++;
		}
	}

	// Fleet counts — single query for system + all orbiting planets
	$_locs = ["'S:$sysID'"];
	foreach($_planetIDs as $_pid) $_locs[] = "'P:$_pid'";
	$_fleetSql = "SELECT COUNT(*) AS total, SUM(PlayerID = '$myPID') AS mine FROM fleets WHERE Location IN(" . implode(',', $_locs) . ")";
	$_fleetRes = mysqli_query($GLOBALS["conn"], $_fleetSql);
	$_fleetRow = mysqli_fetch_object($_fleetRes);
	$_totalFleets = $_fleetRow ? (int)$_fleetRow->total : 0;
	$_myFleets = $_fleetRow ? (int)$_fleetRow->mine : 0;

	// Unassigned ship count on your planets in this system
	$_myPlanetIDs = [];
	foreach($planets as $p) if((int)$p->PlayerID == $myPID) $_myPlanetIDs[] = (int)$p->PlanetID;
	$_shipCount = 0;
	if($_myPlanetIDs){
		$_shipSql = "SELECT COUNT(*) AS cnt FROM ships WHERE FleetID = 0 AND PlanetID IN(" . implode(',', $_myPlanetIDs) . ")";
		$_shipRes = mysqli_query($GLOBALS["conn"], $_shipSql);
		$_shipRow = mysqli_fetch_object($_shipRes);
		$_shipCount = $_shipRow ? (int)$_shipRow->cnt : 0;
	}

	$_sysOwnerName = $_sys->SysOwner > 0 ? GetPlayerNameFromID($_sys->SysOwner) : '';
	$_isMajOwner = ((int)$_sys->SysOwner == $myPID);
	$_sysTeamID = (int)($_sys->SysTeamID ?? 0);
?>
<div class="panel" style="width:350px;">
	<h3><a href="system.php?id=<?php echo $sysID; ?>"><?php echo h($_sys->Name); ?></a>
	<?php if($_isMajOwner): ?> <small style="color:#FFFF00;">[Majority]</small><?php endif; ?>
	<?php if(!$_isMajOwner && $_sysTeamID > 0 && $_sysTeamID == $_myTeamID): ?> <small style="color:#00FF00;">[Team Control]</small><?php endif; ?>
	</h3>
	<small style="color:#888;">Sector: <a href="sector.php?id=<?php echo $_sys->SectorID; ?>"><?php echo h(GetSectorName($_sys->SectorID)); ?></a>
	<?php if($_sysOwnerName && !$_isMajOwner): ?> | Controller: <?php echo h($_sysOwnerName); ?><?php endif; ?>
	<?php if(!$_sysOwnerName && $_sysTeamID > 0): ?> | Team: <?php echo h(TeamNameFromID($_sysTeamID)); ?><?php endif; ?>
	</small><br/>

	<strong>Planets:</strong> <?php echo $myCount; ?>/<?php echo $totalPlanets; ?> owned
	<?php if($uncolonised > 0): ?><small style="color:#888;">(<?php echo $uncolonised; ?> uncolonised)</small><?php endif; ?>
	<?php
	$alliedCount = array_sum(array_column($alliedPlayers, 'count'));
	$rivalCount = array_sum(array_column($rivalPlayers, 'count'));
	if($alliedCount > 0): ?><small style="color:#00FF00;">(<?php echo $alliedCount; ?> allied)</small><?php endif; ?>
	<?php if($rivalCount > 0): ?><small style="color:#ff4444;">(<?php echo $rivalCount; ?> rival)</small><?php endif; ?>
	<br/>

	<strong>Income:</strong> <?php echo $totalMetal; ?> Metal / <?php echo $totalMineral; ?> Mineral / <?php echo $totalAstrium; ?> Astrium<br/>

	<strong>Fleets:</strong> <?php echo $_myFleets; ?> yours<?php if($_totalFleets > $_myFleets): ?>, <?php echo $_totalFleets - $_myFleets; ?> other<?php endif; ?>
	<?php if($_shipCount > 0): ?> | <?php echo $_shipCount; ?> unassigned ship<?php echo $_shipCount > 1 ? 's' : ''; ?><?php endif; ?>
	<br/>

	<?php if(count($alliedPlayers) > 0): ?>
	<strong style="color:#00FF00;">Allied Holdings:</strong><br/>
	<?php foreach($alliedPlayers as $_opid => $_op): ?>
		<small style="color:#00FF00;">- <a href="player.php?id=<?php echo $_opid; ?>" style="color:#00FF00;"><?php echo h($_op['name']); ?></a> (<?php echo $_op['count']; ?> planet<?php echo $_op['count'] > 1 ? 's' : ''; ?>)</small><br/>
	<?php endforeach; ?>
	<?php endif; ?>
	<?php if(count($rivalPlayers) > 0): ?>
	<strong>Rival Holdings:</strong><br/>
	<?php foreach($rivalPlayers as $_opid => $_op): ?>
		<small style="color:#ff4444;">- <a href="player.php?id=<?php echo $_opid; ?>"><?php echo h($_op['name']); ?></a> (<?php echo $_op['count']; ?> planet<?php echo $_op['count'] > 1 ? 's' : ''; ?>)
		<?php if($_op['team'] > 0): ?><span style="color:#888;"> [<?php echo h(TeamNameFromID($_op['team'])); ?>]</span><?php endif; ?>
		</small><br/>
	<?php endforeach; ?>
	<?php endif; ?>
</div>
<?php endforeach; ?>
</div>

<div style="clear:both;"></div>
<h2>Known Systems</h2>
<?php
$KSystems = GetKnownSystems($username);
$_mySystemIDs = [];
foreach($_mySystems as $_s) $_mySystemIDs[] = (int)$_s->SystemID;
$_hasKnown = false;
foreach($KSystems as $key=>$KSystem){
	if(in_array((int)$KSystem->SystemID, $_mySystemIDs)) continue;
	$_hasKnown = true;
?>
<p><a href="system.php?id=<?php echo $KSystem->SystemID; ?>"><?php echo h($KSystem->Name); ?></a>
<small style="color:#888;">(<a href="sector.php?id=<?php echo $KSystem->SectorID; ?>"><?php echo h(GetSectorName($KSystem->SectorID)); ?></a>)</small>
<?php
}
if(!$_hasKnown) echo '<p>No other known systems.</p>';
?>
</body>
</html>
