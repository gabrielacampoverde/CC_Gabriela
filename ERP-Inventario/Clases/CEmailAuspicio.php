<?php
require_once 'class/PHPMailer/PHPMailer.php';
require_once 'class/PHPMailer/PHPMailerAutoload.php';
require_once 'class/PHPMailer/Exception.php';
require_once 'class/PHPMailer/OAuth.php';
require_once 'class/PHPMailer/POP3.php';
require_once 'class/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

ini_set('display_errors', 1);

class CEmail {    
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'rsu@ucsm.edu.pe';
      $this->lcPass   = 'Soledad$4'; 
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
      $this->loMailer->Port = 587;   // 465, 587, 25
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
      //print_r($this->paData);
      $lcBody = "<!DOCTYPE html>
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
                                       UNIVERSIDAD CATÓLICA SANTA MARÍA<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Sr.(s) ".$this->paData['CNOMINS']."</strong>
                                    <br><br>
                                    Previo saludo, Ustedes han realizado una solicitud de Auspicio Académica denominada:
                                    <br><br>
                                    ".$this->paData['CNOMAUS']."
                                    <br><br>
                                    El monto de pago es: S/ 160.00 Soles
                                    <br><br>
                                    El código de pago es:
                                    <br>
                                    <div class='container' style='text-align: center; font-size:25px'>
                                    ".$this->paData['CNROPAG']."
                                    </div>
                                    <br><br>
                                    Puede realizar el pago en las instituciones financieras con las cuales trabaja la UCSM.(BCP, Caja Arequipa, Interbank, Scotiabank)
                                    <br><br>
                                    Una vez realizado el pago se le emitirá una [FACTURA/BOLETA] la cual será remitida a este mismo correo.
                                    (Este correo no requiere respuesta)
                                    <br><br><br>
                                    Atentamente;
                                    <br>
                                    Dirección de Responsabilidad Social
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $laEmail  = $this->paData['CEMAIL'];
      $this->loMailer->AddAddress($laEmail);
      $this->loMailer->Subject = 'UNIVERSIDAD CATÓLICA SANTA MARÍA - AUSPICIO';
      $this->loMailer->Body = $lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}


class CEmailAnularVice {    
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      $this->lcUser   = 'rsu@ucsm.edu.pe';
      $this->lcPass   = 'Soledad$4'; 
      $this->paData   = null;
      $this->pcError  = null;
      $this->paEmail  = null;
      $this->loMailer = new PHPMailer();
      $this->loMailer->IsSMTP();
      $this->loMailer->CharSet = 'UTF-8';
      $this->loMailer->SMTPAuth = true; 
      //$this->loMailer->SMTPSecure = 'ssl';
      $this->loMailer->Host = 'smtp.office365.com';   // smtp.gmail.com
      $this->loMailer->IsHTML(true);
      $this->loMailer->Username = $this->lcUser;
      $this->loMailer->Password = $this->lcPass; 
      $this->loMailer->SMTPSecure = 'tls';
      $this->loMailer->Port = 587;   // 465, 587,25
      $this->loMailer->SetFrom($this->lcUser);
      // Para pruebas locales - XAMPP
      //$this->loMailer->SMTPOptions = array(
      //   'ssl' => array(
      //   'verify_peer' => false,
      //   'verify_peer_name' => false,
      //   'allow_self_signed' => true
      //   )
      //);
   }
   public function omSend() {
      //print_r($this->paData);
      $lcBody = "<!DOCTYPE html>
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
                                       UNIVERSIDAD CATÓLICA SANTA MARÍA<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Sr.(s) ".$this->paData['CNOMINS']."</strong>
                                    <br><br>
                                       Previo saludo, para informarle que su solicitud de auspicio fue rechazado, a continuación el motivo:  
                                    <br><br>
                                    <div class='container' style='font-size:15px'>
                                    ".$this->paData['MOBSERVA']."
                                    </div>
                                    <br><br>
                                    (Este correo no requiere respuesta)
                                    <br><br><br>
                                    Atentamente;
                                    <br>
                                    Dirección de Responsabilidad Social
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $laEmail  = $this->paData['CEMAIL'];
      $this->loMailer->AddAddress($laEmail);
      $this->loMailer->Subject = 'UNIVERSIDAD CATÓLICA SANTA MARÍA - AUSPICIO';
      $this->loMailer->Body = $lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}


