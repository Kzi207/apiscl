<?php
// login.php - Trang đăng nhập/đăng ký cho người dùng
session_start();

require_once 'db_config.php';

// Xử lý đăng ký
if (isset($_POST['register'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    
    if (empty($email) || empty($password) || empty($name)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        // Kiểm tra email đã tồn tại
        $existingUser = getUserByEmail($email);
        
        if ($existingUser) {
            $error = 'Email đã được đăng ký!';
        } else {
            try {
                // Tạo user mới trong database
                $userId = createUser($email, $password, $name);
                
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                
                header('Location: dashboard.php');
                exit;
            } catch (Exception $e) {
                $error = 'Lỗi tạo tài khoản: ' . $e->getMessage();
            }
        }
    }
}

// Xử lý đăng nhập
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } else {
        // Kiểm tra user trong database
        $user = getUserByEmail($email);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $user['name'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Email hoặc mật khẩu không đúng!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - SoundCloud Downloader</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 2em;
        }
        
        .auth-header p {
            color: #6c757d;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 5px;
            border-radius: 10px;
        }
        
        .tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: transparent;
            color: #6c757d;
            font-weight: 600;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-container {
            display: none;
        }
        
        .form-container.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 600;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .error {
            background: #f8d7da;
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1><i class="fas fa-user-circle"></i> Tài khoản</h1>
            <p>Đăng nhập hoặc đăng ký để quản lý API keys</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab active" onclick="switchTab('login')">Đăng nhập</button>
            <button class="tab" onclick="switchTab('register')">Đăng ký</button>
        </div>
        
        <!-- Form đăng nhập -->
        <div id="loginForm" class="form-container active">
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" required placeholder="email@example.com">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••">
                </div>
                
                <button type="submit" name="login" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
            </form>
        </div>
        
        <!-- Form đăng ký -->
        <div id="registerForm" class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Họ và tên</label>
                    <input type="text" name="name" required placeholder="Nguyễn Văn A">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" required placeholder="email@example.com">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mật khẩu</label>
                    <input type="password" name="password" required placeholder="••••••••" minlength="6">
                </div>
                
                <button type="submit" name="register" class="btn-submit">
                    <i class="fas fa-user-plus"></i> Đăng ký
                </button>
            </form>
        </div>
        
        <div class="back-link">
            <a href="index.php"><i class="fas fa-arrow-left"></i> Quay lại trang chủ</a>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Update tabs
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            event.target.classList.add('active');
            
            // Update forms
            document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));
            document.getElementById(tab + 'Form').classList.add('active');
        }
    </script>
</body>
</html>
