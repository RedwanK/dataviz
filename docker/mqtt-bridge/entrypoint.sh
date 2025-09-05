#!/usr/bin/env sh
set -eu

# Defaults
: "${WORKER_COUNT:=4}"
: "${CONSUME_TRANSPORTS:=async}"
: "${CONSUMER_OPTIONS:=--time-limit=3600 --memory-limit=256M --no-interaction}"

# Ensure supervisor dirs
mkdir -p /etc/supervisor/conf.d

cat > /etc/supervisor/supervisord.conf <<EOF
[supervisord]
nodaemon=true
user=root
logfile=/dev/stdout
logfile_maxbytes=0

[inet_http_server]
port=127.0.0.1:9001

[supervisorctl]
serverurl=http://127.0.0.1:9001

[program:mqtt_php_consumer]
command=php /app/bin/mqtt-consumer.php
autostart=true
autorestart=true
startretries=3
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:messenger_consumer]
command=php /app/bin/console messenger:consume ${CONSUME_TRANSPORTS} ${CONSUMER_OPTIONS}
process_name=%(program_name)s_%(process_num)02d
numprocs=${WORKER_COUNT}
autostart=true
autorestart=true
startretries=3
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

echo "[mqtt-bridge] Starting Supervisor with ${WORKER_COUNT} workers on '${CONSUME_TRANSPORTS}'"
echo "[mqtt-bridge] Warming up Symfony cache for APP_ENV='${APP_ENV:-dev}'"
php /app/bin/console cache:warmup || echo "[mqtt-bridge] Cache warmup failed (continuing)"
exec supervisord -c /etc/supervisor/supervisord.conf
