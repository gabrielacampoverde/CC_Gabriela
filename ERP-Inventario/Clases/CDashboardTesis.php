<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Libs/fpdf/fpdf.php";
require_once "class/PHPExcel.php";
date_default_timezone_set('America/Bogota');

// -----------------------------------------------------------
// Clase Dashboard para tesis y convalidaciones
// 2023-04-17 GCH Creacion
// -----------------------------------------------------------
class CDashboardTesis extends CBase {

   public $paData, $paDatos, $paData1, $laDatos, $laData, $paDato;
   // protected $laData;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->laData = $this->laDatos = $this->paDato =  null;
      // $this->pcFile = 'FILES/R' . rand() . '.pdf';
   }

   //------------------------------------------------------------------------------------
   // Init Proyecto de Inventigacion
   // 2022-02-24 GCH
   //------------------------------------------------------------------------------------
   public function omInitUnidAca() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxConsultaUnidAcademica();
      return true;
   }
   
   protected function mxConsultaUnidAcademica() {
      $laData = ['ID' => 'ERP0020'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
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
      return true;
   }

   //------------------------------------------------------------------------------------
   // Init Reporte ESTADISTICAS COBRANZA 2023I
   // 2023-06-08
   //------------------------------------------------------------------------------------
   public function omInitCobranza2023I() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranza2023I();
      return true;
   }
   
   protected function mxCobranza2023I() {
      $laData = ['ID' => 'EST0002'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
      // print_r($lcCommand);
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

   //------------------------------------------------------------------------------------
   // Init Reporte ESTADISTICAS COBRANZA 2022I
   // 2023-06-09
   //------------------------------------------------------------------------------------
   public function omInitCobranza2022I() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranza2022I();
      return true;
   }
   
   protected function mxCobranza2022I() {
      $laData = ['ID' => 'EST0003', 'CIDDATO'=> '002'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

      //------------------------------------------------------------------------------------
   // Init Reporte ESTADISTICAS COBRANZA 2022II
   // 2023-06-09
   //------------------------------------------------------------------------------------
   public function omInitCobranza2022II() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranza2022II();
      return true;
   }
   
   protected function mxCobranza2022II() {
      $laData = ['ID' => 'EST0003', 'CIDDATO'=> '003'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

   //------------------------------------------------------------------------------------
   // Init Reporte ESTADISTICAS COBRANZA 2022I Y II
   // 2023-06-09
   //------------------------------------------------------------------------------------
   public function omInitCobranza2022Total() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranza2022Total();
      return true;
   }
   
   protected function mxCobranza2022Total() {
      $laData = ['ID' => 'EST0004'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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
   // --------------------------------------------------------
   // Consultar ProyectoTesis Tpt 5010
   // 202304-14 GCH Creacion
   // --------------------------------------------------------
   public function omConsultaProyectoTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCabeceraDetalleTesis($loSql);
      $loSql->omDisconnect();
      $llOk = $this->mxConsultaProyectoTesis();
   
      return true;
   }

   protected function mxCabeceraDetalleTesis($p_oSql){
      if($this->paData['CUNIACA'] === '*'){
         $this->laData = ['CUNIACA' => '*', 'CDESCRI' =>'*** TODOS ***']; 
      }else{
         $lcSql = "SELECT cUniAca, cNomUni FROM S01TUAC WHERE cNivel = '01' AND cEstado = 'A' and cuniaca = '{$this->paData['CUNIACA']}' ORDER BY cNomUni";
         $R1 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R1)){
            $this->laData = ['CUNIACA' => $laFila[0], 'CDESCRI' =>$laFila[1]]; 
         }
         if (count($this->paDatos) == 0) {
            $this->pcError = 'NO SE ENCONTRARON DATOS';
            return false;
         }
      }
      return true;
   }
   
   protected function mxConsultaProyectoTesis() {
      //print_r($this->paData);
      $laData = ['ID' => 'ERP0021', 'CUNIACA' => $this->paData['CUNIACA']];
      $sJson = json_encode($laData);
      $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      return true;
   }

   // --------------------------------------------------------
   // Consultar Borrador Tesis Tpt 5020
   // 202304-14 GCH Creacion
   // --------------------------------------------------------
   public function omConsultarBorradorTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCabeceraDetalleTesis($loSql);
      $loSql->omDisconnect();
      $llOk = $this->mxConsultaBorradorTesis();
   
      return true;
   }
   
   protected function mxConsultaBorradorTesis() {
      //print_r($this->paData);
      $laData = ['ID' => 'ERP0022', 'CUNIACA' => $this->paData['CUNIACA']];
      $sJson = json_encode($laData);
      $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      return true;
   }

   // --------------------------------------------------------
   // Consultar Asesor Tesis Tpt 5030
   // 202304-14 GCH Creacion
   // --------------------------------------------------------
   public function omConsultarAsesorTesis() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCabeceraDetalleTesis($loSql);
      $loSql->omDisconnect();
      $llOk = $this->mxConsultarAsesorTesis();
   
      return true;
   }
   protected function mxConsultarAsesorTesis() {
      //print_r($this->paData);
      $laData = ['ID' => 'ERP0023', 'CUNIACA' => $this->paData['CUNIACA']];
      $sJson = json_encode($laData);
      $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      return true;
   }

   // --------------------------------------------------------
   // Consultar Asesor Tesis Tpt 5030
   // 202304-14 GCH Creacion
   // --------------------------------------------------------
   public function omConsultarJurados() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCabeceraDetalleTesis($loSql);
      $loSql->omDisconnect();
      $llOk = $this->mxConsultarJurados();
   
      return true;
   }
   protected function mxConsultarJurados() {
      //print_r($this->paData);
      $laData = ['ID' => 'ERP0024', 'CUNIACA' => $this->paData['CUNIACA']];
      $sJson = json_encode($laData);
      $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      return true;
   }

   // --------------------------------------------------------
   // Consultar Asesor Tesis Tpt 5030
   // 202304-14 GCH Creacion
   // --------------------------------------------------------
   public function omConvalidaciones() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCabeceraDetalleTesis($loSql);
      $loSql->omDisconnect();
      $llOk = $this->mxConvalidaciones();
   
      return true;
   }
   protected function mxConvalidaciones() {
      //print_r($this->paData);
      $laData = ['ID' => 'ERP0025', 'CUNIACA' => $this->paData['CUNIACA']];
      $sJson = json_encode($laData);
      $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      return true;
   }

   //------------------------------------------------------------------------------------
   // Init Reporte EVALUACIONES SUFIENCIENCIA POR JURADO - 2023
   // 2023-06-14
   //------------------------------------------------------------------------------------
   public function omInitSufJurado2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitSufJurado2023();
      return true;
   }
   
   protected function mxInitSufJurado2023() {
      $laData = ['ID' => 'EST0005'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
      // print_r($lcCommand);
      // die;
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

   //------------------------------------------------------------------------------------
   // Init Reporte BACHILLERATO ONLINE 2023
   // 2023-06-15
   //------------------------------------------------------------------------------------
   public function omInitBachilleratoOnline2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitBachilleratoOnline2023();
      return true;
   }
   
   protected function mxInitBachilleratoOnline2023() {
      $laData = ['ID' => 'EST0006'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

   //------------------------------------------------------------------------------------
   // Init Reporte TITULACION ONLINE 2023
   // 2023-06-15
   //------------------------------------------------------------------------------------
   public function omInitTitulacionOnline2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitTitulacionOnline2023();
      return true;
   }
   
   protected function mxInitTitulacionOnline2023() {
      $laData = ['ID' => 'EST0007'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

   //------------------------------------------------------------------------------------
   // Init Reporte 2DA ESPECIALIDAD ONLINE 2023
   // 2023-06-15
   //------------------------------------------------------------------------------------
   public function omInitEspecialidad2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitEspecialidad2023();
      return true;
   }
   
   protected function mxInitEspecialidad2023() {
      $laData = ['ID' => 'EST0008'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

   //------------------------------------------------------------------------------------
   // Init Reporte MAESTRIA ONLINE 2023
   // 2023-06-15
   //------------------------------------------------------------------------------------
   public function omInitMaestria2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitMaestria2023();
      return true;
   }
   
   protected function mxInitMaestria2023() {
      $laData = ['ID' => 'EST0009'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

   //------------------------------------------------------------------------------------
   // Init Reporte DOCTORADO ONLINE 2023
   // 2023-06-15
   //------------------------------------------------------------------------------------
   public function omInitDoctorado2023() {
      // $llOk = $this->mxValConsultarDocente();
      // if (!$llOk) {
		// 	return false;
		// }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitDoctorado2023();
      return true;
   }

   // protected function mxValConsultarDocente() {
   //    if (!isset($this->paData['CCODUSU']) || !preg_match('(^[A-Z0-9]{4}$)', $this->paData['CCODUSU'])) {
   //       $this->pcError = 'CODIGO DE USUARIO INVÁLIDO O NO DEFINIDO';
   //       return false;
   //    } elseif (!isset($this->paData['CNOMBRE'])) {
   //       $this->pcError = 'APELLIDOS Y NOMBRES INVÁLIDOS O NO DEFINIDOS';
   //       return false;
   //    }
   //    return true;
   // }
   
   protected function mxInitDoctorado2023() {
      $laData = ['ID' => 'EST0010'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
      // print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      $laArray = json_decode($lcData,true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      // print_r($lcData);
      // print_r($this->paDatos);
      // die;
      return true;
      
   }   

   //------------------------------------------------------------------------------------
   // Init Reporte ESTADISTICAS MORA 2023
   // 2023-06-21
   //------------------------------------------------------------------------------------
   public function omInitCobranzaMora2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranzaMora2023();
      return true;
   }
   
   protected function mxCobranzaMora2023() {
      $laData = ['ID' => 'EST0011'];
      $sJson = json_encode($laData);
      //$lcCommand = "python ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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

   //------------------------------------------------------------------------------------
   // PROYECTADO 2023I
   // 2023-06-21
   //------------------------------------------------------------------------------------
   public function omInitCobranzaPredic2023() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxInitCobranzaPredic2023();
      return true;
   }
   
   protected function mxInitCobranzaPredic2023() {
      $laData = ['ID' => 'EST0012'];
      $sJson = json_encode($laData);
      //$lcCommand = "python ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
      //print_r($lcCommand);
      $lcData = shell_exec($lcCommand);
      //print_r($lcData);
      $laArray = json_decode($lcData, true); 
      if (isset($laArray['ERROR'])) {
         fxAlert($laArray['ERROR']);
         return;
      }
      $this->paDatos = $laArray;
      // print_r($this->paDatos);
      //die;
      return true;
      
   }

   //------------------------------------------------------------------------------------
   // Init Reporte ESTADISTICAS COBRANZA 2023II
   // 2023-06-08
   //------------------------------------------------------------------------------------
   public function omInitCobranza2023II() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranza2023II();
      return true;
   }
   
   protected function mxCobranza2023II() {
      $laData = ['ID' => 'EST0013'];
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
      // print_r($lcCommand);
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

   //------------------------------------------------------------------------------------
   // Init Reporte CARRERAS A DISTANCIA
   // 2023-09-04
   //------------------------------------------------------------------------------------
   public function omInitCobranzas2023IDistancia() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranzas2023IDistancia();
      return true;
   }
   
   protected function mxCobranzas2023IDistancia() {
      $laData = ['ID' => 'EST0014'];   
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
      // print_r($lcCommand);
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

   //------------------------------------------------------------------------------------
   // Init Reporte CARRERAS A DISTANCIA
   // 2023-09-04
   //------------------------------------------------------------------------------------
   public function omInitCobranzas2023IIDistancia() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(14);
      if (!$llOk) {
        $this->pcError = $loSql->pcError;
        return false;
      }
      $llOk = $this->mxCobranzas2023IIDistancia();
      return true;
   }
   
   protected function mxCobranzas2023IIDistancia() {
      $laData = ['ID' => 'EST0015'];   
      $sJson = json_encode($laData);
      //$lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
      $lcCommand = "python3 ./xpython/CEstadisticasCobranza.py '".$sJson."' 2>&1";
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
}
?>
