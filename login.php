<?php
session_start();
require 'db.php'; // Kết nối MongoDB

// Xử lý logic ĐĂNG NHẬP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['btn_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $user = $usersCollection->findOne(['username' => $username]);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = (string)$user['_id'];
        $_SESSION['fullname'] = $user['fullname'];
        header("Location: index.php"); // Đổi thành trang đích sau khi đăng nhập thành công
        exit;
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống IoT - Đăng nhập</title>
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
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .card-header {
            background-color: #fff;
            border-bottom: none;
            padding-top: 30px;
            text-align: center;
        }
        .btn-custom {
            background: #764ba2;
            border: none;
            padding: 12px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            background: #5a367f;
            transform: translateY(-2px);
        }
        /* Style cho link Đăng ký và Đổi mật khẩu */
        .toggle-link, .register-link {
            cursor: pointer;
            text-decoration: none;
            color: #764ba2;
            font-weight: 600;
            transition: 0.2s;
        }
        .toggle-link:hover, .register-link:hover {
            color: #5a367f;
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5 col-lg-4">
                <div class="card login-card p-4">
                    
                    <div class="card-header">
                        <div class="mb-3 text-primary">
                            <i class="fas fa-home fa-4x" style="color: #764ba2;"></i>
                        </div>
                        <h3 class="fw-bold text-dark" id="dynamic-title">Smarthome IoT</h3>
                        <p class="text-muted" id="dynamic-subtitle">Đăng nhập hệ thống</p>
                    </div>

                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div><?php echo $error; ?></div>
                            </div>
                        <?php endif; ?>

                        <div id="login-section">
                            <form method="POST">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" name="username" placeholder="Tên đăng nhập" required>
                                    <label><i class="fas fa-user me-1"></i> Tên đăng nhập</label>
                                </div>

                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" name="password" placeholder="Mật khẩu" required>
                                    <label><i class="fas fa-lock me-1"></i> Mật khẩu</label>
                                </div>

                                <div class="d-grid gap-2 mb-3">
                                    <button type="submit" name="btn_login" class="btn btn-primary btn-custom text-white fw-bold rounded-pill">
                                        ĐĂNG NHẬP
                                    </button>
                                </div>
                            </form>
                            
                            <div class="text-center mt-3">
                                <p class="mb-2">
                                    <span class="text-muted">Chưa có tài khoản? </span>
                                    <a href="register.php" class="register-link">Đăng ký ngay</a>
                                </p>
                                
                                <p class="mb-0">
                                    <span class="text-muted">Quên mật khẩu? </span>
                                    <span class="toggle-link" onclick="toggleView('change')">Đổi mật khẩu</span>
                                </p>
                            </div>
                        </div>

                        <div id="change-pass-section" class="d-none">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="cp_username" placeholder="Tên tài khoản">
                                <label><i class="fas fa-user-tag me-1"></i> Tài khoản cần đổi</label>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="cp_old_pass" placeholder="Mật khẩu cũ">
                                <label><i class="fas fa-key me-1"></i> Mật khẩu cũ</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="password" class="form-control" id="cp_new_pass" placeholder="Mật khẩu mới">
                                <label><i class="fas fa-lock-open me-1"></i> Mật khẩu mới</label>
                            </div>

                            <div class="d-grid gap-2 mb-3">
                                <button type="button" onclick="submitChangePass()" class="btn btn-primary btn-custom text-white fw-bold rounded-pill">
                                    LƯU MẬT KHẨU
                                </button>
                            </div>

                            <div class="text-center">
                                <span class="toggle-link" onclick="toggleView('login')">
                                    <i class="fas fa-arrow-left me-1"></i> Quay lại Đăng nhập
                                </span>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Hàm chuyển đổi giao diện (Giữ nguyên)
        function toggleView(view) {
            const loginSection = document.getElementById('login-section');
            const changeSection = document.getElementById('change-pass-section');
            const title = document.getElementById('dynamic-title');

            if (view === 'change') {
                loginSection.classList.add('d-none');
                changeSection.classList.remove('d-none');
                title.innerText = "Đổi Mật Khẩu";
            } else {
                changeSection.classList.add('d-none');
                loginSection.classList.remove('d-none');
                title.innerText = "Smarthome IoT";
            }
        }

        // Hàm xử lý đổi mật khẩu (Giữ nguyên - gọi API update_password.php)
        function submitChangePass() {
            const user = document.getElementById('cp_username').value;
            const oldPass = document.getElementById('cp_old_pass').value;
            const newPass = document.getElementById('cp_new_pass').value;

            if (!user || !oldPass || !newPass) {
                alert("Vui lòng nhập đầy đủ thông tin!");
                return;
            }

            const formData = new FormData();
            formData.append('username', user);
            formData.append('old_pass', oldPass);
            formData.append('new_pass', newPass);

            fetch('update_password.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                alert(data);
                if (data.includes("thành công")) {
                    toggleView('login');
                    document.getElementById('cp_old_pass').value = '';
                    document.getElementById('cp_new_pass').value = '';
                }
            })
            .catch(error => { console.error('Lỗi:', error); });
        }
    </script>
</body>
</html>