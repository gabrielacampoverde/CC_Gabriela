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
   </style>
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1030.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>BUSCAR TRANSFERENCIA DE ACTIVO FIJO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="p-2 card-body">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">CÓDIGO ACTIVO FIJO</span>
               </div>
               <input type="text" placeholder="Ingresar código de activo fijo" class="form-control text-uppercase black-input col-lg-4" name="paData[CCODIGO]" autofocus>
               &nbsp;&nbsp;&nbsp;
               <span class="input-group-btn">
                  <button class="btn btn-primary" type="submit" name="Boton" value="Buscar" style="height: 42px !important;"> 
                     <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR
                  </button>
               </span>
               &nbsp;
               <span class="input-group-btn" style="display:;" id="" >
                  <button type="submit" name="Boton" value="Salir" class="btn btn-danger" style="height: 42px !important;" formnovalidate>
                     <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ
                  </button> 
               </span>
            </div>
            <div style="height:800px; overflow-y: scroll;">
            <div style="" id="div_LisConPat">
               <table class="table table-hover table-sm table-bordered">
                  <thead class="TitTab head-fixed thead-dark">
                     <tr style="text-align: left;">
                        <th scope="col">#</th>
                        <th scope="col">Código</th>
                        <th scope="col" class="text-center">Descripción</th>
                        <th scope="col" class="text-center">Fecha</th>
                        <th scope="col" class="text-center">Centro de Costo Origen</th>
                        <th scope="col" class="text-center">Centro de Responsabilidad Origen</th>
                        <th scope="col" class="text-center">Empleado Origen</th>
                        <th scope="col" class="text-center">Centro de Costo Destino</th>
                        <th scope="col" class="text-center">Centro de Responsabilidad Destino</th>
                        <th scope="col" class="text-center">Empleado Destino</th>
                        <th scope="col" class="text-center">Encargado de la Ejecución</th>
                     </tr>
                  </thead>
                  <tbody class="BodTab" id="tbl_ConPat">
                     {$k=1}
                     {foreach from=$saDatos item=i}
                     <tr class="text-center table-info" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CIDTRNF']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-left">{$i['DTRASLA']}</td>
                        <td class="text-left">{$i['CCENCOS']} - {$i['CDESCOS']}</td>
                        <td class="text-left">{$i['CCENRES']} - {$i['CDESCEN']}</td>
                        <td class="text-left">{$i['CCODEMP']} - {$i['CNOMBRE']}</td>
                        <td class="text-left">{$i['CCOSDES']} - {$i['CDESDES']}</td>
                        <td class="text-left">{$i['CRESCEN']} - {$i['CRESDES']}</td>
                        <td class="text-left">{$i['CCODREC']} - {$i['CNOMDES']}</td>
                        <td class="text-left">{$i['CUSUADM']} - {$i['CNOMADM']}</td>
                     </tr>  
                  {$k = $k+1}             
                  {/foreach}
                  </tbody>
               </table>
            </div>
            </div>
         </div>
      {/if}
   </div>
   <div id="footer"></div>
</form>
</body>
</html>