class CEmailObservacion {    
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      //print_r($this->paData);
      $this->lcUser   = 'rsu@ucsm.edu.pe';
      $this->lcPass   = 'Soledad$4'; 
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

   public function omSendObservacion() {
      $laEmail  = $this->paData['CEMAIL'];
      $lcBody = "<!DOCTYPE html>
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
                                       UNIVERSIDAD CATÓLICA SANTA MARÍA<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Estimado(a): </strong><br><br>
                                       Previo saludo, se envia las observaciones realizadas por la facultad académica:  
                                    <br><br>
                                    <div class='container' style='font-size:15px'>
                                    ".$this->paData['MOBSERVA']."
                                    </div>
                                    <br><br><br>
                                    Por favor levantar las observaciones y volver a subir su solicitud correctamente en la siguiente dirección:
                                    <br><br><br>
                                    https://env.ucsm.edu.pe/ERP-II/Aus1050.php
                                    <br><br>
                                    Este es un mensaje automático, por lo que le agradeceremos no responderlo.
                                    <br><br>
                                    Atentamente;
                                    <br>
                                    Dirección de Responsabilidad social
                                    <br><br>
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $this->loMailer->AddAddress($laEmail);
      $this->loMailer->Subject = 'UNIVERSIDAD CATÓLICA SANTA MARÍA - AUSPICIO';
      $this->loMailer->Body = $lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

class CEmailFacultad {    
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      //print_r($this->paData);
      $this->lcUser   = 'rsu@ucsm.edu.pe';
      $this->lcPass   = 'Soledad$4  '; 
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
      // $laEmail  = $this->paData['CEMAIL'];
      $laEmail  = 'gcampoverde@ucsm.edu.pe';
      $lcBody = "<!DOCTYPE html>
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
                                       DIRECCIÓN DE RESPONSABILIDAD SOCIAL<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Estimado(a): </strong><br><br>
                                    Previo saludo, se le comunica que tiene un auspicio para su revisión en su bandeja del ERP;
                                    <br><br>
                                    https://env.ucsm.edu.pe/ERP-II/index.php?id=Auspicio
                                    <br><br><br><br><br><br>
                                    Atentamente;
                                    <br><br>
                                    Dirección de Responsabilidad Social
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $this->loMailer->AddAddress($laEmail);
      $this->loMailer->Subject = 'DIRECCIÓN DE RESPONSABILIDAD SOCIAL - AUSPICIO';
      $this->loMailer->Body = $lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}

class CEmailAnulacion {    
   protected $loMailer, $lcBody;
   public $paData, $pcError;

   public function __construct() {
      print_r($this->paData);
      $this->lcUser   = 'rsu@ucsm.edu.pe';
      $this->lcPass   = 'Soledad$4'; 
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

   public function omSendAnulacion() {
      $laEmail  = $this->paData['CEMAIL'];
      $lcBody = "<!DOCTYPE html>
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
                                       UNIVERSIDAD CATÓLICA SANTA MARÍA<br>
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; text-align: justify'>
                                 <th colspan='2'>
                                 <br><br>
                                    <strong>Estimado(a): </strong><br><br>
                                       Previo saludo, para informarle que su solicitud de auspicio fue rechazado, a continuación el motivo:  
                                    <br><br>
                                    <div class='container' style='font-size:15px'>
                                    ".$this->paData['MOBSERVA']."
                                    </div>
                                    <br><br><br><br><br>
                                    Este es un mensaje automático, por lo que le agradeceremos no responderlo.
                                    <br><br>
                                    Atentamente;
                                    <br>
                                    Dirección de Responsabilidad social
                                    <br><br>
                                 </th>
                              </tr>
                           </thead>
                        </table>
                     </body>
                     </html>";
      $this->loMailer->AddAddress($laEmail);
      $this->loMailer->Subject = 'UNIVERSIDAD CATÓLICA SANTA MARÍA - AUSPICIO';
      $this->loMailer->Body = $lcBody;
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
      return true;
   }
}
?>



