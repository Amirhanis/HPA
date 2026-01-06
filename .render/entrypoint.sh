#!/usr/bin/env sh
set -e

: "${PORT:=8080}"

# Render supplies $PORT; bind nginx to it
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

exec /usr/bin/supervisord -n -c /etc/supervisord.conf
