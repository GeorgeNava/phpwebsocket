<?php  

// Usage: $master=new WebSocket("localhost",12345);

class WebSocket{
  var $master;
  var $sockets = array();
  var $users   = array();
  var $debug   = true;
  
  function __construct($address,$port){
    error_reporting(E_ALL);
    set_time_limit(0);
    ob_implicit_flush();

    $this->master=socket_create(AF_INET, SOCK_STREAM, SOL_TCP)     or die("socket_create() failed");
    socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1)  or die("socket_option() failed");
    socket_bind($this->master, $address, $port)                    or die("socket_bind() failed");
    socket_listen($this->master,20)                                or die("socket_listen() failed");
    $this->sockets[] = $this->master;
    $this->say("Server Started : ".date('Y-m-d H:i:s'));
    $this->say("Listening on   : ".$address." port ".$port);
    $this->say("Master socket  : ".$this->master."\n");
    if( $this->debug )
    {
      $this->say("Debugging on\n");
    }

    while(true){
      $changed = $this->sockets;
      socket_select($changed,$write=NULL,$except=NULL,NULL);
      foreach($changed as $socket){
        if($socket==$this->master){
          $client=socket_accept($this->master);
          if($client<0){ $this->log("socket_accept() failed"); continue; }
          else{ $this->connect($client); }
        }
        else{
          $bytes = @socket_recv($socket,$buffer,2048,0);
          if($bytes==0){ $this->disconnect($socket); }
          else{
            $user = $this->getuserbysocket($socket);
            if(!$user->handshake){ $this->dohandshake($user,$buffer); }
            else{ $this->process($user,$this->unwrap($buffer)); }
          }
        }
      }
    }
  }

  function process($user,$msg){
    /* Extend and modify this method to suit your needs */
    /* Basic usage is to echo incoming messages back to client */
    $this->send($user->socket,$msg);
  }




// unit test for message lengths of 124, 65535, 2^64
// FIXME hook this up
  function test_send($client)
  {
    $base = "Aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa";    
    for( $i =0; $i < 10; $i++ )
    {
      $this->send($client,$base);
      $this->send($client,"".strlen($base));
      $base = $base."a";
    }
  }

