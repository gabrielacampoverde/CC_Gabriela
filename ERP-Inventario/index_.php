<?php
date_default_timezone_set('America/Lima');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'Libs/Smarty.class.php';
require_once 'Clases/CLogin.php';

$loSmarty = new Smarty;
if (@$_REQUEST['Boton1'] == 'IniciarSesion') {
   fxIniciar();
} elseif (@$_REQUEST['ID'] == 'InicioDirecto') {
   fxIniciarSesionToken();
} else {
   fxInit();
}

function fxInit() {
   $_SESSION = [];
   if (isset($_REQUEST['id'])) {
      $_SESSION['GCIDCATE'] = $_REQUEST['id'];
   }   
   fxScreen();
}

function fxIniciar() {
   $lo = new CLogin();
   $lo->paData = $_REQUEST['paData'];
   $lcNroDni = $_REQUEST['paData']['CNRODNI'];
   $llOk = $lo->omIniciarSesion();
   if (!$llOk) {
      fxScreen();
      fxAlert($lo->pcError);
      return;
   }
   $_SESSION['GCCODUSU'] = $lo->paData['CCODUSU'];
   $_SESSION['GCNOMBRE'] = str_replace("/"," ",$lo->paData['CNOMBRE']);
   $_SESSION['GCNRODNI'] = $lo->paData['CNRODNI'];
   //CASO RARO SOLICITADO POR RRHH
   if ($_SESSION['GCCODUSU'] == 'Q065') {
      $_SESSION['GCNOMBRE'] .= ' - RRHH2';
   }
   $_SESSION['GCCENCOS'] = $lo->paData['CCENCOS'];
   $_SESSION['GCDESCCO'] = $lo->paData['CDESCCO'];
   $_SESSION['GCCARGO'] = $lo->paData['CCARGO'];
   $_SESSION['GCNIVEL'] = $lo->paData['CNIVEL'];
   if ($lcNroDni == $_REQUEST['paData']['CCLAVE']) {
      fxAlert('SU CLAVE ES ALTAMENTE INSEGURA, CÁMBIELA POR FAVOR');
      fxHeader('Adm1130.php');
   }
   if ($_SESSION['GCCENCOS'] == '005') {
      fxHeader("Mnu1010.php");
   } elseif ($_SESSION['GCCARGO'] == '026') {
      fxHeader("Snd1000.php");
   } elseif (isset($_SESSION['GCIDCATE']) && $_SESSION['GCIDCATE'] == 'Uniforme') { #UNIFORME INSTITUCIONAL
      fxHeader('Log3110.php');
   } else {
      fxHeader("Mnu1000.php");
   }
}

function fxIniciarSesionToken() {
   $lo = new CLogin();
   $lo->paData = ['CNRODOC' => $_REQUEST['CNRODOC'], 'CTIPDOC' => $_REQUEST['CTIPDOC'],
                  'CTOKEN'  => $_REQUEST['CTOKEN'],  'NIDUSUA' => $_REQUEST['CUSUID']];
   $llOk = $lo->omIniciarSesionToken();
   if (!$llOk) {
      fxHeader('index.php', $lo->pcError);
      return;
   }
   $_SESSION['GCCODUSU'] = $lo->paData['CCODUSU'];
   $_SESSION['GCNOMBRE'] = str_replace("/"," ",$lo->paData['CNOMBRE']);
   $_SESSION['GCNRODNI'] = $lo->paData['CNRODNI'];
   //CASO RARO SOLICITADO POR RRHH
   if ($_SESSION['GCCODUSU'] == 'Q065') {
      $_SESSION['GCNOMBRE'] .= ' - RRHH2';
   }
   $_SESSION['GCCENCOS'] = $lo->paData['CCENCOS'];
   $_SESSION['GCDESCCO'] = $lo->paData['CDESCCO'];
   $_SESSION['GCCARGO'] = $lo->paData['CCARGO'];
   $_SESSION['GCNIVEL'] = $lo->paData['CNIVEL'];
   if ($_SESSION['GCCENCOS'] == '005') {
      fxHeader("Mnu1010.php");
   } else {
      fxHeader("Mnu1000.php");
   }
}

function fxScreen() {
   global $loSmarty;
   $loSmarty->assign('snBehavior', 0);
   $loSmarty->display('Plantillas/index.tpl');
}
?>