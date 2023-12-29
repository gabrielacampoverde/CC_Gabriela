<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Libs/fpdf/fpdf.php";
require_once "Libs/phpqrcode/qrlib.php";

class CSeguroTutor extends CBase {

   public $pcFile;

   public function __construct() {
      parent::__construct();
      $this->pcFile = 'FILES/R'.rand().'.pdf';
   }

   protected function mxValidarParamDni() {
      if (!isset($this->paData['CNRODNI'])){
         $this->pcError = 'NUMERO DE DNI INVALIDO O NO EXISTE';
         return false;
      }
      return true;
   }

   protected function mxValidarParamBusqueda() {
      if (!isset($this->paData['CNRODNI']) OR strlen($this->paData['CNRODNI']) != 8){
         $this->pcError = 'DNI DE ALUMNO NO DEFINIDO O INVALIDO';
         return false;
      }
      return true;
   }

    #-----------------------------------------------------------
    # INICIAR DATOS DE LA PANTALLA INICIAL
    # Creacion ASR 2022-04-28
    #-----------------------------------------------------------
    public function omInit() {
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
       /*$llOk = $this->mxValParamUsuario($loSql, '000');
       if (!$llOk) {
          $loSql->omDisconnect();
          return false;
       }*/
       $llOk = $this->mxInit($loSql);
       $loSql->omDisconnect();
       return $llOk;
   }

   protected function mxInit($p_oSql) {
      $laData = ['ABANCO'=> '','ABANDEJA'=> ''];
      $laDatos = [];
      $lcSql = "SELECT cCodBco, cDescri FROM S01TBCO WHERE cEstado ='A' AND cTipo ='B' ORDER BY CDESCRI";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laDatos[] = ['CCODBCO' => $laFila[0] ,'CDESCRI' => $laFila[1]];
      }

