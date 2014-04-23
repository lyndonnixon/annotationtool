/**
 * ConnectME annotation tool - jQuery extension for administering the tool
 *
 * This jQuery extension loads annotation tool administration file and creates HTML out of it. It allows adding and removing administrators
 * and adding and removing CMF instances from the configuration. Furthermore, the active CMF instance can get selected.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.4
 * @package Core
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
$(document).ready(function() {
	// window.setInterval(function () { fileOps('write') }, 1000);
	
	// current active admin
	var active_admin = $('#active_admin').val();
	
	// remove admin
	$('.remove-admin').click(function () {
		// remove selected entry
		// get entry id
		var entry_id = $('.entry-id', $(this).parent()).val();
		// disallow removing active administrator
		if ($('#admin-' + entry_id).val() == active_admin) {
			alert("You can't remove yourself");
		}
		else {
			$(this).parent().remove();
		}
	});
	
	// remove lmdb
	$('.remove-lmdb').click(function () {
		// remove selected entry
		// get entry id
		var entry_id = $('.entry-id', $(this).parent()).val();
		// disallow removing active administrator
		console.log('Children ' + $('#lmdb-list').children().length);
		if ($('#lmdb-list').children().length <= 2) {
			alert("You need at least one LMDB source");
		}
		else {
			$('#lmdb-' + entry_id).parent().remove();
		}
	});
	
	// add administrator
	$('#add-admin').click(function() {
		var entries = parseInt($('#admin-list').children().length) - 1;
		var new_id = parseInt($('#admin-list li:nth-child(' + entries + ') .entry-id').val()) + 1;
		var new_item = '<li><label for="admin-' + new_id + '">Admin ' + (new_id + 1) + ' Open ID</label><input name="admin-' + new_id + '" id="admin-' + new_id + '" type="text" value="" /><input type="hidden" class="entry-id" name="entry-id" value="' + new_id + '" /><button name="remove-admin-' + new_id + '" id="remove-admin-' + new_id + '" type="button" class="btn btn-danger remove-admin" title="Remove Admin ' + new_id + '"> - </button></li>';
		
		$('#admin-list li.add-button').before(new_item);
		
		$('.remove-admin').unbind('click');
		
		$('.remove-admin').bind('click', function () {
			// remove selected entry
			// get entry id
			var entry_id = $('.entry-id', $(this).parent()).val();
			// disallow removing active administrator
			if ($('#admin-' + entry_id).val() == active_admin) {
				alert("You can't remove yourself");
			}
			else {
				$(this).parent().remove();
			}
		});
		
	});
	
	// add lmdb source
	$('#add-lmdb').click(function() {
		var entries = parseInt($('#lmdb-list').children().length) - 1;
		var new_id = parseInt($('#lmdb-list li:nth-child(' + entries + ') ul.lmdb-detail li .entry-id').val()) + 1;
		var new_item = 	'<li>' + 
										'  <label for="lmdb-' + new_id + '">LMDB ' + (new_id + 1) + '</label>' + 
										'  <ul class="lmdb-detail" id="lmdb-' + new_id + '">' + 
										'    <li>' + 
										'      <label for="lmdb-' + new_id + '-name">name</label><input name="lmdb-' + new_id + '-name" type="text" value="" />' + 
										'    </li>' +
										'    <li>' + 
										'      <label for="lmdb-' + new_id + '-username">username</label><input name="lmdb-' + new_id + '-username" type="text" value="" />' + 
										'    </li>' + 
										'    <li>' + 
										'      <label for="lmdb-' + new_id + '-password">password</label><input name="lmdb-' + new_id + '-password" type="text" value="" />' + 
										'    </li>' + 
										'    <li>' + 
										'      <label for="lmdb-' + new_id + '-url">url</label><input name="lmdb-' + new_id + '-url" type="text" value="" />' + 
										'    </li>' +
										'    <li>' + 
										'      <label for="lmdb-' + new_id + '-selected">selected</label><input name="lmdb-' + new_id + '-selected" type="checkbox" value="" />' + 
										'    </li>' +
										'    <li>' + 
										'      <label for="remove-lmdb-' + new_id + '"></label><input type="hidden" class="entry-id" name="entry-id" value="' + new_id + '" /><button name="remove-lmdb-' + new_id + '" id="remove-lmdb-' + new_id + '" type="button" title="Remove LMDB ' + new_id + '" class="btn btn-danger remove-lmdb"> - </button>' +
										'    </li>' + 
										'  </ul>' + 
										'</li>';
		
		$('#lmdb-list li.add-button').before(new_item);
		
		$('.remove-lmdb').unbind('click');
		
		$('.remove-lmdb').bind('click', function () {
			// remove selected entry
			// get entry id
			var entry_id = $('.entry-id', $(this).parent()).val();
			// disallow removing active administrator
			console.log('Children ' + $('#lmdb-list').children().length);
			if ($('#lmdb-list').children().length <= 2) {
				alert("You need at least one LMDB source");
			}
			else {
				$('#lmdb-' + entry_id).parent().remove();
			}
		});
		
	});
	
	
});

function fileOps(nothing) {
		currTime = new Date();
		console.info('Executed ' + currTime.toGMTString());
}