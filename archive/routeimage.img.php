<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
//$SectorID = $_GET['id'];
//$Systems = GetSystemsInSector($SectorID);
function DrawRoute($image,$colour,$textcolour,$FleetID){
	$sql = "SELECT MovingFrom,Destination,Strategy FROM fleets WHERE(FleetID = '".$_GET['id']."')";
	$res = mysql_query($sql);
	$row = mysql_fetch_object($res);
	$Location = $row->MovingFrom;
	$Destination = substr($row->Destination,2,strlen($row->Destination)-2);
	if(substr($Location,0,2)=="X:"){
		$coords = substr($Location,2,strlen($Location)-2);
		$carr = split("/",$coords);
		$location_x_absolute = $carr[0]/10;
		$location_y_absolute = $carr[1]/10;
	}else{
		$Location = substr($Location,2,strlen($Location)-2);
		
		$sql = "SELECT System FROM planets WHERE(PlanetID = '$Location')";
		$res = mysql_query($sql);
		$row = mysql_fetch_object($res);
		$sql = "SELECT Coords,SectorID,Name FROM Systems WHERE(SystemID = '".$row->System."')";
		$res = mysql_query($sql);
		$row = mysql_fetch_object($res);
		
		$location_sector_coords = GetSectorCoords($row->SectorID);
		$location_system_coords = split("/",$row->Coords);
		$location_system_coords[0] *= 5; // Get x value into units
		$location_system_coords[1] *= 5; // Get y value into units
		
		$location_sector_offset_coords_x = ($location_sector_coords[0]-1)*50;
		$location_sector_offset_coords_y = ($location_sector_coords[1]-1)*50;

		$location_x_absolute = $location_sector_offset_coords_x + $location_system_coords[0];
		$location_y_absolute = $location_sector_offset_coords_y + $location_system_coords[1];
		imagestring($image,2,$location_x_absolute+10,$location_y_absolute+10,$row->Name,$textcolour);
	}

	$sql = "SELECT System FROM planets WHERE(PlanetID = '$Destination')";
	$res = mysql_query($sql);
	$row = mysql_fetch_object($res);
	$sql = "SELECT Coords,SectorID, Name FROM Systems WHERE(SystemID = '".$row->System."')";
	$res = mysql_query($sql);
	$row = mysql_fetch_object($res);
	
	$destination_sector_coords = GetSectorCoords($row->SectorID);
	$destination_system_coords = split("/",$row->Coords);
	$destination_system_coords[0] *= 5; // Get x value into units
	$destination_system_coords[1] *= 5; // Get y value into units
	
	$destination_sector_offset_coords_x = ($destination_sector_coords[0]-1)*50;
	$destination_sector_offset_coords_y = ($destination_sector_coords[1]-1)*50;

	$destination_x_absolute = $destination_sector_offset_coords_x + $destination_system_coords[0];
	$destination_y_absolute = $destination_sector_offset_coords_y + $destination_system_coords[1];
	
	
	imagestring($image,2,$destination_x_absolute+10,$destination_y_absolute+10,$row->Name,$textcolour);
	
	imageline($image,$location_x_absolute,$location_y_absolute,$destination_x_absolute,$destination_y_absolute,$colour);
	imagerectangle($image,$location_x_absolute,$location_y_absolute,$location_x_absolute+1,$location_y_absolute+1,$colour);
	imagerectangle($image,$destination_x_absolute,$destination_y_absolute,$destination_x_absolute+1,$destination_y_absolute+1,$colour);
	$current = GetGalacticLocation($FleetID);

	$coords = substr($current,2,strlen($current)-2);
	$carr = split("/",$coords);
	$carr[0]=$carr[0]/10;
	$carr[1]=$carr[1]/10;

	//die();
	imagerectangle($image,$carr[0]-2,$carr[1]-2,$carr[0]+4,$carr[1]+4,$colour);
}

$image = imagecreatefromjpeg('images/galaxy.jpg') or die("booboo");
$bg = imagecolorallocate($image,0,0,0);
$border = imagecolorallocate($image,255,255,255);
$bordergray = imagecolorallocate($image,153,153,153);
$yellow = imagecolorallocate($image,255,255,51);
$red = imagecolorallocate($image,255,0,0);

//imagefill($image,0,0,$bg);
imagerectangle($image,0,0,499,499,$border);

for($i = 50;$i<500;$i+=50){
	imageline($image,$i,0,$i,500,$bordergray);
}
for($j = 50;$j<500;$j+=50){
	imageline($image,0,$j,500,$j,$bordergray);
}

$sql = "SELECT MovingFrom,Destination,Strategy FROM fleets WHERE(FleetID = '".$_GET['id']."')";
$res = mysql_query($sql);
$row = mysql_fetch_object($res);
switch($row->Strategy){
	case "0":
		$colour = $yellow;
		break;
	case "1":
		$colour = $yellow;
		break;
	case "2":
		$colour = $red;
		break;
	case "3":
		$colour = $red;
		break;
}

DrawRoute($image,$colour,$yellow,$_GET['id']);


header("Content-type: image/jpg");
imagejpeg($image,'',80);
imagedestroy($image);
?>