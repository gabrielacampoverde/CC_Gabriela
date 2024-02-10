<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CLogin extends CBase {
   public $paData, $paDatos;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = null;
   }

   // Iniciar sesion Generico
   public function omIniciarSesion() {
      $llOk = $this->mxValInicioSesion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxIniciarSesion($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = !$laFila[0]
         ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}'
         : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos["ERROR"])) {
         $this->pcError = $this->paDatos["ERROR"];
         return false;
      }
      return true;
   }

   protected function mxValInicioSesion() {
      if (empty($this->paData["CNRODNI"])) {
         $this->pcError = "DNI NO DEFINIDO";
         return false;
      } elseif (empty($this->paData["CCLAVE"])) {
         $this->pcError = "CONTRASEÑA NO DEFINIDA";
         return false;
      }
      return true;
   }

   // Iniciar sesion Administrador - Validar IP
   public function omIniciarSesionIP() {
      if (empty($this->paData["CTERMIP"])) {
         $this->pcError = "IP DE CONEXIÓN NO DEFINIDA";
         return false;
      }
      $llOk = $this->mxValInicioSesion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesion($loSql);

      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxIniciarSesionIP($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxIniciarSesionIP($p_oSql) {
      if ($this->paDatos["CCODUSU"] === "*") {
         $this->pcError = "USUARIO NO EXISTE";
         return false;
      }
      $lcTermIp = $this->paData["CTERMIP"];
      //*** BORRAR(TEST) *******
      // $lcTermIp = '10.0.130.15';
      return true;
      //return true;
      //************************
      $lcUniAca = $this->paDatos["CUNIACA"];
      $lcSql =
         "SELECT TRIM(cTermIp) FROM S01TTER WHERE CCODOFI IN (\'00\',\'" .
         $lcUniAca .
         "\') ORDER BY cTermId";
      $lcSql = str_replace("\'", "'", $lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      if (empty($R1)) {
         $this->pcError = "ERROR AL EJECUTAR COMANDO BASE DE DATOS";
         return false;
      }
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         if (strpos($lcTermIp, $laFila[0]) !== false) {
            return true;
         }
         $i = $i + 1;
      }
      if ($i == 0) {
         $this->pcError = "NO EXISTE UNA LISTA DE CONEXIÓN PREDEFINIDA";
         return false;
      }
      $this->pcError = "LA IP NO PERTENECE A LA RED PERMITIDA";
      return false;
   }
}
?>
