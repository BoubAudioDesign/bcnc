#!/usr/bin/php -q
<?php
error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));

require "classes.php";
require_once "functions.php";
 
/* Autorise l'exécution infinie du script, en attente de connexion. */
set_time_limit(0);
 
/**
 * Check dependencies
 */
if( ! extension_loaded('sockets' ) ) {
	_log( "This program requires sockets extension (http://www.php.net/manual/en/sockets.installation.php)");
	exit(-1);
}

if( ! extension_loaded('pcntl' ) ) {
	_log( "This program requires PCNTL extension (http://www.php.net/manual/en/pcntl.installation.php)");
	exit(-1);
}

_log( "Server start...");


/**
 * Connection handler
 */
function onConnect( $client ) {
	global $jeu, $joueurs, $equipes, $jToken;
	
	$pid = pcntl_fork();
	
	if ($pid == -1) {
		 die('could not fork');
	} else if ($pid) {
		// parent process
		return;
	}
	
	$read = '';
	_log(sprintf( "[%s] Connected at port %d", $client->getAddress(), $client->getPort() ));
	
	while( true ) {
		$read = $client->read();
		if( $read != '' ) {
			
			//echo ( '[' . date( DATE_RFC822 ) . '] ' . $read  );
			$res = processMessage($read, $client);
			if ($res == 999) { break;}
		}
		else {
			break;
		}
		
		if( preg_replace( '/[^a-z]/', '', $read ) == 'exit' ) {
			break;
		}
		if( $read === null ) {
			
			_log(sprintf( "[%s] Disconnected", $client->getAddress() ));
			return false;
		}
		else {
			_log(sprintf( "[%s] recieved: %s", $client->getAddress(), trim($read )));
		}
	}
	$client->close();
	_log(sprintf( "[%s] Disconnected", $client->getAddress() ));

}

require "sock/SocketServer.php";

$server = new \Sock\SocketServer($srvPort, $srvAddress);
$server->init();
$server->setConnectionHandler( 'onConnect' );
$server->listen();
 
/***************************************************************************************/

