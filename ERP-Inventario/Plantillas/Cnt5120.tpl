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
      {* plugin de tabla para paginacion *}
      <link rel="stylesheet" href="css/datatables.min.css">
      <script src="js/datatables.min.js"></script>
      <!-- Script para darle dinamismo a la tabla -->
      <link rel="stylesheet" type="text/css" href="css/datetimepicker.css">
      <script src="js/jquery.datetimepicker.full.min.js"></script>
      <link rel="stylesheet" href="sweetalert/sweetalert2.min.css">
      <script src="sweetalert/sweetalert2.all.min.js"></script>
      <script>
         $(document).ready(function() {
            $('#tdatos').DataTable();
         });
        
        // function f_buscarCCostos() {
        //    var pcBuscar = $("#pcBusqueda").val();
        //    $.ajax({
        //       type:"POST",
        //       url: "Clases/buscarCCostoRes.php",
        //       data: "pcData="+ pcBuscar,
        //    })
        //    .done(function(res){
        //       $("#tdatos").html(res)
        //    })
        //    .fail(function(){
        //       alert("ERROR EN BUSQUEDA");
        //    });
        // }
        
         function f_SeleccionarCenCos(cCenCos) {
           $('#pc_CenCos').val(cCenCos.value);
           $('#btnVer').click();
         }
        
        function f_SeleccionarCenRes(cCenRes) {
          $('#pc_CenRes').val(cCenRes.value);
          $('#btnEditar').click();
        }
      </script>
      <style>
         .black-input:focus {
            background: #FDFD96;
            color: black;
         }
      </style>
   </head>
   <body>
   <div id="header"></div>
      <main role="main" class="container-fluid">
         <form action="Cnt5120.php" method="post">
            <div class="row d-flex justify-content-center">
             <div class="col-sm-12">
              <div class="card text-center">
               <div class="card-header" style="background:#fbc804; color:black;">
                <div class="input-group input-group-sm d-flex justify-content-between">
                  <b>MANTENIMIENTO CENTROS DE RESPONSABILIDAD</b>
                  <div class="input-group-prepend px-2"><b>{$scNombre}</b></div>
                </div>
               </div>
              {if $snBehavior eq 0}
              <div class="card-body">
               <div class="row d-flex justify-content-center">
                <div class="col-sm-11">
                 <!-- <div class="input-group input-group-sm mb-1" style="padding:2px">
                   <div class="form-control col-md-2" style="border:none;color:#245433;text-align:left;font-weight:bold">Centro de Costo:</div>
                   <input type="text" name="pcBusqueda" id="pcBusqueda" style="text-transform: uppercase" class="form-control col-md-8" autofocus />
                   <button type="button" class="btn bg-ucsm col-md-2" onclick="f_buscarCCostos()" >Buscar</button> 
                 </div> -->
                 <div class="card text-center">
                  <div><div class="table-responsive mh-60 mb-3" style="color:black">
                   <table id="tdatos" class="table table-sm table-hover text-12 table-bordered text-center">
                    <thead style="color:black">
                     <tr class="text-center">
                         <th scope="col">#</th>
                         <!-- <th scope="col">CLASE</th> -->
                         <th scope="col">CODIGO</th> 
                         <th scope="col">CENTRO COSTO</th> 
                         <th scope="col">VER</th>
                     </tr>
                    </thead>
                    <tbody>
                     {$k = 1}
                     {foreach from=$saCenCos item=i}
                     <tr>
                         <td class="text-center">{$k++}</td>
                         <!-- <td class="text-left">{$i['CCLASE']}</td> -->
                         <td class="text-center">{$i['CCENCOS']}</td>
                         <td class="text-left">{$i['CDESCRI']}</td>
                         <td align="center"><input type="radio" name="paData[CCENCOS]" value="{$i['CCENCOS']}"></td>
                     </tr>
                     {/foreach}
                    </tbody>
                   </table>
                  </div>
                 </div>
                 <div class="card-footer text-muted">
                   <button type="submit" name="Boton" id="btnVer" value="Ver" class="btn btn-info col-sm-2 col-md-2" formnovalidate>VER</button>
                   <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
                        <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
                 </div>
                </div>
               </div>
              </div>
             </div>
            {else if $snBehavior eq 1}
              <div class="card-body">
               <div class="row d-flex justify-content-center">
                <div class="col-sm-11">
                 <div class="input-group-prepend px-2" style="color:#245433; margin-bottom:15px"><b>CENTRO DE COSTO: {$scCenCo}</b></div>
                 <div class="input-group input-group-sm mb-1" style="padding:2px">
                 </div>
                 <div class="card text-center">
                  <div><div class="table-responsive mh-70 mb-3" style="color:black">
                   <table id="tdatos" class="table table-sm table-hover text-11 table-bordered text-center">
                    <thead style="color:black">
                     <tr class="text-center">
                         <th scope="col">#</th>
                         <th scope="col">CODIGO</th> 
                         <th scope="col">CENTRO DE RESPONSABILIDAD</th> 
                         <th scope="col">ESTADO</th>
                         <th scope="col">EDITAR</th>
                     </tr>
                    </thead>
                    <tbody>
                     {$k = 1}
                     {foreach from=$saDatos item=i}
                     <tr>
                         <td class="text-center">{$k++}</td>
                         <td class="text-center">{$i['CCENRES']}</td>
                         <td class="text-left">{$i['CDESCRI']}</td>
                         <td class="text-center">{$i['CESTADO']}</td>
                         <td align="center"><input type="radio" name="paData[CCENRES]" value="{$i['CCENRES']}"></td>
                     </tr>
                     {/foreach}
                    </tbody>
                   </table>
                  </div>
                 </div>
                 <div class="card-footer text-muted">
                   <button type="submit" name="Boton" value="Nuevo" class="btn bg-ucsm col-sm-2 col-md-3" formnovalidate>Nuevo</button>
                   <button type="submit" name="Boton" id="btnEditar" value="Editar" class="btn btn-info col-sm-2 col-md-3">Editar</button>
                   <button type="submit" name="Boton" value="Regresar" class="btn btn-danger col-sm-2 col-md-3" formnovalidate>Regresar</button> 
                 </div>
                </div>
               </div>
              </div>
             </div>
            {else if $snBehavior eq 2}
            <div style="margin:1.25rem">
               <div class="input-group input-group-sm mb-1" style="padding:2px">
                  <div class="form-control col-md-2" style="border:none;color:#245433;text-align:left;font-weight:bold">CENTRO COSTO:</div>
                  <div class="form-control col-md-10" style="border:none;color:#245433;text-align:left;font-weight:bold">{$saData['CCENCOS']}</div>
               </div>
               <div class="input-group input-group-sm mb-1" style="padding:2px">
                  <div class="form-control col-md-2" style="border:none;color:#245433;text-align:left;font-weight:bold;text-transform: uppercase">ID:</div>
                  <input type="text" name="paData[CCENRES]" value="{$saData['CCENRES']}" class="form-control col-md-10" readonly>
               </div>
               <div class="input-group input-group-sm mb-1" style="padding:2px">
                  <div class="form-control col-md-2" style="border:none;color:#245433;text-align:left;font-weight:bold">DESCRIPCIÓN:</div>
                  <input type="text" name="paData[CDESCRI]" value="{$saData['CDESCRI']}" maxlength="200" style="text-transform: uppercase" class="form-control col-md-10 black-input" autofocus required>
               </div>
               <div class="input-group input-group-sm mb-1">
                  <div class="form-control col-md-2" style="border:none;color:#245433;text-align:left;font-weight:bold">ACTIVO:</div>
                  <input type="checkbox" name="paData[CESTADO]" value="A" {if $saData['CESTADO'] eq 'A'} checked{/if} {if $saData['CCENRES'] eq '*'}checked readonly{/if}>
               </div>
            <input type="hidden" name="paData[CUSUCOD]" value="{$saData['CUSUCOD']}">
            <div class="card-footer text-muted">
               <button type="submit" name="Boton" value="Grabar" class="btn bg-ucsm col-sm-2 col-md-3">Grabar</button>
               <button type="submit" name="Boton" value="Cancelar" class="btn btn-danger col-sm-2 col-md-3" formnovalidate>Cancelar</button> 
            </div>     
           {/if}
        </div>
       </div>
      </div>
     </form>
    </main>
    <div id="footer"></div>
   </body>
</html>
