<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CLogistica extends CBase {

   public $paData, $paDatos, $paRequer, $paCodArt, $paSerial, $paTipReq, $paMoneda, $paEstado, $paNroRuc, $paPaquet, $paTipCom,
          $paFactur, $paTipPre, $paForPag, $paNotIng, $paTipBus, $paTipNot, $paCodAlm, $paCodUsu, $paCenCos, $paActivi, $paOrdene,
          $paTipOrd, $paEstEnv, $paPeriod, $paCompro, $poFile, $paFile;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paRequer = $this->paCodArt = $this->paSerial = $this->paTipReq = $this->paMoneda =
      $this->paEstado = $this->paPaquet = $this->paTipCom = $this->paTipCom = $this->paTipPre = $this->paForPag = $this->paNotIng =
      $this->paTipBus = $this->paTipNot = $this->paCodUsu = $this->paCodAlm = $this->paCenCos = $this->paActivi = $this->paOrdene =
      $this->paPrvCot = $this->paTipOrd = $this->paEstEnv = $this->paPeriod = $this->paCompro = $this->poFile = null;
   }

   ######################################/
   public function omInitRegistroRequerimientos() {
      $llOk = $this->mxValInitRegistroRequerimientos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRegistroRequerimientos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitRegistroRequerimientos() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3 || in_array(trim($this->paData['CCENCOS']), ['*','000'])) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxInitRegistroRequerimientos($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      #TRAER MONEDAS
      $lcSql = "SELECT TRIM(cCodigo), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '007'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE MONEDAS DEFINIDOS [S01TTAB.007]";
         return false;
      }
      #TRAER TIPO DE REQUERIMIENTOS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('A','E','M')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipReq[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE REQUERIMIENTO DEFINIDOS [S01TTAB.075]";
         return false;
      }
      #TRAER RANGOS DE APROBACION DEL VICERRRECTOR
      $lcSql = "SELECT nRango FROM S01TRAN WHERE cTipo = '01' ORDER BY nOrden LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->pnRango = $laFila[0];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY LIMITE DEFINIDO POR VICERRECTOR ADMINISTRATIVO";
         return false;
      }
      #TRAER CABECERA DE REQUERIMIENTOS
      $lcSql = "SELECT TRIM(cCenCos), cDesCCo, cIdRequ, cDesReq, mObserv, tCotiza, cEstado, cTipo, cDesTip, cMoneda, cComDir, cDestin, 
                       cIdActi, cNroDoc, cDesEst, tGenera, dIniEve, dFinEve, cUsuEnc, cNomEnc FROM V_E01MREQ_1
                WHERE cCenCos = '$lcCenCos' AND cEstado IN ('R','H') AND cTipo NOT IN ('A','E','M') ORDER BY cIdRequ DESC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $laTmp = null;
         if ($laFila[10] === 'S') {
            $lcSql = "SELECT A.cNroRuc, TRIM(B.cRazSoc), TRIM(A.cNroCom), A.nMonto, A.dFecha FROM E01DCOM A
                              INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                           WHERE A.cIdRequ = '$laFila[2]'";
            $RS2 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($RS2);
         }
         $lcPath = 'Docs/Logistica/Requerimiento/'.$laFila[2].'.pdf';
         $this->paRequer[] = ['CCENCOS' => $laFila[0], 'CDESCCO' => $laFila[1], 'CIDREQU' => $laFila[2], 'CDESCRI' => $laFila[3],
                              'MOBSANT' => $laFila[4], 'TCOTIZA' => $laFila[5], 'CESTADO' => $laFila[6], 'CTIPO'   => $laFila[7],
                              'CDESTIP' => $laFila[8], 'CMONEDA' => $laFila[9], 'CCOMDIR' => $laFila[10],'CDESTIN' => $laFila[11],
                              'CNRORUC' => $laTmp[0],  'CRAZSOC' => $laTmp[1],  'CNROCOM' => $laTmp[2],  'NMONTO'  => $laTmp[3],
                              'DFECCOM' => $laTmp[4],  'CIDACTI' => $laFila[12],'CNRODOC' => $laFila[13],'CDESEST' => $laFila[14],
                              'TGENERA' => $laFila[15],'DINIEVE' => $laFila[16],'DFINEVE' => $laFila[17],'CUSUENC' => $laFila[18],
                              'CNOMENC' => $laFila[19],'MOBSERV' => '', 'CESPTEC' => (file_exists($lcPath))? 'S' : 'N'];
         $i++;
      }
      return true;
   }

   #AJAX BUSCAR PRODUCTO POR DESCRIPCION -- ERP1110* # MIGUEL
   public function omBuscarArticuloxDescripcion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarArticuloxDescripcion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarArticuloxDescripcion($p_oSql) {
      $lcBusArt = strtoupper(trim($this->paData['CBUSART']));
      $lcBusArt = str_replace(" ", "%", $lcBusArt);
      $lcAlm = $this->paData['CALM'];
      $lcSql = "SELECT A.cCodArt, A.cDescri, A.cUnidad, A.nRefSol, A.nRefDol, B.cDescri, C.nStock, C.nCosPro FROM E01MART A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '074' AND B.cCodigo = A.cUnidad";
      #TRAER ARTICULOS X DESCRIPCION
      if ($lcAlm == 'N') {
         $lcSql = $lcSql." LEFT JOIN E03PALM C ON C.cCodAlm = '001' AND C.cCodArt = A.cCodArt WHERE";
      } elseif ($lcAlm == 'S') {
         $lcSql = $lcSql." RIGHT JOIN E03PALM C ON C.cCodAlm = '001' AND C.cCodArt = A.cCodArt WHERE C.nStock > 0 AND";
      }
      $lcSql = $lcSql." A.cEstado = 'A' AND (A.cDescri LIKE '%$lcBusArt%' OR A.cCodArt LIKE '$lcBusArt%') ORDER BY A.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcSql = "SELECT CASE WHEN B.cMoneda = '1' THEN A.nCosto ELSE (A.nCosto*(SELECT nTipVen FROM S01DCAM WHERE cTipo = 'O' AND cMoneda = '2' AND dFecCam <= NOW() ORDER BY dFecCam DESC LIMIT 1))::NUMERIC(14,6) END AS nCosSol, CASE WHEN B.cMoneda = '2' THEN A.nCosto ELSE (A.nCosto/(SELECT nTipVen FROM S01DCAM WHERE cTipo = 'O' AND cMoneda = '2' AND dFecCam <= NOW() ORDER BY dFecCam DESC LIMIT 1))::NUMERIC(14,6) END AS nCosDol FROM E01DORD A 
                     INNER JOIN E01MORD B ON B.cIdOrde = A.cIdOrde
                     WHERE A.cCodArt = '{$laFila[0]}'
                   ORDER BY B.cIdOrde DESC, A.nSerial DESC LIMIT 1";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         $this->paCodArt[] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'CUNIDAD' => $laFila[5], 'NREFSOL' => $laFila[3],
                              'NREFDOL' => $laFila[4], 'CUNIART' => $laFila[2], 'NSTOCK' => ($laFila[6] == null) ? 0 : $laFila[6],
                              'NCOSPRO' => ($laFila[7] == null) ? 0 : $laFila[7], 'NCOSSOL' => ($laTmp == null) ? 0 : $laTmp[0],
                              'NCOSDOL' => ($laTmp == null) ? 0 : $laTmp[1]];
      }
      return true;
   }

   public function omRevisarDetalleOrdenCS() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRevisarDetalleOrdenCS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   public function mxRevisarDetalleOrdenCS($p_oSql) {
      $lcIdOrde = $this->paData['CIDORDE'];
      $lcCodArt = $this->paData['CCODART'];
      $lcSql = "SELECT cCodArt FROM E01DORD WHERE cIdOrde = '$lcIdOrde' AND cCodArt = '$lcCodArt' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCIÓN EN BASE DE DATOS';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = ['CINFORM' => ($laFila == null)? 'ARTÍCULO NO PERTENECE A ORDEN DE C/S' : 'ARTÍCULO PERTENECE A ORDEN DE C/S',
                       'CESTINF' => ($laFila == null)? false : true];
      return true;
   }

   public function omEditarRequerimiento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEditarRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxEditarRequerimiento($p_oSql) {
      $lcIdRequ = $this->paData['CIDREQU'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cCodAlm, B.cDescri FROM E03PUSU A
               INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm WHERE A.cCodUsu = '$lcCodUsu' LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paData['CALMORI'] = $laFila[0];
      }
      $lcAlmOri = (!isset($this->paData['CALMORI']) || empty($this->paData['CALMORI']))? '001' : $this->paData['CALMORI'];
      $lcSql = "SELECT A.cCodArt, A.cDesArt, A.cDescri, A.nCantid, A.nPreArt, A.nCanAte, A.cUnidad, ROUND(A.nCantid * A.nPreArt, 2) AS nSTotal, 
                       A.cDesUni, B.nStock, A.nSerial FROM V_E01DREQ_1 A
                LEFT JOIN E03PALM B ON B.cCodAlm = '$lcAlmOri' AND B.cCodArt = A.cCodArt 
                WHERE A.cIdRequ = '$lcIdRequ' AND A.cEstado = 'A'
                ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      $lnTotal = 0.00;
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($laFila[0] == '00000000') {
            $laFila[1] = substr($laFila[2], 0, 200);
         }
         $this->paDatos[] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'CDESDET' => htmlspecialchars($laFila[2]), 'NCANTID' => $laFila[3],
                             'NPREREF' => $laFila[4], 'NCANATE' => $laFila[5], 'CUNIDAD' => $laFila[8], 'NSTOTAL' => $laFila[7],
                             'CDESUNI' => $laFila[8], 'NSTKACT' => $laFila[9], 'NSERIAL' => $laFila[10]];
         $lnTotal = $lnTotal + $laFila[7];
         $i++;
      }
      $this->paData['NTOTAL'] = number_format($lnTotal, 2);
      return true;
   }

   public function omGrabarRequerimiento() {
      /*
        if ($this->paData['CCOMDIR'] === 'S' && $this->paData['NTOTAL'] != $this->paData['NMONTO']) {
        $this->pcError = "ERROR EL MONTO DE LA FACTURA NO COINCIDE CON EL TOTAL DEL DETALLE";
        return;
        }
      */
      $llOk = $this->mxValidarGrabarRequerimiento();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      /*
        $llOk = $this->mxValidarPresupuestoXArticulo($loSql);
        if (!$llOk) {
        $loSql->rollback();
        return false;
        } else {
        $llOk = $this->mxGrabarRequerimiento($loSql);
        } */
      $llOk = $this->mxGrabarRequerimiento($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxSubirArchivoEspecificacionesTecnicas();
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValidarPresupuestoXArticulo($p_oSql) {
      $laDatos = null;
      foreach ($this->paData['MDATOS'] as $laFila) {
         $lcJson = json_encode(['CCENCOS' => $this->paData['CCENCOS'],
             'CCODART' => $laFila['CCODART']]);
         $lcSql = "SELECT P_E01DREQ_1('$lcJson')";
         $RS = $p_oSql->omExec($lcSql);
         $laFila2 = $p_oSql->fetch($RS);
         $laFila2[0] = (!$laFila2[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila2[0];
         $this->paDatos = json_decode($laFila2[0], true);
         if (!empty($this->paDatos[0]['ERROR'])) {
            $this->pcError = $this->paDatos[0]['ERROR'];
            return false;
         }
         $llExiste = false;
         if ($laDatos != null) {
            $k = 0;
            foreach ($laDatos as $laTmp) {
               if ($this->paDatos[0]['CCLASIF'] === $laTmp['CCLASIF']) {
                  $laDatos[$k]['NMONREQ'] = $laDatos[$k]['NMONREQ'] + ($laFila['NCANTID'] * $laFila['NPREREF']);
                  $laDatos[$k]['NCANREQ'] = $laDatos[$k]['NCANREQ'] + $laFila['NCANTID'];
                  $llExiste = true;
               }
               $k = $k + 1;
            }
         }
         if (!$llExiste) {
            $this->paDatos[0]['NMONREQ'] = $this->paDatos[0]['NMONREQ'] + ($laFila['NCANTID'] * $laFila['NPREREF']);
            $this->paDatos[0]['NCANREQ'] = $this->paDatos[0]['NCANREQ'] + $laFila['NCANTID'];
            $laDatos[] = $this->paDatos[0];
         }
      }
      $lcCenCos = $this->paData['CCENCOS'];
      foreach ($laDatos as $laFila) {
         $lcClasif = $laFila['CCLASIF'];
         $lcSql = "SELECT A.nSerial, A.cCodigo, A.cCenCos, A.cEstado, A.nMonPre, B.cPeriod, B.cClasif, B.cFueFin, B.cEstado
                      FROM P01DPRE A
                      INNER JOIN P01PPRE B ON B.cCodigo = A.cCodigo
                   WHERE A.cCenCos = '$lcCenCos' AND B.cClasif = '$lcClasif' AND B.cFueFin = 'RP'";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = "ERROR DE EJECUCION SQL";
            return false;
         }
         $laTmp = $p_oSql->fetch($RS);
         if ($laTmp[4] < ($laFila['NMONREQ'] + $laFila['NMONUSA'])) {
            $this->paData['CDESTIN'] = 'I';
         }
      }
      return true;
   }

   protected function mxValidarGrabarRequerimiento() {
      if (!isset($this->paData['CNRODOC']) || strlen($this->paData['CNRODOC']) > 80) {
         $this->pcError = "DOCUMENTO DE REFERENCIA INVALIDO";
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || strlen($this->paData['CDESCRI']) > 200) {
         $this->pcError = "DESCRIPCION INVALIDA";
         return false;
      } elseif (!isset($this->paData['MDATOS'])) {
         $this->pcError = "NO HA DEFINIDO DETALLE PARA EL REQUERIMIENTO";
         return false;
      } elseif (!isset($this->paData['CTIPO']) || strlen($this->paData['CTIPO']) != 1) {
         $this->pcError = "TIPO DE REQUERIMIENTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCOMDIR']) || strlen($this->paData['CCOMDIR']) != 1) {
         $this->pcError = "COMPRA DIRECTA INVALIDA";
         return false;
      } elseif (!isset($this->paData['CDESTIN']) || strlen($this->paData['CDESTIN']) != 1) {
         $this->pcError = "DESTINO ALMACEN INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif ($this->paData['CTIPO'] == 'R' && (!isset($this->paData['CUSUENC']) || strlen($this->paData['CUSUENC']) != 4)) {
         $this->pcError = "RESPONSABLE DE CHEQUE INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      $this->paData['NTOTAL'] = str_replace(',','',$this->paData['NTOTAL']);
      $this->paData['NMONTO'] = str_replace(',','',$this->paData['NMONTO']);
      if ($this->paData['CCOMDIR'] === 'S' && (int)$this->paData['NTOTAL'] < (int)$this->paData['NMONTO']) {
         $this->pcError = "EL MONTO DE LA FACTURA ES MAYOR AL TOTAL DEL DETALLE";
         return false;
      }
      # elseif ($this->paData['CESTADO'] === 'S' && (int)$this->paData['NTOTAL'] <= 0) {
      #    $this->pcError = "EL TOTAL DEL REQUERIMIENTO NO PUEDE SER MENOR O IGUAL A 0 (CERO)";
      #    return false;
      # }
      $this->paData['CNRODOC'] = mb_strtoupper($this->paData['CNRODOC']);
      $this->paData['CDESCRI'] = mb_strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarRequerimiento($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_1('$lcJson')";
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

   # Subir Archivo de Especificaciones Técnicas
   # 2019-09-04 JLF Creacion
   public function mxSubirArchivoEspecificacionesTecnicas() {
      if ($this->poFile == null || $this->paData['CCOMDIR'] == 'S' || $this->paData['CDESTIN'] == 'F') {
         return true;
      }
      $laErrFil = array(
         0 => 'ARCHIVO SUBIDO CON EXITO',
         1 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO EL SERVIDOR',
         2 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO POR USUARIO',
         3 => 'EL ARCHIVO FUE PARCIALMENTE CARGADO',
         4 => 'EL ARCHIVO NO FUE CARGADO',
         6 => 'FALTA UNA CARPETA TEMPORAL',
         7 => 'ERROR AL ESCRIBIR EL ARCHIVO EN EL DISCO',
         8 => 'UNA EXTENSIÓN DE PHP DETUVO LA CARGA DEL ARCHIVO',
      );
      $lcIdRequ = $this->paData['CIDREQU'];
      if ($this->poFile['error'] != 0 && $this->poFile['error'] != 4) {
         $this->pcError = $laErrFil[$this->poFile['error']];
         return false;
      } elseif ($this->poFile['error'] == 4) {
         return true;
      }
      $llOk = fxSubirPDF($this->poFile, 'Logistica/Requerimiento', $lcIdRequ);
      if(!$llOk) {
         $this->pcError = "HA OCURRIDO UN ERROR AL SUBIR ARCHIVO ".$this->poFile['name'];
         return false;
      }
      return true;
   }

   #----------------------BEHAVIOR 0 CABEC. DE REQU----------------------------
   public function omInitAsignarPresupuesto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignarPresupuesto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAsignarPresupuesto($p_oSql) {
      #TRAER TABLA DE CLASIFICADORES PRESUPUESTALES
      $lcSql = "SELECT TRIM(cClasif), cDescri FROM P01MCLA ORDER BY cClasif";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paClasif[] = ['CCLASIF' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CLASIFICADORES PRESUPUESTALES";
         return false;
      }
      #TRAER CUENTAS CONTABLES
      $lcSql = "SELECT cCtaCnt, cDescri FROM D01MCTA WHERE SUBSTRING(cCtaCnt, 1, 2) IN ('60','65','33','00','63') ORDER BY cCtaCnt";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCtaCnt[] = ['CCTACNT' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CUENTAS CONTABLES";
         return false;
      }
      #TRAER TODOS LAS CABECERAS DE ORDENES DE COMPRA PENDIENTES
      $lcSql = "SELECT A.cIdOrde, A.cNroRuc, A.cRazSoc, A.dGenera, A.cTipo, A.cDesTip, A.cIncIgv, A.nMonto, A.cMoneda, A.cDesMon, 
                       A.cIdRequ, A.cCenCos, A.cDesCCo, B.cIdActi, B.cDesAct, A.mObsOrd, A.cCtaCnt, A.cDesCta, C.cDesCor AS cMonSim, 
                       A.cCodAnt FROM V_E01MORD_3 A
                LEFT OUTER JOIN V_E01MREQ_1 B ON B.cIdRequ = A.cIdRequ
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '007' AND C.cCodigo = A.cMoneda
                WHERE A.cEstado = 'A' ORDER BY A.cCodAnt DESC, A.nTotReq DESC, A.cIdRequ ASC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      $lcIdOrde = '';
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($lcIdOrde != $laFila[0]) {
            $this->paDatos[] = ['CIDORDE' => $laFila[0], 'CNRORUC' => $laFila[1], 'CRAZSOC' => $laFila[2], 'DGENERA' => $laFila[3],
                                'CTIPO'   => $laFila[4], 'CDESTIP' => $laFila[5], 'CINCIGV' => $laFila[6], 'NMONTO'  => $laFila[7],
                                'CMONEDA' => $laFila[8], 'CDESMON' => $laFila[9], 'CIDREQU' => $laFila[10],'CCENCOS' => $laFila[11],
                                'CDESCCO' => $laFila[12],'CIDACTI' => $laFila[13],'CDESACT' => $laFila[14],'MOBSERV' => $laFila[15],
                                'CCTACNT' => $laFila[16],'CDESCTA' => $laFila[17],'CMONSIM' => $laFila[18],'CCODANT' => $laFila[19]];
            $lcIdOrde = $laFila[0];
            $i++;
         }
      }
      return true;
   }

   #----------------------BEHAVIOR 1 DETALL. DE REQU---------------------------
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
      #TRAER TODOS LOS DETALLES DE UN REQUERIMIENTO
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

   #----------------------BEHAVIOR 1 DETALL. DE ORDEN---------------------------
   public function omInitDetalleOrde() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDetalleOrde($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitDetalleOrde($p_oSql) {
      #TRAER TODOS LOS DETALLES DE UN REQUERIMIENTO
      $lcIdRequ = $this->paData['CIDREQU'];
      #$lcSql = "SELECT nSerial, cCodArt, cDesArt, nCantid, nPreArt, TRIM(cClasif), cDescri FROM V_E01DREQ_1 WHERE cIdRequ = '$lcIdRequ' AND cEstado = 'A'";
      $lcSql = "SELECT A.nSerial, A.cCodArt, A.cDesArt, B.nCantid, B.nCosto, TRIM(A.cClasif), A.cDescri, ROUND(B.nCantid * B.nCosto, 2) AS nSTotal FROM V_E01DREQ_1 A
                LEFT OUTER JOIN V_E01MORD_1 B ON B.cIdRequ = A.cIdRequ AND B.cCodArt = A.cCodArt
                WHERE A.cIdRequ = '$lcIdRequ' AND A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCODART' => $laFila[1],
             'CDESART' => $laFila[2], 'NCANTID' => $laFila[3], 'NPREART' => $laFila[4],
             'CCLASIF' => $laFila[5], 'CDESCRI' => $laFila[6], 'NSTOTAL' => $laFila[7]];
         $i++;
      }
      return true;
   }

   public function omGrabarAsignacionPresupuestal() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarAsignacionPresupuestal($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarAsignacionPresupuestal($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E01MORD_2('$lcJson')";
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

   #------------------APROBACION POR VICERRECTORADO ACADEMICO------------------

   public function omInitAprobacionVRADM() {
      $llOk = $this->mxValParamInitAprobacionVRADM();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAprobacionVRADM($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamInitAprobacionVRADM() {
      if (!isset($this->paData['CCODUSU'])) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxInitAprobacionVRADM($p_oSql) {
      # Halla centro de costo de Verifica usuario
      /*
        $lcJson = json_encode($this->paData);
        $lcSql = "SELECT P_E01DAUT_3('$lcJson')";
        $R1 = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($R1);
       */
      # Validar ERROR
      $lcSql = "SELECT A.cIdRequ, B.cDesCCo, A.tRecepc, B.cDesReq, B.cNomUsu, A.mObserv, A.cEstado, A.nserial, B.cEstado AS cEstReq FROM E01DAUT A
                INNER JOIN V_E01MREQ_1 B ON B.cIdRequ = A.cIdRequ WHERE A.cCenCos = '02Q' AND A.cEstado IN ('P', 'O') AND B.cEstado = 'E'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESREQ' => $laFila[3],
                             'CNOMBRE' => $laFila[4], 'MOBSAUT' => $laFila[5], 'CESTADO' => $laFila[6], 'NSERIAL' => $laFila[7],
                             'CESTREQ' => $laFila[8], 'MOBSERV' => null];
      }
      return true;
   }

   #-------------Grabar aprobacion - anulacion u observacion de VRADM----------
   public function omGrabarVRADM() {
      $llOk = $this->mxValParamGrabarVRADM();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarVRADM($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarVRADM() {
      if ($this->paSerial == null) {
         $this->pcError = 'NO HA SELECCIONADO NINGÚN REQUERIMIENTO';
         return false;
      }
      return true;
   }

   protected function mxGrabarVRADM($p_oSql) {
      foreach ($this->paSerial as $lnTmp) {
         $laData = $this->paData;
         $laData['NSERIAL'] = $lnTmp;
         $lcJson = json_encode($laData);
         $lcSql = "SELECT P_E01DAUT_2('$lcJson')";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION SQL"}]' : $laFila[0];
         $this->paDatos = json_decode($laFila[0], true);
         if (!empty($this->paDatos[0]['ERROR'])) {
            $this->pcError = $this->paDatos[0]['ERROR'];
            return false;
         }
      }
      return true;
   }

   ######/LLENADO DE COTIZACION #############
   public function omInitLlenarCotizacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitLlenarCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitLlenarCotizacion($p_oSql) {
      $lcNroRuc = $this->paData['CNRORUC'];
      #REVISA RUBRO MEDICAMENTOS PARA EDICION
      $lcSql = "SELECT cRubro FROM S01DRUB WHERE cNroRuc = '$lcNroRuc' AND cRubro = '003'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      $lcEdicio = 'S';
      $laTmp = $p_oSql->fetch($RS);
      if ($laTmp[0] == null) {
         $lcEdicio = 'N';
      }
      #TRAER COTIZACIONES
      $lcSql = "SELECT A.cIdCoti, B.cEstado AS cEstPcot, TO_CHAR(A.tInicio, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tFinali, 'YYYY-MM-DD HH24:MI'), 
                       A.cUsucod, A.cEstado AS cEstcot, B.cCodigo, C.cDescri, F.cDescri, B.cDetEnt, B.cNroCel, B.cTieVal, TRIM(B.cTipPre), 
                       G.cDescri AS cDesTPr, TRIM(B.cTipFPa), H.cDescri AS cDesFPa, B.cForPag, A.cLugar, TRIM(F.cDesCor) AS cSimbol, B.cEmail, 
                       A.cDescri, B.nTieEnt, B.nTieVal, A.cArcPrv, TRIM(B.cDniVen), B.cNomVen
                     FROM E01MCOT A
                     INNER JOIN  E01PCOT B ON B.cIdCoti= A.cIdCoti
                     LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '079' AND C.cCodigo = B.cEstado
                     INNER JOIN E01PREQ D ON D.cIdCoti = B.cIdCoti
                     INNER JOIN E01MREQ E ON E.cIdRequ =  D.cIdRequ
                     LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '007' AND F.cCodigo = E.cMoneda
                     LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '096' AND G.cCodigo = B.cTipPre
                     LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '097' AND H.cCodigo = B.cTipFPa
                  WHERE A.cEstado IN ('I','B') AND B.cNroRuc = '$lcNroRuc' AND A.tFinali > NOW()
                  ORDER BY A.cIdCoti";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      $lcIdCoti = '';
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($lcIdCoti != $laFila[0]) {
            $lcPath1 = 'Docs/Logistica/Planimetria/'.$laFila[0].'.pdf';
            $lcPath2 = 'Docs/Logistica/Cotizacion/'.$laFila[6].'.pdf';
            $this->paDatos[] = ['CIDCOTI' => $laFila[0], 'CESTPCOT' => $laFila[7], 'TINICIO' => $laFila[2], 'TFINALI' => $laFila[3],
                                'CUSUCOD' => $laFila[4], 'CESTCOT'  => $laFila[5], 'CCODIGO' => $laFila[6], 'CMONEDA' => $laFila[8],
                                'CDETENT' => $laFila[9], 'CNROCEL'  => $laFila[10],'CTIEVAL' => $laFila[11],'CTIPPRE' => $laFila[12],
                                'CDESTPR' => $laFila[13],'CTIPFPA'  => $laFila[14],'CDESFPA' => $laFila[15],'CFORPAG' => $laFila[16],
                                'CLUGAR'  => $laFila[17],'CSIMBOL'  => $laFila[18],'CEMAIL'  => $laFila[19],'CDESCRI' => $laFila[20],
                                'NTIEENT' => $laFila[21],'NTIEVAL'  => $laFila[22],'CARCPRV' => $laFila[23],'CDNIVEN' => $laFila[24],
                                'CNOMVEN' => $laFila[25],'CEDICIO'  => $lcEdicio, 'CARCHIV' => (file_exists($lcPath1))? 'S' : 'N',
                                'CARCCOT' => (file_exists($lcPath2))? 'S' : 'N'];
            $lcIdCoti = $laFila[0];
            $i++;
         }
      }
      return true;
   }

   public function omInitDetalleCotizacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDetalleCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitDetalleCotizacion($p_oSql) {
      #TRAER TIPO DE PRECIO
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '096'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 1;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipPre[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE PUDIERON RECUPERAR LOS TIPOS DE PRECIO S01TTAB[096]';
         return false;
      }
      #TRAER FORMAS DE PAGO
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '097' AND cCodigo NOT IN ('01','07')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 1;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paForPag[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE PUDIERON RECUPERAR LAS FORMAS DE PAGO S01TTAB[097]';
         return false;
      }
      #TRAER DETALLE COTIZACION
      $lcSql = "SELECT A.nSerial, A.cCodigo, A.cCodArt, A.cDescri, A.mObserv, A.nCantid, A.nPrecio, B.cDescri AS cDesArt, B.cEstado, 
                       B.cUnidad, A.cMarca, A.cIncIgv, C.cDescri AS cDesUni FROM E01DCOT A
                  INNER JOIN E01MART B ON B.cCodArt = A.cCodArt 
                  LEFT OUTER JOIN S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad 
                  WHERE A.cCodigo ='{$this->pcCodigo}' ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      $i = 1;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCODIGO' => $laFila[1], 'CCODART' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'MOBSERV' => $laFila[4], 'NCANTID' => $laFila[5], 'NPRECIO' => $laFila[6], 'CDESART' => $laFila[7],
                             'CESTADO' => $laFila[8], 'CUNIDAD' => $laFila[9], 'CMARCA'  => $laFila[10],'CINCIGV' => $laFila[11],
                             'CDESUNI' => $laFila[12]];
         $i++;
      }
      return true;
   }

   public function omGrabarDetalleCotizacion() {
      $llOk = $this->mxValGrabarDetalleCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarDetalleCotizacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxSubirArchivoProveedorCotizacion();
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarDetalleCotizacion() {
      if (!isset($this->paData['CCODIGO']) || strlen(trim($this->paData['CCODIGO'])) != 8) {
         $this->pcError = "COTIZACIÓN DE PROVEEDOR INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['NTIEENT']) || strlen(trim($this->paData['NTIEENT'])) < 1 || strlen(trim($this->paData['NTIEENT'])) > 3) {
         $this->pcError = "TIEMPO DE ENTREGA INVALIDO";
         return false;
      } elseif (!isset($this->paData['CDETENT']) || strlen(trim($this->paData['CDETENT'])) < 24 || strlen(trim($this->paData['CDETENT'])) > 26) {
         $this->pcError = "DETALLE DE ENTREGA (TIEMPO) INVALIDO";
         return false;
      } elseif (!isset($this->paData['CNROCEL']) || strlen(trim($this->paData['CNROCEL'])) < 6 || strlen(trim($this->paData['CNROCEL'])) > 12) {
         $this->pcError = "CELULAR INVALIDO";
         return false;
      } elseif (!isset($this->paData['CEMAIL']) || strlen(trim($this->paData['CEMAIL'])) < 7 || strlen(trim($this->paData['CEMAIL'])) > 90) {
         $this->pcError = "EMAIL INVALIDO";
         return false;
      } elseif (!isset($this->paData['NTIEVAL']) || strlen(trim($this->paData['NTIEVAL'])) < 1 || strlen(trim($this->paData['NTIEVAL'])) > 2) {
         $this->pcError = "TIEMPO DE VALIDEZ DE COTIZACIÓN INVALIDO";
         return false;
      } elseif (!isset($this->paData['CTIEVAL']) || strlen(trim($this->paData['CTIEVAL'])) < 28 || strlen(trim($this->paData['CTIEVAL'])) > 29) {
         $this->pcError = "DETALLE DE VALIDEZ DE COTIZACIÓN (TIEMPO) INVALIDO";
         return false;
      } elseif (!isset($this->paData['CTIPPRE']) || strlen(trim($this->paData['CTIPPRE'])) != 2) {
         $this->pcError = "TIPO DE PRECIO SELECCIONADO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTIPFPA']) || strlen(trim($this->paData['CTIPFPA'])) != 2) {
         $this->pcError = "FORMA DE PAGO INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CNRORUC']) || strlen(trim($this->paData['CNRORUC'])) != 11) {
         $this->pcError = "RUC INVÁLIDO";
         return false;
      } elseif (!isset($this->paDatos) || count($this->paDatos) == 0) {
         $this->pcError = "DETALLE DE LA COTIZACIÓN INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CDNIVEN']) || strlen(trim($this->paData['CDNIVEN'])) != 8) {
         $this->pcError = "DNI DEL VENDEDOR INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CAPEPAT']) || strlen(trim($this->paData['CAPEPAT'])) < 1 || strlen(trim($this->paData['CAPEPAT'])) > 30) {
         $this->pcError = "APELLIDO PATERNO DEL VENDEDOR INVALIDO";
         return false;
      } elseif (!isset($this->paData['CAPEMAT']) || strlen(trim($this->paData['CAPEMAT'])) < 1 || strlen(trim($this->paData['CAPEMAT'])) > 30) {
         $this->pcError = "APELLIDO MATERNO DEL VENDEDOR INVALIDO";
         return false;
      } elseif (!isset($this->paData['CNOMBRE']) || strlen(trim($this->paData['CNOMBRE'])) < 1 || strlen(trim($this->paData['CNOMBRE'])) > 30) {
         $this->pcError = "NOMBRE(S) DEL VENDEDOR INVALIDO";
         return false;
      }
      $this->paData['CNOMVEN'] = $this->paData['CAPEPAT'].'/'.$this->paData['CAPEMAT'].'/'.$this->paData['CNOMBRE'];
      return true;
   }

   protected function mxGrabarDetalleCotizacion($p_oSql) {
      foreach ($this->paDatos as $laFila) {
         $laFila['CNRORUC'] = $this->paData['CNRORUC'];
         $lcJson = json_encode($laFila);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01DCOT_1('$lcJson')";
         $RS = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($RS);
         $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION SQL"}]' : $laFila[0];
         $this->paDatos = json_decode($laFila[0], true);
         if (!empty($this->paDatos[0]['ERROR'])) {
            $this->pcError = $this->paDatos[0]['ERROR'];
            return false;
         }
      }
      $lcCodigo = $this->paData['CCODIGO'];
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01PCOT_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData[0]['ERROR'])) {
         $this->pcError = $this->paData[0]['ERROR'];
         return false;
      }
      $this->paData = ['CCODIGO' => $lcCodigo];
      return true;
   }

   # Subir Archivo de la Cotizacion - Proveedor
   # 2019-02-05 JLF Creacion
   public function mxSubirArchivoProveedorCotizacion() {
      $laErrFil = array(
         0 => 'ARCHIVO SUBIDO CON EXITO',
         1 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO EL SERVIDOR',
         2 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO POR USUARIO',
         3 => 'EL ARCHIVO FUE PARCIALMENTE CARGADO',
         4 => 'EL ARCHIVO NO FUE CARGADO',
         6 => 'FALTA UNA CARPETA TEMPORAL',
         7 => 'ERROR AL ESCRIBIR EL ARCHIVO EN EL DISCO',
         8 => 'UNA EXTENSIÓN DE PHP DETUVO LA CARGA DEL ARCHIVO',
      );
      $lcCodigo = $this->paData['CCODIGO'];
      if ($this->poFile['error'] != 0 && $this->poFile['error'] != 4) {
         $this->pcError = $laErrFil[$this->poFile['error']];
         return false;
      } elseif ($this->poFile['error'] == 4) {
         return true;
      }
      $llOk = fxSubirPDF($this->poFile, 'Logistica/Cotizacion', $lcCodigo);
      if(!$llOk) {
         $this->pcError = "HA OCURRIDO UN ERROR AL SUBIR ARCHIVO ".$this->poFile['name'];
         return false;
      }
      return true;
   }

   #GENERAR COTIZACION - INIT # MIGUEL # erp1160.php
   public function omInitGenerarCotizacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGenerarCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitGenerarCotizacion($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      #TRAER CABECERA DE REQUERIMIENTOS
      $lcSql = "SELECT A.cIdRequ, TRIM(A.cCenCos), B.cDescri AS cDesCCo, A.cDescri AS cDesReq, A.mObserv, A.cEstado, A.cTipo, 
                       C.cDescri AS cDesTip, D.cDescri AS cDesMon, A.cNroDoc, TRIM(B.cCodAnt) 
                     FROM E01MREQ A
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                     LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
                  WHERE A.cUsuCot = '$lcCodUsu' AND A.cEstado IN ('A','B') AND A.cComDir = 'N' AND A.cTipo NOT IN ('R','A','E','M')
                  ORDER BY A.cIdRequ DESC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESREQ' => $laFila[3],
                              'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CTIPO'   => $laFila[6], 'CDESTIP' => $laFila[7],
                              'CDESMON' => $laFila[8], 'CNRODOC' => $laFila[9], 'CCODANT' => $laFila[10]];
         $i++;
      }
      return true;
   }

   #GENERAR COTIZACION - CONSOLIDACION # MIGUEL # erp1160.php
   public function omConsolidarRequerimientos() {
      if ($this->paData == null) {
         $this->pcError = "TIENE QUE SELECCIONAR UNO O MAS REQUERIMIENTOS";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxConsolidarRequerimientos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxConsolidarRequerimientos($p_oSql) {
      $laDatos = null;
      #TRAER DETALLE DE CADA REQUERIMIENTO (PRODUCTOS, CANTIDADES Y DESCRIPCION DE CADA DETALLE)
      foreach ($this->paData as $laFila) {
         $lcIdRequ = $laFila['CIDREQU'];
         $lcSql = "SELECT A.nSerial, A.cCodArt, B.cDescri, A.cDescri as DesDet, A.nCantid, A.nPrecio, B.cUnidad FROM E01DREQ A
                        INNER JOIN E01MART B ON B.cCodArt = A.cCodArt
                        WHERE cIdRequ = '$lcIdRequ' AND A.cEstado = 'A' AND A.nCantid != A.nCanAte";
         $RS = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($RS)) {
            $laFila['ADETREQ'][] = ['NSERIAL' => $laTmp[0], 'CCODART' => $laTmp[1], 'CDESART' => $laTmp[2],
                'CDESDET' => $laTmp[3], 'NCANTID' => $laTmp[4], 'NPRECIO' => $laTmp[5],
                'CUNIDAD' => $laTmp[6]];
         }
         $laDatos[] = $laFila;
      }
      $this->paDatos = $laDatos;
      return true;
   }

   #GENERAR COTIZACION - CREAR COTIZACION # MIGUEL # erp1160.php
   public function omGenerarCotizacion() {
      $llOk = $this->mxValParamGenerarCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGenerarCotizacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return $llOk;
      }
      $llOk = $this->mxSubirArchivoAdicionalCotizacion();
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGenerarCotizacion() {
      if (!isset($this->paData['CDESCOT']) || empty($this->paData['CDESCOT']) || strlen($this->paData['CDESCOT']) > 200) {
         $this->pcError = "DESCRIPCION DE COTIZACION INVALIDA";
         return false;
      } else if (!isset($this->paData['CARCHIV']) || empty($this->paData['CARCHIV']) || strlen($this->paData['CARCHIV']) != 1) {
         $this->pcError = "OPCION DE SUBIR ARCHIVOS A COTIZACION INVALIDA";
         return false;
      } else if ($this->paData['MDATOS'] == null) {
         $this->pcError = "TIENE QUE SELECCIONAR UNO O MAS REQUERIMIENTOS";
         return false;
      }
      $this->paData['CDESCOT'] = strtoupper($this->paData['CDESCOT']);
      return true;
   }

   protected function mxGenerarCotizacion($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MCOT_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $this->paData['CIDCOTI'] = $this->paDatos[0]['CIDCOTI'];
      return true;
   }

   # Subir Archivo Adicional para la Cotizacion
   # 2019-02-04 JLF Creacion
   public function mxSubirArchivoAdicionalCotizacion() {
      $laErrFil = array(
         0 => 'ARCHIVO SUBIDO CON EXITO',
         1 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO EL SERVIDOR',
         2 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO POR USUARIO',
         3 => 'EL ARCHIVO FUE PARCIALMENTE CARGADO',
         4 => 'EL ARCHIVO NO FUE CARGADO',
         6 => 'FALTA UNA CARPETA TEMPORAL',
         7 => 'ERROR AL ESCRIBIR EL ARCHIVO EN EL DISCO',
         8 => 'UNA EXTENSIÓN DE PHP DETUVO LA CARGA DEL ARCHIVO',
      );
      $lcIdCoti = $this->paData['CIDCOTI'];
      if ($this->poFile['error'] != 0 && $this->poFile['error'] != 4) {
         $this->pcError = $laErrFil[$this->poFile['error']];
         return false;
      } elseif ($this->poFile['error'] == 4) {
         return true;
      }
      $llOk = fxSubirPDF($this->poFile, 'Logistica/Planimetria', $lcIdCoti);
      if(!$llOk) {
         $this->pcError = "HA OCURRIDO UN ERROR AL SUBIR ARCHIVO ".$this->poFile['name'];
         return false;
      }
      return true;
   }

   #ASIGNACION DE REQUERIMIENTOS A COTIZADORES - INIT # MIGUEL # ERP2110.php
   public function omInitAsignarCotizador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignarCotizador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAsignarCotizador($p_oSql) {
      #TRAER USUARIOS COTIZADORES
      $lcSql = "SELECT A.cCodUsu, A.cNroDni, B.cNombre, cNivel FROM S01TUSU A
                  INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni 
                  WHERE A.cEstado = 'A' AND A.cNivel IN ('CO','AL')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY USUARIOS COTIZADORES REGISTRADOS ACTUALMENTE";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CUSUCOT' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2], 'CNIVEL' => $laFila[3]];
      }
      #TRAER DESTINOS REQUERIMIENTO
      $lcSql = "SELECT cCodigo, cDescri FROM V_S01TTAB WHERE cCodTab = '110'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY DESTINOS DE REQUERIMIENTO [110]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDestin[] = ['CDESTIN' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAER PERIODOS DE PEDIDOS
      $lcSql = "SELECT DISTINCT TO_CHAR(tGenera, 'YYYY') AS cPeriod FROM E01MREQ ORDER BY cPeriod DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY PERIODOS DEFINIDOS PARA REQUERIMIENTO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paPeriod[] = ['CPERIOD' => $laFila[0], 'CDESCRI' => "AÑO ".$laFila[0]];
      }
      #TRAER CABECERA DE TODOS LOS REQUERIMIENTOS AUN SIN ASIGNAR
      $lcSql = "SELECT A.cIdRequ, TRIM(A.cCenCos), B.cDescri, A.cDescri, A.mObserv, A.cEstado, A.cTipo, C.cDescri as cDesTip, 
                       D.cDescri, TO_CHAR(A.TGENERA,'YYYY-MM-DD HH24:MI') AS tGenera
                     FROM E01MREQ A
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                     LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
                     INNER JOIN P02MACT E ON E.cIdActi = A.cIdActi
                  WHERE A.cUsuCot = '9999' AND A.cEstado IN ('A','B') AND A.cTipo NOT IN ('R','A','E','M') AND E.cTipAct NOT IN ('E') 
                        AND TO_CHAR(A.tGenera, 'YYYY') = '{$this->paPeriod[0]['CPERIOD']}'
                  ORDER BY A.tGenera, A.cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR AL CARGAR REQUERIMIENTOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESCRI' => $laFila[3],
                              'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CTIPO'   => $laFila[6], 'CDESTIP' => $laFila[7],
                              'CDESMON' => $laFila[8], 'TGENERA' => $laFila[9]];
      }
      return true;
   }

   #ASIGNACION DE REQUERIMIENTOS A COTIZADORES - SELECCIONAR # MIGUEL # ERP2110.php
   public function omBuscarReqDeCotizador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarReqDeCotizador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarReqDeCotizador($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      #TRAER CABECERA DE TODOS LOS REQUERIMIENTOS ASIGNADOS AL COTIZADOR SELECCIONADO
      $lcSql = "SELECT A.cIdRequ, TRIM(A.cCenCos), B.cDescri, A.cDescri, A.mObserv, A.cEstado, A.cTipo, C.cDescri as cDesTip, 
                       D.cDescri, TO_CHAR(A.TGENERA,'YYYY-MM-DD HH24:MI') AS tGenera
                     FROM E01MREQ A
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                     LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
                  WHERE A.cUsuCot = '$lcUsuCot' AND A.cEstado IN ('A','B') AND A.cTipo NOT IN ('R','A','E','M')
                  ORDER BY A.tGenera, A.cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CTIPO'   => $laFila[6], 'CDESTIP' => $laFila[7],
                             'CDESMON' => $laFila[8], 'TGENERA' => $laFila[9]];
         $i++;
      }
      return true;
   }

   #ASIGNACION DE REQUERIMIENTOS A COTIZADORES - GRABAR # MIGUEL # ERP2110.php
   public function omAsignarReqACotizador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAsignarReqACotizador($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxAsignarReqACotizador($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_2('$lcJson')";
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

   #ASIGNACION DE PROVEEDORES A COTIZACIONES - INIT # MIGUEL # ERP2130.php *
   public function omInitAsignarProveedor() {
      $llOk = $this->mxValInitAsignarProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignarProveedor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitAsignarProveedor() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxInitAsignarProveedor($p_oSql) {
      #TRAER RUBROS DE PROVEEDORES
      $lcSql = "SELECT cRubro, cDescri FROM S01TRUB ORDER BY cTipRub, cRubro";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR AL RECUPERAR RUBROS DE PROVEEDORES";
         return false;
      }
      $j = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $i = 0;
         $lcSql = "SELECT A.cNroRuc, B.cRazSoc FROM S01DRUB A
                      INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                      WHERE A.cRubro = '$laFila[0]' AND B.cEstado = 'A'
                      ORDER BY B.cRazSoc";
         $R1 = $p_oSql->omExec($lcSql);
         if ($RS == false || $p_oSql->pnNumRow == 0) {
            continue;
         }
         while ($laTmp = $p_oSql->fetch($R1)) {
            $i++;
            if ($i == 1) {
               $this->paRubro[$j] = ['CRUBRO' => $laFila[0], 'CDESCRI' => $laFila[1]];
            }
            $this->paRubro[$j]['ANRORUC'][] = ['CNRORUC' => $laTmp[0], 'CRAZSOC' => $laTmp[1]];
         }
         $j++;
      }
      $lcUsuCot = $this->paData['CUSUCOD'];
      #TRAER CABECERA DE TODAS LAS COTIZACIONES PERTENECIENTES COTIZADOR
      $lcSql = "SELECT A.cIdCoti, TO_CHAR(A.tInicio,'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tFinali,'YYYY-MM-DD HH24:MI'), TRIM(A.cLugar), 
                       A.cDescri AS cDesCot, C.cCenCos, D.cDescri, C.cIdRequ, C.cDescri, C.tGenera, SUM(E.nCantid * E.nPrecio) AS nTotal
                     FROM E01MCOT A
                     INNER JOIN E01PREQ B ON B.cIdCoti = A.cIdCoti
                     INNER JOIN E01MREQ C ON C.cIdRequ = B.cIdRequ
                     INNER JOIN S01TCCO D ON D.cCenCos = C.cCenCos
                     INNER JOIN E01DREQ E ON E.cIdRequ = C.cIdRequ
                     WHERE A.cEstado = 'A' AND C.cUsuCot = '$lcUsuCot'
                     GROUP BY A.cIdCoti, D.cCenCos, C.cIdRequ
                     ORDER BY A.cIdCoti DESC, nTotal DESC, C.tGenera ASC, C.cIdRequ ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR AL RECUPERAR COTIZACIONES";
         return false;
      }
      $lcIdCoti = '';
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($lcIdCoti != $laFila[0]) {
            $lcPath = 'Docs/Logistica/Planimetria/'.$laFila[0].'.pdf';
            $this->paDatos[] = ['CIDCOTI' => $laFila[0], 'TINICIO' => $laFila[1], 'TFINALI' => (trim($laFila[3]) == '')? '' : $laFila[2],
                                'CLUGAR'  => $laFila[3], 'CDESCOT' => $laFila[4], 'CCENCOS' => $laFila[5], 'CDESCCO' => $laFila[6],
                                'CIDREQU' => $laFila[7], 'CDESREQ' => $laFila[8],
                                'CARCHIV' => (file_exists($lcPath))? 'CON ARCHIVO ADICIONAL' : 'SIN ARCHIVO ADICIONAL'];
            $lcIdCoti = $laFila[0];
         }
      }
      return true;
   }

   #ASIGNACION DE PROVEEDORES A COTIZACION # MIGUEL # ERP2130.php
   #BUSCAR PROVEEDORES ASIGNADOS A COTIZACION
   public function omBuscarPrvAsignadosACot() {
      $llOk = $this->mxValSeleccionarAsignarProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarAsignarProveedor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValSeleccionarAsignarProveedor() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDCOTI']) || strlen(trim($this->paData['CIDCOTI'])) != 8) {
         $this->pcError = "COTIZACION INVALIDA";
         return false;
      }
      return true;
   }

   protected function mxSeleccionarAsignarProveedor($p_oSql) {
      $lcIdCoti = $this->paData['CIDCOTI'];
      #TRAER TODOS LOS PROVEEDORES YA ASIGNADOS A LA COTIZACION
      $lcSql = "SELECT C.cNroRuc, C.cRazSoc, C.cSiglas FROM E01MCOT A
                     INNER JOIN E01PCOT B ON B.cIdCoti = A.cIdCoti
                     INNER JOIN S01MPRV C ON C.CNRORUC = B.CNRORUC
                  WHERE A.cEstado = 'A' AND B.cEstado = 'I' AND C.cEstado = 'A' AND A.cIdCoti = '$lcIdCoti'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR AL RECUPERAR PROVEEDORES ASIGNADOS A LA COTIZACION";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CSIGLAS' => $laFila[2]];
      }
      return true;
   }

   #ASIGNACION DE PROVEEDORES A COTIZACION # MIGUEL # ERP2130.php
   public function omBuscarAsignarProveedor() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarAsignarProveedor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAsignarProveedor($p_oSql) {
      $lcBusPrv = str_replace(' ','%',strtoupper($this->paData['CBUSPRV']));
      #TRAER TODOS LOS PROVEEDORES BUSQUEDA X RUC O X DESCRIPCION
      $lcSql = "SELECT cNroRuc, cRazSoc, cSiglas FROM S01MPRV WHERE (cNroRuc = '$lcBusPrv' OR cCodAnt = '$lcBusPrv' OR cRazSoc LIKE '%$lcBusPrv%') AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CSIGLAS' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY PROVEEDORES DEFINIDOS ACTUALMENTE";
         return false;
      }
      return true;
   }

   #ASIGNACION DE REQUERIMIENTOS A COTIZADORES - GRABAR # MIGUEL # Erp2130.php
   public function omGrabarAsignarProveedor() {
      $llOk = $this->mxValParamGrabarAsignarProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarAsignarProveedor($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarAsignarProveedor() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDCOTI']) || strlen(trim($this->paData['CIDCOTI'])) != 8) {
         $this->pcError = "COTIZACION INVALIDA";
         return false;
      } elseif (!isset($this->paData['CLUGAR']) || strlen(trim($this->paData['CLUGAR'])) == 0 || strlen(trim($this->paData['CLUGAR'])) > 100) {
         $this->pcError = "LUGAR DE ENTREGA INVALIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO']) || strlen(trim($this->paData['CESTADO'])) != 1) {
         $this->pcError = "ESTADO INVALIDO";
         return false;
      } elseif (!isset($this->paData['TINICIO']) || strlen(trim($this->paData['TINICIO'])) != 16) {
         $this->pcError = "FECHA DE INICIO INVALIDA";
         return false;
      } elseif (!isset($this->paData['TFINALI']) || strlen(trim($this->paData['TFINALI'])) != 16) {
         $this->pcError = "FECHA DE FIN INVALIDA";
         return false;
      } elseif ($this->paData['TFINALI'] <= $this->paData['TINICIO']) {
         $this->pcError = 'FECHA FIN ES MENOR O IGUAL A FECHA INICIO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || count($this->paData['MDATOS']) == 0) {
         $this->pcError = "PROVEEDORES DE COTIZACION INVALIDOS";
         return false;
      }
      return true;
   }

   protected function mxGrabarAsignarProveedor($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01PCOT_1('$lcJson')";
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

   #GENERACION DE ORDEN DE COMPRA --- Erp2160.php
   public function omSeleccionarGenerarOC() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarGenerarOC($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxSeleccionarGenerarOC($p_oSql) {
      $lcIdCoti = $this->paData['CIDCOTI'];
      #TRAER TODOS LOS ARTICULOS DE LA COTIZACION DEL RUC  00000000000
      $lcSql = "SELECT A.cNroRuc, A.cCodArt, B.cDescri, A.nPreUni, A.nCantid, A.nSerial, TRIM(A.cDetall), A.nOrden, B.cUnidad, 
                       C.cDescri AS cDesUni FROM F_E01DCOT_1('$lcIdCoti') A
                INNER JOIN E01MART B ON B.cCodArt = A.cCodArt
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNRORUC' => $laFila[0], 'CCODART' => $laFila[1], 'NCANTID' => number_format($laFila[4], 4),
                             'CDESART' => $laFila[2], 'NPREUNI' => $laFila[3], 'NSERIAL' => $laFila[5], 'CDETALL' => $laFila[6],
                             'NORDEN'  => $laFila[7], 'CUNIDAD' => $laFila[8], 'CDESUNI' => $laFila[9]];
         $i++;
      }
      #TRAER TODOS LOS PROVEEDORES YA ASIGNADOS A LA COTIZACION
      $lcSql = "SELECT C.cNroRuc, C.cRazSoc, C.cSiglas FROM E01MCOT A
                     INNER JOIN E01PCOT B ON B.cIdCoti = A.cIdCoti
                     INNER JOIN S01MPRV C ON C.cNroRuc = B.cNroRuc
                  WHERE B.cEstado = 'E' AND C.cEstado = 'A' AND A.cIdCoti = '$lcIdCoti'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paNroRuc[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CSIGLAS' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY PROVEEDORES QUE ENVIARAN SU COTIZACION";
         return false;
      }
      $this->paNroRuc[] = ['CNRORUC' => '00000000000', 'CRAZSOC' => 'PROVEEDOR SIN DEFINIR', 'CSIGLAS' => 'PROVEEDOR SIN DEFINIR'];
      return true;
   }

   ##CONSULTA COTIZACION
   ######/LLENADO DE COTIZACION #############
   public function omInitConsultaCotizacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitConsultaCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitConsultaCotizacion($p_oSql) {
      #TRAER COTIZACIONES
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcSql = "SELECT A.cIdCoti, A.cEstado, TO_CHAR(A.tInicio,'YYYY-MM-DD HH:MI'), TO_CHAR(A.tFinali,'YYYY-MM-DD HH:MI'), A.cUsuCod, B.cDescri, 
                       E.cUsucot, E.cDescri AS cDesReq, E.cCenCos, F.cDescri, E.cIdRequ, E.tGenera, SUM(G.nCantid * G.nPrecio) AS nTotal
                  FROM E01MCOT A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '078' AND B.cCodigo = A.cEstado
                  INNER JOIN E01PREQ D ON D.cIdCoti = A.cIdCoti
                  INNER JOIN E01MREQ E ON E.cIdRequ =  D.cIdRequ
                  INNER JOIN S01TCCO F ON F.cCenCos = E.cCenCos 
                  INNER JOIN E01DREQ G ON G.cIdRequ = E.cIdRequ
                  WHERE E.cUsucot='$lcUsuCot' 
                  GROUP BY A.cIdCoti, B.cDescri, E.cUsucot, E.cDescri, E.cCenCos, F.cDescri, E.cIdRequ, E.tGenera
                  ORDER BY A.cIdCoti DESC, nTotal DESC, E.tGenera ASC, E.cIdRequ ASC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      $lcIdCoti = '';
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($lcIdCoti != $laFila[0]) {
            $this->paDatos[] = ['CIDCOTI' => $laFila[0], 'TINCIO'  => $laFila[2], 'TFINALI' => $laFila[3], 'CUSUCOD' => $laFila[4],
                                'CESTADO' => $laFila[5], 'CDESREQ' => $laFila[7], 'CCENCOS' => $laFila[8], 'CDESCCO' => $laFila[9],
                                'CIDREQU' => $laFila[10]];
            $lcIdCoti = $laFila[0];
         }
         $i++;
      }
      return true;
   }

   #GENERACION DE ORDEN DE COMPRA - INIT # MIGUEL # Erp2130.php *
   public function omInitGenerarOC() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGenerarOC($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitGenerarOC($p_oSql) {
      $lcUsuCot = $this->paData['CCODUSU'];
      #TRAER CABECERA DE TODAS LAS COTIZACIONES PERTENECIENTES COTIZADOR
      $lcSql = "SELECT A.cIdCoti, TO_CHAR(A.tInicio,'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tFinali,'YYYY-MM-DD HH24:MI'), A.cSituac, 
                       C.cDescri AS cDesReq, C.cCenCos, D.cDescri AS cDesCCo, C.cNroDoc, C.cCodUsu, F.cNombre, TO_CHAR(C.tGenera, 'YYYY-MM-DD'), 
                       C.cIdRequ, SUM(E.nCantid * E.nPrecio) AS nTotal  
                  FROM E01MCOT A
                  INNER JOIN E01PREQ B ON B.CIDCOTI = A.CIDCOTI
                  INNER JOIN E01MREQ C ON C.CIDREQU = B.CIDREQU
                  INNER JOIN S01TCCO D ON D.cCenCos = C.cCenCos
                  INNER JOIN E01DREQ E ON E.cIdRequ = C.cIdRequ
                  LEFT OUTER JOIN V_S01TUSU_1 F ON F.cCodUsu = C.cCodUsu
                  WHERE A.cEstado IN ('B') AND C.cUsuCot = '$lcUsuCot' AND A.cSituac IN ('0','2')
                  GROUP BY A.cIdCoti, D.cDescri, C.cIdRequ, F.cNombre
                  ORDER BY A.cIdCoti DESC, nTotal DESC, C.tGenera ASC, C.cIdRequ ASC";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      $lcIdCoti = '';
      while ($laFila = $p_oSql->fetch($RS)) {
         if($lcIdCoti != $laFila[0]) {
            $this->paDatos[] = ['CIDCOTI' => $laFila[0], 'TINICIO' => $laFila[1], 'TFINALI' => $laFila[2], 'CSITUAC' => $laFila[3],
                                'CDESREQ' => $laFila[4], 'CCENCOS' => $laFila[5], 'CDESCCO' => $laFila[6], 'CNRODOC' => $laFila[7],
                                'CUSUREQ' => $laFila[8], 'CNOMUSU' => $laFila[9], 'DGENREQ' => $laFila[10],'CIDREQU' => $laFila[11]];
            $lcIdCoti = $laFila[0];
            $i++;
         }
      }
      return true;
   }

   #ASIGNACION DE REQUERIMIENTOS A COTIZADORES - GRABAR # MIGUEL # Erp2160.php
   public function omGrabarGenerarOC() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarGenerarOC($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarGenerarOC($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01DCOT_2('$lcJson')";
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

   #ASIGNACION DE STOCK EN ALMACEN A REQUERIMIENTO ERP21X0 BEHAVIOR 0
   public function omInitAsignarStock() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignarStock($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAsignarStock($p_oSql) {
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, 
                       A.cIdActi, A.cDesAct, A.mObserv FROM V_E01MREQ_1 A
                     LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                  WHERE A.CESTADO = 'A' AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC;";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESREQ' => $laFila[3],
                             'CNOMCOT' => $laFila[4], 'MOBSERV' => $laFila[5], 'CESTADO' => $laFila[7], 'CCODUSU' => $laFila[8],
                             'CNOMBRE' => $laFila[9], 'CIDACTI' => $laFila[10],'CDESACT' => $laFila[11],'MOBSANT' => $laFila[12],
                             'MOBSERV' => ''];
      }
      return true;
   }

   public function omVerArticulosEntregados() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerArticulosEntregados($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxVerArticulosEntregados($p_oSql) {
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcIdActi = $this->paData['CIDACTI'];
      $lcSql = "SELECT A.cCodArt,A.cDescri,A.nCanApr,B.cDescri FROM P02DART A
                     LEFT JOIN V_S01TTAB B ON B.cCodTab = '074' AND B.cCodigo = A.cUniMed
                  WHERE A.cIdActi = '$lcIdActi' ORDER BY A.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos['AARTAPR'][] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'NCANTID' => $laFila[2],'CUNIMED' => $laFila[3]];
      }
      $lcSql = "SELECT C.cCodArt,D.cDescri,SUM(C.nCantid),E.cDescri FROM E01MREQ A
                     INNER JOIN E03MKAR B ON B.cIdRequ = A.cIdRequ
                     INNER JOIN E03DKAR C ON C.cIdKard = B.cIdKard
                     INNER JOIN E01MART D ON D.cCodArt = C.cCodArt
                     LEFT JOIN V_S01TTAB E ON E.cCodTab = '074' AND E.cCodigo = D.cUnidad
                  WHERE A.cEstado = 'T' AND  A.cIdActi = '$lcIdActi' AND C.nCantid > 0 GROUP BY C.cCodArt,D.cDescri,E.cDescri ORDER BY D.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos['AARTENT'][] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'NCANTID' => $laFila[2],'CUNIMED' => $laFila[3]];
      }
      return true;
   }

