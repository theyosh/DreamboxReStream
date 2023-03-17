@extends('layouts.app')

@section('title', $dreambox->name)

@push('styles')
  <link href="{{ URL::asset('css/video-js.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs.airplay.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-contextmenu-ui.min.css') }}" rel="stylesheet">
  <link href="{{ URL::asset('css/videojs-http-source-selector.min.css') }}" rel="stylesheet">
@endpush

@push('scripts')
  <script type="text/javascript" src="{{ URL::asset('js/moment-with-locales.min.js') }}" ></script>
  <script type="text/javascript" src="{{ URL::asset('js/video.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-titleoverlay.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs.airplay.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-contextmenu-ui.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-contrib-quality-levels.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/videojs-http-source-selector.min.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('js/dreambox.min.js') }}"></script>
@endpush

@section('javascript')
<script type="text/javascript">
  $(function(){
    set_bouquets_height();
    $('#dreamboxModal').modal();
    location.href = '{{ route('show_dreambox', ['dreambox' => $dreambox->id])}}';
  });
</script>
@endsection
