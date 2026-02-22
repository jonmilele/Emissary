<?php
include("connect.inc.php");

if(isset($_POST['signup_name']) && isset($_POST['signup_email']) && isset($_POST['signup_pass1']))
{
	$username = strtolower(($_POST['signup_name'] ?? ""));
	$email = ($_POST['signup_email'] ?? "");
	$pass1 = strtolower(($_POST['signup_pass1'] ?? ""));
	$pass2 = strtolower(($_POST['signup_pass2'] ?? ""));
	$country = ($_POST['signup_country'] ?? "");
	$location = ($_POST['signup_location'] ?? "");


	$sqlpos = "SELECT UserName FROM players WHERE(UserName = '$username')";
	$respos = mysqli_query($conn, $sqlpos);
	$row = mysqli_fetch_object($respos);

	if(!$row || $row->UserName == "") // Not exists
	{
		if($pass1 == $pass2)
		{
			$crypttext = password_hash($pass1, PASSWORD_DEFAULT);

			$sqlpos = "INSERT INTO players(UserName,Password,Email,Location,DateJoined,Country,SetupStage) VALUES('$username','$crypttext','$email','$location',NOW(),'$country','1')";
			$respos = mysqli_query($conn, $sqlpos) or die(mysqli_error($conn));
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
