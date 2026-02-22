<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Fleets</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php include("header.inc.php"); ?>
<h2>Fleets:</h2>
<?php
$Fleets = ListPlayerFleets(GetPlayerIDFromName($username));
foreach($Fleets as $k=>$Fleet){
?>
<p><a href="fleet.php?id=<?php echo $Fleet->FleetID; ?>"><?php echo GetFleetName($Fleet->FleetID); ?></a> - <?php echo GetFleetLocationString($Fleet->FleetID); ?></p>
<?php
}
?>
<p>&nbsp;</p>
</body>
</html>
