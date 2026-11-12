# syntax=docker/dockerfile:1

# PHP-FPM with the extensions the app uses. The -dev headers needed to build them
# are purged; only the shared libraries the extensions link to stay.
FROM php:8.5-fpm AS runtime

RUN set -eux; \
    savedAptMark="$(apt-mark showmanual)"; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        pgsql \
        pdo_pgsql \
        zip; \
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark > /dev/null; \
    ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { so = $(NF-1); if (index(so, "/usr/local/") == 1) { next }; gsub("^/(usr/)?", "", so); printf "*%s\n", so }' \
        | sort -u \
        | xargs -r dpkg-query --search \
        | cut -d: -f1 \
        | sort -u \
        | xargs -r apt-mark manual > /dev/null; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# Composer, Node 26 LTS and pnpm to install dependencies and build the frontend.
# Local Docker Compose runs this stage; deployed images never include it.
FROM runtime AS build

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip; \
    curl -fsSL https://deb.nodesource.com/setup_26.x -o /tmp/nodesource_setup.sh; \
    bash /tmp/nodesource_setup.sh; \
    rm /tmp/nodesource_setup.sh; \
    apt-get install -y --no-install-recommends nodejs; \
    npm install --global pnpm@10; \
    rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN pnpm install --frozen-lockfile

COPY . .

RUN composer dump-autoload --no-dev --optimize --no-interaction \
    && pnpm build \
    && mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Deployed image: the runtime plus the app, production vendor and built assets.
FROM runtime AS production

COPY --from=build --exclude=node_modules /var/www /var/www

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
