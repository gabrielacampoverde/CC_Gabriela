<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Libs/fpdf/fpdf.php";
require_once "class/PHPExcel.php";
require_once "Clases/CEmailAuspicio.php";
date_default_timezone_set('America/Bogota');

// -----------------------------------------------------------
// Clase AUSPICIOS
// 2022-06-05 GCH Creacion
// -----------------------------------------------------------
class CAuspicio extends CBase {

   protected $laDatos, $laData;
   public $paData, $paDatos;

   public function __construct() {
      parent::__construct();
      // $this->pcFile = 'FILES/R' . rand() . '.pdf';
      $this->paData = $this->paDatos = $this->laData = $this->laDatos = null;
   }
   //------------------------------------------------------------------------------------
   // Aus1010
   // Guardar informaicon del interesado Aus1010
   // 2022-06-07 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarInformacionAuspicio() {
      $llOk = $this->mxValidarInformacionAuspicio();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarInformacionAuspicios($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValidarInformacionAuspicio() {
      if (!isset($this->paData['CNOMINS']) || strlen(trim($this->paData['CNOMINS'])) == 0 ||  !preg_match("(^[a-zA-ZáéíóúñÁÉÍÓÚÑ /]+$)", $this->paData['CNOMINS'])) {
         $this->pcError = "NOMBRE DEL RESGISTRO NO DEFINIDA O  INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNOMAUS']) || strlen(trim($this->paData['CNOMAUS'])) == 0 ||  !preg_match("(^[a-zA-ZáéíóúñÁÉÍÓÚÑ /]+$)", $this->paData['CNOMAUS'])) {
         $this->pcError = "NOMBRE DE AUSPICIO NO DEFINIDA O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['DFECINI']) || strlen(trim($this->paData['DFECINI'])) == 0 || !preg_match('(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)',$this->paData['DFECINI'])) {
         $this->pcError = "FECHA DE INICIO NO DEFINIDA O  INVALIDA";
         return false;
      } elseif (!isset($this->paData['DFECFIN']) || strlen(trim($this->paData['DFECFIN'])) == 0 || !preg_match('(^([0-9]{4})-([0-9]{2})-([0-9]{2})$)',$this->paData['DFECFIN'])) {
         $this->pcError = "FECHA DE FIN NO DEFINIDA O  INVALIDA";
         return false;
      } elseif (!isset($this->paData['CPERCON']) || strlen(trim($this->paData['CPERCON'])) == 0 ||  !preg_match("(^[a-zA-ZáéíóúñÁÉÍÓÚÑ /]+$)", $this->paData['CPERCON'])) {
         $this->pcError = "NOMBRE DE PERSONA NO DEFINIDA O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CEMAIL']) || strlen(trim($this->paData['CEMAIL'])) == 0 ||  !preg_match("(^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$)", $this->paData['CEMAIL'])) {
         $this->pcError = "EMAIL INVALIDO O NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CNROCEL']) || strlen(trim($this->paData['CNROCEL'])) == 0 || (!preg_match('/^[0-9]{9}$/', $this->paData['CNROCEL']))) {
         $this->pcError = "NRO. DE CELULAR NO DEFINIDA O  INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxGrabarInformacionAuspicios($p_oSql){
      $this->paData['CNOMINS']  = strtoupper(trim($this->paData['CNOMINS']));
      $this->paData['CNOMAUS']  = strtoupper(trim($this->paData['CNOMAUS']));
      $this->paData['CPERCON']  = strtoupper(trim($this->paData['CPERCON']));
      $this->paData['CRAZSOC']  = strtoupper(trim($this->paData['CRAZSOC']));
      $lcSql = "SELECT MAX(cNroAus) FROM B06MAUS";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) === 0 or $laTmp[0] === null) {
         $this->paData['CNROAUS'] = '0001';
      }else {
         $this->paData['CNROAUS'] = fxCorrelativo($laTmp[0]);
      }
      if($this->paData['CNRORUC'] === ''){
         $this->paData['CNRORUC'] = '00000000000';
      }
      $lcIdAsun = $this->paData['CNROAUS'];
      //print_r($this->paFile);
      if ($this->paFile['poDocRef']['error'] == 0) {
         if ($this->paFile['poDocRef']['size'] > 5242880) {
            $this->pcError = 'El tamaño maximo de los archivos debe ser de 5MB.';
            return false;
         }
         $lcFolder = "/var/www/html/ERP-II/Docs/AUSPICIOS/";
         $lcFilePath = $lcFolder."AUS".$lcIdAsun.'.pdf';
         $loOk = move_uploaded_file($this->paFile['poDocRef']['tmp_name'], $lcFilePath);
         if (!$loOk) {
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO DE DOCUMENTO DE REFERENCIA";
            return false;
         }
      } else {
         $this->pcError = 'DOCUMENTO DE AUSPICIO '.$this->paErrFil[$this->paFile['poDocRef']['error']].'NO SE PUDO CARGAR';
         return false;
      }
      $lmDatos = json_encode(['CNOMINS'=> $this->paData['CNOMINS'], 'CNOMAUS'=> $this->paData['CNOMAUS'], 'DFECINI'=> $this->paData['DFECINI'], 'DFECFIN'=> $this->paData['DFECFIN'],
                              'CPERCON'=> $this->paData['CPERCON'], 'CEMAIL'=> $this->paData['CEMAIL'], 'CRAZSOC'=> $this->paData['CRAZSOC'], 'CNROCEL'=> $this->paData['CNROCEL'], 'CFACTUR'=> $this->paData['CFACTUR'],
                              'CNRODNI'=> $this->paData['CNRODNI']]);
      $lcSql = "INSERT INTO B06MAUS (cNroAus, cNroRuc, cEstado, mSolici, mDatos, mResolu, cUsuCod) VALUES
               ('{$this->paData['CNROAUS']}', '{$this->paData['CNRORUC']}', 'P', '{$this->paData['MSOLICI']}', '$lmDatos', '', 'U666');";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR AUSPICIO NUEVO";
         return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Aus1020
   // Buscar Auspicio
   // 2022-06-09 GCH
   //------------------------------------------------------------------------------------
   public function omInitAuspicioRecibidosRS() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAuspicioRecibidosRS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxAuspicioRecibidosRS($p_oSql) {
      // Facultad académica
      $laUniAca = [];
      $lcSql = "SELECT cCenCos, cDescri, cEstado FROM S01TCCO WHERE (cEstado = 'A' AND cTipEst = '03' AND cTipDes = '01' AND cOrden != '') OR cCenCos = '08M' ORDER BY cEstPre";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)){
         $laUniAca[] = ['CUNIACA'=> $laFila[0], 'CNOMUNI'=> $laFila[1], 'CESTADO'=> $laFila[2]]; 
      }
      if (count($laUniAca) == 0) {
         $this->pcError = 'NO SE ENCONTRO UNIDADES ACADEMICAS';
         return false;
      }    
      //LISTA DE AUSPICIOS PENDIENTES 
      $paDatos = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'S' ORDER BY cNroAus";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $paDatos[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      //LISTA DE AUSPICIOS OBSERVADOR 
      $laObs = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'O' ORDER BY cNroAus";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laObs[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      //LISTA DE AUSPICIOS APROBADOS UNIDAD ACADEMICA 
      $laApro = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'A' ORDER BY cNroAus";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laApro[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      //LISTA DE AUSPICIOS ANULADOS UNIDAD ACADEMICA 
      $laAnulad = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'X' ORDER BY cNroAus";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laAnulad[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      //LISTA DE AUSPICIOS FINALIZADOS UNIDAD ACADEMICA 
      $laFin = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'T' ORDER BY cNroAus";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laFin[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      $this->paData = ['AUNIACA'=> $laUniAca];
      $this->paDatos = ['APENDIE'=> $paDatos, 'AOBSERV'=> $laObs, 'AAPROBA'=> $laApro, 'AFINAL'=> $laFin, 'ANULADO' =>$laAnulad];
      // print_r($this->paDatos);
      return true;
   }


   //------------------------------------------------------------------------------------
   // Buscar Auspicio por CNROAUS
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omSeleccionarAuspicioRS() {
      $llOk = $this->mxValSeleccionarAuspicioRS();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarAuspicioRS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValSeleccionarAuspicioRS() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } elseif (!isset($this->paData['CNROAUS']) || strlen(trim($this->paData['CNROAUS'])) == 0 || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) { 
         $this->pcError = 'NÚMERO  DE AUSPICIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } 
      return true;  
   }

   protected function mxSeleccionarAuspicioRS($p_oSql) {
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos FROM B06MAUS WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true)];
      }
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Buscar Auspicio observado por CNROAUS
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omObservacionAuspicioUA() {
      $llOk = $this->mxValParamObservacionAuspicioUA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxObservacionAuspicioUA($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamObservacionAuspicioUA() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } elseif (!isset($this->paData['CNROAUS']) || strlen(trim($this->paData['CNROAUS'])) == 0 || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) { 
         $this->pcError = 'NÚMERO  DE AUSPICIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } 
      return true;  
   }

   protected function mxObservacionAuspicioUA($p_oSql) {
      $lcSql = "SELECT A.cNroAus, A.cNroRuc, A.cEstado, A.mDatos, B.cUniAca, B.mObserv, C.cDescri
                  FROM B06MAUS A
                  INNER JOIN B06DAUS B ON B.cNroAus = A.cNroAus
                  INNER JOIN S01TCCO C ON C.cCenCos = B.cUniAca
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}' and A.cEstado ='O'
                  ORDER BY A.cNroAus";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'CUNIACA'=> $laTmp[4], 'MOBSERV'=> $laTmp[5], 'CNOMUNI'=> $laTmp[6]];
      }
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO OBSERVADO";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Enviar auspicio a unidad academica
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarAuspicioUA() {

      $llOk = $this->mxValParamEnviarAuspicioUA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarAuspicioUA($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      $lo = new CEmailFacultad();
      $lo->paData = $this->laData; 
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      return $llOk;
   }

   protected function mxValParamEnviarAuspicioUA() {
      if (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) {
         $this->pcError = "NÚMERO  DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CUNIACA']) || strlen(trim($this->paData['CUNIACA'])) == 0) {
         $this->pcError = "UNIDAD ACADEMICA NO DEFINIDA O ONVÁLIDA";
         return false;
      }elseif (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }
      return true;  
   }

   protected function mxEnviarAuspicioUA($p_oSql) {
      $lcSql = "INSERT INTO B06DAUS (cNroAus, cUniAca, cCodUsu, cEstado, cAproba, tEnvio, cUsuCod) VALUES
               ('{$this->paData['CNROAUS']}', '{$this->paData['CUNIACA']}','{$this->paData['CUSUCOD']}','A', 'P', NOW(), '{$this->paData['CUSUCOD']}')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR AUSPICIO";
         return false;
      }
      $this->paData['CPAGO'] = '"'.$this->paData['CPAGO'].'"';
      $lcSql = "UPDATE B06MAUS set cEstado = 'R', mDatos = jsonb_set(mDatos::jsonb, '{CEXOPAG}', '{$this->paData['CPAGO']}', True)::TEXT, cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() 
                  WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR AUSPICIO";
         return false;
      }
      $lcSql = "SELECT DISTINCT D.CEMAIL FROM S01TUAC A
               INNER JOIN S01TCCO B ON B.CUNIACA=A.CUNIACA
               INNER JOIN S01TCCO C ON SUBSTRING(TRIM(C.cClase),1,3)=SUBSTRING(TRIM(B.cClase),1,3) AND C.cTipEst='03' AND C.xCenCos!='NULL'
               INNER JOIN V_A01MDOC D ON D.CCODDOC=A.CDOCEN1
               WHERE B.CUNIACA!='00' AND A.CDOCEN1!='0000' AND C.cCenCos = '{$this->paData['CUNIACA']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laData = ['CEMAIL'=> $laTmp[0]];
      }
      if (count($this->paData) == 0) {
         $this->pcError = "NO SE ENCONTRO AUSPICIO";
         return false;
      }
      return true;
   }
   
   //------------------------------------------------------------------------------------
   // Buscar Auspicio anulado por CNROAUS
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omAuspicioAnulado() {
      $llOk = $this->mxValParamAuspicioAnulado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAuspicioAnulado($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamAuspicioAnulado() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } elseif (!isset($this->paData['CNROAUS']) || strlen(trim($this->paData['CNROAUS'])) == 0 || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) { 
         $this->pcError = 'NÚMERO  DE AUSPICIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      } 
      return true;  
   }

   protected function mxAuspicioAnulado($p_oSql) {
      $lcSql = "SELECT A.cNroAus, A.cNroRuc, A.cEstado, A.mDatos, B.cUniAca, B.mObserv, C.cDescri
                  FROM B06MAUS A
                  INNER JOIN B06DAUS B ON B.cNroAus = A.cNroAus
                  INNER JOIN S01TCCO C ON C.cCenCos = B.cUniAca
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}' and A.cEstado ='X'
                  ORDER BY A.cNroAus";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'CUNIACA'=> $laTmp[4], 'MOBSERV'=> $laTmp[5], 'CNOMUNI'=> $laTmp[6]];
      }
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO OBSERVADO";
        return false;
      }
      return true;
   }


   //------------------------------------------------------------------------------------
   // Mostrar informacion auspicio, para generar borrador de resolucion
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omAuspicioAprobadoUA() {
      $llOk = $this->mxValParamAuspicioAprobadoUA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAuspicioAprobadoUA($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamAuspicioAprobadoUA() {
      if  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))){
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxAuspicioAprobadoUA($p_oSql) {
      $lcSql = "SELECT A.cNroAus, A.cNroRuc, A.cEstado, A.mDatos, B.cUniAca, C.cDescri FROM B06MAUS A
                  INNER JOIN B06DAUS B ON B.cNroAus = A.cNroAus
                  INNER JOIN S01TCCO C ON C.cCenCos = B.cUniAca
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'CUNIACA'=> $laTmp[4], 'CNOMUNI'=> $laTmp[5]];
      }
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // PDF Borrador de resolucion 
   // 2022-06-13 GCH
   //------------------------------------------------------------------------------------
   public function omBorradorResolucionPDF() {
      $llOk = $this->mxValParamBorradorResolución();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBorradorResolución($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      //generar resolución PDF
      $laData = $this->paData;
      //print_r($laData);
      $lo = new CRepResponsabilidadSocial();
      $lo->paData = $laData;
      $llOk = $lo->mxBorradorResolucionPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;

   }

   protected function mxValParamBorradorResolución() {
      if  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))){
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }
      return true;  
   }

   protected function mxBorradorResolución($p_oSql) {
      $lmDatos = json_encode(['CDESCRI'=> $this->paData['CDESCRI'], 'MCONSID'=> $this->paData['MCONSID'], 'MPRIMER'=> $this->paData['MPRIMER'],
                              'MSEGUND'=> $this->paData['MSEGUND'], 'MTERCER'=> $this->paData['MTERCER'], 'MCUARTO'=> $this->paData['MCUARTO']]);
      $lmDatos = str_replace("'","''",$lmDatos);
      $lcSql = "UPDATE B06MAUS SET mResolu = '$lmDatos', tModifi = NOW(),  cUsuCod = '{$this->paData['CUSUCOD']}' WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO PUDO APROBAR AUSPICIO";
         return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Enviar borrador de resolucion a vicerrector académico
   // 2022-06-19 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarBorradorResolucionVice() {
      $llOk = $this->mxValParamEnviarBorradorResolucionVice();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarBorradorResolucionVice($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamEnviarBorradorResolucionVice() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }else if(!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) { 
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxEnviarBorradorResolucionVice($p_oSql) {
      $lcSql = "UPDATE B06MAUS set cEstado = 'F', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR AUSPICIO";
         return false;
      }
      return true;
   }



   //------------------------------------------------------------------------------------
   // AUS1030
   // Mostrar auspicios unidad academica
   // 2022-06-13 GCH
   //------------------------------------------------------------------------------------
   public function omInitAuspicioRecibidosUA() {
      $llOk = $this->mxValParamInitAuspicioRecibidosUA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAuspicioRecibidosUA($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamInitAuspicioRecibidosUA() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }
      return true;  
   }

   protected function mxAuspicioRecibidosUA($p_oSql) {
      $lcSql = "SELECT distinct(A.cNroAus), A.cNroRuc, A.cEstado, A.mDatos, TO_CHAR(A.tmodifi, 'YYYY-MM-DD HH24:MI:SS'), B.CUNIACA 
                  FROM B06MAUS A
                  INNER JOIN B06DAUS B ON B.cNroAus = A.cNroAus
                  WHERE A.cEstado = 'R' 
                  --AND B.cUniAca = '{$this->paData['CCENCOS']}' 
                  ORDER BY A.cNroAus";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI' => $laTmp[4]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO HAY AUSPICIOS PENDIENTES PARA UNIDAD ACADÉMICA";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Buscar Auspicio por CNROAUS - unidad academica
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omSeleccionarAuspicioUA() {
      $llOk = $this->mxValSeleccionarAuspicioUA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSeleccionarAuspicioUA($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValSeleccionarAuspicioUA() {
      if (!isset($this->paData['CNROAUS'])) {
         $this->pcError = "NÚMERO DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxSeleccionarAuspicioUA($p_oSql) {
      $lcSql = "SELECT A.cNroAus, A.cNroRuc, A.cEstado, A.mDatos, B.cUniAca, C.cDescri FROM B06MAUS A
                  INNER JOIN B06DAUS B ON B.cNroAus = A.cNroAus
                  INNER JOIN S01TCCO C ON C.cCenCos = B.cUniAca
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $this->paData = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'CUNIACA'=> $laTmp[4], 'CNOMUNI'=> $laTmp[5]];
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Visto bueno de unidad academica
   // 2023-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omVistoBuenoUA() {
      // Valida parametros
      $llOk = $this->mxValVistoBuenoUA();
      if (!$llOk) {
         return false;
      }
      if($this->paData['CEXOPAG'] === 'N'){
         // Conecta con UCSMINS
         $loSql = new CSql();
         $llOk = $loSql->omConnect(2);
         if (!$llOk) {
            $this->pcError = $loSql->pcError;
            return false;
         }
         //CONECTAR UCSMASBAC
         $loSql1 = new CSql();
         $llOk = $loSql1->omConnect(3);
         if (!$llOk) {
            $this->pcError = $loSql1->pcError;
            $loSql->omDisconnect();
            return false;
         }
         //CONECTAR UCSMERP
         $loSql2 = new CSql();
         $llOk = $loSql2->omConnect();
         if (!$llOk) {
            $this->pcError = $loSql2->pcError;
            $loSql2->omDisconnect();
            return false;
         }
         //Generar deuda 
         $llOk = $this->mxGenerarDeuda($loSql,$loSql1,$loSql2);
         if (!$llOk) {
            $loSql->rollback();
            $loSql1->rollback();
            $loSql->omDisconnect();
            $loSql1->omDisconnect();
            return false;
         }
         $loSql->omDisconnect();
         $loSql1->omDisconnect();
      }
      
      // Conecta con UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      // Graba visto bueno de unidad academica
      $llOk = $this->mxVistoBuenoUA($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      if($this->paData['CEXOPAG'] === 'N'){
         // Envia email a interesado con codigo de pago
         $lo = new CEmail();
         $lo->paData = $this->laData; 
         $llOk = $lo->omSend();
         if (!$llOk) {
            $this->pcError = $lo->pcError;
         }
      }
      return $llOk;
   }

   protected function mxValVistoBuenoUA() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }elseif  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))){ 
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      // } elseif (!isset($this->paData['CEMAIL']) || strlen(trim($this->paData['CEMAIL'])) == 0 || !preg_match("(^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$)", $this->paData['CEMAIL'])) {
      //    $this->pcError = "EMAIL INVALIDO O NO DEFINIDO";
      //    return false;
      }
      return true;  
   }
   protected function mxGenerarDeuda($p_oSql,$p_oSql1,$p_oSql2){
      $lcSql2 = "SELECT cNroRuc, mDatos::JSON->>'CRAZSOC', mDatos::JSON->>'CEMAIL' FROM b06maus WHERE cnroaus = '{$this->paData['CNROAUS']}'";
      $R2 = $p_oSql2->omExec($lcSql2);
      $laTmp = $p_oSql2->fetch($R2);
      $mObser = json_encode(['CNRORUC'=> $laTmp[0], 'CRAZSOC'=> $laTmp[1], 'CEMAIL'=> $laTmp[2]]);
      // inicio generar deuda
      $lcSql = "SELECT MAX(cIdDeud) FROM B03MDEU";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "NO SE PUDO GENERAR DEUDA [MAX]";
         return false;
      }
      $lcIdDeud = (int)$laFila[0]+1;
      $lcIdDeud = '00000'.(string)$lcIdDeud;
      $this->laData['CIDDEUD'] = right($lcIdDeud, 6);
      // Genera el detalle de la deuda
      $lcSql = "SELECT MAX(cIdLog) FROM B03DDEU";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "NO SE PUDO GENERAR EL DETALLE DE LA DEUDA [MAX]";
         return false;
      }
      $lcIdLog = (int)$laFila[0]+1;
      $lcIdLog = '00000'.(string)$lcIdLog;
      $lcIdLog = right($lcIdLog, 6);
      // Genera codigo 12
      $llFlag = true;
      while (true) {
         if ($llFlag) {
            $r = rand();
            $llFlag = false;
         }   
         $lcNroPag = substr('10'.strval($r).'00000000', 0, 10);
         $lcSql = "SELECT cIdDeud FROM B03MDEU WHERE cNroPag = '{$lcNroPag}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (isset($laTmp[0])) {
            $r = $r + 1;
            $llFlag = ($r == 99999999) ? true : false;
         } else {
            // Verifica nro de pago en UCSMASBANC
            $lcSql = "SELECT cEstado FROM B09DSAL WHERE cCodAlu = '{$lcNroPag}'";
            $R1 = $p_oSql1->omExec($lcSql);
            $laTmp = $p_oSql1->fetch($R1);
            if (!isset($laTmp[0]) or empty($laTmp[0])) {
               break;
            }
            $r = $r + 1;
            $llFlag = ($r == 99999999) ? true : false;
         }
      }
      $lcSql = "SELECT nMonto FROM B03TDOC WHERE CIDCATE = 'PDAUSP'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila1 = $p_oSql->fetch($R1);
      $this->laData['NMONTO'] = $laFila1[0];
      $this->laData['CNROPAG'] = $lcNroPag;
      // Inserta deuda en el maestro de deudas
      $lcSql = "INSERT INTO B03MDEU (cIdDeud, cNroPag, cNroDni, nMonto, cEnvio, cCodUsu, mObserv) 
                  VALUES('{$this->laData['CIDDEUD']}', '{$this->laData['CNROPAG']}', '00000000', '{$this->laData['NMONTO']}', 'N', '{$this->paData['CUSUCOD']}', '$mObser')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO REGISTRAR CABECERA DE LA DEUDA";
         return false;
      }
      // Inserta detalle de la deuda
      $lcSql = "INSERT INTO B03DDEU (cIdLog, cRecibo, cNroExp, cIdDeud, cIdCate, nCosto, nCosFor, cPeriod, cCodAlu, cCurCom, cCodUsu) 
            VALUES('{$lcIdLog}', '', '', '{$this->laData['CIDDEUD']}', 'PDAUSP', '{$this->laData['NMONTO']}', 0.00, '000000', '0000000000', 'N', '{$this->paData['CUSUCOD']}')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO REGISTRAR DETALLE DE LA DEUDA";
         return false;
      }
      $this->laData = ['CNROPAG'=> $this->laData['CNROPAG']];
      return true;
   }

   protected function mxVistoBuenoUA($p_oSql) {
      // Valida estado y email del auspicio
      $lcSql = "SELECT A.cEstado, A.mDatos::JSON->>'CEMAIL',  A.mDatos::JSON->>'CNOMAUS', A.mDatos::JSON->>'CNOMINS', replace(B.cNombre, '/',' ')
                  FROM B06MAUS A 
                  INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cusucod
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}' and B.ccodusu = '{$this->paData['CUSUCOD']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
        $this->pcError = "NÚMERO DE AUSPICIO [{$this->paData['CNROAUS']}] NO EXISTE";
        return false;
      } elseif ($laTmp[0] != 'R'){
        $this->pcError = "ESTADO DE AUSPICIO NO PERMITE APROBACIÓN";
        return false;
      // } elseif ($laTmp[1] != '*') {// OJOGABY VALIDAR EL EMAIL CON REGEX, CREO QUE TAMBIEN HAY QUE VALIDAR SI NO ES NULO
      //   $this->pcError = "EMAIL INVÁLIDO";
      //   return false;
      }
      if($this->laData['CNROPAG'] === ''){
         $lcNroPago = '0000000000';
      }else{
         $lcNroPago = $this->laData['CNROPAG'];
      }
      $this->laData = ['CEMAIL'=> $laTmp[1], 'CNROPAG' =>$lcNroPago, 'CNOMAUS' => $laTmp[2], 'CNOMINS' => $laTmp[3], 'CNOMBRE' => $laTmp[4]];
      // Graba visto bueno de auspicio
      $lcSql = "UPDATE B06MAUS SET cEstado = 'A', cNroPag = '{$this->laData['CNROPAG']}' ,tModifi = NOW() WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO PUDO APROBAR AUSPICIO";
         return false;
      }
      // Graba visto bueno del detalle de observaciones del auspicio
      $lcSql = "UPDATE B06DAUS SET cAproba = 'A', mObserv = TO_CHAR(NOW(),'YYYY-MM-DD HH24:MI')  || '[' || '{$this->paData['CUSUCOD']} - {$this->laData['CNOMBRE']}' || ']' || CHR(13)||CHR(10)|| 
      ''S/O''|| CHR(13) || CHR(10) || mObserv, tUltRpt = NOW(), cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      // print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO PUDO APROBAR DETALLE DE OBSERVACIONES DEL AUSPICIO";
         return false;
      }
      return true;
   }  

   //------------------------------------------------------------------------------------
   // Observacion de la unidad académica
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarObservacionRS() {
      $llOk = $this->mxValParamEnviarObservacionRS();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarObservacionRS($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      // Envia email a interesado con codigo de pago
      $lo = new CEmailObservacion();
      $lo->paData = $this->laData; 
      //print_r($this->paData);
      $llOk = $lo->omSendObservacion();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      return $llOk;
   }

   protected function mxValParamEnviarObservacionRS() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }elseif  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) { 
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxEnviarObservacionRS($p_oSql) {
      // recuperar correo
      $lcSql = "SELECT A.cEstado, A.mDatos::JSON->>'CEMAIL', replace(B.cNombre, '/',' ')
               FROM B06MAUS A 
               INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cusucod
               WHERE A.cNroAus = '{$this->paData['CNROAUS']}' and B.ccodusu = '{$this->paData['CUSUCOD']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
        $this->pcError = "NÚMERO DE AUSPICIO [{$this->paData['CNROAUS']}] NO EXISTE";
        return false;
      } elseif ($laTmp[0] != 'R'){
        $this->pcError = "ESTADO DE AUSPICIO NO PERMITE APROBACIÓN";
        return false;
      }
      $this->laData = ['CEMAIL'=> $laTmp[1], 'MOBSERVA'=>$this->paData['MOBSERV'], 'CNOMBRE'=> $laTmp[2]];
      $this->paData['MOBSERV']  = strtoupper(trim($this->paData['MOBSERV']));
      $lcSql = "UPDATE B06MAUS set cEstado = 'O', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR AUSPICIO";
         return false;
      }
      $lcSql = "UPDATE B06DAUS set cAproba = 'O', mObserv = TO_CHAR(NOW(),'YYYY-MM-DD HH24:MI')  || '[' || '{$this->paData['CUSUCOD']} - {$this->laData['CNOMBRE']}' || ']' || CHR(13)||CHR(10)|| 
               '{$this->paData['MOBSERV']}'|| CHR(13) || CHR(10) || mObserv, tUltRpt = NOW(), cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()
                  WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR AUSPICIO";
         return false;
      }

      return true;
   }

   //------------------------------------------------------------------------------------
   // Anular de la unidad académica
   // 2022-08-01 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarAnulacion() {
      $llOk = $this->mxValParamEnviarAnulacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarAnulacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      //Envia email a interesado con las observaciones de la anulacion
      $lo = new CEmailAnulacion();
      $lo->paData = $this->laData; 
      //print_r($this->paData);
      $llOk = $lo->omSendAnulacion();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      return $llOk;
   }

   protected function mxValParamEnviarAnulacion() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }elseif  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) { 
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxEnviarAnulacion($p_oSql) {
      // recuperar correo
      $lcSql = "SELECT A.cEstado, A.mDatos::JSON->>'CEMAIL', replace(B.cNombre, '/',' ')
                  FROM B06MAUS A 
                  INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cusucod
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}' and B.ccodusu = '{$this->paData['CUSUCOD']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
        $this->pcError = "NÚMERO DE AUSPICIO [{$this->paData['CNROAUS']}] NO EXISTE";
        return false;
      } elseif ($laTmp[0] != 'R'){
        $this->pcError = "ESTADO DE AUSPICIO NO PERMITE APROBACIÓN";
        return false;
      }
      $this->laData = ['CEMAIL'=> $laTmp[1], 'MOBSERVA'=>$this->paData['MOBSERV'], 'CNOMBRE' =>$laTmp[2]];
      //print_r($this->laData);
      // -Actualizar observaciobes
      $this->paData['MOBSERV']  = strtoupper(trim($this->paData['MOBSERV']));
      $lcSql = "UPDATE B06MAUS set cEstado = 'X', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR AUSPICIO";
         return false;
      }
      //$this->paData['MOBSERV'] = '"'.$this->paData['MOBSERV'].'"';
      $lcSql = "UPDATE B06DAUS SET cAproba = 'R',mObserv = TO_CHAR(NOW(),'YYYY-MM-DD HH24:MI')  || '[' || '{$this->paData['CUSUCOD']} - {$this->laData['CNOMBRE']}' || ']' || CHR(13)||CHR(10)|| 
               '{$this->paData['MOBSERV']}'|| CHR(13) || CHR(10) || mObserv, tUltRpt = NOW(), cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()
                  WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR AUSPICIO";
         return false;
      }

      return true;
   }

   //------------------------------------------------------------------------------------
   // AUS1040
   // MOSTRAR AUSPICIO VICERRECTOR ACADÉMICO
   // 2022-06-13 GCH
   //------------------------------------------------------------------------------------
   public function omInitAuspicioViceAcadémico() {
      $llOk = $this->mxValParamInitAuspicioViceAcadémico();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxIniAuspicioViceAcadémico($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamInitAuspicioViceAcadémico() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }
      return true;  
   }

   protected function mxIniAuspicioViceAcadémico($p_oSql) {
      //LISTA DE AUSPICIOS PENDIENTES 
      $paSolici = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'P' ORDER BY cNroAus";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $paSolici[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      //LISTA DE AUSPICIOS OBSERVADOR 
      $laResolu = [];
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS') FROM B06MAUS WHERE cEstado = 'F' ORDER BY cNroAus";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laResolu[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI'=> $laTmp[4]];
      }
      $this->paDatos = ['ASOLIC'=> $paSolici, 'ARESOLU'=> $laResolu];
      // print_r($this->paDatos);
      return true;
   }

   //------------------------------------------------------------------------------------
   // Buscar Auspicio VICERRECTOR ACADÉMICO
   // 2022-06-12 GCH
   //------------------------------------------------------------------------------------
   public function omRevisarAuspicioViceAcadémico() {
      $llOk = $this->mxValParamRevisarAuspicioViceAcadémico();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRevisarAuspicioViceAcadémico($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamRevisarAuspicioViceAcadémico() {
      // print_r($this->paData);
      if  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) {
         $this->pcError = "NÚMERO DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxRevisarAuspicioViceAcadémico($p_oSql) {
      $lcSql = "SELECT A.cNroAus, A.cNroRuc, A.cEstado, A.mDatos, B.cUniAca, C.cDescri, A.mResolu FROM B06MAUS A
                  INNER JOIN B06DAUS B ON B.cNroAus = A.cNroAus
                  INNER JOIN S01TCCO C ON C.cCenCos = B.cUniAca
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $this->paData = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'CUNIACA'=> $laTmp[4], 'CNOMUNI'=> $laTmp[5], 'MRESOLU'=> json_decode($laTmp[6], true),];
      if (count($this->paData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // PDF Resolución de auspicio vice academico 
   // 2022-06-13 GCH
   //------------------------------------------------------------------------------------
   public function omViceAcademicoBorradorResolucionPDF() {
      $llOk = $this->mxValParamViceAcademicoBorradorResolucionPDF();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      //Guardar datos para resolución
      $llOk = $this->mxViceAcademicoBorradorResolucionPDF($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return;
      }
      $loSql->omDisconnect();
      //generar resolución PDF
      $laData =  $this->laData;
      $lo = new CRepResponsabilidadSocial();
      $lo->paData = $laData;
      $llOk = $lo->mxResolucionPDF();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      $this->paData['CREPORT'] = $lo->paData['CREPORT'];
      return $llOk;
   }

   protected function mxValParamViceAcademicoBorradorResolucionPDF() {
      if  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))) {
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }elseif (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }
      return true;  
   }

   protected function mxViceAcademicoBorradorResolucionPDF($p_oSql) {
      $lcSql = "UPDATE B06MAUS set mResolu = jsonb_set(mResolu::jsonb, '{CNRORES}', '{$this->paData['CNRORES']}', True)::TEXT WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO PUDO APROBAR AUSPICIO";
         return false;
      }
      $ldFecha= '"'.$this->paData['DFECRES'].'"';
      $lcSql = "UPDATE B06MAUS set mResolu = jsonb_set(mResolu::jsonb, '{DFECRES}', '{$ldFecha}', true)::jsonb WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO PUDO APROBAR AUSPICIO";
         return false;
      }
       // ver datos resolucion 
      $lcSql = "SELECT  A.cNroAus, A.mResolu FROM B06MAUS A
                  WHERE A.cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      $this->laData = ['CNROAUS'=>$laTmp[0], 'MRESOLU'=> json_decode($laTmp[1], true)];
      if (count($this->laData) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIO";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // APROBAR AUSPICIO VICERRECTOR ACADÉMICO
   // 2022-06-19 GCH
   //------------------------------------------------------------------------------------
   public function omAprobaciónAuspicioViceAcadémico() {
      $llOk = $this->mxValParamAprobaciónAuspicioViceAcadémico();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAprobaciónAuspicioViceAcadémico($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }  
   protected function mxValParamAprobaciónAuspicioViceAcadémico() {
      if (!isset($this->paData['CNROAUS'])) {
         $this->pcError = "NÚMERO DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }
   protected function mxAprobaciónAuspicioViceAcadémico($p_oSql) {
      $lcRut ="/var/www/html/ERP-II/Docs/AUSPICIOS/"; 
      $lcBorradorResolucion = $lcRut."BR".$this->paData['CNROAUS'].'.pdf';
      $lcAuspicio = $lcRut."AUS".$this->paData['CNROAUS'].'.pdf';
      $lcCommand = "pdfunite $lcBorradorResolucion $lcAuspicio {$lcRut}RESOLUCION/BR{$this->paData['CNROAUS']}.pdf 2>&1";
      $lcResult = shell_exec($lcCommand);
      // echo '<br>'.$lcResult;
      $lcSql = "UPDATE B06MAUS set cEstado = 'T', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR AUSPICIO";
         return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // APROBAR AUSPICIO VICERRECTOR ACADÉMICO y ENVIAR A RESPONSABILIDAD SOCIAL
   // 2022-06-19 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarResponsabilidadSocial() {
      $llOk = $this->mxValParamEnviarResponsabilidadSocial();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarResponsabilidadSocial($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }  
   protected function mxValParamEnviarResponsabilidadSocial() {
      if (!isset($this->paData['CNROAUS'])) {
         $this->pcError = "NÚMERO DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }
   protected function mxEnviarResponsabilidadSocial($p_oSql) {
      $lcSql = "UPDATE B06MAUS set cEstado = 'S', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ENVIAR EL AUSPICIO";
         return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // ANULAR AUSPICIO VICERRECTOR ACADÉMICO
   // 2022-06-19 GCH
   //------------------------------------------------------------------------------------
   public function omAnuladoVicerectorAcademico() {
      $llOk = $this->mxValParamAnuladoVicerectorAcademico();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAnuladoVicerectorAcademico($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return;
      }
      $loSql->omDisconnect();
      $lo = new CEmailAnularVice();
      $lo->paData = $this->laData; 
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
      }
      return $llOk;
   }  
   protected function mxValParamAnuladoVicerectorAcademico() {
      if (!isset($this->paData['CNROAUS'])) {
         $this->pcError = "NÚMERO DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }
   protected function mxAnuladoVicerectorAcademico($p_oSql) {
      $lcSql = "SELECT mDatos::JSON->>'CEMAIL', mDatos::JSON->>'CNOMAUS', mDatos::JSON->>'CNOMINS', mDatos::JSON->>'COBSERV'
                  FROM B06MAUS 
                  WHERE cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!isset($laTmp[0])) {
        $this->pcError = "NÚMERO DE AUSPICIO [{$this->paData['CNROAUS']}] NO EXISTE";
        return false;
      } 
      $this->laData = ['CEMAIL'=> $laTmp[0], 'CNOMAUS'=>$laTmp[1], 'CNOMINS' =>$laTmp[2], 'MOBSERVA' => $this->paData['MOBSERV']];
      //print_r($this->laData);
      // Actualizar anulacion de vicerector academico
      $this->paData['MOBSERV'] = '"'.$this->paData['MOBSERV'].'"';
      $lcSql = "UPDATE B06MAUS set cEstado = 'P',mDatos = jsonb_set(mDatos::jsonb, '{mOBSERV}', '{$this->paData['MOBSERV']}', True)::TEXT,  cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ENVIAR EL AUSPICIO";
         return false;
      }

      return true;
   }


   //------------------------------------------------------------------------------------
   // AUS1050
   // CORREGIR OBSERVACIONES INTERESADO
   // 2022-06-26 GCH
   //------------------------------------------------------------------------------------
   public function omInitAuspicioObservacion() {
      $llOk = $this->mxValParamInitAuspicioObservacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitAuspicioObservacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamInitAuspicioObservacion() {
      // print_r($this->paData['CNRODNI']);
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }else if (!isset($this->paData['CNRODNI']) || (!preg_match('/^[0-9]{8}$/', $this->paData['CNRODNI']))) { 
         $this->pcError = 'NÚMERO DE DNI NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }
      return true;  
   }

   protected function mxInitAuspicioObservacion($p_oSql) {
      $lcSql = "SELECT cNroAus, cNroRuc, cEstado, mDatos, TO_CHAR(tmodifi, 'YYYY-MM-DD HH24:MI:SS')  
                  FROM B06MAUS WHERE cEstado = 'O'  AND mDatos::JSON->>'CNRODNI' = '{$this->paData['CNRODNI']}'ORDER BY cNroAus";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CNROAUS'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CESTADO'=> $laTmp[2], 'MDATOS' => json_decode($laTmp[3], true), 'TMODIFI' => $laTmp[4]];
      }
      if (count($this->paDatos) == 0) {
        $this->pcError = "NO SE ENCONTRO AUSPICIOS";
        return false;
      }
      return true;
   }

   //------------------------------------------------------------------------------------
   // Enviar pdf con correcciones
   // 2022-06-27 GCH
   //------------------------------------------------------------------------------------
   public function omEnviarCorrecciones() {
      $llOk = $this->mxValidarEnviarCorrecciones();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarEnviarCorrecciones($loSql);
      if (!$llOk) {
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValidarEnviarCorrecciones() {
      if (!isset($this->paData['CUSUCOD']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CUSUCOD']))) { 
         $this->pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }else if (!isset($this->paData['CNRODNI']) || (!preg_match('/^[0-9]{8}$/', $this->paData['CNRODNI']))) { 
         $this->pcError = 'NÚMERO DE DNI NO DEFINIDO O INVÁLIDO'; 
         return false; 
      }else if  (!isset($this->paData['CNROAUS']) || (!preg_match('/^[0-9A-Z]{4}$/', $this->paData['CNROAUS']))){
         $this->pcError = "ID DE AUSPICIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;  
   }

   protected function mxGrabarEnviarCorrecciones($p_oSql){
      //subir documento
      $lcIdAsun = $this->paData['CNROAUS'];
      if ($this->paFile['poDocRef']['error'] == 0) {
         if ($this->paFile['poDocRef']['size'] > 5242880) {
            $this->pcError = 'El tamaño maximo de los archivos debe ser de 5MB.';
            return false;
         }
         $lcFolder = "/var/www/html/ERP-II/Docs/AUSPICIOS/";
         if (!is_dir($lcFolder)) {
            $perm = "0777";
            $modo = intval($perm, 8);
            mkdir($lcFolder, $modo);
            chmod($lcFolder, $modo);
         }
         $lcFilePath = $lcFolder."AUS".$lcIdAsun.'.pdf';
         $loOk = move_uploaded_file($this->paFile['poDocRef']['tmp_name'], $lcFilePath);
         if (!$loOk) {
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO DE DOCUMENTO DE REFERENCIA";
            return false;
         }
      } else {
         $this->pcError = 'DOCUMENTO DE AUSPICIO '.$this->paErrFil[$this->paFile['poDocRef']['error']];
         return false;
      }
      //actualizar estado
      $lcSql = "UPDATE B06MAUS set cEstado = 'R', cUsuCod = '{$this->paData['CUSUCOD']}', tModifi = NOW()  where cNroAus = '{$this->paData['CNROAUS']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR AUSPICIO";
         return false;
      }
      return true;
   }
}

class CRepResponsabilidadSocial extends CBase {
   function mxBorradorResolucionPDF() {
      $lcFilRep = 'Docs/AUSPICIOS/BR'.$this->paData['CNROAUS'].'.pdf';
      //print_r($lcFilRep);
      $ldDate = new CDate;
      $loPdf = new FPDF();
      $loPdf->SetTitle("Any Title");
      $loPdf->SetAuthor("Any Author");
      $loPdf->SetSubject("Any Subject");
      $loPdf->SetCreator("Any Creator");
      $loPdf->AddPage('P', 'A4'); 
      $loPdf->SetMargins(20, 25 , 30);
      $loPdf->SetAutoPageBreak(true,5);    
      $loPdf->Ln(1);
      $loPdf->SetFont('Times', 'B', 13);
      $loPdf->Image('img/logo_trazos.png',20,10,70);
      $loPdf->Ln(20); 
      $loPdf->Cell(180, 1,utf8_decode('"IN SCIENTIA ET FIDE EST FORTITUDO NOSTRA"'),0,0,'C');
      $loPdf->Ln(4); 
      $loPdf->SetFont('Times', 'B', 10);
      $loPdf->Cell(180, 1,utf8_decode('(En la Ciencia y en la Fe está nuestra Fortaleza)'),0,0,'C');
      $loPdf->Ln(10);
      $loPdf->SetFont('Times','B', 11);
      $loPdf->MultiCell(180, 4, utf8_decode($this->paData['CDESCRI']), 0,'C');  
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','U',11);
      $loPdf->Cell(180, 1, utf8_decode('RESOLUCIÓN NRO.: '.$this->paData['CNRORES'].'-VRACAD-2023'), 0,  'L');
      $loPdf->Ln(8);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 1, utf8_decode('Arequipa, '.$ldDate->dateSimpleText($this->paData['DFECRES'])), 0,  'L');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("CONSIDERANDO:"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MCONSID']), 0,'J');  
      $loPdf->Ln(3.5);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("SE RESUELVE:"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->Cell(180, 0, utf8_decode("PRIMERO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MPRIMER']), 0,'J');
      $loPdf->Ln(4);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("SEGUNDO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MSEGUND']), 0,'J');
      $loPdf->Ln(3.5);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("TERCERO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MTERCER']), 0,'J');
      $loPdf->Ln(4);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("CUARTO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MCUARTO']), 0,'J');  
      $loPdf->Ln(4);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode("                                           Regístrese, comuníquese y cúmplase."), 0,'C'); 
      $loPdf->Ln(8);
      // $loPdf->SetFont('Times','B',12);
      // $loPdf->MultiCell(180, 4, utf8_decode($this->paData['MFIRMA']), 0,'L'); 
      //$loPdf->Ln(10);      
      // Posición: a 1,5 cm del final
      $loPdf->SetY(-20);
      // Arial italic 8
      $loPdf->SetFont('Arial','B',8);
      $loPdf->Cell(180,4,'_______________________________________________________________________________________________',0,0,'C');
      $loPdf->Ln(4);
      $loPdf->Cell(180, 4, utf8_decode('Campus central: Urb. San José s/n Umacollo. Arequipa - Perú'),0, 0,'C'); 
      $loPdf->SetFont('Arial','',8);
      $loPdf->Ln(4);
      $loPdf->Cell(180, 4, utf8_decode('www.ucsm.edu.pe   ucsm@ucsm.edu.pe   (+51) 054 - 382038'),0, 0,'C'); 
      $loPdf->Ln(4);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      //print_r($this->paData);
      return true;
   }

   function mxResolucionPDF() {
      //print_r($this->paData);
      $lcFilRep = 'Docs/AUSPICIOS/BR'.$this->paData['CNROAUS'].'.pdf';
      $ldDate = new CDate;
      $loPdf = new FPDF();
      $loPdf->SetTitle("Any Title");
      $loPdf->SetAuthor("Any Author");
      $loPdf->SetSubject("Any Subject");
      $loPdf->SetCreator("Any Creator");
      $loPdf->AddPage('P', 'A4'); 
      $loPdf->SetMargins(20, 25 , 30);
      $loPdf->SetAutoPageBreak(true,5);    
      $loPdf->Ln(1);
      $loPdf->SetFont('Times', 'B', 13);
      $loPdf->Image('img/logo_trazos.png',20,10,70);
      $loPdf->Ln(20); 
      $loPdf->Cell(180, 1,utf8_decode('"IN SCIENTIA ET FIDE EST FORTITUDO NOSTRA"'),0,0,'C');
      $loPdf->Ln(4); 
      $loPdf->SetFont('Times', 'B', 10);
      $loPdf->Cell(180, 1,utf8_decode('(En la Ciencia y en la Fe está nuestra Fortaleza)'),0,0,'C');
      $loPdf->Ln(10);
      $loPdf->SetFont('Times','B', 11);
      $loPdf->MultiCell(180, 4, utf8_decode($this->paData['MRESOLU']['CDESCRI']), 0,'C');  
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','U',11);
      $loPdf->Cell(180, 1, utf8_decode('RESOLUCIÓN NRO.: '.$this->paData['MRESOLU']['CNRORES'].'-VRACAD-2023'), 0,  'L');
      $loPdf->Ln(8);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 1, utf8_decode('Arequipa, '.$ldDate->dateSimpleText($this->paData['MRESOLU']['DFECRES'])), 0,  'L');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("CONSIDERANDO:"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MRESOLU']['MCONSID']), 0,'J');  
      $loPdf->Ln(3.5);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("SE RESUELVE:"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->Cell(180, 0, utf8_decode("PRIMERO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MRESOLU']['MPRIMER']), 0,'J');
      $loPdf->Ln(4);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("SEGUNDO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MRESOLU']['MSEGUND']), 0,'J');
      $loPdf->Ln(3.5);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("TERCERO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MRESOLU']['MTERCER']), 0,'J');
      $loPdf->Ln(4);
      $loPdf->SetFont('Times','B',11);
      $loPdf->Cell(180, 0, utf8_decode("CUARTO"), 0, 0, 'C');
      $loPdf->Ln(5);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode($this->paData['MRESOLU']['MCUARTO']), 0,'J');  
      $loPdf->Ln(4);
      $loPdf->SetFont('Times','',11);
      $loPdf->MultiCell(180, 5, utf8_decode("                                           Regístrese, comuníquese y cúmplase."), 0,'C'); 
      $loPdf->Ln(8);
      // $loPdf->SetFont('Times','B',12);
      // $loPdf->MultiCell(180, 4, utf8_decode($this->paData['MFIRMA']), 0,'L'); 
      //$loPdf->Ln(10);      
      // Posición: a 1,5 cm del final
      $loPdf->SetY(-20);
      // Arial italic 8
      $loPdf->SetFont('Arial','B',8);
      $loPdf->Cell(180,4,'_______________________________________________________________________________________________',0,0,'C');
      $loPdf->Ln(4);
      $loPdf->Cell(180, 4, utf8_decode('Campus central: Urb. San José s/n Umacollo. Arequipa - Perú'),0, 0,'C'); 
      $loPdf->SetFont('Arial','',8);
      $loPdf->Ln(4);
      $loPdf->Cell(180, 4, utf8_decode('www.ucsm.edu.pe   ucsm@ucsm.edu.pe   (+51) 054 - 382038'),0, 0,'C'); 
      $loPdf->Ln(4);
      $loPdf->Output('F', $lcFilRep, true);
      $this->paData = ['CREPORT'=>$lcFilRep];
      //print_r($this->paData);
      return true;
   }

}
?>
