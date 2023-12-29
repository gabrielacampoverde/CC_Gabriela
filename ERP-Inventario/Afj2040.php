<?php
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   
   //ini_set('max_execution_time', '500'); //300 seconds = 5 minutes
   error_reporting(E_ALL);ini_set('display_errors', 0);
   
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'No ha iniciado sesión.');
   } elseif (@$_REQUEST['Boton'] == 'Reporte') {
      fxReportePorFechaPDF();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }

   function fxInit() {
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      fxScreen(0);
   }

   function fxReportePorFechaPDF() {
      $laData = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omReportePorFechaPDF();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(0);
   }
   
   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj2040.tpl');
      return;
   }

?>