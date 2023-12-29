<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <title>Menú</title>
    <link rel="icon" type="image/png" href="img/logo_ucsm.png">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"
        integrity="sha512-OvBgP9A2JBgiRad/mM36mkzXSXaJE9BEIENnVEmeZdITvwT09xnxLtT4twkCa8m/loMbPHsvPl0T8lRGVBwjlQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
        integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="css/menu-admin/style.css" rel="stylesheet">
{*    <link href="css/menu-admin/menu-admin.css" rel="stylesheet">*}
    <script src="js/menu-admin/perfect-scrollbar.min.js"></script>
    <link href="css/menu-admin/skins.css" rel="stylesheet">
    <link href="css/menu-admin/dark-style.css" rel="stylesheet">
    <link href="css/menu-admin/boxed.css" rel="stylesheet">
    <script src="js/menu-admin/jquery.min.js"></script>
    <script src="js/menu-admin/popper.min.js"></script>
    <script src="js/menu-admin/bootstrap.min.js"></script>
    <script src="js/menu-admin/jquery.flot.js"></script>
    <script src="js/menu-admin/jquery.flot.resize.js"></script>
    <script src="js/menu-admin/chart.flot.sampledata.js"></script>
    <script src="js/menu-admin/Chart.bundle.min.js"></script>
    <script src="js/menu-admin/jquery.peity.min.js"></script>
    <script src="js/menu-admin/datepicker.js"></script>
    <script src="js/menu-admin/select2.min.js"></script>
    <script src="js/menu-admin/multiple-select.js"></script>
    <script src="js/menu-admin/multi-select.js"></script>
    <script src="js/menu-admin/sidemenu.js"></script>
    <script src="js/menu-admin/sidebar.js"></script>
    <script src="js/menu-admin/sticky.js"></script>
    <script src="js/menu-admin/index.js"></script>
    <script src="js/menu-admin/themeColors.js"></script>
    <script src="js/menu-admin/custom.js"></script>
    <script src="js/menu-admin/switcher.js"></script>
    <script src="js/java.js"></script>
    <link rel="stylesheet" href="sweetalert/sweetalert2.min.css">
    <script src="sweetalert/sweetalert2.all.min.js"></script>
</head>
<script>   
    function f_MostrarRoles(p_CodMod) {
        // console.log(p_CodMod);
        var lcSend = "Id=MostrarRoles&p_CodMod=" + p_CodMod;
        //console.log(lcSend);
        $.post("Mnu1000.php",lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            document.getElementById("menuContent").innerHTML = p_cResult;
        });
      }

</script>

<body class="ltr">
    <div class="horizontalMenucontainer">
        <div class="page show active">
            <!-- HeaderMnu -->
            {include file="Plantillas/HeaderMnu.tpl"}
            <!-- Sidemenu -->
            {include file="Plantillas/SideMenu.tpl"}
            <!-- Main Content-->
            <div class="main-content side-content pt-0">
                <div class="side-app">
                    <div class="main-container container-fluid">
                        <div class="row row-sm" id="menuContent">

                            {if $saModule eq NULL}
                                <div class="page-header">
                                    <div>
                                        <h2 class="main-content-title tx-26 mg-b-5">Bienvenido (a) {$scNombre}</h2>
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item tx-20"><a href="#">Nro. Documento: {$scNroDni}</a></li>
                                        </ol>
                                    </div>
                                </div>
                            {else}
                                <script>f_MostrarRoles('{$saModule}')</script>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>
            <footer id="footer" style="position: absolute; bottom:0; width: 100%; height: 2.9rem;" ></footer>
            {* <div id="footer" ></div> *}
        </div>
        {* <a href="#top" id="back-to-top"><i class="fa-solid fa-chevron-up"></i></a> *}
    </div>
</body>
<script>
   var elem = document.documentElement;
   var lcFullScreen = false;

   function f_setFullScreen() {
      !lcFullScreen ?
         openFullscreen() :
         closeFullscreen();
   }

   /* View in fullscreen */
   function openFullscreen() {
      if (elem.requestFullscreen) {
         elem.requestFullscreen();
      } else if (elem.webkitRequestFullscreen) { /* Safari */
         elem.webkitRequestFullscreen();
      } else if (elem.msRequestFullscreen) { /* IE11 */
         elem.msRequestFullscreen();
      }
      lcFullScreen = true;
   }

   /* Close fullscreen */
   function closeFullscreen() {
      if (document.exitFullscreen) {
         document.exitFullscreen();
      } else if (document.webkitExitFullscreen) { /* Safari */
         document.webkitExitFullscreen();
      } else if (document.msExitFullscreen) { /* IE11 */
         document.msExitFullscreen();
      }
      lcFullScreen = false;
   }
   // function f_setResponsiveButton(){
   //    const navbarButtonIcon = document.querySelector('#navbarButtonIcon');
   //    const navbarSupportedContent = document.querySelector('#navbarSupportedContent-4');
   //    if (!navbarButtonIcon.classList.contains('active')) {
   //       navbarButtonIcon.classList.remove('collapsed');
   //       navbarSupportedContent.classList.remove('show');
   //    } else {
   //       navbarButtonIcon.classList.add('collapsed');
   //       navbarSupportedContent.classList.add('collapsed');
   //    }
   //    console.log(navbarButtonIcon.classList.contains('active'))
   //    console.log(navbarButtonIcon.classList)
   //    // if (navbarButtonIcon.classList.)
   // }
</script>
</html>