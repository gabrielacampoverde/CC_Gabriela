$(function () {
    'use strict'

    // ______________ Page Loading
    $("#global-loader").fadeOut("slow");

    // ______________ Card
    const DIV_CARD = 'div.card';

    // ______________ Function for remove card
    $(document).on('click', '[data-bs-toggle="card-remove"]', function (e) {
        let $card = $(this).closest(DIV_CARD);
        $card.remove();
        e.preventDefault();
        return false;
    });

    // ______________ Functions for collapsed card
    $(document).on('click', '[data-bs-toggle="card-collapse"]', function (e) {
        let $card = $(this).closest(DIV_CARD);
        $card.toggleClass('card-collapsed');
        e.preventDefault();
        return false;
    });


    // ______________ Card full screen
    $(document).on('click', '[data-bs-toggle="card-fullscreen"]', function (e) {
        let $card = $(this).closest(DIV_CARD);
        $card.toggleClass('card-fullscreen').removeClass('card-collapsed');
        e.preventDefault();
        return false;
    });

    // ______________ COVER IMAGE
    $(".cover-image").each(function () {
        var attr = $(this).attr('data-bs-image-src');
        if (typeof attr !== typeof undefined && attr !== false) {
            $(this).css('background', 'url(' + attr + ') center center');
        }
    });

    // ______________Main-navbar
    if (window.matchMedia('(min-width: 992px)').matches) {
        $('.main-navbar .active').removeClass('show');
        $('.main-header-menu .active').removeClass('show');
    }
    $('.main-header .dropdown > a').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
    });
    $('.main-navbar .with-sub').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
    });
    $('.main-navbar .with-sub1').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
    });
    $('.main-navbar .with-sub2').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
    });
    $('.main-navbar .with-sub3').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
    });
    $('.dropdown-menu .main-header-arrow').on('click', function (e) {
        e.preventDefault();
        $(this).closest('.dropdown').removeClass('show');
    });
    $('#mainNavShow, #azNavbarShow').on('click', function (e) {
        e.preventDefault();
        $('body').toggleClass('main-navbar-show');
    });
    $('#mainContentLeftShow').on('click touch', function (e) {
        e.preventDefault();
        $('body').addClass('main-content-left-show');
    });
    $('#mainContentLeftHide').on('click touch', function (e) {
        e.preventDefault();
        $('body').removeClass('main-content-left-show');
    });
    $('#mainContentBodyHide').on('click touch', function (e) {
        e.preventDefault();
        $('body').removeClass('main-content-body-show');
    })

    // ______________Dropdown menu
    $(document).on('click touchstart', function (e) {
        e.stopPropagation();
        var dropTarg = $(e.target).closest('.main-header .dropdown').length;
        if (!dropTarg) {
            $('.main-header .dropdown').removeClass('show');
        }
        if (window.matchMedia('(min-width: 992px)').matches) {
            var navTarg = $(e.target).closest('.main-navbar .nav-item').length;
            if (!navTarg) {
                $('.main-navbar .show').removeClass('show');
            }
            var menuTarg = $(e.target).closest('.main-header-menu .nav-item').length;
            if (!menuTarg) {
                $('.main-header-menu .show').removeClass('show');
            }
            if ($(e.target).hasClass('main-menu-sub-mega')) {
                $('.main-header-menu .show').removeClass('show');
            }
        } else {
            if (!$(e.target).closest('#mainMenuShow').length) {
                var hm = $(e.target).closest('.main-header-menu').length;
                if (!hm) {
                    $('body').removeClass('main-header-menu-show');
                }
            }
        }
    });

    // ______________MainMenuShow
    $('#mainMenuShow').on('click', function (e) {
        e.preventDefault();
        $('body').toggleClass('main-header-menu-show');
    })
    $('.main-header-menu .with-sub').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
    })
    $('.main-header-menu-header .close').on('click', function (e) {
        e.preventDefault();
        $('body').removeClass('main-header-menu-show');
    })

    // ______________Tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    // ______________Popover
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })
    // ______________Toast
    $(".toast").toast();
    // ______________ Toast
    var toastElList = [].slice.call(document.querySelectorAll('.toast'))
    var toastList = toastElList.map(function (toastEl) {
        return new bootstrap.Toast(toastEl)
    })


    // ______________Back-top-button
    $(window).on("scroll", function (e) {
        if ($(this).scrollTop() > 0) {
            $('#back-to-top').fadeIn('slow');
        } else {
            $('#back-to-top').fadeOut('slow');
        }
    });
    $(document).on("click", "#back-to-top", function (e) {
        $("html, body").animate({
            scrollTop: 0
        }, 0);
        return false;
    });

    // ______________Full screen
    $(document).on("click", ".fullscreen-button", function toggleFullScreen() {
        $('html').addClass('fullscreenie');
        if ((document.fullScreenElement !== undefined && document.fullScreenElement === null) || (document.msFullscreenElement !== undefined && document.msFullscreenElement === null) || (document.mozFullScreen !== undefined && !document.mozFullScreen) || (document.webkitIsFullScreen !== undefined && !document.webkitIsFullScreen)) {
            if (document.documentElement.requestFullScreen) {
                document.documentElement.requestFullScreen();
            } else if (document.documentElement.mozRequestFullScreen) {
                document.documentElement.mozRequestFullScreen();
            } else if (document.documentElement.webkitRequestFullScreen) {
                document.documentElement.webkitRequestFullScreen(Element.ALLOW_KEYBOARD_INPUT);
            } else if (document.documentElement.msRequestFullscreen) {
                document.documentElement.msRequestFullscreen();
            }
        } else {
            $('html').removeClass('fullscreenie');
            if (document.cancelFullScreen) {
                document.cancelFullScreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitCancelFullScreen) {
                document.webkitCancelFullScreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    })

    // ______________Cover Image
    $(".cover-image").each(function () {
        var attr = $(this).attr('data-image-src');
        if (typeof attr !== typeof undefined && attr !== false) {
            $(this).css('background', 'url(' + attr + ') center center');
        }
    });


    // OFF-CANVAS STYLE
    $('.off-canvas').on('click', function () {
        $('body').addClass('overflow-y-scroll');
        $('body').addClass('pe-0');
    });


    function replay() {
        let replayButtom = document.querySelectorAll('.reply a')
        // Creating Div
        let Div = document.createElement('div')
        Div.setAttribute('class', "comment mt-4 d-grid")
        // creating textarea
        let textArea = document.createElement('textarea')
        textArea.setAttribute('class', "form-control")
        textArea.setAttribute('rows', "5")
        textArea.innerText = "Your Comment";
        // creating Cancel buttons
        let cancelButton = document.createElement('button');
        cancelButton.setAttribute('class', "btn btn-danger");
        cancelButton.innerText = "Cancel";

        let buttonDiv = document.createElement('div')
        buttonDiv.setAttribute('class', "btn-list ms-auto mt-2")

        // Creating submit button
        let submitButton = document.createElement('button');
        submitButton.setAttribute('class', "btn btn-success");
        submitButton.innerText = "Submit";

        // appending text are to div
        Div.append(textArea)
        Div.append(buttonDiv);
        buttonDiv.append(cancelButton);
        buttonDiv.append(submitButton);

        replayButtom.forEach((element, index) => {

            element.addEventListener('click', () => {
                let replay = $(element).parent()
                replay.append(Div)

                cancelButton.addEventListener('click', () => {
                    Div.remove()
                })
            })
        })


    }
    replay()


    // ______________ SWITCHER-toggle ______________//

    $('.layout-setting').on("click", function (e) {
        if (!(document.querySelector('body').classList.contains('dark-theme'))) {
            $('body').addClass('dark-theme');
            $('body').removeClass('light-theme');
            $('body').removeClass('transparent-theme');

            $('#myonoffswitch5').prop('checked', true);
            $('#myonoffswitch8').prop('checked', true);

            localStorage.setItem('dashleaddarkMode', true);
            localStorage.removeItem('dashleadlightMode');
            localStorage.removeItem('dashleadtransparentMode');
            $('#myonoffswitch2').prop('checked', true);
        } else {
            $('body').removeClass('dark-theme');
            $('body').addClass('light-theme');
            $('#myonoffswitch3').prop('checked', true);
            $('#myonoffswitch6').prop('checked', true);

            localStorage.setItem('dashleadlightMode', true);
            localStorage.removeItem('dashleadtransparentMode');
            localStorage.removeItem('dashleaddarkMode');;
            $('#myonoffswitch1').prop('checked', true);
        }
    });
    // LIGHT THEME START
    $(document).on("click", '#myonoffswitch1', function () {
        if (this.checked) {
            $('body').addClass('light-theme');
            $('#myonoffswitch3').prop('checked', true);
            $('#myonoffswitch6').prop('checked', true);
            $('body').removeClass('transparent-theme');
            $('body').removeClass('dark-theme');
            $('body').removeClass('dark-menu');
            $('body').removeClass('dark-header');

            // remove dark theme properties	
            localStorage.removeItem('dashleaddarkPrimary')

            // remove light theme properties
            localStorage.removeItem('dashleadprimaryColor')
            localStorage.removeItem('dashleadprimaryHoverColor')
            localStorage.removeItem('dashleadprimaryBorderColor')
            document.querySelector('html').style.removeProperty('--primary-bg-color', localStorage.darkPrimary);
            document.querySelector('html').style.removeProperty('--primary-bg-hover', localStorage.darkPrimary);
            document.querySelector('html').style.removeProperty('--primary-bg-border', localStorage.darkPrimary);
            document.querySelector('html').style.removeProperty('--dark-primary', localStorage.darkPrimary);

            // removing dark theme properties
            localStorage.removeItem('dashleaddarkPrimary')
            localStorage.removeItem('dashleadtransparentBgColor');
            localStorage.removeItem('dashleadtransparentThemeColor');
            localStorage.removeItem('dashleadtransparentPrimary');
            localStorage.removeItem('dashleaddarkprimaryTransparent');


            $('#myonoffswitch1').prop('checked', true);
            $('#myonoffswitch2').prop('checked', false);
            $('#myonoffswitchTransparent').prop('checked', false);
            localStorage.removeItem('dashleadtransparentBgImgPrimary');
            localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');

            checkOptions();
            const root = document.querySelector(':root');
            root.style = "";
            names()
        } else {
            $('body').removeClass('light-theme');
            localStorage.removeItem("dashleadlight-theme");
        }
        localStorageBackup();
    });
    // LIGHT THEME END

    // DARK THEME START
    $(document).on("click", '#myonoffswitch2', function () {
        if (this.checked) {
            $('body').addClass('dark-theme');
            $('#myonoffswitch2').prop('checked', true);
            $('#myonoffswitch5').prop('checked', true);
            $('#myonoffswitch8').prop('checked', true);
            $('body').removeClass('light-theme');
            $('body').removeClass('transparent-theme');
            $('body').removeClass('light-menu');
            $('body').removeClass('light-header');
            checkOptions();

            // remove light theme properties
            localStorage.removeItem('dashleadprimaryColor')
            localStorage.removeItem('dashleadprimaryHoverColor')
            localStorage.removeItem('dashleadprimaryBorderColor')
            localStorage.removeItem('dashleaddarkPrimary')
            document.querySelector('html').style.removeProperty('--primary-bg-color', localStorage.darkPrimary);
            document.querySelector('html').style.removeProperty('--primary-bg-hover', localStorage.darkPrimary);
            document.querySelector('html').style.removeProperty('--primary-bg-border', localStorage.darkPrimary);
            document.querySelector('html').style.removeProperty('--dark-primary', localStorage.darkPrimary);

            // removing light theme data 
            localStorage.removeItem('dashleadprimaryColor')
            localStorage.removeItem('dashleadprimaryHoverColor')
            localStorage.removeItem('dashleadprimaryBorderColor')
            localStorage.removeItem('dashleadprimaryTransparent');

            $('#myonoffswitch1').prop('checked', false);
            $('#myonoffswitch2').prop('checked', true);
            $('#myonoffswitchTransparent').prop('checked', false);
            //
            checkOptions();

            localStorage.removeItem('dashleadtransparentBgColor');
            localStorage.removeItem('dashleadtransparentThemeColor');
            localStorage.removeItem('dashleadtransparentPrimary');
            localStorage.removeItem('dashleadtransparentBgImgPrimary');
            localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');
            const root = document.querySelector(':root');
            root.style = "";
            names()
        } else {
            $('body').removeClass('dark-theme');
            localStorage.removeItem("dashleaddark-theme");
        }
        localStorageBackup()
    });
    // DARK THEME END

    // TRANSPARENT THEME START
    $(document).on("click", '#myonoffswitchTransparent', function () {
        if (this.checked) {
            $('body').addClass('transparent-theme');
            $('#myonoffswitch3').prop('checked', false);
            $('#myonoffswitch6').prop('checked', false);
            $('#myonoffswitch5').prop('checked', false);
            $('#myonoffswitch8').prop('checked', false);
            $('body').removeClass('dark-theme');
            $('body').removeClass('light-theme');

            // remove light theme properties
            localStorage.removeItem('dashleadprimaryColor')
            localStorage.removeItem('dashleadprimaryHoverColor')
            localStorage.removeItem('dashleadprimaryBorderColor')

            // removing light theme data 
            localStorage.removeItem('dashleaddarkPrimary');
            localStorage.removeItem('dashleadprimaryColor')
            localStorage.removeItem('dashleadprimaryHoverColor')
            localStorage.removeItem('dashleadprimaryBorderColor')
            localStorage.removeItem('dashleadprimaryTransparent');
            localStorage.removeItem('dashleadtransparentPrimary');
            localStorage.removeItem('dashleaddarkprimaryTransparent');
            localStorage.removeItem('dashleadtransparentBgImgPrimary');
            localStorage.removeItem('dashleadtransparentBgImgprimaryTransparent');

            $('#myonoffswitch2').prop('checked', false);
            $('#myonoffswitch1').prop('checked', false);
            $('#myonoffswitchTransparent').prop('checked', true);
            //
            checkOptions();

            const root = document.querySelector(':root');
            root.style = "";
            names()
        } else {
            $('body').removeClass('transparent-theme');
            localStorage.removeItem("dashleadtransparent-theme");
        }
        localStorageBackup()
        $('body').removeClass('bg-img1');
        $('body').removeClass('bg-img2');
        $('body').removeClass('bg-img3');
        $('body').removeClass('bg-img4');
    });
    // TRANSPARENT THEME END

    // BACKGROUND IMAGE STYLE START

    $(document).on("click", '#bgimage1', function () {
        $('body').addClass('bg-img1');
        $('body').removeClass('bg-img2');
        $('body').removeClass('bg-img3');
        $('body').removeClass('bg-img4');

        document.querySelector('body').classList.add('transparent-theme');
        document.querySelector('body').classList.remove('light-theme');
        document.querySelector('body').classList.remove('dark-theme');

        $('#myonoffswitch2').prop('checked', false);
        $('#myonoffswitch1').prop('checked', false);
        $('#myonoffswitchTransparent').prop('checked', true);

        checkOptions();
    })

    $(document).on("click", '#bgimage2', function () {
        $('body').addClass('bg-img2');
        $('body').removeClass('bg-img1');
        $('body').removeClass('bg-img3');
        $('body').removeClass('bg-img4');

        document.querySelector('body').classList.add('transparent-theme');
        document.querySelector('body').classList.remove('light-theme');
        document.querySelector('body').classList.remove('dark-theme');

        $('#myonoffswitch2').prop('checked', false);
        $('#myonoffswitch1').prop('checked', false);
        $('#myonoffswitchTransparent').prop('checked', true);

        checkOptions();
    })

    $(document).on("click", '#bgimage3', function () {
        $('body').addClass('bg-img3');
        $('body').removeClass('bg-img1');
        $('body').removeClass('bg-img2');
        $('body').removeClass('bg-img4');

        document.querySelector('body').classList.add('transparent-theme');
        document.querySelector('body').classList.remove('light-theme');
        document.querySelector('body').classList.remove('dark-theme');

        $('#myonoffswitch2').prop('checked', false);
        $('#myonoffswitch1').prop('checked', false);
        $('#myonoffswitchTransparent').prop('checked', true);

        checkOptions();
    })

    $(document).on("click", '#bgimage4', function () {
        $('body').addClass('bg-img4');
        $('body').removeClass('bg-img1');
        $('body').removeClass('bg-img2');
        $('body').removeClass('bg-img3');

        document.querySelector('body').classList.add('transparent-theme');
        document.querySelector('body').classList.remove('light-theme');
        document.querySelector('body').classList.remove('dark-theme');
        $('#myonoffswitch2').prop('checked', false);
        $('#myonoffswitch1').prop('checked', false);
        $('#myonoffswitchTransparent').prop('checked', true);

        checkOptions();
    })

    // BACKGROUND IMAGE STYLE END

    // DEFAULT SIDEMENU START
    $(document).on("click", '#myonoffswitch13', function () {
        if (this.checked) {
            $('body').addClass('default-menu');
            $('body').removeClass('main-sidebar-hide');
            hovermenu();
            $('body').removeClass('icontext-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('hover-submenu1');
        } else {
            $('body').removeClass('default-menu');
        }
    });
    // DEFAULT SIDEMENU END

    // ICON OVERLAY SIDEMENU START
    $(document).on("click", '#myonoffswitch15', function () {
        if (this.checked) {
            $('body').addClass('icon-overlay');
            hovermenu();
            $('body').addClass('main-sidebar-hide');
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('icontext-menu');
        } else {
            $('body').removeClass('icon-overlay');
            $('body').removeClass('main-sidebar-hide');
        }
    });
    // ICON OVERLAY SIDEMENU END

    // ICONTEXT SIDEMENU START
    $(document).on("click", '#myonoffswitch14', function () {
        if (this.checked) {
            $('body').addClass('icontext-menu');
            icontext();
            $('body').addClass('main-sidebar-hide');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
        } else {
            $('body').removeClass('icontext-menu');
            $('body').removeClass('main-sidebar-hide');
        }
    });

    // ICONTEXT SIDEMENU END

    // CLOSED SIDEMENU START
    $(document).on("click", '#myonoffswitch16', function () {
        if (this.checked) {
            $('body').addClass('closed-menu');
            $('body').addClass('main-sidebar-hide');
            $('body').removeClass('default-menu');
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('icontext-menu');

        } else {
            $('body').removeClass('closed-menu');
            $('body').removeClass('main-sidebar-hide');
            $('body').addClass('default-menu');
        }
    });
    // CLOSED SIDEMENU END

    // HOVER SUBMENU START
    $(document).on("click", '#myonoffswitch17', function () {
        if (this.checked) {
            $('body').addClass('hover-submenu');
            hovermenu();
            $('body').addClass('main-sidebar-hide');
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('icontext-menu');
            $('.app-sidebar').removeClass('sidemenu-scroll');
        } else {
            $('body').removeClass('hover-submenu');
            $('body').removeClass('main-sidebar-hide');
        }
    });
    // HOVER SUBMENU END

    // HOVER SUBMENU STYLE-1 START
    $(document).on("click", '#myonoffswitch18', function () {
        if (this.checked) {
            $('body').addClass('hover-submenu1');
            hovermenu();
            $('body').addClass('main-sidebar-hide');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('default-menu');
            $('body').removeClass('closed-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('icontext-menu');
            $('.app-sidebar').removeClass('sidemenu-scroll');
        } else {
            $('body').removeClass('hover-submenu1');
            $('body').removeClass('main-sidebar-hide');
        }
    });
    // HOVER SUBMENU STYLE-1 END

    // ACCORDION STYLE
    $(document).on("click", '[data-bs-toggle="collapse"]', function () {
        $(this).toggleClass('active').siblings().removeClass('active');
    });

    // SIDE-MENU
    $(document).on("click", '#myonoffswitch34', function () {
            if (this.checked) {
    ActiveSubmenu();
            $('body').removeClass('horizontal');
            $('body').removeClass('horizontal-hover');
            $(".main-content").removeClass("hor-content");
            $(".main-container").removeClass("container");
            $(".side-menu").removeClass("sticky");
            $(".main-container").addClass("container-fluid");
            $(".main-navbar").addClass("main-sidebar");
            $(".main-sidebar").removeClass("main-navbar");
            $(".main-sidebar").addClass("nav");
            $(".main-sidebar").addClass("main-sidebar-sticky");
            $(".main-sidebar").removeClass("main-navbar-sticky");
            //$(".main-sidebar").addClass("ps");
            $(".container").addClass("main-sidebar-body");
            $(".side-header").removeClass("hor-header");
            $(".hor-header").addClass("side-header");
            $(".app-sidebar").removeClass("horizontal-main")
            $(".main-sidebar-body").removeClass("container")
            $(".slide-menu").removeClass("ps")
            $(".slide-menu").removeClass("ps--active-y")
            $('#slide-left').removeClass('d-none');
            $('#slide-right').removeClass('d-none');
            $('body').addClass('sidebar-mini');
            localStorage.removeItem("dashleadhorizontal");
            localStorage.removeItem("dashleadhorizontalHover");
            localStorage.setItem("dashleadvertical", true);
            responsive();
        } else {
            $('body').removeClass('sidebar-mini');
        }
    });
    // SIDE-MENU END

    // HORIZONTAL
    $(document).on("click", '#myonoffswitch35', function () {
        if (this.checked) {
            if (window.innerWidth >= 992) {
                removeActive()
            }
            else{
            ActiveSubmenu();
            }
            $('body').addClass('horizontal');
            $('body').removeClass('horizontal-hover');
            $(".main-content").addClass("hor-content");
            $(".main-content").removeClass("app-content");
            $(".main-container").addClass("container");
            $(".main-container").removeClass("container-fluid");
            $(".main-sidebar").addClass("main-navbar");
            $(".main-sidebar").addClass("sticky");
            $(".main-navbar").removeClass("main-sidebar-sticky")
            $(".main-navbar").removeClass("main-sidebar");
            $(".main-navbar").removeClass("nav");
            // $(".main-navbar").removeClass(" ps ");
            $(".side-header").addClass("hor-header");
            $(".hor-header").removeClass("side-header");
            $(".hor-header").removeClass("fixed-header");
            $(".main-sidebar-body").addClass("container")
            $(".main-sidebar-body").addClass("main-sidemenu")
            $(".main-sidemenu").removeClass("main-sidebar-body")
            $('body').removeClass('sidebar-mini');
            $('body').removeClass('main-sidebar-hide');
            $('body').removeClass('default-menu');
            $('body').removeClass('icontext-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('hover-submenu1');
            localStorage.setItem("dashleadhorizontal", true);
            localStorage.removeItem("dashleadhorizontalHover");
            localStorage.removeItem("dashleadvertical");
            $('#slide-left').removeClass('d-none');
            $('#slide-right').removeClass('d-none');
            document.querySelector('.horizontal .nav.hor-menu').style.flexWrap = 'nowrap'
            // $('#slide-left').addClass('d-none');
            // $('#slide-right').addClass('d-none');
            // document.querySelector('.horizontal .nav').style.flexWrap = 'wrap'
            if (!document.querySelector('body').classList.contains('login-img')) {
                checkHoriMenu();
                responsive();
            }
        } 
    });
    // HORIZONTAL END

    // HORIZONTAL HOVER
    $(document).on("click", '#myonoffswitch111', function () {
        if (this.checked) {
            if (window.innerWidth >= 992) {
                removeActive()
            }
            else{
            ActiveSubmenu();
            }
            $('body').addClass('horizontal-hover');
            $('body').addClass('horizontal');
            $(".main-content").addClass("hor-content");
            $(".main-container").addClass("container");
            $(".main-container").removeClass("container-fluid");
            $(".main-sidebar").addClass("main-navbar");
            $(".main-sidebar").addClass("sticky");
            $(".main-navbar").removeClass("main-sidebar-sticky")
            $(".main-navbar").removeClass("main-sidebar");
            $(".main-navbar").removeClass("nav");
            // $(".main-navbar").removeClass(" ps ");
            $(".side-header").addClass("hor-header");
            $(".hor-header").removeClass("side-header");
            $(".hor-header").removeClass("fixed-header");
            $(".main-sidebar-body").addClass("container")
            $(".main-sidebar-body").addClass("main-sidemenu")
            $(".main-sidemenu").removeClass("main-sidebar-body")
            $('body').removeClass('sidebar-mini');
            $('body').removeClass('main-sidebar-hide');
            $('body').removeClass('default-menu');
            $('body').removeClass('icontext-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('hover-submenu1');
            localStorage.setItem("dashleadhorizontalHover", true);
            localStorage.removeItem("dashleadhorizontal");
            localStorage.removeItem("dashleadvertical");
            $('#slide-left').removeClass('d-none');
            $('#slide-right').removeClass('d-none');
            document.querySelector('.horizontal .nav.hor-menu').style.flexWrap = 'nowrap'
            // $('#slide-left').addClass('d-none');
            // $('#slide-right').addClass('d-none');
            // document.querySelector('.horizontal .nav').style.flexWrap = 'wrap'
            if (!document.querySelector('body').classList.contains('login-img')) {
                checkHoriMenu();
                responsive();
            }
        } 

    });
    // HORIZONTAL HOVER END

    // RTL STYLE START
    $(document).on("click", '#myonoffswitch24', function () {
        if (this.checked) {
            $('body').addClass('rtl');
            $('#slide-left').removeClass('d-none');
            $('#slide-right').removeClass('d-none');
            $("html[lang=en]").attr("dir", "rtl");
            $('body').removeClass('ltr');
            localStorage.setItem("dashleadrtl", true);
            localStorage.removeItem("dashleadltr");
            $("head link#style").attr("href", $(this));
            (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
            var carousel = $('.owl-carousel');
            $.each(carousel, function (index, element) {
                // element == this
                var carouselData = $(element).data('owl.carousel');
                carouselData.settings.rtl = true; //don't know if both are necessary
                carouselData.options.rtl = true;
                $(element).trigger('refresh.owl.carousel');
            });
        } else {
            $('body').removeClass('rtl');
            $('body').addClass('ltr');
            $("head link#style").attr("href", $(this));
            (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.min.css"));
        }
    });
    // RTL STYLE END

    // LTR STYLE START
    $(document).on("click", '#myonoffswitch23', function () {
        if (this.checked) {
            $('body').addClass('ltr');
            $('body').removeClass('rtl');
            $('#slide-left').removeClass('d-none');
            $('#slide-right').removeClass('d-none');
            $("html[lang=en]").attr("dir", "ltr");
            localStorage.setItem("dashleadltr", true);
            localStorage.removeItem("dashleadrtl");
            $("head link#style").attr("href", $(this));
            (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.min.css"));
            var carousel = $('.owl-carousel');
            $.each(carousel, function (index, element) {
                // element == this
                var carouselData = $(element).data('owl.carousel');
                carouselData.settings.rtl = false; //don't know if both are necessary
                carouselData.options.rtl = false;
                $(element).trigger('refresh.owl.carousel');
            });
        } else {
            $('body').removeClass('ltr');
            $('body').addClass('rtl');
            $("head link#style").attr("href", $(this));
            (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
        }
    });
    // LTR STYLE END

    // FULL WIDTH LAYOUT START
    $(document).on("click", '#myonoffswitch9', function () {
        if (this.checked) {
            $('body').addClass('layout-fullwidth');
            $('body').removeClass('layout-boxed');
            checkHoriMenu();
        } else {
            $('body').removeClass('layout-fullwidth');
        }
    });
    // FULL WIDTH LAYOUT END

    // BOXED LAYOUT START
    $(document).on("click", '#myonoffswitch10', function () {
        if (this.checked) {
            $('body').addClass('layout-boxed');
            $('body').removeClass('layout-fullwidth');
            checkHoriMenu();
        } else {
            $('body').removeClass('layout-boxed');
        }
    });
    // BOXED LAYOUT END

    // HEADER POSITION STYLES START
    $(document).on("click", '#myonoffswitch11', function () {
        if (this.checked) {
            $('body').addClass('fixed-layout');
            $('body').removeClass('scrollable-layout');
        } else {
            $('body').removeClass('fixed-layout');
        }
    });
    $(document).on("click", '#myonoffswitch12', function () {
        if (this.checked) {
            $('body').addClass('scrollable-layout');
            $('body').removeClass('fixed-layout');
        } else {
            $('body').removeClass('scrollable-layout');
        }
    });
    // HEADER POSITION STYLES END

    /*Header Styles*/

    // LIGHT HEADER START
    $(document).on("click", '#myonoffswitch6', function () {
        if (this.checked) {
            $('body').addClass('light-header');
            $('body').removeClass('color-header');
            $('body').removeClass('gradient-header');
            $('body').removeClass('dark-header');
        }
        else {
            $('body').removeClass('light-header');
        }
    });
    // LIGHT HEADER END

    // COLOR HEADER START
    $(document).on("click", '#myonoffswitch7', function () {
        if (this.checked) {
            $('body').addClass('color-header');
            $('body').removeClass('light-header');
            $('body').removeClass('gradient-header');
            $('body').removeClass('dark-header');
        }
        else {
            $('body').removeClass('color-header');
        }
    });
    // COLOR HEADER END

    // GRADIENT HEADER START
    $(document).on("click", '#myonoffswitch20', function () {
        if (this.checked) {
            $('body').addClass('gradient-header');
            $('body').removeClass('light-header');
            $('body').removeClass('color-header');
            $('body').removeClass('dark-header');
        }
        else {
            $('body').removeClass('gradient-header');
        }
    });
    // GRADIENT HEADER END

    // DARK HEADER START
    $(document).on("click", '#myonoffswitch8', function () {
        if (this.checked) {
            $('body').addClass('dark-header');
            $('body').removeClass('color-header');
            $('body').removeClass('light-header');
            $('body').removeClass('gradient-header');
        } else {
            $('body').removeClass('dark-header');
        }
    });
    // DARK HEADER END

    /*Menu Styles*/

    // LIGHT menu START
    $(document).on("click", '#myonoffswitch3', function () {
        if (this.checked) {
            $('body').addClass('light-menu');
            $('body').removeClass('color-menu');
            $('body').removeClass('dark-menu');
            $('body').removeClass('gradient-menu');
        } else {
            $('body').removeClass('light-menu');
        }
    });
    // LIGHT menu END

    // COLOR menu START
    $(document).on("click", '#myonoffswitch4', function () {
        if (this.checked) {
            $('body').addClass('color-menu');
            $('body').removeClass('light-menu');
            $('body').removeClass('dark-menu');
            $('body').removeClass('gradient-menu');
        } else {
            $('body').removeClass('color-menu');
        }
    });
    // COLOR menu END

    // DARK menu START
    $(document).on("click", '#myonoffswitch5', function () {
        if (this.checked) {
            $('body').addClass('dark-menu');
            $('body').removeClass('color-menu');
            $('body').removeClass('light-menu');
            $('body').removeClass('gradient-menu');
        } else {
            $('body').removeClass('dark-menu');
        }
    });
    // DARK menu END

    // GRADIENT menu START
    $(document).on("click", '#myonoffswitch19', function () {
        if (this.checked) {
            $('body').addClass('gradient-menu');
            $('body').removeClass('color-menu');
            $('body').removeClass('light-menu');
            $('body').removeClass('dark-menu');
        } else {
            $('body').removeClass('gradient-menu');
        }
    });
    // GRADIENT menu END
    function light() {
        "use strict";
        if (document.querySelector('body').classList.contains('light-theme')) {
            $('#myonoffswitch3').prop('checked', true);
            $('#myonoffswitch6').prop('checked', true);
        }
    }
    light();
    

    // RTL STYLE START
    let bodyRtl = $('body').hasClass('rtl');
    if (bodyRtl) {
        $('body').addClass('rtl');
        $("html[lang=en]").attr("dir", "rtl");
        $('body').removeClass('ltr');
        $("head link#style").attr("href", $(this));
        (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.rtl.min.css"));
        var carousel = $('.owl-carousel');
        $.each(carousel, function (index, element) {
            // element == this
            var carouselData = $(element).data('owl.carousel');
            carouselData.settings.rtl = true; //don't know if both are necessary
            carouselData.options.rtl = true;
            $(element).trigger('refresh.owl.carousel');
        });
    }
    // RTL STYLE END

// HORIZONTAL has class

let bodyhorizontal = $('body').hasClass('horizontal');

if (bodyhorizontal) {
    $('body').addClass('horizontal');
    $(".main-content").addClass("hor-content");
    $(".main-content").removeClass("app-content");
    $(".main-container").addClass("container");
    $(".main-container").removeClass("container-fluid");
    $(".main-sidebar").addClass("main-navbar");
    $(".main-sidebar").addClass("sticky");
    $(".main-navbar").removeClass("main-sidebar-sticky")
    $(".main-navbar").removeClass("main-sidebar");
    $(".main-navbar").removeClass("nav");
    $(".main-navbar").removeClass("main-sidebar");
    $(".side-header").addClass("hor-header");
    $(".hor-header").removeClass("side-header");
    $(".hor-header").removeClass("fixed-header");
    $(".app-sidebar").addClass("horizontal-main")
    $(".main-sidebar-body").addClass("container")
    $(".main-sidebar-body").addClass("main-sidemenu")
    $(".main-sidemenu").removeClass("main-sidebar-body")
    $('body').removeClass('sidebar-mini');
    $('body').removeClass('main-sidebar-hide');
    $('body').removeClass('horizontal-hover');
    $('body').removeClass('default-menu');
    $('body').removeClass('icontext-menu');
    $('body').removeClass('icon-overlay');
    $('body').removeClass('closed-menu');
    $('body').removeClass('hover-submenu');
    $('body').removeClass('hover-submenu1');
    $('#slide-left').removeClass('d-none');
    $('#slide-right').removeClass('d-none');
    // $('#slide-left').addClass('d-none');
    // $('#slide-right').addClass('d-none');
    // document.querySelector('.horizontal .nav').style.flexWrap = 'wrap'
    if (!document.querySelector('body').classList.contains('login-img')) {
        if (window.innerWidth >= 992) {
            removeActive()
        }
        else{
        ActiveSubmenu();
        }
        checkHoriMenu();
        responsive();
        document.querySelector('.horizontal .nav.hor-menu').style.flexWrap = 'nowrap'
    }
}

// HORIZONTAL-HOVER has class

let bodyhorizontalHover = $('body').hasClass('horizontal-hover');
    if (bodyhorizontalHover) {

            $('body').addClass('horizontal-hover');
            $('body').addClass('horizontal');
            $(".main-content").addClass("hor-content");
            $(".main-container").addClass("container");
            $(".main-container").removeClass("container-fluid");
            $(".main-sidebar").addClass("main-navbar");
            $(".main-sidebar").addClass("sticky");
            $(".main-navbar").removeClass("main-sidebar-sticky")
            $(".main-navbar").removeClass("main-sidebar");
            $(".main-navbar").removeClass("nav");
            // $(".main-navbar").removeClass(" ps ");
            $(".side-header").addClass("hor-header");
            $(".hor-header").removeClass("side-header");
            $(".hor-header").removeClass("fixed-header");
            $(".main-sidebar-body").addClass("container")
            $(".main-sidebar-body").addClass("main-sidemenu")
            $(".main-sidemenu").removeClass("main-sidebar-body")
            $('body').removeClass('sidebar-mini');
            $('body').removeClass('main-sidebar-hide');
            $('body').removeClass('default-menu');
            $('body').removeClass('icontext-menu');
            $('body').removeClass('icon-overlay');
            $('body').removeClass('closed-menu');
            $('body').removeClass('hover-submenu');
            $('body').removeClass('hover-submenu1');
            localStorage.setItem("dashleadhorizontal", true);
            localStorage.removeItem("dashleadhorizontalHover");
            localStorage.removeItem("dashleadvertical");
            $('#slide-left').removeClass('d-none');
            $('#slide-right').removeClass('d-none');
            // document.querySelector('.horizontal .nav.hor-menu').style.flexWrap = 'nowrap'
            // $('#slide-left').addClass('d-none');
            // $('#slide-right').addClass('d-none');
            // document.querySelector('.horizontal .nav').style.flexWrap = 'wrap'
            if (!document.querySelector('body').classList.contains('login-img')) {
            if (window.innerWidth >= 992) {
                removeActive()
            }
            else{
            ActiveSubmenu();
            }
            checkHoriMenu();
            responsive();
            document.querySelector('.horizontal .nav.hor-menu').style.flexWrap = 'nowrap'
        }
            
    }
});


// RESET SWITCHER TO DEFAULT
function resetData() {
    "use strict";
    $('#myonoffswitch34').prop('checked', true);
    $('#myonoffswitch3').prop('checked', true);
    $('#myonoffswitch6').prop('checked', true);
    $('#myonoffswitch1').prop('checked', true);
    $('#myonoffswitch9').prop('checked', true);
    $('#myonoffswitch11').prop('checked', true);
    $('#myonoffswitch13').prop('checked', true);
    $('body')?.removeClass('bg-img4');
    $('body')?.removeClass('bg-img1');
    $('body')?.removeClass('bg-img2');
    $('body')?.removeClass('bg-img3');
    $('body')?.removeClass('transparent-theme');
    $('body')?.removeClass('dark-theme');
    $('body')?.removeClass('dark-menu');
    $('body')?.removeClass('color-menu');
    $('body')?.removeClass('gradient-menu');
    $('body')?.removeClass('dark-header');
    $('body')?.removeClass('color-header');
    $('body')?.removeClass('gradient-header');
    $('body')?.removeClass('layout-boxed');
    $('body')?.removeClass('icontext-menu');
    $('body')?.removeClass('icon-overlay');
    $('body')?.removeClass('closed-menu');
    $('body')?.removeClass('hover-submenu');
    $('body')?.removeClass('hover-submenu1');
    $('body')?.removeClass('main-sidebar-hide');
    $('body')?.removeClass('scrollable-layout');
    names();
    ActiveSubmenu();

    $('body').removeClass('horizontal');
    $('body').removeClass('horizontal-hover');
    $(".main-content").removeClass("hor-content");
    $(".main-container").removeClass("container");
    $(".main-container").addClass("container-fluid");
    $(".main-navbar").addClass("main-sidebar");
    $(".main-sidebar").removeClass("main-navbar");
    $(".main-sidebar").addClass("nav");
    $(".main-sidebar").addClass("main-sidebar-sticky");
    $(".main-sidebar").removeClass("main-navbar-sticky");
    //$(".main-sidebar").addClass("ps");
    $(".container").addClass("main-sidebar-body");
    $(".side-header").removeClass("hor-header");
    $(".hor-header").addClass("side-header");
    $(".main-sidebar-body").removeClass("container")
    $(".slide-menu").removeClass("ps")
    $(".slide-menu").removeClass("ps--active-y")
    $('#slide-left').removeClass('d-none');
    $('#slide-right').removeClass('d-none');
    $('body').addClass('sidebar-mini');
    localStorage.removeItem("dashleadhorizontal");
    localStorage.removeItem("dashleadhorizontalHover");
    localStorage.setItem("dashleadvertical", true);
    responsive();


    $('body').addClass('ltr');
    $('#slide-left').removeClass('d-none');
    $('#slide-right').removeClass('d-none');
    $("html[lang=en]").attr("dir", "ltr");
    $('body').removeClass('rtl');
    localStorage.setItem("dashleadltr", true);
    localStorage.removeItem("dashleadrtl");
    $("head link#style").attr("href", $(this));
    (document.getElementById("style").setAttribute("href", "../assets/plugins/bootstrap/css/bootstrap.min.css"));
    var carousel = $('.owl-carousel');
    $.each(carousel, function (index, element) {
        // element == this
        var carouselData = $(element).data('owl.carousel');
        carouselData.settings.rtl = false; //don't know if both are necessary
        carouselData.options.rtl = false;
        $(element).trigger('refresh.owl.carousel');
    });
}


