<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
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
$secid = 1;
for($i = 0;$i<10;$i++){
	for($j = 0;$j<10;$j++){
		$owner = GetSectorMajOwnerTeam($secid);
		if($owner>0){
			$c = GetTeamColour($owner);
			$col = explode(",",$c);
			
			$tfill = imagecolorallocatealpha($image,$col[0],$col[1],$col[2],80);
			$tborder = imagecolorallocate($image,$col[0],$col[1],$col[2]);
			
			imagefilledrectangle($image,$j*50,$i*50,($j*50)+50,($i*50)+50,$tfill);
			imagerectangle($image,$j*50,$i*50,($j*50)+50,($i*50)+50,$tborder);
		}
		// Highlight sectors where the current user owns at least one system
		$sql = "SELECT COUNT(*) AS count FROM Systems WHERE SectorID='$secid' AND PlayerID='$myPID'";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if($row && $row->count > 0){
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