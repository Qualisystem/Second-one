#!/bin/sh
set -e

# Run installation only if the .env file does not exist.
if [ ! -f ".env" ]; then
    echo "--- First time setup: Installing OpenGRC ---"

    # 1. Create environment from example
    cp .env.example .env

    # --- START: FINAL FIX ---
    # 2. Set all required variables for the application to boot
    sed -i 's/APP_KEY=/APP_KEY=base64:dummykeydummykeydummykeydummykeydummykey=/' .env
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=sqlite/' .env
    sed -i '/^DB_DATABASE=/d' .env
    # Explicitly set APP_URL, as this is a common requirement for boot-up checks
    sed -i 's#APP_URL=http://localhost#APP_URL=http://localhost:8080#' .env
    # --- END: FINAL FIX ---

    # 3. Create a valid SQLite database using the default Laravel filename.
    sqlite3 database/database.sqlite "VACUUM;"
    chown www-data:www-data database/database.sqlite

    # 4. Run the settings migration first to allow the app to boot
    php artisan migrate --force --path=database/migrations/2021_02_10_000000_create_settings_table.php

    # 5. Run the rest of the installation
    php artisan migrate --force
    php artisan key:generate --force
    php artisan config:cache

    echo "--- Installation complete ---"
fi

# Execute the command passed to the script (e.g., "apache2-foreground")
exec "$@"