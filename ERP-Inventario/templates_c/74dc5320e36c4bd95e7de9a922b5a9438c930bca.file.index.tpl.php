<?php /* Smarty version Smarty-3.1.8, created on 2023-12-14 11:05:04
         compiled from "Plantillas/index.tpl" */ ?>
<?php /*%%SmartyHeaderCode:770095275657b27b0ef6c18-77912341%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '74dc5320e36c4bd95e7de9a922b5a9438c930bca' => 
    array (
      0 => 'Plantillas/index.tpl',
      1 => 1695224491,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '770095275657b27b0ef6c18-77912341',
  'function' => 
  array (
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.8',
  'unifunc' => 'content_657b27b0ef9819_58229575',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_657b27b0ef9819_58229575')) {function content_657b27b0ef9819_58229575($_smarty_tpl) {?><!DOCTYPE html>
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
<?php }} ?>