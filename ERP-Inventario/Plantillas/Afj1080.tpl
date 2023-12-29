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
      }
   </script>   
   <style>
      .bg-ucsm{
         background:#099957;
         color: white;
      }
   </style>
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1080.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <br>
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>COMPONENTES ACTIVO FIJO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-10">
      <div class="card text-center ">
      <div class="card-body" >
         {* Buscar activo fijo *}
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Activo Fijo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[CCODIGO]" autofocus>
            <!-- <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[NCORREL]"> -->
            <button class="btn btn-info" type="submit" name="Boton" value="Buscar"><i class="fas fa-search"></i>&nbsp;&nbsp;Búsqueda</button>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Descripción</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-5" value="{$saData['CDESCRI']}" style="color: black" readonly >
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm ">Código</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CCODIGO']}" style="color: black" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Proveedor</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-5" value="{$saData['CPROVED']}" style="color: black" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Indicador</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CINDACT']}" style="color: black" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Tip Activo Fijo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-5" value="{$saData['CTIPAFJ']}" style="color: black" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Activo Fijo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CACTFIJ']}" style="color: black" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Centro Responsable</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-5" value="{$saData['CCENRES']}" style="color: black" readonly>
            <div class="input-group-prepend col-lg-2 px-0" >
               <span class="input-group-text w-100 bg-ucsm  ">Situación</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="{$saData['CSITUAC']}" style="color: black" readonly>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Usuario Responsable</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-5" value="{$saData['CCODUSU']}" style="color: black" readonly>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Fecha de Adquisición</span>
            </div>
            <input type="date" class="form-control text-uppercase black-input col-lg-2"  value="{$saData['DFECALT']}" style="color: black" readonly>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton" value="Componente" class="btn btn-warning col-md-2 " formnovalidate>
               <i class="fas fa-search"></i>&nbsp;&nbsp;COMPONENTE</button>
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
               <span class="input-group-text w-100 new-ucsm bg-ucsm">Activo Fijo</span>
            </div>
            <input type="text" value="{$saData['CCODIGO']} - {$saData['CDESCRI']}" class="form-control text-uppercase col-lg-6 black-input" disabled>
         </div>
         <div style="height:260px; overflow-y: scroll;">
            <div >
               <table class="table table-sm table-hover table-bordered">
                  <thead class="thead-dark">
                     <tr>
                        <th class="text-center">#</th>
                        <th class="text-center">Descripción</th>
                        <th class="text-center">Situación</th>
                        <th class="text-center">Fec. Adq.</th>
                        <th class="text-center">Monto</th>
                     </tr>
                  </thead>
                  <tbody class="BodTab">
                     {foreach from=$saDatos item=i}
                     <input type="hidden" name="paData[NSERIAL]" value="{$saDatos['NSERIAL']}">
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$i['NSECUEN']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">{$i['CSITUAC']} - {$i['CDESSIT']}</td>
                        <td class="text-center">{$i['DFECADQ']}</td>
                        <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                     </tr>               
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton1" value="Grabar" class="btn btn-primary col-sm-2" formnovalidate>
               <i class="fas fa-save"></i>&nbsp;&nbsp;GRABAR</button>
            <button type="submit" name="Boton1" value="Nuevo" class="btn btn-success col-sm-2" formnovalidate>
               <i class="fas fa-file"></i>&nbsp;&nbsp;NUEVO</button>
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 2}
      <div class="card">
      <div class="p-3 card-body">
         <input type="hidden" name="paData[NSERIAL]" value="{$saData['NSERIAL']}">
         <input type="hidden" name="paData[NSECUEN]" value="{$saData['NSECUEN']}">
         <div class="input-group mb-1" >
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Descripción del Componente</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-7" name="paData[CDESCRI]" style="color: black" autofocus>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Código</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CACTFIJ]" style="color: black" disabled>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Situación</span>
            </div>
            <select class="form-control col-sm-12 black-input col-lg-2" name="paData[CSITUAC]" data-live-search="true" style="color: black">
               {foreach from=$saSituac item=i}                        
                  <option style="color: black" value="{$i['CSITUAC']}">{$i['CDESCRI']}</option>
               {/foreach}                                          
            </select>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Estado</span>
            </div>
            <select class="form-control col-sm-12 black-input col-lg-2" name="paData[CESTADO]" data-live-search="true" style="color: black">
               {foreach from=$saEstado item=i}                        
                  <option style="color: black" value="{$i['CESTADO']}">{$i['CDESCRI']}</option>
               {/foreach}                                          
            </select>
         </div> 
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Fecha Adquisición</span>
            </div>
            <input type="date" class="form-control text-uppercase black-input col-lg-2" id="fechaActual" name="paData[DFECADQ]" style="color: black">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Monto</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" value="0.00" name="paData[NMONTO]" style="color: black; text-align:right;">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Documento Adquisición</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-2" name="paData[CDOCADQ]" style="color: black;">
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Etiqueta</span>
            </div>
            &nbsp;&nbsp;
            <div class="form-control form-check form-check-inline col-lg-2 border-0">
               <input class="form-check-input" type="checkbox"  name="paData[CETIQUE]" checked>
            </div>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Indicador AF</span>
            </div>
            &nbsp;&nbsp;
            <div class="form-control form-check form-check-inline col-lg-2 border-0">
               <input class="form-check-input" type="checkbox" value="S" name="paData[CINDACT]" checked>      
            </div>
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm  ">Indicador Externo</span>
            </div>
            &nbsp;&nbsp;
            <div class="form-check form-check-inline col-lg-2">
               <input class="form-check-input" type="checkbox" value="S" name="paData[CINDEXT]" checked>      
            </div>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Cantidad</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CCANTID]" style="color: black">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Nro. Serie</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CNROSER]" style="color: black">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Placa</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CPLACA]" style="color: black">
         </div>
         <div class="input-group mb-1"> 
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Modelo</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CMODELO]" style="color: black">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm">Color</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CCOLOR]" style="color: black">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 bg-ucsm ">Marca</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-3" name="paData[CMARCA]" style="color: black">
         </div>
      </div>
      <br>
      </div>
      <div class="card-footer text-center">
         <button type="submit" name="Boton2" value="Guardar" class="btn btn-primary col-md-2" formnovalidate>
            <i class="fas fa-save"></i>&nbsp;&nbsp;GUARDAR</button> 
         <button type="submit" name="Boton2" value="Regresar"  class="btn btn-danger col-md-2">
            <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
      </div>
      {/if}
   </div>
<div id="footer"></div>
</form>
</body>
</html>
