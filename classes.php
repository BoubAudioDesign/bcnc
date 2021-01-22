<?php

require_once "config.php";
require_once "functions.php";


class carte
{
	private $atoutVal = array (
		'7'		=>0,
		'8'		=>0,
		'9'		=>14,
		'VALET'	=>20,
		'DAME'	=>3,
		'ROI'	=>4,
		'10'	=>10,
		'AS'	=>11
	);
	private $couleurVal = array(
		'7'		=>0,
		'8'		=>0,
		'9'		=>0,
		'VALET'	=>2,
		'DAME'	=>3,
		'ROI'	=>4,
		'10'	=>10,
		'AS'	=>11
	);

	private $ponderationCouleur = array(
		'7'		=>1,
		'8'		=>2,
		'9'		=>3,
		'VALET'	=>4,
		'DAME'	=>5,
		'ROI'	=>6,
		'10'	=>7,
		'AS'	=>8
	);
	private $ponderationAtout = array(
		'7'		=>10,
		'8'		=>11,
		'9'		=>16,
		'VALET'	=>17,
		'DAME'	=>12,
		'ROI'	=>13,
		'10'	=>14,
		'AS'	=>15
	);

	public $_couleur;
	public $_carte;

	function __construct($couleur='', $carte='') {
		if ($couleur != '' && $carte != '') {
			$this->_couleur = $couleur;
			$this->_carte = $carte;
		}
	}

	public function valeurCouleur() {
		return $this->couleurVal[$this->_carte];
	}

	public function valeurAtout() {
		return $this->atoutVal[$this->_carte];
	}

	public function valeur($atout) {
		if ($atout == $this->couleurAbr()) {
			$v = $this->valeurAtout();
		} else {
			$v = $this->valeurCouleur();
		}
		return $v;
	}

	public function texte() {
		return $this->_carte . " de " . $this->_couleur;
	}

	public function image() {
		return $this->_carte . '_' . $this->_couleur;
	}

	public function abr() {
		return abr($this);
	}

	public function couleur() {
		return $this->_couleur;
	}

	public function couleurAbr() {
		return abr($this,ABR_COULEUR);
	}

	public function carte() {
		return $this->_carte;
	}

	public function ponderation($isAtout) {
		if ($isAtout) {
			$r = $this->ponderationAtout[$this->_carte];
		} else {
			$r = $this->ponderationCouleur[$this->_carte];
		}
		return $r;
	}
}

class tenchere
{
	public $level;
	public $place;
	public $couleur;

	function __construct($place, $couleur, $level) {
		$this->level = $level;
		$this->couleur = $couleur;
		$this->place = $place;
	}

	public function levelPond() {
		global $ponderationEnchere;
		return $this->level * $ponderationEnchere[$this->couleur];
	}
}

class pli
{
	// Carte = array (carte, place)
	public $cartes;
	public $place;

	function __construct($cartes, $place) {
		$this->cartes = $cartes;
		$this->place = $place;
	}

	public function valeur($atout) {
		$r = 0;
		foreach ($this->cartes as $carteAbr) {
			$carte = abr2carte($carteAbr["carte"]);
			//$l .= $carteAbr["carte"]."(".$carte->valeur($atout).")/";
			$r += $carte->valeur($atout);
		}
		//_log("tPli : ".$l);
		return $r;
	}
}

/*
	Création d'un jeu de cartes
*/
class Jeu
{

	private $couleur = array('TREFLE', 'CARREAU', 'COEUR', 'PIQUE');

	private $carte = array('7','8','9','VALET','DAME','ROI','10','AS');

	private $_jeu;

	//global $tmpDir;

    function __construct() {
    	$iCount=0;
        for ($iCouleur =0; $iCouleur<4; $iCouleur++) {
        	for ($iCarte = 0; $iCarte<8; $iCarte++) {
        		$this->_jeu[$iCount++] = new carte($this->couleur[$iCouleur], $this->carte[$iCarte]);
        	}
        }

    }

