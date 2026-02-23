<?php
include_once("session.inc.php");
session_start();
session_unset();
session_destroy();
session_start();
SetFlash("Logged Out");
header("Location: index.php");
