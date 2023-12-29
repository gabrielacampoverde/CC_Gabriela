<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Calcular') {
      fxCalcularDepreciacion();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton'] == 'Contabilizacion') {
      fxContabilizacionDepreciacion();
   } elseif (@$_REQUEST['Id'] == 'ReporteContabilizacion') {
      fxReporteContabilizacion();
   } elseif (@$_REQUEST['Id'] == 'ReporteDepreciacionDetalle') {
      fxReporteDepreDetalle();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CCENCOS'=> '000'];
      // $lo->paData = ['CUSUCOD'=> '0544', 'CCENCOS'=> '000', 'CMODULO'=> '00N'];
      $llOk = $lo->omInitAsientoDepreciacion(); 
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }
   function fxCalcularDepreciacion(){
      $_SESSION['paData'] = $_REQUEST['paData']; 
      $lcPeriod = substr($_REQUEST['paData']['CPERIOD'],0 , 7);  
      // omDepreciacionAcumulada 
      $laData = ['ID'=>'AFJ8003', 'CUSUCOD'=>$_SESSION['GCCODUSU'], 'CPERIOD'=>$lcPeriod, 'DFECHA'=>$_REQUEST['paData']['CPERIOD'] , 'CFLAG'=>'S'];
      $lcParam = json_encode($laData);
      $lcCommand = "python3 ERPWS/Clases/CCntActivoFijo.py '".$lcParam."'";
      // $lcCommand = str_replace('"', '\"', $lcParam);
      // $lcCommand = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand.'"';
      print_r($lcCommand);
      ECHO "<BR>";
      $laArray = fxCargarResultado($lcCommand);
      if (isset($laArray['ERROR'])) {
         $_SESSION['paData'] = $laData;
         fxScreen(1);
         return fxAlert($laArray['ERROR']);
      } 
      // omCalcularDepreciacion
      $laData1 = ['ID'=>'AFJ8001', 'CUSUCOD'=>$_SESSION['GCCODUSU'], 'CPERIOD'=>$lcPeriod, 'CFLAG'=>'S'];
      $lcParam1 = json_encode($laData1);
      $lcCommand1 = "python3 ERPWS/Clases/CCntActivoFijo.py '".$lcParam1."'";
      // $lcCommand1 = str_replace('"', '\"', $lcParam1);
      // $lcCommand1 = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand1.'"';
      // print_r($lcCommand1);
      print_r($lcCommand1);
      $laArray = fxCargarResultado($lcCommand1);
      if (isset($laArray['ERROR'])) {
         $_SESSION['paData'] = $laData;
         fxScreen(1);
         return fxAlert($laArray['ERROR']);
      } 
      fxAlert("DEPRECIACIÓN CALCULADA CORRECTAMENTE");
      fxScreen(0);
   }

   function fxContabilizacionDepreciacion(){
      $_SESSION['paData'] = $_REQUEST['paData']; 
      $lcPeriod = substr($_REQUEST['paData']['CPERIOD'],0 , 7); 
      // print_r($_REQUEST['paData']['CPERIOD']);     
      $laData = ['ID'=>'AFJ8002', 'CUSUCOD'=>$_SESSION['GCCODUSU'], 'CPERIOD'=>$lcPeriod, 'DFECHA'=>$_REQUEST['paData']['CPERIOD']];
      $lcParam = json_encode($laData);
      $lcCommand = "python3 ERPWS/Clases/CCntActivoFijo.py '".$lcParam."'";
      print_r($lcCommand);
      // $lcCommand = str_replace('"', '\"', $lcParam);
      // $lcCommand = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand.'"';
      // print_r($lcCommand);
      // die();
      $laArray = fxCargarResultado($lcCommand);
      if (isset($laArray['ERROR'])) {  
         $_SESSION['paData'] = $laData;
         fxScreen(1);
         return fxAlert($laArray['ERROR']);
      } 
      fxAlert("CONTABILIZACIÓN CALCULADA CORRECTAMENTE");
      fxScreen(0);
   }

   function fxCargarResultado($p_cCommand) {
      $lcData = shell_exec($p_cCommand);
      $laArray = json_decode($lcData, true);
      return $laArray;
   }
   
   function fxReporteContabilizacion() {
      // print_r($_REQUEST);
      $lo = new CControlPatrimonial();
      $lo->paData = ['CPERIOD'=> $_REQUEST['p_cPeriod'], 'CMODULO'=> '00R'];
      $llOk = $lo->omRepContaDepreciacion(); 
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }   
   function fxReporteDepreDetalle() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CPERIOD'=> $_REQUEST['p_cPeriod'], 'CMODULO'=> '00R'];
      $llOk = $lo->omRepContaDepreciacionDetalle(); 
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }
   

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1010.tpl');
      // return;
   }
?>