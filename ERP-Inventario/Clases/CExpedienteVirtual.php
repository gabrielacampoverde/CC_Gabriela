<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Clases/CEmail2.php";
require_once "Libs/fpdf/fpdf.php";

class CExpedienteVirtual extends CBase {
   public $paData, $paDatos, $pcError, $paTipReq, $paEstReq, $paUsuCot, $paPeriod, $paRequer, $paTipCom, $paErrFil, $paFile;

   public function __construct()
   {
      parent::__construct();
      $this->paData = $this->paDatos = $this->pcError = $this->paEstReq = $this->paTipReq = $this->paUsuCot = $this->paPeriod = $this->paRequer = $this->paTipCom = $this->paFile = null;
      $this->paErrFil = array(
         0 => 'ARCHIVO FUE SUBIDO CON EXITO',
         1 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO',
         2 => 'EL ARCHIVO EXCEDE EL TAMAÑO MAXIMO DEFINIDO POR USUARIO',
         3 => 'EL ARCHIVO FUE PARCIALMENTE CARGADO',
         4 => 'EL ARCHIVO NO FUE CARGADO',
         6 => 'FALTA UNA CARPETA TEMPORAL',
         7 => 'ERROR AL ESCRIBIR EL ARCHIVO EN EL DISCO',
         8 => 'UNA EXTENSIÓN DE PHP DETUVO LA CARGA DEL ARCHIVO',
      );
   }

