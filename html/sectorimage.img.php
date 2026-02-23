<?php
include(__DIR__ . "/authenticate.inc.php");
include(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");
$SectorID = ($_GET['id'] ?? "");
$Systems = GetSystemsInSector($SectorID);

$image = imagecreate(500,500);
$bg = imagecolorallocate($image,0,0,0);
$border = imagecolorallocate($image,255,255,255);
$bordergray = imagecolorallocate($image,153,153,153);
$yellow = imagecolorallocate($image,255,255,51);
$red = imagecolorallocate($image,255,0,0);

imagefill($image,0,0,$bg);
imagerectangle($image,0,0,499,499,$border);

for($i = 50;$i<500;$i+=50){
	imageline($image,$i,0,$i,500,$bordergray);
}
for($j = 50;$j<500;$j+=50){
	imageline($image,0,$j,500,$j,$bordergray);
}

// Bulk: get team colours for all players who own planets in this sector
$_teamColours = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT DISTINCT pl.PlayerID, t.Colour FROM planets p JOIN Systems s ON p.`System`=s.SystemID JOIN players pl ON pl.PlayerID=p.PlayerID JOIN teams t ON t.TeamID=pl.TeamID WHERE s.SectorID='$SectorID' AND p.PlayerID>0 AND pl.TeamID>0");
while($row = mysqli_fetch_object($res)) $_teamColours[(int)$row->PlayerID] = $row->Colour;

// Bulk: team colours by TeamID (derived from planet owners)
$_teamColoursByID = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT DISTINCT t.TeamID, t.Colour FROM planets p JOIN Systems s ON p.`System`=s.SystemID JOIN players pl ON pl.PlayerID=p.PlayerID JOIN teams t ON t.TeamID=pl.TeamID WHERE s.SectorID='$SectorID' AND p.PlayerID>0 AND pl.TeamID>0");
while($row = mysqli_fetch_object($res)) $_teamColoursByID[(int)$row->TeamID] = $row->Colour;

// Bulk: majority owner per system (by planet count)
$_sysMajOwner = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT p.`System` AS sid, p.PlayerID, COUNT(*) AS cnt FROM planets p JOIN Systems s ON s.SystemID=p.`System` WHERE s.SectorID='$SectorID' AND p.PlayerID>0 GROUP BY p.`System`, p.PlayerID ORDER BY p.`System`, cnt DESC");
while($row = mysqli_fetch_object($res)){
	$sid = (int)$row->sid;
	if(!isset($_sysMajOwner[$sid])){
		$_sysMajOwner[$sid] = ['pid' => (int)$row->PlayerID, 'cnt' => (int)$row->cnt];
	} elseif(is_array($_sysMajOwner[$sid]) && (int)$row->cnt == $_sysMajOwner[$sid]['cnt']){
		$_sysMajOwner[$sid] = null; // tied
	}
}

// Bulk: team majority per system (from planet ownership, not stored Systems.TeamID)
$_sysTeamMaj = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT s.SystemID AS sid, pl.TeamID, COUNT(*) AS cnt FROM planets p JOIN Systems s ON p.`System`=s.SystemID JOIN players pl ON pl.PlayerID=p.PlayerID WHERE s.SectorID='$SectorID' AND p.PlayerID>0 AND pl.TeamID>0 GROUP BY s.SystemID, pl.TeamID ORDER BY s.SystemID, cnt DESC");
while($row = mysqli_fetch_object($res)){
	$sid = (int)$row->sid;
	if(!isset($_sysTeamMaj[$sid])){
		$_sysTeamMaj[$sid] = ['tid' => (int)$row->TeamID, 'cnt' => (int)$row->cnt];
	} elseif(is_array($_sysTeamMaj[$sid]) && (int)$row->cnt == $_sysTeamMaj[$sid]['cnt']){
		$_sysTeamMaj[$sid] = null; // tied
	}
}

// --- Helper: check if two rectangles overlap (with padding) ---
function _rectsOverlap($a, $b, $pad = 0){
	return !($a[2] + $pad < $b[0] || $a[0] - $pad > $b[2] || $a[3] + $pad < $b[1] || $a[1] - $pad > $b[3]);
}

$myPID = GetPlayerIDFromName($username);
$_fontW = imagefontwidth(2);
$_fontH = imagefontheight(2);
$_circleR = 17; // half of largest ring (32px) + 1px pad

// Pre-compute system positions
$_sysData = [];
foreach($Systems as $k=>$System){
	$coordarray = explode("/",$System->Coords);
	$xc = (int)($coordarray[0]*50);
	$yc = (int)($coordarray[1]*50);
	$_sysData[$k] = ['sys' => $System, 'x' => $xc, 'y' => $yc];
}

// Collect circle bounding boxes (for label clash detection)
$_circleBoxes = [];
foreach($_sysData as $sd){
	$_circleBoxes[] = [$sd['x'] - $_circleR, $sd['y'] - $_circleR, $sd['x'] + $_circleR, $sd['y'] + $_circleR];
}

// Pass 1: Draw all circles
foreach($_sysData as $sd){
	$System = $sd['sys'];
	$xcoord = $sd['x'];
	$ycoord = $sd['y'];

	$_majPid = (isset($_sysMajOwner[$System->SystemID]) && is_array($_sysMajOwner[$System->SystemID])) ? $_sysMajOwner[$System->SystemID]['pid'] : 0;
	$tc = $_teamColours[$_majPid] ?? '128,128,128';
	$col = explode(",",$tc);
	$team = imagecolorallocate($image,(int)$col[0],(int)$col[1],(int)$col[2]);

	imagerectangle($image,$xcoord,$ycoord,$xcoord+1,$ycoord+1,$border);
	imagearc($image,$xcoord,$ycoord,20,20,0,360,$yellow);
	$majOwner = isset($_sysMajOwner[$System->SystemID]) && is_array($_sysMajOwner[$System->SystemID]) ? $_sysMajOwner[$System->SystemID]['pid'] : 0;
	if($majOwner > 0){
		if($majOwner == $myPID){
			imagearc($image,$xcoord,$ycoord,22,22,0,360,$yellow);
		}
		imagearc($image,$xcoord,$ycoord,30,30,0,360,$team);
		imagearc($image,$xcoord,$ycoord,32,32,0,360,$team);
	} elseif(isset($_sysTeamMaj[$System->SystemID]) && is_array($_sysTeamMaj[$System->SystemID]) && isset($_teamColoursByID[$_sysTeamMaj[$System->SystemID]['tid']])){
		$tcol = explode(',', $_teamColoursByID[$_sysTeamMaj[$System->SystemID]['tid']]);
		$teamRing = imagecolorallocate($image,(int)$tcol[0],(int)$tcol[1],(int)$tcol[2]);
		imagearc($image,$xcoord,$ycoord,30,30,0,360,$teamRing);
	}
}

// Pass 2: Place labels with clash detection
$_placedLabels = [];
$_gap = 18; // offset from circle center (just outside 32px ring)

foreach($_sysData as $sd){
	$System = $sd['sys'];
	$xcoord = $sd['x'];
	$ycoord = $sd['y'];
	$tw = $_fontW * strlen($System->Name);
	$th = $_fontH;

	// 8 candidate positions around the circle
	$candidates = [
		[$xcoord + $_gap, $ycoord + 2],                          // right
		[$xcoord + $_gap, $ycoord - $th - 2],                     // right-above
		[$xcoord - $_gap - $tw, $ycoord + 2],                     // left
		[$xcoord - $_gap - $tw, $ycoord - $th - 2],               // left-above
		[$xcoord - (int)($tw/2), $ycoord + $_gap],                // below-center
		[$xcoord - (int)($tw/2), $ycoord - $_gap - $th],          // above-center
		[$xcoord + $_gap, $ycoord - (int)($th/2)],                // right-center
		[$xcoord - $_gap - $tw, $ycoord - (int)($th/2)],          // left-center
	];

	$bestPos = null;
	foreach($candidates as $c){
		$tx = (int)$c[0];
		$ty = (int)$c[1];
		// Must stay within grid
		if($tx < 1 || $ty < 1 || $tx + $tw > 498 || $ty + $th > 498) continue;
		$labelBox = [$tx, $ty, $tx + $tw, $ty + $th];
		$clash = false;
		// Check against all circle bounding boxes (no extra pad — radius already has 1px margin)
		foreach($_circleBoxes as $cb){
			if(_rectsOverlap($labelBox, $cb, 0)){ $clash = true; break; }
		}
		if($clash) continue;
		// Check against previously placed labels (6px pad for clear separation)
		foreach($_placedLabels as $pl){
			if(_rectsOverlap($labelBox, $pl, 6)){ $clash = true; break; }
		}
		if($clash) continue;
		$bestPos = [$tx, $ty];
		break;
	}

	// Fallback: use first candidate clamped to grid bounds
	if(!$bestPos){
		$tx = max(1, min((int)$candidates[0][0], 498 - $tw));
		$ty = max(1, min((int)$candidates[0][1], 498 - $th));
		$bestPos = [$tx, $ty];
	}

	imagestring($image, 2, $bestPos[0], $bestPos[1], $System->Name, $border);
	$_placedLabels[] = [$bestPos[0], $bestPos[1], $bestPos[0] + $tw, $bestPos[1] + $th];
}
header("Content-type: image/jpg");
imagejpeg($image);
imagedestroy($image);
?>