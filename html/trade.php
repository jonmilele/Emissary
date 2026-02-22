<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");

if(isset($_POST['action']) && csrf_validate()){
	if(($_POST['action'] ?? "")=="cash"){
		$Metal = ($_POST['metal'] ?? "");
		$Mineral = ($_POST['mineral'] ?? "");
		$Astrium = ($_POST['astrium'] ?? "");
		if(($Metal>0)||($Mineral>0)||($Astrium>0)){
			if(HasSufficientResources(GetPlayerIDFromName($username),1,$Metal)&&HasSufficientResources(GetPlayerIDFromName($username),2,$Mineral)&&HasSufficientResources(GetPlayerIDFromName($username),3,$Astrium)){
				AddUserCredits(GetPlayerIDFromName($username),$Metal);
				DeductResources(GetPlayerIDFromName($username),1,$Metal);
				
				AddUserCredits(GetPlayerIDFromName($username),$Mineral*10);
				DeductResources(GetPlayerIDFromName($username),2,$Mineral);
				
				AddUserCredits(GetPlayerIDFromName($username),$Astrium*100);
				DeductResources(GetPlayerIDFromName($username),3,$Astrium);
			}
		}
		header("Location: trade.php");
	}
	if(($_POST['action'] ?? "")=="buy"){
		$Metal = ($_POST['metal'] ?? "");
		$Mineral = ($_POST['mineral'] ?? "");
		$Astrium = ($_POST['astrium'] ?? "");
		if(($Metal>0)||($Mineral>0)||($Astrium>0)){
if(GetUserCredits(GetPlayerIDFromName($username))>=(($Astrium*100)+$Metal+($Mineral*10))){
				DeductUserCredits(GetPlayerIDFromName($username),$Metal);
				AddResources(GetPlayerIDFromName($username),1,$Metal);
				
				DeductUserCredits(GetPlayerIDFromName($username),$Mineral*10);
				AddResources(GetPlayerIDFromName($username),2,$Mineral);
				
				DeductUserCredits(GetPlayerIDFromName($username),$Astrium*100);
				AddResources(GetPlayerIDFromName($username),3,$Astrium);
			}
		}
		header("Location: trade.php");
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Trade Market</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h1>Trade Market</h1>
<p>Use this page to trade resources with other players or the bank. <br/>
You have <?php echo GetUserCredits(GetPlayerIDFromName($username)); ?> Credit(s)</p>
<div class="side">
	<div class="panel" style="width:300px;">
	  <h3>Cash Resources:</h3>
	<form name="form1" method="post" action="trade.php">
	  <input type="hidden" name="action" value="cash">
	  <?php echo csrf_token(); ?>
	  <p><input name="metal" type="text" id="metal" value="0" size="4">
		Metal @ 1C per Metal</p>
	  <p><input name="mineral" type="text" id="mineral" value="0" size="4">
		Mineral @ 10C per Mineral</p>
		<p>
		  <input name="astrium" type="text" id="astrium" value="0" size="4">
		  Astrium @ 100C per Astrium</p>
		<p>
		  <input type="submit" name="Submit" value="Cash">
		</p>
	</form>
	</div>
	<div class="panel" style="width:300px;">
	  <h3>Buy Resources:</h3>
	<form name="form2" method="post" action="trade.php">
	  <input type="hidden" name="action" value="buy">
	  <?php echo csrf_token(); ?>
	  <p><input name="metal" type="text" id="metal" value="0" size="4">
        Metal @ 1C</p>
	  <p><input name="mineral" type="text" id="mineral" value="0" size="4">
        Mineral @ 10C</p>
		<p>
		  <input name="astrium" type="text" id="astrium" value="0" size="4">
        Astrium @ 100C</p>
		<p>
		  <input type="submit" name="Submit" value="Cash">
		</p>
	</form>
	</div>
</div>
<div class="planet" style="width:500px;">
<h3>Open Trades:</h3>
  <h3>Auctions:</h3>
  <p>[Create an Auction]</p>
  <p><?php echo ListPublicAuctions(); ?></p>
</div>
</body>
</html>
