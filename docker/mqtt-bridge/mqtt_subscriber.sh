#!/usr/bin/env sh
set -eu

# Env with defaults
: "${MQTT_HOST:=mqtt}"
: "${MQTT_PORT:=1883}"
: "${MQTT_USERNAME:=}"
: "${MQTT_PASSWORD:=}"

# Build mosquitto_sub args safely (POSIX sh)
# Use JSON output so payloads with newlines/quotes are escaped in a single line
set -- -h "${MQTT_HOST}" -p "${MQTT_PORT}" -F '{"t":"%t","p":%j}'

# Auth if provided
if [ -n "${MQTT_USERNAME}" ] && [ -n "${MQTT_PASSWORD}" ]; then
  set -- "$@" -u "${MQTT_USERNAME}" -P "${MQTT_PASSWORD}"
fi

# Support multiple topics separated by commas or spaces
topics="gateways/+"
if [ -z "${topics}" ]; then
  topics="#"
fi
topics=$(printf %s "$topics" | tr ',' ' ')

for t in $topics; do
  set -- "$@" -t "$t"
done

echo "[mqtt-bridge] Subscribing to topics: $(printf '%s ' $topics) on ${MQTT_HOST}:${MQTT_PORT} (JSON line format)"

# Keep a single Symfony kernel alive in the producer, avoiding per-message boot
exec mosquitto_sub "$@" | php /app/bin/mqtt-producer.php
