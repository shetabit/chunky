# The image the example runs in.
#
# It is PHP and Composer and nothing else: the example is served by the web server
# that ships with PHP.
#
# It is started from this directory:
#
#   docker compose up
#
# The Makefile next to it wraps that as `make up` and `make down`.

FROM php:8.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    COMPOSER_NO_INTERACTION=1

EXPOSE 8080

# The dependencies are installed on the first start, so that a fresh clone needs
# nothing but `docker compose up`.
CMD ["sh", "-c", "[ -f vendor/autoload.php ] || composer install --no-interaction; \
    mkdir -p uploads && chmod 777 uploads; \
    printf '\\n  The example is running on http://localhost:%s\\n\\n' \"${PORT:-8080}\"; \
    php -S 0.0.0.0:8080 -t public"]
