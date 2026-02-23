<?php
include_once("session.inc.php");
session_start();
include_once("csrf.inc.php");

if(empty($_SESSION['username'])) {
		// Remember where the user was trying to go so we can redirect back after login
		$_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
		header("Location: /index.php");
		exit;
	}

// Session timeout check
if(isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT){
	session_unset();
	session_destroy();
	session_start();
	SetFlash("Session expired");
	header("Location: /index.php");
	exit;
}
$_SESSION['last_activity'] = time();

// Validate session fingerprint (IP + user agent)
if(isset($_SESSION['ip']) && ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR'] || ($_SESSION['ua'] ?? '') !== ($_SERVER['HTTP_USER_AGENT'] ?? ''))){
	session_unset();
	session_destroy();
	session_start();
	SetFlash("Session invalidated");
	header("Location: /index.php");
	exit;
}

$username = $_SESSION['username'];

// Home world / game over checks (skip on pages that handle these states)
$_currentPage = basename($_SERVER['PHP_SELF'] ?? '');
if(!in_array($_currentPage, ['sethome.php', 'gameover.php', 'logout.back.php'])){
	include_once("connect.inc.php");
	include_once("userfunctions.inc.php");
	$_myPID = GetPlayerIDFromName($username);
	if($_myPID > 0){
		$_numPlanets = GetNumberOfPlanets($_myPID);
		if($_numPlanets == 0){
			header("Location: /gameover.php");
			exit;
		}
		if(NeedsHomePlanet($_myPID)){
			header("Location: /sethome.php");
			exit;
		}
	}
}
