<?php
include(__DIR__ . "/authenticate.inc.php");
include(__DIR__ . "/connect.inc.php");
include_once(__DIR__ . "/userfunctions.inc.php");
//$SectorID = ($_GET['id'] ?? "");
//$Systems = GetSystemsInSector($SectorID);
function DrawRoute($image,$colour,$textcolour,$FleetID){
	$sql = "SELECT MovingFrom,Destination,Strategy FROM fleets WHERE(FleetID = '".($_GET['id'] ?? "")."')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row || !$row->MovingFrom || !$row->Destination) return;
	$Location = $row->MovingFrom;
	$Destination = substr($row->Destination,2,strlen($row->Destination)-2);
	$locName = '';
	if(substr($Location,0,2)=="X:"){
		$coords = substr($Location,2,strlen($Location)-2);
		$carr = explode("/",$coords);
		$location_x_absolute = (int)($carr[0]/10);
		$location_y_absolute = (int)($carr[1]/10);
	}else{
		$Location = substr($Location,2,strlen($Location)-2);
		
		$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Location')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return;
		$sql = "SELECT Coords,SectorID,Name FROM Systems WHERE(SystemID = '".$row->System."')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return;
		
		$location_sector_coords = GetSectorCoords($row->SectorID);
		$location_system_coords = explode("/",$row->Coords);
		$location_system_coords[0] *= 5; // Get x value into units
		$location_system_coords[1] *= 5; // Get y value into units
		
		$location_sector_offset_coords_x = ($location_sector_coords[0]-1)*50;
		$location_sector_offset_coords_y = ($location_sector_coords[1]-1)*50;

		$location_x_absolute = (int)($location_sector_offset_coords_x + $location_system_coords[0]);
		$location_y_absolute = (int)($location_sector_offset_coords_y + $location_system_coords[1]);
		$locName = $row->Name ?? '';
		imagestring($image,2,$location_x_absolute+10,$location_y_absolute+10,$locName,$textcolour);
	}

	$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$Destination')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return;
	$sql = "SELECT Coords,SectorID, Name FROM Systems WHERE(SystemID = '".$row->System."')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row) return;
	
	$destination_sector_coords = GetSectorCoords($row->SectorID);
	$destination_system_coords = explode("/",$row->Coords);
	$destination_system_coords[0] *= 5; // Get x value into units
	$destination_system_coords[1] *= 5; // Get y value into units
	
	$destination_sector_offset_coords_x = ($destination_sector_coords[0]-1)*50;
	$destination_sector_offset_coords_y = ($destination_sector_coords[1]-1)*50;

	$destination_x_absolute = (int)($destination_sector_offset_coords_x + $destination_system_coords[0]);
	$destination_y_absolute = (int)($destination_sector_offset_coords_y + $destination_system_coords[1]);
	
	$destName = $row->Name ?? '';
	imagestring($image,2,$destination_x_absolute+10,$destination_y_absolute+10,$destName,$textcolour);
	
	imageline($image,$location_x_absolute,$location_y_absolute,$destination_x_absolute,$destination_y_absolute,$colour);
	imagerectangle($image,$location_x_absolute,$location_y_absolute,$location_x_absolute+1,$location_y_absolute+1,$colour);
	imagerectangle($image,$destination_x_absolute,$destination_y_absolute,$destination_x_absolute+1,$destination_y_absolute+1,$colour);
	$current = GetGalacticLocation($FleetID);

	$coords = substr($current,2,strlen($current)-2);
	$carr = explode("/",$coords);
	$carr[0]=(int)($carr[0]/10);
	$carr[1]=(int)($carr[1]/10);

	imagerectangle($image,$carr[0]-2,$carr[1]-2,$carr[0]+4,$carr[1]+4,$colour);
}

function DrawLocation($image,$colour,$textcolour,$FleetID){
	$sql = "SELECT Location FROM fleets WHERE(FleetID = '$FleetID')";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	$row = mysqli_fetch_object($res);
	if(!$row || !$row->Location) return;
	$loc = $row->Location;
	if(substr($loc,0,2)=="P:"){
		$pid = substr($loc,2);
		$sql = "SELECT `System` FROM planets WHERE(PlanetID = '$pid')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return;
		$sql = "SELECT Coords,SectorID,Name FROM Systems WHERE(SystemID = '".$row->System."')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return;
		$sector_coords = GetSectorCoords($row->SectorID);
		$sys_coords = explode("/",$row->Coords);
		$x = (int)(($sector_coords[0]-1)*50 + $sys_coords[0]*5);
		$y = (int)(($sector_coords[1]-1)*50 + $sys_coords[1]*5);
		$name = $row->Name ?? '';
	}elseif(substr($loc,0,2)=="S:"){
		$sid = substr($loc,2);
		$sql = "SELECT Coords,SectorID,Name FROM Systems WHERE(SystemID = '$sid')";
		$res = mysqli_query($GLOBALS["conn"], $sql);
		$row = mysqli_fetch_object($res);
		if(!$row) return;
		$sector_coords = GetSectorCoords($row->SectorID);
		$sys_coords = explode("/",$row->Coords);
		$x = (int)(($sector_coords[0]-1)*50 + $sys_coords[0]*5);
		$y = (int)(($sector_coords[1]-1)*50 + $sys_coords[1]*5);
		$name = $row->Name ?? '';
	}else{
		return;
	}
	imagerectangle($image,$x-3,$y-3,$x+3,$y+3,$colour);
	imagestring($image,2,$x+8,$y-6,$name,$textcolour);
}

$image = imagecreatefromjpeg(__DIR__ . '/images/galaxy.jpg') or die("booboo");
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

$_fleetID = ($_GET['id'] ?? "");
$sql = "SELECT Location,MovingFrom,Destination,Strategy FROM fleets WHERE(FleetID = '$_fleetID')";
$res = mysqli_query($GLOBALS["conn"], $sql);
$row = mysqli_fetch_object($res);
if($row && $row->Destination != ''){
	// In transit — draw route
	switch($row->Strategy){
		case "2": case "3": $colour = $red; break;
		default: $colour = $yellow; break;
	}
	DrawRoute($image,$colour,$yellow,$_fleetID);
}else{
	// Stationary — draw location marker
	DrawLocation($image,$yellow,$yellow,$_fleetID);
}


header("Content-type: image/jpg");
imagejpeg($image, null, 80);
imagedestroy($image);
?>