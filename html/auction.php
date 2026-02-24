<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
include_once("session.inc.php");
include_once("alertfunctions.inc.php");

$_viewerPID = GetPlayerIDFromName($username);
$_viewerTeam = (int)PlayerTeam($_viewerPID);

// Handle cancel
if(isset($_POST['cancel_auction']) && csrf_validate()){
	$auctionID = (int)($_GET['id'] ?? 0);
	if($auctionID > 0 && CancelAuction($auctionID, $_viewerPID)){
		SetFlash("Auction cancelled.");
	} else {
		SetFlash("Cannot cancel that auction.");
	}
	header("Location: trade.php");
	exit;
}

// Handle bid submission
if(isset($_POST['bid']) && csrf_validate()){
	$auctionID = (int)($_GET['id'] ?? 0);
	if(IsAuction($auctionID)){
		$auc = GetAuction($auctionID);
		$bidderPID = $_viewerPID;
		$bidAmount = (int)$_POST['bid'];
		$end = $auc->StartTime + ($auc->Turns * 1800);
		$minBid = ($auc->CurrentBid > 0) ? (int)ceil($auc->CurrentBid * 1.1) : (int)$auc->StartBid;
		// Team gate: bidder must be in same team as auction
		if($_viewerTeam < 1 || $_viewerTeam != (int)$auc->OpenTo){
			SetFlash("You are not in the same team as this auction.");
		} elseif(time() > $end){
			SetFlash("This auction has ended.");
		} elseif($bidderPID == $auc->Seller){
			SetFlash("You cannot bid on your own auction.");
		} elseif($bidAmount < $minBid){
			SetFlash("Minimum bid is {$minBid}C.");
		} elseif(GetUserCredits($bidderPID) < $bidAmount){
			SetFlash("Insufficient credits.");
		} else {
			// Refund previous high bidder
			if($auc->HighBidder > 0 && $auc->CurrentBid > 0){
				AddUserCredits($auc->HighBidder, $auc->CurrentBid);
				AddAlert((int)$auc->HighBidder, 'trade', 'You have been outbid on auction #'.$auctionID.'. '.$auc->CurrentBid.'C refunded.', 'auction.php?id='.$auctionID);
			}
			// Deduct from new bidder and update auction
			DeductUserCredits($bidderPID, $bidAmount);
			$sql = "UPDATE auctions SET CurrentBid='$bidAmount', HighBidder='$bidderPID' WHERE AuctionID='$auctionID'";
			mysqli_query($GLOBALS["conn"], $sql);
			SetFlash("Bid of {$bidAmount}C placed.");
		}
	}
	header("Location: auction.php?id=$auctionID");
	exit;
}

if(!IsAuction(($_GET['id'] ?? ""))){
	echo "Not a valid auction ID";
}else{
	$Auction = GetAuction(($_GET['id'] ?? ""));
	// Team gate: viewer must be in same team
	if($_viewerTeam < 1 || $_viewerTeam != (int)$Auction->OpenTo){
		echo "This auction is restricted to another team.";
		exit;
	}
	$end = $Auction->StartTime + ($Auction->Turns*1800);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Auction</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
<script language="JavaScript" type="text/JavaScript">
<!--
function popupMsg(objName) { //v1.0
	var obj = findObj(objName);
	if(confirm("Are you sure you wish to bid "+obj.value+"C on this item?")){
		document.form1.submit();
	}
}

function findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}
//-->
</script>
</head>

<body>
<?php
include("header.inc.php");
?>
<h1>Auction </h1>
<?php
$lot = "";
// Auction codes: 1=Ship, 2=Resources (not implemented), 3=Planet
switch($Auction->Code){
	case "1": // Ship auction
		$sqlship = "SELECT * FROM ships WHERE(ShipID = '".$Auction->Data."')";
		$resship = mysqli_query($GLOBALS["conn"], $sqlship);
		$rowship = mysqli_fetch_object($resship);
		$lot = GetShipTypeString($rowship->Type);
		break;
	case "2": // Resource auction (not implemented)
		break;
	case "3": // Planet auction
		$lot = "Planet: " . h(GetPlanetNameFromID($Auction->Data));
		break;
}
?>
<p>Lot: <?php echo $lot; ?><br>
  Seller: <?php echo h(GetPlayerNameFromID($Auction->Seller)); ?><br>
  Start Time: <?php echo date("F j, H:i",$Auction->StartTime); ?><br>
  Ends: <?php echo date("F j, H:i",$end); ?><br>
  Start Bid: <?php echo $Auction->StartBid; ?><br>
  Highest Bid: <?php echo $Auction->CurrentBid; ?> by <?php echo h(GetPlayerNameFromID($Auction->HighBidder)); ?></p>
<?php if(time() < $end): ?>
<?php if($_viewerPID != $Auction->Seller): ?>
<form name="form1" method="post" action="auction.php?id=<?php echo $Auction->AuctionID; ?>">
  <?php echo csrf_token(); ?>
  Bid:
<?php $minBid = ($Auction->CurrentBid > 0) ? (int)ceil($Auction->CurrentBid * 1.1) : (int)$Auction->StartBid; ?>
<input name="bid" type="text" value="<?php echo $minBid; ?>" size="7">
  <input name="Submit" type="button" onClick="popupMsg('bid')" value="Make Bid">
</form>
<?php endif; ?>
<?php if($_viewerPID == $Auction->Seller): ?>
<form method="post" action="auction.php?id=<?php echo $Auction->AuctionID; ?>" onsubmit="return confirm('Cancel this auction? The asset will have a 24h re-auction cooldown.');">
  <?php echo csrf_token(); ?>
  <input type="hidden" name="cancel_auction" value="1">
  <input type="submit" value="Cancel Auction">
</form>
<?php endif; ?>
<?php else: ?>
<p><strong>This auction has ended.</strong></p>
<?php endif; ?>
</body>
</html>
<?php } ?>
