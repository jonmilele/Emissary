<?php
session_start();

if(empty($_SESSION['username'])) {
		header("Location: index.php?msg=Not+Logged+In");
		exit;
	}
$username = $_SESSION['username'];
?>
