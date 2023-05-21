@extends('layouts.app')

@section('title', $dreambox->name)

@push('styles')
  <link href="{{ URL::asset('css/video-js.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs.airplay.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-contextmenu-ui.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-http-source-selector.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/silvermine-videojs-chromecast.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
  <script type="text/javascript" src="{{ URL::asset('js/moment-with-locales.min.js') }}" ></script>
  <script type="text/javascript" src="{{ URL::asset('js/video.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-titleoverlay.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs.airplay.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-contextmenu-ui.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-contrib-quality-levels.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-http-source-selector.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/silvermine-videojs-chromecast.min.js') }}"></script>
  <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>
  <script type="text/javascript" src="{{ URL::asset('js/dreambox.min.js') }}"></script>
@endpush

@section('modal_content')
@endsection

@section('javascript')
<script type="text/javascript">
const dreambox_id = {{$dreambox->id}};
let channel_refresh_list = {};
let channel_refresh_timer = null;
let ambilight_on = false;

function start_dreambox() {
  $.getJSON('{{ route('status_dreambox', ['dreambox' => $dreambox->id])}}', function( data ) {
    if (data.id !== undefined) {
      stream(data.id,data.type);
      $('.vjs-button.vjs-icon-cancel').addClass('online');
    }

    setTimeout(function(){
      load_other_bouquets();
    },100);
  });

  setInterval(function(){
    start_progress_bar();

    $.getJSON('{{ route('status_dreambox', ['dreambox' => $dreambox->id])}}', function( data ) {
      if (!data.online) {
        $('#offline_message').show();
      } else if (data.running) {
        $('.vjs-button.vjs-icon-cancel').addClass('online');
        if (dreambox_player.source.currentprogram != null && (dreambox_player.source.currentprogram.name != data.currentprogram.name || dreambox_player.source.currentprogram.start != data.currentprogram.start)) {
          dreambox_player.source = data;
          update_current_source();
        }
      } else {
        $('.vjs-button.vjs-icon-cancel').removeClass('online');
        dreambox_player.source = null;
      }
    });
  },60 * 1000);
}

let dreambox_player = null;
let overlay_timer = null;
function init_video_player() {
  dreambox_player = videojs('dreambox-video',{
    html5: {
      vhs: {
        withCredentials: true,
        handleManifestRedirects: true,
        overrideNative: true,
      }
    },
    techOrder: [ 'chromecast', 'html5' ], // You may have more Tech, such as Flash or HLS
    autoplay: 'any',
    plugins: {
        'airplayButton' : {},
        'httpSourceSelector': {'default' : 'auto'},
        'chromecast': {}
    }
  });
  dreambox_player.source = null;

  dreambox_player.titleoverlay({
    title: generate_player_title_overlay(),
    floatPosition: 'right',
    margin: '10px',
    fontSize: '1.5em',
  });

//  dreambox_player.dvr();

  dreambox_player.contextmenuUI({
    content: [{
        // A plain old link.
        href: 'https://github.com/theyosh/DreamboxReStream',
        label: 'Dreambox ReStream @version'
      },{
        // A link with a listener. Its `href` will automatically be `#`.
        label: 'Ambilight',
        listener: function() {
          ambi();
        }
      }]
  });

  dreambox_player.on('playing',function(event){
    $('#dreamboxModal').modal('hide')
  });

  dreambox_player.on('ready',function(event){
    dreamboxAmbilight.attachPlayer(this);
  });

  dreambox_player.on('mouseover', function(event) {
    clearTimeout(overlay_timer);
    dreambox_player.titleoverlay.updateTitle(generate_player_title_overlay());
    dreambox_player.titleoverlay.showOverlay();

    overlay_timer = setTimeout(function(){
      dreambox_player.titleoverlay.hideOverlay();
    },5 * 1000);
  });

  dreambox_player.httpSourceSelector();
  dreambox_player.getChild('ControlBar').addChild('button', {
        controlText: 'Kill streamer',
        className: 'vjs-icon-cancel',

        clickHandler: function(event) {
            videojs.log('Clicked');
            dreambox_player.pause();
            this.removeClass("online");
            $.post('{{ route('stop_streaming', ['dreambox' => $dreambox->id])}}',function(data){ });
        }
    });
}

