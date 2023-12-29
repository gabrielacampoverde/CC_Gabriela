<?php
   error_reporting(E_ALL);ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') { 
      fxBuscarTransferenciasApro();
   } elseif (@$_REQUEST['Id'] == 'ReporteTranf') {
      fxReporteTranf();
   } elseif (@$_REQUEST['Id'] == 'Conformidad') {
      fxConformidad();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitMantTransConformidad();
      if (!$llOk) {
         // fxHeader("Mnu1000.php", $lo->pcError);
         fxScreen(0);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }

   function fxReporteTranf() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CIDTRNF'=> $_REQUEST['pcIdTrnf']];
      $llOk = $lo->omReporteTrans();
      if (!$llOk) {
         echo json_esncode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }

   function fxConformidad() {
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($_REQUEST['paData'], ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']]);
      $llOk = $lo->omConformidadTranferencia();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);  
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1040.tpl');
      // return;
   }
?>
