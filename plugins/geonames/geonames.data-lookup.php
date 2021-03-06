<?php
/**
 * Geonames data lookup
 *
 * Generates preview for a specified geonames URI by using Google Maps Image API.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core 
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

header('Content-type: application/json');
if (!isset($_POST['uri'])) {
	print '{"response": "No resource URI specified!"}';
}
else if (!isset($_POST['parameters'])) {
	print '{"response": "No response parameters specified!"}';
}
else {
	
	$parameters = explode(',', urldecode($_POST['parameters']));
	
	$resource_uri = urldecode($_POST['uri']);
	
	// append rdf suffix to get data in rdf/xml form
	if (substr($resource_uri, (strlen($resource_uri) - 1), 1) != '/') {
		// append trailing slash
		$resource_uri .= '/';
	}
	$resource_uri .= 'about.rdf';
	  
	$req = new HTTP_Request($resource_uri);
	$req->setMethod(HTTP_REQUEST_METHOD_GET);
	$req->_timeout = 20;
	  
	$response = $req->sendRequest();
	
	if (PEAR::isError($response)) {
		print '{"response": "' .$response->getMessage(). '"}';
	}
	else {
		if ($req->getResponseCode() != 200) {
			// no error => return response header for further investigation
			print '{"response": "ERROR: URL did not respond! [' .$resource_uri. '], [HTTP ' .$req->getResponseCode(). ']"}';
		}
		else {
			error_reporting(0);
			// parse response for specified values
			$body = $req->getResponseBody();
			$pattern = '/<(\/)?([a-z0-9_-]+):([a-z0-9_-]+)/i';
			$body = preg_replace($pattern, '<$1$3', $body);
			$json_obj = simplexml_load_string($body);
			// $json_string = stripslashes(json_encode($json_obj));
			// extract required values
			$tmp_obj = $json_obj->Feature;
			$json_string = '{';
			$param_num = 0;
			foreach ($parameters as $parameter) {
				$parameter = trim(rtrim($parameter));
				if ($parameter == 'map') {
					foreach($json_obj->Feature as $geonames_obj) {
						foreach ($geonames_obj as $key => $val) {
							if ($key == 'long') {
								$long = $val;
							}
							else if ($key == 'lat') {
								$lat = $val;
							}
						}
						// generate map code for content preview
						$map = "<img src='http://maps.googleapis.com/maps/api/staticmap?zoom=8&size=455x155&sensor=false&maptype=roadmap&markers=size:big%7Ccolor:red%7C".$lat.",".$long."&center=".$lat.",".$long."' alt=''/>";
						$json_string .= '"map": "' .$map. '"';
					}
				}
				else {
					foreach($json_obj->Feature as $geonames_obj) {
						foreach ($geonames_obj as $key => $val) {
							if ($parameter == $key) {
								$json_string .= '"' .$key. '": "' .$val. '", ';
							}
						}
					}
				}
				$param_num++;
				if (($param_num + 1) < count($parameters)) {
					$json_string .= ', ';
				}
			}
			$json_string .= '}';
			// var_dump($json_obj);
			$data_found = false;
			print '{"response": ' .$json_string. '}';
		}
	}
}
?>