# Setting up a development environment

## 1. Install PHP 8.3

https://www.php.net/manual/en/install.php

## 2. Enable PHP extensions
- curl
- fileinfo
- openssl
- zip

## 3. Install Composer

https://getcomposer.org/download/

## 4. Install git

https://git-scm.com/

## 5. Clone the project

    $ git clone https://github.com/Flashpoint-Artists-Initiative/app-api.git

## 6. Install dependencies
    
    $ cd app-api
    
    $ composer install

## 7. Generate an encryption key

    $ cp .env.example .env

    $ php artisan key:generate

## 8. Create the database

    $ php artisan migrate

## 9. Seed the database

    $ php artisan db:seed

## 10. Generate JWT certs

    $ md storage/certs

    $ openssl genrsa -out storage/certs/jwt-rsa-4096-private.pem 4096

    $ openssl rsa -in storage/certs/jwt-rsa-4096-private.pem -outform PEM -pubout -out storage/certs/jwt-rsa-4096-public.pem

## 11. Generate API Docs (Optional)

    $ php artisan scribe:generate

    Open .scribe/docs/index.html in a browser to view the API docs

    Import .scribe/docs/collection.json to use in Postman

## 12. Install docker

https://docs.docker.com/

## 13. Run the app in docker
If you are on an ARM based system, set `APP_IMAGE_PREFIX="arm-"` in `.env`

    $ docker-compose up

# Developer info

See https://laravel.com/docs and https://filamentphp.com/

# Other Info

Font used for logo: Amarante Regular