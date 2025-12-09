# Sử dụng PHP 8.1
FROM php:8.1-apache

# 1. Cài đặt các thư viện hệ thống cần thiết
# Đã tối ưu hóa để cài nhanh nhất
RUN apt-get update && apt-get install -y \
    libssl-dev \
    git \
    unzip \
    libzip-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip

# 2. Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Copy toàn bộ code từ máy tính lên Server
COPY . /var/www/html/

# 4. CHIẾN THUẬT MỚI: XÓA SẠCH VÀ CÀI MỚI TRỰC TIẾP
# - Bước này xóa bỏ file composer.json/lock cũ của bạn để tránh mọi lỗi cú pháp
# - Sau đó chạy lệnh 'composer require' để tải thư viện MongoDB trực tiếp
# - Cách này tốn ít RAM hơn nhiều so với 'composer update'
RUN rm -rf vendor composer.lock composer.json \
    && export COMPOSER_MEMORY_LIMIT=-1 \
    && composer require mongodb/mongodb --ignore-platform-reqs --optimize-autoloader

# 5. Cấp quyền thực thi cho file start.sh
RUN chmod +x /var/www/html/start.sh

# 6. Mở cổng 80 và phân quyền
EXPOSE 80
RUN chown -R www-data:www-data /var/www/html

# 7. Chạy script khởi động
CMD ["./start.sh"]