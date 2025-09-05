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
sudo apt install php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml php8.3-curl php8.3-zip php8.3-mbstring php8.3-gd php8.3-bcmath php8.3-intl -y

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
