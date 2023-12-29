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
         f_CargarCentroResp();
         f_BuscarClase();   
         f_CargarCentroRespDI();
         f_BuscarClaseDI();      
      }
      // Funcion para cargar el PDF de la Orden de Compra 
      function f_ReporteOrden(p_cNumCom, p_cNroRuc) {
         // alert(p_cNumCom);
         var lcYearMonth = document.getElementById("YearMonth").value;
         var lcRes = lcYearMonth.split("-");
         var lcYear = lcRes[0];
         var lcSend = "Id=reporteOrden&p_cNumCom=" + p_cNumCom+ "&p_cNroRuc=" + p_cNroRuc+ "&p_cYear=" + lcYear;
         // console.log(lcSend);
         $.post("Afj1020.php",lcSend).done(function(p_cResult) {
            console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=600, height=600');
         });
      }

      function f_ReporteActReg(p_cNumItem){
         p_cNumItem = p_cNumItem - 1;
         var lcSend = "Id=ReporteActReg&p_cNumItem= "+ p_cNumItem;
         //console.log(lcSend);
         $.post("Afj1020.php",lcSend).done(function(p_cResult) {
            //console.log(p_cResult);
            var laJson = JSON.parse(p_cResult.trim());
            if (laJson.pcError){
               f_Alerta('warning', laJson.pcError); //OJO FPM
               return;
            }
            window.open('./'+laJson.CREPORT, '', 'toolbar=yes, scrollbars=yes, resizable=yes, width=1500, height=800');
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
         $.post("Afj1020.php", lcSend).done(function(lcResult) {
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

      function f_NuevosActivos1(p_nIndice) {
         // alert(p_nIndice);
         $('#pnIndice').val(p_nIndice);
         $('#Id').val('NuevosActivos');
         $('#poForm').submit();
      }

      function f_NuevoActivoOL(p_nIndice) {
         $('#pnIndice').val(p_nIndice);
         $('#Id').val('NuevoActivoOL');
         $('#poForm').submit();
      }

      function f_AgregarActivo(p_nIndice){
         // alert(p_nIndice);
         $('#pnIndice').val(p_nIndice);
         $('#Id').val('AgregarActivo');
         $('#poForm').submit();
      }

      function f_CargarCentroResp() {
         document.getElementById("pcCenRes").options.length = 0;
         var lcSend = "Id=CargarCentrosRes&pcCenCos=" + $('#pcCenCos').val();
         // alert(lcSend);
         $.post("Afj1020.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
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
            for (var i = 0; i < laDatos['PADATOS'].length; i++) {
               $('#pcCenRes').append('<option value="'+laDatos['PADATOS'][i].CCENRES+'">'+laDatos['PADATOS'][i].CCENRES + ` - ` +laDatos['PADATOS'][i].CDESRES+'</option>');
               if (laDatos['PADATOS'][i]['CCENRES'] == laDatos['PADATA']['CCENRES']) {
                   loOption.setAttribute("selected", laDatos[i].CCENRES);
               }
            }
            $("#pcCenRes").selectpicker("refresh");
         });
      }

      function f_CargarCentroRespDI() {
         document.getElementById("pcCenRes1").options.length = 0;
         var lcSend = "Id=CargarCentrosRes&pcCenCos=" + $('#pcCenCos1').val();
         // alert(lcSend);
         $.post("Afj1020.php",lcSend).done(function(p_cResult) {
            // console.log(p_cResult);
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
            for (var i = 0; i < laDatos['PADATOS'].length; i++) {
                $('#pcCenRes1').append('<option value="'+laDatos['PADATOS'][i].CCENRES+'">'+laDatos['PADATOS'][i].CCENRES + ` - ` +laDatos['PADATOS'][i].CDESRES+'</option>');
            }
            $("#pcCenRes1").selectpicker("refresh");
         });
      }

      function f_BuscarClase() {
         document.getElementById("pcTipAfj").options.length = 0;
         var lcSend = "Id=ListarTiposAF&pcClase=" + $('#pcClase').val();
         // alert(lcSend);
         $.post("Afj1020.php",lcSend).done(function(p_cResult) {
            // sconsole.log(p_cResult);
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
            for (var i = 0; i < laDatos['PADATOS'].length; i++) {
                // console.log(laDatos[i]);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos['PADATOS'][i].CTIPAFJ);
                loOption.setAttribute("label", laDatos['PADATOS'][i].CTIPAFJ + ` - ` + laDatos['PADATOS'][i].CDESCRI);
                loTipAfj.appendChild(loOption);
                if (laDatos['PADATOS'][i]['CTIPAFJ'] == laDatos['PADATA']['CTIPAFJ']) {
                   // console.log(laDatos['LADATOS'][i].CCENRES);
                   loOption.setAttribute("selected", laDatos[i].CCENRES);
                }
            }
         });
      }
      function f_BuscarClaseDI() {
         document.getElementById("pcTipAfj").options.length = 0;
         var lcSend = "Id=ListarTiposAFDI&pcClase=" + $('#pcClase').val();
         // alert(lcSend);
         $.post("Afj1020.php",lcSend).done(function(p_cResult) {
            // sconsole.log(p_cResult);
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
            for (var i = 0; i < laDatos['PADATOS'].length; i++) {
                // console.log(laDatos[i]);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos['PADATOS'][i].CTIPAFJ);
                loOption.setAttribute("label", laDatos['PADATOS'][i].CTIPAFJ + ` - ` + laDatos['PADATOS'][i].CDESCRI);
                loTipAfj.appendChild(loOption);
            }
         });
      }
   </script>   
   <style>
      .mdl-1500{
         max-width:1500px !important;
      }

      .mdl-1000{
         max-width:1000px !important;
      }

      .mdl-900{
         max-width:900px !important;
      }
      
      .new-ucsm{
         background:#099957;
         color: white;
      }

      .black-input { 
        color: black;
      }

      .TitTab{ 
         font-weight: 500;
         font-size: large;
         background: #343434;
         color: white;
      }

      .BodTab{ 
         color: black; 
         font-size: large;
         text-align: left;
      }
      
      .black-input-mdl { 
         color: black;
         font-family: monospace;
         height: 35px !important; 
         font-size: large;
         border: solid #DBE1E1;
      }

      .-mdl { 
         padding: 3%;
         height: 35px !important; 
         font-size: large;
      }

      .icon-tbl { 
         cursor: pointer;
         color: #28A745;
         font-size: x-large;
      }

      /*thead tr th { */
      .head-fixed {
         position: sticky;
         top: 0;
         z-index: 1;
      }

      /***************************/
      .services-principal .icon-box {
         padding: 60px 30px;
         transition: all ease-in-out 0.3s;
         background: #fefefe;
         box-shadow: 0px 5px 90px 0px rgba(110, 123, 131, 0.1);
         border-radius: 18px;
         border-bottom: 5px solid #fff;
         border: solid #B6CEBE;
         cursor:pointer;
      }
  
      .services-principal .icon-box:hover {
         transform: translateY(-10px);
         border-color: #245433;
      }

      .line-separation{
         border: grey 1px solid !important;
      }
      
      /***************************/
      tr[title]:hover:after {
         content: attr(title);
         position: absolute;
         background-color: rgba(102, 95, 72, 0.75);
         color :white;
         font-size: 16px;
         font-weight: bold;
         font-style: italic;
         left: 22%;
         top: -10;
         transition-duration: 5s;
         padding-top: 7px;
         padding-right: 13px;
         padding-bottom: 7px;
         padding-left: 13px;
      }
   </style>
</head>
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div> 
<form action="Afj1020.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <!--<input type="hidden" name="p_nBehavior" id="p_nBehavior">-->
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>CONTROL PATRIMONIAL</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="p-2 card-body">
            <div class="input-group mb-1">
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm">Año - Mes</span>
               </div>
               <input type="text" id="YearMonth" maxlength="7" name="paData[CPERIOD]" value="{$saData['CPERIOD']}" class="form-control black-input col-lg-2" placeholder="AAAA-MM" autofocus>
               <div class="input-group-prepend col-lg-2 px-0">
                  <span class="input-group-text w-100 new-ucsm  ">Cuenta</span>
               </div>
               <!--<input type="text" maxlength="9" id="cNroCta" placeholder="00" class="form-control text-uppercase black-input col-lg-2" value="33" name="paData[CCTACNT]" readonly>-->
               <input type="text" placeholder="00" class="form-control text-uppercase black-input col-lg-2" value="33" name="paData[CCTACNT]">
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
                  <thead class="TitTab head-fixed">
                     <tr style="text-align: left;">
                        <th scope="col">#</th>
                        <th scope="col">Cuenta</th>
                        <th scope="col" title="Comprobante contable / OL">Id</th>
                        <th scope="col" style="text-align: center;">Fecha</th>
                        <th scope="col" style="text-align: center;">Glosa</th>
                        <!-- <th scope="col" style="text-align: center;">Act.Fij.</th> -->
                        <th scope="col" style="text-align: center;">Importe</th>
                        <th scope="col" style="text-align: center;">Mont Reg.</th>
                        <th scope="col" style="text-align: center;">Act.Fij.</th>
                        <th scope="col" >OC/OS</th>
                        <!-- <th scope="col" style="text-align: center;"><i class="fas fa-file-pdf"></i></th> -->
                        <th scope="col" style="text-align: center;"><i class="fab fa-product-hunt"></i></th>
                     </tr>
                  </thead>
                  <tbody class="BodTab" id="tbl_ConPat">
                     {$k=1}
                     {foreach from=$saDatos item=i}
                     {if $i['NMONREG'] == $i['NMONTO']}
                     <tr class="text-center table-info" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCTACNT']}</td>
                        {if {$i['CCODIOL']} eq ''}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left" style="width: 20rem;"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center"></td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevoActivoOL(this.value);" required>
                           </td>
                        {else}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_ReporteOrden('{$i['CNROCOM']}', '{$i['CNRORUC']}');">
                              <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                           </td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevosActivos1(this.value);" required>
                           </td>
                        {/if}
                     </tr>  
                  {else if $i['NMONTO'] > $i['NMONREG'] AND $i['NMONREG'] > 0 }
                     <tr class="text-center table-warning" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCTACNT']}</td>
                        {if {$i['CCODIOL']} eq ''}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left" style="width: 20rem;"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center"></td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevoActivoOL(this.value);" required>
                           </td>
                        {else}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_ReporteOrden('{$i['CNROCOM']}', '{$i['CNRORUC']}');">
                              <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                           </td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevosActivos1(this.value);" required>
                           </td>
                        {/if}
                     </tr>
                  {else if $i['NMONTO'] < $i['NMONREG']}
                     <tr class="text-center table-danger" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCTACNT']}</td>
                        {if {$i['CCODIOL']} eq ''}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left" style="width: 20rem;"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center"></td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevoActivoOL(this.value);" required>
                           </td>
                        {else}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_ReporteOrden('{$i['CNROCOM']}', '{$i['CNRORUC']}');">
                              <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                           </td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevosActivos1(this.value);" required>
                           </td>
                        {/if}
                     </tr>
                  {else}
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCTACNT']}</td>
                        {if {$i['CCODIOL']} eq ''}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left" style="width: 20rem;"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center"></td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevoActivoOL(this.value);" required>
                           </td>
                        {else}
                           <td class="text-left">{$i['CNROASI']} / {$i['CCODIOL']}</td>
                           <td class="text-left">{$i['DFECDOC']}</td>
                           <td class="text-left">{$i['CGLOSA']}</td>
                           <!-- <td class="text-left"> </td> -->
                           <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center" onclick="f_ReporteActReg('{$k}');">
                              <i class="fas fa-file icon-tbl"style="color:black;"></i> </td>
                           <td class="text-center" title="Orden Compra o Servicio" onclick="f_ReporteOrden('{$i['CNROCOM']}', '{$i['CNRORUC']}');">
                              <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                           </td>
                           <td>
                              <input type="image" src="img/eye.png" width="25" height="25" value="{$k-1}" onclick="f_NuevosActivos1(this.value);" required>
                           </td>
                        {/if}
                     </tr>
                  {/if} 
                  {$k = $k+1}             
                  {/foreach}
                  </tbody>
               </table>
            </div>
            </div>
         </div>
      {else if $snBehavior eq 1}
         <div style="height:800px; overflow-y: scroll;">
            <div >
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark">
                     <tr class="text-center">
                     <th scope="col">#</th> 
                     <th scope="col">Tipo</th>
                     <th scope="col">Artículo</th>
                     <th scope="col">Activo</th>
                     <th scope="col" >Cantidad</th>
                     <th scope="col" >Regist.</th>
                     <th scope="col">F.Adquis.</th>
                     <th scope="col">Monto</th>
                     <th scope="col">Unidad</th>
                     <th scope="col">Monto Regist.</th>
                     <th scope="col" width="30" style="word-break:break-all;"><i class="fas fa-check-circle"></i></th>
                     <th scope="col" style="text-align: center;"><i class="fas fa-file-pdf"></i></th>
                     <th scope="col" width="30" style="word-break:break-all;"><i class="fas fa-trash"></i></th>
                     </tr>
                  </thead>
                  <tbody>
                     {$k = 1}
                     {$lmMonto = 0}
                     {$lmMontoReg = 0}
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$k}</td>
                           <td class="text-center">{$i['CTIPAFJ']}</td>
                           <td class="text-center">{$i['CCODART']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-right">{$i['NCANTID']}</td>
                           <td class="text-right">{$i['NCANREG']}</td>
                           <td class="text-center">{$i['DFECADQ']}</td>
                           <td class="text-right">{$i['NMONTMN']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONUNI']|number_format:2:".":","}</td>
                           <td class="text-right">{$i['NMONREG']|number_format:2:".":","}</td>
                           <td class="text-center">
                              <form>
                                 <input type="hidden" name="pnIndice" value="{$k}" required/>
                                 <button type="submit" name="Boton1" value="AgregarActivo" >
                                    <i class="fas fa-check-circle icon-tbl"></i>
                                 </button>
                              </form>
                           </td>
                           <td class="text-center">
                              <form>
                                 <input type="hidden" name="nSerFac" value="{$i['NSERFAC']}"/>
                                 <button type="submit" name="Boton1" value="ReporteActFij" >
                                    <i class="fas fa-file-pdf icon-tbl" style="color:#C70039;"></i>
                                 </button>
                              </form>
                           </td>
                           <td class="text-center">
                              <form>
                                 <input type="hidden" name="nBorrar" value="{$k}"/>
                                 <button type="submit" name="Boton1" value="BorrarItems" >
                                    <i class="fa fa-trash"></i>
                                 </button>
                              </form>
                           </td>
                        </tr>
                     {$lmMonto = $lmMonto + $i['NMONTMN']}
                     {$lmMontoReg = $lmMontoReg + $i['NMONREG']}
                     {$k = $k + 1}
                     {/foreach}
                     <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="text-right">{$lmMonto|number_format:2:".":","}</td>
                        <td></td>
                        <td class="text-right">
                           <input type='hidden' name="laData[NMONRE]" value="{$lmMontoReg}"/>{$lmMontoReg|number_format:2:".":","}</td>
                        <td></td>
                        <td></td>
                        <td></td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-center">
               <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
            </div>
         <br>
      {else if $snBehavior eq 2}
         <div>
         <div class="card">
            <div class="p-3 card-body" >
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>REGISTRO ACTIVO FIJO</strong></div>
               {* CODIGO y codigo de referencia Y BOTONES *}
               <input type="hidden" name="paData[CSOBRAN]" value="">
               <input type="hidden" name="paData[MFOTOGR]" value="">
               <input type="hidden" name="paData[CACTFIJ]" value="*">
               <input type="hidden" name="paData[NSERFAC]" value="{$saData['NSERFAC']}">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm">Artículo</span>
                  </div>
                  <input type="hidden" name="paData[CCODART]" value="{$saData['CCODART']}">
                  <!-- <input type="hidden" name="paData[CDESCRI]" value="{$saData['CDESCRI']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-10" name="paData[CDESCRI]" value="{$saData['CDESCRI']}">
               </div>
               {* CLASE Y TIPO DE ACTIVO *}
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
                     <!--
                     {foreach from=$saDatas item=i}                        
                        <option  value="{$i['CCODCNT']}">{$i['CCODCNT']} - {$i['CDESCNT']}</option>
                     {/foreach}                                          
                     -->
                  </select>
               </div>
               {* CANTIDAD Y SITUACION *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Estado</span>
                  </div>
                  <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CESTADO]">
                     {foreach from=$saEstAct item=i}
                        <option value="{$i['CESTADO']}">{$i['CDESCRI']}</option>
                     {/foreach}
                  </select>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Situación</span>
                  </div>
                  <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CSITUAC]">
                     {foreach from=$saSituac item=i}
                        <option value="{$i['CSITUAC']}">{$i['CDESCRI']}</option>
                     {/foreach}
                  </select>
               </div>
               {* CENTRO DE COSTO, RESPONSABILIDAD Y CODIGO EMPLEADO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Centro Costo</span>
                  </div>
                  <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos" onchange="f_CargarCentroResp();" name="paData[CCENCOS]" data-live-search="true">
                     {foreach from=$saCenCos item=i}                        
                        <option value="{$i['CCENCOS']}">{$i['CDESCRI']}</option>
                     {/foreach}                                          
                  </select>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Centro Responsabilidad</span>
                  </div>
                  <select class="selectpicker form-control form-control-sm col-sm-12 black-input col-lg-8" id="pcCenRes" data-live-search="true" name="paData[CCENRES]">
                  </select>
               </div>
               {* CODIGO DE EMPLEADO Y ESTADO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm">Código Empleado</span>
                  </div>
                  <input type="text" id="pcCriBus" value="{$saData['CCODEMP']}" placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-2" style="text-transform: uppercase;">
                  <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado();">
                     <a class="justify-content-center" title="Buscar" >
                        <img src="img/lupa1.png" width="20" height="20∫">
                     </a>
                  </button> 
                  <select id="pcCodEmp" name="paData[CCODEMP]"  class="form-control form-control-sm col-sm-4" style="height: 44px">
                     <option value="{$saData['CCODEMP']}" selected>{$saData['CCODEMP']} - {$saData['CNOMEMP']}</option>
                  </select>
                  &nbsp;&nbsp;&nbsp;&nbsp;
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-70 new-ucsm ">Indicador AF</span>
                  </div>
                  <div class="form-check form-check-inline col-lg-2 px-0">
                     <input class="form-check-input" type="checkbox" value="S" name="paData[CINDACT]" checked>      
                  </div>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Cantidad</span>
                  </div>
                  <!-- <input type="hidden" name="paData[NCANTID]" value="{$saData['NCANTID']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[NCANTID]" value="{$saData['NCANTID']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Unidad</span>
                  </div>
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCANTID]">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Código Artículo</span>
                  </div>
                  <input type="text" name="paData[CCODART]" class="form-control text-uppercase col-lg-2 black-input" value="{$saData['CCODART']}" disabled>
               </div>        
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>ADQUISICIÓN</strong></div>   
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Fecha Alta</span>
                  </div>
                  <input type="hidden" name="paData[DFECALT]" value="{$saData['DFECADQ']}">
                  <input type="date" class="form-control text-uppercase black-input col-lg-2" value="{$saData['DFECADQ']}" disabled>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Documento Adquisición</span>
                  </div>
                  <input type="hidden" name="paData[CDOCADQ]" value="{$saDato['CNROCOM']}">
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CDOCADQ]" value="{$saDato['CNROCOM']}" disabled>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Comprobante</span>
                  </div>
                  <input type="hidden" name="paData[CCODREF]" value="{$saDato['CNROASI']}">
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCODREF]"  value="{$saDato['CNROASI']}" disabled >
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
                  <input type="text"class="form-control text-uppercase black-input col-lg-6"  value="{$saData['CRAZSOC']}" name="paData[CRAZSOC]">
               </div>
               {* MONEDA TODO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Moneda</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CMONEDA]" value="{$saData['CMONEDA']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-6" name="paData[CMONEDA]" value="{$saData['CDESMON']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Moneda Nacional</span>
                  </div>
                  <!-- <input type="hidden" name="paData[NMONTMN]" value="{$saData['NMONTMN']}"> -->
                  <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" name="paData[NMONTMN]" value="{$saData['NMONUNI']|number_format:2:'.':','}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Moneda Extranjera</span>
                  </div>
                  <!-- <input type="hidden" name="paData[NMONTME]" value="{$saData['NMONTME']}"> -->
                  <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" name="paData[NMONTME]" value="{$saData['NMONTME']|number_format:2:'.':','}">
               </div>
               {* OTROS DATOS *}
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>OTROS DATOS</strong></div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Marca</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMARCA]" value="{$saData['CMARCA']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Modelo</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMODELO]" value="{$saData['CMODELO']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Placa</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CPLACA]" value="{$saData['CPLACA']}">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Nro. Serie</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CNROSER]" value="{$saData['CNROSER']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Color</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CCOLOR]" value="{$saData['CCOLOR']}">
               </div>
            </div>
            <br>
            <div class="card-footer text-center">
               <button type="submit" name="Boton2" value="Grabar" style="font-weight: 500;" class="btn btn-primary col-sm-2" formnovalidate>GRABAR</button> 
               <button type="submit" name="Boton2" value="Regresar" style="font-weight: 500;" class="btn btn-danger col-sm-2" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
            </div>
         </div>
         </div>
         <br><br><br><br><br><br> 
      {else if $snBehavior eq 3}
         <div>
         <div class="card">
            <div class="p-3 card-body" >
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>REGISTRO ACTIVO FIJO</strong></div>
               {* CODIGO y codigo de referencia Y BOTONES *}
               <input type="hidden" name="paData[CSOBRAN]" value="">
               <input type="hidden" name="paData[MFOTOGR]" value="">
               <input type="hidden" name="paData[NSERFAC]" value="0">
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm">Artículo</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CCODART]" value="{$saData['CCODART']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-5" name="paData[CDESCRI]" value="{$saData['CDESCRI']}" autofocus>
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CACTFIJ]" value="{$saData['CACTFIJ']}" placeholder="SI ES NUEVO PONER *" >
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCODIGO]" value="{$saData['CCODIGO']}" style="border: 2px solid black;" >
                  <span class="input-group-btn">
                     <button class="btn btn-primary" type="submit" name="Boton3" value="Buscar" style="height: 42px !important;"> 
                        <i class="fas fa-search"></i>&nbsp;&nbsp;BUSCAR
                     </button>
                  </span>
                  
               </div>
               {* CLASE Y TIPO DE ACTIVO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm">Clase</span>
                  </div>
                  <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcClase" onchange="f_BuscarClaseDI();">
                     {foreach from=$saDatClaAfj item=i}                        
                        <option  value="{$i['CCODCLA']}" {if $i['CCODCLA'] eq $saData['CCODCLA']} selected {/if}>{$i['CCODCLA']} - {$i['CDESCLA']}</option>
                     {/foreach}                                          
                  </select>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Tipo</span>
                  </div>
                  <select class="form-control form-control col-sm-12 black-input col-lg-5" id="pcTipAfj" name="paData[CTIPAFJ]">
                     <!--
                     {foreach from=$saDatas item=i}                        
                        <option  value="{$i['CCODCNT']}">{$i['CCODCNT']} - {$i['CDESCNT']}</option>
                     {/foreach}                                          
                     -->
                  </select>
               </div>
               {* CANTIDAD Y SITUACION *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Estado</span>
                  </div>
                  <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CESTADO]" value="{$i['CESTADO']}">
                     {foreach from=$saEstAct item=i}
                        <option value="{$i['CESTADO']}" {if $i['CESTADO'] eq $saData['CESTADO']} selected {/if}>{$i['CDESCRI']}</option>
                     {/foreach}
                  </select>
                  
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Situación</span>
                  </div>
                  <select class="form-control form-control col-sm-12 black-input col-lg-4" name="paData[CSITUAC]" value="{$i['CSITUAC']}">
                     {foreach from=$saSituac item=i}
                        <option value="{$i['CSITUAC']}" {if $i['CSITUAC'] eq $saData['CSITUAC']} selected {/if}>{$i['CDESCRI']}</option>
                     {/foreach}
                  </select>
               </div>
               {* CENTRO DE COSTO, RESPONSABILIDAD Y CODIGO EMPLEADO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Centro Costo</span>
                  </div>
                  <select class="selectpicker form-control col-sm-12 black-input col-lg-5" data-live-search="true" id="pcCenCos1" onchange="f_CargarCentroRespDI();" name="paData[CCENCOS]">
                     {foreach from=$saCenCos item=i}                        
                        <option value="{$i['CCENCOS']}" {if $i['CCENCOS'] eq $saData['CCENCOS']} selected {/if}>{$i['CDESCRI']}</option>
                     {/foreach}                                          
                  </select>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Centro Responsabilidad</span>
                  </div>
                  <select class="form-control form-control col-sm-12 black-input col-lg-5" id= "pcCenRes1" name="paData[CCENRES]" data-live-search="true">
                     {foreach from=$saDatas item=i}                        
                        <option  value="{$i['CCENRES']}" {if $i['CCENRES'] eq $saData['CCENRES']} selected {/if}>{$i['CCENRES']} - {$i['CDESCRI']}</option>
                     {/foreach}                                          
                  </select>
               </div>
               {* CODIGO DE EMPLEADO Y ESTADO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm">Código Empleado</span>
                  </div>
                  <input type="text" id="pcCriBus" value="{$saData['CCODEMP']}" placeholder="BUSCAR POR APELLIDOS Y NOMBRES/CODIGO Empleado" class="form-control form-control-sm col-sm-2" style="text-transform: uppercase;">
                  <button type="button" id="b_buscar" class="btn btn-outline-primary" style="height: 44px; width: 40px" onclick="f_buscarEmpleado();">
                     <a class="justify-content-center" title="Buscar" >
                        <img src="img/lupa1.png" width="20" height="20∫">
                     </a>
                  </button>
                  <select id="pcCodEmp" name="paData[CCODEMP]"  class="form-control form-control-sm col-sm-4" style="height: 44px">
                     <option value="{$saData['CCODEMP']}" selected>{$saData['CCODEMP']} - {$saData['CNOMEMP']}</option>
                  </select>
                  &nbsp;&nbsp;&nbsp;&nbsp;
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
                     <span class="input-group-text w-100 new-ucsm  ">Cantidad</span>
                  </div>
                  <!-- <input type="hidden" name="paData[NCANTID]" value="{$saData['NCANTID']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[NCANTID]" value="1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Unidad</span>
                  </div>
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCANTID]" value="{$saData['CCANTID']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Código Artículo</span>
                  </div>
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCODART]" value="{$saData['CCODART']}">
               </div>           
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>ADQUISICIÓN</strong></div>   
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Fecha Alta</span>
                  </div>
                  <input type="hidden" name="paData[DFECALT]" value="{$saData['DFECADQ']}">
                  <input type="date" class="form-control text-uppercase black-input col-lg-2"  name="paData[DFECALT]" value="{$saData['DFECALT']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Documento Adquisición</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CDOCADQ]" value="{$saData['CDOCADQ']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CDOCADQ]" value="{$saData['CDOCADQ']}" >
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Comprobante</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CCODREF]" value="> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CCODREF]"  value="{$laDatos['CNROASI']}">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm ">Nro.RUC</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CNRORUC]" value="{$saData['CNRORUC']}"> -->
                  <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CNRORUC]" value="{$saData['CNRORUC']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm ">Razón Social</span>
                  </div>
                  <!-- <input type="hidden" name="paData[CRAZSOC]" value="{$saData['CRAZSOC']}"> -->
                  <input type="text"class="form-control text-uppercase black-input col-lg-6"  name="paData[CRAZSOC]" value="{$saData['CRAZSOC']}">
               </div>
               {* MONEDA TODO *}
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Moneda</span>
                  </div>
                  <input type="text" class="form-control text-uppercase black-input col-lg-6" value="1" name="paData[CMONEDA]">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Moneda Nacional</span>
                  </div>
                  <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" name="paData[NMONTMN]" value="{$saData['NMONTMN']|number_format:2:'.':','}" >
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Moneda Extranjera</span>
                  </div>
                  <input type="text" style="text-align:right;" class="form-control text-uppercase black-input col-lg-2" name="paData[NMONTME]" value="{$saData['NMONTME']|number_format:2:'.':','}">
               </div>
               {* OTROS DATOS *}
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>OTROS DATOS</strong></div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Marca</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMARCA]" value="{$saData['CMARCA']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Modelo</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CMODELO]" value="{$saData['CMODELO']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Placa</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-4" name="paData[CPLACA]" value="{$saData['CPLACA']}">
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Nro. Serie</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CNROSER]" value="{$saData['CNROSER']}">
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Color</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" name="paData[CCOLOR]" value="{$saData['CCOLOR']}">
               </div>
               <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid grey;"><strong>DATOS REFERENCIALES</strong></div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-lg-1 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Fecha</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-1"  value="{$laDatos['DFECDOC']}" disabled>
                  <div class="input-group-prepend col-lg-1 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Monto</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-2" style="text-align:right;" value="{$laDatos['NMONTO']|number_format:2:".":","}" disabled>
                  <div class="input-group-prepend col-lg-2 px-0">
                     <span class="input-group-text w-100 new-ucsm  ">Razon Social</span>
                  </div>
                  <input type="text" maxlength="25" class="form-control text-uppercase black-input col-lg-5" value="{$laDatos['CNRORUC']} - {$laDatos['CRAZSOC']}" disabled>
               </div>
            </div>
            <br>
            <div class="card-footer text-center">
               <button type="submit" name="Boton3" value="GrabarExistente" style="font-weight: 500;" class="btn btn-success col-sm-2" formnovalidate>GRABAR EXISTENTE</button>
               <button type="submit" name="Boton3" value="Grabar" style="font-weight: 500;" class="btn btn-primary col-sm-2" formnovalidate>GRABAR NUEVO</button> 
               <button type="submit" name="Boton1" value="Regresar" style="font-weight: 500;" class="btn btn-danger col-sm-2" formnovalidate>
                  <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
            </div>
         </div>
         </div>    
         <br><br><br><br><br><br>     
      {/if}
   </div>
   <div id="footer"></div>
</form>
</body>
</html>
