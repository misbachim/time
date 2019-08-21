#!/bin/bash

case $(ps -aux | grep -cs "[a]rtisan queue:work") in
0)
    echo "Starting queue:work -- $(date)" >> /var/www/html/storage/logs/worker.log;
    /usr/local/bin/php /var/www/html/artisan queue:work --tries=3 --memory=512 --timeout=10800 >> /var/www/html/storage/logs/worker.log &
    ;;
*)
    # already started
    ;;
esac
