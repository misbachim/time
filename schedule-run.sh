#!/bin/bash

case $(ps -aux | grep -cs "[a]rtisan schedule:run") in
0)
    echo "Starting schedule:run -- $(date)" >> /var/www/html/storage/logs/scheduler.log;
    /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/www/html/storage/logs/scheduler.log &
    ;;
*)
    # already started
    ;;
esac
