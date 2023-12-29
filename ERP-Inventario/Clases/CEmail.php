<?php
require_once 'class/PHPMailer/PHPMailer.php';
require_once 'class/PHPMailer/Exception.php';
require_once 'class/PHPMailer/OAuth.php';
require_once 'class/PHPMailer/POP3.php';
require_once 'class/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class CEmail {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      //--Revisar Errores.
      //$this->loMailer->SMTPDebug = 2;
      $this->lcUser   = 'inventario@ucsm.edu.pe';
      $this->lcPass   = 'Patrimonio2022'; 
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
                                       CONTROL PATRIMONIAL - UCSM<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Estimado(a): </strong><br><br>
                                    Previo saludo, a traves de la presente se envia una copia de la transferencia realizada. 
                                    <br><br><br><br>
                                    Atentamente;
                                    <br>
                                    Dirección de Control Patrimonial
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $laEmail  = $this->paData['AEMAIL'];
      $lcDocAdj = $this->paData['CDOCADJ'];
      //$lcCopia  = $this->paData['CECOPIA'];
      $this->loMailer->AddAddress($laEmail[0]['EMAIL']);
      $this->loMailer->AddAddress($laEmail[1]['EMAIL']);
      //$this->loMailer->addCC($lcCopia);
      $this->loMailer->addAttachment($lcDocAdj, '',  'base64', 'application/pdf');
      $this->loMailer->Subject = 'CONTROL PATRIMONIAL UCSM - TRANSFERENCIA';
      $this->loMailer->Body = $this->lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }

   public function omSendInventario() {
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
                                       CONTROL PATRIMONIAL - INVENTARIO ".$this->paData['CYEAR']."<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Estimado(a): </strong><br><br>
                                       Previo saludo, a traves de la presente se envia una copia de la lista de activos fijos del Inventario ".$this->paData['CYEAR']."
                                    <br><br><br><br>
                                    Atentamente;
                                    <br>
                                    Dirección de Control Patrimonial
                                    <br><br>
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $laEmail1  = $this->paData[0]['CEMAIL'];
      $laEmail2  = $this->paData[1]['CEMAIL'];
      $lcDocAdj = $this->paData[0]['CDOCADJ'];
      //$lcCopia  = $this->paData['CECOPIA'];
      // $this->loMailer->AddAddress($laEmail[0]['EMAIL']);
      $this->loMailer->AddAddress($laEmail1);
      $this->loMailer->AddAddress($laEmail2);
      //$this->loMailer->addCC($lcCopia);
      $this->loMailer->addAttachment($lcDocAdj, '',  'base64', 'application/pdf');
      $this->loMailer->Subject = 'CONTROL PATRIMONIAL UCSM - INVENTARIO '.$this->paData['CYEAR'];
      $this->loMailer->Body = $this->lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }

}

