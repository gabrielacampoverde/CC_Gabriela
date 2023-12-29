<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CControlPatrimonial.php';
   require_once "Clases/CEmail.php";

   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Id'] == 'Grabar') {
      fxGrabarInventario();
   } elseif (@$_REQUEST['Boton0'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton'] == 'Nuevo') {
      fxCorreo();
   }  else {
      fxInit();
   }
   
   function fxInit() {
      $lo = new CControlPatrimonial();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk =  $lo->omInitManGrabarInventario();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }

   function fxCorreo(){
      $lo = new CEmailPrueba();
      $lo->paData = ['AEMAIL' => 'gcampoverde@ucsm.edu.pe', 'CNOMBRE' => str_replace('/', ' ', $_SESSION['GADATA']['CNOMBRE'])];
      $llOk =  $lo->omSend();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }
   

   function fxGrabarInventario(){
      $laData = ['ID'=>'AFJ0001'];
      $lcParam = json_encode($laData);
      $lcCommand = "python3 ERPWS/Clases/CMigrar.py '".$lcParam."'";
      # print_r($lcCommand);
      // $lcCommand = str_replace('"', '\"', $lcParam);
      // $lcCommand = 'python3 ./ERPWS/Clases/CCntActivoFijo.py "'.$lcCommand.'"';
      // print_r($lcCommand);
      // die();
      $laArray = fxCargarResultado($lcCommand);
      if (isset($laArray['ERROR'])) {  
         $_SESSION['paData'] = $laData;
         fxScreen(1);
         return fxAlert($laArray['ERROR']);
      } 
      fxAlert("MOVIMIENTOS DE INVENTARIO GUARDADO CORRECTAMENTE");
      fxScreen(0);
   }

   function fxCargarResultado($p_cCommand) {
      $lcData = shell_exec($p_cCommand);
      $laArray = json_decode($lcData, true);
      return $laArray;
   }
   
   function fxScreen($p_nBehavior) {
      global $loSmarty;
      // print_r($_SESSION['paDatos']);
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE']));      
      $loSmarty->assign('snBehavior', $p_nBehavior); 
      $loSmarty->display('Plantillas/Afj1220.tpl');
      // return;
   }
?>
