<?php /* Smarty version Smarty-3.1.12, created on 2013-02-26 13:00:28
         compiled from "/var/www/connectme/2.0/administrator/templates/layout/header.tpl" */ ?>
<?php /*%%SmartyHeaderCode:38197145107ab6420d626-05757871%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '6d362982842d6d70f506bbe0521dd04c32130570' => 
    array (
      0 => '/var/www/connectme/2.0/administrator/templates/layout/header.tpl',
      1 => 1361883626,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '38197145107ab6420d626-05757871',
  'function' => 
  array (
  ),
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_5107ab64259da6_03021062',
  'variables' => 
  array (
    'base_url' => 0,
    'language' => 0,
    'menuitem' => 0,
    'attr_key' => 0,
    'attr_val' => 0,
    'menuitem_lvl2' => 0,
    'menukey' => 0,
    'userid' => 0,
  ),
  'has_nocache_code' => false,
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5107ab64259da6_03021062')) {function content_5107ab64259da6_03021062($_smarty_tpl) {?>		<div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
          <a class="brand" href="<?php echo $_smarty_tpl->tpl_vars['base_url']->value;?>
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
"<?php if (isset($_smarty_tpl->tpl_vars['menuitem']->value->children)){?> class="dropdown-toggle" data-hover="dropdown"<?php }?><?php if (isset($_smarty_tpl->tpl_vars['menuitem']->value->attr)){?><?php  $_smarty_tpl->tpl_vars['attr_val'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['attr_val']->_loop = false;
 $_smarty_tpl->tpl_vars['attr_key'] = new Smarty_Variable;
 $_from = ($_smarty_tpl->tpl_vars['menuitem']->value->attr); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['attr_val']->key => $_smarty_tpl->tpl_vars['attr_val']->value){
$_smarty_tpl->tpl_vars['attr_val']->_loop = true;
 $_smarty_tpl->tpl_vars['attr_key']->value = $_smarty_tpl->tpl_vars['attr_val']->key;
?><?php echo $_smarty_tpl->tpl_vars['attr_key']->value;?>
="<?php echo $_smarty_tpl->tpl_vars['attr_val']->value;?>
"<?php } ?><?php }?>><?php echo $_smarty_tpl->tpl_vars['menuitem']->value->title;?>
<?php if (isset($_smarty_tpl->tpl_vars['menuitem']->value->children)){?> <?php if (isset($_smarty_tpl->tpl_vars['menuitem']->value->flag)){?><img src="assets/img/flags/flag-<?php echo $_smarty_tpl->tpl_vars['menuitem']->value->flag;?>
.png" alt="<?php echo $_smarty_tpl->tpl_vars['menuitem']->value->flag;?>
" /><?php }else{ ?><b class="caret"></b><?php }?><?php }?></a>
                <?php if (isset($_smarty_tpl->tpl_vars['menuitem']->value->children)){?>
                <ul class="dropdown-menu">
                	<?php  $_smarty_tpl->tpl_vars['menuitem_lvl2'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['menuitem_lvl2']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['menuitem']->value->children; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['menuitem_lvl2']->key => $_smarty_tpl->tpl_vars['menuitem_lvl2']->value){
$_smarty_tpl->tpl_vars['menuitem_lvl2']->_loop = true;
?>
                  <li><a href="<?php echo $_smarty_tpl->tpl_vars['menuitem_lvl2']->value->url;?>
"<?php if (isset($_smarty_tpl->tpl_vars['menuitem_lvl2']->value->attr)){?><?php  $_smarty_tpl->tpl_vars['attr_val'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['attr_val']->_loop = false;
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