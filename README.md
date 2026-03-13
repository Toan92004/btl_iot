# 🏡 Hệ Thống Smart Home IoT Giám Sát & Điều Khiển Tự Động

Dự án Hệ thống Nhà thông minh (Smart Home IoT) giám sát môi trường và điều khiển thiết bị từ xa qua giao diện Web. Hệ thống sử dụng mô hình kết hợp (Hybrid) giữa giao tiếp phần cứng (I2C), giao thức truyền tin thời gian thực (MQTT) và cơ sở dữ liệu NoSQL (MongoDB).

---

## 🌟 TÍNH NĂNG NỔI BẬT

### 1. Thuật toán Tự động & An toàn (Tại Edge - Arduino Uno)

- **🚨 Cảnh báo cháy (Ưu tiên tuyệt đối):** Khi nhiệt độ vượt quá 50°C, hệ thống lập tức cưỡng chế tắt quạt (ngăn cung cấp oxy) và nháy đèn LED cảnh báo. Hệ thống có cơ chế Hysteresis, chỉ tắt báo động khi nhiệt độ giảm xuống dưới 45°C.
- **🌡️ Quạt thông minh (Smart Fan):** Tự động điều chỉnh 3 tốc độ gió theo nhiệt độ (28°C, 30°C, 32°C) nhưng **chỉ hoạt động khi có người** để tiết kiệm năng lượng.
- **💡 Đèn thông minh (Smart Light):** Tự động bật sáng khi phát hiện trời tối VÀ có người di chuyển.
- **🔄 Tự động phục hồi (Auto-Revert):** Khi người dùng can thiệp chỉnh tay (Manual), hệ thống sẽ đếm ngược 10 giây (nếu không có thao tác mới) để tự động quay về chế độ Auto.

### 2. Quản lý Đám mây & Web (Cloud & Web Dashboard)

- **📊 Giám sát thời gian thực:** Web Dashboard tự động làm mới (Meta refresh 5s) hiển thị Nhiệt độ, Độ ẩm, Cảnh báo chuyển động.
- **🎮 Điều khiển từ xa:** Cho phép Bật/Tắt đèn LED và chỉnh 4 cấp độ Quạt (Tắt, Mức 1, 2, 3) trực tiếp từ Web.
- **💾 Lưu trữ tối ưu:** Tiến trình chạy ngầm (`worker.php`) nhận dữ liệu từ MQTT, có tích hợp bộ lọc thông minh: chỉ lưu vào Database khi nhiệt độ chênh lệch > 0.5°C hoặc thay đổi trạng thái thiết bị.
- **🔍 Tra cứu lịch sử:** Bộ lọc dữ liệu đa dạng theo thời gian, trạng thái thiết bị, nhiệt độ, độ ẩm và xuất báo cáo.
- **🔐 Bảo mật người dùng:** Hệ thống Đăng nhập/Đăng ký, mật khẩu được mã hóa an toàn bằng thuật toán Hash.

---

## ⚙️ KIẾN TRÚC HỆ THỐNG

1.  **Tầng Vật lý (Hardware):** Cảm biến (DHT11, PIR, LDR) + Thiết bị chấp hành (Quạt, Đèn) $\rightarrow$ Vi điều khiển trung tâm **Arduino Uno**.
2.  **Tầng Giao tiếp Nội bộ:** Arduino Uno đóng gói dữ liệu (7 Bytes) và gửi sang **ESP32** qua giao thức **I2C**.
3.  **Tầng Mạng (Network):** ESP32 đóng gói JSON và truyền qua WiFi đến Broker **HiveMQ Cloud** (MQTT over SSL - Port 8883).
4.  **Tầng Ứng dụng & Dữ liệu (Backend):** - **Worker PHP** chạy nền lắng nghe MQTT và lưu trữ dữ liệu vào **MongoDB Atlas**.
    - **Web Server (PHP/Apache)** phục vụ giao diện người dùng.

---

## 🛠️ YÊU CẦU PHẦN CỨNG & PHẦN MỀM

