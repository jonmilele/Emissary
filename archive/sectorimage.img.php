<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
$SectorID = $_GET['id'];
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

imagestring($image,2,5,5,"Sector: ".$SectorID,$border);

foreach($Systems as $k=>$System){
	$col = split(",",GetTeamColour(PlayerTeam($System->PlayerID)));
	$team = imagecolorallocate($image,$col[0],$col[1],$col[2]);
	
	$coords = $System->Coords;
	$coordarray = split("/",$coords);
	
	$xcoord = $coordarray[0]*50;
	$ycoord = $coordarray[1]*50; //$xcoord+","+$ycoord

	imagerectangle($image,$xcoord,$ycoord,$xcoord+1,$ycoord+1,$border);
	imagearc($image,$xcoord,$ycoord,20,20,0,360,$yellow);
	if(CheckSystemMajOwner($System->SystemID)>0){
		$Player = CheckSystemMajOwner($System->SystemID);
		if($Player["Player"]==GetPlayerIDFromName($username)){
			imagearc($image,$xcoord,$ycoord,22,22,0,360,$yellow);
		}
		imagearc($image,$xcoord,$ycoord,30,30,0,360,$team);
		imagearc($image,$xcoord,$ycoord,32,32,0,360,$team);
	}
	
	imagestring($image,2,$xcoord+10,$ycoord+10,$System->Name,$border);
	//imagestring($image,2,$xcoord+10,$ycoord+25,$coordarray[0].",".$coordarray[1],$border);
}
header("Content-type: image/jpg");
imagejpeg($image);
imagedestroy($image);
?>