# Menggunakan gambar PHP 7.x sebagai basis
FROM php:7.x-fpm

# Instal dependensi yang diperlukan
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip mbstring exif pcntl bcmath xml

# Instal Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Menyalin konfigurasi PHP-FPM
COPY docker/php-fpm/php.ini /usr/local/etc/php/php.ini

# Menyalin kode aplikasi Laravel ke dalam kontainer
COPY . /var/www/html

# Menjalankan Composer untuk menginstal dependensi Laravel
RUN composer install

# Set perintah untuk menjalankan kontainer (contoh: php artisan serve)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
