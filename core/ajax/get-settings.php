<?php
/**
 * Get Settings
 *
 * Gets settings and created JSON string out of it. It's used by JS/jQuery via AJAX to get settings specified in a PHP file on the server.
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
$core = new Core();

header('Content-Type: application/json; charset=utf-8');

// allow access to logged in users only
if (!isset($_SESSION['cmf']['userid'])) {
	// redirect to login page
	$message = '{"RESPONSE": [';
	$message .= '{"CODE": "403", "MESSAGE": "' .$core->language->strings->ANNOTATION_SAVE_ERROR_5. '"}';
	$message .= ']}';
	print $message;
}
else {
	$settings = array('settings' => $core->settings, 'video' => $core->video, 'cmf' => $core->cmf, 'userid' => $core->userid);
	print json_encode($settings);
}

?>