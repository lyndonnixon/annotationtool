<?php
/**
 * Google Maps search
 *
 * Allows serching via Google Maps API for locations
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
  // search for results at DBpedia
  
	$request_url = $core->settings->interactive_gmaps['search_url'];
	$cnt = 0;
	foreach ($core->settings->interactive_gmaps['parameters'] as $key => $value) {
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
			$result_select	= '{"results":[{"label": "' .$core->strings->RESULTS_EMPTY. ' ' .$_POST['query']. '", "uri":"", "description": "' .$core->strings->RESULTS_EMPTY. ' <strong>' .$_POST['query']. '</strong>"}]}';
			print $result_select;
		}
		else {
			$query_result = json_decode($req->getResponseBody());
			if (is_object($query_result)) {
				if ($query_result->status == 'OK') {
					$arr_json = array();
					foreach ($query_result->results as $result) {
						$tmp_res->label = $result->formatted_address;
						$tmp_res->description = "<iframe src='http://".$core->settings->base_url."plugins/maps_search/show_map.php?lng=" .$result->geometry->location->lng. "&lat=" .$result->geometry->location->lat. "' width='" .$gmapsimg["width"]. "' height='" .$gmapsimg["height"]. "' scrolling='no' frameborder='0'></iframe>";
						$tmp_res->uri = 'http://'.$core->settings->base_url.'plugins/maps_search/show_map.php?lng=' .$result->geometry->location->lng. '&lat=' .$result->geometry->location->lat;
						foreach ($result->address_components as $component) {
							if ($component->types[0] == 'route') {
								$tmp_res->route = $component->long_name;
							}
							else if ($component->types[0] == 'street_number') {
								$tmp_res->street_number = $component->long_name;
							}
							else if ($component->types[0] == 'locality') {
								$tmp_res->locality = $component->long_name;
							}
							else if ($component->types[0] == 'postal_code') {
								$tmp_res->postal_code = $component->long_name;
							}
							else if ($component->types[0] == 'country') {
								$tmp_res->country = $component->long_name;
							}
						}
						$tmp_res->lng = $result->geometry->location->lng;
						$tmp_res->lat = $result->geometry->location->lat;
						$arr_json[] = $tmp_res;
						unset($tmp_res);
					}
					$obj_json->results = $arr_json;
					print json_encode($obj_json);
				}
			}
		}
	}
}

?>