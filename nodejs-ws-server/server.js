/*===============================================================
*   Copyright (C) 2020 All rights reserved.
*   
*   file     : server.js
*   author   : clwu
*   date     : 2020-02-21
*   descripe : websocket server
*
*   modify   : 
*
================================================================*/

const WebSocket = require('ws');

const wss = new WebSocket.Server({
                                    server : 'localhost',
                                    port   : 8888
                                 });

wss.on('connection', function connection(ws) {
    ws.on('message', function incoming(message) {
        console.log('server received: %s', message);
        ws.send(message); // echo
    });

    ws.send('something on connection');
});

/* vim: set nu expandtab softtabstop=4 tabstop=4 tw=80: */
