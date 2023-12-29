<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Libs/fpdf/fpdf.php";

require_once "class/class.ezpdf.php";
require_once "PDF/easyTable.php";
require_once "PDF/exfpdf.php";

date_default_timezone_set('America/Lima');


class CCajaChica2022 extends CBase {

   public $paData, $paDatos;
   protected $laData, $laDatos;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paData = $this->paDatos = [];
   }


   // Init rendicion de caja chica
   public function omInitCajaChica() {
      // $loValidar = new CBaseERP();
      // $loValidar->paData = $this->paData;
      // $llOk = $loValidar->mxValParam();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitCajaChica($p_oSql) {
      // Tipo de documentos
      $laDatos = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('00', '01', '02', '03', '12', 'PM')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[0] == '00') {
            $laFila = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
            continue;
         }
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      $laDatos[] = $laFila;
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE DOCUMENTOS NO DEFINIDOS [087]";
         return false;
      }
      $laData = ['ATIPDOC'=> $laDatos, 'ATIPOPE'=> '', 'DATOS'=> ''];
      // Tipo de operaciones
      $laDatos = [];
      $lcSql = "SELECT cCodOpe, TRIM(cDescri) FROM E02TOPE WHERE cEstado = 'A' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE OPERACIONES NO DEFINIDOS";
         return false;
      }
      $laData['ATIPOPE'] = $laDatos;
      // Traer cajas chicas habilitadas asociadas al codigo de usuario
      $laDatos = [];
      $lcSql = "SELECT A.cCajaCh, B.cDescri, A.nMonMax FROM E03TCCH A 
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCajaCh
                WHERE A.cCodUsu = '{$this->paData['CCODUSU']}' AND A.cEstado = 'A' ORDER BY A.cCajaCh";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT A.cNroCch, A.cEstado, B.cDescri, A.nMonto, A.cGlosa FROM E03MCCH A
                   LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '230' AND SUBSTRING(B.cCodigo, 1, 1) = A.cEstado
                   WHERE A.cCajaCh = '$laFila[0]' AND A.cEstado IN ('A', 'O', 'B', 'C', 'E', 'F', 'G') ORDER BY A.tEnvio DESC";

         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $laDatos[] = ['CNROCCH'=> $laTmp[0], 'NMONMAX'=> $laFila[2], 'CDESCCO'=> $laFila[1], 'CESTADO'=> $laTmp[1], 'CDESEST'=> $laTmp[2],
               'CGLOSA'=> $laTmp[4], 'NMONTO'=> $laTmp[3]];
         }
      }
      $laData['DATOS'] = $laDatos;

      $this->paData = $laData;
      return true;
   }

   ///////////////////////////////////////
   // Revision de auditoria caja chichas
   // 
   ///////////////////////////////////////
   public function omInitAuditoriaCajaChica(){
      // $llOk = $this->mxValParamAuditoriaCajaChica();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAuditoriaCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamAuditoriaCajaChica() {

   }

   protected function mxAuditoriaCajaChica($p_oSql) {
      // Tipo de documentos
      $laDatos = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('00','01', '02', '03', '12', 'PM')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE DOCUMENTOS NO DEFINIDOS [087]";
         return false;
      }
      $laData = ['ATIPDOC'=> $laDatos, 'ATIPOPE'=> ''];
      // Tipo de operaciones
      $laDatos = [];
      $lcSql = "SELECT cCodOpe, TRIM(cDescri) FROM E02TOPE WHERE cEstado = 'A' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE OPERACIONES NO DEFINIDOS";
         return false;
      }
      $laData['ATIPOPE'] = $laDatos;
      $laDatos = [];
      // Revision de cajas chicas
      // $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos FROM E03MCCH A
      //          INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
      //          INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
      //          WHERE B.cTipo = 'A' AND A.cEstado IN ('B', 'O') ORDER BY A.tEnvio";
      $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos, A.nMonto, A.cEstado, D.cDescri AS cDesEst FROM E03MCCH A
               INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
               INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
               LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '230' AND SUBSTRING(D.cCodigo, 1, 1) = A.cEstado
               WHERE B.cTipo = 'B' AND A.cEstado IN ('B', 'O') ORDER BY A.tEnvio";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcDatos = json_decode($laTmp[6], true);
         $laDatos[] = ['CNROCCH'=> $laTmp[0], 'CCAJACH'=> $laTmp[1], 'CDESCRI'=> $laTmp[2], 'TENVIO'=> $laTmp[3],
            'CUSURES'=> $laTmp[4], 'CGLOSA'=> $laTmp[5], 'MDATOS'=> $lcDatos, 'NMONTO'=> $laTmp[7], 'CESTADO'=> $laTmp[8], 'CDESEST'=> $laTmp[9]];
      }
      $this->paData = $laData;
      $this->paDatos = $laDatos;
      return true;
   }

   ///////////////////////////////////////
   // Revision de auditoria detalle caja chichas
   // 2022-09-21  GAR Creacion
   ///////////////////////////////////////
   public function omRevisarAuditoriaCajasChicas(){
      // $llOk = $this->mxValParamRevisarAuditoriaCajasChicas();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRevisarAuditoriaCajasChicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamRevisarAuditoriaCajasChicas() {
      // if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
      //    $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
      //    return false;
      // }
      return true;
   }

   protected function mxRevisarAuditoriaCajasChicas($p_oSql) {
      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, TO_CHAR(A.tModifi,'YYYY-MM-DD HH24:MI') AS dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial, A.cEstado
               FROM E03DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
               WHERE A.cNroCch = '{$this->paData['CNROCCH']}' AND A.cEstado in ('A', 'B', 'O') ORDER BY A.cSerial";

      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[12] == 'A'){
            $laDataCh = ['CAPROBAR'=> 'A'];
         }
         $laFila = json_decode($laTmp[9], true);
         // print_r($laFila);

         if (isset($laFila['CCODEMP'])) {
            $lcCodEmp = $laFila['CCODEMP'];
            $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp1 = $p_oSql->fetch($R2);
            if (count($laTmp1) == 0 or !$laTmp1[0]){
               $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
            } else {
               $lcNomEmp = $laTmp1[0];
            }
         } else {
            $lcCodEmp = '0000';
            $lcNomEmp = 'N/C';
         }


         // print_r($laFila);
         $laDatos[] = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
            'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
            'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> $laTmp[10],
            'NMONBAS'=>$laFila['NMONBAS'], 'NMONEXO' => $laFila['NMONEXO'], 'NMONIGV'=> $laFila['NMONIGV'],
            'NMONTIR'=> $laFila['NMONTIR'], 'NMONOTR'=> $laFila['NMONOTR'], 'CSERIAL'=> $laTmp[11], 'CESTADO'=> $laTmp[12],
            'COBSERV'=> $laFila['AAUDITO'][0]['COBSERV']];
      }
      // print_r($laDataCh);


      $lcSql = "SELECT A.cNroCch, A.cGlosa, A.nMonto, A.cEstado, B.nMonMax, C.cDescri, TO_CHAR(A.tEnvio,'YYYY-MM-DD HH24:MI') FROM E03MCCH A
                  INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '230' AND SUBSTRING(C.cCodigo, 1, 2) = A.cEstado
                  WHERE cNroCch = '{$this->paData['CNROCCH']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (count($laTmp) == 0) {
         $this->pcError = 'NÚMERO DE CAJA CHICA NO EXISTE';
         return;
      }
      $this->paData = ['CNROCCH'=> $laTmp[0], 'CGLOSA'=> $laTmp[1], 'NMONTO'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'NMONMAX'=> $laTmp[4], 'CDESCRI'=> $laTmp[5], 'TENVIO'=> $laTmp[6]];

      $this->paDatos = $laDatos;
      $this->paDataCh = $laDataCh;
      return true;
   }



   /////////////////////////////////////
   // Carga detalle caja chica
   // 18-09-2022
   ////////////////////////////
   public function omCajasChicas() {
      $llOk = $this->mxValParamCajasChicas();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCajasChicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamCajasChicas() {
      // print_r($this->paData);
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }


   protected function mxCajasChicas($p_oSql) {
      $lcSql = "SELECT A.cNroCch, A.cGlosa, A.nMonto, A.cEstado, B.nMonMax, C.cDescri, A.tEnvio FROM E03MCCH A
                  INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '230' AND SUBSTRING(C.cCodigo, 1, 2) = A.cEstado
                  WHERE cNroCch = '{$this->paData['CNROCCH']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (count($laTmp) == 0) {
         $this->pcError = 'NÚMERO DE CAJA CHICA NO EXISTE';
         return false;
      }
      $this->paData = ['CNROCCH'=> $laTmp[0], 'CGLOSA'=> $laTmp[1], 'NMONTO'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'NMONMAX'=> $laTmp[4], 'CDESCRI'=> $laTmp[5], 'TENVIO'=> $laTmp[6]];
      $laEstado = 'A';
      if ($laTmp[3] == 'O'){
         $laEstado = 'O';
      }

      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial
               FROM E03DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
               WHERE A.cNroCCh = '{$this->paData['CNROCCH']}' AND A.cEstado NOT IN ('X') ORDER BY A.cSerial";
      # AND A.cEstado in ('$laEstado', 'B')


      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laFila = json_decode($laTmp[9], true);
         if (isset($laFila['CCODEMP'])) {
            $lcCodEmp = $laFila['CCODEMP'];
            $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp1 = $p_oSql->fetch($R2);
            if (count($laTmp1) == 0 or !$laTmp1[0]) {
               $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
            } else {
               $lcNomEmp = $laTmp1[0];
            }
         } else {
            $lcCodEmp = '0000';
            $lcNomEmp = 'N/C';
         }

         $this->paDatos[] = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
            'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
            'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> $laTmp[10], 'NMONBAS'=> $laFila[1], 'NMONIGV'=> $laFila[2], 'NMONEXO' => $laFila[3], 'NMONTIR'=> $laFila[4],
            'CSERIAL'=> $laTmp[11]];
      }

      // print_r($this->paDatos);
      return true;
   }

   ////////////////////////////////////////
   // Revisar detalle comrpobante
   // 19-09-2022  GAR
   ///////////////////////////////////////
   public function omRevisarDetalleComprobante(){
      $llOk = $this->mxValParamRevisarDetalleComprobante();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRevisarDetalleComprobante($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamRevisarDetalleComprobante(){
      if (!isset($this->paData['CSERIAL']) or !preg_match('/^[A-Z0-9]{5}$/', $this->paData['CSERIAL'])) {
         $this->pcError = "CSERIAL NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;

   }
   protected function mxRevisarDetalleComprobante($p_oSql){
      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, TRIM(A.cNroCom) AS cNroCom, A.dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial, A.cEstado
               FROM E03DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
               WHERE A.cSerial = '{$this->paData['CSERIAL']}' AND A.cEstado in ('A', 'B', 'O') ORDER BY A.cSerial";
      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laFila = json_decode($laTmp[9], true);
         if (isset($laFila['CCODEMP'])) {
            $lcCodEmp = $laFila['CCODEMP'];
            $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp1 = $p_oSql->fetch($R2);
            if (count($laTmp1) == 0 or !$laTmp1[0]) {
               $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
            } else {
               $lcNomEmp = $laTmp1[0];
            }
         } else {
            $lcCodEmp = '0000';
            $lcNomEmp = 'N/C';
         }
         $this->paData = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
            'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
            'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> number_format($laTmp[10],2),
            'NMONBAS'=> number_format($laFila['NMONBAS'],2), 'NMONEXO' => number_format($laFila['NMONEXO'],2),
            'NMONIGV'=> number_format($laFila['NMONIGV'],2), 'NMONTIR'=> number_format($laFila['NMONTIR'],2),
            'NMONOTR'=> number_format($laFila['NMONOTR'],2), 'CSERIAL'=> $laTmp[11], 'CESTADO'=> $laTmp[12],
            'COBSERV'=> $laFila['AAUDITO'][0]['COBSERV']];
      }
      $this->paData;
      return true;
   }

   // ----------------------------------------------------------- 
   // Subir comprobante caja chica 
   // 2022-09-15 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omSubirComprobante($lcSerial) {
      $llOk = $this->mxValParamSubirComprobante();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSubirComprobante($lcSerial);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamSubirComprobante() {
      if ($this->paFile["error"] != "0" || $this->paFile["size"] > 10485760 || $this->paFile["type"] != "application/pdf"){
         $this->pcError = "ARCHIVO NO VALIDO";
         return false;
      }
      return true;
   }
   protected function mxSubirComprobante($p_oNomDoc){
      $lNomArch = $p_oNomDoc.".pdf";
      // $lcNroCch = $this->paData["CNROCCH"];
      // $lcFolder = "CajaChicaComp/"; //LOCAL
      $lcFolder = "./Docs/CajaChica/"; //SERVIDOR

      if (!file_exists($lcFolder)){
         mkdir($lcFolder, 0777, true);
         if (file_exists($lcFolder)){
            if (!move_uploaded_file($this->paFile["tmp_name"], $lcFolder.$lNomArch)){
               $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO";
               return false;
            }
         }
      } else {
         if (!move_uploaded_file($this->paFile["tmp_name"], $lcFolder.$lNomArch)){
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO";
            return false;
         }
      }
      return true;
   }

   // ----------------------------------------------------------- 
   // Editar comprobante caja chica 
   // 2022-08-08 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omEditarComprobanteCajaChica(){
      $llOk = $this->mxValParamEliminarComprobanteCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEditarComprobanteCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxEditarComprobanteCajaChica($p_oSql){
      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, TRIM(A.cNroCom) AS cNroCom, A.dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial
               FROM E03DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
               WHERE A.cSerial = '{$this->paData['CSERIAL']}' AND A.cEstado = 'A' ORDER BY A.cSerial";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laFila = json_decode($laTmp[9], true);
         if (isset($laFila['CCODEMP'])) {
            $lcCodEmp = $laFila['CCODEMP'];
            $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp1 = $p_oSql->fetch($R2);
            if (count($laTmp1) == 0 or !$laTmp1[0]) {
               $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
            } else {
               $lcNomEmp = $laTmp1[0];
            }
         } else {
            $lcCodEmp = '0000';
            $lcNomEmp = 'N/C';
         }

         $this->paData = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
            'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
            'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> number_format($laTmp[10],2),
            'NMONBAS'=> number_format($laFila['NMONBAS'],2), 'NMONIGV'=> number_format($laFila['NMONIGV'],2),
            'NMONEXO' => number_format($laFila['NMONEXO'],2), 'NMONTIR'=> number_format($laFila['NMONTIR'],2), 'NMONOTR'=> number_format($laFila['NMONOTR'],2),
            'CSERIAL'=> $laTmp[11]];
      }
      $this->paData;
      return true;
   }

   // ----------------------------------------------------------- 
   // Eliminar comprobante caja chica 
   // 2022-09-15 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omEliminarComprobanteCajaChica(){
      $llOk = $this->mxValParamEliminarComprobanteCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEliminarComprobanteCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }

      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamEliminarComprobanteCajaChica(){
      if (!isset($this->paData['CSERIAL']) or !preg_match('/^[A-Z0-9]{5}$/', $this->paData['CSERIAL'])) {
         $this->pcError = "CSERIAL NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxEliminarComprobanteCajaChica($p_oSql){
      $lcSql = "DELETE FROM E03DCCH WHERE cSerial = '{$this->paData['CSERIAL']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ELIMINAR EL COMPROBANTE";
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------- 
   // Enviar comprobantes caja chica 
   // 2022-09-15 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omEnviarCajasChicas(){
      $llOk = $this->mxValParamEnviarCajasChicas();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarCajasChicas($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamEnviarCajasChicas(){
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      } else if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[A-Z0-9]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxEnviarCajasChicas($p_oSql){
      //VALIDA QUE SE TENGAN COMPROBANTES INGRESADOS
      $lcSql = "SELECT COUNT(*) FROM E03DCCH WHERE CNROCCH = '{$this->paData['CNROCCH']}' AND CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL ENVIAR A REVISAR LA CAJA CHICA.";
         return false;
      }
      $lnTotCmp = $p_oSql->fetch($RS)[0];
      if ($lnTotCmp == 0) {
         $this->pcError = "NO SE TIENE COMPROBANTES PARA ENVIAR A CONTABILIDAD";
         return false;
      }

      //OBTIENE FECHA DE CREACION DE CAJA CHICA
      $lcSql = "SELECT TFECHA FROM E03MCCH WHERE CNROCCH = '{$this->paData['CNROCCH']}' AND CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL ENVIAR A REVISAR LA CAJA CHICA.";
         return false;
      }
      $ldCajMes = $p_oSql->fetch($RS)[0];
      $ldCajMes = substr($ldCajMes, 0, 7);

      //OBTIENE DIA MAXIMO COMO PLAZO PAR ENVIO A CONTABILIDAD
      $ldMesAnterior = date('Y-m', strtotime('-1 month'));
      $ldMesActual = date('Y-m');
      $lcDia = date('d');
      $lcSql = "SELECT MDATOS::JSON->>'CPLAZO' FROM S01TVAR WHERE CNOMVAR = 'CCH.GCPLAZO'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL ENVIAR A REVISAR LA CAJA CHICA.";
         return false;
      }
      $lcFecLim = $p_oSql->fetch($RS)[0];
      if ($ldCajMes < $ldMesActual ) {
         if ($ldCajMes == $ldMesAnterior AND $lcDia > $lcFecLim ) {
            $this->pcError = 'EL PLAZO PARA ENVIAR A CONTABILIZAR LA CAJA CHICA TERMINÓ';
            return;
         }
      }

      $lcSql = "SELECT A.ctipo FROM e03tcch A INNER JOIN e03mcch B ON B.ccajach = A.ccajach WHERE B.cnrocch = '{$this->paData['CNROCCH']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if ($laTmp[0] == 'A'){
         $lcEstado = 'C';
      } else {
         $lcEstado = 'B';
      }
      $lcSql = "UPDATE E03MCCH SET cEstado = '$lcEstado', tEnvio = NOW(), nMonto = '{$this->paData['NTOTAL']}', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cNroCch = '{$this->paData['CNROCCH']}'";
      // print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR EL ESTADO DE LA CAJA CHICA";
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------- 
   // Aprobar comprobantes caja chica 
   // 2022-09-21 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omComprobantesAprobar(){
      $llOk = $this->mxValParamComprobantesAprobar();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxComprobantesAprobar($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamComprobantesAprobar(){
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxComprobantesAprobar($p_oSql){
      $lcSql = "SELECT cSerial FROM E03DCCH WHERE cNroCch = '{$this->paData['CNROCCH']}' AND cEstado = 'A' ORDER BY cSerial";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CSERIAL'=> $laTmp[0]];
      }
      $this->paDatos = $laDatos;
      return true;
   }

   // ----------------------------------------------------------- 
   // Observar comprobantes caja chica 
   // 2022-09-21 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omComprobantesObservar(){
      $llOk = $this->mxValParamComprobantesObservar();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxComprobantesObservar($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamComprobantesObservar(){
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxComprobantesObservar($p_oSql){
      $lcSql = "SELECT cSerial FROM E03DCCH WHERE cNroCch = '{$this->paData['CNROCCH']}' AND cEstado = 'A' ORDER BY cSerial";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CSERIAL'=> $laTmp[0]];
      }
      $this->paDatos = $laDatos;
      return true;
   }


   // ----------------------------------------------------------- 
   // Aprobar comprobantes caja chica 
   // 2022-09-21 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omAprobarCajaChica(){
      $llOk = $this->mxValParamomAprobarCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxomAprobarCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamomAprobarCajaChica(){
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      } else if(!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxomAprobarCajaChica($p_oSql){
      $lcSql = "SELECT cEstado FROM E03DCCH WHERE cNroCch = '{$this->paData['CNROCCH']}' ORDER BY cSerial";
      $R1 = $p_oSql->omExec($lcSql);
      $lcEstado = 'C';
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[0] == 'O'){
            $lcEstado = 'O';
         }
      }
      $lcSql = "UPDATE E03MCCH SET cEstado = '$lcEstado', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cNroCch = '{$this->paData['CNROCCH']}'";
      // print_r($lcSql);
      // die;
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR EL ESTADO DE LA CAJA CHICA";
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------- 
   // Observar detalle comprobantes caja chica 
   // 2022-09-21 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omObservarDetalleComprobante(){
      $llOk = $this->mxValParamObservarDetalleComprobante();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxObservarDetalleComprobante($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamObservarDetalleComprobante(){
      if (!isset($this->paData['CSERIAL']) or !preg_match('/^[A-Z0-9]{5}$/', $this->paData['CSERIAL'])) {
         $this->pcError = "CSERIAL NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;

   }
   protected function mxObservarDetalleComprobante($p_oSql){
      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, TRIM(A.cNroCom) AS cNroCom, A.dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial
               FROM E03DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
               WHERE A.cSerial = '{$this->paData['CSERIAL']}' ORDER BY A.cSerial";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $laFila = json_decode($laTmp[9], true);
      if (isset($laFila['CCODEMP'])) {
         $lcCodEmp = $laFila['CCODEMP'];
         $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
         $R2 = $p_oSql->omExec($lcSql);
         $laTmp1 = $p_oSql->fetch($R2);
         if (count($laTmp1) == 0 or !$laTmp1[0]) {
            $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
         } else {
            $lcNomEmp = $laTmp1[0];
         }
      } else {
         $lcCodEmp = '0000';
         $lcNomEmp = 'N/C';
      }
      $this->paData = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
         'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
         'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> number_format($laTmp[10],2),
         'NMONBAS'=> number_format($laFila['NMONBAS'],2), 'NMONEXO' => number_format($laFila['NMONEXO'],2),
         'NMONIGV'=> number_format($laFila['NMONIGV'],2), 'NMONTIR'=> number_format($laFila['NMONTIR'],2),
         'NMONOTR'=> number_format($laFila['NMONOTR'],2), 'CSERIAL'=> $laTmp[11],
         'COBSERV'=> $laFila['AAUDITO'][0]['COBSERV'], 'DOBSERV'=> $laFila['AAUDITO'][0]['TMODIFI']];
      $this->paData;
      return true;
   }

   // ----------------------------------------------------------- 
   // Anular comprobante caja chica 
   // 2022-09-15 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omAnularComprobanteCajaChica(){
      $llOk = $this->mxValParamAnularComprobanteCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAnularComprobanteCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }

      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamAnularComprobanteCajaChica(){
      if (!isset($this->paData['CSERIAL']) or !preg_match('/^[A-Z0-9]{5}$/', $this->paData['CSERIAL'])) {
         $this->pcError = "CSERIAL NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxAnularComprobanteCajaChica($p_oSql){
      $lcSql = "UPDATE E03DCCH SET cEstado = 'X' WHERE cSerial = '{$this->paData['CSERIAL']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ANULAR EL COMPROBANTE";
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------- 
   // Contabilidad caja chica 
   // 2022-10-05 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omInitContabilidadCajaChica(){
      // $llOk = $this->mxValParamInitContabilidadCajaChica();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitContabilidadCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamInitContabilidadCajaChica() {

   }
   protected function mxInitContabilidadCajaChica($p_oSql){

      $laDatos = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('00','01', '02', '03', '12', 'PM')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE DOCUMENTOS NO DEFINIDOS [087]";
         return false;
      }
      $laData = ['ATIPDOC'=> $laDatos, 'ATIPOPE'=> ''];
      // Tipo de operaciones
      $laDatos = [];
      $lcSql = "SELECT cCodOpe, TRIM(cDescri) FROM E02TOPE WHERE cEstado = 'A' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE OPERACIONES NO DEFINIDOS";
         return false;
      }
      $laData['ATIPOPE'] = $laDatos;
      $laDatos = [];
      // Revision de cajas chicas
      $lcMActual = date("m");
      $lcAActual = date("Y");
      $lcDActual = date("d");
      $lcMPasado = $lcMActual - 1;
      if ($lcMPasado == 0){
         $lcMPasado = 12;
         $lcAPasado = $lcAActual - 1;
      } else {
         $lcAPasado = $lcAActual;
      }
      $lcDManana = $lcDActual;
      //echo $lcMActual.' - '.$lcAActual.' - '.$lcMPasado.' - '.$lcDActual.'--';
      if ($lcMPasado == 2 and ($lcDActual >= 29 or $lcDActual <= 31)) {
         $lcFPasado = $lcAPasado.'-'.$lcMPasado.'-28';

      } else {
         if ($lcDActual == 1) {
            //echo '...............';
            $lcFPasado = $lcAActual.'-'.$lcMPasado.'-'.$lcDActual;
         } else {
            //echo '*****************';
            //echo $lcMPasado;
            //echo '*****************';
            //echo $lcDActual;
            $lcFPasado = $lcAActual.'-'.$lcMPasado.'-'.($lcDActual-1);
         }
      }
      $lcFActual = $lcAActual.'-'.$lcMActual.'-'.$lcDManana;
      $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, TO_CHAR(A.tEnvio, 'YYYY-MM-DD HH24:MI'), A.cUsuRes, A.cGlosa, A.mDatos, A.nMonto, A.cEstado, D.cDescri AS cDesEst FROM E03MCCH A
               INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
               INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
              LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '230' AND SUBSTRING(D.cCodigo, 1, 1) = A.cEstado
              WHERE B.cTipo in ('A', 'B') AND A.cEstado in ('C', 'D', 'E') AND A.TENVIO BETWEEN '{$lcFPasado}' AND '{$lcFActual}' ORDER BY A.tmodifi";
      //echo $lcSql;
      // $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos, A.nMonto, A.cEstado, D.cDescri AS cDesEst FROM E03MCCH A
      //          INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
      //          INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
      //          LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '230' AND SUBSTRING(D.cCodigo, 1, 1) = A.cEstado
      //          WHERE B.cTipo in ('A', 'B') AND A.cEstado in ('C', 'E', 'G') ORDER BY A.tEnvio";
      $R2 = $p_oSql->omExec($lcSql);
      while ($laTmp2 = $p_oSql->fetch($R2)) {
         $lcDatos = json_decode($laTmp2[6], true);
         $laDatos[] = ['CNROCCH'=> $laTmp2[0], 'CCAJACH'=> $laTmp2[1], 'CDESCRI'=> $laTmp2[2], 'TENVIO'=> $laTmp2[3],
            'CUSURES'=> $laTmp2[4], 'CGLOSA'=> $laTmp2[5], 'MDATOS'=> $lcDatos, 'NMONTO'=> $laTmp2[7], 'CESTADO'=> $laTmp2[8], 'CDESEST'=> $laTmp2[9]];

      }
      // print_r($laDatos);

      $this->paData = $laData;
      $this->paDatos = $laDatos;
      // print_r($laDatos);
      return true;



   }
   // ----------------------------------------------------------- 
   // Contabilidad revisar caja chica 
   // 2022-10-05 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omRevisarContabilidadCajasChicas(){
      // $llOk = $this->mxValParamRevisarContabilidadCajasChicas();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRevisarContabilidadCajasChicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamRevisarContabilidadCajasChicas() {
      // if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
      //    $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
      //    return false;
      // }
      return true;
   }

   protected function mxRevisarContabilidadCajasChicas($p_oSql) {
      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial, A.cEstado
               FROM E03DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
               WHERE A.cNroCch = '{$this->paData['CNROCCH']}' AND A.cEstado in ('A', 'B', 'O') ORDER BY A.cSerial";

      // print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[12] == 'A'){
            $laDataCh = ['CAPROBAR'=> 'A'];
         }
         $laFila = json_decode($laTmp[9], true);
         // print_r($laFila);

         if (isset($laFila['CCODEMP'])) {
            $lcCodEmp = $laFila['CCODEMP'];
            $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp1 = $p_oSql->fetch($R2);
            if (count($laTmp1) == 0 or !$laTmp1[0]){
               $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
            } else {
               $lcNomEmp = $laTmp1[0];
            }
         } else {
            $lcCodEmp = '0000';
            $lcNomEmp = 'N/C';
         }


         // print_r($laFila);
         $laDatos[] = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
            'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
            'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> $laTmp[10],
            'NMONBAS'=>$laFila['NMONBAS'], 'NMONEXO' => $laFila['NMONEXO'], 'NMONIGV'=> $laFila['NMONIGV'],
            'NMONTIR'=> $laFila['NMONTIR'], 'NMONOTR'=> $laFila['NMONOTR'], 'CSERIAL'=> $laTmp[11], 'CESTADO'=> $laTmp[12],
            'COBSERV'=> $laFila['AAUDITO'][0]['COBSERV']];
      }
      // print_r($laDataCh);


      $lcSql = "SELECT A.cNroCch, A.cGlosa, A.nMonto, A.cEstado, B.nMonMax, C.cDescri, TO_CHAR(A.tEnvio,'YYYY-MM-DD HH24:MI'), B.cTipo, D.cDescri FROM E03MCCH A
                  INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '230' AND SUBSTRING(C.cCodigo, 1, 2) = A.cEstado
                  INNER JOIN S01TCCO D ON D.cCencos = B.cCajaCh
                  WHERE cNroCch = '{$this->paData['CNROCCH']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (count($laTmp) == 0) {
         $this->pcError = 'NÚMERO DE CAJA CHICA NO EXISTE';
         return;
      }
      $this->paData = ['CNROCCH'=> $laTmp[0], 'CGLOSA'=> $laTmp[1], 'NMONTO'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'NMONMAX'=> $laTmp[4], 'CDESCRI'=> $laTmp[5], 'TENVIO'=> $laTmp[6], 'CTIPO'=> $laTmp[7], 'CCENDES'=> $laTmp[8]];

      $this->paDatos = $laDatos;
      $this->paDataCh = $laDataCh;
      return true;
   }

   // ----------------------------------------------------------- 
   // Reporte de caja chica 
   // 2022-11-16 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omReporteCajaChicaPDF(){
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValReporteCajaChicaPDF($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      $lo = new CRepCajaChica2022();
      $lo->paData = $this->paData;
      $lo->paDatos = $this->paDatos;
      $llOk = $lo->mxReporteCajaChicaPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxValReporteCajaChicaPDF($p_oSql){
      // Datos de la caja chica
      $lcSql = "SELECT A.CNROCCH, A.CCAJACH, B.CDESCRI, C.CDESCRI, A.TENVIO, REPLACE(D.CNOMBRE, '/', ' '), A.NMONTO, A.CGLOSA FROM E03MCCH A 
                  INNER JOIN S01TCCO B ON B.CCENCOS = A.CCAJACH
                  LEFT OUTER JOIN V_S01TTAB C ON C.CCODTAB = '230' AND SUBSTRING(C.CCODIGO,1,1) = A.CESTADO
                  INNER JOIN V_S01TUSU_1 D ON D.CCODUSU = A.CUSURES
                  WHERE A.CNROCCH = '{$this->paData['CNROCCH']}'";
      $R1 = $p_oSql->omExec($lcSql);
      if (count($R1) == 0) {
         $this->pcError = 'NÚMERO DE CAJA CHICA NO EXISTE';
         return;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CNROCCH'=> $laTmp[0], 'CCENCOS'=> $laTmp[1], 'CDESCEN'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'TENVIO'=> $laTmp[4],
            'CNOMBRE'=> $laTmp[5], 'NMONTO'=> $laTmp[6], 'CGLOSA'=> $laTmp[7]];
      }
      // Comprobantes de a caja chica
      $lcSql = "SELECT A.cTipDoc, A.cNroCom, TO_CHAR(A.dCompro,'YYYY-MM-DD HH24:MI') , C.cDescri, A.cGlosa, A.nMonto FROM E03DCCH A 
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
                  INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
                  WHERE A.cnrocch = '{$this->paData['CNROCCH']}' ORDER BY A.cSerial ASC";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CTIPDOC'=> $laTmp[0], 'CNROCOM'=> $laTmp[1], 'DCOMPRO'=> $laTmp[2], 'CDESOPE'=> $laTmp[3],
            'CGLOSA'=> $laTmp[4], 'CNRORUC'=> $this->paDatos[$i]['CNRORUC'], 'CRAZSOC'=> $this->paDatos[$i]['CRAZSOC'],
            'CCODEMP'=> $this->paDatos[$i]['CCODEMP'], 'CNOMEMP'=> $this->paDatos[$i]['CNOMEMP'], 'NMONTO'=> $laTmp[5]];
         $i++;
      }
      $this->paDatos = $laDatos;
      return true;
   }

   // ----------------------------------------------------------- 
   // Mandar caja chica Talavera Cont, Jesus Tesoreria
   // 2022-11-28 GAR Creacion  
   // -----------------------------------------------------------
   public function omMandarCajaChica($lcEstado){
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValPagarCajaChica();
      if (!$llOk) {
         return false;
      }


      $llOk = $this->mxPagarCajaChica($loSql, $lcEstado);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();

      return $llOk;
   }

   protected function mxValPagarCajaChica(){
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxPagarCajaChica($p_oSql, $lcEstado){
      $lcSql = "UPDATE E03MCCH SET CESTADO = '$lcEstado', TMODIFI = NOW() WHERE CNROCCH = '{$this->paData['CNROCCH']}'";
      // print_r($lcSql);
      // die;
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR EL ESTADO DE LA CAJA CHICA";
         return false;
      }
      return true;
   }



   ///////////////////////////////////////
   // Revision de auditoria detalle caja chichas
   // 2022-09-21  GAR Creacion
   ///////////////////////////////////////
   public function omRevisarPagarCajasChicas(){
      $llOk = $this->mxValParamPagarAuditoriaCajasChicas();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRevisarPagarCajasChicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamPagarAuditoriaCajasChicas() {
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxRevisarPagarCajasChicas($p_oSql) {
      $lcSql = "SELECT A.cTipDoc, B.cDescri AS cDesTip, A.cNroCom, A.dCompro, A.cCodOpe, TRIM(C.cDescri) AS cDesOpe, A.cGlosa, A.cNroRuc, D.cRazSoc, A.mDatos, A.nMonto, A.cSerial, A.cEstado
                  FROM E03DCCH A
                  LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND SUBSTRING(B.cCodigo, 1, 2) = A.cTipDoc
                  INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
                  LEFT JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
                  WHERE A.cNroCch = '{$this->paData['CNROCCH']}' AND A.cEstado in ('A', 'B', 'O') ORDER BY A.cSerial";
      // print_r($lcSql);
      // die;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[12] == 'A'){
            $laDataCh = ['CAPROBAR'=> 'A'];
         }
         $laFila = json_decode($laTmp[9], true);
         // print_r($laFila);
         if (isset($laFila['CCODEMP'])) {
            $lcCodEmp = $laFila['CCODEMP'];
            $lcSql = "SELECT cNombre FROM V_S01TUSU_1 WHERE cCodUsu = '$lcCodEmp'";
            $R2 = $p_oSql->omExec($lcSql);
            $laTmp1 = $p_oSql->fetch($R2);
            if (count($laTmp1) == 0 or !$laTmp1[0]){
               $lcNomEmp = '* USUARIO [$lcCodEmp] NO EXISTE';
            } else {
               $lcNomEmp = $laTmp1[0];
            }
         } else {
            $lcCodEmp = '0000';
            $lcNomEmp = 'N/C';
         }
         // print_r($laFila);
         $laDatos[] = ['CTIPDOC'=> $laTmp[0], 'CDESTIP'=> $laTmp[1], 'CNROCOM'=> $laTmp[2], 'DCOMPRO'=> $laTmp[3],
            'CCODOPE'=> $laTmp[4], 'CDESOPE'=> $laTmp[5], 'CGLOSA'=> $laTmp[6], 'CNRORUC'=> $laTmp[7],
            'CRAZSOC'=> $laTmp[8], 'CCODEMP'=> $lcCodEmp, 'CNOMEMP'=> $lcNomEmp, 'NMONTO'=> $laTmp[10],
            'NMONBAS'=>$laFila['NMONBAS'], 'NMONEXO' => $laFila['NMONEXO'], 'NMONIGV'=> $laFila['NMONIGV'],
            'NMONTIR'=> $laFila['NMONTIR'], 'NMONOTR'=> $laFila['NMONOTR'], 'CSERIAL'=> $laTmp[11], 'CESTADO'=> $laTmp[12],
            'COBSERV'=> $laFila['AAUDITO'][0]['COBSERV']];
      }
      // Carga datos de la caja chica
      $lcSql = "SELECT A.cNroCch, A.cGlosa, A.nMonto, A.cEstado, B.nMonMax, C.cDescri, TO_CHAR(A.tEnvio,'YYYY-MM-DD HH24:MI'), E.CCENCOS, E.CDESCRI, D.CCODUSU, REPLACE(D.CNOMBRE,'/',' '), B.NMONTO 
                  FROM E03MCCH A
                  INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '230' AND SUBSTRING(C.cCodigo, 1, 2) = A.cEstado
                  INNER JOIN V_S01TUSU_1 D ON D.CCODUSU = B.CUSURES
                  INNER JOIN S01TCCO E ON E.CCENCOS = B.CCAJACH
                  WHERE A.cNroCch = '{$this->paData['CNROCCH']}'";


      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (count($laTmp) == 0) {
         $this->pcError = 'NÚMERO DE CAJA CHICA NO EXISTE';
         return;
      }
      $this->paData = ['CNROCCH'=> $laTmp[0], 'CGLOSA'=> $laTmp[1], 'NMONTO'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'NMONMAX'=> $laTmp[4], 'CDESCRI'=> $laTmp[5], 'TENVIO'=> $laTmp[6],
         'CCENCOS'=> $laTmp[7], 'CDESCEN'=> $laTmp[8], 'CUSURES'=> $laTmp[9], 'CNOMUSU'=> $laTmp[10], 'NMONTOM'=> $laTmp[11]];
      $this->paDatos = $laDatos;
      // print_r($this->paDatos);
      $this->paDataCh = $laDataCh;
      // die;
      return true;
   }

   // ----------------------------------------------------------- 
   // Bandeja para pagar caja chica 
   // 2022-11-22 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omInitPagarCajaChica(){
      $llOk = $this->mxValParamInitPagarCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitPagarCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamInitPagarCajaChica(){
      return true;
   }

   protected function mxInitPagarCajaChica($p_oSql){
      // Tipo de documentos
      $laDatos = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('00','01', '02', '03', '12', 'PM')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE DOCUMENTOS NO DEFINIDOS [087]";
         return false;
      }
      $laData = ['ATIPDOC'=> $laDatos, 'ATIPOPE'=> ''];
      // Tipo de operaciones
      $laDatos = [];
      $lcSql = "SELECT cCodOpe, TRIM(cDescri) FROM E02TOPE WHERE cEstado = 'A' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE OPERACIONES NO DEFINIDOS";
         return false;
      }
      $laData['ATIPOPE'] = $laDatos;
      $laDatos = [];
      // Revision de cajas chicas
      // $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos FROM E03MCCH A
      //          INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
      //          INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
      //          WHERE B.cTipo = 'A' AND A.cEstado IN ('B', 'O') ORDER BY A.tEnvio";
      $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos, A.nMonto, A.cEstado, D.cDescri AS cDesEst FROM E03MCCH A
               INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
               INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
               LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '230' AND SUBSTRING(D.cCodigo, 1, 1) = A.cEstado
               WHERE A.cEstado IN ('F', 'G') ORDER BY A.tEnvio";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcDatos = json_decode($laTmp[6], true);
         $laDatos[] = ['CNROCCH'=> $laTmp[0], 'CCAJACH'=> $laTmp[1], 'CDESCRI'=> $laTmp[2], 'TENVIO'=> $laTmp[3],
            'CUSURES'=> $laTmp[4], 'CGLOSA'=> $laTmp[5], 'MDATOS'=> $lcDatos, 'NMONTO'=> $laTmp[7], 'CESTADO'=> $laTmp[8], 'CDESEST'=> $laTmp[9]];
      }
      $this->paData = $laData;
      $this->paDatos = $laDatos;
      return true;


   }

   ///////////////////////////////////////
   // Buscar caja chichas
   // 2022-12-01  GAR Creacion
   ///////////////////////////////////////
   public function omInitBuscarCajaChica(){
      $llOk = $this->mxValParamInitBuscarCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitBuscarCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamInitBuscarCajaChica() {
      return true;
   }

   protected function mxInitBuscarCajaChica($p_oSql) {
      // Tipo de documentos
      $laDatos = [];
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 2), TRIM(cDescri) FROM V_S01TTAB WHERE cCodTab = '087' AND SUBSTRING(cCodigo, 1, 2) IN ('00','01', '02', '03', '12', 'PM')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE DOCUMENTOS NO DEFINIDOS [087]";
         return false;
      }
      $laData = ['ATIPDOC'=> $laDatos, 'ATIPOPE'=> ''];
      // Tipo de operaciones
      $laDatos = [];
      $lcSql = "SELECT cCodOpe, TRIM(cDescri) FROM E02TOPE WHERE cEstado = 'A' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODIGO'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "TIPO DE OPERACIONES NO DEFINIDOS";
         return false;
      }
      $laData['ATIPOPE'] = $laDatos;
      $laDatos = [];
      $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos, A.nMonto, A.cEstado, D.cDescri AS cDesEst FROM E03MCCH A
                  INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
                  INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
                  LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '230' AND SUBSTRING(D.cCodigo, 1, 1) = A.cEstado
                  WHERE A.cEstado NOT IN ('X', 'A') ORDER BY A.tEnvio DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcDatos = json_decode($laTmp[6], true);
         $laDatos[] = ['CNROCCH'=> $laTmp[0], 'CCAJACH'=> $laTmp[1], 'CDESCRI'=> $laTmp[2], 'TENVIO'=> $laTmp[3],
            'CUSURES'=> $laTmp[4], 'CGLOSA'=> $laTmp[5], 'MDATOS'=> $lcDatos, 'NMONTO'=> $laTmp[7], 'CESTADO'=> $laTmp[8], 'CDESEST'=> $laTmp[9]];
      }
      $this->paData = $laData;
      $this->paDatos = $laDatos;
      return true;
   }

   // ----------------------------------------------------------- 
   // Init Crear caja chica 
   // 2022-12-12 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omInitCrearCajaChica(){
      $llOk = $this->mxValParamInitCrearCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCrearCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamInitCrearCajaChica() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxInitCrearCajaChica($p_oSql){
      $laDatos = [];
      $lcSql = "SELECT B.CCENCOS, B.CDESCRI FROM E03TCCH A 
                  INNER JOIN S01TCCO B ON B.CCENCOS = A.CCAJACH
                  WHERE A.CCODUSU = '{$this->paData['CCODUSU']}' AND A.CESTADO = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCENCOS'=> $laTmp[0], 'CDESCRI'=> $laTmp[1]];
      }
      $this->paDatos = $laDatos;
      if (count($laDatos) == 0) {
         $this->pcError = "USTED NO PUEDE CREAR CAJAS CHICAS";
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------- 
   // Crear caja chica 
   // 2022-12-12 GAR Creacion  
   // ----------------------------------------------------------- 
   public function omCrearCajaChica(){
      $llOk = $this->mxValParamCrearCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCrearCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamCrearCajaChica() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CCENCOS']) || (!preg_match('/^[A-Z0-9]{3}$/', $this->paData['CCENCOS']))) {
         $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['TFECHA']) or $this->paData['TFECHA'] == '') {
         $this->pcError = 'FECHA NO DEFINIDA O INVÁLIDA';
         return false;
      }
      return true;
   }
   protected function mxCrearCajaChica($p_oSql){
      //valida que no
      $this->paData['TFECHA'] = $this->paData['TFECHA'];
      $lcSql = "SELECT CNROCCH FROM E03MCCH WHERE CCAJACH = '{$this->paData['CCENCOS']}' AND TFECHA = '{$this->paData['TFECHA']}-01'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CNROCCH'=> $laTmp[0]];
      }
      if (count($laDatos) != 0) {
         $this->pcError = 'YA EXISTE CAJA CHICA DEL MES INGRESADO';
         return;
      }
      $ldMesAnterior = date('Y-m', strtotime('-1 month'));
      $ldMesActual = date('Y-m');
      $lcDia = date('d');
      $lcSql = "SELECT MDATOS::JSON->>'CPLAZO' FROM S01TVAR WHERE CNOMVAR = 'CCH.GCPLAZO'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CREAR LA CAJA CHICA.";
         return false;
      }
      $lcFecLim = $p_oSql->fetch($RS)[0];
      if ($this->paData['TFECHA'] < $ldMesActual ) {
         if ($this->paData['TFECHA'] == $ldMesAnterior AND $lcDia > $lcFecLim ) {
            $this->pcError = 'EL PLAZO PARA REGISTRAR CAJA CHICA TERMINÓ';
            return;
         } elseif ($this->paData['TFECHA'] < $ldMesAnterior) {
            $this->pcError = 'EL PLAZO PARA REGISTRAR CAJA CHICA TERMINÓ';
            return;
         }
      }
      $lcSql = "SELECT MAX(CNROCCH) FROM E03MCCH";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if ($laFila[0] != null) {
         $lcNroCch = fxCorrelativo($laFila[0]);
      }
      $laMeses = ['ENERO', 'FEBRERO', 'MARZO', 'ABRIL', 'MAYO', 'JUNIO', 'JULIO', 'AGOSTO', 'SETIEMBRE', 'OCTUBRE', 'NOVIEMBRE', 'DICIEMBRE'];
      $lnFecha = $laMeses[substr($this->paData['TFECHA'], -2)-1] .' '. date('Y');
      $lcSql = "INSERT INTO E03MCCH 
                  VALUES('$lcNroCch', '{$this->paData['CCENCOS']}', 'A', NOW(), '{$this->paData['CCODUSU']}', 0.00, 
                  'CAJA CHICA - $lnFecha', '0000', '0000', '', '{$this->paData['CCODUSU']}', NOW(), '{$this->paData['TFECHA']}-1')";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO CREAR LA CAJA CHICA";
         return false;
      }
      return true;
   }



   // -----------------------------------------------------------
   // Listar cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omInitCajasChicas(){
      $llOk = $this->mxValParamInitCajasChicas();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitCajasChicas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamInitCajasChicas() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CCENCOS']) || (!preg_match('/^[A-Z0-9]{3}$/', $this->paData['CCENCOS']))) {
         $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxInitCajasChicas($p_oSql) {
      $lcSql = "SELECT A.CCAJACH, B.CDESCRI, C.CNOMBRE, D.CNOMBRE, A.CESTADO, A.NMONTO, A.NMONMAX, A.CCODUSU, A.CUSURES FROM E03TCCH A
                  INNER JOIN S01TCCO B ON A.ccajach = B.ccencos
                  INNER JOIN V_S01TUSU_1 C ON A.ccodusu = C.ccodusu
                  INNER JOIN V_S01TUSU_1 D ON A.cusures = D.ccodusu 
                  ORDER BY A.CESTADO, B.CDESCRI";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL LISTAR LOS CENTROS DE COSTOS CON CAJA CHICA";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCAJACH' => $laFila[0], 'CCCODES' => $laFila[1],
            'CUSURND' => str_replace('/', ' ', $laFila[2]), 'CUSUENT' => str_replace('/', ' ', $laFila[3]),
            'CESTADO' => $laFila[4], 'NMONTO' => $laFila[5], 'NMONMAX' => $laFila[6], 'CCODUSU' => $laFila[7], 'CUSURES' => $laFila[8]];
      }
      return true;
   }


   // -----------------------------------------------------------
   // Seleccionar centro de costros - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omSeleccionarCajaChica(){
      $llOk = $this->mxValParamSeleccionarCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarCajaChica($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamSeleccionarCajaChica() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CCAJACH']) || (!preg_match('/^[A-Z0-9]{3}$/', $this->paData['CCAJACH']))) {
         $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxSeleccionarCajaChica($p_oSql) {
      $lcSql = "SELECT A.CCAJACH, B.CDESCRI, C.CNOMBRE, D.CNOMBRE, A.CESTADO, A.NMONTO, A.NMONMAX, A.ccodusu, A.CUSURES, A.CESTADO FROM E03TCCH A
                  INNER JOIN S01TCCO B ON A.ccajach = B.ccencos
                  INNER JOIN V_S01TUSU_1 C ON A.ccodusu = C.ccodusu
                  INNER JOIN V_S01TUSU_1 D ON A.cusures = D.ccodusu 
                  WHERE CCAJACH = '{$this->paData['CCAJACH']}' 
                  ORDER BY A.CESTADO, B.CDESCRI";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL LISTAR LOS CENTROS DE COSTOS CON CAJA CHICA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = ['CCAJACH' => $laFila[0], 'CCCODES' => $laFila[1], 'CESTADO' => $laFila[4],
         'CUSURND' => str_replace('/', ' ', $laFila[2]), 'CUSUENT' => str_replace('/', ' ', $laFila[3]),
         'NMONTO' => $laFila[5], 'NMONMAX' => $laFila[6], 'CCODUSU' => $laFila[7], 'CUSURES' => $laFila[8], 'CESTADO' => $laFila[9]];
      return true;
   }


   // -----------------------------------------------------------
   // Busca usuario por codigo o nombre - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omBuscarUsuario(){
      $llOk = $this->mxValParamBuscarUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamBuscarUsuario() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CBUSCAR']) OR $this->paData['CBUSCAR'] == '') {
         $this->pcError = 'PARÁMETROS DE BÚSQUEDA NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxBuscarUsuario($p_oSql) {
      $lcSql = "SELECT CCODUSU, CNOMBRE, CNRODNI FROM V_S01TUSU_1 WHERE CCODUSU LIKE '%{$this->paData['CBUSCAR']}%' OR 
                                                        CNOMBRE LIKE '%{$this->paData['CBUSCAR']}%'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR BUSCAR EL USUARIO";
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON RESULTADOS PARA LA BÚSQUEDA";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCODUSU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ',$laFila[1]), 'CNRODNI' => $laFila[2]];
      }
      return true;
   }


   // -----------------------------------------------------------
   // Inhabilitar centro de costros - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omDeshabilitarCentroCostoCajaChica(){
      $llOk = $this->mxValParamDeshabilitarCentroCostoCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDeshabilitarCentroCostoCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamDeshabilitarCentroCostoCajaChica() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CCAJACH']) OR (!preg_match('/^[0-9A-Z]{3}$/', $this->paData['CCAJACH']))) {
         $this->pcError = 'CÓDIGO DE CENTRO DE COSTOS NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxDeshabilitarCentroCostoCajaChica($p_oSql) {
      $lcSql = "SELECT CCAJACH FROM E03TCCH WHERE CCAJACH = '{$this->paData['CCAJACH']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      $lcSql = "UPDATE E03TCCH SET CESTADO = 'I', CUSUCOD = '{$this->paData['CCODUSU']}', TMODIFI = NOW() WHERE CCAJACH = '{$this->paData['CCAJACH']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS ) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // habilitar centro de costros - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omHabilitarCentroCostoCajaChica(){
      $llOk = $this->mxValParamHabilitarCentroCostoCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxHabilitarCentroCostoCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamHabilitarCentroCostoCajaChica() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CCAJACH']) OR (!preg_match('/^[0-9A-Z]{3}$/', $this->paData['CCAJACH']))) {
         $this->pcError = 'CÓDIGO DE CENTRO DE COSTOS NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxHabilitarCentroCostoCajaChica($p_oSql) {
      $lcSql = "SELECT CCAJACH FROM E03TCCH WHERE CCAJACH = '{$this->paData['CCAJACH']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURIDO UN ERROR AL HABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      $lcSql = "UPDATE E03TCCH SET CESTADO = 'A', CUSUCOD = '{$this->paData['CCODUSU']}', TMODIFI = NOW() WHERE CCAJACH = '{$this->paData['CCAJACH']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS ) {
         $this->pcError = "HA OCURIDO UN ERROR AL HABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      return true;
   }

   // -----------------------------------------------------------
   // Editar valores centro de costros - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omEditarCentroCostosCajaChica(){
      $llOk = $this->mxValParamEditarCentroCostosCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEditarCentroCostosCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamEditarCentroCostosCajaChica() {
      if(!isset($this->paData['CCODUSU']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CCAJACH']) OR (!preg_match('/^[0-9A-Z]{3}$/', $this->paData['CCAJACH']))) {
         $this->pcError = 'CÓDIGO DE CENTRO DE COSTOS NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = 'CÓDIGO DE USUARIO RESPONSABLE DE RENDIR NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CUSURES']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CUSURES']))) {
         $this->pcError = 'CÓDIGO DE USUARIO PARA DEPÓSITO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['NMONTO']) OR $this->paData['NMONTO'] <= 0) {
         $this->pcError = 'MONTO MENSUAL NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['NMONMAX']) OR $this->paData['NMONMAX'] <= 0) {
         $this->pcError = 'MONTO MAXIMO POR COMPROBANTE NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxEditarCentroCostosCajaChica($p_oSql) {
      $lcSql = "SELECT CCAJACH FROM E03TCCH WHERE CCAJACH = '{$this->paData['CCAJACH']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      $lcSql = "UPDATE E03TCCH SET CCODUSU = '{$this->paData['CCODUSU']}', CUSURES = '{$this->paData['CUSURES']}', NMONTO = {$this->paData['NMONTO']}, 
                   NMONMAX = {$this->paData['NMONMAX']}, CUSUCOD = '{$this->paData['CUSUCOD']}', TMODIFI = NOW() WHERE CCAJACH = '{$this->paData['CCAJACH']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS ) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      return true;
   }


   // -----------------------------------------------------------
   // Buscar periodo maximo de envio a revision por area usuaria - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omVerPeriodoMaximoRevisionCajaChica(){
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerPeriodoMaximoRevisionCajaChica($loSql);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   public function mxVerPeriodoMaximoRevisionCajaChica($p_oSql) {
      $lcSql = "SELECT trim(MDATOS::JSON->>'CPLAZO') FROM S01TVAR WHERE CNOMVAR = 'CCH.GCPLAZO'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CPERIOD'] = $laFila[0];
      return true;
   }


   // -----------------------------------------------------------
   // Actualizar fecha limite de envio a revision de contabilidad - cajas chicas
   // 2023-06-05 GCQ Creacion
   // -----------------------------------------------------------
   public function omActualizarPeriodoMaximoRevisionCajaChica(){
      $llOk = $this->mxValParamActualizarPeriodoMaximoRevisionCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarPeriodoMaximoRevisionCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamActualizarPeriodoMaximoRevisionCajaChica() {
      if(!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9]{4}$/', $this->paData['CUSUCOD']))) {
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO';
         return false;
      } else if(!isset($this->paData['CPLAZO']) OR $this->paData['CPLAZO'] == "") {
         $this->pcError = 'FECHA MAXIMA DE ENVIO A REVISIÓN NO DEFINIDO O INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxActualizarPeriodoMaximoRevisionCajaChica($p_oSql) {
      $lcSql = "SELECT MDATOS FROM S01TVAR WHERE CNOMVAR = 'CCH.GCPLAZO'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS OR $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = json_decode($laFila[0], true);
      $this->paData['CPLAZO'] = strval(intval($this->paData['CPLAZO']));
      $lcPeriod = (strlen($this->paData['CPLAZO']) == 1) ? ('0'.$this->paData['CPLAZO']) : $this->paData['CPLAZO'];
      $lmDatos['CPLAZO'] = $lcPeriod;
      $lmDatos = json_encode($lmDatos, true);
      $lcSql = "UPDATE S01TVAR SET MDATOS = '{$lmDatos}', CUSUCOD = '{$this->paData['CUSUCOD']}', TMODIFI = NOW() WHERE CNOMVAR = 'CCH.GCPLAZO'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS ) {
         $this->pcError = "HA OCURIDO UN ERROR AL DESHABILITAR EL CENTRO DE COSTOS PARA CAJA CHICA";
         return false;
      }
      return true;
   }


   // -----------------------------------------------------------
   // Devolver caja chica a area usuaria
   // 2023-06-06 GCQ Creacion
   // -----------------------------------------------------------
   public function omDevolverCajaChicaParaEdicion(){
      $llOk = $this->mxValDevolverCajaChicaParaEdicion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDevolverCajaChicaParaEdicion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();

      return $llOk;
   }

   protected function mxValDevolverCajaChicaParaEdicion(){
      if (!isset($this->paData['CNROCCH']) or !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROCCH'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxDevolverCajaChicaParaEdicion($p_oSql){
      $lcSql = "SELECT mobserv FROM E03MCCH WHERE CNROCCH = '{$this->paData['CNROCCH']}' AND CESTADO <> 'C'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL DEVOLVER LA CAJA CHICA AL ÁREA USUARIA.";
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE PUEDE HACER LA DEVOLUCIÓN DE LA CAJA CHICA.";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmObserv = $laFila[0];

      //OBTENCION DATOS DEL USUARIO QUE REALIZA LA OBSERVACION
      $lcSql = "SELECT ccodusu, cNombre FROM V_S01TUSU_1 WHERE CCODUSU = '{$this->paData['CCODUSU']}' limit 1";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL DEVOLVER LA CAJA CHICA AL ÁREA USUARIA.";
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "EL USUARIO NO ESTA ACTIVO";
         return false;
      }
      $ldFecha = date('Y-m-d');
      $laFila = $p_oSql->fetch($RS);
      $this->paData['MOBSERV'] = strtoupper($this->paData['MOBSERV']);
      $lmObserv = (($lmObserv == '') ? '': $lmObserv.'\n')."{$ldFecha} | {$laFila[0]} - {$laFila[1]} *** {$this->paData['MOBSERV']}";

      $lcSql = "UPDATE E03MCCH SET CESTADO = 'A', CUSUCOD = '{$this->paData['CCODUSU']}', TMODIFI = NOW(), mobserv = '{$lmObserv}' WHERE CNROCCH = '{$this->paData['CNROCCH']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR EL ESTADO DE LA CAJA CHICA";
         return false;
      }
      return true;
   }


   // -----------------------------------------------------------
   // Anular Comprobante caja chica
   // 2023-06-07 Creacion
   // -----------------------------------------------------------
   public function omAnularComprobanteContabilidadCajaChica(){
      $llOk = $this->mxValAnularComprobanteContabilidadCajaChica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAnularComprobanteContabilidadCajaChica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();

      return $llOk;
   }

   protected function mxValAnularComprobanteContabilidadCajaChica(){
      if (!isset($this->paData['CSERIAL']) or !preg_match('/^[0-9A-Z]{5}$/', $this->paData['CSERIAL'])) {
         $this->pcError = "NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxAnularComprobanteContabilidadCajaChica($p_oSql){
      //SE OBTIENE EL NRO DE CAJA CHICA
      $lcSql = "SELECT CNROCCH FROM E03DCCH WHERE CSERIAL = '{$this->paData['CSERIAL']}' AND CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL ANULAR EL COMPROBANTE.";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CNROCCH'] = $laFila[0];

      // ANULAR EL COMPROBANTE
      $lcSql = "UPDATE E03DCCH SET CESTADO = 'X', CUSUCOD = '{$this->paData['CCODUSU']}', TMODIFI = NOW() WHERE CSERIAL = '{$this->paData['CSERIAL']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR EL ESTADO DE LA CAJA CHICA";
         return false;
      }

      // SE OBTIENE EL MONTO TOTAL DEL MAESTRO DE CAJA CHICA
      $lcSql = "SELECT SUM(NMONTO) FROM E03DCCH WHERE CNROCCH = '00C9' AND CESTADO <> 'X';";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL DEVOLVER LA CAJA CHICA AL ÁREA USUARIA.";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['NMONTO'] = $laFila[0];

      // ACTUALIZAR EL MONTO TOTAL DEL MAESTRO DE CAJA CHICA
      $lcSql = "UPDATE E03MCCH SET NMONTO = {$this->paData['NMONTO']}, CUSUCOD = '{$this->paData['CCODUSU']}', TMODIFI = NOW() WHERE CNROCCH = '{$this->paData['CNROCCH']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR EL ESTADO DE LA CAJA CHICA";
         return false;
      }
      return true;
   }




}

