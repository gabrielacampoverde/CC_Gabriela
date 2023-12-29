<?php
   # ------------------------------------------------------
   # Reporte de AF por centro de costo
   # 2022-01-27 KRA-JCF Adecuacion a nuevos requerimientos
   # ------------------------------------------------------
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
    if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Reporte') {
      fxReportePorCentroCosto();
   } elseif (@$_REQUEST['Boton'] == 'ReporteExcel') {
      fxReportePorCentroCostoExcel();
   } elseif (@$_REQUEST['Boton'] == 'ReporteExcelTotal') {
      fxReportePorCentroCostoExcelTotal();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omInitReporteCentroResponsabilidad();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos'] = $lo->paDatos;

      fxScreen(0);
   }

   function fxReportePorCentroCosto() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = array_merge($laData, $_REQUEST['paData']);
      $lo = new CControlPatrimonial();
      //$lo->paData = array_merge($laData, $_REQUEST['paData']);
      $lo->paData = $laData;
      $llOk =  $lo->omReportePorCentroCosto();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(0);
   }

   function fxReportePorCentroCostoExcel(){
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = array_merge($laData, $_REQUEST['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omReportePorCentroCostoExcel();
      if (!$llOk) {
         fxHeader('Afj2060.php', $lo->pcError);
      } else {
         //print_r($lo->paData);
         fxDocumento($lo->paData);
         fxScreen(0);
      }
   }

   function fxReportePorCentroCostoExcelTotal(){
      $lo = new CControlPatrimonial();
      $llOk =  $lo->omReportePorCentroCostoExcelTotal();
      if (!$llOk) {
         fxHeader('Afj2060.php', $lo->pcError);
      } else {
         //print_r($lo->paData);
         fxDocumento($lo->paData);
         fxScreen(0);
      }
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Afj2060.tpl');
   }
?>
