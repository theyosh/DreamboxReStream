# Dreambox ReStream
Dreambox ReStream is a [Laravel PHP framework](https://laravel.com/) application that allows you to watch television on your mobile phone while you are not at home. Using [FFMPEG](https://ffmpeg.org/) it can transcode a live tv channel from your Dreambox to an [HLS live stream](https://developer.apple.com/streaming/) wich can be watched on a Desktop, mobile or any other device that supports [HLS live streaming](https://en.wikipedia.org/wiki/HTTP_Live_Streaming).
**This software is made for PERSONAL usage. It does not facilitate free television streaming!**
![Dreambox ReStream overview image](https://theyosh.nl/sites/default/files/u1/DreamboxRestreamHowTo.png "Dreambox ReStream overview")

# Features
- Stream any channel to your mobile
- Stream any recording to your mobile
- Electronic program guide
- [Ambilight effect](https://en.wikipedia.org/wiki/Bias_lighting)

# Translations
- English

# Installation
Dreambox ReStream requires a webserver with PHP enabled. And FFMPEG for transcoding. This software is tested with NGINX on an Ubuntu server. To get it working smoothly a decent upstream connection (minimal 512 Kbps) is needed. And ofcourse an Enigma2 enabled TV Decoder is needed. Best know are the Dreamboxes.

## Install the dependencies.
```sh
$ sudo apt install git nginx php-fpm php-cli php-mbstring php-xml ffmpeg
```
## Install PHP Composer
Install php composer according to the website: https://getcomposer.org/download/

## Install Dreambox ReStream
```sh
$ git clone https://github.com/theyosh/DreamboxReStream.git
$ cd DreamboxReStream
$ php composer.phar install
$ touch database/restream.sqlite
$ php artisan migrate:fresh
$ php artisan storage:link
```