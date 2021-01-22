<?php
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));

    require_once "config.php";
    require "classes.php";
    require_once "functions.php";

	session_start();
	$sid = session_id();
	$puid = $_SESSION['puid'];
	if ($puid=="") {
		// Pas d'id, définir un nouveau puid pour ce joueur
        $puid = getNewPID();
        $_SESSION['puid'] = $puid;
	}

    if (MODE_DEBUG) {
        $puid .= '/'.$_GET['d'];
    }

	restoreObj($clients, $prefix."bcnc_clients.txt");

    $ok = false;

    if ($clients->exists($puid)) {
        $client = $clients->get($puid);
        if ($client->place == "") {
            $l = 'Location: rejoindre.php';
            if (MODE_DEBUG && $_GET['d'] != '') {
                $l .= "?d=".$_GET['d'];
            }
            header($l);
            exit;
        }
    } else {
        $clients->add();
        storeObj($clients, $prefix."bcnc_clients.txt");
    }


    session_write_close();


?>
<!DOCTYPE html>
<html>
<head>
    <title><?php printf("BCNC (%s) - %s",$puid, $client->place) ?></title>
	<link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto" rel="stylesheet">
	<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" type="text/css" href="base.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <?php
    if ( MODE_DEBUG && ($_GET['d'] != '')) {
        $d = $_GET['d'];
    } else {
        $d = 0;
    } ?>
    <script>
        var debugID = <?php echo $d;?>
    </script>
    <script src="bcnc_main.js"></script>

</head>
<body>
    <!-- Développement only -->
    <div class="selPlayer" id="selPlayer">
        Joueur courant:
        <table style="border:1px solid black; width:200px;">
        <tr>
            <td width="30">N</td>
            <td width="30">E</td>
            <td width="30">S</td>
            <td width="30">O</td>
        </tr>
        <tr>
        <td><input type="radio" name="ppId" value="1" id="ppId1" /></td>
        <td><input type="radio" name="ppId" value="2" id="ppId2" /></td>
        <td><input type="radio" name="ppId" value="3" id="ppId3" /></td>
        <td><input type="radio" name="ppId" value="4" id="ppId4" /></td>
        </tr>
        </table>
    </div>

    <!-- Main du joueur / Annonces -->
    <div class="top">
        <div class="annonces clearfix hide" id="SelAnnonces">

                <div class="selContrat">

                <?php
                // Création du sélecteur hauteur de contrat (level)
                for ($i=7; $i<18; $i++) {
                    switch ($i) {
                        case 7: 	$label = "Passe";
                                    $class = "anContrat";
                                    break;
                        case 17:	$label = "CAPOT";
                                    $class = "anContrat";
                                    break;
                        default:	$label = $i *10;
                                    $class = "anContrat";
                    }
                    echo '<div class="'.$class.'" id="anC'.($i*10).'" onClick="selContrat('.($i*10).')">'.$label.'</div>'."\n";
                }
                ?>
            </div><!-- selContrat-->
            <div class="selCouleur">

                <?php
                // Création du sélecteur couleur du contrat
                $coul=array('T','K','C','P');
                for ($i=0; $i<4; $i++) {
                    $label = '<img src="images/Cartes/misc/'.$coul[$i].'.png"/>';
                    $class = "anCouleur";
                    echo '<div class="' . $class . '" id="anC' . $coul[$i] . '" onClick="selCouleur(\''.$coul[$i].'\')">'.$label.'</div>'."\n";
                }
                ?>
                </div><!-- selCouleur-->
            <div class="anValid" onClick="anValide()">Valider</div>
        </div><!-- SelAnnonces-->

        <!-- Cartes de la main -->
        <div id="deck" class="myDeck clearfix"> Deck </div>

        <!-- Infos / état -->
        <div id="state" class="cmdLogs clearfix"></div>
    </div>

    <!-- Tapis et cartes / Infos -->
    <div class="table clearfix">

        <!-- Colonne de gauche : tapis -->
        <div id="tapis" class="tapis">

            <!-- Nord -->
            <div class="tapisLigne">
                <div class="tapisVide">&nbsp;</div>
                <div id="place_n" class="tapisPlace">&nbsp;</div>
                <div class="tapisVide">&nbsp;</div>
            </div>

            <!-- Ouest / Est + Centre (Infos) -->
            <div class="tapisLigne">
                <div id ="place_o" class="tapisPlace">&nbsp;</div>

                <div class="tapisVide" id="tapisCentre">

                    <!-- Centre du tapis -->
                    <table class="tcContainer" >
                        <tr class="tcR">
                            <td colspan=3 class="tc1 tcR" id="tc1">&nbsp;</td>
                        </tr>
                        <tr class="tcR">
                            <td class="tc4 tcR" id="tc4">&nbsp;</td>
                            <td class="tc0" id="tcC">&nbsp;</td>
                            <td class="tc2 tcR" id="tc2">&nbsp;</td></tr>
                        <tr class="tcR">
                            <td colspan=3 class="tc3 tcR" id="tc3">&nbsp;</td>
                        </tr>
                    </table>

                </div>

                <div id="place_e" class="tapisPlace">&nbsp;</div>
            </div>

            <!-- Sud -->
            <div class="tapisLigne">
                <div class="tapisVide">&nbsp;</div>
                <div id="place_s" class="tapisPlace">&nbsp;</div>
                <div class="tapisVide">&nbsp;</div>
            </div>

        </div>

        <!-- Colonne de droite (Scores / plis / Enchères) -->
        <div class="rightCol">

            <!-- Les scores -->
            <div class="scoresBox" >
                <div class="scoresTitre">SCORE</div>
                <div class="scores" id="scores"></div>
                <div class="scoresInfos" id="scoresInfo"></div>
            </div>

            <!-- Plis ou enchères -->
            <div class="plis">
                <div class="ptitre" id="pTitre">Plis</div>
                <div class="pliste clearfix" id="plis">Liste des plis</div>
            </div>

        </div>
    </div>

    <!-- A supprimer -->
    <div id="debug" class="debug">
        <div class="Tdebug">Debug zone.</div>
        <div id="messages" name="messages">??</div>
        <form action="" method="post">

            <div class="dev-cmd">
            Commande : <input type="text" name="cmd" id="cmd" value=""/> <input type="button" value="Send" onClick="sendCmd()"/>
            </div>
        </form>
        <input type="button" value="Refresh" onClick="forceRefresh()" width="100"/>
        <input type="button" value="Who win?" onClick="sendCmd('WHOWIN')" width="100"/>
        <hr/>
        <div id="commands" class="cmdLogs">//</div>
        <div id="log" class="logs" width="100%"></div>
    </div>

    <div id="dialog" title="Dialog Title"></div>

    <script>
        $( "#dialog" ).dialog({ autoOpen: false });
    </script>
</body>
</html>
