<!DOCTYPE html>
<html>
<head>
    <title>信息墙管理端</title>
    <style>
        .news {
            width: 1000px;
            height: 40px;
            overflow: hidden;
            color: #ff0000;
            font-size: 32px;
        }
    </style>
</head>
<body>
    <div id="news0" class="news"></div>
    <div>
        <textarea cols="100" rows="10"
         id="teslla"></textarea>
    </div>

   <div><input type="text1" id="message1" />
    <button id="send1">特斯拉客服</button></div>

    <div id="news2" class="news"></div>

    <div><input type="text2" id="message2" />
    <button id="send2">Send News2</button></div>
    <script>
            //generate a random 6 digit user id
            var userId = Math.floor(Math.random() * 1000000);
            var conn = new WebSocket('wss://www.qiaoguokeji.com/wss');
            init();
            var that = this;

            function init() {
                conn.onopen = function(e) {
                    var conn = that.conn;
                    console.log("Connection established!");
                    conn.send(JSON.stringify({type: 'login', id: userId}));
                };

                conn.onmessage = function(e) {
                    var conn = that.conn;
                    var news0 = document.getElementById('news0');
                    var teslla = document.getElementById('teslla');
                    var news2 = document.getElementById('news2');
                    //if the message_id is 1, then display the message in news1
                    var data = JSON.parse(e.data);
                    conn.send(JSON.stringify({type: 'info', fd: data.fd, id: userId}));
                    if(data.message_id == 1) {
                        //ring the bell
                        var audio = new Audio('https://www.qiaoguokeji.com/ting.mp3');
                        audio.play();
                        teslla.innerHTML += data.message + '\n';
                    }
                    //if the message_id is 2, then display the message in news2
                    else if(data.message_id == 2) {
                        news2.innerHTML = data.message;
                    } else if(data.message_id == 0){
                        news0.innerHTML = data.message;
                    }
                };

                //onclose, reconnect
                conn.onclose = function(e) {
                    //give the reason of the close
                    console.log("Connection closed: " + e.reason);
                };

                //if conn is closed, news0 display "失去连接，正在重连……"
                conn.onerror = function(e) {
                    var news0 = document.getElementById('news0');
                    news0.innerHTML = "失去连接，正在重连……";
                };
            }
            

            //send ping to server every 5 seconds if conn is not null
            setInterval(function() {
                var teslla = document.getElementById('teslla');
                teslla.scrollTop = teslla.scrollHeight;
                if(that.conn.readyState == 1) {
                    that.conn.send(JSON.stringify({type: 'ping', id: userId}));
                } else{
                    //reconnect
                    that.conn = new WebSocket('wss://www.qiaoguokeji.com/wss');
                    init();
                }
            }, 5000);

            //send the message when press the enter key
            document.getElementById('message1').addEventListener('keypress', function(e) {
                if(e.keyCode == 13) {
                    var conn = that.conn;
                    var message = "特斯拉客服：" + document.getElementById('message1').value;
                    //clear the input
                    document.getElementById('message1').value = '';
                    //send the message and userId to the server
                    conn.send(JSON.stringify({type: 'message', id: userId, message_id: '1', message: message}));
                }
            });


            document.getElementById('send1').addEventListener('click', function() {
                var conn = that.conn;
                var message = "特斯拉客服：" + document.getElementById('message1').value;
                //clear the input
                document.getElementById('message1').value = '';
                //send the message and userId to the server
                conn.send(JSON.stringify({type: 'message', id: userId, message_id: '1', message: message}));
            });

            document.getElementById('send2').addEventListener('click', function() {
                var message = document.getElementById('message2').value;
                //send the message and userId to the server
                conn.send(JSON.stringify({type: 'message', id: userId, message_id: '2', message: message}));
            });
    </script>
</body>
</html>