<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta content="width=device-width, initial-scale=1, shrink-to-fit=no" name="viewport">
    <title>Menu</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"
        integrity="sha512-OvBgP9A2JBgiRad/mM36mkzXSXaJE9BEIENnVEmeZdITvwT09xnxLtT4twkCa8m/loMbPHsvPl0T8lRGVBwjlQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.0/css/all.min.css"
        integrity="sha512-xh6O/CkQoPOWDdYTDqeRdPCVd1SpvCA9XXcUnZS2FmJNp1coAFzvtCN9BmamE+4aHK8yyUHUSCcJHgXloTyT2A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="css/menu-admin/style.css" rel="stylesheet">
    <link href="css/menu-admin/skins.css" rel="stylesheet">
    <link href="css/menu-admin/dark-style.css" rel="stylesheet">
    <link href="css/menu-admin/boxed.css" rel="stylesheet">
    <script src="js/menu-admin/perfect-scrollbar.min.js"></script>
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
    var elem = document.documentElement;
    var lcFullScreen = false;

    function f_setFullScreen() {
        !lcFullScreen ?
            openFullscreen() :
            closeFullscreen();
    }

    function openFullscreen() {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) {
            elem.msRequestFullscreen();
        }
        lcFullScreen = true;
    }

    function closeFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
        lcFullScreen = false;
    }

    function f_ActualizarInformacion() {
        var lcNroCel = document.getElementById("p_cNroCel").value;
        var lcEmail = document.getElementById("p_cEmail").value;
        if (lcNroCel == "" || lcEmail == "") {
            Swal.fire('POR FAVOR, COMPLETE TODO LA INFORMACIÓN SOLICITADA.');
            return;
        }
        if (lcNroCel == "") {
            Swal.fire('POR FAVOR, COMPLETE SU NÚMERO TELEFÓNICO.');
            return;
        }
        if (!formatEmail(lcEmail)) {
            Swal.fire('CORREO ELECTRÓNICO INGRESADO NO VÁLIDO.');
            return;
        }
        Swal.fire({
            title: '¿ESTÁ SEGURO DE ACTUALIZAR SUS DATOS COMO USUARIO?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'CONFIRMAR',
            cancelButtonText: 'CANCELAR'
        }).then((result) => {
            if (result.value) {
                $('#Id').val('Grabar');
                $('#poForm').submit();
            }
        })
    }

    function formatEmail(email) {
        var re = /\S+@\S+\.\S+/;
        return re.test(email);
    }
</script>

<body class="ltr">
    <form action="Afj1000.php" method="post" enctype="multipart/form-data" id="poForm">
        <input type="hidden" name="Id" id="Id">
        <input type="hidden" name="p_nBehavior" id="p_nBehavior">
        <div class="horizontalMenucontainer">
            <div class="page show active">
                {include file="Plantillas/HeaderMnu.tpl"}
                <!-- Sidemenu -->
                {include file="Plantillas/SideMenu.tpl"}
                <div class="main-content side-content pt-0">
                    <div class="side-app">
                        <div class="main-container container-fluid">
                            <div class="page-header">
                                <div>
                                    <h2 class="main-content-title tx-24 mg-b-5">MENÚ DE OPCIONES ACTIVO FIJO</h2>
                                </div>
                            </div>
                            <div class="row row-sm">
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1020.php"><img src="img/activo-fijo.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1020.php">ACTIVO FIJO</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1110.php"><img src="img/activo-fijo.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1110.php">EDITAR/NUEVO ACTIVO FIJO</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1080.php"><img src="img/activo_fijo.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1080.php">COMPONENTES</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1060.php"><img src="img/gastos.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1060.php">GASTOS</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row row-sm">
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1002.php"><img src="img/cambio1.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1002.php">TRANSFERENCIAS DE ACTIVOS FIJOS</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                 <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1220.php"><img src="img/listas-control.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1220.php">GRABAR INVENTARIO</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Cnt5120.php"><img src="img/portapapeles.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Cnt5120.php">CREAR CENTRO DE RESPONSABILIDAD</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1003.php"><img src="img/impuesto.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1003.php">OPCIONES CONTABILIDAD</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row row-sm">
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1090.php"><img src="img/buscar.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1090.php">BUSCAR ARTICULO</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj1050.php"><img src="img/codigo-barras.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj1050.php">IMPRIMIR ETIQUETAS</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Afj2000.php"><img src="img/reporte_activo.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Afj2000.php">REPORTES</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-xl-3 col-lg-6">
                                    <div class="card custom-card text-center">
                                        <div class="card-body dash1 text-center">
                                            <div>
                                                <a href="Mnu1000.php"><img src="img/retroceder.png" width="90" height="80"></a>
                                            </div>
                                            <br>
                                            <div>
                                                <h4><a href="Mnu1000.php">SALIR</a></h4>
                                            </div>
                                            <div class="progress mb-1">
                                                <div aria-valuemax="100" aria-valuemin="0" aria-valuenow="100"
                                                    class="progress-bar progress-bar-xs wd-100p bg-success"
                                                    role="progressbar"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row row-sm">
                                 <!-- PONER UN OPCION DEL MENU -->
                            </div>
                        </div>
                    </div>
                </div>
                <div id="footer"></div>
            </div>
            <a href="#top" id="back-to-top"><i class="fa-solid fa-chevron-up"></i></a>
        </div>
    </form>
</body>

</html>