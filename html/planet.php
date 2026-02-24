<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && csrf_validate()){
	if(($_POST['action'] ?? "")=="sethome"){
		$PlanetID = (int)($_POST["id"] ?? 0);
		if($PlanetID > 0){
			SetHomePlanet(GetPlayerIDFromName($username), $PlanetID);
		}
		AddAlertForCurrentUser('system', GetPlanetNameFromID($PlanetID).' set as home world', 'planet.php?id='.$PlanetID);
		SetFlash("Home world set");
		header("Location: planet.php?id=".$PlanetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="colonise"){
		$PlanetID = ($_POST["id"] ?? "");
		Colonise($PlanetID);
		header("Location: planet.php?id=".$PlanetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="rename_planet"){
		$PlanetID = (int)($_POST["id"] ?? 0);
		$_rName = $_POST['planet_name'] ?? '';
		$_rPid = GetPlayerIDFromName($username);
		$result = RenamePlanet($PlanetID, $_rName, $_rPid);
		if($result === true){
			SetFlash("Planet renamed");
			header("Location: planet.php?id=$PlanetID");
		} else {
			SetFlash($result);
			header("Location: planet.php?id=$PlanetID");
		}
		exit;
	}
	if(($_POST['action'] ?? "")=="revert_planet_name"){
		$PlanetID = (int)($_POST["id"] ?? 0);
		if(OwnsPlanet($username, $PlanetID)){
			RevertPlanetName($PlanetID);
			SetFlash("Name reverted to default");
			header("Location: planet.php?id=$PlanetID");
		} else {
			SetFlash("Cannot revert name");
			header("Location: planet.php?id=$PlanetID");
		}
		exit;
	}
}


if(!IsPlanet(($_GET['id'] ?? ""))){
	echo "Not a valid planet ID";
}else{
	$edit = false;
	$teamView = false;
	if(OwnsPlanet($username,($_GET['id'] ?? ""))){
		$edit = true;
	} else {
		// Check if planet owner is on same team
		$_viewPlanet = GetPlanet(($_GET['id'] ?? ""));
		if($_viewPlanet && $_viewPlanet->PlayerID > 0){
			$_viewerTeam = PlayerTeam(GetPlayerIDFromName($username));
			$_ownerTeam = PlayerTeam($_viewPlanet->PlayerID);
			if($_viewerTeam > 0 && $_viewerTeam == $_ownerTeam){
				$teamView = true;
			}
		}
	}
	$PlanetID = ($_GET['id'] ?? "");
	$Planet = GetPlanet($PlanetID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Planet: <?php echo h(GetPlanetNameFromID($PlanetID)); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
<?php if(isset($_GET['dorefresh'])){
?>
<meta http-equiv="refresh" content="0;URL=<?php $PHP_SELF;?>?id=<?php echo $PlanetID; ?>">
<?php
}?>
</head>

<body>
<?php
include("header.inc.php");
?>

<h2>Planet: <?php echo h(GetPlanetNameFromID($PlanetID)); ?>
<?php if($Planet->DefaultName && GetPlanetNameFromID($PlanetID) !== $Planet->DefaultName): ?>
  <small style="color:#888;">(originally <?php echo h($Planet->DefaultName); ?>)</small>
<?php endif; ?>
<?php if(($edit || $teamView) && IsHomePlanet($Planet->PlayerID, $PlanetID)): ?>
 <strong style="color:#FFFF00;">[Home World]</strong>
<?php endif; ?>
<?php if($teamView): ?>
 <small style="color:#00FF00;">[Allied — <?php echo h(GetPlayerNameFromID($Planet->PlayerID)); ?>]</small>
<?php endif; ?>
</h2>
<?php
$_topBoons = GetPlanetBoons($PlanetID);
if(!empty($_topBoons)):
  foreach($_topBoons as $_tb):
    $_tbCol = GetPlanetBoonColour($_tb);
?>
<p style="color:<?php echo $_tbCol; ?>; border:2px solid <?php echo $_tbCol; ?>; padding:4px 8px; display:inline-block; margin:0 6px 6px 0;">
  &#9733; <?php echo GetPlanetBoonName($_tb); ?> &mdash; <?php echo GetPlanetBoonDesc($_tb); ?>
</p>
<?php endforeach; endif; ?>
<div class="side"> 
  <div class="panel" style="width:250px;"> 
    <h3>Basic Stats</h3>
    <?php $_sys = GetSystem($Planet->System); ?>
    System: <a href="system.php?id=<?php echo $Planet->System; ?>"><?php echo h(GetSystemNameFromID($Planet->System)); ?></a><br/>
    Sector: <a href="sector.php?id=<?php echo $_sys->SectorID; ?>"><?php echo h(GetSectorName($_sys->SectorID)); ?></a><br/>
    Size: <?php echo $Planet->Size; ?><br/>
    Owner: <?php echo PlayerProfileLink($Planet->PlayerID); ?><br/>
    <?php if($edit): ?>
    <form method="POST" action="planet.php" style="margin-top:8px;">
      <input type="hidden" name="action" value="rename_planet">
      <input type="hidden" name="id" value="<?php echo $PlanetID; ?>">
      <?php echo csrf_token(); ?>
      <input type="text" name="planet_name" value="<?php echo h(GetPlanetNameFromID($PlanetID)); ?>" maxlength="50" style="width:160px;">
      <input type="submit" value="Rename">
    </form>
    <?php if($Planet->DefaultName && GetPlanetNameFromID($PlanetID) !== $Planet->DefaultName): ?>
    <form method="POST" action="planet.php" style="margin-top:4px; display:inline;" onsubmit="return confirm('Revert to default name: <?php echo h($Planet->DefaultName); ?>?');">
      <input type="hidden" name="action" value="revert_planet_name">
      <input type="hidden" name="id" value="<?php echo $PlanetID; ?>">
      <?php echo csrf_token(); ?>
      <input type="submit" value="Revert to <?php echo h($Planet->DefaultName); ?>">
    </form>
    <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php
if($edit || $teamView){?>
  <div class="panel" style="width:250px;"> 
    <h3>Owner's Stats</h3>
    Fleets in Orbit: <?php echo FleetsInOrbit($PlanetID); ?><br/>
    Unassigned Ships in Orbit: <?php echo GetShipsInOrbit($PlanetID); ?><br/>
    Orbital Spaces: 0<br/>
    Ground Spaces: <?php echo GridSquares($PlanetID); ?><br>
    Construction Queue: <span title="<?php echo htmlspecialchars(GetConstructionSlotsTooltip($PlanetID)); ?>" style="cursor:help; border-bottom:1px dotted #888;"><?php echo Constructions($PlanetID) . '/' . GetConstructionSlots($PlanetID); ?></span><br>
    <?php
	$_pid = $edit ? GetPlayerIDFromName($username) : (int)$Planet->PlayerID;
	$_isHome = IsHomePlanet($_pid, $PlanetID);
	// Base income from planet size
	$_baseQ = mysqli_query($GLOBALS["conn"], "SELECT income FROM planet_types WHERE Type = '".$Planet->Size."'");
	$_baseRow = mysqli_fetch_object($_baseQ);
	$_baseParts = $_baseRow ? explode(':', $_baseRow->income) : [0,0,0];
	$_baseMetal = (int)$_baseParts[0]; $_baseMineral = (int)$_baseParts[1]; $_baseAstrium = (int)$_baseParts[2];
	// Harvester count
	$_harvQ = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS cnt FROM buildings WHERE PlanetID = '$PlanetID' AND Type = 3");
	$_harvRow = mysqli_fetch_object($_harvQ);
	$_harvCount = $_harvRow ? (int)$_harvRow->cnt : 0;
	$_harvBonus = (float)GetGameSetting('harvester_bonus', 0.05);
	$_harvPct = $_harvCount * $_harvBonus;
	// Resource grid boon harvesters
	$_boonHarvQ = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS cnt FROM buildings b INNER JOIN planet_grid_boons pgb ON pgb.PlanetID = b.PlanetID AND pgb.Grid = b.GridSquare WHERE b.PlanetID='$PlanetID' AND b.Type=3 AND pgb.BoonType=1");
	$_boonHarvRow = mysqli_fetch_object($_boonHarvQ);
	$_boonHarvCount = $_boonHarvRow ? (int)$_boonHarvRow->cnt : 0;
	$_boonResBonus = (float)GetGameSetting('boon_resource_bonus', 0.10);
	$_boonPct = $_boonHarvCount * $_boonResBonus;
	// Resource Rich planet boon
	$_hasResourceRich = HasPlanetBoon($PlanetID, 1);
	$_rrBonus = $_hasResourceRich ? (float)GetGameSetting('pboon_resource_rich_bonus', 0.20) : 0;
	$_rrMult = 1 + $_rrBonus;
	// Home world multiplier
	$_homeMult = $_isHome ? (float)GetGameSetting('home_income_multiplier', 2) : 1;
	// Step-by-step income calculation (matches GetPlanetIncome order)
	// Step 1: Base
	$_s1M = $_baseMetal; $_s1N = $_baseMineral; $_s1A = $_baseAstrium;
	// Step 2: Resource Rich planet boon
	$_s2M = (int)round($_s1M * $_rrMult);
	$_s2N = (int)round($_s1N * $_rrMult);
	$_s2A = (int)round($_s1A * $_rrMult);
	// Step 3: Harvesters + Grid Resource Boons (additive percentages)
	$_combPct = 1 + $_harvPct + $_boonPct;
	$_s3M = (int)round($_s2M * $_combPct);
	$_s3N = (int)round($_s2N * $_combPct);
	$_s3A = (int)round($_s2A * $_combPct);
	// Step 4: Home World multiplier
	$_finalMetal = (int)round($_s3M * $_homeMult);
	$_finalMineral = (int)round($_s3N * $_homeMult);
	$_finalAstrium = (int)round($_s3A * $_homeMult);
	?>
    <strong>Income (per turn):</strong><br/>
    <small style="color:#888;">Base (Size <?php echo $Planet->Size; ?>): <?php echo $_s1M; ?>m / <?php echo $_s1N; ?>n / <?php echo $_s1A; ?>a</small><br/>
    <?php if($_hasResourceRich): ?>
    <small style="color:#FF9900;">&#9733; Resource Rich: +<?php echo round($_rrBonus * 100); ?>% &rarr; <?php echo $_s2M; ?>m / <?php echo $_s2N; ?>n / <?php echo $_s2A; ?>a</small><br/>
    <?php endif; ?>
    <?php if($_harvCount > 0): ?>
    <small style="color:#00FF00;">Harvesters (&times;<?php echo $_harvCount; ?>): +<?php echo round($_harvPct * 100); ?>%</small><br/>
    <?php endif; ?>
    <?php if($_boonHarvCount > 0): ?>
    <small style="color:#32FF32;">&#9632; Grid Resource Boons (&times;<?php echo $_boonHarvCount; ?>): +<?php echo round($_boonPct * 100); ?>%</small><br/>
    <?php endif; ?>
    <?php if($_harvCount > 0 || $_boonHarvCount > 0): ?>
    <small style="color:#888;">&rarr; <?php echo $_s3M; ?>m / <?php echo $_s3N; ?>n / <?php echo $_s3A; ?>a</small><br/>
    <?php endif; ?>
    <?php if($_isHome): ?>
    <small style="color:#FFFF00;">Home World: &times;<?php echo $_homeMult; ?> &rarr; <?php echo $_finalMetal; ?>m / <?php echo $_finalMineral; ?>n / <?php echo $_finalAstrium; ?>a</small><br/>
    <?php endif; ?>
    <strong><?php echo $_finalMetal; ?></strong> Metal / <strong><?php echo $_finalMineral; ?></strong> Mineral / <strong><?php echo $_finalAstrium; ?></strong> Astrium<br/>
    <p>Defence HP: <?php echo GetPlanetDefenceStrength($PlanetID); ?>
      <?php if(IsHomePlanet(GetPlayerIDFromName($username), $PlanetID)): ?><strong style="color:#FFFF00;">(+50% HP)</strong><?php endif; ?><br>
      Attack HP: <?php echo GetPlanetAttackStrength($PlanetID); ?></p>
    <p>Planet Value: <span title="<?php echo htmlspecialchars(PlanetValueTooltip($PlanetID)); ?>" style="cursor:help; border-bottom:1px dotted #888;"><strong><?php echo number_format(GetPlanetValue($PlanetID)); ?>C</strong></span></p>
    <?php if($edit && !IsHomePlanet(GetPlayerIDFromName($username), $PlanetID)): ?>
    <form method="POST" action="planet.php" onsubmit="return confirm('Set this planet as your home world?');">
    <input type="hidden" name="action" value="sethome">
    <input type="hidden" name="id" value="<?php echo $PlanetID; ?>">
    <?php echo csrf_token(); ?>
    <input type="submit" value="Set as Home World">
    </form>
    <?php endif; ?>
    <?php
    // Auction Planet button: owner only, in a team, not already auctioned, not on cooldown
    if($edit){
      $_auctionPid = GetPlayerIDFromName($username);
      $_auctionTeam = (int)PlayerTeam($_auctionPid);
      $_isHomePlanet = IsHomePlanet($_auctionPid, $PlanetID);
      $_alreadyAuctioned = false;
      $_achk = mysqli_query($GLOBALS["conn"], "SELECT AuctionID FROM auctions WHERE Code='3' AND Data='".(int)$PlanetID."'");
      if($_achk && mysqli_num_rows($_achk) > 0) $_alreadyAuctioned = true;
      $_onCooldown = HasAuctionCooldown(3, $PlanetID);
      if($_auctionTeam > 0 && !$_alreadyAuctioned && !$_onCooldown):
    ?>
    <form method="GET" action="trade.php" style="margin-top:8px;">
      <input type="hidden" name="auction_planet" value="<?php echo $PlanetID; ?>">
      <?php if($_isHomePlanet): ?>
      <input type="submit" value="&#9888; Auction Home World" style="color:#FF0000;" onclick="return confirm('WARNING: This is your Home World! If sold, you must select a new home world. Proceed to auction page?');">
      <?php else: ?>
      <input type="submit" value="Auction Planet">
      <?php endif; ?>
    </form>
    <?php
      elseif($_alreadyAuctioned): ?>
      <p style="color:#FF9900;"><small>This planet is currently on auction.</small></p>
    <?php elseif($_onCooldown): ?>
      <p style="color:#FF9900;"><small>Auction cooldown active (24h after cancellation).</small></p>
    <?php elseif($_auctionTeam < 1): ?>
      <p style="color:#888;"><small>Join a team to auction planets.</small></p>
    <?php endif;
    } // $edit auction button
    ?>
  </div>
  <?php
  // Building counts by type for this planet
  $_bcRes = mysqli_query($GLOBALS["conn"], "SELECT Type, COUNT(*) AS cnt FROM buildings WHERE PlanetID = '$PlanetID' GROUP BY Type");
  $_bCounts = [];
  while($_bcRow = mysqli_fetch_object($_bcRes)) $_bCounts[(int)$_bcRow->Type] = (int)$_bcRow->cnt;
  ?>
  <div class="panel" style="width:250px"> 
    <h3>Key</h3>
    <p style="margin:2px 0; border-left:3px solid #FF0000; padding-left:6px;"><font color="#FF0000">Factory</font><?php if(!empty($_bCounts[1])) echo ' ('.$_bCounts[1].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #0066FF; padding-left:6px;"><font color="#0066FF">Laboratory</font><?php if(!empty($_bCounts[2])) echo ' ('.$_bCounts[2].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #00FF00; padding-left:6px;"><font color="#00FF00">Harvester</font><?php if(!empty($_bCounts[3])) echo ' ('.$_bCounts[3].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #FF9900; padding-left:6px;"><font color="#FF9900">Shipyard</font><?php if(!empty($_bCounts[4])) echo ' ('.$_bCounts[4].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #FFFFFF; padding-left:6px;">Hangar<?php if(!empty($_bCounts[5])) echo ' ('.$_bCounts[5].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #FFFF00; padding-left:6px;"><font color="#FFFF00">Shield</font><?php if(!empty($_bCounts[6])) echo ' ('.$_bCounts[6].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #CC00FF; padding-left:6px;"><font color="#CC00FF">Pulse Cannon</font><?php if(!empty($_bCounts[7])) echo ' ('.$_bCounts[7].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #FF99FF; padding-left:6px;"><font color="#FF99FF">Gigashield</font><?php if(!empty($_bCounts[8])) echo ' ('.$_bCounts[8].')'; ?></p>
    <p style="margin:2px 0; border-left:3px solid #999999; padding-left:6px;"><font color="#999999">Missile Silo</font><?php if(!empty($_bCounts[9])) echo ' ('.$_bCounts[9].')'; ?></p>
    <?php
    $_planetBoons = GetPlanetGridBoons($PlanetID);
    if(!empty($_planetBoons)):
      $_boonCounts = array_count_values($_planetBoons);
    ?>
    <h3>Grid Boons</h3>
      <?php if(!empty($_boonCounts[1])): ?><p style="margin:2px 0; border-left:3px solid #32FF32; padding-left:6px;"><font color="#32FF32">&#9632; Resource</font> (<?php echo $_boonCounts[1]; ?>)</p><?php endif; ?>
      <?php if(!empty($_boonCounts[2])): ?><p style="margin:2px 0; border-left:3px solid #3264FF; padding-left:6px;"><font color="#3264FF">&#9632; Research</font> (<?php echo $_boonCounts[2]; ?>)</p><?php endif; ?>
      <?php if(!empty($_boonCounts[3])): ?><p style="margin:2px 0; border-left:3px solid #FFFF32; padding-left:6px;"><font color="#FFFF32">&#9632; Energy</font> (<?php echo $_boonCounts[3]; ?>)</p><?php endif; ?>
      <p><small style="color:#888;">Thick coloured borders on grid</small></p>
    <?php endif; ?>
    <?php
    $_pBoons = GetPlanetBoons($PlanetID);
    if(!empty($_pBoons)):
    ?>
    <h3>Planet Boons</h3>
      <?php foreach($_pBoons as $_pb): ?>
      <p style="margin:2px 0; border-left:3px solid <?php echo GetPlanetBoonColour($_pb); ?>; padding-left:6px;">
      <font color="<?php echo GetPlanetBoonColour($_pb); ?>">&#9733; <?php echo GetPlanetBoonName($_pb); ?></font><br>
      <small style="color:#888;"><?php echo GetPlanetBoonDesc($_pb); ?></small>
      </p>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <?php } // Edit or TeamView — end left sidebar ?>
</div>
<?php
// === Planet Map (center) ===
$squares = 0; $startcorner = 0; $numberinrow = 0;
switch($Planet->Size){
	case "1": $squares = 100; $startcornerx = 100; $startcornery = 50; $numberinrow = 10; break;
	case "2": $squares = 121; $startcornerx = 75; $startcornery = 75; $numberinrow = 11; break;
	case "3": $squares = 81; $startcornerx = 130; $startcornery = 85; $numberinrow = 9; break;
	case "4": $squares = 144; $startcornerx = 75; $startcornery = 25; $numberinrow = 12; break;
}
?>
<div class="planet"><img src="<?php echo GetPlanetPictureFromID($PlanetID); ?>" name="image1" border="0" usemap="#Map" id="image1"/>
<map name="Map">
<?php
if($edit || $teamView){
	$secid = 1;
	for($i = 0;$i<$numberinrow;$i++){
		for($j = 0;$j<$numberinrow;$j++){
?>
  <area shape="rect" coords="<?php echo $startcornerx+$j*40; ?>,<?php echo $startcornery+$i*40; ?>,<?php echo $startcornerx+($j*40)+40; ?>,<?php echo $startcornery+($i*40)+40; ?>" id="s<?php echo $secid; ?>" href="building.php?id=<?php echo $secid; ?>&planet=<?php echo $PlanetID; ?>" target="_self"/>
<?php
			$secid++;
		}
	}
}
?>
</map>
</div>
<div class="side">
  <?php if($edit || $teamView){ ?>
  <?php
  $cres = mysqli_query($GLOBALS["conn"], "SELECT Grid, Type, TTF FROM cbuildings WHERE PlanetID = '$PlanetID' ORDER BY Grid ASC");
  if($cres && mysqli_num_rows($cres) > 0){ ?>
  <div class="panel" style="width:250px;">
    <h3>Under Construction</h3>
    <?php while($crow = mysqli_fetch_object($cres)){ ?>
    <p><a href="building.php?planet=<?php echo $PlanetID; ?>&id=<?php echo $crow->Grid; ?>">Grid <?php echo $crow->Grid; ?>: <?php echo h(GetGridContentString($crow->Type)); ?></a><br/>
      <small>Time left: <?php echo $crow->TTF; ?> min</small></p>
    <?php } ?>
  </div>
  <?php } ?>
  <?php
  // Intelligence: fleets en route to this planet
  $_inboundQ = mysqli_query($GLOBALS['conn'],
    "SELECT f.FleetID, f.Name, f.PlayerID, f.Strategy, f.TTF,
     (SELECT COUNT(*) FROM ships sh WHERE sh.FleetID = f.FleetID) AS ShipCount
     FROM fleets f WHERE f.Destination = 'P:$PlanetID' AND f.Location = ''");
  $_inboundFleets = [];
  if($_inboundQ){ while($_ibf = mysqli_fetch_object($_inboundQ)) $_inboundFleets[] = $_ibf; }
  if(!empty($_inboundFleets)):
  ?>
  <div class="panel" style="width:250px;">
    <h3>&#9888; Intelligence</h3>
    <p><small style="color:#888;"><?php echo count($_inboundFleets); ?> fleet<?php echo count($_inboundFleets) != 1 ? 's' : ''; ?> en route</small></p>
    <?php
    $_stratNames = ['0'=>'Orbit','1'=>'Colonise','2'=>'Attack','3'=>'Invade'];
    $_stratColours = ['0'=>'#FFFFFF','1'=>'#00FF00','2'=>'#FF0000','3'=>'#FF0000'];
    foreach($_inboundFleets as $_ibf):
      $_ibPid = (int)$_ibf->PlayerID;
      $_ibTeamID = (int)PlayerTeam($_ibPid);
      $_ibStrat = (string)$_ibf->Strategy;
      $_ibStratName = $_stratNames[$_ibStrat] ?? 'Unknown';
      $_ibStratCol = $_stratColours[$_ibStrat] ?? '#FFFFFF';
      $_ibName = $_ibf->Name ?: 'Fleet '.$_ibf->FleetID;
    ?>
    <p style="margin:4px 0; border-left:3px solid <?php echo $_ibStratCol; ?>; padding-left:6px;">
      <a href="fleet.php?id=<?php echo $_ibf->FleetID; ?>"><?php echo h($_ibName); ?></a>
      <small style="color:<?php echo $_ibStratCol; ?>;">[<?php echo $_ibStratName; ?>]</small><br/>
      <small>Owner: <?php echo PlayerProfileLink($_ibPid); ?><?php if($_ibTeamID > 0): ?> — <a href="team.php?id=<?php echo $_ibTeamID; ?>"><?php echo h(TeamNameFromID($_ibTeamID)); ?></a><?php endif; ?></small><br/>
      <small style="color:#888;">Ships: <?php echo (int)$_ibf->ShipCount; ?> | HP: <?php echo number_format(FleetHP($_ibf->FleetID)); ?> | AP: <?php echo number_format(FleetAP($_ibf->FleetID)); ?></small><br/>
      <small style="color:#888;">ETA: <?php echo $_ibf->TTF; ?> min</small>
    </p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <?php } // Edit or TeamView ?>
  <div class="panel" style="width:250px;">
    <h3>System: <?php echo h(GetSystemNameFromID($Planet->System)); ?></h3>
    <?php
    $_sysPlanets = ListPlanetsInSystem($Planet->System);
    foreach($_sysPlanets as $_sp):
      $_isCurrent = ((int)$_sp->PlanetID == (int)$PlanetID);
      $_spOwner = (int)$_sp->PlayerID;
      // Size labels: 1=Small, 2=Medium, 3=Large, 4=Huge
      $_szLabel = ['1'=>'S','2'=>'M','3'=>'L','4'=>'H'][$_sp->Size] ?? '?';
    ?>
    <p style="margin:2px 0;<?php if($_isCurrent) echo 'font-weight:bold;'; ?>">
      <?php if($_isCurrent): ?>
        &#9658; <?php echo h($_sp->Name); ?> <small style="color:#888;">(<?php echo $_szLabel; ?>)</small>
      <?php else: ?>
        <a href="planet.php?id=<?php echo $_sp->PlanetID; ?>"><?php echo h($_sp->Name); ?></a> <small style="color:#888;">(<?php echo $_szLabel; ?>)</small>
      <?php endif; ?>
      <?php if($_spOwner > 0): ?>
        <small>— <?php echo h(GetPlayerNameFromID($_spOwner)); ?></small>
      <?php else: ?>
        <small style="color:#666;">— Uncolonised</small>
      <?php endif; ?>
    </p>
    <?php endforeach; ?>
  </div>
  <?php
if(YourFleetsInOrbit($PlanetID)>0){
?>
  <div class="panel" style="width:250px;"> 
    <h3>Fleets in Orbit</h3>
    <ul>
      <?php
  $Fleets = ListYourFleetsInOrbit($PlanetID);
  foreach($Fleets as $k=>$Fleet){
  ?>
      <li> <a href="fleet.php?id=<?php echo $Fleet->FleetID; ?>"><?php echo h(GetFleetName($Fleet->FleetID)); ?></a><br/>
        Actions:<br/>
        <?php if(CanColonise($PlanetID,$Fleet->FleetID)){ ?>
        <form method="post" action="planet.php" style="display:inline;">
          <input type="hidden" name="action" value="colonise">
          <input type="hidden" name="id" value="<?php echo $PlanetID; ?>">
          <input type="hidden" name="fleet" value="<?php echo $Fleet->FleetID; ?>">
          <?php echo csrf_token(); ?>
          <input type="submit" value="Colonise" onclick="return confirm('Colonise this planet? This will consume a colony ship.');">
        </form><br/>
        <?php } // Can Colonise?>
      </li>
      <?php } //Fleet Foreach ?>
    </ul>
  </div>
  <?php } // Fleets in orbit ?>
  <?php
	$Fleets = ListPlayerFleets(GetPlayerIDFromName($username));
	if(sizeof($Fleets)>0){
  ?>
  <div class="panel" style="width:250px;"> 
    <h3>Fleet Functions</h3>
    Send Fleet: 
    <form name="form1" action="fleet.php" method="post">
      <input type="hidden" name="action" value="move">
      <?php echo csrf_token(); ?>
      <select name="fleet">
        <?php
	foreach($Fleets as $k=>$Fleet){
	?>
        <option value="<?php echo $Fleet->FleetID ?>" selected><?php echo h(GetFleetName($Fleet->FleetID)); ?></option>
        <?php } //fleet foreach ?>
      </select>
      <select name="strat" id="strat">
        <?php
        $_pOwner = (int)$Planet->PlayerID;
        $_isEnemyPlanet = EnemyOwned($PlanetID);
        if(!$_isEnemyPlanet): // Own, team, or uncolonised
        ?>
        <option value="0">Orbit</option>
        <?php endif; ?>
        <?php
        // Colonise: only on uncolonised planets, and only if at least one selected fleet has a colony ship
        if($_pOwner == 0):
          $_anyColoniser = false;
          foreach($Fleets as $_cfk => $_cfl){
            $csQ = mysqli_query($GLOBALS['conn'], "SELECT 1 FROM ships WHERE FleetID='".(int)$_cfl->FleetID."' AND Type=3 LIMIT 1");
            if($csQ && mysqli_num_rows($csQ) > 0){ $_anyColoniser = true; break; }
          }
          if($_anyColoniser):
        ?>
        <option value="1">Colonise</option>
        <?php endif; endif; ?>
        <?php if($_isEnemyPlanet): // Enemy only ?>
        <option value="2">Attack</option>
        <option value="3">Invade</option>
        <?php endif; ?>
      </select>
      <input type="hidden" name="type" value="P">
      <input type="hidden" name="value" value="<?php echo $PlanetID; ?>">
      <input type="submit" value="Launch" onclick="var s=document.getElementById('strat');if(s&&s.value>=2)return confirm('Launch fleet with '+(s.value==2?'Attack':'Invade')+' strategy?');return true;">
    </form>
  </div>
  <?php } //Has Fleets ?>
</div>
</body>
</html>
<?php
} //Is Planet
?>
