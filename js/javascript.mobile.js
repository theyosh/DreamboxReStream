DreamboxObj.mobile = true;
DreamboxObj.hls = '';
DreamboxObj.player = null;

DreamboxObj.dialog = function(text) {
	if (text === '') {
		// Close the dialog
		$.mobile.loading('hide');
	} else {
		 $.mobile.loading('show', {
			 text: '',
			 textVisible: true,
			 theme: 'a',
			 textonly: false,
			 html: '<span class="ui-icon ui-icon-loading"></span>' + text + '<span id="zaptimer"></span>'
		});
	}
};

DreamboxObj.showBouquets = function() {
	var tmp = DreamboxObj.bouquetsDiv;
	DreamboxObj.bouquetsDiv = jQuery('<div>').attr({'data-role':'collapsible-set','data-inset':'false'});
	tmp.append(DreamboxObj.bouquetsDiv);
	tmp = null;

	for(var bouquetID in DreamboxObj.bouquets) {
		if (DreamboxObj.bouquets[bouquetID].id.substring(0,1) !== '_') {
			DreamboxObj.showBouquet(DreamboxObj.bouquets[bouquetID]);
		}
	}
	DreamboxObj.bouquetsDiv.children('div:first').attr('data-collapsed','false');
	DreamboxObj.bouquetsDiv.collapsibleset();
};

DreamboxObj.showBouquet = function(bouquetObj) {
	DreamboxObj.bouquetsDiv.append(
		jQuery('<div>')	.attr({'data-role':'collapsible','data-theme':'b'})
						.append(jQuery('<h3>')	.attr('id',bouquetObj.id)
												.text(bouquetObj.name))
	);
};

DreamboxObj.showAllChannels = function() {
	for(var bouquetid in DreamboxObj.bouquets) {
		DreamboxObj.showChannels(bouquetid);
	}
};

DreamboxObj.showChannels = function(bouquetid) {
	jQuery('h3[id="' + bouquetid + '"] + div').append('<ul>');

	for(var channelid in DreamboxObj.bouquets[bouquetid].channels) {
		DreamboxObj.showChannel(bouquetid,channelid);
	}
	jQuery('h3[id="' + bouquetid + '"] + div ul').listview({ filter: true ,filterPlaceholder: 'Search in channels...'});
	// Markers are also collapsible sets....
	// Open the first list
	DreamboxObj.bouquetsDiv.children(":first").collapsible( "expand" );
};

DreamboxObj.showChannel	= function(bouquetid,channelid) {
	var channelObj = DreamboxObj.bouquets[bouquetid].channels[channelid];

	if (channelObj.type == 'marker') {
		// Create a new collapsible set
		var div = jQuery('<div>').attr({'data-role':'collapsible-set','data-inset':'false'});
		var	divContent = jQuery('<div>').attr({'data-role':'collapsible'})
										.append(jQuery('<h3>')	.attr('id',channelObj.id)
																.text(channelObj.name))
										.append(jQuery('<ul>').attr({	'data-role':'listview',
																		'data-filter':'true',
																		'data-filter-placeholder':'Search in channels...'}));
		var firstUL = jQuery('h3[id="' + bouquetid + '"] + div ul:first');
		if (firstUL.children('li').length === 0) {
			firstUL.replaceWith(div);
		}
		jQuery('h3[id="' + bouquetid + '"] + div div:first').append(divContent);
	} else {
		var li = jQuery('<li>').attr({'class':'channel'});
		li.attr({'id':channelObj.id});
		var a = jQuery('<a>').attr({'href':'#'}).bind('click',function() {
			DreamboxObj.zap(jQuery(this).parents('li.channel').attr('id'));
		});
		var title = jQuery('<h2>').text(channelObj.name);
		if (channelObj.hd) {
			title.append(jQuery('<img src="images/hd.png" class="icon hd" alt="HD Channel"/>'));
		}
		a.append(title);

		a.append(jQuery('<p>').attr({'class':'now'}));
		a.append(jQuery('<p>').attr({'class':'next'}));
		a.append(jQuery('<p>').attr({'class':'ui-li-aside'})
			.append(jQuery('<br>'))
			.append(jQuery('<br>'))
			.append(jQuery('<span>').attr({'class':'now'}))
			.append(jQuery('<br>'))
			.append(jQuery('<span>').attr({'class':'next'}))
		);
		li.append(a);
		jQuery('h3[id="' + bouquetid + '"] + div ul:last').append(li);
	}
};

DreamboxObj.showRecordings = function() {
	if (DreamboxObj.bouquets._recordings.amount > 0) {
		jQuery('#recordings > div[data-role="content"]').append(jQuery('<ul>').attr({'data-role':'listview','data-autodividers':'true'}));
		for(var recordingid in DreamboxObj.bouquets._recordings.channels) {
			DreamboxObj.showRecording(recordingid);
		}
	}
};

