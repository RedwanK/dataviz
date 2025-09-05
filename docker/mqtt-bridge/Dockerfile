FROM php:8.2-cli-alpine

# Install tools + PostgreSQL driver for PHP (pdo_pgsql)
RUN apk add --no-cache mosquitto-clients bash supervisor postgresql-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && apk del --no-network $PHPIZE_DEPS

WORKDIR /app

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY mqtt_subscriber.sh /usr/local/bin/mqtt_subscriber.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/mqtt_subscriber.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
