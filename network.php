<?php
require "config.php";

function tcpSend($cmd) {
	global $srvAddress, $srvPort;
	
	if ($cmd) {	
		$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		//$socket_set_timeout($socket,2);
		if ($socket === false) {
			return "501: socket_create() a échoué : raison :  " . socket_strerror(socket_last_error()) . "\n";
		} 
		$result = socket_connect($socket, $srvAddress, $srvPort) or die( "socket_connect() a échoué : raison : ($result) " . socket_strerror(socket_last_error($socket)) );
		
		//$welcome = socket_read($socket, 1024);
		
		$send = $cmd."\n";
		socket_write($socket,$send, strlen($send));
	
		$out = socket_read($socket, 2048);
		socket_close($socket);
		
		return $out;
	}
}

function ping1 ($id=0) {
	global $srvAddress, $srvPort;
	
	$sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

	$msg = "Ping:".$id."\n";
	$len = strlen($msg);

	socket_sendto($sock, $msg, $len, 0, $srvAddress, $srvPort);
	socket_close($sock);
}

function ping($id=0) {
	global $srvAddress, $srvPort;
	
	$fp = fsockopen($srvAddress, $srvPort);

	if (!$fp) { print "Can't connect to that damn host !!!"; exit();}

	fputs($fp, "Ping:".$id."\n\n");
	
	while (!feof ($fp)) {
		$buffer = fgets($fp, 4096);
	}
	
	fclose($fp);
	
	return $buffer;
}
?>