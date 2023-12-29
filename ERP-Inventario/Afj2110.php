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
      fxBuscarAF();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Id'] == 'CargarCentroResp') {
      faxCargarCentroResp();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   } elseif (@$_REQUEST['Id'] == 'ListarTipos') {
      faxListarTipos();
   } elseif (@$_REQUEST['Id'] == 'ListarTiposFin') {
      faxListarTiposFin();
   } elseif (@$_REQUEST['Boton1'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton1'] == 'EXCEL') {
      faxRepCaracteristicasEXCEL();
   } elseif (@$_REQUEST['Boton1'] == 'PDF') {
      faxRepCaracteristicasPDF();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omInitMantInventario();
      if (!$llOk) {
         fxHeader("Mnu1000 .php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos'] = $lo->paDatos;
      $_SESSION['paDatCla'] = $lo->paDatClaAfj;
      $_SESSION['paSituac'] = $lo->paSituac;
      $_SESSION['paTipAfj'] = $lo->paTipAfj;
      // print_r($_SESSION['paTipAfj']);
      fxScreen(0);
   }

   function fxBuscarAF() {
      $_SESSION['laData'] = $_REQUEST['paData']['CINDICA'];
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = array_merge($laData, $_REQUEST['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omBuscarActInventario();
      if (!$llOk) {
         fxHeader("Afj2110.php", $lo->pcError);
         return;
      } 
      $_SESSION['paDatos'] = $lo->paDatos;
      $_SESSION['paData'] = $lo->laData; 
      // print_r($_SESSION['paData']);    
      fxScreen(1);
   }

   function faxRepCaracteristicasPDF() { 
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDatos'];
      $lo->paData = $_SESSION['paData'];
      $lo->laData = $_SESSION['laData'];
      $llOk = $lo->omRepInventarioPDF();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paDatas['CREPORT']);
         // echo json_encode($lo->paData);
      }
      fxScreen(1);
   }

   function faxRepCaracteristicasEXCEL() {
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDatos'];
      $llOk = $lo->omRepInventarioEXCEL();
      if (!$llOk) {
         fxHeader('Afj2070.php', $lo->pcError);
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

   function faxBuscarEmpleado() {
      $lo = new CControlPatrimonial();
      $lo->paData['CCRIBUS'] = strtoupper($_REQUEST['pcCriBus']);
      $llOk = $lo->omBuscarEmpleado();
      if (!$llOk) {
         echo json_encode(['ERROR' => $lo->pcError]);
         return;
      }
      echo json_encode($lo->paDatos);
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
      $loSmarty->display('Plantillas/Afj2110.tpl');
   }
?>
