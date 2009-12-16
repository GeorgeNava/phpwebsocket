#!/php -q
<?php
/* 

Run from command line:
> php -q server.php

*/

error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush();

echo "Initiating...\n";
$address = 'localhost';
$port    = 12345;
$maxconn = 999;
$uselog  = true;
   
$master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("socket_create() failed");
socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($master, $address, $port) or die("socket_bind() failed");
socket_listen($master,20)             or die("socket_listen() failed");

echo "Server Started : ".date('Y-m-d H:i:s')."\n";
echo "Max connections: ".$maxconn."\n";
echo "Master socket  : ".$master."\n";
echo "Listening on   : ".$address." port ".$port."\n";

$users     = array();
$sockets   = array($master);
$handshake = false;

while(true){
  $readsockets = $sockets;
  $numsockets = socket_select($readsockets,$write=NULL,$except=NULL,NULL);
  foreach($readsockets as $socket){
    console();
    if ($socket==$master) {
      if(($client=socket_accept($master))<0) {
        console("socket_accept() failed: reason: ".socket_strerror(socket_last_error($client)));
        continue;
      }
      else{
        array_push($sockets,$client);
        console($client." CONNECTED!");
      }
    }
    else{
      $bytes = @socket_recv($socket,$buffer,2048,0);
      if($bytes==0){ disconnected($socket); }
      else{
        
         /* TODO: store handshake per socket */
        if(!$handshake){
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
          //$resource = "/websocket/server.php";
          //$host     = "localhost:12345";
          //$origin   = "http://localhost";
          console("Handshaking...");
          $upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
                      "Upgrade: WebSocket\r\n" .
                      "Connection: Upgrade\r\n" .
                      "WebSocket-Origin: " . $origin . "\r\n" .
                      "WebSocket-Location: ws://" . $host . $resource . "\r\n" .
                      "\r\n";
          $handshake = true;
          socket_write($client,$upgrade.chr(0),strlen($upgrade.chr(0)));
          console($upgrade);
          console("Done handshaking...");
        }
        else{
          console("<".$buffer);
          $action = substr($buffer,1,$bytes-2); // remove chr(0) and chr(255)
          switch($action){
            case "hello" : send($socket,"hello human"); break;
            case "hi"    : send($socket,"zup human"); break;
            case "name"  : send($socket,"my name is Multivac, silly I know"); break;
            case "age"   : send($socket,"I am older than time itself"); break;
            case "date"  : send($socket,"today is ".date("Y.m.d")); break;
            case "time"  : send($socket,"server time is ".date("H:i:s")); break;
            case "thanks": send($socket,"you're welcome"); break;
            case "bye"   : send($socket,"bye"); break;
            default      : send($socket,$action." not understood"); break;
          }
        }
      }
    }
  }
}

//---------------------------------------------------------------
function wrap($msg){ return chr(0).$msg.chr(255); }

function send($client,$msg){ 
  console("> ".$msg);
  $msg = wrap($msg);
  socket_write($client,$msg,strlen($msg));
} 

function disconnected($socket){
  global $sockets;
  $index = array_search($socket, $sockets);
  if($index>=0){ unset($sockets[$index]); }
  socket_close($socket);
  console($socket." disconnected!");
}

function console($msg=""){
  global $uselog;
  if($uselog){ echo $msg."\n"; }
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
?>