# Bếp Nhà Shop — Laravel 12

Website đặt món ăn viết bằng **Laravel 12 (PHP 8.2+)**, mô hình **MVC**, cơ sở dữ liệu **MySQL**.

## 1. Chuẩn bị (làm 1 lần)

1. **XAMPP**: bật **Apache** và **MySQL** trong XAMPP Control Panel.
2. **Bật 2 extension PHP**: mở `C:\xampp\php\php.ini`, tìm và xoá dấu `;` ở đầu 2 dòng sau, rồi lưu file:
   ```
   extension=zip
   extension=fileinfo
   ```
   (`zip` để Composer tải thư viện, `fileinfo` để kiểm tra file ảnh khi upload.)
3. **Composer**: tải tại https://getcomposer.org/Composer-Setup.exe. Khi cài, chọn `C:\xampp\php\php.exe`.
   Mở **CMD mới** và gõ `composer -V` để kiểm tra.
4. **Tạo database**: vào http://localhost/phpmyadmin → **New** → tên `bepnha_laravel`, collation `utf8mb4_unicode_ci` → **Create**.

## 2. Cài đặt

Giải nén `bepnha-laravel.zip` (ví dụ ra `D:\bepnha-laravel`), mở thư mục và **nháy đúp `setup.bat`**.

Script tự làm 4 việc: cài thư viện → tạo `APP_KEY` → tạo bảng + dữ liệu mẫu → liên kết thư mục ảnh.

> MySQL có mật khẩu (ví dụ dùng MySQL Workbench thay vì XAMPP)? Mở file `.env`, sửa dòng `DB_PASSWORD=` thành mật khẩu của bạn rồi chạy lại `setup.bat`.

## 3. Chạy web

Mở CMD trong thư mục dự án (gõ `cmd` vào thanh địa chỉ của File Explorer rồi Enter):

```
php artisan serve
```

Mở http://127.0.0.1:8000

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@bepnha.vn | 123456 |
| Khách | an@gmail.com | 123456 |
| Khách | binh@gmail.com | 123456 |
| Khách | cuong@gmail.com | 123456 |

Muốn làm mới dữ liệu về ban đầu: `php artisan migrate:fresh --seed`

## 4. Chức năng

**Khách hàng**
- Trang chủ: hero có số liệu thật, danh mục dạng thẻ có icon, món nổi bật, "Vì sao chọn Bếp Nhà", "Đặt món 3 bước", món mới, banner kêu gọi.
- Thực đơn: lọc theo danh mục, **tìm kiếm tức thì khi đang gõ** (AJAX), sắp xếp theo giá / đánh giá, phân trang.
- Chi tiết món: điểm đánh giá, tồn kho, chọn số lượng, danh sách đánh giá, món cùng loại.
- Giỏ hàng **AJAX**: bấm "+" thêm món không tải lại trang, số trên icon giỏ nhảy lên, có toast thông báo; trong giỏ bấm +/− là tự lưu và tính lại tiền.
- Đặt hàng COD, xem lịch sử đơn, theo dõi đơn bằng thanh tiến trình, **tự huỷ đơn** khi còn "Chờ xác nhận" (hàng được hoàn kho).
- **Đánh giá món** 1–5 sao + nhận xét (chỉ khi đã nhận món đó; sửa được đánh giá của mình).
- **Tài khoản**: sửa họ tên / SĐT / địa chỉ, đổi mật khẩu, thống kê số đơn, số tiền đã chi.
- Đăng nhập / đăng ký giao diện chia đôi, nút điền nhanh tài khoản demo.

**Quản trị (`/admin`)**
- Tổng quan: 5 thẻ số liệu, **biểu đồ doanh thu + số đơn 7 ngày** (Chart.js), **top 5 món bán chạy**, đơn mới, món sắp hết.
- Đơn hàng: lọc theo trạng thái, **tìm theo mã đơn / SĐT / tên khách**, đổi trạng thái (huỷ thì hoàn kho).
- CRUD món ăn có upload ảnh; CRUD danh mục có icon emoji; danh sách khách hàng.