    function __destruct() {

    }

    /*
    	Battre le jeu complet
    */
    public function melangeJeu() {
    	for ($i=0; $i<rand(10,30); $i++){
    		shuffle($this->_jeu);
    	}
    }

    /*
    	Couper le jeu à la position donnée en argument
    	Si position = 0 Définir une position aléatoire
    */
    public function coupeJeu($position=0) {
    	if ($position==0) {
    		$position = rand(2,30);
    	}
    	$newJeu = array_slice($this->_jeu,$position);
    	$newJeu = array_merge($newJeu, array_slice($this->_jeu,0,$position));
    	$this->_jeu = $newJeu;

    	_log(sprintf("[jeu->coupeJeu()] COUPE @%s",$position));

    	return sizeof($this->_jeu); // Normalement 32 !
    }

    public function montreJeu() {
    	for ($i=0;  $i<32; $i++) {
    		echo $i.' - '.$this->_jeu[$i]->texte()."<br/>\n";
    	}
    }

    public function jeuStr() {
    	$s = "";
        for ($i=0;  $i<32; $i++) {
    		$s .= $i.' - '.$this->_jeu[$i]->abr();
    	}
        return $s;
    }

    public function montreJeuGFX() {
    	echo '<table>';
    	for ($i=0;  $i<4; $i++) {
    		echo '<tr>';
    		for ($j=0;  $j<8; $j++) {
    			echo '<td>'.$this->getGFX($i*8+$j).'</td>';
    		}
    		echo "</tr>\n";
    	}
    	echo "<table>\n";
    }


    public function montreCarte($index) {
    	return $this->_jeu[$index];
    }

    public function getGFX($index) {
    	return '<img src="images/Cartes/'.$this->_jeu[$index]->image().'" width="100px"/>';
    }

    public function getAbr($index) {
    	return abr($this->_jeu[$index]);
    }

    public function tout() {
    	$r = '';
    	for ($i=0; $i<32; $i++) {
    		$r .= $this->getAbr($i);
    	}
    	return $r;
    }

    public function cartesCount() {
    	return sizeof($this->_jeu);
    }

    public function distribueCarte($count, $joueur) {
    	if ($count <= sizeof($this->_jeu)) {
			for ($i=0; $i<$count; $i++) {
				$joueur->ajouteCarte($this->_jeu[0]);
				array_splice($this->_jeu, 0, 1); // Supprimer la carte du dessus
			}
    	}
    }

    public function ajouteCarte($carte) {
    	if (count($this->_jeu)<32) {
    		$err = false;
            foreach ($this->_jeu as $jcarte) {
                if ($carte == $jcarte) {
                    _log(sprintf("[jeu->ajoutCarte()] ERREUR : Carte (%s) déjà dans le jeu !",$carte->abr()));
                    $err = true;
                }
            }
            $this->_jeu[] = $carte;
    	}
        return $err;
    }

    public function save() {
    	file_put_contents($GLOBALS['tmpDir'].'bcnc_stack.txt', $this->tout());
    }

    public function load() {
    	if (file_exists($GLOBALS['tmpDir'].'bcnc_stack.txt')) {
    		$s = file_get_contents($GLOBALS['tmpDir'].'bcnc_stack.txt');
			$newJeu = array();
    		for ($i=0; $i<32; $i++) {
    			$newJeu[$i] = abr2carte(substr($s, $i*2, 2));
    		}
    		$this->_jeu = $newJeu;
    	}
    }

}

class equipe
{
	private $_joueurs;
	private $_points;
	private $_id;

	function __construct($id=0, $joueur1=null, $joueur2=null) {
		if ($joueur1 != null && $joueur2 != null) {
			$this->_id = $id;
			$this->_joueurs = array ($joueur1, $joueur2);
			$this->_points = 0;
		}
	}

}

class joueur
{
	public $_main;
	public $_place;
	public $_nom;
	private $_id;

