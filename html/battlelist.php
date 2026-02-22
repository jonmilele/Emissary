<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Battles</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php");?>
<h1>Battles</h1>
<?php
$PlayerID = GetPlayerIDFromName($username);
$sql = "SELECT * FROM battles WHERE(Attacker = '".$PlayerID."' OR Defender = '".$PlayerID."')";
$res = mysqli_query($GLOBALS["conn"], $sql);
while($row = mysqli_fetch_object($res)){
?>
<p><a href="battle.php?id=<?php echo $row->BattleID; ?>">The battle of <?php echo GetPlanetNameFromID($row->PlanetID); ?></a></p>
<?php
}
?>
</body>
</html>
