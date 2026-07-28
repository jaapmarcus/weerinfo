#!/bin/bash

if [ "$EUID" -eq 0 ]
        then echo "Do run this script as weerinfo user"
        echo "sudo -u weerinfo ./update.sh"
        exit;
fi

# Update the weerinfo panel and pull the latest changes from the repository, then clear and cache the routes and config, and finally bring the application back up.
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan down
git pull
/usr/bin/php8.5 /home/weerinfo/.composer/composer install --no-dev
npm ci
npm run build
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan route:clear
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan route:cache
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan cache:clear
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan config:clear
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan optimize
/usr/bin/php8.5 /home/weerinfo/web/weerinfo.nl/public_html/weerinfo/artisan up
