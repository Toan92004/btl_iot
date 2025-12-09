<?php
set_time_limit(0);
require 'db.php';         // Kết nối MongoDB
require 'phpMQTT.php';    // Gọi thư viện MQTT bạn đã upload

use Bluerhinos\phpMQTT;   // Sử dụng namespace trong file phpMQTT.php

// Cấu hình HiveMQ Cloud
$server   = '960ad71ccaee46b19a2886a5c3551ee6.s1.eu.hivemq.cloud';
$port     = 8883;
$username = 'dodanhtoan'; 
$password = 'Toan0809';
$clientId = 'PHP_Worker_Listener_' . uniqid(); // Tạo ID ngẫu nhiên để tránh trùng
$cafile   = null; // HiveMQ Cloud public thường hỗ trợ kết nối TLS trực tiếp

$topicStatus = 'esp8266/status'; 

// Khởi tạo class từ file phpMQTT.php
$mqtt = new phpMQTT($server, $port, $clientId, $cafile);

// Kết nối (SSL/TLS = true vì HiveMQ dùng port 8883)
// Hàm connect(clean, will, user, pass)
if(!$mqtt->connect(true, null, $username, $password)) {
    exit("Không thể kết nối tới MQTT Broker!\n");
}

echo "Đang lắng nghe dữ liệu từ topic: $topicStatus ...\n";

// Đăng ký topic
$topics[$topicStatus] = array("qos" => 0, "function" => "procMsg");
$mqtt->subscribe($topics, 0);

// Vòng lặp lắng nghe tin nhắn
while($mqtt->proc()){
    
}

$mqtt->close();

// Hàm xử lý khi có tin nhắn mới
function procMsg($topic, $msg){
    global $sensorDataCollection; // Gọi biến collection từ db.php
    
    echo "Nhận tin nhắn [$topic]: $msg\n";
    
    $data = json_decode($msg, true);
    
    if ($data) {
        // Thêm timestamp chuẩn MongoDB
        $data['timestamp'] = new MongoDB\BSON\UTCDateTime();
        
        // Chèn vào MongoDB
        try {
            $sensorDataCollection->insertOne($data);
            echo "-> Đã lưu vào MongoDB thành công!\n";
        } catch (Exception $e) {
            echo "-> Lỗi lưu DB: " . $e->getMessage() . "\n";
        }
    } else {
        echo "-> Dữ liệu không phải JSON hợp lệ.\n";
    }
}
?>