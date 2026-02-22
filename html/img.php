<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
$PlanetID = 2;
$Planet = GetPlanet($PlanetID);
$image = imagecreatefromjpeg("images/planets/1.jpg") or die("booboo");
$width = imagesx($image);
$height = imagesy($image);

$squares = 0;
$startcorner = 0;
$numberinrow = 0;

$sql= "SELECT Grids,xstart,ystart,rowsquares FROM planet_types WHERE(Type = '".$Planet->Size."')";
$rescount=mysqli_query($GLOBALS["conn"], $sql);
$row = mysqli_fetch_object($rescount);
$squares = $row->Grids;
$startcornerx = $row->xstart;
$startcornery = $row->ystart;
$numberinrow = $row->rowsquares;
$bg = imagecolorallocate($image,0,0,0);
$border = imagecolorallocate($image,255,255,255);
$bordergray = imagecolorallocate($image,153,153,153);
$yellow = imagecolorallocate($image,255,255,51);

header("Content-type: image/jpeg");
imagejpeg($image);
imagedestroy($image);

?>