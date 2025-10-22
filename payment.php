<?php
// payment.php - Trang thanh toán
session_start();

require_once 'db_config.php';

// Lấy order từ session hoặc từ URL parameter
$order = null;

if (isset($_GET['order_id'])) {
    // Lấy từ database
    $order = getOrderById($_GET['order_id']);
} elseif (isset($_SESSION['pending_order'])) {
    $order = $_SESSION['pending_order'];
}

if (!$order) {
    header('Location: pricing.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - SoundCloud Downloader</title>
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
        
        .payment-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .payment-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .order-summary {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .order-item:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2em;
            color: #667eea;
        }
        
        .payment-methods {
            margin-bottom: 30px;
        }
        
        .payment-methods h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .payment-method {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #667eea;
        }
        
        .payment-method.active {
            border-color: #667eea;
            background: #e3f2fd;
        }
        
        .payment-method h4 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .payment-method p {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .qr-code {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .qr-code img {
            max-width: 300px;
            width: 100%;
            border: 3px solid #667eea;
            border-radius: 10px;
        }
        
        .bank-info {
            background: #fff3cd;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            border-left: 4px solid #ffc107;
        }
        
        .bank-info p {
            margin: 8px 0;
            color: #333;
        }
        
        .bank-info strong {
            color: #e65100;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            margin-bottom: 10px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card"></i> Thanh toán</h1>
            <p>Mã đơn hàng: <strong><?php echo $order['order_id']; ?></strong></p>
        </div>
        
        <div class="order-summary">
            <h3 style="margin-bottom: 15px;">Thông tin đơn hàng</h3>
            <div class="order-item">
                <span>Gói dịch vụ:</span>
                <strong><?php echo $order['plan_name']; ?></strong>
            </div>
            <div class="order-item">
                <span>Số lượng requests:</span>
                <strong><?php echo number_format($order['limit']); ?> requests/tháng</strong>
            </div>
            <div class="order-item">
                <span>Khách hàng:</span>
                <strong><?php echo htmlspecialchars($order['name']); ?></strong>
            </div>
            <div class="order-item">
                <span>Email:</span>
                <strong><?php echo htmlspecialchars($order['email']); ?></strong>
            </div>
            <div class="order-item">
                <span>Tổng tiền:</span>
                <strong><?php echo number_format($order['price']); ?>₫</strong>
            </div>
        </div>
        
        <div class="payment-methods">
            <h3><i class="fas fa-wallet"></i> Phương thức thanh toán</h3>
            
            <div class="payment-method active">
                <h4><i class="fas fa-university"></i> Chuyển khoản ngân hàng</h4>
                <p>Quét mã QR hoặc chuyển khoản thủ công</p>
            </div>
        </div>
        
        <div class="qr-code">
            <h3 style="margin-bottom: 20px;">Quét mã QR để thanh toán</h3>
            <?php
            // Tạo QR code từ Sepay
            $sepayQR = 'https://img.vietqr.io/image/MB-0939042183-compact.png?amount=' . $order['price'] . '&addInfo=' . urlencode($order['order_id']);
            ?>
            <img src="<?php echo $sepayQR; ?>" alt="QR Code" style="max-width: 350px;">
            
            <div class="bank-info">
                <p><strong>Ngân hàng:</strong> MB Bank (MBBank)</p>
                <p><strong>Số tài khoản:</strong> 0939042183</p>
                <p><strong>Chủ tài khoản:</strong> LE KHANH DUY</p>
                <p><strong>Số tiền:</strong> <?php echo number_format($order['price']); ?>₫</p>
                <p><strong>Nội dung:</strong> <?php echo $order['order_id']; ?></p>
                <p style="margin-top: 10px; color: #e65100;"><i class="fas fa-info-circle"></i> <strong>Quan trọng:</strong> Vui lòng chuyển khoản ĐÚNG số tiền và nội dung để hệ thống tự động kích hoạt API key!</p>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <p id="checkStatus" style="color: #6c757d; font-size: 0.9em;">
                <i class="fas fa-sync fa-spin"></i> Đang tự động kiểm tra giao dịch...
            </p>
        </div>
        
        <button class="btn btn-confirm" id="checkNowBtn" onclick="checkPaymentNow()">
            <i class="fas fa-sync"></i> Kiểm tra ngay
        </button>
        
        <a href="pricing.php" class="btn btn-cancel" style="text-decoration: none;">
            <i class="fas fa-times"></i> Hủy
        </a>
    </div>
    
    <script>
        const orderId = '<?php echo $order['order_id']; ?>';
        let checkInterval;
        
        // Tự động kiểm tra giao dịch mỗi 10 giây
        function autoCheckPayment() {
            fetch('check_payment.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed') {
                        clearInterval(checkInterval);
                        document.getElementById('checkStatus').innerHTML = 
                            '<i class="fas fa-check-circle" style="color: #28a745;"></i> Thanh toán thành công!';
                        
                        setTimeout(() => {
                            window.location.href = 'success.php?completed=1';
                        }, 1000);
                    } else {
                        document.getElementById('checkStatus').innerHTML = 
                            '<i class="fas fa-clock"></i> Chưa phát hiện giao dịch. Đang tiếp tục kiểm tra...';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }
        
        // Kiểm tra thủ công
        function checkPaymentNow() {
            const btn = document.getElementById('checkNowBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang kiểm tra...';
            
            fetch('check_payment.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync"></i> Kiểm tra ngay';
                    
                    if (data.status === 'completed') {
                        clearInterval(checkInterval);
                        alert('✅ Thanh toán thành công!\n\nAPI key: ' + data.api_key);
                        window.location.href = 'success.php?completed=1';
                    } else {
                        alert('⏳ Chưa tìm thấy giao dịch.\n\nVui lòng đảm bảo bạn đã chuyển khoản đúng số tiền và nội dung.');
                    }
                })
                .catch(error => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-sync"></i> Kiểm tra ngay';
                    alert('❌ Lỗi kết nối. Vui lòng thử lại.');
                });
        }
        
        // Bắt đầu kiểm tra tự động
        checkInterval = setInterval(autoCheckPayment, 10000); // Kiểm tra mỗi 10 giây
        autoCheckPayment(); // Kiểm tra ngay lập tức
    </script>
</body>
</html>
