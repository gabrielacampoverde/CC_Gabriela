<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
    if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton'] == 'PDF') {
      faxRepClasesTiposPDF();
   } elseif (@$_REQUEST['Boton'] == 'ReporteExcel') {
      fxReporteClasesTiposExcel();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omInitManAFClase();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }

   function faxRepClasesTiposPDF() { 
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDatos'];
      $llOk = $lo->omRepClasesTiposPDF();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
         // echo json_encode($lo->paData);
      }
      fxScreen(0);
   }

   function fxReporteClasesTiposExcel(){
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = $_SESSION['paDatos'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omRepClasesTiposExcel();
      if (!$llOk) {
         fxHeader('Afj2090.php', $lo->pcError);
      } else {
         //print_r($lo->paData);
         fxDocumento($lo->paData);
         fxScreen(0);
      }
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saDatCla', $_SESSION['paDatCla']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Afj2090.tpl');
   }
?>
