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
      fxBuscarCodigo();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Id'] == 'reporteOrdCom') { 
      faxReporteOrdCom();
   } elseif (@$_REQUEST['Id'] == 'ReporteActivosFijo') { 
      faxReporteActivosFijo();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxHeader("Afj1090.php");
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omInitMtoCodArt();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paData'] = null;
      fxScreen(0);
   }

   function fxBuscarCodigo() {
      $_SESSION['paData'] = $_REQUEST['paData'];
      $_SESSION['CCODART'] = $_REQUEST['paData'];
      //print_r($_SESSION['CCODART']);
      $laData  = array_merge(['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']], $_SESSION['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omBuscarCodigo();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);
         return;
      }
      $_SESSION['paData'] = $lo->paDatos;
      // print_r($_SESSION['paDatos']);
      fxScreen(1);
   }

   function faxReporteOrdCom() { 
      $lo = new CControlPatrimonial();
      $lo->laData['CIDORDE'] = $_REQUEST['p_cIdOrde']; 
      $llOk = $lo->omReportOCS();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }

   function faxReporteActivosFijo(){
      $laData = ['CASIENT' =>$_REQUEST['p_cAsient'], 'CCODART' => $_SESSION['CCODART']['CCODART']];
      // print_r($laData);
      $lo = new CControlPatrimonial();
      $lo->paData =  $laData ;
      $llOk = $lo->omReporteActivosFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } 
      else {
         echo json_encode($lo->paData);
      }
      
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1090.tpl');
      // return;
   }
?>
