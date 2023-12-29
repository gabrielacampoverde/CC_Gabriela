<?php
//      ECHO $lcSql .'<BR>';
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CMenu extends CBase {

   public $paData,$paDatos,$paRoles,$paOpcion,$paNotifi,$pnCanNot,$paCenCos,$pcPeriod, $paModule;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paRoles = $this->paOpcion = $this->paNotifi = $this->pnCanNot = 
      $this->paCenCos = $this->paModule = null;
      $this->pcPeriod = '';
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
      # TRAE modulos ASIGNADOS A USUARIO
      $lcSql = "SELECT distinct a.ccodmod,a.cnombre FROM s02tmod a
               INNER JOIN s02pmod b on b.ccodmod=a.ccodmod
               INNER JOIN s02prol c on c.ccodrol=b.ccodrol
               WHERE c.ccodusu='{$this->paData['CCODUSU']}' ORDER BY a.cNombre";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paModule[] = ['CCODMOD'=> $laTmp[0], 'CDESMOD'=> $laTmp[1]];
      }
      if (count($this->paModule) == 0) {
        $this->pcError = "NO SE ENCONTRARON MODULOS PARA ESTE USUARIO";
        return false;
      } 
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

   //------------------------------
   // traer roles del usuario
   //------------------------------
   public function omInitMenuRoles() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMenuRoles($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMenuRoles($p_oSql) {
   // print_r($this->paData);
   // die;
      $this->paDatos = [];
      $lcSql = "SELECT A.CCODMOD,A.CNOMBRE,C.CCODROL,D.CDESCRI,C.CCODOPC,E.CDESCRI, trim(E.CIMAGE) FROM s02tmod A
               INNER JOIN s02pmod B ON B.CCODMOD=A.CCODMOD
               INNER JOIN S02POPC C ON C.CCODROL=B.CCODROL
               INNER JOIN s02trol D ON D.CCODROL=C.CCODROL
               INNER JOIN s02tOpc E ON E.CCODOPC=C.CCODOPC
               inner join S02PROL F ON F.CCODROL=D.CCODROL
               WHERE F.CCODUSU='{$this->paData['CUSUCOD']}' AND  A.CCODMOD = '{$this->paData['CCODMOD']}' AND  D.CESTADO = 'A'
               ORDER BY C.cCodRol, E.nOrden";
      //print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODMOD' => $laFila[0], 'CDESMOD' => $laFila[1], 'CCODROL' => $laFila[2], 'CDESROL' => $laFila[3], 'CCODOPC' => $laFila[4],
                              'CDESOPC' => $laFila[5], 'CIMAGE' => $laFila[6]];
      }
      //print_r($this->paDatos);
      if (count($this->paDatos) == 0) {
         $this->pcError = "NO HAY ROLES ASIGNADOS A ESTE USUARIO";
         return false;
      }  
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