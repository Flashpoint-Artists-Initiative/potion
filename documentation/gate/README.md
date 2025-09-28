# Purpose

All local gate tech and documentation is based on using a Raspberry Pi 5. This device is lower power, has many hardware solutions for an attached UPS, is lower power, and run a full linux OS. If you want to roll-out your own computer solution, you'll have to figure it out.

There are several steps to deploying a fully remote and isolated (Not on the internet) Gate solution for running the Portal application for use at your event:

-   Construct the rPi5 hardware platform
-   Configure OS and Networking
-   Install Potion Application
-   Configure Potion for YOUR Event/Org

# Construct Raspberry Pi 5 (rPi5) computer/platform

TODO:

# Configure OS and Networking

## Burn SD Card

Device: Raspberry Pi 5
Operating System: Raspberry Pi OS Lite (64 bit)
Hostname: gate
User: gate
Passwd: <PASSWORD>

## Physical conections

When using this procedure, it is important how you physically hook up the rPi5. Because we'll
be doing some more advanced networking/hosting, you'll need a wired connection to eth0 to
start.
DO NOT USE THE WIFI CONNECTION OF THE RPI5. We will need to take control of this interface to
host a wifi network with NetworkManager later

## Initial Setup and reboot

```
sudo su
apt update
apt upgrade -y

# Update the system locale
grep -q -F 'LC_ALL=en_US.UTF-8' /etc/environment || echo 'LC_ALL=en_US.UTF-8' >> /etc/environment
sed -i '/en_US.UTF-8 UTF-8/s/^#//g' /etc/locale.gen
echo "LANG=en_US.UTF-8" > /etc/locale.conf
locale-gen en_US.UTF-8

raspi-config nonint do_hostname gate
raspi-config nonint do_change_timezone US/Eastern
raspi-config nonint do_wifi_country US
raspi-config nonint do_expand_rootfs
raspi-config nonint do_net_names 1  # Disable predictive naming
reboot
```

## Install all dependencies and tools

```
# Add php repos
curl -sSL https://packages.sury.org/php/apt.gpg | sudo tee /usr/share/keyrings/suryphp-archive-keyring.gpg > /dev/null
echo "deb [signed-by=/usr/share/keyrings/suryphp-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/sury-php.list

# Update system
sudo su
apt update && sudo apt upgrade -y

# Install All of the things
apt install dnsutils ncdu tree tmux git certbot mariadb-server libnss3-tools nginx php8.4 php8.4-cli php8.4-fpm php8.4-mysql php8.4-xml php8.4-curl php8.4-zip php8.4-mbstring php8.4-gd php8.4-bcmath php8.4-intl php8.4-sqlite3 php8.4-redis -y

# Enable Dnsmasq
cat > /etc/NetworkManager/conf.d/00-use-dnsmasq.conf<< EOF
# /etc/NetworkManager/conf.d/00-use-dnsmasq.conf
#
# This enabled the dnsmasq plugin.
[main]
dns=dnsmasq
EOF

# Configure HotSpot
nmcli connection add type wifi ifname wlan1 con-name hotspot autoconnect yes ssid alchemyGate 802-11-wireless.mode ap 802-11-wireless.band bg ipv4.method shared wifi-sec.key-mgmt wpa-psk wifi-sec.psk welcomehome wifi-sec.pmf 1
reboot
```

## Test Wifi Access to rPi5

Ensure that your scanning devices can connect to the rPi5 after it has finished rebooting

# Install the Potion App

## Install Node

```
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
source ~/.bashrc
npm install
npm run build

```

## Configure PHP

```
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Clone App

```
# Clone the repository
git clone https://github.com/Flashpoint-Artists-Initiative/potion.git potion-app
cd potion-app

