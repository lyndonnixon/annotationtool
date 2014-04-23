	{include
  	file="layout/head.tpl"
  	language=$language
  }
  
  <body>

  	{include
  		file="layout/header.tpl"
	  	language=$language
      userid=$userid
  	}

    <div class="container-fluid" id="main-content">
      <div class="row-fluid">
        <div class="span12">
         	<div class="image-preloader">
          	<img src="assets/img/google-signin.png" alt="" />
            <img src="assets/img/google-signin-active.png" alt="" />
            <img src="assets/img/google-signin-hover.png" alt="" />
          </div>
          {if ($error != '') || ($msg != '')}
          <div class="message">
          	{$error}
            {$msg}
          </div>
          {/if}
          <h1>You have to sign in before you can use the ConnectME Hypervideo Annotation Suite!</h1>
          <form id="signin-form" method="get" action="libraries/openid/try_auth.php">
          	<div class="login-container">
         			<div class="line">If you already have an account on one of these sites, you can click on the logo to log in with it:</div>
         	   	<div class="openid-providers line">
               	<span class="provider"><img src="assets/img/google-logo.png" id="google-sign-in" alt="Sign in with Google" /></span>
                <span class="provider"><img src="assets/img/yahoo-logo.png" id="yahoo-sign-in" alt="Sign in with Yahoo!" /></span>
              </div>
         	   	<div class="line">You can also manually enter your OpenID instead:</div>
         	   	<div class="openid line"><input type="text" name="openid_identifier" id="openid_identifier" value="" /><input type="submit" value="Login with" /></div>
         	   	<input type="hidden" name="action" value="verify" />
	          </div>
          </form>
        </div><!--/span-->
      </div><!--/row-->
      
      {include
        file="layout/footer.tpl"
        language=$language
      }

    </div><!--/.fluid-container-->

    {include
      file="layout/scripts.tpl"
      language=$language
    }

  </body>
  
  {include
  	file="layout/foot.tpl"
  	language=$language
  }