class CRepCajaChica2022 extends CBase {
   public function mxReporteCajaChicaPDF(){
      $lcFilRep = 'FILES/R'.rand().'.pdf';
      $i = 1;
      foreach ($this->paDatos as $laFila) {
         if ($laFila['CCODEMP'] == '0000'){
            $lcLinea = utf8_decode(fxNumber($i,3,0).' '.fxStringFixed($laFila['CTIPDOC'], 2).' '.fxStringFixed($laFila['CNROCOM'],16).' '.FxString($laFila['DCOMPRO'],10).'  '.fxStringFixed($laFila['CDESOPE'],32).'  '.fxStringFixed($laFila['CNRORUC'],11).'-'.fxStringFixed($laFila['CRAZSOC'],26).'  '.fxString($laFila['CGLOSA'],32).' '.fxNumber($laFila['NMONTO'],10,2));
         } else {
            $lcLinea = utf8_decode(fxNumber($i,3,0).' '.fxStringFixed($laFila['CTIPDOC'], 2).' '.fxStringFixed($laFila['CNROCOM'],16).' '.FxString($laFila['DCOMPRO'],10).'  '.fxStringFixed($laFila['CDESOPE'],32).'  '.fxStringFixed($laFila['CCODEMP'],4).'-'.fxStringFixed($laFila['CNOMEMP'],33).'  '.fxString($laFila['CGLOSA'],32).' '.fxNumber($laFila['NMONTO'],10,2));
         }
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
            $loPdf->Cell(50, 1,'                                            REPORTE COMPROBANTES DE CAJA CHICA                                           PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Cell(240, 3,'                                                                                                                                            ');
            $loPdf->Ln(4);
            $loPdf->SetFont('Courier', 'B', 7);
            $loPdf->Ln(3);
            $loPdf->Cell(264, 3,'Erp5140', 0, 0, 'L');
            $loPdf->Ln(2);
            $loPdf->Image('img/logo_trazos.png',10,10,35);
            $loPdf->Ln(1);
            $loPdf->SetFont('Courier', 'B', 9);
            $loPdf->Ln(3);
            $loPdf->Cell(50, 1,'CENTRO DE COSTO: '.$this->paData['CCENCOS'].' - '.$this->paData['CDESCEN'].'                                                                            FECHA: '.substr($this->paData['TENVIO'], 0, 10), 0, 0, 'L');
            $loPdf->Ln(1);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(6);
            $loPdf->Cell(0, 2, utf8_decode(' # TIP.  NRO.COMPR.      FECHA            OPERACIÓN                   PROVEEDOR - EMPLEADO                        GLOSA                    MONTO'), 0);
            $loPdf->Ln(2);
            $loPdf->Cell(0, 2, '__________________________________________________________________________________________________________________________________________________', 0);
            $loPdf->Ln(4);
            $loPdf->Cell(0, 2, '', 0);
            $lnRow = 4;
            $llTitulo = false;
         }
         $loPdf->Ln(2);
         $loPdf->SetFont('Courier', '', 8.5);
         $loPdf->Cell(195, 2, $lcLinea);
         $loPdf->Ln(2);
         $lnRow++;
         $llTitulo = ($lnRow == 40) ? true : false;
      }
      $loPdf->Ln(5);
      $loPdf->Cell(0, 2, '___ TOTAL ________________________________________________________________________________________________________________________________ S/. '.fxNumber($this->paData['NMONTO'],10,2), 0);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      return true;
   }
}

