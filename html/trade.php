<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
include_once("session.inc.php");
include_once("alertfunctions.inc.php");

// Resource credit exchange rates from game settings
$_mcv = (int)GetGameSetting('metal_credit_value', 1);
$_ncv = (int)GetGameSetting('mineral_credit_value', 10);
$_acv = (int)GetGameSetting('astrium_credit_value', 100);

if(isset($_POST['action']) && csrf_validate()){
	if(($_POST['action'] ?? "")=="cash"){
		$Metal = (int)($_POST['metal'] ?? 0);
		$Mineral = (int)($_POST['mineral'] ?? 0);
		$Astrium = (int)($_POST['astrium'] ?? 0);
		if(($Metal>0)||($Mineral>0)||($Astrium>0)){
			if(HasSufficientResources(GetPlayerIDFromName($username),1,$Metal)&&HasSufficientResources(GetPlayerIDFromName($username),2,$Mineral)&&HasSufficientResources(GetPlayerIDFromName($username),3,$Astrium)){
				AddUserCredits(GetPlayerIDFromName($username),$Metal*$_mcv);
				DeductResources(GetPlayerIDFromName($username),1,$Metal);
				
				AddUserCredits(GetPlayerIDFromName($username),$Mineral*$_ncv);
				DeductResources(GetPlayerIDFromName($username),2,$Mineral);
				
				AddUserCredits(GetPlayerIDFromName($username),$Astrium*$_acv);
				DeductResources(GetPlayerIDFromName($username),3,$Astrium);
			}
		}
		header("Location: trade.php");
	}
	if(($_POST['action'] ?? "")=="buy"){
		$Metal = (int)($_POST['metal'] ?? 0);
		$Mineral = (int)($_POST['mineral'] ?? 0);
		$Astrium = (int)($_POST['astrium'] ?? 0);
		if(($Metal>0)||($Mineral>0)||($Astrium>0)){
			$_totalCost = ($Astrium*$_acv) + ($Metal*$_mcv) + ($Mineral*$_ncv);
			if(GetUserCredits(GetPlayerIDFromName($username)) >= $_totalCost){
				DeductUserCredits(GetPlayerIDFromName($username),$Metal*$_mcv);
				AddResources(GetPlayerIDFromName($username),1,$Metal);
				
				DeductUserCredits(GetPlayerIDFromName($username),$Mineral*$_ncv);
				AddResources(GetPlayerIDFromName($username),2,$Mineral);
				
				DeductUserCredits(GetPlayerIDFromName($username),$Astrium*$_acv);
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
	} elseif($planetID > 0 && OwnsPlanet($username, $planetID)){
			// Check cooldown (24h after cancel)
			if(HasAuctionCooldown(3, $planetID)){
				SetFlash("That planet is on a 24-hour auction cooldown.");
			// Check planet not already on auction
			} else {
				$chk = mysqli_query($GLOBALS["conn"], "SELECT AuctionID FROM auctions WHERE Code='3' AND Data='$planetID'");
				if(mysqli_num_rows($chk) == 0){
					$sql = "INSERT INTO auctions(OpenTo, Code, Data, Seller, StartTime, Turns, StartBid, CurrentBid, HighBidder) VALUES('$teamID','3','$planetID','$pid','".time()."','$turns','$startBid','0','0')";
					mysqli_query($GLOBALS["conn"], $sql);
					$_flashMsg = "Planet auction created (visible to your team).";
					if(IsHomePlanet($pid, $planetID)){
						$_flashMsg .= " WARNING: This is your Home World!";
					}
					SetFlash($_flashMsg);
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
<meta name="viewport" content="width=device-width, initial-scale=1">
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
		Metal @ <?php echo $_mcv; ?>C per Metal</p>
	  <p><input name="mineral" type="text" id="mineral" value="0" size="4">
		Mineral @ <?php echo $_ncv; ?>C per Mineral</p>
		<p>
		  <input name="astrium" type="text" id="astrium" value="0" size="4">
		  Astrium @ <?php echo $_acv; ?>C per Astrium</p>
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
        Metal @ <?php echo $_mcv; ?>C</p>
	  <p><input name="mineral" type="text" id="mineral" value="0" size="4">
        Mineral @ <?php echo $_ncv; ?>C</p>
		<p>
		  <input name="astrium" type="text" id="astrium" value="0" size="4">
        Astrium @ <?php echo $_acv; ?>C</p>
		<p>
		  <input type="submit" name="Submit" value="Buy">
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
// Filter out planets already on auction and planets on cooldown
$auctioned = [];
$ares = mysqli_query($GLOBALS["conn"], "SELECT Data FROM auctions WHERE Code='3'");
while($ar = mysqli_fetch_object($ares)) $auctioned[(int)$ar->Data] = true;
$available = [];
$planetValues = [];
if($myPlanets){
	foreach($myPlanets as $p){
		if(!isset($auctioned[$p->PlanetID]) && !HasAuctionCooldown(3, $p->PlanetID)){
			$planetValues[$p->PlanetID] = GetPlanetValue($p->PlanetID);
			$available[] = $p;
		}
	}
}
if(count($available) > 0):
$firstValue = $planetValues[$available[0]->PlanetID];
$preselect = isset($_GET['auction_planet']) ? (int)$_GET['auction_planet'] : 0;
// If a planet was pre-selected, use its value as default bid
if($preselect > 0 && isset($planetValues[$preselect])){
	$firstValue = $planetValues[$preselect];
}
?>
  <form method="post" action="trade.php" id="auction_planet_form">
	<input type="hidden" name="action" value="auction_planet">
	<?php echo csrf_token(); ?>
	<p>Planet: <select name="planet_id" id="auction_planet_sel" onchange="document.getElementById('auction_start_bid').value=this.options[this.selectedIndex].dataset.value">
	<?php foreach($available as $p): ?>
		<?php $_tTip = PlanetValueTooltip($p->PlanetID); ?>
		<option value="<?php echo $p->PlanetID; ?>" data-value="<?php echo $planetValues[$p->PlanetID]; ?>" title="<?php echo htmlspecialchars($_tTip); ?>"<?php if($preselect == $p->PlanetID) echo ' selected'; ?>><?php echo h($p->Name); ?><?php if($p->PlanetID == $homePlanet) echo ' [HOME WORLD]'; ?> (value: <?php echo number_format($planetValues[$p->PlanetID]); ?>C)</option>
	<?php endforeach; ?>
	</select></p>
	<p>Starting Bid (Credits): <input type="text" name="start_bid" id="auction_start_bid" value="<?php echo $firstValue; ?>" size="8"></p>
	<p>Duration (turns): <input type="text" name="turns" value="<?php echo $defaultTurns; ?>" size="4"></p>
	<p><input type="submit" value="Create Auction" onclick="var sel=document.getElementById('auction_planet_sel');var opt=sel.options[sel.selectedIndex];var isHome=opt.text.indexOf('[HOME WORLD]')>=0;var msg=isHome?'WARNING: You are auctioning your HOME WORLD! If sold you will need to select a new home world. Proceed?':'Create auction for this planet? Your team will be able to bid.';return confirm(msg);"></p>
  </form>
<?php if($preselect > 0): ?>
<script>document.getElementById('auction_planet_form').scrollIntoView({behavior:'smooth'});</script>
<?php endif; ?>
<?php else: ?>
  <p>No planets available to auction (recently cancelled items excluded).</p>
<?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
