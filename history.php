<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");
require 'db.php';

// --- XỬ LÝ TÌM KIẾM ---

// 1. Khởi tạo biến bộ lọc từ URL (Method GET)
$start_date = $_GET['start'] ?? '';
$end_date   = $_GET['end'] ?? '';
$pir_filter = $_GET['pir'] ?? '';      // ''=All, '1'=Có, '0'=Không
$mode_filter= $_GET['mode'] ?? '';     // ''=All, 'Auto', 'Manual'
$fan_filter = $_GET['fan_level'] ?? ''; // ''=All, 0, 30, 60, 90
$temp_min   = $_GET['temp_min'] ?? '';
$temp_max   = $_GET['temp_max'] ?? '';
$hum_min    = $_GET['hum_min'] ?? '';
$hum_max    = $_GET['hum_max'] ?? '';

// 2. Xây dựng mảng Query cho MongoDB
$filterSensor = []; // Bộ lọc cho bảng Cảm biến
$filterLogs   = []; // Bộ lọc cho bảng Lịch sử thao tác (Chỉ lọc theo thời gian)

// A. Lọc theo Thời Gian (Áp dụng cho cả 2 bảng)
if (!empty($start_date) && !empty($end_date)) {
    // Chuyển đổi string ngày tháng sang MongoDB UTCDateTime (mili-giây)
    $mongoStart = new MongoDB\BSON\UTCDateTime(strtotime($start_date) * 1000);
    $mongoEnd   = new MongoDB\BSON\UTCDateTime(strtotime($end_date) * 1000);
    
    $timeQuery = ['$gte' => $mongoStart, '$lte' => $mongoEnd];
    
    $filterSensor['timestamp'] = $timeQuery;
    $filterLogs['timestamp']   = $timeQuery;
}

// B. Các bộ lọc riêng cho Sensor
// - PIR (Có người hay không)
if ($pir_filter !== '') {
    $filterSensor['pir'] = (int)$pir_filter;
}

// - Chế độ (Auto/Manual)
if ($mode_filter !== '') {
    $filterSensor['fan_mode'] = $mode_filter;
}

// - Mức quạt
if ($fan_filter !== '') {
    $filterSensor['fan_pwm'] = (int)$fan_filter;
}

// - Nhiệt độ (Khoảng Min-Max)
if ($temp_min !== '' || $temp_max !== '') {
    $filterSensor['temp'] = [];
    if ($temp_min !== '') $filterSensor['temp']['$gte'] = (float)$temp_min;
    if ($temp_max !== '') $filterSensor['temp']['$lte'] = (float)$temp_max;
}

// - Độ ẩm (Khoảng Min-Max)
if ($hum_min !== '' || $hum_max !== '') {
    $filterSensor['hum'] = [];
    if ($hum_min !== '') $filterSensor['hum']['$gte'] = (float)$hum_min;
    if ($hum_max !== '') $filterSensor['hum']['$lte'] = (float)$hum_max;
}

// 3. Thực hiện truy vấn (Tăng limit lên 100 để xem nhiều kết quả tìm kiếm hơn)
$sensorData = $sensorDataCollection->find($filterSensor, [
    'limit' => 100, 
    'sort' => ['timestamp' => -1]
]);

