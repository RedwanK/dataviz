FROM php:8.2-cli-alpine

# Install tools + drivers: pdo_pgsql and redis
RUN apk add --no-cache mosquitto-clients bash supervisor postgresql-dev hiredis-dev $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-network $PHPIZE_DEPS

WORKDIR /app

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY mqtt_subscriber.sh /usr/local/bin/mqtt_subscriber.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/mqtt_subscriber.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
