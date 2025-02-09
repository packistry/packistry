envsubst < /etc/supervisord.tpl > ~/supervisord.conf

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan migrate --force

/usr/bin/supervisord -c ~/supervisord.conf
