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
      function f_RepContabilizacion(p_cPeriod){
         // var lcPeriod = document.getElementById("p_cPeriod");
         var lcSend = "Id=ReporteContabilizacion&p_cPeriod="+p_cPeriod;
         //alert(lcSend);
         $.post("Afj1010.php",lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
         });
      }     
      
      function f_ReporteDetalle(p_cPeriod){
         // var lcPeriod = document.getElementById("p_cPeriod");
         var lcSend = "Id=ReporteDepreciacionDetalle&p_cPeriod="+p_cPeriod;
         //alert(lcSend);
         $.post("Afj1010.php",lcSend).done(function(p_cResult) {
            //console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
         });
      }     
   </script>   
   <style>
   </style>
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1010.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" id ='p_cPeriod' name="paData[CPERIOD]">
   <div class="container-fluid">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>CALCULAR DEPREACIACION</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-11">
      <div class="card text-center ">
      <div class="card-body" >
         {* <div style="padding-left: 10rem;"> *}
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Periodo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CPERIOD]" value="{$saData['CPERIOD']}" placeholder="AAAA-MM-DD" maxlength="10" autofocus>
               &nbsp;&nbsp;
               <button type="submit" name="Boton" value="Calcular" class="btn btn-primary col-md-2" style="height: 42px !important;"> 
                  CALCULAR DEPRECIACIÓN</button>&nbsp;&nbsp;
               <button type="submit" name="Boton" value="Contabilizacion" class="btn btn-success col-md-2" style="height: 42px !important;"> 
                  CONTABILIZACÍON</button>&nbsp;&nbsp;
               <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
            </div>
         <div style="height:670px; overflow-y: scroll;">
            <table class="table table-sm table-hover table-bordered">
               <thead class="thead-dark head-fixed">
                  <tr> 
                     <th class="text-center" > #</th>
                     <th class="text-center" > ID</th>
                     <th class="text-center" > FECHA</th>
                     <th class="text-center" > GLOSA</th>
                     <th class="text-center" style="width: 80px" > CONTABILIZACÍON</th>
                     <th class="text-center" style="width: 80px">REP.DETALLE</th>
                  </tr>
               </thead>
               <tbody>
               {$k = 1}
               {foreach from=$saDatos item=i}
                  <tr class="text-center" class="custom-select" multiple>
                     <td class="text-center">{$k}</td>
                     <td class="text-center">{$i['CNROASI']}</td>
                     <td class="text-center">{$i['DFECCNT']}</td>
                     <td class="text-left">{$i['CGLOSA']}</td>
                     <td class="text-center">
                        <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;" onclick="f_RepContabilizacion('{$i['CPERIOD']}')"></i>
                     </td>
                     <td class="text-center">
                        <i class="fas fa-file-pdf icon-tbl" style="color:#75c25d;" onclick="f_ReporteDetalle('{$i['CPERIOD']}')"></i>
                     </td>
                  </tr>
               {$k = $k + 1}
               {/foreach}
               </tbody>
            </table>
         </div>
         <br>
      </div>
      </div>
      </div>
      </div>
      </div>
      {/if}
   </div>
<div id="footer"></div>
</form>
</body>
</html>