<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8">
    <title>{{$channel->name}} - Dreambox Restream (@version)</title>
  </head>
  <body>
    <div class="list-group">
    @foreach ($channel->programs as $program)
      <div class="list-group-item list-group-item-action">
        <div class="d-flex w-100 justify-content-between">
          <h5 class="mb-1">{!! $program->name !!}
            <small> (<span class="program-duration">{{$program->duration}}</span>)</small>
          </h5>
          <small class="text-right">
            <span class="program-start">{{$program->start}}</span> - <span class="program-stop">{{$program->stop}}</span>
          </small>
        </div>
        <p class="mb-1">{{$program->description}}</p>
      </div>
    @endforeach
    </div>
  </body>
</html>
