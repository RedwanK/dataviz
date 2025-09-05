#!/usr/bin/env sh
set -eu

# Env with defaults
: "${MQTT_HOST:=mqtt}"
: "${MQTT_PORT:=1883}"
: "${MQTT_USERNAME:=}"
: "${MQTT_PASSWORD:=}"
: "${MQTT_TOPICS:=#}"

PHP_CONSOLE="php /app/bin/console app:mqtt:handle"

SUB_ARGS="-h ${MQTT_HOST} -p ${MQTT_PORT} -t ${MQTT_TOPICS} -F '%t|||%p'"
if [ -n "${MQTT_USERNAME}" ] && [ -n "${MQTT_PASSWORD}" ]; then
  SUB_ARGS="$SUB_ARGS -u ${MQTT_USERNAME} -P ${MQTT_PASSWORD}"
fi

echo "[mqtt-bridge] Subscribing to '${MQTT_TOPICS}' on ${MQTT_HOST}:${MQTT_PORT}"

# shellcheck disable=SC2086
mosquitto_sub $SUB_ARGS | while IFS= read -r line; do
  topic=${line%|||*}
  payload=${line#*|||}
  # Dispatch message to Messenger via console command
  if [ -n "$topic" ]; then
    $PHP_CONSOLE "$topic" "$payload" || true
  fi
done

