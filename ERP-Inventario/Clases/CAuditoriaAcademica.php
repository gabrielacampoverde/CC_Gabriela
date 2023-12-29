<?php
require_once 'Clases/CBase.php';
require_once 'Clases/CSql.php';

// --------------------------------------------------
// Auditoria Academica
//  2023-04-10 JCF Creacion
// --------------------------------------------------
class CAuditoriaAcademica extends CBase {
   public $paData, $paDatos, $pcFile;
   protected $laData, $laDatos;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->laData = $this->laDatos = null;
   }

   public function mxValParamUsuario($p_oSql, $p_cModulo = '000'){
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCencos = 'UNI' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         ;
      } elseif ($laFila[0] == 'A'){
         return true;
      }
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCencos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '$p_cModulo'";
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

   // --------------------------------------------------
   // Validar USUARIO
   // 2023-04-12                 J.C.F. CREACION
   // --------------------------------------------------
   /*protected function mxValParamUsuario($p_oSql) {
      $lcSql = "SELECT cEstado FROM S01PROL WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cCodRol = 'AUA'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "USUARIO NO TIENE PERMISO PARA LA OPCIÓN";
         return false;
      }
      return true;
   }*/

   // --------------------------------------------------
   // INIT VALIDAR USUARIO
   // 2023-04-12                 J.C.F. CREACION
   // --------------------------------------------------
   public function omInitUsuario() {
      $llOk = $this->mxValParamInitUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '00T');
      $loSql->omDisconnect();
      return $llOk;
   }

   private function mxValParamInitUsuario() {
      if (!isset($this->paData['CUSUCOD']) || !(preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = "CÓDIGO DE USUARIO NO VÁLIDO";
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // INIT VALIDAR DIRECCION
   // 2023-04-12                 J.C.F. CREACION
   // --------------------------------------------------
   public function omInitUsuarioDireccion() {
      $llOk = $this->mxValParamInitUsuarioDireccion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql, '00S');
      $loSql->omDisconnect();
      return $llOk;
   }

   private function mxValParamInitUsuarioDireccion() {
      if (!isset($this->paData['CUSUCOD']) || !(preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = "CÓDIGO DE USUARIO NO VÁLIDO";
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // BUSCAR AUDITORIAS POR FECHA PARA ANULAR
   // 2023-04-18                 J.C.F. Creacion
   // --------------------------------------------------

   public function omBuscarPorFecha() {
      $llOk = $this->mxValParamBuscarPorFecha();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarPorFecha($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamBuscarPorFecha() {
      $loDate = new CDate;
      if (!isset($this->paData['DFECHA']) || !$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = 'FECHA INGRESADA NO VALIDA';
         return false;
      }
      return true;
   }

   protected function mxBuscarPorFecha($p_oSql) {
      $lcSql = "SELECT A.cIdAsis, A.tRevisi, A.cEstado, A.mObserv, A.cUsuCod, B.cCodCur, B.cNroDni, B.cSecGru,
         B.cAula, B.cHorIni, B.cHorFin, B.cPeriod, C.cDescri, D.cNombre, A.nSerial FROM A12DAUD A
         INNER JOIN A12MAUD B ON B.cidasis = A.cidasis
         INNER JOIN A02MCUR C ON C.ccodcur = B.ccodCur
         INNER JOIN S01MPER D ON D.cNroDni = B.cNroDni
         WHERE TO_CHAR(A.trevisi, 'YYYY-MM-dd') = '{$this->paData['DFECHA']}' AND A.cestado != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDASIS'=> $laFila[0], 'TREVISI'=> $laFila[1], 'CESTADO'=> $laFila[2], 'MOBSERV'=> $laFila[3], 'CUSUCOD'=> $laFila[4],
                     'CCODCUR'=> $laFila[5], 'CNRODNI'=> $laFila[6], 'CSECGRU'=> $laFila[7], 'CAULA'=> $laFila[8], 'CHORINI'=> $laFila[9],
                     'CHORFIN'=> $laFila[10], 'CPERIOD'=> $laFila[11], 'CDESCRI'=> $laFila[12], 'CNOMBRE'=> str_replace('/', ' ', $laFila[13]),
                     'NSERIAL'=> $laFila[14]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON AUDITORIAS EN ESAS FECHAS';
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // ANULAR LAS AUDITORIAS
   // 2023-04-19              J.C.F. CREACION
   // --------------------------------------------------

   public function omAnularAuditoria() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAnularAuditoria($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   private function mxAnularAuditoria($p_oSql) {
      $lcSql = "UPDATE A12DAUD SET cEstado = 'X' WHERE nSerial = {$this->paData['NSERIAL']}";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ANULAR LA AUDITORIA";
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // Init Detalle de Clases
   // 2023-04-11 JCF Creacion
   // --------------------------------------------------
   public function omInitAuditoria() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGrabarAuditoria($loSql);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAuditoria($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   private function mxInitAuditoria($p_oSql) {
      $lcSql = "SELECT A.cIdAsis, A.cSecGru, A.cAula, A.cHorIni, A.cHorFin, B.cDescri, A.cNroDni, C.cNombre FROM A12MAUD A
               INNER JOIN A02MCUR B ON B.cCodCur = A.cCodCur
               INNER JOIN S01MPER C ON C.cNroDni = A.cNroDni
               WHERE A.cidasis = '{$this->paData['CIDASIS']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laDatos= ['CIDASIS'=> $laFila[0], 'CSECGRU'=> $laFila[1], 'CAULA'=> $laFila[2], 'CHORINI'=> $laFila[3], 'CHORFIN'=> $laFila[4],
                     'CDESCRI'=> $laFila[5], 'CNRODNI'=> $laFila[6], 'CNOMBRE'=> str_replace('/', ' ', $laFila[7])];
      if (count($laDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON LOS DATOS DE LA CLASE';
         return false;
      }
      $this->paDatos = $laDatos;
      return true;
   }

   // --------------------------------------------------
   // Cargar horarios de acuerdo a pabellon o aula
   // 2023-04-10 JCF Creacion
   // --------------------------------------------------
   public function omCargarHorarios() {
      $llOk = $this->mxValParamCargarHorarios();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarHorarios($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCargarHorarios() {
      if (!isset($this->paData['CPABAUL']) || !preg_match('(^[A-Z0-9/\-]{1,20}$)', $this->paData['CPABAUL'])) {
         $this->pcError = "PABELLÓN/AULA NO DEFINIDA O INVÁLIDA";
         return false;
      }
      return true;
   }

   protected function mxCargarHorarios($p_oSql) {
      $lcPabAul = trim($this->paData['CPABAUL']).'%';
      // Dia de semana y hora actual
      $lnDiaSem = date('w');
      $lcHora = date('H:i');
      // Cargar horarios
      $lcSql = "SELECT A.cIdAsis, A.cCodCur, A.cSecGru, A.cAula, A.cHorIni, A.cHorFin, B.cDescri, A.cNroDni, C.cNombre FROM A12MAUD A
                INNER JOIN A02MCUR B ON B.cCodCur = A.cCodCur
                INNER JOIN S01MPER C ON C.cnrodni = A.cnroDni
                WHERE A.cAula LIKE '{$lcPabAul}' AND A.cEstado = 'A' AND A.nDiaSem = {$lnDiaSem} AND
                A.cHorIni <= '{$lcHora}' AND A.cHorFin >= '{$lcHora}' ORDER BY A.cAula";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDASIS'=> $laTmp[0], 'CCODCUR'=> $laTmp[1], 'CSECGRU'=> $laTmp[2], 'CAULA'=> $laTmp[3],
                       'CHORINI'=> $laTmp[4], 'CHORFIN'=> $laTmp[5], 'CDESCUR'=> $laTmp[6], 'CNRODNI'=> $laTmp[7],
                       'CNOMBRE'=> str_replace('/', ' ', $laTmp[8])];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY HORARIOS PARA MOSTRAR';
         return false;
      }
      foreach ($this->paDatos as $laFila) {
         $loSql = "SELECT cEstado FROM A12DAUD WHERE cIdAsis = '{$laFila['CIDASIS']}' AND TO_CHAR(tRevisi, 'YYYY-MM-DD') = TO_CHAR(NOW(), 'YYYY-MM-DD') AND cEstado != 'X'";
         $R1 = $p_oSql->omExec($loSql);
         $laFila1 = $p_oSql->fetch($R1);
         $laDatos[] = ['CIDASIS'=> $laFila['CIDASIS'], 'CCODCUR'=> $laFila['CCODCUR'], 'CSECGRU'=> $laFila['CSECGRU'],
                     'CAULA'=> $laFila['CAULA'], 'CHORINI'=> $laFila['CHORINI'], 'CHORFIN'=> $laFila['CHORFIN'],
                     'CDESCUR'=> $laFila['CDESCUR'], 'CNRODNI'=> $laFila['CNRODNI'], 'CNOMBRE'=> $laFila['CNOMBRE'],
                     'CESTADO'=> $laFila1[0]];
      }
      $this->paDatos = $laDatos;
      if (count($this->paDatos) == 0) {
         $this->pcError = 'NO HAY HORARIOS PARA MOSTRAR';
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // Init grabar auditoria academica
   // 2023-04-12 JCF Creacion
   // --------------------------------------------------
   public function omInitGrabarAuditoria() {
      $llOk = $this->mxValParamInitGrabarAuditoria();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitGrabarAuditoria($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamInitGrabarAuditoria() {
      if (!isset($this->paData['CIDASIS']) || !(preg_match('/^[0-9A-Z]{5}$/', $this->paData['CIDASIS']))) {
         $this->pcError = "IDENTIFICADOR DE HORARIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxInitGrabarAuditoria($p_oSql) {
      $lcSql = "SELECT cEstado FROM A12MAUD WHERE cIdAsis = '{$this->paData['CIDASIS']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp) {
         $this->pcError = 'ID DE AUDITORÍA ACADÉMICA DE ASISTENCIA NO EXISTE';
         return false;
      } elseif ($laTmp[0] != 'A') {
         $this->pcError = 'ID DE AUDITORÍA ACADÉMICA DE ASISTENCIA NO ESTÁ ACTIVO';
         return false;
      }
      $lcSql = "SELECT cEstado FROM A12DAUD WHERE cIdAsis = '{$this->paData['CIDASIS']}' AND TO_CHAR(tRevisi, 'YYYY-MM-DD') = TO_CHAR(NOW(), 'YYYY-MM-DD') AND cEstado != 'X'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if ($laTmp && $laTmp[0] != 'X') {
         $this->pcError = 'AUDITORÍA ACADÉMICA DE ASISTENCIA YA SE REGISTRÓ';
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // REPORTE DAIRIO DE AUDITORIA ACADEMICA
   // 2023-04-21              J.C.F. CREACION
   // --------------------------------------------------
   public function omReporteAuditoriaDiario() {
      $llOk = $this->mxValParamReporteAuditoriaDiario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
      }
      $llOk = $this->mxReporteAuditoriaDiario($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteAuditoriaDiario();
      return $llOk;
   }

   protected function mxValParamReporteAuditoriaDiario() {
      $loDate = new CDate;
      if (!isset($this->paData['DFECHA']) || !$loDate->valDate($this->paData['DFECHA'])) {
         $this->pcError = 'FECHA INGRESADA INVALIDA';
         return false;
      }
      return true;
   }

   protected function mxReporteAuditoriaDiario($p_oSql) {
      $loSql = "SELECT A.cIdAsis, TO_CHAR(A.tRevisi, 'YYYY-MM-DD HH24:MI:SS'), A.cEstado, A.mObserv, A.cUsuCod, B.cCodCur,
         B.cNroDni, B.cSecGru, B.cAula, B.cHorIni, B.cHorFin, C.cDescri, D.cNombre, E.cNombre FROM A12DAUD A
         INNER JOIN A12MAUD B ON B.cidasis = A.cidasis
         INNER JOIN A02MCUR C ON C.ccodcur = B.ccodCur
         INNER JOIN S01MPER D ON D.cNroDni = B.cNroDni
         INNER JOIN V_S01TUSU_1 E ON E.cCodUsu = A.cUsuCod
         WHERE TO_CHAR(A.trevisi, 'YYYY-MM-DD') = '{$this->paData['DFECHA']}' AND A.cestado != 'X'";
      $RS = $p_oSql->omExec($loSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($laFila[2]='A') {
            $laEstado = 'PRESENTE';
         } else {
            $laEstado = 'AUSENTE';
         }
         $this->paDatos[] = ['CIDASIS'=> $laFila[0], 'TREVISI'=> $laFila[1], 'CESTADO'=> $laFila[2], 'MOBSERV'=> $laFila[3],
            'CUSUCOD'=> $laFila[4], 'CCODCUR'=> $laFila[5], 'CNRODNI'=> $laFila[6], 'CSECGRU'=> $laFila[7],
            'CAULA'=> $laFila[8], 'CHORINI'=> $laFila[9], 'CHORFIN'=> $laFila[10], 'CPERIOD'=> $laFila[11],
            'CDESCRI'=> str_replace('/', ' ', $laFila[12]), 'CNOMBRE'=> str_replace('/', ' ', $laFila[13]), 'CNOMUSU'=> str_replace('/', ' ', $laFila[14]), 'CDESEST'=> $laEstado];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError= 'NO SE ENCONTRARON REGISTROS PARA GENERAR EL REPORTE';
         return false;
      }
      return true;
   }

   protected function mxPrintReporteAuditoriaDiario() {
      $loXls = new CXls();
      $loXls->openXlsIO('Aud1310','R');
      $i = 4;
      $j = 1;
      foreach ($this->paDatos as $laFila) {
         $loXls->sendXls(0, 'A', $i, $j); // SERIE
         $loXls->sendXls(0, 'B', $i, $laFila['CCODCUR'].' '.$laFila['CDESCRI']); // CODIGO Y NOMBRE DE CURSO
         $loXls->sendXls(0, 'C', $i, $laFila['CAULA']); // AULA
         $loXls->sendXls(0, 'D', $i, $laFila['CSECGRU']); // SECCION Y GRUPO
         $loXls->sendXls(0, 'E', $i, $laFila['CDESEST']); // ESTADO DEL DOCENTE
         $loXls->sendXls(0, 'F', $i, $laFila['CNRODNI'].' '.$laFila['CNOMBRE']); // CODIGO Y NOMBRE DEL DOCENTE
         $loXls->sendXls(0, 'G', $i, $laFila['TREVISI']); // FECHA Y HORA DE REVISION
         $loXls->sendXls(0, 'H', $i, $laFila['CHORINI'].' '.$laFila['CHORFIN']); // HORA DE INICIO Y FIN
         $loXls->sendXls(0, 'I', $i, $laFila['CUSUCOD'].' '.$laFila['CNOMUSU']); // CODIGO Y NOMBRE DE QUIEN REGISTRO
         $loXls->sendXls(0, 'J', $i, $laFila['MOBSERV']); // OBSERVACIONES
         $i++;
         $j++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // --------------------------------------------------
   // Reporte de Auditoria Academica
   // 2023-04-14                 J.C.F. CREACION
   // --------------------------------------------------

   public function omReporteAuditoria() {
      $llOk = $this->mxValParamReporteAuditoria();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
      }
      $llOk = $this->mxReporteAuditoria($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteAuditoria();
      return $llOk;
   }

   protected function mxValParamReporteAuditoria() {
      if (!isset($this->paData['DFECHA']) || !(preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['DFECHA']))) {
         $this->pcError = 'FECHA INVALIDA O NO INGRESADA';
         return false;
      }
      return true;
   }

	protected function mxReporteAuditoria($p_oSql) {
	$loSql= "SELECT A.cIdAsis, TO_CHAR(A.tRevisi, 'YYYY-MM-DD HH24:MI:SS'), A.cEstado, A.mObserv, A.cUsuCod, B.cCodCur, B.cNroDni, B.cSecGru,
	       B.cAula, B.cHorIni, B.cHorFin, B.cPeriod, C.cDescri, D.cNombre, E.cNombre FROM A12DAUD A
	       INNER JOIN A12MAUD B ON B.cidasis = A.cidasis
	       INNER JOIN A02MCUR C ON C.ccodcur = B.ccodCur
	       INNER JOIN S01MPER D ON D.cNroDni = B.cNroDni
	       INNER JOIN V_S01TUSU_1 E ON E.cCodUsu = A.cUsuCod
	       WHERE TO_CHAR(A.trevisi, 'YYYY-MM') = '{$this->paData['DFECHA']}' AND A.cestado != 'X'";
	$RS = $p_oSql->omExec($loSql);
	while ($laFila = $p_oSql->fetch($RS)) {
	 if ($laFila[2] == 'A') {
	    $laFila[2] = 'PRESENTE';
	 } else {
	    $laFila[2] = 'AUSENTE';
	 }
	 $this->paDatos[] = ['CIDASIS'=> $laFila[0], 'TREVISI'=> $laFila[1], 'CESTADO'=> $laFila[2],
			      'MOBSERV'=> $laFila[3], 'CUSUCOD'=> $laFila[4], 'CCODCUR'=> $laFila[5],
			      'CNRODNI'=> $laFila[6], 'CSECGRU'=> $laFila[7], 'CAULA'=> $laFila[8],
			      'CHORINI'=> $laFila[9], 'CHORFIN'=> $laFila[10], 'CPERIOD'=> $laFila[11],
			      'CDESCRI'=> str_replace('/', ' ', $laFila[12]), 'CNOMBRE'=> str_replace('/', ' ', $laFila[13]),
			      'CNOMUSU'=> str_replace('/', ' ', $laFila[14])];
	}
	if (count($this->paDatos) == 0) {
	 $this->pcError= 'NO SE ENCONTRARON REGISTROS PARA GENERAR EL REPORTE';
	 return false;
	}
	return true;
	}

	protected function mxPrintReporteAuditoria() {
	    $loXls = new CXls();
	    $loXls->openXlsIO('Aud1300', 'R');
	    $i = 4;
	    $j = 1;
	    foreach ($this->paDatos as $laFila) {
		$loXls->sendXls(0, 'A', $i, $j); // SERIE
		$loXls->sendXls(0, 'B', $i, $laFila['CCODCUR'].' '.$laFila['CDESCRI']); // CODIGO Y NOMBRE DE CURSO
		$loXls->sendXls(0, 'C', $i, $laFila['CAULA']); // AULA
		$loXls->sendXls(0, 'D', $i, $laFila['CSECGRU']); // SECCION Y GRUPO
		$loXls->sendXls(0, 'E', $i, $laFila['CESTADO']); // ESTADO
		$loXls->sendXls(0, 'F', $i, $laFila['CNRODNI'].' '.$laFila['CNOMBRE']); // CODIGO Y NOMBRE DEL DOCENTE
		$loXls->sendXls(0, 'G', $i, $laFila['TREVISI']); // FECHA Y HORA DE REVISION
		$loXls->sendXls(0, 'H', $i, $laFila['CHORINI'].' '.$laFila['CHORFIN']); // HORA DE INICIO Y FIN
		$loXls->sendXls(0, 'I', $i, $laFila['CUSUCOD'].' '.$laFila['CNOMUSU']); // CODIGO Y NOMBRE DE QUIEN REGISTRO
		$loXls->sendXls(0, 'J', $i, $laFila['MOBSERV']); // OBSERVACIONES
		$i++;
		$j++;
	    }
	    $loXls->closeXlsIO();
	    $this->pcFile = $loXls->pcFile;
	    return true;
	}

   // --------------------------------------------------
   // Guardar Auditoria Academica
   // 2023-04-12                 J.C.F. CREACION
   // --------------------------------------------------
   public function omGrabarAuditoria() {
      $llOk = $this->mxValParamGrabarAuditoria();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(3);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarAuditoria($loSql);
      if (!$llOk) {
         $loSql->omRollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarAuditoria() {
      if (!isset($this->paData['CIDASIS']) || !(preg_match('/^[0-9A-Z]{5}$/', $this->paData['CIDASIS']))) {
         $this->pcError = "CÓDIGO DE AULA NO IDENTIFICADO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || !(preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = "CÓDIGO DE USUARIO NO VÁLIDO";
         return false;
      } elseif (!isset($this->paData['CESTADO']) || !(preg_match('/^[AF]{1}$/', $this->paData['CESTADO']))) {
         $this->pcError = "ESTADO DE DOCENTE NO INDENTIFICADO";
      } elseif (!isset($this->paData['MOBSERV']) || !(preg_match('(^[A-Z0-9a-zÑñÁÉÍÓÚÜáéíóúü,;.:¿?¡!+*/\-\[\]\(\)\s]{1,1500}$)', $this->paData['MOBSERV']))) {
         $this->pcError = "OBSERVACIÓN NO DEFINIDA O INVÁLIDA";
         return false;
      }
      return true;
   }

   protected function mxGrabarAuditoria($p_oSql) {
      $lcSql = "INSERT INTO A12DAUD (cIdAsis, tRevisi, cEstado, mObserv, cUsuCod) VALUES
                ('{$this->paData['CIDASIS']}', NOW(), '{$this->paData['CESTADO']}', '{$this->paData['MOBSERV']}',
                '{$this->paData['CUSUCOD']}')";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'NO SE PUDO GRABAR AUDITORÍA DE ASISTENCIA';
         return false;
      }
      if ($this->paData['CESTADO'] == 'F') {
         $loEmail = new CEmailAuditoriaAcademica();
         $laEmails = 'eloisa.guillen@ucsm.edu.pe';
         $loEmail->paData = ['AEMAIL'=> $laEmails, 'CBODY'=> 'El docente: '.$this->paData['CNOMBRE'].
         ' no se encuentra presente en la clase: '.$this->paData['CDESCRI'].' en el aula: '.$this->paData['CAULA'].
         ' en el horario de: '.$this->paData['CHORINI'].' hasta: '.$this->paData['CHORFIN'].'.'];
         $loEmail->omSend();
      }
      return true;
   }

   #--------------------------------------------------------
   # Bandeja de Asesoria, Dictamenes pendientes de Revision
   # Creacion ASR 2023-04-03
   #--------------------------------------------------------
	public function omInitBandeja() {
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
      $llOk = $this->mxInitBandeja($loSql);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
     }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParam() {
      // if (!isset($this->paData['CCODUSU']) || preg_match('(^[0-9A-Z]{4}$)',$this->paData['CCODUSU']) ) {
      //    $this->pcError = 'CODIGO DE USUARIO NO VALIDO O INDEFINIDO';
      //    return false;
      // }
      return true;
   }

   protected function mxInitBandeja($p_oSql) {
      $lcSql = "SELECT A.cIdTesi, B.cCodAlu,REPLACE(C.cNombre,'/',' '),REPLACE(E.cNombre,'/',' '),
               F.cdescri,TO_CHAR(D.tdecret,'YYYY-MM-DD'),TO_CHAR(D.tdecret + '7 day'::interval,'YYYY-MM-DD')
               FROM T01MTES A
               INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
               INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
               INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi
               INNER JOIN V_A01MDOC E ON E.cCodDoc =D.cCodDoc
               LEFT JOIN V_S01TTAB F ON F.cCodigo =D.cCatego AND F.cCodTab='251'
               WHERE D.tDictam isnull AND D.cResult in('P','O') AND A.cEstado!='X' AND D.cEstado !='X' AND B.cNivel='P'
               ORDER BY A.cIdtesi DESC";
      $lcFecha=date('Y-m-d');
      $lcFecha = strtotime($lcFecha);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         if (strtotime($laFila[6]) > $lcFecha ){
            $lcFlag='S';
         } else{
            $lcFlag='N';
         }
         $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CCODALU' => $laFila[1], 'CNOMEST' => $laFila[2],'CNOMDOC' => $laFila[3],'CDESCAR'=> $laFila[4],
                             'DASIGN' => $laFila[5],'CFLAG'=> $lcFlag];
      }
      if (count($this->paDatos) == 0){
         $this->pcError= 'NO SE ENCONTRARON REGISTROS PARA GENERAR EL REPORTE';
         return false;
      }
      $lcSql = "SELECT cUniAca,cNomUni FROM S01TUAC WHERE cEstado='A' AND cUniAca NOT IN('20','21','24','43','46','1I','1J','1P','10','2A') ORDER BY CNOMUNI ASC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $this->paCuniAca[] = ['CUNIACA' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if (count($this->paCuniAca) == 0){
        $this->pcError= 'NO SE ENCONTRARON UNIDADES ACADEMICAS';
        return false;
     }
      return true;
   }

   // ---------------------------------------------------------------------------
   // Reporte de seguimiento Dictaminadores/Asesores de tesis
   // 2023-05-11 MPT Creacion
   // ---------------------------------------------------------------------------
   public function omReporteExcel() {
    $llOk = $this->mxValParamReporte();
    if (!$llOk) {
       return false;
    }
    $loSql = new CSql();
    $llOk  = $loSql->omConnect();
    if (!$llOk) {
       $this->pcError = $loSql->pcError;
       return false;
    }
    $llOk = $this->mxDatosReporte($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
       return false;
    }
    $llOk = $this->mxPrintReporte();
    return $llOk;
   }

   protected function mxValParamReporte() {
    if (!isset($this->paData['CUNIACA']) || preg_match('/^[0-9A-Z]{2}$/', $this->paData['CUNIACA'])) {
       $this->pcError = 'UNIDAD ACADEMICA NO DEFINIDA O INVALIDA';
       return false;
    } elseif(!isset($this->paData['DINICIO']) || preg_match('/^[0-9]-{10}$/',$this->paData['DINICIO']) ){
       $this->pcError = 'FECHA INICIAL NO DEFINIDA O INVALIDA';
       return false;
    } elseif(!isset($this->paData['DFINALI']) || preg_match('/^[0-9]-{10}$/',$this->paData['DFINALI']) ){
       $this->pcError = 'FECHA FINAL NO DEFINIDA O INVALIDA';
       return false;
   } elseif($this->paData['DINICIO'] > $this->paData['DFINALI']){
      $this->pcError = 'FECHA INICIAL NO PUEDE SER MAYOR A LA FINAL';
      return false;
   }
    return true;
   }

   protected function mxDatosReporte($p_oSql) {
    //TRAER LOS CODIGOS DE ESTUDIANTE DE DNI
    if ($this->paData['CUNIACA'] =='*'){
        $lcSql="SELECT A.cIdTesi, B.cCodAlu,REPLACE(C.cNombre,'/',' '),REPLACE(E.cNombre,'/',' '),
             F.cdescri,TO_CHAR(D.tdecret,'YYYY-MM-DD'),TO_CHAR(D.tdecret + '7 day'::interval,'YYYY-MM-DD'),D.cResult,G.cNomUni
             FROM T01MTES A
             INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
             INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
             INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego='{$this->paData['CCATEGO']}'
             INNER JOIN V_A01MDOC E ON E.cCodDoc =D.cCodDoc
             LEFT JOIN V_S01TTAB F ON F.cCodigo =D.cCatego AND F.cCodTab='251'
             INNER JOIN S01TUAC G ON G.cUniAca = A.cUniAca
             WHERE D.tDictam isnull AND D.cResult in('P','O') AND A.cEstado!='X' AND D.cEstado !='X' AND B.cNivel='P'
             AND (TO_CHAR(D.tdecret,'YYYY-MM-DD') BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}')
             ORDER BY D.tDecret DESC";
    } else {
        $lcSql = "SELECT A.cIdTesi, B.cCodAlu,REPLACE(C.cNombre,'/',' '),REPLACE(E.cNombre,'/',' '),
        F.cdescri,TO_CHAR(D.tdecret,'YYYY-MM-DD'),TO_CHAR(D.tdecret + '7 day'::interval,'YYYY-MM-DD'),D.cResult,G.cNomUNi
        FROM T01MTES A
        INNER JOIN T01DALU B ON B.cIdTesi = A.cIdTesi
        INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
        INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego='{$this->paData['CCATEGO']}'
        INNER JOIN V_A01MDOC E ON E.cCodDoc =D.cCodDoc
        LEFT JOIN V_S01TTAB F ON F.cCodigo =D.cCatego AND F.cCodTab='251'
        INNER JOIN S01TUAC G ON G.cUniAca = A.cUniAca
        WHERE D.tDictam isnull AND D.cResult in('P','O') AND A.cEstado!='X' AND D.cEstado !='X' AND B.cNivel='P' AND A.cUniAca= '{$this->paData['CUNIACA']}'
        AND (TO_CHAR(D.tdecret,'YYYY-MM-DD') BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}')
        ORDER BY D.tDecret DESC";

    }
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)){
       if($laFila[7] =='P'){
          $lcEstado ='PENDIENTE DE REVISION';
       } elseif($laFila[7] =='O'){
          $lcEstado ='OBSERVADO';
       }
       $this->paDatos[] = ['CIDTESI' => $laFila[0], 'CCODALU' => $laFila[1], 'CNOMEST' => $laFila[2],
                           'CNOMDOC' => $laFila[3],'CDESCAR'=> $laFila[4],
                           'DASIGN' => strval($laFila[5]),'CDETALL' => $lcEstado,'CNOMUNI' => $laFila[8]];
    }
    if (count($this->paDatos) == 0){
       $this->pcError= 'NO SE ENCONTRARON DATOS PARA LA GENERACION DEL REPORTE';
       return false;
    }
    return true;
 }

   protected function mxPrintReporte(){
      $loXls = new CXls();
      $loXls->openXlsIO('Aud1110', 'R');
      $i = 7;
      $j = 0;
      foreach ($this->paDatos as $laFila) {
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CIDTESI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CCODALU']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMEST']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNOMDOC']);
         $loXls->sendXls(0, 'F', $i, $laFila['CDESCAR']);
         $loXls->sendXls(0, 'G', $i, $laFila['DASIGN']);
         $loXls->sendXls(0, 'H', $i, $laFila['CDETALL']);
         $loXls->sendXls(0, 'I', $i, $laFila['CNOMUNI']);
         $i++;
         $j++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // ---------------------------------------------------------------------------
   // Reporte de Docentes por Escuela
   // 2023-05-29 KMC Creacion
   // ---------------------------------------------------------------------------
   public function omReporteExcelJuradoDocentes() {
      $llOk = $this->mxValParamReporteJuradoDocentes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosReporteJuradoDocentes($loSql);
      $loSql->omDisconnect();
      // if (!$llOk) {
      //    return false;
      // }
      return $llOk;
     }

   protected function mxValParamReporteJuradoDocentes() {

      if (!isset($this->paData['CUNIACA'])) {
         $this->pcError = 'UNIDAD ACADEMICA NO DEFINIDA O INVALIDA';
         return false;
      }
      return true;
   }

   protected function mxDatosReporteJuradoDocentes($p_oSql) {
      //$laData = ['ID' => 'ERP0018', 'CYEAR' => $this->paData['CYEAR']];
      $sJson = json_encode($this->paData);
      $lcCommand = "python3 ./xpython/CJuradosTesis.py '".$sJson."' 2>&1";
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData, true);
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paData = $laArray;
      return true;
   }

   protected function mxPrintReporteJuradoDocentes(){
      $loXls = new CXls();
      $loXls->openXlsIO('Aud1110', 'R');
      $i = 7;
      $j = 0;
      foreach ($this->paDatos as $laFila) {
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CIDTESI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CCODALU']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMEST']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNOMDOC']);
         $loXls->sendXls(0, 'F', $i, $laFila['CDESCAR']);
         $loXls->sendXls(0, 'G', $i, $laFila['DASIGN']);
         $loXls->sendXls(0, 'H', $i, $laFila['CDETALL']);
         $loXls->sendXls(0, 'I', $i, $laFila['CNOMUNI']);
         $i++;
         $j++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // ---------------------------------------------------------------------------
   // Reporte de seguimiento de docentes asignados a evaluacion por jurado
   // 2023-05-11 ASR Creacion
   // ---------------------------------------------------------------------------
   public function omReporteExcelConvalidaciones() {
      $llOk = $this->mxValParamReporte();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDatosReporteConvalidaciones($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteConvalidaciones();
      return $llOk;
     }

     protected function mxDatosReporteConvalidaciones($p_oSql) {
      if ($this->paData['CUNIACA'] =='*'){
         $lcSql = "SELECT A.cIdConv,A.cCodAlu,REPLACE(E.cNombre,'/',' '),C.cCodCur||' - '||F.cDescri,REPLACE(D.cNombre,'/',' ') As Docente,TO_CHAR(C.tAsigna,'YYYY-MM-DD'),C.cEstado FROM B06MCNV A
                  INNER JOIN B03MDEU B ON B.cIdDeud= A.cIdDeud
                  INNER JOIN B06DCNV C ON C.cIdConv=A.cIdConv
                  INNER JOIN V_A01MDOC D ON D.cCodDoc=C.cCodDoc AND c.cCodDoc!='0000'
                  INNER JOIN V_A01MALU E ON E.cCodAlu=A.cCodAlu
                  INNER JOIN A02MCUR F ON F.cCodCur=C.cCodCur
                  WHERE
                  (TO_CHAR(C.tAsigna,'YYYY-MM-DD') BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}')
                  AND C.cEstado ='P'
                  ORDER BY C.tAsigna DESC";
      } else {
          $lcSql = "SELECT A.cIdConv,A.cCodAlu,REPLACE(E.cNombre,'/',' '),C.cCodCur||' - '||F.cDescri,REPLACE(D.cNombre,'/',' ') As Docente,TO_CHAR(C.tAsigna,'YYYY-MM-DD'),C.cEstado FROM B06MCNV A
                     INNER JOIN B03MDEU B ON B.cIdDeud= A.cIdDeud
                     INNER JOIN B06DCNV C ON C.cIdConv=A.cIdConv
                     INNER JOIN V_A01MDOC D ON D.cCodDoc=C.cCodDoc AND c.cCodDoc!='0000'
                     INNER JOIN V_A01MALU E ON E.cCodAlu=A.cCodAlu
                     INNER JOIN A02MCUR F ON F.cCodCur=C.cCodCur
                     WHERE
                     E.cUniAca='{$this->paData['CUNIACA']}'
                     AND (TO_CHAR(C.tAsigna,'YYYY-MM-DD') BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}')
                     AND C.cEstado ='P'
                     ORDER BY C.tAsigna DESC";
      }
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         if($laFila[6] =='P'){
            $lcEstado ='PENDIENTE DE REVISION';
         }
         $this->paDatos[] = ['CIDCONV' => $laFila[0], 'CCODALU' => $laFila[1], 'CNOMEST' => $laFila[2],
                             'CASIGN' => $laFila[3],'CNOMDOC'=> $laFila[4],
                             'DASIGN' => strval($laFila[5]),'CESTADO' => $lcEstado];
      }
      if (count($this->paDatos) == 0){
         $this->pcError= 'NO SE ENCONTRARON DATOS PARA LA GENERACION DEL REPORTE';
         return false;
      }
      return true;
   }

     protected function mxPrintReporteConvalidaciones(){
        $loXls = new CXls();
        $loXls->openXlsIO('Aud1150', 'R');
        $i = 7;
        $j = 0;
        foreach ($this->paDatos as $laFila) {
           $loXls->sendXls(0, 'A', $i, $j);
           $loXls->sendXls(0, 'B', $i, $laFila['CIDCONV']);
           $loXls->sendXls(0, 'C', $i, $laFila['CCODALU']);
           $loXls->sendXls(0, 'D', $i, $laFila['CNOMEST']);
           $loXls->sendXls(0, 'E', $i, $laFila['CASIGN']);
           $loXls->sendXls(0, 'F', $i, $laFila['CNOMDOC']);
           $loXls->sendXls(0, 'G', $i, $laFila['DASIGN']);
           $loXls->sendXls(0, 'H', $i, $laFila['CESTADO']);
           $i++;
           $j++;
        }
        $loXls->closeXlsIO();
        $this->pcFile = $loXls->pcFile;
        return true   ;
     }
}
?>

