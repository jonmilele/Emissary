<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && $_POST['action'] == 'rename_sector' && csrf_validate()){
	$_rSid = (int)($_POST['sector_id'] ?? 0);
	$_rName = trim($_POST['sector_name'] ?? '');
	$_rPid = GetPlayerIDFromName($username);
	if(RenameSector($_rSid, $_rName, $_rPid)){
		SetFlash("Sector renamed");
		header("Location: sector.php?id=$_rSid");
	} else {
		SetFlash("Cannot rename sector");
		header("Location: sector.php?id=$_rSid");
	}
	exit;
}

if(!IsSector(($_GET['id'] ?? ""))){
	echo "Not a valid sector ID";
}else{
	$SectorID = ($_GET['id'] ?? "");
	$_secRow = mysqli_fetch_object(mysqli_query($GLOBALS["conn"], "SELECT Name FROM sectors WHERE SectorID='" . (int)$SectorID . "'"));
	$_sectorName = $_secRow && $_secRow->Name ? $_secRow->Name : "Sector $SectorID";
	$_secOwnership = CalcSectorOwnership($SectorID);
	$TeamID = $_secOwnership['TeamID'];
	$_secMajOwner = $_secOwnership['PlayerID'];
	$_myPID = GetPlayerIDFromName($username);
	$_canRename = ($_secMajOwner > 0 && $_secMajOwner == $_myPID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo h($_sectorName); ?> (Sector <?php echo $SectorID; ?>)</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2><?php echo h($_sectorName); ?> <small style="color:#888;">(Sector <?php echo $SectorID; ?>)</small></h2>
<div class="side">
  <?php
  // Stationary fleets orbiting planets in this sector
  $_secFleets = [];
  $_sfQ = mysqli_query($GLOBALS['conn'],
    "SELECT DISTINCT f.FleetID, f.Name, f.PlayerID,
     (SELECT COUNT(*) FROM ships sh WHERE sh.FleetID = f.FleetID) AS ShipCount
     FROM fleets f
     JOIN planets p ON f.Location = CONCAT('P:', p.PlanetID)
     JOIN Systems s ON p.`System` = s.SystemID
     WHERE s.SectorID = '$SectorID'");
  while($_sf = mysqli_fetch_object($_sfQ)){
    $_secFleets[] = ['id' => (int)$_sf->FleetID, 'name' => $_sf->Name ?: 'Fleet '.$_sf->FleetID, 'pid' => (int)$_sf->PlayerID, 'size' => (int)$_sf->ShipCount, 'transit' => false];
  }
  // In-transit fleets whose current interpolated position is within sector bounds
  $_secC = GetSectorCoords($SectorID);
  $_sOX = ((int)$_secC[0] - 1) * 500;
  $_sOY = ((int)$_secC[1] - 1) * 500;
  $_trCQ = mysqli_query($GLOBALS['conn'],
    "SELECT f.FleetID, f.Name, f.PlayerID,
     (SELECT COUNT(*) FROM ships sh WHERE sh.FleetID = f.FleetID) AS ShipCount
     FROM fleets f WHERE f.Location = '' AND f.Destination != ''");
  while($_trf = mysqli_fetch_object($_trCQ)){
    $gl = GetGalacticLocation($_trf->FleetID);
    $gc = explode('/', substr($gl, 2));
    $lx = (float)$gc[0] - $_sOX;
    $ly = (float)$gc[1] - $_sOY;
    if($lx >= 0 && $lx <= 500 && $ly >= 0 && $ly <= 500){
      $_secFleets[] = ['id' => (int)$_trf->FleetID, 'name' => $_trf->Name ?: 'Fleet '.$_trf->FleetID, 'pid' => (int)$_trf->PlayerID, 'size' => (int)$_trf->ShipCount, 'transit' => true];
    }
  }
  $_sectorFleetCount = count($_secFleets);
  $_sectorSystems = SystemsInSector($SectorID);
  $_sectorPlanets = PlanetsInSector($SectorID);
  $owner = $_secMajOwner;
  $strowner = $owner == 0 ? 'None' : '<a href="player.php?id='.$owner.'">'.h(GetPlayerNameFromID($owner)).'</a>';
  ?>
  <div class="panel" style="width:250px;">
    <h3>Sector Info</h3>
    <p style="margin:2px 0; border-left:3px solid #FF9900; padding-left:6px;">Systems: <strong><?php echo $_sectorSystems; ?></strong></p>
    <p style="margin:2px 0; border-left:3px solid #00FF00; padding-left:6px;">Planets: <strong><?php echo $_sectorPlanets; ?></strong></p>
    <p style="margin:2px 0; border-left:3px solid #FFFFFF; padding-left:6px;">Majority Owner: <?php echo $strowner; ?></p>
    <?php if($TeamID > 0): ?>
    <p style="margin:2px 0; border-left:3px solid #0066FF; padding-left:6px;">Team: <a href="team.php?id=<?php echo $TeamID; ?>"><?php echo h(TeamNameFromID($TeamID)); ?></a> <img src="teamcolour.img.php?id=<?php echo $TeamID; ?>" style="vertical-align:middle;" width="20" height="10"></p>
    <?php endif; ?>
    <?php if($_canRename): ?>
    <form method="POST" action="sector.php" style="margin-top:8px;">
      <input type="hidden" name="action" value="rename_sector">
      <input type="hidden" name="sector_id" value="<?php echo $SectorID; ?>">
      <?php echo csrf_token(); ?>
      <input type="text" name="sector_name" value="<?php echo h($_sectorName); ?>" maxlength="100" style="width:180px;">
      <input type="submit" value="Rename">
    </form>
    <?php endif; ?>
  </div>
  <div class="panel" style="width:250px;">
    <h3>Fleets (<?php echo $_sectorFleetCount; ?>)</h3>
    <?php if($_sectorFleetCount > 0): ?>
    <?php foreach($_secFleets as $_sfl): ?>
    <p style="margin:2px 0; border-left:3px solid <?php echo $_sfl['transit'] ? '#FF9900' : '#FFF'; ?>; padding-left:6px;">
      <a href="fleet.php?id=<?php echo $_sfl['id']; ?>"><?php echo h($_sfl['name']); ?></a>
      <?php if($_sfl['transit']): ?><small style="color:#FF9900;">[transit]</small><?php endif; ?><br/>
      <small><?php echo PlayerProfileLink($_sfl['pid']); ?></small><br/>
      <small style="color:#888;">Ships: <?php echo $_sfl['size']; ?> | HP: <?php echo number_format(FleetHP($_sfl['id'])); ?> | AP: <?php echo number_format(FleetAP($_sfl['id'])); ?></small>
    </p>
    <?php endforeach; ?>
    <?php else: ?>
    <p><small style="color:#888;">No fleets in sector</small></p>
    <?php endif; ?>
  </div>
  <?php
  $stakeholders = ListSectorStakeHolders($SectorID);
  if(!empty($stakeholders)):
  ?>
  <div class="panel" style="width:250px;">
    <h3>Stakeholders</h3>
    <?php foreach($stakeholders as $stake):
      $s = $stake["Count"] != 1 ? "s" : "";
    ?>
    <p style="margin:2px 0; border-left:3px solid #FF0000; padding-left:6px;">
      <?php echo PlayerProfileLink($stake["ID"]); ?> <small style="color:#888;">[<?php echo $stake["Count"]; ?> planet<?php echo $s; ?>]</small><br/>
      <?php foreach($stake["Planets"] as $pl): ?>
      <small>&bull; <a href="planet.php?id=<?php echo (int)$pl["PlanetID"]; ?>"><?php echo h($pl["Name"]); ?></a></small><br/>
      <?php endforeach; ?>
    </p>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php
// Sector navigation — 10x10 grid, IDs 1-100
$_navCols = 10;
$_navRows = 10;
$_navRow = intdiv((int)$SectorID - 1, $_navCols); // 0-9
$_navCol = ((int)$SectorID - 1) % $_navCols;       // 0-9
$_navDirs = [
	'NW' => ($_navRow > 0 && $_navCol > 0)                    ? (int)$SectorID - $_navCols - 1 : 0,
	'N'  => ($_navRow > 0)                                     ? (int)$SectorID - $_navCols     : 0,
	'NE' => ($_navRow > 0 && $_navCol < $_navCols - 1)         ? (int)$SectorID - $_navCols + 1 : 0,
	'W'  => ($_navCol > 0)                                     ? (int)$SectorID - 1             : 0,
	'E'  => ($_navCol < $_navCols - 1)                         ? (int)$SectorID + 1             : 0,
	'SW' => ($_navRow < $_navRows - 1 && $_navCol > 0)         ? (int)$SectorID + $_navCols - 1 : 0,
	'S'  => ($_navRow < $_navRows - 1)                         ? (int)$SectorID + $_navCols     : 0,
	'SE' => ($_navRow < $_navRows - 1 && $_navCol < $_navCols - 1) ? (int)$SectorID + $_navCols + 1 : 0,
];
function _navBtn($label, $target){
	$w = 'width:60px;height:28px;';
	if($target > 0){
		$name = h(GetSectorName($target));
		return '<input type="button" value="'.$label.'" style="'.$w.'cursor:pointer;" onclick="location.href=\'sector.php?id='.$target.'\'" title="'.$name.'">';
	}
	return '<input type="button" value="'.$label.'" style="'.$w.'" disabled>';
}
?>
  <div class="panel" style="width:210px; text-align:center; margin-bottom:10px; padding:8px;">
    <strong>Navigate</strong><br/>
    <div style="margin-top:4px;">
      <?php echo _navBtn('NW', $_navDirs['NW']); ?>
      <?php echo _navBtn('N',  $_navDirs['N']); ?>
      <?php echo _navBtn('NE', $_navDirs['NE']); ?>
    </div>
    <div>
      <?php echo _navBtn('W',  $_navDirs['W']); ?>
      <input type="button" value="·" style="width:60px;height:28px;" disabled>
      <?php echo _navBtn('E',  $_navDirs['E']); ?>
    </div>
    <div>
      <?php echo _navBtn('SW', $_navDirs['SW']); ?>
      <?php echo _navBtn('S',  $_navDirs['S']); ?>
      <?php echo _navBtn('SE', $_navDirs['SE']); ?>
    </div>
    <div style="margin-top:4px;"><small><a href="galaxy.php">Galaxy Map</a></small></div>
  </div>
</div>
<div class="planet"><img border="0" src="<?php echo GetSectorPictureFromID($SectorID); ?>" style="margin:15px;" usemap="#Map"/>
<map name="Map">
<?php
$Systems = GetSystemsInSector($SectorID);
foreach($Systems as $k=>$System){
	$coords = $System->Coords;
	$coordarray = explode("/",$coords);
	
	$xcoord = $coordarray[0]*50;
	$ycoord = $coordarray[1]*50; //$xcoord+","+$ycoord
?>
  <area shape="circle" coords="<?php echo $xcoord; ?>,<?php echo $ycoord; ?>,10" href="system.php?id=<?php echo $System->SystemID; ?>">
<?php
}
// Fleet marker areas — stationary fleets
$_fmQ = mysqli_query($GLOBALS['conn'],
	"SELECT f.FleetID, s.Coords FROM fleets f
	 JOIN planets p ON f.Location = CONCAT('P:', p.PlanetID)
	 JOIN Systems s ON p.`System` = s.SystemID
	 WHERE s.SectorID = '$SectorID' AND f.Location != ''");
$_fmByCoord = [];
while($_fm = mysqli_fetch_object($_fmQ)){
	$ca = explode('/', $_fm->Coords);
	$key = (int)($ca[0] * 50) . ':' . (int)($ca[1] * 50);
	if(!isset($_fmByCoord[$key])) $_fmByCoord[$key] = [];
	$_fmByCoord[$key][] = $_fm;
}
foreach($_fmByCoord as $key => $fleets){
	$parts = explode(':', $key);
	$cx = (int)$parts[0]; $cy = (int)$parts[1];
	foreach($fleets as $i => $fl){
		$fx = $cx + 18 + ($i * 8);
		$fy = $cy - 3;
?>
  <area shape="rect" coords="<?php echo $fx.','.$fy.','.($fx+5).','.($fy+5); ?>" href="fleet.php?id=<?php echo $fl->FleetID; ?>" title="<?php echo h(GetFleetName($fl->FleetID)); ?>">
<?php
	}
}
// Fleet marker areas — in-transit fleets within sector
$_secCoords = GetSectorCoords($SectorID);
$_secOX = ((int)$_secCoords[0] - 1) * 500;
$_secOY = ((int)$_secCoords[1] - 1) * 500;
$_trFQ = mysqli_query($GLOBALS['conn'],
	"SELECT f.FleetID, f.MovingFrom, f.Destination, f.TTF FROM fleets f
	 WHERE f.Location = '' AND f.Destination != ''");
while($_tf = mysqli_fetch_object($_trFQ)){
	$galLoc = GetGalacticLocation($_tf->FleetID);
	$gc = explode('/', substr($galLoc, 2));
	$clx = (int)((float)$gc[0] - $_secOX);
	$cly = (int)((float)$gc[1] - $_secOY);
	if($clx >= 0 && $clx <= 500 && $cly >= 0 && $cly <= 500){
?>
  <area shape="rect" coords="<?php echo ($clx-3).','.($cly-3).','.($clx+3).','.($cly+3); ?>" href="fleet.php?id=<?php echo $_tf->FleetID; ?>" title="<?php echo h(GetFleetName($_tf->FleetID)); ?> (en route)">
<?php
	}
}
?>
</map></div>
</body>
</html>
<?php
} //Is Planet
?>
