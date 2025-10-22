<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundCloud Downloader API</title>
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
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95); 
            padding: 40px; 
            border-radius: 20px; 
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        h1 { 
            color: #2c3e50; 
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 1.1em;
            margin-bottom: 30px;
        }
        
        .search-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .search-box { 
            display: flex; 
            gap: 15px; 
            margin-bottom: 20px;
        }
        
        input[type="text"] { 
            flex: 1; 
            padding: 15px 20px; 
            border: 2px solid #e9ecef; 
            border-radius: 10px; 
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-btn { 
            padding: 15px 30px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        
        .search-btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .loading { 
            display: none; 
            color: #667eea; 
            text-align: center; 
            padding: 30px;
            font-size: 1.2em;
        }
        
        .loading i {
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .result { 
            margin-top: 30px; 
            padding: 30px; 
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%); 
            border-radius: 15px; 
            border-left: 5px solid #667eea;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .track-info { 
            margin-bottom: 25px; 
        }
        
        .track-info h3 { 
            color: #2c3e50; 
            margin-bottom: 15px;
            font-size: 1.5em;
        }
        
        .track-info p { 
            margin: 8px 0; 
            color: #7f8c8d;
            font-size: 1.1em;
        }
        
        .audio-player { 
            width: 100%; 
            margin: 20px 0; 
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }
        
        .download-btn { 
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%); 
            padding: 12px 25px; 
            text-decoration: none; 
            color: white; 
            border-radius: 8px; 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .download-btn:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .action-btn {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .copy-btn {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }
        
        .info-btn {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 193, 7, 0.3);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .error { 
            color: #dc3545; 
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%); 
            padding: 20px; 
            border-radius: 10px; 
            border-left: 5px solid #dc3545;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.1);
        }
        
        .success { 
            color: #155724; 
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%); 
            padding: 20px; 
            border-radius: 10px; 
            border-left: 5px solid #28a745;
            box-shadow: 0 2px 10px rgba(40, 167, 69, 0.1);
        }
        
        .guide-section {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .guide-section h3 {
            color: #1976d2;
            margin-bottom: 20px;
            font-size: 1.4em;
        }
        
        .guide-step {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid #1976d2;
        }
        
        .guide-step h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        
        .guide-step p {
            color: #424242;
            line-height: 1.6;
        }
        
        .api-info {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            padding: 30px;
            border-radius: 15px;
            margin-top: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .api-info h3 {
            color: #7b1fa2;
            margin-bottom: 20px;
            font-size: 1.4em;
        }
        
        .api-endpoint {
            background: white;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            border-left: 4px solid #7b1fa2;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            word-break: break-all;
        }
        
        .endpoint-description {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
            font-style: italic;
        }
        
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .feature-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #667eea;
        }
        
        .feature-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .feature-desc {
            color: #7f8c8d;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .search-box {
                flex-direction: column;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-music"></i> SoundCloud Downloader API</h1>
            <p class="subtitle">Tìm kiếm và tải xuống bài hát từ SoundCloud một cách dễ dàng</p>
        </div>
        
        <div class="search-section">
            <h3><i class="fas fa-search"></i> Tìm kiếm bài hát</h3>
            <div class="search-box">
                <input type="text" id="trackQuery" placeholder="Nhập tên bài hát, nghệ sĩ hoặc từ khóa...">
                <button class="search-btn" onclick="searchTrack()">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </div>
            
            <div id="loading" class="loading">
                <i class="fas fa-spinner"></i> Đang tìm kiếm bài hát...
            </div>
            
            <div id="result" class="result" style="display: none;"></div>
        </div>
        
        <div class="features">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-search"></i></div>
                <div class="feature-title">Tìm kiếm thông minh</div>
                <div class="feature-desc">Tìm kiếm bài hát từ SoundCloud với độ chính xác cao</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-download"></i></div>
                <div class="feature-title">Tải xuống MP3</div>
                <div class="feature-desc">Tải xuống file MP3 chất lượng cao trực tiếp</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-play"></i></div>
                <div class="feature-title">Nghe trước</div>
                <div class="feature-desc">Nghe thử bài hát trước khi tải xuống</div>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-code"></i></div>
                <div class="feature-title">API đơn giản</div>
                <div class="feature-desc">API RESTful dễ sử dụng cho developers</div>
            </div>
        </div>
        
        <div class="guide-section">
            <h3><i class="fas fa-book"></i> Hướng dẫn sử dụng</h3>
            
            <div class="guide-step">
                <h4><i class="fas fa-mouse-pointer"></i> Bước 1: Tìm kiếm bài hát</h4>
                <p>Nhập tên bài hát, nghệ sĩ hoặc từ khóa vào ô tìm kiếm. Bạn có thể tìm kiếm bằng tiếng Việt hoặc tiếng Anh. Ví dụ: "Shape of You", "Đen Vâu", "EDM"</p>
            </div>
            
            <div class="guide-step">
                <h4><i class="fas fa-headphones"></i> Bước 2: Nghe thử</h4>
                <p>Sau khi tìm thấy bài hát, bạn có thể nghe thử bằng audio player. Điều này giúp bạn xác nhận đúng bài hát muốn tải xuống.</p>
            </div>
            
            <div class="guide-step">
                <h4><i class="fas fa-download"></i> Bước 3: Tải xuống</h4>
                <p>Nhấn nút "Tải xuống MP3" để tải file về máy. File sẽ được tải xuống với tên tự động dựa trên tên bài hát và nghệ sĩ.</p>
            </div>
            
            <div class="guide-step">
                <h4><i class="fas fa-info-circle"></i> Bước 4: Thông tin chi tiết</h4>
                <p>Nhấn "Thông tin chi tiết" để xem thêm thông tin về bài hát như thời lượng, khả năng tải xuống, và link gốc trên SoundCloud.</p>
            </div>
        </div>
        
        <div class="api-info">
            <h3><i class="fas fa-code"></i> API Endpoints cho Developers</h3>
            <p style="margin-bottom: 20px; color: #666;">Sử dụng các endpoint sau để tích hợp vào ứng dụng của bạn:</p>
            
            <div class="api-endpoint">
                GET /api.php?action=search&query=tên_bài_hát
                <div class="endpoint-description">Tìm kiếm bài hát theo từ khóa và trả về thông tin bài hát đầu tiên</div>
            </div>
            
            <div class="api-endpoint">
                GET /api.php?action=download&track_id=ID_TRACK
                <div class="endpoint-description">Tải xuống file MP3 trực tiếp từ SoundCloud</div>
            </div>
            
            <div class="api-endpoint">
                GET /api.php?action=multiple&query=tên_bài_hát&limit=5
                <div class="endpoint-description">Tìm kiếm nhiều bài hát cùng lúc (mặc định limit=5)</div>
            </div>
            
            <div class="api-endpoint">
                GET /api.php?action=info&track_id=ID_TRACK
                <div class="endpoint-description">Lấy thông tin chi tiết của một bài hát cụ thể</div>
            </div>
            
            <div class="api-endpoint">
                GET /api.php?action=search&query=tên_bài_hát&play
                <div class="endpoint-description">Tìm kiếm và phát trực tiếp bài hát trên web player</div>
            </div>
            
            <div class="api-endpoint">
                GET /api.php?action=play&query=tên_bài_hát
                <div class="endpoint-description">Phát trực tiếp bài hát trên web player (tương tự search&play)</div>
            </div>
            
            <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px; border-left: 4px solid #7b1fa2;">
                <h4 style="color: #7b1fa2; margin-bottom: 10px;"><i class="fas fa-lightbulb"></i> Ví dụ sử dụng:</h4>
                <p style="color: #666; margin-bottom: 10px;">Tìm kiếm bài hát "Shape of You":</p>
                <code style="background: #f8f9fa; padding: 10px; border-radius: 5px; display: block; word-break: break-all; margin-bottom: 10px;">
                    http://localhost/apiscl/api.php?action=search&query=Shape%20of%20You
                </code>
                <p style="color: #666; margin-bottom: 10px;">Phát trực tiếp bài hát:</p>
                <code style="background: #f8f9fa; padding: 10px; border-radius: 5px; display: block; word-break: break-all;">
                    http://localhost/apiscl/api.php?action=search&query=Shape%20of%20You&play
                </code>
            </div>
        </div>
        
        <div class="credit-section">
            <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; border-radius: 15px; margin-top: 30px;">
                <h3 style="margin-bottom: 15px; color: #ecf0f1;"><i class="fas fa-heart"></i> Credits</h3>
                <p style="margin-bottom: 10px; font-size: 1.1em;">Developed with ❤️ by <strong>SoundCloud Downloader Team</strong></p>
                <p style="margin-bottom: 15px; color: #bdc3c7;">Powered by SoundCloud API v2</p>
                <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-code"></i>
                        <span>PHP 7.4+</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-cloud"></i>
                        <span>SoundCloud API</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-palette"></i>
                        <span>Font Awesome</span>
                    </div>
                </div>
                <p style="margin-top: 15px; font-size: 0.9em; color: #95a5a6;">
                    © 2024 SoundCloud Downloader API. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        async function searchTrack() {
            const query = document.getElementById('trackQuery').value.trim();
            const resultDiv = document.getElementById('result');
            const loadingDiv = document.getElementById('loading');
            
            if (!query) {
                alert('Vui lòng nhập tên bài hát');
                return;
            }
            
            loadingDiv.style.display = 'block';
            resultDiv.style.display = 'none';
            resultDiv.innerHTML = '';
            
            try {
                const response = await fetch(`api.php?action=search&query=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                loadingDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                
                if (data.error) {
                    resultDiv.innerHTML = `<div class="error"><i class="fas fa-exclamation-triangle"></i> Lỗi: ${data.error}</div>`;
                } else if (data.success) {
                    const track = data.track;
                    resultDiv.innerHTML = `
                        <div class="track-info">
                            <h3><i class="fas fa-music"></i> ${track.title}</h3>
                            <p><i class="fas fa-user"></i> Nghệ sĩ: ${track.artist}</p>
                            <p><i class="fas fa-clock"></i> Thời lượng: ${Math.round(track.duration / 60000)} phút</p>
                            
                            <audio controls class="audio-player">
                                <source src="${track.stream_url}" type="audio/mpeg">
                                Trình duyệt không hỗ trợ audio player.
                            </audio>
                            
                            <div class="action-buttons">
                                <a href="api.php?action=download&track_id=${track.id}" 
                                   class="download-btn" target="_blank">
                                   <i class="fas fa-download"></i> Tải xuống MP3
                                </a>
                                
                                <button class="action-btn copy-btn" onclick="copyStreamUrl('${track.stream_url}')">
                                    <i class="fas fa-copy"></i> Copy Stream URL
                                </button>
                                
                                <button class="action-btn info-btn" onclick="getTrackInfo('${track.id}')">
                                    <i class="fas fa-info-circle"></i> Thông tin chi tiết
                                </button>
                            </div>
                        </div>
                    `;
                }
            } catch (error) {
                loadingDiv.style.display = 'none';
                resultDiv.style.display = 'block';
                resultDiv.innerHTML = `<div class="error"><i class="fas fa-wifi"></i> Lỗi kết nối: ${error.message}</div>`;
            }
        }
        
        async function getTrackInfo(trackId) {
            try {
                const response = await fetch(`api.php?action=info&track_id=${trackId}`);
                const data = await response.json();
                
                if (data.success) {
                    const track = data.track;
                    alert(`Thông tin chi tiết:\n\nTên: ${track.title}\nNghệ sĩ: ${track.artist}\nThời lượng: ${Math.round(track.duration / 60000)} phút\nCó thể tải xuống: ${track.downloadable ? 'Có' : 'Không'}\nCó thể phát: ${track.streamable ? 'Có' : 'Không'}`);
                } else {
                    alert('Lỗi: ' + data.error);
                }
            } catch (error) {
                alert('Lỗi: ' + error.message);
            }
        }
        
        function copyStreamUrl(url) {
            navigator.clipboard.writeText(url).then(() => {
                // Tạo thông báo đẹp thay vì alert
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
                    animation: slideIn 0.3s ease;
                `;
                notification.innerHTML = '<i class="fas fa-check"></i> Đã copy stream URL!';
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease';
                    setTimeout(() => notification.remove(), 300);
                }, 2000);
            });
        }
        
        // Thêm CSS animation cho notification
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        // Cho phép tìm kiếm bằng Enter
        document.getElementById('trackQuery').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchTrack();
            }
        });
    </script>
</body>
</html>
