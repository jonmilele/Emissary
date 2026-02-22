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

			// Redirect back to the page they were trying to reach, or home
			$redirect = $_SESSION['redirect_after_login'] ?? 'home.php';
			unset($_SESSION['redirect_after_login']);
			// Sanitize: only allow relative paths (prevent open redirect)
			if(empty($redirect) || $redirect[0] !== '/' || strpos($redirect, '//') !== false){
				$redirect = 'home.php';
			}
			header("Location: " . $redirect);
	}
	else
	{
		$message = "Invalid Username/password combination";
		header("Location: /index.php?msg=".urlencode($message));
	}
?>
