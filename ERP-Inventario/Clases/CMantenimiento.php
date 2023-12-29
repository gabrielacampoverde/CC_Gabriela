<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CMantenimiento extends CBase {

   public $paData, $paDatos, $paEstado, $paSexo, $paUnidad, $paTipArt, $paGrupo, $paTipCue, $paTipAct, $paPeriodo, $paFueFin, $paCodigo, $paCuenta;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paEstado = $this->paSexo = $this->paUnidad = $this->paTipArt = $this->paGrupo = $this->paTipCue = $this->paTipAct = $this->paPeriodo = $this->paFueFin = $this->paCodigo = $this->paRubros = $this->paDetRub = $this->paCuenta = null;
   }

   //-------------------------------------------------
   //Mantenimiento de Roles Del Sistema ADM1010
   //GCH 18-07-2023
   //--------------------------------------------------
   public function omInitMantenimientoRoles() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMantenimientoRoles($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMantenimientoRoles($p_oSql) {
      #TRAER TABLA DE ESTADOS DE LOS ROLES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '041'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS [S01TTAB.041]";
         return false;
      }
      #TRAER TODOS LOS ROLES
      $lcSql = "SELECT cCodRol, cDescri, cDesCor, cEstado FROM S02TROL ORDER BY cCodRol";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODROL' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2], 'CESTADO' => $laFila[3]];
         $i++;
      }
      return true;
   }

   //-------------------------------------------------
   //Grabar Roles ADM1010
   //GCH 18-07-2023
   //--------------------------------------------------
   public function omGrabarRol() {
      $llOk = $this->mxValParamRol();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarRol($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamRol() {
      if (strlen($this->paData['CCODROL']) > 3 || strlen($this->paData['CCODROL']) < 3) {
         $this->pcError = 'CODIGO DE ROL NO DEFINIDO O ES MAYOR A 3 CARACTERES';
         return false;
      }
      return true;
   }

   protected function mxGrabarRol($p_oSql) {
      $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
      $this->paData['CDESCOR'] = strtoupper($this->paData['CDESCOR']);
      $this->paData['CCODROL'] = strtoupper($this->paData['CCODROL']);
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_S02TROL_1('$lcJson')";
      //print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //-------------------------------------------------
   //ADM1020
   //Mantenimiento de Opciones Del Sistema 
   //GCH 18-07-2023
   //--------------------------------------------------
   public function omInitMantenimientoOpciones() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMantenimientoOpciones($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMantenimientoOpciones($p_oSql) {
      #TRAER TABLA DE ESTADOS DE OPCIONES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '041'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS [S01TTAB.041]";
         return false;
      }
      #TRAER TODOS LOS OPCIONES DEL SISTEMA
      $lcSql = "SELECT cCodOpc, cDescri, cEstado, cImage FROM S02TOPC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODOPC' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2], 'CIMAGE' => $laFila[3]];
      }
      return true;
   }

   //-------------------------------------------------
   //Buscar las opciones 
   //GCH 18-07-2023
   //--------------------------------------------------
   public function omBuscarOpcion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarOpcion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarOpcion($p_oSql) {
      # Cargar opciones que cumplen criterio
      $lcDescri = str_replace(' ','%', strtoupper($this->paData['CBUSOPC']));
      $lcSql = "SELECT cCodOpc, cDescri, cEstado, cImage FROM S02TOPC WHERE ( UPPER(cCodOpc) LIKE '$lcDescri%' OR cDescri LIKE '%$lcDescri%') ORDER BY cCodOpc, cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODOPC' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2], 'CIMAGE' => $laFila[3]];
      }
      return true;
   }

   //-------------------------------------------------
   //#Grabar las opciones
   //GCH 18-07-2023
   //-------------------------------------------------
   public function omGrabarOpciones() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarOpciones($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarOpciones($p_oSql) {
      $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_S02TOPC_1('$lcJson')";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //-------------------------------------------------
   //ADM1030
   //Mantenimiento de MODULOS
   //GCH 19-07-2023
   //--------------------------------------------------
   public function omInitMantenimientoModulo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMantenimientoModulo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMantenimientoModulo($p_oSql) {
      #TRAER TABLA DE ESTADOS DE OPCIONES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '041'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS [S01TTAB.041]";
         return false;
      }
      #TRAER TODOS LOS MODULOS
      $lcSql = "SELECT cCodMod, cNombre, cEstado FROM S02TMOD order by cCodMod DESC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODMOD' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CESTADO' => $laFila[2]];
      }
      return true;
   }

   //-------------------------------------------------
   //Grabar Roles ADM1010
   //GCH 18-07-2023
   //--------------------------------------------------
   public function omGrabarModulo() {
      $llOk = $this->mxValParamModulo();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarModulo($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamModulo() {
      if (strlen($this->paData['CCODMOD']) > 3 || strlen($this->paData['CCODMOD']) < 3) {
         $this->pcError = 'CODIGO DE MODULO NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxGrabarModulo($p_oSql) {
      $this->paData['CNOMBRE'] = strtoupper($this->paData['CNOMBRE']);
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT p_s02tmod_1('$lcJson')";
      //print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }
   
   //-------------------------------------------------
   // ADM1040
   // Traer Opciones de un Rols 
   // GCH 19-07-2023
   //--------------------------------------------------
   public function omBuscarOpcRol() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarOpcRol($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarOpcRol($p_oSql) {
      #TRAER OPCIONES
      $lcBusOpcRol = str_replace(' ','%', strtoupper($this->paData['BUSOPCROL']));
      $lcSql = "SELECT B.cCodOpc, B.cDescri, B.cEstado FROM S02POPC A INNER JOIN S02TOPC B ON A.cCodOpc = B.cCodOpc WHERE A.cCodRol = '$lcBusOpcRol'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODOPC' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2]];
         $i++;
      }
      return true;
   }

   //-------------------------------------------------
   // TGRABAR ROL-OPCION
   // GCH 19-07-2023
   //--------------------------------------------------
   public function omGrabarRolOpc() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarRolOpc($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarRolOpc($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_S02POPC_1('$lcJson')";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //-------------------------------------------------
   // PANTALLA ADM1050
   // Buscar usuario y cargar sus datos
   // 2023-07-20 GCH Creacion
   //-------------------------------------------------
   public function omBuscarUsuario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarUsuario($p_oSql) {
      # Cargar articulos que cumplen criterio
      #SELECT * FROM V_S01TUSU_1
      $lcBusUsu = str_replace(' ','%', strtoupper($this->paData['CBUSUSU']));
      $lcSql = "SELECT cCodUsu, cNroDni, cNombre, cEstado, cNivel, cCargo FROM V_S01TUSU_1 WHERE (cNombre LIKE '%$lcBusUsu%' OR cNroDni = '$lcBusUsu' OR cCodUsu = '$lcBusUsu') ORDER BY cCodUsu";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODUSU' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2], 'CESTADO' => $laFila[3], 'CNIVEL' => $laFila[4], 'CCARGO' => $laFila[5]];
      }
      #Cargar Estados de los Usuarios
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '041'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR ESTADOS DE USUARIOS S01TTAB[041]";
         return false;
      }
      #Cargar Cargos de los Usuarios
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '095'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paCargo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CARGOS DE USUARIOS S01TTAB[095]";
         return false;
      }
      #Cargar Niveles de los Usuarios
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '107'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paNivel[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR NIVELES DE USUARIOS S01TTAB[095]";
         return false;
      }
      return true;
   }

   //------------------------------------------
   // Mantenimiento Usuario Roles ADM1210
   // Traer Usuarios de un Rol
   //-------------------------------------------
   public function omBuscarUsuarioRol() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarUsuarioRol($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarUsuarioRol($p_oSql) {
      #TRAER USUARIOS
      $lcBusUsuRol = str_replace(' ','%', strtoupper($this->paData['BUSUSUROL']));
      $lcSql = "SELECT DISTINCT cCodRol, cDesRol, cDCoRol, cEstRol FROM V_S02TUSU WHERE CCODUSU = '$lcBusUsuRol'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODROL' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2], 'CESTADO' => $laFila[3]];
         $i++;
      }
      return true;
   }

   //------------------------------------------
   // GRABAR ROL USUARIO 
   //------------------------------------------
   public function omGrabarRolUsu() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarRolUsu($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarRolUsu($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_S02PROL_1('$lcJson')";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //------------------------------------------
   // Buscar Usuario Roles
   //------------------------------------------
   public function omBuscarRol() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarRol($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarRol($p_oSql) {
      #TRAER USUARIOS
      $lcBusRol = str_replace(' ','%', strtoupper($this->paData['CBUSROL']));
      $lcSql = "SELECT cCodRol, cDescri, cDesCor, cEstado FROM S02TROL WHERE (cCodRol = '$lcBusRol' OR cDescri LIKE '%$lcBusRol%')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODROL' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2], 'CESTADO' => $laFila[3]];
      }
      return true;
   }

   //-----------------------------------------
   // Pantalla ADM 1060
   // Mantenimiento de modulo - roles
   // GCH 2023-07-20   
   //-----------------------------------------
   public function omInitMantenimientoModulosRol() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarModulosRol($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarModulosRol($p_oSql) {
      #TRAER OPCIONES
      $lcSql = "SELECT cCodMod, cNombre, cEstado FROM S02TMOD order by cCodMod DESC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODMOD' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CESTADO' => $laFila[2]];
      }
      return true;
   }

   //--------------------------------------------------
   // Traer Opciones de un Modulo
   // GCH 19-07-2023
   //--------------------------------------------------
   public function omBuscarOpcMod() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarOpcMod($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarOpcMod($p_oSql) {
      #TRAER OPCIONES
      $lcBusOpcRol = str_replace(' ','%', strtoupper($this->paData['BUSOPCMOD']));
      $lcSql = "SELECT B.cCodRol, B.cDescri,B.cDesCor, A.cEstado FROM s02pmod A INNER JOIN S02TROL B ON B.CCODROL = A.CCODROL WHERE CCODMOD = '$lcBusOpcRol'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODROL' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2], 'CESTADO' => $laFila[3]];
         $i++;
      }
      return true;
   }

   //-------------------------------------------------
   //Buscar roles
   //GCH 18-07-2023
   //--------------------------------------------------
   public function omBuscarRoles() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarRoles($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarRoles($p_oSql) {
      # Cargar opciones que cumplen criterio
      $lcDescri = str_replace(' ','%', strtoupper($this->paData['CBUSOPC']));
      $lcSql = "SELECT cCodRol, cDescri, cDesCor, cEstado FROM S02TROL WHERE ( UPPER(cCodRol) LIKE '$lcDescri%' OR cDescri LIKE '%$lcDescri%') ORDER BY cCodRol, cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODROL' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2], 'CESTADO' => $laFila[3]];
      }
      return true;
   }

   //-------------------------------------------------
   // GRABAR MODULO - ROL
   // GCH 19-07-2023
   //--------------------------------------------------
   public function omGrabarModRol() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarModRol($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarModRol($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT p_s02pModRol('$lcJson')";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //----------------------------------------------------
   // Mantenimiento Usuarios Cambio de Clave ADM1070
   // 2023-07-21 Creacion
   //----------------------------------------------------
   public function omTraerDatosUsusuario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTraerDatosUsusuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxTraerDatosUsusuario($p_oSql) {
      #TRAER DATOS DEL USUARIO
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cNroDni, B.cNombre, B.cEstado FROM S01TUSU A INNER JOIN S01MPER B ON A.cNroDni = B.cNroDni WHERE A.cCodUsu = '$lcCodUsu'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = "CODIGO DE USUARIO NO EXISTE";
         return false;
      }
      $this->paData = ['CCODUSU' => $lcCodUsu, 'CNRODNI' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CESTADO' => $laFila[2]];
      return true;
   }

   //-----------------------------
   // Guardar cambio de clave
   // 2023-07-21 GCH
   //---------------------------------
   public function omCambiarClave() {
      $llOk = $this->mxValParamCambiarClave();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCambiarClave($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCambiarClave() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      } elseif (empty($this->paData['CCLAVE1'])) {
         $this->pcError = 'NUEVA CLAVE NO DEFINIDA';
         return false;
      } elseif ($this->paData['CCLAVE1'] != $this->paData['CCLAVE2']) {
         $this->pcError = 'NUEVA CLAVE (1) Y CLAVE (2) NO COINCIDEN';
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
         $this->pcError = 'CLAVE ACTUAL NO DEFINIDA';
         return false;
      }
      return true;
   }

   protected function mxCambiarClave($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_S01MPER_6('$lcJson')";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      return true;
   }

   //----------------------------------------------------
   // Mantenimiento Usuarios Cambio de Clave ADM1080
   // 2023-07-21 Creacion
   //----------------------------------------------------
   public function omTraerDatos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTraerDatos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxTraerDatos($p_oSql) {
      #TRAER DATOS DEL USUARIO
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cNroDni, B.cNombre, B.cEstado, B.cNroCel, B.cEmail  FROM S01TUSU A INNER JOIN S01MPER B ON A.cNroDni = B.cNroDni WHERE A.cCodUsu = '$lcCodUsu'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = "CODIGO DE USUARIO NO EXISTE";
         return false;
      }
      $this->paData = ['CCODUSU' => $lcCodUsu, 'CNRODNI' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CESTADO' => $laFila[2], 'CNROCEL' => $laFila[3], 'CEMAIL' => $laFila[4]];
      //print_r($this->paData);
      return true;
   }

}