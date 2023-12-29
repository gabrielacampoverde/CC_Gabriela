<?php
   error_reporting(E_ALL);
   ini_set('display_errors', 0);
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CMantenimiento.php';
   $loSmarty = new Smarty;
   if (@$_REQUEST['Boton'] == 'Editar') {
      fxEditar();
   } elseif (@$_REQUEST['Boton'] == 'Buscar') {
      fxBuscar();
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
      $_SESSION['paTrabajador'] = null;
      $_SESSION['paDatos'] = null;
      fxScreen(0);
   }
   function fxBuscar() {
      $lo = new CMantenimiento();
      $lo->paData = $_REQUEST['paData'];
      $llOk = $lo->omBuscarUsuario();
      if (!$llOk) {
         fxScreen(1);
         fxAlert($lo->pcError);
         return;
      }
      $_SESSION['paTrabajador'] = $lo->paDatos;
      fxScreen(0);
   }
   
   function fxEditar() {
      $lo = new CMantenimiento();
      $lcCodUsu = $_REQUEST['pcCodUsu'];
      foreach ($_SESSION['paTrabajador'] as $laFila){
         if ($laFila['CCODUSU'] === $lcCodUsu){
            $laData = $laFila;
            break;
         }
      }
      $lo->paData['BUSUSUROL'] = $lcCodUsu;
      $llOk = $lo->omBuscarUsuarioRol();
      if (!$llOk) {
         fxHeader("Mnu1000.php", $lo->pcError);
         return;
      }
      $_SESSION['paDatos'] = $lo->paDatos;
      $_SESSION['paData'] = $laData;
      fxScreen(1);
   }
   
   function fxGrabar(){
      $lo = new CMantenimiento();
      $lo->paData = $_REQUEST['paData'];
      $lo->paData['MDATOS'] = $_SESSION['paDatos'];
      $lo->paData['CUSUCOD'] = $_SESSION['GCCODUSU'];
      $llOk = $lo->omGrabarRolUsu();
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
      $lo->paData['CBUSROL'] = strtoupper($_REQUEST['pcBusRol']);
      $llOk = $lo->omBuscarRol();
      $_SESSION['paRol'] = $lo->paDatos;
      echo json_encode($_SESSION['paRol']);
   } 
   
   function fxAxAgregar() {
       $lnIndice = $_REQUEST['p_nIndice'];
       $laTmp = $_SESSION['paRol'][$lnIndice];
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
      $loSmarty->assign('saTrabajador', $_SESSION['paTrabajador']);
      $loSmarty->assign('saEstado', $_SESSION['paEstado']);
      $loSmarty->assign('snBehavior', $p_nBehavior);
      $loSmarty->display('Plantillas/Adm1050.tpl');
   }
   
   function fxScreenDetalle() {
      global $loSmarty;
      $loSmarty->assign('saDatos', $_SESSION['paDatos']);
      $loSmarty->display('Plantillas/Adm1051.tpl');
   }
   
?>
