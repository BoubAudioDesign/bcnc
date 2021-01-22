<?php

error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));

require_once "config.php";
require_once "classes.php";
require_once "functions.php";
//require "network.php";

session_start();
$sid = session_id();
$puid = $_SESSION['puid'];
session_write_close();

if ($puid!=""){
	/*
	if ($_GET['p']) {
		//$cmd .= ":".$_GET["p"]; 
        $puid .= "/".$_GET['p']; // Ajouter le debugID
	}
    */
    
    //$cmd = "ID:" . $puid . ";" . $_GET["c"];
	$cmd = "ID:" . $puid . ";" . $_GET["c"] . ':' . $_GET["p"] ;

	//$buffer = tcpSend($cmd);

	$buffer = processMessage($cmd);

	echo $buffer;
} else {
	echo "401: No pUID";
}

	
?>