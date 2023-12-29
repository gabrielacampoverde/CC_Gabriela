<?php
   # -------------------------------------------
   # MENU DE OPCIÓN PRINCIPAL PARA ACTIVOS FIJOS
   # 2022-09-28 APR Creacion
   # -------------------------------------------
   session_start();
   require_once 'Libs/Smarty.class.php';
   require_once 'Clases/CMenu.php';
   error_reporting(0);
   $loSmarty = new Smarty;
   if (!fxInitSession()) {
      fxHeader("index.php", 'NO HA INICIADO SESION');
   } else {
      fxInit();
   }

   function fxInit() {
      $lo = new CMenu();
      $lo->paData['CCODUSU'] = $_SESSION['GCCODUSU'];
      $lo->paData['CCENCOS'] = $_SESSION['GCCENCOS'];
      $llOk = $lo->omInitMenu();
      if (!$llOk) {
         fxHeader("index.php", $lo->pcError);
         return;
      }
      $_SESSION['paCenCos'] = $lo->paCenCos;
      $_SESSION['paRoles']  = $lo->paRoles;
//      var_dump($lo->paRoles);
//      return false;
      $_SESSION['paNotifi'] = $lo->paNotifi;
      $_SESSION['pnCanNot'] = $lo->pnCanNot;
      $_SESSION['pcCanUsu'] = $lo->pcCanUsu;
      $_SESSION['pcCanRol'] = $lo->pcCanRol;
      $_SESSION['pcDesarr'] = $lo->pcDesarr;
      $_SESSION['GCPERIOD'] = $lo->pcPeriod;
      $_SESSION['GADATA']['CCODUSU'] = $_SESSION['GCCODUSU'];
      $_SESSION['GADATA']['CNRODNI'] = $_SESSION['GCNRODNI'];
      $_SESSION['GADATA']['CNOMBRE'] = $_SESSION['GCNOMBRE'];
      //Mensajes en menu
      $llOk = $lo->omCargarMensajes();
      $_SESSION['paMensaje'] = $lo->paMensaje;
      $_SESSION['paDesMen']  = $lo->paDesMen;
      if(in_array($lo->paData['CCODUSU'], ['3280','1872','2716','1537','Q128','1952','1221','2675','3006','3386'])){
         fxScreen();
      }else{
         fxHeader("Mnu1000.php", $lo->pcError);
      }
   }

   function fxScreen() {
      global $loSmarty;
      $loSmarty->assign('scNombre', $_SESSION['GCNOMBRE']);
      $loSmarty->assign('scCodUsu', $_SESSION['GCCODUSU']);
      $loSmarty->assign('scCenCos', $_SESSION['GCCENCOS']);
      $loSmarty->assign('saCenCos', $_SESSION['paCenCos']);
      $loSmarty->assign('saRoles',  $_SESSION['paRoles']);
      $loSmarty->assign('sMensaje', $_SESSION['paMensaje']);
      $loSmarty->assign('sDesMen' , $_SESSION['paDesMen']);
      $loSmarty->assign('saNotifi', $_SESSION['paNotifi']);
      $loSmarty->assign('snCanNot', $_SESSION['pnCanNot']);
      $loSmarty->assign('scCanUsu', $_SESSION['pcCanUsu']);
      $loSmarty->assign('scCanRol', $_SESSION['pcCanRol']);
      $loSmarty->assign('scDesarr', $_SESSION['pcDesarr']);
      $loSmarty->assign('scPaqOpc', $_SESSION['GCPAQOPC']);
      $loSmarty->display('Plantillas/Afj1000.tpl');
   }
?>