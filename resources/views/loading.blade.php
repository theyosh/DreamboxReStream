<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $dreambox->name }} - Dreambox Restream (@version)</title>

    <meta http-equiv="refresh" content="1; url=/dreambox/{{$dreambox->id}}" />

    <link rel="shortcut icon" type="image/png" href="{{ URL::asset('images/dreamboxrestream_icon.png') }}" />

    <link href="{{ URL::asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/nprogress.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/video-js.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/videojs.airplay.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/videojs-contextmenu-ui.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/videojs-http-source-selector.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/videojs-dvr.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/bootstrap.min.css') }}" rel="stylesheet">

    <style>
        .card-body {
            overflow-y:scroll;
        }

        .accordion .card-header {
            cursor: pointer;
        }

        .list-group-item,
        div.program-info {
            background-repeat: no-repeat;
            background-position: bottom right;
            background-size: 20% auto;
        }

        #dreambox-video.video-js,
        #dreambox-video .vjs-poster{
            background-color: transparent;
        }

        div.program-info p img.picon {
            width: 20%;
        }

        .modal-body small.hd,
        .modal-body small.epg {
            display:none;
        }

        .program-meta {
          height: 0px;
          font-weight: bold;
          text-align: right;
          padding-right: 0.5rem;
          z-index: 999;
        }

        .program-meta button,
        .program-meta small {
            margin-top: 0.8rem;
            margin-right: 0.2rem;
        }

        #epgChannelModal img {
            height: 1.8rem;
            margin-right: 0.2rem;
        }
    </style>
  </head>
  <body>
  <div class="container-fluid">
    <div class="row">
      <div class="col text-center">
        <h3>{{ $dreambox->name }} <small class="text-muted">@version</small></h3>
      </div>
    </div>
    <div class="row">
      <div class="col-12 col-md-8">
        <div class="row">
          <div class="col">
            <div class="embed-responsive embed-responsive-16by9 rounded">
              <video id='dreambox-video' class='embed-responsive-item video-js' controls preload='auto' width='100%' height='100%' poster='{{ URL::asset('images/dreambox.jpg') }}' data-setup='{"autoplay":"any","plugins" : { "airplayButton": {}, "httpSourceSelector" : {"default" : "auto"} }}'>
                <p class='vjs-no-js'>
                  To view this video please enable JavaScript, and consider upgrading to a web browser that
                  <a href='https://videojs.com/html5-video-support/' target='_blank'>supports HTML5 video</a>
                </p>
              </video>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col program-info">
            <div class="d-flex w-100">
              <h5 class="mb-1"></h5>
              <small class="program-now"></small>
            </div>
            <p></p>
            <small class="program-next"></small>
          </div>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="accordion" id="bouquets">
        @foreach ($dreambox->bouquets as $bouquet)
          <div class="card" id="bouquet{{ $bouquet->id }}">
            <div class="card-header" id="heading{{ $bouquet->id }}" data-toggle="collapse" data-target="#collapse{{ $bouquet->id }}" aria-expanded="true" aria-controls="collapse{{ $bouquet->id }}">
              <div class="d-flex w-100 align-items-center justify-content-between">
                <h4 class="mb-0 primary" >
                  {{ $bouquet->name }}
                </h4>
                <span class="badge badge-primary float-right">{{ $bouquet->channels_count }}</span>
              </div>
            </div>
            <div id="collapse{{ $bouquet->id }}" class="collapse @if ($loop->iteration == 1) show @endif" aria-labelledby="heading{{ $bouquet->id }}" data-parent="#bouquets">
              <div class="card-body">
                <div class="list-group">
                @foreach ($bouquet->channels as $channel)
                  <span class="program-meta">
                    <button class="btn badge badge-warning epg" data-channel="{{ $channel->id }}">
                      <small>EPG ({{$channel->programs_count}})</small>
                    </button>
                    @if ($channel->is_hd)
                    <small class="badge badge-dark hd">HD</small>
                    @endif
                  </span>
                  <a href="#" data-type="channel" data-id="{{ $channel->id }}" class="list-group-item list-group-item-action channel{{ $channel->id }}" @if ($channel->picon) style="background-image: url('{{$channel->picon}}');" @endif >
                    <div class="d-flex w-100 justify-content-between">
                      <h5 class="mb-1">{{$channel->name}}</h5>
                    </div>
                    <p class="mb-1 program-now">now: @if ($channel->currentprogram) <span>{{ $channel->currentprogram['start']}}</span> - {!! $channel->currentprogram['name'] !!}@endif</p>
                    <small class="program-next">next: @if ($channel->nextprogram) <span>{{$channel->nextprogram['start']}}</span> - {!! $channel->nextprogram['name'] !!}@endif</small>
                  </a>
                @endforeach
                </div>
              </div>
            </div>
          </div>
        @endforeach
        <div class="card" id="bouquet_recordings">
          <div class="card-header" id="heading_recordings" data-toggle="collapse" data-target="#collapse_recordings" aria-expanded="true" aria-controls="collapse_recordings">
            <div class="d-flex w-100 align-items-center justify-content-between">
              <h4 class="mb-0">
                 Recordings
              </h4>
              <span class="badge badge-primary float-right">{{$dreambox->recordings_count}}</span>
            </div>
          </div>
          <div id="collapse_recordings" class="collapse" aria-labelledby="heading_recordings" data-parent="#bouquets">
            <div class="card-body">
              <div class="list-group">
                @foreach ($dreambox->recordings as $recording)
                  <span class="program-meta">
                    @if ($recording->channel && $recording->channel->is_hd)
                    <small class="badge badge-dark hd">HD</small>
                    @endif
                  </span>
                  <a href="#" data-type="recording" data-id="{{ $recording->id }}" class="list-group-item list-group-item-action recording{{ $recording->id }}" @if ($recording->channel && $recording->channel->picon) style="background-image: url('{{$recording->channel->picon}}');" @endif >
                    <div class="d-flex w-100 justify-content-between">
                      <h5 class="mb-1">{{$recording->name}}</h5>
                    </div>
                    <p class="mb-1 program-now">recorded: <span>{{ $recording->start}}</span></p>
                    <small class="program-next">duration: <span>{{ $recording->duration}}</span></small>
                  </a>
                @endforeach
              </div>
            </div>
          </div>
        </div>
        <div class="card" id="bouquet_search">
          <div class="card-header" id="heading_search" data-toggle="collapse" data-target="#collapse_search" aria-expanded="true" aria-controls="collapse_search">
            <div class="d-flex w-100 align-items-center justify-content-between">
              <h4 class="mb-0 primary" >
                Search
              </h4>
              <span class="badge badge-primary float-right">0</span>
            </div>
            </div>
            <div id="collapse_search" class="collapse" aria-labelledby="heading_search" data-parent="#bouquets">
              <div class="card-body">
                <div class="list-group">
                  <div class="list-group-item">
                    <form class="form">
                      <label class="sr-only" for="search">Search</label>
                      <input type="text" class="form-control" id="search" placeholder="Start typing to search ...">
                      <small id="searchHelp" class="form-text text-muted">Start typing to search ... (min 3 characters)</small>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="card" id="bouquet_about">
            <div class="card-header" id="heading_about" data-toggle="collapse" data-target="#collapse_about" aria-expanded="true" aria-controls="collapse_about">
              <div class="d-flex w-100 align-items-center justify-content-between">
                <h4 class="mb-0 primary" >
                  About
                </h4>
              </div>
            </div>
            <div id="collapse_about" class="collapse" aria-labelledby="heading_about" data-parent="#bouquets">
              <div class="card-body">
                <div class="list-group">
                  <div class="list-group-item text-center">
                    <img src="{{ URL::asset('images/dreamboxrestream_icon.png') }}" alt="Dreambox ReStream Logo" class="img-thumbnail mx-auto d-block">
                    <h4>{{ $dreambox->name }}</h4>
                    <h5>Dreambox Restream (@version)</h5>
                    <ul class="list-inline">
                      <li>Loaded {{ $dreambox->bouquets_count }} bouquets</li>
                      <li>Loaded {{ $dreambox->channels_count }} channels</li>
                      <li>Loaded {{ $dreambox->programs_count }} programs</li>
                      <li>Loaded {{ $dreambox->recordings_count }} recordings</li>
                    </ul>
                    <p>
                      <a href="https://github.com/theyosh/DreamboxReStream" target="_blank" title="Github">Released at 14 April 2019</a><br />
                      <a href="javascript:void(0);" onclick="showChangeLog();" title="Read changelog">CHANGELOG</a><br />
                      <a href="/dreambox/{{$dreambox->id}}/setup" title="Click to enter the setup page">Setup</a>,
                      <a href="/dreambox/{{$dreambox->id}}/purge" title="Purge the cache and reload the data">Purge</a>
                    </p>
                    <p>
                      Copyright 2006-{{ date('Y') }} - <a href="http://theyosh.nl" class="external" target="_blank" title="The YOSH">TheYOSH</a><br />
                      Like the software?<br>Consider a donation<br>
                      <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_donations">
                        <input type="hidden" name="business" value="paypal@theyosh.nl">
                        <input type="hidden" name="lc" value="US">
                        <input type="hidden" name="item_name" value="Dreambox ReStream">
                        <input type="hidden" name="no_note" value="0">
                        <input type="hidden" name="currency_code" value="EUR">
                        <input type="hidden" name="bn" value="PP-DonationsBF:btn_donate_LG.gif:NonHostedGuest">
                        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" name="submit" alt="PayPal - The safer, easier way to pay online!" border="0">
                        <img alt="" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" style="display: none !important;" width="1" hidden="" height="1" border="0">
                      </form>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="changeChannelModal" tabindex="-1" role="dialog" aria-labelledby="changeChannelModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <div class="clearfix">
              <div class="spinner-border float-left mr-2" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>
            <h5 class="modal-title" id="changeChannelModalTitle">Starting....</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-12 col-md-4 text-center">
                <img src="{{ URL::asset('images/dreamboxrestream_icon.png') }}" alt="" class="img-thumbnail">
              </div>
              <div class="col-12 col-md-8 stream-info">
              Dreambox Restream is being loaded. Please wait...
              </div>
            </div>
          </div>
          <div class="modal-footer">
          </div>
        </div>
      </div>
    </div>


    <script type="text/javascript" src="{{ URL::asset('js/app.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/nprogress.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/moment.js') }}" ></script>
    <script type="text/javascript" src="{{ URL::asset('js/video.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/videojs.plugin.text-overlay.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/videojs.airplay.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/videojs-contextmenu-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/videojs-contrib-quality-levels.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/videojs-http-source-selector.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/videojs-dvr.min.js') }}"></script>


    <script type="text/javascript">
    const dreambox_id = {{$dreambox->id}};
    let channel_refresh_list = {};
    let channel_refresh_timer = null;

    $(document).ajaxStart(function() {
      NProgress.start();
    });

    $(document).ajaxComplete(function(event, request, settings) {
      NProgress.done();
    });


    let dreambox_player = null;
    let overlay_timer = null;
    function init_video_player() {
      dreambox_player = videojs('dreambox-video');
      dreambox_player.source = null;

      dreambox_player.titleoverlay({
        title: generate_player_title_overlay(),
        floatPosition: 'right',
        margin: '10px',
        fontSize: '1.5em',
      });

      dreambox_player.dvr();

      dreambox_player.contextmenuUI({
        content: [{

          // A plain old link.
          href: 'https://github.com/theyosh/DreamboxReStream',
          label: 'Dreambox ReStream'
        }, {

          // A link with a listener. Its `href` will automatically be `#`.
          //label: 'Example Link',
          //listener: function() {
          //  alert('you clicked the example link!');
          //}
        }]
      });

      dreambox_player.on('playing',function(event){
        $('#changeChannelModal').modal('hide')
      })

      dreambox_player.on('mouseover', function(event) {
        clearTimeout(overlay_timer);
        dreambox_player.titleoverlay.updateTitle(generate_player_title_overlay());
        dreambox_player.titleoverlay.showOverlay();

        overlay_timer = setTimeout(function(){
          dreambox_player.titleoverlay.hideOverlay();
        },5 * 1000);
      });

      dreambox_player.on('mouseout', function(event) {
        dreambox_player.titleoverlay.hideOverlay();
      });

      dreambox_player.qualityLevels();
      dreambox_player.httpSourceSelector();
    }


    $(function(){
      // Init VideoJS player
     //  init_video_player();
      $('#changeChannelModal').modal();
    });
    </script>
  </body>
</html>