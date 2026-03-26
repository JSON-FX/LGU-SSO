#!/bin/sh
set -e

echo "Starting LGU-SSO..."

# Wait for MySQL to be ready (use shell env vars from docker-compose, not .env file)
MYSQL_HOST="${DB_HOST:-mysql}"
MYSQL_PORT="${DB_PORT:-3306}"
echo "Waiting for MySQL at ${MYSQL_HOST}:${MYSQL_PORT}..."
until php -r "new PDO('mysql:host=${MYSQL_HOST};port=${MYSQL_PORT}', getenv('DB_USERNAME') ?: '${DB_USERNAME}', getenv('DB_PASSWORD') ?: '${DB_PASSWORD}');" 2>/dev/null; do
    echo "MySQL not ready, retrying in 2s..."
    sleep 2
done
echo "MySQL is ready."

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed if employees table is empty
EMPLOYEE_COUNT=$(php artisan tinker --execute="echo \App\Models\Employee::count();" 2>/dev/null || echo "0")
if [ "$EMPLOYEE_COUNT" = "0" ]; then
    echo "Seeding database..."
    php artisan db:seed --force
fi

# Cache configuration and routes
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Starting PHP development server on port 8000..."
exec php artisan serve --host=0.0.0.0 --port=8000
