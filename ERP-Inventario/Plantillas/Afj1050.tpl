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
      window.onload = function(){
         var fecha = new Date(); //Fecha actual
         var mes = fecha.getMonth()+1; //obteniendo mes
         var dia = fecha.getDate(); //obteniendo dia
         var ano = fecha.getFullYear(); //obteniendo año
         if(dia<10)
            dia='0'+dia; //agrega cero si el menor de 10
         if(mes<10)
            mes='0'+mes //agrega cero si el menor de 10
         document.getElementById('fechaActual').value=ano+"-"+mes+"-"+dia;
         var mesF = '01'; //obteniendo mes
         var diaF = '01'; //obteniendo dia
         var anoF = '1900'; //obteniendo año
         document.getElementById('fechaInicial').value=anoF+"-"+mesF+"-"+diaF;
      }

      function f_mBuscarCResp() {
         document.getElementById("pcCenRes").options.length = 0;
         var lcSend = "Id=CargarCentroResp&pcCenCos=" + $('#pcCenCos').val();
         console.log(lcSend);
         $.post("Afj1050.php",lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR: CENTRO DE COSTO NO TIENE CENTROS DE RESPONSABILIDAD');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            poCenRes = document.getElementById("pcCenRes");
         for (var i = 0; i < laDatos.length; i++) {
             var loOption = document.createElement("option");
             loOption.setAttribute("value", laDatos[i].CCENRES);
             loOption.setAttribute("label", laDatos[i].CCENRES+ ` - ` +laDatos[i].CDESRES);
            //  $('#pcDesRes').val(laDatos[i].CDESRES);
             poCenRes.appendChild(loOption);
            }
         });
      }

      function f_BuscarEmpleado() {
         $('#pcNomEmp').val('');
         var lcSend = "Id=BuscarEmpleado&p_cCodEmp=" + $('#pcCodEmp').val();
         // alert(lcSend);
         $.post("Afj1050.php",lcSend).done(function(p_cResult) {
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
            $('#pcNomEmp').val(laData.CNOMBRE);
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
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1050.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>IMPRESIÓN DE ETIQUETAS</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      <!-- <br> -->
      {if $snBehavior eq 0}
      <br><br>
      <div class="row d-flex ">
      <div class="col-sm-6">
      <div class="card text-center ">
      <div class="card-body" >
         <br><br><br>
         <div style="padding-left: 4rem;">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro de Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-6" data-live-search="true" id="pcCenCos" onchange="f_mBuscarCResp();" name="paData[CCENCOS]" data-live-search="true" >
                  {foreach from=$saCenCos item=i}                        
                     <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                  {/foreach}                                          
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
               </div>
               <select class="form-control form-control-sm col-sm-12 black-input col-lg-6" id= "pcCenRes" name="paData[CCENRES]">
               <!-- <input type="hidden" name="paData[CDESRES]" id ="pcDesRes"> -->
                  {foreach from=$saDatas item=i}                        
                     <option  value="{$i['CCENRES']}">{$i['CCENRES']} - {$i['CDESCRI']}</option>
                  {/foreach}                                          
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Empleado</span>
               </div>
               <input type="text" maxlength="4" id="pcCodEmp"  name="paData[CCODEMP]" class="form-control text-uppercase col-lg-1 black-input" onchange="f_BuscarEmpleado();">
               <input type="text" id="pcNomEmp" name="paData[CNOMEMP]" class="form-control text-uppercase black-input col-lg-5" value=""  readonly>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Fecha Inicial</span>
               </div>
               <input type="date" class="form-control text-uppercase black-input col-lg-6" name="paData[DFECINI]" id="fechaInicial" value="">
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Fecha Final</span>
               </div>
               <input type="date" class="form-control text-uppercase black-input col-lg-6" name="paData[DFECFIN]" id="fechaActual" value="">
            </div>
         </div>
         <br><br><br><br><br>
         <div class="card-footer text-center">
            <button type="submit" name="Boton" value="Buscar" class="btn btn-primary col-md-3" style="height: 42px !important;"> 
               <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
            <!-- <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button> -->
         </div>
      </div>
      </div>
      </div>
      <div class="col-sm-6">
         <div class="card text-center ">
         <div class="card-body" >
            <br><br>
            <div style="padding-left: 7rem;">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Codigo 01</span>
                  </div>
                  <input type="text" name="paData[CCODI01]" class="form-control text-uppercase col-lg-6 black-input">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Codigo 02</span>
                  </div>
                  <input type="text" name="paData[CCODI02]" class="form-control text-uppercase col-lg-6 black-input">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Codigo 03</span>
                  </div>
                  <input type="text" name="paData[CCODI03]" class="form-control text-uppercase col-lg-6 black-input">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Codigo 04</span>
                  </div>
                  <input type="text" name="paData[CCODI04]" class="form-control text-uppercase col-lg-6 black-input">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Codigo 05</span>
                  </div>
                  <input type="text" name="paData[CCODI05]" class="form-control text-uppercase col-lg-6 black-input">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Codigo 06</span>
                  </div>
                  <input type="text" name="paData[CCODI06]" class="form-control text-uppercase col-lg-6 black-input">
               </div>
            </div>
            <br><br><br><br>
            <div class="card-footer text-center">
               <button type="submit" name="BotonC2" value="Buscar" class="btn btn-primary col-md-3" style="height: 42px !important;"> 
                  <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
            </div>
         </div>
         </div>
         </div>
      </div>
      <br>
      <div class="card-footer text-center">
         <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
            <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
      </div>
   </div>
   <br><br><br><br><br>
      {else if $snBehavior eq 1}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-12">
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
            </div>
            <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCENRES']} - {$saData['CDESRES']}">
            {if $saData['CCODEMP'] != null}
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Empleado</span>
               </div>
               <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCODEMP']} - {$saData['CNOMEMP']}">
            {/if}
         </div>
         <div class="card text-center ">
         <div style="height:740px; overflow-y: scroll;">
            <div>
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr class="text-center">
                        <th>#</th>
                        <th>Codigo</th>
                        <th>Descripción</th>
                        <th>Empleado</th>
                     </tr>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-left" style="width: 60rem;">{$i['CDESCRI']}</td>
                           <td class="text-left" >{$i['CCODEMP']} - {$i['CNOMEMP']}</td>
                        </tr>   
                     {$k = $k + 1}
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton1" value="PDF" class="btn btn-primary col-md-2" formnovalidate>
               <i class="fas fa-print"></i>&nbsp;&nbsp;IMPRIMIR</button>
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 2}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-12">
         <!-- <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
            </div>
            <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCENRES']} - {$saData['CDESRES']}">
            {if $saData['CCODEMP'] != null}
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Empleado</span>
               </div>
               <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCODEMP']} - {$saData['CNOMEMP']}">
            {/if}
         </div> -->
         <div class="card text-center ">
         <div style="height:470px; overflow-y: scroll;">
            <div>
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark">
                     <tr class="text-center">
                        <th>#</th>
                        <th>Codigo</th>
                        <th>Descripción</th>
                        <th>Empleado</th>
                        <th>Centro Resp.</th>
                     </tr>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-left" style="width: 60rem;">{$i['CDESCRI']}</td>
                           <td class="text-left" >{$i['CCODEMP']} - {$i['CNOMEMP']}</td>
                           <td class="text-left" >{$i['CCENRES']} - {$i['CDESRES']}</td>
                        </tr>   
                     {$k = $k + 1}
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton2" value="PDF" class="btn btn-primary col-md-2" formnovalidate>
               <i class="fas fa-print"></i>&nbsp;&nbsp;IMPRIMIR</button>
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
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