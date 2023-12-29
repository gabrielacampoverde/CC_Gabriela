$(function () {
    "use strict";

    $('.main-sidebar .with-sub').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');
    })
    $('.main-sidebar .with-sub1').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');
    })
    $('.main-sidebar .with-sub2').on('click', function (e) {
        e.preventDefault();
        $(this).parent().toggleClass('show');
        $(this).parent().siblings().removeClass('show');
        $(this).parent().toggleClass('active');
        $(this).parent().siblings().removeClass('active');
    })

    $(document).on('click touchstart', function (e) {
        e.stopPropagation();
        // closing of sidebar menu when clicking outside of it
        if (!$(e.target).closest('.main-header-menu-icon').length) {
            var sidebarTarg = $(e.target).closest('.main-sidebar').length;
            if (!sidebarTarg) {
                $('body').removeClass('main-sidebar-show');
            }
        }
    });

    $(document).on('click', '#mainSidebarToggle', function (event) {
        event.preventDefault();
        if (window.matchMedia('(min-width: 992px)').matches) {
            $('body').toggleClass('main-sidebar-hide');
        } else {
            $('body').toggleClass('main-sidebar-show');
        }
    });
    $(".nav.hor-menu").hover(function () {
        if ($('body').hasClass('main-sidebar-hide')) {
            $('body').addClass('main-sidebar-open');
        }
    }, function () {
        if ($('body').hasClass('main-sidebar-hide')) {
            $('body').removeClass('main-sidebar-open');
        }
    });

    /*---Scroling ---*/
    //P-scroll
    new PerfectScrollbar('.side-menu', {
        suppressScrollX: true
    });
    // ______________Active Class
    var position = window.location.pathname.split('/');
    $(".nav.hor-menu li a").each(function () {
        var $this = $(this);
        var pageUrl = $this.attr("href");

        if (pageUrl) {
            if (position[position.length - 1] == pageUrl) {
                $(this).addClass("active");
                $(this).parent().addClass("active");
                // $(this).parent().prev().addClass("active"); // add active to li of the current link
                $(this).parent().parent().prev().addClass("active"); // add active class to an anchor
                $(this).parent().parent().parent().parent().prev().addClass("active");
                $(this).parent().parent().parent().parent().parent().addClass("show");
                $(this).parent().parent().parent().parent().parent().addClass("active");
                $(this).parent().parent().prev().click(); // click the item to make it drop
                return false;
            }
        }
    });
    if ($('.nav-sub-link ').hasClass('active')) {
        $('.main-menu').animate({
            scrollTop: $('a.nav-sub-link.active').offset().top - 600
        }, 600);
    }
    if ($('.nav-sub-link').hasClass('active')) {
        $('.main-menu').animate({
            scrollTop: $('a.nav-sub-link.active').offset().top - 600
        }, 600);
    }

    //sticky-header
    $(window).on("scroll", function (e) {
        if ($(window).scrollTop() >= 70) {
            $('.side-header').addClass('fixed-header');
            $('.side-header').addClass('visible-title');
        } else {
            $('.side-header').removeClass('fixed-header');
            $('.side-header').removeClass('visible-title');
        }
    });

    $(window).on("scroll", function (e) {
        if ($(window).scrollTop() >= 70) {
            $('.main-navbar').addClass('fixed-header');
            $('.main-navbar').addClass('visible-title');
        } else {
            $('.main-navbar').removeClass('fixed-header');
            $('.main-navbar').removeClass('visible-title');
        }
    });
});

//________________Horizontal js
jQuery(function () {
    'use strict';
    document.addEventListener("touchstart", function () { }, false);
    jQuery(function () {
        jQuery('body').wrapInner('<div class="horizontalMenucontainer" />');
    });
}());

// for Icon-text Menu
//icontext(); 

// default layout
hovermenu();


// ______________HOVER JS start
function hovermenu() {
    $(".hor-menu").hover(function () {
        if ($('body').hasClass('main-sidebar-hide')) {
            $('body').addClass('main-sidebar-open');
        }
    }, function () {
        if ($('body').hasClass('main-sidebar-hide')) {
            $('body').removeClass('main-sidebar-open');
        }
    });
}
// ______________HOVER JS end

