#!/bin/bash

# Nombre del screen
SCREEN_NAME="Horalia"

# Directorio del proyecto
PROJECT_DIR="/var/www/html/horalia"

# Comando para iniciar el queue:work
QUEUE_WORK_COMMAND="cd $PROJECT_DIR && php artisan queue:work"

# Verifica si el screen con el nombre especificado está en ejecución
if screen -list | grep -q "$SCREEN_NAME"; then
    echo "Screen $SCREEN_NAME is running. Killing it..."
    # Matar el screen existente
    screen -S "$SCREEN_NAME" -X quit
    echo "Screen $SCREEN_NAME has been killed."
fi

echo "Starting a new screen..."
# Iniciar un nuevo screen
screen -dmS "$SCREEN_NAME" bash -c "$QUEUE_WORK_COMMAND"
## echo "Queue worker started in new screen at $(date)" | mail -s "Queue Worker Restarted" your-email@example.com
