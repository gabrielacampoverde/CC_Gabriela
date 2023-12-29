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
   } elseif (@$_REQUEST['Id'] == 'agregar') {
      fxAxAgregar();
   } elseif (@$_REQUEST['Id'] == 'eliminar') {
      fxAxEliminar();
   } elseif (@$_REQUEST['Id'] == 'buscarRol') {
      fxAxbuscarRol();
   } elseif (@$_REQUEST['Boton1'] == 'Guardar') {
      fxGrabar();
   } elseif (@$_REQUEST['Boton1'] == 'Cancelar') {
      fxInit();
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CMantenimiento();
      $lo->paData = ['CCODUSU' => $_SESSION['GCCODUSU']];
      $llOk = $lo->omInitMantenimientoModulosRol();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      fxScreen(0);
   }
   
   function fxNuevo() {
       $laData['CNUEVO'] = 'S';
       $_SESSION['paData'] = $laData;
       fxScreen(1);
   }
   
   function fxEditar() {
      $lo = new CMantenimiento();
      $lcCodMod = $_REQUEST['pcCodMod'];
      foreach ($_SESSION['paDatos'] as $laFila){
         if ($laFila['CCODMOD'] === $lcCodMod){
            $laData = $laFila;
            break;
         }
      }
      $lo->paData['BUSOPCMOD'] = $lcCodMod;
      $llOk = $lo->omBuscarOpcMod();
      if (!$llOk) {
         fxHeMODr("Mnu1Mod.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      $laData['CNUEVO'] = 'N';
      $_SESSION['paData'] = $laData;
      fxScreen(1);
   }
   
   function fxGrabar(){
      $lo = new CMantenimiento();
      $lo->paData = $_REQUEST['paData'];
      $lo->paData['MDATOS'] = $_SESSION['paDatos'];
      $lo->paData['CUSUCOD'] = $_SESSION['GCCODUSU'];
      $llOk = $lo->omGrabarModRol();
      if (!$llOk) {
         fxScreen(1);
         fxAlert($lo->pcError);
         return;
      }
      fxAlert("DATOS GUARDADOS CORRECTAMENTE");
      fxInit();
   }
   
   function fxAxbuscarRol() {
      $lo = new CMantenimiento();
      $lo->paData['CBUSOPC'] = strtoupper($_REQUEST['pcBusOpc']);
      $llOk = $lo->omBuscarRoles();
      $_SESSION['paOpcion'] = $lo->paDatos;
      echo json_encode($_SESSION['paOpcion']);
   } 
   
   function fxAxAgregar() {
      $lnIndice = $_REQUEST['p_nIndice'];
      $laTmp = $_SESSION['paOpcion'][$lnIndice];
      if ($_SESSION['paDatos'] != null) {
         foreach($_SESSION['paDatos'] as $laFila) {
            if($laFila['CCODROL'] == $laTmp['CCODROL']) {
               fxScreenDetalle();
               return;
            }
         }
      }
      $_SESSION['paDatos'][] = $laTmp;
      fxScreenDetalle();
   } 
   
   function fxAxEliminar() {
       $lnIndice = $_REQUEST['p_nIndice'];
       unset($_SESSION['paDatos'][$lnIndice]);
       array_splice($_SESSION['paDatos'], 0, 0);
       fxScreenDetalle();
   } 

   function fxScreen($p_nBehavior) {
      global $loSmarty;
      $loSmarty->assign('saData', $_SESSION['paData']);
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->assign('saRol', $_SESSION['paRol']);
      $loSmarty->assign('saEstado', $_SESSION['paEstado']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Adm1060.tpl');
   }
   
   function fxScreenDetalle() {
      global $loSmarty;
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->display('Plantillas/Adm1061.tpl');
   }
?>
