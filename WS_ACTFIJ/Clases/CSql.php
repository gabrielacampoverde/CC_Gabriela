<?php
// Conexion a Base de Datos
class CSql {
   public $paError, $pnNumRow;
   protected $h;

   public function __construct() {
      $this->paError = null;
      $this->pnNumRow = 0;
   }

   public function omConnect($lnFlag = 0) {
      $lcConBase = "host=localhost   port=5432 user=postgres password=postgres";
      if ($lnFlag == 1) {
         $lcConStr = "$lcConBase dbname=UCSMINS";
      } elseif ($lnFlag == 2) {
         $lcConStr = "$lcConBase dbname=UCSMASBANC";
      } elseif ($lnFlag == 3) {
         $lcConStr = "$lcConBase dbname=UCSMXP";
      } else {
         $lcConStr = "host=localhost dbname=UCSMERP port=5432 user=postgres password=postgres";
      }
      //print_r($lcConStr);
      try {
         $this->h = pg_connect($lcConStr);
      } catch (Exception $e) {
         $this->paError = [
            "ERROR" => "NO SE PUDO CONECTAR A LA BASE DE DATOS",
         ];
         return false;
      }

      $this->omExec("BEGIN");
      return true;
   }
  
   public function omExec($p_cSql) {
      $lcSql = substr(strtoupper(trim($p_cSql)), 0, 6);
      if ($lcSql === "SELECT") {
         $this->pnNumRow = 0;
         $RS = pg_query($this->h, $p_cSql);
         if (!$RS) {
            $this->paError = ["ERROR" => "ERROR AL EJECUTAR COMANDO SQL"];
            return false;
         }
         $this->pnNumRow = pg_num_rows($RS);
         return $RS;
      } else {
         $RS = pg_query($this->h, $p_cSql);
         if (pg_affected_rows($RS) == 0) {
            if (!$RS) {
               $this->paError = [
                  "ERROR" => "LA OPERACCION NO AFECTO A NINGUNA FILA",
               ];
               return false;
            }
         }
         return true;
      }
   }

   public function fetch($RS) {
      return pg_fetch_row($RS);
   }

   public function rollback() {
      $this->omExec("ROLLBACK");
   }

   public function omDisconnect() {
      $this->omExec("COMMIT");
      pg_close($this->h);
   }
}

?>
