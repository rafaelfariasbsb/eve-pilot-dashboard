#!/bin/bash
set -e

echo "Installing EVE Pilot Dashboard application files..."

# Copy app source files over the Laravel installation
cp -r app-src/app/Models/* src/app/Models/ 2>/dev/null || true
cp -r app-src/app/Http/Controllers/* src/app/Http/Controllers/ 2>/dev/null || true
cp -r app-src/app/Services/* src/app/Services/ 2>/dev/null || true
cp -r app-src/app/Jobs/* src/app/Jobs/ 2>/dev/null || true
cp -r app-src/app/Console/Commands/* src/app/Console/Commands/ 2>/dev/null || true
cp -r app-src/app/Providers/* src/app/Providers/ 2>/dev/null || true

# Copy config
cp app-src/config/services.php src/config/services.php

# Copy routes
cp app-src/routes/web.php src/routes/web.php

# Copy views
cp -r app-src/resources/views/* src/resources/views/

# Copy migrations
cp app-src/database/migrations/* src/database/migrations/

echo "Application files installed!"
echo ""
echo "Now run: docker compose exec app php artisan migrate"
echo "And:     docker compose exec app php artisan eve:import-sde"
