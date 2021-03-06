<?php
/**
 * URL Check
 *
 * Tries to get header from specified URL to see if this URL is responding. Used by JS/jQuery via AJAX.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core/Ajax
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */

if (isset($_GET['url']) && ($_GET['url'] != '')) {
	
	error_reporting(0);

	$input_url = urldecode($_GET['url']);
	$input_url = trim($input_url);
	$input_url = ltrim($input_url);
	
	// Check if the URL responding
	$response = get_headers($input_url, 1);
	// check if the URL responding
	if (is_array($response)) {
		// no error => url is working
		if (eregi('200', $response['0'])) {
			// url is responding
			print '200';
		}
		else {
			// return error
			print '404';
		}
	}
	else {
		// return error
		print '404';
	}
}
else {
	// return error
	print '404';
}

?>