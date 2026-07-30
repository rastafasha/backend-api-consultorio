# 1. Usamos la imagen oficial de PHP con FPM incluido
FROM php:8.2-fpm

# 2. Instalamos dependencias del sistema y Nginx
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

# 3. Instalamos las extensiones de PHP indispensables para Postgres y Laravel
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# 4. Traemos Composer actualizado de forma oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 5. Copiamos todo el proyecto Laravel
COPY . .

# 6. Instalamos paquetes de Composer optimizados para producción
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 7. Damos permisos correctos a las carpetas para evitar bloqueos internos de escritura
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# 🛠️ 8. EL FIX DEFINITIVO: Obligamos a PHP-FPM a abrir el puerto TCP 9000 de verdad
RUN sed -i 's|listen = /var/run/php-fpm.sock|listen = 127.0.0.1:9000|g' /usr/local/etc/php-fpm.d/www.conf || \
    sed -i 's|listen = 127.0.0.1:9000|listen = 127.0.0.1:9000|g' /usr/local/etc/php-fpm.d/www.conf

# 9. Configuración limpia de Nginx apuntando al puerto TCP seguro
RUN echo 'server { \
    listen 80; \
    root /var/www/public; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    } \
}' > /etc/nginx/sites-available/default

EXPOSE 80

# 10. Encendemos PHP-FPM en segundo plano, trituramos la caché residual y arrancamos Nginx
CMD php-fpm -D && \
    php artisan config:clear && \
    php artisan route:clear && \
    php artisan cache:clear && \
    nginx -g "daemon off;"
