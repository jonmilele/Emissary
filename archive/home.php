<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
//$User = GetUserInfo("nonny");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Home</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<p>Home</p>
<p>Welcome <?php echo $username; ?>. [<a href="logout.back.php">Logout</a>]</p>
<p>You control <?php echo GetNumberOfPlanets(GetPlayerIDFromName($username)); ?> 
  planet(s).</p>
  <div class="side">
<div class="panel" style="width:300px;">
<h3>Finance Report</h3>
<?php $income = GetUserIncome(GetPlayerIDFromName($username)); ?>
<p>Income Next Turn: <br/>
<?php echo $income->Metal; ?> Metal<br/>
<?php echo $income->Mineral; ?> Mineral<br/>
<?php echo $income->Astrium; ?> Astrium</p>
</div>
<div class="panel" style="width:300px;">
<h3>Intelligence Report</h3><p>
<?php
$Fleets = GetIncomingEnemyFleets(GetPlayerIDFromName($username));
if(sizeof($Fleets)>0){
	echo "<strong>Incoming Enemy Fleets!</strong><br/>";
	foreach($Fleets as $k=>$Fleet){
		$post = "";
		switch($Fleet->Strategy){
			case "2":
				$post = " to attack";
				break;
			case "3":
				$post = " to invade";
				break;
		}
		echo GetFleetName($Fleet->FleetID)." sent by ".GetPlayerNameFromID($Fleet->PlayerID)." to ".GetPlanetNameFromID(substr($Fleet->Destination,2,strlen($Fleet->Destination)-2)).$post." - ETA: ".$Fleet->TTF." minute(s)<br/>";
	}
}

 ?></p></div></div>
<div class="side">
<div class="panel" style="width: 400px;">
<h3>Activity Reports</h3>
<p><?php echo GetLastTenReportsString(GetPlayerIDFromName($username)); ?></p>
</div></div>

</body>
</html>