	function __construct($nom="", $place=0, $id="") {
		if ($id != "") {
			$this->_id = $id;
		} else {
			$bytes = random_bytes(5);
			$this->_id = bin2Hex($bytes);
		}
		$this->_nom = $nom;
		$this->_place = $place;
	}

	public function ajouteCarte($carte) {
		array_push($this->_main, $carte);
		//echo '(Donne) ** '.$this->_nom.' > '.$this->mainStr();
	}

	public function resetMain() {
		$this->_main = array();
	}

	public function defosseMain($jeu) {
		for ($i=0; $i<count($this->_main); $i++) {
			$jeu->ajouteCarte($this->_main[$i]);
		}
		$this->resetMain();
	}

	public function mainCount() {
		//printf("mainCount >>> sizeOf(main)= %d\n",sizeof($this->_main));
		return sizeOf($this->_main);
	}

	public function montreMain() {
		$i = 0;
		foreach ($this->main as $carte) {
			echo $i.' - '.$carte->texte()."<br/>\n";
			$i++;
		}
	}

	public function mainStr() {
		$i = 0;
		$r = '';
		//printf("mainStr >>> main count %d\n",sizeof($this->_main));
		foreach ($this->_main as $carte) {
			$r .= $carte->abr(); //.'/'
			//echo $i.' - '.$carte->texte()."<br/>\n";
			$i++;
		}
		return $r;
	}

	public function pose($idCarte) {
		unset($this->_main[$idCarte]);
	}

	public function infos() {
		$r  = $this->_id;
		$r .= ':'.$this->_nom;
		$r .= ':'.$this->_place;
		return $r;
	}

	public function nom() {
		return $this->_nom;
	}

	private function idCarte($carte) {
		$i = -1;
		for($i=0; $i<8; $i++) {
			if ($carte == $this->_main[$i]) {
				$r = $i;
			}
		}
		return $i;
	}

}

class table
{
	private $_cartes;
	private $_plis;
	public $_encheres;
	private $_joueurs; // array of joueur

	function __construct($jlist=null) {
    	if ($jlist) {
    		$this->_joueurs = $jlist;
    	}
		$this->resetTable();
    }

    function __destruct() {

    }


    public function resetTable() {
    	//_log("Reset table !");
    	$this->_cartes= array();
    	$this->_plis=array();
    	$this->_encheres = array();
    }

    public function enchere($joueur, $couleur, $level){
    	global $ponderationEnchere;
    	$r="";
    	$f = true;
    	$s = "";
    	_log(sprintf("[table->Enchere] : joueur: %s / coul=%s level=%d",$joueur->_place,$couleur, $level));
    	if ($this->_encheres && $couleur!="") {
			_log("[table->Enchere] test / Pond = ".$ponderationEnchere[strToUpper($couleur)]);
			if ($level > 70) {
				$lastEnchere = $this->_encheres[count($this->_encheres)-1];
				$f = ( ($level == $lastEnchere->level) && ($ponderationEnchere[strToUpper($couleur)] > $ponderationEnchere[$lastEnchere->couleur]) )
					||
					 ($level > $lastEnchere->level);
				}
				$s .= 'Llevel='.$lastEnchere->level.'/Lcoul='.$ponderationEnchere[$lastEnchere->couleur].' vs new level='.$level . '/Coul='.$ponderationEnchere[strToUpper($couleur)];

				_log("[table->Enchere] test : ".$s);

		}
		if ( $f ) {
			if ($level==70) $level=0;
			$enchere = new tenchere($joueur->_place, $couleur, $level);
			$this->_encheres[] = $enchere;

			_log("[table->Enchere] recorded tot=".count($this->_encheres));
			$r="200: OK";
		} else {
			$r="400: Trop bas";
		}


		$passe=0;
		for ($i = count($this->_encheres)-1; $i>=0; $i--) {
			if ($this->_encheres[$i]->level == 0) {
				$passe++;
			} else {
				break;
			}
		}
		_log(sprintf("[table->Enchere] Passe=%d / %d",$passe, count($this->_encheres)));
		if ( $passe == 3 && count($this->_encheres) > 3 ) {
			// 3 passes, le contrat est validé
			$r = "200: ".$this->_encheres[$i]->couleur . $this->_encheres[$i]->level .$this->_encheres[$i]->place;
		} else if ($passe == 4) {
			// 4 passes, on redonne en passant un jouer (fin de tour)
			$r = "200: PASSE";
		}

		_log("[table->Enchere] Final result : '".$r."'");
		return $r;
    }

