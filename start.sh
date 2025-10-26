#!/bin/bash

# Simple start script for the Duplicate Image Detector
# This script starts a local PHP development server

PORT=${1:-8080}
HOST=${2:-localhost}

echo "Starting Duplicate Image Detector..."
echo "Server will be available at: http://$HOST:$PORT/frontend.html"
echo "Press Ctrl+C to stop the server"
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "Error: PHP is not installed or not in PATH"
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -v | grep -oP 'PHP \K[0-9]+\.[0-9]+')
REQUIRED_VERSION="8.3"

if [ "$(printf '%s\n' "$REQUIRED_VERSION" "$PHP_VERSION" | sort -V | head -n1)" != "$REQUIRED_VERSION" ]; then
    echo "Warning: PHP version $PHP_VERSION detected. PHP 8.3 or higher is recommended."
fi

# Start the PHP development server
php -S $HOST:$PORT
