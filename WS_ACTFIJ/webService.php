<?php
/*ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); */
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: POST");
header("Content-type: application/json");
header("Allow: POST");

require_once "Clases/CIniciarSesion.php";
require_once "Clases/CKillAll.php";
require_once "Clases/CVerificarEstado.php";
require_once "Clases/CConsultarActFij.php";
require_once "Clases/CCerrarSesion.php";
require_once "Clases/CConsultarEventos.php";
require_once "Clases/CEvenNoti.php";
require_once "Clases/CFirebase.php";

$REQUEST = file_get_contents("php://input");
$DATA = json_decode($REQUEST, true);
$pcQuery = $DATA["QUERY"];
if ($pcQuery == "IniciarSesion") {
   fxAxIniciarSesion();
} elseif ($pcQuery == "KillAll") {
   fxAxKillAll();
} elseif ($pcQuery == "ConsultarActFij") {
   fxAxConsultarActFij();
}elseif($pcQuery == "ConsultarCenCos"){
   fxAxConsultarCenCos();
}elseif($pcQuery == "ConsultarCenRes"){
   fxAxConsultarCenRes();
}elseif($pcQuery == "ConsultarEmpleado"){
   fxAxConsultarEmpleado();
}elseif($pcQuery == "TransferenciaActFij"){
   fxAxTransferenciaActFij();
}elseif($pcQuery == "ConsultarActFijCenRes"){
   fxAxConsultarActFijCenRes();
}elseif($pcQuery == "ConsultarInventario"){
   fxAxConsultarInventario();
}elseif($pcQuery == 'GuardarInventario'){
   fxAxGuardarInventario();
}elseif($pcQuery == 'GuardarActFijInventariado'){
   fxAxGuardarActFijInventariado();
}elseif ($pcQuery == "CerrarSesion") {
   fxAxCerrarSesion();
}elseif ($pcQuery == "MesaAyuda") {
   fxAxMesaAyuda();
}else {
   echo json_encode(["error" => "Conexión incorrecta"]);
}

function fxAxConsultarActFij(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omConsultarActFij();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}

function fxAxConsultarCenCos(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omConsultarCenCos();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}
function fxAxConsultarCenRes(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omConsultarCenRes();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}
function fxAxConsultarActFijCenRes(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omConsultarActFijCenRes();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}

function fxAxConsultarInventario(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omConsultarInventario();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}

function fxAxGuardarInventario(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omGuardarInventario();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}

function fxAxGuardarActFijInventariado(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omGuardarActFijInventariado();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}

function fxAxConsultarEmpleado(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omConsultarEmpleado();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Consulta Exitosa"]);
   }
}


function fxAxTransferenciaActFij(){
   global $DATA;
   $lo = new CConsultarActFij();
   $lo->paData = $DATA;
   $llOk = $lo->omTransferenciaActFij();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   } else {
      echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "Transferecia de activo fijo exitosa"]);
   }
}

function fxAxIniciarSesion() {
   global $DATA;
   $lo = new CIniciarSesion();
   $lo->paData = $DATA;
   //echo json_encode($lo->paData);
   $llOk = $lo->omIniciarSesion();
   if (!$llOk) {
      echo json_encode(["ok" => false, "mensaje" => $lo->pcError]);
   }
   echo json_encode(["ok" => true, "contenido" => $lo->paDatos, "mensaje" => "LOGIN EXITOSO"]);
}

function fxAxKillAll() {
   global $DATA;
   $lo = new CKillAll();
   $lo->paData = $DATA;
   $llOk = $lo->omKillAll();
   echo json_encode($lo->paDatos);
}

function fxAxCerrarSesion() {
   global $DATA;
   $lo = new CCerrarSesion();
   $lo->paData = $DATA;
   $llOk = $lo->omCerrarSesion();
   echo json_encode($lo->paDatos);
}

function fxAxMesaAyuda() {
   global $DATA;
   $params = json_encode($DATA);
   $cmd = "python3 PyClasses/CPreguntas.py " . "'$params'" . " 2>&1";
   $result = shell_exec($cmd);
   // echo $result;
   echo json_encode(["data" => json_decode($result)]);
}


?>
