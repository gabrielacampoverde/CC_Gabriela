<div class="main-sidebar main-sidemenu main-sidebar-sticky side-menu ps ps--active-y">
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
            {foreach from = $scModule item = i}
            <li class="nav-item"  onclick="f_MostrarRoles('{$i['CCODMOD']}');" style="cursor:pointer;">
                <a class="nav-link">
                    <i class="fa-solid fa-desktop"></i>
                    <input type="hidden" name="paData[CCODMOD]" value="{$i['CCODMOD']}">
                    <span class="sidemenu-label" style="font-size: 11px;" >{$i['CDESMOD']}</span>
                    <i class="angle fe fe-chevron-right hor-angle"></i>
                </a>
            </li>
            {/foreach}
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
</div>