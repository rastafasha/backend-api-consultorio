# 1. Cambiamos a la versión CLI que es ligera y perfecta para el servidor integrado
FROM php:8.2-cli

# 2. Instalamos las dependencias necesarias de Linux para Composer y PostgreSQL
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    libzip-dev

# 3. Instalamos las extensiones nativas de PHP indispensables para Laravel y Postgres
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip

# 4. Traemos el ejecutable oficial de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 5. Copiamos los archivos de nuestro proyecto Laravel
COPY . .

# 6. Ejecutamos la instalación de dependencias optimizada para producción
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# 7. Exponemos el puerto dinámico requerido por Render
EXPOSE 80

# 8. Limpiamos la caché interna y encendemos el servidor en el puerto correcto de producción
CMD php artisan config:clear && php artisan route:clear && php artisan cache:clear && php -S 0.0.0.0:80 -t public

