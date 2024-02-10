<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CConsultarActFij extends CBase {
   public $paDatos, $paData;

   public function omConsultarActFij() {
      $llOk = $this->mxValParam();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxConsultarActFij($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxValParam() {
      if (!isset($this->paData["CACTFIJ"])) {
         $this->paData = ["error" => "Código de activo no valido."];
         return false;
      }
      return true;
   }

   protected function mxConsultarActFij($p_oSql) {
      $this->paData['CACTFIJ'] = str_replace('-','',$this->paData['CACTFIJ']);
      if(strlen($this->paData['CACTFIJ']) === 5){
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cactfij = '{$this->paData['CACTFIJ']}'";
      }else{
         $lcTipAfj = substr($this->paData['CACTFIJ'], 0, 5);
         $lnCorrel = substr($this->paData['CACTFIJ'], 5);
         $lcSql = "SELECT cActFij FROM E04MAFJ WHERE cTipAfj = '$lcTipAfj' AND nCorrel = $lnCorrel";
      }
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp or count($laTmp) == 0) {
         $this->pcError = 'ACTIVO FIJO NO EXISTE';
         return false;
      }  
      $lcActFij = $laTmp[0];
      $lcSql = "SELECT cActFij, CtipAfj, nCorrel, cSituac, cDesSit, cDescri, cCodEmp, REPLACE(cNomEmp,'/',' '), cCenRes, cDesRes, cCenCos, cDesCen, dFecAlt, nMontmn, cNroSer, cModelo, cColor, cMarca, mFotogr FROM F_E04MAFJ_2('$lcActFij')";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->paDatos = ["ERROR" => "Error al consultar Activo fijo"];
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcCodigo = substr($laFila[1], 0, 2).'-'.substr($laFila[1], 2, 5).'-'.right('00000'.strval($laFila[2]), 6);
         $this->paDatos = ["CACTFIJ" => $laFila[0], "CTIPAFJ" => $laFila[1], "NCORREL" => $laFila[2], "CSITUAC" => $laFila[3], "CDESSIT" => $laFila[4], "CDESCRI" => $laFila[5],
                          "CCODEMP" => $laFila[6], "CNOMBRE" => $laFila[7], "CCENRES" => $laFila[8], "CDESRES" => $laFila[9], "CCENCOS" => $laFila[10], "CDESCEN" => $laFila[11],
                          "DFECALT" => $laFila[12], "NMONTO" => $laFila[13], "CNROSER" => $laFila[14], "CMODELO" => $laFila[15], "CCOLOR" => $laFila[16], "CMARCA" => $laFila[17],
                          "MFOTOGR" => $laFila[18], "CCODIGO" => $lcCodigo];
      }
      return true;
   }

   public function omConsultarCenCos(){
      $llOk = $this->mxValParamCenCos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk){
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxConsultarCenCos($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         return false;
      }
      return $llOk;
   }
   protected function mxValParamCenCos(){
      if(!isset($this->paData["CDESCOS"])){
         $this->paDatos = ["ERROR" => "Descripción de Centro de Costos no válido"];
      }
      return true;
   } 

   protected function  mxConsultarCenCos($p_oSql){
      $lcDesCos = str_replace(' ', '%', trim(strtoupper($this->paData['CDESCOS']))).'%';
      $lcSql = "SELECT cCenCos, cDescri, cEstado FROM S01TCCO WHERE cEstado = 'A' AND cDescri like '%{$lcDesCos}'";
      $R1 = $p_oSql->omExec($lcSql);
      if(!$R1){
         $this->paDatos = ["ERROR" => "Error al consultar Centros de Costo"];
         return false;
      }
      while($laFila = $p_oSql->fetch($R1)){
         $this->paDatos[] = ["CCENCOS" => $laFila[0], "CDESCRI" => $laFila[1], "CESTADO" => $laFila[2]];
      }
      return true; 
   }

   public function omConsultarCenRes(){
      $llOk = $this->mxValParamCenRes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk){
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxConsultarCenRes($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         return false;
      }
      return $llOk;
   }
   protected function mxValParamCenRes(){
      if(!isset($this->paData["CCENCOS"])){
         $this->paDatos = ["ERROR" => "Centro de costo no válido"];
      }
      return true;
   } 

   protected function  mxConsultarCenRes($p_oSql){
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
         $this->paDatos[] = ["CCENRES" => $laFila[0], "CDESCRI" => $laFila[1], "CESTADO" => $laFila[2], "NTOTAL" => $laFila[3], "NFALINV" => $laTmp[0]];
      }
      if(!$this->paDatos){
         $this->paDatos = ["ERROR" => "Error al consultar Centros de Costo"];
         return false;
      }
      return true; 
   }

   public function omConsultarActFijCenRes(){
      $llOk = $this->mxValParamomConsultarActFijCenRes();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk){
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxConsultarActFijCenRes($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         return false;
      }
      return $llOk;
   }
   protected function mxValParamomConsultarActFijCenRes(){
      if(!isset($this->paData["CCENRES"])){
         $this->paDatos = ["ERROR" => "Centro de Responsabilidad no válido"];
      }
      return true;
   } 

   protected function  mxConsultarActFijCenRes($p_oSql){
      $lcSql = "SELECT A.cactfij, A.ctipafj, A.ncorrel, A.csituac, C.cDescri,  A.cdescri, A.ccodemp, B.cNombre , A.mdatos , A.cInvent, A.mFotogr 
                  FROM e04mafj A
                  INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodEmp
                  INNER JOIN V_S01TTAB C ON C.cCodigo = A.cSituac
                  WHERE C.cCodTab = '334' and A.cCenRes = '{$this->paData['CCENRES']}' AND A.csituac = 'O'
                  ORDER BY  A.ctipafj, A.ncorrel";
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
                             "MFOTOGR" => $laFila[10], "MDATOS" => json_decode($laFila[8], true)];
      }
      return true; 
   }

   public function omConsultarInventario(){
      $llOk = $this->mxValParamomConsultarInventario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk){
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxConsultarInventario($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         return false;
      }
      return $llOk;
   }
   protected function mxValParamomConsultarInventario(){
      if(!isset($this->paData["CCENRES"])){
         $this->paDatos = ["ERROR" => "Centro de Responsabilidad no válido"];
      }
      return true;
   } 

   protected function  mxConsultarInventario($p_oSql){
      //total de activos fijos que faltan inventariar
      $lcSql = "SELECT COUNT(cinvent) FROM e04mafj WHERE ccenres = '{$this->paData['CCENRES']}' AND cSituac != 'B' AND cinvent = 'S'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp) {
         $this->paDatos = ["ERROR" => "Error al consultar total de activos inventariados"];
         return false;
      }
      //total de activos fijos en el centro de responsabilidad
      $lcSql = "SELECT COUNT(cinvent) FROM e04mafj WHERE ccenres = '{$this->paData['CCENRES']}' AND cSituac != 'B'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp1 = $p_oSql->fetch($R1);
      if (!$laTmp1) {
         $this->paDatos = ["ERROR" => "Error al consultar total de activos"];
         return false;
      }

      $this->paDatos[] = ["NFALINV" => $laTmp[0], "NTOTAL" => $laTmp1[0]];
      return true; 
   }

   public function omGuardarInventario(){
      $llOk = $this->mxValParamGuardarInventario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk){
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxGuardarInventario($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      return $llOk;
   }

   protected function mxValParamGuardarInventario(){
      if(!isset($this->paData["CCENRES"])){
         $this->paDatos = ["ERROR" => "Centro de Responsabilidad no válido"];
      }
      return true;
   } 

   protected function  mxGuardarInventario($p_oSql){
      //print_r($this->paData['DATOS']);
      foreach ($this->paData['DATOS'] as $laFila) {
         //$lcTipAfj = substr($laFila['CACTFIJ'], 0, 5);
         //$lnCorrel = substr($laFila['CACTFIJ'], 5);
         // $lcSql = "UPDATE E04MAFJ SET cInvent = '{$laFila['CINVENT']}' WHERE ctipafj = '$lcTipAfj' and ncorrel = '$lnCorrel'";
         $lcSql = "UPDATE E04MAFJ SET cInvent = 'S' WHERE cActFij = '{$laFila['CACTFIJ']}' and ccenres = '{$this->paData['CCENRES']}'";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         $this->paDatos = ["Código de activo fijo inventariado correctamente"];
         if (!$llOk) {
            $this->pcError = 'No se pudo inventariar activo fijo';
            return false;
         }
      }
      return true; 
   }
   

   public function omGuardarActFijInventariado(){
      $llOk = $this->mxValParamGuardarActFijInventariado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxGuardarActFijInventariado($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      return $llOk;
   }

   protected function mxValParamGuardarActFijInventariado(){
      if(!isset($this->paData["CACTFIJ"])){
         $this->paDatos = ["ERROR" => "Código de activo fijo no valido no válido"];
      }
      return true;
   } 

   protected function mxGuardarActFijInventariado($p_oSql){
      $lcSql = "SELECT cInvent FROM E04MAFJ WHERE cActFij = '{$this->paData['CACTFIJ']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp) {
         $this->pcError = 'Código de activo fijo no existe';
         return false;
      }
      if($laTmp[0] === 'S'){
         $this->paDatos = ["Código de activo fijo ya fue inventariado anteriormente"];
      }else{
         $lcSql = "UPDATE E04MAFJ SET cInvent = 'S' WHERE cActFij = '{$this->paData['CACTFIJ']}'";
         //print_r($lcSql);
         $llOk = $p_oSql->omExec($lcSql);
         $this->paDatos = ["Código de activo fijo inventariado correctamente"];
         if (!$llOk) {
            $this->pcError = 'No se pudo inventariar activo fijo';
            return false;
         }
      }      
      return true;  
   }

   public function omConsultarEmpleado(){
      $llOk = $this->mxValParamConsultarEmpleado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk){
         $this->paDatos = ["ERROR" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxConsultarEmpleado($loSql);
      $loSql->omDisconnect();
      if(!$llOk){
         $loSql->rollback();
         $loSql->omDisconnect();
         return false;
      }
      return $llOk;
   }

   protected function mxValParamConsultarEmpleado(){
      if(!isset($this->paData["CCODEMP"])){
         $this->paDatos = ["ERROR" => "Código de empleado no valido"];
      }
      return true;
   } 

   protected function mxConsultarEmpleado($p_oSql){
      if (preg_match('/^[0-9A-Z]{4,5}$/', $this->paData['CCODEMP'])) {
         $lcSql = "SELECT cCodUsu, cNombre, cNroDni, cEmail FROM V_S01TUSU_1 WHERE cCodUsu = '{$this->paData['CCODEMP']}' AND cEstado = 'A'";
      } elseif (preg_match('/^[0-9]{8}$/', $this->paData['CCODEMP'])) {
         $lcSql = "SELECT cCodUsu, cNombre, cNroDni, cEmail FROM V_S01TUSU_1 WHERE cNroDni = '{$this->paData['CCODEMP']}' AND cEstado = 'A'";
      } else {
         $lcCodEmp = str_replace(' ', '%', trim(strtoupper($this->paData['CCODEMP']))).'%';
         $lcSql = "SELECT cCodUsu, cNombre, cNroDni, cEmail FROM V_S01TUSU_1 WHERE cNombre like '%{$lcCodEmp}' AND cEstado = 'A' ORDER BY cNombre";
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


   // ------------------------------------------------------------------------
   // Realizar transferencia de varios centros de resp a uno
   // ------------------------------------------------------------------------
   public function omTransferenciaActFij() {      
      $llOk = $this->mxValParamTransferenciaActFij();
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

      protected function mxValParamTransferenciaActFij() {
      $loDate = new CDate();
      if(!isset($this->paData['CDESCRI'])){
         $this->pcError = "DESCRIPCIÓN DE TRANSFERENCIA NO DEFINIDO O INVÁLIDO";
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

   protected function mxTransferencias($p_oSql) {
      $lcSql = "SELECT cActFij, cCenRes, cCodEmp FROM E04MAFJ WHERE cActFij = '{$this->paData['CACTFIJ']}' "; 
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1);
      if (!$laFila) {
         $this->pcError = 'Código de activo fijo no existe';
         return false;
      }
      // CAMBIAR ACTIVO FIJO
      $lcSql = "UPDATE E04MAFJ SET cCenRes = '{$this->paData['CRESDES']}', cCodEmp = '{$this->paData['CCODDES']}' WHERE cActFij = '{$this->paData['CACTFIJ']}'";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ACTUALIZAR ACTIVO FIJO";
         return false;
      }
      //Insertar en tabla de transferencia
      $lcSql = "SELECT MAX(cIdTrnf) FROM E04MTRF";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!(!$laTmp or count($laTmp) == 0 or $laTmp[0] == null)) {
         $this->paData['CIDTRNF'] = $laTmp[0];
      }
      $this->paData['CIDTRNF'] = fxCorrelativo($this->paData['CIDTRNF']);
      $this->paData['CDESCRI'] = strtoupper(trim($this->paData['CDESCRI']));
      $lcSql = "INSERT INTO E04MTRF (cIdTrnf, cEstado, dTrasla, cDescri, cCenRes, cCodEmp, tRegist, cResCen, cCodRec, tRecepc,  cUsuAdm, tAprAdm, cUsuCod) VALUES
              ('{$this->paData['CIDTRNF']}', 'A', NOW(), '{$this->paData['CDESCRI']}', '$laFila[1]', '$laFila[2]', NOW(),
               '{$this->paData['CRESDES']}', '{$this->paData['CCODDES']}', NOW(), '{$this->paData['CUSUCOD']}', NOW(), '{$this->paData['CUSUCOD']}')";
      //print_r($lcSql);
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR CABECERA DE TRANSFERENCIA";
         return false;
      }      
      $lcSql = "INSERT INTO E04DTRF (cIdTrnf, cActFij, cUsuCod) 
                  VALUES ('{$this->paData['CIDTRNF']}', '{$this->paData['CACTFIJ']}', '{$this->paData['CUSUCOD']}');";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO INSERTAR DETALLE TRANSFERENCIA";
         return false;
      }
      return true;
   }
}

?>
