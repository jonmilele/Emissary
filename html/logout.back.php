<?php
include_once("session.inc.php");
session_start();
session_unset();
session_destroy();
header("Location: index.php?msg=Logged+Out");
