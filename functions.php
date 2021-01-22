<?php
require_once "config.php";

global $jeu, $joueurs, $equipes, $table, $state, $feuilleMarque, $_debug, $clients;

define("MODE_DEBUG", false);

define("ABR_COULEUR", 1);
define("ABR_CARTE", 2);
define("ABR_CC", 0);

define("PHASE_INIT", 0);
define("PHASE_JOUEURS", 1);
define("PHASE_START", 2);
define("PHASE_MANCHE", 3);
define("PHASE_DONNE", 4);
define("PHASE_ENCHERES", 5);
define("PHASE_JEU", 6);
define("PHASE_MARQUE", 7);
define("PHASE_FIN", 8);

$_debug = 1;
$debug_force = false;

$table = new table;
$jeu = new jeu;
$joueurs = array(new joueur,new joueur,new joueur,new joueur);
$equipes = array(new equipe,new equipe);
$feuille = new feuilleMarque;
$state = new state;
$clients = new clients;

$puid = "";
/*$state = array(
	"token"=>1,
	"round"=>0,
	"preneur"=>-1,
	"contrat"=>""
);
*/
$abrCouleur = array('TREFLE'=>'T', 'CARREAU'=>'K', 'COEUR'=>'C', 'PIQUE'=>'P');
$abrCarte = array ('7'=>'7','8'=>'8','9'=>'9','VALET'=>'J','DAME'=>'Q','ROI'=>'K','10'=>'T', 'AS'=>'A');

$placeStr = array ('N','E','S','O');
$placeLongStr = array('Nord','Est','Sud','Ouest');

$ponderationEnchere = array('T'=>1,'K'=>2,'C'=>3,'P'=>4);

