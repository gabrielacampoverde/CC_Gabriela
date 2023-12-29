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
      function f_BuscarTipo() {
         document.getElementById("pcTipAfj").options.length = 0;
         var lcSend = "Id=ListarTipos&pcClase=" + $('#pcClase').val();
         // alert(lcSend);
         $.post("Afj2080.php",lcSend).done(function(p_cResult) {
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
            loTipAfj = document.getElementById("pcTipAfj");
            for (var i = 0; i < laDatos.length; i++) {
                // console.log(laDatos[i]);
                var loOption = document.createElement("option");
                loOption.setAttribute("value", laDatos[i].CTIPAFJ);
                loOption.setAttribute("label", laDatos[i].CTIPAFJ + ` - ` + laDatos[i].CDESCRI);
                loTipAfj.appendChild(loOption);
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
<body class="bg-light text-dark">
<div id="header"></div>
<form action="Afj2090.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <div class="container-fluid">
      <div class="card-header text-dark " style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between KTitCar">
            <div class="col text-left"><strong>REPORTE CLASES Y TIPOS</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-12">
      <div class="card text-center ">
         <div style="height:720px; overflow-y: scroll;">
            <div >
               <table class="table table-hover table-sm table-bordered">
                  <thead class="thead-dark head-fixed">
                     <tr class="text-center">
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Fac.Dep.</th>
                        <th>Cnt.Act</th>
                        <th>Cnt.Dep</th>
                        <th>Cnt.Ctr</th>
                        <th>Cnt.Baja</th>
                     </tr>
                  </thead>
                  <tbody>
                     {foreach from=$saDatos item=i}
                        <tr class="text-center" class="custom-select" multiple>
                           <td class="text-center">{$i['CTIPAFJ']}</td>
                           <td class="text-left">{$i['CDESCRI']}</td>
                           <td class="text-center">{$i['NFACDEP']}</td>
                           <td class="text-center">{$i['CCNTACT']}</td>
                           <td class="text-center">{$i['CCNTDEP']}</td>
                           <td class="text-center">{$i['CCNTCTR']}</td>
                           <td class="text-center">{$i['CCNTBAJ']}</td>
                        </tr>
                     {/foreach}
                  </tbody>
               </table>
            </div>
         </div>
         <div class="card-footer text-center">
            <button type="submit" name="Boton" value="PDF" class="btn btn-primary col-md-2" formnovalidate>
               <i class="fas fa-file"></i>&nbsp;&nbsp;PDF</button>
            <button type="submit" name="Boton" value="ReporteExcel" class="btn btn-success col-md-2" formnovalidate>
               <i class="fa fa-file-excel"></i>&nbsp;&nbsp;Reporte EXCEL</button>
            <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-md-2" style="height: 40px !important;" formnovalidate>
               <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button> 
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