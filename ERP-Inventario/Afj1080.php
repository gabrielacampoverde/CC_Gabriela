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
      fxBuscarActivoFijo();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton'] == 'Componente') {
      fxBuscarComponente();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxRegresarActFijCom();
   } elseif (@$_REQUEST['Boton1'] == 'Nuevo') {
      fxNuevoComponente();
   } elseif (@$_REQUEST['Boton1'] == 'Grabar') {
      fxGrabarComponenteAF();
   } elseif (@$_REQUEST['Boton2'] == 'Guardar') {
      fxGuardarNuevoComponente();
   } elseif (@$_REQUEST['Boton2'] == 'Regresar') {
      fxScreen(1);
   } else {
      fxInit();
   }
   
   function fxInit() {
      $laData = ['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omInitMtoComponentes();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paData'] = null;
      $_SESSION['paSituac'] = $lo->paData['ASITUAC'];
      $_SESSION['paEstado'] = $lo->paData['AESTACT'];
      fxScreen(0);
   }

   function fxBuscarActivoFijo() {
      $_SESSION['paData'] = $_REQUEST['paData'];
      $_SESSION['paData1'] = $_REQUEST['paData'];
      $_SESSION['paData0'] = $_REQUEST['paData'];
      $laData  = array_merge(['CUSUCOD'=> $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS']], $_SESSION['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omAplicarMtoComponentes();
      if (!$llOk) {
         fxAlert($lo->pcError);
         $_SESSION['paData'] = null;
      }
      // $_SESSION['paDatos'] = $lo->paData['DATOS'];
      $_SESSION['paData'] = $lo->paData;
      $_SESSION['paData1'] = $_SESSION['paData']; 
      fxScreen(0);
   }

   function fxBuscarComponente(){
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $_SESSION['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omBuscarComponente();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(0);
         return;
      }

      $_SESSION['paDatos']  = $lo->paDatos;
      // print_r($_SESSION['paDatos']); 
      fxScreen(1);
   }

   function fxNuevoComponente() {
     // $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']];
      fxScreen(2);
   }

   function fxGuardarNuevoComponente(){
      $laTmp1 = $_SESSION['paData1'];
      $laTmp = $_REQUEST['paData'];
      if($laTmp['CETIQUE'] == ''){
         $laTmp['CETIQUE'] = 'N';
      }else{
         $laTmp['CETIQUE'] = 'S';
      }
      if($laTmp['NMONTO'] == ''){
         $laTmp['NMONTO'] = '0.00';
      }
      if($laTmp['CINDACT'] == ''){
         $laTmp['CINDACT'] = 'N';
      }
      $laData = ['NSERIAL'=> -1, 'CACTFIJ'=> $laTmp1['CACTFIJ'], 'CDESCRI'=> $laTmp['CDESCRI'], 
                 'CINDACT'=> $laTmp['CINDACT'], 'CESTADO'=> 'A', 'CSITUAC'=> 'O', 'CETIQUE'=> $laTmp['CETIQUE'], 
                 'DFECADQ'=> $laTmp['DFECADQ'], 'NMONTO'=> $laTmp['NMONTO'], 'CCANTID'=> $laTmp['CCANTID'], 
                 'CPLACA'=> $laTmp['CPLACA'], 'CMODELO'=> $laTmp['CMODELO'], 'CMARCA'=> $laTmp['CMARCA'],
                 'CCOLOR'=> $laTmp['CCOLOR'],'CNROSER'=> $laTmp['CNROSER'], 'CDESSIT' =>'OPERATIVO', 'CDOCADQ'=> $laTmp['CDOCADQ']] ;
      $_SESSION['paDatos'][] = $laData;
      fxScreen(1);
   }   

   function fxGrabarComponenteAF() {
      $laData1 = $_REQUEST['paData'];
      $laData = $_SESSION['paDatos'];
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']];
      $lo->paDatos = $laData;
      $llOk = $lo->omGrabarComponente();
      if (!$llOk) {
         fxAlert($lo->pcError);
         fxScreen(1);
         return;
      }
      fxAlert('Componente guardado');
      fxBuscarComponente();
   }

   function fxRegresarActFijCom(){
      $laData = $_SESSION['paData0'];
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omAplicarMtoComponentes();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saSituac', $_SESSION['paSituac']);
      $loSmarty->assign('saEstado', $_SESSION['paEstado']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1080.tpl');
      // return;
   }
?>