      if (count($laDatos) == 0) {
         $this->pcError = 'NO SE ENCONTRARON REGISTROS 1';
         return false;
      }
      $laData['ABANCO'] = $laDatos;
      $laDatos = [];
      $lcSql = "SELECT A.cIdPago, A.cCodBco, A.cDescri, A.dFecha, A.nMonto, B.cDescri FROM A05MSEG A
               INNER JOIN S01TBCO B ON B.cCodBco = A.cCodBco WHERE A.cEstado='A' ORDER BY A.tModifi DESC";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laDatos[] = ['CIDPAGO' => $laFila[0],'CCODBCO' => $laFila[1], 'CDESCRI' => $laFila[2] ,
                                   'DFECHA' => $laFila[3], 
                                   'NMONTO' => $laFila[4], 'CDESBCO'=>$laFila[5]];
      }
      $laData['ABANDEJA'] = $laDatos;
      $this->paDatos=$laData;
      return true;
   }

   protected function mxInit_old($p_oSql) {
      print_r($p_oSql);
      $laDatos = ['ABANCO'=> [],'ABANDEJA' => []];
      echo 'aaa';
      /*$lcSql = "SELECT A.cIdPago, A.cCodBco, A.cDescri, A.dFecha, A.nMonto, B.cDescri FROM A05MSEG A
               INNER JOIN S01TBCO B ON B.cCodBco = A.cCodBco WHERE A.cEstado='A' ORDER BY A.tModifi DESC";
      echo '222';
      $RS = $p_oSql->omExec($lcSql);
      echo '3333';

      print_r($RS);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laDatos['ABANDEJA'][] = ['CIDPAGO' => $laFila[0],'CCODBCO' => $laFila[1], 'CDESCRI' => $laFila[2] ,
                                   'DFECHA' => $laFila[3], 
                                   'NMONTO' => $laFila[4], 'CDESBCO'=>$laFila[5]];
          echo '****';
      }*/
      $lcSql = "SELECT cCodBco, cDescri FROM S01TBCO WHERE cEstado ='A' AND cTipo ='B' ORDER BY CDESCRI";
      echo $lcSql;
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laDatos['ABANCO'][] = ['CCODBCO' => $laFila[0] ,'CDESCRI' => $laFila[1]];
      }

      if (count($laDatos['ABANCO']) == 0) {
         $this->pcError = 'NO SE ENCONTRARON REGISTROS 1';
         return false;
      }
      print_r($laDatos);
      $this->paDatos=$laDatos;
      return true;
   }

    #-----------------------------------------------------------
    # GRABAR DATOS DEL SEGURO (MONTO)
    # Creacion ASR 2022-04-28
    #-----------------------------------------------------------
   public function omGrabarSeguro() {
      $llOk = $this->mxValParamSeguro();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamSeguro() {
      if (!isset($this->paData['CCODBCO']) OR strlen($this->paData['CCODBCO']) != 3){
         $this->pcError = 'CODIGO DE BANCO NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CDESCRI'])){
         $this->pcError = 'DESCRIPCION NO DEFINIDA';
         return false;
      } elseif (!isset($this->paData['DFECHA'])){
         $this->pcError = 'FECHA NO DEFINIDA O INVALIDA';
         return false;
      } elseif (!isset($this->paData['NMONTO'])){
         $this->pcError = 'MONTO NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) OR strlen($this->paData['CUSUCOD']) != 4){
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxGrabarSeguro($p_oSql) {
      $lcSql = "SELECT MAX(cIdPago) FROM A05MSEG";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila) {
         $this->pcError = "NUMERO DE SEGURO YA SE ENCUENTRA REGISTRADO";
         return false;
      }
      $i = (int)$laFila[0] + 1;
      $lcIdPag = '00'.(string)$i;
      $lcIdPag = right($lcIdPag, 3);
      $lcSql = "INSERT INTO A05MSEG(cIdPago, cCodBco, cDescri, cEstado, dFecha, nMonto, cUsuCod) VALUES
                ('$lcIdPag','{$this->paData['CCODBCO']}','{$this->paData['CDESCRI']}','A',
                 '{$this->paData['DFECHA']}','{$this->paData['NMONTO']}','{$this->paData['CUSUCOD']}')";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR AL INSERTAR REGISTRO';
         return false;
      }
      return true;
   }


   #--------------------------------------------------------------------
   # ANULAR SEGURO
   # Creacion ASR 2022-06-09
   #--------------------------------------------------------------------
   
   public function omAnularSeguro() {
      $llOk = $this->mxValidarParamIdPago();
      if (!$llOk) {
          return false;
      } 
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      } 
      $llOk = $this->mxAnularSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
  }

  protected function mxAnularSeguro($p_oSql) {
     $lcSql = "UPDATE A05MSEG SET cEstado = 'X', cUsuCod='{$this->paData['CUSUCOD']}',tModifi =NOW() WHERE 
               cIdPago='{$this->paData['CIDPAGO']}'";
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
        $this->pcError = "ERROR AL ANULAR SEGURO";
        return false;
     }
     $lcSql = "SELECT COUNT(cIdPago) FROM A05PSEG WHERE cIdPago='{$this->paData['CIDPAGO']}'";
     $RS = $p_oSql->omExec($lcSql);
     $laFila = $p_oSql->fetch($RS);
     if (!isset($laFila[0])) {
         $this->pcError = "NO SE ENCONTRARON DATOS";
         return false;
     }
     if ($laFila[0]> 0){
         $lcSql = "UPDATE A05PSEG SET cEstado = 'X', cUsuCod='{$this->paData['CUSUCOD']}',tModifi =NOW() WHERE 
                   cIdPago='{$this->paData['CIDPAGO']}'";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "ERROR AL ANULAR SEGURO";
            return false;
         }
     }
     return true;
  }


   #--------------------------------------------------------------------
   # ACTUALIZAR ESTADO SEGURO
   # Creacion ASR 2022-06-09
   #--------------------------------------------------------------------
   
   public function omActualizarDatosSeguro() {
      $llOk = $this->mxValidarParamIdPago();
      if (!$llOk) {
          return false;
      } 
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      } 
      $llOk = $this->mxActualizarDatosSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxActualizarDatosSeguro($p_oSql) {
     $lcSql = "UPDATE A05MSEG SET cCodBco='{$this->paData['CCODBCO']}', cDescri ='{$this->paData['CDESCRI']}', dFecha ='{$this->paData['DFECHA']}',
               nMonto='{$this->paData['NMONTO']}',cUsuCod='{$this->paData['CUSUCOD']}',tModifi =NOW() WHERE 
               cIdPago='{$this->paData['CIDPAGO']}'";
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
        $this->pcError = "ERROR AL ANULAR SEGURO";
        return false;
     }
     return true;
   }



    #-----------------------------------------------------------
    # BUSQUEDA DE ESTUDIANTE POR DNI
    # Creacion ASR 2022-04-28
    #-----------------------------------------------------------
    public function omBuscarAlumno() {
      $llOk = $this->mxValidarParamDni();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarAlumno($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAlumno($p_oSql) {
      $lcSql = "SELECT cNombre FROM V_A01MALU WHERE cNroDni ='{$this->paData['CNRODNI']}' 
                LIMIT 1";
      $i = 0;
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == "") {
         $this->pcError = ['ERROR'=>'NO SE ENCONTRARON REGISTROS'];
         return false;
      }
      $lcNombre = ['CNOMBRE' => str_replace("/"," ",$laFila[0])];
      $this->paData=$lcNombre;
      return true;
   }
   
    #-----------------------------------------------------------
    # CONSULTAR DATOS DEL ESTUDIANTE
    # Creacion ASR 2022-04-28
    #-----------------------------------------------------------
   public function omConsultar() {
      $llOk = $this->mxValidarParamDni();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxConsultar($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxConsultar($p_oSql) {
      $lcSql = "SELECT cNombre, cCodAlu, cNroDni, cUniAca, cNomUni FROM V_A01MALU WHERE cNroDni ='{$this->paData['CNRODNI']}' 
                AND CUNIACA NOT IN ('20','21','10')";
      $i = 0;
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]) ,'CCODALU' => $laFila[1], 'CNRODNI' => $laFila[2], 
                             'CUNIACA' =>$laFila[3], 'CNOMUNI' =>$laFila[4]];
         $i++;
      }
      if ($i==0) {
         $this->pcError = 'NO SE ENCONTRARON REGISTROS';
         return false;
      }
      return true;
   }


   #-----------------------------------------------------------
   # INICIAR DATOS DE LA PANTALLA INICIAL  ---SEG1020
   # Creacion ASR 2021-12-30
   #-----------------------------------------------------------
   public function omInitSeguro() {
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
      $llOk = $this->mxInitSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
  }

  protected function mxInitSeguro($p_oSql) {
     $lcSql = "SELECT A.cIdPago, A.cCodBco, A.cDescri, A.dFecha, A.nMonto, B.cDescri FROM A05MSEG A
               INNER JOIN S01TBCO B ON B.cCodBco = A.cCodBco WHERE A.cEstado IN ('A','B') ORDER BY A.tModifi DESC";
     $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDPAGO' => $laFila[0],'CCODBCO' => $laFila[1], 'CDESCRI' => $laFila[2] ,'DFECHA' => $laFila[3], 
                           'NMONTO' => $laFila[4], 'CDESBCO'=>$laFila[5],'NTOTAL' =>0];
     }
     $i=0;
     foreach($this->paDatos AS $laTmp){
         $lcSql = "SELECT SUM(nMonto) FROM A05PSEG WHERE cIdPago ='{$laTmp['CIDPAGO']}'";
         $RS = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($RS);
         if($laFila[0]==null){
            $this->paDatos[$i]['NTOTAL'] = 0;
         }else{
            $this->paDatos[$i]['NTOTAL'] = $laFila[0];
         }  
         $i++;
      }
     return true;
  }


 #-----------------------------------------------------------
 # CARGA LOS ALUMNOS GRABADOS DEL SEGURO
 # Creacion ASR 2022-05-04
 #-----------------------------------------------------------
 
 protected function mxValidarParamIdPago() {
   if (!isset($this->paData['CIDPAGO']) OR strlen($this->paData['CIDPAGO']) != 3){
      $this->pcError = 'IDENTIFICADOR DE PAGO NO DEFINIDO O INVALIDO';
      return false;
   }
   return true;
 }
 
 public function omCargarAlumnos() {
   $llOk = $this->mxValidarParamIdPago();
   if (!$llOk) {
       return false;
   }
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
   } 
   $llOk = $this->mxCargarAlumnos($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxCargarAlumnos($p_oSql) {
  $lcSql = "SELECT DISTINCT A.cIdSegu, A.cNroDni, B.cNombre, A.dInicio, A.nMonto FROM A05PSEG A
            INNER JOIN V_A01MALU B ON B.cNroDni = A.cNroDni WHERE A.cIdPago ='{$this->paData['CIDPAGO']}'";
  $RS = $p_oSql->omExec($lcSql);
  while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CIDSEG' => $laFila[0],'CIDPAGO' =>$this->paData['CIDPAGO'],'CNRODNI' => $laFila[1], 
                           'CNOMBRE' => str_replace ('/',' ',$laFila[2]) ,
                           'DFECHA' => $laFila[3], 'NMONTO' => $laFila[4]];
  }

  $lcSql = "SELECT SUM(nMonto) FROM A05PSEG WHERE cIdPago ='{$this->paData['CIDPAGO']}'";
  $RS = $p_oSql->omExec($lcSql);
  $laFila = $p_oSql->fetch($RS);
  if($laFila[0]==null){
      $lnMonto = 0;
  }else{
      $lnMonto = $laFila[0];
  } 
  $this->paData = null;
  $this->paData = $lnMonto;
  
  return true;
}

