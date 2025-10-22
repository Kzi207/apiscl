<?php
// test.php - File test API đơn giản

require_once 'config.php';
require_once 'SoundCloudDownloader.php';

echo "<h1>SoundCloud API Test</h1>";

try {
    $downloader = new SoundCloudDownloader(SOUNDCLOUD_CLIENT_ID);
    
    echo "<h2>1. Test tìm kiếm bài hát</h2>";
    $query = "test";
    $result = $downloader->searchTracks($query, 3);
    
    if ($result && !empty($result['collection'])) {
        echo "<p>✅ Tìm thấy " . count($result['collection']) . " kết quả:</p>";
        foreach ($result['collection'] as $track) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>" . htmlspecialchars($track['title']) . "</strong><br>";
            echo "Nghệ sĩ: " . htmlspecialchars($track['user']['username']) . "<br>";
            echo "ID: " . $track['id'] . "<br>";
            echo "Thời lượng: " . round($track['duration'] / 60000, 2) . " phút<br>";
            echo "</div>";
        }
    } else {
        echo "<p>❌ Không tìm thấy kết quả nào</p>";
    }
    
    echo "<h2>2. Test lấy thông tin track</h2>";
    if ($result && !empty($result['collection'])) {
        $trackId = $result['collection'][0]['id'];
        $trackInfo = $downloader->getTrackInfo($trackId);
        
        if ($trackInfo) {
            echo "<p>✅ Lấy thông tin track thành công:</p>";
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>" . htmlspecialchars($trackInfo['title']) . "</strong><br>";
            echo "Nghệ sĩ: " . htmlspecialchars($trackInfo['user']['username']) . "<br>";
            echo "Có thể tải xuống: " . ($trackInfo['downloadable'] ? 'Có' : 'Không') . "<br>";
            echo "Có thể phát: " . ($trackInfo['streamable'] ? 'Có' : 'Không') . "<br>";
            echo "</div>";
        } else {
            echo "<p>❌ Không thể lấy thông tin track</p>";
        }
    }
    
    echo "<h2>3. Test lấy stream URL</h2>";
    if ($result && !empty($result['collection'])) {
        $trackId = $result['collection'][0]['id'];
        $streamUrl = $downloader->getStreamUrl($trackId);
        
        if ($streamUrl) {
            echo "<p>✅ Lấy stream URL thành công:</p>";
            echo "<p><a href='" . htmlspecialchars($streamUrl) . "' target='_blank'>" . htmlspecialchars($streamUrl) . "</a></p>";
        } else {
            echo "<p>❌ Không thể lấy stream URL</p>";
        }
    }
    
    echo "<h2>4. Test API endpoints</h2>";
    echo "<p>Bạn có thể test các endpoint sau:</p>";
    echo "<ul>";
    echo "<li><a href='api.php?action=search&query=test' target='_blank'>api.php?action=search&query=test</a></li>";
    echo "<li><a href='api.php?action=multiple&query=test&limit=3' target='_blank'>api.php?action=multiple&query=test&limit=3</a></li>";
    if ($result && !empty($result['collection'])) {
        $trackId = $result['collection'][0]['id'];
        echo "<li><a href='api.php?action=info&track_id=" . $trackId . "' target='_blank'>api.php?action=info&track_id=" . $trackId . "</a></li>";
        echo "<li><a href='api.php?action=download&track_id=" . $trackId . "' target='_blank'>api.php?action=download&track_id=" . $trackId . "</a></li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Lỗi: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Quay lại giao diện chính</a></p>";
?>
