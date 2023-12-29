<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
    if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') {
      fxBuscarAFAnalisis();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Id'] == 'CargarCentroResp') {
      faxCargarCentroResp();
   } elseif (@$_REQUEST['Id'] == 'ListarTipos') {
      faxListarTipos();
   } elseif (@$_REQUEST['Id'] == 'ListarTiposFin') {
      faxListarTiposFin();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxInit();
   } elseif (@$_REQUEST['Boton1'] == 'PDF') {
      fxRepAnalisisPDF();
   } elseif (@$_REQUEST['Boton1'] == 'EXCEL') {
      fxRepAnalisisActFijExcel();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omInitManRep();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos'] = $lo->paDatos;
      $_SESSION['paDatCla'] = $lo->paDatClaAfj;
      $_SESSION['paSituac'] = $lo->paSituac;
      $_SESSION['paTipAfj'] = $lo->paTipAfj;
      fxScreen(0);
   }

   function fxBuscarAFAnalisis() {
      // print_r($_REQUEST['paData']);
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = array_merge($laData, $_REQUEST['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omBuscarAFAnalisis();
      if (!$llOk) {
         fxHeader("Afj2100.php", $lo->pcError);
         fxScreen(0);
         // return;
      } 
      $_SESSION['paDatos'] = $lo->paDatos;
      $_SESSION['paData'] = $lo->laData; 
      //print_r($_SESSION['paDatos']);
      fxScreen(1);
   }

   function fxRepAnalisisPDF() { 
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDatos'];
      $lo->paData = $_SESSION['paData'];
      $llOk = $lo->omRepAnalisisPDF();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(1);
   }

   function fxRepAnalisisActFijExcel(){
      $laDatos = $_SESSION['paDatos'];
      $laData = $_SESSION['paData'];
      $lo = new CControlPatrimonial();
      $lo->paDatos = $laDatos;
      $lo->paData = $laData;
      $llOk =  $lo->omRepAnalisisActFijExcel();
      if (!$llOk) {
         fxHeader('Afj2100.php', $lo->pcError);
      } else {
         //print_r($lo->paData);
         fxDocumento($lo->paData);
         fxScreen(1);
      }
   }

   function faxCargarCentroResp() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      $llOk =  $lo->omCargarCentroResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      $_SESSION['paCenRes'] = $lo->paDatos;
      echo json_encode($_SESSION['paCenRes']);
   }


   function faxListarTipos() {   
      $lo = new CControlPatrimonial();
      $lo->paData['CCLASE'] = $_REQUEST['pcClase'];
      $llOk = $lo->omTiposActivoFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
         return;
      } 
      $_SESSION['paDatos']['LADATOS'] = $_SESSION['paTipAfj'];
      $_SESSION['paDatos']['LADATA'] =  $lo->paDatos;
      // print_r($_SESSION['paTipAfj']);
      echo json_encode($_SESSION['paDatos']);
   }

   function faxListarTiposFin() {   
      $lo = new CControlPatrimonial();
      $lo->paData['CCLASE'] = $_REQUEST['pcClaseFin'];
      $llOk = $lo->omTiposActivoFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
         return;
      } 
      $_SESSION['paDatos']['LADATOS'] = $_SESSION['paTipAfj'];
      $_SESSION['paDatos']['LADATA'] =  $lo->paDatos;
      // print_r($_SESSION['paTipAfj']);
      echo json_encode($_SESSION['paDatos']);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saDatCla', $_SESSION['paDatCla']);
      $loSmarty->assign('saTipAfj', $_SESSION['paTipAfj']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Afj2100.tpl');
   }
?>
