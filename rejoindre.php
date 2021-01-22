
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
	session_write_close();

    if (MODE_DEBUG) {
        $puid .= '/'.$_GET['d']; 
    }
    
    restoreObj($clients, $prefix."bcnc_clients.txt");

    $ok = false;
    if ($_POST["place"] != ""){
        $clients->update($puid, $_POST["place"], $_POST["pseudo"]);
        storeObj($clients, $prefix."bcnc_clients.txt");
        $ok = true;
        $l = 'Location: /bcnc';
        if (MODE_DEBUG) {
            $l .= "?d=".$_GET['d'];
        }
        header($l);
    }

    if(!$ok){
        if ($clients->exists($puid)) {
            $client = $clients->get($puid);
            if ($client->place != "") {
                $message = "Déjà à la table !";

            } else {
                $message = "Inscris toi à une place libre : ";
            }
        }
    } else {
    
    }
?>
<html>
    <header>
    </header>
    <body>
        <div>
            <?php echo $message; ?>
        </div>
        <div>
            <form action="" method="post">
                Pseudo : <input type="text" name="pseudo" maxlength="20"><br/>
                Place : 
                <select name="place">
                    <option value="">-- Choisir --</option>
                    <option value="V">** Spectateur **</option>
                    <option value="N">Nord</option>
                    <option value="S">Sud</option>
                    <option value="O">Ouest</option>
                    <option value="E">Est</option>
                    
                </select>
                <input type="submit" value="Rejoindre"/>
            </form>
        </div>
    </body>
</html>