function load_other_bouquets() {
  $.getJSON('{{ route('load_dreambox', ['dreambox' => $dreambox->id])}}', function( data ) {
    $.each(data.bouquets, function(bqcounter, bouquet_data) {
      let bouquet = $('#bouquet' + bouquet_data.id);
      if (bouquet.length > 0) {
        $.each(bouquet_data.channels, function(chcounter, channel_data) {
          let channel = bouquet.find('a[data-type="channel"][data-id="'+ channel_data.id + '"]');
          if (channel.length == 0) {

            let meta = $('<span>').addClass('program-meta');
            meta.append($('<button>').addClass('btn badge badge-warning epg').attr('data-channel',channel_data.id).html('<small>EPG</small>'));
            if (channel_data.is_hd) {
              meta.append($('<small>').addClass('badge badge-dark hd').text('HD'))
            } else if (channel_data.is_4k) {
              meta.append($('<small>').addClass('badge badge-dark hd').text('4K'))
            }

            channel = $('<a>').addClass('list-group-item list-group-item-action').attr({'data-type': 'channel', 'data-id' : channel_data.id, 'href':'#'});
            if (channel_data.picon && '' != channel_data.picon) {
                channel.css('background-image','url(' + channel_data.picon + ')');
            }

            channel.append($('<div>').addClass('d-flex w-100 justify-content-between').append('<h5 class="mb-1">' + channel_data.name + '</h5>'))
            channel.append($('<p>').addClass('mb-1 program-now'));
            channel.append($('<small>').addClass('program-next'));

            bouquet.find('div.list-group').append(meta);
            bouquet.find('div.list-group').append(channel);
          }
          update_channel(channel_data);
        });
      }
      bouquet.find('.badge.badge-primary').text(data.length);
    });
    refresh_channel_list();
    init_stream_actions();
    load_recordings();
    // Need to do some clean up when channels disapeare....
  });
}

function load_recordings() {
  $.getJSON('{{ route('recordings', ['dreambox' => $dreambox->id])}}', function( data ) {
    let bouquet = $('#bouquet_recordings');
    if (bouquet.length > 0) {
      $.each(data, function(reccounter, recording_data) {
        let recording = bouquet.find('a[data-type="recording"][data-id="'+ recording_data.id + '"]');

        if (recording.length == 0) {

          let meta = $('<span>').addClass('program-meta');
          if (recording_data.channel != null && recording_data.channel.is_hd) {
            meta.append($('<small>').addClass('badge badge-dark hd').text('HD'))
          } else if (channel_data.is_4k) {
            meta.append($('<small>').addClass('badge badge-dark hd').text('4K'))
          }

          recording = $('<a>').addClass('list-group-item list-group-item-action').attr({'data-type': 'recording', 'data-id' : recording_data.id, 'href':'#'});
          if (recording_data.channel != null && recording_data.channel.picon && '' != recording_data.channel.picon) {
              recording.css('background-image','url(' + recording_data.channel.picon + ')');
          }

          recording.append($('<div>').addClass('d-flex w-100 justify-content-between').append('<h5 class="mb-1">' + recording_data.name + '</h5>'))
          recording.append($('<p>').addClass('mb-1 program-now'));
          recording.append($('<small>').addClass('program-next'));

          bouquet.find('div.list-group').append(meta);
          bouquet.find('div.list-group').append(recording);
        }
        update_recording(recording_data);
      });
      bouquet.find('.badge.badge-primary').text(data.length);
    }
    // Need to do some clean up when recordings disapeare....
    init_stream_actions();
  });
}

class DreamboxAmbilight {
/**
 * @author Sergey Chikuyonok (serge.che@gmail.com)
 * @link http://chikuyonok.ru - http://chikuyonok.ru/2010/03/ambilight-video/
 */

  constructor() {
    this.options = {
		brightness: 2.7,    // ambilight brightness coeff
		saturation: 1.4,    // ambilight saturation coeff
		lamps: 5,           // number of glowing lamps.
		blockSize: 20,      // width of image sampling block. Larger value means more accurate but result but slower performance
		delay: 100,
	};

    this.left_light_canvas = document.createElement('canvas');
    this.left_light_canvas.className = 'ambilight-left';

    this.left_light_mask = new Image();
    this.left_light_mask.src = '{{ URL::asset('images/mask4-left.png') }}';

    this.right_light_canvas = document.createElement('canvas');
    this.right_light_canvas.className = 'ambilight-right';

    this.right_light_mask = new Image();
    this.right_light_mask.src = '{{ URL::asset('images/mask4-right.png') }}';

    this.buffer = document.createElement('canvas');
    this.bufferCtx = this.buffer.getContext('2d');

    this.fps_timer = null;
    this.running = false;
    this.last_update = 0;
  }

  attachPlayer(player) {
    this.player = player;
    this.video = player.children_[0];
    this.video.parentNode.appendChild(this.left_light_canvas);
    this.video.parentNode.appendChild(this.right_light_canvas);
    player.on('play', (e) => {
        this.start();
    });
    player.on('pause', (e) => {
        this.stop();
    });

    let vjplayer = player;
    player.on('fullscreenchange', (e) => {

        console.log('Stop ambilight due to fullscreen',vjplayer.isFullscreen());

        if (vjplayer.isFullscreen()) {
            console.log('Stop ambilight due to fullscreen');
            this.stop();
        } else {
            console.log('Start ambilight due to fullscreen');
            this.start();
        }
    });
  }

