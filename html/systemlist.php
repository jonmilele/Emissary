<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo h($username);?>'s Systems</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<h2><?php echo h($username);?>'s Systems</h2>
<?php
$Systems = GetSystemList($username);
foreach($Systems as $key=>$System){
?>
<p><a href="system.php?id=<?php echo $System->SystemID; ?>"><?php echo h($System->Name); ?></a></p>
<?php
}
?>
<h2>Known Systems</h2>
<?php
$KSystems = GetKnownSystems($username);
foreach($KSystems as $key=>$KSystem){
?>
<p><a href="system.php?id=<?php echo $KSystem->SystemID; ?>"><?php echo h($KSystem->Name); ?></a></p>
<?php
}
?>
</body>
</html>
