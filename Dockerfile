# Sử dụng PHP 8.1
FROM php:8.1-apache

# 1. Cài đặt thư viện hệ thống (Thêm libzip-dev để hỗ trợ zip)
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

# 3. Copy code vào container
COPY . /var/www/html/

# 4. Cài đặt thư viện (Thêm cờ --ignore-platform-reqs để bỏ qua lỗi phiên bản)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 5. Cấp quyền chạy file start.sh
RUN chmod +x /var/www/html/start.sh

# 6. Mở cổng 80 và phân quyền
EXPOSE 80
RUN chown -R www-data:www-data /var/www/html

# 7. Chạy script khởi động
CMD ["./start.sh"]