<?php
// config.php - File cấu hình SoundCloud API

// SoundCloud API Configuration
define('SOUNDCLOUD_CLIENT_ID', 'MHDG7vIKasWstY0FaB07rK5WUoUjjCDC'); // SoundCloud Client ID
define('SOUNDCLOUD_CLIENT_SECRET', ''); // Không cần thiết cho public API
define('SOUNDCLOUD_REDIRECT_URI', ''); // Không cần thiết cho public API

// Application Configuration
define('API_RATE_LIMIT', 100); // Số request tối đa mỗi giờ
define('REQUEST_TIMEOUT', 30); // Timeout cho mỗi request (giây)
define('MAX_RESULTS', 10); // Số kết quả tối đa khi tìm kiếm

// Security Configuration
define('ALLOWED_DOMAINS', ['*']); // CORS allowed domains
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB max file size

// Return configuration array
return [
    'soundcloud' => [
        'client_id' => SOUNDCLOUD_CLIENT_ID,
        'client_secret' => SOUNDCLOUD_CLIENT_SECRET,
        'redirect_uri' => SOUNDCLOUD_REDIRECT_URI,
        'base_url' => 'https://api-v2.soundcloud.com'
    ],
    'app' => [
        'rate_limit' => API_RATE_LIMIT,
        'timeout' => REQUEST_TIMEOUT,
        'max_results' => MAX_RESULTS
    ]
];
?>
