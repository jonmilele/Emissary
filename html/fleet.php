<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && csrf_validate()){
	if(($_POST['action'] ?? "")=="move"){
		$FleetID = ($_POST["fleet"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$_targetPlanet = ($_POST["value"] ?? "");
		if($_targetPlanet === '' || !IsPlanet($_targetPlanet)){
			SetFlash("Select a target planet");
			header("Location: fleet.php?id=".$FleetID);
			exit;
		}
		$Target = "P:".$_targetPlanet;
		$Strategy = ($_POST["strat"] ?? "");
		// Determine planet ownership status
		$_tgtPlanet = GetPlanet($_targetPlanet);
		$_tgtOwner = $_tgtPlanet ? (int)$_tgtPlanet->PlayerID : 0;
		$_isEnemy = EnemyOwned($_targetPlanet);
		$_isOwned = ($_tgtOwner > 0);
		// Colonise only valid on uncolonised planets
		if($Strategy == 1 && $_isOwned){
			SetFlash("Cannot colonise — planet already has an owner");
			$Strategy = 0;
		}
		// Attack/invade only valid on enemy planets
		if($Strategy > 1 && !$_isEnemy){
			$Strategy = 0;
		}
		// Cannot orbit/colonise enemy planets
		if($Strategy < 2 && $_isEnemy){
			AddAlertForCurrentUser('fleet', 'Cannot send fleet to enemy-owned planet without attack/invade strategy', 'fleet.php?id='.$FleetID);
			SetFlash("Enemy Owned — select Attack or Invade");
			header("Location: fleet.php?id=".$FleetID);
			exit;
		}
		MoveFleet($FleetID,$Target,$Strategy);
		$destName = GetPlanetNameFromID($_targetPlanet);
		$stratNames = ['0'=>'orbit','1'=>'colonise','2'=>'attack','3'=>'invade'];
		$stratLabel = $stratNames[$Strategy] ?? 'orbit';
		AddAlertForCurrentUser('fleet', 'Fleet '.GetFleetName($FleetID).' dispatched to '.$destName.' to '.$stratLabel, 'fleet.php?id='.$FleetID);
		SetFlash("Moving");
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="rename"){
		$FleetID = ($_POST["fleet"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$Name = ($_POST["new_name"] ?? "");
		if($Name!=""){
			$sql = "UPDATE fleets SET Name = '$Name' WHERE(FleetID = '$FleetID')";
			$res = mysqli_query($GLOBALS["conn"], $sql);
		}else{
			SetFlash("Fleet name cannot be empty");
		}
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="abort"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$Fleet = GetFleet($FleetID);
		$Orig_TTF = CalcTTF($Fleet->MovingFrom,$Fleet->Destination);
		$Abort_TTF = $Orig_TTF-$Fleet->TTF;
		if($Abort_TTF<1){
			$Abort_TTF = 1;
		}
		$sql = "UPDATE fleets SET Strategy = '0', MovingFrom = '".$Fleet->Destination."', Destination = '".$Fleet->MovingFrom."', TTF = '".$Abort_TTF."' WHERE(FleetID = '".$Fleet->FleetID."')";
		$res = mysqli_query($GLOBALS["conn"], $sql) or die(mysqli_error($GLOBALS["conn"]));
		AddAlertForCurrentUser('fleet', 'Fleet '.GetFleetName($FleetID).' movement aborted, returning in '.$Abort_TTF.' turn(s)', 'fleet.php?id='.$Fleet->FleetID);
		SetFlash("Fleet movement aborted ".$Abort_TTF);
		header("Location: fleet.php?id=".$Fleet->FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="disband"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$delName = GetFleetName($FleetID);
		$check = CanDisbandFleet($FleetID);
		if($check['can'] && DisbandFleet($FleetID)){
			AddAlertForCurrentUser('fleet', 'Fleet '.$delName.' disbanded — ships unassigned');
			SetFlash("Fleet disbanded — ships unassigned");
			header("Location: fleetlist.php");
			exit;
		}
		$reason = $check['reason'] ?? 'Unknown';
		SetFlash("Cannot disband: ".$reason);
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="sethomeport"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$HomePort = (int)($_POST["homeport"] ?? 0);
		if($HomePort > 0 && !OwnsPlanet($username, $HomePort)){
			SetFlash("You do not own that planet");
		}else{
			$sql = "UPDATE fleets SET HomePort = '$HomePort' WHERE(FleetID = '$FleetID')";
			mysqli_query($GLOBALS["conn"], $sql);
			if($HomePort > 0){
				SetFlash("Home port set to ".GetPlanetNameFromID($HomePort));
			}else{
				SetFlash("Home port cleared");
			}
		}
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="returnhome"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$Fleet = GetFleet($FleetID);
		if($Fleet->HomePort < 1){
			SetFlash("No home port set");
		}elseif(!OwnsPlanet($username, $Fleet->HomePort)){
			$sql = "UPDATE fleets SET HomePort = 0 WHERE(FleetID = '$FleetID')";
			mysqli_query($GLOBALS["conn"], $sql);
			SetFlash("Home port planet no longer owned — home port cleared");
		}else{
			$Target = "P:".$Fleet->HomePort;
			if($Fleet->Location == $Target){
				SetFlash("Fleet is already at home port");
			}else{
				MoveFleet($FleetID, $Target, 0);
				$destName = GetPlanetNameFromID($Fleet->HomePort);
				AddAlertForCurrentUser('fleet', 'Fleet '.GetFleetName($FleetID).' returning to home port '.$destName, 'fleet.php?id='.$FleetID);
				SetFlash("Returning to home port: ".$destName);
			}
		}
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="returnprev"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$Fleet = GetFleet($FleetID);
		$_prevLoc = $Fleet->MovingFrom ?? '';
		if($_prevLoc == '' || substr($_prevLoc,0,2) != 'P:'){
			SetFlash("No previous orbit to return to");
		}else{
			$_prevPID = (int)substr($_prevLoc,2);
			MoveFleet($FleetID, $_prevLoc, 0);
			$destName = GetPlanetNameFromID($_prevPID);
			AddAlertForCurrentUser('fleet', 'Fleet '.GetFleetName($FleetID).' returning to previous orbit: '.$destName, 'fleet.php?id='.$FleetID);
			SetFlash("Returning to previous orbit: ".$destName);
		}
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
	if(($_POST['action'] ?? "")=="nearestfriendly"){
		$FleetID = ($_POST["id"] ?? "");
		if(!OwnsFleet($username, $FleetID)){
			SetFlash("Not your fleet");
			header("Location: fleetlist.php");
			exit;
		}
		$_nfPID = GetNearestFriendlyPlanet($FleetID, GetPlayerIDFromName($username));
		if($_nfPID < 1){
			SetFlash("No friendly planets found");
		}else{
			$Fleet = GetFleet($FleetID);
			$Target = "P:".$_nfPID;
			if($Fleet->Location == $Target){
				SetFlash("Fleet is already at the nearest friendly planet");
			}else{
				MoveFleet($FleetID, $Target, 0);
				$destName = GetPlanetNameFromID($_nfPID);
				AddAlertForCurrentUser('fleet', 'Fleet '.GetFleetName($FleetID).' heading to nearest friendly planet: '.$destName, 'fleet.php?id='.$FleetID);
				SetFlash("Heading to nearest friendly planet: ".$destName);
			}
		}
		header("Location: fleet.php?id=".$FleetID);
		exit;
	}
}

$FleetID = ($_GET['id'] ?? "");

$Fleet = GetFleet($FleetID);
$CurrentSector = 0;
$CurrentSystem = 0;
$CurrentPlanet = 0;
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Fleet: <?php echo h(GetFleetName($FleetID)); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php");?>
<h2>Fleet: <?php echo h(GetFleetName($FleetID)); ?></h2>
<div class="side"><div class="panel" style="width:250;">
<p> Fleet Size: <?php echo $Fleet->Size; ?> </p>
    <p>Location: <?php echo GetFleetLocationString($FleetID); ?></p>
	<p>
      <?php if($Fleet->Destination!=""&&(substr($Fleet->MovingFrom,0,2)!="X:")){ ?>
      <form method="post" action="fleet.php" style="display:inline;">
        <input type="hidden" name="action" value="abort">
        <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
        <?php echo csrf_token(); ?>
        <input type="submit" value="Abort" onclick="return confirm('Abort fleet movement?');">
      </form>
      <?php } ?>
    </p>
    <p><strong>Total HP:</strong> <?php echo number_format(FleetHP($FleetID)); ?><br/>
		<strong>Total AP:</strong> <?php echo number_format(FleetAP($FleetID)); ?></p>
</div><div class="panel" style="width:250;">
<h3>Fleet Modification</h3>
    <p>Rename Fleet:</p>
    <form name="form2" method="post" action="fleet.php">
      <input type="hidden" name="action" value="rename">
      <input name="fleet" type="hidden" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <p> Name: 
        <input name="new_name" type="text" id="new_name" size="15">
      </p>
      <p>
        <input type="submit" name="Submit2" value="Rename">
      </p>
    </form>
    <?php
    $_disbandCheck = CanDisbandFleet($FleetID);
    if($_disbandCheck['can']):
    ?>
    <form method="post" action="fleet.php" style="display:inline;">
      <input type="hidden" name="action" value="disband">
      <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <input type="submit" value="Disband Fleet" onclick="return confirm('Disband this fleet? Ships will be unassigned to the planet.');">
    </form>
    <?php else: ?>
    <p style="color:#888;"><small>Cannot disband: <?php echo h($_disbandCheck['reason']); ?></small></p>
    <?php endif; ?>
  </div>

  <div class="panel" style="width:250;">
    <h3>Home Port</h3>
    <?php
    $_playerID = GetPlayerIDFromName($username);
    $_ownedPlanets = GetPlanetList($_playerID);
    $_homePortValid = ($Fleet->HomePort > 0 && isset($_ownedPlanets[$Fleet->HomePort]));
    ?>
    <p><strong>Current:</strong>
    <?php if($_homePortValid): ?>
      <a href="planet.php?id=<?php echo $Fleet->HomePort; ?>"><?php echo h(GetPlanetNameFromID($Fleet->HomePort)); ?></a>
    <?php elseif($Fleet->HomePort > 0): ?>
      <span style="color:#c00;">Lost (<?php echo h(GetPlanetNameFromID($Fleet->HomePort)); ?>)</span>
    <?php else: ?>
      <span style="color:#888;">None</span>
    <?php endif; ?>
    </p>
    <form method="post" action="fleet.php">
      <input type="hidden" name="action" value="sethomeport">
      <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <select name="homeport">
        <option value="0">-- None --</option>
        <?php foreach($_ownedPlanets as $_op): ?>
        <option value="<?php echo $_op->PlanetID; ?>"<?php if($Fleet->HomePort == $_op->PlanetID) echo ' selected'; ?>><?php echo h($_op->Name); ?> (<?php echo $_op->PlanetID; ?>)</option>
        <?php endforeach; ?>
      </select>
      <input type="submit" value="Set">
    </form>
    <?php if($_homePortValid): ?>
    <form method="post" action="fleet.php" style="margin-top:6px;">
      <input type="hidden" name="action" value="returnhome">
      <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <input type="submit" value="Return to Home Port">
    </form>
    <?php endif; ?>
  </div>

<?php
// Collect all ships into a flat list grouped by type name
$_shipGroups = [
	'Transports' => $Fleet->Ships->Transports,
	'Colonisers' => $Fleet->Ships->Colonisers,
	'Frigates' => $Fleet->Ships->Frigates,
	'Cruisers' => $Fleet->Ships->Cruisers,
	'Warships' => $Fleet->Ships->Warships,
	'Motherships' => $Fleet->Ships->Motherships,
	'Fighters' => $Fleet->Ships->Fighters,
];
?>
<div class="panel" style="width:250;"><p><strong>Ships (<?php echo $Fleet->Size; ?>):</strong></p>
<?php foreach($_shipGroups as $_sgName => $_sgShips): ?>
<?php if(sizeof($_sgShips) > 0): ?>
  <p style="margin:4px 0 2px;"><strong><?php echo $_sgName; ?> (<?php echo sizeof($_sgShips); ?>):</strong></p>
  <?php foreach($_sgShips as $Ship): ?>
  <p style="margin:2px 0; border-left:3px solid #FFF; padding-left:6px;"><?php if($Ship->Registration) echo '<small>['.h($Ship->Registration).']</small> '; ?><a href="ship.php?id=<?php echo $Ship->ShipID; ?>"><?php echo h($Ship->Name); ?></a> <small style="color:#888;">HP:<?php echo $Ship->HP; ?> AP:<?php echo $Ship->AP; ?></small></p>
  <?php endforeach; ?>
<?php endif; ?>
<?php endforeach; ?>
  </div>
  </div>

<div class="planet">
<?php
$_viewSector = isset($_GET['sector']) ? (int)$_GET['sector'] : 0;
if($_viewSector > 0 && $_viewSector <= 100):
  $_vSecName = GetSectorName($_viewSector);
?>
<p><a href="fleet.php?id=<?php echo $FleetID; ?>">&laquo; Galaxy Map</a> &mdash; <?php echo h($_vSecName); ?></p>
<img src="sectorimage.img.php?id=<?php echo $_viewSector; ?>" usemap="#sectorMap" style="border:0;"/>
<map name="sectorMap">
<?php
$_vSystems = GetSystemsInSector($_viewSector);
foreach($_vSystems as $_vSys){
  $_vc = explode("/", $_vSys->Coords);
  $_vx = (int)($_vc[0] * 50);
  $_vy = (int)($_vc[1] * 50);
?>
  <area shape="circle" coords="<?php echo $_vx; ?>,<?php echo $_vy; ?>,10" href="fleet.php?id=<?php echo $FleetID; ?>&amp;sector=<?php echo $_viewSector; ?>&amp;system=<?php echo $_vSys->SystemID; ?>" title="<?php echo h($_vSys->Name ?? $_vSys->DefaultName); ?>">
<?php } ?>
<?php
// Fleet marker areas — stationary fleets in this sector
$_fmQ2 = mysqli_query($GLOBALS['conn'],
  "SELECT f.FleetID, s.Coords FROM fleets f
   JOIN planets p ON f.Location = CONCAT('P:', p.PlanetID)
   JOIN Systems s ON p.`System` = s.SystemID
   WHERE s.SectorID = '$_viewSector' AND f.Location != ''");
$_fmByCoord2 = [];
while($_fm2 = mysqli_fetch_object($_fmQ2)){
  $_ca2 = explode('/', $_fm2->Coords);
  $_key2 = (int)($_ca2[0] * 50) . ':' . (int)($_ca2[1] * 50);
  if(!isset($_fmByCoord2[$_key2])) $_fmByCoord2[$_key2] = [];
  $_fmByCoord2[$_key2][] = $_fm2;
}
foreach($_fmByCoord2 as $_key2 => $_fleets2){
  $_parts2 = explode(':', $_key2);
  $_cx2 = (int)$_parts2[0]; $_cy2 = (int)$_parts2[1];
  foreach($_fleets2 as $_i2 => $_fl2){
    $_fx2 = $_cx2 + 18 + ($_i2 * 8);
    $_fy2 = $_cy2 - 3;
?>
  <area shape="rect" coords="<?php echo $_fx2.','.$_fy2.','.($_fx2+5).','.($_fy2+5); ?>" href="fleet.php?id=<?php echo $_fl2->FleetID; ?>" title="<?php echo h(GetFleetName($_fl2->FleetID)); ?>">
<?php
  }
}
// Fleet marker areas — in-transit fleets within sector
$_secCoords2 = GetSectorCoords($_viewSector);
$_secOX2 = ((int)$_secCoords2[0] - 1) * 500;
$_secOY2 = ((int)$_secCoords2[1] - 1) * 500;
$_trFQ2 = mysqli_query($GLOBALS['conn'],
  "SELECT f.FleetID FROM fleets f WHERE f.Location = '' AND f.Destination != ''");
while($_tf2 = mysqli_fetch_object($_trFQ2)){
  $_galLoc2 = GetGalacticLocation($_tf2->FleetID);
  $_gc2 = explode('/', substr($_galLoc2, 2));
  $_clx2 = (int)((float)$_gc2[0] - $_secOX2);
  $_cly2 = (int)((float)$_gc2[1] - $_secOY2);
  if($_clx2 >= 0 && $_clx2 <= 500 && $_cly2 >= 0 && $_cly2 <= 500){
?>
  <area shape="rect" coords="<?php echo ($_clx2-3).','.($_cly2-3).','.($_clx2+3).','.($_cly2+3); ?>" href="fleet.php?id=<?php echo $_tf2->FleetID; ?>" title="<?php echo h(GetFleetName($_tf2->FleetID)); ?> (en route)">
<?php
  }
}
?>
</map>
<?php else: ?>
<img src="routeimage.img.php?id=<?php echo $FleetID; ?>" usemap="#fleetMap" style="border:0;"/>
<map name="fleetMap">
<?php
for($_fi = 0; $_fi < 10; $_fi++){
  for($_fj = 0; $_fj < 10; $_fj++){
    $_fsid = $_fi * 10 + $_fj + 1;
    $_fsname = GetSectorName($_fsid);
    $_ftip = $_fsname ? htmlspecialchars($_fsname) . " (Sector $_fsid)" : "Sector $_fsid";
?>
  <area shape="rect" coords="<?php echo $_fj*50; ?>,<?php echo $_fi*50; ?>,<?php echo ($_fj*50)+50; ?>,<?php echo ($_fi*50)+50; ?>" href="fleet.php?id=<?php echo $FleetID; ?>&amp;sector=<?php echo $_fsid; ?>" title="<?php echo $_ftip; ?>">
<?php
  }
}
?>
</map>
<?php endif; ?>
</div>
<div class="side">
  <div class="panel" style="width:250;">
    <?php $_inTransit = ($Fleet->Location == ''); ?>
    <h3><?php echo $_inTransit ? 'Redirect Fleet' : 'Fleet Movement'; ?></h3>

    <?php if($_inTransit): ?>
    <?php
      $_prevLoc = $Fleet->MovingFrom ?? '';
      $_hasPrevOrbit = ($_prevLoc != '' && substr($_prevLoc,0,2) == 'P:');
      $_nfPID = GetNearestFriendlyPlanet($FleetID, $_playerID);
      $_nfName = '';
      $_nfOwner = '';
      if($_nfPID > 0){
        $_nfName = GetPlanetNameFromID($_nfPID);
        $_nfPlanet = GetPlanet($_nfPID);
        if($_nfPlanet) $_nfOwner = GetPlayerNameFromID($_nfPlanet->PlayerID);
      }
    ?>
    <?php if($_hasPrevOrbit): ?>
    <form method="post" action="fleet.php" style="margin-bottom:6px;">
      <input type="hidden" name="action" value="returnprev">
      <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <input type="submit" value="Return to Previous Orbit (<?php echo h(GetPlanetNameFromID((int)substr($_prevLoc,2))); ?>)" onclick="return confirm('Return to previous orbit: <?php echo h(GetPlanetNameFromID((int)substr($_prevLoc,2))); ?>?');">
    </form>
    <?php endif; ?>
    <?php if($_nfPID > 0): ?>
    <form method="post" action="fleet.php" style="margin-bottom:6px;">
      <input type="hidden" name="action" value="nearestfriendly">
      <input type="hidden" name="id" value="<?php echo $FleetID; ?>">
      <?php echo csrf_token(); ?>
      <input type="submit" value="Nearest Friendly: <?php echo h($_nfName); ?>" onclick="return confirm('Redirect to nearest friendly planet: <?php echo h($_nfName); ?> (<?php echo h($_nfOwner); ?>)?');" title="<?php echo h($_nfName); ?> (<?php echo h($_nfOwner); ?>)">
    </form>
    <?php endif; ?>
    <?php endif; ?>

    <form name="form1" method="post" action="fleet.php">
      <input type="hidden" name="action" value="move">
      <?php echo csrf_token(); ?>
      <input name="fleet" type="hidden" value="<?php echo $FleetID; ?>">
      <p>Target:<br/>
        <?php
        $_viewSystem = isset($_GET['system']) ? (int)$_GET['system'] : 0;
        $_sysPlanets = [];
        if($_viewSystem > 0){
          $_sysPlanets = ListPlanetsInSystem($_viewSystem);
        }
        ?>
        <select name="value" id="targetPlanet" onchange="_updateStrats()" style="width:100%;">
          <option value="" data-status="none">-- Select Planet --</option>
          <?php if(!empty($_sysPlanets)): ?>
          <optgroup label="<?php echo h(GetSystemNameFromID($_viewSystem)); ?>">
          <?php $_spIdx = 0; foreach($_sysPlanets as $_sp):
            if((int)$_sp->PlayerID === $_playerID) $_spStatus = 'own';
            elseif((int)$_sp->PlayerID === 0) $_spStatus = 'uncolonised';
            else $_spStatus = (PlayerTeam($_sp->PlayerID) != PlayerTeam($_playerID)) ? 'enemy' : 'own';
          ?>
          <option value="<?php echo $_sp->PlanetID; ?>" data-status="<?php echo $_spStatus; ?>"<?php if($_spIdx === 0) echo ' selected'; ?>><?php echo h($_sp->Name); ?> (<?php echo $_sp->PlanetID; ?>)<?php if($_spStatus === 'uncolonised') echo ' [uncolonised]'; elseif($_spStatus === 'enemy') echo ' [enemy]'; ?></option>
          <?php $_spIdx++; ?>
          <?php endforeach; ?>
          </optgroup>
          <?php endif; ?>
          <?php if(!empty($_ownedPlanets)): ?>
          <optgroup label="Your Planets">
          <?php foreach($_ownedPlanets as $_mp): ?>
          <option value="<?php echo $_mp->PlanetID; ?>" data-status="own"><?php echo h($_mp->Name); ?> (<?php echo $_mp->PlanetID; ?>)</option>
          <?php endforeach; ?>
          </optgroup>
          <?php endif; ?>
        </select>
      </p>
      <p>Strategy:<br/>
        <select name="strat" id="stratSelect" style="width:100%;">
          <option value="0" data-for="own uncolonised">Enter Orbit</option>
          <option value="1" data-for="uncolonised">Colonise</option>
          <option value="2" data-for="enemy">Attack</option>
          <option value="3" data-for="enemy">Invade</option>
        </select>
      </p>
      <script>
      function _updateStrats(){
        var t=document.getElementById('targetPlanet');
        var s=document.getElementById('stratSelect');
        var sel=t.options[t.selectedIndex];
        var st=sel?sel.getAttribute('data-status'):'none';
        for(var i=0;i<s.options.length;i++){
          var f=s.options[i].getAttribute('data-for')||'';
          s.options[i].style.display=(st=='none'||f.indexOf(st)>=0)?'':'none';
        }
        if(s.options[s.selectedIndex].style.display=='none') s.selectedIndex=0;
      }
      _updateStrats();
      </script>
      <p><input type="submit" name="Submit" value="<?php echo $_inTransit ? 'Redirect' : 'Launch'; ?>" onclick="var s=document.querySelector('[name=strat]');if(s&&s.value>=2)return confirm('<?php echo $_inTransit ? 'Redirect' : 'Launch'; ?> fleet with '+(s.value==2?'Attack':'Invade')+' strategy?');return true;"></p>
    </form>
    <h3>Strategies</h3>
    <ol>
      <li><strong>Orbit</strong> - Await instructions.</li>
      <li><strong>Colonise</strong> - Colonise planet.</li>
      <li><strong>Attack</strong> - Destroy defences.</li>
      <li><strong>Invade</strong> - Destroy and claim.</li>
    </ol>
  </div>
</div>
</body>
</html>
