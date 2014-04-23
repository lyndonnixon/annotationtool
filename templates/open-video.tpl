	{include
  	file="layout/head.tpl"
  	language=$language
  }
  
  <body>

  	{include
  		file="layout/header.tpl"
	  	language=$language
      userid=$userid
      current_version=$current_version
      base_url=$base_url
  	}

    <div class="container-fluid" id="main-content">
      <div class="row-fluid">
        <div class="span12">
         	<div class="annotator-container">
          	<div class="headline">{$language->strings->OPEN_VIDEO_TITLE}</div>
            <div id="load-container">
            	<section>
              	<h2>{$language->strings->OPEN_VIDEO_HEADLINE}</h2>	
              	<form id="video-load" method="post" action="">
                	<fieldset>
                    <legend>{$language->strings->OPEN_VIDEO_OPTION_1}</legend>
                    <div id="container-regular-videos">
                      <select name="available-videos" id="available-videos">
                      	<option value="">{$language->strings->OPEN_VIDEO_SELECT_PLACEHOLDER}</option>
                      </select>
                      <img src="assets/img/ajax-loader.gif" alt="Loading..." class="loading-indicator" />
                    </div>
                  </fieldset>
                  <fieldset>
                    <legend>{$language->strings->OPEN_VIDEO_OPTION_2}</legend>
                    <div id="container-annotated-videos">
                      <select name="annotated-videos" id="annotated-videos">
                      	<option value="">{$language->strings->OPEN_VIDEO_SELECT_PLACEHOLDER}</option>
                      </select>
                      <img src="assets/img/ajax-loader.gif" alt="Loading..." class="loading-indicator" />
                    </div>
                  </fieldset>
                  <fieldset>
                    <legend>{$language->strings->OPEN_VIDEO_OPTION_3}</legend>
                    <div id="container-video-url">
                      <input type="text" placeholder="{$language->strings->OPEN_VIDEO_OPTION_3}" name="url-video" id="url-video" />
                      <div id="video-url-additional">
                        <input type="text" placeholder="{$language->strings->ANNOTATOR_VIDEO_ADD_TITLE}" name="video-title" id="video-title" /><br />
                        <textarea placeholder="{$language->strings->ANNOTATOR_VIDEO_ADD_DESCRIPTION}" name="video-description" id="video-description"></textarea><br />
                        <input type="text" placeholder="{$language->strings->ANNOTATOR_VIDEO_ADD_KEYWORDS}" name="video-keywords" id="video-keywords" />
                        <div id="keyword-preview"></div>
                      </div>
                    </div>
                  </fieldset>
                </form>
							</section>
              <aside>
                <button type="button" class="btn btn-primary" id="back">
                 &laquo; {$language->strings->OPEN_VIDEO_CANCEL}
                </button>
                <button type="button" class="btn btn-primary" id="continue">
                 {$language->strings->OPEN_VIDEO_CONTINUE} &raquo;
                </button>
              </aside>
              <div class="clear"></div>
            </div>
            <div class="footer"></div>
          </div><!--/row-->
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
    
		{* Fill video selector if no video has been specified via HTTP GET *}

    {literal}
    <script type="text/javascript" src="assets/js/jquery.annotator.load.js"></script>
    <script type="text/javascript">

			var keywords = [];
		
			function updateKeywords(string) {
				// try to split by comma (copy-paste)
				var keyword_string = string.split(',');
				// add identified keyword(s) to list
				$.each(keyword_string, function(key, val) {
					val = $.trim(val);
					// is keyword valid and already in the list
					if ((val != '') && (jQuery.inArray(val, keywords)) == -1){
						// add keyword
						keywords.push(val);
					}
				})
				// update keyword preview
				$('#keyword-preview').empty();
				for (var i = 0; i < keywords.length; i++) {
					$('#keyword-preview').append('<a class="btn btn-small" href="#" id="keyword_' + i + '"><i class="icon-remove"></i> ' + keywords[i] + '</a>');
				}
				$('.btn-small').bind('click', function(event) {
					var updated_keywords = [];
					for (var i = 0; i < keywords.length; i++) {
						if ($(this).attr('id') == 'keyword_' + i) {
							// keyword found => remove it
							$(this).remove();
						}
						else {
							updated_keywords.push(keywords[i]);
						}
					}
					keywords = updated_keywords;
				})
			}
		
			$(document).ready(function () {
				
				$('body').annotatorload();
				
			});
		</script>
    {/literal}
    
  </body>
  
  {include
  	file="layout/foot.tpl"
  	language=$language
  }