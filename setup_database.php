<?php
// setup_database.php - Script tự động setup database

echo "<h1>Setup Database</h1>";

// Kết nối MySQL
$host = 'localhost';
$user = 'root';
$pass = '';

try {
    // Kết nối không cần database name để tạo database
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Kết nối MySQL thành công!</p>";
    
    // Đọc và thực thi file SQL
    $sql = file_get_contents('database.sql');
    
    // Tách các câu lệnh SQL
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Bỏ qua lỗi duplicate
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "<p style='color: orange;'>⚠️ Warning: " . $e->getMessage() . "</p>";
                }
            }
        }
    }
    
    echo "<p>✅ Tạo database thành công!</p>";
    echo "<p>✅ Tạo các bảng thành công!</p>";
    
    // Kiểm tra các bảng
    $pdo->exec("USE soundcloud_api");
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Các bảng đã tạo:</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>✅ $table</li>";
    }
    echo "</ul>";
    
    // Migrate dữ liệu từ JSON sang SQL (nếu có)
    echo "<h2>Migrate dữ liệu từ JSON:</h2>";
    
    // Migrate users
    if (file_exists('users.json')) {
        $users = json_decode(file_get_contents('users.json'), true);
        $stmt = $pdo->prepare("INSERT IGNORE INTO users (user_id, email, password, name, created_at) VALUES (?, ?, ?, ?, ?)");
        
        foreach ($users as $user) {
            $stmt->execute([
                $user['id'],
                $user['email'],
                $user['password'],
                $user['name'],
                $user['created_at']
            ]);
        }
        echo "<p>✅ Migrated " . count($users) . " users</p>";
    }
    
    // Migrate API keys
    if (file_exists('api_keys.json')) {
        $keys = json_decode(file_get_contents('api_keys.json'), true);
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO api_keys 
            (key_value, user_id, name, email, request_limit, request_count, limit_type, status, order_id, created_at, last_used, daily_reset_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($keys as $key) {
            $userId = 'ADMIN-001'; // Default user nếu không có
            
            $stmt->execute([
                $key['key'],
                $userId,
                $key['name'],
                $key['email'] ?? 'unknown@example.com',
                $key['request_limit'],
                $key['request_count'],
                $key['limit_type'] ?? 'daily',
                $key['status'],
                $key['order_id'] ?? null,
                $key['created_at'],
                $key['last_used'],
                $key['daily_reset_at'] ?? date('Y-m-d')
            ]);
        }
        echo "<p>✅ Migrated " . count($keys) . " API keys</p>";
    }
    
    // Migrate orders
    if (file_exists('orders.json')) {
        $orders = json_decode(file_get_contents('orders.json'), true);
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO orders 
            (order_id, user_id, api_key, plan, plan_name, price, request_limit, email, name, status, created_at, paid_at, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($orders as $order) {
            $userId = 'ADMIN-001'; // Default user nếu không có
            
            $stmt->execute([
                $order['order_id'],
                $userId,
                $order['api_key'],
                $order['plan'] ?? 'unknown',
                $order['plan_name'],
                $order['price'],
                $order['limit'],
                $order['email'],
                $order['name'],
                $order['status'],
                $order['created_at'],
                $order['paid_at'] ?? null,
                $order['expires_at']
            ]);
        }
        echo "<p>✅ Migrated " . count($orders) . " orders</p>";
    }
    
    echo "<h2>✅ Setup hoàn tất!</h2>";
    echo "<p><a href='index.php'>Về trang chủ</a></p>";
    echo "<p><a href='admin.php'>Đăng nhập Admin</a></p>";
    echo "<p><a href='login.php'>Đăng nhập User</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . $e->getMessage() . "</p>";
}
?>
