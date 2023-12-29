<?php

require_once 'Clases/CBase.php';
require_once 'Clases/CSql.php';

class CAlmacen extends CBase {

   public $paData, $paDatos, $laData, $laDatos;

   public function __construct()
   {
      parent::__construct();
      $this->paData = $this->laData = null;
      $this->paDatos = $this->laDatos = [];
   }


   public function omListarArticulosAlmacen() {
      $llOk = $this->mxValListarArticulosAlmacen();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSqlIns = new CSql();
      $llOk = $loSqlIns->omConnect();
      if (!$llOk) {
         $this->pcError = $loSqlIns->pcError;
         return false;
      }
      // Listar solicitudes del trabajador
      $llOk = $this->mxListarArticulosAlmacen($loSqlIns);
      $loSqlIns->omDisconnect();
      return $llOk;
   }
   protected function mxValListarArticulosAlmacen() {
      if (!isset($this->paData['CARTBUS']) || $this->paData['CARTBUS'] == '') {
         $this->pcError = 'VALOR DE BÚSQUEDA ES INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxListarArticulosAlmacen($poSql) {
      $lcSql = "SELECT B.CDESCRI, B.CCODART, A.NSTOCK, A.NCOSPRO, D.CDESCRI, C.CCODALM, C.CDESCRI FROM E03PALM A
                INNER JOIN E01MART B ON A.CCODART = B.CCODART
                INNER JOIN E03MALM C ON A.CCODALM = C.CCODALM
                LEFT JOIN V_S01TTAB D ON D.CCODIGO = B.CUNIDAD AND D.CCODTAB = '074'
                WHERE  (B.CDESCRI LIKE '%{$this->paData['CARTBUS']}%' OR A.CCODART LIKE '%{$this->paData['CARTBUS']}%') 
                    AND NSTOCK > 0 AND A.CESTADO <> 'X' ORDER BY B.CDESCRI";
      $RS = $poSql->omExec($lcSql);
      $this->paDatos = [];
      while($laFila = $poSql->fetch($RS)) {
         $this->paDatos[] = ['CARTDES' => $laFila[0], 'CCODART' => $laFila[1], 'NSTOCK' => $laFila[2], 'NCOSPRO' => $laFila[3],
            'CUNIDAD' => $laFila[4], 'CCODALM' => $laFila[5], 'CNOMALM' => $laFila[6]];
      }
      return true;
   }


}
