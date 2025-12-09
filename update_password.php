<?php
// Nạp thư viện MongoDB (bắt buộc phải chạy composer require mongodb/mongodb trước)
require 'vendor/autoload.php'; 

// Kết nối MongoDB
try {
    // Thay đổi đường dẫn nếu bạn dùng server online
    $client = new MongoDB\Client("mongodb://localhost:27017");
    
    // Chọn Database và Collection
    $collection = $client->test_db->users; // Database: test_db, Collection: users

    // Lấy dữ liệu từ AJAX gửi sang
    $username = $_POST['username'] ?? '';
    $old_pass = $_POST['old_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';

    if (!$username || !$old_pass || !$new_pass) {
        echo "Lỗi: Thiếu thông tin!";
        exit;
    }

    // 1. Tìm người dùng trong DB
    $user = $collection->findOne(['username' => $username]);

    if (!$user) {
        echo "Lỗi: Tài khoản không tồn tại!";
        exit;
    }

    // 2. Kiểm tra mật khẩu cũ
    // Lưu ý: Nếu trong DB bạn lưu mật khẩu dạng Plain Text (không mã hóa) thì dùng so sánh ==
    // Nếu dùng hash (password_hash) thì dùng password_verify($old_pass, $user['password'])
    
    // Giả sử bạn lưu dạng Text thường (cho dễ hiểu):
    $current_password_in_db = $user['password'];

    if ($old_pass != $current_password_in_db) {
        echo "Lỗi: Mật khẩu cũ không chính xác!";
        exit;
    }

    // 3. Cập nhật mật khẩu mới
    $updateResult = $collection->updateOne(
        ['username' => $username],
        ['$set' => ['password' => $new_pass]]
    );

    if ($updateResult->getModifiedCount() > 0) {
        echo "Đổi mật khẩu thành công!";
    } else {
        echo "Mật khẩu mới giống mật khẩu cũ, không có gì thay đổi!";
    }

} catch (Exception $e) {
    echo "Lỗi Server: " . $e->getMessage();
}
?>