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
      fxBuscarActivoPorEmpleado();
   } elseif (@$_REQUEST['Boton'] == 'Salir') { 
      fxHeader("Afj1002.php");
   } elseif (@$_REQUEST['Boton1'] == 'IngresarResponsable') { 
      fxIngresarDestino();
   } elseif (@$_REQUEST['Boton2'] == 'Transferir') { 
      fxTransferir();
   } elseif (@$_REQUEST['Boton2'] == 'TransferirSinEmail') { 
      fxTransferirSinEmail();
   } elseif (@$_REQUEST['Boton3'] == 'CambiarEmpleado') { 
      fxCambiarEmpleadoCentroResp();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   } elseif (@$_REQUEST['Id'] == 'CargarEmpleado') { 
      faxCargarEmpleado();
   } elseif (@$_REQUEST['Id'] == 'CargarCentroResp') { 
      faxCargarCentroResp();
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omInitManRep();
      if (!$llOk) {
         fxHeader("Afj1002.php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos'] = $lo->paDatos;
      fxScreen(0);
   }

   function fxBuscarActivoPorEmpleado() {
      $laData =  $_REQUEST['paData'];
      $laData1 =  $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk =  $lo->omBuscarActivoPorEmpleado();
      if (!$llOk) {
         fxHeader("Afj1130.php", $lo->pcError);
         return;
      } 
      $_SESSION['paDatos'] = $lo->paDatos;  
      $_SESSION['paDato'] = $lo->paData;  
      $_SESSION['paData'] = $laData1;   
      fxScreen(1);
   }

   function fxIngresarDestino(){
      $_SESSION['paDatos'] = $_REQUEST ['ACodAct'];
      fxScreen(2);
   }
   function fxTransferir() {
      $laData =  $_REQUEST['paData'] + $_SESSION['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU']], $laData );
      $lo->paDatos = $_SESSION['paDatos'];   
      $llOk =  $lo->omTransferencias();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(1);
         return;
      }else{
         $llOk = $lo->omEnviarEmailConformidad();
         fxInit();
         return;
      }
   }

   
   function fxTransferirSinEmail() {
      $laData =  $_REQUEST['paData'] + $_SESSION['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU']], $laData );
      $lo->paDatos = $_SESSION['paDatos'];   
      $llOk =  $lo->omTransferencias();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(1);
         return;
      }else{
         $llOk = $lo->omGenerarTransferenciaPDF();
         fxInit();
         return;
      }
   }

   function fxCambiarEmpleadoCentroResp() {
      $laData =  $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $_SESSION['paDatos'];
      $llOk =  $lo->omCambiarEmpleadoResponsable();
      if (!$llOk) {
         fxHeader("Afj1130.php", $lo->pcError);
         return;
      } 
      fxAlert("SE REALIZO ACTUALIZACIÓN DE EMPLEADO CORRECTAMENTE");
      fxScreen(0);
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

   function faxCargarCentroResp() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      $llOk =  $lo->omCargarCentroResponsabilidad_Destino();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      $_SESSION['paCenRes'] = $lo->paDatos;
      echo json_encode($_SESSION['paCenRes']);
   }
 
   function faxCargarEmpleado() {
      $lo = new CControlPatrimonial();
      $lo->paData['CCENRES'] = strtoupper($_REQUEST['pcCenRes']);
      $llOk = $lo->omCargarEmpleadoCenREs();
      if (!$llOk) {
         echo json_encode(['ERROR' => $lo->pcError]);
         return;
      }
      echo json_encode($lo->paDatos);
   }
   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Afj1130.tpl');
   }
?>