  start() {
    if (this.running) {
      // Only start once...
      return;
    }
    this.running = $('div#ambilightModal.modal.fade.show').length == 1;
    this.fps = 0;
    clearInterval(this.fps_timer);
    let self = this;
    this.fps_timer = setInterval(function(){
      console.log('Current ambilight fps: ' + self.fps / 60.0);
      self.fps = 0.0;
    },60000);

    this.last_update = 0;
    $(this.left_light_canvas).show();
    $(this.right_light_canvas).show();
    this.ambilightLoop();
  }

  stop() {
    this.running = false;
    $(this.left_light_canvas).hide();
    $(this.right_light_canvas).hide();
  }

    drawLight(side) {

      let light_canvas = ('left' == side ? this.left_light_canvas : this.right_light_canvas);
      let light_mask   = ('left' == side ? this.left_light_mask : this.right_light_mask);

		/** @type {CanvasRenderingContext2D} */
		let ctx = light_canvas.getContext('2d');

		let midcolors = this.getMidColors(side);

		let grd = ctx.createLinearGradient(0, 0, 0, light_canvas.height);
        let il = midcolors.length;
		for (let i = 0; i < il; i++) {
			this.adjustColor(midcolors[i]);

			grd.addColorStop(i / il, 'rgb(' + midcolors[i].join(',') + ')');
		}

		ctx.fillStyle = grd;
		ctx.fillRect(0, 0, light_canvas.width, light_canvas.height);

		let gco = ctx.globalCompositeOperation;
		ctx.globalCompositeOperation = 'destination-in';
		ctx.drawImage(light_mask, 0, 0, light_mask.width, light_mask.height, 0, 0, light_canvas.width, light_canvas.height);
		ctx.globalCompositeOperation = gco;
	}


  createSnapshot() {
    // Scale 1:1
	//buffer.width = this.video.videoWidth || this.video.width;
	//buffer.height = this.video.videoHeight || this.video.height;

    // Scale down....
    this.buffer.width = this.video.clientWidth || this.video.width;
    this.buffer.height = this.video.clientHeight || this.video.height;

    // Get source size/resolution
	let vw = this.video.videoWidth || this.video.width;
	let vh = this.video.videoHeight || this.video.height;

    // Copy frame from movie to canvas buffer
    this.bufferCtx.drawImage(this.video, 0, 0, vw, vh, 0, 0, this.buffer.width, this.buffer.height);

  }



    /**
	 * Calculates middle color for pixel block
	 * @param {CanvasPixelArray} data Canvas pixel data
	 * @param {Number} from Start index of pixel data
	 * @param {Number} to End index of pixel data
	 * @return {Array} RGB-color
	 */
	 calcMidColor(data, from, to) {
		let result = [0, 0, 0];
		let totalPixels = (to - from) / 4;

		for (let i = from; i <= to; i += 4) {
			result[0] += data[i];
			result[1] += data[i + 1];
			result[2] += data[i + 2];
		}

		result[0] = (result[0] / totalPixels) | 0;
		result[1] = (result[1] / totalPixels) | 0;
		result[2] = (result[2] / totalPixels) | 0;

		return result;
	}


	/**
	 * Returns array of midcolors for one of the side of buffer canvas
	 * @param {String} side Canvas side where to take pixels from. 'left' or 'right'
	 * @return {Array} Array of RGB colors
	 */
	 getMidColors(side) {
		let w = this.buffer.width;
		let h = this.buffer.height;
		let lamps = this.options.lamps;
		let blockWidth = this.options.blockSize;
		let blockHeight = Math.ceil(h / lamps);
		let pxl = blockWidth * blockHeight * 4;
        let result = [];

        try {
            let imgData = this.bufferCtx.getImageData(side == 'right' ? w - blockWidth : 0, 0, blockWidth, h);
            let totalPixels = imgData.data.length;
            for (let i = 0; i < lamps; i++) {
                let from = i * w * blockWidth;
                result[i] = this.calcMidColor(imgData.data, i * pxl, Math.min((i + 1) * pxl, totalPixels - 1));
            }
        } catch (e) {
        }
		return result;
	}

	/**
	 * Convers RGB color to HSV model
	 * @param {Array} RGB color
	 * @return {Array} HSV color
	 */
	 rgb2hsv(color) {
		let r = color[0] / 255;
		let g = color[1] / 255;
		let b = color[2] / 255;

		let x, val, d1, d2, hue, sat;

		x = Math.min(Math.min(r, g), b);
		val = Math.max(Math.max(r, g), b);
		if (x == val) {
			return false;
		}

		d1 = (r == x) ? g - b : ((g == x) ? b - r : r - g);
		d2 = (r == x) ? 3 : ((g == x) ? 5 : 1);

		color[0] = (((d2 - d1 / (val - x)) * 60) | 0) % 360;
		color[1] = (((val - x) / val) * 100) | 0;
		color[2] = (val * 100) | 0;
		return true;
	}