	/*

		Poser une carte sur le tapis.

	*/
	public function poseCarte($joueur, $carte) {
		if ($carte instanceof carte) {
			_log(sprintf("[table->poseCarte()] From instance"),false);
			$idCarte = $joueur->idCarte($carte);

		} else {
			$carte=strtoupper($carte);
			_log(sprintf("[table->poseCarte()] From abr : %s",$carte),false);
			foreach ($joueur->_main as $k=>$c) {
				if ($c->abr() == $carte) {
					$idCarte = $k;
				}
			}
			_log(sprintf("[table->poseCarte()] id=%d",$idCarte));

		}
		$invalide=false;
		$countCartes = 0;
		// Vérifier que ce joueur n'a pas déjà posé une carte
		if ($this->_cartes) {
			foreach($this->_cartes as $k=>$c) {
				$countCartes++;
				if ($c["place"] == $joueur->_place) {
					_log(sprintf("[table->poseCarte()] Invalide - Joueur trouvé : %s @ %d (pour %s)",$c["place"], $k, $joueur->_place));
					$invalide=true;
				}
			}
		}
		if (!$invalide) {
			$this->_cartes[] = array("carte"=>$carte,"place"=>$joueur->_place);
			$joueur->pose($idCarte);
			$r=200;
		} else {
			$r=400;
		}
		//$state->tapisCount = $countCartes;
		return $r;
	}

	/*

		Rendre une carte du tapis à un joueur

	*/
	public function rendCarte(&$joueur) {
		foreach ($_cartes as $k=>$c) {
			if ( $c["place"] == $joueur->_place ) {
				$joueur->ajouteCarte( $c["carte"] );
				unset($this->_cartes[$k]);
				break;
			}
		}
		$state->tapisCount--;
	}

	public function tapisCount($state=null) {
		$r = 0;
		if ($this->_cartes) {
			$r = count($this->_cartes);
		}
		if ($state) {
			$state->tapisCount = $r;
		}
		return $r;
	}

	/*

		Fin de round, ranger le plis en l'associant à un joueur

	*/
	public function donnePlis($place) {
		if (count($this->_cartes)==4) { // Il faut 4 cartes sur la table
			$this->_plis[] = new pli($this->_cartes, $place);
			$this->_cartes = array();
		}
	}

	public function plis($place="") {
		if ($place=="") {
			foreach ($this->_plis as $pli) {
				$cartes = $pli->cartes;
				$str .= $pli->place.".";
				foreach($cartes as $carte) {
					$str .= $carte["carte"].$carte["place"];
				}
				$str .=";";
			}

			$str = substr($str,0,-1);
		}
		return $str;
	}

	public function montreTapisStr() {
		//printf("Montre TAPIS\n");
		$r = "";
		$i = 0;
		if ($this->_cartes) {
			if (count($this->_cartes)>0) {
				foreach($this->_cartes as $carte) {
					$i++;
					$r .= $carte["carte"].$carte["place"];
				}
			} else {
				$r = "";
			}
		}
		return $r;
	}

	public function encheresStr() {
		$r = "";
		if ($this->_encheres) {
			if (count($this->_encheres)>0) {
				foreach ($this->_encheres as $enchere) {
					$r .= $enchere->place . $enchere->couleur . $enchere->level;
				}
			}
		}
		//_log("encheresStr() =".$r);
		return $r;
	}

