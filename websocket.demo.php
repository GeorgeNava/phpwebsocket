#!/php -q
<?php  
// Run from command prompt > php -q websocket.demo.php

// Basic WebSocket demo echoes msg back to client
include "websocket.class.php";
$ws = new WebSocket("localhost",12345);
$ws->listen();
