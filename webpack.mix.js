const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */


mix.js('resources/js/app.js', 'public/js')
   .sass('resources/sass/app.scss', 'public/css')

   .styles('node_modules/nprogress/nprogress.css','public/css/nprogress.min.css')
   .scripts('node_modules/nprogress/nprogress.js','public/js/nprogress.min.js')

   .copy('node_modules/video.js/dist/video-js.min.css','public/css/video-js.min.css')
   .copy('node_modules/video.js/dist/video.min.js','public/js/video.min.js')

   .copy('node_modules/moment/min/moment-with-locales.min.js','public/js/moment-with-locales.min.js')

   .styles('node_modules/videojs-contextmenu-ui/dist/videojs-contextmenu-ui.css','public/css/videojs-contextmenu-ui.min.css')
   .copy('node_modules/videojs-contextmenu-ui/dist/videojs-contextmenu-ui.min.js','public/js/videojs-contextmenu-ui.min.js')

   .styles('node_modules/videojs-http-source-selector/dist/videojs-http-source-selector.css','public/css/videojs-http-source-selector.min.css')
   .copy('node_modules/videojs-http-source-selector/dist/videojs-http-source-selector.min.js','public/js/videojs-http-source-selector.min.js')
   .copy('node_modules/videojs-contrib-quality-levels/dist/videojs-contrib-quality-levels.min.js','public/js/videojs-contrib-quality-levels.min.js')

   .styles('node_modules/videojs-dvr/dist/videojs-dvr.css','public/css/videojs-dvr.min.css')
   .copy('node_modules/videojs-dvr/dist/videojs-dvr.min.js','public/js/videojs-dvr.min.js')

   .scripts('node_modules/videojs-titleoverlay/videojs-titleoverlay.js','public/js/videojs-titleoverlay.min.js')

   .scripts('node_modules/videojs-airplay/dist/videojs.airplay.js','public/js/videojs.airplay.min.js')
   .styles('node_modules/videojs-airplay/dist/videojs.airplay.css','public/css/videojs.airplay.min.css')

   .copy('node_modules/autolinker/dist/Autolinker.min.js','public/js/autolinker.min.js')

   .scripts('resources/js/dreambox.js', 'public/js/dreambox.min.js');

