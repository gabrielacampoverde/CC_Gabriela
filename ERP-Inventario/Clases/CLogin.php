<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once 'Clases/CWebService.php';

class CLogin extends CBase {
   public $paData, $paDatos;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = null;
   }

   // ---------------------------
   // Iniciar Sesion de Alumnos
   // Creacion APR - 2020-08-04
   // ---------------------------
   public function omIniciarSesionAlumnos() {      
      $llOk = $this->mxValIniciarSesionAlumnos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);      
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionAlumnos($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }      
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValIniciarSesionAlumnos() {
      if (!isset($this->paData['CNRODNI']) || strlen(trim($this->paData['CNRODNI'])) != 8) {
         $this->pcError = "NÚMERO DE DNI INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCLAVE']) || strlen(trim($this->paData['CCLAVE'])) == 0) {
         $this->pcError = "CONTRASEÑA INVÁLIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   } 
   
   protected function mxIniciarSesionAlumnos($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_A2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }

   // -----------------------------
   // Iniciar Sesion de Invitados
   // Creacion APR - 2020-08-04
   // -----------------------------
   public function omIniciarSesionInvitados() {      
      $llOk = $this->mxValIniciarSesionInvitados();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);      
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionInvitados($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }      
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValIniciarSesionInvitados() {
      if (!isset($this->paData['CNRODNI']) || strlen(trim($this->paData['CNRODNI'])) != 8) {
         $this->pcError = "NÚMERO DE DNI INVÁLIDO";
         return false;
      }
      return true;
   } 
   
   protected function mxIniciarSesionInvitados($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_INV('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCIÓN DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
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
   
   protected function mxValInicioSesion () {
      if (!isset($this->paData['CNRODNI']) || (strlen(trim($this->paData['CNRODNI'])) != 8 && (strlen(trim($this->paData['CNRODNI'])) < 7 || strlen(trim($this->paData['CNRODNI'])) > 9))) {
         $this->pcError = "NÚMERO DE DNI INVÁLIDO";
         return false;
      } /*elseif (!ctype_digit($this->paData['CNRODNI']) && strlen(trim($this->paData['CNRODNI'])) == 8) {
         $this->pcError = "INGRESAR UN NÚMERO DE DNI VÁLIDO";
         return false;
      } */elseif (!isset($this->paData['CCLAVE']) || strlen(trim($this->paData['CCLAVE'])) == 0) {
         $this->pcError = "CONTRASEÑA INVÁLIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   }
   
   protected function mxIniciarSesion($p_oSql) {
      $lcJson = json_encode($this->paData);
      //echo $lcJson;
      $lcSql = "SELECT P_LOGIN('$lcJson')";
      //echo $lcSql;
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }
   // INICIAR SESION EVENTOS
   public function omIniciarSesionEventos() {
      $llOk = $this->mxValParamIniciarSesionEventos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionEventos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamIniciarSesionEventos() {
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (!ctype_digit($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
         $this->pcError = "CONTRASEÑA NO DEFINIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   }
   
   protected function mxIniciarSesionEventos($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGINE('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }

   // Iniciar sesion proveedores
   public function omIniciarSesionProveedor() {
      $llOk = $this->mxValParamIniciarSesionProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionProveedor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamIniciarSesionProveedor() {
      if (empty($this->paData['CNRORUC'])) {
         $this->pcError = "RUC NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
        $this->pcError = "CLAVE NO DEFINIDA";
        return false;
      }
      return true;
   }
   
   protected function mxIniciarSesionProveedor($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGINP('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData[0]['ERROR'])) {
         $this->pcError = $this->paData[0]['ERROR'];
         return false; 
      }
      $this->paData = $this->paData[0];
      return true;
   }

    // Iniciar Sesion Plan de Tesis
    public function omIniciarSesionPlanTesis() {
      $llOk = $this->mxValParamIniciarSesionPlanTesis();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionPlanTesis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamIniciarSesionPlanTesis () {
      if(!isset($this->paData['CNRODNI']) || empty(trim($this->paData['CNRODNI']) || $this->paData['CNRODNI'] == null || strlen($this->paData['CNRODNI']) != 8 || !ctype_digit($this->paData['CNRODNI']))) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
         $this->pcError = "CONTRASEÑA NO DEFINIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   }
   
   protected function mxIniciarSesionPlanTesis($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGINPT('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }


   // Iniciar sesion TESORERIA - COBRANZA
   public function omIniciarSesionCobranzaTesoreria() {
      $llOk = $this->mxValIniciarSesionCobranzaTesoreria();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionCobranzaTesoreria($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValIniciarSesionCobranzaTesoreria () {
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (!ctype_digit($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
         $this->pcError = "CONTRASEÑA NO DEFINIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   }
   
   protected function mxIniciarSesionCobranzaTesoreria($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_CT('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR":"ERROR DE EJECUCION EN BASE DE DATOS"}' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false; 
      }
      return true;
   }

   //---------------------------------------------------------------------------
   // INICIO DE SESION PLAN DE TESIS V2
   // 2020-07-14 FLC
   //---------------------------------------------------------------------------
    public function omIniciarSesionPlanTesisV2() {
      $llOk = $this->mxValParamIniciarSesionPlanTesisV2();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionPlanTesisV2($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamIniciarSesionPlanTesisV2 () {
      if(!isset($this->paData['CNRODNI']) || empty(trim($this->paData['CNRODNI']) || $this->paData['CNRODNI'] == null || strlen($this->paData['CNRODNI']) != 8 || !ctype_digit($this->paData['CNRODNI']))) {
         $this->pcError = "INGRESAR NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
         $this->pcError = "CONTRASEÑA NO DEFINIDA";
         return false;
      }
      return true;
   }
   
   protected function mxIniciarSesionPlanTesisV2($p_oSql) {
      $lcSql = "SELECT REPLACE(cNombre,'/',' '), cNroDni FROM S01MPER 
            WHERE cNroDni = '{$this->paData['CNRODNI']}' AND cClaAca = TRIM(ENCODE(DIGEST('{$this->paData['CCLAVE']}', 'sha512'), 'hex'))";
      $R1 = $p_oSql->omExec($lcSql);
      $RS = $p_oSql->fetch($R1);
      if ($RS[0] == '') {
         $this->pcError = '|** USUARIO INVALIDO **|';
         return false;
      }
      $this->paDatos = ['CNOMBRE' => $RS[0], 'CNRODNI' => $RS[1], 'CCODIGO' => null];
      $lcSql = "SELECT cCodAlu, cUniAca, cNomUni 
                FROM V_A01MALU WHERE cnrodni = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos['CCODIGO'][] = ['CCODALU' => $laFila[0],  'CUNIACA' => $laFila[1], 
                             'CNOMUNI' => $laFila[2]];
      }
      return true;
   }

    // Iniciar sesion Docentes invitados
    public function omIniciarSesionDocentesInvitados() {
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
   
   protected function mxValInicioSesionDocentesInvitados() {
      if (!isset($this->paData['CNRODNI']) || strlen(trim($this->paData['CNRODNI'])) != 8) {
         $this->pcError = "NÚMERO DE DNI INVÁLIDO";
         return false;
      } elseif (!ctype_digit($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR UN NÚMERO DE DNI VÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCLAVE']) || strlen(trim($this->paData['CCLAVE'])) == 0) {
         $this->pcError = "CONTRASEÑA INVÁLIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   }
   
   protected function mxIniciarSesionDocentesInvitados($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_DI('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }

   public function omIniciarSesionContratos() {
      $llOk = $this->mxValParamIniciarSesionContratos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionContratos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
  
   protected function mxValParamIniciarSesionContratos() {
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR NÚMERO DE DNI/DOCUMENTO VÁLIDO";
         return false;
      } elseif (!ctype_digit($this->paData['CNRODNI'])) {
         $this->pcError = "INGRESAR NÚMERO DE DNI/DOCUMENTO VÁLIDO";
         return false;
      } elseif (empty($this->paData['CCLAVE'])) {
         $this->pcError = "CONTRASEÑA NO DEFINIDA";
         return false;
      }
      $this->paData['CCLAVE'] = hash('sha512', $this->paData['CCLAVE']);
      return true;
   }
   
   protected function mxIniciarSesionContratos($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }

   // ------------------------------------------------------------------------------
   // INICIAR SESIÓN CON TOKEN PARA SISTEMA INTEGRAL UCSM
   // 2021-11-05 JLF Creacion
   // ------------------------------------------------------------------------------
   public function omIniciarSesionToken() {      
      $llOk = $this->mxValIniciarSesionToken();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();      
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionToken($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValIniciarSesionToken () {
      if (!isset($this->paData['CNRODOC']) || empty(trim($this->paData['CNRODOC'])) || strlen(trim($this->paData['CNRODOC'])) > 20) {
         $this->pcError = "NÚMERO DE DOCUMENTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTIPDOC']) || strlen(trim($this->paData['CTIPDOC'])) != 1) {
         $this->pcError = "TIPO DE DOCUMENTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NIDUSUA']) || !is_numeric($this->paData['NIDUSUA']) || $this->paData['NIDUSUA'] <= 0) {
         $this->pcError = "ID DE USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTOKEN']) || strlen(trim($this->paData['CTOKEN'])) < 400 || strlen(trim($this->paData['CTOKEN'])) > 500) {
         $this->pcError = "TOKEN DE VERIFICACIÓN INVÁLIDO";
         return false;
      }
      // VALIDA TOKEN DE SISTEMA INTEGRADO
      $lo = new CWebService();
      $lo->paData = ['id' => $this->paData['NIDUSUA'], 'token' => $this->paData['CTOKEN']];
      $llOk = $lo->omRevisarTokenInicioSesion();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }
   
   protected function mxIniciarSesionToken($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_SI1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR AL MOMENTO DE INCIAR SESIÓN"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }

   // ------------------------------------------------------------------------------
   // INICIAR SESIÓN DE ALUMNOS CON TOKEN PARA SISTEMA INTEGRAL UCSM
   // 2021-11-10 JLF Creacion
   // ------------------------------------------------------------------------------
   public function omIniciarSesionAlumnosToken() {      
      $llOk = $this->mxValIniciarSesionAlumnosToken();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);      
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionAlumnosToken($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }      
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValIniciarSesionAlumnosToken() {
      if (!isset($this->paData['CNRODOC']) || empty(trim($this->paData['CNRODOC'])) || strlen(trim($this->paData['CNRODOC'])) > 20) {
         $this->pcError = "NÚMERO DE DOCUMENTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTIPDOC']) || strlen(trim($this->paData['CTIPDOC'])) != 1) {
         $this->pcError = "TIPO DE DOCUMENTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NIDUSUA']) || !is_numeric($this->paData['NIDUSUA']) || $this->paData['NIDUSUA'] <= 0) {
         $this->pcError = "ID DE USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTOKEN']) || strlen(trim($this->paData['CTOKEN'])) < 400 || strlen(trim($this->paData['CTOKEN'])) > 500) {
         $this->pcError = "TOKEN DE VERIFICACIÓN INVÁLIDO";
         return false;
      }
      // VALIDA TOKEN DE SISTEMA INTEGRADO
      $lo = new CWebService();
      $lo->paData = ['id' => $this->paData['NIDUSUA'], 'token' => $this->paData['CTOKEN']];
      $llOk = $lo->omRevisarTokenInicioSesion();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   } 
   
   protected function mxIniciarSesionAlumnosToken($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_A3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }

   // ------------------------------------------------------------------------------
   // INICIAR SESIÓN DE PROVEEDORES CON TOKEN PARA SISTEMA INTEGRAL UCSM
   // 2021-11-10 JLF Creacion
   // ------------------------------------------------------------------------------
   public function omIniciarSesionProveedoresToken() {
      $llOk = $this->mxValParamIniciarSesionProveedoresToken();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniciarSesionProveedoresToken($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamIniciarSesionProveedoresToken() {
      if (!isset($this->paData['CNRODOC']) || empty(trim($this->paData['CNRODOC'])) || strlen(trim($this->paData['CNRODOC'])) > 20) {
         $this->pcError = "NÚMERO DE DOCUMENTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTIPDOC']) || strlen(trim($this->paData['CTIPDOC'])) != 1) {
         $this->pcError = "TIPO DE DOCUMENTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NIDUSUA']) || !is_numeric($this->paData['NIDUSUA']) || $this->paData['NIDUSUA'] <= 0) {
         $this->pcError = "ID DE USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTOKEN']) || strlen(trim($this->paData['CTOKEN'])) < 400 || strlen(trim($this->paData['CTOKEN'])) > 500) {
         $this->pcError = "TOKEN DE VERIFICACIÓN INVÁLIDO";
         return false;
      }
      // VALIDA TOKEN DE SISTEMA INTEGRADO
      $lo = new CWebService();
      $lo->paData = ['id' => $this->paData['NIDUSUA'], 'token' => $this->paData['CTOKEN']];
      $llOk = $lo->omRevisarTokenInicioSesion();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }
   
   protected function mxIniciarSesionProveedoresToken($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_LOGIN_SI2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false; 
      }
      return true;
   }
}
?>