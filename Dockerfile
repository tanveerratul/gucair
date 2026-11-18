FROM php:8.3-apache

# System libraries needed to compile the official MongoDB PHP extension.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libssl-dev \
        libcurl4-openssl-dev \
        pkg-config \
        zlib1g-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && rm -rf /var/lib/apt/lists/*

# Copy Composer from its official image so the installed version is predictable.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy dependency files first so Docker can cache this layer.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

COPY . ./

RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf \
    && ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log

# Server-level hardening: no directory listings and no direct access to private files.
RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    Options -Indexes' \
    '</Directory>' \
    '<Directory /var/www/html/config>' \
    '    Require all denied' \
    '</Directory>' \
    '<Directory /var/www/html/includes>' \
    '    Require all denied' \
    '</Directory>' \
    '<Directory /var/www/html/database>' \
    '    Require all denied' \
    '</Directory>' \
    '<Directory /var/www/html/vendor>' \
    '    Require all denied' \
    '</Directory>' \
    '<FilesMatch "^(AGENTS\.md|README\.md|DEPLOYMENT\.md|Dockerfile|docker-start\.sh|composer\.json|composer\.lock|START\.bat|\.dockerignore|\.gitignore)$">' \
    '    Require all denied' \
    '</FilesMatch>' \
    > /etc/apache2/conf-available/gucair-security.conf \
    && a2enconf gucair-security

# Render sets PORT (for example 10000); this script reconfigures Apache.
COPY docker-start.sh /usr/local/bin/docker-start.sh
RUN chmod +x /usr/local/bin/docker-start.sh

# MONGODB_URI is overridden by Render/Atlas. Local Docker uses the fallback.
ENV MONGODB_URI=mongodb://127.0.0.1:27017
ENV PORT=80

EXPOSE 80

CMD ["docker-start.sh"]
