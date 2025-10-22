<?php
// dashboard.php - Trang quản lý API keys cho người dùng
session_start();

require_once 'db_config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$userEmail = $_SESSION['user_email'];
$userName = $_SESSION['user_name'];

// Đăng xuất
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Lấy danh sách API keys và orders từ database
$userKeys = getUserApiKeys($userEmail);
$userOrders = getUserOrders($userEmail);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SoundCloud Downloader</title>
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
        
        .container {
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
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5em;
            font-weight: 600;
        }
        
        .user-details h2 {
            color: #2c3e50;
            font-size: 1.3em;
        }
        
        .user-details p {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .header-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 2.5em;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .key-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 5px solid #667eea;
        }
        
        .key-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .key-name {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .key-status {
            padding: 6px 15px;
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
        
        .key-value {
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 1em;
            word-break: break-all;
            margin: 15px 0;
            border: 2px solid #dee2e6;
            position: relative;
        }
        
        .usage-bar {
            margin: 15px 0;
        }
        
        .usage-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            color: #495057;
            font-size: 0.9em;
        }
        
        .progress-bar {
            height: 10px;
            background: #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }
        
        .progress-fill.warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        
        .progress-fill.danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .key-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .key-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #495057;
            font-size: 0.9em;
        }
        
        .btn-copy {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 8px 15px;
            font-size: 0.9em;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4em;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        .empty-state h3 {
            margin-bottom: 10px;
            color: #495057;
        }
        
        .order-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #6c757d;
        }
        
        .order-card.completed {
            border-left-color: #28a745;
        }
        
        .order-card.pending {
            border-left-color: #ffc107;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .order-id {
            font-family: monospace;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .order-status {
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .order-status.completed {
            background: #d4edda;
            color: #155724;
        }
        
        .order-status.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
                <div class="user-details">
                    <h2><?php echo htmlspecialchars($userName); ?></h2>
                    <p><?php echo htmlspecialchars($userEmail); ?></p>
                </div>
            </div>
            <div class="header-actions">
                <a href="pricing.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> Mua API Key
                </a>
                <a href="?logout" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                </a>
            </div>
        </div>
        
        <div class="stats">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-key"></i></div>
                <div class="stat-value"><?php echo count($userKeys); ?></div>
                <div class="stat-label">Tổng số API Keys</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo count(array_filter($userKeys, fn($k) => $k['status'] === 'active')); ?></div>
                <div class="stat-label">Keys đang hoạt động</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-value"><?php echo array_sum(array_column($userKeys, 'request_count')); ?></div>
                <div class="stat-label">Tổng requests đã dùng</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                <div class="stat-value"><?php echo count($userOrders); ?></div>
                <div class="stat-label">Tổng đơn hàng</div>
            </div>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-key"></i> API Keys của tôi</h2>
            
            <?php if (empty($userKeys)): ?>
                <div class="empty-state">
                    <i class="fas fa-key"></i>
                    <h3>Chưa có API key nào</h3>
                    <p>Hãy mua gói API key để bắt đầu sử dụng dịch vụ</p>
                    <br>
                    <a href="pricing.php" class="btn btn-primary">
                        <i class="fas fa-shopping-cart"></i> Mua API Key
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($userKeys as $key): ?>
                    <?php
                    $usagePercent = ($key['request_count'] / $key['request_limit']) * 100;
                    $progressClass = '';
                    if ($usagePercent >= 90) {
                        $progressClass = 'danger';
                    } elseif ($usagePercent >= 70) {
                        $progressClass = 'warning';
                    }
                    
                    $limitType = isset($key['limit_type']) && $key['limit_type'] === 'daily' ? 'ngày' : 'tháng';
                    $remaining = $key['request_limit'] - $key['request_count'];
                    ?>
                    <div class="key-card">
                        <div class="key-header">
                            <div class="key-name">
                                <i class="fas fa-key"></i> <?php echo htmlspecialchars($key['name']); ?>
                            </div>
                            <div class="key-status <?php echo $key['status']; ?>">
                                <?php echo $key['status'] === 'active' ? 'Hoạt động' : 'Vô hiệu hóa'; ?>
                            </div>
                        </div>
                        
                            <div class="key-value">
                            <?php echo htmlspecialchars($key['key_value']); ?>
                            <button class="btn btn-copy" onclick="copyKey('<?php echo $key['key_value']; ?>')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        
                        <div class="usage-bar">
                            <div class="usage-label">
                                <span><strong>Đã sử dụng:</strong> <?php echo $key['request_count']; ?> / <?php echo $key['request_limit']; ?> requests/<?php echo $limitType; ?></span>
                                <span style="color: <?php echo $remaining > 0 ? '#28a745' : '#dc3545'; ?>; font-weight: 600;">
                                    Còn lại: <?php echo $remaining; ?>
                                </span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $progressClass; ?>" style="width: <?php echo min($usagePercent, 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <div class="key-info">
                            <div class="key-info-item">
                                <i class="fas fa-calendar"></i>
                                <span>Tạo: <?php echo date('d/m/Y', strtotime($key['created_at'])); ?></span>
                            </div>
                            <div class="key-info-item">
                                <i class="fas fa-clock"></i>
                                <span>Dùng lần cuối: <?php echo $key['last_used'] ? date('d/m/Y H:i', strtotime($key['last_used'])) : 'Chưa dùng'; ?></span>
                            </div>
                            <?php if (isset($key['daily_reset_at']) && $key['limit_type'] === 'daily'): ?>
                            <div class="key-info-item">
                                <i class="fas fa-redo"></i>
                                <span>Reset hàng ngày lúc 00:00</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-shopping-bag"></i> Lịch sử đơn hàng</h2>
            
            <?php if (empty($userOrders)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3>Chưa có đơn hàng nào</h3>
                    <p>Lịch sử mua hàng của bạn sẽ hiển thị ở đây</p>
                </div>
            <?php else: ?>
                <?php foreach ($userOrders as $order): ?>
                    <div class="order-card <?php echo $order['status']; ?>">
                        <div class="order-header">
                            <div class="order-id">
                                <i class="fas fa-hashtag"></i> <?php echo $order['order_id']; ?>
                            </div>
                            <div class="order-status <?php echo $order['status']; ?>">
                                <?php echo $order['status'] === 'completed' ? 'Đã thanh toán' : 'Chờ thanh toán'; ?>
                            </div>
                        </div>
                        <p style="margin: 5px 0; color: #495057;">
                            <strong><?php echo $order['plan_name']; ?></strong> - 
                            <?php echo number_format($order['limit']); ?> requests/ngày - 
                            <?php echo number_format($order['price']); ?>₫
                        </p>
                        <p style="margin: 5px 0; color: #6c757d; font-size: 0.9em;">
                            <i class="fas fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </p>
                        <?php if ($order['status'] === 'pending'): ?>
                            <a href="payment.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-primary" style="margin-top: 10px; font-size: 0.9em;">
                                <i class="fas fa-credit-card"></i> Tiếp tục thanh toán
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
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
        
        // Auto refresh mỗi 30 giây để cập nhật số liệu
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
