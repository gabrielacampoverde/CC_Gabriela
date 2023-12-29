<?php
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   error_reporting(E_ALL);ini_set('display_errors', 0);
   $loSmarty = new Smarty;
    if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton0'] == 'Salir') { 
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }

   function fxInit() {
      fxScreen(0);
   }

   function fxScreen($p_nBehavior) {
	   global $loSmarty;
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj2000.tpl');
      return;
   }
?>