function processMessage($buf, $client) {		
	//global $jeu, $joueurs, $equipes, $tapis,  $state;
	foreach ($GLOBALS as $key => $val) { global $$key; }
	
	$res = 0;
	$params = array();
	$noStore = false;
	
	/* Commandes */
	if (strpos($buf,"ID")==1) {
		$clientID = substr($buf,4,8);
		$buf = substr($buf,10);
	}
	
	if (strpos($buf,":")>0) {
		$cmd = strtoupper(substr($buf,0,strpos($buf,":")));
		$sparams = trim(substr($buf,strpos($buf,":")+1));
		if (strpos($sparams,";")>0) {
			_log( "; found");
			$params = explode(';',$sparams);
		} else {
			$params[0] = $sparams;
		}
	} else {
		$cmd = strtoupper(trim($buf));
	}
	_log( 'CMD : '.$cmd." (".$sparams.")");
	$l = "";
	for ($z=0; $z<count($params); $z++) {
		$l .= sprintf('%d=(%s) ',$z,$params[$z]);
	}
	_log( $l );
	
	if ($cmd == 'PING') {
		$rep = '800: PONG';
		$noStore=true;
		$res = 999;
	}
	/*
	
		Effacer tous les fichiers temporaires
	
	*/
	if ($cmd == 'CLEARTMP') {
		//unlink($tmpDir.'bcnc*.*');
		array_map('unlink', glob($tmpDir."bcnc*.*"));
		$rep = '200: Temp files cleared';
	}
	/*
	
		Démarrage d'une nouvelle partie :
		- création d'un jeu neuf
		- Mélange
		- Coupe
		
		- Sauvegarde du stack
	
	*/
	if ($cmd == 'START') {
		// Préparer un jeu de cartes
		$state = new state();
		$table = new table();
		$jeu = new jeu;
		_log( "-- Jeu neuf");	
		_log( "-- Melange + coupe");
		$jeu->melangeJeu();
		$jeu->coupeJeu(10);
		// Créer 4 joueurs
		for ($i=0; $i<4; $i++) {
			$joueurs[$i] = new joueur("Joueur ".($i+1),$placeStr[$i]);
		}
		for ($i=0; $i<2; $i++) {
			$equipes[$i] = new equipe($i, $joueurs[$i],$joueurs[$i+2]);
		}
		_log(sprintf("START -- Joueurs count %d",sizeof($GLOBALS['joueurs'])));
		$jToken = 0;
		//saveJoueurs($joueurs);
		//$jeu->save();
		storeContext();
		$rep="200: OK";
		$cmd="DISTRIB";
		$params[0]=4;
	}
	/*
		Charge un stack (jeu complet) à partr d'une sauvegarde
	*/
	if ($cmd =='LOAD') {
		$jeu = new jeu;
		$jeu->load();
		
		$rep = "200: Game loaded";
	}
	
	restoreContext();
	
	if ($cmd == 'STACK') {
		if ($jeu) {
			$rep = '200: '.$jeu->tout();
		} else {
			$rep = '400: Not started';
		}
		//printf("STACK -- Joueurs count %d\n",sizeof($GLOBALS['joueurs']));
	}
	
	/*
	
	*/
	
	if ($cmd == 'JOUEUR') {
		$id = $params[0];
		if ($id>0) {
			
			if ($joueurs[$id]) {
				$rep = "200: ".$joueurs[$id]->_place.' / '.$joueurs[$id]->_nom;
			} else {
				$rep = "400: Player not defined";
			}
		} else {
			$rep = "500: No id";
		}
	}
	
	if ($cmd == 'MAIN') {
		if ($params[0]>0) {
			$id = $params[0]-1;
			if (sizeof($joueurs[$id])>0) {
				$rep = '210: '.$joueurs[$id]->infos().':'.$joueurs[$id]->mainStr();
			} else {
				$rep = '400: Empty';
			}
		} else {
			$rep = '500: No id';
		}
	}
	
	/*
		
		DISTRIB:<premier joueur>
	
	*/
	if ($cmd == 'DISTRIB'){
		if ($params[0]) {
			$from = $params[0];
		} else {
			$from = 1;
		}
		_log(sprintf("DISTRIB -- Joueurs count %d (from : %d)",sizeOf($joueurs),$from));
		
		for ($i=0; $i<4; $i++) {
			//printf("DISTRIB -- Reset %d\n",$i);
			$joueurs[$i]->resetMain();
		}
		$dCartes = array(3,2,3);
		for ($r=0; $r<3; $r++) {
			_log( "Round $r");
			$l="";
			for ($i=0; $i<4; $i++) {
				$joueur = (($i+($from-1)) % 4);
				$l = "(".$joueur.")";
				$jeu->distribueCarte($dCartes[$r],$joueurs[$joueur]);
				$l.= "Donne  (".$joueur.") (".$joueurs[$joueur]->nom()."): ".$joueurs[$joueur]->mainStr();
				_log($l);
			}
		}

		$rep = '200: OK';
		
		$state->roud++;
		$state->token = ($from + 1) % 4;
		$state->preneur = -1;
		$state->contrat="";
		$state->position = $state->token;
		storeContext();
	}
	
	
	/*
	
		POSE une carte
	
	*/
	
	if ($cmd == 'POSE') {
		if ($params[1]>0 && $params[0]!="") {
			$idJoueur = $params[1]-1;
			$carte = $params[0];
			
			if ($state->position-1 == $idJoueur) {
				_log(sprintf("POSE : id=%d carte=%s",$idJoueur, $carte));
				$r = $table->poseCarte($joueurs[$idJoueur],$carte);
				if ($r==200) {
					$state->tapisCount++;
					$state->position = (($idJoueur + 1) % 4)+1;
					$rep = '200: Ok';
				} else {
					$rep = '400: Impossible';
				}
			} else {
				$rep = "401: Impossible - Pas ton tour !";
			}
		
		} else {
			$rep = '500: Missing params';
			_log(sprintf("Param error : %s %s",$params[0],$params[1]));
		}
		if ($state->tapisCount==4) {
			$table->checkVainqueur($joueurs);
		}
		storeContext();
	}
	
	/*
	
		TAPIS : ce qu'il y a sur le tapis
	
	*/
	if ( $cmd == 'TAPIS' ) {
		$tapis = $table->montreTapisStr();
		if ($tapis != "") {
			$rep = "200: ".$tapis;
		} else {
			$rep = "400: Rien";
		}
	}
	
	if ( $cmd == "STATE" ) {
		if ($params[0]>0) {
			$pid = $params[0]-1;
		}
		$rep = "200: ";
		$rep .= $joueurs[$pid]->mainStr().": ";
		$rep .= $table->montreTapisStr().": ";
		$rep .= $state->str();
		_log(sprintf("---State : %s",$rep));
	}
	
	// Réponse
	if ($rep != "") {
		$talkback = $rep."\n\n";
		$client->send( $talkback );
		//$client->close();
		//socket_write($msgsock, $talkback, strlen($talkback));
	}
	
	return $res;
}

/*

	Sauvegarde et retauration des objets

*/

function restoreContext() {
	//global $jeu, $joueurs, $equipes, $state;
	foreach ($GLOBALS as $key => $val) { global $$key; }
	_log(sprintf("Restore context"));
	
	restoreObj($jeu,"jeu.txt");
	restoreObj($joueurs,"joueurs.txt");
	restoreObj($equipes,"equipes.txt");
	restoreObj($state,"state.txt");
	restoreObj($table,"table.txt");
}

function restoreObj(&$obj, $file) {
	$s = file_get_contents($GLOBALS['tmpDir'].$file);
	$obj = unserialize($s);
}

function storeContext() {
	//global $jeu, $joueurs, $equipes, $state;
	foreach ($GLOBALS as $key => $val) { global $$key; }
	_log(sprintf("Store context"));
	storeObj($jeu,"jeu.txt");
	storeObj($joueurs,"joueurs.txt");
	storeObj($equipes,"equipes.txt");
	storeObj($state,"state.txt");
	storeObj($table,"table.txt");
}

function storeObj($obj,$file) {
	$s = serialize($obj);
	file_put_contents($GLOBALS['tmpDir'].$file,$s);
	//printf("Saved : %s\n",$GLOBALS['tmpDir'].$file);
}

/*

	Log 

*/
function _log($txt, $nl=true) {
	if ($GLOBALS['logTimeStamp']==1) {
		$txt = '[' . date( DATE_RFC822 ) . '] ' . $txt;
	}
	if ($nl) $txt .= "\n";
	if ($GLOBALS['logFile'] != "") {
		$file = $GLOBALS['logFile'];
		file_put_contents($file, $txt, FILE_APPEND | LOCK_EX);
	} else {
		print($txt);
	}
}
 
?>