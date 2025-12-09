<?php
session_start(); // 1. BẮT BUỘC CÓ DÒNG NÀY ĐẦU TIÊN

// Kiểm tra nếu chưa đăng nhập thì đẩy về trang login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

require 'db.php'; // Gọi file kết nối MongoDB

// LẤY DỮ LIỆU CẢM BIẾN MỚI NHẤT
$latestData = $sensorDataCollection->findOne([], [
    'sort' => ['timestamp' => -1]
]);

// Khởi tạo giá trị mặc định
$temp = 0; $hum = 0; $motion = 0;
$led = 0; $fan = 0; $mode = "Unknown";
$created_at = "Chưa có dữ liệu";

if ($latestData) {
    $temp = $latestData['temp'] ?? 0;
    $hum = $latestData['hum'] ?? 0;
    $motion = $latestData['pir'] ?? 0;
    $led = $latestData['led_state'] ?? 0;
    
    $fan_pwm = $latestData['fan_pwm'] ?? 0;
    $fan = ($fan_pwm > 0) ? 1 : 0;
    $mode = $latestData['fan_mode'] ?? "AUTO"; 
    
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
        .card { border: none; shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 15px; }
        .sensor-val { font-size: 2.5rem; font-weight: bold; }
        .status-badge { font-size: 1rem; padding: 10px 20px; border-radius: 20px; }
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
                <a href="history.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-history"></i> Lịch Sử
                </a>
                
                <a href="logout.php" class="btn btn-danger" onclick="return confirm('Bạn có chắc muốn đăng xuất?');">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
        
        <p class="text-center text-muted">Cập nhật lần cuối: <?php echo $created_at; ?></p>

        <div class="row mb-4">
            <div class="col-12">
                <?php if ($motion == 1): ?>
                    <div class="alert alert-danger text-center fw-bold" role="alert">
                        <i class="fas fa-exclamation-triangle fa-2x"></i><br>
                        CẢNH BÁO: PHÁT HIỆN CÓ NGƯỜI!
                    </div>
                <?php else: ?>
                    <div class="alert alert-success text-center" role="alert">
                        <i class="fas fa-shield-alt fa-2x"></i><br>
                        An toàn: Không phát hiện chuyển động.
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
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <i class="fas fa-cogs"></i> Trạng thái thiết bị
                    </div>
                    <div class="card-body">
                        <div class="row text-center align-items-center">
                            
                            <div class="col-md-4 mb-3">
                                <h5>ĐÈN LED</h5>
                                <?php if ($led == 1): ?>
                                    <span class="badge bg-warning text-dark status-badge">
                                        <i class="fas fa-lightbulb"></i> ĐANG BẬT
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary status-badge">ĐANG TẮT</span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <h5>QUẠT LÀM MÁT</h5>
                                <?php if ($fan == 1): ?>
                                    <span class="badge bg-success status-badge">
                                        <i class="fas fa-fan fa-spin"></i> ĐANG BẬT
                                    </span>
                                    <p class="mt-2 text-muted small">Tốc độ: <?php echo $fan_pwm; ?>%</p>
                                <?php else: ?>
                                    <span class="badge bg-secondary status-badge">ĐANG TẮT</span>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-4 mb-3">
                                <h5>CHẾ ĐỘ HỆ THỐNG</h5>
                                <div class="p-2 border rounded bg-light">
                                    <strong><?php echo strtoupper($mode); ?></strong>
                                </div>
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