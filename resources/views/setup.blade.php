@extends('layouts.app')

@section('title', 'Setup')

@section('content')

    @if ($dreambox->id)
        {{ Form::model($dreambox, ['route' => ['update.dreambox', $dreambox->id],  'method' => 'put']) }}
    @else
        {{ Form::model($dreambox, ['route' => ['new.dreambox']]) }}
    @endif
    <div class="row">
      <div class="col-12 col-md-4">
        <h4>Required data</h4>
        <div class="form-group">
          {{Form::label('interface_language', 'Interface language')}}
          {{Form::select('interface_language', ['en' => 'English', 'du' => 'Nederlands'], 'en',['class' => 'form-control','required' => 'true'])}}
          <small class="form-text text-danger d-none">test</small>
          <small id="interface_languageHelp" class="form-text text-muted">Select the interface language</small>
        </div>
        <div class="form-group">
          {{Form::label('name', 'Dreambox name')}}
          {{Form::text('name',old('name'),['class' => 'form-control', 'placeholder' => 'Any name', 'required' => 'true'])}}
          <small class="form-text text-danger d-none">test</small>
          <small id="dreamboxnameHelp" class="form-text text-muted">Enter a name for your Dreambox ReStream setup. Any name is valid.</small>
        </div>
        <div class="form-group">
          {{Form::label('hostname', 'Dreambox host name')}}
          {{Form::text('hostname',old('hostname'),['class' => 'form-control', 'placeholder' => 'Host name or ip', 'required' => 'true'])}}
          <small class="form-text text-danger  d-none">tttt</small>
          <small id="hostnameHelp" class="form-text text-muted">Enter the local hostname or ip number of your dreambox.</small>
        </div>
        <div class="form-group">
          {{Form::label('port', 'Dreambox port number')}}
          {{Form::number('port',old('port'),['class' => 'form-control', 'placeholder' => 'Port number', 'required' => 'true'])}}
          <small class="form-text text-danger"></small>
          <small id="portnumberHelp" class="form-text text-muted">Enter the portnumber of the webinterface on your dreambox (default 80).</small>
        </div>
        <div class="form-group">
          {{Form::label('multiple_tuners', 'Dreambox multiple tuners',['class' => 'required'])}}<br />
          <div class="form-check form-check-inline">
            {{Form::radio('multiple_tuners', 1, old('multiple_tuners'), ['id' => 'multiple_tunersYes', 'class' => 'form-check-input'])}}
            {{Form::label('multiple_tunersYes', 'Yes',['class' => 'form-check-label'])}}
          </div>
          <div class="form-check form-check-inline">
            {{Form::radio('multiple_tuners', 0, old('multiple_tuners'), ['id' => 'multiple_tunersNo', 'class' => 'form-check-input'])}}
            {{Form::label('multiple_tunersNo', 'No',['class' => 'form-check-label'])}}
          </div>
          <br />
          <small class="form-text text-danger"></small>
          <small id="dual_tunerHelp" class="form-text text-muted">Does the dreambox have more then 1 tuner?.</small>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <h4>Streaming data</h4>
        <div class="form-group">
          {{Form::label('audio_language', 'Audio language')}}
          {{Form::text('audio_language',old('audio_language'),['class' => 'form-control', 'placeholder' => 'Audio language'])}}
          <small class="form-text text-danger"></small>
          <small id="audiolanguageHelp" class="form-text text-muted">Enter either a number for the nth language or abriviated name.</small>
        </div>
        <div class="form-group">
          {{Form::label('subtitle_language', 'Subtitle language')}}
          {{Form::text('subtitle_language',old('subtitle_language'),['class' => 'form-control', 'placeholder' => 'Subtitle language'])}}
          <small class="form-text text-danger"></small>
          <small id="subtitlelanguageHelp" class="form-text text-muted">Enter either a number for the nth language or abriviated name.</small>
        </div>
        <div class="form-group">
          {{Form::label('dvr_length', 'DVR length')}}
          {{Form::number('dvr_length',old('dvr_length'),['class' => 'form-control', 'placeholder' => 'DVR length time in seconds'])}}
          <small class="form-text text-danger"></small>
          <small id="dvrlengthHelp" class="form-text text-muted">Enter the amount of DVR window length in seconds (default 120).</small>
        </div>
        <div class="form-group">
          {{Form::label('buffer_time', 'Extra buffer time')}}
          {{Form::number('buffer_time',old('buffer_time'),['class' => 'form-control', 'placeholder' => 'Extra buffer time in seconds'])}}
          <small class="form-text text-danger"></small>
          <small id="epglimitHelp" class="form-text text-muted">Enter an extra buffer time in seconds when there are timing issues. (default 0).</small>
        </div>
        <div class="form-group">
          {{Form::label('profiles', 'Streaming profiles',['class' => 'required'])}}<br />
          {{Form::hidden('transcoding_profiles',old('transcoding_profiles'))}}
          @foreach ($profiles as $profileid => $profile)
          <div class="form-check form-check-inline">
            <div class="form-check form-check-inline">
              {{Form::checkbox('profile', $profileid, (stripos($dreambox->transcoding_profiles,$profileid) !== false), ['id' => 'profile' . $profileid, 'class' => 'form-check-input'])}}
              {{Form::label('profile' . $profileid, $profile['name'],['class' => 'form-check-label'])}}
            </div>
          </div>
          @endforeach
          <br />
          <small class="form-text text-danger"></small>
          <small id="transcoding_profilesHelp" class="form-text text-muted">Select the transcoding profiles. More profiles needs more CPU power.</small>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <h4>Optional data</h4>
        <div class="form-group">
          {{Form::label('username', 'Dreambox username')}}
          {{Form::text('username',old('username'),['class' => 'form-control', 'placeholder' => 'Dreambox username'])}}
          <small class="form-text text-danger"></small>
          <small id="dreamboxusernameHelp" class="form-text text-muted">Enter the username for the Dreambox web interface. Leave empty when not needed.</small>
        </div>
        <div class="form-group">
          {{Form::label('password', 'Dreambox password')}}
          {{Form::text('password',old('password'),['class' => 'form-control', 'placeholder' => 'Dreambox password'])}}
          <small class="form-text text-danger"></small>
          <small id="dreamboxpasswordHelp" class="form-text text-muted">Enter the password for the Dreambox web interface. Leave empty when not needed.</small>
        </div>
        <div class="form-group">
          {{Form::label('exclude_bouquets', 'Exclude bouquets')}}
          {{Form::text('exclude_bouquets',old('exclude_bouquets'),['class' => 'form-control', 'placeholder' => 'Exclude bouquets'])}}
          <small class="form-text text-danger"></small>
          <small id="exclude_bouquetsHelp" class="form-text text-muted">Enter a comma separated list of bouquet names to be ingored during loading. Case insenitive.</small>
        </div>
        <div class="form-group">
          {{Form::label('epg_limit', 'EPG time limit')}}
          {{Form::number('epg_limit',old('epg_limit'),['class' => 'form-control', 'placeholder' => 'EPG time limit in hours'])}}
          <small class="form-text text-danger"></small>
          <small id="epglimitHelp" class="form-text text-muted">Enter the amount of time the EPG should load in hours (default 36).</small>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col text-center">
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </div>
  {!! Form::close() !!}

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-body" style="padding: 0">
            <div class="alert alert-danger alert-dismissible fade show" style="margin-bottom:0">
              <h4 class="alert-heading">Error!</h4>
              <p>Please enter a valid value in all the required fields before proceeding. If you need any help just place the mouse pointer above info icon next to the form field.</p>
              <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
          </div>
        </div>
      </div>
    </div>
