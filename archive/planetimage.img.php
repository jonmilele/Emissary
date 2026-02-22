<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
//$SectorID = $_GET['id'];
//$Systems = GetSystemsInSector($SectorID);
$PlanetID = $_GET['id'];
$Planet = GetPlanet($PlanetID);

$image = imagecreatefromjpeg("images/planets/".$Planet->Size.".jpg") or die("booboo");
$width = imagesx($image);
$height = imagesy($image);

$squares = 0;
$startcorner = 0;
$numberinrow = 0;

$sql= "SELECT Grids,xstart,ystart,rowsquares FROM planet_types WHERE(Type = '".$Planet->Size."')";
$rescount=mysql_query($sql);
$row = mysql_fetch_object($rescount);
$squares = $row->Grids;
$startcornerx = $row->xstart;
$startcornery = $row->ystart;
$numberinrow = $row->rowsquares;

$bg = imagecolorallocate($image,0,0,0);
$border = imagecolorallocate($image,255,255,255);
$bordergray = imagecolorallocate($image,153,153,153);
$yellow = imagecolorallocate($image,255,255,51);

//imagefill($image,0,0,$bg);
imagerectangle($image,$startcornerx,$startcornery,$startcornerx+$numberinrow*40,$startcornery+$numberinrow*40,$border);

for($i = $startcornerx+40;$i<$startcornerx+$numberinrow*40;$i+=40){
	imageline($image,$i,$startcornery,$i,$startcornery+$numberinrow*40,$bordergray);
}
for($j = $startcornery+40;$j<$startcornery+$numberinrow*40;$j+=40){
	imageline($image,$startcornerx,$j,$startcornerx+$numberinrow*40,$j,$bordergray);
}
switch($Planet->Size){
	case "1":
		$offsetx = 0;
		$offsety = 30;
		break;
	case "2":
		$offsetx = 0;
		$offsety = 80;
		break;
}
/*
//LHS Orbits
$x1 = 20;
$y1 = $offsety+40;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 10;
$y1 = $offsety+120;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 5;
$y1 = $offsety+200;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 10;
$y1 = $offsety+280;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 20;
$y1 = $offsety+360;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);

// RHS Orbits
$x1 = 540;
$y1 = $offsety+40;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 550;
$y1 = $offsety+120;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 555;
$y1 = $offsety+200;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 550;
$y1 = $offsety+280;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
$x1 = 540;
$y1 = $offsety+360;
imagerectangle($image,$x1,$y1,$x1+40,$y1+40,$border);
*/
$gridid = 1;
for($i = 0;$i<$numberinrow;$i++){
	for($j = 0;$j<$numberinrow;$j++){
		$build = GetGridContents($PlanetID, $gridid);
		if($build>0){
			$c = GetBldColour($build);
			
			$hp = GetBldDefaultHP($build);
			$build_hp = GetBldHP($PlanetID,$gridid);
			$cent = ($build_hp/$hp)*100;
			$height = round((0.4*$cent),0);

			$col = split(",",$c);
			
			$tfill = imagecolorallocatealpha($image,$col[0],$col[1],$col[2],80);
			$tborder = imagecolorallocate($image,$col[0],$col[1],$col[2]);
		//	$imgsrc = imagecreatefromjpeg("images/buildings/2.jpg");
		//	imagecolortransparent($imgsrc,imagecolorallocate($imgsrc,255,0,255));
	
			imagefilledrectangle($image,$startcornerx+$j*40,$startcornery+$i*40,$startcornerx+($j*40)+40,$startcornery+($i*40)+40,$tfill);
			imagerectangle($image,$startcornerx+$j*40,$startcornery+$i*40,$startcornerx+($j*40)+40,$startcornery+($i*40)+40,$tborder);
		//	if($build==2){
		//	imagecopy($image,$imgsrc,$startcorner+$j*40,$startcorner+$i*40,0,0,imagesx($imgsrc),imagesy($imgsrc));
		//	}
			imagestring($image,2,$startcornerx+3+$j*40,$startcornery+3+$i*40,$gridid,$border);
			
			if($cent>0){
				$hpfill = imagecolorallocate($image,255,0,0);
			}
			if($cent>40){
				$hpfill = imagecolorallocate($image,255,200,0);
			}if($cent>70){
				$hpfill = imagecolorallocate($image,0,255,0);
			}
			$hpborder = imagecolorallocate($image,255,255,255);
			$hp_x = $startcornerx+($j*40)+36;
			$hp_y = $startcornery+($i*40)+40;
			$hp_nx = $hp_x+4;
			$hp_ny = $hp_y-$height;
			imagefilledrectangle($image,$hp_x,$hp_ny,$hp_nx,$hp_y,$hpfill);
			imagerectangle($image,$hp_x,$hp_ny,$hp_nx,$hp_y,$hpborder);

			
			//echo "Adding grid: ".$gridid;
		}else if(ConstructingBuilding($PlanetID,$gridid)){
			imagestring($image,5,$startcornerx+15+$j*40,$startcornery+15+$i*40,"C",$border);
			$bldarray = BuildingUnderConstruction($PlanetID,$gridid);
			imagestring($image,1,$startcornerx+3+$j*40,$startcornery+32+$i*40,"TTF: ".$bldarray["TTF"],$border);
		}
		$gridid++;
	}	
}
//imagestring($image,2,5,5,"Sector: ".$SectorID,$border);

header("Content-type: image/jpeg");
imagejpeg($image);
imagedestroy($image);
?>