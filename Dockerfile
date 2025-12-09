# Sử dụng PHP 8.1 kèm Apache
FROM php:8.1-apache

# Cài đặt các thư viện hệ thống cần thiết + MongoDB Driver
RUN apt-get update && apt-get install -y \
    libssl-dev \
    git \
    unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy toàn bộ code từ máy tính lên Server
COPY . /var/www/html/

# Chạy lệnh cài đặt các thư viện PHP (vendor)
RUN composer install --no-dev --optimize-autoloader

# Cấp quyền thực thi cho file start.sh (BẮT BUỘC)
RUN chmod +x /var/www/html/start.sh

# Mở cổng 80
EXPOSE 80

# Chỉnh sửa quyền sở hữu file cho Apache
RUN chown -R www-data:www-data /var/www/html

# Chạy file start.sh thay vì chạy Apache trực tiếp
CMD ["./start.sh"]