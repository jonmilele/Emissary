<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
include_once("session.inc.php");
include_once("alertfunctions.inc.php");

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
		exit;
	}
	if(($_POST['action'] ?? "")=="auction_planet"){
		$pid = GetPlayerIDFromName($username);
		$teamID = (int)PlayerTeam($pid);
		$planetID = (int)($_POST['planet_id'] ?? 0);
		$startBid = max(1, (int)($_POST['start_bid'] ?? 1));
		$defaultTurns = (int)GetGameSetting('default_auction_turns', 5);
		$turns = max(1, (int)($_POST['turns'] ?? $defaultTurns));
		if($teamID < 1){
			SetFlash("You must be in a team to create auctions.");
		} elseif($planetID > 0 && OwnsPlanet($username, $planetID) && !IsHomePlanet($pid, $planetID)){
			// Check cooldown (24h after cancel)
			if(HasAuctionCooldown(3, $planetID)){
				SetFlash("That planet is on a 24-hour auction cooldown.");
			// Check planet not already on auction
			} else {
				$chk = mysqli_query($GLOBALS["conn"], "SELECT AuctionID FROM auctions WHERE Code='3' AND Data='$planetID'");
				if(mysqli_num_rows($chk) == 0){
					$sql = "INSERT INTO auctions(OpenTo, Code, Data, Seller, StartTime, Turns, StartBid, CurrentBid, HighBidder) VALUES('$teamID','3','$planetID','$pid','".time()."','$turns','$startBid','0','0')";
					mysqli_query($GLOBALS["conn"], $sql);
					SetFlash("Planet auction created (visible to your team).");
				} else {
					SetFlash("That planet is already on auction.");
				}
			}
		} else {
			SetFlash("Cannot auction that planet.");
		}
		header("Location: trade.php");
		exit;
	}
	if(($_POST['action'] ?? "")=="cancel_auction"){
		$pid = GetPlayerIDFromName($username);
		$auctionID = (int)($_POST['auction_id'] ?? 0);
		if($auctionID > 0 && CancelAuction($auctionID, $pid)){
			SetFlash("Auction cancelled.");
		} else {
			SetFlash("Cannot cancel that auction.");
		}
		header("Location: trade.php");
		exit;
	}
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Trade Market</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
<?php
$myPID = GetPlayerIDFromName($username);
$myTeamID = (int)PlayerTeam($myPID);
?>
<h3>Team Auctions:</h3>
<?php if($myTeamID > 0): ?>
  <p><?php
  $listing = ListTeamAuctions($myTeamID);
  echo $listing ? $listing : "No active auctions in your team.";
  ?></p>
<?php else: ?>
  <p>Join a team to access auctions.</p>
<?php endif; ?>

<?php
// Show seller's own active auctions with cancel buttons
$myAuctions = [];
$mares = mysqli_query($GLOBALS["conn"], "SELECT * FROM auctions WHERE Seller='$myPID'");
while($ma = mysqli_fetch_object($mares)){
	$end = (int)$ma->StartTime + ((int)$ma->Turns * 1800);
	if(time() < $end) $myAuctions[] = $ma;
}
if(count($myAuctions) > 0):
?>
<h3>Your Active Auctions:</h3>
<?php foreach($myAuctions as $ma):
	$lotDesc = '';
	switch((int)$ma->Code){
		case 1: $sqlsh = "SELECT Type FROM ships WHERE ShipID='".(int)$ma->Data."'"; $rsh = mysqli_query($GLOBALS["conn"], $sqlsh); $rshRow = mysqli_fetch_object($rsh); $lotDesc = $rshRow ? "Ship: ".GetShipTypeString($rshRow->Type) : "Ship #".$ma->Data; break;
		case 3: $lotDesc = "Planet: ".h(GetPlanetNameFromID($ma->Data)); break;
	}
	$maEnd = (int)$ma->StartTime + ((int)$ma->Turns * 1800);
?>
  <p><?php echo $lotDesc; ?> — Bid: <?php echo (int)$ma->CurrentBid > 0 ? $ma->CurrentBid."C" : "none"; ?> — Ends: <?php echo date("M j, H:i", $maEnd); ?>
  <form method="post" action="trade.php" style="display:inline;" onsubmit="return confirm('Cancel this auction? The asset will have a 24h re-auction cooldown.');">
	<input type="hidden" name="action" value="cancel_auction">
	<input type="hidden" name="auction_id" value="<?php echo $ma->AuctionID; ?>">
	<?php echo csrf_token(); ?>
	<input type="submit" value="Cancel">
  </form></p>
<?php endforeach; ?>
<?php endif; ?>

<?php if($myTeamID > 0): ?>
  <h3>Create Planet Auction:</h3>
<?php
$myPlanets = GetPlanetList($myPID);
$homePlanet = GetHomePlanet($myPID);
$defaultTurns = (int)GetGameSetting('default_auction_turns', 5);
// Filter out home planet, planets already on auction, and planets on cooldown
$auctioned = [];
$ares = mysqli_query($GLOBALS["conn"], "SELECT Data FROM auctions WHERE Code='3'");
while($ar = mysqli_fetch_object($ares)) $auctioned[(int)$ar->Data] = true;
$available = [];
if($myPlanets){
	foreach($myPlanets as $p){
		if($p->PlanetID != $homePlanet && !isset($auctioned[$p->PlanetID]) && !HasAuctionCooldown(3, $p->PlanetID)){
			$available[] = $p;
		}
	}
}
if(count($available) > 0):
?>
  <form method="post" action="trade.php">
	<input type="hidden" name="action" value="auction_planet">
	<?php echo csrf_token(); ?>
	<p>Planet: <select name="planet_id">
	<?php foreach($available as $p): ?>
		<option value="<?php echo $p->PlanetID; ?>"><?php echo h($p->Name); ?></option>
	<?php endforeach; ?>
	</select></p>
	<p>Starting Bid (Credits): <input type="text" name="start_bid" value="100" size="6"></p>
	<p>Duration (turns): <input type="text" name="turns" value="<?php echo $defaultTurns; ?>" size="4"></p>
	<p><input type="submit" value="Create Auction"></p>
  </form>
<?php else: ?>
  <p>No planets available to auction (home planet and recently cancelled items excluded).</p>
<?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
