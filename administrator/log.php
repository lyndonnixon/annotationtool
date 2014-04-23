<?php

/**
 * Log
 *
 * Parses the log file by using the admin class funtion and displays it. Allows to show all queries in detail.
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core/Administrator
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
// define root
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(dirname(__FILE__)));
}

/**
 * Get main class of annotation tool.
 * All external libraries are referenced in this class.
 */
require_once(__ROOT__. '/core/class/core.class.php');
require_once(__ROOT__. '/core/class/administrator.class.php');

// start session
session_start();

// init classes
$smarty	= new Smarty();
$core = new Core();
$administrator	= new Administrator();

// include administration file
include(__ROOT__. '/core/includes/administration.inc.php');

// include language file
$smarty->assign('language', $core->language);

//$smarty->force_compile = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->cache_lifetime = 120;
$smarty->setTemplateDir(__ROOT__.'/administrator/templates');
$smarty->setCompileDir(__ROOT__.'/administrator/templates_c');

$smarty->assign('root', __ROOT__);

// page template
$template_name = 'log.tpl';

$save_success = false;

// allow access to logged in users only
if (!isset($_SESSION['cmf']['userid'])) {
	// redirect to login page
	header('Location: http://' .$core->settings->base_url);
}

// menu parameters
$userid = $_SESSION['cmf']['userid'];
$smarty->assign('userid', $userid);

$smarty->assign('base_url', 'http://'.$core->settings->base_url);

if (in_array($userid, $admin['administrators'])) {
	// show settings and allow edit
	$log_list = $administrator->getLog();
	$query_list = $administrator->getQueryList();
	$num = 0;
	foreach ($query_list as $query) {
		$query_list[$num][2] = date('Y-m-d H:i:s', $query_list[$num][1]);
		$num++;
	}
	$smarty->assign('query_list', $query_list);
	$smarty->assign('log_list', nl2br($log_list));
	
}
else {
	// not allowed => redirect to annotator
	header('Location: http://' .$core->settings->base_url);
}


// render template
$smarty->display($template_name);

?>