// ______________ICON-TEXT JS start
function icontext() {
    $(".hor-menu").off("mouseenter mouseleave");

    $(document).on('click', ".hor-menu", function (event) {
        if ($('body').hasClass('main-sidebar-hide') == true) {
            $('body').addClass('main-sidebar-open');
        }
    });

    $(document).on('click', ".side-content", function (event) {
        $('body').removeClass('main-sidebar-open');
    });

}


function responsive() {
    if (innerWidth >= 992) {
        if (document.querySelector("body").classList.contains("main-sidebar-hide") && document.querySelector("body").classList.contains("horizontal")) {
            document.querySelector("body").classList.remove("main-sidebar-hide")
        }
    }
}

//Horizontal activ class
function ActiveSubmenu() {

    var position = location.pathname.split('/');
    $(".main-navbar li a").each(function () {
        var $this = $(this);
        var pageUrl = $this.attr("href");
        let prevValue = prevWidth[prevWidth.length - 2];
        setTimeout(() => {
            if ((innerWidth < 992 && prevValue > 992) || document.querySelector('body').classList.contains('horizontal') != true) {
                if (pageUrl) {
                    if (position[position.length - 1] == pageUrl) {
                        $(this).addClass("active");
                        $(this).parent().addClass("show");
                        $(this).parent().parent().prev().addClass("active");
                        $(this).parent().parent().prev().addClass("show");
                        $(this).parent().parent().parent().addClass("show");
                        $(this).parent().parent().parent().parent().prev().addClass("active");
                        $(this).parent().parent().parent().parent().parent().addClass("show");
                        $(this).next().slideDown(300, function () { }); $(this).parent().parent().slideDown(300, function () {
                            $(this).parent().parent().addClass("open");
                        });
                        $(this).parent().parent().parent().parent().slideDown(300, function () {
                            $(this).parent().parent().parent().parent().addClass("open");
                        });
                        return false;
                    }
                }
            }
        }, 100);
    });
}
//Horizontal wrap nowrap

let slideLeft = document.querySelector(".slide-left");
let slideRight = document.querySelector(".slide-right");
// slideLeft.addEventListener("click", () => { slideClick() }, true)
// slideRight.addEventListener("click", () => { slideClick() }, true)

// used to remove is-expanded class and remove class on clicking arrow buttons
function slideClick() {
    let slide = document.querySelectorAll(".nav-item");
    let slideMenu = document.querySelectorAll(".nav-sub");
    slide.forEach((element, index) => {
        if (element.classList.contains("show.active") == true) {
            element.classList.remove("show.active")
        }
    });
    slideMenu.forEach((element, index) => {
        if (element.classList.contains("open") == true) {
            element.classList.remove("open");
            element.style.display = "none";
        }
    });
}

// horizontal arrows
var sideMenu = $(".nav.hor-menu");
var slide = "100px";

