<?php
// check_payment.php - Kiểm tra thanh toán tự động qua Sepay
require_once 'sepay_config.php';
require_once 'db_config.php';

header('Content-Type: application/json');

$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    echo json_encode(['error' => 'Thiếu mã đơn hàng']);
    exit;
}

// Lấy đơn hàng từ database
$currentOrder = getOrderById($orderId);

if (!$currentOrder) {
    echo json_encode(['error' => 'Đơn hàng không tồn tại']);
    exit;
}

// Nếu đã hoàn thành rồi
if ($currentOrder['status'] === 'completed') {
    echo json_encode([
        'status' => 'completed',
        'message' => 'Đơn hàng đã được thanh toán',
        'api_key' => $currentOrder['api_key']
    ]);
    exit;
}

// Kiểm tra giao dịch qua Sepay
$transaction = checkSepayTransaction($orderId, $currentOrder['price']);

if ($transaction && $transaction['success']) {
    // Kích hoạt API key trong database
    $apiKeyValue = createApiKey(
        $currentOrder['user_id'],
        $currentOrder['email'],
        $currentOrder['name'] . ' - ' . $currentOrder['plan_name'],
        $currentOrder['request_limit'],
        'daily',
        $orderId
    );
    
    // Cập nhật trạng thái đơn hàng
    updateOrderStatus(
        $orderId, 
        'completed', 
        $transaction['transaction_id'], 
        json_encode($transaction)
    );
    
    echo json_encode([
        'status' => 'completed',
        'message' => 'Thanh toán thành công! API key đã được kích hoạt.',
        'api_key' => $apiKeyValue,
        'transaction' => $transaction
    ]);
} else {
    echo json_encode([
        'status' => 'pending',
        'message' => 'Chưa tìm thấy giao dịch. Vui lòng chờ hoặc kiểm tra lại.'
    ]);
}
?>
