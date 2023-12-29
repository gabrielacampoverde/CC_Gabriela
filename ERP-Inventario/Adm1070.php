<?php
   // Cambiar Clave
   session_start();
   error_reporting(0);
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CMantenimiento.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Grabar') {
      fxGrabar();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CMantenimiento();
      $lo->paData = ['CCODUSU' => $_SESSION['GCCODUSU']];
      $llOk = $lo->omTraerDatosUsusuario();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paData'] = $lo->paData;
      fxScreen(0);
   }
   
   function fxGrabar() {
      $laData = $_REQUEST['paData'];
      $lo = new CMantenimiento();
      $lo->paData = $laData;
      $llOk = $lo->omCambiarClave();
      if (!$llOk) {
         fxScreen(0);
         fxAlert($lo->pcError);
         return;
      }
      fxHeader("Mnu1000.php", "CONTRASEÑA CAMBIADA");
   }
   
   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData',   $_SESSION['paData']);
      $loSmarty->assign('saDatos',  $_SESSION['paDatos']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Adm1070.tpl');
   }
?>