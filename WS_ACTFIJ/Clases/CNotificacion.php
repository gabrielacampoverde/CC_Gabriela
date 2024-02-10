<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Clases/CPush.php";
require_once "Clases/CFirebase.php";

class CNotificacion extends CBase {
   public $paDatos,
      $paData,
      $paTitulo,
      $paDescri,
      $paUrlImg,
      $paUniaca,
      $paAcauni,
      $paEstado,
      $devicetoken,
      $paRespuesta,
      $pacidevno,
      $pafailure,
      $pasuccess;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paTitulo = $this->pafailure = $this->pasuccess = $this->pacidevno = $this->paRespuesta = $this->paDescri = $this->paUrlImg = $this->paUniaca = $this->paAcauni = $this->paEstado = $this->devicetoken = null;
   }

   // --------------------------------------------------
   // Iniciar registro y mantenimiento de notificaciones
   // --------------------------------------------------
   public function omInitConsultas() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitConsultas($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitConsultas($p_oSql) {
      $lcUniAca = $this->paData["CUNIACA"];
      $i = 0;
      $lcSql =
         "SELECT cUniAca, cNomUni FROM S01TUAC WHERE cEstado = 'A' ORDER BY CNOMUNI";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paUniAca[] = [$laFila[0], $laFila[1]];
      }
      $this->paUniAca[] = ["00", "* TODAS"];
      if ($i == 0) {
         $this->pcError = "ESCUELAS NO ESTAN DEFINIDAS";
         return false;
      }
      $i = 0;
      $lcSql = "SELECT cUniAca, cNomUni FROM S01TUAC WHERE cUniAca = '$lcUniAca'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos = ["CUNIACA" => $laFila[0], "CNOMUNI" => $laFila[1]];
      }
      if ($i == 0) {
         $this->pcError = "UNIDAD ACADEMICA NO ESTA ACTIVA";
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // Registro de Notificaciones
   // --------------------------------------------------
   public function omGrabarNotificacion() {
      $llOk = $this->mxValParamGrabarNotificacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGrabarNotificacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGrabarNotificacion() {
      if (!isset($this->paData["CUNIACA"]) or empty($this->paData["CUNIACA"])) {
         $this->pcError = "UNIDAD ACADEMICA DE DESTINO NO DEFINIDA";
         return false;
      } elseif (
         !isset($this->paData["CACAUNI"]) or empty($this->paData["CACAUNI"])
      ) {
         $this->pcError = "UNIDAD ACADEMICA ORIGEN NO DEFINIDA";
         return false;
      } elseif (
         !isset($this->paData["CTITULO"]) or empty($this->paData["CTITULO"])
      ) {
         $this->pcError = "TITULO NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxGrabarNotificacion($p_oSql) {
      $lcJson = json_encode($this->paData);
      // Graba datos tabla eventos/notificaciones
      $lcSql = "SELECT p_a04devn_1('$lcJson')";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $laFila[0] = !$laFila[0]
         ? '{"ERROR": "ERROR DE BASE DE DATOS"}'
         : $laFila[0];
      $laData = json_decode($laFila[0], true);
      $this->pcError = @$laData["ERROR"];
      if (!empty($this->pcError)) {
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // Consulta de Notificaciones
   // --------------------------------------------------
   public function omConsultaNotificaciones() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxConsultaNotificaciones($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxConsultaNotificaciones($p_oSql) {
      $this->paUniAca[] = ["*", "* TODAS"];
      $lcAcaUni = $this->paData["CACAUNI"];
      $i = 0;
      if ($lcAcaUni == "00") {
         $lcSql = "SELECT CIDEVNO, CTITULO, CDESCRI,CACAUNI, CUNIACA,CLINK, cDesUni, cDesAca FROM v_a04devn_2 WHERE CESTADO ='$this->paEstado' ";
      } else {
         $lcSql = "SELECT CIDEVNO, CTITULO, CDESCRI,CACAUNI, CUNIACA,CLINK, cDesUni, cDesAca FROM v_a04devn_2 WHERE CESTADO ='$this->paEstado' AND cAcaUni = '$lcAcaUni' ";
      }
      // AND cAcaUni = '$lcAcaUni'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos[] = [
            "CIDEVNO" => $laFila[0],
            "CTITULO" => $laFila[1],
            "CDESCRI" => $laFila[2],
            "CACAUNI" => $laFila[3],
            "CUNIACA" => $laFila[4],
            "CLINK" => $laFila[5],
            "CDESUNI" => $laFila[6],
            "CDESACA" => $laFila[7],
         ];
      }

      return true;
   }

   // --------------------------------------------------
   // Update Estado de los  Eventos
   // --------------------------------------------------

   public function omUpdateEstadoNotificacion() {
      if (sizeof($this->paDatos) == 0) {
         $this->pcError = "NO HA MARCADO Eventos";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxUpdateEstadoNotificacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxUpdateEstadoNotificacion($p_oSql) {
      foreach ($this->paDatos as $laFila) {
         $cidevno = $laFila;
         $this->paData = ["CIDEVNO" => $cidevno, "CESTADO" => $this->paEstado];
         $lcJson = json_encode($this->paData);
         $lcSql = "SELECT p_a04devn_3('$lcJson')";
         $R1 = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($R1);
         $laFila[0] = !$laFila[0]
            ? '{"ERROR": "ERROR DE BASE DE DATOS"}'
            : $laFila[0];
         $laData = json_decode($laFila[0], true);
         $this->pcError = @$laData["ERROR"];
         if (!empty($this->pcError)) {
            return false;
         }
      }
      return true;
   }
   // --------------------------------------------------
   // Consulta de Tokens
   // --------------------------------------------------

   public function omConsultaTokens() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxConsultaTokens($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxConsultaTokens($p_oSql) {
      $this->paUniAca[] = ["*", "* TODAS"];
      $i = 0;
      $lcSql = "SELECT CTOKEN FROM v_s01dtkn"; ///AGREGAR WHERE
      $R1 = $p_oSql->omExec($lcSql);
      $this->devicetoken = [];
      while ($token = $p_oSql->fetch($R1)) {
         $i++;
         array_push($this->devicetoken, $token[0]);
      }
      if ($i == 0) {
         $this->pcError = "TOKENS NO DEFINIDOS";
         return false;
      }
      fxAlert(sizeof($this->devicetoken));
      return true;
   }

   // --------------------------------------------------
   // Enviar Notificaciones
   // --------------------------------------------------

   public function omEnviarNotificacion() {
      if (sizeof($this->paDatos) == 0) {
         $this->pcError = "NO HA MARCADO NOTIFICACIONES";
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxEnviarNotificacion($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxEnviarNotificacion($p_oSql) {
      foreach ($this->paDatos as $laFila) {
         $cidevno = $laFila;
         $lcSql = "SELECT cTitulo, cDescri, cLink, cUniAca FROM A04DEVN WHERE cIdEvNo = '$laFila'";
         $R1 = $p_oSql->omExec($lcSql);
         $laData = $p_oSql->fetch($R1);
         $i = 0;
         $laToken = null;
         if ($laData[3] == "00") {
            $lcSql = "SELECT cToken FROM V_S01DTKN  WHERE cesttkn = 'A'";
         } else {
            $lcSql = "SELECT cToken FROM V_S01DTKN WHERE cUniAca = '$laData[3]' and cesttkn = 'A'";
         }
         $R1 = $p_oSql->omExec($lcSql);
         $tokens = [];
         while ($laTmp = $p_oSql->fetch($R1)) {
            $i++;
            $laToken[] = [$laTmp[0]];
            array_push($tokens, $laTmp[0]);
         }
         if ($i == 0) {
            $lcSql = "INSERT INTO A04DLOG (cIdEvNo, cTexto, cCodUsu) VALUES ('$laFila', 'NO SE ENVIO MENSAJE POR NO HABER USUARIOS', '9999')";
            $this->pcError = "NO HAY USUARIO DE DESTINO";
            return;
            //  ejecutar!!!!
         }
         $lcLink = strlen($laData[2]) == 0 ? null : $laData[2];
         $loPush = new CPush($laData[0], $laData[1], $lcLink);
         $laPush = $loPush->getPush();
         $loFireBase = new CFirebase();
         $devicetoken = $tokens;
         $firebase = new CFirebase();
         $Ltoken = sizeof($devicetoken);
         $cont = 0;
         $resultados;
         $success = 0;
         $failure = 0;
         if ($Ltoken > 1000) {
            $newId = array_chunk($devicetoken, 1000);
            foreach ($newId as $inner_id) {
               $Ltoken = sizeof($inner_id);
               $cont = $Ltoken + $cont;
               //print_R($inner_id);
               $x = $firebase->send($inner_id, $laPush);
               $item = json_decode($x, true);
               $success = $item["success"] + $success;
               $failure = $item["failure"] + $failure;
            }
         } else {
            $x = $firebase->send($devicetoken, $laPush);
            $item = json_decode($x, true);
            $success = $item["success"] + $success;
            $failure = $item["failure"] + $failure;
         }
         if ($x) {
            //   UPDATE
            $this->paData = ["CIDEVNO" => $cidevno, "CESTADO" => "B"];
            $lcJson = json_encode($this->paData);
            $lcSql = "SELECT p_a04devn_3('$lcJson')";
            $R1 = $p_oSql->omExec($lcSql);
            $laFila = $p_oSql->fetch($R1);
            $laFila[0] = !$laFila[0]
               ? '{"ERROR": "ERROR DE BASE DE DATOS"}'
               : $laFila[0];
            $laData = json_decode($laFila[0], true);
            $this->pcError = @$laData["ERROR"];
            if (!empty($this->pcError)) {
               return false;
            }
         }
      }
      //$this->paDatos[]=[$laData[0],$success,$failure];
      $failure = 0;
      $success = $success / 2;
      $this->pafailure = $failure;
      $this->pasuccess = $success;
      return true;
   }

   public function getAllTokens() {
      $query = "SELECT ctoken FROM s01dtkn ";
      ($resultado = pg_query($this->con, $query)) or
         die("Error en la Consulta SQL");
      $numReg = pg_num_rows($resultado);
      /////////////////////
      $tokens = [];
      while ($token = pg_fetch_assoc($resultado)) {
         array_push($tokens, $token["ctoken"]);
      }

      return $tokens;
   }
}

?>
