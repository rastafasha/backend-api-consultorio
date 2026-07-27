FROM php:8.2-fpm

# Instalar dependencias del sistema y soporte para PostgreSQL
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip nginx libpq-dev

# Instalar extensiones de PHP incluyendo el driver de Postgres (pgsql)
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd

# Descargar la última versión de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
WORKDIR /var/www
COPY . .

# Instalar dependencias de Laravel
RUN composer install --no-dev --optimize-autoloader

# Exponer el puerto que Render requiere
EXPOSE 80
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
