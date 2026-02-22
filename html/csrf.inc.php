<?php
// CSRF token helpers — included via authenticate.inc.php

function csrf_token(){
	if(empty($_SESSION['csrf_token'])){
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
}

function csrf_validate(){
	if(empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])){
		return false;
	}
	return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
