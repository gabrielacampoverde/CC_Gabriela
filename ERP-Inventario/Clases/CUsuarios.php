<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CUsuarios extends CBase {
   public $paData, $paDatos, $paCargos , $paIdCate , $paCodUsu, $paSexo;
   protected $lcCodCntto;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paCargos = $this->paCodUsu = $this->paSexo = null;
   }

   //-------------------------------------------------------------------------------
   // DATOS DE ALUMNOS - Tdo1010.php
   // 2020-07-29 APR Creacion
   //------------------------------------------------------------------------------- 
   public function omInitUsuario() {
      $llOk = $this->mxValInitUsuario();
      if (!$llOk) {
			return false;
		}
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(2); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxInitUsuario($loSql); 
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValInitUsuario() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO O NO DEFINIDO';
         return false;
      }  elseif (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8 || !preg_match('(^[E0-9]{1}[0-9]{7}$)', $this->paData['CNRODNI'])) {
            $this->pcError = 'EL NUMERO DE DNI DEBE DE SER DE 8 DIGITOS';
            return false;
      }   
      return true;
   }
 
   protected function mxInitUsuario($p_oSql) { 
      //CONEXION UCSMERP
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $lcSql = "SELECT cEmail FROM S01MPER WHERE cNroDni = '{$this->paData['CNRODNI']}'";
      $R1 = $loSql->omExec($lcSql);
      $lcEmail = $loSql->fetch($R1);
      $loSql->omDisconnect();
      //TRAER DATOS PERSONALES DEL INS
      $lcSql = "SELECT cNroDni, cNombre, cEstado, cEmail, cNroCel FROM S01MPER WHERE cNroDni = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql); 
      $laFila = $p_oSql->fetch($R1); 
      if (empty($laFila[0])) { 
         $this->pcError = "NUMERO DE DNI NO EXISTE"; 
         return false; 
      } 
      $this->paData = ['CNRODNI' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CESTADO' => $laFila[2], 
                       'CEMAILP' => $laFila[3], 'CNROCEL' => $laFila[4], 'CEMAIL' => $lcEmail[0]]; 
      return true; 
   } 
 
   //-------------------------------------------------------------------------------
   // ACTUALIZAR DATOS DE ALUMNOS - Tdo1010.php
   // 2020-07-29 APR Creacion
   //------------------------------------------------------------------------------- 
   public function omActualizarDatosUsuario() { 
      $llOk = $this->mxValActualizarDatosUsuario(); 
      if (!$llOk) {
         return false;
      }
      //BASE DE DATOS UCSMERP
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxActualizarDatosUsuarioUCSMERP($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      } 
      $loSql->omDisconnect(); 
      //BASE DE DATOS UCSMERP
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(2); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxActualizarDatosUsuarioUSCMMTA($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValActualizarDatosUsuario() { 
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO";
         return false;
      }  elseif (!isset($this->paData['CEMAIL']) || empty($this->paData['CEMAIL']) || !filter_var($this->paData['CEMAIL'], FILTER_VALIDATE_EMAIL)) {
            $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
            return false;
      }  elseif (strlen($this->paData['CNRODNI']) != 8){
            $this->pcError = 'EL NUMERO DE DNI DEBE DE SER DE 8 DIGITOS';
            return false;
      }  elseif (!isset($this->paData['CNROCEL']) || empty($this->paData['CNROCEL']) || !ctype_digit($this->paData['CNROCEL']) || strlen($this->paData['CNROCEL']) > 12) {
            $this->pcError = 'NUMERO DE CELULAR NO DEFINIDO O INVALIDO';
            return false;
      }  
      return true; 
   } 
 
   protected function mxActualizarDatosUsuarioUCSMERP($p_oSql) { 
      //ACTUALIZACION DE DATOS DE SOLICITANTE
      $lcSql = "UPDATE S01MPER SET cNroCel = '{$this->paData['CNROCEL']}', cEmailP = '{$this->paData['CEMAIL']}', cUsuCod = '{$this->paData['CUSUCOD']}', 
                  tModiFi = NOW() WHERE cNroDni = '{$this->paData['CNRODNI']}'"; 
      $llOk= $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR AL ACTUALIZAR DATOS EN MAESTRO DE PERSONAS - UCSMERP';
         return false;
      } 
      return true; 
   }

   protected function mxActualizarDatosUsuarioUSCMMTA($p_oSql) { 
      //ACTUALIZACION DE DATOS DE SOLICITANTE
      $lcSql = "UPDATE S01MPER SET cNroCel = '{$this->paData['CNROCEL']}', cEmail = '{$this->paData['CEMAIL']}', cCodUsu = '{$this->paData['CUSUCOD']}', 
                  tModiFi = NOW() WHERE cNroDni = '{$this->paData['CNRODNI']}'"; 
      $llOk= $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR AL ACTUALIZAR DATOS EN MAESTRO DE PERSONAS - UCSMINS';
         return false;
      } 
      return true; 
   }

   //-------------------------------------------------------------------------------
   // DATOS DE INVITADOS - Tdo1020.php
   // 2020-10-02 APR Creacion
   //------------------------------------------------------------------------------- 
   public function omInitDatosUsuarioInvitado() {
      $llOk = $this->mxValInitDatosUsuarioInvitado();
      if (!$llOk) {
			return false;
		}
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(2); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxInitDatosUsuarioInvitado($loSql); 
      if (!$llOk) {
         $loSql->rollback();
         return false;
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   }

   protected function mxValInitDatosUsuarioInvitado() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVÁLIDO O NO DEFINIDO';
         return false;
      }  elseif (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8 || !preg_match('(^[E0-9]{1}[0-9]{7}$)', $this->paData['CNRODNI'])) {
            $this->pcError = 'EL NUMERO DE DNI DEBE DE SER DE 8 DIGITOS';
            return false;
      }  
      return true;
   }
 
   protected function mxInitDatosUsuarioInvitado($p_oSql) { 
      $lcSql = "SELECT cNroDni, SPLIT_PART(cNombre, '/', 1), SPLIT_PART(cNombre, '/', 2), SPLIT_PART(cNombre, '/', 3), 
                  cEstado, cEmail, cNroCel FROM S01MPER WHERE cNroDni = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql); 
      $i = 0; 
      $laFila = $p_oSql->fetch($R1); 
      if (empty($laFila[0])) { 
         $this->pcError = "NUMERO DE DNI NO EXISTE"; 
         return false; 
      } 
      $this->paData = ['CNRODNI' => $laFila[0], 'CNOMAPP' => $laFila[1], 'CNOMAPM' => $laFila[2], 'CNOMBRE' => $laFila[3], 
                       'CESTADO' => $laFila[4], 'CEMAIL'  => $laFila[5], 'CNROCEL' => $laFila[6]]; 
      return true; 
   }

   //-------------------------------------------------------------------------------
   // ACTUALIZAR DATOS DE INVITADOS - Tdo1020.php
   // 2020-11-25 APR Creacion
   //------------------------------------------------------------------------------- 
   public function omActualizarDatosInvitados() { 
      $llOk = $this->mxValActualizarDatosInvitados(); 
      if (!$llOk) {
			return false;
		}
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(2); 
      if (!$llOk) { 
         $this->pcError = $loSql->pcError; 
         return false; 
      } 
      $llOk = $this->mxActualizarDatosInvitados($loSql); 
      if (!$llOk) {
         $loSql->rollback();
         return false;
      } 
      $loSql->omDisconnect(); 
      return $llOk; 
   } 
 
   protected function mxValActualizarDatosInvitados() { 
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO O NO DEFINIDO";
         return false;
      }  elseif (!isset($this->paData['CEMAIL']) || empty($this->paData['CEMAIL']) || !filter_var($this->paData['CEMAIL'], FILTER_VALIDATE_EMAIL)) {
            $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
            return false;
      }  elseif (strlen($this->paData['CNRODNI']) != 8){
            $this->pcError = 'EL NUMERO DE DNI DEBE DE SER DE 8 DIGITOS';
            return false;
      }  elseif (!isset($this->paData['CNROCEL']) || empty($this->paData['CNROCEL']) || !ctype_digit($this->paData['CNROCEL']) || strlen($this->paData['CNROCEL']) > 12) {
            $this->pcError = 'NUMERO DE CELULAR NO DEFINIDO O INVALIDO';
            return false;
      }  elseif (!isset($this->paData['CNOMBRE']) || empty($this->paData['CNOMBRE'])) {
         $this->pcError = 'NOMBRE NO DEFINIDO O INVALIDO';
         return false;
      }  elseif (!isset($this->paData['CNOMAPP']) || empty($this->paData['CNOMAPP'])) {
         $this->pcError = 'APELLIDO PATERNO NO DEFINIDO O INVALIDO';
         return false;
      }  elseif (!isset($this->paData['CNOMAPM']) || empty($this->paData['CNOMAPM'])) {
         $this->pcError = 'APELLIDO MATERNO NO DEFINIDO O INVALIDO';
         return false;
      }  
      return true; 
   } 
 
   protected function mxActualizarDatosInvitados($p_oSql) { 
      $this->paData['CNOMBRE'] = strtoupper($this->paData['CNOMBRE']);
      $this->paData['CNOMAPP'] = strtoupper($this->paData['CNOMAPP']);
      $this->paData['CNOMAPM'] = strtoupper($this->paData['CNOMAPM']);
      $this->paData['CNOMPMA'] = $this->paData['CNOMAPP'].'/'.$this->paData['CNOMAPM'].'/'.$this->paData['CNOMBRE'];
      //ACTUALIZAR DATOS DE INVITADO
      $lcSql ="UPDATE S01MPER SET CNOMBRE = '{$this->paData['CNOMPMA']}', CNROCEL = '{$this->paData['CNROCEL']}', CEMAIL = '{$this->paData['CEMAIL']}',
                  CSEXO = '{$this->paData['CPESEXO']}', cCodUsu = '{$this->paData['CUSUCOD']}', tModifi = NOW() WHERE cNroDni = '{$this->paData['CNRODNI']}'";
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL ACTUALIZAR LOS DATOS DE LA PERSONA TERCERA";
         return false;
      }    
      return true; 
   }

   //Metodo para Reestablecer Constraseña por DNI
   public function omReestablecerConstraseña() {
      $llOk = $this->mxValParamReestablecerConstraseña();
      if (!$llOk) {         
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxReestablecerConstraseña($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }
   protected function mxValParamReestablecerConstraseña() {
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "NRO DE DNI NO DEFINIDO";
         return false;      
      }
      return true;
   }  
   protected function mxReestablecerConstraseña($p_oSql) {  
      $lcNroDni = $this->paData['CNRODNI'];  
      $lcSql ="SELECT CNRODNI FROM S01MPER WHERE cNroDni = '$lcNroDni'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$p_oSql->fetch($llOk)) {         
         $this->pcError = "NO EXISTE PERSONA EN EL MAESTRO PERSONAL";
         return false;
      }
      $lcClave =  openssl_digest($lcNroDni, 'sha512'); 
      $lcSql ="UPDATE S01MPER SET cClave='$lcClave' WHERE cNroDni = '$lcNroDni'"; 
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL REESTABLECER CONTRASEÑA";
         return false;
      }            
      return true;
   }

   // -------------------------------------------------------------
   // Init mantenimiento de Usuarios
   // -------------------------------------------------------------
   public function omInitMtoUsuarios() {  
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitMtoUsuarios($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitMtoUsuarios($p_oSql) {  
      // Unidades academicas
      $i = 0;
      $lcSql = "SELECT cUniAca, cNomUni , cflujo FROM S01TUAC WHERE cEstado = 'A' ORDER BY cUniAca";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paUniAca[] = [$laFila[0], $laFila[1],$laFila[2]];
      }
      if ($i == 0) {
         $this->pcError = "UNIDADES ACADEMICAS NO DEFINIDAS";
         return false;
      }
      // Estados
      $i = 0;
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '041'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paEstado[] = [$laFila[0], $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE ESTADOS NO DEFINIDA [041]";
         return false;
      }
      // Cargos
      $i = 0;
      $lcSql = "SELECT DISTINCT cCodigo, cDescri FROM S01TTAB TA WHERE cCodTab = '155' AND SUBSTRING (ccodigo,1,1) not in ('9') ORDER BY cCodigo";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paCargos[] = [$laFila[0], $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "NO EXISTEN NIVELES";
         return false;
      }    
      // Documentos
      $i = 0;
      $lcSql = "SELECT cIdCate, cDescri, nMonto, cTipo FROM B03TDOC WHERE cEstado = 'A' ORDER BY cIdCate";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paIdCate[] = [$laFila[0], $laFila[1], $laFila[2], $laFila[3]];
      }
      if ($i == 0) {
         $this->pcError = "ID DE CATEGORIAS NO DEFINIDAS";
         return false;
      }
      return true;
   }   

   
   // ---------------------------------------------------
   // Mantenimiento de Usuarios
   // ---------------------------------------------------
   public function omInitUsuarios() {  
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitUsuarios($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitUsuarios($p_oSql) {  
      $lcSql = "SELECT 	a.cIdenti,a.cRazSoc,a.cNroRuc,a.cPago,a.cNroDni,b.cNombre,b.cSexo,b.cEmail,b.cNroCel, a.cDirecc
                FROM B04MENT a
                JOIN S01MPER b ON b.cNroDni = a.cNroDni";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDENTI' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CNRORUC' => $laFila[2], 'CPAGO' => $laFila[3], 'CNRODNI' =>$laFila[4],
                             'CNOMBRE' => $laFila[5], 'CSEXO' =>$laFila[6], 'CEMAIL' =>$laFila[7],'CNROCEL' =>$laFila[8],'CDIRECC' =>$laFila[9]];
         $i++;
      }
      $lcSql = "SELECT cCodigo, cDescri FROM S01TTAB WHERE cCodTab = '003' AND cCodigo != '0' AND cTipReg = '1' ORDER BY nOrden;";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paSexo[] = [$laFila[0], $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE SEXO NO DEFINIDA [003]";
         return false;
      }
      return true;
   } 

   // ---------------------------------------------------
   // Mantenimiento de Usuarios
   // ---------------------------------------------------
   public function omInitNuevoUsuario() {  
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitNuevoUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxInitNuevoUsuario($p_oSql) {  
      $lcSql = "SELECT cCodigo, cDescri FROM S01TTAB WHERE cCodTab = '003' AND cCodigo != '0' AND cTipReg = '1' ORDER BY nOrden;";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paSexo[] = [$laFila[0], $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE SEXO NO DEFINIDA [003]";
         return false;
      }
      return true;
   } 
   
   // ---------------------------------------------------
   // Mantenimiento de Usuarios x Cargo
   // ---------------------------------------------------
   public function omBuscarxCargo() {        
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarxCargo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxBuscarxCargo($p_oSql) {  
      $lcNivel = $this->paData['CNIVEL'];
      if ($lcNivel == '*'){
         $lcSql = "SELECT A.ccodusu,B.cNroDni, B.cnombre, C.cDescri ,A.cEstado
            FROM S01TUSU A 
            INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni 
            LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '155' AND SUBSTRING (C.ccodigo,1,1)= A.cnivel where A.cuniaca = 'TA' ORDER BY A.cEstado, B.cnombre";
      }else {
         $lcSql = "SELECT A.ccodusu,B.cNroDni, B.cnombre, C.cDescri , A.cEstado
            FROM S01TUSU A 
            INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni 
            LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '155' AND SUBSTRING (C.ccodigo,1,1)= A.cnivel where A.cuniaca = 'TA' AND A.cNivel = '$lcNivel' ORDER BY A.cEstado, B.cnombre";
      }
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = [$laFila[0], $laFila[1], $laFila[2], $laFila[3], $laFila[4]];
      }
      return true;
   } 
   // ---------------------------------------------------
   // Crear Usuario S01TUSU
   // ---------------------------------------------------
   public function omCrearUsuarios() {
      $llOk = $this->mxValParamCrearUsuarios();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxCrearUsuarios($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }

   protected function mxValParamCrearUsuarios() {      
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "DNI NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "NIVEL DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }  
      return true;
   }
  
    protected function mxCrearUsuarios($p_oSql) {     
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcNroDni = $this->paData['CNRODNI'];        
      $lcNivel = $this->paData['CNIVEL'];       
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcSql ="INSERT INTO S01TUSU( ccodusu, cnrodni, cestado, cuniaca, cnivel, cusucod) VALUES ('$lcCodUsu', '$lcNroDni', 'A', 'TA', '$lcNivel', '$lcUsuCod')";       
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL GRABAR USUARIO ";
         return false;
      } 
      return true;
   }   

   // ---------------------------------------------------
   // Editar Detalle segun Codigo de Usuario   
   // ---------------------------------------------------   
   public function omDetalleUsuario() {  
      $llOk = $this->mxValParamDetalleUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
      protected function mxValParamDetalleUsuario() {
      if (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO 2";
         return false;
      }
      return true;
   }   
   
   protected function mxDetalleUsuario($p_oSql) {  
      $lcCodUsu = $this->paData['CCODUSU'];  
      // Datos del Usuario
      $lcSql ="SELECT A.ccodusu, MP.cnombre, TA.cDescri,A.cnivel, MP.cNroDni
                  FROM S01TUSU A 
                  INNER JOIN S01MPER MP ON MP.cNroDni = A.cNroDni 
                  LEFT OUTER JOIN V_S01TTAB TA ON TA.cCodTab = '155' AND SUBSTRING (TA.ccodigo,1,1)= A.cnivel
                  WHERE A.cCodUsu = '$lcCodUsu'"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);      
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR USUARIO";
         return false;
      }        
      $this->paData = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CCARGO' => $laFila[2],'CNIVEL' => $laFila[3],'CNRODNI' => $laFila[4]];
      // Trae detalle del Usuario
       $lcSql ="SELECT A.nSerial, C.cNomUni, D.cDescri, B.cDescri, A.cEstado
                  FROM B03DUSU A                   
                  INNER JOIN B03TDOC B ON B.CIDCATE = A.CIDCATE 
                  INNER JOIN S01TUAC C ON C.CUNIACA = A.CUNIACA 
                  LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '155' AND SUBSTRING (D.ccodigo,1,1)= A.cnivel
                   WHERE A.cCodUsu = '$lcCodUsu' ORDER BY A.nSerial;"; 
      $R1 = $p_oSql->omExec($lcSql);           
      while ($laFila = $p_oSql->fetch($R1)) {         
         $this->paDatos [] = [$laFila[0],$laFila[1],$laFila[2],$laFila[3],$laFila[4]];  
      } 
      return true;
   }

   // ---------------------------------------------------
   // Grabar Detalle de Usuario   
   // ---------------------------------------------------
   public function omGrabarUsuarioDetalle() { 
      $llOk = $this->mxValParamGrabarUsuarioDetalle();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarUsuarioDetalle($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }   
   protected function mxValParamGrabarUsuarioDetalle() {
      
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "NIVEL NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUNIACA'])) {
         $this->pcError = "UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      } 
      return true;
   }   
   
   protected function mxGrabarUsuarioDetalle($p_oSql) {  
      $lcCodUsu = $this->paData['CCODUSU'];  
      $lcNivel = $this->paData['CNIVEL']; 
      $lcUniAca = $this->paData['CUNIACA'];
      $lcUsuCod = $this->paData['CUSUCOD'];
      //VALIDAR PERMISO REPETIDO
      $lcSql = "SELECT cUniAca FROM B03DUSU WHERE cCodUsu = '$lcCodUsu' AND cUniAca = '$lcUniAca';";    
      $R1= $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if ($laTmp[0] == $lcUniAca) {
         $this->pcError = 'YA SE AÑADIO ESTE PERMISO';
         return false;
      }
      $lcSql ="SELECT cFlujo FROM S01TUAC WHERE cUniAca = '$lcUniAca'";
      $R1 = $p_oSql->omExec($lcSql);     
      $laTmp = $p_oSql->fetch($R1);      
      if ($laTmp[0]=='0') {         
         $this->pcError = "ERROR AL BUSCAR FLUJO";
         return false;
      } 
      //SECRETARIA
      if ($lcNivel=='1'){         
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' ,  'CCCOND',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - SECRETARIA";
            return false;
         }      
      //DIRECTOR FLUJO 1 
      //DIRECTOR FLUJO 2 (NO FIRMA CCOND) DERECHO
      }elseif ($lcNivel=='2' && $laTmp[0]=='1'){           
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' , 'CCCOND',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - DIRECTOR ";
            return false;
         }
      
      }elseif ($lcNivel=='2' && $laTmp[0]=='2'){      
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' ,  'CCCOND',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - DIRECTOR";
            return false;
         }
      //DECANO
      }elseif ($lcNivel=='3'){  
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' ,  'CCCOND',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - DECANO ";
            return false;
         }
      //COORDINADOR  
      }elseif ($lcNivel=='4'){      
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' ,  'CCCONL',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - COORDINADOR";
            return false;
         }      
      //BIBLIOTECA  
      }elseif ($lcNivel=='5'){      
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' ,  'CCCONB',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - COORDINADOR BIBLIOTECA";
            return false;
         }
      }elseif ($lcNivel=='5'){      
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('$lcCodUsu', '$lcUniAca' ,  'CCCONB',  '$lcNivel','A','$lcUsuCod');"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - COORDINADOR BIBLIOTECA";
            return false;
         }
      }
      return true;
   }

   // ---------------------------------------------------
   // Iniciar para Editar Cabecera de Usuario
   // ---------------------------------------------------
   public function omEditarUsuario() {  
      $llOk = $this->mxValParamEditarUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEditarUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamEditarUsuario() {
      if (empty($this->paData['CIDENTI'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }
      return true;
   }   
   
   protected function mxEditarUsuario($p_oSql) {  
      $lcIdenti = $this->paData['CIDENTI'];      
      // Datos del Usuario - S01TUSU      
      $lcSql ="SELECT  a.cIdenti, 
                        a.cRazSoc, 
                        a.cNroRUC, 
                        a.cPago, 
                        a.cNroDNI,
                        b.cNombre,
                        b.cSexo,
                        b.cNroCel,
                        b.cEmail,
                        a.cDirecc
               FROM b04ment a 
               JOIN S01MPER b ON b.cNroDni = a.cNroDNI
               WHERE a.cIdenti = '$lcIdenti';"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);      
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR USUARIO";
         return false;
      }
      $lcNomPer = explode("/", $laFila[5]);
      $this->paData = ['CIDENTI' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CNRORUC' => $laFila[2],'CPAGO' => $laFila[3],
                       'CNRODNI' => $laFila[4],'CSEXO' => $laFila[6],
                       'CNROCEL' => $laFila[7], 'CEMAIL' => $laFila[8], 'CNOMAPP' => $lcNomPer[0], 'CNOMAPM' => $lcNomPer[1],
                       'CNOMBRES' => $lcNomPer[2], 'CDIRECC' => $laFila[9]];
      return true;
   }  

   // ---------------------------------------------------
   // Actualizar Cabecera de Usuario
   // ---------------------------------------------------
   public function omActualizarUsuario() {  
      $llOk = $this->mxValParamActualizarUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamActualizarUsuario() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CESTADO'])) {
         $this->pcError = "ESTADO DE PAQUETE NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }
      return true;
   }      
   protected function mxActualizarUsuario($p_oSql) {        
      $lcCodUsu = $this->paData['CCODUSU'];      
      $lcEstado = $this->paData['CESTADO'];
      $lcUsuCod = $this->paData['CUSUCOD'];
      
      // Datos del Usuario
      $lcSql = "UPDATE S01TUSU SET cestado = '$lcEstado', cusucod ='$lcUsuCod', tmodifi=NOW()
                   WHERE cCodUsu='$lcCodUsu';";                   
      $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
         $this->pcError = 'ERROR AL ACTUALIZAR USUARIO';
         return false;
      }      
      return true;
   }  

   //Buscar Persona segun DNI
   public function omBuscarPersonas() {
      $llOk = $this->mxValParamBuscarUsuarios();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxBuscarPersona($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }
   protected function mxValParamBuscarUsuarios() {
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "NRO DE DNI NO DEFINIDO";
         return false;      
      }
      return true;
   }  
    protected function mxBuscarPersona($p_oSql) {  
      $lcNroDni = $this->paData['CNRODNI'];     
      $lcSql ="SELECT A.cNombre , B.ccoddoc FROM S01MPER A
               LEFT JOIN A01MDOC B ON A.cNroDni = B.cNroDni
               WHERE A.cNroDni = '$lcNroDni'";                  
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);      
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR PERSONA";
         return false;
      }   
      if ($laFila[1]){
         echo $laFila[0].$laFila[1].'OK'; 
      }else{
         $lcSql ="SELECT A.cNombre , B.ccodadm FROM S01MPER A
               LEFT JOIN A01MADM B ON A.cNroDni = B.cNroDni
               WHERE A.cNroDni = '$lcNroDni'";                  
         $R1 = $p_oSql->omExec($lcSql);     
         $laFila = $p_oSql->fetch($R1);   
         
         if (!$laFila) {         
          $this->pcError = "ERROR AL BUSCAR PERSONA";
          return false;
         }    
         elseif ($laFila[1]){
            echo $laFila[0].$laFila[1].'OK'; 
         }
         else{
            echo $laFila[0];
         }
      }      
      return true;
   }

   // ------------------------------------------------------------------------------
   // Buscar cargos - Usuario por DNI (JSON)
   // 2019-05-10 MLC Creacion
   // ------------------------------------------------------------------------------
   public function omBuscarCargosDisponibles2() {
      $llOk = $this->mxValParamBuscarCargosDisponibles();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxBuscarCargosDisponibles2($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }

   protected function mxBuscarCargosDisponibles2($p_oSql) {
      $lcSql = "SELECT DISTINCT C.cDescri, A.cNivel 
                FROM B03DUSU A
                INNER JOIN S01TUSU B ON A.cCodUsu = B.cCodUsu
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '155' AND TRIM(C.cCodigo) = A.cnivel
                WHERE B.cNroDni = '{$this->paData['CNRODNI']}' AND A.cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CDESCRI' => $laFila[0], 'CNIVEL' => $laFila[1]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "SIN CARGOS DISPONIBLES";
         return false;
      }
      return true;
   }

   //Buscar Cargos - Usuario segun DNI
   public function omBuscarCargosDisponibles() {
      $llOk = $this->mxValParamBuscarCargosDisponibles();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxBuscarCargosDisponibles($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }
   protected function mxValParamBuscarCargosDisponibles() {
      if (!isset($this->paData['CNRODNI']) || empty($this->paData['CNRODNI'])) {
         $this->pcError = "NRO DE DNI NO DEFINIDO";
         return false;      
      }
      return true;
   }  
    protected function mxBuscarCargosDisponibles($p_oSql) {  
      $lcNroDni = $this->paData['CNRODNI']; 
      //Revisar Cargos ASignados B03DUSU    
      $lcSql ="SELECT DISTINCT C.cDescri, A.cNivel FROM B03DUSU A
               INNER JOIN S01TUSU B ON A.cCodUsu = B.cCodUsu
               LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '155' AND SUBSTRING (C.ccodigo,1,1)= A.cnivel
               WHERE B.cNroDni = '$lcNroDni' AND A.cEstado = 'A' AND B.cEstado = 'A'";                 
      //$R1 = $p_oSql->omExec($lcSql); 
      //$laTmp = $p_oSql->fetch($R1);
      /*
      if(empty($laTmp[0])){
         //Revisar Cargo Asignado S01TUSU    
         $lcSql ="SELECT DISTINCT C.cDescri, A.cNivel FROM S01TUSU A
            LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '155' AND SUBSTRING (C.ccodigo,1,1)= A.cnivel
            WHERE A.cNroDni = '$lcNroDni' AND A.cEstado='A'"; 
         $R2 = $p_oSql->omExec($lcSql); 
         $laFila = $p_oSql->fetch($R2);
         if ($laFila[0]) {
            echo ' + '.$laFila[0].$laFila[1].'*';
         } else {
            $this->pcError = "NO DISPONIBLE";            
            return false;
         }
      } else{*/
         $R1 = $p_oSql->omExec($lcSql); 
         if ($R1 == false || $p_oSql->pnNumRow == 0) {
            $this->pcError = "NO DISPONIBLE";
            return false;
         }
         while ($laFila = $p_oSql->fetch($R1)) {
            echo $laFila[0].$laFila[1].'*';
         } 
      //}
      return true;
   }
   // ---------------------------------------------------
   // Actualizar Detalle de Usuario
   // ---------------------------------------------------
   public function omActualizarUsuarioDetalle() {  
      $llOk = $this->mxValParamActualizarUsuarioDetalle();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarUsuarioDetalle($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamActualizarUsuarioDetalle() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;      
      }
      return true;
   }      
   protected function mxActualizarUsuarioDetalle($p_oSql) {  
      $lcCodUsu = $this->paData['CCODUSU'];
      // Trae detalle del paquete
      $lcSql = "SELECT A.nSerial FROM B03DUSU A                
                WHERE cCodUsu = '$lcCodUsu' ORDER BY A.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laTmp = $p_oSql->fetch($R1)) {         
         $lnSerial = $laTmp[0];
         $lcEstado =$this->paEstado[$i];
         $lcSql = "UPDATE B03DUSU SET cestado='$lcEstado', tmodifi=NOW()
                   WHERE nSerial='$lnSerial';";
         $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
            $this->pcError = 'ERROR AL ACTUALIZAR PERMISOS';
            return false;
         }     
         $i++;
      }   
      return true;
   }  

   // ---------------------------------------------------
   //  Usuarios x Unidad Academica
   // ---------------------------------------------------
   public function omInitUsuariosUniAca() {  
      $llOk = $this->mxValParamInitUsuariosUniAca();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitUsuariosUniAca($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamInitUsuariosUniAca() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUNIACA'])) {
         $this->pcError = "UNIADAD ACADEMICA NO DEFINIDA";
         return false;
      }
      return true;
   }   
   
   protected function mxInitUsuariosUniAca($p_oSql) {  
      //LISTA USUARIOS SEGUN UNIDAD ACADEMICA SECRETARIA
      $lcUniAca = $this->paData['CUNIACA'];
      $lcSql = "SELECT A.cCodUsu,B.cNroDni,C.cNombre,D.cDescri,A.cEstado , A.cNivel FROM B03DUSU A   
         INNER JOIN S01TUSU B ON B.cCodUsu = A.cCodUsu 
         INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni 
         LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '155' AND SUBSTRING (D.ccodigo,1,1)= A.cNivel
         WHERE A.cUniAca = '$lcUniAca' ORDER BY A.cEstado,A.cNivel";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = [$laFila[0], $laFila[1], $laFila[2], $laFila[3],$laFila[4],$laFila[5]];
      }
      return true;
   } 

   // ---------------------------------------------------
   // Detalle Codigo de Usuario para Cambio
   // ---------------------------------------------------   
   public function omDetalleUsuarioCambio() {  
      $llOk = $this->mxValParamDetalleUsuarioCambio();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleUsuarioCambio($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamDetalleUsuarioCambio() {
      if (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO 2";
         return false;
      } elseif (empty($this->paData['CUNIACA'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO 2";
         return false;
      } elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO 2";
         return false;
      }
      return true;
   }   
   
   protected function mxDetalleUsuarioCambio($p_oSql) {  
      $lcCodUsu = $this->paData['CCODUSU'];  
      $lcUniAca = $this->paData['CUNIACA'];
      $lcNivel = $this->paData['CNIVEL'];
      //Datos del Usuario
      $lcSql ="SELECT A.ccodusu, C.cnombre, D.cDescri,A.cnivel, B.cNroDni
                  FROM B03DUSU A 
                  INNER JOIN S01TUSU B ON B.ccodusu = A.ccodusu 
                  INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni                   
                  LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '155' AND SUBSTRING (D.ccodigo,1,1)= A.cnivel
                   WHERE A.cCodUsu = '$lcCodUsu' AND A.cuniaca = '$lcUniAca' AND A.cNivel= '$lcNivel'"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);      
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR USUARIO";
         return false;
      }        
      $this->paData = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CCARGO' => $laFila[2],'CNIVEL' => $laFila[3],'CNRODNI' => $laFila[4]];      
      return true;
   }

   // ---------------------------------------------------
   //  Cambio Usuario x Usuario x Permisos
   // ---------------------------------------------------
   public function omCambioUsuarioPermisos() {  
      $llOk = $this->mxValParamCambioUsuarioPermisos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCambioUsuarioPermisos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }   
   protected function mxValParamCambioUsuarioPermisos() {      
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE ENCARGADO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUNIACA'])) {
         $this->pcError = "UNIADAD ACADEMICA NO DEFINIDA";
         return false;
      } elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "NIVEL DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CNRODNI'])) {
         $this->pcError = "PERSONA NO DEFINIDA";
         return false;
      } elseif (empty($this->paData['CCODUSU2'])) {
         $this->pcError = "NO SE PUEDE REALZIAR EL CAMBIO POR ESTA PERSON, NO CUENTA CON UN CODIGO DE USUARIO VALIDA";
         return false;
      } elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }
      return true;
   }   
   
   protected function mxCambioUsuarioPermisos($p_oSql) {      
      $lcUsuCod = $this->paData['CUSUCOD'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcNivel = $this->paData['CNIVEL'];
      $lcNroDni = $this->paData['CNRODNI'];
      //1. CABECERA USUARIO S01TUSU
      //S01TUSU - USUARIO NUEVO
      $lcSql = "SELECT cCodUsu FROM S01TUSU WHERE cNroDni = '$lcNroDni'";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);      
      if (empty($laTmp[0])){
         $lcCodUsu2 = $this->paData['CCODUSU2'];   
         $lcSql ="INSERT INTO S01TUSU( ccodusu, cnrodni, cestado, cuniaca, cnivel, cusucod) VALUES ('$lcCodUsu2', '$lcNroDni', 'A', 'TA', '$lcNivel', '$lcUsuCod')";       
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO ";
            return false;
         } 
      //S01TUSU - USUARIO EXISTENTE
      }else{
         $lcCodUsu2 = $laTmp[0]; 
         // ACTUALIZAR S01TUSU
         $lcSql = "UPDATE S01TUSU SET cUniAca='TA', cEstado='A', tmodifi=NOW() WHERE cCodUsu='$lcCodUsu2';";
         $llOk = $p_oSql->omExec($lcSql);
         if (!$llOk) {
            $this->pcError = 'ERROR AL ACTUALIZAR NIVEL USUARIO2';
            return false;
         }
      }
      //2. DETALLE USUARIO B03DUSU
      //B03DUSU - DETALLE USUARIO ES NUEVO
      $lcSql = "SELECT nSerial, cUniAca, cIdCate , cNivel FROM B03DUSU WHERE cCodUsu = '$lcCodUsu2'";      
      $R2 = $p_oSql->omExec($lcSql); 
      $laTmp = $p_oSql->fetch($R2); 
      if (empty($laTmp[0])){
         //B03DUSU USUARIO 1
         $lcSql = "SELECT nSerial, cUniAca, cIdCate , cNivel FROM B03DUSU WHERE cCodUsu = '$lcCodUsu' AND cEstado = 'A' AND cNivel='$lcNivel'";       
         $R3 = $p_oSql->omExec($lcSql);
         while ($laFila = $p_oSql->fetch($R3)) {
            // INACTIVAR USUARIO 1 
            $lcSql = "UPDATE B03DUSU SET cestado='I', tmodifi=NOW()
                      WHERE nSerial='$laFila[0]';";                      
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = 'ERROR AL ACTUALIZAR PERMISOS';
               return false;
            }
            //CREAR B03DUSU PARA USUARIO 2
            $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod) VALUES ('$lcCodUsu2', '$laFila[1]' , '$laFila[2]', '$laFila[3]','A','$lcUsuCod');"; 
            $llOk = $p_oSql->omExec($lcSql); 
            if (!$llOk) {         
               $this->pcError = "ERROR AL GRABAR PERMISO USUARIO2 1 ";
               return false;
            }            
         }   
      //B03DUSU - DETALLE USUARIO EXISTE
      }else{
         //SACAR B03DUSU DEL USUARIO 1
         $lcSql = "SELECT nSerial, cUniAca, cIdCate , cNivel FROM B03DUSU WHERE cCodUsu = '$lcCodUsu' AND cEstado = 'A' AND cNivel='$lcNivel'";
         $R3 = $p_oSql->omExec($lcSql);
         //B03DUSU USUARIO 1
         while ($laFila = $p_oSql->fetch($R3)) {
            // INACTIVAR USUARIO 1 
            $lcSql = "UPDATE B03DUSU SET cestado='I', tmodifi=NOW()
                      WHERE nSerial='$laFila[0]';";
            $llOk = $p_oSql->omExec($lcSql);
            if (!$llOk) {
               $this->pcError = 'ERROR AL ACTUALIZAR PERMISOS';
               return false;
            }
            //VALIDAMOS
            //SACAR B03DUSU DEL USUARIO 2
            $lcSql = "SELECT nSerial, cUniAca, cIdCate , cNivel FROM B03DUSU WHERE cCodUsu = '$lcCodUsu2' AND cUniAca = '$laFila[1]' AND cIdCate= '$laFila[2]' AND cNivel = '$laFila[3]'";
            $R4 = $p_oSql->omExec($lcSql);
            $laTmp = $p_oSql->fetch($R4);   
            if (empty($laTmp[0])) {
               $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod) VALUES ('$lcCodUsu2','$laFila[1]','$laFila[2]','$laFila[3]','A','$lcUsuCod');";                 
               $llOk = $p_oSql->omExec($lcSql); 
               if (!$llOk) {         
                  $this->pcError = "ERROR AL GRABAR PERMISO USUARIO2 21";
                  return false;
               }
            }else{
               //ACTUALIZAR PERMISOS USUARIO 2
               $lcSql = "UPDATE B03DUSU SET cestado='A', tmodifi=NOW()
                   WHERE nSerial='$laTmp[0]';";
               $llOk = $p_oSql->omExec($lcSql);
               if (!$llOk) {         
                  $this->pcError = "ERROR AL ACTUALIZAR PERMISO USUARIO2 ";
                  return false;
               }
            }
         }
      }    
      //3. TRAMITES ASIGNADOS B03DLOG
      //MOVER TRAMITES NO APROBADOS USUARIO 1 A USUARIO 2
      $lcSql = "UPDATE B03DLOG SET cCodUsu='$lcCodUsu2', tmodifi=NOW()
                WHERE cCodUsu='$lcCodUsu' AND cAproba = 'N';";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = 'ERROR AL MOVER TRAMITES';
         return false;
      }  
      return true;
   }
   
   
   // ---------------------------------------------------
   // Crear Entidad B04MENT
   // ---------------------------------------------------
   public function omCrearEntidadyUsuario() {
      /*$llOk = $this->mxValParamCrearEntidadyUsuario();
      if (!$llOk) {
         return false;
      }*/
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxCrearEntidadyUsuario($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }

   protected function mxValParamCrearEntidadyUsuario() {      
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "DNI NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "NIVEL DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }  
      return true;
   }
  
    protected function mxCrearEntidadyUsuario($p_oSql) {     
      $lcSql ="SELECT MAX(cIdenti) FROM B04MENT";       
      $RS = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($RS);
      $lcIdenti = (empty($laTmp[0]))?'0000':$laTmp[0];
      $i = (int)$lcIdenti + 1;
      $this->paData['CIDENTI'] = sprintf('%04d', $i);
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_B04MENT_1 ('$lcJson');";
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL GRABAR USUARIO ";
         return false;
      } 
      return true;
   } 
   
   public function omEditarEntidadyUsuario() {
      /*$llOk = $this->mxValParamCrearEntidadyUsuario();
      if (!$llOk) {
         return false;
      }*/
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxEditarEntidadyUsuario($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }

   protected function mxValParamEditarEntidadyUsuario() {      
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "DNI NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "NIVEL DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }  
      return true;
   }
  
    protected function mxEditarEntidadyUsuario($p_oSql) {     
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT P_B04MENT_2 ('$lcJson');";
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL GRABAR USUARIO ";
         return false;
      } 
      return true;
   }

   // LVA LVA
   // --------------------------------------------------------------------------
   // BUSCA NOMBRE Y CODIGO DE PERSONA POR MEDIO DE DNI
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   /*public function omBuscarPersonaDNI() {
      $llOk = $this->mxValParamBuscarPersonasDNI();
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxBuscarPersonaDNI($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }

   protected function mxValParamBuscarPersonaDNI() {
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "NRO DE DNI NO DEFINIDO";
         return false;      
      }
      return true;
   }

   protected function mxBuscarPersonaDNI($p_oSql) {  
      $lcNroDni = $this->paData['CNRODNI'];     
      $lcSql ="SELECT A.cNombre , B.ccoddoc FROM S01MPER A
               LEFT JOIN A01MDOC B ON A.cNroDni = B.cNroDni
               WHERE A.cNroDni = '$lcNroDni'";                  
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);     
      if ($laFila) {         
         $this->pcError = "ERROR AL BUSCAR PERSONA";
         return false;
      }   
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR PERSONA";
         return false;
      }   
      if ($laFila[1]){
         echo $laFila[0].$laFila[1].'OK'; 
      }else{
         $lcSql ="SELECT A.cNombre , B.ccodadm FROM S01MPER A
               LEFT JOIN A01MADM B ON A.cNroDni = B.cNroDni
               WHERE A.cNroDni = '$lcNroDni'";                  
         $R1 = $p_oSql->omExec($lcSql);     
         $laFila = $p_oSql->fetch($R1);   
         
         if (!$laFila) {         
          $this->pcError = "ERROR AL BUSCAR PERSONA";
          return false;
         }    
         elseif ($laFila[1]){
            echo $laFila[0].$laFila[1].'OK'; 
         }
         else{
            echo $laFila[0];
         }
      }      
      return true;
   }*/

   // --------------------------------------------------------------------------
   // Crea una nueva persona en la tabla S01MPER (MTO S01MPER) 
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omGrabarNuevaPersona() { 
      $llOk = $this->mxValParamGrabarNuevaPersona();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarNuevaPersona($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarNuevaPersona() {
      if (!isset($this->paData['CNRODNI']) || !preg_match("(^[0-9]+$)", $this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES DNI';
         return false;
      } elseif (!isset($this->paData['CNOMNUE']) || !preg_match("(^[a-zA-ZáéíóúñÁÉÍÓÚÑ /]+$)", $this->paData['CNOMNUE'])) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES EN LOS PARAMETROS DE LOS NOMBRES';
         return false;
      } elseif (!isset($this->paData['CNROCEL']) || !preg_match("(^[0-9]+$)", $this->paData['CNROCEL'])) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES CNROCEL';
         return false;
      } elseif (!isset($this->paData['CSEXO']) || !preg_match("(^[MF]{1}$)", $this->paData['CSEXO']) || strlen($this->paData['CSEXO']) != 1) {
         $this->pcError = 'PARAMETRO SEXO INVALIDO';
         return false;
      } elseif (!isset($this->paData['DNACIMI'])) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES EN FECHA DE NACIMIENTO';
         return false;
      }
      return true;
   }
   
   protected function mxGrabarNuevaPersona($p_oSql) {
      $lcParam = json_encode($this->paData);
      $lcSql = "SELECT P_S01MPER_3('$lcParam')";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp[0]) {
         $this->pcError = "ERROR EN EJECUCION DE GRABACION DE LA TRANSACCION";
         return false;
      }
      $laData = json_decode($laTmp[0], true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return true;
   }

   // ---------------------------------------------------------------------------------
   // Asignacion de nivel a una nueva persona en las tablas S01TUSU, B03PUSU, B03DUSU
   // 2019-02-07 LVA Creacion
   // ---------------------------------------------------------------------------------
   public function omGrabarNuevoNivelUsuario() { 
      $llOk = $this->mxValParamGrabarNuevoNivelUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarNuevoNivelUsuario($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarNuevoNivelUsuario() {
      if (!isset($this->paData['CNRODNI']) || !preg_match("(^[0-9]+$)", $this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES / DNI INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCODNUE']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODNUE'])) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES / CÓDIGO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CNIVEL']) || strlen($this->paData['CNIVEL']) != 1) {
         $this->pcError = 'NO USAR CARACTERES ESPECIALES CNROCEL';
         return false;
      } elseif (!isset($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = 'EL CODIGO DEL USUARIO ES INAVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || !preg_match("(^[A-Z0-9]+$)", $this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      }
      return true;
   }
   
   protected function mxGrabarNuevoNivelUsuario($p_oSql) {
      $lcParam = json_encode($this->paData);
      $lcSql = "SELECT P_S01TUSU_3('$lcParam')";
      $R1 = $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if (!$laTmp[0]) {
         $this->pcError = "ERROR EN EJECUCION DE GRABACION DE LA TRANSACCION";
         return false;
      }
      $laData = json_decode($laTmp[0], true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------------
   // Verifica alumno por codigo ingresado
   // 2019-02-05 LVA Creacion
   // ------------------------------------------------------------------------------
   public function omVerificarPersonaxDni() {
      $llOk = $this->mxValParamVerificarPersonaxDni();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerificarPersonaxDni($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamVerificarPersonaxDni() {   // OJO FALTA VERIFICAR CODIGO DE ALUMNO... QUE DEBE PASAR A SER DNI
      if (!isset($this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CNRODNI']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'EL NUMERO DE DNI INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxVerificarPersonaxDni($p_oSql) {
      $lcSql = "SELECT cNombre FROM S01MPER WHERE CNRODNI = '{$this->paData['CNRODNI']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0])) {
         $this->pcError = "NUMERO DE DNI NO EXISTE";
         return false;
      }
      $this->paData = ['CNOMBRE' => $laFila[0]];   
      return true;
   }

   // ------------------------------------------------------------------------------
   // Verifica alumno por codigo ingresado
   // 2019-02-05 LVA Creacion
   // ------------------------------------------------------------------------------
   public function omVerificarUsuarioxDni() {
      $llOk = $this->mxValParamVerificarUsuarioxDni();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerificarUsuarioxDni($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamVerificarUsuarioxDni() {
      if (!isset($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
      //if (!isset($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CNRODNI']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CNRODNI']) != 8) {
         $this->pcError = 'EL NUMERO DE DNI INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxVerificarUsuarioxDni($p_oSql) {
      $lcSql = "SELECT B.cNombre, A.cCodUsu FROM S01TUSU A INNER JOIN S01MPER B ON B.CNRODNI = A.CNRODNI WHERE A.CNRODNI = '{$this->paData['CNRODNI']}' AND A.cEstado = 'A'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0])) {
         $lcSql = "SELECT cNombre FROM S01MPER WHERE CNRODNI = '{$this->paData['CNRODNI']}'";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         if (!isset($laFila[0])) {
            $this->pcError = "NUMERO DE DNI NO EXISTE";
            return false;
         }
         $this->paData = ['CNOMBRE' => $laFila[0]];   
         return true;
      }
      $this->paData = ['CNOMBRE' => $laFila[0], 'CCODUSU' => $laFila[1]];   
      return true;
   }

   // --------------------------------------------------------------------------
   // Obtiene datos basicos para cambiar el estado
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omEditarEstadoUsuario() {  
      $llOk = $this->mxValParamEditarEstadoUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEditarEstadoUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxValParamEditarEstadoUsuario() {
      if (!isset($this->paData['NSERIAL'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }
      return true;
   }   
   
   protected function mxEditarEstadoUsuario($p_oSql) {  
      $lcSql ="SELECT C.cCodUsu, B.cNombre,B.cNrodni,C.cEstado FROM S01TUSU A
                  INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni
                  INNER JOIN B03DUSU C ON C.cCodUsu = A.cCodUsu WHERE C.nSerial = '{$this->paData['NSERIAL']}'"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);      
      $this->paDatos = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CNRODNI' => $laFila[2], 'CESTADO' => $laFila[3]];
      if (count($this->paDatos) == 0) {
         $this->pcError = "SIN SOLICITUDES PENDIENTES";
         return false;
      }   
      return true;
   }

   // --------------------------------------------------------------------------
   // Busca a usuarios por el nivel seleccionado
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omBuscarxCargoUsuarios() {        
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarxCargoUsuarios($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxBuscarxCargoUsuarios($p_oSql) {  
      $lcNivel = $this->paData['CNIVEL'];
      if ($lcNivel == '*'){
         $lcSql = "SELECT DISTINCT D.nSerial, A.cCodUsu,B.cNroDni, B.cNombre, C.cDescri, D.cEstado, E.cNomUni
                   FROM S01TUSU A 
                   INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni 
                   INNER JOIN S01TTAB C ON C.cCodTab = '155' 
                   INNER JOIN B03DUSU D ON D.cCodUsu = A.cCodUsu
                   AND SUBSTRING (C.cCodigo,1,1)= D.cNivel
                   INNER JOIN S01TUAC E ON E.cUniAca = D.cUniAca 
                   ORDER BY D.cEstado, B.cNombre"; 
      } else {
         $lcSql = "SELECT DISTINCT D.nSerial, A.ccodusu,B.cNroDni, B.cnombre, C.cDescri, D.cEstado, E.cNomUni
                   FROM S01TUSU A 
                   LEFT JOIN B03DUSU D ON D.cCodUsu = A.cCodUsu 
                   INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni 
                   LEFT OUTER JOIN S01TTAB C ON C.cCodTab = '155'
                   AND SUBSTRING (C.cCodigo,1,1)= '$lcNivel' 
                   INNER JOIN S01TUAC E ON E.cUniAca = D.cUniAca
                   WHERE D.cNivel = '{$this->paData['CNIVEL']}' ORDER BY D.cEstado, B.cNombre";
      }

      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CCODUSU' => $laFila[1], 'CNRODNI' => $laFila[2], 'CNOMBRE' => $laFila[3], 
                             'CDESCRI' => $laFila[4], 'CESTADO' => $laFila[5], 'CNOMUNI' => $laFila[6]];
      }
      return true;
   } 

// ---------------------------------------------------
   // Crear Usuario B03PUSU NUEVO
   // ---------------------------------------------------
   /*public function omCrearUsuariosNuevos() {
      $llOk = $this->mxValParamCrearUsuariosNuevos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }     
      $llOk = $this->mxCrearUsuariosNuevos($loSql);  
      $loSql->omDisconnect();    
      return $llOk;
   }

   protected function mxValParamCrearUsuariosNuevos() {      
     
      if (empty($this->paData['CNRODNI'])) {
         $this->pcError = "DNI NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CNIVEL'])) {
         $this->pcError = "NIVEL DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }  
      return true;
   }
  
    protected function mxCrearUsuariosNuevos($p_oSql) {     
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcNroDni = $this->paData['CNRODNI'];        
      $lcNivel = $this->paData['CNIVEL'];       
      $lcUsuCod = $this->paData['CUSUCOD'];
      
      $lcSql = "INSERT INTO S01TUSU( ccodusu, cnrodni, cestado, cuniaca, cnivel, cusucod) VALUES ('$lcCodUsu', '$lcNroDni', 'A', 'TA', '$lcNivel', '$lcUsuCod')";
      
      //$lcSql ="INSERT INTO B03PUSU (cCodigo, cCenCos, cCodUsu, cEstado, cUsuCod, tModifi) VALUES ('$lcCodigo', '$lcCencos', '$lcCodUsu', 'A', '$lcUsuCod',NOW())";
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL GRABAR USUARIO ";
         return false;
      } 
      return true;
   }  */

   // --------------------------------------------------------------------------
   // Cargar datos para la inserción de nuevo usuario
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omCargarDatos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarDatos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxCargarDatos($p_oSql) {  
      // Unidades academicas
      $i = 0;
      $lcSql = "SELECT cUniAca, cNomUni , cflujo FROM S01TUAC WHERE cEstado = 'A' OR cUniAca = '00' ORDER BY cUniAca";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paUniAca[] = ['CUNIACA' =>$laFila[0], 'CNOMUNI' =>$laFila[1], 'CFLUJO' =>$laFila[2]];
      }
      if ($i == 0) {
         $this->pcError = "UNIDADES ACADEMICAS NO DEFINIDAS";
         return false;
      }
      // Estados
      $i = 0;
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '041'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paEstado[]= ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE ESTADOS NO DEFINIDA [041]";
         return false;
      }
      // Cargos
      $i = 0;
      $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE cCodTab = '155'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paCargos[] = ['CNIVEL' => $laFila[0], 'CDESCRI' =>  $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE CARGOS NO DEFINIDA [155]";
         return false;
      }    
      // Documentos
      $i = 0;
      $lcSql = "SELECT cIdCate, cDescri, nMonto, cTipo FROM B03TDOC WHERE cEstado = 'A' ORDER BY cIdCate";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paIdCate[] = ['CIDCATE' => $laFila[0], 'CDESCRI' => $laFila[1], 'MMONTO' => $laFila[2], 'CTIPO' =>  $laFila[3]];
      }
      if ($i == 0) {
         $this->pcError = "ID DE CATEGORIAS NO DEFINIDAS";
         return false;
      }
      //CENTRO DE COSTOS
      $i = 0;
      $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paCenCos['pcCenCos'] = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paCenCos['pcCenCos'][] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CENTROS DE COSTO";
         return false;
      }
      
      //GENERO
      $i = 0;
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM S01TTAB TA WHERE cCodTab = '003' AND SUBSTRING (ccodigo,1,1) NOT IN ('0','')";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paSexo[] = ['CCODIGO' => $laFila[0], 'CDESCRI' =>  $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE SEXO NO DEFINIDA [003]";
         return false;
      } 
      return true;
   }
   ///// NUEV VERSION

   public function omCargarDatos2() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarDatos2($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   
   protected function mxCargarDatos2($p_oSql) {  
      // Unidades academicas
      $i = 0;
      $lcSql = "SELECT cUniAca, cNomUni , cflujo FROM S01TUAC WHERE cEstado = 'A' OR cUniAca = '00' ORDER BY cUniAca";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos['paUniAca'] = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos['paUniAca'][]  = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CFLUJO' => $laFila[2]];
      }
      if ($i == 0) {
         $this->pcError = "UNIDADES ACADEMICAS NO DEFINIDAS";
         return false;
      }
      // Estados
      $i = 0;
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM V_S01TTAB WHERE cCodTab = '041'";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos['paEstado'] = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos['paEstado'][] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE ESTADOS NO DEFINIDA ";
         return false;
      }
      // Cargos
      $i = 0;
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM S01TTAB TA WHERE cCodTab = '155' AND SUBSTRING (ccodigo,1,1) not in ('9')";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos['pcCargos'] = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos['paCargos'][] = ['CNIVEL' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE CARGOS NO DEFINIDA";
         return false;
      }    

      //CENTRO DE COSTOS
      $i = 0;
      $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cDescri LIKE '%E.P%' AND cEstado = 'A' OR cCenCos IN ('000', '00Y', '00Z', '00V', '0FA');";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos['paCenCos'] = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos['paCenCos'][] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "ERROR AL RECUPERAR CENTROS DE COSTO";
         return false;
      }
      //GENERO
      $i = 0;
      $lcSql = "SELECT SUBSTRING(cCodigo, 1, 1), cDescri FROM S01TTAB TA WHERE cCodTab = '003' AND SUBSTRING (ccodigo,1,1) NOT IN ('0','')";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos['paSexo'] = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos['paSexo'][] = ['CCODIGO' => $laFila[0], 'CDESCRI' =>  $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "SUBTABLA DE SEXO NO DEFINIDA";
         return false;
      } 
      return true;
   }

   // --------------------------------------------------------------------------
   // Detalle de cargos/niveles de usuario seleccionado
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omDetalleUsuarioN() {  
      $llOk = $this->mxValParamDetalleUsuarioN();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxDetalleUsuarioN($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamDetalleUsuarioN() {
      //if (empty($this->paData['CUSUCOD'])) {
      if (!isset($this->paData['CUSUCOD']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO";
         return false;
      } // elseif (empty($this->paData['CCODUSU'])) {
      elseif (!isset($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CÓDIGO DE USUARIO SELECCIONADO NO DEFINIDO";
         return false;
      }
      return true;
   }   

   protected function mxDetalleUsuarioN($p_oSql) {  
      $lcSql =  "SELECT DISTINCT A.ccodusu, E.cnombre, F.cDescri,A.cnivel, E.cNroDni, D.cUniAca FROM S01TUSU A 
                     INNER JOIN B03DUSU B ON B.cCodUsu = A.cCodUsu
                     INNER JOIN B03TDOC C ON C.CIDCATE = B.CIDCATE 
                     INNER JOIN S01TUAC D ON D.CUNIACA = B.CUNIACA
                     INNER JOIN S01MPER E ON E.cNroDni = A.cNroDni 
                     LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '155' AND SUBSTRING (F.ccodigo,1,1)= A.cnivel
                     WHERE A.cCodUsu = '{$this->paData['CCODUSU']}'"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);
      $this->paData = ['CCODUSU' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CCARGO' => $laFila[2], 'CNIVEL' => $laFila[3],'CNRODNI' => $laFila[4], 'CUNIACA' => $laFila[5]];      
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR USUARIO";
         return false;
      }        
      $lcSql = "SELECT A.cCodigo, A.cEstado, A.cCenCos, B.cDescri 
               FROM B03PUSU A 
               INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
               WHERE A.cCodUsu = '{$this->paData['CCODUSU']}' ORDER BY A.cCodigo"; //WHERE A.cCodUsu = '{$this->paData['CCODUSU']}' AND B.cCencos <> '000' ORDER BY A.cCodigo"; 
      $R1 = $p_oSql->omExec($lcSql);           
      while ($laFila = $p_oSql->fetch($R1)) {         
         $this->paDatos['paUsuCen'][] = ['CCODIGO' => $laFila[0], 'CESTADO' => $laFila[1], 
                                         'CCENCOS' => $laFila[2], 'CDESCRI' => $laFila[3]];  
      }
      if (count($this->paDatos['paUsuCen']) == 0) {
         $this->pcError = "SIN CENTROS DE COSTO";
         return false;
      } 
      $lcSql = "SELECT A.nSerial, C.cNomUni, D.cDescri, C.cUniAca, A.cEstado  FROM B03DUSU A
               INNER JOIN S01TUAC C ON C.cUniAca = A.cUniAca 
               LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '155' AND SUBSTRING (D.ccodigo,1,1)= A.cnivel
               WHERE A.cCodUsu = '{$this->paData['CCODUSU']}' ORDER BY A.nSerial"; 
      $R1 = $p_oSql->omExec($lcSql);           
      while ($laFila = $p_oSql->fetch($R1)) {         
         $this->paDatos['paUsuNiv'][] = ['NSERIAL' => $laFila[0], 'CNOMUNI' => $laFila[1], 
                                         'CDESCRI' => $laFila[2], 'CUNIACA' => $laFila[3], 
                                         'CESTADO' => $laFila[4]];  
      }
      if (count($this->paDatos['paUsuNiv']) == 0) {
         $this->pcError = "SIN NIVELES";
         return false;
      } 
      return true;
   }
   // --------------------------------------------------------------------------
   // Asignar nuevo centro de costo a usuario
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omAsignarCentroCosto() {
      $llOk = $this->mxValParamAsignarCentroCosto();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAsignarCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;

   }

   protected function mxValParamAsignarCentroCosto() {
      //if (empty($this->paData['CCODUSU'])) {
      if (!isset($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CCENCOS'])) {
         $this->pcError = "UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
         //elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      } 
      return true;
   }   

   protected function mxAsignarCentroCosto($p_oSql) {  
      $lcSql = "SELECT cCenCos FROM B03PUSU WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cCenCos = '{$this->paData['CCENCOS']}';";    
      $R1= $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if ($laTmp[0] == $this->paData['CCENCOS']) {
         $this->pcError = 'YA SE AÑADIO ESTE CENTRO DE COSTO';
         return false;
      }
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' ";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR AL BUSCAR CENTRO DE COSTO";
         return false;
      }
      $lcSql = "SELECT cCodigo FROM B03PUSU ORDER BY cCodigo DESC LIMIT 1"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);
      $lcCodigo = (int)$laFila[0] + 1;
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR CODIGO";
         return false;
      }
      $lcSql = "INSERT INTO B03PUSU(cCodigo, cCenCos, cCodUsu, cEstado, cUsuCod, tModifi) 
                VALUES (LPAD('$lcCodigo', '6', '0'), '{$this->paData['CCENCOS']}', '{$this->paData['CCODUSU']}', 'A', '{$this->paData['CCODUSU']}', NOW())";
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL GRABAR CENTRO DE COSTO";
         return false;
      }      
      return true;
   }

   //NUEVOS NIVELES
   // --------------------------------------------------------------------------
   // Asignar nuevo centro de costo a usuario
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omAsignarNuevoNivel() {
      $llOk = $this->mxValParamAsignarNuevoNivel();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAsignarNuevoNivel($loSql);
      $loSql->omDisconnect();
      return $llOk;

   }

   protected function mxValParamAsignarNuevoNivel() {
      //if (empty($this->paData['CCODUSU'])) {
      if (!isset($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CCENCOS'])) {
         $this->pcError = "UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
      //elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      }
      return true;
   }   

   protected function mxAsignarNuevoNivel($p_oSql) {  
      $lcSql = "SELECT cCenCos FROM B03PUSU WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cCenCos = '{$this->paData['CCENCOS']}';";    
      $R1= $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if ($laTmp[0] == $this->paData['CCENCOS']) {
         $this->pcError = 'YA SE AÑADIO ESTE CENTRO DE COSTO';
         return false;
      }
      $lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' ";
      $R1 = $p_oSql->omExec($lcSql);     
      $laTmp = $p_oSql->fetch($R1);      
      if ($laTmp[0]=='0') {         
         $this->pcError = "ERROR AL BUSCAR CENTRO DE COSTO";
         return false;
      }
      $lcSql = "SELECT cCodigo FROM B03PUSU ORDER BY cCodigo DESC LIMIT 1"; 
      $R1 = $p_oSql->omExec($lcSql);     
      $laFila = $p_oSql->fetch($R1);         
      //$this->paData = $laFila[0];
      $lcCodigo = (int)$laFila[0]+1;
      if (!$laFila) {         
         $this->pcError = "ERROR AL BUSCAR CODIGO";
         return false;
      }  
      $lcSql = "INSERT INTO B03PUSU(cCodigo, cCenCos, cCodUsu, cEstado, cUsuCod, tModifi) 
                  VALUES (lpad('$lcCodigo','6','0') , '{$this->paData['CCENCOS']}', '{$this->paData['CCODUSU']}', 'A', '{$this->paData['CUSUCOD']}', NOW())";
      $llOk = $p_oSql->omExec($lcSql); 
      if (!$llOk) {         
         $this->pcError = "ERROR AL GRABAR CENTRO DE COSTO";
         return false;
      }        
   }

   // --------------------------------------------------------------------------
   // Asignar permisos a usuario seleccionado
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omAsignarPermisosUsuario() { 
      $llOk = $this->mxValParamAsignarPermisosUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxAsignarPermisosUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }   

   protected function mxValParamAsignarPermisosUsuario() {  
      //if (empty($this->paData['CCODUSU'])) {
      if (!isset($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      } elseif (strlen($this->paData['CNIVEL']) == 0) {
         $this->pcError = "NIVEL NO DEFINIDO";
         return false;
      } elseif (empty($this->paData['CUNIACA'])) {
         $this->pcError = "UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
      //elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO NO DEFINIDO";
         return false;
      } 
      return true;
   }   

   protected function mxAsignarPermisosUsuario($p_oSql) {  
      $lcNivel =  $this->paData['CNIVEL']; 
      $lcSql = "SELECT cNivel FROM B03DUSU WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cNivel = '$lcNivel';";    
      $R1= $p_oSql->omExec($lcSql);
      $laTmp = $p_oSql->fetch($R1);
      if ($laTmp[0] == $lcNivel) {
         $this->pcError = 'YA SE AÑADIO ESTE PERMISO';
         return false;
      }      
      if ($lcNivel=='0') {         //ADMINISTRADOR
         $lcSql = "INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod,tmodifi)
                     VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}', 'CCCOND', '$lcNivel','A','{$this->paData['CUSUCOD']}', NOW())"; 

         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - SECRETARIA";
            return false;
         }
      } elseif ($lcNivel=='1' OR $lcNivel=='2' OR $lcNivel=='3' OR $lcNivel=='J') {     //SECRETARIA || DIRECTOR || DECANO || JEFE DEPARTAMENTO   
         $lcSql = "INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod,tmodifi)
                     VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}', 'CCCOND', '$lcNivel','A','{$this->paData['CUSUCOD']}', NOW())"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - DECANO/JEFE DE DEPARTAMENTO/DIRECTOR/SECRETARIA";
            return false;
         }
      } elseif ($lcNivel=='4') { //COORDINADOR  DE LABORATORIOS     
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCCONL',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - COORDINADOR";
            return false;
         }      
      } elseif ($lcNivel=='5' OR $lcNivel=='L') { //BIBLIOTECA        
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCCONB',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - COORDINADOR BIBLIOTECA";
            return false;
         }
      } elseif ($lcNivel=='F') { //FIRMA DE DOCUMENTOS      
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCESTU',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; // NO SE CIDCATE
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - COORDINADOR BIBLIOTECA";
            return false;
         }
      } elseif ($lcNivel=='D'){ // IMAGEN Y DIGITALIZACION
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'PQ0001',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; //NO SE CIDCATE 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - DIGITALIZACIÓM DE IMAGEN";
            return false; 
         }
      } elseif ($lcNivel=='R') { //ORAA
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCESTU',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; //NO SE CIDCATE
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - PERSONAL TÉCNICO";
            return false;
         }
      } elseif ($lcNivel=='7') { //REGISTRO ACADEMICO - CONSTANCIAS
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCESTU',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; //NO SE CIDCATE
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - REGISTRO ACADÉMICO";
            return false;
         }
      } elseif ($lcNivel=='U') { //CURSOS COMPLEMENTARIOS
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CLCRNC',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; //NO SE CIDCATE
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - REGISTRO ACADÉMICO";
            return false;
         }
      } elseif ($lcNivel=='M' || $lcNivel=='E') { //SECRETARIA GENERAL || MESA DE PARTES
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCESTU',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; //NO SE CIDCATE
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - SECRETARIA GENERAL/MESA DE PARTES";
            return false;
         }
      } elseif ($lcNivel=='C' || $lcNivel=='V') { //VICERRECTORADO ACADEMICO/ADMINISTRATIVO
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  '000000',  '$lcNivel','A','{$this->paData['CUSUCOD']}')"; 
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - VICERRECTORADO ADMINISTRATIVO";
            return false;
         }
      } elseif ($lcNivel=='B') { //POSTGRADO
         $lcSql ="INSERT INTO B03DUSU (cCodUsu, cUniAca, cIdCate, cNivel, cEstado, cUsuCod)
                  VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}', 'CCESTU', '$lcNivel', 'A', '{$this->paData['CUSUCOD']}')";
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - POSTGRADO";
            return false;
         }
      } elseif ($lcNivel=='S' && $this->paData['CUNIACA'] == '21') { // INFORMATICA
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCCSUC',  '$lcNivel','A','{$this->paData['CUSUCOD']}')";
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - MESA DE PARTES";
            return false;
         }
      } elseif ($lcNivel=='S' && $this->paData['CUNIACA'] == '20') { //INSTITUTO IDIOMAS
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  'CCCSID',  '$lcNivel','A','{$this->paData['CUSUCOD']}')";
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - MESA DE PARTES";
            return false;
         }
      } elseif ($lcNivel=='S' && $this->paData['CUNIACA'] == '24') { //INSTITUTO CONFUCIO
         $lcSql ="INSERT INTO B03DUSU(cCodUsu, cuniaca, cidcate, cnivel, cestado, cusucod)
                   VALUES ('{$this->paData['CCODUSU']}', '{$this->paData['CUNIACA']}' ,  '000084',  '$lcNivel','A','{$this->paData['CUSUCOD']}')";
         $llOk = $p_oSql->omExec($lcSql); 
         if (!$llOk) {         
            $this->pcError = "ERROR AL GRABAR USUARIO - MESA DE PARTES";
            return false;
         }
      } else {
         $this->pcError = "ESPECIFICACIONES DE NIVEL NO ESTABLECIDAS";
         return false;
      }
      return true;
   }
