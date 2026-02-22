<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
$TeamID = ($_GET['id'] ?? "");
//$Systems = GetSystemsInSector($SectorID);

$image = imagecreate(70,70);

$border = imagecolorallocate($image,255,255,255);
//imagefill($image,0,0,$bg);
imagerectangle($image,0,0,69,69,$border);
if($TeamID>0){
	$c = GetTeamColour($TeamID);
	$col = explode(",",$c);
	
	//$tfill = imagecolorallocatealpha($image,$col[0],$col[1],$col[2],80);
	$tfill = imagecolorallocatealpha($image,$col[0],$col[1],$col[2],40);
	
	imagefilledrectangle($image,1,1,69,69,$tfill);
	//imagestring($image,2,5,5,"Team: ".$TeamID,$border);
}


header("Content-type: image/jpg");
imagejpeg($image);
imagedestroy($image);
?>