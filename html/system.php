<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

// Handle rename action
if(isset($_POST['action']) && $_POST['action'] == 'rename_system' && csrf_validate()){
	$_rSid = (int)($_POST['system_id'] ?? 0);
	$_rName = $_POST['system_name'] ?? '';
	$_rPid = GetPlayerIDFromName($username);
	$result = RenameSystem($_rSid, $_rName, $_rPid);
	if($result === true){
		header("Location: system.php?id=$_rSid&msg=System+renamed");
	} else {
		header("Location: system.php?id=$_rSid&msg=" . urlencode($result));
	}
	exit;
}
if(isset($_POST['action']) && $_POST['action'] == 'revert_system_name' && csrf_validate()){
	$_rSid = (int)($_POST['system_id'] ?? 0);
	$_rPid = GetPlayerIDFromName($username);
	if(CanRenameSystem($_rSid, $_rPid)){
		RevertSystemName($_rSid);
		header("Location: system.php?id=$_rSid&msg=Name+reverted+to+default");
	} else {
		header("Location: system.php?id=$_rSid&msg=Cannot+revert+name");
	}
	exit;
}

if(!IsSystem(($_GET['id'] ?? ""))){
	echo "Not a valid system ID";
}else{
	$SystemID = ($_GET['id'] ?? "");
	CheckSystemMajOwner($SystemID);
	$System = GetSystem($SystemID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>System: <?php echo h(GetSystemNameFromID($SystemID)); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
if(isset($_GET['msg'])): ?><p><strong><?php echo h($_GET['msg']); ?></strong></p><?php endif;
?>
<h2>System: <?php echo h($System->Name); ?>
<?php if($System->DefaultName && $System->Name !== $System->DefaultName): ?>
  <small style="color:#888;">(originally <?php echo h($System->DefaultName); ?>)</small>
<?php endif; ?>
</h2>

  
    <div class="side">
	<div class="panel" style="width:200px;">
Sector: <a href="sector.php?id=<?php echo $System->SectorID; ?>"><?php echo h(GetSectorName($System->SectorID)); ?></a><br/>
	Fleets in System: <?php echo FleetsInSystem($SystemID); ?><br/>
      Planets: <?php echo PlanetsInSystem($SystemID); ?><br/>
      
  <ol>
    <?php
$Planets = ListPlanetsInSystem($SystemID);
$_sysPids = array_keys($Planets);
if(count($_sysPids) > 0){
	$_pidList = implode(',', array_map('intval', $_sysPids));
	// Bulk: building types per planet (shields: 6,8; weapons: 7,9)
	$_sysBld = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT PlanetID, Type, COUNT(*) AS cnt FROM buildings WHERE PlanetID IN($_pidList) AND Type IN(6,7,8,9) GROUP BY PlanetID, Type");
	while($row = mysqli_fetch_object($res)) $_sysBld[(int)$row->PlanetID][(int)$row->Type] = (int)$row->cnt;
	// Bulk: fleets orbiting these planets
	$_sysFleets = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT SUBSTRING(Location,3) AS pid FROM fleets WHERE Location IN(" . implode(',', array_map(function($id){return "'P:$id'";}, $_sysPids)) . ") GROUP BY pid");
	while($row = mysqli_fetch_object($res)) $_sysFleets[(int)$row->pid] = true;
	// Bulk: player names and home planets
	$_ownerPIDs = [];
	foreach($Planets as $p) if($p->PlayerID > 0) $_ownerPIDs[(int)$p->PlayerID] = true;
	$_playerNames = [];
	$_homePlanets = [];
	if($_ownerPIDs){
		$res = mysqli_query($GLOBALS["conn"], "SELECT PlayerID, UserName, HomePlanetID FROM players WHERE PlayerID IN(".implode(',',array_keys($_ownerPIDs)).")");
		while($row = mysqli_fetch_object($res)){
			$_playerNames[(int)$row->PlayerID] = $row->UserName;
			$_homePlanets[(int)$row->PlayerID] = (int)$row->HomePlanetID;
		}
	}
}
foreach($Planets as $key=>$Planet){
	$pid = $Planet->PlanetID;
	$shields = ($_sysBld[$pid][6] ?? 0) + ($_sysBld[$pid][8] ?? 0);
	$weapons = ($_sysBld[$pid][7] ?? 0) + ($_sysBld[$pid][9] ?? 0);
	$isHome = ($Planet->PlayerID > 0 && ($_homePlanets[$Planet->PlayerID] ?? 0) == $pid);
	$hasFleet = isset($_sysFleets[$pid]);
	$ownerName = $_playerNames[$Planet->PlayerID] ?? '';
?>
    <li> <a href="planet.php?id=<?php echo $pid; ?>"><?php echo h($Planet->Name); ?></a><?php if($isHome): ?> <strong style="color:#FFFF00;">[H]</strong><?php endif; ?><?php if($hasFleet){?>&nbsp;<img title="Has Fleets" align="absmiddle" src="images/ship.gif"><?php }?><?php if($shields > 0){?>&nbsp;<img title="Has Shields" align="absmiddle" src="images/shieldcount.img.php?id=<?php echo $pid; ?>"><?php }?><?php if($weapons > 0){?>&nbsp;<img title="Has Weapons" align="absmiddle" src="images/weapon.gif"><?php }?>
    <small><?php echo $Planet->PlayerID > 0 ? '(' . htmlspecialchars($ownerName) . ')' : '(Uncolonised)'; ?></small>
    </li>
    <?php
}
?>
  </ol>
  <?php if ($System->PlayerID>0){?>
      Owner: <?php echo h($_playerNames[$System->PlayerID] ?? GetPlayerNameFromID($System->PlayerID)); ?><br/>
	  <?php } ?>
	  <?php if ($System->TeamID>0){?>
      Controlling Team: <a href="team.php?id=<?php echo $System->TeamID; ?>"><?php echo h(TeamNameFromID($System->TeamID)); ?></a><br/>
	  <?php }?>
	  <?php
	  $_myPID = GetPlayerIDFromName($username);
	  if(CanRenameSystem($SystemID, $_myPID)):
	  ?>
	  <form method="POST" action="system.php" style="margin-top:8px;">
	    <input type="hidden" name="action" value="rename_system">
	    <input type="hidden" name="system_id" value="<?php echo $SystemID; ?>">
	    <?php echo csrf_token(); ?>
	    <input type="text" name="system_name" value="<?php echo h($System->Name); ?>" maxlength="50" style="width:160px;">
	    <input type="submit" value="Rename">
	  </form>
	  <?php if($System->DefaultName && $System->Name !== $System->DefaultName): ?>
	  <form method="POST" action="system.php" style="margin-top:4px; display:inline;" onsubmit="return confirm('Revert to default name: <?php echo h($System->DefaultName); ?>?');">
	    <input type="hidden" name="action" value="revert_system_name">
	    <input type="hidden" name="system_id" value="<?php echo $SystemID; ?>">
	    <?php echo csrf_token(); ?>
	    <input type="submit" value="Revert to <?php echo h($System->DefaultName); ?>">
	  </form>
	  <?php endif; ?>
	  <?php endif; ?>
	  </div>
    </div>
  

<div class="system"><img src="<?php echo GetSystemPictureFromID($SystemID); ?>"/></div>
</body>
</html>
<?php
} //Is Planet
?>
