		<div class="navbar navbar-inverse navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container-fluid">
       		<button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="brand" href="{$base_url}"><img src="assets/img/connectme_logo_web.png" alt="ConnectME" /></a>
          <div class="nav-collapse collapse">
            <ul class="nav pull-right">
	            {foreach from=$language->main_menu item=menuitem name=menu key=menukey}
              <li class="dropdown">
              	<a href="{$menuitem->url}"{if isset($menuitem->children)} class="dropdown-toggle" data-hover="dropdown"{/if}{if isset($menuitem->attr)}{foreach from=($menuitem->attr) item=attr_val key=attr_key}{$attr_key}="{$attr_val}"{/foreach}{/if}>{$menuitem->title}{if isset($menuitem->children)} {if isset($menuitem->flag)}<img src="assets/img/flags/flag-{$menuitem->flag}.png" alt="{$menuitem->flag}" />{else}<b class="caret"></b>{/if}{/if}</a>
                {if isset($menuitem->children)}
                <ul class="dropdown-menu">
                	{foreach from=$menuitem->children item=menuitem_lvl2 name=menu_lvl2}
                  <li><a href="{$menuitem_lvl2->url}"{if isset($menuitem_lvl2->attr)}{foreach from=($menuitem_lvl2->attr) item=attr_val key=attr_key}{$attr_key}="{$attr_val}"{/foreach}{/if}>{$menuitem_lvl2->title}{if ($menukey == 'LOGOUT')} {$userid}{/if}</a></li>
                  {/foreach}
                  </ul>
                {/if}
              </li>
              {/foreach}
              <li><a href="#">{$current_version}</a></li>
            </ul>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>