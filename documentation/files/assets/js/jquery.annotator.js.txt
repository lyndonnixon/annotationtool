/**
 * ConnectME annotation tool - jQuery extension
 *
 * This jQuery extension provides all functions which are required by the annotation tool. Those are:
 *
 ** Load a annotation file and extract the containing annotations
 ** Store the annotations in a google data table
 ** Render the annotations in the annotator timeline
 ** Initialize all included search plugins
 ** Load the PHP settings from the server
 ** Save annotations to the annotation file on the server
 ** Create/Update/Delete annotations in the CMF
 ** Initiate the video player which gets used for annotating
 ** Create spatial and/or temporal annotations and link them to linked data sources or static html sources
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.4
 * @package Core
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */

(function($) {
	
	var userAnnotations;
	var updateEntry;
	var timeline;
  var data;
	var settings;
	var google;
	var myPlayer;
	var videodata = {};
	var timelinedata = {};
	var loadedRows = [];
	var language;
	var lang_file;
	var search_results;
	var areaSelector;
	var elementChanged = false;
		
	var methods = {
	 
	 /**
    * Init
    *
    * Initiates the jQuery plugin. Loads settings from PHP and includes specified (external) settings in the configuration.
    * Loads the video locators in the video js player and parses the selected language file. Furthermore, the search plugins are initiated.
    *
    * @param object {options} External options (set when initiating the extension in the HTML file)
    *
    */
		
		init: function(options) {
			
			// get settings from annotator
			$.getJSON('core/ajax/get-settings.php', function(response) {
				console.log(response);
							
				var rplugins = [];
				$.each(response.settings.resourceplugins, function(key, val) {
					rplugins.push(val.name);
				})
				rplugins.push('pre_annotations');
				var pluginvalues = [];
				$.each(response.settings.resourceplugins, function(key, val) {
					$.each(val.parameters, function(key2, val2) {
						pluginvalues.push(val2);
					})
				})
				var ann_prefix = {};
				$.each(response.settings.ann_prefix, function(key, val) {
					ann_prefix[key] = val.replace('<','').replace('>','');
				})
				
				settings = $.extend({
					containers: {
						main: 'main-content',
						display: 'block',
						player: 'video-container', 
						annotations: 'timeline-container',
						video: 'loaded-video',
						timeline: 'annotator-timeline'
					},
					popup: {
						info: {
							height: '250px',
							width: '80%'
						},
						input: {
							height: '600px',
							width: '80%'
						}
					},
					autocomplete: false,
					video_width: 100,
					annotationtype: { 'bookmark': 'http://connectme.at/ontology#hasContent'
					},
					_default: {
						annotation_duration: 10,
						autosave_delay: 30000
					},
					resourceplugins: rplugins,
					resourcevalues: pluginvalues,
					annotation_prefixes: ann_prefix
				}, options);
				
				console.log(settings);
				
				// search for required containers
				if (($('#' + settings.containers.main).length == 0) || ($('#' + settings.containers.player).length == 0) || ($('#' + settings.containers.annotations).length == 0)) {
					if ($('#' + settings.containers.main).length == 0) {
						$.error('Required container [' + settings.containers.main + '] does not exist');
					}
					if ($('#' + settings.containers.player).length == 0) {
						$.error('Required container [' + settings.containers.player + '] does not exist');
					}
					if ($('#' + settings.containers.annotations).length == 0) {
						$.error('Required container [' + settings.containers.annotations + '] does not exist');
					}
				}
				else {
					
					// hide messages
					$('#' + settings.containers.annotations + ' p').hide();
					
					// load language file
					if ($.cookie('lang') != undefined) {
						lang_file = 'lang.' + $.cookie('lang') + '.json';
					}
					else {
						lang_file = 'lang.en.json';
					}
					$.ajax({
						url: 'core/lang/' + lang_file,
						type: 'POST',
						data: '',
						async: false,
						cache: false,
						timeout: 3000,
						error: function(){
							return true;
						}
					})
					.done(function (response) {
						language = jQuery.parseJSON(response);
						// enable loading overlay
						$('body').mask(language.strings.ANNOTATOR_LOADING);
					})
					
					// append required containers to areas
					$('#' + settings.containers.player).append('<video style="max-width: 100%; height: auto;" id="' + settings.containers.video + '" class="video-js vjs-default-skin" controls="controls" preload="auto"></video>');
					$('#' + settings.containers.annotations).append('<div id="' + settings.containers.timeline + '"></div>');
				
					// load video
					// init video js 4.0
					myPlayer = videojs(settings.containers.video,	{
						"controlsBelow" : true, // Display control bar below video vs. in front of
						"controlsAtStart" : true, // Make controls visible when page loads
						"controlsHiding" : false
					});
					
					// TO DELETE !!!
					// $.cookie('annotationlist', 'backup_1361442165153.ann', '/')
					// TO DELETE !!!
					
					// load video sources
					myPlayer.src(settings.video.source);				
						
					var heigthChanged = function(){
						// does annotation file exist
						if ($.cookie('annotationlist')) {
							
							// add filename to settings
							settings = $.extend({
								annotation: {
									filename : $.cookie('annotationlist')
								}
							}, settings);
							
							// do annotations need to get updated?
							// compare existing values with cmf entries
							// TO-DO: ADD COMPARATION OF CMF AND LOAD RESULT VALUES AND FIND OUT IF DATA NEEDS TO GET RELOADED!
							
							
							// try to load annotations
							methods._fileOps('read');
					
						}
						else {
							methods.resizeVideoJS(true);
						} // .end annotation cookie exits?
						// add event listener for video timeupdate
						myPlayer.on("timeupdate", methods._timeupdateVideoJS);
						
						// add event listener for video start
						myPlayer.on("play", methods._playVideoJS);
						
						myPlayer.off("loadeddata", heigthChanged);
					}
					
					myPlayer.on("loadeddata", heigthChanged);
					
					window.onresize = methods.resizeVideoJS; // Call the function on resize
					
					// enable area selection
					areaSelector = $('#' + settings.containers.video + '_html5_api').imgAreaSelect({
						handles: true,
						fadeSpeed: 200,
						instance: true,
						onSelectChange: function (img, selection) {
							if (!selection.width || !selection.height) {
								return;
							}
							
							var selArea = {};
								selArea = {	'x1': selection.x1,
														'y1': selection.y1,
														'x2': selection.x2,
														'y2': selection.y2,
														'w': selection.width,
														'h': selection.height
													}
									
							var selAreaPercent = methods._selToPercent(selArea);
							methods._spatialRegionSet(selAreaPercent);
						}
					});
					
					areaSelector.cancelSelection();
					areaSelector.update();
					areaSelector.setOptions({ show: false, hide: true });
					areaSelector.update();
					areaSelector.setOptions({ enable: false });
					areaSelector.update();
					
					if ((settings.resourceplugins != undefined) && ((settings.resourceplugins).length > 0)) {
					
						// create search plugin container
						var search_container = '<div style="display: none">' + 
																	 '  <div class="container-fluid" id="resource-search">' + 
																	 '	   <div class="row-fluid">' + 
																	 '		   <div class="span12">' + 
																	 '			   <div class="annotator-container" id="search-container">' + 
																	 '  				 <div class="headline">' + language.strings.SEARCH_BOX + '</div>' +
																	 '	  			 <div class="search-container" id="search-container">' + 
																	 '		  		 	 <section class="">' + 
																	 '			  	 	   <div id="search-source">' + 
																	 '				  		   <h2>1. ' + language.strings.ADD_STEP_1 + '</h2>' + 
																	 '		  		  	 </div>' + 
																	 '   		  		 	 <div id="search-browse">' + 
																	 '		    				 <h2>2. ' + language.strings.ADD_STEP_2 + '</h2>' + 
																	 '		    				 <select id="search-results" size="10"></select>' + 
																	 '		  	  		 </div>' +
																	 '		  		   </section>' + 
																	 '		    		 <aside>' + 
																	 '			    	 	 <div id="search-explore">' + 
																	 '				    		 <h2>3. ' + language.strings.ADD_STEP_3 + '</h2>' + 
																	 '		  			  	 <div id="search-preview"></div>' + 
																	 '			    		 </div>' + 
																	 '			    	 	 <div id="select-type">' + 
																	 '				    		 <h2>4. ' + language.strings.ADD_STEP_4 + '</h2>' + 
																	 '			    		 </div>' + 
																	 '			    	 	 <div id="button-area">' + 
																	 '								 <button type="button" class="btn btn-primary" id="annotation_cancel">' + 
																	 '			  		  	   &laquo; ' +  language.strings.ADD_CANCEL +
																	 '				  		   </button>' + 
																	 '				    		 <button type="button" class="btn btn-primary" id="continue">' + 
																	 '				  	  	   ' +  language.strings.ADD_STEP_5 + ' &raquo;' +
																	 '				  		   </button>' + 
																	 '			    	 	 </div>' + 
																	 '	  		  	 </aside>' + 
																	 '		  		 </div>' + 
																	 '			  	 <div class="footer"></div>' + 
																	 '			   </div>' +
																	 '		   </div>' + 
																	 '	   </div>' +
																	 '  </div>' +
																	 '</div>';
																	 
						$('#' + settings.containers.main).append(search_container);
						
						// create annotation type selector
						var ann_type_sel_html = '<select id="annotation-type">';
						$.each(language.annotation_type, function(key, value) {
							ann_type_sel_html += '<option value="' + settings.annotation_prefixes.cma + key + '">' + value + '</option>';
						})
						ann_type_sel_html += '</select>';
						// append annotation type selector
						$('#select-type').append(ann_type_sel_html);
						
						// initialize search plugins
						methods._initPlugins(0);
						
						// get pre-annotations if pre_annotation plugin is activated (get content in background while performing initialization of plugins)
						if (settings.resourceplugins) {
							if ($.inArray('pre_annotations', settings.resourceplugins)) {
								// pre annotation plugin has been activated
								// get parameters to search for
								var parameters = null;
								var curr = 0;
								$.each(settings.resourcevalues, function(key, value) {
									if (value != 'null') {
										parameters += value;
										if (curr < (settings.resourcevalues).length) {
											parameters += ',';
										}
									}
								})
								console.log((settings.resourcevalues).toString());
								$.ajax({
									url: 'plugins/pre_annotations/pre_annotations.data-lookup.php',
									type: 'POST',
									data: {'parameters': (settings.resourcevalues).toString(), 'uri': settings.video.id},
									async: true,
									timeout: 15000,
									error: function(jqXHR, textStatus, errorThrown) {
										console.log('Error ' + textStatus + '; ' + errorThrown);
										console.log(jqXHR);
										return true;
									}
								})
								.done(function(response) {
									console.log(response);
									console.log('file created');
								})
							}
						}
						
					}
					
					// append resource search popup
					$('.timeline-event-edit').colorbox({inline:true, width: '80%', height: '600px', href: "#resource-search"});
					
					// append continue close method
					$('#continue').bind('click', function() {
						// check for required values
						var error = false;
						if (($('#search-results option:selected').val() == '') || ($('#search-results option:selected').val() == undefined)) {
							alert(language.strings.ANNOTATION_DATA_ERROR_1);
							error = true;
						}
						// set edit inidicator to true => element has been edited properly
						if (error != true) {
							elementChanged = true;
							$('.inline').colorbox.close();
						}
					});
					
					// append cancel close method
					$('#annotation_cancel').bind('click', function() {
						// check for required values
						console.log('cancel');
						elementChanged = false;
						console.log(elementChanged);
						$('.inline').colorbox.close();
						
						// remove newly created element from timeline
						var row = undefined;
						var sel = timeline.getSelection();
						if (sel.length) {
							if (sel[0].row != undefined) {
								var row = sel[0].row;
							}
						}
						 
						if (row != undefined) {
							console.log('deleting ' + row);
							timeline.deleteItem(row);
						}
						
					});
					
					// looks at current selection and saves annotations to file if no event is active.
					$('#annotator-timeline').bind('click', function() {
						// get state of annotations (selected or not)
						var sel = timeline.getSelection();
						if (sel.length) {
							// item selected => do nothing
						}
						else {
							// no item selected
							// hide possible hints
							$('.hints').remove();
							// deactivate area selector
							areaSelector.cancelSelection();
							areaSelector.update();
							areaSelector.setOptions({ show: false, hide: true });
							areaSelector.update();
							areaSelector.setOptions({ enable: false });
							areaSelector.update();
							
							
							
							var currentPlaybackTime = parseInt(Math.round(myPlayer.currentTime()), 10);
							if ((typeof(currentPlaybackTime) != undefined) && (currentPlaybackTime != null) && (currentPlaybackTime != '') && (typeof(currentPlaybackTime) != NaN)) {
								// show spatial regions again
								methods._timeupdateVideoJS();
							}
							
							// save current annotations to file
							methods._fileOps('write');
						}
					});
					
					// bind save to cmf function
					$('#' + language.main_menu.SAVE.children.SAVETOCMF.attr.id).bind('click', function() {
						// check for annotations (at least one must exist to save data to the cmf)
						if ($.cookie('annotationlist') != '') {
							// annotation list cookie is set => search for data table
							if ((typeof(data) != "undefined")) {
								// data table is set => count annotations
								if (data.getNumberOfRows() > 0) {
									// data table exists and at least one annotation has been added
									// show message to user
									$('body').append('<div style="display: none" id="save-container"><div id="showmessage-save"><p>' + language.strings.ANNOTATION_SAVING + '<br /><img src="assets/img/ajax-loader-bar.gif" alt="Loading..." class="loading-indicator" /></p></div></div>');
									$.colorbox({inline:true,
											width: settings.popup.info.width,
											height: settings.popup.info.height,
											href: "#showmessage-save",
											onClosed: function(){ $('#save-container').remove(); }
									});
									// save current timeline data to file before submitting information to the cmf
									var json_string = '{' + methods._videoDataToJson() + ', ' + methods._dataToJson() + '}';
									console.log(data);
									var http_method = 'POST';
									var query = 'core/ajax/annotation-file-functions.php';
									var post_data = 'file=' + settings.annotation.filename + '&data=' + json_string + '&type=write';
									
									$.ajax({
										url: query,
										type: http_method,
										data: post_data,
										async: false,
										cache: false,
										timeout: 3000,
										error: function(){
											return true;
										}
									})
									.done(function (response) {
										userAnnotations = response;
										// show message if data has been saved
										$('#' + settings.containers.annotations + ' p').fadeIn('slow', function() {
											$('#' + settings.containers.annotations + ' p').fadeOut('slow')
										});
										currTime = new Date();
										console.info('Saved ' + currTime.toGMTString() + ' now transmitting data to CMF');
										
										
										// get submit type (create or update)
										if ($.cookie('annotationsloaded') == 'true') {
											var selected_type = 'update';
										}
										else {
											var selected_type = 'new';
										}
										// submit data to cmf
										$.ajax({
											url: 'core/ajax/save-to-cmf.php',
											type: 'GET',
											data: {'type': selected_type},
											async: false,
											timeout: 15000,
											error: function(jqXHR, textStatus, errorThrown) {
												console.log('Error ' + textStatus + '; ' + errorThrown);
												console.log(jqXHR);
												return true;
											}
										})
										.done(function (response) {
											console.log(response);
											if (typeof(response) == "object") {
												if (typeof(response.MESSAGE) != "undefined") {
													var message = response.MESSAGE;
												}
													else if (typeof(response.RESPONSES[0]) == "object") {
													var message = response.RESPONSES[0].MESSAGE;
												}
												else {
													var message = language.strings.ANNOTATIONS_SAVE_CMFERROR;
												}
											}
												else {
												var message = language.strings.ANNOTATIONS_SAVE_CMFERROR;
											}
											$('#showmessage-save').empty();
											$('#showmessage-save').append('<p>' + message + '</p><p><button type="button" class="btn btn-primary" id="save-close">' +  language.strings.ANNOTATIONS_SAVE_CLOSE + ' &raquo;' + '</button></p>');
											$('#save-close').bind('click', function() {
												window.location.href = 'open?video_id=' + settings.video.id;
											});
										});
									})
									
								}
								else {
									// ERROR: data table is empty
									methods._showErrorMessage(language.strings.ANNOTATION_SAVE_ERROR_4, language.strings.ANNOTATION_SAVE_ERROR_3);
									console.error('No annotations set');
								}
							}
							else {
								// ERROR: data table does not exist
								methods._showErrorMessage(language.strings.ANNOTATION_SAVE_ERROR_4, language.strings.ANNOTATION_SAVE_ERROR_2);
								console.error('Annotation datatable not set');
							}
						}
						else {
							// ERROR: annotation list does not exist
							methods._showErrorMessage(language.strings.ANNOTATION_SAVE_ERROR_4, language.strings.ANNOTATION_SAVE_ERROR_1);
							console.error('Annotationlist not set');
						}
						
					});
					
				} // .end required containers set?
				
			}) // .end settings loaded
		
		},
		
		/**
     * Draw timeline
     *
     * Initiates the timline. Creates all required rows in the google table and loads existing annotations in the table. All found 
     * annotations get included in the timeline.
     *
     */
		_timelineDraw: function() {
    	
			// Create and populate a data table.
      data = new google.visualization.DataTable();
      data.addColumn('datetime', 'starttime');
      data.addColumn('datetime', 'endtime');
      data.addColumn('string', 	'label');
			data.addColumn('string', 	'resource');
			data.addColumn('string', 	'description');
			data.addColumn('string', 	'relation');
			data.addColumn('string', 	'fragment');
			data.addColumn('string', 	'annotation');
			data.addColumn('string', 	'creator');
			data.addColumn('number', 	'x1');
			data.addColumn('number', 	'y1');
			data.addColumn('number', 	'x2');
			data.addColumn('number', 	'y2');
			data.addColumn('number', 	'w');
			data.addColumn('number', 	'h');
			data.addColumn('string', 	'preferredlabel');
			data.addColumn('string', 	'annotationtype');
			data.addColumn('number', 	'active');
			
			// maps information
			data.addColumn('string', 	'country');
			data.addColumn('string', 	'street');
			data.addColumn('string', 	'city');
			data.addColumn('string', 	'zip');
			data.addColumn('string', 	'map');
			data.addColumn('string', 	'lng');
			data.addColumn('string', 	'lat');
			
			if (loadedRows.length > 0) {
				data.addRows(loadedRows);
				delete(loadedRows);
			}
			
			console.log(data);
			
			// specify options
      var options = {
      	'width':  "100%",
        'height': "auto",
        'editable': true, // make the events dragable
        'layout': "box",
				'scale': 'SECOND',
				'showCustomTime': true,
				'start': new Date(2012, 1, 1, 0, 0, 0),
        'min': new Date(2012, 1, 1, 0, 0, 0),                 // lower limit of visible range
        'max': methods._convertSecondsToDate(duration),               // upper limit of visible range
        'intervalMin': 1000 * 10,          // one day in milliseconds
        'intervalMax': 1000 * 60 * duration  // about three months in milliseconds
				
      };

      // Instantiate our timeline object.
      timeline = new links.Timeline(document.getElementById(settings.containers.timeline));

      // Make a callback function for the select event
      var onselect = function (event) {
				
				areaSelector.cancelSelection();
				areaSelector.update();
				
				// console.log(event);
				var row = undefined;
        var sel = timeline.getSelection();
        if (sel.length) {
        	if (sel[0].row != undefined) {
          	var row = sel[0].row;
          }
        }
				
				if (row != undefined) {
					
					console.log('Selected row: ' + row);
		
					// add edit button event
					$('.timeline-navigation-edit').unbind();
					$('.timeline-navigation-edit').bind('click', function(e) {
						
						e.preventDefault();
						
						// empty inputs
						$('#search-query').val('');
						$('#has-content').val('');
						$('#search-preview').empty();
						
						// switch between annotation and bookmark
						if ((data.getValue(parseInt($('.timeline-navigation-edit').text()), 16)).toLowerCase().indexOf("bookmark") >= 0) {
							$('#has-content').val(data.getValue(parseInt($('.timeline-navigation-edit').text()), 3));
							$('#has-content').keyup();
						}
						else {
							$('#search-query').val(data.getValue(parseInt($('.timeline-navigation-edit').text()), 2));
							$('#search-query').keyup();
						}
						
						if (((data.getValue(parseInt($('.timeline-navigation-edit').text()), 15)) != null) && (typeof(data.getValue(parseInt($('.timeline-navigation-edit').text()), 15)) != undefined) && ((data.getValue(parseInt($('.timeline-navigation-edit').text()), 15)) != '') ){
							// preferred Lable set
							$('#preferred-label').val('');
							$('#preferred-label').val(data.getValue(parseInt($('.timeline-navigation-edit').text()), 15));
						}
						
						// get values from data table => col 5 = annotation type
						$('#select-type option').each(function() {
							 $(this).removeAttr('selected');
							 if (data.getValue(parseInt($('.timeline-navigation-edit').text()), 5) == $(this).val()) {
								 $(this).attr('selected', 'selected');
							 }
						})
						
						$.colorbox({inline:true,
							width: settings.popup.input.width,
							height: settings.popup.input.height,
							href: "#resource-search",
							onClosed: function(){ methods._timelineItemEdit(parseInt($('.timeline-navigation-edit').text())); }
						});
										
					});
					
        	var content = data.getValue(row, 2);
					
					// enable spatial selector
					// timeline.setSelection([{row: parseInt($('.timeline-navigation-edit').text())}]);
					// hide all other shown spatial regions
					$('.spatial-annotation').remove();
					
					// load current spatial region into area selector
					// if spatial region has been set
					if (data.getValue(row, 9) && data.getValue(row, 10) && data.getValue(row, 13) && data.getValue(row, 14) && $.isNumeric(data.getValue(row, 9)) && $.isNumeric(data.getValue(row, 10)) && $.isNumeric(data.getValue(row, 13)) && $.isNumeric(data.getValue(row, 14))) {
						var regionData = methods._percentToPixel(data.getValue(row, 9), data.getValue(row, 10), data.getValue(row, 13), data.getValue(row, 14));
						if (regionData != null) {
							var x1 =  regionData.x1;
							var y1 =  regionData.y1;
							var x2 =  regionData.x2;
							var y2 =  regionData.y2;
							
							console.log(regionData);
							
							$('#x1').val(x1);
							$('#y1').val(y1);
							$('#x2').val(x2);
							$('#y2').val(y2);
							$('#w').val((x1 + x2));
							$('#h').val((y1 + y2));
							
							// console.log(x + ', ' +y + ', ' +w + ', ' +h)
							
							areaSelector.setOptions({ enable: true });
							areaSelector.update();
							areaSelector.setSelection(x1, y1, x2, y2, true);
							areaSelector.setOptions({ show: true, hide: false });
							areaSelector.update();
						}
						
						// set area select to edit mode
					}
					// or show region available indicator
					else {
						// show spatial region indicator
						areaSelector.setOptions({ enable: true });
						areaSelector.update();
						areaSelector.setOptions({ hide: false });
						areaSelector.update();
						
						if($('#spatial-region-hint').length == 0) {
							$('#' + settings.containers.video).append('<div id="spatial-region-hint" class="popover fade left in hints"><div class="arrow"></div><div class="popover-inner"><h3 class="popover-title">' + language.strings.ANNOTATION_SPATIAL_REGION + '<button type="button" class="close hint-close">×</button></h3><div class="popover-content">' + language.strings.ANNOTATION_SPATIAL_REGION_HINT + '</div></div></div>');
							$('#spatial-region-hint').css('left', 'auto');
							$('.hint-close').bind('click', function() {
								$('#spatial-region-hint').remove();
							});
						}
						
					}
					
					// enable spatial selection
					// areaSelector.setOptions({enable: true, hide: false});
				  // areaSelector.update();
				}
				
      }

      // callback function for the change event
      var onchange = function () {
      	var sel = timeline.getSelection();
        if (sel.length) {
        	if (sel[0].row != undefined) {
          	var row = sel[0].row;
            console.log("event " + row + " changed");
            // getRowData(row);
                        
            var from = data.getValue(row, 0);
        		var to = data.getValue(row, 1);
        		if ((to - from) < 5000) {
        			var enddate = new Date((data.getValue(row, 0)).getMilliseconds() + 5000);
        			setCell(row, 1, enddate);
        		}
						
          }
        }
      }

      // callback function for the delete event
      var ondelete = function () {
      	var sel = timeline.getSelection();
        if (sel.length) {
        	if (sel[0].row != undefined) {
          	var row = sel[0].row;
						timeline.cancelDelete();
						// hide element
						data.setValue(row, 17, 0);
						// force timeline redraw
						timeline.redraw();
            // document.getElementById("info").innerHTML += "event " + row + " deleted<br>";
						methods._timelineItemDelete(row);
          }
        }
      }

      // callback function for the add event
      var onadd = function () {
				
				// cancel possible spatial regions
				areaSelector.cancelSelection();
				areaSelector.update();
				
			  // clear previous search results
			  $('#search-query').val("");
				$('#has-content').val("");
				$('#search-results').empty();
				$('#search-preview').empty();
				
      	var count = data.getNumberOfRows();
				
				// open resource search popup
				$.colorbox({inline:true,
										width: settings.popup.input.width,
										height: settings.popup.input.height,
										href: "#resource-search",
										onClosed: function(){ methods._timelineItemCreate(true); }
									});
        // document.getElementById("info").innerHTML += "event " + (count-1) + " added<br>";
      }
            
      // callback function for the edit event
      var onedit = function () {
      	var count = data.getNumberOfRows();
      }
			
			// callback function for the edit event
      var onrangechange = function () {
				var currentRange = timeline.getVisibleChartRange();
				var start_sec = methods._convertDateToSeconds(new Date(currentRange.start));
				var end_sec = methods._convertDateToSeconds(new Date(currentRange.end));
				var current_visible_range = end_sec - start_sec;
				timelinedata.currenVisibleRange = current_visible_range;
				// console.log('range changed to: ' + current_visible_range);
        // document.getElementById("info").innerHTML += "event " + (count-1) + " edited<br>";
      }
			
			// callback function for the current time selector change
      var ontimechange = function () {
      	// var count = data.getNumberOfRows();
				// console.log(timeline.getCustomTime());
				// move video to currenty selected time
				myPlayer.pause();
				myPlayer.currentTime(methods._convertDateToSeconds(new Date(timeline.getCustomTime())));
				
      }

      // Add event listeners
      google.visualization.events.addListener(timeline, 'select', onselect);
      google.visualization.events.addListener(timeline, 'change', onchange);
      google.visualization.events.addListener(timeline, 'delete', ondelete);
      google.visualization.events.addListener(timeline, 'add', onadd);
      google.visualization.events.addListener(timeline, 'edit', onedit);
			google.visualization.events.addListener(timeline, 'rangechange', onrangechange);
			google.visualization.events.addListener(timeline, 'timechange', ontimechange);

      // Draw our timeline with the created data and options
      timeline.draw(data, options);
			
			// add required timeline buttons
			$('.timeline-navigation').append('<div id="add-timestamp" class="timeline-navigation-timestamp" title="' + language.strings.CREATE_CHAPTER_TITLE + '"></div>');
			$('#add-timestamp').bind('click', function() {
				methods._timelineCreateTimestamp()
			});
			
			// add region indicator if annotation provides a spatial region
			// add annotation type style
			for (row = 0; row < data.getNumberOfRows(); row++) {
				if ((data.getValue(row, 9)) && (data.getValue(row, 10)) && (data.getValue(row, 13)) && (data.getValue(row, 14))) {
					// add spatial region indicator
			 		$('.item-' + row).addClass('spatial-region-set');
				}
				if (data.getValue(row, 16) === (settings.types_of_annotations.bookmark)) {
					// set annotation style to bookmark
					$('.item-' + row).addClass('type-bookmark');
				}
				else {
					// set annotation style to bookmark
					$('.item-' + row).addClass('type-annotation');
				}
			}
			
			// add autosave
			autosave = window.setInterval(function() { methods._fileOps('write') }, settings._default.autosave_delay);
			
			// save current annotations to file
			methods._fileOps('write');
			
			// hide annotator loading overlay
			$('body').unmask();
			
			// show help tooltip (if enabled)
			/* DEACTIVATED
			if (($.cookie('show_help') == '') || ($.cookie('show_help') == undefined) || ($.cookie('show_help') == null)) {  
	
				var tooltips_bubbles = '<div id="tip_video_container" style="display: none;">' + language.strings.TOOLTIP_VIDEO_CONTAINER + '</div>';
				
				$('#video-container').append(tooltips_bubbles);
				
				$('.annotator-container').bubbletip(
					$('#tip_video_container'), {
						deltaDirection: 'down',
						animationDuration: 100,
						offsetTop: 0,
						positionAtElement: $('.annotator-container .headline')
					}
				);
			
			}
			else {
				// help is switched off => replace help menu text
				$("#toggle-help").text(language.strings.HELP_SWITCH_ON);
			}
			*/
			
		},
		
		
		/**
     * Delete timeline item
     *
     * Callback after item has been removed from the annotation tool. Annotations get saved to the annotation file after an item has been
     * removed.
     *
     * @param integer {selected_item} Item number of removed annotation
     *
     */
		_timelineItemDelete: function(selected_item) {
			console.log('deleted ' + selected_item);
			// add annotation type style
	    for (row = 0; row < data.getNumberOfRows(); row++) {
				if ((data.getValue(row, 16)) === (settings.types_of_annotations.bookmark)) {
					// set annotation style to bookmark
					$('.item-' + row).addClass('type-bookmark');
			 	}
			 	else {
					// set annotation style to bookmark
					$('.item-' + row).addClass('type-annotation');
			 	}
			}
			
			// save current annotations to file
 		  methods._fileOps('write');
			
		},
		
		/**
     * Edit timeline item
     *
     * Callback after editing an existing annotation item in the timeline. Saves all set values to the annotation table and enables
     * spatial selector. This allows adding or modifying a spatial region to/of an annotation.
     *
     * @param integer {selected_item} Item number of removed annotation
     *
     */
		_timelineItemEdit: function(selected_item) {
  		 var row = undefined;
			 var sel = timeline.getSelection();
       if (sel.length) {
          if (sel[0].row != undefined) {
             var row = sel[0].row;
          }
       }
			 
			 if (row == undefined) {
				 row = selected_item;
			 }
			 
			 // selected row has been found
  	   if (row != undefined) {
				 // save values only if they have been changed
				 if (elementChanged == true) {
	         var label = search_results.results[$('#search-results option:selected').val()].label;
					 var description = search_results.results[$('#search-results option:selected').val()].description;
					 var uri = search_results.results[$('#search-results option:selected').val()].uri;
					 var url = $('#has-content').val();
					 var preferredLabel = $('#preferred-label').val();
					 var type_of_annotation = settings.types_of_annotations.annotation;
					 if ((typeof(url) != undefined) && (url != null) && (url != '')) {
						 uri = url;
						 // annotation type bookmark
						 type_of_annotation = settings.types_of_annotations.bookmark;
						 // set annotation style to bookmark
						 $('.item-' + row).addClass('type-bookmark');
					 }
					 if ((typeof(preferredLabel) != undefined) && (preferredLabel != null) && (preferredLabel != '')) {
						 data.setValue(row, 15, preferredLabel);
						 label = preferredLabel;
					 }
					 
					 if (type_of_annotation != settings.types_of_annotations.bookmark) {
					 	 var type = $('#select-type option:selected').val();
					 }
					 else {
						 var type = settings.annotationtype.bookmark;
					 }
					 data.setValue(row, 5, type);
					 console.log('slected row: ' + row);
					 data.setValue(row, 2, label);
					 if (description != undefined) {
						data.setValue(row, 4, description);
						data.setValue(row, 3, uri);
					 }
					 data.setValue(row, 16, type_of_annotation);
					 data.setValue(row, 8, settings.user_id);
					 
					 // set edit inidicator to false
					 elementChanged = false;
					 
					 timeline.changeItem(row, {
						 'content': label,
						 // start, end, and group can be added here too.
					 });
					 
					 // save current annotations to file
					 methods._fileOps('write');
					 
					// add annotation type style
					for (row = 0; row < data.getNumberOfRows(); row++) {
						if (data.getValue(row, 16) === (settings.types_of_annotations.bookmark)) {
							// set annotation style to bookmark
								$('.item-' + row).addClass('type-bookmark');
						}
						else {
							// set annotation style to bookmark
							$('.item-' + row).addClass('type-annotation');
						}
					}
					 
				 }
       } else {
	       // alert("First select an event, then press remove again");
       }
		},
		
		/**
     * Add timeline item
     *
     * Callback after adding a new annotation to the timeline. Sets all selected values (like concept, type, etc. ...)
     *
     * @param bool {enableSpatialSelector} When true: Enables area select for adding a spatial region to the created annotation.
     *
     */
		_timelineItemCreate: function(enableSpatialSelector) {
			 // retrieve the selected row
       var sel = timeline.getSelection();
       if (sel.length) {
          if (sel[0].row != undefined) {
             var row = sel[0].row;
          }
       }
	
  	   if (row != undefined) {
				 // set annotation to active
				 data.setValue(row, 17, 1);
				 if ($('#search-results option:selected').val() != undefined) {
	         var label = search_results.results[$('#search-results option:selected').val()].label;
					 var description = search_results.results[$('#search-results option:selected').val()].description;
					 var uri = search_results.results[$('#search-results option:selected').val()].uri;
					 var url = $('#has-content').val();
					 var preferredLabel = $('#preferred-label').val();
					 // add additional values for map
					 if (('lng' in search_results.results[$('#search-results option:selected').val()]) && ('lat' in search_results.results[$('#search-results option:selected').val()])) {
						 // 18 ... country, street, city, zip, map, lng, lat
						 if ('route' in search_results.results[$('#search-results option:selected').val()]) {
							 data.setValue(row, 19, search_results.results[$('#search-results option:selected').val()].route + ' ' + search_results.results[$('#search-results option:selected').val()].street_number);
						 }
						 preferredLabel = search_results.results[$('#search-results option:selected').val()].label;
					 	 data.setValue(row, 18, search_results.results[$('#search-results option:selected').val()].country);
						 data.setValue(row, 20, search_results.results[$('#search-results option:selected').val()].locality);
						 data.setValue(row, 21, search_results.results[$('#search-results option:selected').val()].postal_code);
						 data.setValue(row, 22, search_results.results[$('#search-results option:selected').val()].uri);
						 data.setValue(row, 23, (search_results.results[$('#search-results option:selected').val()].lng).toString());
						 data.setValue(row, 24, (search_results.results[$('#search-results option:selected').val()].lat).toString());
					 }
					 
				 }
				 else {
					 var label = 'New';
				 }
				 
				 var type_of_annotation = settings.types_of_annotations.annotation;
				 if ((typeof(url) != undefined) && (url != null) && (url != '')) {
					 uri = url;
					 // annotation type bookmark
					 type_of_annotation = settings.types_of_annotations.bookmark;
					 // set annotation style to bookmark
					 $('.item-' + row).addClass('type-bookmark');
				 }
				 else if (uri.match(/http:\/\/54.247.177.130\//)) {
					 console.log('Yoovis News Search');
					 // yoovis news: annotation type bookmark
					 type_of_annotation = settings.types_of_annotations.bookmark;
					 // set annotation style to bookmark
					 $('.item-' + row).addClass('type-bookmark');
					 if ((typeof(preferredLabel) != undefined) || (preferredLabel != null) || (preferredLabel != '')) {
					   preferredLabel = search_results.results[$('#search-results option:selected').val()].label;
					 }
				 }
	
				 if ((typeof(preferredLabel) != undefined) && (preferredLabel != null) && (preferredLabel != '')) {
					 data.setValue(row, 15, preferredLabel);
					 label = preferredLabel;
				 }
					 
				 if (type_of_annotation != settings.types_of_annotations.bookmark) {
				   var type = $('#select-type option:selected').val();
				 }
				 else {
				   var type = settings.annotationtype.bookmark;
				 }
				 data.setValue(row, 5, type);
				 data.setValue(row, 2, label);
				 if (description != undefined) {
					 data.setValue(row, 4, description);
				   data.setValue(row, 3, uri);
				 }
				 data.setValue(row, 16, type_of_annotation);
				 data.setValue(row, 8, settings.user_id);
				 
				 // calculate start and endtime of annotation
				 var currentPlaybackTime = parseInt(Math.round(myPlayer.currentTime()), 10);
				 if ((typeof(currentPlaybackTime) == "undefined") || (typeof(currentPlaybackTime) == NaN)) {
					 currentPlaybackTime = 0;
				 }
				 
				 timeline.changeItem(row, {
					 'content': label,
					 'start': methods._convertSecondsToDate(currentPlaybackTime), 
				 	 'end': methods._convertSecondsToDate((currentPlaybackTime + settings._default.annotation_duration))
					 // start, end, and group can be added here too.
				 });
				 
				 // save current annotations to file
				 methods._fileOps('write');
				 
				 if (enableSpatialSelector == true) {
					 // enable spatial selector
					 areaSelector.setOptions({ enable: true });
					 areaSelector.update();
					 areaSelector.setOptions({ show: true, hide: false });
					 areaSelector.update();
				 }
				 
				
				// add annotation type style
		  	for (row = 0; row < data.getNumberOfRows(); row++) {
				 	if (data.getValue(row, 16) === (settings.types_of_annotations.bookmark)) {
						// set annotation style to bookmark
						$('.item-' + row).addClass('type-bookmark');
					}
					else {
						// set annotation style to bookmark
						$('.item-' + row).addClass('type-annotation');
					}
				}
				 
       } else {
	       // alert("First select an event, then press remove again");
       }
		},
		
		/**
     * Create timeline timestamp
     *
     * Creates timestamp (start and end time are the same). Could get used for chapter markers. Not fully implemented yet!
     * @todo: Implement timestamp possibility.
     *
     */
		_timelineCreateTimestamp: function () {
			var range = timeline.getVisibleChartRange();
      var start = new Date((range.start.valueOf() + range.end.valueOf()) / 2);
      var content = 'New';

      timeline.addItem({
        'start': start,
        'content': content
      });

      var count = data.getNumberOfRows();
      timeline.setSelection([{
        'row': count-1
      }]);
			
			// console.log('timestamp');
			
			$.colorbox({inline:true,
										width: settings.popup.input.width,
										height: settings.popup.input.height,
										href: "#resource-search",
										onClosed: function(){ methods._timelineItemCreate(false); }
									});
			
		},
		
		/**
     * Spatial area set?
     *
     * Adds the value of a spatial area to an annoation if it has been set by the user.
     *
     * @param array {selAreaPercent} Associative array with all information of the created spatial area (x1, x2, y1, y2, w, h)
     *
     */
		_spatialRegionSet: function(selAreaPercent) {
			var sel = timeline.getSelection();
       if (sel.length) {
          if (sel[0].row != undefined) {
             var row = sel[0].row;
          }
       }
	
  	   if (row != undefined) {
				 // set spatial region [x1] 9,[y1] 10,[x2] 11,[y2] 12,[w] 13,[h] 14
				 data.setValue(row, 9, selAreaPercent['x1']);
				 data.setValue(row, 10, selAreaPercent['y1']);
				 data.setValue(row, 11, selAreaPercent['x2']);
				 data.setValue(row, 12, selAreaPercent['y2']);
				 data.setValue(row, 13, selAreaPercent['w']);
				 data.setValue(row, 14, selAreaPercent['h']);
				 
				 // add spatial region indicator
				 $('.item-' + row).addClass('spatial-region-set');
			 }
			 else {
				 alert(language.strings.SPATIAL_REGION_ERROR);
			 }
		},
		
		/**
     * Resize video container
     *
     * Calculates the size of the video container to fill 100% of its parent container width
     *
     * @param bool {init} Initiates timeline if set to true (used only once when initiating the plugin)
     *
     */
		resizeVideoJS: function(init) {
			
			var container_width = ($('.row-fluid').width() - 6);
			// set video width according to settings value
			
			var player_width = parseInt(($('#' + settings.containers.player).outerWidth()/100) * settings.video_width);
			// myPlayer.width(container_width).height($('#' + settings.containers.video + '_html5_api').height());			
			myPlayer.width(player_width).height($('#' + settings.containers.video + '_html5_api').height());
			$('#' + settings.containers.player).css('padding-left', parseInt(($('#' + settings.containers.player).outerWidth() - player_width)/2) + 'px');
			
		
			// init timeline only once when plugin gets loaded
			if (init == true) {
				// get video duration
				duration = parseInt(Math.round(myPlayer.duration()), 10);
				
				// load timeline when video has loaded
				// init google
				google = settings.google;
				// Set callback to run when API is loaded
				google.setOnLoadCallback(methods._timelineDraw());
				
				// draw timeline
				methods._timelineDraw();
			}
			
		},
		
		/**
     * Time update
     *
     * Centers the timeline at the currently video playback time. A blue indicator bar shows the current playback time in the timeline.
     * Called for re-calculating the time indicator and the visible range of the timeline (normally called 3 times per second)
     *
     */
		_timeupdateVideoJS: function() {
			var currentPlaybackTime = parseInt(Math.round(myPlayer.currentTime()), 10);
			// is video near start or end time?
			timeline.setCustomTime(methods._convertSecondsToDate(currentPlaybackTime));
			if ((currentPlaybackTime + (timelinedata.currenVisibleRange / 2)) > duration) {
				// set range to video end				
				timeline.setVisibleChartRange(methods._convertSecondsToDate(duration - timelinedata.currenVisibleRange), methods._convertSecondsToDate(duration));
			}
			else if ((currentPlaybackTime - (timelinedata.currenVisibleRange / 2)) < 0) {
				// set range to video start				
				timeline.setVisibleChartRange(methods._convertSecondsToDate(0), methods._convertSecondsToDate(timelinedata.currenVisibleRange));
			}
			else {
				// set viewing range
				timeline.setVisibleChartRange(methods._convertSecondsToDate(currentPlaybackTime - (timelinedata.currenVisibleRange / 2)), methods._convertSecondsToDate(currentPlaybackTime + (timelinedata.currenVisibleRange / 2)));
			}
			
			// get active elements
			if ((data.getNumberOfRows() != undefined) && ((data.getNumberOfRows()) > 0)) {
				// at least one element defined
				// search for active one(s)
				for (row = 0; row < data.getNumberOfRows(); row++) {
					var starttime, endtime;
					var spatialRegion = {};
					for (column = 0; column < data.getNumberOfColumns(); column++) {
						if (column == 0) {
							// annotation start time
							starttime = methods._convertDateToSeconds(new Date(data.getValue(row, column)));
						}
						else if (column == 1) {
							// annotation end time
							endtime = methods._convertDateToSeconds(new Date(data.getValue(row, column)));
						}
						else if ((column >= 9) && (column <= 14)) {
							// annotation spatial region
							if ((column == 9) && (data.getValue(row, column) != '') && $.isNumeric(data.getValue(row, column))) {
								// x1
								spatialRegion['x1'] = data.getValue(row, column);
								spatialRegion['available'] = true;
							}
							else if ((column == 10) && (data.getValue(row, column) != '') && $.isNumeric(data.getValue(row, column))) {
								// y1
								spatialRegion['y1'] = data.getValue(row, column);
								spatialRegion['available'] = true;
							}
							else if ((column == 11)  && (data.getValue(row, column) != '') && $.isNumeric(data.getValue(row, column))) {
								// x2
								spatialRegion['x2'] = data.getValue(row, column);
								spatialRegion['available'] = true;
							}
							else if ((column == 12) && (data.getValue(row, column) != '') && $.isNumeric(data.getValue(row, column))) {
								// y2
								spatialRegion['y2'] = data.getValue(row, column);
								spatialRegion['available'] = true;
							}
							else if ((column == 13) && (data.getValue(row, column) != '') && $.isNumeric(data.getValue(row, column))) {
								// w
								spatialRegion['w'] = data.getValue(row, column);
								spatialRegion['available'] = true;
							}
							else if ((column == 14) && (data.getValue(row, column) != '') && $.isNumeric(data.getValue(row, column))) {
								// h
								spatialRegion['h'] = data.getValue(row, column);
								spatialRegion['available'] = true;
							}
						}
					}
					
					// get current time
					var currentTime = methods._convertDateToSeconds(timeline.getCustomTime());
					// is selected element active?
					if ((starttime <= currentTime) && (endtime >= currentTime)) {
						$('.item-' + row).addClass('active-item');
						if (spatialRegion['available'] == true) {
							// spatial region exists => show it
							if ($('.spatial-region-item-' + row).length == 0) {
								$('#' + settings.containers.video).append('<div class="spatial-annotation spatial-region-item-' + row + '" style="top: ' + spatialRegion['y1'] + '%; left: ' + spatialRegion['x1'] + '%; width: ' + spatialRegion['w'] + '%; height: ' + spatialRegion['h'] + '%;"><span class="spatial-annotation-label">' + data.getValue(row, 2) + '</span></div>');
							}
						}
					}
					else {
						$('.item-' + row).removeClass('active-item');
						$('.spatial-region-item-' + row).remove();
					}
				}
			}
			
		},
		
		/**
     * Play video
     *
     * Called every time when the video gets played. Gets current visible timeline range, first visible timestamp and last visible 
     * timestamp. Those values are needed for calculating the current time indicator in the timeline.
     *
     */
		_playVideoJS: function() {
			var currentRange = timeline.getVisibleChartRange();
			var start_sec = methods._convertDateToSeconds(new Date(currentRange.start));
			var end_sec = methods._convertDateToSeconds(new Date(currentRange.end));
			var current_visible_range = end_sec - start_sec;
			timelinedata.currenVisibleRange = current_visible_range;
		},
		
		/**
		 *	Convert timestring to seconds
		 *
		 *	Converts a time string into seconds
		 *
		 *	@param	string {timestring} - time string
		 *	@return integer calculated number of seconds
		 */	
		_convertTimeToSeconds: function (time) {
			var timeValues = time.split(':');
			// console.log(timeValues);
			if (timeValues.length == 2) {
				// mm:ss
				calcTime = (60 * parseInt(timeValues[0], 10)) + parseInt(timeValues[1], 10);
			}
			else if (timeValues.length == 3) {
				// hh:mm:ss
				calcTime = (60 * 60 * parseInt(timeValues[0], 10)) + (60 * parseInt(timeValues[1], 10)) + parseInt(timeValues[2], 10);
			}
			if (typeof(calcTime) == 'string') {
				// error => time not converted into seconds
			}
			else {
				return calcTime;
			}
		},
		
		/**
		 *	Convert seconds to time
		 *	Converts seconds into time (e.g. 00:00:00)
		 *
		 *	@param	integer {seconds} - number of seconds
		 *	@return string generated time string
		 */
		_convertSecondsToTime: function (seconds) {
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
		
		/**
     * Convert seconds to date
     *
     * Converts a given number of seconds into date format
     *
     * @param integer {seconds} Item number of removed annotation
     * @return date Converted date object
     *
     */
		_convertSecondsToDate: function (seconds) {
			// remove possible decimal places
			var dateTime;
			seconds = parseInt(seconds);
			if (seconds >= (60 * 60)) {
				// duration >= 1 hour
				var hours = parseInt(seconds/(60 * 60));
				var minutes = parseInt((seconds - (hours * 60 * 60))/60);
				dateTime = new Date(2012, 1, 1, hours, minutes, seconds)
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
				dateTime = new Date(2012, 1, 1, 0, minutes, seconds)
			}
			else {
				// duration <= 1 minute
				if (seconds < 10) {
					seconds = '0' + seconds;
				}
				dateTime = new Date(2012, 1, 1, 0, 0, seconds)
			}
			return dateTime;
		},
		_convertDateToSeconds: function (dateTime) {
			var seconds;
			seconds = (dateTime.getHours() * 60 * 60) + (dateTime.getMinutes() * 60) + dateTime.getSeconds();
			return seconds;
		},
		
		/**
		 *	File operations
		 *	Allows reading from a file and writing to one
		 *
		 *	@param string {type} Function which gets used (read or write)
		 *	@param string {filename} Name of file for reading/writing
		 */	
		_fileOps: function(type, filename) {
			if (type != '') {
				
				// convert data table to json
				if (type == 'write') {
					var json_string = '{' + methods._videoDataToJson() + ', ' + methods._dataToJson() + '}';
					console.log(data);
					// console.log(json_string);
				}
				else {
					var json_string = '';
				}
				// console.log(json_string);
				var http_method = 'POST';
				var query = 'core/ajax/annotation-file-functions.php';
				var post_data = 'data=' + json_string + '&type=' + type;
				if ((typeof(filename) == String) && (filename != '')) {
					post_data += '&file=' + filename;
				}
				else {
					post_data += '&file=' + settings.annotation.filename;
				}
				
				$.ajax({
					url: query,
					type: http_method,
					data: post_data,
					async: false,
					cache: false,
					timeout: 3000,
					error: function(){
						return true;
					}
				})
				.done(function (response) {
					userAnnotations = response;
					// show message if data has been saved
					if (type == 'write') {
						$('#' + settings.containers.annotations + ' p').fadeIn('slow', function() {
							$('#' + settings.containers.annotations + ' p').fadeOut('slow')
						});
						currTime = new Date();
						console.info('Saved ' + currTime.toGMTString());
					}
					// load existing annotations
					else if (type == 'read') {
						
						var annotation_list = jQuery.parseJSON(userAnnotations);
						console.log(annotation_list)
						console.log('len ' + annotation_list.annotations.length);
						if (annotation_list != null) {
							$.each(annotation_list.annotations, function (i, annotation) {
								var start = methods._convertSecondsToDate(methods._convertTimeToSeconds(annotation.starttime.value));
								var end = methods._convertSecondsToDate(methods._convertTimeToSeconds(annotation.endtime.value));
								var strings = {'label': annotation.label.value, 'description': annotation.description.value, 'annotationuri': annotation.annotation.value, 'type': annotation.annotationtype.value};
								$.each(strings, function(key, value) {
									console.log(value);
									if (value != null) {
										strings[key] = methods._insertStandardCharacters(value);
									}
									else {
										strings[key] = '';
									}
								})
								
								spatial_values = [];
								// create data object from annotation json
								if (('spatial' in annotation) && (annotation.spatial.value != '') && (annotation.spatial.value != 'null')) {
									// split spatial string into separat values
									spatial_values = (annotation.spatial.value).split(',');
								}
								console.log('spatial: ' + spatial_values);
								var preferredlabel = '';
								if (('preferredlabel' in annotation) && (annotation.preferredlabel.value != '') && (annotation.preferredlabel.value != 'null')) {
									preferredlabel = annotation.preferredlabel.value;
								}
								var vcard_data = {lat: '', lng: '', city: '', zip: '', country: '', street: '', map: ''};
								if (('lat' in annotation) && (annotation.lat.value != '') && (annotation.lat.value != 'null')) {
									vcard_data.lat = annotation.lat.value;
								}
								if (('lng' in annotation) && (annotation.lng.value != '') && (annotation.lng.value != 'null')) {
									vcard_data.lng = annotation.lng.value;
								}
								if (('city' in annotation) && (annotation.city.value != '') && (annotation.city.value != 'null')) {
									vcard_data.city = annotation.city.value;
								}
								if (('zip' in annotation) && (annotation.zip.value != '') && (annotation.zip.value != 'null')) {
									vcard_data.zip = annotation.zip.value;
								}
								if (('country' in annotation) && (annotation.country.value != '') && (annotation.country.value != 'null')) {
									vcard_data.country = annotation.country.value;
								}
								if (('street' in annotation) && (annotation.street.value != '') && (annotation.street.value != 'null')) {
									vcard_data.street = annotation.street.value;
								}
								console.log('resource: ', annotation.resource.value);
								console.log('relation: ', annotation.relation.value);
								console.log('fragment: ', annotation.fragment.value);
								console.log('creator: ', annotation.creator.value);
								console.log('annotationtype: ', annotation.annotationtype.value);
								console.log('active: ', annotation.active.value);
								loadedRows.push([start, end, strings['label'], annotation.resource.value,  strings['description'], annotation.relation.value, annotation.fragment.value,  strings['annotationuri'], annotation.creator.value, parseInt(spatial_values[0]), parseInt(spatial_values[1]), parseInt(spatial_values[2]), parseInt(spatial_values[3]), parseInt(spatial_values[4]), parseInt(spatial_values[5]), preferredlabel, annotation.annotationtype.value, parseInt(annotation.active.value), vcard_data.street, vcard_data.street, vcard_data.zip, vcard_data.city, vcard_data.map, vcard_data.lng, vcard_data.lat]);				
							});
							
							console.log(loadedRows);
							
							// initialise player and timeline
							methods.resizeVideoJS(true);
							
						}
						
					}
				})
			}
		},
		
		/**
     * Convert data to JSON
     *
     * Converts the google data table into a JSON string (which is needed for saving to the annotation file)
     *
     */
		_dataToJson: function() {
			// any values in data table?
			json_obj = {};
			json_obj.annotations = [];
			console.log(json_obj);
			if (data.getNumberOfRows() > 0) {
				for (row = 0; row < data.getNumberOfRows(); row++) {
					row_data = {};
					spatial_data = {};
					for (column = 0; column < data.getNumberOfColumns(); column++) {
						column_data = {};
						var column_label = data.getColumnLabel(column);
						console.log(column + ': ' + data.getValue(row, column));
						if (data.getColumnType(column) == 'string') {
							var tmp_str = data.getValue(row, column);
							if ((tmp_str != '') && (typeof(tmp_str) != NaN) && (tmp_str != null)) {
								data.setCell(row, column, methods._replaceURLCharacters(tmp_str));
								column_data = {value: methods._replaceURLCharacters(tmp_str)};
							}
						}
						else if (data.getColumnType(column) == 'number') {
							if ((column_label == 'x1') || (column_label == 'y1') || (column_label == 'x2') || (column_label == 'y2') || (column_label == 'w') || (column_label == 'h')) {
								if (isNaN(data.getValue(row, column))) {
									data.setCell(row, column, null);
								}
								else {
									spatial_data[column_label] = data.getValue(row, column);
								}
							}
							else {
								if (isNaN(data.getValue(row, column))) {
									data.setCell(row, column, null);
									column_data = {value: null};
								}
							}
						}
						if ((column_label == 'starttime') || (column_label == 'endtime')) {
							json_string += '"' + column_label + '": "' + methods._convertSecondsToTime(methods._convertDateToSeconds(new Date(data.getValue(row, column)))) + '"';
							column_data = {value: methods._convertSecondsToTime(methods._convertDateToSeconds(new Date(data.getValue(row, column))))};
						}
						else {
							json_string += '"' + column_label + '": "' + data.getValue(row, column) + '"';
							column_data = {value: data.getValue(row, column)};
						}
						if ((column + 1) < data.getNumberOfColumns()) {
							json_string += ', ';
						}
						row_data[column_label] = column_data;
					}
					// add spatial string (if set)
					if (('x1' in spatial_data) && ('y1' in spatial_data) && ('x2' in spatial_data) && ('y2' in spatial_data) && ('w' in spatial_data) && ('h' in spatial_data)) {
						if ((spatial_data['x1'] != null) && (spatial_data['y1'] != null) && (spatial_data['x2'] != null) && (spatial_data['y2'] != null) && (spatial_data['w'] != null) && (spatial_data['h'] != null)) {
							row_data.spatial = {value: spatial_data['x1'] + ',' + spatial_data['y1'] + ',' + spatial_data['x2'] + ',' + spatial_data['y2'] + ',' + spatial_data['w'] + ',' + spatial_data['h']};
							console.log(row_data.spatial);
						}
					}
					json_obj.annotations.push(row_data);
				}
			}
			/*
			json_string = '"annotations":[';
			if (data.getNumberOfRows() > 0) {
				var data_copy = data;
				for (row = 0; row < data.getNumberOfRows(); row++) {
					// row key
					row_content = {};
					json_string += '{';
					for (column = 0; column < data.getNumberOfColumns(); column++) {
						var column_label = data.getColumnLabel(column);
						console.log(column + ': ' + data.getValue(row, column));
						if (data.getColumnType(column) == 'string') {
							var tmp_str = data.getValue(row, column);
							if ((tmp_str != '') && (typeof(tmp_str) != NaN) && (tmp_str != null)) {
								data.setCell(row, column, methods._replaceURLCharacters(tmp_str));
							}
						}
						else if (data.getColumnType(column) == 'number') {
							if (isNaN(data.getValue(row, column))) {
								data.setCell(row, column, null);
							}
						}
						if ((column_label == 'starttime') || (column_label == 'endtime')) {
							json_string += '"' + column_label + '": "' + methods._convertSecondsToTime(methods._convertDateToSeconds(new Date(data.getValue(row, column)))) + '"';
						}
						else {
							json_string += '"' + column_label + '": "' + data.getValue(row, column) + '"';
						}
						if ((column + 1) < data.getNumberOfColumns()) {
							json_string += ', ';
						}
					}
					json_string += '}';
					if ((row + 1) < data.getNumberOfRows()) {
						json_string += ', ';
					}
				}
				json_string += ']';
			}
			else {
				json_string += ']';
			}
			*/
			return '"annotations": ' + JSON.stringify(json_obj.annotations);
		},
		
		/**
     * Convert video data to JSON
     *
     * Creates a JSON string of the data of the loaded video
     *
     */
		_videoDataToJson: function() {
			var videoFile = document.getElementById(settings.containers.video + '_html5_api');
			json_string = '"video":[{' + 
											'"uri": "' + methods._replaceURLCharacters(settings.video.id) + '", ' +
											'"width": "' + videoFile.videoWidth + '", ' +
											'"height": "' + videoFile.videoHeight + '", ' + 
											'"player_width": "' + $('#' + settings.containers.video).width() + '", ' +
											'"player_height": "' + $('#' + settings.containers.video).height() + '"' +
										'}]';
			return json_string;
		},
		
		/**
     * Initiate plugins
     *
     * Loads all plugins which are specified in the PHP settings file. This method gets called recoursive until every plugin has been
     * loaded properly.
     *
     * @param integer {plugin_number} Number of the current plugin
     *
     */
		_initPlugins: function(plugin_number) {
			// get plugin data
			console.log((settings.resourceplugins).length + ' ' + plugin_number);
			$.ajax({
				url: 'plugins/' + settings.resourceplugins[plugin_number] + '/lang/' + lang_file,
				type: 'POST',
				data: '',
				async: false,
				cache: false,
				timeout: 3000,
				error: function(){
					return true;
				}
			})
			.done(function (response) {
				// create search selector
				var plugin_strings = jQuery.parseJSON(response);
				var plugin_html = '<input type="radio" id="source-' + plugin_number + '" name="search-source-selector" class="ui-helper-hidden-accessible annotator-search-source"';
				if (plugin_number == 0) {
					plugin_html += ' checked="checked"';
				}
				plugin_html += '/><label for="source-' + plugin_number + '" class="ui-button ui-widget ui-state-default ui-button-text-only';
				if (plugin_number == 0) {
					plugin_html += ' ui-state-active ui-corner-left';
				}
				if ((plugin_number + 1) == (settings.resourceplugins).length) {
					plugin_html += ' ui-corner-right';
				}
				plugin_html += '" role="button" aria-disabled="false" aria-pressed="true"><span class="ui-button-text">' + plugin_strings.strings.NAME + '</span></label>';
				$('#search-source').append(plugin_html);
				if ((settings.resourceplugins).length > (plugin_number + 1)) {
					// more plugins available
					// load next one
					methods._initPlugins((plugin_number + 1));
				}
				else {
					// all plugins loaded
					$('#search-source').buttonset();
					$('#search-source label').bind('click', function() {
						$('#search-source label').removeClass('ui-state-active');
						$(this).addClass('ui-state-active');
						$('#search-source input').removeAttr('checked');
						$('#' + $(this).attr('for')).attr('checked', 'checked');
					});
					$('#search-source label').hover(function() {
						$(this).addClass('ui-state-hover');
					}, function() {
						$(this).removeClass('ui-state-hover');
					});
					
					// search source change function
					$('.annotator-search-source').bind('click', function() {
						// load pre-annotations automatically if pre_annotations plugin selected
						var plugin_id = ($("input[@name=search-source-selector]:checked").attr('id')).split('-');
						plugin_id = plugin_id[1];
						
						console.log('SEL: ' + settings.resourceplugins[plugin_id]);
						
						if (settings.resourceplugins[plugin_id] == 'pre_annotations') {
							
							$.ajax({
								url: 'plugins/' + settings.resourceplugins[plugin_id] + '/pre_annotations.data-lookup.php',
								type: 'POST',
								data: {uri: settings.video.id, parameters: 'http://www.w3.org/2000/01/rdf-schema#label,http://dbpedia.org/ontology/abstract'},
								async: false,
								cache: false,
								timeout: 3000,
								error: function(){
									return true;
								}
							})
							.done(function (response_data) {
							
								$.ajax({
									url: 'plugins/' + settings.resourceplugins[plugin_id] + '/pre_annotations.get-all.php',
									type: 'POST',
									async: false,
									cache: false,
									timeout: 3000,
									error: function(){
										return true;
									}
								})
								.done(function (response_data) {
									
									search_results = jQuery.parseJSON(response_data);
									// response(search_results.results);
									$('#search-results').empty();
									var html = '';
									$.each(search_results.results, function(key, value) {
										html += '<option value="' + key + '"';
										if (key == 0) {
											html += ' selected="selected"';
										}
										html += '>' + value.label + '</option>'
									})
									$('#search-results').append(html);
									// automatically show 1st result in explore area
									$('#search-preview').empty();
									if (search_results.results[$('#search-results option:selected').val()].description != language.strings.RESULTS_EMPTY) {
										$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
										$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
									}
									
									// bind search result change function
									$('#search-results').change(function() {
										$('#search-preview').empty();
										$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
										$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
									});
									
								})
							
							})
						}
						
					});
					
					// append search input field
					$('#search-source').append('<div class="search-query-container"><input type="text" name="search-query" id="search-query" placeholder="' + language.strings.SEARCH_PLACEHOLDER + '"></div>');
					// append has content (HTML URL) to search box
					$('#search-source').append('<div class="search-query-container"><input type="text" name="has-content" id="has-content" placeholder="' + language.strings.HAS_CONTENT + '"></div>');
					
					if (settings.autocomplete == true) {
						// use autocomplete search
					
						$('#search-query').autocomplete({
							minLength: 2,
							select: function( event, ui ) {
								
								$('#has-content').val('');
								$('#annotation-type').css('display', 'inline');
								// get selected search plugin
								var plugin_id = ($("input[@name=search-source-selector]:checked").attr('id')).split('-');
								plugin_id = plugin_id[1];
								
								$.ajax({
									url: 'plugins/' + settings.resourceplugins[plugin_id] + '/',
									type: 'POST',
									data: 'query=' + ui.item.value,
									async: false,
									cache: false,
									timeout: 3000,
									error: function(){
										return true;
									}
								})
								.done(function (response_data) {
									
									search_results = jQuery.parseJSON(response_data);
									// response(search_results.results);
									$('#search-results').empty();
									var html = '';
									$.each(search_results.results, function(key, value) {
										html += '<option value="' + key + '"';
										if (key == 0) {
											html += ' selected="selected"';
										}
										html += '>' + value.label + '</option>'
									})
									$('#search-results').append(html);
									// automatically show 1st result in explore area
									$('#search-preview').empty();
									if (search_results.results[$('#search-results option:selected').val()].description != language.strings.RESULTS_EMPTY) {
										$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
										$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
									}
									
									// bind search result change function
									$('#search-results').change(function() {
										$('#search-preview').empty();
										$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
										$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
									});
									
								})
							},
							source: function( request, response ) {
								var term = request.term;
								
							$('#has-content').val('');
							$('#annotation-type').css('display', 'inline');
							// get selected search plugin
							var plugin_id = ($("input[@name=search-source-selector]:checked").attr('id')).split('-');
							plugin_id = plugin_id[1];
							
							
							$.ajax({
								url: 'plugins/' + settings.resourceplugins[plugin_id] + '/',
								type: 'POST',
								data: 'query=' + $('#search-query').val(),
								async: false,
								cache: false,
								timeout: 3000,
								error: function(){
									return true;
								}
							})
							.done(function (response_data) {
								
								search_results = jQuery.parseJSON(response_data);
								response(search_results.results);
								$('#search-results').empty();
								var html = '';
								$.each(search_results.results, function(key, value) {
									html += '<option value="' + key + '"';
									if (key == 0) {
										html += ' selected="selected"';
									}
									html += '>' + value.label + '</option>'
								})
								$('#search-results').append(html);
								// automatically show 1st result in explore area
								$('#search-preview').empty();
								if (search_results.results[$('#search-results option:selected').val()].description != language.strings.RESULTS_EMPTY) {
									$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
									$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
								}
								
								// bind search result change function
								$('#search-results').change(function() {
									$('#search-preview').empty();
									$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
									$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
								});
								
							})
							
				 
							}
							
							
						});
						
					}
					else {
						// search without autocomplete
				
						// bind search function
						$('#search-query').bind('keyup', function() {
							$('#has-content').val('');
							$('#annotation-type').css('display', 'inline');
							// get selected search plugin
							var plugin_id = ($("input[@name=search-source-selector]:checked").attr('id')).split('-');
							plugin_id = plugin_id[1];
														
							var url = 'plugins/' + settings.resourceplugins[plugin_id] + '/';
							var http_method = 'POST';
							var query_string = 'query=' + $('#search-query').val();
							
							if (window.XMLHttpRequest) {
								// code for IE7+, Firefox, Chrome, Opera, Safari
								xmlhttp=new XMLHttpRequest();
							}
							else {
								// code for IE6, IE5
								xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
							}
							
							xmlhttp.open(http_method, url,true);
							xmlhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
							xmlhttp.send(query_string);
							
							xmlhttp.onreadystatechange=function() {
								if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
									var response = xmlhttp.responseText;
									search_results = jQuery.parseJSON(response);
									$('#search-results').empty();
									var html = '';
									$.each(search_results.results, function(key, value) {
										html += '<option value="' + key + '"';
										if (key == 0) {
											html += ' selected="selected"';
										}
										html += '>' + value.label + '</option>'
									})
									$('#search-results').append(html);
									// automatically show 1st result in explore area
									$('#search-preview').empty();
									if (search_results.results[$('#search-results option:selected').val()].description != language.strings.RESULTS_EMPTY) {
										$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
										$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
									}
									
									// bind search result change function
									$('#search-results').change(function() {
										$('#search-preview').empty();
										$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
										$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
									});
								}
								else {
								 // return xmlhttp.status;
								 console.log(xmlhttp);
								}
							}
							
							
						});
					
					}
					
					// bind search function
					$('#has-content').bind('keyup', function() {
						var html = '';
						$('#search-query').val('');
						$('#search-results').empty();
						$('#annotation-type').css('display', 'none');
						html += '<option value="0" selected="selected">' + $('#has-content').val() + '</option>'
						$('#search-results').append(html);
						$('#search-preview').empty();
						var json_dummy = '{"results": [{"description": "';
						json_dummy += "<iframe frameborder='0' scrolling='auto' width='400' height='150' src='";
						json_dummy += $('#has-content').val();
						json_dummy += "'"
						json_dummy += '></iframe>", "label": "' + $('#has-content').val() + '", "uri": "' + $('#has-content').val() + '"}]}';
						search_results = jQuery.parseJSON(json_dummy);
						
						if (methods._validateURL($('#has-content').val()) == true) {										
							// valid url
							$('#search-preview').empty();
							$('#search-preview').append('<div class="search-query-container"><input type="text" name="preferred-label" id="preferred-label" placeholder="' + language.strings.PREFERRED_LABEL + '"></div>');
							$('#search-preview').append(search_results.results[$('#search-results option:selected').val()].description);
						}
						else {
							// invalid url
							$('#search-preview').empty();
							$('#search-preview').append(language.strings.URL_INVALID + '[' + $('#has-content').val()  + ']');
						}
					})
					
				}
			})
		},
		
		/**
     * Convert selected area
     *
     * Converts the values of a selected spatial area from pixel into percentage values. This is important to play the annotated video
     * on different screen sizes.
     *
     * @param object {selArea} Object which contains the pixel values of the selected area.
     * @return array Associative array with calculated percentage area values
     *
     */
		_selToPercent: function(selArea) {
			// get player size
			var videoSize = {	'width': $('#' + settings.containers.video).width(),
												'height': $('#' + settings.containers.video).height()
											};
			var wPer = videoSize.width / 100;
			var hPer = videoSize.height / 100;
			selArea = {	'x1': Math.round((selArea.x1 / wPer)),
									'y1': Math.round((selArea.y1 / hPer)),
									'x2': Math.round((selArea.x2 / wPer)),
									'y2': Math.round((selArea.y2 / hPer)),
									'w': Math.round(((selArea.x2 - selArea.x1) / wPer)),
									'h': Math.round(((selArea.y2 - selArea.y1) / hPer))
								};
			return selArea;
		},
		
		/**
     * Calculate percentage values into pixel
     *
     * Calculates percentage area values from an annotation into pixel values. This is required for displaying the spatial region and
     * enabling modification of the selected area by using image area select.
     *
     * @param integer {x1} Percentage LEFT value of the top left point of the selected area
     * @param integer {y1} Percentage TOP value of the top left point of the selected area
     * @param integer {w} Percentage WIDTH of the selected area
     * @param integer {h} Percentage HEIGHT of the selected area 
     * @param object Object which contains the pixel values of the selected area.
     *
     */
		_percentToPixel: function(x1, y1, w, h) {
			// get player size
			var videoSize = {	'width': $('#' + settings.containers.video).width(),
												'height': $('#' + settings.containers.video).height()
											};
			var wPer = videoSize.width;
			var hPer = videoSize.height;
			selArea = {	'x1': Math.round((x1 / 100) * wPer),
									'y1': Math.round((y1 / 100) * hPer),
									'x2': Math.round(((x1 + w) / 100) * wPer),
									'y2': Math.round(((y1 + h) / 100) * hPer)
								};
			return selArea;
		},
		
		/**
     * Show error popup
     *
     * Shows a specified error message to the user
     *
     * @param string {title} Message title
     * @param string {message} Message body
     *
     */
		_showErrorMessage: function(title, message) {
			
			$("body").append('<div id="div-dialog-warning">' + message + '</div>');
					
			$("#div-dialog-warning").dialog({
				title: title,
				resizable: false,
				height: 'auto',
				modal: true,
				buttons: {
					"Ok" : function () {
						$(this).dialog("close");
						$('#div-dialog-warning').remove();
					}
				}
			}).parent().addClass("ui-state-error");
		},
		
		/**
     * Validate URL
     *
     * Validates a given string by regular expressions and determines if it is a URL or not
     *
     * @param string {input_url} URL string which should get validated
     * @return bool True if yes, False if no
     *
     */
		_validateURL: function(input_url) {
			if(/^([a-z]([a-z]|\d|\+|-|\.)*):(\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?((\[(|(v[\da-f]{1,}\.(([a-z]|\d|-|\.|_|~)|[!\$&'\(\)\*\+,;=]|:)+))\])|((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=])*)(:\d*)?)(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*|(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)|((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)|((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)){0})(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(input_url)) {
					return true;	
				}
				else {
					return false;
			}
		},
		
		/**
     * Replace regular characters
     *
     * Replaces the characters ? and & from a string by %3F and %26 to get an url encoded string
     *
     * @param string {string} String which gets encoded
     * @return string Encoded string
     *
     */
		_replaceURLCharacters: function(string) {
			var updated_string = '';
			updated_string = string.replace(/([?])/g, '%3F');
			updated_string = updated_string.replace(/([&])/g, '%26');
			return updated_string;
		},
		
		/**
     * Replace encoded characters
     *
     * Replaces the characters %3F and %26 from a string by ? and & to get an url decoded string
     *
     * @param string {string} String which gets decoded
     * @return string Decoded string
     *
     */
		_insertStandardCharacters: function(string) {
			var updated_string = '';
			updated_string = string.replace(/%3F/g, '?');
			updated_string = updated_string.replace(/%26/g, '&');
			return updated_string;
		}
	};
	
	/**
	 *	$.fn.annotator
	 *	Main function of the hypervideo annotation suite which handles the method selection
	 */ 
	$.fn.annotator = function(method) {
		// Method calling logic
		if ( methods[method] ) {
			return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
		}
		else if ( typeof method === 'object' || ! method ) {
			return methods.init.apply( this, arguments );
		}
		else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.annotator' );
		}    
	};
})( jQuery );