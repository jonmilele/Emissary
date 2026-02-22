<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
include("resourcefunctions.inc.php");

$myPID = GetPlayerIDFromName($username);

// If player somehow has planets, redirect away
if(GetNumberOfPlanets($myPID) > 0){
	header("Location: home.php");
	exit;
}

if(isset($_POST['action']) && csrf_validate()){
	if($_POST['action'] == "buyplanet"){
		$planetID = BuyPlanet($myPID);
		if($planetID > 0){
			header("Location: planet.php?id=$planetID&msg=Planet+purchased!+This+is+your+new+home+world.");
			exit;
		}
		header("Location: gameover.php?msg=Could+not+purchase+planet.+Check+resources.");
		exit;
	}
}

$resources = GetPlayerResources($myPID);
$canBuy = ($resources["Metal"] >= 2000 && $resources["Mineral"] >= 1000 && $resources["Astrium"] >= 200);
$myTeamID = PlayerTeam($myPID);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>All Planets Lost</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<p>[<a href="home.php">Home</a>]&nbsp;&nbsp;&nbsp;[<a href="logout.back.php">Logout</a>]</p>
<?php PrintMessage($_GET["msg"] ?? "");?>

<h1>All Planets Lost</h1>
<p>You have lost all your planets. You can recover by purchasing a new planet or requesting a donation from your team.</p>

<div class="panel" style="width:500px;">
<h3>Buy a Planet</h3>
<p>Purchase a random unclaimed planet to start over.</p>
<p>Cost: <strong>2,000 Metal</strong> / <strong>1,000 Mineral</strong> / <strong>200 Astrium</strong></p>
<p>Your resources: <?php echo $resources["Metal"]; ?> Metal / <?php echo $resources["Mineral"]; ?> Mineral / <?php echo $resources["Astrium"]; ?> Astrium</p>
<?php if($canBuy): ?>
<form method="POST" action="gameover.php" onsubmit="return confirm('Purchase a random unclaimed planet?');">
<input type="hidden" name="action" value="buyplanet">
<?php echo csrf_token(); ?>
<input type="submit" value="Buy Planet">
</form>
<?php else: ?>
<p><em>Insufficient resources.</em></p>
<?php endif; ?>
</div>

<?php if($myTeamID > 0): ?>
<div class="panel" style="width:500px;">
<h3>Request Team Donation</h3>
<p>Ask your team (<strong><?php echo htmlspecialchars(TeamNameFromID($myTeamID)); ?></strong>) for a planet donation.</p>
<p>Contact your team leader <strong><?php echo htmlspecialchars(GetPlayerNameFromID(GetTeamLeader($myTeamID))); ?></strong> to arrange a planet transfer.</p>
</div>
<?php endif; ?>

<?php if(!$canBuy && $myTeamID == 0): ?>
<div class="panel" style="width:500px;">
<h3>Game Over</h3>
<p>You have no planets, insufficient resources to buy one, and no team to request a donation from.</p>
<p>You may wait for resource income from trade, or <a href="logout.back.php">log out</a>.</p>
</div>
<?php endif; ?>

</body>
</html>
