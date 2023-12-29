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
   <scrip   t src="js/ajaxview.js"></scrip>
   <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js" integrity="sha384-b/U6ypiBEHpOf/4+1nzFpr53nxSS+GLCkfwBdFNTxtclqqenISfwAzpKaMNFNmj4" crossorigin="anonymous"></script>
   
   <!-- Iconos para edit -->
   <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>
   <script>

   </script>   
   <style>
      .mdl-1500{
         max-width:1500px !important;
      }

      .mdl-1000{
         max-width:1000px !important;
      }

      .mdl-900{
         max-width:900px !important;
      }
      
      .black-input:focus {
        background: #EEFADB;        
      }

      .black-input { 
        color: black;
        font-family: monospace;
        height: 42px !important; 
        font-size: x-large;
        border: solid #DBE1E1;
        font-weight: 500;
      }

      .KTitCar { 
         color: black; 
         font-weight: 500;
         font-size: 18px;
      }

      .KTitCam { 
         font-weight: 500;
         padding: 3%;
      }

      .TitTab{ 
         font-weight: 500;
         font-size: large;
         background: #343434;
         color: white; 
         /*background: #8BCF9A;
         color: black; */
      }

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
   </style>
   
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1002.tpl" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid"> 
      <div class="container col-sm-10">
         <!-- <br> -->
      {if $snBehavior eq 0}
         <div class="card-header text-dark" style="background:#fbc804; color:black;">
            <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
               <div class="col text-left"><strong>OPCIONES CONTABILIDAD</strong></div>
               <div class="col-auto"><b>{$scNombre}</b></div>
               <div class="col-auto badge badge-secondary p-2"><b>{$scCodUsu}</b></div>
            </div>
         </div>
         <br>
         <div class="row text-center">
            <div class="col-6 col-sm-3">
               <a class="btn btn-outline-warning justify-content-start text-dark border border-dark col-3 col-12" href="Afj1001.php">
                  <div><img src="img/baja_af.png" width="60" height="60" style="opacity: 0.5;"></div>
                  <div><br><h5><b>BAJAS, DONACIONES, REMATES</b></h5></div>
               </a>
            </div>
            <div class="col-6 col-sm-3">
               <a class="btn btn-outline-warning justify-content-start text-dark border border-dark col-3 col-12" href="Afj1190.php">
                  <div><img src="img/depreciacion.png" width="60" height="60" style="opacity: 0.5;"></div>
                  <div><br><h5><b>INICIO ANUAL DEPRECIACIÓN</b></h5></div>
               </a>
            </div>
            <div class="col-6 col-sm-3">
               <a class="btn btn-outline-warning justify-content-start text-dark border border-dark col-3 col-12" href="Afj1010.php">
                  <div><img src="img/depreciacion.png" width="60" height="60" style="opacity: 0.5;"></div>
                  <div><br><h5><b>DEPRECIACIÓN</b></h5></div>
               </a>
            </div>
            <div class="col-xs-12 col-sm-3">
               <a class="btn btn-outline-warning justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj2030.php">
                  <div><img src="img/reporte_activo.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>REPORTE CONTABILIZACIÓN</b></h5></div>
               </a>
            </div>
            <div class="col-6 col-sm-3">
               <a class="btn btn-outline-warning justify-content-start text-dark border border-dark col-3 col-12" href="Afj1180.php">
                  <div><img src="img/inbox.svg" width="60" height="60" style="opacity: 0.5;"></div>
                  <div><br><h5><b>CREAR CUENTA CONTABLE</b></h5></div>
               </a>
            </div>
            <div class="col-6 col-sm-3">
               <a class="btn btn-outline-warning justify-content-start text-dark border border-dark col-3 col-12" href="Afj1150.php">
                  <div><img src="img/tipo_activo.png" width="60" height="60" style="opacity: 0.5;"></div>
                  <div><br><h5><b>CREAR  TIPO DE ACTIVO FIJO</b></h5></div>
               </a>
            </div>
            <div class="col-6 col-sm-3">
               <a class="btn btn-outline-danger justify-content-left text-dark border border-dark col-md-3 col-sm-12" href="Afj1000.php">
                  <div><img src="img/retroceder.png" width="80" height="80" style="opacity: 0.5;"></div>
                  <div><br><h5><b>SALIR</b></h5></div>
               </a>
            </div>
            
         </div>   
      </div>
      {/if}
   </div>
   <br><br><br>
   <div id="footer"></div>
</form>
</body>
</html>
