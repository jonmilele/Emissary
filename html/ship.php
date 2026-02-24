<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && csrf_validate()){
	$ShipID = (int)($_POST['ship_id'] ?? 0);
	if($ShipID > 0){
		$_ship = GetShip($ShipID);
		if(!$_ship || (int)$_ship->PlayerID != GetPlayerIDFromName($username)){
			SetFlash("Not your ship");
			header("Location: fleetlist.php");
			exit;
		}

		if($_POST['action'] == "rename"){
			$newName = trim($_POST['new_name'] ?? '');
			if($newName !== ''){
				$safeName = mysqli_real_escape_string($GLOBALS["conn"], $newName);
				mysqli_query($GLOBALS["conn"], "UPDATE ships SET Name='$safeName' WHERE ShipID='$ShipID'");
				SetFlash("Ship renamed");
			} else {
				SetFlash("Ship name cannot be empty");
			}
			header("Location: ship.php?id=".$ShipID);
			exit;
		}

		if($_POST['action'] == "salvage"){
			// Only allow salvage when ship is on a planet (unassigned or fleet in orbit)
			$_canSalvage = false;
			$_salvPlanetID = 0;
			if((int)$_ship->FleetID > 0){
				$_fleet = GetFleet($_ship->FleetID);
				if($_fleet && $_fleet->Location !== '' && substr($_fleet->Location, 0, 2) === 'P:'){
					$_salvPlanetID = (int)substr($_fleet->Location, 2);
					$_canSalvage = true;
				}
			} else {
				$_salvPlanetID = (int)$_ship->PlanetID;
				if($_salvPlanetID > 0) $_canSalvage = true;
			}

			if(!$_canSalvage){
				SetFlash("Ship must be in orbit or on a planet to salvage");
				header("Location: ship.php?id=".$ShipID);
				exit;
			}

			// Calculate salvage refund
			$_salvageRate = (float)GetGameSetting('ship_salvage_rate', 0.4);
			$_maxHP = GetShipTypeDefaultHP($_ship->Type);
			$_hpRate = ($_maxHP > 0) ? (int)$_ship->HP / $_maxHP : 0;
			$_costs = mysqli_fetch_object(mysqli_query($GLOBALS["conn"], "SELECT Metal, Mineral, Astrium FROM ship_types WHERE Type='".(int)$_ship->Type."'"));
			$_rMetal = 0; $_rMineral = 0; $_rAstrium = 0;
			if($_costs){
				$_rMetal = (int)round($_costs->Metal * $_salvageRate * $_hpRate);
				$_rMineral = (int)round($_costs->Mineral * $_salvageRate * $_hpRate);
				$_rAstrium = (int)round($_costs->Astrium * $_salvageRate * $_hpRate);
				$_pid = GetPlayerIDFromName($username);
				if($_rMetal > 0) AddResources($_pid, 1, $_rMetal);
				if($_rMineral > 0) AddResources($_pid, 2, $_rMineral);
				if($_rAstrium > 0) AddResources($_pid, 3, $_rAstrium);
			}

			$_shipName = GetShipName($ShipID);
			$_fleetID = (int)$_ship->FleetID;
			DestroyShip($ShipID, 2); // Reason 2 = Self-Destruct/Salvage
			$flashMsg = h($_shipName)." salvaged. Refund: {$_rMetal} Metal, {$_rMineral} Mineral, {$_rAstrium} Astrium";
			AddAlertForCurrentUser('fleet', $flashMsg);
			SetFlash($flashMsg);

			// Redirect back to fleet if it still exists, otherwise fleetlist
			if($_fleetID > 0 && HasShipsLeft($_fleetID)){
				header("Location: fleet.php?id=".$_fleetID);
			} else {
				header("Location: fleetlist.php");
			}
			exit;
		}
	}
}

$ShipID = (int)($_GET['id'] ?? 0);
if($ShipID < 1){
	echo "No ship specified";
	exit;
}

$Ship = GetShip($ShipID);
if(!$Ship){
	echo "Ship not found";
	exit;
}

// Ownership check — must be yours
$myPID = GetPlayerIDFromName($username);
if((int)$Ship->PlayerID != $myPID){
	echo "Not your ship";
	exit;
}

// Gather ship type info
$_typeRow = mysqli_fetch_object(mysqli_query($GLOBALS["conn"], "SELECT * FROM ship_types WHERE Type='".(int)$Ship->Type."'"));
$_typeName = $_typeRow ? $_typeRow->Name : 'Unknown';
$_maxHP = $_typeRow ? (int)$_typeRow->HP : 0;

// Ship type images: Type => filename in images/shiptypes/
$_shipImages = [
	1 => 'scout.png',
	2 => 'transport.png',
	3 => 'colony.png',
	4 => 'frigate.png',
	5 => 'cruiser.png',
	6 => 'warship.png',
	7 => 'mothership.png',
	8 => 'fighter.png',
];
$_shipImg = $_shipImages[(int)$Ship->Type] ?? null;

// Location string
$_locStr = '';
$_canSalvage = false;
if((int)$Ship->FleetID > 0){
	$_fleet = GetFleet($Ship->FleetID);
	if($_fleet){
		$_locStr = 'Assigned to fleet: <a href="fleet.php?id='.$Ship->FleetID.'">'.h($_fleet->Name).'</a>';
		$_locStr .= '<br/>Fleet location: ' . GetFleetLocationString($Ship->FleetID);
		// Can salvage if fleet is orbiting a planet
		if($_fleet->Location !== '' && substr($_fleet->Location, 0, 2) === 'P:'){
			$_canSalvage = true;
		}
	}
} else {
	$_planetID = (int)$Ship->PlanetID;
	if($_planetID > 0){
		$_locStr = 'Unassigned on <a href="planet.php?id='.$_planetID.'">'.h(GetPlanetNameFromID($_planetID)).'</a>';
		$_canSalvage = true;
	} else {
		$_locStr = 'Unknown';
	}
}

