<?php
include(__DIR__ . "/authenticate.inc.php");
include(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");
//$SectorID = ($_GET['id'] ?? "");
//$Systems = GetSystemsInSector($SectorID);

$image = imagecreatefromjpeg(__DIR__ . '/images/galaxy.jpg') or die("booboo");
$bg = imagecolorallocate($image,0,0,0);
$border = imagecolorallocate($image,255,255,255);
$bordergray = imagecolorallocate($image,153,153,153);
$yellow = imagecolorallocate($image,255,255,51);

//imagefill($image,0,0,$bg);
imagerectangle($image,0,0,499,499,$border);

for($i = 50;$i<500;$i+=50){
	imageline($image,$i,0,$i,500,$bordergray);
}
for($j = 50;$j<500;$j+=50){
	imageline($image,0,$j,500,$j,$bordergray);
}
$myPID = GetPlayerIDFromName($username);

// Bulk: team with most planets per sector + their colour
$_sectorTeam = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT s.SectorID, pl.TeamID, t.Colour, COUNT(*) AS cnt FROM Systems s JOIN planets p ON p.`System`=s.SystemID JOIN players pl ON pl.PlayerID=p.PlayerID JOIN teams t ON t.TeamID=pl.TeamID WHERE p.PlayerID>0 AND pl.TeamID>0 GROUP BY s.SectorID, pl.TeamID ORDER BY s.SectorID, cnt DESC");
while($row = mysqli_fetch_object($res)){
	$sid = (int)$row->SectorID;
	if(!isset($_sectorTeam[$sid])){
		$_sectorTeam[$sid] = ['colour' => $row->Colour, 'cnt' => (int)$row->cnt];
	} elseif((int)$row->cnt == $_sectorTeam[$sid]['cnt']){
		$_sectorTeam[$sid] = null; // tied
	}
}

// Bulk: sectors where current player owns planets
$_mySecHighlight = [];
$res = mysqli_query($GLOBALS["conn"], "SELECT DISTINCT s.SectorID FROM Systems s JOIN planets p ON p.`System`=s.SystemID WHERE p.PlayerID='$myPID'");
while($row = mysqli_fetch_object($res)) $_mySecHighlight[(int)$row->SectorID] = true;

$secid = 1;
for($i = 0;$i<10;$i++){
	for($j = 0;$j<10;$j++){
		if(!empty($_sectorTeam[$secid])){
			$col = explode(",",$_sectorTeam[$secid]['colour']);
			$tfill = imagecolorallocatealpha($image,(int)$col[0],(int)$col[1],(int)$col[2],80);
			$tborder = imagecolorallocate($image,(int)$col[0],(int)$col[1],(int)$col[2]);
			imagefilledrectangle($image,$j*50,$i*50,($j*50)+50,($i*50)+50,$tfill);
			imagerectangle($image,$j*50,$i*50,($j*50)+50,($i*50)+50,$tborder);
		}
		if(isset($_mySecHighlight[$secid])){
			imagerectangle($image,$j*50,$i*50,($j*50)+50,($i*50)+50,$yellow);
			imagerectangle($image,$j*50+1,$i*50+1,($j*50)+49,($i*50)+49,$yellow);
		}
		$secid++;
	}	
}
//imagestring($image,2,5,5,"Sector: ".$SectorID,$border);

header("Content-type: image/jpg");
imagejpeg($image, null, 80);
imagedestroy($image);
?>