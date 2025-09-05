#!/bin/bash

#------------------------------------
# Prerequisites
#------------------------------------

# Add php repos
curl -sSL https://packages.sury.org/php/apt.gpg | sudo tee /usr/share/keyrings/suryphp-archive-keyring.gpg > /dev/null
echo "deb [signed-by=/usr/share/keyrings/suryphp-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/sury-php.list

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.3
sudo apt install php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml php8.3-curl php8.3-zip php8.3-mbstring php8.3-gd php8.3-bcmath -y

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js (use NodeSource for latest version)
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y

# Install Git if not already installed
sudo apt install git -y

#------------------------------------
# Setup Application
#------------------------------------

# Clone the repository
git clone https://github.com/Flashpoint-Artists-Initiative/app-api.git
cd app-api

# Configure Environment
cp .env.example .env
# The .env file must exist and be filled out
# ! IMPORTANT ! Go edit the .env file now as this will be used for the rest of the procedure to build stuff
source .env

# Install PHP dependencies
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
php artisan serve --host=0.0.0.0 --port=80

# RPi-Specific Notes
# -   Performance: The RPi5 should handle this well, but composer install and npm install may take longer than on a desktop
# -   Memory: If you encounter memory issues during Composer install, add swap space or use composer install --no-dev for production
# -   Docker: If using Docker, set APP_IMAGE_PREFIX="arm-" in your .env file as mentioned in the README
# -   Access: The app will be accessible at http://your-rpi-ip:80
#
# The application should run smoothly on RPi5's ARM64 architecture with its 8GB RAM and improved performance over previous generations.
