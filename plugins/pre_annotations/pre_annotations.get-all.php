<?php

/**
 * Get all pre annotations
 *
 * Tries to get all pre-annotations from pre-annotation file
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core/Plugins/Pre_Annotations
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
$pre_ann_core = new Core();
 
	// include configurable settings file (admins, cmf sources, ...)
	if (file_exists(__ROOT__. '/core/includes/administration.inc.php')) {
		include_once(__ROOT__. '/core/includes/administration.inc.php');
	}
		
	$preferred_language = 'en';
	if (isset($_COOKIE['lang'])) {
		if (in_array($_COOKIE['lang'], $pre_ann_core->settings->available_languages)) {
			$preferred_language = $_COOKIE['lang'];
		}
	}
	
	// get video source
	$video_data = $core->video;
	
	$base_mod = 'http://' .$pre_ann_core->settings->base_url;
	
	$filename = $video_data['id'];
	$pattern = '/[\s\W]+/';
	$filename = preg_replace($pattern, '', $filename).'.pre';
	
	$request_url = $base_mod.'plugins/pre_annotations/pre_annotation_data/'.$filename;
	
  $req = new HTTP_Request($request_url);
  $req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->addHeader("Accept", "application/json");
  
  $response = $req->sendRequest();

  if (PEAR::isError($response)) {
    print $response->getMessage();
  } else {

		if ($req->getResponseCode() != 200) {
			// no error => return response header for further investigation
			$result_select	= '{"results":[{"label": "' .$core->language->strings->RESULTS_EMPTY. '", "uri":"", "description": "' .$core->language->strings->RESULTS_EMPTY. '"}]}';
			print $result_select;
		}
		else {
			$query_result = json_decode($req->getResponseBody());
			$print_result = '{"results":[';
			$counter = 0;
			foreach ($query_result as $result) {
				
				// get file content to show pre-annotations
				$label = 'http://www.w3.org/2000/01/rdf-schema#label';
				$print_result .= '{"label": "';
				if ($result->response->$label != '') {
					$print_result .= $result->response->$label;
				}
				else if (isset($result->response->name) && ($result->response->name != '')) {
					$print_result .= $result->response->name;
				}
				$print_result .= '", "uri":"' .$result->uri. '", "description": "';
				$description = 'http://dbpedia.org/ontology/abstract';
				if ($result->response->$description != '') {
					$print_result .= $result->response->$description;
				}
				else if (isset($result->response->map) && ($result->response->map != '')) {
				$print_result .= $result->response->map;
				}
				$print_result .= '"}';
				$print_result .= ', ';
			}
			$counter++;
				
			if (substr($print_result, (strlen($print_result) - 2), 1) == ',') {
				$print_result = substr($print_result, 0, (strlen($print_result) - 2));
			}
			$print_result .= ']}';
			print $print_result;
		}
	}

?>