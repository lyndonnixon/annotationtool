<?php
/**
 * DBPedia data lookup
 *
 * Searches for content of a specified DBPedia URI and returns the content. Parameters specify the response which shall get returned (e.g. http://www.w3.org/2000/01/rdf-schema#label)
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
$dbpedia_core = new Core();
 
header('Content-type: application/json');
if (!isset($_POST['uri'])) {
	print '{"response": "No resource URI specified!"}';
}
else if (!isset($_POST['parameters'])) {
	print '{"response": "No response parameters specified!"}';
}
else {
	
	$resource_uri = $_POST['uri'];
	if (isset($_POST['lang'])) {
		$language = $_POST['lang'];
	}
	else {
		$language = $dbpedia_core->settings->default_lang;
	}
	// print $dbpedia_core->settings->default_lang;
	$parameters = explode(',', urldecode($_POST['parameters']));
	
	// split resource uri and generate data uri
	/*** DEACTIVATED: Query dbpedia live site
	$data_uri = explode('/', $resource_uri);
	if ($data_uri[(count($data_uri) - 1)] == '') {
		print '{"response": "No data found for URI [' .$data_uri. ']"}';
	}
	else {
	***/
	if ($resource_uri == '') {
		print '{"response": "No data found for URI [' .$resource_uri. ']"}';
	}
	else {
		
		// using salzburg reseach dbpedia dump
		$data_uri = $dbpedia_core->settings->resourceplugins[0]['browse_source'].urlencode($resource_uri);
	  
		$req = new HTTP_Request($data_uri);
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
	  
		$response = $req->sendRequest();
	
		if (PEAR::isError($response)) {
			print $response->getMessage();
		}
		else {
			if ($req->getResponseCode() != 200) {
				// no error => return response header for further investigation
				print '{"response": "ERROR: URL did not respond! [' .$data_uri. ']"}';
			}
			else {
				// parse response for specified values
				$json_obj = json_decode($req->getResponseBody());
				// var_dump($json_obj);
				$data_found = false;
				$response_json = '';
				$response_json .= '{';
				/***** DEACTIVATED: DBPedia live site search
				foreach ($parameters as $parameter) {
					$tmp = trim(rtrim($parameter));
					$param_found = false;
					if (isset($json_obj->$resource_uri->$tmp) && is_array($json_obj->$resource_uri->$tmp)) {
						foreach ($json_obj->$resource_uri->$tmp as $key => $res) {
							if (isset($res->lang)) {
								if ($res->lang == $language) {
									// language specified and correct one selected
									$response_json .= '"'. $tmp .'": "' .str_replace('"', "'", $res->value). '", ';
									$data_found = true;
									$param_found = true;
								}
							}
							else if (!isset($res->lang)) {
								// get value, no language selection available
								$response_json .= '"'. $tmp .'": "' .str_replace('"', "'", $res->value). '", ';
								$data_found = true;
								$param_found = true;
								
							}
						}
					}
					if ($param_found != true) {
						$response_json .= '"'. $tmp .'": "", ';
						$data_found = true;
					}
					unset($tmp);
				}
				**/
				foreach ($parameters as $parameter) {
					$tmp = trim(rtrim($parameter));
					$param_found = false;
					if (isset($json_obj->representation->$tmp) && is_array($json_obj->representation->$tmp)) {
						foreach ($json_obj->representation->$tmp as $key => $res) {
							$tmp_lang = 'xml:lang';
							// print "LANG: " .$res->$tmp_lang;
							if (isset($res->$tmp_lang)) {
								if ($res->$tmp_lang == $language) {
									// language specified and correct one selected
									$response_json .= '"'. $tmp .'": "' .str_replace('"', "'", $res->value). '", ';
									$data_found = true;
									$param_found = true;
								}
							}
							else if (!isset($res->$tmp_lang)) {
								// get value, no language selection available
								$response_json .= '"'. $tmp .'": "' .str_replace('"', "'", $res->value). '", ';
								$data_found = true;
								$param_found = true;
								
							}
						}
					}
					if ($param_found != true) {
						$response_json .= '"'. $tmp .'": "", ';
						$data_found = true;
					}
					unset($tmp);
				}
				if ($data_found == true) {
					$response_json = substr($response_json, 0, (strlen($response_json) - 2));
				}
				$response_json .= '}';
				print '{"response": ' .$response_json. '}';
			}
		}
	}
}
?>