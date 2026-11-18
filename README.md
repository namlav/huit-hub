# HUIT Schedule Hub

[![PHP CI](https://github.com/namlav/huit-hub/actions/workflows/php-ci.yml/badge.svg)](https://github.com/namlav/huit-hub/actions/workflows/php-ci.yml)
![PHP](https://img.shields.io/badge/PHP-%3E%3D%208.0-777bb4)
![MySQL](https://img.shields.io/badge/MySQL-8.x-4479a1)
![License](https://img.shields.io/badge/license-MIT-green)

Hệ thống quản lý và phân lịch giảng dạy đa cơ sở dành cho HUIT. Bài toán đặt ra: một giảng viên có thể dạy ở nhiều cơ sở trong cùng một ngày, nên chỉ kiểm tra trùng lịch thôi là chưa đủ — người xếp lịch còn phải tránh khoảng trống chết và hạn chế việc di chuyển giữa các quận.

HUIT Schedule Hub giải quyết việc đó bằng ba màn hình: bảng điều khiển có bộ lọc và biểu đồ, công cụ phân lịch gợi ý giảng viên theo mức tối ưu di chuyển, và cổng tra cứu thời khóa biểu dành riêng cho giảng viên.

## Tính năng

### Tổng quan lịch giảng dạy
- Liệt kê toàn bộ lịch dạy kèm giảng viên, chuyên môn, cơ sở, thứ, ca/tiết và phòng học.
- Lọc theo cơ sở, giảng viên và thứ bằng phương thức `GET`.
- Biểu đồ cột số ca dạy theo cơ sở (Chart.js) và thanh tiến trình khối lượng giảng dạy của từng giảng viên (Bootstrap 5).
- Sửa nhanh qua `assign.php?id=...`, xóa nhanh ngay trên bảng.

### Phân lịch thông minh
- Lọc giảng viên theo đúng nhóm chuyên môn của học phần.
- Loại giảng viên đã có lịch trùng `thứ + ca` (đồng thời chặn ở tầng database bằng unique key).
- Cảnh báo trùng phòng theo `cơ sở + phòng + thứ + ca`.
- Xếp hạng giảng viên theo khả năng tối ưu di chuyển:

| Mức | Điều kiện | Ý nghĩa |
| --- | --- | --- |
| 🟢 Tối ưu cao | Có ca liền trước/sau trong cùng ngày tại **cùng cơ sở** | Không mất thời gian di chuyển |
| 🔵 Bình thường | Trống lịch cả ngày | Thoải mái nhận lớp |
| 🟡 Cần cân nhắc | Có ca tại **cơ sở khác** trong ngày, hoặc lịch bị cách quãng | Phải di chuyển / có khoảng trống chết |

- Danh sách gợi ý được tải bằng AJAX từ `public/api_suggest.php`; mỗi thẻ hiển thị lý do xếp hạng và các ca giảng viên đã dạy trong ngày.

### Cổng thông tin giảng viên
- Đăng nhập nhanh bằng email hoặc chọn tên từ danh sách (phiên `$_SESSION`).
- Thời khóa biểu dạng lưới: cột Thứ 2 → Thứ 7, hàng Ca 1 → Ca 4.
- Mỗi ô hiển thị môn học, phòng và badge màu theo cơ sở.
- Tóm tắt tổng số ca trong tuần và danh sách cơ sở cần đến kèm địa chỉ.

## Yêu cầu hệ thống

- PHP >= 8.0 (khuyến nghị 8.3) với extension `pdo_mysql`, `mbstring`.
- MySQL 8.x (MariaDB tương thích cũng chạy được).
- Composer 2.x.
- Laragon (hoặc Apache/Nginx + PHP-FPM) cho môi trường local.

## Cài đặt

### 1. Lấy mã nguồn và cài phụ thuộc

```bash
git clone https://github.com/namlav/huit-hub.git
cd huit-hub
composer install
```

### 2. Cấu hình biến môi trường

```bash
cp .env.example .env
```

Mở `.env` và chỉnh thông số MySQL:

```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=huit_db
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Tạo cơ sở dữ liệu

Cách nhanh nhất — seeder tự tạo database, bảng và dữ liệu mẫu:

```bash
php database/seeder.php
```

Kết quả mong đợi: `4 branches, 18 teachers, 36 schedules`.

Hoặc import file SQL có sẵn (dữ liệu mẫu giống hệt):

```bash
mysql -u root -p -e "CREATE DATABASE huit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p huit_db < database/huit_db.sql
```

### 4. Trỏ web root và truy cập

Đặt DocumentRoot của virtual host vào thư mục `public/`. Nếu để DocumentRoot ở thư mục gốc dự án, file `.htaccess` đi kèm sẽ tự chuyển request vào `public/` và chặn truy cập `.env`, `vendor/`, `config/`, `database/`.

Với Laragon:

1. Reload Laragon để nhận virtual host.
2. Mở `http://huit-hub.test` (hoặc thêm cổng Apache đang dùng, ví dụ `http://huit-hub.test:8080`).

Không dùng virtual host? Chạy server tích hợp:

```bash
composer serve
# http://localhost:8000
```

## Sử dụng

| Trang | Đường dẫn | Mục đích |
| --- | --- | --- |
| Tổng quan | `index.php` | Lọc, thống kê, sửa/xóa lịch |
| Phân lịch | `assign.php` | Thêm/sửa lịch và xem gợi ý giảng viên |
| API gợi ý | `api_suggest.php` | JSON cho AJAX (`expertise`, `branch_id`, `day_of_week`, `shift_or_periods`, `room`) |
| Cổng giảng viên | `teacher_portal.php` | Đăng nhập và xem thời khóa biểu cá nhân |

Quy trình phân lịch: nhập môn học → chọn chuyên môn, cơ sở, phòng, thứ, ca → bấm **Gợi ý GV** → chọn một giảng viên trong danh sách. Hệ thống kiểm tra lại trùng lịch và trùng phòng ở phía server trước khi lưu.

Ca học được chuẩn hóa thành bốn giá trị cố định để việc so trùng không phụ thuộc vào cách gõ:

- `Sáng - Ca 1 (Tiết 1-3)`
- `Sáng - Ca 2 (Tiết 4-6)`
- `Chiều - Ca 3 (Tiết 7-9)`
- `Chiều - Ca 4 (Tiết 10-12)`

## Cấu trúc dự án

```text
huit-hub/
├── .github/workflows/php-ci.yml   # CI: composer validate + php -l toàn bộ file
├── config/
│   ├── constants.php              # Thứ, ca chuẩn, chuyên môn, màu cơ sở
│   ├── database.php               # Dotenv + PDO (prepared statements)
│   └── helpers.php                # CSRF, flash, truy vấn dùng chung, thuật toán gợi ý
├── database/
│   ├── huit_db.sql                # Dump schema + dữ liệu mẫu
│   └── seeder.php                 # Tạo DB/bảng và seed bằng Faker vi_VN
├── partials/                      # Header/footer Bootstrap 5 dùng chung
├── public/
│   ├── index.php                  # Dashboard, bộ lọc, biểu đồ, CRUD
│   ├── assign.php                 # Form phân lịch + gợi ý
│   ├── teacher_portal.php         # Đăng nhập và lưới thời khóa biểu
│   └── api_suggest.php            # Endpoint JSON cho AJAX
├── .env.example
├── .htaccess                      # Đưa request vào public/ khi docroot ở root
├── composer.json
├── LICENSE
└── README.md
```

## Thiết kế cơ sở dữ liệu

- `branches`: `id`, `branch_name`, `address`.
- `teachers`: `id`, `full_name`, `email` (unique), `phone`, `expertise`.
- `schedules`: `id`, `teacher_id`, `branch_id`, `subject_name`, `room`, `day_of_week` (2–7), `shift_or_periods`.
  - Unique `(teacher_id, day_of_week, shift_or_periods)` chống trùng lịch.
  - Unique `(branch_id, room, day_of_week, shift_or_periods)` chống trùng phòng.
  - Khóa ngoại `ON DELETE CASCADE` để xóa giảng viên/cơ sở không để lại lịch mồ côi.

## Bảo mật

- Mọi truy vấn dùng PDO prepared statements.
- Toàn bộ output thoát HTML qua `htmlspecialchars` (`e()`).
- Form POST có CSRF token; session cookie đặt `HttpOnly` + `SameSite=Lax`.
- `.env` và `vendor/` nằm trong `.gitignore`; `.htaccess` chặn truy cập trực tiếp các thư mục nguồn.
- Server kiểm tra lại chuyên môn và trùng ca trước khi ghi, không tin dữ liệu từ client.

## Kiểm tra chất lượng

```bash
composer validate
find . -path ./vendor -prune -o -name "*.php" -print0 | xargs -0 -n1 -P4 php -l
php tests/algorithm_test.php
```

`tests/algorithm_test.php` kiểm tra thuật toán gợi ý (liền ca, khoảng trống chết, khác cơ sở, trùng ca, trùng phòng) bằng dữ liệu tạm trong transaction và luôn rollback, nên không làm bẩn database đã seed.

Workflow `.github/workflows/php-ci.yml` chạy hai bước trên cho mỗi lần `push` và `pull_request`.

## Giấy phép

Phát hành theo [MIT License](LICENSE) © 2026 namlav.
