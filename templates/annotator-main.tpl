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
          	<div class="headline">{$language->strings->VIDEO_PLAYER}</div>
            {if isset($video_data)}
            <div id="video-container">
            </div>
						{/if}
            <div class="footer"></div>
          </div>
          <div class="row-fluid spacer">
            <div class="span12">
            	<div class="annotator-container">
                <div class="headline">{$language->strings->VIDEO_TIMELINE}</div>
                <div class="timeline-container" id="timeline-container">
                  <p class="data-information">{$language->strings->ANNOTATIONS_SAVED}</p>
                </div>
                <div class="footer"></div>
              </div>
            </div><!--/span-->
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
    
    {literal}
    <script type="text/javascript">
			// initialise google
			google.load("visualization", "1");
			
			$(document).ready(function () {
				
				// initialise annotator
				$('body').annotator('init', {
					'keyupdelay': 2000,
					'video': {'id': '{/literal}{if isset($video_data)}{$video_data.id}{/if}{literal}',
										'source': [{/literal}{if isset($video_data)}{foreach from=$video_data.source item=video name=videodata}{literal}{{/literal}src: "{$video.url}", type: "{$video.type}"{literal}}{/literal}{if $smarty.foreach.videodata.last != true}, {/if}{/foreach}{/if}{literal}]
					},
					'user_id': '{/literal}{$userid}{literal}',
					'types_of_annotations': {'annotation': '{/literal}{$available_annotation_types.0}{literal}', 'bookmark': '{/literal}{$available_annotation_types.1}{literal}'},
					'google': google,
					'video_width': 75
				});
				
				/*
				var arr = [32, 33, 42, 21, 55, 12];
				arr = arr.toString();
				var test = {"spatial": arr};
				var json_str = JSON.stringify(test);
				console.log(json_str);
				console.log(jQuery.parseJSON(json_str));
				*/
			});
		</script>
    {/literal}

  </body>
  
  {include
  	file="layout/foot.tpl"
  	language=$language
  }