<?php
   // -------------------------------------------
   // DEVOLUCIÓN DE DINERO SOLICITUD ALUMNO UCSM
   // 2020-10-14 APR Creacion
   // -------------------------------------------
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   
   $loSmarty = new Smarty;
   error_reporting(E_ALL);ini_set('display_errors', 0);

   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') { 
      fxBuscarActivoFijo();
   } elseif (@$_REQUEST['Boton'] == 'Salir') { 
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton1'] == 'Baja') { 
      fxBajaActivoFijo();
   } elseif (@$_REQUEST['Boton1'] == 'Salir') { 
      fxInit();
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omMantBajaActivoFijo();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      } else {
         $_SESSION['paEstAct'] = $lo->paData['AESTACT'];
         $_SESSION['paSituac'] = $lo->paData['ASITUAC'];
         $_SESSION['paDatClaAfj'] = $lo->paData['CCODCLA'];
         $_SESSION['paCenCos'] = $lo->paData['CCENCOS'];
         $_SESSION['paTipAfj'] = $lo->paData['CTIPAFJ'];  
         fxScreen(0);
      }
   }

   function fxBuscarActivoFijo() {
      $lo =  new CControlPatrimonial();
      $lo->paData = $_REQUEST['paData'];
      // unset($lo->paData['CCENCOS']);
      $llOk = $lo->omCargarActivoFijo();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);
         return;
      }
      $_SESSION['paCenRes'] = $lo->paData['ACENRES'];
      unset($lo->paData['ACENRES']);
      $_SESSION['paData'] = $lo->paData;
      $_SESSION['paDepre'] = $lo->paDatos;
      $_SESSION['paDato'] = $lo->laData;
      $i = 0;
      if ($_SESSION['paData']['CSITUAC'] == 'B') {
         fxAlert("ACTIVO NO SE ENCONTRO O ESTA DE BAJA");
         fxScreen(0);
      } else {
         fxScreen(1);
      }
   }

   function fxBajaActivoFijo(){
      $laDatos = $_SESSION['paDatos'];
      $laData = array_merge($_REQUEST['paData'], $_SESSION['paData'], ['CUSUCOD' => $_SESSION['GCCODUSU']]);
      //print_r($laData);
      $lcDocBaj = strtoupper($laData['CDOCBAJ']);
      $laData = ['ID'=>'AFJ8005', 'CUSUCOD'=>$_SESSION['GCCODUSU'], 'CACTFIJ'=>$laData['CACTFIJ'], 'CDOCBAJ'=>$lcDocBaj, 'DFECBAJ'=>$laData['DFECBAJ']];
      $lcParam = json_encode($laData);
      $lcCommand = "python3 ERPWS/Clases/CCntActivoFijo.py '".$lcParam."' 2>&1";
      //print_r($lcCommand);
      //echo "<br>";
      //$lcCommand = str_replace('"', '\"', $lcParam);
      //$lcCommand = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand.'"';
      $laArray = fxInvocaPython($lcCommand);
      //print_r($laArray);
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
      }else {
         fxDocumento($laArray['CFILE2']);
      }
      $_SESSION['paData'] = array_merge($_SESSION['paData'], $_REQUEST['paData'] );
      $_SESSION['paDepre'] = $_SESSION['paDepre'];
      $_SESSION['paDato'] = $_SESSION['paDato'];
      fxScreen(0);
   }

   function fxScreen($p_nBehavior) {
	   global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('saEstAct', $_SESSION['paEstAct']);
      $loSmarty->assign('saDatClaAfj', $_SESSION['paDatClaAfj']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saDepre', $_SESSION['paDepre']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1160.tpl');
      return;
   }
?>