   public function omInitAsignacionRequerimientoCotizador() {
      $llOk = $this->mxValInitAsignacionRequerimientoCotizador();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValidarJefeLogistica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk) {
         $this->pcError = "HUBO UN ERROR AL CONECTAR CON LA BASE DE DATOS.";
         return false;
      }
      $llOk = $this->mxGetInitAsignacionRequerimientoCotizador($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         $this->pcError = "HUBO UN ERROR AL CARGAR LOS VALORES INICIALES";
         return false;
      }
      return $llOk;
   }
   protected function mxValInitAsignacionRequerimientoCotizador() {
      if (!isset($this->paData['CCODUSU'])) {
         $this->pcError = "CÓDIGO DE USUARIO INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxValidarJefeLogistica() {
      if (!isset($this->paData['CNIVEL']) OR $this->paData['CNIVEL'] != 'JL') {
         $this->pcError = "NIVEL DE USUARIO INVÁLIDO, EL USUARIO NO ES EL JEFE DE LOGISTICA.";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '02W') {
         $this->pcError = "CENTRO DE COSTOS DEL USUARIO ES INVÁLIDO, EL USUARIO DEBE PERTENECER A LOGISTICA.";
         return false;
      }
      return true;
   }

   protected function mxGetInitAsignacionRequerimientoCotizador($p_oSql) {

      #TRAER PERIODOS DE PEDIDOS
      $lcSql = "SELECT DISTINCT TO_CHAR(tGenera, 'YYYY') AS cPeriod FROM E01MREQ ORDER BY cPeriod DESC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY PERIODOS DEFINIDOS PARA REQUERIMIENTOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paPeriod[] = ['CPERIOD' => $laFila[0], 'CDESCRI' => "AÑO ".$laFila[0]];
      }

//      //LISTAR TIPOS DE REQUERIENTOS
//      $lcSql = "SELECT TRIM(CCODIGO), CDESCRI FROM V_S01TTAB WHERE CCODTAB = '075' AND CCODIGO IN ('B', 'S', 'D')";
//      $RS = $p_oSql->omExec($lcSql);
//      if (!$RS) {
//         $this->pcError = "HA OCURRIDO UN ERROR AL LISTAR LOS ESTADOS DEL REQUERIMIENTO";
//         return false;
//      }
//      while($laFila = $p_oSql->fetch($RS)) {
//         $this->paTipReq[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
//      }
//
//      //LISTAR ESTADOS DE LOS REQUERIENTOS
//      $lcSql = "SELECT TRIM(CCODIGO), CDESCRI FROM V_S01TTAB WHERE CCODTAB = '077' AND CCODIGO IN ('A', 'C', 'O', 'X')";
//      $RS = $p_oSql->omExec($lcSql);
//      if (!$RS) {
//         $this->pcError = "HA OCURRIDO UN ERROR AL LISTAR LOS ESTADOS DEL REQUERIMIENTO";
//         return false;
//      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paEstReq[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      //LISTAR COTIZADORES
      $lcSql = "SELECT TRIM(A.CCODUSU), A.CNOMBRE, A.CNRODNI, A.CEMAIL FROM V_S01TUSU_1 A
                 INNER JOIN S01PCCO B ON A.CCODUSU = B.CCODUSU INNER JOIN V_S01TCCO_1 C ON B.CCENCOS = C.CCENCOS
                 WHERE B.CCENCOS = '02W' AND A.CESTADO = 'A' AND A.CNIVEL IN ('CO', 'JL')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError  = "HA OCURRIDO UN ERROR AL LISTAR COTIZADORES.";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paUsuCot[] = ['CCODUSU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CNRODNI' => $laFila[2], 'CEMAIL' => $laFila[3]];
      }
      return true;
   }
   public function omBuscarRequerimientoCotizador() {
      $llOk = $this->mxValBuscarRequerimientoCotizador();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValidarJefeLogistica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk) {
         $this->pcError = "HUBO UN ERROR AL CONECTAR CON LA BASE DE DATOS.";
         return false;
      }
      $llOk = $this->mxGetBuscarRequerimientoCotizador($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         $this->pcError = "HUBO UN ERROR AL CARGAR LOS VALORES INICIALES";
         return false;
      }
      return $llOk;
   }
   protected function mxValBuscarRequerimientoCotizador() {
      if (!isset($this->paData['CPERIOD']) OR strlen(trim($this->paData['CPERIOD'])) != 4) {
         $this->pcError = "PERIODO INVÁLIDO";
         return false;
      }
      return true;
   }
   protected function mxGetBuscarRequerimientoCotizador($p_oSql) {
      $lcSql = "SELECT A.cIdRequ, B.cDescri, A.cDescri, C.cDescri as cDesTip, TO_CHAR(A.TGENERA,'YYYY-MM-DD HH24:MI') AS tGenera, trim(A.cComDir), A.mDatos
                     FROM E01MREQ A INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos LEFT JOIN V_S01TTAB C ON C.cCodTab = '075' AND C.cCodigo = A.cTipo
                     LEFT JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda INNER JOIN P02MACT E ON E.cIdActi = A.cIdActi
                  WHERE A.cEstado IN ('A','B') AND A.cTipo NOT IN ('R','A','E','M') AND E.cTipAct IN ('A', 'P') AND A.cUsuCot in ('9999', 'U666')
                        AND TO_CHAR(A.tGenera, 'YYYY') = '{$this->paData['CPERIOD']}'";
//      if ($this->paData['CUSUCOT'] == 'TTTT') {
//         $lcSql = $lcSql." AND A.cUsuCot = '9999'";
//      } else {
//         $lcSql = $lcSql." AND A.cUsuCot = '{$this->paData['CUSUCOT']}'";
//      }
      if (trim($this->paData['CBUSREQ']) != "") {
         $lcSql = $lcSql." AND (A.cIdRequ like '%{$this->paData['CBUSREQ']}%' OR B.cDescri LIKE '%{$this->paData['CBUSREQ']}%')";
      }
      $lcSql = $lcSql." ORDER BY A.tGenera, A.cCenCos";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "NO SE ENCONTRO RESULTADOS";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paRequer[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCRI' => $laFila[2], 'CTIPO' => $laFila[3],
            'TGENERA' => $laFila[4], 'CCOMDIR' => ($laFila[5] == 'S') ? 'SI' : 'NO', 'MDATOS' => $laFila[6]];
      }
      $lcObserv = '';
      $lMDatos = '';
      foreach ($this->paRequer as &$el) {
         $lMDatos = json_decode($el['MDATOS'], true);
         $el['COBSERV'] = (isset($lMDatos['COBSCOTASG'])) ? $lMDatos['COBSCOTASG'] : null;
      }
      return true;
   }
   public function omAsignarRequerimientoCotizador() {
      $llOk = $this->mxValAsignarRequerimientoCotizador();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValidarJefeLogistica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = 'HUBO UN ERRROR AL CONECTAR A LA BASE DE DATOS, CONTACTE CON SOPORTE DEL ERP.';
         return false;
      }
      $llOk = $this->mxAsignarRequerimientoCotizador($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAsignarRequerimientoCotizador() {
      if (!isset($this->paData['AREQSEL']) OR count($this->paData['AREQSEL']) <= 0){
         $this->pcError  = 'NO SE SELECCIONARON REQUERIMIENTOS PARA SU ASIGNACIÓN.';
         return false;
      } elseif ( !isset($this->paData['CUSUCOT']) OR strlen($this->paData['CUSUCOT']) != '4' OR $this->paData['CUSUCOT'] == 'TTTT') {
            $this->pcError = 'NO SE SELECCIONo UN COTIZADOR VÁLIDO.';
            return false;
      }
      return true;
   }
   protected function mxAsignarRequerimientoCotizador($p_oSql) {
      $lcSql = "SELECT COUNT(*) FROM V_S01TUSU_1 WHERE CCODUSU = '{$this->paData['CUSUCOT']}';";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL ASIGNAR EL(LOS REQUERIMIENTOS AL COTIZADOR.";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == 0) {
         $this->pcError = "EL USUARIO PARA COTIZADOR ES INVÁLIDO";
         return false;
      }
      foreach ($this->paData['AREQSEL'] as $loReq) {
         $lcSql = "SELECT count(*) FROM E01MREQ WHERE CIDREQU = '{$loReq}' AND CESTADO != 'X'";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = "HUBO UN ERROR AL ASIGNAR EL(LOS REQUERIMIENTOS AL COTIZADOR.";
            return false;
         }
         $laFila = $p_oSql->fetch($RS);
         if ($laFila[0] == 0) {
            $this->pcError = "EL REQUERIMIENTO ES INVÁLIDO";
            return false;
         }
      }
      $ldDia = date('Y-m-d H:i:s');
      foreach ($this->paData['AREQSEL'] as $loReq) {
         $lcSql = "UPDATE E01MREQ SET CUSUCOT = '{$this->paData['CUSUCOT']}', dAsgCot = '{$ldDia}' WHERE CIDREQU = '{$loReq}';";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = "HUBO UN ERROR AL ASIGNAR EL(LOS REQUERIMIENTOS AL COTIZADOR.";
            return false;
         }
      }
      return true;
   }

   public function omBuscarCotizacionesVigentesProveedor() {
      $llOk = $this->mxBuscarCotizacionesVigentesProveedor();
      if(!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxGetBuscarCotizacionesVigentesProveedor($loSql);
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxBuscarCotizacionesVigentesProveedor() {
      if (!isset($this->paData['CNRORUC']) or strlen($this->paData['CNRORUC']) != 11) {
         return false;
      }
      return true;
   }
   protected function mxGetBuscarCotizacionesVigentesProveedor($p_oSql) {
      $lcSql = "SELECT COUNT(*) FROM E01PCOT A  INNER JOIN E01MCOT B ON A.cidcoti = B.cidcoti
                  WHERE (NOW() AT TIME ZONE 'America/Lima') BETWEEN B.tinicio AND B.tfinali AND A.cnroruc = '{$this->paData['CNRORUC']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['NCOTVIG'] = $laFila[0];
      return true;
   }

   // LISTA ORDENES EN ESTADO CONFORME (PARA CARGAR LOS COMPROBANTE EN FORMATO PDF)
   public function omBuscarOrdenesPendientesComprobantes() {
      $llOk = $this->mxValBuscarOrdenesPendientesComprobantes();
      if(!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxBuscarOrdenesPendientesComprobantes($loSql);
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValBuscarOrdenesPendientesComprobantes() {
      if (!isset($this->paData['CNRORUC']) or strlen($this->paData['CNRORUC']) != 11) {
         return false;
      }
      return true;
   }
   protected function mxBuscarOrdenesPendientesComprobantes($p_oSql) {
      //LISTAR ORDENES PENDIENTES DE PAGO
      $lcSql = "SELECT A.cIdOrde, A.dGenera, A.cCodAnt, A.cPeriod, B.cDescri, C.cDescri, A.nMonto, count(D.cIdOrde)  FROM E01MORD A
                     LEFT OUTER JOIN E02DFAC D ON D.cIdOrde = A.cIdOrde
                     LEFT OUTER JOIN V_S01TTAB B ON A.cTipo = B.cCodigo AND B.cCodTab = '075'
                     LEFT OUTER JOIN V_S01TTAB C ON A.cMoneda = C.cCodigo AND C.cCodTab = '007'
                     WHERE A.cNroRuc = '{$this->paData['CNRORUC']}' AND A.cEstado in ('B','F')
                     GROUP BY A.cIdOrde, A.dGenera, A.cCodAnt, A.cPeriod, B.cDescri, C.cDescri, D.cidorde";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         return false;
      }
      while($laFila = $p_oSql->fetch($RS) ) {
         $this->paDatos['AORDCOM'][] = ['CIDORDE' => $laFila[0], 'DGENERA' => $laFila[1], 'CORDNUM' => $laFila[2], 'CPERIOD' => $laFila[3],
            'CTIPO' => $laFila[4], 'CMONEDA' => $laFila[5], 'NMONTO' => $laFila[6], 'NCOMPRO' => $laFila[7]];
      }
      $lcSql = "select TRIM(cCodigo), cDescri from s01ttab where ccodtab = '087' AND CCODIGO IN ('00', '01', '02', '03', '09', '31')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         return false;
      }
      while($laFila = $p_oSql->fetch($RS) ) {
         $this->paTipCom[] = ['CCODIGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }

   /**
    * CREACION 18-04-2023 - GCQ
    * CARGAR ARCHIVO AL SERVIDOR DE COMPROBANTES POR PARTE DE LOS PROVEEDORES
    */
   public function omCargarComprobanteOrdenProveedor() {
      $llOk = $this->mxValCargarComprobanteOrdenProveedor();
      if(!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxCargarComprobanteOrdenProveedor($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      $llOk = $this->mxFileCargarComprobanteOrdenProveedor();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValCargarComprobanteOrdenProveedor() {
      if (!isset($this->paData['CNRORUC']) or strlen($this->paData['CNRORUC']) != 11) {
         $this->pcError = "NÚMERO DE RUC DEL PROVEEDOR ES INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['CIDORDE']) or strlen($this->paData['CIDORDE']) != 8) {
         $this->pcError = "HA OCURRIDO UN ERROR, NUMERO DE ORDEN INVÁLIDO,";
         return false;
      } elseif (!isset($this->paData['CTIPCOM']) or strlen($this->paData['CTIPCOM']) != 2) {
         $this->pcError = "TIPO DE COMPROBANTE INVÁLIDO.";
         return false;
      }
      return true;
   }
   protected function mxCargarComprobanteOrdenProveedor($p_oSql) {
      //Validar que cidorde exista
      $lcSql = "SELECT COUNT(*) FROM E01MORD WHERE CIDORDE = '{$this->paData['CIDORDE']}' AND CESTADO != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL ID DE LA ORDEN.';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == 0) {
         $this->pcError = 'ERROR AL CONSULTAR EL NUMERO DE ORDEN PARA SU CARGA EN EL SERVIDOR..';
         return false;
      }
      //Validar que numero de ruc del proveedor exista
      $lcSql = "SELECT COUNT(*) FROM S01MPRV WHERE CNRORUC = '{$this->paData['CNRORUC']}' AND CESTADO != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL RUC DEL PROVEEDOR.';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == 0) {
         $this->pcError = 'ERROR AL CONSULTAR EL RUC PARA SU CARGA EN EL SERVIDOR..';
         return false;
      }
      $lcSql = "SELECT cCorrel FROM E02DFAC ORDER BY CCORREL DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL RUC DEL PROVEEDOR.';
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->paData['CCORREL'] = '0000';
      } else {
         $laFila = $p_oSql->fetch($RS);
         $this->paData['CCORREL'] = $laFila[0];
      }
      $this->paData['CCORREL'] = fxCorrelativo($this->paData['CCORREL']);
      $loSql = "INSERT INTO E02DFAC (cCorrel, cIdOrde, cTipCom, cUsuCod, tModifi) 
                VALUES ('{$this->paData['CCORREL']}', '{$this->paData['CIDORDE']}', '{$this->paData['CTIPCOM']}', 'U666', NOW())";
      $RS = $p_oSql->omExec($loSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL GUARDAR EL COMPROBANTE';
         return false;
      }
      return true;
   }

   protected function mxFileCargarComprobanteOrdenProveedor() {
      if ($this->paFile['error'] == 0) {
         if ($this->paFile['size'] > 5242880) {
            $this->pcError = 'El tamaño maximo de los archivos debe ser de 5MB.';
            return false;
         }
         $lcPath = "Docs/Logistica/Comprobante/TMP/";
         if (!is_dir($lcPath)) {
            $perm = "0777";
            $modo = intval($perm, 8);
            mkdir($lcPath, $modo);
            chmod($lcPath, $modo);
         }
         $lcFilePath = $lcPath.$this->paData['CCORREL'].'.pdf';
         $loOk = move_uploaded_file($this->paFile['tmp_name'], $lcFilePath);
         if (!$loOk) {
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO CORRECTAMENTE";
            return false;
         }
      } else {
         $this->pcError = 'ESPECIFICACIONES TECNICAS. '.$this->paErrFil[$this->paFile['error']];
         return false;
      }
      return true;
   }

   /**
    * CREACION 18-04-2023 - GCQ
    * AGREGAR ARCHIVO DE COMPROBANTES PARA EXPEDIENTE VIRTUAL (VISTA DE EXPEDIENTE VIRTUAL)
   */
   public function omCargarComprobanteOrdenProveedorCotizador() {
      $llOk = $this->mxValCargarComprobanteOrdenProveedorCotizador();
      if(!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxCargarComprobanteOrdenProveedorCotizador($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->mxFileCargarComprobanteOrdenProveedorCotizador();
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValCargarComprobanteOrdenProveedorCotizador()
   {
      if (!isset($this->paData['CIDCOMP']) or strlen($this->paData['CIDCOMP']) != 8) {
         $this->pcError = "Ha ocurrido un error, contacte con soporte del erp. CIDCOMP inválido.";
         return false;
      }
      return true;
   }
   protected function mxCargarComprobanteOrdenProveedorCotizador($p_oSql) {
      //Validar que cidcomp exista
      $lcSql = "SELECT COUNT(*) FROM E01MFAC WHERE CIDCOMP = '{$this->paData['CIDCOMP']}' AND CESTADO != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL ID DEL COMPROBANTE.';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == 0) {
         $this->pcError = 'ERROR AL CONSULTAR EL COMPROBANTE PARA SU CARGA EN EL SERVIDOR..';
         return false;
      }
      return true;
   }
   protected function mxFileCargarComprobanteOrdenProveedorCotizador() {
      if ($this->paFile['error']['CCOMPRO'] == 0) {
         if ($this->paFile['size']['CCOMPRO'] > 5242880) {
            $this->pcError = 'El tamaño maximo de los archivos debe ser de 5MB.';
            return false;
         }
         $lcPath = "Docs/Logistica/Comprobante/";
         if (!is_dir($lcPath)) {
            $perm = "0777";
            $modo = intval($perm, 8);
            mkdir($lcPath, $modo);
            chmod($lcPath, $modo);
         }
         $lcFilePath = $lcPath.$this->paData['CIDCOMP'].'.pdf';
         $loOk = move_uploaded_file($this->paFile['tmp_name']['CCOMPRO'], $lcFilePath);
         if (!$loOk) {
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO CORRECTAMENTE";
            return false;
         }
      } else {
         $this->pcError = 'ESPECIFICACIONES TECNICAS. '.$this->paErrFil[$this->paFile['error']];
         return false;
      }
      return true;
   }


   /**
    * CREACION 18-04-2023 - GCQ
    * LISTAR COMPROBANTES CARGADOS POR EL PROVEEDOR PARA UNA ORDEN DE COMPRA
    */
   public function omListarComprobantesOrden() {
      $llOk = $this->mxValListarComprobantesOrden();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxlListarComprobantesOrden($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValListarComprobantesOrden() {
      if (!isset($this->paData['CIDORDE']) OR strlen($this->paData['CIDORDE']) != 8) {
         $this->pcError = 'ID DE LA ORDEN INVÁLIDO, CONTACTE CON SOPORTE DEL ERP';
         return false;
      }
      return true;
   }
   protected function mxlListarComprobantesOrden($p_oSql) {
      $lcSql = "SELECT A.ccorrel, B.cDescri, A.cEstado, D.cDescri, A.cTipcom FROM E02DFAC A
                  LEFT OUTER JOIN S01TTAB B ON B.cCodTab = '087' AND A.cTipCom = B.cCodigo
                  INNER JOIN S01TTAB D ON D.cCodTab = '353' AND A.cEstado = D.cCodigo AND D.cTipReg = '1'
                  INNER JOIN E01MORD C ON A.cIdOrde = C.cIdOrde
                  WHERE A.cIdOrde = '{$this->paData['CIDORDE']}' and C.cEstado != 'X';";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'HA OCURRIDO UN ERROR AL LISTAR LOS COMPROBANTES, CONTACTE CON SOPORTE DEL ERP.';
         return false;
      }
      $i = 0;
      while($laFila = $p_oSql->fetch($RS)) {
         $lcPath = "Docs/Logistica/Comprobante/TMP/{$laFila[0]}.pdf";
         $this->paData['ACONTENIDO'][] = ['CCORREL' => $laFila[0], 'CCOMPRO' => $laFila[1], 'CESTADO' => $laFila[2],
            'CARCHIV' => ((file_exists($lcPath))? 'S' : 'N'), 'CESTDES' => $laFila[3], 'CTIPCOM' => $laFila[4]];
         $i++;
      }
      $this->paData['NTOTELM'] = $i;
      return true;
   }


   /**
    * CREACION 18-04-2023 - GCQ
    * LISTAR COMPROBANTES CARGADOS POR EL PROVEEDOR PARA UNA ORDEN DE COMPRA
    */
   public  function omCargarComprobanteOrdenCotizador() {
      $llOk = $this->mxValCargarComprobanteOrdenCotizador();
      if(!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxCargarComprobanteOrdenCotizador($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->mxFileCargarComprobanteOrdenCotizador();
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValCargarComprobanteOrdenCotizador() {
      if (!isset($this->paData['CIDORDE']) or strlen($this->paData['CIDORDE']) != 8) {
         $this->pcError = "HA OCURRIDO UN ERROR, NUMERO DE ORDEN INVÁLIDO,";
         return false;
      } elseif (!isset($this->paData['CUSUCOD']) or strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = "TIPO DE COMPROBANTE INVÁLIDO.";
         return false;
      }
      return true;
   }
   protected function mxCargarComprobanteOrdenCotizador($p_oSql) {
      //Validar que cidorde exista
      $lcSql = "SELECT COUNT(*) FROM E01MORD WHERE CIDORDE = '{$this->paData['CIDORDE']}' AND CESTADO != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL ID DE LA ORDEN.';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == 0) {
         $this->pcError = 'ERROR AL CONSULTAR EL NUMERO DE ORDEN PARA SU CARGA EN EL SERVIDOR..';
         return false;
      }
      //Validar que el cotizador exista
      $lcSql = "SELECT TRIM(CNIVEL) FROM S01TUSU WHERE CCODUSU = '{$this->paData['CUSUCOD']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL CÓDIGO DE USUARIO.';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] != 'CO') {
         $this->pcError = 'EL USUSARIO NO TIENE PERMISOS PARA CARGAR EL ARCHIVO';
         return false;
      }
      $lcSql = "SELECT cCorrel FROM E02DFAC ORDER BY CCORREL DESC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL CONSULTAR EL RUC DEL PROVEEDOR.';
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->paData['CCORREL'] = '0000';
      } else {
         $laFila = $p_oSql->fetch($RS);
         $this->paData['CCORREL'] = $laFila[0];
      }
      $this->paData['CCORREL'] = fxCorrelativo($this->paData['CCORREL']);
      $loSql = "INSERT INTO E02DFAC (cCorrel, cIdOrde, cTipCom, cUsuCod, tModifi) 
                VALUES ('{$this->paData['CCORREL']}', '{$this->paData['CIDORDE']}', '{$this->paData['CTIPCOM']}', 'U666', NOW())";
      $RS = $p_oSql->omExec($loSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL GUARDAR EL COMPROBANTE';
         return false;
      }
      return true;
   }

   protected function mxFileCargarComprobanteOrdenCotizador() {
      if ($this->paFile['error'] == 0) {
         if ($this->paFile['size'] > 5242880) {
            $this->pcError = 'El tamaño maximo de los archivos debe ser de 5MB.';
            return false;
         }
         $lcPath = "Docs/Logistica/Comprobante/TMP/";
         if (!is_dir($lcPath)) {
            $perm = "0777";
            $modo = intval($perm, 8);
            mkdir($lcPath, $modo);
            chmod($lcPath, $modo);
         }
         $lcFilePath = $lcPath.$this->paData['CCORREL'].'.pdf';
         $loOk = move_uploaded_file($this->paFile['tmp_name'], $lcFilePath);
         if (!$loOk) {
            $this->pcError = "NO SE PUDO SUBIR EL ARCHIVO CORRECTAMENTE";
            return false;
         }
      } else {
         $this->pcError = 'ESPECIFICACIONES TECNICAS. '.$this->paErrFil[$this->paFile['error']];
         return false;
      }
      return true;
   }

   public function fxCrearRequerimientoDicri() {
      $this->mxSetParamsCrearRequerimientoDicri();
   }
   protected function mxSetParamsCrearRequerimientoDicri() {
      $this->paData = ['CIDREQU' => '*', 'CCOMDIR' => 'S', 'CDESTIN' => 'I', 'CUSUENC' => '0000', 'MOBSANT' => '',  'CESPTEC' => '', 'CDOCREF' => '', 'CTIPO' => 'S',
         'CMONEDA' => 'S', 'CIDACTI' => '00000000', 'CNRODOC' => 'DICRI-001', 'CNRORUC' => '20127249009', 'CNROCOM' => 'F001-00002232', 'NMONTO' => 1200.00, 'DFECCOM' => '2023-04-25',
         'CDESCRI' => 'CONVENIO INTERNACIONAL DICRI ALUMNOS', 'DINIEVE' => '', 'DFINEVE' => '', 'MOBSERV' => '', "MDATOS" => [
            'CCODART' => '63SV0341', 'CDESDET' => 'CONVENIO INTERCAMBIO ALUMNO JOSE DOMINGO PEREZ', 'NPREREF' => 1200.00,
            'NCANTID' => 1
         ]
         ];
      $lo->paData = $laData;
      $lo->paData['MDATOS'] = $_SESSION['paDatos'];
      $lo->paData['CESTADO'] = $_SESSION['CESTADO'];
      $lo->paData['CCODUSU'] = $_SESSION['GCCODUSU'];
      $lo->paData['CUSUCOD'] = $_SESSION['GCCODUSU'];
      $lo->paData['CCENCOS'] = $_SESSION['GCCENCOS'];
   }


   public function omInitEvaluarRequerimientosEspecialesViceAdmin() {
      $llOk = $this->mxValInitEvaluarRequerimiemtoViceAdmin();
      if (!$llOk){
         return false;
      }
      return true;
   }
   protected function mxValInitEvaluarRequerimiemtoViceAdmin() {
      if (!isset($this->paData['CNIVEL']) or ($this->paData['CNIVEL'] != 'VD')) {
         $this->pcError = 'EL NIVEL DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) or ($this->paData['CCENCOS'] != '02Q')) {
         $this->pcError = 'EL CENTRO DE COSTOS DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      }
      return true;
   }

   public function omBuscarRequerimientosEvaluacionConformidadVA() {
      $llOk = $this->mxValInitEvaluarRequerimiemtoViceAdmin();
      if (!$llOk){
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarRequerimientosEvaluacionConformidadVA($loSql);
      if(!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxBuscarRequerimientosEvaluacionConformidadVA($p_oSql) {
      // VALIDAR QUE REQUERIMIENTOS NO ESTEN ANULADOS CON VALOR 00000000
      $lcSql = "SELECT A.CIDPAQU, A.CDESCRI, D.CDESCRI AS CDESCCO, A.CESTADO, C.CDESCRI AS CESTDES, A.mdatos::json#>>'{0,TGENERA}' AS TGENERA, A.mdatos::json#>>'{0,CNRODNI}' AS CNRODNI FROM D05MPAQ A
                  LEFT OUTER JOIN V_S01TTAB C ON A.CESTADO = C.CCODIGO AND C.CCODTAB = '353'
                  LEFT OUTER JOIN V_S01TCCO_1 D ON A.ccencos = D.ccencos
                  WHERE A.CESTADO = 'B' ORDER BY A.CIDPAQU";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laTmp = [];
      $lmDatos = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $laTmp = ['CIDPAQU' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESCCO' => $laFila[2], 'CESTADO' => $laFila[3],
            'CESTDES' => $laFila[4], 'TGENERA' => $laFila[5], 'CNRODNI' => $laFila[6]];
         $lcSql = "SELECT CNOMBRE FROM V_S01TUSU_1 WHERE CNRODNI = '{$laFila[6]}' and cestado = 'A' LIMIT 1";
         $RS1 = $p_oSql->omExec($lcSql);
         if (!$RS1) {
            $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
            return false;
         }
         $this->paDatos[] = array_merge($laTmp, ['CNOMRES' => $p_oSql->fetch($RS1)[0]]);
      }
      return true;
   }

   function omRevisarRequerimientoPendienteAprobacionVA() {
      $llOk = $this->mxValRevisarRequerimientoPendienteAprobacionVA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxRevisarRequerimientoPendienteAprobacionVA($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValRevisarRequerimientoPendienteAprobacionVA() {
      if (!isset($this->paData['CIDPAQU'])) {
         $this->pcError = "EL ID DEL PAQUETE NO ES VÁLIDO.";
         return false;
      }
      return true;
   }
   protected function mxRevisarRequerimientoPendienteAprobacionVA($p_oSql) {
      //CONSULTA CABECERA DCRRII PAQUETE
      $lcSql = "SELECT A.CIDPAQU, A.CDESCRI, A.MDATOS::JSON->>'TGENERA' AS TGENERA, A.MDATOS::JSON->>'MOBSERV' AS MOBSERV, A.COBSERV, B.CDESCRI AS CESTADODES, A.CESTADO 
                  FROM D05MPAQ A
                  INNER JOIN V_S01TTAB B ON B.CCODTAB = '353' AND A.CESTADO = B.CCODIGO
                  WHERE CIDPAQU = '{$this->paData['CIDPAQU']}';";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = ['CIDPAQU' => $laFila[0], 'CDESCRI' => $laFila[1], 'TGENERA' => $laFila[2], 'MOBSERV' => $laFila[3], 'COBSERV' => $laFila[4], 'CESTADODES' => $laFila[5], 'CESTADO' => $laFila[6]];
      //LISTAR DETALLE PAQUETE
      $lcSql = "SELECT A.cIdComp, A.cCodigo, A.cIdAlum, A.cIdRequ, C.cDescri, A.cNroCom, A.mEnlace, A.mDatos, B.cCodAlu, B.cIdPais, B.cNombre, B.cNroDoc, B.cTipDoc, A.cEstado, E.cNRoRuc, E.cRazSoc FROM D05MCOM A
                  INNER JOIN D05MALU B ON A.cidalum = B.cidalum
                  INNER JOIN d05MPRV D ON A.cCodigo = D.cCodigo
                  LEFT OUTER JOIN S01TTAB C ON C.cCodigo = A.cTipCom AND trim(C.cCodTab) = '087'
                  INNER JOIN S01MPRV E ON E.CNRORUC = D.CNRORUC
                  WHERE cIdPaqu = '{$this->paData['CIDPAQU']}' AND A.CESTADO <> 'X' ORDER BY E.cNroRuc";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO AL CONSULTAR EN LA BASE DE DATOS, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      $laTmpDatos = [];
      $lmDatos = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $lcPath = "Docs/Logistica/{$laFila[3]}.pdf";
         $laTmpDatos = ['CIDCOMP' => $laFila[0], 'CCODIGO' => $laFila[1], 'CIDALUM' => $laFila[2], 'CIDREQU' => $laFila[3], 'CTIPCOM' => $laFila[4],
            'CNROCOM' => $laFila[5], 'MENLACE' => $laFila[6], 'MDATOS' => $laFila[7], 'CCODALU' => $laFila[8], 'CIDPAIS' => $laFila[9],
            'CNOMBRE' => str_replace('/', ' ', $laFila[10]), 'CNRODOC' => $laFila[11], 'CTIPDOC' => $laFila[12], 'CNRORUC' => $laFila[14], 'CRAZSOC' => str_replace('/', ' ',$laFila[15])];
         $lmDatos = json_decode($laTmpDatos['MDATOS'], true);
         $this->paDatos[] = array_merge($laTmpDatos, ['DFECCOM' => $lmDatos['DFECCOM'], 'NMONTO' => $lmDatos['NMONTO'],
            'CARCHIV' => ((file_exists($lcPath))? 'S' : 'N')]);
      }

      //OBTENCION DE HISTORICO
      $lcSql = "select value->>'CNRODNI', value->>'CSECUEN', value->>'TGENERA', value->>'MOBSERV', value->>'CSECUEN' 
                FROM json_array_elements((SELECT mdatos from d05mpaq where cidpaqu = '{$this->paData['CIDPAQU']}')::JSON);";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      $laTmpHistor = [];
      while($laFila = $p_oSql->fetch($RS)) {
         if($laFila[1] == 'RRHH') {
            $this->paData['MENLACE'] = $laFila[3];
         }
         $laTmpHistor = ['CNRODNI' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'MOBSERV' => $laFila[3], 'CSECUEN' => $laFila[4]];
         $lcSql = "SELECT CNOMBRE FROM V_S01TUSU_1 WHERE CNRODNI = '{$laTmpHistor['CNRODNI']}'";
         $RS2 = $p_oSql->omExec($lcSql);
         if (!$RS2) {
            $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
            return false;
         }
         $laFila2 = $p_oSql->fetch($RS2);
         $this->paData['AHISTOR'][] = array_merge($laTmpHistor, ['CNOMBRE' => str_replace('/', ' ', $laFila2[0])]);
      }
      return true;
   }
   public function omAprobarPaqueteVA() {
      $llOk = $this->mxValAprobarPaqueteVA();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAprobarPaqueteVA($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->omEnviarCorreoAprobarRequerimientoPaqueteDcriViceAdmin($loSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ENVIAR EL CORREO , CONTACTE CON SOPORTE DEL ERP.";
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAprobarPaqueteVA() {
      if (!isset($this->paData['CIDPAQU'])) {
         $this->pcError = "EL ID DEL PAQUETE NO ES VÁLIDO.";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) or strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "EL CODIGO DE USUARIO ES INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNIVEL']) or $this->paData['CNIVEL'] != 'VD') {
         $this->pcError = "   EL NIVEL DE PERMISOS DEL USUARIO NO PERMITE REALIZAR LA ACCIÓN.";
         return false;
      }
      return true;
   }
   protected function mxAprobarPaqueteVA($p_oSql) {
      //VALIDA QUE LOS REQUERIMIENTOS ESTEN CREADOS
      $lcSql = "SELECT C.cIdRequ FROM D05MPAQ A
                  INNER JOIN D05MCOM B ON A.cidpaqu = B.cidpaqu
                  INNER JOIN E01MREQ C ON B.cidrequ = C.cidrequ
                  WHERE A.cidpaqu = '{$this->paData['CIDPAQU']}' AND C.cIdRequ != '00000000' AND B.CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $llOk = $this->mxAprobarRequerimientoPaqueteDcriViceAdmin($p_oSql, $laFila[0]);
         if (!$llOk) {
            return false;
         }
      }
      //DATOS DE USUARIO
      $lcSql = "SELECT CNRODNI FROM V_S01TUSU_1 WHERE CCODUSU = '{$this->paData['CCODUSU']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP [DATOS USUARIO].";
         return false;
      }
      $this->paData['CNRODNI'] = $p_oSql->fetch($RS)[0];

      //MDATOS DE PAQUETE PARA LA ASIGNACION DE OBSERVACIONES
      $lcSql = "SELECT MDATOS FROM D05MPAQ WHERE CIDPAQU = '{$this->paData['CIDPAQU']}'";
      $RS1 = $p_oSql->omExec($lcSql);
      if (!$RS1 or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP [OBSERVACIONES].";
         return false;
      }
      $laFilaPaq = $p_oSql->fetch($RS1);
      $lmDatos = $laFilaPaq[0];
      $lmDatos = json_decode($lmDatos, true);
      $lcDate = date('Y-m-d H:i:s');
      $lmDatos[] = ['CNRODNI' => $this->paData['CNRODNI'], 'CSECUEN' => 'VICERECTORADO ADMINISTRATIVO', 'TGENERA' => $lcDate,
         'MOBSERV' => 'APROBADO Y ENVIADO A LOGISITCA PARA SU REVISIÓN.'];
      $lmDatos = json_encode($lmDatos, true);

      $lcSql = "UPDATE D05MPAQ SET CESTADO = 'C', mdatos = '{$lmDatos}', cusucod = '{$this->paData['CCODUSU']}', TMODIFI = now() WHERE CIDPAQU = '{$this->paData['CIDPAQU']}'";
      $RS2 = $p_oSql->omExec($lcSql);
      if (!$RS2 or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxAprobarRequerimientoPaqueteDcriViceAdmin($p_oSql, $p_cIdRequ) {
      //OBTENCION DEL NUMERO DE RUC DEL PROVEEDOR
      $laData = [];
      $lcSql = "SELECT CNRORUC FROM E01DCOM WHERE CIDREQU = '{$p_cIdRequ}';";
      $RS = $p_oSql->omExec($lcSql);;
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "EL REQUERIMIENTO CON ID ".$p_cIdRequ.' ES INVÁLIDO';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laData = array_merge($this->paData, ['CNRORUC' => $laFila[0]]);
      $lcFecha = date('Y-m-d');
      // OBTENCION DE DETALLE REQUERIMIENTO PARA LA CREACION DE LA ORDEN DE SERVICIO
      $lcSql = "SELECT NSERIAL, CDESCRI, NCANTID, NPRECIO FROM E01DREQ WHERE CIDREQU = '{$p_cIdRequ}' AND CESTADO != 'X' limit 1";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laData = array_merge($laData, [ 'CIDREQU' => $p_cIdRequ, 'NTIEENT' => '0', 'CTIPFPA'=>'07', 'NCANHOJ' => '1',
         'MOBSERV' => "{$lcFecha}: SE GIRA LA PRESENTE ORDEN CON LA AUTORIZACION DEL VICERRECTOR ADMINISTRATIVO CON FECHA {$lcFecha}",
         'MDATOS' => [['NSERIAL' => $laFila[0], 'NCANTID' => $laFila[2], 'NPREART' => $laFila[3]]]]);
      $lcJson = json_encode($laData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MORD_301('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      $lcRes = json_decode($laFila[0], true);
      if (!empty($lcRes[0]['ERROR'])) {
         $this->pcError = $lcRes[0]['ERROR'];
         return false;
      }

      //FIRMA DE ORDEN POR PARTE DE VICE ADMINISTRATIVO
      $lcIdOrde = $lcRes[0]['CIDORDE'];
      $lcSql = "INSERT INTO E01DFIR (cidorde, ccodusu, cnivel, cestado, tfirma, cusucod, tmodifi)
                    values  ('{$lcIdOrde}', '1015', 'VD', 'A', NOW(), '1015', NOW())";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }

      //ACTUALIZAR DATOS DE ORDEN
      $lcSql = "UPDATE E01MORD SET DRECVIC = NOW(), denvpre = NOW(), DENVVIC = NOW(), CESTADO = 'F' WHERE CIDORDE = '{$lcIdOrde}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL, NO SE PUDO FIRMAR LAS ORDENES.';
         return false;
      }
      return true;
   }
   public function omEnviarCorreoAprobarRequerimientoPaqueteDcriViceAdmin($p_oSql) {
      $llOk = $this->mxGetDataEnviarCorreoPaquetesViceAdminAPresupuesto($p_oSql);
      if (!$llOk) {
         return false;
      }
      $lo = new CEmail();
      $llOk = $lo->omConnect();

      $lcBody = "<ul style='margin: 0.5em'>";
      foreach ($this->paDatos as $item) {
         $lcRow = "<li style='font-weight: normal; font-size: 14px; margin: 0.5em;'>{$item['CORDEN']}, PROVEEDOR {$item['CNRORUC']} - {$item['CRAZSOC']}, ( Alumno {$item['CNOMBRE']} ). </li>";
         $lcBody = $lcBody.$lcRow;
      }
      $lcBody = $lcBody."</ul>";
      $lcDate = date('Y-m-d H:i:s');
      $lcMensaje = "<!DOCTYPE html>
                     <html>
                     <body>
                     <table>
                           <colgroup>
                              <col style='background-color: #ececec'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>    
                           <thead>
                              <tr style='background-color: #ffffff; color:black'>
                                 <th colspan='3'>
                                    <div class='container'>
                                       UNIVERSIDAD CATÓLICA DE SANTA MARÍA
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                           </thead>
                           <tbody>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='3'>
                                    Se aprueba el {$this->paData['CDESCRI']}.<br>
                                    Se remite para su evaluación y continue su trámite las siguientes órdenes de servicio:
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: left'>
                                 <th colspan='3'>
                                    {$lcBody}
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: left'>
                                 <th colspan='3'>
                                      <br>VRADM<br>
                                        {$lcDate}
                                      <br><br>
                                 </th>
                              </tr>
                           </tbody>
                        </table>
                     </body>
                     </html>";
//      $laEmails[0] = "gilmar.campana@ucsm.edu.pe";
//      $laEmails[0] = "ccaceres@ucsm.edu.pe";
//      $laEmails[0] = "vradm04@ucsm.edu.pe";
//      $laEmails[0] = "rgutierreza@ucsm.edu.pe";
//      $laEmails[1] = "logistica@ucsm.edu.pe";
//      $laEmails[2] = "opresupuesto@ucsm.edu.pe";
//      $laEmails[3] = "71231729@ucsm.edu.pe";
//      $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $laEmails];
      $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $this->paData['ACORREOS']];
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }
   protected function mxGetDataEnviarCorreoPaquetesViceAdminAPresupuesto($p_oSql) {
      //OBTENCION DE LA DESCRIPCION DEL PAQUETE
      $lcSql = "SELECT cEstado, cdescri FROM D05MPAQ WHERE cIdPaqu = '{$this->paData['CIDPAQU']}' AND cEstado != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL CONSULTAR EL PAQUETE PARA PAGO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CDESCRI'] = $laFila[1];

      //LISTAR ORDENES CREADAS PARA EL PAQUETE
      $lcSql = "select B.cNombre, B.cCodAlu, C.cNroRuc, D.cRazSoc, G.cCodAnt from D05MCOM A
                  INNER JOIN D05MALU B ON A.cidalum = B.cidalum
                  INNER JOIN D05MPRV C ON A.ccodigo = C.ccodigo
                  INNER JOIN S01MPRV D ON C.cNroRuc = D.cNroRuc
                  INNER JOIN E01PREQ E ON A.cIdRequ = E.cIdRequ
                  INNER JOIN E01PORD F ON E.cIdCoti = F.cIdCoti
                  INNER JOIN E01MORD G ON F.cIdOrde = G.cidorde
                  WHERE A.cIdPaqu = '{$this->paData['CIDPAQU']}' AND E.cEstado != 'X' AND F.cEStado != 'X'
                  ORDER BY D.cRazsoc";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL CONSULTAR EL PAQUETE PARA PAGO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1], 'CNRORUC' => $laFila[2], 'CRAZSOC' => str_replace('/', ' ', $laFila[3]), 'CORDEN' => $laFila[4] ];
      }

      //TRAER CORREOS PROCESO DCRRII
      $lcSql = "select mdatos from s01tvar where cnomvar = 'DCRRII.EMAILS'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = $laFila[0];
      $lmDatos = json_decode($lmDatos, true);
      $this->paData['ACORREOS'] = array_merge( $lmDatos['DPLAPRE'], $lmDatos['DCRRII']);
      return true;
   }

   /***
    * CARGA INICIAL DE VISTA PARA APROBACION DE PRESUPUESTO DE PAQUETES DE REQUERIMIENTOS ESPECIALES
    * GAC - 03-05-2023
   */
   public function omInitAsignarPresupuestoRequerimientosEspecialesPresupuesto() {
      $llOk = $this->mxValAsignarPresupuestoRequerimientosEspecialesPresupuesto();
      if (!$llOk){
         return false;
      }
      return true;
   }
   protected function mxValAsignarPresupuestoRequerimientosEspecialesPresupuesto() {
      if (!isset($this->paData['CCENCOS']) or ($this->paData['CCENCOS'] != '009')) {
         $this->pcError = 'EL CENTRO DE COSTOS DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      }
      return true;
   }

   /***
    * LISTAR PAQUETES PENDIENTE DE APROBACION DE PRESUPUESTO DE REQUERIMIENTOS ESPECIALES
    * GAC - 04-05-2023
    */
   public function omBuscarPaquetesPendientesAprobacionPresupuesto() {
      $llOk = $this->mxValBuscarPaquetesPendientesAprobacionPresupuesto();
      if (!$llOk){
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGetBuscarPaquetesPendientesAprobacionPresupuesto($loSql);
      if(!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValBuscarPaquetesPendientesAprobacionPresupuesto() {
      if (!isset($this->paData['CCENCOS']) or ($this->paData['CCENCOS'] != '009')) {
         $this->pcError = 'EL CENTRO DE COSTOS DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      }
      return true;
   }
   protected function mxGetBuscarPaquetesPendientesAprobacionPresupuesto($p_oSql) {
      //LISTAR PAQUETES PENDIENTES DE APROBACION PRESUPEUSTO
      $lcSql = "SELECT A.cIdPaqu, A.cEstado, A.CDESCRI, A.mdatos::json#>>'{0,TGENERA}', A.mdatos::json#>>'{0,CNRODNI}', B.cdescri, C.cdescri from D05MPAQ A
                  INNER JOIN S01TTAB B ON A.cestado = B.ccodigo AND trim(B.cCodTab) = '353' AND nOrden != 0
                  INNER JOIN v_s01tcco_1 C ON A.ccencos = C.ccencos
                  where A.cEstado = 'C' ORDER BY A.CIDPAQU;";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laTmp = [];
      $lmDatos = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $laTmp = ['CIDPAQU' => $laFila[0], 'CESTADO' => $laFila[1], 'CDESCRI' => $laFila[2], 'TGENERA' => $laFila[3], 'CNRODNI' => $laFila[4], 'CESTDES' => $laFila[5], 'CDESCCO' => $laFila[6]];
         $lcSql = "SELECT CNOMBRE FROM v_s01tusu_1 WHERE cnrodni = '{$laFila[4]}' limit 1;";
         $RS2 = $p_oSql->omExec($lcSql);
         if (!$RS2) {
            $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP";
            return false;
         }
         $laFila2 = $p_oSql->fetch($RS2);
         $this->paDatos[] = array_merge($laTmp, ['CNOMBRE' => str_replace('/', ' ',  $laFila2[0])]);
      }
      return true;
   }

   /***
    * LISTAR ORDENES DE PAQUETES PENDIENTE DE APROBACION DE PRESUPUESTO - REQUERIMIENTOS ESPECIALES
    * GAC - 04-05-2023
    */
   public function omRevisarPaquetePendienteAprobacionPresupuesto() {
      $llOk = $this->mxValRevisarPaquetePendienteAprobacionPresupuesto();
      if (!$llOk){
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGetRevisarPaquetePendienteAprobacionPresupuesto($loSql);
      if(!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValRevisarPaquetePendienteAprobacionPresupuesto() {
      if (!isset($this->paData['CCENCOS']) or ($this->paData['CCENCOS'] != '009')) {
         $this->pcError = 'EL CENTRO DE COSTOS DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      } elseif (!isset($this->paData['CIDPAQU'])) {
         $this->pcError = 'EL ID DEL PAQUETE ES INVÁLIDO.';
         return false;
      }
      return true;
   }
   protected function mxGetRevisarPaquetePendienteAprobacionPresupuesto($p_oSql) {

      //LISTAR PARTIDAS PRESUPESTALES
      //TRAER PARTIDAS PRESUPUESTALES DE EGRESOS
      $lcSql = "SELECT t_cCodPar,t_cDescri,t_cCtaCnt FROM F_P01MPAR_1() WHERE TRIM(t_cCodPar) LIKE '2_%'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paData['paCodPar'][] = ['CCODPAR' => $laFila[0],'CDESCRI' => $laFila[1].' '.$laFila[2]];
         $i++;
      }
      if ($i == 0){
         $this->pcError = "NO HAY PARTIDAS PRESUPUESTALES DEFINIDAS";
         return false;
      }

      //OBTENER ID ACTIVIDAD PRESUPUESTAL
      $lcSql = "SELECT B.CIDACTI FROM D05MCOM A
                INNER JOIN E01MREQ B ON A.cidrequ = B.cidrequ
                WHERE A.cidpaqu = '{$this->paData['CIDPAQU']}' AND A.cestado <> 'X' AND B.CESTADO <> 'X' LIMIT 1";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CIDACTI'] = $laFila[0];

      //OBTENER DATA ACTIVIDAD
      $lcSql = "SELECT A.CIDACTI, A.CAUTFIN, A.CDESCRI, B.CDESCRI AS CTIPACT FROM P02MACT A
                LEFT OUTER JOIN V_S01TTAB B ON A.ctipact = TRIM(B.CCODIGO) AND B.CCODTAB = '052'
                WHERE A.CIDACTI = '{$this->paData['CIDACTI']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = array_merge($this->paData, ['CIDACTI' => $laFila[0], 'CAUTFIN' => $laFila[1], 'CACTDES' => $laFila[2], 'CTIPACT' => $laFila[3]]);

      //OBTENER DATOS CABECERA DEL PAQUETE
      $lcSql = "SELECT A.MDATOS::JSON#>>'{0,TGENERA}', B.CDESCRI AS CCENCCO FROM D05MPAQ A 
                INNER JOIN S01TCCO B ON A.CCENCOS = B.CCENCOS
                  WHERE A.CIDPAQU = '{$this->paData['CIDPAQU']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = array_merge($this->paData, ['TGENERA' => $laFila[0], 'CDESCCO' => $laFila[1]]);

      //OBTENER EL MONTO TOTAL DEL PAQUETE
      $lcSql = "SELECT DISTINCT(G.CDESCRI), SUM(F.nmonto) AS NMONTOT FROM D05MCOM A INNER JOIN E01MREQ B ON A.cidrequ = B.cidrequ
                  INNER JOIN E01PREQ C ON B.cidrequ = C.cidrequ INNER JOIN E01MCOT D ON C.cidcoti = D.cidcoti
                  INNER JOIN E01PORD E ON D.cidcoti = E.cidcoti INNER JOIN E01MORD F ON E.cidorde = F.cidorde
                LEFT OUTER JOIN V_S01TTAB G ON F.CMONEDA = TRIM(G.CCODIGO) AND G.CCODTAB = '007'
                  WHERE A.CIDPAQU = '{$this->paData['CIDPAQU']}' GROUP BY G.CDESCRI";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CMONEDA'] = $laFila[0];
      $this->paData['NMONTOT'] = $laFila[1];

      // LISTAR ORDENES DEL PAQUETE PARA ASIGNAR PRESUPUESTO
      $lcSql = "SELECT F.cCodAnt, F.dEnvPre, G.cDescri AS CCENCOS, B.cDescri, H.cRazSoc, F.nMonto, I.cDescri, A.MENLACE, E.cIdOrde FROM D05MCOM A
                  INNER JOIN E01MREQ B ON A.cidrequ = B.cidrequ
                  INNER JOIN E01PREQ C ON B.cidrequ = C.cidrequ
                  INNER JOIN E01MCOT D ON C.cidcoti = D.cidcoti
                  INNER JOIN E01PORD E ON D.cidcoti = E.cidcoti
                  INNER JOIN E01MORD F ON F.cIdOrde = E.cIdOrde
                  INNER JOIN v_s01tcco_1 G ON G.ccencos = B.ccencos
                  INNER JOIN S01MPRV H ON H.cNroRuc = F.cNroRuc
                  INNER JOIN V_S01TTAb I ON I.cCodigo = F.cMoneda ANd TRIM(I.cCodtab) = '007'
                  WHERE A.cidpaqu = '{$this->paData['CIDPAQU']}' AND C.cEstado != 'X' AND E.cEStado != 'X'
                  ORDER BY H.CRAZSOC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE TIENE ORDENES EN EL PAQUETE PARA LA ASIGNACIÓN DE PRESUPUESTO.";
         return false;
      }
      $this->paDatos = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CORDEN' => $laFila[0], 'DENVIO' => $laFila[1], 'CCENCOS' => $laFila[2], 'CDESCRI' => $laFila[3],
            'CRAZSOC' =>  str_replace('/', ' ', $laFila[4]), 'NMONTO' => $laFila[5], 'CMONEDA' => $laFila[6],
            'MENLACE' => $laFila[7], 'CIDORDE' => $laFila[8]];
      }
      return true;
   }

   /**
    *    INIT LISTAR ORDENES DE PAQUETES PENDIENTES DE REVISION Y FIRMA DE LOGISTICA - JEFE DE LOGISTICA
    *    GCQ - 05-05-2023
   **/
   public function omInitEvaluarFirmarRequerimientosEspecialesLogistica() {
      $llOk = $this->mxValInitEvaluarFirmarRequerimientosEspecialesLogistica();
      if (!$llOk){
         return false;
      }
      return true;
   }
   protected function mxValInitEvaluarFirmarRequerimientosEspecialesLogistica() {
      if (!isset($this->paData['CNIVEL']) or ($this->paData['CNIVEL'] != 'JL')) {
         $this->pcError = 'EL NIVEL DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) or ($this->paData['CCENCOS'] != '02W')) {
         $this->pcError = 'EL CENTRO DE COSTOS DEL USUARIO NO TIENE PERMISOS PARA REALIZAR ESTA ACCIÓN';
         return false;
      }
      return true;
   }
   /**
    *    LISTAR ORDENES DE PAQUETES PENDIENTES DE REVISION Y FIRMA DE LOGISTICA - JEFE DE LOGISTICA
    *    GCQ - 05-05-2023
    **/
   public function omBuscarRequerimientosEspecialesPendientesRevisionYFirmaLogistica() {
      $llOk = $this->mxValInitEvaluarFirmarRequerimientosEspecialesLogistica();
      if (!$llOk){
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if(!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGetBuscarRequerimientosEspecialesPendientesRevisionYFirmaLogistica($loSql);
      if(!$llOk) {
         return false;
      }
      return true;
   }

   protected function mxGetBuscarRequerimientosEspecialesPendientesRevisionYFirmaLogistica($p_oSql) {
      //LISTAR PAQUETES PENDIENTES DE APROBACION PRESUPEUSTO
      $lcSql = "SELECT A.cIdPaqu, A.cEstado, A.CDESCRI, A.mdatos::json#>>'{0,TGENERA}', A.mdatos::json#>>'{0,CNRODNI}', B.cdescri, C.cdescri from D05MPAQ A
                  INNER JOIN S01TTAB B ON A.cestado = B.ccodigo AND trim(B.cCodTab) = '353' AND nOrden != 0
                  INNER JOIN v_s01tcco_1 C ON A.ccencos = C.ccencos
                  where A.cEstado = 'E' ORDER BY A.CIDPAQU;";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP,";
         return false;
      }
      $laTmp = [];
      $lmDatos = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $laTmp = ['CIDPAQU' => $laFila[0], 'CESTADO' => $laFila[1], 'CDESCRI' => $laFila[2], 'TGENERA' => $laFila[3], 'CNRODNI' => $laFila[4], 'CESTDES' => $laFila[5], 'CDESCCO' => $laFila[6]];
         $lcSql = "SELECT CNOMBRE FROM v_s01tusu_1 WHERE cnrodni = '{$laFila[4]}' limit 1;";
         $RS2 = $p_oSql->omExec($lcSql);
         if (!$RS2) {
            $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS, CONTACTE CON SOPORTE DE ERP";
            return false;
         }
         $laFila2 = $p_oSql->fetch($RS2);
         $this->paDatos[] = array_merge($laTmp, ['CNOMBRE' => str_replace('/', ' ',  $laFila2[0])]);
      }
      return true;
   }

   public function omInitConsultaPyInvestigacion() {
      $llOk = $this->mxValCentroCostosViceInvestigacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxGetInitConsultaPyInvestigacion($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxGetInitConsultaPyInvestigacion($p_oSql) {
      $lcSql = "SELECT DISTINCT(anopro) FROM D10MCTA WHERE codcta LIKE '4699102%' AND anopro >= '2022'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS.";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos['APERIOD'][] = ['CPERIOD' => $laFila[0]];
      }
      return true;
   }

   public function omBuscarProyectosInvestigacionPorPeriodo() {
      $llOk = $this->mxValCentroCostosViceInvestigacion();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValBuscarProyectosInvestigacionPorPeriodo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxGetBuscarProyectosInvestigacionPorPeriodo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValBuscarProyectosInvestigacionPorPeriodo() {
      if (!isset($this->paData['CPERIOD']) OR strlen($this->paData['CPERIOD']) != 4) {
         $this->pcError = "PERIODO A BUSCAR INVÁLIDO.";
         return false;
      }
      return true;
   }
   protected function mxGetBuscarProyectosInvestigacionPorPeriodo($p_oSql) {
      $lcSql = "SELECT trim(codcta), descri FROM D10MCTA WHERE codcta LIKE '4699102%' AND anopro = '{$this->paData['CPERIOD']}' AND estado = 'A' order by descri";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS.";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos['APROYEC'][] = ['CCODCTA' => $laFila[0], 'CDESCRI' => $laFila[1]];
      }
      return true;
   }
   protected function mxValCentroCostosViceInvestigacion() {
      if (!isset($this->paData['CCENCOS']) OR strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = "CENTRO DE COSTOS INVÁLIDO, NO TIENE PERMISO PARA ESTA ACCIÓN";
         return false;
      }
      return true;
   }
   public function omVerDetalleProyectoInvestigacion() {
      $llOk = $this->mxValCentroCostosViceInvestigacion();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValVerDetalleProyectoInvestigacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxVerDetalleProyectoInvestigacion($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }

   protected function mxValVerDetalleProyectoInvestigacion() {
      if (!isset($this->paData['CCODCTA'])) {
         $this->pcError = "CÓDIGO DE CUENTA PARA PROYECTO INVÁLIDO.";
         return false;
      } else if (!isset($this->paData['CPERIOD']) OR strlen($this->paData['CPERIOD']) != 4) {
         $this->pcError = "PERIODO PARA CONSULTA ES INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxVerDetalleProyectoInvestigacion($p_oSql) {
      $lcSql = "SELECT m.mescom,
                      CONCAT(m.tipcom,'-',m.numcom,'#',d.numsec)::char(15) AS asient,
                      d.fecdoc,  d.descri,
                      CONCAT(p.cnroruc, c.cnumide, e.ccodusu) AS rucdni,
                      CONCAT(p.crazsoc, c.cdescri, e.cnombre) AS desruc,
                      CONCAT(d.tipdoc,'/',d.numdoc)::char(25) AS docume,
                      a.habsol AS valing, a.debsol as valegr,
                      F.cdescri
                 FROM D10AASI AS a
                   LEFT JOIN D10DASI AS d ON d.idasid = a.idasid
                   LEFT JOIN D10MASI AS m ON m.idasie = d.idasie
                   LEFT JOIN S01MPRV AS p ON p.ccodant = d.codcte AND d.tipcte = 'P' AND p.cestado = 'A'
                   LEFT JOIN S01MCLI AS c ON c.ccodant = d.codcte AND d.tipcte = 'C'
                   LEFT JOIN V_S01TUSU_1 AS e ON e.ccodusu = d.codcte AND d.tipcte = 'E'
                   left OUTER JOIN V_S01TTAB F ON F.ccodtab = '087' AND D.tipdoc = F.ccodigo
                 WHERE a.codcta = '{$this->paData['CCODCTA']}'
                   AND m.anocom = '{$this->paData['CPERIOD']}'
                 ORDER BY m.anocom, m.mescom, m.tipcom, m.numcom, d.numsec";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL CONSULTAR CON LA BASE DE DATOS.";
         return false;
      }
      if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE HAN ENCONTRADO REGISTROS DEL PROYECTO.";
         return false;
      }
      $laMonths = array('ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SET', 'OCT', 'NOV', 'DIC');
      while($laFila = $p_oSql->fetch($RS)) {
         $lcMes = $laFila[0];
         if ($lcMes == '00') {
            $lcMes = 'INICIO AÑO';
         } else if ($lcMes == '13') {
            continue;
//            $lcMes = 'CIERRE AÑO';
         } else {
            $lcMes = $laMonths[intval($lcMes) - 1];
         }
         $this->paDatos['ADETALLE'][] = ['CMESCON' => $lcMes, 'CASIENT' => $laFila[1], 'DFECDOC' => $laFila[2],
               'CDESCRI' => $laFila[3], 'CRUCDNI' => $laFila[4], 'CRAZSOC' => $laFila[5], 'CDOCUM' => $laFila[6],
            'NVALING' => $laFila[7], 'NVALEGR' => $laFila[8], 'CTIPCOM' => $laFila[9]];
      }
      return true;
   }


   //LISTADO DE PAQUETES DE DCRRII - FAMILIAS ANFITRIONAS
   public function omInitDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValInitDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxInitDCRRIIFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxValParamUsuario($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxValInitDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } else if (!isset($this->paData['CCODUSU']) || !preg_match('(^[A-Z0-9]{4}$)', $this->paData['CCODUSU'])) {
         $this->pcError = "CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO";
         return false;
      }
      return true;
   }

   protected function mxInitDCRRIIFamiliasAnfitrionas($p_oSql) {
      $lcSql = "SELECT A.CIDPAQU, A.CDESCRI, B.CDESCRI AS CESTADO, A.mdatos::json#>>'{0,TGENERA}' AS DGENERA FROM D05MPAQ A
                  LEFT OUTER JOIN v_s01ttab B ON B.ccodtab = '353' AND A.CESTADO = TRIM(B.CCODIGO)
                  WHERE CTIPPAQ = 'D' AND CESTADO <> 'X' ORDER BY CIDPAQU;";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR LA LISTA DE PAQUETES DE FAMILIAS ANFITRIONAS, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDPAQU' => $laFila[0], 'CDESCRI' => $laFila[1], 'CESTADO' => $laFila[2], 'TGENERA' => $laFila[3]];
      }
      return true;
   }

   public function omVerPaqueteDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValVerPaqueteDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxVerPaqueteDCRRIIFamiliasAnfitrionas($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValVerPaqueteDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } else if (!isset($this->paData['CIDPAQU']) OR $this->paData['CIDPAQU'] == '') {
         $this->pcError = "EL PAQUETE SELECCIONADO NO SE PUEDE VISUALIZAR, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }

   protected function mxVerPaqueteDCRRIIFamiliasAnfitrionas($p_oSql) {
      //CONSULTA CABECERA DCRRII PAQUETE
      $lcSql = "SELECT A.CIDPAQU, A.CDESCRI, A.MDATOS::JSON->>'TGENERA' AS TGENERA, A.MDATOS::JSON->>'MOBSERV' AS MOBSERV, A.COBSERV, B.CDESCRI AS CESTADODES, A.CESTADO 
                  FROM D05MPAQ A
                  INNER JOIN V_S01TTAB B ON B.CCODTAB = '353' AND A.CESTADO = B.CCODIGO
                  WHERE CIDPAQU = '{$this->paData['CIDPAQU']}';";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = ['CIDPAQU' => $laFila[0], 'CDESCRI' => $laFila[1], 'TGENERA' => $laFila[2], 'MOBSERV' => $laFila[3], 'COBSERV' => $laFila[4], 'CESTADODES' => $laFila[5], 'CESTADO' => $laFila[6]];
      //CONSULTA COMPROBANTES DEL PAQUETE DE DCRRII
      $lcSql = "SELECT A.CIDCOMP, A.CESTADO, E.CDESCRI AS CTIPCOM, A.CNROCOM, A.MENLACE, A.MDATOS::JSON->>'DFECCOM' AS DFECCOM, A.MDATOS::JSON->>'NMONTO' AS NMONCOM,
                D.CNRORUC, D.CRAZSOC, C.CNOMBRE AS CNOMALU, G.CDESCRI AS CPAIS, A.mEnlace FROM D05MCOM A
                  INNER JOIN D05MPRV B ON A.ccodigo = B.ccodigo
                  INNER JOIN D05MALU C ON A.cidalum = C.cidalum
                  LEFT OUTER JOIN S01MPRV D ON B.cnroruc = D.cnroruc
                  LEFT OUTER JOIN V_S01TTAB E ON E.CCODIGO = TRIM(A.CTIPCOM) AND E.CCODTAB = '087'
                  -- LEFT OUTER JOIN V_S01TTAB E ON E.CCODIGO = A.CTIPCOM AND E.CCODTAB = '007'
                  LEFT OUTER JOIN s01tpai G ON G.cidpais = C.cidpais
                  WHERE A.CIDPAQU = '{$this->paData['CIDPAQU']}' AND G.CESTADO = 'A' AND A.CESTADO <> 'X' ORDER BY D.CRAZSOC";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDCOMP' => $laFila[0], 'CESTADO' => $laFila[1], 'CTIPCOM' => $laFila[2], 'CNROCOM' => $laFila[3], 'MENLACE' => $laFila[4],
               'DFECCOM' => ($laFila[5] == '') ? '-' : $laFila[5], 'NMONCOM' => $laFila[6], 'CNRORUC' => $laFila[7], 'CRAZSOC' => str_replace('/', ' ', $laFila[8]),
               'CNOMALU' => str_replace('/', ' ', $laFila[9]), 'CPAIS' => $laFila[10], 'CFILEPATH' => ($laFila[11] == '') ? 'N':'S', 'MENLACE' => $laFila[11]];
      }
      //OBTENCION DE HISTORICO
      $lcSql = "select value->>'CNRODNI', value->>'CSECUEN', value->>'TGENERA', value->>'MOBSERV', value->>'CSECUEN' 
                FROM json_array_elements((SELECT mdatos from d05mpaq where cidpaqu = '{$this->paData['CIDPAQU']}')::JSON);";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      $laTmpHistor = [];
      while($laFila = $p_oSql->fetch($RS)) {
         $laTmpHistor = ['CNRODNI' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'MOBSERV' => $laFila[3], 'CSECUEN' => $laFila[4]];
         $lcSql = "SELECT CNOMBRE FROM V_S01TUSU_1 WHERE CNRODNI = '{$laTmpHistor['CNRODNI']}'";
         $RS2 = $p_oSql->omExec($lcSql);
         if (!$RS2) {
            $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
            return false;
         }
         $laFila2 = $p_oSql->fetch($RS2);
         $this->paData['AHISTOR'][] = array_merge($laTmpHistor, ['CNOMBRE' => str_replace('/', ' ', $laFila2[0])]);
      }
      return true;
   }

   public function omAgregarPaqueteDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValAgregarPaqueteDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxAgregarPaqueteDCRRIIFamiliasAnfitrionas($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return true;
   }
   protected function mxValAgregarPaqueteDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxAgregarPaqueteDCRRIIFamiliasAnfitrionas($p_oSql) {
      $lcSql = "SELECT C.cnombre AS CNOMALU, C.ccodalu, D.cnroruc, D.crazsoc, E.cdescri AS CPAIS, A.nSerial FROM D05PALU A
                  INNER JOIN D05MPRV B ON A.ccodigo = B.ccodigo
                  INNER JOIN D05MALU C ON A.ccodalu = C.ccodalu
                  LEFT OUTER JOIN S01MPRV D ON B.cnroruc = D.cnroruc
                  LEFT OUTER JOIN S01TPAI E ON C.cidpais = E.cidpais
                  WHERE A.CESTADO = 'A' ORDER BY A.CCODIGO;";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CMONALU' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1], 'CNRORUC' => $laFila[2],
            'CRAZSOC' => str_replace('/', ' ', $laFila[3]), 'CPAIS' => $laFila[4], 'NSERIAL' => $laFila[5]];
      }
      return true;
   }


   public function omAnularAlumnoListaBaseDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValAnularAlumnoListaBaseDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxAnularAlumnoListaBaseDCRRIIFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAnularAlumnoListaBaseDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['NSERIAL'])) {
         $this->pcError = "SERIAL INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxAnularAlumnoListaBaseDCRRIIFamiliasAnfitrionas($p_oSql) {
      $lcSql = "UPDATE D05PALU SET CESTADO = 'X' WHERE NSERIAL = {$this->paData['NSERIAL']};";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "NO SE PUDO ELIMINAR EL ALUMNO DE LA LISTA, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }


   public function omGenerarPaqueteDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValGenerarPaqueteDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxGenerarPaqueteDCRRIIFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValGenerarPaqueteDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxGenerarPaqueteDCRRIIFamiliasAnfitrionas($p_oSql) {
      $this->paData = array_merge($this->paData, ['CMONEDA' => '1', 'CIDACTI' => '00009531', 'CNRODOC' => 'OFICIO S/R']);
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_102('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laRes = json_decode($laFila[0], true);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      if (!isset($laRes[0]['OK'])) {
         $this->pcError = $laRes[0]['ERROR'];
         return false;
      }
      $this->paData['CIDPAQU'] = $laRes[0]['CIDPAQU'];
      return true;
   }


   public function omEditarComprobanteAlumnoDCrriiFamiliasAnfitrionas() {
      $llOk = $this->mxValEditarComprobanteAlumnoDCrriiFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxEditarComprobanteAlumnoDCrriiFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValEditarComprobanteAlumnoDCrriiFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) or strlen($this->paData['CIDCOMP']) != 5) {
         $this->pcError = "EL ID DEL COMPROBANTE ES INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['DEMISION'])) {
         $this->pcError = "LA FECHA DEL COMPROBANTE ES INVÁLIDA, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['CFILE']) OR $this->paData['CFILE'] == '' ) {
         $this->pcError = "EL LINK DEL ARCHIVO DEL COMPROBANTE ES INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['CNROCOM']) OR $this->paData['CNROCOM'] == '' ) {
         $this->pcError = "EL LINK DEL ARCHIVO DEL COMPROBANTE ES INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxEditarComprobanteAlumnoDCrriiFamiliasAnfitrionas($p_oSql) {
      $lcSql = "SELECT CIDPAQU, MDATOS,CCODIGO FROM D05MCOM WHERE CIDCOMP = '{$this->paData['CIDCOMP']}' AND CESTADO = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == '') {
         $this->pcError = "NO SE ENCONTRO EL COMPROBANTE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      $this->paData['CIDPAQU'] = $laFila[0];
      $this->paData['MDATOS'] = $laFila[1];
      $this->paData['CCODIGO'] = $laFila[2];

      //VALIDA QUE COMPROBANTE NO HAYA SIDO INGRESADO ANTERIORMENTE EN LA TABLA E01MFAC - MAESTRO DE OL'S
      $lcSql = "SELECT COUNT(*) FROM E01MFAC A 
                  INNER JOIN S01MPRV B ON A.cnroruc = B.cnroruc
                  INNER JOIN D05MPRV C ON B.cnroruc = C.cnroruc 
                  WHERE A.CESTADO IN ('A', 'E', 'C', 'B') AND A.CTIPCOM = '02' AND A.CNROCOM = '{$this->paData['CNROCOMPRO']}' AND C.CCODIGO = '{$this->paData['CCODIGO']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] > 0) {
         $this->pcError = "EL COMPROBANTE {$this->paData['CNROCOMPRO']} YA FUE INGRESADO CON ANTERIORIDAD PARA ESTE PROVEEDOR";
         return false;
      }
      //VALIDA QUE COMPROBANTE NO HAYA SIDO INGRESADO ANTERIORMENTE EN LA TABLA D05MCOM
      $lcSql = "SELECT A.CIDCOMP FROM D05MCOM A 
                  INNER JOIN D05MPRV B ON A.ccodigo = B.ccodigo
                  INNER JOIN S01MPRV C ON B.cnroruc = C.cnroruc
                  WHERE A.CESTADO <> 'X' AND A.CTIPCOM = '02' AND A.CNROCOM = '{$this->paData['CNROCOMPRO']}' AND B.ccodigo = '{$this->paData['CCODIGO']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] != '') {
         if ($this->paData['CIDCOMP'] != $laFila[0]) {
            $this->pcError = "EL COMPROBANTE {$this->paData['CNROCOMPRO']} YA FUE INGRESADO CON ANTERIORIDAD PARA ESTE PROVEEDOR";
            return false;
         }
      }
      $lmDatos = json_decode($this->paData['MDATOS'], true);
      $lmDatos['DFECCOM'] = $this->paData['DEMISION'];
      $lmDatos = json_encode($lmDatos, true);

      $lcSql = "UPDATE D05MCOM SET MENLACE = '{$this->paData['CFILE']}', cnrocom = '{$this->paData['CNROCOMPRO']}', MDATOS = '{$lmDatos}' 
               WHERE CIDCOMP = '{$this->paData['CIDCOMP']}' AND CESTADO = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL ACTUALIZAR COMPROBANTE, COMUNIQUESE CON SOPORTE DEL ERP.';
         return false;
      }
      return true;
   }

   public function omEnviarRevisionRRHHDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValEnviarRevisionRRHHDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxEnviarRevisionRRHHDCRRIIFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->omEnviarCorreoRevisionRRHHDCRRIIFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValEnviarRevisionRRHHDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['CIDPAQU']) or strlen($this->paData['CIDPAQU']) != 3) {
         $this->pcError = "EL ID DEL PAQUETE ES INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxEnviarRevisionRRHHDCRRIIFamiliasAnfitrionas($p_oSql) {
      $this->paData = array_merge($this->paData, ['NMONTO' => 1500.00]);
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT * FROM P_E01MREQ_103('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laRes = json_decode($laFila[0], true);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS"}' : $laFila[0];
      if (!isset($laRes[0]['OK'])) {
         $this->pcError = $laRes[0]['ERROR'];
         return false;
      }
//      $this->paData['CIDPAQU'] = $laRes[0]['CIDPAQU'];
//      //VALIDACION DE COMPROBANTES, ESTOS DEBEN TENER TODA SU INFORMACIÓN CARGADA
//      $lcSql = "select COUNT(*) from D05MCOM WHERE CESTADO IN ('A', 'O') AND CIDPAQU = '{$this->paData['CIDPAQU']}' AND (CTIPCOM = '00' OR CNROCOM = '' OR mdatos::JSON->>'DFECCOM' = '' OR MENLACE = '');";
//      $RS = $p_oSql->omExec($lcSql);
//      if (!$RS) {
//         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
//         return false;
//      }
//      $laFila = $p_oSql->fetch($RS);
//      if ($laFila[0] > 0) {
//         $this->pcError = "DEBE INGRESAR LA INFORMACIÓN DE TODOS LOS COMPROBANTES ANTES DE ENVIAR PARA SU REVISION";
//         return false;
//      }
//      //OBTENER MDATOS DEL PAQUETE PARA ACTUALIZACIÓN DE LA FECHA DE ENVIO A REVISION DE RRHH
//      $lcSql = "select MDATOS from D05MPAQ WHERE CESTADO IN ('R', 'O') AND CIDPAQU = '{$this->paData['CIDPAQU']}'";
//      $RS = $p_oSql->omExec($lcSql);
//      if (!$RS or $p_oSql->pnNumRow = 0) {
//         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
//         return false;
//      }
//      $lcDate = date('Y-m-d H:i:s');
//      $laFila = $p_oSql->fetch($RS);
//      $lmDatos = json_decode($laFila[0], true);
//      $lmDatos['TENRHRV'] = $lcDate;
//      $lmDatos = json_encode($lmDatos, true);
//      $lcSql = "UPDATE D05MPAQ SET CESTADO = 'A', TMODIFI = NOW(), CUSUCOD = '{$this->paData['CCODUSU']}', mdatos = '{$lmDatos}' WHERE CIDPAQU = '{$this->paData['CIDPAQU']}' AND CESTADO IN ('R', 'O')";
//      $RS = $p_oSql->omExec($lcSql);
//      if (!$RS) {
//         $this->pcError = 'ERROR AL ACTUALIZAR COMPROBANTE, COMUNIQUESE CON SOPORTE DEL ERP.';
//         return false;
//      }
      return true;
   }

      public function omEnviarCorreoRevisionRRHHDCRRIIFamiliasAnfitrionas($p_oSql) {
         $llOk = $this->mxGetDataEnviarCorreoRevisionRRHHDCRRIIFamiliasAnfitrionas($p_oSql);
         if (!$llOk) {
            return false;
         }
         $lcDate = date('Y-m-d H:i:s');
         $lo = new CEmail();
         $llOk = $lo->omConnect();
         $lcBody = "<ul style='margin: 0.5em'>";
         foreach ($this->paDatos as $item) {
            $lcRow = "<li style='font-weight: normal; font-size: 14px; margin: 0.5em;'><b>{$item['CNRORUC']} - {$item['CRAZSOC']}</b>, ( Estudiante {$item['CNOMALU']} ). </li>";
            $lcBody = $lcBody.$lcRow;
         }
         $lcBody = $lcBody."</ul>";
         $lcMensaje = "<!DOCTYPE html>
                        <html>
                        <body>
                        <table>
                              <colgroup>
                                 <col style='background-color: #ececec'>
                                 <col style='background-color: #ffffff;'>
                              </colgroup>    
                              <thead>
                                 <tr style='background-color: #ffffff; color:black'>
                                    <th colspan='3'>
                                       <div class='container'>
                                          UNIVERSIDAD CATÓLICA DE SANTA MARÍA
                                       </div>
                                       <br>
                                    </th>
                                 </tr>
                              </thead>
                              <tbody>
                                 <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                    <th colspan='3'>
                                       
                                       Se remite la presente para la revisión de proveedores, con el fin de cumplir con el {$this->paData['CDESCRI']}.
                                    </th>
                                 </tr>
                                 <tr style='background-color: #ffffff; color:black; text-align: left'>
                                    <th colspan='3'>
                                       {$lcBody}
                                    </th>
                                 </tr>
                                 <tr style='background-color: #ffffff; color:black; text-align: left'>
                                    <th colspan='3'>
                                         <br>DCRRII<br>
                                           {$lcDate}
                                         <br><br>
                                    </th>
                                 </tr>
                              </tbody>
                           </table>
                        </body>
                        </html>";
   //      $laEmails[0] = "ccaceres@ucsm.edu.pe";
   //      $laEmails[0] = "vradm04@ucsm.edu.pe";
   //      $laEmails[0] = "gilmar.campana@ucsm.edu.pe";
   //      $laEmails[0] = "rgutierreza@ucsm.edu.pe";
   //      $laEmails[1] = "orrhh@ucsm.edu.pe";
   //      $laEmails[2] = "ocai@ucsm.edu.pe";
   //      $laEmails[3] = "jtapiama@ucsm.edu.pe";
   //      $laEmails[3] = "71231729@ucsm.edu.pe";
         $laEmails = $this->paData['ACORREOS'];
         $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $laEmails];
         $llOk = $lo->omSend();
         if (!$llOk) {
            $this->pcError = $lo->pcError;
            return false;
         }
         return true;
      }
      protected function mxGetDataEnviarCorreoRevisionRRHHDCRRIIFamiliasAnfitrionas($p_oSql) {
         //LISTAR ALUMNOS Y PROVEEDORES PARA EL PAGO DE DCRRII
         $lcSql = "SELECT C.crazsoc, C.cnroruc, D.cnombre AS CNOMALU, E.cdescri AS CTIPCOM, A.cnrocom, A.mdatos::JSON->>'DFECCOM' AS DFECEMI FROM D05MCOM A
                     INNER JOIN D05MPRV B ON A.ccodigo = B.ccodigo
                     INNER JOIN S01MPRV C ON B.cnroruc = C.cnroruc
                     INNER JOIN D05MALU D ON A.cidalum = D.cidalum
                     LEFT OUTER JOIN V_S01TTAB E ON E.CCODIGO = TRIM(A.CTIPCOM) AND E.CCODTAB = '087'
                     WHERE A.cidpaqu = '{$this->paData['CIDPAQU']}' AND A.cestado <> 'X' ORDER BY C.crazsoc";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
            return false;
         }
         $this->paDatos = [];
         while($laFila = $p_oSql->fetch($RS)) {
            $this->paDatos[] = ['CRAZSOC' => str_replace('/', ' ', $laFila[0]), 'CNRORUC' => $laFila[1], 'CNOMALU' => str_replace('/', ' ', $laFila[2]),
               'CTIPCOM' => $laFila[3], 'CNROCOM' => $laFila[4], 'DFECCOM' => $laFila[5] ];
         }
         //TRAER CABECERA PAQUETE
         $lcSql = "SELECT cDescri FROM D05MPAQ
                     WHERE cidpaqu = '{$this->paData['CIDPAQU']}' AND cestado <> 'X'";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
            return false;
         }
         $laFila = $p_oSql->fetch($RS);
         $this->paData['CDESCRI'] = $laFila[0];

         //TRAER CORREOS PROCESO DCRRII
         $lcSql = "select mdatos from s01tvar where cnomvar = 'DCRRII.EMAILS'";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
            return false;
         }
         $laFila = $p_oSql->fetch($RS);
         $lmDatos = $laFila[0];
         $lmDatos = json_decode($lmDatos, true);
         $this->paData['ACORREOS'] = array_merge( $lmDatos['RRHH'], $lmDatos['DCRRII']);
         return true;
      }

   protected function mxValParamUsuario($p_oSql, $p_cModulo = '000') {
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
//      if (!isset($laFila[0]) or empty($laFila[0])) {
//         $this->pcError = "";
//         return false;
//      } else
      if ($laFila[0] == 'A') {
         return true;
      }
      $lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cModulo = '$p_cModulo'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if (!isset($laFila[0]) or empty($laFila[0])) {
         $this->pcError = "USUARIO NO TIENE PERMISO PARA OPCION";
         return False;
      } elseif ($laFila[0] != 'A') {
         $this->pcError = "USUARIO NO TIENE PERMISO ACTIVO PARA OPCION";
         return false;
      }
      return true;
   }

   public function omAnularComprobanteDCRRIIFamiliasAnfitrionas() {
      $llOk = $this->mxValAnularComprobanteDCRRIIFamiliasAnfitrionas();
      if (!$llOk) return false;
      $loSql =  new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) return false;
      $llOk = $this->mxAnularComprobanteDCRRIIFamiliasAnfitrionas($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAnularComprobanteDCRRIIFamiliasAnfitrionas() {
      if (!isset($this->paData['CCENCOS']) OR $this->paData['CCENCOS'] != '00F') {
         $this->pcError = "USTED NO TIENE LOS PERMISOS PARA REALIZAR ESTA ACCIÓN, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      } elseif (!isset($this->paData['CIDCOMP']) or strlen($this->paData['CIDCOMP']) != 5) {
         $this->pcError = "EL ID DEL COMPROBANTE ES INVÁLIDO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }
   protected function mxAnularComprobanteDCRRIIFamiliasAnfitrionas($p_oSql) {
      //VALIDA QUE EL COMPROBANTE EXISTA
      $lcSql = "SELECT CIDPAQU, CIDREQU, MDATOS FROM D05MCOM WHERE CIDCOMP = '{$this->paData['CIDCOMP']}' AND CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == '' OR $laFila[1] == '') {
         $this->pcError = "NO SE ENCONTRO EL COMPROBANTE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      $this->paData['CIDPAQU'] = $laFila[0];
      $this->paData['CIDREQU'] = $laFila[1];
      $lmDatos = $laFila[2];
      $lmDatos = json_decode($lmDatos, true);
      $lcDate = date("Y-m-d H:i:s");
      $lmDatos = array_merge($lmDatos, ['DFECANU' => $lcDate, 'MOBSERV' => $this->paData['MOBSERV']]);
      $lmDatos = json_encode($lmDatos, true);
      //ANULACION DE COMPROBANTE
      $lcSql = "UPDATE D05MCOM SET CESTADO = 'X', MDATOS = '{$lmDatos}' WHERE CIDCOMP = '{$this->paData['CIDCOMP']}' AND CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL ANULAR COMPROBANTE, COMUNIQUESE CON SOPORTE DEL ERP.';
         return false;
      }

      //ANULACION DE REQUERIMIENTO
      $lcSql = "UPDATE E01MREQ SET CESTADO = 'X' WHERE CIDREQU = '{$this->paData['CIDREQU']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL ANULAR COMPROBANTE, COMUNIQUESE CON SOPORTE DEL ERP.';
         return false;
      }
      $lcSql = "UPDATE E01DCOM SET CESTADO = 'X' WHERE CIDREQU = '{$this->paData['CIDREQU']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR AL ANULAR COMPROBANTE, COMUNIQUESE CON SOPORTE DEL ERP.';
         return false;
      }
      return true;
   }


   public function omAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto() {
      $llOk = $this->mxValAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->mxEnviarCorreoAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto($loSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ENVIAR EL CORREO , CONTACTE CON SOPORTE DEL ERP.";
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto() {
      if (!isset($this->paData['CIDPAQU'])) {
         $this->pcError = "EL ID DEL PAQUETE NO ES VÁLIDO.";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) or strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "EL CODIGO DE USUARIO ES INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CCENCOS']) or $this->paData['CCENCOS'] != '009') {
         $this->pcError = "   EL NIVEL DE PERMISOS DEL USUARIO NO PERMITE REALIZAR LA ACCIÓN.";
         return false;
      } elseif (!isset($this->paData['CCODPAR']) or $this->paData['CCODPAR'] == '') {
         $this->pcError = "   EL CÓDIGO DE PARTIDA ES INVALIDO.";
         return false;
      }
      return true;
   }
   protected function mxAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto($p_oSql) {
      //VALIDA QUE LOS REQUERIMIENTOS ESTEN CREADOS
      $lcSql = "SELECT count(*) FROM D05MCOM A
                  INNER JOIN E01MREQ B ON A.cidrequ = B.cidrequ
                  INNER JOIN E01PREQ C ON B.cidrequ = C.cidrequ
                  INNER JOIN E01MCOT D ON C.cidcoti = D.cidcoti
                  INNER JOIN E01PORD E ON D.cidcoti = E.cidcoti
                  INNER JOIN E01MORD F ON E.cidorde = F.cidorde
                  WHERE A.CIDPAQU = '{$this->paData['CIDPAQU']}' AND C.CESTADO <> 'X' AND D.CESTADO <> 'X' AND F.CESTADO <> 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }

      //OBTENER CUENTA CONTABLE
      $lcSql = "SELECT TRIM(cCtaCnt) FROM P01MPAR WHERE cCodPar = '{$this->paData['CCODPAR']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      $this->paData['CCTACNT'] = $p_oSql->fetch($RS)[0];
      //ACTUALIZAR PARTIDA DE LAS ORDENES DEL PAQUETE
      $lcSql = "UPDATE E01MORD SET CCODPAR = '{$this->paData['CCODPAR']}', CUSUAFE = '{$this->paData['CCODUSU']}', TAFECTA = now(), cEstPro = 'A', 
                   CCTACNT = '{$this->paData['CCTACNT']}', CUSUCOD = '{$this->paData['CCODUSU']}', TMODIFI = now() 
               WHERE CIDORDE IN 
                  (SELECT F.CIDORDE FROM D05MCOM A
                  INNER JOIN E01MREQ B ON A.cidrequ = B.cidrequ
                  INNER JOIN E01PREQ C ON B.cidrequ = C.cidrequ
                  INNER JOIN E01MCOT D ON C.cidcoti = D.cidcoti
                  INNER JOIN E01PORD E ON D.cidcoti = E.cidcoti
                  INNER JOIN E01MORD F ON E.cidorde = F.cidorde
                  WHERE A.CIDPAQU = '{$this->paData['CIDPAQU']}' AND C.CESTADO <> 'X' AND D.CESTADO <> 'X' AND F.CESTADO <> 'X')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }

      //OBTENCIO DATOS DE USUARIO
      $lcSql = "SELECT CNRODNI FROM V_S01TUSU_1 WHERE CCODUSU = '{$this->paData['CCODUSU']}'";
      $RS1 = $p_oSql->omExec($lcSql);
      if (!$RS1 or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP [OBSERVACIONES].";
         return false;
      }
      $laFilaUsu = $p_oSql->fetch($RS1);
      $this->paData['CNRODNI'] = $laFilaUsu[0];

      //MDATOS DE PAQUETE PARA LA ASIGNACION DE OBSERVACIONES
      $lcSql = "SELECT MDATOS FROM D05MPAQ WHERE CIDPAQU = '{$this->paData['CIDPAQU']}'";
      $RS1 = $p_oSql->omExec($lcSql);
      if (!$RS1 or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP [OBSERVACIONES].";
         return false;
      }
      $laFilaPaq = $p_oSql->fetch($RS1);
      $lmDatos = $laFilaPaq[0];
      $lmDatos = json_decode($lmDatos, true);
      $lcDate = date('Y-m-d H:i:s');
      $lmDatos[] = ['CNRODNI' => $this->paData['CNRODNI'], 'CSECUEN' => 'PLANIF. Y PRESUP.', 'TGENERA' => $lcDate,
         'MOBSERV' => 'ASIGNACIÓN DE PARTIDA PRESUPUESTAL.'];
      $lmDatos = json_encode($lmDatos, true);

      $lcSql = "UPDATE D05MPAQ SET CESTADO = 'E', mdatos = '{$lmDatos}', cusucod = '{$this->paData['CCODUSU']}', tmodifi = now() WHERE CIDPAQU = '{$this->paData['CIDPAQU']}'";
      $RS2 = $p_oSql->omExec($lcSql);
      if (!$RS2 or $p_oSql->pnNumRow == 0) {
         $this->pcError = "HA OCURRIDO UN ERROR AL APROBAR EL PAQUETE, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }


   public function mxEnviarCorreoAprobarPaqueteDCRRIIFamiliasAnfitrionasPresupuesto($p_oSql) {
      $llOk = $this->mxGetDataEnviarCorreoPaquetesDCRRIIPresupuesto($p_oSql);
      if (!$llOk) {
         return false;
      }
      $lo = new CEmail();
      $llOk = $lo->omConnect();
      $lcBody = "<ul style='margin: 0.5em'>";
      foreach ($this->paDatos as $item) {
         $lcRow = "<li style='font-weight: normal; font-size: 14px; margin: 0.5em;'>{$item['CORDEN']}, PROVEEDOR {$item['CNRORUC']} - {$item['CRAZSOC']}, ( Alumno {$item['CNOMBRE']} ). </li>";
         $lcBody = $lcBody.$lcRow;
      }
      $lcBody = $lcBody."</ul>";
      $lcDate = date('Y-m-d H:i:s');
      $lcMensaje = "<!DOCTYPE html>
                     <html>
                     <body>
                     <table>
                           <colgroup>
                              <col style='background-color: #ececec'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>    
                           <thead>
                              <tr style='background-color: #ffffff; color:black'>
                                 <th colspan='3'>
                                    <div class='container'>
                                       UNIVERSIDAD CATÓLICA DE SANTA MARÍA
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                           </thead>
                           <tbody>
                              <tr style='background-color: #ffffff; color:black; text-align: justify'>
                                 <th colspan='3'>
                                    Se remite para su evaluación y continue su trámite las siguientes órdenes de servicio, con el fin de poder realizar el {$this->paData['CDESCRI']}:
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: left'>
                                 <th colspan='3'>
                                    {$lcBody}
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: left'>
                                 <th colspan='3'>
                                      <br>Dirección de Planificación y Presupuesto<br>
                                        {$lcDate}
                                      <br><br>
                                 </th>
                              </tr>
                           </tbody>
                        </table>
                     </body>
                     </html>";
//      $laEmails[0] = "gilmar.campana@ucsm.edu.pe";
//      $laEmails[0] = "ccaceres@ucsm.edu.pe";
//      $laEmails[0] = "vradm04@ucsm.edu.pe";
//      $laEmails[0] = "rgutierreza@ucsm.edu.pe";
//      $laEmails[1] = "logistica@ucsm.edu.pe";
//      $laEmails[2] = "opresupuesto@ucsm.edu.pe";
//      $laEmails[3] = "71231729@ucsm.edu.pe";
//      $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $laEmails];
      $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $this->paData['ACORREOS']];
//
//      echo json_encode(['OK' => true, 'MENSAJE' => "PAQUETE APROBADO Y ENVIADO A LOGÍSTICA PARA SU TRÁMITE.",
//         'CONTENIDO' => array_merge($this->paData, $this->paDatos, ['LODATA' => $lo->paData])]);
//      die;
//      return false;
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }
   protected function mxGetDataEnviarCorreoPaquetesDCRRIIPresupuesto($p_oSql) {
      //OBTENCION DE LA DESCRIPCION DEL PAQUETE
      $lcSql = "SELECT cEstado, cdescri FROM D05MPAQ WHERE cIdPaqu = '{$this->paData['CIDPAQU']}' AND cEstado != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL CONSULTAR EL PAQUETE PARA PAGO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CDESCRI'] = $laFila[1];

      //LISTAR ORDENES CREADAS PARA EL PAQUETE
      $lcSql = "select B.cNombre, B.cCodAlu, C.cNroRuc, D.cRazSoc, G.cCodAnt from D05MCOM A
                  INNER JOIN D05MALU B ON A.cidalum = B.cidalum
                  INNER JOIN D05MPRV C ON A.ccodigo = C.ccodigo
                  INNER JOIN S01MPRV D ON C.cNroRuc = D.cNroRuc
                  INNER JOIN E01PREQ E ON A.cIdRequ = E.cIdRequ
                  INNER JOIN E01PORD F ON E.cIdCoti = F.cIdCoti
                  INNER JOIN E01MORD G ON F.cIdOrde = G.cidorde
                  WHERE A.cIdPaqu = '{$this->paData['CIDPAQU']}' AND E.cEstado != 'X' AND F.cEStado != 'X'
                  ORDER BY D.cRazsoc";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL CONSULTAR EL PAQUETE PARA PAGO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1], 'CNRORUC' => $laFila[2], 'CRAZSOC' => str_replace('/', ' ', $laFila[3]), 'CORDEN' => $laFila[4] ];
      }

      //TRAER CORREOS PROCESO DCRRII
      $lcSql = "select mdatos from s01tvar where cnomvar = 'DCRRII.EMAILS'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = $laFila[0];
      $lmDatos = json_decode($lmDatos, true);
      $this->paData['ACORREOS'] = array_merge( $lmDatos['LOG'], $lmDatos['DCRRII']);
      return true;
   }


   function omRevisarPaquetePendienteFirmaLogistica() {
      $llOk = $this->mxValRevisarPaquetePendienteFirmaLogistica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxRevisarPaquetePendienteFirmaLogistica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValRevisarPaquetePendienteFirmaLogistica() {
      if (!isset($this->paData['CIDPAQU'])) {
         $this->pcError = "EL ID DEL PAQUETE NO ES VÁLIDO.";
         return false;
      }
      return true;
   }
   protected function mxRevisarPaquetePendienteFirmaLogistica($p_oSql) {
      //CONSULTA CABECERA DCRRII PAQUETE
      $lcSql = "SELECT A.CIDPAQU, A.CDESCRI, A.MDATOS::JSON->>'TGENERA' AS TGENERA, A.MDATOS::JSON->>'MOBSERV' AS MOBSERV, A.COBSERV, B.CDESCRI AS CESTADODES, A.CESTADO 
                  FROM D05MPAQ A
                  INNER JOIN V_S01TTAB B ON B.CCODTAB = '353' AND A.CESTADO = B.CCODIGO
                  WHERE CIDPAQU = '{$this->paData['CIDPAQU']}';";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData = ['CIDPAQU' => $laFila[0], 'CDESCRI' => $laFila[1], 'TGENERA' => $laFila[2], 'MOBSERV' => $laFila[3], 'COBSERV' => $laFila[4], 'CESTADODES' => $laFila[5], 'CESTADO' => $laFila[6]];
      //LISTAR DETALLE PAQUETE
      $lcSql = "SELECT J.cidorde, J.CCODANT, J.CNRORUC, E.CRAZSOC, J.dgenera, K.CDESCRI AS CTIPO, L.cdescri AS CMONEDA, J.nmonto, F.cDescri, A.mEnlace
               FROM D05MCOM A
               INNER JOIN E01MREQ F ON A.cidrequ = F.cidrequ
               INNER JOIN E01PREQ G ON F.cidrequ = G.cidrequ
               INNER JOIN E01MCOT H ON G.cidcoti = H.cidcoti
               INNER JOIN E01PORD I ON H.cidcoti = I.cidcoti
               INNER JOIN E01MORD J ON I.cidorde = J.cidorde
               INNER JOIN S01MPRV E ON J.cnroruc = E.cnroruc
               LEFT OUTER JOIN S01TTAB K ON K.cCodigo = J.ctipo AND trim(K.cCodTab) = '075'
               LEFT OUTER JOIN S01TTAB L ON L.cCodigo = J.cmoneda AND trim(L.cCodTab) = '007'
               WHERE cIdPaqu = '{$this->paData['CIDPAQU']}' AND A.CESTADO <> 'X' AND F.CESTADO <> 'X' AND G.CESTADO <> 'X'
                 AND H.CESTADO <> 'X' AND I.CESTADO <> 'X' AND J.CESTADO <> 'X' ORDER BY E.cNroRuc";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO AL CONSULTAR EN LA BASE DE DATOS, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      while($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CIDORDE' => $laFila[0], 'CCODANT' => $laFila[1], 'CNRORUC' => $laFila[2], 'CRAZSOC' => str_replace('/', ' ', $laFila[3]),
            'TGENERA' => $laFila[4], 'CTIPO' => $laFila[5], 'CMONEDA' => $laFila[6], 'NMONTO' => $laFila[7], 'CDESCRI' => $laFila[8], 'MENLACE' => $laFila[9]];
      }

      //OBTENCION DE HISTORICO
      $lcSql = "select value->>'CNRODNI', value->>'CSECUEN', value->>'TGENERA', value->>'MOBSERV', value->>'CSECUEN' 
                FROM json_array_elements((SELECT mdatos from d05mpaq where cidpaqu = '{$this->paData['CIDPAQU']}')::JSON);";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
         return false;
      }
      $laTmpHistor = [];
      while($laFila = $p_oSql->fetch($RS)) {
         if($laFila[1] == 'RRHH') {
            $this->paData['MENLACE'] = $laFila[3];
         }
         $laTmpHistor = ['CNRODNI' => $laFila[0], 'CDESCCO' => $laFila[1], 'TGENERA' => $laFila[2], 'MOBSERV' => $laFila[3], 'CSECUEN' => $laFila[4]];
         $lcSql = "SELECT CNOMBRE FROM V_S01TUSU_1 WHERE CNRODNI = '{$laTmpHistor['CNRODNI']}'";
         $RS2 = $p_oSql->omExec($lcSql);
         if (!$RS2) {
            $this->pcError = "ERROR AL CONSULTAR EL DETALLE DEL PAQUETE, CONTACTE CON SOPORTE DEL ERP";
            return false;
         }
         $laFila2 = $p_oSql->fetch($RS2);
         $this->paData['AHISTOR'][] = array_merge($laTmpHistor, ['CNOMBRE' => str_replace('/', ' ', $laFila2[0])]);
      }
      return true;
   }
   public function omAprobarPaqueteDcrriiLogistica() {
      $llOk = $this->mxValAprobarPaqueteDcrriiLogistica();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxAprobarPaqueteDcrriiLogistica($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->mxAprobarPaqueteDcrriiLogisticaXls($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $llOk = $this->omEnviarCorreoAprobarPaqueteDcrriiLogisitica($loSql);
      if (!$llOk) {
         $this->pcError = "NO SE PUDO ENVIAR EL CORREO , CONTACTE CON SOPORTE DEL ERP.";
         $loSql->rollback();
         return false;
      }

//      $this->pcError = 'ERROR DE PRUEBA';
//      $loSql->rollback();
//      return false;
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAprobarPaqueteDcrriiLogistica() {
      if (!isset($this->paData['CIDPAQU']) OR $this->paData['CIDPAQU'] == '') {
         $this->pcError = "EL ID DEL PAQUETE NO ES VÁLIDO.";
         return false;
      } elseif (!isset($this->paData['CCODUSU']) or strlen($this->paData['CCODUSU']) != 4) {
         $this->pcError = "EL CODIGO DE USUARIO ES INVÁLIDO";
         return false;
      } elseif (!isset($this->paData['CNIVEL']) or $this->paData['CNIVEL'] != 'JL') {
         $this->pcError = "EL NIVEL DE PERMISOS DEL USUARIO NO PERMITE REALIZAR LA ACCIÓN.";
         return false;
      }
      return true;
   }
   protected function mxAprobarPaqueteDcrriiLogistica($p_oSql) {
//      $this->paData = array_merge($this->paData, ['CMONEDA' => '1', 'CIDACTI' => '00009531', 'CNRODOC' => 'OFICIO S/R']);
      $lcJson = json_encode($this->paData);
      $lcJson = str_replace("'", "''", $lcJson);
      $lcSql = "SELECT P_E01MREQ_104('$lcJson')";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $laRes = json_decode($laFila[0], true);
      $laFila[0] = (!$laFila[0]) ? '{"ERROR": "ERROR DE EJECUCION DE BASE DE DATOS, CONTACTE CON SOPORTE DEL ERP."}' : $laFila[0];
      if (!isset($laRes[0]['OK'])) {
         $this->pcError = $laRes[0]['ERROR'];
         return false;
      }
      $this->paData['CIDPAQU'] = $laRes[0]['CIDPAQU'];
      return true;
   }
   protected function mxAprobarPaqueteDcrriiLogisticaXls($p_oSql) {
      $anio = date('Y');
      $lcSql = "SELECT H.CCODANT, H.CNRORUC, H.CRAZSOC, G.CMONEDA, I.CDESCRI AS CMONEDA, G.CNROCOM, J.CDESCRI AS CTIPCOM, G.nmonto,
                K.CDESCRI AS CDESCCO, G.CIDCOMP, G.CCODIOL, F.CTIPO, F.CCODANT, B.CDESCRI, 'PAGOS DCRRII ' || TO_CHAR(now(), 'MONTH')
               FROM D05MCOM A
               INNER JOIN E01MREQ B ON A.cidrequ = B.cidrequ
               INNER JOIN E01PREQ C ON B.cidrequ = C.cidrequ
               INNER JOIN E01MCOT D ON C.cidcoti = D.cidcoti
               INNER JOIN E01PORD E ON D.cidcoti = E.cidcoti
               INNER JOIN E01MORD F ON E.cidorde = F.cidorde
               INNER JOIN E01MFAC G ON F.cidorde = G.cidorde
               INNER JOIN S01MPRV H ON G.cnroruc = H.cnroruc
               LEFT OUTER JOIN S01TTAB I ON I.cCodigo = G.cmoneda AND trim(I.cCodTab) = '007'
               LEFT OUTER JOIN S01TTAB J ON J.cCodigo = G.ctipcom AND trim(J.cCodTab) = '087'
               LEFT OUTER JOIN v_s01tcco_1 K ON K.CCENCOS = G.CCENCOS
               WHERE CIDPAQU = '{$this->paData['CIDPAQU']}' AND A.CESTADO <> 'X' AND B.CESTADO <> 'X' AND C.CESTADO <> 'X' AND D.CESTADO <> 'X'
                 AND E.CESTADO <> 'X' AND F.CESTADO <> 'X' AND G.CESTADO <> 'X' AND H.cestado <> 'X' ORDER BY H.CRAZSOC";
      $R1 = $p_oSql->omExec($lcSql);
      $loXls = new CXls();
      $loXls->openXlsIO('Erp2620', 'R');
      # Cabecera
      $loXls->sendXls(0, 'K', 3, date("Y-m-d"));
      $i = 4;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laDatos[] = ['CCODANTPRV'=> $laTmp[0], 'CNRORUC'=> $laTmp[1], 'CRAZSOC'=> str_replace('/', ' ', $laTmp[2]),
            'CMONCOD'=> $laTmp[3], 'CMONEDA'=> $laTmp[4], 'CNROCOM'=> $laTmp[5], 'CTIPCOM'=> $laTmp[6], 'NMONTO'=> $laTmp[7],
            'CDESCCO'=> $laTmp[8], 'CIDCOMP'=> $laTmp[9], 'CCODIOL'=> $laTmp[10], 'CTIPORD'=> $laTmp[11], 'CCODANT'=> $laTmp[12],
            'CDESCRI'=> $laTmp[13], 'CDESCRIOBS'=> $laTmp[17]];
      }
      if (count($laDatos) == 0) {
         $this->pcError = "NO HAY COMPROBANTES PARA MOSTRAR";
         return false;
      }
      foreach ($laDatos as $key => $laFila) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $key + 1);
         $loXls->sendXls(0, 'B', $i, $laFila['CCODANTPRV']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNRORUC']);
         $loXls->sendXls(0, 'D', $i, $laFila['CRAZSOC']);
         $loXls->sendXls(0, 'E', $i, $laFila['CTIPCOM']);
         $loXls->sendXls(0, 'F', $i, $laFila['CNROCOM']);
         if ($laFila['CMONCOD'] == '1') {
            $loXls->sendXls(0, 'G', $i, $laFila['NMONTO']);
            $loXls->sendXls(0, 'H', $i, '');
         } else {
            $loXls->sendXls(0, 'G', $i, '');
            $loXls->sendXls(0, 'H', $i, $laFila['NMONTO']);
         }

         $loXls->sendXls(0, 'I', $i, $laFila['CDESCCO']);
         $loXls->sendXls(0, 'J', $i, $laFila['CIDCOMP']);
         if ($laFila['CTIPORD'] == 'S') {
            $loXls->sendXls(0, 'K', $i, '');
            $loXls->sendXls(0, 'L', $i, $laFila['CCODANT']);
         } else {
            $loXls->sendXls(0, 'K', $i,  $laFila['CCODANT']);
            $loXls->sendXls(0, 'L', $i, '');
         }
         $loXls->sendXls(0, 'M', $i, $laFila['CCODIOL']);
         $loXls->sendXls(0, 'N', $i, '');
         $loXls->sendXls(0, 'O', $i, $laFila['CDESCRI']);
      }
      $loXls->closeXlsIO();
      $this->paFile = $loXls->pcFile;

      return true;
   }
   public function omEnviarCorreoAprobarPaqueteDcrriiLogisitica($p_oSql) {
      $llOk = $this->mxGetDataEnviarCorreoPaquetesLogistica($p_oSql);
      if (!$llOk) {
         return false;
      }
      $lo = new CEmailFile();
      $llOk = $lo->omConnect();

//      $lcBody = "<ul style='margin: 0.5em'>";
//      foreach ($this->paDatos as $item) {
//         $lcRow = "<li style='font-weight: normal; font-size: 14px; margin: 0.5em;'>{$item['CORDEN']}, PROVEEDOR {$item['CNRORUC']} - {$item['CRAZSOC']}, ( Alumno {$item['CNOMBRE']} ). </li>";
//         $lcBody = $lcBody.$lcRow;
//      }
//      $lcBody = $lcBody."</ul>";
      $lcDate = date('Y-m-d H:i:s');
      $lcMensaje = "<!DOCTYPE html>
                     <html>
                     <body>
                     <table>
                           <colgroup>
                              <col style='background-color: #ececec'>
                              <col style='background-color: #ffffff;'>
                           </colgroup>    
                           <thead>
                              <tr style='background-color: #ffffff; color:black'>
                                 <th colspan='3'>
                                    <div style='text-align: center'>
                                       UNIVERSIDAD CATÓLICA DE SANTA MARÍA
                                    </div>
                                    <br>
                                 </th>
                              </tr>
                           </thead>
                           <tbody>
                              <tr style='background-color: #ffffff; color:black; text-align: justify;'>
                                 <th colspan='3'>
                                    Se aprueba el {$this->paData['CDESCRI']}, se remite para el pago de las OLS.
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: justify;'>
                                 <th colspan='3'>
                                    Se remite suspension de 4ta categoria de los proveedores.<br>
                                    <a 
                                    target='_blank' 
                                    href='https://ucsmedu-my.sharepoint.com/personal/ocai_ucsm_edu_pe/_layouts/15/onedrive.aspx?ct=1684936440082&or=OWA%2DNT&cid=21acfdb1%2D940a%2D96a3%2Dda47%2Ddf103eaeb523&ga=1&id=%2Fpersonal%2Focai%5Fucsm%5Fedu%5Fpe%2FDocuments%2F1%2E%20COMPU%20ADMINISTRATIVA%2FANFITRIONAS%2F2023%2Fsuspensi%C3%B3n%20de%204ta%20categor%C3%ADa&view=0'
                                    >Ver archivos</a>.
                                 </th>
                              </tr>
                              <tr style='background-color: #ffffff; color:black; text-align: left;'>
                                 <th colspan='3'>
                                      <br>Dirección de Logistica<br>
                                        {$lcDate}
                                      <br><br>
                                 </th>
                              </tr>
                           </tbody>
                        </table>
                     </body>
                     </html>";
//
//      echo json_encode(['OK' => false, 'MENSAJE' => 'ERROR DE PRUEBA', 'CONTENIDO' => array_merge($this->paData), 'CONTENIDO2' => $this->paFile]);
//      $p_oSql->rollback();
//      die;
//      return false;
      $lo->paData = ['CSUBJEC' => $this->paData['CDESCRI'], 'CBODY' => $lcMensaje, 'AEMAILS' => $this->paData['ACORREOS']];
      $lo->omAñadirDocumento('/var/www/html/ERP-II'.substr($this->paFile, 1, strlen($this->paFile) - 1), 'LISTA PAGOS DCRRI');
      $llOk = $lo->omSend();
      if (!$llOk) {
         $this->pcError = $lo->pcError;
         return false;
      }
      return true;
   }
   protected function mxGetDataEnviarCorreoPaquetesLogistica($p_oSql) {
      //OBTENCION DE LA DESCRIPCION DEL PAQUETE
      $lcSql = "SELECT cEstado, cdescri FROM D05MPAQ WHERE cIdPaqu = '{$this->paData['CIDPAQU']}' AND cEstado != 'X'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL CONSULTAR EL PAQUETE PARA PAGO";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paData['CDESCRI'] = $laFila[1];

      //LISTAR ORDENES CREADAS PARA EL PAQUETE
      $lcSql = "select B.cNombre, B.cCodAlu, C.cNroRuc, D.cRazSoc, G.cCodAnt from D05MCOM A
                  INNER JOIN D05MALU B ON A.cidalum = B.cidalum
                  INNER JOIN D05MPRV C ON A.ccodigo = C.ccodigo
                  INNER JOIN S01MPRV D ON C.cNroRuc = D.cNroRuc
                  INNER JOIN E01PREQ E ON A.cIdRequ = E.cIdRequ
                  INNER JOIN E01PORD F ON E.cIdCoti = F.cIdCoti
                  INNER JOIN E01MORD G ON F.cIdOrde = G.cidorde
                  WHERE A.cIdPaqu = '{$this->paData['CIDPAQU']}' AND E.cEstado != 'X' AND F.cEStado != 'X'
                  ORDER BY D.cRazsoc";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HUBO UN ERROR AL CONSULTAR EL PAQUETE PARA PAGO";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1], 'CNRORUC' => $laFila[2], 'CRAZSOC' => str_replace('/', ' ', $laFila[3]), 'CORDEN' => $laFila[4] ];
      }

      //TRAER CORREOS PROCESO DCRRII
      $lcSql = "select mdatos from s01tvar where cnomvar = 'DCRRII.EMAILS'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'ERROR DE EJECUCION DE COMANDO SQL';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = $laFila[0];
      $lmDatos = json_decode($lmDatos, true);
      $this->paData['ACORREOS'] = array_merge( $lmDatos['DCONTA'], $lmDatos['DCRRII']);
      return true;
   }


}












