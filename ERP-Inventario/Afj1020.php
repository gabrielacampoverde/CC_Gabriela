<?php
   error_reporting(E_ALL);ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   // require_once 'Clases/CReportes.php';
   // require_once 'Clases/CActivofijo.php';
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      unset($_SESSION['paData0']);
      unset($_SESSION['paData1']);
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') { 
      fxComprasActivoFijo();
   } elseif (@$_REQUEST['Id'] == 'ReporteActReg') { 
      fxReporteActRegPDF();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Id'] == 'NuevoActivoOL') {
      fxNuevoActivoOL();
   } elseif (@$_REQUEST['Id'] == 'reporteOrden') { 
      faxReporteOrden();
   } elseif (@$_REQUEST['Boton1'] == 'ReporteActFij') { 
      fxReporteActFij();
   } elseif (@$_REQUEST['Id'] == 'ListarTiposAF') { 
      faxListarTiposAF();
   } elseif (@$_REQUEST['Id'] == 'ListarTiposAFDI') { 
      faxListarTiposAFDI();
   } elseif (@$_REQUEST['Id'] == 'NuevosActivos') { 
      faxNuevosActivos();
   } elseif (@$_REQUEST['Boton1'] == 'AgregarActivo') { 
      fxAgregarActivoFijo();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxRegresar();
   } elseif (@$_REQUEST['Id'] == 'CargarCentrosRes') { 
      faxCargarCentroRes();
   } elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   } elseif (@$_REQUEST['Id'] == 'CargarRazSoc') { 
      faxCargarRazSoc();
   } elseif (@$_REQUEST['Boton2'] == 'Regresar') {
      fxRegresarNuevosActivos();
   } elseif (@$_REQUEST['Boton2'] == 'Grabar') {
      fxGrabarActivoFijo();
   } elseif (@$_REQUEST['Boton3'] == 'Grabar') {
      fxGrabarActivoFijo_();
   } elseif (@$_REQUEST['Boton3'] == 'Buscar') {
      fxBuscarActFijExistente();
   } elseif (@$_REQUEST['Boton3'] == 'GrabarExistente'){
      fxGrabarActivoFijoExistente();
   }else { 
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitDatosMaeAct();
      if (!$llOk) {
         unset($_SESSION['paData0']);
         unset($_SESSION['paData1']);
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      // $_SESSION['paData']  = $lo->paData;
      $_SESSION['paDatos'] = null;
      $_SESSION['paEstAct'] = $lo->paData['AESTACT'];
      $_SESSION['paSituac'] = $lo->paData['ASITUAC'];
      $_SESSION['paCenRes'] = $lo->paData['ACENRES'];
      $_SESSION['paDatClaAfj'] = $lo->paData['ACLASE'];
      $_SESSION['paCenCos'] = $lo->paData['ACENCOS'];
      $_SESSION['paTipAfj'] = $lo->paData['ATIPAFJ'];
      // print_r($_SESSION['paDatClaAfj']);
      fxScreen(0);
   }
     
   function fxComprasActivoFijo() {
      $laData = $_REQUEST['paData'];
      $laData['CYEAR'] = substr($laData['CPERIOD'], 0, 4);
      $laData['CMONTH'] = substr($laData['CPERIOD'], 5, 2);
      $_SESSION['paData'] = $laData;
      $_SESSION['paData0'] = $laData;
      // print_r($_SESSION['paData0']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omComprasActivoFijo();
      if (!$llOk) {
         fxAlert($lo->pcError);
         $_SESSION['paDatos'] = null;
      } else {
         $laDato['DATOS'] = $_SESSION['laDato'];
         $_SESSION['paDatos'] = $lo->paDatos;
         // $_SESSION['paDatos'] = array_merge($laDato, $_SESSION['paDatos']);
      }

      // $_SESSION['laDato']
      // print_r($_SESSION['paDatos']);
      fxScreen(0);
   }

   function fxRegresar() {
      $_SESSION['paData'] = $_SESSION['paData0'];
      $lo = new CControlPatrimonial();
      $lo->paData = $_SESSION['paData0'];
      $llOk = $lo->omComprasActivoFijo();
      if (!$llOk) {
         fxAlert($lo->pcError);
         $_SESSION['paDatos'] = null;
      } else {
         $_SESSION['paDatos'] = $lo->paDatos;
      }
      // print_r($_SESSION['paDatos']['CCTACNT']);
      fxScreen(0);
   }

   function fxReporteActRegPDF(){
      $pcNumItem =intval($_REQUEST['p_cNumItem']);
      $_SESSION['paDato'] = $_SESSION['paDatos'][$pcNumItem]['CACTFIJ'];
      $lo = new CControlPatrimonial();
      $lo->paDatos = $_SESSION['paDato']; 
      //print_r($lo->paDatos); 
      $llOk = $lo->omReporteActRegPDF(); 
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      echo json_encode($lo->paData);
   }

   function faxReporteOrden() {
      $lo = new CControlPatrimonial();
      $lo->paData['p_cNumCom'] = $_REQUEST['p_cNumCom'];
      $lo->paData['p_cNroRuc'] = $_REQUEST['p_cNroRuc'];
      $lo->paData['p_cYear'] = $_REQUEST['p_cYear'];     
      $llOk = $lo->omObtenerIdOrden();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
      } else {
         echo json_encode($lo->paData);
      }
   }

   function fxReporteActFij() {
      $laData['CCODIOL'] = $_SESSION['paData1']['CCODIOL'];
      $laData['nSerFac'] = $_REQUEST['nSerFac'];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      // print_r($lo->paData);
      $llOk = $lo->omReporteActFij();
      if (!$llOk) {
         fxAlert($lo->pcError);
      } else {
         fxDocumento($lo->paData['CREPORT']);
      }
      $_SESSION['laDato'] = $lo->paDatos;
      fxScreen(1);
   }

   function faxListarTiposAF() {   
      $lo = new CControlPatrimonial();
      $lo->paData['CCLASE'] = $_REQUEST['pcClase'];
      $llOk = $lo->omTiposActivoFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
         return;
      } 
      $_SESSION['paTipAfj'] = $_SESSION['paTipAfj'];
      $_SESSION['paTipAfj']['PADATOS'] = $lo->paDatos;
      $_SESSION['paTipAfj']['PADATA'] = $_SESSION['paData1'];
      echo json_encode($_SESSION['paTipAfj']);
   }

   function faxListarTiposAFDI() {   
      $lo = new CControlPatrimonial();
      $lo->paData['CCLASE'] = $_REQUEST['pcClase'];
      $llOk = $lo->omTiposActivoFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
         return;
      } 
      $_SESSION['paTipAfj'] = $_SESSION['paTipAfj'];
      $_SESSION['paTipAfj']['PADATOS'] = $lo->paDatos;
      $_SESSION['paTipAfj']['PADATA'] = $_SESSION['paData1'];
      echo json_encode($_SESSION['paTipAfj']);
   }
   //Muestra la lista de los activo - GCH
   function faxNuevosActivos() {
      // Define cCodiOl y lo guarda en variable de sesion
      $lnIndice = $_REQUEST['pnIndice'];
      $lcCtaCnt = $_SESSION['paDatos'][$lnIndice]['CCTACNT'];
      $lcCodiOL = $_SESSION['paDatos'][$lnIndice]['CCODIOL'];
      $_SESSION['paDato']= $_SESSION['paDatos'][$lnIndice]; //recuperar el nroasi
      $_SESSION['paData1'] = ['CCODIOL'=> $lcCodiOL];
      // Llama a clase
      $lo = new CControlPatrimonial();
      $lo->paData['CCODIOL'] = $lcCodiOL;
      $llOk = $lo->omDetalleNuevosActivos();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);
      }else { 
         $_SESSION['paData'] = ['CCTACNT'=> $lcCtaCnt];
         $_SESSION['paDatos'] = $lo->paDatos;
         // print_r($_SESSION['paDatos']);
         fxScreen(1);
      }
   }

   function fxNuevoActivoOL(){
      $lnIndice = $_REQUEST['pnIndice'];
      $lcCtaCnt = $_SESSION['paDatos'][$lnIndice]['CCTACNT'];
      $lcCodiOL = $_SESSION['paDatos'][$lnIndice]['CCODIOL'];
      $_SESSION['laDatos'] = $_SESSION['paDatos'][$lnIndice];
      //print_r($_SESSION['laDatos']);
      fxScreen(3);
   }

   //Muestra la lista de los activo - GCH
   function faxNuevosActivos_OLD() {
      $lnIndice = $_REQUEST['pnIndice'];
      $lcCtaCnt = $_SESSION['paDatos'][$lnIndice]['CCTACNT'];
      $lo = new CControlPatrimonial();
      $lo->paData['CCODIOL'] = $_SESSION['paDatos'][$lnIndice]['CCODIOL'];
      $llOk = $lo->omDetalleNuevosActivos();
      if (!$llOk) {
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;
      }      
      $_SESSION['paData'] = ['CCTACNT'=> $lcCtaCnt];
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(1);
   }

   // Muestra la lista de los activo - GCH
   function fxRegresarNuevosActivos() {
      $lo = new CControlPatrimonial();
      $lo->paData['CCODIOL'] = $_SESSION['paData1']['CCODIOL'];
      $llOk = $lo->omDetalleNuevosActivos();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();       // OJO GABY
         return;
      }      
      $_SESSION['paData'] = ['CCTACNT'=> $lcCtaCnt];
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(1);
   }
   
   function fxAgregarActivoFijo() {
      $laData1 = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lnIndice = $_REQUEST['pnIndice']-1;
      $laData = $_SESSION['paDatos'][$lnIndice];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData1;
      $llOk =  $lo->omCargarClaseYCentroCosto();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxInit();       // OJO GABY
         return;
      }
      $_SESSION['paDato'] = $_SESSION['paDato'];
      $_SESSION['paCenCos'] = $lo->paDatos;
      $_SESSION['paDatClaAfj'] = $lo->paDatClaAfj;
      $_SESSION['paData'] = $laData;
      // print_r($_SESSION['paDato']);
      fxScreen(2);
   }

   function faxCargarCentroRes() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      $llOk =  $lo->omCargarCentroResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      $_SESSION['paCenRes'] = $_SESSION['paCenRes'];
      $_SESSION['paCenRes']['PADATOS'] = $lo->paDatos;
      $_SESSION['paCenRes']['PADATA'] = $_SESSION['paData1'];
      echo json_encode($_SESSION['paCenRes']);
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

   function fxBuscarActFijExistente(){
      $lo = new CControlPatrimonial();
      $lo->paData['CCODIGO'] = $_REQUEST['paData']['CCODIGO'];
      $llOk =  $lo->omBuscarActFijExistente();
      if (!$llOk) {
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;  
      }
      $_SESSION['paData'] = $lo->laData;
      $_SESSION['paData1'] = $lo->laData;
      fxScreen(3);
   }

   function faxCargarRazSoc(){
      $lo = new CControlPatrimonial();
      $lo->paData['p_cCodEmp'] = $_REQUEST['p_cCodEmp'];
      $llOk =  $lo->omCargarRazSoc();
      if (!$llOk) {
         echo json_encode(['ERROR'=>$lo->pcError]);
         return;  
      }
      echo json_encode($lo->paData);
   }
   //Boton Graba los activos fijos ingresados (pantalla3)
   function fxGrabarActivoFijo() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU']] + $_REQUEST['paData'];
      if ($lo->paData['CINDACT'] == '') {
         $lo->paData['CINDACT'] = 'N';
      }
      if ($lo->paData['CCODEMP'] == NULL) {
         $lo->paData['CCODEMP'] = '0000';
      }
      if ($lo->paData['CMONEDA'] == 'SOLES') {
         $lo->paData['CMONEDA'] = '1';
      }else{
         $lo->paData['CMONEDA'] = '2';
      }
      $lo->paData['CMARCA'] = strtoupper($lo->paData['CMARCA']);
      $lo->paData['CCANTID'] = strtoupper($lo->paData['CCANTID']);
      $lo->paData['CMODELO'] = strtoupper($lo->paData['CMODELO']);
      $lo->paData['CCOLOR'] = strtoupper($lo->paData['CCOLOR']);
      $lo->paData['CPLACA'] = strtoupper($lo->paData['CPLACA']);
      $lo->paData['CNROSER'] = strtoupper($lo->paData['CNROSER']);
      $lo->paData['CDOCADQ'] = str_replace(' ','',strtoupper($lo->paData['CDOCADQ']));
      $lo->paData['CCODART'] = strtoupper($lo->paData['CCODART']);
      $lo->paData['CCODREF'] = strtoupper($lo->paData['CCODREF']);
      $_SESSION['paData'] = $lo->paData;
      $llOk = $lo->omRegistrarNuevosActivosFijos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      fxRegresarNuevosActivos();
   }

   function fxGrabarActivoFijo_() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU']] + $_REQUEST['paData'];
      if ($lo->paData['CINDACT'] == '') {
         $lo->paData['CINDACT'] = 'N';
      }
      if ($lo->paData['CNRORUC'] == NULL) {
         $lo->paData['CNRORUC'] = '00000000000';
         $lo->paData['CRAZSOC'] = '-';
      }
      if ($lo->paData['CCODEMP'] == NULL) {
         $lo->paData['CCODEMP'] = '0000';
      }
      if ($lo->paData['NMONTMN'] == NULL) {
         $lo->paData['NMONTMN'] = '0.00';
      }
      if ($lo->paData['NMONTME'] == NULL) {
         $lo->paData['NMONTME'] = '0.00';
      }
      if ($lo->paData['CMONEDA'] == 'SOLES') {
         $lo->paData['CMONEDA'] = '1';
      }else if($lo->paData['CMONEDA'] == 'DOLARES'){
         $lo->paData['CMONEDA'] = '2';
      }
      $lo->paData['CMARCA'] = strtoupper($lo->paData['CMARCA']);
      $lo->paData['CCANTID'] = strtoupper($lo->paData['CCANTID']);
      $lo->paData['CMODELO'] = strtoupper($lo->paData['CMODELO']);
      $lo->paData['CCOLOR'] = strtoupper($lo->paData['CCOLOR']);
      $lo->paData['CPLACA'] = strtoupper($lo->paData['CPLACA']);
      $lo->paData['CNROSER'] = strtoupper($lo->paData['CNROSER']);
      $lo->paData['CDOCADQ'] = str_replace(' ','',strtoupper($lo->paData['CDOCADQ']));
      $lo->paData['CCODART'] = strtoupper($lo->paData['CCODART']);
      $lo->paData['CCODREF'] = strtoupper($lo->paData['CCODREF']);
      $_SESSION['paData'] = $lo->paData;
      $llOk = $lo->omRegistrarNuevosActivosFijos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }else {
         fxAlert("ACTIVO FIJO GRABADO CORRECTAMENTE  ".$lo->laData['CCODIGO']);
         fxScreen(3);
      }
      // fxScreen(3);
   }

   function fxGrabarActivoFijoExistente(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU']] + $_REQUEST['paData'];
      if ($lo->paData['CINDACT'] == '') {
         $lo->paData['CINDACT'] = 'N';
      }
      if ($lo->paData['CNRORUC'] == NULL) {
         $lo->paData['CNRORUC'] = '00000000000';
         $lo->paData['CRAZSOC'] = '-';
      }
      if ($lo->paData['CCODEMP'] == NULL) {
         $lo->paData['CCODEMP'] = '0000';
      }
      if ($lo->paData['NMONTMN'] == NULL) {
         $lo->paData['NMONTMN'] = '0.00';
      }
      if ($lo->paData['NMONTME'] == NULL) {
         $lo->paData['NMONTME'] = '0.00';
      }
      if ($lo->paData['CMONEDA'] == 'SOLES') {
         $lo->paData['CMONEDA'] = '1';
      }else if($lo->paData['CMONEDA'] == 'DOLARES'){
         $lo->paData['CMONEDA'] = '2';
      }
      $lo->paData['CMARCA'] = strtoupper($lo->paData['CMARCA']);
      $lo->paData['CCANTID'] = strtoupper($lo->paData['CCANTID']);
      $lo->paData['CMODELO'] = strtoupper($lo->paData['CMODELO']);
      $lo->paData['CCOLOR'] = strtoupper($lo->paData['CCOLOR']);
      $lo->paData['CPLACA'] = strtoupper($lo->paData['CPLACA']);
      $lo->paData['CNROSER'] = strtoupper($lo->paData['CNROSER']);
      $lo->paData['CDOCADQ'] = str_replace(' ','',strtoupper($lo->paData['CDOCADQ']));
      $lo->paData['CCODART'] = strtoupper($lo->paData['CCODART']);
      $lo->paData['CCODREF'] = strtoupper($lo->paData['CCODREF']);
      $_SESSION['paData'] = $lo->paData;
      // print_r($_SESSION['paData']);
      // die;
      $llOk = $lo->omRegistrarNuevosActivosFijos();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }else {
         fxAlert("ACTIVO FIJO GRABADO CORRECTAMENTE  ".$lo->laData['CCODIGO']);
         fxScreen(3);
      }
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('laData', $_SESSION['laData']);
      $loSmarty->assign('saDatas', $_SESSION['paDatas']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('laDatos', $_SESSION['laDatos']);
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      $loSmarty->assign('saEstAct', $_SESSION['paEstAct']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('saDatClaAfj', $_SESSION['paDatClaAfj']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saTipAfj', $_SESSION['paTipAfj']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1020.tpl');
   }
?>
