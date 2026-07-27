FROM php:8.2-fpm

# Instalar dependencias del sistema indispensables para Composer, Nginx y Postgres
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

# Instalar y activar extensiones de PHP clave, incluyendo ZIP y PGSQL
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# Traer Composer oficial actualizado
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

# Correr composer ignorando restricciones rígidas del sistema operativo base
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

EXPOSE 80
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
