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
   <link rel="stylesheet" type="text/css" href="css/datetimepicker.css">
   <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>
   <script>
      $(document).ready(function () {
         $("#pcHorario").datetimepicker({
            format:'Y-m-d',
            mask:true,
            onSelect: function(datetext){
               $('#pcHorario').val(datetext);
            },
         });
      });

      function Init() {
         f_CargarCentroResp();
         f_CargarCentroResp1();
         
      }

      function f_Reporte(p_cIdTrnf) {
         var lcSend = "Id=ReporteTranf&pcIdTrnf=" + p_cIdTrnf;
         // alert(lcSend);
         $.post("Afj1070.php", lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            // alert(laJson.CREPORT);
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
         });
      }

      function f_Transferencia(p_nIndice) {
         // alert(p_nIndice);
         $('#pnIndice').val(p_nIndice);
         $('#Id').val('Editar');
         $('#poForm').submit();
      }
   
      function f_CargarCentroResp() {
         document.getElementById("pcCenRes").options.length = 0;
         var lcSend = "Id=CargarCentrosRes&pcCenCos=" + $('#pcCenCos').val();
         // alert(lcSend);
         $.post("Afj1070.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            console.log(laDatos);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            loCenRes = document.getElementById("pcCenRes");
            for (var i = 0; i < laDatos['LADATOS'].length; i++) {
               var loOption = document.createElement("option");
               loOption.setAttribute("value", laDatos['LADATOS'][i].CCENRES);
               loOption.setAttribute("label", laDatos['LADATOS'][i].CDESRES);
               loCenRes.appendChild(loOption); 
               if(laDatos['LADATOS'][i]['CCENRES'] == laDatos['LADATA']['CCENRES']){
                  loOption.setAttribute("selected", laDatos['LADATOS'][i].CCENRES);
               }               
            }
            
         });
      }
      function f_CargarCentroResp1() {
         document.getElementById("pcCenRes1").options.length = 0;
         var lcSend = "Id=CargarCentrosRes1&pcCenCos1=" + $('#pcCenCos1').val();
         //alert(lcSend);
         $.post("Afj1070.php",lcSend).done(function(p_cResult) {
            //console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            console.log(laDatos);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            loCenRes1 = document.getElementById("pcCenRes1");
            for (var i = 0; i < laDatos['LADATOS'].length; i++) {
                // alert(laDatos[i].CCENRES);
                var loOption1 = document.createElement("option");
                loOption1.setAttribute("value", laDatos['LADATOS'][i].CCENRES);
                loOption1.setAttribute("label", laDatos['LADATOS'][i].CDESRES);
                loCenRes1.appendChild(loOption1);
                if(laDatos['LADATOS'][i]['CCENRES'] == laDatos['LADATA']['CRESCEN']){
                  loOption1.setAttribute("selected", laDatos['LADATOS'][i].CCENRES);
               }
            }
         });
      }
      function f_BuscarEmpleado() {
         $('#pcNomEmp').val('');
         var lcSend = "Id=CargarEmpleado&p_cCodEmp=" + $('#pcCodEmp').val();
         //alert(lcSend);
         $.post("Afj1070.php",lcSend).done(function(p_cResult) {
            //console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laData = JSON.parse(p_cResult);
            if (laData.ERROR) {
               Swal.fire(laData.ERROR);
               return
            } else if (laData.CESTADO != 'A') {
               Swal.fire('CÓDIGO NO ESTÁ ACTIVO');
               return
            }
            $('#pcNomEmp').val(laData.CNOMBRE);
            $('#pcEmpNom').val(laData.CNOMBRE);
         });
      }
      function f_BuscarEmpleadoDes() {
         $('#pcNomEmpDes').val('');
         var lcSend = "Id=CargarEmpleadoDes&p_cCodEmpDes=" + $('#pcCodEmpDes').val();
         // alert(lcSend);
         $.post("Afj1070.php",lcSend).done(function(p_cResult) {
            // alert(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laData = JSON.parse(p_cResult);
            if (laData.ERROR) {
               Swal.fire(laData.ERROR);
               return
            } else if (laData.CESTADO != 'A') {
               Swal.fire('CÓDIGO NO ESTÁ ACTIVO');
               return
            }
            $('#pcNomEmpDes').val(laData.CNOMBRE);
            $('#pcEmpNomDes').val(laData.CNOMBRE);
         });
      }

      function f_EnviarAprobar(p_cIdTrnf) {
         event.preventDefault();
         Swal.fire({
            title: '¿Desea Enviar Transferencia al Vicerectorado Administrativo para su Aprobación?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',   
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'SI',
            cancelButtonText: 'NO'
         })
         .then((result) => {
            $("#pcIdTrnf").val(p_cIdTrnf);
            $('#Id').val('Enviar');
            // console.log(result.value);
            if (result.value) {
               // console.log("entreeeee");
               $("#p_bFlag").val('true');
               $('#poForm').submit();
            }
            else{
               // console.log("saliii");
               $("#p_bFlag").val('false');
               $('#poForm').submit();
            }
         })
      }
   </script>   
   <style>
      .new-ucsm{
         background:#099957;
         color: white;
         font-weight: 500;
         padding: .375rem .75rem;
      }
      .head-fixed {
         position: sticky;
         top: 0;
         z-index: 1;
      }
   </style>
