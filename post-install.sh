#!/bin/bash

# Create necessary directories for SleekDB
mkdir -p storage/sleekdb
mkdir -p storage/app/files
mkdir -p storage/testing/sleekdb

# Set proper permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create symbolic link for storage
php artisan storage:link

echo "Setup completed successfully!"