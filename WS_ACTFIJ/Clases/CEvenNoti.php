<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CEvenNoti extends CBase {
   public $pcParam;
   public $paDatos;
   public $paData;

   public function __construc() {
      $this->pcParam = null;
   }

   public function omCargarEventos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxCargarEventos($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarEventos($p_oSql) {
      $lcSql = "SELECT cIdEvNo, cTipo, cTitulo, cConten, cDescri, cUniAca, cAcaUni, dEvento, cHora, cLugar, cLink, tGenera, cEstado, dVencim
               FROM a04devn ORDER BY dEvento DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->paDatos = ["error" => "Error al consultar eventos."];
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $laTmp = [
            "CIDEVNO" => $laFila[0],
            "CTIPO" => $laFila[1],
            "CTITULO" => $laFila[2],
            "CCONTEN" => $laFila[3],
            "CDESCRI" => $laFila[4],
            "CUNIACA" => $laFila[5],
            "CACAUNI" => $laFila[6],
            "DEVENTO" => $laFila[7],
            "CHORA" => $laFila[8],
            "CLUGAR" => $laFila[9],
            "CLINK" => $laFila[10],
            "TGENERA" => $laFila[11],
            "CESTADO" => $laFila[12],
            "DVENCIM" => $laFila[13],
         ];
         $laVar[] = $laTmp;
      }
      $this->paDatos = $laVar;
      return true;
   }

   public function omCargarEvento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxCargarEvento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCargarEvento($p_oSql) {
      $lcSql = "SELECT cIdEvNo, cTipo, cTitulo, cConten, cDescri, cUniAca, cAcaUni, dEvento, cHora, cLugar, cLink, tGenera, cEstado, dVencim
               FROM a04devn WHERE cIdEvNo = {$this->paData["CIDEVNO"]} ORDER BY dEvento DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->paDatos = ["error" => "Error al consultar evento."];
         return false;
      } elseif ($p_oSql->pnNumRow === 0) {
         $this->paDatos = ["error" => "No se encontro el evento."];
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laTmp = [
         "CIDEVNO" => $laFila[0],
         "CTIPO" => $laFila[1],
         "CTITULO" => $laFila[2],
         "CCONTEN" => $laFila[3],
         "CDESCRI" => $laFila[4],
         "CUNIACA" => $laFila[5],
         "CACAUNI" => $laFila[6],
         "DEVENTO" => $laFila[7],
         "CHORA" => $laFila[8],
         "CLUGAR" => $laFila[9],
         "CLINK" => $laFila[10],
         "TGENERA" => $laFila[11],
         "CESTADO" => $laFila[12],
         "DVENCIM" => $laFila[13],
      ];
      $this->paDatos = $laTmp;
      return true;
   }

   public function omCrearEvento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxCrearEvento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxCrearEvento($p_oSql) {
      $lcSql = "INSERT INTO a04devn (cIdEvno, cTipo, cTitulo, cConten, cDescri, cUniAca, cAcaUni, dEvento, dVencim, cHora, cLugar, cLink, cEstado, cCodUsu) 
               VALUES (DEFAULT, '{$this->paData["CTIPO"]}', '{$this->paData["CTITULO"]}', '{$this->paData["CCONTEN"]}', '{$this->paData["CDESCRI"]}',
               '{$this->paData["CUNIACA"]}', '{$this->paData["CACAUNI"]}', '{$this->paData["DEVENTO"]}', '{$this->paData["DVENCIM"]}',
               '{$this->paData["CHORA"]}', '{$this->paData["CLUGAR"]}', '{$this->paData["CLINK"]}', '{$this->paData["CESTADO"]}', '9999')";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->paDatos = [
            "error" => "Error al crear evento.",
         ];
         return false;
      }
      $this->paDatos = ["data" => "Evento creado."];
      return true;
   }

   public function omEditarEvento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxEditarEvento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxEditarEvento($p_oSql) {
      $lcSql = "UPDATE a04devn SET (cTipo, cTitulo, cConten, cDescri, cUniAca, cAcaUni, dEvento, dVencim, cHora, cLugar, cLink, cEstado, cCodUsu) 
               = ('{$this->paData["CTIPO"]}', '{$this->paData["CTITULO"]}', '{$this->paData["CCONTEN"]}', '{$this->paData["CDESCRI"]}',
               '{$this->paData["CUNIACA"]}', '{$this->paData["CACAUNI"]}', '{$this->paData["DEVENTO"]}', '{$this->paData["DVENCIM"]}',
               '{$this->paData["CHORA"]}', '{$this->paData["CLUGAR"]}', '{$this->paData["CLINK"]}', '{$this->paData["CESTADO"]}', '9999')
               WHERE cIdEvno = {$this->paData["CIDEVNO"]}";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->paDatos = [
            "error" => "Error al editar evento.",
         ];
         return false;
      }
      $this->paDatos = ["data" => "Evento creado."];
      return true;
   }

   public function omDesactivarEvento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxDesactivarEvento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxDesactivarEvento($p_oSql) {
      $lcSql = "UPDATE a04devn SET cEstado = 'I' WHERE cIdEvno = {$this->paData["CIDEVNO"]}";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->paDatos = [
            "error" => "Error al desactivar evento.",
         ];
         return false;
      }
      $this->paDatos = ["data" => "Evento desactivado."];
      return true;
   }

   public function omRecuperarUnidades() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxRecuperarUnidades($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxRecuperarUnidades($p_oSql) {
      $lcSql =
         "SELECT cuniaca, cnomuni FROM s01tuac WHERE cestado = 'A' AND cnivel = '01'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->paDatos = [
            "error" => "Error al consultar unidades académicas.",
         ];
         return false;
      }
      $laTmp = [];
      while ($laFila = $p_oSql->fetch($RS)) {
         $laTmp[] = [
            "CUNIACA" => $laFila[0],
            "CNOMUNI" => $laFila[1],
         ];
      }
      $this->paDatos = $laTmp;
      return true;
   }

   public function omLanzarNotificacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->paDatos = ["error" => $loSql->paError];
         return false;
      }
      $llOk = $this->mxLanzarNotificacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxLanzarNotificacion($p_oSql) {
      $lcIdEvno = intval($this->paData["CIDEVNO"]);
      $lcSql = "SELECT cUniAca, cTitulo, cDescri FROM a04devn WHERE cIdEvno = $lcIdEvno";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->paDatos = [
            "error" => "Error al recuperar unidad académica de destino",
         ];
         return false;
      }
      $lcEvento = $p_oSql->fetch($R1);
      if ($lcEvento[0] == "00") {
         $lcSql =
            "SELECT cdevuid FROM s01pdis WHERE cnrodni IN (SELECT cnrodni FROM a01malu WHERE cestado = 'A') AND cdevuid != 'token'";
      } else {
         $lcSql = "SELECT cdevuid FROM s01pdis WHERE cnrodni IN (SELECT cnrodni FROM a01malu WHERE cestado = 'A' AND cuniaca = '$lcEvento[0]') AND cdevuid != 'token'";
      }
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->paDatos = ["error" => "Error al obtener usuarios de destino."];
         return false;
      }
      $tokens = [];
      while ($laFila = $p_oSql->fetch($RS)) {
         array_push($tokens, $laFila[0]);
      }

      $fields = [
         "registration_ids" => $tokens,
         "notification" => [
            "title" => $lcEvento[1],
            "body" => $lcEvento[2],
            "click_action" => "/UCSMPWA/eventos?id=" . $lcIdEvno,
         ],
      ];
      $llOk = $this->sendPushNotification($fields);
      if ($llOk === false) {
         $this->paDatos = ["error" => "Error al enviar notificaciones."];
         return false;
      }
      $this->paDatos = json_decode($llOk, true);
      return true;
   }

   private function sendPushNotification($fields) {
      //require_once 'Config.php';
      $url = "https://fcm.googleapis.com/fcm/send";
      $headers = [
         "Authorization: key=" .
         "AAAAFG_7oIo:APA91bGAd6UeRWkn3XBTYsyoD435KSq-0eI-i7hi95r_kWxGwAxtBCsUYk9rKn3nlOTPiOgSB__UZOO24i5g-A__TLagXdsVZoRT3m1PWM7SWO4ARPj3ybyLedQPGvSXCqODYQDZuUxo",
         "Content-Type: application/json",
      ];
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($ch, CURLOPT_SSLVERSION, 6);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

      $result = curl_exec($ch);
      curl_close($ch);
      return $result;
   }
}

/* SELECT cIdEvNo, cTipo, cTitulo, cConten, cUniAca, cAcaUni, dEvento, cHora, cLugar, cLink, tGenera FROM a04devn 
               WHERE cEstado = 'B' AND CTIPO = 'E' AND cUniAca IN ('00', 'ZA')
               ORDER BY dEvento DESC
			   
delete from a04devn where to_char(devento, 'YYYY') IN ('2017','2018','0001')

select * from a04devn

SELECT cIdEvNo, cTipo, cTitulo, cConten, cDescri, cUniAca, cAcaUni, dEvento, cHora, cLugar, cLink, tGenera, cEstado FROM a04devn 
               ORDER BY dEvento DESC
			   
alter table a04devn add column dvencim date

delete from a04devn where cidevno = 585 */

?>
