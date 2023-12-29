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

      function Init() {
         var fecha = new Date();
         // console.log(fecha);
         var mes = fecha.getMonth()+1;
         var dia = fecha.getDate(); 
         var ano = fecha.getFullYear();
         if(dia<10)
            dia='0'+dia;
         if(mes<10)
            mes='0'+mes;
         document.getElementById('fechaActual').value=ano+"-"+mes+"-"+dia;
      }

      function f_CambiarDescripcion(p_cIdTrnf, p_cDescri) {
         $('#m_descripcion').modal('show');
         $('#pctrnf').val(p_cIdTrnf);
         $('#p_cDescri').val(p_cDescri);
      }

      function f_Reporte(p_cIdTrnf) {
         var lcSend = "Id=ReporteTranf&pcIdTrnf=" + p_cIdTrnf;
         //alert(lcSend);
         $.post("Afj1170.php", lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            //console.log(laJson.CREPORT);
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
            // window.open('./Docs/TransActFij/T'+p_cIdTrnf+'.pdf', '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
         });
      }
      
      function f_CargarCentroResp() {
         document.getElementById("pcCenRes").options.length = 0;
         var lcSend = "Id=CargarCentrosRes&pcCenCos=" + $('#pcCenCos').val();
         //alert(lcSend);
         $.post("Afj1170.php",lcSend).done(function(p_cResult) {
            //console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            // console.log(laDatos);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            console.log(laDatos);
            loCenRes = document.getElementById("pcCenRes");
            for (var i = 0; i < laDatos.length; i++) {
               $('#pcCenRes').append('<option value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES + ` - ` +laDatos[i].CDESRES+'</option>');
            }
            $("#pcCenRes").selectpicker("refresh");
         });
      }
      
      function f_buscarEmpleado() {
         var lcCriBus = document.getElementById("pcCriBus").value;
         if (lcCriBus.length < 4) {
            alert("DEBE INGRESAR AL MENOS 4 CARACTERES PARA LA BÚSQUEDA");
            return;
         }
         var lcCodEmp = document.getElementById("pcCodEmp");
         lcCodEmp.innerHTML = '';
         var lcSend = "Id=BuscarEmpleado&pcCriBus=" + lcCriBus;
         // alert(lcSend);
         $.post("Afj1170.php", lcSend).done(function(lcResult) {
            // alert(lcResult);
            var laJson = JSON.parse(lcResult);
            if (laJson.ERROR) {
               alert(laJson.ERROR);
            } else {
               for (var i = 0; i < laJson.length; i++) {
                  // console.log(laJson); 
                  var option = document.createElement("option");
                   option.text = laJson[i].CCODUSU + " - " + laJson[i].CNOMBRE;
                   option.value = laJson[i].CCODUSU;
                   lcCodEmp.add(option);
               }
               $('#pcCodEmp').selectpicker('refresh');
            }
         });
      }

      function f_CargarCentroRespDes() {
         document.getElementById("pcCenRes1").options.length = 0;
         var lcSend = "Id=CargarCentrosRes&pcCenCos=" + $('#pcCenCos1').val();
         //alert(lcSend);
         $.post("Afj1170.php",lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            // console.log(laDatos);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            console.log(laDatos);
            loCenRes = document.getElementById("pcCenRes1");
            for (var i = 0; i < laDatos.length; i++) {
               $('#pcCenRes1').append('<option value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES + ` - ` +laDatos[i].CDESRES+'</option>');
            }
            $("#pcCenRes1").selectpicker("refresh");
         });
      }
      
      function f_buscarEmpleadoDes() {
         var lcCriBus = document.getElementById("pcCriBus1").value;
         if (lcCriBus.length < 4) {
            alert("DEBE INGRESAR AL MENOS 4 CARACTERES PARA LA BÚSQUEDA");
            return;
         }
         var lcCodEmp = document.getElementById("pcCodEmp1");
         lcCodEmp.innerHTML = '';
         var lcSend = "Id=BuscarEmpleado&pcCriBus=" + lcCriBus;
         // alert(lcSend);
         $.post("Afj1170.php", lcSend).done(function(lcResult) {
            // alert(lcResult);
            var laJson = JSON.parse(lcResult);
            if (laJson.ERROR) {
               alert(laJson.ERROR);
            } else {
               for (var i = 0; i < laJson.length; i++) {
                  // console.log(laJson); 
                  var option = document.createElement("option");
                   option.text = laJson[i].CCODUSU + " - " + laJson[i].CNOMBRE;
                   option.value = laJson[i].CCODUSU;
                   lcCodEmp.add(option);
               }
               $('#pcCodEmp1').selectpicker('refresh');
            }
         });
      }

      function f_EliminarActFij(p_nIndice){
         $('#Id').val('EliminarActFij')
         $('#pnIndice').val(p_nIndice)
         $('#poForm').submit()   
      }


      function f_EliminarTransferencia(p_cIdTrnf){
         event.preventDefault();
         Swal.fire({
            title: '¿Desea eliminar transferencia?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',   
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'SI',
            cancelButtonText: 'NO'
         })
         .then((result) => {
            $('#Id').val('EliminarTransferencia');
            $('#pcIdTrnf').val(p_cIdTrnf)
            if (result.value) {
               // console.log("entreeeee");
               $("#p_bFlag").val('true');
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
<form action="Afj1170.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pcIdTrnf" id="pcIdTrnf">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>TRANSFERENCIAS DE ACTIVOS FIJOS</strong></div>
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
                        <i class="fas fa-edit icon-tbl" style="color:#75c25d;"></i></th>
                     {if {$saData['CUSUCOD']} eq '3280' or {$saData['CUSUCOD']} eq '1872'}
                     <th class="text-center" style="width: 80px">
                        <i class="fas fa-times icon-tbl" style="color:#C70039;"></i></th>
                     {/if}
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
                     <th class="text-center" style="width: 80px"> 
                        <i class="fas fa-edit icon-tbl" style="color:#75c25d;" onclick="f_CambiarDescripcion('{$i['CIDTRNF']}', '{$i['CDESCRI']}')" > </i></th>
                     {if {$saData['CUSUCOD']} eq '3280' or {$saData['CUSUCOD']} eq '1872'}
                     <th class="text-center" style="width: 80px"> 
                        <i class="fas fa-times icon-tbl" style="color:#C70039;" onclick="f_EliminarTransferencia('{$i['CIDTRNF']}')" > </i></th>
                     {/if}
                  </tr>
               {$k = $k + 1}
               {/foreach}
               </tbody>
            </table>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton0" value="BuscarTransferencias" class="btn btn-warning col-md-2 " formnovalidate>
                  <i class="fas fa-file"></i>&nbsp;&nbsp;BUSCAR ACTIVO FIJO</button>
            <button type="submit" name="Boton0" value="Nuevo" class="btn btn-primary col-md-2 " formnovalidate>
               <i class="fas fa-file"></i>&nbsp;&nbsp;NUEVA TRANSFERENCIA</button>
            <button type="submit" name="Boton0" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
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
                  <span class="input-group-text w-100 new-ucsm bg-ucsm">Activo Fijo</span>
               </div>
               <input type="text" name="paData[CCODIGO]" class="form-control text-uppercase col-lg-6 black-input" autofocus>
               &nbsp;&nbsp;
               <button type="submit" name="Boton1" value="Agregar" class="btn btn-primary col-md-1" formnovalidate><i class="fas fa-plus"></i>&nbsp;AGREGAR</button>
            </div>
            <div class="input-group mb-1">
               <div style="height:650px;width:100%; overflow: scroll;">
                  <table class="table table-hover table-sm table-bordered" id="myTable">
                     <thead class="thead-dark">
                        <tr class="text-center">
                           <th>#</th> 
                           <th scope="col">Act.Fij</th>
                           <th scope="col">Código</th>
                           <th scope="col">Activo</th>
                           <th scope="col">Estado</th>
                           <th scope="col">Centro Resp.</th>
                           <th scope="col">Empleado</th>
                           <th scope="col" width="30" style="word-break:break-all;">
                              <img src="img/eliminar.png" width="30" height="30">
                           </th>
                        </tr>
                     </thead>
                     <tbody id="detallesRequerimiento">
                        {$k = 1}
                        {foreach from=$saDatos item=i}
                           <tr class="text-center" class="custom-select" multiple>
                              <td class="text-center">{$k}</td>
                              <td class="text-center">{$i['CACTFIJ']}</td>
                              <td class="text-center">{$i['CCODIGO']}</td>
                              <td class="text-left">{$i['CDESCRI']}</td>
                              <td class="text-center">{$i['CDESSIT']}</td>
                              <td class="text-left">{$i['CCENRES']} - {$i['CDESRES']}</td>
                              <td class="text-left">{$i['CCODEMP']} - {$i['CNOMEMP']}</td>
                              <td class="align-middle text-center p-0"><button type="button" value="{$k}" onclick="f_EliminarActFij(this.value);" tabindex="-1"><img src="img/eliminar.png" width="30" height="30"></button></td>
                           </tr>
                        {$k = $k + 1}
                        {/foreach}
                     </tbody>
                  </table>
               </div>
            </div>
            <div class="card-footer text-muted">
               <button type="submit" name="Boton1" value="Guardar" class="btn btn-primary col-md-3 " formnovalidate>
                  <i class="fas fa-save"></i>&nbsp;&nbsp;GUARDAR</button>
               <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-3" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button>
            </div>
         </div>
         </div>
         </div>
         </div>
         </div>
      {else if $snBehavior eq 2}
         <div class="card-body">
            <div class="row d-flex justify-content-center">
            <div class="col-sm-12">
            <div class="card text-center ">
            <div class="card-body" >
            <div class="input-group mb-2">
               <div class="input-group-prepend col-lg-1 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Fecha</span>
               </div>
               <input type="date" class="form-control text-uppercase black-input col-lg-2" name="paData[DTRASLA]" id="fechaActual" value="">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Descripción</span>
               </div>
               <input type="text" name="paData[CDESCRI]" value="{$saData['CDESCRI']}" class="form-control text-uppercase col-lg-8 black-input" maxlength="240">
            </div>
            <div class="col text-left" style="background-color:black;border-radius: 8px;border:1px solid grey; color: white;"><strong>Origen de los Activos:</strong></div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos" onchange="f_CargarCentroResp();" data-live-search="true">
                  {foreach from=$saCenCos item=i}                        
                     <option value="{$i['CCENCOS']}"> {$i['CCENCOS']} - {$i['CDESCOS']}</option>
                  {/foreach}
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
               </div>
               <select class="selectpicker form-control form-control-sm col-sm-12 black-input col-lg-8" id="pcCenRes" data-live-search="true" name="paData[CCENRES]">
                  </select>
            </div>   
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
               </div>
               <input type="text" id="pcCriBus"  placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-3" style="text-transform: uppercase;">
               <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado();">
                  <a class="justify-content-center" title="Buscar" >
                     <img src="img/lupa1.png" width="20" height="20∫">
                  </a>
               </button>
               <select id="pcCodEmp" name="paData[CCODEMP]"  class="form-control form-control-sm col-sm-5" style="height: 44px">
               </select>
            </div>
            <div class="col text-left" style="background-color:black;border-radius: 8px;border:1px solid grey; color: white;"><strong>Destino de los Activos:</strong></div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos1" onchange="f_CargarCentroRespDes();"  data-live-search="true">
                  {foreach from=$saCenCos item=i}                      
                     <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCOS']}</option>
                  {/foreach}                                          
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro Responsabilidad</span>
               </div>
               <!-- <input type="hidden" name="paData[CRESDES]" value="{$saData['CRESDES']}">  -->
               <select class="selectpicker form-control form-control-sm col-sm-12 black-input col-lg-8" id="pcCenRes1" data-live-search="true" name="paData[CRESDES]">
                  </select>
            </div>   
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
               </div>
               <input type="text" id="pcCriBus1"  placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-3" style="text-transform: uppercase;">
               <button type="button" id="b_buscar1" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleadoDes();">
                  <a class="justify-content-center" title="Buscar" >
                     <img src="img/lupa1.png" width="20" height="20∫">
                  </a>
               </button>
               <select id="pcCodEmp1" name="paData[CCODDES]"  class="form-control form-control-sm col-sm-5" style="height: 44px">
               </select>
            </div>
               <div class="input-group mb-1">
                  <div style="height:350px;width:100%; overflow: scroll;">
                     <table class="table table-hover table-sm table-bordered" id="myTable">
                        <thead class="thead-dark">
                           <tr class="text-center">
                              <th>#</th> 
                              <th scope="col">Act.Fij.</th>
                              <th scope="col">Código</th>
                              <th scope="col">Activo</th>
                              <th scope="col">Estado</th>
                              <th scope="col">Centro Resp.</th>
                              <th scope="col">Empleado</th>
                           </tr>
                        </thead>
                        <tbody id="detallesRequerimiento">
                           {$k = 1}
                           {foreach from=$saDatos item=i}
                              <tr class="text-center" class="custom-select" multiple>
                                 <td class="text-center">{$k}</td>
                                 <td class="text-center">{$i['CACTFIJ']}</td>
                                 <td class="text-center">{$i['CCODIGO']}</td>
                                 <td class="text-left">{$i['CDESCRI']}</td>
                                 <td class="text-center">{$i['CDESSIT']}</td>
                                 <td class="text-left">{$i['CCENRES']}-{$i['CDESRES']}</td>
                                 <td class="text-left">{$i['CCODEMP']}-{$i['CNOMEMP']}</td>
                              </tr>
                           {$k = $k + 1}
                           {/foreach}
                        </tbody>
                     </table>
                  </div>
               </div>
               <div class="card-footer text-muted">
                  <button type="submit" name="Boton2" value="Transferir" class="btn btn-primary col-md-3 " formnovalidate>
                     <i class="fas fa-save"></i>&nbsp;&nbsp;TRANSFERIR CON EMAIL</button>
                  <button type="submit" name="Boton2" value="TransferirSinEmail" class="btn btn-success col-md-3 " formnovalidate>
                     <i class="fas fa-save"></i>&nbsp;&nbsp;TRANSFERIR</button>
                  <button type="submit" name="Boton2" value="Regresar" class="btn btn-danger col-md-3" formnovalidate>
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
<!-- Modal de modificacion de descripcion-->
<div class="modal fade" id="m_descripcion" tabindex="-1">
<div class="modal-dialog mw-80" role="document">
   <form action="Afj1170.php" method="POST">
      <input type="hidden" name="paData[CIDTRNF]" value="{$saData['CIDTRNF']}" id="pctrnf">
      <div class="modal-content">
         <div class="modal-header" style="background-color: #fbc804; color: black; font-weight: 500; ">
            <h5 class="modal-title col-md-8">DESCRIPCIÓN</h5>
            <span class="badge badge-secondary p-2 col-md-8 float-right" style="border:none" id="s_cIdM1"></span>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
            <div class="form-group">
               <div id="d_observar">
                  <textarea id="p_cDescri" name="paData[CDESCRI]" class="form-control" rows="3" autofocus value="{$saData['CDESCRI']}"></textarea>
               </div>
            </div>
         </div>
         <div id="d_botonobservar" class="modal-footer">
            <button type="submit" name="BotonM" value='CambiarDescripcion' class="btn btn-warning col-md-2">GUARDAR</button>
            <button type="submit" class="btn btn-danger col-md-2" data-dismiss="modal">CERRAR</button>
         </div>
      </div>
   </form>
</div>
</div>
</body>
</html>
   