<?php
set_time_limit(0); // Chạy mãi mãi
require 'db.php';
require 'phpMQTT.php';

use Bluerhinos\phpMQTT;   

// --- CẤU HÌNH HIVEMQ ---
$server   = '18597cd464464ab4b3c1c5d4bf9b070e.s1.eu.hivemq.cloud';
$port     = 8883;
$username = 'dodanhtoan'; 
$password = 'Toan0809';
$clientId = 'Render_Worker_' . uniqid();

// --- KHẮC PHỤC LỖI KẾT NỐI TẠI ĐÂY ---
// Trên Render (Linux), file chứng chỉ gốc nằm ở đây
$cafile = '/etc/ssl/certs/ca-certificates.crt';

// Topic lắng nghe
$topicStatus = 'esp8266/status'; 

// Khởi tạo
$mqtt = new phpMQTT($server, $port, $clientId, $cafile);

// Kết nối (Tham số đầu tiên là clean session = true)
if(!$mqtt->connect(true, null, $username, $password)) {
    // Nếu lỗi, in ra log để debug trên Render
    error_log("MQTT ERROR: Khong the ket noi toi HiveMQ!");
    exit(1); 
}

echo "MQTT: Da ket noi thanh cong toi $server\n";
echo "Dang lang nghe topic: $topicStatus ...\n";

$topics[$topicStatus] = array("qos" => 0, "function" => "procMsg");
$mqtt->subscribe($topics, 0);

while($mqtt->proc()){
    // Vòng lặp lắng nghe
}

$mqtt->close();
// Biến toàn cục để nhớ giá trị cũ
$last_temp = -999;
$last_fan_pwm = -1;
$last_led_state = -1;

// function procMsg($topic, $msg){
//     global $sensorDataCollection; 
    
//     echo "Nhan du lieu: $msg\n";
//     $data = json_decode($msg, true);
    
//     if ($data) {
//         // Thêm timestamp
//         $data['timestamp'] = new MongoDB\BSON\UTCDateTime();
        
//         try {
//             $sensorDataCollection->insertOne($data);
//             echo "-> Da luu vao MongoDB.\n";
//         } catch (Exception $e) {
//             echo "-> Loi MongoDB: " . $e->getMessage() . "\n";
//         }
//     }
// }
function procMsg($topic, $msg){
    global $sensorDataCollection, $last_temp, $last_fan_pwm, $last_led_state; 
    
    $data = json_decode($msg, true);
    
    if ($data) {
        // 1. Kiểm tra xem dữ liệu có thay đổi đáng kể không?
        // Ví dụ: Nhiệt độ lệch > 0.3 độ, hoặc trạng thái Fan/Led thay đổi
        $is_changed = false;

        if (abs($data['temp'] - $last_temp) > 0.3) $is_changed = true;
        if ($data['fan_pwm'] != $last_fan_pwm) $is_changed = true;
        if ($data['led_state'] != $last_led_state) $is_changed = true;
        if ($data['pir'] == 1) $is_changed = true; // Có người là phải lưu ngay

        // 2. Chỉ lưu vào DB nếu có thay đổi
        if ($is_changed) {
            $data['timestamp'] = new MongoDB\BSON\UTCDateTime();
            try {
                $sensorDataCollection->insertOne($data);
                echo "-> [CHANGE] Da luu DB (Temp: {$data['temp']})\n";
                
                // Cập nhật lại giá trị cũ
                $last_temp = $data['temp'];
                $last_fan_pwm = $data['fan_pwm'];
                $last_led_state = $data['led_state'];
            } catch (Exception $e) {
                echo "-> Loi DB: " . $e->getMessage() . "\n";
            }
        } else {
            echo "-> [SKIP] Du lieu giong cu, khong luu.\n";
        }
    }
}
?>