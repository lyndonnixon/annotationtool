<?php
/**
 * Geonames Search
 *
 * Searches at Geonames for a given query. Creates JSON string and prints it. Preview of found place by using Google Maps Image.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core/Plugins/Geonames
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */

// define root
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(dirname(dirname(__FILE__))));
}

/**
 * Get main class of annotation tool.
 * All external libraries are referenced in this class.
 */
require_once(__ROOT__. '/core/class/core.class.php');

// start session
session_start();

// init classes
$core = new Core();

// plugin settings
include('settings.inc.php');
 
 
if (isset($_POST['query']) && ($_POST['query'] != '')) {
  // search for results at Geonames
	$preferred_language = 'en';
	if (isset($_COOKIE['lang'])) {
		if (in_array($_COOKIE['lang'], $core->settings->available_languages)) {
			$preferred_language = $_COOKIE['lang'];
		}
	}
	$geonames['parameters']['lang'] = $preferred_language;
	
	$request_url = $geonames['search_url'];
	$cnt = 0;
	foreach ($geonames['parameters'] as $key => $value) {
		if ($cnt == 0) {
			$request_url .= '?';
		}
		else {
			$request_url .= '&';
		}
		$request_url .= $key. '=' .$value;
		$cnt++;
	}
	
	$request_url .= urlencode($_POST['query']);
	
  $req = new HTTP_Request($request_url);
  $req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addHeader("Accept", "application/json");
  
  $response = $req->sendRequest();

  if (PEAR::isError($response)) {
    print $response->getMessage();
  } else {

		if ($req->getResponseCode() != 200) {
			// no error => return response header for further investigation
			$result_select	= '{"results":[{"label": "' .$core->language->strings->RESULTS_EMPTY. ' ' .$_POST['query']. '", "uri":"", "description": "' .$core->language->strings->RESULTS_EMPTY. ' <strong>' .$_POST['query']. '</strong>"}]}';
			print $result_select;
		}
		else {
			$query_result = json_decode($req->getResponseBody());
			print '{"results":[';
			$num_res = count($query_result->geonames);
			$counter = 0;
			foreach ($query_result->geonames as $result) {
				// generate google map for result
				$result->map = "<img src='".$gmapsimg["url"]. "?center=" .$result->lat. ",".$result->lng. "&zoom=" .$gmapsimg["zoom"]. "&size=" .$gmapsimg["width"]. "x" .$gmapsimg["height"]. "&maptype=roadmap&markers=".urlencode("color:red|label:" .$result->name. "|" .$result->lat. ",".$result->lng). "&sensor=false' alt='" .$result->name. "' />";
				print '{"label": "' .$result->name. '", "uri":"'.$geonames['resource_url'].$result->geonameId. '/", "description": "' .$result->map. '"}';
				if (($counter + 1) < $num_res) {
					print ', ';
				}
				$counter++;
			}
			print ']}';
		}
	}
}

?>