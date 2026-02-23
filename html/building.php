<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && csrf_validate()){
	if(($_POST['action'] ?? "")=="build"){
		$PlanetID = ($_POST["planet"] ?? "");
		$ret = 0;
		if(OwnsPlanet($username,$PlanetID)){
			$BuildingType = ($_POST["building_type"] ?? "");
			$Grid = ($_POST["grid"] ?? "");
			$ret = Build($PlanetID,$BuildingType,$Grid);
		}
		if($ret > 0){
			AddAlertForCurrentUser('construction', 'Construction of a '.GetGridContentString($BuildingType).' started on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}elseif($ret == -1){
			AddAlertForCurrentUser('system', 'Construction queue full on '.GetPlanetNameFromID($PlanetID).'. Build more Factories for extra slots.', 'planet.php?id='.$PlanetID);
			SetFlash("Construction queue full");
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}else{
			AddAlertForCurrentUser('system', 'Insufficient resources to build on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
			SetFlash("Insufficient Resources");
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}
	}
	if(($_POST['action'] ?? "")=="repair"){
		$PlanetID = ($_POST["planet"] ?? "");
		$Grid = ($_POST["grid"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$pid = GetPlayerIDFromName($username);
			if(Repair(GetBldIDFromGrid($PlanetID,$Grid), $pid)){
				AddAlertForCurrentUser('construction', GetGridContentString(GetGridContents($PlanetID,$Grid)).' repaired on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
				SetFlash("Building Repaired");
				header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
			}else{
				SetFlash("Insufficient Resources");
				header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
			}
		}else{
			SetFlash("You don't own this planet");
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}
	}
	if(($_POST['action'] ?? "")=="demolish"){
		$PlanetID = ($_POST["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Grid = ($_POST["grid"] ?? "");
			$_demName = GetGridContentString(GetGridContents($PlanetID,$Grid));
			$sql = "DELETE FROM buildings WHERE(GridSquare = '$Grid' AND PlanetID = '$PlanetID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			AddAlertForCurrentUser('construction', $_demName.' demolished on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
		}
		SetFlash("Building Demolished");
		header("Location: planet.php?id=".$PlanetID);
	}
	if(($_POST['action'] ?? "")=="cancel"){
		$PlanetID = ($_POST["planet"] ?? "");
		$cancelMsg = "Building+Cancelled";
		if(OwnsPlanet($username,$PlanetID)){
			$Grid = ($_POST["grid"] ?? "");
			// Check construction still exists (may have completed between page load and click)
			if(!ConstructingBuilding($PlanetID,$Grid)){
			SetFlash("Construction already completed");
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
				exit;
			}
			// Fetch construction record before deleting
			$sql = "SELECT Type, TTF, PlayerID FROM cbuildings WHERE(Grid = '$Grid' AND PlanetID = '$PlanetID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
			$cbld = mysqli_fetch_object($res);
			if($cbld){
				// Get original build costs
				$sql = "SELECT Metal, Mineral, Astrium, Turns FROM building_types WHERE(Type = '{$cbld->Type}')";
				$res = mysqli_query($GLOBALS["conn"], $sql);
				$costs = mysqli_fetch_object($res);
				if($costs){
					$totalTime = CalculateBuildTime($PlanetID, $cbld->Type);
					if($totalTime < $cbld->TTF) $totalTime = $cbld->TTF;
					$refundRate = ($totalTime > 0) ? $cbld->TTF / $totalTime : 0;
					$refundMetal = round($costs->Metal * $refundRate);
					$refundMineral = round($costs->Mineral * $refundRate);
					$refundAstrium = round($costs->Astrium * $refundRate);
					if($refundMetal > 0) AddResources($cbld->PlayerID, 1, $refundMetal);
					if($refundMineral > 0) AddResources($cbld->PlayerID, 2, $refundMineral);
					if($refundAstrium > 0) AddResources($cbld->PlayerID, 3, $refundAstrium);
					$cancelMsg = "Building+Cancelled.+Refund:+{$refundMetal}+Metal,+{$refundMineral}+Mineral,+{$refundAstrium}+Astrium";
				}
				$sql = "DELETE FROM cbuildings WHERE(Grid = '$Grid' AND PlanetID = '$PlanetID')";
				$res = mysqli_query($GLOBALS["conn"], $sql);
			}
		}
		$_cancelBldName = ($cbld ? GetGridContentString($cbld->Type) : 'Building');
		AddAlertForCurrentUser('construction', $_cancelBldName.' cancelled on '.GetPlanetNameFromID($PlanetID).($cancelMsg !== 'Building+Cancelled' ? '. '.str_replace('+', ' ', substr($cancelMsg, strpos($cancelMsg, 'Refund'))) : ''), 'planet.php?id='.$PlanetID);
		SetFlash(str_replace('+', ' ', $cancelMsg));
		header("Location: planet.php?id=".$PlanetID);
	}
	if(($_POST['action'] ?? "")=="consship"){
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
			AddAlertForCurrentUser('construction', GetShipTypeString($Type).' construction started on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}else{
			AddAlertForCurrentUser('system', 'Insufficient resources to build ship on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
			SetFlash("Insufficient Resources");
			header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
		}
		
	}
	if(($_POST['action'] ?? "")=="addtoqueue"){
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
				AddAlertForCurrentUser('system', 'Ship queue is full on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
				SetFlash("Queue is full");
				header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
			}else{
				AddToQueue($PlanetID,$Grid,$Type,$Name);
				AddAlertForCurrentUser('construction', $Name.' added to build queue on '.GetPlanetNameFromID($PlanetID), 'planet.php?id='.$PlanetID);
				header("Location: building.php?planet=".$PlanetID."&id=".$Grid);
			}
		}
	}
	if(($_POST['action'] ?? "")=="createfleet"){
		$PlanetID = ($_POST["planet"] ?? "");
		if(OwnsPlanet($username,$PlanetID)){
			$Name = GetDefaultFleetName(GetPlayerIDFromName($username));
			CreateFleet($PlanetID,$Name,true);
		}
		header("Location: planet.php?dorefresh=1&id=".$PlanetID);
	}
	if(($_POST['action'] ?? "")=="addtofleet"){
		$PlanetID = ($_POST["planet"] ?? "");
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
	$edit = false;
	$teamView = false;
	if(OwnsPlanet($username,($_GET['planet'] ?? ""))){
		$edit = true;
	} else {
		$_bvPlanet = GetPlanet(($_GET['planet'] ?? ""));
		if($_bvPlanet && $_bvPlanet->PlayerID > 0){
			$_bvViewerTeam = PlayerTeam(GetPlayerIDFromName($username));
			$_bvOwnerTeam = PlayerTeam($_bvPlanet->PlayerID);
			if($_bvViewerTeam > 0 && $_bvViewerTeam == $_bvOwnerTeam){
				$teamView = true;
			}
		}
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
<title><?php echo h(GetPlanetNameFromID($PlanetID)); ?> - Grid: <?php echo $Grid; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php");?>
<p>Return to: <a href="planet.php?id=<?php echo $PlanetID; ?>"><?php echo h(GetPlanetNameFromID($PlanetID)); ?></a></p>
<h2>Grid: <?php echo $Grid; ?> </h2>
<?php if(ConstructingBuilding($PlanetID,$Grid))
{
$bldg = BuildingUnderConstruction($PlanetID,$Grid);
?>
<div class="panel" style="width:400px;">
  <p>Constructing: <?php echo h(GetGridContentString($bldg["Type"])); ?><br>
    Time Left: <?php echo $bldg["TTF"]; ?> minutes </p>
  <?php
  $cq = "SELECT Metal, Mineral, Astrium, Turns FROM building_types WHERE(Type = '".$bldg["Type"]."')";
  $cr = mysqli_query($GLOBALS["conn"], $cq);
  $ccosts = mysqli_fetch_object($cr);
  $refundStr = "unknown";
  if($ccosts){
    $prevTotal = CalculateBuildTime($PlanetID, $bldg["Type"]);
    if($prevTotal < $bldg["TTF"]) $prevTotal = $bldg["TTF"];
    $prevRate = ($prevTotal > 0) ? $bldg["TTF"] / $prevTotal : 0;
    $refundStr = round($ccosts->Metal * $prevRate) . " Metal, " . round($ccosts->Mineral * $prevRate) . " Mineral, " . round($ccosts->Astrium * $prevRate) . " Astrium";
  }
  ?>
  <?php if($edit): ?>
  <p><small>Refund if cancelled: <?php echo $refundStr; ?></small></p>
  <p><form method="post" action="building.php" style="display:inline;">
    <input type="hidden" name="action" value="cancel">
    <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
    <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
    <?php echo csrf_token(); ?>
    <input type="submit" value="Cancel" onclick="return confirm('Cancel construction? Refund: <?php echo $refundStr; ?>');">
  </form></p>
  <?php endif; ?>
</div>
<?php }else{ ?>
<p>This grid contains: <?php echo h(GetGridContentString($GridContents)); ?></p>
<?php if(($GridContents>0)&&($edit || $teamView)){?>
<p>HP: <?php echo $hp."/".$default_hp; ?>
<?php
if($edit){
$_repairCost = GetRepairCost($PlanetID, $Grid);
if($_repairCost):
  $_costStr = $_repairCost['Metal'].' Metal, '.$_repairCost['Mineral'].' Mineral';
  if($_repairCost['Astrium'] > 0) $_costStr .= ', '.$_repairCost['Astrium'].' Astrium';
?>
  [<form method="post" action="building.php" style="display:inline;">
    <input type="hidden" name="action" value="repair">
    <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
    <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
    <?php echo csrf_token(); ?>
    <input type="submit" value="Repair" onclick="return confirm('Repair for <?php echo $_costStr; ?>?');">
  </form> Cost: <?php echo $_costStr; ?>]
<?php endif; ?>
</p>
<p><form method="post" action="building.php" style="display:inline;">
    <input type="hidden" name="action" value="demolish">
    <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
    <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
    <?php echo csrf_token(); ?>
    <input type="submit" value="Demolish" onclick="return confirm('Demolish this building?');">
  </form></p>
<p><?php PrintGridFunctions($PlanetID,$Grid); ?></p>
<?php } else { ?>
</p>
<?php } ?>
<?php } ?>
<?php if((!$GridContents>0)&&($edit)){?>
<?php /* Team viewers see empty grid but no build options */ ?>
<?php
	$_qUsed = Constructions($PlanetID);
	$_qMax = GetConstructionSlots($PlanetID);
	$_qFree = $_qMax - $_qUsed;
?>
<h3>Build on this grid:</h3>
<p>Construction Queue: <?php echo $_qUsed . '/' . $_qMax; ?>
<?php if($_qFree > 0): ?>
  <small style="color:#00FF00;">(<?php echo $_qFree; ?> slot<?php echo $_qFree != 1 ? 's' : ''; ?> free)</small>
<?php else: ?>
  <small style="color:#FF0000;">(full — build more Factories for extra slots)</small>
<?php endif; ?>
</p>
<?php if($_qFree <= 0): ?>
<p style="color:#FF0000;">All construction slots are in use. Build Factories to increase capacity.</p>
<?php else: ?>
<?php
	$descriptions = array(
		1 => 'Reduces construction time by 5% (stacks) and adds +1 construction queue slot per factory.',
		2 => 'Research facility. No gameplay effect yet.',
		3 => 'Increases all resource income on this planet by 5% (stacks).',
		4 => 'Allows construction of ships on this planet.',
		5 => 'Provides docking space for ships and fleets.',
		6 => 'Absorbs damage from orbital attacks. Must be destroyed before weapons can be hit.',
		7 => 'Fires on attacking fleets. 1-in-' . (int)GetGameSetting('planet_weapon_hit_chance', 3) . ' chance to fire each round.',
		8 => 'Heavy shield — absorbs large amounts of damage from orbital attacks.',
		9 => 'Heavy weapon — high firepower against attacking fleets.',
	);
	$sql = "SELECT * FROM building_types ORDER BY Type ASC";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	while($row = mysqli_fetch_object($res)){
		$cost = $row->Metal . " Metal, " . $row->Mineral . " Mineral";
		if($row->Astrium > 0) $cost .= ", " . $row->Astrium . " Astrium";
		$stats = "HP: " . $row->HP;
		if($row->AP > 0) $stats .= " | AP: " . $row->AP;
		$desc = $descriptions[$row->Type] ?? '';
?>
<div class="panel" style="width:500px; margin-bottom:6px; padding:6px 10px;">
  <form method="post" action="building.php" style="display:inline;">
    <input type="hidden" name="action" value="build">
    <input type="hidden" name="planet" value="<?php echo $PlanetID; ?>">
    <input type="hidden" name="grid" value="<?php echo $Grid; ?>">
    <input type="hidden" name="building_type" value="<?php echo $row->Type; ?>">
    <?php echo csrf_token(); ?>
    <p style="margin:2px 0;"><button type="submit" onclick="return confirm('Build <?php echo h($row->Name); ?> for <?php echo $cost; ?>?');" style="background:none;border:none;color:inherit;cursor:pointer;padding:0;font:inherit;text-decoration:underline;"><strong><?php echo h($row->Name); ?></strong></button>
    — <?php echo $cost; ?> — <?php echo $row->Turns; ?> turn(s)<br/>
    <small><?php echo $stats; ?><?php if($desc){ echo ' — ' . $desc; } ?></small></p>
  </form>
</div>
<?php } ?>
<?php endif; /* queue has free slots */ ?>
<?php } ?></body>
</html>
<?php } ?>
<?php } // Construction else ?>
