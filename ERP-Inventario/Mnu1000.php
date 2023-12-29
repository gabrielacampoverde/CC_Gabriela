<?php
   # -------------------------------------------
   # MENU DE OPCIÓN PRINCIPAL EN EL SISTEMA ERP
   # 2023-07-21 GCH Creacion
   # -------------------------------------------
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CMenu.php';
   ini_set('display_errors', 0);
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } elseif (@$_REQUEST['Id'] == 'MostrarRoles') {
      faxMostrarRoles();
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CMenu();
      $lo->paData['CCODUSU'] = $_SESSION['GCCODUSU'];
      $lo->paData['CCENCOS'] = $_SESSION['GCCENCOS'];
      $lo->paData['CNRODNI'] = $_SESSION['GCNRODNI'];
      if(count($_SESSION['paCencos']) === 0 || count($_SESSION['paModule']) === 0) {
         $llOk = $lo->omInitMenu();
         $_SESSION['paCenCos'] = $lo->paCenCos;
         $_SESSION['paModule'] = $lo->paModule;
         $_SESSION['paRoles'] = $lo->paRoles;
         if (!$llOk) {
            fxHeader("index.php", $lo->pcError);
            return;
         }
      }
      $_SESSION['GADATA']['CCODUSU'] = $_SESSION['GCCODUSU'];
      $_SESSION['GADATA']['CNRODNI'] = $_SESSION['GCNRODNI'];
      $_SESSION['GADATA']['CNOMBRE'] = $_SESSION['GCNOMBRE'];
      fxScreen();
   }

   function faxMostrarRoles(){
      $lo = new CMenu();
      $lo->paData = ['CUSUCOD' => $_SESSION['GCCODUSU'], 'CCENCOS'=> $_SESSION['GCCENCOS'], 'CCODMOD'=> $_REQUEST['p_CodMod']];
      if ($lo->paData['CCODMOD'] != $_SESSION['GCCODMOD']) {
         $llOk = $lo->omInitMenuRoles();
         if (!$llOk) {
            //echo json_encode(['ERROR' => $lo->pcError]);
            // fxAlert( $lo->pcError)
            return;
         }
         $_SESSION['GCCODMOD'] = $_REQUEST['p_CodMod'];
         $_SESSION['paDatosMenu'] = $lo->paDatos;
      }
      fxScreenRoll();
   }

   function fxScreenRoll() {
      global $loSmarty;
      $loSmarty->assign('saDatos', $_SESSION['paDatosMenu']);
      $loSmarty->display('Plantillas/Mnu1001.tpl');
   }

   function fxScreen() {
      global $loSmarty;
      $loSmarty->assign('scNombre', $_SESSION['GCNOMBRE']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scNroDni', $_SESSION['GCNRODNI']);
      $loSmarty->assign('scCenCos', $_SESSION['GCCENCOS']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saRoles', $_SESSION['paRoles']);
      $loSmarty->assign('scModule', $_SESSION['paModule']);
      $loSmarty->assign('saDatos', $_SESSION['paDatosMenu']);
      $loSmarty->assign('saModule', $_SESSION['GCCODMOD']);
      $loSmarty->display('Plantillas/Mnu1000.tpl');
   }
?>