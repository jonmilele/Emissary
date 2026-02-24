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
<title>Player: <?php echo h(GetPlayerNameFromID($PlayerID)); ?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Player: <?php echo h(GetPlayerNameFromID($PlayerID)); ?></h2>
<div class="side">
<h3>Statistics</h3>
<?php
$homePlanetID = GetHomePlanet($PlayerID);
?>
<?php
$playerTeamID = PlayerTeam($PlayerID);
$_ranking = GetPlayerRanking($PlayerID);
?>
<p>Rank: <strong>#<?php echo $_ranking['rank']; ?></strong> of <?php echo $_ranking['totalPlayers']; ?>
&mdash; Net Worth: <strong><?php echo number_format($_ranking['totalValue']); ?>C</strong>
<small style="color:#888;">(Planets: <?php echo number_format($_ranking['planetValue']); ?>C | Resources: <?php echo number_format($_ranking['resourceValue']); ?>C)</small>
<br/><small><a href="leaderboard.php">View Leaderboard</a></small>
</p>
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
<?php
$Planets = GetPlanetList($PlayerID);
$_totalPlanetValue = 0;
if($Planets){
	foreach($Planets as $_tp) $_totalPlanetValue += GetPlanetValue($_tp->PlanetID);
}
?>
<p>Planets Owned: <?php echo GetNumberOfPlanets($PlayerID); ?></p>
<p>Total Planet Value: <strong><?php echo number_format($_totalPlanetValue); ?>C</strong></p>
</div>
<div class="planet">
<h3>Planets</h3>
<?php
if($Planets){
foreach($Planets as $key=>$Planet){
	$_pv = GetPlanetValue($Planet->PlanetID);
	$_pvTip = PlanetValueTooltip($Planet->PlanetID);
?>
<p><a href="planet.php?id=<?php echo $Planet->PlanetID; ?>"><?php echo h($Planet->Name); ?></a> <small style="color:#888; cursor:help; border-bottom:1px dotted #666;" title="<?php echo htmlspecialchars($_pvTip); ?>">(<?php echo number_format($_pv); ?>C)</small></p>
<?php
}
}
?></div>
</body>
</html>
