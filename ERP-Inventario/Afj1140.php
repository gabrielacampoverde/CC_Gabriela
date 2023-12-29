<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   // Mantenimiento Centro Responsable
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton'] == 'Buscar') {
      fxBuscarCentrosResponsabilidad();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleadoInventario') { 
      faxBuscarEmpleadoInventario();
   } elseif (@$_REQUEST['Id'] == 'BuscarActFijInventario') { 
      faxBuscarActFijInventario();
   } elseif (@$_REQUEST['Boton1'] == 'GrabarInventario') {
      fxGrabarInventario();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxRegresar();
   } elseif (@$_REQUEST['BotonM'] == 'Enviar') {
      fxEnviarFinInventario();
   } elseif (@$_REQUEST['Id'] == 'ReporteInventario') { 
      faxReporteInventario();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData['CUSUCOD'] =  $_SESSION['GCCODUSU'];
      $lo->paData['CCENCOS'] =  $_SESSION['GCCENCOS'];
      $llOk = $lo->omInitMntoInventario();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos']  = $lo->laDatos;
      $_SESSION['paData'] = null;
      fxScreen(0);
   }
   
   function fxBuscarCentrosResponsabilidad(){
      $_SESSION['pcYear'] = $_REQUEST['paData']['CYEAR'];
      $_SESSION['paData0'] = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData =  $_REQUEST['paData'];
      $llOk = $lo->omBuscarCentrosResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      }
      $_SESSION['paData']  = $lo->paDatos;
      fxScreen(0); 
   }

   function faxBuscarEmpleadoInventario(){
      $lcCenRes = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData =  $lcCenRes;
      $llOk = $lo->omBuscarEmpleadoInventario();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      }
      $_SESSION['paDatas']  = $lo->paDatos;
      $_SESSION['paData']  = $_REQUEST['paData'];
      fxScreen(1);  
   }
   function faxBuscarActFijInventario(){
      $lcCenRes = $_REQUEST['paData'];
      $_SESSION['CCODEMP'] = $_REQUEST['paData']['CCODEMP'];
      $lo = new CControlPatrimonial();
      $lo->paData =  $lcCenRes;
      $llOk = $lo->omBuscarActFijInventario();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      }
      $_SESSION['paDatos']  = $lo->paDatos;
      $_SESSION['paData']  = $lo->paData;
      //print_r($_SESSION['paData']);
      fxScreen(2);  
   }

   function fxGrabarInventario(){
      $lcActFij = $_REQUEST ['ACodAct'];
      $lo = new CControlPatrimonial();
      $lo->paData =  $lcActFij;
      $llOk = $lo->omGrabarInventario();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      }
      $_SESSION['paDatos']  = $lo->paDatos;
      $_SESSION['paData']  = $_REQUEST['paData'];
      // print_r($_SESSION['paDatos']);
      fxRegresar();  
   }

   function fxEnviarFinInventario(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CYEAR' => $_SESSION['pcYear'], 'CCODEMP' =>$_SESSION['CCODEMP']] + $_REQUEST['paData'];
      $llOk = $lo->omEnviarReporteInventario();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      }
      $_SESSION['paDatos']  = $lo->paDatos;
      $_SESSION['paDato']  = $lo->paDato;
      $_SESSION['paData']  = $_REQUEST['paData'];
      fxRegresar();  
   }

   function fxRegresar() {
      $_SESSION['paData'] = $_SESSION['paData0'];
      $lo = new CControlPatrimonial();
      $lo->paData = $_SESSION['paData0'];
      $llOk = $lo->omBuscarCentrosResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();
      }
      $_SESSION['paData']  = $lo->paDatos;
      fxScreen(0); 
   }

   function faxReporteInventario(){
      $lcpaData = ['CYEAR' => $_SESSION['pcYear'], 'CCODEMP' => $_REQUEST['p_nCodEmp']];
      $lcpaData['CREPORT'] = 'Docs/Inventario/'.$lcpaData['CYEAR'].'/I'.$lcpaData['CCODEMP'].'.pdf';
      echo json_encode($lcpaData);
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
   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('scNombre', $_SESSION['GCNOMBRE']);
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saDatas', $_SESSION['paDatas']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Afj1140.tpl');
   }
?>
