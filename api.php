<?php
// api.php - API endpoint chính

require_once 'config.php';
require_once 'SoundCloudDownloader.php';
require_once 'db_config.php';

class SoundCloudAPI {
    private $downloader;
    private $requireApiKey = true; // LUÔN yêu cầu API key
    
    public function __construct() {
        $clientId = SOUNDCLOUD_CLIENT_ID;
        $this->downloader = new SoundCloudDownloader($clientId);
    }
    
    /**
     * Kiểm tra API key
     */
    private function validateApiKey() {
        // LUÔN yêu cầu API key
        $apiKey = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
        
        if (empty($apiKey)) {
            return ['error' => 'Thiếu API key. Vui lòng thêm tham số api_key hoặc header X-API-Key', 'code' => 401];
        }
        
        // Lấy key từ database
        $keyData = getApiKeyByValue($apiKey);
        
        if (!$keyData) {
            return ['error' => 'API key không hợp lệ', 'code' => 401];
        }
        
        if ($keyData['status'] !== 'active') {
            return ['error' => 'API key đã bị vô hiệu hóa', 'code' => 403];
        }
        
        // Kiểm tra limit type (daily hoặc monthly)
        $limitType = $keyData['limit_type'];
        $today = date('Y-m-d');
        
        if ($limitType === 'daily') {
            // Reset count nếu sang ngày mới
            $lastUsedDate = $keyData['daily_reset_at'] ?? '';
            if ($lastUsedDate !== $today) {
                // Database sẽ tự động reset trong updateApiKeyUsage
            }
        }
        
        // Kiểm tra giới hạn (sau khi reset nếu cần)
        $currentKey = getApiKeyByValue($apiKey); // Lấy lại sau reset
        
        if ($currentKey['request_count'] >= $currentKey['request_limit']) {
            $limitText = $limitType === 'daily' ? 'requests/ngày' : 'requests/tháng';
            return ['error' => 'API key đã vượt quá giới hạn ' . $currentKey['request_limit'] . ' ' . $limitText, 'code' => 429];
        }
        
        // Tăng số lượng request trong database
        updateApiKeyUsage($apiKey);
        
        // Log request (optional)
        try {
            $action = $_GET['action'] ?? '';
            $query = $_GET['query'] ?? null;
            $trackId = $_GET['track_id'] ?? null;
            logApiRequest($apiKey, $action, $query, $trackId);
        } catch (Exception $e) {
            // Bỏ qua lỗi log
        }
        
        return true;
    }
    
    public function handleRequest() {
        // Bỏ qua kiểm tra API key nếu request từ website (referer check)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $host = $_SERVER['HTTP_HOST'] ?? '';
        
        // Kiểm tra xem request có từ chính website này không
        $isFromWebsite = (
            strpos($referer, 'index.php') !== false || 
            strpos($referer, $host . '/apiscl/') !== false ||
            strpos($referer, $host . '/apiscl') !== false ||
            strpos($referer, 'localhost/apiscl') !== false
        );
        
        // Chỉ kiểm tra API key nếu không phải từ website
        if (!$isFromWebsite) {
            $keyValidation = $this->validateApiKey();
            if (is_array($keyValidation) && isset($keyValidation['error'])) {
                http_response_code($keyValidation['code'] ?? 403);
                return $keyValidation;
            }
        }
        
        $action = $_GET['action'] ?? '';
        $query = $_GET['query'] ?? '';
        $trackId = $_GET['track_id'] ?? '';
        
        switch ($action) {
            case 'search':
                // Kiểm tra nếu có parameter play
                if (isset($_GET['play'])) {
                    return $this->playTrack($query);
                }
                return $this->searchTrack($query);
                
            case 'download':
                return $this->downloadTrack($trackId);
                
            case 'multiple':
                return $this->getMultipleTracks($query);
                
            case 'info':
                return $this->getTrackInfo($trackId);
                
            case 'play':
                return $this->playTrack($query);
                
            default:
                return ['error' => 'Action không hợp lệ. Các action hỗ trợ: search, download, multiple, info, play'];
        }
    }
    
    private function searchTrack($query) {
        if (empty($query)) {
            return ['error' => 'Vui lòng nhập tên bài hát (tham số: query)'];
        }
        
        return $this->downloader->getTrackByQuery($query);
    }
    
