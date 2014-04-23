<?php
/**
 * DBPedia Search
 *
 * Searches in DBPedia for a given string and returns found content as JSON object.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core/Plugins/DBPedia
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
 
if (isset($_POST['query']) && ($_POST['query'] != '')) {
  // search for results at DBpedia
	$preferred_language = 'en';
	if (isset($_COOKIE['lang'])) {
		if (in_array($_COOKIE['lang'], $core->settings->available_languages)) {
			$preferred_language = $_COOKIE['lang'];
		}
	}
	
  $req = new HTTP_Request($core->settings->resourceplugins[0]['search_source'].urlencode($_POST['query']).'&ldpath='. urlencode('name=rdfs:label[@' .$preferred_language. ']::xsd:string;comment=rdfs:comment[@' .$preferred_language. ']::xsd:string;'));
  $req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addHeader("Accept", "application/json");
  
  $response = $req->sendRequest();

  if (PEAR::isError($response)) {
    print $response->getMessage();
  } else {

    // parse result xml and extract neccessary values
	
	// ----- Development helper -------
	// header('Content-type: text/xml');
    // print $req->getResponseBody();
	// ----- Development helper -------
	
	// var_dump($req->getResponseBody());
	
	// Label, URI, Description
	
	if ($req->getResponseCode() != 200) {
		// no error => return response header for further investigation
		$result_select	= '{"results":[{"label": "' .$core->language->strings->RESULTS_EMPTY. '", "uri":"", "description": "' .$core->language->strings->RESULTS_EMPTY. '"}]}';
	  print $result_select;
	}
	else {
		
		$empty = false;
		
		$query_result = json_decode($req->getResponseBody());
		
		// print $req->getResponseBody();
		
		$result_json	= '{"results":[';
		$result_select	= '<ul id="search-results" name="search-results" size="11">';
		
		if (is_object($query_result)) {
			$result_container = array();
			if (count($query_result->results) > 0) {
				// parse results by preferred language
				foreach ($query_result->results as $result) {
					$tmp_arr = array();
					if (isset($result->id)) {
						$tmp_arr['uri'] = $result->id;
					}
					if (isset($result->name)) {
						$tmp_arr['label'] = $result->name[0]->value;
					}
					if (isset($result->comment)) {
						$tmp_arr['description'] = $result->comment[0]->value;
					}
					else {
						$tmp_arr['description'] = '';
					}
					if (isset($tmp_arr['uri']) && isset($tmp_arr['label'])) {
						$result_container[] = $tmp_arr;
					}
				}
			}
			else {
				$result_select	.= '<li>Sorry, nothing found</li>';
				$result_json	.= '{"label": "' .$core->language->strings->RESULTS_EMPTY. '", "uri":"", "description": "' .$core->language->strings->RESULTS_EMPTY. '"}';
				$empty = true;
			}	
			
			foreach ($result_container as $res) {
				$result_select	.= '<li class="result"><ul class="content"><li class="value">'.$res['uri'].'</li><li class="label">'.$res['label'].'</li></ul></li>';
				$result_json 		.= '{"label":"'.str_replace('"', "'", $res['label']).'","uri":"'.$res['uri'].'","description":"'.str_replace('"', "'", $res['description']).'"},';
			}
			
		}
		
		else {
			$result_select	.= '<li>Sorry, nothing found</li>';
			$result_json	.= '{"label": "' .$core->language->strings->RESULTS_EMPTY. '", "uri":"", "description": "' .$core->language->strings->RESULTS_EMPTY. '"}';
			$empty = true;
		}
		
		$result_select .= '</ul>';
		if ($empty != true) {
			$result_json	= substr($result_json, 0, strlen($result_json) - 1);
		}
		$result_json	.= ']}';
		
		// print $result_select;
		print $result_json;
	
	}
	
  }
  
}
else {
	$result_select	= '{"results":[{"label": "' .$core->language->strings->RESULTS_EMPTY. '", "uri":"", "description": "' .$core->language->strings->RESULTS_EMPTY. '"}]}';
	print $result_select;
}

?>