# Install PHP dependencies
composer install --no-dev
```

## Add your Organizations dependencies

### The Production Environment File

You will have your own `.env` file that contains all the production info for your installation. If you have not already configured this, you can bootstrap this process by using the example contained in this repo. If you have already made your own `.env` file, `scp` it to this rPi5 in the `~/potion` folder now

### Your Production Certificates

The Gate Potion app expects you to use real and valid certificates. We suggest getting them from LetsEncrypt. If you already have your own certificates, `scp` them now. If not, read the Certs.md file and generate them now

### Setup Database

#### configure MariaDB Server instance

```
sudo su
mysql_secure_installation

        ##  NOTE: RUNNING ALL PARTS OF THIS SCRIPT IS RECOMMENDED FOR ALL MariaDB
        ##        SERVERS IN PRODUCTION USE!  PLEASE READ EACH STEP CAREFULLY!
        ##
        ##  In order to log into MariaDB to secure it, we'll need the current
        ##  password for the root user. If you've just installed MariaDB, and
        ##  haven't set the root password yet, you should just press enter here.
        ##
        ##  Enter current password for root (enter for none):
        ##  OK, successfully used password, moving on...
        ##
        ##  Setting the root password or using the unix_socket ensures that nobody
        ##  can log into the MariaDB root user without the proper authorisation.
        ##
        ##  You already have your root account protected, so you can safely answer 'n'.
        ##
        ##  Switch to unix_socket authentication [Y/n] n
        ##   ... skipping.
        ##
        ##  You already have your root account protected, so you can safely answer 'n'.
        ##
        ##  Change the root password? [Y/n] y
        ##  New password:
        ##  Re-enter new password:
        ##  Password updated successfully!
        ##  Reloading privilege tables..
        ##   ... Success!
        ##
        ##
        ##  By default, a MariaDB installation has an anonymous user, allowing anyone
        ##  to log into MariaDB without having to have a user account created for
        ##  them.  This is intended only for testing, and to make the installation
        ##  go a bit smoother.  You should remove them before moving into a
        ##  production environment.
        ##
        ##  Remove anonymous users? [Y/n] y
        ##   ... Success!
        ##
        ##  Normally, root should only be allowed to connect from 'localhost'.  This
        ##  ensures that someone cannot guess at the root password from the network.
        ##
        ##  Disallow root login remotely? [Y/n] y
        ##   ... Success!
        ##
        ##  By default, MariaDB comes with a database named 'test' that anyone can
        ##  access.  This is also intended only for testing, and should be removed
        ##  before moving into a production environment.
        ##
        ##  Remove test database and access to it? [Y/n] y
        ##   - Dropping test database...
        ##   ... Success!
        ##   - Removing privileges on test database...
        ##   ... Success!
        ##
        ##  Reloading the privilege tables will ensure that all changes made so far
        ##  will take effect immediately.
        ##
        ##  Reload privilege tables now? [Y/n] y
        ##   ... Success!
        ##
        ##  Cleaning up...
        ##
        ##  All done!  If you've completed all of the above steps, your MariaDB
        ##  installation should now be secure.
        ##
        ##  Thanks for using MariaDB!
```

#### Import Production Database

You will need a production dump of the database that you've already hosted publically. The gate system is meant to be an offline resource so that Potion functions can be run without an internet connection at your event. Because of this you'll need to:

-   Freeze your production system (Readonly everything so we can transfer control to the gate system)
-   get a mysql dump of your production database
    -   <CMD>
-   transfer and import your production data to the gate system

```
# The Database name is the same name you've given in your .env file
# Extract the value of MY_VARIABLE
DB_DUMP_FILE=<PUT_YOUR_FILE_HERE!!!>
DB_DATABASE=$(grep "^DB_DATABASE=" "/home/gate/potion-app/.env" | cut -d '=' -f 2-)
DB_USER=$(grep "^DB_USERNAME=" "/home/gate/potion-app/.env" | cut -d '=' -f 2-)
DB_PASSWORD=$(grep "^DB_PASSWORD=" "/home/gate/potion-app/.env" | cut -d '=' -f 2-)

# Ensure any old data is discarded
mysql -u root -p -e "DROP DATABASE IF EXISTS \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\`;"

# Import your production data
mysql -u root -p $DB_DATABASE < ${DB_DUMP_FILE}
mysql -u root -p -e "CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASSWORD'; GRANT ALL PRIVILEGES ON \`$DB_DATABASE\`.* TO '$DB_USER'@'localhost'; FLUSH PRIVILEGES;"

# Run migrations for app
exit
cd /home/gate/potion-app
php artisan migrate
php artisan permission:populate
```

### DNS for your ORG

```
sudo su
GATE_DNS=$(grep "^GATE_DNS=" "/home/gate/potion-app/.env" | cut -d '=' -f 2-)

# Configure DNS for the Potion App
cat > /etc/NetworkManager/dnsmasq-shared.d/00-dns-potion-app.conf<< EOF
address=/$GATE_DNS/10.42.0.1
EOF
```

### Nginx SSL

```
# Create configuration to redirect SSL to our local php app
cat > /etc/nginx/sites-available/$GATE_DNS<< EOF
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name localhost;
    ssl_certificate /etc/letsencrypt/live/$GATE_DNS-0001/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$GATE_DNS-0001/privkey.pem;

    # For Development: Requires adding the following to /etc/hosts
    # 127.0.0.1 potion.dev

    location / {
        proxy_set_header   X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto \$scheme;
        proxy_set_header   X-Forwarded-Port 443;
        proxy_set_header   X-Real-IP \$remote_addr;
        proxy_set_header   Host \$host;
        proxy_pass         http://0.0.0.0:8000;
    }
}
EOF

# Enable this config
ln -s /etc/nginx/sites-available/$GATE_DNS /etc/nginx/sites-enabled/$GATE_DNS

# reload nginx and ensure it all works
nginx -s reload
```

## Auto-Launch Potion App

```
# Configure System Service to auto-launch on boot
cat > /etc/systemd/system/potion.service<< EOF
[Unit]
Description=Potion Application
After=multi-user.target

[Service]
Type=simple
ExecStart=/home/gate/potion/documentation/gate/scripts/startup.sh
Restart=always

[Install]
WantedBy=multi-user.target
EOF

systemctl enable potion
```
