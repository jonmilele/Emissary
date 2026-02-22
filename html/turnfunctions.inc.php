<?php
function GetGameSetting($key, $default = null){
	$key = mysqli_real_escape_string($GLOBALS["conn"], $key);
	$sql = "SELECT setting_value FROM game_settings WHERE setting_key = '$key'";
	$res = mysqli_query($GLOBALS["conn"], $sql);
	if($res){
		$row = mysqli_fetch_object($res);
		if($row) return $row->setting_value;
	}
	return $default;
}

function SetGameSetting($key, $value){
	$key = mysqli_real_escape_string($GLOBALS["conn"], $key);
	$value = mysqli_real_escape_string($GLOBALS["conn"], $value);
	$sql = "REPLACE INTO game_settings(setting_key, setting_value) VALUES('$key', '$value')";
	mysqli_query($GLOBALS["conn"], $sql);
}

function GetTurnInterval(){
	$val = (int)GetGameSetting('turn_interval', 1800);
	return $val > 0 ? $val : 1800;
}

function MinutesToNextTurn(){
	$turn = GetTurnInterval();
	$ts = (int)GetGameSetting('turn_time', 0);
	if($ts == 0){
		ResetTurnTimer();
		$ts = time();
	}
	$next = $ts + $turn;
	$now = time();
	$diff = $next - $now;
	if($diff < 0) return 0;
	return ceil($diff / 60);
}

function ResetTurnTimer(){
	SetGameSetting('turn_time', time());
}
?>