function processMessage($buf, $client=0) {		
	//global $jeu, $joueurs, $equipes, $tapis,  $state;
	foreach ($GLOBALS as $key => $val) { global $$key; }
	
	$res = 0;
	$params = array();
	$noStore = false;
    
    // Client
    restoreObj($clients, $prefix."bcnc_clients.txt");
    
    $puid = $_SESSION["puid"];
    if (MODE_DEBUG && $_GET['p'] != '') {
        $puid .= '/'.$_GET['p'];
    }

    if ( !$clients->exists( $puid ) ) {
        //_log("** Not exist ** Session puid = ".$puid);
/*
        $clients->add();
        storeObj($clients, $prefix."bcnc_clients.txt");
*/
    } else {
        //_log("Session puid = ".$puid);
        $client = $clients->get($puid);
    }
	
	/* Commandes */

	if (preg_match('/^(ID:([a-z0-9\/]+);)?(.+)/',$buf,$m)) {
		if ($client){
            $clientID = array_search($client->place, $placeStr);
            //_log("ClientID = ".$clientID);
        } else {
            $clientID = $m[2];
        }
        //_log("ClientID = ".$clientID);
		$buf = $m[3];
	} else {
		$buf = "No match";
	}

	//_log( sprintf("buf = '%s' - Client : %s",$buf, $clientID));

	if (strpos($buf,":")>0) {
		$cmd = strtoupper(substr($buf,0,strpos($buf,":")));
		$sparams = trim(substr($buf,strpos($buf,":")+1));
		if (strpos($sparams,";")>0) {
			$params = explode(';',$sparams);
		} else {
			$params[0] = $sparams;
		}
	} else {
		$cmd = strtoupper(trim($buf));
	}

	//_log( sprintf("buf = '%s' - Client : %s / cmd= '%s' ----> Params : %s",$buf, $clientID, $cmd, $sparams));
	//_log( $params[2]);
	
	// forçage
	if (substr($cmd,-1,1)=='!') {
		$cmd = substr($cmd, 0,-1);
		$debug_force = true;
	}
	
	if ($cmd == 'PING') {
		$rep = '800: PONG';
		$noStore=true;
		$res = 999;
	}
	
	if ($cmd == "DEBUG") {
		toggleDebug();
		$rep = '200: OK Debug set to '.$GLOBALS["_debug"];
	}
	
	// Not really usefull !
	
	if ($cmd == 'LEFFE') {
		$t = array(	'410: Regarde dans ton frigo...', 
					'410: On n\'avait pas parlé d\'une bière ?',
					'410: Irène ?');
		$rep = htmlentities($t[rand(0,count($t)-1)]);
	}
	if ($cmd == 'PELF') {
		
		$t = array(	'410: Attention stock bas.',
					'410: C\'est ma tournée',
					'410: Ca c\'est bon !');
		$rep = htmlentities($t[rand(0,count($t)-1)]);
	}
	if ($cmd =='PAPPLE' || $cmd == "PAPLE") {
		$t = array(	'410: Alex, sors de ce corps !',
					'410: Quoi ? un quoi ?',
					'410: Faut dire, c\'est une boisson d\'homme');
		$rep = htmlentities($t[rand(0,count($t)-1)]);
	}
	
	/*
	
		Effacer tous les fichiers temporaires
	
	*/
	
	if ($cmd == 'CLEARTMP') {
		array_map('unlink', glob($tmpDir."bcnc*.*"));
		$rep = '200: Temp files cleared';
	}
	
	/*
	
		START : Démarrage d'une nouvelle partie :
			- création d'un jeu neuf
			- Mélange
			
		/!\ DEV ONLY : Ne dois pas être appelé en cours de manche ! 
		
		- Sauvegarde du stack
	
	*/
	if ($cmd == 'START') {
		// Préparer un jeu de cartes
		$table = new table();
		$state = new state();
		$feuille = new feuilleMarque;
		$jeu = new jeu;
		_log( "[START] Jeu neuf créé");	
		$jeu->melangeJeu();
		_log( "[START] Jeu mélangé");
		//$jeu->coupeJeu(10);
		// Créer 4 joueurs
		for ($i=0; $i<4; $i++) {
			$joueurs[$i] = new joueur($placeLongStr[$i],$placeStr[$i]);
		}
		for ($i=0; $i<2; $i++) {
			$equipes[$i] = new equipe($i, $joueurs[$i],$joueurs[$i+2]);
		}
		$jToken = 0;
		$state->phase = PHASE_DONNE;

		// Sauver le nouveau contexte.
		storeContext();

		$rep="200: OK";
		$cmd="DISTRIB";
		$params[0]=1;
	}
	/*
		
		LOAD : Charge un état à partr d'une sauvegarde
		
	*/
	if ($cmd =='LOAD') {
		if ($params[0] != "" ) {
			$prefix = $params[0];
			if (substr($prefix,-1,1) != '/') {
				$prefix .= '_';
			}
			restoreContext($prefix);
			$rep = "200: Game loaded = ".$prefix;
			storeContext();
		}	
		
	} else {
		restoreContext();
	}
	
	if ($cmd == 'SAVE') {
		if ($params[0] != '') {
			$prefix = $params[0];
			if (substr($prefix,-1,1) != '/') {
				$prefix .= '_';
			}
			storeContext($prefix);	
			$rep = "200: Saved (".$prefix.")";		
		}
		
	}

	/*
	
		Ajout d'un nouveau joueur
	
	*/
	
	if ($cmd == 'AJOUEUR') {
		$rep = "400: Player table full";
		for ($i=0; $i<4; $i++) {
			if ($joueurs[$i] == null) {
				$joueurs[$i] = new joueur($params[0], $params[1], $clientID);
				storeObj($joueurs,"bcnc_joueurs.txt");
				$rep = "200: OK";
			}
		}
		return $rep;
	}
	
	/*
	
		STACK : Retourne la pile du jeu
	
	*/
	
	if ($cmd == 'STACK') {
		if ($jeu) {
			$rep = '200: '.$jeu->tout();
		} else {
			$rep = '400: Not started';
		}
		//printf("STACK -- Joueurs count %d\n",sizeof($GLOBALS['joueurs']));
	}
	
	/*
	
		JOUEUR : Retourne la place et le nom du joueur
	
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
	
	/*
	
		MAIN : Retourne la liste des cartes du joueur
	
	*/
	
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
	
		ENCHERE
	
	*/
	if ($cmd == 'ENCHERE') {
		_log(sprintf("--ENCHERE de %s ($params[2]) @ %s.%d",$client->place, $params[0], $params[1]));
		if ($state->phase == PHASE_ENCHERES) {
			if ($params[0] != "" && $params[1] >0 && /*$params[2]>0)*/ $client->place) {
			
				$couleur = $params[0];
				$level = $params[1];
				//$joueur = $joueurs[$params[2]-1];
				$joueur = $joueurs[array_search($client->place, $placeStr)];
				$params[2] = array_search($client->place, $placeStr)+1;
				
				_log(sprintf("ENCHERE de %s ($params[2]) @ %s.%d",/*$joueur->_place*/$client->place, $couleur, $level));
				if ( $state->position == $params[2] /*array_search($client->place, $placeStr)+1*/ ) {
					$d = $table->enchere($joueur, $couleur, $level);
					if ($d == '200: PASSE') {
						_log("ENCHERE (4 Passes) : ".$d);
						// Les joueurs rendent leurs cartes
						foreach ($joueurs as $joueur) {
							$joueur->defosseMain($jeu);
						}
						_log("Reset table !");
						$table->resetTable();
						_log(sprintf("Encheres count %d",count($table->_encheres) ));
						$state->roud++;
						$state->token = ($state->token % 4) + 1;
						$state->contrat="";
						$state->position = $state->token;
						//$rep= "200: OK PASSE";
						$state->phase=PHASE_DONNE;
						$cmd = "DISTRIB";
						$params[0] = $state->token;
						storeContext();
					} else if ($d == '200: OK') {
						_log("ENCHERE : ".$d);
						$state->contrat = $level;
						$state->atout = $couleur;
						$state->position = ($state->position%4)+1;
						$rep = "200: OK (".$d.")";
						storeContext();
					} else if (preg_match('/200: ([*TKCP])([0-9]{2,3})([NESO])/i', $d, $matches)) {
						//_log("ENCHERE (fin): ".$d);
					
						$state->couleur = $matches[1];
						$state->contrat = $matches[2];
						$state->preneur = $matches[3];
						$state->atout = $matches[1];
						_log(sprintf("ENCHERE (fin) coul=%s level=%d Preneur=%s",$matches[1],$matches[2],$matches[3]));
						$state->position = $state->token;
						$state->phase = PHASE_JEU;
						storeContext();
					} else {
						$rep = $d;
					}
				} else {
					$rep="400: Impossible pas à toi de jouer";
				}
			}
		} else {
			$rep = "400: On n'est pas en phase d'enchères";
		}
	}
	
	if ($cmd == "CONTRAT") {
		if ($state->phase == PHASE_ENCHERES) {
			if ($params[0]!="") {
				if (preg_match('/([TKCP])([0-9]{2,3})([NESO])/i', strToUpper($params[0]), $matches)) {
					_log(sprintf("SET CONTRAT coul=%s level=%d Preneur=%s",$matches[1],$matches[2],$matches[3]));
					$state->couleur = $matches[1];
					$state->contrat = $matches[2];
					$state->preneur = $matches[3];
					$state->atout = $matches[1];
					$state->position = $state->token;
					$state->phase = PHASE_JEU;
					storeContext();
					$ret = "200: Ok";
				} else {
					$ret = "401: Paramètre incorecte (<Coul><Level><Place>)";
					_log("CONTRAT : parametre NoGood". $params[0]);
				}			
			} else {
				_log("CONTRAT : Manque parametre");
				$ret = "400: Manque paramètre";
			}
		} else {
			$ret = "400: Impossible pour l'instant";
		}
	}

	
	/*
		
		DISTRIB: Distribue les carte à partir de 
			- <premier joueur> si spécifié,
			- sinon joueur courant (token)
			
			- Incrémente Token
		
	
	*/
	if ($cmd == 'DISTRIB'){
		if ($state->phase == PHASE_MARQUE) {
			// Il faut d'abord ramasser les cartes
			$table->ramassePlis($jeu);
			$table->resetTable();
			//$state->token = ($state->token % 4) + 1;
			$state->phase = PHASE_DONNE; // On passe à la suite...
		}
		if ($state->phase == PHASE_DONNE) {
			if ($params[0]) {
				$from = $params[0];
			} else {
				$from = ($state->token % 4)+1;
			}
			// Couper
			$ret = $jeu->coupeJeu();
            _log(sprintf('[DISTRIB]--> Jeu coupé (%d cartes)',$ret));
			// Distribution et init de la partie
			_log(sprintf('[DISTRIB]--> Distribution à partir de %d',$from));
			if (distrib($joueurs, $from, $jeu)) {
				$rep = '200: OK';		
				$state->roud++;
				//$state->token = ($from + 1) % 4;
				$state->token = $from; // Définir la position du premier joueur
				$state->preneur = -1;
				$state->contrat=0;
				$state->atout="";
				$state->position = $state->token; // passe la main au premier joueur
				$state->phase = PHASE_ENCHERES;
				storeContext();
			} else {
				$rep = '400: Distribution impossible pour l\'instant.';
			}
		} else {
			$rep = "400: Distribution impossible en dehors de la phase 'Donne'.";
		}
	}
		
	/*
	
		POSE une carte
	
	*/
	
	if ($cmd == 'POSE') {
		_log("POSE ");
		if ($state->phase == PHASE_JEU) {
			$params[1] = array_search($client->place, $placeStr)+1;
			_log("POSE ".$params[1]." / ".$params[0]." --- ");
			if ($params[1]>0 && $params[0]!="") {
				$idJoueur = array_search($client->place, $placeStr);//$params[1]-1;
				$carte = $params[0];
				//
				$maitre = '';
				_log(sprintf("[POSE] id=%d carte=%s",$idJoueur, $carte));
				if ($state->position-1 == $idJoueur) {
					_log(sprintf("[POSE] id=%d carte=%s",$idJoueur, $carte));
					if ($table->tapisCount()<1) {
						// Première carte -> définir la couleur demandée
						$state->pcouleur = substr($carte,0,1);
					}
					$r = $table->poseCarte($joueurs[$idJoueur],$carte);
					if ($r==200) {
						$state->tapisCount++;
						$state->position = (($idJoueur + 1) % 4)+1;
						// Déterminer le maître :
						$state->maitre = $table->checkVainqueur($joueurs,$state);
						$rep = '200: Ok';
					} else {
						$rep = '400: Impossible';
					}
				} else {
					$rep = "401: Impossible - Pas ton tour !";
				}
		
			} else {
				$rep = '500: Missing params';
				//_log(sprintf("Param error : %s %s",$params[0],$params[1]));
			}
			// Le plis est terminé (4 cartes au tapis)
			if ($state->tapisCount == 4) {
				$placeVainqueur = $state->maitre; //$table->checkVainqueur($joueurs, $state);
				$table->donnePlis($placeVainqueur);
				_log(sprintf("[POSE] Fin du tour %d : %s remporte le plis",$state->round +1 , $placeVainqueur));
				$state->tapisCount = $table->tapisCount();
				$state->pcouleur = "";
				$state->round++;
				$state->position = array_search($placeVainqueur,$placeStr)+1;
			}
			if ($table->plisCount() == 8) {				
				$state->scores = $table->comptePlis($state);
				_log(sprintf("[POSE] Fin de partie : (NS: %d)-(EO: %d",$tate->scores[0], $state->scores[1]));
				$state->phase = PHASE_MARQUE;
				$cmd = "MARQUE"; 
			}
			storeContext();
		} else {
			$rep = "400: Impossible pour l'instant";
		}
	}
	
	
	/*
	
		MARQUE : 
			Remplir la feuille de marque
	
	*/
	if ( $cmd == 'MARQUE' ) {
		if ($state->phase == PHASE_MARQUE || $debug_force) {
			$feuille->addMarque($state, $table);
            $state->sTotal = $feuille->scores();
            
			storeObj($feuille, "bcnc_feuille.txt");		
            storeObj($state,"bcnc_state.txt");
            
			$rep = "200: Ok ";
		} else {
			$rep = "400: Impossible pour l'instant";
		}
	}
	
	
	/*
	
		SCORES : 
			Calcul et affiche les scores de la partie
	
	*/
	
	if ($cmd == "SCORES") {
		$state->sTotal = $feuille->scores();
        $rep = sprintf("200 : Scores = (NS) %d - (EO %d)",$state->sTotal[0],$state->sTotal[1]);
        storeObj($state,"bcnc_state.txt");
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
	
	/*
	
		STATE : Etat global du jeu
		
		Retourne :
			<200: ><Main du joueur>:<Cartes au tapis>:<Etat des flags>:<Liste des plis>
	
	*/
	
	if ( $cmd == "STATE" ) {
		// Debug
        if ($client->place != ""){
            $pid = array_search($client->place, $placeStr);
        } else {
            if ($params[0]>0) {
                $pid = $params[0]-1;
            }
        }
        $rep = "200: ";
        if ($pid>-1) {
            $rep .= $joueurs[$pid]->mainStr().": ";
        } else  {
            $rep .= ": ";
        }
        $rep .= $table->montreTapisStr().": ";
        $state->scores = $table->comptePlis($state);
        $rep .= $state->str().",PL".$client->place.": ";
        switch ($state->phase) {
            case PHASE_JEU:
                $rep .= $table->plis();
                break;
            case PHASE_MARQUE:
                $rep .= $table->plis();
                break;
            case PHASE_ENCHERES:
                $rep .= $table->encheresStr();
                break;
        }
		//if ($_debug) _log(sprintf("---State (%d) : %s",$pid, $rep));
	}
	
	// Réponse
	return $rep;
	
}

/*

	Log 

*/

function _log($txt, $nl=true) {
	
	if ($GLOBALS['puid'] != "") {
		$txt = '('.$GLOBALS['puid'].') ' . $txt;
	}
	$txt = $_SERVER['REMOTE_ADDR']." - " . $txt;
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

/*

	Conversion d'un objet "carte" en texte ABR
		Choix de la artie à convertir dans $part :
		- ABR_CC 		: <Couleur ABR><Valeur ABR>
		- ABR_COULEUR 	: <Couleur ABR>
		- ABR_CARTE		: <Valeur ABR>

*/

function abr($carte, $part=0) {
	global $abrCouleur, $abrCarte;
	
	$r = "";
	switch ($part) {
		case ABR_CC:
			$r = $abrCouleur[$carte->_couleur].$abrCarte[$carte->_carte];
			break;
		case ABR_COULEUR:
			$r = $abrCouleur[$carte->_couleur];
			break;
		case ABR_CARTE:
			$r = $abrCarte[$carte->_carte];
			break;
	}
	return $r;
	
}

/*

	Création d'un objet "carte" à partir d'une valeur ABR

*/

function abr2carte($abr) {
	global $abrCouleur, $abrCarte;
	
	$couleur = array_search($abr[0],$abrCouleur);
	$carte = array_search($abr[1],$abrCarte);
	
	return new carte($couleur, $carte);
}

/*

	Conversion d'une couleur ABR en Full text

*/

function abr2Couleur($abr) {
	return array_search(substr($abr,0,1), $abrCouleur);
}

/*

	Conversion d'une valeur de carte ABR en Full text

*/

function abr2valeur($abr) {
	return array_search(substr($abr,1,1), $abrCarte);
}

/*

	Convertis un taleau en cahine de caractères de type
	Key=Val;... 

*/

function arrayStr($a) {
	
	$s = "";
	foreach($a as $k=>$v) {
		if ($s != "") $s .= ";";
		$s .= $k."=".$v;
	}
	return $s;
	
}

/*

	Distribution des cartes (Donne)

*/

function distrib($joueurs, $from, $jeu) {
	_log(sprintf("[distrib()] -- Joueurs count %d (from : %d)",sizeOf($joueurs),$from));
	$r = false;
	if ( (sizeOf($joueurs) == 4) && ($jeu->cartesCount()==32) ) {
		for ($i=0; $i<4; $i++) {
			$joueurs[$i]->resetMain();
		}
		$dCartes = array(3,2,3);
		for ($r=0; $r<3; $r++) {
			_log( "[distrib()] Round $r");
			$l="[distrib()] ";
			for ($i=0; $i<4; $i++) {
				$joueur = (($i+($from-1)) % 4);
				$l = "[distrib()] ";
				$jeu->distribueCarte($dCartes[$r],$joueurs[$joueur]);
				$l.= "Donne  (".$joueur.") (".$joueurs[$joueur]->nom()."): ".$joueurs[$joueur]->mainStr();
				_log($l);
			}
		}
		$r = true;
	}
	return $r;
}

function getNewPID() {
    foreach ($GLOBALS as $key => $val) { global $$key; }
    
    $bytes = random_bytes(5);
    $puid = bin2Hex($bytes);

    return $puid;
}

/*

	Restauration des objets

*/

function restoreContext( $prefix = "" ) {
	foreach ($GLOBALS as $key => $val) { global $$key; }
	
	restoreObj($jeu, $prefix."bcnc_jeu.txt");
	restoreObj($joueurs, $prefix."bcnc_joueurs.txt");
	restoreObj($equipes, $prefix."bcnc_equipes.txt");
	restoreObj($state, $prefix."bcnc_state.txt");
	restoreObj($table, $prefix."bcnc_table.txt");
	restoreObj($feuille, $prefix."bcnc_feuille.txt");
}

/*

	Restauration d'un objet

*/

function restoreObj(&$obj, $file) {
	if (file_exists($GLOBALS['tmpDir'].$file)) {
        $s = file_get_contents($GLOBALS['tmpDir'].$file);
        $obj = unserialize($s);
    }
}

/*

	Enregistre les infos joueurs dans un fichier texte

*/

function saveJoueurs($joueurs) {
	$s = "";
	foreach ($joueurs as $joueur) {
		$s .= $joueur->infos()."\n";
	}
	file_put_contents($GLOBALS['tmpDir'].'bcnc_players.txt', $s);
}


/*

	Sauvegarde des objets

*/

function storeContext( $prefix = "" ) {
	foreach ($GLOBALS as $key => $val) { global $$key; }
	if ($prefix != '') {
		/*
		if ( !mkdir(substr($prefix,0,-1))) {
			_log('Store prefix='.substr($prefix,0,-1).' createdir Error');
		} else {
			_log('Store prefix='.$prefix.' createdir OK');
		}
		*/
	}
	storeObj($jeu, $prefix."bcnc_jeu.txt");
	storeObj($joueurs, $prefix."bcnc_joueurs.txt");
	storeObj($equipes, $prefix."bcnc_equipes.txt");
	storeObj($state, $prefix."bcnc_state.txt");
	storeObj($table, $prefix."bcnc_table.txt");
	storeObj($feuille, $prefix."bcnc_feuille.txt");
}

/*

	Sauvegarde d'un objet

*/

function storeObj($obj,$file) {
	$s = serialize($obj);
	file_put_contents($GLOBALS['tmpDir'].$file,$s);
}

function toggleDebug() {
	$GLOBALS["_debug"] = ($GLOBALS["_debug"]==1)?0:1;
}

?>