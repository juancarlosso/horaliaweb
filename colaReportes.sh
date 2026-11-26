#!/bin/bash

SCREEN_NAME="Horalia_Reportes"
PROJECT_DIR="/var/www/html/horalia"
QUEUE_WORK_COMMAND="cd '$PROJECT_DIR' && php artisan queue:work reportes --queue=reportes --timeout=600"

if screen -list | grep -Eq "^[[:space:]]*[0-9]+\.${SCREEN_NAME}[[:space:]]"; then
    echo "Deteniendo la sesión $SCREEN_NAME..."
    screen -S "$SCREEN_NAME" -X quit || exit 1
fi

echo "Iniciando la sesión $SCREEN_NAME..."
screen -dmS "$SCREEN_NAME" bash -c "$QUEUE_WORK_COMMAND"
