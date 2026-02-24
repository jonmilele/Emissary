<?php
include("authenticate.inc.php");
include("connect.inc.php");
include_once("userfunctions.inc.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Galaxy Map — Zoom</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="style.css" rel="stylesheet" type="text/css">
<style>
  .galaxy-zoom { overflow: auto; padding: 15px; }
  .galaxy-grid { display: grid; grid-template-columns: repeat(10, 500px); grid-template-rows: repeat(10, 500px); gap: 0; width: 5000px; }
  .galaxy-grid a { display: block; line-height: 0; }
  .galaxy-grid img { width: 500px; height: 500px; display: block; border: none; }
</style>
</head>
<body>
<?php include("header.inc.php"); ?>
<h2>Galaxy Map — Zoom</h2>
<div class="galaxy-zoom">
<div class="galaxy-grid">
<?php
$secid = 1;
for($i = 0; $i < 10; $i++){
  for($j = 0; $j < 10; $j++){
    $name = htmlspecialchars(GetSectorName($secid));
    echo '<a href="sector.php?id=' . $secid . '" title="' . $name . '">';
    echo '<img src="sectorimage.img.php?id=' . $secid . '" alt="' . $name . '">';
    echo '</a>';
    $secid++;
  }
}
?>
</div>
</div>
</body>
</html>
