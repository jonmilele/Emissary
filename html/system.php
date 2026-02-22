<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");

if(!IsSystem(($_GET['id'] ?? ""))){
	echo "Not a valid system ID";
}else{
	$SystemID = ($_GET['id'] ?? "");
	CheckSystemMajOwner($SystemID);
	$System = GetSystem($SystemID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>System: <?php echo GetSystemNameFromID($SystemID); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2>System: <?php echo GetSystemNameFromID($SystemID); ?> </h2>

  
    <div class="side">
	<div class="panel" style="width:200px;">
Sector: <a href="sector.php?id=<?php echo $System->SectorID; ?>"><?php echo $System->SectorID; ?></a><br/>
	Fleets in System: <?php echo FleetsInSystem($SystemID); ?><br/>
      Planets: <?php echo PlanetsInSystem($SystemID); ?><br/>
      
  <ol>
    <?php
$Planets = ListPlanetsInSystem($SystemID);

foreach($Planets as $key=>$Planet){
?>
    <li> <a href="planet.php?id=<?php echo $Planet->PlanetID; ?>"><?php echo $Planet->Name; ?></a> 
    </li>
    <?php
}
?>
  </ol>
  <?php if ($System->PlayerID>0){?>
      Owner: <?php echo GetPlayerNameFromID($System->PlayerID); ?><br/>
	  <?php }?> 
	  </div>
    </div>
  

<div class="system"><img src="<?php echo GetSystemPictureFromID($SystemID); ?>"/></div>
</body>
</html>
<?php
} //Is Planet
?>
