# Sử dụng PHP 8.1
FROM php:8.1-apache

# 1. Cài đặt thư viện hệ thống (QUAN TRỌNG: Thêm ca-certificates)
RUN apt-get update && apt-get install -y \
    libssl-dev \
    git \
    unzip \
    libzip-dev \
    ca-certificates \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install zip

# 2. Cài đặt Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Copy code
COPY . /var/www/html/

# 4. Xóa và cài mới thư viện (Tránh lỗi RAM)
RUN rm -rf vendor composer.lock composer.json \
    && export COMPOSER_MEMORY_LIMIT=-1 \
    && composer require mongodb/mongodb --ignore-platform-reqs --optimize-autoloader

# 5. Cấp quyền file start
RUN chmod +x /var/www/html/start.sh

# 6. Mở cổng
EXPOSE 80
RUN chown -R www-data:www-data /var/www/html

# 7. Chạy
CMD ["./start.sh"]