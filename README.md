# Dreambox ReStream
Dreambox ReStream is a [Laravel PHP framework](https://laravel.com/) application that allows you to watch television on your mobile phone while you are not at home. Using [FFMPEG](https://ffmpeg.org/) it can transcode a live tv channel from your Dreambox to an [HLS live stream](https://developer.apple.com/streaming/) wich can be watched on a Desktop, mobile or any other device that supports [HLS live streaming](https://en.wikipedia.org/wiki/HTTP_Live_Streaming).
**This software is made for PERSONAL usage. It does not facilitate free television streaming!**
![Dreambox ReStream overview image](https://theyosh.nl/sites/default/files/u1/DreamboxRestreamHowTo.png "Dreambox ReStream overview")

# Features
- Stream any channel to your mobile
- Stream any recording to your mobile
- Electronic program guide
- [Ambilight effect](https://en.wikipedia.org/wiki/Bias_lighting)
- Hardware enabled transcoding (VAAPI, NVIDIA)

# Translations
- English

# Installation
Dreambox ReStream requires a webserver with PHP enabled. And FFMPEG for transcoding. This software is tested with NGINX on an Ubuntu server. To get it working smoothly a decent upstream connection (minimal 512 Kbps) is needed. And ofcourse an Enigma2 enabled TV Decoder is needed. Best known are the Dreamboxes.

## Vagrant installation
If you have Vagrant installed already, then you can skip the installation below by using the vagrant file that is provided with this software. Just run
```sh
vagrant up
```
and everything will be installed and configured.

## Install the dependencies
```sh
sudo apt install git nginx php-fpm php-cli php-mbstring php-xml ffmpeg
```
## Install PHP Composer
Install php composer according to the website: https://getcomposer.org/download/

## Install Dreambox ReStream
```sh
git clone https://github.com/theyosh/DreamboxReStream.git
cd DreamboxReStream
php composer.phar install
touch database/restream.sqlite
cp .env.example .env
sed -i -e 's@APP_NAME=.*@APP_NAME=DreamboxReStream@g' .env
sed -i -e 's@APP_DEBUG=.*@APP_DEBUG=false@g' .env
sed -i -e 's@DB_CONNECTION=.*@DB_CONNECTION=sqlite@g' .env
sed -i -e 's@DB_DATABASE=.*@DB_DATABASE='`pwd`'/database/restream\.sqlite@g' .env
php artisan key:generate
php artisan migrate
php artisan storage:link
```
## Configure NGINX
```sh
cp nginx.conf.example nginx.conf
sed -i -e 's@root .*@root '`pwd`'/public;@g' nginx.conf
sed -i -e 's@server_name .*@server_name [YOUR_DOMAIN_NAME];@g' .env
sudo ln -s `pwd`/nginx.conf /etc/nginx/sites-enabled/dreambox_restream.conf
sudo nginx -t
sudo service nginx reload
```

# Setup
When the installation is finished, go with a browser to your configured domain. This will load a setup screen when you can enter the needed information to connect to your TV encoder.

The amount of 'Streaming profiles' thant can be used is depending on the CPU power. So start with 1 or 2 profiles, and when that works well, you can later add more profiles until your CPU gets loaded to much.