### Phần Cứng

- 1x Board Arduino Uno R3
- 1x Board ESP32 (NodeMCU)
- Cảm biến: DHT11 (Nhiệt/Ẩm), HC-SR501 (PIR - Chuyển động), LDR (Quang trở)
- Module: Relay (Cho Đèn LED), L298N/L293D (Điều khiển động cơ Quạt)
- Nút nhấn cứng (Cho chế độ Manual tại chỗ)

### Phần Mềm & Dịch Vụ

- **Arduino IDE:** Thư viện `WiFiClientSecure`, `PubSubClient`, `ArduinoJson`, `DHT sensor library`.
- **Web Server:** PHP 8.1, Apache, Composer (thư viện `mongodb/mongodb`, `phpMQTT`).
- **Cloud Services:** MongoDB Atlas (Database), HiveMQ Cloud (MQTT Broker).
- **Triển khai:** Nền tảng Render.com (sử dụng `Dockerfile` có sẵn).

---

## 🚀 HƯỚNG DẪN CÀI ĐẶT & TRIỂN KHAI

### Bước 1: Thiết lập Phần cứng (C-Code)

1.  Mở `mqtt_uno.ino` và nạp vào mạch **Arduino Uno**.
2.  Mở `mqtt_esp.ino`, cập nhật thông tin mạng của bạn:
    ```cpp
    const char* ssid = "TÊN_WIFI_CỦA_BẠN";
    const char* password = "MẬT_KHẨU_WIFI";
    ```
3.  Nạp code vào **ESP32**. Đảm bảo nối dây I2C giữa 2 board (SDA_UNO $\leftrightarrow$ D21_ESP32, SCL_UNO $\leftrightarrow$ D22_ESP32) và nối chung mass (GND).

### Bước 2: Cấu hình Cơ sở dữ liệu & MQTT

1.  Mở file `db.php` và thay đổi chuỗi kết nối MongoDB Atlas của bạn.
2.  Kiểm tra và cập nhật thông tin HiveMQ (Server, Port, Username, Password) tại 3 file:
    - [cite_start]`mqtt_esp.ino`
    - `worker.php`
    - `index.php`

### Bước 3: Triển khai Web Server lên Render.com

Hệ thống đã được thiết kế sẵn `Dockerfile` để cấu hình môi trường chuẩn nhất trên Render.

1.  **Đẩy mã nguồn lên GitHub:** Sử dụng Git Bash để `commit` và `push` toàn bộ thư mục dự án của bạn lên một Repository trên GitHub.
2.  **Tạo dịch vụ trên Render:** Truy cập [Render.com](https://render.com), đăng nhập và chọn tạo **New Web Service**.
3.  **Kết nối GitHub:** Chọn kết nối với Repository bạn vừa đẩy code lên.
4.  **Cấu hình môi trường:**
    - Ở mục **Environment**, chọn **Docker**. (Render sẽ tự động quét và đọc file `Dockerfile` của bạn).
    - Chọn gói miễn phí (Free Tier) để bắt đầu.
5.  **Deploy:** Nhấn **Create Web Service**.
    - _Lưu ý: Render sẽ tiến hành build image. File `start.sh` sẽ tự động khởi chạy tiến trình `worker.php` ngầm và kích hoạt Apache Web Server cùng một lúc._

---

## 📖 HƯỚNG DẪN SỬ DỤNG

1.  Truy cập vào tên miền mà Render.com cung cấp cho bạn (Ví dụ: `https://my-smarthome-iot.onrender.com`).
2.  Tạo tài khoản mới tại trang **Đăng ký** (`register.php`).
3.  Đăng nhập vào hệ thống.
4.  Tại bảng điều khiển (`index.php`), bạn có thể giám sát thông số thời gian thực từ phần cứng gửi lên và nhấn các nút bấm để điều khiển Quạt/Đèn ở nhà.
5.  Truy cập tab **Lịch sử** (`history.php`) để tra cứu lịch sử ghi nhận của các cảm biến cũng như nhật ký thao tác điều khiển.