// Salvage calculation for display
$_salvageRate = (float)GetGameSetting('ship_salvage_rate', 0.4);
$_hpRate = ($_maxHP > 0) ? (int)$Ship->HP / $_maxHP : 0;
$_salvStr = '';
if($_typeRow){
	$_sMetal = (int)round($_typeRow->Metal * $_salvageRate * $_hpRate);
	$_sMineral = (int)round($_typeRow->Mineral * $_salvageRate * $_hpRate);
	$_sAstrium = (int)round($_typeRow->Astrium * $_salvageRate * $_hpRate);
	$_salvStr = $_sMetal . ' Metal, ' . $_sMineral . ' Mineral';
	if($_sAstrium > 0) $_salvStr .= ', ' . $_sAstrium . ' Astrium';
}
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<?php $_fleetName = ((int)$Ship->FleetID > 0 && isset($_fleet) && $_fleet) ? ' ['.h($_fleet->Name).']' : ''; ?>
<title>Ship: <?php if($Ship->Registration) echo h($Ship->Registration).' '; echo h($Ship->Name) . $_fleetName; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Ship: <?php if($Ship->Registration) echo h($Ship->Registration).' '; echo h($Ship->Name); ?><?php if($_fleetName): ?> <span style="color:#888;"><?php echo $_fleetName; ?></span><?php endif; ?></h2>
<div class="side">
<div class="panel" style="width:300px;">
  <h3>Ship Information</h3>
  <p>
    <strong>Name:</strong> <?php echo h($Ship->Name); ?><br/>
    <?php if($Ship->Registration): ?>
    <strong>Registration:</strong> <?php echo h($Ship->Registration); ?><br/>
    <?php endif; ?>
    <strong>Type:</strong> <?php echo h($_typeName); ?><br/>
    <strong>HP:</strong> <?php echo $Ship->HP . '/' . $_maxHP; ?><br/>
    <strong>AP:</strong> <?php echo $Ship->AP; ?><br/>
    <strong>Location:</strong> <?php echo $_locStr; ?>
  </p>
</div>

<div class="panel" style="width:300px;">
  <h3>Rename Ship</h3>
  <form method="post" action="ship.php">
    <input type="hidden" name="action" value="rename">
    <input type="hidden" name="ship_id" value="<?php echo $ShipID; ?>">
    <?php echo csrf_token(); ?>
    <p>Name: <input type="text" name="new_name" size="15" value="<?php echo htmlspecialchars($Ship->Name); ?>">
    <input type="submit" value="Rename"></p>
  </form>
</div>

</div>
<?php if($_shipImg): ?>
<div class="planet"><img src="images/shiptypes/<?php echo $_shipImg; ?>" alt="<?php echo h($_typeName); ?>" style="max-width:600px; height:auto;"></div>
<?php endif; ?>
<div class="side">
<div class="panel" style="width:300px;">
  <h3>Salvage Ship</h3>
  <?php if($_canSalvage): ?>
  <?php if($_salvStr): ?>
  <p><small>Salvage value: <?php echo $_salvStr; ?> <em>(<?php echo round($_salvageRate * 100); ?>% &times; <?php echo round($_hpRate * 100); ?>% HP)</em></small></p>
  <?php endif; ?>
  <form method="post" action="ship.php">
    <input type="hidden" name="action" value="salvage">
    <input type="hidden" name="ship_id" value="<?php echo $ShipID; ?>">
    <?php echo csrf_token(); ?>
    <input type="submit" value="Salvage Ship" onclick="return confirm('Salvage <?php echo htmlspecialchars($Ship->Name); ?>? This will destroy the ship.<?php if($_salvStr) echo ' Refund: '.$_salvStr; ?>');">
  </form>
  <?php else: ?>
  <p style="color:#888;"><small>Ship must be in orbit or on a planet to salvage.</small></p>
  <?php endif; ?>
</div>
<?php if((int)$Ship->FleetID > 0 && isset($_fleet) && $_fleet): ?>
<div class="panel" style="width:300px;">
  <h3>Other Ships in Fleet</h3>
  <?php
  $_allShips = GetShipArray($Ship->FleetID);
  $_hasOthers = false;
  foreach($_allShips as $_os):
    if((int)$_os->ShipID == $ShipID) continue;
    $_hasOthers = true;
    $_osType = GetShipTypeString($_os->Type);
    $_osMaxHP = GetShipTypeDefaultHP($_os->Type);
  ?>
  <p style="margin:2px 0; border-left:3px solid #FFF; padding-left:6px;">
    <a href="ship.php?id=<?php echo $_os->ShipID; ?>"><?php if($_os->Registration) echo '<small>['.h($_os->Registration).']</small> '; echo h($_os->Name); ?></a><br/>
    <small style="color:#aaa;"><?php echo h($_osType); ?> &mdash; HP: <?php echo $_os->HP.'/'.$_osMaxHP; ?> &middot; AP: <?php echo $_os->AP; ?></small>
  </p>
  <?php endforeach; ?>
  <?php if(!$_hasOthers): ?>
  <p style="color:#888;"><small>No other ships in this fleet.</small></p>
  <?php endif; ?>
</div>
<?php endif; ?>
</div>
</body>
</html>
