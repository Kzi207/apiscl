<?php
// admin.php - Trang quản lý API keys
session_start();

// Thông tin đăng nhập admin (nên lưu trong database hoặc file riêng)
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // Nên hash password trong thực tế

// File lưu trữ API keys
define('KEYS_FILE', 'api_keys.json');

// Xử lý đăng nhập
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Sai tên đăng nhập hoặc mật khẩu!';
    }
}

// Xử lý đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Hàm đọc keys từ file
function loadKeys() {
    if (!file_exists(KEYS_FILE)) {
        return [];
    }
    $data = file_get_contents(KEYS_FILE);
    return json_decode($data, true) ?: [];
}

// Hàm lưu keys vào file
function saveKeys($keys) {
    file_put_contents(KEYS_FILE, json_encode($keys, JSON_PRETTY_PRINT));
}

// Hàm tạo API key ngẫu nhiên
function generateApiKey() {
    return 'kzi_' . bin2hex(random_bytes(24));
}

// Xử lý các action
if ($isLoggedIn && isset($_POST['action'])) {
    $keys = loadKeys();
    
    switch ($_POST['action']) {
        case 'create':
            $name = $_POST['key_name'] ?? 'Unnamed Key';
            $limit = intval($_POST['request_limit'] ?? 1000);
            $newKey = [
                'key' => generateApiKey(),
                'name' => $name,
                'created_at' => date('Y-m-d H:i:s'),
                'request_limit' => $limit,
                'request_count' => 0,
                'status' => 'active',
                'last_used' => null
            ];
            $keys[] = $newKey;
            saveKeys($keys);
            $success_message = 'Tạo API key thành công!';
            break;
            
        case 'delete':
            $keyToDelete = $_POST['key'] ?? '';
            $keys = array_filter($keys, function($k) use ($keyToDelete) {
                return $k['key'] !== $keyToDelete;
            });
            $keys = array_values($keys);
            saveKeys($keys);
            $success_message = 'Xóa API key thành công!';
            break;
            
        case 'toggle_status':
            $keyToToggle = $_POST['key'] ?? '';
            foreach ($keys as &$k) {
                if ($k['key'] === $keyToToggle) {
                    $k['status'] = $k['status'] === 'active' ? 'inactive' : 'active';
                    break;
                }
            }
            saveKeys($keys);
            $success_message = 'Cập nhật trạng thái thành công!';
            break;
            
        case 'reset_count':
            $keyToReset = $_POST['key'] ?? '';
            foreach ($keys as &$k) {
                if ($k['key'] === $keyToReset) {
                    $k['request_count'] = 0;
                    break;
                }
            }
            saveKeys($keys);
            $success_message = 'Reset số lượng request thành công!';
            break;
    }
}