let menuWidth = document.querySelector('.main-navbar')
let menuItems = document.querySelector('.hor-menu')
let prevWidth = [innerWidth]
window.addEventListener('resize', () => {
    let menuWidth = document.querySelector('.main-navbar');
    let menuItems = document.querySelector('.hor-menu');
    let mainSidemenuWidth = document.querySelector('.main-sidemenu');
    let menuContainerWidth = menuWidth?.offsetWidth - mainSidemenuWidth?.offsetWidth;
    if (menuItems !== null) {
        let check = menuItems.scrollWidth + (0 - menuWidth?.offsetWidth) + menuContainerWidth;
        let marginLeftValue = Math.ceil(window.getComputedStyle(menuItems).marginLeft.split('px')[0]);
        let marginRightValue = Math.ceil(window.getComputedStyle(menuItems).marginRight.split('px')[0]);
        // to check and adjst the menu on screen size change
        if ($('body').hasClass('ltr')) {
            if (marginLeftValue > -check == false && (menuWidth?.offsetWidth - menuContainerWidth) < menuItems.scrollWidth) {
                sideMenu.stop(false, true).animate({
                    marginLeft: -check
                }, {
                    duration: 400
                })
            }
            else {
                sideMenu.stop(false, true).animate({
                    marginLeft: 0
                }, {
                    duration: 400
                })
            }
        }
        else {
            if (marginRightValue > -check == false && menuWidth?.offsetWidth < menuItems.scrollWidth) {
                sideMenu.stop(false, true).animate({
                    marginRight: -check
                }, {
                    duration: 400
                })
            }
            else {
                sideMenu.stop(false, true).animate({
                    marginRight: 0
                }, {
                    duration: 400
                })
            }
        }
        checkHoriMenu();
        responsive();


        prevWidth.push(innerWidth)
        if (prevWidth.length > 3) {
            prevWidth.shift()
        }
        let prevValue;
        if (prevWidth.length > 1) {
            prevValue = prevWidth[prevWidth.length - 2];
        }
        else {
            prevValue = prevWidth[0];

        }
        if (innerWidth >= 992 && prevValue < 992 || innerWidth >= 992) {
            if (document.querySelector('body').classList.contains('horizontal')) {
                removeActive()
            }
        }
        else {
            ActiveSubmenu();
        }
    }
}
)
function removeActive() {
    let li = document.querySelectorAll('.hor-menu li')
    li.forEach((e, i) => {
        e.classList.remove('show')
    })
    var animationSpeed = 300;
    // first level
    var parent = $("with-sub1").parents('ul');
    var ul = parent.find('ul:visible').slideUp(animationSpeed);
    ul.removeClass('open');
    var parent1 = $("with-sub2").parents('ul');
    var ul1 = parent1.find('ul:visible').slideUp(animationSpeed);
    ul1.removeClass('open');
}
function checkHoriMenu() {

    let menuWidth = document.querySelector('.main-navbar')
    let menuItems = document.querySelector('.hor-menu')
    let mainSidemenuWidth = document.querySelector('.main-sidemenu')
    let menuContainerWidth = menuWidth?.offsetWidth - mainSidemenuWidth?.offsetWidth
    let marginLeftValue = Math.ceil(window.getComputedStyle(menuItems).marginLeft.split('px')[0]);
    let marginRightValue = Math.ceil(window.getComputedStyle(menuItems).marginRight.split('px')[0]);
    let check = menuItems.scrollWidth + (0 - menuWidth?.offsetWidth) + menuContainerWidth;

    if ($('body').hasClass('ltr')) {
        menuItems.style.marginLeft = 0;
        menuItems.style.marginRight = 0
    }
    else {
        menuItems.style.marginLeft = 0;
        menuItems.style.marginRight = 0
    }

    if (menuItems.scrollWidth - 2 < (menuWidth?.offsetWidth - menuContainerWidth)) {
        $("#slide-right").addClass("d-none");
        $("#slide-left").addClass("d-none");
    }
    else if (marginLeftValue != 0) {
        $("#slide-left").removeClass("d-none");
    }
    else if (marginLeftValue != -check) {
        $("#slide-right").removeClass("d-none");
    }
    else if (marginRightValue != 0) {
        $("#slide-left").removeClass("d-none");
    }
    else if (marginRightValue != -check) {
        $("#slide-right").removeClass("d-none");
    }
}
checkHoriMenu();
$(document).on("click", ".ltr #slide-left", function () {
    let menuWidth = document.querySelector('.main-navbar')
    let menuItems = document.querySelector('.hor-menu')
    let mainSidemenuWidth = document.querySelector('.main-sidemenu')
    let menuContainerWidth = menuWidth?.offsetWidth - mainSidemenuWidth?.offsetWidth
    let marginLeftValue = Math.ceil(window.getComputedStyle(menuItems).marginLeft.split('px')[0]) + 100;

    if (marginLeftValue < 0) {
        sideMenu.stop(false, true).animate({
            // marginRight : 0,
            marginLeft: "+=" + slide
        }, {
            duration: 400
        })
        if ((menuWidth?.offsetWidth - menuContainerWidth) < menuItems.scrollWidth) {
            $("#slide-right").removeClass("d-none");
        }
    }
    else {
        $("#slide-left").addClass("d-none");
    }

    if (marginLeftValue >= 0) {
        sideMenu.stop(false, true).animate({
            // marginRight : 0,
            marginLeft: 0
        }, {
            duration: 400
        })

        if (menuWidth?.offsetWidth < menuItems.scrollWidth) {
            // $("#slide-left").addClass("d-none");
        }
    }
    // to remove dropdown when clicking arrows in horizontal menu
    let subNavSub = document.querySelectorAll('.sub-nav-sub');
    subNavSub.forEach((e) => {
        e.style.display = '';
    })
    let subNav = document.querySelectorAll('.nav-sub')
    subNav.forEach((e) => {
        e.style.display = '';
    })
    //
});
$(document).on("click", ".ltr #slide-right", function () {
    let menuWidth = document.querySelector('.main-navbar')
    let menuItems = document.querySelector('.hor-menu')
    let mainSidemenuWidth = document.querySelector('.main-sidemenu.container')
    let menuContainerWidth = menuWidth?.offsetWidth - mainSidemenuWidth?.offsetWidth
    let marginLeftValue = Math.ceil(window.getComputedStyle(menuItems).marginLeft.split('px')[0]) - 100;
    let check = menuItems.scrollWidth + (0 - menuWidth?.offsetWidth) + menuContainerWidth;
    if (marginLeftValue > -check ) {
        sideMenu.stop(false, true).animate({
            // marginLeft : 0,
            marginLeft: "-=" + slide,
            marginRight: 0,
        }, {
            duration: 400
        })
    }
    else {
        sideMenu.stop(false, true).animate({
            // marginLeft : 0,
            marginRight: 0,
            marginLeft: -check + 7
        }, {
            duration: 400
        });

        $("#slide-right").addClass("d-none");
    }

    if (marginLeftValue != 0) {
        $("#slide-left").removeClass("d-none");
    }
    // to remove dropdown when clicking arrows in horizontal menu
    let subNavSub = document.querySelectorAll('.sub-nav-sub');
    subNavSub.forEach((e) => {
        e.style.display = '';
    })
    let subNav = document.querySelectorAll('.nav-sub')
    subNav.forEach((e) => {
        e.style.display = '';
    })
    //
});

