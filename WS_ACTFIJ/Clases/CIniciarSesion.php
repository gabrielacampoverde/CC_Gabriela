<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CIniciarSesion extends CBase {
   public $pcParam, $paData, $paDatos;

   public function __construc() {
      $this->pcParam = $this->paData = $this->paDatos= null;
   }

   public function omIniciarSesion() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxIniciarSesion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParam() {
      if (!isset($this->paData["NRODNI"])) {
         $this->paDatos = ["error" => "Ingrese número de DNI válido 1."];
         return false;
      } elseif (strlen($this->paData["NRODNI"]) < 7 || strlen($this->paData["NRODNI"]) > 11) {
         $this->paDatos = ["error" => "Ingrese número de DNI válido 2."];
         return false;
      } elseif (!ctype_digit($this->paData["NRODNI"])) {
         $this->paDatos = ["error" => "Ingrese número de DNI válido 3."];
         return false;
      }
      //print_r($this->paData['PASSWORD']);
      $this->paData['PASSWORD'] = hash('sha512', $this->paData['PASSWORD']);
      return true;
   }

   protected function mxIniciarSesion($p_oSql) {
      $lcJson = ['CNRODNI' => $this->paData['NRODNI'], 'CCLAVE' => $this->paData['PASSWORD']];
      $lcJson = json_encode($lcJson);
      //print_r($lcJson);
      $lcSql = "SELECT P_LOGIN('$lcJson')";
      //print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }
}
?>
