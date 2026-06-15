# Setting up a development environment
1. Install Docker or an alternative provider (https://docs.ddev.com/en/stable/users/install/docker-installation/)
1. Install [DDEV](https://docs.ddev.com/en/stable/users/install/ddev-installation/)
2. Generate an encryption key
    
```
ddev artisan key:generate
```
3. Set up the database
```
ddev artisan migrate
# Optionally seed with fake data
ddev artisan db:seed
```
4. Set up NPM
```
ddev npm install
ddev npm run build
```
# Developer info

See https://laravel.com/docs and https://filamentphp.com/

# Other Info

Font used for logo: Amarante Regular
