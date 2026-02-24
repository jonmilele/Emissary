<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

$myPID = GetPlayerIDFromName($username);

if(isset($_POST['action']) && csrf_validate()){
	if($_POST['action'] == "sethome"){
		$planetID = (int)($_POST['planetid'] ?? 0);
		if($planetID > 0 && SetHomePlanet($myPID, $planetID)){
			AddAlert($myPID, 'system', GetPlanetNameFromID($planetID).' set as home world', 'planet.php?id='.$planetID);
			SetFlash("Home world set");
			header("Location: planet.php?id=$planetID");
			exit;
		}
		SetFlash("Could not set home world");
		header("Location: sethome.php");
		exit;
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Select Home World</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>
<body>
<?php include("header.inc.php"); ?>

<h1>Select Your Home World</h1>
<p>Your home world receives <strong>2x resource production</strong> and <strong>+50% building HP</strong> in combat.</p>
<p>You must select a home world to continue.</p>

<?php
$Planets = GetPlanetList($myPID);
if(count($Planets) == 0){
	echo "<p>You have no planets. Redirecting...</p>";
	echo "<script>window.location='gameover.php';</script>";
} else {
	foreach($Planets as $Planet){
		$income = GetPlanetIncome($Planet->PlanetID);
?>
<div class="panel" style="width:500px;">
<strong><a href="planet.php?id=<?php echo $Planet->PlanetID; ?>"><?php echo htmlspecialchars($Planet->Name); ?></a></strong>
 — System: <?php echo htmlspecialchars(GetSystemNameFromID($Planet->System)); ?>
 — Size: <?php echo $Planet->Size; ?>
 — Income: <?php echo $income->Metal; ?>M / <?php echo $income->Mineral; ?>Mi / <?php echo $income->Astrium; ?>A
<form method="POST" action="sethome.php" style="display:inline; margin-left:10px;">
<input type="hidden" name="action" value="sethome">
<input type="hidden" name="planetid" value="<?php echo $Planet->PlanetID; ?>">
<?php echo csrf_token(); ?>
<input type="submit" value="Set as Home World">
</form>
</div>
<?php
	}
}
?>

</body>
</html>
