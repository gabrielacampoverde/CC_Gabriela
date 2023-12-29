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
      function f_buscarRol() {
         var lcBusRol = document.getElementById("pcBusRol");
         if (lcBusRol.value.length < 3) {
            alert("DEBE INGRESAR AL MENOS 1 CARACTERES PARA LA BUSQUEDA");
            return;
         }
         var lcRol = document.getElementById("pcRol");
         lcRol.innerHTML = '';
         $('#pcRol').selectpicker('refresh');
         var xhttp = new XMLHttpRequest();
         xhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
               var laJson = JSON.parse(this.responseText);
               for (var i = 0; i < laJson.length; i++) {
                  var option = document.createElement("option");
                  option.text = laJson[i].CCODROL + " - " + laJson[i].CDESCRI;
                  option.value = laJson[i].CCODROL;
                  lcRol.add(option);
               }
               $('#pcRol').selectpicker('refresh');
            }
         }
         var lcSend = "Id=buscarRol&pcBusRol=" + lcBusRol.value;
         xhttp.open("POST", "Adm1050.php", true);
         xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
         xhttp.send(lcSend);
      }
      function f_agregar() {
         var xhttp = new XMLHttpRequest();
         xhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
               document.getElementById("detalle").innerHTML = this.responseText;
            }
         }
          var lcRol = document.getElementById("pcRol");
          var lcSend = "Id=agregar&p_nIndice=" + lcRol.selectedIndex;
          xhttp.open("POST", "Adm1050.php", true);
          xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xhttp.send(lcSend);
      }
      function f_eliminar() {
         var xhttp = new XMLHttpRequest();
         xhttp.onreadystatechange = function () {
            if (this.readyState == 4 && this.status == 200) {
               document.getElementById("detalle").innerHTML = this.responseText;
            }
         }
          var lcIndice = document.querySelector('input[name="p_nIndice"]:checked').value;
          var lcSend = "Id=eliminar&p_nIndice="+lcIndice;
          xhttp.open("POST", "Adm1050.php", true);
          xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
          xhttp.send(lcSend);
      }      
   </script>
