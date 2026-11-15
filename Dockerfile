# Runtime image for the PHP short-link app: Apache + mod_php with the
# mysqli, PDO, cURL and GD extensions the README lists as requirements.
FROM php:8.3-apache-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql \
    && apt-get purge -y --auto-remove libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers

# Debian ships AllowOverride None for /var/www, which would silently disable
# the bundled .htaccess pretty-URL and security-header rules.
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# No-database probe endpoint for HEALTHCHECK and reverse-proxy uptime checks.
RUN printf '<?php\nhttp_response_code(200);\nheader("Content-Type: text/plain; charset=utf-8");\necho "ok";\n' > /var/www/html/health.php

COPY --chown=www-data:www-data . /var/www/html/

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -fsS http://127.0.0.1/health.php || exit 1

CMD ["apache2-foreground"]