/*
   // --------------------------------------------------------------------------
   // Actualizar estado de Usuario del B03PUSU
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------
   public function omActualizarEstadoUsuario() {  
      $llOk = $this->mxValParamActualizarEstadoUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarEstadoUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamActualizarEstadoUsuario() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CESTADO'])) {
         $this->pcError = "ESTADO DE PAQUETE NO DEFINIDO";
         return false;
      }elseif (empty($this->paData['CUSUCOD'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;
      }
      return true;
   }      
   protected function mxActualizarEstadoUsuario($p_oSql) {        
      $lcCodUsu = $this->paData['CCODUSU'];      
      $lcSql = "UPDATE B03DUSU SET cEstado = '{$this->paData['CESTADO']}', cUsuCod ='{$this->paData['CUSUCOD']}', tmodifi=NOW()
                   WHERE nSerial='{$this->paData['NSERIAL']}'";   //OJO      
     print_r($lcSql);
     $llOk = $p_oSql->omExec($lcSql);
     if (!$llOk) {
         $this->pcError = 'ERROR AL ACTUALIZAR USUARIO';
         return false;
      }      
      return true;
   }  */
   // --------------------------------------------------------------------------
   // Actualiza permisos a nivel seleccionado
   // 2019-02-05 LVA Creacion
   // --------------------------------------------------------------------------

   public function omActualizarPermisoUsuario() {  
      $llOk = $this->mxValParamActualizarPermisoUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarPermisoUsuario($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValParamActualizarPermisoUsuario() {
      //if (empty($this->paData['CCODUSU'])) {
      if (empty($this->paData['CCODUSU']) || !preg_match("(^[a-zA-Z0-9]+$)", $this->paData['CCODUSU']) || strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;      
      }
      return true;
   }      
   protected function mxActualizarPermisoUsuario($p_oSql) {  
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.nSerial FROM B03DUSU A 
               WHERE cCodUsu = '$lcCodUsu' ORDER BY A.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laTmp = $p_oSql->fetch($R1)) {         
         $lnSerial = $laTmp[0];
         $lcEstado =$this->paEstado[$i];
         $lcSql = "UPDATE B03DUSU SET cestado = '$lcEstado', tmodifi = NOW() 
                   WHERE nSerial = '$lnSerial';";
         $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
            $this->pcError = 'ERROR AL ACTUALIZAR PERMISOS';
            return false;
         }
         $i++;     
      }   
      return true;
   }  
   
   public function omActualizarCentroCostoUsuario() {  
      $llOk = $this->mxValParamActualizarCentroCostoUsuario();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActualizarCentroCostoUsuario($loSql);
      if (!$llOk) {
         $loSql->rollback();
      }
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamActualizarCentroCostoUsuario() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = "CODIGO DE USUARIO NO DEFINIDO";
         return false;      
      }
      return true;
   }

   protected function mxActualizarCentroCostoUsuario($p_oSql) {
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cCodigo FROM B03PUSU A                
      WHERE cCodUsu = '$lcCodUsu' ORDER BY A.cCodigo";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laTmp = $p_oSql->fetch($R1)) {         
         $lcCodigo = $laTmp[0];
         $lcEstado =$this->paEstado[$i];
         $lcSql = "UPDATE B03PUSU SET cestado = '$lcEstado', tmodifi=NOW()
                   WHERE cCodigo='$lcCodigo';";
         $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
            $this->pcError = 'ERROR AL ACTUALIZAR PERMISOS';
            return false;
         }     
         $i++;
      }   
      return true;
   }  
}
?>