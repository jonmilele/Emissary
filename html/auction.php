<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
if(!IsAuction(($_GET['id'] ?? ""))){
	echo "Not a valid auction ID";
}else{
	$Auction = GetAuction(($_GET['id'] ?? ""));
	$end = $Auction->StartTime + ($Auction->Turns*1800);
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Auction</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
switch($Auction->Code){
	case "1":
		$sqlship = "SELECT * FROM ships WHERE(ShipID = '".$Auction->Data."')";
		$resship = mysqli_query($GLOBALS["conn"], $sqlship);
		$rowship = mysqli_fetch_object($resship);
		$lot = GetShipTypeString($rowship->Type);
		break;
	case "2":
		break;
}
?>
<p>Lot: <?php echo $lot; ?><br>
  Seller: <?php echo h(GetPlayerNameFromID($Auction->Seller)); ?><br>
  Start Time: <?php echo date("F j, H:i",$Auction->StartTime); ?><br>
  Ends: <?php echo date("F j, H:i",$end); ?><br>
  Start Bid: <?php echo $Auction->StartBid; ?><br>
  Highest Bid: <?php echo $Auction->CurrentBid; ?> by <?php echo h(GetPlayerNameFromID($Auction->HighBidder)); ?></p>
<form name="form1" method="post">
  Bid:
<input name="bid" type="text" value="<?php echo $Auction->CurrentBid*1.1; ?>" size="7">
  <input name="Submit" type="button" onClick="popupMsg('bid')" value="Make Bid">
</form>
</body>
</html>
<?php } ?>
