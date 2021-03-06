<?php
/**
 * To log
 *
 * Allows using cmf class function toLog from JS/jQuery via AJAX and HTTP POST.
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
	
if (isset($_POST['data']) && ($_POST['data'] != '')) {

	$cmf->toLog(date('Y-m-d H:i:s', time()). ' ' .stripslashes($_POST['data']));
	
}
else {
	print 'Nothing found';
}


?>