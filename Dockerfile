# 1. Imagen oficial de PHP con FPM incluido
FROM php:8.2-fpm

# 2. Dependencias del sistema y Nginx
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev \
    nginx

# 3. Extensiones de PHP indispensables para Postgres y Laravel
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# 4. Traemos Composer actualizado
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 5. Copiamos todo el proyecto Laravel
COPY . .

# 6. Instalamos paquetes de Composer optimizados para producción
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 7. Damos permisos correctos a las carpetas
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 8. Forzamos a PHP-FPM a abrir el puerto TCP 9000
RUN sed -i 's|listen = /var/run/php-fpm.sock|listen = 127.0.0.1:9000|g' /usr/local/etc/php-fpm.d/www.conf || \
    sed -i 's|listen = 127.0.0.1:9000|listen = 127.0.0.1:9000|g' /usr/local/etc/php-fpm.d/www.conf

# 🛠️ 9. EL FIX DE CORS DEFINITIVO EN NGINX (Con la directiva 'always')
RUN echo 'server { \
    listen 80; \
    root /var/www/public; \
    index index.php index.html; \
    location / { \
        if ($request_method = "OPTIONS") { \
            add_header "Access-Control-Allow-Origin" "*" always; \
            add_header "Access-Control-Allow-Methods" "GET, POST, OPTIONS, PUT, DELETE" always; \
            add_header "Access-Control-Allow-Headers" "Authorization, Content-Type, Accept, X-Requested-With" always; \
            return 204; \
        } \
        add_header "Access-Control-Allow-Origin" "*" always; \
        add_header "Access-Control-Allow-Methods" "GET, POST, OPTIONS, PUT, DELETE" always; \
        add_header "Access-Control-Allow-Headers" "Authorization, Content-Type, Accept, X-Requested-With" always; \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        add_header "Access-Control-Allow-Origin" "*" always; \
        add_header "Access-Control-Allow-Methods" "GET, POST, OPTIONS, PUT, DELETE" always; \
        add_header "Access-Control-Allow-Headers" "Authorization, Content-Type, Accept, X-Requested-With" always; \
    } \
}' > /etc/nginx/sites-available/default

EXPOSE 80

# 10. Encendemos PHP-FPM, limpiamos caché de Laravel 8 y arrancamos Nginx
CMD php-fpm -D && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan cache:clear && \
    nginx -g "daemon off;"
