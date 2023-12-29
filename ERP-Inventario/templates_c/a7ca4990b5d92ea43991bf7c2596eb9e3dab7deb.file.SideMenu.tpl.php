<?php /* Smarty version Smarty-3.1.8, created on 2023-12-14 17:06:19
         compiled from "Plantillas/SideMenu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:1573605628657b27fb93eb30-36189382%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'a7ca4990b5d92ea43991bf7c2596eb9e3dab7deb' => 
    array (
      0 => 'Plantillas/SideMenu.tpl',
      1 => 1695224496,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1573605628657b27fb93eb30-36189382',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'scModule' => 0,
    'i' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_657b27fb949e44_21976100',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_657b27fb949e44_21976100')) {function content_657b27fb949e44_21976100($_smarty_tpl) {?><div class="main-sidebar main-sidemenu main-sidebar-sticky side-menu ps ps--active-y">
    <div class="sidemenu-logo" style="background: #05be6a;">
        <a class="main-logo" href="#">
            <img src="img/logo_ucsm_4.png" class="header-brand-img desktop-logo" alt="logo">
            <img src="img/logo_ucsm.png" class="header-brand-img icon-logo" alt="logo">
            <img src="img/logo_ucsm_1.png" class="header-brand-img desktop-logo theme-logo" alt="logo">
            <img src="img/logo_ucsm.png" class="header-brand-img icon-logo theme-logo" alt="logo">
        </a>
    </div>
    <div class="main-sidebar-body">
        <div class="slide-left disabled active d-none" id="slide-left">
            <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24"
                viewBox="0 0 24 24">
                <path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"></path>
            </svg>
        </div>
        <ul class="nav hor-menu" style="margin-left: 0px; margin-right: 0px;">
            <?php  $_smarty_tpl->tpl_vars['i'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['i']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['scModule']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['i']->key => $_smarty_tpl->tpl_vars['i']->value){
$_smarty_tpl->tpl_vars['i']->_loop = true;
?>
            <li class="nav-item"  onclick="f_MostrarRoles('<?php echo $_smarty_tpl->tpl_vars['i']->value['CCODMOD'];?>
');" style="cursor:pointer;">
                <a class="nav-link">
                    <i class="fa-solid fa-desktop"></i>
                    <input type="hidden" name="paData[CCODMOD]" value="<?php echo $_smarty_tpl->tpl_vars['i']->value['CCODMOD'];?>
">
                    <span class="sidemenu-label" style="font-size: 11px;" ><?php echo $_smarty_tpl->tpl_vars['i']->value['CDESMOD'];?>
</span>
                    <i class="angle fe fe-chevron-right hor-angle"></i>
                </a>
            </li>
            <?php } ?>
        </ul>
        <div class="slide-right" id="slide-right">
            <svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24"
                viewBox="0 0 24 24">
                <path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z">
                </path>
            </svg>
        </div>
    </div>
    <div class="ps__rail-x" style="left: 0px; bottom: 0px;">
        <div class="ps__thumb-x" tabindex="0" style="left: 0px; width: 0px;"></div>
    </div>
    <div class="ps__rail-y" style="top: 0px; height: 754px; right: 0px;">
        <div class="ps__thumb-y" tabindex="0" style="top: 0px; height: 661px;"></div>
    </div>
</div><?php }} ?>