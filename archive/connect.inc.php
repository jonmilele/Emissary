<?php
$hostname_conn = "localhost";
$database_conn = "emissary_game";
$username_conn = "emissary_game";
$password_conn = "REDACTED";
$conn = mysql_connect($hostname_conn, $username_conn, $password_conn) or die(mysql_error());
mysql_select_db("emissary_game",$conn) or die(mysql_error());
?>