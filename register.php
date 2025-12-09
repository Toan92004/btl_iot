<?php
session_start();
require 'db.php'; // Gọi file kết nối MongoDB

$message = "";
$message_type = ""; // 'success' hoặc 'danger'

// XỬ LÝ KHI NGƯỜI DÙNG ẤN NÚT ĐĂNG KÝ
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Kiểm tra xác nhận mật khẩu
    if ($password !== $confirm_password) {
        $message = "Mật khẩu xác nhận không khớp!";
        $message_type = "danger";
    } else {
        // 2. Kiểm tra tài khoản đã tồn tại chưa
        $existingUser = $usersCollection->findOne(['username' => $username]);

        if ($existingUser) {
            $message = "Tên đăng nhập này đã được sử dụng!";
            $message_type = "danger";
        } else {
            // 3. Mã hóa mật khẩu (Bắt buộc bảo mật)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 4. Lưu vào MongoDB
            $newUser = [
                'fullname' => $fullname,
                'username' => $username,
                'password' => $hashed_password,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $insertResult = $usersCollection->insertOne($newUser);

            if ($insertResult->getInsertedCount() > 0) {
                $message = "Đăng ký thành công! Đang chuyển hướng...";
                $message_type = "success";
                // Tự động chuyển về trang đăng nhập sau 2 giây
                echo "<script>setTimeout(function(){ window.location.href = 'index.php'; }, 2000);</script>";
            } else {
                $message = "Lỗi hệ thống, vui lòng thử lại!";
                $message_type = "danger";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký tài khoản</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
        }
        .card-header {
            background-color: #fff;
            border-bottom: none;
            padding-top: 20px;
            text-align: center;
        }
        .btn-register {
            background: #28a745;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-register:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .link-login {
            color: #764ba2;
            text-decoration: none;
            font-weight: bold;
        }
        .link-login:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card register-card p-4">
                    
                    <div class="card-header">
                        <h3 class="fw-bold text-dark">Đăng Ký Tài Khoản</h3>
                        <p class="text-muted">Tạo tài khoản để quản lý IoT</p>
                    </div>

                    <div class="card-body">
                        <?php if(!empty($message)): ?>
                            <div class="alert alert-<?php echo $message_type; ?> d-flex align-items-center" role="alert">
                                <?php if($message_type == 'success'): ?>
                                    <i class="fas fa-check-circle me-2"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                <?php endif; ?>
                                <div><?php echo $message; ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="fullname" placeholder="Họ và tên" required>
                                <label><i class="fas fa-id-card me-1"></i> Họ và tên</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="username" placeholder="Tên đăng nhập" required>
                                <label><i class="fas fa-user me-1"></i> Tên đăng nhập</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Mật khẩu" required>
                                <label><i class="fas fa-lock me-1"></i> Mật khẩu</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                                <label><i class="fas fa-check-double me-1"></i> Nhập lại mật khẩu</label>
                            </div>

                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-primary btn-register text-white fw-bold rounded-pill">
                                    ĐĂNG KÝ NGAY
                                </button>
                            </div>

                            <div class="text-center mt-3">
                                <span class="text-muted">Đã có tài khoản? </span>
                                <a href="index.php" class="link-login">Đăng nhập tại đây</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>