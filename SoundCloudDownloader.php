<?php
// SoundCloudDownloader.php - Class chính xử lý SoundCloud API

class SoundCloudDownloader {
    private $clientId;
    private $baseUrl = 'https://api-v2.soundcloud.com';
    private $timeout = 30;
    
    public function __construct($clientId) {
        $this->clientId = $clientId;
    }
    
    /**
     * Thực hiện HTTP request
     */
    private function makeRequest($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'Accept: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return json_decode($response, true);
        } else {
            error_log("SoundCloud API Error: HTTP $httpCode - $error");
            return null;
        }
    }
    
    /**
     * Tìm kiếm track theo query
     */
    public function searchTracks($query, $limit = 10) {
        $url = $this->baseUrl . '/search/tracks?' . http_build_query([
            'q' => $query,
            'client_id' => $this->clientId,
            'limit' => $limit,
            'offset' => 0
        ]);
        
        return $this->makeRequest($url);
    }
    
    /**
     * Lấy thông tin chi tiết track
     */
    public function getTrackInfo($trackId) {
        $url = $this->baseUrl . '/tracks/' . $trackId . '?' . http_build_query([
            'client_id' => $this->clientId
        ]);
        
        return $this->makeRequest($url);
    }
    
    /**
     * Lấy stream URL của track
     */
    public function getStreamUrl($trackId) {
        $trackInfo = $this->getTrackInfo($trackId);
        
        if (!$trackInfo || !isset($trackInfo['media']['transcodings'])) {
            return null;
        }
        
        // Tìm progressive stream (MP3)
        foreach ($trackInfo['media']['transcodings'] as $transcoding) {
            if ($transcoding['format']['protocol'] === 'progressive') {
                $streamUrl = $transcoding['url'] . '?' . http_build_query([
                    'client_id' => $this->clientId
                ]);
                
                $streamInfo = $this->makeRequest($streamUrl);
                return $streamInfo['url'] ?? null;
            }
        }
        
        return null;
    }
    
    /**
     * Download track và trả về file MP3
     */
    public function downloadTrackAsMP3($trackId, $filename = null) {
        $streamUrl = $this->getStreamUrl($trackId);
        
        if (!$streamUrl) {
            return false;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $streamUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_BUFFERSIZE => 128000,
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $audioData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if ($httpCode === 200 && $audioData) {
            // Lấy thông tin track để tạo tên file
            $trackInfo = $this->getTrackInfo($trackId);
            if (!$filename && $trackInfo) {
                $safeTitle = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $trackInfo['title']);
                $safeArtist = preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $trackInfo['user']['username']);
                $filename = $safeArtist . ' - ' . $safeTitle . '.mp3';
            }
            
            return [
                'success' => true,
                'data' => $audioData,
                'filename' => $filename ?: 'track_' . $trackId . '.mp3',
                'content_type' => $contentType ?: 'audio/mpeg',
                'size' => strlen($audioData)
            ];
        }
        
        return false;
    }
    
    /**
     * Tìm kiếm và lấy thông tin track theo query
     */
    public function getTrackByQuery($query) {
        // Tìm kiếm track
        $searchResults = $this->searchTracks($query, 1);
        
        if (!$searchResults || empty($searchResults['collection'])) {
            return ['error' => 'Không tìm thấy bài hát'];
        }
        
        $track = $searchResults['collection'][0];
        $trackId = $track['id'];
        
        // Lấy stream URL
        $streamUrl = $this->getStreamUrl($trackId);
        
        if (!$streamUrl) {
            return ['error' => 'Không thể lấy stream URL của bài hát'];
        }
        
        return [
            'success' => true,
            'track' => [
                'id' => $trackId,
                'title' => $track['title'],
                'artist' => $track['user']['username'],
                'duration' => $track['duration'],
                'stream_url' => $streamUrl,
                'permalink_url' => $track['permalink_url'],
                'artwork_url' => $track['artwork_url'],
                'downloadable' => $track['downloadable'] ?? false
            ],
            'credits' => [
                'api' => 'SoundCloud Downloader API',
                'version' => '1.0.0',
                'developer' => 'Khánh Duy',
                'powered_by' => 'Khánh Duy',
                'github' => 'https://github.com/soundcloud-downloader/api'
            ]
        ];
    }
    
    /**
     * Lấy nhiều kết quả tìm kiếm
     */
    public function getMultipleTracks($query, $limit = 5) {
        $searchResults = $this->searchTracks($query, $limit);
        
        if (!$searchResults || empty($searchResults['collection'])) {
            return ['error' => 'Không tìm thấy bài hát nào'];
        }
        
        $tracks = [];
        foreach ($searchResults['collection'] as $track) {
            $tracks[] = [
                'id' => $track['id'],
                'title' => $track['title'],
                'artist' => $track['user']['username'],
                'duration' => $track['duration'],
                'permalink_url' => $track['permalink_url'],
                'artwork_url' => $track['artwork_url']
            ];
        }
        
        return [
            'success' => true,
            'tracks' => $tracks,
            'credits' => [
                'api' => 'SoundCloud Downloader API',
                'version' => '1.0.0',
                'developer' => 'SoundCloud Downloader Team',
                'powered_by' => 'SoundCloud API v2',
                'github' => 'https://github.com/soundcloud-downloader/api'
            ]
        ];
    }
}
?>
