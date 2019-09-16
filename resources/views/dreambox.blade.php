<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $dreambox->name }} - Dreambox Restream (@version)</title>
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
        a, a:active, a:focus, button, button:focus, button:active,
        .btn, .btn:focus, .btn:active:focus, .btn.active:focus, .btn.focus, .btn.focus:active, .btn.active.focus {
            outline: transparent;
            outline: 0;
        }

        input::-moz-focus-inner {
            border: 0;
        }

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
            margin-top: -1.3rem;
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

        .test-screen {
            background-repeat: no-repeat;
            background-size: 100%;
            background-image:url('{{ URL::asset('images/test_screen.gif') }}');
            position: absolute;
            top: 10%;
            left: 15%;
            width: 55%;
            height: 70%;
        }

        a.no-underline:hover {
            text-decoration: none;
        }
    </style>
  </head>
  <body>
  <div class="container-fluid">
    <div class="row">
      <div class="col text-center">
        <h3>{{ $dreambox->name }} <small class="text-muted">@version</small></h3>
        <div class="alert alert-danger" id="offline_message" style="display:none" role="alert">
          <h4 class="alert-heading">Offline!</h4>
          <div>Your dreambox is offline. Please check your network connections.</div>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-12 col-md-8">
        <div class="row">
          <div class="col">
            <div class="embed-responsive embed-responsive-16by9 rounded">
              <div class="test-screen"></div>
              <video id='dreambox-video' class='embed-responsive-item video-js' controls preload='auto' width='100%' height='100%' poster='{{ URL::asset('images/old_tv.png') }}' data-setup='{"autoplay":"any","plugins" : { "airplayButton": {}, "httpSourceSelector" : {"default" : "auto"} }}'>
                <p class='vjs-no-js'>
                  To view this video please enable JavaScript, and consider upgrading to a web browser that
                  <a href='https://videojs.com/html5-video-support/' target='_blank'>supports HTML5 video</a>
                </p>
              </video>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col">
            <div class="progress" style="height: 0.3rem">
              <div class="progress-bar progress-bar-striped bg-success progress-bar-animated" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"></div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col program-info">
            <div class="d-flex w-100 align-bottom">
              <h5 class="mb-1"><span></span> <small class="program-now"></small></h5>
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
                  <a href="#" class="no-underline">{{ $bouquet->name }}</a>
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
                 <a href="#" class="no-underline">Recordings</a>
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
                <a href="#" class="no-underline">Search</a>
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
                  <a href="#" class="no-underline">About</a>
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
                      <a href="{{ URL::asset('CHANGELOG') }}" title="Read changelog">CHANGELOG</a><br />
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
                <img src="" alt="" class="img-thumbnail">
              </div>
              <div class="col-12 col-md-8 stream-info">
              </div>
            </div>
          </div>
          <div class="modal-footer">
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="epgChannelModal" tabindex="-1" role="dialog" aria-labelledby="epgChannelModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <img src="" alt="" class="img-thumbnail">
            <h5 class="modal-title" id="epgChannelModalTitle">Channel....</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
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

    let search_timer = null;
    function live_search(search_value) {
      // Clear old results....
      let search_list = $('#collapse_search .list-group');
      search_list.find('a.list-group-item-action').remove();
      $('#heading_search .badge.badge-primary').text('0');
      // Search case insensitive
      search_value = search_value.toLowerCase();
      // Search
      let search_counter = 0;
      $('a.list-group-item-action').each(function(){
        if($(this).text().toLowerCase().indexOf(""+search_value+"") != -1 ){
          $(this).clone().appendTo(search_list);
          search_counter++;
        }
      });
      $('#heading_search .badge.badge-primary').text(search_counter);
      init_stream_actions();
    }

    function show_epg(channel_id) {
      let channel = $('a[data-type="channel"][data-id="' + channel_id + '"]');
      $('#epgChannelModal img').attr({'src':channel.css('background-image').replace('url("','').replace('")','')}).show();
      $('#epgChannelModal .modal-title').html('Electronic program guide ' + channel.find('h5').html());
      $('#epgChannelModal .modal-body').html('<h3 class="text-center">Loading EPG data ...</h3>');
      $('#epgChannelModal').modal();

      $.get('/dreambox/' + dreambox_id + '/channel/' + channel_id + '/epg',function(data){
        $('#epgChannelModal .modal-body').html(data);
        $('#epgChannelModal .program-start').each(function(index,value){
            $(value).text(moment(+moment.utc($(value).text())).format('dddd D MMMM @ LT'));
        });
        $('#epgChannelModal .program-stop').each(function(index,value){
            $(value).text(moment(+moment.utc($(value).text())).format('LT'));
        });
        $('#epgChannelModal .program-duration').each(function(index,value){
            $(value).text(moment.duration($(value).text()*1, 'seconds').humanize());
        });
      });
    }

    function init_stream_actions() {
      $('a.list-group-item-action').off('click').on('click',function(event){
        stream($(this).attr('data-id'),$(this).attr('data-type'));
        return false;
      });

      $('button.epg').off('click').on('click',function(event){
        show_epg($(this).attr('data-channel'));
        return false;
      });
    }

    function generate_player_title_overlay() {
        let title = $('h3:first').text();
        if (dreambox_player.source && 'channel' == dreambox_player.source.type) {
            title = $('<div/>').html(dreambox_player.source.name).text() + ' - now: ' + $('<div/>').html(dreambox_player.source.currentprogram.name).text() +
            ' (' +  moment.duration(moment(+moment.utc(dreambox_player.source.nextprogram.start)) - moment.now()).humanize()+ ' left). next: ' +
            $('<div/>').html(dreambox_player.source.nextprogram.name).text()
        } else if (dreambox_player.source && 'recording' == dreambox_player.source.type) {
            title = 'Recording: ' + $('<div/>').html(dreambox_player.source.name).text() + ' (duration ' + moment.duration(dreambox_player.source.duration,'seconds').humanize()
            + ', recored at: ' +  moment(+moment.utc(dreambox_player.source.start)).format('LLLL') +')';
        }
        return title;
    }

    function stream(id, type) {
      dreambox_player.pause();
      source = $('a[data-type="' + type + '"][data-id="'+ id + '"]');

      if (source.length >= 1) {
        $('#changeChannelModal img').attr({'src':$(source[0]).css('background-image').replace('url("','').replace('")','')});
        $('#changeChannelModal .modal-body .stream-info').html($(source[0]).html());
        $('#changeChannelModal').modal({
          keyboard: false,
          backdrop: 'static'
        });
      }

      $('a.list-group-item-action').removeClass('active');
      $.post('/api/dreambox/' + dreambox_id + '/' + type + '/' + id + '/stream',function(data){
        source.addClass('active');

        dreambox_player.source = data;

        dreambox_player.src({
          type: "application/x-mpegURL",
          src: dreambox_player.source.stream
        });

        dreambox_player.play();
        update_current_source();
      });
    }

    function update_current_source()
    {
        if (!dreambox_player.source) return
        if ('channel' == dreambox_player.source.type) {
            update_current_program();
        } else if ('recording' == dreambox_player.source.type) {
            update_current_recording();
        }
        start_progress_bar();
    }

    function update_current_program()
    {
        if (!dreambox_player.source) return
        let program = $('div.program-info');
        program.find('h5 span').html(dreambox_player.source.currentprogram.name);

        program.find('p').html(dreambox_player.source.currentprogram.description);
        program.find('small.program-now').text( '(' + moment.duration(dreambox_player.source.currentprogram.duration,'seconds').humanize() + ')');
        program.find('small.program-next').html('next: ' + moment(+moment.utc(dreambox_player.source.nextprogram.start)).format('LT') + ' - ' + dreambox_player.source.nextprogram.name);

        if (dreambox_player.source.picon) {
          program_image = $('<img>').addClass('img-fluid rounded float-right picon').attr({'src':dreambox_player.source.picon,'alt':dreambox_player.source.name});
          program.find('p').prepend(program_image);
        }
    }

    function update_current_recording()
    {
        if (!dreambox_player.source) return
        let program = $('div.program-info');
        program.find('h5 span').html(dreambox_player.source.name);
        program.find('p').html(dreambox_player.source.description);
        program.find('small.program-now').text( '(' + moment.duration(dreambox_player.source.duration,'seconds').humanize() + ')');
        program.find('small.program-next').html('recorded: ' + moment(+moment.utc(dreambox_player.source.start)).format('LLLL'));
        if (dreambox_player.source.channel.picon) {
          program_image = $('<img>').addClass('img-fluid rounded float-right picon').attr({'src':dreambox_player.source.channel.picon,'alt':dreambox_player.source.channel.name});
          program.find('p').prepend(program_image);
        }
    }

    function start_dreambox() {
      $.getJSON('{{ route('status_dreambox', ['dreambox' => $dreambox->id])}}', function( data ) {
        if (data.id !== undefined) {
          stream(data.id,data.type);
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
            if (dreambox_player.source.currentprogram.name != data.currentprogram.name || dreambox_player.source.currentprogram.start != data.currentprogram.start) {
              dreambox_player.source = data;
              update_current_source();
            }
          } else {
            dreambox_player.source = null;
          }
        });
      },60 * 1000);
    }

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
          label: '<img src=\'{{ URL::asset('images/dreamboxrestream_icon.png') }}\'><br />Dreambox ReStream<br /><small>@version</small>'
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

    function set_bouquets_height() {
      let pos = $('#bouquets').position();
      let height = $(window).height() - (($('#bouquets .card-header:first').outerHeight() * $('#bouquets .card-header').length) + pos.top) - 100;
      $('div.card-body').height(height);
    }

    function update_channel(channel_data) {
      let channel = $('a[data-type="channel"][data-id="' + channel_data.id + '"]');
      let now = 'now: ';
      if (channel_data.currentprogram) {
        now += moment(+moment.utc(channel_data.currentprogram.start)).format('LT') + ' - ' + channel_data.currentprogram.name;
      }
      if (channel.find('.program-now').text() != now) {
        let next = 'next: ';
        if (channel_data.nextprogram) {
          next += moment(+moment.utc(channel_data.nextprogram.start)).format('LT') + ' - ' + channel_data.nextprogram.name;
        }
        channel.find('h5').html(channel_data.name);
        channel.find('.program-now').html(now);
        channel.find('.program-next').html(next);

        add_to_epg_refresh_list(moment(+moment.utc(channel_data.currentprogram.stop)),channel_data.id);
      }
      channel.next().find('.epg').text('EPG (' + (channel_data.programs_count ?  channel_data.programs_count : channel_data.programs.length) + ')');
      if (channel_data.picon && '' == channel.css('background-image')) {
        channel.css('background-image', 'url("' + channel_data.picon + '")');
      }
    }

    function update_recording(recording_data) {
      let recording = $('a[data-type="recording"][data-id="' + recording_data.id + '"]');
      let now = 'recorded: ' + moment(+moment.utc(recording_data.start)).format('LLLL');

      if (recording.find('.program-now').text() != now) {
        recording.find('h5').html(recording_data.name);
        recording.find('.program-now').html(now);
        recording.find('.program-next').html('duration: ' + moment.duration(recording_data.duration, 'seconds').humanize());
      }
      if (recording_data.channel != null && recording_data.channel.picon && '' == recording.css('background-image')) {
       recording.css('background-image', 'url("' + recording_data.channel.picon + '")');
      }
    }

    function load_epg(channel_queue) {
      if (channel_queue && channel_queue.length > 0) {
        let channel_id = channel_queue.shift();
        let start = new Date();

        $.getJSON('/api/dreambox/' + dreambox_id + '/channel/' + channel_id + '/epg', function( data ) {
          update_channel(data);
          refresh_channel_list();

          let duration = new Date() - start;

          if (duration < 1000) {
            setTimeout(function(){
              load_epg(channel_queue);
             },1000 - duration);
          } else {
          // Use setTimeout, so that the nprogress bar will function properly.....
            setTimeout(function(){
              load_epg(channel_queue);
             },100);
          }
        });
      }
    }

    function refresh_channel_list() {
      //if (channel_refresh_timer !== null) {
      clearTimeout(channel_refresh_timer);
      //}

      let now = moment().utc().unix();
      let timestamps = Object.keys(channel_refresh_list).sort();

      for (var i = 0; i < timestamps.length; i++) {
        if (timestamps[i] <= now) {
          load_epg(channel_refresh_list[timestamps[i]].slice(0));
          delete(channel_refresh_list[timestamps[i]]);
        } else {
          channel_refresh_timer = setTimeout(function(){
            refresh_channel_list();
          }, (timestamps[i] - now) * 1000);
          break;
        }
      }
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
              update_recording(recording_data);
            }
          });
          bouquet.find('.badge.badge-primary').text(data.length);
        }
        // Need to do some clean up when recordings disapeare....
        init_stream_actions();
      });
    }

    function add_to_epg_refresh_list(timestamp,channel_id)
    {
        let unix_timestamp = timestamp.unix();
        if (channel_refresh_list[unix_timestamp] === undefined) {
            channel_refresh_list[unix_timestamp] = [];
        }
        if (channel_refresh_list[unix_timestamp].indexOf(channel_id) == -1) {
            channel_refresh_list[unix_timestamp].push(channel_id);
        }
    }

    function load_changelog(url)
    {
      $('#epgChannelModal img').hide();
      $('#epgChannelModal .modal-title').html('CHANGELOG');
      $('#epgChannelModal .modal-body').html('<h3 class="text-center">Loading changelog ...</h3>');
      $('#epgChannelModal').modal();
      $.get(url,function(data){
        $('#epgChannelModal .modal-body').html('<pre>' + data + '</pre>');
      });
    }

    function start_progress_bar()
    {
        if (dreambox_player.source.currentprogram != null) {
            let now = moment.utc();
            let past = now - moment(+moment.utc(dreambox_player.source.currentprogram.start));
            let left = moment(+moment.utc(dreambox_player.source.currentprogram.stop)) - now;
            let duration = dreambox_player.source.currentprogram.duration * 1000;
            $('.progress-bar').css('width', ((past / duration) * 100) + '%');
        } else {
            $('.progress-bar').css('width','0%');
        }
    }

    $(function(){
      // Set the local to the application locale
      moment.locale('{{ str_replace('_', '-', app()->getLocale()) }}');
      // Reformat the program start times in template
      $('#bouquets a[data-type="channel"] [class*="program"]').each(function(index,value){
        $(value).find('span').text(moment(+moment.utc($(value).find('span').text())).format('LT'));
      });
      $('#bouquet_recordings a[data-type="recording"] .program-now').each(function(index,value){
        $(value).find('span').text(moment(+moment.utc($(value).find('span').text())).format('LLLL'));
      });
      $('#bouquet_recordings a[data-type="recording"] .program-next').each(function(index,value){
        $(value).find('span').text(moment.duration($(value).find('span').text()*1, 'seconds').humanize());
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
      // Load the rest of streambox restream data
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
  </body>
</html>