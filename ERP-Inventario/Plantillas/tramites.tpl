<!DOCTYPE html>
<html>
<head>
   <title>ERP - Universidad Católica de Santa María</title>
   <meta charset="utf-8">
   <link rel="icon" type="image/png" href="img/logo_ucsm.png">
   <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
   <link rel="stylesheet" href="css2/jquery-ui.min.css">
   <link rel="stylesheet" href="css2/bootstrap.min.css">
   <link rel="stylesheet" href="css2/bootstrap-select.min.css">
   <link rel="stylesheet" href="css2/style.css?version=1">
   <script src="css2/jquery-3.3.1.min.js"></script>
   <script src="css2/jquery-ui.min.js"></script>
   <script src="css2/bootstrap.bundle.min.js"></script>
   <script src="css2/bootstrap-select.min.js"></script>
   <script src="css2/java.js"></script>
   <link rel="stylesheet" href="sweetalert/sweetalert2.min.css">
   <script src="sweetalert/sweetalert2.all.min.js"></script>
</head>
<body class="ff-Roboto" style="background-image: url('img/campus_1.jpg');background-size: cover;background-repeat: no-repeat;background-attachment: fixed;background-position: center;">
<div id="fb-root"></div>
<script>
   window.fbAsyncInit = function() {
      FB.init({
         xfbml            : true,
         version          : 'v7.0'
      });
   };(function(d, s, id) {
      var js, fjs = d.getElementsByTagName(s)[0];
      if (d.getElementById(id)) return;
      js = d.createElement(s); js.id = id;
      js.src = 'https://connect.facebook.net/es_LA/sdk/xfbml.customerchat.js';
      fjs.parentNode.insertBefore(js, fjs);
   }(document, 'script', 'facebook-jssdk'));
</script>
<!-- Your Chat Plugin code -->
<!--div class="fb-customerchat" attribution=setup_tool page_id="138087020200132" theme_color="#5cb85c" logged_in_greeting="Hola! ¿Cuál es tu consulta acerca del Sitema de Gestión de Tesis Online?" logged_out_greeting="Hola! ¿Cuál es tu consulta acerca del Sistema de Gestión de Tesis Online?"></div-->
<form action="tramites.php" method="post">
   <div class="container" style="height: 100vh;">
      {if $scBehavior eq 0}
         <div class="row justify-content-center align-items-center h-100 p-3">
            <div class="card text-white col-lg-6" style="background: #fff; padding-right: 0px; padding-left: 0px;">
               <div class="card-header text-white" style="background: #05be6a">
                  <h3 class="text-center" style="color: white;"><b>UCSM - ERP</b><br><b>INGRESO - INVITADOS</b></h3>
               </div>           
               <div class="card p-3" style="border: 0px">
                  <div class="card-body p-1 text-12 alert-warning">
                     <p class="mb-0 text-center">POR ESTE MEDIO SÓLO INGRESAN PERSONAS QUE NUNCA <strong>INGRESARON O ESTUDIARON EN LA UCSM</strong>.</p>
                  </div>
               </div>
               <div class="p-0 d-flex justify-content-center" style="color: black;">
                  <div class="row col-lg-12 text-center">
                     <div class="form-group w-100">
                        <h6 style="text-align: left;"><b>DNI</b></h6>
                        <div class="input-group">
                           <span class="input-group-addon" style="background-color: #C7C7C8;width: 8%; border-radius: 6px 0px 0px 6px;"><img src="css/menu/user.png" style="max-width: 50%; max-height: 70%; margin-top: 9px;" class="card-img-top"></span>                           
                           <input type="text" id="pcNroDni" maxlength="8" name="paData[CNROINV]" class="form-control text-uppercase" placeholder="INGRESE EL NUMERO DE DNI" autofocus>
                        </div>
                     </div>
                     <div class="form-group w-100">
                        <label><h7><b>Captcha <br> Resuelva la Operación: </b></h7><br><img class="Captcha" src="Captcha.php?IS=A"></label>
                        <input type="number" name="pcCaptcha" class="form-control text-center" placeholder="INGRESE LA RESPUESTA">
                     </div>
                     <button type="submit" name="Boton" value="Iniciar3"  class="btn w-100 mb-2" style="background: #05be6a; color: white">INGRESAR</button><br>
                     
                     {* <button type="submit" name="Boton" value="Regresar" class="btn w-100 mb-2 bg-danger" style="color: #ffffff">REGRESAR</button> *}
                  </div>
               </div>
               <br>
            </div>
         </div>
      {/if}
   </div>
</form>
<!-- MODAL ESPECIFICACIONES-->
<div class="modal fade" id="myModal" role="dialog">
   <div class="modal-dialog modal-lg">
      <div class="modal-content">
         <div class="modal-header" style="background-color: #ffa000;">
            <h5 class="modal-title" style="color: #ffffff;"> ESPECIFICACIONES </h5>   
            <button type="button" class="close" data-dismiss="modal">&times;</button>            
         </div>
         <div class="modal-body justify-content-center">
            <strong> PARA INGRESAR AL SISTEMA </strong><br><label style="text-align: justify;"> La contraseña es la misma del sistema académico de pregrado o postgrado (dónde se ven las notas o donde se realizan las matrículas). </label><br><br> 
            <strong> PARA RECUPERAR LA CONTRASEÑA </strong><br><label style="text-align: justify;"> Para recuperar o restaurar su contraseña, debe ingresar al siguiente enlace <a href="https://webapp.ucsm.edu.pe/sm/Views/login.php" target="#">https://webapp.ucsm.edu.pe/sm/Views/login.php</a> y <strong>generar una nueva contraseña</strong>. O enviar un correo a <strong>soporte@ucsm.edu.pe</strong> con el asunto <strong>CONTRASEÑA TRÁMITES ONLINE</strong> (de preferencia desde su correo institucional, o si es de su correo personal con la fotografía de ambas caras de su DNI adjunta al correo), y en el contenido sus datos de estudios (DNI, nombre completo, carrera y su código de estudiante).</label>
         </div>
      </div>
   </div>
</div>
</body>
</html>