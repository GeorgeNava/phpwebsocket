PHPWEBSOCKET
============

So here is a quick hack to implement hybi-10 websockets in php!  
Check out the [hybi-10 standard here](http://tools.ietf.org/html/draft-ietf-hybi-thewebsocketprotocol-10#section-4.2)  
Check [This Wikipedia Page](http://en.wikipedia.org/wiki/WebSocket#Browser_support) for suppored list of browsers.  


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

View source code of [client.html](http://github.com/esromneb/phpwebsocket/blob/master/client.html)


Server side
-----------

  View source code of [chatbot.demo.php](http://github.com/esromneb/phpwebsocket/blob/master/chatbot.demo.php)

Steps to run the test:
----------------------
* [See these instructions](http://net.tutsplus.com/tutorials/javascript-ajax/start-using-html5-websockets-today/) for info on how to run this under windows ( xampp: Apache + PHP )
* Save both files, client.html and chatbot.demo.php, in a folder in your local server running Apache and PHP.
* From the command line, run the "php -q chatbot.demo.php" program to listen for socket connections.
* Open Google Chrome and point to the client.html page
* Done, your browser now has a full-duplex channel with the server.
* Start sending commands to the server to get some responses.

WebSockets for the masses!
==========================

Author  
------
Ben Morse  
George Nava  


[http://portforwardpodcast.com/](http://portforwardpodcast.com/)  
[http://twitter.com/PortFwdPodcast](http://twitter.com/PortFwdPodcast)  
