FROM php:8.2-fpm-alpine

# Instalar dependencias del sistema y extensiones de MySQL modernas que entienden SHA2
RUN apk add --no-cache \
    nginx \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git

RUN docker-php-ext-install pdo_mysql bcmath gd

# Instalar Composer de forma limpia
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar el directorio de trabajo
WORKDIR /app
COPY . .

# Instalar dependencias de Laravel omitiendo scripts conflictivos
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Exponer el puerto estándar
EXPOSE 80

# Limpiar, migrar base de datos y arrancar el servidor web
CMD ["sh", "-c", "php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php -S 0.0.0.0:${PORT} -t public"]