#-----------------------------------------------------------
# CARGA EL SIGUIENTE CODIGO DE PAGO
# Creacion ASR 2022-05-04
#-----------------------------------------------------------
   public function omCargarIdPago() {
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
      $llOk = $this->mxCargarIdPag($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarIdPag($p_oSql) {
      $lcSql = "SELECT MAX(cIdSegu) FROM A05PSEG";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!$laFila) {
            $this->pcError = "IDENTIFICADOR DE SEGURO YA SE ENCUENTRA REGISTRADO";
            return false;
      }
      $i = (int)$laFila[0] + 1;
      $lcIdSeg = '000'.(string)$i;
      $lcIdSeg = right($lcIdSeg, 4);
      $this->paData = $lcIdSeg;
      return true;
   }


#-----------------------------------------------------------
# BUSQUEDA DE ALUMNOS POR CODIGO
# Creacion ASR 2022-05-04
#-----------------------------------------------------------
   public function omBuscarAlumnoCodigo() {
      $llOk = $this->mxValidarParamBusqueda();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      } 
      $llOk = $this->mxBuscarAlumnoCodigo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAlumnoCodigo($p_oSql) {
      $lcSql = "SELECT cNombre from V_A01MALU WHERE cNroDni='{$this->paData['CNRODNI']}' LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0])){
         $this->pcError = 'NO SE ENCONTRARON DATOS DEL ALUMNO';
         return false;
      }
      $this->paData = ['CNOMBRE' => str_replace('/',' ',$laFila[0])];
      return true;
   }

#-----------------------------------------------------------
# INSERTAR DATOS DE ALUMNOS EN TABLA DE SEGURO
# Creacion ASR 2022-05-05
#-----------------------------------------------------------
   protected function mxValidarParamGrabar() {
      if (!isset($this->paData['CIDPAGO']) OR strlen($this->paData['CIDPAGO']) != 3 ){
         $this->pcError = 'IDENTIFICADOR DE PAGO NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) OR strlen($this->paData['CUSUCOD']) != 4 ){
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
         return false;
      }
      $laDni =[]; // Almacena los DNI

      $lnCont =0;
      $this->paData['CFLAG'] =false; //Valida que el monto sea igual a la suma de montos de alumnos
      foreach ($this->paDatos AS $laTmp) {
         $lnCont = $lnCont+$laTmp['NMONTO'];
         if (!isset($laTmp['CNRODNI']) OR strlen($laTmp['CNRODNI'])!= 8){
            $this->pcError = 'CODIGO DE ALUMNO NO DEFINIDO O INVALIDO';
            return false;
         } elseif (!isset($laTmp['NMONTO'])){
            $this->pcError = 'MONTO NO DEFINIDO O INVALIDO';
            return false;
         }
         array_push($laDni,$laTmp['CNRODNI']); //AGREGA DNI EN EL ARREGLO
      }
      if (count($laDni) > count (array_unique($laDni))){  // VALIDA SI SE REPITE DNI
         $this->pcError = 'DNI DE ALUMNO INGRESADO ANTERIORMENTE ';
         return false;
      }
      if($lnCont > $this->paData['NMONTO']){
         $this->pcError = 'SE EXCEDIO EL MONTO PERMITIDO';
         return false;
      } elseif ($lnCont == $this->paData['NMONTO']){
         $this->paData['CFLAG']= true;
      }
      return true;
   }
 
   public function omGrabarAlumnos() {
      $llOk = $this->mxValidarParamGrabar();
      if (!$llOk) {
          return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      } 
      $llOk = $this->mxGrabarAlumnos($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxGrabarAlumnos($p_oSql) {
      foreach ($this->paDatos as $laTmp) {
         if ($laTmp['CIDSEG'] == '*') {
            // Siguiente CIDSEG
            $lcSql = "SELECT MAX(cIdSegu) FROM A05PSEG";
            $RS = $p_oSql->omExec($lcSql);
            $laFila = $p_oSql->fetch($RS);
            if (!$laFila) {
               $this->pcError = "NUMERO DE SEGURO YA SE ENCUENTRA REGISTRADO";
               return false;
            }
            $i = (int)$laFila[0] + 1;
            $lcIdSeg = '000'.(string)$i;
            $lcIdSeg = right($lcIdSeg, 4);
            $lcSql = "INSERT INTO A05PSEG (cIdSegu, cIdPago, cNroDni, dInicio, nMonto, mDatos,cUsuCod) 
                      VALUES('$lcIdSeg', '{$this->paData['CIDPAGO']}', '{$laTmp['CNRODNI']}', NOW(), {$laTmp['NMONTO']}, '{}' ,'{$this->paData['CUSUCOD']}')"; 
         } else {
            $lcSql = "UPDATE A05PSEG set cNroDni = '{$laTmp['CNRODNI']}', nMonto = {$laTmp['NMONTO']}, cUsuCod ='{$this->paData['CUSUCOD']}',
                      tModifi = NOW() WHERE cIdSegu = '{$laTmp['CIDSEG']}'";
         }
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = "ERROR AL INSERTAR/ACTUALIZAR REGISTRO {$laTmp['CIDSEG']}";
            return false;
         }
      }
      if ($this->paData['CFLAG'] == true){
         $lcSql = "UPDATE A05MSEG set cEstado='B', cUsuCod ='{$this->paData['CUSUCOD']}',
                      tModifi = NOW() WHERE cIdPago = '{$this->paData['CIDPAGO']}'";
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
              $this->pcError = "ERROR AL ACTUALIZAR CABECERA {$this->paData['CIDPAGO']}";
              return false;
            }
      }
     return true;
   }


#-----------------------------------------------------------
# INICIAR DATOS DE LA PANTALLA INICIAL - SEG1030
# Creacion ASR 2022-06-10
#-----------------------------------------------------------
   public function omInitPagarSeguro() {
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
      /*$llOk = $this->mxValParamUsuario($loSql, '000');
      if (!$llOk) {
          $loSql->omDisconnect();
          return false;
      }*/
      $loSql->omDisconnect();
      return $llOk;
   }



