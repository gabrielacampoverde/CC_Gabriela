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
      function f_ReporteOrCom(p_cIdOrde) {
         var lcSend = "Id=reporteOrdCom&p_cIdOrde=" + p_cIdOrde;
         // alert(lcSend); 
         $.post("Afj1090.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
         });
      }

      function f_ReporteActFij(p_cAsient){
         var lcSend = "Id=ReporteActivosFijo&p_cAsient="+p_cAsient;
         // alert(lcSend);
         $.post("Afj1090.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            // var laJson = p_cResult.trim();
            console.log(laJson.CREPORT);
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
         });
      }
   </script>   
   <style>
      .bg-ucsm{
         background:#099957;
         color: white;
      }
   </style>
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1090.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <br>
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>BUSCAR POR CODIGO DE ARTICULO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-8">
      <div class="card text-center">
      <div class="card-body">
         <div style="padding-left: 15rem;">
            {* Buscar activo fijo *}
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Código Artículo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CCODART]" autofocus>
               &nbsp;
            </div>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton" value="Buscar" class="btn btn-primary col-md-2" style="height: 42px !important;"> 
               <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
            <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 1}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-12">
      <div class="card text-center ">
      <div class="card-body" >
         <div style="height:400px; overflow-y: scroll;">
            <div >
               <table class="table table-sm table-hover table-bordered">
                  <thead class="thead-dark">
                     <tr>
                        <th class="text-center">ASI.CON.</th>
                        <th class="text-center">Descripción</th>
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Costo</th>
                        <th class="text-center">OC</th>
                        <th class="text-center">ACT.FIJ.</th>
                     </tr>
                  </thead>
                  <tbody class="BodTab">
                     {foreach from=$saData item=i}
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$i['CASIENT']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">{$i['DFECEMI']}</td>
                        <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                        <td class="text-center" title="Orden Compra o Servicio" onclick="f_ReporteOrCom('{$i['CIDORDE']}');">
                           <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                        <td class="text-center" title="Lista Activos FIjos" onclick="f_ReporteActFij('{$i['CASIENT']}');">
                           <i class="fas fa-file-pdf icon-tbl" style="color:green;"></i>
                     </tr>               
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
         <!-- <div style="height:260px; overflow-y: scroll;">
            <div >
               <table class="table table-sm table-hover table-bordered">
                  <thead class="thead-dark">
                     <tr>
                        <th class="text-center">Código</th>
                        <th class="text-center">Descripción</th>
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Cantidad</th>
                        <th class="text-center">Costo</th>
                     </tr>
                  </thead>
                  <tbody class="BodTab">
                     {foreach from=$saDatos item=i}
                     <tr>
                        <td class="text-center">{$i['CCODART']}</td>
                        <td class="text-left">{$i['CDESART']}</td>
                        <td class="text-center">{$i['DFECHA']}</td>
                        <td class="text-center">{$i['NCANTID']}</td>
                        <td class="text-right">{$i['NCOSTO']|number_format:2:".":","}</td>
                     </tr>               
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div> -->
      {/if}
   </div>
<div id="footer"></div>
</form>
</body>
</html>
