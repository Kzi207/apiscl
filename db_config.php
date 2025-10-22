<?php
// db_config.php - Cấu hình kết nối database

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'soundcloud_api');

// Tạo kết nối database
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Lỗi kết nối database. Vui lòng kiểm tra cấu hình.");
        }
    }
    
    return $pdo;
}

// ================== USER FUNCTIONS ==================

function createUser($email, $password, $name) {
    $db = getDB();
    $userId = 'USER-' . strtoupper(bin2hex(random_bytes(6)));
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("INSERT INTO users (user_id, email, password, name) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $email, $hashedPassword, $name]);
    
    return $userId;
}

function getUserByEmail($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function getUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

// ================== API KEY FUNCTIONS ==================

function createApiKey($userId, $email, $name, $requestLimit, $limitType = 'daily', $orderId = null) {
    $db = getDB();
    $apiKey = 'kzi_' . bin2hex(random_bytes(24));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    $stmt = $db->prepare("
        INSERT INTO api_keys 
        (key_value, user_id, name, email, request_limit, limit_type, order_id, expires_at, daily_reset_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $apiKey, 
        $userId, 
        $name, 
        $email, 
        $requestLimit, 
        $limitType, 
        $orderId, 
        $expiresAt,
        date('Y-m-d')
    ]);
    
    return $apiKey;
}

function getApiKeyByValue($keyValue) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM api_keys WHERE key_value = ?");
    $stmt->execute([$keyValue]);
    return $stmt->fetch();
}

function getUserApiKeys($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM api_keys WHERE email = ? ORDER BY created_at DESC");
    $stmt->execute([$email]);
    return $stmt->fetchAll();
}

function updateApiKeyUsage($keyValue) {
    $db = getDB();
    
    // Lấy thông tin key
    $key = getApiKeyByValue($keyValue);
    
    if (!$key) {
        return false;
    }
    
    // Kiểm tra daily reset
    if ($key['limit_type'] === 'daily') {
        $today = date('Y-m-d');
        if ($key['daily_reset_at'] !== $today) {
            // Reset count về 0
            $stmt = $db->prepare("
                UPDATE api_keys 
                SET request_count = 1, last_used = ?, daily_reset_at = ? 
                WHERE key_value = ?
            ");
            $stmt->execute([date('Y-m-d H:i:s'), $today, $keyValue]);
            return true;
        }
    }
    
    // Tăng count
    $stmt = $db->prepare("
        UPDATE api_keys 
        SET request_count = request_count + 1, last_used = ? 
        WHERE key_value = ?
    ");
    $stmt->execute([date('Y-m-d H:i:s'), $keyValue]);
    
    return true;
}

function getAllApiKeys() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM api_keys ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function updateApiKeyStatus($keyValue, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE api_keys SET status = ? WHERE key_value = ?");
    return $stmt->execute([$status, $keyValue]);
}

function resetApiKeyCount($keyValue) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE api_keys SET request_count = 0 WHERE key_value = ?");
    return $stmt->execute([$keyValue]);
}

function deleteApiKey($keyValue) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM api_keys WHERE key_value = ?");
    return $stmt->execute([$keyValue]);
}

// ================== ORDER FUNCTIONS ==================

function createOrder($userId, $email, $name, $plan, $planName, $price, $requestLimit) {
    $db = getDB();
    $orderId = 'KD' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    $apiKey = 'kzi_' . bin2hex(random_bytes(24));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 year'));
    
    $stmt = $db->prepare("
        INSERT INTO orders 
        (order_id, user_id, api_key, plan, plan_name, price, request_limit, email, name, expires_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $orderId,
        $userId,
        $apiKey,
        $plan,
        $planName,
        $price,
        $requestLimit,
        $email,
        $name,
        $expiresAt
    ]);
    
    return [
        'order_id' => $orderId,
        'api_key' => $apiKey
    ];
}

function getOrderById($orderId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return $stmt->fetch();
}

function getUserOrders($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM orders WHERE email = ? ORDER BY created_at DESC");
    $stmt->execute([$email]);
    return $stmt->fetchAll();
}

function updateOrderStatus($orderId, $status, $transactionId = null, $transactionData = null) {
    $db = getDB();
    
    if ($transactionId) {
        $stmt = $db->prepare("
            UPDATE orders 
            SET status = ?, transaction_id = ?, transaction_data = ?, paid_at = ? 
            WHERE order_id = ?
        ");
        $stmt->execute([
            $status, 
            $transactionId, 
            $transactionData, 
            date('Y-m-d H:i:s'), 
            $orderId
        ]);
    } else {
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $stmt->execute([$status, $orderId]);
    }
    
    return true;
}

// ================== REQUEST LOG FUNCTIONS ==================

function logApiRequest($apiKey, $action, $query = null, $trackId = null) {
    $db = getDB();
    
    $stmt = $db->prepare("
        INSERT INTO request_logs 
        (api_key, action, query, track_id, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $apiKey,
        $action,
        $query,
        $trackId,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

function getApiKeyStats($keyValue, $days = 30) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as requests
        FROM request_logs 
        WHERE api_key = ? 
        AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ");
    $stmt->execute([$keyValue, $days]);
    return $stmt->fetchAll();
}

// ================== UTILITY FUNCTIONS ==================

function getTotalStats() {
    $db = getDB();
    
    $stats = [
        'total_users' => 0,
        'total_keys' => 0,
        'active_keys' => 0,
        'total_requests' => 0,
        'total_orders' => 0,
        'completed_orders' => 0
    ];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Total keys
    $stmt = $db->query("SELECT COUNT(*) as count FROM api_keys");
    $stats['total_keys'] = $stmt->fetch()['count'];
    
    // Active keys
    $stmt = $db->query("SELECT COUNT(*) as count FROM api_keys WHERE status = 'active'");
    $stats['active_keys'] = $stmt->fetch()['count'];
    
    // Total requests
    $stmt = $db->query("SELECT SUM(request_count) as total FROM api_keys");
    $stats['total_requests'] = $stmt->fetch()['total'] ?? 0;
    
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $stmt->fetch()['count'];
    
    // Completed orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'completed'");
    $stats['completed_orders'] = $stmt->fetch()['count'];
    
    return $stats;
}
?>
