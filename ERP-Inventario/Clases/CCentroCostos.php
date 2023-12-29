<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
// require_once "Clases/CSpreadSheet.php";
require_once "class/class.ezpdf.php";
require_once "Libs/fpdf/fpdf.php";

class CCentroCostos extends CBase {
   public $paData, $paDatos, $paUniAca, $paTipo, $paTipEst, $paEstPre, $paCenCos, $paFactor, $paTipDes, $poFile, $pcFilXls, $pcFile;
   protected $laData, $laDatos;
   
   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paUniAca = $this->paTipo = $this->paTipEst = $this->paEstPre = $this->paFactor = $this->paTipDes = $this->poFile = $this->laData = $this->laDatos = $this->pcFilXls = $this->pcFile = null;  
   }
   
   # --------------------------------------------------
   # Validacion de usuario para modulo
   # 2021-02-12 BOL Creacion
   # --------------------------------------------------   
   public function mxValParamUsuario($p_oSql, $p_cModulo = '000'){
       //UNI: Acceso a todo
       $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCencos = '02V' AND 
                  cCodUsu = '{$this->paData['CUSUCOD']}'";
       $RS = $p_oSql->omExec($lcSql);
       $laFila = $p_oSql->fetch($RS);
       if (!isset($laFila[0]) or empty($laFila[0])) {
          ;
       } elseif ($laFila[0] == 'A'){
          return true;
       }
       // Valida que el modulo corresponda 
       $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '$p_cModulo'";
       $RS = $p_oSql->omExec($lcSql);
       $laFila = $p_oSql->fetch($RS);
       if (!isset($laFila[0]) or empty($laFila[0])) {
          $this->pcError = "USUARIO NO TIENE PERMISO PARA OPCION";
          return False;
       } elseif ($laFila[0] != 'A'){
          $this->pcError = "USUARIO NO TIENE PERMISO ACTIVO PARA OPCION";
          return False;
       }
       return True;
   }
   
   public function mxValParam(){
       if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
          $this->pcError = "CODIGO DE USUARIO NO DEFINIDO O INVALIDO";
          return false;
       } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
          $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVALIDO";
          return false;
       } 
       return true;
   }
   
   # --------------------------------------------------
   # Init Mantenimiento Centro de Costos
   # 2020-12-02 BOL Creacion
   # --------------------------------------------------
   public function omInitMntoCenCos() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '00A');// 00A:Mantenimiento CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitMntoCencos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitMntoCencos($p_oSql) {
      // Trae y Valida si usuario tiene permisos para mantenimiento de centros de costo
      $llOk = $this->mxValUsuarioCenCos($p_oSql);
      if (!$llOk) {
         return false;
      }
      //TRAE TABLA DE UNIDAD ACADEMICA
      $lcUniAca = '';
      $lbFirst = true;
      $lcSql = "SELECT cUniAca FROM S01TCCO WHERE cEstado = 'A' OR cUniAca != '00'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($laFila[0] == '00') { 
            continue;
         }
         if ($lbFirst) { 
            $lcUniAca = "'".$laFila[0]."'";
            $lbFirst = false;
         }
         $lcUniAca .= ",'".$laFila[0]."'";
      }
      $lcSql = "SELECT cUniAca, TRIM(cNomUni) FROM S01TUAC WHERE cEstado = 'A' OR cUniACa = '00' AND cUniAca NOT IN ($lcUniAca) ORDER BY cNomUni";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUniAca[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1]];
      }
      if (count($this->paUniAca) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE UNIDAD ACADEMICA";
         return false;
      }
      //TRAE TABLA DE TIPO DE CENTRO DE COSTOS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab='048'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paTipo) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE TIPO [048]";
         return false;
      }
      //TRAE TABLA DE TIPO DE ESTRUCTURA
      $lcSql = "SELECT SUBSTRING(cCodigo,1,2), cDescri FROM V_S01TTAB WHERE cCodTab='308'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipEst[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paTipEst) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE TIPO DE ESTRUCTURA [308]";
         return false;
      }
      //TRAE TABLA DE ESTRUCTURA PRESUPUESTAL
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab='309'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstPre[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paEstPre) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTRUCTURA PRESUPUESTAL [309]";
         return false;
      }
      //TRAE TABLA DE TIPO DE DISTRIBUCION
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab='312' AND cCodigo in ('01', '02', '03', '04') ORDER BY cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipDes[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paTipDes) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE DISTRIBUCION [312]";
         return false;
      }
      return true;
   }
   
   protected function mxValUsuarioCenCos($p_oSql){
      $this->paDatos = [];
      $lcCentro = '';
      $lcSql = "SELECT cCentro FROM S02DCON WHERE cTipo = '002' AND cUsuari like '%{$this->paData['CUSUCOD']}%' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)){
         $lcCentro = $laFila[0];
         if ($lcCentro == '*'){
            // *:Trae todos los centros de costo
            $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri as cEstado FROM S01TCCO A
                LEFT JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
                WHERE LENGTH(A.cClase) <= 7 AND A.cClase != '' AND A.cClase NOT LIKE 'Z%'
                ORDER BY A.cClase";
            $R2 = $p_oSql->omExec($lcSql);
            while ($laFila2 = $p_oSql->fetch($R2)) {
               $this->paDatos[] = ['CCENCOS' => $laFila2[0], 'CDESCRI' => $laFila2[1], 'CNOMUNI' => $laFila2[2], 'CCLASE' => $laFila2[3], 'CESTADO' => $laFila2[4]];
            }
         } else{
            // Trae solo los centros de costo a los que tiene acceso
            $laCentro = explode(',',$lcCentro);
            foreach ($laCentro as $lcCenCos){
               $lcCenCos = trim($lcCenCos);
               $lcSql = "SELECT cClase FROM S01TCCO WHERE cCenCos = '$lcCenCos'";
               $R2 = $p_oSql->omExec($lcSql);
               $laFila2 = $p_oSql->fetch($R2);
               if (!isset($laFila2[0]) and empty($laFila2[0])){
                  $this->pcError = "ERROR CENTRO DE COSTO [".$lcCenCos."] NO TIENE CLASE CONFIGURADA";
                  return false;
               }
               $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri as cEstado FROM S01TCCO A
                   LEFT JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                   LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
                   WHERE LENGTH(A.cClase) <= 7 AND A.cClase LIKE '{$laFila2[0]}%'
                   ORDER BY A.cClase";
               $R2 = $p_oSql->omExec($lcSql);
               while ($laFila2 = $p_oSql->fetch($R2)){
                  $this->paDatos[] = ['CCENCOS' => $laFila2[0], 'CDESCRI' => $laFila2[1], 'CNOMUNI' => $laFila2[2], 'CCLASE' => $laFila2[3], 'CESTADO' => $laFila2[4]];  
               }
            }
         }
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR USUARIO NO TIENE PERMISOS PARA OPCION";
         return false;
      }
      $this->paData['CPERMIS'] = $lcCentro;
      return True;
   }
   
   # --------------------------------------------------
   # Trae hijos de Centro de Costos
   # 2020-12-02 BOL Creacion
   # --------------------------------------------------
   
   public function omDetalleCenCos() {
      $llOk = $this->mxValDetalleCenCos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosCenCos($loSql);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleCenCos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDetalleCenCos() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDetalleCenCos($p_oSql) {
      //TRAE TABLA DE CENTRO DE COSTOS
      $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri as cEstado FROM S01TCCO A
                LEFT JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
                WHERE A.cClase like '{$this->paData['CCLASE']}%'
                ORDER BY A.cClase";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCLASE' => $laFila[3], 'CESTADO' => $laFila[4]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "CENTRO DE COSTO NO TIENE CENTROS DE COSTO DEPENDIENTES";
         return false;
      }
      return true;
   }
   
   # --------------------------------------------------
   # Trae datos de un Centro de Costos
   # 2020-12-02 BOL Creacion
   # --------------------------------------------------
   public function omDatosCenCos() {
      $llOk = $this->mxValDatosCenCos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosCenCos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDatosCenCos() {
      if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTOS NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDatosCenCos($p_oSql) {
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT cCenCos, cDescri, TRIM(cEstado), TRIM(cUniAca), TRIM(cClase), TRIM(cTipEst), TRIM(cEstPre), TRIM(cAfecta), TRIM(cTipo), TRIM(cDepend), TRIM(cTipDes) FROM S01TCCO WHERE cCenCos = '$lcCenCos'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "CENTRO DE COSTOS NO EXISTE";
         return false;
      }
      $this->paData = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2], 'CUNIACA' => $laFila[3], 'CCLASE' => $laFila[4], 'CTIPEST' => $laFila[5], 'CESTPRE' => $laFila[6], 'CAFECTA' => $laFila[7], 'CTIPO' => $laFila[8], 'CDEPEND' => $laFila[9], 'CTIPDES' => $laFila[10]];
      return true;
   }
   
   # --------------------------------------------------
   # Init Mantenimiento Centros de Responsabilidad
   # 2021-01-15 BOL Creacion
   # --------------------------------------------------
   public function omInitMntoCenRes() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '008');// 008:Administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitMntoCenRes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitMntoCenRes($p_oSql) {
      //TRAE TABLA DE CENTRO DE COSTOS
      // $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), TRIM(A.cClase), B.cDescri as cEstado FROM S01TCCO A
      //           LEFT JOIN V_S01TTAB B ON B.cCodigo = A.cEstado AND B.cCodTab='041'
      //           WHERE A.cEstado = 'A'
      //           ORDER BY A.cClase";
      $lcSql = "SELECT cCenCos, cDescri, cNivel, cEstado FROM S01TCCO WHERE cEstado = 'A'
                 ORDER BY cCenCos";
      // print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNIVEL' => $laFila[2], 'CESTADO' => $laFila[3]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE CENTRO DE COSTOS";
         return false;
      }
      return true;
   }
   
   # --------------------------------------------------
   # Init Mantenimiento Centros de Responsabilidad
   # 2021-01-15 BOL Creacion
   # --------------------------------------------------
   public function omVerCenRes() {
      $llOk = $this->mxValVerCenRes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerCenRes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValVerCenRes() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } 
      elseif (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTOS NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxVerCenRes($p_oSql) {
      //TRAE TABLA  DE SEGUN CENTRO COSTO
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT A.cCenRes, TRIM(A.cDescri), B.cDescri as cEstado FROM S01TRES A
                LEFT JOIN V_S01TTAB B ON B.cCodigo = A.cEstado AND B.cCodTab='041'
                WHERE A.cCenCos = '$lcCenCos'
                ORDER BY A.cDescri";
      // print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCENRES' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2]];
      }
      //TRAE DATOS DEL CENTRO COSTO
      $lcSql = "SELECT TRIM(A.cDescri), TRIM(A.cClase) FROM S01TCCO A
                WHERE A.cCenCos = '$lcCenCos'
                ORDER BY A.cClase";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "ERROR DATOS CENTRO DE COSTOS";
         return false;
      }
      $this->paData = ['CCENCOS' => $lcCenCos, 'CDESCRI' => $laFila[0], 'CCLASE' => $laFila[1]];
      return true;
   }
   
   # --------------------------------------------------
   # Trae Datos para Centro de Responsabilidad
   # 2021-01-18 BOL Creacion
   # --------------------------------------------------
   public function omDatosCenRes() {
      $llOk = $this->mxValDatosCenRes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosCenRes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDatosCenRes() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } 
      elseif (!isset($this->paData['CCENRES'])) {
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDatosCenRes($p_oSql) {
      //TRAE DATOS DEL CENTRO DE RESPONSABILIDAD
      $lcCenRes = $this->paData['CCENRES'];
      $lcSql = "SELECT TRIM(cDescri), cEstado, cCenCos FROM S01TRES
                WHERE cCenRes = '$lcCenRes'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "ERROR DATOS CENTRO DE RESPONSABILIDAD";
         return false;
      }
      $this->paData = ['CCENRES' => $lcCenRes, 'CDESCRI' => $laFila[0], 'CESTADO' => $laFila[1], 'CCENCOS' => $laFila[2]];
      return true;
   }
   
   # --------------------------------------------------
   # Trae Conceptos de Ingresos/Egresos Centros de Costo
   # 2021-01-20 BOL Creacion
   # --------------------------------------------------
   public function omInitConceptosIE() {
      $llOk = $this->mxValInitConceptosIE();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '005');// 005: mnt control distribucion
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitConceptosIE($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValInitConceptosIE() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   
   protected function mxInitConceptosIE($p_oSql) {
      //TRAE CENTROS RESPONSABLES
      $lcSql = "SELECT cCenCos FROM S01PCCO WHERE cEstado = 'A' AND cCodUsu = '{$this->paData['CUSUCOD']}'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laCenCos[] = ['CCENCOS' => $laFila[0]];
      }
      //TRAE TABLA DE CONCEPTOS SEGUN MODULOS CORRESPONDIENTES DEL USUARIO
      $lcSql = "SELECT DISTINCT B.cIdInEg, B.cDescri FROM S01PCCO A
                LEFT JOIN D02TINE B ON B.cModulo = A.cModulo
                INNER JOIN S01TMOD C ON C.cModulo = B.cModulo
                WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cModulo in ('002','003','004','005') 
                AND C.cEstado = 'A' AND B.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDINEG' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paDatos) <= 0) {
         $this->pcError = "SIN ACCESO PARA EL MANTENIMIENTO DE DISTRIBUCION";
         return false;
      }
      return true;
   }
   
   # --------------------------------------------------
   # Init Distribucion Centros de Costo
   # 2021-01-18 BOL Creacion
   # --------------------------------------------------
   public function omInitDistribucion() {
      $llOk = $this->mxValInitDistribucion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '008');// Administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitDistribucion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValInitDistribucion() {
      if (!$this->mxValParam()) {
         return false;
      } elseif (!isset($this->paData['CIDINEG'])) {
         $this->pcError = "CONCEPTO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 7) {
         $this->pcError = "PERIODO FORMATO AAAA-MM, NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxInitDistribucion($p_oSql) {
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcCodigo = $this->paData['CIDINEG'];
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], -2);
      //CONSULTA SI PERIODO ESTA HABILITADO
      $lcSql = "SELECT cEstCos FROM S01MPRY WHERE cProyec = '$lcPeriod'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "PERIODO ".$this->paData['CPERIOD']." NO SE ENCUENTRA HABILITADO";
         return false;      
      }
      if ($laFila[0] != 'A') {
         $this->pcError = "PERIODO ".$this->paData['CPERIOD']." SE ENCUENTRA CERRADO. NO ES POSIBLE REALIZAR CAMBIOS";
         return false;
      }
      //TRAE TABLA DE DISTRIBUCION
      $lcSql = "SELECT A.nSerial, C.cDescri, A.nElemen, D.cDescri FROM D02DFCT A 
                LEFT JOIN S01TCCO C ON A.cCosCen = C.cCenCos
                LEFT JOIN V_S01TTAB D ON A.cEstado = D.cCodigo AND D.cCodTab = '041'
                WHERE A.cIdInEg = '$lcCodigo' AND A.cPeriod='$lcPeriod' ORDER BY C.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCENCOS' => $laFila[1], 'NELEMEN' => $laFila[2], 'CESTADO' => $laFila[3], 'CCODFAC' => $laFila[4]];
      }
      //TRAE TABLA DE CENTROS DE COSTO
      $lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO
                WHERE cEstado = 'A' AND cCenCos != 'UNI' ORDER BY cClase";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[2].' - '.$laFila[1]];
      }
      if (count($this->paCenCos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE CENTRO DE COSTOS";
         return false;
      }
      //TRAE DETALLE DE LA DISTRIBUCION
      $lcSql = "SELECT B.cDescri, C.cDescri, A.cCodigo, A.cUnidad, F.cDescri, B.cCenCos, B.cIdInEg, B.cModulo FROM D02MINE A
                LEFT JOIN D02TINE B ON A.cIdInEg = B.cIdInEg
                LEFT JOIN V_S01TTAB C ON A.cUnidad = C.cCodigo AND C.cCodTab = '306'
                LEFT JOIN S01TCCO F ON F.cCenCos= B.cCenCos
                WHERE A.cIdInEg = '$lcCodigo' AND A.cPeriod = '$lcPeriod'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paData = ['CPERIOD' => substr($lcPeriod, 0, 4).'-'.substr($lcPeriod, -2), 'CCODIGO' => $lcCodigo, 'CDESCRI' => $laFila[0], 
                          'CUNIDES' => $laFila[1], 'CCONCEP' => $laFila[2], 'CUNIDAD' => $laFila[3],'CRESPON' => $laFila[4], 'CCOSCEN' => $laFila[5], 
                          'CIDINEG' => $laFila[6], 'CMODULO' => $laFila[7]];
      }
      return true;
   }
   
   # --------------------------------------------------
   # Trae Datos de Distribucion
   # 2021-01-18 BOL Creacion
   # --------------------------------------------------
   public function omDatosDist() {
      $llOk = $this->mxValDatosDist();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosDist($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDatosDist() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } 
      elseif (!isset($this->paData['NSERIAL'])) {
         $this->pcError = "DISTRIBUCION NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDatosDist($p_oSql) {
      //TRAE DATOS DE DISTRIBUCION
      $lcSerial = $this->paData['NSERIAL'];
      $lcSql = "SELECT A.cCosCen, A.nElemen, A.cEstado, B.cDescri FROM D02DFCT A
                LEFT JOIN S01TCCO B ON A.cCosCen = B.cCenCos WHERE A.nSerial = $lcSerial";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "ERROR DATOS CON DISTRIBUCION";
         return false;
      }
      $this->paData = ['NSERIAL'=>$lcSerial, 'CCENCOS' => $laFila[0], 'NMONTO' => $laFila[1], 'CESTADO' => $laFila[2], 'CDESCEN' => $laFila[3]];
      return true;
   }
   
   # --------------------------------------------------
   # Graba Datos de Distribucion
   # 2021-01-29 BOL Creacion
   # --------------------------------------------------
   public function omGrabaDatosDist() {
      $llOk = $this->mxValGrabaDatosDist();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabaDatosDist($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValGrabaDatosDist() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['NSERIAL'])) {
         $this->pcError = "DISTRIBUCION NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CCOSCEN'])) {
         $this->pcError = "CENTRO DE COSTO RESPONSABLE NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['NMONTO'])) {
         $this->pcError = "VALOR DE DISTRIBUCION NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO'])) {
         $this->pcError = "ESTADO DE DISTRIBUCION NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUNIDAD'])) {
         $this->pcError = "UNIDAD DE DISTRIBUCION NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CIDINEG'])) {
         $this->pcError = "CONCEPTO NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxGrabaDatosDist($p_oSql) {
      $lcPeriod = $this->paData['CPERIOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      $lnElemen = round((float)$this->paData['NMONTO'], 2);
      $lcEstado = $this->paData['CESTADO'];
      $lcUnidad = $this->paData['CUNIDAD'];
      $lcSerial = $this->paData['NSERIAL'];
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcCosCen = $this->paData['CCOSCEN'];
      $lcIdInEg = $this->paData['CIDINEG'];
      // Valida que centro costo responsable exista
      if ($lcCosCen == '000'){
         $this->pcError = "ERROR CONCEPTO SELECCIONADO NO TIENE CENTRO DE COSTO RESPONSABLE";
         return false;
      }
      // Codifica en Json para el campo cElemen
      $lcElemen = json_encode(['NMONTO' => round((float)$this->paData['NMONTO'], 2)]);
      // Suma todos los elementos
      $laPeriod = explode("-",$lcPeriod);
      $lcPeriod = $laPeriod[0].$laPeriod[1];
      if ($this->paData['NSERIAL'] != '*'){
         //ACTUALIZA DISTRIBUCION
         $lcSql = "UPDATE D02DFCT SET cElemen = '$lcElemen', nElemen = $lnElemen, cEstado = '$lcEstado',
                   cUsuCod = '$lcCodUsu', tModifi = NOW() WHERE nSerial = $lcSerial";
         $RS = $p_oSql->omExec($lcSql);
         if(!$RS){
            $this->pcError = "ERROR ACTUALIZACION DE DATOS DE DISTRIBUCION";
            return false;
         }
      } else {
         //GRABA NUEVA DISTRIBUCION
         $lcSql = "INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) 
                   VALUES ('$lcIdInEg', '$lcPeriod', '$lcCosCen', '$lcCenCos', '$lcUnidad', $lnElemen, '$lcElemen', 'A','$lcCodUsu')";
         $RS = $p_oSql->omExec($lcSql);
         if(!$RS){
            $this->pcError = "ERROR GRABACION DE DATOS DE DISTRIBUCION";
            return false;
         }
      }
      return true;
   }
   
   # --------------------------------------------------
   # Carga los datos de la plantilla xlsx
   # 2021-02-15 BOL Creacion
   # --------------------------------------------------
   public function omSubirPlantilla() {
      $llOk = $this->mxValParamSubirPlantilla();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, $this->paData['CMODULO']);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxSubirPlantilla($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxValXls($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGrabaDatosXls($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamSubirPlantilla() {
      if (!$this->mxValParam()){
         return false;
      } elseif (!isset($this->poFile)) {
         $this->pcError = "ARCHIVO NO DEFINIDO";
         return false;
      } else if ($this->poFile['size'] > (5.1 * 1000000)){
         $this->pcError = 'EL TAMAÑO DEL ARCHIVO ES SUPERIOR A 5.0 MB';
         return false;
      } else if (pathinfo($this->poFile['name'], PATHINFO_EXTENSION) != 'xlsx') {
         $this->pcError = 'EL ARCHIVO NO ES UN ARCHIVO xls/xlsx';
         return false;
      } elseif (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO RESPONSABLE NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CMODULO'])) {
         $this->pcError = "MODULO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CIDINEG'])) {
         $this->pcError = "CONCEPTO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUNIDAD'])) {
         $this->pcError = "PERIODO NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxSubirPlantilla($p_oSql) {
      $this->poFile['CNOMBRE'] = 'R'.rand();
      $llOk = fxSubirXls($this->poFile, 'Tmp', $this->poFile['CNOMBRE']);
      if (!$llOk) {
         $this->pcError = 'UN ERROR HA OCURRIDO MIENTRAS SE SUBIA EL ARCHIVO';
         return false;
      }
      // Guarda contenido en un arreglo
      $lcType = pathinfo($this->poFile['name'], PATHINFO_EXTENSION);
      $lcFile = 'Docs/Tmp/'.$this->poFile['CNOMBRE'].'.'.$lcType;
      //caso phpexel: se comenta las lineas 765 a 768
      $loXls = new CSpreadSheet();
      $llOK = $loXls->openXlsx($lcFile);
      if (!$llOK){
         $this->pcError = $loXls->pcError;    
         return false;
      }
      $this->laData = ['CMODULO' => $loXls->getValue(0,'D',3), 'CCENRES' => $loXls->getValue(0,'D',4)];
      // Trae el tipo de distribucion
      $lcSql = "SELECT A.cUnidad, C.cDescri FROM D02MINE A INNER JOIN D02TINE B ON A.cIdInEg = B.cIdInEg
                LEFT JOIN V_S01TTAB C ON C.cCodTab = '306' AND C.cCodigo = A.cUnidad
                WHERE B.cModulo = '{$this->laData['CMODULO']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = 'DEBE DEFINIR UNIDAD DE DISTRIBUCION PARA MODULO ['.$this->laData['CMODULO'].']';    
         return false;
      }
      $this->laData['CUNIDAD'] = $laFila[0];
      $this->laData['CUNIDES'] = $laFila[1];
      $lnFila = 6;
      $lcCenCos = trim($loXls->getValue(0, 'B', $lnFila));
      while ($lcCenCos != '') {
         $this->laDatos[] = ['CCENCOS' => $lcCenCos, 'NMONTO' => $loXls->getValue(0, 'D', $lnFila), 'NMONTO2' => $loXls->getValue(0, 'E', $lnFila), 'NELEMEN' => round($loXls->getValue(0, 'D', $lnFila) + $loXls->getValue(0, 'E', $lnFila), 2)];
         $lnFila ++;
         $lcCenCos = trim($loXls->getValue(0, 'B', $lnFila));
      }
      // Elimina plantilla temporal
      $llOk = unlink('./'.$lcFile);
      if (!$llOk){
         $this->pcError = "HUBO UN ERROR AL ELIMINAR ARCHIVO TEMPORAL XLSX";
         return false;
      }
      return true;
   }
   
   protected function mxValXls($p_oSql){
      $lcError = '*** ERROR ***'.PHP_EOL;
      $llError = false;
      // Valida que tenga registros
      if (count($this->laDatos) <= 0){
         $lcError .= 'NO SE ENCONTRARON REGISTROS'.PHP_EOL;
         $llError = true;
      }
      // Valida que el modulo y centro de costo corresponda a la opcion ingresada
      if ($this->laData['CMODULO'] != $this->paData['CMODULO']){
         $this->pcError = 'ERROR EL MODULO DEL ARCHIVO NO CORRESPONDE AL MODULO INGRESADO';
         return false;
      } 
      $lnFila = 6;
      foreach ($this->laDatos as $laDatos){
         // Valida que c.c. del archivo sea valido
         if (strlen($laDatos['CCENCOS']) != 3){
            $lcError .= 'CENTRO DE COSTO INVALIDO. FILA:'.$lnFila.PHP_EOL;
            $llError = true;
         }
         // Valida que c.c. del archivo existan
         $lcSql = "SELECT cEstado, cTipDes FROM S01TCCO WHERE cCenCos = '{$laDatos['CCENCOS']}'";
         $RS = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($RS);
         if (!isset($laFila[0]) or empty($laFila[0])) {
            $lcError .= 'CENTRO DE COSTO NO EXISTE. FILA:'.$lnFila.PHP_EOL;
            $llError = true;
         } elseif ($laFila[0] != 'A') {
            $lcError .= 'CENTRO DE COSTO NO ESTA ACTIVO. FILA:'.$lnFila.PHP_EOL;
            $llError = true;
         } /*elseif ($laFila[1] != '01') {
            $lcError .= 'CENTRO DE COSTO NO PERMITIDO PARA DISTRIBUCION. FILA:'.$lnFila.PHP_EOL;
            $llError = true;
         }*/
         // Valida que montos sean numericos y no negativos
         $laUnidad = explode(',', $this->laData['CUNIDES']); // Nombre de la unidad de medida
         if (!is_numeric($laDatos['NMONTO'])) {
            $lcError .= 'COLUMNA '.$laUnidad[0].' NO ES NUMERICO FILA:'.$lnFila.PHP_EOL;
            $llError = true;
         } elseif ($laDatos['NMONTO'] < 0) {
            $lcError .= 'COLUMNA '.$laUnidad[0].' NO PUEDE SER NEGATIVO. FILA:'.$lnFila.PHP_EOL;
            $llError = true;
         }
         $laTmp[] = $laDatos['CCENCOS'];
         $lnFila ++;
      }
      // Valida que los centros de costo no se repitan entre ellos
      $laTmp = array_count_values($laTmp);
      foreach($laTmp as $lcCenCos => $lnNumero){
         if ($lnNumero >= 2 ){
            $lcError .= 'CENTRO DE COSTO ['.$lcCenCos.'], SE REPITE MAS DE UNA VEZ'.PHP_EOL;
            $llError = true;
         }
      }
      $lcError .= 'EL ARCHIVO CONTIENE '.count($this->laDatos).' REGISTROS EN TOTAL';
      if($llError){
         $this->paData['COBSERV'] = $lcError;
         $this->pcError = 'DEBE RESOLVER LAS OBSERVACIONES';
         return false;
      }
      return true;
   }
   
   protected function mxGrabaDatosXls($p_oSql){
      $lcElemen = '';
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], 5, 2);
      // Borra datos actuales de distribucion
      $lcSql = "DELETE FROM D02DFCT WHERE cIdInEg = '{$this->paData['CIDINEG']}' AND cPeriod = '$lcPeriod' AND CCENCOS = '{$this->paData['CCENCOS']}'";
      $RS = $p_oSql->omExec($lcSql);
      if(!$RS){
         $this->pcError = "ERROR ACTUALIZANDO DATOS DE DISTRIBUCION";
         return false;
      }
      // Graba nuevos datos de distribucion a partir del Xls
      foreach($this->laDatos as $laDatos){
         $laTmp = ['NMONTO'=>round($laDatos['NMONTO'], 2)];
         $lcElemen = json_encode($laTmp);
         $lcSql = "INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, cElemen, nElemen, cEstado, cUsuCod) 
                VALUES ('{$this->paData['CIDINEG']}', '$lcPeriod', '{$this->paData['CCENCOS']}', '{$laDatos['CCENCOS']}', '{$this->paData['CUNIDAD']}','$lcElemen', {$laDatos['NELEMEN']}, 'A','{$this->paData['CUSUCOD']}')";
         $RS = $p_oSql->omExec($lcSql);
         if(!$RS){
            $this->pcError = "ERROR EN GRABACION DE DATOS XLSX";
            return false;
         }
      }
      // Actualiza fecha de actualizacion de la distribucion
      $lcSql = "UPDATE D02MINE SET tActual = NOW() WHERE cIdInEg = '{$this->paData['CIDINEG']}' AND cPeriod = '$lcPeriod' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if(!$RS){
         $this->pcError = "ERROR AL INGRESAR FECHA DE ACTUALIZACION DE DISTRIBUCION";
         return false;
      }
      return true;
   }
   
   # --------------------------------------------------
   # Descarga la plantilla xlsx
   # 2021-02-16 BOL Creacion
   # --------------------------------------------------
   public function omDescargarPlantilla() {
      $llOk = $this->mxValParamDescargarXls();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosXls($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      $llOk = $this->mxGeneraXls();
      return $llOk;
   }
   
   protected function mxValParamDescargarXls() {
      if (!$this->mxValParam()){
         return false;
      } elseif (!isset($this->paData['CIDINEG'])) {
         $this->pcError = "CONCEPTO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO";
         return false;
      } 
      return true;
   }
   
   protected function mxDatosXls($p_oSql){
      $this->laDatos = [];
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], 5, 2);
      // Trae descripcion del centro de costo y descripcion del concepto
      $lcSql = "SELECT A.cDescri, B.cDescri FROM D02TINE A 
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.cIdInEg = '{$this->paData['CIDINEG']}' AND A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = "ERROR EN RECUPERACION DE DETALLE DEL CONCEPTO";
         return false;
      }
      $this->paData['CCONCEP'] = $this->paData['CCENCOS'].': '.$laFila[1].'/'.$laFila[0];
      // Trae cuenta contable y unidad de medida
      $lcSql = "SELECT A.cCtaCnt, B.cDescri FROM D02MINE A 
                LEFT JOIN V_S01TTAB B ON B.cCodtab = '306' AND A.cUnidad = B.cCodigo
                WHERE A.cIdInEg = '{$this->paData['CIDINEG']}' AND
                A.cPeriod = '$lcPeriod' AND A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = "ERROR EN RECUPERACION DE CUENTA CONTABLE DEL CONCEPTO";
         return false;
      }
      $this->paData['CCTACNT'] = '';
      $laCtaCnt = explode(',', $laFila[0]);
      foreach ($laCtaCnt as $lcCtaCnt) {
         $lnSaldo = $this->mxSaldosContables($p_oSql, $lcCtaCnt, '0');       
         $this->paData['NSALDO'] = $this->paData['NSALDO'] + $lnSaldo;
         $this->paData['CCTACNT'] .= $lcCtaCnt.', ';
      }
      $this->paData['CUNIDAD'] = $laFila[1];
      // Trae datos actuales de distribucion
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], 5, 2);
      $lcSql = "SELECT A.cCosCen, C.cDescri, A.nElemen FROM D02DFCT A 
                LEFT JOIN S01TCCO C ON A.cCosCen = C.cCenCos
                LEFT JOIN V_S01TTAB D ON A.cEstado = D.cCodigo AND D.cCodTab = '041'
                WHERE A.cIdInEg = '{$this->paData['CIDINEG']}' AND A.cPeriod='$lcPeriod' ORDER BY C.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->laDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'NELEMEN' => $laFila[2]];
      } 
      return true;
   }
   
   // Calcula el saldo dependiendo si es cuenta activa o pasiva
   protected function mxSaldosContables($p_oSql, $p_cCtaCnt, $p_cFlag){
       $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], 5, 2);
       $laCtaCnt = ['1','2','3','7'];
       $lcCtaCnt = substr($lcCtaCnt,0 ,1);
       if ($p_cFlag == '0'){
          if (in_array($lcCtaCnt, $laCtaCnt)){
             $lcSql = "SELECT SUM(nDebMN - nHabMN) FROM V_D10DASI
                       WHERE cPeriod = '$lcPeriod' AND cCtaCnt LIKE '$p_cCtaCnt%'";
          } else{
             $lcSql = "SELECT SUM(nHabMN - nDebMN) FROM V_D10DASI
                       WHERE cPeriod = '$lcPeriod' AND cCtaCnt LIKE '$p_cCtaCnt%'";
          }
       } else {
          if (in_array($lcCtaCnt, $laCtaCnt)){ //OJO AUMENTAR LA CLAUSULA DE CTA:701
             // Consulta el saldo correspondiente de su distribucion pcflag:1
             $lcSql = "SELECT SUM(nDebMN - nHabMN), cCenCos FROM V_D10DASI
                        WHERE cPeriod = '$lcPeriod' AND cCtaCnt LIKE '$p_cCtaCnt%'
                        GROUP BY cCenCos";
          } else{
             $lcSql = "SELECT SUM(nHabMN - nDebMN), cCenCos FROM V_D10DASI
                        WHERE cPeriod = '$lcPeriod' AND cCtaCnt LIKE '$p_cCtaCnt%'
                        GROUP BY cCenCos";
          }
       }
       $RS = $p_oSql->omExec($lcSql);
       $laFila = $p_oSql->fetch($RS);
       if (!isset($laFila[0]) or empty($laFila[0])){
          $laFila[0] = 0.00;
       }
       return $laFila[0];
   } 
   
   protected function mxGeneraXls(){
      $lcFile = 'Xls/Cnt5130.xlsx';
      $loXls = new CSpreadSheet();
      $llOK = $loXls->openXlsx($lcFile);
      if (!$llOK){
         $this->pcError = $loXls->pcError;    
         return false;
      }
      $loXls->sendXls(0,'D',1, date('Y').'-'.date('m').'-'.date('d'));
      $loXls->sendXls(0,'B',2, $this->paData['CCONCEP']);
      $loXls->sendXls(0,'D',3, $this->paData['CMODULO']);
      $loXls->sendXls(0,'D',4, $this->paData['CPERIOD']);
      $loXls->sendXls(0,'B',3, substr($this->paData['CCTACNT'], 0, strlen($this->paData['CCTACNT']) - 2));
      $loXls->sendXls(0,'B',4, $this->paData['NSALDO']);
      $loXls->sendXls(0,'D',5, $this->paData['CUNIDAD']);
      $i = 6;
      foreach ($this->laDatos as $laFila) {
         $loXls->sendXls(0,'A', $i, $i - 5);
         $loXls->sendXls(0,'B', $i, $laFila['CCENCOS']);
         $loXls->sendXls(0,'C', $i, $laFila['CDESCRI']);
         $loXls->sendXls(0,'D', $i, $laFila['NELEMEN']);
         $i ++;
      }
      $loXls->closeXls();
      $this->pcFilXls = $loXls->pcFilXls;
      return true;
   }
   
   # ---------------------------------------------------
   # Init Habilita periodo
   # 2021-03-23 BOL Creacion
   # ---------------------------------------------------
   public function omInitHabilitaPeriodo() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '008');// Administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxDatosInitHabilitaPeriodo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxDatosInitHabilitaPeriodo($p_oSql) {
      // Trae tabla principal
      $lcSql = "SELECT A.cProyec, A.cDescri, B.cDescri FROM S01MPRY A 
                LEFT JOIN V_S01TTAB B ON A.cEstCos = B.cCodigo AND B.cCodTab = '314' ORDER BY A.cProyec DESC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CPERIOD'=> $laFila[0], 'CPROYEC'=> substr($laFila[0], 0, 4).'-'.substr($laFila[0], -2), 'CDESCRI'=> $laFila[1], 'CESTCOS'=> $laFila[2]];
      }
      return true;
   }
   
   # -----------------------------------------------------------
   # Cierra fecha para proceso de distribucion ingreso/egreso
   # 2021-04-05 BOL Creacion
   # -----------------------------------------------------------
   public function omCierreProceso() {
      $llOk = $this->mxValCierreProceso();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValFechaCierreProceso($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCierreProceso($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValCierreProceso() {
      if (!$this->mxValParam()){
         return false;
      } elseif (!isset($this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxValFechaCierreProceso($p_oSql) {
      // Valida que periodo este habilitado para el cierre
      $lcSql = "SELECT cEstCos FROM S01MPRY WHERE cProyec = '{$this->paData['CPERIOD']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = "PERIODO NO ESTA HABILITADO PARA CIERRE DE PROCESO";    
         return false;
      } elseif ($laFila[0] != 'A'){
         $this->pcError = "PERIODO YA FUE EJECUTADO PARA CIERRE DE PROCESO";    
         return false;
      }
      // Valida que un periodo anterior fue cerrado
      $lcPerAnt = $this->mxPeriodoAnt($this->paData['CPERIOD']);
      $lcSql = "SELECT cEstCos FROM S01MPRY WHERE cProyec = '$lcPerAnt'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == 'A'){
         $this->pcError = "DEBE CERRAR PRIMERO EL PERIODO ANTERIOR ".substr($lcPerAnt, 0, 4)."-".substr($lcPerAnt, -2);    
         return false;
      }
      // Valida que proceso(distribucion) se haya ejecutado en dicho periodo
      $lcSql = "SELECT COUNT(A.*) FROM D02DCOS A INNER JOIN D02MINE B ON B.cCodigo = A.cCodigo
                WHERE B.cEstado = 'A' AND B.cPeriod = '{$this->paData['CPERIOD']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = "PRIMERO DEBE EJECUTAR EL PROCESO DE DISTRIBUCION ANTES DE SU CIERRE";    
         return false; 
      } elseif ($laFila[0] <= 0){
         $this->pcError = "PRIMERO DEBE EJECUTAR EL PROCESO DE DISTRIBUCION ANTES DE SU CIERRE";    
         return false;
      }
      return true;
   }
   
   protected function mxCierreProceso($p_oSql){
      $lcSql = "UPDATE S01MPRY SET cEstCos = 'C' WHERE cProyec = '{$this->paData['CPERIOD']}'";
      $RS = $p_oSql->omExec($lcSql);
      if(!$RS){
         $this->pcError = "ERROR ACTUALIZANDO CIERRE DEL PROCESO";
         return false;
      }
      return true;
   }
   
   protected function mxPeriodoAnt($lcPeriod){
      if (substr($lcPeriod, -2) == '01') {
         $lcAnio = (string)((int)substr($lcPeriod, 0, 4) - 1);
         $lcPerAnt = $lcAnio.'12';
      } else {
         $lcPerAnt = (string)((int)$lcPeriod - 1);
      }
      return $lcPerAnt;
   }
   
   # --------------------------------------------------
   # Init Consulta Saldos de ingresos/egresos
   # 2021-03-23 BOL Creacion
   # --------------------------------------------------
   public function omInitRepSaldoIngEgr() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '007'); // Consulta reporte CC.
      $loSql->omDisconnect();
      return $llOk;
   }
   
   # ---------------------------------------------------
   # Reporte Saldos contables de los ingresos/egresos
   # 2021-02-04 GCH Creacion
   # ---------------------------------------------------
   public function omRepSaldoIngEgr() { 
      $llOk = $this->mxValParamRepSaldoIngEgr();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatoSaldoIngEgr($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxSaldoIngEgrXls();
      return $llOk;
   }

   protected function mxValParamRepSaldoIngEgr() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 7) {
         $this->pcError = "PERIODO (aaaa-mm) NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxDatoSaldoIngEgr($p_oSql) {
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], -2);
      $lcPerIni = substr($this->paData['CPERIOD'], 0, 4)."00";
      $lcAnio = substr($this->paData['CPERIOD'], 0, 4);
      $lcSql = "SELECT cIdInEg, cDescri FROM D02TINE WHERE cEstado = 'A'
                  AND SUBSTRING(cIdInEg, 1, 1) != 'Z' ORDER BY cIdInEg";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         if (substr($laFila[0], 3, 2) == '00') {
            $this->laDatos[] = ['CIDINEG'=> $laFila[0], 'CDESCRI'=> $laFila[1], 'CCTACNT'=> '', 'CDESCNT'=> '', 'NDEBE'=> 0.00, 'NHABER'=> 0.00];
            continue;
         }
         $lcSql = "SELECT A.cCtaCnt FROM D02MINE A
                   INNER JOIN D02TINE B ON A.cIdInEg = B.cIdInEg
                   WHERE A.cEstado = 'A' AND A.cPeriod = '$lcPeriod'
                        AND A.cIdInEg = '$laFila[0]'";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $laCtaCnt = explode(",", $laTmp[0]);
            foreach ($laCtaCnt as $lcCtaCnt){
               if (substr($lcCtaCnt, 0, 1) == '-') {
                  $lcCtaCnt = trim(substr($lcCtaCnt, 1, strlen($lcCtaCnt)));
               }
               $lcSql = "SELECT Descri FROM D10MCTA WHERE CodCta = '$lcCtaCnt' AND AnoPro = '$lcAnio'";
               $R3 = $p_oSql->omExec($lcSql);
               $laFila3 = $p_oSql->fetch($R3);
               if (empty($laFila3[0])) {
                  $this->laDatos[] = ['CIDINEG'=> $laFila[0], 'CDESCRI'=> $laFila[1], 
                                      'CCTACNT'=> $lcCtaCnt, 'CDESCNT'=> '*** ERROR ***', 
                                      'NDEBE'=> 0.00, 'NHABER'=> 0.00];
                  continue;
               }
               $lcSql = "SELECT SUM(nDebMn), SUM(nHabMn) FROM V_D10DASI 
                         WHERE cCtaCnt LIKE '$lcCtaCnt%'
                          AND cPeriod BETWEEN '$lcPerIni' AND '$lcPeriod'";
               $R4 = $p_oSql->omExec($lcSql);
               while ($laFila4 = $p_oSql->fetch($R4)) {
                   if($laFila4[0] == null){
                      $this->laDatos[] = ['CIDINEG'=> $laFila[0], 'CDESCRI'=> $laFila[1], 
                                          'CCTACNT'=> $lcCtaCnt, 'CDESCNT' => $laFila3[0],
                                          'NDEBE' => 0.00, 'NHABER' => 0.00];   
                   } else {
                      $this->laDatos[] = ['CIDINEG'=> $laFila[0], 'CDESCRI'=> $laFila[1], 
                                          'CCTACNT'=> $lcCtaCnt, 'CDESCNT' => $laFila3[0],
                                          'NDEBE' => $laFila4[0],'NHABER' => $laFila4[1]];
                   }
               }
            }
         }
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "SIN REGISTROS";
         return false;
      }
       // Totaliza detalle
      $i = 0;
      foreach ($this->laDatos as $laTmp) {
         if (substr($laTmp['CIDINEG'], 3, 2) != '00') {
            $i++;
            continue;
         }
         $lnDebe  = 0.00;
         $lnHaber = 0.00;
         $lcIdInEg = substr($laTmp['CIDINEG'], 0, 2);
         foreach ($this->laDatos as $laTmp1) {
            if (substr($laTmp1['CIDINEG'], 0, 2) > $lcIdInEg) {
               break;
            } elseif (substr($laTmp1['CIDINEG'], 3, 2) == '00' or substr($laTmp1['CIDINEG'], 0, 2) != $lcIdInEg) {
               continue;
            }
            $lnDebe  += $laTmp1['NDEBE'];
            $lnHaber += $laTmp1['NHABER'];
         }
         $this->laDatos[$i]['NDEBE']  = $lnDebe;
         $this->laDatos[$i]['NHABER'] = $lnHaber;
         $i++;
      }
      return true;
   }

   protected function mxSaldoIngEgrXls() {
      //$loXls = new CXls();
      $loXls = new CSpreadSheet();
      $loXls->openXlsx('Xls/Cnt5140.xlsx');
      // Cabecera
      $loXls->sendXls(0, 'G', 1, 'FECHA: '.date("Y-m-d"));
      $loXls->sendXls(0, 'G', 2, 'PERIODO: '.$this->paData['CPERIOD']);
      $lnFila = 4;
      $lnSaldo = 0;   
      foreach ($this->laDatos as $laFila) {
         $lnSaldo = $this->mxSaldosCnt($laFila['CCTACNT'], $laFila['NDEBE'], $laFila['NHABER']);
         $loXls->sendXls(0, 'A', $lnFila, $laFila['CIDINEG']);
         $loXls->sendXls(0, 'B', $lnFila, $laFila['CDESCRI']);
         $loXls->sendXls(0, 'C', $lnFila, $laFila['CCTACNT']);
         $loXls->sendXls(0, 'D', $lnFila, $laFila['CDESCNT']);
         $loXls->sendXls(0, 'E', $lnFila, $laFila['NDEBE']);
         $loXls->sendXls(0, 'F', $lnFila, $laFila['NHABER']);
         $loXls->sendXls(0, 'G', $lnFila, $lnSaldo);
         $lnFila++;
      }
      $loXls->closeXls();
      $this->pcFile = $loXls->pcFilXls;
      return true;
   }
   
   protected function mxSaldosCnt($p_cCtaCnt, $p_nDebe, $p_nHaber){
      $lcCtaCnt = substr($p_cCtaCnt, 0, 1);
      $laCtaCnt = array("1","2","3","7");
      $lnSaldo  = 0.00;
      if (in_array($lcCtaCnt, $laCtaCnt)){
         $lnSaldo = $p_nDebe - $p_nHaber;
      } else{
         $lnSaldo = $p_nHaber - $p_nDebe;
      }
      return $lnSaldo;
   }
   
   # ---------------------------------------------------
   # Reporte Saldos de los ingresos/egresos segun C.C.
   # 2021-02-18 BOL Creacion
   # ---------------------------------------------------
   public function omInitInEgCenCos() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '007'); // 007: COnsulta Reporte CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitInEgCenCos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitInEgCenCos($p_oSql) {
      // Trae tabla de Centro Costo
      $this->paCenCos[] = ['CCENCOS' => '*', 'CDESCRI' => '* TODOS'];
      $lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO WHERE cEstado = 'A' AND cCenCos !='UNI' ORDER BY cClase";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[2].' - '.$laFila[1]];
      }
      if (count($this->paCenCos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE CENTRO DE COSTOS";
         return false;
      }
      return true;
   }
   
   # ---------------------------------------------------
   # Reporte distribucion segun ingresos/egresos
   # 2021-02-18 BOL Creacion
   # ---------------------------------------------------
   public function omInitDistIngEgr() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '007');// 007:Consulta reporte CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitDistIngEgr($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitDistIngEgr($p_oSql) {
      // Trae tabla de Conceptos
      $lcSql = "SELECT cIdInEg, cDescri FROM D02TINE WHERE cEstado = 'A' AND SUBSTRING(cIdInEg, 3, 2) != '00' ORDER BY cIdInEg";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDINEG' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE CENTRO DE COSTOS";
         return false;
      }
      return true;
   }
   
   # ---------------------------------------------------
   # Consulta.. totales por tràmites segun conceptos
   # 2021-02-23 BOL Creacion
   # ---------------------------------------------------
   
   public function omRepTramiteXls() { 
      $llOk = $this->mxValParamRepTramiteXls();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosTramiteXls($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxTramiteXls();
      return $llOk;
   }

   protected function mxValParamRepTramiteXls() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 7) {
         $this->pcError = "PERIODO (aaaa-mm) NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDatosTramiteXls($p_oSql) {
      $lcSql = "SELECT A.cIdDeud, to_char(A.dFecha, 'YYYY-MM-DD'), A.cNroDni, B.cCodAlu, D.cNombre, A.nMonto, C.cDescri, F.cNomUni, B.cIdCate, G.cDescri FROM B03MDEU A
               INNER JOIN B03DDEU B ON B.cIdDeud = A.cIdDeud
               LEFT JOIN V_S01TTAB C ON C.cCodTab = '157' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstado 
               INNER JOIN S01MPER D ON D.cNroDni = A.cNroDni
               INNER JOIN A01MALU E ON E.cCodAlu = B.cCodAlu
               INNER JOIN S01TUAC F ON F.cUniAca = E.cUniAca
               INNER JOIN B03TDOC G ON G.CIDCATE = B.CIDCATE
               WHERE A.cNroPag != '0000000000' AND B.cIdCate LIKE 'PG%'
               AND to_char(A.dFecha, 'YYYY-MM') = '{$this->paData['CPERIOD']}'
               ORDER BY B.cIdCate, A.dFecha, B.cCodAlu";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {//OJO SEPARAR 
         $laData = $this->mxCodAlumPg($p_oSql, $laFila[2]);//ojo estudiante postgrado
         $lcNombre = str_replace("/"," ",$laFila[4]);
         $this->laDatos[] = ['CIDDEU'=> $laFila[0], 'DFECHA'=> $laFila[1], 'CNRODNI'=> $laFila[2], 'CCODALU'=> $laData['CCODALU'], 
                             'CNOMBRE'=>$lcNombre,  'NMONTO'=> $laFila[5], 'CESTADO'=> $laFila[6], 'CNOMUNI'=> $laData['CNOMUNI'],
                             'CIDCATE'=> $laFila[8],'CDESCRI'=> $laFila[9], 'CFLAG' => 'ERR1'];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "SIN REGISTROS";
         return false;
      }
      // Trae mov D12DASI para su comparacion
      $lcSql = "SELECT cNroDni, TO_CHAR(dFecha, 'YYYY-MM-DD'), cCtaCnt, nMonto FROM F_D12DASI_1('{$this->paData['CPERIOD']}','7032102')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {//OJO LADATOS
         $laTemp[] = ['CNRODNI'=> $laFila[0], 'DFECHA'=> $laFila[1], 'CCTACNT'=> $laFila[2], 'NMONTO'=> $laFila[3]];
      }
      // Compara data con el D12DASI
      $laError = [];
      foreach ($laTemp as $laData) {//AL REVES LA TEMP SOLO PARA BUCLE
         $lbFound = false;
         for ($i = 0; $i < count($this->laDatos); $i++) {
            if ($laData['CNRODNI'] == $this->laDatos[$i]['CNRODNI'] and $laData['NMONTO'] == $this->laDatos[$i]['NMONTO'] and $laData['DFECHA'] == $this->laDatos[$i]['DFECHA']){
               $lbFound = true;
               $this->laDatos[$i]['CFLAG'] = 'OK';
               break;
            } elseif($laData['CNRODNI'] == $this->laDatos[$i]['CNRODNI'] and $laData['NMONTO'] != $this->laDatos[$i]['NMONTO']){
               $lbFound = true;
               $this->laDatos[$i]['CFLAG'] = 'ERR2';
               break;
            } elseif($laData['CNRODNI'] == $this->laDatos[$i]['CNRODNI'] and $laData['DFECHA'] != $this->laDatos[$i]['DFECHA']){
               $lbFound = true;
               $this->laDatos[$i]['CFLAG'] = 'ERR3';
               break;
            }
         }
         if (!$lbFound){
            $laError[] = ['CIDDEU'=> '**', 'DFECHA'=> $laData['DFECHA'], 'CNRODNI'=> $laData['CNRODNI'], 'CCODALU'=> '**', 
                          'CNOMBRE'=>'**',  'NMONTO'=> $laData['NMONTO'], 'CESTADO'=> '**', 'CNOMUNI'=> '**',
                          'CIDCATE'=> '**','CDESCRI'=> '**', 'CFLAG' => 'ERR4'];
         }
      }
      if (count($laError) > 0){
         foreach($laError as $laData){
             array_push($this->laDatos, $laData);
         }
      }
      return true;
   }
   
   // Trae el codigo de alumno correspondiente a postgrado si tuviera
   protected function mxCodAlumPg($p_oSql, $p_cNroDni) {
      $laData = ['CCODALU'=> '', 'CUNIACA'=> '', 'CNOMUNI' => ''];
      $lcSql = "SELECT A.cCodAlu, A.cUniAca, B.cNomUni FROM A01MALU A
                INNER JOIN S01TUAC B ON A.cUniAca = B.cUniAca WHERE A.cNroDni = '$p_cNroDni'
                ORDER BY A.cCodAlu DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         if($laFila[1] == '00'){ // sin unidad academica
            continue;
         }
         $lcSql = "SELECT cCenCos, cClase FROM S01TCCO WHERE cUniAca = '{$laFila[1]}' AND cClase LIKE '5%' AND cEstado = 'A'";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laFila2 = $p_oSql->fetch($R2)) {
            if (isset($laFila2[0]) and !empty($laFila2[0])){
               $laData = ['CCODALU'=> $laFila[0], 'CUNIACA'=> $laFila[1], 'CNOMUNI' => $laFila[2], 'CCENCOS' => $laFila2[0], 'CCLASE' => $laFila2[1]];
            }
         }
      }
      return $laData;
   }

   protected function mxTramiteXls() {
      //$loXls = new CXls();
      $loXls = new CSpreadSheet();
      $loXls->openXlsx('Xls/Bol1110.xlsx');
      // Distribucion
      $loXls->sendXls(0, 'C', 1, 'FECHA: '.date("Y-m-d"));
      $loXls->sendXls(0, 'C', 2, 'PERIODO: '.$this->paData['CPERIOD']);
      /*$loXls->sendXls(0, 'A', 4, $this->laData['CCENCOS']);
      $loXls->sendXls(0, 'B', 4, $this->laData['CDESCRI']);
      $loXls->sendXls(0, 'C', 4, $this->laData['NMONTO']);*/
      // Tràmites
      $loXls->sendXls(1, 'K', 1, 'FECHA: '.date("Y-m-d"));
      $loXls->sendXls(1, 'K', 2, 'PERIODO: '.$this->paData['CPERIOD']);
      $i = 4; 
      foreach ($this->laDatos as $laFila) {
         $loXls->sendXls(1, 'A', $i, $laFila['CIDDEU']);
         $loXls->sendXls(1, 'B', $i, $laFila['DFECHA']);
         $loXls->sendXls(1, 'C', $i, $laFila['CIDCATE']);
         $loXls->sendXls(1, 'D', $i, $laFila['CDESCRI']);
         $loXls->sendXls(1, 'E', $i, $laFila['CNRODNI']);
         $loXls->sendXls(1, 'F', $i, $laFila['CCODALU']);
         $loXls->sendXls(1, 'G', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(1, 'H', $i, $laFila['CNOMUNI']);
         $loXls->sendXls(1, 'I', $i, $laFila['NMONTO']);
         $loXls->sendXls(1, 'J', $i, $laFila['CESTADO']);
         $loXls->sendXls(1, 'K', $i, $laFila['CFLAG']);
         $i++;
      }
      $loXls->closeXls();
      $this->pcFile = $loXls->pcFilXls;
      return true;
   }
   
   # ---------------------------------------------------
   # Init Reporte montos agrupados por tramites
   # 2021-03-23 BOL Creacion
   # ---------------------------------------------------
   public function omInitConcepAgrupXls() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '007'); // 007: Consulta reporte CC.
      $loSql->omDisconnect();
      return $llOk;
   }
   
   # -----------------------------------------------------
   # Reporte montos agrupados por tràmites segun conceptos
   # 2021-02-23 BOL Creacion
   # -----------------------------------------------------
   public function omConcepAgrupXls() { 
      $llOk = $this->mxValParamConcepAgrupXls();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosConcepAgrupXls($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxConcepAgrupXls();
      return $llOk;
   }

   protected function mxValParamConcepAgrupXls() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 7) {
         $this->pcError = "PERIODO (aaaa-mm) NO DEFINIDO";
         return false;
      } 
      return true;
   }
   
   protected function mxDatosConcepAgrupXls($p_oSql) {
      $lcSql = "SELECT E.cIdCate, E.cDescri, SUM(A.nMonto), C.cDescri FROM B03MDEU A
               INNER JOIN B03DDEU B ON B.cIdDeud = A.cIdDeud
               LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab = '157' 
               INNER JOIN B03TDOC E ON E.cIdCate = B.cIdCate
               WHERE A.cNroPag != '0000000000' AND B.cIdCate LIKE 'PG%'
               AND to_char(A.dFecha, 'YYYY-MM') = '{$this->paData['CPERIOD']}'
               GROUP BY E.cDescri, C.cDescri, E.cIdCate ORDER BY E.cIdCate";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->laDatos[] = ['CIDCATE'=>$laFila[0],'CCATEGO'=> $laFila[1], 'NMONTO'=> $laFila[2], 'CESTADO'=> $laFila[3]];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "SIN REGISTROS";
         return false;
      }
      $lcSql = "SELECT SUM(A.nMonto) FROM B03MDEU A
               INNER JOIN B03DDEU B ON B.CIDDEUD = A.CIDDEUD
               INNER JOIN B03TDOC C ON C.CIDCATE = B.CIDCATE
               WHERE A.CNROPAG != '0000000000' AND B.cIdCate LIKE 'PG%' AND A.CESTADO IN ('B','C')
               AND to_char(A.dFecha, 'YYYY-MM') = '{$this->paData['CPERIOD']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (isset($laFila[0]) or !empty($laFila[0])){
         $this->laData = ['CCENCOS'=> 'CC1', 'CDESCRI'=> 'DEFINIR A QUE CENTRO DE COSTO','NMONTO'=> $laFila[0]];
      } else{
         $this->laData = ['CCENCOS'=> 'CC1', 'CDESCRI'=> '*** ERROR ***', 'NMONTO'=> 0.00];
      }
      return true;
   }

   protected function mxConcepAgrupXls() {
      //$loXls = new CXls();
      $loXls = new CSpreadSheet();
      $loXls->openXlsx('Xls/Bol1110_ALL.xlsx');
      // distribucion
      $loXls->sendXls(0, 'C', 1, 'FECHA: '.date("Y-m-d"));
      $loXls->sendXls(0, 'C', 2, 'PERIODO: '.$this->paData['CPERIOD']);
      $loXls->sendXls(0, 'A', 4, $this->laData['CCENCOS']);
      $loXls->sendXls(0, 'B', 4, $this->laData['CDESCRI']);
      $loXls->sendXls(0, 'C', 4, $this->laData['NMONTO']);
      // tramites
      $loXls->sendXls(1, 'D', 1, 'FECHA: '.date("Y-m-d"));
      $loXls->sendXls(1, 'D', 2, 'PERIODO: '.$this->paData['CPERIOD']);
      $i = 4; 
      foreach ($this->laDatos as $laFila) {
         $loXls->sendXls(1, 'A', $i, $laFila['CIDCATE']);
         $loXls->sendXls(1, 'B', $i, $laFila['CCATEGO']);
         $loXls->sendXls(1, 'C', $i, $laFila['NMONTO']);
         $loXls->sendXls(1, 'D', $i, $laFila['CESTADO']);
         $i++;
      }
      $loXls->closeXls();
      $this->pcFile = $loXls->pcFilXls;
      return true;
   }
   
   # -----------------------------------------------------------------------
   # Hoja de calculo de ingresos/egresos por conceptos segun Centro de Costo
   # 2021-02-08 BOL Creacion
   # -----------------------------------------------------------------------
   public function omSaldoCenCosXls() { 
      $llOk = $this->mxValParamSaldoCenCos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosSaldoCenCos($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxSaldoCenCosXls();
      return $llOk;
   }
   
   protected function mxValParamSaldoCenCos() {
      if (!$this->mxValParam()){
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 7) {
         $this->pcError = "PERIODO (aaaa-mm) NO DEFINIDO";
         return false;
      }
      return true;
   }
   
   protected function mxDatosSaldoCenCos($p_oSql) {
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], -2);
      // Trae datos del centro de costo
      $lcSql = "SELECT cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if(!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = "CENTRO DE COSTO NO EXISTE";
         return false;
      }
      $this->laData['CCENDES'] = $laFila[0];
      // Trae datos agrupados por concepto
      $lcSql = "SELECT B.cIdInEg, ABS(SUM(A.nMonto)) FROM D02DCOS A
                INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                INNER JOIN D02TINE C ON C.cIdInEg = B.cIdInEg
                WHERE B.cPeriod = '$lcPeriod' AND B.cEstado = 'A' AND A.cCenCos = '{$this->paData['CCENCOS']}'
                GROUP BY B.cIdInEg ORDER BY B.cIdInEg";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcIdInEg = substr($laFila[0], 0, 2);
         $lcSql = "SELECT cIdInEg, cDescri FROM D02TINE WHERE SUBSTRING(cIdInEg, 1,2) = '$lcIdInEg'
                   AND SUBSTRING(cIdInEg, 3,2) = '00'";
         $R2 = $p_oSql->omExec($lcSql);
         $laFila2 = $p_oSql->fetch($R2);
         if(!isset($laFila2[0]) or empty($laFila2[0])){
            $this->pcError = "CABECERA DEL CONCEPTO ".$laFila[0]." NO ESTA DEFINIDA";
            return false;
         }
         $this->laDatos[] = ['CIDINEG'=>$laFila2[0],'CCONCEP'=> $laFila2[1], 'NMONTO'=> $laFila[1]];
      }
      // Trae datos detallados por concepto
      $lcSql = "SELECT C.cIdInEg, C.cDescri, ABS(A.nMonto), D.cDescri FROM D02DCOS A
                INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                INNER JOIN D02TINE C ON B.cIdInEg = C.cIdInEg
                LEFT JOIN V_S01TTAB D ON D.cCodigo = A.cTipo AND D.cCodTab = '312'
                WHERE B.cPeriod = '$lcPeriod' AND B.cEstado = 'A' AND A.cCenCos = '{$this->paData['CCENCOS']}'
                ORDER BY C.cTipo DESC, B.cIdInEg";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)){
         $this->paDatos[] = ['CIDINEG'=>$laFila[0],'CCONCEP'=> $laFila[1], 'NMONTO'=> $laFila[2], 'CTIPO'=> $laFila[3]];
      }
      return true;
   }
   
   protected function mxSaldoCenCosXls() {
      //$loXls = new CXls();
      $loXls = new CSpreadSheet();
      $loXls->openXlsx('Xls/Cnt5150.xlsx');
      // Hoja Trabajo
      $loXls->sendXls(0, 'B', 1, $this->laData['CCENDES']);
      $loXls->sendXls(0, 'C', 1, 'PERIODO: '.$this->paData['CPERIOD']);
      $lnFila = 3;
      foreach ($this->laDatos as $laData) {
         $loXls->sendXls(0, 'A', $lnFila, $laData['CIDINEG']);
         $loXls->sendXls(0, 'B', $lnFila, $laData['CCONCEP']);
         $loXls->sendXls(0, 'C', $lnFila, $laData['NMONTO']);
         $lnFila ++;
      }
      // Hoja Resumen
      $loXls->sendXls(1, 'B', 1, $this->laData['CCENDES']);
      $loXls->sendXls(1, 'D', 1, 'PERIODO: '.$this->paData['CPERIOD']);
      $lnFila = 3;
      foreach ($this->paDatos as $laData) {
         $loXls->sendXls(1, 'A', $lnFila, $laData['CIDINEG']);
         $loXls->sendXls(1, 'B', $lnFila, $laData['CCONCEP']);
         $loXls->sendXls(1, 'C', $lnFila, $laData['NMONTO']);
         $loXls->sendXls(1, 'D', $lnFila, $laData['CTIPO']);
         $lnFila ++;
      }
      $loXls->closeXls();
      $this->pcFile = $loXls->pcFilXls;
      return true;
   }
   
   # ------------------------------------------------------------------
   # Hoja de calculo de ingresos/egresos por concepto
   # 2021-02-10 BOL Creacion
   # ------------------------------------------------------------------
   public function omIngEgConceptoXls() { 
      $llOk = $this->mxValParamIngEgConcepto();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosIngEgConcepto($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxIngEgConceptoXls();
      return $llOk;
   }
   
   protected function mxValParamIngEgConcepto() {
      if (!$this->mxValParam()){
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 7) {
         $this->pcError = "PERIODO (aaaa-mm) NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CIDINEG'])) {
         $this->pcError = "CONCEPTO NO DEFINIDO";
         return false;
      }
      return true;
   }
   
   protected function mxDatosIngEgConcepto($p_oSql) {
      $lcPeriod = substr($this->paData['CPERIOD'], 0, 4).substr($this->paData['CPERIOD'], -2);
      // Trae saldo contable de la cuenta configurada
      $lcSql = "SELECT B.cDescri, A.cCtaCnt FROM D02MINE A
                INNER JOIN D02TINE B ON A.cIdInEg = B.cIdInEg
                WHERE A.cIdInEg = '{$this->paData['CIDINEG']}' AND A.cPeriod = '$lcPeriod' AND A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if(!isset($laFila[0]) or empty($laFila[0])){
         $this->pcError = "ERROR NO EXISTE CONCEPTO";
         return false;
      }
      $this->laData['CCONCEP'] = $laFila[0];
      $laCtaCnt = explode(",", $laFila[1]);
      foreach ($laCtaCnt as $lcCtaCnt){
         $lnMonto = $this->mxSaldosContables($p_oSql, $lcCtaCnt, '1');      
         if (!isset($lnMonto) or empty($lnMonto)){
            $this->pcError = "NO HAY SALDO EN CUENTA ".$lcCtaCnt;
            return false;
         }
         $lcSql = "SELECT cDescri FROM D01MCTA WHERE cCtaCnt = '$lcCtaCnt'";
         $RS = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($RS);
         if(!isset($laFila[0]) or empty($laFila[0])){
            $this->pcError = "CUENTA CONTABLE ".$lcCtaCnt." NO EXISTE";
            return false;
         }
         $this->laDatos[] = ['CCENCOS'=> $lcCtaCnt, 'CDESCRI'=> $laFila[0], 'NMONTO'=> $lnMonto, 'CTIPO'=> 'CUENTA CONTABLE']; 
      }
      // Trae datos de los ingresos/egresos segun id de concepto
      $lcSql = "SELECT A.cCenCos, C.cDescri, A.nMonto, D.cDescri FROM D02DCOS A 
                INNER JOIN D02MINE B ON B.cCodigo=A.cCodigo
                INNER JOIN S01TCCO C ON C.cCenCos=A.cCenCos
                LEFT JOIN V_S01TTAB D ON D.cCodigo = A.cTipo AND D.cCodTab = '312'
                WHERE B.cIdInEg = '{$this->paData['CIDINEG']}' AND B.cPeriod = '$lcPeriod' ORDER BY C.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)){
         $this->paDatos[] = ['CCENCOS'=> $laFila[0], 'CDESCRI'=> $laFila[1], 'NMONTO'=> $laFila[2], 'CTIPO'=> $laFila[3]]; 
      }
      if (count($this->paDatos) == 0){
         $this->pcError = "NO HAY REGISTROS";
         return false;
      }
      return true;
   }
   
   protected function mxIngEgConceptoXls() {
      //$loXls = new CXls();
      $loXls = new CSpreadSheet();
      $loXls->openXlsx('Xls/Cnt5160.xlsx');
      // Hoja Trabajo
      $loXls->sendXls(0, 'A', 3, $this->paData['CIDINEG'].': '.$this->laData['CCONCEP']);
      $loXls->sendXls(0, 'D', 1, 'FECHA: ');
      $loXls->sendXls(0, 'D', 2, 'PERIODO: '.$this->paData['CPERIOD']);
      $lnFila = 5;
      foreach ($this->paDatos as $laData) {
         $loXls->sendXls(0, 'A', $lnFila, $laData['CCENCOS']);
         $loXls->sendXls(0, 'B', $lnFila, $laData['CDESCRI']);
         $loXls->sendXls(0, 'C', $lnFila, $laData['NMONTO']);
         $loXls->sendXls(0, 'D', $lnFila, $laData['CTIPO']);
         $lnFila ++;
      }
      $loXls->closeXls();
      $this->pcFile = $loXls->pcFilXls;
      return true;
   }
   
   # ---------------------------------------------------
   # Init Proceso que distribuye ingresos/egresos
   # 2021-03-23 BOL Creacion
   # ---------------------------------------------------
   public function omInitDistribucionIngEgr() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '007');// 007:consulta reportes CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   
   # -------------------------------------------------------------
   # Init Permisos y definicion de consulta por centros de costoV2
   # 2021-06-07 BOL Creacion
   # -------------------------------------------------------------
   public function omInitDetalleUsuarioCC(){
   	  $llOk = $this->mxValInitDetalleUsuarioCC(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxValParamUsuario($loSql, '008');//008:administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitDetalleUsuarioCC($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValInitDetalleUsuarioCC() { 
      if (!$this->mxValParam()){
         return false;
      }
      return true;
  }
  
  protected function mxInitDetalleUsuarioCC($p_oSql) { 
  	  $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE cEstado = 'A' ORDER BY cNombre";
	  $RS = $p_oSql->omExec($lcSql);
	  while($laFila = $p_oSql->fetch($RS)){
	  	 $this->laDatos[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => str_replace("/", " ",$laFila[1])];
	  }
	  $this->paDatos = $this->laDatos;
	  // Trae datos de los centros de costo
	  $lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO WHERE cEstado = 'A' AND cClase NOT LIKE 'Z%' ORDER BY cClase";
     $RS = $p_oSql->omExec($lcSql);
     while($laFila = $p_oSql->fetch($RS)){
        $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[2].'-'.$laFila[1]];
     }
  	  return true;
   }
  
   # -------------------------------------------------------------
   # Agregar Centro Costo
   # 2021-06-07 BOL Creacion
   # -------------------------------------------------------------
   public function omAgregarCC(){
   	  $llOk = $this->mxValAgregarCC(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxAgregarCC($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValAgregarCC() { 
      if (!$this->mxValParam()){
         return false;
      }
      return true;
   }
  
   protected function mxAgregarCC($p_oSql) { 
     // Trae detalles sobre CC.
     $lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO WHERE cCenCos = '{$this->paData['CCOSCEN']}'";
     $RS = $p_oSql->omExec($lcSql);
     while($laFila = $p_oSql->fetch($RS)){
        $this->paData = ['CCOSCEN' => $laFila[0], 'CDESCRI' => $laFila[2].'-'.$laFila[1], 'CESTADO'=>'A', 'CCLASE'=>$laFila[2]];
     }
  	  return true;
   }
  
   # -------------------------------------------------------------
   # Agregar Centro Costo
   # 2021-06-07 BOL Creacion
   # -------------------------------------------------------------
   public function omGrabarPermisoCC(){
   	  $llOk = $this->mxValGrabarPermisoCC(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxGrabarPermisoCC($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValGrabarPermisoCC() { 
      if (!$this->mxValParam()){
         return false;
      } elseif (count($this->paDatos) == 0) {
         $this->pcError = "NINGUN CENTRO DE COSTO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUSUARI']) or empty($this->paData['CUSUARI'])) {
         $this->pcError = "USUARIO NO DEFINIDO";
         return false;
      } 
      return true;
   }
  
   protected function mxGrabarPermisoCC($p_oSql) { 
     $lcCentro = '';
     $lfirst = true;
     foreach ($this->paDatos as $laFila){
        if ($lfirst) {
           $lcCentro.= $laFila['CCOSCEN'];
           $lfirst = false;
        } else{
           $lcCentro.= ','.$laFila['CCOSCEN'];
        }
     }
     // Busca si tiene permisos para sobreescribir o crear nuevo
     $lcSql = "SELECT nSerial FROM S02DCON WHERE cTipo = '004' AND cUsuAri LIKE '%{$this->paData['CUSUARI']}%' AND cEstado = 'A'";
     $RS = $p_oSql->omExec($lcSql);
     $laFila = $p_oSql->fetch($RS);
     if (empty($laFila[0])) {
        $this->paData['NSERIAL'] = '*';
     } else{
        $this->paData['NSERIAL'] = $laFila[0];
     }
     if ($this->paData['NSERIAL'] != '*') {
        $lcSql = "UPDATE S02DCON SET cCentro = '$lcCentro', cUsuari = '{$this->paData['CUSUARI']}' WHERE NSERIAL = {$this->paData['NSERIAL']} AND cTipo = '004'";
     }else {
        $lcSql = "INSERT INTO S02DCON (cTipo, cEstado, cDescri, cCentro, cUsuari, cUsuCod) VALUES 
                 ('004', 'A', 'PERMISOS', '$lcCentro', '{$this->paData['CUSUARI']}', '{$this->paData['CUSUCOD']}');";
     }
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
       $this->pcError = "ERROR AL GRABAR NUEVO REGISTRO";
       return false;
     }
  	  return true;
   }
   
   # -------------------------------------------------------------
   # Buscar Centro Costo segun permisos
   # 2021-06-07 BOL Creacion
   # -------------------------------------------------------------
   public function omBuscarCCPermisos(){
   	  $llOk = $this->mxValBuscarCCPermisos(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxBuscarCCPermisos($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValBuscarCCPermisos() { 
      if (!$this->mxValParam()){
         return false;
      }
      return true;
  }
  
   protected function mxBuscarCCPermisos($p_oSql) { 
     $lcSql = "SELECT nSerial, cCentro, cEstado FROM S02DCON WHERE cTipo = '004' AND cUsuAri LIKE '%{$this->paData['CUSUARI']}%' AND cEstado = 'A'";
     $R1 = $p_oSql->omExec($lcSql);
     while ($laFila = $p_oSql->fetch($R1)) {
  	     $laCenCos = explode(",", $laFila[1]);// Recupera los centros de costo por usuario
        foreach ($laCenCos as $lcCenCos){
           $lcSql = "SELECT cClase, cDescri, cCenCos FROM S01TCCO WHERE cCenCos = '$lcCenCos'";
           $R2 = $p_oSql->omExec($lcSql);
           while ($laFila2 = $p_oSql->fetch($R2)) {
              $lcDescri = $laFila2[0].'-'.$laFila2[1];
              $this->paDatos[] = ['CCOSCEN'=>$lcCenCos, 'CDESCRI'=>$lcDescri, 'CESTADO'=>$laFila[2]];
           }
        }
     }
  	  return true;
   }
  
   # -------------------------------------------------------------
   # Init Permisos y definicion de consulta por  centros de costo
   # 2021-03-19 FLC Creacion
   # -------------------------------------------------------------
   public function omInitDetalleUsuario(){
   	  $llOk = $this->mxValInitDetalleUsuario(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxValParamUsuario($loSql, '008');//008:administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitDetalleUsuario($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValInitDetalleUsuario() { 
      if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
         $this->pcError = "USUARIO NO VALIDO"; 
         return false; 
      }
      return true;
  }
  
  protected function mxInitDetalleUsuario($p_oSql) { 
  	  $lcSql = "SELECT nSerial, cDescri, cestado, cCentro, cUsuAri FROM S02DCON WHERE cTipo = '001' ORDER BY nSerial";
	  $RS = $p_oSql->omExec($lcSql);
	  while($laFila = $p_oSql->fetch($RS)){
	  	 $this->laDatos[] = ['NSERIAL' => $laFila[0], 'CDESCRI' => $laFila[1]];
	  }
	  $this->paData = $this->laDatos;
  	  return true;
  }
   
  # -------------------------------------------------------------
  # Mantenimiento Permisos consulta segun centros de costo
  # 2021-03-19 FLC Creacion
  # -------------------------------------------------------------
  public function omCentroCosto(){
   	  $llOk = $this->mxValCentroCosto(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxCentroCosto($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValCentroCosto() { 
      if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
         $this->pcError = "USUARIO NO VALIDO"; 
         return false; 
      }
      return true;
  }
  
  protected function mxCentroCosto($p_oSql) { 
  	$lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO WHERE cEstado = 'A' ORDER BY cClase";
    //print_r($this->paData);
	  $RS = $p_oSql->omExec($lcSql);
	  while($laFila = $p_oSql->fetch($RS)){
	  	 $this->laDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[2].'-'.$laFila[1]];
	  }
	  $datos = explode(",", $this->paData['CCENCOS']);
    foreach ($datos as $key => $value) {
      $datos[$key] = "'".$value."'";
    }
    $datos = implode(",", $datos);
    $datos = str_replace(" ", "", $datos);
    $lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO WHERE cCenCos IN ({$datos})";
    $RS = $p_oSql->omExec($lcSql);
    while($laFila = $p_oSql->fetch($RS)){
       $this->laData[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[2].'-'.$laFila[1]];
    }
    $datos = explode(",", $this->paData['CUSUARI']);
    foreach ($datos as $key => $value) {
      $datos[$key] = "'".$value."'";
    }
    $datos = implode(",", $datos);
    $datos = str_replace(" ", "", $datos);
    $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE cCodUsu IN ({$datos})";
    $RS = $p_oSql->omExec($lcSql);
    while($laFila = $p_oSql->fetch($RS)){
       $this->paDatos[] = ['CCODIGO' => $laFila[0], 'CNOMBRE' => str_replace("/", " ", $laFila[1])];
    }
	  $this->paData = ['CCECOS1' => $this->laData, 'CCECOS2' => $this->laDatos, 'CUSUARI' => $this->paDatos];
  	  return true;
  }
  
  # -------------------------------------------------------------
  # Busca al usuario responsable para Mantenimiento Permisos 
  # 2021-03-19 FLC Creacion
  # -------------------------------------------------------------
  public function omBuscarResponsable(){
   	  $llOk = $this->mxValBuscarResponsable(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxBuscarResponsable($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValBuscarResponsable() { 
   	  $this->paData['CCODRES'] = strtoupper($this->paData['CCODRES']);
      if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
         $this->pcError = "USUARIO NO VALIDO"; 
         return false; 
      } /*elseif (!isset($this->paData['CCODRES']) and strlen($this->paData['CCODRES'])) != 0) { 
         $this->pcError = "USUARIO NO VALIDO"; 
         return false; 
      }*/
      return true;
  }
  
  protected function mxBuscarResponsable($p_oSql) { 
  	  $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE (cCodUsu = '{$this->paData['CCODRES']}' OR cNombre LIKE '%{$this->paData['CCODRES']}%' OR cNroDni = '{$this->paData['CCODRES']}')";
	  $RS = $p_oSql->omExec($lcSql);
	  while($laFila = $p_oSql->fetch($RS)){
	  	 $this->laDatos[] = ['CCODIGO' => $laFila[0], 'CNOMBRE' => str_replace("/", " ", $laFila[1])];
	  }
	  $this->paData = $this->laDatos;
  	  return true;
  }
  
  # -------------------------------------------------------------
  # Graba para Mantenimiento Permisos Consulta CC.
  # 2021-03-19 FLC Creacion
  # -------------------------------------------------------------  
  public function omGrabarD02DUSU(){
      $llOk = $this->mxValGrabarD02DUSU(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxGrabarD02DUSU($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValGrabarD02DUSU() { 
      $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
      if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
         $this->pcError = "USUARIO NO VALIDO"; 
         return false; 
      } else if (!isset($this->paData['CDESCRI']) and strlen($this->paData['CDESCRI']) == 0) { 
         $this->pcError = "FALTA DESCRIPCION"; 
         return false; 
      } 
      return true;
  }
  
  protected function mxGrabarD02DUSU($p_oSql) { 
    $laCenCos = explode(",", $this->paData['CCENTRO']);
    $this->paData['CCENTRO'] = '';
    foreach ($laCenCos as $key => $value) {
       $value = str_replace(" ", "", $value);
       if ($value != '') {
          if($this->paData['CCENTRO'] == ''){
             $this->paData['CCENTRO'] = $value;
          } else {
             $this->paData['CCENTRO'] = $this->paData['CCENTRO'].",".$value;
          }
       }       
    }
    $laUsuari = explode(",", $this->paData['CUSUARI']);
    $this->paData['CUSUARI'] = '';
    foreach ($laUsuari as $key => $value) {
       $value = str_replace(" ", "", $value);
       if ($value != '') {
          if($this->paData['CUSUARI'] == ''){
             $this->paData['CUSUARI'] = $value;
          } else {
             $this->paData['CUSUARI'] = $this->paData['CUSUARI'].",".$value;
          }
       }       
    }
    if ($this->paData['NSERIAL'] != '') {
       $lcSql = "UPDATE S02DCON SET CESTADO = '{$this->paData['CESTADO']}',CDESCRI = '{$this->paData['CDESCRI']}', CCENTRO = '{$this->paData['CCENTRO']}', CUSUARI = '{$this->paData['CUSUARI']}' WHERE NSERIAL = {$this->paData['NSERIAL']};";
    }else {
       $lcSql = "INSERT INTO S02DCON (cTipo, cEstado, cDescri, cCentro, cUsuari, cUsuCod) VALUES 
                ('001','{$this->paData['CESTADO']}', '{$this->paData['CDESCRI']}', '{$this->paData['CCENTRO']}', '{$this->paData['CUSUARI']}', '{$this->paData['CUSUCOD']}');";
    }
    $llOk = $p_oSql->omExec($lcSql);
    if (!$llOk) {
       $this->pcError = "ERROR AL GRABAR D02DUSU*";
       return false;
    }
    return true;
  }
  
  # -------------------------------------------------------------
  # Busca cuenta contable
  # 2021-04-07 FLC Creacion
  # -------------------------------------------------------------  
  public function omBuscarCuentaContable(){
     $llOk = $this->mxValBuscarCuentaContable(); 
     if (!$llOk) { 
        return false; 
     } 
     $loSql = new CSql(); 
     $llOk = $loSql->omConnect(); 
     if (!$llOk) { 
        $this->pcError = $loSql->pcError; 
        return false; 
     } 
     $llOk = $this->mxBuscarCuentaContable($loSql); 
     if (!$llOk) { 
        return false; 
     } 
     $loSql->omDisconnect(); 
     return $llOk; 
  }

  protected function mxValBuscarCuentaContable() { 
     if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
        $this->pcError = "USUARIO NO VALIDO"; 
        return false; 
     } elseif (!isset($this->paData['CBUSCAR']) or empty($this->paData['CBUSCAR']) or strlen($this->paData['CBUSCAR']) > 12) { 
        $this->pcError = "DEBE SER NUMERICO Y TENER 2 O MAS CARACTERES"; 
        return false; 
     } elseif (!isset($this->paData['CANOPRO']) or empty($this->paData['CANOPRO']) or strlen($this->paData['CANOPRO']) != 4 ) { 
        $this->pcError = "DEBE INGRESAR EL AÑO DEL PLAN CONTABLE A CONSULTAR"; 
        return false; 
     }
     return true;
  }
  
  protected function mxBuscarCuentaContable($p_oSql) {
      $this->paData['CBUSCAR'] = str_replace(" ", "", $this->paData['CBUSCAR']);
      $lcSql = "SELECT TRIM(A.CodCta), A.Descri, B.cCenCos, B.cDescri FROM D10MCTA A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.CodCta LIKE '{$this->paData['CBUSCAR']}%' AND A.AnoPro = '{$this->paData['CANOPRO']}' ORDER BY A.CodCta";
      $RS = $p_oSql->omExec($lcSql);
      while($laFila = $p_oSql->fetch($RS)){
         $this->laDatos[] = ['CCTACNT' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCENCOS' => $laFila[2], 'CDESCOS' => $laFila[3], 'CANOPRO' => $this->paData['CANOPRO']];
      }
      $this->paData = $this->laDatos;
      return true;
  }
  
  # -------------------------------------------------------------
  # Busca centro de costo
  # 2021-04-07 FLC Creacion
  # -------------------------------------------------------------  
  public function omBuscarCentroCosto(){
     $llOk = $this->mxValBuscarCentroCosto(); 
     if (!$llOk) { 
        return false; 
     } 
     $loSql = new CSql(); 
     $llOk = $loSql->omConnect(); 
     if (!$llOk) { 
        $this->pcError = $loSql->pcError; 
        return false; 
     } 
     $llOk = $this->mxBuscarCentroCosto($loSql); 
     if (!$llOk) { 
        return false; 
     } 
     $loSql->omDisconnect(); 
     return $llOk; 
  }

  protected function mxValBuscarCentroCosto() { 
     if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
        $this->pcError = "USUARIO NO VALIDO"; 
        return false; 
     } elseif (!isset($this->paData['CBUSCAR']) and strlen($this->paData['CBUSCAR']) > 6) { 
        $this->pcError = "DEBE SER NUMERICO Y TENER 2 O MAS CARACTERES"; 
        return false; 
     }
     return true;
  }
  
  protected function mxBuscarCentroCosto($p_oSql) { 
      $this->paData['CBUSCAR'] = str_replace(" ", "%", $this->paData['CBUSCAR']);
      $this->paData['CBUSCAR'] = strtoupper($this->paData['CBUSCAR']);
      $lcSql = "SELECT CCENCOS, CDESCRI FROM S01TCCO WHERE CDESCRI LIKE '%{$this->paData['CBUSCAR']}%'";
      $RS = $p_oSql->omExec($lcSql);
      while($laFila = $p_oSql->fetch($RS)){
         $this->laDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      $this->paDatos = $this->laDatos;
      return true;
  }
  
  # -------------------------------------------------------------
  # Graba actualizacion de la relacion cuenta contable con cc.
  # 2021-04-07 FLC Creacion
  # -------------------------------------------------------------  
  public function omGrabarCuentaCC(){
     $llOk = $this->mxValGrabarCuentaCentroCosto(); 
     if (!$llOk) { 
        return false; 
     } 
     $loSql = new CSql(); 
     $llOk = $loSql->omConnect(); 
     if (!$llOk) { 
        $this->pcError = $loSql->pcError; 
        return false; 
     } 
     $llOk = $this->mxGrabarCuentaCentroCosto($loSql); 
     if (!$llOk) { 
        return false; 
     } 
     $loSql->omDisconnect(); 
     return $llOk; 
  }

  protected function mxValGrabarCuentaCentroCosto() { 
     if (!isset($this->paData['CUSUCOD']) and strlen($this->paData['CUSUCOD']) != 4) { 
        $this->pcError = "USUARIO NO VALIDO"; 
        return false; 
     } elseif (!isset($this->paData['CCENCOS']) and strlen($this->paData['CCENCOS']) != 3) { 
        $this->pcError = "USUARIO NO VALIDO"; 
        return false; 
     } elseif (!isset($this->paData['CANOPRO']) or empty($this->paData['CANOPRO']) or strlen($this->paData['CANOPRO']) != 4 ) { 
        $this->pcError = "DEBE INGRESAR EL AÑO DEL PLAN CONTABLE A CONSULTAR"; 
        return false; 
     }
     return true;
  }
  
  protected function mxGrabarCuentaCentroCosto($p_oSql) {
      $lcSql = "UPDATE D10MCTA SET cCenCos = '{$this->paData['CCENCOS']}' WHERE CodCta = '{$this->paData['CCTACNT']}'
                AND AnoPro = '{$this->paData['CANOPRO']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR AL GRABAR LA CENTRO DE COSTO';
         return false;
      }
      return true;
  }
  
   # ---------------------------------------------------
   # Init Mantenimiento tabla modulo
   # 2021-05-12 BOL Creacion
   # ---------------------------------------------------
   public function omInitMntModulo() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '008'); //008: administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitModulo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitModulo($p_oSql) {
      // Trae tabla del modulo
      $lcSql = "SELECT A.cModulo, TRIM(A.cDescri), B.cDescri FROM S01TMOD A 
                INNER JOIN V_S01TTAB B ON B.cCodtab = '041' AND B.cCodigo = A.cEstado ORDER BY A.cModulo";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CMODULO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE MODULO";
         return false;
      }
      return true;
   }
   
   # --------------------------------------------------
   # Trae datos del registro tabla modulo
   # 2021-05-19 BOL Creacion
   # --------------------------------------------------
   public function omDatosModulo() {
      $llOk = $this->mxValDatosModulo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosModulo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDatosModulo() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }elseif (!isset($this->paData['CMODULO'])) {
         $this->pcError = "MODULO NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDatosModulo($p_oSql) {
      $lcSql = "SELECT TRIM(cDescri), cEstado FROM S01TMOD WHERE cModulo = '{$this->paData['CMODULO']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "MODULO NO EXISTE";
         return false;
      }
      $this->paData = ['CMODULO' => $this->paData['CMODULO'], 'CDESCRI' => $laFila[0], 'CESTADO' => $laFila[1]];
      return true;
   }
   
   # --------------------------------------------------
   # Grabar datosdel modulo 
   # 2021-05-19 BOL Creacion
   # --------------------------------------------------
   public function omGrabarModulo() {
      $llOk = $this->mxValGrabarModulo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarModulo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValGrabarModulo() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['CMODULO'])) {
         $this->pcError = "MODULO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDESCRI'])) {
         $this->pcError = "DESCRIPCION NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO'])) {
         $this->pcError = "ESTADO NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxGrabarModulo($p_oSql) {
      $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
      if ($this->paData['CMODULO'] == '*'){
        // Buscar ultimo codigo para generar nuevo
        $lcSql = "SELECT cModulo FROM S01TMOD ORDER BY cModulo DESC LIMIT 1";
        $RS = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($RS);
        if (empty($laFila[0])) {
           $lcModulo = '000'; // Primer registro
        }
        $lcModulo = substr('00'.strval((int)$laFila[0] + 1), -3);
        // Nueva opcion
        $lcSql = "INSERT INTO S01TMOD (cModulo, cDescri, cEstado, cUsuCod) VALUES ('$lcModulo', '{$this->paData['CDESCRI']}', 'A', '{$this->paData['CUSUCOD']}')";
        $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
           $this->pcError = 'ERROR AL INGRESAR NUEVA REGISTRO';
           return false;
        } 
      } else{
        // actualiza agrupacion
        $lcSql = "UPDATE S01TMOD SET cDescri = '{$this->paData['CDESCRI']}', cEstado = '{$this->paData['CESTADO']}', cUsuCod = '{$this->paData['CUSUCOD']}', tmodifi = NOW() WHERE cModulo = '{$this->paData['CMODULO']}'";
        $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
           $this->pcError = 'ERROR AL ACTUALIZAR REGISTRO';
           return false;
        }
      } 
      return true;
   }
   
   # ---------------------------------------------------
   # Init Mantenimiento tabla agrupacion unidades acad.
   # 2021-05-20 BOL Creacion
   # ---------------------------------------------------
   public function omInitMntAgrupacionAcad() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '008');//008:administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitAgrupacionAcademicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitAgrupacionAcademicas($p_oSql) {
      // Trae tabla de agrupaciones
      $lcSql = "SELECT A.nSerial, C.cDescri, A.cUniAca||'-'||D.cNomUni, A.cAcaUni||'-'||E.cNomUni, B.cDescri FROM S02DUAC A 
                INNER JOIN V_S01TTAB B ON B.cCodtab = '041' AND B.cCodigo = A.cEstado 
                INNER JOIN V_S01TTAB C ON C.cCodtab = '313' AND C.cCodigo = A.cTipo 
                INNER JOIN S01TUAC D ON D.cUniAca = A.cUniAca
                INNER JOIN S01TUAC E ON E.cUniAca = A.cAcaUni ORDER BY A.cTipo, A.cUniAca";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CTIPO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CACAUNI' => $laFila[3], 'CESTADO' => $laFila[4]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE MODULO";
         return false;
      }
      // Trae tabla de unidades academicas
      $lcSql = "SELECT cUniAca, cNomUni FROM S01TUAC WHERE CESTADO = 'A' ORDER BY cNomUni";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUniAca[] = ['CUNIACA' => $laFila[0], 'CDESCRI' => $laFila[0].'-'.$laFila[1]];
      }
      if (count($this->paUniAca) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE UNIDADES ACADEMICAS";
         return false;
      }
      // Trae tabla de tipo de agrupacion
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab='313'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipo[] = ['CTIPO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paTipo) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE TIPO [313]";
         return false;
      }
      return true;
   }
   
   # --------------------------------------------------
   # Trae datos de la agrupacion acad. seleccionada
   # 2021-05-19 BOL Creacion
   # --------------------------------------------------
   public function omDatosAgrupacionAcad() {
      $llOk = $this->mxValDatosAgrupacionAcad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosAgrupacionAcad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDatosAgrupacionAcad() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }elseif (!isset($this->paData['NSERIAL'])) {
         $this->pcError = "AGRUPACION NO DEFINIDA";
         return false;
      } 
      return true;
   }

   protected function mxDatosAgrupacionAcad($p_oSql) {
      $lcSql = "SELECT nSerial, cUniAca, cAcaUni, cTipo, cEstado FROM S02DUAC WHERE nSerial = '{$this->paData['NSERIAL']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "AGRUPACION NO EXISTE";
         return false;
      }
      $this->paData = ['NSERIAL' => $laFila[0], 'CUNIACA' => $laFila[1], 'CACAUNI' => $laFila[2], 'CTIPO' => $laFila[3], 'CESTADO' => $laFila[4]];
      return true;
   }
   
   # -------------------------------------------------------------
   # Graba actualizacion de la agrupacion unidades academicas
   # 2021-04-07 FLC Creacion
   # -------------------------------------------------------------  
   public function omGrabarAgrupacionAcademica(){
      $llOk = $this->mxValGrabarAgrupacionAcademica(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxGrabarAgrupacionAcademica($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

  protected function mxValGrabarAgrupacionAcademica() { 
     $llOk = $this->mxValParam();
     if (!$llOk) {
        return false;
     } elseif (!isset($this->paData['NSERIAL'])) { 
        $this->pcError = "DEBE INGRESAR EL CODIGO DE AGRUPACION"; 
        return false; 
     } elseif (!isset($this->paData['CUNIACA'])) { 
        $this->pcError = "DEBE INGRESAR LA UNIDAD ACADEMICA PRINCIPAL DE AGRUPACION"; 
        return false; 
     } elseif (!isset($this->paData['CACAUNI'])) { 
        $this->pcError = "DEBE INGRESAR LA UNIDAD ACADEMICA A SER AGRUPADA"; 
        return false; 
     } elseif (!isset($this->paData['CTIPO'])) { 
        $this->pcError = "DEBE INGRESAR EL TIPO DE AGRUPACION"; 
        return false; 
     } elseif (!isset($this->paData['CESTADO'])) { 
        $this->pcError = "DEBE DEFINIR EL ESTADO DE AGRUPACION"; 
        return false; 
     }
     return true;
  }
  
  protected function mxGrabarAgrupacionAcademica($p_oSql) {
      if ($this->paData['NSERIAL'] == '*'){
        // Nueva agrupacion
        $lcSql = "INSERT INTO S02DUAC (cUniAca, cAcaUni, cTipo, cEstado, cUsuCod) VALUES ('{$this->paData['CUNIACA']}', '{$this->paData['CACAUNI']}', '{$this->paData['CTIPO']}', 'A', '{$this->paData['CUSUCOD']}')";
        $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
           $this->pcError = 'ERROR AL INGRESAR AGRUPACION SOBRE UNIDAD ACADEMICA';
           return false;
        } 
      } else{
        // actualiza agrupacion
        $lcSql = "UPDATE S02DUAC SET cAcaUni = '{$this->paData['CACAUNI']}', cTipo = '{$this->paData['CTIPO']}', cEstado = '{$this->paData['CESTADO']}', cUsuCod = '{$this->paData['CUSUCOD']}', tmodifi = NOW() WHERE nSerial = '{$this->paData['NSERIAL']}'";
        $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
           $this->pcError = 'ERROR AL ACTUALIZAR AGRUPACION SOBRE UNIDAD ACADEMICA';
           return false;
        } 
      }
      return true;
  }
  
   # ---------------------------------------------------
   # Init Mantenimiento centros de costo anulados
   # 2021-06-03 BOL Creacion
   # ---------------------------------------------------
   public function omInitMntoCenCosAnulados() { 
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
      $llOk = $this->mxValParamUsuario($loSql, '008');//008:administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitMntoCenCosAnulados($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitMntoCenCosAnulados($p_oSql) {
      // Trae tabla de centro de costos
      $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri as cEstado FROM S01TCCO A
                LEFT JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='015'
                ORDER BY A.cClase";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCOSCEN' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCLASE' => $laFila[3], 'CESTADO' => $laFila[4]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE MODULO";
         return false;
      }
      // Trae tabla del estado
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '015'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipEst[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paTipEst) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE ESTADOS";
         return false;
      }
      return true;
   }
   
   # ---------------------------------------------------
   # Trae datos Mantenimiento centros de costo anulados
   # 2021-06-03 BOL Creacion
   # ---------------------------------------------------
   public function omDatosCenCosAnulados() { 
      $llOk = $this->mxValParamCenCosAnulados();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosMntoCenCosAnulados($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamCenCosAnulados() {
      if (!$this->mxValParam()) {
         return false;
      } elseif (!isset($this->paData['CCOSCEN'])) {
         $this->pcError = "CENTRO DE COSTOS NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDatosMntoCenCosAnulados($p_oSql) {
      $lcCenCos = $this->paData['CCOSCEN'];
      $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri, D.cDescri, A.cEstado FROM S01TCCO A
                INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
                INNER JOIN V_S01TTAB C ON C.cCodTab = '308' AND C.cCodigo = A.cTipEst 
                INNER JOIN V_S01TTAB D ON D.cCodTab = '309' AND D.cCodigo = A.cEstPre 
                WHERE cCenCos = '$lcCenCos'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "CENTRO DE COSTOS NO EXISTE";
         return false;
      }
      $this->paData = ['CCOSCEN' => $laFila[0], 'CDESCRI' => $laFila[1], 'CUNIACA' => $laFila[2], 'CCLASE' => $laFila[3], 'CTIPEST' => $laFila[4], 'CESTPRE' => $laFila[5], 'CESTADO' => $laFila[6]];
      return true;
   }
   
   # -------------------------------------------------------------
   # Graba actualizacion de los mantenimientos CC. anulados
   # 2021-04-07 BOL Creacion
   # -------------------------------------------------------------  
   public function omGrabarCentroCostoAnulados(){
      $llOk = $this->mxValGrabarCentroCostoAnulados(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxGrabarCentroCostoAnulados($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

  protected function mxValGrabarCentroCostoAnulados() { 
     $llOk = $this->mxValParam();
     if (!$llOk) {
        return false;
     } elseif (!isset($this->paData['CCOSCEN'])) { 
        $this->pcError = "CENTRO DE COSTO NO DEFINIDO"; 
        return false; 
     } elseif (!isset($this->paData['CESTADO'])) { 
        $this->pcError = "DEBE DEFINIR EL ESTADO DE AGRUPACION"; 
        return false; 
     }
     return true;
  }
  
  protected function mxGrabarCentroCostoAnulados($p_oSql) {
     // Actualiza el estado
     $lcSql = "UPDATE S01TCCO SET cEstado = '{$this->paData['CESTADO']}'WHERE cCenCos = '{$this->paData['CCOSCEN']}'";
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
         $this->pcError = 'ERROR AL ACTUALIZAR ESTADO DEL CENTRO DE COSTO';
         return false;
     } 
     return true;
  }
  
   # -------------------------------------------------------------
   # Busqueda de Centro de Costo Cnt5440
   # 2021-06-11 BOL Creacion
   # -------------------------------------------------------------  
   public function omBuscarCentroCostoEstado(){
      $llOk = $this->mxValBuscarCentroCostoEstado(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxBuscarCentroCostoEstado($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

  protected function mxValBuscarCentroCostoEstado() { 
     $llOk = $this->mxValParam();
     if (!$llOk) {
        return false;
     } elseif (!isset($this->paData['CBUSCAR'])) { 
        $this->pcError = "PARAMETROS DE BUSQUEDA NO DEFINIDO"; 
        return false; 
     } 
     return true;
  }
  
  protected function mxBuscarCentroCostoEstado($p_oSql) {
     $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri as cEstado FROM S01TCCO A
                LEFT JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='015'
                WHERE (A.cDescri LIKE '%{$this->paData['CBUSCAR']}%' OR A.cClase LIKE 
                '{$this->paData['CBUSCAR']}%') AND A.cCenCos != 'UNI' AND A.cClase NOT LIKE 'Z%' ORDER BY A.cClase";
     $RS = $p_oSql->omExec($lcSql);
     while ($laFila = $p_oSql->fetch($RS)) {
        $this->paDatos[] = ['CCOSCEN' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCLASE' => $laFila[3], 'CESTADO' => $laFila[4]];
     }
     return true;
  }
  
   # -------------------------------------------------------------
   # Init Menu, trae opciones que tiene acceso
   # 2021-06-11 BOL Creacion
   # -------------------------------------------------------------  
   public function omInitMenu(){
      $llOk = $this->mxValInitMenu(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxDatosInitMenu($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

  protected function mxValInitMenu() { 
     $llOk = $this->mxValParam();
     if (!$llOk) {
        return false;
     } 
     return true;
  }
  
  protected function mxDatosInitMenu($p_oSql) {
     $lcModulo = '';
     $lfirst = True;
     $lcSql = "SELECT DISTINCT cModulo FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cEstado = 'A'";
     $RS = $p_oSql->omExec($lcSql);
     while ($laFila = $p_oSql->fetch($RS)) {
        if ($lfirst) {
           $lcModulo .= $laFila[0];
           $lfirst = False;     
        } else{
           $lcModulo .= ','.$laFila[0];
        }
     }
     $this->paData = ['CMODULO' => $lcModulo];
     return true;
  }
  
  # -------------------------------------------------------------
   # Init Permisos para manteniminetos por centros de costo
   # 2021-06-14 BOL Creacion
   # -------------------------------------------------------------
   public function omInitPermisosUsuarioMantenimientoCC(){
   	  $llOk = $this->mxValInitPermisosUsuarioMantenimientoCC(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxValParamUsuario($loSql, '008');//008:administrar CC.
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitPermisosUsuarioMantenimientoCC($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValInitPermisosUsuarioMantenimientoCC() { 
      if (!$this->mxValParam()){
         return false;
      }
      return true;
  }
  
  protected function mxInitPermisosUsuarioMantenimientoCC($p_oSql) { 
  	  $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE cEstado = 'A' AND cCodUsu != '0000' ORDER BY cNombre";
	  $RS = $p_oSql->omExec($lcSql);
	  while($laFila = $p_oSql->fetch($RS)){
	  	 $this->laDatos[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => str_replace("/", " ",$laFila[1])];
	  }
	  $this->paDatos = $this->laDatos;
	  // Trae datos de los centros de costo
	  $lcSql = "SELECT cCenCos, cDescri, cClase FROM S01TCCO WHERE cEstado = 'A' AND cClase NOT LIKE 'Z%' AND cCenCos != 'UNI' ORDER BY cClase";
     $RS = $p_oSql->omExec($lcSql);
     while($laFila = $p_oSql->fetch($RS)){
        $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[2].'-'.$laFila[1]];
     }
  	  return true;
   }
   
   # -------------------------------------------------------------
   # Agregar Centro Costo
   # 2021-06-07 BOL Creacion
   # -------------------------------------------------------------
   public function omGrabarPermisoMantenimientoCC(){
   	  $llOk = $this->mxValPermisoMantenimientoCC(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxGrabarPermisoMantenimientoCC($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValPermisoMantenimientoCC() { 
      if (!$this->mxValParam()){
         return false;
      } elseif (count($this->paDatos) == 0) {
         $this->pcError = "NINGUN CENTRO DE COSTO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUSUARI']) or empty($this->paData['CUSUARI'])) {
         $this->pcError = "USUARIO NO DEFINIDO";
         return false;
      } 
      return true;
   }
  
   protected function mxGrabarPermisoMantenimientoCC($p_oSql) { 
     $lcCentro = '';
     $lfirst = true;
     foreach ($this->paDatos as $laFila){
        if ($lfirst) {
           $lcCentro.= $laFila['CCOSCEN'];
           $lfirst = false;
        } else{
           $lcCentro.= ','.$laFila['CCOSCEN'];
        }
     }
     // Busca si tiene permisos para sobreescribir o crear nuevo
     $lcSql = "SELECT nSerial FROM S02DCON WHERE cTipo = '002' AND cUsuAri LIKE '%{$this->paData['CUSUARI']}%' AND cEstado = 'A' AND TRIM(cCentro) != '*'";
     $RS = $p_oSql->omExec($lcSql);
     $laFila = $p_oSql->fetch($RS);
     if (empty($laFila[0])) {
        $this->paData['NSERIAL'] = '*';
     } else{
        $this->paData['NSERIAL'] = $laFila[0];
     }
     if ($this->paData['NSERIAL'] != '*') {
        $lcSql = "UPDATE S02DCON SET cCentro = '$lcCentro', cUsuari = '{$this->paData['CUSUARI']}' WHERE NSERIAL = {$this->paData['NSERIAL']} AND cTipo = '002'";
     }else {
        $lcSql = "INSERT INTO S02DCON (cTipo, cEstado, cDescri, cCentro, cUsuari, cUsuCod) VALUES 
                 ('002', 'A', 'CONTROL PARA MNT.CENTROS DE COSTO', '$lcCentro', '{$this->paData['CUSUARI']}', '{$this->paData['CUSUCOD']}')";
     }
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
       $this->pcError = "ERROR AL GRABAR REGISTRO";
       return false;
     }
  	  return true;
   }
   
   # -------------------------------------------------------------
   # Buscar Centro Costo segun permisos para mantenientos CC.002
   # 2021-06-14 BOL Creacion
   # -------------------------------------------------------------
   public function omBuscarMantenimientoCCPermisos(){
   	$llOk = $this->mxValBuscarMntCCPermisos(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxBuscarMntCCPermisos($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValBuscarMntCCPermisos() { 
      if (!$this->mxValParam()){
         return false;
      }
      return true;
  }
  
   protected function mxBuscarMntCCPermisos($p_oSql) { 
     $lcSql = "SELECT nSerial, cCentro, cEstado FROM S02DCON WHERE cTipo = '002' AND cUsuAri LIKE '%{$this->paData['CUSUARI']}%' AND cEstado = 'A' AND TRIM(cCentro) != '*'";
     $R1 = $p_oSql->omExec($lcSql);
     while ($laFila = $p_oSql->fetch($R1)) {
  	     $laCenCos = explode(",", $laFila[1]);// Recupera los centros de costo por usuario
        foreach ($laCenCos as $lcCenCos){
           $lcSql = "SELECT cClase, cDescri, cCenCos FROM S01TCCO WHERE cCenCos = '$lcCenCos'";
           $R2 = $p_oSql->omExec($lcSql);
           while ($laFila2 = $p_oSql->fetch($R2)) {
              $lcDescri = $laFila2[0].'-'.$laFila2[1];
              $this->paDatos[] = ['CCOSCEN'=>$lcCenCos, 'CDESCRI'=>$lcDescri, 'CESTADO'=>$laFila[2], 'CCLASE'=>$laFila2[0]];
           }
        }
     }
  	  return true;
   }
   
   # -------------------------------------------------------------
   # Buscar Centro Costo segun permisos del Usuario
   # 2021-06-16 BOL Creacion
   # -------------------------------------------------------------
   public function omBuscarCCporUsuario(){
      $llOk = $this->mxValBuscarCCporUsuario(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxBuscarCCporUsuario($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return true;
   }
   
   protected function mxValBuscarCCporUsuario() { 
      if (!$this->mxValParam()){
         return false;
      } elseif (!isset($this->paData['CBUSQUEDA']) or empty($this->paData['CBUSQUEDA'])) {
         $this->pcError = "PARAMETRO DE BUSQUEDA NO DEFINIDO";
         return false;
      }
      return true;
   }
  
   protected function mxBuscarCCporUsuario($p_oSql) { 
     $this->paDatos = [];
     // Trae centros de costo que el usuario tiene permisos
     $lcSql = "SELECT TRIM(cCentro) FROM S02DCON WHERE cTipo = '002' AND cUsuari like '%{$this->paData['CUSUCOD']}%' AND cEstado = 'A'";
     $RS = $p_oSql->omExec($lcSql);
     $laFila = $p_oSql->fetch($RS);
     if (!isset($laFila[0]) or empty($laFila[0])) {
        $this->paDatos = [];
        return true;
     }
     $laCenCos = explode(",", $laFila[0]);// Recupera los centros de costo por usuario
     foreach ($laCenCos as $lcCenCos) {
        $lcSql = "SELECT TRIM(cClase) FROM S01TCCO WHERE cCenCos = '$lcCenCos'";
        $RS = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($RS);
        if (!isset($laFila[0]) or empty($laFila[0])) {
           $this->paDatos = [];
           return true;
        }
        $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri as cEstado FROM S01TCCO A
                  LEFT JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                  LEFT JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
                  WHERE (A.cDescri LIKE '%{$this->paData['CBUSQUEDA']}%' OR A.cClase LIKE 
                  '%{$this->paData['CBUSQUEDA']}%') AND A.cCenCos != 'UNI' AND A.cEstado = 'A' AND A.cClase LIKE '{$laFila[0]}%' ORDER BY A.cClase";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila2 = $p_oSql->fetch($RS)) {
           $this->paDatos[] = ['CCOSCEN'=>$laFila2[0], 'CDESCRI'=>$laFila2[1], 'CNOMUNI'=>$laFila2[2], 'CCLASE'=>$laFila2[3], 'CESTADO'=>$laFila2[4]];
        }
     }
     return true;
   }
   
   # --------------------------------------------------
   # Init Consulta busqueda por Centro de Costos
   # 2021-08-19 BOL Creacion
   # --------------------------------------------------
   public function omInitConsultaCenCos() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '00D');// Consulta CC SIN EDICION
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitConsultaCenCos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitConsultaCenCos($p_oSql) {
      $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri, D.cDescri, E.cDescri, F.cDescri, A.cTipEst FROM S01TCCO A
                INNER JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                INNER JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
                INNER JOIN V_S01TTAB D ON D.cCodigo = A.cTipo AND D.cCodTab='048'
                INNER JOIN V_S01TTAB E ON E.cCodigo = A.cTipEst AND E.cCodTab='308'
                INNER JOIN V_S01TTAB F ON F.cCodigo = A.cEstPre AND F.cCodTab='309'
                WHERE LENGTH(A.cClase) <= 7 AND A.cClase != '' AND A.cClase NOT LIKE 'Z%'
                ORDER BY A.cClase";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcTipEst = $laFila[6];
         if ($laFila[8] === '00') {
            $lcTipEst = '';
         }
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCLASE' => $laFila[3], 'CESTADO' => $laFila[4], 'CTIPO' => $laFila[5], 'CTIPEST' => $lcTipEst, 'CESTPRE' => $laFila[7]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE CENTROS DE COSTO";
         return false;
      }
      // Trae datos de tipo de estructura
      $lcSql = "SELECT SUBSTRING(cCodigo,1,2), cDescri FROM V_S01TTAB WHERE cCodTab='308'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipEst[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paTipEst) == 0) {
         $this->pcError = "ERROR AL RECUPERAR TABLA DE TIPO DE ESTRUCTURA [308]";
         return false;
      }
      return true;
   }
   
   # -----------------------------------
   # Filtro de busqueda de Centro Costo 
   # 2021-08-19 BOL Creacion
   # -----------------------------------
   public function omFiltroCentroCosto(){
      $llOk = $this->mxValFiltroCentroCosto(); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      }
      $llOk = $this->mxFiltroCentroCosto($loSql); 
      if (!$llOk) { 
         return false; 
      } 
      $loSql->omDisconnect(); 
      return true;
   }
   
   protected function mxValFiltroCentroCosto() { 
      if (!$this->mxValParam()){
         return false;
      } elseif ((!isset($this->paData['CBUSQUEDA']) or empty($this->paData['CBUSQUEDA'])) and ($this->paData['CTIPEST'] === '00') ) {
         $this->pcError = "PARAMETRO DE BUSQUEDA NO DEFINIDO";
         return false;
      }
      return true;
   }
  
   protected function mxFiltroCentroCosto($p_oSql) { 
     $this->paDatos = [];
     $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri, D.cDescri, E.cDescri, F.cDescri, A.cTipEst FROM S01TCCO A
               INNER JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
               INNER JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
               INNER JOIN V_S01TTAB D ON D.cCodigo = A.cTipo AND D.cCodTab='048'
               INNER JOIN V_S01TTAB E ON E.cCodigo = A.cTipEst AND E.cCodTab='308'
               INNER JOIN V_S01TTAB F ON F.cCodigo = A.cEstPre AND F.cCodTab='309'
               WHERE A.cCenCos != 'UNI' AND A.cEstado = 'A' AND A.cClase NOT LIKE 'Z%'";
     if ($this->paData['CBUSQUEDA'] != '') {
        $lcSql .= " AND (A.cDescri LIKE '%{$this->paData['CBUSQUEDA']}%' OR A.cCenCos = '{$this->paData['CBUSQUEDA']}')";
     }
     if ($this->paData['CTIPEST'] != '00') {
        $lcSql .= " AND A.cTipEst = '{$this->paData['CTIPEST']}'";
     }
     $lcSql .= " ORDER BY A.cClase";
     $RS = $p_oSql->omExec($lcSql);
     while ($laFila = $p_oSql->fetch($RS)) {
        $lcTipEst = $laFila[6];
         if ($laFila[8] === '00') {
            $lcTipEst = '';
         }
        $this->paDatos[] = ['CCOSCEN'=>$laFila[0], 'CDESCRI'=>$laFila[1], 'CNOMUNI'=>$laFila[2], 'CCLASE'=>$laFila[3], 'CESTADO'=>$laFila[4], 'CTIPO' => $laFila[5], 'CTIPEST' => $lcTipEst, 'CESTPRE' => $laFila[7]];
     }
     return true;
   }
   
   # --------------------------------------------------
   # Trae Detalle de Centro de Costos
   # 2021-08-19 BOL Creacion
   # --------------------------------------------------
   public function omDetalleCenCosUltNivel() {
      $llOk = $this->mxValDetalleCenCosUltNivel();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleCenCosUltNivel($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValDetalleCenCosUltNivel() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO";
         return false;
      } 
      return true;
   }

   protected function mxDetalleCenCosUltNivel($p_oSql) {
      $lcSql = "SELECT cCenCos, cDescri, TRIM(cEstado), TRIM(cUniAca), TRIM(cClase), TRIM(cTipEst), TRIM(cEstPre), TRIM(cAfecta), TRIM(cTipo), TRIM(cDepend), TRIM(cTipDes) FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (empty($laFila[0])) {
         $this->pcError = "CENTRO DE COSTOS NO EXISTE";
         return false;
      }
      $this->paData = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2], 'CUNIACA' => $laFila[3], 'CCLASE' => $laFila[4], 'CTIPEST' => $laFila[5], 'CESTPRE' => $laFila[6], 'CAFECTA' => $laFila[7], 'CTIPO' => $laFila[8], 'CDEPEND' => $laFila[9], 'CTIPDES' => $laFila[10]];
      //TRAE TABLA DE CENTRO DE COSTOS
      $lcSql = "SELECT A.cCenCos, TRIM(A.cDescri), B.cNomUni, TRIM(A.cClase), C.cDescri, D.cDescri, E.cDescri, A.cTipEst FROM S01TCCO A
                INNER JOIN S01TUAC B ON A.cUniAca = B.cUniAca 
                INNER JOIN V_S01TTAB C ON C.cCodigo = A.cEstado AND C.cCodTab='041'
                INNER JOIN V_S01TTAB D ON D.cCodigo = A.cTipo AND D.cCodTab='048'
                INNER JOIN V_S01TTAB E ON E.cCodigo = A.cTipEst AND E.cCodTab='308'
                WHERE A.cClase like '{$this->paData['CCLASE']}%'
                ORDER BY A.cClase";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcTipEst = $laFila[6];
         if ($laFila[7] === '00') {
            $lcTipEst = '';
         }
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCLASE' => $laFila[3], 'CESTADO' => $laFila[4], 'CTIPO' => $laFila[5], 'CTIPEST' => $lcTipEst];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "CENTRO DE COSTO NO TIENE CENTROS DE COSTO DEPENDIENTES";
         return false;
      }
      return true;
   }
}
?>
