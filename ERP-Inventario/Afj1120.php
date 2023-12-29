<?php
   error_reporting(E_ALL);ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Id'] == 'ReporteBaja') {
      fxReporteBaja();
   } elseif (@$_REQUEST['Id'] == 'DarBajaActivos') {
      fxDarBajaActivos();
   } elseif (@$_REQUEST['Boton'] == 'RevisarBaja') {
      fxRevisarBaja();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxInit();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitMantBajas();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }

   function fxReporteBaja() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENRES'=> $_REQUEST['p_cCenRes']];
      // print_r($lo->paData);
      $llOk = $lo->omReporteDetalleCenResp();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         // print_r($lo->paData);
         echo json_encode($lo->paData);
      }
   }
   function fxRevisarBaja(){
      // $lo->paData = ['CCENRES'=> $_REQUEST['CCENRES']];
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($_REQUEST, ['CUSUCOD' => $_SESSION['GCCODUSU']]);
      $llOk = $lo->omRevisarBaja();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);  
         return;
      }
      $_SESSION['paData'] = $lo->paData;
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(1);
   }
   
   function fxDarBajaActivos_() {
      $laData = array_merge($_REQUEST['paData'], $_SESSION['paData']) ;
      $laDatos = $_SESSION['paDatos'];
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($laData, ['CUSUCOD' => $_SESSION['GCCODUSU']]);
      $lo->paDatos = $laDatos;
      $llOk = $lo->omDarBaja();
      if (!$llOk) {
         fxAlert($lo->pcError);  
         fxScreen(0);   
         return;
      }
      $_SESSION['paDatos'] = $_SESSION['paDatos1'];
      fxScreen(0);
   }
   function fxDarBajaActivos() {
      $laDatos = $_SESSION['paDatos'];
      $laData = array_merge($_REQUEST['paData'], $_SESSION['paData'], ['CUSUCOD' => $_SESSION['GCCODUSU']]);
      // print_r($laData);
      $lcDocBaj = strtoupper($laData['CDOCBAJ']);
      // print_r($laData);
      $laData = ['ID'=>'AFJ8004', 'CUSUCOD'=>$_SESSION['GCCODUSU'], 'CCENRES'=>$laData['CCENRES'], 'CDOCBAJ'=>$lcDocBaj, 'DFECBAJ'=>$laData['DFECBAJ']];
      $lcParam = json_encode($laData);
      $lcCommand = "python3 ERPWS/Clases/CCntActivoFijo.py '".$lcParam."'";
      // $lcCommand = str_replace('"', '\"', $lcParam);
      // $lcCommand = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand.'"';
      // print_r($lcCommand);
      $laArray = fxInvocaPython($lcCommand);
      // print_r($laArray);
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         fxInit();
      }
      fxDocumento($laArray['CFILE2']);
      fxInit();
   }

   function fxCargarResultado($p_cCommand) {
      $lcData = shell_exec($p_cCommand);
      $laArray = json_decode($lcData, true);
      return $laArray;
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1120.tpl');
      // return;
   }
?>
