<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(!IsSector(($_GET['id'] ?? ""))){
	echo "Not a valid sector ID";
}else{
	$SectorID = ($_GET['id'] ?? "");
	//$Planet = GetPlanet($SectorID);
	$TeamID = GetSectorMajOwnerTeam($SectorID)
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Sector: <?php echo $SectorID; ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2>Sector: <?php echo $SectorID; ?> </h2>
<div class="side">
  <p>Fleets in Sector: 0<br/>
    Systems in Sector: <?php echo SystemsInSector($SectorID); ?><br/>
    Planets in Sector: <?php echo PlanetsInSector($SectorID); ?><br/>
    Stakeholders: <br/>
	<ul>
	<?php 
	$stakeholders = ListSectorStakeHolders($SectorID);
	foreach($stakeholders as $k=>$stake){
		$s = "";
		if($stake["Count"]>1){
			$s = "s";
		}
		echo "<li>".$stake["Name"]." [".$stake["Count"]." planet".$s."]</li>";
	}
	?></ul>
    <?php
$owner = CalcMajOwner($SectorID);
if($owner ==0){
	$strowner = "None";
}else{
	$strowner = "<a href=\"player.php?id=".$owner."\">".GetPlayerNameFromID($owner)."</a>";
}
?>
    Majority Owner: <?php echo $strowner; ?><br/>
	<?php if($TeamID>0){?>
    Team Controlling:<br/>
    <a href="team.php?id=<?php echo $TeamID; ?>"><?php echo TeamNameFromID($TeamID); ?></a></p>
  <p><img src="teamcolour.img.php?id=<?php echo $TeamID; ?>"><br/>
  </p><?php }?>
</div>
<div class="planet"><img border="0" src="<?php echo GetSectorPictureFromID($SectorID); ?>" style="margin:15px;" usemap="#Map"/>
<map name="Map">
<?php
$Systems = GetSystemsInSector($SectorID);
foreach($Systems as $k=>$System){
	$coords = $System->Coords;
	$coordarray = explode("/",$coords);
	
	$xcoord = $coordarray[0]*50;
	$ycoord = $coordarray[1]*50; //$xcoord+","+$ycoord
?>
  <area shape="circle" coords="<?php echo $xcoord; ?>,<?php echo $ycoord; ?>,10" href="system.php?id=<?php echo $System->SystemID; ?>">
<?php
}
?>
</map></div>
</body>
</html>
<?php
} //Is Planet
?>
