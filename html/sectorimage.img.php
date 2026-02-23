<?php
include(__DIR__ . "/authenticate.inc.php");
include(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");
$SectorID = ($_GET['id'] ?? "");
$Systems = GetSystemsInSector($SectorID);

$image = imagecreate(550,550);
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

$_secName = GetSectorName($SectorID);
imagestring($image,2,5,5,$_secName . " (" . $SectorID . ")",$border);

// Bulk: get team colours for all players in this sector's systems
$_teamColours = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT DISTINCT pl.PlayerID, t.Colour FROM Systems s JOIN players pl ON pl.PlayerID=s.PlayerID JOIN teams t ON t.TeamID=pl.TeamID WHERE s.SectorID='$SectorID' AND s.PlayerID>0 AND pl.TeamID>0");
while($row = mysqli_fetch_object($res)) $_teamColours[(int)$row->PlayerID] = $row->Colour;

// Bulk: team colours by TeamID (for team-only majority)
$_teamColoursByID = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT DISTINCT t.TeamID, t.Colour FROM Systems s JOIN teams t ON t.TeamID=s.TeamID WHERE s.SectorID='$SectorID' AND s.TeamID>0");
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

$myPID = GetPlayerIDFromName($username);
foreach($Systems as $k=>$System){
	$tc = $_teamColours[$System->PlayerID] ?? '128,128,128';
	$col = explode(",",$tc);
	$team = imagecolorallocate($image,(int)$col[0],(int)$col[1],(int)$col[2]);
	
	$coords = $System->Coords;
	$coordarray = explode("/",$coords);
	
	$xcoord = (int)($coordarray[0]*50);
	$ycoord = (int)($coordarray[1]*50);

	imagerectangle($image,$xcoord,$ycoord,$xcoord+1,$ycoord+1,$border);
	imagearc($image,$xcoord,$ycoord,20,20,0,360,$yellow);
	$majOwner = isset($_sysMajOwner[$System->SystemID]) && is_array($_sysMajOwner[$System->SystemID]) ? $_sysMajOwner[$System->SystemID]['pid'] : 0;
	if($majOwner > 0){
		if($majOwner == $myPID){
			imagearc($image,$xcoord,$ycoord,22,22,0,360,$yellow);
		}
		imagearc($image,$xcoord,$ycoord,30,30,0,360,$team);
		imagearc($image,$xcoord,$ycoord,32,32,0,360,$team);
	} elseif(($System->TeamID ?? 0) > 0 && isset($_teamColoursByID[$System->TeamID])){
		// No player majority but team controls system — draw dashed team ring
		$tcol = explode(',', $_teamColoursByID[$System->TeamID]);
		$teamRing = imagecolorallocate($image,(int)$tcol[0],(int)$tcol[1],(int)$tcol[2]);
		imagearc($image,$xcoord,$ycoord,30,30,0,360,$teamRing);
	}
	
	imagestring($image,2,$xcoord+10,$ycoord+10,$System->Name,$border);
}
header("Content-type: image/jpg");
imagejpeg($image);
imagedestroy($image);
?>