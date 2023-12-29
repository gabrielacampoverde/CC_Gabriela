<?php
require_once 'class/PHPMailer/PHPMailer.php';
require_once 'class/PHPMailer/Exception.php';
require_once 'class/PHPMailer/OAuth.php';
require_once 'class/PHPMailer/POP3.php';
require_once 'class/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class CEmail {
   protected $php_mailer, $lcUser, $lcPass, $lcCopia, $lcRutAdj, $lcDocAdj;
   public $paData, $pcError;

   public function __construct() {
      $this->php_mailer          = null;
      $this->paData              = null;
      $this->pcError             = null;
      $this->lcUser              = 'soportetramites@ucsm.edu.pe';
      $this->lcPass              = 'Inglorious$2019';
      $this->lcCopia             = null;
      $this->lcCopiaAlumno       = null;
      $this->lcSegundaCopia      = null;
      $this->lcTerceraCopia      = null;
      $this->lcCopiaInformatica  = null;
      $this->lcCopiaInformatica1 = null;
      $this->lcRutAdj            = null;
      $this->lcDocAdj            = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $llOk = $this->mxValParamIngresarOrigen();
      if (!$llOk) {
         return false;
      }
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }

   protected function mxValParamIngresarOrigen() {
      return true;
   }

   public function omAñadirDestinosCopia($p_cCopia) {
      $llOk = $this->mxValParamIngresarCopia();
      if (!$llOk) {
         return false;
      }
      $this->lcCopia = $p_cCopia;
      return true;
   }

   protected function mxValParamIngresarCopia() {
      return true;
   }

   public function omAñadirDestinosCopiaAlumno($p_cCopiaAlumno) {
      $llOk = $this->mxValParamIngresarCopiaAlumno();
      if (!$llOk) {
         return false;
      }
      $this->lcCopiaAlumno = $p_cCopiaAlumno;
      return true;
   }

   protected function mxValParamIngresarCopiaAlumno() {
      return true;
   }

   public function omAñadirDestinoSegundaCopia($p_cSegundaCopia) {
      $llOk = $this->mxValParamIngresarSegundaCopia();
      if (!$llOk) {
         return false;
      }
      $this->lcSegundaCopia = $p_cSegundaCopia;
      return true;
   }

   protected function mxValParamIngresarSegundaCopia() {
      return true;
   }

   public function omAñadirDestinoTerceraCopia($p_cTerceraCopia) {
      $llOk = $this->mxValParamIngresarTerceraCopia();
      if (!$llOk) {
         return false;
      }
      $this->lcTerceraCopia = $p_cTerceraCopia;
      return true;
   }

   protected function mxValParamIngresarTerceraCopia() {
      return true;
   }

   public function omAñadirDestinoCopiaJefeInformatica($p_cCopiaJInformatica) {
      $llOk = $this->mxValParamIngresarCopiaJefeInformatica();
      if (!$llOk) {
         return false;
      }
      $this->lcCopiaInformatica = $p_cCopiaJInformatica;
      return true;
   }

   protected function mxValParamIngresarCopiaJefeInformatica() {
      return true;
   }

   public function omAñadirDestinoCopiaInformatica($p_cCopiaJInformatica1) {
      $llOk = $this->mxValParamIngresarCopiaInformatica();
      if (!$llOk) {
         return false;
      }
      $this->lcCopiaInformatica1 = $p_cCopiaJInformatica1;
      return true;
   }

   protected function mxValParamIngresarCopiaInformatica() {
      return true;
   }

   public function omAñadirDocumento($p_RutAdj, $p_DocAdj) {
      $llOk = $this->mxValParamAñadirDocumento();
      if (!$llOk) {
         return false;
      }
      $this->lcRutAdj = $p_RutAdj;
      $this->lcDocAdj = $p_DocAdj;
      return true;
   }

   protected function mxValParamAñadirDocumento() {
      return true;
   }

   public function omConnect() {
      $this->php_mailer = new PHPMailer();
      $this->php_mailer->IsSMTP(); // enable SMTP
      $this->php_mailer->CharSet = 'UTF-8';
      //$this->php_mailer->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
      $this->php_mailer->SMTPAuth = true; // authentication enabled
      $this->php_mailer->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
      //$this->php_mailer->Host = "smtp.gmail.com";
      $this->php_mailer->Host = 'smtp.office365.com';
      $this->php_mailer->Port = 465; // or 587
      $this->php_mailer->IsHTML(true);
      /*$this->php_mailer->Username = "efb.devs@gmail.com";
      $this->php_mailer->Password = "SistemasFPM";*/
      $this->php_mailer->Username = $this->lcUser;
      $this->php_mailer->Password = $this->lcPass;  
      $this->php_mailer->SMTPSecure = 'tls';
      $this->php_mailer->Port = 25;
      $this->php_mailer->SetFrom($this->lcUser);
      
      //PARA PROBAR LOCALMENE EN XAMPP
      $this->php_mailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );

      $this->php_mailer->addAttachment($this->lcRutAdj, $this->lcDocAdj,  'base64', 'application/pdf');
      $this->php_mailer->addCC($this->lcCopia);
      $this->php_mailer->addCC($this->lcCopiaAlumno);
      $this->php_mailer->addCC($this->lcSegundaCopia);
      $this->php_mailer->addBCC($this->lcTerceraCopia);
      $this->php_mailer->addBCC($this->lcCopiaInformatica);
      $this->php_mailer->addBCC($this->lcCopiaInformatica1);
      return true;
   }

   public function omSend() {
      $llOk = $this->mxValParamSend();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinos();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = $this->paData['CSUBJEC'];
      $this->php_mailer->Body .= $this->paData['CBODY'];
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSend() {
      if (!isset($this->paData['CSUBJEC']) || empty($this->paData['CSUBJEC'])) {
         $this->pcError = "ASUNTO DEL EMAIL NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['AEMAILS']) || count($this->paData['AEMAILS']) == 0) {
         $this->pcError = "EMAIL DESTINO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CBODY']) || empty($this->paData['CBODY'])) {
         $this->pcError = "CUERPO DEL EMAIL NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinos() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }

   public function omSendBachillerato() {
      $llOk = $this->mxValParamSendBachillerato();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinosBachillerato();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = $this->paData['CSUBJEC'];
      $this->php_mailer->AddEmbeddedImage('Images/Colacion.png', 'imagenColacion');
      $this->php_mailer->Body = $this->paData['CBODY'];
      $this->php_mailer->Body .= '<div><img src="cid:imagenColacion" height="580" class="center"></div><br>';
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSendBachillerato() {
      if (!isset($this->paData['CSUBJEC']) || empty($this->paData['CSUBJEC'])) {
         $this->pcError = "ASUNTO DEL EMAIL NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['AEMAILS']) || count($this->paData['AEMAILS']) == 0) {
         $this->pcError = "EMAIL DESTINO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CBODY']) || empty($this->paData['CBODY'])) {
         $this->pcError = "CUERPO DEL EMAIL NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinosBachillerato() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }

   public function omSendTitulacion() {
      $llOk = $this->mxValParamSendTitulacion();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinosTitulacion();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = $this->paData['CSUBJEC'];
      $this->php_mailer->AddEmbeddedImage('Images/Colacion.png', 'imagenColacion');
      $this->php_mailer->Body = $this->paData['CBODY'];
      $this->php_mailer->Body .= '<div><img src="cid:imagenColacion" height="580" class="center"></div><br>';
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSendTitulacion() {
      if (!isset($this->paData['CSUBJEC']) || empty($this->paData['CSUBJEC'])) {
         $this->pcError = "ASUNTO DEL EMAIL NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['AEMAILS']) || count($this->paData['AEMAILS']) == 0) {
         $this->pcError = "EMAIL DESTINO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CBODY']) || empty($this->paData['CBODY'])) {
         $this->pcError = "CUERPO DEL EMAIL NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinosTitulacion() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }



   public function omSendCETraduccion() {
      $llOk = $this->mxValParamSendCETraduccion();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinosCETraduccion();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = 'ARCHIVO DE TRADUCCIÓN';
      $this->php_mailer->AddEmbeddedImage('Images/ucsm-03.png', 'logo');
      $this->php_mailer->Body = '<div><img src="cid:logo" height="80"></div><br>';
      $this->php_mailer->Body .= 'ADJUNTO ARCHIVO DE TRADUCCIÓN';
      $this->php_mailer->AddAttachment($this->paData['CARCHIV'],$this->paData['CARCHNM']);
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSendCETraduccion() {
      if (!isset($this->paData['CARCHIV']) || empty($this->paData['CARCHIV'])) {
         $this->pcError = "ARCHIVO NO ADJUNTADO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinosCETraduccion() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }
}

function fxValEmail($p_cEmail) {
   return true;
}

// ----------------------------------------------------------------
// ENVIO DE CONFIRMACION DE EXPEDIENTE COMPLETO ADMISION POSTGRADO
// CREACION APR 2021-01-31
// ----------------------------------------------------------------
class CEmailAdmisionPostgrado {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $lcUser   = 'admisionepg@ucsm.edu.pe';
      $lcClave  = 'Postgrado$1234';      
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->pcRutAdj = null;
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $lcUser;
      $this->loMailer->Password = $lcClave; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
      $this->lcBody = "<!DOCTYPE html>
                  <html>
                  <body>                   
                     <table>
                        <colgroup>
                           <col style='background-color: #ececec'>
                           <col style='background-color: #ffffff;'>
                        </colgroup>   
                        <thead>
                           <tr style='background-color: #ffffff; font-weight: 500; color:black'>
                              <th colspan='2'>
                                 <div class='container'>
                                    UNIVERSIDAD CATÓLICA DE SANTA MARÍA<br>
                                    CONCURSO DE ADMISIÓN 2021<br>
                                    MAESTRÍAS Y DOCTORADOS
                                 </div>
                                 <br>
                              </th>
                           </tr>
                           <tr style='background-color: #ffffff; color:black; text-align: justify'>
                              <th colspan='2'>
                              Estimado Postulante: <br><br>
                              Gracias por confiar en la Escuela de Postgrado de la Universidad Católica de Santa, 
                              se le informa que su expediente ha sido recepcionado y en caso de tener alguna observación se le comunicará a su correo electrónico.
                              <br>Este correo electrónico se genera automaticamente, no responder.
                              </th>
                           </tr>
                        </thead>
                     </table>
                  </body>
                  </html>";
   }
   public function omSend() {
      $laEmail = $this->paData['AEMAIL'];
      if ($laEmail != '' and !fxValEmail($laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
      }
      $this->loMailer->Subject = 'CONCURSO DE ADMISIÓN 2021 MAESTRÍAS Y DOCTORADOS - UCSM';
      $this->loMailer->Body = $this->lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

// ---------------------------------------------
// ENVIAR CARGO DE SOLICITUD DE MESA DE PARTES
// CREACION APR 2021-01-31
// ---------------------------------------------
class CEmailMesaDePartes {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'mesapartes@ucsm.edu.pe';
      $this->lcPass   = 'MESADEpartes1234'; 
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->pcRutAdj = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }
   
   public function omConnect() {      
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
   }

   public function omSend() {
      $laEmail  = $this->paData['AEMAIL'];
      $lcRutAdj = $this->paData['CRUTDOC'];
      $lcDocAdj = $this->paData['CDOCADJ'];
      $lcCopia  = $this->paData['CECOPIA'];
      if ($laEmail != '' and !fxValEmail($laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
      }
      $this->loMailer->addCC($lcCopia);
      $this->loMailer->addAttachment($lcRutAdj, $lcDocAdj,  'base64', 'application/pdf');
      $this->loMailer->Subject = 'MESA DE PARTES VIRTUAL UCSM - SOLICITUDES ESPECIALES';
      $this->loMailer->Body = $this->paData['CBODY'];
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

// --------------------------------------------------
// ENVIAR CARGO DE SOLICITUD DE DEVOLUCIÓN DENEGADA
// CREACION APR 2021-01-31
// --------------------------------------------------
class CEmailDevoluciones {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->pcRutAdj = null;
   }
   
   public function omConnect() {      
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );

      $this->lcBody = "<!DOCTYPE html>
                     <html>
                     <body>                   
                        <table>
                           <colgroup>
                              <col style='background-color: #ececec'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>   
                           <thead>
                              <tr style='background-color: #ffffff; font-weight: 500; color:black'>
                                 <th colspan='2'>
                                    <div class='container'>
                                       UNIVERSIDAD CATÓLICA DE SANTA MARÍA<br>
                                       DEVOLUCIÓN DE DINERO 2021<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='2'>
                                 Estimado Interesado: <br><br>
                                 Se le remite el siguiente correo electrónico ya que su Solicitud de Devolución de Dinero fue Denegada. El motivo esta especificado en el documento adjunto.
                                 <br>Este correo electrónico se genera automaticamente, no responder.
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
   }

   public function omSend() {
      $laEmail  = $this->paData['AEMAIL'];
      $lcRutAdj = $this->paData['CRUTDOC'];
      $lcDocAdj = $this->paData['CDOCADJ'];
      if ($laEmail != '' and !fxValEmail($laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
      }
      $this->loMailer->addAttachment($lcRutAdj, $lcDocAdj,  'base64', 'application/pdf');
      $this->loMailer->Subject = 'SOLICITUD DE DEVOLUCIÓN DE DINERO UCSM';
      $this->loMailer->Body = $this->lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

// -------------------------------------------
// ENVIAR CARGO DE SOLICITUD DE MATRICULADOS 
// CREACION APR 2021-01-31
// -------------------------------------------
class CEmailMatri {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );

      $this->lcBody = "<!DOCTYPE html>
                     <html>
                     <body>                   
                        <table>
                           <colgroup>
                              <col style='background-color: #ececec'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>   
                           <thead>
                              <tr style='background-color: #ffffff; font-weight: 500; color:black'>
                                 <th colspan='2'>
                                    <div class='container'>
                                       UNIVERSIDAD CATÓLICA DE SANTA MARÍA<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='2'>
                                    <strong>Estimado(a) Bachiller: </strong><br>
                                    Te recordamos que el programa de actualización para titularte que estás siguiendo, tiene como plazo máximo el mes de octubre del presente año el inicio del trámite de titulación.<br><br>
Por lo que te recordamos realices el pago de la segunda cuota e inicies lo más pronto posible el trámite de titulación.<br><br>
Cualquier consulta puedes hacerla al 987-060-459, 959-344-202 ó 997-972-718.<br><br>
Si ya pagaste las cuotas, iniciaste tu trámite de titulación o te titulaste, por favor, omitir este mensaje.<br><br>
La Cato piensa en ti.<br><br>
Arequipa, octubre del 2022.<br><br>
                                    Este correo electrónico se genera y envia automaticamente, no responder este correo.
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
   }

   public function omSend() {
      $lcCorreo = $this->paData['AEMAIL'];
      $lcCopia = $this->paData['CCOPIA'];
      $this->loMailer->AddAddress($lcCorreo);
      $this->loMailer->addCC($lcCopia);
      # PARA ADJUNTAR DOCUMENTOS ADJUNTOS
      #$this->loMailer->addAttachment('C:\xampp\htdocs\UCSMMTA\EXP\D70840304\COMUNICADO.pdf', 'COMUNICADO CREDITO EDUCATIVO.pdf',  'base64', 'application/pdf');
      $this->loMailer->Subject = 'PROGRAMA DE CURSO DE ACTUALIZACIÓN';
      $this->loMailer->Body = $this->lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

// --------------------------------------
// ENVIAR CORREO DE DIA DE SUSTANTACIÓN
// CREACION APR 2021-04-22
// --------------------------------------
class CEmailDiaDeSustentacion {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019';
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->pcRutAdj = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }
   
   public function omConnect() {      
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
   }

   public function omSend() {
      $lcAlumno1 = $this->paData['AEMAIL'][0]['CEMAIL'];
      $lcAlumno2 = $this->paData['AEMAIL'][1]['CEMAIL'];
      $lcAlumno3 = $this->paData['AEMAIL'][2]['CEMAIL'];
      $lcJurado1 = $this->paData['CECOPIA'][0]['CEMAIL'];
      $lcJurado2 = $this->paData['CECOPIA'][1]['CEMAIL'];
      $lcJurado3 = $this->paData['CECOPIA'][2]['CEMAIL'];
      $lcJurado4 = $this->paData['CECOPIA'][3]['CEMAIL'];
      $this->loMailer->addCC($lcAlumno1);
      $this->loMailer->addCC($lcAlumno2);
      $this->loMailer->addCC($lcAlumno3);
      $this->loMailer->addBCC($lcJurado1);
      $this->loMailer->addBCC($lcJurado2);
      $this->loMailer->addBCC($lcJurado3);
      $this->loMailer->addBCC($lcJurado4);
      $this->loMailer->Subject = 'SVUCSM - DÍA DE SUSTENTACIÓN VIRTUAL';
      $this->loMailer->Body = $this->paData['CBODY'];
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

// ---------------------------------------------
// ENVIAR CORREO OBSERVACION DE CONTRATOS FIRMADOS POR DOCENTE
// CREACION WZA 2021-08-11
// ---------------------------------------------
class CEmailContratosCarga {
    protected $loMailer, $lcBody;
    public $paData, $pcError;
 
    public function __construct() {
       $this->lcUser   = 'contratos.rrhh@ucsm.edu.pe';
       $this->lcPass   = 'Clave$1234'; 
       $this->paData   = null;
       $this->pcError  = null;
       $this->paEmail  = null;
       $this->pcRutAdj = null;
    }
 
    public function omIngresarOrigen($p_cUser, $p_cPass) {
       $this->lcUser = $p_cUser;
       $this->lcPass = $p_cPass;
       return true;
    }
    
    public function omConnect() {      
       $this->loMailer = new PHPMailer();
       $this->loMailer->IsSMTP();
       $this->loMailer->CharSet = 'UTF-8';
       $this->loMailer->SMTPAuth = true; 
       $this->loMailer->SMTPSecure = 'ssl';
       $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
       $this->loMailer->IsHTML(true);
       $this->loMailer->Username = $this->lcUser;
       $this->loMailer->Password = $this->lcPass; 
       $this->loMailer->SMTPSecure = 'tls';
       $this->loMailer->Port = 25;   // 465, 587
       $this->loMailer->SetFrom($this->lcUser);
       // Para pruebas locales - XAMPP
       $this->loMailer->SMTPOptions = array(
          'ssl' => array(
          'verify_peer' => false,
          'verify_peer_name' => false,
          'allow_self_signed' => true
          )
       );
       return true;
    }
 
    public function omSend() {
       $laEmail  = $this->paData['AEMAIL'];
       if ($laEmail != '' and !fxValEmail($laEmail)) {
          $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
          return false;
       }
       if ($laEmail != '') {
          $this->loMailer->AddAddress($laEmail);
       }
       $this->loMailer->Subject = $this->paData['CSUBJEC'];
       $this->loMailer->Body = $this->paData['CBODY'];
       if (!$this->loMailer->Send()) {
          $this->pcError = $this->loMailer->ErrorInfo;
          return false;
       }
       return true;
    }
}

// -------------------------------------------
// ENVIAR CARGO DE SOLICITUD DE MUSEOS ANDINOS
// CREACION APR 2022-01-28
// -------------------------------------------
class CEmailMuseosAndinos {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019';
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true;
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass;
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
   }
   public function omSend() {
      $lcCorreo = $this->paData['AEMAIL'];
      $this->loMailer->AddAddress($lcCorreo);
      $this->loMailer->Subject = 'ANDEAN SANCTUARY MUSEUM';
      $this->loMailer->Body = $this->paData['CBODY'];
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}  

# --------------------------------------------------
# ENVIAR CARGO DE SOLICITUD DE CLINICA ODONTOLOGICA
# CREACION APR 2022-04-03
# --------------------------------------------------
class CEmailClinicaOdontologica {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->pcRutAdj = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }
   
   public function omConnect() {      
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
   }

   public function omSend() {
      $laEmail  = $this->paData['AEMAIL'];
      if ($laEmail != '' and !fxValEmail($laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
      }
      $this->loMailer->Subject = 'DEUDA PROVISIONAL EN LA CLINICA ODONTOLOGICA - UCSM';
      $this->loMailer->Body = $this->paData['CBODY'];
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

// ---------------------------------------------
// ENVIAR CORREO DE EXPEDIENTES JURIDICOS
// CREACION GAR 2022-09-02
// ---------------------------------------------
class CEmailExpedienteJuridico {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      $this->paData   = null;
      $this->pcError  = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }
   
   public function omConnect() {      
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
      
   }

   public function omSend() {
      $laEmail  = $this->paData['AEMAIL'];
      $laMensa = "<!DOCTYPE html>
                           <html>
                           <body>                    
                              <table>
                                 <colgroup>
                                    <col style='background-color: #ececec'>
                                    <col style='background-color: #ffffff;'>
                                 </colgroup>    
                                 <thead>
                                    <tr style='background-color: #ffffff; color:black'>
                                       <th colspan='2'>
                                          <div class='container'>
                                             UNIVERSIDAD CATÓLICA DE SANTA MARÍA - ESCUELA PROFESIONAL DERECHO
                                          </div>
                                          <br>
                                       </th>
                                    </tr>
                                 </thead>
                                 <tbody>".$this->paData['CBODY']."</tbody>
                              </table>
                           </body>
                           </html>";
      
      if ($laEmail != '' and !fxValEmail($laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
      }
      foreach ($this->paData['CECOPIA'] as $lcCopia){
         $this->loMailer->addCC($lcCopia);
      }
      $this->loMailer->Subject = 'ESCUELA PROFESIONAL DERECHO UCSM - EXPEDIENTES JURÍDICOS';
      $this->loMailer->Body = $laMensa;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

# ------------------------------------------------
# ENVIAR EXPEDIENTE DE COMPLEMENTACIÓN PEDAGÓGICA
# CREACION APR 2022-07-19
# ------------------------------------------------
class CEmailComplementacionPedagogica {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->pcRutAdj = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }
   
   public function omConnect() {      
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      $this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 25;   // 465, 587
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
         'verify_peer' => false,
         'verify_peer_name' => false,
         'allow_self_signed' => true
         )
      );
   }

   public function omSend() {
      $laEmail  = 'vracademico2@ucsm.edu.pe';
      $lcCopia  = 'mjaime@ucsm.edu.pe';
      //$laEmail  = 'asalasr@ucsm.edu.pe';
      //$lcCopia  = '76334254@ucsm.edu.pe';
      if ($laEmail != '' and !fxValEmail($laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
      }
      $this->loMailer->addCC($lcCopia);
      foreach ($this->paData['ADOCUME'] as $laFila) {
         $this->loMailer->addAttachment($laFila['CRUTADJ'], $laFila['CDOCADJ'],  'base64', 'application/pdf');
      }
      $this->loMailer->Subject = 'CONVALIDACIÓN DE ASIGNATURAS DE COMPLEMENTACIÓN PEDAGÓGICA';
      $this->loMailer->Body = $this->paData['CBODY'];
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}
# ------------------------------------------------
# ENVIAR CORREOS CON DOCUMENTOS ADJUNTOS
# CREACION APR 2022-07-19
# ------------------------------------------------

class CEmailFile {
   protected $php_mailer, $lcUser, $lcPass, $lcCopia, $lcRutAdj, $lcDocAdj;
   public $paData, $pcError, $lcFileType;

   public function __construct() {
      $this->php_mailer          = null;
      $this->paData              = null;
      $this->pcError             = null;
      $this->lcUser              = 'soportetramites@ucsm.edu.pe';
      $this->lcPass              = 'Inglorious$2019';
      $this->lcCopia             = null;
      $this->lcCopiaAlumno       = null;
      $this->lcSegundaCopia      = null;
      $this->lcTerceraCopia      = null;
      $this->lcCopiaInformatica  = null;
      $this->lcCopiaInformatica1 = null;
      $this->lcRutAdj            = null;
      $this->lcDocAdj            = null;
      $this->lcFileType            = null;
   }

   public function omIngresarOrigen($p_cUser, $p_cPass) {
      $llOk = $this->mxValParamIngresarOrigen();
      if (!$llOk) {
         return false;
      }
      $this->lcUser = $p_cUser;
      $this->lcPass = $p_cPass;
      return true;
   }

   protected function mxValParamIngresarOrigen() {
      return true;
   }

   public function omAñadirDestinosCopia($p_cCopia) {
      $llOk = $this->mxValParamIngresarCopia();
      if (!$llOk) {
         return false;
      }
      $this->lcCopia = $p_cCopia;
      return true;
   }

   protected function mxValParamIngresarCopia() {
      return true;
   }

   public function omAñadirDestinosCopiaAlumno($p_cCopiaAlumno) {
      $llOk = $this->mxValParamIngresarCopiaAlumno();
      if (!$llOk) {
         return false;
      }
      $this->lcCopiaAlumno = $p_cCopiaAlumno;
      return true;
   }

   protected function mxValParamIngresarCopiaAlumno() {
      return true;
   }

   public function omAñadirDestinoSegundaCopia($p_cSegundaCopia) {
      $llOk = $this->mxValParamIngresarSegundaCopia();
      if (!$llOk) {
         return false;
      }
      $this->lcSegundaCopia = $p_cSegundaCopia;
      return true;
   }

   protected function mxValParamIngresarSegundaCopia() {
      return true;
   }

   public function omAñadirDestinoTerceraCopia($p_cTerceraCopia) {
      $llOk = $this->mxValParamIngresarTerceraCopia();
      if (!$llOk) {
         return false;
      }
      $this->lcTerceraCopia = $p_cTerceraCopia;
      return true;
   }

   protected function mxValParamIngresarTerceraCopia() {
      return true;
   }

   public function omAñadirDestinoCopiaJefeInformatica($p_cCopiaJInformatica) {
      $llOk = $this->mxValParamIngresarCopiaJefeInformatica();
      if (!$llOk) {
         return false;
      }
      $this->lcCopiaInformatica = $p_cCopiaJInformatica;
      return true;
   }

   protected function mxValParamIngresarCopiaJefeInformatica() {
      return true;
   }

   public function omAñadirDestinoCopiaInformatica($p_cCopiaJInformatica1) {
      $llOk = $this->mxValParamIngresarCopiaInformatica();
      if (!$llOk) {
         return false;
      }
      $this->lcCopiaInformatica1 = $p_cCopiaJInformatica1;
      return true;
   }

   protected function mxValParamIngresarCopiaInformatica() {
      return true;
   }

   public function omAñadirDocumento($p_RutAdj, $p_DocAdj) {
      $llOk = $this->mxValParamAñadirDocumento();
      if (!$llOk) {
         return false;
      }
      $this->lcRutAdj = $p_RutAdj;
      $this->lcDocAdj = $p_DocAdj;
      return true;
   }

   protected function mxValParamAñadirDocumento() {
      return true;
   }

   public function omConnect() {
      $this->php_mailer = new PHPMailer();
      $this->php_mailer->IsSMTP(); // enable SMTP
      $this->php_mailer->CharSet = 'UTF-8';
      //$this->php_mailer->SMTPDebug = 1; // debugging: 1 = errors and messages, 2 = messages only
      $this->php_mailer->SMTPAuth = true; // authentication enabled
      $this->php_mailer->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
      //$this->php_mailer->Host = "smtp.gmail.com";
      $this->php_mailer->Host = 'smtp.office365.com';
      $this->php_mailer->Port = 465; // or 587
      $this->php_mailer->IsHTML(true);
      /*$this->php_mailer->Username = "efb.devs@gmail.com";
      $this->php_mailer->Password = "SistemasFPM";*/
      $this->php_mailer->Username = $this->lcUser;
      $this->php_mailer->Password = $this->lcPass;
      $this->php_mailer->SMTPSecure = 'tls';
      $this->php_mailer->Port = 25;
      $this->php_mailer->SetFrom($this->lcUser);

      //PARA PROBAR LOCALMENE EN XAMPP
      $this->php_mailer->SMTPOptions = array(
         'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
         )
      );

      $this->php_mailer->addAttachment($this->lcRutAdj, $this->lcDocAdj,  'base64', 'application/pdf');
      $this->php_mailer->addCC($this->lcCopia);
      $this->php_mailer->addCC($this->lcCopiaAlumno);
      $this->php_mailer->addCC($this->lcSegundaCopia);
      $this->php_mailer->addBCC($this->lcTerceraCopia);
      $this->php_mailer->addBCC($this->lcCopiaInformatica);
      $this->php_mailer->addBCC($this->lcCopiaInformatica1);
      return true;
   }

   public function omSend() {
      $llOk = $this->mxValParamSend();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinos();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = $this->paData['CSUBJEC'];
      $this->php_mailer->Body .= $this->paData['CBODY'];
      $this->php_mailer->addAttachment($this->lcRutAdj);
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSend() {
      if (!isset($this->paData['CSUBJEC']) || empty($this->paData['CSUBJEC'])) {
         $this->pcError = "ASUNTO DEL EMAIL NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['AEMAILS']) || count($this->paData['AEMAILS']) == 0) {
         $this->pcError = "EMAIL DESTINO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CBODY']) || empty($this->paData['CBODY'])) {
         $this->pcError = "CUERPO DEL EMAIL NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinos() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }

   public function omSendBachillerato() {
      $llOk = $this->mxValParamSendBachillerato();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinosBachillerato();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = $this->paData['CSUBJEC'];
      $this->php_mailer->AddEmbeddedImage('Images/Colacion.png', 'imagenColacion');
      $this->php_mailer->Body = $this->paData['CBODY'];
      $this->php_mailer->Body .= '<div><img src="cid:imagenColacion" height="580" class="center"></div><br>';
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSendBachillerato() {
      if (!isset($this->paData['CSUBJEC']) || empty($this->paData['CSUBJEC'])) {
         $this->pcError = "ASUNTO DEL EMAIL NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['AEMAILS']) || count($this->paData['AEMAILS']) == 0) {
         $this->pcError = "EMAIL DESTINO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CBODY']) || empty($this->paData['CBODY'])) {
         $this->pcError = "CUERPO DEL EMAIL NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinosBachillerato() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }

   public function omSendTitulacion() {
      $llOk = $this->mxValParamSendTitulacion();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinosTitulacion();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = $this->paData['CSUBJEC'];
      $this->php_mailer->AddEmbeddedImage('Images/Colacion.png', 'imagenColacion');
      $this->php_mailer->Body = $this->paData['CBODY'];
      $this->php_mailer->Body .= '<div><img src="cid:imagenColacion" height="580" class="center"></div><br>';
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSendTitulacion() {
      if (!isset($this->paData['CSUBJEC']) || empty($this->paData['CSUBJEC'])) {
         $this->pcError = "ASUNTO DEL EMAIL NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['AEMAILS']) || count($this->paData['AEMAILS']) == 0) {
         $this->pcError = "EMAIL DESTINO NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CBODY']) || empty($this->paData['CBODY'])) {
         $this->pcError = "CUERPO DEL EMAIL NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinosTitulacion() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }



   public function omSendCETraduccion() {
      $llOk = $this->mxValParamSendCETraduccion();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAñadirDestinosCETraduccion();
      if (!$llOk) {
         return false;
      }
      $this->php_mailer->Subject = 'ARCHIVO DE TRADUCCIÓN';
      $this->php_mailer->AddEmbeddedImage('Images/ucsm-03.png', 'logo');
      $this->php_mailer->Body = '<div><img src="cid:logo" height="80"></div><br>';
      $this->php_mailer->Body .= 'ADJUNTO ARCHIVO DE TRADUCCIÓN';
      $this->php_mailer->AddAttachment($this->paData['CARCHIV'],$this->paData['CARCHNM']);
      if (!$this->php_mailer->Send()) {
         $this->pcError = $this->php_mailer->ErrorInfo;
         return false;
      }
      return true;
   }

   protected function mxValParamSendCETraduccion() {
      if (!isset($this->paData['CARCHIV']) || empty($this->paData['CARCHIV'])) {
         $this->pcError = "ARCHIVO NO ADJUNTADO";
         return false;
      }
      return true;
   }

   protected function mxAñadirDestinosCETraduccion() {
      foreach ($this->paData['AEMAILS'] as $value) {
         $this->php_mailer->AddAddress($value);
      }
      return true;
   }
}
?>