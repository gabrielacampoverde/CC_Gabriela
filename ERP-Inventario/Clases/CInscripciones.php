<?php

require_once 'Clases/CBase.php';
require_once 'Clases/CSql.php';

class CInscripciones extends CBase
{
   public $paData, $paDatos, $pcError, $laUniAca, $pcFile;

   public function __construct()
   {
      parent::__construct();
      $this->paData = $this->paDatos = $this->pcError = $this->laUniAca = $this->pcFile = null;
   }

   public function omBuscarEstudiantePorNombre() {
      $llOk = $this->mxValidarBuscarEstudiantePorNombre();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxomBuscarEstudiantePorNombre();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValidarBuscarEstudiantePorNombre() {
      if (!isset($this->paData['CNOMBRE']) or $this->paData['CNOMBRE'] == "") {
         $this->pcError = "NOMBRE NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxomBuscarEstudiantePorNombre() {
      $laData = ['ID' => 'INS1010B', 'CNOMBRE' => str_replace(' ', '%', $this->paData['CNOMBRE'])];
      $sJson = json_encode($laData);
      // el comando debe apuntar al entorno virtual en el que esta trabajando
      $entorno = 'python';
      $command = $entorno.' ../ERPWS/Clases/CPersonal.py \''.$sJson.'\'';
      $laTmp = fxInvocaPython($command);

      $this->paData= array_merge($this->paData, ['FVACIO' => 0]);
      if (isset($laTmp)) {
         if (!isset($laTmp['ERROR'])) {
            $this->paData['FVACIO'] = 1;
            $this->paDatos = $laTmp;
            return true;
         } else {
            $this->pcError = $laTmp['ERROR'];
            return false;
         }
      } else {
         $this->pcError = 'HA OCURRIDO UN ERROR';
         return false;
      }
   }

   public function omBuscarEstudianteDni() {
      $llOk = $this->mxValidaromBuscarEstudianteDni();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxBuscarEstudianteDni();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValidaromBuscarEstudianteDni() {
      if (!isset($this->paData['CUSUCOD'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['ANRODNI']) or count($this->paData['ANRODNI']) == 0) {
         $this->pcError = "DEBE SELECCIONAR AL MENOS UN REGISTRO PARA SU ACTUALIZACIÓN ";
         return false;
      }
      return true;
   }
   protected function mxBuscarEstudianteDni() {
      $laData = ['ID' => 'INS1010V', 'ANRODNI' => $this->paData['ANRODNI'], 'CUSUCOD' => $this->paData['CUSUCOD']];
      $sJson = json_encode($laData);
      // el comando debe apuntar al entorno virtual en el que esta trabajando
      $entorno = 'python';
      $command = $entorno.' ../ERPWS/Clases/CPersonal.py \''.$sJson.'\'';
      $laTmp = fxInvocaPython($command);
      if (isset($laTmp)) {
         $this->paDatos = $laTmp;
         return true;
      } else {
         $this->pcError = 'HA OCURRIDO UN ERROR';
         return false;
      }
   }

   public function omUnificarEstudiante() {
      $llOk = $this->mxValidaromUnificarEstudiante();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxomomUnificarEstudiante();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValidaromUnificarEstudiante() {
      if (!isset($this->paData['CUSUCOD'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['ANRODNI']) or count($this->paData['ANRODNI']) == 0) {
         $this->pcError = "DEBE SELECCIONAR AL MENOS UN REGISTRO PARA SU ACTUALIZACIÓN ";
         return false;
      }elseif (!isset($this->paData['CDNINRO']) or $this->paData['CDNINRO'] == '' or !preg_match('/^[0-9]{8}$/', $this->paData['CDNINRO'])) {
         $this->pcError = "DNI NUEVO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxomomUnificarEstudiante() {
      $laData = ['ID' => 'INS1010A', 'ANRODNI' => $this->paData['ANRODNI'], 'CUSUCOD' => $this->paData['CUSUCOD'], 'CDNINRO' => $this->paData['CDNINRO']];
      $sJson = json_encode($laData);
      // el comando debe apuntar al entorno virtual en el que esta trabajando
      $entorno = 'python';
      $command = $entorno.' ../ERPWS/Clases/CPersonal.py \''.$sJson.'\'';
      $laTmp = fxInvocaPython($command);
      if (isset($laTmp)) {
         if (!isset($laTmp['ERROR'])) {
            if (isset($laTmp['OK'])) {
               fxAlert('DNI ACTUALIZADO CORRECTAMENTE');
            }
            return true;
         } else {
            $this->pcError = $laTmp['ERROR'];
            return false;
         }
      } else {
         $this->pcError = 'HA OCURRIDO UN ERROR';
         return false;
      }
   }



   public function mxGenerarReportePagosCursoActualizacion() {
       $llOk = $this->mxValParamGenerarReportePagosCursoActualizacion();
       if (!$llOk) {
           return false;
       }
       $loSql = new CSql();
       $llOk  = $loSql->omConnect();
       if (!$llOk) {
           $this->pcError = $loSql->pcError;
           return false;
       }
       $llOk  = $this->mxValUsuarioGenerarReportePagosCursoActualizacion($loSql);
       if (!$llOk) {
           $loSql->omDisconnect();
           return false;
       }
       $loSql->omDisconnect();
       $loSql = new CSql();
       $llOk  = $loSql->omConnect(2);
       if (!$llOk) {
           $this->pcError = $loSql->pcError;
           return false;
       }
//      $llOk = $this->mxMostrarReportePagosCursoActualizacion($loSql);
      $llOk = $this->mxFpg1420Consultar($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReportePagosCursoActualizacion();
      return $llOk;
   }
    protected function mxValParamGenerarReportePagosCursoActualizacion(){
        if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4){
            $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
            return false;
        } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3){
            $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVALIDO';
            return false;
        }
        return true;
    }
    protected function mxValUsuarioGenerarReportePagosCursoActualizacion($p_oSql){
        if (in_array($this->paData['CCENCOS'], ['UNI', '00E', '1BU'])) {
            # Si es super-usuario
            $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}'";
            $R1 = $p_oSql->omExec($lcSql);
            $RS = $p_oSql->fetch($R1);
            if (strlen($RS[0]) == '') {
                return;
            } elseif ($RS[0] == 'A') {
                $this->laUniAca[] = '*';
                return true;
            }
        } else {
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

   protected function mxMostrarReportePagosCursoActualizacion($p_oSql) {
       $laData  = [];
       $laDatos = [];
       $lcSql = "SELECT A.cNroDni, C.cNombre, TO_CHAR(A.dFecha, 'YYYY-MM-DD') AS dFecGen, TO_CHAR(A.dRecepc, 'YYYY-MM-DD')
               AS dFecPag, B.nCosto, A.cNroPag, B.cCodAlu, D.cidcate, D.cdescri, C.cEmail, C.cNroCel, A.cEstado, C.cUniAca
               FROM B03MDEU A
               INNER JOIN B03DDEU B ON B.cIdDeud = A.cIdDeud
               INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
               INNER JOIN B03TDOC D ON D.cIdCate = B.cIdCate
               INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
               WHERE D.cTipo = 'AC' AND A.cEstado IN ('B', 'C', 'A') AND A.DFECHA > '2023-09-01 00:00:01.123123'
               ORDER BY D.cidcate, dFecPag, C.cNombre";
       $R1 = $p_oSql->omExec($lcSql);
       while ($laFila = $p_oSql->fetch($R1)) {
           if ($this->laUniAca[0] == '*'){}
           else if (!in_array($laFila[12], $this->laUniAca)){
               continue;
           }
           $lcNombre = explode("/", $laFila[1]);
           $laDatos[] = ['CNRODNI' => $laFila[0], 'CPATPMA' => $lcNombre[0], 'CMATPMA' => $lcNombre[1], 'CNOMPMA' => $lcNombre[2], 'DFECGEN' => $laFila[2],
               'DFECPAG'  => $laFila[3], 'NCOSTO'  => $laFila[4], 'CNROPAG' => $laFila[5], 'CCODALU' => $laFila[6],
               'CUNIACA' => $laFila[7], 'CNOMUNI' => $laFila[8], 'CEMAIL' => $laFila[9], 'CNROCEL' => $laFila[10], 'CESTADO' => $laFila[11]];
       }
       $this->paData  = $laData;
       $this->laDatos = $laDatos;
       return true;
   }
   protected function mxPrintReportePagosCursoActualizacion() {
      $loXls = new CXls();
      $loXls->openXlsIO('Cua1010', 'R');
      # Cabecera
      $loXls->sendXls(0, 'J', 3, date("Y-m-d"));
      $i = 5;
      $j = 0;
      foreach ($this->paDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CCODALU']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'E', $i, $laFila['CUNIACA'].'-'.$laFila['CNOMUNI']);
         $loXls->sendXls(0, 'F', $i, $laFila['CEMAIL']);
         $loXls->sendXls(0, 'G', $i, $laFila['CNROCEL']);
         $loXls->sendXls(0, 'H', $i, $laFila['DFECGEN']);
         $loXls->sendXls(0, 'I', $i, $laFila['DFECPAG']);
         $loXls->sendXls(0, 'J', $i, $laFila['NCOSTO']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # --------------------------------------------------
   # Consulta Inscritos Curso de Actualización
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
      $loSql->omDisconnect();
      $loSqlIns = new CSql();
      $llOk  = $loSqlIns->omConnect(2);
      $llOk = $this->mxFpg1420Consultar($loSqlIns);
      $loSqlIns->omDisconnect();
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
		if (in_array($this->paData['CCENCOS'], ['UNI', '00E', '1BU'])) {
         # Si es super-usuario
         $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $RS = $p_oSql->fetch($R1);
         if (strlen($RS[0]) == '') {
            return;
         } elseif ($RS[0] == 'A') {
            $this->laUniAca[] = '*';
            return true;
         }
      } else {
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

		$lcSql = "SELECT A.cNroDni, C.cNombre, TO_CHAR(A.dFecha, 'YYYY-MM-DD') AS dFecGen, TO_CHAR(A.dRecepc, 'YYYY-MM-DD')
               AS dFecPag, B.nCosto, A.cNroPag, B.cCodAlu, D.cidcate, D.cdescri, C.cEmail, C.cNroCel, A.cEstado, C.cUniAca
               FROM B03MDEU A
               INNER JOIN B03DDEU B ON B.cIdDeud = A.cIdDeud
               INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
               INNER JOIN B03TDOC D ON D.cIdCate = B.cIdCate
               WHERE D.cTipo = 'AC' AND A.cEstado IN ('B', 'C', 'A') AND A.DFECHA > '2023-09-01 00:00:01.123123'";

		if ($this->laUniAca[0] != '*') {
			$lcUniAcaIdCate = $this->mxValCentroCostosConUnidadAcademicaProcesoActualizacion();
			if ($lcUniAcaIdCate == false) {
				$this->pcError = "USTED NO TIENE PERMISOS PARA VER ESTA CONSULTA";
				return false;
			} else {
				$lcSql = $lcSql . " AND B.CIDCATE = '{$lcUniAcaIdCate[0]}'";
			}
		}
		$lcSql = $lcSql . " ORDER BY D.cidcate, dFecPag, C.cNombre";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
//         if ($this->laUniAca[0] == '*'){}
//         else if (!in_array($laFila[14], $this->laUniAca)){
//            continue;
//         }
         $laDatos[] = ['CNRODNI' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'DFECGEN' => $laFila[2],
            'DFECPAG'  => $laFila[3], 'NCOSTO'  => $laFila[4], 'CNROPAG' => $laFila[5], 'CCODALU' => $laFila[6],
            'CUNIACA' => $laFila[7], 'CNOMUNI' => $laFila[8], 'CEMAIL' => $laFila[9], 'CNROCEL' => $laFila[10], 'CESTADO' => $laFila[11]];
      }
      $this->paData  = $laData;
      $this->paDatos = $laDatos;
      return true;
   }

	protected function mxValCentroCostosConUnidadAcademicaProcesoActualizacion() {
		$laCarreras = [
			[ ['59'], ['ACICON'], ['35'] ],
			[ ['53'], ['ACIADM'], ['36'] ],
			[ ['47', '48', '49', '50', '51', '52'], ['ACCUCS'], ['38'] ],
			[ ['40', '54'], ['ACIAIC'], ['3K'] ],
			[ ['62'], ['ACDERE'], ['62'] ],
			[ ['67', '4I'], ['ACAGRO'], ['67'] ],
			[ ['69'], ['ACALIM'], ['69'] ],
			[ ['78'], ['ACCUED'], ['78'] ],
			[ ['44'], ['ACIIND'], ['T4'] ],
			[ ['41'], ['ACCUAQ'], ['T5'] ],
			[ ['45'], ['ACCUIC'], ['T6'] ],
			[ ['4G'], ['ACCUIA'], ['T7'] ],
			[ ['4A', '4E', '4K', '4L', '73'], ['ACCIME'], ['T8'] ],
			[ ['71'], ['ACCUIS'], ['T9'] ],
			[ ['74'], ['ACCUIE'], ['U0'] ],
			[ ['4F'], ['ACMINA'], ['U1'] ],
			[ ['4C', '77'], ['ACCSPM'], ['U4'] ],
			[ ['4D', '66'], ['ACCUTS'], ['U5'] ],
			[ ['79', '61', 'S1', 'T2'], ['ACCUTH'], ['U6'] ],
			[ ['65'], ['ACCUFB'], ['U7'] ],
			[ ['42'], ['ACCUIB'], ['U8'] ],
			[ ['68'], ['ACVETE'], ['68'] ],
			[ ['58'], ['AC'], ['58'] ],
			[ ['76'], ['ACSICO'], ['76'] ]
		];
		foreach ($laCarreras as $laProceso) {
			$laValPro = array_diff($laProceso[0], $this->laUniAca);
			if (count($laValPro) != count($laProceso[0])) {
				return $laProceso[1];
			}
		}
		return false;


//		if (array_diff( ['59'], $this->laUniAca)) {				#CONTABILIDAD
//			$lcUniAcaCco = '35';
//		} elseif (array_diff( ['53'], $this->laUniAca)) {		#ADMIN EMPRESAS
//			$lcUniAcaCco = '36';
//		} elseif (array_diff( ['47', '48', '49', '50', '51', '52'], $this->laUniAca)) {		#COM SOCIAL	*
//			$lcUniAcaCco = '38';
//		} elseif (array_diff( ['40', '54'], $this->laUniAca)) {		#ING COMERCIAL	*
//			$lcUniAcaCco = '3K';
//		} elseif (array_diff( ['62'], $this->laUniAca)) {		#DERECHO
//			$lcUniAcaCco = '62';
//		} elseif (array_diff( ['67', '4I'], $this->laUniAca)) {		#AGRONOMIA	*
//			$lcUniAcaCco = '67';
//		} elseif (array_diff( ['69'], $this->laUniAca)) {		#IND ALIMENTARIAS
//			$lcUniAcaCco = '69';
//		} elseif (array_diff( ['78'], $this->laUniAca)) {		#EDUCACION
//			$lcUniAcaCco = '78';
//		} elseif (array_diff( ['44'], $this->laUniAca)) {		#ING	INDUSTRIAL
//			$lcUniAcaCco = 'T4';
//		} elseif (array_diff( ['41'], $this->laUniAca)) {		#ARQUITECTURA
//			$lcUniAcaCco = 'T5';
//		} elseif (array_diff( ['45'], $this->laUniAca)) {		#ING 	CIVIL
//			$lcUniAcaCco = 'T6';
//		} elseif (array_diff( ['4G'], $this->laUniAca)) {		#ING AMBIENTAL
//			$lcUniAcaCco = 'T7';
//		} elseif (array_diff( ['4A', '4E', '4K', '4L', '73'], $this->laUniAca)) {	#ING	MECANICA Y AFINES
//			$lcUniAcaCco = 'T8';
//		} elseif (array_diff( ['71'], $this->laUniAca)) {		#ING	SISTEMAS
//			$lcUniAcaCco = 'T9';
//		} elseif (array_diff( ['74'], $this->laUniAca)) {		#ING	ELECTRONICA
//			$lcUniAcaCco = 'U0';
//		} elseif (array_diff( ['4F'], $this->laUniAca)) {		#ING MINAS
//			$lcUniAcaCco = 'U1';
//		} elseif (array_diff( ['4C', '77'], $this->laUniAca)) {		#PUBLICIDAD
//			$lcUniAcaCco = 'U4';
//		} elseif (array_diff( ['4D', '66'], $this->laUniAca)) {		#TRABAJO SOCIAL
//			$lcUniAcaCco = 'U5';
//		} elseif (array_diff( ['79', '61', 'S1', 'T2'], $this->laUniAca)) {		#TURISMO Y HOTELERIA	*
//			$lcUniAcaCco = 'U6';
//		} elseif (array_diff( ['65'], $this->laUniAca)) {		#FARMACIA
//			$lcUniAcaCco = 'U7';
//		} elseif (array_diff( ['42'], $this->laUniAca)) {		#BIOTECNOLOGIA
//			$lcUniAcaCco = 'U8';
//		} elseif (array_diff( ['68'], $this->laUniAca)) {		#VETERINARIA
//			$lcUniAcaCco = '68';
//		} elseif (array_diff( ['58'], $this->laUniAca)) {		#TEOLOGIA
//			$lcUniAcaCco = '58';
//		} elseif (array_diff( ['76'], $this->laUniAca)) {		#PSICOLOGIA
//			$lcUniAcaCco = '76';
//		}
//		return false;
	}
}