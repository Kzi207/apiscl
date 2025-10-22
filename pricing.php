<?php
// pricing.php - Trang mua API key
session_start();

require_once 'db_config.php';

// Kiểm tra đăng nhập
$isLoggedIn = isset($_SESSION['user_id']);
$userEmail = $_SESSION['user_email'] ?? '';
$userName = $_SESSION['user_name'] ?? '';

// Xử lý thanh toán
if (isset($_POST['purchase'])) {
    if (!$isLoggedIn) {
        header('Location: login.php');
        exit;
    }
    
    $plan = $_POST['plan'] ?? '';
    $userId = $_SESSION['user_id'];
    $email = $userEmail;
    $name = $userName;
    
    if (empty($plan)) {
        $error_message = 'Gói không hợp lệ!';
    } else {
        // Xác định giá và limit (requests/ngày)
        $plans = [
            'free' => ['price' => 0, 'limit' => 10, 'name' => 'Gói Free'],
            'starter' => ['price' => 5000, 'limit' => 100, 'name' => 'Gói Starter'],
            'basic' => ['price' => 10000, 'limit' => 300, 'name' => 'Gói Basic'],
            'pro' => ['price' => 20000, 'limit' => 700, 'name' => 'Gói Pro']
        ];
        
        if (!isset($plans[$plan])) {
            $error_message = 'Gói không hợp lệ!';
        } else {
            $planInfo = $plans[$plan];
            
            try {
                // Tạo đơn hàng trong database
                $orderData = createOrder(
                    $userId,
                    $email,
                    $name,
                    $plan,
                    $planInfo['name'],
                    $planInfo['price'],
                    $planInfo['limit']
                );
                
                $orderId = $orderData['order_id'];
                $apiKey = $orderData['api_key'];
                
                // Lấy order đầy đủ từ DB
                $order = getOrderById($orderId);
                
                // Nếu là gói Free, tạo key ngay
                if ($planInfo['price'] == 0) {
                    // Tạo API key trong database
                    createApiKey(
                        $userId,
                        $email,
                        $name . ' - ' . $planInfo['name'],
                        $planInfo['limit'],
                        'daily',
                        $orderId
                    );
                    
                    // Cập nhật đơn hàng thành completed
                    updateOrderStatus($orderId, 'completed');
                    
                    $_SESSION['success_order'] = $order;
                    header('Location: success.php?completed=1');
                    exit;
                } else {
                    // Redirect đến trang thanh toán
                    $_SESSION['pending_order'] = $order;
                    header('Location: payment.php');
                    exit;
                }
            } catch (Exception $e) {
                $error_message = 'Lỗi tạo đơn hàng: ' . $e->getMessage();
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
    <title>Mua API Key - SoundCloud Downloader</title>
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
            text-align: center;
            color: white;
            margin-bottom: 50px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }
        
        .pricing-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
        }
        
        .pricing-card.featured {
            border: 3px solid #667eea;
        }
        
        .pricing-card.featured::before {
            content: 'PHỔ BIẾN';
            position: absolute;
            top: 20px;
            right: -35px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 5px 40px;
            transform: rotate(45deg);
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .plan-name {
            font-size: 1.5em;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .plan-price {
            font-size: 3em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .plan-price span {
            font-size: 0.4em;
            color: #6c757d;
        }
        
        .plan-duration {
            color: #6c757d;
            margin-bottom: 30px;
        }
        
        .plan-features {
            text-align: left;
            margin-bottom: 30px;
        }
        
        .plan-feature {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            color: #495057;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .plan-feature i {
            color: #28a745;
        }
        
        .plan-feature.disabled {
            opacity: 0.4;
        }
        
        .plan-feature.disabled i {
            color: #dc3545;
        }
        
        .btn-purchase {
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
        }
        
        .btn-purchase:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-purchase.free {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .modal-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
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
        }
        
        .btn-close {
            background: #6c757d;
            margin-top: 10px;
        }
        
        .error {
            background: #f8d7da;
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .features-section {
            background: white;
            padding: 40px;
            border-radius: 20px;
            margin-top: 50px;
        }
        
        .features-section h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .feature-item {
            text-align: center;
            padding: 20px;
        }
        
        .feature-icon {
            font-size: 3em;
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .feature-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .feature-desc {
            color: #6c757d;
            line-height: 1.6;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            margin-bottom: 30px;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <a href="index.php" class="back-link" style="margin: 0;">
                <i class="fas fa-arrow-left"></i> Quay lại trang chủ
            </a>
            
            <?php if ($isLoggedIn): ?>
                <a href="dashboard.php" class="back-link" style="margin: 0; background: rgba(40, 167, 69, 0.3);">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($userName); ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="back-link" style="margin: 0; background: rgba(255, 193, 7, 0.3);">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
            <?php endif; ?>
        </div>
        
        <div class="header">
            <h1><i class="fas fa-key"></i> Mua API Key</h1>
            <p>Chọn gói phù hợp với nhu cầu của bạn</p>
        </div>
        
        <div class="pricing-grid">
            <!-- Free Plan -->
            <div class="pricing-card">
                <div class="plan-name">Free</div>
                <div class="plan-price">
                    0₫
                    <span>/mãi mãi</span>
                </div>
                <div class="plan-duration">Miễn phí cho mọi người</div>
                <div class="plan-features">
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>10 requests/ngày</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tìm kiếm bài hát</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tải xuống MP3</span>
                    </div>
                    <div class="plan-feature disabled">
                        <i class="fas fa-times"></i>
                        <span>Priority support</span>
                    </div>
                </div>
                <button class="btn-purchase free" onclick="openModal('free')">
                    <i class="fas fa-gift"></i> Nhận ngay miễn phí
                </button>
            </div>
            
            <!-- Starter Plan -->
            <div class="pricing-card">
                <div class="plan-name">Starter</div>
                <div class="plan-price">
                    5.000₫
                    <span>/tháng</span>
                </div>
                <div class="plan-duration">Phù hợp cho người mới</div>
                <div class="plan-features">
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>100 requests/ngày</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tìm kiếm bài hát</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tải xuống MP3</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Email support</span>
                    </div>
                </div>
                <button class="btn-purchase" onclick="openModal('starter')">
                    <i class="fas fa-shopping-cart"></i> Mua ngay
                </button>
            </div>
            
            <!-- Basic Plan -->
            <div class="pricing-card featured">
                <div class="plan-name">Basic</div>
                <div class="plan-price">
                    10.000₫
                    <span>/tháng</span>
                </div>
                <div class="plan-duration">Phù hợp cho cá nhân</div>
                <div class="plan-features">
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>300 requests/ngày</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tìm kiếm bài hát</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tải xuống MP3</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Priority support</span>
                    </div>
                </div>
                <button class="btn-purchase" onclick="openModal('basic')">
                    <i class="fas fa-star"></i> Mua ngay
                </button>
            </div>
            
            <!-- Pro Plan -->
            <div class="pricing-card">
                <div class="plan-name">Pro</div>
                <div class="plan-price">
                    20.000₫
                    <span>/tháng</span>
                </div>
                <div class="plan-duration">Phù hợp cho doanh nghiệp</div>
                <div class="plan-features">
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>700 requests/ngày</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>Tất cả tính năng Basic</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>24/7 Priority support</span>
                    </div>
                    <div class="plan-feature">
                        <i class="fas fa-check"></i>
                        <span>API documentation</span>
                    </div>
                </div>
                <button class="btn-purchase" onclick="openModal('pro')">
                    <i class="fas fa-rocket"></i> Mua ngay
                </button>
            </div>
        </div>
        
        <div class="features-section">
            <h2><i class="fas fa-star"></i> Tại sao chọn chúng tôi?</h2>
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <div class="feature-title">Siêu nhanh</div>
                    <div class="feature-desc">API response time < 200ms, tối ưu cho mọi ứng dụng</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="feature-title">Bảo mật</div>
                    <div class="feature-desc">Mã hóa SSL, bảo vệ API key và dữ liệu người dùng</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="feature-title">Uptime 99.9%</div>
                    <div class="feature-desc">Hệ thống ổn định, luôn sẵn sàng phục vụ 24/7</div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <div class="feature-title">Support tốt</div>
                    <div class="feature-desc">Đội ngũ hỗ trợ chuyên nghiệp, phản hồi nhanh</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div id="purchaseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-shopping-cart"></i> Thông tin mua hàng</h2>
                <p id="modalPlanName" style="color: #667eea;"></p>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!$isLoggedIn): ?>
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #ffc107;">
                    <p style="color: #856404;">
                        <i class="fas fa-info-circle"></i> Bạn cần đăng nhập để mua API key
                    </p>
                </div>
                <a href="login.php" class="btn-submit" style="text-align: center; text-decoration: none;">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                </a>
            <?php else: ?>
                <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                    <p style="color: #155724;">
                        <i class="fas fa-user-check"></i> Đăng nhập: <strong><?php echo htmlspecialchars($userName); ?></strong>
                    </p>
                    <p style="color: #155724; font-size: 0.9em; margin-top: 5px;">
                        <?php echo htmlspecialchars($userEmail); ?>
                    </p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="plan" id="planInput">
                    
                    <button type="submit" name="purchase" class="btn-submit">
                        <i class="fas fa-check"></i> Xác nhận mua
                    </button>
                </form>
            <?php endif; ?>
                
            <button type="button" class="btn-submit btn-close" onclick="closeModal()">
                <i class="fas fa-times"></i> Đóng
            </button>
        </div>
    </div>
    
    <script>
        const plans = {
            'free': 'Gói Free - 0₫ (Miễn phí)',
            'starter': 'Gói Starter - 5.000₫/tháng',
            'basic': 'Gói Basic - 10.000₫/tháng',
            'pro': 'Gói Pro - 20.000₫/tháng'
        };
        
        function openModal(plan) {
            document.getElementById('planInput').value = plan;
            document.getElementById('modalPlanName').textContent = plans[plan];
            document.getElementById('purchaseModal').classList.add('show');
        }
        
        function closeModal() {
            document.getElementById('purchaseModal').classList.remove('show');
        }
    </script>
</body>
</html>
