# SoundCloud Downloader API

API đơn giản để tìm kiếm và tải xuống bài hát từ SoundCloud.

## Cài đặt

1. Đặt các file vào thư mục web server (XAMPP, WAMP, etc.)
2. Đảm bảo PHP có hỗ trợ cURL
3. Không cần cài đặt thêm thư viện nào

## Cách sử dụng

### 1. Giao diện Web
Truy cập `index.php` để sử dụng giao diện web đơn giản.

### 2. API Endpoints

#### Tìm kiếm bài hát
```
GET /api.php?action=search&query=tên_bài_hát
```

**Ví dụ:**
```
http://localhost/apiscl/api.php?action=search&query=test
```

**Response:**
```json
{
    "success": true,
    "track": {
        "id": "498655485",
        "title": "Test And Recognize",
        "artist": "christophurb",
        "duration": 158400,
        "stream_url": "https://cf-media.sndcdn.com/...",
        "permalink_url": "https://soundcloud.com/...",
        "artwork_url": "https://i1.sndcdn.com/...",
        "downloadable": false
    }
}
```

#### Tải xuống MP3
```
GET /api.php?action=download&track_id=ID_TRACK
```

**Ví dụ:**
```
http://localhost/apiscl/api.php?action=download&track_id=498655485
```

**Response:** File MP3 được tải xuống trực tiếp

#### Tìm kiếm nhiều bài hát
```
GET /api.php?action=multiple&query=tên_bài_hát&limit=5
```

**Ví dụ:**
```
http://localhost/apiscl/api.php?action=multiple&query=test&limit=3
```

#### Lấy thông tin chi tiết
```
GET /api.php?action=info&track_id=ID_TRACK
```

**Ví dụ:**
```
http://localhost/apiscl/api.php?action=info&track_id=498655485
```

### 3. Test API
Truy cập `test.php` để test các chức năng API.

## Cấu trúc File

- `config.php` - Cấu hình SoundCloud API
- `SoundCloudDownloader.php` - Class chính xử lý API
- `api.php` - Endpoint API chính
- `index.php` - Giao diện web
- `test.php` - File test API

## Lưu ý

- API sử dụng SoundCloud Client ID công khai
- Một số track có thể không cho phép tải xuống
- Stream URL có thời hạn sử dụng
- Chỉ hỗ trợ các track có thể phát (streamable)

## Demo

1. Mở `index.php` trong trình duyệt
2. Nhập tên bài hát và nhấn "Tìm kiếm"
3. Nghe thử và tải xuống MP3
4. Hoặc sử dụng trực tiếp API endpoints
