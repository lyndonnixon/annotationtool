$(document).ready(function() {
  
  	/* Main menu */
  	$('.menu li ul li').slideToggle(1);
  	// droplinemenu.buildmenu("main-menu");
  	$('.menu li').hover(function() {
	  	$('ul', this).css('left', '0px');
	  	$('li', this).stop().height('auto').slideToggle(300);
  	},
  	function() {
	  	$('li', this).stop().slideToggle(300, function() {
		  // $('ul', this).css('left', '-9999px');
	  	});
  	});
  
  	/* Error container */
  	$('#error-container').css('width', window.outerWidth);
 	 $('#error-container').css('height', window.innerHeight);
  	$('#error-message-container').css('top', parseInt((window.innerHeight - $('#error-message-container').innerHeight())/2));
  	$('#error-ok').click(function () {
    	location.reload();
  	});
  
  	$('#search-areas').zAccordion({
	  	auto: false,
	  	slideWidth: 660,
	  	width: 720,
	  	height: 186,
	  	startingSlide: 2
  	});
  
  	/* Development helper */
 	$("#development-helper").draggable();
	
	$("#minimizer").click(function() {
		if ($('#minimizer').hasClass('opened')) {
			$('#minimizer').removeClass('opened');
			$('#minimizer').addClass('closed');
			$('#minimizer a img').attr('src', 'images/icon-maximize.png');
			$('#development-helper .headline').css('color', '#434343');
			$('#development-helper').animate({height: '25px', width: '22px'}, 500);
		}
		else if ($('#minimizer').hasClass('closed')) {
			$('#minimizer').addClass('opened');
			$('#minimizer').removeClass('closed');
			$('#minimizer a img').attr('src', 'images/icon-minimize.png');
			$('#development-helper .headline').css('color', '#ffffff');
			$('#development-helper').animate({height: '330px', width: '850px'}, 500);
		}
	});
	
	/* scroll pane areas */
	$('.scroll-pane').jScrollPane({
	  autoReinitialise: true
	});
	
	var tmpjsonstr	= '{"annotations":[]}';
	
	var annotation_list = jQuery.parseJSON(tmpjsonstr);
	$.each(annotation_list.annotations, function (i, annotation) {
		alert('in');
	})
	
	// sample video files
	var sample_videos = new Array('http://upload.wikimedia.org/wikipedia/commons/7/79/Big_Buck_Bunny_small.ogv',
	'http://video-js.zencoder.com/oceans-clip.ogv',
	'http://video-js.zencoder.com/oceans-clip.mp4',
    'http://video-js.zencoder.com/oceans-clip.webm',
	'http://devserver.sti2.org/connectme/tmp_video/6256_519_connectme_volksbuehne_heldenmp4_standardmpeg_20120427015958_standard.mp4',
	'https://s3-eu-west-1.amazonaws.com/yoo.120/connectme/6306_519_20120508125738_standard.mp4');
	$('.sample-video').click(function() {
		var video_num = ($(this).attr('id').substring(($(this).attr('id').length) - 1, ($(this).attr('id').length)) - 1);
		$('#video-input-url').val(sample_videos[video_num]);
		$('#video-open').click();
	});
	
	// show lmf url if already set
	if ($.cookie('lmdb_tools_src') != '') {
		$('#lmdb-tool-source').val($.cookie('lmdb_tools_src'));
	}
	
	// tools - lmdb settings
	$('#save-lmdb').click(function() {
		alert($('#lmdb-tool-source').val());
		$.cookie('lmdb_tools_src', $('#lmdb-tool-source').val(), { expires: 7});
		$('#lmdb-tool-source').val($.cookie('lmdb_tools_src'));
	});
	
	// tools - reload annotations
	$('#reload-annotations').click(function() {
		$.cookie('annotations_loaded', '', { expires: -7, path: '/'});
		$.cookie('annotationlist', '', { expires: -7, path: '/'});
		$.cookie('annotationbackup', '', { expires: -7, path: '/'});
		location.reload();
	});
	
	
	
	// get all rules from lmf
	$('#rules-show').click(function() {
		$.ajax({
			url: $.cookie('lmdb_tools_src') + 'reasoner/program/list',
			type: 'GET',
			async: false,
			cache: false,
			timeout: 30000,
			error: function(){
				alert('ERROR');
				return true;
			}
		})
		.done(function (data) {
			$('#rules-result').empty();
			$('#rules-result').append(data);
		})
	});
	// open popup window of lmf sparql ui
	$('#query-execute').click(function() {
		if ($.cookie('lmdb_tools_src') != '') {
			window.open($.cookie('lmdb_tools_src') + 'sparql/admin/snorql/snorql.html');
		}
	});
	
	// rules add/remove scroll down menu
	$('.actions .rules-input').slideToggle(1);
  	// droplinemenu.buildmenu("main-menu");
  	$('.actions li').hover(function() {
	  	$('div.rules-input', this).stop().height('auto').slideToggle(300);
  	},
  	function() {
	  	$('div.rules-input', this).stop().slideToggle(300, function() {
		  // $('ul', this).css('left', '-9999px');
	  	});
  	});
	
	// add rule to lmf
	$('#save-rule').click(function() {
		if (($.cookie('lmdb_tools_src') != '') && ($('#rule-name').val() != '') && ($('#rule-add').val() != '')) {
			$('#rules-result').empty();
			$.ajax({
				url: $.cookie('lmdb_tools_src') + 'reasoner/program/' + $('#rule-name').val(),
				type: 'POST',
				contentType: 'text/plain',
				data: $('#rule-add').val(), 
				async: false,
				cache: false,
				timeout: 30000,
				error: function(){
					return true;
				}
			})
			.statusText(function(data) {
				$('#rules-result').empty();
				$('#rules-result').append(data)
			})
			.done(function (data) {
				$('#rules-result').empty();
				$('#rules-result').append(data);
			})
		}
		else {
			$('#rules-result').append('<p>You have to specify a linked media database, a rule name and rule content to be able to add!</p>');
		}
	});
	
	// remove rule from lmf
	$('#remove-rule').click(function() {
		if (($.cookie('lmdb_tools_src') != '') && ($('#rule-remove').val() != '')) {
			$('#rules-result').empty();
			$.ajax({
				url: $.cookie('lmdb_tools_src') + 'reasoner/program/' + $('#rule-remove').val(),
				type: 'DELETE',
				contentType: 'text/plain',
				async: false,
				cache: false,
				timeout: 30000,
				error: function(){
					return true;
				}
			})
			.statusCode(function(data) {
				$('#rules-result').empty();
				$('#rules-result').append(data)
			})
			.done(function (data) {
				$('#rules-result').empty();
				$('#rules-result').append(data);
			})
		}
		else {
			$('#rules-result').append('<p>You have to specify a linked media database and a rule name to be able to remove!</p>');
		}
	});
});