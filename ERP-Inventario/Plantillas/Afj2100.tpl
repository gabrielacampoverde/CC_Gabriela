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
      // "use strict";
      function Init() {
         f_BuscarTipo();
         f_BuscarTipoFin();
         f_mBuscarCResp();
         f_fecha();
      }
      
      function f_fecha(){
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
         // console.log(lcSend);
         $.post("Afj2100.php",lcSend).done(function(p_cResult) {
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
            var poCenRes = document.getElementById("pcCenRes");
            var loOption = document.createElement("option");
            loOption.setAttribute("value", `*`);
            loOption.setAttribute("label", `*`);
            poCenRes.appendChild(loOption);
            for (var i = 0; i < laDatos.length; i++) {
               // console.log(laDatos[i].CDESRES);
               var loOption = document.createElement("option");
               $('#pcDesRes').val(laDatos[i].CDESRES);
               loOption.setAttribute("value", laDatos[i].CCENRES);
               loOption.setAttribute("label", laDatos[i].CCENRES+ ` - ` +laDatos[i].CDESRES);
               poCenRes.appendChild(loOption);
            }
         });
      }

      function f_BuscarTipo() {
         document.getElementById("pcTipAfj").options.length = 0;
         var lcSend = "Id=ListarTipos&pcClase=" + $('#pcClase').val();
         // alert(lcSend);
         $.post("Afj2100.php",lcSend).done(function(p_cResult) {
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
            var loTipAfj = document.getElementById("pcTipAfj");
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
         $.post("Afj2100.php",lcSend).done(function(p_cResult) {
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
            var loTipAfj = document.getElementById("pcTipAfjFin");
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
<form action="Afj2100.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>REPORTE ANÁLISIS DE ACTIVOS FIJOS</strong></div>
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
                  <select class="form-control form-control-sm col-sm-12 black-input col-lg-8" id= "pcCenRes" name="paData[CCENRES]">
                     <!-- {foreach from=$saDatas item=i}                         
                        <option  value="{$i['CCENRES']}">*</option>
                     {/foreach}                                            -->
                  </select>
               </div>
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
                     <span class="input-group-text w-100 bg-ucsm">Fecha Inicial</span>
                  </div>
                  <input type="date" class="form-control text-uppercase black-input col-lg-8" name="paData[DFECHA]" id="fechaInicial" value="">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Fecha Final</span>
                  </div>
                  <input type="date" class="form-control text-uppercase black-input col-lg-8" name="paData[DFECFIN]" id="fechaActual" value="">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-3 px-0">
                     <span class="input-group-text w-100 bg-ucsm">Fecha Depreciación</span>
                  </div>
                  <input type="text" class="form-control text-uppercase black-input col-lg-8" name="paData[DFECINI]" placeholder="AAAA-MM">
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
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Centro de Responsabilidad</span>
            </div>
            <input type="text" class="form-control text-uppercase col-lg-5 black-input" value="{$saData['CCENRES']} - {$saData['CDESRES']}">
         </div>   
      <div class="card text-center ">
         <div style="height:690px; overflow-y: scroll;">
            <div >
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr class="text-center">
                        <th>#</th>
                        <th>Codigo</th>
                        <th>Cant.</th>
                        <th>Descripción</th>
                        <th>Sit.</th>
                        <th>Fecha</th>
                        <th>Factor</th>
                        <th>Val.Inicial</th>
                        <th>Adic/Adquis.</th>
                        <th>Valor Final</th>
                        <th>Dep.Inicial</th>
                        <th>Adiciones</th>
                        <th>Dep.Periodo</th>
                        <th>Dep.Retiro</th>
                        <th>Dep.Acumulada</th>
                        <th>Valor Neto</th>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CCODIGO']}</td>
                           <td class="text-left">{$i['CDATOS']['CCANTID']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-left">{$i['CSITUAC']}</td>
                           <td class="text-center">{$i['DFECALT']}</td>
                           <td class="text-right">{$i['NFACDEP']|number_format:4:".":","}</td>
                           <td class="text-right">{$i['NMONCAL']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NADICIO']|number_format:2:".":","}</td>
                           <td class="text-right">{($i['NMONCAL']+$i['NADICIO'])|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NDEPINI']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['N']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NDEPACU']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NRETIRO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NDEPTOT']|number_format:2:".":","}</td>
                           <td class="text-right">{($i['NMONCAL']-$i['NDEPTOT'])|number_format:2:".":","}</td>
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