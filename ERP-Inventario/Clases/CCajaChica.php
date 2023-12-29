<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CCajaChica extends CBase {

   public $paData, $paDatos, $paCajChi, $pcCajChi, $paTipDoc, $paTipOpe, $paMoneda, $paCenCos, $paUsuari, $paNotifi;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paCajChi = $this->pcCajChi = $this->paTipDoc = $this->paTipOpe = $this->paMoneda = $this->paUsuari = $this->paCenCos = $this->paNotifi = null;
   }

   /////////////////////////////////////////////////////////////////////////////
   protected function mxValidarUsuario() {
      if (!isset($this->paData['CCODUSU']) || strlen(trim($this->paData['CCODUSU'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      }
      return true;
   }

   public function omInitCajaChicaT() {
      if ($this->paData['CCENCOS'] === '*') {
         $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCajaChicaT($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCajaChicaT($p_oSql) {
      //TRAER CENTROS DE COSTO
      $lcSql = "SELECT cCenCos, TRIM(cDescri), cCodAnt FROM S01TCCO WHERE cEstado = 'A' AND cCodAnt != '';";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCODANT' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR IDCAJC O NO HAY CAJAS ACTIVAS";
         return false;
      }
      //TRAER CAJAS CHICAS DEL CENTRO DE COSTO DEL USUARIO
      $lcSql = "SELECT A.cIdCajC, A.cCenCos, B.cDescri AS cDesCco, A.nMonto, A.nMonTot FROM E02TCCH A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCajChi[] = ['CIDCAJC' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2],
                              'NMONTO' => $laFila[3], 'NMONTOT' => $laFila[4]];
         $i++;
      }
      return true;
   }

   public function omGrabarCajaChica() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarCajaChica($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E02TCCH_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCIÓN DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   public function omInitCajaChicaM() {
      $llOk = $this->mxValidarUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCajaChicaM($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCajaChicaM($p_oSql) {
      //TRAER TODAS LA CAJAS CHICAS DE CENTRO DE COSTO
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT A.cIdCajC, A.cCenCos, B.cDescri AS cDesCCo FROM E02TCCH A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.cEstado = 'A' AND A.cCenCos = '$lcCenCos'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paIdCajC[] = ['CIDCAJC' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CAJAS CHICAS DE CENTRO DE COSTO";
         return false;
      }
      //TRAER CAJAS CHICAS PENDIENTES PARA RENDICION
      $lcSql = "SELECT A.cNroCCh, A.cIdCajC, A.cCodUsu, A.dFecha, A.nMonto, A.cEstado, B.cDescri AS cDesEst, A.cAsient, A.cDescri, 
                       C.cCenCos 
                FROM E02MCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '098' AND B.cCodigo = A.cEstado
                INNER JOIN E02TCCH C ON C.cIdCajC = A.cIdCajC
                WHERE A.cEstado IN ('A') AND C.cCenCos = '$lcCenCos'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCajChi[] = ['CNROCCH' => $laFila[0], 'CIDCAJC' => $laFila[1], 'CCODUSU' => $laFila[2], 'DFECHA'  => $laFila[3], 
                              'NMONTO'  => $laFila[4], 'CESTADO' => $laFila[5], 'CDESEST' => $laFila[6], 'CASIENT' => $laFila[7], 
                              'CDESCRI' => $laFila[8]];
      }
      return true;
   }

   public function omInitRendirCajaChica() {
      $llOk = $this->mxValidarUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRendirCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitRendirCajaChica($p_oSql) {
      $lcCenCos = $this->paData['CCENCOS'];
      $lcCodUsu = $this->paData['CCODUSU'];
      //TRAER PERIODO DEL SISTEMA
      $lcSql = "SELECT TRIM(cPeriod) FROM S01TPER WHERE cTipo = 'S0'";
      $RS = $p_oSql->omExec($lcSql);
      if(!($RS)) {
         $this->pcError = "ERROR DE EJECUCION SQL";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "PERIODO DEL SISTEMA NO DEFINIDO";
         return false;
      }
      $laTmp = $p_oSql->fetch($RS);
      $lcPeriod = $laTmp[0];
      //TRAER CAJAS CHICAS APERTURADAS, ASOCIADAS AL CENTRO DE COSTO O AL CODIGO DE USUARIO
      $lcSql = "SELECT A.cNroCCh, A.cIdCajC, A.cCodUsu, A.dFecha, A.nMonto, A.cEstado, B.cDescri AS cDesEst, A.cAsient, C.cCenCos, 
                       D.cDescri AS cDesCCo, TRIM(A.mObserv), A.cUsuEnc, A.cDescri, C.nMonTot, D.cCodAnt
                FROM E02MCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '098' AND B.cCodigo = A.cEstado
                INNER JOIN E02TCCH C ON C.cIdCajC = A.cIdCajC
                INNER JOIN S01TCCO D ON D.cCenCos = C.cCenCos
                WHERE A.cEstado IN ('A', 'O') AND (A.cCodUsu = '$lcCodUsu' OR C.cCenCos = '$lcCenCos')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcSql = "SELECT cIdCajC, COUNT(cNroCCh) FROM E02MCCH WHERE TO_CHAR(dFecha, 'YYYY') = '$lcPeriod' AND cEstado NOT IN ('X') AND cIdCajC = '{$laFila[1]}' GROUP BY cIdCajC";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         $this->paCajChi[] = ['CNROCCH' => $laFila[0], 'CIDCAJC' => $laFila[1], 'CCODUSU' => $laFila[2], 'DFECHA'  => $laFila[3], 
                              'NMONTO'  => $laFila[4], 'CESTADO' => $laFila[5], 'CDESEST' => $laFila[6], 'CASIENT' => $laFila[7], 
                              'CCENCOS' => $laFila[8], 'CDESCCO' => $laFila[9], 'MOBSANT' => $laFila[10],'CUSUENC' => $laFila[11],
                              'CDESCRI' => $laFila[12],'NMONTOT' => $laFila[13],'CCCOANT' => $laFila[14],'NCANCAJ' => (!isset($laTmp[1]))? 0 : $laTmp[1],
                              'MOBSERV' => ''];
      }
      return true;
   }

   public function omInitCajaChica3130() {
      if ($this->paData['CCENCOS'] === '*') {
         $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCajaChica3130($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCajaChica3130($p_oSql) {
      $lcCenCos = $this->paData['CCENCOS'];
      //TRAER CAJAS CHICAS RELACIONADAS A LA ULTIMA CAJA EN E02TCCH
      $lcSql = "SELECT A.cNroCCh, A.cIdCajC, A.cCodUsu, A.dFecha, A.nMonto, A.cEstado, B.cDescri AS cDesEst, A.cAsient, A.mObserv, C.cCenCos, D.cDescri AS cDesCCo, A.cUsuEnc, E.cNombre, A.dFecCnt
                FROM E02MCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '098' AND B.cCodigo = A.cEstado
                INNER JOIN E02TCCH C ON C.cIdCajC = A.cIdCajC
                INNER JOIN S01TCCO D ON D.cCenCos = C.cCenCos
                LEFT OUTER JOIN V_S01TUSU_1 E ON E.cCodUsu = A.cUsuEnc
                WHERE A.cEstado IN ('B', 'O')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCajChi[] = ['CNROCCH' => $laFila[0], 'CIDCAJC' => $laFila[1], 'CCODUSU' => $laFila[2],
                              'DFECHA' => $laFila[3], 'NMONTO' => $laFila[4], 'CESTADO' => $laFila[5],
                              'CDESEST' => $laFila[6], 'CASIENT' => $laFila[7], 'MOBSERV' => $laFila[8],
                              'CCENCOS' => $laFila[9], 'CDESCCO' => $laFila[10], 'CUSUENC' => $laFila[11],
                              'CNOMBRE' => $laFila[12], 'DFECCNT' => $laFila[13]];
         $i++;
      }
      return true;
   }

   public function omGrabarCajaChicaM() {
      $llOk = $this->mxValGrabarCajaChicaM();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarCajaChicaM($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarCajaChicaM() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || strlen(trim($this->paData['CCODUSU'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDCAJC']) || strlen(trim($this->paData['CIDCAJC'])) != 3) {
         $this->pcError = "CAJA CHICA INVALIDA";
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || strlen(trim($this->paData['CDESCRI'])) > 200) {
         $this->pcError = "DESCRIPCION INVALIDA";
         return false;
      } elseif (!isset($this->paData['NMONTO']) || !is_numeric($this->paData['NMONTO'])) {
         $this->pcError = "MONTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CNROCCH']) || (strlen(trim($this->paData['CNROCCH'])) != 6 && strlen(trim($this->paData['CNROCCH'])) != 1)) {
         $this->pcError = "NRO DE CAJA CHICA INVALIDO";
         return false;
      } elseif (!isset($this->paData['DFECHA']) || strlen(trim($this->paData['DFECHA'])) != 10 || !$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = "FECHA INVALIDA";
         return false;
      }
      $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarCajaChicaM($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E02MCCH_1('$lcJson')";
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

   public function omInitCajaChicaD() {
      if ($this->paData['CCENCOS'] === '*') {
         $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCajaChicaD($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCajaChicaD($p_oSql) {
      $lcNroCCh = $this->paData['CNROCCH'];
      //TRAER TIPOS DE DOCUMENTOS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '087' AND TRIM(cCodigo) IN ('00','01', '02', '03', '12', 'PM')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipDoc[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TIPOS DE DOCUMENTOS DE S01TTAB[087]";
         return false;
      }
      //TRAER TIPOS DE OPERACIONES
      $lcSql = "SELECT TRIM(cCodOpe), TRIM(cDescri) FROM E02TOPE WHERE cCodOpe NOT IN ('KARD', 'MATP') ORDER BY cDescri";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipOpe[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TIPOS DE OPERACIONES DE E02TOPE";
         return false;
      }
      //TRAER CENTROS DE COSTO
      $lcSql = "SELECT TRIM(cCenCos), cDescri, cCodAnt FROM S01TCCO WHERE cEstado = 'A' AND cCodAnt != '' ORDER BY cDescri";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCODANT' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CENTROS DE COSTO";
         return false;
      }
      //TRAER TIPOS DE MONEDAS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '007'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR TIPOS DE MONEDAS DE S01TTAB[007]";
         return false;
      }
      $lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dFecha, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, 
                       A.cRucNro, A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, E.cRazSoc, A.cRucNro, C.cCtaCnt, 
                       A.cCodUsu, F.cNombre, A.cCCoDes, G.cDescri AS cDesCCo, A.cContab, A.nMonto + A.nMonIgv
                FROM E02DCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND  B.cCodigo = A.cTipDoc
                INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND  D.cCodigo = A.cMoneda
                LEFT JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
                LEFT OUTER JOIN V_S01TUSU_1 F ON F.cCodUsu = A.cCodUsu
                INNER JOIN S01TCCO G ON G.cCenCos = A.cCCoDes
                WHERE A.cNroCCh = '$lcNroCCh' ORDER BY A.dFecha";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      $lnTotal = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $lnTotal +=  $laFila[21];
         $this->paDatos[] = ['CNROCCH' => $laFila[0], 'CTIPDOC' => $laFila[1], 'CDESTIP' => $laFila[2], 'CNRODOC' => $laFila[3], 
                             'DFECHA'  => $laFila[4], 'CCODOPE' => $laFila[5], 'CDESOPE' => $laFila[6], 'CNRORUC' => $laFila[7], 
                             'CMONEDA' => $laFila[8], 'CDESMON' => $laFila[9], 'CGLOSA'  => $laFila[10],'NMONTO'  => $laFila[11],
                             'NMONIGV' => $laFila[12],'CRAZSOC' => str_replace("'", " ", $laFila[13]) , 'CRUCNRO' => $laFila[14],
                             'CCTACNT' => $laFila[15],'CCODUSU' => $laFila[16],'CNOMBRE' => $laFila[17],'CCCODES' => $laFila[18], 
                             'CDESCCO' => $laFila[19],'CCONTAB' => $laFila[20]];
         $i++;
      }
      $this->paData['NTOTCCH'] = $lnTotal;
      return true;
   }

   public function omSeleccionarDetalleCCh() {
      if ($this->paData['CCENCOS'] === '*') {
         $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarDetalleCCh($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxSeleccionarDetalleCCh($p_oSql) {
      $lcNroCCh = $this->paData['CNROCCH'];
      $lcOrden = $this->paData['CORDEN'];
      //TRAER DETALLE DE CAJA CHICA SELECCIONADA
      /*
      $lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dFecha, A.cCodOpe, C.cDescri AS cDesOpe, A.cNroRuc,
                       A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, E.cRazSoc, A.cRucNro
                FROM E02DCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND  B.cCodigo = A.cTipDoc
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '099' AND  C.cCodigo = A.cCodOpe
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND  D.cCodigo = A.cMoneda
                INNER JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
                WHERE A.cNroCCh = '$lcNroCCh' ORDER BY A.dFecha ASC";
      */
      if ($lcOrden == 'FECHA') {
			$lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dFecha, A.cCodOpe, C.cDescri AS cDesOpe, A.cNroRuc,
                       A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, E.cRazSoc, A.cRucNro, C.cCtaCnt,  A.cCodUsu, F.cNombre, A.cContab, A.cCCoDes, G.cDescri AS cDesCCo
                FROM E02DCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND  B.cCodigo = A.cTipDoc
                INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND  D.cCodigo = A.cMoneda
                INNER JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
                LEFT OUTER JOIN V_S01TUSU_1 F ON F.cCodUsu = A.cCodUsu
                INNER JOIN S01TCCO G ON G.cCenCos = A.cCCoDes
                WHERE A.cNroCCh = '$lcNroCCh'
                ORDER BY A.dFecha ASC";	
		}
		else {
			$lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dFecha, A.cCodOpe, C.cDescri AS cDesOpe, A.cNroRuc,
                       A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, E.cRazSoc, A.cRucNro, C.cCtaCnt,  A.cCodUsu, F.cNombre, A.cContab, A.cCCoDes, G.cDescri AS cDesCCo
                FROM E02DCCH A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND  B.cCodigo = A.cTipDoc
                INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND  D.cCodigo = A.cMoneda
                INNER JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
                LEFT OUTER JOIN V_S01TUSU_1 F ON F.cCodUsu = A.cCodUsu
                INNER JOIN S01TCCO G ON G.cCenCos = A.cCCoDes
                WHERE A.cNroCCh = '$lcNroCCh'";
		}
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         /*
         $this->paDatos[] = ['CNROCCH' => $laFila[0], 'CTIPDOC' => $laFila[1], 'CDESTIP' => $laFila[2],
                             'CNRODOC' => $laFila[3], 'DFECHA' => $laFila[4], 'CCODOPE' => $laFila[5],
                             'CDESOPE' => $laFila[6], 'CNRORUC' => $laFila[7], 'CMONEDA' => $laFila[8],
                             'CDESMON' => $laFila[9], 'CGLOSA' => $laFila[10], 'NMONTO' => $laFila[11],
                             'NMONIGV' => $laFila[12], 'CRAZSOC' => $laFila[13], 'CRUCNRO' => $laFila[14]];
         */
         $this->paDatos[] = ['CNROCCH' => $laFila[0], 'CTIPDOC' => $laFila[1], 'CDESTIP' => $laFila[2],
                             'CNRODOC' => $laFila[3], 'DFECHA' => $laFila[4], 'CCODOPE' => $laFila[5],
                             'CDESOPE' => $laFila[6], 'CNRORUC' => $laFila[7], 'CMONEDA' => $laFila[8],
                             'CDESMON' => $laFila[9], 'CGLOSA' => $laFila[10], 'NMONTO' => $laFila[11],
                             'NMONIGV' => $laFila[12], 'CRAZSOC' => str_replace("'", " ", $laFila[13]), 'CRUCNRO' => $laFila[14],
                             'CCTACNT' => $laFila[15], 'CCODUSU' => $laFila[16], 'CNOMBRE' => $laFila[17],
                             'CCONTAB' => $laFila[18], 'CCCODES' => $laFila[19], 'CDESCCO' => $laFila[20]];
         $i++;
      }
      return true;
   }

   public function omGrabarDetalleCajaChica() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarDetalleCajaChica($loSql);
      if(!$llOk)
      {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarDetalleCajaChica($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E02DCCH_1('$lcJson')";
      //echo $lcSql;// die;
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //AJAX BUSCAR PROVEEDOR POR NRO RUC -- ERP3120* // ALBERTO
   //18-03-23 AMM CREACION
   public function omBuscarProveedorxNroRuc() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarProveedorxNroRuc($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarProveedorxNroRuc($p_oSql) {
      $lcNroRuc = $this->paData['CNRORUC'];
      //TRAER PROVEEDOR X NRO RUC
      $lcSql = "SELECT cNroRuc, cRazSoc, cSiglas FROM S01MPRV WHERE CNRORUC = '$lcNroRuc' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION SQL";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paDatos = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CSIGLAS' => $laFila[2]];
      return true;
   }

   public function omValidarObservarCCH() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValidarObservarCCH($loSql);
      if(!$llOk)
      {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValidarObservarCCH($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E02MCCH_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      if ($this->paData['CESTADO'] == 'C') {
        $lcSql = "SELECT P_D01DDIA_1('$lcJson')";
        $RS = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($RS);
        $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
        $this->paDatos = json_decode($laFila[0], true);
        if (!empty($this->paDatos[0]['ERROR'])) {
           $this->pcError = $this->paDatos[0]['ERROR'];
           return false;
        }
      }
      // CREA NOTIFICACION DE OBSERVACION O APROBACION 
      if ($this->paData['CESTADO'] == 'O') {
        $this->paNotifi = ['CNRONOT' => '*',
                           'CCODUSU' => $this->paData['CCODUSU'],
                           'CCENCOS' => $this->paData['CCENCOS'],
                           'CMENSAJ' => 'LA CAJA CHICA CON CODIGO '.$this->paData['CNROCCH']. ' HA SIDO OBSERVADA. EDITA EL DETALLE HACIENDO CLICK EN ESTE MENSAJE.',
                           'CENLACE' => 'http://10.0.7.159/UCSMERP/Erp3140.php',
                           'CUSUCOD' => $this->paData['CCODUSU']];
        $lcJson = json_encode($this->paNotifi);
        $lcSql = "SELECT P_E01MNOT_1('$lcJson')";
        $RS = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($RS);
        $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
        $this->paDatos = json_decode($laFila[0], true);
        if (!empty($this->paDatos[0]['ERROR'])) {
           $this->pcError = $this->paDatos[0]['ERROR'];
           return false;
        }
      } else if ($this->paData['CESTADO'] == 'C'){
        $this->paNotifi = ['CNRONOT' => '*',
                           'CCODUSU' => $this->paData['CCODUSU'],
                           'CCENCOS' => $this->paData['CCENCOS'],
                           'CMENSAJ' => '¡LA CAJA CHICA CON CODIGO '.$this->paData['CNROCCH']. ' HA SIDO APROBADA!.',
                           'CENLACE' => '#',
                           'CUSUCOD' => $this->paData['CCODUSU']];
        $lcJson = json_encode($this->paNotifi);
        $lcSql = "SELECT P_E01MNOT_1('$lcJson')";
        $RS = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($RS);
        $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
        $this->paDatos = json_decode($laFila[0], true);
        if (!empty($this->paDatos[0]['ERROR'])) {
           $this->pcError = $this->paDatos[0]['ERROR'];
           return false;
        } 
      }
      return true;
   }
   //----------------------BUSCAR PERSONA POR CODIGO DE USUARIO---------------------
   // Buscar Persona por Codigo de Usuario
   public function omBuscarPersonaCod() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarPersonaCod($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarPersonaCod($p_oSql) {
      //TRAER PERSONAS QUE SON BUSCADOS POR CCODUSU O NOMBRE
      $lcBusPer = strtoupper($this->paData['CBUSPER']);
      $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE (cCodUsu = '$lcBusPer' OR cNombre LIKE '%$lcBusPer%') AND cEstado = 'A' ORDER BY cCodUsu";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY USUARIOS DEFINIDOS ACTUALMENTE";
         return false;
      }
      return true;
   }

   //----------------------BUSCAR MONTO DIFERENCIAL PARA CAJA CHICA---------------------
   public function omBuscarMonto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarMonto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarMonto($p_oSql) {
      //TRAER PERSONAS QUE SON BUSCADOS POR CCODUSU O NOMBRE
      $lcIdCajC = strtoupper($this->paData['CIDCAJC']);
      /*
      $lcSql = "SELECT MAX(cNroCCh), nMonto FROM E02MCCH WHERE cIdCajC = '$lcIdCajC' GROUP BY nMonto LIMIT 1";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CNROCCH' => $laFila[0], 'NMONTO' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $lcSql = "SELECT nMonto FROM E02TCCH WHERE cIdCajC = '$lcIdCajC'";
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)) {
            $this->paDatos[] = ['NMONTO' => $laFila[0]];
            $i++;
         }
      }*/
      $lcSql = "SELECT nMonto FROM E02TCCH WHERE cIdCajC = '$lcIdCajC'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
        $this->paDatos[] = ['NMONTO' => $laFila[0]];
        $i++;
      }
      if ($i == 0) {
         $this->paDatos[] = ['CNROCCH' => 'ERROR AL RECUPERAR MONTO DE CAJA CHICA'];
         return false;
      }
      return true;
   }

   public function omInit3150() {
      if ($this->paData['CCENCOS'] === '*') {
         $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInit3150($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInit3150($p_oSql) {
      $pcCenCos = $this->paData['CCENCOS'];
      //TRAER CAJAS CHICAS DEL CENTRO DE COSTO DEL USUARIO
      $lcSql = "SELECT A.cNroCCh, A.cDescri, B.cCenCos, C.cDescri AS cDesCCo, A.cCodUsu, D.cNombre, A.nMonto
                FROM E02MCCH A
                INNER JOIN E02TCCH B ON B.cIdCajC = A.cIdCajC
                INNER JOIN S01TCCO C ON C.cCenCos = B.cCenCos
                LEFT JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cCodUsu
                WHERE B.cCenCos = '$pcCenCos' AND A.cEstado != 'X' ORDER BY A.cNroCCh DESC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCajChi[] = ['CNROCCH' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCENCOS' => $laFila[2],
                              'CDESCCO' => $laFila[3], 'CCODUSU' => $laFila[4], 'CNOMBRE' => $laFila[5],
                              'NMONTO' => $laFila[6]];
         $i++;
      }
      return true;
   }   

   public function omTraerCenCos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTraerCenCos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxTraerCenCos($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      /*
      $lcSql = "SELECT A.cCenCos, B.cDescri FROM S01PCCO A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos WHERE A.cCodUsu = '$lcCodUsu'
                ORDER BY B.cDescri";
       * 
       */
      $lcSql = "SELECT cCenCos, cDesCen FROM v_S01PCCO WHERE cCodUsu = '$lcCodUsu' AND cModulo = '000' ORDER BY cDesCen";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      return true;
   }

}
