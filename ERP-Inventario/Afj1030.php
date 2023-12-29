<?php
   error_reporting(E_ALL);ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') {
      fxBuscarTransferenciasActFij();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Afj1000.php");
   } else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitMantBuscarTransferencia();
      if (!$llOk) {
         fxHeader("Afj1002.php", $lo->pcError);
         fxScreen(0);
         return;
      }
      $_SESSION['paDatos'] = null;
      fxScreen(0);
   }

   function fxBuscarTransferenciasActFij() {
      $lo = new CControlPatrimonial();
      $lo->paData = $_REQUEST['paData'];
      $llOk = $lo->omBuscarTransferenciasActFij();
      if (!$llOk) {
         fxAlert($lo->pcError);
         $_SESSION['paDatos'] = null;
      } else {
         $_SESSION['paDatos'] = $lo->paDatos;
      }
      fxScreen(0);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1030.tpl');
      // return;
   }
?>
