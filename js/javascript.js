var DreamboxObj = {
	bouquets 		: [],
	activechannel	: null,
	bouquetsDiv		: null,
	programTimer 	: [],
	searchTimeout	: null,
	zapTimer 		: null,
	zapDone: false,
	progressBarTimer: null,
	encoderTimer	: null,
	debugDiv		: null,
	debug			: 0,
	mobile			: false,
	running			: 0,
	bouquetQueue	: null,
	baseurl			: location.href,
	playerLoaded	: null,
	player:      null,
	isWebkit 		: 'webkitRequestAnimationFrame' in window,

	// Basic functions
	init: function(debug) {
		DreamboxObj.running = 0;
		DreamboxObj.debug = debug;
		DreamboxObj.log('init','Initializing Dreambox ReStream');
    DreamboxObj.player = null;
		if (DreamboxObj.baseurl.indexOf('?') != -1) {
			DreamboxObj.baseurl = DreamboxObj.baseurl.substr(0,DreamboxObj.baseurl.indexOf('?')-1);
		}
		if (DreamboxObj.baseurl.indexOf('#') != -1) {
			DreamboxObj.baseurl = DreamboxObj.baseurl.substr(0,DreamboxObj.baseurl.indexOf('#')-1);
		}

		DreamboxObj.dialog('Loading dreambox');
		DreamboxObj.log('init','Starting Dreambox ReStream');
		DreamboxObj.encodingStatus('');

		if (!DreamboxObj.mobile) {
			if (DreamboxObj.isWebkit) jQuery('#ReStreamUI').addClass('webkit');
			jQuery('#ProgramGuide').height(590);
			DreamboxObj.bouquetsDiv = jQuery('#channels').accordion({ heightStyle: "fill" });
			jQuery('#EncoderStatus').bind('click',function(){ DreamboxObj.toggleEncoderStatus(); });
		} else {
			DreamboxObj.bouquetsDiv = jQuery('#channels div[data-role="content"]');
		}
		xajax_action('initRestream');
	},

	update: function() {
		jQuery('#message').html('<p>Updating ReStream<br />This can take up to a few minutes. Leave the browser open. After the update you will be reloaded to the new version. The setup will start automaticly</p>');
		jQuery('#message').dialog({modal: true,closeOnEscape: false});
		xajax_action('updateSoftware');
	},

	log: function(caller,message) {
		if (DreamboxObj.debug == 1) {
			if (DreamboxObj.debugDiv === null) {
				DreamboxObj.debugDiv = jQuery('<div>').attr({'id':'errorLog','title':'Debug window'});
				var debugWindow = jQuery('<div>').attr({'id':'errorWrapper'});
					debugWindow.append(jQuery('<div>').attr({'id':'errorTitle'}).html('<a href="VLCLog.php">Download VLC Log</a>, <a href="javascript:void(0);" onclick="downloadDebug();">Download Debug Log</a>'));
					debugWindow.append(DreamboxObj.debugDiv);
				jQuery('body').append(debugWindow);
			}
			var logline = '[' + dateFormat(new Date(),'dd-mm-yyyy HH:MM:ss') + ']:[browser]:[' + caller + '] ' + message;
			DreamboxObj.debugDiv.html(logline + '<br />' + DreamboxObj.debugDiv.html());
		}
	},

	start: function() {
		DreamboxObj.log('start','Done loading Dreambox ReStream channel data.');
		jQuery('#message').dialog('close');
		xajax_action('loadNowAndNextEPGData',jQuery('#channels h3:first').attr('id'));
		var params = location.href.substr(location.href.indexOf('#')+1).split(':');
		if (params.length >= 2) {
			var lBouquet = decodeURIComponent(params[0]);
			var lChannel = decodeURIComponent(params[1]);
			if (params.length != 2) {
				params.shift();
				lChannel = decodeURIComponent(params.join(':'));
			}

			DreamboxObj.log('start','Auto start data: ' + lBouquet + ', ' + lChannel);

			var bouquetObj = DreamboxObj.getBouquetByName(lBouquet);
			if (bouquetObj !== false) {
				DreamboxObj.log('start','Found bouquet: ' + lBouquet);
				jQuery('#channels > h3[id="' + bouquetObj.id + '"]').click();
				var channelObj = DreamboxObj.getChannelByName(lChannel);
				if (channelObj !== false) {
					DreamboxObj.log('start','Found channel: ' + lChannel);
					jQuery('#channels > div li[id="' + channelObj.id + '"] span.zap').click();
				}
			}
		}
	},

	stop: function() {
		clearTimeout(DreamboxObj.progressBarTimer);
		jQuery('#rtsplink').html('');
		DreamboxObj.play('');
		DreamboxObj.encodingStatus('');
		xajax_action('stopWatching');
	},

	dialog: function(text) {
		jQuery('#message').dialog({modal: true,closeOnEscape: false, width: 300, draggable: false});
		if (text === '') {
			// Close the dialog
			jQuery('#message').dialog('close');
		} else {
			jQuery('#message').html('<p>' + text + '</p>');
		}
	},

	search: function(text) {
		if (text.length >= 3) {
			clearTimeout(DreamboxObj.searchTimeout);
			DreamboxObj.searchTimeout = setTimeout(function(){
				jQuery('#result li').remove();
				var re = new RegExp(text, 'ig');
				var noResults = true;
				jQuery('#channels li').each(function(){
					if (jQuery(this).text().match(re)) {
						noResults = false;
						jQuery('#result').append(jQuery(this).clone(true));
					}
				});
				if (noResults) {
					jQuery('#result').append('<li style=\"text-align: center; font-weight: bold;\">No Results</li>');
				}
			},1500);
		}
	},

	zap: function(channelid) {
		DreamboxObj.play('');
		DreamboxObj.activechannel = channelid;

		var channelObj = DreamboxObj.getChannel(DreamboxObj.activechannel);
		var bouquetObj = DreamboxObj.getBouquet(channelObj.bouquetid);

		DreamboxObj.log('zap','Zapping to channel: ' + channelObj.name);
		DreamboxObj.dialog('Zapping to channel <strong>' + channelObj.name + '</strong>');

		if (!DreamboxObj.mobile) location.href = location.href.substr(0,location.href.indexOf('#')) + '#' + bouquetObj.name + ':' + channelObj.name;
		if (DreamboxObj.mobile) {
		  location.href = location.href.substr(0,location.href.indexOf('#')) + '#watch';
		  DreamboxObj.dialog('Zapping to channel <strong>' + channelObj.name + '</strong>');
		}

		jQuery('#message').append(jQuery('<span>').attr({'id':'zaptimer'}));
		DreamboxObj.zaptimer(30 + gAdditionalTimeout);
		DreamboxObj.log('zap','Zapping to channel: ' + channelObj.name + ' waiting for ' + (30 + gAdditionalTimeout) + ' seconds now....');
		DreamboxObj.zapDone = true;
		xajax_action('startWatching',channelid);
	},

	zaptimer: function(duration) {
		clearTimeout(DreamboxObj.zapTimer);
		jQuery('#zaptimer').text('Waiting... ' + duration);
		duration--;
		if (duration > -10){
			DreamboxObj.zapTimer = setTimeout(function(){DreamboxObj.zaptimer(duration);}, 1000);
		}
	},

  play: function(url) {
		stop = (url === '');
		url = location.protocol + '//' + location.host + (url !== '' ? url : '/images/empty.m3u8');
		DreamboxObj.log('play','Starting playing the url: \'' + url + '\'');

    if (!stop) {
      jQuery('#rtsplink').html('<a href="' + url + '" target="_blank">' + url + '</a>');
      DreamboxObj.player = new Clappr.Player({
        parentId: '#videoPlayer',
        autoPlay: true,
        poster : 'images/dreambox.jpg',
        mediacontrol: {buttons: "#75abff"},
        plugins: [LevelSelector, ClapprNerdStats, ClapprStats],
        source: url,
        height: '100%',
        width: '100%',

        levelSelectorConfig: {
          title: 'Quality',
          labelCallback: function(playbackLevel) {
            return playbackLevel.level.height+'p'; // High 720p
          }
        },
      });
	  }	else {
			try{
				DreamboxObj.player.destroy();
			} catch (e) {
			}
		}
		clearTimeout(DreamboxObj.zapTimer);
		DreamboxObj.dialog('');
		DreamboxObj.startProgressBar();
	},

	showActiveProgramInfo: function() {
		DreamboxObj.log('showActiveProgramInfo','Showing the running program information');

		var channelObj = DreamboxObj.getChannel(DreamboxObj.activechannel);
		var programObj = channelObj.currentProgram();

		jQuery('#channels li').removeClass('current');
		jQuery("#channels li[id='" + channelObj.id.replace("'","\\'") + "']").addClass('current');
		if (!DreamboxObj.mobile) jQuery('.ui-accordion-content-active').scrollTo(jQuery("li.current"),1200);

		jQuery('#ProgramInfo h1').text(channelObj.name);
		jQuery('#ProgramInfo h1').append(jQuery('<span>').attr({'id':channelObj.id,'class':'icon zap','title':'Watch channel ' + channelObj.name}).bind('click',function(){ DreamboxObj.zap(this.id); }));
		jQuery('#ProgramInfo p').html('');
		if (programObj !== false) jQuery('#ProgramInfo h2').text(dateFormat(new Date(programObj.start * 1000),'HH:MM') + ' - ' + dateFormat(new Date(programObj.stop * 1000),'HH:MM') + ' - ' + humanizeDuration((programObj.stop-programObj.start) * 1000) + ': ' + programObj.name);
		if (programObj !== false) jQuery('#ProgramInfo p').html(programObj.description);
	},

	showActiveChannelImage: function(imgurl) {
		jQuery('#ProgramInfo').css({'background':'url(' + imgurl + ') no-repeat right bottom'});
	},

	startProgressBar: function() {
		DreamboxObj.log('startProgressBar','Starting the progressbar');
		clearTimeout(DreamboxObj.progressBarTimer);
		var channelObj = DreamboxObj.getChannel(DreamboxObj.activechannel);
		if (channelObj !== false) {
			var programObj = channelObj.currentProgram();

			if (programObj !== false) {
				DreamboxObj.showActiveProgramInfo();
				if (DreamboxObj.mobile) return 0; // Mobile version does not have a progress indicator... :(

				DreamboxObj.log('startProgressBar','Calculating the duration and time left');
				var duration = programObj.stop - programObj.start;
				var timedone = 1;

				if (programObj.bouquetid != '_recordings') {
					timedone = Math.round((new Date()).getTime() / 1000) - programObj.start;
				}
				var percentage = Math.round( (timedone / duration) * 100);

				DreamboxObj.log('startProgressBar','Duration: ' + duration + ', Time done: ' + timedone + ', Percentage: ' + percentage);

				jQuery('#ProgramProgress').progressbar({value: percentage}).attr('title','Program at ' + percentage + '%');
				DreamboxObj.log('startProgressBar','Updating the progressbar every ' + Math.round(duration / 100) + ' seconds');
				DreamboxObj.progressBarTimer = setInterval(function(){
					var percentage = 0;
					if (!DreamboxObj.mobile) percentage = jQuery('#ProgramProgress').progressbar('value');
					percentage++;
					DreamboxObj.log('startProgressBar','Update progressbar to ' + percentage + '%');

					if (!DreamboxObj.mobile) jQuery('#ProgramProgress').progressbar({value: percentage}).attr('title','Program at ' + percentage + '%');
					if (percentage > 100) {
						DreamboxObj.log('startProgressBar','Progressbar reached 100%. Waiting 35 seconds and restart over with new program');
						clearTimeout(DreamboxObj.progressBarTimer);
						// Restart the timer so that it will look the next program information
						DreamboxObj.progressBarTimer = setTimeout(function(){
							DreamboxObj.startProgressBar();
						}, 35 * 1000);
					}

				}, Math.round(duration / 100) * 1000);

			} else {
				DreamboxObj.log('startProgressBar','Program information not found. Trying again in 30 seconds');
				DreamboxObj.progressBarTimer = setTimeout(function(){
					DreamboxObj.startProgressBar();
				}, 30 * 1000);
			}
		} else if (DreamboxObj.activechannel !== ''){
			DreamboxObj.log('startProgressBar','Channel \'' + DreamboxObj.activechannel + '\'not found. Trying again in 30 seconds');
				DreamboxObj.progressBarTimer = setTimeout(function(){
					DreamboxObj.startProgressBar();
				}, 30 * 1000);
		}
	},

	encodingStatus: function(channelid) {
		DreamboxObj.log('encodingStatus','Clear timer');
		clearTimeout(DreamboxObj.encoderTimer);
		running = (channelid !== '');

		jQuery('#EncoderStatus').removeClass('stopped').removeClass('running');
		jQuery('#EncoderStatus').addClass((running ? 'running' : 'stopped'));

		jQuery('.EncoderIcon').removeClass('stopped').removeClass('running');
		jQuery('.EncoderIcon').addClass((running ? 'running' : 'stopped'));

		if (channelid != DreamboxObj.activechannel) {

			DreamboxObj.log('encodingStatus','Encoder channel and active channel differ. Encoder: \'' + channelid + '\', Current: \'' +DreamboxObj.activechannel + '\'');
			DreamboxObj.activechannel = channelid;

			if (running) {
				DreamboxObj.startProgressBar();
			} else {
				jQuery('#channels ul li').removeClass('current');
				DreamboxObj.activechannel = '';
			}
		}
		DreamboxObj.log('encodingStatus','Starting timer');
		DreamboxObj.encoderTimer = setTimeout(function() {
			xajax_action('encodingStatus');
		}, 30 * 1000);
	},

	toggleEncoderStatus: function() {
		if (jQuery('#EncoderStatus').hasClass('running') || jQuery('.EncoderIcon').hasClass('running')) {
			DreamboxObj.stop();
		}
	},
	// End Basic functions

	// Bouquet functions
	loadBouquets: function() {
		xajax_action('loadBouqetData');
	},

	addBouquet: function(id,name) {
		DreamboxObj.bouquets[id] = {
			id: id,
			name: name,
			amount: 0,
			channels: []
		};
	},

	removeBouquet: function(id) {
		delete DreamboxObj.bouquets[id];
		if (DreamboxObj.mobile) {
			jQuery('h3[id="' + id + '"]').parent().remove();
		} else {
			jQuery('h3[id="' + id + '"]').next('div').remove();
	                jQuery('h3[id="' + id + '"]').remove();
			setTimeout(function() {DreamboxObj.bouquetsDiv.accordion('refresh'); } , 500);
		}
		DreamboxObj.log('removeBouquet','Removed bouquetid: ' + id);
	},

	getBouquets: function() {
		return DreamboxObj.bouquets;
	},

	getBouquet: function(id) {
		return DreamboxObj.bouquets[id];
	},

	getBouquetByName: function(name) {
		for(var bouquetid in DreamboxObj.bouquets) {
			if (DreamboxObj.bouquets[bouquetid].name == name) {
				return DreamboxObj.bouquets[bouquetid];
			}
		}
		return false;
	},

	showBouquets: function() {
		DreamboxObj.bouquetsDiv.html('');
		for(var bouquetID in DreamboxObj.bouquets) {
			if (bouquetID !== '') {
				DreamboxObj.showBouquet(DreamboxObj.bouquets[bouquetID]);
			}
		}
		DreamboxObj.bouquetsDiv.accordion('refresh');
	},

	showBouquet: function(bouquetObj) {
		var div = jQuery('<div>');
		var ul = jQuery('<ul>');
		if (bouquetObj.id == '_search') {
			div.append(jQuery('<input>').attr({'name':'search','id':'search'}).bind('keyup',function(){ DreamboxObj.search(this.value);}));
			ul.attr('id','result');
		}
		div.append(ul);
		DreamboxObj.bouquetsDiv.append(jQuery('<h3>').attr('id',bouquetObj.id).text(bouquetObj.name));
		DreamboxObj.bouquetsDiv.append(div);
	},
	// End Bouquet functions

	// Channel functions
	loadChannels: function() {
		DreamboxObj.log('loadChannels','Loading all channels per bouquete');
		if (DreamboxObj.bouqueteQueue instanceof Array) {
			if (DreamboxObj.bouqueteQueue.length > 0) {
				var bouquetData = DreamboxObj.bouquets[DreamboxObj.bouqueteQueue.shift()];
				DreamboxObj.log('loadChannels','Loading channels: ' + bouquetData.name);
				xajax_action('loadChannelData',bouquetData.id);
			}
		} else {
			// Load the bouguete queue
			DreamboxObj.log('loadChannels','Filling the boutique queue');
			DreamboxObj.bouqueteQueue = [];
			for(var bouquetID in DreamboxObj.bouquets) {
				if (bouquetID.substr(0,1) != '_') {
					DreamboxObj.bouqueteQueue[DreamboxObj.bouqueteQueue.length] = bouquetID;
				}
			}
			DreamboxObj.log('loadChannels','Done filling the boutique queque');
			DreamboxObj.loadChannels();
		}
	},

	addChannel: function(id,bouquetid,name,type,hd) {
		DreamboxObj.bouquets[bouquetid].channels[id] = {
			id			  : id,
			bouquetid	: bouquetid,
			name		  : name,
			type		  : type,
			hd			  : hd,
			programs  : [],

			currentProgram: function() {
				var now = Math.round((new Date()).getTime() / 1000); // GMT timestamp
				for(var i = 0; i < this.programs.length; i++) {
					if (this.programs[i].start < now && this.programs[i].stop > now) {
						return this.programs[i];
					}
				}
				return false;
			},
		};
		DreamboxObj.bouquets[bouquetid].amount++;
	},

	getChannels: function(bouquetid) {
		return DreamboxObj.bouquets[bouquetid].channels;
	},

	getChannel: function(channelid) {
		for(var bouquetid in DreamboxObj.bouquets) {
			if (DreamboxObj.bouquets[bouquetid] !== undefined) {
				for (var channel in DreamboxObj.bouquets[bouquetid].channels) {
					if (channel == channelid) {
						return DreamboxObj.bouquets[bouquetid].channels[channelid];
					}
				}
			}

		}
		return false;
	},

	getChannelByName: function(name) {
		for(var bouquetid in DreamboxObj.bouquets) {
			if (DreamboxObj.bouquets[bouquetid] !== undefined) {
				for (var channelid in DreamboxObj.bouquets[bouquetid].channels) {
					if (DreamboxObj.bouquets[bouquetid].channels[channelid].name == name) {
						return DreamboxObj.bouquets[bouquetid].channels[channelid];
					}
				}
			}
		}
		return false;
	},

	showAllChannels: function() {
		for(var bouquetid in DreamboxObj.bouquets) {
			if (bouquetid !== '') {
				DreamboxObj.showChannels(bouquetid);
			}
		}
	},

	showChannels: function(bouquetid) {
		for(var channelid in DreamboxObj.bouquets[bouquetid].channels) {
			if (channelid !== '') {
				DreamboxObj.showChannel(bouquetid,channelid);
			}
		}
		DreamboxObj.bouquetsDiv.accordion('refresh');
	},

	showChannel	: function(bouquetid,channelid) {
		var channelObj = DreamboxObj.bouquets[bouquetid].channels[channelid];
		var channelDiv = jQuery("#channels > h3[id='" + bouquetid + "'] + div > ul");
		var li = jQuery('<li>');


		if (channelObj.type == 'marker') {
			li.attr('class','separator');
		} else {
			li.attr({'id':channelObj.id,'class':'channel'});
			li.append(jQuery('<span>')	.attr({'class':'icon zap','title':'Watch channel ' + channelObj.name})
										.bind('click',function(){ DreamboxObj.zap(jQuery(this).parent().attr('id')); })
					);
			if (channelObj.hd) {
				li.append(jQuery('<span>')	.attr({'class':'icon hd','title':'HD Channel'}));
			}
			li.append(jQuery('<span>')	.attr({'class':'icon tvguide','title':'Show EPG for channel ' + channelObj.name})
										.bind('click',function(){ DreamboxObj.showEPG(jQuery(this).parent().attr('id')); })
					);
			li.append(channelObj.name);
			var p = jQuery('<p>').attr('class','programs');
			p.append(jQuery('<span>').attr('class','now'));
			p.append(jQuery('<span>').attr('class','next'));
			li.append(p);

		}
		channelDiv.append(li);
	},
	// End Channel functions

	// Recording functions
	loadRecordings: function() {
		xajax_action('loadRecordingData');
	},

	addRecording : function(id,name,start,channel,duration,description,long_description,filesize) {
		DreamboxObj.bouquets._recordings.channels[id] = {
			id: id,
			name: name,
			bouquetid	: '_recordings',
			duration: duration,
			start: start,
			stop: start + duration,
			channel: channel,
			shortdescription: description,
			description: '<strong>' + description + '</strong><br />' + long_description,
			filesize: filesize,

			currentProgram: function() {
				return this;
			},
			sortOnStartTime: function() {

			},
		};
		DreamboxObj.bouquets._recordings.amount = DreamboxObj.bouquets._recordings.channels.length;
	},

	getRecordings: function() {
		return DreamboxObj.bouquets._recordings.channels;
	},

	showRecordings: function() {
		for(var recordingid in DreamboxObj.bouquets._recordings.channels) {
			if (recordingid !== '') {
				DreamboxObj.showRecording(recordingid);
			}
		}
		DreamboxObj.bouquetsDiv.accordion('refresh');
	},

	showRecording: function(recordingid) {
		var recordingObj = DreamboxObj.bouquets._recordings.channels[recordingid];
		var channelDiv = jQuery("#channels > h3[id='_recordings'] + div > ul");
		var li = jQuery('<li>').text(recordingObj.name).attr('title',recordingObj.shortdescription);

		li.attr({'id':recordingObj.id,'class':'channel'});
		li.append(jQuery('<span>').attr({'class':'icon zap','title':'Watch recording ' + recordingObj.name}).bind('click',function(){ DreamboxObj.zap(jQuery(this).parent().attr('id')); }));
		var p = jQuery('<p>').attr('class','programs');
		p.append(jQuery('<span>').attr('class','now').html('<strong>' + dateFormat(new Date(recordingObj.start * 1000),'dd-mm-yyyy') + '</strong>' + (recordingObj.duration > 0 ? ' - ' + humanizeDuration(recordingObj.duration * 1000) : '') + (recordingObj.channel !== '' ? ', ' + recordingObj.channel : '')));
		li.append(p);
		channelDiv.append(li);
	},
	// End Recording functions

	// Movies functions
	loadMovies: function() {
		xajax_action('loadMovieData');
	},

	addMovie : function(id,name,duration,filesize,bitrate,resolution,languages,hd) {
		if (languages[0] === '') languages = [];
		DreamboxObj.bouquets._movies.channels[id] = {
			id: id,
			name: name,
			bouquetid	: '_movies',
			duration: duration,
			filesize: filesize,
			bitrate: bitrate,
			resolution:resolution,
			languages: languages,
			hd: hd,
			date: null,

			currentProgram: function() {
				// Fake the start and end times....
				this.start = Math.round((new Date()).getTime() / 1000);
				this.stop = this.start+this.duration;
				return this;
			},
			sortOnStartTime: function() {
			},
		};
		DreamboxObj.bouquets._movies.amount = DreamboxObj.bouquets._movies.channels.length;
	},

	getMovies: function() {
		return DreamboxObj.bouquets._movies.channels;
	},

	showMovies: function() {
		for(var movieid in DreamboxObj.bouquets._movies.channels) {
			if (movieid !== '') {
				DreamboxObj.showMovie(movieid);
			}
		}
		DreamboxObj.bouquetsDiv.accordion('refresh');
	},

	showMovie: function(movieid) {
		var movieObj = DreamboxObj.bouquets._movies.channels[movieid];
		var channelDiv = jQuery("#channels > h3[id='_movies'] + div > ul");
		var li = jQuery('<li>');
		li.attr({'id':movieObj.id,'class':'channel'});
		li.append(jQuery('<span>').attr({'class':'icon zap','title':'Watch movie ' + movieObj.name}).bind('click',function(){ DreamboxObj.zap(jQuery(this).parent().attr('id')); }));
		if (movieObj.hd) {
			li.append(jQuery('<span>').attr({'class':'icon hd','title':'HD Movie'}));
		}
		if (movieObj.languages.length > 0) {
			li.append(jQuery('<span>').attr({'class':'icon subtitle','title':'Subtitle(s): ' + movieObj.languages}).text(movieObj.languages));
		}
		li.append(movieObj.name);
		var p = jQuery('<p>').attr('class','programs');
		var movieParts = [];
		if (movieObj.duration > -1) {
			movieParts[movieParts.length] = '<strong>' + humanizeDuration(movieObj.duration*1000) + '</strong>';
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
		p.append(jQuery('<span>').attr('class','now').html(movieParts.join(',')));

		li.append(p);
		channelDiv.append(li);
	},
	// End Movies functions

	// WebCam Functions
	addWebCam : function(id,name) {
		if (languages[0] === '') languages = [];
		DreamboxObj.bouquets._webcams.channels[id] = {
			id: id,
			name: name,
			bouquetid	: '_webcams',

			currentProgram: function() {
				return this;
			},

			sortOnStartTime: function() {
			},
		};
		DreamboxObj.bouquets._webcams.amount = DreamboxObj.bouquets._webcams.channels.length;
	},

	getWebCams: function() {
		return DreamboxObj.bouquets._webcams.channels;
	},

	showWebCams: function() {
		for(var movieid in DreamboxObj.bouquets._webcams.channels) {
			if (movieid !== '') {
				DreamboxObj.showWebCam(movieid);
			}
		}
		DreamboxObj.bouquetsDiv.accordion('refresh');
	},

	showWebCam: function(webcamid) {
		var webCamObj = DreamboxObj.bouquets._webcams.channels[webcamid];
		var channelDiv = jQuery("#channels > h3[id='_webcams'] + div > ul");
		var li = jQuery('<li>').text(webCamObj.name);

		li.attr({'id':webCamObj.id,'class':'channel'});
		li.append(jQuery('<span>').attr({'class':'icon zap','title':'Watch webcam ' + webCamObj.name}).bind('click',function(){ DreamboxObj.zap(jQuery(this).parent().attr('id')); }));
		channelDiv.append(li);
	},
	// End WebCam functions

	// Program functions
	addProgram: function(id,channelid,name,start,stop,description) {
		var channelObj = DreamboxObj.getChannel(channelid);
		if (channelObj !== false) {
			channelObj.programs.push({
				id: id,
				name: name,
				start: start,
				stop: stop,
				description: description
			});
		}
	},

	showProgram: function(channelid,programObj) {
		var now = Math.round((new Date()).getTime() / 1000); // GMT timestamp
		var spanClass = (programObj.start < now && programObj.stop > now ? 'now' : 'next');
		var spanObj = jQuery("li[id='" + channelid + "'] p.programs span." + spanClass);
		spanObj.html(jQuery('<strong>').text(dateFormat(new Date(programObj.start * 1000),'HH:MM'))).attr({'title' : dateFormat(new Date(programObj.start * 1000),'HH:MM') + ' ' + programObj.name});
		spanObj.append(programObj.name);
	},

	clearPrograms: function(bouquetid,channelid) {
            DreamboxObj.bouquets[bouquetid].channels[channelid].programs = [];
	},

	showCurrentPrograms: function(bouquetid) {
		var now = Math.round((new Date()).getTime() / 1000); // GMT timestamp
		var diff = 999999999;
		DreamboxObj.log('showCurrentPrograms','Updating current programs in bouquet \'' + DreamboxObj.bouquets[bouquetid].name + '\'');
		for(var channelid in DreamboxObj.bouquets[bouquetid].channels) {
			var showNext = false;
			var deleteIds = [];
			for (var i = 0; i < DreamboxObj.bouquets[bouquetid].channels[channelid].programs.length; i++) {
				var programObj = DreamboxObj.bouquets[bouquetid].channels[channelid].programs[i];
				if (programObj.stop < now) {
					// Clean up old programs
					deleteIds.push(i);
				} else {
					if ((programObj.start <= now && programObj.stop > now) || showNext ) {
						if (!showNext) {
							if ((programObj.stop - now) < diff) diff = programObj.stop - now;
						}
						DreamboxObj.showProgram(channelid,programObj);
						showNext = (programObj.start < now && programObj.stop > now);
					} else {
						i = DreamboxObj.bouquets[bouquetid].channels[channelid].programs.length + 1;
					}
				}
			}
			deleteIds.sort(function(a,b){return b - a;});
			for (var i = 0; i < deleteIds.length; i++) {
			  DreamboxObj.log('showCurrentPrograms','Deleted program \'' + DreamboxObj.bouquets[bouquetid].channels[channelid].programs[i].name + '\' on channel \'' + DreamboxObj.bouquets[bouquetid].channels[channelid].name + '\' from bouquet \'' + DreamboxObj.bouquets[bouquetid].name + '\'');
				DreamboxObj.bouquets[bouquetid].channels[channelid].programs.splice(deleteIds[i],1);
			}
		}
		if (DreamboxObj.mobile) {
			jQuery('h3[id="' + bouquetid + '"] + div > p > ul').listview('refresh');
		}
		if (diff != 999999999) {
			DreamboxObj.log('showCurrentPrograms','Next update for bouquet \'' + DreamboxObj.bouquets[bouquetid].name + '\' at ' + (diff+5) + ' seconds');
			clearTimeout(DreamboxObj.programTimer[bouquetid]);

			DreamboxObj.programTimer[bouquetid] = setTimeout(function() {
				DreamboxObj.showCurrentPrograms(bouquetid);
				}, (diff+5) * 1000);
		}
	},

	showEPG: function(channelid) {
		var channelObj = DreamboxObj.getChannel(channelid);
		var div = jQuery('<div>').attr({'id':'epglist'});

		for (var programid in channelObj.programs) {
			var programObj = channelObj.programs[programid];
			var programDiv = jQuery('<div>').attr('class','program');

			programDiv.append(jQuery('<span>')	.attr({'class':'title'})
												.text(programObj.name)
			);

			programDiv.append(jQuery('<span>')	.attr({'class':'time'})
												.html(dateFormat(new Date(programObj.start * 1000),'dddd d mmmm @ HH:MM') + ' - ' + dateFormat(new Date(programObj.stop * 1000),'HH:MM') + '<br />Duration: ' + humanizeDuration((programObj.stop-programObj.start) * 1000))
			);
			programDiv.append(jQuery('<span>')	.attr({'class':'description'})
												.html(programObj.description)

			);
			div.append(programDiv);
		}
		jQuery('#epg').dialog({title: "EPG channel " + channelObj.name , modal: false, width: '85%', height: Math.round($(window).height() * 0.9) , draggable: false}).html(div);
	},

	loadPrograms: function() {
		var bouquetid = null;
		DreamboxObj.log('loadPrograms','Loading all program data');
		if (DreamboxObj.bouquetQueue !== null && DreamboxObj.running === 0) {
		  if (DreamboxObj.bouquetQueue.length > 0) {
  		  bouquetid = DreamboxObj.bouquetQueue.shift();
  		  DreamboxObj.log('loadPrograms','Loading EPG for bouguet \'' + bouquetid + '\'');
  		  xajax_action('loadChannelEPGData',bouquetid);
		  }
		} else {
			if (DreamboxObj.running === 0) {
				DreamboxObj.running = 1;
				// Load the channel queue
				DreamboxObj.log('loadPrograms','Loading all program data starting with empty queue');
				DreamboxObj.bouquetQueue = [];
				for(bouquetid in DreamboxObj.bouquets) {
				  if (bouquetid.substr(0,1) !== '_') {
  				  DreamboxObj.bouquetQueue.push(bouquetid);
  				  DreamboxObj.log('loadPrograms','Queued bouguet \'' + bouquetid + '\'');
				  }
				}
				DreamboxObj.log('loadPrograms','Done filling the queque');
				DreamboxObj.running = 0;
				DreamboxObj.loadPrograms();
			}
		}
	},
	// End program functions
};
xajax.callback.global.onRequest  = function(){jQuery("#loading").show();};
xajax.callback.global.onComplete = function(){jQuery("#loading").hide();};

function downloadDebug() {
	// Parsing the log data to change the order of the messages in a chronocal way
	var logData = {};
	var logDataSortOrder = [];
	var logDataSource = jQuery('#errorLog').html().split('<br>');

	jQuery.each(logDataSource,function(index,value){
		value = value.trim();
		var line = value.split(']');
		var indexTime = line[0].substr(1);

		indexTime = indexTime.split(' ');
		if (indexTime.length == 2) {
			var date = indexTime[0].split('-');
			var time = indexTime[1].split(':');

			indexTime = Date.UTC(date[2], date[1], date[0], time[0], time[1], time[2]);
			if (logDataSortOrder.indexOf(indexTime) == -1) {
				logDataSortOrder.push(indexTime);
			}
			if(indexTime in logData){
				logData[indexTime] = value + "<br/>\n" + logData[indexTime];
			} else {
				logData[indexTime] = value + "<br/>\n";
			}
		}
	});

	var logHTML = '';
	logDataSortOrder.sort(function(a,b){return b-a;});
	for (var i = 0; i < logDataSortOrder.length; i++) {
		logHTML += logData[logDataSortOrder[i]];
	}
	myWindow=window.open('','Debug log','menubar=yes,scrollbars=yes,width=' + (window.screen.availWidth - 100) + ',height=' + (window.screen.availHeight - 100));
	myWindow.document.write(logHTML);
	myWindow.focus();
}

function showChangeLog() {
	jQuery.get('CHANGELOG', function(data) {
		var regexp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
		jQuery('#epg').dialog({title: "CHANGELOG", modal: false, width: '85%', height: Math.round($(window).height() * 0.9) , draggable: false}).html('<pre>' + data.replace(regexp,'<a href="$1" target="_blank" class="external">$1</a>') + '</pre>');
	});
}

function getUrlParam(name) {
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if( results === null )
		return "";
	else
		return decodeURIComponent(results[1].replace(/\+/g, " "));
}

function bitrateFormat(bytes, precision) {
	var bitrate = getBytesWithUnit(bytes, false, precision, true);
	bitrate = bitrate.split(' ');
	return (bitrate[0]*1).toFixed(precision) + ' ' + bitrate[1].replace(/B/,'bps');
}

// function: getBytesWithUnit
// input: bytes (number)
// input: useSI (boolean), if true then uses SI standard (1KB = 1000bytes), otherwise uses IEC (1KiB = 1024 bytes)
// input: precision (number), sets the maximum length of decimal places.
// input: useSISuffix (boolean), if true forces the suffix to be in SI standard. Useful if you want 1KB = 1024 bytes
// returns (string), represents bytes is the most simplified form.
function getBytesWithUnit(bytes, useSI, precision, useSISuffix) {
	//"use strict";
	if (!(!isNaN(bytes) && +bytes > -1 && isFinite(bytes))) {
		return false;
	}
	var units, obj,	amountOfUnits, unitSelected, suffix;
	units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
	obj = {
		base : useSI ? 10 : 2,
		unitDegreeDiff : useSI ? 3 : 10
	};
	amountOfUnits = Math.max(0, Math.floor(Math.round(Math.log(+bytes) / Math.log(obj.base) * 1e6) / 1e6));
	unitSelected = Math.floor(amountOfUnits / obj.unitDegreeDiff);
	unitSelected = units.length > unitSelected ? unitSelected : units.length - 1;
	suffix = (useSI || useSISuffix) ? units[unitSelected] : units[unitSelected].replace('B', 'iB');
	bytes = +bytes / Math.pow(obj.base, obj.unitDegreeDiff * unitSelected);
	precision = precision || 3;
	if (bytes.toString().length > bytes.toFixed(precision).toString().length) {
		bytes = bytes.toFixed(precision);
	}
	return bytes + " " + suffix;
}

jQuery(document).ready(function() {
	if (gSetup === 0) {
		DreamboxObj.init(getUrlParam('debug') == 1);
	} else if (gSetup == 1) {
		jQuery('#ProgramGuide').height(jQuery('#PlayerWrapper').height());
		jQuery('#channels').accordion({ heightStyle: "fill" });
		jQuery("[title]").tooltip();
	}
});
