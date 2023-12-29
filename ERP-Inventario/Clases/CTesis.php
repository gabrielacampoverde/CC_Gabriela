<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Clases/CEmail.php";

class CTesis extends CBase {

   public $paData, $paDatos, $paDetalle, $paEstado, $paTipo, $paTipTes, $paError, $paTesis, $paDocente, $paAlumno, $paEstPro, $paCargo, $paEstDic, $paUniaca, $paNotificacion, $pcFile, $paDicRol, $paDicCar;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paDetalle = $this->paEstado = $this->paTipo = $this->pcError = $this->paTesis = $this->paDocente = $this->paAlumno = $this->paEstPro = $this->paCargo = $this->paEstDic = $this->paUniaca = $this->paNotificacion = $this->paTipTes = $this->pcFile = $this->paDicRol = $this->paDicCar = $this->laUniAca = $this->paObserv = null;  
   }

   #------------------------------------------------
   # Datos de Estudiante para Presentación de Tesis
   # Creacion APR 2020-10-14
   #------------------------------------------------
	public function omInitDatosEstudiantesBachiller() {
      $llOk = $this->mxValInitDatosEstudiantesBachiller();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDatosEstudiantesBachiller($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValInitDatosEstudiantesBachiller() {
      if (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'NÚMERO DE DNI DE ALUMNO INVALIDO O NO DEFINIDO';
         return false;
      }    
      return true;
   }
   
   protected function mxInitDatosEstudiantesBachiller($p_oSql) {
      //TRAER LOS CODIGOS DE ESTUDIANTE DE DNI
      $lcSql = "SELECT CCODALU, CUNIACA, CNOMUNI FROM V_A01MALU WHERE CNRODNI = '{$this->paData['CNRODNI']}' AND CNIVEL IN ('01', '09') AND CESTADO = 'A' ORDER BY CCODALU ASC";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false && $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR: ESTIMADO USUARIO, USTED NO CUENTA CON CODIGOS DE ESTUDIANTE ACTIVOS PARA PRESENTAR SU TESIS.";
         return false; 
      }
      while ($laFila = $p_oSql->fetch($R1)){
        $this->paDatos[] = ['CCODALU' => $laFila[0], 'CUNIACA' => $laFila[1], 'CNOMUNI' => $laFila[2]]; 
      }      
      return true;
   }

   #---------------------------------------------
   # Datos de Alumno para Presentacion de Tesis
   # Creacion APR 2020-10-14
   #---------------------------------------------
	public function omInitDatosAlumnosPresentacionTesis() {
      $llOk = $this->mxValInitDatosAlumnosPresentacionTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDatosAlumnosPresentacionTesis($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValInitDatosAlumnosPresentacionTesis() {
      if (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'NÚMERO DE DNI DE ALUMNO INV�?LIDO O NO DEFINIDO';
         return false;
      }    
      return true;
   }
   
   protected function mxInitDatosAlumnosPresentacionTesis($p_oSql) {
      //TRAER LOS CODIGOS DE ALUMNOS DE DNI
      $lcSql = "SELECT CCODALU, CUNIACA, CNOMUNI FROM V_A01MALU WHERE CNRODNI = '{$this->paData['CNRODNI']}' AND CNIVEL IN ('01', '02', '03', '04', '09', '06') AND CESTADO = 'A' ORDER BY CCODALU ASC";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false && $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR: ESTIMADO USUARIO, USTED NO CUENTA CON CODIGOS DE ESTUDIANTE ACTIVOS PARA PRESENTAR SU TESIS.";
         return false; 
      }
      //alumnos sancionados arquitectura
      $laSancion = ['72936823', '72634423', '76181635', '71831502', '45260784', '46496268', '45564344', '45379030'];
         
      while ($laFila = $p_oSql->fetch($R1)){
         if (in_array($this->paData['CNRODNI'], $laSancion) AND in_array($laFila[1],['T5','41'])){
            // ALUMNOS ARQUITECTURA INHABILITADO POR 6 MESES (29/11/2022)
            $this->pcError = 'ERROR: ESTIMADO USUARIO, USTED NO CUENTA CON CODIGOS DE ESTUDIANTE HABILITADOS PARA PRESENTAR SU TESIS DE ARQUITECTURA.';
            return false;
         }
         $this->paDatos[] = ['CCODALU' => $laFila[0], 'CUNIACA' => $laFila[1], 'CNOMUNI' => $laFila[2]]; 
      }
      
      return true;
   }

   // ----------------------------------------------------------- 
   // 2019-08-01 PFC Linea de tiempo de una tesis 
   // ---------------------------------------------------------- 
   public function omLineadeTiempo(){ 
      $llOk = $this->mxValLineadeTiempo(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxLineadeTiempo($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValLineadeTiempo() { 
      if (!isset($this->paData['CPARAME']) || empty(trim($this->paData['CPARAME']) || $this->paData['CPARAME'] == null)) { 
         $this->pcError = "ID NO VALIDO"; 
         return false; 
      } 
      return true; 
   } 
 
   protected function mxLineadeTiempo($p_oSql) { 
      //Trae ultima observacion del Dictaminador 
      $lcIdTesi = $this->paData['CPARAME']; 
      $lcSql = "SELECT B.cNombre FROM T01DALU A INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu AND A.cNivel = 'P' WHERE cIdTesi = '$lcIdTesi'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      $Alumno = str_replace('/', ' ',$laFila[0]); 
      $lcSql = "SELECT A.cEstTes, A.cUsuCod, B.cNombre, A.mObserv, A.tModifi::DATE FROM T01DLOG A INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cUsuCod  WHERE A.cIdTesi = '$lcIdTesi' ORDER BY A.tModifi ASC"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $i = 0; 
      while ($laFila = $p_oSql->fetch($R1)) { 
         if($laFila[1] == '9999' || $laFila[1] == 'U666'){ 
            $tmp = $Alumno; 
         }else {$tmp = str_replace('/', ' ', $laFila[2]);} 
         $this->paDatos[] = ['CESTADO' => $laFila[0], 'CUSUCOD' => $laFila[1], 'CNOMBRE' => $tmp, 'MOBSERV' => (empty($laFila[3]))? ' ' : $laFila[3], 
                             'TMODIFI' => $laFila[4]]; 
         $i++; 
      } 
      return true; 
   } 
   // -----------------------------------------------------------
   // 2018-05-02 PFC INIT CAMBIAR CLAVE ALUMNO  Creacion ADM1370
   // -----------------------------------------------------------
   public function omInitAlumno() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAlumno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAlumno($p_oSql) {
      $lcSql = "SELECT cNroDni, cNombre, cEstado FROM S01MPER WHERE cNroDni = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = "NUMERO DE DNI NO EXISTE";
         return false;
      }
      $this->paData = ['CNRODNI' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CESTADO' => $laFila[2]];
      return true;
   }

   // -----------------------------------------------------------
   // 2018-05-02 PFC CARGA CODIGO DE ALUMNO EN LA VARIABLE DE SESION SI TIENE TESIS VIGENTE
   // -----------------------------------------------------------
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
      //Verifica si tiene plan de Tesis
      $lcSql = "SELECT A.cCodAlu FROM T01DALU A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi WHERE B.cEstado != 'X' AND A.cCodAlu IN(SELECT cCodAlu FROM V_A01MALU WHERE cNroDni = '{$this->paData['CNRODNI']}')";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->paData = ['CCODALU' => 0];  
      }
      $this->paData = ['CCODALU' => $laFila[0]];
      $lcSql = "SELECT cEstado FROM T01DDEU WHERE cEstado NOT IN ('X', 'B') AND cCodAlu ='{$this->paData['CCODALU']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $R1 = $p_oSql->fetch($R1);
      if (empty($R1[0])) {
         $this->paData['CESTADO'] = '*';  
      } else {         
         $this->paData['CESTADO'] = $R1[0];  
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2018-05-02 PFC CAMBIAR CLAVE ALUMNO  Creacion ADM1370
   // -----------------------------------------------------------
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
      if (!isset($this->paData['CNRODNI']) || empty(trim($this->paData['CNRODNI']) || $this->paData['CNRODNI'] == null)) {
         $this->pcError = 'DNI NO DEFINIDO';
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
      $lcSql = "SELECT P_S01MPER_4('$lcJson')";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2018-07-17 PFC Init Anular Plan/Proyecto
   // -----------------------------------------------------------
   public function omInitAnularPlanTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAnularPlanTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAnularPlanTesis($p_oSql) {
      $lcCodAlu = $this->paData['CCODALU'];
      $lcSql = "SELECT B.cNivel FROM T01MTES A INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi AND A.cEstPro != 'I' AND A.cEstado != 'X' AND B.cEstado != 'X' WHERE B.cCodAlu = '$lcCodAlu'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = "NO TIENE TESIS VIGENTE ACTUALMENTE";
         return false;
      // S = Secundario alumnos que pertenecen a la tesis pero no son el principal
      }elseif($laFila[0] == 'S'){
         $this->pcError = "NO TIENE PERMISO PARA ESTA OPCION";
         return false;
      // P = principal el que genera la deuda y sube el archivo
      }elseif($laFila[0] == 'P'){
         $lcSql = "SELECT cIdTesi, mTitulo, cCodAlu, cNombre FROM V_T01MTES WHERE cEstado != 'X' AND cEstPro != 'I' AND cCodAlu = '$lcCodAlu'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         if (empty($laFila[0])) {
            $this->pcError = "NO HAY DATOS DISPONIBLES EN ESTE MOMENTO";
            return false;
         }
         $this->paData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CCODALU' => $laFila[2], 'CNOMBRE' => $laFila[3]];
         return true;
      }
   }

   // -----------------------------------------------------------
   // 2018-05-02 PFC Init Datos del Usuario
   // -----------------------------------------------------------
   public function omInitUsuario() {
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

   protected function mxInitUsuario($p_oSql) {
      $lcSql = "SELECT cNroDni, cNombre, cEstado, cEmail, cNroCel, cCodUsu FROM V_S01TUSU_1 WHERE cNroDni = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = "NUMERO DE DNI NO EXISTE";
         return false;
      }
      $this->paData = ['CNRODNI' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CESTADO' => $laFila[2], 'CEMAIL' => $laFila[3],
                       'CNROCEL' => $laFila[4], 'CUSUCOD' => $laFila[5]];
      return true;
   }

   // -----------------------------------------------------------
   // 2018-05-02 PFC Actualizar datos Usuario  Creacion ADM1390
   // -----------------------------------------------------------
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

    // -----------------------------------------------------------
   // Inicio generacion de deuda para proyecto o plan de tesis
   // 2018-05-02 PFC Creacion
   // 2018-06-18 FPM Traer monto de tramite administrativo
   // -----------------------------------------------------------
   public function omInitPlan() {
      $llOk = $this->mxValParamInitPlan();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxMontoDeuda();
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitPlan($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamInitPlan() {
      if (!isset($this->paData['CCODALU']) or strlen($this->paData['CCODALU']) != 10) {
         $this->pcError = 'CODIGO DE ALUMNO NO ESTA DEFINIDO O ES INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxMontoDeuda() {
      // Conecta con UCSMINS
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      // Trae monto de traite administrativo
      $lcSql = "SELECT nMonto * 2 FROM B03TDOC WHERE cIdCate = 'PDDTRA'";
      $R1 = $loSql->omExec($lcSql);
      $laFila = $loSql->fetch($R1);
      $loSql->omDisconnect();
      if (!$R1) {
         $this->pcError = 'ERROR EN EJECUCION SENTENCIA SQL';
         return false;
      } elseif ($loSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY MONTO DEFINIDO PARA TRAMITE ADMINISTRATIVO';
         return false;
      }
      if ((float)$laFila[0] <= 0.00) {
         $this->pcError = 'MONTO DEFINIDO PARA TRAMITE ADMINISTRATIVO INVALIDO';
         return false;
      }
      $this->paData['NMONTO'] = fxNumber($laFila[0],5,2).' Soles';
      return true;
   }

   protected function mxInitPlan($p_oSql) {  
      $lcCodAlu = $this->paData['CCODALU'];    
      // Tipos de PDTs (bachiller, titulo, maestria, doctorado, etc.)

      $lcSql = "SELECT A.cTipo, C.cDescri, A.nLimAlu FROM T01PUNI A
                INNER JOIN A01MALU B ON B.cUniAca = A.cuniAca
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '143' AND SUBSTRING(C.cCodigo, 0, 1) = A.cTipo
                WHERE B.cCodAlu = '$lcCodAlu'";
      
      $R1 = $p_oSql->omExec($lcSql);
      $llOk = false;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'NLIMALU' => $laFila[2]];
         $llOk = true;
      }
      if (!$llOk) {
         $this->pcError = 'NO HAY TIPOS DE PLANES/PROYECTOS DE TESIS PARA UNIDAD ACADEMICA DE ALUMNO|';
         return false;
      }
      // Valida codigo de alumno
      $lcSql = "SELECT cNomUni, cNroDni, cNombre, cNroCel, cEmail FROM V_A01MALU WHERE cCodAlu = '$lcCodAlu'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = 'CODIGO DE ALUMNO NO EXISTE';
         return false;
      }
      $laData = ['CCODALU' => $lcCodAlu, 'NMONTO' => $this->paData['NMONTO'], 'CNOMUNI'=> $laFila[0], 'CNRODNI'=> $laFila[1], 'CNOMBRE'=> str_replace('/', ' ', $laFila[2]),
                 'CNROCEL'=> $laFila[3], 'CEMAIL'=> $laFila[4]];
      // Valida que no tenga plan de tesis vigente
      $lcSql = "SELECT A.cIdTesi FROM T01MTES A INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi AND A.cEstPro != 'I' AND A.cEstado != 'X' AND B.cEstado != 'X' WHERE B.cCodAlu = '$lcCodAlu'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!empty($laFila[0]) || $laFila[0] != null) {
         $this->pcError = 'ALUMNO TIENE PLAN DE TESIS VIGENTE';
         return false;
      }
      // Valida estado de la deuda
      $lcSql = "SELECT cNroPag, nMonto, dVencim, cEstado FROM T01DDEU WHERE cCodAlu = '$lcCodAlu' AND cEstado NOT IN ('X', 'B')";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      // Variables locales
      $lcNroPag = $laFila[0];
      $lnMonto  = $laFila[1];
      $ldVencim = $laFila[2];
      $lcEstPag = $laFila[3];
      if ($lcEstPag === null) {
         // Devuelve datos para generar deuda
         $this->paData = $laData + ['SCREEN'=> 0];
      } elseif ($lcEstPag === 'P') {
         // Devuelve datos de la deuda pendiente
         $this->paData = ['SCREEN'=> 1, 'NMONTO' => $lnMonto, 'DVENCIM' => $ldVencim, 'CNROPAG' => $lcNroPag[0].'-'.substr($lcNroPag, 1, 3).'-'.substr($lcNroPag, 4, 3).'-'.substr($lcNroPag, 7, 3)];
      } elseif ($lcEstPag === 'A') {
         // Datos del Alumno Pantalla Subida de Archivos
         $this->paData = $laData + ['SCREEN'=> 2];
      } else {
         $this->pcError = 'ESTADO DEL DETALLE DE DEUDA ERRADO [{$lcEstPag}]';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2018-05-02 PFC GRABAR PLAN DE TESIS  Creacion PLT1110
   // -----------------------------------------------------------
   public function omGrabarPlanTesis() {
      $llOk = $this->mxValGrabarPlanTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarPlanTesis($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarPlanTesis() {
      $this->paData['MTITULO'] = mb_strtoupper($this->paData['MTITULO']);
      //$this->paData['MTITULO'] = str_replace($this->paData['MTITULO'], '  ', ' ');? TITULO EN BLANCO
      if (!isset($this->paFile) || $this->paFile['size'] > 56214400 || ($this->paFile['type'] != 'application/pdf')) {
         $this->pcError = "ARCHIVO MUY PESADO O NO VALIDO";
         return false;
      //} elseif (!isset($this->paData['MTITULO']) || empty($this->paData['MTITULO']) || !preg_match("(^[a-zA-Z0-9áéíóúñ�?É�?ÓÚÑ \n\r\/\t¿?!¡(),.:;_-]+$)", $this->paData['MTITULO'])) {
      } elseif (!isset($this->paData['MTITULO']) || empty($this->paData['MTITULO']) || preg_match("([\'\"\&])", $this->paData['MTITULO'])) {
         $this->pcError = 'TITULO DE LA TESIS NO DEFINIDO, INVALIDO O CONTIENE CARACTERES ESPECIALES';
         return false;
      } elseif (!isset($this->paData['CEMAIL']) || empty($this->paData['CEMAIL']) || !filter_var($this->paData['CEMAIL'], FILTER_VALIDATE_EMAIL)) {   // OJOFPM REGEX
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CNROCEL']) || empty($this->paData['CNROCEL']) || !ctype_digit($this->paData['CNROCEL']) || strlen($this->paData['CNROCEL']) > 12) {
         $this->pcError = 'NUMERO DE CELULAR NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCODALU']) || strlen($this->paData['CCODALU']) != 10) {
         $this->pcError = 'CODIGO ALUMNO NO DEFINIDO O INVALIDO';
         return false;
      }
      $this->paData['MTITULO'] = mb_strtoupper($this->paData['MTITULO']);
      return true;
   }

   protected function mxGrabarPlanTesis($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MTES_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION SQL"}' : $laFila[0];
      $laData = json_decode($laFila[0], true);
      if (!empty($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      //NOMBRE DEL ARCHIVO
      $lcCodPdf = $laData['CIDTESI'];
      if ($this->paFile['error'] == 0) {
         $llOk = fxSubirPDF($this->paFile, 'Tesis', $lcCodPdf);
         if (!$llOk) {
            $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO';
            return false;
         }
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Actualiza Titulo de la tesis y Archivo  Creacion PLT1210
   // -----------------------------------------------------------
   public function omActualizarPlanTesis() {
      $llOk = $this->mxValActualizarPlanTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarPlanTesis($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValActualizarPlanTesis() {
      $this->paData['MTITULO'] = strtoupper($this->paData['MTITULO']);
      if (!isset($this->paFile) || $this->paFile['size'] > 56214400 || ($this->paFile['type'] != 'application/pdf')) {
         $this->pcError = "ARCHIVO MUY PESADO O NO VALIDO";
         return false;
      } elseif ($this->paFile['error'] != 0) {
         $this->pcError = "ERROR DE ARCHIVO ".$this->paFile['error'];
         return false;
      } elseif (!isset($this->paData['MTITULO']) || empty($this->paData['MTITULO']) || $this->paData['MTITULO'] == null) {
         $this->pcError = 'TITULO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCODALU']) || empty($this->paData['CCODALU']) || $this->paData['CCODALU'] == null || !ctype_digit($this->paData['CCODALU'])) {
         $this->pcError = 'CODIGO ALUMNO INV�?LIDO';
         return false;
      }
      return true;
   }

   protected function mxActualizarPlanTesis($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MTES_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION SQL"}' : $laFila[0];
      $laData = json_decode($laFila[0], true);
      if (!empty($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      //NOMBRE DEL ARCHIVO
      $lcCodPdf = $laData['CIDTESI'];
      if ($this->paFile['error'] == 0) {
         $llOk = fxSubirPDF($this->paFile, 'Tesis', $lcCodPdf);
         if (!$llOk) {
            $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO';
            return false;
         }
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Proponer Asesor Plan de Tesis Creacion PLT1210
   // -----------------------------------------------------------
   public function omPropuestaAsesor() {
      $llOk = $this->mxValPropuestaAsesor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPropuestaAsesor($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValPropuestaAsesor() {
      if ($this->paData['CCODDOC'] != '*'){
         if (!isset($this->paData['CCODDOC']) || empty($this->paData['CCODDOC']) || $this->paData['CCODDOC'] == null || !ctype_digit($this->paData['CCODDOC'])) {
            $this->pcError = "DOCENTE NO VALIDO";
            return false;
         }
      }elseif (!isset($this->paData['CCODALU']) || empty($this->paData['CCODALU']) || $this->paData['CCODALU'] == null || !ctype_digit($this->paData['CCODALU'])) {
         $this->pcError = 'ALUMNO NO VALIDO';
         return false;
      }
      return true;
   }

   protected function mxPropuestaAsesor($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MTES_4('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Estado Plan de tesis FUNCION QUE DEVUELVE JSON Creacion PLT1120
   // -----------------------------------------------------------
   public function omEstadoPlanTesisAlumno() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEstadoPlanTesisAlumno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxEstadoPlanTesisAlumno($p_oSql) {
      //Valida que tenga un Plan de Tesis
      $lcSql = "SELECT cIdTesi FROM T01DALU WHERE cEstado NOT IN ('I', 'X') AND cCodAlu = '{$this->paData['CPARAME']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!$R1) {
         $this->pcError = 'ERROR EN EJECUCION SENTENCIA SQL';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY TESIS VIGENTE';
         return false;
      }
      //Carga Estado de los Dictaminadores
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '145'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Datos del Alumno JSON
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT F_T01MTES('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $tmp = json_decode($laFila[0], true);
      $this->paData = array_change_key_case_recursive($tmp);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      //Archivo
      $lcPath1 = 'Docs/Tesis/'.$this->paData['CIDTESI'].'.pdf';
      $this->paData = $this->paData+['CARCHIV' => (file_exists($lcPath1))? 'S' : 'N'];
      //trae el cesttes
      $lcSql = "SELECT cEstTes FROM T01MTES WHERE cIdTesi = '{$this->paData['CIDTESI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $RS = $p_oSql->fetch($R1);
      $this->paData['CESTTES'] = $RS[0];
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Estado Plan de tesis FUNCION QUE DEVUELVE JSON Creacion PLT1120
   // -----------------------------------------------------------
   public function omEstadoPlanTesis2() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEstadoPlanTesis2($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxEstadoPlanTesis2($p_oSql) {
      //Carga Tipos de Usuario en el Detalle
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '223'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Datos del Alumno JSON
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT F_T01MTES_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $tmp = json_decode($laFila[0], true);
      $this->paDetalle = array_change_key_case_recursive($tmp);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      return true;
   }


   // -----------------------------------------------------------
   // 2019-05-17 PFC Buscar Tesis FUNCION QUE DEVUELVE JSON Creacion PLT2130-PLT2150-PLT3110
   // -----------------------------------------------------------
   public function omDatosPlanTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosPlanTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxDatosPlanTesis($p_oSql) {
      //Datos del Alumno JSON
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT F_T01MTES('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR":"ERROR DE EJECUCION SQL"}' : $laFila[0];
      $tmp = json_decode($laFila[0], true);
      $this->paData = array_change_key_case_recursive($tmp);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      //Archivo
      $lcPath1 = 'Docs/Tesis/'.$this->paData['CIDTESI'].'.pdf';
      $this->paData = $this->paData+['CARCHIV' => (file_exists($lcPath1))? 'S' : 'N'];
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Anular Plan de Tesis Alumno Creacion PLT1210
   // -----------------------------------------------------------
   public function omAnularPlanTesisAlumno() {
      $llOk = $this->mxValAnularPlanTesisAlumno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAnularPlanTesisAlumno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValAnularPlanTesisAlumno() {
      if (!isset($this->paData['CCODALU']) || empty(trim($this->paData['CCODALU']) || $this->paData['CCODALU'] == null)) {
         $this->pcError = "ID NO VALIDO";
         return false;
      }
      return true;
   }

   protected function mxAnularPlanTesisAlumno($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MTES_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Genera Deuda Plan de Tesis Creacion PLT1110
   // 2019-06-18 FPM
   // -----------------------------------------------------------
   public function omGenerarDeudaPlanTesis() {
      $llOk = $this->mxValGenerarDeudaPlanTesis();
      if (!$llOk) {
         return false;
      }
      // Conecta DB UCSMERP
      $loSqlE = new CSql();
      $llOk = $loSqlE->omConnect();
      if (!$llOk) {
         $this->pcError = $loSqlE->pcError;
         return false;
      }
      // Conecta DB UCSMINS
      $loSqlI = new CSql();
      $llOk = $loSqlI->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSqlI->pcError;
         return false;
      }
      // Valida deuda UCSMERP
      $llOk = $this->mxValGenerarDeudaPlanTesisERP($loSqlE);
      if (!$llOk) {
         $loSqlE->omDisconnect();
         $loSqlI->omDisconnect();
         return false;
      }
      $llOk = $this->mxGenerarDeudaPlanTesisINS($loSqlI);
      if (!$llOk) {
         $loSqlI->rollback();
         $loSqlE->omDisconnect();
         $loSqlI->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarDeudaERP($loSqlE);
      if (!$llOk) {
         $loSqlI->rollback();
         $loSqlE->rollback();
      }
      $loSqlI->omDisconnect();
      $loSqlE->omDisconnect();
      return $llOk;
   }

   protected function mxValGenerarDeudaPlanTesis() {
      if (!isset($this->paData['CCODALU']) || strlen($this->paData['CCODALU']) != 10) {
         $this->pcError = "CODIGO DE ALUMNO NO DEFINIDO O INVALIDO";
         return false;
      }
      return true;
   }
   protected function mxValGenerarDeudaPlanTesisERP($p_oSql) {
      // Verifica si tiene deuda pendiente
      $lcSql = "SELECT cNroPag FROM T01DDEU WHERE cEstado = 'P' AND cCodAlu = '{$this->paData['CCODALU']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!empty($laFila[0])){
         $this->pcError = 'TIENE DEUDA GENERADA';
         return false;
      }
      return true;
   }

   protected function mxGenerarDeudaPlanTesisINS($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_B03MDEU_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      $this->laData = json_decode($laFila[0], true);
      return true;
   }

   // -----------------------------------------------------------
   // Actualiza la deuda generarada en INS en el ERP
   // -----------------------------------------------------------
   protected function mxCargarDeudaERP($p_oSql) {
      $this->paData = $this->paData + $this->laData;
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MTES_6('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Valida Datos de los Dictaminadores - Decano Creacion PLT2130
   // -----------------------------------------------------------
   public function omValidarDatosUsuario() {
      $llOk = $this->mxValDatosUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValidarDatosUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValDatosUsuario() {
      if (!isset($this->paData['CNRODNI']) || empty($this->paData['CNRODNI']) || $this->paData['CNRODNI'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxValidarDatosUsuario($p_oSql) {
      //TRAER PERSONAS QUE ASOCIADAS A LA UNIDAD ACADEMICA DEL USUARIO
      $lcNroDni = $this->paData['CNRODNI'];
      $lcSql = "SELECT cEmail, cNroCel FROM S01MPER WHERE cNroDni = '$lcNroDni'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0]) || $laFila[0] == null || empty($laFila[1]) || $laFila[1] == null) {
         $this->pcError = 'POR FAVOR ACTUALICE SU EMAIL PODER EMPEZAR CON EL PROCESO DE EVALUACION DE PLAN DE TESIS';
         return false;
      }
      return true;
   }

   // -------------------------------------------------------------------------------------------------------
   // 2019-05-17 PFC Bandeja que muestra la asignacion de Dictaminador-Asesor-Tercer Jurado Creacion PLT2110
   // -------------------------------------------------------------------------------------------------------
   public function omBandejaEscuelaPlanTesis() {
      $llOk = $this->mxValBandejaEscuelaPlanTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaEscuelaPlanTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaEscuelaPlanTesis() {
      if (!isset($this->paData['CESTPRO']) || empty(trim($this->paData['CESTPRO']) || $this->paData['CESTPRO'] == null)) {
         $this->pcError = "OPCION NO VALIDO";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaEscuelaPlanTesis($p_oSql) {
      $lcEstPro = $this->paData['CESTPRO'];
      $lcCargo = $this->paData['CCARGO'];
      $lcCencos = $this->paData['CCENCOS'];
      if($lcCargo == '006' || $lcCargo == '013'){
         $lctmp = "SELECT cUniAca FROM S01TCCO WHERE CNIVEL LIKE (SELECT cNivel FROM S01TCCO WHERE CCENCOS = '$lcCencos' AND CTIPO = 'A')||'%' AND CUNIACA != '00'";
      }else{
         $this->pcError = "CARGO NO PERMITIDO PARA ACCEDER A ESTA OPCION";
         return false;
      }
      //Carga los estados de Plan de tesis y Genera las notificaciones
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '138'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstPro = '$laFila[0]' AND cEstado = 'A' AND cUniAca IN ($lctmp)";
         $R2 = $p_oSql->omExec($lcSql);
         $laFila2 = $p_oSql->fetch($R2);
         if (empty($laFila2[0])) {
            $i = 0;
         } else {
            $i = $laFila2[0];
         }
         $this->paNotificacion[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'NNOTIFI' => $i];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS [143]";
         return false;
      }
      //Carga las tesis segun el estado de proceso Dcitaminador-Asesor-Tercer Jurado
      $lcSql = "SELECT cIdTesi, cDesTip, mTitulo, cCodAlu, cNombre, cEstado, cEstPro, nNroDic, nNroJur, cNomUni, cLinkTe
                FROM V_T01MTES
                WHERE cEstado IN('A', 'P', 'O') AND cUniAca IN ($lctmp) AND cEstPro = '$lcEstPro' 
                ORDER BY cEstado, tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CESTADO' => $laFila[5], 'CESTPRO' => $laFila[6], 'NNRODIC' => $laFila[7],
                             'NNROJUR' => $laFila[8], 'CNOMUNI' => $laFila[9], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[10]];
         $i++;
      }
      return true;
   }

   // -------------------------------------------------------------------------
   // 2019-05-17 PFC Carga los Tipos dictaminador-asesor-tercer jurado PLT2190
   // -------------------------------------------------------------------------
   public function omInitDictaminador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitDictaminador($p_oSql) {
      //Carga Tipo de Plan de Tesis
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '143'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paTipTes[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Carga Tipo Relacion Docente
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '144'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Carga Estado
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '146'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 2";
         return false;
      }
      //Carga Cargos
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '140'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCargo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 3";
         return false;
      }
      //Carga Estados de Procesos
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '142'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstPro[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 4";
         return false;
      }
      //Carga Estados de Procesos
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '145'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstDic[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 5";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Bandeja que muestra cambia dictaminador y cargo en un plan de Tesis PLT2190
   // -----------------------------------------------------------
   public function omBandejaCambiarDictaminador() {
      $llOk = $this->mxValBandejaCambiarDictaminador();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaCambiarDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaCambiarDictaminador() {
      if (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID TESIS NO VALIDO";
         return false;
      }
      return true;
   }

   protected function mxBandejaCambiarDictaminador($p_oSql) {
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcCodUsu = $this->paData['CCODUSU'];
      //Carga las Tesis segun filtro de Seleccion
      $lcSql = "SELECT cIdTesi, cDesTip, mTitulo, cCodAlu, cNombre, cEstado, cEstPro, nNroJur, cNomUni, cDesPro, cTipo, cLinkTe
                FROM V_T01MTES
                WHERE cIdTesi = '$lcIdTesi'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CESTADO' => $laFila[5], 'CESTPRO' => $laFila[6], 'NLIMDOC' => $laFila[7],
                             'CNOMUNI' => $laFila[8], 'CDESPRO' => $laFila[9], 'CTIPO' => $laFila[10], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[11]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Trae Dictaminadores de una Tesis Creacion PLT2110-PLT2120-2190
   // -----------------------------------------------------------
   public function omInitAsignacionDictaminador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignacionDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitAsignacionDictaminador($p_oSql) {
      // Cargar aLumnos
      //CFLAG variable de eliminacion de alumno
      $lcBusDic = str_replace(' ','%',strtoupper($this->paData['CPARAME']));
      $lcSql = "SELECT cCodAlu, cNomUni FROM V_A01MALU
                WHERE cNivel NOT IN ('08', '09') AND cNroDni = (SELECT cNroDni FROM V_A01MALU WHERE cCodAlu = (SELECT cCodAlu FROM T01DALU WHERE cIdTesi = '$lcBusDic' AND cNivel ='P')) ORDER BY cCodAlu";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODALU' => $laFila[0], 'CNOMUNI' => $laFila[1]];
         $i++;
      }
      $lcSql = "SELECT C.cCodAlu, C.cNombre, B.cNivel FROM T01MTES A
                  INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
                  INNER JOIN V_A01MALU C ON C.cCodAlu =B.cCodAlu
                WHERE B.cIdTesi = '$lcBusDic' AND B.cEstado !='X' ORDER BY cCodAlu";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CFLAG' => ($laFila[2]) != 'S'? 0 : 1];
         $i++;
      }
      // Cargar docentes
      $lcSql = "SELECT C.cCodDoc, C.cNombre, B.cTipRel, D.cDescri, B.cCargo, B.cEstado, B.cNivel, C.cEmail FROM T01MTES A
                     INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                     INNER JOIN V_A01MDOC C ON C.cCodDoc =B.cCodDoc
                     LEFT JOIN V_S01TTAB D ON D.cCodigo = B.cTipRel
                WHERE B.cIdTesi = '$lcBusDic' AND D.cCodTab = '144' ORDER BY cCodDoc";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CTIPREL' => $laFila[2], 'CDESREL' => $laFila[3], 
                               'CCARGO'  => $laFila[4], 'CESTADO' => $laFila[5], 'CFLAG' => ($laFila[6]) != 'S'? 0 : 1, 'CEMAIL' => $laFila[7]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Buscar Docente PLT1110 - PLT2110 - PLT2120
   // -----------------------------------------------------------
   public function omBuscarDocente() {
      $llOk = $this->mxValBuscarDocente();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarDocente($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarDocente() {
      if (empty(trim($this->paData['CBUSDOC']))) {
         $this->pcError = 'CLAVE DE BÚSQUEDA DE DOCENTE NO DEFINIDA';
         return false;
      }
      return true;
   }
   
   protected function mxBuscarDocente($p_oSql) {
      // CFLAG PERMITE ELIMINAR AL DOCENTE
      // CESTADO Iactivo por que muestra los estados en la pantalla de cambio de dictaminadores
      $tmp = 1;  
      $lcBusDoc = str_replace(' ','%',strtoupper($this->paData['CBUSDOC'])); 
      if (in_array(strlen($lcBusDoc), [4,8]) && is_numeric($lcBusDoc)) { 
         $lcSql = "(cNroDni = '{$lcBusDoc}' OR cCodDoc = '{$lcBusDoc}')"; 
         $tmp = 0; 
      } else { 
         $lcSql = "cNombre LIKE '%{$lcBusDoc}%'"; 
      } 
      $lcSql ="SELECT cCodDoc, cNombre, cEmail, cEstado FROM V_A01MDOC 
               WHERE {$lcSql} AND cEstado = 'A' ORDER BY cNombre"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $i = 0; 
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CEMAIL' => $laFila[2],  
                             'CTIPREL' => 'D', 'CDESREL' => 'DICTAMINADOR', 'CCARGO' => 'V', 'CFLAG' => 1, 'CESTADO' => $laFila[3]]; 
         $i++; 
      } 
      if ($tmp == 0 && $this->paDatos[0]['CESTADO'] == 'I') { 
         $this->pcError = "EL DOCENTE NO SE ENCUENTRA LABORANDO EN LA UNIVERSIDAD"; 
         return false; 
      } 
      if ($i == 0) { 
         $this->pcError = "NO HAY DOCENTES DEFINIDOS ACTUALMENTE PARA BUSQUEDA"; 
         return false; 
      } 
      $this->paData = $i; 
      return true; 
   }

   // -----------------------------------------------------------
   // 2020-05-09 APR Buscar ESPECIALIDAD PLT1110 - PLT2110 - PLT2120
   // -----------------------------------------------------------
   public function omBuscarEspecialidadPregrado() {
      $llOk = $this->mxValBuscarEspecialidadPregrado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarEspecialidadPregrado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarEspecialidadPregrado() {
      if (empty(trim($this->paData['CBUSESP']))) {
         $this->pcError = 'CLAVE DE BÚSQUEDA DE ESPECIALIDAD NO DEFINIDA';
         return false;
      }
      return true;
   }
   
   protected function mxBuscarEspecialidadPregrado($p_oSql) {
      // CFLAG PERMITE ELIMINAR AL DOCENTE
      // CESTADO I activo por que muestra los estados en la pantalla de cambio de dictaminadores
      $lcBusEsp = $this->paData['CBUSESP'];
      $lcSql ="SELECT cPrefij, cDescri FROM S01DLAV
               WHERE cEstado != 'I' AND cDescri LIKE  '%$lcBusEsp%' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CPREFIJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CFLAG' => 1];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY ESPECIALIDADES DEFINIDAS ACTUALMENTE PARA BUSQUEDA";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Buscar Alumno PLT ADM1380 NO ESTA EN FUNCION
   // -----------------------------------------------------------
   public function omBandejaAlumnos() {
      $llOk = $this->mxValBandejaAlumnos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaAlumnos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaAlumnos() {
     if (!isset($this->paData['CCODALU']) || empty(trim($this->paData['CCODALU']) || $this->paData['CCODALU'] == null)) {
         $this->pcError = "CODIGO DE ALUMNO PRINCIPAL NO VALIDO";
         return false;
      }
      return true;
   }
   
   protected function mxBandejaAlumnos($p_oSql) {
      $lcParam = $this->paData['CCODALU'];
      $lcSql = "SELECT cEstado FROM T01DALU WHERE cCodAlu = '$lcParam'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      //Variables
      $this->paTesis['CESTADO'] =  $laFila[0];    
      if ($laFila[0] === 'P') {
         $lcSql = "SELECT A.cIdTesi, B.cDescri, STRING_AGG(DISTINCT D.cNombre, '-') AS cNombres
                   FROM T01MTES A
                   LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '143' AND TRIM(B.cCodigo) = A.cTipo
                   INNER JOIN T01DALU C ON C.cIdTesi = A.cIdTesi
                   INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu
                   WHERE A.cIdTesi  = (SELECT cIdTesi FROM T01DALU WHERE cCodAlu = '$lcParam')
                   GROUP BY A.cIdTesi, B.cDescri";
         $R1 = $p_oSql->omExec($lcSql);
         $i = 0;
         while ($laFila = $p_oSql->fetch($R1)) {
            $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMALUS' => str_replace('/', ' ', $laFila[2])];
            $i++;
         }
         if ($i == 0) {
            $this->pcError = "NO HAY ALUMNO DEFINIDO ACTUALMENTE";
            return false;
         }
      } else {
         $lcSql = "SELECT A.cNroDni, A.cNombre, B.cDescri AS cDesNiv, T.cEstado, C.cDescri AS cDesEst FROM T01DALU T
                   INNER JOIN V_A01MALU A ON A.cCodAlu = T.cCodAlu
                   LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '147' AND TRIM(B.cCodigo) = T.cNivel
                   LEFT OUTER JOIN V_S01TTAB C ON B.cCodTab = '148' AND TRIM(C.cCodigo) = T.cEstado
                   WHERE T.cIdTesi = (SELECT cIdTesi FROM T01DALU WHERE cEstado !='I' AND cCodAlu = '$lcParam' LIMIT 1)";
         $R1 = $p_oSql->omExec($lcSql);
         $i = 0;
         while ($laFila = $p_oSql->fetch($R1)) {
            $this->paDatos[] = ['CNRODNI' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CDESNIV' => $laFila[2], 'CESTADO' => $laFila[3],
                                'CDESEST' => $laFila[4]];
            $i++;
         }
         if ($i == 0) {
            $this->pcError = "NO HAY ALUMNO DEFINIDO ACTUALMENTE";
            return false;
         }
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Asignacion de Dictaminadores  PLT2110 - PLT2120 - PLT2190
   // -----------------------------------------------------------
   public function omGrabarDictaminador() {
      $llOk = $this->mxValDictaminadores();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarDictaminador($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   // Usado por la pantalla principal de asignacion 2110 y por la 2180 cambio de dictaminadores
   protected function mxValDictaminadores() {
      if (!isset($this->paData['CUSUCOD']) || empty(trim($this->paData['CUSUCOD']) || $this->paData['CUSUCOD'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDTESI']) || empty($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null) {
         $this->pcError = 'ID DE LA TESIS NO VALIDO';
         return false;
      } elseif (empty($this->paData['MDATOS'])) {
         $this->pcError = 'DOCENTES NO DEFINIDOS';
         return false;
      }
      return true;
   }

   protected function mxGrabarDictaminador($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MDIC_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }                               
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Asignacion de Dictaminadores  PLT2110 - PLT2120 - PLT2190
   // -----------------------------------------------------------
   public function omGrabarCambioDictaminador() {
      $llOk = $this->mxValDictaminadores();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarCambioDictaminador($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarCambioDictaminador($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MDIC_4('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }                           
      return true;
   }
   // -----------------------------------------------------------
   // 2019-05-17 PFC Propuesta de Dictaminador de dia de sustentacion PLT2140
   // -----------------------------------------------------------

   public function omGrabarDiaSustentacionDictaminador() {
      $llOk = $this->mxValDia();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarDiaSustentacionDictaminador($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValDia() {
      //Guarda una matriz de unos y ceros como propuesta
      if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['AMATRIZ']) || empty($this->paData['AMATRIZ']) || $this->paData['AMATRIZ'] == null) {
         $this->pcError = 'ERROR EN PARAMETRO 1';
         return false;
      }
      return true;
   }

   protected function mxGrabarDiaSustentacionDictaminador($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01DDIC_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }                            
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Dia de Sustentacion Secretaria PLT2170
   // -----------------------------------------------------------
   public function omGrabarDiaSustentacion() {
      $llOk = $this->mxValDiaSustentacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarDiaSustentacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValDiaSustentacion() {
      if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['TDIASUS']) || empty($this->paData['TDIASUS']) || $this->paData['TDIASUS'] == null) {
         $this->pcError = 'ERROR EN PARAMETRO 1';
         return false;
      } elseif (!isset($this->paData['CAULA']) || empty($this->paData['CAULA']) || $this->paData['CAULA'] == null) {
         $this->pcError = 'ERROR EN PARAMETRO 1';
         return false;
      }
      return true;
   }

   protected function mxGrabarDiaSustentacion($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MDIC_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      } 
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Busqueda de Planes de Tesis por titlo o Nombre del alumno PLT3110
   // -----------------------------------------------------------
   public function omInitBuscarPlan() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitBuscarPlan($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitBuscarPlan($p_oSql) {
      //Carga Estado de los Dictaminadores
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '145'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paEstDic[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Carga Estado de los Dictaminadores
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '139'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      // TRAE UNIDADES ACADEMICAS
      $lcSql = "SELECT cUniAca, cNomUni FROM S01TUAC WHERE cNivel NOT IN('08', '09')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUniaca[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR UNIDADES ACADEMICAS";
         return false;
      }
      return true;
   }
   // -----------------------------------------------------------
   // 2019-05-17 PFC Busqueda de Planes de Tesis por titlo o Nombre del alumno PLT3110
   // -----------------------------------------------------------
   public function omBuscarPlanTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarPlanTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarPlanTesis($p_oSql) {
      $lcBuscar = str_replace(' ','%',strtoupper($this->paData['CPARAME']));
      $lcFlag = $this->paData['CFLAG'];
      $lcUniAca = $this->paData['CUNIACA'];
      //Busqueda por ID
      if ($lcFlag == 'I') {
         $lcSql = "SELECT cIdTesi, cDestip, mTitulo, cCodAlu, cNombre, cEstado, cLinkTe
                  FROM V_T01MTES WHERE cIdTesi = '$lcBuscar'";
      //Busqueda por titulo de la tesis                  
      }elseif($lcFlag == 'T') {
         $lcSql = "SELECT cIdTesi, cDesTip, mTitulo, cCodAlu, cNombre, cEstado, cLinkTe
                  FROM V_T01MTES WHERE mTitulo LIKE '%$lcBuscar%' ORDER BY tModifi";
      //Busqueda dats del docente
      }elseif($lcFlag == 'C') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cEstado, A.cLinkTe
                  FROM V_T01MTES A
                  INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                  INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                  WHERE B.cCodDoc = '$lcBuscar' OR C.cNombre LIKE '%$lcBuscar%' ORDER BY A.tModifi";
      //Busqueda por datos alumno
      } elseif ($lcFlag == 'A') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cEstado, A.cLinkTe
                  FROM V_T01MTES A
                  INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
                  INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                  WHERE B.cCodAlu = '$lcBuscar' OR C.cNombre LIKE '%$lcBuscar%' ORDER BY A.tModifi";
      //Busqueda por escuela profesional
      } elseif ($lcFlag == 'E') {
         $lcSql = "SELECT cIdTesi, cDestip, mTitulo, cCodAlu, cNombre, cEstado, cLinkTe
                  FROM V_T01MTES WHERE cUniAca = '$lcUniAca' ORDER BY cIdTesi";
      }
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CESTADO' => $laFila[5], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[6]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC TRAER PLANES DE TESIS FACULTAD PLT2120
   // -----------------------------------------------------------
   public function omBandejaFacultadPlanTesis() {
      $llOk = $this->mxValBandejaFacultadPlanTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaFacultadPlanTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaFacultadPlanTesis() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }
   //SUBQUERY que tra las unidades academicas de una area -> facultad -> escuela
   protected function mxBandejaFacultadPlanTesis($p_oSql) {
      $lcCargo = $this->paData['CCARGO'];
      $lcCencos = $this->paData['CCENCOS'];
      if($lcCargo == '003' || $lcCargo == '004'){
         $lctmp = "SELECT cUniAca FROM S01TCCO WHERE CNIVEL LIKE (SELECT cNivel FROM S01TCCO WHERE CCENCOS = '$lcCencos' AND CTIPO = 'A')||'%' AND CUNIACA != '00'";
      }else{
         $this->pcError = "CARGO NO PERMITIDO PARA ACCEDER A ESTA OPCION";
         return false;
      }
      $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cNombre, A.cNomUni, string_agg(DISTINCT C.cCodDoc, '-') AS cCodDocs, string_agg(DISTINCT C.cNombre, '-') AS cNomDocs, A.cEstado, A.cEstPro, A.nNroDic, A.nNroJur, A.cLinkTe
               FROM V_T01MTES A
               INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
               INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
               WHERE A.cEstado IN ('P', 'Z') AND A.cUniAca IN ($lctmp)
               GROUP BY A.cIdTesi, A.cDesTip, A.mTitulo, A.cNombre, A.cNomUni, A.nNroDic, A.cEstado, A.cEstPro, A.nNroDic, A.nNroJur, A.tModifi, A.cLinkTe
               ORDER BY A.tModifi";       
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CNOMALU' => str_replace('/', ' ', $laFila[3]),
                             'CNOMUNI' => $laFila[4], 'CCODDOS' => $laFila[5], 'CNOMDOCS' => str_replace('/', ' ', $laFila[6]), 'CESTADO' => $laFila[7],
                             'CESTPRO' => $laFila[8], 'NNRODIC' => $laFila[9], 'NNROJUR' => $laFila[10], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[11]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Bandeja de Dia de Sustentacion PLT2140
   // -----------------------------------------------------------
   public function omBandejaDiaSustentacionDictaminador() {
      $llOk = $this->mxValBandejaDiaSustentacionDictaminador();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaDiaSustentacionDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaDiaSustentacionDictaminador() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaDiaSustentacionDictaminador($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, B.cEstado, A.cLinkTe
                FROM V_T01MTES A
                INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                WHERE A.cEstPro = 'H' AND B.cCodDoc = '$lcCodUsu'
                GROUP BY A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, B.cEstado, A.tModifi, A.cLinkTe
                ORDER BY A.tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTADO' => $laFila[6], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[7]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Bandeja de Dia de Sustentacion PLT2170
   // -----------------------------------------------------------
   public function omBandejaDiaSustentacion() {
      $llOk = $this->mxValBandejaDiaSustentacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaDiaSustentacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaDiaSustentacion() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaDiaSustentacion($p_oSql) {
      // $lcCargo = $this->paData['CCARGO'];
      $lcCencos = $this->paData['CCENCOS'];
      $lctmp = "SELECT cUniAca FROM S01TCCO WHERE CNIVEL LIKE (SELECT cNivel FROM S01TCCO WHERE CCENCOS = '$lcCencos' AND CTIPO = 'A')||'%' AND CUNIACA != '00'";
      // Estado revisar??
      $lcSql = "SELECT cIdTesi, cDesTip, cNomUni, mTitulo, cCodAlu, cNombre, cNomUni, cEstado, cLinkTe
                FROM V_T01MTES
                WHERE cEstPro = 'H' AND cUniAca IN ($lctmp) 
                ORDER BY tModifi";                            
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'MTITULO' => $laFila[3],
                             'CCODALU' => $laFila[4], 'CNOMALU' => str_replace('/', ' ', $laFila[5]), 'CNOMUNIS' => str_replace('/', ' ', $laFila[6]),
                             'CESTADO' => $laFila[7], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[8]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Seleccion Semana de Dia de Sustentacion PLT2140 - PLT2170
   // -----------------------------------------------------------
   public function omAsignarDiaSustentacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAsignarDiaSustentacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxAsignarDiaSustentacion($p_oSql) {
      $lcIdTesi = $this->paData['CIDTESI'];
      //?? validar estado de proceso y estado
      $lcSql = "SELECT tDiaSus::DATE FROM T01MTES WHERE cIdTesi = '$lcIdTesi'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $fecha = $laFila[0];
      //genera un arreglo de fechas
      $cont = 0;
      //formato de fecha
      //valida que no sea ni sabado ni domingo
      for ($i = 0; $i <= 7; $i++) {
          $fecha = strtotime ( '+1 day' , strtotime ( $fecha ) ) ;
          $flag = date ( "w" , $fecha );
          $fecha = date ( 'Y-m-j' , $fecha );
          if ($flag != 0 and $flag != 6) {
            // array_push($this->paDatos, $fecha);
            $tmp[$i] = $fecha;
            $cont++;
          }
      }
      $this->paData['AFECHAS'] = implode(',', $tmp);
      //Trae matriz de horarios de los dictaminadores
      $lcSql = "SELECT A.cCodAlu, B.cNombre FROM T01DALU A INNER JOIN V_A01MALU B ON A.cCodAlu = B.cCodAlu WHERE cIdTesi = '$lcIdTesi'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1])];
         $i++;
      }
      //Trae matriz de horarios de los dictaminadores
      $lcSql = "SELECT A.cDiaSus, A.cCodDoc, B.cNombre, C.cDescri, A.cCargo, D.cEmail FROM T01DDIC A 
                  INNER JOIN V_A01MDOC B ON A.cCodDoc = B.cCodDoc 
                  INNER JOIN S01TTAB C ON A.cCargo = C.cCodigo
                  LEFT OUTER JOIN S01MPER D ON D.cNroDni = B.cNroDni 
                  WHERE A.cIdTesi = '$lcIdTesi' AND C.cCodTab = '140' ORDER BY C.cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $lcFlag = 'N';
      while ($laFila = $p_oSql->fetch($R1)) {
         if(empty($laFila[0])){
            for($i = 0; $i <= (($cont)*15)-1; $i++) {
               $amatriz[$i] = 0;
            }
            $this->paDatos[] = ['CDIASUS' => $amatriz, 'CCODDOC' => $laFila[1]];
            $this->paDocente[] = ['CCODDOC' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'CDESCRI' => $laFila[3], 'CCARGO' => $laFila[4], 'CEMAIL' => $laFila[5]];
         } else {
            $this->paDatos[] = ['CDIASUS' => explode(',', $laFila[0]), 'CCODDOC' => $laFila[1]];
            $this->paDocente[] = ['CCODDOC' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'CDESCRI' => $laFila[3], 'CCARGO' => $laFila[4], 'CEMAIL' => $laFila[5]];
         }
         $lcFlag = ($laFila[1] == $this->paData['CUSUCOD'] && $laFila[4] == 'S' && $lcFlag == 'N')? 'S' : $lcFlag; //ESTA VARIABLE SERA LA QUE DEBES ENVIAR AL TPL PARA EVALUAR SI SE MUESTRA O NO
         $i++;
      }
      $tmp = null;
      $adocente = null;
      for ($i = 0; $i <= count($this->paDatos[0]['CDIASUS'])-1; $i++) {
         $result = 0;
         $toggle = '*';
         for ($j = 0; $j <= count($this->paDatos)-1; $j++) {
            if($this->paDatos[$j]['CDIASUS'][$i]  == 1) {
               $result++;
               $toggle = '['.$this->paDatos[$j]['CCODDOC'].']'.$toggle;
            }
         }
         $tmp[$i] = $result;
         $adocente[$i] = $toggle;
      }
      $adocente = str_replace('*', '', $adocente);
      $this->paData['CDIASUS'] = implode(',', $tmp);
      $this->paData['CDOCSUS'] = implode(',', $adocente);
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Aprobacion Arreglo de Tesis con Dictaminadores asginados PLT2120
   // -----------------------------------------------------------
   public function omAprobarDictaminadorFacultad() {
      $llOk = $this->mxValAprobarDictaminadorFacultad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAprobarDictaminadorFacultad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValAprobarDictaminadorFacultad() {
      if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['MDATOS']) || empty($this->paData['MDATOS']) || $this->paData['MDATOS'] == null) {
         $this->pcError = 'NO SELECCIONO NINGUNA TESIS PARA APROBAR';
         return false;
      }
      return true;
   }

   protected function mxAprobarDictaminadorFacultad($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MDIC_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC APRUEBA PLAN DE TESIS DICTAMINADOR PLT2130
   // ----------------------------------------------------------
   public function omAprobarPlanDictaminador() {
      $llOk = $this->mxValAprobarDictaminador();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAprobarPlanDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValAprobarDictaminador() {
      if (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI'])) || $this->paData['CIDTESI'] == null ) {
         $this->pcError = "ID NO VALIDO";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxAprobarPlanDictaminador($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01DDIC_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC INIT Bandeja Plan de Tesis Dictaminador AProbar PLT2130
   // ----------------------------------------------------------
   public function omInitBandejaDictaminador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitBandejaDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitBandejaDictaminador($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      //Notificacion de Dia de Sustentacion Bandeja Dictaminador
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT COUNT(cIdTesi) FROM T01MTES WHERE cIdTesi IN (SELECT cIdTesi FROM T01DDIC WHERE cCodDoc = '$lcCodUsu') AND cEstPro = 'I' AND CURRENT_TIMESTAMP  <= tDiaSus";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!empty($laFila[0]) || $laFila[0] == null) {
         $this->paData['NNOTIFI'] = 0;
      }
      $this->paData['NNOTIFI'] = $laFila[0];
      $lcSql = "SELECT cIdTesi, tDiaSus, cAula FROM T01MTES WHERE cIdTesi IN (SELECT cIdTesi FROM T01DDIC WHERE cCodDoc = '$lcCodUsu') AND cEstPro = 'I' AND CURRENT_TIMESTAMP  <= tDiaSus";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'TDIASUS' => $laFila[1], 'CAULA' => $laFila[2]];
         $i++;
      }
      //Carga Tipo
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '144'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Carga Estado de los Dictaminadores
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '145'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      //Carga Estado de los Dictaminadores
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '221'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paFiltro[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Bandeja Plan de Tesis Dictaminador AProbar PLT2130
   // ----------------------------------------------------------
   public function omBandejaDictaminador() {
      $llOk = $this->mxValBandejaDictaminador();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValBandejaDictaminador() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaDictaminador($p_oSql) {
      //Bandeja Dictaminador
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, B.cTipRel, A.cEstPro, A.cLinkTe
         FROM V_T01MTES A
         INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
         WHERE B.cEstado = 'A' AND B.cEstado != 'O' AND A.cEstado IN ('B', 'O') AND B.cCodDoc = '$lcCodUsu' 
         ORDER BY A.tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paTesis[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CTIPREL' => $laFila[5], 'CESTPRO' => $laFila[6],
                             'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[7]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Bandeja de Tesis en Proceso de  un Dictaminador PLT2150
   // ----------------------------------------------------------
   public function omCambiarBandejaDictaminador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCambiarBandejaDictaminador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCambiarBandejaDictaminador($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcFiltro = $this->paData['CFILTRO'];
      if ($lcFiltro == 'P') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, B.cTipRel, A.cEstPro, A.cLinkTe
                   FROM V_T01MTES A
                   INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                   WHERE B.cEstado = 'A' AND A.cEstado IN ('B', 'O') AND B.cCodDoc = '$lcCodUsu' 
                   ORDER BY A.tModifi";
      } elseif ($lcFiltro == 'S') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, B.cTipRel, A.cEstPro, A.cLinkTe
                   FROM V_T01MTES A
                   INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                   WHERE A.cEstado NOT IN ('B', 'D', 'E') AND B.cCodDoc = '$lcCodUsu' 
                   ORDER BY A.tModifi";
      } elseif ($lcFiltro == 'H') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, B.cTipRel, A.cEstPro, A.cLinkTe
                   FROM V_T01MTES A
                   INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                   WHERE B.cCodDoc = '$lcCodUsu' 
                   ORDER BY A.tModifi";
      } elseif ($lcFiltro == 'B') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, B.cTipRel, A.cEstPro, A.cLinkTe
                   FROM V_T01MTES A
                   INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                   WHERE B.cEstado = 'B' AND A.cEstado IN ('B', 'O') AND B.cCodDoc = '$lcCodUsu' 
                   ORDER BY A.tModifi";
      } elseif ($lcFiltro == 'O') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, B.cTipRel, A.cEstPro, A.cLinkTe
                   FROM V_T01MTES A
                   INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                   WHERE B.cEstado = 'O' AND A.cEstado IN ('B', 'O') AND B.cCodDoc = '$lcCodUsu' 
                   ORDER BY A.tModifi";
      }
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CTIPREL' => $laFila[5], 'CESTPRO' => $laFila[6], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[7]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL CAMBIAR SWITCH DICTAMINADOR";
         return false;
      }
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Ver Observacion del Dictaminador en un plan de Tesis  PLT1210 - PLT2130 - PLT2150
   // ----------------------------------------------------------
   public function omVerObservacionDictamiandor() {
      $llOk = $this->mxValVerObservacionDictamiandor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerObservacionDictamiandor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValVerObservacionDictamiandor() {
      if (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID NO VALIDO";
         return false;
      }
      return true;
   }

   protected function mxVerObservacionDictamiandor($p_oSql) {
      //Trae ultima observacionn del Dictaminador
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT cIdTesi, mObserv FROM T01DLOG WHERE cIdTesi = '$lcIdTesi' AND  cUsuCod = '$lcCodUsu' AND cEstTes = 'O' ORDER BY tModifi DESC LIMIT 1";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paData = ['CIDTESI' => $laFila[0], 'MOBSERV' => $laFila[1]];
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Ver Observacion del Dictaminador en un plan de Tesis  PLT1210 - PLT2130 - PLT2150
   // ----------------------------------------------------------
   public function omVerObservacionTurnitin() {
      $llOk = $this->mxValVerObservacionTurnitin();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerObservacionTurnitin($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValVerObservacionTurnitin() {
      if (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID NO VALIDO";
         return false;
      }
      return true;
   }

   protected function mxVerObservacionTurnitin($p_oSql) {
      //Trae ultima observacionn del Dictaminador
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcSql = "SELECT cIdTesi, mObserv FROM T01DLOG WHERE cEstado = 'A' AND  cTipo = 'T' AND cIdTesi = '$lcIdTesi' ORDER BY tModifi DESC LIMIT 1";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Ver Observacion del Dictaminador en un plan de Tesis  PLT1210 - PLT2130 - PLT2150
   // ----------------------------------------------------------
   public function omVerObservacion() {
      $llOk = $this->mxValVerObservacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerObservacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValVerObservacion() {
      if (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID NO VALIDO";
         return false;
      }
      return true;
   }

   protected function mxVerObservacion($p_oSql) {
      //Trae ultima observacionn del Dictaminador
      $lcIdTesi = $this->paData['CIDTESI'];
      //trae la ultima observacion
      $lcSql = "SELECT cIdTesi, mObserv FROM T01DLOG WHERE cIdTesi = '$lcIdTesi' AND cEstado = 'A' ORDER BY tModifi DESC LIMIT 1";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paData = ['CIDTESI' => $laFila[0],'MOBSERV' => $laFila[1]];
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Observar Dictaminador a un plan de Tesis  PLT2130
   // ----------------------------------------------------------
   public function omObservarPlan() {
      $llOk = $this->mxValObservarPlan();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxObservarPlan($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValObservarPlan() {
      if (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID DE TESIS NO VALIDO";
         return false;
      //} elseif (!isset($this->paData['MOBSERV']) || empty($this->paData['MOBSERV']) || !preg_match("(^[a-zA-Z0-9áéíóúñ�?É�?ÓÚÑ \n\r\/\t¿?!¡(),.:;_-]+$)", trim($this->paData['MOBSERV']))) { 
      } elseif (!isset($this->paData['MOBSERV']) || empty($this->paData['MOBSERV']) || preg_match("([\'\"\&])", trim($this->paData['MOBSERV']))) { 
         $this->pcError = 'OBSERVACION NO DEFINIDA, INVALIDA O CONTIENE CARACTERES ESPECIALES(EL TEXTO SOLO PUEDE CONTENER CARACTERES ALFANUMERICOS Y SIGNOS DE PUNTUACION)';
         return false;
      }
      $this->paData['MOBSERV'] = mb_strtoupper($this->paData['MOBSERV']);
      return true;
   }

   protected function mxObservarPlan($p_oSql) {
      //Poner en Observado el Plan de Tesis
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01DDIC_1('$lcJson')";
      print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Dashboard Facultad Tesis Presentadas unidad Academica Docente PLT3130
   // ----------------------------------------------------------
   public function omDashboardTesisGeneral() {
      // $llOk = $this->mxValDashboard();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDashboardTesisGeneral($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   // protected function mxValDashboard() {
   //    if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
   //       $this->pcError = "CODIGO DE USUARIO NO VALIDA PARA ESTA OPCION";
   //       return false;
   //    }
   //    return true;
   // }

   protected function mxDashboardTesisGeneral($p_oSql) {
      $lcFecha = $this->paData['TFECHA'];
      $lcaño = substr($lcFecha, 0, 4);
      $lcUniAca = $this->paData['CUNIACA'];
      $la_tesis0[] = 0;
      $la_tesis1[] = 0;
      $la_tesis2[] = 0;
      for ($i = 1; $i <= 12; $i++) {
         if ($i<10) {$lcmes = '0'.$i;
         } else {$lcmes = $i;}  
         $inicio = $lcaño.'-'.$lcmes."-01 00:00:00";
         if ($lcmes == '04' || $lcmes == '06' || $lcmes == '09' || $lcmes == '11') {
            $fin = $lcaño.'-'.$lcmes."-30 23:59:59";
         } elseif ($lcmes == '02') {
            $fin = $lcaño.'-'.$lcmes."-28 23:59:59";
         } else {
            $fin = $lcaño.'-'.$lcmes."-31 23:59:59";
         }
         $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstado != 'X' AND cUniAca = '$lcUniAca' AND dEntreg BETWEEN '$inicio' AND '$fin'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $la_tesis0[$i-1] = $laFila[0];
         $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstado != 'X' AND cEstPro != 'I' AND cUniAca = '$lcUniAca' AND tDiaSus BETWEEN '$inicio' AND '$fin'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $la_tesis1[$i-1] = $laFila[0];
         $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstado = 'X' AND cUniAca = '$lcUniAca' AND dEntreg BETWEEN '$inicio' AND '$fin'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $la_tesis2[$i-1] = $laFila[0];
      }
      $this->paTesis = array_merge($la_tesis0,$la_tesis1,$la_tesis2);
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Dashboard Facultad Tesis Presentadas unidad Academica Docente PLT3130
   // ----------------------------------------------------------
   public function omDashboardTesisMes() {
      // $llOk = $this->mxValDashboard();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDashboardTesisMes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   // protected function mxValDashboard() {
   //    if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
   //       $this->pcError = "CODIGO DE USUARIO NO VALIDA PARA ESTA OPCION";
   //       return false;
   //    }
         // if($lcCargo == '006' || $lcCargo == '013'){
         //    $lctmp = "SELECT cUniAca FROM S01TCCO WHERE CNIVEL LIKE (SELECT cNivel FROM S01TCCO WHERE CCENCOS = '$lcCencos' AND CTIPO = 'A')||'%' AND CUNIACA != '00'";
         // }else{
         //    $this->pcError = "CARGO NO PERMITIDO PARA ACCEDER A ESTA OPCION";
         //    return false;
         // }
   //    return true;
   // }

   protected function mxDashboardTesisMes($p_oSql) {
      $lcFecha = $this->paData['TFECHA'];
      $lcmes = substr($lcFecha, 5, 6);
      $lcUniAca = $this->paData['CUNIACA'];
      $la_tesis0[] = 0;
      $la_tesis1[] = 0;
      $la_tesis2[] = 0;
      $inicio = $lcFecha."-01 00:00:00";
      if ($lcmes == '04' || $lcmes == '06' || $lcmes == '09' || $lcmes == '11') {
         $fin = $lcFecha."-30 23:59:59";
      } elseif ($lcmes == '02') {
         $fin = $lcFecha."-28 23:59:59";
      } else {
         $fin = $lcFecha."-31 23:59:59";
      }
      $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstado != 'X' AND cUniAca = '$lcUniAca' AND dEntreg BETWEEN '$inicio' AND '$fin'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paTesis[0] = $laFila[0];
      $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstado != 'X' AND cEstPro != 'I' AND cUniAca = '$lcUniAca' AND tDiaSus BETWEEN '$inicio' AND '$fin'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paTesis[1] = $laFila[0];
      $lcSql = "SELECT COUNT(cIdTesi) FROM V_T01MTES WHERE cEstado = 'X' AND cUniAca = '$lcUniAca' AND dEntreg BETWEEN '$inicio' AND '$fin'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paTesis[2] = $laFila[0];
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Dashboard Facultad Tesis-Docentes PLT3130
   // ----------------------------------------------------------
   public function omDashboardTesisDocentesMes() {
      // $llOk = $this->mxValDashboard();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDashboardTesisDocentesMes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxDashboardTesisDocentesMes($p_oSql) {
      $lcFecha = $this->paData['TFECHA'];
      $lcmes = substr($lcFecha, 5, 6);
      $lcUniAca = $this->paData['CUNIACA'];
      $inicio = $lcFecha."-01 00:00:00";
      if ($lcmes == '04' || $lcmes == '06' || $lcmes == '09' || $lcmes == '11') {
         $fin = $lcFecha."-30 23:59:59";
      } elseif ($lcmes == '02') {
         $fin = $lcFecha."-28 23:59:59";
      } else {
         $fin = $lcFecha."-31 23:59:59";
      }
      $lcSql = "SELECT C.cCodDoc, C.cNombre, COUNT(A.cIdTesi) FROM V_T01MTES A
                INNER JOIN T01DDIC B ON B.cIdtesi = A.cIdtesi
                INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                WHERE A.cUniAca = '$lcUniAca'
                AND B.tDiaAsi BETWEEN '$inicio' AND '$fin'
                GROUP BY C.cCodDoc, C.cNombre";
       $R1 = $p_oSql->omExec($lcSql);
       $i = 0;
       while ($laFila = $p_oSql->fetch($R1)) {
          $this->paDatos[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CCOUNT' => $laFila[2]];
          $i++;
       }
       if ($i == 0) {
         $this->paDatos[] = ['CCODDOC' => 0, 'CNOMBRE' => 0, 'CCOUNT' => 0];
       }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Dashboard Facultad Docente PLT3130
   // ----------------------------------------------------------
   public function omDashboardDetalleDocente() {
      // $llOk = $this->mxValDashboard3();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDashboardDetalleDocente($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   // protected function mxValDashboard3() {
   //    if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
   //       $this->pcError = "CODIGO DE USUARIO NO VALIDA PARA ESTA OPCION";
   //       return false;
   //    } elseif (!isset($this->paData['CCODDOC']) || empty(trim($this->paData['CCODDOC']) || $this->paData['CCODDOC'] == null)) {
   //       $this->pcError = "CODIGO DE DOCENTE NO VALIDO";
   //       return false;
   //    }
   //    return true;
   // }

   protected function mxDashboardDetalleDocente($p_oSql) {
      $lcFecha = $this->paData['TFECHA'];
      $lcaño = substr($lcFecha, 0, 4);
      $lcUniAca = $this->paData['CUNIACA'];
      $lcCodDoc = $this->paData['CCODDOC'];
      $la_tesis0[] = 0;
      $la_tesis1[] = 0;
      $la_tesis2[] = 0;
      for ($i = 1; $i <= 12; $i++) {
         if ($i<10) {$lcmes = '0'.$i;
         } else {$lcmes = $i;}  
         $inicio = $lcaño.'-'.$lcmes."-01 00:00:00";
         if ($lcmes == '04' || $lcmes == '06' || $lcmes == '09' || $lcmes == '11') {
            $fin = $lcaño.'-'.$lcmes."-30 23:59:59";
         } elseif ($lcmes == '02') {
            $fin = $lcaño.'-'.$lcmes."-28 23:59:59";
         } else {
            $fin = $lcaño.'-'.$lcmes."-31 23:59:59";
         }
         $lcSql = "SELECT COUNT(B.cIdTesi) FROM T01DDIC A
                   INNER JOIN V_T01MTES B ON B.cIdTesi = A.cIdTesi AND B.cUniAca = '$lcUniAca'
                   WHERE A.cCodDoc = '$lcCodDoc' AND A.tDiaAsi BETWEEN '$inicio' AND '$fin' AND A.cTiprel = 'D'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $la_tesis0[$i-1] = $laFila[0];
         $lcSql = "SELECT COUNT(B.cIdTesi) FROM T01DDIC A
                  INNER JOIN V_T01MTES B ON B.cIdTesi = A.cIdTesi AND B.cUniAca = '$lcUniAca'
                   WHERE A.cCodDoc = '$lcCodDoc' AND A.tDiaAsi BETWEEN '$inicio' AND '$fin' AND A.cTiprel = 'A'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $la_tesis1[$i-1] = $laFila[0];
         $lcSql = "SELECT COUNT(B.cIdTesi) FROM T01DDIC A
                   INNER JOIN V_T01MTES B ON B.cIdTesi = A.cIdTesi AND B.cUniAca = '$lcUniAca'
                   WHERE A.cCodDoc = '$lcCodDoc' AND A.tDiaAsi BETWEEN '$inicio' AND '$fin' AND A.cTiprel = 'T'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $la_tesis2[$i-1] = $laFila[0];
      }
      $this->paDocente = array_merge($la_tesis0, $la_tesis1, $la_tesis2);
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Bandeja de Turnting  PLT2160
   // ----------------------------------------------------------
   public function omBandejaTurnitin() {
      $llOk = $this->mxValBandejaTurniting();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaTurniting($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaTurniting() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaTurniting($p_oSql) {
      //Carga Estado de los Dictaminadores
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '222'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paFiltro[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS 1";
         return false;
      }
      $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cNomUni, A.cCodAlu, A.cNombre, A.cEstado, B.cCodTur, B.nOptimo, B.mObserv, A.cLinkTe
                FROM V_T01MTES A
                LEFT JOIN T01DTUR B ON B.cIdTesi = A.cIdTesi
                WHERE A.cEstado = 'B' AND A.cEstPro = 'F'
                ORDER BY A.tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CNOMUNI' => $laFila[3],
                             'CCODALU' => $laFila[4], 'CNOMALU' => str_replace('/', ' ', $laFila[5]), 'CESTADO' => $laFila[6],
                             'CCODTUR' => (empty($laFila[7]))? '000000' : $laFila[7], 'NOPTIMO' => (empty($laFila[8]))? '0' : $laFila[7],
                             'MOBSERV' => (empty($laFila[9]))? '' : $laFila[9], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[10]];
         $i++;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-05-17 PFC Aprueba de Turnting  PLT2160
   // ----------------------------------------------------------
   public function omRevisarTurnitin() {
      $llOk = $this->mxValomRevisarTurnitin();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxomRevisarTurnitin($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValomRevisarTurnitin() {
      if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['NPORCEN']) || empty(trim($this->paData['NPORCEN']) || $this->paData['NPORCEN'] == null)) {
         $this->pcError = "PORCENTAJE INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID TESIS INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxomRevisarTurnitin($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01DTUR_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-0-31 PFC Elimina Alumno
   // ----------------------------------------------------------
   public function omEliminarAlumno() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEliminarAlumno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxEliminarAlumno($p_oSql) {
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcCodAlu = $this->paData['CCODALU'];
      //Verifica que el almno a eliminar no sea el principal de la tesis
      $lcSql = "SELECT cIdTesi FROM T01DALU WHERE cIdTesi = '$lcIdTesi' AND cCodAlu = '$lcCodAlu' AND cNivel = 'S'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (empty($laFila[0])) {
         $this->pcError = "ERROR AL ELIMNAR AL ALUMNO";
         return false;
      }else {
         $lcSql = "UPDATE T01DALU SET cEstado = 'X', cUsuCod = '$lcCodUsu', tModifi = NOW() WHERE cIdTesi = '$lcIdTesi' AND cCodAlu = '$lcCodAlu'";
         $R1 = $p_oSql->omExec($lcSql);
         if(!$R1){
            $this->pcError = $this->paDatos['ERROR'];
            return false;
         }else {
            // Cargar aLumnos
            $lcSql = "SELECT C.cCodAlu, C.cNombre, B.cNivel FROM T01MTES A
                        INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MALU C ON C.cCodAlu =B.cCodAlu
                     WHERE B.cIdTesi = '$lcIdTesi' AND B.cEstado !='X' ORDER BY cCodAlu";
            $R1 = $p_oSql->omExec($lcSql);
            $i = 0;
            while ($laFila = $p_oSql->fetch($R1)) {
               $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CFLAG' => ($laFila[2]) != 'S'? 0 : 1];
               $i++;
            }
            return true;
         }
      }
   }
   // -----------------------------------------------------------
   // 2019-05-17 PFC Datos Turnting  PLT2160
   // ----------------------------------------------------------
   public function omDatosTurnitin() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosTurnitin($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxDatosTurnitin($p_oSql) {
      // Cargar aLumnos
      $lcIdTesi = str_replace(' ','%',strtoupper($this->paData['CIDTESI']));
      $lcSql = "SELECT C.cCodAlu, C.cNombre FROM T01MTES A
                  INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
                  INNER JOIN V_A01MALU C ON C.cCodAlu =B.cCodAlu
                WHERE B.cIdTesi = '$lcIdTesi' AND B.cEstado !='X' ORDER BY cCodAlu";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1])];
         $i++;
      }
      // Cargar docentes
      $lcSql = "SELECT C.cCodDoc, C.cNombre, D.cDescri, B.tAprbPT::DATE, B.tAprbBT::DATE FROM T01MTES A
                  INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                  INNER JOIN V_A01MDOC C ON C.cCodDoc =B.cCodDoc
                  LEFT JOIN V_S01TTAB D ON D.cCodigo = B.cTipRel
               WHERE B.cIdTesi = '$lcIdTesi' AND D.cCodTab = '144' ORDER BY cCodDoc";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CDESREL' => $laFila[2], 'TAPRBPT' => (empty($laFila[3]))? 'N/A' : $laFila[3], 'TAPRBBT' => (empty($laFila[4]))? 'N/A' : $laFila[4]];
         $i++;
      }
      //trae la ultima observacion
      $lcSql = "SELECT mObserv FROM T01DLOG WHERE cIdTesi = '$lcIdTesi' AND cTipo = 'T' ORDER BY tModifi DESC LIMIT 1";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paData = ['MOBSERV' => $laFila[0]];
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-08-05 PFC Bandeja de Turnitin en Proceso
   // ----------------------------------------------------------
   public function omCambiarBandejaTurnitin() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCambiarBandejaTurnitin($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCambiarBandejaTurnitin($p_oSql) {
      $lcFiltro = $this->paData['CFILTRO'];
      if ($lcFiltro == 'P') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cEstado, B.cCodTur, B.nOptimo, A.cLinkTe, B.nPorcen
                  FROM V_T01MTES A
                  LEFT JOIN T01DTUR B ON B.cIdTesi = A.cIdTesi
                  WHERE A.cEstPro = 'F'
                  ORDER BY A.tModifi";
      } elseif ($lcFiltro == 'O') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cEstado, B.cCodTur, B.nOptimo, A.cLinkTe
                  FROM V_T01MTES A
                  LEFT JOIN T01DTUR B ON B.cIdTesi = A.cIdTesi
                  WHERE B.cEstado = 'O'
                  ORDER BY A.tModifi";
      }  elseif ($lcFiltro == 'H') {
         $lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cEstado, B.cCodTur, B.nOptimo, A.cLinkTe
                  FROM V_T01MTES A
                  LEFT JOIN T01DTUR B ON B.cIdTesi = A.cIdTesi
                  WHERE A.cEstPro IN ('G', 'H', 'I')
                  ORDER BY A.tModifi";
      }
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CESTADO' => $laFila[5], 'CCODTUR' => (empty($laFila[6]))? '000000' : $laFila[6],
                             'NOPTIMO' => (empty($laFila[7]))? '0' : $laFila[7], 'NPORCEN' => (empty($laFila[11]))? '' : $laFila[8], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[10]];
         $i++;
      }
   }

   // -----------------------------------------------------------
   // 2019-09-02 PFC Ver Observacion Escuela
   // ----------------------------------------------------------
   public function omVerObservacionEscuela() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerObservacionEscuela($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxVerObservacionEscuela($p_oSql) {
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcSql = "SELECT cIdTesi, mObserv FROM T01DLOG WHERE cEstado = 'A' AND  cTipo = 'E' AND cIdTesi = '$lcIdTesi' ORDER BY tModifi DESC LIMIT 1";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paData = ['CIDTESI' => $laFila[0], 'MOBSERV' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR OBSERVACIONES DE LA ESCUELA";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2019-09-02 PFC Graba Observacion Escuela
   // ----------------------------------------------------------
   public function omObservarPlanEscuela() {
      $llOk = $this->mxValObservarPlanEscuela();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxObservarPlanEscuela($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValObservarPlanEscuela() {
      if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID TESIS INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxObservarPlanEscuela($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MDIC_5('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   public function omInitDashboard() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDashboard($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitDashboard($p_oSql) {
      // TRAE UNIDADES ACADEMICAS
      $lcCencos = $this->paData['CCENCOS'];
      $lcSql = "SELECT cUniAca, cDescri FROM S01TCCO WHERE CNIVEL LIKE (SELECT cNivel FROM S01TCCO WHERE CCENCOS = '$lcCencos' AND CTIPO = 'A')||'%' AND CUNIACA != '00' ORDER BY cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUniaca[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR UNIDADES ACADEMICAS";
         return false;
      }
      return true;
   }

   public function omInitBandejaEmpastados() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniBandejaEmpastados($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxIniBandejaEmpastados($p_oSql) {
      $lcCencos = $this->paData['CCENCOS'];
      $lctmp = "SELECT cUniAca FROM S01TCCO WHERE CNIVEL LIKE (SELECT cNivel FROM S01TCCO WHERE CCENCOS = '$lcCencos' AND CTIPO = 'A')||'%' AND CUNIACA != '00'";
      // TRAE UNIDADES ACADEMICAS
      $lcSql = "SELECT cIdTesi, cDesTip, cNomUni, mTitulo, cCodAlu, cNombre, cEstado, cEstPro, cLinkTe
                FROM V_T01MTES
                WHERE cUniAca IN ($lctmp) AND cEstPro = 'I' ORDER BY cIdTesi";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'MTITULO' => $laFila[3],
                             'CCODALU' => $laFila[4], 'CNOMALU' => str_replace('/', ' ', $laFila[5]), 'CESTADO' => $laFila[6], 'CESTPRO' => $laFila[7],
                             'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[8]];
         $i++;
      }
      return true;
   }
   
   // -----------------------------------------------------------
   // 2019-09-18 PFC Graba Entrega de empastados y asigna dia de Sustentacion
   // ----------------------------------------------------------
   public function omEntregaEmpastado() {
      $llOk = $this->mxValEntregaEmpastado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEntregaEmpastado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValEntregaEmpastado() {
      if (!isset($this->paData['CCODUSU']) || empty(trim($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null)) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDTESI']) || empty(trim($this->paData['CIDTESI']) || $this->paData['CIDTESI'] == null)) {
         $this->pcError = "ID TESIS INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxEntregaEmpastado($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01DTUR_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // 2022-01-28 FLC BUSQUEDA POR NOMBRE DE LOS ALUMNOS 
   // ----------------------------------------------------------
   public function omBuscarAlumno() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarAlumno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAlumno($p_oSql) {   
      $lcNombre = strtoupper($this->paData);   
      $lcSql = "SELECT A.cIdTesI, A.mTitulo, C.cCodAlu, D.cNomUni, E.cNombre, A.cEstado, B.cDescri AS cDesEst FROM T01MTES A    
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '141' AND SUBSTRING(B.cCodigo, 1, 1) = A.cEstado   
                  INNER JOIN T01DALU C ON C.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TUAC D ON D.cUniAca = A.cUniAca 
                  INNER JOIN V_A01MALU E ON E.cCodAlu = C.cCodAlu 
                  WHERE E.cNombre LIKE '%$lcNombre%' ORDER BY E.cNombre";   
      $RS = $p_oSql->omExec($lcSql);   
      $i = 0;   
      while ($laFila = $p_oSql->fetch($RS)) {   
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CCODALU' => $laFila[2], 'CNOMACA' => $laFila[3],   
                             'CNOMBRE' => $laFila[4], 'CESTADO' => $laFila[5], 'CDESCRI' => $laFila[6]];   
         $i++;   
      }   
      if ($i == 0) {   
         $this->pcError = "NO SE ENCONTRARON REGISTROS DE ALUMNOS";   
         return false;   
      }   
      return true;   
   }

   // -----------------------------------------------------------
   // 2022-01-28 FLC BUSQUEDA A LOS DICTAMINADORES Y ASESOR DE TESIS
   // ----------------------------------------------------------
   public function omBuscarDictaminadores() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarDictaminadores($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarDictaminadores($p_oSql) {
      $lcIdTesI = $this->paData['CIDTESI'];
      $lcSql = "SELECT A.nSerial, A.cIdTesi, A.cCodDoc, D.cNombre, A.cEstado, B.cDescri AS cDesEst, A.CTIPREL, C.cDescri AS cDesRel FROM T01DDIC A
                    LEFT OUTER JOIN V_S01TTAB B ON B.CCODTAB = '145' AND SUBSTRING(B.CCODIGO, 1, 1) = A.CESTADO
                    LEFT OUTER JOIN V_S01TTAB C ON C.CCODTAB = '138' AND SUBSTRING(C.CDESCOR, 1, 1) = A.CTIPREL
                    INNER JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cCodDoc
                    WHERE A.cIdTesi IN ('$lcIdTesI') ORDER BY A.cIdTesi, A.cCodDoc ";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CIDTESI' => $laFila[1], 'CCODDOC' => $laFila[2], 'CNOMBRE' => $laFila[3],
                             'CESTADO' => $laFila[4], 'CDESEST' => $laFila[5], 'CTIPREL' => $laFila[6], 'CDESREL' => $laFila[7]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // DEMO TESIS
   // ----------------------------------------------------------
   public function omDemoTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDemoTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxDemoTesis($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT F_TESIS('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila[0]) {
         $this->pcError = "ERROR DE EJECUCION SQL, COMUNIQUESE CON EL ERP";
         return false;
      }
      return true;
   }

   // --------------------------------------------------------------
   // 2020-05-04 APR Bandeja de Dia de Sustentacion Virtual PLT3140
   // --------------------------------------------------------------
   public function omBandejaDiaSustentacionVirtual() {
      $llOk = $this->mxValBandejaDiaSustentacionVirtual();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaDiaSustentacionVirtual($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaDiaSustentacionVirtual() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaDiaSustentacionVirtual($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      //RESPUESTA DE LA SUSTENTACION TRAER
      $lcSql = "SELECT cCodigo, cDescri FROM S01TTAB WHERE CCODTAB = '250' AND CCODIGO NOT IN ('0', 'A')";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paResSus[] = ['CCODIGO' => $laFila[0],  'CDESCRI' => $laFila[1]];
         $i++;
      }
      //TESIS EN ETAPA DE SUSTENTACION VIRTUAL
      /*$lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, B.cEstado, A.cAula, A.tDiaSus, D.cEmail, A.cLinkTe, A.cUniAca, A.cEmail 
                  FROM V_T01MTES A
                  --INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                  INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi AND B.cCatego = 'D' 
                  INNER JOIN V_A01MDOC C ON B.cCodDoc = C.cCodDoc
                  INNER JOIN S01MPER D ON D.cNroDni = C.cNroDni
                  WHERE A.cEstPro = 'I' AND B.cCargo = 'S' AND B.cCodDoc = '$lcCodUsu'
                  GROUP BY A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, B.cEstado, B.cCargo, A.cAula, A.tDiaSus, D.cEmail, A.tModifi, A.cLinkTe, A.cUniAca, A.cEmail 
                  ORDER BY A.tModifi";*/
      $lcSql = "SELECT A.cIdTesi, h.cdescri, A.mTitulo, E.cCodAlu, F.cNombre, G.cNomUni, B.cEstado, A.cAula, A.tDiaSus, D.cEmail, A.cLinkTe, A.cUniAca, F.cEmail 
                  FROM T01MTES A
                  --INNER JOIN T01DDIC B ON B.cIdTesi = A.cIdTesi AND B.cEstado != 'X'
                  INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi AND B.cCatego = 'D' 
                  INNER JOIN V_A01MDOC C ON B.cCodDoc = C.cCodDoc
                  INNER JOIN S01MPER D ON D.cNroDni = C.cNroDni
              INNER JOIN T01DALU E ON E.cIdTesi = A.cIdTesi
              INNER JOIN V_A01MALU F ON F.cCodALu = E.cCodAlu
              INNER JOIN S01TUAC G ON G.cUniAca = A.cUniAca
              LEFT JOIN t01dtur j ON j.cidtesi = a.cidtesi
                  LEFT JOIN v_s01ttab h ON h.ccodtab = '143'::bpchar AND h.ccodigo = a.ctipo
                  LEFT JOIN v_s01ttab i ON i.ccodtab = '142'::bpchar AND i.ccodigo = a.cestpro
                  WHERE A.cEstPro = 'I' AND B.cCargo in ('S','E') AND B.cCodDoc = '$lcCodUsu'
                  GROUP BY A.cIdTesi, h.cdescri, A.mTitulo, E.cCodAlu, F.cNombre, G.cNomUni, B.cEstado, A.cAula, A.tDiaSus, D.cEmail, A.cLinkTe, A.cUniAca, F.cEmail 
                  ORDER BY A.tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0],  'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]),    'CNOMUNI' => $laFila[5], 'CESTADO' => $laFila[6], 'CAULA'   => $laFila[7], 
                             'TDIASUS' => $laFila[8],  'CEMAILD' => $laFila[9], 'CLINKTE' => $laFila[10], 'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[11], 
                             'CUNIACA' => $laFila[12], 'CEMAILE' => $laFila[12]];
         $i++;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // 2020-05-05 APR Respuesta de Jurado de dia de sustentacion PLT3140
   // -----------------------------------------------------------------------
   public function omGrabarRespuestaDiaSustentacion(){
      $llOk = $this->mxValDiaSustentacionJurado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarActaSustentacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGrabarRespuestaDiaSustentacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }   
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValDiaSustentacionJurado() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INV�?LIDO O NO DEFINIDO';
         return false;
      } elseif (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6) {
         $this->pcError = 'CODIGO DE TESIS NO DEFINIDO O INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxGrabarActaSustentacion($p_oSql) {
      //VALIDAMOS SI LA TESIS YA ESTA REGISTRADA EN EL MAESTRO DE LIBROS
      $lcSql = "SELECT cIdLibr FROM T02MLIB WHERE cIdTesi = '{$this->paData['CIDTESI']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($RS);
      if ($RS == false || $p_oSql->pnNumRow == 1) {
         $this->pcError = "ERROR: LA TESIS YA FUE INGRESADA EN EL MAESTRO DE LIBROS";
         return false;
      }
      //VALIDAMOS SI TIENE ESPCIALIDAD, LA UNIDAD ACADEMICA Y LA MODALIDAD 
      $lcSql = "SELECT cPrefij, cUniAca, cTipo FROM T01MTES WHERE cIdtesi = '{$this->paData['CIDTESI']}'";
      $RS = $p_oSql->omExec($lcSql);
      $R1 = $p_oSql->fetch($RS);
      //OBTENEMOS EL MAXIMO LIBRO
      $lcSql = "SELECT MAX(cIdLibr) FROM T02MLIB";
      $RS = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($RS);
      $lcNroLib = intval($laTmp[0]) + 1;
      #VALIDAR UNIDAD DE SEGUNDA ESPECIALIDAD - MEDICINA HUMANA
      $paDatAca = array('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9', 'M7');
      if (in_array($R1[1], $paDatAca)) {
         $lcSql = "SELECT MAX (B.cFolio) FROM T01MTES A
                  INNER JOIN T02MLIB B ON B.cIdtesi = A.cIdtesi
                  WHERE A.CESTPRO IN ('K','J','R') AND B.cUniaca IN ('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9', 'M7') AND A.CTIPO = '{$R1[2]}'";
      } else {
         $lcSql = "SELECT MAX (B.cFolio) FROM T01MTES A
                  INNER JOIN T02MLIB B ON B.cIdtesi = A.cIdtesi
                  WHERE A.CESTPRO IN ('K','J','R') AND A.CPREFIJ = '{$R1[0]}' AND A.CUNIACA = '{$R1[1]}'AND A.CTIPO = '{$R1[2]}'";
      }
      $RS = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($RS);
      $lcNroFol = intval($laTmp[0]) + 1;  
      $lcNroFol = substr('000000', 0, 6 - strlen($lcNroFol)).$lcNroFol;  
      $lcNroLib = substr('000000', 0, 6 - strlen($lcNroLib)).$lcNroLib;
      $lcSql = "INSERT INTO T02MLIB (cIdLibr, cFolio, cIdtesi, cUniAca, cPrefij, cTipo, tIniSus, tFinSus, cResult, cUsuCod, tModifi)
                    VALUES('{$lcNroLib}','{$lcNroFol}','{$this->paData['CIDTESI']}','{$R1[1]}', '{$R1[0]}', '{$R1[2]}', '{$this->paData['TDIASUS']}', 
                           '{$this->paData['TDIAFIN']}', '{$this->paData['CRESULT']}', '{$this->paData['CUSUCOD']}',NOW())";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "ERROR AL REGISTRAR EL ACTA EN EL MAESTRO DE LIBROS";
         return false;
      }
      return true;
   }

   protected function mxGrabarRespuestaDiaSustentacion($p_oSql) {
      $lcEmailD = $this->paData['CEMAILD'];
      $lcPasWor = $this->paData['CCONTRA'];
      $lcObserv = $this->paData['MOBSERV'];
      $lcFinSus = substr($this->paData['TDIAFIN'], 11, 5);
      if ($lcObserv == ''){ 
         $lcObsVer = 'NO HAY OBSERVACIONES';
      } else{ 
         $lcObsVer = $lcObserv; 
      } 
      //INGRESAR OBSERVACIONES DE SUSTENTACIÓN EN LA TABLA T01DLOG DE TESIS
      $lcSql = "INSERT INTO T01DLOG VALUES (DEFAULT, '{$this->paData['CIDTESI']}','B', 'N', '023', '$lcObsVer', 'D', 'A', '{$this->paData['CUSUCOD']}',NOW());";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "ERROR AL REGISTRAR LAS OBSERVACIONES EN EL HISTORIAL DE LA TESIS.";
         return false;
      }
      //TRAEMOS LA DESCRIPCION DE LA RESPUESTA DE LA APROBACION
      $lcSql = "SELECT TRIM(cDescri) FROM V_S01TTAB WHERE cCodigo = '{$this->paData['CRESULT']}' AND cCodTab = '250'";
      $R1 = $p_oSql->omExec($lcSql);
      $paResult = $p_oSql->fetch($R1);
      //TRAEMOS LOS NOMBRES DE LOS ALUMNOS AUTORES DE LA TESIS
      $lcSql = "SELECT B.cNombre, B.cEmail FROM T01DALU A INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CNOMBRE' => str_replace('/', ' ',$laFila[0]), 'CEMAIL' => $laFila[1]];
      }
      //TRAEMOS LOS CORREOS DE LOS JURADOS DE LA SUSTENTACIÓN
      $lcSql = "SELECT cEmail FROM T01DDOC A INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'D' AND A.cCodDoc <> '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paJurado[] = ['CEMAIL' => $laFila[0]];
      }
      //ACTUALIZAMOS EL ESTADO DEL PROCESO DE LA TESIS EN EL MAESTRO DE TESIS
      $lcSql = "UPDATE T01MTES SET cEstPro = 'J', cEstTes = 'J', tDiaSus = '{$this->paData['TDIASUS']}', tDiaFin = '{$this->paData['TDIAFIN']}', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE CIDTESI = '{$this->paData['CIDTESI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->pcError = 'ERROR AL ACTUALIZAR DATOS DE LA SUSTENTACION EN MAESTRO DE TESIS';
         return false;
      }  
      if ($this->paAlumno[1]['CNOMBRE'] == '' && $this->paAlumno[2]['CNOMBRE'] == ''){
         $lcNomTit = 'Sr.(a) '.$this->paAlumno[0]['CNOMBRE'];
      } elseif ($this->paAlumno[2]['CNOMBRE'] == ''){
         $lcNomTit = 'Srs.(as) '.$this->paAlumno[0]['CNOMBRE'].' - '.$this->paAlumno[1]['CNOMBRE'];
      } else {
         $lcNomTit = 'Srs.(as) '.$this->paAlumno[0]['CNOMBRE'].' - '.$this->paAlumno[1]['CNOMBRE'].' - '.$this->paAlumno[2]['CNOMBRE'];
      }
      //CORREO POR EL DIA DE SUSTENTACIÓN
      $loEmail = new CEmailDiaDeSustentacion();
      //INGRESAMOS EL ORIGEN DEL CORREO
      $llOk = $loEmail->omIngresarOrigen($lcEmailD, $lcPasWor);
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      $llOk = $loEmail->omConnect();
      $lcMensa = "<!DOCTYPE html>
                  <html>
                  <body>
                     <colgroup>
                        <col style='background-color: #ececec'>
                           <col style='background-color: #ffffff;'>
                        </colgroup>    
                        <tbody>
                           <tr style='background-color: #ffffff; color:black; text-align: justify'>
                              <th colspan='2'>
                                 <center><strong>UNIVERSIDAD CATÓLICA DE SANTA MAR�?A</strong></center><br>
                                 <center><img src='https://scontent.faqp3-1.fna.fbcdn.net/v/t1.6435-9/44262562_243541272988039_8121661918399168512_n.png?_nc_cat=109&ccb=1-3&_nc_sid=09cbfe&_nc_eui2=AeFtk1rgQIHI28qcPw9pNQ3f9NQYfiuboyH01Bh-K5ujIXftNHKl6Tb0Mx4kRZTP491OlxcqCbGIId1fu4F01hhF&_nc_ohc=91SkgSZM6-0AX-gSk7K&_nc_ht=scontent.faqp3-1.fna&oh=06933a24bd0987232ec1f81dddfd8d12&oe=60A6E795' width='90' height='95'></center><br>
                                 <strong>$lcNomTit</strong><br><br>
                                 Se le remite la siguiente respuesta en el Dia de su Sustentación Virtual.<br><br>
                                 Siendo las ".$lcFinSus.", en el salón de grados virtual de la Universidad Católica de Santa María.<br><br>
                                 Conducida la sustentación de la tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado: <br><br><center><strong>".$paResult[0]."</strong></center><br>
                                 <strong>Observaciones:</strong><br>".$lcObsVer."<br><br>
                                 Para el levantamiento de las Observaciones tiene que ingresar al Sistema ERP cual link es el https://apps.ucsm.edu.pe/UCSMERP/tramites.php <br><br>
                              </th>
                           </tr>
                           <tr>
                              <th colspan='2' style='background-color: #ffffff; text-align: left'>
                                 El presente correo electronico es solo para efectos informativos, NO responda a este mensaje, es un envío automático.
                              </th>
                           </tr>
                        </tbody>
                     </table>
                  </body>
                  </html>";
      $loEmail->paData = ['AEMAIL'=> $this->paAlumno, 'CECOPIA'=> $this->paJurado, 'CBODY' => $lcMensa];
      $llOk = $loEmail->omSend();
      if (!$llOk) {
         $this->pcError = "ERROR: NO SE PUDO ENVIAR CORREO DE RESPUESTA DEL DIA DE SUSTENTACIÓN VIRTUAL";
         return false;
      }
      return true;
   }   

   // --------------------------------------------------------------
   // 2020-05-11 APR Bandeja de Dia de Generacion Actas PLT3150
   // --------------------------------------------------------------
   public function omBandejaGeneraciondeActas() {
      $llOk = $this->mxValBandejaGeneraciondeActas();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaGeneraciondeActas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBandejaGeneraciondeActas() {
      if (!isset($this->paData['CCODUSU']) || empty($this->paData['CCODUSU']) || $this->paData['CCODUSU'] == null) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxBandejaGeneraciondeActas($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      /*$lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, A.cAula, A.tDiaSus, A.tDiaFin, A.cLinkTe, A.cUniAca, C.cDescri, B.cIdLibr, B.cFolio 
                  FROM V_T01MTES A 
                  INNER JOIN T02MLIB B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TTAB C ON C.cCodigo = B.cResult AND C.cCodTab = '250' 
                  WHERE A.cEstPro = 'K' AND B.cEstado = 'A'  
                  GROUP BY A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, A.cAula, A.tDiaSus, A.tDiaFin, A.tModifi, A.cLinkTe, A.cUniAca, C.cDescri, B.cIdLibr, B.cFolio 
                  ORDER BY A.tModifi";*/
      $lcSql = "SELECT A.cIdTesi, h.cdescri, A.mTitulo, E.cCodAlu, F.cNombre, G.cNomUni, A.cAula, A.tDiaSus, A.tDiaFin, A.cLinkTe, A.cUniAca, C.cDescri, B.cIdLibr, B.cFolio  
                  FROM T01MTES A  
                  INNER JOIN T02MLIB B ON B.cIdTesi = A.cIdTesi  
                  INNER JOIN S01TTAB C ON C.cCodigo = B.cResult AND C.cCodTab = '250'  
                  INNER JOIN T01DALU E ON E.cIdTesi = A.cIdTesi 
                  INNER JOIN V_A01MALU F ON F.cCodALu = E.cCodAlu 
                  INNER JOIN S01TUAC G ON G.cUniAca = A.cUniAca 
                  LEFT JOIN t01dtur j ON j.cidtesi = a.cidtesi 
                  LEFT JOIN v_s01ttab h ON h.ccodtab = '143'::bpchar AND h.ccodigo = a.ctipo 
                  LEFT JOIN v_s01ttab i ON i.ccodtab = '142'::bpchar AND i.ccodigo = a.cestpro 
                  WHERE A.cEstPro = 'K' AND B.cEstado = 'A'   
                  GROUP BY A.cIdTesi, h.cdescri, A.mTitulo, E.cCodAlu, F.cNombre, G.cNomUni, A.cAula, A.tDiaSus, A.tDiaFin, A.tModifi, A.cLinkTe, A.cUniAca, C.cDescri, B.cIdLibr, B.cFolio  
                  ORDER BY A.cIdTesi desc";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcPath1 = 'Docs/Tesis/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1], 'MTITULO' => $laFila[2], 'CCODALU' => $laFila[3],
                             'CNOMALU' => str_replace('/', ' ', $laFila[4]), 'CNOMUNI' => $laFila[5],  
                             'CAULA'   => $laFila[6], 'TDIASUS' => $laFila[7], 'TDIAFIN' => $laFila[8],  
                             'CARCHIV' => (file_exists($lcPath1))? 'S' : $laFila[9], 'CUNIACA' => $laFila[10], 
                             'CDESAPR' => $laFila[11],'CIDLIBR' => $laFila[12],'CFOLIO' => $laFila[13]]; 
         $i++;
      }
      return true;
   }

   // --------------------------------------------------------------
   // BANDEJA DE Levantamiento de Observaciones de sustentacion
   // 2020-05-11 FLC 
   // --------------------------------------------------------------
   public function omBandejaLevantamientoObservaciones() {
      $llOk = $this->mxValBandejaDiaSustentacionVirtual();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBandejaLevantamientoObservaciones($loSql);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBandejaLevantamientoObservaciones($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      /*$lcSql = "SELECT A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, B.cEstado, A.cAula, A.tDiaSus, D.cEmail, A.cLinkTe, A.cUniAca
                  FROM V_T01MTES A
                  INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi AND B.cEstado NOT IN ('X') 
                  INNER JOIN V_A01MDOC C ON B.cCodDoc = C.cCodDoc
                  INNER JOIN S01MPER D ON D.cNroDni = C.cNroDni
                  WHERE A.cEstPro = 'J' AND B.cCodDoc = '{$lcCodUsu}' AND A.cIdTesi NOT IN (SELECT E.cIdTesi FROM T02MLIB E INNER JOIN T02DLIB F ON F.cIdLibr = E.cIdLibr WHERE F.cUsuCod = '{$lcCodUsu}')
                  GROUP BY A.cIdTesi, A.cDesTip, A.mTitulo, A.cCodAlu, A.cNombre, A.cNomUni, B.cEstado, B.cCargo, A.cAula, A.tDiaSus, D.cEmail, A.tModifi, A.cLinkTe, A.cUniAca
                  ORDER BY A.tModifi";*/
      $lcCodUsu = $this->paData['CCODUSU']; 
      $lcSql = "SELECT A.cIdTesi, H.cDescri, A.mTitulo, E.cCodAlu, F.cNombre, A.cUniAca, G.cNomUni, A.cLinkTe
                  FROM T01MTES A
                  INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                  INNER JOIN T01DALU E ON E.cIdTesi = A.cIdTesi
                  INNER JOIN V_A01MALU F ON F.cCodALu = E.cCodAlu
                  INNER JOIN S01TUAC G ON G.cUniAca = A.cUniAca
                  LEFT JOIN V_S01TTAB H ON H.cCodTab = '143' AND SUBSTRING(H.cCodigo, 0, 3) = A.cTipo
                  LEFT JOIN V_S01TTAB I ON I.cCodTab = '252' AND SUBSTRING(I.cCodigo, 1, 1) = A.cEstTes
                  WHERE A.cEstTes = 'J' AND B.cCodDoc = '{$lcCodUsu}' AND B.cEstado != 'X' AND B.cCatego = 'D' AND 
                  A.cIdTesi NOT IN (SELECT E.cIdTesi FROM T02MLIB E INNER JOIN T02DLIB F ON F.cIdLibr = E.cIdLibr WHERE F.cUsuCod = '{$lcCodUsu}')
                  ORDER BY A.tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $cLinkTe = "http://apps.ucsm.edu.pe/UCSMERP/Docs/Tesis/".$laFila[0].".pdf";
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CDESCRI' => $laFila[1],  'MTITULO' => $laFila[2], 
                             'CCODALU' => $laFila[3], 'CNOMALU' => str_replace('/', ' ', $laFila[4]),
                             'CNOMUNI' => $laFila[5].' - '.$laFila[6], 'CLINKTE' => $cLinkTe];
      }
      return true; 
   }

   // -----------------------------------------------------------
   // Seleccion de tesis para el levantamiento de observaciones PLT3160
   // 2020-05-05 FLC
   // -----------------------------------------------------------
   public function omSeleccionTesisLevantamientoObservaciones() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxSeleccionTesisLevantamientoObservaciones($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxSeleccionTesisLevantamientoObservaciones($p_oSql){
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNomUni FROM T01DALU A INNER JOIN V_A01MALU B ON A.cCodAlu = B.cCodAlu WHERE cIdTesi = '$lcIdTesi'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CNOMUNI' => $laFila[2]];
         $i++;
      }
      $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, A.cCargo, B.cEmail FROM T01DDOC A  
                  INNER JOIN V_A01MDOC B ON A.cCodDoc = B.cCodDoc  
                  INNER JOIN S01TTAB C ON A.cCargo = C.cCodigo 
                  WHERE A.cIdTesi = '$lcIdTesi' AND C.cCodTab = '140' ORDER BY B.cCodDoc"; 
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CDESCRI' => $laFila[2], 'CCARGO' => $laFila[3], 'CEMAIL' => $laFila[4]];
      }
      $lcSql = "SELECT cIdLibr FROM T02MLIB WHERE cIdTesi = '{$lcIdTesi}'";
      $R1 = $p_oSql->omExec($lcSql);
      $lcIdLibr = $p_oSql->fetch($R1);
      $lcSql = "SELECT B.cCargo FROM V_A01MDOC A
                  INNER JOIN T01DDIC B ON B.CCODDOC = A.CCODDOC
                  WHERE A.CCODDOC = '{$lcCodUsu}' AND B.CIDTESI = '{$lcIdTesi}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $lcSql = "SELECT COUNT (*)
                  FROM T02DLIB 
                  WHERE cUsuCod = '{$lcCodUsu}' AND cIdLibr = '{$lcIdLibr[0]}'"; 
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paData['CFIRMA'] = $laFila[0];
      $lcSql = "SELECT A.cDescri FROM S01TTAB A 
                  INNER JOIN T02MLIB B ON B.cResult = A.cCodigo 
                  WHERE B.cIdTesi = '{$lcIdTesi}' AND A.CCODTAB = '250'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      $this->paData['CRESULT'] = $laFila[0];
      return true;
   }

   // ------------------------------------------------------------------------
   // GRABAR INFORMACIÓN DE LEVANTAMIENTO DE OBSERVACIONES DE SUSTENTACION
   // Creacion APR 2021-04-05 
   // ------------------------------------------------------------------------
   public function omGrabarRespuestaLevantamientoObservaciones() {
      $llOk = $this->mxValDiaSustentacionJurado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarRespuestaLevantamientoObservaciones($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarRespuestaLevantamientoObservaciones($p_oSql) {
      //GRABAR INFORMACIÓN DE APROBACIÓN EN TABLA T01DDOC
      $lcSql = "UPDATE T01DDOC SET tDicTam = NOW(), cResult = 'A', cUsuCod = '{$this->paData['CUSUCOD']}' WHERE CIDTESI = '{$this->paData['CIDTESI']}' AND CCODDOC = '{$this->paData['CUSUCOD']}' AND cCatego = 'D'"; 
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->pcError = 'ERROR: NO SE GUARDO APROBACIÓN DE JURADO DE SUSTENTACIÓN';
         return false;
      }
      //VERIFICAMOS SI TESIS ESTA ASIGNADA EN MAESTRO DE LIBROS
      $lcSql = "SELECT cIdLibr FROM T02MLIB WHERE CIDTESI = '{$this->paData['CIDTESI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->pcError = 'ERROR: LA TESIS NO ESTA ASIGNADA A NINGUN LIBRO EN MAESTRO DE TABLAS';
         return false;
      }
      $lcIdLibr = $p_oSql->fetch($R1);
      //VERIFICAMOS EL CARGO Y LOS DATOS DEL JURADO QUE HARA LA APROBACION
      $lcSql = "SELECT A.cNroDni, B.cCargo FROM V_A01MDOC A INNER JOIN T01DDOC B ON B.CCODDOC = A.CCODDOC
                  WHERE B.cCatego = 'D' AND A.CCODDOC = '{$this->paData['CUSUCOD']}' AND B.CIDTESI = '{$this->paData['CIDTESI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->pcError= 'ERROR: NO SE PUDO OBTENER EL CARGO Y DNI DEL JURADO QUE REALIZARA LA APROBACIÓN';
         return false;
      }
      $laFila = $p_oSql->fetch($R1);
      //VERIFICAR SI HAY UNA FIRMA ANTERIOR PARA VER EL ORDEN CORRESPONDIENTE
      $lcSql = "SELECT MAX (B.cNroFir) from T02MLIB A INNER JOIN T02DLIB B ON B.cIdLibr = A.cIdLibr 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $lnNroFir = $p_oSql->fetch($R1);
      $lnNroFir = intval($lnNroFir[0]) + 1; 
      //VERIFICAR DE LA TABLA T02DLIB EL ULTIMO REGISTRO
      $lcSql = "SELECT MAX (nSerial) from T02DLIB";
      $R1 = $p_oSql->omExec($lcSql);
      $lnSerial = $p_oSql->fetch($R1);
      $lnSerial = intval($lnSerial[0]) + 1; 
      //INGRESAR EL REGISTRO DE LA FIRMA EN EL DETALLE DE LA TABLA.
      $lcSql = "INSERT INTO T02DLIB (nSerial, cIdLibr, cTipo, cNroFir, cNroDni, tFirma,cUsuCod,tModifi)
                VALUES ({$lnSerial}, '{$lcIdLibr[0]}','{$laFila[1]}', '{$lnNroFir}','{$laFila[0]}',NOW(),'{$this->paData['CUSUCOD']}',NOW())";
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->pcError = "ERROR AL REGISTRAR EL LEVANTAMIENTO DE LA TESIS EN EL DETALLE DE LIBROS";
         return false;
      }
      $lcSql = "SELECT COUNT (*) FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cCatego = 'D'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $lnJurados = $p_oSql->fetch($R1); 
      //evalua si el numero que firmaron el acta es igual al de los jurados 
      if($lnNroFir == $lnJurados[0]){ 
         $lcSql = "SELECT B.cDesTit FROM T01MTES A INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
                     WHERE A.CIDTESI = '{$this->paData['CIDTESI']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $lcGrado = $p_oSql->fetch($R1);
         $lcSql = "UPDATE T01MTES SET cEstPro = 'K', cEstTes = 'K', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE CIDTESI = '{$this->paData['CIDTESI']}'";
         $R1 = $p_oSql->omExec($lcSql);
         if(!$R1){
            $this->pcError = "ERROR AL ACTUALIZAR CESTPRO T01MTES";
            return false;
         }
         $lcSql = "INSERT INTO T01DLOG (cIdTesi, cEstTes, cEstPro, cCargo, mObserv, cTipo, cEstado, cUsuCod, tModifi)
                  VALUES ('{$this->paData['CIDTESI']}', 'B', 'K', '023', 'GENERACION DE ACTAS', 'D', 'A', '{$this->paData['CUSUCOD']}',NOW())";
         $R1 = $p_oSql->omExec($lcSql);
         if(!$R1){
            $this->pcError;
            return false;
         }
      }
      return true;
   }

   // -----------------------------------------------------------
   // SELECCION DE ACTA DE TESIS - PLT3150
   // 2019-05-17 FLC  
   // -----------------------------------------------------------
   public function omLecturaActa() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxLecturaActa($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxLecturaActa($p_oSql) {
      $lcIdTesi = $this->paData['CIDTESI'];
      $lcSql = "SELECT A.cCodAlu, B.cNombre FROM T01DALU A INNER JOIN V_A01MALU B ON A.cCodAlu = B.cCodAlu WHERE cIdTesi = '$lcIdTesi'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1])];
         $i++;
      }
      $lcSql = "SELECT A.cDiaSus, A.cCodDoc, B.cNombre, C.cDescri, A.cCargo FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON A.cCodDoc = B.cCodDoc 
                  INNER JOIN S01TTAB C ON A.cCargo = C.cCodigo
                  WHERE A.cIdTesi = '$lcIdTesi' AND C.cCodTab = '140' ORDER BY C.cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      $lcFlag = 'N';
      while ($laFila = $p_oSql->fetch($R1)) {
         if(empty($laFila[0])){
            for($i = 0; $i <= (($cont)*15)-1; $i++) {
               $amatriz[$i] = 0;
            }
            $this->paDatos[] = ['CDIASUS' => $amatriz, 'CCODDOC' => $laFila[1]];
            $this->paDocente[] = ['CCODDOC' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'CDESCRI' => $laFila[3], 'CCARGO' => $laFila[4]];
         } else {
            $this->paDatos[] = ['CDIASUS' => explode(',', $laFila[0]), 'CCODDOC' => $laFila[1]];
            $this->paDocente[] = ['CCODDOC' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'CDESCRI' => $laFila[3], 'CCARGO' => $laFila[4]];
         }
         $lcFlag = ($laFila[1] == $this->paData['CUSUCOD'] && $laFila[4] == 'S' && $lcFlag == 'N')? 'S' : $lcFlag; //ESTA VARIABLE SERA LA QUE DEBES ENVIAR AL TPL PARA EVALUAR SI SE MUESTRA O NO
         $i++;
      }
      $tmp = null;
      $adocente = null;
      for ($i = 0; $i <= count($this->paDatos[0]['CDIASUS'])-1; $i++) {
         $result = 0;
         $toggle = '*';
         for ($j = 0; $j <= count($this->paDatos)-1; $j++) {
            if($this->paDatos[$j]['CDIASUS'][$i]  == 1) {
               $result++;
               $toggle = '['.$this->paDatos[$j]['CCODDOC'].']'.$toggle;
            }
         }
         $tmp[$i] = $result;
         $adocente[$i] = $toggle;
      }
      $adocente = str_replace('*', '', $adocente);
      $this->paData['CDIASUS'] = implode(',', $tmp);
      $this->paData['CDOCSUS'] = implode(',', $adocente);
      return true;
   }

   // -----------------------------------------------------------
   // Obtener Nombres de Alumnos
   // 2020-05-17 FLC  
   // -----------------------------------------------------------
   public function omNombreAlumnos() {
      $llOk = $this->mxValNombreAlumnos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxNombreAlumnos($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValNombreAlumnos() {
      if (!isset($this->paData['CNRODNI']) OR strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'EL DNI DEBE DE SER DE 8 DIGITOS';
         return false;
      } elseif (!is_numeric($this->paData['CNRODNI'])){
         $this->pcError = 'EL DNI DEBE DE SER NUMERICO';
         return false;
      }
      return true;
   }

   protected function mxNombreAlumnos($p_oSql) {
      $lcNroDni = $this->paData['CNRODNI'];
      $lcUniAca = $this->paData['CUNIACA']; 
      $lcSql = "SELECT cNombre, cNroCel, cEmail, cCodAlu FROM V_A01MALU WHERE cNroDni = '{$lcNroDni}' AND cUniAca = '{$lcUniAca}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!$laFila[0]){
         $this->pcError = 'EL DNI NO ESTA REGISTRADO EN LA UNIDAD ACADEMICA';
         return false;
      }
      $this->paAlumno = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CNROCEL' => $laFila[1], 'CEMAIL' => $laFila[2], 'CCODALU' => $laFila[3]];
      return true;
   }

   // -----------------------------------------------------------
   // Obtener Cargo (Decano o Director escuela) y las especialidades de la escuela
   // 2020-05-17 FLC  
   // -----------------------------------------------------------
   public function omInitCargoEscuela() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCargoEscuela($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCargoEscuela($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcCenCos = $this->paData['CCENCOS'];
      /*OJO DEBE HACER REFERENCIA A LA VISTA V_S01PCCO 
       * $lcSql = "SELECT A.cNombre, A.cCargo, A.cDesCar, A.cNivel, B.cCenCos
                      FROM V_S01TUSU_1 A
                      INNER JOIN S01PCCO B ON B.cCodUsu = A.cCodUsu
                      WHERE A.CCARGO = '013' AND A.CCODUSU = '{$lcCodUsu}'";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1){
         $this->pcError = 'USUARIO SIN AUTORIZACION';
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paData = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCARGO' => $laFila[1], 
                          'CDESCAR' => $laFila[2], 'CNIVEL' => $laFila[3], 'CCENCOS' => $laFila[4]];
         $lcCenCos = $laFila[4];
         $lcSql = "SELECT cCenCos, cDescri, cUniAca, SUBSTRING(CNIVEL,0,13) FROM S01TCCO WHERE CCENCOS = '{$laFila[4]}'";
         $R2 = $p_oSql->omExec($lcSql);
         if (!$R2){
            $this->pcError = 'USUARIO SIN AUTORIZACION';
            return false;
         }
         $laTmp1 = $p_oSql->fetch($R2);
         $this->paCargo[] = ['CCENCOS' => $laTmp1[0], 'CDESCRI' => $laTmp1[1], 'CUNIACA' => $laTmp1[2]];
         $lcSql = "SELECT cCenCos FROM S01TCCO WHERE CNIVEL = '{$laTmp1[3]}'";
         $R3 = $p_oSql->omExec($lcSql);
         $laTmp2 = $p_oSql->fetch($R3);
         $this->paData['CCODFAC'] = $laTmp2[0];
      }
      $lcSql = "SELECT A.cNombre, A.cCargo, A.cDesCar, A.cNivel, B.cCenCos
                      FROM V_S01TUSU_1 A
                      INNER JOIN S01PCCO B ON B.cCodUsu = A.cCodUsu
                      WHERE A.CCARGO IN ('013') AND A.CCODUSU = '{$lcCodUsu}'";
      */
      $lcSql = "SELECT A.cNombre, A.cCargo, A.cDesCar, A.cNivel, B.cCenCos
                      FROM V_S01TUSU_1 A
                      INNER JOIN V_S01PCCO B ON B.cCodUsu = A.cCodUsu AND B.cModulo='000'
                      WHERE A.CCARGO IN ('013', '006', '003') AND A.CCODUSU = '{$lcCodUsu}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!$laFila[0]){
         $this->pcError = 'USUARIO SIN AUTORIZACION';
         return false;
      }
      $this->paData = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCARGO' => $laFila[1], 
                       'CDESCAR' => $laFila[2], 'CNIVEL' => $laFila[3], 'CCENCOS' => $laFila[4]];
      /*$lcCenCos = $laFila[4];
      if ($laFila[1] == '003') {*/
         $this->paData['CCODFAC'] = $this->paData['CCENCOS'];
         $lcSql = "SELECT A.cCenCos, A.cDescri, A.cUniAca, B.CNOMUNI FROM S01TCCO A
                     INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca WHERE  A.CNIVEL LIKE 
                         (SELECT cNivel FROM S01TCCO 
                             WHERE CCENCOS = '{$lcCenCos}' AND CTIPO = 'A')||'%' AND A.CUNIACA != '00'";
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $this->paCargo[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CUNIACA' => $laFila[2], 'CNOMUNI' => $laFila[3]];
            $lcCenCos = $laFila[0];
         }
      /*} else {
         $lcSql = "SELECT cCenCos, cDescri, cUniAca, SUBSTRING(CNIVEL,0,13) FROM S01TCCO WHERE CCENCOS = '{$this->paData['CCENCOS']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $this->paCargo[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CUNIACA' => $laFila[2]];
         $lcSql = "SELECT cCenCos FROM S01TCCO WHERE CNIVEL = '{$laFila[3]}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $this->paData['CCODFAC'] = $laFila[0];
      }*/
      //TRAER LOS TIPOS DE MODALIDAD DEL ALUMNO
      $lcSql = "SELECT cCodigo, cDescri FROM V_S01TTAB WHERE cCodTab = '143' AND cCodigo NOT IN ('A') ORDER BY CCODIGO, CDESCRI";
      $R1 = $p_oSql->omExec($lcSql);
      $llOk = false;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $llOk = true;
      }
      if (!$llOk) {
         $this->pcError = 'NO HAY TIPOS DE PLANES/PROYECTOS DE TESIS PARA UNIDAD ACADEMICA DE ALUMNO|';
         return false;
      }
      //TRAER LAS ESPECIALIDADES DE CADA UNIDAD ACADEMICA
      $lcSql = "SELECT A.cPrefij, A.cDescri FROM S01DLAV A
                      INNER JOIN S01TCCO B ON B.cUniAca = A.cUniAca
                      WHERE A.cEstado = 'A' AND A.cUniAca = '{$this->paCargo[0]['CUNIACA']}' ORDER BY A.cDescri";
      
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CPREFIJ' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   // -----------------------------------------------------------
   // Grabar Ingreso Rapido de Tesis
   // 2020-05-17 FLC - APR CREACION  
   // -----------------------------------------------------------
   public function omGrabarIngresoRapidoTesis() {
      $llOk = $this->mxValIngresoRapidoTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarIngresoRapidoTesis($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValIngresoRapidoTesis(){
      if (!isset($this->paFile) || $this->paFile['size'] > 56214400 || ($this->paFile['type'] != 'application/pdf')) {
         $this->pcError = "ARCHIVO MUY PESADO O NO VALIDO";
         return false;
      } elseif (!isset($this->paData['MTITULO']) || empty($this->paData['MTITULO']) || preg_match("([\'\"\&])", $this->paData['MTITULO'])) {
         $this->pcError = 'TITULO DE LA TESIS NO DEFINIDO, INVALIDO O CONTIENE CARACTERES ESPECIALES';
         return false;
      } elseif (!isset($this->paData['CEMAIL1']) || empty($this->paData['CEMAIL1']) || !filter_var($this->paData['CEMAIL1'], FILTER_VALIDATE_EMAIL)) {   // OJOFPM REGEX
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO DEL PRIMER TITULANDO';
         return false;
      } elseif (strlen($this->paData['CCODALU1']) != 10){  
         $this->pcError = 'EL CODIGO DE ALUMNO DEBE DE SER DE 10 DIGITOS DEL PRIMER TITULANDO';
         return false;
      } elseif (!isset($this->paData['CNROCEL1']) || empty($this->paData['CNROCEL1']) || !ctype_digit($this->paData['CNROCEL1']) || strlen($this->paData['CNROCEL1']) > 12) {
         $this->pcError = 'NUMERO DE CELULAR NO DEFINIDO O INVALIDO DEL PRIMER TITULANDO';
         return false;
      }
      return true;
   }
   protected function mxGrabarIngresoRapidoTesis($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_T01MTES_8('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION SQL"}' : $laFila[0];
      $laData = json_decode($laFila[0], true);
      if (!empty($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $lcCodPdf = $laData['CIDTESI'];
      if ($this->paFile['error'] == 0) {
         $llOk = fxSubirPDF($this->paFile, 'Tesis', $lcCodPdf);
         if (!$llOk) {
            $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO';
            return false;
         }
      }
      return true;
   }

   // -----------------------------------------------------------
   // Obtener Cargo (Decano o Director escuela) y las especialidades de la escuela
   // 2020-05-17 FLC  
   // -----------------------------------------------------------
   public function omBuscarModalidad() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarModalidad($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarModalidad($p_oSql) {
      $lcUniAca = $this->paData['CUNIACA'];
      //TRAER LAS ESPECIALIDADES DE CADA UNIDAD ACADEMICA
      $lcSql = "SELECT A.cPrefij, A.cDescri FROM S01DLAV A
                      INNER JOIN S01TCCO B ON B.cUniAca = A.cUniAca
                      WHERE A.cEstado = 'A' AND A.cUniAca = '$lcUniAca' ORDER BY A.cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CPREFIJ' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   // -----------------------------------------------------------
   // Obtener Cargo (Decano o Director escuela) y las especialidades de la escuela 
   // ROL Y CARGO DE LOS DICTAMINADORES
   // 2020-05-17 FLC  
   // -----------------------------------------------------------
   public function omInitCargoEscuelaJuradosRolCargo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCargoEscuela($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitCargoEscuelaJuradosRolCargo($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCargoEscuelaJuradosRolCargo($p_oSql) {
      //TRAER LOS CARGOS DE LA SUSTENTACION
      $lcSql = "SELECT cCodigo, cDescri FROM S01TTAB WHERE CCODTAB = '140' AND nOrden != 0";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDicCar[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      //TRAER LOS ROLES DE LA SUSTENTACION
      $lcSql = "SELECT cCodigo, cDescri FROM S01TTAB WHERE CCODTAB = '144' AND nOrden != 0";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDicRol[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   // -----------------------------------------------------------
   // OBTENER LAS ESCUELAS DE LA FACULTAD
   // 2020-05-17 FLC  
   // -----------------------------------------------------------
   public function omInitFacultadEscuelas() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitFacultadEscuelas($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitFacultadEscuelas($p_oSql) {
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT A.cCenCos, A.cDescri, A.cUniAca, B.CNOMUNI FROM S01TCCO A
                     INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca WHERE  A.CNIVEL LIKE 
                         (SELECT cNivel FROM S01TCCO 
                             WHERE CCENCOS = '{$lcCenCos}' AND CTIPO = 'A')||'%' AND A.CUNIACA != '00'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paCargo[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CUNIACA' => $laFila[2], 'CNOMUNI' => $laFila[3]];
      }
      return true;
   }

   // -----------------------------------------------------------
   // OBTENER LOS DOCENTES QUE FALTA FIRMAR POR ESCUELA
   // 2020-05-17 FLC  
   // -----------------------------------------------------------
   public function omFaltaFirmaDocente() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxFaltaFirmaDocente($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxFaltaFirmaDocente($p_oSql) {
      $lcSql = "SELECT A.CIDTESI, F.CNOMBRE, E.CCODDOC, E.CNOMBRE--, G.TFIRMA, CASE WHEN G.CNROFIR != '' THEN 1 ELSE 0 END AS FLAG
         FROM T01MTES A
         INNER JOIN T01DDIC B ON B.CIDTESI = A.CIDTESI
         INNER JOIN T01DALU C ON C.CIDTESI = A.CIDTESI
         INNER JOIN T02MLIB D ON D.CIDTESI = A.CIDTESI
         INNER JOIN V_A01MDOC E ON E.CCODDOC = B.CCODDOC
         INNER JOIN V_A01MALU F ON F.CCODALU = C.CCODALU
         LEFT OUTER JOIN T02DLIB G ON G.CNRODNI = E.CNRODNI AND G.CIDLIBR = D.CIDLIBR 
         WHERE E.CESTADO = 'A' AND A.CUNIACA = '{$this->paData['CUNIACA']}' AND 0 = CASE WHEN G.CNROFIR != '' THEN 1 ELSE 0 END
         ORDER BY A.CIDTESI ASC";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDocente[] = ['CIDTESI' => $laFila[0], 'CNOMALU' => str_replace('/', ' ', $laFila[1]), 
                               'CCODDOC' => $laFila[2], 'CNOMDOC' => str_replace('/', ' ', $laFila[3])];
         $i += 1;
      }
      if ($i == 0) {
         $this->paDocente[] = ['CIDTESI' => '', 'CNOMALU' => '', 'CCODDOC' => '', 'CNOMDOC' => ''];
      }
      return true;
   }

   public function omServicioBibliotecaTurniting() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxServicioBibliotecaTurniting($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxServicioBibliotecaTurniting($p_oSql) {
      $this->paDatos = [];
      foreach ($this->paData['ALUMNOS'] as $laFila) {
          $autores[] = ['codigo' => $laFila['CCODALU']];  
      }
      $params = ['titulo' => $this->paData['MTITULO'], "tipo" => substr($this->paData['CTIPO'], 0, 1), 'autores' => $autores]; 
      $params = json_encode($params);
      // Create the context for the request
      $laContext = stream_context_create(array(
        'http' => [
           'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                       "Content-Length: ".strlen($params)."\r\n",
           'method' => 'GET', 
           'content' => $params]
      ));
      $response = file_get_contents('http://cib.ucsm.edu.pe/api-rest-biblio/solicitudes-turnitin', false, $laContext);
      if (!$response) {
        $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO DE BIBLIOTECA';
        return false;
      } 
      $response = json_decode($response, true);
      if ($response['estado'] == 0){
        $this->paDatos['URL'] = $response['url'];
        $this->paDatos['CFLAG'] = 0;
        $this->paDatos['MENSAJE'] = '';
        return true;
      } elseif ($response['estado'] == 1) {
        $this->paDatos['MENSAJE'] = $response['mensaje'];
        $this->paDatos['CFLAG'] = 1;
        return true;
      } elseif ($response['estado'] == 2) {
        $this->paDatos['MENSAJE'] = $response['mensaje'];
        $this->paDatos['CPORCEN'] = $response['porcentaje'].'% de Copia';
        $this->paDatos['URL']= $response['url_pdf'];
        $llOk = $this->mxCambioEstado($p_oSql);
        $this->paDatos['CFLAG'] = 2;
        return true;
      } else {
        $this->paDatos['MENSAJE'] = $response['mensaje'];
        $this->paDatos['CPORCEN'] = $response['porcentaje'].'% de Copia';
        $this->paDatos['CFLAG'] = 3;
        /*$response = file_get_contents('http://cib.ucsm.edu.pe/api-rest-biblio/solicitudes-turnitin?venviar=1', false, $laContext);
        if (!$response) {
          $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO DE  BIBLIOTECA2';
          return false;
        }
        $this->paDatos['URL'] = $response['url'];*/
        return true;
      }
      return true;
   }

   protected function mxCambioEstado($p_oSql){
      // la respuesta de biblioteca es que procede
      $lcSql = "UPDATE T01MTES SET cEstTes = 'H' WHERE cIdTesi = '{$this->paData['CIDTESI']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR COMUNICARSE CON LA OFICINA ERP';
         return false;
      }
      return true;
   }

   //-----------------------------------------------
   // TRAE TODO LOS ALUMNOS EN CESTTES I PARA CAMBIAR LA FECHA DE SUSTENTACION
   // 2020-06-30 FLC
   //-----------------------------------------------

   public function omInitConsultaListosParaSustentar() {
      $llOk = $this->mxValParamConsultaListosParaSustentar();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitConsultaListosParaSustentar($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamConsultaListosParaSustentar() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO O NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxInitConsultaListosParaSustentar($p_oSql) {
      if ($this->paData['CUSUCOD'] == '2682' || $this->paData['CUSUCOD'] == '2144' || $this->paData['CUSUCOD'] == '1099') {
         $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri FROM T01DALU A
                     INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca
                     INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu
                     LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes
                     WHERE B.cEstTes = 'I' AND B.cTipo IN ('M0', 'D0') ORDER BY B.cIdTesi";
      } else {
         $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri FROM T01DALU A
                     INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca
                     INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu
                     LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes
                     WHERE B.cEstTes = 'I' AND B.cUniAca IN (
                     SELECT DISTINCT G.cUniAca FROM V_S01PCCO F
                     INNER JOIN S01TCCO G ON G.cCenCos = F.cCenCos
                     WHERE F.cCodUsu = '{$this->paData['CUSUCOD']}' AND G.cEstado = 'A' AND G.cUniAca != '00') ";
      }
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 
                             'MTITULO' => $laFila[3], 'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 
                             'CDESEST' => $laFila[7]];
      }
      return true;
   }

   //-----------------------------------------------
   // CONSULTA LOS DATOS DE LA TESIS A CAMBIAR LA FECHA DE SUSTENTACION
   // 2020-06-30 FLC
   //-----------------------------------------------

   public function omVerTesisCambiarFechaSustentacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerTesisCambiarFechaSustentacion($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxVerTesisCambiarFechaSustentacion($p_oSql) {
      $lcSql = "SELECT A.cIdTesi, B.cEstado, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.tDiaSus FROM T01DALU A
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes
                  WHERE B.cIdTesi = '{$this->paData['CIDTESI']}' ";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paDatos = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'MTITULO' => $laFila[2], 'CUNIACA' => $laFila[3].' '.$laFila[4], 
                             'CESTTES' => $laFila[5], 'CDESEST' => $laFila[6], 'TDIASUS' => $laFila[7]];
      // trae los datos de los alumnos
      $lcSql = "SELECT A.cCodAlu, B.cNombre FROM T01DALU A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' ";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMALU' => str_replace('/', ' ', $laFila[1])];
      }
      //trae los datos de sus jurados
      $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri
                  FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'D' AND A.cEstado = 'A' ORDER BY A.cCodDoc";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMDOC' => str_replace('/', ' ', $laFila[1]), 'CCARGO' => $laFila[2]];
      }
      return true;
   }

   //-----------------------------------------------
   // GRABAR EL CAMBIO DE FECHA DE SUSTENTACION
   // 2020-06-30 FLC
   //-----------------------------------------------

   public function omGrabarCambioFechaSustentacion() {
      $llOk = $this->mxValGrabarCambioFechaSustentacion($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarCambioFechaSustentacion($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarCambioFechaSustentacion() {
      if (!isset($this->paData['TDIASUS']) || empty($this->paData['TDIASUS']) || $this->paData['TDIASUS'] == null) {
         $this->pcError = 'ERROR FECHA INVALIDA';
         return false;
      }
      return true;
   }

   protected function mxGrabarCambioFechaSustentacion($p_oSql) {
      $lcSql = "UPDATE T01MTES SET tDiaSus = '{$this->paData['TDIASUS']}' WHERE CIDTESI = '{$this->paData['CIDTESI']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE GRABA EL CAMBIO O ASIGNACION DE FECHA DE SUSTENTACION';
         return false;
      }
      /*$lcSql = "UPDATE T01DDEC SET dFecha = '{$this->paData['TDIASUS']}' WHERE CIDTESI = '{$this->paData['CIDTESI']}' AND cEstTes = 'I'";
      $R1 = $p_oSql->omExec($lcSql);*/
      return true;
   }

   // ----------------------------------------------------------- 
   // 2019-05-17 FLC Seleccion Semana de Dia de Sustentacion PLT2140 - PLT2170 
   // ----------------------------------------------------------- 
   public function omVerDatosDocenteTesis() { 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxVerDatosDocenteTesis($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxVerDatosDocenteTesis($p_oSql) { 
      $lcIdTesi = $this->paData['CIDTESI']; 
      //Trae matriz de horarios de los dictaminadores 
      $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cEmail FROM T01DALU A INNER JOIN V_A01MALU B ON A.cCodAlu = B.cCodAlu WHERE cIdTesi = '$lcIdTesi'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $i = 0; 
      while ($laFila = $p_oSql->fetch($R1)) { 
         $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CEMAIL' => $laFila[2]]; 
         $i++; 
      } 
      //Trae matriz de horarios de los dictaminadores 
      $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, A.cCargo, D.cEmail FROM T01DDOC A  
                  INNER JOIN V_A01MDOC B ON A.cCodDoc = B.cCodDoc  
                  INNER JOIN S01TTAB C ON A.cCargo = C.cCodigo 
                  LEFT OUTER JOIN S01MPER D ON D.cNroDni = B.cNroDni  
                  WHERE A.cIdTesi = '$lcIdTesi' AND C.cCodTab = '140' ORDER BY cCodDoc"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $i = 0; 
      $lcFlag = 'N'; 
      while ($laFila = $p_oSql->fetch($R1)) { 
         $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CDESCRI' => $laFila[2], 'CCARGO' => $laFila[3], 'CEMAIL' => $laFila[4]]; 
         $i++; 
      } 
      return true; 
   } 

   //----------------------------------------------- 
   // bandeja de observaciones alumno 
   // 2020-06-22 FLC 
   //----------------------------------------------- 
 
   public function omBandejaObservacionesAlumno() { 
      $llOk = $this->mxValBandejaObservacionesAlumno(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxBandejaObservacionesAlumno($loSql); 
      if (!$llOk) { 
         $loSql->omDisconnect(); 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValBandejaObservacionesAlumno() { 
      if (strlen($this->paData['CIDTESI']) != 6 AND intval($this->paData['CIDTESI'])) { 
         $this->pcError = "EL IDENTIFICADOR DE LA TESIS NO ES VALIDO"; 
         return false; 
      }  
      return true; 
   } 
 
   protected function mxBandejaObservacionesAlumno($p_oSql) { 
      $lcSql = "SELECT cIdTesi, mTitulo, cEstTes FROM T01MTES WHERE cEstPro != 'K' AND cEstado != 'X' AND CIDTESI = '{$this->paData['CIDTESI']}'";  
      $R1 = $p_oSql->omExec($lcSql);  
      $laFila = $p_oSql->fetch($R1);  
      $this->paDatos = ['CIDTESI' => $laFila[0], 'MTITULO' =>$laFila[1], 'CESTTES' =>$laFila[2]];  
      $lcEstTes = $laFila[2];  
      if (in_array($lcEstTes, ['B','D','F','J'])) {  
         $lcCatego = 'A';  
         if ($lcEstTes == 'D') {  
            $lcCatego = 'B';  
         } elseif ($lcEstTes == 'F') {  
            $lcCatego = 'C';  
         }  
         $lcSql = "SELECT A.cCodDoc, B.cNombre FROM T01DDOC A  
                     INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc  
                     WHERE CIDTESI = '{$this->paData['CIDTESI']}' AND A.cCatego = '{$lcCatego}'";  
         $R1 = $p_oSql->omExec($lcSql);  
         while ($laFila = $p_oSql->fetch($R1)){  
            $lcPath = 'Docs/Tesis/'.$this->paData['CIDTESI'].'_O_'.$laFila[0].'.pdf';  
            if (file_exists ( $lcPath )) {  
               $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1])];  
            }  
              
         }  
      } else {  
         $this->pcError = "NO SE PERMITEN HACER CAMBIOS AL PROYECTO DE TESIS";  
         return false;  
      }  
      return true;  
   }  
 
   //----------------------------------------------- 
   // GRABAR EL PDF DEL LEVANTAMIENTO DE OBSERVACIONES ALUMNO 
   // 2020-06-22 FLC 
   //----------------------------------------------- 
 
   public function omGrabarLevantamientoObservacionesAlumnoPDF() { 
      $llOk = $this->mxValGrabarLevantamientoObservacionesAlumnoPDF(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxGrabarLevantamientoObservacionesAlumnoPDF($loSql); 
      if (!$llOk) { 
         $loSql->omDisconnect(); 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValGrabarLevantamientoObservacionesAlumnoPDF() { 
      if (!isset($this->paFile) || $this->paFile['size'] > 56214400 || ($this->paFile['type'] != 'application/pdf')) { 
         $this->pcError = "ARCHIVO MUY PESADO O NO VALIDO"; 
         return false; 
      } elseif (strlen($this->paData['CIDTESI']) != 6 AND intval($this->paData['CIDTESI'])) {  
         $this->pcError = "EL IDENTIFICADOR DE LA TESIS NO ES VALIDO";  
         return false;  
      } 
      return true; 
   } 
 
   protected function mxGrabarLevantamientoObservacionesAlumnoPDF($p_oSql) { 
      $lcSql = "SELECT cEstTes FROM T01MTES WHERE CESTPRO != 'K' AND cEstado != 'X' AND CIDTESI = '{$this->paData['CIDTESI']}'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      $lcEstTes = $laFila[0]; 
      if (in_array($lcEstTes, ['B','C','D','E','F','H','I','J'])) { 
         $lcCodPdf = $this->paData['CIDTESI']; 
         if ($this->paFile['error'] == 0) { 
            $llOk = fxSubirPDF($this->paFile, 'Tesis', $lcCodPdf); 
            if (!$llOk) { 
               $this->pcError = '*UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO*'; 
               return false; 
            } 
         } 
      } else { 
         $this->pcError = "NO PUEDE HACER OBSERVACIONES EN ESTE MOMENTO"; 
         return false; 
      } 
      $lcCatego = ''; 
      if ($lcEstTes == 'B') { 
         $lcCatego = 'A'; 
      } else if ($lcEstTes == 'D') { 
         $lcCatego = 'B'; 
      } else if ($lcEstTes == 'F') { 
         $lcCatego = 'C'; 
      } else if ($lcEstTes == 'J') { 
         $lcCatego = 'D'; 
      } 
      $lcSql = "UPDATE T01DDOC SET cResult = 'P' WHERE CIDTESI = '{$this->paData['CIDTESI']}' AND cCatego = '{$lcCatego}' AND cResult = 'O'";  
      $llOk = $p_oSql->omExec($lcSql);  
      if (!$llOk) { 
         $this->pcError = '*ERROR AL CAMIAR EL TITULO, NO INCLUIR CARACTERES ESPECIALES*'; 
         return false; 
      } 
      $lcSql = "INSERT INTO T01DLOG(cIdTesi,cEstTes,mObserv,cEstado,cEstPro,cUsuCod,tModifi)values  
                     ('{$this->paData['CIDTESI']}','{$lcEstTes}','EL ALUMNO HIZO LA ACTUALIZACION / EL LEVANTAMIENTO DE LAS OBSERVACIONES DE SU TESIS EN EL SISTEMA','A','N','9999',NOW())";  
      $llOk = $p_oSql->omExec($lcSql);   
      if (!$llOk) {  
         $this->pcError = "**ERROR AL CAMIAR EL TITULO, NO INCLUIR CARACTERES ESPECIALES**";    
         return false;  
      } 
      return true; 
   } 

   //-----------------------------------------------   
   // bandeja de observaciones alumno   
   // 2020-06-22 FLC   
   //-----------------------------------------------   
   
   public function omObservacionesAlumno() {   
      $llOk = $this->mxValObservacionesAlumno();   
      if (!$llOk) {   
         return false;   
      }   
      $loSql = new CSql();   
      $llOk = $loSql->omConnect();   
      if (!$llOk) {   
         $this->pcError = $loSql->pcError;   
         return false;   
      }   
      $llOk = $this->mxObservacionesAlumno($loSql);   
      if (!$llOk) {   
         $loSql->omDisconnect();   
         return false;   
      }   
      $loSql->omDisconnect();   
      return $llOk;   
   }   
   
   protected function mxValObservacionesAlumno() {   
      if (strlen($this->paData['CCODALU']) != 10 AND intval($this->paData['CCODALU'])) {   
         $this->pcError = "EL CODIGO DEL ALUMNO NO ES VALIDO";   
         return false;   
      }  
      return true;   
   }   
   
   protected function mxObservacionesAlumno($p_oSql) {   
      $lcIdTesi = '';  
      $lcSql = "SELECT cIdTesi FROM T01DALU WHERE cCodAlu = '{$this->paData['CCODALU']}' AND cEstado = 'A'";   
      $R1 = $p_oSql->omExec($lcSql);  
      $laFila = $p_oSql->fetch($R1);  
      $lcIdTesi = $laFila[0];  
      $lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cEstTes, B.cDescri, C.cDescri, D.cDescri, A.cTipo, A.tDiaSus, A.cEstDec 
                  FROM T01MTES A   
                  INNER JOIN V_S01TTAB B ON SUBSTRING(B.cCodigo, 1, 1) = A.cEstTes   
                  INNER JOIN V_S01TTAB C ON SUBSTRING(C.cCodigo, 0, 3) = A.cTipo AND C.cCodTab = '143'   
                  LEFT OUTER JOIN S01DLAV D ON D.cPrefij = A.cPrefij   
                  WHERE A.cEstado != 'X' AND B.CCODTAB = '252' AND A.cIdTesi = '{$lcIdTesi}'";
      $R1 = $p_oSql->omExec($lcSql);   
      $laFila = $p_oSql->fetch($R1);   
      if ($laFila[0] == '') {  
         $this->pcError = 'SU PROCESO DE TESIS HA CULMINADO !!!';  
         return false;  
      }  
      $this->paDatos = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CESTTES' =>$laFila[2], 'CESTADO' => 0, 'CDESTES' =>$laFila[3],  
                        'CDESTIP'   => $laFila[4], 'CESPECI' => $laFila[5], 'CTIPO' => $laFila[6], 'TDIASUS' => $laFila[7], 'CESTDEC' => $laFila[8]];   
      $lcSql = "SELECT A.cIdTesi, A.cCodAlu, REPLACE(B.cNombre,'/',' ') FROM T01DALU A  
                 INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu  
                 WHERE A.cIdTesi = '{$lcIdTesi}' AND A.cEstado = 'A'";   
      $R1 = $p_oSql->omExec($lcSql);   
      while ($laFila = $p_oSql->fetch($R1)){   
         $this->paAlumnos[] = ['CCODALU' => $laFila[1], 'CNOMBRE' => $laFila[2]];   
         $lcIdTesi = $laFila[0];  
      }   
      $lcCatego = '';  
      if (in_array($this->paDatos['CESTTES'], ['B','C','D','E','F','H','I','J'])) {  
         $this->paDatos['CESTADO'] = 1;  
      } else if ($this->paDatos['CESTTES'] == 'G'){  
         $this->paDatos['CESTADO'] = 2;  
      }  
      if ($this->paDatos['CESTTES'] == 'B') {  
         $lcCatego = 'A';  
         $this->paDatos['CCATEGO'] = 'Dictaminador de Proyecto Tesis';  
      } elseif ($this->paDatos['CESTTES'] == 'D') {  
         $lcCatego = 'B';  
         $this->paDatos['CCATEGO'] = 'Asesor de Borrador Tesis';  
      } elseif ($this->paDatos['CESTTES'] == 'F') {  
         $lcCatego = 'C';  
         $this->paDatos['CCATEGO'] = 'Dictaminador del Borrador Tesis';  
      } elseif ($this->paDatos['CESTTES'] >= 'I') {  
         $lcCatego = 'D';  
         $this->paDatos['CCATEGO'] = 'Jurado de Sustentacion';  
      }  
      $lcSql = "SELECT A.cCodDoc, REPLACE(B.cNombre,'/',' '), B.cEmail, C.cDescri FROM T01DDOC A  
                 INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc  
                 INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253'  
                 WHERE A.cIdTesi = '$lcIdTesi' AND A.cCatego = '{$lcCatego}' AND A.cEstado = 'A'";   
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laFila = $p_oSql->fetch($R1)){   
         if ($laFila[3] == 'APROBADO' && $lcCatego == 'D') {
            $lcDescri = 'FIRMADO';
         } else {
            $lcDescri = $laFila[3];
         } 
         $this->paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CEMAIL' => $laFila[2], 'CRESULT' => $lcDescri];   
      }   
      // Observaciones  
      $lcSql = "SELECT TRIM(B.cNombre), TRIM(A.mObserv), TO_CHAR(A.tModifi,'YYYY-MM-DD') 
                  FROM T01DLOG A 
                  LEFT OUTER JOIN V_S01TUSU_1 B ON B.CCODUSU = A.CUSUCOD 
                  WHERE A.cEstTes IN ('B','D','F','J') AND A.cEstPro = 'N' AND A.cEstado = 'A' AND A.cIdTesi = '{$lcIdTesi}' ORDER BY tModifi DESC "; 
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)){   
         $this->paObserv[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'MOBSERV' => $laFila[1], 'FECHA' => $laFila[2]];   
      }   
      return true;   
   }   

   // ----------------------------------------------------------- 
   // 2018-07-17 PFC Init Anular Plan/Proyecto 
   // ----------------------------------------------------------- 
   public function omInitAnularPlanTesisV2() { 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxInitAnularPlanTesisV2($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxInitAnularPlanTesisV2($p_oSql) {   
      $lcCodAlu = $this->paData['CCODALU'];   
      $lcSql = "SELECT B.cNivel FROM T01MTES A INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi AND A.cEstPro != 'I' AND A.cEstado != 'X' AND B.cEstado != 'X' WHERE B.cCodAlu = '$lcCodAlu'";   
      $R1 = $p_oSql->omExec($lcSql);   
      $laFila = $p_oSql->fetch($R1);   
      if (empty($laFila[0])) {   
         $this->pcError = "NO TIENE TESIS VIGENTE ACTUALMENTE";   
         return false;   
      // S = Secundario alumnos que pertenecen a la tesis pero no son el principal   
      }elseif($laFila[0] == 'S'){   
         $this->pcError = "NO TIENE PERMISO PARA ESTA OPCION";   
         return false;   
      // P = principal el que genera la deuda y sube el archivo   
      }elseif($laFila[0] == 'P'){   
         $lcSql = "SELECT A.cIdTesi, A.mTitulo, B.cCodAlu, C.cNombre FROM T01MTES A  
                     INNER JOIN T01DALU B ON B.cIdTesi = A.cIdtesi  
                     INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu 
                     WHERE A.cEstado != 'X' AND B.cCodAlu = '$lcCodAlu'";   
         $R1 = $p_oSql->omExec($lcSql);   
         $laFila = $p_oSql->fetch($R1);   
         if (empty($laFila[0])) {   
            $this->pcError = "NO HAY DATOS DISPONIBLES EN ESTE MOMENTO";   
            return false;   
         }   
         $this->paData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CCODALU' => $laFila[2], 'CNOMBRE' => $laFila[3]];   
         return true;   
      }   
   } 
 
   // ----------------------------------------------------------- 
   // 2019-05-17 PFC Anular Plan de Tesis Alumno Creacion PLT1210 
   // ----------------------------------------------------------- 
   public function omAnularPlanTesisAlumnoV2() { 
      $llOk = $this->mxValAnularPlanTesisAlumnoV2(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxAnularPlanTesisAlumnoV2($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValAnularPlanTesisAlumnoV2() { 
      if (!isset($this->paData['CCODALU']) || empty(trim($this->paData['CCODALU']) || $this->paData['CCODALU'] == null)) { 
         $this->pcError = "ID NO VALIDO"; 
         return false; 
      } 
      return true; 
   }  

   # ---------------------------------------------------------- 
   # ANULAR TESIS DE TITULANDOS POR LAS ESCUELAS PROFESIONALES
   # Creacion 2022-05-19 APR
   # ---------------------------------------------------------- 
   public function omAnularTesisTitulandos() { 
      $llOk = $this->mxValAnularTesisTitulandos(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxAnularPlanTesisAlumnoV2($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValAnularTesisTitulandos() { 
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO O NO DEFINIDO';
         return false;
      } elseif (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6) {
         $this->pcError = 'CODIGO DE TESIS INVÁLIDO O NO DEFINIDO';
         return false;
      } elseif (!isset($this->paData['MOBSERV']) || strlen(trim($this->paData['MOBSERV'])) == 0) {
         $this->pcError = 'OBSERVACIÓN NO DEFINIDA O INVALIDA';
         return false;
      } 
      return true; 
   } 
 
   protected function mxAnularPlanTesisAlumnoV2($p_oSql) { 
      $lcIdTesi = $this->paData['CIDTESI']; 
      $lcSql = "UPDATE T01MTES SET cEstado = 'X', cEstPro = 'X', cEstTes = 'X', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '$lcIdTesi'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR: NO SE PUDO ANULAR EL REGISTRO EN MAESTRO DE TESIS"; 
         return false; 
      } 
      $lcSql = "UPDATE T01DALU SET cEstado = 'X', cNivel = 'X',cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '$lcIdTesi'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR: NO SE PUDO ANULAR EL REGISTRO EN MAESTRO DE TITULANDOS"; 
         return false; 
      } 
      $lcSql = "UPDATE T01DDIC SET cEstado = 'X', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '$lcIdTesi' AND cEstado != 'X'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR: NO SE PUDO ANULAR EL REGISTRO EN EL ERP"; 
         return false; 
      } 
      $lcSql = "UPDATE T01DDOC SET cEstado = 'X', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '$lcIdTesi' AND cEstado != 'X'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR: NO SE PUDO ANULAR EL REGISTRO EN MAESTRO DE DICTAMINADORES"; 
         return false; 
      } 
      $lcSql = "UPDATE T01DDEC SET cEstado = 'X', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '$lcIdTesi' AND cEstado != 'X'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR: NO SE PUDO ANULAR EL REGISTRO EN MAESTRO DE DECRETOS"; 
         return false; 
      } 
      $lcSql = "INSERT INTO T01DLOG (cIdTesi, cEstTes, mObserv, cEstado, cEstPro, cUsuCod, tModifi) VALUES 
                  ('$lcIdTesi','O','{$this->paData['MOBSERV']}','A','N','{$this->paData['CUSUCOD']}',NOW())"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR: NO SE PUDO REGISTRAR MOTIVO DE ANULACIÓN DE TESIS"; 
         return false; 
      }       
      return true; 
   }
 
   //------------------------------------------------------------------------------------------------ 
   // Valida en que estado se encuentra Mnu4100 
   //  0 - generar deuda o falta pago) 
   //  1 - Subir proyecto tesis 
   //  2 - Seguimiento de tesis alumno  
   // 2020-07-18 FLC 
   //------------------------------------------------------------------------------------------------ 
   public function omMenuAlumnoV2(){ 
      $llOk = $this->mxValMenuAlumnoV2(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxMenuAlumnoV2($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValMenuAlumnoV2() { 
      if (!isset($this->paData['CCODALU']) || empty(trim($this->paData['CCODALU']) || $this->paData['CCODALU'] == null)) { 
         $this->pcError = "CODIGO DE ALUMNO NO DEFINIDO O INVALIDO"; 
         return false; 
      } /*elseif(!isset($this->paData['CEGRESO']) || empty(trim($this->paData['CEGRESO']) || $this->paData['CEGRESO'] == null)){
         $this->pcError = "ESTIMADO ESTUDIANTE EL CODIGO DE ALUMNO SELECCIONADO NO ES ESTA CONSIDERADO COMO EGRESADO";
         return false;
      }*/
      return true; 
   } 
 
   protected function mxMenuAlumnoV2($p_oSql) {
      $lcSql = "SELECT cUniAca FROM V_A01MALU WHERE cEstado != 'X' AND cCodAlu = '{$this->paData['CCODALU']}'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $RS = $p_oSql->fetch($R1); 
      if ($RS[0] != '') { 
         $lcUniAca = $RS[0];
      }
      $lcSql = "SELECT A.cIdTesi FROM T01MTES A INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi WHERE A.cEstado != 'X' AND B.cCodAlu = '{$this->paData['CCODALU']}'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $RS = $p_oSql->fetch($R1); 
      if ($RS[0] != '') { 
         $this->paDatos['CESTADO'] = 2;
         $this->paDatos['CUNIACA'] = $lcUniAca;
         return true; 
      }
      $lcSql = "SELECT cEstado FROM T01DDEU WHERE cEstado NOT IN ('X', 'B') AND cCodAlu = '{$this->paData['CCODALU']}'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $RS = $p_oSql->fetch($R1); 
      if ($RS[0] == '' OR $RS[0] == 'P') { 
         $this->paDatos['CESTADO'] = 1; 
         $this->paDatos['CUNIACA'] = $lcUniAca;
         return true; 
      } 
      $this->paDatos = ['CESTADO' => 1]; 
      $this->paDatos['CUNIACA'] = $lcUniAca;
      return true; 
   } 
   // ----------------------------------------------------------- 
   // Inicio generacion de deuda para proyecto o plan de tesis 
   // 2018-07-19 FLC  
   // ----------------------------------------------------------- 
   public function omInitPlanV2() { 
      $llOk = $this->mxValParamInitPlanV2(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxInitPlanV2($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValParamInitPlanV2() { 
      if (!isset($this->paData['CCODALU']) or strlen($this->paData['CCODALU']) != 10) { 
         $this->pcError = 'CODIGO DE ALUMNO NO ESTA DEFINIDO O ES INVALIDO'; 
         return false; 
      } 
      return true; 
   } 
 
   protected function mxInitPlanV2($p_oSql) {   
      $lcCodAlu = $this->paData['CCODALU'];     
      // Tipos de PDTs (bachiller, titulo, maestria, doctorado, etc.) 
      $lcSql = "SELECT C.cCodigo, C.cDescri, A.nLimAlu FROM T01PUNI A 
                INNER JOIN A01MALU B ON B.cUniAca = A.cuniAca 
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '143' AND SUBSTRING(C.cCodigo, 0, 2) = A.cTipo --AND C.cCodigo!='B0'
                WHERE B.cCodAlu = '$lcCodAlu'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $llOk = false; 
      while ($laFila = $p_oSql->fetch($R1)) {
         if (TRIM($laFila[0]) != 'B0'){
            $this->paTipo[] = ['CCODIGO' => TRIM($laFila[0]), 'CDESCRI' => $laFila[1], 'NLIMALU' => $laFila[2]]; 
         }
         //$this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'NLIMALU' => $laFila[2]]; 
         $llOk = true; 
      } 
      if (!$llOk) { 
         $this->pcError = 'NO HAY TIPOS DE PLANES/PROYECTOS DE TESIS PARA UNIDAD ACADEMICA DE ALUMNO|'; 
         return false; 
      } 
      // Valida codigo de alumno 
      $lcSql = "SELECT cNomUni, cNroDni, cNombre, cNroCel, cEmail, cUniAca FROM V_A01MALU WHERE cCodAlu = '$lcCodAlu'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      if (empty($laFila[0])) { 
         $this->pcError = 'CODIGO DE ALUMNO NO EXISTE'; 
         return false; 
      } 
      $laData = ['CCODALU' => $lcCodAlu, 'NMONTO' => $this->paData['NMONTO'], 'CNOMUNI'=> $laFila[0], 'CNRODNI'=> $laFila[1], 'CNOMBRE'=> str_replace('/', ' ', $laFila[2]), 
                 'CNROCEL'=> $laFila[3], 'CEMAIL'=> $laFila[4], 'CUNIACA'=> $laFila[5]]; 
      // Valida que no tenga plan de tesis vigente 
      $lcSql = "SELECT A.cIdTesi FROM T01MTES A INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi AND A.cEstPro != 'I' AND A.cEstado != 'X' AND B.cEstado != 'X' WHERE B.cCodAlu = '$lcCodAlu'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      if (!empty($laFila[0]) || $laFila[0] != null) { 
         $this->pcError = 'ALUMNO TIENE PLAN DE TESIS VIGENTE'; 
         return false; 
      } 
      $lcUniPre = $laData['CUNIACA'];
      // TRAER LAS ESPECIALIDADES DE LA UNIDAD ACADEMICA
      $lcSql = "SELECT cPrefij, cDescri FROM S01DLAV WHERE cUniaca = '$lcUniPre' ORDER BY cPrefij DESC"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $llOk = false; 
      while ($laFila = $p_oSql->fetch($R1)) { 
         $this->paPreFij[] = ['CPREFIJ' => $laFila[0], 'CDESCRI' => $laFila[1]]; 
         $llOk = true; 
      } 
      $this->paData = $laData + ['SCREEN'=> 2]; 
      return true; 
   } 
 
   // ----------------------------------------------------------- 
   // Buscar el tipo, su especialidad y limite de alumnos 
   // 2020-07-19 FLC  
   // ----------------------------------------------------------- 
   public function omBuscarEspecialidadPregradoV2() { 
      $llOk = $this->mxValBuscarEspecialidadPregradoV2(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxBuscarEspecialidadPregradoV2($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValBuscarEspecialidadPregradoV2() { 
      if (empty(trim($this->paData['CBUSTIP'])) && strlen($this->paData['CBUSTIP'] != 1)) { 
         $this->pcError = 'CLAVE DE BÚSQUEDA DE ESPECIALIDAD NO DEFINIDA'; 
         return false; 
      } elseif (empty(trim($this->paData['CUNIACA'])) && strlen($this->paData['CUNIACA'] != 2)) { 
         $this->pcError = 'UNIDAD ACADEMICA INCORRECTA'; 
         return false; 
      } 
      return true; 
   } 
    
   protected function mxBuscarEspecialidadPregradoV2($p_oSql) {   
      $lcBusTip = $this->paData['CBUSTIP'];   
      $lcUniAca = $this->paData['CUNIACA'];   
      // Especialidad   
      if ($lcBusTip != 'B') {   
         $lcSql ="SELECT cPrefij, cDescri FROM S01DLAV   
               WHERE cEstado != 'I' AND cUniAca = '$lcUniAca' ORDER BY cDescri";   
         $R1 = $p_oSql->omExec($lcSql);   
         while ($laFila = $p_oSql->fetch($R1)) {   
            $this->paDatos['CESPECI'][] = ['CPREFIJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CFLAG' => 1];   
         }   
      }   
      // Limite de Alumnos   
      $lcSql ="SELECT cNivel FROM S01TUAC WHERE cUniAca = '$lcUniAca'";   
      $R1 = $p_oSql->omExec($lcSql);   
      $laFila = $p_oSql->fetch($R1);   
      $lnAluLim = 2; 
      if ($laFila[0] == '04') { 
         $lnAluLim = 3; 
      } 
      $this->paDatos['NALULIM'] = $lnAluLim; 
      return true;   
   } 
 
   // ----------------------------------------------------------- 
   // Buscar alumno por el codigo de alumno 
   // 2020-07-19 FLC  
   // ----------------------------------------------------------- 
   public function omBuscarAlumnoCodigo() { 
      $llOk = $this->mxValBuscarAlumnoCodigo(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxBuscarAlumnoCodigo($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValBuscarAlumnoCodigo() { 
      if (!is_numeric($this->paData['CCODALU_2']) || strlen($this->paData['CCODALU_2']) != 10) { 
         $this->pcError = 'CODIGO DE ALUMNO NO DEFINIDO'; 
         return false; 
      } elseif ($this->paData['CCODALU_1'] == $this->paData['CCODALU_2']) { 
         $this->pcError = 'NO SE PUEDE REPETIR EL CODIGOS'; 
         return false; 
      } elseif (empty(trim($this->paData['CUNIACA'])) && strlen($this->paData['CUNIACA'] != 2)) { 
         $this->pcError = 'UNIDAD ACADEMICA INCORRECTA'; 
         return false; 
      } 
      return true; 
   } 
    
   protected function mxBuscarAlumnoCodigo($p_oSql) { 
      $lcCodAlu = $this->paData['CCODALU_2']; 
      $lcUniAca = $this->paData['CUNIACA']; 
      // Especialidad 
      $lcSql ="SELECT REPLACE(cNombre, '/', ' ') FROM V_A01MALU WHERE cCodAlu = '$lcCodAlu' AND cUniAca = '$lcUniAca'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      if ($p_oSql->pnNumRow == 0) { 
         $this->pcError = 'NO HAY ALUMNO REGISTRADO CON ESE CODIGO'; 
         return false; 
      } 
      $this->paDatos = ['CNOMBRE' => $laFila[0]]; 
      return true; 
   } 
 
   // ----------------------------------------------------------- 
   // 2018-07-19 FLC GRABAR PLAN DE TESIS  Creacion FPG1520 
   // ----------------------------------------------------------- 
   public function omGrabarPlanTesisv2() { 
      $llOk = $this->mxValGrabarPlanTesisV2(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxGrabarPlanTesisV2($loSql); 
      if (!$llOk) { 
         $loSql->rollback(); 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValGrabarPlanTesisV2() { 
      $this->paData['MTITULO'] = strtoupper($this->paData['MTITULO']); 
      //$this->paData['MTITULO'] = str_replace($this->paData['MTITULO'], '  ', ' ');? TITULO EN BLANCO 
      if (!isset($this->paFile) || $this->paFile['size'] > 56214400 || ($this->paFile['type'] != 'application/pdf')) { 
         $this->pcError = "ARCHIVO MUY PESADO O NO VALIDO"; 
         return false; 
      //} elseif (!isset($this->paData['MTITULO']) || empty($this->paData['MTITULO']) || !preg_match("(^[a-zA-Z0-9áéíóúñ�?É�?ÓÚÑ \n\r\/\t¿?!¡(),.:;_-]+$)", $this->paData['MTITULO'])) { 
      } elseif (!isset($this->paData['MTITULO']) || empty($this->paData['MTITULO']) || preg_match("([\'\"\&])", $this->paData['MTITULO'])) { 
         $this->pcError = 'TITULO DE LA TESIS NO DEFINIDO, INVALIDO O CONTIENE CARACTERES ESPECIALES'; 
         return false; 
      } elseif (!isset($this->paData['CEMAIL']) || empty($this->paData['CEMAIL']) || !filter_var($this->paData['CEMAIL'], FILTER_VALIDATE_EMAIL)) {   // OJOFPM REGEX 
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO'; 
         return false; 
      } elseif (!strrpos($this->paData['CEMAIL'], '@ucsm.edu.pe')) {   // OJOFPM REGEX 
         $this->pcError = 'EMAIL NO ES INSTITUCIONAL'; 
         return false; 
      } elseif (!isset($this->paData['CNROCEL']) || empty($this->paData['CNROCEL']) || !ctype_digit($this->paData['CNROCEL']) || strlen($this->paData['CNROCEL']) > 12) { 
         $this->pcError = 'NUMERO DE CELULAR NO DEFINIDO O INVALIDO'; 
         return false; 
      } elseif (!isset($this->paData['CCODALU']) || strlen($this->paData['CCODALU']) != 10) { 
         $this->pcError = 'CODIGO ALUMNO NO DEFINIDO O INVALIDO'; 
         return false; 
      } elseif ($this->paData['CCODCOM'][0] != ''){ 
         if (!is_numeric($this->paData['CCODCOM'][0])) { 
            $this->pcError = 'EL CODIGO DEL PRIMER COMPAÑERO ES INVALIDO'; 
            return false; 
         }          
      } elseif ($this->paData['CCODCOM'][1] != ''){ 
         if (!is_numeric($this->paData['CCODCOM'][1])) { 
            $this->pcError = 'EL CODIGO DEL SEGUNDO COMPAÑERO ES INVALIDO'; 
            return false; 
         }          
      } elseif ($this->paData['CCODCOM'][2] != ''){ 
         if (!is_numeric($this->paData['CCODCOM'][2])) { 
            $this->pcError = 'EL CODIGO DEL TERCER COMPAÑERO ES INVALIDO'; 
            return false; 
         }          
      } /*elseif (isset($this->paData['CTIPO'])){ 
         $this->pcError = 'EL DATO TIPO INVALIDO'; 
         return false;   
      } */elseif (isset($this->paData['CPREFIJ'])){ 
         if (strlen($this->paData['CPREFIJ']) != 1){ 
            $this->pcError = 'LA ESPECIALIDAD INVALIDA'; 
            return false;  
         } 
      } 
      $this->paData['MTITULO'] = strtoupper($this->paData['MTITULO']); 
      return true; 
   } 
 
   protected function mxGrabarPlanTesisV2($p_oSql) { 
      // validamos si los compañeros no tienen una tesis activa 
      foreach ($this->paData['CCODCOM'] as $laTmp) { 
         if ($laTmp != '') { 
            $lcSql = "SELECT cIdTesi FROM T01DALU WHERE cCodAlu = '{$laTmp}' AND cEstado != 'X' "; 
            $R1 = $p_oSql->omExec($lcSql); 
            $RS = $p_oSql->fetch($R1); 
            if ($RS[0] != '') { 
               $this->pcError = 'EL CODIGO '.$laTmp.' YA TIENE UNA TESIS ACTIVA'; 
               return false; 
            } 
         } 
      } 
      // saco el ultimo cIdTesi 
      $lcSql = "SELECT MAX(cIdTesi) FROM T01MTES"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $RS = $p_oSql->fetch($R1); 
      $lcIdTesi = '000000'.strval(intval($RS[0])+1); 
      $lcIdTesi = substr($lcIdTesi, strlen($lcIdTesi) - 6); 
      // obtengo la unidad academica 
      $lcSql = "SELECT A.cUniAca, B.cDesTit FROM V_A01MALU A INNER JOIN S01TUAC B ON B.cUniaca = A.cUniaca WHERE A.cCodAlu = '{$this->paData['CCODALU']}'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $RS = $p_oSql->fetch($R1); 
      $lcUniAca = $RS[0]; 
      $lcDesGra = $RS[1];
      //Actualizo los datos del alumno 
      $lcSql = "UPDATE S01MPER SET cEmail = '{$this->paData['CEMAIL']}', cNroCel = '{$this->paData['CNROCEL']}', cUsuCod = '9999', tModifi = NOW() WHERE cNroDni = '{$this->paData['CNRODNI']}'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = 'ERROR AL ACTULIZAR DATOS PERSONALES'; 
         return false; 
      } 
      // registro los datos de la tesis 
      if (!isset($this->paData['CPREFIJ'])){ 
         $this->paData['CPREFIJ'] = '*'; 
      } 
      if (in_array($lcUniAca, ['4A','4E']) AND $this->paData['CPREFIJ'] == 'A'){
         $lcDesTit = 'INGENIERO MECÁNICO';
      } elseif (in_array($lcUniAca, ['4A','4E']) AND $this->paData['CPREFIJ'] == 'C'){
         $lcDesTit = 'INGENIERO MECÁNICO ELECTRICISTA';
      } elseif (in_array($lcUniAca, ['4A','4E']) AND $this->paData['CPREFIJ'] == 'D'){
         $lcDesTit = 'INGENIERO MECATRÓNICO';
      } else {
         $lcDesTit = $lcDesGra;
      }
      $paDatAca = array('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9', 'M7');
      if (in_array($lcUniAca, $paDatAca)) {
         $lcSql = "INSERT INTO T01MTES(cIdTesi, cEstado, cGraTit, cTipo, cUniAca, cPrefij, mTitulo, dEntreg, cEstPro, cEstTes, cUsuCod, tModifi) 
                  VALUES ('{$lcIdTesi}', 'A', '{$lcDesTit}', '{$this->paData['CTIPO']}', '{$lcUniAca}', '{$this->paData['CPREFIJ']}', '{$this->paData['MTITULO']}', NOW(), 'G', 'G',  
                          'U666', NOW())"; 
      } else {
         $lcSql = "INSERT INTO T01MTES(cIdTesi, cEstado, cGraTit, cTipo, cUniAca, cPrefij, mTitulo, dEntreg, cEstPro, cEstTes, cUsuCod, tModifi) 
                  VALUES ('{$lcIdTesi}', 'A', '{$lcDesTit}', '{$this->paData['CTIPO']}', '{$lcUniAca}', '{$this->paData['CPREFIJ']}', '{$this->paData['MTITULO']}', NOW(), 'A', 'A',  
                          'U666', NOW())"; 
      }
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = 'ERROR AL REGISTRAR LA TESIS'; 
         return false; 
      } 
      // registro de alumnos 
      $lcSql = "INSERT INTO T01DALU(cIdTesi, cCodAlu, cNivel, cEstado, cUsuCod, tModifi) 
                  VALUES ('{$lcIdTesi}', '{$this->paData['CCODALU']}', 'P', 'A', 'U666',NOW())"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = 'ERROR AL REGISTRAR DATOS DEL ALUMNO'; 
         return false; 
      } 
      foreach ($this->paData['CCODCOM'] as $laTmp) { 
         if ($laTmp != '') { 
            $lcSql = "INSERT INTO T01DALU(cIdTesi, cCodAlu, cNivel, cEstado, cUsuCod, tModifi) 
                        VALUES ('{$lcIdTesi}', '{$laTmp}', 'S', 'A', 'U666',NOW())"; 
            $llOk = $p_oSql->omExec($lcSql); 
            if (!$llOk) { 
               $this->pcError = 'ERROR AL REGISTRAR DATOS DEL ALUMNO'; 
               return false; 
            } 
         } 
      } 
      // Actualizo T01DDEU - cIdTesi 
      $lcSql = "UPDATE T01DDEU SET cIdTesi = '{$lcIdTesi}', cEstado = 'B' WHERE cEstado = 'A' AND cCodAlu = '{$this->paData['CCODALU']}' AND cIdTesi = '000000'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = 'ERROR AL ACTUALIZAR ESTADO - COMUNIQUESE CON LA OFICINA ERP'; 
         return false; 
      } 
      //NOMBRE DEL ARCHIVO 
      $lcCodPdf = $lcIdTesi; 
      if ($this->paFile['error'] == 0) { 
         $llOk = fxSubirPDF($this->paFile, 'Tesis', $lcCodPdf); 
         if (!$llOk) { 
            $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO'; 
            return false; 
         } 
      } 
      return true; 
   }   

   //-----------------------------------------------   
   // GRABAR EL PDF DE LAS OBSERVACIONES   
   // 2020-06-22 FLC   
   //-----------------------------------------------   
   
   public function omObservacionesTesis() {   
      $llOk = $this->mxValObservacionesTesis($loSql);   
      if (!$llOk) {   
		   $loSql->omDisconnect();   
         return false;   
      }   
      $loSql = new CSql();   
      $llOk = $loSql->omConnect();   
      if (!$llOk) {   
         $this->pcError = $loSql->pcError;   
         return false;   
      }   
      $llOk = $this->mxObservacionesTesis($loSql);   
      if (!$llOk) {   
         $loSql->omDisconnect();   
         return false;   
      }   
      $loSql->omDisconnect();   
      return $llOk;   
   }   
   
   protected function mxValObservacionesTesis($p_oSql) {   
      if (strlen($this->paData['CIDTESI']) != 6 AND intval($this->paData['CIDTESI'])) {   
         $this->pcError = "EL IDENTIFICADOR DE LA TESIS NO ES VALIDO";   
         return false;   
      }   
      return true;   
   }   
   
   protected function mxObservacionesTesis($p_oSql) {   
      $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, A.cNewReg FROM T01MTES A 
                  INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       $R1 = $p_oSql->fetch($R1); 
       if (count($R1) == 0){ 
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE'; 
          return false; 
       } 
       $lcDesEst =  ($R1[6] == '')? '[ERR]' : $R1[6]; 
       $this->paDatos = ['CIDTESI'=>$R1[0], 'CESTADO'=>$R1[1], 'MTITULO'=>$R1[2], 'CUNIACA'=>$R1[3].' - '.$R1[4], 
                         'CESTTES'=>$R1[5], 'CDESEST'=> $lcDesEst, 'CNEWREG'=>$R1[7]]; 
      $lcSql = "SELECT A.cCodAlu, B.cNombre FROM T01DALU A INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu    
                     WHERE CIDTESI = '{$this->paData['CIDTESI']}'";   
      $R1 = $p_oSql->omExec($lcSql);   
      while ($laFila = $p_oSql->fetch($R1)){   
            $this->paAlumno[] = ['CCODALU' => $laFila[0], 'CNOMALU' => str_replace('/', ' ', $laFila[1])];   
      } 
      // Observaciones  
      $lcSql = "SELECT TRIM(B.cNombre), TRIM(A.mObserv), TO_CHAR(A.tModifi,'YYYY-MM-DD') 
                  FROM T01DLOG A 
                  LEFT OUTER JOIN V_A01MDOC B ON B.CCODDOC = A.CUSUCOD 
                  WHERE A.cEstTes IN ('B','D','F') AND A.cEstPro = 'N' AND A.cIdTesi = '{$this->paData['CIDTESI']}' ORDER BY tModifi DESC ";   
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)){   
         $this->paObserv[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'MOBSERV' => $laFila[1], 'FECHA' => $laFila[2]];   
      }  
      return true;   
   }   

   //-----------------------------------------------   
   // GRABAR EL PDF DE LAS OBSERVACIONES DOCENTES   
   // 2020-06-22 FLC   
   //-----------------------------------------------   
   
   public function omGrabarObservacionesDocentesPDF() {   
      $llOk = $this->mxValGrabarObservacionesDocentesPDF();   
      if (!$llOk) {   
         return false;   
      }   
      $loSql = new CSql();   
      $llOk = $loSql->omConnect();   
      if (!$llOk) {   
         $this->pcError = $loSql->pcError;   
         return false;   
      }   
      $llOk = $this->mxGrabarObservacionesDocentesPDF($loSql);   
      if (!$llOk) {   
         $loSql->rollback();   
      }   
      $loSql->omDisconnect();   
      return $llOk;   
   }   
   
   protected function mxValGrabarObservacionesDocentesPDF() {   
      /*if (!isset($this->paFile) || $this->paFile['size'] > 56214400 || ($this->paFile['type'] != 'application/pdf')) {   
         $this->pcError = "ARCHIVO MUY PESADO O NO VALIDO";   
         return false;   
      } else*/ 
      if (strlen($this->paData['CIDTESI']) != 6 AND intval($this->paData['CIDTESI'])) {   
         $this->pcError = "EL IDENTIFICADOR DE LA TESIS NO ES VALIDO";   
         return false;   
      } elseif (strlen($this->paData['CUSUCOD']) != 4 ) {   
         $this->pcError = "CODIGO DE DOCENTE NO VALIDO";   
         return false;   
      }  elseif (strlen($this->paData['MOBSERV']) == 0 ) {   
         $this->pcError = "NO HAY OBSERVACIONES";   
         return false;   
      }  
      return true;   
   }   
   
   protected function mxGrabarObservacionesDocentesPDF($p_oSql) {   
      //$this->paData['MOBSERV'] = strtoupper($this->paData['MOBSERV']);   
      $lcSql = "SELECT cEstTes FROM T01MTES WHERE CIDTESI = '{$this->paData['CIDTESI']}'";   
      $R1 = $p_oSql->omExec($lcSql);   
      $RS = $p_oSql->fetch($R1);  
      if (in_array($RS[0], ['B','D','F'])) {   
         $lcSql = "INSERT INTO T01DLOG(cIdTesi,cEstTes,mObserv,cEstado,cEstPro,cUsuCod,tModifi)values 
                     ('{$this->paData['CIDTESI']}','{$RS[0]}','{$this->paData['MOBSERV']}','A','N','{$this->paData['CUSUCOD']}',NOW())"; 
         $llOk = $p_oSql->omExec($lcSql);  
         if (!$llOk) { 
            $this->pcError = "NO SE REGISTRO LAS OBSERVACIONES";   
            return false; 
         }   
      } else{   
         $this->pcError = "NO PUEDE HACER OBSERVACIONES";   
         return false;   
      } 
      $lcCatego = ''; 
      if ($RS[0] == 'B'){ 
         $lcCatego = 'A'; 
      } else if ($RS[0] == 'D'){ 
         $lcCatego = 'B'; 
      } else if ($RS[0] == 'F'){ 
         $lcCatego = 'C'; 
      } 
      $lcSql = "UPDATE T01DDOC SET cResult = 'O', cUsuCod = '{$this->paData['CUSUCOD']}' WHERE cCodDoc = '{$this->paData['CUSUCOD']}' AND cIdTesi = '{$this->paData['CIDTESI']}'"; 
      $llOk = $p_oSql->omExec($lcSql);  
      if (!$llOk) { 
         $this->pcError = "NO SE REGISTRO LAS OBSERVACIONES";    
         return false;  
      } 
      /*$lcSql = "SELECT cEstTes FROM T01MTES WHERE CIDTESI = '{$this->paData['CIDTESI']}'";   
      $R1 = $p_oSql->omExec($lcSql);   
      $laFila = $p_oSql->fetch($R1);   
      $lcEstTes = $laFila[0];   
      if (in_array($lcEstTes, ['B','D','F'])) {   
         $lcCodPdf = $this->paData['CIDTESI'].'_O_'.$this->paData['CUSUCOD'];   
         if ($this->paFile['error'] == 0) {   
            $llOk = fxSubirPDF($this->paFile, 'Tesis', $lcCodPdf);   
            if (!$llOk) {   
               $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO';   
               return false;   
            }   
         }   
      } else {   
         $this->pcError = "NO PUEDE HACER OBSERVACIONES EN ESTE MOMENTO";   
         return false;   
      }  */ 
      return true;   
   }   

   # -------------------------------------------------- 
   # Consulta tesis por apellidos y nombres de alumnos 
   # Bandeja de Decano - Director 
   # 2020-06-11 FPM Creacion 
   # -------------------------------------------------- 
 
   public function omFpg1420Consultar() {  
      $llOk = $this->mxValParam();  
      if (!$llOk) {  
         return false;  
      }  
      $loSql = new CSql();  
      $llOk  = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk  = $this->mxValUsuario($loSql);  
      if (!$llOk) {  
         $loSql->omDisconnect();  
         return false;  
      }  
      $llOk = $this->mxFpg1420Consultar($loSql);  
      $loSql->omDisconnect();  
      if (!$llOk) {  
         return false;  
      }  
      return $llOk;  
   }  
  
   protected function mxValParam(){ 
       if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4){ 
          $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'; 
          return false; 
       } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3){ 
          $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVALIDO'; 
          return false; 
       } 
       return true; 
   } 
 
   protected function mxValUsuario($p_oSql){ 
      if ($this->paData['CCENCOS'] == 'UNI'){ 
         # Si es super-usuario 
         $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
         $R1 = $p_oSql->omExec($lcSql); 
         $RS = $p_oSql->fetch($R1); 
         if (strlen($RS[0]) == ''){ 
            return; 
         } elseif ($RS[0] == 'A'){ 
            $this->laUniAca[] = '*'; 
            return true; 
         } 
      } 
      if ($this->paData['CCENCOS'] == '0CP'){ //BIBLIOTECA
         # Si es super-usuario 
         $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
         $R1 = $p_oSql->omExec($lcSql); 
         $RS = $p_oSql->fetch($R1); 
         if (strlen($RS[0]) == ''){ 
            return; 
         } elseif ($RS[0] == 'A'){ 
            $this->laUniAca[] = '*'; 
            return true; 
         } 
      } 
      if ($this->paData['CCENCOS'] == '08M'){
          # Director Postgrado 
          $lcSql = "SELECT cUniAca FROM S01TUAC WHERE cNivel in ('03','04') OR cUniaca = '99'"; 
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laFila = $p_oSql->fetch($R1)) {  
            $this->laUniAca[] = $laFila[0]; 
         } 
      } else {
         # Usuario normal 
         /*$lcSql = "SELECT DISTINCT B.cUniAca FROM S01PCCO A 
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                     WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cUniAca != '00'"; 
          * 
          */
         $lcSql = "SELECT DISTINCT B.cUniAca FROM V_S01PCCO A 
                   INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                   WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cUniAca != '00'";
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laFila = $p_oSql->fetch($R1)) {  
            $this->laUniAca[] = $laFila[0]; 
         } 
         if (count($this->laUniAca) == 0){ 
             $this->pcError = 'USUARIO NO TIENE UNIDADES ACADEMICAS ASIGNADAS'; 
             return false; 
         } 
      }
      return true; 
   } 
  
   protected function mxFpg1420Consultar($p_oSql) {  
      $laData  = []; 
      $laDatos = []; 
      // pendientes de asignacion 
      $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo, F.cDescri FROM T01DALU A 
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '143' AND F.cCodigo = B.cTipo 
                  WHERE B.cEstTes IN ('A','C','E','H') AND B.cEstado != 'X' 
                  ORDER BY D.cNombre"; 
       //echo $lcSql; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) {  
           # Valida si usuario accede a unidad academica de tesis 
           if ($this->laUniAca[0] == '*'){} 
           else if (!in_array($laFila[4], $this->laUniAca)){ 
              continue; 
           } 
           $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
           $laData[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3], 
                        'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst,'CTIPO'   => $laFila[8],
                        'CMODALI' => $laFila[9], 'CFLAG' => $laFila[0].$laFila[4].$laFila[8]]; //(N)
       } 
       $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo, F.cDescri FROM T01DALU A 
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '143' AND F.cCodigo = B.cTipo 
                  WHERE B.cEstTes NOT IN ('A','C','E','H','K','N') AND B.cEstado != 'X' 
                  ORDER BY D.cNombre"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) {  
           # Valida si usuario accede a unidad academica de tesis 
           if ($this->laUniAca[0] == '*'){} 
           else if (!in_array($laFila[4], $this->laUniAca)){ 
              continue; 
           } 
           $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
           $laDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3], 
                         'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst,'CTIPO'   => $laFila[8],
                         'CMODALI' => $laFila[9]]; 
       } 
       $this->paData  = $laData; 
       $this->paDatos = $laDatos; 
       return true;
   }
 
   # -------------------------------------------------- 
   # VISUALIZAR DATOS DE LA TESIS EN BUSQUEDA 
   # 2020-06-11 FPM Creacion 
   # -------------------------------------------------- 
   public function omFpg1420Ver(){ 
      $llOk = $this->mxValParamFpg1420Ver(); 
       if (!$llOk){ 
          return false; 
       } 
       $loSql = new CSql();  
       $llOk = $loSql->omConnect(); 
       if (!$llOk){ 
          $this->pcError = $loSql->pcError; 
          return false; 
       } 
       $llOk = $this->mxFpg1420Ver($loSql); 
       $loSql->omDisconnect(); 
       return $llOk; 
   } 
 
   protected function  mxValParamFpg1420Ver(){ 
       $llOk = $this->mxValParam(); 
       if (!$llOk){ 
          return false; 
       } else if (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6){ 
          $this->pcError = 'ID DE TESIS NO DEFINIDO O INVALIDO'; 
          return false; 
       } 
       return true; 
   } 
 
   protected function mxFpg1420Ver($p_oSql){ 
      $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, B.cNivel, A.cNewReg, TO_CHAR(A.dEntreg, 'YYYY-mm-dd HH24:MI'), A.cTipo FROM T01MTES A 
                INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes 
                WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cEstado != 'X'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      $RS = $p_oSql->fetch($R1); 
      if ( $RS[0] == ''){ 
         $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE'; 
         return false; 
      } 
      $lcDesEst = ($RS[6] == '')? '[ERR-'.$RS[5].']' : $RS[6]; 
      $laData = ['CIDTESI'=> $RS[0],   'CESTADO'=> $RS[1],'MTITULO'=> $RS[2],'CUNIACA'=> $RS[3],'CNOMUNI'=> $RS[4],'CESTTES'=> $RS[5], 
                 'CDESEST'=> $lcDesEst,'ACODALU'=> null,  'ACODDOC'=> null,  'ACODASE'=> null,  'ACODDIC'=> null,  'ACODJUR'=> null, 
                 'CNIVEL' => $RS[7],   'CNEWREG'=> $RS[8],'DENTREG'=> $RS[9], 'CTIPO'=> $RS[10], 'AEXPJUR'=> '']; 
      # Alumnos de tesis                
      $laCodAlu = $this->mxAlumnos($p_oSql); 
      if (count($laCodAlu) == 0){ 
         $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE ALUMNOS DEFINIDOS'; 
         return false; 
      } 
      $laData['ACODALU'] = $laCodAlu; 
      if ($laData['CUNIACA'] == '62' and $laData['CTIPO'] == 'T6') {
         # Tesis con expediente juridico
         $llOk = $this->mxExpedienteJuridico($p_oSql, $laData);
         return $llOk;
      } else {
         $llOk = $this->mxExpedienteEstandar($p_oSql, $laData);
      }
      return $llOk;
   }

   protected function mxExpedienteEstandar($p_oSql, $p_aData) {
       # Dictaminadores de PDT 
       if ($p_aData['CESTTES'] >= 'B' && $p_aData['CESTTES'] <= 'J'){ 
          $laCodDoc = $this->mxDictaminadoresPDT($p_oSql); 
          if (count($laCodDoc) != 2 and $p_aData['CNIVEL'] != '04' AND $p_aData['CNEWREG'] == '1'){ 
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE DOS  DICTAMINADORES DEFINIDOS'; 
             return false; 
          } elseif (!in_array(count($laCodDoc),[2,3]) and $p_aData['CNIVEL'] == '04' AND $p_aData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES DICTAMINADORES DEFINIDOS'; 
             return false; 
          } 
          $p_aData['ACODDOC'] = $laCodDoc; 
          //print_r($laCodDoc);
       } 
       # Asesor de tesis 
       if ($p_aData['CESTTES'] >= 'D' and $p_aData['CESTTES'] <= 'J'){ 
          $laCodAse = $this->mxAsesorTesis($p_oSql); 
          if (count($laCodAse) != 1 AND $p_aData['CNEWREG'] == '1'){ 
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE UN ASESOR DEFINIDO'; 
             return false; 
          } 
          $p_aData['ACODASE'] = $laCodAse; 
          //print_r($laCodAse);
       } 
       # Dictaminadores de borrador de tesis 
       if ($p_aData['CESTTES'] >= 'F' and $p_aData['CESTTES'] <= 'J'){  
          $laCodDic = $this->mxDictaminadoresBDT($p_oSql); 
          if (count($laCodDic) != 3 and $p_aData['CNIVEL'] != '04' AND $p_aData['CNEWREG'] == '1'){ 
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES DICTAMINADORES BORRADOR DEFINIDOS'; 
             return false; 
          } else if (count($laCodDic) != 5 and $p_aData['CNIVEL'] == '04' AND $p_aData['CNEWREG'] == '1'){ 
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE CINCO DICTAMINADORES BORRADOR DEFINIDOS'; 
             return false; 
          } 
          $p_aData['ACODDIC'] = $laCodDic; 
       } 
       # Dictaminadores de Jurado de tesis 
       if ($p_aData['CESTTES'] >= 'I' and $p_aData['CESTTES'] <= 'J'){  
          $laCodJur = $this->mxJuradoTesis($p_oSql); 
          if (count($laCodJur) != 3 and $p_aData['CNIVEL'] != '04'){ 
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES JURADOS DEFINIDOS'; 
             return false; 
          } else if (count($laCodJur) != 5 and $p_aData['CNIVEL'] == '04'){ 
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE CINCO JURADOS DEFINIDOS'; 
             return false; 
          } 
          $p_aData['ACODJUR'] = $laCodJur; 
       } 
       // Observaciones  
       $lcSql = "SELECT TRIM(B.cNombre), TRIM(A.mObserv), TO_CHAR(A.tModifi,'YYYY-MM-DD') 
                  FROM T01DLOG A 
                  LEFT OUTER JOIN V_S01TUSU_1 B ON B.CCODUSU = A.CUSUCOD 
                  WHERE A.cEstTes IN ('B','D','F','J') AND A.cEstPro = 'N' AND A.cEstado = 'A' AND A.cIdTesi = '{$this->paData['CIDTESI']}' ORDER BY tModifi DESC "; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)){   
         $this->paObserv[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'MOBSERV' => $laFila[1], 'FECHA' => $laFila[2]];   
       } 
      #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS
      if ($p_aData['CNIVEL'] == '03' || $p_aData['CNIVEL'] == '04') {
         $lcSql = "SELECT C.cNroDni, C.cNombre, COUNT(*) FROM T01MTES A 
                     INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                     WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'A'
                     GROUP BY C.cNroDni, C.cNombre
                     ORDER BY C.cNombre";
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laFila = $p_oSql->fetch($R1)){   
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'B' AND C.cNroDni = '$laFila[0]'";
            $R2 = $p_oSql->omExec($lcSql); 
            $laFila1 = $p_oSql->fetch($R2);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'C' AND C.cNroDni = '$laFila[0]'";
            $R3 = $p_oSql->omExec($lcSql); 
            $laFila2 = $p_oSql->fetch($R3);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACI�N DE TESIS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'D' AND C.cNroDni = '$laFila[0]'";
            $R4 = $p_oSql->omExec($lcSql); 
            $laFila3 = $p_oSql->fetch($R4);
            $paCanTes[] = ['CNRODNI' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CCANPDT' => $laFila[2], 
                           'CCANASE' => $laFila1[0],'CCANBDT' => $laFila2[0],'CCANJDT' => $laFila3[0]];   
         }
      } else {
         $lcSql = "SELECT C.cNroDni, C.cNombre, COUNT(*) FROM T01MTES A 
                     INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                     WHERE B.cEstado <> 'X' AND A.cUniaca = '{$p_aData['CUNIACA']}' AND B.cCatego = 'A'
                     GROUP BY C.cNroDni, C.cNombre
                     ORDER BY C.cNombre";
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laFila = $p_oSql->fetch($R1)){   
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$p_aData['CUNIACA']}' AND B.cCatego = 'B' AND C.cNroDni = '$laFila[0]'";
            $R2 = $p_oSql->omExec($lcSql); 
            $laFila1 = $p_oSql->fetch($R2);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$p_aData['CUNIACA']}' AND B.cCatego = 'C' AND C.cNroDni = '$laFila[0]'";
            $R3 = $p_oSql->omExec($lcSql); 
            $laFila2 = $p_oSql->fetch($R3);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACI�N DE TESIS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$p_aData['CUNIACA']}' AND B.cCatego = 'D' AND C.cNroDni = '$laFila[0]'";
            $R4 = $p_oSql->omExec($lcSql); 
            $laFila3 = $p_oSql->fetch($R4);
            $paCanTes[] = ['CNRODNI' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CCANPDT' => $laFila[2], 
                           'CCANASE' => $laFila1[0],'CCANBDT' => $laFila2[0],'CCANJDT' => $laFila3[0]];   
         }
      }
      $p_aData['ACANTES'] = $paCanTes;
      $this->paData = $p_aData; 
      return true; 
   }  
 
   protected function mxExpedienteJuridico($p_oSql, $p_aData) {
      # Dictaminadores  
      if ($p_aData['CESTTES'] == 'A') {
         $laExpJur = [];
         $lcSql = "SELECT nSerial, cNroExp, mDatos FROM T01DEXP WHERE cIdTesi = '{$this->paData['CIDTESI']}' ORDER BY nSerial";
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laTmp = $p_oSql->fetch($R1)) {  
            $laTmp1 = json_decode($laTmp[2], true);
            $laExpJur[] = array_merge(['NSERIAL'=> $laTmp[0], 'CNROEXP'=> $laTmp[1]], $laTmp1);
         }
         if (count($laExpJur) != 2) {
            $this->pcError = 'NO HAY DOS EXPEDIENTES JURIDICOS';
            return false;
         }
         $this->paData['AEXPJUR'] = $laExpJur;
         return true;
      }   
      return true; 
   }  

   # Jurado de tesis 
   protected function mxJuradoTesis($p_oSql){ 
       $laArray = null; 
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), C.cDescri, D.cDescri 
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo 
                  INNER JOIN S01TTAB D ON D.cCodigo = A.cResult AND D.cCodTab = '253'
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'D' AND A.cEstado = 'A' ORDER BY A.cCodDoc"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) {  
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3]; 
           if ($laFila[5] == 'APROBADO') {
            $lcDescri = 'FIRMADO';
           } else {
            $lcDescri = $laFila[5];
           }            
           $laArray[] = ['CCODDOC'=> $laFila[0], 'CNOMDOC'=> str_replace('/', ' ', $laFila[1]), 'TDECRET'=> $laFila[2], 'TDICTAM'=> $ltDictam, 'CCARGO'=> $laFila[4], 'CDESCRI'=> $lcDescri]; 
       } 
       return $laArray; 
   } 
 
   # Dictaminadores borrador tesis 
   protected function mxDictaminadoresBDT($p_oSql){ 
       $laCodDic = null; 
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI'), C.cDescri 
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'C' AND A.cEstado = 'A' ORDER BY A.cCodDoc"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) { 
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3]; 
           $laCodDic[] = ['CCODDOC' => $laFila[0], 'CNOMDOC' => str_replace('/', ' ', $laFila[1]), 'TDECRET' => $laFila[2], 'TDICTAM' => $ltDictam, 'DFECHOR' => $laFila[4], 'CDESCRI'=> $laFila[5]]; 
       } 
       return $laCodDic; 
   } 
 
   # Dictaminadores proyecto de tesis 
   protected function mxDictaminadoresPDT($p_oSql){ 
       $laCodDoc = null; 
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI'), C.cDescri 
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'A' AND A.cEstado = 'A' ORDER BY A.cCodDoc"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) { 
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3]; 
           $laCodDoc[] = ['CCODDOC' => $laFila[0], 'CNOMDOC' => str_replace('/', ' ', $laFila[1]), 'TDECRET' => $laFila[2], 'TDICTAM' => $ltDictam, 'DFECHOR' => $laFila[4], 'CDESCRI'=> $laFila[5]]; 
       } 
       return $laCodDoc; 
   } 
 
   # Estudiantes integrantes de tesis 
   protected function mxAlumnos($p_oSql){ 
      $laCodAlu = null; 
      $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroCel, B.cEmailp, B.cEmail, B.cNomUni, B.cNroDni FROM T01DALU A 
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.CESTADO = 'A'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)) { 
         $laCodAlu[] = ['CCODALU'=> $laFila[0], 'CNOMALU'=> str_replace('/', ' ', $laFila[1]), 'CNROCEL'=> $laFila[2],
                        'CEMAILP'=> $laFila[3], 'CEMAIL' => $laFila[4], 'CNOMUNI'=> $laFila[5],'CNRODNI'=> $laFila[6]]; 
      } 
      return $laCodAlu; 
   }
   
   # Tesis anuladas anteriormente 
   protected function mxTesisAnuladas($p_oSql){ 
      $laTesAnu = null; 
      $lcSql = "SELECT cCodAlu FROM T01DALU WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)) { 
         $lcSql = "SELECT cIdTesi, cCodAlu, TO_CHAR(tModifi, 'YYYY-mm-dd HH24:MI') FROM T01DALU WHERE cCodAlu = '$laFila[0]' AND cEstado = 'X'
                     ORDER BY tModifi"; 
         $R2 = $p_oSql->omExec($lcSql); 
         while ($laFila1 = $p_oSql->fetch($R2)) { 
            $laTesAnu[] = ['CIDTESI'=> $laFila1[0], 'CCODALU' => $laFila1[1], 'TMODIFI' => $laFila1[2]]; 
         }
      } 
      return $laTesAnu; 
    } 
 
   # Asesor de tesis 
   protected function mxAsesorTesis($p_oSql){ 
       $laCodAse = null; 
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI'), C.cDescri
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'B' AND A.cEstado = 'A' ORDER BY A.cCodDoc"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) { 
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3]; 
           $laCodAse[] = ['CCODDOC'=> $laFila[0], 'CNOMDOC'=> str_replace('/', ' ', $laFila[1]), 'TDECRET'=> $laFila[2], 'TDICTAM'=> $ltDictam, 'DFECHOR'=> $laFila[4], 'CDESCRI'=> $laFila[5]]; 
       } 
       return $laCodAse; 
    } 
 

   # -------------------------------------------------- 
   # Grabar espediente juridico 
   # 2022-06-22 GAR Creacion
   # -------------------------------------------------- 
    public function omFpg1420GrabarExpedienteJuridico(){
      $llok = $this->mxValParamGrabarExpedienteJuridico();
      if (!$llOk){ 
         return false; 
      } 
      $loSql = new CSql();  
      $llOk = $loSql->omConnect(); 
      if (!$llOk){ 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxGrabarExpedienteJuridico($loSql); 
      if (!$llOk) { 
         $loSql->rollback(); 
      } 
      $loSql->omDisconnect(); 
      return $llOk;
   }

   protected function mxValParamGrabarExpedienteJuridico(){
      if (!isset($this->paData['CCODDOC1'])) { 
         $this->pcError = "DICTAMINADOR 1 INVALIDO"; 
         return false;
      } elseif (!isset($this->paData['CCODDOC2'])){
         $this->pcError = "DICTAMINADOR 2 INVALIDO"; 
         return false;
      }
      return true;
   }

   protected function mxGrabarExpedienteJuridico($p_oSql){

   }

   # -------------------------------------------------- 
   # Grabar dictaminadores 
   # 2020-06-11 FPM Creacion 
   # -------------------------------------------------- 
   public function omFpg1420Grabar(){ 
       $llOk = $this->mxValParamFpg1420Grabar(); 
       if (!$llOk){ 
          return false; 
       } 
       $loSql = new CSql();  
       $llOk = $loSql->omConnect(); 
       if (!$llOk){ 
          $this->pcError = $loSql->pcError; 
          return false; 
       } 
       $llOk = $this->mxValUsuario($loSql); 
       if (!$llOk){ 
          $loSql->omDisconnect(); 
          return false; 
       } 
       $llOk = $this->mxFpg1420Grabar($loSql); 
       if (!$llOk){ 
          $loSql->rollback(); 
          $loSql->omDisconnect();  
          return false;
       } 
       $loSql->omDisconnect(); 
       return $llOk; 
   } 
 
   protected function mxValParamFpg1420Grabar(){ 
       $loDate = new CDate; 
       $llOk = $this->mxValParam(); 
       if (!$llOk){ 
          return false; 
       } else if (!isset($this->paData['CFLAG']) || count($this->paData['CFLAG']) != 1 || $this->paData['CFLAG'] != 'A'){ 
          $this->pcError = 'INDICADOR CFLAG PARA CANTIDAD DE DICTAMINADORES NO DEFINIDO O INVALIDO'; 
          return false; 
       } else if (!isset($this->paData['CESTTES']) || strlen($this->paData['CESTTES']) != 1){ 
          $this->pcError = 'ESTADO DE LA TESIS NO DEFINIDO O INVALIDO'; 
          return false; 
       } else if ($this->paData['CESTTES'] == 'H' and  !isset($this->paData['TDIASUS'])){ 
          $this->pcError = 'FECHA Y HORA DE SUSTENTACION NO DEFINIDA'; 
          return false; 
       } else if (!isset($this->paData['ACODDOC'])){ 
          $this->pcError = 'ARREGLO DE DOCENTES NO DEFINIDO'; 
          return false; 
       } 
       # Valida fecha y hora de sustentacion 
       if ($this->paData['CESTTES'] == 'H'){ 
          $ldToday = date ("Y-m-d"); 
          $ldFecha = substr($this->paData['TDIASUS'], 0, 10); 
          $lcHora  = substr($this->paData['TDIASUS'], 11, 5); 
          $loDate = new CDate();  
          if (!$loDate->mxvalDate($ldFecha)){ 
             $this->pcError = 'FECHA DE SUSTENTACION INVALIDA'; 
             return false; 
          } /*else if ($ldFecha <= $ldToday){ 
             $this->pcError = 'FECHA DE SUSTENTACION DEBE SER MAYOR QUE LA FECHA ACTUAL'; 
             return false; 
          } */else if (substr($lcHora, 0, 2) < '00' || substr($lcHora, 0, 2) > '23'){ 
            $this->pcError = 'HORA DE SUSTENTACION INVALIDA'; 
            return false; 
          } else if (substr($lcHora, 3, 2) < '00' || substr($lcHora, 3, 2) > '59'){ 
            $this->pcError = 'HORA/MINUTO DE SUSTENTACION INVALIDA'; 
            return false; 
         } 
       } 
       # Valida parametro docentes 
       $this->laCodDoc = $this->paData['ACODDOC']; 
       if ($this->paData['CFLAG'] == 'A'){ 
          if ($this->paData['CESTTES'] == 'A' and !in_array(count($this->laCodDoc), [1,2]) and $this->paData['CNIVEL'] != '04') { 
             $this->pcError = 'DICTAMINADORES DE PROYECTO DE TESIS DEBEN SER DOS DOCENTES'; 
             return false; 
          }else if ($this->paData['CESTTES'] == 'C' and count($this->laCodDoc) != 1) { 
             $this->pcError = 'ASESOR DEBE SER UN DOCENTE'; 
             return false; 
          }else if ($this->paData['CESTTES'] == 'E' and !in_array(count($this->laCodDoc), [2,3]) and $this->paData['CNIVEL'] != '04') { 
             $this->pcError = 'DICTAMINADORES DE BORRADOR DE TESIS DEBEN SER DOS O TRES DOCENTES'; 
             return false; 
          }else if ($this->paData['CESTTES'] == 'H' and count($this->laCodDoc) != 3 and $this->paData['CNIVEL'] != '04') { 
             $this->pcError = 'JURADOS DE TESIS DEBEN SER TRES DOCENTES'; 
             return false; 
          }else if ($this->paData['CESTTES'] == 'A' and count($this->laCodDoc) != 3 and $this->paData['CNIVEL'] == '04') { 
             $this->pcError = 'DICTAMINADORES DE BORRADOR DE TESIS DEBEN SER TRES DOCENTES'; 
             return false; 
          }else if ($this->paData['CESTTES'] == 'E' and count($this->laCodDoc) != 5 and $this->paData['CNIVEL'] == '04') { 
             $this->pcError = 'DICTAMINADORES DE BORRADOR DE TESIS DEBEN SER CINCO DOCENTES'; 
             return false; 
          }else if ($this->paData['CESTTES'] == 'H' and count($this->laCodDoc) != 5 and $this->paData['CNIVEL'] == '04') { 
             $this->pcError = 'JURADOS DE TESIS DEBEN SER TRES DOCENTES'; 
             return false; 
          } 
       } else { 
          $this->pcError = 'INDICADOR DE TITPO DE TESIS ERRADO'; 
          return false; 
       } 
       # Valida contenido de parametro docentes 
       $llOk = $this->mxValParamDocentes(); 
       return $llOk; 
   }
 
   # Valida los docentes recibidos como parametro 
   protected function mxValParamDocentes(){ 
       $laCodDoc = null; 
       $valCodDoc = 0; 
       for ($i=0; $i < count($this->laCodDoc); $i++) {  
           # Verifica que docentes no esten repetidos 
           for ($j=0; $j < count($this->laCodDoc); $j++) { 
               if (in_array($laCodDoc[$i]['CCODDOC'], $laCodDoc)) { 
                  $this->pcError = 'CODIGO DE DOCENTE ['.$this->laCodDoc[$i]['CCODDOC'].'] REPETIDO'; 
                  return false; 
               } 
            } 
           if ($this->laCodDoc[$i]['CCODDOC'] != '0000'){ 
               $laCodDoc[] = $this->laCodDoc[$i]['CCODDOC']; 
           } else if ($this->laCodDoc[$i]['CCODDOC'] == '0000'){ 
               $valCodDoc += 1; 
           } 
           # Verifica estructura de docentes 
           if (!isset($this->laCodDoc[$i]['CCODDOC']) or strlen($this->laCodDoc[$i]['CCODDOC']) != 4){ 
              $this->pcError = 'CODIGO DE DOCENTE NO DEFINIDO O INVALIDO'; 
              return false; 
           } 
       } 
       if ($valCodDoc >= 1 and $this->paData['CESTTES'] == 'A' and $this->paData['CNIVEL'] == '04'){ 
           $this->pcError = 'DEBE INGRESAR 3 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES' ; 
           return false; 
       } else if ( $valCodDoc >= 2 and $this->paData['CESTTES'] == 'A' and !in_array($this->paData['CNIVEL'], ['04','03']) AND  
                   $laData['CNEWREG'] == '0'){ 
           $this->pcError = 'DEBE INGRESAR 1 O 2 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES' ; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'A' and !in_array($this->paData['CNIVEL'], ['04','03']) AND  
                   $laData['CNEWREG'] == '1'){ 
           $this->pcError = 'DEBE INGRESAR 2 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES' ; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'A' and $this->paData['CNIVEL'] == '03'){ 
           $this->pcError = 'DEBE INGRESAR 2 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES'; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'C'){ 
           $this->pcError = 'DEBE INGRESAR UN CODIGO DE DOCENTE VALIDO PARA ASESOR'; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'E' and $this->paData['CNIVEL'] == '04'){ 
           $this->pcError = 'DEBE INGRESAR 5 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES BORRADOR'; 
           return false; 
       } else if ( $valCodDoc >= 2 and $this->paData['CESTTES'] == 'E' and !in_array($this->paData['CNIVEL'], ['04','03']) AND  
                   $laData['CNEWREG'] == '0'){ 
           $this->pcError = 'DEBE INGRESAR 2 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES'; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'E' and !in_array($this->paData['CNIVEL'], ['04','03']) AND  
                   $laData['CNEWREG'] == '1'){ 
           $this->pcError = 'DEBE INGRESAR 3 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES DE BORRADOR'; 
           return false; 
       } else if ( $valCodDoc >= 2 and $this->paData['CESTTES'] == 'E' and $this->paData['CNIVEL'] == '03'){ 
           $this->pcError = 'DEBE INGRESAR 3 CODIGOS DE DOCENTES VALIDOS PARA DICTAMINADORES DE BORRADOR'; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'H' and $this->paData['CNIVEL'] == '04'){ 
           $this->pcError = 'DEBE INGRESAR 5 CODIGOS DE DOCENTES VALIDOS PARA JURADOS'; 
           return false; 
       } else if ( $valCodDoc >= 1 and $this->paData['CESTTES'] == 'H' and $this->paData['CNIVEL'] != '04'){ 
           $this->pcError = 'DEBE INGRESAR 3 CODIGOS DE DOCENTES VALIDOS PARA JURADOS'; 
           return false; 
       } 
       return true; 
   } 
 
   protected function mxFpg1420Grabar($p_oSql){ 
       # Verifica estado de tesis 
       $lcSql = "SELECT cEstTes, cUniAca, cEstDec, cNewReg FROM T01MTES WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       $RS = $p_oSql->fetch($R1); 
       if (count($RS) == 0){ 
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE'; 
          return false; 
       } else if ($RS[0] != $this->paData['CESTTES']){ 
          $this->pcError = 'ESTADO DE ID DE TESIS ['.$this->paData['CIDTESI'].'] NO PERMITE ASIGNAR DICTAMINADORES/ASESOR/JURADOS'; 
          return false; 
       } 
       $lcUniAca = $RS[1]; 
       $lcNewReg = $RS[3]; 
       # Cargos decano/director de unidad academica 
       $lcSql = "SELECT cDocen1, cCargo1, cDocen2, cCargo2 FROM S01TUAC WHERE cUniAca = '{$lcUniAca}'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       $RS = $p_oSql->fetch($R1); 
       if (count($RS) == 0){ 
          $this->pcError = 'UNIDAD ACADEMICA ['.$lcUniAca.'] NO EXISTE'; 
          return false; 
       } 
       $this->laData = $laCargo1 = ['CDOCEN1'=> $RS[0], 'CCARGO1'=> $RS[1], 'CDOCEN2'=> $RS[2], 'CCARGO2'=> $RS[3], 'CNEWREG'=> $lcNewReg];  
       # Elimina dictaminadores/asesor/jurados (si los hubiera) 
       $lcCatego = $lcEstTes = '*'; 
       if ($this->paData['CESTTES'] == 'A'){ 
          $lcCatego = 'A'; 
          $lcEstTes = 'B'; 
       } else if ($this->paData['CESTTES'] == 'C'){ 
          $lcCatego = 'B'; 
          $lcEstTes = 'D'; 
          $lcBusCat = 'A'; 
          $lcError  = 'ASESOR'; 
       } else if ($this->paData['CESTTES'] == 'E'){ 
          $lcCatego = 'C'; 
          $lcEstTes = 'F'; 
          $lcBusCat = 'B'; 
          $lcError  = 'DICTAMINADOR DEL BORRADOR';
       } else if ($this->paData['CESTTES'] == 'H'){ 
          $lcCatego = 'D'; 
          $lcEstTes = 'I'; 
          $lcBusCat = 'B'; 
          $lcError  = 'JURADO'; 
       } else if ($this->paData['CESTDEC'] == 'A'){  
          return $this->mxTPT2110AsignacionDocenteDecano($p_oSql); 
       } else {  
          $this->pcError = 'ESTADO DE ID DE TESIS ['.$this->paData['CIDTESI'].'] ERRADO';  
          return false;  
       } 
       $lcSql = "DELETE FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cCatego = '{$lcCatego}'";  
       $llOk = $p_oSql->omExec($lcSql); 
       if (!$llOk) { 
          $this->pcError = 'ERROR AL ELIMINAR DICTAMINADORES/ASESOR/JURADOS ANTERIORES'; 
          return false; 
       } 
       # Verifica y graba docentes dictaminadores/asesor/jurados 
       $laCargo = ['P', 'V', 'S','V','V']; 
       for ($i=0; $i < count($this->laCodDoc); $i++) {  
           $lcSql = "SELECT cCodDoc, cNombre FROM V_A01MDOC WHERE cCodDoc = '{$this->laCodDoc[$i]['CCODDOC']}' AND cEstado = 'A'";  
           $R1 = $p_oSql->omExec($lcSql); 
           $R1 = $p_oSql->fetch($R1); 
           if ($R1[0] == ''){  
              $this->pcError = 'CODIGO DE DOCENTE ['.$this->laCodDoc[$i]['CCODDOC'].'] NO ESTA ACTIVO';  
              return false;  
           }  
           if ($this->laData['CNEWREG'] == '1' && $lcBusCat != ''){  
               $lcSql = "SELECT cCodDoc FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cCatego = '{$lcBusCat}' AND cCodDoc = '{$this->laCodDoc[$i]['CCODDOC']}' AND cEstado = 'A'";   
               $R2 = $p_oSql->omExec($lcSql);  
               $R2 = $p_oSql->fetch($R2);  
               if ($R2[0] != ''){  
                   $this->pcError = 'ERROR EL DOCENTE '.$R1[0].' - '.str_replace('/', ' ', $R1[1]).' NO PUEDE PARTICIPAR COMO '.$lcError;  
                   return false;  
               } 
           } 
           $lcCargo = '*'; 
           if ($this->paData['CESTTES'] == 'H'){ 
              $lcCargo = $laCargo[$i]; 
           } 
           if ($this->laCodDoc[$i]['CCODDOC'] != '0000'){ 
              $lcSql = "INSERT INTO T01DDOC (cIdTesi, cCodDoc, cEstado, cCatego, tDecret, cCargo, cUsuCod) VALUES  
                          ('{$this->paData['CIDTESI']}', '{$this->laCodDoc[$i]['CCODDOC']}', 'A', '{$lcCatego}', NOW(), '{$lcCargo}', '{$this->paData['CUSUCOD']}')"; 
           } 
           $llOk = $p_oSql->omExec($lcSql); 
           if (!$llOk){ 
              $this->pcError = 'ERROR AL INSERTAR DICTAMINADOR/ASESOR/JURADO'; 
              return false; 
           } 
        } 
       # Actualiza estado de tesis  
       $lcSql = "UPDATE T01MTES SET cEstTes = '{$lcEstTes}', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
       $llOk = $p_oSql->omExec($lcSql); 
       if (!$llOk){ 
          $this->pcError = 'ERROR AL ACTUALIZAR ESTADO DE TESIS'; 
          return false; 
       } 
       if ($this->paData['CESTTES'] == 'H'){ 
          # Actualiza aula y fecha y hora de sustentacion 
          $lcSql = "UPDATE T01MTES SET tDiaSus = '{$this->paData['TDIASUS']}', cEstPro = 'I', cAula = 'VIRTU' WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
          $llOk = $p_oSql->omExec($lcSql); 
          if (!$llOk){ 
             $this->pcError = 'ERROR AL ACTUALIZAR FECHA Y HORA DE SUSTENTACION'; 
             return false; 
          } 
       } 
       # Actualiza decano y director 
       $lcSql = "DELETE FROM T01DDEC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cEstTes = '{$lcEstTes}'"; 
       $llOk = $p_oSql->omExec($lcSql); 
       if (!$llOk){ 
          $this->pcError = 'ERROR AL ELIMINAR DICTAMINADORES/ASESOR/JURADOS ANTERIORES'; 
          return false; 
       } 
       $lcSql = "INSERT INTO T01DDEC (cIdTesi, cEstado, cCargo1, cDocen1, cCargo2, cDocen2, cEstPro, dFecha, cEstTes, cUsuCod) VALUES 
                  ('{$this->paData['CIDTESI']}', 'A', '{$laCargo1['CCARGO1']}', '{$laCargo1['CDOCEN1']}', '{$laCargo1['CCARGO2']}',  
                   '{$laCargo1['CDOCEN2']}', '*', NOW(), '{$lcEstTes}', '{$this->paData['CUSUCOD']}')"; 
       $llOk = $p_oSql->omExec($lcSql); 
       if (!$llOk){ 
          $this->pcError = 'ERROR AL ACTUALIZAR DECANO/DIRECTOR QUE FIRMAN DECRETO'; 
          return false; 
       } 
       $lcEstDes = true;
       if (in_array($lcUniAca, ['74','71','44','4F','4E','73','4K','4A'])) {
          $lcSql = "UPDATE T01MTES SET cEstDec = 'P' WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
          $llOk = $p_oSql->omExec($lcSql);
          if (!$llOk){ 
             $this->pcError = 'ERROR AL ACTUALIZAR ESTADO*'; 
             return false; 
          } 
          $lcSql = "UPDATE T01DDEC SET cEstDec = 'P' WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
          $llOk = $p_oSql->omExec($lcSql);
          if (!$llOk){ 
             $this->pcError = 'ERROR AL ACTUALIZAR ESTADO**'; 
             return false; 
          } 
          $lcEstDes = false;
       }
       $this->paData = ['CESTTES'=> $lcEstTes, 'CCATEGO'=> $lcCatego, 'CESTDEC'=> $lcEstDes]; 
       return $llOk; 
    }

    # -------------------------------------------------- 
   # Grabar dictaminadores (decano de ingenierias)
   # 2020-11-25 FPM Creacion 
   # -------------------------------------------------- 
   public function omTPT2110Grabar(){ 
       $llOk = $this->mxValParamFpg1420Grabar(); 
       if (!$llOk){ 
          return false; 
       } 
       $loSql = new CSql();  
       $llOk = $loSql->omConnect(); 
       if (!$llOk){ 
          $this->pcError = $loSql->pcError; 
          return false; 
       } 
       $llOk = $this->mxFpg1420Grabar($loSql); 
       if (!$llOk){ 
          $loSql->rollback(); 
          $loSql->omDisconnect(); 
          return false; 
       } 
       $loSql->omDisconnect(); 
       return $llOk; 
   } 

    function mxTPT2110AsignacionDocenteDecano($p_oSql){ 
      $lcError = $lcBusCat = ''; 
       if ($this->paData['CESTTES'] == 'B'){  
          $lcCatego = 'A';  
          $lcEstTes = 'B';  
       } else if ($this->paData['CESTTES'] == 'D'){  
          $lcCatego = 'B';  
          $lcEstTes = 'D';  
          $lcBusCat = 'A'; 
          $lcError  = 'ASESOR'; 
       } else if ($this->paData['CESTTES'] == 'F'){  
          $lcCatego = 'C';  
          $lcEstTes = 'F';  
          $lcBusCat = 'B'; 
          $lcError  = 'DICTAMINADOR DEL BORRADOR'; 
       } else if ($this->paData['CESTTES'] == 'I'){  
          $lcCatego = 'D';  
          $lcEstTes = 'I';  
          $lcBusCat = 'B'; 
          $lcError  = 'JURADO'; 
       }  
       $laCargo1 = $this->laData; 
       $laCargo = ['P', 'V', 'S','V','V'];  
       # Actualiza docentes decano  
       $lcSql = "DELETE FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cCatego = '{$lcCatego}'";  
       $llOk = $p_oSql->omExec($lcSql);  
       if (!$llOk){  
          $this->pcError = 'ERROR AL ELIMINAR DICTAMINADORES/ASESOR/JURADOS ANTERIORES';  
          return false;  
       }  
       for ($i=0; $i < count($this->laCodDoc); $i++) {   
           $lcSql = "SELECT cCodDoc, cNombre FROM V_A01MDOC WHERE cCodDoc = '{$this->laCodDoc[$i]['CCODDOC']}'";  
           $R1 = $p_oSql->omExec($lcSql);  
           $R1 = $p_oSql->fetch($R1);  
           if ($R1[0] == ''){  
              $this->pcError = 'CODIGO DE DOCENTE ['.$this->laCodDoc[$i]['CCODDOC'].'] NO ESTA ACTIVO';  
              return false;  
           } 
           if ($this->laData['CNEWREG'] == '1' && $lcBusCat != ''){  
               $lcSql = "SELECT cCodDoc FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cCatego = '{$lcBusCat}' AND cCodDoc = '{$this->laCodDoc[$i]['CCODDOC']}' AND cEstado = 'A'";   
               $R2 = $p_oSql->omExec($lcSql);  
               $R2 = $p_oSql->fetch($R2);  
               if ($R2[0] != ''){  
                   $this->pcError = 'ERROR EL DOCENTE '.$R1[0].' - '.str_replace('/', ' ', $R1[1]).' NO PUEDE PARTICIPAR COMO '.$lcError;  
                   return false;  
               } 
           } 
           $lcCargo = '*';  
           if ($lcEstTes == 'I'){  
              $lcCargo = $laCargo[$i];  
           }  
           if ($this->laCodDoc[$i]['CCODDOC'] != '0000'){  
              $lcSql = "INSERT INTO T01DDOC (cIdTesi, cCodDoc, cEstado, cCatego, tDecret, cCargo, cUsuCod) VALUES   
                          ('{$this->paData['CIDTESI']}', '{$this->laCodDoc[$i]['CCODDOC']}', 'A', '{$lcCatego}', NOW(), '{$lcCargo}', '{$this->paData['CUSUCOD']}')";  
           }  
           $llOk = $p_oSql->omExec($lcSql);  
           if (!$llOk){  
              $this->pcError = 'ERROR AL INSERTAR DICTAMINADOR/ASESOR/JURADO*';  
              return false;  
           }  
        }  
       # Actualiza estado de tesis   
       $lcSql = "UPDATE T01MTES SET cEstDec = 'A' ,cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdTesi = '{$this->paData['CIDTESI']}'";  
       $llOk = $p_oSql->omExec($lcSql);  
       if (!$llOk){  
          $this->pcError = 'ERROR AL ACTUALIZAR ESTADO DE TESIS';  
          return false;  
       }  
       if ($lcEstTes == 'I'){  
          # Actualiza aula y fecha y hora de sustentacion  
          $lcSql = "UPDATE T01MTES SET tDiaSus = '{$this->paData['TDIASUS']}', cEstPro = 'I', cAula = 'VIRTU' WHERE cIdTesi = '{$this->paData['CIDTESI']}'";  
          $llOk = $p_oSql->omExec($lcSql);  
          if (!$llOk){  
             $this->pcError = 'ERROR AL ACTUALIZAR FECHA Y HORA DE SUSTENTACION';  
             return false;  
          }  
       }  
       # Actualiza decano y director  
       $lcSql = "DELETE FROM T01DDEC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cEstTes = '{$lcEstTes}'";  
       $llOk = $p_oSql->omExec($lcSql);  
       if (!$llOk){  
          $this->pcError = 'ERROR AL ELIMINAR DICTAMINADORES/ASESOR/JURADOS ANTERIORES**';  
          return false;  
       }  
       $lcSql = "INSERT INTO T01DDEC (cIdTesi, cEstado, cCargo1, cDocen1, cCargo2, cDocen2, cEstPro, dFecha, cEstTes, cUsuCod) VALUES  
                  ('{$this->paData['CIDTESI']}', 'A', '{$laCargo1['CCARGO1']}', '{$laCargo1['CDOCEN1']}', '{$laCargo1['CCARGO2']}',   
                   '{$laCargo1['CDOCEN2']}', '*', NOW(), '{$lcEstTes}', '{$this->paData['CUSUCOD']}')";  
       $llOk = $p_oSql->omExec($lcSql);  
       if (!$llOk){  
          $this->pcError = 'ERROR AL ACTUALIZAR DECANO/DIRECTOR QUE FIRMAN DECRETO';  
          return false;  
       }  
       $this->paData = ['CESTTES'=> $lcEstTes, 'CCATEGO'=> $lcCatego, 'CESTDEC'=> true];  
       return true;  
    } 
 
   # -----------------------------------------------------  
   # Init aprobacion PDT, fin de asesoria, aprobacion BDT   TPT3110 hasta TPT3130 
   # 2020-06-17 FPM Creacion  
   # -----------------------------------------------------  
   public function omTPT3110Init(){  
       $llOk = $this->mxValParam();  
       if (!$llOk){  
          return false;  
       }  
       $loSql = new CSql();   
       $llOk = $loSql->omConnect();  
       if (!$llOk){  
          $this->pcError = $loSql->pcError;  
          return false;  
       }  
      //  $loGestion = new CGestionTesis();   
      //  $llOk = $loGestion->mxBuscarExpedienteJuridico
       $llOk = $this->mxTPT3110Init($loSql);  
       $loSql->omDisconnect();  
       return $llOk;  
   }  
  
   protected function mxTPT3110Init($p_oSql){  
       $laData = null;  
       $laDatos = null;  
       $laTmp = null;  
       //echo $this->paData['COPTION'];
       // pendientes de revision  
       $lcSql = "SELECT DISTINCT A.cIdTesi FROM T01DDOC A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi   
                  WHERE A.cCodDoc = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND A.tDictam IS NULL AND A.cResult = 'P'";
       $R1 = $p_oSql->omExec($lcSql);  
       while ($RS = $p_oSql->fetch($R1)){  
           $lcSql = "SELECT B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, F.cDescri, TO_CHAR(B.dEntreg, 'YYYY-mm-dd HH24:MI'), B.cTipo FROM T01DALU A  
                      INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi  
                      INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca  
                      INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu  
                      LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes  
                      LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '143'
                      WHERE A.cIdTesi = '{$RS[0]}' AND B.cEstTes = '{$this->paData['COPTION']}' AND B.cEstado IN ('A','O') AND B.cEstDec = 'A'";
           $R2 = $p_oSql->omExec($lcSql);  
           while ($R2 = $p_oSql->fetch($R2)){  
               $lcDesEst = ($R2[6] == None)? '*' : $R2[5].' - '.$R2[6];  
               $laTmp[] = $RS[0];  
               $laData[] = ['CIDTESI'=> $RS[0], 'CESTADO'=> $R2[0], 'CNOMBRE'=> str_replace('/', ' ',$R2[1]), 'MTITULO'=> $R2[2],  
                        'CUNIACA'=> $R2[3].' - '.$R2[4], 'CESTTES'=> $lcDesEst, 'CTIPO'=> $R2[7], 'CTITULA'=> $R2[9], 'DENTREG'=> $R2[8], 'CFLAG'=> $RS[0].$R2[3].$R2[5].$R2[9]];  
           }
             
       } 
       $lcSql = "SELECT DISTINCT A.cIdTesi FROM T01DDOC A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi   
                  WHERE A.cCodDoc = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND A.tDictam IS NULL AND A.cResult = 'O'";  
       $R1 = $p_oSql->omExec($lcSql); 
       while ($RS = $p_oSql->fetch($R1)){   
           $lcSql = "SELECT B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, F.cDescri FROM T01DALU A   
                      INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi   
                      INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca   
                      INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu   
                      LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes  
                      LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '143' 
                      WHERE A.cIdTesi = '{$RS[0]}' AND B.cEstTes = '{$this->paData['COPTION']}' AND B.cEstDec = 'A'";   
           $R2 = $p_oSql->omExec($lcSql);   
           while ($R2 = $p_oSql->fetch($R2)){   
               $lcDesEst = ($R2[6] == None)? '*' : $R2[5].' - '.$R2[6];   
               $laTmp[] = $RS[0];   
               $laObs[] = ['CIDTESI'=> $RS[0], 'CESTADO'=> $R2[0], 'CNOMBRE'=> str_replace('/', ' ',$R2[1]), 'MTITULO'=> $R2[2],   
                           'CUNIACA'=> $R2[3].' - '.$R2[4], 'CESTTES'=> $lcDesEst, 'CTIPO'=> $R2[7]];   
           }   
       }  
       $lcSql = "SELECT DISTINCT A.cIdTesi FROM T01DDOC A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi  
                  WHERE A.cCodDoc = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND A.tDictam IS NOT NULL";  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($RS = $p_oSql->fetch($R1)){  
           if (in_array($RS[0], $laTmp)) {  
              continue;  
           }  
           $lcSql = "SELECT B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, F.cDescri FROM T01DALU A  
                      INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi  
                      INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca  
                      INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu  
                      LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                      LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '143' 
                      WHERE A.cIdTesi = '{$RS[0]}' AND B.cEstTes = '{$this->paData['COPTION']}'  AND B.cEstado IN ('A','O') AND B.cEstDec = 'A'";  
           $R2 = $p_oSql->omExec($lcSql);  
           while ($R2 = $p_oSql->fetch($R2)){  
               $lcDesEst = ($R2[6] == None)? '*' : $R2[5].' - '.$R2[6];  
               $laDatos[] = ['CIDTESI'=> $RS[0], 'CESTADO'=> $R2[0], 'CNOMBRE'=> str_replace('/', ' ',$R2[1]), 'MTITULO'=> $R2[2],  
                             'CUNIACA'=> $R2[3].' - '.$R2[4], 'CESTTES'=> $lcDesEst, 'CTIPO'=> $R2[7]];  
           }  
       }  
       $this->paData = $laData;  
       $this->paDatos = $laDatos;  
       $this->paObserv = $laObs;
       return true;  
    }   
 
   # -------------------------------------------------- 
   # Graba aprobacion de dictaminadores de PDT 
   # 2020-06-11 FPM Creacion 
   # -------------------------------------------------- 
   public function omFpg1430Aprobar(){ 
       $llOk = $this->mxValParamFpg1430Aprobar(); 
       if (!$llOk){ 
          return false; 
       } 
       $loSql = new CSql();  
       $llOk = $loSql->omConnect(); 
       if (!$llOk){ 
          $this->pcError = $loSql->pcError; 
          return false; 
       } 
       $llOk = $this->mxFpg1430Aprobar($loSql); 
       if (!$llOk){ 
          $loSql->rollback(); 
       } 
       $loSql->omDisconnect(); 
       return $llOk; 
   } 
 
   protected function mxValParamFpg1430Aprobar(){ 
       $llOk = $this->mxValParam(); 
       if (!$llOk){ 
          return false; 
       } else if (!isset($this->paData['CESTTES']) || strlen($this->paData['CESTTES']) != 1){ 
          $this->pcError = 'ESTADO DE LA TESIS NO DEFINIDO O INVALIDO'; 
          return false; 
       } else if (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6){ 
          $this->pcError = 'ID DE TESIS NO DEFINIDO O INVALIDO'; 
          return false; 
       } 
       return true; 
   } 
 
   protected function mxFpg1430Aprobar($p_oSql){ 
       $lcCatego = ''; 
       # Verifica estado de tesis 
       $lcSql = "SELECT cEstTes, cNewReg FROM T01MTES WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       $R1 = $p_oSql->fetch($R1); 
       if (count($R1) == 0){ 
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE'; 
          return false; 
       } else if ($R1[0] != $this->paData['CESTTES']){ 
          $this->pcError = 'ESTADO DE ID DE TESIS ['.$this->paData['CIDTESI'].'] NO PERMITE APROBACION'; 
          return false; 
       }
       $lcNewReg = $RS[1];
       if ($this->paData['CESTTES'] == 'B'){ 
          $lcCatego = 'A'; 
       } else if ($this->paData['CESTTES'] == 'D'){ 
          $lcCatego = 'B'; 
       } else if ($this->paData['CESTTES'] == 'F'){ 
          $lcCatego = 'C'; 
       } else{ 
          $this->pcError = 'ESTADO DE ID DE TESIS ['.$this->paData['CIDTESI'].'] NO PERMITE APROBACION'; 
          return false; 
       } 
       $lcSql = "SELECT nSerial, TO_CHAR(tDictam, 'YYYY-MM-DD HH24MI') FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND 
                  cCodDoc = '{$this->paData['CUSUCOD']}' AND cEstado = 'A' AND cCatego = '{$lcCatego}'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       $R1 = $p_oSql->fetch($R1); 
       if (count($R1) == 0){ 
          $this->pcError = 'DOCENTE NO TIENE RELACION CON ID DE TESIS ['.$this->paData['CIDTESI'].']'; 
          return false; 
       } else if ($R1[1] != ''){ 
          $this->pcError = 'PROYECTO DE TESIS YA FUE APROBADO POR DOCENTE'; 
          return false; 
       } 
       $lcSql = "UPDATE T01DDOC SET tDictam = NOW(), cResult = 'A', cUsuCod = '{$this->paData['CUSUCOD']}' WHERE nSerial = {$R1[0]}"; 
       $llOk = $p_oSql->omExec($lcSql); 
       if (!$llOk){ 
          $this->pcError = 'ERROR AL APROBAR PROYECTO DE TESIS'; 
          return false; 
       } 
       # Verifica dictaminadores/asesores 
       $i = 0; 
       $llOk = true; 
       $lcSql = "SELECT TO_CHAR(tDictam, 'YYYY-MM-DD HH24MI'), cResult FROM T01DDOC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND 
                  cEstado = 'A' AND cCatego = '{$lcCatego}'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ( $RS = $p_oSql->fetch($R1)){ 
           $i += 1; 
           if ($RS[0] == '' || $RS[1] != 'A'){ 
              $llOk = false; 
           } 
        } 
       # Valida estado de tesis con cantidad de dictaminadores/asesor/jurados 
       if ($this->paData['CESTTES'] == 'B' and !in_array($i, [1,2,3,5])){ 
          $this->pcError = 'NO HAY 2 DICTAMINADORES DE PROYECTO DE TESIS DEFINIDOS'; 
          return false; 
       } else if ( $this->paData['CESTTES'] == 'D' and $i != 1){ 
          $this->pcError = 'NO HAY 1 ASESOR DE TESIS DEFINIDO'; 
          return false; 
       } else if ( $this->paData['CESTTES'] == 'F' and !in_array($i, [2,3,5])){ 
          $this->pcError = 'NO HAY 2 DICTAMINADORES DE BORRADOR DE TESIS DEFINIDOS'; 
          return false; 
       } else if ( $this->paData['CESTTES'] == 'I' and !in_array($i, [3,5])){ 
          $this->pcError = 'NO HAY 3 JURADOS DE TESIS DEFINIDOS'; 
          return false; 
       }
       if ($llOk){ 
          #Resultado del dictamen 
          $lcSql = "UPDATE T01DDEC SET cResult = 'A' WHERE cEstTes = '{$this->paData['CESTTES']}' AND cIdTesi = '{$this->paData['CIDTESI']}'"; 
          $llOk = $p_oSql->omExec($lcSql); 
          if (!$llOk){ 
             $this->pcError = 'ERROR AL ACTUALIZAR RESULTADO DE DICTAMEN TESIS'; 
             return false; 
          } 
          # Corresponde aprobacion 
          $lcEstTes = chr(ord($this->paData['CESTTES']) + 1); 
          $lcSql = "UPDATE T01MTES SET cEstTes = '{$lcEstTes}' WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
          $llOk = $p_oSql->omExec($lcSql); 
          if (!$llOk){ 
             $this->pcError = 'ERROR AL ACTUALIZAR MAESTRO DE TESIS'; 
             return false; 
          } 
          if ($lcEstTes == 'G'){
            $lcSql = "UPDATE T01MTES SET cEstPro = 'F' WHERE cIdTesi = '{$this->paData['CIDTESI']}'"; 
            $llOk = $p_oSql->omExec($lcSql); 
            if (!$llOk){ 
               $this->pcError = 'ERROR AL ACTUALIZAR MAESTRO DE TESIS'; 
               return false; 
            } 
          }
       } 
       # Confirma aprobacion 
       return true; 
   }  
    
   # -----------------------------------------------------------  
   # Visualiza datos de tesis  TPT3110 hasta TPT3130 
   # 2020-06-11 FPM Creacion  
   # 2021-10-20 APR Complementación
   # -----------------------------------------------------------  
   public function omTPT3110Ver(){  
       $llOk = $this->mxValParamFpg1420Ver();  
       if (!$llOk){  
          return false;  
       }  
       $loSql = new CSql();  
       $llOk = $loSql->omConnect();  
       if (!$llOk){  
          $this->pcError = $loSql->pcError;  
          return false;  
       }  
       $llOk = $this->mxTPT3110Ver($loSql);  
       $loSql->omDisconnect();  
       return $llOk;  
   }  
  
   protected function mxTPT3110Ver($p_oSql){  
      $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, B.cNivel, A.cNewReg FROM T01MTES A  
               INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca  
               LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes  
               WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cEstado != 'X'";  
      $R1 = $p_oSql->omExec($lcSql);  
      $laFila = $p_oSql->fetch($R1);  
      if (count($laFila) == 0){  
         $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE';  
         return false;  
      }  
      $lcDesEst =  ($laFila[6] == '')? '[ERR]' : $laFila[6];  

      $laData = ['CIDTESI'=>$laFila[0], 'CESTADO'=>$laFila[1],     'MTITULO'=>$laFila[2], 'CUNIACA'=>$laFila[3].' - '.$laFila[4],  
                 'CESTTES'=>$laFila[5], 'CDESEST'=> $lcDesEst, 'ACODALU'=> '',    'ACODDOC'=> null,  'ACODASE'=> null,   
                 'ACODDIC'=> null,  'ACODJUR'=> null,      'CNIVEL'=> $laFila[7], 'CNEWREG'=> $laFila[8],'ATESANT'=> null];
      # Estudiante(s) de la tesis  
      $laCodAlu = $this->mxAlumnos($p_oSql);  
      if (count($laCodAlu) == 0){  
         $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE ALUMNOS DEFINIDOS';  
         return false;  
      }  
      $laData['ACODALU'] = $laCodAlu;
      # Tesis anuladas anteriormente 
      $laTesAnu = $this->mxTesisAnuladas($p_oSql);    
      $laData['ATESANT'] = $laTesAnu;  
       if ($laData['CESTTES'] >= 'B'){  
          # Dictaminadores de PDT  
          $laArray = $this->mxDictaminadoresPDT($p_oSql);  
           if (count($laArray) != 2 AND $laData['CNIVEL'] != '04' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE DOS DICTAMINADORES DE PROYECTO DE TESIS DEFINIDOS';  
             return false;  
          } else if (!in_array(count($laArray), [2,3]) AND $laData['CNIVEL'] == '04' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES DICTAMINADORES DE PROYECTO DE TESIS DEFINIDOS';  
             return false;  
          }  
          $laData['ACODDOC'] = $laArray;  
       }  
       if ($laData['CESTTES'] >= 'D'){  
          # Asesor de la tesis  
          $laArray = $this->mxAsesorTesis($p_oSql);   
          if (count($laArray) != 1 AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE UN ASESOR DEFINIDO';  
             return false;  
          }  
          $laData['ACODASE'] = $laArray;  
       }  
       if ($laData['CESTTES'] >= 'F'){  
          # Dictaminadores de BDT  
          $laArray = $this->mxDictaminadoresBDT($p_oSql);   
          if (count($laArray) != 3 AND $laData['CNIVEL'] != '04' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE DOS DICTAMINADORES DE PROYECTO DE TESIS DEFINIDOS';  
             return false;   
          } else if (count($laArray) != 5 AND $laData['CNIVEL'] == '04' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE 5 DICTAMINADORES DE BORRADOR DE TESIS DEFINIDOS';  
             return false;  
          }  
          $laData['ACODDIC'] = $laArray;  
       }  
       if ($laData['CESTTES'] >= 'I'){  
          # Dictaminadores de BDT  
          $laArray = $this->mxJuradoTesis($p_oSql);   
          if (count($laArray) != 3 AND $laData['CNIVEL'] != '04'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE 3 JURADOS DEFINIDOS';  
             return false;  
          } else if (count($laArray) != 5 AND $laData['CNIVEL'] == '04'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE 5 JURADOS DEFINIDOS';  
             return false;  
          }  
          $laData['ACODJUR'] = $laArray;  
       }  
       $this->paData = $laData;  
       return true;  
    } 

    # --------------------------------------------------
   # Visualiza datos de tesis para anulacion
   # 2020-06-19 FPM Creacion
   # --------------------------------------------------
   public function omFpg1470Init(){
       $llOk = $this->mxValParamFpg1470Init();
       if (!$llOk){
          return false;
       }
       $loSql = new CSql(); 
       $llOk  = $loSql->omConnect(); 
       if (!$llOk){
          $this->pcError = $loSql->pcError;
          return False;
       }
       $llOk = $this->mxFpg1470Init($loSql);
       $loSql->omDisconnect();
       return $llOk;
   }

   protected function mxValParamFpg1470Init(){
       if (!isset($this->paData) || strlen($this->paData['CNRODNI']) != 8){
          $this->pcError = 'NUMERO DE DNI NO DEFINIDO O INVALIDO';
          return false;
       }
       return true;
   }

   protected function mxFpg1470Init($p_oSql){
       $laDatos = [];
       $this->paData['CIDTESI'] = '';
       $lcSql = "SELECT cIdTesi FROM T01DALU WHERE cCodAlu = '{$this->paData['CCODALU']}'";
       $R1 = $p_oSql->omExec($lcSql);
       $RS = $p_oSql->fetch($R1);
       $this->paData['CIDTESI'] = $RS[0];
       $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, B.cNivel FROM T01MTES A
                  INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes
                  INNER JOIN T01DALU D ON D.cIDTesi = A.cIdTesi
                  WHERE D.cEstado = 'A' AND D.cCodAlu = '{$this->paData['CCODALU']}' AND
                  A.cEstTes >= 'A' AND A.cEstTes <= 'J' ORDER BY A.cIdTesi";
       $R1 = $p_oSql->omExec($lcSql);
       $RS = $p_oSql->fetch($R1);
       $laDatos = ['CIDTESI' => $RS[0], 'CESTADO' => $RS[1], 'MTITULO' => $RS[2], 'CUNIACA' => $RS[3].' - '.$RS[4],
                    'CNOMUNI' => $RS[4], 'CESTTES' => $RS[5], 'CDESEST' => $RS[6], 'CNIVEL' => $RS[7]];
       $this->paDatos = $laDatos;
       $this->paAlumno = $this->mxAlumnos($p_oSql);
       return true;
    }

    //-----------------------------------------------  
   // Obtener los decretos de tesis entre una fecha para un docente - PLT3240 
   // 2020-07-10 FPL  
   //----------------------------------------------- 
 
   public function omVerDecretosTesisDocente() { 
      $llOk = $this->mxValVerDecretosTesisDocente(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk  = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxVerDecretosTesisDocente($loSql); 
      $loSql->omDisconnect(); 
      if (!$llOk) { 
         return false; 
      } 
      return $llOk; 
   } 
 
   protected function mxValVerDecretosTesisDocente() { 
      $loDate = new CDate(); 
      if (!isset($this->paData['DINICIO']) || !$loDate->mxValDate($this->paData['DINICIO'])) { 
         $this->pcError = 'FECHA INICIAL INVALIDA'; 
         return false; 
      }  elseif (!isset($this->paData['DFINALI']) || !$loDate->mxValDate($this->paData['DFINALI'])) { 
            $this->pcError = 'FECHA FINAL INVALIDA'; 
            return false; 
      }  elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) { 
            $this->pcError = 'FECHA FINAL NO PUEDE SER MENOR QUE FECHA INICIAL'; 
            return false; 
      } 
      return true; 
   } 
 
   protected function mxVerDecretosTesisDocente($p_oSql) { 
      $this->laData = $this->paData; 
      $lcBusDoc = $this->paData['CUSUCOD']; 
      $lcSql  = "SELECT DISTINCT(A.cIdTesi), TO_CHAR(B.tDecret, 'YYYY-MM-DD HH24:MI'), B.cCatego, CASE B.cCatego
                        WHEN 'A' THEN 'B' 
                        WHEN 'B' THEN 'D'
                        WHEN 'C' THEN 'F'
                        ELSE 'I'
                     END AS cEstTes, B.cCargo, D.cEmail FROM T01DALU A 
                     INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi 
                     LEFT OUTER JOIN V_A01MALU C ON C.cCodAlu = A.cCodAlu 
                     LEFT OUTER JOIN V_A01MDOC D ON D.cCodDoc = B.cCodDoc 
                     WHERE B.tDecret::DATE BETWEEN '{$this->laData['DINICIO']}' AND '{$this->laData['DFINALI']}' AND B.cCodDoc = '{$lcBusDoc}' AND A.cEstado = 'A'
                     ORDER BY A.cIdTesi ASC, B.cCatego ASC"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) { 
         $this->pcError = "ERROR AL RECUPERAR DATOS"; 
         return false; 
      } elseif ($p_oSql->pnNumRow == 0) { 
         $this->pcError = 'NO HAY DECRETOS ENTRE LAS FECHAS'; 
         return false; 
      } 
      $i = 0; 
      while ($laFila = $p_oSql->fetch($llOk)) { 
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'DFECHA' => $laFila[1], 'CCATEGO'  => $laFila[2], 'CESTTES'  => $laFila[3], 'CCARGO'  => $laFila[4]]; 
      }     
      return true; 
   } 

   //-----------------------------------------------  
   // CONSULTA DOCENTE TESIS PARA SUSTENTACION
   // 2020-08-06 FLC 
   //----------------------------------------------- 
 
   public function omTPT3140Init() { 
      $llOk = $this->mxValTPT3140Init(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk  = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxVerTPT3140Init($loSql); 
      $loSql->omDisconnect(); 
      if (!$llOk) { 
         return false; 
      } 
      return $llOk; 
   } 
 
   protected function mxValTPT3140Init() { 
      $loDate = new CDate(); 
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) { 
         $this->pcError = 'USUARIO INVALIDO'; 
         return false; 
      } 
      return true; 
   } 
 
   protected function mxVerTPT3140Init($p_oSql) { 
      $this->laData = $this->paData; 
      $lcBusDoc = $this->paData['CUSUCOD']; 
      $lcSql  = "SELECT A.tDiaSus, A.cIdTesi, C.cUniAca, C.cNomUni, B.cCodAlu, C.cNombre
                     FROM T01MTES A 
                     INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                     INNER JOIN S01TUAC D ON D.cUniAca = A.cUniAca
                     INNER JOIN T01DDOC E ON E.cIdTesi = A.cIdTesi
                     WHERE A.cEstTes = 'I' AND E.cCodDoc = '$lcBusDoc' 
                     GROUP BY A.tDiaSus, A.cIdTesi, C.cUniAca, C.cNomUni, B.cCodAlu, C.cNombre"; 
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)) { 
         $this->paDatos[] = ['TDIASUS' => $laFila[0], 'CIDTESI' => $laFila[1], 'CUNIACA' => $laFila[2].' - '.$laFila[3],
                              'CCODALU'  => $laFila[4], 'CNOMBRE'  => str_replace('/', ' ', $laFila[5])]; 
      }     
      return true; 
   } 

   # -------------------------------------------------- 
   # Actas Listas y Pendientes - TPT4110 - secretaria
   # 2020-06-11 FPM Creacion 
   # -------------------------------------------------- 
 
   public function omTPT4110Init() {  
      $llOk = $this->mxValParamTPT4110Init();  
      if (!$llOk) {  
         return false;  
      }  
      $loSql = new CSql();  
      $llOk  = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk  = $this->mxValUsuario($loSql);  
      if (!$llOk) {  
         $loSql->omDisconnect();  
         return false;  
      }  
      $llOk = $this->mxTPT4110Init($loSql);  
      $loSql->omDisconnect();  
      if (!$llOk) {  
         return false;  
      }  
      return $llOk;  
   }  
  
   protected function mxValParamTPT4110Init(){ 
       if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4){ 
          $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'; 
          return false; 
       } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3){ 
          $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVALIDO'; 
          return false; 
       } 
       return true; 
   } 
  
   protected function mxTPT4110Init($p_oSql) {  
      $laData  = []; 
      $laDatos = []; 
      // pendientes de asignacion 
      $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri FROM T01DALU A 
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                  INNER JOIN T02MLIB F ON B.cIdTesi = F.cIdTesi
                  WHERE B.cEstTes = 'K' AND B.cEstado != 'X' AND F.cEstado = 'A' ORDER BY D.cNombre"; 
       #print lcSql 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) {  
           # Valida si usuario accede a unidad academica de tesis 
           if ($this->laUniAca[0] == '*'){} 
           else if (!in_array($laFila[4], $this->laUniAca)){ 
              continue; 
           } 
           $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
           $laData[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3], 
                    'CUNIACA' => $laFila[4].' - '.$laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst]; 
       } 
       $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri FROM T01DALU A 
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                  WHERE B.cEstTes = 'J' AND B.cEstado != 'X' ORDER BY D.cNombre"; 
       $R1 = $p_oSql->omExec($lcSql); 
       while ($laFila = $p_oSql->fetch($R1)) {  
           # Valida si usuario accede a unidad academica de tesis 
           if ($this->laUniAca[0] == '*'){} 
           else if (!in_array($laFila[4], $this->laUniAca)){ 
              continue; 
           } 
           $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
           $laDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3], 
                    'CUNIACA' => $laFila[4].' - '.$laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst]; 
       } 
       $this->paData  = $laData; 
       $this->paDatos = $laDatos; 
       return true; 
   }

   # -------------------------------------------------- 
   # Visualiza datos de tesis - secretaria
   # 2020-06-11 FPM Creacion 
   # -------------------------------------------------- 
   public function omTPT4110Ver(){ 
       $llOk = $this->mxValParamTPT4110Ver(); 
       if (!$llOk){ 
          return false; 
       } 
       $loSql = new CSql();  
       $llOk = $loSql->omConnect(); 
       if (!$llOk){ 
          $this->pcError = $loSql->pcError; 
          return false; 
       } 
       $llOk = $this->mxValUsuario($loSql); 
       if (!$llOk){ 
          $loSql->omDisconnect(); 
          return false; 
       } 
       $llOk = $this->mxTPT4110Ver($loSql); 
       $loSql->omDisconnect(); 
       return $llOk; 
   } 
 
   protected function  mxValParamTPT4110Ver(){ 
       $llOk = $this->mxValParam(); 
       if (!$llOk){ 
          return false; 
       } else if (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6){ 
          $this->pcError = 'ID DE TESIS NO DEFINIDO O INVALIDO'; 
          return false; 
       } 
       return true; 
   } 
 
   protected function mxTPT4110Ver($p_oSql){ 
       $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, B.cNivel, A.cNewReg FROM T01MTES A 
                  INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cEstado != 'X'"; 
       $R1 = $p_oSql->omExec($lcSql); 
       $RS = $p_oSql->fetch($R1); 
       if ( $RS[0] == ''){ 
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE'; 
          return false; 
       } 
       $lcDesEst = ($RS[6] == '')? '[ERR]' : $RS[6]; 
       $laData = ['CIDTESI'=> $RS[0], 'CESTADO'=> $RS[1], 'MTITULO'=> $RS[2], 'CUNIACA'=> $RS[3].' - '.$RS[4], 
                 'CESTTES'=> $RS[5], 'CDESEST'=> $lcDesEst, 'ACODALU'=> null, 'ACODDOC'=> null, 'ACODASE'=> null, 'ACODDIC'=> null, 
                 'ACODJUR'=> null, 'CNIVEL'=> $RS[7], 'CNEWREG'=> $RS[8]]; 
       # Alumnos de tesis                
       $laCodAlu = $this->mxAlumnos($p_oSql); 
       if (count($laCodAlu) == 0){ 
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE ALUMNOS DEFINIDOS'; 
          return false; 
       } 
       $laData['ACODALU'] = $laCodAlu; 
       $laCodJur = $this->mxJuradoTesis($p_oSql); 
       $laData['ACODJUR'] = $laCodJur; 
       $this->paData = $laData; 
       return true; 
   }  

   //--------------------------------------------
   // ENVIAR DE NUEVO A TURNITIN
   // 2020-08-14 FLC
   //------------------------------------------------

   public function omServicioBibliotecaTurnitinLevantarObservaciones() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxServicioBibliotecaTurnitinLevantarObservaciones($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxServicioBibliotecaTurnitinLevantarObservaciones($p_oSql) {
      $this->paDatos = [];
      foreach ($this->paData['ALUMNOS'] as $laFila) {
          $autores[] = ['codigo' => $laFila['CCODALU']];  
      }
      $params = ['titulo' => $this->paData['MTITULO'], "tipo" => substr($this->paData['CTIPO'], 0, 1), 'autores' => $autores]; 
      $params = json_encode($params);
      // Create the context for the request
      $laContext = stream_context_create(array(
        'http' => [
           'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                       "Content-Length: ".strlen($params)."\r\n",
           'method' => 'GET', 
           'content' => $params]
      ));
      $response = file_get_contents('http://cib.ucsm.edu.pe/api-rest-biblio/solicitudes-turnitin?venviar=1', false, $laContext);
      if (!$response) {
        $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO DE  BIBLIOTECA';
        return false;
      } 
      $response = json_decode($response, true);
      $this->paDatos['URL'] = $response['url'];
      return true;
   }

   //--------------------------------------------------------
   // BUSQUEDA DE ESTADO DE TESIS 
   // 2020-08-17 FLC
   //--------------------------------------------------------
   public function omBuscarTesis(){
      $llOk = $this->mxValBuscarTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarTesis($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarTesis(){
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INV�?LIDO O NO DEFINIDO';
         return false;
      }  elseif (!isset($this->paData['CBUSCAR']) || strlen($this->paData['CBUSCAR']) < 6) {
            $this->pcError = 'ERROR, DEBE INGRESAR POR LO MINIMO 6 CARACTERES';
            return false;
      }      
      return true;
   }

   protected function mxBuscarTesis($p_oSql){
      $this->paData['CBUSCAR'] = str_replace(' ', '%', $this->paData['CBUSCAR']);
      $this->paData['CBUSCAR'] = strtoupper($this->paData['CBUSCAR']);
      $lcSql = "SELECT A.cIdTesi, E.cDescri, A.mTitulo, B.cCodAlu, C.cNombre, A.cUniAca, D.cNomUni FROM T01MTES A
                  INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                  INNER JOIN S01TUAC D ON D.cUniAca = A.cUniAca 
                  LEFT OUTER JOIN S01TTAB E ON E.cCodigo = A.cTipo AND E.cCodTab = '143'
                  WHERE (A.cIdTesi = '{$this->paData['CBUSCAR']}' OR C.cNombre LIKE '%{$this->paData['CBUSCAR']}%' OR
                        B.cCodAlu = '{$this->paData['CBUSCAR']}' OR A.mTitulo LIKE '%{$this->paData['CBUSCAR']}%') AND A.cEstTes NOT IN ('X') AND B.cEstado = 'A' ORDER BY A.cIdTesi ASC";
      //echo $lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      while ($RS = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDTESI'=> $RS[0], 'CTIPO'=> $RS[1], 'MTITULO'=> $RS[2], 'CCODALU'=> $RS[3], 'CNOMBRE'=> str_replace('/', ' ', $RS[4]), 
                             'CUNIACA'=> $RS[5].' - '.$RS[6]]; 
      }
      return true;
   }

   # --------------------------------------------------  
   # VISUALIZAR DATOS DE LA TESIS DECANO 
   # 2020-06-11 FPM Creacion  
   # --------------------------------------------------  
   public function omTPT2110AprobarAsignacionDocente(){  
      $llOk = $this->mxValParamTPT2110AprobarAsignacionDocente();  
       if (!$llOk){  
          return false;  
       }  
       $loSql = new CSql();   
       $llOk = $loSql->omConnect();  
       if (!$llOk){  
          $this->pcError = $loSql->pcError;  
          return false;  
       }  
       $llOk = $this->mxTPT2110AprobarAsignacionDocente($loSql);  
       $loSql->omDisconnect();  
       return $llOk;  
   }  
  
   protected function  mxValParamTPT2110AprobarAsignacionDocente(){  
       $llOk = $this->mxValParam();  
       if (!$llOk){  
          return false;  
       } else if (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6){  
          $this->pcError = 'ID DE TESIS NO DEFINIDO O INVALIDO';  
          return false;  
       }  
       return true;  
   }  
  
   protected function mxTPT2110AprobarAsignacionDocente($p_oSql){  
       $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, B.cNivel, A.cNewReg, A.cEstDec, TO_CHAR(A.tDiaSus, 'YYYY-MM-DD HH:mm') FROM T01MTES A  
                  INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca  
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes  
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cEstado != 'X'";  
       $R1 = $p_oSql->omExec($lcSql);  
       $RS = $p_oSql->fetch($R1);  
       if ( $RS[0] == ''){  
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE';  
          return false;  
       }  
       $lcDesEst = ($RS[6] == '')? '[ERR]' : $RS[6];  
       $laData = ['CIDTESI'=> $RS[0], 'CESTADO'=> $RS[1], 'MTITULO'=> $RS[2], 'CUNIACA'=> $RS[3].' - '.$RS[4],  
                 'CESTTES'=> $RS[5], 'CDESEST'=> $lcDesEst, 'ACODALU'=> null, 'ACODDOC'=> null, 'ACODASE'=> null, 'ACODDIC'=> null,  
                 'ACODJUR'=> null, 'CNIVEL'=> $RS[7], 'CNEWREG'=> $RS[8], 'CESTDEC'=> $RS[9], 'TDIASUS'=> $RS[10]];  
       # Alumnos de tesis                 
       $laCodAlu = $this->mxAlumnos($p_oSql);  
       if (count($laCodAlu) == 0){  
          $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE ALUMNOS DEFINIDOS';  
          return false;  
       }  
       $laData['ACODALU'] = $laCodAlu;  
       # Dictaminadores de PDT  
       if ($laData['CESTTES'] >= 'B' && $laData['CESTTES'] <= 'J'){  
          $laCodDoc = $this->mxDecanoDictaminadoresPDT($p_oSql);  
          if (count($laCodDoc) != 2 and $laData['CNIVEL'] != '04' and $laData['CNIVEL'] != '02' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE DOS  DICTAMINADORES DEFINIDOS';  
             return false;  
          } elseif (!in_array(count($laCodDoc),[2,3]) and $laData['CNIVEL'] == '04' AND $laData['CNEWREG'] == '1'){   
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES DICTAMINADORES DEFINIDOS';  
             return false;  
          }  
          $laData['ACODDOC'] = $laCodDoc;  
          //print_r($laCodDoc); 
       }  
       # Asesor de tesis  
       if ($laData['CESTTES'] >= 'D' and $laData['CESTTES'] <= 'J'){  
          $laCodAse = $this->mxDecanoAsesorTesis($p_oSql);  
          if (count($laCodAse) != 1 AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE UN ASESOR DEFINIDO';  
             return false;  
          }  
          $laData['ACODASE'] = $laCodAse;  
          //print_r($laCodAse); 
       }  
       # Dictaminadores de borrador de tesis  
       if ($laData['CESTTES'] >= 'F' and $laData['CESTTES'] <= 'J'){   
          $laCodDic = $this->mxDecanoDictaminadoresBDT($p_oSql);  
          if (count($laCodDic) != 3 and $laData['CNIVEL'] != '04' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES DICTAMINADORES BOORADOR DEFINIDOS';  
             return false;  
          } else if (count($laCodDoc) != 5 and $laData['CNIVEL'] == '04' AND $laData['CNEWREG'] == '1'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE CINCO DICTAMINADORES BOORADOR DEFINIDOS';  
             return false;  
          }  
          $laData['ACODDIC'] = $laCodDic;  
       }  
       # Dictaminadores de Jurado de tesis  
       if ($laData['CESTTES'] >= 'I' and $laData['CESTTES'] <= 'J'){   
          $laCodJur = $this->mxDecanoJuradoTesis($p_oSql);  
          if (count($laCodJur) != 3 and $laData['CNIVEL'] != '04'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE TRES JURADOS DEFINIDOS';  
             return false;  
          } else if (count($laCodJur) != 5 and $laData['CNIVEL'] == '04'){  
             $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE CINCO JURADOS DEFINIDOS';  
             return false;  
          }  
          $laData['ACODJUR'] = $laCodJur;  
       }  
       $this->paData = $laData;  
       return true;  
   }   
  
   # Jurado de tesis  
   protected function mxDecanoJuradoTesis($p_oSql){  
       $laArray = null;  
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), C.cDescri  
                  FROM T01DDOC A  
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc  
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo  
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'D' AND A.cEstado = 'A' ORDER BY A.cCodDoc";  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($laFila = $p_oSql->fetch($R1)) {   
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3];  
           $laArray[] = ['CCODDOC'=> $laFila[0], 'CNOMDOC'=> str_replace('/', ' ', $laFila[1]), 'TDECRET'=> $laFila[2], 'TDICTAM'=> $ltDictam, 'CCARGO'=> $laFila[4]];  
       }  
       return $laArray;  
   }  
  
   # Dictaminadores borrador tesis  
   protected function mxDecanoDictaminadoresBDT($p_oSql){  
       $laCodDic = null;  
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI')  
                  FROM T01DDOC A  
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc  
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'C' AND A.cEstado = 'A' ORDER BY A.cCodDoc";  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($laFila = $p_oSql->fetch($R1)) {  
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3];  
           $laCodDic[] = ['CCODDOC' => $laFila[0], 'CNOMDOC' => str_replace('/', ' ', $laFila[1]), 'TDECRET' => $laFila[2], 'TDICTAM' => $ltDictam, 'DFECHOR' => $laFila[4]];  
       }  
       return $laCodDic;  
   }  
  
   # Dictaminadores proyecto de tesis  
   protected function mxDecanoDictaminadoresPDT($p_oSql){  
       $laCodDoc = null;  
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI')  
                  FROM T01DDOC A  
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc  
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'A' AND A.cEstado = 'A' ORDER BY A.cCodDoc";  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($laFila = $p_oSql->fetch($R1)) {  
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3];  
           $laCodDoc[] = ['CCODDOC' => $laFila[0], 'CNOMDOC' => str_replace('/', ' ', $laFila[1]), 'TDECRET' => $laFila[2], 'TDICTAM' => $ltDictam, 'DFECHOR' => $laFila[4]];  
       }  
       return $laCodDoc;  
   }  
  
   # Asesor de tesis  
   protected function mxDecanoAsesorTesis($p_oSql){  
       $laCodAse = null;  
       $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI')  
                  FROM T01DDOC A  
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc  
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'B' AND A.cEstado = 'A' ORDER BY A.cCodDoc";  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($laFila = $p_oSql->fetch($R1)) {  
           $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3];  
           $laCodAse[] = ['CCODDOC'=> $laFila[0], 'CNOMDOC'=> str_replace('/', ' ', $laFila[1]), 'TDECRET'=> $laFila[2], 'TDICTAM'=> $ltDictam, 'DFECHOR'=> $laFila[4]];  
       }  
       return $laCodAse;  
    } 

    //-------------------------------------------------------- 
   // BANDEJA DE DECANO (INGENIERIAS) 
   // 2020-08-17 FLC 
   //-------------------------------------------------------- 
   public function omBandejaDecano(){ 
      $llOk = $this->mxValBandejaDecano(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxBandejaDecano($loSql); 
      if (!$llOk) { 
         $loSql->omDisconnect(); 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValBandejaDecano(){ 
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) { 
         $this->pcError = 'CODIGO DE USUARIO INV�?LIDO O NO DEFINIDO'; 
         return false; 
      }       
      return true; 
   } 
 
   protected function mxBandejaDecano($p_oSql){ 
      //$lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cEstado = 'A'"; 
      $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000' AND cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql); 
      $lcTmp = false; 
      while ($RS = $p_oSql->fetch($R1)) { 
         if (in_array($RS[0], ['05C'])) { 
            $lcTmp = true; 
         } 
      } 
      if (!$lcTmp) { 
         $this->pcError = 'USUARIO NO ES DECANO'; 
         return false; 
      } 
      // pendientes de asignacion  
      $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, F.cDescri FROM T01DALU A  
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi  
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca  
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu  
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '143'
                  WHERE B.cEstTes IN ('B','D','F','I') AND B.cEstado != 'X' AND B.cEstDec = 'P' ORDER BY D.cNombre";  
       #print lcSql  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($laFila = $p_oSql->fetch($R1)) {   
           # Valida si usuario accede a unidad academica de tesis  
           if ($this->laUniAca[0] == '*'){}  
           else if (!in_array($laFila[4], ['74','71','44','4F','4E','73','4K','4A','D1'])){  
              continue;  
           }  
           $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7];  
           $laData[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3],  
                        'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst, 'CTIPO' => $laFila[8]];  
      }  
      $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo FROM T01DALU A  
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi  
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca  
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu  
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes  
                  WHERE B.cEstTes NOT IN ('A','C','E','H','K','N') AND B.cEstado != 'X' AND B.cEstDec = 'A' ORDER BY D.cNombre";  
       $R1 = $p_oSql->omExec($lcSql);  
       while ($laFila = $p_oSql->fetch($R1)) {   
           # Valida si usuario accede a unidad academica de tesis  
           if ($this->laUniAca[0] == '*'){}  
           else if (!in_array($laFila[4], ['74','71','44','4F','4E','73','4K','4A','D1'])){  
              continue;  
           }  
           $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7];  
           $laDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3],  
                         'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst, 'CTIPO' => $laFila[8]];  
       }  
       $this->paData  = $laData;  
       $this->paDatos = $laDatos;  
      return true; 
   }  

   # --------------------------------------------------  
   # ACTUALIZAR EL TITULO DE TESIS
   # 2020-11-24 FLC Creacion  
   # --------------------------------------------------  
   public function omGrabarActualizacionTitulo(){  
      $llOk = $this->mxValParamGrabarActualizacionTitulo();  
       if (!$llOk){  
          return false;  
       }  
       $loSql = new CSql();   
       $llOk = $loSql->omConnect();  
       if (!$llOk){  
          $this->pcError = $loSql->pcError;  
          return false;  
       }  
       $llOk = $this->mxGrabarActualizacionTitulo($loSql);  
       $loSql->omDisconnect();  
       return $llOk;  
   }  
  
   protected function  mxValParamGrabarActualizacionTitulo(){ 
      $lcTitulo = str_replace(' ', '', $this->paData['MTITULO']);  
      if (!isset($lcTitulo) || empty($lcTitulo) || preg_match("([\'\"\&])", $lcTitulo)) {  
         $this->pcError = 'TITULO DE LA TESIS NO DEFINIDO, INVALIDO O CONTIENE CARACTERES ESPECIALES';  
         return false;  
      }  
      $this->paData['MTITULO'] = strtoupper($this->paData['MTITULO']);   
       return true;  
   }  
  
   protected function mxGrabarActualizacionTitulo($p_oSql){  
      $lcSql = "UPDATE T01MTES SET mTitulo = '{$this->paData['MTITULO']}', tModifi = NOW() WHERE CIDTESI = '{$this->paData['CIDTESI']}'";   
      $llOk = $p_oSql->omExec($lcSql);   
      if (!$llOk) {  
         $this->pcError = 'ERROR AL CAMIAR EL TITULO, NO INCLUIR CARACTERES ESPECIALES';  
         return false;  
      }  
      $lcSql = "INSERT INTO T01DLOG(cIdTesi,cEstTes,mObserv,cEstado,cEstPro,cUsuCod,tModifi)values   
                     ('{$this->paData['CIDTESI']}','{$this->paData['CESTTES']}','EL ALUMNO HIZO EL CAMBIO DE TITULO DE TESIS','A','N','9999',NOW())";   
      $llOk = $p_oSql->omExec($lcSql);    
      if (!$llOk) {   
         $this->pcError = "**ERROR AL CAMIAR EL TITULO, NO INCLUIR CARACTERES ESPECIALES**";     
         return false;   
      } 
      return true;  
   } 

   # -------------------------------------------------------------------------
   # INIT REPORTE DE RENDIMIENTO DE TESIS PRESENTADAS- ESCUELAS PROFESIONALES
   # 2021-09-20 APR Creaci�n
   # -------------------------------------------------------------------------
   public function omInitRendimientoTesisPresentadas() {
      $llOk = $this->mxValInitRendimientoTesisPresentadas();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRendimientoTesisPresentadas($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValInitRendimientoTesisPresentadas() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INV�LIDO O NO EXISTE';
         return false;
      }      
      return true;
   }
   
   protected function mxInitRendimientoTesisPresentadas($p_oSql) {
      if (in_array($this->paData['CUSUCOD'], ['1051','2144','1099','2682'])) { 
         //CARGAR UNIDADES ACADEMICAS ASIGNADAS A CODIGO DE USUARIO
         $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cCenCos = '08M'";
         $R1 = $p_oSql->omExec($lcSql);
         if ($R1 == false) {
            $this->pcError = "ERROR AL CARGAR UNIDADES ACADEMICAS";
            return false;
         } elseif ($p_oSql->pnNumRow == 0) {
            $this->pcError = "NO TIENE UNIDADES ACADEMICAS ASOCIADAS, SELECCIONE EL CENTRO DE COSTO CORRECTO";
            return false;
         }
         while ($laFila = $p_oSql->fetch($R1)){
            $this->paUniAca[] = ['CUNIACA' => 'PO', 'CNOMUNI' => $laFila[1]];
         }
      } else {
         //CARGAR UNIDADES ACADEMICAS ASIGNADAS A CODIGO DE USUARIO
         $lcSql = "SELECT DISTINCT B.cUniaca, B.cDescri FROM V_S01PCCO A 
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                     WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cUniAca != '00'";
         $R1 = $p_oSql->omExec($lcSql);
         if ($R1 == false) {
            $this->pcError = "ERROR AL CARGAR UNIDADES ACADEMICAS";
            return false;
         } elseif ($p_oSql->pnNumRow == 0) {
            $this->pcError = "NO TIENE UNIDADES ACADEMICAS ASOCIADAS, SELECCIONE EL CENTRO DE COSTO CORRECTO";
            return false;
         }
         while ($laFila = $p_oSql->fetch($R1)){
            $this->paUniAca[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1]];
         }
      }
      return true;
   }

   # -------------------------------------------------- 
   # ESTADISTICAS DE TESIS SEGUN LA ESCUELA PROFESIONAL 
   # 2021-09-20 APR Creacion 
   # -------------------------------------------------- 
   public function omInitEstadisticasTesisEscuelaProfesional(){ 
      $llOk = $this->mxValInitEstadisticasTesisEscuelaProfesional(); 
      if (!$llOk){ 
         return false; 
      } 
      $loSql = new CSql();  
      $llOk = $loSql->omConnect(); 
      if (!$llOk){ 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxInitEstadisticasTesisEscuelaProfesional($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function  mxValInitEstadisticasTesisEscuelaProfesional(){ 
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INV�LIDO";
         return false;
      } elseif (!isset($this->paData['CUNIACA']) || strlen(trim($this->paData['CUNIACA'])) != 2) {
         $this->pcError = "UNIDAD ACAD�MICA INV�LIDA";
         return false;
      } elseif (!isset($this->paData['DINICIO']) || !$loDate->mxValDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!isset($this->paData['DFINALI']) || !$loDate->mxValDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL ES MENOR QUE FECHA DE INICIO';
         return false;
      } 
      return true; 
   } 
 
   protected function mxInitEstadisticasTesisEscuelaProfesional($p_oSql){  
      #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA
      if ($this->paData['CUNIACA'] == 'PO') {
         #VERIFICAR LA CANTIDAD DE TESIS PRESENTADAS
         $lcSql = "SELECT COUNT(cIdTesi) FROM T01MTES WHERE cEstado <> 'X' AND cTipo IN ('M0', 'D0') AND cEstTes IN ('A','B','C','D','E','F','G','H','I','J','K','L')";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);   
         #VERIFICAR LA CANTIDAD DE SUSTENTACIONES REALIZADAS
         $lcSql = "SELECT COUNT(cIdTesi) FROM T01MTES WHERE cEstado <> 'X' AND cTipo IN ('M0', 'D0') AND cEstTes IN ('J','K')";
         $R1 = $p_oSql->omExec($lcSql); 
         $laFila1 = $p_oSql->fetch($R1);
         $this->paEstAdi = ['CCANTES' => $laFila[0], 'CCANSUS' => $laFila1[0]];
         #VERIFICAR TODOS LOS DOCENTES DE UNIDAD ACADEMICA 
         $lcSql = "SELECT C.cNroDni, C.cNombre FROM T01MTES A 
                     INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                     WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'
                     GROUP BY C.cNroDni, C.cNombre
                     ORDER BY C.cNombre";
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laFila = $p_oSql->fetch($R1)){  
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R2 = $p_oSql->omExec($lcSql); 
            $laFila1 = $p_oSql->fetch($R2); 
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'A' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R3 = $p_oSql->omExec($lcSql); 
            $laFila2 = $p_oSql->fetch($R3);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'A' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R4 = $p_oSql->omExec($lcSql); 
            $laFila3 = $p_oSql->fetch($R4);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS OBSERVADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'A' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R5 = $p_oSql->omExec($lcSql); 
            $laFila4 = $p_oSql->fetch($R5); 
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'B' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R6 = $p_oSql->omExec($lcSql); 
            $laFila5 = $p_oSql->fetch($R6);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'B' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R7 = $p_oSql->omExec($lcSql); 
            $laFila6 = $p_oSql->fetch($R7);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'B' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R8 = $p_oSql->omExec($lcSql); 
            $laFila7 = $p_oSql->fetch($R8);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS OBSERVADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'B' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R9 = $p_oSql->omExec($lcSql); 
            $laFila8 = $p_oSql->fetch($R9);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'C' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R10 = $p_oSql->omExec($lcSql); 
            $laFila9 = $p_oSql->fetch($R10);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'C' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R11 = $p_oSql->omExec($lcSql); 
            $laFila10 = $p_oSql->fetch($R11);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'C' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R12 = $p_oSql->omExec($lcSql); 
            $laFila11 = $p_oSql->fetch($R12);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS OBSERVADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'C' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R13 = $p_oSql->omExec($lcSql); 
            $laFila12 = $p_oSql->fetch($R13);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'D' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R14 = $p_oSql->omExec($lcSql); 
            $laFila13 = $p_oSql->fetch($R14);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'D' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R15 = $p_oSql->omExec($lcSql); 
            $laFila14 = $p_oSql->fetch($R15);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'D' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R16 = $p_oSql->omExec($lcSql); 
            $laFila15 = $p_oSql->fetch($R16);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cTipo IN ('M0', 'D0') AND B.cCatego = 'D' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R17 = $p_oSql->omExec($lcSql); 
            $laFila16 = $p_oSql->fetch($R17);
            #TRAER TODOS LOS PENDIENTES DE LOS DOCENTES
            $lcSql = "SELECT B.cIdTesi, E.cNombre, B.cCatego, TO_CHAR(B.tdecret, 'YYYY-MM-DD HH24:MI'), NOW()::DATE - B.tDecret::DATE FROM V_A01MDOC A 
                        INNER JOIN T01DDOC B ON B.cCodDoc = A.cCodDoc 
                        INNER JOIN T01DALU C ON C.cIdTesi = B.cIdTesi
                        INNER JOIN T01MTES D ON D.cIdTesi = C.cIdTesi
                        INNER JOIN V_A01MALU E ON E.cCodAlu = C.cCodAlu
                        WHERE A.cNroDni = '$laFila[0]' AND D.cTipo IN ('M0', 'D0') AND B.cResult = 'P' AND C.cEstado <> 'X' AND B.cEstado <> 'X' ORDER BY B.cCatego, B.cIdTesi";
            $R18 = $p_oSql->omExec($lcSql); 
            $paTesPen = null;
            while ($laFila17 = $p_oSql->fetch($R18)) {
               if ($laFila17[2] == 'A'){
                  $lcCatego = 'PROYECTO DE TESIS';
               } elseif ($laFila17[2] == 'B'){
                  $lcCatego = 'ASESOR DE TESIS';
               } elseif ($laFila17[2] == 'C'){
                  $lcCatego = 'BORRADOR DE TESIS';
               } elseif ($laFila17[2] == 'D'){
                  $lcCatego = 'JURADO DE TESIS';
               } 
               $paTesPen[] = ['CIDTESI' => $laFila17[0], 'CNOMBRE' => str_replace('/', ' ', $laFila17[1]), 'CCATEGO' => $lcCatego,
                              'TDECRET' => $laFila17[3], 'CDIAPEN' => $laFila17[4]];
            }
            $laFila17 = $p_oSql->fetch($R18);
            $this->paDatos[] = ['CNRODNI' => $laFila[0],  'CNOMBRE' => str_replace('/', ' ', $laFila[1]),     'CTOTPDT' => $laFila1[0], 
                              'CAPRPDT' => $laFila2[0], 'CPENPDT' => $laFila3[0], 'COBRPDT' => $laFila4[0], 'CTOTASE' => $laFila5[0],
                              'CAPRASE' => $laFila6[0], 'CPENASE' => $laFila7[0], 'COBRASE' => $laFila8[0], 'CTOTBRT' => $laFila9[0],
                              'CAPRBRT' => $laFila10[0],'CPENBRT' => $laFila11[0],'COBRBRT' => $laFila12[0],'CTOTSUS' => $laFila13[0],
                              'CAPRSUS' => $laFila14[0],'CPENSUS' => $laFila15[0],'COBRSUS' => $laFila16[0],'CTESPEN' => $paTesPen];  
         }
      } else {
         #VERIFICAR LA CANTIDAD DE TESIS PRESENTADAS
         $lcSql = "SELECT COUNT(cIdTesi) FROM T01MTES WHERE cEstado <> 'X' AND cUniaca = '{$this->paData['CUNIACA']}' AND cEstTes IN ('A','B','C','D','E','F','G','H','I','J','K','L')";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);   
         #VERIFICAR LA CANTIDAD DE SUSTENTACIONES REALIZADAS
         $lcSql = "SELECT COUNT(cIdTesi) FROM T01MTES WHERE cEstado <> 'X' AND cUniaca = '{$this->paData['CUNIACA']}' AND cEstTes IN ('J','K')";
         $R1 = $p_oSql->omExec($lcSql); 
         $laFila1 = $p_oSql->fetch($R1);
         $this->paEstAdi = ['CCANTES' => $laFila[0], 'CCANSUS' => $laFila1[0]];
         #VERIFICAR TODOS LOS DOCENTES DE UNIDAD ACADEMICA 
         $lcSql = "SELECT C.cNroDni, C.cNombre FROM T01MTES A 
                     INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                     INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                     WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'
                     GROUP BY C.cNroDni, C.cNombre
                     ORDER BY C.cNombre";
         $R1 = $p_oSql->omExec($lcSql); 
         while ($laFila = $p_oSql->fetch($R1)){  
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R2 = $p_oSql->omExec($lcSql); 
            $laFila1 = $p_oSql->fetch($R2); 
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'A' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R3 = $p_oSql->omExec($lcSql); 
            $laFila2 = $p_oSql->fetch($R3);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'A' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R4 = $p_oSql->omExec($lcSql); 
            $laFila3 = $p_oSql->fetch($R4);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - PROYECTO/PLAN DE TESIS OBSERVADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'A' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R5 = $p_oSql->omExec($lcSql); 
            $laFila4 = $p_oSql->fetch($R5); 
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'B' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R6 = $p_oSql->omExec($lcSql); 
            $laFila5 = $p_oSql->fetch($R6);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'B' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R7 = $p_oSql->omExec($lcSql); 
            $laFila6 = $p_oSql->fetch($R7);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'B' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R8 = $p_oSql->omExec($lcSql); 
            $laFila7 = $p_oSql->fetch($R8);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - ASESOR DE TESIS OBSERVADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'B' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R9 = $p_oSql->omExec($lcSql); 
            $laFila8 = $p_oSql->fetch($R9);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'C' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R10 = $p_oSql->omExec($lcSql); 
            $laFila9 = $p_oSql->fetch($R10);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'C' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R11 = $p_oSql->omExec($lcSql); 
            $laFila10 = $p_oSql->fetch($R11);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'C' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R12 = $p_oSql->omExec($lcSql); 
            $laFila11 = $p_oSql->fetch($R12);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - BORRADOR DE TESIS OBSERVADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'C' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R13 = $p_oSql->omExec($lcSql); 
            $laFila12 = $p_oSql->fetch($R13);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS TOTALES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'D' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R14 = $p_oSql->omExec($lcSql); 
            $laFila13 = $p_oSql->fetch($R14);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'D' AND B.cResult = 'A' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R15 = $p_oSql->omExec($lcSql); 
            $laFila14 = $p_oSql->fetch($R15);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS PENDIENTES
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'D' AND B.cResult = 'P' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R16 = $p_oSql->omExec($lcSql); 
            $laFila15 = $p_oSql->fetch($R16);
            #ESTADISTICAS DE LOS DOCENTES DE LA UNIDAD ACADEMICA - JURADO DE SUSTENTACION DE TESIS APROBADOS
            $lcSql = "SELECT COUNT(*) FROM T01MTES A 
                        INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                        WHERE B.cEstado <> 'X' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND B.cCatego = 'D' AND B.cResult = 'O' AND C.cNroDni = '$laFila[0]' AND B.tDecret::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}'";
            $R17 = $p_oSql->omExec($lcSql); 
            $laFila16 = $p_oSql->fetch($R17);
            #TRAER TODOS LOS PENDIENTES DE LOS DOCENTES
            $lcSql = "SELECT A.cIdTesi, C.cNombre, D.cCatego, TO_CHAR(D.tDecret, 'YYYY-MM-DD HH24:MI'), NOW()::DATE - D.tDecret::DATE FROM T01MTES A
                        INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
                        INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                        INNER JOIN T01DDOC D ON D.cIdTesi = B.cIdTesi
                        INNER JOIN V_A01MDOC E ON E.cCodDoc = D.cCodDoc
                        WHERE E.cNroDni = '$laFila[0]' AND A.cUniaca = '{$this->paData['CUNIACA']}' AND D.cResult = 'P' AND B.cEstado <> 'X' AND D.cEstado <> 'X' ORDER BY D.cCatego, D.cIdTesi";
            $R18 = $p_oSql->omExec($lcSql); 
            $paTesPen = null;
            while ($laFila17 = $p_oSql->fetch($R18)) {
               if ($laFila17[2] == 'A'){
                  $lcCatego = 'PROYECTO DE TESIS';
               } elseif ($laFila17[2] == 'B'){
                  $lcCatego = 'ASESOR DE TESIS';
               } elseif ($laFila17[2] == 'C'){
                  $lcCatego = 'BORRADOR DE TESIS';
               } elseif ($laFila17[2] == 'D'){
                  $lcCatego = 'JURADO DE TESIS';
               } 
               $paTesPen[] = ['CIDTESI' => $laFila17[0], 'CNOMBRE' => str_replace('/', ' ', $laFila17[1]), 'CCATEGO' => $lcCatego,
                              'TDECRET' => $laFila17[3], 'CDIAPEN' => $laFila17[4]];
            }
            $laFila17 = $p_oSql->fetch($R18);
            $this->paDatos[] = ['CNRODNI' => $laFila[0],  'CNOMBRE' => str_replace('/', ' ', $laFila[1]),     'CTOTPDT' => $laFila1[0], 
                                'CAPRPDT' => $laFila2[0], 'CPENPDT' => $laFila3[0], 'COBRPDT' => $laFila4[0], 'CTOTASE' => $laFila5[0],
                                'CAPRASE' => $laFila6[0], 'CPENASE' => $laFila7[0], 'COBRASE' => $laFila8[0], 'CTOTBRT' => $laFila9[0],
                                'CAPRBRT' => $laFila10[0],'CPENBRT' => $laFila11[0],'COBRBRT' => $laFila12[0],'CTOTSUS' => $laFila13[0],
                                'CAPRSUS' => $laFila14[0],'CPENSUS' => $laFila15[0],'COBRSUS' => $laFila16[0],'CTESPEN' => $paTesPen];   
         }
      } 
      return true; 
   }

   # -----------------------------------------------------------
   # INIT LIBRO DE ACTAS DE SUSTENTACIONES VIRTUALES REALIZADAS 
   # 2022-01-26 APR Creacion
   # -----------------------------------------------------------
   public function omInitInformacionLibroDeActasDeSustentacion() {
      $llOk = $this->mxValInformacionLibroDeActasDeSustentacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitInformacionLibroDeActasDeSustentacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValInformacionLibroDeActasDeSustentacion() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO O NO EXISTE';
         return false;
      }      
      return true;
   }
   
   protected function mxInitInformacionLibroDeActasDeSustentacion($p_oSql) {
      if (in_array($this->paData['CUSUCOD'], ['1051','2144','1099','2682','3285'])) { 
         //CARGAR UNIDADES ACADEMICAS ASIGNADAS A CODIGO DE USUARIO
         $lcSql = "SELECT cUniaca, cNomUni FROM S01TUAC
                     WHERE cNivel IN ('03','04') AND cEstado = 'A' ORDER BY cNomUni";
         $R1 = $p_oSql->omExec($lcSql);
         if ($R1 == false) {
            $this->pcError = "ERROR AL CARGAR UNIDADES ACADEMICAS";
            return false;
         } elseif ($p_oSql->pnNumRow == 0) {
            $this->pcError = "NO TIENE UNIDADES ACADEMICAS ASOCIADAS, SELECCIONE EL CENTRO DE COSTO CORRECTO";
            return false;
         }
         while ($laFila = $p_oSql->fetch($R1)){
            $this->paUniAca[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1]];
         }
      } else {
         //CARGAR UNIDADES ACADEMICAS ASIGNADAS A CODIGO DE USUARIO
         $lcSql = "SELECT DISTINCT B.cUniaca, B.cDescri FROM V_S01PCCO A 
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                     WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cUniAca != '00'";
         $R1 = $p_oSql->omExec($lcSql);
         if ($R1 == false) {
            $this->pcError = "ERROR AL CARGAR UNIDADES ACADEMICAS";
            return false;
         } elseif ($p_oSql->pnNumRow == 0) {
            $this->pcError = "NO TIENE UNIDADES ACADEMICAS ASOCIADAS, SELECCIONE EL CENTRO DE COSTO CORRECTO";
            return false;
         }
         while ($laFila = $p_oSql->fetch($R1)){
            $this->paUniAca[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1]];
         }
      }
      return true;
   }

   #------------------------------------------------------------------
   # Recuperar Especialización de Unidad Académica - Trámite de Tesis
   # Creacion APR 2021-01-26
   #------------------------------------------------------------------
   public function omRecuperarEspecializacionUnidadAcademica(){
      $llOk = $this->mxValRecuperarEspecializacionUnidadAcademica();
      if (!$llOk) {
         return false;
      }
       $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRecuperarEspecializacionUnidadAcademica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxValRecuperarEspecializacionUnidadAcademica(){
      if (!isset($this->paData['CUNIACA']) || strlen($this->paData['CUNIACA']) != 2) {
         $this->pcError = 'UNIDAD ACADÉMICA INVÁLIDA O NO DEFINIDA';
         return false;
      } 
      return true;
   }

   protected function mxRecuperarEspecializacionUnidadAcademica($p_oSql){
      //RECUPERAR ESPECIALIZACIÓN DE UNIDAD ACADÉMICA.
      $lcSql = "SELECT cPrefij, cDescri FROM S01DLAV WHERE cUniaca = '{$this->paData['CUNIACA']}' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCIÓN DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "UNIDAD ACADÉMICA NO TIENE ESPECIALIZACIONES DEFINIDAS EN EL SISTEMA.";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CPREFIJ' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   #-----------------------------------------------------------
   # Recuperar Modalidades en Sustentación de Unidad Académica 
   # Creacion APR 2021-01-26
   #-----------------------------------------------------------
   public function omRecuperarModalidadesUnidadAcademica(){
      $llOk = $this->mxValRecuperarModalidadesUnidadAcademica();
      if (!$llOk) {
         return false;
      }
       $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRecuperarModalidadesUnidadAcademica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxValRecuperarModalidadesUnidadAcademica(){
      if (!isset($this->paData['CUNIACA']) || strlen($this->paData['CUNIACA']) != 2) {
         $this->pcError = 'UNIDAD ACADÉMICA INVÁLIDA O NO DEFINIDA';
         return false;
      } elseif (!isset($this->paData['CPREFIJ']) || strlen($this->paData['CPREFIJ']) != 1) {
         $this->pcError = 'ESPECIALIZACION INVÁLIDA O NO DEFINIDA';
         return false;
      } 
      return true;
   }

   protected function mxRecuperarModalidadesUnidadAcademica($p_oSql){
      //RECUPERAR MODALIDADES DE UNIDAD ACADÉMICA EN SUSTENTACIONES.
      $lcSql = "SELECT DISTINCT TRIM(B.cCodigo), B.cDescri FROM T02MLIB A 
                  INNER JOIN V_S01TTAB B ON B.cCodigo = A.cTipo AND B.cCodTab = '143' 
                  WHERE A.cUniaca = '{$this->paData['CUNIACA']}' AND A.cPrefij = '{$this->paData['CPREFIJ']}'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCIÓN DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "UNIDAD ACADÉMICA NO TIENE ESPECIALIZACIONES DEFINIDAS EN EL SISTEMA.";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }
   
   # ------------------------------------------------------
   # ESTUDIANTES CON ACTAS GENERADAS SEGUN NÚMERO DE FOLIO 
   # 2022-01-24 APR Creacion 
   # ------------------------------------------------------
   public function omInitEstadisticasActasGeneradas(){ 
      $llOk = $this->mxValInitEstadisticasActasGeneradas(); 
      if (!$llOk){ 
         return false; 
      } 
      $loSql = new CSql();  
      $llOk = $loSql->omConnect(); 
      if (!$llOk){ 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxInitEstadisticasActasGeneradas($loSql); 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function  mxValInitEstadisticasActasGeneradas(){ 
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO O NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUNIACA']) || strlen(trim($this->paData['CUNIACA'])) != 2) {
         $this->pcError = "UNIDAD ACADEMICA INVALIDA O NO DEFINIDA";
         return false;
      } elseif (!isset($this->paData['CPREFIJ']) || strlen(trim($this->paData['CPREFIJ'])) != 1) {
         $this->pcError = "ESPECIALIZACION INVALIDA O NO DEFINIDA";
         return false;
      } elseif (!isset($this->paData['CMODALI']) || strlen(trim($this->paData['CMODALI'])) != 2) {
         $this->pcError = "MODALIDAD INVALIDA O NO DEFINIDA";
         return false;
      } elseif (!isset($this->paData['DINICIO']) || !$loDate->mxValDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA O NO DEFINIDA';
         return false;
      } elseif (!isset($this->paData['DFINALI']) || !$loDate->mxValDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA O NO DEFINIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL ES MENOR QUE FECHA DE INICIO';
         return false;
      } 
      return true; 
   } 
 
   protected function mxInitEstadisticasActasGeneradas($p_oSql){
      $paDatAca = array('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9');
      if(in_array($this->paData['CUNIACA'], $paDatAca)) {
         #VERIFICAR TODAS LAS ACTAS GENERADAS DE UNA FECHA A OTRA
         $lcSql = "SELECT B.cFolio, A.cIdTesi, C.cCodAlu, D.cNombre, B.tIniSus
                     FROM T01MTES A 
                     INNER JOIN T02MLIB   B ON B.cIdTesi = A.cIdTesi 
                     INNER JOIN T01DALU   C ON C.cIdTesi = A.cIdTesi 
                     INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu
                     INNER JOIN S01TUAC   E ON E.cUniAca = A.cUniAca
                     WHERE A.cEstPro IN ('K', 'R') AND B.cPrefij = '{$this->paData['CPREFIJ']}' AND B.cUniaca IN ('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9') 
                        AND B.cTipo = '{$this->paData['CMODALI']}' AND B.tIniSus::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' ORDER BY CFOLIO";
      } else {
         #VERIFICAR TODAS LAS ACTAS GENERADAS DE UNA FECHA A OTRA
         $lcSql = "SELECT B.cFolio, A.cIdTesi, C.cCodAlu, D.cNombre, B.tIniSus
                     FROM T01MTES A 
                     INNER JOIN T02MLIB   B ON B.cIdTesi = A.cIdTesi 
                     INNER JOIN T01DALU   C ON C.cIdTesi = A.cIdTesi 
                     INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu
                     INNER JOIN S01TUAC   E ON E.cUniAca = A.cUniAca
                     WHERE A.cEstPro IN ('K', 'R') AND B.cPrefij = '{$this->paData['CPREFIJ']}' AND B.cUniaca = '{$this->paData['CUNIACA']}' 
                        AND B.cTipo = '{$this->paData['CMODALI']}' AND B.tIniSus::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' ORDER BY CFOLIO";
      }
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)){  
         $this->paDatos[] = ['CFOLIO'  => $laFila[0], 'CIDTESI' => $laFila[1], 'CCODALU' => $laFila[2], 'CNOMBRE' => str_replace('/', ' ', $laFila[3]), 
                             'TINISUS' => $laFila[4]];  
      }
      return true; 
   }

   # ----------------------------------------------- 
   # BANDEJA DE PROYECTOS/BORRADORES PARA ANULACIÓN  
   # 2022-05-20 APR Creacion 
   # ----------------------------------------------- 
   public function omConsultarTesisEscuelaProfesional() {  
      $llOk = $this->mxValParam();  
      if (!$llOk) {  
         return false;  
      }  
      $loSql = new CSql();  
      $llOk  = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk  = $this->mxValUsuario($loSql);  
      if (!$llOk) {  
         $loSql->omDisconnect();  
         return false;  
      }  
      $llOk = $this->mxConsultarTesisEscuelaProfesional($loSql);  
      $loSql->omDisconnect();  
      if (!$llOk) {  
         return false;  
      }  
      return $llOk;  
   }   
  
   protected function mxConsultarTesisEscuelaProfesional($p_oSql) {  
      $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo, F.cDescri, TO_CHAR(B.dEntreg, 'YYYY-MM-DD HH24:mm') FROM T01DALU A 
                  INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                  INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                  INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '143' AND F.cCodigo = B.cTipo 
                  WHERE B.cEstTes NOT IN ('H','I','J','K','N') AND B.cEstado != 'X' ORDER BY D.cNombre";
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)) {  
         # Valida si usuario accede a unidad academica de tesis 
         if ($this->laUniAca[0] == '*'){} 
         else if (!in_array($laFila[4], $this->laUniAca)){ 
            continue; 
         } 
         $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1],  'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 
                              'MTITULO' => $laFila[3], 'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 
                              'CDESEST' => $lcDesEst,  'CTIPO'   => $laFila[8], 'CMODALI' => $laFila[9], 'DENTREG' => $laFila[10]]; 
      } 
      return true;
   }


   # -----------------------------------------------
   # BANDEJA DE SEGUIMIENTO DE TESIS
   # 2023-01-15 APR Creacion
   # -----------------------------------------------
   public function omInit3170() {  
   $llOk = $this->mxValParam();  
   if (!$llOk) {  
      return false;  
   }  
   $loSql = new CSql();  
   $llOk  = $loSql->omConnect();  
   if (!$llOk) {  
      $this->pcError = $loSql->pcError;  
      return false;  
   }  
   $llOk  = $this->mxValUsuario($loSql);  
   if (!$llOk) {  
      $loSql->omDisconnect();  
      return false;  
   }  
   $llOk = $this->mxInit3170($loSql);  
   $loSql->omDisconnect();  
   if (!$llOk) {  
      return false;  
   }  
   return $llOk;  
   }   
 
   protected function mxInit3170($p_oSql) {  
   $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo, F.cDescri, TO_CHAR(B.dEntreg, 'YYYY-MM-DD HH24:mm') FROM T01DALU A
               INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
               INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca
               INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu
               LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes
               LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '143' AND F.cCodigo = B.cTipo
               WHERE B.cEstTes NOT IN ('K','X') AND B.cEstado != 'X' ORDER BY D.cNombre";

   $R1 = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($R1)) {  
      # Valida si usuario accede a unidad academica de tesis
      if ($this->laUniAca[0] == '*'){}
      else if (!in_array($laFila[4], $this->laUniAca)){
         continue;
      }
      $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7];
      $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1],  'CNOMBRE' => str_replace('/', ' ', $laFila[2]),
                           'MTITULO' => $laFila[3], 'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6],
                           'CDESEST' => $lcDesEst,  'CTIPO'   => $laFila[8], 'CMODALI' => $laFila[9], 'DENTREG' => $laFila[10]];
   }
   return true;
   }

}
?>
