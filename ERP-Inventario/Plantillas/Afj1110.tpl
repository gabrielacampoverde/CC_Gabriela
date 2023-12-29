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
         // f_CentroCosto();
         // f_CargarCentroResp();
         // f_agregarDetalle();
         f_Fecha();
         
      }

      function f_DetalleCenCos() {
         $('#p_nIndice').val('-1');
      }

      function f_agregarDetalle() {
         // alert($('#pcCenCos').val());
         console.log(document.getElementById("pcCenRes").options.length = 0);
         document.getElementById("pcCosMod");
         var lcCenCos = $('#pcCosMod').val();
         var lcSend = "Id=CentrosResponsabilidad&pcCenCos=" + lcCenCos;
         // alert(lcSend);
         $.post("Afj1110.php",lcSend).done(function(p_cResult) {
            // alert(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404');
               return;
            }
            var laData = JSON.parse(p_cResult);
            // alert(laData['CCENCOS']);   
            if (laData.ERROR) {
               Swal.fire(laData.ERROR);
               return
            }
            $('#pcCenCos').val(laData['CCENCOS']);
            $('#pcDesCen').val(laData['CDESCEN']);
            var laDatos = laData['ACENRES'];
            var lcCenRes = $('#pcCenRes').val();
            loCenRes = document.getElementById("pcCenRes");
            var loOption = document.createElement("option");
            // alert(laDatos[0].CCENRES);
            for (var i = 0; i < laDatos.length; i++) {
                // console.log(laDatos['LADATOS']);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos[i].CCENRES);
                loOption.innerText = laDatos[i].CCENRES+ ` - ` +laDatos[i].CDESCRI
                loCenRes.appendChild(loOption); 
                if (laDatos['CCENRES'] == lcCenRes) {
                   // console.log(laDatos['LADATOS'][i].CCENRES);
                   loOption.setAttribute("selected", laDatos[i].CCENRES);
               }
            } 
            $("#pcCenRes").selectpicker('refresh')
         });
         $('#nuevoDetalle').modal('hide');   
      }

      function f_Fecha(){
         var fecha = new Date();
         var mes = fecha.getMonth()+1;
         var dia = fecha.getDate(); 
         var ano = fecha.getFullYear();
         if(dia<10)
            dia='0'+dia;
         if(mes<10)
            mes='0'+mes
         document.getElementById('fechaActual').value=ano+"-"+mes+"-"+dia;
      }

      function f_mBuscarCRespNuevo() {
         document.getElementById("pcCenRes1").options.length = 0;
         var lcSend = "Id=CargarCentroResp&pcCenCos=" + $('#pcCenCos1').val();
         // alert(lcSend);
         $.post("Afj1110.php",lcSend).done(function(p_cResult) {
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
            
            var laDatos = laDatos['ACENRES'];
            loCenRes = document.getElementById("pcCenRes1");
            // alert(laDatos[0].CCENRES);
            for (var i = 0; i < laDatos.length; i++) {
               $('#pcCenRes1').append('<option value="'+laDatos[i].CCENRES+'">'+laDatos[i].CCENRES+' - ' +laDatos[i].CDESCRI+'</option>');
            }
            $('#pcCenRes1').selectpicker('refresh');
         });
         
      }

      function f_BuscarClase() {
         document.getElementById("pcTipAfj").options.length = 0;
         var lcSend = "Id=ListarTiposAF&pcClase=" + $('#pcClase').val();
         // alert(lcSend);
         $.post("Afj1110.php",lcSend).done(function(p_cResult) {
            // alert(p_cResult);
            if (!isJson(p_cResult.trim())) {
               Swal.fire('ERROR 404 -  CLASE');
               return;
            }
            var laDatos = JSON.parse(p_cResult);
            if (laDatos.ERROR) {
               Swal.fire(laDatos.ERROR);
               return
            }
            // console.log(laDatos);
            loTipAfj = document.getElementById("pcTipAfj");
            for (var i = 0; i < laDatos['LADATAS'].length; i++) {
               //  alert(laDatos['LADATAS']);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos['LADATAS'][i].CTIPAFJ);
                loOption.setAttribute("label", laDatos['LADATAS'][i].CTIPAFJ + ` - ` + laDatos['LADATAS'][i].CDESCRI);
                loTipAfj.appendChild(loOption);
               //  if(laDatos['LADATOS'][i]['CTIPAFJ'] == laDatos['LADATA']['CTIPAFJ']){
               //    loOption.setAttribute("selected", laDatos['LADATOS'][i].CTIPAFJ + ` - ` + laDatos['LADATOS'][i].CDESCRI);
               //  }
            }
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
         $.post("Afj1110.php", lcSend).done(function(lcResult) {
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

      function f_EliminarActFij() {
         event.preventDefault();
         Swal.fire({
            title: '¿Seguro desea eliminar activo fijo?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'CONFIRMAR',
            cancelButtonText: 'CANCELAR'
         }).then((result) => {
            //console.log(result);
            if (result.value) {
               $('#Id').val('Eliminar');
               $("#cActFij").val();
               $('#poForm').submit();
            }
         })
      }
   </script>
   <style>
   .new-ucsm{
      background:#099957;
      color: white;
   }   
   input{
      color: black;
   }
   </style>
</head>
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div>
<form action="Afj1110.php" method="post" enctype="multipart/form-data" id="poForm" >
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>EDITAR O CREAR NUEVO ACTIVO FIJO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
   {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-8">
      <div class="card text-center ">
         <div style="padding-left: 10%;">
            <div class="card-body"><br>
               <div class="input-group-prepend">
                  <span class="input-group-text new-ucsm">Activo Fijo</span>
                  <input type="text" name="paData[CCODDES]" class="form-control text-uppercase col-lg-6 black-input" placeholder="Código" autofocus>
               </div><br>
               <br>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton" value="Buscar" class="btn btn-primary col-md-2" formnovalidate>
               <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR</button>
            <button type="submit" name="Boton" value="Nuevo" class="btn btn-success col-md-2" formnovalidate>
               <i class="fa fa-file"></i>&nbsp;&nbsp;NUEVO</button>
            <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
         </div>
      </div>
      </div>
      </div>
      </div>
   {else if $snBehavior eq 1}
      <div class="card">
         <div class="p-3 card-body" >
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>REGISTRO ACTIVO FIJO</strong></div>
            <input type="hidden" name="paData[CSOBRAN]" value="">
            <input type="hidden" name="paData[MFOTOGR]" value="">
            <input type="hidden" name="paData[NSERFAC]" value="{$saData['NSERFAC']}">
            <input type="hidden" name="paData[CACTFIJ]" value="{$saData['CACTFIJ']}">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Activo Fijo</span>
               </div>
               <input type="hidden" name="paData[CCODIGO]" value="{$saData['CCODIGO']}" id="cActFij">
               <!-- <input type="hidden" name="paData[CDESCRI]" value="{$saData['CDESCRI']}"> -->
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CCODIGO']} ({$saData['CACTFIJ']})" disabled>
               <input type="text" class="form-control text-uppercase black-input col-lg-8" value="{$saData['CDESCRI']}" name="paData[CDESCRI]">
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Tipo</span>
               </div>
               <input type="hidden"  name="paData[CTIPAFJ]" value="{$saData['CTIPAFJ']}">
               <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CTIPAFJ]" value="{$saData['CTIPAFJ']} - {$saData['CDESTIP']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Indicador AF</span>
               </div>
               &nbsp;&nbsp;
               <div class="form-check form-check-inline col-lg-2">
                  <input class="form-check-input" type="checkbox" value="S" name="paData[CINDACT]" checked>      
               </div>
               <span class="input-group-btn">
                  <button class="btn btn-info" type="button" style="height: 42px !important;" data-toggle="modal" tabindex="-1" data-target="#nuevoDetalle1" > 
                     <i class="fas fa-search"></i>&nbsp;&nbsp;Depreciación
                  </button>
               </span>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Estado</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CESTADO]">
                  {foreach from=$saEstAct item=i}
                     <option value="{$i['CESTADO']}" {if $i['CESTADO'] eq $saData['CESTADO']} selected {/if}>{$i['CDESCRI']}</option>
                  {/foreach}
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Situación</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CSITUAC]" value="{$saData['CSITUAC']}">
                  {foreach from=$saSituac item=i}
                     <option value="{$i['CSITUAC']}" {if $i['CSITUAC'] eq $saData['CSITUAC']} selected {/if}>{$i['CDESCRI']}</option>
                  {/foreach}
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Centro de Costo</span>
               </div>
               <input type="hidden"  value="{$saData['CCENCOS']}" name="paData[CCENCOS]">
               <input type="text" id="pcDesCen" value="{$saData['CCENCOS']} - {$saData['CDESCEN']}" class="form-control form-control-sm col-sm-3" style="text-transform: uppercase;" disabled>&nbsp;&nbsp;&nbsp;
               <button type="button" id="add_button" class="btn btn-outline-primary" style="height: 44px; width: 90px"  data-toggle="modal" tabindex="-1" data-target="#nuevoDetalle" onclick="f_DetalleCenCos();">
                  <a class="justify-content-center" title="Buscar" >
                     <img src="img/lupa1.png" width="35" height="35">
                  </a>
               </button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Centro Responsabilidad</span>
               </div>
               <select class="selectpicker form-control form-control col-sm-12 black-input col-lg-4" data-live-search="true" id="pcCenRes" name="paData[CCENRES]">
                  {foreach from=$saCenRes item=i}
                     <option value="{$i['CCENRES']}" {if $i['CCENRES'] eq $saData['CCENRES']} selected {/if}>{$i['CCENRES']} - {$i['CDESCRI']}</option>
                  {/foreach}
               </select> 
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Código Empleado</span>
               </div>
               <input type="text" id="pcCriBus" value="{$saData['CCODEMP']}" placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-3" style="text-transform: uppercase;">
               &nbsp;&nbsp;&nbsp;
               <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 90px" onclick="f_buscarEmpleado();">
                  <a class="justify-content-center" title="Buscar" >
                     <img src="img/lupa1.png" width="35" height="35">
                  </a>
               </button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
               <select id="pcCodEmp" name="paData[CCODEMP]"  class="form-control form-control-sm col-sm-10" style="height: 44px">
                  <option value="{$saData['CCODEMP']}" selected>{$saData['CCODEMP']} - {$saData['CNOMEMP']}</option>
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Código Artículo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4"  name="paData[CCODART]" value="{$saData['MDATOS']['CCODART']}" >
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Cantidad</span>
               </div>
               <input type="hidden" name="paData[CCANTID]" value="{$saData['MDATOS']['CCANTID']}" >
               <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CCANTID]" value="{$saData['MDATOS']['CCANTID']}" >
            </div>           
            <!-- <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>ADQUISICIÓN</strong></div>    -->
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Fecha Alta</span>
               </div>
               <input type="hidden" name="paData[DFECALT]" value="{$saData['DFECALT']}">
               <input type="date" class="form-control text-uppercase black-input col-lg-2" value="{$saData['DFECALT']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Documento de Adquisición</span>
               </div>
               <input type="hidden" name="paData[CDOCADQ]" value="{$saData['MDATOS']['CDOCADQ']}" >
               <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CDOCADQ]" value="{$saData['MDATOS']['CDOCADQ']}">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Comprobante</span>
               </div>
               <input type="hidden" name="paData[CCODREF]" value="{$saData['MDATOS']['CCODREF']}" >
               <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCODREF]" value="{$saData['MDATOS']['CCODREF']}" >
            </div>
            <div class="input-group mb-1"> 
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Nro.RUC</span>
               </div>
               <input type="hidden" name="paData[CNRORUC]" value="{$saData['CNRORUC']}">
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CNRORUC']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Razón Social</span>
               </div>
               <input type="hidden" name="paData[CRAZSOC]" value="{$saData['CRAZSOC']}">
               <input type="text"class="form-control text-uppercase black-input col-lg-6"  value="{$saData['CRAZSOC']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda</span>
               </div>
               <input type="hidden" name="paData[CMONEDA]" value="SOLES">
               <input type="text" class="form-control text-uppercase black-input col-lg-6" value="SOLES" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda Nacional</span>
               </div>
               <input type="hidden" name="paData[NMONTMN]" value="{$saData['NMONTMN']}">
               <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" value="{$saData['NMONTMN']|number_format:2:'.':','}" name="paData[NMONTMN]">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda Extranjera</span>
               </div>
               <input type="hidden" name="paData[NMONTME]" value="{$saData['NMONTME']}">
               <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" value="{$saData['NMONTME']|number_format:2:'.':','}">
            </div>
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>OTROS DATOS</strong></div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Marca</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMARCA]" value="{$saData['MDATOS']['CMARCA']}">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Modelo</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMODELO]" value="{$saData['MDATOS']['CMODELO']}">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Placa</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CPLACA]" value="{$saData['MDATOS']['CPLACA']}">
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Nro. Serie</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CNROSER]" value="{$saData['MDATOS']['CNROSER']}">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Color</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CCOLOR]" value="{$saData['MDATOS']['CCOLOR']}">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Motor</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CMOTOR]" value="{$saData['MDATOS']['CMOTOR']}">      
            </div>
         </div>
         <br>
         <div class="card-footer text-center">
            <button type="submit" name="Boton1" value="Grabar" style="font-weight: 500;" class="btn btn-primary col-sm-2" formnovalidate>
               <i class="fas fa-save"></i>&nbsp;&nbsp;GRABAR</button> 
            <button type="submit" name="Boton1" value="Regresar" style="font-weight: 500;" class="btn btn-danger col-sm-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
         <div class="card-footer text-lg-right">
            <button onclick="f_EliminarActFij()" class="btn btn-warning col-sm-2" formnovalidate>
               <img src="img/delete.png" width="30" height="30">&nbsp;&nbsp;ELIMINAR</button> 
         </div>
      </div>
   {else if $snBehavior eq 2}
      <div class="card">
         <div class="p-3 card-body" >
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>REGISTRO ACTIVO FIJO</strong></div>
            <input type="hidden" name="paData[CSOBRAN]" value="">
            <input type="hidden" name="paData[MFOTOGR]" value="">
            <input type="hidden" name="paData[CACTFIJ]" value="*">
            <input type="hidden" name="paData[DFECBAJ]" value="1900-01-01">
            <input type="hidden" name="paData[NSERFAC]" value="{$saDato['NSERFAC']}">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Artículo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-6" name="paData[CDESCRI]" autofocus>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Indicador AF</span>
               </div>
               &nbsp;&nbsp;
               <div class="form-check form-check-inline col-lg-2">
                  <input class="form-check-input" type="checkbox" value="S" name="paData[CINDACT]" checked>      
               </div>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Clase</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcClase" onchange="f_BuscarClase();">
                  {foreach from=$saDatClaAfj item=i}                        
                     <option  value="{$i['CCODCLA']}">{$i['CCODCLA']} - {$i['CDESCLA']}</option>
                  {/foreach}                                          
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Tipo</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-5" id="pcTipAfj" name="paData[CTIPAFJ]">
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Cantidad</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CCANTID]" maxlength="5">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Situación</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CSITUAC]" >
                  {foreach from=$saSituac item=i}
                     <option value="{$i['CSITUAC']}">{$i['CDESCRI']}</option>
                  {/foreach}
               </select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Centro Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos1" name="paData[CCENCOS]" onchange="f_mBuscarCRespNuevo();">
                  {foreach from=$saCenCos item=i}                        
                     <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                  {/foreach}
               </select>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Centro Responsabilidad</span>
               </div>
               <select class="form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenRes1" name="paData[CCENRES]">         
               </select>
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Código Empleado</span>
               </div>
               <input type="text" id="pcCriBus" placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-4" style="text-transform: uppercase;">
               <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 90px" onclick="f_buscarEmpleado();">
                  <a class="justify-content-center" title="Buscar" >
                     <img src="img/lupa1.png" width="35" height="35">
                  </a>
               </button>
               <select id="pcCodEmp" name="paData[CCODEMP]" class="form-control form-control-sm col-sm-10" style="height: 44px"></select>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Estado</span>
               </div>
               <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CESTADO]">
                  {foreach from=$saEstAct item=i}
                     <option value="{$i['CESTADO']}">{$i['CDESCRI']}</option>
                  {/foreach}
               </select>               
            </div>           
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>ADQUISICIÓN</strong></div>   
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Fecha Alta</span>
               </div>
               <input type="date" class="form-control text-uppercase black-input col-lg-2" name="paData[DFECALT]" id="fechaActual" onchange="f_Fecha();" value="">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Documento de Adquisición</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CDOCADQ]" value="SOBRANTE">
            </div>
            <div class="input-group mb-1"> 
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Nro.RUC</span>
               </div>
               <!-- <input type="hidden" name="paData[CNRORUC]" value="{$saData['CNRORUC']}"> -->
               <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CNRORUC]" value="00000000000">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Razón Social</span>
               </div>
               <!-- <input type="hidden" name="paData[CRAZSOC]" value="{$saData['CRAZSOC']}"> -->
               <input type="text"class="form-control text-uppercase black-input col-lg-6"  name="paData[CRAZSOC]" value="-">
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda</span>
               </div>
               <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" name="paData[CMONEDA]" value="SOLES">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda Nacional</span>
               </div>
               <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" name="paData[NMONTMN]" value="0.00">
            </div>
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>OTROS DATOS</strong></div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Marca</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMARCA]">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Modelo</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMODELO]">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Placa</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CPLACA]">
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Nro. Serie</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CNROSER]">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Color</span>
               </div>
               <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CCOLOR]">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Motor</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CMOTOR]">
            </div>
         </div>
         <br>
         <div class="card-footer text-center">
            <button type="submit" name="Boton2" value="Grabar" style="font-weight: 500;" class="btn btn-primary col-sm-2" formnovalidate>GRABAR</button> 
            <button type="submit" name="Boton2" value="Regresar" style="font-weight: 500;" class="btn btn-danger col-sm-2" formnovalidate>REGRESAR</button> 
         </div>
      </div>
   {else if $snBehavior eq 3}
      <div class="card">
         <div class="p-3 card-body" >
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>REGISTRO ACTIVO FIJO</strong></div>
            <input type="hidden" name="paData[CSOBRAN]" value="">
            <input type="hidden" name="paData[MFOTOGR]" value="">
            <input type="hidden" name="paData[NSERFAC]" value="{$saData['NSERFAC']}">
            <input type="hidden" name="paData[CACTFIJ]" value="{$saData['CACTFIJ']}">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Activo Fijo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CCODIGO']}" disabled>
               <input type="text" class="form-control text-uppercase black-input col-lg-8" value="{$saData['CDESCRI']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Tipo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CTIPAFJ']} - {$saData['CDESTIP']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Código Artículo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['MDATOS']['CCODART']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Estado</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CSITUAC']} - {$saData['CDESSIT']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Situación</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CSITUAC']} - {$saData['CDESSIT']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Centro Costo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CCENCOS']} - {$saData['CDESCEN']}" disabled>              
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Centro Responsabilidad</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CCENRES']} - {$saData['CDESRES']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Empleado Responsable</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['CCODEMP']} - {$saData['CNOMEMP']}" disabled>
            </div>          
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Fecha Alta</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['DFECALT']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Documento de Adquisición</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CDOCADQ']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Comprobante</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CCODREF']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Fecha Baja</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['DFECBAJ']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Documento de Baja</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CDOCBAJ']}" disabled>
            </div>
            <div class="input-group mb-1"> 
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Nro.RUC</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CNRORUC']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm ">Razón Social</span>
               </div>
               <input type="text"class="form-control text-uppercase black-input col-lg-6"  value="{$saData['CRAZSOC']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-6" value="SOLES" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda Nacional</span>
               </div>
               <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" value="{$saData['NMONTMN']|number_format:2:'.':','}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Moneda Extranjera</span>
               </div>
               <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" value="{$saData['NMONTME']|number_format:2:'.':','}" disabled>
            </div>
            <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>OTROS DATOS</strong></div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Marca</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['MDATOS']['CMARCA']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Modelo</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4" value="{$saData['MDATOS']['CMODELO']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Placa</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-4"  value="{$saData['MDATOS']['CPLACA']}" disabled>
            </div>
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Nro. Serie</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CNROSER']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Color</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CCOLOR']}" disabled>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Motor</span>
               </div>
               <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['MDATOS']['CMOTOR']}" disabled>      
            </div>
         </div>
         <br>
         <div class="card-footer text-center">
            <button type="submit" name="Boton3" value="Regresar" style="font-weight: 500;" class="btn btn-danger col-sm-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
   {/if}
   </div>
