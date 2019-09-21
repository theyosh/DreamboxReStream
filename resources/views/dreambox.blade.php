@extends('layouts.app')

@section('title', $dreambox->name)

@push('styles')
  <link href="{{ URL::asset('css/video-js.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs.airplay.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-contextmenu-ui.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-http-source-selector.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-dvr.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
  <script type="text/javascript" src="{{ URL::asset('js/moment-with-locales.min.js') }}" ></script>
  <script type="text/javascript" src="{{ URL::asset('js/video.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-titleoverlay.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs.airplay.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-contextmenu-ui.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-contrib-quality-levels.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-http-source-selector.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-dvr.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/dreambox.min.js') }}"></script>
@endpush

@section('modal_content')
@endsection

@section('javascript')
<script type="text/javascript">
const dreambox_id = {{$dreambox->id}};
let channel_refresh_list = {};
let channel_refresh_timer = null;

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
        if (dreambox_player.source.currentprogram != null && (dreambox_player.source.currentprogram.name != data.currentprogram.name || dreambox_player.source.currentprogram.start != data.currentprogram.start)) {
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
    $('#dreamboxModal').modal('hide')
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

$(function(){
  // Set the local to the application locale
  moment.locale('{{ str_replace('_', '-', app()->getLocale()) }}');
  // Reformat the program start times in template
  $('#bouquets a[data-type="channel"] [class*="program"]').each(function(index,value){
    $(value).find('span').text(moment(+moment.utc($(value).find('span').text())).format('LT'));
  });
  $('#bouquet_recordings a[data-type="recording"] .program-next').each(function(index,value){
    let duration = moment(+moment.utc($(this).prev().find('span').text())) - moment.utc();
    $(value).find('span').text(moment.duration($(value).find('span').text()*1, 'seconds').humanize() + ', ' + moment.duration(duration).humanize(true));
  });
  $('#bouquet_recordings a[data-type="recording"] .program-now').each(function(index,value){
    $(value).find('span').text(moment(+moment.utc($(value).find('span').text())).format('LLLL'));
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
@endsection