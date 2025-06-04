#!/bin/bash

composer install
php artisan key:generate
php artisan migrate --seed

npm install
npm run test
npm run dev

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

php artisan test
php artisan sync:translations
php artisan reverb:start
php artisan serve
