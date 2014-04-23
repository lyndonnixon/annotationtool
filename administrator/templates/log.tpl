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
            	<form name="admin-settings" method="post" action="administrator/">
                <ul class="settings">
                	<li><h3>General Log:</h3></li>
                	<li>{$log_list}</li>
                  <li><h3>Query Log:</h3></li>
                  <li>
                  	<table>
                    	{foreach from=$query_list item=row}
                    	<tr>
                      	<td>{$row.2}</td>
                        <td><a href="log/{$row.1}_query.log" target="_blank">{$row.0}</a></td>
                      </tr>
                      {/foreach}
                    </table>
                  </li>
                	<li>
	              		<button name="back-to-settings" type="submit" class="btn btn-primary" title="{$language->strings->ADMIN_LOG_BACK}">{$language->strings->ADMIN_LOG_BACK}</button>
                  </li>
                </ul>
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