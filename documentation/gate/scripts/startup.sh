# This will start the local potion app on HTTP
# HTTPS is hosted by nginx and will forward to port 8000
cd /home/gate/potion/
php artisan serve --host=0.0.0.0 --port=8000