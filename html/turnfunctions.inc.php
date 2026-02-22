<?php
function GetTurnInterval(){
	$file = __DIR__ . "/turninterval.txt";
	if(file_exists($file)){
		$val = (int)trim(file_get_contents($file));
		if($val > 0) return $val;
	}
	return 1800; // default 30 min
}

function MinutesToNextTurn(){
	$turn = GetTurnInterval();
	$file = __DIR__ . "/turntime.txt";
	if(!file_exists($file)){
		ResetTurnTimer();
	}
	$ts = (int)file_get_contents($file);
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
	$file = __DIR__ . "/turntime.txt";
	file_put_contents($file, time());
}
?>
