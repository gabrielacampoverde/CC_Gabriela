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
      fxBuscarActFijEtiquetas();
   } elseif (@$_REQUEST['BotonC2'] == 'Buscar') {
      fxBuscarActFijEtiquetasCodigo();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Id'] == 'CargarCentroResp') {
      faxCargarCentroResp();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') {
      faxCargarEmpleado();
   } elseif (@$_REQUEST['Boton1'] == 'Salir') {
      fxHeader("Afj1050.php");
   } elseif (@$_REQUEST['Boton1'] == 'PDF') {
      fxPrintCodigos();
   } elseif (@$_REQUEST['Boton2'] == 'PDF') {
      fxPrintCodigosOtros();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omInitManImpresionEtiquetas();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos'] = $lo->paDatos;
      $_SESSION['paSituac'] = $lo->paSituac;
      fxScreen(0);
   }

   function fxBuscarActFijEtiquetas() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = array_merge($laData, $_REQUEST['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omBuscarActFijEtiquetas();
      if (!$llOk) {
         fxHeader("Afj1050.php", $lo->pcError);
         return;
      } 
      $_SESSION['paDatos'] = $lo->paDatos;  
      $_SESSION['paData'] = $lo->laData;  
      // print_r($_SESSION['paData']);
      fxScreen(1);
   }

   function fxBuscarActFijEtiquetasCodigo() {
      // $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $laData = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omBuscarActFijEtiquetasCodigo();
      if (!$llOk) {
         fxHeader("Afj1050.php", $lo->pcError);
         return;
      } 
      $_SESSION['paDatos'] = $lo->paDatos;  
      $_SESSION['paData'] = $lo->laData;  
      // print_r($_SESSION['paData']);
      fxScreen(2);
   }

   function fxPrintCodigos() { 
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDatos'];
      $lo->paData = $_SESSION['paData'];
      $llOk = $lo->omPrintCodigos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(1);
   }

   function fxPrintCodigosOtros() { 
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDatos'];
      $lo->paData = $_SESSION['paData'];
      $llOk = $lo->omPrintCodigosOtros();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(2);
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

   function faxCargarEmpleado() {
      $lo = new CControlPatrimonial();
      $lo->paData['p_cCodEmp'] = $_REQUEST['p_cCodEmp'];
      $llOk =  $lo->omCargarEmpleado();
      if (!$llOk) {
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;  
      }
      echo json_encode($lo->paData);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Afj1050.tpl');
   }
?>
