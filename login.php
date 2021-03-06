<?php
/**
 * Login
 *
 * Login page for logging in via open ID
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
 	
if (isset($_SESSION['cmf']['userid'])) {
	// user is already logged in => redirect to annotator main page
	header('Location: http://' .$core->settings->base_url.'annotator');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="">
<meta name="author" content="">
<base href="http://<?php print $core->settings->base_url; ?>">

<!-- Styles -->
<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/bootstrap-responsive.min.css" rel="stylesheet">
<link href="assets/js/jquery-ui/css/cupertino/jquery-ui-1.9.2.custom.min.css" rel="stylesheet">
<link href="assets/js/video-js/video-js.min.css" rel="stylesheet">
<link href="assets/js/jquery.imgareaselect/css/imgareaselect-default.css" rel="stylesheet">
<link href="assets/css/annotator.css" rel="stylesheet">
<link href="assets/js/timeline/timeline.css" rel="stylesheet">
<link href="assets/js/colorbox/colorbox.css" rel="stylesheet">

<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
<!--[if lt IE 9]>
      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
    <![endif]-->

<!-- Fav and touch icons -->
<link rel="shortcut icon" href="assets/ico/favicon.ico">
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="assets/ico/apple-touch-icon-144-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="assets/ico/apple-touch-icon-114-precomposed.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="assets/ico/apple-touch-icon-72-precomposed.png">
<link rel="apple-touch-icon-precomposed" href="assets/ico/apple-touch-icon-57-precomposed.png">
</head>

<body>
<div class="navbar navbar-inverse navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container-fluid"> <a class="brand" href=""><img src="assets/img/connectme_logo_web.png" alt="ConnectME" /></a>
    </div>
  </div>
</div>
<div class="container-fluid" id="main-content">
  <div class="row-fluid">
    <div class="span12">
      <?php if (isset($error) || isset($msg)): ?>
      <div class="message"> <?php  if (isset($error)) { print $error; }  if (isset($msg)) { print $msg; } ?> </div>
      <?php endif; ?>
      <h1>You have to sign in before you can use the ConnectME Hypervideo Annotation Suite!</h1>
      <form id="signin-form" method="get" action="libraries/openid/try_auth.php">
        <div class="login-container">
          <div class="line">If you already have an account on one of these sites, you can click on the logo to log in with it:</div>
          <div class="openid-providers line"> <span class="provider"><img src="assets/img/google-logo.png" id="google-sign-in" alt="Sign in with Google" /></span> <span class="provider"><img src="assets/img/yahoo-logo.png" id="yahoo-sign-in" alt="Sign in with Yahoo!" /></span> </div>
          <div class="line">You can also manually enter your OpenID instead:</div>
          <div class="openid line">
            <input type="text" name="openid_identifier" id="openid_identifier" value="" />
            <button type="submit" class="btn btn-primary">
            	Login with
            </button>
          </div>
          <input type="hidden" name="action" value="verify" />
        </div>
      </form>
    </div>
    <!--/span--> 
  </div>
  <!--/row-->
  
  <footer id="footer">
    <div class="container">
      <!--<p>&copy; <a href="http://www.connectme.at/" target="_blank">ConnectME</a> 2012</p>-->
      <p>Courtesy <a href="http://www.connectme.at/" target="_blank">ConnectME project</a> funded by the <a href="http://www.ffg.at/" target="_blank">FFG</a> <a href="http://www.ffg.at/coin-cooperation-innovation" target="_blank">COIN programme</a>. &copy; <a href="http://research.sti2.org/" target="_blank">STI Research</a>, <a href="http://www.seekda.com/" target="_blank">Seekda</a> and <a href="https://sites.google.com/site/lyndonnixon/" target="_blank">Dr. Lyndon Nixon</a>.<br />
			Code available under <a href="http://creativecommons.org/licenses/by-nc-nd/3.0/" target="_blank">CC-BY-NC-ND 3.0 license</a> at <a href="https://git.sti2.org/projects/CONNECTME">http://git.sti2.org/projects/CONNECTME</a>.</p>
    </div>
  </footer>
</div>
<!--/.fluid-container--> 

<!-- Javascripts
    ================================================== --> 
<!-- Placed at the end of the document so the pages load faster --> 
<script src="assets/js/jquery-1.7.1.js"></script> 
<script src="assets/js/jquery-ui/js/jquery-ui-1.9.2.custom.min.js"></script> 
<script src="assets/js/bootstrap.min.js"></script> 
<script src="assets/js/bootstrap.hover-dropdown.min.js"></script> 
<script src="assets/js/video-js/video.min.js"></script> 
<script src="assets/js/jquery.imgareaselect/scripts/jquery.imgareaselect.min.js"></script> 
<script src="assets/js/jquery.imgareaselect/scripts/jquery.imgareaselect.pack.js"></script> 
<script src="assets/js/jquery.cookie.js"></script> 
<script src="assets/js/colorbox/jquery.colorbox-min.js"></script> 
<script src="http://www.google.com/jsapi"></script> 
<script src="assets/js/timeline/timeline-modified.js"></script> 
<script src="assets/js/underscore.min.js"></script> 
<script src="assets/js/cmf.js"></script> 
<script src="assets/js/jquery.annotator.js"></script>

<script type="text/javascript">
		$(document).ready(function() {
			if ((($('body').height() - $('#header').height() - $('#footer').height())) > 440) {
				$('#main-content .content').css('height', ($('body').height() - $('#header').height() - $('#footer').height()) + 'px');
			}
			else {
				$('#main-content .content').css('height', '440px');
			}
			$(window).resize(function() {
				if ((($('body').height() - $('#header').height() - $('#footer').height()))  > 440) {
					$('#main-content .content').css('height', ($('body').height() - $('#header').height() - $('#footer').height()) + 'px');
				}
				else {
					$('#main-content .content').css('height', '435px');
				}
			});
			$('#google-sign-in').click(function() {
				$('#openid_identifier').val('https://www.google.com/accounts/o8/id');
				$('#signin-form').submit();
			});
			$('#yahoo-sign-in').click(function() {
				$('#openid_identifier').val('https://me.yahoo.com');
				$('#signin-form').submit();
			});
		});
</script>
</body>
</html>