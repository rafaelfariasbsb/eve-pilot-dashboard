#!/bin/bash
set -e

echo "========================================="
echo "  EVE Pilot Dashboard - Setup Script"
echo "========================================="
echo ""

# Build containers
echo "[1/6] Building Docker containers..."
docker compose build

# Create Laravel project via composer in a temporary container
echo "[2/6] Creating Laravel 12 project..."
if [ ! -f "src/artisan" ]; then
    mkdir -p src
    docker compose run --rm --no-deps -u root app sh -c "
        composer create-project laravel/laravel /tmp/laravel '12.*' --prefer-dist --no-interaction
        cp -a /tmp/laravel/. /var/www/
        chown -R www:www /var/www
    "
    echo "    Laravel installed successfully!"
else
    echo "    Laravel already installed, skipping..."
fi

# Install additional packages
echo "[3/6] Installing EVE Online packages..."
docker compose run --rm --no-deps app sh -c "
    cd /var/www
    composer require socialiteproviders/eveonline
    composer require firebase/php-jwt
"

# Copy .env
echo "[4/6] Configuring environment..."
if [ ! -f "src/.env" ]; then
    cp src/.env.example src/.env
fi

# Update .env with our settings
cat > src/.env << 'ENVEOF'
APP_NAME="EVE Pilot Dashboard"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080
APP_KEY=

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=eve_pilot
DB_USERNAME=eve_pilot
DB_PASSWORD=secret

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# EVE Online SSO - Get from https://developers.eveonline.com/
EVE_CLIENT_ID=
EVE_CLIENT_SECRET=
EVE_CALLBACK_URL=http://localhost:8080/auth/eve/callback

# ESI API
ESI_BASE_URL=https://esi.evetech.net/latest
ESI_DATASOURCE=tranquility
ESI_USER_AGENT="EVE Pilot Dashboard/1.0 (contact@example.com)"
ENVEOF

# Start all services
echo "[5/6] Starting services..."
docker compose up -d

# Wait for services
echo "    Waiting for services to be ready..."
sleep 5

# Generate app key and run migrations
echo "[6/6] Finalizing setup..."
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
docker compose exec app php artisan storage:link

echo ""
echo "========================================="
echo "  Setup Complete!"
echo "========================================="
echo ""
echo "  App URL:    http://localhost:8080"
echo "  PostgreSQL: localhost:5432"
echo "  Redis:      localhost:6379"
echo ""
echo "  IMPORTANT: Configure your EVE SSO credentials in src/.env"
echo "  Get them at: https://developers.eveonline.com/"
echo ""
echo "  Scopes needed:"
echo "    esi-wallet.read_character_wallet.v1"
echo "    esi-assets.read_assets.v1"
echo "    esi-characters.read_blueprints.v1"
echo "    esi-industry.read_character_jobs.v1"
echo "    esi-skills.read_skills.v1"
echo ""
