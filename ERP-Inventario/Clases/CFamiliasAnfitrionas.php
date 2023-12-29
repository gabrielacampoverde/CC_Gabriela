<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Clases/CEmail2.php";

class CFamiliasAnfitrionas extends CBase {

   public $paData, $paDatos, $paParPre, $paRequer, $paProduc, $paSerial, $paEstado;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paParPre = $this->paRequer = $this->paProduc = $this->paSerial = null;
   }
   # --------------------------------------------------
   # Validacion de usuario para modulo
   # 2023-05-19 KRA 
   # --------------------------------------------------   
   public function mxValParamUsuario($p_oSql, $p_cModulo = '00U'){
      //UNI: Acceso a todo
      $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCencos = 'UNI' AND 
                 cCodUsu = '{$this->paData['CCODUSU']}' AND CMODULO='$p_cModulo'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         ;
      } elseif ($laFila[0] == 'A'){
         return true;
      }
      // Valida que el modulo corresponda 
      $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cModulo = '$p_cModulo'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "USUARIO NO TIENE PERMISO PARA OPCION";
         return false;
      } elseif ($laFila[0] != 'A') {
         $this->pcError = "USUARIO NO TIENE PERMISO ACTIVO PARA OPCION";
         return false;
      }
      return true;
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

   // Init para anular requerimiento
   public function omInitAnularRequerimiento() {
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
      $llOk = $this->mxInitAnularRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAnularRequerimiento($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcCenCos = $this->paData['CCENCOS'];
      // Trae requerimientos que se pueden anular
      $lcSql = "SELECT cIdRequ, cDesCCo, cDesReq, cDesTip, cDesEst, tGenera FROM V_E01MREQ_1 WHERE cCenCos = '$lcCenCos' AND cEstado IN ('R', 'E', 'A') ORDER BY tGenera DESC";
      //echo $lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCOS' => $laFila[1], 'CDESCRI' => $laFila[2], 'CDESTIP' => $laFila[3], 'CDESEST' => $laFila[4], 'TGENERA' => $laFila[5]];
      }
      return true;
   }
   
   // Anular requerimiento
   public function omAnularRequerimiento() {
      $llOk = $this->mxValParamAnularRequerimiento();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAnularRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamAnularRequerimiento() {
      if (!isset($this->paData['CUSUCOD']) || !preg_match('(^[0-9A-Z]{4}$)', $this->paData['CUSUCOD'])) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO';
         return false;
      } 
        elseif (!isset($this->paData['CCENCOS']) || !preg_match('(^[0-9A-Z]{4}$)', $this->paData['CCENCOS'])) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDREQU']) || strlen($this->paData['CIDREQU']) != 8) {
         $this->pcError = 'ID DE REQUERIMIENTO INVALIDO';
         return false;
      }
      return true;
   }
   
   protected function mxAnularRequerimiento($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData[0]['ERROR'])) {
         $this->pcError = $this->paData[0]['ERROR'];
         return false; 
      }
      return true;
   }
   
   // Init para anular requerimiento
   public function omInitBandejaRequerimiento() {
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
      $llOk = $this->mxInitBandejaRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitBandejaRequerimiento($p_oSql) { 
      $lcCenCos = $this->paData['CCENCOS'];
      // Trae todos los requerimientos de un usuario
      $lcSql = "SELECT cIdRequ, cCenCos, cDesCCo, cDesReq, cTipo, cDesTip, cEstado, cDesEst, tGenera, cMoneda, cDesMon, nMonto 
                FROM V_E01MREQ_1 WHERE cCenCos = '$lcCenCos' ORDER BY tGenera DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESREQ' => $laFila[3], 
                             'CTIPO'   => $laFila[4], 'CDESTIP' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7], 
                             'TGENERA' => $laFila[8], 'CMONEDA' => $laFila[9], 'CDESMON' => $laFila[10],'NMONTO'  => $laFila[11]];
      }
      //Trae todos los estados de los requerimientos
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '076'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY ESTADOS DE REQUERIMIENTOS DEFINIDOS [S01TTAB.076]";
         return false;
      }
      return true;
   }
   
   // Buscar Requerimientos por Estado
   public function omBuscarRequerimientos() {
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
      $llOk = $this->mxBuscarRequerimientos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxBuscarRequerimientos($p_oSql) {
      $lcTipBus = $this->paData['CESTADO'];
      $lcCenCos = $this->paData['CCENCOS'];
      // Trae todos los requerimientos de un usuario
      if ($lcTipBus == 'T') {
         $lcSql = "SELECT cIdRequ, cCenCos, cDesCCo, cDesReq, cTipo, cDesTip, cEstado, cDesEst, tGenera, cMoneda, cDesMon, nMonto 
                   FROM V_E01MREQ_1 WHERE cCenCos = '$lcCenCos' AND cEstado NOT IN ('X') ORDER BY tGenera DESC";
      } else {
         $lcSql = "SELECT cIdRequ, cCenCos, cDesCCo, cDesReq, cTipo, cDesTip, cEstado, cDesEst, tGenera, cMoneda, cDesMon, nMonto 
                   FROM V_E01MREQ_1 WHERE cCenCos = '$lcCenCos' AND cEstado = '$lcTipBus' ORDER BY tGenera DESC";
      }
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESREQ' => $laFila[3], 
                             'CTIPO'   => $laFila[4], 'CDESTIP' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7], 
                             'TGENERA' => $laFila[8], 'CMONEDA' => $laFila[9], 'CDESMON' => $laFila[10],'NMONTO'  => $laFila[11]];
      }
      return true;
   }
   
   public function omBuscarOrdenCompra() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarOrdenCompra($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarOrdenCompra($p_oSql) {
      $lcBusOrd = strtoupper($this->paData['CBUSORD']);
      $lcSql = "SELECT A.cIdOrde, A.cCodAnt, A.cNroRuc, A.cRazSoc, A.dGenera, A.cTipo, A.cDesTip, A.cIncIgv, A.nMonto, 
                       A.cMoneda, A.cDesMon, B.cIdRequ, A.cCenCos, A.cDesCCo, A.cEstado, A.cDesEst, A.cCtaCnt, A.cDesCta,
                       B.cDesReq, B.mObserv, B.cNomUsu, B.tGenera, B.cComDir, A.mObsOrd, B.cNroDoc, B.cEstado
                   FROM V_E01MORD_3 A
                   LEFT OUTER JOIN V_E01MREQ_1 B ON B.cIdRequ = A.cIdRequ
                   WHERE
                   A.cCodAnt LIKE '%$lcBusOrd' OR A.cNroRuc LIKE '$lcBusOrd' OR 
                   A.cRazSoc LIKE '%$lcBusOrd%' OR A.cDesCCo LIKE '%$lcBusOrd%' OR 
                   A.mObsOrd LIKE '%$lcBusOrd%' OR B.cDesReq LIKE '%$lcBusOrd%' OR 
                   B.mObserv LIKE '%$lcBusOrd%'
                   ORDER BY A.cCodAnt";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos[] = ['CIDORDE'=>$laFila[0], 'CCODANT'=>$laFila[1], 'CNRORUC'=>$laFila[2], 'CRAZSOC'=>$laFila[3], 
                             'DGENERA'=>$laFila[4], 'CTIPO'  =>$laFila[5], 'CDESTIP'=>$laFila[6], 'CINCIGV'=>$laFila[7], 
                             'NMONTO' =>$laFila[8], 'CMONEDA'=>$laFila[9], 'CDESMON'=>$laFila[10],'CIDREQU'=>$laFila[11],
                             'CCENCOS'=>$laFila[12],'CDESCCO'=>$laFila[13],'CESTADO'=>$laFila[14],'CDESEST'=>$laFila[15],
                             'CCTACNT'=>$laFila[16],'CDESCTA'=>$laFila[17],'CDESREQ'=>$laFila[18],'MOBSREQ'=>$laFila[19],
                             'CNOMUSU'=>$laFila[20],'DGENREQ'=>$laFila[21],'CCOMDIR'=>$laFila[22],'MOBSORD'=>$laFila[23],
                             'CNRODOC'=>$laFila[24],'CESTREQ'=>$laFila[25]];
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS CON LOS PARAMETROS ENVIADOS';
         return false;
      }
      return true;
   }
   
   public function omInitDetalleRequ() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDetalleRequ($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitDetalleRequ($p_oSql) {
      //TRAER TODOS LOS DETALLES DE UN REQUERIMIENTO
      $lcIdRequ = $this->paData['CIDREQU'];
      $lcSql = "SELECT nSerial, cCodArt, cDesArt, nCantid, nPreArt, TRIM(cClasif), cDescri, ROUND(nCantid * nPreArt, 2) AS nSTotal,
                       cUnidad, cDesUni 
                FROM V_E01DREQ_1 WHERE cIdRequ = '$lcIdRequ'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCODART' => $laFila[1], 'CDESART' => $laFila[2], 'NCANTID' => $laFila[3],
                             'NPREART' => $laFila[4], 'CCLASIF' => $laFila[5], 'CDESCRI' => $laFila[6], 'NSTOTAL' => $laFila[7],
                             'CUNIDAD' => $laFila[8], 'CDESUNI' => $laFila[9]];
         $i++;
      }
      return true;
   }
   
   public function omGrabarObservRequerimiento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarObservRequerimiento($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarObservRequerimiento($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E01MREQ_7('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      return true;
   }

   //GENERAR DOCUMENTO - ERP1180 - JLF 2018-09-17
   public function omInitGenerarDocumento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGenerarDocumento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitGenerarDocumento($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcCenCos = $this->paData['CCENCOS'];
      //TRAER CABECERA DE REQUERIMIENTOS
      $lcSql = "SELECT A.cIdRequ, TRIM(A.cCenCos), B.cDescri AS cDesCCo, A.cDescri AS cDesReq, A.mObserv, A.cEstado, A.cTipo, 
                       C.cDescri AS cDesTip, D.cDescri AS cDesMon, A.cNroDoc, TRIM(B.cCodAnt), E.cNroRuc, F.cRazSoc, E.cNroCom,
                       E.nMonto, TO_CHAR(A.tGenera,'YYYY-MM-DD HH24:MI') 
                     FROM E01MREQ A
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                     LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
                     INNER JOIN E01DCOM E ON E.cIdRequ = A.cIdRequ
                     INNER JOIN S01MPRV F ON F.cNroRuc = E.cNroRuc
                  WHERE A.cCenCos = '$lcCenCos' AND A.cEstado IN ('R','E','A') AND A.cComDir = 'S'
                  ORDER BY A.cIdRequ DESC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESREQ' => $laFila[3], 
                              'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CTIPO'   => $laFila[6], 'CDESTIP' => $laFila[7], 
                              'CDESMON' => $laFila[8], 'CNRODOC' => $laFila[9], 'CCODANT' => $laFila[10],'CNRORUC' => $laFila[11],
                              'CRAZSOC' => $laFila[12],'CNROCOM' => $laFila[13],'NMONTO'  => $laFila[14],'TGENERA' => $laFila[15]];
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
      /*$lcSql = "SELECT A.cCenCos, B.cDescri FROM S01PCCO A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos WHERE A.cCodUsu = '$lcCodUsu'
                ORDER BY B.cDescri";
       * 
       */
      $lcSql = "SELECT cCenCos, cDesCen FROM V_S01PCCO WHERE cCodUsu = '$lcCodUsu' AND cModulo = '000' ORDER BY cDesCen";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      return true;
   }

   //CARGAR CABEZERA DE REQUERIMIENTO // LEVANTAR OBSERVACION/ANULAR REQ POR LOGISTICA Erp1190 JLF 26-09-2018
   public function omCargarRequerimientoObservado() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRequerimientoObservado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRequerimientoObservado($p_oSql) {
      //TRAE CABECERAS DE REQUERIMIENTOS
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, A.cTipo, 
                       A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin, A.cIdActi, A.cNroDoc, D.cNroRuc, TRIM(E.cRazSoc), 
                       TRIM(D.cNroCom), D.nMonto, D.dFecha, A.cUsuCot
                  FROM V_E01MREQ_1 A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                  LEFT OUTER JOIN E01DCOM D ON D.cIdRequ = A.cIdRequ
                  LEFT OUTER JOIN S01MPRV E ON E.cNroRuc = D.cNroRuc
                  WHERE A.cEstado = 'H' AND A.cCenCos = '$lcCenCos' AND NOT A.tCotiza ISNULL ORDER BY A.tGenera ASC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESCRI' => $laFila[3], 
                              'CNOMCOT' => $laFila[4], 'MOBSREQ' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
                              'CCODUSU' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO'   => $laFila[10],'CDESTIP' => $laFila[11], 
                              'CMONEDA' => $laFila[12],'CDESMON' => $laFila[13],'CCENCOS' => $laFila[14],'CCOMDIR' => $laFila[15], 
                              'CDESTIN' => $laFila[16],'CIDACTI' => $laFila[17],'CNRODOC' => $laFila[18],'CNRORUC' => $laFila[19],
                              'CRAZSOC' => $laFila[20],'CNROCOM' => $laFila[21],'NMONTO'  => $laFila[22],'DFECCOM' => $laFila[23],
                              'CUSUCOT' => $laFila[24]];
      }
      return true;
   }

   public function omInitBandejaAnfitrionesRh() {
      $llOk = $this->mxValParamMostrarBandejaAnfitriones();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql);
      if (!$llOk) {
         //$this->pcError = $this->pcError;
         return false;
      }
      $llOk = $this->mxInitMostrarBandejaAnfitriones();
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   
   protected function mxValParamMostrarBandejaAnfitriones() {
      if (!isset($this->paData['CCODUSU']) || !preg_match('(^[A-Z0-9]{4}$)', $this->paData['CCODUSU'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } 
        elseif (!isset($this->paData['CCENCOS']) || !preg_match('(^[0-9A-Z]{3}$)', $this->paData['CCENCOS'])) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } 
      return true;
   }

   protected function mxInitMostrarBandejaAnfitriones() {
      $laData = array_merge(['ID' => 'ERP9001I'], $this->paData);
      //$lcCommand = "python3 ./xpython/CFamiliasAnfitrionas.py '".json_encode($laData)."' 2>&1";
      $lcCommand = "python3 ./xpython/CFamiliasAnfitrionas.py '".json_encode($laData)."' 2>&1";
      //print_r($lcCommand);
      //die;
      $lcData = shell_exec($lcCommand);
      if (empty($lcData)) {
         $this->pcError = "ERROR AL EJECUTAR BANDEJA FAMILIAS ANFITRIONAS";
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      //print_r($laData);
      // OJOFPM FALTA VALIDAR SI NO RETORNA NADA
      $this->paDatos = $laData;
      //print_r ($this->paDatos);
      return true;
   }


   public function omDetalles() {
      /*$llOk = $this->mxValParamMostrarBandejaAnfitriones();
      if (!$llOk) {
         return false;
      }*/
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalles();
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   
   protected function mxValParamMostrarBandejaAnfitrionesxx() {
      if (!isset($this->paData['CCODUSU']) || !preg_match('(^[0-9A-Z]{4}$)', $this->paData['CCODUSU'])) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || !preg_match('(^[0-9A-Z]{3}$)', $this->paData['CCENCOS'])) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } 
      return true;
   }

   protected function mxDetalles() {
      $laData = $this->paData;
      $lcCommand = "python3 ./xpython/CFamiliasAnfitrionas.py '".json_encode($laData)."' 2>&1";
      $lcData = shell_exec($lcCommand);
      if (empty($lcData)) {
         $this->pcError = "ERROR AL EJECUTAR BANDEJA FAMILIAS ANFITRIONAS";
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->paDatos = $laData;
      return true;
   }

      public function omGrabarRevisionRhhh() {
      $llOk = $this->mxValParamGrabar();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxGrabarRevisionRhhh();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->omEnviarCorreoGrabarRevisionPaqueteDcriViceAdmin($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   
   protected function mxValParamGrabar() {
      //print_r($this->paData);
     
      if (!isset($this->paData['CUSUCOD']) || !preg_match('(^[0-9A-Z]{4}$)', $this->paData['CUSUCOD'])){
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      }elseif (!isset($this->paData['CIDPAQU']) || !preg_match('(^[0-9A-Z]{3}$)', $this->paData['CIDPAQU'])){
         $this->pcError = 'ID DE PAQUETE INVÁLIDO';
         return false;
      } 
      return true;
   }
   public function mxGrabarRevisionRhhh() {
      $laData = array_merge($this->paData, ['ID' => 'ERP9001G']);
      $lcCommand = "python3 ./xpython/CFamiliasAnfitrionas.py '".json_encode($laData)."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      if (empty($lcData)) {
         $this->pcError = "ERROR AL EJECUTAR PROCEDIMIENTO DE EJECUCIÓN PRESUPUESTAL";
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->paData = $laData;
      return true;
   }

   public function omGrabarObservacionesRhhh() {
      $llOk = $this->mxValParamObservaciones();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxGrabarObservacionesRhhh();
      return true;
   }
   
   protected function mxValParamObservaciones() {
      if (!isset($this->paData['CUSUCOD']) || !preg_match('(^[0-9A-Z]{4}$)', $this->paData['CUSUCOD'])){
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      }elseif (!isset($this->paData['CIDPAQU']) || !preg_match('(^[0-9A-Z]{3}$)', $this->paData['CIDPAQU'])){
         $this->pcError = 'ID DE PAQUETE INVÁLIDO';
         return false;
      }elseif (!preg_match('(^[A-Z0-9a-zÑñÁÉÍÓÚÜáéíóúü,;.:¿?¡!+*/\-\[\]\(\)\s]{5,1500}$)',$this->paData['MOBSERV'])) {
         $this->pcError = 'OBSERVACIONES CONTIENE CARACTERES INVÁLIDOS';
         return false;
      } 
      return true;
   }

   public function mxGrabarObservacionesRhhh() {
      $laData = array_merge(['ID' => 'ERP9001G'], $this->paData);
      $lcCommand = "python3 ./xpython/CFamiliasAnfitrionas.py '".json_encode($laData)."' 2>&1";
      //print_r($lcCommand);
      
      $lcData = shell_exec($lcCommand);
      if (empty($lcData)) {
         $this->pcError = "ERROR AL EJECUTAR PROCEDIMIENTO DE EJECUCIÓN PRESUPUESTAL";
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->paData = $laData;
      return true;
   }


   public function omEnviarCorreoGrabarRevisionPaqueteDcriViceAdmin($p_oSql) {
      $llOk = $this->mxGetDataEnviarCorreoGrabarRevisionPaqueteDcriViceAdmin($p_oSql);
      if (!$llOk) {
         return false;
      }
      $lcDate = date('Y-m-d H:i:s');
      $lo = new CEmail();
      $llOk = $lo->omConnect();
      $lcBody = "<ul style='margin: 0.5em'>";
      foreach ($this->paDatos as $item) {
         $lcRow = "<li style='font-weight: normal; font-size: 14px; margin: 0.5em;'><b>{$item['CNRORUC']} - {$item['CRAZSOC']}</b>, ( Estudiante {$item['CNOMALU']} ). </li>";
         $lcBody = $lcBody.$lcRow;
      }
      $lcBody = $lcBody."</ul>";
      $lcMensaje = "<!DOCTYPE html>
                     <html>
                     <body>
                     <table>
                           <colgroup>
                              <col style='background-color: #ececec'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>    
                           <thead>
                              <tr style='background-color: #ffffff; color:black'>
                                 <th colspan='3'>
                                    <div class='container'>
                                       UNIVERSIDAD CATÓLICA DE SANTA MARÍA
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                           </thead>
                           <tbody>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='3'>
                                    Se remite la presente para la revisión de las familias anfitrionas, con el fin de cumplir con el {$this->paData['CDESCRI']}.
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='3'>
                                    {$lcBody}
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='3'>
                                      <br>DCRRII<br>
                                        {$lcDate}
                                      <br><br>
                                 </th>
                              </tr>
                           </tbody>
                        </table>
                     </body>
                     </html>";
//      $laEmails[0] = "ccaceres@ucsm.edu.pe";
//      $laEmails[0] = "vradm04@ucsm.edu.pe";
//      $laEmails[0] = "gilmar.campana@ucsm.edu.pe";
//      $laEmails[0] = "rgutierreza@ucsm.edu.pe";
//      $laEmails[1] = "orrhh@ucsm.edu.pe";
//      $laEmails[2] = "ocai@ucsm.edu.pe";
//      $laEmails[3] = "jtapiama@ucsm.edu.pe";
//      $laEmails[3] = "71231729@ucsm.edu.pe";
      $laEmails = $this->paData['ACORREOS'];
      $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $laEmails];
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }
   protected function mxGetDataEnviarCorreoGrabarRevisionPaqueteDcriViceAdmin($p_oSql) {
      //LISTAR ALUMNOS Y PROVEEDORES PARA EL PAGO DE DCRRII
      $lcSql = "SELECT C.crazsoc, C.cnroruc, D.cnombre AS CNOMALU, E.cdescri AS CTIPCOM, A.cnrocom, A.mdatos::JSON->>'DFECCOM' AS DFECEMI FROM D05MCOM A
                  INNER JOIN D05MPRV B ON A.ccodigo = B.ccodigo
                  INNER JOIN S01MPRV C ON B.cnroruc = C.cnroruc
                  INNER JOIN D05MALU D ON A.cidalum = D.cidalum
                  LEFT OUTER JOIN V_S01TTAB E ON E.CCODIGO = TRIM(A.CTIPCOM) AND E.CCODTAB = '087'
                  WHERE A.cidpaqu = '{$this->paData['CIDPAQU']}' AND A.cestado <> 'X' ORDER BY C.crazsoc";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $this->paDatos = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CRAZSOC' => str_replace('/', ' ', $laFila[0]), 'CNRORUC' => $laFila[1], 'CNOMALU' => str_replace('/', ' ', $laFila[2]),
            'CTIPCOM' => $laFila[3], 'CNROCOM' => $laFila[4], 'DFECCOM' => $laFila[5] ];
      }
      //TRAER CABECERA PAQUETE
      $lcSql = "SELECT cDescri FROM D05MPAQ
                  WHERE cidpaqu = '{$this->paData['CIDPAQU']}' AND cestado <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CDESCRI'] = $laFila[0];

      //TRAER CORREOS PROCESO DCRRII
      $lcSql = "select mdatos from s01tvar where cnomvar = 'DCRRII.EMAILS'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = $laFila[0];
      $lmDatos = json_decode($lmDatos, true);
      $this->paData['ACORREOS'] = array_merge( $lmDatos['VRADM'], $lmDatos['DCRRII']);
      return true;
   }

}
?>
