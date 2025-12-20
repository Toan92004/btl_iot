<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");
require 'db.php';

// --- XỬ LÝ TÌM KIẾM ---
$start_date = $_GET['start'] ?? '';
$end_date   = $_GET['end'] ?? '';
$pir_filter = $_GET['pir'] ?? '';     
$fan_mode_filter = $_GET['fan_mode'] ?? ''; // Lọc chế độ Quạt
$led_mode_filter = $_GET['led_mode'] ?? ''; // Lọc chế độ LED
$fan_filter = $_GET['fan_level'] ?? ''; 
$temp_min   = $_GET['temp_min'] ?? '';
$temp_max   = $_GET['temp_max'] ?? '';
$hum_min    = $_GET['hum_min'] ?? '';
$hum_max    = $_GET['hum_max'] ?? '';

// Query
$filterSensor = []; 
$filterLogs   = []; 

if (!empty($start_date) && !empty($end_date)) {
    $mongoStart = new MongoDB\BSON\UTCDateTime(strtotime($start_date) * 1000);
    $mongoEnd   = new MongoDB\BSON\UTCDateTime(strtotime($end_date) * 1000);
    $timeQuery = ['$gte' => $mongoStart, '$lte' => $mongoEnd];
    $filterSensor['timestamp'] = $timeQuery;
    $filterLogs['timestamp']   = $timeQuery;
}

if ($pir_filter !== '') $filterSensor['pir'] = (int)$pir_filter;
if ($fan_filter !== '') $filterSensor['fan_pwm'] = (int)$fan_filter;

// Lọc riêng chế độ Quạt
if ($fan_mode_filter !== '') $filterSensor['fan_mode'] = $fan_mode_filter;

// Lọc riêng chế độ LED
if ($led_mode_filter !== '') $filterSensor['led_mode'] = $led_mode_filter;

if ($temp_min !== '' || $temp_max !== '') {
    $filterSensor['temp'] = [];
    if ($temp_min !== '') $filterSensor['temp']['$gte'] = (float)$temp_min;
    if ($temp_max !== '') $filterSensor['temp']['$lte'] = (float)$temp_max;
}

if ($hum_min !== '' || $hum_max !== '') {
    $filterSensor['hum'] = [];
    if ($hum_min !== '') $filterSensor['hum']['$gte'] = (float)$hum_min;
    if ($hum_max !== '') $filterSensor['hum']['$lte'] = (float)$hum_max;
}