    private function downloadTrack($trackId) {
        if (empty($trackId)) {
            return ['error' => 'Thiếu track ID (tham số: track_id)'];
        }
        
        $result = $this->downloader->downloadTrackAsMP3($trackId);
        
        if (!$result) {
            return ['error' => 'Không thể tải xuống track'];
        }
        
        // Set headers để download file MP3
        header('Content-Type: ' . $result['content_type']);
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        header('Content-Length: ' . $result['size']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        
        // Output file MP3
        echo $result['data'];
        exit;
    }
    
    private function getMultipleTracks($query) {
        if (empty($query)) {
            return ['error' => 'Vui lòng nhập tên bài hát (tham số: query)'];
        }
        
        $limit = $_GET['limit'] ?? 5;
        return $this->downloader->getMultipleTracks($query, $limit);
    }
    
    private function getTrackInfo($trackId) {
        if (empty($trackId)) {
            return ['error' => 'Thiếu track ID (tham số: track_id)'];
        }
        
        $trackInfo = $this->downloader->getTrackInfo($trackId);
        
        if (!$trackInfo) {
            return ['error' => 'Không thể lấy thông tin track'];
        }
        
        return [
            'success' => true,
            'track' => [
                'id' => $trackInfo['id'],
                'title' => $trackInfo['title'],
                'artist' => $trackInfo['user']['username'],
                'duration' => $trackInfo['duration'],
                'permalink_url' => $trackInfo['permalink_url'],
                'artwork_url' => $trackInfo['artwork_url'],
                'downloadable' => $trackInfo['downloadable'] ?? false,
                'streamable' => $trackInfo['streamable'] ?? false
            ],
            'credits' => [
                'api' => 'SoundCloud Downloader API',
                'version' => '1.0.0',
                'developer' => 'Khánh Duy',
                'powered_by' => 'Khánh Duy',
                'github' => 'https://github.com/kzi207'
            ]
        ];
    }
    
    private function playTrack($query) {
        if (empty($query)) {
            return $this->showErrorPage('Vui lòng nhập tên bài hát (tham số: query)');
        }
        
        $result = $this->downloader->getTrackByQuery($query);
        
        if (isset($result['error'])) {
            return $this->showErrorPage($result['error']);
        }
        
        if (!$result['success']) {
            return $this->showErrorPage('Không thể tìm thấy bài hát');
        }
        
        $track = $result['track'];
        return $this->showPlayerPage($track);
    }
    
    private function showPlayerPage($track) {
        // Set content type to HTML
        header('Content-Type: text/html; charset=utf-8');
        
        $streamUrl = htmlspecialchars($track['stream_url']);
        
        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundCloud Player</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: Arial, sans-serif;
        }
        
        .audio-player {
            width: 100%;
            max-width: 600px;
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        audio {
            width: 100%;
            height: 50px;
            outline: none;
        }
        
        audio::-webkit-media-controls-panel {
            background-color: #f8f9fa;
        }
        
        audio::-webkit-media-controls-play-button {
            background-color: #007bff;
            border-radius: 50%;
        }
        
        audio::-webkit-media-controls-timeline {
            background-color: #e9ecef;
            border-radius: 5px;
        }
        
        audio::-webkit-media-controls-current-time-display,
        audio::-webkit-media-controls-time-remaining-display {
            color: #495057;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="audio-player">
        <audio controls autoplay>
            <source src="' . $streamUrl . '" type="audio/mpeg">
            Trình duyệt không hỗ trợ audio player.
        </audio>
    </div>
    
    <script>
        // Auto-play khi trang load
        document.addEventListener("DOMContentLoaded", function() {
            const audio = document.querySelector("audio");
            if (audio) {
                audio.play().catch(function(error) {
                    console.log("Auto-play bị chặn:", error);
                });
            }
        });
    </script>
</body>
</html>';
        
        exit;
    }
    
    private function showErrorPage($error) {
        header('Content-Type: text/html; charset=utf-8');
        
        echo '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi - SoundCloud Downloader API</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .error-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .error-icon {
            font-size: 4em;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 1.5em;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .error-message {
            color: #7f8c8d;
            margin-bottom: 30px;
            line-height: 1.6;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h1 class="error-title">Có lỗi xảy ra</h1>
        <p class="error-message">' . htmlspecialchars($error) . '</p>
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Quay lại trang chủ
        </a>
    </div>
</body>
</html>';
        
        exit;
    }
}

// Xử lý CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Thêm credit headers
header('X-API-Name: SoundCloud Downloader API');
header('X-API-Version: 1.0.0');
header('X-Developer: Khánh Duy');
header('X-Powered-By: Khánh Duy');

// Xử lý preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Xử lý API request
try {
    $api = new SoundCloudAPI();
    $result = $api->handleRequest();
    
    // Chỉ set JSON header nếu không phải download hoặc play action
    if ($_GET['action'] !== 'download' && $_GET['action'] !== 'play' && !isset($_GET['play'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'error' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
