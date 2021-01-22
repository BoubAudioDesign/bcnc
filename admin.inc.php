<?php
    require_once "classes.php";
    require_once "functions.php";

// lister les clients
if ($_GET['o']=='clients') {
    restoreObj($clients, $prefix."bcnc_clients.txt");
    if ($clients) {
        //_log(sprintf("[ADMIN] create json response - clients length: %d",count($clients->clients)));
        $r = json_encode($clients->clients);
    }
    header('Content-Type: application/json');
    echo $r;
    exit;
}

if ($_GET['o']=='delclient') {
    _log(sprintf("[ADMIN del] pid = %s",$_GET['p']));
    restoreObj($clients, $prefix."bcnc_clients.txt");
    $clients->del($_GET['p']);
    storeObj($clients, $prefix."bcnc_clients.txt");
    echo "OK !";
    exit;
}
?>