DreamboxObj.showRecording = function(recordingid) {
	var recordingObj = DreamboxObj.bouquets._recordings.channels[recordingid];
	var channelDiv = jQuery('#recordings div[data-role="content"] ul');
	var li = jQuery('<li>').attr({'class':'channel'});

	li.attr({'id':recordingObj.id});
	var a = jQuery('<a>').attr({'href':'#'}).bind('click',function() {
			DreamboxObj.zap(jQuery(this).parents('li.channel').attr('id'));
		});
	a.append(jQuery('<h2>').text(recordingObj.name));
    a.append(jQuery('<p>').html('<strong>' + dateFormat(new Date(recordingObj.start * 1000),'dd-mm-yyyy') + '</strong>' + (recordingObj.duration > 0 ? ', ' + humanizeDuration(recordingObj.duration * 1000) : '') + (recordingObj.channel !== '' ? ', ' + recordingObj.channel : '')));
	li.append(a);
	channelDiv.append(li);
};

DreamboxObj.showMovies = function() {
	if (DreamboxObj.bouquets._movies.amount > 0) {
		jQuery('#movies div[data-role="content"]').append(jQuery('<ul>').attr({'data-role':'listview','data-autodividers':'true'}));
		for(var movieid in DreamboxObj.bouquets['_movies'].channels) {
			DreamboxObj.showMovie(movieid);
		}
	}
};

DreamboxObj.showMovie = function(movieid) {
	var movieObj = DreamboxObj.bouquets._movies.channels[movieid];
	var channelDiv = jQuery('#movies div[data-role="content"] ul');
	var li = jQuery('<li>').attr({'class':'channel'});

	li.attr({'id':movieObj.id});
	var a = jQuery('<a>').attr({'href':'#'}).bind('click',function() {
			DreamboxObj.zap(jQuery(this).parents('li.channel').attr('id'));
		});
	a.append(jQuery('<h2>').text(movieObj.name));

	var movieParts = [];
	if (movieObj.duration > -1) {
		movieParts[movieParts.length] = '<strong>' + humanizeDuration(movieObj.duration * 1000) + '</strong>';
	}
	if (movieObj.filesize > -1) {
		movieParts[movieParts.length] = getBytesWithUnit(movieObj.filesize,true,2,false);
	}
	if (movieObj.bitrate > -1) {
		movieParts[movieParts.length] = bitrateFormat(movieObj.bitrate,2);
	}
	if (movieObj.resolution != '-1') {
		movieParts[movieParts.length] = movieObj.resolution + ' px';
	}
	a.append(jQuery('<p>').html(movieParts.join(',')));

	if (movieObj.languages.length > 0) {
		var languagesDiv = jQuery('<span>').attr('class','languages');
		for (var i = 0 ; i < movieObj.languages.length; i++) {
			languagesDiv.append(jQuery('<span>').attr('class','ui-li-count').text(movieObj.languages[i]));
		}
		a.append(languagesDiv);
	}
	li.append(a);
	channelDiv.append(li);
};

DreamboxObj.showProgram = function(channelid,programObj) {
	var now = Math.round((new Date()).getTime() / 1000); // GMT timestamp
	var spanClass = (programObj.start < now && programObj.stop > now ? 'now' : 'next');

	jQuery("#channels li[id='" + channelid.replace("'","\\'") + "'] a p." + spanClass).text(programObj.name);
	jQuery("#channels li[id='" + channelid.replace("'","\\'") + "'] a p.ui-li-aside span." + spanClass).text(dateFormat(new Date(programObj.start * 1000),'HH:MM'));
};
DreamboxObj.start = function() {
	DreamboxObj.dialog('');
	xajax_action('loadNowAndNextEPGData',jQuery('#channels h3:first').attr('id'));
};

jQuery(document).on( "pagehide", function( event ,data) {
	if (jQuery(data.nextPage).attr('id') === 'watch' && DreamboxObj.activechannel !== '') {
	  channelObj = DreamboxObj.getChannel(DreamboxObj.activechannel);
	  DreamboxObj.dialog('Zapping to channel: ' + channelObj.name);
	  if (DreamboxObj.hls !== '') {
			  DreamboxObj.play(DreamboxObj.hls);
    }
	} else if (jQuery(data.nextPage).attr('id') == 'about') {
		addToHomescreen();
	}

	if(jQuery(data.nextPage).attr('id') != 'watch') {
  		try { DreamboxObj.player.destroy(); } catch (e) {};
	}
});
