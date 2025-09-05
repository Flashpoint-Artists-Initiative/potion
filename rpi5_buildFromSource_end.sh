#!/bin/bash

#------------------------------------
# Configure Environment
#------------------------------------
# The .env file must exist and be filled out
# ! IMPORTANT ! Go edit the .env file now as this will be used for the rest of the procedure to build stuff
source .env

# Install PHP dependencies
composer update
composer install

# Install Node.js dependencies
npm install

php artisan key:generate

# Generate JWT certificates
mkdir -p storage/certs
openssl genrsa -out storage/certs/jwt-rsa-4096-private.pem 4096
openssl rsa -in storage/certs/jwt-rsa-4096-private.pem -outform PEM -pubout -out storage/certs/jwt-rsa-4096-public.pem

# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

mkdir -p storage/logs
sudo chmod -R 775 storage/logs
sudo chmod -R 775 bootstrap/cache

sudo chown -R alchemy:alchemy storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

#------------------------------------
# Database Setup
#------------------------------------

# Install MariaDB/MySQL
sudo apt install mariadb-server -y

# Create database
sudo mysql -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\`; DROP USER IF EXISTS '$DB_USERNAME'@'localhost'; CREATE USER '$DB_USERNAME'@'localhost' IDENTIFIED BY '$DB_PASSWORD'; GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'localhost'; FLUSH PRIVILEGES;"  

# Run migrations
php artisan migrate
php artisan db:seed

#------------------------------------
# Install Docker
#------------------------------------

# install docker
curl -sSL https://get.docker.com | sh
# groups/users
sudo usermod -aG docker $USER

#------------------------------------
# Run the Application
#------------------------------------

# Build frontend assets
npm run build

# Start the development server
php artisan serve --host=0.0.0.0 --port=8000

# RPi-Specific Notes
# -   Performance: The RPi5 should handle this well, but composer install and npm install may take longer than on a desktop
# -   Memory: If you encounter memory issues during Composer install, add swap space or use composer install --no-dev for production
# -   Docker: If using Docker, set APP_IMAGE_PREFIX="arm-" in your .env file as mentioned in the README
# -   Access: The app will be accessible at http://your-rpi-ip:80
#
# The application should run smoothly on RPi5's ARM64 architecture with its 8GB RAM and improved performance over previous generations.
