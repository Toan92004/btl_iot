<?php
session_start(); // Bắt đầu session để có thể truy cập nó
session_unset(); // Xóa tất cả các biến session
session_destroy(); // Hủy session hoàn toàn

// Chuyển hướng về trang đăng nhập
header("Location: login.php");
exit;
?>