// FIXME throw error if message length is longer than 0x7FFFFFFFFFFFFFFF chracters
   function send($client,$data){
    $this->say("> ".$data);


    $header = " ";
    $header[0] = chr(0x81);
    $header_length = 1;
    
    //Payload length:  7 bits, 7+16 bits, or 7+64 bits
    $dataLength = strlen($data);
    
    //The length of the payload data, in bytes: if 0-125, that is the payload length.  
    if($dataLength <= 125)
    {
      $header[1] = chr($dataLength);
      $header_length = 2;
    }
    elseif ($dataLength <= 65535)
    {
      // If 126, the following 2 bytes interpreted as a 16
      // bit unsigned integer are the payload length. 
    
      $header[1] = chr(126);
    	$header[2] = chr($dataLength >> 8);
		  $header[3] = chr($dataLength & 0xFF);
		  $header_length = 4;
    }
    else
    {
      // If 127, the following 8 bytes interpreted as a 64-bit unsigned integer (the 
      // most significant bit MUST be 0) are the payload length. 
      $header[1] = chr(127);
      $header[2] = chr(($dataLength & 0xFF00000000000000) >> 56);
      $header[3] = chr(($dataLength & 0xFF000000000000) >> 48);
      $header[4] = chr(($dataLength & 0xFF0000000000) >> 40);
      $header[5] = chr(($dataLength & 0xFF00000000) >> 32);
      $header[6] = chr(($dataLength & 0xFF000000) >> 24);
      $header[7] = chr(($dataLength & 0xFF0000) >> 16);
      $header[8] = chr(($dataLength & 0xFF00 ) >> 8);
      $header[9] = chr( $dataLength & 0xFF );
      $header_length = 10;
    }
   
    $result = socket_write($client, $header . $data, strlen($data) + $header_length);
    //$result = socket_write($client, chr(0x81) . chr(strlen($data)) . $data, strlen($data) + 2);
   if ( !$result ) {
         $this->disconnect($client);
         $client = false;
    }
    $this->say("len(".strlen($data).")");
  }

  function connect($socket){
    $user = new User();
    $user->id = uniqid();
    $user->socket = $socket;
    array_push($this->users,$user);
    array_push($this->sockets,$socket);
    $this->log($socket." CONNECTED!");
    $this->log(date("d/n/Y ")."at ".date("H:i:s T"));
  }

  function disconnect($socket){
    $found=null;
    $n=count($this->users);
    for($i=0;$i<$n;$i++){
      if($this->users[$i]->socket==$socket){ $found=$i; break; }
    }
    if(!is_null($found)){ array_splice($this->users,$found,1); }
    $index=array_search($socket,$this->sockets);
    socket_close($socket);
    $this->log($socket." DISCONNECTED!");
    if($index>=0){ array_splice($this->sockets,$index,1); }
  }
  
  function dohandshake($user,$buffer){
    $this->log("\nRequesting handshake...");
    $this->log($buffer);
    list($resource,$host,$origin,$key1,$key2,$l8b,$key0) = $this->getheaders($buffer);
    $this->log("Handshaking...");
    //$port = explode(":",$host);
    //$port = $port[1];
    //$this->log($origin."\r\n".$host);
    $upgrade  = "HTTP/1.1 101 WebSocket Protocol Handshake\r\n" .
                "Upgrade: WebSocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Origin: " . $origin . "\r\n" .
                "Sec-WebSocket-Accept: " .  $this->calcKeyHybi10($key0) . "\r\n" . "\r\n" ;

    socket_write($user->socket,$upgrade,strlen($upgrade));
    $user->handshake=true;
    $this->log($upgrade);
    $this->log("Done handshaking...");
    return true;
  }

  function calcKeyHybi10($key){
     $CRAZY = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
     $sha = sha1($key.$CRAZY,true);
     return base64_encode($sha);
  }
  
  function getheaders($req){
    $r=$h=$o=null;
    if(preg_match("/GET (.*) HTTP/"               ,$req,$match)){ $r=$match[1]; }
    if(preg_match("/Host: (.*)\r\n/"              ,$req,$match)){ $h=$match[1]; }
    if(preg_match("/Origin: (.*)\r\n/"            ,$req,$match)){ $o=$match[1]; }
    if(preg_match("/Sec-WebSocket-Key1: (.*)\r\n/",$req,$match)){ $this->log("Sec Key1: ".$sk1=$match[1]); }
    if(preg_match("/Sec-WebSocket-Key2: (.*)\r\n/",$req,$match)){ $this->log("Sec Key2: ".$sk2=$match[1]); }
    if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/" ,$req,$match)){ $this->log("new Sec Key2: ".$sk0=$match[1]); }
    if($match=substr($req,-8))                                                                  { $this->log("Last 8 bytes: ".$l8b=$match); }
    return array($r,$h,$o,$sk1,$sk2,$l8b,$sk0);
  }

  function getuserbysocket($socket){
    $found=null;
    foreach($this->users as $user){
      if($user->socket==$socket){ $found=$user; break; }
    }
    return $found;
  }

  function     say($msg=""){ echo $msg."\n"; }
  function     log($msg=""){ if($this->debug){ echo $msg."\n"; } }
  function    wrap($msg=""){ return chr(0).$msg.chr(255); }

  // copied from http://lemmingzshadow.net/386/php-websocket-serverclient-nach-draft-hybi-10/
  function unwrap($data="")
  {		
  	$bytes = $data;
  	$dataLength = '';
  	$mask = '';
  	$coded_data = '';
  	$decodedData = '';
  	$secondByte = sprintf('%08b', ord($bytes[1]));		
  	$masked = ($secondByte[0] == '1') ? true : false;		
  	$dataLength = ($masked === true) ? ord($bytes[1]) & 127 : ord($bytes[1]);
  	if($masked === true)
  	{
  		if($dataLength === 126)
  		{
  		   $mask = substr($bytes, 4, 4);
  		   $coded_data = substr($bytes, 8);
  		}
  		elseif($dataLength === 127)
  		{
  			$mask = substr($bytes, 10, 4);
  			$coded_data = substr($bytes, 14);
  		}
  		else
  		{
  			$mask = substr($bytes, 2, 4);		
  			$coded_data = substr($bytes, 6);		
  		}	
  		for($i = 0; $i < strlen($coded_data); $i++)
  		{		
  			$decodedData .= $coded_data[$i] ^ $mask[$i % 4];
  		}
  	}
  	else
  	{
  		if($dataLength === 126)
  		{		   
  		   $decodedData = substr($bytes, 4);
  		}
  		elseif($dataLength === 127)
  		{			
  			$decodedData = substr($bytes, 10);
  		}
  		else
  		{				
  			$decodedData = substr($bytes, 2);		
  		}		
  	}
 
	return $decodedData;
  }
  
  

} //class WebSocket

class User{
  var $id;
  var $socket;
  var $handshake;
}

?>