$sensorData = $sensorDataCollection->find($filterSensor, ['limit' => 100, 'sort' => ['timestamp' => -1]]);
$userLogs = $actionLogCollection->find($filterLogs, ['limit' => 100, 'sort' => ['timestamp' => -1]]);
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
                        <input type="datetime-local" name="start" value="<?= $start_date ?>" class="w-full p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-600">Đến ngày giờ:</label>
                        <input type="datetime-local" name="end" value="<?= $end_date ?>" class="w-full p-2 border rounded">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">PIR (Người):</label>
                    <select name="pir" class="w-full p-2 border rounded">
                        <option value="">-- Tất cả --</option>
                        <option value="1" <?= $pir_filter === '1' ? 'selected' : '' ?>>Có người ⚠️</option>
                        <option value="0" <?= $pir_filter === '0' ? 'selected' : '' ?>>Không có</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Mức Quạt:</label>
                    <select name="fan_level" class="w-full p-2 border rounded">
                        <option value="">-- Tất cả --</option>
                        <option value="0"  <?= $fan_filter === '0' ? 'selected' : '' ?>>Tắt (0%)</option>
                        <option value="30" <?= $fan_filter === '30' ? 'selected' : '' ?>>Mức 1 (30%)</option>
                        <option value="60" <?= $fan_filter === '60' ? 'selected' : '' ?>>Mức 2 (60%)</option>
                        <option value="90" <?= $fan_filter === '90' ? 'selected' : '' ?>>Mức 3 (90%)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Chế độ QUẠT:</label>
                    <select name="fan_mode" class="w-full p-2 border rounded">
                        <option value="">-- Tất cả --</option>
                        <option value="Auto" <?= $fan_mode_filter === 'Auto' ? 'selected' : '' ?>>Quạt Auto</option>
                        <option value="Manual" <?= $fan_mode_filter === 'Manual' ? 'selected' : '' ?>>Quạt Manual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-600">Chế độ LED:</label>
                    <select name="led_mode" class="w-full p-2 border rounded">
                        <option value="">-- Tất cả --</option>
                        <option value="Auto" <?= $led_mode_filter === 'Auto' ? 'selected' : '' ?>>LED Auto</option>
                        <option value="Manual" <?= $led_mode_filter === 'Manual' ? 'selected' : '' ?>>LED Manual</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.1" name="temp_min" value="<?= $temp_min ?>" placeholder="Min Temp" class="w-full p-2 border rounded">
                    <input type="number" step="0.1" name="temp_max" value="<?= $temp_max ?>" placeholder="Max Temp" class="w-full p-2 border rounded">
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <input type="number" step="0.1" name="hum_min" value="<?= $hum_min ?>" placeholder="Min Hum" class="w-full p-2 border rounded">
                    <input type="number" step="0.1" name="hum_max" value="<?= $hum_max ?>" placeholder="Max Hum" class="w-full p-2 border rounded">
                </div>

                <div class="col-span-1 md:col-span-4 flex justify-end gap-3 mt-2">
                    <a href="history.php" class="bg-gray-500 text-white px-6 py-2 rounded font-bold">Xóa lọc</a>
                    <button type="submit" class="bg-blue-600 text-white px-8 py-2 rounded font-bold shadow">TÌM KIẾM</button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow mb-8 overflow-hidden">
            <div class="bg-green-600 p-4 text-white font-bold">📡 Dữ liệu Cảm biến (Tìm thấy: <?= $sensorDataCollection->countDocuments($filterSensor) ?> bản ghi)</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 border-b-2">
                        <tr>
                            <th class="p-3 border">Thời gian</th>
                            <th class="p-3 border">Nhiệt độ</th>
                            <th class="p-3 border">Độ ẩm</th>
                            <th class="p-3 border">PIR</th>
                            <th class="p-3 border">Quạt (Level)</th>
                            <th class="p-3 border">LED</th>
                            <th class="p-3 border">Mode Quạt</th>
                            <th class="p-3 border">Mode LED</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sensorData as $doc): ?>
                        <tr class="border-b hover:bg-green-50 transition">
                            <td class="p-3 text-gray-600"><?= $doc['timestamp']->toDateTime()->format('H:i:s d/m/Y') ?></td>
                            <td class="p-3 font-bold text-red-600"><?= $doc['temp'] ?> °C</td>
                            <td class="p-3 font-bold text-blue-600"><?= $doc['hum'] ?> %</td>
                            <td class="p-3"><?= (isset($doc['pir']) && $doc['pir'] == 1) ? '<span class="text-red-600 font-bold">⚠️ CÓ</span>' : 'Không' ?></td>
                            <td class="p-3 text-green-600 font-bold"><?= ($doc['fan_pwm'] > 0) ? $doc['fan_pwm'].'%' : 'Off' ?></td>
                            <td class="p-3"><?= ($doc['led_state'] == 1) ? '<span class="text-yellow-600 font-bold">Bật</span>' : 'Tắt' ?></td>
                            
                            <td class="p-3">
                                <span class="text-xs px-2 py-1 rounded font-bold <?= ($doc['fan_mode'] ?? '') == 'Auto' ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-700' ?>">
                                    <?= $doc['fan_mode'] ?? '?' ?>
                                </span>
                            </td>

                            <td class="p-3">
                                <span class="text-xs px-2 py-1 rounded font-bold <?= ($doc['led_mode'] ?? '') == 'Auto' ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-700' ?>">
                                    <?= $doc['led_mode'] ?? '?' ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 p-4 text-white font-bold">👤 Lịch sử Thao tác</div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-100 border-b-2">
                        <tr>
                            <th class="p-3 border">Thời gian</th>
                            <th class="p-3 border">Người dùng</th>
                            <th class="p-3 border">Thiết bị</th>
                            <th class="p-3 border">Lệnh</th>
                            <th class="p-3 border">Mã</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($userLogs as $log): ?>
                        <tr class="border-b hover:bg-blue-50">
                            <td class="p-3 text-gray-600"><?= $log['timestamp']->toDateTime()->format('H:i:s d/m/Y') ?></td>
                            <td class="p-3 font-bold text-blue-700"><?= $log['username'] ?></td>
                            <td class="p-3 uppercase"><?= $log['device'] ?></td>
                            <td class="p-3"><?= $log['command'] ?></td>
                            <td class="p-3 font-mono text-xs"><?= $log['payload'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>