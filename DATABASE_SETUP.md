# Hướng dẫn Setup Database

## Bước 1: Khởi động XAMPP

1. Mở XAMPP Control Panel
2. Start **Apache**
3. Start **MySQL**

## Bước 2: Tạo Database tự động

### Option 1: Sử dụng script PHP (Khuyến nghị)

1. Truy cập: `http://localhost/apiscl/setup_database.php`
2. Script sẽ tự động:
   - Tạo database `soundcloud_api`
   - Tạo các bảng (users, api_keys, orders, request_logs)
   - Migrate dữ liệu từ JSON sang SQL (nếu có)
   - Hiển thị kết quả

### Option 2: Import thủ công

1. Mở phpMyAdmin: `http://localhost/phpmyadmin`
2. Click tab "SQL"
3. Copy toàn bộ nội dung file `database.sql`
4. Paste vào và click "Go"

## Bước 3: Cấu hình kết nối

Mở file `db_config.php` và kiểm tra:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Để trống cho XAMPP mặc định
define('DB_NAME', 'soundcloud_api');
```

## Bước 4: Kiểm tra

Sau khi setup xong, hệ thống sẽ sử dụng MySQL thay vì JSON files:

### Dữ liệu được lưu trong 4 bảng:

1. **users** - Tài khoản người dùng
   - user_id, email, password, name
   - created_at, updated_at

2. **api_keys** - API keys
   - key_value, user_id, name, email
   - request_limit, request_count
   - limit_type (daily/monthly)
   - status, created_at, last_used
   - daily_reset_at, expires_at

3. **orders** - Đơn hàng
   - order_id, user_id, api_key
   - plan, plan_name, price, request_limit
   - status, transaction_id
   - created_at, paid_at, expires_at

4. **request_logs** - Log các request (optional)
   - api_key, action, query, track_id
   - ip_address, user_agent
   - created_at

## Lợi ích của Database

### So với JSON files:

✅ **Performance**
- Truy vấn nhanh hơn với indexes
- Không cần load toàn bộ file
- Concurrent access tốt hơn

✅ **Bảo mật**
- Password được hash
- Foreign keys đảm bảo integrity
- Transaction support

✅ **Scalability**
- Xử lý được hàng triệu records
- Easy backup/restore
- Replication support

✅ **Tính năng**
- Auto reset daily limit
- Request logging
- Advanced queries
- Statistics

✅ **Quản lý**
- phpMyAdmin để quản lý trực quan
- Backup dễ dàng
- Migration tools

## Troubleshooting

### Lỗi kết nối database?
- Kiểm tra MySQL đã start trong XAMPP
- Kiểm tra username/password trong db_config.php
- Kiểm tra database đã được tạo

### Lỗi permission?
- Đảm bảo user MySQL có quyền CREATE DATABASE
- Đảm bảo user có quyền INSERT/UPDATE/DELETE

### Migrate từ JSON?
- File JSON cũ vẫn được giữ lại
- Script setup_database.php tự động migrate
- Kiểm tra dữ liệu sau khi migrate

## Backup Database

### Export:
```bash
mysqldump -u root soundcloud_api > backup.sql
```

### Import:
```bash
mysql -u root soundcloud_api < backup.sql
```

Hoặc sử dụng phpMyAdmin: Export/Import tab

## Notes

- Database sử dụng charset **utf8mb4** hỗ trợ đầy đủ Unicode
- Auto increment IDs cho tất cả bảng
- Indexes được tạo sẵn để tối ưu performance
- Foreign keys cascade delete để đảm bảo data integrity