#ASIGNACION DE STOCK EN ALMACEN A REQUERIMIENTO ERP21X0 BEHAVIOR 1

   public function omInitAsignarDetalleStock() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignarDetalleStock($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAsignarDetalleStock($p_oSql) {
      $lcIdReq = $this->paData['CIDREQU'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcCenCos = $this->paData['CCENCOS'];
      #TRAE ALAMCEN DE USUARIO ORIGEN ENCARGADO  XDDDDDDDDD :v
      $lcSql = "SELECT A.cCodAlm, B.cDescri FROM E03PUSU A
               INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm WHERE A.cCodUsu = '$lcCodUsu' LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcCodAlm = $laFila[0];
      }
      $lcSql = "SELECT nSerial, cCodArt, cDesArt, nCantid, cDescri,nPreArt FROM V_E01DREQ_1 WHERE cIdRequ = '" . $this->paData['CIDREQU'] . "' AND nCantid != nCanate ORDER BY cDesArt ASC;";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcArticu = $laFila[1];
         $lcSql1 = "SELECT nStock FROM E03PALM WHERE cCodArt = '$lcArticu' AND cCodAlm = '$lcCodAlm';";
         $R2 = $p_oSql->omExec($lcSql1);
         $lnStkact = 0;
         while ($laFila1 = $p_oSql->fetch($R2)) {
            $lnStkact = $laFila1[0];
         }
         $lcSql1 = "SELECT SUM(A.nCantid) FROM E03DKAR A
                    INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard  WHERE B.cCenCos = '$lcCenCos'  AND A.cCodArt = '$lcArticu' GROUP BY B.cCenCos, A.cCodArt;";
         $R2 = $p_oSql->omExec($lcSql1);
         $lnSalEje = 0;
         while ($laFila1 = $p_oSql->fetch($R2)) {
            $lnSalEje = $laFila1[0];
         }
         $lcSql1 = "SELECT SUM(A.nCanSol) FROM E09DREQ A
                    LEFT OUTER JOIN E01DREQ B ON B.nSerial = A.nRefSer
                    WHERE B.cCodArt = '$lcArticu' AND A.cEstado = 'P' AND B.cIdRequ !='$lcIdReq'";
         $lnSalCom = 0;
         $R2 = $p_oSql->omExec($lcSql1);
         $laFila1 = $p_oSql->fetch($R2);
         $lnSalCom = $laFila1[0];
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCODART' => $laFila[1],
             'CDESART' => $laFila[2], 'NCANTID' => $laFila[3], 'CDESCRI' => $laFila[4], 'NPREART' => $laFila[5],
             'STKACT' => $lnStkact, 'NSALCOM' => $lnSalCom, 'NSALEJE' => $lnSalEje];
         $i++;
      }
      #Validar error (i)
      return true;
   }

   public function omGrabarAsignacionStock() {
      $llOk = $this->mxValParamGrabarAsignacionStock();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarAsignacionStock($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarAsignacionStock() {

      if (!isset($this->paData['CCODUSU'])) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      }
      $i = $j = 0;
      foreach ($this->paData['MDATOS'] as $laTmp) {
         if (isset($laTmp['NSERENT']) && isset($laTmp['NSERCOT'])) {
            $this->pcError = 'NO SE PUEDE ENTREGAR Y COTIZAR AL MISMO TIEMPO';
            return false;
         } else if (isset($laTmp['NSERENT'])) {
            $this->paData['MDATOS'] [$i]['NSERCOT'] = '0';
         } else if (isset($laTmp['NSERCOT'])) {
            $this->paData['MDATOS'] [$i]['NSERENT'] = '0';
         } else {
            $this->paData['MDATOS'] [$i]['NSERENT'] = '0';
            $this->paData['MDATOS'] [$i]['NSERCOT'] = '0';
            $j++;
         }
         $i++;
      }
      if ($j == count($this->paData['MDATOS'])) {
         $this->pcError = 'DEBE MARCAR AL MENOS UNA OPCIÓN';
         return false;
      }
      return true;
   }

   protected function mxGrabarAsignacionStock($p_oSql) {
      $lcJson = json_encode($this->paData);
      # PRINT_R($this->paData); DIE;
      $lcSql = "SELECT P_E09DREQ_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      /*
        foreach ($this->paDatos as $laFila) {
        $lcJson = json_encode($laFila);
        $lcSql = "SELECT P_E09DREQ_1('$lcJson')";
        $RS = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($RS);
        $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}]' : $laFila[0];
        $this->paDatos = json_decode($laFila[0], true);
        if (!empty($this->paDatos[0]['ERROR'])) {
        $this->pcError = $this->paDatos[0]['ERROR'];
        return false;
        }
        } */
      return true;
   }

   #INIT - 2910.PHP # REPORTE DE REQUERIMIENTOS POR COTIZADOR # BETO
   public function omInitRepReqCot() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRepReqCot($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitRepReqCot($p_oSql) {
      #TRAER USUARIOS COTIZADORES
      $lcSql = "SELECT A.cCodUsu, A.cNroDni, B.cNombre, cNivel FROM S01TUSU A
                  INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni WHERE A.cNivel = 'CO'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CUSUCOT' => $laFila[0], 'CNRODNI' => $laFila[1],
             'CNOMBRE' => $laFila[2], 'CNIVEL' => $laFila[3]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY USUARIOS COTIZADORES REGISTRADOS ACTUALMENTE";
         return false;
      }
      #TRAER ESTADOS [076]
      $lcSql = "SELECT cCodigo, cDescri FROM V_S01TTAB WHERE CCODTAB = '076'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR ESTADOS DE S01TTAB CON CODIGO [076]";
         return false;
      }
      return true;
   }

   #BUSCAR PROVEEDOR POR RUC, RAZ. SOCIAL O CODIGO ANTIGUO -- Erp1110 - Erp2230
   # 2018-10-18 JLF - Creacion
   public function omBuscarProveedor() {
      $llOk = $this->mxValBuscarProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarProveedor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarProveedor() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CBUSPRV']) || empty($this->paData['CBUSPRV']) || strlen(trim($this->paData['CBUSPRV'])) > 20) {
         $this->pcError = "CLAVE DE BUSQUEDA INVALIDA";
         return false;
      }
      return true;
   }

   protected function mxBuscarProveedor($p_oSql) {
      $lcBusPrv = $this->paData['CBUSPRV'];
      $lcBusPrv = str_replace(' ', '%', trim($lcBusPrv));
      $lcBusPrv = str_replace('#', 'Ñ', trim($lcBusPrv));
      #TRAER ARTICULOS X DESCRIPCION
      $lcSql = "SELECT cNroRuc, cRazSoc, cSiglas, cCodAnt, cNroCel, cEmail FROM S01MPRV
                WHERE (cNroRuc = '$lcBusPrv' OR cRazSoc LIKE '%$lcBusPrv%' OR cCodAnt = '$lcBusPrv') AND cEstado = 'A' ORDER BY cRazSoc";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = 'ERROR DE EJECUCION SQL';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY PROVEEDORES CON LA CLAVE DE BÚSQUEDA';
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcEdicio = 'S';
         #VALIDA SI PROVEEDOR PUEDE EDITAR CANTIDADES EN SU COTIZACION
         $lcSql = "SELECT cRubro FROM S01DRUB WHERE cNroRuc = '{$laFila[0]}' AND cRubro = '003'";
         $R2 = $p_oSql->omExec($lcSql);
         if ($R2 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
            return false;
         } elseif ($p_oSql->pnNumRow == 0) {
            $lcEdicio = 'N';
         }
         $this->paNroRuc[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CSIGLAS' => $laFila[2], 'CCODANT' => $laFila[3],
                              'CNROCEL' => $laFila[4], 'CEMAIL'  => $laFila[5], 'CEDICIO' => $lcEdicio];
      }
      return true;
   }

   #AJAX BUSCAR PROVEEDOR POR NRO RUC -- ERP1110* # MIGUEL
   #18-02-10 MMH CREACION
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
      #TRAER PROVEEDOR X NRO RUC
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

   #BEHAVIOR 1 - 2910.PHP # REPORTE DE REQUERIMIENTOS POR COTIZADOR # BETO
   public function omSeleccionarFiltro() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarFiltro($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxSeleccionarFiltro($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcEstado = $this->paData['CCODIGO'];
      #TRAER CABECERA DE TODOS LOS REQUERIMIENTOS ASIGNADOS AL COTIZADOR SELECCIONADO
      $lcSql = "SELECT A.cIdRequ, TRIM(A.cCenCos), B.cDescri, A.cDescri, A.mObserv, A.cEstado, A.cTipo, C.cDescri as cDesTip, 
                       D.cDescri, TO_CHAR(A.TGENERA,'YYYY-MM-DD HH24:MI') AS tGenera FROM E01MREQ A
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                     LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
                  WHERE A.cUsuCot = '$lcUsuCot' AND A.cEstado = '$lcEstado' AND A.cTipo NOT IN ('R','A','E','M')
                     AND A.cIdRequ NOT IN (SELECT DISTINCT B.cIdRequ FROM E01MCOT A
                                             INNER JOIN E01PREQ B ON B.cIdCoti = A.cIdCoti
                                             WHERE A.cEstado = 'A')
                  ORDER BY tGenera, A.cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CTIPO'   => $laFila[6], 'CDESTIP' => $laFila[7],
                             'CDESMON' => $laFila[8], 'TGENERA' => $laFila[9]];
         $i++;
      }
      return true;
   }

   #BEHAVIOR 1 - ORDENES DE COMPRA POR COTIZADOR - ALBERTO - 2018-02-14
   public function omInitOCSCotizador() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitOCSCotizador($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitOCSCotizador($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      #TRAER TODOS LAS CABECERAS DE ORDENES DE COMPRA/SERVICIO
      $lcSql = "SELECT DISTINCT cIdOrde, cNroRuc, dGenera, cDesTip, cIncIgv, nMonto, cDesMon FROM V_E01MORD_9 WHERE cUsuCot = '$lcUsuCot'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDORDE' => $laFila[0], 'CNRORUC' => $laFila[1],
             'DGENERA' => $laFila[2], 'CTIPO' => $laFila[3],
             'CINCIGV' => $laFila[4], 'NMONTO' => $laFila[5],
             'CMONEDA' => $laFila[6]];
         $i++;
      }
      return true;
   }

   #Busca Centro de Costo por descripcion
   public function omBuscarCentroCostoxDescripcion() {
      $llOk = $this->mxValBuscarCentroCostoxDescripcion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarCentroCostoxDescripcion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarCentroCostoxDescripcion() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CBUSCCO']) || empty(trim($this->paData['CBUSCCO'])) || strlen(trim($this->paData['CBUSCCO'])) > 40) {
         $this->pcError = "CLAVE DE BUSQUEDA INVALIDA";
         return false;
      } elseif (isset($this->paData['CFILTRO']) && strlen(trim($this->paData['CFILTRO'])) != 1) {
         $this->pcError = "FILTRO DE BUSQUEDA INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarCentroCostoxDescripcion($p_oSql) {
      $lcBusCCo = $this->paData['CBUSCCO'];
      $lcBusCCo = str_replace(' ', '%', trim($lcBusCCo));
      $lcBusCCo = str_replace('#', 'Ñ', trim($lcBusCCo));
      #TRAER ARTICULOS X DESCRIPCION
      $lcSql = "SELECT cCenCos, cDescri, cCodAnt FROM S01TCCO WHERE (cCenCos = '$lcBusCCo' OR cCodAnt = '$lcBusCCo' OR cDescri LIKE '%$lcBusCCo%') AND cEstado = 'A'";
      if (isset($this->paData['CFILTRO']) && $this->paData['CFILTRO'] == 'S') {
         $lcSql .= " AND cCenCos IN (SELECT cCenCos FROM P02MACT WHERE cPeriod IN (SELECT cPeriod FROM S01TPER WHERE cTipo = 'S0') AND cEstado IN ('A','D') AND cTipAct IN ('I','E','C','U','L','S','M','A','P','T','R','V','Q'))";
      }
      $lcSql .= " ORDER BY cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION SQL";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY CENTROS DE COSTO CON LA CLAVE DE BUSQUEDA";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCODANT' => $laFila[2]];
      }
      return true;
   }

   #AJAX BUSCAR PROVEEDOR POR NRO RUC -- ERP1140* # FER
   #18-02-16 FRL CREACION
   public function omBuscarUsuarioxCodigo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarUsuarioxCodigoc($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarUsuarioxCodigoc($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE CCODUSU = '$lcCodUsu' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION SQL";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paDatos = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1]];
      return true;
   }

   #AJAX BUSCAR USUARIO POR NOMBRE O CODIGO -- Erp2290 # JLF
   public function omBuscarCodigoUsuario() {
      $llOk = $this->mxValBuscarCodigoUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarCodigoUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarCodigoUsuario() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CBUSUSU']) || empty(trim($this->paData['CBUSUSU'])) || strlen(trim($this->paData['CUSUCOD'])) > 40) {
         $this->pcError = "CLAVE DE BUSQUEDA INVALIDA";
         return false;
      }
      return true;
   }

   protected function mxBuscarCodigoUsuario($p_oSql) {
      $lcBusUsu = $this->paData['CBUSUSU'];
      $lcBusUsu = str_replace(' ', '%', trim($lcBusUsu));
      $lcBusUsu = str_replace('#', 'Ñ', trim($lcBusUsu));
      #TRAER USUARIOS
      $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1
                WHERE (cCodUsu = '$lcBusUsu' OR cNombre LIKE '%$lcBusUsu%' OR cNroDni LIKE '$lcBusUsu') AND cEstado = 'A' ORDER BY cNombre";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCodUsu[] = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1]];
         $i++;
      }
      return true;
   }

   # BUSCAR EMPLEADO ENCARGADO PARA EMISION DE CHEQUES - Erp1110
   # 2019-09-26 JLF Creación
   public function omBuscarCodigoEmpleadoEncargado() {
      $llOk = $this->mxValBuscarCodigoEmpleadoEncargado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarCodigoEmpleadoEncargado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarCodigoEmpleadoEncargado() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CBUSUSU']) || empty(trim($this->paData['CBUSUSU'])) || strlen(trim($this->paData['CUSUCOD'])) > 40) {
         $this->pcError = "CLAVE DE BUSQUEDA INVALIDA";
         return false;
      }
      return true;
   }

   protected function mxBuscarCodigoEmpleadoEncargado($p_oSql) {
      $lcBusUsu = $this->paData['CBUSUSU'];
      $lcBusUsu = str_replace(' ', '%', trim($lcBusUsu));
      $lcBusUsu = str_replace('#', 'Ñ', trim($lcBusUsu));
      #TRAER USUARIOS
      $lcSql = "SELECT A.cCodEmp, A.cNombre FROM V_P10MEMP A INNER JOIN S01TUSU B ON B.cCodUsu = A.cCodEmp
                WHERE (A.cCodEmp = '$lcBusUsu' OR A.cNombre LIKE '%$lcBusUsu%' OR A.cNroDni LIKE '$lcBusUsu') AND A.cEstado NOT IN ('00','09') 
                AND B.cEstado = 'A' ORDER BY cNombre";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCodUsu[] = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1]];
         $i++;
      }
      return true;
   }

   #REMITIR COTIZACION A USUARIO ORIGEN - UPDATE # ALBERTO # Erp2160.php
   public function omRemitir() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRemitir($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxRemitir($p_oSql) {
      $lcIdCoti = $this->paData['CIDCOTI'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "UPDATE E01MCOT SET cSituac = '1', cUsuCod = '$lcCodUsu', tModifi = NOW() WHERE cIdCoti = '$lcIdCoti'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR AL REMITIR LA COTIZACION AL USUARIO';
      }
      return $llOk;
   }

   #BEHAVIOR 0 - ERP11150 - CREACION - ALBERTO - 2018-02-20
   public function omInitCotizacionUsuario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCotizacionUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCotizacionUsuario($p_oSql) {
      #TRAER CABECERA DE TODAS LAS COTIZACIONES PERTENECIENTES COTIZADOR
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT A.cIdCoti, TO_CHAR(A.tInicio,'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tFinali,'YYYY-MM-DD HH24:MI'), C.cIdRequ, 
                       C.cDescri, C.cCenCos, D.cDescri AS cDesCCo FROM E01MCOT A
                     INNER JOIN E01PREQ B ON B.CIDCOTI = A.CIDCOTI
                     INNER JOIN E01MREQ C ON C.CIDREQU = B.CIDREQU
                     INNER JOIN S01TCCO D ON D.cCenCos = C.cCenCos
                  WHERE A.cEstado IN ('B') AND A.cSitUAc IN ('1') AND C.cCenCos = '$lcCenCos' 
                  GROUP BY A.cIdCoti, C.cIdRequ, D.cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY COTIZACIONES PENDIENTES DE SELECCION DE PROVEEDORES GANADORES";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDCOTI' => $laFila[0], 'TINICIO' => $laFila[1], 'TFINALI' => $laFila[2], 'CIDREQU' => $laFila[3],
                             'CDESREQ' => $laFila[4], 'CCENCOS' => $laFila[5], 'CDESCCO' => $laFila[6]];
      }
      return true;
   }

   #SELECCIONAR COTIZACION DE USUARIO--- Erp1150.php
   public function omSeleccionarCotizacionUsuario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarCotizacionUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxSeleccionarCotizacionUsuario($p_oSql) {
      $lcIdCoti = $this->paData['CIDCOTI'];
      #TRAER TODOS LOS ARTICULOS DE LA COTIZACION DEL RUC  00000000000
      $lcSql = "SELECT A.cNroRuc, A.cCodArt, B.cDescri, A.nPreUni, A.nCantid FROM F_E01DCOT_1('$lcIdCoti') A
                   INNER JOIN E01MART B ON B.cCodArt = A.cCodArt ORDER BY A.nSerial, A.cCodArt";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNRORUC' => $laFila[0], 'CCODART' => $laFila[1], 'NCANTID' => number_format($laFila[4], 4), 'CDESART' => $laFila[2],
                             'NPREUNI' => $laFila[3]];
         $i++;
      }
      #TRAER TODOS LOS PROVEEDORES YA ASIGNADOS A LA COTIZACION
      $lcSql = "SELECT C.cNroRuc, C.cRazSoc, C.cSiglas, B.cCodigo FROM E01MCOT A
                     INNER JOIN E01PCOT B ON B.cIdCoti = A.cIdCoti
                     INNER JOIN S01MPRV C ON C.cNroRuc = B.cNroRuc
                  WHERE B.cEstado = 'E' AND C.cEstado = 'A' AND A.cIdCoti = '$lcIdCoti'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         #$this->paNroRuc[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CSIGLAS' => $laFila[2]];
         $this->paNroRuc[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => 'PROVEEDOR '.$laFila[3], 'CSIGLAS' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY PROVEEDORES QUE ENVIARAN SU COTIZACION";
         return false;
      }
      $this->paNroRuc[] = ['CNRORUC' => '00000000000', 'CRAZSOC' => 'PROVEEDOR SIN DEFINIR', 'CSIGLAS' => 'PROVEEDOR SIN DEFINIR'];
      return true;
   }

   #GRABA LOS PROVEEDORES GANADORES DEL USUARIO - GRABAR # ALBERTO # Erp1150.php
   public function omGrabarCotizacionUsuario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarCotizacionUsuario($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarCotizacionUsuario($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E01DCOT_3('$lcJson')";
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

   #GENERACION DE OLS CARGAR ORDENES DE COMPRA FRL 2018-02-21
   public function omInitGenerarComprobantesPago() {
      $llOk = $this->mxValInitGenerarComprobantesPago();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGenerarComprobantesPago($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitGenerarComprobantesPago() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen(trim($this->paData['CNIVEL'])) != 2) {
         $this->pcError = "NIVEL DE USUARIO INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxInitGenerarComprobantesPago($p_oSql) {
      $lcNivel = $this->paData['CNIVEL'];
      #TRAE TODOS LOS TIPOS DE LAS ORDENES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('R','A','E','M') ";
      if ($lcNivel == 'AL') {
         $lcSql .= "AND cCodigo NOT IN ('S')";
      #} elseif ($lcNivel == 'CO') {
      #   $lcSql .= "AND cCodigo NOT IN ('B')";
      }
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE ORDENES DEFINIDOS [S01TTAB.075]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE TODOS LOS ESTADOS DE LAS ORDENES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '080' AND cCodigo IN ('B','F')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY ESTADOS DE ORDENES DEFINIDOS [S01TTAB.080]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE PERIODOS DE ORDENES
      $lcSql = "SELECT DISTINCT cPeriod FROM E01MORD WHERE cIdOrde != '00000000' ORDER BY cPeriod DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY PERIODOS DEFINIDOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paPeriod[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[0]];
      }
      #TRAE COTIZADORES
      $lcSql = "SELECT cCodUsu, cNroDni, cNombre, cNivel FROM V_S01TUSU_1 WHERE cEstado = 'A' AND cNivel IN ('CO','JL')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY COTIZADORES DEFINIDOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUsuCot[] = ['CCODUSU' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2], 'CNIVEL' => $laFila[3]];
      }
      #TRAE MONEDAS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '007'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE MONEDAS DEFINIDOS [S01TTAB.007]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE TIPOS DE COMPROBANTES DE PAGO
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '087'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY TIPOS DE COMPROBANTES DEFINIDOS [S01TTAB.087]';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipCom[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE CUENTAS CONTABLES
      $lcSql = "SELECT cCtaCnt, cDescri FROM D01MCTA ORDER BY cCtaCnt";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY CUENTAS CONTABLES DEFINIDAS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCtaCnt[] = ['CCTACNT' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   #BANDEJA DE COMPROBANTES DE PAGO
   public function omCargarOrdenesComprobantes() {
      $llOk = $this->mxValCargarOrdenesComprobantes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarOrdenesComprobantes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarOrdenesComprobantes() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen(trim($this->paData['CNIVEL'])) != 2) {
         $this->pcError = "NIVEL DE USUARIO INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarOrdenesComprobantes($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      #TRAE TODOS LOS TIPOS DE LAS ORDENES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('R','A','E','M')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE ORDENES DEFINIDOS [S01TTAB.075]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE TODOS LOS ESTADOS DE LAS ORDENES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '080' AND cCodigo IN ('B','C')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY ESTADOS DE ORDENES DEFINIDOS [S01TTAB.080]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE PERIODOS DE ORDENES
      $lcSql = "SELECT DISTINCT cPeriod FROM E01MORD WHERE cIdOrde != '00000000' ORDER BY cPeriod DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY PERIODOS DEFINIDOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paPeriod[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[0]];
      }
      #TRAE COTIZADORES
      $lcSql = "SELECT cCodUsu, cNroDni, cNombre, cNivel FROM V_S01TUSU_1 WHERE cEstado = 'A' AND cNivel IN ('CO','JL')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY COTIZADORES DEFINIDOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUsuCot[] = ['CCODUSU' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2], 'CNIVEL' => $laFila[3]];
      }
      #TRAE MONEDAS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '007'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE MONEDAS DEFINIDOS [S01TTAB.007]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE TIPOS DE COMPROBANTES DE PAGO
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '087'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE COMPROBANTES DEFINIDOS [S01TTAB.087]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipCom[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE CUENTAS CONTABLES
      $lcSql = "SELECT cCtaCnt, cDescri FROM D01MCTA ORDER BY cCtaCnt";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY CUENTAS CONTABLES DEFINIDAS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCtaCnt[] = ['CCTACNT' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   public function omBuscarOCS() {
      $llOk = $this->mxValBuscarOCS();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarOCS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarOCS() {
      if (!isset($this->paData['CBUSORD']) || strlen(trim($this->paData['CBUSORD'])) > 50) {
         $this->pcError = "CLAVE DE BÚSQUEDA INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CTIPO']) || strlen(trim($this->paData['CTIPO'])) != 1) {
         $this->pcError = "TIPO DE ORDEN INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO']) || strlen(trim($this->paData['CESTADO'])) != 1) {
         $this->pcError = "ESTADO DE ORDEN INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 4) {
         $this->pcError = "PERIODO DE ORDEN INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen(trim($this->paData['CNIVEL'])) != 2) {
         $this->pcError = "NIVEL DE USUARIO INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarOCS($p_oSql) {
      $lcBusOrd = $this->paData['CBUSORD'];
      $lcBusOrd = str_replace(' ', '%', trim($lcBusOrd));
      $lcBusOrd = str_replace('#', 'Ñ', trim($lcBusOrd));
      $lcTipo   = $this->paData['CTIPO'];
      $lcEstado = $this->paData['CESTADO'];
      $lcPeriod = $this->paData['CPERIOD'];
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcNivel  = $this->paData['CNIVEL'];
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcSql = "SELECT A.cIdOrde, A.cNroRuc, A.cEstado, A.dGenera, A.cTipo, A.cIncIgv, A.nMonto, A.cMoneda, A.mObsOrd, A.cUsuMod, 
                       A.tModifi, A.cDesEst, A.cDesTip, A.cDesMon, A.cRazSoc, A.cCenCos, A.cDesCCo, A.cCodAnt, A.cCtaCnt, B.cCenAfe,
                       C.cDescri AS cDesAfe, A.cDesReq, A.cCCoAnt, C.cCodAnt AS cAfeAnt, COUNT(D.cNotIng)
                     FROM V_E01MORD_3 A
                     INNER JOIN P02MACT B ON B.cIdActi = A.cIdActi
                     INNER JOIN S01TCCO C ON C.cCenCos = B.cCenAfe
                     LEFT OUTER JOIN E01MNIN D ON D.cIdOrde = A.cIdOrde AND A.cEstado != 'X'
                WHERE A.cTipo = '$lcTipo' AND A.cPeriod = '$lcPeriod' ";
      if ($lcNivel == 'CO') {
         $lcSql .= "AND A.cUsuCot = '$lcCodUsu' ";
      } elseif ($lcUsuCot != 'TTTT') {
         $lcSql .= "AND A.cUsuCot = '$lcUsuCot' ";
      }
      if ($lcEstado != 'T') {
         $lcSql .= "AND A.cEstado = '$lcEstado' ";
      } else {
         $lcSql .= "AND A.cEstado IN ('F','B','C') ";
      }
      if ($lcBusOrd != '') {
         $lcSql .= " AND (A.cCodAnt LIKE '%$lcBusOrd%' OR A.cCodAnt = '$lcBusOrd' OR A.cNroRuc = '$lcBusOrd' OR A.cRazSoc LIKE '%$lcBusOrd%')";
      }
      $lcSql .= "GROUP BY A.cIdOrde, A.cNroRuc, A.cEstado, A.dGenera, A.cTipo, A.cIncIgv, A.nMonto, A.cMoneda, A.mObsOrd,
                          A.cUsuMod, A.tModifi, A.cDesEst, A.cDesTip, A.cDesMon, A.cRazSoc, A.cCenCos, A.cDesCCo, A.cCodAnt,
                          A.cCtaCnt, B.cCenAfe, C.cDescri, A.cDesReq, A.cCCoAnt, C.cCodAnt
                 ORDER BY A.cCodAnt DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY ORDENES CON LAS FILTRO SELECCIONADOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paOrdene[] = ['CIDORDE' => $laFila[0], 'CNRORUC' => $laFila[1], 'CESTADO' => $laFila[2], 'DGENERA' => $laFila[3],
                              'CTIPO'   => $laFila[4], 'CINCIGV' => $laFila[5], 'NMONTO'  => $laFila[6], 'CMONEDA' => $laFila[7],
                              'MOBSERV' => $laFila[8], 'CUSUCOD' => $laFila[9], 'TMODIFI' => $laFila[10],'CDESEST' => $laFila[11],
                              'CDESTIP' => $laFila[12],'CDESMON' => $laFila[13],'CRAZSOC' => $laFila[14],
                              'CCENCOS' => ($laFila[19] != $laFila[15] && $laFila[19] != '000')? $laFila[19] : $laFila[15],
                              'CDESCEN' => ($laFila[19] != $laFila[15] && $laFila[19] != '000')? $laFila[20] : $laFila[16],
                              'CCCOANT' => ($laFila[19] != $laFila[15] && $laFila[19] != '000')? $laFila[23] : $laFila[22],
                              'CCODANT' => $laFila[17],'CCTACNT' => $laFila[18],'CDESREQ' => $laFila[21],'NCANNIN' => $laFila[24]];
      }
      return true;
   }

   public function omCargarDetalleOrdenComprobante() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarDetalleOrdenComprobante($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarDetalleOrdenComprobante($p_oSql) {
      #TRAE ORDENES DE COMPRA
      $lcIdOrde = $this->paData['CIDORDE'];
      $lcTipo   = $this->paData['CTIPO'];
      $lcSql = "SELECT COUNT(*) FROM E01DORD WHERE cIdOrde = '$lcIdOrde'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0][0] == 0) {
         $this->pcError = 'ORDEN NO TIENE DETALLE DEFINIDO';
         return false;
      }
      $lcSql = "SELECT A.nSerial, A.cIdOrde, A.cCodArt, A.cEstado, A.cDescri, (A.nCantid - A.nCanIng)::NUMERIC(14,4), A.nCanIng, A.nCosto, A.cUsuCod, 
                       B.cDescri AS cDesArt, (A.nCantid - A.nCanIng) * A.nCosto AS nMonto, B.cUnidad, C.cDescri AS cDesUni 
                FROM E01DORD A
                INNER JOIN E01MART B ON B.cCodArt = A.cCodArt 
                INNER JOIN V_S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                WHERE A.cIdOrde ='$lcIdOrde' ";
      if (in_array($lcTipo, ['B','D'])) {
         #$lcSql = $lcSql." AND A.cCodArt NOT IN (SELECT DISTINCT A.cCodArt FROM E01DFAC A INNER JOIN E01MFAC B ON B.cIdComp = A.cIdComp WHERE B.cIdOrde = '$lcIdOrde' AND B.cEstado != 'X')";
         $lcSql = $lcSql. "AND A.nCanIng < A.nCantid ";
      }
      $lcSql = $lcSql."ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERORD' => $laFila[0], 'CIDORDE' => $laFila[1], 'CCODART' => $laFila[2], 'CESTADO' => $laFila[3],
                             'CDESCRI' => $laFila[4], 'NCANTID' => $laFila[5], 'NCANING' => $laFila[6], 'NCOSTO'  => $laFila[7],
                             'CUSUCOD' => $laFila[8], 'CDESART' => $laFila[9], 'NMONTO'  => $laFila[10],'CUNIDAD' => $laFila[11],
                             'CDESUNI' => $laFila[12],'NSERIAL' => -1];
      }
      return true;
   }

   #GRABA COMPROBANTES DE PAGO CON SU DETALLE FRL ERP2190.PHP 22-02-18
   public function omGrabarComprobantePago() {
      $llOk = $this->mxValParamGrabarComprobantePago();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarComprobantePago($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarComprobantePago() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen(trim($this->paData['CIDCOMP'])) != 8) {
         $this->pcError = 'COMPROBANTE INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDORDE']) || strlen(trim($this->paData['CIDORDE'])) != 8) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CTIPCOM']) || strlen(trim($this->paData['CTIPCOM'])) != 2) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CNROCOM']) || strlen(trim($this->paData['CNROCOM'])) > 20 || empty($this->paData['CNROCOM'])) {
         $this->pcError = 'NRO DE COMPROBANTE INVALIDO';
         return false;
      } elseif (!isset($this->paData['CMONEDA']) || strlen(trim($this->paData['CMONEDA'])) != 1) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['DFECEMI']) || strlen(trim($this->paData['DFECEMI'])) != 10 || !$loDate->valDate($this->paData['DFECEMI'])) {
         $this->pcError = 'FECHA DE EMISION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || strlen(trim($this->paData['CDESCRI'])) > 200 || empty($this->paData['CDESCRI'])) {
         $this->pcError = 'DESCRIPCION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CCTACNT']) || strlen(trim($this->paData['CCTACNT'])) > 12 || empty($this->paData['CCTACNT'])) {
         $this->pcError = 'CUENTA CONTABLE INVALIDA';
         return false;
      } elseif (!isset($this->paData['NADICIO']) || strlen(trim($this->paData['NADICIO'])) == 0) {
         $this->pcError = 'MONTO ADICIONAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONIGV']) || strlen(trim($this->paData['NMONIGV'])) == 0 || $this->paData['NMONIGV'] < 0) {
         $this->pcError = 'MONTO IGV INVALIDO';
         return false;
      } elseif (!isset($this->paData['NINAFEC']) || strlen(trim($this->paData['NINAFEC'])) == 0) {
         $this->pcError = 'MONTO INFECTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONTO']) || strlen(trim($this->paData['NMONTO'])) == 0 || $this->paData['NMONTO'] < 0) {
         $this->pcError = 'MONTO TOTAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || count($this->paData['MDATOS']) == 0) {
         $this->pcError = 'DETALLE DE COMPROBANTE INVALIDO';
         return false;
      }
      if (isset($this->paData['MANOTAC'])) $this->paData['MANOTAC'] = mb_strtoupper($this->paData['MANOTAC']);
      $this->paData['CNROCOM'] = mb_strtoupper($this->paData['CNROCOM']);
      $this->paData['CDESCRI'] = mb_strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarComprobantePago($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MFAC_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $this->paData['CIDCOMP'] = $this->paDatos[0]['CIDCOMP'];
      return true;
   }

   # ANULAR COMPROBANTES DE PAGO - Erp2210
   # 2019-10-24 JLF Creación
   public function omAnularComprobantePago() {
      $llOk = $this->mxValAnularComprobantePago();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxAnularComprobantePago($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValAnularComprobantePago() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen(trim($this->paData['CIDCOMP'])) != 8) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['MANOTAC']) || strlen(trim($this->paData['MANOTAC'])) == 0) {
         $this->pcError = 'MOTIVO DE ANULACIÓN INVALIDO';
         return false;
      }
      $this->paData['MANOTAC'] = mb_strtoupper($this->paData['MANOTAC']);
      return true;
   }

   protected function mxAnularComprobantePago($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MFAC_2('$lcJson')";
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

   #INIT - 2920.PHP # BEHAVIOR 0 DE ERP2920 # BETO
   public function omInitRepEntregaDirecta() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRepEntregaDirecta($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitRepEntregaDirecta($p_oSql) {
      #TRAER CENTROS DE COSTO
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY CENTROS DE COSTO REGISTRADOS";
         return false;
      }
      return true;
   }

   #INIT - 2930.PHP # BEHAVIOR 0 DE ERP2920 # BETO
   public function omInitRepLogistica() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRepLogistica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitRepLogistica($p_oSql) {
      #TRAER TIPO DE REQUERIMIENTOS
      $lcSql = "SELECT cCodigo, cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('A','R','E')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR LOS TIPOS DE REQUERIMIENTO CCODTAB = [075]";
         return false;
      }
      return true;
   }

   #CARGAR COMPROBANTES DE PAGO CABECERA  ERP2110.PHP  FRL 2018-02-22
   public function omCargarComprobantes() {
      $llOk = $this->mxValCargarComprobantes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarComprobantes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarComprobantes() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CIDORDE']) || strlen(trim($this->paData['CIDORDE'])) != 8) {
         $this->pcError = "ORDEN INVÁLIDA";
         return false;
      }
      return true;
   }

   protected function mxCargarComprobantes($p_oSql) {
      #TRAE FACTURAS DE UNA ORDEN DE COMPRA
      $lcIdOrde = $this->paData['CIDORDE'];
      $lcSql = "SELECT A.cIdComp, A.cTipCom, TRIM(A.cNroCom), A.cIdorde, A.cNroRuc, A.cEstado, A.nMonto, A.nMonigv, A.nInaFec, A.nAdicio, 
                       A.cUsuCod, B.cRazSoc, C.cDescri AS cDesEst, D.cDescri AS cDesTip, A.cDescri, G.cDescri AS cDesMon, A.dFecEmi, 
                       A.cCenCos, H.cDescri AS cDesCen, F.cCodAnt, A.cMoneda, F.cTipo, A.cCtaCnt, I.cNombre AS cNomMod, A.cEstPro, 
                       TO_CHAR(A.tModifi, 'YYYY-MM-DD'), J.cDescri AS cDesPro, H.cCodAnt AS cCCoAnt, F.mObserv AS mObsOrd, A.mObserv,
                       A.cPagAde, A.dEnvVic, A.dVencim
                FROM E01MFAC A
                INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '126' AND C.cCodigo = A.cEstado
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '087' AND D.cCodigo = A.cTipCom
                INNER JOIN E01MORD F ON F.cIdorde =  A.cIdorde
                LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '007' AND G.cCodigo = F.cMoneda
                INNER JOIN S01TCCO H ON H.cCenCos = A.cCenCos 
                INNER JOIN V_S01TUSU_1 I ON I.cCodUsu = A.cUsuCod
                LEFT OUTER JOIN V_S01TTAB J ON J.cCodTab = '040' AND J.cCodigo = A.cEstPro
                WHERE A.cIdorde ='$lcIdOrde' ORDER BY A.dFecEmi, A.cIdComp";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'ORDEN DE COMPRA NO TIENE COMPROBANTES INGRESADOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCompro[] = ['CIDCOMP' => $laFila[0], 'CTIPCOM' => $laFila[1], 'CNROCOM' => $laFila[2], 'CIDORDE' => $laFila[3],
                              'CNRORUC' => $laFila[4], 'CESTADO' => $laFila[5], 'NMONTO'  => $laFila[6], 'NMONIGV' => $laFila[7],
                              'NINAFEC' => $laFila[8], 'NADICIO' => $laFila[9], 'CUSUMOD' => $laFila[10],'CRAZSOC' => $laFila[11],
                              'CDESEST' => $laFila[12],'CDESTIP' => $laFila[13],'CDESCRI' => $laFila[14],'CDESMON' => $laFila[15],
                              'DFECEMI' => $laFila[16],'CCENCOS' => $laFila[17],'CDESCEN' => $laFila[18],'CCODANT' => $laFila[19],
                              'CMONEDA' => $laFila[20],'CTIPO'   => $laFila[21],'CCTACNT' => $laFila[22],'CNOMMOD' => $laFila[23],
                              'CESTPRO' => $laFila[24],'TMODIFI' => $laFila[25],'CDESPRO' => $laFila[26],'CCCOANT' => $laFila[27],
                              'MOBSORD' => $laFila[28],'MOBSERV' => $laFila[29],'CPAGADE' => $laFila[30],'DENVVIC' => $laFila[31],
                              'DVENCIM' => $laFila[32]];
      }
      return true;
   }

   #CARGAR DETALLE DEL COMPROBANTE DE PAGO
   public function omCargarDetalleComprobante() {
      $llOk = $this->mxValCargarDetalleComprobante();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarDetalleComprobante($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarDetalleComprobante() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen(trim($this->paData['CIDCOMP'])) != 8) {
         $this->pcError = "COMPROBANTE INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarDetalleComprobante($p_oSql) {
      #TRAE ORDENES DE COMPRA
      $lcIdComp = $this->paData['CIDCOMP'];
      $this->paDatos = null;
      $lcSql = "SELECT A.nSerial, A.cIdComp, A.cCodArt, A.cEstado, A.nCantid, A.nMonto, A.nMonigv, A.cUsuCod, B.cDescri AS cDesArt,
                       C.cDescri AS cDesUni FROM E01DFAC A
                INNER JOIN E01MART B ON B.cCodArt = A.cCodArt 
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                WHERE A.cIdComp ='$lcIdComp' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERRO DE EJECUCION DE BASE DE DATOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CIDCOMP' => $laFila[1], 'CCODART' => $laFila[2], 'CESTADO' => $laFila[3],
                             'NCANTID' => $laFila[4], 'NMONTO'  => $laFila[5], 'NMONIGV' => $laFila[6], 'CUSUCOD' => $laFila[7],
                             'CDESART' => $laFila[8], 'CDESUNI' => $laFila[9]];
      }
      return true;
   }

   #CARGAR SUMA DE DETALLE DE COMPROBANTES ERP2180.PHP FRL 01-03-18
   public function omCargarSumDetalleComprobante() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarSumDetalleComprobante($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarSumDetalleComprobante($p_oSql) {
      #TRAE ORDENES DE COMPRA
      $lcIdOrde = $this->paData['CIDORDE'];
      $this->paDatos = null;
      $lcSql = "SELECT t_cCodArt, t_cDescri, t_cUnidad, t_nCantid , t_nMonto, t_cDesUni FROM F_E01DNIN_1('$lcIdOrde')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'CUNIDAD' => $laFila[2], 'NCANTID' => $laFila[3],
                             'NMONTO'  => $laFila[4], 'CDESUNI' => $laFila[5]];
      }
      return true;
   }

   #GRABA COMPROBANTES DE PAGO CON SU DETALLE FRL ERP2190.PHP 22-02-18
   public function omGrabarNotadeIngreso() {
      $llOk = $this->mxValParamGrabarNotadeIngreso();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarNotadeIngreso($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarNotadeIngreso() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen(trim($this->paData['CIDCOMP'])) != 8) {
         $this->pcError = "COMPROBANTE INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['DFECHA']) || strlen(trim($this->paData['DFECHA'])) != 10 || !$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = 'FECHA DE EMISIÓN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CGUIREM']) || strlen(trim($this->paData['CGUIREM'])) > 20 || empty($this->paData['CGUIREM'])) {
         $this->pcError = 'GUIA DE REMISION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || strlen(trim($this->paData['CDESCRI'])) > 100 || empty($this->paData['CDESCRI'])) {
         $this->pcError = 'DESCRIPCION INVALIDA';
         return false;
      } elseif (!isset($this->paData['MOBSERV']) || empty($this->paData['MOBSERV'])) {
         $this->pcError = 'OBSERVACION INVALIDA';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || count($this->paData['MDATOS']) == 0) {
         $this->pcError = 'NOTA DE INGRESO NO TIENE DETALLE';
         return false;
      }
      return true;
   }

   protected function mxGrabarNotadeIngreso($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MNIN_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $laFila = json_decode($laFila[0], true);
      $this->paData['CNOTING'] = $laFila[0]['CNOTING'];
      return true;
   }

   # BANDEJA DE NOTAS DE INGRESO - Erp2410
   # 2019-03-06 JLF Creación
   public function omCargarBandejaNotasIngreso() {
      $llOk = $this->mxValCargarBandejaNotasIngreso();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarBandejaNotasIngreso($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarBandejaNotasIngreso() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxCargarBandejaNotasIngreso($p_oSql) {
      #TRAE PERIODOS DE INGRESO
      $lcSql = "SELECT DISTINCT TO_CHAR(DFECHA, 'YYYYMM') AS cPeriod FROM E03MKAR WHERE cTipMov = 'NI' AND DFECHA != '1900-01-01' ORDER BY cPeriod DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY PERIODOS DEFINIDOS';
         return false;
      }
      $llFlag = false;
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($llFlag && $lcAnio != substr($laFila[0], 0, 4)) $this->paPeriod[] = ['CCODIGO' => $lcAnio.'00', 'CDESCRI'  => 'PERIODO '.$lcAnio.'00'];
         $this->paPeriod[] = ['CCODIGO' => $laFila[0], 'CDESCRI'  => 'PERIODO '.$laFila[0]];
         $lcAnio = substr($laFila[0], 0, 4);
         $llFlag = true;
      }
      if ($llFlag) $this->paPeriod[] = ['CCODIGO' => $lcAnio.'00', 'CDESCRI'  => 'PERIODO '.$lcAnio.'00'];
      return true;
   }

   # BANDEJA DE NOTAS DE INGRESO (IB) BOTICA ALIVIARI - Erp2530
   # 2021-08-12 WZA Creación
   public function omCargarBandejaNotasIngresoBoticaAli() {
      $llOk = $this->mxValCargarBandejaNotasIngresoBoticaAli();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarBandejaNotasIngresoBoticaAli($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarBandejaNotasIngresoBoticaAli() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxCargarBandejaNotasIngresoBoticaAli($p_oSql) {
      #TRAE PERIODOS DE INGRESO
      $lcSql = "SELECT DISTINCT TO_CHAR(DFECHA, 'YYYYMM') AS cPeriod FROM E03MKAR WHERE cTipMov = 'IB' AND DFECHA != '1900-01-01' ORDER BY cPeriod DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY PERIODOS DEFINIDOS';
         return false;
      }
      $llFlag = false;
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($llFlag && $lcAnio != substr($laFila[0], 0, 4)) $this->paPeriod[] = ['CCODIGO' => $lcAnio.'00', 'CDESCRI'  => 'PERIODO '.$lcAnio.'00'];
         $this->paPeriod[] = ['CCODIGO' => $laFila[0], 'CDESCRI'  => 'PERIODO '.$laFila[0]];
         $lcAnio = substr($laFila[0], 0, 4);
         $llFlag = true;
      }
      if ($llFlag) $this->paPeriod[] = ['CCODIGO' => $lcAnio.'00', 'CDESCRI'  => 'PERIODO '.$lcAnio.'00'];
      return true;
   }

   # REGRESA COMPROBANTES DE CONTABILIDAD - Erp2390
   # 2019-03-04 JLF Creación
   public function omCargarNotasIngreso() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarNotasIngreso($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarNotasIngreso($p_oSql) {
      #TRAE NOTAS DE INGRESO
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcSql = "SELECT A.cNotIng, A.dFecha, A.cGuiRem, A.cDescri, A.mObserv, A.cEstado, G.cDescri AS cDesEst, A.cIdKard, B.cTipMov, 
                       B.cNumMov, B.cNroRuc, E.cRazSoc, B.cCenCos, F.cDescri AS cDesCCo, C.cIdOrde, C.cCodAnt, A.cIdComp, D.cNroCom 
                FROM E01MNIN A
                INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                INNER JOIN E01MORD C ON C.cIdOrde = A.cIdOrde
                INNER JOIN E01MFAC D ON D.cIdComp = A.cIdComp
                INNER JOIN S01MPRV E ON E.cNroRuc = B.cNroRuc
                INNER JOIN S01TCCO F ON F.cCenCos = B.cCenCos
                LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '089' AND G.cCodigo = A.cEstado
                ORDER BY A.dFecha DESC, B.cNumMov DESC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOTING' => $laFila[0], 'DFECHA'  => $laFila[1], 'CGUIREM' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CDESEST' => $laFila[6], 'CIDKARD' => $laFila[7],
                             'CTIPMOV' => $laFila[8], 'CNUMMOV' => $laFila[9], 'CNRORUC' => $laFila[10],'CRAZSOC' => $laFila[11],
                             'CCENCOS' => $laFila[12],'CDESCCO' => $laFila[13],'CIDORDE' => $laFila[14],'CCODANT' => $laFila[15],
                             'CIDCOMP' => $laFila[16],'CNROCOM' => $laFila[17]];
      }
      return true;
   }

   #ASIGNACION DE STOCK EN ALMACEN A REQUERIMIENTO ERP21X0 BEHAVIOR 0
   public function omInitReqEntregados() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReqEntregados($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxReqEntregados($p_oSql) {
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.CIDREQU, A.CDESCCO, A.TGENERA, A.CDESREQ, A.CNOMCOT, A.MOBSERV, A.CESTADO, B.CDESCRI FROM V_E01MREQ_1 A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado WHERE A.CESTADO = 'T' AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC ;";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1],
             'TGENERA' => $laFila[2], 'CDESREQ' => $laFila[3],
             'CNOMCOT' => $laFila[4], 'MOBSERV' => $laFila[5],
             'CESTADO' => $laFila[7]];
      }
      #print_r($this->paDatos); die;

      return true;
   }

   #BANDEJA 2270
   public function omInitBandejaCompraDirecta() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBandejaCompraDirecta($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBandejaCompraDirecta($p_oSql) {
      #TRAE COMPRAS DIRECTAS CREADAS POR EL USUARIO COTIZADOR
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCco, A.cDesReq, A.cDesTip, A.cDesmon, A.cCenCos, B.cDesCor, A.cTipo AS cMonSim, A.cNroDoc, 
                       A.cNomUsu, SUBSTRING(A.tGenera, 1, 10) AS dGenera FROM V_E01MREQ_1 A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '007' AND B.cCodigo = A.cMoneda
                WHERE A.cComDir= 'S' AND A.cEstado = 'A' AND cUsuCot = '$lcCodUsu'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'CDESREQ' => $laFila[2], 'CDESTIP' => $laFila[3],
                             'CDESMON' => $laFila[4], 'CCENCOS' => $laFila[5], 'CMONSIM' => $laFila[6], 'CTIPO'   => $laFila[7],
                             'CNRODOC' => $laFila[8], 'CNOMUSU' => $laFila[9], 'DGENERA' => $laFila[10]];
      }
      #TRAER FORMAS DE PAGO
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '097'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 1;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paForPag[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE PUDIERON RECUPERAR LAS FORMAS DE PAGO S01TTAB[097]';
         return false;
      }
      return true;
   }

   #BANDEJA 2270 DETALLE
   public function omDetalleCompraDirecta() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleCompraDirecta($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxDetalleCompraDirecta($p_oSql) {
      #TRAE
      $lcIdRequ = $this->paData['CIDREQU'];
      $lcSql = "SELECT cCodArt, cDesArt, cDesUni, cClasif, nCantid, nCanAte, nPreArt, cDescri, cEstado, (nCantid * nPreArt) AS nSubTot, 
                       nSerial FROM V_E01DREQ_1 WHERE cIdRequ = '$lcIdRequ' AND cEstado = 'A' ORDER BY nSerial";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'CDESUNI' => $laFila[2], 'CCLASIF' => $laFila[3],
                             'NCANTID' => number_format($laFila[4],4,'.',''),  'NCANATE' => $laFila[5], 'NPREART' => number_format($laFila[6],6,'.',''),
                             'CDESCRI' => $laFila[7], 'CESTADO' => $laFila[8], 'NSUBTOT' => number_format($laFila[9],4,'.',''), 'NSERIAL' => $laFila[10]];
      }
      $lcSql = "SELECT A.cNroRuc, B.cRazSoc FROM E01DCOM A INNER JOIN S01MPRV B ON A.cNroRuc = B.cNroRuc WHERE A.cIdRequ = '$lcIdRequ'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paNroRuc = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1]];
      }
      return true;
   }

   #GENERAR COMPRA DIRECTA
   public function omGenerarOrdenCompraDirecta() {
      $llOk = $this->mxValGenerarOrdenCompraDirecta();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGenerarOrdenCompraDirecta($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGenerarOrdenCompraDirecta() {
      if (!isset($this->paData['CCODUSU'])) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxGenerarOrdenCompraDirecta($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MORD_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData[0]['ERROR'])) {
         $this->pcError = $this->paData[0]['ERROR'];
         return false;
      }
      return true;
   }

   #CARGAR CABEZERA DE REQUERIMIENTO # EDITAR REQ POR ALAMACEN FRL 21-03-18
   public function omCargarRequerimiento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRequerimiento($p_oSql) {
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcCodUsu = $this->paData['CCODUSU'];
      if ($lcCodUsu === '2444') {
         $lcSql = " SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, 
                           A.cTipo, A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin, A.cIdActi, A.cNroDoc, A.tCotiza 
                    FROM V_E01MREQ_1 A
                    LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                    INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                    WHERE A.CESTADO = 'A' AND A.cUsuCot = '9999' ORDER BY A.tGenera ASC;";
      } else {
         $lcSql = " SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, 
                           A.cTipo, A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin, A.cIdActi, A.cNroDoc, A.tCotiza 
                    FROM V_E01MREQ_1 A
                    LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                    INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                    WHERE A.CESTADO = 'A' AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC;";
      }
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESREQ' => $laFila[3],
                             'CNOMCOT' => $laFila[4], 'MOBSERV' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
                             'CCODUSU' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO'   => $laFila[10],'CDESTIP' => $laFila[11],
                             'CMONEDA' => $laFila[12],'CDESMON' => $laFila[13],'CCENCOS' => $laFila[14],'CCOMDIR' => $laFila[15],
                             'CDESTIN' => $laFila[16],'CIDACTI' => $laFila[17],'CNRODOC' => $laFila[18],'TCOTIZA' => $laFila[19]];
      }
      return true;
   }

   #RECHAZAR REQUERIMIENTO JLF 22-03-18
   public function omRechazarRequerimiento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRechazarRequerimiento($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxRechazarRequerimiento($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E01MREQ_7('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $this->paData = json_decode($laFila[0], true);
      if (!empty($this->paData[0]['ERROR'])) {
         $this->pcError = $this->paData[0]['ERROR'];
         return false;
      }
      return true;
   }

   #CARGAR CABEZERA DE REQUERIMIENTO # GENERAR NOTAS DE SALIDA  FRL 27-03-18
   public function omCargarRequerimientoNotaSalida() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRequerimientoNotaSalida($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRequerimientoNotaSalida($p_oSql) {
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, A.cTipo, A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin FROM V_E01MREQ_1 A
                   LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                WHERE A.CESTADO in ('D','B') AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC;";
      #echo $lcSql;
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1],
             'TGENERA' => $laFila[2], 'CDESREQ' => $laFila[3], 'CNOMCOT' => $laFila[4],
             'MOBSERV' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
             'CCODUSU' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO' => $laFila[10],
             'CDESTIP' => $laFila[11], 'CMONEDA' => $laFila[12], 'CDESMON' => $laFila[13],
             'CCENCOS' => $laFila[14], 'CCOMDIR' => $laFila[15], 'CDESTIN' => $laFila[16]];
      }
      return true;
   }

   # GENERAR NOTAS DE SALIDA  FRL 27-03-18
   public function omCargarDetalleRequerimiento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarDetalleRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarDetalleRequerimiento($p_oSql) {
      $lcIdRequ = $this->paData['CIDREQU'];
      #TRAER DETALLE DE REQUERIMIENTO
      $lcSql = "SELECT cCodArt, cDesArt, cDescri, nCantid, nPreArt, nCanAte, cUnidad, ROUND(nCantid * nPreArt, 2) AS nSTotal, cDesUni 
                FROM V_E01DREQ_1 WHERE cIdRequ = '$lcIdRequ' AND nCantid = nCanAte AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      }
      $lnTotal = 0.00;
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcCodArt = $laFila[0];
         $lcSql = "SELECT A.nCosto FROM E03DKAR A INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                   WHERE B.cAlmOri = '001' AND B.cTipMov = 'NS' AND A.cCodArt = '$lcCodArt' ORDER BY B.dFecha DESC, B.cNumMov DESC, A.nSerial DESC LIMIT 1";
         $RS2 = $p_oSql->omExec($lcSql);
         if (!$RS2) {
            $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
            return false;
         }
         $laFila2  = $p_oSql->fetch($RS2);
         $lnCosto  = ($laFila2[0] != null)? $laFila2[0] : 0.0;
         $lnSubTot = number_format($lnCosto * $laFila[3], 2);
         $this->paDatos[] = ['CCODART' => $lcCodArt,  'CDESART' => $laFila[1], 'CDESDET' => $laFila[2], 'NCANTID' => $laFila[3],
                             'NPREREF' => $laFila[4], 'NCANATE' => $laFila[5], 'CUNIDAD' => $laFila[6], 'CDESUNI' => $laFila[8],
                             'NSTOTAL' => $lnSubTot,  'NCOSTO'  => $lnCosto];
         $lnTotal = $lnTotal + $lnCosto;
         $i++;
      }
      $this->paData['NTOTAL'] = number_format($lnTotal, 2);
      return true;
   }

   #CARGAR NOTAS DE INGRESO PARA GENERAR NOTAS DE INGRESO 27-03-18 FRL
   public function omCargarNotasIngresoNS() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarNotasIngresoNS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarNotasIngresoNS($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cNotIng, A.cIdOrde, A.dFecha, A.mObserv, A.cEstado, B.cDescri AS cDesEst FROM E01MNIN A
      LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '089' AND B.cCodigo = A.cEstado AND A.cEstado = 'A' ORDER BY A.cNotIng DESC";
      #echo $lcSql;
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paNotIng[] = ['CNOTING' => $laFila[0], 'CIDORDE' => $laFila[1],
             'DFECHA' => $laFila[2], 'MOBSERV' => $laFila[3], 'CESTADO' => $laFila[4],
             'CDESEST' => $laFila[5]];
      }
      return true;
   }

   # cargar detalle notaas de salida  GENERAR NOTAS DE SALIDA  FRL 27-03-18
   public function omCargarDetalleNSalida() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarDetalleNSalida($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarDetalleNSalida($p_oSql) {
      $lcNotIng = $this->paData['CNOTING'];
      #TRAER DETALLE DE REQUERIMIENTO
      $lcSql = "SELECT A.nSerial, A.cNotIng, A.cCodArt, A.nCantid, A.nMonto, A.cEstado, B.cDescri, B.cUnidad, CASE WHEN A.nCantid != 0 THEN ROUND(A.nMonto / A.nCantid, 4) ELSE 0 END
                       AS nCosto, C.cDescri AS cDesUni FROM E01DNIN A
                INNER JOIN E01MART B ON  B.cCodArt = A.cCodArt 
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                WHERE A.cNotIng = '$lcNotIng'";
      $RS = $p_oSql->omExec($lcSql);
      $lnTotal = 0.00;
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CNOTING' => $laFila[1], 'CCODART' => $laFila[2], 'NCANTID' => $laFila[3],
                             'NCOSTO'  => $laFila[8], 'CESTADO' => $laFila[5], 'CDESART' => $laFila[6], 'CUNIDAD' => $laFila[7],
                             'NSTOTAL' => $laFila[4], 'CDESUNI' => $laFila[9]];
         $lnTotal = $lnTotal + $laFila[8];
         $i++;
      }
      $this->paData['NTOTAL'] = number_format($lnTotal, 2);
      return true;
   }

   #GRABA NOTAS DE SALIDA CON SU DETALLE FRL ERP2190.PHP 22-02-18
   public function omGrabarNotadeSalida() {
      $llOk = $this->mxValParamGrabarNotadeSalida();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarNotadeSalida($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarNotadeSalida() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CALMORI']) || strlen($this->paData['CALMORI']) != 3) {
         $this->pcError = 'CODIGO DE ALMACEN INVALIDO';
         return false;
      } elseif (!isset($this->paData['CENVALM']) || strlen($this->paData['CENVALM']) != 1 || !in_array($this->paData['CENVALM'], ['S','N'])) {
         $this->pcError = 'ESTADO DE ENVIO A ALMACEN INVALIDO';
         return false;
      } elseif ($this->paData['CENVALM'] == 'S') {
         return true;
      } elseif (!isset($this->paData['CCODEMP']) || strlen($this->paData['CCODEMP']) != 4) {
         $this->pcError = 'CODIGO DE EMPLEADO INVALIDO';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = 'FECHA DE EMISIÓN INVALIDA';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || empty($this->paData['MDATOS']) || $this->paData['MDATOS'] == null) {
         $this->pcError = 'DETALLE DE MOVIMIENTO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxGrabarNotadeSalida($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E03MKAR_1('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $laTmp = json_decode($laFila[0], true);
      $this->paData['CIDKARD'] = (!isset($laTmp[0]['CIDKARD']))? '000000000' : $laTmp[0]['CIDKARD'];
      return true;
   }

   # FRL
   public function omGrabarPedidoTransferencia() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarPedidoTransferencia($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarPedidoTransferencia($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_1('$lcJson')";
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

   # GENERAR NOTAS DE SALIDA -- FRL 28 DE MARZO 2018
   public function omInitGenerarNotasDeSalida() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGenerarNotasDeSalida($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitGenerarNotasDeSalida($p_oSql) {
      # Trae Requerimientos asigandos a almacen
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, A.cDesEst, A.cCodUsu, A.cNomUsu, 
                       A.cTipo, A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin FROM V_E01MREQ_1 A
                WHERE A.CESTADO in ('D','B') AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC;";
      #echo $lcSql;
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESCRI' => $laFila[3],
                              'CNOMCOT' => $laFila[4], 'MOBSERV' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
                              'CCODEMP' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO'   => $laFila[10],'CDESTIP' => $laFila[11],
                              'CMONEDA' => $laFila[12],'CDESMON' => $laFila[13],'CCENCOS' => $laFila[14],'CCOMDIR' => $laFila[15],
                              'CDESTIN' => $laFila[16]];
      }
      #Trae todos los estados de los requerimientos
      $this->paTipBus[] = ['CTIPBUS' => 'RQ', 'CDESCRI' => 'REQUERIMIENTO'];
      $this->paTipBus[] = ['CTIPBUS' => 'NI', 'CDESCRI' => 'NOTA DE INGRESO'];
      #TRAE ALMACEN DE USUARIO ORIGEN ENCARGADO
      $lcSql = "SELECT A.cCodAlm, B.cDescri FROM E03PUSU A
                INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm WHERE A.cCodUsu = '$lcCodUsu' ORDER BY B.cCodAlm LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0])) {
         $this->pcError = "USUARIO NO ESTÁ ENCARGADO DE ALMACÉN";
         return false;
      }
      $this->paData['CALMORI'] = $laFila[0];
      $this->paData['CDESALM'] = $laFila[1];
      return true;
   }

   # BUSCAR ORIGEN PARA GENERAR NOTA DE SALIDA  FRL
   public function omBuscarOrigenNotaSalida() {
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
      $llOk = $this->mxBuscarOrigenNotaSalida($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarOrigenNotaSalida($p_oSql) {
      $lcTipBus = $this->paData['CTIPBUS'];
      $lcCenCos = $this->paData['CCENCOS'];
      $lcCodUsu = $this->paData['CCODUSU'];
      # Trae todos los requerimientos de un usuario
      if ($lcTipBus == 'RQ') {
         $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, A.cDesEst, A.cCodUsu, A.cNomUsu, 
                          A.cTipo, A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin FROM V_E01MREQ_1 A
                   WHERE A.cEstado IN ('D','B') AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC;";
         $RS = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($RS)) {
            $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESCRI' => $laFila[3],
                                 'CNOMCOT' => $laFila[4], 'MOBSERV' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
                                 'CCODEMP' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO'   => $laFila[10],'CDESTIP' => $laFila[11],
                                 'CMONEDA' => $laFila[12],'CDESMON' => $laFila[13],'CCENCOS' => $laFila[14],'CCOMDIR' => $laFila[15],
                                 'CDESTIN' => $laFila[16]];
         }
         return true;
      } elseif ($lcTipBus == 'NI') {
         $lcSql = "SELECT A.cNotIng, A.cIdOrde, A.dFecha, A.cDescri, A.cEstado, B.cDescri AS cDesEst, D.cCenCos, E.cDescri AS cDesCco, 
                          C.cIdRequ, C.cCodAnt, A.mObserv, F.cIdKard, F.cTipMov, F.cNumMov, C.cNroRuc, C.cRazSoc FROM E01MNIN A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '089' AND B.cCodigo = A.cEstado
                  INNER JOIN V_E01MORD_3 C ON C.cIdOrde = A.cIdOrde
                  INNER JOIN E01MFAC D ON D.cIdOrde = C.cIdOrde
                  INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                  INNER JOIN E03MKAR F ON F.cIdKard = A.cIdKard
                  WHERE A.cEstado = 'G' ORDER BY F.cNumMov DESC";
         $RS = $p_oSql->omExec($lcSql);
         $lcNumMov = '';
         while ($laFila = $p_oSql->fetch($RS)) {
            if ($laFila[13] != $lcNumMov) {
               $this->paNotIng[] = ['CNOTING' => $laFila[0], 'CIDORDE' => $laFila[1], 'DFECHA'  => $laFila[2], 'CDESCRI' => $laFila[3],
                                    'CESTADO' => $laFila[4], 'CDESEST' => $laFila[5], 'CCENCOS' => $laFila[6], 'CDESCCO' => $laFila[7],
                                    'CIDREQU' => $laFila[8], 'CCODANT' => $laFila[9], 'MOBSERV' => $laFila[10],'CIDKARD' => $laFila[11],
                                    'CTIPMOV' => $laFila[12],'CNUMMOV' => $laFila[13],'CNRORUC' => $laFila[14],'CRAZSOC' => $laFila[15]];
               $lcNumMov = $laFila[13];
            }
         }
         return true;
      }
      return true;
   }

   # TIPOS DE NOTAS DE SALIDA FRL
   public function omCargarTipoSalida() {
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
      $llOk = $this->mxCargarTipoSalida($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarTipoSalida($p_oSql) {
      #CARGAR MOVIMIENTOS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '106' AND cCodigo IN ('NS','TX')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipNot[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE NOTAS DEFINIDAS [S01TTAB.106]";
         return false;
      }
      return true;
   }

   # TIPOS DE NOTAS DE SALIDA FRL
   public function omCargarTipoTransferencias() {
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
      $llOk = $this->mxCargarTipoTransferencias($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarTipoTransferencias($p_oSql) {
      #TRAER TIPO DE MOVIMIENTOS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '106' AND cCodigo NOT IN ('NE','NP','SC','IP','NI','IB')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipNot[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE NOTAS DEFINIDAS [S01TTAB.106]";
         return false;
      }
      return true;
   }

   # CARGAR ALMACENES  FRL 02-04-18
   public function omBuscarAlmacenes() {
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
      $llOk = $this->mxBuscarAlmacenes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAlmacenes($p_oSql) {
      $lcCodAlm = $this->paData['CCODALM'];
      $lcSql = "SELECT cCodAlm, cDescri FROM E03MALM WHERE  CCODALM = '$lcCodAlm' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION SQL";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paDatos = ['CCODALM' => $laFila[0], 'CDESCRI' => $laFila[1]];
      return true;
   }

   #AJAX BUSCAR PRODUCTO POR DESCRIPCION -- FRL 17-04-18
   public function omBuscarArticuloxDescripcionEditar() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarArticuloxDescripcionEditar($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   # AJAX EDITAR REQ
   protected function mxBuscarArticuloxDescripcionEditar($p_oSql) {
      $lcBusArt = $this->paData['CBUSART'];
      $lcAlmOri = $this->paData['CALMORI'];
      #TRAER ARTICULOS X DESCRIPCION
      $lcSql = "SELECT A.cCodArt, A.cDescri, A.cUnidad, A.nRefSol, A.nRefDol, B.cDescri AS cDesEst, C.nStock FROM E01MART A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '074' AND B.cCodigo = A.cUnidad
                LEFT OUTER JOIN E03PALM C ON C.cCodAlm = '001' AND C.cCodArt = A.cCodArt
                WHERE A.cEstado = 'A' AND (A.cDescri LIKE '%$lcBusArt%' OR A.cCodArt LIKE '$lcBusArt%') ORDER BY C.nStock, A.cDescri";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCodArt[] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'CUNIDAD' => $laFila[5],
             'NREFSOL' => $laFila[3], 'NREFDOL' => $laFila[4], 'NSTKACT' => ($laFila[6] == null) ? 0 : $laFila[6]];
         $i++;
      }
      if ($i == 0) {
         $this->paCodArt[] = ['CCODART' => '*', 'CDESART' => '*', 'CUNIDAD' => '*',
             'NREFSOL' => 0, 'NREFDOL' => 0, 'NSTKACT' => 0];
      }
      return true;
   }

   #AJAX BUSCAR USUARIO # FER
   #18-02-16 FRL CREACION
   public function omBuscarUsuarioxCodigo1() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarUsuarioxCodigoc1($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarUsuarioxCodigoc1($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT cCodUsu, cNombre FROM V_S01TUSU_1 WHERE (cNombre LIKE '%$lcCodUsu%' OR cNroDni = '$lcCodUsu' OR cCodUsu = '$lcCodUsu')  AND cEstado = 'A' ORDER BY cNombre";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION SQL";
         return false;
      }
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCodUsu [] = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1]];
      }
      return true;
   }

   # EDITAR REQUERIMIENTO COMO PEDIDO DE TRANSFERENCIA FRL 18-04-18  -- Alm1160

   public function omEditarReqTransferencia() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEditarReqTransferencia($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxEditarReqTransferencia($p_oSql) {
      $lcIdRequ = $this->paData['CIDREQU'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cCodArt, A.cDesArt, A.cDescri, A.nCantid, A.nPreArt, A.nCanAte, A.cUnidad, ROUND(A.nCantid * A.nPreArt, 2) AS nSTotal, A.cDesUni, B.nStock FROM V_E01DREQ_1 A
                LEFT JOIN E03PALM B ON B.cCodAlm = '001' AND B.cCodArt = A.cCodArt WHERE  A.cIdRequ = '$lcIdRequ' AND A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      $lnTotal = 0.00;
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODART' => $laFila[0], 'CDESART' => $laFila[1], 'CDESDET' => $laFila[2],
             'NCANTID' => $laFila[3], 'NPREREF' => $laFila[4], 'NCANATE' => $laFila[5],
             'CUNIDAD' => $laFila[8], 'NSTOTAL' => $laFila[7], 'CDESUNI' => $laFila[8], 'NSTOCK' => ($laFila[9] == null) ? 0 : $laFila[9]];
         $lnTotal = $lnTotal + $laFila[7];
         $i++;
      }
      $this->paData['NTOTAL'] = number_format($lnTotal, 2);
      return true;
   }

   public function omInitRevisionRUCsNuevos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRevisionRUCsNuevos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   # AJAX EDITAR REQ
   protected function mxInitRevisionRUCsNuevos($p_oSql) {
      $lcSql = "SELECT DISTINCT cRucNro FROM E02DCCH WHERE cRucNro NOT IN (
                   SELECT cNroRuc FROM S01MPRV WHERE cEstado = 'A') AND cRucNro != '00000000000'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNRORUC' => $laFila[0]];
         $i++;
      }
      return true;
   }

   public function omTraerCenCos() {
      $llOk = $this->mxValTraerCenCos();
      if (!$llOk) {
         return false;
      }
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

   protected function mxValTraerCenCos() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxTraerCenCos($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      /*
      $lcSql = "SELECT A.cCenCos, B.cDescri FROM S01PCCO A INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos WHERE A.cCodUsu = '$lcCodUsu'
                ORDER BY B.cDescri";
       */
      $lcSql = "SELECT cCenCos, cDesCen FROM V_S01PCCO WHERE cCodUsu = '$lcCodUsu' AND cModulo = '000' ORDER BY cDesCen";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE SQL";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "USUARIO NO TIENE CENTROS DE COSTO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   public function omTraerActividades() {
      $llOk = $this->mxValTraerActividades();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTraerActividades($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValTraerActividades() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxTraerActividades($p_oSql) {
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT cPeriod FROM S01TPER WHERE cTipo = 'S0'";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [PERIODOS]";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY UN PERIODO ACTIVO DEFINIDO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lcPeriod = $laFila[0];
      $lcSql = "SELECT A.cIdActi, B.cDescri FROM P02MACT A INNER JOIN V_S01TTAB B ON B.cCodTab = '052' AND B.cCodigo = A.cTipAct
                WHERE cCenCos IN ('$lcCenCos') AND cPeriod = '$lcPeriod' AND cEstado IN ('D') AND A.cTipAct IN ('I','E','C','U','L','M')
                ORDER BY A.cIdActi";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [ACTIVIDADES]";
         return false;
      }
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paActivi[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      $lcSql = "SELECT A.cIdActi, A.cDesCri || ' - ' || B.cDescri FROM P02MACT A INNER JOIN V_S01TTAB B ON B.cCodTab = '052' AND B.cCodigo = A.cTipAct
                WHERE A.cCenCos IN ('$lcCenCos') AND A.cPeriod = '$lcPeriod' AND A.cEstado IN ('D') AND A.cTipAct IN ('A','P','S','T','R','V','Q') ORDER BY A.cIdActi";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [ACTIVIDADES]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paActivi[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO TIENE ACTIVIDADES APROBADAS EN SU PLAN OPERATIVO";
         return false;
      }
      return true;
   }

   public function omGrabarObeservPresupuesto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarObeservPresupuesto($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarObeservPresupuesto($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E01DAUT_1('$lcJson')";
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

   public function omInitAsignarPresupuesto2() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAsignarPresupuesto2($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitAsignarPresupuesto2($p_oSql) {
      #TRAER TABLA DE CLASIFICADORES PRESUPUESTALES
      $lcSql = "SELECT TRIM(cClasif), cDescri FROM P01MCLA WHERE cNivel = '0'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paClasif[] = ['CCLASIF' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CLASIFICADORES PRESUPUESTALES";
         return false;
      }
      #TRAER TODOS LAS CABECERAS DE REQUERIMIENTOS
      $lcSql = "SELECT A.cIdRequ, B.cDesCCo, B.tGenera, B.cDesReq, B.cNomUsu, A.mObserv, A.cEstado, C.cDescri , B.cIdActi FROM E01DAUT A
                INNER JOIN V_E01MREQ_1 B ON B.cIdRequ = A.cIdRequ
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '077' AND C.cCodigo = A.cEstado
                WHERE A.cEstado IN ('P','O') AND A.cCenCos = '009' AND B.cEstado = 'E';";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1],
             'TGENERA' => $laFila[2], 'CDESREQ' => $laFila[3],
             'CNOMBRE' => $laFila[4], 'MOBSERV' => $laFila[5],
             'CESTADO' => $laFila[6], 'CDESCRI' => $laFila[7], 'CIDACTI' => $laFila[8]];
         $i++;
      }
      return true;
   }

   #CARGAR CABEZERA DE REQUERIMIENTO # EDITAR REQ POR LOGISTICA ERP2290 FRL 17-05-18
   public function omCargarRequerimientoEditarL() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRequerimientoEditarL($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRequerimientoEditarL($p_oSql) {
      #TRAE MONEDAS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '007'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE MONEDAS DEFINIDOS [S01TTAB.007]";
         return false;
      }
      #TRAER TIPO DE REQUERIMIENTOS
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('R','A','E','M')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipReq[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE REQUERIMIENTO DEFINIDOS [S01TTAB.075]";
         return false;
      }
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, A.cTipo, 
                       A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin, A.cIdActi, A.cNroDoc, D.cNroRuc, TRIM(E.cRazSoc), 
                       TRIM(D.cNroCom), D.nMonto, D.dFecha, A.cPeriod, A.cDesAct
                  FROM V_E01MREQ_1 A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                  LEFT OUTER JOIN E01DCOM D ON D.cIdRequ = A.cIdRequ
                  LEFT OUTER JOIN S01MPRV E ON E.cNroRuc = D.cNroRuc
                  WHERE A.cEstado = 'A' AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR AL RECUPERAR  REQUERIMIENTOS ASIGNADOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcPath = 'Docs/Logistica/Requerimiento/'.$laFila[0].'.pdf';
         $this->paDatos[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'CNOMCOT' => $laFila[4], 'MOBSERV' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
                             'CCODUSU' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO'   => $laFila[10],'CDESTIP' => $laFila[11],
                             'CMONEDA' => $laFila[12],'CDESMON' => $laFila[13],'CCENCOS' => $laFila[14],'CCOMDIR' => $laFila[15],
                             'CDESTIN' => $laFila[16],'CIDACTI' => $laFila[17],'CNRODOC' => $laFila[18],'CNRORUC' => $laFila[19],
                             'CRAZSOC' => $laFila[20],'CNROCOM' => $laFila[21],'NMONTO'  => $laFila[22],'DFECCOM' => $laFila[23],
                             'CPERIOD' => $laFila[24],'CDESACT' => $laFila[25],'CESPTEC' => (file_exists($lcPath))? 'S' : 'N'];
      }
      return true;
   }

   public function omGrabarRequerimientoFisico() {
      $llOk = $this->mxValidarGrabarRequerimiento();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarRequerimientoFisico($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarRequerimientoFisico($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_E01MREQ_8('$lcJson')";
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

   #-------MANDAR A PROCESAR ORDENES GENERANDO CODIGO ANTIGUO AUTOMATICAMENTE-------
   # Actualizar Codigo Antiguo de Orden de C/S
   public function omMandarProcesarOrden() {
      $llOk = $this->mxValMandarProcesarOrden();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxMandarProcesarOrden($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValMandarProcesarOrden() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = "CÓDIGO DE USUARIO INVÁLIDO";
         return false;
      } else if (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) < 1) {
         $this->pcError = "ORDENES PARA PROCESAR INVALIDAS";
         return false;
      }
      return true;
   }

   protected function mxMandarProcesarOrden($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MORD_4('$lcJson')";
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

   # Init Behavior de Pantalla de actualizacion de codigo
   public function omInitErp2160B2() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitErp2160B2($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitErp2160B2($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      #TRAER TODOS LOS DETALLES DE UN REQUERIMIENTO
      /*
      <th scope="col">ID</th>
      <th scope="col">Nro RUC</th>
      <th scope="col">Fecha de Generacion</th>
      <th scope="col">Tipo</th>
      <th scope="col">Moneda</th>
      <th scope="col">Monto</th>
      <th scope="col">Observacion</th>
      <th scope="col">OC/S</th>
      */
      $lcSql = "SELECT A.cIdOrde, A.cNroRuc, A.dGenera, A.cTipo, B.cDescri AS cDesTip, A.cMoneda, C.cDescri AS cDesMon, A.nMonto, 
                       A.mObserv, A.cCodAnt, D.cRazSoc, COUNT(E.cIdOrde) AS nCanArt
                FROM E01MORD A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '075' AND B.cCodigo = A.cTipo
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '007' AND C.cCodigo = A.cMoneda
                INNER JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
                INNER JOIN E01DORD E ON E.cIdOrde = A.cIdOrde
                WHERE A.cUsuCod = '$lcCodUsu' AND A.cCodAnt = '' AND A.cEstado = 'A'
                GROUP BY A.cIdOrde, B.cDescri, C.cDescri, D.cRazSoc
                ORDER BY A.cIdOrde";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paOrdene[] = ['CIDORDE' => $laFila[0], 'CNRORUC' => $laFila[1], 'DGENERA' => $laFila[2], 'CTIPO'  => $laFila[3],
                              'CDESTIP' => $laFila[4], 'CMONEDA' => $laFila[5], 'CDESMON' => $laFila[6], 'NMONTO' => $laFila[7],
                              'MOBSERV' => $laFila[8], 'CCODANT' => $laFila[9], 'CMASK' => $laFila[3] == 'B'? 'OC':'O'.$laFila[3],
                              'CRAZSOC' => $laFila[10],'NCANART' => $laFila[11]];
         $i++;
      }
      return true;
   }

   #ASIGNACION DE REQUERIMIENTOS A COTIZADORES - CARGAR REQUERIMIENTOS X DESTINO (ALMACEN/OFICIO)
   public function omCargarRequerimientosxDestino() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRequerimientosxDestino($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRequerimientosxDestino($p_oSql) {
      $lcDestin = $this->paData['CDESTIN'];
      $lcPeriod = $this->paData['CPERIOD'];
      #TRAER CABECERA DE TODOS LOS REQUERIMIENTOS AUN SIN ASIGNAR DEPENDIENDO EL DESTINO
      $lcSql = "SELECT A.cIdRequ, TRIM(A.cCenCos), B.cDescri, A.cDescri, A.mObserv, A.cEstado, A.cTipo, C.cDescri as cDesTip, D.cDescri, TO_CHAR(A.TGENERA,'YYYY-MM-DD HH24:MI') AS tGenera 
                     FROM E01MREQ A
                     INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                     LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
                     INNER JOIN P02MACT E ON E.cIdActi = A.cIdActi
                  WHERE A.cUsuCot = '9999' AND A.cEstado IN ('A','B') AND A.cTipo NOT IN ('R','A','E','M') AND E.cTipAct NOT IN ('E')
                        AND TO_CHAR(A.tGenera, 'YYYY') = '$lcPeriod'";
      if ($lcDestin != 'TODOS') {
         $lcSql .= " AND A.cDestin = '$lcDestin'";
      }
      $lcSql .= " ORDER BY A.tGenera, A.cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2], 'CDESCRI' => $laFila[3],
                              'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CTIPO'   => $laFila[6], 'CDESTIP' => $laFila[7],
                              'CDESMON' => $laFila[8], 'TGENERA' => $laFila[9]];
         $i++;
      }
      return true;
   }

   #SEGUIMIENTO - EDICION DE COTIZACIONES -- Erp2320.php -- JLF
   public function omInitSeguimientoCotizacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitSeguimientoCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitSeguimientoCotizacion($p_oSql) {
      #TRAE TODAS LAS SITUACIONES DE COTIZACION DISPONIBLE
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '086'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS 1';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY SITUACIONES DE COTIZACION DEFINIDAS [S01TTAB.086]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paSituac[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE TODAS LOS ESTADOS DE COTIZACION DISPONIBLE
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '078'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS 2';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY ESTADOS DE COTIZACION DEFINIDOS [S01TTAB.078]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paEstado[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAE PERIODOS DE COTIZACIONES
      $lcSql = "SELECT DISTINCT TO_CHAR(tInicio, 'YYYYMM') FROM E01MCOT ORDER BY TO_CHAR(tInicio, 'YYYYMM') DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY PERIODOS DEFINIDOS';
         return false;
      }
      $llFlag = false;
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($llFlag && $lcAnio != substr($laFila[0], 0, 4)) $this->paPeriod[] = ['CCODIGO' => $lcAnio.'00', 'CDESCRI'  => $lcAnio.'00'];
         $this->paPeriod[] = ['CCODIGO' => $laFila[0], 'CDESCRI'  => $laFila[0]];
         $lcAnio = substr($laFila[0], 0, 4);
         $llFlag = true;
      }
      if ($llFlag) $this->paPeriod[] = ['CCODIGO' => $lcAnio.'00', 'CDESCRI'  => $lcAnio.'00'];
      #TRAE COTIZADORES
      $lcSql = "SELECT cCodUsu, cNroDni, cNombre, cNivel FROM V_S01TUSU_1 WHERE cEstado = 'A' AND cNivel IN ('CO','JL')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY COTIZADORES DEFINIDOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUsuCot[] = ['CCODUSU' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2], 'CNIVEL' => $laFila[3]];
      }
      return true;
   }

   public function omBuscarCotizacion() {
      $llOk = $this->mxValBuscarCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarCotizacion() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOT']) || strlen(trim($this->paData['CUSUCOT'])) != 4) {
         $this->pcError = "COTIZADOR INVALIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO']) || strlen(trim($this->paData['CESTADO'])) != 1) {
         $this->pcError = "ESTADO DE COTIZACION INVALIDO";
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 6) {
         $this->pcError = "PERIODO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCOMDIR']) || strlen(trim($this->paData['CCOMDIR'])) != 1) {
         $this->pcError = "INDICADOR DE COMPRA DIRECTA INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarCotizacion($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcEstado = $this->paData['CESTADO'];
      $lcPeriod = $this->paData['CPERIOD'];
      $lcComDir = $this->paData['CCOMDIR'];
      #TRAER CABECERA DE TODAS LAS COTIZACIONES PERTENECIENTES COTIZADOR
      $lcSql = "SELECT A.cIdCoti, TO_CHAR(A.tInicio,'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tFinali,'YYYY-MM-DD HH24:MI'), A.cSituac, C.cDescri AS cDesReq, 
                       C.cCenCos, D.cDescri, C.cIdRequ, C.tGenera, SUM(E.nCantid * E.nPrecio) AS nTotal, F.cDescri AS cDesSit, A.cDescri, A.cLugar, 
                       A.cEstado, G.cDescri AS cDesEst, A.cArcPrv, C.cMoneda, H.cDescri AS cDesMon, A.nLimPrv
                  FROM E01MCOT A
                  INNER JOIN E01PREQ B ON B.cIdCoti = A.cIdCoti
                  INNER JOIN E01MREQ C ON C.cIdRequ = B.cIdRequ
                  INNER JOIN S01TCCO D ON D.cCenCos = C.cCenCos
                  INNER JOIN E01DREQ E ON E.cIdRequ = C.cIdRequ
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '086' AND F.cCodigo = A.cSituac
                  LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '078' AND G.cCodigo = A.cEstado
                  LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '007' AND H.cCodigo = C.cMoneda
                  WHERE A.cEstado = '$lcEstado' AND C.cComDir = '$lcComDir' ";
      if (substr($lcPeriod, 4) == '00') {
         $lcPeriod = substr($lcPeriod, 0, 4);
         $lcSql .= "AND TO_CHAR(C.tGenera, 'YYYY') = '$lcPeriod' ";
      } else {
         $lcSql .= "AND TO_CHAR(C.tGenera, 'YYYYMM') = '$lcPeriod' ";
      }
      if ($lcUsuCot != 'TTTT') {
         $lcSql .= "AND C.cUsuCot = '$lcUsuCot' ";
      }
      $lcSql .= "GROUP BY A.cIdCoti, D.cDescri, C.cIdRequ, F.cDescri, G.cDescri, H.cDescri
                 ORDER BY A.cIdCoti DESC, nTotal DESC, C.tGenera ASC, C.cIdRequ ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      }
      $lcIdCoti = '';
      $this->paCotiza = null;
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($lcIdCoti != $laFila[0]) {
            $lcIdCoti = $laFila[0];
            $lcSql = "SELECT COUNT(CASE WHEN cEstado IN ('E','C') THEN cCodigo END), COUNT(CASE WHEN cEstado NOT IN ('X') THEN cCodigo END) FROM E01PCOT WHERE cIdCoti = '$lcIdCoti' AND cNroRuc != '00000000000' ";
            $RS2 = $p_oSql->omExec($lcSql);
            if (!$RS2) {
               $this->pcError = 'ERROR DE EJECUCIÓN DE BASE DE DATOS 2';
               return false;
            }
            $laTmp = $p_oSql->fetch($RS2);
            $this->paCotiza[] = ['CIDCOTI' => $laFila[0], 'TINICIO' => $laFila[1], 'TFINALI' => $laFila[2], 'CSITUAC' => $laFila[3],
                                 'CDESREQ' => $laFila[4], 'CCENCOS' => $laFila[5], 'CDESCCO' => $laFila[6], 'CIDREQU' => $laFila[7],
                                 'CDESSIT' => $laFila[10],'CDESCRI' => $laFila[11],'CLUGAR'  => $laFila[12],'CESTADO' => $laFila[13],
                                 'CDESEST' => $laFila[14],'CARCPRV' => $laFila[15],'CMONEDA' => $laFila[16],'CDESMON' => $laFila[17],
                                 'NLIMPRV' => $laFila[18],'NCANTID' => $laTmp[0],  'NINVITA' => $laTmp[1]];
         }
      }
      return true;
   }

   #SEGUIMIENTO DE COTIZACIONES - GRABAR -- Erp2320.php -- JLF
   public function omGrabarSeguimientoCotizacion() {
      $llOk = $this->mxValParamGrabarSeguimientoCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGrabarSeguimientoCotizacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarSeguimientoCotizacion() {
      if ($this->paData['TFINALI'] <= $this->paData['TINICIO']) {
         $this->pcError = 'FECHA FIN ES MENOR O IGUAL A FECHA INICIO';
         return false;
      } else if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO';
         return false;
      } else if (!isset($this->paData['CLUGAR']) || empty($this->paData['CLUGAR']) || strlen(trim($this->paData['CLUGAR'])) > 100) {
         $this->pcError = 'LUGAR DE ENTREGA INVALIDO';
         return false;
      } else if (!isset($this->paData['CDESCRI']) || empty($this->paData['CDESCRI']) || strlen(trim($this->paData['CDESCRI'])) > 200) {
         $this->pcError = 'DESCRIPCIÓN DE COTIZACIÓN INVALIDA';
         return false;
      } else if (!isset($this->paData['CARCPRV']) || strlen(trim($this->paData['CARCPRV'])) != 1) {
         $this->pcError = 'OPCION DE SUBIR ARCHIVOS A PROVEEDOR INVALIDA';
         return false;
      } else if (!isset($this->paData['NLIMPRV']) || strlen(trim($this->paData['NLIMPRV'])) == 0) {
         $this->pcError = 'LIMITE PARA AÑADIR PROVEEDORES INVALIDO3';
         return false;
      }
      $this->paData['CLUGAR']  = strtoupper($this->paData['CLUGAR']);
      $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarSeguimientoCotizacion($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MCOT_2('$lcJson')";
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

   #SEGUIMIENTO - EDICION DE COTIZACIONES -- Erp2320.php -- JLF
   public function omCargarDetalleCotizacion() {
      $llOk = $this->mxValCargarDetalleCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarDetalleCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarDetalleCotizacion() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDCOTI']) || strlen(trim($this->paData['CIDCOTI'])) != 8) {
         $this->pcError = "COTIZADOR INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarDetalleCotizacion($p_oSql) {
      #TRAER TIPO DE PRECIO
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '096'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO SE PUDIERON RECUPERAR LOS TIPOS DE PRECIO S01TTAB[096]';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipPre[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAER FORMAS DE PAGO
      $lcSql = "SELECT TRIM(cCodigo), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '097'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO SE PUDIERON RECUPERAR LAS FORMAS DE PAGO S01TTAB[097]';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paForPag[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      #TRAER DETALLE COTIZACION
      $lcSql = "SELECT A.nSerial, A.cCodigo, A.cCodArt, A.cDescri, A.mObserv, A.nCantid, A.nPrecio, B.cDescri AS cDesArt, B.cEstado, 
                       B.cUnidad, A.cMarca, A.cIncIgv, C.cDescri AS cDesUni FROM E01DCOT A
                  INNER JOIN E01MART B ON B.cCodArt = A.cCodArt 
                  LEFT OUTER JOIN S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                  INNER JOIN E01PCOT D ON D.cCodigo = A.cCodigo
                  WHERE D.cIdCoti ='{$this->paData['CIDCOTI']}' AND D.cNroRuc = '00000000000' ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO SE PUDO RECUPERAR EL DETALLE DE LA COTIZACIÓN';
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCODIGO' => $laFila[1], 'CCODART' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'MOBSERV' => $laFila[4], 'NCANTID' => $laFila[5], 'NPRECIO' => $laFila[6], 'CDESART' => $laFila[7],
                             'CESTADO' => $laFila[8], 'CUNIDAD' => $laFila[9], 'CMARCA'  => $laFila[10],'CINCIGV' => $laFila[11],
                             'CDESUNI' => $laFila[12]];
      }
      return true;
   }

   #CARGAR CABEZERA DE REQUERIMIENTO
   # RECHAZAR REQ POR LOGISTICA Erp2330 JLF 24-09-2018
   # y
   # DIVIDIR REQ POR LOGISTICA Erp2340 JLF 04-10-2018
   public function omCargarRequerimientoLogistica() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRequerimientoLogistica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRequerimientoLogistica($p_oSql) {
      #TRAE CABECERAS DE REQUERIMIENTOS
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cIdRequ, A.cDesCCo, A.tGenera, A.cDesReq, A.cNomCot, A.mObserv, A.cEstado, B.cDescri, A.cCodUsu, C.cNombre, A.cTipo, 
                       A.cDesTip, A.cMoneda, A.cDesMon, A.cCenCos, A.cComDir, A.cDestin, A.cIdActi, A.cNroDoc, D.cNroRuc, TRIM(E.cRazSoc), 
                       TRIM(D.cNroCom), D.nMonto, D.dFecha 
                  FROM V_E01MREQ_1 A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '076' AND B.cCodigo = A.cEstado
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodUsu
                  LEFT OUTER JOIN E01DCOM D ON D.cIdRequ = A.cIdRequ
                  LEFT OUTER JOIN S01MPRV E ON E.cNroRuc = D.cNroRuc
                  WHERE A.cEstado = 'A' AND A.cUsuCot = '$lcCodUsu' ORDER BY A.tGenera ASC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'CNOMCOT' => $laFila[4], 'MOBSREQ' => $laFila[5], 'CESTADO' => $laFila[6], 'CDESEST' => $laFila[7],
                             'CCODUSU' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CTIPO'   => $laFila[10],'CDESTIP' => $laFila[11],
                             'CMONEDA' => $laFila[12],'CDESMON' => $laFila[13],'CCENCOS' => $laFila[14],'CCOMDIR' => $laFila[15],
                             'CDESTIN' => $laFila[16],'CIDACTI' => $laFila[17],'CNRODOC' => $laFila[18],'CNRORUC' => $laFila[19],
                             'CRAZSOC' => $laFila[20],'CNROCOM' => $laFila[21],'NMONTO'  => $laFila[22],'DFECCOM' => $laFila[23]];
      }
      return true;
   }

   public function omGrabarRechazoRequerimientoL() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarRechazoRequerimientoL($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarRechazoRequerimientoL($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
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

   public function omCargarUsuariosCotizadores() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarUsuariosCotizadores($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarUsuariosCotizadores($p_oSql) {
      #TRAER USUARIOS COTIZADORES
      $lcSql = "SELECT A.cCodUsu, A.cNroDni, B.cNombre, cNivel FROM S01TUSU A
                  INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni 
                  WHERE A.cEstado = 'A' AND A.cNivel IN ('CO','AL')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUsuCot[] = ['CUSUCOT' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2], 'CNIVEL' => $laFila[3]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY USUARIOS COTIZADORES REGISTRADOS ACTUALMENTE";
         return false;
      }
      return true;
   }

   #SEGUIMIENTO DE COTIZACIONES - GRABAR -- Erp2320.php -- JLF
   public function omGrabarDivisionRequerimiento() {
      $llOk = $this->mxValParamGrabarDivisionRequerimiento();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarDivisionRequerimiento($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarDivisionRequerimiento() {
      if ($this->paData['MDATOS'] == null) {
         $this->pcError = 'NO HA MARCADO NINGÚN ARTÁCULO';
         return false;
      }
      return true;
   }

   protected function mxGrabarDivisionRequerimiento($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_9('$lcJson')";
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

   #Cargar Proveedores de una Cotizacion
   public function omCargarProveedoresCotizacion() {
      $llOk = $this->mxValCargarProveedoresCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarProveedoresCotizacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarProveedoresCotizacion() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDCOTI']) || strlen(trim($this->paData['CIDCOTI'])) != 8) {
         $this->pcError = "COTIZADOR INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarProveedoresCotizacion($p_oSql) {
      #PROVEEDORES DE UNA COTIZACION
      $lcIdCoti = $this->paData['CIDCOTI'];
      $lcSql = "SELECT A.cCodigo, A.cNroRuc, B.cRazSoc FROM E01PCOT A
                INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                WHERE A.cIdCoti = '$lcIdCoti' AND A.cEstado IN ('E','C') AND A.cNroRuc != '00000000000'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $lcPath = 'Docs/Logistica/Cotizacion/'.$laFila[0].'.pdf';
         $this->paPrvCot[] = ['CCODIGO' => $laFila[0], 'CNRORUC' => $laFila[1], 'CRAZSOC' => $laFila[2], 'CARCHIV' => (file_exists($lcPath))? 'S' : 'N'];
      }
      return true;
   }

   #Carga de Tipos de Rubros
   public function omCargarTiposRubros() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarTiposRubros($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarTiposRubros($p_oSql) {
      #TRAER TABLA DE TIPOS DE RUBRO DE LOS PROVEEDORES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '109' ORDER BY cDescri";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipRub[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE RUBROS DEFINIDOS [S01TTAB.109]";
         return false;
      }
      return true;
   }

   #Carga de Tipos de Rubros
   public function omCargarRubrosxTipo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarRubrosxTipo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarRubrosxTipo($p_oSql) {
      #TRAER TABLA DE TIPOS DE RUBRO DE LOS PROVEEDORES
      $lcTipRub = $this->paData['CTIPRUB'];
      $lcSql = "SELECT cRubro, cDescri, cDetall FROM S01TRUB WHERE cEstado = 'A'";
      if ($lcTipRub != 'TODOS') {
         $lcSql = $lcSql." AND cTipRub = '$lcTipRub'";
      }
      $lcSql = $lcSql." ORDER BY cRubro";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRubro[] = ['CRUBRO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDETALL' => $laFila[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY RUBROS DEFINIDOS";
         return false;
      }
      return true;
   }

   # TRAE TIPOS DE ARTICULO
   public function omCargarTipoArticulo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarTipoArticulo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarTipoArticulo($p_oSql) {
      $lcSql = "SELECT cCodigo, cDescri FROM  V_S01TTAB WHERE cCodTab = '082' ORDER BY cCodigo";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paTipArt [] = ['CCODIGO' => $laTmp[0], 'CDESCRI' => $laTmp[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE ARTICULO DEFINIDOS";
         return false;
      }
      return true;
   }

   #TRAE GRUPO DE ARTICULOS
   public function omCargarGrupoArticulo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarGrupoArticulo($loSql);
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarGrupoArticulo($p_oSql) {
      $lcSql = "SELECT cCodigo, cDescri FROM  V_S01TTAB WHERE cCodTab = '083' ORDER BY cCodigo";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paGruArt [] = ['CCODIGO' => $laTmp[0], 'CDESCRI' => $laTmp[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY GRUPOS DE ARTICULO DEFINIDOS";
         return false;
      }
      return true;
   }

   #BUSCA COMPROBANTES DE PAGO PARA REVISAR PAGOS - Erp2370
   # 2018-11-19 JLF Creación
   public function omBuscarComprobantes() {
      $llOk = $this->mxValBuscarComprobantes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarComprobantes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarComprobantes() {
      $loDate = new CDate();
      if (!isset($this->paData['CBUSCOM']) || empty($this->paData['CBUSCOM']) || strlen($this->paData['CBUSCOM']) > 50) {
         $this->pcError = 'BÚSQUEDA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CPAGADO']) || strlen(trim($this->paData['CPAGADO'])) != 1) {
         $this->pcError = 'FILTRO DE PAGO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CENVCON']) || strlen(trim($this->paData['CENVCON'])) != 1) {
         $this->pcError = 'FILTRO DE ENVIO A CONTABILIDAD INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['DFECINI']) || empty($this->paData['DFECINI']) || strlen($this->paData['DFECINI']) != 10) {
         $this->pcError = 'FECHA DE INICIO INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['DFECFIN']) || empty($this->paData['DFECFIN']) || strlen($this->paData['DFECFIN']) != 10) {
         $this->pcError = 'FECHA DE FIN INVÁLIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFECINI'])) {
         $this->pcError = 'FECHA DE INICIO INVÁLIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFECFIN'])) {
         $this->pcError = 'FECHA DE FIN INVÁLIDA';
         return false;
      }
      return true;
   }

   protected function mxBuscarComprobantes($p_oSql) {
      #TRAER TODOS LOS COMPROBANTES ASOCIADOS A LA BUSQUEDA
      $lcBusCom = $this->paData['CBUSCOM'];
      $lcBusCom = str_replace(' ', '%', trim($lcBusCom));
      $lcBusCom = str_replace('#', 'Ñ', trim($lcBusCom));
      $ldFecIni = $this->paData['DFECINI'];
      $ldFecFin = $this->paData['DFECFIN'];
      $lcSql = "SELECT A.cIdOrde, A.cIdComp, A.cNroRuc, B.cRazSoc, A.cTipCom, D.cDescri AS cDesTip, TRIM(A.cNroCom), A.dFecEmi, C.cMoneda, 
                       E.cDescri AS cDesMon, C.cTipo, F.cDescri AS cTipDes, A.nMonto, A.nMonIgv, A.nInafec, A.nAdicio, TRIM(A.mObserv), 
                       C.cCodAnt, C.mObserv, A.cAsient, A.dEnvCon, TRIM(B.cCodAnt) AS cCodPrv, H.cNumMov, I.cCenCos, I.cCodAnt, I.cDescri
                  FROM E01MFAC A
                  INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                  INNER JOIN E01MORD C ON C.cIdOrde = A.cIdOrde
                  LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '087' AND SUBSTRING(D.cCodigo, 1, 2) = A.cTipCom
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '007' AND SUBSTRING(E.cCodigo, 1, 1) = C.cMoneda
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '075' AND SUBSTRING(F.cCodigo, 1, 1) = C.cTipo
                  LEFT OUTER JOIN E01MNIN G ON G.cIdComp = A.cIdComp AND G.cEstado != 'X'
                  LEFT OUTER JOIN E03MKAR H ON H.cIdKard = G.cIdKard
                  INNER JOIN S01TCCO I ON I.cCenCos = A.cCenCos
                  WHERE A.cEstado NOT IN ('X') AND C.cEstado NOT IN ('X') AND A.dFecEmi BETWEEN '$ldFecIni' AND '$ldFecFin' ";
      if ($lcBusCom != '*') {
         $lcSql .= "AND (C.cCodAnt LIKE '%$lcBusCom' OR B.cNroRuc = '$lcBusCom' OR B.cRazSoc LIKE '%$lcBusCom%' OR B.cCodAnt = '$lcBusCom'
                        OR A.cNroCom = '$lcBusCom' OR H.cNumMov LIKE '%$lcBusCom' OR I.cCenCos = '$lcBusCom' OR I.cCodAnt = '$lcBusCom'
                        OR I.cDescri LIKE '%$lcBusCom%') ";
      }
      if ($this->paData['CENVCON'] == 'S') {
         $lcSql .= "AND A.dEnvCon NOTNULL ";
      }
      $lcSql .= "ORDER BY C.cCodAnt DESC, A.dFecEmi ASC";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      $i = 0;
      $lcIdOrde = '*';
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcFlag = ($lcIdOrde != $laFila[0]) ? '*' : '';
         $lcIdOrde = $laFila[0];
         $lcSql = "SELECT A.nSerial, TO_CHAR(C.tEntreg, 'YYYY-MM-DD'), C.cAsient FROM D02DCPP A
                     INNER JOIN D02MCCT B ON B.cCtaCte = A.cCtaCte
                     INNER JOIN D02MTRX C ON C.cCodTrx = A.cCodTrx
                     WHERE B.cTipo = 'P' AND A.cEstado = 'B' 
                           AND TRIM(A.cCtaCnt) NOT IN (SELECT TRIM(cCodigo) FROM V_S01TTAB WHERE cCodTab = '024') ";
         if (substr($laFila[2], 0, 1) == 'X' || substr($laFila[2], 0, 1) == '0') {
            $lcSql .= "AND TRIM(B.cCodOld) = '$laFila[21]' ";
         } else {
            $lcSql .= "AND TRIM(B.cCodigo) = '$laFila[2]' ";
         }
         $lcSql .= "AND TRIM(A.cCompro) LIKE '$laFila[4]/%$laFila[6]'";
         $R2 = $p_oSql->omExec($lcSql);
         if ($R2 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
            return false;
         }
         $laTmp = $p_oSql->fetch($R2);
         $lcPagado = ($laTmp[0] != null && $laTmp[1] != null)? 'SI' : 'NO';
         if ($lcPagado == 'SI' &&  $this->paData['CPAGADO'] == 'S') {
            continue;
         }
         $this->paCompro[] = ['CIDORDE' => $laFila[0], 'CIDCOMP' => $laFila[1], 'CNRORUC' => $laFila[2], 'CRAZSOC' => $laFila[3],
                              'CTIPCOM' => $laFila[4], 'CDESTIP' => $laFila[5], 'CNROCOM' => $laFila[6], 'DFECEMI' => $laFila[7],
                              'CMONEDA' => $laFila[8], 'CDESMON' => $laFila[9], 'CTIPO'   => $laFila[10],'CTIPDES' => $laFila[11],
                              'NMONTO'  => $laFila[12],'NMONIGV' => $laFila[13],'NINAFEC' => $laFila[14],'NADICIO' => $laFila[15],
                              'CDESCRI' => $laFila[16],'CCODANT' => $laFila[17],'MOBSERV' => $laFila[18],'CASIENT' => $laFila[19],
                              'DENVCON' => $laFila[20],'CCODPRV' => $laFila[21],'CNUMMOV' => ($laFila[22] == null)? 'S/D' : $laFila[22],
                              'CCENCOS' => $laFila[23],'CCCOANT' => $laFila[24],'CDESCCO' => $laFila[25],'CPAGADO' => $lcPagado,
                              'DFECPAG' => $laTmp[1],  'CASICAJ' => $laTmp[2],  'CFLAG'   => $lcFlag];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO SE ENCONTRARON COMPROBANTES DE PAGO";
         return false;
      }
      return true;
   }

   #BUSCA COMPROBANTES DE PAGO PARA REVISAR PAGOS - Erp2380
   # 2019-01-24 JLF Creación
   public function omBuscarComprobantesxProveedor() {
      $llOk = $this->mxValBuscarComprobantesxProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarComprobantesxProveedor($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarComprobantesxProveedor() {
      $loDate = new CDate();
      if (!isset($this->paData['CBUSCOM']) || strlen($this->paData['CBUSCOM']) > 20) {
         $this->pcError = 'BÚSQUEDA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['DFECINI']) || empty($this->paData['DFECINI']) || strlen($this->paData['DFECINI']) != 10) {
         $this->pcError = 'FECHA DE INICIO INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['DFECFIN']) || empty($this->paData['DFECFIN']) || strlen($this->paData['DFECFIN']) != 10) {
         $this->pcError = 'FECHA DE FIN INVÁLIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFECINI'])) {
         $this->pcError = 'FECHA DE INICIO INVÁLIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFECFIN'])) {
         $this->pcError = 'FECHA DE FIN INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CNRORUC']) || empty($this->paData['CNRORUC']) || strlen($this->paData['CNRORUC']) != 11) {
         $this->pcError = 'RUC INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxBuscarComprobantesxProveedor($p_oSql) {
      #TRAER TODOS LOS COMPROBANTES ASOCIADOS A LA BUSQUEDA
      $lcNroRuc = $this->paData['CNRORUC'];
      $lcBusCom = $this->paData['CBUSCOM'];
      $lcBusCom = str_replace(' ', '%', trim($lcBusCom));
      $lcBusCom = str_replace('#', 'Ñ', trim($lcBusCom));
      $ldFecIni = $this->paData['DFECINI'];
      $ldFecFin = $this->paData['DFECFIN'];
      $lcSql = "SELECT A.cIdOrde, A.cIdComp, A.cNroRuc, B.cRazSoc, A.cTipCom, D.cDescri AS cDesTip, TRIM(A.cNroCom), A.dFecEmi, C.cMoneda, 
                       E.cDescri AS cDesMon, C.cTipo, F.cDescri AS cTipDes, A.nMonto, A.nMonIgv, A.nInafec, A.nAdicio, TRIM(A.mObserv), 
                       C.cCodAnt, C.mObserv, A.cAsient, A.dEnvCon, TRIM(B.cCodAnt) AS cCodPrv
                  FROM E01MFAC A
                  INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                  INNER JOIN E01MORD C ON C.cIdOrde = A.cIdOrde
                  LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '087' AND SUBSTRING(D.cCodigo, 1, 2) = A.cTipCom
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '007' AND SUBSTRING(E.cCodigo, 1, 1) = C.cMoneda
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '075' AND SUBSTRING(F.cCodigo, 1, 1) = C.cTipo
                  WHERE A.cNroRuc = '$lcNroRuc' AND A.cEstado NOT IN ('X') AND C.cEstado NOT IN ('X')";
      if (!empty($lcBusCom)) {
         $lcSql .= " AND (C.cCodAnt LIKE '%$lcBusCom' OR A.cNroCom = '$lcBusCom')";
      }
      $lcSql .= " AND A.dFecEmi >= '$ldFecIni' AND A.dFecEmi <= '$ldFecFin' ORDER BY C.cCodAnt DESC, A.dFecEmi ASC";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      $i = 0;
      $lcIdOrde = '*';
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcFlag = ($lcIdOrde != $laFila[0]) ? '*' : '';
         $lcIdOrde = $laFila[0];
         # VERIFICA SI COMPROBANTE ESTA PAGADO
         $lcSql = "SELECT A.nSerial, TO_CHAR(C.tEntreg, 'YYYY-MM-DD'), C.cAsient FROM D02DCPP A
                     INNER JOIN D02MCCT B ON B.cCtaCte = A.cCtaCte
                     INNER JOIN D02MTRX C ON C.cCodTrx = A.cCodTrx
                     WHERE B.cTipo = 'P' AND A.cEstado = 'B' 
                           AND TRIM(A.cCtaCnt) NOT IN (SELECT TRIM(cCodigo) FROM V_S01TTAB WHERE cCodTab = '024') ";
         if (substr($laFila[2], 0, 1) == 'X' || substr($laFila[2], 0, 1) == '0') {
            $lcSql .= "AND TRIM(B.cCodOld) = '$laFila[21]' ";
         } else {
            $lcSql .= "AND TRIM(B.cCodigo) = '$laFila[2]' ";
         }
         $lcSql .= "AND TRIM(A.cCompro) LIKE '$laFila[4]/%$laFila[6]'";
         $R2 = $p_oSql->omExec($lcSql);
         if ($R2 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
            return false;
         }
         $laTmp = $p_oSql->fetch($R2);
         $lcPagado = ($laTmp[0] != null && $laTmp[1] != null)? 'SI' : 'NO';
         $this->paCompro[] = ['CIDORDE' => $laFila[0], 'CIDCOMP' => $laFila[1], 'CNRORUC' => $laFila[2], 'CRAZSOC' => $laFila[3],
                              'CTIPCOM' => $laFila[4], 'CDESTIP' => $laFila[5], 'CNROCOM' => $laFila[6], 'DFECEMI' => $laFila[7],
                              'CMONEDA' => $laFila[8], 'CDESMON' => $laFila[9], 'CTIPO'   => $laFila[10],'CTIPDES' => $laFila[11],
                              'NMONTO'  => $laFila[12],'NMONIGV' => $laFila[13],'NINAFEC' => $laFila[14],'NADICIO' => $laFila[15],
                              'CDESCRI' => $laFila[16],'CCODANT' => $laFila[17],'MOBSERV' => $laFila[18],'CASIENT' => $laFila[19],
                              'DENVCON' => $laFila[20],'CCODPRV' => $laFila[21],'CPAGADO' => $lcPagado,  'DFECPAG' => $laTmp[1],
                              'CASICAJ' => $laTmp[2],  'CFLAG'   => $lcFlag];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO SE ENCONTRARON COMPROBANTES DE PAGO";
         return false;
      }
      return true;
   }

   # CARGA ACTIVIDADES X CENTRO DE COSTO
   # 2018-11-26 JLF - Creación
   public function omCargarActividadxCentroCosto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarActividadxCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarActividadxCentroCosto($p_oSql) {
      #TRAER ACTIVIDADES DE UN CENTRO DE COSTO EN UN PERIODO
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT cPeriod FROM S01TPER WHERE cTipo = 'S0'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY UN PERIODO ACTIVO DEFINIDO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lcPeriod = $laFila[0];
      $lcSql = "SELECT A.cIdActi, B.cDescri FROM P02MACT A INNER JOIN V_S01TTAB B ON B.cCodTab = '052' AND B.cCodigo = A.cTipAct
                WHERE cCenCos IN ('$lcCenCos') AND cPeriod = '$lcPeriod' AND cEstado IN ('A','D') AND A.cTipAct IN ('I','E','C','U','L','M')
                ORDER BY A.cIdActi";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paActivi[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      $lcSql = "SELECT A.cIdActi, A.cDesCri || ' - ' || B.cDescri FROM P02MACT A INNER JOIN V_S01TTAB B ON B.cCodTab = '052' AND B.cCodigo = A.cTipAct
                WHERE A.cCenCos IN ('$lcCenCos') AND A.cPeriod = '$lcPeriod' AND A.cEstado IN ('A','D') AND A.cTipAct IN ('A','P','S','T','R','V','Q') ORDER BY A.cIdActi";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paActivi[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "CENTRO DE COSTO NO TIENE ACTIVIDADES EN SU PLAN OPERATIVO ANUAL";
         return false;
      }
      return true;
   }

   # CARGA PRESUPUESTO X ACTIVIDAD
   # 2019-02-13 JLF - Creación
   public function omCargarPresupuestoActividad() {
      $llOk = $this->mxValCargarPresupuestoActividad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarPresupuestoActividad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValCargarPresupuestoActividad() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CIDACTI']) || strlen(trim($this->paData['CIDACTI'])) != 8) {
         $this->pcError = "ACTIVIDAD INVALIDA";
         return false;
      }
      return true;
   }

   protected function mxCargarPresupuestoActividad($p_oSql) {
      #CARGA PRESUPUESTO DE UNA ACTIVIDAD
      $lcIdActi = $this->paData['CIDACTI'];
      $lcSql = "SELECT t_nPreApr, t_nMonEje, t_nMonCom, t_nSaldo, t_nMonReq FROM F_P02MACT_1('$lcIdActi')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR AL CARGAR PRESUPUESTO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData += ['NPREAPR' => $laFila[0], 'NMONEJE' => $laFila[1], 'NMONCOM' => $laFila[2], 'NSALDO' => $laFila[3], 'NMONREQ' => $laFila[4]];
      return true;
   }

   # Validacion de usuarios de Logistica
   # 2019-02-26 JLF Creación
   protected function mxValUsuarioLogistica($p_oSql) {
      # VALIDA USUARIO LOGISTICA
      $lcCodUsu = $this->paData['CUSUCOD'];
      #$lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCenCos = '02W' AND cCodUsu = '$lcCodUsu' AND cEstado = 'A'";
      $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE  cCenCos = '02W' AND cCodUsu = '$lcCodUsu' AND cModulo = '000' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
      return true;
   }

   # Validacion de usuarios de Contabilidad
   # 2019-02-28 JLF Creación
   protected function mxValUsuarioContabilidad($p_oSql) {
      # VALIDA USUARIO CONTABILIDAD
      $lcCodUsu = $this->paData['CUSUCOD'];
      #$lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCenCos = '02U' AND cCodUsu = '$lcCodUsu' AND cEstado = 'A'";
      $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE  cCenCos = '02U' AND cCodUsu = '$lcCodUsu' AND cModulo = '000' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
      return true;
   }

   # Consulta de Envios de Ordenes y Comprobantes a Vice Adm. y/o Contabilidad
   # 2019-02-25 JLF Creación
   public function omInitConsultaCargoLogistica() {
      $llOk = $this->mxValInitConsultaCargoLogistica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitConsultaCargoLogistica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitConsultaCargoLogistica() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CÓDIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxInitConsultaCargoLogistica($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      # TRAER TIPO DE ORDENES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('R','A','E','M')";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipOrd[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "NO HAY TIPOS DE ORDENES DEFINIDOS [S01TTAB.075]";
         return false;
      }
      # CARGAR ESTADOS DE ENVIO OJO:NO ESTAN EN DB
      for ($i = 65; $i <= 69; $i++) {
         if (chr($i) == 'A') {
            $lcDescri = 'NO ENVIADAS A PRESUPUESTO';
         } else if (chr($i) == 'B') {
            $lcDescri = 'NO ENVIADAS A VIC. ADMINISTRATIVO';
         } else if (chr($i) == 'C') {
            $lcDescri = 'ENVIADAS A VIC. ADMINISTRATIVO';
         } else if (chr($i) == 'D') {
            $lcDescri = 'NO ENVIADAS A CONTABILIDAD';
         } else if (chr($i) == 'E') {
            $lcDescri = 'ENVIADAS A CONTABILIDAD';
         } else {
            $this->pcError = "ESTADO DE ENVIO NO DEFINIDO";
            return false;
         }
         $this->paEstEnv[] = ['CCODIGO' => chr($i), 'CDESCRI' => $lcDescri];
      }
      return true;
   }

   # ---------------------------------------------------------
   # BUSCA ORDENES Y COMPROBANTES DE PAGO PARA REVISAR ENVIOS
   # 2019-02-25 JLF Creación
   # 2022-08-05 APR Modificación
   # ---------------------------------------------------------
   public function omBuscarOrdenesComprobantesCargo() {
      $llOk = $this->mxValBuscarOrdenesComprobantesCargo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarOrdenesComprobantesCargo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarOrdenesComprobantesCargo() {
      if (!isset($this->paData['CBUSORD']) || strlen($this->paData['CBUSORD']) > 50) {
         $this->pcError = 'BÚSQUEDA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CTIPORD']) || strlen($this->paData['CTIPORD']) != 1) {
         $this->pcError = 'TIPO DE ORDEN INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CESTENV']) || strlen($this->paData['CESTENV']) != 1) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxBuscarOrdenesComprobantesCargo($p_oSql) {
      #TRAER TODOS LOS COMPROBANTES ASOCIADOS A LA BUSQUEDA
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcNivel  = $this->paData['CNIVEL'];
      $lcBusOrd = $this->paData['CBUSORD'];
      $lcBusOrd = str_replace(' ', '%', trim($lcBusOrd));
      $lcBusOrd = str_replace('#', 'Ñ', trim($lcBusOrd));
      $lcTipOrd = $this->paData['CTIPORD'];
      $lcEstEnv = $this->paData['CESTENV'];
      if ($lcNivel == 'CO') {
         $lcSql .= "SELECT A.cIdOrde, A.cNroRuc, B.cRazSoc, A.dGenera, A.cTipo, C.cDescri AS cDesTip, A.nMonto, A.cMoneda, D.cDescri AS cDesMon, 
                        A.mObserv, A.cCodAnt, A.cCtaCnt, E.cDescri AS cDesCta, A.cPeriod, A.dEnvio AS dEnvPrv, A.dEnvVic, A.dRecVic, I.cIdRequ, J.cDescri FROM E01MORD A
                     INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                     LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '075' AND SUBSTRING(C.cCodigo, 1, 1) = A.cTipo
                     LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND SUBSTRING(D.cCodigo, 1, 1) = A.cMoneda
                     INNER JOIN D01MCTA E ON E.cCtaCnt = A.cCtaCnt 
                     INNER JOIN E01PORD F ON F.cIdOrde = A.cIdOrde
                     INNER JOIN E01MCOT G ON G.cIdCoti = F.cIdCoti
                     INNER JOIN E01PREQ H ON H.cIdCoti = G.cIdCoti
                     INNER JOIN E01MREQ I ON I.cIdRequ = H.cIdRequ
                     LEFT OUTER JOIN S01TCCO J ON J.cCenCos = I.cCenCos 
                     WHERE A.cCodAnt != '' AND A.cTipo = '$lcTipOrd' AND I.cUsuCot = '$lcCodUsu'";
      } else {
         $lcSql = "SELECT A.cIdOrde, A.cNroRuc, B.cRazSoc, A.dGenera, A.cTipo, C.cDescri AS cDesTip, A.nMonto, A.cMoneda, D.cDescri AS cDesMon, 
                        A.mObserv, A.cCodAnt, A.cCtaCnt, E.cDescri AS cDesCta, A.cPeriod, A.dEnvio AS dEnvPrv, A.dEnvVic, A.dRecVic FROM E01MORD A
                     INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
                     LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '075' AND SUBSTRING(C.cCodigo, 1, 1) = A.cTipo
                     LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND SUBSTRING(D.cCodigo, 1, 1) = A.cMoneda
                     INNER JOIN D01MCTA E ON E.cCtaCnt = A.cCtaCnt 
                     WHERE A.cCodAnt != '' AND A.cTipo = '$lcTipOrd'";
      }
      if ($lcEstEnv == 'A') {
         $lcSql .= " AND A.cEstado IN ('A') AND A.dEnvPre ISNULL";
      } elseif ($lcEstEnv == 'B') {
         $lcSql .= " AND CASE WHEN A.cTipo != 'D' THEN A.cCodPar NOTNULL ELSE A.dGenera >= '2019-01-01' END AND A.cEstado IN ('F','B') AND A.dEnvVic ISNULL";
      } elseif ($lcEstEnv == 'C') {
         $lcSql .= " AND A.cEstado IN ('F','B') AND A.dEnvVic NOTNULL AND A.dRecVic ISNULL";
      } else {
         $lcSql .= " AND A.cIdOrde IN (SELECT cIdOrde FROM E01MFAC WHERE cEsTado != 'X') AND CASE WHEN A.dGenera >= '2019-01-01' THEN A.cEstado IN ('B','C') AND A.dEnvVic NOTNULL AND A.dRecVic NOTNULL ELSE A.cEstado IN ('B','C') END";
      }
      if ($lcBusOrd != '') {
         $lcSql .= " AND (A.cNroRuc = '$lcBusOrd' OR B.cRazSoc LIKE '%$lcBusOrd%' OR A.cCodAnt LIKE '%$lcBusOrd%')";
      }
      $lcSql .= " ORDER BY A.cCodAnt";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      }
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcNotIng = null;
         $lcFlag = '*';
         $lcIdOrde = $laFila[0];
         # CARGAR NOTAS DE INGRESO
         $lcSql = "SELECT cNotIng FROM E01MNIN WHERE cIdOrde = '$laFila[0]' AND cEstado != 'X'";
         $R3 = $p_oSql->omExec($lcSql);
         $lcNotIng = $p_oSql->fetch($R3);
         if ($R3 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS AL CONSULTAR NOTA DE INGRESO";
            return false;
         }
         # CARGAR CODIGO DE PROVEEDOR
         $lcSql = "SELECT cCodOld FROM D02MCCT WHERE cCodigo = '$laFila[1]';";
         $R4 = $p_oSql->omExec($lcSql);
         $lcCodOld = $p_oSql->fetch($R4);
         if ($R4 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS AL CONSULTAR CODIGO DE PROVEEDOR";
            return false;
         }
         # CARGA COMPROBANTES DE PAGO
         if (in_array($lcEstEnv, ['A','B','C'])) {
            $this->paOrdene[$i] = ['CIDORDE' => $laFila[0],  'CNRORUC' => $laFila[1], 'CRAZSOC' => $laFila[2], 'DGENERA' => $laFila[3],
                                   'CTIPO'   => $laFila[4],  'CDESTIP' => $laFila[5], 'NMONORD' => $laFila[6], 'CMONEDA' => $laFila[7],
                                   'CDESMON' => $laFila[8],  'MOBSERV' => $laFila[9], 'CCODANT' => $laFila[10],'CCTACNT' => $laFila[11],
                                   'CDESCTA' => $laFila[12], 'CPERIOD' => $laFila[13],'DENVPRV' => $laFila[14],'DENVVIC' => $laFila[15],
                                   'DRECVIC' => $laFila[16], 'CIDREQU' => $laFila[17],'CCENCOS' => $laFila[18],'CNOTING' => $lcNotIng[0],
                                   'CCODOLD' => $lcCodOld[0],'CIDCOMP' => null,       'CTIPCOM' => null,       'CDESCOM' => null, 
                                   'CNROCOM' => null,        'DFECEMI' => null,       'NMONTO'  => null,       'NMONIGV' => null,
                                   'NINAFEC' => null,        'NADICIO' => null,       'DENVCON' => null,       'CFLAG'   => $lcFlag];
         }
         $i++;
         if (in_array($lcEstEnv, ['D','E'])) {
            $lcSql = "SELECT A.cIdComp, A.cTipCom, B.cDescri AS cDesCom, A.cNroCom, A.dFecEmi, A.nMonto, A.nMonIgv, A.nInafec, A.nAdicio, 
                             A.dEnvCon FROM E01MFAC A
                     LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipCom
                   WHERE A.cIdOrde = '$lcIdOrde' AND A.cEstado != 'X'";
            if ($lcEstEnv == 'D') {
               $lcSql .= " AND A.dFecEmi >= '2019-01-01' AND A.dEnvCon ISNULL";
            } else {
               $lcSql .= " AND A.dEnvCon NOTNULL";
            }
            $lcSql .= " ORDER BY A.dFecEmi, A.cNroCom";
            $R2 = $p_oSql->omExec($lcSql);
            if ($R2 == false) {
               $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
               return false;
            }
            $llFirst = true;
            while ($laTmp = $p_oSql->fetch($R2)) {
               $lcFlag = ($llFirst) ? '*' : '';
               $this->paOrdene[$i - 1] = ['CIDORDE' => $laFila[0],  'CNRORUC' => $laFila[1], 'CRAZSOC' => $laFila[2], 'DGENERA' => $laFila[3],
                                          'CTIPO'   => $laFila[4],  'CDESTIP' => $laFila[5], 'NMONORD' => $laFila[6], 'CMONEDA' => $laFila[7],
                                          'CDESMON' => $laFila[8],  'MOBSERV' => $laFila[9], 'CCODANT' => $laFila[10],'CCTACNT' => $laFila[11],
                                          'CDESCTA' => $laFila[12], 'CPERIOD' => $laFila[13],'DENVPRV' => $laFila[14],'DENVVIC' => $laFila[15],
                                          'DRECVIC' => $laFila[16], 'CIDREQU' => $laFila[17],'CCENCOS' => $laFila[18],'CNOTING' => $lcNotIng[0],
                                          'CCODOLD' => $lcCodOld[0],'CIDCOMP' => $laTmp[0],  'CTIPCOM' => $laTmp[1],  'CDESCOM' => $laTmp[2],
                                          'CNROCOM' => $laTmp[3],   'DFECEMI' => $laTmp[4],  'NMONTO'  => $laTmp[5],  'NMONIGV' => $laTmp[6],  
                                          'NINAFEC' => $laTmp[7],   'NADICIO' => $laTmp[8],  'DENVCON' => $laTmp[9],  'CFLAG'   => $lcFlag];
               $llFirst = false;
               $i++;
            }
         }
      }
      if ($i == 0) {
         $this->pcError = "NO SE ENCONTRARON ORDENES EN BUSQUEDA";
         return false;
      }
      return true;
   }

   # ENVIA ORDENES A PRESUPUESTO - Erp2390
   # 2019-03-04 JLF Creación
   function omEnviarOrdenesPresupuesto() {
      $llOk = $this->mxValEnviarOrdenesPresupuesto();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxEnviarOrdenesPresupuesto($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValEnviarOrdenesPresupuesto() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      $this->paData['CTIPENV'] = 'DENVPRE';
      $this->paData['DACTUAL'] = date('Y-m-d');
      return true;
   }

   protected function mxEnviarOrdenesPresupuesto($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdOrde) {
         $laData = array_merge($this->paData, ['CIDORDE' => $lcIdOrde]);
         $lcJson = json_encode($laData);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01MORD_9('$lcJson')";
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

   # ENVIA ORDENES HACIA VICE ADMNISTRATIVO - Erp2390
   # 2019-02-27 JLF Creación
   function omEnviarOrdenesViceAdm() {
      $llOk = $this->mxValEnviarOrdenesViceAdm();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxEnviarOrdenesViceAdm($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValEnviarOrdenesViceAdm() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      $this->paData['CTIPENV'] = 'DENVVIC';
      $this->paData['DACTUAL'] = date('Y-m-d');
      return true;
   }

   protected function mxEnviarOrdenesViceAdm($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdOrde) {
         $laData = array_merge($this->paData, ['CIDORDE' => $lcIdOrde]);
         $lcJson = json_encode($laData);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01MORD_9('$lcJson')";
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

   # RECEPCIONA ORDENES DE VICE ADMNISTRATIVO - Erp2390
   # 2019-02-27 JLF Creación
   function omRecibirOrdenesViceAdm() {
      $llOk = $this->mxValRecibirOrdenesViceAdm();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRecibirOrdenesViceAdm($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValRecibirOrdenesViceAdm() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      $this->paData['CTIPENV'] = 'DRECVIC';
      $this->paData['DACTUAL'] = date('Y-m-d');
      return true;
   }

   protected function mxRecibirOrdenesViceAdm($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdOrde) {
         $laData = array_merge($this->paData, ['CIDORDE' => $lcIdOrde]);
         $lcJson = json_encode($laData);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01MORD_9('$lcJson')";
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

   # REGRESA ORDENES DE VICE ADMNISTRATIVO - Erp2390
   # 2019-03-04 JLF Creación
   function omRegresarOrdenesViceAdm() {
      $llOk = $this->mxValRegresarOrdenesViceAdm();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRegresarOrdenesViceAdm($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValRegresarOrdenesViceAdm() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxRegresarOrdenesViceAdm($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdOrde) {
         $lcSql = "UPDATE E01MORD SET dEnvVic = NULL, cUsuCod = '$lcCodUsu', tModifi = NOW() WHERE cIdOrde = '$lcIdOrde'";
         $R1 = $p_oSql->omExec($lcSql);
         if ($R1 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
            return false;
         }
      }
      return true;
   }

   # -----------------------------------------------------
   # ENVIA COMPROBANTES DE PAGO A CONTABILIDAD PARA PAGO
   # 2019-02-27 JLF Creación
   # 2022-09-05 APR Modificación
   # -----------------------------------------------------
   function omEnviarComprobantesContabilidad() {
      $llOk = $this->mxValEnviarComprobantesContabilidad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxEnviarComprobantesContabilidad($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValEnviarComprobantesContabilidad() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      $this->paData['CTIPENV'] = 'DENVCON';
      $this->paData['DACTUAL'] = date('Y-m-d');
      return true;
   }

   protected function mxEnviarComprobantesContabilidad($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdComp) {
         $laData = array_merge($this->paData, ['CIDORDE' => $lcIdComp]);
         $lcJson = json_encode($laData);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01MORD_9('$lcJson')";
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

   # -------------------------------------------------
   # REGRESA COMPROBANTES DE CONTABILIDAD - Erp2390
   # 2019-03-04 JLF Creación
   # 2022-09-05 APR Modificación
   # -------------------------------------------------
   function omRegresarComprobantesContabilidad() {
      $llOk = $this->mxValRegresarComprobantesContabilidad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioLogistica($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRegresarComprobantesContabilidad($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValRegresarComprobantesContabilidad() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'ESTADO DE ENVÍO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxRegresarComprobantesContabilidad($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdComp) {
         $lcSql = "UPDATE E01MFAC SET dEnvCon = NULL, cUsuCod = '$lcCodUsu', tModifi = NOW() WHERE cIdComp = '$lcIdComp'";
         $R1 = $p_oSql->omExec($lcSql);
         if ($R1 == false) {
            $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
            return false;
         }
      }
      return true;
   }

   # BUSCA NOTAS  DE INGRESO PARA REVISAR - Erp2410
   # 2019-03-13 JLF Creación
   public function omBuscarNotaIngreso() {
      $llOk = $this->mxValBuscarNotaIngreso();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarNotaIngreso($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarNotaIngreso() {
      if (!isset($this->paData['CBUSNOT']) || strlen(trim($this->paData['CBUSNOT'])) > 30) {
         $this->pcError = 'BÚSQUEDA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 6) {
         $this->pcError = 'PERIODO DE BÚSQUEDA INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxBuscarNotaIngreso($p_oSql) {
      #TRAE NOTAS DE INGRESO
      $lcBusNot = $this->paData['CBUSNOT'];
      $lcBusNot = str_replace(' ', '%', trim($lcBusNot));
      $lcBusNot = str_replace('#', 'Ñ', trim($lcBusNot));
      $lcPeriod = $this->paData['CPERIOD'];
      $lcSql = "SELECT A.cNotIng, A.dFecha, TRIM(A.cGuiRem), A.cDescri, A.mObserv, A.cEstado, G.cDescri AS cDesEst, A.cIdKard, B.cTipMov, 
                       B.cNumMov, B.cNroRuc, E.cRazSoc, B.cCenCos, F.cDescri AS cDesCCo, C.cIdOrde, C.cCodAnt, A.cIdComp, D.cNroCom,
                       F.cCodAnt AS cCCoAnt, E.cCodAnt AS cCodPrv, D.cTipCom, H.cDescri AS cDesCom, D.cMoneda, I.cDescri AS cDesMon,
                       D.nTipCam, D.nMonto, D.nMonIgv, D.nInafec, D.nAdicio, A.cEstPro, J.cDescri AS cDesPro, B.cUsuCod, K.cNombre
                       AS cNomMod, TO_CHAR(B.tModifi, 'YYYY-MM-DD')
                FROM E01MNIN A
                   INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                   INNER JOIN E01MORD C ON C.cIdOrde = A.cIdOrde
                   INNER JOIN E01MFAC D ON D.cIdComp = A.cIdComp
                   INNER JOIN S01MPRV E ON E.cNroRuc = B.cNroRuc
                   INNER JOIN S01TCCO F ON F.cCenCos = B.cCenCos
                   LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '089' AND G.cCodigo = A.cEstado
                   LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '087' AND H.cCodigo = D.cTipCom
                   LEFT OUTER JOIN V_S01TTAB I ON I.cCodTab = '007' AND I.cCodigo = D.cMoneda
                   LEFT OUTER JOIN V_S01TTAB J ON J.cCodTab = '040' AND J.cCodigo = A.cEstPro
                   LEFT OUTER JOIN V_S01TUSU_1 K ON K.cCodUsu = B.cUsuCod
                   WHERE A.cEstado != 'X' ";
      if (substr($lcPeriod, 4, 2) != '00') {
         $lcSql .= "AND TO_CHAR(B.dFecha, 'YYYYMM') = '$lcPeriod' ";
      } else {
         $lcSql .= "AND TO_CHAR(B.dFecha, 'YYYY') = SUBSTRING('$lcPeriod', 1, 4) ";
      }
      if ($lcBusNot != '') {
         $lcSql .= "AND (B.cNumMov LIKE '%$lcBusNot%' OR C.cCodAnt LIKE '%$lcBusNot%' OR E.cNroRuc = '$lcBusNot' OR 
                         E.cCodAnt = '$lcBusNot' OR E.cRazSoc LIKE '%$lcBusNot%' OR D.cNroCom LIKE '%$lcBusNot')";
      }
      $lcSql .= " ORDER BY A.dFecha DESC, B.cNumMov DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON COINCIDENCIAS CON LOS PARÁMETROS SELECCIONADOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOTING' => $laFila[0], 'DFECHA'  => $laFila[1], 'CGUIREM' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'MOBSERV' => $laFila[4], 'CESTADO' => $laFila[5], 'CDESEST' => $laFila[6], 'CIDKARD' => $laFila[7],
                             'CTIPMOV' => $laFila[8], 'CNUMMOV' => $laFila[9], 'CNRORUC' => $laFila[10],'CRAZSOC' => $laFila[11],
                             'CCENCOS' => $laFila[12],'CDESCCO' => $laFila[13],'CIDORDE' => $laFila[14],'CCODANT' => $laFila[15],
                             'CIDCOMP' => $laFila[16],'CNROCOM' => $laFila[17],'CCCOANT' => $laFila[18],'CCODPRV' => $laFila[19],
                             'CTIPCOM' => $laFila[20],'CDESCOM' => $laFila[21],'CMONEDA' => $laFila[22],'CDESMON' => $laFila[23],
                             'NTIPCAM' => $laFila[24],'NMONTO'  => $laFila[25],'NMONIGV' => $laFila[26],'NINAFEC' => $laFila[27],
                             'NADICIO' => $laFila[28],'CESTPRO' => $laFila[29],'CDESPRO' => $laFila[30],'CUSUMOD' => $laFila[31],
                             'CNOMMOD' => $laFila[32],'TMODIFI' => $laFila[33]];
      }
      return true;
   }

   # BUSCA NOTAS  DE INGRESO (IB) BOTICA ALIVIARI- Erp2530
   # 2020-08-12 WZA Creación
   public function omBuscarNotaIngresoIB() {
      $llOk = $this->mxValBuscarNotaIngresoIB();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarNotaIngresoIB($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarNotaIngresoIB() {
      if (!isset($this->paData['CBUSNOT']) || strlen(trim($this->paData['CBUSNOT'])) > 30) {
         $this->pcError = 'BÚSQUEDA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 6) {
         $this->pcError = 'PERIODO DE BÚSQUEDA INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxBuscarNotaIngresoIB($p_oSql) {
      #TRAE NOTAS DE INGRESO
      $lcBusNot = $this->paData['CBUSNOT'];
      $lcBusNot = str_replace(' ', '%', trim($lcBusNot));
      $lcBusNot = str_replace('#', 'Ñ', trim($lcBusNot));
      $lcPeriod = $this->paData['CPERIOD'];
      $lcSql = "SELECT A.cIdAdqU, A.dFecAdq, TRIM(A.cGuiRem), C.cGlosa, A.cEstado, G.cDescri AS cDesEst, A.cIdKard, B.cTipMov, 
                       B.cNumMov, B.cNroRuc, D.cRazSoc, B.cCenCos, E.cDescri AS cDesCCo, A.cNroCom,
                       E.cCodAnt AS cCCoAnt, D.cCodAnt AS cCodPrv, A.cTipo, H.cDescri AS cDesCom,
                       C.nMonSol, C.nMonIgv, C.nInafec, B.cUsuCod, I.cNombre
                       AS cNomMod, TO_CHAR(B.tModifi, 'YYYY-MM-DD')
                FROM E05MADQ A
                INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                INNER JOIN E02DRCT C ON C.nSerial = A.nSerRef
                INNER JOIN S01MPRV D ON D.cNroRuc = C.cNroRuc
                INNER JOIN S01TCCO E ON E.cCenCos = B.cCenCos
                LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '089' AND G.cCodigo = A.cEstado
                LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '087' AND H.cCodigo = A.cTipo
                LEFT OUTER JOIN V_S01TUSU_1 I ON I.cCodUsu = B.cUsuCod ";
      if (substr($lcPeriod, 4, 2) != '00') {
         $lcSql .= "WHERE TO_CHAR(B.dFecha, 'YYYYMM') = '$lcPeriod' ";
      } else {
         $lcSql .= "WHERE TO_CHAR(B.dFecha, 'YYYY') = SUBSTRING('$lcPeriod', 1, 4) ";
      }
      if ($lcBusNot != '') {
         $lcSql .= "AND (B.cNumMov LIKE '%$lcBusNot%' OR D.cNroRuc = '$lcBusNot' OR 
                         D.cCodAnt = '$lcBusNot' OR D.cRazSoc LIKE '%$lcBusNot%' OR A.cNroCom LIKE '%$lcBusNot')";
      }
      $lcSql .= " ORDER BY A.dFecAdq DESC, B.cNumMov DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON COINCIDENCIAS CON LOS PARÁMETROS SELECCIONADOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOTING' => $laFila[0], 'DFECHA'  => $laFila[1], 'CGUIREM' => trim($laFila[2]) == ''? 'S/G': $laFila[2], 'CDESCRI' => $laFila[3],
                             'CESTADO' => $laFila[4], 'CDESEST' => $laFila[5], 'CIDKARD' => $laFila[6], 'CTIPMOV' => $laFila[7],
                             'CNUMMOV' => $laFila[8], 'CNRORUC' => $laFila[9], 'CRAZSOC' => $laFila[10],'CCENCOS' => $laFila[11],
                             'CDESCCO' => $laFila[12],'CNROCOM' => $laFila[13],'CCCOANT' => $laFila[14],'CCODPRV' => $laFila[15],
                             'CTIPCOM' => $laFila[16],'CDESCOM' => $laFila[17],'NMONTO'  => $laFila[18],'NMONIGV' => $laFila[19],
                             'NINAFEC' => $laFila[20],'CUSUMOD' => $laFila[21],'CNOMMOD' => $laFila[22],'TMODIFI' => $laFila[23]];
      }
      return true;
   }

   # CARGA DETALLE DE COMPROBANTE PARA REVISAR - Erp2410
   # 2019-03-13 JLF Creación
   public function omVerDetalleComprobante() {
      $llOk = $this->mxValVerDetalleComprobante();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxVerDetalleComprobante($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValVerDetalleComprobante() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCCOUSU']) || strlen($this->paData['CCCOUSU']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen($this->paData['CIDCOMP']) != 8) {
         $this->pcError = 'COMPROBANTE INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxVerDetalleComprobante($p_oSql) {
      #TRAE NOTAS DE INGRESO
      $lcIdComp = $this->paData['CIDCOMP'];
      $lcSql = "SELECT A.nSerial, A.cIdComp, A.cCodArt, B.cDescri AS cDesArt, B.cUnidad, C.cDescri AS cDesUni, A.nCantid, 
                       A.nMonto, A.nMonIgv FROM E01DFAC A
                  INNER JOIN E01MART B ON B.cCodArt = A.cCodArt 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                  WHERE cIdComp = '$lcIdComp' AND A.cEstado = 'A'
                  ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR AL CARGAR DETALLE DEL COMPROBANTE DE PAGO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CIDCOMP' => $laFila[1], 'CCODART' => $laFila[2], 'CDESART' => $laFila[3],
                             'CUNIDAD' => $laFila[4], 'CDESUNI' => $laFila[5], 'NCANTID' => $laFila[6], 'NMONTO' => $laFila[7],
                             'NMONIGV' => $laFila[8]];
      }
      return true;
   }

   # CARGA DETALLE DE ADQUISICION PARA REVISAR - Erp2530
   # 2021-08-12 WZA Creación
   public function omVerDetalleComprobanteIB() {
      $llOk = $this->mxValVerDetalleComprobanteIB();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxVerDetalleComprobanteIB($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValVerDetalleComprobanteIB() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCCOUSU']) || strlen($this->paData['CCCOUSU']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CNOTING']) || strlen($this->paData['CNOTING']) != 8) {
         $this->pcError = 'COMPROBANTE INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxVerDetalleComprobanteIB($p_oSql) {
      #TRAE NOTAS DE INGRESO
      $lcIdComp = $this->paData['CNOTING'];
      $lcSql = "SELECT A.cIdAdqu, A.cCodArt, B.cDescri AS cDesArt, B.cUnidad, C.cDescri AS cDesUni, A.nCantid, 
                       A.nMonto, A.nMonIgv, A.nSerial FROM E05DADQ A
                  INNER JOIN E01MART B ON B.cCodArt = A.cCodArt 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '074' AND C.cCodigo = B.cUnidad
                  WHERE A.cIdAdqu = '$lcIdComp' AND A.cEstado = 'A'
                  ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR AL CARGAR DETALLE DEL COMPROBANTE DE PAGO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDCOMP' => $laFila[0], 'CCODART' => $laFila[1], 'CDESART' => $laFila[2],
                             'CUNIDAD' => $laFila[3], 'CDESUNI' => $laFila[4], 'NCANTID' => $laFila[5],
                             'NMONTO' => $laFila[6],  'NMONIGV' => $laFila[7], 'NSERIAL' => $laFila[8]];
      }
      return true;
   }

   # REVISA DETALLE DE COMPROBANTE PARA CARGAR - Erp2530
   # 2021-08-12 WZA Creación
   public function omRevisarDetalleComprobanteIB() {
      $llOk = $this->mxValRevisarDetalleComprobanteIB();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRevisarDetalleComprobanteIB($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValRevisarDetalleComprobanteIB() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen($this->paData['CIDCOMP']) != 8) {
         $this->pcError = 'COMPROBANTE INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCODART']) || strlen($this->paData['CCODART']) != 8) {
         $this->pcError = 'CODIGO ARTICULO INVALIDO';
         return false;
      }
      return true;
   }

   public function mxRevisarDetalleComprobanteIB($p_oSql) {
        $lcIdComp = $this->paData['CIDCOMP'];
        $lcCodArt = $this->paData['CCODART'];
        $lcSql = "SELECT cCodArt FROM E05DADQ WHERE cIdAdqu = '$lcIdComp' AND cCodArt = '$lcCodArt' AND cEstado = 'A'";
        $RS = $p_oSql->omExec($lcSql);
        if (!$RS) {
           $this->pcError = 'ERROR DE EJECUCIÓN EN BASE DE DATOS';
           return false;
        }
        $laFila = $p_oSql->fetch($RS);
        $this->paData = ['CINFORM' => ($laFila == null)? 'ARTÍCULO NO PERTENECE AL COMPROBANTE DE PAGO' : 'ARTÍCULO PERTENECE AL OCMPROBANTE DE PAGO',
                         'CESTINF' => ($laFila == null)? false : true];
        return true;
     }

     # REVISA DETALLE DE COMPROBANTE PARA CARGAR - Erp2410
     # 2019-03-13 JLF Creación
     public function omRevisarDetalleComprobante() {
      $llOk = $this->mxValRevisarDetalleComprobante();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRevisarDetalleComprobante($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValRevisarDetalleComprobante() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen($this->paData['CIDCOMP']) != 8) {
         $this->pcError = 'COMPROBANTE INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCODART']) || strlen($this->paData['CCODART']) != 8) {
         $this->pcError = 'CODIGO ARTICULO INVALIDO';
         return false;
      }
      return true;
   }

   public function mxRevisarDetalleComprobante($p_oSql) {
      $lcIdComp = $this->paData['CIDCOMP'];
      $lcCodArt = $this->paData['CCODART'];
      $lcSql = "SELECT cCodArt FROM E01DFAC WHERE cIdComp = '$lcIdComp' AND cCodArt = '$lcCodArt' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCIÓN EN BASE DE DATOS';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = ['CINFORM' => ($laFila == null)? 'ARTÍCULO NO PERTENECE AL COMPROBANTE DE PAGO' : 'ARTÍCULO PERTENECE AL OCMPROBANTE DE PAGO',
                       'CESTINF' => ($laFila == null)? false : true];
      return true;
   }

   # REVISA DETALLE DE COMPROBANTE PARA CARGAR - Erp2410
   # 2019-03-13 JLF Creación
   public function omActualizarNotaIngreso() {
      $llOk = $this->mxValActualizarNotaIngreso();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxActualizarNotaIngreso($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValActualizarNotaIngreso() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCCOUSU']) || strlen($this->paData['CCCOUSU']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['DFECHA']) || !$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = 'FECHA DE NOTA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen($this->paData['CIDCOMP']) != 8) {
         $this->pcError = 'COMPROBANTE INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CNOTING']) || strlen($this->paData['CNOTING']) != 8) {
         $this->pcError = 'NOTA DE INGRESO INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CGUIREM']) || strlen(trim($this->paData['CGUIREM'])) == 0 || strlen(trim($this->paData['CGUIREM'])) > 20) {
         $this->pcError = 'GUÍA DE REMISIÓN INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['NMONTO']) || $this->paData['NMONTO'] < 0) {
         $this->pcError = 'MONTO TOTAL INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NMONIGV']) || $this->paData['NMONIGV'] < 0) {
         $this->pcError = 'MONTO IGV INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NINAFEC']) || empty($this->paData['NINAFEC'])) {
         $this->pcError = 'MONTO INAFECTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NTIPCAM']) || empty($this->paData['NTIPCAM']) || $this->paData['NTIPCAM'] < 0) {
         $this->pcError = 'TIPO DE CAMBIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'DETALLE DE NOTA INVÁLIDA';
         return false;
      }
      return true;
   }

   protected function mxActualizarNotaIngreso($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MNIN_2('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $laFila = json_decode($laFila[0], true);
      $this->paData['CNOTING'] = $laFila[0]['CNOTING'];
      return true;
   }

   # REVISA DETALLE DE COMPROBANTE PARA CARGAR - Erp2530
   # 2021-08-19 WZA Creación
   public function omActualizarIngresoBotica() {
      $llOk = $this->mxValActualizarIngresoBotica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioContabilidad($loSql);
      if (!$llOk1 && !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxActualizarIngresoBotica($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValActualizarIngresoBotica() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCCOUSU']) || strlen($this->paData['CCCOUSU']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['DFECHA']) || !$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = 'FECHA DE NOTA INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen($this->paData['CIDCOMP']) != 8) {
         $this->pcError = 'COMPROBANTE INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CNOTING']) || strlen($this->paData['CNOTING']) != 8) {
         $this->pcError = 'NOTA DE INGRESO INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CGUIREM']) || strlen(trim($this->paData['CGUIREM'])) == 0 || strlen(trim($this->paData['CGUIREM'])) > 20) {
         $this->pcError = 'GUÍA DE REMISIÓN INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['NMONTO']) || $this->paData['NMONTO'] < 0) {
         $this->pcError = 'MONTO TOTAL INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NMONIGV']) || $this->paData['NMONIGV'] < 0) {
         $this->pcError = 'MONTO IGV INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NINAFEC']) || empty($this->paData['NINAFEC'])) {
         $this->pcError = 'MONTO INAFECTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'DETALLE DE NOTA INVÁLIDA';
         return false;
      }
      return true;
   }

   protected function mxActualizarIngresoBotica($p_oSql) {
      #PERIODO SISTEMA
      $lcSql = "SELECT TRIM(cPeriod) FROM S01TPER WHERE cTipo = 'S1'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NO HAY PERIODO DE SISTEMA DEFINIDO';
         return false;
      }
      $laTmp = $p_oSql->fetch($RS);
      $lcPeriod = $laTmp[0];
      #VALIDA CODIGO USUARIO
      $lcSql = "SELECT cNivel FROM S01TUSU WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'USUARIO NO EXISTE O NO ESTA INACTIVO';
         return false;
      }
      $laTmp = $p_oSql->fetch($RS);
      if (!in_array($laTmp[0], array('CO','AL','CA'))) {
         $this->pcError = 'NIVEL DE USUARIO INVALIDO';
         return false;
      }
      $lcNivel = $laTmp[0];
      #VALIDAD INGRESO BOTICA
      $lcSql = "SELECT cIdKard, dFecAdq FROM E05MADQ WHERE cIdAdqu = '{$this->paData['CNOTING']}' AND cEstado IN ('G','P')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NOTA DE INGRESO(IB) INVALIDA';
         return false;
      }
      $laTmp = $p_oSql->fetch($RS);
      if ($laTmp[0] == '00000000'){
         $this->pcError = 'NOTA DE INGRESO(IB) INVALIDA';
         return false;
      }
      $lcIdKard = $laTmp[0];
      $lcFecIng = $laTmp[1];
      #VALIDA KARDEX
      $lcSql = "SELECT cCenCos FROM E03MKAR WHERE cIdKard = '$lcIdKard' AND cTipMov = 'IB' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'KARDEX INVALIDO';
         return false;
      }
      $laTmp = $p_oSql->fetch($RS);
      if ($laTmp[0] == '000'){
         $this->pcError = 'KARDEX INVALIDO';
         return false;
      }
      #VALIDA FECHA
      $lcNow = date('Y-m-d');
      if ($lcNivel != 'CA' && $lcFecIng != $this->paData['DFECHA'] && substr($this->paData['DFECHA'], 0,4) != TRIM($lcPeriod)){
         $this->pcError = 'PERIODO DE CAMBIO INVALIDO';
         return false;
      } else if($this->paData['DFECHA'] > $lcNow){
         $this->pcError = 'FECHA DE INGRESO MAYOR AL DIA DE HOY';
         return false;
      } else if($lcNivel != 'CA' && $lcFecIng != $this->paData['DFECHA'] && substr($this->paData['DFECHA'], 0,7) != substr($lcFecIng, 0,7)){
         $this->pcError = 'FECHA DE INGRESO NO PUEDE SER DE OTRO MES';
         return false;
      } else if ($lcNivel != 'CA' && substr($this->paData['DFECHA'], 0,7) != substr($lcNow, 0,7)){
         $this->pcError = 'NO SE PUEDE MODIFICAR INGRESO DE OTRO MES';
         return false;
      }
      if (strlen(TRIM($this->paData['CGUIREM'])) == 0){
         $this->pcError = 'GUIA DE REMISION INVALIDA';
         return false;
      }
      if ($this->paData['NMONTO'] < 0){
         $this->pcError = 'MONTO INVALIDO';
         return false;
      }
      if ($this->paData['NMONIGV'] < 0){
         $this->pcError = 'MONTO IGV INVALIDO';
         return false;
      }
      #TRAERVALIDA DETALLE
      if (count($this->paData['MDATOS']) == 0){
         $this->pcError = 'DETALLE INVALIDO';
         return false;
      }
      $lcSql = "DELETE FROM E03DKAR WHERE cIdKard = '$lcIdKard'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL MODIFICAR DETALLE KARDEX';
         return false;
      }
      $lnMonIgv = 0;
      $lnSubTot = 0;
      foreach ($this->paData['MDATOS'] as $laFila){
          $lcSql = "SELECT cCodArt FROM E01MART WHERE cCodArt = '{$laFila['CCODART']}'";
          $RS = $p_oSql->omExec($lcSql);
          if (!$RS) {
             $this->pcError = 'ARTICULO NO EXISTE';
             return false;
          }
          if ($laFila['NSERIAL'] < -1 || $laFila['NSERIAL'] == 0){
             $this->pcError = 'IDENTIFICADOR DE DETALLE INVALIDO';
             return false;
          } else if ($laFila['NCANTID'] < 0.00){
             $this->pcError = 'CANTIDAD DE INGRESO DEL ARTICULO INVALIDA';
             return false;
          } else if ($laFila['NMONTO'] < 0.00){
             $this->pcError = 'MONTO DEL ARTICULO INVALIDO';
             return false;
          } else if ($laFila['NMONIGV'] < 0.00){
             $this->pcError = 'MONTO IGV DEL ARTICULO INVALIDO';
             return false;
          }
          $lcEstado = 'A';
          if ($laFila['NSERIAL'] == -1 && $laFila['NCANTID'] == 0){
             continue;
          } else if ($laFila['NSERIAL'] != 1 && $laFila['NCANTID'] == 0){
             $lcEstado = 'X';
             $lcSql = "UPDATE E05DADQ SET cEstado = 'X', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE nSerial =  '{$laFila['NSERIAL']}'";
             $RS = $p_oSql->omExec($lcSql);
             if (!$RS) {
                $this->pcError = 'ERROR AL ACTUALIZAR DETALLE DE ADQUISICION';
                return false;
             }
          } else if ($laFila['NSERIAL'] == -1){
             $lcSql = "INSERT INTO E05DADQ (cIdAdqu, cCodArt, cEstado, nCantid, nMonto, nMonIgv, dFecVen, cUsuCod, tModifi) VALUES ('{$this->paData['CNOTING']}', '{$laFila['CCODART']}', $lcEstado, {$laFila['NCANTID']}, '{$laFila['NMONTO']}', 0.00, '1999-01-01','{$this->paData['CUSUCOD']}', NOW())";
             $RS = $p_oSql->omExec($lcSql);
             if (!$RS) {
                $this->pcError = 'ERROR AL NUEVO DETALLE';
                return false;
             }
          } else {
             $lcSql = "UPDATE E05DADQ SET nCantid = {$laFila['NCANTID']}, nMonto = {$laFila['NMONTO']}, nMonIgv = {$laFila['NMONIGV']}, cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE nSerial =  '{$laFila['NSERIAL']}'";
             $RS = $p_oSql->omExec($lcSql);
             if (!$RS) {
                $this->pcError = 'ERROR AL ACTUALIZAR DETALLE DE ADQUISICION';
                return false;
             }
          }
          $lcSql = "INSERT INTO E03DKAR (cIdKard, cCodArt, cEstado, nCantid, nPastil, nCosto, nPreTot, cUsuCod) VALUES ('$lcIdKard', '{$laFila['CCODART']}', '$lcEstado', {$laFila['NCANTID']}, 0, {$laFila['NMONTO']}/{$laFila['NCANTID']}, {$laFila['NMONTO']}, '{$this->paData['CUSUCOD']}')";
          $RS = $p_oSql->omExec($lcSql);
          if (!$RS) {
             $this->pcError = 'ERROR AL INSERTAR DETALLE DE KARDEX';
             return false;
          }
          $lcSql = "SELECT cCodAlm FROM E03PALM WHERE cCodAlm = '001' AND cCodArt = '{$laFila['CCODART']}'";
          $RS = $p_oSql->omExec($lcSql);
          if (!$RS || $p_oSql->pnNumRow == 0) {
             $lcSql ="INSERT INTO E03PALM (cCodAlm, cCodArt, cEstado, nStock, nPastil, nPasBas, cPasBas, cUsuCod) VALUES ('001', '{$laFila['CCODART']}', 'A', 0, 0, 0, 'XXX', '{$this->paData['CUSUCOD']}')";
             $RS = $p_oSql->omExec($lcSql);
             if (!$RS) {
                $this->pcError = 'ERROR AL INSERTAR NUEVO ARTICULO A ALMACEN';
                return false;
             }
          }
          $lnMonIgv = $lnMonIgv + $laFila['NMONIGV'];
          $lnSubTot = $lnSubTot + $laFila['NMONTO'];
          $laData = ['CCODALM' => '092', 'CCODART' => $laFila['CCODART']];
          $lcParam = json_encode($laData);
          $lcSql = "SELECT P_E03PALM_1('$lcParam')";
          $RS = $p_oSql->omExec($lcSql);
          $laTmp = $p_oSql->fetch($RS);
          $laTmp[0] = (!$laTmp[0]) ? '[{"ERROR":""ERROR EN JSON DE RETORNO DE FUNCION P_E01MALM_1"}]' : $laTmp[0];
          $this->paDatos = json_decode($laTmp[0], true);
          if (!empty($this->paDatos[0]['ERROR'])) {
             $this->pcError = $this->paDatos[0]['ERROR'];
             return false;
          }
      }
      $lnMonIgt = trim($this->paData['NMONIGV']);
      $llOk = ($lnMonIgv != $lnMonIgt)? true: false;
      if ($llOk){
         $this->pcError = "TOTAL IGV DE COMPROBANTE ES DIFERENTE AL TOTAL IGV DEL DETALLE";
         return false;
      }
      $lcSql = "UPDATE E05MADQ SET dFecAdq = '{$this->paData['DFECHA']}', cGuiRem = '{$this->paData['CGUIREM']}', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdAdqu =  '{$this->paData['CNOTING']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS){
         $this->pcError = "ERROR AL ACTUALIZAR MAESTRO DE ADQUISICION";
         return false;
      }
      $lcSql = "UPDATE E03MKAR SET dFecha = '{$this->paData['DFECHA']}', cEstPro = 'A', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cIdKard = '$lcIdKard'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS){
         $this->pcError = "ERROR AL ACTUALIZAR MAESTRO DE ADQUISICION";
         return false;
      }
      return true;
   }

   # CARGA DETALLE DE REQUERIMIENTO DESDE UN ARCHIVO CSV - Erp1110
   # 2019-05-09 JLF Creación
   public function omCargarArchivoDetalle() {
      $llOk = $this->mxValCargarArchivoDetalle();
      if (!$llOk) {
         return False;
      }
      $llOk = $this->mxSubirArchivoTemporalCSV();
      if (!$llOk) {
         return False;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarArchivoDetalle($loSql);
      if (!$llOk) {
         return False;
      }
      return $llOk;
   }

   protected function mxValCargarArchivoDetalle() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      }
      return True;
   }

   protected function mxSubirArchivoTemporalCSV() {
      $laErrFil = array(
         0 => 'ARCHIVO SUBIDO CON EXITO',
         1 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO EL SERVIDOR',
         2 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO POR USUARIO',
         3 => 'EL ARCHIVO FUE PARCIALMENTE CARGADO',
         4 => 'EL ARCHIVO NO FUE CARGADO',
         6 => 'FALTA UNA CARPETA TEMPORAL',
         7 => 'ERROR AL ESCRIBIR EL ARCHIVO EN EL DISCO',
         8 => 'UNA EXTENSIÓN DE PHP DETUVO LA CARGA DEL ARCHIVO',
      );
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      if ($this->poFile['error'] != 0 && $this->poFile['error'] != 4) {
         $this->pcError = $laErrFil[$this->poFile['error']];
         return false;
      } elseif ($this->poFile['error'] == 4) {
         return true;
      }
      $llOk = fxSubirCSV($this->poFile, 'FILES', $lcUsuCod.$lcCenCos);
      if(!$llOk) {
         $this->pcError = "HA OCURRIDO UN ERROR AL SUBIR ARCHIVO ".$this->poFile['name'];
         return false;
      }
      return true;
   }

   protected function mxCargarArchivoDetalle($p_oSql) {
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      $this->paDatos = null;
      $loArchiv = fopen("FILES/".$lcUsuCod.$lcCenCos.".csv", "r");
      while (($laTmp = fgetcsv($loArchiv, 4, ';')) == true) {
         $laTmp = array_map("utf8_encode", $laTmp);
         if (!isset($laTmp[0])) {
            continue;
         }
         $lcCodArt = ($laTmp[0] == '0')? '00000000' : $laTmp[0];
         $lcSql = "SELECT A.cCodArt, A.cDescri, A.cUnidad, B.cDescri AS cDesUni FROM E01MART A 
                     LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '074' AND B.cCodigo = A.cUnidad
                     WHERE A.cCodArt = '$lcCodArt'";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = 'ERROR DE EJECUCIÓN EN BASE DE DATOS';
            continue;
         }
         $laFila = $p_oSql->fetch($RS);
         $this->paDatos[] = ['CCODART' => $lcCodArt, 'CDESART' => (!isset($laFila[1]) || $laFila[0] == '00000000')? substr($laTmp[1], 0, 200) : $laFila[1],
                             'CDESDET' => $laTmp[1], 'NCANTID' => $laTmp[2], 'NPREREF' => $laTmp[3], 'CUNIDAD' => (!isset($laFila[4]))? 'UNIDAD' : $laFila[4],
                             'NSTOTAL' => $laTmp[2] * $laTmp[3]];
      }
      #Cerramos el archivo
      fclose($loArchiv);
      return True;
   }

   public function omInitRegistroReciboProvisional() {
      $llOk = $this->mxValInitRegistroReciboProvisional();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRegistroReciboProvisional($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitRegistroReciboProvisional() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxInitRegistroReciboProvisional($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      #TRAER MONEDAS QUE PUEDEN EMITIR CHEQUE
      $lcSql = "SELECT TRIM(cCodigo), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '007' AND cCodigo IN ('1','2')";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION SQL [MONEDAS]";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE MONEDAS DEFINIDOS [S01TTAB.007]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
      }
      #TRAER PERIODO ACTIVO
      $lcSql = "SELECT cPeriod FROM S01TPER WHERE cTipo = 'S0'";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [PERIODOS]";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY UN PERIODO ACTIVO DEFINIDO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lcPeriod = $laFila[0];
      #TRAER ACTIVIDADES DE CENTRO DE COSTO
      $lcSql = "SELECT A.cIdActi, A.cDesCri || ' - ' || B.cDescri FROM P02MACT A INNER JOIN V_S01TTAB B ON B.cCodTab = '052' AND B.cCodigo = A.cTipAct
                WHERE A.cCenCos IN ('$lcCenCos') AND A.cPeriod = '$lcPeriod' AND A.cEstado IN ('A','D') AND A.cTipAct IN ('A','P','S','T','R','V','Q') ORDER BY A.cIdActi";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [ACTIVIDADES]";
         return false;
      }# elseif ($p_oSql->pnNumRow == 0) {
      #   $this->pcError = "NO HAY ACTIVIDADES DEL PLAN OPERATIVO DEFINIDAS EN SU CENTRO DE COSTO";
      #   return false;
      #}
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paActivi[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      $this->paActivi[] = ['CIDACTI' => '00000000', 'CDESCRI' => 'SIN ACTIVIDAD DEFINIDA'];
      #TRAER CABECERA DE REQUERIMIENTOS DE RECIBO PROVISIONAL
      $lcSql = "SELECT TRIM(cCenCos), cDesCCo, cIdRequ, cDesReq, mObserv, tCotiza, cEstado, cTipo, cDesTip, cMoneda, cComDir, cDestin, 
                       cIdActi, cNroDoc, cDesEst, tGenera, dIniEve, dFinEve, cUsuEnc, cNomEnc, nMonto FROM V_E01MREQ_1
                WHERE cCenCos = '$lcCenCos' AND cEstado IN ('R','H') AND cTipo = 'E' ORDER BY cIdRequ DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [REQUERIMIENTOS]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CCENCOS' => $laFila[0], 'CDESCCO' => $laFila[1], 'CIDREQU' => $laFila[2], 'CDESCRI' => $laFila[3],
                              'MOBSANT' => $laFila[4], 'TCOTIZA' => $laFila[5], 'CESTADO' => $laFila[6], 'CTIPO'   => $laFila[7],
                              'CDESTIP' => $laFila[8], 'CMONEDA' => $laFila[9], 'CCOMDIR' => $laFila[10],'CDESTIN' => $laFila[11],
                              'CIDACTI' => $laFila[12],'CNRODOC' => $laFila[13],'CDESEST' => $laFila[14],'TGENERA' => $laFila[15],
                              'DINIEVE' => $laFila[16],'DFINEVE' => $laFila[17],'CUSUENC' => $laFila[18],'CNOMENC' => $laFila[19],
                              'NMONTO'  => $laFila[20],'MOBSERV' => ''];
      }
      return true;
   }

   public function omGrabarReciboProvisional() {
      $llOk = $this->mxValGrabarReciboProvisional();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarReciboProvisional($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarReciboProvisional() {
      $loDate = new CDate();
      if (!isset($this->paData['CIDREQU']) || strlen(trim($this->paData['CIDREQU'])) != 8) {
         $this->pcError = "REQUERIMIENTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || empty(strlen($this->paData['CDESCRI'])) || strlen(trim($this->paData['CDESCRI'])) > 200) {
         $this->pcError = "DESCRIPCION INVALIDA";
         return false;
      } elseif (!isset($this->paData['CMONEDA']) || strlen(trim($this->paData['CMONEDA'])) != 1) {
         $this->pcError = "MONEDA INVALIDA";
         return false;
      } elseif (!isset($this->paData['DINIEVE']) || strlen(trim($this->paData['DINIEVE'])) != 10 || !$loDate->valDate($this->paData['DINIEVE'])) {
         $this->pcError = "FECHA DE INICIO DE ACTIVIDAD INVALIDA";
         return false;
      } elseif (!isset($this->paData['DFINEVE']) || strlen(trim($this->paData['DFINEVE'])) != 10 || !$loDate->valDate($this->paData['DFINEVE'])) {
         $this->pcError = "FECHA DE FINALIZACIÓN DE ACTIVIDAD INVALIDA";
         return false;
      } elseif (!isset($this->paData['CIDACTI']) || strlen(trim($this->paData['CIDACTI'])) != 8) {
         $this->pcError = "ACTIVIDAD INVALIDA";
         return false;
      } elseif (!isset($this->paData['NMONTO']) || empty(trim($this->paData['NMONTO'])) || strlen(trim($this->paData['NMONTO'])) > 12) {
         $this->pcError = "MONTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CENVIO']) || strlen(trim($this->paData['CENVIO'])) != 1) {
         $this->pcError = "TIPO DE ENVIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || strlen(trim($this->paData['CCODUSU'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      $this->paData['CDESCRI'] = mb_strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarReciboProvisional($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_12('$lcJson')";
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

   public function omInitRegistroEnvioMensajeria() {
      $llOk = $this->mxValInitRegistroEnvioMensajeria();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRegistroEnvioMensajeria($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValInitRegistroEnvioMensajeria() {
      if (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      return true;
   }

   protected function mxInitRegistroEnvioMensajeria($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      #TRAER MONEDAS QUE PUEDEN EMITIR CHEQUE
      $lcSql = "SELECT TRIM(cCodigo), cDescri, cDesCor FROM V_S01TTAB WHERE cCodTab = '007' AND cCodigo IN ('1','2')";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION SQL [MONEDAS]";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE MONEDAS DEFINIDOS [S01TTAB.007]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paMoneda[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCOR' => $laFila[2]];
      }
      #TRAER CABECERA DE REQUERIMIENTOS DE RECIBO PROVISIONAL
      $lcSql = "SELECT TRIM(cCenCos), cDesCCo, cIdRequ, cDesReq, mObserv, tCotiza, cEstado, cTipo, cDesTip, cMoneda, cComDir, cDestin, 
                       cIdActi, cNroDoc, cDesEst, tGenera, dIniEve, dFinEve, cUsuEnc, cNomEnc, nMonto FROM V_E01MREQ_1
                WHERE cCenCos = '$lcCenCos' AND cEstado IN ('R','H') AND cTipo = 'M' ORDER BY cIdRequ DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!($RS)) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS [REQUERIMIENTOS]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CCENCOS' => $laFila[0], 'CDESCCO' => $laFila[1], 'CIDREQU' => $laFila[2], 'CDESCRI' => $laFila[3],
                              'MOBSANT' => $laFila[4], 'TCOTIZA' => $laFila[5], 'CESTADO' => $laFila[6], 'CTIPO'   => $laFila[7],
                              'CDESTIP' => $laFila[8], 'CMONEDA' => $laFila[9], 'CCOMDIR' => $laFila[10],'CDESTIN' => $laFila[11],
                              'CIDACTI' => $laFila[12],'CNRODOC' => $laFila[13],'CDESEST' => $laFila[14],'TGENERA' => $laFila[15],
                              'DINIEVE' => $laFila[16],'DFINEVE' => $laFila[17],'CUSUENC' => $laFila[18],'CNOMENC' => $laFila[19],
                              'NMONTO'  => $laFila[20],'MOBSERV' => ''];
      }
      return true;
   }

   public function omGrabarEnvioMensajeria() {
      $llOk = $this->mxValGrabarEnvioMensajeria();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarEnvioMensajeria($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarEnvioMensajeria() {
      if (!isset($this->paData['CIDREQU']) || strlen(trim($this->paData['CIDREQU'])) != 8) {
         $this->pcError = "REQUERIMIENTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || empty(strlen($this->paData['CDESCRI'])) || strlen(trim($this->paData['CDESCRI'])) > 200) {
         $this->pcError = "DESCRIPCION INVALIDA";
         return false;
      } elseif (!isset($this->paData['CMONEDA']) || strlen(trim($this->paData['CMONEDA'])) != 1) {
         $this->pcError = "MONEDA INVALIDA";
         return false;
      } elseif (!isset($this->paData['CCCODES']) || strlen(trim($this->paData['CCCODES'])) != 3) {
         $this->pcError = "CENTRO DE COSTO DE DESTINO INVALIDO";
         return false;
      } elseif (!isset($this->paData['NMONTO']) || empty(trim($this->paData['NMONTO'])) || strlen(trim($this->paData['NMONTO'])) > 12) {
         $this->pcError = "MONTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CENVIO']) || strlen(trim($this->paData['CENVIO'])) != 1) {
         $this->pcError = "TIPO DE ENVIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || strlen(trim($this->paData['CCODUSU'])) != 4) {
         $this->pcError = "CODIGO DE USUARIO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVALIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }
      $this->paData['CDESCRI'] = mb_strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarEnvioMensajeria($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_15('$lcJson')";
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

   public function omGrabarNuevaCotizacion() {
      $llOk = $this->mxValGrabarNuevaCotizacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarNuevaCotizacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxSubirArchivoProveedorCotizacion();
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValGrabarNuevaCotizacion() {
      if (!isset($this->paData['CIDCOTI']) || strlen(trim($this->paData['CIDCOTI'])) != 8) {
         $this->pcError = "ID DE COTIZACIÓN INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['NTIEENT']) || strlen(trim($this->paData['NTIEENT'])) < 1 || strlen(trim($this->paData['NTIEENT'])) > 3) {
         $this->pcError = "TIEMPO DE ENTREGA INVALIDO";
         return false;
      } elseif (!isset($this->paData['CDETENT']) || strlen(trim($this->paData['CDETENT'])) < 24 || strlen(trim($this->paData['CDETENT'])) > 26) {
         $this->pcError = "DETALLE DE ENTREGA (TIEMPO) INVALIDO";
         return false;
      } elseif (!isset($this->paData['CNROCEL']) || strlen(trim($this->paData['CNROCEL'])) < 6 || strlen(trim($this->paData['CNROCEL'])) > 12) {
         $this->pcError = "CELULAR INVALIDO";
         return false;
      } elseif (!isset($this->paData['CEMAIL']) || strlen(trim($this->paData['CEMAIL'])) < 7 || strlen(trim($this->paData['CEMAIL'])) > 90) {
         $this->pcError = "EMAIL INVALIDO";
         return false;
      } elseif (!isset($this->paData['NTIEVAL']) || strlen(trim($this->paData['NTIEVAL'])) < 1 || strlen(trim($this->paData['NTIEVAL'])) > 2) {
         $this->pcError = "TIEMPO DE VALIDEZ DE COTIZACIÓN INVALIDO";
         return false;
      } elseif (!isset($this->paData['CTIEVAL']) || strlen(trim($this->paData['CTIEVAL'])) < 28 || strlen(trim($this->paData['CTIEVAL'])) > 29) {
         $this->pcError = "DETALLE DE VALIDEZ DE COTIZACIÓN (TIEMPO) INVALIDO";
         return false;
      } elseif (!isset($this->paData['CTIPPRE']) || strlen(trim($this->paData['CTIPPRE'])) != 2) {
         $this->pcError = "TIPO DE PRECIO SELECCIONADO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CTIPFPA']) || strlen(trim($this->paData['CTIPFPA'])) != 2) {
         $this->pcError = "FORMA DE PAGO INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CNRORUC']) || strlen(trim($this->paData['CNRORUC'])) != 11) {
         $this->pcError = "RUC INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['MOBSERV']) || strlen(trim($this->paData['MOBSERV'])) == 0) {
         $this->pcError = "OBSERVACIÓN INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = "CENTRO DE COSTO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['MDATOS']) || !isset($this->paDatos) || count($this->paDatos) == 0) {
         $this->pcError = "DETALLE DE LA COTIZACIÓN INVÁLIDO";
         return false;
      }
      $this->paData['MOBSERV'] = mb_strtoupper($this->paData['MOBSERV']);
      return true;
   }

   protected function mxGrabarNuevaCotizacion($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01PCOT_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR": "ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $this->paData = ['CCODIGO' => $this->paDatos[0]['CCODIGO']];
      return true;
   }

   # CARGA ACTIVIDADES PENDIENTES DE ATENCION DE PERIODO ANTERIOR
   # 2018-11-26 JLF - Creación
   public function omCargarActividadesPendientesAtencion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarActividadesPendientesAtencion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarActividadesPendientesAtencion($p_oSql) {
      #TRAER ACTIVIDADES DE UN CENTRO DE COSTO EN UN PERIODO
      $lcCenCos = $this->paData['CCENCOS'];
      $lcSql = "SELECT cPeriod FROM S01TPER WHERE cTipo = 'S0'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY UN PERIODO ACTIVO DEFINIDO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lcPeriod = $laFila[0];
      $lcSql = "SELECT A.cIdActi, A.cDesCri || ' - ' || B.cDescri FROM P02MACT A INNER JOIN V_S01TTAB B ON B.cCodTab = '052' AND B.cCodigo = A.cTipAct
                WHERE A.cCenCos IN ('$lcCenCos') AND A.cPeriod = '$lcPeriod' AND A.cEstado IN ('D') AND A.cTipAct IN ('N') ORDER BY A.cIdActi";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY DEFINIDAS ACTIVIDADES PENDIENTES DE ATENCION";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paActivi[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   #GRABA INGRESO DE MATERIAL CON COMPROBANTE DE PAGO
   # 2020-02-27 JLF - Creación
   public function omGrabarIngresoMaterial() {
      $llOk = $this->mxValParamGrabarIngresoMaterial();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarIngresoMaterial($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarIngresoMaterial() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen(trim($this->paData['CNIVEL'])) != 2) {
         $this->pcError = 'NIVEL DE USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDORDE']) || strlen(trim($this->paData['CIDORDE'])) != 8) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CTIPCOM']) || strlen(trim($this->paData['CTIPCOM'])) != 2) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CNROCOM']) || strlen(trim($this->paData['CNROCOM'])) > 20 || empty($this->paData['CNROCOM'])) {
         $this->pcError = 'NRO DE COMPROBANTE INVALIDO';
         return false;
      } elseif (!isset($this->paData['CMONEDA']) || strlen(trim($this->paData['CMONEDA'])) != 1) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['DFECEMI']) || strlen(trim($this->paData['DFECEMI'])) != 10 || !$loDate->valDate($this->paData['DFECEMI'])) {
         $this->pcError = 'FECHA DE EMISION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || strlen(trim($this->paData['CDESCRI'])) > 200 || empty($this->paData['CDESCRI'])) {
         $this->pcError = 'DESCRIPCION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CCTACNT']) || strlen(trim($this->paData['CCTACNT'])) > 12 || empty($this->paData['CCTACNT'])) {
         $this->pcError = 'CUENTA CONTABLE INVALIDA';
         return false;
      } elseif (!isset($this->paData['NADICIO']) || strlen(trim($this->paData['NADICIO'])) == 0) {
         $this->pcError = 'MONTO ADICIONAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONIGV']) || strlen(trim($this->paData['NMONIGV'])) == 0 || $this->paData['NMONIGV'] < 0) {
         $this->pcError = 'MONTO IGV INVALIDO';
         return false;
      } elseif (!isset($this->paData['NINAFEC']) || strlen(trim($this->paData['NINAFEC'])) == 0) {
         $this->pcError = 'MONTO INFECTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONTO']) || strlen(trim($this->paData['NMONTO'])) == 0 || $this->paData['NMONTO'] < 0) {
         $this->pcError = 'MONTO TOTAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || count($this->paData['MDATOS']) == 0) {
         $this->pcError = 'DETALLE DE COMPROBANTE INVALIDO';
         return false;
      } elseif ($this->paData['CNIVEL'] == 'AL' && (!isset($this->paData['DFECING']) || strlen(trim($this->paData['DFECING'])) != 10 || !$loDate->valDate($this->paData['DFECING']))) {
         $this->pcError = 'FECHA DE INGRESO INVALIDA';
         return false;
      } elseif ($this->paData['CNIVEL'] == 'AL' && (!isset($this->paData['CGUIREM']) || strlen(trim($this->paData['CGUIREM'])) > 20 || empty($this->paData['CGUIREM']))) {
         $this->pcError = 'GUIA DE REMISION INVALIDA';
         return false;
      } elseif ($this->paData['CNIVEL'] == 'AL' && (!isset($this->paData['MOBSERV']) || empty($this->paData['MOBSERV']))) {
         $this->pcError = 'OBSERVACION INVALIDA';
         return false;
      }
      $this->paData['CNROCOM'] = mb_strtoupper($this->paData['CNROCOM']);
      $this->paData['CDESCRI'] = mb_strtoupper($this->paData['CDESCRI']);
      return true;
   }

   protected function mxGrabarIngresoMaterial($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MNIN_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $this->paData['CIDCOMP'] = $this->paDatos[0]['CIDCOMP'];
      return true;
   }

   #GRABA COMPROBANTES PARA PAGO ADELANTADO
   # 2021-02-03 JLF - Creación
   public function omGrabarPagoAdelantado() {
      $llOk = $this->mxValParamGrabarPagoAdelantado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarPagoAdelantado($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarPagoAdelantado() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDORDE']) || strlen(trim($this->paData['CIDORDE'])) != 8) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CTIPCOM']) || strlen(trim($this->paData['CTIPCOM'])) != 2) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['CNROCOM']) || strlen(trim($this->paData['CNROCOM'])) > 20 || empty($this->paData['CNROCOM'])) {
         $this->pcError = 'NRO DE COMPROBANTE INVALIDO';
         return false;
      } elseif (!isset($this->paData['CMONEDA']) || strlen(trim($this->paData['CMONEDA'])) != 1) {
         $this->pcError = 'ORDEN INVALIDA';
         return false;
      } elseif (!isset($this->paData['DFECEMI']) || strlen(trim($this->paData['DFECEMI'])) != 10 || !$loDate->valDate($this->paData['DFECEMI'])) {
         $this->pcError = 'FECHA DE EMISION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CDESCRI']) || strlen(trim($this->paData['CDESCRI'])) > 200 || empty($this->paData['CDESCRI'])) {
         $this->pcError = 'DESCRIPCION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CCTACNT']) || strlen(trim($this->paData['CCTACNT'])) > 12 || empty($this->paData['CCTACNT'])) {
         $this->pcError = 'CUENTA CONTABLE INVALIDA';
         return false;
      } elseif (!isset($this->paData['NADICIO']) || strlen(trim($this->paData['NADICIO'])) == 0) {
         $this->pcError = 'MONTO ADICIONAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONIGV']) || strlen(trim($this->paData['NMONIGV'])) == 0 || $this->paData['NMONIGV'] < 0) {
         $this->pcError = 'MONTO IGV INVALIDO';
         return false;
      } elseif (!isset($this->paData['NINAFEC']) || strlen(trim($this->paData['NINAFEC'])) == 0) {
         $this->pcError = 'MONTO INFECTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONTO']) || strlen(trim($this->paData['NMONTO'])) == 0 || $this->paData['NMONTO'] < 0) {
         $this->pcError = 'MONTO TOTAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['MANOTAC']) || empty(trim($this->paData['MANOTAC']))) {
         $this->pcError = 'MOTIVO DE PAGO ADELANTADO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || count($this->paData['MDATOS']) == 0) {
         $this->pcError = 'DETALLE DE COMPROBANTE INVALIDO';
         return false;
      }
      $this->paData['CNROCOM'] = mb_strtoupper($this->paData['CNROCOM']);
      $this->paData['CDESCRI'] = mb_strtoupper($this->paData['CDESCRI']);
      $this->paData['MANOTAC'] = mb_strtoupper($this->paData['MANOTAC']);
      return true;
   }

   protected function mxGrabarPagoAdelantado($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MFAC_3('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '[{"ERROR":"ERROR DE EJECUCION SQL"}]' : $laFila[0];
      $this->paDatos = json_decode($laFila[0], true);
      if (!empty($this->paDatos[0]['ERROR'])) {
         $this->pcError = $this->paDatos[0]['ERROR'];
         return false;
      }
      $this->paData['CIDCOMP'] = $this->paDatos[0]['CIDCOMP'];
      return true;
   }

   #ENVIA COMPROBANTES PARA PAGO ADELANTADO A VICE ADMINISTRATIVO
   # 2021-02-03 JLF - Creación
   public function omEnviarComprobanteViceAdministrativo() {
      $llOk = $this->mxValParamEnviarComprobanteViceAdministrativo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarComprobanteViceAdministrativo($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamEnviarComprobanteViceAdministrativo() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) || strlen(trim($this->paData['CIDCOMP'])) != 8) {
         $this->pcError = 'COMPROBANTE INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxEnviarComprobanteViceAdministrativo($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MFAC_4('$lcJson')";
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

   # BANDEJA DE COMPROBANTES DE PAGO ADELANTADO PENDIENTES DE APROBACION VICE ADM
   # 2021-02-04 JLF Creación
   public function omInitBandejaComprobantesPendientesAprobacionViceADM() {
      $llOk = $this->mxValInitBandejaComprobantesPendientesAprobacionViceADM();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioViceAdministrativo($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitBandejaComprobantesPendientesAprobacionViceADM($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValUsuarioViceAdministrativo($p_oSql) {
      # VALIDA USUARIO VICERRECTORADO ADMNISTRATIVO
      $lcCodUsu = $this->paData['CUSUCOD'];
      #$lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCenCos = '02Q' AND cCodUsu = '$lcCodUsu'";
      $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE  cCenCos = '02Q' AND cCodUsu = '$lcCodUsu' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
      return true;
   }

   protected function mxValInitBandejaComprobantesPendientesAprobacionViceADM() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CÓDIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxInitBandejaComprobantesPendientesAprobacionViceADM($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcCenCos = $this->paData['CCENCOS'];
      # TRAER TIPO DE ORDENES
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '075' AND cCodigo NOT IN ('R','A','E','M')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY TIPOS DE ORDENES DEFINIDOS [S01TTAB.075]";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paTipOrd[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   # BUSCA COMPROBANTES DE PAGO ADELANTADO PARA APROBACION DE VICE ADMINISTRATIVO
   # 2021-02-04 JLF Creación
   public function omBuscarComprobantesPendientesAprobacionViceADM() {
      $llOk = $this->mxValBuscarComprobantesPendientesAprobacionViceADM();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioViceAdministrativo($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarComprobantesPendientesAprobacionViceADM($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarComprobantesPendientesAprobacionViceADM() {
      if (!isset($this->paData['CBUSCOM']) || strlen(trim($this->paData['CBUSCOM'])) > 50) {
         $this->pcError = "CLAVE DE BÚSQUEDA INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CTIPO']) || strlen(trim($this->paData['CTIPO'])) != 1) {
         $this->pcError = "TIPO DE ORDEN INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen(trim($this->paData['CNIVEL'])) != 2) {
         $this->pcError = "NIVEL DE USUARIO INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarComprobantesPendientesAprobacionViceADM($p_oSql) {
      $lcBusOrd = $this->paData['CBUSCOM'];
      $lcBusOrd = str_replace(' ', '%', trim($lcBusOrd));
      $lcBusOrd = str_replace('#', 'Ñ', trim($lcBusOrd));
      $lcTipo   = $this->paData['CTIPO'];
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcNivel  = $this->paData['CNIVEL'];
      $lcSql = "SELECT A.cIdComp, A.cTipCom, C.cDescri AS cDesCom, A.cNroCom, A.dFecEmi, A.nMonto, A.mObserv, B.cIdOrde, B.cNroRuc, B.cRazSoc,
                       B.dGenera, B.cTipo, B.cDesTip, B.cDesMon, B.nMonto, B.cCodAnt, B.cDesReq, B.cCenCos, B.cDesCCo, B.cPeriod, B.cEstado,
                       B.cDesEst, B.cIdCoti, B.cUsuMod, B.cNomMod, TO_CHAR(B.tModifi, 'YYYY-MM-DD HH24:MI'), B.mHistor, B.mObsOrd, B.cCodPrv,
                       B.cCCoAnt, B.cCodPar, B.cDesPar, B.cIdActi, B.cIdRequ
                       FROM E01MFAC A
                       INNER JOIN V_E01MORD_3 B ON B.cIdOrde = A.cIdOrde
                       LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '087' AND C.cCodigo = A.cTipCom
                WHERE A.cEstado = 'E' AND A.cPagAde = 'S' AND A.dEnvVic NOTNULL ";
      if ($lcTipo != 'T') {
         $lcSql .= "AND B.cTipo = '$lcTipo' ";
      }
      if ($lcBusOrd != '') {
         $lcSql .= "AND (B.cCodAnt LIKE '%$lcBusOrd%' OR B.cCodPrv = '$lcBusOrd' OR B.cNroRuc = '$lcBusOrd' OR B.cRazSoc LIKE '%$lcBusOrd%') ";
      }
      $lcSql .= "ORDER BY B.cCodAnt ASC, B.nTotReq DESC, B.cIdRequ ASC";
      $R1 = $p_oSql->omExec($lcSql);
      $lcIdOrde = '';
      while ($laFila = $p_oSql->fetch($R1)) {
         if ($lcIdOrde != $laFila[0]) {
            $this->paCompro[] = ['CIDCOMP' => $laFila[0], 'CTIPCOM' => $laFila[1], 'CDESCOM' => $laFila[2], 'CNROCOM' => $laFila[3],
                                 'DFECEMI' => $laFila[4], 'NMONTO'  => $laFila[5], 'MOBSERV' => $laFila[6], 'CIDORDE' => $laFila[7],
                                 'CNRORUC' => $laFila[8], 'CRAZSOC' => $laFila[9], 'DGENERA' => $laFila[10],'CTIPO'   => $laFila[11],
                                 'CDESTIP' => $laFila[12],'CDESMON' => $laFila[13],'NTOTAL'  => $laFila[14],'CCODANT' => $laFila[15],
                                 'CDESREQ' => $laFila[16],'CCENCOS' => $laFila[17],'CDESCCO' => $laFila[18],'CPERIOD' => $laFila[19],
                                 'CESTADO' => $laFila[20],'CDESEST' => $laFila[21],'CIDCOTI' => $laFila[22],'CUSUMOD' => $laFila[23],
                                 'CNOMMOD' => $laFila[24],'TMODIFI' => $laFila[25],'MHISTOR' => $laFila[26],'MOBSORD' => $laFila[27],
                                 'CCODPRV' => $laFila[28],'CCCOANT' => $laFila[29],'CCODPAR' => $laFila[30],'CDESPAR' => $laFila[31],
                                 'CIDACTI' => $laFila[32],'CIDREQU' => $laFila[33]];
            $lcIdOrde = $laFila[0];
         }
      }
      return true;
   }

   # APROBAR COMRPOBANTES PARA PAGO ADELANTADO - VICERRECTORADO ADMNISTRATIVO
   # 2021-02-04 JLF Creación
   function omAprobarComprobantesViceADM() {
      $llOk = $this->mxValAprobarComprobantesViceADM();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioViceAdministrativo($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxAprobarComprobantesViceADM($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValAprobarComprobantesViceADM() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen($this->paData['CNIVEL']) != 2) {
         $this->pcError = 'NIVEL DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'NO HA SELECCIONADO NINGUNA ORDEN';
         return false;
      }
      return true;
   }

   protected function mxAprobarComprobantesViceADM($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdComp) {
         $laData = array_merge($this->paData, ['CIDCOMP' => $lcIdComp]);
         $lcJson = json_encode($laData);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01MFAC_5('$lcJson')";
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

   # OBSERVAR COMRPOBANTES PARA PAGO ADELANTADO - VICERRECTORADO ADMNISTRATIVO
   # 2021-02-04 JLF Creación
   function omObservarComprobantesViceADM() {
      $llOk = $this->mxValObservarComprobantesViceADM();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioViceAdministrativo($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxObservarComprobantesViceADM($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValObservarComprobantesViceADM() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen($this->paData['CNIVEL']) != 2) {
         $this->pcError = 'NIVEL DE USUARIO INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MANOTAC']) || empty(trim($this->paData['MANOTAC']))) {
         $this->pcError = 'MOTIVO DE OBSERVACIÓN INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MDATOS']) || sizeof($this->paData['MDATOS']) == 0) {
         $this->pcError = 'NO HA SELECCIONADO NINGUNA ORDEN';
         return false;
      }
      $this->paData['MANOTAC'] = mb_strtoupper($this->paData['MANOTAC']);
      return true;
   }
   protected function mxObservarComprobantesViceADM($p_oSql) {
      $lcCodUsu = $this->paData['CUSUCOD'];
      foreach ($this->paData['MDATOS'] as $lcIdComp) {
         $laData = array_merge($this->paData, ['CIDCOMP' => $lcIdComp]);
         $lcJson = json_encode($laData);
         $lcJson = str_replace("'", "''", $lcJson);
         $lcSql = "SELECT P_E01MFAC_6('$lcJson')";
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

   protected function mxValParamInitConfeccionTerno() {
      if (!isset($this->paData['CCODEMP']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODEMP']))) {
         $this->pcError = "CÓDIGO DE TRABAJADOR NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxInitConfeccionTerno($p_oSql) {
      $ldPeriod = date('Y');
      $lcSql ="SELECT cIdConf, cEstado, mDatos FROM E02DRCU WHERE cCodEmp = '{$this->paData['CCODEMP']}' AND tModifi BETWEEN '{$ldPeriod}-01-01' AND '{$ldPeriod}-12-31'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
         $this->pcError = "CÓDIGO DE TRABAJADOR NO ESTA CONSIDERADO EN LA RELACIÓN DE RENDICION DE CUENTA PARA UNIFORMES INSTITUCIONALES";
         return false;
      } elseif ($laTmp[1] == 'C' or $laTmp[1] == 'D' or $laTmp[1] == 'B') {
         $this->pcError = "USTED YA REGISTRO SU COMPROBANTE PARA LA RENDICION DE CUENTA DE SU TERNO";
         return false;
      } elseif (isset($laTmp[1]) and $laTmp[1] == 'O') {
         $this->paData = array_merge($this->paData, ['MOBSERV' => json_decode($laTmp[2], true)['MOBSERV'],
            'CESTADO' => $laTmp[1]]);
      }
      $laData = ['CIDCONF'=> $laTmp[0], 'CNOMBRE'=> '', 'CNRODNI'=> '', 'CCODEMP'=> $this->paData['CCODEMP'], 'ATIPCOM'=> ''];
      $laDatos = [];
      $lcSql ="SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('01', '02', '03')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TABLA DE TIPOS DE COMPROBANTE [087] NO TIENE DETALLE";
         return false;
      }
      $laData['ATIPCOM'] = $laDatos;
      $lcSql ="SELECT cNroDni, cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '{$this->paData['CCODEMP']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $laData['CNRODNI'] = $laTmp[0];
      $laData['CNOMBRE'] = str_replace("/",  " ", $laTmp[1]);
      $this->paData = array_merge($this->paData, $laData);
      return true;
   }
   public function omInitConfeccionTerno() {
      $llOk = $this->mxValParamInitConfeccionTerno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitConfeccionTerno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   public function omGrabarConfeccionTerno() {
      $llOk = $this->mxValParamGrabarConfeccionTerno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarConfeccionTerno($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
      return false;
   }
   protected function mxValParamGrabarConfeccionTerno() {
      $loDate = new CDate();
      if (!isset($this->paData['CIDCONF']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CIDCONF'])) {
         $this->pcError = "ID DE CONFECCION NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNRORUC']) || !preg_match('/^[0-9]{11}$/', $this->paData['CNRORUC'])) {
         $this->pcError = "NÚMERO DE RUC NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif ($this->paData['CNRORUC'] == '00000000000') {
         if (!isset($this->paData['CRUCNRO']) || !preg_match('/^[0-9]{11}$/', $this->paData['CRUCNRO'])) {
            $this->pcError = "NÚMERO DE RUC NO DEFINIDO O INVÁLIDO (1)";
            return false;
         } elseif (!isset($this->paData['CRAZSOC']) || empty($this->paData['CRAZSOC'])) {
            $this->pcError = "RAZÓN SOCIAL NO DEFINIDA O INVÁLIDA";
            return false;
         }
      } elseif (!isset($this->paData['CNROCOM']) || empty($this->paData['CNROCOM'])
         || !preg_match('/^[0-9A-Z]{4}-[0-9]{8}$/', $this->paData['CNROCOM'])) {
         $this->pcError = "NÚMERO DE COMPROBANTE NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['DFECCOM']) || !$loDate->ValDate($this->paData['DFECCOM'])) {
         $this->pcError = "FECHA DE COMPROBANTE NO DEFINIDO O INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['NMONTO']) || !is_numeric($this->paData['NMONTO']) || floatval($this->paData['NMONTO']) <= 0.00) {
         $this->pcError = "MONTO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NMONIGV']) || !is_numeric($this->paData['NMONIGV']) || floatval($this->paData['NMONIGV']) < 0.00) {
         $this->pcError = "IGV NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NMONTIR']) || !is_numeric($this->paData['NMONTIR']) || floatval($this->paData['NMONTIR']) < 0.00) {
         $this->pcError = "IMPUESTO A LA RENTA NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif ($this->paData['CTIPCOM'] == '01' and floatval($this->paData['NMONIGV']) <= 0) {
         $this->pcError = "EL MONTO DE IGV DEBE SER MAYOR A CERO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD'])|| !preg_match('/^[0-9]{4}$/', $this->paData['CUSUCOD'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (($this->paFile['size']==0)) {
         $this->pcError = "DEBE CARGAR EL COMPROBANTE";
         return false;
      }
      return true;
   }
   protected function mxGrabarConfeccionTerno($p_oSql) {
      $lcIdConf = $this->paData['CIDCONF'];
      if ($this->paFile['error'] == 0) {
         $lcFolder = "/var/www/html/UCSMERP/Docs/Uniformes/";
         if (!is_dir($lcFolder)) {
            $perm = "0777";
            $modo = intval($perm, 8);
            mkdir($lcFolder, $modo);
            chmod($lcFolder, $modo);
         }
         $lcFilePath = $lcFolder."TER$lcIdConf.".'pdf';
         if (!move_uploaded_file($this->paFile['tmp_name'], $lcFilePath)) {
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO CORRECTAMENTE";
            return false;
         }
      }
      $lmDatos = '';
      if ($this->paData['CNRORUC'] != '00000000000') {
         $lcSql = "SELECT cNroRuc FROM S01MPRV WHERE cNroRuc = '{$this->paData['CNRORUC']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!isset($laTmp[0])) {
            $this->pcError = "NÚMERO DE RUC NO EXISTE";
            return false;
         }
         $lcNroRuc = $this->paData['CNRORUC'];
      } else {
         $lmDatos = json_encode(['CRUCNRO'=> $this->paData['CRUCNRO'], 'CRAZSOC'=> $this->paData['CRAZSOC']]);
         $lcNroRuc = $this->paData['CRUCNRO'];
      }
      $lcSql = "SELECT cIdConf, cNroRuc, mDatos FROM E02DRCU WHERE cIdConf != '{$this->paData['CIDCONF']}' AND
                cNroCom = '{$this->paData['CNROCOM']}' AND cTipCom = '{$this->paData['CTIPCOM']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laData = json_decode($laTmp[2], true);
         if ($this->paData['CNRORUC'] != '00000000000' and $this->paData['CNRORUC'] == $laTmp[1]) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA REGISTRADO";
            return false;
         } elseif (isset($laData['CRUCNRO']) and $lcNroRuc == $laData['CRUCNRO']) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA REGISTRADO";
            return false;
         }
      }
      $lcSql = "UPDATE E02DRCU SET cNroRuc = '{$this->paData['CNRORUC']}', cTipCom = '{$this->paData['CTIPCOM']}', mDatos = '$lmDatos',
                cNroCom = '{$this->paData['CNROCOM']}', dFecCom = '{$this->paData['DFECCOM']}', nMonto = '{$this->paData['NMONTO']}', nMonIgv = '{$this->paData['NMONIGV']}',
                nMontIR = '{$this->paData['NMONTIR']}', cEstado = 'B', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()
                WHERE cIdConf = '{$this->paData['CIDCONF']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'NO SE PUDO GRABAR COMPROBANTE DE CONFECCIÓN DE TERNO';
         return false;
      }

      return true;
   }

   public function omInitRevisarTerno() {
      $llOk = true;
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = true;
#      $llOk = $this->mxValParamUsuario('000');
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxInitRevisarTerno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxInitRevisarTerno($p_oSql) {
      $anio = date('Y');
      $lcSql = "SELECT * FROM F_E02DRCU('{$anio}', {$this->paData['CESTADO']}) 
         WHERE TO_CHAR(tModifi, 'YYYY-MM-DD') <= '{$this->paData['DFECHA']}' ORDER BY tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      $laDatos = [];
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CIDCONF'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CCODEMP'=> $laTmp[2],
            'CNOMBRE'=> str_replace("/",  " ", $laTmp[3]), 'CNRORUC'=> $laTmp[4], 'CRAZSOC'=> $laTmp[5],
            'CTIPCOM'=> $laTmp[6], 'CNROCOM'=> $laTmp[7], 'DFECCOM'=> $laTmp[8], 'NMONTO'=> $laTmp[9],
            'NMONIGV'=> $laTmp[10], 'NMONTIR'=> $laTmp[11], 'CMONEDA'=> $laTmp[12], 'NASIENT'=> $laTmp[13],
            'NCOMPRV'=> $laTmp[17], 'NTOTPRV'=> $laTmp[18], 'CUSUCOD'=> $laTmp[19], ];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "NO HAY COMPROBANTES PARA REVISAR";
         return true;
      } else {
         $this->paDatos = $laDatos;
         return true;
      }
   }

   public function omInitBuscarRevisarTerno() {
      $llOk = true;
#      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = true;
#      $llOk = $this->mxValParamUsuario('000');
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxInitBuscarRevisarTerno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxInitBuscarRevisarTerno($p_oSql) {
      $lcSql = "SELECT * FROM F_E02DRCU('2022', {$this->paData['CESTADO']}) WHERE cIdConf = '{$this->paData['CIDCONF']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp)) {
         $this->pcError = "NO SE PUDO CARGAR LA INFORMACIÓN";
         return false;
      }
      $laData = ['CIDCONF'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CCODEMP'=> $laTmp[2],
         'CNOMBRE'=> str_replace("/",  " ", $laTmp[3]), 'CNRORUC'=> $laTmp[4], 'CRAZSOC'=> $laTmp[5],
         'CTIPCOM'=> $laTmp[6], 'CNROCOM'=> $laTmp[7], 'DFECCOM'=> $laTmp[8], 'NMONTO'=> $laTmp[9],
         'NMONIGV'=> $laTmp[10], 'NMONTIR'=> $laTmp[11], 'CMONEDA'=> $laTmp[12], 'NASIENT'=> $laTmp[13],
         'NCOMPRV'=> $laTmp[17], 'NTOTPRV'=> $laTmp[18], 'CUSUCOD'=> $laTmp[20], ];
      $this->paData = array_merge($this->paData, $laData);
      $laDatos = [];
      $lcSql ="SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('01', '02', '03')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TABLA DE TIPOS DE COMPROBANTE [087] NO TIENE DETALLE";
         return false;
      }
      $this->paData = array_merge($this->paData, ['ATIPCOM'=>$laDatos]);
#      $laData['ATIPCOM'] = $laDatos;
      return true;
   }

   public function omGrabarRevisarTerno() {
      $llOk = $this->mxValParamGrabarRevisarTerno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = true;
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxGrabarRevisarTerno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamGrabarRevisarTerno() {
      if (!isset($this->paData['CIDCONF']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CIDCONF'])) {
         $this->pcError = "ID DE CONFECCIÓN NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CTIPCOM'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNROCOM'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['DFECCOM'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NMONTO'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NMONTIR'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['NMONIGV'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxGrabarRevisarTerno($p_oSql) {
      #      Validación de que comprobante no haya sido ingresado anteriormente
      $lcSql = "SELECT cIdConf, cNroRuc, mDatos FROM E02DRCU WHERE cIdConf != '{$this->paData['CIDCONF']}' AND cNroCom = '{$this->paData['CNROCOM']}' AND cTipCom = '{$this->paData['CTIPCOM']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (isset($laTmp)) {
         $laData = json_decode($laTmp[2], true);
         if ($this->paData['CNRORUC'] == $laTmp[1]) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA FUE REGISTRADO";
            return false;
         } elseif (isset($laData['CRUCNRO']) and $this->paData['CNRORUC'] == $laData['CRUCNRO']) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA FUE REGISTRADO";
            return false;
         }
      }
      $lcSql = "SELECT cEstado, mDatos FROM E02DRCU WHERE cIdConf = '{$this->paData['CIDCONF']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
         $this->pcError = "NO HAY ID DE CONFECCIÓN";
         return false;
      } elseif ($laTmp[0] == 'A' or $laTmp[0] == 'C') {
         $this->pcError = "ESTADO NO PERMITE ACTUALIZACIÓN";
         return false;
      }
      $ljMDatosTabla = json_decode($laTmp[1], true);
      if ($ljMDatosTabla == null) {
         $ljMDatosTabla = [];
      }
      $lmDatos = json_encode(array_merge(
         $ljMDatosTabla, [
            'CCODREV'=> $this->paData['CUSUCOD'], 'TREVISI'=> date('Y-m-d G:i:s'),
            'MOBSERV'=>strtoupper($this->paData['MOBSERV']), 'CRAZSOC'=> str_replace("'", "''", $ljMDatosTabla)]
      ));
      $lcSql = "UPDATE E02DRCU set cEstado = 'C', mDatos = '$lmDatos', nMonto = '{$this->paData['NMONTO']}', 
                   dFecCom = '{$this->paData['DFECCOM']}', cNroCom = '{$this->paData['CNROCOM']}', cTipCom = '{$this->paData['CTIPCOM']}',
                   nMonIgv = '{$this->paData['NMONIGV']}', nMontIr = '{$this->paData['NMONTIR']}' WHERE cIdConf = '{$this->paData['CIDCONF']}'";

      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'NO SE PUDO GRABAR REVISIÓN DE COMPROBANTE';
         return false;
      }
      return true;
   }

   public function omObservarTerno() {
      $llOk = $this->mxValParamObservarTerno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxObservarTerno($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamObservarTerno() {
      if (!isset($this->paData['CIDCONF']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CIDCONF'])) {
         $this->pcError = "ID DE CONFECCIÓN NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['MOBSERV'])) {
         $this->pcError = "OBSERVACIÓN NO DEFINIDA O INVÁLIDA";
         return false;
      }
      return true;
   }
   protected function mxObservarTerno($p_oSql) {
      #      Validación de que comprobante no haya sido ingresado anteriormente
      $lcSql = "SELECT cIdConf, cNroRuc, mDatos FROM E02DRCU WHERE cIdConf != '{$this->paData['CIDCONF']}' AND cNroCom = '{$this->paData['CNROCOM']}' AND cTipCom = '{$this->paData['CTIPCOM']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (isset($laTmp)) {
         $laData = json_decode($laTmp[2], true);
         if ($this->paData['CNRORUC'] == $laTmp[1]) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA FUE REGISTRADO";
            return false;
         } elseif (isset($laData['CRUCNRO']) and $this->paData['CNRORUC'] == $laData['CRUCNRO']) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA FUE REGISTRADO";
            return false;
         }
      }
      $lcSql = "SELECT cIdConf, cNroRuc, mDatos FROM E02DRCU WHERE cIdConf != '{$this->paData['CIDCONF']}' AND cNroCom = '{$this->paData['CNROCOM']}' AND cTipCom = '{$this->paData['CTIPCOM']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (isset($laTmp)) {
         $laData = json_decode($laTmp[2], true);
         if ($this->paData['CNRORUC'] == $laTmp[1]) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA REGISTRADO";
            echo 'RUC INVALIDO';
            return false;
         } elseif (isset($laData['CRUCNRO']) and $this->paData['CNRORUC'] == $laData['CRUCNRO']) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA REGISTRADO";
            echo 'RUC INVALIDO MDATOS';
            return false;
         }
      }
      $lcSql = "SELECT cEstado, mDatos FROM E02DRCU WHERE cIdConf = '{$this->paData['CIDCONF']}'";
      $R1 = $p_oSql->OmExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
         $this->pcError = "NO HAY ID DE CONFECCIÓN";
         return false;
      } elseif ($laTmp[0] == 'A' or $laTmp[0] == 'C') {
         $this->pcError = "ESTADO NO PERMITE ACTUALIZACIÓN";
         return false;
      }
      $ljMDatosTabla = json_decode($laTmp[1], true);
      if ($ljMDatosTabla == null) {
         $ljMDatosTabla = [];
      }
      $lmDatos = json_encode(array_merge(
         $ljMDatosTabla, [
            'CCODREV'=> $this->paData['CUSUCOD'], 'TREVISI'=> date('Y-m-d G:i:s'),
            'MOBSERV'=>strtoupper($this->paData['MOBSERV']), 'CRAZSOC'=> str_replace("'", "''", $ljMDatosTabla)]
      ));
      $lcSql = "UPDATE E02DRCU set cEstado = 'O', mDatos = '$lmDatos', nMonto = '{$this->paData['NMONTO']}', 
                   dFecCom = '{$this->paData['DFECCOM']}', cNroCom = '{$this->paData['CNROCOM']}', cTipCom = '{$this->paData['CTIPCOM']}',
                   nMonIgv = '{$this->paData['NMONIGV']}', nMontIr = '{$this->paData['NMONTIR']}' WHERE cIdConf = '{$this->paData['CIDCONF']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'NO SE PUDO OBSERVAR EL REGISTRO';
         return false;
      }
      return true;
   }

   public function omBuscarDetalleProveedorTerno() {
      $llOk = $this->mxValParamBuscarDetalleProveedorTerno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarDetalleProveedorTerno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamBuscarDetalleProveedorTerno() {
      if (!isset($this->paData['CNRORUC']) or !preg_match('/^[0-9A-Z]{11}$/', $this->paData['CNRORUC'])) {
         $this->pcError = "NÚMERO DE RUC NO DEFINIDO O INVÁLIDA";
         return false;
      }
      return true;
   }
   protected function mxBuscarDetalleProveedorTerno($p_oSql) {
      $lcSql = "SELECT cidconf, cestado, ccodemp, cnombre, cnroruc, crazsoc, ctipcom, cnrocom, nmonto FROM F_E02DRCU('2022', 1) WHERE cNroRuc = '{$this->paData['CNRORUC']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CIDCONF' => $laTmp[0], 'CESTADO' => $laTmp[1], 'CCODEMP' => $laTmp[2],
            'CNOMBRE' => str_replace("/", " ", $laTmp[3]), 'CNRORUC' => $laTmp[4], 'CRAZSOC' => $laTmp[5],
            'CTIPCOM' => $laTmp[6], 'CNROCOM' => $laTmp[7], 'NMONTO' => $laTmp[8]];
#         $R1 = $p_oSql->omExec($lcSql);
      }
      if (count($laDatos) == 0 ) {
         $this->pcError = 'NO SE HAN ENCONTRADO RESULTADOS';
         return false;
      }
      $this->paDatos = $laDatos;
      return true;
   }

   public function omVerReporteTernoXls() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerReporteTernoXls($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxVerReporteTernoXls($p_oSql) {
      $anio = date('Y');
      $lcSql = "SELECT * FROM F_E02DRCU('{$anio}', {$this->paData['CESTADO']}) 
         WHERE TO_CHAR(tModifi, 'YYYY-MM-DD') <= '{$this->paData['DFECHA']}' ORDER BY tModifi";
      $R1 = $p_oSql->omExec($lcSql);

      $loXls = new CXls();
      $loXls->openXlsIO('Log3120', 'R');
      # Cabecera
      $loXls->sendXls(0, 'K', 3, date("Y-m-d"));
      $i = 5;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT ccoddoc FROM V_A01MDOC WHERE CCodDoc = '{$laTmp[2]}'";
         $R2 = $p_oSql->omExec($lcSql);
         $lcCodDoc = $p_oSql->fetch($R2);
         $lcTipTra = null;
         if (empty($lcCodDoc)) {
            $lcTipTra = 'TRABAJADOR';
         } else {
            $lcTipTra = 'DOCENTE';
         }
         $laDatos[] = ['CIDCONF'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CCODEMP'=> $laTmp[2],
            'CNOMBRE'=> str_replace("/",  " ", $laTmp[3]), 'CNRORUC'=> $laTmp[4], 'CRAZSOC'=> $laTmp[5],
            'CTIPCOM'=> $laTmp[6], 'CNROCOM'=> $laTmp[7], 'DFECCOM'=> $laTmp[8], 'NMONTO'=> $laTmp[9],
            'NMONIGV'=> $laTmp[10], 'NMONTIR'=> $laTmp[11], 'CMONEDA'=> $laTmp[12], 'NASIENT'=> $laTmp[13],
            'NCOMPRV'=> $laTmp[17], 'NTOTPRV'=> $laTmp[18], 'CUSUCOD'=> $laTmp[19], 'CTIPTRA' => $lcTipTra];
      }
#      var_dump($laDatos);
#      die;
      if (count($laDatos) == 0) {
         $this->pcError = "NO HAY COMPROBANTES PARA MOSTRAR";
         return false;
      }
      foreach ($laDatos as $key => $laFila) {
         $i++;
         if ($laFila['CTIPCOM'] == '01') {
            $lcTipCom = 'FACTURA';
         } elseif ($laFila['CTIPCOM'] == '02') {
            $lcTipCom = 'RECIBO POR HONORARIOS';
         } else {
            $lcTipCom = 'BOLETA';
         }
         if ($laFila['CESTADO'] == 'B') {
            $lcEstado = 'ENVIADO';
         } elseif ($laFila['CESTADO'] == 'O') {
            $lcEstado = 'OBSERVADO';
         } elseif ($laFila['CESTADO'] == 'C') {
            $lcEstado = 'CONTABILIZADO';
         }
         $loXls->sendXls(0, 'A', $i, $key + 1);
         $loXls->sendXls(0, 'B', $i, $laFila['CCODEMP']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['CTIPTRA']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNRORUC']);
         $loXls->sendXls(0, 'F', $i, $laFila['CRAZSOC']);
         $loXls->sendXls(0, 'G', $i, $lcTipCom);
         $loXls->sendXls(0, 'H', $i, $laFila['CNROCOM']);
         $loXls->sendXls(0, 'I', $i, $laFila['DFECCOM']);
         $loXls->sendXls(0, 'J', $i, $laFila['NMONTO']);
         $loXls->sendXls(0, 'K', $i, $laFila['NMONIGV']);
         $loXls->sendXls(0, 'L', $i, $laFila['NMONTIR']);
         $loXls->sendXls(0, 'M', $i, $lcEstado);
      }
      $loXls->closeXlsIO();
      $this->paFile = $loXls->pcFile;
      return true;
   }

   public function omVerReporteContabilizadosPagoXls() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerReporteContabilizadosPagoXls($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxVerReporteContabilizadosPagoXls($p_oSql) {
      $lcSql = "SELECT ccodemp, nmonto FROM E02DRCU WHERE cEstado = 'D' ORDER BY ccodemp";
      echo $lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      $loXls = new CXls();
      $loXls->openXlsIO('Log3120-2', 'R');
      # Cabecera
      $loXls->sendXls(0, 'D', 1, date("Y-m-d"));
      $i = 5;
      $laDatos = [];
      echo 'passo query';
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODEMP'=> $laTmp[0], 'NMONTO'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "NO HAY COMPROBANTES PARA MOSTRAR";
         return false;
      }
      foreach ($laDatos as $key => $laFila) {
         $i++;
         if ($laFila['NMONTO'] > 400.00) {
            $lnMonto = 400.00;
         } else {
            $lnMonto = $laFila['NMONTO'];
         }
         $loXls->sendXls(0, 'A', $i, $key + 1);
         $loXls->sendXls(0, 'B', $i, $laFila['CCODEMP']);
         $loXls->sendXls(0, 'C', $i, $lnMonto);
      }
      $loXls->closeXlsIO();
      $this->paFile = $loXls->pcFile;
      return true;
   }

   public function omVerAsientoContableTerno() {
      $llOk = $this->mxVerAsientoContableTerno();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxVerAsientoContableTerno() {
      $laData = ['ID' => 'Log3130', 'CUSUCOD'=> '1221', 'CPERIOD' => $this->paData['CPERIOD'],
         'DCONTAB' => $this->paData['DCONTAB'], 'CFLAG' => 'S'];
      $sJson = json_encode($laData);
      # ruta temporal para guardar archivos generados
      $dirTmp = 'cd C:\xampp\htdocs\UCSMERP\ERPWS\Clases\FILESTMP';
      # primero concatenar los comandos para que cuando se genere los archivos se guarden en una carpeta para que no se llene la carpeta root
      # el comando debe apuntar al entorno virtual en el que esta trabajando
      $command = $dirTmp.' && C:\APLICATIVOS\UCSM\ucm-env\Scripts\python.exe C:\xampp\htdocs\UCSMERP\ERPWS\Clases\CContabilizacion.py '.$sJson;
      $command = str_replace('"', '\"', $command);
      $output = shell_exec($command);
      $laTmp = json_decode($output, true);
      if (isset($laTmp)) {
         $this->paData = ['FASIENTOS'=>$laTmp];
         return true;
      } else {
         $this->pcError = 'HA OCURRIDO UN ERROR';
         return false;
      }
   }

   public function omResumenComprobantesTerno() {
      $llOk = $this->mxValParamResumenComprobantesTerno();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxResumenComprobantesTerno($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamResumenComprobantesTerno() {
      if (!isset($this->paData['DPERIOD']) or !preg_match('/^[0-9]{4}$/', $this->paData['DPERIOD'])) {
         $this->pcError = 'PERIODO DE CONSULTA NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxResumenComprobantesTerno($p_oSql) {
      $lcSql = "SELECT count(cestado) FROM e02drcu WHERE date_part('year', tmodifi) = '{$this->paData['DPERIOD']}' and cestado = 'B'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $this->paDatos = [];
      $this->paDatos = array_merge($this->paDatos, ['NTOTENV'=> $laTmp[0]]);
      $lcSql = "SELECT count(cestado) FROM e02drcu WHERE date_part('year', tmodifi) = '{$this->paData['DPERIOD']}' and cestado = 'O'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $this->paDatos = array_merge($this->paDatos, ['NTOTOBS'=> $laTmp[0]]);
      $lcSql = "SELECT count(cestado) FROM e02drcu WHERE date_part('year', tmodifi) = '{$this->paData['DPERIOD']}' and cestado = 'C'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $this->paDatos = array_merge($this->paDatos, ['NTOTCNT'=> $laTmp[0]]);
      return true;
   }

   public function omVerificarComprobanteDuplicadoRendicionTernos() {
      $llOk = $this->mxValParamVerificarComprobanteDuplicadoRendicionTernos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerificarComprobanteDuplicadoRendicionTernos($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamVerificarComprobanteDuplicadoRendicionTernos() {
      return true;
   }
   protected function mxVerificarComprobanteDuplicadoRendicionTernos($p_oSql) {
#      Validación de que comprobante no haya sido ingresado anteriormente
      $lcSql = "SELECT cIdConf, cNroRuc, mDatos FROM E02DRCU WHERE cIdConf != '{$this->paData['CIDCONF']}' AND cNroCom = '{$this->paData['CNROCOM']}' AND cTipCom = '{$this->paData['CTIPCOM']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (isset($laTmp)) {
         $laData = json_decode($laTmp[2], true);
         if ($this->paData['CNRORUC'] == $laTmp[1]) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA REGISTRADO";
            return false;
         } elseif (isset($laData['CRUCNRO']) and $this->paData['CNRORUC'] == $laData['CRUCNRO']) {
            $this->pcError = "NÚMERO DE COMPROBANTE YA REGISTRADO";
            return false;
         }
      }

      $this->paDatos = $laDatos;
      return true;

   }

}
