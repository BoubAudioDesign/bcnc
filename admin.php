
<?php
	error_reporting(E_ALL & ~(E_STRICT|E_NOTICE|E_WARNING));
	
    require_once "config.php";
    require_once "classes.php";
    require_once "functions.php";
    include "admin.inc.php"; // traitement des requettes ajax    

	session_start();
	$sid = session_id();
	$puid = $_SESSION['puid'];
	if ($puid=="") {
		// Pas d'id, définir un nouveau puid pour ce joueur
        $puid = getNewPID();
	 	$_SESSION['puid'] = $puid;
	} else {
        if (MODE_DEBUG) {
            $puid .= '/'.$_GET['d']; 
        }
    }
	session_write_close();

    
?>
<html>
    <head>
    	<title><?php printf("BCNC - ADMIN (%s)",$puid)?></title>
        <link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="base.css">    
        <link rel="stylesheet" type="text/css" href="admin.css">
        <link href="https://fonts.googleapis.com/css?family=Open+Sans|Roboto" rel="stylesheet">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
        <link rel="stylesheet" type="text/css" href="base.css">
        <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
        <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>        
        <script src="admin.js"></script>
        <style>
            
        </style>        
    </head>
    <body>
        <div class="header">
            BCNC : Log et Analyse
        </div>
        <div class="container clearfix">
            <div class="commands">
                <div id="start" class="btn start" onclick="sendCmd('START');">START</div>
                <div id="reset" class = "btn reset">RESET CLIENTS</div>
            </div>
            <div class="atop clearfix">
                <div class="title">Etat de la partie en cours</div>
                <div class="stateMain">
                    <div class="clients" id="clients">
                    
                    </div>
                </div>
                <div class="state" id="state">?</div>
            </div>
            <div class="alog clearfix">
                <div class="title">Log</div>
                <div id="log" class="log"></div>
            </div>
        </div>
    </body>
</html>