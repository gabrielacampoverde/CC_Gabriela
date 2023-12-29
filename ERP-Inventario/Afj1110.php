<?php
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   
   //ini_set('max_execution_time', '500'); //300 seconds = 5 minutes
   error_reporting(E_ALL);ini_set('display_errors', 0);
   
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'No ha iniciado sesión.');
   } elseif (@$_REQUEST['Boton'] == 'Buscar') {
      fxBuscarActivoFijo();
   } elseif (@$_REQUEST['Boton'] == 'Nuevo') {
      fxScreen(2);
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Id'] == 'ListarTiposN') { 
      faxListarTiposN();
   } elseif (@$_REQUEST['Id'] == 'ListarTiposAF') { 
      faxListarTiposAF();
   } elseif (@$_REQUEST['Id'] == 'CentrosResponsabilidad') { 
      faxCentrosResponsabilidad();
   }  elseif (@$_REQUEST['Id'] == 'BuscarEmpleado') { 
      faxBuscarEmpleado();
   }  elseif (@$_REQUEST['Boton1'] == 'Grabar') {
      fxGrabarEditarActivo();
   }  elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxInit();
   }  elseif (@$_REQUEST['Id'] == 'Eliminar') {
      fxEliminarActFij();
   }  elseif (@$_REQUEST['Boton2'] == 'Grabar') {
      fxGrabarActivoNuevo();
   }  elseif (@$_REQUEST['Boton2'] == 'Regresar') {
      fxInit();
   }  elseif (@$_REQUEST['Id'] == 'CargarCentroResp') { 
      faxCentrosResponsabilidad();
   }  else {
      fxInit();
   }

   function fxInit(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $llOk = $lo->omMantActivoFijo();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      $_SESSION['paEstAct'] = $lo->paData['AESTACT'];
      $_SESSION['paSituac'] = $lo->paData['ASITUAC'];
      $_SESSION['paDatClaAfj'] = $lo->paData['CCODCLA'];
      $_SESSION['paCenCos'] = $lo->paData['CCENCOS'];
      $_SESSION['paTipAfj'] = $lo->paData['CTIPAFJ'];
      fxScreen(0);
   }

   function faxCargarCentrosCosto(){
      echo json_encode($_SESSION['paCenCos']);
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
      // print_r($_SESSION['paData']);
      $i = 0;
      if ($_SESSION['paData']['CSITUAC'] == 'B') {
         fxScreen(3);
      } else {
         fxScreen(1);
      }
   }

   function fxEliminarActFij(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'],'CACTFIJ' => $_REQUEST['paData']['CACTFIJ']];
      if(in_array($lo->paData['CUSUCOD'], ['3280','1872']) ){ 
         $llOk = $lo->omEliminarActivosFijos();
         fxAlert($lo->pcError);
         fxScreen(0);
      }else{
         fxAlert("NO ESTA AUTORIZADO PARA ELIMINAR UN ACTIVO FIJO");
         fxScreen(0);
      }
   }
   
   function fxGrabarEditarActivo(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU']] + $_REQUEST['paData'];
      if ($lo->paData['CINDACT'] == '') {
         $lo->paData['CINDACT'] = 'N';
      }
      if ($lo->paData['CMONEDA'] == 'SOLES') {
         $lo->paData['CMONEDA'] = '1';
      } else {
         $lo->paData['CMONEDA'] = '0';
      }
      if ($lo->paData['CCODEMP'] == null) {
         $lo->paData['CCODEMP'] = '0000';
      }
      $lo->paData['NSERFAC'] = 0;
      $lo->paData['CMARCA'] = strtoupper($lo->paData['CMARCA']);
      $lo->paData['CMODELO'] = strtoupper($lo->paData['CMODELO']);
      $lo->paData['CCOLOR'] = strtoupper($lo->paData['CCOLOR']);
      $lo->paData['CPLACA'] = strtoupper($lo->paData['CPLACA']);
      $lo->paData['CNROSER'] = strtoupper($lo->paData['CNROSER']);
      $lo->paData['CMOTOR'] = strtoupper($lo->paData['CMOTOR']);
      $lo->paData['CDOCADQ'] = strtoupper($lo->paData['CDOCADQ']);
      $lo->paData['CDOCALT'] = strtoupper($lo->paData['CDOCALT']);
      $lo->paData['CDOCBAJ'] = strtoupper($lo->paData['CDOCBAJ']);
      $lo->paData['DFECBAJ'] = strtoupper($lo->paData['DFECBAJ']);
      $lo->paData['CCANTID'] = strtoupper($lo->paData['CCANTID']);
      $_SESSION['paData'] = $lo->paData;
      $llOk = $lo->omNuevosActivosFijos();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(1);
      } else {
         fxAlert("ACTIVO FIJO GRABADO CORRECTAMENTE");
         fxScreen(0);
      }
   }

   function fxGrabarActivoNuevo(){
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU']] + $_REQUEST['paData'];
      if ($lo->paData['CINDACT'] == '') {
         $lo->paData['CINDACT'] = 'N';
      }
      if ($lo->paData['CCODEMP'] == '') {
         $lo->paData['CCODEMP'] = '0000';
      }
      if ($lo->paData['CMONEDA'] == 'SOLES') {
         $lo->paData['CMONEDA'] = '1';
      } else {
         $lo->paData['CMONEDA'] = '0';
      }
      $lo->paData['NSERFAC'] = 0;
      $lo->paData['CMARCA'] = strtoupper($lo->paData['CMARCA']);
      $lo->paData['CMODELO'] = strtoupper($lo->paData['CMODELO']);
      $lo->paData['CCOLOR'] = strtoupper($lo->paData['CCOLOR']);
      $lo->paData['CPLACA'] = strtoupper($lo->paData['CPLACA']);
      $lo->paData['CNROSER'] = strtoupper($lo->paData['CNROSER']);
      $lo->paData['CMOTOR'] = strtoupper($lo->paData['CMOTOR']);
      $lo->paData['CDOCADQ'] = strtoupper($lo->paData['CDOCADQ']);
      $lo->paData['CDOCALT'] = strtoupper($lo->paData['CDOCALT']);
      $lo->paData['CDOCBAJ'] = strtoupper($lo->paData['CDOCBAJ']);
      $lo->paData['DFECBAJ'] = strtoupper($lo->paData['DFECBAJ']);
      $_SESSION['paData'] = $lo->paData;
      $llOk = $lo->omNuevosActivosFijos();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(2);
      } else {
         fxAlert("ACTIVO FIJO GRABADO CORRECTAMENTE  ".$lo->laData['CCODIGO']);
         fxScreen(0);
      }
   }

   function faxListarTiposAF() {
      $lo = new CControlPatrimonial();
      $lo->paData['CCLASE'] = $_REQUEST['pcClase'];
      $llOk = $lo->omTiposActivoFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
         return;
      }
      $_SESSION['paTipAf']['LADATOS'] = $_SESSION['paTipAfj'];
      $_SESSION['paTipAf']['LADATA'] =  $_SESSION['paData']; 
      $_SESSION['paTipAf']['LADATAS'] =  $lo->paDatos; 
      echo json_encode($_SESSION['paTipAf']);
   }

   function faxListarTiposN() {  
      // print_r($_REQUEST); 
      $lo = new CControlPatrimonial();
      $lo->paData['CCLASE'] = $_REQUEST['pcClase'];
      $llOk = $lo->omTiposActivoFijo();
      if (!$llOk) {
         echo json_encode(['ERROR'=> $lo->pcError]);
         return;
      }
      echo json_encode($lo->paDatos);
   }

   function faxCentrosResponsabilidad() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CCENCOS'=> $_REQUEST['pcCenCos']];
      // print_r($lo->paData);
      $llOk =  $lo->omCentrosResponsabilidad();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      echo json_encode($lo->paData);
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
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDato', $_SESSION['paDato']);
      // $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('saEstAct', $_SESSION['paEstAct']);
      $loSmarty->assign('saDatClaAfj', $_SESSION['paDatClaAfj']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saCenRes', $_SESSION['paCenRes']);
      $loSmarty->assign('saDepre', $_SESSION['paDepre']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1110.tpl');
      return;
   }

?>