</head>
<body onload="Init()">
<div id="header"></div>
<form action="Adm1050.php" method="post" enctype="multipart/form-data" id="poForm" >
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header bg-ucsm" style="background: #fbc804">
         <div class="input-group input-group-sm d-flex justify-content-between" style="color: black; font-weight: 500">
            <strong>ASIGNAR ROLES A USUARIO</strong>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
         <div class="card-body">
            <div class="row d-flex justify-content-center">
            <div class="col-sm-10">
            <div class="card text-center ">
               <div class="card-body">
                  <div class="input-group mb-3 d-flex justify-content-center">
                     <div class="input-group-prepend">
                        <label class="input-group-text bg-sc-ucsm">Usuario</label>
                     </div>
                     <input type="text"  name="paData[CBUSUSU]" class="form-control text-uppercase" placeholder="APELLIDOS Y NOMBRES/CODIGO DE TRABAJADOR" autofocus>
                     <div class="input-group-append">
                        <button type="submit" name="Boton" value="Buscar" class="btn btn-outline-info" formnovalidate>Buscar</button>
                     </div>
                  </div>
                  <div class="mh-50">
                     <table class="table table-sm table-hover text-center">
                        <thead class="bg-ucsm">
                           <tr>
                           <th scope="col">#</th> 
                           <th scope="col">Codigo</th> 
                           <th scope="col">DNI</th>
                           <th scope="col">Apellidos y Nombres</th>
                           <th scope="col">Estado</th>
                           <th scope="col"><img src="css/feather/check-square-white.svg"></th>
                           </tr>
                        </thead>
                        <tbody>
                           {$j = 0}
                           {foreach from = $saTrabajador item = i}
                              <tr>
                              <th scope="row">{$j}</th>
                              <td>{$i['CCODUSU']}</td> 
                              <td>{$i['CNRODNI']}</td>
                              <td class="text-left">{$i['CNOMBRE']}</td>
                              <td>
                              {if $i['CESTADO'] eq 'A'}
                                 <img src="css/svg/ic_active_24.svg">
                              {elseif $i['CESTADO'] eq 'I'}
                                 <img src="css/svg/ic_inactive_24.svg">
                              {/if}
                              </td>
                              <td><input name="pcCodUsu" type="radio" value="{$i['CCODUSU']}" required></td> 
                              </tr>
                              {$j = $j + 1}
                           {/foreach}
                        </tbody>                        
                     </table>
                  </div>
               </div>
               <div class="card-footer text-muted">
                  <button type="submit" name="Boton" value="Editar" class="btn  btn-info col-sm-2 col-md-3"><i class="fas fa-edit"></i>&nbsp;&nbsp;Editar</button>
                  <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-sm-2 col-md-3" formnovalidate><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Salir</button> 
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
            <div class="p-1"> 
               <div class="form-row mb-1">
               <div class="input-group input-group-sm mb-1">
               <div class="input-group-prepend bg-ucsm px-4">USUARIO </div>
               <input type="text" placeholder="NUEVO" name="paData[CCODUSU]" value="{$saData['CCODUSU']}" class="form-control" readonly required>
               <input type="text" placeholder="NUEVO" name="paData[CNRODNI]" value="{$saData['CNRODNI']}" class="form-control" readonly required>
               <input type="text" placeholder="NUEVO" name="paData[CNOMBRE]" value="{$saData['CNOMBRE']}" class="form-control" readonly required>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-3 bg-ucsm px-2 justify-content-end">Rol</div>
                  <input type="text" id="pcBusRol" class="form-control uppercase col-8">
                  <div class="input-group-append">
                     <button type="button" class="btn btn-outline-primary" onclick="f_buscarRol();">Buscar</button>
                  </div>
               </div>
               <div class="input-group mb-1">
                  <div class="input-group-prepend col-3 px-2 justify-content-end"></div>
                  <select id="pcRol" class="selectpicker form-control col-8" data-live-search="true"></select>
                  <div class="input-group-append">
                     <button type="button" class="btn btn-outline-primary" onclick="f_agregar();">Agregar</button>
                  </div>
               </div>
               </div>
               <div class="form-row">
               <div class="col-sm-12">
                  <div class="card text-center ">
                  <div>
                  <div class="table-responsive mh-50">
                     <table class="table table-sm table-hover table-bordered">
                           <thead class="bg-ucsm">
                           <tr>
                           <th scope="col">#</th>
                           <th scope="col">Codigo Rol</th> 
                           <th scope="col">Descripcion</th>
                           <th scope="col">Descripcion Corta</th>
                           <th scope="col">Estado</th>
                           <th scope="col"><img src="css/feather/check-square-white.svg"></th>
                           </tr>
                           </thead>
                           <tfoot>
                              <tr>
                              <td colspan="7" class="text-center">
                                 <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-warning" onclick="f_eliminar();">Eliminar</button>
                                 </div>
                              </td>
                              </tr>
                           </tfoot>
                           <tbody id="detalle">
                              {$j = 0}
                              {foreach from = $saDatos item = i}
                                 <tr>
                                 <th scope="row">{$j + 1}</th>
                                 <td>{$i['CCODROL']}</td> 
                                 <td class="text-left">{$i['CDESCRI']}</td>
                                 <td class="text-left">{$i['CDESCOR']}</td>
                                 <td>
                                    {if $i['CESTADO'] eq 'A'}
                                       <img src="css/svg/ic_active_24.svg">
                                    {elseif $i['CESTADO'] eq 'I'}
                                       <img src="css/svg/ic_inactive_24.svg">
                                    {/if}
                                 </td>
                                 <td><input id="p_nIndice" name="p_nIndice" type="radio" value="{$j}"></td> 
                                 </tr>
                                 {$j = $j + 1}
                              {/foreach}
                           </tbody>  
                     </table>
                  </div>
                  </div>
                  </div>
               </div>
               </div> 
            </div>
            <br>
            <div class="card-footer text-center">
               <button type="submit" name="Boton1" value="Guardar" class="btn bg-ucsm col-md-2"><i class="fas fa-save"></i>&nbsp;&nbsp;Guardar</button>
               <button type="submit" name="Boton1" value="Cancelar" class="btn btn-danger col-md-2" formnovalidate><i class="fas fa-undo-alt"></i>&nbsp;&nbsp;Cancelar</button> 
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