#-----------------------------------------------------------
# CONSULTAR DATOS DEL ESTUDIANTE 
# Creacion ASR 2022-04-28
#-----------------------------------------------------------
   public function omBuscarAlumnosSeguro() {
      $llOk = $this->mxValidarParamBusqueda();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarAlumnosSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAlumnosSeguro($p_oSql) {   
      $lcSql = "SELECT distinct A.cNroDni, B.cNombre, A.nMonto, A.cIdSegu FROM A05PSEG A
               INNER JOIN S01MPER B ON B.cNroDni =A.cNroDni WHERE A.cNroDni ='{$this->paData['CNRODNI']}' AND A.cIdPago ='{$this->paData['CIDPAGO']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0])) {
         $this->pcError = 'NO SE ENCONTRO SEGURO';
         return false;
      }
      $this->paDatos['ADATOS'] = ['CNRODNI' =>  $laFila[0] ,'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'NMONTO' => $laFila[2], 
                        'CIDSEGU' => $laFila[3],'NTOTAL' => number_format($laFila[2], 2, '.', ',')];
      $lcSql = "SELECT SUM(nMonto) FROM A05DSEG WHERE cIdSegu ='{$this->paDatos['ADATOS']['CIDSEGU']}'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if($laFila[0]==null){
         $lnMonto = 0;
      }else{
         $lnMonto = $laFila[0];
      } 
      $lnTotal = $this->paDatos['ADATOS']['NMONTO'] - $lnMonto;
      $this->paDatos['ADATOS']['NSALDO'] = number_format($lnTotal, 2, '.', ','); // En nmonto se va restando lo que se paga, en ntotal se tiene el monto total 
      $lcSql = "SELECT cCodAlu,cNomUni FROM V_A01MALU WHERE CNRODNI ='{$this->paData['CNRODNI']}' AND cUniAca not in('10')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos['AUNIACA'][] = ['CCODALU' =>  $laFila[0] ,'CNOMUNI' => $laFila[1]];
      }
      if (count($this->paDatos['AUNIACA'])==0){
         $this->pcError = 'NO SE ENCONTRARON UNIDADES ACADEMICAS';
         return false;
      }
      // Observaciones para el historico
      $laCodAlu =[];
      foreach ($this->paDatos['AUNIACA'] as $laTmp) {
         array_push($laCodAlu,$laTmp['CCODALU']);
      }
      $lcCodAlu = implode("','", $laCodAlu);
      $lcSql = "SELECT A.cCodAlu,A.dFecha,A.mObserv,B.cUniAca,B.cNomUni FROM A05DSEG A
                INNER JOIN V_A01MALU B ON B.cCodAlu =A.cCodAlu 
                WHERE A.cCodAlu IN ('{$lcCodAlu}')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos['AOBSERV'][] = ['CCODALU' =>  $laFila[0] ,'DFECHA' => $laFila[1],'MOBSERV' => $laFila[2],'CUNIACA'=> $laFila[3],
                                        'CNOMUNI' => $laFila[4]];
      }
      return true;
   }



#-----------------------------------------------------------
# Restar monto del seguro de un estudiante
# Creacion ASR 2022-04-28
#-----------------------------------------------------------
   protected function mxValidarParamPagar() {
      if (!isset($this->paData['NSALDO'])){
         $this->pcError = 'SALDO DISPONIBLE NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['MOBSERV']) ){
         $this->pcError = 'OBSERVACIONES NO DEFINIDAS O INVALIDAS';
         return false;
      } elseif (!isset($this->paData['CIDSEGU']) OR strlen($this->paData['CIDSEGU']) != 4 ){
         $this->pcError = 'IDENTIFICADOR DE SEGURO NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['DFECHA'])){
         $this->pcError = 'FECHA DE REGISTRO NO DEFINIDA O INVALIDA';
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) OR strlen($this->paData['CUSUCOD']) != 4){
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['NMONTO'])){
         $this->pcError = 'MONTO A PAGAR NO DEFINIDO O INVALIDO';
         return false;
      }
      return true;
   }

   public function omPagar() {
      $llOk = $this->mxValidarParamPagar();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPagar($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxPagar($p_oSql) {  
      $lnMonto =  (float)str_replace(',','',(string)$this->paData['NMONTO']);
      $lnSaldo =  (float)str_replace(',','',(string)$this->paData['NSALDO']);
      if ($lnMonto > $lnSaldo){ // VERIFICA QUE LO QUE SE QUIERE PAGAR NO EXCEDA EL SALDO DEL SEGURO
         $this->pcError ='SE EXCEDIO EL SALDO DISPONIBLE';
         return false;
      } 
      $lcSql = "INSERT INTO A05DSEG (nSerial, cIdSegu, cAfecta, nMonto, dFecha, mObserv, cCodAlu,cUsuCod,tmodifi) VALUES 
               (DEFAULT ,'{$this->paData['CIDSEGU']}','{}',{$this->paData['NMONTO']},'{$this->paData['DFECHA']}',
                  '{$this->paData['MOBSERV']}','{$this->paData['CCODALU']}','{$this->paData['CUSUCOD']}',NOW())";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "ERROR AL REGISTRAR PAGO";
         return false;
      }
      return true;
   }


   #--------------------------------------------------------------------
   # MOSTRAR SEGUROS DEL ESTUDIANTE
   # Creacion ASR 2022-06-11
   #--------------------------------------------------------------------
   public function omMostrarSeguros() {
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
      $llOk = $this->mxMostrarSeguros($loSql);
      $loSql->omDisconnect();
      return $llOk;
  }

  protected function mxMostrarSeguros($p_oSql) {
     $lcSql = "SELECT A.cIdPago, A.cCodBco, A.cDescri, A.dFecha, C.nMonto, B.cDescri FROM A05MSEG A
               INNER JOIN S01TBCO B ON B.cCodBco = A.cCodBco
               INNER JOIN A05PSEG C ON C.cIdPago = A.cIdPago WHERE A.cEstado='C' AND C.cNroDni ='{$this->paData['CNRODNI']}'
               ORDER BY A.tModifi DESC";
     $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CIDPAGO' => $laFila[0],'CCODBCO' => $laFila[1], 'CDESCRI' => $laFila[2] ,'DFECHA' => $laFila[3], 
                           'NMONTO' => $laFila[4], 'CDESBCO'=>$laFila[5]];
     }
     return true;
  }

   

   #--------------------------------------------------------------------
   # INICIAR DATOS DE LA PANTALLA INICIAL APROBAR SEGUROS  ---SEG1040
   # Creacion ASR 2021-12-30
   #--------------------------------------------------------------------
   public function omInitAprobarSeguro() {
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
      $llOk = $this->mxInitAprobarSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
  }

  protected function mxInitAprobarSeguro($p_oSql) {
     $lcSql = "SELECT A.cIdPago, A.cCodBco, A.cDescri, A.dFecha, A.nMonto, B.cDescri FROM A05MSEG A
               INNER JOIN S01TBCO B ON B.cCodBco = A.cCodBco WHERE A.cEstado='B' ORDER BY A.tModifi DESC";
     $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CIDPAGO' => $laFila[0],'CCODBCO' => $laFila[1], 'CDESCRI' => $laFila[2] ,'DFECHA' => $laFila[3], 
                           'NMONTO' => $laFila[4], 'CDESBCO'=>$laFila[5]];
     }
     return true;
  }

   #--------------------------------------------------------------------
   # ACTUALIZAR ESTADO SEGURO
   # Creacion ASR 2022-05-19
   #--------------------------------------------------------------------
   
   public function omAprobarSeguro() {
      $llOk = $this->mxValidarParamIdPago();
      if (!$llOk) {
          return false;
      } 
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      } 
      $llOk = $this->mxAprobarSeguro($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      /*if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }*/
      $loSql->omDisconnect();
      return $llOk;
  }

  protected function mxAprobarSeguro($p_oSql) {
     $lcSql = "UPDATE A05MSEG SET cEstado = 'C', cUsuCod='{$this->paData['CUSUCOD']}',tModifi =NOW() WHERE 
               cIdPago='{$this->paData['CIDPAGO']}'";
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
        $this->pcError = "ERROR AL ACTUALIZAR REGISTRO";
        return false;
     }
     return true;
  }


   #--------------------------------------------------------------------
   # BANDEJA DE ENTRADA CONSULTA DE SEGUROS  ---SEG1050
   # Creacion ASR 2022-06-14
   #--------------------------------------------------------------------
   public function omInitConsultaSeguro() {
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
      $llOk = $this->mxInitConsultaSeguro($loSql);
      $loSql->omDisconnect();
      return $llOk;
  }

  protected function mxInitConsultaSeguro($p_oSql) {
     $lcSql = "SELECT A.cIdPago, A.cCodBco, A.cDescri, A.dFecha, A.nMonto, B.cDescri,A.cEstado FROM A05MSEG A
               INNER JOIN S01TBCO B ON B.cCodBco = A.cCodBco ORDER BY A.tModifi DESC";
     $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CIDPAGO' => $laFila[0],'CCODBCO' => $laFila[1], 'CDESCRI' => $laFila[2] ,'DFECHA' => $laFila[3], 
                           'NMONTO' => $laFila[4], 'CDESBCO'=>$laFila[5], 'CESTADO' => $laFila[6]];
     }
     return true;
  }


