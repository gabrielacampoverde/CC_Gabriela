<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Id'] == 'Editar') {
      faxEditarTransferencia();
   } elseif (@$_REQUEST['Boton0'] == 'Buscar') {
      fxBuscarTransferencias();
   } elseif (@$_REQUEST['Id'] == 'ReporteTranf') {
      fxReporteTranf();
   } elseif (@$_REQUEST['Boton0'] == 'Nuevo') {
      fxTranferenciaNueva();
   } elseif (@$_REQUEST['Boton0'] == 'Salir') {
      fxHeader("Afj1002.php");
   } elseif (@$_REQUEST['Boton1'] == 'Aceptar') {
      fxAceptar();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxRegresarInit();
   } elseif (@$_REQUEST['Boton2'] == 'Nuevo') {
      fxNuevoAF();
   } elseif (@$_REQUEST['Boton2'] == 'NuevoVarios') {
      fxNuevaTransferenciaVarios();
   } elseif (@$_REQUEST['Boton2'] == 'Salir') {
      fxRegresarInit();
   } elseif (@$_REQUEST['Boton2'] == 'Transferir') {
      fxGrabarTransferenciasAF();
   } elseif (@$_REQUEST['Id'] == 'Enviar') {
      fxEnviarTransferenciasAF();
   } elseif (@$_REQUEST['Boton3'] == 'Regresar') {
      fxRegresarP2();
   } elseif (@$_REQUEST['Boton3'] == 'Guardar') {
      fxGuardarActivos();
   } elseif (@$_REQUEST['Boton3'] == 'Agregar') {
      fxAgregarActivo();
   } elseif (@$_REQUEST['Boton3'] == 'PDFActivos') {
      fxGenerarPDF();
   } elseif (@$_REQUEST['Boton3'] == 'Eliminar') {
      fxEliminarActFij();
   } elseif (@$_REQUEST['Boton4'] == 'AgregarVarios') {
      fxAgregarActivoVarios();
   } elseif (@$_REQUEST['Boton4'] == 'PDFActivos') {
      fxGenerarPDFVarios();
   } elseif (@$_REQUEST['Boton4'] == 'Eliminar') {
      fxEliminarActFijVarios();
   } elseif (@$_REQUEST['Boton4'] == 'Guardar') {
      fxGuardarActivosVarios();
   } elseif (@$_REQUEST['Id'] == 'CargarCentrosRes') { 
      faxCargarCentroRes();
   } elseif (@$_REQUEST['Id'] == 'CargarCentrosRes1') { 
      faxCargarCentroRes1();
   } elseif (@$_REQUEST['Id'] == 'CargarEmpleado') { 
      faxCargarEmpleado();
   }  elseif (@$_REQUEST['Id'] == 'CargarEmpleadoDes') { 
      faxCargarEmpleadoDes();
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
      $_SESSION['paDatos4'] =  null;
      fxScreen(0);
   }

   function fxBuscarTransferencias(){
      $_SESSION['paData0'] = $_REQUEST['paData'];
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo =  new CControlPatrimonial();
      $lo->paData = array_merge($laData, $_REQUEST['paData']);
      // print_r($lo->paData);
      $llOk = $lo->omBuscarTransferencias();
      if(!$llOk){
         fxAlert($lo->pcError);
      }
      // print_r($lo->paDatos);
      $_SESSION['paDatos'] = $lo->paDatos;  
      fxScreen(0);
   }

   function fxReporteTranf() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CIDTRNF'=> $_REQUEST['pcIdTrnf']];
      $llOk = $lo->omReporteTrans();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }

   function faxEditarTransferencia(){
      $_SESSION['paDatos'] =$_SESSION['paDato'];
      $lnIndice = $_REQUEST['pnIndice'];
      $lcIdTrnf = $_SESSION['paDatos'][$lnIndice]['CIDTRNF'];
      $lo =  new CControlPatrimonial();
      $lo->paData['CIDTRNF'] = $_SESSION['paDatos'][$lnIndice]['CIDTRNF'];
      $llOk = $lo->omEditarTransferencias();
      if(!$llOk){
         fxAlert($lo->pcError);
      }
      $_SESSION['paData'] = $lo->paData;
      $_SESSION['paDatos3'] = $lo->paDatos;
      $_SESSION['pcOpc'] = 'Editar';
      fxScreen(1);
   }

   function fxRegresarInit(){
      $_SESSION['paDatos'] = $_SESSION['paDatos'];
      fxScreen(0);
   }

   function fxTranferenciaNueva(){
      $_SESSION['pcOpc'] = 'Nuevo';
      $laData = ['CIDTRNF'=> '*'];
      $_SESSION['paData'] = $laData;
      fxScreen(1);
   }

   function fxNuevoAF(){
      $_SESSION['paDatos1'] = null;
      $_SESSION['paData'] = $_SESSION['paData'];
      fxScreen(3);
   }

   function fxGenerarPDF(){
      $lo = new CControlPatrimonial();
      $lo->paData = $_SESSION['paData'];
      $llOk =  $lo->omReporteCentroResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(3);  
   }

   function fxEliminarActFij(){
      $lcIndice = $_REQUEST['paData']['cCodigo'];
      $lcIndice = $lcIndice -1;
      unset($_SESSION['paDatos1'][$lcIndice]);
      fxScreen(3);
   }

   function fxAgregarActivo(){ 
      $laData = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($_SESSION['paData'], $laData);
      $llOk = $lo->omBuscarActivoFijoTrans();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(3);
         return;
      } 
      $_SESSION['paDatos1'][] = $lo->paDatos;
      $_SESSION['paDatos1'] = $_SESSION['paDatos1'];
      // print_r($_SESSION['paDatos1']);
      fxScreen(3);
   }

   function fxAceptar(){
      $laData = $_REQUEST['paData'];
      unset($laData['CDESCOS']);
      unset($laData['CDESCCO']);
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omCargarDatos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      if ($_SESSION['pcOpc'] == 'Nuevo'){
         $_SESSION['paDatos3'] = [];
      } elseif($_SESSION['pcOpc'] == 'Editar'){
         $_SESSION['paDatos3'] = $_SESSION['paDatos3'];
      }
      $_SESSION['paData'] = array_merge($lo->paData, $laData);
      fxScreen(2);  
   }

   function fxRegresarP2(){
      $_SESSION['paData'] = $_SESSION['paData'];
      $_SESSION['paDatos3'] = $_SESSION['paDatos3'];
      fxScreen(2);
   }

   function fxGuardarActivos(){
      $_SESSION['paData'] = $_SESSION['paData'];
      $laDatos1 = $_SESSION['paDatos1'];
      $laDatos3 = $_SESSION['paDatos3'];
      $_SESSION['paDatos3'] = array_merge($laDatos3, $laDatos1);
      fxScreen(2);
   }

   function fxGrabarTransferenciasAF() {
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $_SESSION['paData']);
      $lo->paDatos = $_SESSION['paDatos3'];
      $llOk = $lo->omGrabarTranferencia();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }else {
         fxAlert('Tranferencia Guardada Satisfactoriamente');
      }
      $_SESSION['paData'] = $lo->paData;
      fxScreen(2);
   }

   function fxEnviarTransferenciasAF(){
      $lcEstado = $_REQUEST['p_bFlag'];
      $lo = new CControlPatrimonial();
      $lo->paData =['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS'] ,'CESTADO'=> $lcEstado]+$_SESSION['paData'];
      $lo->paDatos = $_SESSION['paDatos3'];
      $llOk = $lo->omEnviarTranferencia();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);
         return;
      }
      // $lo = new CControlPatrimonial();
      // $lo->paData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $_SESSION['paData0']);
      // $llOk = $lo->omBuscarTransferencias();
      // if(!$llOk){
      //    fxAlert($lo->pcError);
      // }
      // $_SESSION['paDatos'] = $lo->paDatos;
      fxInit();
   }

   function fxNuevaTransferenciaVarios(){
      $_SESSION['paDatos2'] = null;
      $_SESSION['paData1'] = $_REQUEST['paData'];
      //print_r($_SESSION['paData1']);
      fxScreen(4);
   }

   function fxAgregarActivoVarios(){ 
      $laData = $_REQUEST['paData'];
      $lo = new CControlPatrimonial();
      $lo->paData = array_merge($_SESSION['paData'], $laData);
      $llOk = $lo->omBuscarActivoFijoVarios();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(4);
         return;
      } 
      $_SESSION['paDatos2'] = $lo->paDatos;
      fxScreen(4);
   }

   function fxEliminarActFijVarios(){
      $lcIndice = $_REQUEST['paData']['cCodigo'];
      $lcIndice = $lcIndice -1;
      unset($_SESSION['paDatos2'][$lcIndice]);
      fxScreen(4);
   }

   function fxGenerarPDFVarios(){
      $lo = new CControlPatrimonial();
      $lo->paData = $_SESSION['paData'];
      $llOk =  $lo->omReporteCentroResponsabilidadVarios();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      fxScreen(4);  
   }

   function fxGuardarActivosVarios(){
      $_SESSION['paData'] = $_SESSION['paData'];
      $laDatos2 = $_SESSION['paDatos2'];
      $laDatos3 = $_SESSION['paDatos3'];
      $_SESSION['paDatos3'] = array_merge($laDatos3, $laDatos2);
      fxScreen(2);
   }

   function faxCargarCentroRes() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      $llOk =  $lo->omCargarCentroResponsabilidad();
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
      $llOk =  $lo->omCargarCentroResponsabilidad();
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

   function faxCargarEmpleado() {
      $lo = new CControlPatrimonial();
      $lo->paData['p_cCodEmp'] = $_REQUEST['p_cCodEmp'];
      $llOk =  $lo->omCargarEmpleado();
      if (!$llOk) {
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;  
      }
      echo json_encode($lo->paData);
   }

   function faxCargarEmpleadoDes() {
      $lo = new CControlPatrimonial();
      $lo->paData['p_cCodEmp'] = $_REQUEST['p_cCodEmpDes'];
      $llOk =  $lo->omCargarEmpleado();
      if (!$llOk) {
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;  
      }
      echo json_encode($lo->paData);
   }

   
   function fxScreen($p_nBehavior) {
      global $loSmarty;
      // print_r($_SESSION['paDatos']);
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      $loSmarty->assign('saDato1', $_SESSION['paDato1']);
      $loSmarty->assign('saDatos1', $_SESSION['paDatos1']);
      $loSmarty->assign('saDatos2', $_SESSION['paDatos2']);
      $loSmarty->assign('saDatos3', $_SESSION['paDatos3']);
      $loSmarty->assign('saDatos4', $_SESSION['paDatos4']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saNomEmp', $_SESSION['pcNomEmp']);
      $loSmarty->assign('saEmpNom', $_SESSION['pcEmpNom']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1070.tpl');
      // return;
   }
?>
