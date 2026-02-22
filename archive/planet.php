<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");

if(isset($_GET['action'])){
	if($_GET['action']=="colonise"){
		$PlanetID = $_GET["id"];
		$Planet = GetPlanet($PlanetID);
		Colonise($PlanetID);
		CheckSystemMajOwner($Planet->System);
		header("Location: planet.php?id=".$PlanetID);
	}
}


if(!IsPlanet($_GET['id'])){
	echo "Not a valid planet ID";
}else{
	if(!OwnsPlanet($username,$_GET['id'])){
		$edit = false;
	}else{
		$edit = true;
	}
	$PlanetID = $_GET['id'];
	$Planet = GetPlanet($PlanetID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Planet: <?php echo GetPlanetNameFromID($PlanetID); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
<?php if(isset($_GET['dorefresh'])){
?>
<meta http-equiv="refresh" content="0;URL=<?php $PHP_SELF;?>?id=<?php echo $PlanetID; ?>">
<?php
}?>
<script language="JavaScript" type="text/JavaScript">
<!--
function MM_jumpMenu(targ,selObj,restore){ //v3.0
  eval(targ+".location='"+selObj.options[selObj.selectedIndex].value+"'");
  if (restore) selObj.selectedIndex=0;
}
//-->
</script>
<script type="text/javascript" src="jsdomenu.js"></script>
<script type="text/javascript" src="jsdomenu.inc.js"></script>
</head>

<body onload="initjsDOMenu()">
<?php
include("header.inc.php");
?>

<h2>Planet: <?php echo GetPlanetNameFromID($PlanetID); ?> </h2>
<div class="side"> 
  <div class="panel" style="width:250px;"> 
    <h3>Basic Stats</h3>
    System: <a href="system.php?id=<?php echo $Planet->System; ?>"><?php echo GetSystemNameFromID($Planet->System); ?></a><br/>
    Size: <?php echo $Planet->Size; ?><br/>
    Owner: <?php echo PlayerProfileLink($Planet->PlayerID); ?><br/>
  </div>
  <?php
if($edit){?>
  <div class="panel" style="width:250px;"> 
    <h3>Owner's Stats</h3>
    Fleets in Orbit: <?php echo FleetsInOrbit($PlanetID); ?><br/>
    Unassigned Ships in Orbit: <?php echo GetShipsInOrbit($PlanetID); ?><br/>
    Orbital Spaces: 0<br/>
    Ground Spaces: <?php echo GridSquares($PlanetID); ?><br>
    Construction Sites: <?php echo Constructions($PlanetID); ?><br>
    <?php
	$Income = GetPlanetIncome($PlanetID);
	?>
    Income (per turn): <br/>
    - <?php echo $Income->Metal; ?> Metal<br/>
    - <?php echo $Income->Mineral; ?> Mineral<br/>
    - <?php echo $Income->Astrium; ?> Astrium<br/>
    <p>Defence HP: <?php echo GetPlanetDefenceStrength($PlanetID); ?><br>
      Attack HP: <?php echo GetPlanetAttackStrength($PlanetID); ?></p>
  </div>
  <div class="panel" style="width:250px"> 
    <h3>Key</h3>
    <p> <font color="#FF0000">Factory</font><br>
      <font color="#0066FF">Laboratory</font><br>
      <font color="#00FF00">Harvester</font><br>
      <font color="#FF9900">Shipyard</font><br>
      Hangar<br>
      <font color="#FFFF00">Shield</font><br>
      <font color="#CC00FF">Pulse Cannon</font><br>
      <font color="#FF99FF">Gigashield</font> <br>
      <font color="#999999">Missile Silo</font></p>
  </div>
  <?php } // Edit?>
  <?php
if(YourFleetsInOrbit($PlanetID)>0){
?>
  <div class="panel" style="width:250px;"> 
    <h3>Fleets in Orbit</h3>
    <ul>
      <?php
  $Fleets = ListYourFleetsInOrbit($PlanetID);
  foreach($Fleets as $k=>$Fleet){
  ?>
      <li> <a href="fleet.php?id=<?php echo $Fleet->FleetID; ?>"><?php echo GetFleetName($Fleet->FleetID); ?></a><br/>
        Actions:<br/>
        <?php if(CanColonise($PlanetID,$Fleet->FleetID)){ ?>
        <a href="planet.php?id=<?php echo $PlanetID; ?>&fleet=<?php echo $Fleet->FleetID; ?>&action=colonise">Colonise</a><br/>
        <?php } // Can Colonise?>
      </li>
      <?php } //Fleet Foreach ?>
    </ul>
  </div>
  <?php } // Fleets in orbit ?>
  <?php
	$Fleets = ListPlayerFleets(GetPlayerIDFromName($username));
	if(sizeof($Fleets)>0){
  ?>
  <div class="panel" style="width:250px;"> 
    <h3>Fleet Functions</h3>
    Send Fleet: 
    <form name="form1" action="fleet.php?action=move" method="post">
      <select name="fleet">
        <?php
	foreach($Fleets as $k=>$Fleet){
	?>
        <option value="<?php echo $Fleet->FleetID ?>" selected><?php echo GetFleetName($Fleet->FleetID); ?></option>
        <?php } //fleet foreach ?>
      </select>
      <select name="strat" id="strat">
        <option value="0">Orbit</option>
        <option value="1">Colonise</option>
        <option value="2">Attack</option>
        <option value="3">Invade</option>
      </select>
      <input type="hidden" name="type" value="P">
      <input type="hidden" name="value" value="<?php echo $PlanetID; ?>">
      <input type="submit" value="Launch">
    </form>
  </div>
  <?php } //Has Fleets ?>
</div>
<div class="planet"><img src="<?php echo GetPlanetPictureFromID($PlanetID); ?>" name="image1" border="0" usemap="#Map" id="image1"/> 
</div>
<map name="Map">
  <?php
$squares = 0;
$startcorner = 0;
$numberinrow = 0;

switch($Planet->Size){
	case "1":
		$squares = 100;
		$startcornerx = 100;
		$startcornery = 50;
		$numberinrow = 10;
		break;
	case "2":
		$squares = 121;
		$startcornerx = 75;
		$startcornery = 75;
		$numberinrow = 11;
		break;
	case "3":
		$squares = 81;
		$startcornerx = 130;
		$startcornery = 85;
		$numberinrow = 9;
		break;
	case "4":
		$squares = 144;
		$startcornerx = 75;
		$startcornery = 25;
		$numberinrow = 12;
		break;
}
if($edit){
	$secid = 1;
	for($i = 0;$i<$numberinrow;$i++){
		for($j = 0;$j<$numberinrow;$j++){
			?>
  <area shape="rect" coords="<?php echo $startcornerx+$j*40; ?>,<?php echo $startcornery+$i*40; ?>,<?php echo $startcornerx+($j*40)+40; ?>,<?php echo $startcornery+($i*40)+40; ?>" id="s<?php echo $secid; ?>" href="building.php?id=<?php echo $secid; ?>&planet=<?php echo $PlanetID; ?>" target="_self"/>
  <?php
			$secid++;
		}	
	}
	//$grids = GetOrbitalGridCoords($Planet->Size);

}
?>
</map>
</body>
</html>
<?php
} //Is Planet
?>
