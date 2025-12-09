<?php
session_start();
if (!isset($_SESSION['user_id'])) header("Location: login.php");
require 'db.php';

// Lấy 20 bản ghi dữ liệu cảm biến mới nhất
$sensorData = $sensorDataCollection->find([], [
    'limit' => 20, 
    'sort' => ['timestamp' => -1]
]);

// Lấy 20 bản ghi thao tác người dùng mới nhất
$userLogs = $actionLogCollection->find([], [
    'limit' => 20, 
    'sort' => ['timestamp' => -1]
]);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <title>Báo Cáo Hoạt Động</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Báo Cáo Hoạt Động</h1>
            <a href="index.php" class="bg-blue-500 text-white px-4 py-2 rounded">Quay lại Điều khiển</a>
        </div>

        <div class="bg-white rounded-lg shadow mb-8 overflow-hidden">
            <div class="bg-green-600 p-4 text-white font-bold">📡 Lịch sử Cảm biến & Trạng thái Thiết bị</div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 border">Thời gian</th>
                        <th class="p-3 border">Nhiệt độ</th>
                        <th class="p-3 border">Độ ẩm</th>
                        <th class="p-3 border">PIR (Người)</th>
                        <th class="p-3 border">Quạt (PWM)</th>
                        <th class="p-3 border">LED</th>
                        <th class="p-3 border">Chế độ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sensorData as $doc): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?= $doc['timestamp']->toDateTime()->format('H:i:s d/m/Y') ?></td>
                        <td class="p-3"><?= $doc['temp'] ?> °C</td>
                        <td class="p-3"><?= $doc['hum'] ?> %</td>
                        <td class="p-3"><?= isset($doc['pir']) && $doc['pir'] == 1 ? '<span class="text-red-500">Có người</span>' : 'Không' ?></td>
                        <td class="p-3"><?= $doc['fan_pwm'] ?>%</td>
                        <td class="p-3"><?= $doc['led_state'] == 1 ? 'BẬT' : 'TẮT' ?></td>
                        <td class="p-3">
                            <span class="text-xs bg-gray-200 px-2 py-1 rounded">Quạt: <?= $doc['fan_mode'] ?? '?' ?></span>
                            <span class="text-xs bg-gray-200 px-2 py-1 rounded">LED: <?= $doc['led_mode'] ?? '?' ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-blue-600 p-4 text-white font-bold">👤 Lịch sử Thao tác Người dùng</div>
            <table class="w-full text-left border-collapse">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-3 border">Thời gian</th>
                        <th class="p-3 border">Người dùng</th>
                        <th class="p-3 border">Thiết bị</th>
                        <th class="p-3 border">Lệnh</th>
                        <th class="p-3 border">Mã lệnh gửi đi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userLogs as $log): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3"><?= $log['timestamp']->toDateTime()->format('H:i:s d/m/Y') ?></td>
                        <td class="p-3 font-bold text-blue-600"><?= $log['username'] ?></td>
                        <td class="p-3 uppercase"><?= $log['device'] ?></td>
                        <td class="p-3">
                            <span class="px-2 py-1 rounded <?= $log['command'] == 'OFF' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' ?>">
                                <?= $log['command'] ?>
                            </span>
                        </td>
                        <td class="p-3 font-mono text-gray-500"><?= $log['payload'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>