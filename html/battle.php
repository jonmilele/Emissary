<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$BattleID = ($_GET["id"] ?? "");
$Battle = GetBattle($BattleID);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Battle Report</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php");?>
<h1>Battle Report</h1>
<h2>Planet: <?php echo GetPlanetNameFromID($Battle->PlanetID); ?></h2>
<div class="side">
<div class="panel">
    <h3>Battle Report</h3>
    <p>Planet: <?php echo GetPlanetNameFromID($Battle->PlanetID); ?><br>
      Attacker: <?php echo GetPlayerNameFromID($Battle->Attacker); ?><br>
      Defender: <?php echo GetPlayerNameFromID($Battle->Defender); ?><br>
      Winner: <?php echo GetPlayerNameFromID($Battle->Winner); ?></p>
  </div>
</div>
<div class="planet">
  <h2>Battle Log</h2>
  <p><?php
	$logSql = "SELECT Log FROM battles WHERE BattleID = '" . mysqli_real_escape_string($conn, $BattleID) . "'";
	$logRes = mysqli_query($conn, $logSql);
	$logRow = $logRes ? mysqli_fetch_object($logRes) : null;
	echo $logRow ? $logRow->Log : 'Battle log not available.';
	?></p>
</div>
</body>
</html>