#-----------------------------------------------------------
# DETALLES DE LOS SEGUROS
# Creacion ASR 2022-04-28
#-----------------------------------------------------------

protected function mxValidarParamDetalleSeguro() {
   if (!isset($this->paData['CUSUCOD']) OR !preg_match('/^[0-9,A-Z]{1}[0-9]{3}$/', $this->paData['CUSUCOD'])){
      $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CCENCOS']) OR !preg_match('/^[0-9,A-Z]{3}$/', $this->paData['CCENCOS'])){
      $this->pcError = 'CODIGO DE COSTOS NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CIDPAGO']) OR strlen($this->paData['CIDPAGO'])!=3){
      $this->pcError = 'IDENTIFICADOR DE PAGO INVALIDO O NO EXISTE';
      return false;
   }
   return true;
}


public function omDetallesSeguro() {
   $llOk = $this->mxValidarParamDetalleSeguro();
   if (!$llOk) {
      return false;
   }
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
   }
   $llOk = $this->mxDetallesSeguro($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxDetallesSeguro($p_oSql) {   
   $lcSql = "SELECT  A.cNroDni, B.cNombre, A.nMonto, A.cIdSegu, A.dInicio FROM A05PSEG A
             INNER JOIN S01MPER B ON B.cNroDni =A.cNroDni WHERE  A.cIdPago ='{$this->paData['CIDPAGO']}' ORDER BY A.cIdPago ASC";
   $RS = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CNRODNI' =>  $laFila[0] ,'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'NMONTO' => $laFila[2], 
                           'CIDSEGU' => $laFila[3],'DFECHA' => $laFila[4] ,'CIDPAGO' => $this->paData['CIDPAGO']];
   }
   if (count($this->paDatos)==0){
      $this->pcError = 'NO SE DETALLES DEL SEGURO';
      return false;
   }
   return true;
}

#-----------------------------------------------------------
# DETALLES DE LOS SEGUROS
# Creacion ASR 2022-04-28
#-----------------------------------------------------------

protected function mxValidarParamDetalleEstudiante() {
   if (!isset($this->paData['CUSUCOD']) OR !preg_match('/^[0-9,A-Z]{1}[0-9]{3}$/', $this->paData['CUSUCOD'])){
      $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CCENCOS']) OR !preg_match('/^[0-9,A-Z]{3}$/', $this->paData['CCENCOS'])){
      $this->pcError = 'CODIGO DE COSTOS NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CIDSEGU']) OR strlen($this->paData['CIDSEGU'])!=4){
      $this->pcError = 'IDENTIFICADOR DE SEGURO INVALIDO O NO EXISTE';
      return false;
   }
   return true;
}


public function omDetallesEstudiante() {
   $llOk = $this->mxValidarParamDetalleEstudiante();
   if (!$llOk) {
      return false;
   }
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
   }
   $llOk = $this->mxDetallesEstudiante($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxDetallesEstudiante($p_oSql) {   
   $lcSql = "SELECT C.cNombre, C.cCodAlu ,C.cNomUni,B.nMonto, A.cIdSegu, A.dFecha, A.mObserv FROM A05DSEG A 
             INNER JOIN A05PSEG B ON B.cIdSegu = A.cIdSegu 
             INNER JOIN V_A01MALU C ON C.cNroDni =B.cNroDni WHERE  A.cIdSegu ='{$this->paData['CIDSEGU']}'";
   $RS = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($RS)) {
      $this->paDatos = ['CNOMBRE' =>  str_replace('/', ' ', $laFila[0]) ,'CCODALU' => $laFila[1], 'CDESUNI' => $laFila[2],
                        'NMONTO' => $laFila[3], 'CIDSEGU' => $laFila[4],'DFECHA' => $laFila[5] ,
                        'MOBSERV' => $laFila[6]];
   }
   if (count($this->paDatos)==0){
      $this->pcError = 'NO SE ENCONTRO REGISTRO DE PAGO';
      return false;
   }
   return true;
}


