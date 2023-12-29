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
         {if $snBehavior eq 0}
            f_mBuscarCResp();
         {elseif $snBehavior eq 2}
            f_mBuscarCResp_Destino();
            var fecha = new Date();
            var mes = fecha.getMonth()+1;
            var dia = fecha.getDate(); 
            var ano = fecha.getFullYear();
            if(dia<10)
               dia='0'+dia;
            if(mes<10)
               mes='0'+mes;
            document.getElementById('fechaActual').value=ano+"-"+mes+"-"+dia;
         {/if}
      }

      function f_mCargarEmpleado() {
         document.getElementById("pcCodEmp1").options.length = 0;
         var lcSend = "Id=CargarEmpleado&pcCenRes=" + $('#pcCenRes').val();
         $.post("Afj1130.php",lcSend).done(function(p_cResult) {
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR: CENTRO DE RESPONSABILIDAD NO TIENE EMPLEADOS ENCARGADOS');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            for (var i = 0; i < laDatos.length; i++) {
               $('#pcCodEmp1').append('<option value="'+laDatos[i].CCODEMP+'">'+laDatos[i].CCODEMP + ` - ` +laDatos[i].CNOMEMP+'</option>');
            }
            $('#pcCodEmp1').selectpicker('refresh');
         });
      }
      
         
      function f_mBuscarCResp() {
         document.getElementById("pcCenRes").options.length = 0;
         var lcSend = "Id=CargarCentroResp&pcCenCos=" + $('#pcCenCos').val();
         //console.log(lcSend);
         $.post("Afj1130.php",lcSend).done( function(p_cResult) {
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR: CENTRO DE COSTO NO TIENE CENTROS DE RESPONSABILIDAaaaaaaaaaaaaaaaaaaaD');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            poCenRes = document.getElementById("pcCenRes");
            for (var i = 0; i < laDatos.length; i++) {
               if(i == 0){
                  $('#pcCenRes').append('<option selected value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES + ` - ` +laDatos[i].CDESRES+'</option>');
               }else{
                  $('#pcCenRes').append('<option value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES + ` - ` +laDatos[i].CDESRES+'</option>');
               }
            }
         $('#pcCenRes').selectpicker('refresh');
         
         f_mCargarEmpleado();
         });
      }

      function f_mBuscarCResp_Destino() {
         var lcSend = "Id=CargarCentroResp&pcCenCos=" + $('#pcCenCos2').val();
         //console.log(lcSend);
         $.post("Afj1130.php",lcSend).done(function(p_cResult) {
            //console.log(p_cResult); 
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR: CENTRO DE COSTO NO TIENE CENTROS DE RESPONSABILIDADDDDDDDDDDDD');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            for (var i = 0; i < laDatos.length; i++) {
               $('#pcCenRes2').append('<option value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES + ` - ` +laDatos[i].CDESRES+'</option>');
            }
            $('#pcCenRes2').selectpicker('refresh');
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
         $.post("Afj1130.php", lcSend).done(function(lcResult) {
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

      function marcar(source) {
         checkboxes = document.getElementsByTagName('input'); //obtenemos todos los controles del tipo Input
         for(i=0;i<checkboxes.length;i++){
            if(checkboxes[i].type == "checkbox"){
               checkboxes[i].checked=source.checked;
            }
         }
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
<form action="Afj1130.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid" style="width: 100%;">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>CAMBIAR EMPLEADO RESPONSABLE DE ACTIVOS FIJOS</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="card-body">
            <div class="row d-flex justify-content-center">
            <div class="col-sm-8">
            <div class="card text-center ">
            <div class="card-body" >
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
                  </div>
                  <select class="selectpicker form-control col-sm-12 black-input col-lg-6" data-live-search="true" id="pcCenCos" onchange="f_mBuscarCResp();" name="paData[CCENCOS]" >
                     {foreach from=$saCenCos item=i}                        
                        <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                     {/foreach}                                          
                  </select>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
                  </div>
                  <select class="selectpicker form-control form-control-sm col-sm-6" data-live-search="true" id= "pcCenRes" name="paData[CCENRES]" onchange="f_mCargarEmpleado();">                                        
                  </select>
               </div>
               <div class="input-group mb-0">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
                  </div>
                  <select class="selectpicker form-control form-control-sm col-sm-6" data-live-search="true" id="pcCodEmp1" name="paData[CCODEMP]">
                  </select>
               </div>
               <div class="card-footer text-center">
                  <button type="submit" name="Boton" value="Buscar" class="btn btn-primary col-md-2" formnovalidate > 
                     <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
                  <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2"  formnovalidate>
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
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
            </div>
            <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saDato['CCENRES']} - {$saDato['CDESCRI']}">
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Empleado</span>
            </div>
            <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saDato['CCODEMP']} - {$saDato['CNOMEMP']}">
         </div>
         <div class="card text-center ">
         <div style="height:700px; overflow-y: scroll;">
            <div>
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr class="text-center">
                        <th>Codigo</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Sit.</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Color</th>
                        <th>Serie</th>
                        <th>
                           <input type="checkbox" onclick="marcar(this);" />
                        </th>
                     </tr>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-center">{$i['DFECALT']}</td>
                           <td class="text-center">{$i['CSITUAC']}</td>
                           <td class="text-left">{$i['CDATOS']['CMARCA']}</td>
                           <td class="text-left">{$i['CDATOS']['CMODELO']}</td>
                           <td class="text-left">{$i['CDATOS']['CCOLOR']}</td>
                           <td class="text-left">{$i['CDATOS']['CNROSER']}</td>
                           <td class="text-left">
                              <input type="checkbox" title="Seleccionar Activos" name="ACodAct[]"
                                 value="{$i['CACTFIJ']}">
                           </td>
                        </tr>   
                     {$k = $k + 1}
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton1" value="IngresarResponsable" class="btn btn-success col-md-2" formnovalidate>
               &nbsp;&nbsp;INGRESAR NUEVO RESPONSABLE</button>
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 2}
         <br>
         <div class="row d-flex " style="margin-left: 20rem">
         <div class="col-sm-8">
         <div class="card text-center ">
         <div class="card-body" >
         <br>
            <div style="padding-left: 1rem;">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Fecha</span>
                  </div>
                  <input type="date" class="form-control text-uppercase black-input col-lg-2" name="paData[DTRASLA]" id="fechaActual" value="">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Descripción</span>
                  </div>
                  <input type="text" name="paData[CDESCRI]" value="{$saData['CDESCRI']}" class="form-control text-uppercase col-lg-8 black-input" maxlength="240" AUTO>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
                  </div>
                  <select class="selectpicker form-control black-input col-lg-8" data-live-search="true" id="pcCenCos2" onchange="f_mBuscarCResp_Destino();" name="paData[CCOSDES]" >
                     {foreach from=$saCenCos item=i}                        
                        <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                     {/foreach}                                          
                  </select>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
                  </div>
                  <select class="selectpicker form-control black-input col-lg-8" id="pcCenRes2" name="paData[CRESDES]" data-live-search="true" >
                  </select>
               </div>
               <div class="input-group mb-0">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
                  </div>
                  <input type="text" id="pcCriBus" placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-2" style="text-transform: uppercase;">
                  <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado();">
                     <a class="justify-content-center" title="Buscar" >
                        <img src="img/lupa1.png" width="20" height="20">
                     </a>
                  </button>
                  <select id="pcCodEmp" name="paData[CCODDES]"  class="form-control form-control-sm col-sm-6" style="height: 44px">
                  </select>
               </div>
            </div>
            <br><br>
            <div class="card-footer text-center">
               <button type="submit" name="Boton2" value="Transferir" class="btn btn-primary col-md-4" formnovalidate>
                  &nbsp;&nbsp;TRANSFERIR CON EMAIL</button>
               <button type="submit" name="Boton2" value="TransferirSinEmail" class="btn btn-success col-md-3 " formnovalidate>
                     <i class="fas fa-save"></i>&nbsp;&nbsp;TRANSFERIR</button>
               <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-4" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
            </div>
         </div>
         </div>
         </div>
         </div>
      {/if}
   </div>
   <div id="footer" style="margin-top: 20rem;"></div>
</form>
</body>
</html>