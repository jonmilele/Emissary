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
    Construction Queue: <?php echo Constructions($PlanetID) . '/' . GetConstructionSlots($PlanetID); ?><br>
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
	// Home world multiplier
	$_homeMult = $_isHome ? (float)GetGameSetting('home_income_multiplier', 2) : 1;
	// Final income
	$_finalMetal = (int)round($_baseMetal * (1 + $_harvPct) * $_homeMult);
	$_finalMineral = (int)round($_baseMineral * (1 + $_harvPct) * $_homeMult);
	$_finalAstrium = (int)round($_baseAstrium * (1 + $_harvPct) * $_homeMult);
	?>
    <strong>Income (per turn):</strong><br/>
    <small style="color:#888;">Base (Size <?php echo $Planet->Size; ?>): <?php echo $_baseMetal; ?>m / <?php echo $_baseMineral; ?>n / <?php echo $_baseAstrium; ?>a</small><br/>
    <?php if($_isHome): ?>
    <small style="color:#FFFF00;">Home World: x<?php echo $_homeMult; ?> (<?php echo (int)($_baseMetal*$_homeMult); ?>m / <?php echo (int)($_baseMineral*$_homeMult); ?>n / <?php echo (int)($_baseAstrium*$_homeMult); ?>a)</small><br/>
    <?php endif; ?>
    <?php if($_harvCount > 0): ?>
    <small style="color:#00FF00;">Harvesters (x<?php echo $_harvCount; ?>): +<?php echo round($_harvPct * 100); ?>%</small><br/>
    <?php endif; ?>
    - <?php echo $_finalMetal; ?> Metal<br/>
    - <?php echo $_finalMineral; ?> Mineral<br/>
    - <?php echo $_finalAstrium; ?> Astrium<br/>
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
  <div class="panel" style="width:250px"> 
    <h3>Key</h3>
    <p> <font color="#FF0000">Factory</font><br>
      <font color="#0066FF">Laboratory</font><br>
      <font color="#00FF00">Harvester</font><br>
      <font color="#FF9900">Shipyard</font><br>
      Hangar<br>
      <font color="#FFFF00">Shield</font><br>
      <font color="#CC00FF">Pulse Cannon</font><br>
      <font color="#FF99FF">Gigashield</font> <br>
      <font color="#999999">Missile Silo</font></p>
  </div>
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
  <?php } // Edit or TeamView?>
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
          <button type="submit" onclick="return confirm('Colonise this planet? This will consume a colony ship.');">Colonise</button>
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
        <option value="0">Orbit</option>
        <option value="1">Colonise</option>
        <option value="2">Attack</option>
        <option value="3">Invade</option>
      </select>
      <input type="hidden" name="type" value="P">
      <input type="hidden" name="value" value="<?php echo $PlanetID; ?>">
      <input type="submit" value="Launch" onclick="var s=document.getElementById('strat');if(s&&s.value>=2)return confirm('Launch fleet with '+(s.value==2?'Attack':'Invade')+' strategy?');return true;">
    </form>
  </div>
  <?php } //Has Fleets ?>
</div>
<div class="planet"><img src="<?php echo GetPlanetPictureFromID($PlanetID); ?>" name="image1" border="0" usemap="#Map" id="image1"/> 
</div>
<map name="Map">
  <?php
$squares = 0;
$startcorner = 0;
$numberinrow = 0;

// Planet sizes: 1=Small (10x10), 2=Medium (11x11), 3=Large (9x9), 4=Huge (12x12)
switch($Planet->Size){
	case "1": // Small — 100 grid squares
		$squares = 100;
		$startcornerx = 100;
		$startcornery = 50;
		$numberinrow = 10;
		break;
	case "2": // Medium — 121 grid squares
		$squares = 121;
		$startcornerx = 75;
		$startcornery = 75;
		$numberinrow = 11;
		break;
	case "3": // Large — 81 grid squares
		$squares = 81;
		$startcornerx = 130;
		$startcornery = 85;
		$numberinrow = 9;
		break;
	case "4": // Huge — 144 grid squares
		$squares = 144;
		$startcornerx = 75;
		$startcornery = 25;
		$numberinrow = 12;
		break;
}
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
	//$grids = GetOrbitalGridCoords($Planet->Size);

}
?>
</map>
</body>
</html>
<?php
} //Is Planet
?>
