#!/bin/bash

crontab -l -u www-data > /tmp/mycron;
echo "* * * * * /var/www/html/process-queue.sh >> /dev/null 2>&1" >> /tmp/mycron;
echo "* * * * * /var/www/html/schedule-run.sh >> /dev/null 2>&1" >> /tmp/mycron;
crontab -u www-data /tmp/mycron;
rm /tmp/mycron;
