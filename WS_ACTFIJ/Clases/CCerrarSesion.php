<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CCerrarSesion extends CBase {
   public $paError = [];

   public function omCerrarSesion() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(1);
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxCerrarSesion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParam() {
      if (!isset($this->paData["NRODNI"])) {
         $this->paDatos = ["error" => "Ingrese un número de DNI válido."];
         return false;
      } elseif (!isset($this->paData["FCMTKN"])) {
         $this->paDatos = [
            "error" => "Error al recuperar identificación de dispositivo.",
         ];
         return false;
      }
      return true;
   }

   protected function mxCerrarSesion($p_oSql) {
      $lcNroDni = $this->paData["NRODNI"];
      $lcDevUid = $this->paData["FCMTKN"];
      $lcSql = "SELECT COUNT(*) FROM s01pdis WHERE cNroDni = '$lcNroDni' AND cDevUid='$lcDevUid'";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->paDatos = ["error" => "Dispositivo no registrado."];
         return false;
      }
      $lcActSes = $p_oSql->fetch($R1);

      if ($lcActSes[0] == 0) {
         $this->paDatos = ["error" => "No hay sesiones activas."];
         return false;
      }

      $lcSql = "DELETE FROM s01pdis WHERE cDevUid = '$lcDevUid' AND cNroDni = '$lcNroDni'";
      $R2 = $p_oSql->omExec($lcSql);
      if (!$R2) {
         $this->paDatos = [
            "error" => "La sesión no pudo cerrarse correctamente.",
         ];
         return false;
      }
      $this->paDatos = ["data" => "Sesión cerrada correctamente."];
      return true;
   }
}

?>
