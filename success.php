<?php
// success.php - Trang thành công
session_start();

require_once 'db_config.php';

// Kiểm tra order từ session hoặc từ database
$order = $_SESSION['success_order'] ?? $_SESSION['pending_order'] ?? null;

// Nếu có completed parameter, lấy từ database
if (isset($_GET['completed']) && isset($_GET['order_id'])) {
    $order = getOrderById($_GET['order_id']);
} elseif (isset($_GET['completed']) && $order && isset($order['order_id'])) {
    // Lấy order mới nhất từ database
    $order = getOrderById($order['order_id']);
}

if (!$order) {
    header('Location: pricing.php');
    exit;
}

$isCompleted = $order['status'] === 'completed';
$isConfirmed = isset($_GET['confirmed']);
$isFree = isset($order['price']) && $order['price'] == 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thành công - SoundCloud Downloader</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .success-container {
            background: white;
            border-radius: 20px;
            padding: 50px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 5em;
            color: #28a745;
            margin-bottom: 20px;
            animation: scaleIn 0.5s ease;
        }
        
        @keyframes scaleIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        .success-title {
            font-size: 2em;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .success-message {
            color: #6c757d;
            font-size: 1.1em;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .order-details {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: left;
        }
        
        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .api-key-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border-left: 5px solid #1976d2;
        }
        
        .api-key-section h3 {
            color: #1976d2;
            margin-bottom: 15px;
        }
        
        .api-key-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 1.1em;
            color: #333;
            word-break: break-all;
            margin-bottom: 15px;
            border: 2px solid #1976d2;
        }
        
        .btn-copy {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .warning-box {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            border-left: 4px solid #ffc107;
            text-align: left;
        }
        
        .warning-box h4 {
            color: #e65100;
            margin-bottom: 10px;
        }
        
        .warning-box p {
            color: #333;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        
        <h1 class="success-title">
            <?php echo $isFree ? 'Chúc mừng!' : ($isConfirmed ? 'Đơn hàng đã được ghi nhận!' : 'Thành công!'); ?>
        </h1>
        
        <p class="success-message">
            <?php if ($isCompleted): ?>
                Thanh toán thành công! API key của bạn đã được kích hoạt.
            <?php elseif ($isConfirmed): ?>
                Cảm ơn bạn đã thanh toán. Chúng tôi sẽ xác nhận và kích hoạt API key của bạn trong vòng 24h.
            <?php else: ?>
                Đơn hàng của bạn đã được tạo thành công!
            <?php endif; ?>
        </p>
        
        <div class="order-details">
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Thông tin đơn hàng</h3>
            <div class="detail-item">
                <span>Mã đơn hàng:</span>
                <strong><?php echo $order['order_id']; ?></strong>
            </div>
            <div class="detail-item">
                <span>Gói dịch vụ:</span>
                <strong><?php echo $order['plan_name']; ?></strong>
            </div>
            <div class="detail-item">
                <span>Giới hạn requests:</span>
                <strong><?php echo number_format($order['limit']); ?> requests/tháng</strong>
            </div>
            <div class="detail-item">
                <span>Email:</span>
                <strong><?php echo htmlspecialchars($order['email']); ?></strong>
            </div>
            <div class="detail-item">
                <span>Trạng thái:</span>
                <strong style="color: <?php echo $order['status'] === 'completed' ? '#28a745' : '#ffc107'; ?>">
                    <?php echo $order['status'] === 'completed' ? 'Đã kích hoạt' : 'Chờ xác nhận'; ?>
                </strong>
            </div>
        </div>
        
        <?php if ($isCompleted): ?>
            <div class="api-key-section">
                <h3><i class="fas fa-key"></i> API Key của bạn</h3>
                <div class="api-key-box" id="apiKey">
                    <?php echo $order['api_key']; ?>
                </div>
                <button class="btn-copy" onclick="copyApiKey()">
                    <i class="fas fa-copy"></i> Copy API Key
                </button>
            </div>
            
            <div class="warning-box">
                <h4><i class="fas fa-exclamation-triangle"></i> Lưu ý quan trọng</h4>
                <p>• Hãy lưu API key này cẩn thận. Bạn sẽ không thể xem lại sau này.</p>
                <p>• API key đã được gửi đến email: <strong><?php echo htmlspecialchars($order['email']); ?></strong></p>
                <p>• Giới hạn: <?php echo number_format($order['limit']); ?> requests/tháng</p>
                <p>• Hết hạn: <?php echo date('d/m/Y', strtotime($order['expires_at'])); ?></p>
                <?php if (isset($order['transaction'])): ?>
                <p>• Mã giao dịch: <strong><?php echo $order['transaction']['transaction_id']; ?></strong></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="warning-box">
                <h4><i class="fas fa-info-circle"></i> Thông tin thanh toán</h4>
                <p>• API key sẽ được gửi đến email: <strong><?php echo htmlspecialchars($order['email']); ?></strong></p>
                <p>• Thời gian xử lý: Tự động sau khi chuyển khoản (hoặc tối đa 24h)</p>
                <p>• Nếu có thắc mắc, vui lòng liên hệ support với mã đơn hàng: <strong><?php echo $order['order_id']; ?></strong></p>
            </div>
        <?php endif; ?>
        
        <div class="actions">
            <?php if ($isCompleted): ?>
                <a href="dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i> Về trang quản lý
                </a>
                <a href="index.php#api-docs" class="btn btn-secondary">
                    <i class="fas fa-book"></i> Xem tài liệu API
                </a>
            <?php else: ?>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-home"></i> Về trang chủ
                </a>
            <?php endif; ?>
        </div>
        
        <?php if ($isCompleted): ?>
        <div style="text-align: center; margin-top: 20px; color: #6c757d; font-size: 0.9em;">
            <i class="fas fa-clock"></i> Tự động chuyển đến trang quản lý sau <span id="countdown">5</span> giây...
        </div>
        <script>
            // Tự động chuyển về dashboard sau 5 giây với countdown
            let seconds = 5;
            const countdownElement = document.getElementById('countdown');
            
            const interval = setInterval(() => {
                seconds--;
                countdownElement.textContent = seconds;
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = 'dashboard.php';
                }
            }, 1000);
        </script>
        <?php endif; ?>
    </div>
    
    <script>
        function copyApiKey() {
            const apiKey = document.getElementById('apiKey').textContent.trim();
            navigator.clipboard.writeText(apiKey).then(() => {
                alert('✅ Đã copy API key!');
            });
        }
    </script>
</body>
</html>
<?php
// Clear session
unset($_SESSION['success_order']);
unset($_SESSION['pending_order']);
?>
