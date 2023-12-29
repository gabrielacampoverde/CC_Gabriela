<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CWebService{

   public $paData, $pcData, $pcError, $pcFile;
   protected $lcPostData;

   public function __construct() {
      $this->paData = null;
   }
   
   public function omValidarNivelUsuario() {
      if ($this->paData['CNIVEL'] != 'AA') {
         $this->pcError = 'ACCESO RESTRINGIDO';
         return false;
      }
      return true;
   }
   
   protected function mxJsonEncode() {
      $this->lcPostData = json_encode($this->paData);
      return true;
   }

   protected function mxValError() {
      if (isset($this->paData['ERROR'])) {
         $this->pcError = $this->paData['ERROR'];
         return false;
      }
      return true;
   }

   public function omConsultarDNIMesaPartes() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarDNIMesaPartes', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }
   
   public function omActivarDNIMesaPartes() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsActivarDNIMesaPartes', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   public function omDesactivarDNIMesaPartes() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsDesactivarDNIMesaPartes', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }
   
   public function omConsultarPersonalNombre() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarPersonalNombre', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return true;
   }
   
   public function omAgruparDNIs() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsAgruparDNIs', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return true;
   }

   public function omAplicarActualizacionDNI() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => ['method' => 'POST', 'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsAplicarActualizacionDNI', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }
   
   public function omActualizarDNI() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => ['method' => 'POST', 'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsActualizarDNI', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   public function omInitValPagosOCRRII() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      echo '<br>'.$this->lcPostData.'<br>';
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsInitValPagosOCRRII', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paDatos = json_decode($lcData, true);
      return $this->mxValError();
   }
   
   public function omBandejaContabPagosOCRRII() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsBandejaContabPagosOCRRII', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;

      }
      return $this->mxValError();
   }

   public function omCargarRevisionContabPagosOCRRII() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsCargarRevisionContabPagosOCRRII', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $laData = json_decode($lcData, true);
      $this->paData = ['CIDCOMP'=> $laData['CIDCOMP'], 'CPERIOD'=> $laData['CPERIOD'], 'CDESTIP'=> $laData['CDESTIP'], 'CDESEST'=> $laData['CDESEST'], 'CDESREV'=> $laData['CDESREV'], 'CESTADO'=> $laData['CESTADO'], 'CTIPO'=> $laData['CTIPO']];
      $this->paDatos = $laData['DATA'];
      return $this->mxValError();
   }

   public function omGrabarRevisionOCRRII() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGrabarRevisionOCRRII', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $laData = json_decode($lcData, true);
      $this->paData = ['CIDCOMP'=> $laData['CIDCOMP'], 'CPERIOD'=> $laData['CPERIOD'], 'CDESTIP'=> $laData['CDESTIP'], 'CDESEST'=> $laData['CDESEST'], 'CDESREV'=> $laData['CDESREV'], 'CESTADO'=> $laData['CESTADO'], 'CTIPO'=> $laData['CTIPO']];
      $this->paDatos = $laData['DATA'];
      return $this->mxValError();
   }

   public function omReporteAsientosOCRRII() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsReporteAsientosOCRRII', false, $laContext);
      print_r($lcData);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->pcFile = 'FILES/'.$laData['CREPORT'];
      return $this->mxValError();
   }
   
   public function omReportComprobantesOCRRII(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsReportComprobantesOCRRII', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->pcFile = 'FILES/'.$laData['CREPORT'];
      return $this->mxValError();
   }

   public function omInitReporteContabPagosOCRRII(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsInitReporteContabPagosOCRRII', false, $laContext);
      $this->pcFile = 'FILES/'.$laData['CREPORT'];
      return $this->mxValError();
   }

   public function omInitComprobantesOCRRII(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsInitComprobantesOCRRII', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      return $this->mxValError();
   }

   public function omCargarComprobantesOCRRII(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsCargarComprobantesOCRRII', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      return $this->mxValError();
   }
   public function omGrabarComprobantesOCRRII(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGrabarComprobantesOCRRII', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      return $this->mxValError();
   }
   public function omContabilizarPagosOCRRII(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      
      $lcData = file_get_contents('http://localhost:8082/wsContabilizarPagosOCRRII', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      return $this->mxValError();
   }

   public function omConsultarCxP(){
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      
      $lcData = file_get_contents('http://localhost:8082/wsConsultarCxP', false, $laContext);
      $this->paDatos = json_decode($lcData, true);


      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   public function omSeguimientoComprobantes() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      //echo '<br>'.$this->lcPostData.'<br>';
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsSeguimientoComprobantesOCRRII', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $laData = json_decode($lcData, true);
      if (isset($laData['ADATOS'])) {
         $this->paDatos = $laData['ADATOS'];
         unset($laData['ADATOS']); 
         $this->paData = $laData;
      } else {
         $this->paDatos = $laData;
      }
      return $this->mxValError();
   }

   public function omEstadoFinancieroBG() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsEstadoFinancieroBG', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }
   
   public function omInitContabilidadLibro() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsInitContabilidadLibro', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      return $this->mxValError();
   }

   public function omBuscarAsientos() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsBuscarAsientos', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }
   
   public function omCargarDetalleAsientoCnt() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsCargarDetalleAsientoCnt', false, $laContext);
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->paData = $laData['DATA'];
      $this->paDatos = $laData['DATOS'];
      return $this->mxValError();
   }
   
   public function omBuscarCuentaContable() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsBuscarCuentaContable', false, $laContext);
      $this->paData = json_decode($lcData, true);
      if (isset($paData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   public function omGrabarAsiento() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGrabarAsiento', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($paDatos['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   public function omTraerOrigenAsientos() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsTraerOrigenAsientos', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($paDatos['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   public function omEstadoResultados() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsEstadoResultados', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   public function omBalanceComprobacion() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsBalanceComprobacion', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   public function omResultadosNaturaleza() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsResultadosNaturaleza', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------------------------
   # Reporte 10 Columnas BALANCE DE COMPROBACION
   # 2019-10-22 FLC Creacion
   # ------------------------------------------------------------------------------
   public function omReporteTenCol() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsReporteTenCol', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------------------------
   # Reporte deAsientos Contables
   # 2019-10-22 FLC Creacion
   # ------------------------------------------------------------------------------
   public function omReporteAsientoContable() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsReporteAsientoContable', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------------------------
   # Reporte Excel Flujo Caja
   # 2019-10-22 FLC Creacion
   # ------------------------------------------------------------------------------
   public function omReporteFlujoCaja() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsReporteFlujoCaja', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------------------------
   # GENERACION DE ASIENTOS DE COMPRA
   # 2019-12-11 FLC Creacion
   # ------------------------------------------------------------------------------
   public function omGeneracionAsientosCompra() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGeneracionAsientosCompra', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------
   # Contabilizar OLs 
   # 2019-12-10 FPM Creacion
   # ------------------------------------------------------------
   public function omContabilizarOL() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsContabilizarOL', false, $laContext);
      $laData = json_decode($lcData, true);
      if (isset($laData['ERROR'])) {
         $this->pcError = $laData['ERROR'];
         return false;
      }
      $this->paData = $laData['DATA'];
      $this->paDatos = $laData['DATOS'];
      return $this->mxValError();
   }

   # ------------------------------------------------------------
   # Cuenta de Retencion 
   # 2019-12-15 FPM Creacion
   # ------------------------------------------------------------
   public function omCodigoRetencion() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsCodigoRetencion', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------
   # Grabar Asientos de Compra 
   # 2019-12-15 FPM Creacion
   # ------------------------------------------------------------
   public function omGrabarAsientoCO() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGrabarAsientoCO', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   # ------------------------------------------------------------
   # Porcentaje Detraccion
   # 2020-01-06 FPM Creacion
   # ------------------------------------------------------------
   public function omPorcentajeDetraccion() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsPorcentajeDetraccion', false, $laContext);
      $this->paDatos = json_decode($lcData, true);
      if (isset($this->paDatos['ERROR'])) {
         $this->pcError = $this->paDatos['ERROR'];
         return false;
      }
      return $this->mxValError();
   }

   //**************************************************
   // Generar de Deuda Admision
   // 2020-02-04 FLC CREACION
   //**************************************************
   public function omGenerarDeudaAdmision() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGenerarDeudaAdmision', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Consultar deuda de Admision
   // 2020-02-04 FLC CREACION
   //**************************************************
   public function omConsultarDeudaAdmision() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarDeudaAdmision', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Generar de Deuda Precatolica
   // 2020-09-01 JLF Creacion
   //**************************************************
   public function omGenerarDeudaPrecatolica() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGenerarDeudaPrecatolica', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Consultar deuda de Precatolica
   // 2020-09-01 JLF Creacion
   //**************************************************
   public function omConsultarDeudaPrecatolica() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarDeudaPrecatolica', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Anular deuda de Precatolica
   // 2021-09-21 JLF Creacion
   //**************************************************
   public function omAnularDeudaPrecatolica() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsAnularDeudaPrecatolica', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Generar Deuda por Venta
   // 2021-02-16 JLF Creacion
   //**************************************************
   public function omGenerarDeudaVenta() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGenerarDeudaVenta', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Consultar Pago Deuda por Venta
   // 2021-02-16 JLF Creacion
   //**************************************************
   public function omConsultarDeudaVenta() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarDeudaVenta', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Anular Deuda Admision
   // 2021-04-06 JLF Creacion
   //**************************************************
   public function omAnularDeudaAdmision() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsAnularDeudaAdmision', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Generar Deuda Evento Académico
   // 2021-09-21 JLF Creacion
   //**************************************************
   public function omGenerarDeudaEventoAcademico() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsGenerarDeudaEventoAcademico', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Consultar Deuda de Evento Académico
   // 2021-09-21 JLF Creacion
   //**************************************************
   public function omConsultarDeudaEventoAcademico() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarDeudaEventoAcademico', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Anular Deuda de Evento Académico
   // 2021-09-21 JLF Creacion
   //**************************************************
   public function omAnularDeudaEventoAcademico() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsAnularDeudaEventoAcademico', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // Consultar Deudas Provisionales x DNI (Código 12)
   // 2021-09-21 JLF Creacion
   //**************************************************
   public function omConsultarDeudasProvisionalesxDNI() {
      $llOk = $this->mxJsonEncode();
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsConsultarDeudasProvisionalesxDNI', false, $laContext);
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   //**************************************************
   // RETORNA PATH DE FOTOGRAFIA CARGADA DE ALUMNO
   // 2020-07-05 FLC CREACION
   //**************************************************
   public function omCargarFotografiaAlumno() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPost)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPost]
      ));
      //$lcData = '{"OK":"OK", "CPATH":"./Images/user.png"}';
      $lcData = file_get_contents('http://localhost:8082/wsCargarFotografiaAlumno', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE CARGAR RUTA DE FOTOGRAFIA'; 
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO INVALIDO";
         return false;
      }
      return $this->mxValError();
   }
   
   //**************************************************
   // Graba datos del usuario en metadata del documento
   // 2020-07-05 LVA CREACION
   //**************************************************
   public function omFirmarContrato() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsFirmarContrato', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE ACCEDER AL SERVICIO WEB';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      return $this->mxValError();
   }

   
   //SERVICIO QUE RETORNA LA CARGA ACADEMICA
   //WZA 17-05-2021
   public function omObtenerCargaAcademicaInformatica() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('https://academico.ucsm.edu.pe:444/MatriculaService/MatriculaService.svc/ObtenerCargaAcademica', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE OBTENER CARGA ACADEMICA';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO POR SERVIDOR INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }

   public function omObtenerEquivalencias() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('https://academico.ucsm.edu.pe:444/MatriculaService/MatriculaService.svc/ObtenerEquivalencias', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE OBTENER EQUIVALENCIAS';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO POR SERVIDOR INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }

   public function omObtenerPreRequisito() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('https://academico.ucsm.edu.pe:444/MatriculaService/MatriculaService.svc/ObtenerPreRequisito', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE OBTENER EQUIVALENCIAS';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO POR SERVIDOR INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }
   // VERIFICA EL AÑO DE INGRESO Y EGRESO DEL ESTUDIANTE
   // 2021-05-07 APR Creación
   public function omVerificarIngresoYEgresoEstudiante() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPost)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPost]
      ));
      $lcData = file_get_contents('https://academico.ucsm.edu.pe:444/MatriculaService/MatriculaService.svc/ObtenerFechaEgreso', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE VERIFICAR EGRESO DE ESTUDIANTE';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO POR SERVIDOR INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }

   //SERVICIO QUE RETORNA LAS DECLARACIONES JURADAS
   //WZA 13-07-2021
   public function omObtenerDeclaracionesJuradasInformatica() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('https://academico.ucsm.edu.pe:444/MatriculaService/MatriculaService.svc/ObtenerDeclaracionJurada', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE OBTENER DECLARACIONES JURADAS';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO POR SERVIDOR INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }

   // REVISA CURSOS DE UN ALUMNO PARA JURADO
   // 2019-06-21 JLF Creación
   public function omRevisarCursosJurado() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('http://localhost:8082/wsRevisarCursosJurado', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE CARGAR CURSOS PARA JURADO';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO INVALIDO";
         return false;
      }
      return $this->mxValError();
   }

   // REVISA TOKEN DE INICIO DE SESIÓN DE SISTEMA INTEGRADO
   // 2021-11-04 JLF Creación
   public function omRevisarTokenInicioSesion() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Authorization: Bearer ".$this->paData['token']."\r\n".
                        "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      //$lcData = '{"ERROR":"ERROR AL VALIDAR WEB SERVICE DE INICIO DE SESIÓN"}';
      $lcData = file_get_contents('https://gateway.ucsm.edu.pe:44395/api/Usuario/GetUsersByIdToken', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE VERIFICAR TOKEN DE INICIO DE SESIÓN, CIERRE LA VENTANA, REGRESE AL MENÚ PRINCIPAL Y VUELVA A INGRESAR';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR RETORNADO PARA VERIFICACIÓN DE TOKEN INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }

   # REVISA SI CÓDIGO DE ESTUDIANTE TIENE MATRÍCULAS ANULADAS
   # 2021-12-07 APR Creación
   public function omRevisarAnulacionesDeMatricula() {
      $llOk = $this->mxJsonEncode();
      if (!$llOk) {
         return false;
      }
      // Create the context for the request
      $laContext = stream_context_create(array(
         'http' => [
            'header' => "Content-Type: application/json\r\n".
                        "Content-Length: ".strlen($this->lcPostData)."\r\n",
            'method' => 'POST', 
            'content' => $this->lcPostData]
      ));
      $lcData = file_get_contents('https://academico.ucsm.edu.pe:444/MatriculaService/MatriculaService.svc/ObtenerMatriculaAnulada', false, $laContext);
      if (!$lcData) {
         $this->pcError = 'NO SE PUEDE OBTENER ANULACIONES DE MATRÍCULA';
         return false;
      }
      $this->paData = json_decode($lcData, true);
      if ($this->paData == null) {
         $this->pcError = "VALOR DEVUELTO POR SERVIDOR INVÁLIDO";
         return false;
      }
      return $this->mxValError();
   }
}
?>
