# Sử dụng PHP 8.1
FROM php:8.1-apache

# Cài đặt các thư viện hệ thống và Driver MongoDB
RUN apt-get update && apt-get install -y \
    libssl-dev \
    git \
    unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy toàn bộ code vào trong server
COPY . /var/www/html/

# Chạy lệnh cài đặt các thư viện PHP
RUN composer install --no-dev --optimize-autoloader

# Mở cổng 80 cho web
EXPOSE 80

# Chỉnh sửa quyền
RUN chown -R www-data:www-data /var/www/html