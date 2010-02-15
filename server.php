#!/php -q
<?php  /*  >php -q server.php  */
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

$master  = WebSocket("localhost",12345);
$sockets = array($master);
$users   = array();
$debug   = false;

while(true){
  $changed = $sockets;
  socket_select($changed,$write=NULL,$except=NULL,NULL);
  foreach($changed as $socket){
    if($socket==$master){
      $client=socket_accept($master);
      if($client<0){ console("socket_accept() failed"); continue; }
      else{ connect($client); }
    }
    else{
      $bytes = @socket_recv($socket,$buffer,2048,0);
      if($bytes==0){ disconnect($socket); }
      else{
        $user = getuserbysocket($socket);
        if(!$user->handshake){ dohandshake($user,$buffer); }
        else{ process($user,$buffer); }
      }
    }
  }
}

//---------------------------------------------------------------
function process($user,$msg){
  $action = unwrap($msg);
  say("< ".$action);
  switch($action){
    case "hello" : send($user->socket,"hello human");                       break;
    case "hi"    : send($user->socket,"zup human");                         break;
    case "name"  : send($user->socket,"my name is Multivac, silly I know"); break;
    case "age"   : send($user->socket,"I am older than time itself");       break;
    case "date"  : send($user->socket,"today is ".date("Y.m.d"));           break;
    case "time"  : send($user->socket,"server time is ".date("H:i:s"));     break;
    case "thanks": send($user->socket,"you're welcome");                    break;
    case "bye"   : send($user->socket,"bye");                               break;
    default      : send($user->socket,$action." not understood");           break;
  }
}

function send($client,$msg){ 
  say("> ".$msg);
  $msg = wrap($msg);
  socket_write($client,$msg,strlen($msg));
} 

function WebSocket($address,$port){
  $master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
  socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
  socket_bind($master, $address, $port)                    or die("socket_bind() failed");
  socket_listen($master,20)                                or die("socket_listen() failed");
  echo "Server Started : ".date('Y-m-d H:i:s')."\n";
  echo "Master socket  : ".$master."\n";
  echo "Listening on   : ".$address." port ".$port."\n\n";
  return $master;
}

function connect($socket){
  global $sockets,$users;
  $user = new User();
  $user->id = uniqid();
  $user->socket = $socket;
  array_push($users,$user);
  array_push($sockets,$socket);
  console($socket." CONNECTED!");
}

function disconnect($socket){
  global $sockets,$users;
  $found=null;
  $n=count($users);
  for($i=0;$i<$n;$i++){
    if($users[$i]->socket==$socket){ $found=$i; break; }
  }
  if(!is_null($found)){ array_splice($users,$found,1); }
  $index = array_search($socket,$sockets);
  socket_close($socket);
  console($socket." DISCONNECTED!");
  if($index>=0){ array_splice($sockets,$index,1); }
}

function dohandshake($user,$buffer){
  console("\nRequesting handshake...");
  console($buffer);
  /*        
    GET {resource} HTTP/1.1
    Upgrade: WebSocket
    Connection: Upgrade
    Host: {host}
    Origin: {origin}
    \r\n
  */
  list($resource,$host,$origin) = getheaders($buffer);
  //$resource = "/phpwebsocketchat/server.php";
  //$host     = "localhost:12345";
  //$origin   = "http://localhost";
  console("Handshaking...");
  $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
              "Upgrade: WebSocket\r\n" .
              "Connection: Upgrade\r\n" .
              "WebSocket-Origin: " . $origin . "\r\n" .
              "WebSocket-Location: ws://" . $host . $resource . "\r\n" .
              "\r\n";
  socket_write($user->socket,$upgrade.chr(0),strlen($upgrade.chr(0)));
  $user->handshake=true;
  console($upgrade);
  console("Done handshaking...");
  return true;
}

function getheaders($req){
  $req  = substr($req,4); /* RegEx kill babies */
  $res  = substr($req,0,strpos($req," HTTP"));
  $req  = substr($req,strpos($req,"Host:")+6);
  $host = substr($req,0,strpos($req,"\r\n"));
  $req  = substr($req,strpos($req,"Origin:")+8);
  $ori  = substr($req,0,strpos($req,"\r\n"));
  return array($res,$host,$ori);
}

function getuserbysocket($socket){
  global $users;
  $found=null;
  foreach($users as $user){
    if($user->socket==$socket){ $found=$user; break; }
  }
  return $found;
}

function     say($msg=""){ echo $msg."\n"; }
function    wrap($msg=""){ return chr(0).$msg.chr(255); }
function  unwrap($msg=""){ return substr($msg,1,strlen($msg)-2); }
function console($msg=""){ global $debug; if($debug){ echo $msg."\n"; } }

class User{
  var $id;
  var $socket;
  var $handshake;
}

?>