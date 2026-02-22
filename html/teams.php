<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
//$User = GetUserInfo("nonny");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Teams</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
$Players = ListPlayersInTeam(PlayerTeam(GetPlayerIDFromName($username)));
?>
<p>Players in your team: <a href="team.php?id=<?php echo PlayerTeam(GetPlayerIDFromName($username)); ?>"><?php echo TeamNameFromID(PlayerTeam(GetPlayerIDFromName($username))); ?></a></p>
<?php
foreach($Players as $k=>$Player){
?>
<p><a href="player.php?id=<?php echo $Player; ?>"><?php echo GetPlayerNameFromID($Player); ?></a></p>
<?php
}
?>
</body>
</html>
