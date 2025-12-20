<?php
session_start(); 

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php'; 
require 'phpMQTT.php'; 
use Bluerhinos\phpMQTT; 

// --- XỬ LÝ KHI NGƯỜI DÙNG BẤM NÚT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_action'])) {
    
    $command_code = $_POST['btn_action']; 
    $device_name  = $_POST['device_name']; 
    $action_text  = $_POST['action_text']; 

    $server   = '18597cd464464ab4b3c1c5d4bf9b070e.s1.eu.hivemq.cloud';
    $port     = 8883;
    $username = 'dodanhtoan'; 
    $password = 'Toan0809';
    $clientId = 'Web_Control_' . uniqid();
    $cafile   = '/etc/ssl/certs/ca-certificates.crt'; 

    try {
        $mqtt = new phpMQTT($server, $port, $clientId, $cafile);
        
        if ($mqtt->connect(true, null, $username, $password)) {
            $mqtt->publish("esp8266/client", $command_code, 0);
            $mqtt->close();

            $actionLogCollection->insertOne([
                'username'  => $_SESSION['fullname'],
                'device'    => $device_name,
                'command'   => $action_text,
                'payload'   => $command_code,
                'timestamp' => new MongoDB\BSON\UTCDateTime()
            ]);

            $msg_success = "Đã gửi lệnh: $action_text cho $device_name";
        } else {
            $msg_error = "Không thể kết nối tới MQTT Broker!";
        }
    } catch (Exception $e) {
        $msg_error = "Lỗi: " . $e->getMessage();
    }
}

// --- LẤY DỮ LIỆU MỚI NHẤT ---
$latestData = $sensorDataCollection->findOne([], ['sort' => ['timestamp' => -1]]);

// Giá trị mặc định
$temp = 0; $hum = 0; $motion = 0; $led = 0; $fan = 0; $fan_pwm = 0; 
$fan_mode = "Auto"; $led_mode = "Auto"; // Tách riêng 2 biến chế độ
$created_at = "Chưa có dữ liệu";

