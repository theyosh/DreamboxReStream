#!/bin/bash

apt-get -y update && apt-get -y full-upgrade
apt-get -y install ca-certificates apt-transport-https software-properties-common unzip lsb-release ca-certificates
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
apt-get -y update && apt-get -y install git nginx-light php8.1-fpm php8.1-cli php8.1-mbstring php8.1-xml php8.1-sqlite3 php8.1-zip ffmpeg ntp

usermod -G vagrant -a www-data
usermod -G www-data -a vagrant

cd /home/vagrant/dreamboxrestream/

rm /etc/nginx/sites-enabled/default || true
su -c 'cp nginx.conf.example nginx.conf' vagrant
sed -i -e 's@root .*@root '`pwd`'/public;@g' nginx.conf
mv `pwd`/nginx.conf /etc/nginx/sites-enabled/dreambox_restream.conf
service nginx restart


EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
RESULT=$?
rm composer-setup.php

su -c 'php composer.phar install --no-dev' -s /bin/bash vagrant
su -c 'touch database/restream.sqlite' -s /bin/bash vagrant
su -c 'cp .env.example .env' -s /bin/bash vagrant

sed -i -e 's@APP_NAME=.*@APP_NAME=DreamboxReStream@g' .env
sed -i -e 's@APP_DEBUG=.*@APP_DEBUG=false@g' .env
sed -i -e 's@DB_CONNECTION=.*@DB_CONNECTION=sqlite@g' .env
sed -i -e 's@DB_DATABASE=.*@DB_DATABASE='`pwd`'/database/restream\.sqlite@g' .env

su -c 'php artisan key:generate' -s /bin/bash vagrant
su -c 'php artisan migrate' -s /bin/bash vagrant
su -c 'php artisan storage:link' -s /bin/bash vagrant
su -c 'php artisan version:absorb' -s /bin/bash vagrant
su -c 'php artisan version:timestamp' -s /bin/bash vagrant

rm /etc/issue.net
ln -s /etc/issue /etc/issue.net
sh -c echo '@reboot /home/vagrant/dreamboxrestream/vm_ip.sh' >> /var/spool/cron/crontabs/root

