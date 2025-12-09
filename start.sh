#!/bin/bash

# 1. Chạy worker.php dưới nền (dấu & rất quan trọng)
# Nó sẽ giúp worker chạy song song mà không chặn Web Server
echo "Dang khoi dong MQTT Worker..."
php worker.php &

# 2. Chạy Web Server (Apache)
echo "Dang khoi dong Web Server..."
docker-php-entrypoint apache2-foreground