$userLogs = $actionLogCollection->find($filterLogs, [
    'limit' => 100, 
    'sort' => ['timestamp' => -1]
]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử & Tìm Kiếm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800"><i class="fas fa-search"></i> Tra Cứu Lịch Sử</h1>
            <a href="index.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg font-bold transition shadow">
                <i class="fas fa-arrow-left"></i> Quay lại Điều khiển
            </a>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 mb-8 border border-gray-200">
            <h2 class="text-xl font-bold text-gray-700 mb-4 border-b pb-2">🔍 Bộ Lọc Tìm Kiếm</h2>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                
                <div class="col-span-1 md:col-span-2 grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Từ ngày giờ:</label>
                        <input type="datetime-local" name="start" value="<?= $start_date ?>" class="w-full p-2 border rounded focus:ring focus:ring-blue-200">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Đến ngày giờ:</label>
                        <input type="datetime-local" name="end" value="<?= $end_date ?>" class="w-full p-2 border rounded focus:ring focus:ring-blue-200">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Phát hiện người (PIR):</label>
                    <select name="pir" class="w-full p-2 border rounded focus:ring focus:ring-blue-200">
                        <option value="">-- Tất cả --</option>
                        <option value="1" <?= $pir_filter === '1' ? 'selected' : '' ?>>Có người ⚠️</option>
                        <option value="0" <?= $pir_filter === '0' ? 'selected' : '' ?>>Không có</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Mức Quạt:</label>
                    <select name="fan_level" class="w-full p-2 border rounded focus:ring focus:ring-blue-200">
                        <option value="">-- Tất cả --</option>
                        <option value="0"  <?= $fan_filter === '0' ? 'selected' : '' ?>>Tắt (0%)</option>
                        <option value="30" <?= $fan_filter === '30' ? 'selected' : '' ?>>Mức 1 (30%)</option>
                        <option value="60" <?= $fan_filter === '60' ? 'selected' : '' ?>>Mức 2 (60%)</option>
                        <option value="90" <?= $fan_filter === '90' ? 'selected' : '' ?>>Mức 3 (90%)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Chế độ:</label>
                    <select name="mode" class="w-full p-2 border rounded focus:ring focus:ring-blue-200">
                        <option value="">-- Tất cả --</option>
                        <option value="Auto" <?= $mode_filter === 'Auto' ? 'selected' : '' ?>>Tự động (Auto)</option>
                        <option value="Manual" <?= $mode_filter === 'Manual' ? 'selected' : '' ?>>Thủ công (Manual)</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Nhiệt độ Min:</label>
                        <input type="number" step="0.1" name="temp_min" value="<?= $temp_min ?>" placeholder="Ví dụ: 25" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Max:</label>
                        <input type="number" step="0.1" name="temp_max" value="<?= $temp_max ?>" placeholder="Ví dụ: 35" class="w-full p-2 border rounded">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Độ ẩm Min:</label>
                        <input type="number" step="0.1" name="hum_min" value="<?= $hum_min ?>" placeholder="Ví dụ: 50" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Max:</label>
                        <input type="number" step="0.1" name="hum_max" value="<?= $hum_max ?>" placeholder="Ví dụ: 90" class="w-full p-2 border rounded">
                    </div>
                </div>

                <div class="col-span-1 md:col-span-4 flex justify-end gap-3 mt-2">
                    <a href="history.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded font-bold transition">
                        Xóa lọc
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-2 rounded font-bold transition shadow">
                        <i class="fas fa-search"></i> TÌM KIẾM
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow mb-8 overflow-hidden">
            <div class="bg-green-600 p-4 text-white font-bold flex justify-between items-center">
                <span>📡 Dữ liệu Cảm biến (Tìm thấy: <?= $sensorDataCollection->countDocuments($filterSensor) ?> bản ghi)</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 border-b-2 border-gray-200">
                        <tr>
                            <th class="p-3 border">Thời gian</th>
                            <th class="p-3 border">Nhiệt độ</th>
                            <th class="p-3 border">Độ ẩm</th>
                            <th class="p-3 border">PIR (Người)</th>
                            <th class="p-3 border">Quạt (Mức)</th>
                            <th class="p-3 border">LED</th>
                            <th class="p-3 border">Chế độ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 0;
                        foreach ($sensorData as $doc): 
                            $count++;
                        ?>
                        <tr class="border-b hover:bg-green-50 transition">
                            <td class="p-3 text-gray-600"><?= $doc['timestamp']->toDateTime()->format('H:i:s d/m/Y') ?></td>
                            <td class="p-3 font-bold text-red-600"><?= $doc['temp'] ?> °C</td>
                            <td class="p-3 font-bold text-blue-600"><?= $doc['hum'] ?> %</td>
                            <td class="p-3">
                                <?= (isset($doc['pir']) && $doc['pir'] == 1) 
                                    ? '<span class="bg-red-100 text-red-700 px-2 py-1 rounded text-xs font-bold">⚠️ CÓ NGƯỜI</span>' 
                                    : '<span class="text-gray-400">Không</span>' ?>
                            </td>
                            <td class="p-3">
                                <?php if($doc['fan_pwm'] > 0): ?>
                                    <span class="text-green-600 font-bold"><?= $doc['fan_pwm'] ?>%</span>
                                <?php else: ?>
                                    <span class="text-gray-400">Tắt</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <?= ($doc['led_state'] == 1) 
                                    ? '<i class="fas fa-lightbulb text-yellow-500"></i> Bật' 
                                    : '<span class="text-gray-400">Tắt</span>' ?>
                            </td>
                            <td class="p-3">
                                <span class="text-xs px-2 py-1 rounded font-bold <?= ($doc['fan_mode'] ?? '') == 'Auto' ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-700' ?>">
                                    <?= $doc['fan_mode'] ?? '?' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if($count == 0): ?>
                        <tr>
                            <td colspan="7" class="p-6 text-center text-gray-500 italic">Không tìm thấy dữ liệu nào phù hợp với bộ lọc.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 p-4 text-white font-bold">
                👤 Lịch sử Thao tác Người dùng (Lọc theo thời gian)
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 border-b-2 border-gray-200">
                        <tr>
                            <th class="p-3 border">Thời gian</th>
                            <th class="p-3 border">Người dùng</th>
                            <th class="p-3 border">Thiết bị</th>
                            <th class="p-3 border">Lệnh</th>
                            <th class="p-3 border">Chi tiết lệnh</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userLogs as $log): ?>
                        <tr class="border-b hover:bg-blue-50 transition">
                            <td class="p-3 text-gray-600"><?= $log['timestamp']->toDateTime()->format('H:i:s d/m/Y') ?></td>
                            <td class="p-3 font-bold text-blue-700"><?= $log['username'] ?></td>
                            <td class="p-3 uppercase font-semibold"><?= $log['device'] ?></td>
                            <td class="p-3">
                                <span class="px-2 py-1 rounded text-xs font-bold <?= strpos($log['command'], 'TẮT') !== false ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                                    <?= $log['command'] ?>
                                </span>
                            </td>
                            <td class="p-3 font-mono text-xs text-gray-500 bg-gray-50 rounded"><?= $log['payload'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>