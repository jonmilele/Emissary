<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && $_POST['action'] == 'rename_sector' && csrf_validate()){
	$_rSid = (int)($_POST['sector_id'] ?? 0);
	$_rName = trim($_POST['sector_name'] ?? '');
	$_rPid = GetPlayerIDFromName($username);
	if(RenameSector($_rSid, $_rName, $_rPid)){
		header("Location: sector.php?id=$_rSid&msg=Sector+renamed");
	} else {
		header("Location: sector.php?id=$_rSid&msg=Cannot+rename+sector");
	}
	exit;
}

if(!IsSector(($_GET['id'] ?? ""))){
	echo "Not a valid sector ID";
}else{
	$SectorID = ($_GET['id'] ?? "");
	$_secRow = mysqli_fetch_object(mysqli_query($GLOBALS["conn"], "SELECT Name, MajOwner, MajTeamID FROM sectors WHERE SectorID='" . (int)$SectorID . "'"));
	$TeamID = $_secRow ? (int)$_secRow->MajTeamID : 0;
	$_sectorName = $_secRow && $_secRow->Name ? $_secRow->Name : "Sector $SectorID";
	$_secMajOwner = $_secRow ? (int)$_secRow->MajOwner : 0;
	$_myPID = GetPlayerIDFromName($username);
	$_canRename = ($_secMajOwner > 0 && $_secMajOwner == $_myPID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo h($_sectorName); ?> (Sector <?php echo $SectorID; ?>)</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
if(isset($_GET['msg'])): ?><p><strong><?php echo h($_GET['msg']); ?></strong></p><?php endif;
?>
<h2><?php echo h($_sectorName); ?> <small style="color:#888;">(Sector <?php echo $SectorID; ?>)</small></h2>
<div class="side">
  <p>Fleets in Sector: 0<br/>
    Systems in Sector: <?php echo SystemsInSector($SectorID); ?><br/>
    Planets in Sector: <?php echo PlanetsInSector($SectorID); ?><br/>
    Stakeholders: <br/>
	<ul>
	<?php 
	$stakeholders = ListSectorStakeHolders($SectorID);
	foreach($stakeholders as $k=>$stake){
		$s = $stake["Count"] != 1 ? "s" : "";
		echo "<li>" . PlayerProfileLink($stake["ID"]) . " [" . $stake["Count"] . " planet" . $s . "]";
		echo "<ul>";
		foreach($stake["Planets"] as $pl){
			echo "<li><a href=\"planet.php?id=" . (int)$pl["PlanetID"] . "\">" . h($pl["Name"]) . "</a></li>";
		}
		echo "</ul>";
		echo "</li>";
	}
	?></ul>
    <?php
$owner = CalcMajOwner($SectorID);
if($owner ==0){
	$strowner = "None";
}else{
	$strowner = "<a href=\"player.php?id=".$owner."\">".h(GetPlayerNameFromID($owner))."</a>";
}
?>
    Majority Owner: <?php echo $strowner; ?><br/>
	<?php if($TeamID>0){?>
    Team Controlling:<br/>
    <a href="team.php?id=<?php echo $TeamID; ?>"><?php echo h(TeamNameFromID($TeamID)); ?></a></p>
  <p><img src="teamcolour.img.php?id=<?php echo $TeamID; ?>"><br/>
  </p><?php }?>
  <?php if($_canRename): ?>
  <form method="POST" action="sector.php" style="margin-top:8px;">
    <input type="hidden" name="action" value="rename_sector">
    <input type="hidden" name="sector_id" value="<?php echo $SectorID; ?>">
    <?php echo csrf_token(); ?>
    <input type="text" name="sector_name" value="<?php echo h($_sectorName); ?>" maxlength="100" style="width:180px;">
    <input type="submit" value="Rename">
  </form>
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
		return '<a href="sector.php?id='.$target.'" title="'.$name.'"><button style="'.$w.'">'.$label.'</button></a>';
	}
	return '<button style="'.$w.'" disabled>'.$label.'</button>';
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
      <button style="width:60px;height:28px;" disabled>&bull;</button>
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
?>
</map></div>
</body>
</html>
<?php
} //Is Planet
?>
