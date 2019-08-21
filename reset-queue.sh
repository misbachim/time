pkill -f "php /var/www/html/artisan queue:work --tries=3"
./process-queue.sh
