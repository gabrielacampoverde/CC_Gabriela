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
      <script src="bootstrap4/js/bootstrap-select.js"></script>
      <link rel="stylesheet" href="css/style.css">
      <script src="js/java.js"></script>
      <script>
         function f_BuscarUnidad() {
            var lcUniAca = document.getElementById("p_cUniAca");
            var lcPeriod = document.getElementById("p_cPeriod");
            var lcParame = document.getElementById("p_cParame");
            var lcSend = "Id=BuscarUnidad&p_cUniAca=" + lcUniAca.value+
                                        "&p_cPeriod=" + lcPeriod.value+
                                        "&p_cParame=" + lcParame.value;
            $.post("Con1010.php",lcSend).done(function(p_cResult) {
               console.log(p_cResult);
               document.getElementById("detalle").innerHTML = p_cResult;
            });
            return false;
         }
         $(document).ready(function() {
            $('#p_cUniAca').change();
            $('#p_cPeriod').change();
            f_BuscarUnidad();
         });
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
         tbody {
            color: black;
         }
         tr {
            color: black;
         }
      </style>
   </head>
   <body>
      <div id="header"></div>
         <form action="Con1010.php" method="post" id="oform" enctype="multipart/form-data">
            <div class="col-sm-12">
               <div class="container-fluid">   
                  <div class="card-header" style="background:#fbc804; color:black;">
                     <div class="input-group input-group-sm d-flex justify-content-between">
                        <div class="col text-left"><strong>CONSULTA DEUDAS ESTUDIOS A DISTANCIA</strong></div>
                        <div class="col-auto"><b>{$scNombre}</b></div>
                     </div>
                  </div>
                  {if $snBehavior eq 0}
                     <div class="card text-center">
                        <div class="row d-flex justify-content-center">
                           <div class="col-sm-9">
                              <div class="input-group input-group-sm mb-1">
                                 <div class="input-group-prepend new-ucsm col-1 px-2"><strong>ESTUDIANTE</strong></div>
                                 <input type="text" id="p_cParame" name="paData[CPARAME]" class="form-control uppercase" maxlength="50" placeholder="Código / Apellidos y Nombres" onkeyup="f_BuscarUnidad();" >
                                 <div class="input-group-prepend new-ucsm col-1 px-2"><strong>UNIDAD</strong></div>
                                 <select id="p_cUniAca" name="paData[CUNIACA]" onchange="f_BuscarUnidad();" autofocus class="form-control form-control-lg col-3 selectpicker" data-live-search="true">
                                    <option class="text-11" value="*" selected>TODOS</option>
                                    {foreach from=$saUniAca item=i}
                                       <option class="text-11" value="{$i['CUNIACA']}"  {if $i['CUNIACA'] eq $saData['CUNIACA']} selected {/if}>{$i['CNOMUNI']}</option>
                                    {/foreach}
                                 </select>
                                 <div class="input-group-prepend new-ucsm col-1 px-2"><strong>PERIODO</strong></div>
                                 <select id="p_cPeriod" name="paData[CPERIOD]" onchange="f_BuscarUnidad();" autofocus class="form-control form-control-lg col-1 selectpicker" data-live-search="true">
                                    <option class="text-11" value="*" selected>TODOS</option>
                                    {foreach from=$saPeriod item=i}
                                       <option value="{$i['CPROYEC']}"  {if $i['CPROYEC'] eq $saData['CPROYEC']} selected {/if}>{$i['CPROYEC']}</option>
                                    {/foreach}
                                 </select>
                              </div>
                              <input type="hidden" name="p_nIndice" id="p_nIndice">
                              <input type="hidden" name="Id" id="Id">
                              <div class="table-responsive mh-70">
                                 <table class="table table-sm table-hover table-bordered" style="font-size:90%;">
                                    <thead class="thead-dark head-fixed">
                                       <tr class="text-center" >
                                          <th scope="col">#</th>
                                          <th scope="col">NRO. DOCUMENTO</th>
                                          <th scope="col">COD. EST.</th>
                                          <th scope="col">NOMBRE</th>
                                          <th scope="col">PERIODO</th>
                                          <th scope="col" style="width: 1%;">MONTO</th>
                                       </tr>
                                    </thead>
                                    <tbody id="detalle">
                                    </tbody>
                                 </table>
                              </div>
                           </div>
                           <div class="card-footer input-group input-group-sm justify-content-center">
                              <button type="submit" name="Boton0" value="Regresar" class="btn btn-danger col-lg-2 mx-2" formnovalidate><b>REGRESAR</b></button>
                           </div>
                        </div>
                     </div>
               </div>
            </div>
                  {/if}
         </form>
      <!-- Modal editar Jurado-->
   </body>
</html>
