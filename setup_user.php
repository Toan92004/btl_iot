<?php
require 'db.php';

// Tạo tài khoản mẫu
$usersCollection->insertOne([
    'username' => 'admin',
    'password' => password_hash('123456', PASSWORD_DEFAULT), // Mật khẩu được mã hóa
    'fullname' => 'Quản trị viên'
]);
echo "Đã tạo user admin thành công!";
?>