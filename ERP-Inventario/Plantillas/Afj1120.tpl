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
      function Init() {
      }

      function f_Reporte(p_cCenRes) {
         var lcSend = "Id=ReporteBaja&p_cCenRes=" + p_cCenRes;
         // alert(lcSend);
         $.post("Afj1120.php", lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            // alert(laJson.CREPORT);
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1200, height=800');
         });
      }


      // Aprobar baja
      function f_AprobarBaja() {
         event.preventDefault();
         Swal.fire({
            title: '¿Seguro desea dar de baja?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'CONFIRMAR',
            cancelButtonText: 'CANCELAR'
         }).then((result) => {
            // console.log(result);
            if (result.value) {
               $('#Id').val('DarBajaActivos');
               $("#cDocBaj").val();
               $("#dFecBaj").val();
               $('#poForm').submit();
            }
         })
      }
   </script>   
   <style>
      .head-fixed {
         position: sticky;
         top: 0;
         z-index: 1;
      }
   </style>
</head>
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div>
<form action="Afj1120.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="CCENRES" id="pcCenRes">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>BAJAS, DONACIONES, REMATES</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="card-body">
         <div class="row d-flex justify-content-center">
         <div class="col-sm-11">
         <div class="card text-center ">
         <div class="card-body" >
            <div class="input-group mb-2"></div>
            <div style="height:650px; overflow-y: scroll;">
               <table class="table table-sm table-hover table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr style="text-align: left;"> 
                        <th class="text-center"  style="width: 50px">#</th>
                        <th class="text-center"  style="width: 100px">CEN.RESP</th>
                        <th class="text-center">DESCRIPCIÓN</th>
                        <th class="text-center">ESTADO</th>
                        <th class="text-center" style="width: 80px">
                           <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i></th>
                        <th class="text-center" style="width: 60px">
                           <img src="img/aprobado.png" width="20" height="20"></th>
                     </tr>
                  </thead>
                  <tbody>
                  {$k = 1}
                  {foreach from=$saDatos item=i}
                  {if {$i['CESTADO']} eq 'I'}
                     <tr class="text-center table-info" multiple >
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCENRES']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">{$i['CESTADO']}-{$i['CDESEST']}</td>
                        <td class="text-center">
                           <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;" onclick="f_Reporte('{$i['CCENRES']}')"></i>
                        </td>
                        <td class="text-center">
                           <form>
                              <input type="hidden" name="CCENRES" value="{$i['CCENRES']}" required/>
                              <!-- <button type="submit" name="Boton" value="" > -->
                                 <i class="fa fa-times-circle" style="color: #C70039;"></i>
                              <!-- </button> -->
                           </form>                           
                           <!-- <i class="fas fa-check" style="color:#56c700;"  onclick="f_AprobarBaja('{$i['CCENRES']}')"></i> -->
                        </td>
                     </tr>
                  {else}
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCENRES']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">{$i['CESTADO']}-{$i['CDESEST']}</td>
                        <td class="text-center">
                           <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;" onclick="f_Reporte('{$i['CCENRES']}')"></i>
                        </td>
                        <td class="text-center">
                           <form>
                              <input type="hidden" name="CCENRES" value="{$i['CCENRES']}" required/>
                              <button type="submit" name="Boton" value="RevisarBaja" >
                                 <i class="fas fa-check-circle icon-tbl" style="color: #56c700;"></i>
                              </button>
                           </form>                           
                           <!-- <i class="fas fa-check" style="color:#56c700;"  onclick="f_AprobarBaja('{$i['CCENRES']}')"></i> -->
                        </td>
                     </tr>
                  {/if}
                  {$k = $k + 1}
                  {/foreach}
                  </tbody>
               </table>
            </div>
            <div class="card-footer text-muted">
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
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm bg-ucsm">Descripción</span>
               </div>
               <input type="text" value="{$saData['CCENRES']} - {$saData['CDESCRI']}" class="form-control text-uppercase col-lg-6 black-input" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm bg-ucsm">Documento de Baja</span>
               </div>
               <input type="text"  class="form-control text-uppercase col-lg-6 black-input" name="paData[CDOCBAJ]" id="cDocBaj">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm bg-ucsm">Fecha</span>
               </div>
               <input type="date"  class="form-control text-uppercase col-lg-2 black-input" name="paData[DFECBAJ]" id="dFecBaj">
            </div>
            <div style="height:260px; overflow-y: scroll;">
               <div >
                  <table class="table table-hover table-sm table-bordered">
                     <thead class="thead-dark">
                        <tr>
                        <th scope="col">#</th> 
                        <th scope="col">Act.Fij.</th>
                        <th scope="col">Código</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Fecha</th>
                     </thead>
                     <tbody>
                     {$k=1}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CACTFIJ']}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-center">{$i['DFECALT']}</td>
                        </tr>
                     {$k=$k+1}
                     {/foreach}
                     </tbody>
                  </table>
               </div>
            </div>
            <br>
            <div class="card-footer text-muted">
               <button class="btn btn-primary col-sm-2"  onclick="f_AprobarBaja()">BAJA</button>
               <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>REGRESAR</button> 
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
