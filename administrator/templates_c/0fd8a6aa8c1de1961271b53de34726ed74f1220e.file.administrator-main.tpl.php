<?php /* Smarty version Smarty-3.1.12, created on 2013-02-26 11:41:36
         compiled from "/var/www/connectme/2.0/administrator/templates/administrator-main.tpl" */ ?>
<?php /*%%SmartyHeaderCode:11088320435107a5fc6761e4-51152558%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '0fd8a6aa8c1de1961271b53de34726ed74f1220e' => 
    array (
      0 => '/var/www/connectme/2.0/administrator/templates/administrator-main.tpl',
      1 => 1361877871,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '11088320435107a5fc6761e4-51152558',
  'function' => 
  array (
  ),
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_5107a5fc6fd974_12781540',
  'variables' => 
  array (
    'language' => 0,
    'base_url' => 0,
    'userid' => 0,
    'output' => 0,
    'save_success' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5107a5fc6fd974_12781540')) {function content_5107a5fc6fd974_12781540($_smarty_tpl) {?>	<?php echo $_smarty_tpl->getSubTemplate ("layout/head.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value,'base_url'=>$_smarty_tpl->tpl_vars['base_url']->value), 0);?>

  
  <body>

  	<?php echo $_smarty_tpl->getSubTemplate ("layout/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value,'userid'=>$_smarty_tpl->tpl_vars['userid']->value), 0);?>


    <div class="container-fluid" id="main-content">
      <div class="row-fluid">
        <div class="span12">
         	<div class="annotator-container">
          	<div class="headline"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->ADMIN_HEADLINE;?>
</div>
            	<form name="admin-settings" method="post" action="">
             		<?php echo $_smarty_tpl->tpl_vars['output']->value;?>

                <?php if ($_smarty_tpl->tpl_vars['save_success']->value!=true){?>
                <ul class="settings">
                	<li>
	              		<button name="save-settings" type="submit" class="btn btn-primary" title="<?php echo $_smarty_tpl->tpl_vars['language']->value->strings->BUTTON_SAVE;?>
"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->BUTTON_SAVE;?>
</button>
                  </li>
                </ul>
                <?php }?>
              </form>
              <input type="hidden" name="active_admin" id="active_admin" value="<?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
" />
            <div class="footer"></div>
          </div>
        </div><!--/span-->
      </div><!--/row-->
      
      <?php echo $_smarty_tpl->getSubTemplate ("layout/footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>


    </div><!--/.fluid-container-->

    <?php echo $_smarty_tpl->getSubTemplate ("layout/scripts.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>

    
    
    <script type="text/javascript" src="assets/js/jquery.administrator.js"></script>
    

  </body>
  
  <?php echo $_smarty_tpl->getSubTemplate ("layout/foot.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value), 0);?>
<?php }} ?>