<?php /* Smarty version Smarty-3.1.12, created on 2013-06-06 13:44:17
         compiled from "/var/www/connectme/2.2/administrator/templates/log.tpl" */ ?>
<?php /*%%SmartyHeaderCode:190733433151b08a6ccb5754-00549870%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b8820e28699bf4c0a91827e5236d2bc04b831438' => 
    array (
      0 => '/var/www/connectme/2.2/administrator/templates/log.tpl',
      1 => 1370526255,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '190733433151b08a6ccb5754-00549870',
  'function' => 
  array (
  ),
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_51b08a6cea5466_96026517',
  'variables' => 
  array (
    'language' => 0,
    'base_url' => 0,
    'userid' => 0,
    'log_list' => 0,
    'query_list' => 0,
    'row' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_51b08a6cea5466_96026517')) {function content_51b08a6cea5466_96026517($_smarty_tpl) {?>	<?php echo $_smarty_tpl->getSubTemplate ("layout/head.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value,'base_url'=>$_smarty_tpl->tpl_vars['base_url']->value), 0);?>

  
  <body>

  	<?php echo $_smarty_tpl->getSubTemplate ("layout/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, null, null, array('language'=>$_smarty_tpl->tpl_vars['language']->value,'userid'=>$_smarty_tpl->tpl_vars['userid']->value), 0);?>


    <div class="container-fluid" id="main-content">
      <div class="row-fluid">
        <div class="span12">
         	<div class="annotator-container">
          	<div class="headline"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->ADMIN_HEADLINE;?>
</div>
            	<form name="admin-settings" method="post" action="administrator/">
                <ul class="settings">
                	<li><h3>General Log:</h3></li>
                	<li><?php echo $_smarty_tpl->tpl_vars['log_list']->value;?>
</li>
                  <li><h3>Query Log:</h3></li>
                  <li>
                  	<table>
                    	<?php  $_smarty_tpl->tpl_vars['row'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['row']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['query_list']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['row']->key => $_smarty_tpl->tpl_vars['row']->value){
$_smarty_tpl->tpl_vars['row']->_loop = true;
?>
                    	<tr>
                      	<td><?php echo $_smarty_tpl->tpl_vars['row']->value[2];?>
</td>
                        <td><a href="log/<?php echo $_smarty_tpl->tpl_vars['row']->value[1];?>
_query.log" target="_blank"><?php echo $_smarty_tpl->tpl_vars['row']->value[0];?>
</a></td>
                      </tr>
                      <?php } ?>
                    </table>
                  </li>
                	<li>
	              		<button name="back-to-settings" type="submit" class="btn btn-primary" title="<?php echo $_smarty_tpl->tpl_vars['language']->value->strings->ADMIN_LOG_BACK;?>
"><?php echo $_smarty_tpl->tpl_vars['language']->value->strings->ADMIN_LOG_BACK;?>
</button>
                  </li>
                </ul>
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