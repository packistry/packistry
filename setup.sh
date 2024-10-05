touch /data/database.sqlite
ln -s /data/database.sqlite /var/www/html/database/database.sqlite

mkdir -p /data/archives
mkdir -p /var/www/html/storage/app
ln -s /data/archives /var/www/html/storage/app/private

php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan migrate --force

/usr/bin/supervisord  -c /etc/supervisord.conf