$keys = loadKeys();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - API Key Management</title>
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
            padding: 20px;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .login-container h1 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
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
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
        
        .error {
            background: #f8d7da;
            color: #dc3545;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #2c3e50;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .create-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .create-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .create-form {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 15px;
            align-items: end;
        }
        
        .btn-create {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .keys-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .keys-section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .key-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        
        .key-card.inactive {
            border-left-color: #dc3545;
            opacity: 0.7;
        }
        
        .key-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .key-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .key-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .key-status.active {
            background: #d4edda;
            color: #155724;
        }
        
        .key-status.inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .key-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .key-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #495057;
        }
        
        .key-value {
            background: white;
            padding: 10px 15px;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        
        .key-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-toggle {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }
        
        .btn-delete {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn-copy {
            background: linear-gradient(135deg, #6c757d 0%, #545b62 100%);
            color: white;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .create-form {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php if (!$isLoggedIn): ?>
        <div class="login-container">
            <h1><i class="fas fa-lock"></i> Đăng nhập Admin</h1>
            
            <?php if (isset($login_error)): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Tên đăng nhập</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" name="login" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
            </form>
        </div>
    <?php else: ?>
        <div class="admin-container">
            <div class="header">
                <h1><i class="fas fa-key"></i> Quản lý API Keys</h1>
                <a href="?logout" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="success"><i class="fas fa-check-circle"></i> <?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($keys); ?></div>
                    <div class="stat-label">Tổng số keys</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($keys, fn($k) => $k['status'] === 'active')); ?></div>
                    <div class="stat-label">Keys đang hoạt động</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo array_sum(array_column($keys, 'request_count')); ?></div>
                    <div class="stat-label">Tổng requests</div>
                </div>
            </div>
            
            <div class="create-section">
                <h2><i class="fas fa-plus-circle"></i> Tạo API Key mới</h2>
                <form method="POST" class="create-form">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label>Tên Key</label>
                        <input type="text" name="key_name" placeholder="VD: Mobile App Key" required>
                    </div>
                    <div class="form-group">
                        <label>Giới hạn requests</label>
                        <input type="number" name="request_limit" value="1000" min="1" required>
                    </div>
                    <button type="submit" class="btn-create">
                        <i class="fas fa-plus"></i> Tạo Key
                    </button>
                </form>
            </div>
            
            <div class="keys-section">
                <h2><i class="fas fa-list"></i> Danh sách API Keys (<?php echo count($keys); ?>)</h2>
                
                <?php if (empty($keys)): ?>
                    <p style="text-align: center; color: #6c757d; padding: 40px;">
                        <i class="fas fa-info-circle"></i> Chưa có API key nào. Hãy tạo key mới!
                    </p>
                <?php else: ?>
                    <?php foreach ($keys as $key): ?>
                        <div class="key-card <?php echo $key['status']; ?>">
                            <div class="key-header">
                                <div class="key-name">
                                    <i class="fas fa-key"></i> <?php echo htmlspecialchars($key['name']); ?>
                                </div>
                                <div class="key-status <?php echo $key['status']; ?>">
                                    <?php echo $key['status'] === 'active' ? 'Hoạt động' : 'Vô hiệu hóa'; ?>
                                </div>
                            </div>
                            
                            <div class="key-value">
                                <?php echo htmlspecialchars($key['key']); ?>
                            </div>
                            
                            <div class="key-info">
                                <div class="key-info-item">
                                    <i class="fas fa-calendar"></i>
                                    <span>Tạo: <?php echo $key['created_at']; ?></span>
                                </div>
                                <div class="key-info-item">
                                    <i class="fas fa-chart-line"></i>
                                    <span>Requests: <?php echo $key['request_count']; ?> / <?php echo $key['request_limit']; ?><?php echo isset($key['limit_type']) && $key['limit_type'] === 'daily' ? '/ngày' : '/tháng'; ?></span>
                                </div>
                                <div class="key-info-item">
                                    <i class="fas fa-clock"></i>
                                    <span>Dùng lần cuối: <?php echo $key['last_used'] ?? 'Chưa dùng'; ?></span>
                                </div>
                            </div>
                            
                            <div class="key-actions">
                                <button class="btn-small btn-copy" onclick="copyKey('<?php echo $key['key']; ?>')">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="key" value="<?php echo $key['key']; ?>">
                                    <button type="submit" class="btn-small btn-toggle">
                                        <i class="fas fa-power-off"></i>
                                        <?php echo $key['status'] === 'active' ? 'Vô hiệu hóa' : 'Kích hoạt'; ?>
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reset_count">
                                    <input type="hidden" name="key" value="<?php echo $key['key']; ?>">
                                    <button type="submit" class="btn-small btn-reset">
                                        <i class="fas fa-redo"></i> Reset Count
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn xóa key này?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="key" value="<?php echo $key['key']; ?>">
                                    <button type="submit" class="btn-small btn-delete">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        function copyKey(key) {
            navigator.clipboard.writeText(key).then(() => {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
                    color: white;
                    padding: 15px 20px;
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
                    z-index: 1000;
                    font-weight: 600;
                `;
                notification.innerHTML = '<i class="fas fa-check"></i> Đã copy API key!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.remove();
                }, 2000);
            });
        }
    </script>
</body>
</html>
