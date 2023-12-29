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
   <link rel="stylesheet" type="text/css" href="css/datetimepicker.css">
   <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>
   <script>
      function f_EnviarAprobar() {
         event.preventDefault();
         Swal.fire({
            title: '¿Desea grabar inventario anual?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#17a2b8',   
            cancelButtonColor: '#dc3545',
            confirmButtonText: 'SI',
            cancelButtonText: 'NO'
         })
         .then((result) => {
            $('#Id').val('Grabar');
            // console.log(result.value);
            if (result.value) {
               // console.log("entreeeee");
               $("#p_bFlag").val('true');
               $('#poForm').submit();
            }
         })
      }
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
   </style>
</head>
<body class="bg-light text-dark" onload="Init()">
<div id="header"></div>
<form action="Afj1220.php" method="post" enctype="multipart/form-data" id="poForm">
   <input type="hidden" name="Id" id="Id">
   <input type="hidden" name="p_nBehavior" id="p_nBehavior">
   <input type="hidden" name="pnIndice" id="pnIndice">
   <div class="container-fluid">
      <div class="card-header" style="background:#fbc804; color:black;">
         <div class="input-group input-group-sm d-flex justify-content-between">
            <div class="col text-left"><strong>INICIAR/GUARDAR INVENTARIO</strong></div>
            <div class="col-auto"><b>{$scNombre}</b></div>
         </div>
      </div>
      {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-11">
      <div class="card text-center ">
      <div class="card-body" >
         <div>
            <H2>Solo hacer click en "GRABAR INVENTARIO" cuando culmine el inventario anual</H2>
         </div>
      </div>
      <div class="card-footer text-muted">
         <button class="btn btn-success col-md-2" onclick="f_IniciarInventario()">
            <i class="fa fa-play"></i>&nbsp;&nbsp;INICIAR INVENTARIO</button>
         <button class="btn btn-primary col-md-2" onclick="f_EnviarAprobar()">
            <i class="fas fa-save"></i>&nbsp;&nbsp;GRABAR INVENTARIO</button>
         <button type="submit" name="Boton0" value="Salir" class="btn btn-danger col-md-2" formnovalidate>
            <i class="fas fa-undo-alt"></i>&nbsp;&nbsp;MENÚ</button>
      </div>
      </div>
      </div>
      </div>
      </div>  
      {/if}
   </div>
<div id="footer" style="margin-top: 30%;"></div>
</form>
</body>
<script>

      
</script>
</html>
   