	public function checkVainqueur($joueurs, $state) {
		$cval = array();
		$maxPonderation = 0;
		$vainqueur = "";
		$l="";
		$coupe = "";
		foreach ($this->_cartes as $k=>$carte) {
			$c = abr2Carte($carte["carte"]);
			if ($c->couleurAbr()!=strToUpper($state->pcouleur) && $c->couleurAbr()==$state->atout ) {
				// Coupé !
				$p = $c->ponderation($c->couleurAbr()==$state->atout);
				$coupe = 'Coupe';
			} else {
				$p = $c->ponderation($c->couleurAbr()==$state->atout) * (($c->couleurAbr()==strToUpper($state->pcouleur))?1:0);
			}
			$l .= sprintf(" [%s]=%d %s",$c->abr(),$p, $coupe);
			if ($p > $maxPonderation) {
				$maxPonderation = $p;
				$vainqueur = $carte["place"];
			}
		}
		$l .=sprintf(" => (max=%d) %s",$maxPonderation,$vainqueur);
		//_log("[table->checkVainqueur()] :" . $l);
		return $vainqueur;
	}

	public function plisCount() {
		return count($this->_plis);
	}

	public function valeurPli($atout, $idx=-1) {
		$val = 0;
		$attrib = "";
		if ($idx < count($this->_plis)) {
			$val = $this->_plis[$idx]->valeur($atout);
			$attrib = $this->_plis[$idx]->place;
		}
		return array("valeur"=>$val, $attrib);
	}

	public function ramassePlis($jeu) {
		if ($this->_plis) {
			if (count($this->_plis) == 8) {
				$r = "200: Ok";
                foreach($this->_plis as $k=>$pli) {
					_log(sprintf("[table->ramasse] : pli %d",$k));
                    foreach($pli->cartes as $carte) {
						_log(sprintf("[table->ramasse] : %s",$carte["carte"]));
						$err = $jeu->ajouteCarte(abr2carte($carte["carte"]));
                        if ($err == true) {
                            $r = "400: Erreur, carte déjà dans le jeu";
                        }
					}
				}
				$this->_plis = array();
			} else {
				$r = "400: Impossible, tous les plis ne sont pas fais";
			}
		}
		return $r;
	}

	public function comptePlis($state) {
		$s = array(0,0);
		$i=0;
		if ($this->_plis) {
			foreach ($this->_plis as $pli) {
				$i++;
				$plis = array(0,0);

				$equipe = ($pli->place == 'N' || $pli->place == 'S') ? 0:1;
				$s[$equipe] +=	$pli->valeur($state->atout);
				if ($i == 8) $s[$equipe] += 10; // 10 de der !
			}
		}
		return $s;
	}


}


/*

	STATE :
		Etat et flags généraux

*/

class state {
	public $token; 		// TK : Premier joueur (bouchon)
	public $round; 		// RD : Donne
	public $pcouleur;	// PC : (Première) Couleur demandée (premier joueur)
	// Contrat du round courant
	public $preneur; 	// PR : joueur qui réalise le contrat (place)
	public $atout; 		// AT : couleur d'atout
	public $contrat;	// CT : Points à réaliser

	// Etat du jeu
	public $position;	// PO : Joueur qui à la main (1..4)
	public $tapisCount;	// TC : Nopmbre de carte sur le tapis
	public $phase; 		// PH : phase de jeu :
	public $debug;
	public $scores;
	public $sTotal;
	public $maitre;

	function __construct() {
    	$this->token   		= 1;
    	$this->round   		= 0;
    	$this->pcouleur		= "";
    	$this->preneur 		= "";
    	$this->atout		= "";
    	$this->contrat 		= 0;
    	$this->position 	= 0;
    	$this->tapisCount 	= 0;
    	$this->phase		= 0;
    	$this->debug		= 0;
    	$this->scores		= array(0,0);
      $this->sTotal       = array(0,0);
      $this->maitre		= "";
    }

