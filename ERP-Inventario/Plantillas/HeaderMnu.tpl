<div class="main-header side-header sticky active" style="margin-bottom: -64px; background: #05be6a;">
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
                       {* <li class="dropdown main-header-notification">
                            <a class="nav-link icon" href="#" title="Notificaciones">
                                <i class="fa-solid fa-bell"></i> <span class="pulse bg-danger"></span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="header-navheading">
                                    <p class="main-notification-text">Tienes {$sMensaje['CCANMEN']}
                                        notificaciones</p>
                                </div>
                                {foreach from = $sDesMen item = k}
                                    <div class="main-notification-list">
                                        <a href="{$k['CENLACE']}" class="media new">
                                            <div class="main-img-user online">
                                                <img alt="Notificación" style="max-height: 80%"
                                                    src="css/menu/correo.png">
                                            </div>
                                            <div class="media-body">
                                                <p><strong>{$k['CDESCRI']}</strong><br><span>{$k['NCANTID']}
                                                        pendientes</span>
                                                </p>
                                            </div>
                                        </a>
                                    </div>
                                {/foreach}
                            </div>
                        </li> *}
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