</head>
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div>
<form action="Afj1070.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>TRANSFERENCIAS</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-11">
      <div class="card text-center ">
      <div class="card-body" >
         <div style="height:680px; overflow-y: scroll;">
            <table class="table table-sm table-hover table-bordered">
               <thead class="thead-dark head-fixed">
                  <tr> 
                     <th class="text-center"  style="width: 50px">#</th>
                     <th class="text-center"  style="width: 100px">ID</th>
                     <th class="text-center"  style="width: 300px">FECHA</th>
                     <th class="text-center">DESCRIPCIÓN</th>
                     <th class="text-center" style="width: 100px">EST.</th>
                     <th class="text-center" style="width: 80px">
                        <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i></th>
                     <th class="text-center" style="width: 80px">
                        <img src="img/edit-white.png" width="20" height="20"></th>
                  </tr>
               </thead>
               <tbody>
               {$k = 1}
               {foreach from=$saDato item=i}
                  <tr class="text-center" class="custom-select" multiple>
                     <td class="text-center">{$k}</td>
                     <td class="text-center">{$i['CIDTRNF']}</td>
                     <td class="text-center">{$i['CTRASLA']}</td>
                     <td class="text-left">{$i['CDESCRI']}</td>
                     <td class="text-center">{$i['CESTADO']}</td>
                     <td class="text-center">
                        <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;" onclick="f_Reporte('{$i['CIDTRNF']}')"></i>
                     </td>
                     <td class="text-center">
                        <input type="image" src="img/edit.png" width="20" height="20" value="{$k-1}" onclick="f_Transferencia(this.value);"></td>
                  </tr>
               {$k = $k + 1}
               {/foreach}
               </tbody>
            </table>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton0" value="Nuevo" class="btn btn-primary col-md-3" formnovalidate>
               <i class="fas fa-file"></i>&nbsp;&nbsp;NUEVO</button>
            <button type="submit" name="Boton0" value="Salir" class="btn btn-danger col-md-3" style="height: 40px !important;" formnovalidate>
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
         <div class="input-group mb-2">
            <div class="input-group-prepend col-lg-1 px-0">
               <span class="input-group-text w-100 bg-ucsm">ID</span>
            </div>
            <input type="hidden" name="paData[CIDTRNF]" value="{$saData['CIDTRNF']}">
            <input type="text" value="{$saData['CIDTRNF']}" class="form-control text-uppercase col-lg-1 black-input" disabled>
            <div class="input-group-prepend col-lg-1 px-0">
               <span class="input-group-text w-100 bg-ucsm">Fecha</span>
            </div>
            <input id="pcHorario" class="form-control text-uppercase col-lg-2 black-input" name="paData[DTRASLA]" value="{$saData['DTRASLA']}" required>
            <!-- <input type="date" class="form-control text-uppercase col-lg-2 black-input" name="paData[DTRASLA]" value="{$saData['DTRASLA']}"> -->
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Descripción</span>
            </div>
            <input type="text" name="paData[CDESCRI]" value="{$saData['CDESCRI']}" class="form-control text-uppercase col-lg-6 black-input" maxlength="100">
         </div>
         <div class="col text-left" style="background-color:black;border-radius: 8px;border:1px solid grey; color: white;"><strong>Origen de los Activos:</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
            </div>
            <input type="hidden" name="paData[CDESCEN]" value="{$saData['CDESCEN']}">  
            <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos" onchange="f_CargarCentroResp();" name="paData[CCENCOS]" data-live-search="true">
               {foreach from=$saCenCos item=i}                        
                  <option value="{$i['CCENCOS']}" {if $i['CCENCOS'] eq $saData['CCENCOS']} selected {/if}>{$i['CDESCOS']}</option>
               {/foreach}
            </select>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
            </div>
            <select class="form-control form-control col-sm-12 black-input col-lg-5" id= "pcCenRes" name="paData[CCENRES]">         
            </select>
         </div>   
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
            </div>
            <input type="text" maxlength="4" id="pcCodEmp"  name="paData[CCODEMP]" value="{$saData['CCODEMP']}" class="form-control text-uppercase col-lg-4 black-input" onchange="f_BuscarEmpleado();">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Nombre Empleado</span>
            </div>
            <input type="hidden" id='pcEmpNom' name="paData[CNOMEMP]" value="{$saData['CNOMEMP']}">
            <input type="text" id="pcNomEmp" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CNOMEMP']}" disabled>
         </div>
         <div class="col text-left" style="background-color:black;border-radius: 8px;border:1px solid grey; color: white;"><strong>Destino de los Activos:</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
            </div>
            <input type="hidden" name="paData[CDESCCO]" value="{$saData['CDESCCO']}"> 
            <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos1" onchange="f_CargarCentroResp1();" name="paData[CCOSDES]" data-live-search="true">
               {foreach from=$saCenCos item=i}                      
                  <option value="{$i['CCENCOS']}" {if $i['CCENCOS'] eq $saData['CDESCOS']} selected {/if}>{$i['CDESCOS']}</option>
               {/foreach}                                          
            </select>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
            </div>
            <!-- <input type="hidden" name="paData[CRESDES]" value="{$saData['CRESDES']}">  -->
            <select class="form-control form-control col-sm-12 black-input col-lg-5" id= "pcCenRes1" name="paData[CRESCEN]">                                       
            </select>
         </div>   
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
            </div>
            <input type="text" maxlength="4" id="pcCodEmpDes"  name="paData[CCODRES]" value="{$saData['CCODREC']}" class="form-control text-uppercase col-lg-4 black-input" onchange="f_BuscarEmpleadoDes();">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Nombre Empleado</span>
            </div>
            <input type="hidden" id='pcEmpNomDes' name="paData[CEMPNOM]" value="{$saData['CNOMDES']}">
            <!-- <input type="text" id="pcNomEmpDes" class="form-control text-uppercase black-input col-lg-4" value="{$saEmpNom}" name="paData[CNOMDES]" disabled> -->
            <input type="text" id="pcNomEmpDes" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CNOMDES']}" name="paData[CNOMDES]" disabled>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton1" value="Aceptar" class="btn btn-primary col-md-2 " formnovalidate>
               <i class="fas fa-check"></i>&nbsp;&nbsp;ACEPTAR</button>
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 2}
      <input hidden id="p_bFlag" name="p_bFlag">
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-12">
      <div class="card text-center ">
      <div class="card-body" >
         <div class="input-group mb-2">
            <div class="input-group-prepend col-lg-1 px-0">
               <span class="input-group-text w-100 bg-ucsm">ID</span>
            </div>
            <input type="hidden" name="paData[CIDTRNF]" value="{$saData['CIDTRNF']}">
            <input type="text" value="{$saData['CIDTRNF']}" class="form-control text-uppercase col-lg-1 black-input" disabled>
            <div class="input-group-prepend col-lg-1 px-0">
               <span class="input-group-text w-100 bg-ucsm">Fecha</span>
            </div>
            <input type="text" value="{$saData['DTRASLA']}" class="form-control text-uppercase col-lg-2 black-input" disabled>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Descripción</span>
            </div>
            <input type="text" value="{$saData['CDESCRI']}" class="form-control text-uppercase col-lg-6 black-input" disabled>
         </div>
         <div class="col text-left" style="background-color:black;border-radius: 8px;border:1px solid grey; color: white;"><strong>Origen de los Activos:</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
            </div>
            <input type="text" value="{$saData['CDESCOS']}" class="form-control text-uppercase col-lg-4 black-input" disabled>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
            </div>
            <input type="text" value="{$saData['CDESRES']}" class="form-control text-uppercase col-lg-4 black-input" disabled>
         </div>   
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Empleado</span>
            </div>
            <input type="text" value="{$saData['CCODEMP']} - {$saData['CNOMEMP']}" class="form-control text-uppercase col-lg-4 black-input" disabled>
         </div>
         <div class="col text-left" style="background-color:black;border-radius: 8px;border:1px solid grey; color: white;"><strong>Destino de los Activos:</strong></div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
            </div>
            <input type="text" value="{$saData['CDESCCO']}" class="form-control text-uppercase col-lg-4 black-input" disabled>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
            </div>
            <input type="text" value="{$saData['CRESDES']}" class="form-control text-uppercase col-lg-4 black-input" disabled>
         </div>   
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Empleado</span>
            </div>
            <input type="text" value="{$saData['CCODRES']} - {$saData['CEMPNOM']}" class="form-control text-uppercase col-lg-4 black-input" disabled>
            &nbsp;&nbsp;
            <!-- <div class="form-check form-check-inline col-lg-2">
               <span class="input-group-text bg-ucsm">Enviar para Aprobar</span>
               &nbsp;&nbsp;
               <input class="form-check-input" type="checkbox" value="A" name="paData[CESTADO]" checked>   
               <input type = 'checkbox' id="idCheck">      
            </div> -->
         </div>
         <div style="height:230px; overflow-y: scroll;">
            <table class="table table-sm table-hover table-bordered">
               <thead class="thead-dark">
                  <tr>
                     <th>#</th> 
                     <th class="text-center">Código</th>
                     <th class="text-center">Activo Fijo</th>
                  </tr>
               </thead>
               <tbody>
               {$k = 1}
               {foreach from = $saDatos3 item=i}
                  <tr class="text-center" class="custom-select" multiple>
                     <td class="text-center">{$k}</td>
                     <td class="text-center">{$i['CCODIGO']}</td>
                     <td class="text-left">{$i['CDESCRI']}</td>
                  </tr>
               {$k = $k + 1}
               {/foreach}
               </tbody>
            </table>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button class="btn btn-warning col-md-2" onclick="f_EnviarAprobar('{$saData['CIDTRNF']}')">
               <i class="fas fa-check"></i>&nbsp;&nbsp;ENVIAR</button>
            <button type="submit" name="Boton2" value="Nuevo" class="btn btn-primary col-md-2 " formnovalidate>
               <i class="fas fa-plus"></i>&nbsp;&nbsp;NUEVO</button>
            <button type="submit" name="Boton2" value="NuevoVarios" class="btn btn-primary col-md-2 " formnovalidate>
               <i class="fas fa-plus"></i>&nbsp;&nbsp;NUEVO VARIOS</button>
            <button type="submit" name="Boton2" value="Transferir" class="btn btn-success col-md-2 " formnovalidate>
               <i class="fas fa-sync"></i>&nbsp;&nbsp;GUARDAR</button>
            <button type="submit" name="Boton2" value="Salir" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 3}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-10">
      <div class="card text-center ">
      <div class="card-body" >
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm bg-ucsm">Activo Fijo</span>
            </div>
            <input type="text" name="paData[CCODDES]" class="form-control text-uppercase col-lg-6 black-input" autofocus>
            &nbsp;&nbsp;
            <button type="submit" name="Boton3" value="Agregar"class="btn btn-primary col-md-1" formnovalidate><i class="fas fa-plus"></i>&nbsp;AGREGAR</button>
            &nbsp;&nbsp;
            <button type="submit" name="Boton3" value="PDFActivos"class="btn btn-danger col-md-1" formnovalidate><i class="fas fa-file"></i>&nbsp;&nbsp;PDF</button>
         </div>
         <br>
         <div class="input-group mb-1">
            <div style="height:350px;width:100%; overflow: scroll;">
               <table class="table table-hover table-sm table-bordered" id="myTable">
                  <thead class="thead-dark">
                     <tr class="text-center">
                        <th>#</th> 
                        <th scope="col">Código</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Activo</th>
                        <th scope="col" width="30" style="word-break:break-all;">
                           <img src="img/eliminar.png" width="30" height="30">
                        </th>
                     </tr>
                  </thead>
                  <tbody id="detallesRequerimiento">
                     {$k = 1}
                     {foreach from=$saDatos1 item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-center">{$i['CDESEST']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-center">
                              <form>
                                 <input type="hidden" name="paData[cCodigo]" value="{$k}"/>
                                 <button type="submit" name="Boton3" value="Eliminar" >
                                    <img src="img/eliminar.png" width="30" height="30">
                                 </button>
                              </form>
                           </td>
                        </tr>
                     {$k = $k + 1}
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton3" value="Guardar" class="btn btn-primary col-md-3 " formnovalidate>
               <i class="fas fa-save"></i>&nbsp;&nbsp;GUARDAR</button>
            <button type="submit" name="Boton3" value="Regresar" class="btn btn-danger col-md-3" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button>
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 4}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-10">
      <div class="card text-center ">
      <div class="card-body" >
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm bg-ucsm">De:</span>
            </div>
            <input type="text" name="paData[CCODDES]" class="form-control text-uppercase col-lg-2 black-input" autofocus>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm bg-ucsm">Hasta:</span>
            </div>
            <input type="text" name="paData[CCODHAS]" class="form-control text-uppercase col-lg-2 black-input" autofocus>
            <button type="submit" name="Boton4" value="AgregarVarios"class="btn btn-primary col-md-1 " formnovalidate><i class="fas fa-plus"></i>&nbsp;AGREGAR</button>
            &nbsp;&nbsp;
            <button type="submit" name="Boton4" value="PDFActivos"class="btn btn-danger col-md-1" formnovalidate><i class="fas fa-file"></i>&nbsp;&nbsp;PDF</button>
         </div>
         <br>
         <div class="input-group mb-1">
            <div style="height:350px;width:100%; overflow: scroll;">
               <table class="table table-hover table-sm table-bordered" id="myTable2">
                  <thead class="thead-dark">
                     <tr class="text-center">
                        <th>#</th> 
                        <th scope="col">Código</th>
                        <th scope="col">Estado</th>
                        <th scope="col">Activo</th>
                        <th scope="col" width="30" style="word-break:break-all;">
                           <img src="img/eliminar.png" width="30" height="30">
                        </th>
                     </tr>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {foreach from=$saDatos2 item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-center">{$i['CDESEST']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-center">
                              <form>
                                 <input type="hidden" name="paData[cCodigo]" value="{$k}"/>
                                 <button type="submit" name="Boton4" value="Eliminar" >
                                    <img src="img/eliminar.png" width="30" height="30">
                                 </button>
                              </form>
                           </td>
                        </tr>
                     {$k = $k + 1}
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton4" value="Guardar" class="btn btn-primary col-md-3 " formnovalidate>
               <i class="fas fa-save"></i>&nbsp;&nbsp;GUARDAR</button>
            <button type="submit" name="Boton3" value="Regresar" class="btn btn-danger col-md-3" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button>
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 5}
      <div class="card-body">
         <div class="row d-flex justify-content-center">
         <div class="col-sm-12">
         <div class="card text-center ">
         <div class="card-body" >
            <div class="input-group mb-2">
               <input type="hidden" name="paData[CIDTRNF]" value="*">
               <div class="input-group-prepend col-lg-1 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Fecha</span>
               </div>
               <input id="pcHorario" class="form-control text-uppercase col-lg-2 black-input" name="paData[DTRASLA]" value="{$saData['DTRASLA']}" required>
               <!-- <input type="date" class="form-control text-uppercase col-lg-2 black-input" name="paData[DTRASLA]" value="{$saData['DTRASLA']}"> -->
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Descripción</span>
               </div>
               <input type="text" name="paData[CDESCRI]" value="{$saData['CDESCRI']}" class="form-control text-uppercase col-lg-6 black-input" maxlength="245">
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
               </div>
               <!-- <input type="hidden" name="paData[CDESCCO]" value="{$saData['CDESCCO']}">  -->
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos1" onchange="f_CargarCentroResp1();" name="paData[CCOSDES]" data-live-search="true">
                  {foreach from=$saCenCos item=i}                      
                     <option value="{$i['CCENCOS']}" {if $i['CCENCOS'] eq $saData['CCOSDES']} selected {/if}>{$i['CDESCOS']}</option>
                  {/foreach}                                          
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-5" id= "pcCenRes1" name="paData[CRESCEN]">                                       
               </select>
            </div>   
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
               </div>
               <input type="text" maxlength="4" id="pcCodEmpDes"  name="paData[CCODRES]" value="{$saData['CCODRES']}" class="form-control text-uppercase col-lg-4 black-input" onchange="f_BuscarEmpleadoDes();">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Nombre Empleado</span>
               </div>
               <input type="hidden" id='pcEmpNomDes' name="paData[CEMPNOM]" value="{$saData['CNOMDES']}">
               <input type="text" id="pcNomEmpDes" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CNOMDES']}" name="paData[CNOMDES]" disabled>
            </div>
            <div class="input-group mb-1">
               <!-- agregar activos -->
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm bg-ucsm">Activo Fijo</span>
               </div>
               <input type="text" name="paData[CCODIGO]" class="form-control text-uppercase col-lg-6 black-input" >
               &nbsp;&nbsp;
               <button type="submit" name="Boton5" value="Agregar" class="btn btn-primary col-md-1" formnovalidate><i class="fas fa-plus"></i>&nbsp;AGREGAR</button>
            </div>
            <!-- <br> -->
            <div class="input-group mb-1">
               <div style="height:350px;width:100%; overflow: scroll;">
                  <table class="table table-hover table-sm table-bordered" id="myTable">
                     <thead class="thead-dark">
                        <tr class="text-center">
                           <th>#</th> 
                           <th scope="col">Código</th>
                           <th scope="col">Activo</th>
                           <th scope="col">Estado</th>
                           <th scope="col">Centro Resp.</th>
                           <th scope="col">Empleado</th>
                           <!-- <th scope="col" width="30" style="word-break:break-all;">
                              <img src="img/eliminar.png" width="30" height="30">
                           </th> -->
                        </tr>
                     </thead>
                     <tbody id="detallesRequerimiento">
                        {$k = 1}
                        {foreach from=$saDatos4 item=i}
                           <tr class="text-center" class="custom-select" multiple>
                              <td class="text-center">{$k}</td>
                              <td class="text-center">{$i['CCODIGO']}</td>
                              <td class="text-left">{$i['CDESCRI']}</td>
                              <td class="text-center">{$i['CDESSIT']}</td>
                              <td class="text-left">{$i['CCENRES']}-{$i['CDESRES']}</td>
                              <td class="text-left">{$i['CCODEMP']}-{$i['CNOMEMP']}</td>
                              <!-- <td class="text-center">
                                 <form>
                                    <input type="hidden" name="paData[cCodigo]" value="{$k}"/>
                                    <button type="submit" name="Boton3" value="Eliminar" >
                                       <img src="img/eliminar.png" width="30" height="30">
                                    </button>
                                 </form>
                              </td> -->
                           </tr>
                        {$k = $k + 1}
                        {/foreach}
                     </tbody>
                  </table>
               </div>
            </div>
            <div class="card-footer text-muted">
               <button type="submit" name="Boton5" value="Guardar" class="btn btn-primary col-md-3 " formnovalidate>
                  <i class="fas fa-save"></i>&nbsp;&nbsp;GUARDAR</button>
               <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-3" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button>
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
   