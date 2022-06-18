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

function set_bouquets_height() {
  let pos = $('#bouquets').position();
  let height = $(window).height() - (($('#bouquets .card-header:first').outerHeight() * $('#bouquets .card-header').length) + pos.top) - 100;
  $('div.card-body').height(height);
}

function show_epg(channel_id) {
  let channel = $('a[data-type="channel"][data-id="' + channel_id + '"]');
  let modal = $('#dreamboxModal');

  modal.find('.modal-dialog').addClass('modal-xl');
  modal.find('.modal-title').html('Electronic program guide ' + channel.find('h5').html());
  modal.find('.modal-body img').parent().hide();
  modal.find('.stream-info').removeClass('col-8').html('<h3 class="text-center">Loading EPG data ...</h3>');
  modal.modal();

  $.get('/dreambox/' + dreambox_id + '/channel/' + channel_id + '/epg',function(data){

    modal.find('.modal-header .clearfix').hide();
    modal.find('.modal-header img').attr({'src':channel.css('background-image').replace('url("','').replace('")','')}).show();
    modal.find('.stream-info').html(Autolinker.link(data));
    modal.find('.stream-info .list-group-item-action:first').addClass('active');
    modal.find('.program-start').each(function(index,value){
        $(value).text(moment(+moment.utc($(value).text())).format('dddd D MMMM @ LT'));
    });
    modal.find('.program-stop').each(function(index,value){
        $(value).text(moment(+moment.utc($(value).text())).format('LT'));
    });
    modal.find('.program-duration').each(function(index,value){
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
        $('<div/>').html(dreambox_player.source.nextprogram.name).text();
    } else if (dreambox_player.source && 'recording' == dreambox_player.source.type) {
        title = 'Recording: ' + $('<div/>').html(dreambox_player.source.name).text() + ' (duration ' + moment.duration(dreambox_player.source.duration,'seconds').humanize() +
        ', recored at: ' +  moment(+moment.utc(dreambox_player.source.start)).format('LLLL') +')';
    }
    return title;
}

function stream(id, type) {
  dreambox_player.pause();
  let source = $('a[data-type="' + type + '"][data-id="'+ id + '"]');
  let modal = $('#dreamboxModal');

  modal.find('.modal-dialog').removeClass('modal-xl');
  modal.find('.modal-header img').hide();
  modal.find('.modal-header .clearfix').show();
  modal.find('.modal-title').html('Loading...');
  modal.find('.stream-info').addClass('col-8').html('');

  if (source.length >= 1) {
    modal.find('.modal-body img').attr({'src':$(source[0]).css('background-image').replace('url("','').replace('")','')}).parent().show();
    modal.find('.stream-info').html($(source[0]).html());
    modal.modal({
      keyboard: false,
    });
  }

  $('a.list-group-item-action').removeClass('active');
  $.post('/api/dreambox/' + dreambox_id + '/' + type + '/' + id + '/stream',function(data){
    $('.test-screen').remove();

    $(document).scrollTop(0);
    // Open bouquet is needed:
    let bouquet = source.parents('div.card');
    if (!bouquet.find('.collapse').hasClass('show')) {
        bouquet.find('.card-header').trigger('click');
    }
    source.addClass('active');
    let source_position = source.position();
    bouquet.find('.card-body').scrollTop(bouquet.find('.card-body').scrollTop() + (source_position.top - source.height()));

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

    program.find('p').html(Autolinker.link(dreambox_player.source.currentprogram.description));
    program.find('small.program-now').text( '(' + moment.duration(dreambox_player.source.currentprogram.duration,'seconds').humanize() + ')');
    program.find('small.program-next').html('next: ' + moment(+moment.utc(dreambox_player.source.nextprogram.start)).format('LT') + ' - ' + dreambox_player.source.nextprogram.name + ' (' + moment.duration(dreambox_player.source.nextprogram.duration,'seconds').humanize() + ')');

    if (dreambox_player.source.picon) {
      //program_image = $('<img>').addClass('img-fluid rounded float-right picon').attr({'src':dreambox_player.source.picon,'alt':dreambox_player.source.name});
      program.find('img').attr({'src':dreambox_player.source.picon,'alt':dreambox_player.source.name});
    }
}

function update_current_recording()
{
    if (!dreambox_player.source) return
    let program = $('div.program-info');
    program.find('h5 span').html(dreambox_player.source.name);
    program.find('p').html(dreambox_player.source.description);
    program.find('small.program-now').text( '(' + moment.duration(dreambox_player.source.duration,'seconds').humanize() + ')');
    program.find('small.program-next').html('recorded: ' + moment(+moment.utc(dreambox_player.source.start)).format('LLLL') + ', ' + moment.duration(moment(+moment.utc(dreambox_player.source.start))-moment.utc()).humanize(true));
    if (dreambox_player.source.channel.picon) {
      //program_image = $('<img>').addClass('img-fluid rounded float-right picon').attr({'src':dreambox_player.source.channel.picon,'alt':dreambox_player.source.channel.name});
      //program.find('p').prepend(program_image);
      program.find('img').attr({'src':dreambox_player.source.picon,'alt':dreambox_player.source.name});
    }
}

function update_channel(channel_data) {
  let channel = $('a[data-type="channel"][data-id="' + channel_data.id + '"]');
  let now  = null;
  let next = null;

  if (channel_data.currentprogram !== null) {
    now = moment(+moment.utc(channel_data.currentprogram.start)).format('LT'); // + ' - ' + channel_data.currentprogram.name;
    add_to_epg_refresh_list(moment(+moment.utc(channel_data.currentprogram.stop)),channel_data.id);
  } else {
    add_to_epg_refresh_list(moment.utc(),channel_data.id);
  }

  if (channel_data.nextprogram) {
    next = moment(+moment.utc(channel_data.nextprogram.start)).format('LT'); // + ' - ' + channel_data.nextprogram.name;
  }

  channel.find('h5').html(channel_data.name);

  channel.find('.program-now span.time').text(now);
  channel.find('.program-next span.time').text(next);

  if (channel_data.currentprogram !== null) {
    channel.find('.program-now span.name').html(channel_data.currentprogram.name);
  }
  if (channel_data.nextprogram !== null) {
    channel.find('.program-next span.name').html(channel_data.nextprogram.name);
  }

  channel.prev().find('.epg').text('EPG (' + (channel_data.programs_count ? channel_data.programs_count : '') + ')');
  if (channel_data.picon) {
    channel.css('background-image', 'url("' + channel_data.picon + '")');
  }
}

function update_recording(recording_data) {
  const recording = $('a[data-type="recording"][data-id="' + recording_data.id + '"]');
  const now = 'recorded: ' + moment(+moment.utc(recording_data.start)).format('LLLL');

  recording.find('h5').html(recording_data.name);
  recording.find('.program-now').html(now);
  recording.find('.program-next').html('duration: ' + moment.duration(recording_data.duration, 'seconds').humanize() + ', ' + moment.duration(moment(+moment.utc(recording_data.start))-moment.utc()).humanize(true));
  if (recording_data.channel != null && recording_data.channel.picon && '' == recording.css('background-image')) {
   recording.css('background-image', 'url("' + recording_data.channel.picon + '")');
  }
}

function load_epg(channel_queue) {
  if (channel_queue && channel_queue.length > 0) {
    let channel_id = channel_queue.shift();
    let start = new Date();

    $.getJSON('/api/dreambox/' + dreambox_id + '/channel/' + channel_id + '/epg', function( data ) {
      if (data.programs.length > 0)
      {
        update_channel(data);
        refresh_channel_list();
      }

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
  clearTimeout(channel_refresh_timer);

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
  let modal = $('#dreamboxModal');
  modal.find('.modal-dialog').addClass('modal-xl');
  modal.find('.modal-title').html('CHANGELOG');
  modal.find('.modal-body img').parent().hide();
  modal.find('.stream-info').removeClass('col-md-8').html('<h3 class="text-center">Loading changelog ...</h3>');
  modal.modal();
  $.get(url,function(data){
    modal.find('.stream-info').html('<pre>' + Autolinker.link(data) + '</pre>');
  });

}

function start_progress_bar()
{
    if (dreambox_player.source != null && dreambox_player.source.currentprogram != null) {
        let now = moment.utc();
        let past = now - moment(+moment.utc(dreambox_player.source.currentprogram.start));
        //let left = moment(+moment.utc(dreambox_player.source.currentprogram.stop)) - now;
        let duration = dreambox_player.source.currentprogram.duration * 1000;
        $('.progress-bar').css('width', ((past / duration) * 100) + '%');
    } else {
        $('.progress-bar').css('width','0%');
    }
}
