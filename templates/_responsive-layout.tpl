	{include
  	file="layout/head.tpl"
  	strings=$strings
  }
  
  <body>

  	{include
  		file="layout/header.tpl"
	  	strings=$strings
  	}

    <div class="container-fluid" id="main-content">
      <div class="row-fluid">
        <div class="span12">
         	<div class="annotator-container">
          	<div class="headline">{$strings.VIDEO_PLAYER}</div>
            {if ($video_data != '')}
            <div id="video-container">
              <video style="max-width: 100%; height: auto;" id="loaded_video" class="video-js vjs-default-skin" controls="controls" preload="auto">
              </video>
            </div>
						{/if}
            <div class="footer"></div>
          </div>
          <div class="row-fluid">
            <div class="span12">
            	<div class="annotator-container">
                <div class="headline">{$strings.VIDEO_TIMELINE}</div>
                <div class="timeline-container">
                  <p>Donec id elit non mi porta gravida at eget metus. Fusce dapibus, tellus ac cursus commodo, tortor mauris condimentum nibh, ut fermentum massa justo sit amet risus. Etiam porta sem malesuada magna mollis euismod. Donec sed odio dui. </p>
                  <p><a class="btn" href="#">View details &raquo;</a></p>
                </div>
                <div class="footer"></div>
              </div>
            </div><!--/span-->
          </div><!--/row-->
        </div><!--/span-->
      </div><!--/row-->

      <hr>

      {include
        file="layout/footer.tpl"
        strings=$strings
      }

    </div><!--/.fluid-container-->

    {include
      file="layout/scripts.tpl"
      strings=$strings
    }

  </body>
  
  {include
  	file="layout/foot.tpl"
  	strings=$strings
  }