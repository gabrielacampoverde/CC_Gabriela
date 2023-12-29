<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CMantenimiento.php';
   $loSmarty = new Smarty;
   if (@$_REQUEST['Boton'] == 'Nuevo') {
      fxNuevo();
   } elseif (@$_REQUEST['Boton'] == 'Editar') {
      fxEditar();
   } elseif (@$_REQUEST['Boton'] == 'Salir') {
      fxHeader("Mnu1000.php");
   } elseif (@$_REQUEST['Boton1'] == 'Grabar') {
      fxGrabar();
   } elseif (@$_REQUEST['Boton1'] == 'Cancelar') {
      fxInit();
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CMantenimiento();
      $lo->paData = ['CCODUSU' => $_SESSION['GCCODUSU']];
      $llOk = $lo->omInitMantenimientoModulo();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paData'] = null;
      $_SESSION['paEstado'] = $lo->paEstado;
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }
   function fxNuevo() {
      $laData['CNUEVO'] = 'S';
      $_SESSION['paData'] = $laData;
      fxScreen(1);
   }

   function fxEditar() {
      $lcCodArt = $_REQUEST['pcCodMod'];
      foreach ($_SESSION['paDatos'] as $laFila){
         if ($laFila['CCODMOD'] === $lcCodArt){
            $laData = $laFila;
            break;
         }
      }
      $laData['CNUEVO'] = 'N';
      $_SESSION['paData'] = $laData;
      fxScreen(1);
   }

   function fxGrabar() {
      $laData = $_REQUEST['paData'];
      $lo = new CMantenimiento();
      $lo->paData = $laData+['CUSUCOD' => $_SESSION['GCCODUSU']];
      $llOk = $lo->omGrabarModulo();
      if (!$llOk) {
         fxScreen(1);
         fxAlert($lo->pcError);
         return;
      }
      fxAlert("DATOS GUARDADOS CORRECTAMENTE");
      $_SESSION['paDatos'] = null;
      fxInit();
   }

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saDatos',$_SESSION['paDatos']);
      $loSmarty->assign('saData',$_SESSION['paData']);
      $loSmarty->assign('saEstado',$_SESSION['paEstado']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNombre', str_replace('/', ' ',$_SESSION['GADATA']['CNOMBRE'])); 
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Adm1030.tpl');
   }
?>
