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
   } elseif (@$_REQUEST['Boton1'] == 'Editar') {
      fxGastosActivoFijo();
   } elseif (@$_REQUEST['Boton1'] == 'Regresar') {
      fxScreen(0);
   } elseif (@$_REQUEST['Boton2'] == 'Nuevo') {
      fxNuevoGasto();
   } elseif (@$_REQUEST['Boton2'] == 'Grabar') {
      fxGrabarGastosAF();
   } elseif (@$_REQUEST['Boton2'] == 'Regresar') {
      fxRegresarActFij();
   } elseif (@$_REQUEST['Boton3'] == 'Guardar') {
      fxGuardarNuevoGasto();
   } elseif (@$_REQUEST['Boton3'] == 'Regresar') {
      fxScreen(2);
   } else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS'],  'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk = $lo->omInitMtoGastos();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      // $_SESSION['paData']  = $lo->paData;
      $_SESSION['paDatos'] = null;
      $_SESSION['paEstAct'] = $lo->paData['AESTACT'];
      $_SESSION['paSituac'] = $lo->paData['ASITUAC'];
      fxScreen(0);
   }

   function fxBuscarActivoFijo() {
      $laData = $_REQUEST['paData'];
      $_SESSION['paData0'] = $_REQUEST['paData'];
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omBuscarActivoFijo();
      if (!$llOk) {
         fxHeader("Afj1060.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;   
      fxScreen(1);
   }

   function fxRegresarActFij(){
      $laData = $_SESSION['paData0'];
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omBuscarActivoFijo();
      if (!$llOk) {
         fxHeader("Afj1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(1);
   }

   function fxGastosActivoFijo() {
      $laData = $_REQUEST['paData'];
      $_SESSION['paData'] = $laData;
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $_SESSION['paData']);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omGastosActivoFijo();
      if (!$llOk) {
         fxHeader("Afj1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paData']  = $lo->paData;
      $_SESSION['paData']['CCODIGO'] =substr($_SESSION['paData']['CCODIGO'],0,2).'-'.substr($_SESSION['paData']['CCODIGO'],2,3).'-'.substr($_SESSION['paData']['CCODIGO'],5,7);
      $_SESSION['paDatos']  = $lo->paData['DATOS'];  
      fxScreen(2);
   }

   function fxNuevoGasto() {
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']];
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omTipoGasto();
      $_SESSION['paTipGas']  = $lo->paDatos;
      fxScreen(3);
   }

   function fxGrabarGastosAF() {
      $laData = $_REQUEST['paData'];
      $laData = array_merge(['CUSUCOD' => $_SESSION['GCCODUSU'],'CCENCOS' => $_SESSION['GCCENCOS']], $laData);
      $laData['DATOS'] = $_SESSION['paDatos'];
      // print_r($laData);
      $lo = new CControlPatrimonial();
      $lo->paData = $laData;
      $llOk = $lo->omGrabarGastosAF();
      if (!$llOk) {
         fxAlert($lo->pcError);
      }
      fxRegresarActFij();
   }

   function fxGuardarNuevoGasto(){
      $laTmp = $_REQUEST['paData'];
      // print_r($laTmp);
      $lcDescri = '*';
      foreach ($_SESSION['paTipGas'] as $laFila) {
         if($laFila['CCODFOR'] == $laTmp['CCODFOR']){
            $lcDescri = $laFila['CDESCRI'];
            break;
         }
      }
      $laData = ['NSERIAL'=> -1, 'CCODFOR'=> $laTmp['CCODFOR'], 'CDESCRI'=> $lcDescri, 'CESTADO'=> 'A', 
                  'DFECHA'=> $laTmp['DFECHA'], 'NMONTO'=> $laTmp['NMONTO']];
      $_SESSION['paDatos'][] = $laData;
      fxScreen(2);
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saTipGas', $_SESSION['paTipGas']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1060.tpl');
      // return;
   }
?>
