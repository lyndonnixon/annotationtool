<?php /* Smarty version Smarty-3.1.12, created on 2013-01-29 10:38:38
         compiled from "/var/www/connectme/2.0/templates/layout/header.tpl" */ ?>
<?php /*%%SmartyHeaderCode:10399933015107a6aeb429c5-63933308%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd0bf9051a496b0ae5290ea445978bbd4ce4d72d2' => 
    array (
      0 => '/var/www/connectme/2.0/templates/layout/header.tpl',
      1 => 1356082720,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '10399933015107a6aeb429c5-63933308',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'base_relative' => 0,
    'language' => 0,
    'menuitem' => 0,
    'menuitem_lvl2' => 0,
    'attr_key' => 0,
    'attr_val' => 0,
    'menukey' => 0,
    'userid' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_5107a6aebd09c3_75280186',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5107a6aebd09c3_75280186')) {function content_5107a6aebd09c3_75280186($_smarty_tpl) {?>		<div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="brand" href="<?php echo $_smarty_tpl->tpl_vars['base_relative']->value;?>
"><img src="assets/img/connectme_logo_web.png" alt="ConnectME" /></a>
          <div class="nav-collapse collapse">
            <ul class="nav pull-right">
	            <?php  $_smarty_tpl->tpl_vars['menuitem'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['menuitem']->_loop = false;
 $_smarty_tpl->tpl_vars['menukey'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['language']->value->main_menu; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['menuitem']->key => $_smarty_tpl->tpl_vars['menuitem']->value){
$_smarty_tpl->tpl_vars['menuitem']->_loop = true;
 $_smarty_tpl->tpl_vars['menukey']->value = $_smarty_tpl->tpl_vars['menuitem']->key;
?>
              <li class="dropdown">
              	<a href="<?php echo $_smarty_tpl->tpl_vars['menuitem']->value->url;?>
"<?php if (($_smarty_tpl->tpl_vars['menuitem']->value->children!='')){?> class="dropdown-toggle" data-hover="dropdown"<?php }?>><?php echo $_smarty_tpl->tpl_vars['menuitem']->value->title;?>
<?php if (($_smarty_tpl->tpl_vars['menuitem']->value->children!='')){?> <b class="caret"></b><?php }?></a>
                <?php if (($_smarty_tpl->tpl_vars['menuitem']->value->children!='')){?>
                <ul class="dropdown-menu">
                	<?php  $_smarty_tpl->tpl_vars['menuitem_lvl2'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['menuitem_lvl2']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['menuitem']->value->children; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['menuitem_lvl2']->key => $_smarty_tpl->tpl_vars['menuitem_lvl2']->value){
$_smarty_tpl->tpl_vars['menuitem_lvl2']->_loop = true;
?>
                  <li><a href="<?php echo $_smarty_tpl->tpl_vars['menuitem_lvl2']->value->url;?>
"<?php if (($_smarty_tpl->tpl_vars['menuitem_lvl2']->value->attr!='')){?><?php  $_smarty_tpl->tpl_vars['attr_val'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['attr_val']->_loop = false;
 $_smarty_tpl->tpl_vars['attr_key'] = new Smarty_Variable;
 $_from = ($_smarty_tpl->tpl_vars['menuitem_lvl2']->value->attr); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['attr_val']->key => $_smarty_tpl->tpl_vars['attr_val']->value){
$_smarty_tpl->tpl_vars['attr_val']->_loop = true;
 $_smarty_tpl->tpl_vars['attr_key']->value = $_smarty_tpl->tpl_vars['attr_val']->key;
?><?php echo $_smarty_tpl->tpl_vars['attr_key']->value;?>
="<?php echo $_smarty_tpl->tpl_vars['attr_val']->value;?>
"<?php } ?><?php }?>><?php echo $_smarty_tpl->tpl_vars['menuitem_lvl2']->value->title;?>
<?php if (($_smarty_tpl->tpl_vars['menukey']->value=='LOGOUT')){?> <?php echo $_smarty_tpl->tpl_vars['userid']->value;?>
<?php }?></a></li>
                  <?php } ?>
                  </ul>
                <?php }?>
              </li>
              <?php } ?>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div><?php }} ?>