	/**
	 * Convers HSV color to RGB model
	 * @param {Array} RGB color
	 * @return {Array} HSV color
	 */
	 hsv2rgb(color) {
		let h = color[0],
			s = color[1],
			v = color[2];

		let r, g, a, b, c;
        s = s / 100;
        v = v / 100;
        h = h / 360;

		if (s > 0) {
			if (h >= 1) h = 0;

			h = 6 * h;
			let f = h - (h | 0);
			// don't need accurate results here, use |0 instead of Math.round()
			a = (255 * v * (1 - s)) | 0;
			b = (255 * v * (1 - (s * f))) | 0;
			c = (255 * v * (1 - (s * (1 - f)))) | 0;
			v = (255 * v) | 0;

			switch (h | 0) {
				case 0: r = v; g = c; b = a; break;
				case 1: r = b; g = v; b = a; break;
				case 2: r = a; g = v; b = c; break;
				case 3: r = a; g = b; b = v; break;
				case 4: r = c; g = a; b = v; break;
				case 5: r = v; g = a; b = b; break;
			}

			color[0] = r || 0;
			color[1] = g || 0;
			color[2] = b || 0;
		} else {
			color[0] = color[1] = color[2] = (v * 255) | 0;
		}
	}

	/**
	 * Adjusts color lightness and saturation
	 * @param {Array} RGB color
	 * @return {Array}
	 */
	 adjustColor(color) {
		let ok = this.rgb2hsv(color);
		if (ok) {
			color[1] = Math.min(100, color[1] * this.options.saturation)
			// color[2] = Math.min(100, color[2] * getOption('brightness'));
			color[2] = 90;
			this.hsv2rgb(color);
		}
		return color;
	}

	  ambilightLoop() {
        if (dreamboxAmbilight.running) {
			let now = +new Date;
			if (now - dreamboxAmbilight.last_update >= dreamboxAmbilight.options.delay) {
                dreamboxAmbilight.createSnapshot();
                dreamboxAmbilight.drawLight('left');
                dreamboxAmbilight.drawLight('right');
                dreamboxAmbilight.last_update = now;
                dreamboxAmbilight.fps += 1;
            }
            requestAnimationFrame(dreamboxAmbilight.ambilightLoop);
        }
	}
}

function ambi() {
    let modal = $('#ambilightModal');
    modal.find('.modal-body').html($('#dreambox-video').parent());

    modal.off('hidden.bs.modal').on('hidden.bs.modal', function (e) {
        $('#mainplayer').html(modal.find('#dreambox-video').parent());
        dreamboxAmbilight.stop();
    });

    modal.off('shown.bs.modal').on('shown.bs.modal', function (e) {
      $('div.modal-backdrop.fade.show').css('opacity',0.85);
      dreamboxAmbilight.start();
    });
    modal.modal();
}

const dreamboxAmbilight = new DreamboxAmbilight();
$(function(){
  // Set the local to the application locale
  moment.locale('{{ str_replace('_', '-', app()->getLocale()) }}');
  // Reformat the program start times in template
  $('#bouquets a[data-type="channel"] [class*="program"]').each(function(index,value){
    $(value).find('span.time').text(moment(+moment.utc($(value).find('span.time').text())).format('LT'));
  });
  $('#bouquet_recordings a[data-type="recording"] .program-next').each(function(index,value){
    let duration = moment(+moment.utc($(this).prev().find('span').text())) - moment.utc();
    $(value).find('span.time').text(moment.duration($(value).find('span.time').text()*1, 'seconds').humanize() + ', ' + moment.duration(duration).humanize(true));
  });
  $('#bouquet_recordings a[data-type="recording"] .program-now').each(function(index,value){
    $(value).find('span.time').text(moment(+moment.utc($(value).find('span.time').text())).format('LLLL'));
  });
  $('a[href$="CHANGELOG"]').on('click',function(event){
    event.preventDefault();
    load_changelog($(this).attr('href'));
  });

  // Init VideoJS player
  init_video_player();
  // Set the click actions to start stream
  init_stream_actions();
  // Set the max fixed height
  set_bouquets_height();
  // Load the rest of dreambox restream data
  @if ($dreambox->is_online())
  start_dreambox();
  @else
  $('#offline_message').show();
  @endif

  $('#search').on('keyup',function(){
    let search_value = $(this).val();
    if (search_value.length >= 3) {
      clearTimeout(search_timer);
      search_timer = setTimeout(function(){
        live_search(search_value);
      },1000)
    }
  })
});
</script>
@endsection
