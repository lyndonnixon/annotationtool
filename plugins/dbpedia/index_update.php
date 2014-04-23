<?php
/**	DBPedia resource extraction
 *	2012-11-23
 */
 
define('__ROOT__', dirname(dirname(dirname(__FILE__))));
require_once(__ROOT__. '/libraries/HTTP_Request/Request.php');
require_once(__ROOT__. '/core/class/core.class.php');
require_once('includes/settings.inc.php');
$core = new Core();
$plugin_settings = new DBPedia_settings();
 
if (isset($_POST['query']) && ($_POST['query'] != '')) {
  // search for results at DBpedia
	
	$preferred_language = $core->settings->default_language;
	if (isset($_COOKIE['lang'])) {
		if (in_array($_COOKIE['lang'], $core->settings->available_languages)) {
			$preferred_language = $_COOKIE['lang'];
		}
	}
	$plugin_settings->preferred_language = $preferred_language;
	
	// add search query to parameters
	$plugin_settings->search['parameters']['name'] = $_POST['query'];
  $req = new HTTP_Request($plugin_settings->search['url']);
  $req->setMethod(HTTP_REQUEST_METHOD_POST);
	foreach ($plugin_settings->search['parameters'] as $key => $val) {
		if (isset($preferred_language)) {
			$val = str_replace('[@en]', '[@' .$preferred_language. ']', $val);
		}
		$req->addPostData($key, $val);
	}
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
	
	// var_dump($req->getResponseCode(), $req->getResponseBody());
	
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
					if (isset($result->redirect)) {
						$redirect_extracted = checkForRedirect($result, 0, $plugin_settings);
						if (is_object($redirect_extracted)) {
							$comment_label = 'http://www.w3.org/2000/01/rdf-schema#comment';
							$label_label = 'http://www.w3.org/2000/01/rdf-schema#label';
							$lang_label = 'xml:lang';
							foreach ($redirect_extracted->representation as $key => $val) {
								// print $key;
								if ($key == $comment_label) {
									foreach ($val as $comment) {
										foreach ($comment as $c_key => $c_val) {
											if(($c_key == $lang_label) && ($c_val == $plugin_settings->preferred_language) && isset($comment->value)) {
												$comment = $comment->value;
											}
											if(($c_key == $lang_label) && ($c_val == $core->settings->default_language) && isset($comment->value)) {
												$comment_default = $comment->value;
											}
										}
									}
								}
								if ($key == $label_label) {
									foreach ($val as $comment) {
										foreach ($comment as $c_key => $c_val) {
											if(($c_key == $lang_label) && ($c_val == $plugin_settings->preferred_language) && isset($comment->value)) {
												$label = $comment->value;
											}
											if(($c_key == $lang_label) && ($c_val == $core->settings->default_language) && isset($comment->value)) {
												$label_default = $comment->value;
											}
										}
									}
								}
								if (isset($label)) {
									$redirect_extracted->name = array($label);
								}
								else if(isset($label_default)) {
									$redirect_extracted->name = array($label_default);
								}
								else {
									$label_default->value = '&nbsp;';
									$redirect_extracted->name = array($label_default);
								}
								if (isset($comment)) {
									$redirect_extracted->comment = array($comment);
								}
								else if(isset($comment_default)) {
									$redirect_extracted->comment = array($comment_default);
								}
								else {
									$comment_default->value = '&nbsp;';
									$redirect_extracted->comment = array($comment_default);
								}
							}
							$result = $redirect_extracted;
							unset($comment, $comment_default);
						}						
					}
					print $result->id. '; ' .$result->name[0]->value. '; '. $result->comment[0]->value;
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

function checkForRedirect($entity, $current_depth, $plugin_settings) {
	if (isset($entity->redirect) && ($current_depth < $plugin_settings->max_redirect_depth)) {
		// redirect identified => get data from redirect if maximum depth not exceeded
		// follow redirect and try to extract data
		// add search query to parameters
		$plugin_settings->browse['parameters']['id'] = stripslashes($entity->redirect[0]->value);
		// print $plugin_settings->browse['url'].'?id='.$plugin_settings->browse['parameters']['id'];
		$req = new HTTP_Request($plugin_settings->browse['url'].'?id='.urlencode($plugin_settings->browse['parameters']['id']));
		$req->setMethod(HTTP_REQUEST_METHOD_GET);
		$req->addHeader("Accept", "application/json");
		
		$response = $req->sendRequest();
	
		if (PEAR::isError($response)) {
			return false;
		} else {
			//print "no error: " .$req->getResponseCode(). ";";
			$query_result = json_decode($req->getResponseBody());
			// var_dump($req);
			if (is_object($query_result)) {
				// print "obj";
				if (isset($query_result->representation->redirect) && ($current_depth < $plugin_settings->max_redirect_depth)) {
					checkForRedirect($query_result, ($current_depth + 1), $plugin_settings);
				}
				else {
					return $query_result;
				}
			}
			else {
				return false;
			}
		}
	}
	else {
		return false;
	}
}

?>