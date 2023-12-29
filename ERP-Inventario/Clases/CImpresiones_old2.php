<?php

require_once 'Clases/CBase.php';
require_once 'Clases/CSql.php';
require_once 'Clases/CEmail.php';

class CImpresiones extends CBase {
   public $paData, $paDatos, $laData, $laDatos;

   public function __construct()
   {
      parent::__construct();
      $this->paData = $this->pcError = $this->pcTipTrb = null;
      $this->paDatos = $this->laDatos = [];
   }

   // -------------------------------------------------------
   // Carga Inicial Bandeja de Pedidos de impresiones
   // 2023-07-10  -  GCQ
   // -------------------------------------------------------
   public function omInitGenerarPedidoImpresion()
   {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      // Valida usuario (administrativo o docente)
      $llOk = $this->mxInitGenerarPedidoImpresion($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
//      $loSqlIns = new CSql();
//      $llOk = $loSqlIns->omConnect(2);
//      if (!$llOk) {
//         $this->pcError = $loSqlIns->pcError;
//         return false;
//      }
      // Listar solicitudes del trabajador
//      $llOk = $this->mxInitGenerarPedidoImpresionSolicitudes($loSqlIns);
//      $loSqlIns->omDisconnect();
      return $llOk;
   }
   protected function mxValParams()
   {
      if (!isset($this->paData['CCODUSU']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CCODUSU'])) {
         $this->pcError = 'VALOR DEL CÓDIGO DE USUARIO ES INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxInitGenerarPedidoImpresion($p_oSql)
   {
      $lcSql = "SELECT * FROM A01MDOC A
                  INNER JOIN V_S01TUSU_1 B ON B.CCODUSU = A.CCODDOC
                  WHERE A.CCODDOC = '{$this->paData['CCODUSU']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "OCURRIO UN ERROR AL CONSULTAR LA INFORMACIÓN DEL USUARIO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      //RECUPERAR CENTROS DE COSTOS
      $this->paData = array_merge($this->paData, ['CTIPTRB' => ($p_oSql->pnNumRow == 0) ? 'A' : 'D', 'ACENCOS' => []]);

      $laDatos = [];
      $lcSql = "SELECT DISTINCT cCenCos, cDesCen FROM V_S01PCCO WHERE cCodUsu = '{$this->paData['CCODUSU']}' AND cEstado = 'A' ORDER BY cDesCen";
      $RS = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($RS)) {
         $laDatos[] = ['CCENCOS' => $laFila[0], 'CDESCCO' => $laFila[1]];
      }
      $this->paData['ACENCOS'] = $laDatos;

      //LISTA CENTROS DE COSTOS QUE PUEDEN REALIZAR PEDIDOS DE IMPRESIONES PARA PERSONAS FORANEAS
      $lcSql = "SELECT MDATOS FROM S01TVAR WHERE CNOMVAR = 'IMPFORA.ACENCOS'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "OCURRIO UN ERROR AL CONSULTAR LA INFORMACIÓN DEL USUARIO, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = json_decode($laFila[0], true);
      $lmDatos = $lmDatos['CCENCOS'];
      $laDatos = [];
      foreach ($lmDatos as $loCencos) {
         $this->paDatos[] = $loCencos;
      }
      return true;
   }

   public function mxListarImpresionesTrabajador() {
      $llOk = $this->omValListarImpresionesTrabajador();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $loSqlIns = new CSql();
      $llOk = $loSqlIns->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSqlIns->pcError;
         return false;
      }
      // Listar solicitudes del trabajador
      $llOk = $this->omListarImpresionesTrabajador($loSqlIns);
      $loSqlIns->omDisconnect();
      return $llOk;
   }
   protected function omValListarImpresionesTrabajador() {
      if (!isset($this->paData['CCODUSU']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CCODUSU'])) {
         $this->pcError = 'VALOR DEL CÓDIGO DE USUARIO ES INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function omListarImpresionesTrabajador($poSql) {
      $lcSql = "SELECT A.cIdImpr, A.cCodUsu, TO_CHAR(A.tSolici, 'YYYY-MM-DD'), A.cEstado, TO_CHAR(A.tRecojo, 'YYYY-MM-DD HH24:MI'), 
                    TO_CHAR(A.tAproba, 'YYYY-MM-DD'), replace(B.CNOMBRE, '/', ' '), C.CDESCRI
                     FROM B11MIMP A
                     LEFT JOIN v_s01tusu_1 B ON A.cCodUsu = B.CCODUSU
                     LEFT JOIN V_S01TTAB C ON A.CESTADO = C.CCODIGO AND C.CCODTAB = '520'
                     WHERE A.CCODUSU = '{$this->paData['CCODUSU']}' AND CTIPSOL = '{$this->paData['CTIPTRB']}' AND CCENCOS = '{$this->paData['CCENCOS']}'";
      $RS = $poSql->omExec($lcSql);
      $this->paDatos = [];
      while($laFila = $poSql->fetch($RS)) {
         $this->paDatos[] = ['CIDIMPR' => $laFila[0], 'CCODUSU' => $laFila[1], 'TSOLICI' => ($laFila[3] == 'A' ? '-' : $laFila[2]),
            'CESTADO' => $laFila[3], 'TRECOJO' => ($laFila[4] == NULL ? '-' : $laFila[4]),
            'TAPROBA' => ($laFila[5] == NULL ? '-' : $laFila[5]), 'CNOMEMP' => $laFila[6], 'CESTDES' => $laFila[7]];
      }
      return true;
   }

   // -------------------------------------------------------
   // Listar cursos por docente
   // 2023-07-10  -  GCQ
   // -------------------------------------------------------
   public function omBuscarCursosDocente() {
      $llOk = $this->mxValParams();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(5);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      //VALIDACION TIPO DE USUARIO -- ADMINISTRATIVO / DOCENTE
      $llOk = $this->mxBuscarCursosDocente($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      return true;
   }
   protected function mxBuscarCursosDocente($poSql) {
      $lcSql = "SELECT A.CCODCUR, B.cdescri,
                   SUM(CASE WHEN A.CSECGRU  ~ '^[0-9\.]+$' THEN 0 ELSE 1 END) AS TEORIA,
                   SUM(CASE WHEN A.CSECGRU  ~ '^[0-9\.]+$' THEN 1 ELSE 0 END) AS PRACTICA
                   FROM v_a01pmat_5 A
                              LEFT JOIN A02MCUR B ON A.CCODCUR = B.CCODCUR
                              WHERE A.CCODDOC = '{$this->paData['CCODUSU']}' GROUP BY A.CCODCUR, B.CDESCRI";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'No se pudo listar los cursos del docente';
         return false;
      }
      while ($laFila = $poSql->fetch($RS)) {
         $this->paDatos[] = ['CCODCUR' => $laFila[0], 'CNOMCUR' => $laFila[1], 'NTEORIA' => $laFila[2], 'NPRACTI' => $laFila[3]];
      }
      return true;
   }

   // -------------------------------------------------------
   // Agregar detalle de Pedidos de impresiones
   // 2023-07-10  -  GCQ
   // -------------------------------------------------------
   public function omAgregarDetalleImpresionDocente() {
      $llOk = $this->mxValAgregarDetalleImpresionDocente();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      //VALIDACION TIPO DE USUARIO -- ADMINISTRATIVO / DOCENTE
      $llOk = $this->mxAgregarDetalleImpresionDocente($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAgregarDetalleImpresionDocente() {
      if (!isset($this->paData['CCODUSU']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CCODUSU'])) {
         $this->pcError = 'VALOR DEL CÓDIGO DE USUARIO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCODCUR']) || !preg_match('/^[0-9]{7}$/', $this->paData['CCODCUR'])) {
         $this->pcError = 'VALOR DEL CÓDIGO DEL CURSO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NCANTID']) || $this->paData['NCANTID'] < 1) {
         $this->pcError = 'CANTIDAD DE COPIAS PARA EL PEDÍDO ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CCOLOR']) || !preg_match('/^[S|N]{1}$/', $this->paData['CCOLOR'])) {
         $this->pcError = 'VALOR  DEL COLOR DE IMPRESIÓN ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CCODUSU']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CCODUSU'])) {
         $this->pcError = 'VALOR DE CÓDIGO DE USUARIO ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CCENCOS']) || !preg_match('/^[0-9A-Z]{3}$/', $this->paData['CCENCOS'])) {
         $this->pcError = 'VALOR DEL CENTRO DE COSTOS ES INVÁLIDO';
         return false;
      }
      if (isset($this->paData['CCLASE']) AND preg_match('/^[T|P|B|C|D]{1}$/', $this->paData['CCLASE'])) {
         if (in_array($this->paData['CCLASE'], ['T', 'P'])) {
            if (!isset($this->paData['CTIPEVA']) || !preg_match('/^[A-Z]{1}$/', $this->paData['CTIPEVA'])) {
               $this->pcError = 'VALOR TIPO DE EVALUACIÓN ES INVÁLIDO';
               return false;
            } elseif (!isset($this->paData['CFORMAT']) || !preg_match('/^[A-Z]{1}$/', $this->paData['CFORMAT'])) {
               $this->pcError = 'VALOR DEL FORMATO DEL PEDÍDO ES INVÁLIDO';
               return false;
            } elseif (!isset($this->paData['NCARAS']) || $this->paData['NCARAS'] < 1) {
               $this->pcError = 'CANTIDAD DE PÁGINAS PARA EL PEDÍDO ES INVÁLIDO';
               return false;
            }  elseif (!isset($this->paData['CDOBCAR']) || !preg_match('/^[S|N]{1}$/', $this->paData['CDOBCAR'])) {
               $this->pcError = 'VALOR DEL DEL TIPO DE IMPRESIÓN ES INVÁLIDO';
               return false;
            }
         }
      } else {
         $this->pcError = 'VALOR DE TIPO DE PEDIDO ES INVÁLIDO';
         return false;
      }

      if ($this->paData['CTIPTRB'] == 'A') {
         if (!isset($this->paData['CENLACE']) || $this->paData['CENLACE'] == '') {
            $this->pcError = 'VALOR DE ENLACE ES INVÁLIDO';
            return false;
         }  elseif (!isset($this->paData['CANILLA']) || $this->paData['CANILLA'] == '') {
            $this->pcError = 'VALOR PARA ANILLADO ES INVÁLIDO';
            return false;
         }
      }
      return true;
   }
   protected function mxAgregarDetalleImpresionDocente($poSql) {

      //VALIDACION QUE EXISTA MAESTRO DE IMPRESION
      if ($this->paData['CIDIMPR'] != '') {
         $lcSql = "SELECT * FROM B11MIMP WHERE CESTADO <> 'X' AND CIDIMPR = '{$this->paData['CIDIMPR']}'";
         $RS = $poSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = 'Ocurrio un error al agregar la solicitud. Contacte con soporte del ERP.';
            return false;
         }
         $laFila = $poSql->fetch($RS);
         if ($laFila[0] == null) {
            $this->pcError = 'No se pudo encontrar el id de impresión. Contacte con soporte del ERP.';
            return false;
         }
      } else {
         // AGREGA MAESTRO DE IMPRESION
         $lcCorrel = '00000';
         $lcSql = "SELECT CASE WHEN MAX(CIDIMPR) IS NULL THEN '00000' ELSE MAX(CIDIMPR) END AS CCORREL FROM B11MIMP";
         $RS = $poSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = "Ha ocurrido un error al agregar la solicitud. Contacte con soporte del ERP.[1]";
            return false;
         }
         $laFila = $poSql->fetch($RS);
         if ($laFila[0] != null) {
            $lcCorrel = $laFila[0];
         }
         $this->paData['CIDIMPR'] = fxCorrelativo($lcCorrel);

         $lcTipSol = $this->paData['CTIPTRB'];
         if (isset($this->paData['CTIPIMP']) and $this->paData['CTIPIMP'] == 'S') {
            $lcTipSol = 'F';
         }

         // INSERTAR MAESTRO DE IMPRESIONES
         $lcSql = "INSERT INTO B11MIMP (CIDIMPR, CTIPSOL, CCODUSU, CCENCOS, CUSUCOD, CESTADO) 
                    VALUES ('{$this->paData['CIDIMPR']}', '{$lcTipSol}', '{$this->paData['CCODUSU']}', 
                            '{$this->paData['CCENCOS']}', '{$this->paData['CCODUSU']}', 'P')";
         $RS = $poSql->omExec($lcSql);
         if (!$RS) {
//            $this->pcError = $lcSql;
            $this->pcError = "Ha ocurrido un error al agregar la solicitud. Contacte con soporte del ERP.[2]";
            return false;
         }
      }

      $lmDatos = ['NCARAS' => $this->paData['NCARAS'], 'CDOBCAR' => $this->paData['CDOBCAR'],
         'CCOLOR' => $this->paData['CCOLOR'], 'CTIPEVA' => $this->paData['CTIPEVA'], 'CDESCRI' => strtoupper($this->paData['CDESCRI'])];

      if ($this->paData['CTIPTRB'] == 'A') {
         $lmDatos = array_merge($lmDatos, ['CANILLA' => $this->paData['CANILLA'], 'CENLACE' => $this->paData['CENLACE']]);
      }
      $lmDatos = json_encode($lmDatos, true);

      //AGREGAR DETALLE DE IMPRESION
      $lcSql = "INSERT INTO B11DIMP (CIDIMPR, CCODCUR, CESTADO, CCLASE, CFORMAT, NCANTID, MDATA, CUSUCOD)
                VALUES ('{$this->paData['CIDIMPR']}', '{$this->paData['CCODCUR']}', 'A', '{$this->paData['CCLASE']}', 
                        '{$this->paData['CFORMAT']}', '{$this->paData['NCANTID']}', '{$lmDatos}', '{$this->paData['CCODUSU']}' )";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "Ha ocurrido un error al agregar la solicitud. Contacte con soporte del ERP.[3]";
         return false;
      }
      return true;
   }


   // -------------------------------------------------------
   // Editar detalle de Pedidos de impresiones
   // 2023-07-12  -  GCQ
   // -------------------------------------------------------
   public function omEditarDetalleImpresionDocente() {
      $llOk = $this->mxValEditarDetalleImpresionDocente();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      //VALIDACION TIPO DE USUARIO -- ADMINISTRATIVO / DOCENTE
      $llOk = $this->mxEditarDetalleImpresionDocente($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValEditarDetalleImpresionDocente() {
      if (!isset($this->paData['CCODUSU']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CCODUSU'])) {
         $this->pcError = 'VALOR DEL CÓDIGO DE USUARIO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCODCUR']) || !preg_match('/^[0-9]{7}$/', $this->paData['CCODCUR'])) {
         $this->pcError = 'VALOR DEL CÓDIGO DEL CURSO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CCLASE']) || !preg_match('/^[T|P|B|C|D]{1}$/', $this->paData['CCLASE'])) {
         $this->pcError = 'VALOR DE TIPO DE PEDIDO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NCANTID']) || $this->paData['NCANTID'] < 1) {
         $this->pcError = 'CANTIDAD DE COPIAS PARA EL PEDÍDO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CTIPEVA']) || !preg_match('/^[A-Z]{1}$/', $this->paData['CTIPEVA'])) {
         $this->pcError = 'VALOR TIPO DE EVALUACIÓN ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['CFORMAT']) || !preg_match('/^[A-Z]{1}$/', $this->paData['CFORMAT'])) {
         $this->pcError = 'VALOR DEL FORMATO DEL PEDÍDO ES INVÁLIDO';
         return false;
      } elseif (!isset($this->paData['NCARAS']) || $this->paData['NCARAS'] < 1) {
         $this->pcError = 'CANTIDAD DE PÁGINAS PARA EL PEDÍDO ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CDOBCAR']) || !preg_match('/^[S|N]{1}$/', $this->paData['CDOBCAR'])) {
         $this->pcError = 'VALOR DEL DEL TIPO DE IMPRESIÓN ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CCOLOR']) || !preg_match('/^[S|N]{1}$/', $this->paData['CCOLOR'])) {
         $this->pcError = 'VALOR  DEL COLOR DE IMPRESIÓN ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CCODUSU']) || !preg_match('/^[0-9A-Z]{4}$/', $this->paData['CCODUSU'])) {
         $this->pcError = 'VALOR DE CÓDIGO DE USUARIO ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CCENCOS']) || !preg_match('/^[0-9A-Z]{3}$/', $this->paData['CCENCOS'])) {
         $this->pcError = 'VALOR DEL CENTRO DE COSTOS ES INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['CIDIMPR']) || !preg_match('/^[0-9A-Z]{5}$/', $this->paData['CIDIMPR'])) {
         $this->pcError = 'VALOR DE ID DE IMPRESIÓN INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['NSERIAL']) || $this->paData['NSERIAL'] < 1) {
         $this->pcError = 'SERIAL DE IMPRESIÓN INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxEditarDetalleImpresionDocente($poSql) {

      //VALIDACION QUE EXISTA MAESTRO DE IMPRESION
      $lcSql = "SELECT CIDIMPR FROM B11MIMP WHERE CESTADO = 'P' AND CIDIMPR = '{$this->paData['CIDIMPR']}'";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al agregar la solicitud. Contacte con soporte del ERP.';
         return false;
      }
      $laFila = $poSql->fetch($RS);
      if ($laFila[0] == null) {
         $this->pcError = 'No se pudo encontrar el id de impresión. Contacte con soporte del ERP.';
         return false;
      }
