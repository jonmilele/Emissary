<?php
session_start();

if(empty($_SESSION['username'])) {
		// Remember where the user was trying to go so we can redirect back after login
		$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
		header("Location: /index.php");
		exit;
	}
$username = $_SESSION['username'];
?>
