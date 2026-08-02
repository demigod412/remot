FROM php:8.3-fpm

# zlib1g-dev is required by gd's configure step; libjpeg-dev and libfreetype6-dev
# provide the headers for --with-jpeg / --with-freetype.
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    zlib1g-dev libpng-dev libjpeg-dev libfreetype6-dev \
    libonig-dev libxml2-dev libzip-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# gd must be CONFIGURED with jpeg BEFORE install. Installing it bare builds
# PNG-only support, so imagejpeg() is never defined.
RUN docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["php-fpm"]