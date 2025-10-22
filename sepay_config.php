<?php
// sepay_config.php - Cấu hình Sepay API

// Thông tin Sepay
define('SEPAY_API_URL', 'https://my.sepay.vn/userapi/transactions/list');
define('SEPAY_TOKEN', ''); // Thay bằng token thực của bạn
define('SEPAY_ACCOUNT_NUMBER', '0939042183'); // Số tài khoản MB Bank

// Hàm kiểm tra giao dịch từ Sepay
function checkSepayTransaction($orderId, $amount) {
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => SEPAY_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . SEPAY_TOKEN,
            'Content-Type: application/json'
        ],
    ]);
    
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);
    
    if ($err) {
        error_log('Sepay API Error: ' . $err);
        return false;
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['transactions'])) {
        return false;
    }
    
    // Kiểm tra trong danh sách giao dịch
    foreach ($data['transactions'] as $transaction) {
        // Kiểm tra nội dung chuyển khoản có chứa mã đơn hàng
        $content = strtoupper($transaction['transaction_content'] ?? '');
        $transAmount = intval($transaction['amount_in'] ?? 0);
        
        // Nếu tìm thấy mã đơn hàng và số tiền khớp
        if (strpos($content, strtoupper($orderId)) !== false && $transAmount == $amount) {
            return [
                'success' => true,
                'transaction_id' => $transaction['id'] ?? '',
                'amount' => $transAmount,
                'content' => $transaction['transaction_content'],
                'time' => $transaction['transaction_date'] ?? ''
            ];
        }
    }
    
    return false;
}

// Hàm tự động kích hoạt API key khi thanh toán thành công
function activateApiKey($order) {
    $keysFile = 'api_keys.json';
    $keys = [];
    
    if (file_exists($keysFile)) {
        $keys = json_decode(file_get_contents($keysFile), true);
    }
    
    $newKey = [
        'key' => $order['api_key'],
        'name' => $order['name'] . ' - ' . $order['plan_name'],
        'created_at' => date('Y-m-d H:i:s'),
        'request_limit' => $order['limit'],
        'request_count' => 0,
        'status' => 'active',
        'last_used' => null,
        'email' => $order['email'],
        'order_id' => $order['order_id'],
        'limit_type' => 'daily', // Giới hạn theo ngày
        'daily_reset_at' => date('Y-m-d')
    ];
    
    $keys[] = $newKey;
    file_put_contents($keysFile, json_encode($keys, JSON_PRETTY_PRINT));
    
    return true;
}

// Hàm cập nhật trạng thái đơn hàng
function updateOrderStatus($orderId, $status, $transactionInfo = null) {
    $ordersFile = 'orders.json';
    
    if (!file_exists($ordersFile)) {
        return false;
    }
    
    $orders = json_decode(file_get_contents($ordersFile), true);
    
    foreach ($orders as &$order) {
        if ($order['order_id'] === $orderId) {
            $order['status'] = $status;
            if ($transactionInfo) {
                $order['transaction'] = $transactionInfo;
                $order['paid_at'] = date('Y-m-d H:i:s');
            }
            break;
        }
    }
    
    file_put_contents($ordersFile, json_encode($orders, JSON_PRETTY_PRINT));
    return true;
}
?>
