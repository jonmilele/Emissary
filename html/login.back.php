<?php
session_start();
include("connect.inc.php");
	$user_name = strtolower(($_POST['login_username'] ?? ""));
	$password = strtolower(($_POST['login_password'] ?? ""));

	//set up the query
	$query = "SELECT UserName,Email,Password FROM players WHERE(UserName='$user_name')";

	//run the query and get the number of affected rows
	$notresult = mysqli_query($conn, $query) or die(mysqli_error($conn));
	$result = mysqli_fetch_object($notresult);

	if($result && password_verify($password, $result->Password))
	{
			//add the user to our session variables
			$_SESSION['username'] = $user_name;
			header("Location: home.php");
	}
	else
	{
		$message = "Invalid Username/password combination, ";
		header("Location: index.php?msg=".$message);
	}
?>