#--------------------------------------------------------------------
# BANDEJA DE ENTRADA CONSULTA DE SEGUROS  ---SEG1060
# Creacion ASR 2022-06-14
#--------------------------------------------------------------------
public function omInitConsultaDepositos() {
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
   $llOk = $this->mxInitConsultaDepositos($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxInitConsultaDepositos($p_oSql) {
   $lcSql = "SELECT C.cIdSegu, C.cCodAlu,D.cNombre, C.nMonto, C.dFecha FROM A05MSEG A
               INNER JOIN A05PSEG B ON B.cIdPago = A.cIdPago
               INNER JOIN A05DSEG C ON C.cIdSegu = B.cIdSegu 
               INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu 
               WHERE A.cEstado!='X' ORDER BY A.tModifi DESC";
   $RS = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CIDSEGU' => $laFila[0],'CCODALU' => $laFila[1], 'CNOMBRE' => str_replace('/',' ',$laFila[2]) ,
                           'NMONTO' => $laFila[3], 'DFECHA' => $laFila[4]];
   }
   return true;
}

#--------------------------------------------------------------------
# MOSTRAR DEPOSITOS
# Creacion ASR 2022-06-12
#--------------------------------------------------------------------
protected function mxValidarParamFecha() {
   if (!isset($this->paData['CUSUCOD']) OR !preg_match('/^[0-9,A-Z]{1}[0-9]{3}$/', $this->paData['CUSUCOD'])){
      $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CCENCOS']) OR !preg_match('/^[0-9,A-Z]{3}$/', $this->paData['CCENCOS'])){
      $this->pcError = 'CODIGO DE COSTOS NO DEFINIDO O INVALIDO';
      return false;
   } elseif(!isset($this->paData['DFECHA'])){
      $this->pcError = 'FECHA DE MOVIMIENTOS NO DEFINIDO O INVALIDA';
      return false;
   }
   return true;
}

public function omMostrarPagos() {
   $llOk = $this->mxValidarParamFecha();
   if (!$llOk) {
      return false;
   } 
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
   } 
   $llOk = $this->mxMostrarPagos($loSql);
   if (!$llOk) {
      $loSql->omDisconnect();
      return false;
   }
   $loSql->omDisconnect();
   return true;
  }

protected function mxMostrarPagos($p_oSql) {
   $i=1;
   $lo = new CDate();
   while($i<13){
      $lcMes = (string)$i;
      if (strlen($lcMes) == 1){
         $lcMes = '0'.$lcMes;
      }
      $lcFecha = $this->paData['DFECHA'].'-'.$lcMes;
      $lcMesText = $lo->dateTextMonth($lcMes);
      $lcSql = "SELECT SUM(NMONTO) FROM A05MSEG WHERE TO_CHAR(dFecha,'YYYY-mm-dd') LIKE '{$lcFecha}%' AND cEstado!='X'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0])){
         $lnMonto = 0;
      } else{
         $lnMonto = $laFila[0];
      }
      //Calcular el monto del detalle de los seguros
      $lcSql = "SELECT SUM(NMONTO) FROM A05DSEG WHERE TO_CHAR(dFecha,'YYYY-mm-dd') LIKE '{$lcFecha}%'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0])){
         $lnDeposito = 0;
      } else{
         $lnDeposito = $laFila[0];
      }
      $lnSaldo = $lnMonto - $lnDeposito;
      $this->paDatos[] = ['CMES' => $lcMesText,'NMONTO' => $lnMonto, 'NDEPOSITO' => $lnDeposito, 'NSALDO' => $lnSaldo];
      $i++;
   }
   if (count($this->paDatos) == 0){
      $this->pcError='NO SE ENCONTRARON DEPOSITOS';
      return false;
   }
   return true;
}

#-----------------------------------------------------------
# MOSTRAR MOVIMIENTOS 
# Creacion ASR 2022-06-13
#-----------------------------------------------------------
public function omBuscarMovimientos() {
   $llOk = $this->mxValidarParamFecha();
   if (!$llOk) {
      return false;
   }
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
   }
   $llOk = $this->mxBuscarMovimientos($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxBuscarMovimientos($p_oSql) {   
   $lcSql = "SELECT SUM(C.nMonto),C.cIdSegu,A.cDescri FROM A05MSEG A
             INNER JOIN A05PSEG B ON B.cIdPago = A.cIdPago
             INNER JOIN A05DSEG C ON C.cIdSegu = B.cIdSegu 
             INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu 
             WHERE A.cEstado!='X' AND TO_CHAR(C.dFecha,'YYYY-mm-dd') LIKE '{$this->paData['DFECHA']}%'
             GROUP BY C.cIdSegu,A.cDescri";
   $RS = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($RS)) {
      $this->paDatos[] = ['NMONTO' => $laFila[0], 'CIDSEGU' => $laFila[1],'CDESCRI' => $laFila[2]];
   }
   return true;
}

#-----------------------------------------------------------
# BUSQUEDA DE ESTUDIANTE POR CODIGO
# Creacion ASR 2022-04-28
#-----------------------------------------------------------
protected function mxValidarParamCod() {
   if(!isset($this->paData['CCODALU']) OR strlen($this->paData['CCODALU']) !=10){
      $this->pcError = 'CODIGO DE ESTUDIANTE NO VALIDO';
      return false;
   }
   return true;
}

