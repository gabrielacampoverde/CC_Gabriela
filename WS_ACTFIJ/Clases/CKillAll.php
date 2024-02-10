<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CKillAll extends CBase {
   public function omKillAll() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxKillAll($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxKillAll($p_oSql) {
      $lcNroDni = $this->paData["NRODNI"];
      $lcSql = "DELETE FROM s01pdis WHERE cnrodni='$lcNroDni'";
      $R2 = $p_oSql->omExec($lcSql);
      if (!$R2) {
         $this->paDatos = ["error" => "No se pudo eliminar sesiones."];
         return false;
      }
      $this->paDatos = ["data" => "Sesiones eliminadas."];
      return true;
   }
}

?>
