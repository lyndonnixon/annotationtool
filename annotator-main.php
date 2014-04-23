<?php
/**
 * Annotator main screen
 *
 * Gets all required data and renders template for displaying the main view of the annotation tool
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
$smarty	= new Smarty();
$core = new Core();

// include language file
$smarty->assign('language', $core->language);

//$smarty->force_compile = true;
$smarty->debugging = false;
$smarty->caching = false;
$smarty->cache_lifetime = 120;

// page template
$template_name = 'annotator-main.tpl';

// allow access to logged in users only
if (!isset($_SESSION['cmf']['userid'])) {
	// redirect to login page
	header('Location: http://' .$core->settings->base_url);
}

$smarty->assign('base_url', 'http://'.$core->settings->base_url);

// menu parameters
$userid = $_SESSION['cmf']['userid'];
$smarty->assign('userid', $userid);

$smarty->assign('video_data', $core->video);
$smarty->assign('lmdb_sources', $core->cmf);

// types of annotations
$smarty->assign('available_annotation_types', $core->settings->available_annotation_types);

$smarty->assign('current_version', $core->settings->current_version);
$smarty->assign('base_url', $core->settings->base_url);

// render template
$smarty->display($template_name);

?>