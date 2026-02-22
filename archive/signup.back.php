<?php
include("connect.inc.php");

if(isset($_POST[signup_name]) && isset($_POST[signup_email]) && isset($_POST[signup_pass1]))
{
	$username = strtolower($_POST[signup_name]);
	$email = $_POST[signup_email];
	$pass1 = strtolower($_POST[signup_pass1]);
	$pass2 = strtolower($_POST[signup_pass2]);
	$country = $_POST[signup_country];
	$location = $_POST[signup_location];
	
	
	$sqlpos = "SELECT UserName FROM players WHERE(UserName = '$username')";
	$respos = mysql_query($sqlpos);
	$row = mysql_fetch_object($respos);
	
	if($row->UserName == "") // Not exists
	{
		if($pass1 == $pass2)
		{
			$iv = mcrypt_create_iv (mcrypt_get_iv_size (MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND);
			$key = "nonnyrules";
			$text = $pass1;
			$crypttext = mcrypt_encrypt (MCRYPT_RIJNDAEL_256, $key, $text, MCRYPT_MODE_ECB, $iv);
			
			$sqlpos = "INSERT INTO players(UserName,Password,Email,Location,DateJoined,Country,SetupStage) VALUES('$username','$crypttext','$email','$location',NOW(),'$country','1')";
			$respos = mysql_query($sqlpos) or die(mysql_error());
			echo "Signup complete! Proceed to step 2.";
		}
		else
		{
			echo "Passwords do not Match";
		}
	}
	else
	{
		echo "Username Exists";
	}
}
?>