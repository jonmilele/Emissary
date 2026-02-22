<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
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
		$secid++;
	}	
}
//imagestring($image,2,5,5,"Sector: ".$SectorID,$border);

header("Content-type: image/jpg");
imagejpeg($image, null, 80);
imagedestroy($image);
?>