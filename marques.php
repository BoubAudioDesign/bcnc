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
		$bytes = random_bytes(5);
		$puid = bin2Hex($bytes);
	 	$_SESSION['puid'] = $puid;
	}
	session_write_close();

?>
<!DOCTYPE html>
<html>
    <head>
    	<link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="base.css">
        <link rel="stylesheet" type="text/css" href="marques.css">
    </head>
    <body>
        <div class="marqueHeader">
            Feuille de marque
        </div>
        <div class="marqueContainer">
            <table class="marqueTable">
                <tr>
                    <td>&nbsp;</td>
                    <td class="col hdr points">
                        Nord / Sud<br/>
                        1/3
                    </td>
                    <td class="col hdr points">
                        Points
                    </td>
                    <td class="col hdr points">
                        Points
                    </td>
                    <td class="col hdr points">
                        Est / Ouest<br/>
                        2/4
                    </td>
                    <td class="col hdr points large">
                        Contrat
                    </td>
                    <td class="col hdr points">
                        Par
                    </td>
                </tr>
                <?php
                restoreContext();
                if ($feuille->marques) {
                    $currentIdPartie = $feuille->currentIdPartie;
                    $total = array(0,0);
                    $id=0;
                    foreach ($feuille->marques as $marque) {
                        if ( $idPartie == $marque->idPartie ) {
                            $contrat = $marque->contrat . '&nbsp;<img src="images/Cartes/misc/' . $marque->couleur . '.png" height="16px"/>';
                            $scoresMarque = $marque->totaux();
														if ($id>0) {
															for($i=0; $i<2; $i++) {
	                                $total[$i] += $scoresMarque[$i];
	                                if ($marque->preneur=="N" || $marque->preneur=="S") {

	                                }
	                            }
														}
                            printf("<tr class='ligne%d'>".
                                   "<td class='col right'>%s</td>".
                                   "<td class='col right'>%d</td><td class='col right'><span class='light'>%d</span> %d</td>".
                                   "<td class='col right'><span class='light'>%d</span> %d</td><td class='col right'>%d</td>".
                                   "<td class='col center'>%s</td><td class='col center'>%s</td></tr>\n",

                                   ($id>0)?($id % 2):'3',
                                   ($id>0)?$id:"&nbsp;",
                                   $total[0],
                                $marque->scores[0],$scoresMarque[0],
                                $marque->scores[1],$scoresMarque[1],
                                $total[1],
                                $contrat,
                                $marque->preneur);

														if ($id>0){
															$s = $scoresMarque;
	                            for ($i=0; $i<2; $i++) {
	                                $st[$i] += $s[$i];
	                            }
														}
														$id++;
                        }

                    }
                } else {
                    echo "<tr><td></td><td colspan='6' class='col center'><br/>Aucune partie en cours...</br></td></tr>";
                }
                ?>
            </table>
        </div>
    </body>
</html>
