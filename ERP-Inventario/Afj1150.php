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
   } elseif (@$_REQUEST['Boton'] == 'GuardarTipoAct') { 
      fxGuardarTipoAct();
   } elseif (@$_REQUEST['Id'] == 'BuscarTipoActivo') { 
      fxBuscarTipoActivo();
   } elseif (@$_REQUEST['Boton'] == 'Salir') { 
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitDatosTipAct();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      } else {
         $_SESSION['paData']  = $lo->paData;   
         $_SESSION['paDatEst'] = $lo->paDatEst;   
         $_SESSION['paDatCla'] = $lo->paDatCla;   
         fxScreen(0);
      }
   }

   function fxGuardarTipoAct() {
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($_REQUEST['paData'], ['CUSUCOD' => $_SESSION['GCCODUSU']]);
      $lo->paData['CDESCRI'] = strtoupper($lo->paData['CDESCRI']);
      $llOk = $lo->omGuardarTipoActivo();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      } else {
         fxAlert("TIPO DE ACTIVO FIJO GRABADO CORRECTAMENTE  ".$lo->paData['CTIPAFJ']);
         fxInit();
      }
   }

   function fxBuscarTipoActivo(){
      $lo = new CControlPatrimonial();
      $lo->paData['CTIPAFJ'] = $_REQUEST['pcTipAfj'];
      $llOk = $lo->omBuscarTipoActFij();
      if (!$llOk) {  
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;
      } 
      $lo->paData['paData'] = $lo->paData;
      $lo->paData['paDatos'] = $_SESSION['paDatCla'];
      echo json_encode($lo->paData);
   }

   function fxScreen($p_nBehavior) {
	   global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('saDatEst', $_SESSION['paDatEst']);
      $loSmarty->assign('saDatCla', $_SESSION['paDatCla']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1150.tpl');
      return;
   }
?>