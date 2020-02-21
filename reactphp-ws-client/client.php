<?php
/*===============================================================
*   Copyright (C) 2020 All rights reserved.
*   
*   file     : client.php
*   author   : clwu
*   date     : 2020-02-21
*   descripe : websocket client, powered by ReactPHP
*
*   modify   : 
*
================================================================*/
require 'vendor/autoload.php';


$loop = React\EventLoop\Factory::create();


$reactConnector = new \React\Socket\Connector($loop, [ 'timeout' => 10 ]);
$connector = new \Ratchet\Client\Connector($loop, $reactConnector);

$connector('ws://127.0.0.1:8888', ['protocol1'/*, 'subprotocol2'*/], ['Origin' => 'http://localhost'])
    ->then(function(Ratchet\Client\WebSocket $conn) use ($loop) {
        // 1) 注册事件 handler
        $conn->on('message', function(\Ratchet\RFC6455\Messaging\MessageInterface $msg) use ($conn) {
            echo "Client Received: {$msg}\n";
            // $conn->close();
        });

        $conn->on('close', function($code = null, $reason = null) {
            echo "Connection closed ({$code} - {$reason})\n";
        });

        $conn->send('Client finish websocket connecting');

        // // 2) 定时发ws心跳
        // $loop->addPeriodicTimer(1, function() use ($conn) {
        //     // TODO
        //     $conn->send('heartbeat');
        // });

        // 3) setup http server
        $server = new React\Http\Server(function (Psr\Http\Message\ServerRequestInterface $request) use ($conn) {
            // TODO
            $body = $request->getParsedBody();
            $body = json_encode($body);
            $conn->send($body); // bridge to ws

            return new React\Http\Response(
                200,                              // status code
                ['Content-Type' => 'text/plain'], // header
                "Hello World!\n"                  // body
            );
        });

        $socket = new React\Socket\Server(9999, $loop);
        $server->listen($socket);

        echo "Clinet's Server is running at http://127.0.0.1:9999\n";

    }, function(\Exception $e) use ($loop) {
        echo "Could not connect: {$e->getMessage()}\n";
        $loop->stop();
    });

$loop->run();

/* vim: set nu expandtab softtabstop=4 tabstop=4 tw=80: */
