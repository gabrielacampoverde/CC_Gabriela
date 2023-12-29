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
      // print_r($this->paData);
      $laEmail  = $this->paData['AEMAIL'];
      $lcDocAdj = $this->paData['CDOCADJ'];
      //$lcCopia  = $this->paData['CECOPIA'];
      //$this->loMailer->AddAddress('gcampoverde@ucsm.edu.pe');
      $this->loMailer->AddAddress($laEmail[0]['EMAIL']);
      $this->loMailer->AddAddress($laEmail[1]['EMAIL']);
      //$this->loMailer->addCC($lcCopia);
      $this->loMailer->addAttachment($lcDocAdj, '',  'base64', 'application/pdf');
      $this->loMailer->Subject = 'CONTROL PATRIMONIAL UCSM - TRANSFERENCIA';
      $this->loMailer->Body = $this->lcBody;
      //print_r($this->paData);
      if (!$this->loMailer->Send()) {
         $this->pcError = $this->loMailer->ErrorInfo;
         return false;
      }
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