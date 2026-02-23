<?php
// Secure session configuration — include before session_start()
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');
// Uncomment when HTTPS is enabled:
// ini_set('session.cookie_secure', 1);

define('SESSION_TIMEOUT', 3600); // 1 hour

function SetFlash($msg){
	$_SESSION['flash_msg'] = $msg;
}

function GetFlash(){
	if(isset($_SESSION['flash_msg'])){
		$msg = $_SESSION['flash_msg'];
		unset($_SESSION['flash_msg']);
		return $msg;
	}
	return '';
}
