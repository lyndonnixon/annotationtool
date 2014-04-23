<?php
/**
 * Save to CMF
 *
 * Implements saving to CMF by using the CMF class.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core/Ajax
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */

// start session
session_start();

// define root
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(dirname(dirname(__FILE__))));
}

/**
 * Get main class of annotation tool.
 * All external libraries are referenced in this class.
 */
require_once(__ROOT__. '/core/class/core.class.php');

// init classes
$cmf	= new CMF();
$core = new Core();

// allow access to logged in users only
if (!isset($_SESSION['cmf']['userid'])) {
	// redirect to login page
	$message = '{"RESPONSES": [';
	$message .= '{"CODE": "403", "MESSAGE": "' .$cmf->language->strings->ANNOTATION_SAVE_ERROR_5. '"}';
	$message .= ']}';
	print $message;
	die();
}

if (isset($_GET['type']) && ($_GET['type'] != '')) {
	
	$allowed = false;
	
	$video_data= $core->video;
	$lmdb_sources = $core->cmf;

	
		$updated_annotations = $_COOKIE['annotationlist'];
		$old_annotations = str_replace('annotations_', 'backup_', $_COOKIE['annotationlist']);
		$video_locators = $video_data;
		$service_url = 'sparql/update';
		
		if ($_GET['type'] == 'update') {
			// update annotations
			$insert_query = $cmf->updateAnnotations($lmdb_sources['protected_url'], $service_url, $video_data, $updated_annotations, $old_annotations);
			
			// var_dump($insert_query);
			
			// get response details
			$success = false;
			$message = '';
			if ($insert_query->_response->_code == 200) {
					$success = true;
			}
			
			if ($success == false) {
				// error ocurred
				header('Content-type: application/json');
				$message = '{"RESPONSES": [';
				$cnt = 0;
				$message .= '{"CODE": "' .$insert_query->_response->_code. '", "MESSAGE": "' .$insert_query->_response->_reason. '"}';
				$message .= ']}';
				print $message;
			}
			else {
				
				header('Content-type: application/json');
			
				// print '{"CODE": "304", "MESSAGE": "' .$cmf->language->strings->ANNOTATION_SAVE_NO_MODIFICATIONS. '"}';
					
				// set annotations_loaded cookie (=> now annotations exist => further inserts should update them)
				setcookie('annotationsloaded', 'true', (time() + (60*60*24)), '/');
				$_SESSION['annotationsloaded'] = 'true';
					
				// changes submitted
				print '{"CODE": "200", "MESSAGE": "' .$cmf->language->strings->ANNOTATION_SAVE_SUCCESS. '"}';
				
			}
		}
		else if ($_GET['type'] == 'new') {
			// create new annotations in the cmf
			// update annotations
			$insert_query = $cmf->createAnnotations($lmdb_sources['protected_url'], $service_url, $video_data, $updated_annotations);
			// get response details
			$success = false;
			$message = '';
			if ($insert_query->_response->_code == 200) {
				$success = true;
			}
			
			if ($success == false) {
				// error ocurred
				header('Content-type: application/json');
				$message = '{"RESPONSES": [';
				$cnt = 0;
				$message .= '{"CODE": "' .$insert_query->_response->_code. '", "MESSAGE": "' .$insert_query->_response->_reason. '"}';
				$message .= ']}';
				print $message;
			}
			else {
				
					header('Content-type: application/json');
					
				// no changes
				// print '{"CODE": "304", "MESSAGE": "' .$cmf->language->strings->ANNOTATION_SAVE_NO_MODIFICATIONS. '"}';
					
				// set annotations_loaded cookie (=> now annotations exist => further inserts should update them)
				setcookie('annotationsloaded', 'true', (time() + (60*60*24)), '/');
						
				// changes submitted
				print '{"CODE": "200", "MESSAGE": "' .$cmf->language->strings->ANNOTATION_SAVE_SUCCESS. '"}';
					
			}
		}
		else {
			print "not allowed";
		}

}
?>