<?php

define("FIREBASE_API_KEY", "AIzaSyCs4oxXXCRVYeKsV_dhn13n1dyWAE1ssTo");

class CBase {
   public $pcError;
   public $loSql;

   function __construct() {
      $this->pcError = null;
      $this->loSql = null;
   }
}

class CDate extends CBase {
   public $date;
   public $days;

   public function valDate($p_dFecha) {
      $laFecha = explode("-", $p_dFecha);
      $llOk = checkdate(
         (int) $laFecha[1],
         (int) $laFecha[2],
         (int) $laFecha[0]
      );
      if (!$llOk) {
         $this->pcError = "FORMATO DE FECHA INVALIDA";
      }
      return $llOk;
   }

   public function add($p_dFecha, $p_nDias) {
      $llOk = $this->valDate($p_dFecha);
      if (!$llOk) {
         return false;
      }
      if (!is_int($p_nDias)) {
         $this->pcError = "PARAMETRO DE DIAS ES INVALIDO";
         return false;
      } elseif ($p_nDias >= 0) {
         $lcDias = " + " . $p_nDias . " days";
      } else {
         $p_nDias = $p_nDias * -1;
         $lcDias = " - " . $p_nDias . " days";
      }
      $this->date = date("Y-m-d", strtotime($p_dFecha . $lcDias));
      return true;
   }

   public function diff($p_dFecha1, $p_dFecha2) {
      $llOk = $this->valDate($p_dFecha1);
      if (!$llOk) {
         return false;
      }
      $llOk = $this->valDate($p_dFecha2);
      if (!$llOk) {
         return false;
      }
      $this->days = (strtotime($p_dFecha1) - strtotime($p_dFecha2)) / 86400;
      $this->days = floor($this->days);
      return true;
   }
}

function fxHeader($p_cLocation, $p_cMensaje = "") {
   if (empty($p_cMensaje)) {
      $lcScript = "window.location='$p_cLocation';";
   } else {
      $lcScript = "alert('$p_cMensaje');window.location='$p_cLocation';";
      //$lcScript = "window.location='$p_cLocation';alert('$p_cMensaje');";
   }
   echo "<script>" . $lcScript . "</script>";
}

function right($lcCadena, $count) {
   return substr($lcCadena, ($count * -1));
}

function left($lcCadena, $count) {
   return substr($lcCadena, 0, $count);
}

function fxCorrelativo($p_cCodigo) {
   $lcCodigo = $p_cCodigo;
   $i = strlen($p_cCodigo) - 1;
   while ($i >= 0) {
       $lcDigito = substr($p_cCodigo, $i, 1);
       if ($lcDigito == '9') {
          $lcDigito = 'A';
       } elseif ($lcDigito < '9') {
          $lcDigito = strval(intval($lcDigito) + 1);
       } elseif ($lcDigito < 'Z') {
          $lcDigito = chr(ord($lcDigito) + 1);
       } elseif ($lcDigito == 'Z') {
          $lcDigito = '0';
       }
       $lcCodigo[$i] = $lcDigito;
       if ($lcDigito != '0') {
          break;
       }
       $i--;
   }
   return $lcCodigo;
}

?>
