<?php
/**
 * Open video
 *
 * Renders the template for laoding videos from the CMF
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
$template_name = 'open-video.tpl';

// allow access to logged in users only
if (!isset($_SESSION['cmf']['userid'])) {
	// redirect to login page
	header('Location: http://' .$base_url);
}

// menu parameters
$userid = $_SESSION['cmf']['userid'];
$smarty->assign('userid', $userid);

$video_data = array();
$video_data['id'] = 'http://my-video.com/id1';
// $video_data['source'] = array(array('url' => 'http://video-js.zencoder.com/oceans-clip.mp4', 'type' => 'video/mp4'), array('url' => 'http://video-js.zencoder.com/oceans-clip.webm', 'type' => 'video/webm'), array('url' => 'http://video-js.zencoder.com/oceans-clip.ogv', 'type' => 'video/ogg'));

$video_data['source'] = array(array('url' => 'https://s3-eu-west-1.amazonaws.com/yoo.120/connectme/6306_519_20120508125738_standard.mp4', 'type' => 'video/mp4'));

$smarty->assign('video_data', $video_data);

if (isset($admin["lmdb_sources"]) && (count($admin["lmdb_sources"]) > 0)) {
	foreach ($admin["lmdb_sources"] as $lmdb) {
		if ($lmdb["selected"] == 1) {
			if (substr($lmdb['url'], 4, 1) == ':') {
				// http
				if (isset($lmdb['username']) && isset($lmdb['password']) && ($lmdb['username'] != '') && ($lmdb['password'] != '')) {
					$tmp_url = 'http://'.substr($lmdb['url'], 7, strlen($lmdb['url']));
				}
				else {
					$tmp_url = $lmdb['url'];
				}
			}
			else if (substr($lmdb['url'], 4, 1) == 's') {
				// https
				if (isset($lmdb['username']) && isset($lmdb['password']) && ($lmdb['username'] != '') && ($lmdb['password'] != '')) {
					$tmp_url = 'https://'.substr($lmdb['url'], 8, strlen($lmdb['url']));
				}
				else {
					$tmp_url = $lmdb['url'];
				}
			}
			else {
				$tmp_url = $lmdb['url'];
			}
			$lmdb['url'] = $tmp_url;
			$lmdb_sources = $lmdb;
		}
	}
}
else {
	$lmdb_sources = $core->settings->lmdb_default;
}
$smarty->assign('lmdb_sources', $lmdb_sources);
$smarty->assign('base_relative', $core->settings->base_relative);

if (isset($_GET['video_id']) && ($_GET['video_id'] != '')) {
	$smarty->assign('video_id', $_GET['video_id']);
}

$smarty->assign('current_version', $core->settings->current_version);
$smarty->assign('base_url', $core->settings->base_relative);

// render template
$smarty->display($template_name);

?>