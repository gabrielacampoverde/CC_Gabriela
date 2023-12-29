<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Clases/CEmail.php";
//require_once "Libs/fpdf/fpdf.php";

# ----------------------------------------------------------------------
# Clase para gestionar los pagos de congresos
# ----------------------------------------------------------------------
class CParqueo extends CBase {
   public $paData, $paDatos, $pcFile;
   protected $laData, $laDatos;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->laData = $this->laDatos = null;
   }
   
   protected function mxValParam() {
      if (!isset($this->paData['CUSUCOD']) || !preg_match('/^[A-B0-9]{4}$/', $this->paData['CUSUCOD'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxValVicerrectorado() {
      /*if (!isset($this->paData['CNIVEL']) or ($this->paData['CNIVEL'] != 'VD')) {
         $this->pcError = 'EL NIVEL DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      } else*/
      //1015
      if (!isset($this->paData['CUSUCOD']) or !in_array($this->paData['CUSUCOD'], ['3378','1221','3184','1936','1015'])) {
         $this->pcError = 'EL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      }
      return true;
   }

   protected function mxValSeguridad() {
      /*if (!isset($this->paData['CNIVEL']) or ($this->paData['CNIVEL'] != 'VD')) {
         $this->pcError = 'EL NIVEL DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      } else*/
      //1015
      if (!isset($this->paData['CUSUCOD']) or !in_array($this->paData['CUSUCOD'], ['3378','1936','3184'])) {
         $this->pcError = 'EL USUARIO NO TIENE PERMISOS PARA ESTA OPCIÓN';
         return false;
      }
      return true;
   }

   protected function mxValRrhhContabilidad() {
      /*if (!isset($this->paData['CNIVEL']) or ($this->paData['CNIVEL'] != 'VD')) {
         $this->pcError = 'EL NIVEL DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      } else*/
      //1015
      if (!isset($this->paData['CUSUCOD']) or !in_array($this->paData['CUSUCOD'], ['3378','1815','1871','1221'])) {
         $this->pcError = 'EL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      }
      return true;
   }
   protected function mxValParamUsuario($p_oSql, $p_cModulo = '000') {
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      //echo $lcSql;
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         ;
      } elseif ($laFila[0] == 'A') {
         return true;
      }
      $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '$p_cModulo'";
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

   # --------------------------------------------------------------------------
   # Init Parqueo Seguridad 
   # --------------------------------------------------------------------------
   public function omInitSeguridad() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      // Conexion con UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql,'00W');
      $loSql->omDisconnect();
      return $llOk;
   }

   # --------------------------------------------------------------------------
   # Init Parqueo
   # --------------------------------------------------------------------------
   public function omInitCargarXLS() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValVicerrectorado();
      if (!$llOk) {
         return false;
      }
      // Conexion con UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamInitCargarXLS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   # --------------------------------------------------------------------------
   # Init Parqueo
   # --------------------------------------------------------------------------
   public function omInitCargarRrhhConta() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValRrhhContabilidad();
      if (!$llOk) {
         return false;
      }
      // Conexion con UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      
      $llOk = $this->mxValParamInitCargarXLS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   #----------------------------------------------------------------
   # Cargar datos de parqueo
   # 2023-07-07 KRA  omCargarXLS
   # ----------------------------------------------------------------
   public function omCargarXLSParqueo() {
      $llOk = $this->mxValParamCargarXLSParqueo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      // UCSMDEEP
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxCargarValidarDatosParqueo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      // $llOk = $this->mxGenerarXlsx01();
      return $llOk;
   }
   

   protected function mxValParamCargarXLSParqueo() {
      $loDate = new CDate();
      if (!isset($this->paData['CFILEUP']) ) {
         $this->pcError = 'NO HA DEFINIDO ARCHIVO PARA SUBIR';
         return false;
      }  elseif (!isset($this->paData['DFECINI']) or (!$loDate->ValDate($this->paData['DFECINI']))) {
         $this->pcError = 'FECHA INICIAL NO DEFINIDA O INVÁLIDA';
         return false;
      }  elseif (!isset($this->paData['DFECFIN']) or (!$loDate->ValDate($this->paData['DFECFIN']))) {
         $this->pcError = 'FECHA FINAL NO DEFINIDA O INVÁLIDA';
         return false;
      }  elseif (substr($this->paData['DFECINI'], 0, 7) != substr($this->paData['DFECFIN'], 0, 7)) {
         $this->pcError = 'AMBAS FECHAS DEBEN SER DEL MISMO PERIODO';   
         return false;  
      }  elseif($this->paData['DFECINI'] > $this->paData['DFECFIN']) {
         $this->pcError = 'FECHA INICIAL NO PUEDE SER MAYOR A LA FINAL';   
         return false;  
      } 
      return true;
   }

   protected function mxCargarValidarDatosParqueo()
   {
       $lcFilErr = 'FILES/TMP' . (string) rand() . '.log';
       $loFile = fopen($lcFilErr, "w");
       $laDatos = [];
       $loXls = new CXls();
       $loXls->openXlsRead($this->paData['CFILEUP']);
       $i = 1;
       $llOk = true;
   
       while (true) {
           $i += 1;
           if ($loXls->getValue(1, 'A', $i) == '') {
               break;
           }
           $lcIngSal = $loXls->getValue(1, 'A', $i);
           $lcIngSal = substr($lcIngSal, 0, 3);
           $lcPlaca = $loXls->getValue(1, 'B', $i);
           $ltFecHor = $loXls->getValue(1, 'C', $i);
   
           if (!preg_match('/^[A-Z0-9]{6,10}$/', $lcPlaca)) {
               $lcLinea = (string) $i . ') ERR01 - ' . $lcPlaca . PHP_EOL;
               fwrite($loFile, $lcLinea);
               $llOk = false;
           }
   
           $loValid = DateTime::createFromFormat('Y-m-d H:i:s', $ltFecHor);
           if (!$loValid || $loValid->format('Y-m-d H:i:s') != $ltFecHor) {
               $lcLinea = (string) $i . ') ERR02 - ' . $ltFecHor . PHP_EOL;
               fwrite($loFile, $lcLinea);
               $llOk = false;
           }
   
           if (substr($lcIngSal, 0, 3) == 'ING') {
               $laDatos[] = ['CPLACA' => $lcPlaca, 'TENTRAD' => $ltFecHor, 'TSALIDA' => ''];
           } elseif (substr($lcIngSal, 0, 3) == 'SAL') {
               $j = -1;
               foreach ($laDatos as $key => $laTmp) {
                   if ($laTmp['CPLACA'] == $lcPlaca && $laTmp['TSALIDA'] == '') {
                       $laDatos[$key]['TSALIDA'] = $ltFecHor;
                       break;
                   }
               }
           } else {
               $lcLinea = (string) $i . ') ERR035 - ' . $lcIngSal . PHP_EOL;
               fwrite($loFile, $lcLinea);
               $llOk = false;
           }
       }
   
       //fclose($loFile); REVISAR como habilitarlo sin que genere problemas
       //$loXls->closeXls();
   
       if (count($laDatos) == 0 || !$llOk) {
           $this->pcError = 'NO HAY DATOS VALIDOS PARA CARGAR';
           return false;
       }
   
       $this->paDatos = $laDatos;
       return true;
   }
   #----------------------------------------------------------------
   # Cargar datos de parqueo
   # 2023-07-07 KRA  omCargarXLS
   # ----------------------------------------------------------------
   public function omCargarXLSParqueo_01() {
      $llOk = $this->mxGenerarXlsx01();
      return $llOk;
   }
   protected function mxGenerarXlsx01() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoDataInicial', 'R');   // OJOKEI EL NOMBRE FIJO???
      # Cabecera
      $loXls->sendXls(0, 'E', 1, 'FECHA INICIO: '.$this->paData['DFECINI']);
      $loXls->sendXls(0, 'F', 1, 'FECHA FINAL: '.$this->paData['DFECFIN']); 
      $i = 1;
      $j = 0;   // OJOKEI PARA QUE ESTA VARIABLE j????
      foreach ($this->paDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         // $loXls->sendXls(0, 'A', $i);
         $loXls->sendXls(0, 'B', $i, $laFila['CPLACA']);
         $loXls->sendXls(0, 'C', $i, $laFila['TENTRAD']);
         $loXls->sendXls(0, 'D', $i, $laFila['TSALIDA']);
      }
      $loXls->closeXlsIO();   // OJOKEI ACA SI SE CIERRA EL XLSX
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      // $this->pcFile = $loXls->pcFile;   // OJOKEI PARA QUE ESTE PCFILE? SI SE DEVUELVE EL NOMBRE DEL ARCHIVO DEBE SER EN EL PADATA!!!
      return true;
   }

   #----------------------------------------------------------------
   # Cargar datos de parqueo placa hora entrada horasalida
   # 2023-07-07 KRA  omCargarXLS
   # ----------------------------------------------------------------
   public function omCargarXLSParqueoFinal() {
      $llOk = $this->mxValParamCargarXLSParqueoFinal();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxCargarValidarDatosParqueoFinal();
      if (!$llOk) {
         return false;
      }
      // CONEXION UCSMDEEP 14
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(14); 
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxValidarDatosParqueoFinal($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      // $llOk = $this->mxPrintXlsx01();
      return $llOk;
   }

   protected function mxValParamCargarXLSParqueoFinal() {
      $loDate = new CDate();
      if (!isset($this->paData['CFILEUP']) ) {
         $this->pcError = 'NO HA DEFINIDO ARCHIVO PARA SUBIR';
         return false;
      }  elseif (!isset($this->paData['DFECINI']) or (!$loDate->ValDate($this->paData['DFECINI']))) {
         $this->pcError = 'FECHA INICIAL NO DEFINIDA O INVÁLIDA';
         return false;
      }  elseif (!isset($this->paData['DFECFIN']) or (!$loDate->ValDate($this->paData['DFECFIN']))) {
         $this->pcError = 'FECHA FINAL NO DEFINIDA O INVÁLIDA';
         return false;
      }  elseif (substr($this->paData['DFECINI'], 0, 7) != substr($this->paData['DFECFIN'], 0, 7)) {
         $this->pcError = 'AMBAS FECHAS DEBEN SER DEL MISMO PERIODO';   
         return false;  
      }  elseif ($this->paData['DFECINI'] > $this->paData['DFECFIN']) {
         $this->pcError = 'FECHA INICIAL NO PUEDE SER MAYOR A LA FINAL';   
         return false;  
      } 
      return true;
   }
   
   protected function mxCargarValidarDatosParqueoFinal() {
      $laDatos = [];
      $loXls = new CXls();
      $loXls->openXlsRead($this->paData['CFILEUP']);
      $i = 1;
      while (true) {
         $i += 1;
         if ($loXls->getValue(1, 'A', $i) == '') {
            break;
         }
         $laDatos[] = ['CPLACA'=> $loXls->getValue(1, 'B', $i), 'TENTRAD'=> $loXls->getValue(1, 'C', $i), 'TSALIDA'=> $loXls->getValue(1, 'D', $i)];
      }
      //$loXls->closeXlsIO();
      if (count($laDatos) == 0) {
         $this->pcError = 'NO HAY DATOS PARA CARGAR';
         return false;
      }
      $this->laDatos = $laDatos;
      return true;
   }

   protected function mxValidarDatosParqueoFinal($p_oSql) {
      $lcFilErr = 'FILES/TMP'.(string)rand().'.log';
      $loFile = fopen($lcFilErr, "w");
      $i = 0;
      $llOk = True;
      foreach ($this->laDatos as $laFila) {
         $i++;
         // Valida fecha y hora de entrada
         $loValid = DateTime::createFromFormat('Y-m-d H:i:s', $laFila['TENTRAD']);
         $llOk1 = $loValid && $loValid->format('Y-m-d H:i:s') == $laFila['TENTRAD'];
         if (!$llOk1) {
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR01 - FORMATO FECHA ENTRADA Y-M-D H:M:S DEBE SER VÁLIDO => '.$laFila['TENTRAD'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }  
         // Valida fecha y hora de salida
         $loValid = DateTime::createFromFormat('Y-m-d H:i:s', $laFila['TSALIDA']);
         $llOk1 = $loValid && $loValid->format('Y-m-d H:i:s') == $laFila['TSALIDA'];
         if (!$llOk1) {
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR02 - FORMATO FECHA SALIDA Y-M-D H:M:S DEBE SER VÁLIDO => '.$laFila['TSALIDA'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }
         // Valida que fecha de entrada no sea menor que fecha inicial
         if (substr($laFila['TENTRAD'], 0, 10) < $this->paData['DFECINI']) { 
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR03 - FECHA ENTRADA DEBE SER MAYOR O IGUAL QUE FECHA INICIO => '.$laFila['TENTRAD'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }   
         // Valida que fecha de entrada no sea mayor que fecha final
         if (substr($laFila['TENTRAD'], 0, 10) > $this->paData['DFECFIN']) { 
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR04 - FECHA ENTRADA DEBE SER MENOR O IGUAL QUE FECHA FINAL => '.$laFila['TENTRAD'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }   
         // Valida que fecha de salida no sea menor que fecha inicial
         if (substr($laFila['TSALIDA'], 0, 10) < $this->paData['DFECINI']) { 
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR05 - FECHA SALIDA DEBE SER MAYOR O IGUAL QUE FECHA INICIO => '.$laFila['TENTRAD'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }   
         // Valida que fecha de salida no sea mayor que fecha final
         if (substr($laFila['TSALIDA'], 0, 10) > $this->paData['DFECFIN']) { 
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR06 - FECHA SALIDA DEBE SER MENOR O IGUAL QUE FECHA FINAL => '.$laFila['TENTRAD'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }   
         // Valida que fecha y hora de salida no sea menor que fecha y hora de entrada
         if ($laFila['TSALIDA'] < $laFila['TENTRAD']) {
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR07 - FECHA Y HORA ENTRADA DEBE SER MENOR QUE FECHA Y HORA SALIDA => '.$laFila['TENTRAD'].' - '.$laFila['TSALIDA'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         }  
         // Valida que placa exista en maestro
         $lcSql = "SELECT cIdenti FROM B11MPAR WHERE cPlaca = '{$laFila['CPLACA']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!isset($laTmp[0])) {
            $llOk = false;
            $lcLinea = (string)'FILA '.$i.' - ERR08 - PLACA NO FUÉ REGISTRADA => '.$laFila['CPLACA'].PHP_EOL;
            fwrite($loFile, $lcLinea);
         } else {
            $this->laDatos[$i - 1]['CIDENTI'] = $laTmp[0];
         }
         $this->laErrores = $lcLinea;
      }  
      fclose($loFile);
      if (!$llOk) {
         //$this->paData = ['CFILERR'=> $lcFilErr];   // OJOKEI ESTE LOG DE ERRORES TIENES QUE MOSTRARLO EN UN TEXTAREA
         $loFile = fopen($lcFilErr, "r");
         $this->paData['CDATERR'] = fread($loFile, filesize($lcFilErr));
         fclose($loFile);
         return false;
      }
      $this->paDatos = $this->laDatos; 
      return true;
   }

   protected function mxValidarDatosParqueo_old($p_oSql) {
      $lcFilErr = 'FILES/TMP'.(string)rand().'.log';
      $loFile = fopen($lcFilErr, "w");
      $i = 0;
      $llOk=True;
      // print_r($this->laDatos);
      // die();
      foreach ($this->laDatos as $laFila) {
         $i++;
         if (!preg_match('/^[A-Z0-9]{8,12}$/', $laFila['CNRODNI'])) {
            $llOk = false;
            $lcLinea = (string)$i.') ERR01 - '.$laFila['CNRODNI'].PHP_EOL;
            //fwrite($loFile);
            fwrite($loFile, $lcLinea);
         }  
         if (!preg_match('/^[A-Z0-9-]{7,10}$/', $laFila['CIDPLAC'])) {
            $lcLinea = (string)$i.') ERR02 - '.$laFila['CIDPLAC'].PHP_EOL;
            //fwrite($loFile);
            fwrite($loFile, $lcLinea);
            $llOk = false;
         }  
         $loValid = DateTime::createFromFormat('Y-m-d H:i:s', $laFila['TENTRAD']);
         $llOk = $loValid && $loValid->format('Y-m-d H:i:s') == $laFila['TENTRAD'];
         if (!$llOk) {
            $llOk = false;
            $lcLinea = (string)$i.') ERR03 - '.$laFila['TENTRAD'].PHP_EOL;
            //fwrite($loFile);
            fwrite($loFile, $lcLinea);
         }  
         $loValid = DateTime::createFromFormat('Y-m-d H:i:s', $laFila['TSALIDA']);
         $llOk = $loValid && $loValid->format('Y-m-d H:i:s') == $laFila['TSALIDA'];
         if (!$llOk) {
            $llOk = false;
            $lcLinea = (string)$i.') ERR04 - '.$laFila['TSALIDA'].PHP_EOL;
            // fwrite($loFile);
            fwrite($loFile, $lcLinea);
         }
         if (substr($laFila['TENTRAD'], 0, 7) != $this->paData['DFECHA']) { //año mes 7
            $llOk = false;
            $lcLinea = (string)$i.') ERR05 - '.$laFila['TENTRAD'].PHP_EOL;
            // fwrite($loFile);
            fwrite($loFile, $lcLinea);
         }
         if ($laFila['TSALIDA'] < $laFila['TENTRAD']) {
            $llOk = false;
            $lcLinea = (string)$i.') ERR06 - '.$laFila['TENTRAD'].' - '.$laFila['TSALIDA'].PHP_EOL;
            //fwrite($loFile);
            fwrite($loFile, $lcLinea);
         }  
         $llFlag = true;
         if (!preg_match('/^[0-9]{8}$/', $laFila['CNRODNI'])) {
            $lcSql = "SELECT cNroDni FROM S01MPER WHERE cNroDni = '{$laFila['CNRODNI']}'";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!isset($laTmp[0])) {
               $llFlag = false;
            }
         }
         if (!$llFlag) {
            $lcSql = "SELECT cNroDni FROM S01MPER WHERE cDocExt = '{$laFila['CNRODNI']}'";
            $R1 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R1);
            if (!isset($laTmp[0])) {
               $llOk = false;
               $lcLinea = (string)$i.') ERR07 - '.$laFila['CNRODNI'].PHP_EOL;
               //fwrite($loFile);
               fwrite($loFile, $lcLinea);
            } else {
               $this->laDatos[$i - 1]['CNRODNI'] = $laTmp[0];
            }
         }  
      }  
      fclose($loFile);
      if (!$llOk) {
         $this->paData = ['CFILERR'=> $lcFilErr];
         $this->pcError = 'HAY ERRORES EN DATOS. REVISE EL LOG';
         return false;
      }
      $this->paDatos= $this->laDatos;
      return true;
   }

   # ----------------------------------------------------------------
   # Grabar la carga de datos al UCSMDEEP
   # KRA Creacion
   # ----------------------------------------------------------------
   public function omGrabarDatos() {
      # Conecta con UCSMDEEP
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $this->laDatos = $this->paDatos;
      $llOk = $this->mxGrabarDatos($loSql);
      if (!$llOk) {
         $loSql->omRollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarDatos($p_oSql) { 
      $lcSql = "DELETE FROM B11DPAR WHERE TO_CHAR(TENTRAD, 'YYYY-MM-DD') BETWEEN '{$this->paData['DFECINI']}' AND '{$this->paData['DFECFIN']}'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ELIMINAR DATOS ANTERIORES - INFORME A ERP";
         return false;
      }
      // Reinicializa el nSerial
      $lcSql = "SELECT SETVAL(PG_GET_SERIAL_SEQUENCE('B11DPAR', 'nserial'), COALESCE(MAX(nSerial), 0) + 1, FALSE) FROM B11DPAR";
      $p_oSql->omExec($lcSql);
      foreach ($this->paDatos as $laFila) {
         $lcSql = "SELECT cIdenti FROM B11MPAR WHERE cplaca = '{$laFila['CPLACA']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laTmp = $p_oSql->fetch($R1);
         if (!isset($laTmp[0]) or empty($laTmp[0])) {
            $lcSql = "SELECT MAX(cIdenti) from B11MPAR";
            $RS = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($RS);
            if (!isset($laTmp[0]) or empty($laTmp[0])) {
               $lcIdenti='0000';
            }  else {
               $lcIdenti = $laTmp[0];
            }
            $lcIdenti = fxCorrelativo($lcIdenti);
            $lcSql = "INSERT INTO B11MPAR (cIdenti, cNroDni, cPlaca, cEstado, cUsuCod) VALUES
                      ('{$lcIdenti}', '{$laFila['CNRODNI']}', '{$laFila['CPLACA']}', 'A', {$this->paData['CUSUCOD']})";
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = "NO SE PUDO INSERTAR DATO DE CABECERA - INFORME A ERP";
               return false;
            }
         } else {
            $lcIdenti = $laTmp[0];
         }
         $lcSql = "INSERT INTO B11DPAR (cIdenti, tEntrad, tSalida) VALUES ('{$lcIdenti}', '{$laFila['TENTRAD']}', '{$laFila['TSALIDA']}')";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "NO SE PUDO INSERTAR DATO DETALLE - INFORME A ERP";
            return false;
         }
      }
      return true;
   }

   # -------------------------------------------------------------------
   # DESCARGAR PLANTILLA DE PAGOS EN FORMATO XLS PARA SUBIR INFORMACIÓN
   # -------------------------------------------------------------------
   public function omDescargarPlantillaXLS() {
      if ($this->paData['COPCION'] == '1') {
         $this->paData['CFILE'] = './Xls/ParqueoCobro.xlsx';
      } elseif ($this->paData['COPCION'] == '2') {
         $this->paData['CFILE'] = './Xls/ParqueoCobro1.xlsx';
      } 
      return true;
   }
   
   # -------------------------------------------------------------------------------
   # REPORTE MONTO A PAGAR POR USO DE PARQUEO
   # -------------------------------------------------------------------------------
   public function omReporteCobroParqueo1() {
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxMostrarReporteCobroParqueo1($loSql);

      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReporteCobroParqueo1();
      return $llOk;
   }

   protected function mxMostrarReporteCobroParqueo1($p_oSql) {
      $lcPeriod = str_replace('-', '',$this->paData['DFECHA']);
      // print_r($lcPeriod);
      // $lcSql = "SELECT A.CIDENTI,A.CPERIOD, B.CNRODNI,REPLACE(C.CNOMBRE,'/', ' '),B.CPLACA, B.CESTADO,A.NTIEMPO,A.NMONTO FROM B11DPER A
      //             INNER JOIN B11mPAR B ON B.cidenti=A.cidenti
      //             LEFT JOIN S01MPER C ON C.CNRODNI=B.CNRODNI
      //             WHERE cperiod = '{$lcPeriod}' ORDER BY C.CNOMBRE";
      $lcSql = "SELECT A.CPERIOD, B.CNRODNI,REPLACE(C.CNOMBRE,'/', ' '),B.CPLACA, B.CESTADO,A.NTIEMPO,A.NMONTO FROM B11DPER A
                  INNER JOIN B11mPAR B ON B.cidenti=A.cidenti
                  LEFT JOIN S01MPER C ON C.CNRODNI=B.CNRODNI
                  WHERE cperiod = '{$lcPeriod}' ORDER BY C.CNOMBRE";
      // print_r($lcSql);
      // die;
      $R1 = $p_oSql->omExec($lcSql);
      // echo pg_last_error();
      // echo pg_last_error($p_oSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[6] == 0) {
            continue;
            // $this->pcError = "ERROR: NO SE TIENE INFORMACIÓN SOBRE MONTOS A COBRAR";
            // return false;
         }
         // $this->laDatos[] = ['CIDENTI' => $laTmp[0], 'CPERIOD' => $laTmp[1], 'CNRODNI' => $laTmp[2], 'CNOMBRE' => $laTmp[3], 'CPLACA' => $laTmp[4], 'CESTADO' => $laTmp[5],         
         // 'NTIEMPO' => $laTmp[6],'NMONTO' => $laTmp[7]];
         $this->laDatos[] = ['CPERIOD' => $laTmp[0], 'CNRODNI' => $laTmp[1], 'CNOMBRE' => $laTmp[2], 'CPLACA' => $laTmp[3], 'CESTADO' => $laTmp[4],         
                           'NTIEMPO' => $laTmp[5],'NMONTO' => $laTmp[6]];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "NO HAY DATOS PARA PERIODO SELECCIONADO";
         return false;
      }
      $this->paDatos=$this->laDatos; 
      return true;
   }

   protected function mxPrintReporteCobroParqueo1() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroDetalle', 'R');
      # Cabecera
      // $loXls->sendXls(0, 'H', 1, date("Y-m-d"));
      $loXls->sendXls(0, 'H', 1, 'Periodo: '.$this->paData['DFECHA']);
      $i = 1;
      // $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         // $j++;
         // print_r($laFila['CIDENTI']); 
         // die ();
         // $loXls->sendXls(0, 'A', $i, $laFila['CIDENTI']);
         $loXls->sendXls(0, 'A', $i, $laFila['CPERIOD']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['CPLACA']);
         $loXls->sendXls(0, 'E', $i, $laFila['CESTADO']);
         $loXls->sendXls(0, 'F', $i, $laFila['NTIEMPO']);
         $loXls->sendXls(0, 'G', $i, $laFila['NMONTO']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # -------------------------------------------------------------------------------
   # REPORTE MONTO A PAGAR POR USO DE PARQUEO
   # -------------------------------------------------------------------------------
   
   public function omReporteCobroParqueo2() {
      $llOk = $this->mxValParamPeriod();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      // LLama a python
      $llOk = $this->mxCalcularUsoParqueo();
      // echo('aaaaaaaaaaaa');
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxMostrarReporteCobroParqueo2($loSql);
      // echo('bbbbbbbbbbbbbbbbbbbb');
      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReporteCobroParqueo2();
      // echo('cccccccccccccccccccc');
      return $llOk;
   }

   protected function mxValParamPeriod() {
      if (!isset($this->paData['DFECHA']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['DFECHA'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCalcularUsoParqueo() {
      $laData = ['ID' => 'ERP0001', 'CPERIOD'=> $this->paData['DFECHA'],'CUSUCOD'=> $this->paData['CUSUCOD']];
      $sJson = json_encode($laData);
      $lcCommand = "python3 ./xpython/CParqueo.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      //print_r($lcData);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      //print_r($this->paDatos);
      //die;
      return true;
   }

   protected function mxMostrarReporteCobroParqueo2($p_oSql) {
      # Total a cobrar por DNI en un periodo
      //$lcPeriod = $this->paData['CPERIOD'];
      $lcPeriod = str_replace('-', '',$this->paData['DFECHA']);
      $lcSql = "SELECT cPeriod, cNroDni, cNombre, SUM(nTiempo), SUM(nMonto) FROM
               (SELECT A.cPeriod, B.cNroDni, REPLACE(C.cNombre, '/', ' ') AS cNombre, A.nTiempo, A.nMonto FROM B11DPER A
               INNER JOIN B11MPAR B ON B.cIdenti = A.cIdenti
               INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni
               WHERE cPeriod = '{$lcPeriod}' AND B.CESTADO!='N' ORDER BY C.cNombre) Z
               WHERE NMONTO>0 
               GROUP BY cPeriod, cNroDni, cNombre ORDER BY cNombre";
      // print_r($lcSql);
      // die;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[4] == 0) {
            continue;
         }
         $this->laDatos[] = ['CPERIOD'=> $laTmp[0], 'CNRODNI'=> $laTmp[1], 'CNOMBRE'=> $laTmp[2], 'NTIEMPO'=> $laTmp[3], 'NMONTO'=> $laTmp[4]];
      } 
      if (count($this->laDatos) == 0) {
         $this->pcError = "NO HAY DATOS REGISTRADOS PARA PERIODO SELECCIONADO";
         return false;
      }
      $this->paDatos=$this->laDatos;     
      return true;
   }

   protected function mxPrintReporteCobroParqueo2() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroDetalle2', 'R');
      # Cabecera
      $loXls->sendXls(0, 'G', 1, 'Periodo: '.$this->paData['DFECHA']);
      $i = 1;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CPERIOD']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'E', $i, $laFila['NTIEMPO']);
         $loXls->sendXls(0, 'F', $i, $laFila['NMONTO']);

      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # -------------------------------------------------------------------------------
   # REPORTE MONTO A PAGAR POR USO DE PARQUEO RHHH
   # -------------------------------------------------------------------------------
   public function omReporteCobroParqueo3() {
      // $llOk = $this->mxValParamDNI();
      // if (!$llOk) {
      //    return false;
      // }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxMostrarReporteCobroParqueo3($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReporteCobroParqueo3();
      return $llOk;
   }


   protected function mxMostrarReporteCobroParqueo3($p_oSql) {
      # Total a cobrar por DNI en un periodo
      //$lcPeriod = $this->paData['CPERIOD'];
      $lcPeriod = '202307';
      $lcSql = "SELECT  CUSUINF ,cPeriod, cNroDni, cNombre, SUM(nTiempo), SUM(nMonto) FROM
                  (SELECT distinct on (D.CUSUINF) D.cusuinf, A.cPeriod, B.cNroDni, REPLACE(C.cNombre, '/', ' ') AS cNombre, A.nTiempo, A.nMonto FROM B11DPER A
                  INNER JOIN B11MPAR B ON B.cIdenti = A.cIdenti
                  INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni
                  INNER JOIN S01TUSU D ON D.cNroDni = C.cNroDni
                  WHERE cPeriod = '202307' AND B.CESTADO='A' AND D.CESTADO='A' ORDER BY D.CUSUINF, C.cNombre) Z
                  WHERE NMONTO>0 
                  GROUP BY  cusuinf, cPeriod, cNroDni, cNombre ORDER BY cNombre";

      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[4] == 0) {
            continue;
         }
         $this->laDatos[] = ['CUSUINF'=> $laTmp[0],'CPERIOD'=> $laTmp[1], 'CNRODNI'=> $laTmp[2], 'CNOMBRE'=> $laTmp[3], 'NTIEMPO'=> $laTmp[4], 'NMONTO'=> $laTmp[5]];
      } 
           
      return true;
   }

   protected function mxPrintReporteCobroParqueo3() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroRRHH', 'R');
      # Cabecera
      $loXls->sendXls(0, 'H', 3, date("Y-m-d"));
      $loXls->sendXls(0, 'D', 3, 'Periodo Académico: '.$this->paData['DFECHA']);
      $i = 6;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'C', $i, $laFila['CPERIOD']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'F', $i, $laFila['NTIEMPO']);
         $loXls->sendXls(0, 'F', $i, $laFila['NMONTO']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // -------------------------------------------------------------------------------
   // BUSQUEDA Y APROBACION VICERRECTORADO
   // 2023-08-29 
   // -------------------------------------------------------------------------------
   public function omMostrarRegistroParqueoVicerrectorado() {
      $llOk = $this->mxValParamXlsxVice();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      //CONEXION UCSMDEEP
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxCargarMostrarRegistroParqueoVicerrectorado($loSql);
      if (!$llOk) {
         return false;
         $loSql->omDisconnect();
      }
      $loSql->omDisconnect();
      $llOk = $this->mxFileXlsxMostrarRegistroParqueoVicerrectorado();
      return $llOk;
   }

   protected function mxValParamXlsxVice() {
      if (!isset($this->paData['CPERIOD']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarMostrarRegistroParqueoVicerrectorado($p_oSql) {
      // Valida que se pueda enviar a RRHH   
      // VALIDA USUARIO RRHH
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCenCos in ('02Q','02J','UNI') AND cCodUsu = '{$lcCodUsu}'";
      // $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCenCos = '02T' AND cCodUsu = '$lcCodUsu' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
   
      // Carga datos del periodo
      $lcPeriod = str_replace('-', '', $this->paData['CPERIOD']);
      // $lcSql = "SELECT B.cNroDni, C.cNombre, SUM(A.nMonto) FROM FROM B11DPER A
      //           INNER JOIN B11MPAR B ON B.cIdenti = A.cIdenti
      //           INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni
      //           WHERE A.cPeriod = '{$lcPeriod}' AND B.cEstado = 'A'
      //           WHERE A.nMonto > 0 
      //           GROUP BY B.cNroDni ORDER BY cNombre";   // OJOKEI HAY QUE VERIFICAR ESTE QUERY!
      $lcSql = "SELECT B.cNroDni, C.cNombre, SUM(A.nMonto) FROM  B11DPER A
                INNER JOIN B11MPAR B ON B.cIdenti = A.cIdenti
                INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni
                WHERE A.cPeriod = '{$lcPeriod}' AND B.cEstado != 'N' AND A.cEstado = 'B' and A.nMonto > 0 
                GROUP BY B.cNroDni,  C.cNombre ORDER BY cNombre";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT cUsuInf, cEstado FROM S01TUSU WHERE cNroDni = '{$laTmp[0]}' ORDER BY cUsuInf";
         // print_r($lcSql);
         // echo('<br>');
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp2 = $p_oSql->fetch($R2)) {
            if ($laTmp2[1] == 'A') {
               $lcUsuInf = $laTmp2[0];
               //print_r($laTmp2);
               break;  
               
            } 
            $lcUsuInf = $laTmp2[0];
         }
         $this->laDatos[] = ['CUSUINF'=> $lcUsuInf, 'CNOMBRE'=> str_replace('/', ' ', $laTmp[1]), 'NMONTO'=> $laTmp[2], 'CNRODNI'=> $laTmp[0]];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "NO HAY DATOS PARA PERIODO SELECCIONADO";
         return false;
      }
      $this->paDatos=$this->laDatos;
      return true;
   }

   protected function mxFileXlsxMostrarRegistroParqueoVicerrectorado() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroRRHH', 'R');
      $loXls->sendXls(0, 'E', 1, 'Periodo: '.$this->paData['CPERIOD']);
      $i = 1;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['NMONTO']);
         
      }
      $loXls->closeXlsIO();
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      return true;
   }  

   public function omMostrarXls() {
      $llOk = $this->mxValXlsVice();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxFileMostrarXls();
      return $llOk;
   }

   protected function mxValXlsVice() {
      if (!isset($this->paData['CPERIOD']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paDatos)){
         $this->pcError = "PRIMERO DEBE HACER CLIC EN BUSCAR";
         return false;
      }
      return true;
   }

   protected function mxValXlsVice2() {
      if (!isset($this->paData['DFECHA']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['DFECHA'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxFileMostrarXls() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroRRHH', 'R');
      $loXls->sendXls(0, 'E', 1, 'Periodo: '.$this->paData['CPERIOD']);
      $i = 1;
      foreach ($this->paDatos as $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['NMONTO']);
         
      }
      $loXls->closeXlsIO();
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      return true;
   }  

   // -------------------------------------------------------------------------------
   // Busqueda registros uso parqueo
   // 2023-08-18 FPM Creacion
   // -------------------------------------------------------------------------------
   public function omXlsxUsoParqueo() {
      $llOk = $this->mxValParamXlsxRegParqueo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      //CONEXION UCSMDEEP
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxCargarXlsxRegParqueo($loSql);
      if (!$llOk) {
         return false;
         $loSql->omDisconnect();
      }
      $loSql->omDisconnect();
      $llOk = $this->mxFileXlsxRegParq();
      return $llOk;
   }

   protected function mxValParamXlsxRegParqueo() {
      // print_r(strlen($this->paData['CBUSQUE']));
      // die;
      // if (!isset($this->paData['CBUSQUE']) || strlen($this->paData['CBUSQUE']) != 8 || !preg_match('(^[E0-9]{1}[0-9]{7}$)', $this->paData['CBUSQUE'])) {
      //    $this->pcError = "BÚSQUEDA DNI NO DEFINIDA O INVÁLIDA";
      //    return false;
      // }
      if (!isset($this->paData['CPERIOD']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CBUSQUE']) || strlen(trim($this->paData['CBUSQUE'])) < 0 || strlen(trim($this->paData['CBUSALU'])) > 50) {
         $this->pcError = "BÚSQUEDA  NO DEFINIDA O INVÁLIDA";
         return false;
      }
      
      return true;
   }

   protected function mxCargarXlsxRegParqueo($p_oSql) {  
      // Carga datos del periodo
      $lcPeriod = $this->paData['CPERIOD'];
      $lcBusque = str_replace('-', '', $this->paData['CBUSQUE']);
      $lcSql = "SELECT A.CNRODNI,A.CPLACA, B.TENTRAD, B.TSALIDA,C.CNOMBRE FROM B11MPAR A 
      INNER JOIN B11DPAR B ON B.CIDENTI=A.CIDENTI 
      INNER JOIN S01MPER C ON C.CNRODNI=A.CNRODNI 
      WHERE TO_CHAR(B.TENTRAD,'YYYY-MM') LIKE '$lcPeriod' AND TO_CHAR(B.TSALIDA,'YYYY-MM') LIKE '$lcPeriod' AND";
      if (preg_match('/^[0-9A-Z]{6}$/',$lcBusque)) {
         $lcSql = $lcSql."  A.cPlaca = '{$lcBusque}' AND B.tentrad >= '2023-07-01' ";
      } elseif (preg_match('/^[0-9A-Z]{8}$/',$lcBusque) ) {
         $lcSql = $lcSql."  A.cNroDni = '{$lcBusque}' AND B.tentrad >= '2023-07-01' ";
      } else {
         $lcBusque = str_replace(' ', '/', $lcBusque);
         $lcBusque = '%'.$lcBusque.'%';
         $lcSql = $lcSql."  C.cNombre LIKE '{$lcBusque}' AND B.tentrad >= '2023-07-01' ";
      }
      // echo($lcSql );
      // die;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT cUsuInf, cEstado FROM S01TUSU WHERE cNroDni = '{$laTmp[0]}' ORDER BY cUsuInf";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp2 = $p_oSql->fetch($R2)) {
            if ($laTmp2[1] == 'A') {
               $lcUsuInf = $laTmp2[0];
               break;  
               
            } 
            $lcUsuInf = $laTmp2[0];
         }
         $this->laDatos[] = ['CUSUINF'=> $lcUsuInf,'CNRODNI'=> $laTmp[0] ,'CNOMBRE'=> str_replace('/', ' ', $laTmp[4]), 'CPLACA'=> $laTmp[1], 'TENTRAD'=> $laTmp[2] , 'TSALIDA'=> $laTmp[3]];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "NO HAY DATOS PARA DNI INGRESADO";
         return false;
      }
      $this->paDatos=$this->laDatos;
      return true;
   }

   protected function mxFileXlsxRegParq() {
      $loXls = new CXls();
      $loXls->openXlsIO('HistUsoParqueo', 'R');
      $i = 1;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['CPLACA']);
         $loXls->sendXls(0, 'E', $i, $laFila['TENTRAD']);
         $loXls->sendXls(0, 'F', $i, $laFila['TSALIDA']);
         
      }
      $loXls->closeXlsIO();
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      return true;
   }

   // -------------------------------------------------------------------------------
   // Busqueda Histórico uso parqueo
   // 2023-08-23 kra Creacion
   // -------------------------------------------------------------------------------
   public function omHistoricoUsoParqueo() {
      $llOk = $this->mxvalHistoricoUsoParqueo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      //CONEXION UCSMDEEP
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxHistoricoUsoParqueo($loSql);
      if (!$llOk) {
         return false;
         $loSql->omDisconnect();
      }
      $loSql->omDisconnect();
      $llOk = $this->mxFileXlsxRegHistParq();
      return $llOk;
   }

   protected function mxvalHistoricoUsoParqueo() {
      // print_r(strlen($this->paData['CBUSQUE']));
      // die;
      if (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8 || !preg_match('(^[E0-9]{1}[0-9]{7}$)', $this->paData['CNRODNI'])) {
         $this->pcError = "BÚSQUEDA DNI NO DEFINIDA O INVÁLIDA";
         return false;
      }
      return true;
   }

   protected function mxHistoricoUsoParqueo($p_oSql) {
      // Carga datos del periodo
      $lcBusDni = trim($this->paData['CNRODNI']);
      $lcSql = "SELECT A.CNRODNI,A.CPLACA, B.TENTRAD, B.TSALIDA,C.CNOMBRE,D.CUSUINF FROM B11MPAR A
                INNER JOIN B11DPAR B ON B.CIDENTI=A.CIDENTI
                INNER JOIN S01MPER C ON C.CNRODNI=A.CNRODNI
                INNER JOIN S01TUSU D ON D.CNRODNI=C.CNRODNI
                WHERE A.cNroDni = '{$lcBusDni}' AND B.tentrad >= '2023-07-01'";
      // echo($lcSql);
      // die;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT cUsuInf, cEstado FROM S01TUSU WHERE cNroDni = '{$laTmp[0]}' ORDER BY cUsuInf";
         // echo('<br>');
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp2 = $p_oSql->fetch($R2)) {
            if ($laTmp2[1] == 'A') {
               $lcUsuInf = $laTmp2[0];
               //print_r($laTmp2);
               break;  
            } 
            $lcUsuInf = $laTmp2[0];
         }
         $this->laDatos[] = ['CUSUINF'=> $lcUsuInf,'CNRODNI'=> $laTmp[0] ,'CNOMBRE'=> str_replace('/', ' ', $laTmp[4]), 'CPLACA'=> $laTmp[1], 'TENTRAD'=> $laTmp[2] , 'TSALIDA'=> $laTmp[3]];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "NO HAY DATOS PARA DNI INGRESADO";
         return false;
      }
      $this->paDatos=$this->laDatos;
      return true;
   }

   protected function mxFileXlsxRegHistParq() {
      $loXls = new CXls();
      $loXls->openXlsIO('HistUsoParqueo', 'R');
      //$loXls->sendXls(0, 'E', 1, 'Periodo: '.$this->paData['CPERIOD']);
      $i = 1;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['CPLACA']);
         $loXls->sendXls(0, 'E', $i, $laFila['TENTRAD']);
         $loXls->sendXls(0, 'F', $i, $laFila['TSALIDA']);
         
      }
      $loXls->closeXlsIO();
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      return true;
   }
   #----------------------------------------------------------
   # ACTUALIZAR ESTADO A CALCULADO => B 
   #----------------------------------------------------------
   public function omEnvioVicerrectorado() {
      $llOk = $this->mxValParamPeriod();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnvioVicerrectorado($loSql);
      $loSql->omDisconnect();
      $laData = $this->paDatos;
      $lo = new CEmailParqueo();
      foreach ($laData as $laTmp) {
         $lo->paData = array($laTmp); 
         $llOk = $lo->omSend();
         if (!$llOk) {
            $this->pcError = $lo->pcError;
            return false;
         }
      }
      $loSql->omDisconnect();
      return $llOk;  
   }
   protected function mxEnvioVicerrectorado($p_oSql) { 
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcPeriod = str_replace('-', '',$this->paData['DFECHA']);
      $lcSql = "UPDATE B11DPER SET CESTADO= 'B', cUsuCod='$lcUsuCod' , tModifi = NOW()  WHERE cperiod = '$lcPeriod'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'NO SE PUEDE ENVIAR A VICERRECTORADO';
         return false;
      } 
      //$this->paDatos[] = ['CEMAVI1' => 'krissmedi@gmail.com', 'CEMAVI2' => 'kymedinac@ucsm.edu.pe','CEMAIL1' => 'kreyesa@ucsm.edu.pe','CEMAIL2' => '74606599@ucsm.edu.pe','CPERIOD' => $this->paData['DFECHA']];
      $this->paData[] = ['CEMAVI1' => 'ccaceres@ucsm.edu.pe', 'CEMAVI2' => 'vradm@ucsm.edu.pe', 'CEMAIL1' => 'fparedesm@ucsm.edu.pe', 'CEMAIL1' => 'smestasr@ucsm.edu.pe','CPERIOD' => $this->paData['DFECHA']];
      return true;
   }

   public function omAprobarVicerrector() {
      $llOk = $this->mxValXlsVice2();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAprobarVicerrector($loSql);
      $loSql->omDisconnect();
      $laData = $this->paDatos;
      $lo = new CEmailParqueo();
      foreach ($laData as $laTmp) {
         $lo->paData = array($laTmp); 
         $llOk = $lo->omSendAprobacionVicerrectorado();
         if (!$llOk) {
            $this->pcError = $lo->pcError;
            return false;
         }
      }
      $loSql->omDisconnect();
      return $llOk; 
   }
   protected function mxAprobarVicerrector($p_oSql) { 
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcPeriod = str_replace('-', '',$this->paData['DFECHA']);
      //ACTUALIZAR ESTADO A CALCULADO => B 
      $lcSql= "SELECT COUNT(CESTADO) FROM B11DPER where cestado='B' AND cperiod = '$lcPeriod'";
      $RS = $p_oSql->omExec($lcSql);
      $laTmp= $p_oSql->fetch($RS);
      if ($laTmp[0]==0){
         $this->pcError = 'NO HAY PENDIENTES DE APROBACIÓN';
         return false;
      } 
      $lcSql = "UPDATE B11DPER SET CESTADO= 'C', cUsuCod='$lcUsuCod' , tModifi = NOW()  WHERE cperiod = '$lcPeriod'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'NO SE PUEDE ENVIAR A VICERRECTORADO';
         return false;
      } 
      //$this->paDatos[] = ['CEMAVI1' => 'krissmedi@gmail.com', 'CEMAVI2' => 'kymedinac@ucsm.edu.pe','CEMAIL1' => 'kreyesa@ucsm.edu.pe','CEMAIL2' => '74606599@ucsm.edu.pe','CPERIOD' => $this->paData['DFECHA']];
      $this->paData[] = ['CEMAVI1' => 'lgalarre@ucsm.edu.pe', 'CEMAVI2' => 'ltalaveram@ucsm.edu.pe','CEMAVI3' => 'jtapiama@ucsm.edu.pe', 'CEMAIL1' => 'fparedesm@ucsm.edu.pe', 'CEMAIL2' => 'smestasr@ucsm.edu.pe','CEMAIL3' => 'cpacheco@ucsm.edu.pe','CEMAIL4' => 'orrhh@ucsm.edu.pe','CPERIOD' => $this->paData['DFECHA']];
      return true;
   }


    // -------------------------------------------------------------------------------
   // Genera archivo xlsx para descuento en RRHH
   // 2023-08-10 FPM Creacion
   // -------------------------------------------------------------------------------
   public function omMostrarRegistroRrhhConta() {
      $llOk = $this->mxValParamXlsxRRHH();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      //CONEXION UCSMDEEP
      $llOk  = $loSql->omConnect(14);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxCargarMostrarRegistroRrhhConta($loSql);
      if (!$llOk) {
         return false;
         $loSql->omDisconnect();
      }
      $loSql->omDisconnect();
      $llOk = $this->mxFileXlsxMostrarRegistroRrhhConta();
      return $llOk;
   }

   protected function mxValParamXlsxRRHH() {
      if (!isset($this->paData['CPERIOD']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxCargarMostrarRegistroRrhhConta($p_oSql) {
      // Valida que se pueda enviar a RRHH
      // VALIDA USUARIO RRHH Y CONTA
      $lcCodUsu = $this->paData['CUSUCOD'];
      // valica CC rrhh y contabilidad
      $lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCenCos in ('02T','02U') AND cCodUsu = '{$lcCodUsu}'";
      // $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCenCos = '02T' AND cCodUsu = '$lcCodUsu' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
   
      // Carga datos del periodo
      $lcPeriod = str_replace('-', '', $this->paData['CPERIOD']);
      $lcSql = "SELECT B.cNroDni, C.cNombre, SUM(A.nMonto) FROM  B11DPER A
                INNER JOIN B11MPAR B ON B.cIdenti = A.cIdenti
                INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni
                WHERE A.cPeriod = '{$lcPeriod}' AND B.cEstado != 'N' AND A.cEstado = 'C' and A.nMonto > 0 
                GROUP BY B.cNroDni,  C.cNombre ORDER BY cNombre";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT cUsuInf, cEstado FROM S01TUSU WHERE cNroDni = '{$laTmp[0]}' ORDER BY cUsuInf";
         // print_r($lcSql);
         // echo('<br>');
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp2 = $p_oSql->fetch($R2)) {
            if ($laTmp2[1] == 'A') {
               $lcUsuInf = $laTmp2[0];
               //print_r($laTmp2);
               break;  
               
            } 
            $lcUsuInf = $laTmp2[0];
         }
         $this->laDatos[] = ['CUSUINF'=> $lcUsuInf, 'CNOMBRE'=> str_replace('/', ' ', $laTmp[1]), 'NMONTO'=> $laTmp[2], 'CNRODNI'=> $laTmp[0]];
      }
      if (count($this->laDatos) == 0) {
         $this->pcError = "NO HAY DATOS PARA PERIODO SELECCIONADO";
         return false;
      }
      $this->paDatos=$this->laDatos;
      return true;
   }

   protected function mxFileXlsxMostrarRegistroRrhhConta() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroRRHH', 'R');
      $loXls->sendXls(0, 'E', 1, 'Periodo: '.$this->paData['CPERIOD']);
      $i = 1;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['NMONTO']);
         
      }
      $loXls->closeXlsIO();
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      return true;
   }  

   public function omMostrarXlsRrhh() {
      $llOk = $this->mxValXlsRrhhContaPeriod();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxFileMostrarXlsRrhhConta();
      return $llOk;
   }

   protected function mxValXlsRrhhContaPeriod() {
      if (!isset($this->paData['CPERIOD']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['CPERIOD'])) {
         $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
         return false;
      } elseif (!isset($this->paDatos)){
         $this->pcError = "PRIMERO DEBE HACER CLIC EN BUSCAR";
         return false;
      }
      return true;
   }

   // protected function mxValXlsVice2() {
   //    if (!isset($this->paData['DFECHA']) || !preg_match('/^20[0-9]{2}-[0-9]{2}$/', $this->paData['DFECHA'])) {
   //       $this->pcError = "PERIODO NO DEFINIDO O INVÁLIDO";
   //       return false;
   //    }
   //    return true;
   // }

   protected function mxFileMostrarXlsRrhhConta() {
      $loXls = new CXls();
      $loXls->openXlsIO('ParqueoCobroRRHH', 'R');
      $loXls->sendXls(0, 'E', 1, 'Periodo: '.$this->paData['CPERIOD']);
      $i = 1;
      foreach ($this->paDatos as $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $laFila['CUSUINF']);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['NMONTO']);
         
      }
      $loXls->closeXlsIO();
      $this->paData = ['CFILXLS'=> $loXls->pcFile];   
      return true;
   } 

}
