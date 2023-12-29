<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Libs/fpdf/fpdf.php";
require_once "class/PHPExcel.php";
require_once "Clases/CEmail.php";
//use PhpOffice\PhpSpreadsheet\IOFactory;
//use PhpOffice\PhpSpreadsheet\Spreadsheet;
//use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
//use PhpOffice\PhpSpreadsheet\Style\Fill;
//use PhpOffice\PhpSpreadsheet\Style\Alignment;
//require 'class/ImportarExcel/vendor/autoload.php';
//require_once "Libs/fpdf/fpdf.php";
date_default_timezone_set('America/Bogota');

// -----------------------------------------------------------
// Clase que gestiona los procesos de Control Patrimonial
// 2022-02-03 FPM Creacion
// -----------------------------------------------------------
class CControlPatrimonial extends CBase {

   public $paData, $paDatos, $paData1, $laDatos, $laData, $paDato;
   // protected $laData;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->laData = $this->laDatos = $this->paDato =  null;
      // $this->pcFile = 'FILES/R' . rand() . '.pdf';
   }

   protected function mxValParamUsuario($p_oSql, $p_cModulo = '000') {
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCencos = '02V' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         ;
      } elseif ($laFila[0] == 'A') {
         return true;
      }
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCencos = '02V' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '$p_cModulo'";
      // print_r($lcSql);
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "USUARIO NO TIENE PERMISO PARA OPCION";
         return False;
      } elseif ($laFila[0] != 'A') {
         $this->pcError = "USUARIO NO TIENE PERMISO ACTIVO PARA OPCION";
         return False;
      }
      return True;
   }
   
   protected function mxValParam() { 
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } elseif (!isset($this->paData['CCENCOS']) || (!preg_match('/^[0-9A-Z]{3}$/', $this->paData['CCENCOS']))) { 
         $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } 
      return true; 
   } 

   //------------------------------------------------------------------------------------
   // Init recuperacion de activos fijos contabilizados (Afj1020 MAESTRO DE ACTIVOS)
   // 2022-02-24 GCH
   //------------------------------------------------------------------------------------
   public function omInitDatosMaeAct() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDatosMaeAct($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxInitDatosMaeAct($p_oSql) {
      // Estado del AF
      $laEstAct = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '333'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laEstAct[] = ['CESTADO'=> $laFila[0], 'CDESCRI'=> $laFila[1]]; 
      }
      if (count($laEstAct) == 0) {
         $this->pcError = 'NO HAY ESTADOS DE ACTIVO FIJO DEFINIDOS [313]';
         return false;
      }      
      // Situacion del AF
      $laSituac = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($laSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      // Cargar Clase Activo
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $laDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($laDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      // Cargar Centro Responsabilidad
      // $lcSql = "SELECT cCenRes, cDescri FROM S01TRES WHERE cEstado = 'A' ORDER BY cDescri";
      $Sql = "SELECT A.cCenRes, A.cDescri FROM S01TRES A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.cEstado = 'A' ORDER BY A.cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laCenRes[] = ['CCENRES'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laCenRes) == 0) {
        $this->pcError = "NO HAY CENTROS DE RESPONSABILIDAD ACTIVOS";
        return false;
      }
      // Cargar Tipos Activos Fijos
      $lcSql = "SELECT cTipAfj, cDescri FROM E04TTIP WHERE cEstado = 'A' ORDER BY cTipAfj";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laTipAfj[] = ['CTIPAFJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laTipAfj) == 0) {
        $this->pcError = "NO HAY TIPOS DE ACTIVOS FIJOS ACTIVOS";
        return false;
      }
      $this->paData = ['AESTACT'=> $laEstAct, 'ASITUAC'=> $laSituac, 'ACLASE'=> $laDatClaAfj, 'ACENCOS'=> $laDatos, 'ACENRES'=> $laCenRes, 'ATIPAFJ'=> $laTipAfj];
      return true;
   }
   
   // --------------------------------------------------------
   // Compras realizadas de Activo Fijo
   // 2022-01-17 FPM Creacion
   // --------------------------------------------------------
   public function omComprasActivoFijo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxComprasActivoFijo($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxComprasActivoFijo($p_oSql) {
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT TRIM(cCtaCnt), TRIM(cNroAsi), TRIM(cCodiOL), TRIM(cGlosa), cNroRuc, cRazSoc, cNroCom, dFecDoc, nTipCam, nMonto, nIdAsie
                FROM F_D10MASI_3('{$lcJson}')";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)){
         $laDatos = [];
         //SUMA MONTO DE ACTIVOS 
         //$lcSql = "SELECT SUM(nMontmn) FROM E04MAFJ WHERE mdatos like '%$laTmp[1]%'";
         $lcSql = "SELECT SUM(nMontmn) FROM E04MAFJ WHERE  mDatos::JSON->>'CCODREF' = '{$this->paData['CYEAR']}$laTmp[1]' OR mDatos::JSON->>'CCODREF' like '{$this->paData['CYEAR']}$laTmp[1]%'";
         $R2 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R2);
         $lnMonReg = $laFila[0];
         // ACTIVOS INGRESADOS
         $laActFij = [];
         $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.nMontmn, A.dFecAlt, A.cCodEmp, A.cCenRes, REPLACE(C.cNombre, '/', ' '), B.cDescri, A.cSituac
                  FROM E04MAFJ A
                  INNER JOIN S01TRES B ON B.cCenRes = a.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE mdatos like '%$laTmp[1]%'
                  ORDER BY A.cCenRes, A.cTipAfj, A.nCorrel ";
         $R3 = $p_oSql->omExec($lcSql);
         while ($laTmp1 = $p_oSql->fetch($R3)) {
            $lcCodigo = substr($laTmp1[1], 0, 2).'-'.substr($laTmp1[1], 2, 5).'-'.right('00000'.strval($laTmp1[2]), 6);
            $laActFij[] = ['CACTFIJ'=> $laTmp1[0], 'CCODIGO'=> $lcCodigo, 'CDESCRI'=> $laTmp1[3], 'NMONTO'=> $laTmp1[4],'DFECALT'=> $laTmp1[5],
                           'CCODEMP'=> $laTmp1[6], 'CCENRES'=> $laTmp1[7], 'CNOMBRE'=> $laTmp1[8], 'CDESRES'=> $laTmp1[9], 'CSITUAC'=> $laTmp1[10]];
         }
         $this->paDatos[] = ['CCTACNT'=> $laTmp[0], 'CNROASI'=> $this->paData['CYEAR'].$laTmp[1], 'CCODIOL'=> $laTmp[2], 'CGLOSA'=> $laTmp[3], 'CNRORUC'=> $laTmp[4],
                             'CRAZSOC'=> $laTmp[5], 'CNROCOM'=> $laTmp[6], 'DFECDOC'=> $laTmp[7], 'NTIPCAM'=> $laTmp[8], 'NMONTO'=> $laTmp[9],
                             'NIDASIE'=> $laTmp[10], 'NMONREG' => $lnMonReg, 'CACTFIJ'=>$laActFij];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY REGISTROS PARA MOSTRAR';
         return false;
      }
      return true;
   }

   // --------------------------------------------------------
   // LISTA DE ACTIVOS FIJOS INGRESADOS 
   // GCH 25-11-2022
   // --------------------------------------------------------
   public function omReporteActRegPDF() {
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
     // Print_r($lo->paDatos);
      $llOk = $lo->mxReporteActRegPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      //print_r($this->paData['CREPORT']);
      return $llOk;
   }
   // --------------------------------------------------------
   // Afj1020 - ORDEN PARA EL REPORTE
   // --------------------------------------------------------
   public function omObtenerIdOrden() {
      //CONEXION UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxObtenerIdOrden($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      // $lcIdOrde = $this->laData['CIDORDE'];
      // $lo->laData = ['CIDORDE' => $lcIdOrde];
      $llOk = $this->mxPrintOCS_($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->laDatos;
      $laFirmas = $this->laFirmas;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $lo->laFirmas = $laFirmas;
      $llOk = $lo->mxPrintReportOCS_();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      // print_r($this->paData['CREPORT']);
      return $llOk;
   }

   protected function mxObtenerIdOrden($p_oSql) {
      $lcNumCom = $this->paData['p_cNumCom'];
      $lcNroRuc = $this->paData['p_cNroRuc'];
      $lcYear = $this->paData['p_cYear'];
      $lcSql = "SELECT A.cIdOrde
               FROM E01MFAC A
               INNER JOIN S01MPRV B ON B.cNroRuc = A.cNroRuc
               INNER JOIN E01MORD C ON C.cIdOrde = A.cIdOrde
               WHERE A.cEstado NOT IN ('X') AND C.cEstado NOT IN ('X') AND A.dFecEmi BETWEEN '{$lcYear}-01-01' AND '{$lcYear}-12-30'
                 AND B.cNroRuc = '{$lcNroRuc}' AND  A.cNroCom = '{$lcNumCom}'";
      // print_r($lcSql);
      // echo "<br>";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->laData = ['CIDORDE' => $laFila[0]]; 
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintOCS_($p_oSql) {
      $lcIdOrde = $this->laData['CIDORDE'];
      // print_r($lcIdOrde);
      $lcSql = "SELECT t_cIdOrde, t_dGenera, t_cNroRuc, t_cRazSoc, t_cDetEnt, t_cCodArt, t_cDesArt, t_nCantid, t_nCosto, t_mObserv, 
                       t_cTipo, t_cNroCel, t_cForPag, t_cCtaBc1, t_cCtaBc2, t_cCenCos, t_cDesCCo, t_cMoneda, t_cCodAnt, t_cUnidad,
                       t_cDesUni, TRIM(t_cDescri), t_cEmail, t_cDirecc, t_cCodPrv, t_cCCoAnt, t_cCtaCnt, t_cCodPar, t_cDesPar, 
                       t_cEstado, t_cLugar, t_cMonCor, t_cDesMon, t_nMonto, TO_CHAR(t_tAfecta, 'YYYY-MM-DD'), t_cUsuCot, TRIM(t_cNomCot),
                       t_cUsuGen, t_cNomGen
                FROM F_E01MORD_4('$lcIdOrde')";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDORDE' => $laTmp[0], 'DGENERA' => $laTmp[1], 'CNRORUC' => $laTmp[2], 'CRAZSOC' => $laTmp[3], 
                             'CDETENT' => $laTmp[4], 'CCODART' => $laTmp[5], 'CDESART' => $laTmp[6], 'NCANTID' => $laTmp[7], 
                             'NCOSTO'  => $laTmp[8], 'MOBSERV' => $laTmp[9], 'CTIPO'   => $laTmp[10],'CNROCEL' => $laTmp[11],
                             'CFORPAG' => $laTmp[12],'CCTABC1' => $laTmp[13],'CCTABC2' => $laTmp[14],'CCENCOS' => $laTmp[15], 
                             'CDESCCO' => $laTmp[16],'CMONEDA' => $laTmp[17],'CCODANT' => $laTmp[18],'CUNIDAD' => $laTmp[19],
                             'CDESUNI' => $laTmp[20],'CDESCRI' => $laTmp[21],'CEMAIL'  => $laTmp[22],'CDIRECC' => $laTmp[23],
                             'CCODPRV' => $laTmp[24],'CCCOANT' => $laTmp[25],'CCTACNT' => $laTmp[26],'CCODPAR' => $laTmp[27],
                             'CDESPAR' => $laTmp[28],'CESTADO' => $laTmp[29],'CLUGAR' => $laTmp[30],'CMONCOR' => $laTmp[31],
                             'CDESMON' => $laTmp[32],'NMONTO'  => $laTmp[33],'DAFECTA' => $laTmp[34],'CUSUCOT' => $laTmp[35],
                             'CNOMCOT' => $laTmp[36],'CUSUGEN' => $laTmp[37],'CNOMGEN' => $laTmp[38]];
      }
      // RECUPERA FIRMAS DIGITALES
      $lcSql = "SELECT A.nSerial, A.cIdOrde, A.cCodUsu, B.cNombre, B.cGraAca, A.cNivel, TO_CHAR(A.tFirma, 'YYYY-MM-DD'),
                       B.nPosFiX, B.nPosFiY
                FROM E01DFIR A
                INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodUsu
                WHERE A.cIdOrde = '$lcIdOrde' AND A.cEstado = 'A'
                ORDER BY A.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = 'ERROR AL VALIDAR FIRMAS DIGITALES';
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laNombre = explode('/', $laTmp[3]);
         $lcNombre = $laNombre[2].' '.$laNombre[0].' '.$laNombre[1];
         $this->laFirmas[] = ['NSERIAL' => $laTmp[0], 'CIDORDE' => $laTmp[1], 'CCODUSU' => $laTmp[2], 'CNOMBRE' => $lcNombre,
                              'CGRAACA' => $laTmp[4], 'CNIVEL'  => $laTmp[5], 'DFIRMA'  => $laTmp[6], 'NPOSFIX' => $laTmp[7],
                              'NPOSFIY' => $laTmp[8]];
      }
      return true;
   }

    // --------------------------------------------------------
   // Detalle de nuevos activos
   // 2022-01-17 FPM Creacion
   // --------------------------------------------------------
   public function omDetalleNuevosActivos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleNuevosActivos($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxDetalleNuevosActivos($p_oSql) {
      $lcSql = "SELECT A.cCodArt, A.cDescri, A.cCenCos, A.cCenRes, TO_CHAR(A.dFecAdq, 'YYYY-MM-DD'), A.nAsiAdq, A.cNroRuc, A.cRazSoc,
                A.cMoneda, B.cDescri,A.nMonSol, A.nMonDol, A.nTipCam, A.nCantid, A.nSerFac, sum(I.debsol) AS debsol
                FROM F_E01DFAC_11('{$this->paData['CCODIOL']}') A
                LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '007' AND SUBSTRING(B.cCodigo, 1, 1) = A.cMoneda
                LEFT JOIN d10dasi J ON J.idasie = A.nAsiAdq    
                LEFT JOIN d10aasi I ON I.idasid = J.idasid
                GROUP BY A.cCodArt, A.cDescri, A.cCenCos, A.cCenRes, TO_CHAR(A.dFecAdq, 'YYYY-MM-DD'), A.nAsiAdq, A.cNroRuc, A.cRazSoc, 
                A.cMoneda, B.cDescri,A.nMonSol, A.nMonDol, A.nTipCam, A.nCantid, A.nSerFac";
      // print_r($lcSql); 
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         // Halla tipo de AF y cuenta cuantos AF han sido registrados por cCodArt
         $laDatos = [];
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE nSerFac = $laFila[14]";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $laDatos[] = $laTmp[0];
         }
         $lcTipAfj = '';
         $i = 0;
         $lnMonReg = 0;
         foreach ($laDatos as $lcActFij) {
            $lcSql = "SELECT cCodArt, cTipAfj, cActFij, cTipAfj, nCorrel, nMontmn FROM F_E04MAFJ_2('$lcActFij')";
            // print_r($lcSql);
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R2);
            if ($laTmp[0] == $laFila[0]) {   // cCodArt
               $lcTipAfj = $laTmp[1];
               $i += 1;  
               $lnMonReg = $lnMonReg + $laTmp[5];
            }
         }
         // Monto por unidad de AF
         $lnMonUni = round($laFila[10] / $laFila[13], 2);
         // print_r($lnMonUni);
         $this->paDatos[] = ['CCODART'=> $laFila[0],  'CDESCRI'=> $laFila[1],  'CCENCOS'=> $laFila[2],  'CCENRES'=> $laFila[3],  'DFECADQ'=> $laFila[4],
                             'NASIADQ'=> $laFila[5],  'CNRORUC'=> $laFila[6],  'CRAZSOC'=> $laFila[7],  'CMONEDA'=> $laFila[8],  'CDESMON'=> $laFila[9],
                             'NMONTMN'=> $laFila[10], 'NMONTME'=> $laFila[11], 'NTIPCAM'=> $laFila[12], 'NCANTID'=> $laFila[13], 'NSERFAC'=> $laFila[14],
                             'CTIPAFJ'=> $lcTipAfj,   'NMONUNI'=> $lnMonUni,   'NCANREG'=> $i, 'NMONTO'=> $laFila[15], 'NMONREG' =>$lnMonReg];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON NUEVOS ACTIVOS';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Cargar Clases Activo y Centros Costos
   // 2022-02-20 GCH Creacion
   // ------------------------------------------------------------------------
   public function omCargarClaseYCentroCosto() {
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
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarClaseYCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarClaseYCentroCosto($p_oSql) {
      // Cargar Clase Activo
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      return true;
   }

   // --------------------------------------------------------
   // Cargar Empleado AFJ1020
   // 2022-02-22 GCH Creacion
   // --------------------------------------------------------
   public function omCargarEmpleado() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarEmpleado($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxCargarEmpleado($p_oSql) {
      $lcSql = "SELECT cNombre, cNroDni, cEstado FROM V_S01TUSU_1 WHERE cCodUsu = '{$this->paData['p_cCodEmp']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = 'NO SE ENCONTRÓ CÓDIGO DE EMPLEADO';
         return false;
      }
      $lcNombre = str_replace('/', ' ', $laTmp[0]);
      $this->paData = ['CNOMBRE'=> $lcNombre, 'CNRODNI'=> $laTmp[1], 'CESTADO'=> $laTmp[2]];
      // print_r($this->paData);
      return true;
   }

   // --------------------------------------------------------
   // Registra nuevos activos fijos
   // 2022-02-23 FPM Creacion
   // --------------------------------------------------------
   public function omRegistrarNuevosActivosFijos() {
      // print_r($this->paData);
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['NCANTID']) or !preg_match('/^[0-9]+$/', $this->paData['NCANTID'])) {
         $this->pcError = 'CANTIDAD DE ACTIVOS FIJOS NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif ($this->paData['NCANTID'] <= 0) {
         $this->pcError = 'CANTIDAD DE ACTIVOS ES CERO O NEGATIVO';
         return false;
      }
      // $this->paData['CACTFIJ'] = '*';
      $llOk = $this->mxValParamGrabarActivoFijo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxVerProveedor($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      for ($i = 1; $i <= $this->paData['NCANTID']; $i++) {
         $llOk = $this->mxGrabarActivoFijo($loSql);
         if (!$llOk) {
            $loSql->rollback();
            $loSql->omDisconnect();
            return false;
          }
      }
      $loSql->omDisconnect();
      $this->paData = $this->laData;
      return true;
   }

   // --------------------------------------------------------
   // Graba activo fijo
   // 2022-01-17 FPM Creacion
   // --------------------------------------------------------
   public function omGrabarActivoFijo() {
      $llOk = $this->mxValParamGrabarActivoFijo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxVerProveedor($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGrabarActivoFijo($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarActivoFijo() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
         // elseif (!isset($this->paData['CMARCA']) or !preg_match('/^[0-9,A-Z, -_ÑÁÉÍÓÚ\/]{4,25}$/', $this->paData['CMARCA'])) {
      } elseif (!isset($this->paData['CMARCA'])) {
         $this->pcError = "MARCA [{$this->paData['CMARCA']}] NO DEFINIDA O INVÁLIDA";
         return false;
      // } elseif (!isset($this->paData['CMODELO']) or !preg_match('/^[0-9,A-Z, -_ÑÁÉÍÓÚ\/]{4,25}$/', $this->paData['CMODELO'])) {
      } elseif (!isset($this->paData['CMODELO'])) {
         $this->pcError = "MODELO [{$this->paData['CMODELO']}] NO DEFINIDO O INVÁLIDO";
         return false;
      // } elseif (!isset($this->paData['CCOLOR']) or !preg_match('/^[0-9,A-Z, -_ÑÁÉÍÓÚ\/]{4,25}$/', $this->paData['CCOLOR'])) {
      } elseif (!isset($this->paData['CCOLOR'])) {
         $this->pcError = 'COLOR NO DEFINIDO O INVÁLIDO';
         return false;
      // } elseif (!isset($this->paData['CPLACA']) or !preg_match('/^[0-9,A-Z, -_ÑÁÉÍÓÚ\/]{4,25}$/', $this->paData['CPLACA'])) {
      } elseif (!isset($this->paData['CPLACA']) ) {
         $this->pcError = 'PLACA NO DEFINIDA O INVÁLIDA';
         return false;
      // } elseif (!isset($this->paData['CNROSER']) or !preg_match('/^[0-9,A-Z,-]{4,25}$/', $this->paData['CNROSER'])) {
      } elseif (!isset($this->paData['CNROSER'])) {
         $this->pcError = 'NÚMERO DE SERIE NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CINDACT']) or !preg_match('/^[S,N]{1}$/', $this->paData['CINDACT'])) {
         $this->pcError = 'INDICADOR DE ACTIVO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCANTID'])) {
         $this->pcError = 'DEFINICIÓN DE CANTIDAD MARCA NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CSOBRAN'])) {
         $this->pcError = 'SOBRANTE NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCODART'])) {
         $this->pcError = 'CODIGO DE ARTÍCULO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CACTFIJ'])) {
         $this->pcError = 'ID DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CTIPAFJ'])) {
         $this->pcError = "TIPO DE ACTIVO FIJO [{$this->paData['CTIPAFJ']}] NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO'])) {
         $this->pcError = 'ESTADO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CDESCRI'])) {
         $this->pcError = 'DESCRIPCIÓN NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CSITUAC'])) {
         $this->pcError = 'SITUACIÓN NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CINDACT'])) {
         $this->pcError = 'INDICADOR DE AF NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENRES'])) {
         $this->pcError = 'CENTRO DE RESPONSABILIDAD NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCODEMP'])) {
         $this->pcError = 'CÓDIGO DE EMPLEADO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CNRORUC'])) {
         $this->pcError = 'RUC DE PROVEEDOR NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CRAZSOC'])) {
         $this->pcError = "RAZÓN SOCIAL NO DEFINIDA O INVÁLIDA [{$this->paData['CRAZSOC']}] ";
         return false;
      } elseif (!isset($this->paData['DFECALT'])) {
         $this->pcError = "FECHA DE ALTA [{$this->paData['DFECALT']}] NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['CDOCADQ'])) {
         $this->pcError = 'DOCUMENTO DE ADQUISICIÓN NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['NMONTMN'])) {
         $this->pcError = 'MONTO EN SOLES NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NMONTME'])) {
         $this->pcError = 'MONTO EN MONEDA EXTRANJERA NO DEFINIDO O INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CMONEDA'])) {
         $this->pcError = 'MONEDA NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CESTADO'])) {
         $this->pcError = 'ESTADO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MFOTOGR'])) {
         $this->pcError = 'ENLACE DE LA FOTOGRAFÍA NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif ($this->paData['CNRORUC'] == '00000000000' and $this->paData['CRAZSOC'] == '') {
         $this->pcError = 'RAZÓN SOCIAL DEL PROVEEDOR DEBE ESTAR DEFINIDO';
         return false;
      }
      return true;
   }

   // Verifica proveedor
   protected function mxVerProveedor($p_oSql) {
     if ($this->paData['CNRORUC'] == '00000000000') {
         return true;
      }
      $lcSql = "SELECT cNroRuc, cRazSoc FROM S01MPRV WHERE cNroRuc = '{$this->paData['CNRORUC']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = 'NÚMERO DE RUC NO EXISTE EN MAESTRO DE PROVEEDORES';
         return false;
      }
      // $this->paData['CRAZSOC'] = '';
      return true;
   }
   
   protected function mxGrabarActivoFijo($p_oSql) {
      $this->paData['NMONTMN'] = str_replace(',','',$this->paData['NMONTMN']);
      $this->paData['NMONTME'] = str_replace(',','',$this->paData['NMONTME']);
      $this->paData['CMARCA']  = strtoupper(trim($this->paData['CMARCA']));
      $this->paData['CMODELO']  = strtoupper(trim($this->paData['CMODELO']));
      $this->paData['CCOLOR']  = strtoupper(trim($this->paData['CCOLOR']));
      $this->paData['CPLACA']  = strtoupper(trim($this->paData['CPLACA']));
      $this->paData['CNROSER']  = strtoupper(trim($this->paData['CNROSER']));
      $this->paData['NASIADQ']  = strtoupper(trim($this->paData['NASIADQ']));
      $this->paData['CCODART']  = strtoupper(trim($this->paData['CCODART']));
      $this->paData['CDOCADQ']  = strtoupper(trim($this->paData['CDOCADQ']));
      $this->paData['CDOCREF']  = strtoupper(trim($this->paData['CDOCREF']));
      $this->paData['CMOTOR']  = strtoupper(trim($this->paData['CMOTOR']));
      $lmDatos = json_encode(['CMARCA'=> $this->paData['CMARCA'],   'CMODELO'=> $this->paData['CMODELO'], 'CCOLOR'=> $this->paData['CCOLOR'],
                              'CPLACA'=> $this->paData['CPLACA'],   'CNROSER'=> $this->paData['CNROSER'], 'CINDACT'=> $this->paData['CINDACT'],
                              'CCANTID'=> $this->paData['CCANTID'], 'CSOBRAN'=> $this->paData['CSOBRAN'], 'NASIADQ'=> $this->paData['NASIADQ'],
                              'CCODART'=> $this->paData['CCODART'], 'CDOCADQ'=> $this->paData['CDOCADQ'], 'CCODREF'=> $this->paData['CCODREF'],
                              'CMOTOR'=> $this->paData['CMOTOR']]);

      if ($this->paData['CACTFIJ'] == '*') {   // Nuevo AF
         // Siguiente codigo de AF
         $lcSql = "SELECT MAX(cActFij) FROM E04MAFJ";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $laTmp[0] = '00000';
         }
         $lcActFij = fxCorrelativo($laTmp[0]);
         // Correlativo
         $lcSql = "SELECT MAX(nCorrel) FROM E04MAFJ WHERE CTIPAFJ = '{$this->paData['CTIPAFJ']}';";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $lnCorrel = 0;
         } else {
            $lnCorrel = $laTmp[0];
         }
         $lnCorrel += 1;
         $this->paData['CDESCRI']  = strtoupper(trim($this->paData['CDESCRI']));
         //$this->paData['CCODOLD'] = $lCTIPAFJ.$lcNewCor; OJOFPM FALTA!
         $lcSql = "INSERT INTO E04MAFJ (cActFij, CTIPAFJ, nCorrel, cEstado,
                                        cDescri, cSituac, cIndAct, cCenRes, 
                                        cCodEmp, cNroRuc, cRazSoc, dFecAlt, 
                                        nMontMN, nMontME, cMoneda, nSerFac, 
                                        mDatos,  mFotogr, cCodOld, cUsuCod, nMoncal)
                              VALUES ('$lcActFij', '{$this->paData['CTIPAFJ']}', '$lnCorrel', '{$this->paData['CESTADO']}',
                                      '{$this->paData['CDESCRI']}', '{$this->paData['CSITUAC']}', '{$this->paData['CINDACT']}', '{$this->paData['CCENRES']}',
                                      '{$this->paData['CCODEMP']}', '{$this->paData['CNRORUC']}', '{$this->paData['CRAZSOC']}', '{$this->paData['DFECALT']}',
                                      '{$this->paData['NMONTMN']}', '{$this->paData['NMONTME']}', '{$this->paData['CMONEDA']}', '{$this->paData['NSERFAC']}',
                                      '$lmDatos' , '{$this->paData['MFOTOGR']}', '{$this->paData['CCODOLD']}', '{$this->paData['CUSUCOD']}', '{$this->paData['NMONTMN']}');";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = 'NO SE PUDO INSERTAR NUEVO ACTIVO FIJO';
            return false;
         }
         $laData = ['CACTFIJ'=> $this->paData['CACTFIJ'], 'CCODIGO'=> $lcCodigo];
      } else {   // Actualizacion de activo fijo
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cActFij = '{$this->paData['CACTFIJ']}';";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $this->pcError = "ID DE ACTIVO FIJO [{$this->paData['CACTFIJ']}] NO EXISTE";
            return false;
         }
         $this->paData['CDESCRI']  = strtoupper(trim($this->paData['CDESCRI']));
         $lcSql = "UPDATE E04MAFJ SET cEstado = '{$this->paData['CESTADO']}', cDescri = '{$this->paData['CDESCRI']}', cSituac = '{$this->paData['CSITUAC']}',                                         
                                      cIndAct = '{$this->paData['CINDACT']}', cCenRes = '{$this->paData['CCENRES']}', cCodEmp = '{$this->paData['CCODEMP']}',  
                                      cNroRuc = '{$this->paData['CNRORUC']}', cRazSoc = '{$this->paData['CRAZSOC']}', dFecAlt = '{$this->paData['DFECALT']}', 
                                      nMontMN = '{$this->paData['NMONTMN']}', nMontME = '{$this->paData['NMONTME']}', 
                                      cMoneda = '{$this->paData['CMONEDA']}', mDatos  = '$lmDatos',  mFotogr = '{$this->paData['MFOTOGR']}',
                                      cUsuCod = '{$this->paData['CUSUCOD']}', nMoncal = '{$this->paData['NMONTMN']}', tModifi = NOW()
                                      WHERE cActFij = '{$this->paData['CACTFIJ']}'";
         // print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO [{$this->paData['CACTFIJ']}]";
            return false;
         }
         $lcCodigo = $this->paData['CCODIGO']; 
         $laData = ['CACTFIJ'=> $this->paData['CACTFIJ'], 'CCODIGO'=> $lcCodigo];
      }
      $this->laData = $laData;
      return true;
   }

   // --------------------------------------------------------
   // Buscar codigo de Act Fij ya existente
   // 2022-11-07 GCM Creacion
   // --------------------------------------------------------
   public function omBuscarActFijExistente() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarActFijExistente($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxBuscarActFijExistente($p_oSql) {
      // print_r($this->paData);
      $lcCodigo = str_replace('-','',$this->paData['CCODIGO']);
      $lcTipAfj = substr($lcCodigo, 0, 5);
      $lnCorrel = substr($lcCodigo, 5);
      $lcSql = "SELECT cActFij FROM E04MAFJ where cTipAfj = '{$lcTipAfj}' AND  ncorrel = $lnCorrel";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         ;
      } else {
         $lcSql = "SELECT  A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, 
                           A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                           A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, 
                           A.cCodEmp, A.cNomEmp, A.cNroRuc, A.cRazSoc, 
                           A.dFecAlt, A.nMontMN, A.cPlaca, A.cCantid, 
                           A.cNroSer, A.cModelo, A.cColor, A.cMarca, B.cDescri, 
                           A.dFecAdq, A.cDocAdq, A.cDocAlt, A.dFecBaj, A.cDocBaj, A.cMotor, A.cCodArt, A.cCodRef
                  FROM F_E04MAFJ_2('$laTmp[0]') A
                  INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ ";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R1)) {
            $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[13]);
            $lcCodCla = substr($laTmp[1], 0, 2);
            $this->laData = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1],'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3],
                              'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],
                              'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],'CDESCEN'=> $laTmp[11],
                              'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $lcNomEmp, 'CNRORUC'=> $laTmp[14],'CRAZSOC'=> $laTmp[15],
                              'DFECALT'=> $laTmp[16], 'NMONTMN'=> $laTmp[17], 'CPLACA'=> $laTmp[18],'CCANTID'=> $laTmp[19],
                              'CNROSER'=> $laTmp[20], 'CMODELO'=> $laTmp[21], 'CCOLOR'=> $laTmp[22], 'CMARCA'=> $laTmp[23],
                              'CDESTIP'=> $laTmp[24], 'CCODIGO'=> $lcCodigo, 'CCODCLA' => $lcCodCla, 'DFECADQ'=> $laTmp[25],
                              'CDOCADQ'=> $laTmp[26], 'CDOCALT'=> $laTmp[27], 'DFECBAJ'=> $laTmp[28], 'CDOCBAJ'=> $laTmp[29],
                              'CMOTOR'=> $laTmp[30], 'CCODART'=> $laTmp[31], 'CCODREF'=> $laTmp[32]];
         }
      }
      if (count($this->laData) == 0) {
         $this->pcError = 'NO HAY REGISTROS PARA MOSTRAR';
         return false;
      }
      // print_r($this->laData);
      return true;
   }


   // ----------------------------------------
   // Cargar Reporte de activos fijos
   // Creación GCH 03-03-2022
   // ----------------------------------------
   public function omReporteActFij() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteActFij($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $llOk = $lo->omPrintReporteActFij();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      // print_r($this->paData['CREPORT']);
      return $llOk;
   }

   protected function mxReporteActFij($p_oSql) {
      $lcSerFac = $this->paData['nSerFac'];
      $lcSql = "SELECT cActFij FROM E04MAFJ WHERE nSerFac = {$lcSerFac} ORDER BY cActFij";
      // print_r($lcSql);
      $R2 = $p_oSql->omExec($lcSql);
      while ($laFila1 = $p_oSql->fetch($R2)){   
         $this->paData[] = ['CACTFIJ' => $laFila1[0]];
         if($this->paData){
            $lcSql = "SELECT cActFij, CTIPAFJ, nCorrel, cEstado, cDesEst, cSituAc, cDesSit, cDescri, cCenRes, cDesRes, 
                             cCenCos, cDesCen, cCodEmp, cNomEmp, cNroRuc, cRazSoc, dFecAlt, nMontmn, cCodArt, cCodRef
                        FROM f_e04mafj_2('$laFila1[0]')";
            // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            while ($laFila = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
               $this->paDatos[] = ['CACTFIJ' => $laFila[0],'CCODIGO' => $lcCodigo, 'CESTADO' => $laFila[3],
                                   'CDESEST' => $laFila[4],'CSITUAC' => $laFila[5],'CDESSIT' => $laFila[6], 'CDESCRI' => $laFila[7],
                                   'CCENRES' => $laFila[8],'CDESRES' => $laFila[9],'CCENCOS' => $laFila[10], 'CDESCEN' => $laFila[11],
                                   'CCODEMP' => $laFila[12],'CNOMEMP' => $laFila[13],'CNRORUC' => $laFila[14], 'CRAZSOC' => $laFila[15], 
                                   'DFECALT' => $laFila[16],'NMONTMN'=> $laFila[17], 'CCODART'=> $laFila[18],
                                   'CCODREF'=> $laFila[19]];
            }
         }  
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR REPORTE ACTIVO FIJO';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga tipos de activo fijo
   // 2022-02-21 GCM Creacion
   // ------------------------------------------------------------------------
   public function omTiposActivoFijo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTiposActivoFijo($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxTiposActivoFijo($p_oSql) {
      $lcSql = "SELECT CTIPAFJ, cDescri FROM E04TTIP WHERE cClase = '{$this->paData['CCLASE']}' AND cEstado = 'A' ORDER BY CTIPAFJ";
      // print_r($_SESSION['paEmple']);
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CTIPAFJ'=> $laFila[0], 'CDESCRI'=> $laFila[1]]; 
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON TIPOS DE ACTIVO FIJO PARA CLASE';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------------------
   // INICIO PANTALLA 2060 -  REPOTE POR CENTRO DE RESPONSABILIDAD
   // ------------------------------------------------------------------------
   // Init para reporte por centros de responsabilidad
   // 2022-02-03 FPM Creacion
   // ------------------------------------------------------------------------
   public function omInitReporteCentroResponsabilidad() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitReporteCentroResponsabilidad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitReporteCentroResponsabilidad($p_oSql) {
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      // $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE cEstado = 'A' AND 
               //  cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga centros de responsabilidad de centro de costo
   // 2022-02-03 FPM Creacion
   // ------------------------------------------------------------------------
   public function omCargarCentroResponsabilidad() {
      $llOk = $this->mxValParamCargarCentroResponsabilidad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCargarCentroResponsabilidad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamCargarCentroResponsabilidad() {
      if (!isset($this->paData['CCENCOS']) or !preg_match("/[0-9,A-Z]{3}/", $this->paData['CCENCOS'])) {
      //if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarCentroResponsabilidad($p_oSql) {
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO EXISTE";
         return false;
      } 
      $lcSql = "SELECT DISTINCT(A.cCenRes), A.cEstado, A.cDescri FROM S01TRES A
                 --INNER JOIN E04MAFJ B ON B.cCenRes = A.cCenRes
                 WHERE A.cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENRES'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      return true;
   }
   
   // ------------------------------------------------------------------------
   // Reporte de Activos Fijos por centros de responsabilidad
   // 2022-02-03 FPM Creacion
   // ------------------------------------------------------------------------
   public function omReporteCentroResponsabilidad() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxReporteCentroResponsabilidad($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $lo = new CRepControlPatrimonial();
      // $lo->paDatos = $laDatos;
      $lo->paData  = $this->laData;
      $llOk = $lo->omPrintRepActivos();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxReporteCentroResponsabilidad($p_oSql) {
      // print_r($this->paData);
      $laDatos = [];
      $lcSql = "SELECT A.cCenRes, A.cEstado, A.cDescri, A.cCenCos, B.cDescri, B.cEstado, B.cNivel FROM S01TRES A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.cCenRes = '{$this->paData['CCENRES']}'";         
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE RESPONSABILIDAD [{$this->paData['CCENRES']}] NO EXISTE";
         return false;
      }
      $laData = ['CCENRES'=> $laTmp[0], 'CESTRES'=> $laTmp[1], 'CDESRES'=> $laTmp[2], 'CCENCOS'=> $laTmp[3], 'CDESCRI'=> $laTmp[4],
                 'CESTADO'=> $laTmp[5], 'CNIVEL'=> $laTmp[6], 'DATOS'=> ''];
      $lcSql ="SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, 
               TO_CHAR(A.dFecAlt, 'YYYY-MM-DD'), A.nMontMN, B.cCodUsu, B.cNombre, A.cSituac
               FROM E04MAFJ A 
               INNER JOIN V_S01TUSU_1 B ON A.cCodEmp = B.cCodUsu
               WHERE cCenRes = '{$this->paData['CCENRES']}' 
               ORDER BY B.cCodUsu,A.CTIPAFJ, A.nCorrel";
      //print_r($lcSql);
      // echo $lcSql.'<br>';
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2).'-'.substr('00000'.strval($laTmp[2]), -6);
         $lcNombre = str_replace('/', ' ', $laTmp[8]);
         $laDatos[] = ['CACTFIJ'=> $laTmp[0], 'CCODIGO'=> $lcCodigo, 'CDESCRI'=> $laTmp[3], 'CESTADO'=> $laTmp[4], 
                     'DADQUIS'=> $laTmp[5], 'NMONTMN'=> $laTmp[6], 'CCODEMP'=> $laTmp[7], 'CNOMBRE'=> $lcNombre, 'CSITUAC'=> $laTmp[9]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO TIENE ACTIVOS FIJOS";
         return false;
      }
      $laData['DATOS'] = $laDatos;
      $this->laData = $laData;
      return true;
   }
   // -----------------------------------------------------------------------
   // PANTALA AFJ1080 - COMPONENTES
   // ------------------------------------------------------------------------
   // Init mantenimiento de componentes
   // 2022-02-04 FPM Creacion
   // ------------------------------------------------------------------------
   public function omInitMtoComponentes() {
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
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitMtoComponentes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMtoComponentes($p_oSql) {
      // Estado del AF
      $laEstAct = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '333'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laEstAct[] = ['CESTADO'=> $laFila[0], 'CDESCRI'=> $laFila[1]]; 
      }
      if (count($laEstAct) == 0) {
         $this->pcError = 'NO HAY ESTADOS DE ACTIVO FIJO DEFINIDOS [313]';
         return false;
      }      
      // Situacion del AF
      $laSituac = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($laSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      $this->paData = ['AESTACT'=> $laEstAct, 'ASITUAC'=> $laSituac];
      return true;
   }

   // ------------------------------------------------------------------------
   // Aplicar codigo de activo fijo
   // 2022-02-04 FPM Creacion
   // ------------------------------------------------------------------------
   public function omAplicarMtoComponentes() {
      $llOk = $this->mxValParamAplicarMtoComponentes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAplicarMtoComponentes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamAplicarMtoComponentes() {
      if (!$this->mxValParam()) {
         return false;
      } elseif (!isset($this->paData['CCODIGO']) or !preg_match("/[0-9]{5}/", $this->paData['CCODIGO'])) {
         $this->pcError = "TIPO DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO";
         return false;
      } 
      return true;
   }

   protected function mxAplicarMtoComponentes($p_oSql) {
      // $this->paData['CTIPAFJ'] = $this->paData['CTIPAFJ1'];
      $this->paData['CCODIGO'] = str_replace('-','',$this->paData['CCODIGO']);
      $lcTipAfj = substr($this->paData['CCODIGO'], 0, 5);
      $lnCorrel = substr($this->paData['CCODIGO'], 5);
      $laDatos = [];
      // Datos del activo fijo
      $lcSql = "SELECT A.cActFij, A.CTIPAFJ, C.cDescri, A.cSituac, E.cDescri, A.cDescri, A.cIndAct, A.cCenRes, B.cDescri, 
                  A.cUsuCod, D.cNombre, A.cRazSoc, A.dFecAlt, A.nCorrel
                  FROM E04MAFJ A INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes 
                  INNER JOIN E04TTIP C ON C.CTIPAFJ = A.CTIPAFJ 
                  INNER JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cUsuCod
                  LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '334' AND SUBSTRING(E.cCodigo, 1, 1) = A.cSituac
                  WHERE A.CTIPAFJ = '$lcTipAfj' AND A.nCorrel = $lnCorrel";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CÓDIGO DE ACTIVO FIJO [{$this->paData['CCODIGO']}] NO EXISTE";
         return false;
      }
      $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2).'-'.substr('00000'.strval($laTmp[13]), -6);
      $laData = ['CCODIGO'=> $lcCodigo, 'CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1].' - '.$laTmp[2], 'CSITUAC'=> $laTmp[3].' - '.$laTmp[4],
                 'CDESCRI'=> $laTmp[5], 'CINDACT'=> $laTmp[6], 'CCENRES'=> $laTmp[7].' - '.$laTmp[8], 'CCODUSU'=> $laTmp[9].' - '.$laTmp[10],
                 'CPROVED'=> $laTmp[11], 'DFECALT' =>$laTmp[12]  ,'DATOS'=> ''];
      $this->paData = $laData;
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga componentes
   // 2022-04-04 GCH Creacion
   // ------------------------------------------------------------------------
   public function omBuscarComponente() {
      $llOk = $this->mxValParamBuscarComponentes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarComponente($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarComponentes() {
      if (!$this->mxValParam()) {
         return false;
      } elseif (!isset($this->paData['CACTFIJ'])) {
         $this->pcError = "TIPO DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO";
         return false;
      } 
      return true;
   }

   protected function mxBuscarComponente($p_oSql) {
      $lcSql = "SELECT nSerial, cActFij, nSecuen, cDescri, cIndAct, cSituac, cDesSit, TO_CHAR(dFecAlt, 'YYYY-MM-DD'), 
                  TO_CHAR(dRetiro, 'YYYY-MM-DD'), nMonto, cIndExt, cEtique, cDocAdq, cDocRet, cCantid, cNroSer, cPlaca,
                  cModelo, cColor, cMarca, cEstado, cUsuCod 
                FROM F_E04DCOM_1('{$this->paData['CACTFIJ']}')";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['NSERIAL'=> $laTmp[0], 'CACTFIJ'=> $laTmp[1], 'NSECUEN'=> $laTmp[2], 'CDESCRI'=> $laTmp[3], 'CINDACT'=> $laTmp[4], 'CSITUAC'=> $laTmp[5], 
                       'CDESSIT'=> $laTmp[6], 'DFECADQ'=> $laTmp[7], 'DRETIRO'=> $laTmp[8], 'NMONTO'=> $laTmp[9], 'CINDEXT'=> $laTmp[10], 'CETIQUE'=> $laTmp[11],
                       'CDOCADQ'=> $laTmp[12], 'CDOCRET'=> $laTmp[13], 'CCANTID'=> $laTmp[14], 'CNROSER'=> $laTmp[15], 'CPLACA'=> $laTmp[16], 'CMODELO'=> $laTmp[17],
                       'CCOLOR'=> $laTmp[18], 'CMARCA'=> $laTmp[19], 'CESTADO'=> $laTmp[20], 'CUSUCOD'=> $laTmp[21]];
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Grabar componente
   // 2022-02-04 FPM Creacion
   // ------------------------------------------------------------------------
   public function omGrabarComponente() {      
      $llOk = $this->mxValParamGrabarComponente();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGrabarComponente($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
      echo "4444";
   }

   protected function mxValParamGrabarComponente() {
      // print_r($this->paData[0]['NSERIAL']);
      $loDate = new CDate();
      if (!$this->mxValParam()) {
         return false;
      } 
      $laDatos = $this->paDatos;
      foreach ($laDatos as $laTmp) {
         if (!isset($laTmp['NSERIAL'])) {
            $this->pcError = "SERIAL NO DEFINIDO O INVÁLIDO";
            return false;
         } elseif (!isset($laTmp['CACTFIJ'])) {
            $this->pcError = "ACTIVO FIJO NO DEFINIDO O INVÁLIDO";
            return false;
         } elseif (!isset($laTmp['CDESCRI'])) {
            $this->pcError = "DESCRIPCIÓN NO DEFINIDA O INVÁLIDA";
            return false;
         } elseif (!isset($laTmp['CINDACT']) or !preg_match('/^[S,N]{1}$/', $laTmp['CINDACT'])) {
            $this->pcError = 'INDICADOR DE ACTIVO NO DEFINIDO O INVÁLIDO';
            return false;
         } elseif (!isset($laTmp['CSITUAC'])) {
            $this->pcError = 'SITUACIÓN NO DEFINIDA O INVÁLIDA';
            return false;
         } elseif (!isset($laTmp['DFECADQ'])) {
            $this->pcError = "FECHA DE ALTA DEL COMPONENTE NO DEFINIDA O INVÁLIDA";
            return false;
         } elseif (!isset($laTmp['NMONTO'])) {
            $this->pcError = "MONTO VALOR DEL COMPONENTE NO DEFINIDO O INVÁLIDO";
            return false;
         } elseif (!isset($laTmp['CCANTID'])) {
            $this->pcError = "CANTIDAD NO DEFINIDA";
            return false;
         }
         // print_r($this->paData);
         // Nuevo
         if ($laTmp['NSERIAL'] == -1) {
            if (!isset($laTmp['DRETIRO']) or $laTmp['DRETIRO'] == '') {
               $laTmp['DRETIRO'] = '1800-01-01';
            } elseif ($loDate->valDate($laTmp['DRETIRO'])) {
               $this->pcError = "FECHA DE RETIRO ESTÁ DEFINIDA";
               return false;
            } elseif (!isset($laTmp['CDOCRET'])) {
               $laTmp['CDOCRET'] = '';
            } elseif ($laTmp['CDOCRET'] != '') {
               $this->pcError = "DOCUMENTO DE RETIRO ESTÁ DEFINIDO";
               return false;
            } elseif (!isset($laTmp['CESTADO'])) {
               $this->pcError = 'ESTADO NO DEFINIDA O INVÁLIDA';
               return false;
            } elseif (!isset($laTmp['CDOCUME'])) {
               $this->pcError = "DOCUMENTO DE REFERENCIA DEL COMPONENTE NO DEFINIDO O INVÁLIDO";
               return false;
            }
         }
         // Retiro
         if ($laTmp['NSERIAL'] > 0 and $laTmp['CSITUAC'] == 'B') {
            if (!isset($laTmp['DRETIRO']) or !$loDate->valDate($laTmp['DRETIRO'])) {
               $this->pcError = "FECHA DE RETIRO NO ESTÁ DEFINIDA";
               return false;
            } elseif (!isset($laTmp['CDOCRET']) or $laTmp['CDOCRET'] == '') {
               $this->pcError = "DOCUMENTO DE RETIRO NO ESTÁ DEFINIDO";
               return false;
            }
         }   
      }
      return true;  
   }

   protected function mxGrabarComponente($p_oSql) {
      $laData = $this->paDatos;
      foreach($laData as $laDatos){
         $laDatos['CDESCRI']  = strtoupper(trim($laDatos['CDESCRI']));
         $laDatos['NMONTO'] = str_replace(',','',$laDatos['NMONTO']);
         $laDatos['CMARCA']  = strtoupper(trim($laDatos['CMARCA']));
         $laDatos['CCANTID']  = strtoupper(trim($laDatos['CCANTID']));
         $laDatos['CMODELO']  = strtoupper(trim($laDatos['CMODELO']));
         $laDatos['CCOLOR']  = strtoupper(trim($laDatos['CCOLOR']));
         $laDatos['CPLACA']  = strtoupper(trim($laDatos['CPLACA']));
         $laDatos['CNROSER']  = strtoupper(trim($laDatos['CNROSER']));
         $laDatos['CDOCADQ']  = strtoupper(trim($laDatos['CDOCADQ']));
         $laDatos['MDATOS'] = json_encode(['CMARCA'=> $laDatos['CMARCA'], 'CCOLOR'=> $laDatos['CCOLOR'], 'CNROSER'=> $laDatos['CNROSER'], 'CMODELO'=> $laDatos['CMODELO'],
                                          'CCANTID'=> $laDatos['CCANTID'], 'CDOCADQ'=> $laDatos['CDOCADQ'], 'CPLACA'=> $laDatos['CPLACA']]);
         if ($laDatos['NSERIAL'] == -1) {
            $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cActFij = '{$laDatos['CACTFIJ']}'";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or !$laTmp[0]) {
               $this->pcError = "ACTIVO FIJO [{$laDatos['CACTFIJ']}] NO EXISTE";
               return false;
            }
            $lcSql = "SELECT MAX(NSECUEN) FROM E04DCOM WHERE cActFij = '{$laDatos['CACTFIJ']}'";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or !$laTmp[0]) {
               $laDatos['NSECUEN'] = 1;
            } else {
               $laDatos['NSECUEN'] = $laTmp[0] + 1;
            }
            $lcSql = "INSERT INTO E04DCOM (cActFij, cEstado ,nSecuen, cDescri, cIndAct, cSituac, dFecAlt, nMonto, cIndExt, cEtique, mDatos, cCodOld, cUsuCod) VALUES
                      ('{$laDatos['CACTFIJ']}', '{$laDatos['CESTADO']}', {$laDatos['NSECUEN']}, '{$laDatos['CDESCRI']}', '{$laDatos['CINDACT']}', '{$laDatos['CSITUAC']}',
                       '{$laDatos['DFECADQ']}', {$laDatos['NMONTO']}, '{$laDatos['CINDEXT']}', '{$laDatos['CETIQUE']}', '{$laDatos['MDATOS']}', ' ', '{$this->paData['CUSUCOD']}')";
            //print_r($lcSql);
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = "NO SE PUDO INSERTAR NUEVO COMPONENTE";
               return false;
            }
         } else {
            $lcSql = "SELECT nSerial FROM E04DCOM WHERE nSerial = '{$laDatos['NSERIAL']}'";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or !$laTmp[0]) {
               $this->pcError = "SERIAL DE COMPONENTE [{$laDatos['NSERIAL']}] NO EXISTE";
               return false;
            }
            $lcSql = "UPDATE E04DCOM SET cEstado = '{$laDatos['CESTADO']}', cDescri = '{$laDatos['CDESCRI']}', cIndAct = '{$laDatos['CINDACT']}', cSituac = '{$laDatos['CSITUAC']}',
                      dFecAlt = '{$laDatos['DFECADQ']}', nMonto = {$laDatos['NMONTO']}, cIndExt = '{$laDatos['CINDEXT']}', cUsuCod = '{$laDatos['CUSUCOD']}', tModifi = NOW()
                      WHERE nSerial = {$laDatos['NSERIAL']}";
            /* print_r($lcSql); */
            $llOk= $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = 'NO SE PUDO ACTUALIZAR COMPONENTE';
               return false;
            }
         }   
      }
      //$this->paData = ['OK'=> 'OK'];
      return true;
   }

   // --------------------------------------------------------
   //Inicio AFJ1090 BUSCAR POR CODIGO ARTICULO
   // --------------------------------------------------------
   // Init mantenimiento Codigo 
   // 2022-05-23 GCH Creacion
   // --------------------------------------------------------
   public function omInitMtoCodArt() {
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
      $llOk = $this->mxValParamUsuario($loSql, '000');
      $loSql->omDisconnect();
      return $llOk;
   }

   // ------------------------------------------------------------------------
   // Buscar Codigo
   // 2022-05-23 GCH Creacion
   // ------------------------------------------------------------------------
   public function omBuscarCodigo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarCodigo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarCodigo($p_oSql) {
      $this->paData['CCODART']  = strtoupper(trim($this->paData['CCODART']));
      $lcSql = "SELECT A.cAsient, A.cDescri , A.cNroCom, A.cNroRuc, A.cIdOrde, A.nMonto, A.cCodiOl, A.dFecEmi
                  FROM E01MFAC A
                  INNER JOIN E01DFAC B ON B.cIdComp = A.cIdComp 
                  WHERE B.cCodArt = '{$this->paData['CCODART']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laTmp[0] = str_replace(' ','',$laTmp[0]);
         if(strlen($laTmp[0]) < 4){
            $lcAsient = $laTmp[0];
         } else {
            $lcAsient = substr($laTmp[0], 4, 8);
         }
         $this->paDatos[] = ['CASIENT'=> $lcAsient, 'CDESCRI'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'CNRORUC'=> $laTmp[3],
                           'CIDORDE'=> $laTmp[4], 'NMONTO'=> $laTmp[5], 'CCODIOL'=> $laTmp[6], 'DFECEMI'=> $laTmp[7]];
      }
      if (count($this->paDatos) === 0) {
       $this->pcError = "NO SE ENCONTRO CODIGO";
        return false;
      }

      return true;
   }

   // ------------------------------------------------------------------------
   // Reporte ACTIVOS FIJOS
   // 2022-08-04 GCH Creacion
   // ------------------------------------------------------------------------
   public function omReporteActivosFijo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxReporteActivosFijo($loSql);
      $loSql->omDisconnect();
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $llOk = $lo->mxPrintReporteActivoFijo();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxReporteActivosFijo($p_oSql) {
      $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cEstado, A.cDescri, A.cCenRes, B.cDescri, A.cCodEmp, C.cNombre, A.dFecAlt, A.nMontmn, A.mDatos 
                  FROM E04MAFJ A 
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE mDatos like '%{$this->paData['CASIENT']}%' OR mDatos like '%{$this->paData['CCODART']}%' ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
         $lcNomEmp = str_replace('/',' ',$laTmp[8]);
         $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2], 'CESTADO'=> $laTmp[3],
                             'CDESCRI'=> $laTmp[4], 'CCENRES'=> $laTmp[5], 'CDESRES'=> $laTmp[6], 'CCODEMP'=> $laTmp[7], 
                             'CNOMEMP'=> $lcNomEmp, 'DFECALT'=> $laTmp[9], 'NMONTO'=> $laTmp[10], 'CDATOS' => json_decode($laTmp[11], true), 
                             'CCODIGO'=>$lcCodigo];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO SE ENCONTRON ACTIVOS FIJOS";
        return false;
      }
      return true;
   }

   // --------------------------------------------------------
   // Afj1090 - ORDEN PARA EL REPORTE
   // --------------------------------------------------------
   public function omReportOCS() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintOCS_($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->laDatos;
      $laFirmas = $this->laFirmas;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $lo->laFirmas = $laFirmas;
      $llOk = $lo->mxPrintReportOCS_();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // --------------------------------------------------------
   //Inicio AFJ1060 - GASTOS
   // --------------------------------------------------------
   // Init mantenimiento gastos del AF
   // 2022-02-25 FPM Creacion
   // --------------------------------------------------------
   public function omInitMtoGastos() {
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
      $llOk = $this->mxValParamUsuario($loSql, '000');
      $loSql->omDisconnect();
      return $llOk;
   }

   // --------------------------------------------------------
   // Buscar AFs por codigo / descripcion
   // 2022-02-25 FPM Creacion
   // --------------------------------------------------------
   public function omBuscarActivoFijo() {
      $llOk = $this->mxValParamBuscarActivoFijo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarActivoFijo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarActivoFijo() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['CCODDES'])) {
         $this->pcError = "CODIGO/DESCRIPCIÓN NO DEFINIDO";
         return false;
      }
      $this->paData['CCODDES'] = strtoupper(trim($this->paData['CCODDES']));
      $this->paData['CCODDES'] = str_replace('  ', ' ', $this->paData['CCODDES']);
      $this->paData['CCODDES'] = str_replace(' ', '%', $this->paData['CCODDES']);
      if (!preg_match('/^[0-9,A-Z, -_ÑÁÉÍÓÚ\/]{4,25}$/', $this->paData['CCODDES']) or strpos($this->paData['CCODDES'], '%%')) {
         $this->pcError = "CODIGO/DESCRIPCIÓN INVÁLIDO";
         return false;
      }
      return true;
   }

    protected function mxBuscarActivoFijo($p_oSql) {
      // Por codigo
      if (strlen($this->paData['CCODDES']) > 5) {
         $this->paData['CCODDES'] = str_replace('-','',$this->paData['CCODDES'] );
         $lcTipAfj = substr($this->paData['CCODDES'], 0, 5);
         $lnCorrel = substr($this->paData['CCODDES'], 5);
         if ($lnCorrel > 0) {
            $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or count($laTmp) == 0) {
               ;
            } else {
               // $lcSql = "SELECT cActFij, cDescri, cEstado, CTIPAFJ, nCorrel FROM F_E04MAFJ_2('$laTmp[0]')";
               $lcSql = "SELECT A.cActFij, A.cDescri, A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, A.CTIPAFJ, A.nCorrel, B.cDescri
                         FROM F_E04MAFJ_2('$laTmp[0]') A
                         INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ";
               // print_r($lcSql);
               $R1 = $p_oSql->omExec($lcSql);
               $laTmp = $p_oSql->fetch($R1);
               $lcCodigo = substr($laTmp[6], 0, 2).'-'.substr($laTmp[6], 2, 5).'-'.right('00000'.strval($laTmp[7]), 6);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'CDESEST'=> $laTmp[3], 'CSITUAC'=> $laTmp[4],
                                   'CDESSIT'=> $laTmp[5], 'CCODIGO'=> $lcCodigo, 'CDESTIP'=> $laTmp[8]];
               return true;
            }
         }
      }
      // Por descripcion
      $lcCodDes = str_replace(' ','%',$this->paData['CCODDES']).'%';
      $laDatos = [];
      $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cDescri LIKE '$lcCodDes' ORDER BY cDescri LIMIT 100";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = $laTmp[0];
      }
      if (count($laDatos) == 0) {
         $this->pcError = 'NO HAY ACTIVOS FIJOS CON CRITERIO DEFINIDO';
         return false;
      }
      foreach ($laDatos as $lcActFij) {
         $lcSql = "SELECT A.cActFij, A.cDescri, A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, A.CTIPAFJ, A.nCorrel, B.cDescri
                   FROM F_E04MAFJ_2('$lcActFij') A
                   INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         $lcCodigo = substr($laTmp[6],0,2).'-'.substr($laTmp[6],2,5).'-'.right('00000'.strval($laTmp[7]), 6);
         $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'CDESEST'=> $laTmp[3], 'CSITUAC'=> $laTmp[4],
                             'CDESSIT'=> $laTmp[5], 'CCODIGO'=> $lcCodigo, 'CDESTIP'=> $laTmp[8]];
      }
      return true;
   }

   // --------------------------------------------------------
   // Cargar datos y gastos de un AF
   // 2022-02-25 FPM Creacion
   // --------------------------------------------------------
   public function omGastosActivoFijo() {
      $llOk = $this->mxValParamGastosActivoFijo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGastosActivoFijo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGastosActivoFijo() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['CACTFIJ']) or !preg_match('/^[0-9,A-Z]{5}$/', $this->paData['CACTFIJ'])) {
         $this->pcError = "ID DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxGastosActivoFijo($p_oSql) {
      // Carga AF
      $lcSql = "SELECT cActFij, cDescri, cEstado, CTIPAFJ, nCorrel FROM F_E04MAFJ_2('{$this->paData['CACTFIJ']}')";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = "ID DE ACTIVO FIJO NO EXISTE";
         return false;
      }
      $lcCodigo = $laTmp[3].right('00000'.strval($laTmp[4]), 6);
      $this->paData = ['CCODIGO'=> $lcCodigo, 'CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'DATOS'=> ''];
      // Carga gastos
      $laDatos = [];
      $lcSql = "SELECT A.nSerial, A.cCodFor, C.cDescri, A.cEstado, A.dFecha, A.nMonto 
                  FROM E04DGAS A 
                  INNER JOIN D01MFOR B ON B.cCodFor = A.cCodFor
                  INNER JOIN D01MCTA C ON C.cCtaCnt = B.cCtaCnt 
                  WHERE A.cActFij = '{$this->paData['CACTFIJ']}' order by A.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laFila = ['NSERIAL'=> $laTmp[0], 'CCODFOR'=> $laTmp[1], 'CDESCRI'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'DFECHA'=> $laTmp[4],
                    'NMONTO'=> $laTmp[5]];
         $laDatos[] = $laFila;
      }
      // print_r($laDatos);
      $this->paData['DATOS'] = $laDatos;
      return true;
   }

   // --------------------------------------------------------
   // Cargar tipo de gasto
   // 2022-02-28 FPM Creacion
   // --------------------------------------------------------
   public function omTipoGasto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTipoGasto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxTipoGasto($p_oSql) {
      // Carga gastos
      $laDatos = [];
      $lcSql = "SELECT A.cCodFor, A.cCtaCnt||' - '||B.cDescri 
                  FROM D01MFOR A INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt 
                  WHERE A.cModulo = '00I' AND A.cEstado = 'A' ORDER BY A.cCtaCnt";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODFOR'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      return true;
   }
   
   // --------------------------------------------------------
   // Grabar gastos de AF
   // 2022-02-25 FPM Creacion
   // --------------------------------------------------------
   public function omGrabarGastosAF() {
      $llOk = $this->mxValParamGrabarGastosAF();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxGrabarGastosAF($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarGastosAF() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['CACTFIJ']) or !preg_match('/^[0-9,A-Z]{5}$/', $this->paData['CACTFIJ'])) {
         $this->pcError = "ID DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO";
         return false;
      }   // OJOFPM FALTA VALIDAR PADATOS
      return true;
   }

   protected function mxGrabarGastosAF($p_oSql) {
      $laDatos = $this->paData['DATOS'];
      foreach ($laDatos as $laFila) {
         if ($laFila['NSERIAL'] > 0) {
            continue;
         }
         $lcSql = "INSERT INTO E04DGAS (cActFij, cCodFor, cEstado, dFecha, nMonto, cUsuCod) VALUES
                   ( '{$this->paData['CACTFIJ']}', '{$laFila['CCODFOR']}', '{$laFila['CESTADO']}', '{$laFila['DFECHA']}',
                    {$laFila['NMONTO']}, '{$this->paData['CUSUCOD']}')";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR GASTO";
            return false;
         }
      }
      return true;
   }

   //-------------------------------------------------------- 
   // INICIO PANTALLA 1110 - Buscar Act Fij, EDITAR O NUEVO
   // --------------------------------------------------------
   // Buscar AFs por codigo / descripcion
   // 2022-06-27 GCH Creacion
   // --------------------------------------------------------
   public function omMantActivoFijo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxMantActivoFijo($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxMantActivoFijo($p_oSql) {
      // Estado del AF
      $laEstAct = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '333'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laEstAct[] = ['CESTADO'=> $laFila[0], 'CDESCRI'=> $laFila[1]]; 
      }
      if (count($laEstAct) == 0) {
         $this->pcError = 'NO HAY ESTADOS DE ACTIVO FIJO DEFINIDOS [313]';
         return false;
      }      
      // Situacion del AF
      $laSituac = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($laSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      // Centros de costo
      $lcSql = "SELECT SUBSTR(cCenCos,1,3), cDescri FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laCenCos[] = ['CCENCOS'=> substr($laTmp[0],0,3), 'CDESCRI'=> $laTmp[1]];
         // $laCenCos[] = [substr($laTmp[0],0,3), $laTmp[1]];
      }
      if (count($laCenCos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      // Clases
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
      }
      if (count($laDatClaAfj) == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      // Tipo de activo
      $lcSql = "SELECT cTipAfj, cDescri, cEstado FROM E04TTIP WHERE cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laTipAfj[] = ['CTIPAFJ' => $laFila[0], 'CDESTIP' => $laFila[1], 'CESTADO' => $laFila[2]]; 
         $i++;
      }
      if (count($laTipAfj) == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      $this->paData = ['AESTACT'=> $laEstAct, 'ASITUAC'=> $laSituac, 'CCENCOS'=> $laCenCos, 'CCODCLA'=> $laDatClaAfj, 
                       'CTIPAFJ' =>$laTipAfj];
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga centros de responsabilidad por centro de costo
   // 2022-09-22 FPM Creacion
   // ------------------------------------------------------------------------
   public function omCentrosResponsabilidad() {
      $llOk = $this->mxValParamCargarCentroResponsabilidad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCentrosResponsabilidad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCentrosResponsabilidad($p_oSql) {
      // print_r('hola'.$this->paData);
      $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO EXISTE";
         return false;
      }  
      $laData = ['CCENCOS'=> $laTmp[0], 'CDESCEN'=> $laTmp[1], 'ACENRES'=> ''];
      $laDatos = [];
      $lcSql = "SELECT cCenRes, cDescri FROM S01TRES WHERE cCenCos = '{$this->paData['CCENCOS']}' and cEstado = 'A' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCENRES'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      $laData['ACENRES'] = $laDatos;
      $this->paData = $laData;
      return true;
   }

   // --------------------------------------------------------
   // Buscar AFs por codigo / descripcion
   // 2022-06-27 GCH Creacion
   // --------------------------------------------------------
   public function omBuscarActiFijo() {
      $llOk = $this->mxValParamBuscarActFij();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarActFijEdit($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxBuscarActFijEdit($p_oSql) {
      // Por codigo
      if (strlen($this->paData['CCODDES']) > 5) {
         $lcTipAfj = substr($this->paData['CCODDES'], 0, 5);
         $lnCorrel = substr($this->paData['CCODDES'], 5);
         if ($lnCorrel > 0) {
            $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or count($laTmp) == 0) {
               ;
            } else {
               $lcSql = "SELECT  A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, 
                                 A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                                 A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, 
                                 A.cCodEmp, A.cNomEmp, A.cNroRuc, A.cRazSoc, 
                                 A.dFecAlt, A.nMontMN, A.cPlaca, A.cCantid, 
                                 A.cNroSer, A.cModelo, A.cColor, A.cMarca, B.cDescri, 
                                 A.dFecAdq, A.cDocAdq, A.cDocAlt, A.dFecBaj, A.cDocBaj, A.cMotor, A.cCodArt, A.cCodRef
                         FROM F_E04MAFJ_2('$laTmp[0]') A
                         INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ 
                         WHERE A.cEstado != 'X'";
               // print_r($lcSql);
               $R1 = $p_oSql->omExec($lcSql);
               while ($laTmp = $p_oSql->fetch($R1)) {
                  $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
                  $lcNomEmp = str_replace('/',' ',$laTmp[13]);
                  $lcCodCla = substr($laTmp[1], 0, 2);
                  $this->paDatos = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1],'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3],
                                    'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],
                                    'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],'CDESCEN'=> $laTmp[11],
                                    'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $lcNomEmp, 'CNRORUC'=> $laTmp[14],'CRAZSOC'=> $laTmp[15],
                                    'DFECALT'=> $laTmp[16], 'NMONTMN'=> $laTmp[17], 'CPLACA'=> $laTmp[18],'CCANTID'=> $laTmp[19],
                                    'CNROSER'=> $laTmp[20], 'CMODELO'=> $laTmp[21], 'CCOLOR'=> $laTmp[22], 'CMARCA'=> $laTmp[23],
                                    'CDESTIP'=> $laTmp[24], 'CCODIGO'=> $lcCodigo, 'CCODCLA' => $lcCodCla, 'DFECADQ'=> $laTmp[25],
                                    'CDOCADQ'=> $laTmp[26], 'CDOCALT'=> $laTmp[27], 'DFECBAJ'=> $laTmp[28], 'CDOCBAJ'=> $laTmp[29],
                                    'CMOTOR'=> $laTmp[30], 'CCODART'=> $laTmp[31], 'CCODREF'=> $laTmp[32]];
               }
               if (count($this->paDatos) == 0) {
                  $this->pcError = "ACTIVO FIJO ESTA DADO DE BAJA O NO SE ENCONTRO";
                  $lcTipAfj = substr($this->paData['CCODDES'], 0, 5);
                  $lnCorrel = substr($this->paData['CCODDES'], 5);
                  if ($lnCorrel > 0) {
                     $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel";
                     $R1 = $p_oSql->omExec($lcSql);
                     $laTmp = $p_oSql->fetch($R1);
                     if (!$laTmp or count($laTmp) == 0) {
                        ;
                     } else {
                        $lcSql = "SELECT  A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, 
                                          A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                                          A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, 
                                          A.cCodEmp, A.cNomEmp, A.cNroRuc, A.cRazSoc, 
                                          A.dFecAlt, A.nMontMN, A.cPlaca, A.cCantid, 
                                          A.cNroSer, A.cModelo, A.cColor, A.cMarca, B.cDescri, 
                                          A.dFecAdq, A.cDocAdq, A.cDocAlt, A.dFecBaj, A.cDocBaj, A.cMotor, A.cCodArt, A.cCodRef
                                 FROM F_E04MAFJ_2('$laTmp[0]') A
                                 INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ";
                        //print_r($lcSql);
                        $R1 = $p_oSql->omExec($lcSql);
                        while ($laTmp = $p_oSql->fetch($R1)) {
                           $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
                           $lcNomEmp = str_replace('/',' ',$laTmp[13]);
                           $lcCodCla = substr($laTmp[1], 0, 2);
                           $this->laDatos = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1],'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3],
                                             'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],
                                             'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],'CDESCEN'=> $laTmp[11],
                                             'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $lcNomEmp, 'CNRORUC'=> $laTmp[14],'CRAZSOC'=> $laTmp[15],
                                             'DFECALT'=> $laTmp[16], 'NMONTMN'=> $laTmp[17], 'CPLACA'=> $laTmp[18],'NCANTID'=> $laTmp[19],
                                             'CNROSER'=> $laTmp[20], 'CMODELO'=> $laTmp[21], 'CCOLOR'=> $laTmp[22], 'CMARCA'=> $laTmp[23],
                                             'CDESTIP'=> $laTmp[24], 'CCODIGO'=> $lcCodigo, 'CCODCLA' => $lcCodCla, 'DFECADQ'=> $laTmp[25],
                                             'CDOCADQ'=> $laTmp[26], 'CDOCALT'=> $laTmp[27], 'DFECBAJ'=> $laTmp[28], 'CDOCBAJ'=> $laTmp[29],
                                             'CMOTOR'=> $laTmp[30], 'CCODART'=> $laTmp[31], 'CCODREF'=> $laTmp[32]];
                        }
                     }return False;
                  }
               }
            }
         }
      }return true;
   }
   
   // --------------------------------------------------------
   // ELIMINAR ACTIVO FIJO
   // 2022-01-20 GCH Creacion
   // --------------------------------------------------------
   public function omEliminarActivosFijos() {
      $llOk = $this->mxValParamEliminarActivosFijos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEliminarActivosFijos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamEliminarActivosFijos() {
      if (!isset($this->paData['CACTFIJ'])) {
         $this->pcError = "CODIGO DE ACTIVO FIJO NO DEFINIDO";
         return false;
      }
      return true;
   }
   
   protected function mxEliminarActivosFijos($p_oSql) {
      $lcSql = "DELETE FROM E04MAFJ WHERE cActFij = '{$this->paData['CACTFIJ']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         //print_r($lcSql);
         $this->pcError = "NO SE PUDO ELIMINAR ACTIVO FIJO ";
         return false;
      }else{
         $this->pcError = "ACTIVO FIJO ELIMINADO CORRECTAMENTE ";
      }
   }
   
   // --------------------------------------------------------
   // Buscar AFs por codigo / descripcion
   // 2022-06-27 GCH Creacion
   // --------------------------------------------------------
   public function omCargarActivoFijo() {
      $llOk = $this->mxValParamCargarActivoFijo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarActivoFijo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCargarActivoFijo() {
      if (!isset($this->paData['CCODDES'])) {
         $this->pcError = "CODIGO DE ACTIVO FIJO NO DEFINIDO";
         return false;
      }
      return true;
   }
   
   protected function mxCargarActivoFijo($p_oSql) {
      $this->paData['CCODDES'] = str_replace('-','',$this->paData['CCODDES']);
      if(strlen($this->paData['CCODDES']) === 5){
         $lcSql = "SELECT cActFij, cEstado FROM E04MAFJ WHERE cactfij = '{$this->paData['CCODDES']}'";
      }else{
         $lcTipAfj = substr($this->paData['CCODDES'], 0, 5);
         $lnCorrel = substr($this->paData['CCODDES'], 5);
         $lcSql = "SELECT cActFij, cEstado FROM E04MAFJ WHERE cTipAfj = '$lcTipAfj' AND nCorrel = $lnCorrel";
      }
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = 'ACTIVO NO EXISTE';
         return false;
      }  
      $lcActFij = $laTmp[0];
      $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cEstado, C.cDescri, A.cSituac, D.cDescri, 
                  A.cCenRes, F.cCenCos, F.cDescri, A.cCodEmp, G.cNombre, A.cNroRuc, A.cRazSoc, A.dFecAlt, A.nMontMN,
                  A.mDatos, B.cDescri, E.cDescri
                  FROM E04MAFJ A
                  INNER JOIN E04TTIP B ON B.cTipAfj = A.cTipAfj
                  INNER JOIN V_S01TTAB C ON C.cCodigo = A.cEstado
                  INNER JOIN V_S01TTAB D ON D.cCodigo = A.cSituac
                  INNER JOIN S01TRES E ON E.cCenRes = A.cCenRes
                  INNER JOIN S01TCCO F ON F.cCenCos = E.cCenCos
                  INNER JOIN V_S01TUSU_1 G ON G.cCodUsu = A.cCodEmp
                  WHERE A.cActFij = '$lcActFij' AND  C.cCodTab = '333' and D.cCodTab = '334'"; 
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
      $lcNomEmp = str_replace('/',' ',$laTmp[12]);
      $lcCodCla = substr($laTmp[1], 0, 2);
      $laData = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1],'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3],
                  'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],
                  'CCENRES'=> $laTmp[8],  'CCENCOS'=> $laTmp[9],'CDESCEN'=> $laTmp[10], 
                  'CCODEMP'=> $laTmp[11], 'CNOMEMP'=> $lcNomEmp, 'CNRORUC'=> $laTmp[13],'CRAZSOC'=> $laTmp[14],
                  'DFECALT'=> $laTmp[15], 'NMONTMN'=> $laTmp[16], 'MDATOS'=> json_decode($laTmp[17], true),
                  'CDESTIP'=> $laTmp[18], 'CCODIGO'=> $lcCodigo, 'CCODCLA' => $lcCodCla, 'CDESRES'=> $laTmp[19]];
         // print_r($laData);
      $this->paData['CCENCOS'] = $laData['CCENCOS'];
      $llOk = $this->mxCentrosResponsabilidad($p_oSql);
      if (!$llOk) {
         return false;
      }
      $laData['ACENRES'] = $this->paData['ACENRES'];
      $this->paData = $laData;

      // Cargar Depreciacion
      $lcDate =  date('Y', time());
      $lcSql = "SELECT max(cperiod) FROM e04mdep ";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $laTmp[0] = substr($laTmp[0], 4, 2);
      if ($laTmp[0] ===  '00') {
         $lcDate = $lcDate - 1;
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      } else {
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      }
      $lcSql = "SELECT C.cActFij, C.nMoncal, C.nDeprec, sum(B.nDeprec)
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' and C.cactfij = '{$this->paData['CACTFIJ']}'
                  GROUP BY C.cActFij";
      //print_r($lcSql);
      $R2 = $p_oSql->omExec($lcSql); 
      while ($laTmp = $p_oSql->fetch($R2)) {
         $this->laData = ['CACTFIJ'=> $laTmp[0], 'NMONCAL'=> $laTmp[1], 'NDEPREC'=> $laTmp[2], 'NSUMDEP'=>$laTmp[3], 
                          'NDEPPER'=> $laTmp[3]-$laTmp[2], 'NVALCAL'=> $laTmp[1]-$laTmp[3], 'NVALNET'=>$laTmp[1]-$laTmp[2]]; 
      } 
      $lcSql = "SELECT A.cActFij, A.nMontmn, C.cPeriod, B.nMonto, B.nDeprec, B.nFactor, C.dMovimi FROM e04mafj A
                  INNER JOIN E04DDEP B ON B.cActFij = A.cActFij
                  INNER JOIN E04MDEP C ON C.cIdDepr = B.cIdDepr 
                  WHERE A.cactfij = '{$this->paData['CACTFIJ']}' order by C.cPeriod desc Limit 14";
      // print_r($lcSql); 
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CACTFIJ'=> $laFila[0], 'NMONTMN'=> $laFila[1], 'CPERIOD'=> $laFila[2], 'NMONTO'=>$laFila[3], 
                             'NDEPREC'=> $laFila[4], 'NFACTOR'=> $laFila[5], 'DMOVIMI'=> $laFila[6]]; 
      }
      return true;                 
   }

   // --------------------------------------------------------
   // Registra nuevos activos fijos
   // 2022-07-12 GCH Creacion
   // --------------------------------------------------------
   public function omNuevosActivosFijos() {
      // print_r($this->paData);
      $llOk = $this->mxValParamGrabarActivoFijoNuevo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerProveedor($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->omGrabarNuevoActivoFijo($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      $this->paData = $this->laData;
      return true;
   }

   protected function mxValParamGrabarActivoFijoNuevo() {
      // print_r($this->paData);
      if (!isset($this->paData['CINDACT'])) {
         $this->pcError = 'INDICADOR DE ACTIVO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCANTID'])) {
         $this->pcError = 'DEFINICIÓN DE CANTIDAD NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CSOBRAN'])) {
         $this->pcError = 'SOBRANTE NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CACTFIJ'])) {
         $this->pcError = 'ID DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CTIPAFJ'])) {
         $this->pcError = "TIPO DE ACTIVO FIJO [{$this->paData['CTIPAFJ']}] NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO'])) {
         $this->pcError = 'ESTADO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CDESCRI'])) {
         $this->pcError = 'DESCRIPCIÓN NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CSITUAC'])) {
         $this->pcError = 'SITUACIÓN NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CINDACT'])) {
         $this->pcError = 'INDICADOR DE AF NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCENRES'])) {
         $this->pcError = 'CENTRO DE RESPONSABILIDAD NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCODEMP'])) {
         $this->pcError = 'CÓDIGO DE EMPLEADO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['DFECALT'])) {
         $this->pcError = "FECHA DE ALTA [{$this->paData['DFECALT']}] NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($this->paData['NMONTMN'])) {
         $this->pcError = 'MONTO EN SOLES NO DEFINIDO O INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CMONEDA'])) {
         $this->pcError = 'MONEDA NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['CESTADO'])) {
         $this->pcError = 'ESTADO NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['MFOTOGR'])) {
         $this->pcError = 'ENLACE DE LA FOTOGRAFÍA NO DEFINIDO O INVÁLIDO';
         return false;
      } 
      return true;
   }
   protected function omGrabarNuevoActivoFijo($p_oSql) {
      // print_r($this->paData);
      $this->paData['NMONTMN'] = str_replace(',','',$this->paData['NMONTMN']);
      $this->paData['NMONTME'] = str_replace(',','',$this->paData['NMONTME']);
      $this->paData['CMARCA']  = strtoupper(trim($this->paData['CMARCA']));
      $this->paData['CMODELO']  = strtoupper(trim($this->paData['CMODELO']));
      $this->paData['CCOLOR']  = strtoupper(trim($this->paData['CCOLOR']));
      $this->paData['CPLACA']  = strtoupper(trim($this->paData['CPLACA']));
      $this->paData['CNROSER']  = strtoupper(trim($this->paData['CNROSER']));
      $this->paData['NASIADQ']  = strtoupper(trim($this->paData['NASIADQ']));
      $this->paData['CCODART']  = strtoupper(trim($this->paData['CCODART']));
      $this->paData['CDOCADQ']  = strtoupper(trim($this->paData['CDOCADQ']));
      $this->paData['CDOCREF']  = strtoupper(trim($this->paData['CDOCREF']));
      $this->paData['CMOTOR']  = strtoupper(trim($this->paData['CMOTOR']));
      $lmDatos = json_encode(['CMARCA'=> $this->paData['CMARCA'],   'CMODELO'=> $this->paData['CMODELO'], 'CCOLOR'=> $this->paData['CCOLOR'],
                              'CPLACA'=> $this->paData['CPLACA'],   'CNROSER'=> $this->paData['CNROSER'], 'CINDACT'=> $this->paData['CINDACT'],
                              'CCANTID'=> $this->paData['CCANTID'], 'CSOBRAN'=> $this->paData['CSOBRAN'], 'NASIADQ'=> $this->paData['NASIADQ'],
                              'CCODART'=> $this->paData['CCODART'], 'CDOCADQ'=> $this->paData['CDOCADQ'], 'CCODREF'=> $this->paData['CCODREF'],
                              'CDOCBAJ'=> $this->paData['CDOCBAJ'], 'DFECBAJ'=> $this->paData['DFECBAJ'], 'CMOTOR'=> $this->paData['CMOTOR']]);
      // print_r($lmDatos);   
      if ($this->paData['CACTFIJ'] == '*') {   // Nuevo AF
         // Siguiente codigo de AF
         $lcSql = "SELECT MAX(cActFij) FROM E04MAFJ";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $laTmp[0] = '00000';
         }
         $lcActFij = fxCorrelativo($laTmp[0]);
         // Correlativo
         $lcSql = "SELECT MAX(nCorrel) FROM E04MAFJ WHERE CTIPAFJ = '{$this->paData['CTIPAFJ']}';";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $lnCorrel = 0;
         } else {
            $lnCorrel = $laTmp[0];
         }
         $lnCorrel += 1;
         $this->paData['CDESCRI']  = strtoupper(trim($this->paData['CDESCRI']));
         //$this->paData['CCODOLD'] = $lCTIPAFJ.$lcNewCor; OJOFPM FALTA!
         $lcSql = "INSERT INTO E04MAFJ (cActFij, CTIPAFJ, nCorrel, cEstado,
                                        cDescri, cSituac, cIndAct, cCenRes, 
                                        cCodEmp, cNroRuc, cRazSoc, dFecAlt, 
                                        nMontMN, nMontME, cMoneda, nSerFac, 
                                        mDatos,  mFotogr, cCodOld, cUsuCod, nMonCal)
                              VALUES ('$lcActFij', '{$this->paData['CTIPAFJ']}', '$lnCorrel', '{$this->paData['CESTADO']}',
                                      '{$this->paData['CDESCRI']}', '{$this->paData['CSITUAC']}', '{$this->paData['CINDACT']}', '{$this->paData['CCENRES']}',
                                      '{$this->paData['CCODEMP']}', '{$this->paData['CNRORUC']}', '{$this->paData['CRAZSOC']}', '{$this->paData['DFECALT']}',
                                      '{$this->paData['NMONTMN']}', '0.0', '{$this->paData['CMONEDA']}', '{$this->paData['NSERFAC']}',
                                      '$lmDatos' , '{$this->paData['MFOTOGR']}', '{$this->paData['CCODOLD']}', '{$this->paData['CUSUCOD']}', 
                                      '{$this->paData['NMONTMN']}');";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = 'NO SE PUDO INSERTAR NUEVO ACTIVO FIJO';
            return false;
         }
         $lcCodigo = substr($this->paData['CTIPAFJ'], 0, 2).'-'.substr($this->paData['CTIPAFJ'], 2, 5);
         $laData = ['CACTFIJ'=> $lcActFij, 'CCODIGO'=> $lcCodigo.'-'.$lnCorrel];
      } else {   // Actualizacion de activo fijo
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cActFij = '{$this->paData['CACTFIJ']}';";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $this->pcError = "ID DE ACTIVO FIJO [{$this->paData['CACTFIJ']}] NO EXISTE";
            return false;
         }
         $this->paData['CDESCRI']  = strtoupper(trim($this->paData['CDESCRI']));
         $lcSql = "UPDATE E04MAFJ SET cEstado = '{$this->paData['CESTADO']}', cDescri = '{$this->paData['CDESCRI']}', cSituac = '{$this->paData['CSITUAC']}',                                         
                                      cIndAct = '{$this->paData['CINDACT']}', cCenRes = '{$this->paData['CCENRES']}', cCodEmp = '{$this->paData['CCODEMP']}',  
                                      cNroRuc = '{$this->paData['CNRORUC']}', cRazSoc = '{$this->paData['CRAZSOC']}', dFecAlt = '{$this->paData['DFECALT']}', 
                                      nMontMN = '{$this->paData['NMONTMN']}',  nMontME = '0.00', 
                                      cMoneda = '{$this->paData['CMONEDA']}', mDatos  = '$lmDatos' ,  mFotogr = '{$this->paData['MFOTOGR']}',
                                      cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW(), nMoncal = '{$this->paData['NMONTMN']}'
                                      WHERE cActFij = '{$this->paData['CACTFIJ']}'";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO [{$this->paData['CACTFIJ']}]";
            return false;
         }
         $laData = ['OK'=> 'OK'];
      }
      $this->laData = $laData;
      return true;
   }
	
   // -----------------------------------------------------------
   // Buscar empleado por codigo o apellido
   // 2022-09-16 GCH 
   // -----------------------------------------------------------
   public function omBuscarEmpleado() {
      $llOk = $this->mxValBuscarEmpleado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarEmpleado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValBuscarEmpleado() {
      if (empty(trim($this->paData['CCRIBUS']))) {
         $this->pcError = 'CRITERIO DE BÚSQUEDA DE EMPLEADO NO DEFINIDA';
         return false;
      }
      return true;
   }
   
   protected function mxBuscarEmpleado($p_oSql) {
      if (preg_match('/^[0-9A-Z]{4,5}$/', $this->paData['CCRIBUS'])) {
         $lcSql = "SELECT cCodUsu, cNombre, cNroDni, cEmail FROM V_S01TUSU_1 WHERE cCodUsu = '{$this->paData['CCRIBUS']}' AND cEstado = 'A'";
      } elseif (preg_match('/^[0-9]{8}$/', $this->paData['CCRIBUS'])) {
         $lcSql = "SELECT cCodUsu, cNombre, cNroDni, cEmail FROM V_S01TUSU_1 WHERE cNroDni = '{$this->paData['CCRIBUS']}' AND cEstado = 'A'";
      } else {
         $lcCriBus = str_replace(' ', '%', trim(strtoupper($this->paData['CCRIBUS']))).'%';
         $lcSql = "SELECT cCodUsu, cNombre, cNroDni, cEmail FROM V_S01TUSU_1 WHERE cNombre like '%{$lcCriBus}' AND cEstado = 'A' ORDER BY cNombre";
      }         
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql); 
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODUSU'=> $laFila[0], 'CNOMBRE'=> str_replace('/', ' ', $laFila[1]), 'CNRODNI'=> $laFila[2], 'CEMAIL'=> $laFila[3]]; 
      } 
      if (count($this->paDatos) == 0) { 
         $this->pcError = "NO HAY EMPLEADOS QUE CUMPLAN CRITERIO DE BUSQUEDA"; 
         return false; 
      } 
      return true;  
   }

   //-------------------------------------------------------- 
   // INICIO PANTALLA 2010 - Reporte Activo Fijo
   // --------------------------------------------------------
   // Buscar AFs por codigo / descripcion
   // 2022-03-08 GCH Creacion
   // --------------------------------------------------------
   public function omBuscarActFij() {
      $llOk = $this->mxValParamBuscarActFij();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarActFij($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarActFij() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['CCODDES'])) {
         $this->pcError = "CODIGO/DESCRIPCIÓN NO DEFINIDO";
         return false;
      }
      $this->paData['CCODDES'] = strtoupper(trim($this->paData['CCODDES']));
      $this->paData['CCODDES'] = str_replace('  ', ' ', $this->paData['CCODDES']);
      $this->paData['CCODDES'] = str_replace(' ', '%', $this->paData['CCODDES']);
      if (!preg_match('/^[0-9,A-Z, -_ÑÁÉÍÓÚ\/]{2,25}$/', $this->paData['CCODDES']) or strpos($this->paData['CCODDES'], '%%')) {
         $this->pcError = "CODIGO/DESCRIPCIÓN INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarActFij($p_oSql) {
      $lcCodDes = str_replace(' ','%',$this->paData['CCODDES']).'%';
      $laDatos = [];
      $lcCodDes1 = str_replace(' ','',$this->paData['CCODDES']);
      if($this->paData['OPCION'] == '*' && $lcCodDes1 == 'VEHICULO'){
      $lcSql = "SELECT A.cActFij FROM E04MAFJ A INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes 
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE (A.cDescri LIKE '%$lcCodDes' OR B.cDescri LIKE '%$lcCodDes' OR A.MDATOS LIKE '%$lcCodDes'
                  OR C.cNombre LIKE '%$lcCodDes' OR A.cDescri LIKE '%AUTOMOVIL%' OR A.cDescri LIKE '%MOTOCICLETA%' OR A.cDescri LIKE '%CAMION%' OR A.cDescri LIKE '%MINIVAN%'
                  OR A.cDescri LIKE '%AMBULANCIA%' OR A.cDescri LIKE '%OMNIBUS%' OR A.cDescri like '%TRIMOTOR%'  OR A.cDescri like '%MONTACARGA%' OR A.cDescri like '%FURGON%')  and (A.cTipAfj != '18012')
                  ORDER BY A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      }elseif($this->paData['OPCION'] == '*') {
         $lcSql = "SELECT A.cActFij FROM E04MAFJ A INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes 
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cDescri LIKE '%$lcCodDes' OR B.cDescri LIKE '%$lcCodDes' OR A.MDATOS LIKE '%$lcCodDes'
                     OR C.cNombre LIKE '%$lcCodDes'
                     ORDER BY A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel LIMIT 250";
      } elseif($this->paData['OPCION'] == 'DESCRIPCIÓN'){
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cDescri LIKE '%$lcCodDes' ORDER BY cCenRes, cCodEmp LIMIT 250";
      } elseif($this->paData['OPCION'] == 'CENTRO DE RESPONSABILIDAD'){
         $lcSql = "SELECT c.cActFij FROM s01tres A 
                     inneR join s01tcco b on b.ccencos = a.ccencos
                     inner join e04mafj c on c.ccenres = a.ccenres
                     inner join V_S01TUSU_1 d ON d.cCodUsu = c.cCodEmp
                     where A.cdescri like '%$lcCodDes' 
                     order by a.ccenres";
      }else{
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE mDatos LIKE '%$lcCodDes' ORDER BY cCenRes, cCodEmp";
      }
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = $laTmp[0];
      }
      if (count($laDatos) == 0) {
         $this->pcError = 'NO HAY ACTIVOS FIJOS CON CRITERIO DEFINIDO';
         return false;
      }
      $lcDate =  date('Y', time());
      if ($laTmp[0] ===  '00') {
         $lcDate = $lcDate - 1;
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      } else {
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      }

      foreach ($laDatos as $lcActFij) {
         $lcSql = "SELECT C.cActFij, C.CTIPAFJ, C.nCorrel, C.cDescri, 
                                 C.cEstado, C.cDesEst, C.cSituac, C.cDesSit, 
                                 C.cCenRes, C.cDesRes, C.cCenCos, C.cDesCen, 
                                 C.cCodEmp, C.cNomEmp, C.cNroRuc, C.cRazSoc, 
                                 C.dFecAlt, C.nMontMN, C.cPlaca, C.cCantid, 
                                 C.cNroSer, C.cModelo, C.cColor, C.cMarca, D.cDescri,
                                 C.dFecAdq, C.cDocAdq, C.cDocAlt, C.dFecBaj, C.cDocBaj, C.cCodArt, C.cCodRef, sum(B.nDeprec), E.nMonCal
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN F_E04MAFJ_2('$lcActFij') C ON C.cActFij = B.cActFij 
                  INNER JOIN E04TTIP D ON D.CTIPAFJ = C.CTIPAFJ
                  INNER JOIN E04MAFJ E ON E.cActFij = B.cActFij 
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' 
                  GROUP BY C.cActFij, c.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.nMontMN, C.cEstado, C.cDesEst, C.cSituac, C.cDesSit, C.cCenRes, C.cDesRes, C.cCenCos, 
                  C.cDesCen, C.cCodEmp, C.cNomEmp, C.cNroRuc, C.cRazSoc, C.cPlaca, C.cCantid, C.cNroSer, C.cModelo, C.cColor, C.cMarca, D.cDescri, C.dFecAdq, C.cDocAdq, C.cDocAlt, C.dFecBaj, C.cDocBaj, C.cCodArt, C.cCodRef, E.nMonCal ";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
         $lcNomEmp = str_replace('/',' ',$laTmp[13]);
         if($laTmp[32] >= $laTmp[33]){
            $lnDepr = 0.00;
         }else{
            $lnDepr = $laTmp[32];
         }
         if($lnDepr === 0.00){
            $lnNeto = 0.00;
         }else{
            $lnNeto =  $laTmp[33] - $lnDepr;
         }
         $lcDepre = 
         $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1],'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3],
                           'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],
                           'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],'CDESCEN'=> $laTmp[11],
                           'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $lcNomEmp, 'CNRORUC'=> $laTmp[14],'CRAZSOC'=> $laTmp[15],
                           'DFECALT'=> $laTmp[16], 'NMONTMN'=> $laTmp[17], 'CPLACA'=> $laTmp[18],'CCANTID'=> $laTmp[19],
                           'CNROSER'=> $laTmp[20], 'CMODELO'=> $laTmp[21], 'CCOLOR'=> $laTmp[22], 'CMARCA'=> $laTmp[23],
                           'CDESTIP'=> $laTmp[24], 'CCODIGO'=> $lcCodigo, 'DFECADQ'=> $laTmp[25], 'CDOCADQ'=> $laTmp[26], 
                           'CDOCALT'=> $laTmp[27], 'DFECBAJ'=> $laTmp[28], 'CDOCBAJ'=> $laTmp[29], 'CCODART'=> $laTmp[30],
                           'CCODREF'=> $laTmp[31], 'NDEPREC'=> $lnDepr, 'NMONCAL' => $laTmp[33], 'NNETO' => $lnNeto];
      }
      //print_r($this->paDatos);
      return true;
   }

   // ----------------------------------------
   // Cargar Reporte de activos fijos PDF
   // 2022-03-09 GCH
   // ----------------------------------------
   public function omReporteBusquedaActFij() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $laData = $this->paData;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laData;
      $llOk = $lo->omPrintReporteBusquedaActFijPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // ----------------------------------------
   // Cargar Reporte de activos fijos EXCEL
   // 2023-02-21 GCH
   // ----------------------------------------
   public function omReporteBusquedaActFijExcel() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $laData = $this->paData;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laData;
      $llOk = $lo->omPrintReporteBusquedaActFijExcel();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }

   // ----------------------------------------
   // Cargar Reporte de activos fijos PDF
   // 2022-03-09 GCH
   // ----------------------------------------
   public function omReporteActFijPDF() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $laData = $this->paData;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laData;
      $llOk = $lo->omPrintReporteActFijPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // --------------------------------------------------------
   // INICIO PANTALLA AFJ1070 - TRANFERENCIAS
   // ------------------------------------------------------------------------
   // Cargar Centros Costos
   // 2022-03-23 GCH Creacion
   // ------------------------------------------------------------------------
   public function omInitMantTrans() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarCentroCosto($p_oSql) {
      $laDatos = ['ACENCOS' => [], 'ARESPON' => []];
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM s01tcco WHERE cEstado = 'A' AND cCenCos IN 
               (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         // $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCOS'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
         $laDatos['ACENCOS'][] = ['CCENCOS'=> trim($laTmp[0]), 'CDESCOS'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($laDatos['ACENCOS']) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES  ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos['ARESPON'][] = ['CCENRES'=> trim($laTmp[0]), 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($laDatos['ARESPON']) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      $this->paDatos = $laDatos;
      // Lista de tranferencias 
      $lcSql = "SELECT cIdTrnf, dTrasla, cDescri, cCenRes, cCodEmp, cResCen, cCodRec, cEstado FROM E04MTRF WHERE cEstado != 'X' ORDER BY cIdTrnf DESC";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CIDTRNF' => $laFila[0], 'CTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],
                            'CCENRES' => $laFila[3], 'CCODEMP' => $laFila[4], 'CRESCEN' => $laFila[5],
                            'CCODREC' => $laFila[6], 'CESTADO' => $laFila[7]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDato) == 0) {
         $this->pcError = 'NO HAY TRANFERENCIAS';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Buscar transferencias por rango de fecha
   // 2022-05-04 GCH Creacion
   // ------------------------------------------------------------------------
   public function omBuscarTransferencias() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarTransferencias($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarTransferencias($p_oSql) {
      // print_r($this->paData);
      $lcSql = "SELECT cIdTrnf, dTrasla, cDescri, cCenRes, cCodEmp, cResCen, cCodRec, cEstado FROM E04MTRF
                WHERE dTrasla >= '{$this->paData['DFECDES']}' AND
                  dTrasla <= '{$this->paData['DFECHAS']}' AND cEstado = 'P'
               ORDER BY dTrasla DESC";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CIDTRNF' => $laFila[0], 'CTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],
                             'CCENRES' => $laFila[3], 'CCODEMP' => $laFila[4], 'CRESCEN' => $laFila[5],
                             'CCODREC' => $laFila[6], 'CESTADO' => $laFila[7]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY TRANFERENCIAS EN ESE RANGO DE FECHAS';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Editar Transferencia.
   // 2022-05-06 GCH Creacion
   // ------------------------------------------------------------------------
   public function omEditarTransferencias() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxEditarTransferencias($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxEditarTransferencias($p_oSql) {
      // print_r($this->paData);
      // CABECERA
      $lcSql = "SELECT A.cIdTrnf, A.dTrasla, A.cDescri, A.cCenRes, A.cCodEmp, A.cResCen, A.cCodRec, B.cCenCos, 
                       C.cCenCos, D.cNombre, E.cNombre, A.cEstado FROM E04MTRF A
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN S01TRES C ON C.cCenRes = A.cResCen 
                  INNER JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cCodEmp
                  INNER JOIN V_S01TUSU_1 E ON E.cCodUsu = A.cCodRec
                  WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $lcNomOri = str_replace('/', ' ', $laFila[9]);
         $lcNomDes = str_replace('/', ' ', $laFila[10]);
         $this->paData = ['CIDTRNF' => $laFila[0], 'DTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],
                           'CCENRES' => $laFila[3], 'CCODEMP' => $laFila[4], 'CRESCEN' => $laFila[5],
                           'CCODREC' => $laFila[6], 'CCENCOS' => $laFila[7], 'CDESCOS' => $laFila[8],
                           'CNOMEMP' => $lcNomOri, 'CNOMDES' => $lcNomDes, 'CESTADO' => $laFila[11]];
      }
      // print_r($this->paDatos); 
      if (count($this->paData) == 0) {
         $this->pcError = 'NO SE ENCONTRO TRANFERENCIA';
         return false;
      }
      // DETALLE
      $lcSql = "SELECT cActFij FROM E04DTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT A.cActFij, B.cTipAfj, B.nCorrel, B.cDescri, A.cEstado
                     FROM E04DTRF A 
                     INNER JOIN E04MAFJ B ON B.cActFij = A.cActFij
                     WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         //echo $lcSql;
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)) {
            $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
            $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CCODIGO' => $lcCodigo, 'CDESCRI' => $laFila[3],'CESTADO' => $laFila[4]];
         }
         if (count($this->paDatos) == 0) {
            $this->pcError = 'NO SE ENCONTRO DETALLE DE TRANFERENCIA';
            return false;
         }
      }
      //print_r($this->paDatos);
      return true;
   }

   // ---------------------------------------------
   // Buscar AFs por codigo
   // 2022-03-22 GCH Creacion
   // ----------------------------------------------
   public function omBuscarActivoFijoTrans() {
      $llOk = $this->mxValParamBuscarActivoFijoTrans();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarActivoFijoTrans($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarActivoFijoTrans() {
      if (!isset($this->paData['CCODDES'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarActivoFijoTrans($p_oSql) {
      $lcCodigo = str_replace('-','', $this->paData['CCODDES']);
      // buscra por codigo
      if ($lcCodigo > 5) {
         $lcTipAfj = substr($lcCodigo, 0, 5);
         $lnCorrel = substr($lcCodigo, 5);
         if ($lnCorrel > 0) {
            $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or count($laTmp) == 0) {
               ;
            } else {
               $lcSql = "SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                        A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, A.cCodEmp, A.cNomEmp, B.cDescri 
                        FROM F_E04MAFJ_2('$laTmp[0]') A INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ
                        WHERE A.cCenRes = '{$this->paData['CCENRES']}'";
               // print_r($lcSql);  
               $R1 = $p_oSql->omExec($lcSql);
               while ($laTmp = $p_oSql->fetch($R1)) {
                  $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
                  $this->paDatos = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2],
                                      'CDESCRI'=> $laTmp[3], 'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 
                                      'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],  
                                      'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],
                                      'CDESCEN'=> $laTmp[11], 'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $laTmp[13],
                                      'CDESTIP'=> $laTmp[14], 'CCODIGO'=> $lcCodigo,];
               }
               if (count($this->paDatos) == 0) {
                  $this->pcError = 'NO SE ENCONTRO ACTIVO FIJO';
                  return false;
               }
               return true;
            }
         }
      }
      return true;
   }

   // ---------------------------------------------
   // Buscar Centro de Costo y Responsabilidad
   // 2022-05-09 GCH Creacion
   // ----------------------------------------------
   public function omCargarDatos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxCargarDatos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarDatos($p_oSql) {
      $laData = ['CDESCOS' => [], 'CDESRES' => [], 'CDESCCO' => [], 'CRESDES' => []];
      $lcSql = "SELECT cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laData['CDESCOS'] = $laFila[0];
      }
      $lcSql = "SELECT cDescri FROM S01TRES WHERE cCenRes = '{$this->paData['CCENRES']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laData['CDESRES'] = $laFila[0];
      }
      $lcSql = "SELECT cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCOSDES']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laData['CDESCCO'] = $laFila[0];
      }
      $lcSql = "SELECT cDescri FROM S01TRES WHERE cCenRes = '{$this->paData['CRESCEN']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laData['CRESDES'] = $laFila[0];
      }
      $this->paData = $laData;   
      return true;
   }

   // ---------------------------------------------
   // Buscar AFs por codigo desde-hasta
   // 2022-04-06 GCH Creacion
   // ---------------------------------------------
   public function omBuscarActivoFijoVarios() {
      $llOk = $this->mxValParamBuscarActivoFijoVarios();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarActivoFijoVarios($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarActivoFijoVarios() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      } elseif (!isset($this->paData['CCODDES'])) {
         $this->pcError = "CODIGO/DESCRIPCIÓN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CCODHAS'])) {
         $this->pcError = "CODIGO/DESCRIPCIÓN NO DEFINIDO";
         return false;
      }
      return true;
   }

      protected function mxBuscarActivoFijoVarios($p_oSql) {
      // print_r($this->paData);
      $lcCodDes = str_replace('-','', $this->paData['CCODDES']);
      $lcCodHas = str_replace('-','', $this->paData['CCODHAS']);
      $lcTipAfj = substr($lcCodDes, 0, 5);
      $lnCorrel = substr($lcCodDes, 5);
      $lcTipAfj1 = substr($lcCodHas, 0, 5);
      $lnCorrel1 = substr($lcCodHas, 5);
      $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cTipAfj = '$lcTipAfj' AND nCorrel = $lnCorrel";
      // print_r($lcSql);
      $R2 = $p_oSql->omExec($lcSql);
      $laFila1 = $p_oSql->fetch($R2);
      $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cTipAfj = '$lcTipAfj1' AND nCorrel = $lnCorrel1";
      // print_r($lcSql);
      $R3 = $p_oSql->omExec($lcSql);
      $laFila2 = $p_oSql->fetch($R3);

      $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cActFij >= '$laFila1[0]' AND cActFij <= '$laFila2[0]'
                  AND cCenRes = '{$this->paData['CCENRES']}' AND cCodEmp = '{$this->paData['CCODEMP']}' AND cSituac = 'O' ORDER BY cTipAfj, nCorrel";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                  A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, A.cCodEmp, A.cNomEmp, B.cDescri 
                  FROM F_E04MAFJ_2('$laFila[0]') A INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ
                  WHERE A.cCenRes = '{$this->paData['CCENRES']}'";
         // print_r($lcSql);
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
            $lcNombre = str_replace('/', ' ', $laTmp[13]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2],
                               'CDESCRI'=> $laTmp[3], 'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 
                               'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],  
                               'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],
                               'CDESCEN'=> $laTmp[11], 'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $lcNombre,
                               'CDESTIP'=> $laTmp[14], 'CCODIGO'=> $lcCodigo,];
         }
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // PDF de Activos Fijos por centros de responsabilidad VARIOS 
   // 2022-09-21 GCH Creacion
   // ------------------------------------------------------------------------
   public function omReporteCentroResponsabilidadVarios() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxReporteCentroResponsabilidadVarios($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $lo = new CRepControlPatrimonial();
      // $lo->paDatos = $laDatos;
      $lo->paData  = $this->laData;
      $llOk = $lo->omPrintRepActivos();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxReporteCentroResponsabilidadVarios($p_oSql) {
      // print_r($this->paData);
      $laDatos = [];
      $lcSql = "SELECT A.cCenRes, A.cEstado, A.cDescri, A.cCenCos, B.cDescri, B.cEstado, B.cNivel FROM S01TRES A
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                WHERE A.cCenRes = '{$this->paData['CCENRES']}'";         
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE RESPONSABILIDAD [{$this->paData['CCENRES']}] NO EXISTE";
         return false;
      }
      $laData = ['CCENRES'=> $laTmp[0], 'CESTRES'=> $laTmp[1], 'CDESRES'=> $laTmp[2], 'CCENCOS'=> $laTmp[3], 'CDESCRI'=> $laTmp[4],
                 'CESTADO'=> $laTmp[5], 'CNIVEL'=> $laTmp[6], 'DATOS'=> ''];
      $lcSql ="SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, 
               TO_CHAR(A.dFecAlt, 'YYYY-MM-DD'), A.nMontMN, B.cCodUsu, B.cNombre, A.cSituac
               FROM E04MAFJ A 
               INNER JOIN V_S01TUSU_1 B ON A.cCodEmp = B.cCodUsu
               WHERE cCenRes = '{$this->paData['CCENRES']}' AND cSituac = 'O'
               ORDER BY B.cCodUsu,A.CTIPAFJ, A.nCorrel";
      // print_r($lcSql);
      // echo $lcSql.'<br>';
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2).'-'.substr('00000'.strval($laTmp[2]), -6);
         $lcNombre = str_replace('/', ' ', $laTmp[8]);
         $laDatos[] = ['CACTFIJ'=> $laTmp[0], 'CCODIGO'=> $lcCodigo, 'CDESCRI'=> $laTmp[3], 'CESTADO'=> $laTmp[4], 
                     'DADQUIS'=> $laTmp[5], 'NMONTMN'=> $laTmp[6], 'CCODEMP'=> $laTmp[7], 'CNOMBRE'=> $lcNombre, 'CSITUAC'=> $laTmp[9]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO TIENE ACTIVOS FIJOS";
         return false;
      }
      $laData['DATOS'] = $laDatos;
      $this->laData = $laData;
      return true;
   }

   // ------------------------------------------------------------------------
   // Grabar Tranferencia
   // 2022-04-29 Creacion
   // ------------------------------------------------------------------------
   public function omGrabarTranferencia() {      
      $llOk = $this->mxValParamGrabarTranferencia();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarTranferencia($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarTranferencia() {
      // print_r($this->paData);
      $loDate = new CDate(); 
      $laData = $this->paData;
      if (!isset($laData['CIDTRNF'])) {
         $this->pcError = "ID DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($laData['DTRASLA'])) {
         $this->pcError = "FECHA [{$laDatos['DTRASLA']}] NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($laData['CDESCRI'])) {
         $this->pcError = "DESCRIPCIÓN NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($laData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($laData['CCENRES'])) {
         $this->pcError = 'CENTRO DE RESPONSABILIDAD NO DEFINIDO O INVÁLIDO';
         return false;
      } elseif (!isset($laData['CCODEMP'])) {
         $this->pcError = 'CODIGO EMPLEADO NO DEFINIDA O INVÁLIDA';
         return false;
      } elseif (!isset($laData['CCOSDES'])) {
         $this->pcError = "CENTRO DE COSTO DESTINO NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($laData['CRESCEN'])) {
         $this->pcError = "CENTRO DE RESPONSALIDAD DESTINO NO DEFINIDA O INVÁLIDA";
         return false;
      } elseif (!isset($laData['CCODRES'])) {
         $this->pcError = "CODIGO EMPLEADO DESTINO NO DEFINIDA O INVÁLIDA";
         return false;
      }
     $i = -1;
      foreach ($this->paDatos as $laTmp) {
         $i++;
         if (!isset($laTmp['CACTFIJ'])) {
            $this->pcError = "CODIGO ACTIVO FIJO NO DEFINIDA O INVÁLIDA";
            return false;
         } elseif (!isset($laTmp['CESTADO'])) {   // OJOFPM
            $this->pcError = "ESTADO DEL ACTIVO FIJO NO DEFINIDA O INVÁLIDA";
            return false;
         }
       for ($j = 0; $j <= $i; $j++) {
          if ($j != $i and $this->paDatos[$j]['CACTFIJ'] == $laTmp['CACTFIJ']) {
                $this->pcError = "ACTIVO FIJO [{$laTmp['CACTFIJ']}] ESTÁ REPETIDO";
                return false;
             }
         }
      }      
      return true;  
   }

   protected function mxGrabarTranferencia($p_oSql) {
      // validar unique E04DTRF
      if ($this->paData['CIDTRNF'] == '*') {
       $this->paData['CIDTRNF'] = '0000';
         $lcSql = "SELECT MAX(cIdTrnf) FROM E04MTRF";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!(!$laTmp or count($laTmp) == 0 or $laTmp[0] == null)) {
            $this->paData['CIDTRNF'] = $laTmp[0];
         }
         $this->paData['CIDTRNF'] = fxCorrelativo($this->paData['CIDTRNF']);
         $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);
         $lcSql = "INSERT INTO E04MTRF (cIdTrnf, dTrasla, cDescri, cCenRes, cCodEmp, cResCen, cCodRec, cUsuCod) VALUES
                   ('{$this->paData['CIDTRNF']}', '{$this->paData['DTRASLA']}', '{$this->paData['CDESCRI']}', '{$this->paData['CCENRES']}', '{$this->paData['CCODEMP']}',
                    '{$this->paData['CRESCEN']}', '{$this->paData['CCODRES']}', '{$this->paData['CUSUCOD']}')";
         // print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR CABECERA DE TRANSFERENCIA";
            return false;
         }
      } else {
         $lcSql = "SELECT cEstado FROM E04MTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            $this->pcError = "ID DE TRANSFERENCIA [{$this->paData['CIDTRNF']}] NO EXISTE";
         return false;
       /*   print_r($this->paData); */
         } elseif ($laTmp[0] != 'P') {
            $this->pcError = "ID DE TRANSFERENCIA [{$this->paData['CIDTRNF']}] NO PERMITE MODIFICAR";
            return false;
         }      
         $this->paData['CDESCRI'] = strtoupper($this->paData['CDESCRI']);   
         $lcSql = "UPDATE E04MTRF SET dTrasla = '{$this->paData['DTRASLA']}', cDescri = '{$this->paData['CDESCRI']}', cCenRes = '{$this->paData['CCENRES']}', 
                 cCodEmp = '{$this->paData['CCODEMP']}', cResCen = '{$this->paData['CRESCEN']}', cCodRec = '{$this->paData['CCODRES']}', cUsuCod = '{$this->paData['CUSUCOD']}',
               tModifi = NOW() WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR CABECERA DE TRANSFERENCIA";
            return false;
         }
         $lcSql = "DELETE FROM E04DTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            print_r($lcSql);
            $this->pcError = "NO SE PUDO ELIMINAR DETALLE DE TRANSFERENCIA";
            return false;
         }
      }      
      foreach ($this->paDatos as $laFila) {
         $lcSql = "INSERT INTO E04DTRF (cIdTrnf, cActFij, cUsuCod) VALUES ('{$this->paData['CIDTRNF']}', '{$laFila['CACTFIJ']}', '{$this->paData['CUSUCOD']}')";
         // print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR DETALLE TRANSFERENCIA";
            return false;
         }
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Enviar Tranferencia
   // 2022-05-11 Creacion
   // ------------------------------------------------------------------------
   public function omEnviarTranferencia() {      
      $llOk = $this->mxValParamEnviarTranferencia();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarTranferencia($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamEnviarTranferencia() {
      $loDate = new CDate();
      if (!isset($this->paData['CIDTRNF'])) {
         $this->pcError = "ID DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxEnviarTranferencia($p_oSql) {
      // print_r($this->paDatos);
      // echo "<br>";
      // die();
      $lcSql = "SELECT cEstado FROM E04MTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = "ID DE TRANSFERENCIA NO EXISTE";
         return false;
      }
      if($this->paData['CESTADO'] == 'false'){
         $lcSql = "UPDATE E04MTRF SET cEstado = 'A', tRecepc = NOW(), tApradm = NOW(), tModifi = NOW()
               WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         // print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR  TRANSFERENCIA";
            return false;
         }
         $i = 0;
         while ($this->paDatos[$i] != null) {
            $lcSql = "UPDATE E04MAFJ SET cCenRes = '{$this->paData['CRESCEN']}', cCodEmp = '{$this->paData['CCODRES']}'
                      WHERE cActFij = '{$this->paDatos[$i]['CACTFIJ']}'";
            /* print_r($lcSql);
            echo "<br>"; */
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
               return false;
            }
            $i++;
            if(count($this->paDatos) <= $i){
               break;
            }
         }
         return true;
      } else {
         $lcSql = "UPDATE E04MTRF SET cEstado = 'E', tRecepc = NOW(), tModifi = NOW()
               WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         // print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR TRANSFERENCIA";
            return false;
         }
         return true;
      }
      return true;
      
   }

      // ----------------------------------------------------------------
   // INICIO PANTALLA AFJ1210 - TRANFERENCIAS REDES
   // ----------------------------------------------------------------
   // Cargar Transferencias
   // 2022-01-20 GCH Creacion
   // ----------------------------------------------------------------
   public function omInitMantTransRedes() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitMantTransRedes($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMantTransRedes($p_oSql) {
      $laDatos = ['ACENCOS' => [], 'ARESPON' => []];
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM s01tcco WHERE cEstado = 'A' AND cCenCos IN 
               (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         // $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCOS'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
         $laDatos['ACENCOS'][] = ['CCENCOS'=> trim($laTmp[0]), 'CDESCOS'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($laDatos['ACENCOS']) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      // Cargar Centro de Responsabilida
      $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE CESTADO = 'A'  ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos['ARESPON'][] = ['CCENRES'=> trim($laTmp[0]), 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($laDatos['ARESPON']) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      $this->paDatos = $laDatos;
      // Lista de tranferencias 
      if($this->paData['CUSUCOD'] === '1952'){
         $lcDescri = 'INFORMATICA'.'%';
      }elseif($this->paData['CUSUCOD'] === '2368'){
         $lcDescri = 'REDES'.'%';
      }elseif (in_array($this->paData['CUSUCOD'], ['3280','1872'])) {
         $lcDescri1 = 'REDES'.'%';
      }
      $lcSql = "SELECT cIdTrnf, dTrasla, cDescri, cCenRes, cCodEmp, cResCen, cCodRec, cEstado FROM E04MTRF
                  WHERE cDescri like '$lcDescri' OR cDescri like '$lcDescri1'
                  ORDER BY cIdTrnf DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CIDTRNF' => $laFila[0], 'CTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],
                            'CCENRES' => $laFila[3], 'CCODEMP' => $laFila[4], 'CRESCEN' => $laFila[5],
                            'CCODREC' => $laFila[6], 'CESTADO' => $laFila[7]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDato) == 0) {
         $this->pcError = 'NO HAY TRANFERENCIAS';
         return false;
      }
      return true;
   }


    // ----------------------------------------------------------------
   // INICIO PANTALLA AFJ1200 - TRANFERENCIAS INFORMATICA
   // ----------------------------------------------------------------
   // Cargar Centros Costos
   // 2022-01-20 GCH Creacion
   // ----------------------------------------------------------------
   public function omInitMantTransInformatica() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitMantTransInformatica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMantTransInformatica($p_oSql) {
      $laDatos = ['ACENCOS' => [], 'ARESPON' => []];
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM s01tcco WHERE cEstado = 'A' AND cCenCos IN 
               (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         // $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCOS'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
         $laDatos['ACENCOS'][] = ['CCENCOS'=> trim($laTmp[0]), 'CDESCOS'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($laDatos['ACENCOS']) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      // Cargar Centro de Responsabilida
      $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE CESTADO = 'A'  ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos['ARESPON'][] = ['CCENRES'=> trim($laTmp[0]), 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($laDatos['ARESPON']) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      $this->paDatos = $laDatos;
      // Lista de tranferencias 
      if($this->paData['CUSUCOD'] === '1952'){
         $lcDescri = 'INFORMATICA'.'%';
      }elseif($this->paData['CUSUCOD'] === '2368'){
         $lcDescri = 'REDES'.'%';
      }elseif (in_array($this->paData['CUSUCOD'], ['3280','1872'])) {
         $lcDescri = 'INFORMATICA'.'%';
         $lcDescri1 = 'REDES'.'%';
      }
      $lcSql = "SELECT cIdTrnf, dTrasla, cDescri, cCenRes, cCodEmp, cResCen, cCodRec, cEstado FROM E04MTRF
                  WHERE cDescri like '$lcDescri' OR cDescri like '$lcDescri1'
                  ORDER BY cIdTrnf DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CIDTRNF' => $laFila[0], 'CTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],
                            'CCENRES' => $laFila[3], 'CCODEMP' => $laFila[4], 'CRESCEN' => $laFila[5],
                            'CCODREC' => $laFila[6], 'CESTADO' => $laFila[7]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDato) == 0) {
         $this->pcError = 'NO HAY TRANFERENCIAS';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga centros de responsabilidad de centro de costo
   // 2022-02-03 FPM Creacion
   // ------------------------------------------------------------------------
   public function omCargarCentroResponsabilidadSinActivos() {
      $llOk = $this->mxValParamCargarCentroResponsabilidadSinActivos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCargarCentroResponsabilidadSinActivos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamCargarCentroResponsabilidadSinActivos() {
      if (!isset($this->paData['CCENCOS']) or !preg_match("/[0-9,A-Z]{3}/", $this->paData['CCENCOS'])) {
      //if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarCentroResponsabilidadSinActivos($p_oSql) {
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO EXISTE";
         return false;
      } 
      $lcSql = "SELECT DISTINCT(A.cCenRes), A.cEstado, A.cDescri FROM S01TRES A
                 WHERE A.cCenCos = '{$this->paData['CCENCOS']}'  and A.cEstado = 'A' ORDER BY cDescri";
      // $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENRES'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga centros de responsabilidad de centro de costo
   // 2022-10-04 gch Creacion
   // ------------------------------------------------------------------------
   public function omCargarCentroResponsabilidad_DestinoSinActivos() {
      $llOk = $this->mxValParamCargarCentroResponsabilidadSinActivos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCargarCentroResponsabilidad_DestinoSinActivos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamCargarCentroResponsabilidad_DestinoSinActivos() {
      if (!isset($this->paData['CCENCOS']) or !preg_match("/[0-9,A-Z]{3}/", $this->paData['CCENCOS'])) {
      //if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarCentroResponsabilidad_DestinoSinActivos($p_oSql) {
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO EXISTE";
         return false;
      } 
      $lcSql = "SELECT DISTINCT(cCenRes), cEstado, cDescri FROM S01TRES 
                 WHERE cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENRES'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      return true;
   }

   // ---------------------------------------------
   // Buscar AFs por codigo - informatica
   // 2022-03-22 GCH Creacion
   // ----------------------------------------------
   public function omBuscarActivoFijoInformatica() {
      $llOk = $this->mxValParamBuscarActivoFijoInformatica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarActivoFijoInformatica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarActivoFijoInformatica() {
      if (!isset($this->paData['CCODDES'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarActivoFijoInformatica($p_oSql) {
      $lcCodigo = str_replace('-','', $this->paData['CCODDES']);
      // buscra por codigo
      if ($lcCodigo > 5) {
         $lcTipAfj = substr($lcCodigo, 0, 5);
         $lnCorrel = substr($lcCodigo, 5);
         if ($lnCorrel > 0) {
            $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!$laTmp or count($laTmp) == 0) {
               ;
            } else {
               $lcSql = "SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                        A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, A.cCodEmp, A.cNomEmp, B.cDescri 
                        FROM F_E04MAFJ_2('$laTmp[0]') A INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ WHERE A.cSituac != 'B'";
               // print_r($lcSql);  
               $R1 = $p_oSql->omExec($lcSql);
               while ($laTmp = $p_oSql->fetch($R1)) {
                  $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
                  $this->paDatos = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2],
                                      'CDESCRI'=> $laTmp[3], 'CESTADO'=> $laTmp[4], 'CDESEST'=> $laTmp[5], 
                                      'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7],  
                                      'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 'CCENCOS'=> $laTmp[10],
                                      'CDESCEN'=> $laTmp[11], 'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $laTmp[13],
                                      'CDESTIP'=> $laTmp[14], 'CCODIGO'=> $lcCodigo,];
               }
               if (count($this->paDatos) == 0) {
                  $this->pcError = 'ACTIVO FIJO NO ENCONTADO O DADO DE BAJA';
                  return false;
               }
               return true;
            }
         }
      }else{
         $this->pcError = 'CÓDIGO NO EXISTE';
         return false;
      }
      return true;
   }

   // ----------------------------------------
   // Cargar Reporte Transferencias
   // 2022-05-11 Creación GCH
   // ----------------------------------------
   public function omReporteTransInformatica() {
      //print_r('111');
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteTransInformatica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      $laDato = $this->paDato;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $lo->paDato = $laDato;
      $llOk = $lo->omPrintReporteTransInformatica();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      //print_r($this->paData);
      return $llOk;
   }

   // ------------------------------------------------------------------------
   // Realizar transferencia de informatica
   // 2022-11-10 Creacion
   // ------------------------------------------------------------------------
   public function omTransferenciaInformatica() {      
      $llOk = $this->mxValParamTransferenciaInformatica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGuardarTransferenciaInformatica($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamTransferenciaInformatica() {
      $loDate = new CDate();
      if (!isset($this->paData['CIDTRNF'])) {
         $this->pcError = "ID DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['DTRASLA'])){
         $this->pcError = "FECHA DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CDESCRI'])){
         $this->pcError = "DESCRIPCIÓN DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CRESCEN'])){
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CCODEMP'])){
         $this->pcError = "PERSONA RESPONSABLE NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CCENRES'])){
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CCODRES'])){
         $this->pcError = "PERSONA RESPONSABLE NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxGuardarTransferenciaInformatica($p_oSql) {
      $this->paData['CIDTRNF'] = '0000';
      $lcSql = "SELECT MAX(cIdTrnf) FROM E04MTRF";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!(!$laTmp or count($laTmp) == 0 or $laTmp[0] == null)) {
         $this->paData['CIDTRNF'] = $laTmp[0];
      }
      //1952- DANIEL
      if($this->paData['CUSUCOD'] === '1952'){
         $this->paData['CDESCRI']  = strtoupper(trim('INFORMATICA- '.$this->paData['CDESCRI']));
      }elseif($this->paData['CUSUCOD'] === '2368'){
         $this->paData['CDESCRI']  = strtoupper(trim('REDES- '.$this->paData['CDESCRI']));
      }else {
         $this->paData['CDESCRI']  = strtoupper(trim($this->paData['CDESCRI']));
      }
      $this->paData['CIDTRNF'] = fxCorrelativo($this->paData['CIDTRNF']);
      $lcSql = "INSERT INTO E04MTRF (cIdTrnf, cEstado, dTrasla, cDescri, cCenRes, cCodEmp, tRegist, cResCen, cCodRec, tRecepc,  cUsuAdm, tAprAdm, cUsuCod) VALUES
              ('{$this->paData['CIDTRNF']}', 'A', '{$this->paData['DTRASLA']}', '{$this->paData['CDESCRI']}', '{$this->paData['CCENRES']}', '{$this->paData['CCODEMP']}', NOW(),
               '{$this->paData['CRESCEN']}', '{$this->paData['CCODRES']}', NOW(), '{$this->paData['CUSUCOD']}', NOW(), '{$this->paData['CUSUCOD']}')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR CABECERA DE TRANSFERENCIA";
         return false;
      }      
      foreach ($this->paDatos as $laFila) {
         $lcSql = "INSERT INTO E04DTRF (cIdTrnf, cActFij, cUsuCod) 
                     VALUES ('{$this->paData['CIDTRNF']}', '{$laFila['CACTFIJ']}', '{$this->paData['CUSUCOD']}')";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR DETALLE TRANSFERENCIA";
            return false;
         }
      }
      // CAMBIAR ACTIVO FIJO
      foreach ($this->paDatos as $laFila) {
         $lcSql = "UPDATE E04MAFJ SET cCenRes = '{$this->paData['CRESCEN']}', cCodEmp = '{$this->paData['CCODRES']}' WHERE cActFij = '{$laFila['CACTFIJ']}'";
         // print_r($lcSql);
         // echo "<br>";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
            return false;
         }
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Enviar email de conformidad ambas partes
   // 2023-03-27 GCH
   // ------------------------------------------------------------------------
   public function omEnviarEmailConformidadInformtica() {      
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $this->paDatos = null;
      $llOk = $this->mxReporteTransInformatica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      $laDato = $this->paDato;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $lo->paDato = $laDato;
      $llOk = $lo->omPrintReporteTransInformatica();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      // print_r($this->paData);

      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosEmailInformatica($loSql);
      $loSql->omDisconnect();
      $laDato = $this->laData;
      $lo = new CEmail();
      $lo->paData = $laDato;
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }

   protected function mxReporteTransInformatica($p_oSql) {
      // print_r($this->paData);
      $lcIdTrnf = $this->paData['CIDTRNF'];
      $lcSql = "SELECT A.cIdTrnf, A.cEstado, A.dTrasla, A.cDescri, A.cCenRes, A.cCodEmp, A.cResCen, A.cCodRec,
                   B.cDescri, B.cCenCos, C.cDescri, D.cNombre, E.cDescri, E.cCenCos, F.cDescri, G.cNombre, TO_CHAR(A.tRegist, 'YYYY-MM-DD HH12:MI:SS'),
                   D.cNroDni, G.cNroDni
               FROM E04MTRF A
               INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
               INNER JOIN S01TCCO C ON C.cCenCos = B.cCenCos  
               INNER JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cCodEmp
               INNER JOIN S01TRES E ON E.cCenRes = A.cResCen
               INNER JOIN S01TCCO F ON F.cCenCos = E.cCenCos  
               INNER JOIN V_S01TUSU_1 G ON G.cCodUsu = A.cCodRec
               WHERE A.cIdTrnf = '{$lcIdTrnf}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $lcNomOri = str_replace('/', ' ', $laFila[11]);
         $lcNomDes = str_replace('/', ' ', $laFila[15]);
         $this->paData = ['CIDTRNF' => $laFila[0],'CESTADO' => $laFila[1], 'DTRASLA' => $laFila[2],
                           'CDESCRI' => $laFila[3],'CCENRES' => $laFila[4],'CCODEMP' => $laFila[5], 
                           'CRESDES' => $laFila[6],'CEMPDES' => $laFila[7],'CDESRES' => $laFila[8],
                           'CCENCOS' => $laFila[9],'CDESCOS' => $laFila[10],'CNOMEMP' => $lcNomOri,
                           'CRESDESD' => $laFila[12],'CCOSDES' => $laFila[13], 'CCODEDES' => $laFila[14], 
                           'DNOMDES' => $lcNomDes,'TREGIST'=>$laFila[16], 'CNROORI' => $laFila[17], 'CNRODES' => $laFila[18]];
      }
      if (count($this->paData) == 0) {
         $this->pcError = 'NO HAY DATOS CABECERA PARA IMPRIMIR REPORTE TRANFERENCIAS';
         return false;
      }
      $lcSql = "SELECT A.cIdTrnf, A.cActFij, B.cTipAfj, B.nCorrel, B.cDescri, B.mDatos
                  FROM E04DTRF A
                  INNER JOIN E04MAFJ B On B.cActFij = A.cActFij
                  WHERE A.cIdTrnf = '{$lcIdTrnf}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $lcCodigo = substr($laFila[2], 0, 2).'-'.substr($laFila[2], 2, 5).'-'.right('00000'.strval($laFila[3]), 6);
         $this->paDatos[] = ['CIDTRNF' => $laFila[0], 'CACTFIJ' => $laFila[1], 'CTIPAFJ' => $laFila[2],
                             'NCORREL' => $laFila[3], 'CDESCRI' => $laFila[4], 'CCODIGO' => $lcCodigo, 'MDATOS' => json_decode($laFila[5], true)];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS DETALLE PARA IMPRIMIR REPORTE TRANFERENCIAS';
         return false;
      }
      //Llenar para firma.
      $lcSql = "SELECT cCodUsu, replace(cNombre, '/',' '), cNroDni, cEmail, cDescar FROM V_S01TUSU_1 
               WHERE cCodUsu IN ('{$this->paData['CCODEMP']}') and cEstado = 'A'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CCODUSU' => $laFila[0],'CNOMBRE' => $laFila[1], 'CNRODNI' => $laFila[2],'CEMAIL' => $laFila[3],'CDESCAR' => $laFila[4]];
      }
      $lcSql = "SELECT cCodUsu, replace(cNombre, '/',' '), cNroDni, cEmail, cDescar FROM V_S01TUSU_1 
               WHERE cCodUsu IN ('{$this->paData['CEMPDES']}') and cEstado = 'A'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CCODUSU' => $laFila[0],'CNOMBRE' => $laFila[1], 'CNRODNI' => $laFila[2],'CEMAIL' => $laFila[3],'CDESCAR' => $laFila[4]];
      }
      return true;
   }

   public function mxDatosEmailInformatica($p_oSql){
      //print_r($this->paData);
      $laEmail = [];
      if(in_array($this->paData['CCODEMP'], ['3280','1872','1952','2716'])){
         $lcSql = "SELECT cEmail FROM V_S01TUSU_1 WHERE cCodUsu IN ('{$this->paData['CCODEMP']}') and cEstado = 'A'";
         $R1 = $p_oSql->omExec($lcSql);
         while($laFila = $p_oSql->fetch($R1)){
            $laEmail[] = ['EMAIL' => $laFila[0]];
         }
      }
      if(in_array($this->paData['CEMPDES'], ['3280','1872','1952','2716'])){
         $lcSql = "SELECT cEmail FROM V_S01TUSU_1 WHERE cCodUsu IN ('{$this->paData['CEMPDES']}') and cEstado = 'A'";
         $R1 = $p_oSql->omExec($lcSql);
         while($laFila = $p_oSql->fetch($R1)){
            $laEmail[] = ['EMAIL' => $laFila[0]];
         }
      }
      $lcFolder = "/var/www/html/ERP-II/Docs/TransActFij/";
      //print_r($lcFolder);
      if (!is_dir($lcFolder)) {
         $perm = "0777";             
         $modo = intval( $perm, 8 ); 
         mkdir( $lcFolder, $modo ); 
         chmod( $lcFolder, $modo);
      }
      $lcFilePath = $lcFolder.'T'.$this->paData['CIDTRNF'].'.pdf';
      $this->laData = ['AEMAIL'=>$laEmail, 'CDOCADJ'=> $lcFilePath];
      return true;;
   }



   // -----------------------------------------------------------------------
   // INICIO PANTALLA AFJ1170 - TRANSFERIR DE VARIOS CEN.RESP. A UN CEN.RESP.
   // ------------------------------------------------------------------------
   // Buscar AFs por codigo para cambiar su centro de resp y empleado
   // 2022-03-22 GCH Creacion
   // ----------------------------------------------------------------
   public function omAgregarDisfCenResp() {
      $llOk = $this->mxValParamAgregarDisfCenResp();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAgregarDisfCenResp($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamAgregarDisfCenResp() {
      if (!isset($this->paData['CCODIGO'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAgregarDisfCenResp($p_oSql) {
      $lcCodigo = str_replace('-','', $this->paData['CCODIGO']);
      $lcTipAfj = substr($lcCodigo, 0, 5);
      $lnCorrel = substr($lcCodigo, 5);
      $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         ;
      } else {
         $lcSql = "SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, A.cDesEst, A.cSituac, A.cDesSit, 
                   A.cCenRes, A.cDesRes, A.cCenCos, A.cDesCen, A.cCodEmp, A.cNomEmp, B.cDescri 
                   FROM F_E04MAFJ_2('$laTmp[0]') A INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ ";
         //  print_r($lcSql);  
         $R1 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R1)) {
            $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
            $this->paData = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3], 'CESTADO'=> $laTmp[4], 
                              'CDESEST'=> $laTmp[5], 'CSITUAC'=> $laTmp[6], 'CDESSIT'=> $laTmp[7], 'CCENRES'=> $laTmp[8], 'CDESRES'=> $laTmp[9], 
                              'CCENCOS'=> $laTmp[10], 'CDESCEN'=> $laTmp[11], 'CCODEMP'=> $laTmp[12], 'CNOMEMP'=> $laTmp[13],
                              'CDESTIP'=> $laTmp[14], 'CCODIGO'=> $lcCodigo,];
         }
         if (count($this->paData) == 0) {
            $this->pcError = 'NO SE ENCONTRO ACTIVO FIJO ';
            return false;
         }
         return true;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Realizar transferencia de varios centros de resp a uno
   // 2022-11-10 Creacion
   // ------------------------------------------------------------------------
   public function omGuardarDisfeCenResp() {      
      $llOk = $this->mxValParamGuardarDisfeCenResp();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGuardarDisfeCenResp($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGuardarDisfeCenResp() {
      $loDate = new CDate();
      if(!isset($this->paData['DTRASLA'])){
         $this->pcError = "FECHA DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CDESCRI'])){
         $this->pcError = "DESCRIPCIÓN DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CCENRES'])){
         $this->pcError = "CENTRO DE RESPONSABILIDAD ORIGEN NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CCODEMP'])){
         $this->pcError = "PERSONA RESPONSABLE ORIGEN NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CRESDES'])){
         $this->pcError = "CENTRO DE RESPONSABILIDAD DESTINO NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CCODDES'])){
         $this->pcError = "PERSONA RESPONSABLE DESTINO  NO DEFINIDO O INVÁLIDO";
         return false;
      }

      return true;  
   }

   protected function mxGuardarDisfeCenResp($p_oSql) {
      $lcSql = "SELECT MAX(cIdTrnf) FROM E04MTRF";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!(!$laTmp or count($laTmp) == 0 or $laTmp[0] == null)) {
         $this->paData['CIDTRNF'] = $laTmp[0];
      }
      $this->paData['CIDTRNF'] = fxCorrelativo($this->paData['CIDTRNF']);
      $lcSql = "INSERT INTO E04MTRF (cIdTrnf, cEstado, dTrasla, cDescri, cCenRes, cCodEmp, tRegist, cResCen, cCodRec, tRecepc,  cUsuAdm, tAprAdm, cUsuCod) VALUES
              ('{$this->paData['CIDTRNF']}', 'A', '{$this->paData['DTRASLA']}', '{$this->paData['CDESCRI']}', '{$this->paData['CCENRES']}', '{$this->paData['CCODEMP']}', NOW(),
               '{$this->paData['CRESDES']}', '{$this->paData['CCODDES']}', NOW(), '{$this->paData['CUSUCOD']}', NOW(), '{$this->paData['CUSUCOD']}')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR CABECERA DE TRANSFERENCIA";
         return false;
      }      
      foreach ($this->paDatos as $laFila) {
         $lcSql = "INSERT INTO E04DTRF (cIdTrnf, cActFij, cUsuCod) 
                     VALUES ('{$this->paData['CIDTRNF']}', '{$laFila['CACTFIJ']}', '{$this->paData['CUSUCOD']}');";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR DETALLE TRANSFERENCIA";
            return false;
         }
      }
      // CAMBIAR ACTIVO FIJO
      foreach ($this->paDatos as $laFila) {
         $lcSql = "UPDATE E04MAFJ SET cCenRes = '{$this->paData['CRESDES']}', cCodEmp = '{$this->paData['CCODDES']}' WHERE cActFij = '{$laFila['CACTFIJ']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
            return false;
         }
      }
      return true;
   }

   //------------------------------------------------------------------------
   // IMPRIMIR PDF DE TRANSFERENCIAS
   // CREADO GCH 08-05-2023
   //------------------------------------------------------------------------
   public function  omPrintTransferenciaPDF(){
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteTrans($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      $laDato = $this->paDato;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $lo->paDato = $laDato;
      $llOk = $lo->omPrintReporteTrans();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return true;
   }
   
   // ------------------------------------------------------------------------
   // Enviar email de conformidad ambas partes
   // 2023-03-27 GCH
   // ------------------------------------------------------------------------
   public function omGenerarTransferenciaPDF() {      
      $llOk = $this->mxValParamGuardarDisfeCenResp();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $this->paDatos = null;
      $llOk = $this->mxReporteTrans($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      $laDato = $this->paDato;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $lo->paDato = $laDato;
      $llOk = $lo->omPrintReporteTrans();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return true;
   }

   // ------------------------------------------------------------------------
   // Enviar email de conformidad ambas partes
   // 2023-03-27 GCH
   // ------------------------------------------------------------------------
   public function omEnviarEmailConformidad() {      
      $llOk = $this->mxValParamGuardarDisfeCenResp();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $this->paDatos = null;
      $llOk = $this->mxReporteTrans($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      $laDato = $this->paDato;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $lo->paDato = $laDato;
      $llOk = $lo->omPrintReporteTrans();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      // print_r($this->paData);

      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosEmail($loSql);
      $loSql->omDisconnect();
      $laDato = $this->laData;
      $lo = new CEmail();
      $lo->paData = $laDato;
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }

   protected function mxReporteTrans($p_oSql) {
      // print_r($this->paData);
      $lcIdTrnf = $this->paData['CIDTRNF'];
      $lcSql = "SELECT A.cIdTrnf, A.cEstado, A.dTrasla, A.cDescri, A.cCenRes, A.cCodEmp, A.cResCen, A.cCodRec,B.cDescri, B.cCenCos, C.cDescri, D.cNombre, 
                        E.cDescri, E.cCenCos, F.cDescri, G.cNombre, TO_CHAR(A.tRegist, 'YYYY-MM-DD HH12:MI:SS'), D.cNroDni, G.cNroDni
               FROM E04MTRF A
               INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
               INNER JOIN S01TCCO C ON C.cCenCos = B.cCenCos  
               INNER JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cCodEmp
               INNER JOIN S01TRES E ON E.cCenRes = A.cResCen
               INNER JOIN S01TCCO F ON F.cCenCos = E.cCenCos  
               INNER JOIN V_S01TUSU_1 G ON G.cCodUsu = A.cCodRec
               WHERE A.cIdTrnf = '{$lcIdTrnf}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $lcNomOri = str_replace('/', ' ', $laFila[11]);
         $lcNomDes = str_replace('/', ' ', $laFila[15]);
         $this->paData = ['CIDTRNF' => $laFila[0],'CESTADO' => $laFila[1], 'DTRASLA' => $laFila[2],
                           'CDESCRI' => $laFila[3],'CCENRES' => $laFila[4],'CCODEMP' => $laFila[5], 
                           'CRESDES' => $laFila[6],'CEMPDES' => $laFila[7],'CDESRES' => $laFila[8],
                           'CCENCOS' => $laFila[9],'CDESCOS' => $laFila[10],'CNOMEMP' => $lcNomOri,
                           'CRESDESD' => $laFila[12],'CCOSDES' => $laFila[13], 'CCODEDES' => $laFila[14], 
                           'DNOMDES' => $lcNomDes, 'TREGIST' => $laFila[16], 'CNROORI' => $laFila[17], 'CNRODES' => $laFila[18]];
      }
      if (count($this->paData) == 0) {
         $this->pcError = 'NO HAY DATOS CABECERA PARA IMPRIMIR REPORTE TRANFERENCIAS';
         return false;
      }
      $lcSql = "SELECT A.cIdTrnf, A.cActFij, B.cTipAfj, B.nCorrel, B.cDescri, B.nMontmn
                  FROM E04DTRF A
                  INNER JOIN E04MAFJ B On B.cActFij = A.cActFij
                  WHERE A.cIdTrnf = '{$lcIdTrnf}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $lcCodigo = substr($laFila[2], 0, 2).'-'.substr($laFila[2], 2, 5).'-'.right('00000'.strval($laFila[3]), 6);
         $this->paDatos[] = ['CIDTRNF' => $laFila[0], 'CACTFIJ' => $laFila[1], 'CTIPAFJ' => $laFila[2],
                             'NCORREL' => $laFila[3], 'CDESCRI' => $laFila[4], 'CCODIGO' => $lcCodigo, 'NMONTO' => $laFila[5]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS DETALLE PARA IMPRIMIR REPORTE TRANFERENCIAS';
         return false;
      }

      $lcSql = "SELECT cCodUsu, replace(cNombre, '/',' '), cNroDni, cEmail, cDescar FROM V_S01TUSU_1 
               WHERE cCodUsu IN ('{$this->paData['CCODEMP']}') and cEstado = 'A'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CCODUSU' => $laFila[0],'CNOMBRE' => $laFila[1], 'CNRODNI' => $laFila[2],'CEMAIL' => $laFila[3],'CDESCAR' => $laFila[4]];
      }
      $lcSql = "SELECT cCodUsu, replace(cNombre, '/',' '), cNroDni, cEmail, cDescar FROM V_S01TUSU_1 
               WHERE cCodUsu IN ('{$this->paData['CEMPDES']}') and cEstado = 'A'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDato[] = ['CCODUSU' => $laFila[0],'CNOMBRE' => $laFila[1], 'CNRODNI' => $laFila[2],'CEMAIL' => $laFila[3],'CDESCAR' => $laFila[4]];
      }
      return true;
   }

   public function mxDatosEmail($p_oSql){
      $laEmail = [];
      if(!in_array($this->paData['CCODEMP'], ['1872','2716'])){
         $lcSql = "SELECT cEmail FROM V_S01TUSU_1 WHERE cCodUsu IN ('{$this->paData['CCODEMP']}') and cEstado = 'A'";
         $R1 = $p_oSql->omExec($lcSql);
         while($laFila = $p_oSql->fetch($R1)){
            $laEmail[] = ['EMAIL' => $laFila[0]];
         }
      }
      if(!in_array($this->paData['CEMPDES'], ['1872','2716'])){
         $lcSql = "SELECT cEmail FROM V_S01TUSU_1 WHERE cCodUsu IN ('{$this->paData['CEMPDES']}') and cEstado = 'A'";
         $R1 = $p_oSql->omExec($lcSql);
         while($laFila = $p_oSql->fetch($R1)){
            $laEmail[] = ['EMAIL' => $laFila[0]];
         }
      }     
      $lcFolder = "/var/www/html/ERP-II/Docs/TransActFij/T".$this->paData['CIDTRNF'].".pdf";
      if (!is_dir($lcFolder)) {
         $perm = "0777";             
         $modo = intval( $perm, 8 ); 
         mkdir( $lcFolder, $modo ); 
         chmod( $lcFolder, $modo);
      }
      $this->laData = ['AEMAIL'=>$laEmail, 'CECOPIA'=> 'inventario@ucsm.edu.pe', 'CDOCADJ'=> $lcFolder];
      return true;;
   }

   // ------------------------------------------------------------------------
   // Cambiar Descripcion
   // 2023-08-16 Creacion GCH
   // ------------------------------------------------------------------------
   public function omCambiarDescripcion() {      
      $llOk = $this->mxValParamCambiarDescripcion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCambiarDescripcion($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCambiarDescripcion() {
      //print_r($this->paData['CIDTRNF']);
      if(!isset($this->paData['CIDTRNF'])){
         $this->pcError = "ID DE LA TRANSFERENCUA NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif(!isset($this->paData['CDESCRI'])){
         $this->pcError = "DESCRIPCIÓN DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxCambiarDescripcion($p_oSql) {
      if($this->paData['CUSUCOD'] === '1952'){
         $this->paData['CDESCRI']  = strtoupper(trim('INFORMATICA- '.$this->paData['CDESCRI']));
      }elseif($this->paData['CUSUCOD'] === '2368'){
         $this->paData['CDESCRI']  = strtoupper(trim('REDES- '.$this->paData['CDESCRI']));
      }else {
         $this->paData['CDESCRI']  = strtoupper(trim($this->paData['CDESCRI']));
      }
      $lcSql = "UPDATE e04mtrf SET cDescri = '{$this->paData['CDESCRI']}' WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR LA DESCRIPCIÓN DE LA TRANSFERENCIA ";
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Eliminar transferencia
   // 2023-08-16 Creacion GCH
   // ------------------------------------------------------------------------
   public function omEliminarTransferencia() {      
      $llOk = $this->mxValParamEliminarTransferencia();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEliminarTransferencia($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamEliminarTransferencia() {
      if(!isset($this->paData['CIDTRNF'])){
         $this->pcError = "ID DE LA TRANSFERENCUA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxEliminarTransferencia($p_oSql) {
      $lcSql = "SELECT A.cActFij FROM E04DTRF A WHERE A.cIdTrnf = '{$this->paData['CIDTRNF']}'";
      //print_r($lcSql);
      $R2 = $p_oSql->omExec($lcSql);
      while ($latmp = $p_oSql->fetch($R2)){
         $this->paDatos[] = ['CACTFIJ' => $latmp[0]];
      }
      if($this->paDatos === NULL){
         $lcSql = "DELETE FROM E04MTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
            return false;
         }
      }else{
         //Recuperar Centro de responsabilidad y empleado responsable anterior 
         $lcSql = "SELECT A.cCenRes, A.cCodEmp FROM E04MTRF A WHERE A.cIdTrnf = '{$this->paData['CIDTRNF']}'";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $this->paDato = ['CCENRES' => $laFila[0], 'CCODEMP' => $laFila[1]];
         }
         // eliminar detalle de transferencia
         $lcSql = "DELETE FROM E04DTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
            return false;
         }
         //Eliminando cabecera de transferencia
         $lcSql = "DELETE FROM E04MTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
            return false;
         }
         foreach ($this->paDatos as $laFila) {
            //revertir centro de responsabilidad y empleado responsable
            $lcSql = "UPDATE E04MAFJ SET cCenRes = '{$this->paDato['CCENRES']}', cCodEmp = '{$this->paDato['CCODEMP']}' WHERE cActFij = '{$laFila['CACTFIJ']}'";
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
               return false;
            }
         }
      }
      return true;
   }

   // -----------------------------------------------------------------------
   // INICIO PANTALLA AFJ1030 - BUSCAR TRANSFERENCIA DE ACTIVO FIJO
   // ------------------------------------------------------------------------
   // Init BUSCAR TRANSFERENCIA DE ACTIVO FIJO
   // 2023-08-16 GCH Creacion
   // ------------------------------------------------------------------------
      public function omInitMantBuscarTransferencia() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParam($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   // ------------------------------------------------------------------------
   // Buscar Transferencia
   // 2023-08-16 GCH Creacion
   // ------------------------------------------------------------------------
   public function omBuscarTransferenciasActFij() {
      $llOk = $this->mxValParamBuscarTransferenciasActFij();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarTransferenciasActFij($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarTransferenciasActFij() {
      if (!isset($this->paData['CCODIGO'])) {   
         $this->pcError = 'CÓDIGO DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxBuscarTransferenciasActFij($p_oSql) {
      $this->paData['CCODIGO'] = str_replace('-','',$this->paData['CCODIGO']);
      if (strlen($this->paData['CCODIGO']) > 5) {
         $lcTipAfj = substr($this->paData['CCODIGO'], 0, 5);
         $lnCorrel = substr($this->paData['CCODIGO'], 5);
         if ($lnCorrel > 0) {
            $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj' AND nCorrel = $lnCorrel";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $cCodigo = $laTmp[0];
            if (!$laTmp or count($laTmp) == 0) {
               $this->pcError = "CÓDIGO DE TRANSFERENCIA NO EXISTE";
               return false;
            } 
         }
      }else{
         $cCodigo = $this->paData['CCODIGO'];
      }
      $lcSql = "SELECT A.cIdtrnf, A.dTrasla, A.cDescri,H.cCenCos, H.cDescri, A.cCenres ,F.cDescri, A.cCodemp, REPLACE(C.cNombre,'/',' '), I.cCenCos, I.cDescri,  A.cResCen,G.cDescri ,A.cCodRec, REPLACE(D.cNombre,'/',' '), A.cUsuAdm, REPLACE(E.cNombre,'/',' ') 
                  FROM E04MTRF A
                  INNER JOIN E04DTRF B ON B.cIdtrnf = A.cIdtrnf
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodemp
                  INNER JOIN V_S01TUSU_1 D ON D.cCodUsu = A.cCodRec
                  INNER JOIN V_S01TUSU_1 E ON E.cCodUsu = A.cUsuAdm
                  INNER JOIN S01TRES F ON F.cCenRes =  A.cCenres
                  INNER JOIN S01TRES G ON G.cCenRes =  A.cResCen
                  INNER JOIN S01TCCO H ON H.cCencos = F.cCencos
                  INNER JOIN S01TCCO I ON I.cCencos = G.cCencos
                  WHERE B.CACTFIJ = '$cCodigo' ORDER BY A.dTrasla desc";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CIDTRNF' => $laFila[0], 'DTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],'CCENCOS' => $laFila[3], 'CDESCOS' => $laFila[4], 'CCENRES' => $laFila[5],'CDESCEN' => $laFila[6], 
                             'CCODEMP' => $laFila[7], 'CNOMBRE' => $laFila[8], 'CCOSDES' => $laFila[9], 'CDESDES' => $laFila[10], 'CRESCEN' => $laFila[11], 'CRESDES' => $laFila[12], 'CCODREC' => $laFila[13],
                             'CNOMDES' => $laFila[14], 'CUSUADM' => $laFila[15], 'CNOMADM' => $laFila[16]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON TRANFERENCIAS PARA ESTE ACTIVO FIJO';
         return false;
      }
      return true;
   }
   
   // -----------------------------------------------------------------------
   // INICIO PANTALLA AFJ1040 - CONFORMIDAD TRANFERENCIAS
   // ------------------------------------------------------------------------
   // Conformidad Tranferencias
   // 2022-05-11 GCH Creacion
   // ------------------------------------------------------------------------
   public function omInitMantTransConformidad() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParam($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxTransferenciasConformidad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxTransferenciasConformidad($p_oSql) {
      // print_r($this->paData);
      $lcSql = "SELECT cIdTrnf, dTrasla, cDescri, cCenRes, cCodEmp, cResCen, cCodRec, cEstado FROM E04MTRF
                WHERE cEstado = 'A' ORDER BY dTrasla DESC";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CIDTRNF' => $laFila[0], 'CTRASLA' => $laFila[1], 'CDESCRI' => $laFila[2],
                             'CCENRES' => $laFila[3], 'CCODEMP' => $laFila[4], 'CRESCEN' => $laFila[5],
                             'CCODREC' => $laFila[6], 'CESTADO' => $laFila[7]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON TRANFERENCIAS';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Conformidad transferencias
   // 2022-05-12 GCH Creacion
   // ------------------------------------------------------------------------
   public function omConformidadTranferencia() {
      $llOk = $this->mxValParamConformidadTranferencia();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxConformidadTranferencia($loSql);
      if (!$llOk) {
         $loSql->omRollback();
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxTransferenciasConformidad($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamConformidadTranferencia() {
      if (!$this->mxValParam()) {
         return false;
      } elseif (!isset($this->paData['CIDTRNF'])) {   
         $this->pcError = 'ID DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }

   protected function mxConformidadTranferencia($p_oSql) {
      $lcSql = "SELECT cEstado FROM E04MTRF WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = "ID DE TRANSFERENCIA NO EXISTE";
         return false;
      } elseif ($laTmp[0] != 'E') {
         $this->pcError = "ID DE TRANSFERENCIA NO PUEDE SER APROBADA";
         return false;
      }
      $lcSql = "UPDATE E04MTRF SET cEstado = 'C' WHERE cIdTrnf = '{$this->paData['CIDTRNF']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR ESTADO DE TRANSFERENCIA";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------------------
   // INICIO PANTALLA AFJ 1120 - BAJAS-DONACIONES-REMATES
   // ------------------------------------------------------------------------
   // Init BAJAS-DONACIONES-REMATES
   // 2022-07-13 GCH Creacion
   // ------------------------------------------------------------------------
   public function omInitMantBajas() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParam($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxMostarBajas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   // ------------------------------------------------------------------------
   // Mostrar BAJAS-DONACIONES-REMATES
   // 2022-07-13 GCH Creacion
   // ------------------------------------------------------------------------
   protected function mxMostarBajas($p_oSql) {
      // print_r($this->paData);
      $lcSql = "SELECT DISTINCT(A.cCenRes), A.cEstado, A.cDescri, C.cDescri, A.tModifi
               FROM s01tres A
               INNER JOIN E04MAFJ B ON B.cCenRes = A.cCenRes
               INNER JOIN V_S01TTAB C ON C.cCodigo = A.cEstado
               WHERE  C.cCodTab = '333'  ORDER BY A.tModifi desc ";
      // AND A.cCenCos = '1LW'  --- para el centro de costos de control patrimonial BAJA
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CCENRES' => $laFila[0], 'CESTADO' => $laFila[1], 'CDESCRI' => $laFila[2], 'CDESEST' => $laFila[3]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON DONACIONES, BAJAS, REMATES';
         return false;
      }
      return true;
   }
   // ------------------------------------------
   // Cargar Reporte BAJAS, DONACIONES, REMATES
   // 2022-07-13 Creación GCH
   // ------------------------------------------
   public function omReporteDetalleCenResp() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteDetalleCenResp($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $llOk = $lo->omPrintReporteDetalleCenRespBaj();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }
   
   protected function mxReporteDetalleCenResp($p_oSql) {
      // Cabecera del reporte
      $cCenRes= $this->paData['CCENRES'];
      $lcSql = "SELECT cCenRes, cEstado, cDescri FROM s01tres WHERE cCenRes = '$cCenRes'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paData = ['CCENRES' => $laFila[0],'CESTADO' => $laFila[1], 'CDESCRI' => $laFila[2]];
      }
      if (count($this->paData) == 0) {
         $this->pcError = 'NO HAY DATOS CABECERA PARA IMPRIMIR REPORTE';
         return false;
      }
      // Activos fijos
      $lcDate =  date('Y', time());
      $lcSql = "SELECT max(cperiod) FROM e04mdep ";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $laTmp[0] = substr($laTmp[0], 4, 2);
      if ($laTmp[0] ===  '00') {
         $lcDate = $lcDate - 1;
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      } else {
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      }
      $lcSql = "SELECT C.cActFij, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.cSituac, C.nMoncal, C.nDeprec, sum(B.nDeprec), mDatos
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1'
                        AND C.cCenRes =  '$cCenRes'  
                  GROUP BY C.cActFij, c.cTipAfj
                  ORDER BY C.cTipAfj, C.nCorrel";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){       
         $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
         $lnDepPer = $laFila[8] - $laFila[7];
         $lnValNet = $laFila[6] - $laFila[8];
         if($laFila[5] === 'B'){
            if($laFila[6] === $laFila[7]){
               $lnRetiro = 0.00;
            }
            $lnRetiro = $laFila[6] - $laFila[8]; 
            $lnValNet = $laFila[6] - $laFila[8] - $lnRetiro;
         }else{
            $lnRetiro = 0.00;
         } 
         $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCRI' => $laFila[3], 'DFECALT' => $laFila[4], 'CSITUAC' => $laFila[5],'NMONCAL' => $laFila[6], 'NDEPREC' => $laFila[7], 
                              'NSUMDEP' => $laFila[8], 'CCODIGO' => $lcCodigo, 'NDEPPER' => $lnDepPer, 'NVALNET' => $lnValNet, 'NRETIRO' => $lnRetiro, 'MDATOS' => json_decode($laFila[9], true),]; 
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRO ACTIVOS';
         return false;
      }   
      return true;
   }

   // ------------------------------------------
   // Revisar detalle de BAJAS, DONACIONES, REMATES
   // 2022-08-05 Creación GCH
   // ------------------------------------------
   public function omRevisarBaja() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteDetalleCenResp($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      return $llOk;
   }
   // ------------------------------------------------------------------------
   // Dar de bajas Activos de un centro de responsabilidad
   // 2022-07-13 GCH Creacion
   // ------------------------------------------------------------------------
   public function omDarBaja() {
      $llOk = $this->mxValParamDarBaja();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxDarBaja($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamDarBaja() {
      // print_r($this->paData);
      if (!isset($this->paData['CCENRES'])) {   
         $this->pcError = 'CENTRO DE RESPONSABILIDAD NO ENCONTRADO';
         return false;
      } elseif (!isset($this->paData['CDOCBAJ'])) {
         $this->pcError = 'DOCUMENTO DE BAJA NO ENCONTRADO';
         return false;
      } elseif (!isset($this->paData['DFECBAJ'])) {
         $this->pcError = 'FECHA DE BAJA NO ENCONTRADA';
         return false;
      }
      return true;
   }
   protected function mxDarBaja($p_oSql) {
      $lcSql = "SELECT cEstado FROM S01TRES WHERE cCenRes = '{$this->paData['CCENRES']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO EXISTE";
         // return false;
      } elseif ($laTmp[0] != 'E') {
         $this->pcError = "CENTRO DE RESPONSABILIDAD NO EXISTE";
         // return false;
      }
      // Cambie el estado del centro de responsabilidad
      $lcSql = "UPDATE S01TRES SET cEstado = 'B', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()
            WHERE cCenRes = '{$this->paData['CCENRES']}'";
      // print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR CENTRO DE RESPONSABILIDAD";
         return false;
      }
      // cambie el estado de los activos
      $lcSql = "SELECT cActFij, mDatos FROM E04MAFJ WHERE cCenRes = '{$this->paData['CCENRES']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $laTmp = json_decode($laFila[1], true); 
         // print_r($laTmp);
         $laTmp['DFECBAJ'] = $this->paData['DFECBAJ'];
         $laTmp['CDOCBAJ'] = $this->paData['CDOCBAJ'];
         $laTmp = json_encode($laTmp);
         $lcSql = "UPDATE E04MAFJ SET cEstado = 'X', cSituac = 'B', mDatos = '{$laTmp}', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() 
                   WHERE cActFij = '{$laFila[0]}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVOS";
            return false;
         } 
      }
      return true;
   }


   // ----------------------------------------
   //PANTALLA 2040 -  REPORTE POR FECHA
   // ----------------------------------------
   // Cargar Reporte de activos fijos por fecha
   // Creación GCH 2022-04-12
   // ----------------------------------------
   public function omReportePorFechaPDF() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReportePorFecha($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->paDatos;
      $laData = $this->paData;
      // print_r($laData);
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $lo->paData = $laData;
      $llOk = $lo->omPrintReportePorFechaPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxReportePorFecha($p_oSql) {
      $lcSql = "SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cEstado, TO_CHAR(A.dFecAlt, 'YYYY-MM-DD'), A.nMontMN, A.cCenRes, 
                        B.cDescri, A.cCodEmp, C.cNombre, A.mDatos, A.cSituac
               FROM E04MAFJ A 
               INNER JOIN S01TRES B ON A.cCenRes = B.cCenRes 
               INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp 
               WHERE A.dFecAlt >= '{$this->paData['DDESDE']}' AND  A.dFecAlt <= '{$this->paData['DHASTA']}' AND A.cEstado = 'A' 
               ORDER BY A.cCenRes, A.CTIPAFJ, A.nCorrel";
      // print_r ($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
         $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CTIPAFJ' => $laFila[1], 'NCORREL' => $laFila[2], 'CDESCRI' => $laFila[3],
                             'CESTADO' => $laFila[4], 'DFECHA' => $laFila[5], 'NMONTO' => $laFila[6], 'CCENRES' => $laFila[7],
                             'CDESRES' => $laFila[8], 'CCODEMP' => $laFila[9], 'CNOMEMP' => $laFila[10], 'CDATOS' => json_decode($laFila[11], true),
                             'CSITUAC' => $laFila[12], 'CCODIGO' => $lcCodigo];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR REPORTE ACTIVO FIJO POR FECHA';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------
   //PANTALLA 2060 -  REPORTE POR CENTRO DE COSTO
   // -----------------------------------------------------
   // Cargar Reporte de activos fijos por Centro de costo
   // Creación GCH 2022-04-20
   // -----------------------------------------------------
   public function omReportePorCentroCosto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReportePorCentroCosto($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $llOk = $lo->omPrintReportePorCentroCostoPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxReportePorCentroCosto($p_oSql) {
      // print_r($this->paData);
      $lcSql = "SELECT distinct A.cCenCos, A.cDescri, B.cCenRes, B.cDescri
                  FROM S01TCCO A
                  INNER JOIN S01TRES B ON B.cCenCos = A. cCenCos 
                  INNER JOIN E04MAFJ C ON C.cCenRes = B.cCenRes
                  WHERE A.cEstado = 'A' AND C.cSituac = 'O'
                  ORDER BY A.cCenCos, B.cCenRes";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCOS' => $laFila[1], 'CCENRES' => $laFila[2], 
                             'CDESRES' => $laFila[3]];
      }
      // print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR REPORTE ACTIVO FIJO POR FECHA';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------
   // Cargar Reporte de activos fijos por Centro de costo EXCEL
   // Creación GCH 2022-09-16
   // -----------------------------------------------------
   public function omReportePorCentroCostoExcel() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReportePorCentroCosto($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $llOk = $lo->omPrintReportePorCentroCostoExcel();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }

   // -----------------------------------------------------------------------
   // Cargar Reporte de activos fijos por Centro de costo total EXCEL
   // Creación GCH 2023-07-17
   // -----------------------------------------------------------------------
   public function omReportePorCentroCostoExcelTotal() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReportePorCentroCostoTotal($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $llOk = $lo->omPrintReportePorCentroCostoExcelTotal();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }

   protected function mxReportePorCentroCostoTotal($p_oSql) {
      // print_r($this->paData);
      $lcSql = "SELECT distinct A.cCenCos, A.cDescri, B.cCenRes, B.cDescri, B.cEstado
                  FROM S01TCCO A
                  INNER JOIN S01TRES B ON B.cCenCos = A. cCenCos 
                  ORDER BY A.cCenCos, B.cCenRes";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCOS' => $laFila[1], 'CCENRES' => $laFila[2], 'CDESRES' => $laFila[3], 'CESTADO' => $laFila[4]];
      }
      //print_r($this->paDatos); 
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR REPORTE CENTRO DE COSTOS Y CENTROS DE RESPONSABILIDAD';
         return false;
      }
      return true;
   }
   // -----------------------------------------------------
   //PANTALLA 2070 -  REPORTE POR CARACTERISTICAS
   // -----------------------------------------------------
   // Init Mantenimiento Reporte por caracteristicas
   // Creación GCH 2022-05-24
   // -----------------------------------------------------
   public function omInitManRep() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      //$llOk = $this->mxValParamUsuario($loSql, '000');
      //if (!$llOk) {
      //   $loSql->omDisconnect();
      //   return false;
      //}
      $llOk = $this->mxInitManRep($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitManRep($p_oSql) {
      // Cargar Clase Activo
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE  cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      //Cargar Situación
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($this->paSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      //Cargar Tipos de activo fijo
      $lcSql = "SELECT CTIPAFJ, cDescri FROM E04TTIP WHERE  cEstado = 'A' ORDER BY CTIPAFJ";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paTipAfj[] = ['CTIPAFJ' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($this->paTipAfj) == 0) {
         $this->pcError = 'NO HAY TIPOS DE ACTIVO FIJO DEFINIDAS ';
         return false;
      }
      return true;
   }
   // -----------------------------------------------------
   // Cargar AF por caracteristicas
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omBuscarRepCaracteristicas() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarRepCaracteristicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarRepCaracteristicas($p_oSql) {
      $lcSql = "SELECT A.cCenRes, A.cDescri, B.cCenCos, B.cDescri FROM  S01TRES A
               INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
               WHERE A.cCenRes = '{$this->paData['CCENRES']}' or B.cCenCos = '{$this->paData['CCENCOS']}' ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->laData = ['CCENRES' => $laFila[0], 'CDESRES' =>$laFila[1], 'CCENCOS'=> $laFila[2], 'CDESCOS' => $laFila[3]]; 
      }
      if (count($this->laData) == 0) {
         return false;
      }
      if($this->paData['CSITUAC'] === '*'){
         $lcSituac = "'O','B','I'";
      }else{
         $lcSituac = "'".$this->paData['CSITUAC']."'";
      }
      if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*' && $this->paData['CCODEMP'] !== null) {
         //print_r("111");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn, A.nMoncal
                   FROM E04MAFJ A
                   INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                   INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                   WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                   AND A.cSituac IN ({$lcSituac})  AND A.cCodEmp = '{$this->paData['CCODEMP']}'
                   AND A.cCenRes = '{$this->paData['CCENRES']}' 
                   AND A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                   ORDER BY A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } elseif($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*' && $this->paData['CCODEMP'] === null) {
         //print_r("222");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn, A.nMoncal
                   FROM E04MAFJ A
                   INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                   INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                   WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                   AND A.cSituac IN ({$lcSituac}) AND A.cCenRes = '{$this->paData['CCENRES']}' 
                   AND A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                   ORDER BY A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] === null){
         //OJO REVISAR  SALEN ACTIVOS SIN CENTRO DE REPONSABILIDAD Y SIN PERSONA RESPONSABLE
         //print_r("333");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn, A.nMoncal
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' AND A.cSituac IN ({$lcSituac}) 
                     AND A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] === null){
         //print_r("6666");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn , A.nMoncal
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) AND  E.cCenCos = '{$this->paData['CCENCOS']}' 
                     AND A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] !== null){
         //print_r("444");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn , A.nMoncal
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) AND  E.cCenCos = '{$this->paData['CCENCOS']}' 
                     AND A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                     AND A.cCodEmp = '{$this->paData['CCODEMP']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] !== null){
         //print_r("555");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn , A.nMoncal
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) 
                     AND A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                     AND A.cCodEmp = '{$this->paData['CCODEMP']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } 
      // die;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laFila[6], 0, 2).'-'.substr($laFila[6], 2, 5).'-'.right('00000'.strval($laFila[7]), 6);
         $lcNombre = str_replace('/',' ',$laFila[9]);
         $this->paDatos[] = ['CACTFIJ'=> $laFila[0], 'CDESCRI'=> $laFila[1], 'DFECALT'=> $laFila[2], 'CCENRES'=> $laFila[3],
                             'CDESRES'=> $laFila[4], 'CDATOS'=> json_decode($laFila[5], true), 'CCODIGO'=> $lcCodigo, 
                             'CCODEMP'=> $laFila[8], 'CNOMEMP'=> $lcNombre, 'CSITUAC'=> $laFila[10], 'NMONTO'=> $laFila[11], 'NMONCAL' => $laFila[12]];
         $lcSql = "SELECT DISTINCT(cActFij) FROM E04DCOM WHERE cActFij = '$laFila[0]'";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $lcSql = "SELECT A.cActFij, D.cDescri, D.dFecAlt, D.mDatos, D.cSituac, D.nMonto, D.cEtique, D.nSecuen 
                      FROM F_E04MAFJ_2('$laFila[0]') A
                      INNER JOIN E04DCOM D ON D.cActFij = A.cActFij ORDER BY D.nSecuen";
            $R3 = $p_oSql->omExec($lcSql);
            while ($laTmp1 = $p_oSql->fetch($R3)) {
               $lcCodig1 = $lcCodigo.'-'.strval($laTmp1[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp1[0], 'CDESCRI'=> $laTmp1[1], 'DFECALT'=> $laTmp1[2], 'CCENRES'=> $laFila[3],
                                   'CDESRES'=> $laFila[4], 'CDATOS'=> json_decode($laTmp1[3], true), 'CCODIGO'=> $lcCodig1, 
                                   'CCODEMP'=> $laFila[8], 'CNOMEMP'=> $lcNombre, 'CSITUAC'=> $laTmp1[4], 'NMONTO'=> $laTmp1[5],
                                   'CETIQUE'=> $laTmp1[6], 'NMONCAL' => $laFila[12]]; 
            }
         }
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON ACTIVOS FIJOS';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------
   // Cargar Reporte por caracteristicas PDF
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omRepCaracteristicasPDF() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData  = $this->paData;
      $lo->laData  = $this->laData;
      $llOk = $lo->omRepCaracteristicasPDFAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paDatas['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // -----------------------------------------------------
   // Cargar Reporte por caracteristicas EXCEL
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omRepCaracteristicasEXCEL() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      //print_r($lo->paDatos);
      $llOk = $lo->omRepCaracteristicasEXCELAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }


   // -----------------------------------------------------
   // PANTALLA 2100 -  REPORTE ANALISIS DE ACTIVOS FIJOS
   // -----------------------------------------------------
   // BUSCAR ANALISIS DE ACTIVOS FIJOS
   // Creación GCH 2022-11-17
   // -----------------------------------------------------
   public function omBuscarAFAnalisis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarAFAnalisis($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAFAnalisis($p_oSql) {
      //print_r($this->paData);
      $this->paData['DFECINI'] = str_replace('-','',$this->paData['DFECINI']);
      $lcDatIni = substr($this->paData['DFECINI'], 0, 4).'00';
      $lcDatFin = substr($this->paData['DFECINI'], 0, 6);
      $lcSql = "SELECT cPeriod, DMOVIMI FROM E04MDEP WHERE cPeriod = '$lcDatFin'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = "NO SE ENCONTRO DEPRECIACIÓN DEL MES ";
         return false;
      }
      if($this->paData['CCENRES'] == '*'){
         $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $this->laData = ['CCENRES'=> '*', 'CDESRES' => 'C. Respons', 'CCENCOS'=> $laFila[0], 'CDESCOS' => $laFila[1], 'CDEPREC' =>$laTmp[1]];
      } else{
         $lcSql = "SELECT A.cCenRes, A.cDescri, A.cCencos, B.cDescri from S01TRES A 
                  INNER JOIN S01TCCO B ON B.cCencos = A.cCencos
                  WHERE A.cCencos = '{$this->paData['CCENCOS']}' AND A.cCenRes = '{$this->paData['CCENRES']}'  ";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $this->laData = ['CCENRES'=> $laFila[0], 'CDESRES' => $laFila[1], 'CCENCOS'=> $laFila[2], 'CDESCOS' => $laFila[3], 'CDEPREC' =>$laTmp[1]];
      }
      // $lcDate =  date('Y', time());
      
      if($this->paData['CSITUAC'] === '*'){
         $lcSituac = "'O','B','I'";
      }else{
         $lcSituac = "'".$this->paData['CSITUAC']."'";
      }
      // $lcDate =  date('Y', time());
      if($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*'){
         $lcSql = "SELECT C.cActFij, F.cDescri, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.nMoncal, C.nDeprec, sum(B.nDeprec),
                     sum(B.nDeprec) - C.nDeprec AS DepAcu, F.nfacdep, C.cSituac
                     FROM E04MDEP A
                     INNER JOIN E04DDEP B ON B.cIdDepr =  A.cIdDepr
                     INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij
                     INNER JOIN E04TTIP F ON F.cTipAfj = C.cTipAfj 
                     where  A.cperiod >= '$lcDatIni' and A.cperiod <= '$lcDatFin' and C.cCenRes = '{$this->paData['CCENRES']}' 
                     AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' AND C.cSituac IN ({$lcSituac})
                     AND C.dFecAlt >= '{$this->paData['DFECHA']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}'
                     GROUP BY C.cActFij, F.cTipAfj
                     ORDER BY C.cTipAfj, C.ncorrel, C.dFecAlt";
      } else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] === '*'){
         $lcSql = "SELECT C.cActFij, F.cDescri, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.nMoncal, C.nDeprec, sum(B.nDeprec),
                     sum(B.nDeprec) - C.nDeprec AS DepAcu , F.nfacdep, C.cSituac
                     FROM E04MDEP A
                     INNER JOIN E04DDEP B ON B.cIdDepr =  A.cIdDepr
                     INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij
                     INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     INNER JOIN E04TTIP F ON F.cTipAfj = C.cTipAfj 
                     where  A.cperiod >= '$lcDatIni' and A.cperiod <= '$lcDatFin' and E.ccencos = '{$this->paData['CCENCOS']}' 
                     AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' AND C.cSituac IN ({$lcSituac})
                     AND C.dFecAlt >= '{$this->paData['DFECHA']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}'
                     GROUP BY C.cActFij, F.cTipAfj
                     ORDER BY C.cTipAfj, C.ncorrel, C.dFecAlt";
      } else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*'){
         $lcSql = "SELECT C.cActFij, F.cDescri, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.nMoncal, C.nDeprec, sum(B.nDeprec),
                     sum(B.nDeprec) - C.nDeprec AS DepAcu , F.nfacdep, C.cSituac
                     FROM E04MDEP A
                     INNER JOIN E04DDEP B ON B.cIdDepr =  A.cIdDepr
                     INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij
                     INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     INNER JOIN E04TTIP F ON F.cTipAfj = C.cTipAfj 
                     where  A.cperiod >= '$lcDatIni' and A.cperiod <= '$lcDatFin' 
                     AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' AND C.cSituac IN ({$lcSituac})
                     AND C.dFecAlt >= '{$this->paData['DFECHA']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}'
                     GROUP BY C.cActFij, F.cTipAfj
                     ORDER BY C.cTipAfj, C.ncorrel, C.dFecAlt";
      }
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         if($laFila[11] === 'B'){
            $lnRetiro = $laFila[6] - $laFila[8]; 
            $laFila[8] = $laFila[8] + $lnRetiro;
         }else{
            $lnRetiro = 0.00;
         }
         $lcCodigo = substr($laFila[2], 0, 2).'-'.substr($laFila[2], 2, 5).'-'.right('00000'.strval($laFila[3]), 6);
         $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCLA' => $laFila[1], 'CTIPAFJ' => $laFila[2], 'CDESCRI' => $laFila[4],
                             'DFECALT' => $laFila[5], 'NMONCAL' => $laFila[6], 'NDEPINI' => $laFila[7], 'NDEPTOT' => $laFila[8], 
                             'NDEPACU' => $laFila[9],  'CCODIGO' => $lcCodigo, 'NFACDEP' => $laFila[10], 'CSITUAC' => $laFila[11], 
                             'NRETIRO' => $lnRetiro]; 
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRO ACTIVOS';
         return false;
      }   
      return true;
   }

   // -----------------------------------------------------
   // Cargar Reporte análisis
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omRepAnalisisPDF() {
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData = $this->paData;
      $llOk = $lo->mxRepAnalisisPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }
   
   // -----------------------------------------------------
   // Cargar Reporte análisis EXCEL
   // Creación GCH 2023-01-03
   // -----------------------------------------------------
   public function omRepAnalisisActFijExcel() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData = $this->paData;
      $llOk = $lo->mxRepAnalisisExcel();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }
   
   // -----------------------------------------------------
   // PANTALLA 2080 -  REPORTE POR VALORES
   // -----------------------------------------------------
   // Cargar AF por caracteristicas
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omBuscarAFValores() {
      // $llOk = $this->mxValParam();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarAFValores($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   
   protected function mxBuscarAFValores($p_oSql) {
      $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->laData = ['CCENCOS'=> $laFila[0], 'CDESCOS' => $laFila[1]]; 
      }
      $lcDate =  date('Y', time());
      $lcSql = "SELECT max(cperiod) FROM e04mdep ";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $laTmp[0] = substr($laTmp[0], 4, 2);
      if ($laTmp[0] ===  '00') {
         $lcDate = $lcDate - 1;
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      } else {
         $lcDate0 = $lcDate.'00';
         $lcDate1 = $lcDate.'12';
      }
      if($this->paData['CSITUAC'] === '*'){
         $lcSituac = "'O','B','I'";
      }else{
         $lcSituac = "'".$this->paData['CSITUAC']."'";
      }
      if($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*' && $this->paData['CCODEMP'] !== null){
         ///echo"44444444444";
         $lcSql = "SELECT C.cActFij, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.cSituac, C.nMoncal, C.nDeprec, sum(B.nDeprec), C.cCodEmp, 
                  F.cNombre, C.cCenRes, D.cDescri
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes 
                  INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos 
                  INNER JOIN V_S01TUSU_1 F ON F.cCodUsu = C.cCodEmp
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                        AND C.cSituac IN ({$lcSituac}) AND C.cCodEmp = '{$this->paData['CCODEMP']}'
                        AND C.dFecAlt >= '{$this->paData['DFECINI']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}' AND C.cCenRes = '{$this->paData['CCENRES']}'  
                  GROUP BY C.cActFij, c.cTipAfj, F.cNombre, D.cDescri
                  ORDER BY C.cCenRes, C.cCodEmp, C.cTipAfj, C.nCorrel";
      }else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*' && $this->paData['CCODEMP'] === null){
         //echo"3333333333333";
         $lcSql = "SELECT C.cActFij, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.cSituac, C.nMoncal, C.nDeprec, sum(B.nDeprec), C.cCodEmp, 
                  F.cNombre, C.cCenRes, D.cDescri
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes 
                  INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos 
                  INNER JOIN V_S01TUSU_1 F ON F.cCodUsu = C.cCodEmp
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                        AND C.cSituac IN ({$lcSituac})
                        AND C.dFecAlt >= '{$this->paData['DFECINI']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}' AND C.cCenRes = '{$this->paData['CCENRES']}'  
                  GROUP BY C.cActFij, c.cTipAfj, F.cNombre, D.cDescri
                  ORDER BY C.cCenRes, C.cCodEmp, C.cTipAfj, C.nCorrel";
      } else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] === null){
         //echo"22222222222";
         $lcSql = "SELECT C.cActFij, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.cSituac, C.nMoncal, C.nDeprec, sum(B.nDeprec), C.cCodEmp, F.cNombre, C.cCenRes, D.cDescri
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes 
                  INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos 
                  INNER JOIN V_S01TUSU_1 F ON F.cCodUsu = C.cCodEmp
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                        AND C.cSituac IN ({$lcSituac})
                        AND C.dFecAlt >= '{$this->paData['DFECINI']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}' AND D.cCenCos = '{$this->paData['CCENCOS']}'  
                  GROUP BY C.cActFij, c.cTipAfj, F.cNombre, D.cDescri
                  ORDER BY C.cCenRes, C.cCodEmp, C.cTipAfj, C.nCorrel";
      }else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] !== null){
         //echo"111111111";
         $lcSql = "SELECT C.cActFij, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.cSituac, C.nMoncal, C.nDeprec, sum(B.nDeprec), C.cCodEmp, F.cNombre, C.cCenRes, D.cDescri
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes 
                  INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos 
                  INNER JOIN V_S01TUSU_1 F ON F.cCodUsu = C.cCodEmp
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                        AND C.cSituac IN ({$lcSituac}) AND C.cCodEmp = '{$this->paData['CCODEMP']}'
                        AND C.dFecAlt >= '{$this->paData['DFECINI']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}' 
                  GROUP BY C.cActFij, c.cTipAfj, F.cNombre, D.cDescri
                  ORDER BY C.cCenRes, C.cCodEmp, C.cTipAfj, C.nCorrel";
      }else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] === null){
         //echo"66666";
         $lcSql = "SELECT C.cActFij, C.cTipAfj, C.ncorrel, C.cDescri, C.dFecAlt, C.cSituac, C.nMoncal, C.nDeprec, sum(B.nDeprec), C.cCodEmp, F.cNombre, C.cCenRes, D.cDescri
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij 
                  INNER JOIN S01TRES D ON D.cCenRes = c.cCenRes 
                  INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos 
                  INNER JOIN V_S01TUSU_1 F ON F.cCodUsu = C.cCodEmp
                  where A.cperiod >= '$lcDate0' and A.cperiod <= '$lcDate1' AND C.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND C.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                        AND C.cSituac IN ({$lcSituac}) 
                        AND C.dFecAlt >= '{$this->paData['DFECINI']}' AND C.dFecAlt <= '{$this->paData['DFECFIN']}' 
                  GROUP BY C.cActFij, c.cTipAfj, F.cNombre, D.cDescri
                  ORDER BY C.cCenRes, C.cCodEmp, C.cTipAfj, C.nCorrel";
      }
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){       
         $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
         $lcNombre = str_replace('/',' ',$laFila[10]);
         $lnDepPer = $laFila[8] - $laFila[7];
         $lnValNet = $laFila[6] - $laFila[8];
         if($laFila[5] === 'B'){
            if($laFila[6] === $laFila[7]){
               $lnRetiro = 0.00;
            }
            $lnRetiro = $laFila[6] - $laFila[8]; 
            $laFila[8] = $laFila[8] + $lnRetiro;
            $lnValNet = $laFila[6] - $laFila[8];
         }else{
            $lnRetiro = 0.00;
         }
         $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCRI' => $laFila[3], 'DFECALT' => $laFila[4], 'CSITUAC' => $laFila[5],
                           'NMONCAL' => $laFila[6], 'NDEPREC' => $laFila[7], 'NSUMDEP' => $laFila[8], 'CCODEMP' => $laFila[9], 'CNOMEMP' => $lcNombre,
                           'CCENRES' => $laFila[11], 'CDESRES' => $laFila[12], 'CCODIGO' => $lcCodigo, 'NDEPPER' => $lnDepPer, 'NVALNET' => $lnValNet, 'NRETIRO' => $lnRetiro]; 
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRO ACTIVOS';
         return false;
      }   
      return true;
   }

   // -----------------------------------------------------
   // Cargar Reporte por caracteristicas PDF
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omRepValoresPDF() {
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData = $this->paData;
      $llOk = $lo->omRepValoresPDFAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // -----------------------------------------------------
   // Cargar Reporte por caracteristicas EXCEL
   // Creación GCH 2022-05-25
   // -----------------------------------------------------
   public function omRepValoresEXCEL() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $llOk = $lo->omRepValoresEXCELAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }

      // -----------------------------------------------------
   //PANTALLA 2110 -  REPORTE POR INVENTARIO
   // -----------------------------------------------------
   // Init Mantenimiento Reporte por inventario
   // Creación GCH 2023-03-30
   // -----------------------------------------------------
   public function omInitMantInventario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      //$llOk = $this->mxValParamUsuario($loSql, '000');
      //if (!$llOk) {
      //   $loSql->omDisconnect();
      //   return false;
      //}
      $llOk = $this->mxInitMantInventario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitMantInventario($p_oSql) {
      // Cargar Clase Activo
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE  cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY ccencos";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      //Cargar Situación
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($this->paSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      //Cargar Tipos de activo fijo
      $lcSql = "SELECT CTIPAFJ, cDescri FROM E04TTIP WHERE  cEstado = 'A' ORDER BY CTIPAFJ";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paTipAfj[] = ['CTIPAFJ' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($this->paTipAfj) == 0) {
         $this->pcError = 'NO HAY TIPOS DE ACTIVO FIJO DEFINIDAS ';
         return false;
      }
      return true;
   }
   // -----------------------------------------------------
   // Cargar AF por inventario
   // Creación GCH 2023-03-30
   // -----------------------------------------------------
   public function omBuscarActInventario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(13);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamActInventario($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxBuscarActInventario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamActInventario() {
      // print_r($this->paData);
      if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = 'CENTRO DE COSTO  NO ENCONTRADO';
         return false;
      } elseif (!isset($this->paData['CINVENT'])) {
         $this->pcError = 'AÑO DE INVENTARIO NO ENCONTRADA';
         return false;
      }
      return true;
   }


   protected function mxBuscarActInventario($p_oSql) {
      //print_r($this->paData);
      //echo "<br>";
      $lcSql = "SELECT A.cCenRes, A.cDescri, B.cCenCos, B.cDescri FROM  S01TRES A
               INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
               WHERE A.cCenRes = '{$this->paData['CCENRES']}' or B.cCenCos = '{$this->paData['CCENCOS']}' ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->laData = ['CCENRES' => $laFila[0], 'CDESRES' =>$laFila[1], 'CCENCOS'=> $laFila[2], 'CDESCOS' => $laFila[3]]; 
      }
      
      if($this->paData['CSITUAC'] === '*'){
         $lcSituac = "'O','B','I'";
      }else{
         $lcSituac = "'".$this->paData['CSITUAC']."'";
      }
      if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*' && $this->paData['CCODEMP'] !== null) {
         //print_r("111");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn
                   FROM E04MAFJ A
                   INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                   INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                   WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' AND A.cSituac IN ({$lcSituac})  AND A.cCodEmp = '{$this->paData['CCODEMP']}'
                   AND A.cCenRes = '{$this->paData['CCENRES']}' AND A.cPerInv = '{$this->paData['CINVENT']}'
                   ORDER BY A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } elseif($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] !== '*' && $this->paData['CCODEMP'] === null) {
         //print_r("222");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn
                   FROM E04MAFJ A
                   INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                   INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                   WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' AND A.cSituac IN ({$lcSituac}) AND A.cCenRes = '{$this->paData['CCENRES']}' 
                   AND A.cPerInv = '{$this->paData['CINVENT']}'
                   ORDER BY A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] === null){
         //OJO REVISAR  SALEN ACTIVOS SIN CENTRO DE REPONSABILIDAD Y SIN PERSONA RESPONSABLE
         //print_r("333");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) AND A.cPerInv = '{$this->paData['CINVENT']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] === null){
         //print_r("6666");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) AND  E.cCenCos = '{$this->paData['CCENCOS']}' 
                     AND A.cPerInv = '{$this->paData['CINVENT']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] !== '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] !== null){
         //print_r("444");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) AND  E.cCenCos = '{$this->paData['CCENCOS']}' 
                     AND A.cCodEmp = '{$this->paData['CCODEMP']}' AND A.cPerInv = '{$this->paData['CINVENT']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } else if ($this->paData['CCENCOS'] === '000' && $this->paData['CCENRES'] === '*' && $this->paData['CCODEMP'] !== null){
         //print_r("555");
         $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cTipAfj >= '{$this->paData['CTIPAFJ']}' AND A.cTipAfj <= '{$this->paData['CTIPAFJF']}' 
                     AND A.cSituac IN ({$lcSituac}) 
                     AND A.cCodEmp = '{$this->paData['CCODEMP']}' AND A.cPerInv = '{$this->paData['CINVENT']}'
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      } 
      //print_r($lcSql);
      //die;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laFila[6], 0, 2).'-'.substr($laFila[6], 2, 5).'-'.right('00000'.strval($laFila[7]), 6);
         $lcNombre = str_replace('/',' ',$laFila[9]);
         $this->paDatos[] = ['CACTFIJ'=> $laFila[0], 'CDESCRI'=> $laFila[1], 'DFECALT'=> $laFila[2], 'CCENRES'=> $laFila[3],
                             'CDESRES'=> $laFila[4], 'CDATOS'=> json_decode($laFila[5], true), 'CCODIGO'=> $lcCodigo, 
                             'CCODEMP'=> $laFila[8], 'CNOMEMP'=> $lcNombre, 'CSITUAC'=> $laFila[10], 'NMONTO'=> $laFila[11]];
         $lcSql = "SELECT DISTINCT(cActFij) FROM E04DCOM WHERE cActFij = '$laFila[0]'";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $lcSql = "SELECT A.cActFij, D.cDescri, D.dFecAlt, D.mDatos, D.cSituac, D.nMonto, D.cEtique, D.nSecuen 
                      FROM F_E04MAFJ_2('$laFila[0]') A
                      INNER JOIN E04DCOM D ON D.cActFij = A.cActFij ORDER BY D.nSecuen";
            $R3 = $p_oSql->omExec($lcSql);
            while ($laTmp1 = $p_oSql->fetch($R3)) {
               $lcCodig1 = $lcCodigo.'-'.strval($laTmp1[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp1[0], 'CDESCRI'=> $laTmp1[1], 'DFECALT'=> $laTmp1[2], 'CCENRES'=> $laFila[3],
                                   'CDESRES'=> $laFila[4], 'CDATOS'=> json_decode($laTmp1[3], true), 'CCODIGO'=> $lcCodig1, 
                                   'CCODEMP'=> $laFila[8], 'CNOMEMP'=> $lcNombre, 'CSITUAC'=> $laTmp1[4], 'NMONTO'=> $laTmp1[5],
                                   'CETIQUE'=> $laTmp1[6]]; 
            }
         }
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON ACTIVOS FIJOS';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------
   // Cargar Reporte por inventario PDF
   //Creación GCH 2023-03-30
   // -----------------------------------------------------
   public function omRepInventarioPDF() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData  = $this->paData;
      $lo->laData = $this->laData;
      $llOk = $lo->omRepCaracteristicasPDFAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paDatas['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // -----------------------------------------------------
   // Cargar Reporte por inventario EXCEL
   // Creación GCH 2023-03-30
   // -----------------------------------------------------
   public function omRepInventarioEXCEL() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      //print_r($lo->paDatos);
      $llOk = $lo->omRepCaracteristicasEXCELAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }
   
   // -----------------------------------------------------
   //PANTALLA 2090 -  REPORTE POR CLASES
   // -----------------------------------------------------
   // Init Mantenimiento Reporte por Clase
   // Creación GCH 2022-06-21
   // -----------------------------------------------------
   public function omInitManAFClase() {
      // $llOk = $this->mxValParam();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxMostrarClasesTipos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxMostrarClasesTipos($p_oSql) {
      // Cargar Clase Activo
      $lcSql = "SELECT A.cTipAfj, A.cDescri, A.cEstado, A.nFacDep, A.cCntAct, A.cCntDep, A.cCntCtr,  A.cCntBaj, SUBSTRING(B.cCodigo, 1, 2), B.cDescri
                  FROM E04TTIP A
                  INNER JOIN V_S01TTAB B ON SUBSTRING(B.cCodigo, 1, 2) = A.cClase
                  WHERE cEstado = 'A' AND B.cCodTab = '336' AND SUBSTRING(B.cCodigo, 1, 2) != '00'
                  ORDER BY A.cTipAfj";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ['CTIPAFJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2], 'NFACDEP' => $laFila[3],
                             'CCNTACT' => $laFila[4], 'CCNTDEP' => $laFila[5], 'CCNTCTR' => $laFila[6], 'CCNTBAJ' => $laFila[7],
                             'CCODIDO' => $laFila[8], 'CDESCLA' => $laFila[9]]; 
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      return true;
   }

   // -----------------------------------------------------
   // Cargar Reporte por clases PDF
   // Creación GCH 2022-06-22
   // -----------------------------------------------------
   public function omRepClasesTiposPDF() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $llOk = $lo->omRepClasesyTiposPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // -----------------------------------------------------
   // Cargar Reporte por clases EXCEL
   // Creación GCH 2022-06-22
   // -----------------------------------------------------
   public function omRepClasesTiposExcel() {
      // print_r("CLASE");
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paData;
      $llOk = $lo->omReporteClasesTiposEXCEL();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData = $lo->pcFile;
      return $llOk;
   }
   
   //---------------------------------------------------
   //PANTALLA 1010 ASIENTOS CONTABLES DE DEPREACIACION 
   //--------------------------------------------------
   // Reporte de asiento contable de depreciacion  
   // 2022-07-19 FPM  
   //---------------------------------------------------
   public function omInitAsientoDepreciacion() {  
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitAsientoDepreciacion($loSql);
      $loSql->omDisconnect(); 
      return $llOk;  
   }

   protected function mxInitAsientoDepreciacion($p_oSql) {
      // Cargar Centro de Costo
      $lcSql = "SELECT  cNroAsi, dFecCnt, cGlosa, cPeriod FROM D01MASI WHERE cglosa LIKE 'DEPRECIACION DEL PERIODO%' ORDER BY TMODIFI DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CNROASI'=> $laTmp[0], 'DFECCNT'=> $laTmp[1], 'CGLOSA'=> $laTmp[2], 'CPERIOD'=> $laTmp[3]];
      }
      return true;
   }

   // -----------------------------------------------------
   // Mant Reporte contabilización Detalle
   // Creación GCH 2022-08-02
   // -----------------------------------------------------
   public function omRepContaDepreciacionDetalle() { 
      $llOk = $this->mxValParamRepContaDepreciacionDetalle();  
      if (!$llOk) {  
         return false;  
      }  
      $loSql = new CSql();
      $llOk = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk = $this->mxRepContaDepreciacionDetalle($loSql);
      $loSql->omDisconnect(); 
      $laData = $this->laData;
      $laDatos = $this->paDatos;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      $llOk = $lo->mxPrintRepContaDepreciacionDetalle();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;  
   }

   protected function mxValParamRepContaDepreciacionDetalle() {  
      if (!isset($this->paData['CPERIOD']) or !preg_match('/^20[0-9]{2}[0-9]{2}$/', $this->paData['CPERIOD'])) {  
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";  
         return false;  
      } elseif (!isset($this->paData['CMODULO']) or !preg_match('/^[A-Z0-9]{3}$/', $this->paData['CMODULO'])) {  
         $this->pcError = "MÓDULO NO DEFINIDO O INVÁLIDO";  
         return false;  
      }  
      return true;  
   }  
   
   protected function mxRepContaDepreciacionDetalle($p_oSql) {
      $lcPeriod = str_replace('-', '', $this->paData['CPERIOD']);
      $lcSql = "SELECT A.cIdDepr, A.cPeriod, A.cEstado, B.cGlosa FROM E04MDEP A 
                  INNER JOIN D01MASI B ON B.CPERIOD = A.CPERIOD
                  WHERE B.cPeriod = '$lcPeriod' AND B.cOrigen = '{$this->paData['CMODULO']}'";
      $R1 = $p_oSql->omExec($lcSql);  
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {  
         $this->pcError = "DEPRECIACIÓN CONTABLE DE PERIODO [{$this->paData['CPERIOD']}] NO EXISTE";
         return false;  
      } elseif ($laTmp[2] != 'A') {
         $this->pcError = "DEPRECIACIÓN CONTABLE DE PERIODO [{$this->paData['CPERIOD']}] NO ESTÁ ACTIVO";
         return false;
      }
      $this->laData = ['CIDDEPR'=> $laTmp[0], 'CPERIOD'=> $laTmp[1], 'CESTADO' => $laTmp[2], 'CGLOSA' => $laTmp[3]]; 
      $lcSql = "SELECT A.cActFiJ, A.cTipAfj, A.nCorrel, A.cDescri, A.dFecAlt, A.nMonCal, A.nDeprec, A.nDepAcu, C.nDeprec FROM E04MAFJ A
                  INNER JOIN E04TTIP B ON B.CTIPAFJ = A.CTIPAFJ
                  INNER JOIN E04DDEP C ON C.cActFij = A.cActFij
                  WHERE B.NFACDEP > '0.00' AND C.cIdDepr = '{$this->laData['CIDDEPR']}'
                  ORDER BY A.cTipAfj, A.nCorrel ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
         $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2], 'CDESCRI'=> $laTmp[3], 'DFECALT'=> $laTmp[4],
                             'NMONCAL'=> $laTmp[5], 'NDEPANIO'=> $laTmp[6], 'NDEPACU'=> $laTmp[7]+$laTmp[8], 'NDEPREC'=> $laTmp[8],'CCODIGO'=> $lcCodigo];

      }
      // print_r($this->paDatos);
      if (count($this->paDatos) == 0) {
         $this->pcError = "DEPRECIACIÓN DEL PERIODO NO TIENE DETALLE";
         return false;
      }
      return true;  
   }
   
   // -----------------------------------------------------
   // Mant Reporte contabilización Depreciacion
   // Creación GCH 2022-08-02
   // -----------------------------------------------------
   public function omRepContaDepreciacion() { 
      $llOk = $this->mxValParamAsientoDepreciacion();  
      if (!$llOk) {  
         return false;  
      }  
      $loSql = new CSql();
      $llOk = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk = $this->mxAsientoDepreciacion($loSql);
      $loSql->omDisconnect();  
      $laDatos = $this->paData;
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $laDatos;
      $llOk = $lo->mxPrintRepContaDepre();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      print_r($this->paData);
      return $llOk;  
   }   
   protected function mxValParamAsientoDepreciacion() {  
      if (!isset($this->paData['CPERIOD']) or !preg_match('/^20[0-9]{2}[0-9]{2}$/', $this->paData['CPERIOD'])) {  
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";  
         return false;  
      } elseif (!isset($this->paData['CMODULO']) or !preg_match('/^[A-Z0-9]{3}$/', $this->paData['CMODULO'])) {  
         $this->pcError = "MÓDULO NO DEFINIDO O INVÁLIDO";  
         return false;  
      }  
      return true;  
   }  
   
   protected function mxAsientoDepreciacion($p_oSql) {
      $lcPeriod = str_replace('-', '', $this->paData['CPERIOD']);
      $lcSql = "SELECT cNroAsi, cEstado, dFecCnt, cGlosa FROM D01MASI WHERE cPeriod = '$lcPeriod' AND cOrigen = '{$this->paData['CMODULO']}'";  
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {  
         $this->pcError = "ASIENTO DE DEPRECIACIÓN CONTABLE DE PERIODO [{$this->paData['CPERIOD']}] NO EXISTE";
         return false;  
      } elseif ($laTmp[1] != 'A') {
         $this->pcError = "ASIENTO DE DEPRECIACIÓN CONTABLE DE PERIODO [{$this->paData['CPERIOD']}] NO ESTÁ ACTIVO";
         return false;
      }
      $laDatos = [];
      $this->laData = ['CNROASI'=> $laTmp[0], 'DFECCNT'=> $laTmp[2], 'CGLOSA'=> $laTmp[3]]; 
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39111%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68111' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39520%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68410' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39521%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68411' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39525%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68413' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt IN('3952601','3952602')  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68414' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '3952603'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68417' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39527%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68415' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '39528'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68416' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39531%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68422' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt LIKE '39611%'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68611' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '39612'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68612' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '39613'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68613' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
            $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '3981101'  ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $laTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      $lcSql = "SELECT A.cCtaCnt, B.cDescri, A.nDebMN, A.nHabMN FROM D01DASI A 
                INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                WHERE A.cNroAsi = '{$this->laData['CNROASI']}' AND A.cctacnt = '68511' ORDER BY A.nSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);  
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NDEBMN'=> $lxaTmp[2], 'NHABMN'=> $laTmp[3]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "ASIENTO DE DEPRECIACIÓN NO TIENE DETALLE";
         return false;
      }
      $this->laData['DATOS'] = $laDatos;
      $this->paData = $this->laData;
      // print_r($this->paData);
      return true;  
   }

   // -----------------------------------------------------
   //PANTALLA 1050 -  IMPRESIÓN DE ETIQUETAS 
   // -----------------------------------------------------
   // Init Mantenimiento Impresión de etiquetas
   // Creación GCH 2022-08-25
   // -----------------------------------------------------
   public function omInitManImpresionEtiquetas() {
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
      $llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxInitManImpresionEtiquetas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitManImpresionEtiquetas($p_oSql) {
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      //Cargar Situación
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($this->paSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      return true;
   }

   //-----------------------------------------------  
   // Buscar Activo Fijo para Etiquetas
   // 2022-08-25 GCH  
   //-----------------------------------------------  
   public function omBuscarActFijEtiquetas() { 
      $loSql = new CSql();
      $llOk = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk = $this->mxBuscarActFijEtiquetas($loSql);
      $loSql->omDisconnect();  
      return $llOk;  
   }   
   protected function mxBuscarActFijEtiquetas($p_oSql) {
      $lcSql = "SELECT cCenRes, cDescri FROM S01TRES WHERE cCenRes = '{$this->paData['CCENRES']}'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->laData = ['CCENRES' => $laFila[0], 'CDESRES' =>$laFila[1]]; 
      }
      if (count($this->laData) == 0) {
         $this->pcError = 'NO SE ENCONTRO CENTRO DE RESPONSABILIDAD';
         return false;
      }
      if($this->paData['CCODEMP'] == null ){
         $lcSql = "SELECT A.cActFij, A.cDescri, A.cCenRes, B.cDescri, A.cTipAfj, A.nCorrel, A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                           AND A.cCenRes = '{$this->paData['CCENRES']}' AND cSituac IN ('O','B','I') AND A.cIndAct = 'S'
                           ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $lcCodigo = substr($laFila[4], 0, 2).substr($laFila[4], 2, 5).right('00000'.strval($laFila[5]), 6);
            $lcNombre = str_replace('/',' ',$laFila[7]);
            $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCENRES' => $laFila[2], 'CDESRES' =>$laFila[3], 
                                'CCODIGO' => $lcCodigo, 'CCODEMP' =>$laFila[6], 'CNOMEMP' =>$lcNombre]; 
         }
         $lcSql = "SELECT A.cActFij, D.cDescri, A.cCenRes, B.cDescri, A.cTipAfj, A.nCorrel, D.nSecuen,  A.cCodEmp, C.cNombre
                  FROM E04MAFJ A
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                  WHERE A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                        AND A.cCenRes = '{$this->paData['CCENRES']}' AND A.cSituac IN ('O','B','I') AND D.cEtique = 'S'
                        ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $lcCodigo = substr($laFila[4], 0, 2).substr($laFila[4], 2, 5).right('00000'.strval($laFila[5]), 6).'-'.substr($laFila[6], 0, 2);
            $lcNombre = str_replace('/',' ',$laFila[8]);
            $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCENRES' => $laFila[2], 'CDESRES' =>$laFila[3], 
                                'CCODIGO' => $lcCodigo, 'CCODEMP' =>$laFila[7], 'CNOMEMP' =>$lcNombre]; 
         }
         if (count($this->paDatos) == 0) {
            $this->pcError = 'NO SE ENCONTRO ACTIVOS';
            return false;
         }
         return true;
      }else{
         $lcSql = "SELECT A.cActFij, A.cDescri, A.cCenRes, B.cDescri, A.cTipAfj, A.nCorrel, A.cCodEmp,C.cNombre
                  FROM E04MAFJ A
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                        AND A.cCenRes = '{$this->paData['CCENRES']}' AND A.cCodEmp = '{$this->paData['CCODEMP']}' 
                        AND A.cSituac = 'O' AND A.cIndAct = 'S'
                        ORDER BY A.cTipAfj, A.nCorrel";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $lcCodigo = substr($laFila[4], 0, 2).substr($laFila[4], 2, 5).right('00000'.strval($laFila[5]), 6);
            $lcNombre = str_replace('/',' ',$laFila[7]);
            $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCENRES' => $laFila[2], 'CDESRES' =>$laFila[3], 
                              'CCODIGO' => $lcCodigo, 'CCODEMP' =>$laFila[6], 'CNOMEMP' =>$lcNombre]; 
         }
         $lcSql = "SELECT A.cActFij, D.cDescri, A.cCenRes, B.cDescri, A.cTipAfj, A.nCorrel, D.nSecuen,  A.cCodEmp, C.cNombre
                  FROM E04MAFJ A
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  INNER JOIN E04DCOM D ON D.cActFij = A.cActFij 
                  WHERE A.dFecAlt >= '{$this->paData['DFECINI']}' AND A.dFecAlt <= '{$this->paData['DFECFIN']}'
                        AND A.cCenRes = '{$this->paData['CCENRES']}' AND A.cCodEmp = '{$this->paData['CCODEMP']}' AND A.cSituac = 'O'
                        AND D.cEtique = 'S' ORDER BY A.cTipAfj, A.nCorrel";
         // print_r($lcSql);
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $lcCodigo = substr($laFila[4], 0, 2).substr($laFila[4], 2, 5).right('00000'.strval($laFila[5]), 6).'-'.substr($laFila[6], 0, 2);
            $lcNombre = str_replace('/',' ',$laFila[8]);
            $this->paDatos[] = ['CACTFIJ' => $laFila[0], 'CDESCRI' => $laFila[1], 'CCENRES' => $laFila[2], 'CDESRES' =>$laFila[3], 
                              'CCODIGO' => $lcCodigo, 'CCODEMP' =>$laFila[7], 'CNOMEMP' =>$lcNombre]; 
         }
         if (count($this->paDatos) == 0) {
            $this->pcError = 'NO SE ENCONTRO ACTIVOS';
            return false;
         }
         return true;
      }
      return true;
   }

      //-----------------------------------------------  
   // Buscar Activo Fijo para Etiquetas por Codigo
   // 2022-08-25 GCH  
   //-----------------------------------------------  
   public function omBuscarActFijEtiquetasCodigo() { 
      $loSql = new CSql();
      $llOk = $loSql->omConnect();  
      if (!$llOk) {  
         $this->pcError = $loSql->pcError;  
         return false;  
      }  
      $llOk = $this->mxBuscarActFijEtiquetasCodigo($loSql);
      $loSql->omDisconnect();  
      return $llOk;  
   }   
   
    protected function mxBuscarActFijEtiquetasCodigo($p_oSql) {
      // print_r($this->paData);
      $this->paData['CCODI01'] = str_replace('-','',$this->paData['CCODI01']);
      $this->paData['CCODI02'] = str_replace('-','',$this->paData['CCODI02']);
      $this->paData['CCODI03'] = str_replace('-','',$this->paData['CCODI03']);
      $this->paData['CCODI04'] = str_replace('-','',$this->paData['CCODI04']);
      $this->paData['CCODI05'] = str_replace('-','',$this->paData['CCODI05']);
      $this->paData['CCODI06'] = str_replace('-','',$this->paData['CCODI06']);
      $lcTipAfj1 = substr($this->paData['CCODI01'], 0, 5);
      $lnCorrel1= substr($this->paData['CCODI01'], 5);
      $lcTipAfj2 = substr($this->paData['CCODI02'], 0, 5);
      $lnCorrel2 = substr($this->paData['CCODI02'], 5);
      $lcTipAfj3 = substr($this->paData['CCODI03'], 0, 5);
      $lnCorrel3 = substr($this->paData['CCODI03'], 5);
      $lcTipAfj4 = substr($this->paData['CCODI04'], 0, 5);
      $lnCorrel4 = substr($this->paData['CCODI04'], 5);
      $lcTipAfj5 = substr($this->paData['CCODI05'], 0, 5);
      $lnCorrel5 = substr($this->paData['CCODI05'], 5);
      $lcTipAfj6 = substr($this->paData['CCODI06'], 0, 5);
      $lnCorrel6 = substr($this->paData['CCODI06'], 5);
      if ($this->paData['CCODI01'] != null) {
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj1' AND nCorrel = $lnCorrel1";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            ;
         } else {
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cActFij = '$laTmp[0]' ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";

            // $lcSql = "SELECT A.cActFij, A.CTIPAFJ, A.nCorrel, A.cDescri, A.cCenRes, A.cDesRes, A.cCodEmp, A.cNomEmp
            //          FROM F_E04MAFJ_2('$laTmp[0]') A";
            // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[7]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo];
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, D.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre, D.nSecuen
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                     WHERE A.cActFij = '$laTmp[0]' AND D.cEtique = 'S' ORDER BY D.nSecuen";
            $R1 = $p_oSql->omExec($lcSql);
            while ($laTmp = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6).'-'.substr($laTmp[8], 0, 2);
               $lcNomEmp = str_replace('/',' ',$laTmp[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                 'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo]; 
            }
            
         }     
      } if ($this->paData['CCODI02'] != null) {
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj2' AND nCorrel = $lnCorrel2";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            ;
         } else {
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cActFij = '$laTmp[0]' ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
               // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[7]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo];
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, D.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre, D.nSecuen
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                     WHERE A.cActFij = '$laTmp[0]' AND D.cEtique = 'S' ORDER BY D.nSecuen";
            $R1 = $p_oSql->omExec($lcSql);
            while ($laTmp = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6).'-'.substr($laTmp[8], 0, 2);
               $lcNomEmp = str_replace('/',' ',$laTmp[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                 'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo]; 
            }
         }     
      }if ($this->paData['CCODI03'] != null) {
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj3' AND nCorrel = $lnCorrel3";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            ;
         } else {
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cActFij = '$laTmp[0]' ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
               // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[7]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo];
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, D.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre, D.nSecuen
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                     WHERE A.cActFij = '$laTmp[0]' AND D.cEtique = 'S' ORDER BY D.nSecuen";
            $R1 = $p_oSql->omExec($lcSql);
            while ($laTmp = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6).'-'.substr($laTmp[8], 0, 2);
               $lcNomEmp = str_replace('/',' ',$laTmp[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                 'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo]; 
            }
         }     
      }if ($this->paData['CCODI04'] != null) {
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj4' AND nCorrel = $lnCorrel4";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            ;
         } else {
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cActFij = '$laTmp[0]' ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
               // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[7]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo];
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, D.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre, D.nSecuen
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                     WHERE A.cActFij = '$laTmp[0]' AND D.cEtique = 'S' ORDER BY D.nSecuen";
            $R1 = $p_oSql->omExec($lcSql);
            while ($laTmp = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6).'-'.substr($laTmp[8], 0, 2);
               $lcNomEmp = str_replace('/',' ',$laTmp[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                 'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo]; 
            }
         }     
      }if ($this->paData['CCODI05'] != null) {
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj5' AND nCorrel = $lnCorrel5";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            ;
         } else {
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cActFij = '$laTmp[0]' ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
               // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[7]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo];
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, D.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre, D.nSecuen
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                     WHERE A.cActFij = '$laTmp[0]' AND D.cEtique = 'S' ORDER BY D.nSecuen";
            $R1 = $p_oSql->omExec($lcSql);
            while ($laTmp = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6).'-'.substr($laTmp[8], 0, 2);
               $lcNomEmp = str_replace('/',' ',$laTmp[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                 'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo]; 
            }
         }     
      }if ($this->paData['CCODI06'] != null) {
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE CTIPAFJ = '$lcTipAfj6' AND nCorrel = $lnCorrel6";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!$laTmp or count($laTmp) == 0) {
            ;
         } else {
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     WHERE A.cActFij = '$laTmp[0]' ORDER BY A.cCodEmp, A.cTipAfj, A.nCorrel";
               // print_r($lcSql);
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6);
            $lcNomEmp = str_replace('/',' ',$laTmp[7]);
            $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo];
            $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, D.cDescri, A.cCenRes, B.cDescri,  A.cCodEmp,C.cNombre, D.nSecuen
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN E04DCOM D ON D.cActFij = A.cActFij
                     WHERE A.cActFij = '$laTmp[0]' AND D.cEtique = 'S' ORDER BY D.nSecuen";
            $R1 = $p_oSql->omExec($lcSql);
            while ($laTmp = $p_oSql->fetch($R1)){
               $lcCodigo = substr($laTmp[1], 0, 2).substr($laTmp[1], 2, 5).right('00000'.strval($laTmp[2]), 6).'-'.substr($laTmp[8], 0, 2);
               $lcNomEmp = str_replace('/',' ',$laTmp[7]);
               $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[3], 'CCENRES'=> $laTmp[4], 'CDESRES'=> $laTmp[5],
                                 'CCODEMP'=> $laTmp[6], 'CNOMEMP'=> $lcNomEmp, 'CCODIGO'=> $lcCodigo]; 
            }
         }     
      }
      return true;
   }

   // -----------------------------------------------------
   // Imprimir codigo de activos
   // Creación GCH 2022-8-25
   // -----------------------------------------------------
   public function omPrintCodigos() {
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData = $this->paData;
      $llOk = $lo->omPrintCodigosAF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   // -----------------------------------------------------
   // Imprimir codigo de activos Otros
   // Creación GCH 2022-09-15
   // -----------------------------------------------------
   public function omPrintCodigosOtros() {
      $lo = new CRepControlPatrimonial();
      $lo->paDatos = $this->paDatos;
      $lo->paData = $this->paData;
      $llOk = $lo->omPrintCodigosOtros();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

  // ------------------------------------------------------------------------
   //  PANTALLA 1130 -  TRANFERIR ACTIVOS II
   // ------------------------------------------------------------------------
   // Cargar Empleado
   // 2023-08-11 GCH
   // ------------------------------------------------------------------------
   public function omCargarEmpleadoCenREs() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCargarEmpleadoCenREs($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarEmpleadoCenREs($p_oSql) {
      // print_r($this->paData['CCODEMP']);
      $lcSql = "SELECT DISTINCT(A.cCodEmp), C.cNombre, C.cNroDni
               FROM E04MAFJ A 
               INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
               INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
               WHERE A.cCenRes = '{$this->paData['CCENRES']}'  ORDER BY  A.cCodEmp ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcNomEmp = str_replace('/',' ',$laTmp[1]);
         $this->paDatos[] = ['CCODEMP'=> $laTmp[0], 'CNOMEMP'=> $lcNomEmp, 'CNRODNI'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
         $this->paDatos[] = ['CCODEMP'=> '0000', 'CNOMEMP'=> '* SIN ASIGNAR', 'CNRODNI'=> '00000000'];
        //return false;
      }
      return true;
   }


   // ------------------------------------------------------------------------
   // Buscar ACTIVOS DE un EMPLEADO 
   // 2022-09-19 GCH
   // ------------------------------------------------------------------------
   public function omBuscarActivoPorEmpleado() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarActivoPorEmpleado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarActivoPorEmpleado($p_oSql) {
      // print_r($this->paData['CCODEMP']);
      $lcSql = "SELECT DISTINCT(A.cCodEmp), C.cNombre, C.cNroDni, B.cCenRes, B.cDescri
                  FROM E04MAFJ A 
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE A.cCenRes = '{$this->paData['CCENRES']}' AND cCodUsu = '{$this->paData['CCODEMP']}' ORDER BY  A.cCodEmp ";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcNomEmp = str_replace('/',' ',$laTmp[1]);
         $this->paData = ['CCODEMP'=> $laTmp[0], 'CNOMEMP'=> $lcNomEmp, 'CNRODNI'=> $laTmp[2], 'CCENRES' => $laTmp[3], 'CDESCRI' => $laTmp[4]];
      }
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO EMPLEADO";
        return false;
      }
      //ACTIVOS POR EMPLEADO RESPONSABLE
      $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cEstado, A.cDescri, A.cCenRes, B.cDescri, A.cCodEmp, C.cNombre, A.dFecAlt, A.mDatos, A.cSituac
                  FROM E04MAFJ A 
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE A.cCodEmp = '{$this->paData['CCODEMP']}' AND A.cCenRes = '{$this->paData['CCENRES']}'   ORDER BY  A.cTipAfj, A.nCorrel";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
         $lcNomEmp = str_replace('/',' ',$laTmp[8]);
         $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2], 'CESTADO'=> $laTmp[3],
                             'CDESCRI'=> $laTmp[4], 'CCENRES'=> $laTmp[5], 'CDESRES'=> $laTmp[6], 'CCODEMP'=> $laTmp[7], 
                             'CNOMEMP'=> $lcNomEmp, 'DFECALT'=> $laTmp[9], 'CDATOS' => json_decode($laTmp[10], true), 
                             'CCODIGO'=>$lcCodigo, 'CSITUAC' =>$laTmp[11]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO SE ENCONTRON ACTIVOS FIJOS ASIGNADOS A ESE EMPLEADO";
        return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Cambiar empleado responsable a todos los activos
   // 2022-09-19 GCH
   // ------------------------------------------------------------------------
   public function omCambiarEmpleadoResponsable() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCambiarEmpleadoResponsable($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCambiarEmpleadoResponsable($p_oSql) {
      $lcSql = "SELECT cCodUsu, cNombre, cNroDni  FROM V_S01TUSU_1 WHERE cCodUsu = '{$this->paData['CCODEMP']}' AND cEstado = 'A'";
      // print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE ENCONTRO EMPLEADO";
         return false;
      }
      $i = 0;
      // print_r($this->paDatos);
      // die;
      while ($this->paDatos[$i] != null) {
         $lcSql = "UPDATE E04MAFJ SET cCodEmp = '{$this->paData['CCODEMP']}'
                   WHERE cActFij = '{$this->paDatos[$i]['CACTFIJ']}'";
            /* print_r($lcSql);
            echo "<br>"; */
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR NUEVO EMPLEADO RESPONSABLE";
            return false;
         }  
         $i++;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Carga centros de responsabilidad de centro de costo
   // 2022-10-04 gch Creacion
   // ------------------------------------------------------------------------
   public function omCargarCentroResponsabilidad_Destino() {
      $llOk = $this->mxValParamCargarCentroResponsabilidad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCargarCentroResponsabilidad_Destino($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamCargarCentroResponsabilidad_Destino() {
      if (!isset($this->paData['CCENCOS']) or !preg_match("/[0-9,A-Z]{3}/", $this->paData['CCENCOS'])) {
      //if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarCentroResponsabilidad_Destino($p_oSql) {
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or !$laTmp[0]) {
         $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO EXISTE";
         return false;
      } 
      $lcSql = "SELECT DISTINCT(cCenRes), cEstado, cDescri FROM S01TRES 
                 WHERE cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE cCenCos = '{$this->paData['CCENCOS']}' ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENRES'=> $laTmp[0], 'CESTADO'=> $laTmp[1], 'CDESRES'=> $laTmp[2]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "CENTRO DE COSTO [{$this->paData['CCENCOS']}] NO TIENE CENTROS DE RESPONSABILIDAD";
        return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // buscar ACTIVOS por centro de responsabilidad
   // 2022-09-19 GCH
   // ------------------------------------------------------------------------
   public function omBuscarActivoCenResp() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxBuscarActivoCenResp($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarActivoCenResp($p_oSql) {
      // print_r($this->paData['CCODEMP']);
      $lcSql = "SELECT cCenRes, cEstado, cDescri FROM S01TRES WHERE cCenRes = '{$this->paData['CCENRES']}' AND cEstado = 'A'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CCENRES'=> $laTmp[0], 'CESTADO'=>$laTmp[1], 'CDESCRI'=> $laTmp[2]];
      }
      if (count($this->paData) == 0) {
        $this->pcError = "Centro de Responsabilidad no encontrado";
        return false;
      }
      //ACTIVOS POR CENTRO DE RESPONSABILIDAD   
      $lcSql = "SELECT A.cActFij, A.cTipAfj, A.nCorrel, A.cEstado, A.cDescri, A.cCenRes, B.cDescri, A.cCodEmp, C.cNombre, A.dFecAlt, A.mDatos 
                  FROM E04MAFJ A 
                  INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                  INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                  WHERE A.cCenRes = '{$this->paData['CCENRES']}' AND A.cEstado = 'A' 
                  ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laTmp[1], 0, 2).'-'.substr($laTmp[1], 2, 5).'-'.right('00000'.strval($laTmp[2]), 6);
         $lcNomEmp = str_replace('/',' ',$laTmp[8]);
         $this->paDatos[] = ['CACTFIJ'=> $laTmp[0], 'CTIPAFJ'=> $laTmp[1], 'NCORREL'=> $laTmp[2], 'CESTADO'=> $laTmp[3],
                             'CDESCRI'=> $laTmp[4], 'CCENRES'=> $laTmp[5], 'CDESRES'=> $laTmp[6], 'CCODEMP'=> $laTmp[7], 
                             'CNOMEMP'=> $lcNomEmp, 'DFECALT'=> $laTmp[9], 'CDATOS' => json_decode($laTmp[10], true), 
                             'CCODIGO'=>$lcCodigo];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO SE ENCONTRON ACTIVOS FIJOS ASIGNADOS A ESE EMPLEADO";
        return false;
      }
      return true;
   }

      // ------------------------------------------------------------------------
   // Realizar transferencia de varios centros de resp a uno
   // 2022-11-10 Creacion
   // ------------------------------------------------------------------------
   public function omTransferencias() {      
      $llOk = $this->mxValParamGuardarDisfeCenResp();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTransferencias($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxTransferencias($p_oSql) {
      $lcSql = "SELECT MAX(cIdTrnf) FROM E04MTRF";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!(!$laTmp or count($laTmp) == 0 or $laTmp[0] == null)) {
         $this->paData['CIDTRNF'] = $laTmp[0];
      }
      $this->paData['CIDTRNF'] = fxCorrelativo($this->paData['CIDTRNF']);
      $this->paData['CDESCRI'] = strtoupper(trim($this->paData['CDESCRI']));
      $lcSql = "INSERT INTO E04MTRF (cIdTrnf, cEstado, dTrasla, cDescri, cCenRes, cCodEmp, tRegist, cResCen, cCodRec, tRecepc,  cUsuAdm, tAprAdm, cUsuCod) VALUES
              ('{$this->paData['CIDTRNF']}', 'A', '{$this->paData['DTRASLA']}', '{$this->paData['CDESCRI']}', '{$this->paData['CCENRES']}', '{$this->paData['CCODEMP']}', NOW(),
               '{$this->paData['CRESDES']}', '{$this->paData['CCODDES']}', NOW(), '{$this->paData['CUSUCOD']}', NOW(), '{$this->paData['CUSUCOD']}')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR CABECERA DE TRANSFERENCIA";
         return false;
      }      
      foreach ($this->paDatos as $laFila) {
         $lcSql = "INSERT INTO E04DTRF (cIdTrnf, cActFij, cUsuCod) 
                     VALUES ('{$this->paData['CIDTRNF']}', '{$laFila}', '{$this->paData['CUSUCOD']}');";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR DETALLE TRANSFERENCIA";
            return false;
         }
      }
      // CAMBIAR ACTIVO FIJO
      foreach ($this->paDatos as $laFila) {
         $lcSql = "UPDATE E04MAFJ SET cCenRes = '{$this->paData['CRESDES']}', cCodEmp = '{$this->paData['CCODDES']}' WHERE cActFij = '$laFila'";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
            return false;
         }
      }
      return true;
   }
   // -----------------------------------------------------------------------
   // PANTALA AFJ1150 - PANTALLA TIPOS DE ACTIVOS FIJOS NUEVO / EDITAR
   // ------------------------------------------------------------------------
   // Init mantenimiento 
   // 2022-10-22 GCH Creacion
   // ------------------------------------------------------------------------
   public function omInitDatosTipAct() {
      $llOk = $this->mxValUsuarioActFij();
      if (!$llOk) {
         return false;
      }
      //CONEXION UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitDatosTipAct($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      return $llOk;      
   }

   protected function mxValUsuarioActFij() {
      if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO O NO DEFINIDO';
         return false;
      }
      return true;
   }
   
   protected function mxInitDatosTipAct($p_oSql) {
      //ESTADO (E04TTIP)
      $lcSql = "SELECT TRIM(cCodigo) AS cCodigo, TRIM(cDescri) FROM v_S01TTAB WHERE ccodtab = '041';";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatEst[] = ['CCODEST' => $laFila[0], 'CDESESR'  => $laFila[1]]; 
         $i++;
      }
      if ($i == 0 ){
         $this->paDatEst[] = ['CCODEST' => '--', 'CDESESR'  => 'NO SE ENCONTRO LOS ESTADOS']; 
      }
      //Clase Activo fijo
      $lcSql = "SELECT TRIM(cCodigo) AS cCodigo, CONCAT(TRIM(cCodigo),' - ', TRIM(cDescri)) FROM v_S01TTAB WHERE ccodtab = '336';";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paDatCla[] = ['CCODCLA' => $laFila[0], 'CDESCLA'  => $laFila[1]]; 
         $i++;
      }
      if ($i == 0 ){
         $this->paDatCla[] = ['CCODCLA' => '--', 'CDESCLA'  => 'NO SE ENCONTRO LAS CLASES']; 
      }
      return true;
   }

   // -------------------------------------------
   // Guardar nuevo tipo de activo fijo
   // GCH 01-11-2022
   // -------------------------------------------
   public function omGuardarTipoActivo() {
      $llOk = $this->mxValParamGrabarTipoActivo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGuardarTipoActivo($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      $this->paData = $this->laData;
      return $llOk;
   }
   
   protected function mxValParamGrabarTipoActivo() {
      if (!isset($this->paData['CCODEST'])) {
         $this->pcError = "ESTADO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CDESCRI'])) {
         $this->pcError = "DESCRIPCIÓN NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCLASE'])) {
         $this->pcError = "CLASE NO DEFINIDA O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCNTACT'])) {
         $this->pcError = "CUENTA DEL ACTIVO NO DEFINIDA O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CFACDEP'])) {
         $this->pcError = "FACTOR DE DEPRECIACIÓN NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCNTDEP'])) {
         $this->pcError = "CUENTA DE DEPRECIACIÓN NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCNTBAJ'])) {
         $this->pcError = "CUENTA DE BAJA NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCNTCTR'])) {
         $this->pcError = "CONTRA CUENTA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxGuardarTipoActivo($p_oSql) {
      // print_r($this->paData);
      if ($this->paData['CTIPACT'] == '*') {
         $lcSql = "SELECT MAX(cTipAfj) FROM E04TTIP WHERE CCLASE = '{$this->paData['CCLASE']}';";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $lcCodTip = $laFila[0];
         if($this->paData['CCLASE'] == '09' OR $this->paData['CCLASE'] == '07'){
            $lcCodTip = $laFila[0];
            $lcCodTip = substr($lcCodTip, strlen($this->paData['CCLASE']), strlen($lcCodTip));
            $lcCodTip = $lcCodTip + 1;
            $lcCodTip = $this->paData['CCLASE'].right('000'.strval($lcCodTip), 3);
         }else{
            $lcCodTip = $laFila[0];
            $lcCodTip = substr($lcCodTip, strlen($this->paData['CCLASE']), strlen($lcCodTip));
            $lcCodTip = $lcCodTip + 10;
            $lcCodTip = $this->paData['CCLASE'].right('000'.strval($lcCodTip), 3);
         }
         $lcSql = "INSERT INTO E04TTIP (cTipAfj, cDescri, cEstado, cClase, cCatego, nFacDep, cCntAct, cCntDep, cCntCtr, cCntBaj, cUsuCod)
                   VALUES ('{$lcCodTip}', '{$this->paData['CDESCRI']}', '{$this->paData['CCODEST']}', '{$this->paData['CCLASE']}', '*', 
                           '{$this->paData['CFACDEP']}', '{$this->paData['CCNTACT']}', '{$this->paData['CCNTDEP']}', '{$this->paData['CCNTCTR']}',
                           '{$this->paData['CCNTBAJ']}', '{$this->paData['CUSUCOD']}')";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = 'ERROR AL INSERTAR NUEVO TIPO DE ACTIVO FIJO (NUEVA CUENTA AGREGAR D01MCTA)';
            return false;
         }
         $laData = ['CTIPAFJ' => $lcCodTip];
      } else {
         $lcSql = "SELECT cTipAfj FROM E04TTIP WHERE cTipAfj = '{$this->paData['CTIPACT']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         if (count($laFila) == 0) {
            $this->pcError = "TIPO DE ACTIVO FIJO {$this->paData['CTIPACT']} NO EXISTE";
            return false;
         }
         $lcSql = "UPDATE E04TTIP SET cDescri = '{$this->paData['CDESCRI']}', 
                                      cEstado = '{$this->paData['CCODEST']}',
                                      cClase  = '{$this->paData['CCLASE']}', 
                                      nFacDep =  {$this->paData['CFACDEP']},  
                                      cCntAct = '{$this->paData['CCNTACT']}',
                                      cCntDep = '{$this->paData['CCNTDEP']}', 
                                      cCntCtr = '{$this->paData['CCNTCTR']}',
                                      cCntBaj = '{$this->paData['CCNTBAJ']}',
                                      cUsuCod = '{$this->paData['CUSUCOD']}',
                                      tModifi = NOW()
                                      WHERE cTipAfj = '{$this->paData['CTIPACT']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "ERROR AL ACTUALIZAR TIPO DE ACTIVO FIJO {$this->paData['CTIPACT']}";
            return false;
         }
         $laData = ['CTIPAFJ' => $this->paData['CTIPACT']];
      }
      $this->laData = $laData;
      return $laData;  
   }

   // -----------------------------------------------
   // BUSCAR TIPO DE ACTIVO FIJO
   // GCH 02-11-2022
   // -----------------------------------------------
   public function omBuscarTipoActFij() {
      $llOk = $this->mxValParamBuscarTipoActFij();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarTipoActFij($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarTipoActFij() {
      if (!isset($this->paData['CTIPAFJ'])) {
         $this->pcError = "TIPO DE ACTIVO FIJO NO DEFINIDO O INVÁLIDO";
         return false;
      } 
      return true;
   }

   protected function mxBuscarTipoActFij($p_oSql) {
      $lcSql = "SELECT cTipAfj, cDescri, cEstado, cClase, nFacDep, cCntAct, cCntDep, cCntCtr, cCntBaj 
                FROM E04TTIP WHERE cTipAfj = '{$this->paData['CTIPAFJ']}'";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CTIPAFJ'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'CCLASE'=> $laTmp[3], 'NFACDEP'=> $laTmp[4], 
                          'CCNTACT'=> $laTmp[5], 'CCNTDEP'=> $laTmp[6], 'CCNTCTR'=> $laTmp[7], 'CCNTBAJ'=> $laTmp[8]];
      }
      return true;
   }

   // -----------------------------------------------------------------------
   // PANTALA AFJ1180 - PANTALLA CREAR CUENTA CONTABLE
   // ------------------------------------------------------------------------
   // Init mantenimiento 
   // 2022-01-02 GCH Creacion
   // ------------------------------------------------------------------------
   public function omInitCuentaContable() {
      $llOk = $this->mxValUsuarioActFij();
      if (!$llOk) {
         return false;
      }
      //CONEXION UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCuentaContable($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect(); 
      return $llOk;      
   }

   protected function mxInitCuentaContable($p_oSql) {
      //ESTADO (E04TTIP)
      $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cEstado = 'A' order by cCenCos";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paCenCos[] = ['CCENCOS' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
         $i++;
      }
      return true;
   }
   
   // -----------------------------------------------
   // BUSCAR CUENTA CONTABLE
   // GCH 02-01-2022
   // -----------------------------------------------
   public function omBuscarCuentaContable() {
      $llOk = $this->mxValParamBuscarCuentaContable();
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
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarCuentaContable() {
      if (!isset($this->paData['CCTACNT'])) {
         $this->pcError = "CUENTA CONTABLE NO DEFINIDO O INVÁLIDO";
         return false;
      } 
      return true;
   }

   protected function mxBuscarCuentaContable($p_oSql) {
      $lcSql = "SELECT trim(cCtaCnt), cDescri, cCenCos FROM D01MCTA WHERE cCtaCnt = '{$this->paData['CCTACNT']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatas = ['CCTACNT'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CCENCOS'=> $laTmp[2]];
      }
      if (count($this->paDatas) == 0) {
         $this->pcError = "NO SE ENCONTRO CUENTA CONTABLE";
         return false;
       }
      return true;
   }

   // -------------------------------------------
   // Guardar nueva cuenta contable
   // GCH 01-11-2022
   // -------------------------------------------
   public function omGuardarCuentaContable() {
      $llOk = $this->mxValParamGuardarCuentaContable();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGuardarCuentaContable($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      $this->paData = $this->laData;
      return $llOk;
   }
   
   protected function mxValParamGuardarCuentaContable() {
      if (!isset($this->paData['CCTACNT'])) {
         $this->pcError = "CUENTA CONTABLE NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CDESCRI'])) {
         $this->pcError = "DESCRIPCIÓN NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDA O INVÁLIDO";
         return false;
      } 
      return true;
   }

   protected function mxGuardarCuentaContable($p_oSql) {
      $lcSql = "SELECT count(cCtaCnt) FROM D01MCTA WHERE cCtaCnt = '{$this->paData['CCTACNT']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if($laFila[0] == 0){
         $lcSql = "INSERT INTO D01MCTA (cCtaCnt, cDescri, cGructa, cNivel, cCenCos, cUsuCod)
                VALUES ('{$this->paData['CCTACNT']}', '{$this->paData['CDESCRI']}', '0', '0', '{$this->paData['CCENCOS']}', '{$this->paData['CUSUCOD']}')";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = 'ERROR AL INSERTAR NUEVA CUENTA CONTABLE';
            return false;
         }
         $laData = ['CCTACNT' => $this->paData['CCTACNT']];
      }else{
         $lcSql = "UPDATE D01MCTA SET cDescri = '{$this->paData['CDESCRI']}',
                                      cCenCos  = '{$this->paData['CCENCOS']}', 
                                      cUsuCod = '{$this->paData['CUSUCOD']}',
                                      tModifi = NOW()
                                      WHERE cCtaCnt = '{$this->paData['CCTACNT']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "ERROR AL ACTUALIZAR CUENTA CONTABLE {$this->paData['CCTACNT']}";
            return false;
         }
         $laData = ['CCTACNT' => $this->paData['CCTACNT']];
      }
      $this->laData = $laData;
      return $laData;  
   }


   //-------------------------------------------------------- 
   // INICIO PANTALLA 1160 - Buscar Act Fij PARA DAR DE BAJA
   // --------------------------------------------------------
   // uscar Act Fij PARA DAR DE BAJA
   // 2022-11-14 GCH Creacion
   // --------------------------------------------------------
   public function omMantBajaActivoFijo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxMantBajaActivoFijo($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxMantBajaActivoFijo($p_oSql) {
      // Estado del AF
      $laEstAct = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '333'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laEstAct[] = ['CESTADO'=> $laFila[0], 'CDESCRI'=> $laFila[1]]; 
      }
      if (count($laEstAct) == 0) {
         $this->pcError = 'NO HAY ESTADOS DE ACTIVO FIJO DEFINIDOS [313]';
         return false;
      }      
      // Situacion del AF
      $laSituac = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '334'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laSituac[] = ['CSITUAC' => $laFila[0], 'CDESCRI'  => $laFila[1]]; 
      }
      if (count($laSituac) == 0) {
         $this->pcError = 'NO HAY SITUACIÓN DE ACTIVO FIJO DEFINIDAS [133]';
         return false;
      }
      // Centros de costo
      $lcSql = "SELECT SUBSTR(cCenCos,1,3), cDescri FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laCenCos[] = ['CCENCOS'=> substr($laTmp[0],0,3), 'CDESCRI'=> $laTmp[1]];
         // $laCenCos[] = [substr($laTmp[0],0,3), $laTmp[1]];
      }
      if (count($laCenCos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      // Clases
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), cDescri FROM V_S01TTAB 
                WHERE cCodTab = '336' AND SUBSTRING(cCodigo, 1, 2) != '00'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laDatClaAfj[] = ['CCODCLA' => $laFila[0], 'CDESCLA' => $laFila[1]]; 
      }
      if (count($laDatClaAfj) == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      // Tipo de activo
      $lcSql = "SELECT cTipAfj, cDescri, cEstado FROM E04TTIP WHERE cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laTipAfj[] = ['CTIPAFJ' => $laFila[0], 'CDESTIP' => $laFila[1], 'CESTADO' => $laFila[2]]; 
      }
      if (count($laTipAfj) == 0) {
         $this->pcError = 'NO SE ENCONTRO CLASES DE ACTIVOS!';
         return false;
      }
      $this->paData = ['AESTACT'=> $laEstAct, 'ASITUAC'=> $laSituac, 'CCENCOS'=> $laCenCos, 'CCODCLA'=> $laDatClaAfj, 
                       'CTIPAFJ' =>$laTipAfj];
      return true;
   }

   //---------------------------------------------------
   //PANTALLA 1220 Grabar inventario anual
   //--------------------------------------------------
   // Init - evaluar si hay inventario anual registrado
   // 2023-04-04 GCH
   //---------------------------------------------------
   public function omInitManGrabarInventario() {   
      $loSql = new CSql();
      $llOk = $loSql->omConnect(13);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitManGrabarInventario($loSql);
      $loSql->omDisconnect();      
      return $llOk;

   }

   protected function mxInitManGrabarInventario($p_oSql) {
      $lcDate =  date('Y', time());
      //$lcDate =  2022;
      $lcSql = "SELECT count(nserial) from e04mafj where cperinv = '$lcDate'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if($laFila[0] > 0){
         $this->pcError = "Ya se guardo inventario [{$lcDate}] ";
         return false;
      }     
      return true;
   }


      // -----------------------------------------------------------
   // PANTALLA 1140 INVENTARIO
   //--------------------------------------------
   // Init recuperar centros de costos
   // 2023-08-29 GCH
   //--------------------------------------------
   public function omInitMntoInventario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMntoInventario($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxInitMntoInventario($p_oSql) {
      // Cargar Centro de Costo
      $lcSql = "SELECT cCenCos, cDescri, cNivel FROM S01TCCO WHERE cEstado = 'A' AND 
                cCenCos IN (SELECT DISTINCT cCenCos FROM S01TRES) ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'CNIVEL'=> $laTmp[2]];
      }
      if (count($this->laDatos) == 0) {
        $this->pcError = "NO HAY CENTROS DE COSTO ACTIVOS";
        return false;
      }
      return true;
   }

   //-----------------------------------------------------------------------
   // Init recuperar centros de Responsabilidad con totales de inventario
   // 2023-08-29 GCH
   //-----------------------------------------------------------------------
   public function omBuscarCentrosResponsabilidad() {
      $llOk = $this->mxValParamBuscarCentrosResponsabilidad();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarCentrosResponsabilidad($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxValParamBuscarCentrosResponsabilidad() {
      if (!isset($this->paData['CCENCOS'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarCentrosResponsabilidad($p_oSql) {
      $lcSql = "SELECT A.cCenRes, A.cDescri, A.cEstado, COUNT(cactfij)
                  FROM S01TRES A
                  INNER JOIN E04MAFJ B ON B.cCenRes = A.cCenRes
                  WHERE A.cEstado = 'A'and A.cCenCos = '{$this->paData['CCENCOS']}' and B.cSituac = 'O'
                  GROUP BY A.cCenRes ";
      $R1 = $p_oSql->omExec($lcSql);
      while($laFila = $p_oSql->fetch($R1)){
         $lcSql = "SELECT COUNT(cinvent) FROM e04mafj WHERE ccenres = '$laFila[0]' AND cSituac != 'B' AND cinvent = 'N'";
         // print_r($lcSql);
         $R2 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R2);
         $this->paDatos[] = ["CCENRES" => $laFila[0], "CDESCRI" => $laFila[1], "CESTADO" => $laFila[2], "NTOTAL" => $laFila[3], "NFALINV" => $laTmp[0], "NINVENT" => $laFila[3] - $laTmp[0]];
      }
      if(!$this->paDatos){
         $this->paDatos = ["ERROR" => "Error al consultar Centros de Costo"];
         return false;
      }
      return true; 
   }

   //-----------------------------------------------------------------------
   // Mostar empleados responsables del centro de responsabilidad
   // 2023-09-18 GCH
   //-----------------------------------------------------------------------
   public function omBuscarEmpleadoInventario() {
      $llOk = $this->mxValParamBuscarEmpleadoInventario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarEmpleadoInventario($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxValParamBuscarEmpleadoInventario() {
      if (!isset($this->paData['CCENRES'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarEmpleadoInventario($p_oSql) {
      $lcSql = "SELECT DISTINCT (A.cCodEmp), B.cNombre, COUNT(cactfij),A.cCenRes
                  FROM e04mafj A 
                  INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodEmp
                  WHERE A.ccenres = '{$this->paData['CCENRES']}' 
                  GROUP BY A.cCodEmp, B.cNombre,A.cCenRes ";
      $R1 = $p_oSql->omExec($lcSql);
      while($laFila = $p_oSql->fetch($R1)){
         $lcSql = "SELECT COUNT(cinvent) FROM e04mafj WHERE ccenres = '$laFila[3]' AND cSituac != 'B' AND cinvent = 'N' AND cCodEmp = '$laFila[0]' ";
         //print_r($lcSql);
         $R2 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R2);
         $lcNomEmp = str_replace('/',' ',$laFila[1]);
         $this->paDatos[] = ["CCODEMP" => $laFila[0], "CNOMBRE" => $lcNomEmp, "NTOTAL" => $laFila[2], "NFALINV" => $laTmp[0], "NINVENT" => $laFila[2] - $laTmp[0], "CCENRES" => $laFila[3]];
      }
      // print_r($this->paDatos);
      if(!$this->paDatos){
         $this->paDatos = ["ERROR" => "Error al consultar Centros de Costo"];
         return false;
      }
      return true; 
   }

   //-----------------------------------------------------------------------
   // Init recuperar centros de Responsabilidad con totales de inventario
   // 2023-08-29 GCH
   //-----------------------------------------------------------------------
   public function omBuscarActFijInventario() {
      $llOk = $this->mxValParamBuscarActFijInventario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarActFijInventario($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxValParamBuscarActFijInventario() {
      if (!isset($this->paData['CCENRES'])) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxBuscarActFijInventario($p_oSql) {
      $lcSql = "SELECT A.cactfij, A.ctipafj, A.ncorrel, A.csituac, C.cDescri,  A.cdescri, A.ccodemp, B.cNombre , A.mdatos , A.cInvent, A.mFotogr, A.dFecAlt
                  FROM e04mafj A
                  INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodEmp
                  INNER JOIN V_S01TTAB C ON C.cCodigo = A.cSituac
                  WHERE C.cCodTab = '334' and A.cCenRes = '{$this->paData['CCENRES']}' AND A.csituac = 'O' AND A.cCodEmp = '{$this->paData['CCODEMP']}'
                  ORDER BY  A.ctipafj, A.ncorrel";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->paDatos = ["ERROR" => "Error al consultar Centros de Costo"];
         return false;
      }
      while($laFila = $p_oSql->fetch($R1)){
         $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
         $lcNomEmp = str_replace('/',' ',$laFila[7]);
         $this->paDatos[] = ["CACTFIJ" => $laFila[0], "CTIPAFJ" => $laFila[1], "NCORREL" => $laFila[2], "CSITUAC" => $laFila[3], "CDESSIT" => $laFila[4], 
                             "CDESCRI" => $laFila[5], "CCODEMP" => $laFila[6], "CNOMBRE" => $lcNomEmp, "CCODIGO" => $lcCodigo, "CINVENT" => $laFila[9], 
                             "MFOTOGR" => $laFila[10], "MDATOS" => json_decode($laFila[8], true), "DFECALT" => $laFila[11]];
      }
      // ----Empleado Responsable
      $lcSql = "SELECT ccodusu, cNombre 
                  FROM V_S01TUSU_1 
                  WHERE cCodUsu = '{$this->paData['CCODEMP']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->paData = ["ERROR" => "Error al consultar Empleado"];
         return false;
      }
      while($laFila = $p_oSql->fetch($R1)){
         $lcNomEmp = str_replace('/',' ',$laFila[1]);
         $this->paData= ["CCODEMP" => $laFila[0], "CNOMBRE" => $lcNomEmp];
      }
      return true;
   }

   //-----------------------------------------------------------------------
   // Init recuperar centros de Responsabilidad con totales de inventario
   // 2023-08-29 GCH
   //-----------------------------------------------------------------------
   public function omGrabarInventario() {
      $llOk = $this->mxValParamGrabarInventario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarInventario($loSql);
      $loSql->omDisconnect();      
      return $llOk;
   }

   protected function mxValParamGrabarInventario() {
      if (!isset($this->paData)) {
         $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxGrabarInventario($p_oSql) {
      $lcSql = "SELECT A.cCenRes, A.ccodemp
                  FROM e04mafj A
                  WHERE A.cActFij = '{$this->paData[0]}'";
      $R1 = $p_oSql->omExec($lcSql);
      while($laFila = $p_oSql->fetch($R1)){
         $this->paDato = ["CCENRES" => $laFila[0], "CCODEMP" => $laFila[1]];
      }
      $lcSql = "UPDATE E04MAFJ SET cInvent = 'N' WHERE CCENRES = '{$this->paDato['CCENRES']}' AND CCODEMP = '{$this->paDato['CCODEMP']}' ";
      $llOk = $p_oSql->omExec($lcSql);
      foreach ($this->paData as $laFila) {
         $lcSql = "UPDATE E04MAFJ SET cInvent = 'S' WHERE cActFij = '{$laFila}'";
         // print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = 'No se pudo inventariar activo fijo';
            return false;
         }
      }
      return true;
   }

   // ------------------------------------------------------------------------
   // Enviar email de conformidad inventario
   // 2023-08-29 GCH
   // ------------------------------------------------------------------------
   public function omEnviarReporteInventario() {      
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $this->paDatos = null;
      $llOk = $this->mxEnviarReporteInventario($loSql);
      $loSql->omDisconnect(); 
      $laData = $this->paData;
      $laDatos = $this->paDatos;
      // $laDato = $this->paDato;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laData;
      $lo->paDatos = $laDatos;
      // $lo->paDato = $laDato;
      $llOk = $lo->omPrintReporteFinInventario();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paDato['CREPORT'] = $lo->paDato['CREPORT'];
      $laData = $lo->paData;
      $lo = new CEmail();
      $lo->paData = $laData;
      $llOk = $lo->omSendInventario();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }

   protected function mxEnviarReporteInventario($p_oSql) {
      // print_r($this->paData);
      $lcSql = "SELECT A.cActFij, A.cDescri, A.dFecAlt, A.cCenRes, B.cDescri, A.mDatos, A.cTipAfj, A.nCorrel, A.cCodEmp, C.cNombre, A.cSituac, A.nMontmn , A.nMoncal
                     FROM E04MAFJ A
                     INNER JOIN S01TRES B ON B.cCenRes = A.cCenRes
                     INNER JOIN V_S01TUSU_1 C ON C.cCodUsu = A.cCodEmp
                     INNER JOIN S01TRES D ON D.cCenRes = A.cCenRes
                     INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                     WHERE A.cCenREs = '{$this->paData['CCENRES']}' AND A.cCodEmp = '{$this->paData['CCODEMP']}' 
                     ORDER BY  A.cCenRes, A.cCodEmp, A.cTipAfj, A.nCorrel";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laFila[6], 0, 2).'-'.substr($laFila[6], 2, 5).'-'.right('00000'.strval($laFila[7]), 6);
         $lcNombre = str_replace('/',' ',$laFila[9]);
         $this->paDatos[] = ['CACTFIJ'=> $laFila[0], 'CDESCRI'=> $laFila[1], 'DFECALT'=> $laFila[2], 'CCENRES'=> $laFila[3], 'CDESRES'=> $laFila[4], 'MDATOS'=> json_decode($laFila[5], true), 
                             'CCODIGO'=> $lcCodigo,  'CCODEMP'=> $laFila[8], 'CNOMEMP'=> $lcNombre, 'CSITUAC'=> $laFila[10], 'NMONTO'=> $laFila[11], 'NMONCAL' => $laFila[12]]; 
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON ACTIVOS FIJOS';
         return false;
      }
      $lcSql = "SELECT cCodUsu, cNombre, cEmail, cNroDni FROM V_S01TUSU_1 A WHERE A.cCodUsu in ('{$this->paData['CCODEMP1']}')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcNombre = str_replace('/',' ',$laFila[1]);
         $lcFolder = "/var/www/html/ERP-II/Docs/Inventario/".$this->paData['CYEAR']."/";
         $lcFilePath = $lcFolder.'I'.$this->paData['CCODEMP'].'.pdf';
         $this->paData[] = ['CCODUSU'=> $laFila[0], 'CNOMBRE'=> $lcNombre, 'CEMAIL'=> $laFila[2],'CDOCADJ' =>$lcFilePath, 'CNRODNI'=> $laFila[3]]; 
      }
      $lcSql = "SELECT cCodUsu, cNombre, cEmail, cNroDni FROM V_S01TUSU_1 A WHERE A.cCodUsu in ('{$this->paData['CCODEMP2']}')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcNombre = str_replace('/',' ',$laFila[1]);
         $lcFolder = "/var/www/html/ERP-II/Docs/Inventario/".$this->paData['CYEAR']."/";
         $lcFilePath = $lcFolder.'I'.$this->paData['CCODEMP'].'.pdf';
         $this->paData[] = ['CCODUSU'=> $laFila[0], 'CNOMBRE'=> $lcNombre, 'CEMAIL'=> $laFila[2],'CDOCADJ' =>$lcFilePath, 'CNRODNI'=> $laFila[3]]; 
      }

      return true;
   }

   // -----------------
   // prueba folio
   // ------------------------
   public function omFolio(){
      $laDatos = $this->paData;
      $lo = new CRepControlPatrimonial();
      $lo->paData = $laDatos;
      $llOk = $lo->mxPDF8();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }
   

}
// ------------------------------------------------------

class CRepControlPatrimonial extends CBase {

   function mxPDF8() {
      $this->paData['CFOLIO'] =right('00000'.strval($this->paData['CFOLIO']), 6);
      $lcFilRep = 'FILES/FPA'.$this->paData['CFOLIO'].'.pdf';
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      // $loPdf->SetMargins(0, 1);
      // $loPdf->SetAutoPageBreak(false);
      $loPdf->AddPage('P', 'A4');   
      $loPdf->SetFont('Arial','', 8);
      $loPdf->Ln(1); 
      $loPdf->Cell(50, 1,fxString(' ',215).'FOLIO : '.$this->paData['CFOLIO'],0,0,'J');
      $loPdf->Ln(1);
      $loPdf->SetFont('Courier', 'B', 14);
      $loPdf->Image('img/Logo_trazos.png',8,8,40);
      $loPdf->Ln(6); 
      $loPdf->Cell(50, 1,utf8_decode('                UNIVERSIDAD CATÓLICA DE SANTA MARÍA'));
      $loPdf->Ln(4); 
      $loPdf->Cell(50, 1,utf8_decode('                 ACTA DE PROCESO DE ACTUALIZACIÓN'),0,0,'J');
      $loPdf->Ln(9);
      $loPdf->SetFont('Arial','', 9);
      $loPdf->MultiCell(190, 4, utf8_decode('En el local de la Universidad Católica de Santa María, siendo las '.date("H:i").' horas del dia 08 de Noviembre del 2022, se reunió el Jurado integrado por los señores docentes:'), 0,'L');  
      $loPdf->Ln(5);
      $loPdf->SetFont('Arial','B',10);
      $loPdf->Cell(30, 0, 'PRESIDENTE:   ', 0,  'L');
      $loPdf->SetFont('Arial','',10);
      $loPdf->Cell(30, 0, utf8_decode($this->paData['ACODDOC'][0]['CNRODNI'].' - '.$this->paData['ACODDOC'][0]['CNOMBRE']), 0, 0, 'L');
      $loPdf->Ln(5);
      $loPdf->SetFont('Arial','B',10); 
      $loPdf->Cell(30, 0, 'VOCAL: ', 0, 'L');
      $loPdf->SetFont('Arial','',10);
      $loPdf->Cell(30, 0, utf8_decode($this->paData['ACODDOC'][1]['CNRODNI'].' - '.$this->paData['ACODDOC'][1]['CNOMBRE']), 0, 0, 'L');
      $loPdf->Ln(5);
      $loPdf->SetFont('Arial','B',10);
      $loPdf->Cell(30, 0, 'SECRETARIO:  ', 0, 0, 'L');
      $loPdf->SetFont('Arial','',10);
      $loPdf->Cell(30, 0, utf8_decode($this->paData['ACODDOC'][2]['CNRODNI'].' - '.$this->paData['ACODDOC'][2]['CNOMBRE']), 0, 0, 'L');
      $loPdf->Ln(5);
      $loPdf->SetFont('Arial','', 9);
      $loPdf->MultiCell(190, 4, utf8_decode('Para evaluar los expedientes presentados por los/las señores/señoritas Bachilleres:'), 0,'L');  
      $loPdf->Ln(2);
      $loPdf->SetFont('Arial','B', 9);
      $width_cell=array(40,130);
      $loPdf->Cell($width_cell[0],10,utf8_decode('Nro.'),0,0,'C',false); 
      $loPdf->Cell(70,10,utf8_decode('APELLIDOS Y NOMBRES'),0,0,'C',false);   
      $loPdf->Ln(7);
      $loPdf->SetFont('Arial','',10);
      $i = 1;
      foreach($this->paData['ACODALU'] as $lcFila){
         $loPdf->Cell($width_cell[0],10,utf8_decode($i),0,0,'C',false);
         $loPdf->Cell($width_cell[1],10,utf8_decode($lcFila['CNOMBRE']),0,0,'L',false);
         $loPdf->Ln(7);
         $i++;
      }
      $loPdf->Ln(4);
      $loPdf->SetFont('Arial','', 9);
      $loPdf->MultiCell(190, 4, utf8_decode('Quienes desean optar el Titulo Profesional en concordancia con la Resolución '.$this->paData['CRESOLU'].' que aprueba el ciclo de Proceso de Actualización en Contenidos de la Profesión, tendente al logro del Titulo Profesional de:'), 0,'J');  
      $loPdf->Ln(1);
      $loPdf->SetFont('Arial','B',10);
      $loPdf->Cell(180, 10, utf8_decode('INGENIERO(A) INDUSTRIAL'),0,0,'C',false);
      $loPdf->Ln(9);
      $loPdf->SetFont('Arial','', 9);
      $loPdf->MultiCell(190, 4, utf8_decode('POR LO TANTO'), 0,'J');  
      $loPdf->Ln(2);
      $loPdf->SetFont('Arial','', 9);
      $loPdf->MultiCell(190, 4, utf8_decode('Encontrándose conforme el expediente el jurado acordó darles el resultado de:'), 0,'J');  
      $loPdf->Ln(1);
      $loPdf->SetFont('Arial','B',10);
      $loPdf->Cell(180, 10, utf8_decode('APROBADO(S) POR UNANIMIDAD'),0,0,'C',false);
      $loPdf->Ln(10);
      $loPdf->SetFont('Arial','', 9);
      $loPdf->MultiCell(190, 4, utf8_decode('Siendo las '.date("H:i").', se dio por concluido el acto, y firmaron.'), 0,'J');  
      $loPdf->Ln(3);
      $width_cell=array(40,130);
      $lcFilPre = 'FILES/R'.rand().'.png';
      $lcClaPre = PHP_EOL.'PRESIDENTE: '.PHP_EOL.$this->paData['ACODDOC'][0]['CNRODNI'].' - '.$this->paData['ACODDOC'][0]['CNOMBRE'].PHP_EOL.$ldDate;
         // print_r($lcClave);
      QRcode::png(utf8_encode($lcClaPre), $lcFilPre , QR_ECLEVEL_L, 4, 0, true);
      $loPdf->Cell($width_cell[0],10,utf8_decode($this->paData['ACODDOC'][0]['CNRODNI']),0,0,'C',false);
      $loPdf->Cell($width_cell[1],10,utf8_decode($this->paData['ACODDOC'][0]['CNOMBRE']),0,0,'L',false);
      // print_r($i);
      if($i == 3){
         $loPdf->Image($lcFilPre, 150, 137, 27, 27);
      }else{
         $loPdf->Image($lcFilPre, 150, 196, 27, 27);
      }
      $loPdf->Ln(32);
      $lcFilVo = 'FILES/R'.rand().'.png';
      $lcClaVo = PHP_EOL.'VOCAL: '.PHP_EOL.$this->paData['ACODDOC'][1]['CNRODNI'].' - '.$this->paData['ACODDOC'][1]['CNOMBRE'].PHP_EOL.$ldDate;
         // print_r($lcClave);
      QRcode::png(utf8_encode($lcClaVo), $lcFilVo , QR_ECLEVEL_L, 4, 0, true);
      $loPdf->Cell($width_cell[0],10,utf8_decode($this->paData['ACODDOC'][1]['CNRODNI']),0,0,'C',false);
      $loPdf->Cell($width_cell[1],10,utf8_decode($this->paData['ACODDOC'][1]['CNOMBRE']),0,0,'L',false);
      if($i == 3){
         $loPdf->Image($lcFilPre, 150, 169, 27, 27);
      }else{
         $loPdf->Image($lcFilVo, 150, 228, 27, 27);
      }
      $loPdf->Ln(30);
      $lcFilSe = 'FILES/R'.rand().'.png';
      $lcClaSe = PHP_EOL.'SECRETARIO: '.PHP_EOL.$this->paData['ACODDOC'][2]['CNRODNI'].' - '.$this->paData['ACODDOC'][2]['CNOMBRE'].PHP_EOL.$ldDate;
         // print_r($lcClave);
      QRcode::png(utf8_encode($lcClaSe), $lcFilSe , QR_ECLEVEL_L, 4, 0, true);
      $loPdf->Cell($width_cell[0],10,utf8_decode($this->paData['ACODDOC'][2]['CNRODNI']),0,0,'C',false);
      $loPdf->Cell($width_cell[1],10,utf8_decode($this->paData['ACODDOC'][2]['CNOMBRE']),0,0,'L',false);
      if($i == 3){
         $loPdf->Image($lcFilPre, 150, 200, 27, 27);
      }else{
         $loPdf->Image($lcFilSe, 150, 260, 27, 27);
      }
      $loPdf->Ln(32);      
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }
   
   public function omPrintRepActivos() {
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      $lcNombre = '*';
      foreach ($this->paData['DATOS'] as $laFila) {
         if($laFila['CCODEMP'] != $lcNombre){
            $lcLinea = utf8_decode('- '.fxString($laFila['CCODEMP'],4).' - '.fxStringFixed($laFila['CNOMBRE'],50));
            $laDatos[] = $lcLinea;
            $lcNombre = $laFila['CCODEMP'];
         }
         $lcLinea = utf8_decode('  '.fxNumber($i,3,0).' '.fxString($laFila['CACTFIJ'],5).' '.fxString($laFila['CCODIGO'],13).' '.fxStringFixed($laFila['CDESCRI'],80).' '.fxString($laFila['CSITUAC'],2).'  '.fxString($laFila['DADQUIS'],10).' '.fxNumber($laFila['NMONTMN'],10,2));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                    REPORTE ACTIVOS FIJOS                                         PAG.'.fxNumber($lnPag, 6, 0));
            
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                  '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1070', 0, 0, 'L');
            $loPdf->Ln(0);
            $loPdf->Image('img/logo_trazos.png',10,10,45);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(195, 3,'CENTRO DE COSTO           : ', 0, 0, 'J');
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Cell(195, 3,'                           '.utf8_decode($this->paData['CCENCOS'].' - '.$this->paData['CDESCRI']), 0, 0, 'J');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(195, 3,'NIVEL                     : ', 0, 0, 'J');
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Cell(195, 3,'                           '.$this->paData['CNIVEL'], 0, 0, 'J');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(195, 3,'CENTRO DE RESPONSABILIDAD : ', 0, 0, 'J');
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Cell(195, 3,'                           '.utf8_decode($this->paData['CCENRES'].' - '.$this->paData['CDESRES']), 0, 0, 'J');
            $loPdf->ln(4);
            $loPdf->ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(0, 2, '_____________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, ' # CODIGO            DESCRI                                                                    SIT.   FECHA      MONEDA ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_____________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 9;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 3, $lcLinea);
         $loPdf->Ln(3);
         $lnRow++;
         $llTitulo = ($lnRow == 36) ? true : false;
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
  }

      public function omPrintReporteActFij() {
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      $lnMonReg = 0;
      $lcCenRes = '*';
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode('- '.fxString($laFila['CCENRES'],5).' - '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES']; 
         } 
         $lcLinea = utf8_decode(fxNumber($i,4,0).'  '.$laFila['CACTFIJ'].'  '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],50).' '.fxNumber($laFila['NMONTMN'],14,2).'  '.fxString($laFila['CCODREF'],10));
         $laDatos[] = $lcLinea;  
         $i++;
         $lnMonReg = $lnMonReg + $laFila['NMONTMN'];
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                 REPORTE ACTIVOS FIJOS INGRESADOS                                   PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1020', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            // $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '  #  ACT.FIJ. CODIGO         DESCRIPCION                                             MONTO    ASI.CON. ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 4;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 42) ? true : false;
      }
      $loPdf->Ln(2);
      $loPdf->Cell(0, 2, '                                                                               ____________', 0);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, '                                                           Total Registrado: '.fxNumber($lnMonReg,14,2), 0);
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function mxReporteActRegPDF() {
      //print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      $lcCenRes = '*';
      $lcTotal = 0;
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode('- '.fxString($laFila['CCENRES'],4).' - '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES']; 
         } 
         $lcLinea = utf8_decode(fxNumber($i,4,0).'  '.$laFila['CACTFIJ'].'  '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],57).'  '.fxString($laFila['CSITUAC'],2).' '.fxNumber($laFila['NMONTO'],14,2).'  '.fxString($laFila['CCODEMP'],4).' - '.fxStringFixed($laFila['CNOMBRE'],30));
         $laDatos[] = $lcLinea;  
         $i++;
         $lcTotal = $lcTotal + $laFila['NMONTO'];
      }
      $lcLinea = utf8_decode(fxString('',83).'       _____________');
      $laDatos[] = $lcLinea;
      $lcLinea = utf8_decode(fxString('',83).'TOTAL: '.fxNumber($lcTotal,12,2));
      $laDatos[] = $lcLinea;
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                 REPORTE ACTIVOS FIJOS INGRESADOS                                   PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1020', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            // $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '  #  ACT.FIJ.   CODIGO         DESCRIPCION                                           SIT.       MONTO        EMPLEADO ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 4;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 42) ? true : false;
      }
      $loPdf->Ln(4);
      $i = $i - 1;
      $loPdf->Cell(264, 3,'Total unid: '.$i, 0, 0, 'L');
      $loPdf->Ln(2);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omPrintReporteActFijPDF() {
      //print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      $loPdf->Ln(4);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Cell(50, 1,'                                  REPORTE ACTIVO FIJO                          '.$ldDate);
      $loPdf->Ln(6);
      $loPdf->SetFont('Courier', 'B', 7);  
      $loPdf->Cell(264, 3,'AFJ2010', 0, 0, 'L');
      $loPdf->Ln(2);
      $loPdf->Image('img/logo_trazos.png',10,10,35);
      $loPdf->Ln(6);
      $loPdf->Cell(0, 2, '', 0);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Activo Fijo       : ');
      $loPdf->Ln(0);
      $loPdf->Cell(50, 1,'                    '.utf8_decode($this->paDatos['CACTFIJ'].' / '.$this->paDatos['CCODIGO']));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,utf8_decode('Descripción       : '));
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.utf8_decode($this->paDatos['CDESCRI']));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,utf8_decode('Doc. Adquisición  : '));
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CDOCADQ']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Fecha Alta        : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['DFECALT']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Tipo              : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CTIPAFJ'].' - '.$this->paDatos['CDESTIP']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Estado            : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CESTADO'].' - '.$this->paDatos['CDESEST']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,utf8_decode('Situación         : '));
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CSITUAC'].' - '.$this->paDatos['CDESSIT']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Monto             : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.fxNumber($this->paDatos['NMONTMN'],12,2));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Centro de Costo   : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.utf8_decode($this->paDatos['CCENCOS'].' - '.$this->paDatos['CDESCEN']));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Centro Resp.      : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.utf8_decode($this->paDatos['CCENRES'].' - '.$this->paDatos['CDESRES']));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Empleado          : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.utf8_decode($this->paDatos['CCODEMP'].' - '.$this->paDatos['CNOMEMP']));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Proveedor         : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.utf8_decode($this->paDatos['CNRORUC'].' - '.$this->paDatos['CRAZSOC']));
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Cantidad          : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CCANTID']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Marca             : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CMARCA']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Placa             : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CPLACA']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Nro. Serie        : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CNROSER']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Modelo            : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CMODELO']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,utf8_decode('Código Artículo   : '));
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CCODART']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,utf8_decode('Código Ref.       : '));
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CCODREF']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Fecha Baja        : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['DFECBAJ']);
      $loPdf->Ln(3);
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Ln(2);
      $loPdf->Cell(50, 1,'Doc. Baja         : ');
      $loPdf->Ln(0);
      $loPdf->SetFont('Courier', '', 10);
      $loPdf->Cell(50, 1,'                    '.$this->paDatos['CDOCBAJ']);
      $loPdf->Ln(3);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }


   public function omPrintReportePorFechaPDF() {
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcCenRes = '*';
      $lcCodEmp = '*';
      $i = 1;
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            if ($i != 1) {
                     $i = 1;
            }
            $lcLinea = utf8_decode('-'.fxString($laFila['CCENRES'],5).' '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES']; 
            $lcCodEmp = '*';
         }
         if($laFila['CCODEMP'] != $lcCodEmp){
      	  if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' *'.fxString($laFila['CCODEMP'],4).' '.fxStringFixed($laFila['CNOMEMP'],50));
            $laDatos[] = $lcLinea;
            $lcCodEmp = $laFila['CCODEMP']; 
         } 
         $lcLinea = utf8_decode(fxNumber($i,4,0).' '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],50).' '.fxString($laFila['DFECHA'],10).'  '.$laFila['CSITUAC'].'  '.fxString($laFila['CDATOS']['CMARCA'],15).' '.fxString($laFila['CDATOS']['CMODELO'],15).' '.fxString($laFila['CDATOS']['CNROSER'],15).' '.fxNumber($laFila['NMONTO'],11,2));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion*
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                     REPORTE ACTIVOS FIJOS POR FECHA                                  PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                   DESDE: '.$this->paData['DDESDE'].' HASTA: '.$this->paData['DHASTA'].'                                '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Ln(3);
            $loPdf->Cell(264, 3,'AFJ2040', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(1);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '   CODIGO         DESCRIPCION                                         FECHA    SIT.  MARCA          MODELO          SERIE             MONTO', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 4;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 41) ? true : false;
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omPrintReportePorCentroCostoPDF() {
      //print_r($this->paDatos);
      // print_r($this->paData);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcCenCos = '*';
      $i = 1;
      foreach ($this->paDatos as $laFila) { 
         if($lcCenCos !== $laFila['CCENCOS'] ){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode('- '.$laFila['CCENCOS'].' - '.fxStringFixed($laFila['CDESCOS'],50));
            $laDatos[] = $lcLinea;
            $lcCenCos = $laFila['CCENCOS'];
         }
         $lcLinea = utf8_decode('   '.$i.' '.$laFila['CCENRES'].' - '.fxStringFixed($laFila['CDESRES'],55));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                            REPORTE CENTRO DE COSTOS DETALLE                    PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Ln(3);
            $loPdf->Cell(264, 3,'AFJ2060', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(1);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Ln(1);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, 'CODIGO  DESCRIPCION', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 4;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 62) ? true : false;
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omPrintReporteTrans() {
      $lcFilRep = 'Docs/TransActFij/T'.$this->paData['CIDTRNF'].'.pdf';
      $i = 1;
      foreach ($this->paDatos as $laFila) {  
         $lcLinea = utf8_decode(fxNumber($i,3,0).' '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],45).' '.$laFila['CSITUAC'].'   '.fxString($laFila['MDATOS']['CMARCA'],13).' '.fxString($laFila['MDATOS']['CMODELO'],13).' '.fxString($laFila['MDATOS']['CNROSER'],13));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                              REPORTE ACTIVOS FIJOS TRANSFERIDOS                PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1170', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'TRANSFERENCIA        : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,utf8_decode('                       '.$this->paData['CIDTRNF'].' - '.fxStringFixed($this->paData['CDESCRI'],50)),0);
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'FECHA                : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.fxString($this->paData['TREGIST'],10),0);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'ORIGEN: ');
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'  C. COSTO            : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CDESCOS'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  C. RESPONSABILIDAD  : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CDESRES'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  USUARIO            : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.$this->paData['CNROORI'].' - '.utf8_decode(fxStringFixed($this->paData['CNOMEMP'],50)),0);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'DESTINO: ');
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'  C. COSTO           : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CCODEDES'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  C. RESPONSABILIDAD : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CRESDESD'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  USUARIO            : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.$this->paData['CNRODES'].' - '.utf8_decode(fxStringFixed($this->paData['DNOMDES'],50)),0);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '  # CODIGO         DESCRIPCION                                MARCA      MODELO      SERIE    ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 8);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 45) ? true : false;
         $loPdf->Ln(2);
      }
      $i = $i-1;
      $loPdf->Cell(0, 2, '______________________________________________________________________________________________________________', 0);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, $i.' item', 0);
      //print_r($this->paDato);
      //1ra firma
      $lcFile = 'FILES/R'.rand().'.png';
      $lcClave = $this->paDato[0]['CNRODNI'].' - '.$this->paDato[0]['CNOMBRE'].PHP_EOL.$this->paData['TREGIST'];
      QRcode::png(utf8_encode($lcClave), $lcFile , QR_ECLEVEL_L, 4, 0, true);
      //2da firma
      $lcFile_ = 'FILES/R'.rand().'.png';
      $lcClave_ = $this->paDato[1]['CNRODNI'].' - '.$this->paDato[1]['CNOMBRE'].PHP_EOL.$this->paData['TREGIST'];
      QRcode::png(utf8_encode($lcClave_), $lcFile_ , QR_ECLEVEL_L, 4, 0, true);
      //print_r($lcClave_);
      $loPdf->Image('img/entregado.png',50,270,35);
      $loPdf->Image($lcFile,60,265,20);

      $loPdf->Image('img/recibido.png',120,270,35);
      $loPdf->Image($lcFile_,129,265,20);
      $loPdf->Ln(3);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      //print_r($this->paData);
      return true;
   }

   public function omPrintReporteTransInformatica() {
      $lcFilRep = 'Docs/TransActFij/T'.$this->paData['CIDTRNF'].'.pdf';
      //print_r($lcFilRep);
      $i = 1;
      foreach ($this->paDatos as $laFila) {  
         $lcLinea = utf8_decode(fxNumber($i,3,0).' '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],45).' '.$laFila['CSITUAC'].'   '.fxString($laFila['MDATOS']['CMARCA'],13).' '.fxString($laFila['MDATOS']['CMODELO'],13).' '.fxString($laFila['MDATOS']['CNROSER'],13));
            $laDatos[] = $lcLinea;
            $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                              REPORTE ACTIVOS FIJOS TRANSFERIDOS                PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1200', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'TRANSFERENCIA        : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.$this->paData['CIDTRNF'].' - '.fxStringFixed($this->paData['CDESCRI'],50),0);
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                                        FECHA : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                                                                                '.fxString($this->paData['TREGIST'],10),0);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'ORIGEN: ');
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'  C. COSTO           : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CDESCOS'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  C. RESPONSABILIDAD : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CDESRES'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  USUARIO            : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.$this->paData['CNROORI'].' - '.utf8_decode(fxStringFixed($this->paData['CNOMEMP'],50)),0);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'DESTINO: ');
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'  C. COSTO           : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CCODEDES'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  C. RESPONSABILIDAD : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.utf8_decode(fxStringFixed($this->paData['CRESDESD'],50)));
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'  USUARIO            : ');
            $loPdf->SetFont('Courier', '', 10);
            $loPdf->Ln(0);
            $loPdf->Cell(50, 1,'                       '.$this->paData['CNRODES'].' - '.utf8_decode(fxStringFixed($this->paData['DNOMDES'],50)),0);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '  # CODIGO         DESCRIPCION                                MARCA      MODELO      SERIE    ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 8);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 62) ? true : false;
      }
      $i = $i-1;
      $loPdf->Cell(0, 2, '_____________________________________________________________________________________________________________', 0);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, $i.' item', 0);
      $lcFile = 'FILES/R'.rand().'.png';
      $lcClave = $this->paDato[0]['CNRODNI'].' - '.$this->paDato[0]['CNOMBRE'].PHP_EOL.$this->paData['TREGIST'];
      QRcode::png(utf8_encode($lcClave), $lcFile , QR_ECLEVEL_L, 4, 0, true);
      //2da firma
      $lcFile_ = 'FILES/R'.rand().'.png';
      $lcClave_ = $this->paDato[1]['CNRODNI'].' - '.$this->paDato[1]['CNOMBRE'].PHP_EOL.$this->paData['TREGIST'];
      QRcode::png(utf8_encode($lcClave_), $lcFile_ , QR_ECLEVEL_L, 4, 0, true);
      //print_r($lcClave_);
      $loPdf->Image('img/entregado.png',50,270,35);
      $loPdf->Image($lcFile,60,265,20);

      $loPdf->Image('img/recibido.png',120,270,35);
      $loPdf->Image($lcFile_,129,265,20);
      $loPdf->Ln(3);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function mxPrintReportOCS_() {
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcTipRep = (!isset($this->paData['CTIPREP']))? 'N' : $this->paData['CTIPREP'];
      $laSuma = 0.00;
      $ldDate = date('Y-m-d', time());
      $lnCanArt = 1;
      $lcTipo = '';
      $lcMoneda = '';
      $lmObserv = '';
      $i = 0;
      foreach ($this->paDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         $this->laDatos[$i] = $laFila;
         /*
         UCSM-ERP                                                                            PAG.:    1
         ----------------------------------------------------------------------------------------------
         * ID. ORDEN        : 18000651                                           FECHA   :   2018-02-12
         * CENTRO DE COSTO  : 000 - DESCRIPCION DEL CENTRO DE COSTO
         * PROVEEDOR        : 00000000000 -  1234567890123456789012345678901234567890
         * DETALLE DE COMPRA: INMEDIATA                                          TELEFONO: 992128216000
         * CONDICION DE PAGO: 15 DIAS
         * CTA.BANCO SOLES : 12345678901234                        * CTA. BANCO US DOL: 12345678901234
         * CTA.BANCO US DOL :
         */
         $lcTipo = $laFila['CTIPO'];
         $lmObserv = $laFila['MOBSERV'];
         if ((float)$laFila['NCANTID'] != 0.0) {
            $lnSubTot = $laFila['NCANTID'] * $laFila['NCOSTO'];
            $laSuma += $laFila['NCANTID'] * $laFila['NCOSTO'];
            // CANTIDAD DE CARACTERES EN EL DETALLE 106
            // 3 + 1 + 8 + 1 + 12 + 1 + 3 + 1 + 46 + 4 + 12 + 1 + 12
            $laDatos[] = ['CLINEA' => fxNumber($lnCanArt, 3, 0) . ' ' . $laFila['CCODART'] . ' ' . fxNumber($laFila['NCANTID'], 12, 2) . ' ' . fxString($laFila['CUNIDAD'], 3) . ' ' . fxString($laFila['CDESART'], 49) . '    ' . fxNumber($laFila['NCOSTO'], 12, 2) . ' ' . fxNumber($lnSubTot, 12, 2), 'CESTFUE' => '', 'NTAMFUE' => 8, 'NSUBTOT' => $laSuma];
            $lcDescri = $laFila['CDESCRI'];
            do {
               if ($lcDescri == '') break;
               $laDatos[] = ['CLINEA' => fxString('', 30) . fxString($lcDescri, 49) . fxString('', 29), 'CESTFUE' => '', 'NTAMFUE' => 8, 'NSUBTOT' => $laSuma];
               $lcDescri = trim(fxStringTail($lcDescri, 49));
            } while(true);
            $lnCanArt += 1;
         }
         $i++;
      }
      //Numeros a Letras
      $loNumLet = new CNumeroLetras();
      $lcMonTot = strtoupper($loNumLet->omNumeroLetras($this->laDatos[0]['NMONTO'], $this->laDatos[0]['CDESMON']));
      $loPdf = new FPDF('portrait','cm','A4');
      $loPdf->SetMargins(1.3, 2.2, 1.3);
      $loPdf->SetAutoPageBreak(false);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $lnWidth = 0;
      $lnHeight = 0.4;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->Image("./img/ucsm-02.jpg" , 1.3, 1.4, 0, 2.3);
            $loPdf->SetFont('Courier', 'B' , 17);
            $loPdf->Cell($lnWidth, $lnHeight, '', 0, 2, 'C');
            $loPdf->Cell($lnWidth, $lnHeight, '', 0, 2, 'C');
            $loPdf->SetFont('Courier', 'B' , 14);
            $loPdf->SetTextColor(242,53,13);
            $loPdf->Cell($lnWidth, $lnHeight, $this->laDatos[0]['CCODANT'], 0, 2, 'R');
            $loPdf->Cell($lnWidth, 0.5, '', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->SetTextColor(0,0,0);
            $loPdf->Cell($lnWidth, $lnHeight, 'UCSM-ERP                                                                              PAG.:' . fxNumber($lnPag, 5, 0), 0, 2, 'L');
            // 96 CARACTERES POR LINEA EN LA CABECERA
            $loPdf->SetFont('Courier', 'BI' , 7);
            $loPdf->Cell($lnWidth, 0.2, '', 0, 2, 'C');
            $loPdf->SetFont('Courier', 'B' , 9);
            // 96 - 42 = 54 DISPONIBLES - 11 RUC = 43 DISPONIBLES
            $lnTam = (strlen(trim($this->laDatos[0]['CRAZSOC'])) < 36)? strlen(trim($this->laDatos[0]['CRAZSOC'])) : 36;
            $loPdf->Cell($lnWidth, $lnHeight, 'PROVEEDOR:       : ' . fxString($this->laDatos[0]['CRAZSOC'], $lnTam) . ' ' . $this->laDatos[0]['CNRORUC'] . fxString('', 36 - $lnTam) .' FECHA DE EMISION: ' . $this->laDatos[0]['DGENERA'], 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DIRECCIÓN        : ') . fxString($this->laDatos[0]['CDIRECC'], 56) . ' COD.PROV: ' . fxString($this->laDatos[0]['CCODPRV'], 10), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'EMAIL            : ' . fxString($this->laDatos[0]['CEMAIL'] , 54) . utf8_decode(' TELÉFONO: ') . fxString($this->laDatos[0]['CNROCEL'], 12), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'TIEMPO DE ENTREGA: ' . fxString($this->laDatos[0]['CDETENT'], 77), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'LUGAR DE ENTREGA : ' . fxString($this->laDatos[0]['CLUGAR'], 77), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('CONDICIÓN DE PAGO: ') . fxString($this->laDatos[0]['CFORPAG'], 77), 0, 2, 'L');
            if ($this->laDatos[0]['CMONEDA'] == '1') {
               $loPdf->Cell($lnWidth, $lnHeight, 'CTA.BANCO SOLES  : ' . fxString($this->laDatos[0]['CCTABC1'], 77), 0, 2, 'L');
            } elseif ($this->laDatos[0]['CMONEDA'] == '2') {
               $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('CTA.BANCO DÓLARES: ') . fxString($this->laDatos[0]['CCTABC2'], 77), 0, 2, 'L');
            } else {
               $loPdf->Cell($lnWidth, $lnHeight, 'CTA.BANCO        : ' . fxString('NO DEFINIDA PARA MONEDA QUE NO SEA SOLES O DÓLARES', 75), 0, 2, 'L');
            }
            $loPdf->Cell($lnWidth, $lnHeight, 'CENTRO DE COSTO  : ' . $this->laDatos[0]['CCENCOS'] . ' - ' . fxString($this->laDatos[0]['CDESCCO'], 50) . utf8_decode(' CÓDIGO  : ') . fxString($this->laDatos[0]['CCCOANT'], 10), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '---DETALLE--------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode(' #   CÓDIGO      CANTIDAD UNI                   DESCRIPCIÓN                     PRECIO UNITARIO        TOTAL'), 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------', 0, 2, 'L');
            if ($lnPag > 1) {
               $loPdf->SetFont('Courier', 'BI', $laFila['NTAMFUE']);
               if ($this->laDatos[0]['CMONEDA'] == '1') {
                  $loPdf->Cell($lnWidth, $lnHeight, '* VIENEN ...                                                                               S/ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               } else {
                  $loPdf->Cell($lnWidth, $lnHeight, '* VIENEN ...                                                                                $ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               }
            }
            $lnRow = 0;
         }
         $loPdf->SetFont('Courier', $laFila['CESTFUE'], $laFila['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, $laFila['CLINEA'], 0, 2, 'L');
         $lnRow++;
         if ($lnRow == 30 && count($laDatos) != (30*$lnPag)) {
            $lnSubTot = $laFila['NSUBTOT'];
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'BI' , $laFila['NTAMFUE']);
            if ($this->laDatos[0]['CMONEDA'] == '1') {
               $loPdf->Cell($lnWidth, $lnHeight, '* VAN ...                                                                                  S/ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               $loPdf->Cell($lnWidth, $lnHeight, '* La presente orden tiene '.($lnCanArt - 1).' items por el importe total de S/ ' . trim(fxNumber($laDatos[count($laDatos)-1]['NSUBTOT'], 12, 2)), 0, 2, 'L');
               $loPdf->SetFont('Courier', 'B' , 9);
            } elseif ($this->laDatos[0]['CMONEDA'] == '2') {
               $loPdf->Cell($lnWidth, $lnHeight, '* VAN ...                                                                                   $ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               $loPdf->Cell($lnWidth, $lnHeight, '* La presente orden tiene '.($lnCanArt - 1).' items por el importe total de $ ' . trim(fxNumber($laDatos[count($laDatos)-1]['NSUBTOT'], 12, 2)), 0, 2, 'L');
               $loPdf->SetFont('Courier', 'B' , 9);
            }
            $llTitulo = true;
         } else {
            $llTitulo = false;
         }
      }
      for ($i = $lnRow; $i <= 30 ; $i++) {
         $loPdf->SetFont('Courier', $laDatos[0]['CESTFUE'], $laDatos[0]['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, '', 0, 2, 'L');
      }
      $loPdf->SetFont('Courier', 'B' , 9);
      $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------', 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight + 0.1, 'TOTAL ORDEN                                                                     '. fxString($this->laDatos[0]['CMONCOR'], 3) . ' ' . fxNumber($this->laDatos[0]['NMONTO'], 12, 2), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight + 0.1, fxString($lcMonTot, 96), 0, 2, 'L');
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(4.1, $lnHeight/1.5, 'PARTIDA PRESUPUESTAL:', 0, 0, 'L');
      $loPdf->SetFont('Courier', '', 7);
      $loDate = new CDate();
      if ($this->laDatos[0]['CESTADO'] != 'A') {
         $laObserv = explode("/", $lmObserv, 2);
         $lmObserv = (!$loDate->valDate(substr($laObserv[0], 0, 10)))? trim($lmObserv) : trim($laObserv[1]);
      }
      $lnTam = 36 - (strlen(trim($this->laDatos[0]['CCODPAR'])) - 21);
      if ($this->laDatos[0]['DAFECTA'] == null) {
         $loPdf->Cell($lnWidth, $lnHeight/1.5, '-', 0, 1, 'L');
      } else {
         $loPdf->Cell($lnWidth, $lnHeight/1.5, $this->laDatos[0]['DAFECTA'] . ': ' . trim($this->laDatos[0]['CCODPAR']).' - '.fxString($this->laDatos[0]['CDESPAR'], $lnTam), 0, 1, 'L');
      }
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(4.1, $lnHeight/1.5, utf8_decode('LIBERACIÓN LOGÍSTICA:'), 0, 0, 'L');
      $loPdf->SetFont('Courier', '', 7);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2 - 4.1, $lnHeight/1.5, isset($this->laFirmas[0])? $this->laFirmas[0]['DFIRMA'] : '-', 0, 0, 'L');
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(4.1, $lnHeight/1.5, utf8_decode('LIBERACIÓN VICE. ADMINIS.:'), 0, 0, 'L');
      $loPdf->SetFont('Courier', '', 7);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2 - 4.1, $lnHeight/1.5, isset($this->laFirmas[1])? $this->laFirmas[1]['DFIRMA'] : '-', 0, 1, 'L');
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Ln($lnHeight - 0.32);
      $loPdf->Cell($lnWidth, $lnHeight/1.5, 'OBSERVACIONES:', 0, 2, 'L');
      $loPdf->SetFont('Courier', $laDatos[0]['CESTFUE'], 7);
      $lnRow = 0;
      if ($lmObserv == '') {
         $loPdf->Cell($lnWidth, $lnHeight/1.5, 'SIN OBSERVACIONES.', 0, 2, 'L');
         $lnRow++;
      } else {
         $loPdf->Multicell($lnWidth, $lnHeight/1.5, $lmObserv, 0, 'J');
      }
      $loPdf->SetFont('Courier', 'BI', 7);
      $loPdf->Ln($lnHeight - 0.32);
      $loPdf->Cell($lnWidth, $lnHeight/1.5, utf8_decode('CREACIÓN: ').$this->laDatos[0]['CUSUGEN'].' - '.$this->laDatos[0]['CNOMGEN'], 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight/1.5, utf8_decode('COTIZADOR: ').$this->laDatos[0]['CUSUCOT'].' - '.$this->laDatos[0]['CNOMCOT'], 0, 2, 'L');
      $loPdf->Ln($lnHeight);
      if ($lcTipRep == 'P' || $this->laDatos[0]['CESTADO'] != 'F') {
         $loPdf->SetFont('Courier', 'B', 7);
         $loPdf->Cell($lnWidth, $lnHeight - 0.32, ($lcTipRep == 'P')?'REPORTE PARA PROVEEDORES' : 'REPORTE INTERNO', 0, 2, 'C');
      }
      if (isset($this->laFirmas[0])) {
         $loPdf->Image("./img/{$this->laFirmas[0]['CNIVEL']}_{$this->laFirmas[0]['CCODUSU']}.png", $this->laFirmas[0]['NPOSFIX'], $this->laFirmas[0]['NPOSFIY'], 6.5, 0, 'PNG');
      }
      if (isset($this->laFirmas[1])) {
         $loPdf->Image("./img/{$this->laFirmas[1]['CNIVEL']}_{$this->laFirmas[1]['CCODUSU']}.png", $this->laFirmas[1]['NPOSFIX'], $this->laFirmas[1]['NPOSFIY'], 6.5, 0, 'PNG');
      }
      $loPdf->SetFont('Courier', 'B', 9);
      $loPdf->SetY($loPdf->GetPageHeight() - 2.5);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, '_______________________________________', 0, 2, 'C');
      if (isset($this->laFirmas[0])) {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode("{$this->laFirmas[0]['CGRAACA']} {$this->laFirmas[0]['CNOMBRE']}"), 0, 2, 'C');
      } else {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('ECON. EDGAR JULIO CONTRERAS GONZALES'), 0, 2, 'C');
      }
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('DIRECTOR DE LOGÍSTICA Y CONTRATACIONES'), 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->SetXY($loPdf->GetX() + ($loPdf->GetPageWidth() - 2.6)/2, $loPdf->GetPageHeight() - 2.5);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, '_______________________________________', 0, 2, 'C');
      if (isset($this->laFirmas[1])) {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode("{$this->laFirmas[1]['CGRAACA']} {$this->laFirmas[1]['CNOMBRE']}"), 0, 2, 'C');
      } else {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('DR. CESAR CACERES ZARATE'), 0, 2, 'C');
      }
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('VICERRECTOR ADMINISTRATIVO'), 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

      public function omRepCaracteristicasPDFAF() {
      // print_r("REPORTE");
      // print_r($this->paDatos);
      // die();
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcCenRes = '*';
      $lcCodEmp = '*';
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode('-'.fxString($laFila['CCENRES'],5).' '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES']; 
            $lcCodEmp = '*';
         }
         if($laFila['CCODEMP'] != $lcCodEmp){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode(' *'.fxString($laFila['CCODEMP'],4).' '.fxStringFixed($laFila['CNOMEMP'],50));
            $laDatos[] = $lcLinea;
            $lcCodEmp = $laFila['CCODEMP']; 
         } 
         $lcLinea = utf8_decode(fxNumber($i,5,0).' '.fxString($laFila['CCODIGO'],16).' '.fxStringFixed($laFila['CDESCRI'],45).' '.$laFila['DFECALT'].'  '.$laFila['CSITUAC'].'   '.fxString($laFila['CDATOS']['CMARCA'],15).' '.fxString($laFila['CDATOS']['CMODELO'],15).' '.fxString($laFila['CDATOS']['CNROSER'],15).' '.fxNumber($laFila['NMONTO'],12,2));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                              REPORTE ACTIVOS FIJOS POR CARACTERISTICAS                                PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                       '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ2070', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(18, 2, 'Centro de Costo: ', 0);
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', '', 9);
            $loPdf->Cell(0, 2, utf8_decode(fxString('',18).$this->paData['CCENCOS'].' - '.$this->paData['CDESCOS']) , 0);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '        CODIGO           DESCRIPCION                                    FECHA   SIT   MARCA           MODELO          SERIE             MONTO', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 50) ? true : false;
      }
      $loPdf->Ln(3);
      $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
      $loPdf->Ln(20);
      $this->laData =  strtoupper($this->laData);
      if($this->laData === 'R'){
         $loPdf->Ln(8);
         $loPdf->Cell(0, 2, '                                        ______________________                  _____________________', 0);
         $loPdf->Ln(4);
         $loPdf->Cell(0, 2, '                                           ENTREGUE/CODIGO                         RECIBI/CODIGO', 0);
         $loPdf->Ln(2);
      }else{
         $loPdf->Ln(1);
         $loPdf->Cell(0, 2, '                                        ______________________                  ______________________', 0);
         $loPdf->Ln(4);
         $loPdf->Cell(0, 2, '                                         INVENTARIADOR/CODIGO                    RESPONSABLE/CODIGO', 0);
         $loPdf->Ln(2);
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omRepCaracteristicasEXCELAF() {
      //print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2070', 'R');
      $loXls->sendXls(0, 'H', 1, date("Y-m-d"));
      $i = 4;
      $lcCenRes = '*';
      $lcCodEmp = '*';
      foreach ($this->paDatos as $laFila) {
         if($laFila['CCENRES'] != $lcCenRes){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 10, 'general','center');
            $loXls->sendXls(0, 'A', $i, $laFila['CCENRES']);
            $loXls->sendXls(0, 'B', $i, $laFila['CDESRES']);
            $lcCenRes = $laFila['CCENRES'];
            $i++;
         }
         if($laFila['CCODEMP'] != $lcCodEmp){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 10, 'general','center');
            $loXls->sendXls(0, 'B', $i, $laFila['CCODEMP'].' - '.$laFila['CNOMEMP']);
            $lcCodEmp = $laFila['CCODEMP'];
            $i++;
         }
         $loXls->sendXls(0, 'B', $i, $laFila['CCODIGO']);
         $loXls->sendXls(0, 'C', $i, $laFila['CDESCRI']);
         $loXls->sendXls(0, 'D', $i, $laFila['DFECALT']);
         //$loXls->sendXls(0, 'J', $i, $laFila['NMONCAL']);
         $loXls->sendXls(0, 'E', $i, $laFila['NMONTO']);
         $loXls->sendXls(0, 'F', $i, $laFila['CSITUAC']);
         $loXls->sendXls(0, 'G', $i, $laFila['CDATOS']['CMARCA']);
         $loXls->sendXls(0, 'H', $i, $laFila['CDATOS']['CMODELO']);
         $loXls->sendXls(0, 'I', $i, $laFila['CDATOS']['CNROSER']);
         $loXls->sendXls(0, 'J', $i, $laFila['NMONCAL']);
         $i++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }


   public function omRepValoresPDFAF() {
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcCenRes = '*';
      $lcCodEmp = '*';
      $i = 1;
      $lnMoncal = 0;
      $lnDeprec = 0;
      $lnDepPer = 0;
      $lnRetiro = 0;
      $lnSumDep = 0;
      $lnValNet = 0;
      $lnTotMoncal = 0;
      $lnTotDeprec = 0;
      $lnTotDepPer = 0;
      $lnTotRetiro = 0;
      $lnTotSumDep = 0;
      $lnTotValNet = 0;
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] !== $lcCenRes){
            if ($i != 1) {
               $lcLinea = utf8_decode(fxString(' ',89).fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12));
               $laDatos[] = $lcLinea;
               $lcLinea = utf8_decode(fxString(' ',89).fxNumber($lnTotMoncal, 12,2).' '.fxNumber($lnTotDeprec, 12,2).' '.fxNumber($lnTotDepPer, 12,2).' '.fxNumber($lnTotRetiro, 12,2).' '.fxNumber($lnTotSumDep, 12,2).' '.fxNumber($lnTotValNet, 12,2));
               $laDatos[] = $lcLinea;
               $i = 1;
               $lnTotMoncal = 0;
               $lnTotDeprec = 0;
               $lnTotDepPer = 0;
               $lnTotRetiro = 0;
               $lnTotSumDep = 0;
               $lnTotValNet = 0;
            }
            
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode('-'.fxString($laFila['CCENRES'],5).' '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES'];
            $lcCodEmp = '*';
         } 
         if($laFila['CCODEMP'] !== $lcCodEmp){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode(' *'.fxString($laFila['CCODEMP'],4).' '.fxStringFixed($laFila['CNOMEMP'],50));
            $laDatos[] = $lcLinea;
            $lcCodEmp = $laFila['CCODEMP']; 
         } 
         $lcLinea = utf8_decode(fxNumber($i,3,0).' '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],55).' '.$laFila['CSITUAC'].'  '.$laFila['DFECALT'].'  '.fxNumber($laFila['NMONCAL'],12,2).' '.fxNumber($laFila['NDEPREC'],12,2).' '.fxNumber($laFila['NDEPPER'],12,2).' '.fxNumber($laFila['NRETIRO'],12,2).' '.fxNumber($laFila['NSUMDEP'],12,2).' '.fxNumber($laFila['NVALNET'],12,2)); 
         $laDatos[] = $lcLinea;
         $i++;
         $lnTotMoncal = $lnTotMoncal + $laFila['NMONCAL'];
         $lnTotDeprec = $lnTotDeprec + $laFila['NDEPREC'];
         $lnTotDepPer = $lnTotDepPer + $laFila['NDEPPER'];
         $lnTotRetiro = $lnTotRetiro + $laFila['NRETIRO'];
         $lnTotSumDep = $lnTotSumDep + $laFila['NSUMDEP'];
         $lnTotValNet = $lnTotValNet + $laFila['NVALNET'];
         
         $lnMoncal = $lnMoncal + $laFila['NMONCAL'];
         $lnDeprec = $lnDeprec + $laFila['NDEPREC'];
         $lnDepPer = $lnDepPer + $laFila['NDEPPER'];
         $lnRetiro = $lnRetiro + $laFila['NRETIRO'];
         $lnSumDep = $lnSumDep + $laFila['NSUMDEP'];
         $lnValNet = $lnValNet + $laFila['NVALNET'];
      }
      $lcLinea = utf8_decode(fxString(' ',89).fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12));
      $laDatos[] = $lcLinea;
      $lcLinea = utf8_decode(fxString(' ',89).fxNumber($lnTotMoncal, 12,2).' '.fxNumber($lnTotDeprec, 12,2).' '.fxNumber($lnTotDepPer, 12,2).' '.fxNumber($lnTotRetiro, 12,2).' '.fxNumber($lnTotSumDep, 12,2).' '.fxNumber($lnTotValNet, 12,2));
      $laDatos[] = $lcLinea;

      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                  REPORTE ACTIVOS FIJOS POR VALORES                                    PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                       '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ2080', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(18, 2, 'Centro de Costo: ', 0);
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', '', 9);
            $loPdf->Cell(0, 2, utf8_decode(fxString('',18).$this->paData['CCENCOS'].' - '.$this->paData['CDESCOS']) , 0);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 8);
            $loPdf->Cell(0, 2, '_______________________________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '    CODIGO        DESCRIPCION                                            SIT. FECHA       MONTO ACT.  DEPR.INICIAL  DEP.PERIODO   DEP.RETIRO   DEP.ACTUAL   VALOR NETO', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_______________________________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 8);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 50) ? true : false;
      }
      $loPdf->SetFont('Courier', 'B', 8);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, '                                                                                         ____________ ____________ ____________ ____________ ____________ ____________', 0);
      $loPdf->Ln(5);  
      $loPdf->Cell(0, 2, '                                                                                  Total: '.fxNumber($lnMoncal,12,2).' '.fxNumber($lnDeprec,12,2).' '.fxNumber($lnDepPer,12,2).' '.fxNumber($lnRetiro,12,2).' '.fxNumber($lnSumDep,12,2).' '.fxNumber($lnValNet,12,2), 0);
      $loPdf->Ln(4);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omRepValoresEXCELAF() {
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2080', 'R');
      $loXls->sendXls(0, 'G', 1, date("Y-m-d"));
      $i = 4;
      $lcCenRes = '*';
      $lcCodEmp = '*';
      foreach ($this->paDatos as $laFila) {
         if($laFila['CCENRES'] != $lcCenRes){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 10, 'general','center');
            $loXls->sendXls(0, 'A', $i, $laFila['CCENRES']);
            $loXls->sendXls(0, 'B', $i, $laFila['CDESRES']);
            $lcCenRes = $laFila['CCENRES'];
            $i++;
         }
         if($laFila['CCODEMP'] != $lcCodEmp){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 10, 'general','center');
            $loXls->sendXls(0, 'B', $i, $laFila['CCODEMP'].' - '.$laFila['CNOMEMP']);
            $lcCodEmp = $laFila['CCODEMP'];
            $i++;
         }
         $loXls->sendXls(0, 'B', $i, $laFila['CCODIGO']);
         $loXls->sendXls(0, 'C', $i, $laFila['CDESCRI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CSITUAC']);
         $loXls->sendXls(0, 'E', $i, $laFila['DFECALT']);
         $loXls->sendXls(0, 'F', $i, $laFila['NMONCAL']);
         $loXls->sendXls(0, 'G', $i, $laFila['NDEPREC']);
         $loXls->sendXls(0, 'H', $i, $laFila['NDEPPER']);
         $loXls->sendXls(0, 'I', $i, $laFila['NRETIRO']);
         $loXls->sendXls(0, 'J', $i, $laFila['NSUMDEP']);
         $loXls->sendXls(0, 'K', $i, $laFila['NVALNET']);
         $i++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   public function mxRepAnalisisPDF() {
      //print_r($this->paData);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      $j = 1;
      $lcTipAfj = '*';
      // sub totales
      $lnValini = 0;
      $lnAdiAdq = 0;
      $lnRetIni = 0;
      $lnValFin = 0;
      $lnDepIni = 0;
      $lnDepAdi = 0;
      $lnDepPer = 0;
      $lnDepRet = 0;
      $lnDepAcu = 0;
      $lnValNet = 0;
      //totales
      $lnToValini = 0;
      $lnToAdiAdq = 0;
      $lnToRetIni = 0;
      $lnToValFin = 0;
      $lnToDepIni = 0;
      $lnToDepAdi = 0;
      $lnToDepPer = 0;
      $lnToDepRet = 0;
      $lnToDepAcu = 0;
      $lnToValNet = 0;
      
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CTIPAFJ'] != $lcTipAfj){
            if ($i != 1) {
               $lcLinea = utf8_decode(fxString(' ',81).fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).'    '.fxString('___________', 12).' '.fxString('___________', 12).' '.fxString('___________', 12).' '.fxString('___________', 12).' '.fxString('___________', 12).'   '.fxString('___________', 12));
               $laDatos[] = $lcLinea;
               $lcLinea = utf8_decode(fxString(' ',80).fxNumber($lnValini, 12,2).' '.fxNumber($lnAdiAdq, 12,2).' '.fxNumber($lnRetIni, 12,2).' '.fxNumber($lnValFin, 12,2).'    '.fxNumber($lnDepIni, 12,2).' '.fxNumber($lnDepAdi, 12,2).' '.fxNumber($lnDepPer, 12,2).' '.fxNumber($lnDepRet, 12,2).' '.fxNumber($lnDepAcu, 12,2).'   '.fxNumber($lnValNet, 12,2));
               $laDatos[] = $lcLinea;
               $lcLinea = utf8_decode('_______________________________________________________________________________________________________________________________________________________________________________________________________________________');
               $laDatos[] = $lcLinea;
               $i = 1;
               $lnValini = 0;
               $lnAdiAdq = 0;
               $lnRetIni = 0;
               $lnValFin = 0;
               $lnDepIni = 0;
               $lnDepAdi = 0;
               $lnDepPer = 0;
               $lnDepRet = 0;
               $lnDepAcu = 0;
               $lnValNet = 0;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode('-'.fxString($laFila['CTIPAFJ'],5).' '.fxStringFixed($laFila['CDESCLA'],50));
            $laDatos[] = $lcLinea;
            $lcTipAfj = $laFila['CTIPAFJ'];
             
         } 
         $lcLinea = utf8_decode('  '.$laFila['CCODIGO'].' '.fxString($laFila['CDATOS']['CCANTID'],4).' '.fxStringFixed($laFila['CDESCRI'],35).'  '.$laFila['CSITUAC'].'  '.$laFila['DFECALT'].'  '.fxNumber($laFila['NFACDEP'],6,3).'  '.fxNumber($laFila['NMONCAL'],12,2).' '.fxNumber($laFila['NFALT1'],12,2).' '.fxNumber($laFila['NFALTA'],12,2).' '.fxNumber(($laFila['NMONCAL']+$laFila['NFALT1']),12,2).' | '.fxNumber($laFila['NDEPINI'],12,2).' '.fxNumber($laFila['NFALT1'],12,2).' '.fxNumber($laFila['NDEPACU'],12,2).' '.fxNumber($laFila['NRETIRO'],12,2).' '.fxNumber($laFila['NDEPTOT'],12,2).' | '.fxNumber(($laFila['NMONCAL']-$laFila['NDEPTOT']),12,2)); 
         $laDatos[] = $lcLinea;
         $i++;
         $j++;
         $lnValini = $lnValini + $laFila['NMONCAL'];
         $lnAdiAdq = $lnAdiAdq + $laFila['NFALT1'];
         $lnRetIni = $lnRetIni + $laFila['NFALTA'];
         $lnValFin = $lnValFin + $laFila['NMONCAL'];
         $lnDepIni = $lnDepIni + $laFila['NDEPINI'];
         $lnDepAdi = $lnDepAdi + $laFila['NFALT1'];
         $lnDepPer = $lnDepPer + $laFila['NDEPACU'];
         $lnDepRet = $lnDepRet + $laFila['NRETIRO'];
         $lnDepAcu = $lnDepAcu + $laFila['NDEPTOT'];
         $lnValNet = $lnValNet + ($laFila['NMONCAL']-$laFila['NDEPTOT']);
         //totales
         $lnToValini = $lnToValini + $laFila['NMONCAL'];
         $lnToAdiAdq = $lnToAdiAdq + $laFila['NFALT1'];
         $lnToRetIni = $lnToRetIni + $laFila['NFALTA'];
         $lnToValFin = $lnToValFin + $laFila['NMONCAL'];
         $lnToDepIni = $lnToDepIni + $laFila['NDEPINI'];
         $lnToDepAdi = $lnToDepAdi + $laFila['NFALT1'];
         $lnToDepPer = $lnToDepPer + $laFila['NDEPACU'];
         $lnToDepRet = $lnToDepRet + $laFila['NRETIRO'];
         $lnToDepAcu = $lnToDepAcu + $laFila['NDEPTOT'];
         $lnToValNet = $lnToValNet + ($laFila['NMONCAL']-$laFila['NDEPTOT']);
      }
      $lcLinea = utf8_decode(fxString(' ',81).fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).' '.fxString('____________', 12).'    '.fxString('___________', 12).' '.fxString('___________', 12).' '.fxString('___________', 12).' '.fxString('___________', 12).' '.fxString('___________', 12).'   '.fxString('___________', 12));
      $laDatos[] = $lcLinea;
      $lcLinea = utf8_decode(fxString(' ',80).fxNumber($lnValini, 12,2).' '.fxNumber($lnAdiAdq, 12,2).' '.fxNumber($lnRetIni, 12,2).' '.fxNumber($lnValFin, 12,2).'    '.fxNumber($lnDepIni, 12,2).' '.fxNumber($lnDepAdi, 12,2).' '.fxNumber($lnDepPer, 12,2).' '.fxNumber($lnDepRet, 12,2).' '.fxNumber($lnDepAcu, 12,2).'   '.fxNumber($lnValNet, 12,2));
      $laDatos[] = $lcLinea;

      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,utf8_decode('                                                  ANÁLISIS DE ACTIVOS FIJOS                                             PAG.'.fxNumber($lnPag, 6, 0)));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3, utf8_decode('                                               FECHA DEPRECIACIÓN: '.$this->paData['CDEPREC'].'                                           '.$ldDate), 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ2100', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(18, 2, 'Centro de Costo: ', 0);
            $loPdf->Ln(0);
            $loPdf->SetFont('Courier', '', 9);
            $loPdf->Cell(0, 2, utf8_decode(fxString('',18).$this->paData['CCENCOS'].' - '.$this->paData['CDESCOS']) , 0);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 6);
            $loPdf->Cell(0, 2, '______________________________________________________________________________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '    CODIGO     CANT.   DESCRIPCION                       SIT  FEC.ADQ.    FAC.     VAL.INI.    ADIC./ADQ.     RETIROS    VAL.FINAL  |   DEP.INI.    ADICIONES   DEP.PERIOD.     RETIROS     DEP.ACU.   |   VAL.NETO ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '______________________________________________________________________________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 6);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 50) ? true : false;
      }
      $loPdf->SetFont('Courier', 'B', 6);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, '                                                                                 ____________ ____________ ____________ ____________   ____________ ____________ ____________ ____________ ____________   ____________', 0);
      $loPdf->Ln(5);
      $loPdf->Cell(0, 2, fxNumber($j,3,0).' activos                                                            Total:   '.fxNumber($lnToValini,12,2).' '.fxNumber($lnToAdiAdq,12,2).' '.fxNumber($lnToRetIni,12,2).'  '.fxNumber($lnToValFin,12,2).'   '.fxNumber($lnToDepIni,12,2).' '.fxNumber($lnToDepAdi,12,2).' '.fxNumber($lnToDepPer,12,2).' '.fxNumber($lnToDepRet,12,2).' '.fxNumber($lnToDepAcu,12,2).'   '.fxNumber($lnToValNet,12,2), 0);
      $loPdf->Ln(4);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function mxRepAnalisisExcel(){
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2100', 'R');
      $loXls->sendXls(0, 'N', 1, date("Y-m-d"));
      $loXls->cellStyle('A2', 'B2', true, false, 14, 'center','center');
      $loXls->sendXls(0, 'B', 2, 'FECHA DEPRECIACIÓN: '.$this->paData['CDEPREC']);
      $i = 4;
      $lcTipAfj = '*';
      // sub totales
      $lnValini = 0.00;
      $lnAdiAdq = 0.00;
      $lnRetIni = 0.00;
      $lnValFin = 0.00;
      $lnDepIni = 0.00;
      $lnDepAdi = 0.00;
      $lnDepPer = 0.00;
      $lnDepRet = 0.00;
      $lnDepAcu = 0.00;
      $lnValNet = 0.00;
      //totales
      $lnToValini = 0.00;
      $lnToAdiAdq = 0.00;
      $lnToRetIni = 0.00;
      $lnToValFin = 0.00;
      $lnToDepIni = 0.00;
      $lnToDepAdi = 0.00;
      $lnToDepPer = 0.00;
      $lnToDepRet = 0.00;
      $lnToDepAcu = 0.00;
      $lnToValNet = 0.00;
      $j = 4;
      foreach ($this->paDatos as $laFila) {
         if($laFila['CTIPAFJ'] != $lcTipAfj){
            if ($j != 1) {
               $loXls->cellStyle('A'.$i, 'P'.$i, true, false, 11, 'general','center');
               $loXls->sendXls(0, 'G', $i, $lnValini);
               $loXls->sendXls(0, 'H', $i, $lnAdiAdq);
               $loXls->sendXls(0, 'I', $i, $lnRetIni);
               $loXls->sendXls(0, 'J', $i, $lnValFin);
               $loXls->sendXls(0, 'K', $i, $lnDepIni);
               $loXls->sendXls(0, 'L', $i, $lnDepAdi);
               $loXls->sendXls(0, 'M', $i, $lnDepPer);
               $loXls->sendXls(0, 'N', $i, $lnDepRet);
               $loXls->sendXls(0, 'O', $i, $lnDepAcu);
               $loXls->sendXls(0, 'P', $i, $lnValNet);
               $j = 1;
               $lnValini = 0;
               $lnAdiAdq = 0;
               $lnRetIni = 0;
               $lnValFin = 0;
               $lnDepIni = 0;
               $lnDepAdi = 0;
               $lnDepPer = 0;
               $lnDepRet = 0;
               $lnDepAcu = 0;
               $lnValNet = 0;
               $i++;
            }
            $loXls->cellStyle('A'.$i, 'J'.$i, true, false, 11, 'general','center');
            $loXls->sendXls(0, 'A', $i, $laFila['CTIPAFJ'].' - '.$laFila['CDESCLA']);
            $lcTipAfj = $laFila['CTIPAFJ'];
            $i++;
         }
         $loXls->sendXls(0, 'A', $i, $laFila['CCODIGO']);
         $loXls->sendXls(0, 'B', $i, $laFila['CDATOS']['CCANTID']);
         $loXls->sendXls(0, 'C', $i, $laFila['CDESCRI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CSITUAC']);
         $loXls->sendXls(0, 'E', $i, $laFila['DFECALT']);
         $loXls->sendXls(0, 'F', $i, $laFila['NFACDEP']);
         $loXls->sendXls(0, 'G', $i, $laFila['NMONCAL']);
         $loXls->sendXls(0, 'H', $i, '0.00');
         $loXls->sendXls(0, 'I', $i, '0.00');
         $loXls->sendXls(0, 'J', $i, $laFila['NMONCAL']+$laFila['NFALT1']);
         $loXls->sendXls(0, 'K', $i, $laFila['NDEPINI']);
         $loXls->sendXls(0, 'L', $i, '0.00');
         $loXls->sendXls(0, 'M', $i, $laFila['NDEPACU']);
         $loXls->sendXls(0, 'N', $i, $laFila['NRETIRO']);
         $loXls->sendXls(0, 'O', $i, $laFila['NDEPTOT']);
         $loXls->sendXls(0, 'P', $i, $laFila['NMONCAL']-$laFila['NDEPTOT']);
         $i++; 
         $j++;
         $lnValini = $lnValini + $laFila['NMONCAL'];
         $lnAdiAdq = $lnAdiAdq + 0.00;
         $lnRetIni = $lnRetIni + $laFila['NFALTA'];
         $lnValFin = $lnValFin + $laFila['NMONCAL'];
         $lnDepIni = $lnDepIni + $laFila['NDEPINI'];
         $lnDepAdi = $lnDepAdi + 0.00;
         $lnDepPer = $lnDepPer + $laFila['NDEPACU'];
         $lnDepRet = $lnDepRet + $laFila['NRETIRO'];
         $lnDepAcu = $lnDepAcu + $laFila['NDEPTOT'];
         $lnValNet = $lnValNet + ($laFila['NMONCAL']-$laFila['NDEPTOT']);
         //totales
         $lnToValini = $lnToValini + $laFila['NMONCAL'];
         $lnToAdiAdq = $lnToAdiAdq + 0.00;
         $lnToRetIni = $lnToRetIni + $laFila['NFALTA'];
         $lnToValFin = $lnToValFin + $laFila['NMONCAL'];
         $lnToDepIni = $lnToDepIni + $laFila['NDEPINI'];
         $lnToDepAdi = $lnToDepAdi + 0.00;
         $lnToDepPer = $lnToDepPer + $laFila['NDEPACU'];
         $lnToDepRet = $lnToDepRet + $laFila['NRETIRO'];
         $lnToDepAcu = $lnToDepAcu + $laFila['NDEPTOT'];
         $lnToValNet = $lnToValNet + ($laFila['NMONCAL']-$laFila['NDEPTOT']);
      }
      //SUB TOTAL  ULTIMO
      $loXls->cellStyle('A'.$i, 'P'.$i, true, false, 11, 'general','center');
      $loXls->sendXls(0, 'G', $i, $lnValini);
      $loXls->sendXls(0, 'H', $i, $lnAdiAdq);
      $loXls->sendXls(0, 'I', $i, $lnRetIni);
      $loXls->sendXls(0, 'J', $i, $lnValFin);
      $loXls->sendXls(0, 'J', $i, $lnDepIni);
      $loXls->sendXls(0, 'L', $i, $lnDepAdi);
      $loXls->sendXls(0, 'M', $i, $lnDepPer);
      $loXls->sendXls(0, 'N', $i, $lnDepRet);
      $loXls->sendXls(0, 'O', $i, $lnDepAcu);
      $loXls->sendXls(0, 'P', $i, $lnValNet);
      $i = $i+2;;
      // TOTAL FINAL
      $loXls->cellStyle('A'.$i, 'P'.$i, true, false, 11, 'general','center');
      $loXls->sendXls(0, 'G', $i, $lnToValini);
      $loXls->sendXls(0, 'H', $i, $lnToAdiAdq);
      $loXls->sendXls(0, 'I', $i, $lnToRetIni);
      $loXls->sendXls(0, 'J', $i, $lnToValFin);
      $loXls->sendXls(0, 'K', $i, $lnToDepIni);
      $loXls->sendXls(0, 'L', $i, $lnToDepAdi);
      $loXls->sendXls(0, 'M', $i, $lnToDepPer);
      $loXls->sendXls(0, 'N', $i, $lnToDepRet);
      $loXls->sendXls(0, 'O', $i, $lnToDepAcu);
      $loXls->sendXls(0, 'P', $i, $lnToValNet);
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   

   public function omRepClasesyTiposPDF() {
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcClase = '*';
      $i = 0;
      foreach ($this->paDatos as $laFila) {

         if($laFila['CCODIDO'] != $lcClase){
            $lcLinea = utf8_decode('* '.$laFila['CCODIDO'].' - '.fxStringFixed($laFila['CDESCLA'],50)); 
            $laDatos[] = $lcLinea;
            $lcClase = $laFila['CCODIDO'];
         }  
         $lcLinea = utf8_decode('    '.$laFila['CTIPAFJ'].' '.fxStringFixed($laFila['CDESCRI'],50).'  '.fxNumber($laFila['NFACDEP'],8,4).'%  '.fxString($laFila['CCNTACT'],7).'  '.fxString($laFila['CCNTDEP'],7).'  '.fxString($laFila['CCNTCTR'],7).'  '.fxString($laFila['CCNTBAJ'],7)); 
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                             REPORTE ACTIVOS FIJOS POR CLASES                  PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                               '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ2090', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 8);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '   CODIGO     DESCRIPCION                                       FAC.DEP  CNT.ACT. CNT.DEP. CNT.CTR. CNT.BAJA', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 8);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 73) ? true : false;
      }
      $loPdf->Ln(3);
      $loPdf->Cell(0, 2, '___________________________________________________________________________________________________', 0);
      $loPdf->Ln(5);
      $loPdf->Cell(0, 2, $i.' Registros', 0);
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omReporteClasesTiposEXCEL() {
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2090', 'R');
      $loXls->sendXls(0, 'H', 1, date("Y-m-d"));
      $i = 4;
      $lcClase = '*';
      foreach ($this->paDatos as $laFila) {
         if($laFila['CCODIDO'] != $lcClase){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 12, 'general','center');
            $loXls->sendXls(0, 'A', $i, $laFila['CCODIDO']);
            $loXls->sendXls(0, 'B', $i, $laFila['CDESCLA']);
            $lcClase = $laFila['CCODIDO'];
            $i++;
         }
         $loXls->sendXls(0, 'B', $i, $laFila['CTIPAFJ']);
         $loXls->sendXls(0, 'C', $i, $laFila['CDESCRI']);
         $loXls->sendXls(0, 'D', $i, $laFila['NFACDEP']);
         $loXls->sendXls(0, 'E', $i, $laFila['CCNTACT']);
         $loXls->sendXls(0, 'F', $i, $laFila['CCNTDEP']);
         $loXls->sendXls(0, 'G', $i, $laFila['CCNTCTR']);
         $loXls->sendXls(0, 'H', $i, $laFila['CCNTBAJ']);
         $i++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   function omPrintReporteDetalleCenRespBaj() {
      //print_r($this->paData);
      //print_r($this->paDatos[0]);
      $lcDocBaj = $this->paDatos[0]['MDATOS']['CDOCBAJ'];
      $ldFecbaj = $this->paDatos[0]['MDATOS']['DFECBAJ'];
      //print($lcDocBaj);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      $lnMonCal = 0; 
      $lnDeprec = 0; 
      $lnRetiro = 0;
      $lnValNet = 0;     
      foreach ($this->paDatos as $laFila) {  
         $lcLinea = utf8_decode(fxNumber($i,3,0).' '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],45).' '.$laFila['CSITUAC'].' '.$laFila['DFECALT'].'  '.fxNumber($laFila['NMONCAL'],12,2).' '.fxNumber($laFila['NSUMDEP'],12,2).' '.fxNumber($laFila['NRETIRO'],12,2).' '.fxNumber($laFila['NVALNET'],12,2)); 
         $laDatos[] = $lcLinea;
         $i++;
         $lnMonCal = $lnMonCal + $laFila['NMONCAL'];
         $lnDeprec = $lnDeprec + $laFila['NSUMDEP']; 
         $lnRetiro = $lnRetiro + $laFila['NRETIRO'];
         $lnValNet = $lnValNet + $laFila['NVALNET']; 
      }
      $lcLinea = utf8_decode(fxString(' ',78).'____________ ____________ ____________ ____________');
      $laDatos[] = $lcLinea;
      $lcLinea = utf8_decode(fxString(' ',78).fxNumber($lnMonCal,12,2).' '.fxNumber($lnDeprec,12,2).' '.fxNumber($lnRetiro,12,2).' '.fxNumber($lnValNet,12,2));
      $laDatos[] = $lcLinea;
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                              REPORTE CENTRO DE RESPONSABILIDAD                PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1120', 0, 0, 'L');
            $loPdf->Ln(3);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 8);
            $loPdf->Cell(50, 1,'C. RESPONSABILIDAD : '.utf8_decode($this->paData['CCENRES'].' - '.fxStringFixed($this->paData['CDESCRI'],70)));
            $loPdf->Ln(3);
            if($ldFecbaj == '1900-01-01'){
               $loPdf->Cell(50, 1,' ');
            }else{
               $loPdf->Cell(50, 1,utf8_decode('DOCUMENTO DE BAJA  : '.fxStringFixed($lcDocBaj,55).'  FECHA DE BAJA: '.$ldFecbaj));
            }
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 8);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '  #   CODIGO        DESCRIPCION                        SIT FECHA        MONTO      DEPREC.    RETIRO   VALOR NETO', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 7);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 71) ? true : false;
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   function mxPrintRepContaDepre() {
      // print_r($this->paData);
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lnTotDeb = 0;
      $lnTotHab = 0;
      foreach ($this->paDatos['DATOS'] as $laFila) {  
         $lcLinea = utf8_decode(' '.fxString($laFila['CCTACNT'],8).' '.fxStringFixed($laFila['CDESCRI'],50).'    '.fxNumber($laFila['NDEBMN'],12,2).' '.fxNumber($laFila['NHABMN'],12,2 ));
         $laDatos[] = $lcLinea;
         $lnTotDeb = $lnTotDeb + $laFila['NDEBMN'];
         $lnTotHab = $lnTotHab + $laFila['NHABMN'];
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,utf8_decode('                              REPORTE CONTABILIDAD DEPRECIACIÓN               PAG.').fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ2030', 0, 0, 'L');
            $loPdf->Ln(3);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'Nro. Asiento : '.$this->paDatos['CNROASI'].' - '.$this->paDatos['CGLOSA'].'     Periodo : '.$this->paDatos['DFECCNT']);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '  # CUENTA        DESCRIPCION                                         DEBE        HABER', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 6;          
            $llTitulo = false;
         }
         $loPdf->Ln(1.5);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 72) ? true : false;
      }
      $loPdf->SetFont('Courier', 'B', 9);
      $loPdf->Ln(2);
      $loPdf->Cell(0, 2, fxString(' ',64).'____________ ____________', 0);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, fxString(' ',56).'Total : '.fxNumber($lnTotDeb,12,2).' '.fxNumber($lnTotHab,12,2 ), 0);
      $loPdf->Ln(4);
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   function mxPrintRepContaDepreciacionDetalle() {
      // print_r($this->paData);
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      $totalDep = 0;
      foreach ($this->paDatos as $laFila) {  
         $lcLinea = utf8_decode(fxString($laFila['CACTFIJ'],5).' '.fxString($laFila['CCODIGO'],13).' '.fxStringFixed($laFila['CDESCRI'],56).' '.fxString($laFila['DFECALT'],10).' '.fxNumber($laFila['NMONCAL'],14,2).' '.fxNumber($laFila['NDEPANIO'],14,2).' '.fxNumber($laFila['NDEPACU'],14,2).' '.fxNumber($laFila['NDEPREC'],12,2));
         $laDatos[] = $lcLinea;
         $i++;
         $totalDep = $totalDep + $laFila['NDEPREC'];
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,utf8_decode('                                              REPORTE CONTABILIDAD DEPRECIACIÓN DETALLE                               PAG.').fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                   '.$this->paData['CGLOSA'].'                                    '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ2030', 0, 0, 'L');
            $loPdf->Ln(3);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, ' ACT.    CODIGO          DESCRIPCION                                           FECHA           MONTO        DEP.INI.       DEP.ACU.      DEP.MES.', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 6;          
            $llTitulo = false;
         }
         $loPdf->Ln(1.5);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 48) ? true : false;
      }
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, '                                                                                                                                    ______________', 0);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, '                                                                                                                            TOTAL: '.fxNumber($totalDep,14,2), 0);
      $loPdf->Ln(2);
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }
   
   function mxPrintReporteActivoFijo() {
      // print_r($this->paData);
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcCenRes = '*';
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            $lcLinea = utf8_decode('* '.fxString($laFila['CCENRES'],5).' - '.fxString($laFila['CDESRES'],40));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES'];
         } 
         $lcLinea = utf8_decode('  '.fxString($laFila['CACTFIJ'],5).' '.fxString($laFila['CCODIGO'],13).' '.fxStringFixed($laFila['CDESCRI'],50).' '.fxString($laFila['DFECALT'],10).' '.fxNumber($laFila['NMONTO'],10,2));
         $laDatos[] = $lcLinea;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,utf8_decode('                                     LISTA DE ACTIVOS FIJOS                    PAG.').fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                               '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1090', 0, 0, 'L');
            $loPdf->Ln(3);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(5);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '   ACT.  CODIGO          DESCRIPCION                                       FECHA       MONTO ', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 6;          
            $llTitulo = false;
         }
         $loPdf->Ln(1.5);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 72) ? true : false;
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   function omPrintCodigosAF() {
      // print_r($this->paData);
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $loPdf = new FPDF('L', 'mm', array(50,25));
      $loPdf->SetMargins(0, 1);
      $loPdf->SetAutoPageBreak(false);
      foreach ($this->paDatos as $laFila) {
         $loPdf->AddPage();
         $lcFile = 'FILES/R'.rand().'.png';
         $lcClave = 'UNIVERSIDAD CATOLICA SANTA MARIA'.PHP_EOL.'INVENTARIO'.PHP_EOL.$this->paData['CCENRES'].' - '.$this->paData['CDESRES'].
                      PHP_EOL.$laFila['CACTFIJ'].' - '.$laFila['CCODIGO'].' - '.$laFila['CDESCRI'].PHP_EOL.$laFila['CCODEMP'].' - '.$laFila['CNOMEMP'];
         // print_r($lcClave);
         QRcode::png(utf8_encode($lcClave), $lcFile , QR_ECLEVEL_L, 4, 0, true);
         $loPdf->Ln(6);
         $loPdf->Image($lcFile, 28, 5, 19, 19);
         $loPdf->SetFont('Courier', 'B', 9);
         $loPdf->Cell(0.3, 0.5,utf8_decode('UCSM'));
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', 'B', 5);
         $loPdf->MultiCell(25, 1.5,fxStringFixed($this->paData['CCENRES'].'-'.$this->paData['CDESRES'],50), 0, 'L');
         $loPdf->Ln(1);
         $loPdf->SetFont('Courier', 'B', 9);
         $loPdf->Cell(10, 0.01,utf8_decode($laFila['CCODIGO']), 0, 0, 'L');
         $loPdf->Ln(1.5);
         $loPdf->SetFont('Courier', 'B', 5);
         if($laFila['CCODEMP'] != '0000'){
            $loPdf->Cell(30, 2,fxStringFixed($laFila['CCODEMP'].'-'.$laFila['CNOMEMP'],25), 0, 'L');
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', 'B', 5);
         $loPdf->MultiCell(25, 1.5,fxStringFixed($laFila['CDESCRI'],40), 0, 'L');
         $loPdf->Ln(0);
      }            
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   function omPrintCodigosOtros() {
      // print_r($this->paData);
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $loPdf = new FPDF('L', 'mm', array(50,25));
      $loPdf->SetMargins(0, 1);
      $loPdf->SetAutoPageBreak(false);
      foreach ($this->paDatos as $laFila) {
         // print_r($laFila);
         $loPdf->AddPage();
         $lcFile = 'FILES/R'.rand().'.png';
         $lcClave = 'UCSM - INVENTARIO'.PHP_EOL.$laFila['CCENRES'].' - '.$laFila['CDESRES'].
                      PHP_EOL.$laFila['CACTFIJ'].' - '.$laFila['CCODIGO'].' - '.$laFila['CDESCRI'].PHP_EOL.$laFila['CCODEMP'].' - '.$laFila['CNOMEMP'];
         // print_r($lcClave);
         QRcode::png(utf8_encode($lcClave), $lcFile , QR_ECLEVEL_L, 4, 0, true);
         $loPdf->Ln(6);
         $loPdf->Image($lcFile, 28, 5, 19, 19);
         $loPdf->SetFont('Courier', 'B', 9);
         $loPdf->Cell(0.3, 0.5,utf8_decode('UCSM'));
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', 'B', 5);
         $loPdf->MultiCell(26, 1.5,fxStringFixed($laFila['CCENRES'].'-'.$laFila['CDESRES'],50), 0, 'L');
         $loPdf->Ln(1);
         $loPdf->SetFont('Courier', 'B', 9);
         $loPdf->Cell(10, 0.01,utf8_decode($laFila['CCODIGO']), 0, 0, 'L');
         $loPdf->Ln(1.5);
         $loPdf->SetFont('Courier', 'B', 5);
         if($laFila['CCODEMP'] != '0000'){
            $loPdf->Cell(25, 2,fxStringFixed($laFila['CCODEMP'].'-'.$laFila['CNOMEMP'],25), 0, 'L');
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', 'B', 5);
         $loPdf->MultiCell(25, 1.5,fxStringFixed($laFila['CDESCRI'],40), 0, 'L');
         $loPdf->Ln(0);
      }            
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   } 

   public function omPrintReportePorCentroCostoExcel() {
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2060', 'R');
      $loXls->sendXls(0, 'D', 1, date("Y-m-d"));
      $i = 4;
      $lcCenCos = '*';
      foreach ($this->paDatos as $laFila) {
         if($laFila['CCENCOS'] !== $lcCenCos){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 10, 'general','center');
            $loXls->sendXls(0, 'A', $i, $laFila['CCENCOS']);
            $loXls->sendXls(0, 'B', $i, $laFila['CDESCOS']);
            $lcCenCos = $laFila['CCENCOS'];
            $i++;
         }
         $loXls->sendXls(0, 'B', $i, $laFila['CCENRES']);
         $loXls->sendXls(0, 'C', $i, $laFila['CDESRES']);
         $i++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   public function omPrintReportePorCentroCostoExcelTotal() {
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2060T', 'R');
      $loXls->sendXls(0, 'D', 1, date("Y-m-d"));
      $i = 4;
      $lcCenCos = '*';
      foreach ($this->paDatos as $laFila) {
         //print_r($laFila);
         if($laFila['CCENCOS'] !== $lcCenCos){
            $loXls->cellStyle('A'.$i, 'B'.$i, true, false, 10, 'general','center');
            $loXls->sendXls(0, 'A', $i, $laFila['CCENCOS']);
            $loXls->sendXls(0, 'B', $i, $laFila['CDESCOS']);
            $lcCenCos = $laFila['CCENCOS'];
            $i++;
         }
         $loXls->sendXls(0, 'B', $i, $laFila['CCENRES']);
         $loXls->sendXls(0, 'C', $i, $laFila['CDESRES']);
         $loXls->sendXls(0, 'D', $i, $laFila['CESTADO']);
         $i++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   public function omPrintReporteBusquedaActFijPDF() {
      // print_r($this->paDatos);
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $lcCenRes = '*';
      $lcCodEmp = '*';
      $i = 1;
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            if ($i != 1) {
               $i = 1;
               $lcLinea = utf8_decode(' ');
               $laDatos[] = $lcLinea;
            }
            $lcLinea = utf8_decode('-'.fxString($laFila['CCENRES'],5).' '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES']; 
            $lcCodEmp = '*';
         }
         if($laFila['CCODEMP'] != $lcCodEmp){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' *'.fxString($laFila['CCODEMP'],4).' '.fxStringFixed($laFila['CNOMEMP'],50));
            $laDatos[] = $lcLinea;            
            $lcCodEmp = $laFila['CCODEMP'];
         } 
         $lcLinea = utf8_decode(fxNumber($i,3,0).' '.$laFila['CCODIGO'].' '.fxStringFixed($laFila['CDESCRI'],50).' '.FxString($laFila['DFECALT'],10).'  '.$laFila['CSITUAC'].'  '.fxString($laFila['CMARCA'],15).' '.fxString($laFila['CMODELO'],15).' '.fxString($laFila['CNROSER'],15));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                                REPORTE DE BUSQUEDA ACTIVOS FIJOS                                      PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                       '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Ln(3);
            $loPdf->Cell(264, 3,'AFJ2010', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(1);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Ln(3);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '   CODIGO         DESCRIPCION                                        FECHA ADQ. SIT.   MARCA         MODELO          SERIE', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 4;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 40) ? true : false;
      }
      $loPdf->Ln(1);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }

   public function omPrintReporteBusquedaActFijExcel() {
      $lcFilRep = 'FILES/R'.rand().'.xlsx';
      $loXls = new CXls();
      $loXls->openXlsIO('Afj2010', 'R');
      $loXls->sendXls(0, 'P', 1, date("Y-m-d"));
      $i = 4;
      $lcCenCos = '*';
      foreach ($this->paDatos as $laFila) {
         $loXls->sendXls(0, 'A', $i, $laFila['CCODIGO']);
         $loXls->sendXls(0, 'B', $i, $laFila['CDESCRI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CSITUAC']);
         $loXls->sendXls(0, 'D', $i, $laFila['DFECALT']);
         $loXls->sendXls(0, 'E', $i, $laFila['NMONCAL']);
         $loXls->sendXls(0, 'F', $i, $laFila['NDEPREC']);
         $loXls->sendXls(0, 'G', $i, $laFila['NNETO']);
         $loXls->sendXls(0, 'H', $i, $laFila['CCENRES']);
         $loXls->sendXls(0, 'I', $i, $laFila['CDESRES']);
         $loXls->sendXls(0, 'J', $i, $laFila['CCODEMP']);
         $loXls->sendXls(0, 'K', $i, $laFila['CNOMEMP']);
         $loXls->sendXls(0, 'L', $i, $laFila['CMARCA']);
         $loXls->sendXls(0, 'M', $i, $laFila['CMODELO']);
         $loXls->sendXls(0, 'N', $i, $laFila['CNROSER']);
         $loXls->sendXls(0, 'O', $i, $laFila['CCOLOR']);
         $loXls->sendXls(0, 'P', $i, $laFila['CPLACA']);
         $i++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   public function omPrintReporteFinInventario() {
      // print_r($this->paData);
      $lnX = 0;
      $lcFilRep = 'Docs/Inventario/'.$this->paData['CYEAR'].'/I'.$this->paData['CCODEMP'].'.pdf';
      $lcCenRes = '*';
      $lcCodEmp = '*';
      foreach ($this->paDatos as $laFila) { 
         if($laFila['CCENRES'] != $lcCenRes){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode('-'.fxString($laFila['CCENRES'],5).' '.fxStringFixed($laFila['CDESRES'],50));
            $laDatos[] = $lcLinea;
            $lcCenRes = $laFila['CCENRES']; 
            $lcCodEmp = '*';
         }
         if($laFila['CCODEMP'] != $lcCodEmp){
            if ($i != 1) {
               $i = 1;
            }
            $lcLinea = utf8_decode(' ');
            $laDatos[] = $lcLinea;
            $lcLinea = utf8_decode(' *'.fxString($laFila['CCODEMP'],4).' '.fxStringFixed($laFila['CNOMEMP'],50));
            $laDatos[] = $lcLinea;
            $lcCodEmp = $laFila['CCODEMP']; 
         } 
         $lcLinea = utf8_decode(fxNumber($i,5,0).' '.fxString($laFila['CCODIGO'],16).' '.fxStringFixed($laFila['CDESCRI'],45).' '.$laFila['DFECALT'].'  '.$laFila['CSITUAC'].'   '.fxString($laFila['CDATOS']['CMARCA'],15).' '.fxString($laFila['CDATOS']['CMODELO'],15).' '.fxString($laFila['CDATOS']['CNROSER'],15).' '.fxNumber($laFila['NMONTO'],12,2));
         $laDatos[] = $lcLinea;
         $i++;
      }
      // Impresion
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4'); 
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(50, 1,'                                              REPORTE ACTIVOS FIJOS POR CARACTERISTICAS                                PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                       '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);  
            $loPdf->Cell(264, 3,'AFJ1140', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, '        CODIGO           DESCRIPCION                                    FECHA   SIT   MARCA           MODELO          SERIE             MONTO', 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 14;          
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 9);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 51) ? true : false;
      }
      $loPdf->Ln(2);
      $loPdf->Cell(0, 2, '_________________________________________________________________________________________________________________________________________________', 0);
      // $loPdf->Ln(4);
      $lcFile = 'FILES/Q'.rand().'.png';
      $lcClave = $this->paData[0]['CNRODNI'].' - '.$this->paData[0]['CNOMBRE'].PHP_EOL.' Inventario'.$this->paData['CYEAR'];
      QRcode::png(utf8_encode($lcClave), $lcFile , QR_ECLEVEL_L, 4, 0, true);
      //2da firma
      $lcFile_ = 'FILES/Q'.rand().'.png';
      $lcClave_ = $this->paData[1]['CNRODNI'].' - '.$this->paData[1]['CNOMBRE'].PHP_EOL.' Inventario'.$this->paData['CYEAR'];
      QRcode::png(utf8_encode($lcClave_), $lcFile_ , QR_ECLEVEL_L, 4, 0, true);
      $lnX = ($lnRow*4)-3;
      $loPdf->Image($lcFile,100,$lnX,20);
      $loPdf->Image($lcFile_,175,$lnX,20);
      $loPdf->Ln(5);
      $loPdf->Cell(0, 2, $i.' item', 0);
      $loPdf->Ln(33);
      $loPdf->Cell(0, 2, '                                        ______________________                  ______________________', 0);
      $loPdf->Ln(4);
      $loPdf->Cell(0, 2, '                                            RESPONSABLE                              INVENTARIADOR', 0);
      $loPdf->Ln(2);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paDato = ['CREPORT'=>$lcFilRep];
      // print_r($this->paDato);
      return true;
   }
} 
?>
