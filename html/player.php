<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
//$User = GetUserInfo("nonny");
$PlayerID = ($_GET['id'] ?? "");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Player: <?php echo GetPlayerNameFromID($PlayerID); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Player: <?php echo GetPlayerNameFromID($PlayerID); ?></h2>
<div class="side">
<h3>Statistics</h3>
<p>Race:</p>
<p>Home Planet:</p>
<p>Score: 0 </p>
<p>Planets Owned: <?php echo GetNumberOfPlanets($PlayerID); ?></p>
<p>Sectors Owned: 0</p>
<p>Fleets Owned: 0</p></div>
<div class="planet">
<h3>Planets</h3>
<?php
$Planets = GetPlanetList($PlayerID);
foreach($Planets as $key=>$Planet){
?>
<p><a href="planet.php?id=<?php echo $Planet->PlanetID; ?>"><?php echo $Planet->Name; ?></a></p>
<?php
}
?></div>
</body>
</html>
