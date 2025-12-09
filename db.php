<?php
require 'vendor/autoload.php'; // Load thư viện MongoDB

// --- THÔNG TIN CẤU HÌNH ATLAS ---
// 1. Thay username bạn vừa tạo ở bước 1 vào đây
$username = "admin_iot"; 

// 2. Thay mật khẩu bạn vừa tạo vào đây
// Lưu ý: Nếu mật khẩu có ký tự đặc biệt, hãy giữ nguyên, hàm urlencode bên dưới sẽ xử lý
$password = "matkhau123"; 

// 3. Thay phần đuôi Cluster của bạn vào đây (Lấy từ chuỗi connection string của Atlas)
// Ví dụ chuỗi gốc là: mongodb+srv://user:pass@cluster0.abcde.mongodb.net/...
// Thì bạn chỉ copy đoạn: cluster0.abcde.mongodb.net
$cluster_address = "cluster0.rpatysx.mongodb.net"; 

// 4. Tên Database muốn lưu
$db_name = "iot_project"; 
// --------------------------------

try {
    // Mã hóa mật khẩu để tránh lỗi ký tự đặc biệt
    $encoded_password = urlencode($password);

    // Tạo chuỗi kết nối chuẩn
    $uri = "mongodb+srv://admin_iot:matkhau123@cluster0.rpatysx.mongodb.net/?appName=Cluster0";

    // Cấu hình Client (Tắt SSL verify để chạy mượt trên XAMPP/Localhost)
    $client = new MongoDB\Client($uri, [], [
        'driver' => [
            'tls' => true,
            'tlsAllowInvalidCertificates' => true // Quan trọng: Cho phép chứng chỉ không hợp lệ (để tránh lỗi trên XAMPP)
        ]
    ]);
    
    // Chọn Database và Collection
    $database = $client->$db_name; 
    $usersCollection = $database->users;

    // (Tùy chọn) Kiểm tra kết nối bằng cách thử ping
    // $client->selectDatabase('admin')->command(['ping' => 1]);
    // echo "Kết nối MongoDB Atlas thành công!";
    $sensorDataCollection = $database->sensor_data;
} catch (Exception $e) {
    die("Lỗi kết nối MongoDB Atlas: " . $e->getMessage());
}
?>