<?php
/**
 * Logout
 *
 * Logout page for logging users out
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core 
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(__FILE__));
}
/**
 * Get main class of annotation tool.
 * All external libraries are referenced in this class.
 */
require_once(__ROOT__. '/core/class/core.class.php');

// start session
session_start();

// init classes
$cmf	= new CMF();
$core = new Core();

$cmf->toLog(date('Y-m-d H:i:s', time()). ' Logout user ' .$_SESSION['cmf']['userid']);
unset($_SESSION['cmf']['userid']);
header('Location: http://' .$core->settings->base_url);
?>