<div id="footer"></div>
</form>
<div class="modal fade" id="nuevoDetalle" tabindex="-1">
   <div class="modal-dialog mw-60" role="document">
      <div class="modal-content">
         <div class="modal-header bg-sc-ucsm">
            <h5 class="modal-title">CENTROS DE COSTO</h5>
            <button type="button" class="close" data-dismiss="modal">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body">
            <div class="input-group mb-1 row" style="left: 15rem;">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Centro Costo</span>
               </div>
               <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" data-live-search="true" id="pcCosMod">
                  {foreach from=$saCenCos item=i}                        
                     <option value="{$i['CCENCOS']}">{$i['CCENCOS']} - {$i['CDESCRI']}</option>
                  {/foreach}
               </select>
            </div>
         </div>
         <div class="modal-footer">
            <button id="btn_agregarArt" type="button" class="btn bg-ucsm" onclick="f_agregarDetalle();">Agregar</button>
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#pcError').addClass('d-none');">Cerrar</button>
         </div>
      </div>
   </div>
</div>
<!-- MODAL PARA MOSTRAR DEPRECIACION -->
<div class="modal fade" id="nuevoDetalle1" tabindex="-1" id="detalle">
   <div class="modal-dialog mw-60" role="document">
      <div class="modal-content" >
         <div class="modal-header bg-sc-ucsm">
            <h5 class="modal-title">DEPRECIACIÓN DEL ACTIVO FIJO</h5>
            <button type="button" class="close" data-dismiss="modal">
               <span aria-hidden="true">&times;</span>
            </button>
         </div>
         <div class="modal-body" >
            {if $saDato eq null}
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valores</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valor de Aper./Adq.</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Actualizado</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Final</span>
               </div>
            </div>
            <br>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-12 px-0">
                  <span class="input-group-text w-100" style="background-color: lightblue">ACTIVIVO FIJO SIN DEPRECIACIÓN</span>
               </div>
            </div>
            {else}
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valores</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Valor de Aper./Adq.</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Actualizado</span>
               </div>
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Final</span>
               </div>
            </div>
            <div class="input-group mb-0" >
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Activo</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NMONCAL']|number_format:2:".":","}" >
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NMONCAL']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NMONCAL']|number_format:2:".":","}">
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Depreciación</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPREC']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPPER']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NSUMDEP']|number_format:2:".":","}">
            </div>
            <div class="input-group mb-0">
               <div class="input-group-prepend col-lg-3 px-0">
                  <span class="input-group-text w-100 bg-ucsm">Neto</span>
               </div>
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NVALNET']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NDEPACT']|number_format:2:".":","}">
               <input type="text" class="form-control form-control-sm col-sm-3" style="text-align: right; font-size: 18px; color: black;" value="{$saDato['NVALCAL']|number_format:2:".":","}">
            </div>
            {/if}
            <br>
            <div class="input-group">
               <div class="card text-center" >
                  <div style="height:380px; overflow-y: scroll;">
                     <div >
                        <table class="table table-hover table-sm table-bordered">
                           <thead class="thead-dark">
                              <tr class="text-center">
                                 <th style="width: 10rem;">Periodo</th>
                                 <th style="width: 10rem;">Factor</th>
                                 <th style="width: 10rem;">Depr. Act.</th>
                                 <th style="width: 10rem;">Valor Neto</th>
                              </tr>
                           </thead>
                           <tbody style="color: black;">
                              {foreach from=$saDepre item=i}
                                 <tr class="text-center" class="custom-select" multiple>
                                    <td class="text-left">{$i['DMOVIMI']}</td>
                                    <td class="text-right">{$i['NFACTOR']|number_format:2:".":","}%</td>
                                    <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                                    <td class="text-right">{$i['NDEPREC']|number_format:2:".":","}</td>
                                 </tr>
                              {/foreach}
                           </tbody>
                        </table>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#pcError').addClass('d-none');">Cerrar</button>
         </div>
      </div>
   </div>
</div>

</body>
</html>
