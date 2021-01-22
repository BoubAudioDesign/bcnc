<?
/*
Presque toutes les propriétés de la classe ont une valeur par défaut
vous pouvez bien entendu les changer dans le script start_chat.php en
spécifiant l'instance de l'objet suivi de la propriété $objet->propriété=valeur.
*/
class Chat_Server{
//Nombre de connexions concurrentes au maximum
var $max_clients=10;
//Un tableau qui contiendra les ID de sockets de tous les clients connectés
var $clients=array();
//La socket "maître" sur laquelle le serveur écoute
var $socket=null;
//Contiendra l'id de chaque nouvelle connexion
var $client=null;
//Contient un message à afficher lorsqu'une connexion est refusée
var $denied;
//0=afficher les infos sur l'écran, 1=enregistrer les log dans un fichier
var $log=0;
//Nom du fichier log où stocker les infos
var $logfile='ChatServerLog.log';
//Ressource du fichier log
var $fp_log;
//Contient l'en-tête html à envoyer à chaque nouveau client
var $html;
//Si un pseudo est déjà pris, envoyer ce message au client avant de refuser sa connexion
var $Already_In_use;
//Type d'info que l'on envoie au(x) client(s) 
 
//1->MAJ liste connectés+message 2->envoi d'un message 3->ne pas reformater le message
var $write_type=0;
//Méthode qui démarre le serveur
function Start($adress,$port){
  $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//on lie la ressource sur laquelle le serveur va écouter
  socket_bind($this->socket, $address, $port) or die($this->destroy(null));
//On prépare l'écoute
  socket_listen($this->socket);
//Boucle infinie, car le serveur ne doit s'arrêter que si on lui demande
  while(true){
//Le code se bloque jusqu'à ce qu'une nouvelle connexion cliente est établie
    $this->client=socket_accept($this->socket);
//Lors d'une connexion, le code reprend ici, il est temps de lire ce qu'on nous envoie
    $this->read_write();
  }
}
 
//Cette méthode lit les données reçues d'un client et les redistribue
function read_write(){
//L'en-tête fait 5 bytes, donc on lit 5 bytes et on vérifie s'il s'agit d'une connexion
//cliente ou s’il s'agit d'un envoi de message ou encore s'il s'agit de stopper le serveur
  $input = socket_read($this->client, 5);
//11111 est le signal d'arrêt du serveur, vous pourriez en définir un autre
  if($input=='11111'){
    $this->write_type=3;
//On envoie un message à tous les clients, notifiant l'arrêt du serveur
    $this->Write_To_Clients("<script language=\"JavaScript\">alert('Le serveur a été arrêté')</script>");
// Temporisation de 3 secondes pour que les clients aient le temps de lire le message
    sleep(3);
//On appelle la méthode qui arrête le serveur proprement
    $this->Destroy(null);
  }
 
//Si le mot clé "get" figure dans l'en-tête, c'est qu'il s'agit d'une nouvelle connexion
  if(substr_count($input,'GET')>0){ 
//Si le nombre maximum autorisé de connexions n'est pas atteint
    if($this->max_clients > count($this->clients)){
//On lit les 50 octets suivants pour récupérer le pseudo
      $nick=socket_read($this->client,50);
      if(substr_count($nick,'Pseudo')==0){
//Accès refusé, car pseudo invalide dans le cas où le client tape directement l'adresse dans la barre d'URL
         socket_close($this->client);
         return;
      }
//On récupère le pseudo qui se trouve après '?Pseudo=' et fait 15 caractères
      $nick=trim(substr($nick,(strpos($nick,'=')+1),15));
//On contrôle la validité du pseudo      
      if(substr_count($nick,' ')>0 || $nick==null){
        socket_close($this->client);
        return;
      }
//On tente d'obtenir l'IP du client.
      @socket_getpeername($this->client,&$adress,&$port);
//On vérifie que le pseudo n'est pas déjà utilisé
      if($this->clients[$nick]==null){
//On ajoute la connexion au tableau des connexions
        $this->clients[$nick]=$this->client;
//On avertit les autres que ce client vient de se connecter
        $this->Write_Connected();
        $this->write_type=1;
//On met à jour la liste de tous les connectés chez tous les clients
        $this->Write_To_Clients($nick.':'.$adress);
//On enregistre ou affiche qu'une nouvelle connexion a été établie
        $this->Logging('Nouvelle connexion client : '.$adress.':'.$port);
      }
      else{
//Si le pseudo est déjà utilisé, on refuse la connexion
       $this->write_type=3;
       @socket_write($this->client,$this->html.$this->Already_In_use,(strlen($this->Already_In_use)+strlen($this->html)));
       sleep(1);
       @socket_close($this->client);
      }
    }
    else{        
//Connexion refusée si le nombre maximal de connexions est atteint
      @socket_write($this->client,$this->denied,strlen($this->denied));
      sleep(1);
      socket_getpeername($this->client,&$adress,&$port);
      $this->Logging('Client '.$this->client.' : '.$adress.':'.$port.' a eu un accès refusé');
      @socket_close($this->client);
    }
 
  }
  else{
/*Si ce n’est pas un client de type get, alors c'est un client qui tente d'écrire un message
donc on récupère le paquet grâce aux parties numériques significatives de l'en-tête*/  
    $paquet=socket_read($this->client,intval(substr($input,0,2))+intval(substr($input,2,3)));
    $pseudo=substr($paquet,0,intval(substr($input,0,2)));
    $message=substr($paquet,intval(substr($input,0,2)),intval(substr($input,2,3)));
//On ajoute des br dans le message
    $this->Wrap_Message(&$message);                
    $full_client_msg="<font color='#FF0000'> [$pseudo a écrit:]<font><font color='#AF00AF'>$message</font><br>";
    $this->write_type=2;
//On envoie le message à tous les clients connectés
    $this->Write_To_Clients($full_client_msg);
  }
}
//Cette méthode distribue les messages à tous les connectés
function Write_To_Clients($msg){
  reset($this->clients);
  if($this->write_type==1){
    $info=split(':',$msg);
    $add_to_list="<script language=\"JavaScript\">add_opt(\"".$info[0]."\",\"".$info[0]."\");</script>";
    $info_board="<script language=\"JavaScript\">document.getElementById('info_board').innerHTML=\"".$info[0];
    $info_board.="-".$info[1]." vient de se connecter\";</script>";
  }
  if($this->write_type == 2){
    $full_msg="<script language=\"JavaScript\">document.getElementById('content').innerHTML+=\"".$msg."\"</script>";
  }
 
  if($this->write_type == 3){
    $full_msg=$msg;
  }
//On parcourt le tableau des connexions
  while ($value = current($this->clients)) {
    if(is_resource($value)){
      if($this->write_type == 1){
        if($value != $this->client){
          $full_msg=$add_to_list.$info_board;
        }
        else{
          $full_msg=$info_board;
        }
      }
      $this->Logging('Ecriture de '.$msg.' to '.$value);
      if((@socket_write($value,$full_msg,strlen($full_msg))===false)){
        $this->Logging ('déconnexion de '.key($this->clients).':'.$value);
        $disconnected[]=key($this->clients);
//Si l'écriture vers un client ne fonctionne pas, on en déduit qu'il est déconnecté
        unset($this->clients[key($this->clients)]); 
      }
    }
    next($this->clients);
  }
//Si des clients se sont déconnectés, on fait un appel récursif de cette fonction
//pour mettre à jour la liste des connectés et envoyer un message d'info
  if(count($disconnected)>0){ 
    $msg="<script language=\"JavaScript\">document.getElementById('info_board').innerHTML=\"";
    for($i=0;$i<count($disconnected);$i++){
      $msg.=$disconnected[$i]." s'est déconnecté<br>";
      $msg1.="<script language=\"JavaScript\">remove_opt('".$disconnected[$i]."');";
    }
    $msg.="\";</script>";
    $msg1.="</script>";
    $fullmsg=$msg.$msg1;
    $this->write_type=3;
    $this->Write_To_Clients($fullmsg); 
  }
  else{
    return;
  }
 
}
 
//Cette méthode rajoute des br pour limiter le nombre de caractères par ligne.
function Wrap_Message(&$msg){
  $j=0;
  for($i=0;$i<strlen($msg);$i++){
    $msg_wrapped.=$msg[$i];
    if($j == 50){
      $msg_wrapped.='<br>';
      $j=0;
    }
    $j++;
  }
  $msg=$msg_wrapped;
}
 
//Cette méthode envoie à tous les clients ceux qui sont connectés
function write_Connected(){
  reset($this->clients);
  while ($value = current($this->clients)) {
    if(is_resource($value)){
      $msg.="<script language=javascript>add_opt(\"".key($this->clients)."\",\"".key($this->clients)."\")</script>";
    }
    next($this->clients);
  }
  $full_msg=$this->html.$msg;
  @socket_write($this->client,$full_msg,strlen($full_msg));
}
//Cette méthode donne des infos sur le processing du serveur
//On peut soit, stocker l'info dans un fichier log, soit afficher l'output
//sur la sortie standard.
function Logging($msg){
  if($this->log == 1){
    if(empty($this->fp_log)){
      $this->fp_log=fopen($this->logfile,'w') or die($this->destroy('Erreur de création du fichier log'));
    }
    fwrite($this->fp_log,$msg."\n");
  }
  else{
    echo "\n".$msg."\n";
  }
  return;
}
//Cette méthode est appelée lorsque l'on stoppe le serveur
//et le stoppe de manière propre en fermant toutes les connexions
//clients et en leur envoyant un message au préalable.
function destroy($err){
  if($err != null){
    $this->Logging($err);
  }
  else{
    $this->Logging(socket_strerror(socket_last_error()));
  }
  reset($this->clients);
  while ($sock_cli = current($this->clients)) {
    @socket_close($sock_cli);
    next($this->clients);
  }
 
  if(is_resource($fp)){
    fclose($fp);
  }
  @socket_close($this->socket);
  die();
}
 
}
 
?>