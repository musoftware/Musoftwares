#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
while ! php -r "try { new PDO('mysql:host=mysql.test;dbname=musoftware_testing', 'root', 'testing_password'); echo 'Ready'; } catch (Exception \$e) { exit(1); }" &> /dev/null; do
    sleep 2
done

echo "MySQL is ready. Running migrations..."
php artisan migrate:fresh --seed --force

echo "Starting Laravel server..."
php artisan serve --host=0.0.0.0 --port=80 &
PHP_PID=$!

echo "Starting Reverb WebSocket server..."
php artisan reverb:start --host=0.0.0.0 --port=8080 &
REVERB_PID=$!

wait $PHP_PID $REVERB_PID