public function omBuscarAlumnoCod() {
   $llOk = $this->mxValidarParamCod();
   if (!$llOk) {
      return false;
   }
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
   }
   $llOk = $this->mxBuscarAlumnoCod($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxBuscarAlumnoCod($p_oSql) {
   $lcSql = "SELECT cNombre FROM V_A01MALU WHERE cCodAlu ='{$this->paData['CCODALU']}' 
             LIMIT 1";
   $i = 0;
   $RS = $p_oSql->omExec($lcSql);
   $laFila = $p_oSql->fetch($RS);
   if ($laFila[0] == "") {
      $this->pcError = 'NO SE ENCONTRARON REGISTROS';
      return false;
   }
   $lcNombre = ['CNOMBRE' => str_replace("/"," ",$laFila[0])];
   $this->paData=$lcNombre;
   return true;
}


#--------------------------------------------------------------------
# MOSTRAR SEGUROS DEL ESTUDIANTE
# Creacion ASR 2022-06-11
#--------------------------------------------------------------------
protected function mxValidarParamConsulta() {
   if (!isset($this->paData['CUSUCOD']) OR !preg_match('/^[0-9,A-Z]{1}[0-9]{3}$/', $this->paData['CUSUCOD'])){
      $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CCENCOS']) OR !preg_match('/^[0-9,A-Z]{3}$/', $this->paData['CCENCOS'])){
      $this->pcError = 'CODIGO DE COSTOS NO DEFINIDO O INVALIDO';
      return false;
   } elseif(!isset($this->paData['CPARAM'])){
      $this->pcError = 'PARAMETRO DE BUSQUEDA NO DEFINIDO';
      return false;
   }
   return true;
}

public function omConsultarDatosAlumno() {
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
   $llOk = $this->mxConsultarDatosAlumno($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxConsultarDatosAlumno($p_oSql) {
   if (strlen($this->paData['CPARAM']) == 8){
      $lcSql = "SELECT DISTINCT A.cIdPago,B.cIdSegu,B.cNroDni, C.cNombre,B.nMonto FROM A05MSEG A
                INNER JOIN A05PSEG B ON B.cIdPago = A.cIdPago
                INNER JOIN V_A01MALU C ON C.cNroDni =B.cNroDni WHERE C.cNroDni ='($this->paData['CPARAM']'";
   } elseif (strlen($this->paData['CPARAM']) == 10){
      $lcSql = "SELECT DISTINCT A.cIdPago,B.cIdSegu,B.cNroDni, C.cNombre,B.nMonto FROM A05MSEG A
                INNER JOIN A05PSEG B ON B.cIdPago = A.cIdPago
                INNER JOIN V_A01MALU C ON C.cNroDni =B.cNroDni WHERE C.cCodAlu ='($this->paData['CPARAM']'";
   } else{
      $lcNombre = '%'.str_replace(' ','/',$this->paData['CPARAM']).'%';
      $lcSql = "SELECT DISTINCT A.cIdPago,B.cIdSegu,B.cNroDni, C.cNombre,B.nMonto FROM A05MSEG A
                INNER JOIN A05PSEG B ON B.cIdPago = A.cIdPago
                INNER JOIN V_A01MALU C ON C.cNroDni =B.cNroDni WHERE C.cNombre LIKE '{$lcNombre}'";
   }
   
   $RS = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($RS)) {
      $this->paDatos[] = ['CIDPAGO' => $laFila[0],'CIDSEGU' => $laFila[1], 'CNRODNI' => $laFila[2] ,
                          'CNOMBRE' => str_replace('/',' ',$laFila[3]), 'NTOTAL' => $laFila[4],'NSALDO' => 0.00];
   }
   // foreach($this->paDatos AS $laDatos){
      
   // }
   return true;
}

protected function mxValidarParamConstancia() {
   if (!isset($this->paData['CUSUCOD']) OR !preg_match('/^[0-9,A-Z]{1}[0-9]{3}$/', $this->paData['CUSUCOD'])){
      $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
      return false;
   } elseif (!isset($this->paData['CCENCOS']) OR !preg_match('/^[0-9,A-Z]{3}$/', $this->paData['CCENCOS'])){
      $this->pcError = 'CODIGO DE COSTOS NO DEFINIDO O INVALIDO';
      return false;
   } elseif(!isset($this->paData['CNOMBRE'])){
      $this->pcError = 'NOMBRE DE ESTUDIANTE NO DEFINIDO';
      return false;
   } elseif(!isset($this->paData['CNRODNI']) OR strlen($this->paData['CNRODNI']) != 8){
      $this->pcError = 'DNI DE ESTUDIANTE NO DEFINIDO O INVALIDO';
      return false;
   } 
   return true;
}
public function omGenerarConstancia() {
   $llOk = $this->mxValidarParamConstancia();
   if (!$llOk) {
          return false;
   } 
   $loSql = new CSql();
   $llOk = $loSql->omConnect();
   if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
   } 
   $llOk = $this->mxGenerarConstancia($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxGenerarConstancia($p_oSql) {
   $loDate = new DateTime();
   $loDate->setTimeZone(new DateTimeZone('America/Lima'));
   $lcUniAca = substr($this->paData['CCODALU'],-2);
   $lcCodAlu = substr($this->paData['CCODALU'],0,10);
   $lcSql = "SELECT cNomUni,cCatego FROM V_A01MALU  
             WHERE cCodAlu ='{$lcCodAlu}'";
   $RS = $p_oSql->omExec($lcSql);
   $laFila = $p_oSql->fetch($RS);
   if (!isset($laFila[0]) or empty($laFila[0])) {
      $this->pcError = "NO SE ENCONTRARON REGISTROS";
      return false;
   }
   $laData = ['CNRODNI' => $this->paData['CNRODNI'],'CCODALU' => $lcCodAlu,'CCATEGO' => $laFila[1],
              'CNOMBRE' => $this->paData['CNOMBRE'],'CYEAR' => '', 'DFECHA' => $loDate->format("Y-m-d"),'CUSUCOD' => $this->paData['CUSUCOD'],
              'NMONTO' => $this->paData['CNRODNI'],'NMATRIC' => 0.00, 'NPENSIO' => 0.00,'CUNIACA' => $lcUniAca,
              'CNOMUNI' => $laFila[0]];
   $lcSql = "SELECT nMatric,nCuota,cYear FROM S02TTAS 
             WHERE cUniAca ='{$laData['CUNIACA']}' 
             AND cCatego ='{$laData['CCATEGO']}' ORDER BY CYEAR DESC LIMIT 1";
   $RS = $p_oSql->omExec($lcSql);
   $laFila = $p_oSql->fetch($RS);
   if (!isset($laFila[0]) or empty($laFila[0])) {
      $this->pcError = "NO SE ENCONTRARON REGISTROS DE LAS TASAS EDUCATIVAS";
      return false;
   } 
   $laData['NMATRIC'] = $laFila[0];
   $laData['NPENSIO'] = $laFila[1]; 
   $laData['CYEAR'] = $laFila[2];
   $this->paData = $laData;
   $llOK = $this->omPrintReporteConstanciaPago();
   if(!$llOK){
      $this->pcError ='NO SE PUDO GENERAR LA CONSTANCIA';
      return false;
   }
   return true;
}



function omPrintReporteConstanciaPago() {
   //Generacion de QR
   $lcFile = 'FILES/R'.rand().'.png';
   $lcClave =md5($this->paData['CNRODNI'].$this->paData['CCODALU'].$this->paData['CCATEGO'].$this->paData['CNOMBRE'].
                 $this->paData['CYEAR'].$this->paData['NMATRIC'].
                 $this->paData['NPENSIO'].$this->paData['DFECHA'].$this->paData['CUSUCOD'].'UNIVERSIDAD CATOLICA DE SANTA MARIA');
   QRcode::png(utf8_encode($lcClave), $lcFile, QR_ECLEVEL_L, 4, 0, false);
   $lo = new CDate();
   $ldDate = $lo->dateSimpleText($this->paData['DFECHA']);
   $loPdf = new FPDF();
   $lnTpGety = $loPdf->GetY();
   $loPdf->AddPage('P', 'A4');
   if ($loPdf->PageNo() > 1){
      $loPdf->AddPage('P', 'A4');  
   }
   $loPdf->Ln(4);
   $loPdf->SetFont('Courier', 'B', 10);
   $loPdf->Cell(50, 1,'                                          CONSTANCIA');
   $loPdf->Ln(3);
   $loPdf->SetFont('Courier', 'B', 10);
   $loPdf->Ln(3);
   $loPdf->Image('img/logo_trazos.png',10,10,35);
   $loPdf->Ln(5);
   $loPdf->SetFont('Courier', '', 10);
   $loPdf->MultiCell(180, 5, utf8_decode(fxStringFixed('La Dirección de Contabilidad de la Universidad Católica de Santa Maria, hace CONSTAR que el(la) Sr.(Srta):',180)), 0,'J');
   $loPdf->Ln(3);
   $loPdf->SetFont('Courier', 'B', 10);
   $loPdf->Cell(50, 1,utf8_decode('NOMBRE   : '.$this->paData['CNOMBRE']));
   $loPdf->Ln(4);
   $loPdf->Cell(50, 1,utf8_decode('DNI      : '.$this->paData['CCODALU']));
   $loPdf->Ln(4);
   $loPdf->Cell(50, 1,utf8_decode('CÓDIGO   : '.$this->paData['CCODALU']));
   $loPdf->Ln(4);
   $loPdf->Cell(50, 1,utf8_decode('ESCUELA  : '.$this->paData['CNOMUNI'] ), 0,'J');
   $loPdf->Ln(4);
   $loPdf->SetFont('Courier', 'B', 10);
   $loPdf->Cell(50, 1, utf8_decode(fxStringFixed('CATEGORÍA: '.$this->paData['CCATEGO'],14)), 0,'J');
   $loPdf->Ln(4);
   $loPdf->Ln(4);
   $loPdf->SetFont('Courier', '', 10);
   $loPdf->Cell(50, 1, utf8_decode('Por concepto de Tasas Educativas, le corresponde: '), 0,'J');
   $loPdf->Ln(8);
   $loPdf->SetFont('Courier', 'B', 10);
   $loPdf->Cell(50, 1,utf8_decode('MATRÍCULA: S/***'.$this->paData['NMATRIC']));
   $loPdf->Ln(4);
   $loPdf->Cell(50, 1,utf8_decode('PENSIÓN  : S/***'.$this->paData['NPENSIO']));
   $loPdf->Ln(8);
   $loPdf->SetFont('Courier', '', 10);
   $loPdf->MultiCell(180, 3,utf8_decode(fxStringFixed('Tomando en cuenta las pensiones vigentes al año '.$this->paData['CYEAR'].'.',55)), 0,'J');
   $loPdf->Ln(5);
   $loPdf->MultiCell(180, 5, utf8_decode(fxStringFixed('Se expide la presente constancia para trámite de Seguro Estudiantil a solicitud de la Dirección de Bienestar Universitario - Servicio Social.',150)), 0,'J');
   $loPdf->Ln(10);
   $loPdf->MultiCell(180, 5, utf8_decode('Arequipa, '.$ldDate), 0,'C');
   $loPdf->Ln(2);
   $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 50, 110, 20, 20, 'PNG');
        
   $loPdf->Output('F', $this->pcFile, true);
   $this->paData['CREPORT'] = $this->pcFile;
   return true;
}

#--------------------------------------------------------------------
# BANDEJA DE DEPOSITOS PARA GENERACION DE CONSTANCIAS  ---SEG1090
# Creacion ASR 2022-06-14
#--------------------------------------------------------------------
public function omInitBandejaConstancias() {
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
   $llOk = $this->mxInitBandejaConstancias($loSql);
   $loSql->omDisconnect();
   return $llOk;
}

protected function mxInitBandejaConstancias($p_oSql) {
   $lcSql = "SELECT C.cIdSegu, C.cCodAlu,D.cNombre, C.nMonto, C.dFecha,C.nSerial FROM A05MSEG A
               INNER JOIN A05PSEG B ON B.cIdPago = A.cIdPago
               INNER JOIN A05DSEG C ON C.cIdSegu = B.cIdSegu 
               INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu 
               WHERE A.cEstado!='X' AND C.cEstado='A' ORDER BY A.tModifi DESC";
   $RS = $p_oSql->omExec($lcSql);
   while ($laFila = $p_oSql->fetch($RS)) {
       $this->paDatos[] = ['CIDSEGU' => $laFila[0],'CCODALU' => $laFila[1], 'CNOMBRE' => str_replace('/',' ',$laFila[2]) ,
                           'NMONTO' => $laFila[3], 'DFECHA' => $laFila[4],'NSERIAL' => $laFila[5]];
   }
   return true;
}

#-----------------------------------------------------------
# BUSQUEDA DE ALUMNOS (RETORNA UNIDADES ACADEMICAS Y NOMBRE)
# Creacion ASR 2022-08-03
#-----------------------------------------------------------
   public function omBuscarAlumnoUniAca() {
      $llOk = $this->mxValidarParamBusqueda();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      } 
      $llOk = $this->mxBuscarAlumnoUniAca($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarAlumnoUniAca($p_oSql) {
      $lcSql = "SELECT cNombre,cCatego from V_A01MALU WHERE cNroDni='{$this->paData['CNRODNI']}' LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0])){
         $this->pcError = 'NO SE ENCONTRARON DATOS DEL ALUMNO';
         return false;
      }
      $this->paDatos = ['CNOMBRE' => str_replace('/',' ',$laFila[0]),'CCATEGO' => $laFila[1]];
      $lcSql = "SELECT cCodAlu,cNomUni,cUniAca FROM V_A01MALU WHERE CNRODNI ='{$this->paData['CNRODNI']}' AND cUniAca not in('10')";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laDatos[] = ['CCODALU' =>  $laFila[0] ,'CNOMUNI' => $laFila[1],'CUNIACA' => $laFila[2]];
      }
      if (count($laDatos) == 0){
         $this->pcError = 'NO SE ENCONTRARON UNIDADES ACADEMICAS';
         return false;
      }
      $lnLenght = count($laDatos);
      $this->paDatos = ['NNROELE' => $lnLenght]+$this->paDatos +$laDatos;
      return true;
   }



}
?>
