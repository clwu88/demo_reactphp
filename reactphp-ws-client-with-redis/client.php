<?php
/*===============================================================
*   Copyright (C) 2020 All rights reserved.
*   
*   file     : RobotEngine.php
*   author   : clwu
*   date     : 2020-03-02
*   descripe : websocket client, powered by ReactPHP
*
*   modify   : 
*
================================================================*/
require 'vendor/autoload.php';

$robotKey = $argv[1];

$loop = \React\EventLoop\Factory::create();


$reactConnector = new \React\Socket\Connector($loop, [ 'timeout' => 10 ]);
$connector      = new \Ratchet\Client\Connector($loop, $reactConnector);

$connector('ws://127.0.0.1:8888', ['protocol1'/*, 'subprotocol2'*/], ['Origin' => 'http://localhost'])
    ->then(function(Ratchet\Client\WebSocket $conn) use ($loop, $robotKey) {

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

        // 3) setup redis subscribe
        $factory = new Clue\React\Redis\Factory($loop);
        $client  = $factory->createLazyClient('redis://localhost?db=2');

        $channel = "msg:{$robotKey}";

        $client->subscribe($channel)->then(function () {
            echo 'Now subscribed to channel ' . PHP_EOL;
        }, function (Exception $e) use ($client) {
            $client->close();
            echo 'Unable to subscribe: ' . $e->getMessage() . PHP_EOL;
        });

        $client->on('message', function ($channel, $message) use ($conn) {
            // echo 'Message on ' . $channel . ': ' . $message . PHP_EOL;
            $body = json_encode($message);
            $conn->send($body); // bridge to ws
        });

        // automatically re-subscribe to channel on connection issues
        $client->on('unsubscribe', function ($channel) use ($client, $loop) {
            echo 'Unsubscribed from ' . $channel . PHP_EOL;

            $loop->addPeriodicTimer(2.0, function ($timer) use ($client, $channel, $loop){
                $client->subscribe($channel)->then(function () use ($timer, $loop) {
                    echo 'Now subscribed again' . PHP_EOL;
                    $loop->cancelTimer($timer);
                }, function (Exception $e) {
                    echo 'Unable to subscribe again: ' . $e->getMessage() . PHP_EOL;
                });
            });
        });

    }, function(\Exception $e) use ($loop) {
        echo "Could not connect: {$e->getMessage()}\n";
        $loop->stop();
    });

$loop->run();

/* vim: set nu expandtab softtabstop=4 tabstop=4 tw=80: */
