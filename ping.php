<?php 
	require "config.php";
	require "classes.php";
	require "network.php";
	
	$send="";
	
	$id = $_GET['id'];
	$r = "";
	if ($id) {
		$r = ping($id);
		//$r = tcpSend("ping:$id");
	}
	echo $r;

?>