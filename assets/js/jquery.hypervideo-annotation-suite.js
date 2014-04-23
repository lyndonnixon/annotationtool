(function( $ ) {
	
	var userAnnotations = '';
	var updateEntry		= '';
	
	var methods = {
		/**
		 *	init
		 *	Initialisation of the Hypervideo Annotation Suite jQuery Plugin
		 */
    	init : function(options) {
			
			var settings = $.extend( {
			  'display' : 'block'
			}, options);
			
			// bind neccessary functions to buttons
			$('.save-annotation').bind('click', function() {
				methods._saveAnnotation();
			});
			$('#search-string').bind('keyup', function() {
				methods.startSearch(0);		
			});
			$('#video-open').bind('click', function() {
				methods.loadVideoFromURL($('#video-input-url').val());
			});
			$('#clear-annotations').bind('click', function() {
				$('#annotation-list-content').empty();
				$.cookie('annotationlist', null, { expires: -1, path: '/'});
			});
			$('#clear-current-annotations').bind('click', function() {
				$('#x1').val('');
				$('#y1').val('');
				$('#x2').val('');
				$('#y2').val('');
				$('#w').val('');
				$('#h').val('');
				$('#st').val('');
				$('#et').val('');
				$('#annotation-type').val('explicitlyMentions');
				$('annotation-search-box').empty();
			});
			$('#clear-settings').bind('click', function() {
				$.cookie('development_mode', null, { expires: -1, path: '/'});
				$.cookie('lmdb-src', null, { expires: -1, path: '/'});
				location.reload();	
			});
			$('#unload-video').bind('click', function() {
				$.cookie('video_uri', null, { expires: -1, path: '/'});
				location.reload();
			});
			$('#settings-save').bind('click', function() {
				if ($('#devmode').is(':checked')) {
					$.cookie('development_mode', true, { expires: 7, path: '/'});
				}
				else {
					$.cookie('development_mode', null, { expires: -1, path: '/'});
				}
				if ($('#lmdb-source').val() != '') {
					$.cookie('lmdb_src', $('#lmdb-source').val(), { expires: 7, path: '/'});
				}
				else {
					$.cookie('lmdb_src', null, { expires: -1, path: '/'});
				}
				// reload page
				location.reload();	
			});
			// set annotation start timestamp
			$('#start-annotation').bind('click', function () {
				// set start time
				$('#st').val($('span.vjs-current-time-display').text());
				// show time to user
				$('.control-time-start .time-content').empty();
				$('.control-time-start .time-content').append($('#st').val());	
			});
			// set annotation end timestamp
			$('#end-annotation').bind('click', function () {
				// valid end time?
				if ($('span.vjs-current-time-display').text() > $('#st').val()) {
					// set start time
					$('#et').val($('span.vjs-current-time-display').text());
					// show time to user
					$('.control-time-end .time-content').empty();
					$('.control-time-end .time-content').append($('#et').val());	
				}
				else {
					var errorDialog = $('<div id="error-message"></div>')
						.html('Attention: Setting an end timestamp before a start timestamp is not allowed!')
						.dialog({
						  	title: 'Error',
						  	modal: true,
						  	resizable: false,
						  	buttons: {
							Ok: function() {
								errorDialog.dialog('close');
							}
						}
					});
					errorDialog.dialog('open');
				}
			});
			// clear current annotation and search results
			$('#clear-results').bind('click', function() {
			  $('#search-result-description').empty();
			  $('#search-result-label').empty();
			  $('#annotation-search-box').empty();
			});
			$('.button-edit').bind('click', function() {
				methods._editEntry($(this));
			});
			// delete entry
			$('.button-delete').bind('click', function () {
				methods._deleteEntry($(this));
			});
			$('.entry .time').bind('click', function () {
				methods.playVideoAt($(this));
			});
			
			
			// if annotation file has been set => update annotation list
			if ($.cookie('annotationlist')) {
				methods._getAnnotations($.cookie('annotationlist'));
			}
			
			// look for video cookie
			// start video player if video uri has been set
			if ($.cookie('video_uri')) {
				
				// load video
				myPlayer = VideoJS.setup("loaded_video",
				{
					"controlsBelow" : true, // Display control bar below video vs. in front of
					"controlsAtStart" : true, // Make controls visible when page loads
					"controlsHiding" : false
				});
				
				// start listener for annotations
				myPlayer.addVideoListener("timeupdate", function(obg) { 
				
					var currentPlaybackTime = parseInt(Math.round(myPlayer.video.currentTime), 10);
					
					// show annotation areas only when editing mode is false
					if ($.isNumeric(parseInt(updateEntry, 10))) {
						// edit mode is active => do nothing
					}
					else {
						
						// annotations have to be available to get show
						if ($.cookie('annotationlist')) {
							
							// load annoation json if userAnnotations is empty
							if (userAnnotations == '') {
								userAnnotations = methods._getAnnotations($.cookie('annotationlist'));
							}
							
							// search through annotation list if one or more active annotations can get found
							var annotation_list = jQuery.parseJSON(userAnnotations);
							var annotation_data = new Array();
							$.each(annotation_list.annotations, function (i, annotation) {
								var starttime = methods._convertTimeToSeconds(annotation.starttime);
								var endtime = methods._convertTimeToSeconds(annotation.endtime);
								// DEVELOPMENT HELPER: $('#search-result-description').append(i + ': ' + starttime + ' --> ' + endtime + '<br />');
								// current time >= start time ?
								// current time <= end time ?
								if ((currentPlaybackTime >= starttime) && (currentPlaybackTime <= endtime)) {
									// create annotation div and highlight it
									methods.showAnnotation(i);
									// highlight list entry
								}
								else {
									// hide annotation layer if its visible
									methods.hideAnnotation(i);
								}
							})
						}
					}		
				})
				
				// enable area selection
				areaSelector = $('#loaded_video').imgAreaSelect({
					handles: true,
					fadeSpeed: 200,
					onSelectChange: methods.previewAnnotationData,
					instance: true
				})
			}
		},
		/**
		 *	activatePlugin
		 *	Loads the code of a selected plugin to make it ready to use.
		 *
		 *	@param: annotation_file string - filename of annotation list
		 */
		_activatePlugin: function (plugin_path) {
			var http_method = 'POST';
			var query = plugin_path;
			var post_data = ''
			$.ajax({
				url: query,
				type: http_method,
				data: post_data,
				async: false,
				cache: false,
				timeout: 30000,
				error: function(){
					return true;
				}
			})
			.done(function (data) {
				$('#plugin-area').append(data);
			})
		},
		/**
		 *	getAnnotations
		 *	Gets all annotations from an annotation file
		 *
		 *	@param: annotation_file string - filename of annotation list
		 */
		_getAnnotations: function (annotation_file) {
			methods._fileOps('read', annotation_file, '');
		},
		/**
		 *	fileOps
		 *	Allows reading from a file and writing to one
		 *
		 *	@param: fnc string - function which gets used (read or write)
		 *	@param: filename string - filename for reading/writing
		 *	@param: data string - data set which shall get inserted into file
		 *	@param: clear int - file gets deleted, if clear is set to 1
		 */	
		_fileOps : function(fnc, filename, json_data) {
			if (fnc == '') {
				$('#search-result-description').text('No function specified!');
				return;
		  	}
		  	else {
				switch (fnc) {
					case 'read':
						selOp 	= 'functions/getData.php';
						update	= true;
						break;
				  
					case 'write':
						selOp 	= 'functions/addData.php';
						update	= false;
						break;
				  
					default:
						selOp 	= 'functions/getData.php'
						update	= true;
				}
		  	}
		  
		  	if (filename == '') {
				$('#search-result-description').text('No filename specified');
				return;
		  	}
			
			var http_method = 'POST';
			var query = selOp;
			var post_data = 'file=' + filename + '&data=' + json_data
			$.ajax({
				url: query,
				type: http_method,
				data: post_data,
				async: false,
				cache: false,
				timeout: 30000,
				error: function(){
					return true;
				}
			})
			.done(function (data) {
				userAnnotations = data;
			})
		},
		/**
		 *	showAnnotation
		 *	Shows an annotation area within the video region and adds its description texts to the explore area
		 *
		 *	@param: index int - id of entry which has to get hidden
		 */	
		showAnnotation : function(index) {
			// get annotation data from json object
			var annotation_list = jQuery.parseJSON(userAnnotations);
			$.each(annotation_list.annotations, function (i, annotation) {
				if ((i == index)) {
					if (annotation.description) {
					var tmp_str = (annotation.description).split('$$');
					var tmp_desc = '';
					for (w = 0; w < tmp_str.length; w++) {
						tmp_desc = tmp_desc + tmp_str[w];
						if (w < (tmp_str.length - 1)) {
							tmp_desc = tmp_desc + '&';
						}
					 }
					 annotation.description = tmp_desc;
				  }
					annotation_data = new Array(i, annotation.label, annotation.uri, parseInt(annotation.x1), parseInt(annotation.y1), parseInt(annotation.x2), parseInt(annotation.y2), parseInt(annotation.width), parseInt(annotation.height), (annotation.description), annotation.type);
				}
			});
			if (annotation_data.length > 0) {
				// add annotation div only if it hasn't been already created
				if ($('#ann_' + annotation_data[0]).length == 0) {
					// get title and description from uri
					// generate annotation video marker
					$('#video-player .has-box-400').append('<div id="ann_' + annotation_data[0] + '"></div>')
					$('#ann_' + annotation_data[0]).addClass('annotation-area');
					$('#ann_' + annotation_data[0]).css('display', 'block');
					$('#ann_' + annotation_data[0]).css('width', annotation_data[7] + 'px');
					$('#ann_' + annotation_data[0]).css('height', annotation_data[8] + 'px');
					$('#ann_' + annotation_data[0]).css('top', annotation_data[4] + 'px');
					$('#ann_' + annotation_data[0]).css('left', annotation_data[3] + 'px');
					$('#search-result-description').html((annotation_data[9]));
					$('#search-result-label').text(annotation_data[1]);
					$('#annotation-type').val(annotation_data[10]);
				}
			}
			else {
				// annotation could not get identified => throw error message
				
			}
		},
		/**
		 *	hideAnnotation
		 *	Hides an annotation div which is shown within the video area and removes its description texts from the explore area
		 *
		 *	@param: index int - id of entry which has to get hidden
		 */	
		hideAnnotation : function(index) {
			// hide annotation div if its visible
			if ($('#ann_' + index).length > 0) {
				// remove annotation layer
				$('#ann_' + index).remove();
				// empty annotation text areas if no other annotation is active
				if ($('.annotation-area').length <= 0) {
					$('#search-result-description').empty();
					$('#search-result-label').empty();
				}
			}
		},
		/**
		 *	previewAnnotationData
		 *	Allows previewing the selected time and spatial values of an annotation before saving it
		 *
		 *	@param: img object - video spatial area
		 *	@param: selection array - selected area within the video
		 */	
		previewAnnotationData : function (img, selection) {
			if (!selection.width || !selection.height) {
				return;
			}
			
			var scaleX = 100 / selection.width;
		  	var scaleY = 100 / selection.height;
		
		  	$('#preview img').css({
				width: Math.round(scaleX * 300),
				height: Math.round(scaleY * 300),
				marginLeft: -Math.round(scaleX * selection.x1),
				marginTop: -Math.round(scaleY * selection.y1)
		  	});
		
		  	$('#x1').val(selection.x1);
		  	$('#y1').val(selection.y1);
		  	$('#x2').val(selection.x2);
		  	$('#y2').val(selection.y2);
		  	$('#w').val(selection.width);
		  	$('#h').val(selection.height);
		  	// set the spatial selector to true => area has been selected
		  	$('.control-spatial .set-content').empty();
		  	$('.control-spatial .set-content').append('<img src="images/icon-yes.gif" alt="Region set" title="Region set" />');
		},
		/**
		 *	saveAnnotation
		 *	Saves a new annotation to an annotation list
		 *
		 *	@output: updated or generated annotation in a file which is saved on the server and a cookie with the files name
		 */	
		_saveAnnotation : function() {
			
			// video dimensions
			var vid = document.getElementById("loaded_video");
			
			// save current annotation to the annotation list
			// check required input values: video loaded, timestamp begin, timestamp end, coordinates, dimensions, selected annotation
			var error 	= false;
			var errMess	= new Array();
			// check if video has been loaded
			if (($.cookie('video_uri') == null) || (typeof $.cookie('video_uri') == 'undefined')) {
				errMess.push('You have to select a valid search result to add an annotation!');
				error = true;
			}
			
			// check if time has been set
			if (($.isNumeric(parseInt($('#st').val().substring(0, 2), 10)) == false) || ($.isNumeric(parseInt($('#st').val().substring(3, 5), 10)) == false)) {
			  errMess.push('You have to select a valid start time to add an annotation!');
			  error = true;
			}
			if (($.isNumeric(parseInt($('#et').val().substring(0, 2), 10)) == false) || ($.isNumeric(parseInt($('#et').val().substring(3, 5), 10)) == false)) {
			  errMess.push('You have to select a valid end time to add an annotation!');
			  error = true;
			}
			// end time <= start time?
			if (methods._convertTimeToSeconds($('#et').val()) <= methods._convertTimeToSeconds($('#st').val())) {
			  errMess.push('Attention: Setting an end timestamp before a start timestamp is not allowed!');
			  error = true;
			}
			// check if video area has been set
			if (($.isNumeric(parseInt($('#x1').val())) != true) || ($.isNumeric(parseInt($('#x2').val())) != true) || ($.isNumeric(parseInt($('#y1').val()))!= true) || ($.isNumeric(parseInt($('#y2').val())) != true)) {
			  errMess.push('You have to select an area within the video to add an annotation!');
			  // error = true;
			}
			
			// either spatial area or temporal area or both have to be set
			// if ((start_ts == false) && (end_ts == false))
			
			if ($.isNumeric(parseInt(updateEntry), 10)) {
				// update existing entry => 
				if (($('#search-results #selected ul li.value').text() == '') || ($('#search-results #selected ul li.value').text() == null) || (typeof $('#search-results #selected').text() == 'undefined')) {
				  errMess.push('You have to select a valid search result to add an annotation!');
				  error = true;
				}
			}
			else {
				// check if search result has been selected
				if (($('#search-results #selected ul li.value').text() == '') || ($('#search-results #selected ul li.value').text() == null) || (typeof $('#search-results #selected').text() == 'undefined')) {
				  // MODIFIED FOR URI INPUT INSTEAD OF SEARCH RESULT
				  // no search result selected => uri specified instead?
				  if (($('#uri-ressource').val() != '')) {
					  // append uri ressource as selected search result
					  $('#annotation-search-box').append('<ul id="search-results" name="search-results" size="11"><li class="result" id="selected"><ul class="content"><li class="value">' + $('#uri-ressource').val() + '</li></ul></li></ul>');
					  
				  }
				  else {
					errMess.push('You have to select a valid search result or enter a valid uri to add an annotation!');
					error = true;
			      }
				  // MODIFIED FOR URI INPUT INSTEAD OF SEARCH RESULT - END
				}
			}
			
			// any error so far?
			if (error == true) {
			  
			  // at least one error occurred, stop function
			  var showMessage = '';
			  for (i = 0; i < errMess.length; i++) {
				showMessage = showMessage + errMess[i] + '<br />';
			  }
			  // $('body').append('<div id="#error-message" title="Error">' + showMessage + '</div>');
			  
			  // $("#error-message:ui-dialog").dialog("Acknowledged");
			  var errorDialog = $('<div id="error-message"></div>')
				.html(showMessage)
				.dialog({
				  title: 'Error',
				  modal: true,
				  resizable: false,
				  buttons: {
					Ok: function() {
					  errorDialog.dialog('close');
					}
				  }
				});
			  errorDialog.dialog('open');
			}
			else {
			  // no errors, continue processing json
			  // generate json string
			  // look for json cookie
			  /* Annotation cookie found, add further annotations to the existing */
			  if ($.cookie('annotationlist')) {
				  var jsonstr = userAnnotations;
				  var anArr	= new Array();
				  var obj = jQuery.parseJSON(jsonstr);
				  $.each(obj.annotations, function (i, annotation) {
						if ((annotation.cmfuri == undefined) || (annotation.cmfuri == NaN)) {
							annotation.cmfuri = '';
						}
						if ((annotation.fragmenturi == undefined) || (annotation.fragmenturi == NaN)) {
							annotation.fragmenturi = '';
						}
						if ((annotation.creator == undefined) || (annotation.creator == NaN)) {
							annotation.creator = '';
						}
						console.log('cmfuri: ' + annotation.cmfuri);
						var tmpArr = new Array(annotation.starttime, annotation.endtime, annotation.uri, annotation.label, annotation.x1, annotation.x2, annotation.y1, annotation.y2, annotation.width, annotation.height, ((annotation.description).replace(/"/g, "'")).replace('/&/g', '§§'), annotation.type, annotation.cmfuri, encodeURIComponent(annotation.fragmenturi), encodeURIComponent(annotation.creator));
						anArr.push(tmpArr);
				  });
				  // add current values to the array
				  if ($('#search-result-description')) {
					var tmp_str = $('#search-result-description').html().replace(/"/g, "'");
					tmp_str = tmp_str.split('&');
					var tmp_desc = '';
					for (w = 0; w < tmp_str.length; w++) {
						tmp_desc = tmp_desc + tmp_str[w];
						if (w < (tmp_str.length - 1)) {
							tmp_desc = tmp_desc + '$$';
						}
					 }
				  }
				  var tmpArr = new Array($('#st').val(), $('#et').val(), $('#search-results #selected ul li.value').text(), $('#search-result-label').text(), $('#x1').val(), $('#x2').val(), $('#y1').val(), $('#y2').val(), $('#w').val(), $('#h').val(), tmp_desc, $('#annotation-type option:selected').val(), $('#cmfuri').val(), encodeURIComponent($('#fragmenturi').val()), encodeURIComponent($('#creator').val()));
				  // switch between update and new entry
				  if ($.isNumeric(parseInt(updateEntry), 10)) {
					// update existing element
					for (i = 0; i < anArr.length; i++) {
						if (i == updateEntry) {
							anArr[i][0] = tmpArr[0];
							anArr[i][1] = tmpArr[1];
							anArr[i][2] = tmpArr[2];
							anArr[i][3] = tmpArr[3];
							anArr[i][4] = tmpArr[4];
							anArr[i][5] = tmpArr[5];
							anArr[i][6] = tmpArr[6];
							anArr[i][7] = tmpArr[7];
							anArr[i][8] = tmpArr[8];
							anArr[i][9] = tmpArr[9];
							anArr[i][10] = tmpArr[10];
							anArr[i][11] = tmpArr[11];
							anArr[i][12] = tmpArr[12];
							anArr[i][13] = tmpArr[13];
							anArr[i][14] = tmpArr[14];
						}
					}
				  }
				  else {
					// add new elemenet
					anArr.push(tmpArr);
				  }
				  anArr.sort(methods._sortmyway);
				  var newjsonstr = '';
				  newjsonstr = '{"video": [{"uri": "' + $.cookie('video_uri') + '", "width": "' + vid.videoWidth + '", "height": "' + vid.videoHeight + '", "player_width": "' +  myPlayer.width() + '", "player_height": "' +  myPlayer.height() + '"}], "annotations":[';
				  for (i = 0; i < anArr.length; i++) {
					if (anArr[i][0]) {
					  newjsonstr = newjsonstr + '{"starttime" : "' + anArr[i][0] + '", "endtime" : "' + anArr[i][1] + '", "uri" : "' + anArr[i][2] + '", "label" : "' + anArr[i][3] + '", "x1" : "' + anArr[i][4] + '", "x2" : "' + anArr[i][5] + '", "y1" : "' + anArr[i][6] + '", "y2" : "' + anArr[i][7] + '", "width" : "' + anArr[i][8] + '", "height" : "' + anArr[i][9] + '", "description" : "' + anArr[i][10] + '", "type" : "' + anArr[i][11] + '", "cmfuri" : "' + anArr[i][12] + '", "fragmenturi" : "' + anArr[i][13] + '", "creator" : "' + anArr[i][14] + '"}';
					}
					if (i < (anArr.length - 1)) {
					  newjsonstr = newjsonstr + ', ';
					}
				  }
				  newjsonstr = newjsonstr + ']}';
				  
				  var file_msg	= methods._fileOps('write', $.cookie('annotationlist'), newjsonstr);
				  
				  // update user annotations
				  userAnnotations = newjsonstr;
				}
				
				/* Create new annotation list */
				else {
				  var jsonstr = '';
				  if ($('#search-result-description')) {
					var tmp_str = $('#search-result-description').html().replace(/"/g, "'");
					tmp_str = tmp_str.split('&');
					var tmp_desc = '';
					for (w = 0; w < tmp_str.length; w++) {
						tmp_desc = tmp_desc + tmp_str[w];
						if (w < (tmp_str.length - 1)) {
							tmp_desc = tmp_desc + '$$';
						}
					 }
				  }
				  jsonstr = '{"video": [{"uri": "' + $.cookie('video_uri') + '", "width": "' + vid.videoWidth + '", "height": "' + vid.videoHeight + '", "player_width": "' +  myPlayer.width() + '", "player_height": "' +  myPlayer.height() + '"}], "annotations" : [{"starttime" : "' + $('#st').val() + '", "endtime" : "' + $('#et').val() + '", "uri" : "' + $('#search-results #selected ul li.value').text() + '", "label" : "' + $('#search-result-label').text() + '", "x1" : "' + $('#x1').val() + '", "x2" : "' + $('#x2').val() + '", "y1" : "' + $('#y1').val() + '", "y2" : "' + $('#y2').val() + '", "width" : "' + $('#w').val() + '", "height" : "' + $('#h').val() + '", "description" : "' + tmp_desc + '", "type" : "' + $('#annotation-type option:selected').val() + '", "cmfuri" : "", "fragmenturi" : "", "creator" : ""}]}' ;
				  
				  // create new annotation file
				  var ts 		= Math.round((new Date()).getTime() / 1000);
				  // add random number to file name to make it unique
				  var rand		= Math.floor(Math.random() * 7);
				  // generate file name
				  var new_file 	= 'has_' + ts + '_' + rand + '.ann';
				  var file_msg	= methods._fileOps('write', new_file, jsonstr) 
				  // save file name in cookie
				  $.cookie('annotationlist', new_file, { expires: 7 });
				  // store new json string in user annotations
				  userAnnotations = jsonstr;
				}
				
				// content fields have been added to the JSON cookie => clear values
				$('#x1').val('');
				$('#y1').val('');
				$('#x2').val('');
				$('#y2').val('');
				$('#w').val('');
				$('#h').val('');
				$('#st').val('');
				$('#et').val('');
				$('#cmfuri').val('');
				$('#fragmenturi').val('');
				$('#annotation-type').val('explicitlyMentions');
				$('.control-spatial .set-content').empty();
				$('.control-spatial .set-content').append('<img src="images/icon-no.gif" alt="Region not set" title="Region not set" />');
				$('.control-time-start .time-content').empty();
				$('.control-time-start .time-content').append('00:00:00');
				$('.control-time-end .time-content').empty();
				$('.control-time-end .time-content').append('00:00:00');
				
				// if entry got edited => update visible area and text content
				if ($.isNumeric(parseInt(updateEntry), 10)) {
					methods.hideAnnotation(updateEntry);
					methods.showAnnotation(updateEntry);
				}
				// set update mode to false
				updateEntry = '';
		 
				// remove old annotation list from list box and add new one
				$('#annotation-list-content').empty();
				// add cookie annotation values to the preview box
				if (typeof newjsonstr !== "undefined" && newjsonstr != '') {
				  delete jsonstr;
				  var jsonstr = newjsonstr;
				}
				var obj = jQuery.parseJSON(jsonstr);
				var list = new Array();
				$.each(obj.annotations, function (i, annotation) {
				  var tmp_data = new Array(i, annotation.starttime, annotation.endtime, annotation.label, annotation.uri);
				  list.push(tmp_data);
				});
				list.sort(methods._sortmyway);
				var annotation_table = '';
				annotation_table = '<table border=""><tr class="entry headline"><td class="id">&nbsp;</td><td class="time"><table border=""><tr><td class="start">From</td><td class="end">To</td></tr></table></td><td class="label">Label</td><td class="uri">URI</td><td class="additional">&nbsp;</td></tr>';
				$.each(list, function (i, annotation) {
				  annotation_table = annotation_table + '<tr class="entry"><td class="id">' + annotation[0] + '</td><td class="time"><table border=""><tr><td class="start">' + annotation[1] + '</td><td class="end">' + annotation[2] + '</td></tr></table></td><td class="label">' + annotation[3] + '</td><td class="uri">' + annotation[4] + '</td><td class="additional"><a name="entry-edit" class="button-edit" title="Edit"><img src="images/icon-edit.png" width="24" height="24" alt="Edit" class="button-icon" /></a> <a name="entry-delete" class="button-delete" title="Delete"><img src="images/icon-delete.png" width="24" height="24" alt="Delete" class="button-icon" /></a></td></tr>';
				  
				  newjsonstr = newjsonstr + ']}';
				  
				});
				annotation_table = annotation_table + '</table>';
				$('#annotation-list-content').append(annotation_table);
				// bind edit function to button
				$('.button-edit').bind('click', function() {
					methods._editEntry($(this));
				});
				// bind play function to button
				$('.entry .time').bind('click', function() {
					methods.playVideoAt($(this));
				});
				// bind delete function to button
				$('.button-delete').bind('click', function() {
					methods._deleteEntry($(this));
				});
				
				// clear search results, annotation title and description
				$('#search-result-description').empty();
				$('#search-result-label').empty();
				$('#annotation-search-box').empty();
				
				// hide area selector
				areaSelector.hideAll();
				areaSelector.update();
				
			}
		},
		/**
		 *	convertTimeToSeconds
		 *	Converts a time string into seconds
		 *
		 *	@param:	string timestring - time string
		 *	@return: int seconds - calculated number of seconds
		 */	
		_convertTimeToSeconds : function (time) {
			if (time.length == 5) {
				// mm:ss
				time = (60 * parseInt(time.substring(0, 2), 10)) + parseInt(time.substring(3, 5), 10);
			}
			else if (time.length == 8) {
				// hh:mm:ss
				time = (60 * 60 * parseInt(time.substring(0, 2), 10)) + (60 * parseInt(time.substring(3, 5), 10)) + parseInt(time.substring(6, 8), 10);
			}
			if (typeof(time) == 'string') {
				// error => time not converted into seconds
			}
			else {
				return time;
			}
		},
		/**
		 *	convertSecondsToTime
		 *	Converts seconds into time (e.g. 00:00:00)
		 *
		 *	@param:	int seconds - number of seconds
		 *	@return: string timestring - generated time string
		 */
		_convertSecondsToTime : function (seconds) {
			// remove possible decimal places
			var timestring = '';
			seconds = parseInt(seconds);
			if (seconds >= (60 * 60)) {
				// duration >= 1 hour
				var hours = parseInt(seconds/(60 * 60));
				var minutes = parseInt((seconds - (hours * 60 * 60))/60);
				timestring = hours + ':' + minutes + ':' + (seconds - parseInt(minutes * 60 - hours * 60 * 60));
			}
			else if (seconds > 59) {
				// duration >= 1 minute
				var minutes = parseInt(seconds/60);
				if (minutes < 10) {
					minutes = '0' + minutes;
				}
				seconds = seconds - parseInt(minutes * 60);
				if (seconds < 10) {
					seconds = '0' + seconds;
				}
				timestring = minutes + ':' + seconds;
			}
			else {
				// duration <= 1 minute
				if (seconds < 10) {
					seconds = '0' + seconds;
				}
				timestring = '00:' + seconds;
			}
			return timestring;
		},
		_sortmyway : function (data_A, data_B) {
		  if ( data_A[0] < data_B[0] )
			return -1;
		  if ( data_A[0] > data_B[0] )
			return 1;
		  return 0;
		},
		/**
		 *	startSearch
		 *	Allows searching via Ajax at different sources (e.g. dbpedia)
		 *
		 *	@param:	int mode - defines the search source (e.g. 0 = dbpedia)
		 */ 
		startSearch : function (parameters) {
			
			if ((typeof parameters === "undefined") || (parameters == '') || (isNaN(parameters))) {
				source = 0;
			}
			else if (typeof parameters === "object") {
				source = parameters.search_source;
			}
			else {
				source = 0;
			}
		
			var queryString 	= $('#search-string').val();
		  	if (queryString == '') {
				return;
		  	}
			
			// clear possible search results
		  	$('#annotation-search-box').empty();
		  	$('#annotation-search-box').attr('styles', '');
		  	// show loading animation
			$('#annotation-search-box').append('<img src="images/ajax-loader.gif" alt="loading..." width="32" height="32" id="ajax-loader" />');
		  
		  	var http_method = 'GET';
			var query = 'functions/search.php?source=' + source + '&querystr=' + queryString;
			if (window.XMLHttpRequest) {
				// code for IE7+, Firefox, Chrome, Opera, Safari
				xmlhttp=new XMLHttpRequest();
			 }
			 else {
				// code for IE6, IE5
				xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
			 }
					  
			 xmlhttp.onreadystatechange=function() {
				if (xmlhttp.readyState==4 && xmlhttp.status==200) {
					$('#annotation-search-box').empty();
					$('#annotation-search-box').append(xmlhttp.responseText);
		  
					$('.scroll-pane').jScrollPane();
					$('#annotation-search-box').bind('click', function() {		  
						// show search results
						methods.showResult();
					});
				}
			}
					  
			xmlhttp.open(http_method, query, true);
			xmlhttp.send();
		},
		/**
		 *	showResult
		 *	Shows a selected search result at the explore area
		 */ 
		showResult : function() {
			$('#search-results li.result').click(function() {
				var clicked = $(this);
				var value	= $('ul.content li.value', clicked).text();
				var obj = jQuery.parseJSON($('#search-result-object').text());
				var reArr	= new Array();
				var desc = '';
				var label = '';
				
				$.each(obj.results,function (i, result) {
				  if (result.description) {
					var tmp_str = (result.description).split('$$');
					var tmp_desc = '';
					for (w = 0; w < tmp_str.length; w++) {
						tmp_desc = tmp_desc + tmp_str[w];
						if (w < (tmp_str.length - 1)) {
							tmp_desc = tmp_desc + '&';
						}
					 }
					 result.description = tmp_desc;
				  }
				  var tmpArr = new Array(result.label, result.uri, (result.description));
				  reArr.push(tmpArr);
				});
				
				for (i = 0; i < reArr.length; i++) {
					if (reArr[i][1] == value) {
						desc	= reArr[i][2];
						label	= reArr[i][0];
					}
				}
				$('#search-result-description').html(desc);
				$('#search-result-label').text(label);
				// remove old selection (if set)
				$('#search-results li#selected').attr('id', '');
				// set new selected id
				clicked.attr('id', 'selected');
			})
		},
		/**
		 *	editEntry
		 *	Allows editing a selected entry of the annotation list
		 *
		 *	@params: element object	- selected element
		 */ 
		_editEntry : function(element) {		
			// unbind play function while editing
			$('.entry .time').unbind('click');
			// unbind delete function while editing
			$('.button-delete').unbind('click');
			// get parent .entry
			var entry = $(element).parent().parent().get(0);
			var entry_id = $('.id', entry).text();
			// get entry data from annotation cookie
			var jsonstr = userAnnotations;
			var obj = jQuery.parseJSON(jsonstr);
			// is any other annotation being edited?
			$('.entry').each(function (curr_entry) {
				
				// hide all annotation layers
				methods.hideAnnotation($('.id', this).text());
				
				if (($('.time .start', this).children().length > 0) && ($('.id', this).text() != entry_id)) {
					// close other time selectors
					// remove input fields
					var curr_start = $('.time .start', this).empty();
					var curr_end = $('.time .end', this).empty();
					var curr_id = $('.id', this).text();
					var curr_label = $('.label', this).empty();
					var curr_uri = $('.uri', this).empty();
					var curr_element = $(this);
					// add stored time, uri and label to annotation entry
					$.each(obj.annotations, function (i, annotation) {
						if ((i == curr_id)) {
							$('.label', curr_element).append(annotation.label);
							$('.uri', curr_element).append(annotation.uri);
							$('.time .start', curr_element).append(annotation.starttime);
							$('.time .end', curr_element).append(annotation.endtime);
						}
					});
				}
			});
			$.each(obj.annotations, function (i, annotation) {
				if ((i == entry_id)) {
					if ((annotation.cmfuri == undefined) || (annotation.cmfuri == NaN)) {
						annotation.cmfuri = '';
					}
					if ((annotation.fragmenturi == undefined) || (annotation.fragmenturi == NaN)) {
						annotation.fragmenturi = '';
					}
					if ((annotation.creator == undefined) || (annotation.creator == NaN)) {
						annotation.creator = '';
					}
					var x1 = '', x2 = '', y1 = '', y2 = '', w = '', h = '';
					if (methods.is_numeric(annotation.x1) == true) {
						x1 = parseInt(annotation.x1);
						console.log('used');
					}
					if (methods.is_numeric(annotation.y1) == true) {
						y1 = parseInt(annotation.y1);
					}
					if (methods.is_numeric(annotation.x2) == true) {
						x2 = parseInt(annotation.x2);
					}
					if (methods.is_numeric(annotation.y2) == true) {
						y2 = parseInt(annotation.y2);
					}
					if (methods.is_numeric(annotation.width) == true) {
						w = parseInt(annotation.width);
					}
					if (methods.is_numeric(annotation.height) == true) {
						h = parseInt(annotation.height);
					}
					console.log(x1, x2, y1, y2, w, h);
					entryData = new Array(i, annotation.label, annotation.description, annotation.uri, annotation.starttime, annotation.endtime, x1, y1, x2, y2, w, h, annotation.type, annotation.cmfuri, annotation.fragmenturi, annotation.creator);
				}
			});
			if ((entryData != 'undefined') && (entryData.length > 0)) {
				// jump to entry start time
				methods.gotoTime(methods._convertTimeToSeconds(entryData[4]), myPlayer);
				// show area and enable editing
				areaSelector.setSelection(entryData[6], entryData[7], entryData[8], entryData[9], true);
				areaSelector.setOptions({ show: true });
				areaSelector.update();
				// add input fields for start and end time
				// remove time string
				$('.time .start', entry).empty();
				$('.time .end', entry).empty();
				// add time inputs
				$('.time .start', entry).append('<input type="text" class="timeselector" name="start_entry_' + entry_id + '" id="start_entry_' + entry_id + '" value="' + entryData[4] + '" />');
				$('.time .end', entry).append('<input type="text" class="timeselector name="end_entry_' + entry_id + '" id="end_entry_' + entry_id + '" value="' + entryData[5] + '" />');
				$('#start_entry_' + entry_id).bind('change', function() {
					methods.gotoTime(methods._convertTimeToSeconds($(this).val()), myPlayer, 'st');
				});
				$('#end_entry_' + entry_id).bind('change', function() {
					methods.gotoTime(methods._convertTimeToSeconds($(this).val()), myPlayer, 'et');
				});
				// add values of current annotation to helper area
				$('#x1').val(entryData[6]);
				$('#y1').val(entryData[7]);
				$('#x2').val(entryData[8]);
				$('#y2').val(entryData[9]);
				$('#w').val(entryData[10]);
				$('#h').val(entryData[11]);
				$('#st').val(entryData[4]);
				$('#et').val(entryData[5]);
				$('#annotation-type').val(entryData[12]);
				$('#cmfuri').val(entryData[13]);
				$('#fragmenturi').val(entryData[14]);
				$('#creator').val(entryData[15]);
				console.log('cmfuri set: ' + $('#cmfuri').val());
				if ($('#search-results').length != 0) {
					$('#annotation-search-box').empty();
				}
				$('#annotation-search-box').append('<ul id="search-results" name="search-results" size="11"><li class="result" id="selected"><ul class="content"><li class="value">' + entryData[3] + '</li></ul></li></ul>');
				// show annotation
				methods.showAnnotation(entryData[0]);
				// set update mode true
				updateEntry = entryData[0];
				// get time format from entry
				var tForm = '';
				var maxHour = 0;
				var maxMin = 0;
				var maxSec = 0;
				// select maximum for hours, minutes and seconds depending on video duration
				if (methods._convertSecondsToTime(Math.round(myPlayer.duration())).length == 8) {
					tForm = 'hh:mm:ss';
					maxHour = methods._convertSecondsToTime(Math.round(myPlayer.duration(), 10)).substring(0, 2);
					maxMin = '59';
					maxSec = '59';
				}
				else if (methods._convertSecondsToTime(Math.round(myPlayer.duration(), 10)).length == 5) {
					tForm = 'mm:ss';
					maxMin = methods._convertSecondsToTime(Math.round(myPlayer.duration(), 10)).substring(0, 2);
					if (maxMin > 0) {
						maxSec = '59';
					}
					else {
						maxSec = maxSec = methods._convertSecondsToTime(Math.round(myPlayer.duration(), 10)).substring(3, 5);
					}
				}
				else {
					tForm = 'ss';
					maxSec = methods._convertSecondsToTime(Math.round(myPlayer.duration(), 10)).substring(3, 5);
				}
				
				// add time pickers to input fields
				$('#start_entry_' + entry_id).timepicker({
					showSecond: true,
					timeFormat: tForm,
					stepHour: 1,
					stepMinute: 1,
					stepSecond: 1,
					hourMax: maxHour,    
					minuteMax: maxMin,
					secondMax: maxSec
				});
				$('#end_entry_' + entry_id).timepicker({
					showSecond: true,
					timeFormat: tForm,
					stepHour: 1,
					stepMinute: 1,
					stepSecond: 1,
					hourMax: maxHour,
					minuteMax: maxMin,
					secondMax: maxSec
				});
			}
			else {
				// entry data could not get found => trow error message
				alert('ERROR: Entry data not found!');
			}
		},
		/**
		 *	playVideoAt
		 *	Enables playing a video starting at a selected time stamp
		 *
		 *	@params: element object	- selected element
		 */ 
		playVideoAt : function(element) {
			// get start time
			var starttime = $('.start', element).text();
			var entry = $(element).parent().parent().get(0);
			var annotation_id = parseInt($('.id', entry).text());
			// is current entry getting edited?
			if ($('.start', element).children().is('input') != true) {
				// get start time format (mm:ss or hh:mm:ss)
				if (starttime.length == 5) {
					// mm:ss
					starttime = (60 * parseInt(starttime.substring(0, 2), 10)) + parseInt(starttime.substring(3, 5), 10);
				}
				else if (starttime.length == 8) {
					// hh:mm:ss
					starttime = (60 * 60 * parseInt(starttime.substring(0, 2), 10)) + (60 * parseInt(starttime.substring(3, 5), 10)) + parseInt(starttime.substring(6, 8), 10);
				}
				// start player at given start time
				methods.startPlaybackAt(starttime, myPlayer);
				methods.showAnnotation(annotation_id);
			}
		},
		/**
		 *	deleteEntry
		 *	Allows deleting an entry of the annotation list
		 *
		 *	@params: element object	- selected element
		 */ 
		_deleteEntry : function(element) {
			// video dimensions
			var vid = document.getElementById("loaded_video");
			// get parent .entry
			var entry = $(element).parent().parent().get(0);
			var arraykey = $('.id', entry).text();
			if ($.cookie('annotationlist')) {
				var jsonstr = userAnnotations;
			  	var anArr	= new Array();
			  	var obj = jQuery.parseJSON(jsonstr);
			  	newjsonstr = '{"video": [{"uri": "' + $.cookie('video_uri') + '", "width": "' + vid.videoWidth + '", "height": "' + vid.videoHeight + '", "player_width": "' +  myPlayer.width() + '", "player_height": "' +  myPlayer.height() + '"}], "annotations":[  ';
			  	var annotation_table = '';
			  	annotation_table = '<table border=""><tr class="entry headline"><td class="id">&nbsp;</td><td class="time"><table border=""><tr><td class="start">From</td><td class="end">To</td></tr></table></td><td class="label">Label</td><td class="uri">URI</td><td class="additional">&nbsp;</td></tr>';
			  	$.each(obj.annotations, function (i, annotation) {
					if ((i == arraykey) && (annotation.label == $('.label', entry).text())) {
						// entry will get deleted
						// alert(annotation.label);
				  	}
				  	else {
					  if (annotation.description) {
						var tmp_str = (annotation.description).replace(/"/g, "'");
						tmp_str = tmp_str.split('&');
						var tmp_desc = '';
						for (w = 0; w < tmp_str.length; w++) {
							tmp_desc = tmp_desc + tmp_str[w];
							if (w < (tmp_str.length - 1)) {
								tmp_desc = tmp_desc + '$$';
							}
						 }
						 annotation.description = tmp_desc;
					  }
						newjsonstr = newjsonstr + '{"starttime" : "' + annotation.starttime + '", "endtime" : "' + annotation.endtime + '", "uri" : "' + annotation.uri + '", "label" : "' + annotation.label + '", "x1" : "' + annotation.x1 + '", "x2" : "' + annotation.x2 + '", "y1" : "' + annotation.y1 + '", "y2" : "' + annotation.y2 + '", "width" : "' + annotation.width + '", "height" : "' + annotation.height + '", "description" : "' + (annotation.description) + '", "type" : "' + annotation.type + '", "cmfuri" : "' + encodeURIComponent(annotation.cmfuri) + '", "fragmenturi" : "' + encodeURIComponent(annotation.fragmenturi) + '", "creator" : "' + encodeURIComponent(annotation.creator) + '"}, ';
					  
					 annotation_table = annotation_table + '<tr class="entry"><td class="id">' + i + '</td><td class="time"><table border=""><tr><td class="start">' + annotation.starttime + '</td><td class="end">' + annotation.endtime + '</td></tr></table></td><td class="label">' + annotation.label + '</td><td class="uri">' + annotation.uri + '</td><td class="additional"><a name="entry-edit" class="button-edit" title="Edit"><img src="images/icon-edit.png" width="24" height="24" alt="Edit" class="button-icon" /></a> <a name="entry-delete" class="button-delete" title="Delete"><img src="images/icon-delete.png" width="24" height="24" alt="Delete" class="button-icon" /></a></td></tr>';
					  
					}
				});
			  
				newjsonstr = newjsonstr.substr(0, (newjsonstr.length - 2));
				newjsonstr = newjsonstr + ']}';
				  
				annotation_table = annotation_table + '</table>';
				// open file and add updated json
				var file_msg	= methods._fileOps('write', $.cookie('annotationlist'), newjsonstr);
				// update user annotations
				userAnnotations = newjsonstr;
				// show updated annotation list
				$('#annotation-list-content').empty();
				$('#annotation-list-content').append(annotation_table);
				// bind edit function to button
				$('.button-edit').bind('click', function() {
					methods._editEntry($(this));
				});
				// bind play function to button
				$('.entry .time').bind('click', function() {
					methods.playVideoAt($(this));
				});
				// bind delete function to button
				$('.button-delete').bind('click', function() {
					methods._deleteEntry($(this));
				});
			}
		},		
		/**
		 *	loadVideoFromURL
		 *	Loads a video for the player by setting a video uri cookie
		 *
		 *	@params: video_uri string	- URI of a valid HTML5 video
		 */ 
		loadVideoFromURL : function(video_uri) {
			// write video uri to cookie
			// delete cookies if it is set
			$.cookie('video_uri', null, { expires: -1, path: '/'});
			$.cookie('annotationlist', null, { expires: -1, path: '/'});
			$.cookie('annotations_loaded', null, { expires: -1, path: '/'});
			// clear variables
			userAnnotations = '';
			updateEntry		= '';
			// set new video source
			$.cookie('video_uri', video_uri, { expires: 7, path: '/'});
					
			// reload page
			location.reload();
		},
		/**
		 *	startPlaybackAt
		 *	Jumps to a specified time stamp within a video and starts playing it
		 *
		 *	@params: seconds int - time stamp in seconds
		 *	@params: myPlayer object - instance of video player
		 */ 
		 startPlaybackAt : function(seconds, myPlayer) {
			 methods.gotoTime(seconds, myPlayer);
			 myPlayer.play();
		},
		/**
		 *	gotoTime
		 *	Jumps to a specified timestamp within a video and pauses the video player
		 *
		 *	@params: seconds int - time stamp in seconds
		 *	@params: myPlayer object - instance of video player
		 *	@params: selected_time string - id of time div (e.g. start time, end time)
		 */ 
		gotoTime : function(seconds, myPlayer, selected_time) {
			// goto seleted timestamp
			 myPlayer.currentTime(seconds);
			 // update time field
			 $('#' + selected_time).val(methods._convertSecondsToTime(seconds));
			 myPlayer.pause();
		},
		/**
		 *	is_numeric
		 *	Ckecks if the entered value is a number or not
		 *
		 *	@params: fData string - numeric or non numeric string
		 */
		is_numeric : function(fData) {
				var reg = new RegExp("^[0-9]+$");
				return (reg.test(fData));
		}
	};
	
	
	/**
	 *	$.fn.hasSuite
	 *	Main function of the hypervideo annotation suite which handles the method selection
	 */ 
	$.fn.hasSuite = function(method) {
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		}
		else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		}
		else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.hasSuite' );
		}    
	};
})( jQuery );