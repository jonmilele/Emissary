<?php
function MinutesToNextTurn(){
	$turn = 1800;
	$fp = fopen("turntime.txt","r");
	$ts = fread($fp,filesize("turntime.txt"));
	$ts = $ts+$turn;
	fclose($fp);
	
	$now = time();
	$diff = $ts - $now;
	$minutes = ceil($diff/60);
	return $minutes;
}
?>