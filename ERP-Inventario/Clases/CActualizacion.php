<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

# ----------------------------------------------------------------------
# Clase para gestionar los pagos de congresos
# ----------------------------------------------------------------------
class CActualizacion extends CBase {
   public $paData, $paDatos, $pcFile;
   protected $laData, $laDatos;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->laData = $this->laDatos = null;
		$this->paDatos = [];
		$this->pcFile = 'FILES/R' . rand() . '.pdf';
   }

   // public function mxValParamUsuario($p_oSql, $p_cModulo = '000') {
   //    $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCencos = 'UNI' AND cCodUsu = '{$this->paData['CUSUCOD']}'";
   //    $RS = $p_oSql->omExec($lcSql);
   //    $laFila = $p_oSql->fetch($RS);
   //    if (!isset($laFila[0]) or empty($laFila[0])) {
   //       ;
   //    } elseif ($laFila[0] == 'A') {
   //       return true;
   //    }
   //    $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '$p_cModulo'";
   //    $RS = $p_oSql->omExec($lcSql);
   //    $laFila = $p_oSql->fetch($RS);
   //    if (!isset($laFila[0]) or empty($laFila[0])) {
   //       $this->pcError = "USUARIO NO TIENE PERMISO PARA OPCION";
   //       return False;
   //    } elseif ($laFila[0] != 'A') {
   //       $this->pcError = "USUARIO NO TIENE PERMISO ACTIVO PARA OPCION";
   //       return False;
   //    }
   //    return True;
   // }
   
   protected function mxValParam() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      }  elseif (!isset($this->paData['CNRODNI']) || !preg_match('/^[0-9]{8}$/', $this->paData['CNRODNI'])) {
         $this->pcError = "DNI DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      // elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
      //    $this->pcError = "CENTRO DE COSTO NO DEFINIDO O INVÁLIDO";
      //    return false;
      // } 
      return true;
   }

   protected function mxValParamInitCargarXLS($p_oSql) {
      $lcSql = "SELECT B.cNombre FROM S01TUSU A 
                INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni
                WHERE A.cNroDni = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (count($laTmp) == 0) {
         $this->pcError = 'DNI DE TRABAJADOR NO EXISTE';
         return false;
      }
      $this->paData['CNOMBRE'] = str_replace('/', ' ',$laTmp[0]);
      return true;
   }

   # --------------------------------------------------------------------------
   # Init Parqueo
   # --------------------------------------------------------------------------
   public function omTotalInscripciones2023() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      // Conexion con UCSMINS
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTotalInscripciones2023($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxTotalInscripciones2023($p_oSql) { 
      $laDatos = [];
      $lcSql = "SELECT * FROM (SELECT C.cIdCate, C.cDescri, C.nMonto, A.nCantid, (A.nCantid * C.nMonto) AS NTOTSOL,
					 CASE
						  WHEN B.nCantid IS NULL THEN 0
						  WHEN B.nCantid IS NOT NULL THEN B.nCantid 
					     END AS nCanPag,
					 (B.nCantid * C.nMonto) AS nTotPag
                FROM (SELECT B.cIdCate, COUNT(B.cIdCate) AS nCantid FROM B03MDEU  A
                INNER JOIN B03DDEU B ON A.cIdDeud = B.cIdDeud
                INNER JOIN B03TDOC C ON B.cIdCate = C.cIdCate
                WHERE A.tModifi >= '2023-09-01 00:01:01.123123' AND LEFT(B.cIdCate, 2) = 'AC' AND A.cEstado <> 'X'
                GROUP BY B.cIdCate, C.nMonto) AS A
                LEFT JOIN
                (SELECT B.cIdCate, COUNT(B.cIdCate) AS nCantid FROM B03MDEU  A
                INNER JOIN B03DDEU B ON A.cIdDeud = B.cIdDeud
                INNER JOIN B03TDOC C ON B.cIdCate = C.cIdCate
                WHERE A.tModifi >= '2023-09-01 00:01:01.123123' AND LEFT(B.cIdCate, 2) = 'AC' AND A.cEstado = 'C'
                GROUP BY B.cIdCate, C.nMonto) AS B ON A.cIdCate = B.cIdCate
                INNER JOIN B03TDOC C ON A.cIdCate = C.cIdCate) AS B ORDER BY B.nCanPag DESC;";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDCATE'=> $laTmp[0], 'CDESCRI'=> $laTmp[1], 'NMONTO'=> $laTmp[2], 'NCANINS'=> $laTmp[3], 'NMONINS'=> $laTmp[4], 'NCANPAG'=> $laTmp[5], 'NMONPAG'=> $laTmp[6]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY DATOS PARA MOSTRAR';
         return false;
      }
      return true;
   }
   # --------------------------------------------------------------------------
   # Reporte Resumen Proceso de Actualizacion
   # --------------------------------------------------------------------------
   public function omReporteProcesoActualizacion() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      // Conexion con UCSMINS
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxTotalInscripciones2023($loSql);
      $loSql->omDisconnect();
		if (!$llOk) {
			return false;
		}

		$llOk = $this->mxFilePrintReportePagosCursoActualizacion();
      return $llOk;
   }

	protected function mxFilePrintReportePagosCursoActualizacion() {
		$loXls = new CXls();
		$loXls->openXlsIO('Tpt5190', 'R');
		# Cabecera
		$loXls->sendXls(0, 'E', 3, date("Y-m-d"));
		$i = 5;
		$j = 0;
		$lnTotIns = 0;
		$lnTotPag = 0;
		foreach ($this->paDatos as $laFila) {
			$i++;
			$j++;
			$loXls->sendXls(0, 'A', $i, $j);
			$loXls->sendXls(0, 'B', $i, $laFila['CIDCATE']);
			$loXls->sendXls(0, 'C', $i, $laFila['CDESCRI']);
			$loXls->sendXls(0, 'D', $i, $laFila['NCANINS']);
			$loXls->sendXls(0, 'E', $i, $laFila['NCANPAG']);
			$lnTotIns = $lnTotIns + $laFila['NCANINS'] ;
			$lnTotPag = $lnTotPag + $laFila['NCANPAG'] ;
		}

		$loXls->sendXls(0, 'D', 30, $lnTotIns );
		$loXls->sendXls(0, 'E', 30, $lnTotPag);

		$loXls->closeXlsIO();
		$this->pcFile = $loXls->pcFile;
		return true;
	}

}
