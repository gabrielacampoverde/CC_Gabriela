<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CConsultarEventos extends CBase {
   public $pcParam;
   public $paEventos;

   public function __construc() {
      $this->pcParam = null;
   }

   public function omConsultarEventos() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = $loSql->paError;
         return false;
      }
      $llOk = $this->mxConsultarEventos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParam() {
      if (!isset($this->paData["CUNIACA"])) {
         $this->paDatos = ["error" => "UNIDAD ACADEMICA NO DEFINIDA"];
         return false;
      }
      return true;
   }

   protected function mxConsultarEventos($p_oSql) {
      $lcUniAca = $this->paData["CUNIACA"];

      $lcSql = "SELECT cIdEvNo, cTipo, cTitulo, cConten, cDescri, cUniAca, cAcaUni, dEvento, cHora, cLugar, cLink, tGenera, cEstado
                  FROM a04devn WHERE cEstado = 'A' AND CTIPO = 'E' 
                  AND cUniAca IN ('$lcUniAca', '00', 'ZA') AND (dVencim > NOW() OR NOW() - tGenera < '30 days')
                  ORDER BY dEvento DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->paDatos = ["error" => "ERROR AL CONSULTAR EVENTOS"];
         return false;
      }
      $laTmp = [];
      while ($laFila = $p_oSql->fetch($RS)) {
         $laTmp[] = [
            "CIDEVNO" => $laFila[0],
            "CTIPO" => $laFila[1],
            "CTITULO" => $laFila[2],
            "CCONTEN" => $laFila[3],
            "CDESCRI" => $laFila[4],
            "CUNIACA" => $laFila[5],
            "CACAUNI" => $laFila[6],
            "DEVENTO" => $laFila[7],
            "CHORA" => $laFila[8],
            "CLUGAR" => $laFila[9],
            "CLINK" => $laFila[10],
            "TGENERA" => $laFila[11],
            "CESTADO" => $laFila[12],
         ];
      }
      $this->paDatos = ["data" => $laTmp];
      return true;
   }
}

?>
