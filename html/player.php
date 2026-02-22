<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
//$User = GetUserInfo("nonny");
$PlayerID = ($_GET['id'] ?? "");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Player: <?php echo GetPlayerNameFromID($PlayerID); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Player: <?php echo GetPlayerNameFromID($PlayerID); ?></h2>
<div class="side">
<h3>Statistics</h3>
<?php
$homePlanetID = GetHomePlanet($PlayerID);
?>
<?php
$playerTeamID = PlayerTeam($PlayerID);
?>
<p>Team: <?php
if($playerTeamID > 0){
	echo '<a href="team.php?id=' . $playerTeamID . '">' . htmlspecialchars(TeamNameFromID($playerTeamID)) . '</a>';
	echo ' <img src="teamcolour.img.php?id=' . $playerTeamID . '" style="vertical-align:middle;" width="20" height="10">';
} else {
	echo 'None';
}
?></p>
<p>Home Planet: <?php
if($homePlanetID > 0){
	echo '<a href="planet.php?id=' . $homePlanetID . '">' . htmlspecialchars(GetPlanetNameFromID($homePlanetID)) . '</a>';
} else {
	echo 'None';
}
?></p>
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
