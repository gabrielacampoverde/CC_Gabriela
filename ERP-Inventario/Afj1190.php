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
      fxScreen(0);
   }
   function fxCalcularDepreciacion(){
      $_SESSION['paData'] = $_REQUEST['paData']; 
      $lcPeriod = $_REQUEST['paData']['CPERIOD'];  
      // omDepreciacionAcumulada 
      $laData = ['ID'=>'AFJ8006', 'CPERIOD'=>$lcPeriod , 'CUSUCOD'=>$_SESSION['GCCODUSU'], 'CFLAG'=>'S'];
      $lcParam = json_encode($laData);
      $lcCommand = "python3 ERPWS/Clases/CCntActivoFijo.py '".$lcParam."'";
      // $lcCommand = str_replace('"', '\"', $lcParam);
      // $lcCommand = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand.'"';
      //print_r($lcCommand);
      $laArray = fxInvocaPython($lcCommand);
      if (isset($laArray['ERROR'])) {
         $_SESSION['paData'] = $laData;
         fxInit();
         return fxAlert($laArray['ERROR']);
      } 
      fxAlert("DEPRECIACIÓN DE INICIO DE AÑO CORRECTAMENTE");
      fxInit();
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1190.tpl');
      // return;
   }
?>