<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
//$User = GetUserInfo("nonny");
$TeamID = $_GET['id'];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Team: <?php echo TeamNameFromID($TeamID); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Team: <?php echo TeamNameFromID($TeamID); ?></h2>
<div class="side">
<h3>Statistics</h3>
<p>Colour: <br/><img src="teamcolour.img.php?id=<?php echo $TeamID; ?>"></p>
<p>Score: 0 </p>
<p>Planets Owned: <?php echo GetNumberOfPlanetsInTeam($TeamID); ?></p>
<p>Sectors Owned: 0</p>
<p>Fleets Owned: 0</p></div>
<div class="planet">
<h3>Planets</h3>
</div>
</body>
</html>
