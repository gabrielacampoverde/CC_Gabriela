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
                   WHERE A.cCajaCh = '$laFila[0]' AND A.cEstado IN ('A', 'O', 'B', 'C', 'E', 'F', 'G')"; 

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
               WHERE A.cNroCCh = '{$this->paData['CNROCCH']}' ORDER BY A.cSerial";
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
      $lcDManana = $lcDActual + 1;
      $lcFPasado = $lcAPasado.'-'.$lcMPasado.'-'.$lcDActual;
      $lcFActual = $lcAActual.'-'.$lcMActual.'-'.$lcDManana;
      $lcSql = "SELECT A.cNroCch, A.cCajaCh, C.cDescri, A.tEnvio, A.cUsuRes, A.cGlosa, A.mDatos, A.nMonto, A.cEstado, D.cDescri AS cDesEst FROM E03MCCH A
               INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
               INNER JOIN S01TCCO C ON C.cCenCos = B.cCajaCh
              LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '230' AND SUBSTRING(D.cCodigo, 1, 1) = A.cEstado
              WHERE B.cTipo in ('A', 'B') AND A.cEstado in ('C', 'E', 'G') AND A.TENVIO BETWEEN '{$lcFPasado}' AND '{$lcFActual}' ORDER BY A.tmodifi";
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


      $lcSql = "SELECT A.cNroCch, A.cGlosa, A.nMonto, A.cEstado, B.nMonMax, C.cDescri, TO_CHAR(A.tEnvio,'YYYY-MM-DD HH24:MI'), B.cTipo FROM E03MCCH A
                  INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '230' AND SUBSTRING(C.cCodigo, 1, 2) = A.cEstado
                  WHERE cNroCch = '{$this->paData['CNROCCH']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (count($laTmp) == 0) {
         $this->pcError = 'NÚMERO DE CAJA CHICA NO EXISTE';
         return;
      }
      $this->paData = ['CNROCCH'=> $laTmp[0], 'CGLOSA'=> $laTmp[1], 'NMONTO'=> $laTmp[2], 'CESTADO'=> $laTmp[3], 'NMONMAX'=> $laTmp[4], 'CDESCRI'=> $laTmp[5], 'TENVIO'=> $laTmp[6], 'CTIPO'=> $laTmp[7]];

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
         $loPdf->Ln(2,);
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
