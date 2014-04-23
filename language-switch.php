<?php
// define root
define('__ROOT__', dirname(__FILE__));

// include settings
require_once(__ROOT__. '/core/class/annotator.class.php');
$annotator = new Annotator();

if (isset($_GET['language']) && ($_GET['language'] != '')) {
	if (in_array($_GET['language'], $available_languages)) {
		setcookie('lang', $_GET['language'], (time() + (60 * 60 *24)), '/');
	}
	header('Location: ' .$annotator->settings->base_relative);
}

?>