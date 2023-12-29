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
            var fecha = new Date(); 
            var ano = fecha.getFullYear();
            document.getElementById('fechaActual').value=ano;
         {/if}
      }

      function f_BuscarEmpleadoInventario(p_nCenRes, p_nDescri) {
         // alert(p_nCenRes);
         $('#pnCenRes').val(p_nCenRes);
         $('#pnDescri').val(p_nDescri);
         $('#Id').val('BuscarEmpleadoInventario');
         $('#poForm').submit();
      }

      function f_BuscarActFijInventario(p_nCenRes, p_nDescri, p_nCodEmp) {
         // alert(p_nCodEmp);
         $('#pnCenRes').val(p_nCenRes);
         $('#pnDescri').val(p_nDescri);
         $('#pnCodEmp').val(p_nCodEmp);
         $('#Id').val('BuscarActFijInventario');
         $('#poForm').submit();
      }

      function marcar(source) {
         checkboxes = document.getElementsByTagName('input'); //obtenemos todos los controles del tipo Input
         for(i=0;i<checkboxes.length;i++){
            if(checkboxes[i].type == "checkbox"){
               checkboxes[i].checked=source.checked;
            }
         }
      }

      function f_ReporteInventario(p_nCenRes){
         var lcSend = "Id=ReporteInventario&p_nCenRes=" + p_nCenRes;
         //alert(lcSend);
         $.post("Afj1140.php", lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            // console.log(laJson.CREPORT);
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1000, height=1000');
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
         $.post("Afj1140.php", lcSend).done(function(lcResult) {
            // alert(lcResult);
            var laJson = JSON.parse(lcResult);
            if (laJson.ERROR) {
               alert(laJson.ERROR);
            } else {
               for (var i = 0; i < laJson.length; i++) {
                  console.log(laJson); 
                  var option = document.createElement("option");
                  option.text = laJson[i].CCODUSU + " - " + laJson[i].CNOMBRE+ " - " +laJson[i].CEMAIL;
                  option.value = laJson[i].CCODUSU;
                  lcCodEmp.add(option);
               }
               $('#pcCodEmp').selectpicker('refresh');
            }
         });
      }

      function f_buscarEmpleado1() {
         var lcCriBus1 = document.getElementById("pcCriBus1").value;
         if (lcCriBus1.length < 4) {
            alert("DEBE INGRESAR AL MENOS 4 CARACTERES PARA LA BÚSQUEDA");
            return;
         }
         var lcCodEmp = document.getElementById("pcCodEmp1");
         lcCodEmp.innerHTML = '';
         var lcSend = "Id=BuscarEmpleado&pcCriBus=" + lcCriBus1;
         // alert(lcSend);
         $.post("Afj1140.php", lcSend).done(function(lcResult) {
            // alert(lcResult);
            var laJson = JSON.parse(lcResult);
            if (laJson.ERROR) {
               alert(laJson.ERROR);
            } else {
               for (var i = 0; i < laJson.length; i++) {
                  console.log(laJson); 
                  var option = document.createElement("option");
                  option.text = laJson[i].CCODUSU + " - " + laJson[i].CNOMBRE+ " - " +laJson[i].CEMAIL;
                  option.value = laJson[i].CCODUSU;
                  lcCodEmp.add(option);
               }
               $('#pcCodEmp1').selectpicker('refresh');
            }
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
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div>
<form action="Afj1140.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="paData[CCENRES]" id="pnCenRes">
   <input type="hidden" name="paData[CDESCRI]" id="pnDescri">
   <input type="hidden" name="paData[CCODEMP]" id="pnCodEmp">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>REGISTRO DE INVENTARIO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="card">
         <div class="row p-3 justify-content-center">
         <div class="col-sm-10">
            <div class="input-group mb-2">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos" name="paData[CCENCOS]" data-live-search="true">
                  {foreach from=$saCenCos item=i}                        
                     <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                  {/foreach}                                          
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">AÑO DE INVENTARIO</span>
               </div>
               <input id="fechaActual" class="form-control text-uppercase col-lg-1 black-input"  name="paData[CYEAR]">
               &nbsp;&nbsp;
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
                  <thead class="thead-dark">
                     <tr style="text-align: left;">
                        <th scope="col">#</th>
                        <th scope="col" style="text-align: center;">Centro de Responsabilidad</th>
                        <th scope="col" style="text-align: center;">Total de Act.Fij.</th>
                        <th scope="col" style="text-align: center;">Act.Fij. Inventariados</th>
                        <th scope="col" style="text-align: center;">Act.Fij. Faltantes</th>
                        <th class="text-center"><i class="fas fa-file-pdf icon-tbl" style="color:#C70039; "></th>
                        <th class="text-center"><img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/></th>
                     </tr>
                  </thead>
                  <tbody class="BodTab" id="tbl_ConPat">
                     {$k=1}
                     {foreach from=$saData item=i}
                        {if $i['NTOTAL'] == $i['NINVENT']}
                        <tr class="text-center table-info" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-left">{$i['CCENRES']} - {$i['CDESCRI']}</td>
                           <td class="text-right">{$i['NTOTAL']}</td>
                           <td class="text-right">{$i['NINVENT']}</td>
                           <td class="text-right">{$i['NFALINV']}</td>
                           <td class="text-center"  onclick="f_ReporteInventario('{$i['CCENRES']}')">
                              <i class="fas fa-file-pdf icon-tbl" style="color:#C70039; "></i></a></td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_BuscarEmpleadoInventario('{$i['CCENRES']}','{$i['CDESCRI']}');">
                              <img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/>
                           </td>
                        </tr>            
                        {else if $i['NTOTAL'] > $i['NINVENT'] AND $i['NINVENT'] > 0 }
                        <tr class="text-center table-warning" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-left">{$i['CCENRES']} - {$i['CDESCRI']}</td>
                           <td class="text-right">{$i['NTOTAL']}</td>
                           <td class="text-right">{$i['NINVENT']}</td>
                           <td class="text-right">{$i['NFALINV']}</td>
                           <td class="text-center"> </td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_BuscarEmpleadoInventario('{$i['CCENRES']}','{$i['CDESCRI']}');">
                              <img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/>
                           </td>
                        </tr>
                        {else}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-left">{$i['CCENRES']} - {$i['CDESCRI']}</td>
                           <td class="text-right">{$i['NTOTAL']}</td>
                           <td class="text-right">{$i['NINVENT']}</td>
                           <td class="text-right">{$i['NFALINV']}</td>
                           <td class="text-center">  </td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_BuscarEmpleadoInventario('{$i['CCENRES']}','{$i['CDESCRI']}');">
                              <img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/>
                           </td>
                        </tr>
                  {/if} 
                  {$k = $k+1}             
                  {/foreach}
                  </tbody>
               </table>
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
               <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCENRES']} - {$saData['CDESCRI']}">
               &nbsp;
               <span class="input-group-btn" style="display:;" id="" >
                  <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger" style="height: 42px !important;" formnovalidate>
                     <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR
                  </button> 
               </span>
            </div>
            <div style="height:800px; overflow-y: scroll;">
            <div style="" id="div_LisConPat">
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark">
                     <tr style="text-align: left;">
                        <th scope="col">#</th>
                        <th scope="col" style="text-align: center;">Empleado Responsable</th>
                        <th scope="col" style="text-align: center;">Total de Act.Fij.</th>
                        <th scope="col" style="text-align: center;">Act.Fij. Inventariados</th>
                        <th scope="col" style="text-align: center;">Act.Fij. Faltantes</th>
                        <th class="text-center"><img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/></th>
                     </tr>
                  </thead>
                  <tbody class="BodTab" id="tbl_ConPat">
                     {$k=1}
                     {foreach from=$saDatas item=i}
                        {if $i['NTOTAL'] == $i['NINVENT']}
                        <tr class="text-center table-info" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-left">{$i['CCODEMP']} - {$i['CNOMBRE']}</td>
                           <td class="text-right">{$i['NTOTAL']}</td>
                           <td class="text-right">{$i['NINVENT']}</td>
                           <td class="text-right">{$i['NFALINV']}</td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_BuscarActFijInventario('{$i['CCENRES']}','{$i['CDESCRI']}','{$i['CCODEMP']}');">
                              <img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/>
                           </td>
                        </tr>            
                        {else if $i['NTOTAL'] > $i['NINVENT'] AND $i['NINVENT'] > 0 }
                        <tr class="text-center table-warning" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-left">{$i['CCODEMP']} - {$i['CNOMBRE']}</td>
                           <td class="text-right">{$i['NTOTAL']}</td>
                           <td class="text-right">{$i['NINVENT']}</td>
                           <td class="text-right">{$i['NFALINV']}</td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_BuscarActFijInventario('{$i['CCENRES']}','{$i['CDESCRI']}','{$i['CCODEMP']}');">
                              <img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/>
                           </td>
                        </tr>
                        {else}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-left">{$i['CCODEMP']} - {$i['CNOMBRE']}</td>
                           <td class="text-right">{$i['NTOTAL']}</td>
                           <td class="text-right">{$i['NINVENT']}</td>
                           <td class="text-right">{$i['NFALINV']}</td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_BuscarActFijInventario('{$i['CCENRES']}','{$i['CDESCRI']}', '{$i['CCODEMP']}');">
                              <img src="img/aprobado.png" width="24" height="24" class="align-middle text-center"/>
                           </td>
                        </tr>
                  {/if} 
                  {$k = $k+1}             
                  {/foreach}
                  </tbody>
               </table>
            </div>
            </div>
         </div>
         </div>
         </div>
      {else if $snBehavior eq 2}
         <div class="card-body">
         <div class="row d-flex justify-content-center">
         <div class="col-sm-12">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Empleado Responsable</span>
               </div>
               <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCODEMP']} - {$saData['CNOMBRE']}">
            </div>
            <div class="card text-center ">
            <div style="height:730px; overflow-y: scroll;">
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
                           <th>
                              <input type="checkbox" onclick="marcar(this);" />
                           </th>
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
                              <td class="text-left">{$i['MDATOS']['CMARCA']}</td>
                              <td class="text-left">{$i['MDATOS']['CMODELO']}</td>
                              <td class="text-left">{$i['MDATOS']['CCOLOR']}</td>
                              <td class="text-left">{$i['MDATOS']['CNROSER']}</td>
                              <td class="text-left">
                                 <input type="checkbox" title="Seleccionar Activos" name="ACodAct[]" value="{$i['CACTFIJ']}" {if $i['CINVENT'] eq 'S'} checked{/if}>
                                 <!--{if $i['CINVENT'] eq 'S'} checked disabled{/if}-->
                              </td>
                           </tr>   
                        {$k = $k + 1}
                        {/foreach}
                     </tbody>
                  </table>
               </div>
            </div>
            <div class="card-footer text-center">
               <button type="submit" name="Boton1" value="GrabarInventario" class="btn btn-success col-md-2" formnovalidate>&nbsp;&nbsp;GRABAR INVENTARIO</button>
               <button type="button" class="btn btn-info col-md-2" data-toggle="modal" tabindex="-1" data-target="#m_FinalizarInventario" formnovalidate>&nbsp;&nbsp;FINALIZAR INVENTARIO</button>
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
<!-- Modal de obsevaciones-->
<div class="modal fade" id="m_FinalizarInventario" tabindex="-1">
<div class="modal-dialog mw-80" role="document">
   <form action="Afj1140.php" method="POST">
   <input type="hidden" name="paData[CCENRES]" value="{$saData['CCENRES']}">
      <div class="modal-content">
         <div class="modal-header" style="background-color: #fbc804; color: black; font-weight: 500; ">
            <h5 class="modal-title col-md-8">REGISTRAR DATOS</h5>
            <span class="badge badge-secondary p-2 col-md-8 float-right" style="border:none" id="s_cIdM1"></span>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
            <div class="form-group">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Empleado Responsable</span>
                  </div>
                  <input type="text" id="pcCriBus" placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-2" style="text-transform: uppercase;" autofocus>
                  <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado();">
                     <a class="justify-content-center" title="Buscar" >
                        <img src="img/lupa1.png" width="20" height="20∫">
                     </a>
                  </button> 
                  <select id="pcCodEmp" name="paData[CCODEMP1]"  class="form-control form-control-sm col-sm-8" style="height: 44px">
                  </select>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Inventariador</span>
                  </div>
                  <input type="text" id="pcCriBus1"  placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-2" style="text-transform: uppercase;">
                  <button type="button" id="b_buscar1" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado1();">
                     <a class="justify-content-center" title="Buscar" >
                        <img src="img/lupa1.png" width="20" height="20∫">
                     </a>
                  </button> 
                  <select id="pcCodEmp1" name="paData[CCODEMP2]"  class="form-control form-control-sm col-sm-8" style="height: 44px">
                  </select>
               </div>
            </div>
         </div>
         <div  class="modal-footer">
            <button type="submit" name="BotonM" value='Enviar' class="btn btn-warning col-md-2">ENVIAR</button>
            <button type="submit" class="btn btn-danger col-md-2" data-dismiss="modal">CERRAR</button>
         </div>
      </div>
   </form>
</div>
</div>

</body>
</html>
