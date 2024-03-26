<?php
$server = new swoole_websocket_server("127.0.0.1", 9502);
$server->on('open', function($server, $request){
    echo "new connection open: {$request->fd}\n";
    //send json string "hello, welcome"
    $json_string = json_encode(['message_id' => 0, 'message' => '已经连接服务器，等待接收消息……', 'fd' => $request->fd]);
    $server->push($request->fd, $json_string);
});
$server->on('message', function($server, $frame){
    //echo "received message: {$frame->data}\n";
    $data = json_decode($frame->data, true);
    $device_id = $data['device_id']?? null;
    $device_fd = $data['device_fd']?? null;
    
    //to check if the client is a websocket client
    $client = $server->getClientInfo($frame->fd);
    if(!isset($client['websocket_status'])) {
        //use coroutine to start tcp client to send message to device
        $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
        if($client->connect('127.0.0.1', 9501, 0.5)) {
            $client->send($frame->data);
            $response = $client->recv();
            $server->push($frame->fd, $response);
            $client->close();
        } else {
            $server->push($device_fd, "server offline");
        
        }
    }else {
        //send message to all clients
        //if the message type is 'message', then send the payload to all clients
        if($data['type'] == 'message') {
            echo "received message: {$frame->data}\n";
            foreach($server->connections as $fd) {
                //send json string include the message and the message_id
                echo $fd.PHP_EOL;
                $json_string = json_encode(['message_id' => $data['message_id'], 'fd'=>$fd, 'message' => $data['message']]);
                $server->push($fd, $json_string);
            }
        }else if($data['type'] == 'login') {
            echo "received login: {$frame->data}\n";
        }else if($data['type' == 'info']) {
            echo "info: {$frame->data}\n";
        }else if($data['type'] == 'ping') {
            //send pong to the client
            $json_string = json_encode(['type' => 'pong', 'message_id' => 255, 'message' => 'pong', 'fd' => $frame->fd]);
            $server->push($frame->fd, $json_string);
            echo "received ping: {$frame->data}\n";
        }
    }
});
$server->on('close', function($server, $fd){
    echo "connection close: {$fd}\n";
});

$server->start();
?>