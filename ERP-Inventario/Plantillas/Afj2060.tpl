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
<form action="Afj2060.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>REPORTE ACTIVO FIJO POR CENTRO DE COSTO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-10">
      <div class="card text-center ">
      <div class="card-body" >
         {* Buscar activo fijo *}
         <!-- <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-3 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
            </div>
            <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos" name="paData[CCENCOS]" data-live-search="true" >
               {foreach from=$saCenCos item=i}                        
                  <option value="{$i['CCENCOS']}">{$i['CDESCRI']}</option>
               {/foreach}                                          
            </select>
         </div> -->
         <div class="card-footer text-center">
            <button type="submit" name="Boton" value="Reporte" class="btn btn-primary col-md-2" formnovalidate>
               <i class="fas fa-file"></i>&nbsp;&nbsp;Reporte PDF</button>
            <button type="submit" name="Boton" value="ReporteExcel" class="btn btn-success col-md-2" formnovalidate>
               <i class="fa fa-file-excel"></i>&nbsp;&nbsp;Reporte EXCEL</button>  
            <button type="submit" name="Boton" value="ReporteExcelTotal" class="btn btn-warning col-md-2" formnovalidate>
               <i class="fa fa-file-excel"></i>&nbsp;&nbsp;Reporte Total EXCEL</button> 
            <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
         </div>
      </div>
      </div>
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