<?php
set_time_limit(0); 
require 'db.php';
require 'phpMQTT.php';

use Bluerhinos\phpMQTT;   

// --- CẤU HÌNH HIVEMQ ---
$server   = '18597cd464464ab4b3c1c5d4bf9b070e.s1.eu.hivemq.cloud';
$port     = 8883;
$username = 'dodanhtoan'; 
$password = 'Toan0809';
$clientId = 'Render_Worker_' . uniqid();
$cafile = '/etc/ssl/certs/ca-certificates.crt';
$topicStatus = 'esp8266/status'; 

$mqtt = new phpMQTT($server, $port, $clientId, $cafile);

if(!$mqtt->connect(true, null, $username, $password)) {
    error_log("MQTT ERROR: Khong the ket noi toi HiveMQ!");
    exit(1); 
}

echo "MQTT: Da ket noi thanh cong toi $server\n";
$topics[$topicStatus] = array("qos" => 0, "function" => "procMsg");
$mqtt->subscribe($topics, 0);

while($mqtt->proc()){}
$mqtt->close();

// --- KHAI BÁO CÁC BIẾN GHI NHỚ TRẠNG THÁI CŨ ---
$last_temp = -999;
$last_fan_pwm = -1;
$last_led_state = -1;
$last_pir = -1; // [MỚI] Thêm biến nhớ trạng thái PIR cũ

function procMsg($topic, $msg){
    global $sensorDataCollection, $last_temp, $last_fan_pwm, $last_led_state, $last_pir; 
    
    $data = json_decode($msg, true);
    
    if ($data) {
        $is_changed = false;

        // 1. Kiểm tra biến động (So sánh với giá trị cũ)
        if (abs($data['temp'] - $last_temp) > 0.5) $is_changed = true; // Giảm xuống 0.5 để nhạy hơn chút
        if ($data['fan_pwm'] != $last_fan_pwm) $is_changed = true;
        if ($data['led_state'] != $last_led_state) $is_changed = true;
        
        // [SỬA LỖI TẠI ĐÂY] Chỉ lưu khi trạng thái PIR thay đổi (0->1 hoặc 1->0)
        if ($data['pir'] != $last_pir) $is_changed = true; 

        // 2. Lưu nếu có thay đổi
        if ($is_changed) {
            $data['timestamp'] = new MongoDB\BSON\UTCDateTime();
            try {
                $sensorDataCollection->insertOne($data);
                echo "-> [CHANGE] Da luu DB (Temp: {$data['temp']} | PIR: {$data['pir']})\n";
                
                // Cập nhật lại giá trị cũ để so sánh cho lần sau
                $last_temp = $data['temp'];
                $last_fan_pwm = $data['fan_pwm'];
                $last_led_state = $data['led_state'];
                $last_pir = $data['pir']; // [MỚI] Cập nhật PIR cũ
                
            } catch (Exception $e) {
                echo "-> Loi DB: " . $e->getMessage() . "\n";
            }
        } else {
            // echo "-> [SKIP] Du lieu giong cu, khong luu.\n";
        }
    }
}
?>