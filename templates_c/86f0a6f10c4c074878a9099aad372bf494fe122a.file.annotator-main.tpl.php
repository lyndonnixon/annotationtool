<?php /* Smarty version Smarty-3.1.12, created on 2013-09-30 13:03:10
         compiled from "./templates/annotator-main.tpl" */ ?>
<?php /*%%SmartyHeaderCode:19405111785249768e68f507-87006841%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '86f0a6f10c4c074878a9099aad372bf494fe122a' => 
    array (
      0 => './templates/annotator-main.tpl',
      1 => 1377790993,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '19405111785249768e68f507-87006841',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'language' => 0,
    'userid' => 0,
    'current_version' => 0,
    'base_url' => 0,
    'video_data' => 0,
    'video' => 0,
    'available_annotation_types' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_5249768ea0b2f4_63644266',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5249768ea0b2f4_63644266')) {function content_5249768ea0b2f4_63644266($_smarty_tpl) {?>	<?php echo $_smarty_tpl->getSubTemplate ("layout/head.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>

  
  <body>

  	<?php echo $_smarty_tpl->getSubTemplate ("layout/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value,'userid'=>$_smarty_tpl->tpl_vars['userid']->value,'current_version'=>$_smarty_tpl->tpl_vars['current_version']->value,'base_url'=>$_smarty_tpl->tpl_vars['base_url']->value), 0);?>


    <div class="container-fluid" id="main-content">
      <div class="row-fluid">
        <div class="span12">
         	<div class="annotator-container">
          	<div class="headline"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->VIDEO_PLAYER;?>
</div>
            <?php if (isset($_smarty_tpl->tpl_vars['video_data']->value)){?>
            <div id="video-container">
            </div>
						<?php }?>
            <div class="footer"></div>
          </div>
          <div class="row-fluid spacer">
            <div class="span12">
            	<div class="annotator-container">
                <div class="headline"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->VIDEO_TIMELINE;?>
</div>
                <div class="timeline-container" id="timeline-container">
                  <p class="data-information"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->ANNOTATIONS_SAVED;?>
</p>
                </div>
                <div class="footer"></div>
              </div>
            </div><!--/span-->
          </div><!--/row-->
        </div><!--/span-->
      </div><!--/row-->
      
      <?php echo $_smarty_tpl->getSubTemplate ("layout/footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>


    </div><!--/.fluid-container-->

    <?php echo $_smarty_tpl->getSubTemplate ("layout/scripts.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>

    
    
    <script type="text/javascript">
			// initialise google
			google.load("visualization", "1");
			
			$(document).ready(function () {
				
				// initialise annotator
				$('body').annotator('init', {
					'keyupdelay': 2000,
					'video': {'id': '<?php if (isset($_smarty_tpl->tpl_vars['video_data']->value)){?><?php echo $_smarty_tpl->tpl_vars['video_data']->value['id'];?>
<?php }?>',
										'source': [<?php if (isset($_smarty_tpl->tpl_vars['video_data']->value)){?><?php  $_smarty_tpl->tpl_vars['video'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['video']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['video_data']->value['source']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['video']->total= $_smarty_tpl->_count($_from);
 $_smarty_tpl->tpl_vars['video']->iteration=0;
foreach ($_from as $_smarty_tpl->tpl_vars['video']->key => $_smarty_tpl->tpl_vars['video']->value){
$_smarty_tpl->tpl_vars['video']->_loop = true;
 $_smarty_tpl->tpl_vars['video']->iteration++;
 $_smarty_tpl->tpl_vars['video']->last = $_smarty_tpl->tpl_vars['video']->iteration === $_smarty_tpl->tpl_vars['video']->total;
 $_smarty_tpl->tpl_vars['smarty']->value['foreach']['videodata']['last'] = $_smarty_tpl->tpl_vars['video']->last;
?>{src: "<?php echo $_smarty_tpl->tpl_vars['video']->value['url'];?>
", type: "<?php echo $_smarty_tpl->tpl_vars['video']->value['type'];?>
"}<?php if ($_smarty_tpl->getVariable('smarty')->value['foreach']['videodata']['last']!=true){?>, <?php }?><?php } ?><?php }?>]
					},
					'user_id': '<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
',
					'types_of_annotations': {'annotation': '<?php echo $_smarty_tpl->tpl_vars['available_annotation_types']->value[0];?>
', 'bookmark': '<?php echo $_smarty_tpl->tpl_vars['available_annotation_types']->value[1];?>
'},
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
    

  </body>
  
  <?php echo $_smarty_tpl->getSubTemplate ("layout/foot.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>
<?php }} ?>