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
      
   </script>   
   <style>
      .new-ucsm{
         background:#099957;
         color: white;
         font-weight: 500;
         padding: .375rem .75rem;
      }
   </style>
</head>
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj1060.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <br>
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>GASTOS ACTIVO FIJO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-8">
      <div class="card text-center ">
      <div class="card-body justify-content-center" >
         <div class="input-group mb-1" style="margin-left:13rem;">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm bg-ucsm">Activo Fijo</span>
            </div>
            <input type="text" name="paData[CCODDES]" class="form-control text-uppercase col-lg-4 black-input" autofocus>
            <!-- <input type="text" name="paData[CCODDES]" class="form-control text-uppercase col-lg-6 black-input" autofocus> -->
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton" value="Buscar" id="btnApLicar" class="btn btn-primary col-md-2 " formnovalidate>
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
      <div class="card text-center ">
      <div class="card-body" >         <br>
         <div style="height:350px; overflow-y: scroll;">
            <div >
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark">
                     <tr class="text-center">
                        <th>#</th>
                        <th>Act.Fij.</th> 
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Situación</th>
                        <th>Tipo</th>
                        <th width="30" style="word-break:break-all;"><i class="fas fa-check-circle"></i></th>
                     </tr>
                  </thead>
                  <tbody>
                  {$k = 1}
                  {foreach from=$saDatos item=i}
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CACTFIJ']}</td>
                        <td class="text-center">{$i['CCODIGO']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">{$i['CESTADO']} - {$i['CDESEST']}</td>
                        <td class="text-center">{$i['CSITUAC']} - {$i['CDESSIT']}</td>
                        <td class="text-left">{$i['CDESTIP']}</td>
                        <td class="text-center">
                           <form>
                              <input type="hidden" name="paData[CACTFIJ]" value="{$i['CACTFIJ']}" required/>
                              <button type="submit" name="Boton1" value="Editar" >
                                 <i class="fas fa-check-circle icon-tbl"></i>
                              </button>
                           </form>
                        </td>
                     </tr>  
                  {$k = $k + 1}
                  {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton1" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 2}
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
            <input type="hidden" name="paData[CACTFIJ]" value="{$saData['CACTFIJ']}" required/>
            <div >
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark">
                     <tr>
                     <th scope="col">#</th> 
                     <th scope="col">Código</th>
                     <th scope="col">Descripción</th>
                     <th scope="col">Estado</th>
                     <th scope="col">Fecha</th>
                     <th scope="col">Monto</th>
                  </thead>
                  <tbody>
                  {$k=1}
                  {foreach from=$saDatos item=i}
                     <tr class="text-center" class="custom-select" multiple>
                        <td class="text-center">{$k}</td>
                        <td class="text-center">{$i['CCODFOR']}</td>
                        <td class="text-left">{$i['CDESCRI']}</td>
                        <td class="text-center">{$i['CESTADO']}</td>
                        <td class="text-center">{$i['DFECHA']}</td>
                        <td class="text-right">{$i['NMONTO']|number_format:2:".":","}</td>
                     </tr>
                  {$k=$k+1}
                  {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton2" value="Grabar" class="btn btn-primary col-sm-2" formnovalidate>
               <i class="fas fa-save"></i>&nbsp;&nbsp;GRABAR</button>
            <button type="submit" name="Boton2" value="Nuevo" class="btn btn-success col-sm-2" formnovalidate>
               <i class="fas fa-file"></i>&nbsp;&nbsp;NUEVO</button>
            <button type="submit" name="Boton2" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {else if $snBehavior eq 3}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-10">
      <div class="card text-center ">
      <div class="card-body" >
         <div class="col text-left" style="background-color:#FABC49;border-radius: 8px;border:1px solid black;"><strong>REGISTRAR GASTO</strong></div>
         <br>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm bg-ucsm">Tipo Gasto</span>
            </div>
            <select class="form-control col-sm-12 black-input col-lg-4" name="paData[CCODFOR]">
               {foreach from=$saTipGas item=i}                        
                  <option value="{$i['CCODFOR']}">{$i['CDESCRI']}</option>
               {/foreach}                                          
            </select>
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm">Fecha</span>
            </div>
            <input type="date" class="datapicker form-control text-uppercase black-input col-lg-4" name="paData[DFECHA]"  value="{$saData['DFECHA']}">
         </div>
         <div class="input-group mb-1">
            <div class="input-group-prepend col-lg-2 px-0">
               <span class="input-group-text w-100 new-ucsm ">Monto</span>
            </div>
            <input type="text" class="form-control text-uppercase black-input col-lg-4" name="paData[NMONTO]" value="{$saData['NMONTO']}" placeholder="000.00">
         </div>
         <br>
         <div class="card-footer text-muted">
            <button type="submit" name="Boton3" value="Guardar" class="btn btn-primary col-sm-2" formnovalidate>
               <i class="fas fa-save"></i>&nbsp;&nbsp;GUARDAR</button>
            <button type="submit" name="Boton3" value="Regresar" class="btn btn-danger col-md-2" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;REGRESAR</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
      </div>
      {/if}
   </div>
<div id="footer"></div>
<div class="modal fade" id="modal_loading" data-backdrop="static" data-keyboard="false" tabindex="-1">
   <div class="modal-dialog modal-lg" style="margin:10rem auto;max-width:300px" role="document">
      <div class="modal-content">
         <div class="modal-body">
            <div class="notificaciones">
               <div class="wrap">
                  <ul class="lista" id="lista">
                  <br>
                     <li>
                        <div class="container-fluid">
                           <div class="row">
                              <div class="col-sm-12">
                                 <center>
                                 <h5>Procesando, espere por favor...</h5>
                                 <img src="img/loading.gif" width="150" height="200" class="d-inline-block align-top" alt="">
                                 </center>
                              </div>
                           </div>
                        </div>
                        <br>
                     </li>
                  </ul>
               </div>
            </div>
         </div>
      </div>
   </div>
</div>
</form>
<script>
   $("#btnApLicar").click("click", function(){
        // alert("event click");
        let md = $("#modal_loading");
        console.log(md);
        md.modal('show');
   });
</script>
</body>
</html>
