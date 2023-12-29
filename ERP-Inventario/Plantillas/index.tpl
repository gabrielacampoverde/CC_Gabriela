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
   <link rel="stylesheet" href="css/style.css?version=1">
   <script src="js/java.js"></script>
   <script type="text/javascript"></script>
</head>
<body class="ff-Roboto p-0" style="background-image: url('img/campus_nuevo.jpg');background-size: cover;background-repeat: no-repeat;background-attachment: fixed;background-position: center;">
<form action="index.php" method="post">
<div class="container" style="height: 100vh;">
   <div class="row justify-content-center align-items-center h-100">
      <div class="card text-white col-lg-8 m-0 p-0">
         <h5 class="card-header text-center bg-ucsm p-0"><img src="img/logo_ucsm_4.png" width="250px" height="80px"></h5>
         <div class="p-3 d-flex justify-content-center" style="color: black;">
            <div class="row col-lg-8">
               <div class="form-group w-100 text-center m-0">
                  <label><h3>UCSM - ERP</h3></label>
               </div>
               <div class="form-group w-100">
                  <input type="text" name="paData[CNRODNI]" class="form-control text-center" maxlength="9" placeholder="Nro de Documento" autofocus required>
               </div>
               <div class="form-group w-100">
                  <input type="password" name="paData[CCLAVE]" class="form-control text-center" placeholder="Contraseña" required>
               </div>
               <button type="submit" name="Boton1" value="IniciarSesion" class="btn w-100 mb-2 bg-ucsm">Ingresar</button>
            </div>
         </div>
      </div>
   </div>
</div>
</form>
</body>
</html>
