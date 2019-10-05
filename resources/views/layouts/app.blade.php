<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/png" href="{{ URL::asset('images/dreamboxrestream_icon.png') }}" />
    <link href="{{ URL::asset('css/nprogress.min.css') }}" rel="stylesheet">
    @stack('styles')
    <link href="{{ URL::asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('css/bootstrap.min.css') }}" rel="stylesheet">
    <title>@yield('title') - Dreambox Restream (@version)</title>
  </head>
  <body>
    <div class="container-fluid">
      <div class="row">
        <div class="col text-center">
          <h3>@yield('title') <small class="text-muted">@version</small></h3>
          <div class="alert alert-danger" id="offline_message" role="alert" style="display:none">
            <h4 class="alert-heading">Offline!</h4>
            <div>Your dreambox is offline. Please check your network connections.</div>
          </div>
        </div>
      </div>
    @section('content')
      <div class="row">
        <div class="col-12 col-md-8">
          <div class="row">
            <div class="col" id="mainplayer">
              <div class="embed-responsive embed-responsive-16by9 rounded">
                <div class="test-screen" style="background-image:url('{{ URL::asset('images/test_screen.gif') }}');"><p>Pick a channel -></p></div>
                <video id='dreambox-video' class='embed-responsive-item video-js vjs-default-skin' controls preload='auto' width='100%' height='100%' poster='{{ URL::asset('images/old_tv.png') }}'>
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
            <div class="col">
              <div class="media program-info">
                <div class="media-body">
                  <h5 class="mt-0 mb-1"><span>Channel name</span> <small class="program-now">(duration)</small></h5>
                  <p>Description</p>
                  <small class="program-next">Next: upcoming</small>
                </div>
                <img src="{{ URL::asset('images/dreamboxrestream_icon.png') }}" alt="Program icon" class="picon" >
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="accordion" id="bouquets">
          @if (count($dreambox->bouquets) == 0)
            <div class="card" id="bouquet_bouquets">
              <div class="card-header" id="heading_recordings" data-toggle="collapse" data-target="#collapse_bouquets" aria-expanded="true" aria-controls="collapse_bouquets">
                <div class="d-flex w-100 align-items-center justify-content-between">
                  <h4 class="mb-0">
                     <a href="#" class="no-underline">Bouquets</a>
                  </h4>
                  <span class="badge badge-primary float-right">0</span>
                </div>
              </div>
              <div id="collapse_recordings" class="collapse show" aria-labelledby="heading_bouquets" data-parent="#bouquets">
                <div class="card-body">
                  <div class="list-group">

                  </div>
                </div>
              </div>
            </div>
          @endif

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
                      <h4>{{$dreambox->name}}</h4>
                      <h5>Dreambox Restream (@version)</h5>
                      <ul class="list-inline">
                        <li>Loaded {{ $dreambox->bouquets_count }} bouquets</li>
                        <li>Loaded {{ $dreambox->channels_count }} channels</li>
                        <li>Loaded {{ $dreambox->programs_count }} programs</li>
                        <li>Loaded {{ $dreambox->recordings_count }} recordings</li>
                      </ul>
                      <p>
                        <a href="https://github.com/theyosh/DreamboxReStream" target="_blank" title="Github">Released at @version('timestamp-date')</a>
                        <br />
                        <a href="{{ URL::asset('CHANGELOG') }}" title="Read changelog">CHANGELOG</a>
                        <br />
                        <a href="/dreambox/{{$dreambox->id}}/setup" title="Click to enter the setup page">Setup</a>,
                        <a href="/dreambox/{{$dreambox->id}}/purge" title="Purge the cache and reload the data">Purge</a>
                      </p>
                      <p>
                        Copyright 2006-{{ date('Y') }} - <a href="http://theyosh.nl" class="external" target="_blank" title="The YOSH">TheYOSH</a>
                        <br />
                        Like the software?<br>Consider a donation
                      </p>
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
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    @show
    </div>

    <div class="modal fade" id="dreamboxModal" tabindex="-1" role="dialog" aria-labelledby="dreamboxModalTitle" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
          <div class="modal-header">

            <div class="clearfix">
              <div class="spinner-border float-left mr-2" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>
            <img src="{{ URL::asset('images/dreamboxrestream_icon.png') }}" alt="Dreambox ReStream Logo" class="img-thumbnail" style="display:none">



            <h5 class="modal-title" id="dreamboxModalTitle">Loading...</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-4 text-center">
                <img src="{{ URL::asset('images/dreamboxrestream_icon.png') }}" alt="Dreambox ReStream Logo" class="img-thumbnail">
              </div>
              <div class="col-8 stream-info w-100">
              @section('modal_content')
                <div class="d-flex w-100 justify-content-between">
                  <h5 class="mb-1">{{$dreambox->name}}</h5>
                </div>
                <p class="mb-1 program-now">Dreambox ReStream</p>
                <small class="program-next">@version</small>
              @show
              </div>
            </div>
          </div>
          <div class="modal-footer"></div>
        </div>
      </div>
    </div>
    <div class="modal fade" id="ambilightModal" tabindex="-1" role="dialog" aria-hidden="true" style="overflow-y:hidden !important">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl" role="document">
        <div class="modal-content">
          <div class="modal-body">
          </div>
        </div>
      </div>
    </div>

    @stack('scripts')
    <script type="text/javascript" src="{{ URL::asset('js/nprogress.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('js/app.js') }}"></script>
    @yield('javascript')

    <script type="text/javascript">
    $(document).ajaxStart(function() {
      NProgress.start();
    });

    $(document).ajaxComplete(function(event, request, settings) {
      NProgress.done();
    });
    </script>
  </body>
</html>
