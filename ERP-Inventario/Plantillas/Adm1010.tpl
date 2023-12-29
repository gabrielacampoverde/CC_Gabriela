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
</head>
<body onload="Init()">
<div id="header"></div>
<form action="Adm1010.php" method="post" enctype="multipart/form-data" id="poForm" >
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header bg-ucsm" style="background: #fbc804">
         <div class="input-group input-group-sm d-flex justify-content-between" style="color: black; font-weight: 500">
            <strong>MANTENIMIENTO DE ROLES</strong>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="card">
         <div class="row d-flex justify-content-center">
            <div class="col-sm-10">
            <div class="table-responsive p-3 mh-70">
               <table id="T_Oficinas" class="table table-sm table-hover text-15 text-center">
                  <thead class="thead text-dark bg-light text-center text-15">
                     <tr class="text-center"  style="background:#000000; color:white">
                        <th scope="col">Código</th> 
                        <th scope="col">Descripcion</th>
                        <th scope="col">Descripcion corta</th>
                        <th scope="col">Estado</th>       
                        <th scope="col"><img src="css/feather/check-square-white.svg"></th>
                     </tr>
                  </thead>
                  <tbody id="TBody">
                     {foreach from = $saDatos item = i}
                        {$k=$k+1}
                        <tr class="text-center" style="color: black; font-weight: 500" onclick="f_seleccionarFila(this);" >
                           <td scope="row">{$i['CCODROL']}</td> 
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-left">{$i['CDESCOR']}</td>
                           <td>
                              {if $i['CESTADO'] eq 'A'}
                                 <img src="css/svg/ic_active_24.svg">
                              {elseif $i['CESTADO'] eq 'I'}
                                 <img src="css/svg/ic_inactive_24.svg">
                              {/if}
                           </td>
                           <td><input name="pcCodRol" type="radio" value="{$i['CCODROL']}" required></td>
                        </tr> 
                     {/foreach}
                  </tbody>
               </table>
            </div>
            <br>
            <div class="card-footer text-center">
               <button type="submit" name="Boton" value="Nuevo" class="btn bg-ucsm col-md-2" formnovalidate><i class="fas fa-share"></i>&nbsp;&nbsp;Nuevo</button>
               {if $saDatos neq NULL}
                  <button type="submit" name="Boton" value="Editar" class="btn  btn-info col-md-2"><i class="fas fa-edit"></i>&nbsp;&nbsp;Editar</button>
               {/if}
               <button type="submit" name="Boton" value="Salir" class="btn btn-danger  col-md-2" formnovalidate><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Salir</button>
            </div>
         </div>
         </div>
         </div>

      {else if $snBehavior eq 1}
         <div class="card-body">
         <div class="row d-flex justify-content-center">
         <div class="col-sm-6">
         <div class="card text-center ">
         <div class="card-body" >
            <input type="hidden" name="paData[CNUEVO]" value="{$saData['CNUEVO']}">
            <div class="input-group mb-3">
               <div class="input-group-prepend">
                  <span class="input-group-text bg-sc-ucsm" id="inputGroup-sizing-default">Código</span>
               </div>
               <input type="text" placeholder="000/AAA" name="paData[CCODROL]" value="{$saData['CCODROL']}" {if $saData['CNUEVO'] eq 'N'} readonly {/if} class="form-control" maxlength="3" required>
            </div>
            <div class="input-group mb-3 d-flex align-items-start">
               <div class="input-group-prepend">
                  <span class="input-group-text bg-sc-ucsm" id="inputGroup-sizing-default">Descripción</span>
               </div>
               <textarea name="paData[CDESCRI]" class="form-control no-resize" maxlength="100" rows="4" style="text-transform: uppercase;" required>{$saData['CDESCRI']}</textarea>
            </div>
            <div class="input-group mb-3 d-flex align-items-start">
               <div class="input-group-prepend">
                  <span class="input-group-text bg-sc-ucsm" id="inputGroup-sizing-default">Descripción Corta</span>
               </div>
               <input type="text" name="paData[CDESCOR]" value="{$saData['CDESCOR']}" class="form-control" maxlength="40" style="text-transform: uppercase;" required>
            </div>
            <div class="input-group mb-3 d-flex justify-content-center">
               <div class="input-group-prepend">
                  <label class="input-group-text bg-sc-ucsm">Estado</label>
               </div>
               <select name="paData[CESTADO]" class="custom-select col-sm-4">
                  {foreach from = $saEstado item = i}
                     <option value="{$i['CCODIGO']}" {if $saData['CESTADO'] eq $i['CCODIGO']} selected {/if}>{$i['CDESCRI']}</option>
                  {/foreach}
               </select>
            </div>
         </div>
            <br>
         <div class="card-footer text-center">
            <button type="submit" name="Boton1" value="Grabar" class="btn bg-ucsm col-sm-2 col-md-4"><i class="fas fa-save"></i>&nbsp;&nbsp;Grabar</button>
            <button type="submit" name="Boton1" value="Cancelar" class="btn btn-danger col-sm-2 col-md-4" formnovalidate><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Regresar</button> 
         </div>
         </div>
         </div>
         </div>
         </div>
         </div>
      {/if}
   </div>
   <br><br>
   <div id="footer"></div>
</form>
</body>
</html>