@endsection

@section('javascript')
<script type="text/javascript">
  $(function(){
    $('div.form-group input[required="true"]').prev().addClass('required');
    $('div.form-group select[required="true"]').prev().addClass('required');

    $('form').on('submit',function(event) {
      // Get the form.
      event.preventDefault();
      $('.text-danger').addClass('d-none');
      $('.is-invalid').removeClass('is-invalid');

      $('input[name="transcoding_profiles"]').val($('input[name="profile"]:checked').map(function() {return this.value;}).get().join(','));

      var form = $('form');
      $.ajax({
        type: form.attr('method'),
        url: form.attr('action'),
        data: form.serialize()
      }).done(function(data){
        $('#exampleModal .alert').removeClass('alert-danger').addClass('alert-success').find('p').text(data.message);
        $('#exampleModal .alert h4.alert-heading').text('Success!');
        $('#exampleModal .alert').show();
        $('#exampleModal').modal();
        location.href = '/';
      }).fail(function(data) {
        // Make sure that the formMessages div has the 'error' class.
        data = data.responseJSON;
        $('#exampleModal .alert p').text(data.message);
        $('#exampleModal .alert').show();
        $('#exampleModal').modal();
        $.each(data.errors,function(index,value) {
          $('[name="' + index + '"]').addClass('is-invalid').parents('.form-group').find('small.text-danger').text(value.join(',')).removeClass('d-none');
        });
      });
    });
  });
</script>
@endsection