    function __destruct() {

    }

    function str() {
    	$s  = "TK".$this->token;
    	$s .= ','."PC".$this->pcouleur;
    	$s .= ','."RD".$this->round;
    	$s .= ','."PR".$this->preneur;
    	$s .= ','."CO".$this->contrat;
    	$s .= ','."AT".$this->atout;
    	$s .= ','."PO".$this->position;
    	$s .= ','."TC".$this->tapisCount;
    	$s .= ','."PH".$this->phase;
    	$s .= ','."DE".$this->debug;
    	$s .= ','."SC".$this->scores[0].'-'.$this->scores[1];
      $s .= ','."ST".$this->sTotal[0].'-'.$this->sTotal[1];
      $s .= ','."MA".$this->maitre;

    	return $s;
    }
}

/*

	MARQUE :
		Ligne de marque

*/

class marque {
	public $contrat;
	public $couleur;
	public $preneur;
	public $scores;
	public $ts;
	public $idPartie;

	function __construct($state, $idPartie="") {
		$this->contrat = $state->contrat;
		$this->couleur = $state->atout;
		$this->preneur = $state->preneur;
		$this->scores = $state->scores;
		$this->ts = time();
		$this->idPartie = $idPartie;
		/*
		_log(sprintf("New marque : Contrat = %d %s par %s Score: '%s' '%s'",
		$this->contrat,
		$this->couleur,
		$this->preneur,
		'/'.$this->scores[0],
		'/'.$this->scores[1]
		));
		*/
	}

	function totaux() {
		$s = $this->scores;
    $eqAttaque = ($this->preneur == 'N' || $this->preneur == 'S') ? 0:1;
		$eqDefense = ($this->preneur == 'N' || $this->preneur == 'S') ? 1:0;
		//_log(sprintf("[marque->totaux()] (preneur = %s [%d]) Attaque / défense : %d (%d) / %d (%d)",$this->preneur, $this->contrat, $eqAttaque, $s[$eqAttaque], $eqDefense, $s[$eqDefense]));


		if ( ($s[$eqAttaque] <= $s[$eqDefense])  || ($s[$eqAttaque] < $this->contrat) && ($s[$eqAttaque]<162) ) {
			_log("[marque->totaux()] Chute");
			$points[$eqAttaque]=0;
			if ($this->contrat == 170) {
				$points[$eqDefense] = 250;
			} else {
				$points[$eqDefense] = 160;
			}
		} else {
			if($s[$eqAttaque]==162) {
				// Capot !
				$points[$eqDefense] = 0;
				$points[$eqAttaque] = 250;// + (($this->contrat == 170)?250:$this->contrat);
			} else {
				// Arrondi
				$points[$eqDefense] = round ($s[$eqDefense]/10.0)*10;
				$points[$eqAttaque] = 160 - $points[$eqDefense];
				//_log(sprintf("[marque->totaux()] Arrondi : %d - %d", $points[$eqAttaque], $points[$eqDefense]));
				// Si defense fini par 5 ou 6 +10 à att
				$dec = $s[$eqDefense]-floor($s[$eqDefense]/10.0)*10;
				//_log("dec = ".$dec." floor = ".(floor($s[$eqDefense]/10.0)*10));
				if ($dec ==5 || $dec==6) {
					$points[$eqAttaque] += 10;
				}
			}
		}
    // Ajouter le contrat
    if ($points[$eqAttaque] < $this->contrat) {
        // Chuté
        $points[$eqDefense] += $this->contrat;
    } else {
        // Fait
        $points[$eqAttaque] += (($this->contrat == 170)?250:$this->contrat);
    }

    //_log(sprintf("[marque->totaux()] Brut %d-%d - Points %d-%d", $s[$eqAttaque], $s[$eqDefense], $points[$eqAttaque], $points[$eqDefense]));
		return $points;
	}
}

