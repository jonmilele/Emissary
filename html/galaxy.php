<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>The Galaxy</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2>The Galaxy</h2>
<p><a href="galaxyzoom.php">Zoom In — Full Size Galaxy Map</a></p>
<?php
// Galaxy statistics
$_gSectors = 100;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM Systems"); $_gSystems = (int)mysqli_fetch_object($_r)->c;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM planets"); $_gPlanets = (int)mysqli_fetch_object($_r)->c;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM planets WHERE PlayerID > 0"); $_gColonised = (int)mysqli_fetch_object($_r)->c;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM players"); $_gPlayers = (int)mysqli_fetch_object($_r)->c;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM teams"); $_gTeams = (int)mysqli_fetch_object($_r)->c;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM fleets"); $_gFleets = (int)mysqli_fetch_object($_r)->c;
$_r = mysqli_query($GLOBALS["conn"], "SELECT COUNT(*) AS c FROM ships"); $_gShips = (int)mysqli_fetch_object($_r)->c;
?>
<div class="panel" style="display:flex; flex-wrap:wrap; gap:4px 16px; align-items:center;">
  <h3 style="margin-right:8px;">Galaxy Statistics</h3>
  <span style="border-left:3px solid #FF0000; padding-left:6px;">Sectors: <strong><?php echo number_format($_gSectors); ?></strong></span>
  <span style="border-left:3px solid #FF9900; padding-left:6px;">Systems: <strong><?php echo number_format($_gSystems); ?></strong></span>
  <span style="border-left:3px solid #00FF00; padding-left:6px;">Planets: <strong><?php echo number_format($_gPlanets); ?></strong> <small style="color:#888;">(<?php echo number_format($_gColonised); ?> colonised)</small></span>
  <span style="border-left:3px solid #0066FF; padding-left:6px;">Players: <strong><?php echo number_format($_gPlayers); ?></strong></span>
  <span style="border-left:3px solid #CC00FF; padding-left:6px;">Teams: <strong><?php echo number_format($_gTeams); ?></strong></span>
  <span style="border-left:3px solid #FFFFFF; padding-left:6px;">Fleets: <strong><?php echo number_format($_gFleets); ?></strong> <small style="color:#888;">(<?php echo number_format($_gShips); ?> ships)</small></span>
</div>
<div class="galaxy"><img src="galaxyimage.img.php" border="0" usemap="#Map" style="margin:15px;"/>
<map name="Map">
<?php
// Bulk precompute all sector data in a few queries instead of hundreds
$_secSystems = [];
$_secPlanets = [];
$_secTeamCtrl = [];
$_secPlayers = [];
$_secNames = [];

// 0. Sector names
$res = mysqli_query($GLOBALS["conn"], "SELECT SectorID, Name FROM sectors");
while($row = mysqli_fetch_object($res)) $_secNames[(int)$row->SectorID] = $row->Name;

// 1. Systems per sector
$res = mysqli_query($GLOBALS["conn"], "SELECT SectorID, COUNT(*) AS cnt FROM Systems GROUP BY SectorID");
while($row = mysqli_fetch_object($res)) $_secSystems[(int)$row->SectorID] = (int)$row->cnt;

// 2. Planets per sector
$res = mysqli_query($GLOBALS["conn"], "SELECT s.SectorID, COUNT(p.PlanetID) AS cnt FROM Systems s JOIN planets p ON p.`System`=s.SystemID GROUP BY s.SectorID");
while($row = mysqli_fetch_object($res)) $_secPlanets[(int)$row->SectorID] = (int)$row->cnt;

// 3. Player planet counts per sector (for stakeholders)
$res = mysqli_query($GLOBALS["conn"], "SELECT s.SectorID, p.PlayerID, pl.UserName AS PlayerName, COUNT(*) AS cnt FROM Systems s JOIN planets p ON p.`System`=s.SystemID JOIN players pl ON pl.PlayerID=p.PlayerID WHERE p.PlayerID > 0 GROUP BY s.SectorID, p.PlayerID");
while($row = mysqli_fetch_object($res)){
	$sid = (int)$row->SectorID;
	if(!isset($_secPlayers[$sid])) $_secPlayers[$sid] = [];
	$_secPlayers[$sid][] = $row->PlayerName . " (" . $row->cnt . ")";
}

// 4. Majority team per sector (team with most owned systems)
$res = mysqli_query($GLOBALS["conn"], "SELECT s.SectorID, pl.TeamID, t.Name AS TeamName, COUNT(*) AS cnt FROM Systems s JOIN planets p ON p.`System`=s.SystemID JOIN players pl ON pl.PlayerID=p.PlayerID JOIN teams t ON t.TeamID=pl.TeamID WHERE p.PlayerID > 0 AND pl.TeamID > 0 GROUP BY s.SectorID, pl.TeamID ORDER BY s.SectorID, cnt DESC");
$_secTeamTop = [];
while($row = mysqli_fetch_object($res)){
	$sid = (int)$row->SectorID;
	if(!isset($_secTeamTop[$sid])){
		$_secTeamTop[$sid] = ['name' => $row->TeamName, 'cnt' => (int)$row->cnt];
	} elseif((int)$row->cnt == $_secTeamTop[$sid]['cnt']){
		$_secTeamTop[$sid] = null; // tied — no majority
	}
}

$secid = 1;
for($i = 0;$i<10;$i++){
	for($j = 0;$j<10;$j++){
		$ns = $_secSystems[$secid] ?? 0;
		$np = $_secPlanets[$secid] ?? 0;
		$sname = $_secNames[$secid] ?? '';
		$tip = ($sname ? "$sname (Sector $secid)" : "Sector $secid") . " | Systems: $ns | Planets: $np";
		if(!empty($_secTeamTop[$secid])){
			$tip .= " | Controlled by: " . htmlspecialchars($_secTeamTop[$secid]['name']);
		}
		if(!empty($_secPlayers[$secid])){
			$tip .= " | Players: " . implode(", ", $_secPlayers[$secid]);
		}
		?>
		 <area shape="rect" title="<?php echo htmlspecialchars($tip); ?>" coords="<?php echo $j*50; ?>,<?php echo $i*50; ?>,<?php echo ($j*50)+50; ?>,<?php echo ($i*50)+50; ?>" href="sector.php?id=<?php echo $secid; ?>">
		<?php
		$secid++;
	}	
}
?>
  </map></div>
</body>
</html>