class CEmailImpresiones {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      //$this->lcUser   = 'inventario@ucsm.edu.pe';
      //$this->lcPass   = 'Patrimonio2022'; 
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
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self-signed' => true
         )
      );
   }
   public function omSend() {
      $laEmail = $this->paData['AEMAIL'];
      //$lcCopia = $this->paData['CECOPIA'];
      $laMensa = "<!DOCTYPE html>
                  <html>
                     <body>
                        <table>
                           <colgroup>
                              <col style='background-color: #ececec;'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>
                           <tbody><div class='container'>UNIVERSIDAD CATOLICA DE SANTA MARIA - IMPRESIONES <br><br>".$this->paData['CBODY']."</div></tbody>
                        </table>
                     </body>
                  </html>";
      if ($laEmail != '' and !preg_match("/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/", $laEmail)) {
         $this->pcError = 'EMAIL NO DEFINIDO O INVALIDO';
         return false;
      }
      if ($laEmail != '') {
         $this->loMailer->AddAddress($laEmail);
         //$this->loMailer->addCC($lcCopia);
      }
      $this->loMailer->Subject = 'AVISO DE IMPRESIONES - UCSM';
      $this->loMailer->Body = $laMensa;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

class CEmailParqueo {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'soportetramites@ucsm.edu.pe';
      $this->lcPass   = 'Inglorious$2019'; 
      //$this->lcUser   = 'inventario@ucsm.edu.pe';
      //$this->lcPass   = 'Patrimonio2022'; 
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
      $this->loMailer->SMTPOptions = array(
         'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self-signed' => true
         )
      );
   }
   public function omSend() {
      foreach ($this->paData as $laTmp) {
         $laEmaVice = $laTmp['CEMAVI1'];
        //$this->paData[] = ['CEMAVI1' => 'ccaceres@ucsm.edu.pe', 'CEMAVI2' => 'vradm@ucsm.edu.pe', 'CEMAIL1' => 'fparedesm@ucsm.edu.pe', 'CEMAIL2' => 'smestasr@ucsm.edu.pe'];
         $this->loMailer->AddAddress($laEmaVice);
         //$this->loMailer->addAttachment($lcDocAdj, '',  'base64', 'application/pdf');
         $this->loMailer->addCC($laTmp['CEMAVI2']);
         $this->loMailer->addCC($laTmp['CEMAIL1']);
         $this->loMailer->addCC($laTmp['CEMAIL2']);
         //body
         $this->lcBodyConvalidacion = "<!DOCTYPE html>
         <html>
         <body>                   
            <table>
               <colgroup>
                  <col style='background-color: #ececec'>
                  <col style='background-color: #ffffff;'>
               </colgroup>   
               <thead>
                  <tr style='background-color: #ffffff; font-weight: 500; color:black; font-size:30px'>
                     <th colspan='2'>
                        <div class='container'>
                           COMUNICADO<br>
                        </div>
                     </th>
                  </tr>
                  <tr style='background-color: #ffffff; color:black;font-size:20px; text-align: justify'>
                     <th colspan='2'>
                        <br>
                            <strong>Estimado Dr. CESAR CACERES ZARATE, Vicerrector Administrativo de la UCSM: </strong><br><br>
                            <p>Sirve la presente para solicitar su apoyo en la aprobación de los descuentos de la playa de estacionamiento PERIODO: ".$laTmp['CPERIOD']."</p>
                            <p>Para poder realizar la operación debe ingresar al siguiente enlace: </p>
                            <p style='color: blue'>   https://env.ucsm.edu.pe/ERP-II/index.php</p>
                            <p>Nota: La opción es APROBACIÓN DE DESCUENTO POR USO DE PLAYA DE ESTACIONAMIENTO</p>
                            <p>Agradezco por el apoyo en este proceso.</p>
                            <br><br>
                            <p>Atentamente,</p>
                            <p>SOPORTE TRAMITES</p>
                     </th>
                  </tr>
               </thead>
            </table>
         </body>
         </html>";
      }
      # PARA ADJUNTAR DOCUMENTOS ADJUNTOS
      $this->loMailer->Subject = 'COMUNICADO - PLAYA DE ESTACIONAMIENTO - PERIODO: '.$this->paData[0]['CPERIOD'];
      $this->loMailer->Body = $this->lcBodyConvalidacion;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      $this->loMailer->ClearAddresses();
      $this->loMailer->ClearCCs();
      return true;
   }

   public function omSendAprobacionVicerrectorado() {
      foreach ($this->paData as $laTmp) {
         $laEmail = $laTmp['CEMAVI1'];
         $this->loMailer->AddAddress($laEmail);
         //$this->loMailer->addAttachment($lcDocAdj, '',  'base64', 'application/pdf');
         $this->loMailer->addCC($laTmp['CEMAVI2']);
         $this->loMailer->addCC($laTmp['CEMAVI3']);
         $this->loMailer->addCC($laTmp['CEMAIL1']);
         $this->loMailer->addCC($laTmp['CEMAIL2']);
         $this->loMailer->addCC($laTmp['CEMAIL3']);
         $this->loMailer->addCC($laTmp['CEMAIL4']);
         //body
         $this->lcBody = "<!DOCTYPE html>
         <html>
         <body>                   
            <table>
               <colgroup>
                  <col style='background-color: #ececec'>
                  <col style='background-color: #ffffff;'>
               </colgroup>   
               <thead>
                  <tr style='background-color: #ffffff; font-weight: 500; color:black; font-size:30px'>
                     <th colspan='2'>
                        <div class='container'>
                           COMUNICADO<br>
                        </div>
                     </th>
                  </tr>
                  <tr style='background-color: #ffffff; color:black;font-size:20px; text-align: justify'>
                     <th colspan='2'>
                        <br>
                            <strong>Estimados: </strong><br><br>
                            <p>Sirve la presente para informarles que los descuentos de la playa de estacionamiento PERIODO: ".$laTmp['CPERIOD']." han sido APROBADO POR VICERRECTORADO ADMINISTRATIVO.</p>
                            <p>Para poder realizar la operación debe ingresar al siguiente enlace: </p>
                            <p style='color: blue'>   https://env.ucsm.edu.pe/ERP-II/index.php</p>
                            <p>Nota: La opción es CONSULTA DE DESCUENTOS APROBADOS POR USO DE PLAYA DE ESTACIONAMIENTO</p>
                            <p>Agradezco por el apoyo en este proceso.</p>
                            <br><br>
                            <p>Atentamente,</p>
                            <p>SOPORTE TRAMITES</p>
                     </th>
                  </tr>
                  <tr style='background-color: #ffffff; color:black;font-size:20px; text-align: justify'>
                     <th colspan='2'>
                        <p><span style='color: rgb(178, 178, 178);'>Este mensaje de correo electrónico se ha enviado desde una dirección exclusivamente para envíos.No responda a este mensaje.</span></p>
                     </th>
                  </tr>
               </thead>
            </table>
         </body>
         </html>";
      }
      # PARA ADJUNTAR DOCUMENTOS ADJUNTOS
      $this->loMailer->Subject = 'COMUNICADO - PLAYA DE ESTACIONAMIENTO - PERIODO: '.$this->paData[0]['CPERIOD'].' - APROBADO';
      $this->loMailer->Body = $this->lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      $this->loMailer->ClearAddresses();
      $this->loMailer->ClearCCs();
      return true;
   }
}

class CEmailPrueba {
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      //-- Revisar Errores
      //$this->loMailer->SMTPDebug = 2;

      $this->lcUser   = 'inventario@ucsm.edu.pe';
      $this->lcPass   = 'Patrimonio2022'; 
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
                                       CONTROL PATRIMONIAL - UCSM<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Estimado(a): </strong><br><br>
                                       Previo saludo, a traves de la presente se envia una copia de la transferencia realizada. 
                                    <br><br><br><br>
                                    Este es un mensaje automático, por lo que le agradeceremos no responderlo.
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
   }

   public function omSend() {
      print_r($this->paData);
      $laEmail  = $this->paData['AEMAIL'];
      $this->loMailer->AddAddress('gcampoverde@ucsm.edu.pe');
      //$this->loMailer->AddAddress($laEmail[0]['EMAIL']);
      //$this->loMailer->AddAddress($laEmail[1]['EMAIL']);
      //$this->loMailer->addCC($lcCopia);
      //$this->loMailer->addAttachment($lcDocAdj, '',  'base64', 'application/pdf');
      $this->loMailer->Subject = 'CONTROL PATRIMONIAL UCSM - TRANSFERENCIA';
      $this->loMailer->Body = $this->lcBody;
      print_r($this->paData);
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }

}

?>