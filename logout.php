<?php

session_start();

/* Destroy all session data */
$_SESSION = array();

/* Destroy session */
session_destroy();

/* Go to login page */
header("Location: login.php");
exit;

?>