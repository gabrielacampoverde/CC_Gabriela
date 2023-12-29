<?php
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   
   //ini_set('max_execution_time', '500'); //300 seconds = 5 minutes
   error_reporting(E_ALL);ini_set('display_errors', 0);
   
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'No ha iniciado sesión.');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') {
      fxBuscarActivoFijo();
   } elseif (@$_REQUEST['Boton'] == 'BusquedaPDF') {
      fxReporteBusquedaActFij();
   } elseif (@$_REQUEST['Boton'] == 'ReportePDF') {
      fxReportePDF();
   } elseif (@$_REQUEST['Boton'] == 'BusquedaEXCEL') {
      fxReporteBusquedaActFijExcel();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } else {
      Init();
   }

   function Init(){
      $_SESSION['paDatos'] = null;
      fxScreen(0);
   }
   function fxBuscarActivoFijo() {
      $laData = $_REQUEST['paData'];
      $_SESSION['paData0'] = $_REQUEST['paData'];
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omBuscarActFij();
      if (!$llOk) {
         fxAlert($lo->pcError);
         $_SESSION['paDatos'] = null;
      } 
      $_SESSION['paDatos'] = $lo->paDatos;
      // print_r($_SESSION['paDatos']);
      fxScreen(0);
   }

   function fxReporteBusquedaActFij() {
      $laData = $_SESSION['paDatos'];
      // $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omReporteBusquedaActFij();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(0);
   }

   function fxReporteBusquedaActFijExcel() {
      $laData = $_SESSION['paDatos'];
      // $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omReporteBusquedaActFijExcel();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData);
      }
      fxScreen(0);
   }

   function fxReportePDF() {
      $lnIndice = $_REQUEST['pnIndice']-1;
      $laData = $_SESSION['paDatos'][$lnIndice];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omReporteActFijPDF();
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
      $loSmarty->display('Plantillas/Afj2010.tpl');
      return;
   }

?>