<?php
// Conexion a Base de Datos
class CSql {
   public $pcError, $pnNumRow;
   protected $h;
 
   public function __construct() {
      $this->pcError = null;
      $this->pnNumRow = 0;
   }

   public function omDisconnect() {
      $this->omExec("COMMIT;");
      pg_close($this->h); 
   }
 
   public function omConnect($lnFlag = 0) {
      if ($lnFlag == 1) {
         $lcConStr = "host=localhost dbname=UCSMListener port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 2) {
         $lcConStr = "host=localhost dbname=UCSMINS port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 3) {
         $lcConStr = "host=localhost dbname=UCSMASBANC port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 4) {
         $lcConStr = "host=localhost dbname=UCSMFactElec port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 5) {
         $lcConStr = "host=localhost dbname= port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 7) {
         $lcConStr = "host=localhost dbname=UCSMERP_DW port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 8) {
         $lcConStr = "host=localhost dbname=UcsmFactElec port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 10) {
         $lcConStr = "host=localhost dbname=ISPSMERP port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 11) {
         $lcConStr = "host=localhost dbname=ISPSMFACT port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 12) {
         $lcConStr = "host=localhost dbname=UCSMACR port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 13) {
         $lcConStr = "host=localhost dbname=UCSMAFJ port=5432 user=postgres password=postgres";
      } else if ($lnFlag == 14) {
         $lcConStr = "host=localhost dbname=UCSMDEEP port=5432 user=postgres password=postgres";
      } else {
         $lcConStr = "host=localhost dbname=UCSMERP port=5432 user=postgres password=postgres";
      }
      try {
         @$this->h = pg_connect($lcConStr);
      } catch (Exception $ex) {
         $this->pcError = "No se pudo conectar a la base de datos";
         return false;
      }
      $this->omExec("BEGIN;");
      return true;
   }

   public function omExec($p_cSql) {
      $lcSql = substr(strtoupper(trim($p_cSql)), 0, 6);
      if ($lcSql === "SELECT") {
         $this->pnNumRow = 0;
         $RS = pg_query($this->h, $p_cSql);
         if (!($RS)) {
            $this->pcError = "Error al ejecutar comando SQL";
            return false;
         }
         $this->pnNumRow = pg_num_rows($RS);
         return $RS;
      } else {
         @$RS = pg_query($this->h, $p_cSql);
         if (pg_affected_rows($RS) === 0)                                                 
            if (!($RS)) {
               $this->pcError = "La operacion no afecto a ninguna fila";
               return false;
            }
         return true;
      }
   }

   public function fetch($RS) {
      return pg_fetch_row($RS);     
   }
   
   public function rollback() {
      $this->omExec("ROLLBACK;");
   }
}

?>