if ($latestData) {
    $temp = $latestData['temp'] ?? 0;
    $hum  = $latestData['hum'] ?? 0;
    $motion = $latestData['pir'] ?? 0;
    $led  = $latestData['led_state'] ?? 0;
    $fan_pwm = $latestData['fan_pwm'] ?? 0;
    $fan  = ($fan_pwm > 0) ? 1 : 0;
    
    // Tách riêng chế độ
    $fan_mode = $latestData['fan_mode'] ?? "Auto"; 
    $led_mode = $latestData['led_mode'] ?? "Auto"; 
    
    if (isset($latestData['timestamp'])) {
        $created_at = $latestData['timestamp']->toDateTime()->format('H:i:s d/m/Y');
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ Thống Giám Sát IoT</title>
    <meta http-equiv="refresh" content="5"> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body { background-color: #f4f6f9; }
        .card { border: none; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 15px; }
        .sensor-val { font-size: 2.5rem; font-weight: bold; }
        .status-badge { font-size: 0.9rem; padding: 5px 15px; border-radius: 20px; }
        .mode-badge { font-size: 0.8rem; padding: 5px 10px; border-radius: 5px; font-weight: bold; text-transform: uppercase; }
        .mode-auto { background-color: #6f42c1; color: white; } /* Màu tím cho Auto */
        .mode-manual { background-color: #6c757d; color: white; } /* Màu xám cho Manual */
        .btn-control { width: 100%; margin-bottom: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 rounded shadow-sm">
            <div>
                <h2 class="text-primary fw-bold m-0">BẢNG ĐIỀU KHIỂN IOT</h2>
                <small class="text-muted">Xin chào, <strong><?php echo $_SESSION['fullname'] ?? 'Admin'; ?></strong></small>
            </div>
            <div>
                <a href="history.php" class="btn btn-outline-primary me-2"><i class="fas fa-history"></i> Lịch Sử</a>
                <a href="logout.php" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn đăng xuất?');"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            </div>
        </div>
        
        <?php if(isset($msg_success)): ?><div class="alert alert-success"><?php echo $msg_success; ?></div><?php endif; ?>
        <?php if(isset($msg_error)): ?><div class="alert alert-danger"><?php echo $msg_error; ?></div><?php endif; ?>

        <p class="text-center text-muted">Cập nhật lần cuối: <?php echo $created_at; ?></p>

        <div class="row mb-4">
            <div class="col-12">
                <?php if ($motion == 1): ?>
                    <div class="alert alert-danger text-center fw-bold" role="alert">
                        <i class="fas fa-exclamation-triangle fa-2x"></i><br> CẢNH BÁO: PHÁT HIỆN CÓ NGƯỜI!
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center" role="alert">
                        <i class="fas fa-shield-alt fa-2x"></i><br> An toàn: Không phát hiện chuyển động.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card p-4 text-center text-danger">
                    <div class="card-body">
                        <i class="fas fa-thermometer-half fa-3x mb-3"></i>
                        <h5 class="card-title">Nhiệt độ</h5>
                        <p class="sensor-val"><?php echo $temp; ?>°C</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card p-4 text-center text-primary">
                    <div class="card-body">
                        <i class="fas fa-tint fa-3x mb-3"></i>
                        <h5 class="card-title">Độ ẩm</h5>
                        <p class="sensor-val"><?php echo $hum; ?>%</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-lightbulb"></i> ĐIỀU KHIỂN ĐÈN LED</span>
                        <span class="mode-badge <?php echo ($led_mode == 'Auto') ? 'mode-auto' : 'mode-manual'; ?>">
                            <?php echo $led_mode; ?>
                        </span>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if ($led == 1): ?>
                                <span class="badge bg-warning text-dark status-badge"><i class="fas fa-lightbulb"></i> ĐANG BẬT</span>
                            <?php else: ?>
                                <span class="badge bg-secondary status-badge">ĐANG TẮT</span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-2 justify-content-center">
                            <form method="POST" style="width: 45%;">
                                <input type="hidden" name="device_name" value="LED">
                                <input type="hidden" name="action_text" value="BẬT">
                                <button type="submit" name="btn_action" value="O" class="btn btn-warning btn-control">BẬT</button>
                            </form>
                            <form method="POST" style="width: 45%;">
                                <input type="hidden" name="device_name" value="LED">
                                <input type="hidden" name="action_text" value="TẮT">
                                <button type="submit" name="btn_action" value="f" class="btn btn-secondary btn-control">TẮT</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white fw-bold d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-fan"></i> ĐIỀU KHIỂN QUẠT</span>
                        <span class="mode-badge <?php echo ($fan_mode == 'Auto') ? 'mode-auto' : 'mode-manual'; ?>">
                            <?php echo $fan_mode; ?>
                        </span>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if ($fan == 1): ?>
                                <span class="badge bg-success status-badge"><i class="fas fa-fan fa-spin"></i> ĐANG BẬT (<?php echo $fan_pwm; ?>%)</span>
                            <?php else: ?>
                                <span class="badge bg-secondary status-badge">ĐANG TẮT</span>
                            <?php endif; ?>
                        </div>

                        <div class="row g-2">
                            <div class="col-3">
                                <form method="POST">
                                    <input type="hidden" name="device_name" value="FAN">
                                    <input type="hidden" name="action_text" value="TẮT">
                                    <button type="submit" name="btn_action" value="F" class="btn btn-outline-danger btn-control">OFF</button>
                                </form>
                            </div>
                            <div class="col-3">
                                <form method="POST">
                                    <input type="hidden" name="device_name" value="FAN">
                                    <input type="hidden" name="action_text" value="MỨC 1">
                                    <button type="submit" name="btn_action" value="3" class="btn btn-outline-success btn-control">LV 1</button>
                                </form>
                            </div>
                            <div class="col-3">
                                <form method="POST">
                                    <input type="hidden" name="device_name" value="FAN">
                                    <input type="hidden" name="action_text" value="MỨC 2">
                                    <button type="submit" name="btn_action" value="6" class="btn btn-outline-success btn-control">LV 2</button>
                                </form>
                            </div>
                            <div class="col-3">
                                <form method="POST">
                                    <input type="hidden" name="device_name" value="FAN">
                                    <input type="hidden" name="action_text" value="MỨC 3">
                                    <button type="submit" name="btn_action" value="9" class="btn btn-outline-success btn-control">LV 3</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>