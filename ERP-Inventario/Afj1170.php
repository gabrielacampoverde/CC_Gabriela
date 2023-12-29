<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton0'] == 'Nuevo') {
      fxTranferenciaNueva();
   } elseif (@$_REQUEST['Boton0'] == 'Salir') {
      fxHeader("Afj1002.php");
    } elseif (@$_REQUEST['Boton0'] == 'BuscarTransferencias') {
      fxHeader("Afj1030.php");
   }  elseif (@$_REQUEST['Boton1'] == 'Agregar') { 
      fxAgregarDisfeCenResp();
   }  elseif (@$_REQUEST['Boton1'] == 'Guardar') { 
      fxAgregarLugarTrans();
   }  elseif (@$_REQUEST['Boton1'] == 'Regresar') { 
      fxInit(); 
   } elseif (@$_REQUEST['Id'] == 'CargarCentrosRes') { 
      faxCargarCentroRes();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   } elseif (@$_REQUEST['Boton2'] == 'Transferir') { 
      fxGuardarDisfeCenResp();
   } elseif (@$_REQUEST['Boton2'] == 'TransferirSinEmail') { 
      fxGuardarDisfeCenRespSinEmail();
   } elseif (@$_REQUEST['Id'] == 'EliminarActFij') {
      fxEliminarActFij();
   } elseif (@$_REQUEST['Id'] == 'ReporteTranf') {
      faxReporteTranf();   
   } elseif (@$_REQUEST['BotonM'] == 'CambiarDescripcion') {
      faxCambiarDescripcion();
   } elseif(@$_REQUEST['Id'] == 'EliminarTransferencia'){
      faxEliminarTransferencia();
   }  else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitMantTrans();
      if (!$llOk) {
         fxHeader("Afj1002.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = null;
      $_SESSION['paData'] = null;
      $_SESSION['pcEmpNom'] = null;
      $_SESSION['pcNomEmp'] = null;
      $_SESSION['paCenCos'] = $lo->paDatos['ACENCOS'];
      $_SESSION['paCenRes'] = $lo->paDatos['ARESPON'];
      $_SESSION['pcOpc'] = null;
      $_SESSION['paDato'] = $lo->paDato; 
      $_SESSION['paData'] = $lo->paData;
      $_SESSION['paDatos4'] =  null;
      fxScreen(0);
   }


   function fxTranferenciaNueva(){
      fxScreen(1);
   }

   function faxReporteTranf(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CIDTRNF'=> $_REQUEST['pcIdTrnf']];
      $llOk = $lo->omPrintTransferenciaPDF();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } 
      echo json_encode($lo->paData);
      //print_r($lo->paData);
   }

   function fxEliminarActFij(){
      $lnIndice = $_REQUEST['pnIndice'];
      //print_r($lnIndice);
      array_splice($_SESSION['paDatos'], $lnIndice - 1,1);
      
      //echo json_encode($_SESSION['paDatos']);
      fxScreen(1);
   }

   function fxAgregarDisfeCenResp(){
      $laData = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData['CCODIGO'] = $_REQUEST['paData']['CCODIGO'];
      $llOk = $lo->omAgregarDisfCenResp();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(1);
         return;
      } 
      $_SESSION['paDatos'][] = $lo->paData;
      $_SESSION['paDatos'] = $_SESSION['paDatos'];
      // $_SESSION['paData'] = $laData;
      // print_r($_SESSION['paData']); 
      fxScreen(1);
   }
   
   function fxAgregarLugarTrans(){
      fxScreen(2);
   }

   function fxGuardarDisfeCenResp(){
      $laData = $_REQUEST['paData'];
      $laData['CDESCRI'] = strtoupper($laData['CDESCRI']);
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU']], $laData );
      $lo->paDatos = $_SESSION['paDatos'];
      $llOk = $lo->omGuardarDisfeCenResp();
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

   function fxGuardarDisfeCenRespSinEmail(){
      $laData = $_REQUEST['paData'];
      $laData['CDESCRI'] = strtoupper($laData['CDESCRI']);
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU']], $laData );
      $lo->paDatos = $_SESSION['paDatos'];
      $llOk = $lo->omGuardarDisfeCenResp();
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

   function faxCargarCentroRes() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      $llOk =  $lo->omCargarCentroResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      echo json_encode($lo->paDatos);

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

   function faxCambiarDescripcion(){
      // print_r($_REQUEST);
      $lo = new CControlPatrimonial();
      $lo->paData = $_REQUEST['paData'];
      $llOk = $lo->omCambiarDescripcion();
      if (!$llOk) {
         echo json_encode(['ERROR' => $lo->pcError]);
         return;
      }
      fxInit();
   }


   function faxEliminarTransferencia(){
      $lo = new CControlPatrimonial();
      $lo->paData['CIDTRNF'] = $_REQUEST['pcIdTrnf'];
      $llOk = $lo->omEliminarTransferencia();
      if (!$llOk) {
         echo json_encode(['ERROR' => $lo->pcError]);
         return;
      }
      fxInit();
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1170.tpl');
      // return;
   }
?>