Dữ liệu mẫu có sẵn 12 đơn hàng trong 7 ngày gần nhất và 8 đánh giá, nên biểu đồ và phần đánh giá có dữ liệu ngay. Biểu đồ cần Internet để tải Chart.js.

### Ảnh món ăn

- 15 món mẫu dùng ảnh thật từ **Pexels** (https://www.pexels.com, giấy phép miễn phí, không bắt buộc ghi công).
- Khi chạy `setup.bat`, bước 3 **tự tải ảnh về** thư mục `storage/app/public/products/` (lần đầu khoảng 30 giây, cần Internet). Sau đó demo **không cần mạng** vẫn hiện ảnh.
- Nếu máy không tải được (mất mạng, hoặc PHP của XAMPP báo lỗi SSL), web sẽ **dùng thẳng link ảnh Pexels**. Lúc đó cần mạng mới thấy ảnh, còn không có mạng thì hiện icon món.
- Muốn đổi ảnh một món: vào **Admin → Món ăn → Sửa → chọn ảnh** rồi lưu.

### Chức năng mới: thông báo, thanh toán online, chat CSKH

**1. Thông báo trạng thái đơn**
- Admin xử lý đơn theo từng bước bằng 1 nút: **✅ Nhận đơn → 👩‍🍳 Chuyển cho bếp → 🛵 Giao cho shipper** (nhập tên + SĐT shipper) **→ 🏁 Xác nhận đã giao xong**.
- Mỗi lần bấm, khách nhận **thông báo** ở chuông 🔔 trên thanh menu. Web tự hỏi server mỗi 10 giây nên thông báo hiện lên kèm toast mà không cần tải lại trang. Nếu khách đang mở đúng đơn đó, trang tự cập nhật.
- Trang đơn hàng của khách có thanh tiến trình, thông tin shipper (bấm gọi được) và **lịch sử đơn** ghi lại giờ của từng bước.
- Demo: mở 2 trình duyệt (hoặc 1 tab ẩn danh). Một bên đăng nhập admin, bên kia đăng nhập khách `binh@gmail.com`. Admin bấm chuyển trạng thái đơn của Bình, khoảng 10 giây sau bên khách hiện thông báo.

**2. Thanh toán trực tuyến (chọn ở giỏ hàng)**
- **💵 Tiền mặt (COD)** như cũ.
- **🏦 Chuyển khoản QR (VietQR):** trang thanh toán hiện mã QR. Khách quét bằng app ngân hàng bất kỳ, số tiền và nội dung `BEPNHA <mã đơn>` đã được điền sẵn. Khách chuyển xong bấm "Tôi đã chuyển khoản", rồi admin kiểm tra tài khoản và bấm **💳 Xác nhận đã nhận tiền**.
  - **Phải điền tài khoản nhận tiền của bạn** vào file `.env` rồi chạy lại `php artisan serve`:
    ```
    VIETQR_BANK=MB              (mã ngân hàng: MB, VCB, TCB, ACB, BIDV, VPB, TPB, ICB...)
    VIETQR_ACCOUNT=0123456789   (số tài khoản của bạn)
    VIETQR_NAME="NGUYEN VAN A"  (tên chủ tài khoản, không dấu)
    ```
- **🟣 Ví MoMo:** bấm thanh toán sẽ chuyển sang trang MoMo, thanh toán xong MoMo tự quay về web và đơn được đánh dấu "Đã thanh toán". Web kiểm tra chữ ký HMAC-SHA256 nên không giả kết quả được.
  - Mặc định dùng **môi trường TEST của MoMo** (không trừ tiền thật). Trên trang MoMo chọn **Thẻ ATM** và nhập thẻ test: `9704 0000 0000 0018`, tên `NGUYEN VAN A`, ngày `03/07`, OTP theo hướng dẫn trên màn hình.
  - Muốn nhận tiền thật thì đăng ký doanh nghiệp tại business.momo.vn, rồi thay `MOMO_*` trong `.env`.
- Đơn chọn COD vẫn có nút **"Thanh toán online"** để trả trước. Huỷ một đơn đã trả tiền thì đơn được đánh dấu "Chờ hoàn tiền".

**3. Chọn số lượng ngay ngoài thực đơn:** mỗi thẻ món có nút **− 1 +** và nút 🛒, nên không cần vào trang chi tiết.

**4. Chat với nhân viên CSKH**
- Khách: nút 💬 ở góc phải dưới mọi trang, có số tin chưa đọc. Khách chưa đăng nhập sẽ được nhắc đăng nhập.
- Nhân viên (tài khoản admin): menu **💬 CSKH** có danh sách khách đã nhắn (tự làm mới mỗi 5 giây, có số tin chưa đọc), khung trả lời và **câu trả lời nhanh**. Từ trang đơn hàng của admin có nút "Chat với khách".
- Tin nhắn hiển thị bằng `textContent` để chống XSS. Giới hạn 20 tin/phút để chống spam.

## 5. Cấu trúc mã nguồn (để thuyết trình / vấn đáp)

```
app/
├── Models/                   ← Lớp Eloquent (OOP): User, Category, Product, CartItem, Order, OrderItem, Review, ChatMessage
├── Http/
│   ├── Controllers/          ← Home, Shop, Auth, Cart, Order, Profile, Review, Payment, Notification, Chat
│   │   └── Admin/            ← Dashboard, Category, Product, Order, User, Chat
│   └── Middleware/AdminOnly.php  ← chặn người không phải admin
├── Notifications/OrderStatusChanged.php ← nội dung thông báo theo từng trạng thái
├── Services/                 ← MomoPayment (tạo giao dịch + ký/kiểm chữ ký), VietQr (tạo mã QR)
├── Providers/AppServiceProvider.php
└── helpers.php               ← hàm vnd() định dạng tiền
database/
├── migrations/               ← tạo 9 bảng
└── seeders/DatabaseSeeder.php ← dữ liệu mẫu
resources/views/              ← giao diện Blade (layouts, shop, cart, orders, auth, admin)
routes/web.php                ← toàn bộ đường dẫn
public/css/style.css, public/js/app.js (AJAX, thông báo), public/js/chat.js (khung chat)
```

### Các điểm kỹ thuật nên nêu

| Điểm | Ở đâu | Ý nghĩa |
|---|---|---|
| MVC | Models / Controllers / views | Tách dữ liệu – xử lý – giao diện |
| OOP | `app/Models/*` | Kế thừa `Model`, quan hệ `hasMany`/`belongsTo`, phương thức `finalPrice()`, `isOutOfStock()`, hằng `Order::STATUSES` |
| Eloquent ORM | mọi controller | Truy vấn bằng đối tượng, tự chống SQL injection |
| Migration + Seeder | `database/` | Tạo bảng và dữ liệu bằng code, chạy lại được |
| Xác thực | `AuthController` | `Auth::attempt`, mật khẩu băm bcrypt (cast `hashed`), `session()->regenerate()` chống session fixation |
| Phân quyền | `AdminOnly` + `routes/web.php` | Nhóm route `/admin` qua middleware `auth` + `admin` |
| Validation | `$request->validate()` | Kiểm tra dữ liệu, báo lỗi tiếng Việt |
| CSRF | `@csrf` trong mọi form | Chống giả mạo request |
| Chống XSS | `{{ }}` trong Blade | Tự escape HTML |
| Transaction | `OrderController@store` | Tạo đơn + trừ kho + xoá giỏ: lỗi 1 bước thì rollback hết |
| Kiểm tra tồn kho 3 lớp | `CartController@store`, `@update`, `OrderController@store` | Lớp 3 dùng `UPDATE ... WHERE stock >= sl` → 2 người đặt cùng lúc món còn 1 phần thì chỉ 1 người thành công |
| Upload ảnh | `Admin\ProductController` | Lưu vào `storage/app/public/products`, xoá ảnh cũ khi đổi/xoá món |
| Phân quyền dữ liệu | `CartController`, `OrderController@show` | Khách chỉ sửa giỏ / xem đơn của chính mình (403 nếu không phải) |
| AJAX | `public/js/app.js` + `CartController@reply` | Form có `data-ajax` gửi bằng `fetch`; server thấy header `Accept: application/json` thì trả JSON, không thì redirect như cũ (tắt JS vẫn dùng được) |
| Tìm kiếm tức thì | `ShopController@index` + `shop/_results.blade.php` | Debounce 400ms; request AJAX chỉ trả phần kết quả (partial view) |
| Quan hệ nhiều bảng | `Review` (user ↔ product) | `withAvg`, `withCount`, scope `withRating()` tính điểm trung bình |
| Huỷ đơn an toàn | `Order::cancel()` | Transaction + `lockForUpdate()`: 2 lần huỷ cùng lúc không hoàn kho 2 lần; dùng chung cho khách và admin |
| Điều kiện đánh giá | `Product::canBeReviewedBy()` | Chỉ khách có đơn "Đã giao" chứa món mới được đánh giá |
| Thống kê | `Admin\DashboardController` | `GROUP BY DATE(created_at)` cho biểu đồ, `JOIN` + `SUM` cho món bán chạy |
| Notification của Laravel | `OrderStatusChanged` + trait `Notifiable` | `$user->notify(...)` lưu vào bảng `notifications`; `unreadNotifications`, `markAsRead()` |
| Máy trạng thái đơn | `Order::FLOW`, `nextStatus()`, `moveTo()` | Mỗi đơn chỉ có 1 bước tiếp theo; đổi trạng thái là báo khách luôn |
| Cổng thanh toán | `Services\MomoPayment` | Ký request bằng HMAC-SHA256 (trường xếp a→z), kiểm chữ ký + số tiền khi MoMo trả kết quả; dùng `hash_equals` chống dò chữ ký |
| Service class | `app/Services/*` | Tách logic thanh toán khỏi controller (controller gọn, dễ test) |
| Polling | `NotificationController@poll`, `chat.js` | JS gọi server định kỳ lấy dữ liệu mới. Đơn giản hơn WebSocket (Laravel Reverb), đủ dùng cho quán nhỏ |
| Chống spam | `throttle:20,1` ở route gửi chat | Mỗi người tối đa 20 tin/phút |

Thử kiểm tra hết hàng: món **"Gỏi cuốn tôm thịt"** trong dữ liệu mẫu có tồn kho = 0 → không thêm vào giỏ được.

## 6. Lỗi thường gặp

| Lỗi | Cách sửa |
|---|---|
| `'composer' is not recognized` | Cài Composer (mục 1.3) rồi mở CMD **mới** |
| `The zip extension and unzip/7z commands are both missing` | Bật `extension=zip` trong php.ini (mục 1.2) |
| `Your requirements could not be resolved ... php` | PHP của XAMPP cũ hơn 8.2 → cài XAMPP bản PHP 8.2 trở lên |
| `SQLSTATE[HY000] [1049] Unknown database` | Chưa tạo database `bepnha_laravel` (mục 1.4) |
| `SQLSTATE[HY000] [2002]` | MySQL chưa bật trong XAMPP |
| `Access denied for user 'root'` | Sửa `DB_PASSWORD` trong `.env` |
| Ảnh món không hiện | Chạy `php artisan storage:link` |
| `The image failed to upload` / lỗi đoán kiểu file | Bật `extension=fileinfo` trong php.ini |
| Bấm thanh toán MoMo báo `cURL error 60: SSL certificate problem` | PHP của XAMPP thiếu chứng chỉ SSL. Tải https://curl.se/ca/cacert.pem về `C:\xampp\php\extras\ssl\cacert.pem`, mở `php.ini`, thêm dòng `curl.cainfo="C:\xampp\php\extras\ssl\cacert.pem"`, rồi tắt và chạy lại `php artisan serve` |
| MoMo báo "Chữ ký không hợp lệ" / "Merchant không tồn tại" | Bộ khoá test mặc định có thể đã bị MoMo đổi → đăng ký tài khoản test tại business.momo.vn rồi điền `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY` vào `.env` |
| Trang thanh toán báo "Quán chưa cài đặt tài khoản nhận tiền" | Điền `VIETQR_ACCOUNT` trong `.env` (xem phần Thanh toán ở trên) |
