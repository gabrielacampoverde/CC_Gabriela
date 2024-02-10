<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CVerificarEstado extends CBase {
   public $paMatriculas;

   public function omVerificarEstado() {
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
      $llOk = $this->mxVerificarEstado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParam() {
      if (array_key_exists($this->paData["NRODNI"])) {
         $this->paDatos = ["error" => "Número de DNI no válido"];
         return false;
      } elseif (array_key_exists($this->paData["FCMTKN"])) {
         $this->paDatos = ["error" => "Token no especificado."];
         return false;
      }
      return true;
   }

   protected function mxVerificarEstado($p_oSql) {
      $lcNroDni = $this->paData["NRODNI"];
      $lcDevUid = $this->paData["FCMTKN"];
      $lcSql = "SELECT COUNT(*) FROM s01pdis WHERE cNroDni = '$lcNroDni' AND cDevUid = '$lcDevUid'";
      $RS = $p_oSql->omExec($lcSql);

      if (!$RS) {
         $this->paDatos = ["error" => "Error al consultar número de sesiones."];
         return false;
      }
      $laEstado = $p_oSql->fetch($RS);

      if ($laEstado[0] == 0) {
         $this->paDatos = ["alert" => "Sesión inválida."];
         return true;
      } elseif ($laEstado[0] > 0) {
         $this->paDatos = ["data" => "Sesión verificada."];
         return true;
      } else {
         $this->paDatos = ["error" => "Error al validar estado."];
         return false;
      }
   }
}

?>
