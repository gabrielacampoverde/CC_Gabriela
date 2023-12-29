<?php /* Smarty version Smarty-3.1.8, created on 2023-12-14 17:06:19
         compiled from "Plantillas/HeaderMnu.tpl" */ ?>
<?php /*%%SmartyHeaderCode:1864236096657b27fb93c476-62849291%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '87aad158aadcad09996492b296ca2ed52e92c3f9' => 
    array (
      0 => 'Plantillas/HeaderMnu.tpl',
      1 => 1695224492,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '1864236096657b27fb93c476-62849291',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_657b27fb93d103_43570958',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_657b27fb93d103_43570958')) {function content_657b27fb93d103_43570958($_smarty_tpl) {?><div class="main-header side-header sticky active" style="margin-bottom: -64px; background: #05be6a;">
    <div class="container-fluid main-container">
        <div class="main-header-left sidemenu">
            <a class="main-header-menu-icon" href="Mnu1000.php" id="mainSidebarToggle"><span></span></a>
        </div>
        <a class="main-header-menu-icon  horizontal  d-lg-none" href="Mnu1000.php"
            id="mainNavShow"><span></span></a>
        <div class="main-header-left horizontal"><a class="main-logo" href="Mnu1000.php">
            <img src="img/logo_ucsm.png" href="Mnu1000.php" class="header-brand-img desktop-logo" alt="logo">
            <img src="img/logo_ucsm.png" href="Mnu1000.php" class="header-brand-img desktop-logo theme-logo" alt="logo">
            </a></div>
        <div class="main-header-right">
            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto collapsed"
                type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4"
                aria-controls="navbarSupportedContent-4" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon fe fe-more-vertical "></span>
            </button>
            <div class="navbar navbar-expand-lg navbar-collapse responsive-navbar p-0">
                <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                    <ul class="nav nav-item header-icons navbar-nav-right ms-auto">
                        <!-- Theme-Layout -->
                        <div class="dropdown d-flex" >
                            <a class="nav-link icon theme-layout nav-link-bg layout-setting" title="Modo nocturno">
                                <span class="dark-layout"><i class="fa-regular fa-moon"></i></i></span>
                                <span class="light-layout"><i class="fa-regular fa-sun"></i></span>
                            </a>
                        </div>
                        <li class="dropdown">
                            <a class="nav-link icon full-screen-link" onclick="f_setFullScreen()" title="Mostrar pantalla completa">
                                <i class="fa-solid fa-expand"></i>
                            </a>
                        </li>
                       
                        <li class="dropdown main-profile-menu"><a class="nav-link icon" href="#" title="Perfil">
                                <i class="fa-solid fa-user"></i></a>
                            <div class="dropdown-menu">
                                <a class="dropdown-item border-top text-wrap" href="Adm1080.php"><i
                                        class="fa-solid fa-user"></i>Mi Cuenta</a>
                                <a class="dropdown-item border-top text-wrap" href="Adm1070.php"><i
                                        class="fa-solid fa-key"></i>Cambiar Contraseña</a>
                                <a class="dropdown-item text-wrap" href="index.php"><i
                                        class="fa-solid fa-right-from-bracket"></i>Cerrar Sesión</a>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }} ?>