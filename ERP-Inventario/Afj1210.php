<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Id'] == 'ReporteTranf') {
      fxReporteTranf();
   } elseif (@$_REQUEST['Boton0'] == 'Nuevo') {
      fxNuevaTransferenciaInformatica();
   } elseif (@$_REQUEST['Boton0'] == 'Salir') {
      fxHeader("Afj1002.php");
   } elseif (@$_REQUEST['Boton1'] == 'Siguiente') {
      fxSiguienteTransferencia();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxInit();
   } elseif (@$_REQUEST['Boton1'] == 'Agregar') {
      fxAgregarActFijInformatica();
   } elseif (@$_REQUEST['Boton2'] == 'Transferir') {
      fxTranferenciaInformatica();
   } elseif (@$_REQUEST['Boton2'] == 'Regresar') {
      fxRegresarP1();
   } elseif (@$_REQUEST['Id'] == 'EliminarActFij') {
      fxEliminarActFij();
   } elseif (@$_REQUEST['Id'] == 'CargarCentrosRes') { 
      faxCargarCentroRes();
   } elseif (@$_REQUEST['Id'] == 'CargarCentrosRes1') { 
      faxCargarCentroRes1();
   }  elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   }  else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitMantTransRedes();
      $_SESSION['paCenCos'] = $lo->paDatos['ACENCOS'];
      $_SESSION['paCenRes'] = $lo->paDatos['ARESPON'];
      $_SESSION['paDato'] = $lo->paDato; 
      fxScreen(0);
   }

   function fxReporteTranf() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CIDTRNF'=> $_REQUEST['pcIdTrnf']];
      $llOk = $lo->omReporteTransInformatica();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }

   function fxNuevaTransferenciaInformatica(){
      $_SESSION['paDatos'] = null;
      fxScreen(1);
   }

   function fxAgregarActFijInformatica(){
      $laData = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omBuscarActivoFijoInformatica();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(1);
         return;
      } 
      $_SESSION['paDatos'][] = $lo->paDatos;
      $_SESSION['paDatos'] = $_SESSION['paDatos'];
      // print_r($_SESSION['paDatos1']);
      fxScreen(1);
   }

   function fxRegresarP1(){
      $_SESSION['paDatos'] = $_SESSION['paDatos'];
      fxScreen(1);
   }

   function fxSiguienteTransferencia(){
      $_SESSION['pcOpc'] = 'Nuevo';
      $laDatos = $_SESSION['paDatos'];
      $laData = ['CIDTRNF'=> '*'];
      fxScreen(2);
   }

   function fxTranferenciaInformatica(){
      $laDatos = $_SESSION['paDatos']; 
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU']], $_REQUEST['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $llOk = $lo->omTransferenciaInformatica();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(2);
         return;
      }else{
         fxAlert("TRANFERENCIA REALIZADA CORRECTAMENTE");
         fxInit();
      }
   }

   function fxEliminarActFij(){
      $lnIndice = $_REQUEST['pnIndice'];
      //print_r($lnIndice);
      array_splice($_SESSION['paDatos'], $lnIndice - 1,1);
      
      //echo json_encode($_SESSION['paDatos']);
      fxScreen(1);
   }
   
   function faxCargarCentroRes() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      $llOk =  $lo->omCargarCentroResponsabilidadSinActivos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      $_SESSION['paCenRes1'] = $_SESSION['paCenRes'];
      $_SESSION['paCenRes'] =null;
      $_SESSION['paCenRes']['LADATOS'] = $lo->paDatos;
      $_SESSION['paCenRes']['LADATA'] = $_SESSION['paData'];
      // print_r($_SESSION['paCenRes']);
      // die();
      echo json_encode($_SESSION['paCenRes']);

   }

   function faxCargarCentroRes1() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos1']];
      $llOk =  $lo->omCargarCentroResponsabilidadSinActivos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      $_SESSION['paCenRes'] = $lo->paDatos;
      $_SESSION['paCenRes'] =null;
      $_SESSION['paCenRes']['LADATOS'] = $lo->paDatos;
      $_SESSION['paCenRes']['LADATA'] = $_SESSION['paData'];
      // print_r($_SESSION['paCenRes']);
      echo json_encode($_SESSION['paCenRes']);
   }

   function faxBuscarEmpleado() {
      $lo = new CControlPatrimonial();
      $lo->paData['CCRIBUS'] = strtoupper($_REQUEST['pcCriBusDes']);
      $llOk = $lo->omBuscarEmpleado();
      if (!$llOk) {
         echo json_encode(['ERROR' => $lo->pcError]);
         return;
      }
      echo json_encode($lo->paDatos);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      // print_r($_SESSION['paDatos']);
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saNomEmp', $_SESSION['pcNomEmp']);
      $loSmarty->assign('saEmpNom', $_SESSION['pcEmpNom']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1210.tpl');
      // return;
   }
?>
