#!/bin/bash
mv /var/www/public_html/images /home/ubuntu/project/
mv /var/www/public_html/images2 /home/ubuntu/project/
mv /var/www/public_html/files /home/ubuntu/project/
cp -f /var/www/public_html/config.local.php /home/ubuntu/project/
chmod -R 755 /home/ubuntu/project
chown -R www-data:www-data /home/ubuntu/project
cd /home/ubuntu/project/
find design -type f -print0 | xargs -0 chmod 644
find images -type f -print0 | xargs -0 chmod 644
find var -type f -print0 | xargs -0 chmod 644
