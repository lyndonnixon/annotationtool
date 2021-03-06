<?php
/**
 * Download annotations
 *
 * Implements display of annotation triples by a specified serializer
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



if (isset($_GET['serializer']) && ($_GET['serializer'] != '')) {
	
	$allowed = false;
	foreach ($cmf->settings->available_serializers as $key => $val) {
		if ($_GET['serializer'] == $key) {
			$allowed = true;
		}
	}
	
	if ($allowed != true) {
		
		// serializer not allowed
		
	}
	
	else {

		$video_data= $core->video;
		
		$sel_serializer = $_GET['serializer'];
		
		$file_data = $cmf->generateAnnotationObject($_COOKIE['annotationlist'], $video_data, $cmf->settings->available_serializers[$sel_serializer]);
		
		header('Content-type: ' .$cmf->settings->serializers_header[$sel_serializer]. '; charset=utf-8');
		
		print $file_data;
	
	}

}

?>