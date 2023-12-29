<!DOCTYPE html>
<html>
<head>
   <title>ERP - Universidad Católica de Santa María</title>
   <meta http-equiv="content-type" content="text/html; charset=UTF-8">
   <link rel="icon" type="image/png" href="img/logo_ucsm.png">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="js/jquery-ui-1.12.1/jquery-ui.css">
   <link rel="stylesheet" href="bootstrap4/css/bootstrap.min.css">
   <link rel="stylesheet" href="bootstrap4/css/bootstrap-select.css">
   <script src="js/jquery-3.1.1.min.js"></script>
   <script src="js/jquery-ui-1.12.1/jquery-ui.js"></script>
   <script src="bootstrap4/js/bootstrap.bundle.min.js"></script>
   <script src="js/jquery.blockUI.js"></script>
   <script src="bootstrap4/js/bootstrap-select.js"></script>
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="css/style.css?v=1.0">
   <script src="js/java.js"></script>
    {* plugin de tabla para paginacion *}
   <link rel="stylesheet" href="css/datatables.min.css">
   <script src="js/datatables.min.js"></script>
   <!-- Script para darle dinamismo a la tabla -->
   <link rel="stylesheet" type="text/css" href="css/datetimepicker.css">
   <script src="js/jquery.datetimepicker.full.min.js"></script>
   <link rel="stylesheet" href="sweetalert/sweetalert2.min.css">
   <script src="sweetalert/sweetalert2.all.min.js"></script>
   <script src="js/inputmask.js"></script>
   <script src="js/ajaxview.js"></script>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
   
   <!-- Iconos para edit -->
   <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>
   <script>

   </script>   
   <style>
      .BodTab{ 
         color: black; 
         font-size: large;
         text-align: left;
      }
      
      .black-input-mdl { 
         color: black;
         font-family: monospace;
         height: 35px !important; 
         font-size: large;
         border: solid #DBE1E1;         
         /*font-weight: 100;*/
      }

      .KTitCam-mdl { 
         padding: 3%;
         height: 35px !important; 
         font-size: large;
      }

      .icon-tbl { 
         cursor: pointer;
         color: #28A745;
         /*color: #007BFF;*/
         font-size: x-large;
      }

      /***************************/
      .services-principal .icon-box {
         padding: 60px 30px;
         transition: all ease-in-out 0.3s;
         background: #fefefe;
         box-shadow: 0px 5px 90px 0px rgba(110, 123, 131, 0.1);
         border-radius: 18px;
         border-bottom: 5px solid #fff;
         /*border: solid rgba(0, 146, 153, 0.2);*/
         border: solid #B6CEBE;
         cursor:pointer;
      }
  
      .services-principal .icon-box:hover {
         transform: translateY(-10px);
         /*border-color: #009299;*/
         border-color: #245433;
      }
      .btn {
         white-space: normal !important;
         max-width: 100%;
         height: 98%
      } 
  
      /*.services-principal .icon-box:hover h4 a {
         color: #009299;
      }*/*/
   </style>
   
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj2000.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="pcIdDevo" id="pcIdDevo">
   <input type="hidden" name="pcCodAlu" id="pcCodAlu">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid"> 
      <div class="container col-sm-10">
         <br>
      {if $snBehavior eq 0}
         <div class="card-header text-dark" style="background:#fbc804; color:black;">
            <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
               <div class="col text-left"><strong>REPORTES ACTIVOS FIJOS</strong></div>
               <div class="col-auto"><b>{$scNombre}</b></div>
               <div class="col-auto badge badge-secondary p-2"><b>{$scCodUsu}</b></div>
            </div>
         </div>
         <br>
         <div class="row text-center">
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2070.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE POR CARACTERISTICAS</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2080.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE POR VALORES</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2100.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE ANÁLISIS DE ACTIVOS FIJOS</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2010.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>BUSCAR ACTIVO FIJO</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2040.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE POR FECHA</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2060.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE CENTRO DE COSTO Y CENTRO RESPONSABILIDAD</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2090.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE CLASES Y TIPOS</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2110.php">
                  <div><img src="img/listas-control.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE DE INVENTARIO</b></h5></div>
               </a>
            </div> 
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-danger justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj1000.php">
                  <div><img src="img/retroceder.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>SALIR</b></h5></div>
               </a>
            </div>  
         </div>   
      </div>    
         <br>
      {/if}
   </div>
   <br><br><br>
   <div id="footer"></div>
</form>
</body>
</html>
