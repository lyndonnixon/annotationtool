<?php /* Smarty version Smarty-3.1.12, created on 2013-09-30 13:03:10
         compiled from "./templates/layout/header.tpl" */ ?>
<?php /*%%SmartyHeaderCode:7319917545249768ea3a0b9-72716438%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '7c68129d69ff8aad044a55275f8902731df18108' => 
    array (
      0 => './templates/layout/header.tpl',
      1 => 1377790993,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '7319917545249768ea3a0b9-72716438',
  'function' => 
  array (
  ),
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
    'current_version' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.12',
  'unifunc' => 'content_5249768ed205d8_74724620',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_5249768ed205d8_74724620')) {function content_5249768ed205d8_74724620($_smarty_tpl) {?>		<div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
       		<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
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
              <li><a href="#"><?php echo $_smarty_tpl->tpl_vars['current_version']->value;?>
</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div><?php }} ?>