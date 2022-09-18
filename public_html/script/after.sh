#!/bin/bash
rm -rf /var/www/public_html/* 
mv /home/ubuntu/project/* /var/www/public_html/
chown -R www-data:ftpu /var/www/public_html
chmod -R 775 /var/www/public_html
mkdir /home/ubuntu/project/
chown -R www-data:ftpu /home/ubuntu/project/
nginx -s reload
rm -rf /var/www/public_html/script
