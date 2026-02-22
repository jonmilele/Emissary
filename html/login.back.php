<?php
include_once("session.inc.php");
session_start();
include("connect.inc.php");
	$user_name = strtolower(($_POST['login_username'] ?? ""));
	$password = ($_POST['login_password'] ?? "");

	// Prepared statement to prevent SQL injection
	$stmt = mysqli_prepare($conn, "SELECT UserName, Email, Password FROM players WHERE UserName = ?");
	mysqli_stmt_bind_param($stmt, "s", $user_name);
	mysqli_stmt_execute($stmt);
	$notresult = mysqli_stmt_get_result($stmt);
	$result = mysqli_fetch_object($notresult);
	mysqli_stmt_close($stmt);

	if($result && password_verify($password, $result->Password))
	{
			// Regenerate session ID to prevent session fixation
			session_regenerate_id(true);

			//add the user to our session variables
			$_SESSION['username'] = $user_name;
			$_SESSION['last_activity'] = time();
			$_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
			$_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

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