class feuilleMarque {
	public $marques;
	public $joueurs;
	public $currentIdPartie;

	function __construct($joueurs='', $idPartie='') {
		if ($idPartie != '') {
			$this->_currentIdPartie = $idPartie;
		} else {
			$bytes = random_bytes(10);
			$uid = bin2Hex($bytes);
			$this->_idCurrentIdPartie = $uid;
		}
	}

	public function addMarque($state, $table=null) {
		if ($table) {
			$state->scores = $table->comptePlis($state);
		}
		$this->marques[] = new marque($state, $this->_currentIdPartie );
		//_log(sprintf("--addMarque (count = %d)",count($this->marques)));
	}

	public function countMarques($idPartie="") {
		$c = 0;
		foreach($this->marques as $marque) {
			if (($idPartie != '' && $idPartie == $marque->idPartie) || ($idPartie=='')) {
				$c++;
			}
		}
		return $c;
	}

	public function scores($idPartie="") {
		$st = array(0,0);
		if ($idPartie="") $idPartie = $this->_currentIdPartie;

		//_log(sprintf("Scores : Marques count = %d)",count($this->marques)));
		$ligne = 1;
		foreach($this->marques as $marque) {
			if ( $idPartie == $marque->idPartie ) {
				if ($ligne > 1) { // On ne compte pas la première donne
					$s = $marque->totaux();
					for ($i=0; $i<2; $i++) {
						$st[$i] += $s[$i];
					}
				}
				$ligne++;
			}
		}
		return $st;
	}
}

class client {
    public $ip;
    public $pId;
    public $nom;
    public $isAdmin;
    public $place;
    public $ts;

    function __construct($pId, $ip) {
        $this->pId = $pId;
        $this->nom = "";
        $this->isAdmin = false;
        $this->ip = $ip;
        $this->place = "";
        $this->ts = time();
    }
}

class clients {
    public $clients;

    function __construct() {
        $this->clients = array();
    }

    public function add() {
        $r = false;

        if ($_GET['d'] != '' && MODE_DEBUG) {
            $puid = $_SESSION['puid'].'/'.$_GET['d'];
        } else {
            $puid = $_SESSION['puid'];
        }
        if (! $this->exists($puid)) {
            $this->clients[] = new client(
                $puid,
                $_SERVER['REMOTE_ADDR']
            );
            _log(sprintf("[clients->add()] puid=%s",$puid));
            $res = true;
        }

        return $res;
    }

    public function exists($pId) {
        $r = false;

        foreach($this->clients as $c) {
            if ($c->pId == $pId) {
                //_log(sprintf("[clients->exists()] sId=%s Found !",$pId));
                $r = true;
            }
        }
        //_log(sprintf("[clients->exists()] sId=%s (r=%d)",$pId, $r));
        return $r;
    }

    public function get($pId) {
        foreach($this->clients as $c) {
            if ($c->pId == $pId) {
                //_log(sprintf("[clients->exists()] sId=%s Found !",$pId));
                $r = $c;
                break;
            }
        }
        return $r;
    }

    public function update($pId, $place, $nom=""){
        foreach($this->clients as $c) {
            if ($c->pId == $pId) {
                //_log(sprintf("[clients->exists()] sId=%s Found !",$pId));
                $c->place = $place;
                $c->nom = $nom;
                break;
            }
        }
    }

    public function list() {
        return $this->clients;
    }

    public function del($pid) {
        //_log(sprintf("[clients->del()] pid = %s",$pid));
        for ($i=0; $i<count($this->clients); $i++){
            if ($this->clients[$i]->pId == $pid) {
                //_log(sprintf("[clients->del()] found."));
                array_splice($this->clients,$i,1);
                break;
            }
        }
    }
}
/*$state = array(
	"token"=>1,
	"round"=>0,
	"preneur"=>-1,
	"contrat"=>""
);*/
?>
