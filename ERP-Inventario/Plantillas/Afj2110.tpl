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
         f_BuscarTipo();
         f_BuscarTipoFin();
         f_mBuscarCResp();
      }

      function f_mBuscarCResp() {
         document.getElementById("pcCenRes").options.length = 0;
         var lcSend = "Id=CargarCentroResp&pcCenCos=" + $('#pcCenCos').val();
         // console.log(lcSend);
         $.post("Afj2110.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR: CENTRO DE COSTO NO TIENE CENTROS DE RESPONSABILIDAD');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            $('#pcCenRes').append('<option value="*">*</option>');
            console.log(pcCenRes);
            for (var i = 0; i < laDatos.length; i++) {
               $('#pcCenRes').append('<option value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES+' - '+laDatos[i].CDESRES+'</option>');
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
         $.post("Afj2110.php", lcSend).done(function(lcResult) {
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
               $('#pcCodEmp'+id).selectpicker('refresh');
            }
         });
      }

      function f_BuscarTipo() {
         document.getElementById("pcTipAfj").options.length = 0;
         var lcSend = "Id=ListarTipos&pcClase=" + $('#pcClase').val();
         // alert(lcSend);
         $.post("Afj2110.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            // console.log(laDatos);
            loTipAfj = document.getElementById("pcTipAfj");
            for (var i = 0; i < laDatos['LADATOS'].length; i++) {
                // console.log(laDatos[i]);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos['LADATA'][i].CTIPAFJ);
                loOption.setAttribute("label", laDatos['LADATA'][i].CTIPAFJ + ` - ` + laDatos['LADATA'][i].CDESCRI);
                loTipAfj.appendChild(loOption);
                if(laDatos['LADATOS'][i]['CTIPAFJ'] == '01010'){
                  loOption.setAttribute("selected", laDatos['LADATA'][i]['CTIPAFJ']);
               } 
            }
         });
      }

      function f_BuscarTipoFin() {
         document.getElementById("pcTipAfjFin").options.length = 0;
         var lcSend = "Id=ListarTiposFin&pcClaseFin=" + $('#pcClaseFin').val();
         // alert(lcSend);
         $.post("Afj2110.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            loTipAfj = document.getElementById("pcTipAfjFin");
            for (var i = 0; i < laDatos['LADATOS'].length; i++) {
                // console.log(laDatos[i]);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos['LADATA'][i].CTIPAFJ);
                loOption.setAttribute("label", laDatos['LADATA'][i].CTIPAFJ + ` - ` + laDatos['LADATA'][i].CDESCRI);
                loTipAfj.appendChild(loOption);
                if(laDatos['LADATOS'][i]['CTIPAFJ'] == '52210'){
                  loOption.setAttribute("selected", laDatos['LADATA'][i]['CTIPAFJ']);
               } 
            }
         });
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
<form action="Afj2110.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>REPORTE INVENTARIO POR AÑO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-8">
      <div class="card text-center ">
      <div class="card-body" >
         {* Buscar activo fijo *}
         <div style="padding-left: 5rem;">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-8" data-live-search="true" id="pcCenCos" onchange="f_mBuscarCResp();" name="paData[CCENCOS]" >
                  {foreach from=$saCenCos item=i}                        
                     <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                  {/foreach}                                          
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
               </div>
               <input type="hidden" name="paData[CDESRES]" id ="pcDesRes">
               <select class="selectpicker form-control form-control-sm col-sm-12 black-input col-lg-8" id="pcCenRes" data-live-search="true" name="paData[CCENRES]">
               </select>
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Código Empleado</span>
               </div>
               <input type="text" id="pcCriBus"  placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-2" style="text-transform: uppercase;">
               <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado();">
                  <a class="justify-content-center" title="Buscar" >
                     <img src="img/lupa1.png" width="20" height="20∫">
                  </a>
               </button>
               <select id="pcCodEmp" name="paData[CCODEMP]"  class="form-control form-control-sm col-sm-5" style="height: 44px">
                  <!-- <option value="{$saData['CCODEMP']}" selected>{$saData['CCODEMP']} - {$saData['CNOMEMP']}</option> -->
               </select>
            </div>
            <!-- <div class="input-group mb-0">
               <select id="pcEmpleado1" name="paData[CCODEMP]" class="form-control form-control-sm col-sm-8" style="height: 44px"></select>
            </div> -->
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Clase Inicial</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-8" data-live-search="true" id="pcClase" name="paData[CCODCLA]" onchange="f_BuscarTipo();">
                  {foreach from=$saDatCla item=i}                        
                     <option  value="{$i['CCODCLA']}" {if $i['CCODCLA'] eq '01'} selected{/if}>{$i['CCODCLA']} - {$i['CDESCLA']}</option>
                  {/foreach}                                          
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Clase Final</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-8" data-live-search="true" id="pcClaseFin" name="paData[CCODCLAF]" onchange="f_BuscarTipoFin();">
                  {foreach from=$saDatCla item=i}                        
                     <option  value="{$i['CCODCLA']}" {if $i['CCODCLA'] eq '52'} selected{/if}>{$i['CCODCLA']} - {$i['CDESCLA']}</option>
                  {/foreach}                                          
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Tipo Inicial</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-8" id="pcTipAfj" name="paData[CTIPAFJ]">
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Tipo Final</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-8" id="pcTipAfjFin" name="paData[CTIPAFJF]">
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Situación</span>
               </div>
               <select class="form-control col-sm-12 black-input col-lg-8" name="paData[CSITUAC]">
                  <option  value="*">*</option>
                  {foreach from=$saSituac item=i}                        
                     <option  value="{$i['CSITUAC']}">{$i['CDESCRI']}</option>
                  {/foreach}                                          
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Inventario</span>
               </div>
               <input type="text" maxlength="4" name="paData[CINVENT]" class="form-control text-uppercase col-lg-1 black-input" placeholder="AAAA" >
            </div>
         </div>
         <div class="card-footer text-center">
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
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
            </div>
            <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCENCOS']} - {$saData['CDESCOS']}">
         </div>
         <div class="card text-center ">
         <div style="height:690px; overflow-y: scroll;">
            <div>
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr class="text-center">
                        <th>#</th>
                        <th>Codigo</th>
                        <th>Descripción</th>
                        <th>Fecha</th>
                        <th>Sit.</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Color</th>
                        <th>Serie</th>
                        <th>Monto</th>
                     </tr>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-center">{$i['DFECALT']}</td>
                           <td class="text-center">{$i['CSITUAC']}</td>
                           <td class="text-left">{$i['CDATOS']['CMARCA']}</td>
                           <td class="text-left">{$i['CDATOS']['CMODELO']}</td>
                           <td class="text-left">{$i['CDATOS']['CCOLOR']}</td>
                           <td class="text-left">{$i['CDATOS']['CNROSER']}</td>
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                        </tr>   
                     {$k = $k + 1}
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton1" value="PDF" class="btn btn-primary col-md-2" onclick="f_ReportePDF();"formnovalidate><i class="fas fa-file"></i>&nbsp;&nbsp;PDF</button>
            <button type="submit" name="Boton1" value="EXCEL" class="btn btn-success col-md-2" formnovalidate><i class="fas fa-file-excel"></i>&nbsp;&nbsp;EXCEL</button>
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
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