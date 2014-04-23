<?php

define('__ROOT__', dirname(__FILE__));

// include settings
// include(__ROOT__. '/core/includes/settings.inc.php');

// include language file
require_once(__ROOT__. '/core/class/annotator.class.php');
$annotator = new Annotator();

$service_url = 'video/resource/rss';

session_start();

// identify partner
if (isset($_GET['partner']) && in_array($_GET['partner'], $annotator->settings->allowed_partners)) {
	// search for user id
	if (isset($_GET['user']) && is_numeric($_GET['user'])) {
		// valid user id found
		$user_url = $annotator->settings->urls_partner[$_GET['partner']].$_GET['user'];
		$_SESSION['cmf']['userid'] = $user_url;
		// print $user_url;
		// search for video id
		if (isset($_GET['video']) && is_numeric($_GET['video']) && isset($_GET['channel']) && is_numeric($_GET['channel'])) {
			// valid video and channel id found
			$parameters = '?id=' .$_GET['video']. '&origin=yoovis&channel=' .$_GET['channel'];
			try {
				// http request required
				require_once (__ROOT__. '/libraries/HTTP_Request/Request.php');
				
				// print $lmdb_sources[1]['url'].$service_url.$parameters;
				
				// create new http request
				$request	= new HTTP_Request();
				$request->setURL($annotator->settings->lmdb_sources[1]['url'].$service_url.$parameters);
				$request->setMethod(HTTP_REQUEST_METHOD_GET);
				$response	= $request->sendRequest();
				
				if (PEAR::isError($response)) {
					// return PEAR error
					Throw new Exception($response->getMessage());
				}
				else {
					$response = $request;
					// parse response and open loading url
					
					// load video in annotation tool
					$cmf_response = json_decode(stripslashes($response->getResponseBody()));
					if (is_object($cmf_response)) {
						require_once __ROOT__. '/core/class/annotator.class.php';
						$annotator->toLog(date('Y-m-d H:i:s', time()). ' Login user ' .$_SESSION['cmf']['userid']);
						header('Location: http://' .$annotator->settings->base_url.'open?video_id='.urlencode($cmf_response->url));
					}
					else {
						Throw new Exception($response->getResponseBody());
					}
				}
			}
			catch (Exception $e) {
				print 'ERROR: ' .$e->getMessage();
			}
		}
	}
	else {
		print 'ERROR: User not allowed';
	}
}
else {
	print 'ERROR: Partner not allowed';
}


?>