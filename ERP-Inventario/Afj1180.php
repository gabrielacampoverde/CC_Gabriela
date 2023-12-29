<?php
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   
   $loSmarty = new Smarty;
   error_reporting(E_ALL);ini_set('display_errors', 0);

   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'GuardarCuenta') { 
      fxGuardarCuenta();
   } elseif (@$_REQUEST['Boton'] == 'BuscarCuenta') { 
      fxBuscarCuenta();
   } elseif (@$_REQUEST['Boton'] == 'Salir') { 
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }

   function fxInit() {
      $_SESSION['paDatas']  = NULL;
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitCuentaContable();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      } else {
         $_SESSION['paData']  = $lo->paData;   
         $_SESSION['paCenCos'] = $lo->paCenCos;   
         fxScreen(0);
      }
   }

   function fxGuardarCuenta() {
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($_REQUEST['paData'], ['CUSUCOD' => $_SESSION['GCCODUSU']]);
      $lo->paData['CDESCRI'] = strtoupper($lo->paData['CDESCRI']);
      $llOk = $lo->omGuardarCuentaContable();
      if (!$llOk) {
         fxAlert($lo->pcError);
         return;
      } else {
         fxAlert("CUENTA CONTABLE GRABADO CORRECTAMENTE  ".$lo->paData['CCTACNT']);
         $_SESSION['paDatas']  = NULL;
         fxScreen(0);
      }
   }

   function fxBuscarCuenta(){
      $lo = new CControlPatrimonial();
      $lo->paData['CCTACNT'] = $_REQUEST['paData']['CCTACNT'];
      $llOk = $lo->omBuscarCuentaContable();
      if (!$llOk) {  
         fxAlert($lo->pcError);
         $_SESSION['paDatas']  = NULL;
         fxScreen(0);
      }else{
         $_SESSION['paDatas']  = $lo->paDatas; 
         fxScreen(0);
      }
      
   }

   function fxScreen($p_nBehavior) {
	   global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatas', $_SESSION['paDatas']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1180.tpl');
      return;
   }
?>