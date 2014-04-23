/**
 * ConnectME annotation tool - jQuery extension for loading annotations
 *
 * This jQuery extension loads annotated and not annotated videos from a CMF instance in the annoation tool. Furthermore, it provides
 * the follwing functions:
 *
 ** Creates a list of annotated and not annotated videos
 ** Loads annotations of a selected video from the CMF
 ** Creates an annotation file of the found annotations
 ** Tries to find labels and descriptions for every loaded annotation and adds them to the annotation list
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
	var language;
	var lang_file;
	var lang_code;
	var queryResult = null;
		
	var methods = {
		
		init: function(options) {
			
			// get settings from annotator
			$.getJSON('core/ajax/get-settings.php', function(response) {
				console.log(response);
				
				settings = $.extend({
					containers: {
						main: 'main-content',
						display: 'block',
						player: 'video-container', 
						annotations: 'timeline-container',
						video: 'loaded-video',
						timeline: 'annotator-timeline'
					},
					resourceplugins: response.settings.resourceplugins,
					cmf: response.cmf.url,
					video: response.video,
					annotator_path: response.settings.base_relative,
					userid: response.userid
				}, options);
				
				console.log(settings);
				
				// get language file
				if ($.cookie('lang') != undefined) {
					lang_file = 'lang.' + $.cookie('lang') + '.json';
					lang_code = $.cookie('lang');
				}
				else {
					lang_file = 'lang.en.json';
					lang_code = 'en';
				}
				$.getJSON('core/lang/' + lang_file, function(response) {
					language = response;
					methods._getVideoLists();
				}) // .end ajax load language file
			
			}) // .end ajax load settings file
				
		}, // .end init
		
		loadVideoFromCmf: function(selected_video_id) {
			var query = 'PREFIX oac: <http://www.openannotation.org/ns/>' + 
									'PREFIX ma: <http://www.w3.org/ns/ma-ont#>' +
									'PREFIX dct: <http://purl.org/dc/terms/>' +
									'PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>' +
									'PREFIX cma: <http://connectme.at/ontology#>' + 
									'SELECT DISTINCT ?annotation ?fragment ?resource ?relation ?creator ?created ?preferredlabel ?annotationtype' +
									'	WHERE {' +
									'		<' + selected_video_id + '>  ma:hasFragment ?f.' +
									'		?f ma:locator ?fragment. ' +
									'		?annotation oac:hasTarget ?f.' +
									'		?annotation oac:hasBody ?resource.' +
									'		?annotation dct:creator ?creator.' +
									'		OPTIONAL { ?annotation dct:created ?created. }' +
									'		OPTIONAL { ?f ?relation ?resource. } '+
									'		OPTIONAL { ?annotation cma:preferredLabel ?preferredlabel. } '+
									'		OPTIONAL { ?annotation rdf:type ?annotationtype. } '+
									'	}' + 
									'ORDER BY ASC(?annotation)';
					
			console.log(query);
					
			$('#indicator-text').empty();
			$('#indicator-text').append(language.strings.ANNOTATIONS_LOADING);
					
			console.log(settings.cmf + "sparql/select?query=" + encodeURIComponent(query) + "&output=json");
					
			// load annotations from cmf
			$.getJSON(settings.cmf + "sparql/select?query=" + encodeURIComponent(query) + "&output=json",function (data) {
						
				// store result json in file (for quick changes check)
				queryResult = JSON.stringify(data);
				console.log(data);
				settings.annotations = [];
				tmp_annotations = [];
				var list = data.results.bindings;
				for(var i in list) {
					var tmp = new Array();
					tmp['annotation']	= list[i].annotation.value;
					tmp['fragment']		= list[i].fragment.value;
					tmp['resource']		= list[i].resource.value;
					if('relation' in list[i]) {
						tmp['relation']		= list[i].relation.value;
					}
					else {
						tmp['relation']		= null;
					}
					tmp['creator']		= list[i].creator.value;
					tmp['created']		= list[i].created.value;
					if('preferredlabel' in list[i]) {
						tmp['label']		= list[i].preferredlabel.value;
					}
					else {
						tmp['label']		= null;
					}
					if('annotationtype' in list[i]) {
						tmp['annotationtype']		= list[i].annotationtype.value;
					}
					else {
						tmp['annotationtype']		= null;
					}
					tmp_annotations.push(tmp);
					// console.log(i);
				}
				
				// remove duplicates
				var reduced_annotations = [];
				var tmp_annotation = {};
				var last_annotation = '';
				var firstrun = true;
				// loop through annotations
				$.each(list, function(key, value) {
					console.log('last: ', last_annotation);
					console.log('this: ', value.annotation.value);
					if (last_annotation != value.annotation.value) {
						// push annotation to list (if its already set)
						console.log('added : ', value.annotation.value);
						// if (firstrun != true) {
							value.active = {value: 1};
							reduced_annotations.push(value);
							// delete(tmp_annotation);
						// }
						// tmp_annotation = value;
					}
					firstrun = false;
					last_annotation = value.annotation.value;
				})
					
				// push last annotation to array
				/*
				if ((typeof(tmp_annotation) == 'object') && ('annotation' in tmp_annotation)) {
					console.log('pushing last one');
					reduced_annotations.push(tmp_annotation);
				}
				*/
				
				settings.annotations = userAnnotations = {"video": [{"uri": settings.video, "width": 0, "height": 0, "player_width": 0, "player_height": 0}], "annotations": reduced_annotations};
				
				console.log(settings.annotations);
						
				// start loading resource content (with first plugin)
				methods.loadDataFromResouce(0);
					
				// console.log(methods.objectSize(settings.annotations.annotations));
				
			});
					
			// create annotation file content
			// create basic structure and set video data
			userAnnotations = {"video": [{"uri": settings.video, "width": 0, "height": 0, "player_width": 0, "player_height": 0}], "annotations":[]};
		
		}, // .end loadVideoFromCmf
		
		_getVideoLists: function() {
			$('#video-url-additional').slideUp(0);
				
			$('#video-url-additional').css('height', 'auto');
				
			$('#url-video').focus(function() {
				$('#video-url-additional').slideDown('fast');
			});
			$('#url-video').focusout(function() {
				if ($(this).val() == '') {
					$('#video-url-additional').slideUp('fast');
				}
			});
				
			$('#video-keywords').keyup(function(event) {
				if(event.which === 188) {
					// get content from input
					updateKeywords($(this).val());
					$(this).val('');
				}
			})
				
			// add probaly missing keywords
			$('#video-keywords').focusout(function() {
				updateKeywords($(this).val());
				$(this).val('');
			})
			
			var $_GET = {};

			document.location.search.replace(/\??(?:([^=]+)=([^&]*)&?)/g, function () {
					function decode(s) {
							return decodeURIComponent(s.split("+").join(" "));
					}
			
					$_GET[decode(arguments[1])] = decode(arguments[2]);
			});
				
			// switch between video selectors and video already specified via http get
			if ((typeof($_GET['video_id']) != 'undefined') && ($_GET['video_id'] != '')) {
				var video_get = 'true';
				var selected_video = $_GET['video_id'];
			}
			else {
				var video_get = 'false';
				var selected_video = '';
			}
			
			// init cmf
			var cmfUrl = settings.cmf;
			cmfUrl = cmfUrl.replace(/\/$/, '') + '/';
			
			console.log(video_get, selected_video);
			
			$('#continue').click(function() {
				// which option has been selected?
				var add_video = false;
				
				console.log('video 1: ' + selected_video);
				
				console.log('values: ' + $('#url-video').val() + ', ' + $('#all-videos option:selected').val() + ', ' + typeof($('#annotated-videos option:selected').val()));
				
				if (($('#url-video').val() != '') && ($('#url-video').val() != undefined)) {
					selected_video = $('#url-video').val();
					add_video = true;
				}
				if (($('#all-videos option:selected').val() != '') && ($('#all-videos option:selected').val()) != undefined) {
					selected_video = $('#all-videos option:selected').val();
					add_video = false;
				}
				if (($('#annotated-videos option:selected').val() != '') && ($('#annotated-videos option:selected').val()) != undefined) {
					selected_video = $('#annotated-videos option:selected').val();
					add_video = false;
				}
				
				console.log('video 2: ' + selected_video);
				
				if ((selected_video == '') || (typeof(selected_video) == 'undefined')) {
					console.error("No video loaded!");
					alert(language.strings.OPEN_VIDEO_ERROR_1);
				}
				
				if ((selected_video != '') && (typeof(selected_video) != 'undefined')) {
					
					// set video cookie and go back to annotator main page
					function doneLoading(err, res) {
						if(err){
							console.error("Error loading annotated video locators", err);
							alert("Error loading annotated video locators: " + err);
							return;
							}
						// create json for cookie and set it
						var video_json = '{"id": "' + (selected_video).replace(/\/$/, '') + '", "locator": [';
						$.each(res, function(key, val) {
							val.type = '';
							var video_type = ((val.source).replace(/\/$/, '')).split('.');
							if (video_type.length > 0) {
								val.type = video_type[(video_type.length - 1)];
							}
							video_json += '{"source": "' + (val.source).replace(/\/$/, '') + '", "type": "video/' + val.type + '"}';
							if ((key + 1) < res.length) {
								video_json += ', ';
							}
						})
						video_json += ']}';
						console.log(video_json);
						$.cookie('video_source', null, { expires: -7, path: '/'});
						$.cookie('video_source', video_json, { expires: 7, path: '/'});
						// show loading indicator
						$('#load-container').empty();
						$('#load-container').append('<div id="video-loading-indicator"><span id="indicator-text">' + language.strings.ANNOTATIONS_LOADING + '</span><br /><img src="assets/img/ajax-loader-bar.gif" alt="Loading..." class="loading-indicator" /></div>');
								
						// load existing annotations
						
						// load annotation resource loader
						methods.loadVideoFromCmf((selected_video).replace(/\/$/, ''));
						
						// window.location.href = settings.annotator_path;
					}
					
					function addURL(url) {
						// use webservice to add the video
						var addQuery = settings.cmf + 'video/resource/url';
						// is specified video url responding?
						var keyword_str = '';
						$.each(keywords, function(key, val) {
							if (keyword_str == '') {
								keyword_str += val
							}
							else {
								keyword_str += ',' + val
							}
						})
						$.ajax({
							url: addQuery,
							type: 'GET',
							data: 'url=' + encodeURIComponent(url) + '&title=' + encodeURIComponent($('#video-title').val()) + '&keywords=' + encodeURIComponent(keyword_str) + '&description=' + encodeURIComponent($('#video-description').val()),
							error: function(){
								// url is not responding
								console.error(language.strings.OPEN_VIDEO_ERROR_2 +  '[' + url + ']');
							},
							success: function () {
								// console.log('success');
							}
						})
						.done(function (response) {
							// parse response
							console.log(response);
							var video_json = '{"id": "' + (response.url).replace(/\/$/, '') + '", "locator": [';
							$.each(response.sources, function(key, val) {
								tmp = {};
								tmp.type = '';
								tmp.source = val;
								var video_type = ((tmp.source).replace(/\/$/, '')).split('.');
								if (video_type.length > 0) {
									tmp.type = video_type[(video_type.length - 1)];
								}
								video_json += '{"source": "' + (tmp.source).replace(/\/$/, '') + '", "type": "video/' + tmp.type + '"}';
								if ((key + 1) < response.length) {
									video_json += ', ';
								}
							})
							video_json += ']}';
							console.log(video_json);
							$.cookie('video_source', null, { expires: -7, path: '/'});
							$.cookie('video_source', video_json, { expires: 7, path: '/'});
								// load existing annotations
							$('#load-container').empty();
							$('#load-container').append('<div id="video-loading-indicator"><span id="indicator-text">' + language.strings.ANNOTATIONS_LOADING + '</span><br /><img src="assets/img/ajax-loader-bar.gif" alt="Loading..." class="loading-indicator" /></div>');
								
							// load annotation resource loader
							methods.loadVideoFromCmf((response.url).replace(/\/$/, ''));
							
						})
					}
						
					// switch between adding and loading videos from the cmf
					if (add_video == false) {
						// get video locators
						this.cmf = new CMF(cmfUrl);
						console.log(selected_video);
						this.cmf.getVideoLocators(selected_video, doneLoading)
					}
					else {
						// is specified video url responding?
						$.ajax({
							url: 'core/ajax/url-check.php',
							type: 'GET',
							data: 'url=' + encodeURIComponent(selected_video),
							error: function(){
								// url is not responding
								console.error(language.strings.OPEN_VIDEO_ERROR_2 +  '[' + selected_video + ']');
								alert(language.strings.OPEN_VIDEO_ERROR_2);
							},
							success: function () {
								console.log('success');
							}
						})
						.done(function (response) {
							// show response
							console.log('done');
							console.log(response);
							if (response == '200') {
								// url is responding
								// add to cmf
								addURL(selected_video);
							}
							else {
								// url is not responding
								console.error(language.strings.OPEN_VIDEO_ERROR_2 +  '[' + selected_video + ']');
								alert(language.strings.OPEN_VIDEO_ERROR_2);
							}
						})
					}
				}
				
			})
				
			$('#back').click(function() {
				window.location.href = settings.annotator_path;
			})
			
				
			function loadVideos(cmfUrl) {
				var container = $('#videolist');
				container.html("");
				// cmfUrl = $('#cmf-url').val();
				this.cmf = new CMF(cmfUrl);
				// this.cmf.test();
				function renderVideoThumbs(list){
						var html_data = '';
					html_data += '<select name="annotated-videos" id="annotated-videos">';
					html_data += '<option value="">' + language.strings.OPEN_VIDEO_SELECT_PLACEHOLDER + '</option>';
					$.each(list, function(number, video) {
						if('title' in video) {
							html_data += '<option value="' + video.instance.value + '">' + video.title.value + '</option>';
							}
						else {
							html_data += '<option value="' + video.instance.value + '">' + video.instance.value + '</option>';
						}
					})
					html_data += '</select>';
					$('#container-annotated-videos').empty();
					$('#container-annotated-videos').append(html_data);
				}
				this.cmf.getAnnotatedVideos(function(err, res){
					if(err){
						console.error("Error loading annotated videos", err);
						alert("Error loading annotated videos: " + err);
						return;
					}
					// console.info(res);
					renderVideoThumbs(res);
				});
			}
			function loadAllVideos(cmfUrl) {
				var container = $('#videolist');
				container.html("");
				// cmfUrl = $('#cmf-url').val();
				this.cmf = new CMF(cmfUrl);
				// this.cmf.test();
				function renderVideoThumbs(list){
						var html_data = '';
					html_data += '<select name="all-videos" id="all-videos">';
					html_data += '<option value="">' + language.strings.OPEN_VIDEO_SELECT_PLACEHOLDER + '</option>';
					$.each(list, function(number, video) {
						if('title' in video) {
							html_data += '<option value="' + video.instance.value + '">' + video.title.value + '</option>';
						}
						else {
							html_data += '<option value="' + video.instance.value + '">' + video.instance.value + '</option>';
						}
 					})
					html_data += '</select>';
					$('#container-regular-videos').empty();
					$('#container-regular-videos').append(html_data);
				}
				this.cmf.getVideos(function(err, res){
					if(err){
						console.error("Error loading annotated videos", err);
						alert("Error loading annotated videos: " + err);
						return;
					}
					//console.info(res);
					renderVideoThumbs(res);
				});
			}
 			// has video already been specified via http get?
			if (video_get != 'true') {
 				// get all annotated videos from cmf
				loadVideos(cmfUrl);
	
				// get all videos from cmf
				loadAllVideos(cmfUrl);
			
			}
			else {
				// video specified => skip loading selectors
				$('#continue').click();
			}
		},	// .end _getVideoLists
		
		loadDataFromResouce: function(resource) {
			// loop through annotations
			console.log('SIZE: ' + methods.objectSize(settings.annotations.annotations));
			console.log('LOADING RESOURCES');
			if (methods.objectSize(settings.annotations.annotations) > 0) {
				// start loading data from external resource
				methods.loadData(resource, 0);
			}
			else {
				// no annotations found => nothing to load
				// set new annotation file
				// $.cookie('annotationlist', '', { expires: 7, path: '/'});
				// create an empty annotation for preview
				userAnnotations = '{"video": [{"uri": "' + settings.video + '", "width": "0", "height": "0", "player_width": "0", "player_height": "0"}], "annotations":[{"starttime": {"value": "00:01"}, "endtime": {"value": "00:05"}, "annotation": {"value": ""}, "label": {"value": "My first annotation"}, "resource": {"value": "http://dbpedia.org/resource/Annotation"}, "description": {"value": ""}, "annotationtype": {"value": "http://www.openannotation.org/ns/Annotation"}, "fragment": {"value": ""}, "relation": {"value": "http://connectme.at/ontology#showsImplicitly"}, "creator": {"value": ""}, "preferredlabel": {"value": ""}, "active" : {"value": "1"}}]}';
				// write annotations to file
				// unset outdated cookies
				$.cookie('annotationlist', '', { expires: -7, path: '/'});
				$.cookie('annotationsloaded', '', { expires: -7, path: '/'});
				methods._fileOps('write', userAnnotations, 'EMPTY', new Date().getTime());
				
			}
		}, // .end loadDataFromResouce
		
		loadData: function(resource, annotation_number) {
			// is specified annotation based on current resource?
			console.log(settings.annotations.annotations); // .resource.value);
			$('#indicator-text').empty();
			$('#indicator-text').append(language.strings.ANNOTATIONS_LOADING_FROM + ' ' + settings.resourceplugins[resource].name);
			var regExpStr = "/" + settings.resourceplugins[resource].url + "/";
			console.log(regExpStr + ': ' + settings.annotations.annotations[annotation_number].resource.value);
			if ((settings.annotations.annotations[annotation_number].resource.value).match(regExpStr)) {
				// load data from resource
				console.log(annotation_number + ' matches ' + resource);
				var parameter_string = '';
				var parameter_count = 0;
				$.each(settings.resourceplugins[resource].parameters, function(key, val) {
					parameter_string += val;
					if ((parameter_count + 1) < (settings.resourceplugins[resource].parameters).length) {
						parameter_string += ', ';
					}
					parameter_count++;
				})
				$.ajax({
					headers: {"Accept": "application/json; charset=UTF-8"},
					url: settings.resourceplugins[resource].loader,
					data:{uri: settings.annotations.annotations[annotation_number].resource.value, lang: lang_code, parameters: parameter_string},
					type: 'POST',
					timeout: 3000,
					dataType: 'json',
					error: function(e){
						console.log(e);
						if (methods.objectSize(settings.annotations.annotations) > (annotation_number + 1)) {
							// go to next annotation
							methods.loadData(resource, (annotation_number + 1));
						}
						else {
							if ((settings.resourceplugins).length > (resource + 1)) {
								// go to next resource
								console.log('go to resource nr ' + (resource + 1));
								methods.loadDataFromResouce((resource + 1));
							}
							else {
								// finish
								methods.loadingCompleted();
							}
						}
					}	
				})
				.done(function (response) {
					// data loaded => extract temporal and/or spatial information
					var extractedData = (settings.annotations.annotations[annotation_number].fragment.value).split('#');
					var temporal = [];
					var spatial = [];
					var fragmentData = [];
					if (extractedData[1] != null) {
						// more that one variable specified?
						extractedData = extractedData[1].split('&');
						$.each(extractedData, function(key, value) {
							console.log('extracted ' + value);
							// get variables and values
							var variableData = value.split('=');
							if ((variableData[0] != null) && (variableData[1] != null)) {
								console.log('key ' + variableData[0] + ', value: ' + variableData[1]);
								// add key/value pairs to fragment data array
								var contentData = variableData[1].split(':');
								if (contentData[1] != null) {
									// xywh identified
									variableData[1] = contentData[1];
								}
								contentData = variableData[1].split(',');
								var splitContent = [];
								$.each(contentData, function(key, value) {
										splitContent.push(parseInt(value));
								});
								fragmentData[variableData[0]] = splitContent;
							}
						});
					}
					console.log(fragmentData);
					console.log(settings.annotations.annotations[annotation_number].resource.value + ': ' + fragmentData['t']);
					
					/*
					var relation = '';
					if (settings.annotations.annotations[annotation_number].relation != null) {
						relation = (settings.annotations.annotations[annotation_number].relation).split('#');
						relation = relation[1];
					}
					console.log(relation);
					*/
					var json_response = response;
					// add data to annotation list
					if (json_response.response[settings.resourceplugins[resource].parameters[0]] != '') {
						var label = json_response.response[settings.resourceplugins[resource].parameters[0]];
					}
					else {
						var label = settings.annotations.annotations[annotation_number].resource.value;
					}
					// var label = json_response.response[settings.resourceplugins[resource].parameters[0]];
					var annotationtype = '';
					var preferredlabel = '';
					// search for preferred label
					if (('preferredlabel' in settings.annotations.annotations[annotation_number]) && (settings.annotations.annotations[annotation_number].preferredlabel.value != null)) {
						label = settings.annotations.annotations[annotation_number].preferredlabel.value;
					}
					if (settings.annotations.annotations[annotation_number].annotationtype.value != null) {
						annotationtype = settings.annotations.annotations[annotation_number].annotationtype.value;
					}
					var description = json_response.response[settings.resourceplugins[resource].parameters[1]];
					if (typeof(description) == 'undefined') {
						description = settings.annotations.annotations[annotation_number].resource.value;
					}
					console.info(settings.annotations.annotations[annotation_number].fragment.value);
					if (fragmentData['xywh'] != undefined) {
						// spatial region defined
						settings.annotations.annotations[annotation_number].spatial = {value: fragmentData['xywh'][0] + ',' + fragmentData['xywh'][1] + ',' + (fragmentData['xywh'][0] + fragmentData['xywh'][2]) + ',' + (fragmentData['xywh'][1] + fragmentData['xywh'][3]) + ','  + fragmentData['xywh'][2] + ','  + fragmentData['xywh'][3]};
						
						// (userAnnotations.annotations).push({'starttime': methods._convertSecondsToTime(fragmentData['t'][0]), 'endtime': methods._convertSecondsToTime(fragmentData['t'][1]), 'label': label, 'uri': settings.annotations.annotations[annotation_number].resource, 'description': json_response.response[description], 'type': relation, 'fragment': settings.annotations.annotations[annotation_number].fragment, 'annotationuri': settings.annotations.annotations[annotation_number].annotation, 'creator': settings.annotations.annotations[annotation_number].creator, 'created': settings.annotations.annotations[annotation_number].created, 'x1': fragmentData['xywh'][0], 'y1': fragmentData['xywh'][1], 'x2': fragmentData['xywh'][0], 'y2': fragmentData['xywh'][1], 'w': fragmentData['xywh'][2], 'h': fragmentData['xywh'][3], 'preferredlabel': preferredlabel, 'annotationtype': annotationtype});
					};
					settings.annotations.annotations[annotation_number].starttime = {value: methods._convertSecondsToTime(fragmentData['t'][0])};
					settings.annotations.annotations[annotation_number].endtime = {value: methods._convertSecondsToTime(fragmentData['t'][1])};
					settings.annotations.annotations[annotation_number].label = {value: label};
					settings.annotations.annotations[annotation_number].description = {value: description};
					
					console.log(settings.annotations.annotations[annotation_number]);
					
					/*
					else {
						// no spatial region defined
						(userAnnotations.annotations).push({'starttime': methods._convertSecondsToTime(fragmentData['t'][0]), 'endtime': methods._convertSecondsToTime(fragmentData['t'][1]), 'label': label, 'uri': settings.annotations.annotations[annotation_number].resource, 'description': json_response.response[description], 'type': relation, 'fragment': settings.annotations.annotations[annotation_number].fragment, 'annotationuri': settings.annotations.annotations[annotation_number].annotation, 'creator': settings.annotations.annotations[annotation_number].creator, 'created': settings.annotations.annotations[annotation_number].created, 'x1': '', 'y1': '', 'x2': '', 'y2': '', 'w': '', 'h': '', 'preferredlabel': preferredlabel, 'annotationtype': annotationtype});
					}
					*/
					if (methods.objectSize(settings.annotations.annotations) > (annotation_number + 1)) {
						// go to next annotation
						methods.loadData(resource, (annotation_number + 1));
					}
					else {
						if ((settings.resourceplugins).length > (resource + 1)) {
							// go to next resource
							console.log('go to resource nr ' + (resource + 1));
							methods.loadDataFromResouce((resource + 1));
						}
						else {
							// finish
							methods.loadingCompleted();
						}
					}
				})
			}
			else {
				// go to next annotation, go to next resource or finish
				if (methods.objectSize(settings.annotations.annotations) > (annotation_number + 1)) {
					// go to next annotation
					methods.loadData(resource, (annotation_number + 1));
				}
				else {
					if ((settings.resourceplugins).length > (resource + 1)) {
						// go to next resource
						console.log('go to resource nr ' + (resource + 1));
						methods.loadDataFromResouce((resource + 1));
					}
					else {
						// finish
						methods.loadingCompleted();
					}
				}
			}
		}, // .end loadData
		loadingCompleted: function() {
			// add annotations with unknown resources
			$('#indicator-text').empty();
			$('#indicator-text').append(language.strings.ANNOTATIONS_LOADING_FROM_UNKNOWN);
			$.each(settings.annotations.annotations, function(annotationkey, annotationvalue) {
				var already_added = false;
				$.each(settings.resourceplugins, function(pluginkey, pluginvalue) {
					var regExpStr = "/" + pluginvalue.url + "/";
					if ((annotationvalue.resource.value).match(regExpStr)) {
						already_added = true;
					}
				})
				if (already_added != true) {
					console.log('need to add ' + annotationkey);
					// annotation has to get added
					var extractedData = (annotationvalue.fragment.value).split('#');
					var temporal = [];
					var spatial = [];
					var fragmentData = [];
					if (extractedData[1] != null) {
						// more that one variable specified?
						extractedData = extractedData[1].split('&');
						$.each(extractedData, function(key, value) {
							console.log('extracted ' + value);
							// get variables and values
							var variableData = value.split('=');
							if ((variableData[0] != null) && (variableData[1] != null)) {
								console.log('key ' + variableData[0] + ', value: ' + variableData[1]);
								// add key/value pairs to fragment data array
								var contentData = variableData[1].split(':');
								if (contentData[1] != null) {
									// xywh identified
									variableData[1] = contentData[1];
								}
								contentData = variableData[1].split(',');
								var splitContent = [];
								$.each(contentData, function(key, value) {
										splitContent.push(value);
								});
								fragmentData[variableData[0]] = splitContent;
								console.log(fragmentData);
							}
						});
					}
					console.log(fragmentData);
					console.log(settings.annotations.annotations[annotationkey].resource.value + ': ' + fragmentData['t']);
					
					/*
					var relation = '';
					if (settings.annotations.annotations[annotationkey].relation.value != null) {
						relation = (settings.annotations.annotations[annotationkey].relation.value).split('#');
					}
					console.log(relation);
					*/
					// var json_response = response;
					// add data to annotation list
					var label = settings.annotations.annotations[annotationkey].resource.value;
					var annotationtype = '';
					var preferredlabel = '';
					// search for preferred label
					if (('preferredlabel' in settings.annotations.annotations[annotationkey]) && (settings.annotations.annotations[annotationkey].preferredlabel.value != null)) {
						label = settings.annotations.annotations[annotationkey].preferredlabel.value;
					}
					if (settings.annotations.annotations[annotationkey].annotationtype.value != null) {
						annotationtype = settings.annotations.annotations[annotationkey].annotationtype.value;
					}
					var description = settings.annotations.annotations[annotationkey].resource.value;
					if (fragmentData['xywh'] != undefined) {
						settings.annotations.annotations[annotationkey].spatial = {value: fragmentData['xywh'][0] + ',' + fragmentData['xywh'][1] + ',' + (fragmentData['xywh'][0] + fragmentData['xywh'][2]) + ',' + (fragmentData['xywh'][1] + fragmentData['xywh'][3]) + ','  + fragmentData['xywh'][3]};
						
						// (userAnnotations.annotations).push({'starttime': methods._convertSecondsToTime(fragmentData['t'][0]), 'endtime': methods._convertSecondsToTime(fragmentData['t'][1]), 'label': label, 'uri': settings.annotations.annotations[annotation_number].resource, 'description': json_response.response[description], 'type': relation, 'fragment': settings.annotations.annotations[annotation_number].fragment, 'annotationuri': settings.annotations.annotations[annotation_number].annotation, 'creator': settings.annotations.annotations[annotation_number].creator, 'created': settings.annotations.annotations[annotation_number].created, 'x1': fragmentData['xywh'][0], 'y1': fragmentData['xywh'][1], 'x2': fragmentData['xywh'][0], 'y2': fragmentData['xywh'][1], 'w': fragmentData['xywh'][2], 'h': fragmentData['xywh'][3], 'preferredlabel': preferredlabel, 'annotationtype': annotationtype});
					};
					settings.annotations.annotations[annotationkey].starttime = {value: methods._convertSecondsToTime(fragmentData['t'][0])};
					settings.annotations.annotations[annotationkey].endtime = {value: methods._convertSecondsToTime(fragmentData['t'][1])};
					settings.annotations.annotations[annotationkey].label = {value: label};
					settings.annotations.annotations[annotationkey].description = {value: description};
				}
			})
			
			
			// add time and spatial information to all annotations
			$.each(settings.annotations.annotations, function(annotationkey, annotationvalue) {
				console.log(typeof(settings.annotations.annotations[annotationkey].label));
				var extractedData = (annotationvalue.fragment.value).split('#');
				var temporal = [];
				var spatial = [];
				var fragmentData = [];
				if (extractedData[1] != null) {
					// more that one variable specified?
					extractedData = extractedData[1].split('&');
					$.each(extractedData, function(key, value) {
						console.log('extracted ' + value);
						// get variables and values
						var variableData = value.split('=');
						if ((variableData[0] != null) && (variableData[1] != null)) {
							console.log('key ' + variableData[0] + ', value: ' + variableData[1]);
							// add key/value pairs to fragment data array
							var contentData = variableData[1].split(':');
							if (contentData[1] != null) {
								// xywh identified
								variableData[1] = contentData[1];
							}
							contentData = variableData[1].split(',');
							var splitContent = [];
							$.each(contentData, function(key, value) {
									splitContent.push(value);
							});
							fragmentData[variableData[0]] = splitContent;
							console.log(fragmentData);
						}
					});
				}
				if (fragmentData['xywh'] != undefined) {
						settings.annotations.annotations[annotationkey].spatial = {value: fragmentData['xywh'][0] + ',' + fragmentData['xywh'][1] + ',' + (parseInt(fragmentData['xywh'][0]) + parseInt(fragmentData['xywh'][2])) + ',' + (parseInt(fragmentData['xywh'][1]) + parseInt(fragmentData['xywh'][3])) + ','  + fragmentData['xywh'][2] + ','  + fragmentData['xywh'][3]};
				}
				if (typeof(settings.annotations.annotations[annotationkey].label) == 'undefined') {
					settings.annotations.annotations[annotationkey].label = {value: settings.annotations.annotations[annotationkey].resource.value};
				}
				if (typeof(settings.annotations.annotations[annotationkey].description) == 'undefined') {
					settings.annotations.annotations[annotationkey].description = {value: settings.annotations.annotations[annotationkey].resource.value};
				}
				settings.annotations.annotations[annotationkey].starttime = {value: methods._convertSecondsToTime(fragmentData['t'][0])};
				settings.annotations.annotations[annotationkey].endtime = {value: methods._convertSecondsToTime(fragmentData['t'][1])};
			})
			
			// create json string
			var json_string = '{';
			$.each(userAnnotations, function(key, value) {
				json_string += '"' + key + '": [';
				$.each(value, function(part_key, data_part) {
					json_string += '{';
					$.each(data_part, function(datakey, datavalue) {
						console.log(typeof(datavalue));
						if (datavalue instanceof Array) {
							json_string += '"' + datakey + '": [';
							var tmp_cnt = 0;
							$.each(datavalue, function(arr_key, arr_value) {
								json_string += '"' + arr_value + '"';
								if ((tmp_cnt + 1) < datavalue.length) {
									json_string += ', ';
								}
								tmp_cnt++;
							})
							json_string += '], ';
						}
						else {
							json_string += '"' + datakey + '": "' + datavalue + '", ';
						}
					})
					if (json_string.substring((json_string.length - 2), (json_string.length)) == ', ') {
						json_string = json_string.substring(0, (json_string.length - 2));
					}
					json_string += '}, ';
				})
				if (json_string.substring((json_string.length - 2), (json_string.length)) == ', ') {
					json_string = json_string.substring(0, (json_string.length - 2));
				}
				json_string += '], ';
			})
			if (json_string.substring((json_string.length - 2), (json_string.length)) == ', ') {
				json_string = json_string.substring(0, (json_string.length - 2));
			}
			json_string += '}';
			
			// write json to annotation file
			methods._fileOps('write', JSON.stringify(settings.annotations), 'annotations_', new Date().getTime());
			
			
			console.log(json_string);
			
		},
		/**
		 *	convertSecondsToTime
		 *	Converts seconds into time (e.g. 00:00:00)
		 *
		 *	@param:	int seconds - number of seconds
		 *	@return: string timestring - generated time string
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
		}, // .end convertSecondsToTime
		/**
		 *	fileOps
		 *	Allows reading from a file and writing to one
		 *
		 *	@param: type string - function which gets used (read or write)
		 *	@param: filename string - filename for reading/writing
		 */	
		_fileOps: function(type, json_string, prefix, ts) {
			if (type != '') {
				
				if (prefix != 'EMPTY') {
					var ann_filename = prefix + ts + '.ann';
				}
				else {
					var ann_filename = 'annotations_' + ts + '.ann';
				}
				
				var http_method = 'POST';
				var query = 'core/ajax/annotation-file-functions.php';
				var post_data = {'file': ann_filename ,'data': json_string, 'type': type}
				
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
					if (prefix == 'annotations_') {
						// create backup
						methods._fileOps('write', json_string, 'backup_', ts);
					}
					else if  (prefix == 'backup_') {
						// create backup
						methods._fileOps('write', queryResult, 'cmf_', ts);
					}
					else {
						// unset outdated cookies
						$.cookie('annotationlist', '', { expires: -7, path: '/'});
						$.cookie('annotationsloaded', '', { expires: -7, path: '/'});
						// set annotation file cookie
						$.cookie('annotationlist', 'annotations_' + ts + '.ann', { expires: 7, path: '/'});
						if (prefix != 'EMPTY') {
							// set annotation loaded indicator
							$.cookie('annotationsloaded', 'true', { expires: 7, path: '/'});
						}
						
						// add loaded video info to log file
						$.ajax({
							url: 'core/ajax/to-log.php',
							type: http_method,
							data: {'data': 'Video <' + settings.video + '> loaded by ' + settings.userid},
							async: false,
							cache: false,
							timeout: 3000,
							error: function(){
								return true;
							}
						})
						.done(function (response) {
							// redirect to annotator main page
							window.location.href = settings.annotator_path;
						})
					}
				})
			}
		},
		objectSize: function(obj) {
			var size = 0, key;
			for (key in obj) {
				if (obj.hasOwnProperty(key)) size++;
			}
			return size;
		}
		
	} // .end methods
	
	/**
	 *	$.fn.annotator
	 *	Main function of the hypervideo annotation suite which handles the method selection
	 */ 
	$.fn.annotatorload = function(method) {
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