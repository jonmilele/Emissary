<?php
// Global settings cache — loaded once per request
$GLOBALS['_gs_cache'] = null;

function GetGameSetting($key, $default = null){
	if($GLOBALS['_gs_cache'] === null){
		$GLOBALS['_gs_cache'] = [];
		$res = mysqli_query($GLOBALS["conn"], "SELECT setting_key, setting_value FROM game_settings");
		if($res){
			while($row = mysqli_fetch_object($res)){
				$GLOBALS['_gs_cache'][$row->setting_key] = $row->setting_value;
			}
		}
	}
	return array_key_exists($key, $GLOBALS['_gs_cache']) ? $GLOBALS['_gs_cache'][$key] : $default;
}

function SetGameSetting($key, $value){
	$key = mysqli_real_escape_string($GLOBALS["conn"], $key);
	$value = mysqli_real_escape_string($GLOBALS["conn"], $value);
	$sql = "REPLACE INTO game_settings(setting_key, setting_value) VALUES('$key', '$value')";
	mysqli_query($GLOBALS["conn"], $sql);
	$GLOBALS['_gs_cache'] = null; // invalidate cache
}

function LoadAllSettings(){
	$settings = [];
	$res = mysqli_query($GLOBALS["conn"], "SELECT setting_key, setting_value FROM game_settings ORDER BY setting_key");
	if($res){
		while($row = mysqli_fetch_object($res)){
			$settings[$row->setting_key] = $row->setting_value;
		}
	}
	return $settings;
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
