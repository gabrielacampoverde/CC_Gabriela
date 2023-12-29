<?php
//      ECHO $lcSql .'<BR>';
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CMenu extends CBase {

   public $paData,$paDatos,$paRoles,$paOpcion,$paNotifi,$pnCanNot,$paCenCos,$pcPeriod;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paRoles = $this->paOpcion = $this->paNotifi = $this->pnCanNot = 
      $this->paCenCos = null;
      $this->pcPeriod = '';
   }
   
   public function omUpdateNotificacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxUpdateNotificacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxUpdateNotificacion($p_oSql) {
      $lcNroNot = $this->paData['CNRONOT'];
      $lcSql = "UPDATE E01MNOT SET cEstado = 'B' WHERE cNroNot = '$lcNroNot'";
      $llOk = $p_oSql->omExec($lcSql);
      if ($llOk)
      {
         return true;
      }
      return false;
   }

   # ------------------------------------------------------------------------
   # CANTIDAD DE SOLICITUDES PENDIENTES POR LA OFICINA DE MESA DE PARTES
   # 2021-01-16 APR Creacion
   # ------------------------------------------------------------------------
   public function omCargarSolicitudesPendientesMesaPartes() {
      $llOk = $this->mxValParamCargarSolicitudesPendientesMesaPartes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarSolicitudesPendientesMesaPartes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCargarSolicitudesPendientesMesaPartes() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CÓDIGO DE USUARIO INV�?LIDO O NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarSolicitudesPendientesMesaPartes($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      # TRAE LA CANTIDAD DE PENDIENTES DE MESA DE PARTES
      $lcSql = "SELECT COUNT(*) FROM T05DDOC WHERE cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $this->paCanMen = $p_oSql->fetch($RS);
      # TRAE LA CANTIDAD DE PENDIENTES DE CADA USUARIO
      $lcSql = "SELECT COUNT(*) FROM T05DDOC WHERE cUsuDes = '$lcCodUsu' and cEstado = 'B'";
      $RS = $p_oSql->omExec($lcSql);
      $this->paNotEnc = $p_oSql->fetch($RS);
      return true;
   }

   # --------------------------------------------------------------------
   # CARGAR CANTIDAD DE MENSAJES EN BANDEJAS DE ENTRADA - MESA DE PARTES
   # 2020-05-25 APR Creacion
   # --------------------------------------------------------------------
   public function omCargarMensajes() {
      $llOk = $this->mxValParamCargarMensajes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarMensajes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCargarMensajes() {
      if (!isset($this->paData['CCODUSU']) || strlen(trim($this->paData['CCODUSU'])) != 4) {
         $this->pcError = "CÓDIGO DE USUARIO INV�?LIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarMensajes($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      # TRAE LA CANTIDAD DE PENDIENTES DE CADA USUARIO
      $lcSql = "SELECT COUNT(*) FROM T05DDOC WHERE cUsuDes = '$lcCodUsu' and cEstado = 'B'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMensaje = ['CCANMEN' => $laFila[0]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO TIENE MENSAJES PENDIENTES";
         return false;
      }
      # TRAER LA DESCRIPCION DE CADA PENDIENTE DEL USUARIO
      $lcSql = "SELECT cDescri, nCantid, cLink FROM F_S00DDOC_1('$lcCodUsu')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDesMen[] = ['CDESCRI' => $laFila[0], 'NCANTID' => $laFila[1], 'CENLACE' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO TIENE MENSAJES PENDIENTES";
         return false;
      }
      return true;
   }

   public function omInitMenu() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMenu($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMenu($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      # TRAE CENTROS DE COSTO DE USUARIO
      $lcSql = "SELECT DISTINCT cCenCos, cDesCen FROM V_S01PCCO WHERE cCodUsu = '$lcCodUsu' AND cEstado = 'A' ORDER BY cDesCen";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCCO' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO TIENE CENTRO(S) DE COSTO ASIGNADO(S)";
         return false;
      }
      # TRAE ROLES ASIGNADOS A USUARIO
      $lcSql = "SELECT DISTINCT C.cCodRol, C.cDescri, TRIM(c.cNumPla)
                  FROM S01TUSU A
                  JOIN S01PROL B ON B.cCodUsu = A.cCodUsu
                  JOIN S01MROL C ON C.cCodRol = B.cCodRol 
                  WHERE B.cCodUsu = '$lcCodUsu' AND C.cEstado = 'A' ORDER BY C.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRoles[] = ['CCODROL' => $laFila[0], 'CDESROL' => $laFila[1], 'CNUMPLA' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY ROLES ASIGNADOS A ESTE USUARIO";
         return false;
      }
      # RECUPERACION DE NOTIFICACIONES
      $lcSql = "SELECT cNroNot, cMensaj, cEnlace FROM E01MNOT WHERE cCodUsu = '$lcCodUsu' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $this->pnCanNot = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paNotifi[] = ['CNRONOT' => $laFila[0], 'CMENSAJ' => $laFila[1], 'CENLACE' => $laFila[2]];
         $this->pnCanNot++;
      }
      # TRAE PERIODO ACTIVO DEL SISTEMA
      $lcSql = "SELECT cPeriod FROM S01TPER WHERE cTipo = 'S0'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE PUDO CARGAR PERIODO ACTIVO DEL SISTEMA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->pcPeriod = $laFila[0];
      # TRAE CANTIDAD DE USUARIOS DEL SISTEMA
      $lcSql = "SELECT COUNT(*) FROM V_S01TUSU_1 WHERE cNroDni NOT LIKE 'X%' AND cEstado <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE PUDO CARGAR PERIODO ACTIVO DEL SISTEMA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->pcCanUsu = $laFila[0];
      # TRAE CANTIDAD DE ROLES DEL SISTEMA
      $lcSql = "SELECT COUNT(*) FROM S01MROL WHERE cEstado NOT IN ('X','I')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE PUDO CARGAR PERIODO ACTIVO DEL SISTEMA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->pcCanRol = $laFila[0];
      # TRAE CANTIDAD DE DESARROLLADORES DEL SISTEMA
      $lcSql = "SELECT COUNT(*) FROM V_S01TUSU_1 WHERE cNivel = 'AA' AND cEstado NOT IN ('X','I')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE PUDO CARGAR PERIODO ACTIVO DEL SISTEMA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->pcDesarr = $laFila[0];
      return true;
   }  

   # -----------------------------------------------------
   # TRAER DATOS DE USUARIO PARA EDICIÓN EN BASE DE DATOS
   # 2022-09-28 APR Creacion
   # -----------------------------------------------------
   public function omInitUsuario() {
      $llOk = $this->mxValInitUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitUsuario() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO O NO DEFINIDO';
         return false;
      } elseif (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'DOCUMENTO DE IDENTIDAD INVÁLIDO O NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxInitUsuario($p_oSql) {
      $lcSql = "SELECT cNroDni, cNombre, cEmail, cNroCel, cCodUsu, cEmailp, cDesNiv FROM V_S01TUSU_1 WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $laFila = $p_oSql->fetch($R1);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "DOCUMENTO DE IDENTIDAD NO EXISTE EN LA BASE DE DATOS";
         return false;
      }
      $this->paData = ['CNRODNI' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),   'CEMAIL' => $laFila[2],
                       'CNROCEL' => $laFila[3], 'CUSUCOD' => $laFila[4], 'CEMAILP' => $laFila[5], 'CDESNIV'=> $laFila[6]];
      return true;
   }

   # ---------------------------------------------------
   # ACTUALIZAR INFORMACIÓN DE USUARIO EN BASE DE DATOS 
   # 2022-09-28 APR Creacion
   # ---------------------------------------------------
   public function omActualizarDatosUsuario() {
      $llOk = $this->mxValActualizarDatosUsuario();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarDatosUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValActualizarDatosUsuario() {
      if (!isset($this->paData['CNRODNI']) || empty(trim($this->paData['CNRODNI']) || $this->paData['CNRODNI'] == null)) {
         $this->pcError = "CODIGO DE ALUMNO PROPUESTO NO VALIDO";
         return false;
      } elseif (!isset($this->paData['CEMAIL']) || empty($this->paData['CEMAIL']) || $this->paData['CEMAIL'] == null) {
         $this->pcError = 'EMAIL NO POSEE LOS REQUISITOS BASICOS @ O .COM';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || empty($this->paData['CUSUCOD']) || $this->paData['CUSUCOD'] == null) {
         $this->pcError = 'ERROR CODIGO DE USUARIO';
         return false;
      } elseif (empty($this->paData['CNROCEL'])) {
         $this->pcError = 'NUMERO DE CELULAR NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxActualizarDatosUsuario($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_S01MPER_3('$lcJson')";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }
}
?>