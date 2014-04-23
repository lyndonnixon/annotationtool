<?php
/**
 * Language switch
 *
 * Allows switching between available languages
 *
 * @author Matthias Bauer <matthias.bauer@sti2.org>
 * @version v2.3
 * @package Core 
 * @copyright 2013 STI International, Seekda GmbH and Dr. Lyndon Nixon
 * @license http://creativecommons.org/licenses/by-nc-nd/3.0/ Creative Commons Attribution-NonCommercial-NoDerivs 3.0 Unported License
 *
 */
if (!defined('__ROOT__')) {
	define('__ROOT__', dirname(dirname(dirname(__FILE__))));
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

if (isset($_GET['language']) && ($_GET['language'] != '')) {
	if (in_array($_GET['language'], $core->settings->available_languages)) {
		setcookie('lang', $_GET['language'], (time() + (60 * 60 *24)), '/');
	}
	header('Location: ' .$core->settings->base_relative);
}

?>