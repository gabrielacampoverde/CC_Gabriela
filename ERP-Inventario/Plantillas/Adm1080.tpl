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
   </script>
</head>
<body>
<div id="header"></div>
<main role="main" class="container-fluid">
<div class="row d-flex justify-content-center">
<div class="col-sm-12">
<div class="card text-center">
<div class="card-header bg-sc-ucsm">
   <b>Mi Cuenta</b>
</div>
<form action="Adm1080.php" method="post">
   <input type="hidden" name="paData[CCODUSU]" value="{$saData['CCODUSU']}">
   {if $snBehavior eq 0}
      <div class="card-body">
      <div class="row d-flex justify-content-center">
      <div class="col-sm-8">
      <div class="card text-center ">
         <div class="card-body">
            <div class="input-group input-group-sm mb-1">
               <div class="input-group-prepend col-3 bg-ucsm px-2 justify-content-end">Datos</div>
               <input type="text" name="paData[CNRODNI]" value="{$saData['CNRODNI']}" class="form-control col-2" style="text-transform: uppercase;" readonly>
               <input type="text" name="paData[CNOMBRE]" value="{$saData['CNOMBRE']}" class="form-control col-8" style="text-transform: uppercase;" readonly>
               {* <input type="text" name="paData[CESTADO]" value="{$saData['CESTADO']}" class="form-control" style="text-transform: uppercase;" readonly> *}
            </div>
            <div class="input-group input-group-sm mb-1">
               <div class="input-group-prepend col-3 bg-ucsm px-2 justify-content-end">Celular</div>
               <input type="text" name="paData[CNROCEL]" value="{$saData['CNROCEL']}" class="form-control" maxlength="128" readonly>
            </div>
            <div class="input-group input-group-sm mb-1">
               <div class="input-group-prepend col-3 bg-ucsm px-2 justify-content-end">Email</div>
               <input type="text" name="paData[CEMAIL]" value="{$saData['CEMAIL']}" class="form-control" maxlength="128" readonly>
            </div>
         </div>
         <div class="card-footer text-muted">
             {* <button type="submit" id="BtnGrabar" name="Boton" value="Grabar" class="btn bg-ucsm col-sm-2 col-md-4">Grabar</button> *}
            <button type="submit" name="Boton" value="Salir" class="btn btn-danger col-sm-2 col-md-4" formnovalidate>Salir</button> 
         </div>
      </div>
      </div>
      </div>
      </div>
   {/if}
</form>
</div>
</div>
</div>
</main>
<div id="footer"></div>
</body>
</html>
