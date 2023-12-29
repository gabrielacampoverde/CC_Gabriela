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
      function f_Reporte(p_cIdTrnf) {
         var lcSend = "Id=ReporteTranf&pcIdTrnf=" + p_cIdTrnf;
         $.post("Afj1040.php", lcSend).done(function(p_cResult) {
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=600, height=600');
         });
      }

      // Aprobar la transferencia
      function f_Conformidad(p_cIdTrnf) {
         Swal.fire({
            title: '¿Desea darle la aprobación a la transferencia?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'CONFIRMAR',
            cancelButtonText: 'CANCELAR'
         }).then((result) => {
            if (result.value) {
               $("#pcIdTrnf").val(p_cIdTrnf);
               $('#Id').val('Aprobar');
               $('#poForm').submit();
            }
         })
      }
   </script>   
   <style>
   </style>
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1040.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="paData[CIDTRNF]" id="pcIdTrnf">
   <!--<input type="hidden" name="p_nBehavior" id="p_nBehavior">-->
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>CONFORMIDAD DE TRANSFERENCIA</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="card-body">
         <div class="row d-flex justify-content-center">
         <div class="col-sm-11">
         <div class="card text-center ">
         <div class="card-body" >
            <div class="input-group mb-2">
            </div>
            <div style="height:750px; overflow-y: scroll;">
               <table class="table table-sm table-hover table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr> 
                        <th class="text-center"  style="width: 50px">#</th>
                        <th class="text-center"  style="width: 100px">ID</th>
                        <th class="text-center"  style="width: 300px">FECHA</th>
                        <th class="text-center">DESCRIPCIÓN</th>
                        <th class="text-center" style="width: 80px">
                           <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i></th>
                        <th class="text-center" style="width: 60px">
                           <img src="img/aprobado.png" width="20" height="20"></th>
                     </tr>
                  </thead>
                  <tbody>
                  {$k = 1}
                  {foreach from=$saDatos item=i}
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CIDTRNF']}</td>
                        <td class="text-center">{$i['CTRASLA']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">
                           <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;" onclick="f_Reporte('{$i['CIDTRNF']}')"></i>
                        </td>
                        <td class="text-center">
                           <i class="fas fa-check" style="color:#56c700;" onclick="f_Conformidad('{$i['CIDTRNF']}')"></i>
                        </td>
                     </tr>
                  {$k = $k + 1}
                  {/foreach}
                  </tbody>
               </table>
            </div>
            <div class="card-footer text-muted">
               <button type="submit" name="Boton" value="Conformidad" class="btn btn-warning col-md-2 " formnovalidate>CONFORMIDAD</button>
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
   <div id="footer"></div>
</form>
</body>
</html>
