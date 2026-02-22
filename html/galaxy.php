<?php
include("authenticate.inc.php");
include("connect.inc.php");
include("userfunctions.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>The Galaxy</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<?php
include("header.inc.php");
?>
<div class="side">
Stakeholders: 0<br/>
Majority Owner: None<br/>
</div>
<div class="galaxy"><img src="galaxyimage.img.php" border="0" usemap="#Map" style="margin:15px;"/>
<map name="Map">
<?php
$secid = 1;
for($i = 0;$i<10;$i++){
	for($j = 0;$j<10;$j++){
		?>
		 <area shape="rect" title="Sector: <?php echo $secid; ?>" coords="<?php echo $j*50; ?>,<?php echo $i*50; ?>,<?php echo ($j*50)+50; ?>,<?php echo ($i*50)+50; ?>" href="sector.php?id=<?php echo $secid; ?>">
		<?php
		$secid++;
	}	
}
?>
  </map></div>
</body>
</html>

