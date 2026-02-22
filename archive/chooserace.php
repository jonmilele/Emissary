<?php
include("connect.inc.php");
include("setupfunctions.inc.php");

if(!StageTwo()){
	echo "Error: Incorrect Setup Stage Access";
}else{
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Untitled Document</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="style.css" rel="stylesheet" type="text/css">
</head>

<body>
<h2>2. Race Creation</h2>
<p>On this page you will create your race.</p>
<form name="form1" method="post" action="">
  Race Name: 
  <input type="text" name="textfield">
</form>
<p>&nbsp;</p>
<p>&nbsp;</p>
</body>
</html>
<?php
} //Stage Check
?>