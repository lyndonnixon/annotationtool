	{include
  	file="layout/head.tpl"
  	language=$language
    base_url = $base_url
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
         	<div class="annotator-container">
          	<div class="headline">{$language->strings->ADMIN_HEADLINE}</div>
            	<form name="admin-settings" method="post" action="">
             		{$output}
                {if $save_success != true}
                <ul class="settings">
                	<li>
	              		<p><a href="administrator/log.php">{$language->strings->ADMIN_LOG}</a></p>
                  </li>
                	<li>
	              		<button name="save-settings" type="submit" class="btn btn-primary" title="{$language->strings->BUTTON_SAVE}">{$language->strings->BUTTON_SAVE}</button>
                  </li>
                </ul>
                {/if}
              </form>
              <input type="hidden" name="active_admin" id="active_admin" value="{$userid}" />
            <div class="footer"></div>
          </div>
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
    
    {literal}
    <script type="text/javascript" src="assets/js/jquery.administrator.js"></script>
    {/literal}

  </body>
  
  {include
  	file="layout/foot.tpl"
  	language=$language
  }