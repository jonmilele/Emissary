<?php 
include("../connect.inc.php");
include("../userfunctions.inc.php");

$PlanetID = ($_GET["id"] ?? "");

$image = imagecreatefromjpeg('shield.jpg') or die("booboo");
$bg = imagecolorallocate($image,255,255,255);
$shields = CountShields($PlanetID);

$width  = ImageFontWidth(2) * strlen($shields);
$height = ImageFontHeight(2);

$iwidth = imagesx($image);
$iheight = imagesy($image);
$x = ($iwidth/2)-($width/2);
$y = ($iheight/2)-($height/2);
imagestring($image,2,(int)$x,(int)$y,$shields,$bg);

header("Content-type: image/jpeg");
imagejpeg($image,null,80);
imagedestroy($image);
?>