#!/bin/bash

apt-get -y update && apt-get -y upgrade && apt-get -y install git nginx php-fpm php-cli php-mbstring php-xml php-sqlite3 ffmpeg ntp

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
#exit $RESULT

su -c 'php composer.phar install --no-dev' vagrant
su -c 'touch database/restream.sqlite' vagrant
su -c 'cp .env.example .env' vagrant

sed -i -e 's@APP_NAME=.*@APP_NAME=DreamboxReStream@g' .env
sed -i -e 's@APP_DEBUG=.*@APP_DEBUG=false@g' .env
sed -i -e 's@DB_CONNECTION=.*@DB_CONNECTION=sqlite@g' .env
sed -i -e 's@DB_DATABASE=.*@DB_DATABASE='`pwd`'/database/restream\.sqlite@g' .env

su -c 'php artisan key:generate' vagrant
su -c 'php artisan migrate' vagrant
su -c 'php artisan storage:link' vagrant
