PHPWEBSOCKET
============

So here is a quick hack to implement websockets in php.

As of Dec/09 the only browser that supports websockets is Google Chrome.

Get the developers release from here [Google Chrome Dev Channel](http://www.chromium.org/getting-involved/dev-channel)

Hurry up Firefox and Safari, you're late to the party!

Changelog
---------
* 20100215 - Added Users list to keep track of handshakes
* 20100215 - Minor cosmetic changes

Client side
-----------

	var host = "ws://localhost:12345/websocket/server.php";
	try{
	  socket = new WebSocket(host);
	  log('WebSocket - status '+socket.readyState);
	  socket.onopen    = function(msg){ log("Welcome - status "+this.readyState); };
	  socket.onmessage = function(msg){ log("Received: "+msg.data); };
	  socket.onclose   = function(msg){ log("Disconnected - status "+this.readyState); };
	}
	catch(ex){ log(ex); }

View source code of [client.html](http://github.com/GeorgeNava/phpwebsocket/blob/master/client.html)


Server side
-----------

	log("Handshaking...");
	list($resource,$host,$origin) = getheaders($buffer);
	$upgrade = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
			   "Upgrade: WebSocket\r\n" .
			   "Connection: Upgrade\r\n" .
			   "WebSocket-Origin: " . $origin . "\r\n" .
			   "WebSocket-Location: ws://" . $host . $resource . "\r\n" .
			   "\r\n";
	$handshake = true;
	socket_write($socket,$upgrade.chr(0),strlen($upgrade.chr(0)));

View source code of [server.php](http://github.com/GeorgeNava/phpwebsocket/blob/master/server.php)

Steps to run the test:
----------------------

* Save both files, client.html and server.php, in a folder in your local server running Apache and PHP.
* From the command line, run the server.php program to listen for socket connections.
* Open Google Chrome (dev build) and point to the client.html page
* Done, your browser now has a full-duplex channel with the server.
* Start sending commands to the server to get some responses.

2010 will be an interesting year.

WebSockets for the masses!
==========================

Author
------
George Nava

[http://georgenava.appspot.com](http://georgenava.appspot.com)

[http://mylittlehacks.appspot.com](http://mylittlehacks.appspot.com)

[http://twitter.com/georgenava](http://twitter.com/georgenava)