//      $lmDatos = json_decode($laFila[0], true);
      $lmDatos = ['NCARAS' => $this->paData['NCARAS'], 'CDOBCAR' => $this->paData['CDOBCAR'],
         'CCOLOR' => $this->paData['CCOLOR'], 'CTIPEVA' => $this->paData['CTIPEVA'], 'CDESCRI' => strtoupper($this->paData['CDESCRI'])];
      if ($this->paData['CTIPTRB'] == 'A') {
         $lmDatos = array_merge($lmDatos, ['CANILLA' => $this->paData['CANILLA'], 'CENLACE' => $this->paData['CENLACE']]);
      }
      $lmDatos = json_encode($lmDatos);

      //AGREGAR DETALLE DE IMPRESION
      $lcSql = "UPDATE B11DIMP SET CCODCUR = '{$this->paData['CCODCUR']}', CCLASE = '{$this->paData['CCLASE']}', CFORMAT = '{$this->paData['CFORMAT']}', 
                   NCANTID = '{$this->paData['NCANTID']}', MDATA = '{$lmDatos}', CUSUCOD = '{$this->paData['CCODUSU']}' 
               WHERE CIDIMPR = '{$this->paData['CIDIMPR']}' and NSERIAL = {$this->paData['NSERIAL']}";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "Ha ocurrido un error al agregar la solicitud. Contacte con soporte del ERP.";
         return false;
      }
      return true;
   }


   // -------------------------------------------------------
   // Editar detalle de Pedidos de impresiones
   // 2023-07-12  -  GCQ
   // -------------------------------------------------------
   public function omAnularDetalleImpresion() {
      $llOk = $this->mxValAnularDetalleImpresion();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      //Anulacion de detalle de impresion
      $llOk = $this->mxAnularDetalleImpresion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValAnularDetalleImpresion() {
      if (!isset($this->paData['CIDIMPR']) || !preg_match('/^[0-9A-Z]{5}$/', $this->paData['CIDIMPR'])) {
         $this->pcError = 'VALOR DE ID DE IMPRESIÓN INVÁLIDO';
         return false;
      }  elseif (!isset($this->paData['NSERIAL']) || $this->paData['NSERIAL'] < 1) {
         $this->pcError = 'SERIAL DE IMPRESIÓN INVÁLIDO';
         return false;
      }
      return true;
   }
   protected function mxAnularDetalleImpresion($poSql) {
      //VALIDACION QUE EXISTA MAESTRO DE IMPRESION
      $lcSql = "SELECT CIDIMPR FROM B11MIMP WHERE CESTADO = 'P' AND CIDIMPR = '{$this->paData['CIDIMPR']}'";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al anular el detalle. Contacte con soporte del ERP.';
         return false;
      }
      $laFila = $poSql->fetch($RS);
      if ($laFila[0] == null) {
         $this->pcError = 'No se pudo encontrar el id de impresión. Contacte con soporte del ERP.';
         return false;
      }
//      $lmDatos = json_decode($laFila[0], true);
      $lmDatos = ['NCARAS' => $this->paData['NCARAS'], 'CDOBCAR' => $this->paData['CDOBCAR'],
         'CCOLOR' => $this->paData['CCOLOR'], 'CTIPEVA' => $this->paData['CTIPEVA'], 'CDESCRI' => strtoupper($this->paData['CDESCRI'])];
      $lmDatos = json_encode($lmDatos);

      //AGREGAR DETALLE DE IMPRESION
      $lcSql = "UPDATE B11DIMP SET CESTADO = 'X' WHERE CIDIMPR = '{$this->paData['CIDIMPR']}' and NSERIAL = {$this->paData['NSERIAL']}";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "Ha ocurrido un error al agregar la solicitud. Contacte con soporte del ERP.";
         return false;
      }
      return true;
   }


   // -------------------------------------------------------
   // Ver detalle de Pedidos de impresiones
   // 2023-07-10  -  GCQ
   // -------------------------------------------------------
   public function omVerImpresion() {
      $llOk = $this->mxValVerImpresion();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = 'OCURRIO UN ERROR AL CONECTAR CON LA BASE DE DATOS';
         return false;
      }
      //VALIDACION TIPO DE USUARIO -- ADMINISTRATIVO / DOCENTE
      $llOk = $this->mxVerImpresion($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValVerImpresion() {
      if (!isset($this->paData['CCODUSU']) || !(preg_match('/^[A-Z0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE DOCENTE INVÁLIDO O NO DEFINIDO.';
         return false;
      } elseif (!isset($this->paData['CIDIMPR']) || !(preg_match('/^[A-Z0-9]{5}$/', $this->paData['CIDIMPR']))) {
         $this->pcError = 'ID DE IMPRESIÓN INVÁLIDO O NO DEFINIDO.';
         return false;
      }
      return true;
   }
   protected function mxVerImpresion($poSql) {
      $lcSql = "select A.NSERIAL, A.CIDIMPR, A.CCODCUR, B.CDESCRI AS CNOMCUR, C.CDESCRI AS CESTADO, A.CESTADO, D.CDESCRI AS CCLASE, A.CCLASE, 
                E.CDESCRI AS CFORMAT, A.CFORMAT, MDATA::JSON->>'NCARAS' AS NCARAS, MDATA::JSON->>'CDOBCAR' AS CDOBCAR, MDATA::JSON->>'CTIPEVA' AS CCOMPAG,
                MDATA::JSON->>'CCOLOR' AS CCOLOR, MDATA::JSON->>'CDESCRI' AS CDESCRI, MDATA::JSON->>'CENLACE' AS CENLACE, A.NCANTID from b11dimp A
                  LEFT JOIN A02MCUR B ON A.CCODCUR = B.CCODCUR
                  LEFT JOIN V_S01TTAB C ON C.CCODIGO = A.CESTADO AND C.CCODTAB = '517'
                  LEFT JOIN V_S01TTAB D ON D.CCODIGO = A.CCLASE  AND D.CCODTAB = '518'
                  LEFT JOIN V_S01TTAB E ON E.CCODIGO = A.CFORMAT AND E.CCODTAB = '519'
                  WHERE A.CESTADO <> 'X' AND A.CIDIMPR = '{$this->paData['CIDIMPR']}' ORDER BY A.CCLASE DESC";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al listar las impresiones. Contacte con soporte del ERP.';
         return false;
      }
      $this->paDatos = [];
      while($laFila = $poSql->fetch($RS)) {
         $laTmp = ['NSERIAL' => $laFila[0], 'CIDIMPR' => $laFila[1], 'CCODCUR' => $laFila[2], 'CNOMCUR' => $laFila[3], 'CESTNOM' => $laFila[4],
            'CESTADO' => $laFila[5], 'CCLANOM' => $laFila[6], 'CCLASE' => $laFila[7], 'CFORNOM' => $laFila[8], 'CFORMAT' => $laFila[9], 'NCARAS' => $laFila[10],
            'CDOBCAR' => $laFila[11], 'CTIPEVA' => $laFila[12], 'CCOLOR' => $laFila[13], 'CDESCRI' => ($laFila[14] == '' ? '-' : $laFila[14]),
            'CENLACE' => $laFila[15], 'NCANTID' => $laFila[16]];
         if (in_array($laFila[7], ['T', 'P', 'D'])) {
            $laTmp['NTOTHOJ'] = ($laTmp['CDOBCAR'] == 'S')
               ?  ceil($laTmp['NCARAS']/2) * $laTmp['NCANTID']
               :  $laTmp['NCARAS'] * $laTmp['NCANTID'];
            $laTmp['NTOTIMP'] = $laTmp['NCARAS'] * $laTmp['NCANTID'];
         } else {
            $laTmp['NTOTIMP'] = $laTmp['NCANTID'];
         }
         $this->paDatos[] = $laTmp;
      }

      $lcSql = "SELECT A.CIDIMPR, A.CESTADO, TO_CHAR(A.tSolici, 'YYYY-MM-DD'), TO_CHAR(A.tRecojo, 'YYYY-MM-DD HH24:MI'), B.CDESCRI
                    FROM B11MIMP A 
                    RIGHT JOIN V_S01TTAB B ON B.CCODIGO = A.CESTADO AND B.CCODTAB = '520'
                    WHERE A.CIDIMPR = '{$this->paData['CIDIMPR']}'";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al listar las impresiones. Contacte con soporte del ERP.';
         return false;
      }
      $laFila = $poSql->fetch($RS);
      $this->paData = array_merge($this->paData, ['CIDIMPR' => $laFila[0], 'CESTADO' => $laFila[1], 'TSOLICI' => $laFila[2],
         'TRECOJO' => $laFila[3], 'CESTNOM' => $laFila[4]]);
      return true;
   }

   // -------------------------------------------------------
   // Cnsultar Detalle de Pedidos de impresiones
   // 2023-07-10  -  GCQ
   // -------------------------------------------------------
   public function omConsultarDetalleImpresionDocente() {
      $llOk = $this->mxValConsultarDetalleImpresionDocente();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = 'OCURRIO UN ERROR AL CONECTAR CON LA BASE DE DATOS';
         return false;
      }
      //VALIDACION TIPO DE USUARIO -- ADMINISTRATIVO / DOCENTE
      $llOk = $this->mxConsultarDetalleImpresionDocente($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }
   protected function mxValConsultarDetalleImpresionDocente() {
      if (!isset($this->paData['CCODUSU']) || !(preg_match('/^[A-Z0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE DOCENTE INVÁLIDO O NO DEFINIDO.';
         return false;
      } elseif (!isset($this->paData['NSERIAL']) || $this->paData['NSERIAL'] < 1 ) {
         $this->pcError = 'SERIAL DE DETALLE DE IMPRESIÓN INVÁLIDO O NO DEFINIDO.';
         return false;
      }
      return true;
   }
   protected function mxConsultarDetalleImpresionDocente($poSql) {
      $lcSql = "SELECT NSERIAL, CIDIMPR, CCODCUR, CESTADO, CCLASE, CFORMAT, MDATA::JSON->>'NCARAS' AS NCARAS, MDATA::JSON->>'CDOBCAR' AS CDOBCAR, 
                MDATA::JSON->>'CCOMPAG' AS CCOMPAG, MDATA::JSON->>'CCOLOR' AS CCOLOR, MDATA::JSON->>'CDESCRI' AS CDESCRI, NCANTID, 
                MDATA::JSON->>'CTIPEVA' AS CTIPEVA, MDATA::JSON->>'CANILLA' AS CANILLA, MDATA::JSON->>'CENLACE' AS CENLACE from b11dimp A
                  WHERE CESTADO <> 'X' AND NSERIAL = {$this->paData['NSERIAL']};";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al listar las impresiones. Contacte con soporte del ERP.';
         return false;
      }
      $laFila = $poSql->fetch($RS);
      $this->paData = ['NSERIAL' => $laFila[0], 'CIDIMPR' => $laFila[1], 'CCODCUR' => $laFila[2], 'CESTADO' => $laFila[3],
         'CCLASE' => $laFila[4], 'CFORMAT' => $laFila[5], 'NCARAS' => $laFila[6], 'CDOBCAR' => $laFila[7], 'CCOMPAG' => $laFila[8],
         'CCOLOR' => $laFila[9], 'CDESCRI' => $laFila[10], 'NCANTID' => $laFila[11], 'CTIPEVA' => $laFila[12], 'CANILLA' => $laFila[13], 'CENLACE' => $laFila[14]];
      return true;
   }



   // -------------------------------------------------------
   // Enviar Pedido de impresion para su revision (Docentes se aprueban en automático, Administrativos pasan a revisión de jefe de Of Impresiones)
   // 2023-13-10  -  GCQ
   // -------------------------------------------------------
   public function omEnviarPedido() {
      $llOk = $this->mxValEnviarPedido();
      if (!$llOk) return false;
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = 'OCURRIO UN ERROR AL CONECTAR CON LA BASE DE DATOS';
         return false;
      }
      //VALIDACION TIPO DE USUARIO -- ADMINISTRATIVO / DOCENTE
      $llOk = $this->mxEnviarPedido($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $loSql->omDisconnect();
      return $llOk;
   }
   protected function mxValEnviarPedido() {
      if (!isset($this->paData['CCODUSU']) || !(preg_match('/^[A-Z0-9]{4}$/', $this->paData['CCODUSU']))) {
         $this->pcError = 'CÓDIGO DE DOCENTE INVÁLIDO O NO DEFINIDO.';
         return false;
      } elseif (!isset($this->paData['CIDIMPR']) || !(preg_match('/^[A-Z0-9]{5}$/', $this->paData['CIDIMPR']))) {
         $this->pcError = 'ID DE IMPRESIÓN INVÁLIDO O NO DEFINIDO.';
         return false;
      } elseif (!isset($this->paData['CTIPSOL']) || !(preg_match('/^[A-Z]{1}$/', $this->paData['CTIPSOL']))) {
         $this->pcError = 'TIPO DE TRABAJADOR INVÁLIDO O NO DEFINIDO.';
         return false;
      } elseif (!isset($this->paData['TRECOJO']) || $this->paData['TRECOJO'] == '____-__-__ __:__') {
         $this->pcError = 'FECHA DE RECOJO INVÁLIDO O NO DEFINIDO.';
         return false;
      }
      $lcDate = date('Y-m-d H:i');
      if ($lcDate > $this->paData['TRECOJO']) {
         $this->pcError = 'La fecha para el recojo no puede ser menor a la fecha actual.';
         return false;
      }
      return true;
   }
   protected function mxEnviarPedido($poSql) {
      // VALIDAR ESTADO DE IMPRESION
      if ($this->paData['CTIPSOL'] == 'D') {
         $this->paData['CCENCOS'] = '000';
      }
      $lcSql = "SELECT * FROM B11MIMP WHERE CIDIMPR = '{$this->paData['CIDIMPR']}' AND CESTADO = 'P' AND CTIPSOL = '{$this->paData['CTIPSOL']}' AND CCENCOS = '{$this->paData['CCENCOS']}'";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al listar las impresiones. Contacte con soporte del ERP.';
         return false;
      }
      if ($poSql->pnNumRow == 0) {
         $this->pcError = 'Ocurrio un error al enviar la solicitud, .';
         return false;
      }

      $this->paData['CMNSAPR'] = 'Solicitud para impresión enviada y aprobada.';
      //ENVIO DE SOLCITUD , VALIDA TIPO DE SOLICITANTE
      if ($this->paData['CTIPSOL'] == 'D') {
         $lcSql = "UPDATE B11MIMP SET CESTADO = 'B', TMODIFI = NOW(), CUSUCOD = '{$this->paData['CCODUSU']}', TAPROBA = NOW(), 
                   TSOLICI = NOW(), TRECOJO = '{$this->paData['TRECOJO']}' WHERE CIDIMPR = '{$this->paData['CIDIMPR']}'";
      } else {
         $lcEstado = 'B';
         // VALIDA SI HAY IMPRESIONES A COLOR EN EL DETALLE DEL PEDIDO
         $lcSql = "SELECT COUNT(*) FROM B11DIMP WHERE CIDIMPR = '{$this->paData['CIDIMPR']}' AND MDATA <> '' AND CESTADO <> 'X' AND MDATA::JSON->>'CCOLOR' = 'S'";
         $RS = $poSql->omExec($lcSql);
         $laFila = $poSql->fetch($RS);
         if ($laFila[0] > 0) {
            $lcEstado = 'A';
            $this->paData['CMNSAPR'] = 'Solicitud para impresión enviada para su revisión.';
         } else {

            $lcSql = "SELECT NCANTID, MDATA::JSON->>'NCARAS', MDATA::JSON->>'CDOBCAR' , CCLASE 
                FROM B11DIMP WHERE CIDIMPR = '{$this->paData['CIDIMPR']}' AND MDATA <> '' AND CESTADO <> 'X'";
            $RS = $poSql->omExec($lcSql);
            $lnTotImp = 0;
            while($laFila = $poSql->fetch($RS)) {
               $laTmp = ['NCANTID' => $laFila[0], 'NCARAS' => $laFila[1], 'CDOBCAR' => $laFila[2]];
               if (in_array($laFila[3], ['T', 'P', 'D'])) {
                  $laTmp['NTOTHOJ'] = ($laTmp['CDOBCAR'] == 'S')
                     ?  ceil($laTmp['NCARAS']/2) * $laTmp['NCANTID']
                     :  $laTmp['NCARAS'] * $laTmp['NCANTID'];
                  $laTmp['NTOTIMP'] = $laTmp['NCARAS'] * $laTmp['NCANTID'];
               } else {
                  $laTmp['NTOTIMP'] = $laTmp['NCANTID'];
               }
               $lnTotImp = $lnTotImp + $laTmp['NTOTIMP'];
            }
            if ($lnTotImp > 200) {
               $lcEstado = 'A';
               $this->paData['CMNSAPR'] = 'Solicitud para impresión enviada para su revisión.';
            }
         }

         $lcSql = "UPDATE B11MIMP SET CESTADO = '{$lcEstado}', TMODIFI = NOW(), CUSUCOD = '{$this->paData['CCODUSU']}',". ($lcEstado == 'B' ? 'tAproba = NOW(),' : '')
            ."TSOLICI = NOW(), TRECOJO = '{$this->paData['TRECOJO']}'  WHERE CIDIMPR = '{$this->paData['CIDIMPR']}'";
      }
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'Ocurrio un error al enviar la solicitud de impresion. Contacte con soporte del ERP.';
         return false;
      }
      return true;
   }

   // --------------------------------------------------
   // Confirmar Solicitud
   // 2023-08-13        J.C.F. Creacion
   // --------------------------------------------------

   public function omInitConfirmarSolicitud() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuAprovar($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      $loSql = new CSql(); 
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitConfirmarSolicitud($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxValUsuAprovar($p_oSql) {
      $lcSql = "SELECT mDatos FROM S01TVAR WHERE cnomvar = 'AUSUAPROV'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "NO HAY REVISORES ASIGNADOS";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $lmDatos = $laFila[0];
      $lmDatos = json_decode($lmDatos, true);
      foreach ($lmDatos['CCODUSU'] as $lcCodUsu) {
         if ($lcCodUsu == $this->paData['CCODUSU']) {
            return true;
         }
      }
      $this->pcError = "EL USUARIO NO TIENE PERMISO PARA OPCION";
      return false;
   }

   protected function mxInitConfirmarSolicitud($p_oSql) {
      $lcSql = "SELECT A.cIdImpr, A.cTipSol, A.cCodUsu, TO_CHAR(A.tSolici, 'YYYY-MM-DD'), TO_CHAR(A.tRecojo, 'YYYY-MM-DD HH24:MI'), A.cEstado, 
         A.cCenCos, A.cUsuApr, TO_CHAR(A.tAproba, 'YYYY-MM_DD'), A.tImpres, B.cNombre FROM B11MIMP A
         INNER JOIN V_S01TUSU_1 B on A.cCodUsu = B.cCodUsu
         WHERE A.cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "No se encontro ninguna solicitud";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($laFila[5] == "A") {
            $laFila[5] = "SOLICITADO";
         }
         $this->paDatos[] = ['CIDIMPR'=> $laFila[0], 'CTIPSOL'=> $laFila[1], 'CCODUSU'=> $laFila[2], 'TSOLICI'=> $laFila[3],
            'TRECOJO'=> $laFila[4], 'CESTADO'=> $laFila[5], 'CCENCOS'=> $laFila[6], 'CUSUAPR'=> $laFila[7], 'TAPROBA'=> $laFila[8],
            'TIMPRES'=> $laFila[9], 'CNOMBRE'=> str_replace("/", " ", $laFila[10])];
      }
      return true;
   }

   // ------------------------------------------------------------
   // Ver Solicitud de Impresiones
   // 2023-07-14           J.C.F. Creacion
   // ------------------------------------------------------------
   public function omVerSolicitud() {
      $llOk = $this->mxValParams();
      if (!$llOk) { 
         return false; 
      }
      $llOk = $this->mxValParamVerSolicitud();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerSolicitud($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxValParamVerSolicitud() {
      if (!isset($this->paData['RBUTTON'])) {
         $this->pcError = 'NO SE SELECCIONO UNA SOLICITUD';
         return false;
      }
      return true;
   }

   protected function mxVerSolicitud($p_oSql) {
      $lcSql = "SELECT A.nSerial, A.cIdImpr, A.cCodCur, A.cEstado, A.cClase, A.cFormat, A.nCantid, A.mData, A.cUsuCod, B.cDescri, 
                  A.MDATA::JSON->>'NCARAS' AS NCARAS, A.MDATA::JSON->>'CDOBCAR' AS CDOBCAR, A.MDATA::JSON->>'CCOMPAG' AS CCOMPAG,
                  A.MDATA::JSON->>'CCOLOR' AS CCOLOR, A.MDATA::JSON->>'CDESCRI' AS CDESCRI, A.MDATA::JSON->>'CTIPEVA' AS CTIPEVA, 
                  A.MDATA::JSON->>'CANILLA' AS CANILLA, A.MDATA::JSON->>'CENLACE' AS CENLACE, D.CDESCRI AS CCLASE
                  FROM B11DIMP A
                  INNER JOIN A02MCUR B ON A.cCodCur = B.cCodCur
                  INNER JOIN B11MIMP C ON A.cIdImpr = C.cIdImpr 
                  LEFT JOIN V_S01TTAB D ON D.CCODIGO = A.CCLASE AND D.CCODTAB = '518'
                  WHERE C.cEstado = 'A' AND A.cIdImpr = '{$this->paData['RBUTTON']}' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      $lnTotImp = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $laTmp = ['NCANTID' => $laFila[6], 'NCARAS' => $laFila[10], 'CDOBCAR' => $laFila[2]];
         if (in_array($laFila[4], ['T', 'P', 'D'])) {
            $laTmp['NTOTHOJ'] = ($laTmp['CDOBCAR'] == 'S')
               ?  ceil($laTmp['NCARAS']/2) * $laTmp['NCANTID']
               :  $laTmp['NCARAS'] * $laTmp['NCANTID'];
            $laTmp['NTOTIMP'] = $laTmp['NCARAS'] * $laTmp['NCANTID'];
         } else {
            $laTmp['NTOTIMP'] = $laTmp['NCANTID'];
         }
         $lnTotImp = $lnTotImp + $laTmp['NTOTIMP'];
         $lmDatos = json_decode($laFila[7], true);
         if ($laFila[4] == 'T') {
            $lcDesCla = 'EXAMEN TEORIA';
         } elseif ($laFila[4] == 'P') {
            $lcDesCla = 'EXAMEN PRATICTAS';
         } elseif ($laFila[4] == 'B') {
            $lcDesCla = 'BURBUJAS 60 PREGUNTAS';
         } elseif ($laFila[4] == 'C') {
            $lcDesCla = 'BURBUJAS 120 PREGUNTAS';
         } else {
            $lcDesCla = 'OTROS';
         }
         if ($laFila[5] == 'A') {
            $lcDesFor = 'A3';
         } elseif ($laFila[5] == 'B') {
            $lcDesFor = 'A4';
         } else {
            $lcDesFor = 'NO CORRESPONDE';
         }
         $laTmp = ['NSERIAL'=> $laFila[0], 'CIDIMPR'=> $laFila[1], 'CCODCUR'=> $laFila[2], 'CESTADO'=> $laFila[3], 
            'CCLASE'=> $laFila[4], 'CDESCLA'=> $lcDesCla, 'CFORMAT'=> $laFila[5], 'NCANTID'=> $laFila[6], 'ADATA'=> $lmDatos, 
            'CUSUCOD'=> $laFila[8], 'CNOMCUR'=> $laFila[9], 'CDESFOR'=> $lcDesFor, 'NCARAS'=> $laFila[10], 'CDOBCAR'=> $laFila[11],
            'CCOMPAG'=> $laFila[12], 'CCOLOR'=> $laFila[13], 'CDESCRI'=> $laFila[14], 'CTIPEVA'=> $laFila[15], 'CANILLA'=> $laFila[16],
            'CENLACE'=> $laFila[17], 'CCLANOM'=> $laFila[18]];
         if (in_array($laFila[4], ['T', 'P', 'D'])) {
            $laTmp['NTOTHOJ'] = ($laTmp['CDOBCAR'] == 'S')
               ?  ceil($laTmp['NCARAS']/2) * $laTmp['NCANTID']
               :  $laTmp['NCARAS'] * $laTmp['NCANTID'];
            $laTmp['NTOTIMP'] = $laTmp['NCARAS'] * $laTmp['NCANTID'];
         } else {
            $laTmp['NTOTIMP'] = $laTmp['NCANTID'];
         }
         $this->paDatos[] = $laTmp;
      }
      return true;
   }

   // ------------------------------------------------------------
   // Ver Detalles de Impresiones
   // 2023-07-14           J.C.F. Creacion
   // ------------------------------------------------------------
   public function omVerDetalles() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerDetalles($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxVerDetalles($p_oSql) {
      $lcSql = "SELECT A.nserial, A.cidimpr, A.ccodcur, A.cestado, A.cclase, A.cformat, A.ncantid, A.mdata, A.ccodimp, B.cDescri FROM B11DIMP A
         INNER JOIN A02MCUR B ON A.ccodcur = B.ccodcur
         WHERE A.nserial = {$this->paData['NSERIAL']} ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "NO SE PUDO CARGAR LOS DATOS DE LA SOLICITUD, CONTACTE CON SOPORTE DEL ERP.";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($laFila[4] == 'T') {
            $lcClase = 'TEORIA';
         } elseif ($laFila[4] == 'P') {
            $lcClase = 'PRACTICA';
         } elseif ($laFila[4] == 'B') {
            $lcClase = 'BURBUJAS 60 PREGUNTAS';
         } elseif ($laFila[4] == 'C') {
            $lcClase = 'BURBUJAS 120 PREGUNTAS';
         } else {
            $lcClase = 'OTRO';
         }
         $lmDatos = json_decode($laFila[7], true); 
         $this->paDatos = ['NSERIAL'=> $laFila[0], 'CIDIMPR'=> $laFila[1], 'CCODCUR'=> $laFila[2], 'CESTADO'=> $laFila[3], 'CCLASE'=> $laFila[4],
            'CFORMAT'=> $laFila[5], 'NCANTID'=> $laFila[6], 'ADATA'=> $lmDatos, 'CCODIMP'=> $laFila[8], 'CDESCUR'=> $laFila[9], 'CDESCLA'=> $lcClase];
      }
      return true;
   }

   // ------------------------------------------------------------
   // Guardar Cambios en Aprobacion de Solicitud
   // 2023-07-19           J.C.F. Creacion
   // ------------------------------------------------------------
   public function omGuardarCambios() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGuardarCambios($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxGuardarCambios($p_oSql) {
      if ($this->paData['CCOLOR'] == 'on') {
         $this->paData['CCOLOR'] = 'S';
      } else {
         $this->paData['CCOLOR'] = 'N';
      }
      if ($this->paData['CANILLA'] == 'on') {
         $this->paData['CANILLA'] = 'S';
      } else {
         $this->paData['CANILLA'] = 'N';
      }
      if ($this->paData['CCOMPAG'] == null) {
         $this->paData['CCOMPAG'] = 'N';
      }
      if ($this->paData['CENLACE'] == null) {
         $this->paData['CENLACE'] = '';
      } 
      $laDatos = ['CCOMPAG'=> $this->paData['CCOMPAG'], 'NCARAS'=> $this->paData['NCARAS'], 'CDOBCAR'=> $this->paData['CDOBCAR'], 
         'CCOLOR'=> $this->paData['CCOLOR'], 'CTIPEVA'=> $this->paData['CTIPEVA'], 'CDESCRI'=> $this->paData['CDESCRI'], 
         'CANILLA'=> $this->paData['CANILLA'], 'CENLACE'=> $this->paData['CENLACE']];
      $laDatos = json_encode($laDatos);
      $lcSql = "UPDATE B11DIMP SET ccodcur = '{$this->paData['CCODCUR']}', cestado = '{$this->paData['CESTADO']}', cclase = '{$this->paData['CCLASE']}', 
         cformat = '{$this->paData['CFORMAT']}', ncantid = {$this->paData['NCANTID']}, mdata = '{$laDatos}', cusucod = 'U666', tmodifi = NOW() 
         WHERE nserial = {$this->paData['NSERIAL']}";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "HA OCURRIDO UN PROBLEMA A GUARDAR LOS DATOS. CONTACTAR CON EL SOPORTE DEL ERP.";
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------
   // Confirmar Solicitud
   // 2023-07-14        J.C.F. Creacion
   // ------------------------------------------------------------

   public function omConfirmarSolicitud() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxConfirmarSolicitud($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxConfirmarSolicitud($p_oSql) {
      $lcSql = "UPDATE B11MIMP SET cEstado = 'B', cUsuApr = '{$this->paData['CCODUSU']}', tAproba = NOW(), cUsuCod = 'U666', tModifi = NOW() WHERE cIdImpr = '{$this->paData['CIDIMPR']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NO SE PUDO APROBAR LA SOLICITUD';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------
   // Rechazar Solicitud
   // 2023-07-14        J.C.F. Creacion
   // ------------------------------------------------------------

   public function omRechazarSolicitud() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRechazarSolicitud($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxRechazarSolicitud($poSql) {
      $mDatos = ['COBSERV'=> $this->paData['CRECHAZ']];
      $mDatos = json_encode($mDatos, true);
      $lcSql = "UPDATE B11MIMP SET cEstado = 'X', cUsuApr = '{$this->paData['CCODUSU']}', tAproba = NOW(), cUsuCod = 'U666', tModifi = NOW(), mDatos = '$mDatos' WHERE cIdImpr = '{$this->paData['CIDIMPR']}'";
      $RS = $poSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NO SE PUDO APROBAR LA SOLICITUD';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------
   //  Init Comfirmar Impresion
   //  2023-07-24       J.C.F. Creacion
   // ------------------------------------------------------------

   public function omInitConfirmarImpresion() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuAprovar($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitConfirmarImpresion($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxInitConfirmarImpresion($p_oSql) {
      $lcSql = "SELECT A.cIdImpr, A.cTipSol, A.cCodUsu, TO_CHAR(A.tSolici, 'YYYY-MM-DD'), TO_CHAR(A.tRecojo, 'YYYY-MM-DD HH24:MI'), A.cEstado, A.cCenCos, A.cUsuApr, TO_CHAR(A.tAproba, 'YYYY-MM_DD'), A.tImpres, 
         B.cNombre, B.cemail FROM B11MIMP A
         INNER JOIN V_S01TUSU_1 B on A.cCodUsu = B.cCodUsu 
         WHERE A.cEstado = 'B'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "NO SE ENCONTRO NINGUNA SOLICITUD";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         if ($laFila[5] == "B") {
            $laFila[5] = "APROBADO";
         }
         $this->paDatos[] = ['CIDIMPR'=> $laFila[0], 'CTIPSOL'=> $laFila[1], 'CCODUSU'=> $laFila[2], 'TSOLICI'=> $laFila[3],
            'TRECOJO'=> $laFila[4], 'CESTADO'=> $laFila[5], 'CCENCOS'=> $laFila[6], 'CUSUAPR'=> $laFila[7], 'TAPROBA'=> $laFila[8],
            'TIMPRES'=> $laFila[9], 'CNOMBRE'=> str_replace("/", " ", $laFila[10]), 'CEMAIL'=> $laFila[11]];
      }
      return true;
   }

   // ------------------------------------------------------------
   //  Ver Aprobacion de Impresiones
   //  2023-07-24          J.C.F. Creacion
   // ------------------------------------------------------------

   public function omVerAprobacion() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxValParamVerAprobacion();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxListaImpresoras($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxVerAprobacion($loSql);
      if (!$llOk) {
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxValParamVerAprobacion() {
      if (!isset($this->paData['RBUTTON'])) {
         $this->pcError = 'NO SE SELECCIONO UNA SOLICITUD';
         return false;
      }
      return true;
   }

   protected function mxListaImpresoras($p_oSql) {
      $lcSql = "SELECT mdatos FROM S01TVAR WHERE cnomvar = 'AIMPRCOD'";
      $RS = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($RS);
      if ($laFila[0] == null) {
         $this->pcError = 'NO SE PUDO ENCONTRAR LA LISTA DE IMPRESORAS';
         return false;
      }
      $lmDatos = json_decode($laFila[0], true);
      foreach ($lmDatos as $laData) {
         $lcSql = "SELECT cdescri, cactfij FROM E04MAFJ WHERE ctipafj = '{$laData['CTIPAFJ']}' AND ncorrel = {$laData['NCORREL']}";
         $RS = $p_oSql->omExec($lcSql);
         $laFila = $p_oSql->fetch($RS);
         if ($laFila[0] == null) {
            $this->pcError = 'NO SE PUDO HALLAR LA DESCRIPCION DE LAS IMPRESORAS';
            return false;
         }
         $lcCodigo = substr($laData['CTIPAFJ'], 0, 2) . '-' . substr($laData['CTIPAFJ'], 2) . '-' . right('00000' . strval($laData['NCORREL']), 6);
         $laDatos[] = ['CACTFIJ'=> $laFila[1], 'CTIPAFJ'=> $laData['CTIPAFJ'], 'NCORREL'=> $laData['NCORREL'], 'CDESCRI'=> $laFila[0], 'CCODIGO'=> $lcCodigo]; 
      }
      $this->paDatos['AIMPRES'] = $laDatos;
      return true;
   }

   protected function mxVerAprobacion($p_oSql) {
      $lcSql = "SELECT A.ccodusu, B.cemail FROM B11MIMP A 
                  INNER JOIN V_S01TUSU_1 B ON A.ccodusu = B.ccodusu
                  WHERE A.cidImpr = '00003'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "No se pudo encontrar el correo del usuario. Contacte con soporte del ERP.";
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->paDatos['AEMAIL'] = $laFila[1];
      $lcSql = "SELECT A.nSerial, A.cIdImpr, A.cCodCur, A.cEstado, A.cClase, A.cFormat, A.nCantid, A.mData, A.cUsuCod, B.cDescri, 
         A.MDATA::JSON->>'NCARAS' AS NCARAS, A.MDATA::JSON->>'CDOBCAR' AS CDOBCAR, A.MDATA::JSON->>'CCOMPAG' AS CCOMPAG,
         A.MDATA::JSON->>'CCOLOR' AS CCOLOR, A.MDATA::JSON->>'CDESCRI' AS CDESCRI, A.MDATA::JSON->>'CTIPEVA' AS CTIPEVA, 
         A.MDATA::JSON->>'CANILLA' AS CANILLA, A.MDATA::JSON->>'CENLACE' AS CENLACE, D.CDESCRI AS CCLASE
         FROM B11DIMP A
         INNER JOIN A02MCUR B ON A.cCodCur = B.cCodCur
         INNER JOIN B11MIMP C ON A.cIdImpr = C.cIdImpr 
         LEFT JOIN V_S01TTAB D ON D.CCODIGO = A.CCLASE AND D.CCODTAB = '518'
         WHERE C.cEstado = 'B' AND A.cIdImpr = '{$this->paData['RBUTTON']}' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $RS = $p_oSql->omExec($lcSql);
      $lnTotImp = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $laTmp = ['NCANTID' => $laFila[6], 'NCARAS' => $laFila[10], 'CDOBCAR' => $laFila[2]];
         if (in_array($laFila[4], ['T', 'P', 'D'])) {
            $laTmp['NTOTHOJ'] = ($laTmp['CDOBCAR'] == 'S')
               ?  ceil($laTmp['NCARAS']/2) * $laTmp['NCANTID']
               :  $laTmp['NCARAS'] * $laTmp['NCANTID'];
            $laTmp['NTOTIMP'] = $laTmp['NCARAS'] * $laTmp['NCANTID'];
         } else {
            $laTmp['NTOTIMP'] = $laTmp['NCANTID'];
         }
         $lnTotImp = $lnTotImp + $laTmp['NTOTIMP'];
         $lmDatos = json_decode($laFila[7], true);
         if ($laFila[4] == 'T') {
            $lcDesCla = 'EXAMEN TEORIA';
         } elseif ($laFila[4] == 'P') {
            $lcDesCla = 'EXAMEN PRATICTAS';
         } elseif ($laFila[4] == 'B') {
            $lcDesCla = 'BURBUJAS 60 PREGUNTAS';
         } elseif ($laFila[4] == 'C') {
            $lcDesCla = 'BURBUJAS 120 PREGUNTAS';
         } else {
            $lcDesCla = 'OTROS';
         }
         if ($laFila[5] == 'A') {
            $lcDesFor = 'A3';
         } elseif ($laFila[5] == 'B') {
            $lcDesFor = 'A4';
         } else {
            $lcDesFor = 'NO CORRESPONDE';
         }
         $laTmp = ['NSERIAL'=> $laFila[0], 'CIDIMPR'=> $laFila[1], 'CCODCUR'=> $laFila[2], 'CESTADO'=> $laFila[3], 
            'CCLASE'=> $laFila[4], 'CDESCLA'=> $lcDesCla, 'CFORMAT'=> $laFila[5], 'NCANTID'=> $laFila[6], 'ADATA'=> $lmDatos, 
            'CUSUCOD'=> $laFila[8], 'CNOMCUR'=> $laFila[9], 'CDESFOR'=> $lcDesFor, 'NCARAS'=> $laFila[10], 'CDOBCAR'=> $laFila[11],
            'CCOMPAG'=> $laFila[12], 'CCOLOR'=> $laFila[13], 'CDESCRI'=> $laFila[14], 'CTIPEVA'=> $laFila[15], 'CANILLA'=> $laFila[16],
            'CENLACE'=> $laFila[17], 'CCLANOM'=> $laFila[18]];
         if (in_array($laFila[4], ['T', 'P', 'D'])) {
            $laTmp['NTOTHOJ'] = ($laTmp['CDOBCAR'] == 'S')
               ?  ceil($laTmp['NCARAS']/2) * $laTmp['NCANTID']
               :  $laTmp['NCARAS'] * $laTmp['NCANTID'];
            $laTmp['NTOTIMP'] = $laTmp['NCARAS'] * $laTmp['NCANTID'];
         } else {
            $laTmp['NTOTIMP'] = $laTmp['NCANTID'];
         }
         $laDatos[] = $laTmp;
      }
      $this->paDatos['AAPROBA'] = $laDatos;
      return true;
   }

   // ------------------------------------------------------------
   //  Confirmar Aprobacion
   //  2023-07-25          J.C.F. Creacion
   // ------------------------------------------------------------

   public function omConfirmarAprobacion() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxConfirmarAprobacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxConfirmarAprobacion($p_oSql) {
      $lcSql = "UPDATE B11MIMP SET cEstado = 'C', cUsuApr = '{$this->paData['CCODUSU']}', tAproba = NOW(), cUsuCod = 'U666', tModifi = NOW() WHERE cIdImpr = '{$this->paData['CIDIMPR']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NO SE PUDO APROBAR LA SOLICITUD';
         return false;
      }
      $i = 0;
      foreach ($this->paData['ASERIAL'] as $laData) {
         $lcSql = "UPDATE B11DIMP SET ccodimp = '{$this->paData['ASELECT'][$i]}' WHERE nserial = $laData";
         $RS = $p_oSql->omExec($lcSql);
         if (!$RS) {
            $this->pcError = "NO SE PUDIERON GUARDAR LAS IMPRESORAS";
            return false;
         }
         $i++;
      }
      $loEmail = new CEmailImpresiones();
      $laEmails = '74606599@ucsm.edu.pe';
      //$laCopia = '';
      $loEmail->paData = ['AEMAIL'=> $laEmails, 'CBODY'=> 'Se ha Aprobado su peticion de Impresion'];
      $loEmail->omSend();
      return true;
   }

   // ------------------------------------------------------------
   //  Rechazar Solicitud
   //  2023-07-25          J.C.F. Creacion
   // ------------------------------------------------------------

   public function omRechazarAprobacion() {
      $llOk = $this->mxValParams();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRechazarAprobacion($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxRechazarAprobacion($p_oSql) {
      $mDatos = ['COBSERV'=> $this->paData['CRECHAZ']];
      $mDatos = json_encode($mDatos, true);
      $lcSql = "UPDATE B11MIMP SET cEstado = 'X', cUsuApr = '{$this->paData['CCODUSU']}', tAproba = NOW(), cUsuCod = 'U666', tModifi = NOW(), mDatos = '$mDatos' WHERE cIdImpr = '{$this->paData['CIDIMPR']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NO SE PUDO APROBAR lA SOLICITUD';
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------
   // Anular el pedido de Impresion
   // 2023-08-14        J.C.F. Creacion
   // ------------------------------------------------------------

   public function omSalirAnularDetalles() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxSalirAnularDetalles($loSql);
      if (!$llOk) {
         $loSql->rollback();
         return false;
      }
      $loSql->omDisconnect();
      return true;
   }

   protected function mxSalirAnularDetalles($p_oSql) {
      $lcSql = "UPDATE B11MIMP SET cestado = 'X' WHERE cidimpr = '{$this->paData['CIDIMPR']}'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = 'NO SE PUDO CANCELAR LA SOLICITUD';
         return false;
      }   
      return true;
   }
}