$(document).on("click", ".rtl #slide-left", function () {
    let menuWidth = document.querySelector('.main-navbar')
    let menuItems = document.querySelector('.hor-menu')
    let mainSidemenuWidth = document.querySelector('.main-sidemenu')
    let menuContainerWidth = menuWidth?.offsetWidth - mainSidemenuWidth?.offsetWidth
    let marginRightValue = Math.ceil(window.getComputedStyle(menuItems).marginRight.split('px')[0]) + 100;

    if (marginRightValue < 0) {
        sideMenu.stop(false, true).animate({
            // marginRight : 0,
            marginLeft: 0,
            marginRight: "+=" + slide
        }, {
            duration: 400
        })
        if ((menuWidth?.offsetWidth - menuContainerWidth) < menuItems.scrollWidth) {
            $("#slide-right").removeClass("d-none");
        }
    }
    else {
        $("#slide-left").addClass("d-none");
    }

    if (marginRightValue >= 0) {
        $("#slide-left").addClass("d-none");
        sideMenu.stop(false, true).animate({
            // marginRight : 0,
            marginLeft: 0
        }, {
            duration: 400
        })
    }
    // to remove dropdown when clicking arrows in horizontal menu
    let subNavSub = document.querySelectorAll('.sub-nav-sub');
    subNavSub.forEach((e) => {
        e.style.display = '';
    })
    let subNav = document.querySelectorAll('.nav-sub')
    subNav.forEach((e) => {
        e.style.display = '';
    })
    //
});
$(document).on("click", ".rtl #slide-right", function () {
    let menuWidth = document.querySelector('.main-navbar')
    let menuItems = document.querySelector('.hor-menu')
    let mainSidemenuWidth = document.querySelector('.main-sidemenu.container')
    let menuContainerWidth = menuWidth?.offsetWidth - mainSidemenuWidth?.offsetWidth
    let marginRightValue = Math.ceil(window.getComputedStyle(menuItems).marginRight.split('px')[0]) - 100;
    let check = menuItems.scrollWidth + (0 - menuWidth?.offsetWidth) + menuContainerWidth;
    if (marginRightValue > -check) {
        sideMenu.stop(false, true).animate({
            // marginLeft : 0,
            marginLeft: 0,
            marginRight: "-=" + slide
        }, {
            duration: 400
        })

    }
    else {
        sideMenu.stop(false, true).animate({
            // marginLeft : 0,
            marginLeft: 0,
            marginRight: -check + 7
        }, {
            duration: 400
        })
        $("#slide-right").addClass("d-none");
    }

    if (marginRightValue != 0) {
        $("#slide-left").removeClass("d-none");
    }
    // to remove dropdown when clicking arrows in horizontal menu
    let subNavSub = document.querySelectorAll('.sub-nav-sub');
    subNavSub.forEach((e) => {
        e.style.display = '';
    })
    let subNav = document.querySelectorAll('.nav-sub')
    subNav.forEach((e) => {
        e.style.display = '';
    })
});