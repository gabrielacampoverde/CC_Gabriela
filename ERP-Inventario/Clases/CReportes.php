<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "class/class.ezpdf.php";
require_once "Libs/fpdf/fpdf.php";

//-------------------------------------
// Reportes
//-------------------------------------
class CReportes extends CBase {

   public $pcFile, $paData, $paDatos, $paProyec, $laRequer, $laDatos1;
   protected $laDatos, $ldFecSis, $lcPeriod, $ldFecCal, $ldFecCnt, $laIdRequ, $laData, $laFirmas;
   
   public function __construct() {
      parent::__construct();
      $this->paDatos = $this->laDatos1 = $this->paProyec = $this->laDatos = $this->laRequer = $this->laData = $this->laFirmas = null;
      $this->pcFile = 'FILES/R' . rand() . '.pdf';
   }

   // Validacion de usuarios de Logistica
   // 2020-02-27 JLF Creación
   protected function mxValUsuarioLogistica($p_oSql) {
      // VALIDA USUARIO LOGISTICA
      $lcCodUsu = $this->paData['CUSUCOD'];
      //$lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCenCos = '02W' AND cCodUsu = '$lcCodUsu' AND cEstado = 'A'";
      $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCenCos = '02W' AND cCodUsu = '$lcCodUsu' AND cModulo = '000' AND cEstado = 'A'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
      return true;
   }

   // Validacion de usuarios de Almacen General
   // 2019-02-27 JLF Creación
   protected function mxValUsuarioAlmacen($p_oSql) {
      // VALIDA USUARIO LOGISTICA
      $lcCodUsu = $this->paData['CUSUCOD'];
      $lcSql = "SELECT cCodUsu FROM S01TUSU WHERE cCodUsu = '$lcCodUsu' AND cEstado = 'A' AND cNivel IN ('AL','CA')";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } else if ($p_oSql->pnNumRow == 0) {
         $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
         return false;
      }
      return true;
   }

   public function omInitRep() {
      if (empty($this->paData['CCODUSU'])) {
         $this->pcError = 'CODIGO DE USUARIO VACIO';
         return false;
      } elseif (empty($this->paData['CUNIACA'])) {
         $this->pcError = 'UNIDAD ACADEMICA VACIA';
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxInitRep($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxInitRep($p_oSql) {
      $lcUniAca = $this->paData['CUNIACA'];
      $i = 0;
      $lcSql = "SELECT DISTINCT A.cProyec FROM A02MCAR A
                INNER JOIN A02MCUR B ON B.cCodCur = A.cCodCur
                WHERE B.cUniAca = '$lcUniAca' ORDER BY A.cProyec DESC";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paProyec[] = trim($laFila[0]);
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY PROYECTOS DEFINIDOS PARA UNIDAD ACADEMICA';
         return false;
      }
      return true;
   }

   protected function mxFechaSistema($p_oSql) {
      $this->ldFecSis = date("Y-m-d");
      return true;
      /*
        $loDate = new CDate();
        $lcSql = "SELECT TRIM(cConVar) FROM S01TVAR WHERE cNomVar = 'GDFECSIS'";
        $R1 = $p_oSql->omExec($lcSql);
        $laFila = $p_oSql->fetch($R1);
        $this->ldFecSis = $laFila[0];
        if (!$loDate->valDate($this->ldFecSis)) {
        $this->pcError = 'FECHA DE SISTEMA INVALIDA';
        return false;
        }
        return true; */
   }

   public function omRep1130() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValParamRep1130($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRep1130($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep1130();
      return $llOk;
   }

   protected function mxValParamRep1130($p_oSql) {
      if ($this->paData['CCENCOS'] === '*') {
         $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
         return false;
      }
      $lcCenCos = $this->paData['CCENCOS'];
      $lcCodUsu = $this->paData['CCODUSU'];
      //$lcSql = "SELECT cCenCos FROM S01PCCO WHERE cCodUsu = '$lcCodUsu' AND cCenCos = '$lcCenCos'";
      $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCenCos = '$lcCenCos' AND cCodUsu = '$lcCodUsu' AND cModulo = '000'";
      $RS = $p_oSql->omExec($lcSql);
      if (!$RS) {
         $this->pcError = "USUARIO NO EXISTE O NO TIENE ESE CENTRO DE COSTO ASOCIADO";
         return false;
      }
      return true;
   }

   protected function mxRep1130($p_oSql) {
      $this->mxFechaSistema($p_oSql);
      $lcIdRequ = $this->paData['CIDREQU'];
      $lcSql = "SELECT cDestin FROM E01MREQ WHERE cIdRequ = '$lcIdRequ'";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'REQUERIMIENTO NO EXISTE';
         return false;
      }
      $laFila = $p_oSql->fetch($RS);
      $this->laData['CDESTIN'] = $laFila[0];
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT * FROM F_E01MREQ_1('$lcJson');";

      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->laDatos[] = [fxStringFixed($laFila[0],120)];
      }
      if ($i == 0) {
         $this->pcError = 'USUARIO NO ENCONTRADO';
         return false;
      }
      return true;
   }

   protected function mxPrintRep1130() {
      $laDatos[] = ['-------------------------------------------------------------------------------------'];
      // Reporte
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Courier.afm');
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = $lnRow = 0;
      $ldDate = date('Y-m-d', time());
      $llTitulo = true;
      foreach ($this->laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $llTitulo = false;
            $lnPag++;
            $lnRow = 0;
            if ($this->laData['CDESTIN'] == 'F') {
               $loPdf->ezText('<b>UCSM-ERP                                            ORDEN DE RECOJO DE MATERIAL                               PAG.:' . fxString($lnPag, 5) . '</b>', 7, array('justification' => 'center'));
            } else {
               $loPdf->ezText('<b>UCSM-ERP                                         SITUACION ACTUAL DEL REQUERIMIENTO                           PAG.:' . fxString($lnPag, 5) . '</b>', 7, array('justification' => 'center'));
            }
            $loPdf->ezText('<b>REP1130                                                                                                       ' . $ldDate . '</b>', 7, array('justification' => 'center'));
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------</b>', 7, array('justification' => 'center'));
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . substr(utf8_encode($laFila[0]), 0, strlen($laFila[0]) + 6) . '</b>', 7);
         } else if (substr($laFila[0], 0, 1) == '#') {
            $loPdf->ezText('<b> ' . substr(utf8_encode($laFila[0]), 1, strlen($laFila[0]) + 6) . '</b>', 7);
         } else {
            $loPdf->ezText(substr(utf8_encode($laFila[0]), 0, strlen($laFila[0]) + 6), 7);
         }
         $lnRow++;
         if ($lnRow == 96) {
            $llTitulo = true;
            $loPdf->ezNewPage();
         }
      }
      ob_end_clean();
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   public function omRep1310() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep1310($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep1310();
      return $llOk;
   }

   protected function mxRep1310($p_oSql) {
      $this->mxFechaSistema($p_oSql);
      $lcJson = json_encode($this->paData);
      $lcSql = "SELECT * FROM F_E01MREQ_1('$lcJson');";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->laDatos[] = [$laFila[0]];
      }
      if ($i == 0) {
         $this->pcError = 'USUARIO NO ENCONTRADO';
         return false;
      }
      return true;
   }

   protected function mxPrintRep1310() {
      $laDatos[] = ['-------------------------------------------------------------------------------------'];
      // Reporte
      $loPdf = new Cezpdf('A5', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm');
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = $lnRow = 0;
      $ldDate = date('Y-m-d', time());
      $llTitulo = true;
      $loPdf->ezText('<b>UCSM-ERP                 SITUACION ACTUAL DEL REQUERIMIENTO                PAG.:    1</b>', 10, array('justification' => 'center'));
      $loPdf->ezText('<b>REP1310                                                                    ' . $ldDate . '</b>', 10, array('justification' => 'center'));
      $loPdf->ezText('<b>-------------------------------------------------------------------------------------</b>', 10, array('justification' => 'center'));
      $loPdf->ezText('', 7);
      foreach ($this->laDatos as $laFila) {
         // Titulo
         if ($llTitulo or $laFila[1] == 1) {
            $lnPag++;
            if ($lnPag > 1) {
               $lnRow = 0;
               $loPdf->ezNewPage();
               $loPdf->ezText('<b>UCSM-ERP                 SITUACION ACTUAL DEL REQUERIMIENTO                PAG.:   ' . $lnPag . '</b>', 10, array('justification' => 'center'));
               $loPdf->ezText('<b>REP1310                                                                    ' . $ldDate . '</b>', 10, array('justification' => 'center'));
               $loPdf->ezText('<b>-------------------------------------------------------------------------------------</b>', 10, array('justification' => 'center'));
            }
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', 7);
         } else {
            $loPdf->ezText($laFila[0], 7);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 42) ? true : false;
      }
      ob_end_clean();
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   public function omRep2130() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2130($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep1130();
      return $llOk;
   }

   protected function mxRep2130($p_oSql) {
      $this->mxFechaSistema($p_oSql);
      $lcIdCoti = $this->paData['CIDCOTI'];
      $lcSql = "SELECT cIdRequ FROM E01PREQ WHERE cIdCoti = '$lcIdCoti'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->laIdRequ[] = ['CIDREQU' => $laFila[0]];
      }
      foreach ($this->laIdRequ as $laFila) {
         $lcJson = json_encode($laFila);
         $lcSql = "SELECT * FROM F_E01MREQ_1('$lcJson');";
         $R1 = $p_oSql->omExec($lcSql);
         $i = 0;
         while ($laFila = $p_oSql->fetch($R1)) {
            $i++;
            $this->laDatos[] = [substr($laFila[0], 0, 120)];
         }
         if ($i == 0) {
            $this->pcError = 'SIN INFORMACION DE REQUERMIENTO';
            return false;
         }
      }
      return true;
   }

   /*
    *
     protected function mxPrintRep2130_OLD() {
     $laDatos[] = ['------------------------------------------------------------------------------------'];
     // Reporte
     $loPdf = &new Cezpdf('A5', 'landscape');
     $loPdf->selectFont('fonts/Courier.afm');
     $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
     $lnPag = $lnRow = 0;
     $llTitulo = true;
     $ldDate = date('Y/m/d', time());
     $loPdf->ezText('<b>UCSM-ERP                   REQUERIMIENTOS DE LA COTIZACION               PAG:    1</b>', 10, array('justification' => 'center'));
     $loPdf->ezText('<b>REP2130                                                                  ' . $ldDate . '</b>', 10, array('justification' => 'center'));
     $loPdf->ezText('<b>------------------------------------------------------------------------------------</b>', 10, array('justification' => 'center'));
     foreach ($this->laDatos as $laFila) {
     // Titulo
     if ($llTitulo or $laFila[1] == 1) {
     $lnPag++;
     if ($lnPag > 1) {
     $lnRow = 0;
     $loPdf->ezNewPage();
     $loPdf->ezText('<b>UCSM-ERP                   REQUERIMIENTOS DE LA COTIZACION               PAG:   ' . $lnPag . '</b>', 10, array('justification' => 'center'));
     $loPdf->ezText('<b>REP2130                                                                  ' . $ldDate . '</b>', 10, array('justification' => 'center'));
     $loPdf->ezText('<b>------------------------------------------------------------------------------------</b>', 10, array('justification' => 'center'));
     }
     }
     if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
     $loPdf->ezText('<b>' . $laFila[0] . '</b>', 7);
     } else {
     $loPdf->ezText($laFila[0], 7);
     }
     $lnRow++;
     $llTitulo = ($lnRow == 44) ? true : false;
     }
     ob_end_clean();
     $pdfcode = $loPdf->ezOutput(1);
     $fp = fopen($this->pcFile, 'wb');
     fwrite($fp, $pdfcode);
     fclose($fp);
     return true;
     }
    *
    */

   // REPORTE DE REQUERIMIENTOS POR COTIZADOR
   public function omRep2910REQ() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2910REQ($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxPrintRep2910REQ($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxRep2910REQ($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcEstado = $this->paData['CCODIGO'];
      $i = 0;
      $lcSql = "SELECT cIdRequ, cCenCos, cDesCco, cNomCot, cCodArt, cDesArt, nCantid, nCanAte, nPrecio, cDesMon FROM V_E01DREQ_2
                WHERE cUsuCot = '$lcUsuCot' AND cEstado = '$lcEstado' ORDER BY cIdRequ, cCodArt";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2],
             'CNOMCOT' => $laFila[3], 'CCODART' => $laFila[4], 'CDESART' => $laFila[5],
             'NCANTID' => $laFila[6], 'NCANATE' => $laFila[7], 'NPRECIO' => $laFila[8],
             'CDESMON' => $laFila[9]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2910REQ($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcNombre = $this->paData['CNOMBRE'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CDESCCO']) . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
         } elseif ($lcIdRequ != $laFila['CIDREQU']) {
            $laDatos[] = ['-----------------------------------------------------------------------------------------'];
            $laDatos[] = ['* SUBTOTAL REQUERIMIENTO                                                   ' . fxNumber($laSuma, 14, 2)];
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CDESCCO']) . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $laDatos[] = [$lnContad . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 40) . ' ' . fxNumber($laFila['NCANTID'], 11, 4) . ' ' . fxNumber($laFila['NPRECIO'], 12, 4) . ' ' . fxNumber($laFila['NCANTID'] * $laFila['NPRECIO'], 12, 2)];
         $laSuma += $laFila['NCANTID'] * $laFila['NPRECIO'];
         $lnContad += 1;
      }
      $laDatos[] = ['-----------------------------------------------------------------------------------------'];
      $laDatos[] = ['* SUBTOTAL REQUERIMIENTO                                                   ' . fxNumber($laSuma, 14, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                    REQUERIMIENTOS POR COTIZADOR                       PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2910                ' . $lcUsuCot . ' - ' . $lcNombre . '                   ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>  CODIGO   DESCRIPCION                                 CANTIDAD  COSTO.UNIT.       PRECIO</b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE COTIZACIONES POR COTIZADOR
   public function omRep2910COT() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2910COT($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxPrintRep2910COT($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxRep2910COT($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      $i = 0;
      $lcSql = "SELECT cIdRequ, cCenCos, cDesCCo, cCodArt, cDesArt, nCantid, nCanAte, nPrecio, cDesMon FROM V_E01DREQ_3 WHERE cUsuCot = '$lcUsuCot' ORDER BY cIdRequ, cCodArt";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDREQU' => $laFila[0], 'CCENCOS' => $laFila[1], 'CDESCCO' => $laFila[2],
             'CCODART' => $laFila[3], 'CDESART' => $laFila[4], 'NCANTID' => $laFila[5],
             'NCANATE' => $laFila[6], 'NPRECIO' => $laFila[7], 'CDESMON' => $laFila[8]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2910COT($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcNombre = $this->paData['CNOMBRE'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CDESCCO']) . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
         } elseif ($lcIdRequ != $laFila['CIDREQU']) {
            $laDatos[] = ['-----------------------------------------------------------------------------------------'];
            $laDatos[] = ['* SUBTOTAL REQUERIMIENTO                                                   ' . fxNumber($laSuma, 14, 2)];
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CDESCCO']) . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $laDatos[] = [$lnContad . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 40) . ' ' . fxNumber($laFila['NCANTID'], 11, 4) . ' ' . fxNumber($laFila['NPRECIO'], 12, 4) . ' ' . fxNumber($laFila['NCANTID'] * $laFila['NPRECIO'], 12, 2)];
         $laSuma += $laFila['NCANTID'] * $laFila['NPRECIO'];
         $lnContad += 1;
      }
      $laDatos[] = ['-----------------------------------------------------------------------------------------'];
      $laDatos[] = ['* SUBTOTAL REQUERIMIENTO                                                   ' . fxNumber($laSuma, 14, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                       COTIZACIONES POR COTIZADOR                      PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2910                ' . $lcUsuCot . ' - ' . $lcNombre . '                   ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>  CODIGO   DESCRIPCION                                 CANTIDAD  COSTO.UNIT.       PRECIO</b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE
   public function omRep2910OCS() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2910OCS($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxPrintRep2910OCS($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxRep2910OCS($p_oSql) {
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcIdOrde = $this->paData['CIDORDE'];
      $i = 0;
      $lcSql = "SELECT cIdRequ, cCodArt, nCantid, nCosto, cIdCoti, cIdOrde, cNroRuc, dGenera, cDesTip, cIncIgv, nMonto, cDesMon, cDesArt, cDesCCo FROM V_E01MORD_1 WHERE cUsuCot = '$lcUsuCot' AND cIdOrde = '$lcIdOrde' ORDER BY dGenera, cCodArt";

      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDREQU' => $laFila[0], 'CCODART' => $laFila[1], 'NCANTID' => $laFila[2],
             'NCOSTO' => $laFila[3], 'CIDCOTI' => $laFila[4], 'CIDORDE' => $laFila[5],
             'CNRORUC' => $laFila[6], 'DGENERA' => $laFila[7], 'CDESTIP' => $laFila[8],
             'CINCIGV' => $laFila[9], 'NMONTO' => $laFila[10], 'CDESMON' => $laFila[11],
             'CDESART' => $laFila[12], 'CDESCCO' => $laFila[13]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2910OCS($p_oSql) {
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcUsuCot = $this->paData['CUSUCOT'];
      $lcNombre = $this->paData['CNOMBRE'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* NUM.ORD: ' . $laFila['CIDORDE'] . '   RUC: ' . $laFila['CNRORUC']];
            $laDatos[] = ['* FEC. DE GENERACION: ' . $laFila['DGENERA']];
            $laDatos[] = ['* TIPO: ' . $laFila['CDESTIP'] . '   INC. IGV: ' . $laFila['CINCIGV']];
            $laDatos[] = ['* MONEDA: ' . $laFila['CDESMON']];
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CDESCCO']) . ' - ' . trim($laFila['CDESMON'])];
            $laDatos[] = ['* COTIZACION: ' . $laFila['CIDCOTI']];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
         }
         $laDatos[] = [$lnContad . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 40) . ' ' . fxNumber($laFila['NCANTID'], 11, 4) . ' ' . fxNumber($laFila['NPRECIO'], 12, 4) . ' ' . fxNumber($laFila['NCANTID'] * $laFila['NPRECIO'], 12, 2)];
         $laSuma += $laFila['NCANTID'] * $laFila['NPRECIO'];
         $lnContad += 1;
      }
      $laDatos[] = ['* MONTO TOTAL: ' . $this->laDatos[0]['NMONTO']];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                         ORDEN DE COMPRA/SERVICIO                      PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2910                ' . $lcUsuCot . ' - ' . $lcNombre . '                   ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>  CODIGO   DESCRIPCION                                 CANTIDAD  COSTO.UNIT.       PRECIO</b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE ENTREGAS DIRECTAS
   public function omRep2920() {
      $llOk = $this->mxValParamRep2920();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2920($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep2920($loSql);
      return $llOk;
   }

   protected function mxValParamRep2920() {
      $loDate = new CDate();
      if (!$loDate->valDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL NO PUEDE SER MENOR QUE FECHA INICIAL';
         return false;
      }
      return true;
   }

   protected function mxRep2920($p_oSql) {
      $lcCenCos = $this->paData['CCENCOS'];
      $lcFecIni = $this->paData['DINICIO'];
      $lcFecFin = $this->paData['DFINALI'];
      $i = 0;
      $lcSql = "SELECT cCenCos, cDesCCo, cIdRequ, cCodUsu, cNombre, TO_CHAR(tEntreg, 'YYYY-MM-DD'), cDesMon, cCodArt, cDesArt, nAteCan,
                nPrecio FROM V_E01DREQ_4 WHERE cEstReq = 'D' AND cCenCos = '$lcCenCos' AND (tEntreg > '$lcFecIni' AND tEntreg < '$lcFecFin')
                ORDER BY tEntreg";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCENCOS' => $laFila[0], 'CDESCCO' => $laFila[1], 'CIDREQU' => $laFila[2],
             'CCODUSU' => $laFila[3], 'CNOMBRE' => $laFila[4], 'TENTREG' => $laFila[5],
             'CDESMON' => $laFila[6], 'CCODART' => $laFila[7], 'CDESART' => $laFila[8],
             'NATECAN' => $laFila[9], 'NPRECIO' => $laFila[10]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2920($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcCenCos = $this->laDatos[0]['CCENCOS'];
      $lcDesCCo = $this->laDatos[0]['CDESCCO'];
      $lcFecIni = $this->paData['DINICIO'];
      $lcFecFin = $this->paData['DFINALI'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CCODUSU']) . ' - ' . trim($laFila['CNOMBRE'])];
            $laDatos[] = ['* FEC. DE ENTREGA: ' . $laFila['TENTREG'] . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
         } elseif ($lcIdRequ != $laFila['CIDREQU']) {
            $laDatos[] = ['-----------------------------------------------------------------------------------------'];
            $laDatos[] = ['* TOTAL                                                                  ' . fxNumber($laSuma, 14, 2)];
            $laDatos[] = ['* ID.REQ: ' . $laFila['CIDREQU'] . ' - ' . trim($laFila['CCODUSU']) . ' - ' . trim($laFila['CNOMBRE'])];
            $laDatos[] = ['* FEC. DE ENTREGA: ' . $laFila['TENTREG'] . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDREQU'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $laDatos[] = [$lnContad . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 58) . ' ' . fxNumber($laFila['NATECAN'], 11, 4) . ' ' . fxNumber($laFila['NPRECIO'], 12, 2)];
         $laSuma += $laFila['NATECAN'] * $laFila['NPRECIO'];
         $lnContad += 1;
      }
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                         ' . fxNumber($laSuma, 14, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                      ENTREGAS DIRECTAS POR CENTRO DE COSTO                 PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2920                          ' . $lcCenCos . ' - ' . fxString($lcDesCCo, 40) . '     ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>                              DESDE: ' . $lcFecIni . ' HASTA: ' . $lcFecFin . '                             </b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>  CODIGO   DESCRIPCION                                                CANT. ENTR.   COSTO REF.</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE ORDENES DE COMPRA POR TIPO - LOGISTICA - CREACION - ALBERTO - 2018-02-21
   public function omRep2930xTipo() {
      $llOk = $this->mxValParamRep2930xTipo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2930xTipo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep2930xTipo($loSql);
      return $llOk;
   }

   protected function mxValParamRep2930xTipo() {
      $loDate = new CDate();
      if (!$loDate->valDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL NO PUEDE SER MENOR QUE FECHA INICIAL';
         return false;
      }
      return true;
   }

   protected function mxRep2930xTipo($p_oSql) {
      $lcTipo = $this->paData['CTIPO'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      if ($lcTipo == '*') {
         $lcSql = "SELECT cIdOrde FROM E01MORD WHERE dGenera BETWEEN '$ldInicio' AND '$ldFinali' ORDER BY dCodAnt";
      } else {
         $lcSql = "SELECT cIdOrde FROM E01MORD WHERE (dGenera BETWEEN '$ldInicio' AND '$ldFinali') AND cTipo = '$lcTipo' ORDER BY cCodAnt";
      }
      $i = 0;
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $lcIdOrde = $laFila[0];
         $lcSql = "SELECT cIdOrde, cIdCoti, cNroRuc, cRazSoc, dGenera, cDesEst, cDesTip, cDesMon, cCenCos,
                   cDesCco, cCodArt, cDesArt, cClasif, nCantid, nCanIng, nCosto, cCodAnt FROM F_E01MORD_1('$lcIdOrde')";
         $R2 = $p_oSql->omExec($lcSql);
         while ($laTmp = $p_oSql->fetch($R2)) {
            $this->laDatos[] = ['CIDORDE' => $laTmp[0], 'CIDCOTI' => $laTmp[1], 'CNRORUC' => $laTmp[2],
                'CRAZSOC' => $laTmp[3], 'DGENERA' => $laTmp[4], 'CDESEST' => $laTmp[5],
                'CDESTIP' => $laTmp[6], 'CDESMON' => $laTmp[7], 'CCENCOS' => $laTmp[8],
                'CDESCCO' => $laTmp[9], 'CCODART' => $laTmp[10], 'CDESART' => $laTmp[11],
                'CCLASIF' => $laTmp[12], 'NCANTID' => $laTmp[13], 'NCANING' => $laTmp[14],
                'NCOSTO' => $laTmp[15], 'CCODANT' => $laTmp[16]];
            $i++;
         }
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2930xTipo($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcFecIni = $this->paData['DINICIO'];
      $lcFecFin = $this->paData['DFINALI'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $lcTipo = $laFila['CDESTIP'];
            $laDatos[] = ['* ID.ORDEN: ' . $laFila['CIDORDE'] . '-' . $laFila['CCODANT'] . ' COTIZACION: ' . trim($laFila['CIDCOTI']) . ' RUC: ' . trim($laFila['CNRORUC']) . ' - ' . fxString($laFila['CRAZSOC'], 67)];
            $laDatos[] = ['* FECHA: ' . $laFila['DGENERA'] . ' - ' . trim($laFila['CDESEST']) . ' - ' . trim($laFila['CDESTIP']) . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDORDE'];
            $laSuma = 0.00;
         } elseif ($lcIdRequ != $laFila['CIDORDE']) {
            $laDatos[] = ['--------------------------------------------------------------------------------------------------------------------------------------------'];
            $laDatos[] = ['* TOTAL                                                                                                                       ' . fxNumber($laSuma, 14, 2)];
            $laDatos[] = ['* ID.ORDEN: ' . $laFila['CIDORDE'] . '-' . $laFila['CCODANT'] . ' COTIZACION: ' . trim($laFila['CIDCOTI']) . ' RUC: ' . trim($laFila['CNRORUC']) . ' - ' . fxString($laFila['CRAZSOC'], 67)];
            $laDatos[] = ['* FECHA: ' . $laFila['DGENERA'] . ' - ' . trim($laFila['CDESEST']) . ' - ' . trim($laFila['CDESTIP']) . ' - ' . trim($laFila['CDESMON'])];
            $lcIdRequ = $laFila['CIDORDE'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $lnTotal = $laFila['NCANTID'] * $laFila['NCOSTO'];
         $laDatos[] = [fxString($lnContad, 3) . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 29) . ' ' . $laFila['CCLASIF'] . ' ' . $laFila['CCENCOS'] . '-' . fxString($laFila['CDESCCO'], 27) . ' ' . fxNumber($laFila['NCANTID'], 12, 4) . ' ' . fxNumber($laFila['NCANING'], 12, 4) . ' ' . fxNumber($laFila['NCOSTO'], 14, 4) . ' ' . fxNumber($lnTotal, 13, 2)];
         $laSuma += $laFila['NCANTID'] * $laFila['NCOSTO'];
         $lnContad += 1;
      }
      $laDatos[] = ['--------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                                                                       ' . fxNumber($laSuma, 14, 2)];
      $laDatos[] = ['--------------------------------------------------------------------------------------------------------------------------------------------'];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                                             ORDENES DE COMPRA/SERVICIO POR MES                                           PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2930                                             DESDE: ' . $lcFecIni . '  HASTA: ' . $lcFecFin . '                                          ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>                                                               TIPO : ' . fxString($lcTipo, 10) . '                                                           </b>', $lnFont);
            $loPdf->ezText('<b>--------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>    CODIGO   DESCRIPCION                    CLASIF.   CENTRO DE COSTO                     CANTIDAD   CANT. ING.    COSTO UNIT.   COSTO TOTAL</b>', $lnFont);
            $loPdf->ezText('<b>--------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 48) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE ORDENES DE COMPRA - LOGISTICA - CREACION - ALBERTO - 2018-02-21
   public function omRep2930OCS() {
      $llOk = $this->mxValParamRep2930OCS();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2930OCS($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep2930OCS($loSql);
      return $llOk;
   }

   protected function mxValParamRep2930OCS() {
      $loDate = new CDate();
      if (!$loDate->valDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL NO PUEDE SER MENOR QUE FECHA INICIAL';
         return false;
      }
      return true;
   }

   protected function mxRep2930OCS($p_oSql) {
      $lcTipo   = $this->paData['CTIPO'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $i = 0;
      if ($lcTipo == '*') {
         $lcSql = "SELECT cIdOrde, cNroRuc, cRazSoc, cEstado, cDesEst, dGenera, cTipo,
                   cDesTip, cIncIgv, nMonto, cMoneda, cDesMon, cCodAnt
                   FROM F_E01MORD_3('$ldInicio', '$ldFinali')
                   ORDER BY cCodAnt";
      } else {
         $lcSql = "SELECT cIdOrde, cNroRuc, cRazSoc, cEstado, cDesEst, dGenera, cTipo,
                   cDesTip, cIncIgv, nMonto, cMoneda, cDesMon, cCodAnt
                   FROM F_E01MORD_3('$ldInicio', '$ldFinali')
                   WHERE cTipo = '$lcTipo'
                   ORDER BY cCodAnt";
      }

      $R2 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R2)) {
         $this->laDatos[] = ['CIDORDE' => $laTmp[0], 'CNRORUC' => $laTmp[1], 'CRAZSOC' => $laTmp[2],
             'CESTADO' => $laTmp[3], 'CDESEST' => $laTmp[4], 'DGENERA' => $laTmp[5],
             'CTIPO' => $laTmp[6], 'CDESTIP' => $laTmp[7], 'CINCIGV' => $laTmp[8],
             'NMONTO' => $laTmp[9], 'NMONEDA' => $laTmp[10], 'CDESMON' => $laTmp[11],
             'CCODANT' => $laTmp[12]];
         $i++;
      }

      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2930OCS($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcFecIni = $this->paData['DINICIO'];
      $lcFecFin = $this->paData['DFINALI'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         $laDatos[] = [fxString($lnContad, 3) . ' ' . $laFila['CCODANT'] . ' ' . $laFila['CNRORUC'] . ' ' . fxString($laFila['CRAZSOC'], 16) . ' ' . fxString($laFila['CDESEST'], 11) . ' ' . $laFila['DGENERA'] . ' ' . fxString($laFila['CDESTIP'], 8) . ' ' . fxString($laFila['CDESMON'], 5) . ' ' . fxNumber($laFila['NMONTO'], 12, 2)];
         $laSuma += $laFila['NMONTO'];
         $lnContad += 1;
      }
      //$laDatos[] = ['----------------------------------------------------------------------------------------------'];
      //$laDatos[] = ['* TOTAL                                                                         ' . fxNumber($laSuma, 14, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                           ORDENES DE COMPRA/SERVICIO                       PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP292                       DESDE: ' . $lcFecFin . ' HASTA: ' . $lcFecFin . '                    ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>    ID.ORDE. NRO. RUC    RAZ. SOCIAL        ESTADO      FECHA      TIPO     MONEDA       MONTO</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE ORDENES DE COMPRA POR ARTICULO - LOGISTICA - CREACION - ALBERTO - 2018-02-21
   public function omRep2930xArticulo() {
      $llOk = $this->mxValParamRep2930xArticulo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2930xArticulo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep2930xArticulo($loSql);
      return $llOk;
   }

   protected function mxValParamRep2930xArticulo() {
      $loDate = new CDate();
      if (!$loDate->valDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL NO PUEDE SER MENOR QUE FECHA INICIAL';
         return false;
      }
      return true;
   }

   protected function mxRep2930xArticulo($p_oSql) {
      $lcCodArt = $this->paData['CCODART'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcSql = "SELECT DISTINCT(cCodArt), cDesArt, cClasif, cDesCla, cIdOrde, cNroRuc, cRazSoc, dGenera, cDesTip, cDesMon, nCantid, nCosto, cCodAnt
                FROM F_E01MORD_2('$lcCodArt') WHERE dGenera BETWEEN '$ldInicio' AND '$ldFinali'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODART' => $laTmp[0], 'CDESART' => $laTmp[1], 'CCLASIF' => $laTmp[2],
             'CDESCLA' => $laTmp[3], 'CIDORDE' => $laTmp[4], 'CNRORUC' => $laTmp[5],
             'CRAZSOC' => $laTmp[6], 'DGENERA' => $laTmp[7], 'CDESTIP' => $laTmp[8],
             'CDESMON' => $laTmp[9], 'NCANTID' => $laTmp[10], 'NCOSTO' => $laTmp[11],
             'CCODANT' => $laTmp[12]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2930xArticulo($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $lcFecIni = $this->paData['DINICIO'];
      $lcFecFin = $this->paData['DFINALI'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* COD. ART.: ' . $laFila['CCODART'] . ' - ' . trim($laFila['CDESART'])];
            $laDatos[] = ['* CLASIF. PRES.: ' . $laFila['CCLASIF'] . ' - ' . trim($laFila['CDESCLA'])];
            $lcIdRequ = $laFila['CCODART'];
            $laSuma = 0.00;
         } elseif ($lcIdRequ != $laFila['CCODART']) {
            //$laDatos[] = ['* COD. ART.: ' . $laFila['CCODART'] . ' - ' . trim($laFila['CDESART'])];
            //$laDatos[] = ['* CLASIF. PRES.: ' . $laFila['CCLASIF'] . ' - ' . trim($laFila['CDESCLA'])];
            $lcIdRequ = $laFila['CCODART'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $laDatos[] = [fxString($lnContad, 3) . ' ' . $laFila['CIDORDE'] . '-' . $laFila['CCODANT'] . ' ' . $laFila['CNRORUC'] . ' ' . $laFila['DGENERA'] . ' ' . fxString($laFila['CDESTIP'], 14) . ' ' . fxString($laFila['CDESMON'], 6) . ' ' . fxNumber($laFila['NCANTID'], 12, 4) . ' ' . fxNumber($laFila['NCOSTO'], 12, 2)];
         if ($laFila['NCANTID'] == 0.0000 )
         {
           $laSuma += $laFila['NCOSTO'];
         }
         else {
           $laSuma += $laFila['NCANTID'] * $laFila['NCOSTO'];
         }
         $lnContad += 1;
      }
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                           ' . fxNumber($laSuma, 12, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                    ORDENES DE COMPRA/SERVICIO POR ARTICULO                 PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2930                      DESDE: ' . $lcFecIni . '   HASTA: ' . $lcFecFin . '                  ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>                   *TODOS LOS COSTOS MOSTRADOS SE ENCUENTRAN EN SOLES (S/)*                   </b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b> #  ID.ORDE.            NRO. RUC    FECHA      TIPO           MONEDA     CANTIDAD        COSTO</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME ORDEN DE COMPRA O SERVICO - LOGISTICA - CREACION - ALBERTO - 2018-02-27
   public function omPrintOCS() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintOCS($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReportOCS($loSql);
      return $llOk;
   }

   protected function mxPrintOCS($p_oSql) {
      $lcIdOrde = $this->paData['CIDORDE'];
      $lcSql = "SELECT t_cIdOrde, t_dGenera, t_cNroRuc, t_cRazSoc, t_cDetEnt, t_cCodArt, t_cDesArt, t_nCantid, t_nCosto, t_mObserv, 
                       t_cTipo, t_cNroCel, t_cForPag, t_cCtaBc1, t_cCtaBc2, t_cCenCos, t_cDesCCo, t_cMoneda, t_cCodAnt, t_cUnidad,
                       t_cDesUni, TRIM(t_cDescri), t_cEmail, t_cDirecc, t_cCodPrv, t_cCCoAnt, t_cCtaCnt, t_cCodPar, t_cDesPar, 
                       t_cEstado, t_cLugar, t_cMonCor, t_cDesMon, t_nMonto, TO_CHAR(t_tAfecta, 'YYYY-MM-DD'), t_cUsuCot, TRIM(t_cNomCot),
                       t_cUsuGen, t_cNomGen, t_cEmaPrv
                FROM F_E01MORD_4('$lcIdOrde')";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDORDE' => $laTmp[0], 'DGENERA' => $laTmp[1], 'CNRORUC' => $laTmp[2], 'CRAZSOC' => $laTmp[3], 
                             'CDETENT' => $laTmp[4], 'CCODART' => $laTmp[5], 'CDESART' => $laTmp[6], 'NCANTID' => $laTmp[7], 
                             'NCOSTO'  => $laTmp[8], 'MOBSERV' => $laTmp[9], 'CTIPO'   => $laTmp[10],'CNROCEL' => $laTmp[11],
                             'CFORPAG' => $laTmp[12],'CCTABC1' => $laTmp[13],'CCTABC2' => $laTmp[14],'CCENCOS' => $laTmp[15], 
                             'CDESCCO' => $laTmp[16],'CMONEDA' => $laTmp[17],'CCODANT' => $laTmp[18],'CUNIDAD' => $laTmp[19],
                             'CDESUNI' => $laTmp[20],'CDESCRI' => $laTmp[21],'CEMAIL'  => $laTmp[22],'CDIRECC' => $laTmp[23],
                             'CCODPRV' => $laTmp[24],'CCCOANT' => $laTmp[25],'CCTACNT' => $laTmp[26],'CCODPAR' => $laTmp[27],
                             'CDESPAR' => mb_strtoupper($laTmp[28]),'CESTADO' => $laTmp[29],'CLUGAR' => $laTmp[30],'CMONCOR' => $laTmp[31],
                             'CDESMON' => $laTmp[32],'NMONTO'  => $laTmp[33],'DAFECTA' => $laTmp[34],'CUSUCOT' => $laTmp[35],
                             'CNOMCOT' => $laTmp[36],'CUSUGEN' => $laTmp[37],'CNOMGEN' => $laTmp[38],'CEMAPRV' => $laTmp[39]];
      }
      // RECUPERA FIRMAS DIGITALES
      $lcSql = "SELECT A.nSerial, A.cIdOrde, A.cCodUsu, B.cNombre, B.cGraAca, A.cNivel, TO_CHAR(A.tFirma, 'YYYY-MM-DD'),
                       B.nPosFiX, B.nPosFiY
                FROM E01DFIR A
                INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodUsu
                WHERE A.cIdOrde = '$lcIdOrde' AND A.cEstado = 'A'
                ORDER BY A.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = 'ERROR AL VALIDAR FIRMAS DIGITALES';
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $laNombre = explode('/', $laTmp[3]);
         $lcNombre = $laNombre[2].' '.$laNombre[0].' '.$laNombre[1];
         $this->laFirmas[] = ['NSERIAL' => $laTmp[0], 'CIDORDE' => $laTmp[1], 'CCODUSU' => $laTmp[2], 'CNOMBRE' => $lcNombre,
                              'CGRAACA' => $laTmp[4], 'CNIVEL'  => $laTmp[5], 'DFIRMA'  => $laTmp[6], 'NPOSFIX' => $laTmp[7],
                              'NPOSFIY' => $laTmp[8]];
      }
      return true;
   }

   protected function mxPrintReportOCS($p_oSql) {
      $lcTipRep = (!isset($this->paData['CTIPREP']))? 'N' : $this->paData['CTIPREP'];
      $laSuma = 0.00;
      $ldDate = date('Y-m-d', time());
      $lnCanArt = 1;
      $lcTipo = '';
      $lcMoneda = '';
      $lmObserv = '';
      $i = 0;
      foreach ($this->laDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         $this->laDatos[$i] = $laFila;
         /*
         UCSM-ERP                                                                            PAG.:    1
         ----------------------------------------------------------------------------------------------
         * ID. ORDEN        : 18000651                                           FECHA   :   2018-02-12
         * CENTRO DE COSTO  : 000 - DESCRIPCION DEL CENTRO DE COSTO
         * PROVEEDOR        : 00000000000 -  1234567890123456789012345678901234567890
         * DETALLE DE COMPRA: INMEDIATA                                          TELEFONO: 992128216000
         * CONDICION DE PAGO: 15 DIAS
         * CTA.BANCO SOLES : 12345678901234                        * CTA. BANCO US DOL: 12345678901234
         * CTA.BANCO US DOL :
         */
         $lcTipo = $laFila['CTIPO'];
         $lmObserv = $laFila['MOBSERV'];
         if ((float)$laFila['NCANTID'] != 0.0) {
            $lnSubTot = $laFila['NCANTID'] * $laFila['NCOSTO'];
            $laSuma += $laFila['NCANTID'] * $laFila['NCOSTO'];
            // CANTIDAD DE CARACTERES EN EL DETALLE 106
            // 3 + 1 + 8 + 1 + 12 + 1 + 3 + 1 + 46 + 4 + 12 + 1 + 12
            $laDatos[] = ['CLINEA' => fxNumber($lnCanArt, 3, 0) . ' ' . $laFila['CCODART'] . ' ' . fxNumber($laFila['NCANTID'], 12, 2) . ' ' . fxString($laFila['CUNIDAD'], 3) . ' ' . fxString($laFila['CDESART'], 49) . '    ' . fxNumber($laFila['NCOSTO'], 12, 2) . ' ' . fxNumber($lnSubTot, 13, 2), 'CESTFUE' => '', 'NTAMFUE' => 8, 'NSUBTOT' => $laSuma];
            $lcDescri = $laFila['CDESCRI'];
            do {
               if ($lcDescri == '') break;
               $laDatos[] = ['CLINEA' => fxString('', 30) . fxString($lcDescri, 49) . fxString('', 29), 'CESTFUE' => '', 'NTAMFUE' => 8, 'NSUBTOT' => $laSuma];
               $lcDescri = trim(fxStringTail($lcDescri, 49));
            } while(true);
            $lnCanArt += 1;
         }
         $i++;
      }
      //Numeros a Letras
      $loNumLet = new CNumeroLetras();
      $lcMonTot = strtoupper($loNumLet->omNumeroLetras($this->laDatos[0]['NMONTO'], $this->laDatos[0]['CDESMON']));
      $loPdf = new FPDF('portrait','cm','A4');
      $loPdf->SetMargins(1.3, 2.2, 1.3);
      $loPdf->SetAutoPageBreak(false);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $lnWidth = 0;
      $lnHeight = 0.4;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->Image("./img/ucsm-02.jpg" , 1.3, 1.4, 0, 2.3);
            $loPdf->SetFont('Courier', 'B' , 17);
            $loPdf->Cell($lnWidth, $lnHeight, '', 0, 2, 'C');
            $loPdf->Cell($lnWidth, $lnHeight, '', 0, 2, 'C');
            $loPdf->SetFont('Courier', 'B' , 14);
            $loPdf->SetTextColor(242,53,13);
            $loPdf->Cell($lnWidth, $lnHeight, $this->laDatos[0]['CCODANT'], 0, 2, 'R');
            $loPdf->Cell($lnWidth, 0.5, '', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->SetTextColor(0,0,0);
            $loPdf->Cell($lnWidth, $lnHeight, 'UCSM-ERP                                                                              PAG.:' . fxNumber($lnPag, 5, 0), 0, 2, 'L');
            // 96 CARACTERES POR LINEA EN LA CABECERA
            $loPdf->SetFont('Courier', 'BI' , 7);
            $loPdf->Cell($lnWidth, 0.2, '', 0, 2, 'C');
            $loPdf->SetFont('Courier', 'B' , 9);
            // 96 - 42 = 54 DISPONIBLES - 11 RUC = 43 DISPONIBLES
            $lnTam = (strlen(trim($this->laDatos[0]['CRAZSOC'])) < 36)? strlen(trim($this->laDatos[0]['CRAZSOC'])) : 36;
            $loPdf->Cell($lnWidth, $lnHeight, 'PROVEEDOR:       : ' . fxString($this->laDatos[0]['CRAZSOC'], $lnTam) . ' ' . $this->laDatos[0]['CNRORUC'] . fxString('', 36 - $lnTam) .' FECHA DE EMISION: ' . $this->laDatos[0]['DGENERA'], 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DIRECCIÓN        : ') . fxString($this->laDatos[0]['CDIRECC'], 56) . ' COD.PROV: ' . fxString($this->laDatos[0]['CCODPRV'], 10), 0, 2, 'L');
            if ($this->laDatos[0]['CEMAIL'] == $this->laDatos[0]['CEMAPRV']){
               $loPdf->Cell($lnWidth, $lnHeight, 'EMAIL            : ' . fxString($this->laDatos[0]['CEMAIL'] , 54) . utf8_decode(' TELÉFONO: ') . fxString($this->laDatos[0]['CNROCEL'], 12), 0, 2, 'L');
            } else {
               $loPdf->Cell($lnWidth, $lnHeight, 'EMAIL COTIZACION : ' . fxString($this->laDatos[0]['CEMAIL'] , 54) . utf8_decode(' TELÉFONO: ') . fxString($this->laDatos[0]['CNROCEL'], 12), 0, 2, 'L');
               $loPdf->Cell($lnWidth, $lnHeight, 'EMAIL PROVEEDOR  : ' . fxString($this->laDatos[0]['CEMAPRV'] , 54), 0, 2, 'L');
            }
            $loPdf->Cell($lnWidth, $lnHeight, 'TIEMPO DE ENTREGA: ' . fxString($this->laDatos[0]['CDETENT'], 77), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'LUGAR DE ENTREGA : ' . fxString($this->laDatos[0]['CLUGAR'], 77), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('CONDICIÓN DE PAGO: ') . fxString($this->laDatos[0]['CFORPAG'], 77), 0, 2, 'L');
            if ($this->laDatos[0]['CMONEDA'] == '1') {
               $loPdf->Cell($lnWidth, $lnHeight, 'CTA.BANCO SOLES  : ' . fxString($this->laDatos[0]['CCTABC1'], 77), 0, 2, 'L');
            } elseif ($this->laDatos[0]['CMONEDA'] == '2') {
               $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('CTA.BANCO DÓLARES: ') . fxString($this->laDatos[0]['CCTABC2'], 77), 0, 2, 'L');
            } else {
               $loPdf->Cell($lnWidth, $lnHeight, 'CTA.BANCO        : ' . fxString('NO DEFINIDA PARA MONEDA QUE NO SEA SOLES O DÓLARES', 75), 0, 2, 'L');
            }
            $loPdf->Cell($lnWidth, $lnHeight, 'CENTRO DE COSTO  : ' . $this->laDatos[0]['CCENCOS'] . ' - ' . fxString($this->laDatos[0]['CDESCCO'], 50) . utf8_decode(' CÓDIGO  : ') . fxString($this->laDatos[0]['CCCOANT'], 10), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '---DETALLE--------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode(' #   CÓDIGO      CANTIDAD UNI                   DESCRIPCIÓN                     PRECIO UNITARIO        TOTAL'), 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------', 0, 2, 'L');
            if ($lnPag > 1) {
               $loPdf->SetFont('Courier', 'BI', $laFila['NTAMFUE']);
               if ($this->laDatos[0]['CMONEDA'] == '1') {
                  $loPdf->Cell($lnWidth, $lnHeight, '* VIENEN ...                                                                               S/ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               } else {
                  $loPdf->Cell($lnWidth, $lnHeight, '* VIENEN ...                                                                                $ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               }
            }
            $lnRow = 0;
         }
         $loPdf->SetFont('Courier', $laFila['CESTFUE'], $laFila['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, $laFila['CLINEA'], 0, 2, 'L');
         $lnRow++;
         if ($lnRow == 30 && count($laDatos) != (30*$lnPag)) {
            $lnSubTot = $laFila['NSUBTOT'];
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'BI' , $laFila['NTAMFUE']);
            if ($this->laDatos[0]['CMONEDA'] == '1') {
               $loPdf->Cell($lnWidth, $lnHeight, '* VAN ...                                                                                  S/ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               $loPdf->Cell($lnWidth, $lnHeight, '* La presente orden tiene '.($lnCanArt - 1).' items por el importe total de S/ ' . trim(fxNumber($laDatos[count($laDatos)-1]['NSUBTOT'], 12, 2)), 0, 2, 'L');
               $loPdf->SetFont('Courier', 'B' , 9);
            } elseif ($this->laDatos[0]['CMONEDA'] == '2') {
               $loPdf->Cell($lnWidth, $lnHeight, '* VAN ...                                                                                   $ ' . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
               $loPdf->Cell($lnWidth, $lnHeight, '* La presente orden tiene '.($lnCanArt - 1).' items por el importe total de $ ' . trim(fxNumber($laDatos[count($laDatos)-1]['NSUBTOT'], 12, 2)), 0, 2, 'L');
               $loPdf->SetFont('Courier', 'B' , 9);
            }
            $llTitulo = true;
         } else {
            $llTitulo = false;
         }
      }
      for ($i = $lnRow; $i <= 30 ; $i++) {
         $loPdf->SetFont('Courier', $laDatos[0]['CESTFUE'], $laDatos[0]['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, '', 0, 2, 'L');
      }
      $loPdf->SetFont('Courier', 'B' , 9);
      $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------', 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight + 0.1, 'TOTAL ORDEN                                                                     '. fxString($this->laDatos[0]['CMONCOR'], 3) . ' ' . fxNumber($this->laDatos[0]['NMONTO'], 13, 2), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight + 0.1, fxString($lcMonTot, 96), 0, 2, 'L');
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(4.1, $lnHeight/1.5, 'PARTIDA PRESUPUESTAL:', 0, 0, 'L');
      $loPdf->SetFont('Courier', '', 7);
      $loDate = new CDate();
      if ($this->laDatos[0]['CESTADO'] != 'A') {
         $laObserv = explode("/", $lmObserv, 2);
         $lmObserv = (!$loDate->valDate(substr($laObserv[0], 0, 10)))? trim($lmObserv) : trim($laObserv[1]);
      }
      $lnTam = 36 - (strlen(trim($this->laDatos[0]['CCODPAR'])) - 21);
      if ($this->laDatos[0]['DAFECTA'] == null) {
         $loPdf->Cell($lnWidth, $lnHeight/1.5, '-', 0, 1, 'L');
      } else {
         $loPdf->Cell($lnWidth, $lnHeight/1.5, $this->laDatos[0]['DAFECTA'] . ': ' . trim($this->laDatos[0]['CCODPAR']).' - '.fxString($this->laDatos[0]['CDESPAR'], $lnTam), 0, 1, 'L');
      }
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(4.1, $lnHeight/1.5, utf8_decode('LIBERACIÓN LOGÍSTICA:'), 0, 0, 'L');
      $loPdf->SetFont('Courier', '', 7);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2 - 4.1, $lnHeight/1.5, isset($this->laFirmas[0])? $this->laFirmas[0]['DFIRMA'] : '-', 0, 0, 'L');
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(4.1, $lnHeight/1.5, utf8_decode('LIBERACIÓN VICE. ADMINIS.:'), 0, 0, 'L');
      $loPdf->SetFont('Courier', '', 7);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2 - 4.1, $lnHeight/1.5, isset($this->laFirmas[1])? $this->laFirmas[1]['DFIRMA'] : '-', 0, 1, 'L');
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Ln($lnHeight - 0.32);
      $loPdf->Cell($lnWidth, $lnHeight/1.5, 'OBSERVACIONES:', 0, 2, 'L');
      $loPdf->SetFont('Courier', $laDatos[0]['CESTFUE'], 7);
      $lnRow = 0;
      if ($lmObserv == '') {
         $loPdf->Cell($lnWidth, $lnHeight/1.5, 'SIN OBSERVACIONES.', 0, 2, 'L');
         $lnRow++;
      } else {
         $loPdf->Multicell($lnWidth, $lnHeight/1.5, $lmObserv, 0, 'J');
      }
      $loPdf->SetFont('Courier', 'BI', 7);
      $loPdf->Ln($lnHeight - 0.32);
      $loPdf->Cell($lnWidth, $lnHeight/1.5, utf8_decode('CREACIÓN: ').$this->laDatos[0]['CUSUGEN'].' - '.$this->laDatos[0]['CNOMGEN'], 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight/1.5, utf8_decode('COTIZADOR: ').$this->laDatos[0]['CUSUCOT'].' - '.$this->laDatos[0]['CNOMCOT'], 0, 2, 'L');
      $loPdf->Ln($lnHeight);
      if ($lcTipRep == 'P' || $this->laDatos[0]['CESTADO'] != 'F') {
         $loPdf->SetFont('Courier', 'B', 7);
         $loPdf->Cell($lnWidth, $lnHeight - 0.32, ($lcTipRep == 'P')?'REPORTE PARA PROVEEDORES' : 'REPORTE INTERNO', 0, 2, 'C');
      }
      if (isset($this->laFirmas[0])) {
         $loPdf->Image("./img/{$this->laFirmas[0]['CNIVEL']}_{$this->laFirmas[0]['CCODUSU']}.png", $this->laFirmas[0]['NPOSFIX'], $this->laFirmas[0]['NPOSFIY'], 6.5, 0, 'PNG');
      }
      if (isset($this->laFirmas[1])) {
         $loPdf->Image("./img/{$this->laFirmas[1]['CNIVEL']}_{$this->laFirmas[1]['CCODUSU']}.png", $this->laFirmas[1]['NPOSFIX'], $this->laFirmas[1]['NPOSFIY'], 6.5, 0, 'PNG');
      }
      $loPdf->SetFont('Courier', 'B', 9);
      $loPdf->SetY($loPdf->GetPageHeight() - 2.5);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, '_______________________________________', 0, 2, 'C');
      if (isset($this->laFirmas[0]) AND $this->laFirmas[0]['CCODUSU'] == '2362') {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode("{$this->laFirmas[0]['CGRAACA']} {$this->laFirmas[0]['CNOMBRE']}"), 0, 2, 'C');
      } else {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('ECON. EDGAR JULIO CONTRERAS GONZALES'), 0, 2, 'C');
      }
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('DIRECTOR DE LOGÍSTICA Y CONTRATACIONES'), 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->SetXY($loPdf->GetX() + ($loPdf->GetPageWidth() - 2.6)/2, $loPdf->GetPageHeight() - 2.5);
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, '_______________________________________', 0, 2, 'C');
      if (isset($this->laFirmas[1])) {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode("{$this->laFirmas[1]['CGRAACA']} {$this->laFirmas[1]['CNOMBRE']}"), 0, 2, 'C');
      } else {
         $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('DR. CESAR CACERES ZARATE'), 0, 2, 'C');
      }
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('VICERRECTOR ADMINISTRATIVO'), 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 2.6)/2, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   // IMPRIME OL - LOGISTICA - CREACION - ALBERTO - 2018-02-27
   public function omPrintOL() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintOL($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepoOL($loSql);
      return $llOk;
   }

   protected function mxPrintOL($p_oSql) {
      $lcIdComp = $this->paData['CIDCOMP'];
      $lcSql = "SELECT t_cIdComp, t_cTipCom, t_cNroCom, t_dFecEmi, t_cMoneda, t_cDesMon, t_nTipCam, t_cNroRuc, t_cRazSoc, 
                       t_cIdOrde, t_cTipo, t_cCodAnt, t_cEstado, t_nMonto, t_nMonIgv, t_nInafec, t_nAdicio, t_mObserv, 
                       t_cCodArt, t_cDesArt, t_cUnidad, t_nCantid, CASE WHEN t_nCantid != 0 THEN (t_nCosto/t_nCantid)::NUMERIC(14,4) ELSE 0.0 END, 
                       t_nCosto AS nSubTot, t_nCosIgv, t_cDescri, t_cCodiOL, t_dFecIng, t_cGuiRem, t_cTipMov, t_cNumMov, t_dVencim
                FROM F_E01MFAC_1('$lcIdComp')";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY DATOS PARA IMPRIMIR";
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDCOMP' => $laTmp[0], 'CTIPCOM' => $laTmp[1], 'CNROCOM' => $laTmp[2], 'DFECEMI' => $laTmp[3],
                             'CMONEDA' => $laTmp[4], 'CDESMON' => $laTmp[5], 'NTIPCAM' => $laTmp[6], 'CNRORUC' => $laTmp[7],
                             'CRAZSOC' => $laTmp[8], 'CIDORDE' => $laTmp[9], 'CTIPO'   => $laTmp[10],'CCODANT' => $laTmp[11],
                             'CESTADO' => $laTmp[12],'NMONTO'  => $laTmp[13],'NMONIGV' => $laTmp[14],'NINAFEC' => $laTmp[15],
                             'NADICIO' => $laTmp[16],'MOBSERV' => $laTmp[17],'CCODART' => $laTmp[18],'CDESART' => $laTmp[19],
                             'CUNIDAD' => $laTmp[20],'NCANTID' => $laTmp[21],'NCOSTO'  => $laTmp[22],'NSUBTOT' => $laTmp[23],
                             'NCOSIGV' => $laTmp[24],'CDESCRI' => $laTmp[25],'CCODIOL' => $laTmp[26],'DFECING' => $laTmp[27],
                             'CGUIREM' => $laTmp[28],'CTIPMOV' => $laTmp[29],'CNUMMOV' => $laTmp[30],'DVENCIM' => ($laTmp[31] == null)? 'S/D' : $laTmp[31]];
      }
      return true;
   }

   protected function mxPrintRepoOL() {
      /*
      UCSM-ERP                     UNIVERSIDAD CATÓLICA DE SANTA MARIA                    PAG.:    1
      OL000001          OF. LOGÍSTICA Y CONTRATACIONES - REGISTRO DE COMPROBANTES         2020-03-24
      ----------------------------------------------------------------------------------------------
      ID. COMP.  : 01234567                  ORDEN : OC00000001                  MONEDA : 0123456789
      PROVEEDOR  : 01234567890 - 0123456789012345678901234567890123456789012345678901234567890123456
      COMPROBANTE: 01/F001-00000014                                         TIPO CAMBIO : 99,999.999
      EMISION    : 1900-01-01                                               VENCIMIENTO : 1900-01-01
      MOVIMIENTO : NI-2020123456     GUIA REMISION : F001-00000014        FECHA INGRESO : 2020-03-24
      DESCRIPCIÓN: 012345678901234567890123456789012345678901234567890123456789012345678901234567890
      ---DETALLE------------------------------------------------------------------------------------------------
        # CÓDIGO   DESCRIPCIÓN                                            CANTIDAD UNI        COSTO  VALOR TOTAL
      ----------------------------------------------------------------------------------------------------------
        1 01234567 012345678901234567890123456789012345678901234567 9,999,999.9999 UNI 9,999,999.99 9,999,999.99
      ----------------------------------------------------------------------------------------------
      SUBTOTAL :                                                                        9,999,999.99
      ADICIONAL:                                                                        9,999,999.99
      IMPUESTOS:                                                                        9,999,999.99
      INAFECTO :                                                                        9,999,999.99
      TOTAL    :                                                                        9,999,999.99
      */
      $lnSubTot = 0.00;
      $ldDate = date("Y-m-d");
      $i = 0;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         $this->laDatos[$i++] = $laFila;
         if ((float)$laFila['NCANTID'] != 0.0) {
            $lnSubTot += $laFila['NSUBTOT'];
            $laDatos[] = ['CLINEA' => fxNumber(++$j, 3, 0) . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 48) . ' ' . fxNumber($laFila['NCANTID'], 14, 4) . ' ' . fxString($laFila['CUNIDAD'], 3) . ' ' . fxNumber($laFila['NCOSTO'], 12, 2) . ' ' . fxNumber($laFila['NSUBTOT'], 12, 2), 'CESTFUE' => '', 'NTAMFUE' => 8, 'NSUBTOT' => $lnSubTot];
         }
      }
      $loPdf = new FPDF('landscape','cm','A5');
      $loPdf->SetMargins(1.5, 1, 1.5);
      $loPdf->SetAutoPageBreak(false);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $lnWidth = 0;
      $lnHeight = 0.38;
      $llTitulo = true;
      $lnFilas = ($this->laDatos[0]['CTIPO'] != 'S')? 18 : 19;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP                     UNIVERSIDAD CATÓLICA DE SANTA MARIA                    PAG.:'.fxNumber($lnPag,5,0)), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, $this->laDatos[0]['CCODIOL'].utf8_decode('          OF. LOGÍSTICA Y CONTRATACIONES - REGISTRO DE COMPROBANTES         ').$ldDate, 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'ID. COMP.  : '.$this->laDatos[0]['CIDCOMP'].'                  ORDEN : '.$this->laDatos[0]['CCODANT'].'                  MONEDA : '.fxString($this->laDatos[0]['CDESMON'], 10), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'PROVEEDOR  : '.$this->laDatos[0]['CNRORUC'].' - '.fxString($this->laDatos[0]['CRAZSOC'], 67), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'COMPROBANTE: '.$this->laDatos[0]['CTIPCOM'].'/'.fxString($this->laDatos[0]['CNROCOM'], 20).'                                  TIPO CAMBIO : '.fxNumber($this->laDatos[0]['NTIPCAM'], 10, 3), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('EMISIÓN    : '.$this->laDatos[0]['DFECEMI'].'                                               VENCIMIENTO : '.$this->laDatos[0]['DVENCIM']), 0, 2, 'L');
            if ($this->laDatos[0]['CTIPO'] != 'S' && $this->laDatos[0]['CTIPMOV'] != null) {
               $loPdf->Cell($lnWidth, $lnHeight, 'MOVIMIENTO : '.$this->laDatos[0]['CTIPMOV'].'-'.$this->laDatos[0]['CNUMMOV'].'     GUIA REMISION : '.fxString($this->laDatos[0]['CGUIREM'], 20).' FECHA INGRESO : '.$this->laDatos[0]['DFECING'], 0, 2, 'L');
            } elseif ($this->laDatos[0]['CTIPO'] != 'S') {
               $loPdf->Cell($lnWidth, $lnHeight, 'MOVIMIENTO : S/D               GUIA REMISION : S/D                  FECHA INGRESO : S/D', 0, 2, 'L');
            }
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DESCRIPCIÓN: ').fxString($this->laDatos[0]['CDESCRI'], 81), 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, '---DETALLE------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('  # CÓDIGO   DESCRIPCIÓN                                            CANTIDAD UNI        COSTO  VALOR TOTAL'), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->SetFont('Courier', $laFila['CESTFUE'] , $laFila['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, $laFila['CLINEA'], 0, 2, 'L');
         $lnRow++;
         $llTitulo = ($lnRow == 18 && count($laDatos) != (18*$lnPag))? true : false;
         if ($lnRow == $lnFilas && count($laDatos) != ($lnFilas*$lnPag)) {
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'BI' , $laFila['NTAMFUE']);
            $loPdf->Cell($lnWidth, $lnHeight, '* VAN ...                                                                                  S/ ' . fxNumber($laFila['NSUBTOT'], 12, 2), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '* El presente comprobante tiene '.$j.' items por el importe total de S/ ' . trim(fxNumber($this->laDatos[0]['NMONTO'], 12, 2)), 0, 2, 'L');
            $llTitulo = true;
         } else {
            $llTitulo = false;
         }
      }
      $loPdf->SetFont('Courier', 'B' , 9);
      $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------', 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, fxString('SUBTOTAL :', 82) . fxNumber($lnSubTot, 12, 2), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, fxString('ADICIONAL:', 82) . fxNumber($this->laDatos[0]['NADICIO'], 12, 2), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, fxString('IMPUESTOS:', 82) . fxNumber($this->laDatos[0]['NMONIGV'], 12, 2), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, fxString('INAFECTO :', 82) . fxNumber($this->laDatos[0]['NINAFEC'], 12, 2), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, fxString('TOTAL    :', 82) . fxNumber($this->laDatos[0]['NMONTO'], 12, 2), 0, 2, 'L');
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   // IMPRIME NOTA DE INGRESO - LOGISTICA - CREACION - ALBERTO - 2018-02-27
   public function omPrintNotaIngreso() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintNotaIngreso($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepNotaIngreso($loSql);
      return $llOk;
   }

   protected function mxPrintNotaIngreso($p_oSql) {
      $lcNotIng = $this->paData['CNOTING'];
      $lcIdOrde = '';
      $lcSql = "SELECT cNotIng, cIdOrde, dFecha, cNroCom, cGuiRem, mObserv, cCodArt, cDesArt, 
                     nCantid, nMonto, cNroRuc, cRazSoc, cOrdAnt, cTipMov, cNumMov, cCodEmp,
                     cNomEmp, cCodAlu, cNomAlu, cIdRequ, cCenCos, cDesCco, cNroTra, cDesTra,
                     cNroBol, cAlmOri, cDesAlm, cAlmDes, cAlDesc, cTipo
                FROM F_E01MNIN_1('$lcNotIng')";
      $R1 = $p_oSql->omExec($lcSql);
      if (!$R1) {
         $this->pcError = 'ERROR DE EJECUCIÓN EN BASE DE DATOS';
         return false;
      }
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         if ($laTmp[8] == 0 && $laTmp[9] == 0){
             continue;
         }
         $this->laDatos[] = ['CNOTING' => $laTmp[0], 'CIDORDE' => $laTmp[1], 'DFECHA'  => $laTmp[2], 'CNROCOM' => $laTmp[3], 
                             'CGUIREM' => $laTmp[4], 'MOBSERV' => $laTmp[5], 'CCODART' => $laTmp[6], 'CDESART' => $laTmp[7], 
                             'NCANTID' => $laTmp[8], 'NMONTO'  => $laTmp[9], 'CNRORUC' => $laTmp[10],'CRAZSOC' => $laTmp[11],
                             'CORDANT' => $laTmp[12],'CTIPMOV' => $laTmp[13],'CNUMMOV' => $laTmp[14],'CCODEMP' => $laTmp[15],
                             'CNOMEMP' => $laTmp[16],'CCODALU' => $laTmp[17],'CNOMALU' => $laTmp[18],'CIDREQU' => $laTmp[19],
                             'CCENCOS' => $laTmp[20],'CDESCCO' => $laTmp[21],'CNROTRA' => $laTmp[22],'CDESTRA' => $laTmp[23],
                             'CNROBOL' => $laTmp[24],'CALMORI' => $laTmp[25],'CDESALM' => $laTmp[26],'CALMDES' => $laTmp[27],
                             'CALDESC' => $laTmp[28],'CTIPO'   => $laTmp[29]];
         $lcIdOrde = $laTmp[1];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   // IMPRIME NOTA DE INGRESO (IB) CREACION - WZA - 2021/08/12
   public function omPrintNotaIngresoIB() {
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
       $this->pcError = $loSql->pcError;
       return false;
    }
    $llOk = $this->mxPrintNotaIngresoIB($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
       return false;
    }
    $llOk = $this->mxPrintRepIngresoBotica($loSql);
    return $llOk;
 }

 protected function mxPrintNotaIngresoIB($p_oSql) {
    $lcNotIng = $this->paData['CNOTING'];
    $lcIdOrde = '';
    $lcSql = "SELECT A.cIdAdqu, A.dFecAdq, A.cNroCom, A.cGuiRem, B.cCodArt, C.cDesCri, B.nCantid, B.nMonto, D.cNroRuc, E.cRazSoc, D.cTipMov, D.cNumMov FROM E05MADQ A
              INNER JOIN E05DADQ B ON B.cIdAdqu = A.cIdAdqu
              INNER JOIN E01MART C ON C.cCodArt = B.cCodArt
              INNER JOIN E03MKAR D ON D.cIdKard = A.cIdKard
              INNER JOIN S01MPRV E ON E.cNroRuc = D.cNroRuc 
              WHERE A.cIdAdqu = '$lcNotIng'";
    $R1 = $p_oSql->omExec($lcSql);
    if (!$R1) {
       $this->pcError = 'ERROR DE EJECUCIÓN EN BASE DE DATOS';
       return false;
    }
    $i = 0;
    while ($laTmp = $p_oSql->fetch($R1)) {
       if ($laTmp[8] == 0 && $laTmp[9] == 0){
            continue;
       }
       $this->laDatos[] = ['CNOTING' => $laTmp[0], 'DFECHA'  => $laTmp[1], 'CNROCOM' => $laTmp[2], 
                           'CGUIREM' => trim($laTmp[3]) == ''? 'S/G': $laTmp[3], 'CCODART' => $laTmp[4], 'CDESART' => $laTmp[5], 
                           'NCANTID' => $laTmp[6], 'NMONTO'  => $laTmp[7], 'CNRORUC' => $laTmp[8],
                           'CRAZSOC' => $laTmp[9], 'CTIPMOV' => $laTmp[10],'CNUMMOV' => $laTmp[11]];
       $lcIdOrde = $laTmp[1];
       $i++;
    }
    if ($i == 0) {
       $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
       return false;
    }
    return true;
 }

   protected function mxPrintRepNotaIngreso($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $lcCompro = '';
      $lcGuiRem = '';
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            if ($this->laFila['CIDREQU'] != '00000000') {
               //$loPdf->ezText('<b>* MOVIMIENTO: ' . $this->paDatos[0]['CTIPMOV'] . $this->paDatos[0]['CNUMMOV'] . fxString('', 10) . 'REQUERIMIENTO: ' . $this->paDatos[0]['CIDREQU'] . fxString('', 10) .utf8_encode('FECHA EMISIÓN: ') . $this->paDatos[0]['DFECHA'] . '</b>', $lnFont);
               $laDatos[] = ['* MOVIMIENTO  : ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . fxString('',9) . 'REQUERIMIENTO: ' . $laFila['CIDREQU'] . fxString('', 8) . ' FECHA       : ' . $laFila['DFECHA']];
            } else {
               //$loPdf->ezText('<b>* MOVIMIENTO: ' . $this->paDatos[0]['CTIPMOV'] . $this->paDatos[0]['CNUMMOV'] . fxString('', 43) .utf8_encode('FECHA EMISIÓN: ') . $this->paDatos[0]['DFECHA'] . '</b>', $lnFont);
               $laDatos[] = ['* MOVIMIENTO  : ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . fxString('',10) . ' FECHA       : ' . $laFila['DFECHA']];
            }
            //$laDatos[] = ['* MOVIMIENTO  : ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . fxString('',17) . ' FECHA       : ' . $laFila['DFECHA']];
            $laDatos[] = ['* GUIA DE REM.: ' . $laFila['CGUIREM']];
            $laDatos[] = ['* PROVEEDOR   : ' . $laFila['CNRORUC'] . ' - ' . fxString(trim($laFila['CRAZSOC']), 40)];
            if($laFila['CTIPO'] == 'T') {
               $laDatos[] = ['* ALMACEN ORIGEN: ' . $laFila['CALMORI'] . ' - ' . fxString(trim($laFila['CDESALM']), 20) . 'ALMACEN DESTINO: ' . $laFila['CALMDES'] . ' - ' . fxString(trim($laFila['CALDESC']), 20)];
            } else {
                  $laDatos[] = ['* CENTRO COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString(trim($laFila['CDESCCO']), 40)];
            }
            if($laFila['CCODEMP'] == '9999') {
               $laDatos[] = ['* ALUMNO: ' . $laFila['CCODALU'] . ' - ' . fxString(trim($laFila['CNOMALU']), 70)];
            }
            else {
               $laDatos[] = ['* EMPLEADO    : ' . $laFila['CCODEMP'] . ' - ' . fxString(trim($laFila['CNOMEMP']), 70)];
            }
            if($laFila['CCODALU'] != '0000000000') {
               $laDatos[] = ['* NRO. BOLETA: ' . fxString($laFila['CNROBOL'], 10) . ' NRO. TRANS.:' . fxString($laFila['CNROTRA'], 10)];
            }
            $laDatos[] = ['* COMPROBANT. : ' . $laFila['CNROCOM']];
            $laDatos[] = ['* DESCRIPCION : ' . $laFila['CDESTRA']];
            $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
            $laDatos[] = ['<b>   COD.ART. DESCRIPCION                                                  CANTIDAD        COSTO</b>'];
            $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
            $lcIdRequ = $laFila['CNOTING'];
            $lnSuma = 0.00;
            $lnContad = 1;
         }
         $laDatos[] = [$lnContad . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 54) . ' ' . fxNumber(trim($laFila['NCANTID']), 14, 4) . ' ' . fxNumber($laFila['NMONTO'], 12, 2)];
         $lnContad += 1;
         $lnSuma += $laFila['NMONTO'];
      }
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                           ' . fxNumber($lnSuma, 12, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A5', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                                NOTA DE INGRESO                             PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            //$llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   protected function mxPrintRepIngresoBotica($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $lcCompro = '';
      $lcGuiRem = '';
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* MOVIMIENTO  : ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . fxString('',17) . ' FECHA       : ' . $laFila['DFECHA']];
            $laDatos[] = ['* GUIA DE REM.: ' . $laFila['CGUIREM']];
            $laDatos[] = ['* PROVEEDOR   : ' . $laFila['CNRORUC'] . ' - ' . fxString(trim($laFila['CRAZSOC']), 40)];
            $laDatos[] = ['* COMPROBANT. : ' . $laFila['CNROCOM']];
            $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
            $laDatos[] = ['<b>   COD.ART. DESCRIPCION                                                  CANTIDAD        COSTO</b>'];
            $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
            $lcIdRequ = $laFila['CNOTING'];
            $lnSuma = 0.00;
            $lnContad = 1;
         }
         $laDatos[] = [$lnContad . ' ' . $laFila['CCODART'] . ' ' . fxString($laFila['CDESART'], 54) . ' ' . fxNumber(trim($laFila['NCANTID']), 14, 4) . ' ' . fxNumber($laFila['NMONTO'], 12, 2)];
         $lnContad += 1;
         $lnSuma += $laFila['NMONTO'];
      }
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                           ' . fxNumber($lnSuma, 12, 2)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A5', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                                INGRESO BOTICA                            PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            //$llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE ORDENES DE COMPRA POR ARTICULO - LOGISTICA - CREACION - ALBERTO - 2018-02-21
   public function omRep2930xProveedor() {
      $llOk = $this->mxValParamRep2930xProveedor();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRep2930xProveedor($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRep2930xProveedor($loSql);
      return $llOk;
   }

   protected function mxValParamRep2930xProveedor() {
      $loDate = new CDate();
      if (!$loDate->valDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->valDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL NO PUEDE SER MENOR QUE FECHA INICIAL';
         return false;
      }
      return true;
   }

   protected function mxRep2930xProveedor($p_oSql) {
      $lcNroRuc = $this->paData['CNRORUC'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcSql = "SELECT A.cIdOrde, B.cNroRuc, C.cRazSoc, B.dGenera, B.cTipo, D.cDescri AS cDesTip, B.nMonto, B.cMoneda, E.cDescri AS cDesMon,
                A.cCodArt, F.cDescri AS cDesArt, A.nCantid, A.nCosto, B.cCodAnt FROM E01DORD A
                INNER JOIN E01MORD B ON B.cIdOrde = A.cIdOrde
                INNER JOIN S01MPRV C ON C.cNroRuc = B.cNroRuc
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodigo = B.cTipo AND D.cCodTab = '075'
                LEFT OUTER JOIN V_S01TTAB E ON E.cCodigo = B.cMoneda AND E.cCodTab = '007'
                INNER JOIN E01MART F ON F.cCodArt = A.cCodArt
                WHERE B.cNroRuc = '$lcNroRuc' AND B.dGenera BETWEEN '$ldInicio' AND '$ldFinali'";
      $R1 = $p_oSql->omExec($lcSql);
      $i=0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDORDE' => $laTmp[0], 'CNRORUC' => $laTmp[1], 'CRAZSOC' => $laTmp[2], 'DGENERA' => $laTmp[3], 
                             'CTIPO'   => $laTmp[4], 'CDESTIP' => $laTmp[5], 'NMONTO'  => $laTmp[6], 'CMONEDA' => $laTmp[7], 
                             'CDESMON' => $laTmp[8], 'CCODART' => $laTmp[9], 'CDESART' => $laTmp[10],'NCANTID' => $laTmp[11],
                             'NCOSTO'  => $laTmp[12],'CNUMORD' => $laTmp[13]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRep2930xProveedor($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $lnSuma = 0.00;
      $lnTotal = 0;
      $lcIdRequ = '*';
      $lcFecIni = $this->paData['DINICIO'];
      $lcFecFin = $this->paData['DFINALI'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            //* ID ORD.: 00000000 FECHA: 2018-01-01 TIPO: DONACIONES MONEDA: DOLARES
            $laDatos[] = ['* ID ORD.: ' . $laFila['CNUMORD'] . ' FECHA: ' . $laFila['DGENERA'] . ' TIPO: ' . $laFila['CDESTIP'] . ' MONEDA: ' . $laFila['CDESMON']];
            $laDatos[] = ['----------------------------------------------------------------------------------------------'];
            $lcIdRequ = $laFila['CIDORDE'];
         } elseif ($lcIdRequ != $laFila['CIDORDE']) {
            $laDatos[] = ['----------------------------------------------------------------------------------------------'];
            $laDatos[] = ['* TOTAL                                                                           ' . fxNumber($lnSuma, 12, 2)];
            $laDatos[] = ['----------------------------------------------------------------------------------------------'];
            $laDatos[] = ['* ID ORD.: ' . $laFila['CNUMORD'] . ' FECHA: ' . $laFila['DGENERA'] . ' TIPO: ' . $laFila['CDESTIP'] . ' MONEDA: ' . $laFila['CDESMON']];
            $laDatos[] = ['----------------------------------------------------------------------------------------------'];
            $lcIdRequ = $laFila['CIDORDE'];
            $lnSuma = 0.00;
            $lnContad = 1;
         }
         $lnSuma += $laFila['NCANTID'] * $laFila['NCOSTO'];
         $lnTotal += $lnSuma;
         $laDatos[] = [$lnContad . '  ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 44) . ' ' . fxNumber($laFila['NCANTID'], 18, 4) . ' ' . fxNumber($laFila['NCOSTO'], 18, 4)];
         $lnContad += 1;
      }
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                           ' . fxNumber($lnSuma, 12, 2)];
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL ORDENES                                                                   ' . fxNumber($lnTotal, 12, 2)];
      $laDatos[] = ['----------------------------------------------------------------------------------------------'];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                             REPORTE POR PROVEEDOR                          PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>REP2930                      DESDE: ' . $lcFecIni . '   HASTA: ' . $lcFecFin . '                  ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>'.$this->laDatos[0]['CNRORUC'] . ' - ' . fxString($this->laDatos[0]['CRAZSOC'], 30) .'</b>', $lnFont,  array('justification' => 'centre'));
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>   COD.ART. DESCRIPCION                                            CANTIDAD              COSTO</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText(utf8_encode($laFila[0]), $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 66) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME COMPROBANTE DE ENVIO DE CAJA CHICA - CREACION - ALBERTO - 2018-04-18
   public function omPrintCajaChica() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintCajaChica($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepCajaChica($loSql);
      return $llOk;
   }

   protected function mxPrintCajaChica($p_oSql) {
      $lcNroCCh = $this->paData['CNROCCH'];
      if (isset($this->paData['CORDEN'])) {
         $lcOrden = $this->paData['CORDEN'];
         if ($lcOrden == 'FECHA'){
         $lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDTipDo, A.cNroCom, A.dFecha, A.cCodOpe, C.cDescri AS cDesOpe,
                        A.cRucNro, A.cCodUsu, A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, A.cCCoDes,
                        E.cDescri as cDesCCo, G.cCenCos, H.cDescri AS cDesCen, F.cDescri, F.cCodUsu, REPLACE(I.cNombre, '/', ' '),
                        F.cEstado, J.cDescri AS cDesEst, F.cAsient, F.dFecha
               FROM E02DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND B.cCodigo = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
               INNER JOIN S01TCCO E ON E.cCenCos = A.cCCoDes
               INNER JOIN E02MCCH F ON F.cNroCCh = A.cNroCCh
               INNER JOIN E02TCCH G ON G.cIdCajC = F.cIdCajC
               INNER JOIN S01TCCO H ON H.cCenCos = G.cCenCos
               LEFT OUTER JOIN V_S01TUSU_1 I ON I.cCodUsu = F.cCodUsu
               LEFT OUTER JOIN V_S01TTAB J ON J.cCodTab = '098' AND J.cCodigo = F.cEstado
               WHERE A.cNroCCh = '$lcNroCCh' ORDER BY A.dFecha ASC, A.cNroCom ASC";
         }
         else{
         $lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDTipDo, A.cNroCom, A.dFecha, A.cCodOpe, C.cDescri AS cDesOpe,
                        A.cRucNro, A.cCodUsu, A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, A.cCCoDes,
                        E.cDescri as cDesCCo, G.cCenCos, H.cDescri AS cDesCen, F.cDescri, F.cCodUsu, REPLACE(I.cNombre, '/', ' '),
                        F.cEstado, J.cDescri AS cDesEst, F.cAsient, F.dFecha
               FROM E02DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND B.cCodigo = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
               INNER JOIN S01TCCO E ON E.cCenCos = A.cCCoDes
               INNER JOIN E02MCCH F ON F.cNroCCh = A.cNroCCh
               INNER JOIN E02TCCH G ON G.cIdCajC = F.cIdCajC
               INNER JOIN S01TCCO H ON H.cCenCos = G.cCenCos
               LEFT OUTER JOIN V_S01TUSU_1 I ON I.cCodUsu = F.cCodUsu
               LEFT OUTER JOIN V_S01TTAB J ON J.cCodTab = '098' AND J.cCodigo = F.cEstado
               WHERE A.cNroCCh = '$lcNroCCh'";
         }
      }
      else{
         $lcSql = "SELECT A.cNroCCh, A.cTipDoc, B.cDescri AS cDTipDo, A.cNroCom, A.dFecha, A.cCodOpe, C.cDescri AS cDesOpe,
                        A.cRucNro, A.cCodUsu, A.cMoneda, D.cDescri AS cDesMon, A.cGlosa, A.nMonto, A.nMonIgv, A.cCCoDes,
                        E.cDescri as cDesCCo, G.cCenCos, H.cDescri AS cDesCen, F.cDescri, F.cCodUsu, REPLACE(I.cNombre, '/', ' '),
                        F.cEstado, J.cDescri AS cDesEst, F.cAsient, F.dFecha
               FROM E02DCCH A
               LEFT OUTER JOIN V_S01TTAB B ON B.cCodTab = '087' AND B.cCodigo = A.cTipDoc
               INNER JOIN E02TOPE C ON C.cCodOpe = A.cCodOpe
               LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = A.cMoneda
               INNER JOIN S01TCCO E ON E.cCenCos = A.cCCoDes
               INNER JOIN E02MCCH F ON F.cNroCCh = A.cNroCCh
               INNER JOIN E02TCCH G ON G.cIdCajC = F.cIdCajC
               INNER JOIN S01TCCO H ON H.cCenCos = G.cCenCos
               LEFT OUTER JOIN V_S01TUSU_1 I ON I.cCodUsu = F.cCodUsu
               LEFT OUTER JOIN V_S01TTAB J ON J.cCodTab = '098' AND J.cCodigo = F.cEstado
               WHERE A.cNroCCh = '$lcNroCCh' ORDER BY A.dFecha ASC, A.cNroCom ASC";
      }
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CNROCCH' => $laTmp[0], 'CTIPDOC' => $laTmp[1], 'CDTIPDO' => $laTmp[2], 'CNROCOM' => $laTmp[3], 
                             'DFECHA'  => $laTmp[4], 'CCODOPE' => $laTmp[5], 'CDESOPE' => $laTmp[6], 'CNRORUC' => $laTmp[7], 
                             'CCODUSU' => $laTmp[8], 'CMONEDA' => $laTmp[9], 'CDESMON' => $laTmp[10],'CGLOSA'  => $laTmp[11],
                             'NMONTO' => $laTmp[12], 'NMONIGV' => $laTmp[13],'CCCODES' => $laTmp[14],'CDESCCO' => $laTmp[15], 
                             'CCENCOS' => $laTmp[16],'CDESCEN' => $laTmp[17],'CDESCRI' => $laTmp[18],'CUSUCOD' => $laTmp[19], 
                             'CNOMBRE' => $laTmp[20],'CESTADO' => $laTmp[21],'CDESEST' => $laTmp[22],'CASIENT' => $laTmp[23],
                             'DFECCCH' => $laTmp[24]];
         $i++;
      }
      if ($i == 0) {
         /*
         * NUMERO         : 999999 - 999 - AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
         * DESCRIPCION    : AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
         * USUARIO        : 9999 - AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
         * FECHA          : 2018-01-01    ESTADO: AAAAAAAAAAAAAA    ASIENTO: 99999999999999
         */
         $lcSql = "SELECT A.cNroCCh, A.cDescri, A.cCodUsu, REPLACE(B.cNombre, '/', ' '), A.dFecha, A.cEstado, C.cDescri AS cDesEst, A.cAsient, 
                          D.cCenCos, E.cDescri AS cDesCCo
                   FROM E02MCCH A
                   LEFT OUTER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodUsu
                   LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '098' AND C.cCodigo = A.cEstado
                   INNER JOIN E02TCCH D ON D.cIdCajC = A.cIdCajC
                   INNER JOIN S01TCCO E ON E.cCenCos = D.cCenCos
                   WHERE A.cNroCCh = '$lcNroCCh'";
         $R1 = $p_oSql->omExec($lcSql);
         $i = 0;
         while ($laTmp = $p_oSql->fetch($R1)) {
            $this->laDatos[] = ['CNROCCH' => $laTmp[0], 'CDESCRI' => $laTmp[1], 'CCODUSU' => $laTmp[2], 'CNOMBRE' => $laTmp[3], 
                                'DFECHA'  => $laTmp[4], 'CESTADO' => $laTmp[5], 'CDESEST' => $laTmp[6], 'CASIENT' => $laTmp[7], 
                                'CCENCOS' => $laTmp[8], 'CDESCCO' => $laTmp[9]];
            $i++;
         }
      }
      return true;
  }

   protected function mxPrintRepCajaChica($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $lnSuma = 0.00;
      $lnSubTot = 0.00;
      $lcIdRequ = '*';
      //$lcFecIni = $this->paData['DINICIO'];
      //$lcFecFin = $this->paData['DFINALI'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      /*
      UCSM-ERP               CAJA CHICA                                                                  PAG.:    1
      TDO9999        DEL: 2018-01-01  AL: 2018-01-01                                                     2018-01-01
      -------------------------------------------------------------------------------------------------------------
       #  TIPO       DOCUMENTO            FECHA      NRO.RUC     OPERACION    EMPL. GLOSA
          CENTRO DE COSTO                                                               MONTO        IGV      TOTAL
      -------------------------------------------------------------------------------------------------------------
      * NUMERO         : 999999 - 999 - AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
      * DESCRIPCION    : AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
      * USUARIO        : 9999 - AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
      * FECHA          : 2018-01-01    ESTADO: AAAAAAAAAAAAAA    ASIENTO: 99999999999999
      999 AAAAAAAAAA 99999999999999999999 AAAA-MM-DD 99999999999 AAAAAAAAAAAA 9999  AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA
          999-AAAAAAAAAAAAAAAAAAAAAAAAAAAAAA                                       999,999.99 999,999.99 999,999.99
      -------------------------------------------------------------------------------------------------------------
      * TOTAL                                                                      999,999.99 999,999.99 999,999.99
      */
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*') {
            $laDatos[] = ['* NUMERO         : ' . $laFila['CNROCCH'] . ' - ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCEN'], 40)];
            $laDatos[] = ['* DESCRIPCION    : ' . $laFila['CDESCRI']];
            $laDatos[] = ['* USUARIO        : ' . $laFila['CUSUCOD'] . ' - ' . $laFila['CNOMBRE']];
            $laDatos[] = ['* FECHA          : ' . fxString($laFila['DFECCCH'], 13) . ' ESTADO: ' . $laFila['CDESEST'] . '  ASIENTO: ' . $laFila['CASIENT']];
            $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
            $lcIdRequ = $laFila['CNROCCH'];
            $laSuma = 0.00;
         } elseif ($lcIdRequ != $laFila['CNROCCH']) {
            $laDatos[] = ['* NUMERO         : ' . $laFila['CNROCCH'] . ' - ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCEN'], 40)];
            $laDatos[] = ['* DESCRIPCION    : ' . $laFila['CDESCRI']];
            $laDatos[] = ['* USUARIO        : ' . $laFila['CCODUSU'] . ' - ' . $laFila['CNOMBRE']];
            $laDatos[] = ['* FECHA          : ' . fxString($laFila['DFECHA'], 13) . ' ESTADO: ' . $laFila['CDESEST'] . '  ASIENTO: ' . $laFila['CASIENT']];
            $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
            $lcIdRequ = $laFila['CNROCCH'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $lnSubTot = $laFila['NMONTO'] + $laFila['NMONIGV'];
         $laDatos[] = [fxNumber($lnContad, 3, 0) . ' ' . fxString($laFila['CDTIPDO'], 9) . ' ' . fxString($laFila['CNROCOM'], 20) . ' ' . $laFila['DFECHA'] . ' ' . $laFila['CNRORUC'] . ' ' . fxString($laFila['CDESOPE'], 15) . ' ' . $laFila['CCODUSU'] . ' ' . fxStringFixed($laFila['CGLOSA'], 25) . ' ' . fxString($laFila['CCCODES'], 3) . '-' . fxString($laFila['CDESCCO'], 15) . ' ' . fxNumber($laFila['NMONTO'], 10, 2) . ' ' . fxNumber($laFila['NMONIGV'], 10, 2) . ' ' . fxNumber($lnSubTot, 10, 2)];
         $lnContad += 1;
         $lnSuma += $lnSubTot;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                                                                                            ' . fxNumber($lnSuma, 10, 2)];
      $lnFont = 8;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 8);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>'.utf8_encode('UCSM-ERP                                                     REPORTE DE RENDICIÓN DE CAJA CHICA                                                    PÁG.:'). fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ERP3140                                                                                                                                            ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b> #  TIPO      DOCUMENTO            FECHA      NRO.RUC     OPERACION       EMPL GLOSA                     CENTRO DE COSTO          MONTO        IGV      TOTAL</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . utf8_encode($laFila[0]) . '</b>', $lnFont);
         } else {
            $loPdf->ezText(utf8_encode($laFila[0]), $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 56) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME COMPROBANTE DE RENDICION DE CUENTAS - CREACION - ALBERTO - 2018-04-18
   public function omPrintRendicionCta() {
      $llOk = $this->mxValPrintRendicionCta();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintRendicionCta($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepRendicionCta($loSql);
      return $llOk;
   }

   protected function mxValPrintRendicionCta() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CNRORCT']) || strlen(trim($this->paData['CNRORCT'])) != 8) {
         $this->pcError = 'NRO DE RENDICION INVALIDA';
         return false;
      } elseif (!isset($this->paData['CTIPIMP']) || strlen(trim($this->paData['CTIPIMP'])) != 1) {
         $this->pcError = 'TIPO DE IMPRESION INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxPrintRendicionCta($p_oSql) {
      $lcNroRct = $this->paData['CNRORCT'];
      $lcTipImp = $this->paData['CTIPIMP'];
      $lcSql = "SELECT A.cNroRct, A.cTipDoc, C.cDescri AS cDesTip, A.cNroCom, A.cCtaCnt, D.cDescri AS cDesCta, A.dFecha,
                       A.cNroRuc, E.cRazSoc, A.cIdPais, A.cGlosa, A.nMonSol, A.nMonOri, A.nMonIgv, B.cCodEmp, B.cCodAlu,
                       B.cCenCos, F.cDescri AS cDesCCo, B.dFecha AS dFecRCt, B.cEstado, G.cDescri AS cDesEst, A.cAsient,
                       B.mObserv, B.cDescri, H.cNombre AS cNomEmp, J.cNombre AS cNomAlu, A.nInafec,B.cIdRequ
                FROM E02DRCT A
                INNER JOIN E02MRCT B ON B.cNroRct = A.cNroRct
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '087' AND C.cCodigo = A.cTipDoc
                INNER JOIN D01MCTA D ON D.cCtaCnt = A.cCtaCnt
                LEFT OUTER JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
                INNER JOIN S01TCCO F ON F.cCenCos = B.cCenCos
                LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '098' AND G.cCodigo = B.cEstado
                LEFT OUTER JOIN V_S01TUSU_1 H ON H.cCodUsu = B.cCodEmp
                INNER JOIN A01MALU I ON I.cCodAlu = B.cCodAlu
                INNER JOIN S01MPER J ON J.cNroDni = I.cNroDni
                WHERE A.cNroRct = '$lcNroRct' AND A.cEstado != 'X'";
      if ($lcTipImp == 'P') {
         $lcSql .= "AND A.dFecCnt ISNULL ";
      }
      $lcSql .= "ORDER BY A.dFecha, A.cTipDoc, A.cNroCom";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CNRORCT' => $laTmp[0], 'CTIPDOC' => $laTmp[1], 'CDESTIP' => $laTmp[2], 'CNROCOM' => $laTmp[3], 
                             'CCTACNT' => $laTmp[4], 'CDESCTA' => $laTmp[5], 'DFECHA'  => $laTmp[6], 'CNRORUC' => $laTmp[7], 
                             'CRAZSOC' => $laTmp[8], 'CIDPAIS' => $laTmp[9], 'CGLOSA' => $laTmp[10], 'NMONSOL' => $laTmp[11],
                             'NMONORI' => $laTmp[12],'NMONIGV' => $laTmp[13],'CCODEMP' => $laTmp[14],'CCODALU' => $laTmp[15], 
                             'CCENCOS' => $laTmp[16],'CDESCCO' => $laTmp[17],'DFECRCT' => $laTmp[18],'CESTADO' => $laTmp[19], 
                             'CDESEST' => $laTmp[20],'CASIENT' => $laTmp[21],'MOBSERV' => $laTmp[22],'CDESCRI' => $laTmp[23],
                             'CNOMEMP' => $laTmp[24],'CNOMALU' => $laTmp[25],'NINAFEC' => $laTmp[26],'CIDREQU' => $laTmp[27]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepRendicionCta($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $lnSuma = 0.00;
      $lnSubTot = 0.00;
      $lcNroRct = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         if ($lcNroRct == '*') {
            $laDatos[] = ['* NRO RENDICION  : ' . $laFila['CNRORCT']];
            $laDatos[] = ['* ID REQ. ORIGEN : ' . $laFila['CIDREQU']];
            if ($laFila['CCODALU'] == '0000000000') {
               $laDatos[] = ['* EMPLEADO       : ' . $laFila['CCODEMP'] . ' - ' . $laFila['CNOMEMP']];
            } else {
               $laDatos[] = ['* ALUMNO         : ' . $laFila['CCODALU'] . ' - ' . $laFila['CNOMALU']];
            }
            $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCCO'], 40) . ' ESTADO         : ' . $laFila['CDESEST']];
            $laDatos[] = ['* FECHA          : ' . $laFila['DFECHA']];
            $laDatos[] = ['* DESCRIPCION    : ' . fxString($laFila['CDESCRI'], 138)];
            if(trim($laFila['MOBSERV']) != '') {
               $laDatos[] = ['* OBSERVACIONES  : ' . fxString($laFila['MOBSERV'], 138)];
            }
            $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
            $lcNroRct = $laFila['CNRORCT'];
            $laSuma = 0.00;
         } elseif ($lcNroRct != $laFila['CNRORCT']) {
            $laDatos[] = ['* ID REQ. ORIGEN : ' . $laFila['CIDREQU']];
            if ($laFila['CCODALU'] == '0000000000'){
               $laDatos[] = ['* EMPLEADO       : ' . $laFila['CCODEMP'] . ' - ' . $laFila['CNOMEMP']];
            }
            else{
               $laDatos[] = ['* ALUMNO         : ' . $laFila['CCODALU'] . ' - ' . $laFila['CNOMALU']];
            }
            $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCCO'], 40) . ' ESTADO         : ' . $laFila['CDESEST']];
            $laDatos[] = ['* FECHA          : ' . $laFila['DFECHA']];
            $laDatos[] = ['* DESCRIPCION    : ' . fxString($laFila['CDESCRI'], 138)];
            if($laFila['MOBSERV'] == '')
            {
               $laDatos[] = ['* OBSERVACIONES  : ' . fxString($laFila['MOBSERV'], 138)];
            }
            $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
            $lcNroRct = $laFila['CNRORCT'];
            $laSuma = 0.00;
            $lnContad = 1;
         }
         $lnSubTot = $laFila['NMONSOL'] + $laFila['NMONIGV'] + $laFila['NINAFEC'];
         $laDatos[] = [fxNumber($lnContad, 3, 0) . ' ' . fxString($laFila['CDESTIP'], 9) . ' ' . fxString($laFila['CNROCOM'], 20) . ' ' . fxString($laFila['CCTACNT'], 12) . ' ' . $laFila['DFECHA'] . ' ' . fxString($laFila['CNRORUC'], 11) . ' ' . $laFila['CIDPAIS'] . '  ' . fxStringFixed($laFila['CGLOSA'], 33) . ' ' . fxNumber($laFila['NMONSOL'], 10, 2) . '     ' . fxNumber($laFila['NMONORI'], 10, 2) . ' ' . fxNumber($laFila['NMONIGV'], 10, 2) . ' ' . fxNumber($lnSubTot, 10, 2)];        $lnContad += 1;
         $lnSuma += $lnSubTot;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                                                                                         ' . fxNumber($lnSuma, 13, 2)];
      $lnFont = 8;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 8);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            /*
                UCSM-ERP                                                                                                                                           PAG: 00000
                ERP4120                                                                                                                                     FECHA: 0000-00-00
                -------------------------------------------------------------------------------------------------------------------------------------------------------------
                * EMPLEADO       : 000 - NOMBRE DEL EMPLEADO
                * ALUMNO         : 0000000000 - NOMBRE DEL ALUMNO
                * CENTRO DE COSTO: 000 - DESCRIPCION DEL CENTRO DE COSTO          ESTADO         : ESTADO DE LA RENDICION DE CUENTA
                * FECHA          : 0000-00-00
                * DESCRIPCION    : 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
                * OBSERVACIONES  : 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
                -------------------------------------------------------------------------------------------------------------------------------------------------------------
                 #  TIPO      DOCUMENTO            OPERACION            FECHA      NRO.RUC     PAIS GLOSA                       MONTO S/  M.MONEDA ORI.   IMPUESTO      TOTAL
                -------------------------------------------------------------------------------------------------------------------------------------------------------------
                000 000000000 00000000000000000000 00000000000000000000 0000-00-00 00000000000 000  0000000000000000000000000 000,000.00     000,000.00 000,000.00 000,000.00
                -------------------------------------------------------------------------------------------------------------------------------------------------------------
                TOTAL                                                                                                                                           00,000,000.00
            */
            $loPdf->ezText('<b>UCSM-ERP                                                                RENDICION DE CUENTAS                                                       PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ERP4120                                                                                                                                     FECHA: ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('', $lnFont,  array('justification' => 'centre'));
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b> #  TIPO      DOCUMENTO            OPERACION    FECHA      NRO.RUC     PAIS GLOSA                               MONTO S/  M.MONEDA ORI.   IMPUESTO      TOTAL</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 56) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   public function omBuscarRequerimiento() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarRequerimiento($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarRequerimiento($p_oSql) {
      $lcIdRequ = strtoupper($this->paData['CIDREQU']);
      $lcSql = "SELECT cIdRequ,cCenCos,cDesCCo,cCodUsu,cNroDni,cNombre,cDescri,cTipo,cDesTip,
                       cComDir,cDestin,cEstado,cDesEst,cMoneda,cDesMon,cIdActi,cDesAct,
                       TO_CHAR(tGenera, 'YYYY-MM-DD HH24:MI')
                   FROM V_E01MREQ_2 WHERE
                   cIdRequ LIKE '%$lcIdRequ%' OR cCenCos LIKE '%$lcIdRequ%' OR
                   cDesCco LIKE '%$lcIdRequ%' OR cCodUsu LIKE '%$lcIdRequ%' OR
                   cNroDni LIKE '%$lcIdRequ%' OR cNombre LIKE '%$lcIdRequ%' OR
                   cDescri LIKE '%$lcIdRequ%' OR cDesTip LIKE '%$lcIdRequ%' OR
                   cDesEst LIKE '%$lcIdRequ%' OR cDesMon LIKE '%$lcIdRequ%' OR
                   cIdActi LIKE '%$lcIdRequ%' OR cDesAct LIKE '%$lcIdRequ%'
                ORDER BY cIdRequ DESC";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $i++;
         $this->paDatos[] = ['CIDREQU'=>$laFila[0], 'CCENCOS'=>$laFila[1], 'CDESCCO'=>$laFila[2],
                             'CCODUSU'=>$laFila[3], 'CNRODNI'=>$laFila[4], 'CNOMBRE'=>$laFila[5],
                             'CDESCRI'=>$laFila[6], 'CTIPO'  =>$laFila[7], 'CDESTIP'=>$laFila[8],
                             'CCOMDIR'=>$laFila[9], 'CDESTIN'=>$laFila[10],'CESTADO'=>$laFila[11],
                             'CDESEST'=>$laFila[12],'CMONEDA'=>$laFila[13],'CDESMON'=>$laFila[14],
                             'CIDACTI'=>$laFila[15],'CDESACT'=>$laFila[16],'TGENERA'=>$laFila[17]];
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS CON LOS PARAMETROS ENVIADOS';
         return false;
      }
      return true;
   }

   // IMPRIME OFICIO PARA ENVIO DE REQUERIMIENTO - CREACION - ALBERTO - 2018-06-06
   public function omPrintOficio() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintOficio($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepOficio($loSql);
      return $llOk;
   }

   protected function mxPrintOficio($p_oSql) {
      $lcIdRequ = $this->paData['CIDREQU'];
      $lcSql = "SELECT A.cIdRequ, A.cDescri, B.cDescri AS cDesCCo   
                FROM E01MREQ A 
                INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                WHERE cIdRequ = '$lcIdRequ'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDREQU' => $laTmp[0], 'CDESCRI' => $laTmp[1], 'CDESCCO' => $laTmp[2]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepOficio($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
        $laDatos[] = [$laFila['CDESCRI'] . ' - ' . $laFila['CIDREQU']];
        $lcIdRequ = $laFila['CIDREQU'];
        $lcDescri = $laFila['CDESCRI'];
        $lcDesCCo = $laFila['CDESCCO'];
      }
      $lnFont = 12;
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Times-Roman.afm', 11);
      $loPdf->ezSetCmMargins(2.5, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            /*
            SEÑOR
            VICERRECTOR ADMINISTRATIVO
            UNIVERSIDAD CATOLICA DE SANTA MARIA
            PRESENTE.-

            Asunto: [Descripcion del Requerimiento]

            De mi mayor consideracion:

            Tengo el agrado de dirigirme a usted, saludándolo cordialmente, para hacerle llegar el requerimiento identificado con número 00000000.
            Se adjunta el detalle al presente documento.
            Atentamente.
            */
            $loPdf->ezText(fxStringFixed('<b>SEÑOR</b>', 13), $lnFont);
            $loPdf->ezText('<b>VICERRECTOR ADMINISTRATIVO</b>', $lnFont);
            $loPdf->ezText('<b>UNIVERSIDAD CATOLICA DE SANTA MARIA</b>', $lnFont);
            $loPdf->ezText('PRESENTE.-', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('<b>Asunto: </b>'.$lcDescri, $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('De mi mayor consideracion:', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('Tengo el agrado de dirigirme a usted, saludandolo cordialmente, para hacerle llegar el requerimiento identificado con Nro. '.$lcIdRequ.', perteneciente a '.$lcDesCCo.'. Se adjunta el detalle al presente documento.', $lnFont, array('justification' => 'full'));
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('', $lnFont);
            $loPdf->ezText('Atentamente.', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $lnRow++;
         $llTitulo = ($lnRow == 56) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME OFICIO PARA ENVIO DE COMPRAS DIRECTAS - CREACION - JLF - 2018-09-18
   public function omPrintOficioCompraDirecta() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintOficioCompraDirecta($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepOficioCompraDirecta($loSql);
      return $llOk;
   }

   protected function mxPrintOficioCompraDirecta($p_oSql) {
      $lcIdRequ = $this->paDatos[0]['CIDREQU'];
      $lcSql = "SELECT A.cIdRequ, A.cNroDoc, A.cDescri, A.cCodUsu, C.cNombre AS cNomUsu, A.cCenCos, D.cDesCri AS cDesCCo
                FROM E01MREQ A 
                INNER JOIN S01TUSU B ON B.cCodUsu = A.cCodUsu
                INNER JOIN S01MPER C ON C.cNroDni = B.cNroDni 
                INNER JOIN S01TCCO D ON D.cCenCos = A.cCenCos 
                WHERE cIdRequ = '$lcIdRequ'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paData = ['CIDREQU' => $laTmp[0], 'CNRODOC' => $laTmp[1], 'CDESCRI' => $laTmp[2], 'CCODUSU' => $laTmp[3],
                          'CNOMUSU' => $laTmp[4], 'CCENCOS' => $laTmp[5], 'CDESCCO' => $laTmp[6]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepOficioCompraDirecta($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laMeses =  ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Setiembre','Octubre','Noviembre','Diciembre'];
      $lnFont = 12;
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Times-Roman.afm', 11);
      $loPdf->ezSetCmMargins(2.5, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      /*
                                    “IN SCENTIA ET FIDE EST FORTITUDO NOSTRA"
                                 (En la Ciencia y en la Fe está nuestra Fuerza)

                                                                        Arequipa, 99 de Setiembre del 2018

      [cNroDoc del Requerimiento]
      Señor Doctor
      JORGE LUIS CÁCERES ARCE
      Vicerrector Administrativo de la UCSM
      Presente. -

      ASUNTO: [Descripcion del Requerimiento]

      De mi especial consideracion:

            Tengo el agrado de dirigirme a Ud., para saludarlo cordialmente y por medio del presente hacer
      de su conocimiento sobre el siguiente requerimiento:

       ID Req        RUC                   Razón Social                      Factura             Monto    
      99999999   22222222222   0123456789012345678901234567890123456   01234567890123456789   999999999,99   

            Sin otro en particular, agradezco la atención al presente y hago propicia la oportunidad para 
      expresarle los sentimientos de mi estima personal.

                                                   Atentamente,

      [CNOMUSU del Requerimiento]
      */
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('<b>IN SCENTIA ET FIDE EST FORTITUDO NOSTRA</b>', $lnFont, array('justification' => 'center'));
      $loPdf->ezText(utf8_encode('<b>(En la Ciencia y en la Fe está nuestra Fuerza)</b>'), $lnFont, array('justification' => 'center'));
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('Arequipa, '.date("d \d\\e ").$laMeses[date("n")].date(" \d\\e\l Y"), $lnFont, array('justification' => 'right'));
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText(utf8_encode($this->paData['CNRODOC']), $lnFont, array('justification' => 'left'));
      $loPdf->ezText(utf8_encode(fxStringFixed('SEÑOR', 13)), $lnFont);
      $loPdf->ezText('<b>VICERRECTOR ADMINISTRATIVO DE LA UCSM</b>', $lnFont);
      $loPdf->ezText('PRESENTE. -', $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('<b>ASUNTO: </b>'.utf8_encode($this->paData['CDESCRI']), $lnFont, array('justification' => 'full', 'left' => 200));
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText(utf8_encode('De mi especial consideración:'), $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('Tengo el agrado de dirigirme a Ud., para saludarlo cordialmente y por medio del presente hacer de', $lnFont, array('justification' => 'full', 'left' => 30));
      $loPdf->ezText('su conocimiento sobre los siguientes requerimientos:', $lnFont, array('justification' => 'full'));
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('<b>'.str_pad('#', 4, ' ', STR_PAD_BOTH).' | '.str_pad('ID', 16, ' ', STR_PAD_BOTH).' | '.str_pad('RUC', 20, ' ', STR_PAD_BOTH).' | '.str_pad(utf8_encode('Razón Social'), 60, ' ', STR_PAD_BOTH).' | '.str_pad('Factura', 38, ' ', STR_PAD_BOTH).' | '.str_pad('Monto', 22, ' ', STR_PAD_BOTH).'</b>', 10, array('justification' => 'full'));
      $i = 0;
      foreach ($this->paDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
         }
         $loPdf->selectFont('fonts/Courier.afm', 11);
         $lcRazSoc = strlen(trim($laFila['CRAZSOC'])) > 31? substr($laFila['CRAZSOC'],0,31) : trim($laFila['CRAZSOC']);
         $loPdf->ezText('<b>'.str_pad(++$i, 2, ' ', STR_PAD_LEFT).'</b>'.fxString('',2).str_pad($laFila['CIDREQU'], 10).str_pad($laFila['CNRORUC'], 13).str_pad($lcRazSoc, 33).str_pad($laFila['CNROCOM'], 22).str_pad($laFila['NMONTO'], 12, ' ', STR_PAD_LEFT), 9, array('justification' => 'left'));
         $llTitulo = false;
         $lnRow = 0;
         $lnRow++;
         $llTitulo = ($lnRow == 56) ? true : false;
      }
      $loPdf->selectFont('fonts/Times-Roman.afm', 11);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText(utf8_encode('Sin otro en particular, agradezco la atención al presente y hago propicia la oportunidad para'), $lnFont, array('justification' => 'full', 'left' => 30));
      $loPdf->ezText('expresarle los sentimientos de mi estima personal.', $lnFont, array('justification' => 'full'));
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('Atentamente,', $lnFont, array('justification' => 'full', 'left' => 200));
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('', $lnFont);
      $loPdf->ezText('', $lnFont);
      //$loPdf->ezText($this->paData['CNOMUSU'], $lnFont, array('justification' => 'full', 'left' => 30));
      //$loPdf->ezText(str_pad($this->paData['CCODUSU'], strlen($this->paData['CNOMUSU'])*2, ' ', STR_PAD_BOTH), $lnFont, array('justification' => 'full', 'left' => 30));
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME COMPROBANTE DE RENDICION DE RECIBOS POR HONORARIOS - CREACION - ALBERTO - 2018-04-18
   public function omPrintRendicionPlanillas() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintRendicionPlanillas($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepRendicionPlanillas($loSql);
      return $llOk;
   }

   protected function mxPrintRendicionPlanillas($p_oSql) {
      $lcNroRct = $this->paData['CNRORCT'];
      $lcSql = "SELECT A.cNroRct, A.cTipDoc, C.cDescri AS cDesTip, A.cNroCom, A.cCtaCnt, D.cDescri AS cDesCta, A.dFecha,
                        A.cNroRuc, E.cRazSoc, A.cIdPais, A.cGlosa, A.nMonSol, A.nMonOri, A.nMonIgv, B.cCodEmp, B.cCodAlu,
                        B.cCenCos, F.cDescri AS cDesCCo, B.dFecha AS dFecRCt, B.cEstado, G.cDescri AS cDesEst, A.cAsient,
                        B.mObserv, B.cDescri, H.cNombre AS cNomEmp, J.cNombre AS cNomAlu, A.nMonRet
               FROM E02DRCT A
               INNER JOIN E02MRCT B ON B.cNroRct = A.cNroRct
               LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '087' AND C.cCodigo = A.cTipDoc
               INNER JOIN D01MCTA D ON D.cCtaCnt = A.cCtaCnt
               LEFT OUTER JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
               INNER JOIN S01TCCO F ON F.cCenCos = B.cCenCos
               LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '098' AND G.cCodigo = B.cEstado
               LEFT OUTER JOIN V_S01TUSU_1 H ON H.cCodUsu = B.cCodEmp
               INNER JOIN A01MALU I ON I.cCodAlu = B.cCodAlu
               INNER JOIN S01MPER J ON J.cNroDni = I.cNroDni
               WHERE A.cNroRct = '$lcNroRct'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CNRORCT' => $laTmp[0], 'CTIPDOC' => $laTmp[1], 'CDESTIP' => $laTmp[2],
                              'CNROCOM' => $laTmp[3], 'CCTACNT' => $laTmp[4], 'CDESCTA' => $laTmp[5],
                              'DFECHA' => $laTmp[6], 'CNRORUC' => $laTmp[7], 'CRAZSOC' => $laTmp[8],
                              'CIDPAIS' => $laTmp[9], 'CGLOSA' => $laTmp[10], 'NMONSOL' => $laTmp[11],
                              'NMONORI' => $laTmp[12], 'NMONIGV' => $laTmp[13], 'CCODEMP' => $laTmp[14],
                              'CCODALU' => $laTmp[15], 'CCENCOS' => $laTmp[16], 'CDESCCO' => $laTmp[17],
                              'DFECRCT' => $laTmp[18], 'CESTADO' => $laTmp[19], 'CDESEST' => $laTmp[20],
                              'CASIENT' => $laTmp[21], 'MOBSERV' => $laTmp[22], 'CDESCRI' => $laTmp[23],
                              'CNOMEMP' => $laTmp[24], 'CNOMALU' => $laTmp[25],'NMONRET' => $laTmp[26] ];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepRendicionPlanillas($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $lnSuma = 0.00;
      $lnSubTot = 0.00;
      $lcIdRequ = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*')
         {
         if ($laFila['CCODALU'] == '0000000000'){
            $laDatos[] = ['* EMPLEADO       : ' . $laFila['CCODEMP'] . ' - ' . $laFila['CNOMEMP']];
         }
         else{
            $laDatos[] = ['* ALUMNO         : ' . $laFila['CCODALU'] . ' - ' . $laFila['CNOMALU']];
         }
         $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCCO'], 40) . ' ESTADO         : ' . $laFila['CDESEST']];
         $laDatos[] = ['* FECHA          : ' . $laFila['DFECHA']];
         $laDatos[] = ['* DESCRIPCION    : ' . fxString($laFila['CDESCRI'], 138)];
         if($laFila['MOBSERV'] == '')
         {
            $laDatos[] = ['* OBSERVACIONES  : ' . fxString($laFila['MOBSERV'], 138)];
         }
         $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
         $lcIdRequ = $laFila['CNRORCT'];
         $laSuma = 0.00;
         }
         elseif ($lcIdRequ != $laFila['CNRORCT'])
         {
         if ($laFila['CCODALU'] == '0000000000'){
            $laDatos[] = ['* EMPLEADO       : ' . $laFila['CCODEMP'] . ' - ' . $laFila['CNOMEMP']];
         }
         else{
            $laDatos[] = ['* ALUMNO         : ' . $laFila['CCODALU'] . ' - ' . $laFila['CNOMALU']];
         }
         $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCCO'], 40) . ' ESTADO         : ' . $laFila['CDESEST']];
         $laDatos[] = ['* FECHA          : ' . $laFila['DFECHA']];
         $laDatos[] = ['* DESCRIPCION    : ' . fxString($laFila['CDESCRI'], 138)];
         if($laFila['MOBSERV'] == '')
         {
            $laDatos[] = ['* OBSERVACIONES  : ' . fxString($laFila['MOBSERV'], 138)];
         }
         $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
         $lcIdRequ = $laFila['CNRORCT'];
         $laSuma = 0.00;
         $lnContad = 1;
         }
         $lnSubTot = $laFila['NMONSOL'] ;
         $laDatos[] = [fxNumber($lnContad, 3, 0) . ' ' . fxString($laFila['CDESTIP'], 9) . ' ' . fxString($laFila['CNROCOM'], 20) . ' ' . fxString($laFila['CCTACNT'], 12) . ' ' . $laFila['DFECHA'] . ' ' . fxString($laFila['CNRORUC'], 11) . ' ' . $laFila['CIDPAIS'] . '  ' . fxStringFixed($laFila['CGLOSA'], 33) . ' ' . fxNumber($laFila['NMONSOL'], 10, 2) . '     ' . fxNumber($laFila['NMONORI'], 10, 2) . ' ' . fxNumber($laFila['NMONRET'], 10, 2) . ' ' . fxNumber($lnSubTot, 10, 2)];        $lnContad += 1;
         $lnSuma += $lnSubTot;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                                                                                         ' . fxNumber($lnSuma, 13, 2)];
      $lnFont = 8;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 8);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            /*
               UCSM-ERP                                         RENDICION DE RECIBOS POR HONORARIOS - ALIVIARI                                                    PAG: 00000
               ERP4120                                                                                                                                     FECHA: 0000-00-00
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
               * EMPLEADO       : 000 - NOMBRE DEL EMPLEADO
               * ALUMNO         : 0000000000 - NOMBRE DEL ALUMNO
               * CENTRO DE COSTO: 000 - DESCRIPCION DEL CENTRO DE COSTO          ESTADO         : ESTADO DE LA RENDICION DE CUENTA
               * FECHA          : 0000-00-00
               * DESCRIPCION    : 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
               * OBSERVACIONES  : 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
                  #  TIPO      DOCUMENTO            OPERACION            FECHA      NRO.RUC     PAIS GLOSA                       MONTO S/  M.MONEDA ORI.   IMPUESTO      TOTAL
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
               000 000000000 00000000000000000000 00000000000000000000 0000-00-00 00000000000 000  0000000000000000000000000 000,000.00     000,000.00 000,000.00 000,000.00
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
               TOTAL                                                                                                                                           00,000,000.00
            */
            $loPdf->ezText('<b>UCSM-ERP                                         RENDICION DE RECIBOS POR HONORARIOS - ALIVIARI                                                    PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ERP4120                                                                                                                                     FECHA: ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('', $lnFont,  array('justification' => 'centre'));
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b> #  TIPO      DOCUMENTO            OPERACION    FECHA      NRO.RUC     PAIS GLOSA                               MONTO S/  M.MONEDA ORI.   IMPUESTO      TOTAL</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 56) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME COMPROBANTE DE REQUERIMIENTO DIRECTO A CONTABILIDAD - CREACION - ALBERTO - 2018-09-14
   public function omPrintReqContabilidad() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintReqContabilidad($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepReqContabilidad($loSql);
      return $llOk;
   }

   protected function mxPrintReqContabilidad($p_oSql) {
      $lcNroRct = $this->paData['CNRORCT'];
      $lcSql = "SELECT A.cNroRct, A.cTipDoc, C.cDescri AS cDesTip, A.cNroCom, A.cCtaCnt, D.cDescri AS cDesCta, A.dFecha,
                        A.cNroRuc, E.cRazSoc, A.cIdPais, A.cGlosa, A.nMonSol, A.nMonOri, A.nMonIgv, B.cCodEmp, B.cCodAlu,
                        B.cCenCos, F.cDescri AS cDesCCo, B.dFecha AS dFecRCt, B.cEstado, G.cDescri AS cDesEst, A.cAsient,
                        B.mObserv, B.cDescri, H.cNombre AS cNomEmp, J.cNombre AS cNomAlu, A.nMonRet
               FROM E02DRCT A
               INNER JOIN E02MRCT B ON B.cNroRct = A.cNroRct
               LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '087' AND C.cCodigo = A.cTipDoc
               INNER JOIN D01MCTA D ON D.cCtaCnt = A.cCtaCnt
               LEFT OUTER JOIN S01MPRV E ON E.cNroRuc = A.cNroRuc
               INNER JOIN S01TCCO F ON F.cCenCos = B.cCenCos
               LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '098' AND G.cCodigo = B.cEstado
               LEFT OUTER JOIN V_S01TUSU_1 H ON H.cCodUsu = B.cCodEmp
               INNER JOIN A01MALU I ON I.cCodAlu = B.cCodAlu
               INNER JOIN S01MPER J ON J.cNroDni = I.cNroDni
               WHERE A.cNroRct = '$lcNroRct'";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CNRORCT' => $laTmp[0], 'CTIPDOC' => $laTmp[1], 'CDESTIP' => $laTmp[2],
                              'CNROCOM' => $laTmp[3], 'CCTACNT' => $laTmp[4], 'CDESCTA' => $laTmp[5],
                              'DFECHA' => $laTmp[6], 'CNRORUC' => $laTmp[7], 'CRAZSOC' => $laTmp[8],
                              'CIDPAIS' => $laTmp[9], 'CGLOSA' => $laTmp[10], 'NMONSOL' => $laTmp[11],
                              'NMONORI' => $laTmp[12], 'NMONIGV' => $laTmp[13], 'CCODEMP' => $laTmp[14],
                              'CCODALU' => $laTmp[15], 'CCENCOS' => $laTmp[16], 'CDESCCO' => $laTmp[17],
                              'DFECRCT' => $laTmp[18], 'CESTADO' => $laTmp[19], 'CDESEST' => $laTmp[20],
                              'CASIENT' => $laTmp[21], 'MOBSERV' => $laTmp[22], 'CDESCRI' => $laTmp[23],
                              'CNOMEMP' => $laTmp[24], 'CNOMALU' => $laTmp[25],'NMONRET' => $laTmp[26] ];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepReqContabilidad($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $lnSuma = 0.00;
      $lnSubTot = 0.00;
      $lcIdRequ = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcIdRequ == '*')
         {
         if ($laFila['CCODALU'] == '0000000000'){
            $laDatos[] = ['* EMPLEADO       : ' . $laFila['CCODEMP'] . ' - ' . $laFila['CNOMEMP']];
         }
         else{
            $laDatos[] = ['* ALUMNO         : ' . $laFila['CCODALU'] . ' - ' . $laFila['CNOMALU']];
         }
         $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCCO'], 40) . ' ESTADO         : ' . $laFila['CDESEST']];
         $laDatos[] = ['* FECHA          : ' . $laFila['DFECHA']];
         $laDatos[] = ['* DESCRIPCION    : ' . fxString($laFila['CDESCRI'], 138)];
         if($laFila['MOBSERV'] == '')
         {
            $laDatos[] = ['* OBSERVACIONES  : ' . fxString($laFila['MOBSERV'], 138)];
         }
         $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
         $lcIdRequ = $laFila['CNRORCT'];
         $laSuma = 0.00;
         }
         elseif ($lcIdRequ != $laFila['CNRORCT'])
         {
         if ($laFila['CCODALU'] == '0000000000'){
            $laDatos[] = ['* EMPLEADO       : ' . $laFila['CCODEMP'] . ' - ' . $laFila['CNOMEMP']];
         }
         else{
            $laDatos[] = ['* ALUMNO         : ' . $laFila['CCODALU'] . ' - ' . $laFila['CNOMALU']];
         }
         $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString($laFila['CDESCCO'], 40) . ' ESTADO         : ' . $laFila['CDESEST']];
         $laDatos[] = ['* FECHA          : ' . $laFila['DFECHA']];
         $laDatos[] = ['* DESCRIPCION    : ' . fxString($laFila['CDESCRI'], 138)];
         if($laFila['MOBSERV'] == '')
         {
            $laDatos[] = ['* OBSERVACIONES  : ' . fxString($laFila['MOBSERV'], 138)];
         }
         $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
         $lcIdRequ = $laFila['CNRORCT'];
         $laSuma = 0.00;
         $lnContad = 1;
         }
         $lnSubTot = $laFila['NMONSOL'] ;
         $laDatos[] = [fxNumber($lnContad, 3, 0) . ' ' . fxString($laFila['CDESTIP'], 9) . ' ' . fxString($laFila['CNROCOM'], 20) . ' ' . fxString($laFila['CCTACNT'], 12) . ' ' . $laFila['DFECHA'] . ' ' . fxString($laFila['CNRORUC'], 11) . ' ' . $laFila['CIDPAIS'] . '  ' . fxStringFixed($laFila['CGLOSA'], 33) . ' ' . fxNumber($laFila['NMONSOL'], 10, 2) . '     ' . fxNumber($laFila['NMONORI'], 10, 2) . ' ' . fxNumber($laFila['NMONRET'], 10, 2) . ' ' . fxNumber($lnSubTot, 10, 2)];        $lnContad += 1;
         $lnSuma += $lnSubTot;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['* TOTAL                                                                                                                                         ' . fxNumber($lnSuma, 13, 2)];
      $lnFont = 8;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 8);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            /*
               UCSM-ERP                                         RENDICION DE RECIBOS POR HONORARIOS - ALIVIARI                                                    PAG: 00000
               ERP4120                                                                                                                                     FECHA: 0000-00-00
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
               * EMPLEADO       : 000 - NOMBRE DEL EMPLEADO
               * ALUMNO         : 0000000000 - NOMBRE DEL ALUMNO
               * CENTRO DE COSTO: 000 - DESCRIPCION DEL CENTRO DE COSTO          ESTADO         : ESTADO DE LA RENDICION DE CUENTA
               * FECHA          : 0000-00-00
               * DESCRIPCION    : 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
               * OBSERVACIONES  : 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
                  #  TIPO      DOCUMENTO            OPERACION            FECHA      NRO.RUC     PAIS GLOSA                       MONTO S/  M.MONEDA ORI.   IMPUESTO      TOTAL
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
               000 000000000 00000000000000000000 00000000000000000000 0000-00-00 00000000000 000  0000000000000000000000000 000,000.00     000,000.00 000,000.00 000,000.00
               -------------------------------------------------------------------------------------------------------------------------------------------------------------
               TOTAL                                                                                                                                           00,000,000.00
            */
            $loPdf->ezText('<b>UCSM-ERP                                               REQUERIMIENTO DIRECTO A CONTABILIDAD                                                        PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ERP4120                                                                                                                                     FECHA: ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('', $lnFont,  array('justification' => 'centre'));
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b> #  TIPO      DOCUMENTO            OPERACION    FECHA      NRO.RUC     PAIS GLOSA                               MONTO S/  M.MONEDA ORI.   IMPUESTO      TOTAL</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 56) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }
   
   // Reporte Solicitud de Cotización
   // JLF - Creacion - 2018-11-06
   public function omRepSolicitudCotizacion() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepSolicitudCotizacion($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepSolicitudCotizacion($loSql);
      return $llOk;
   }

   protected function mxRepSolicitudCotizacion($p_oSql) {
      $lcCodigo = $this->paData['CCODIGO'];
      $lcSql = "SELECT DISTINCT A.cCodigo, B.cIdCoti, A.cNroRuc, D.cRazSoc, A.cEmail, A.cNroCel, A.cDetEnt, A.cTipPre, 
                       H.cDescri AS cDesTPr, A.cTipFPa, I.cDescri AS cDesFPa, A.cTieVal, B.cLugar, F.cMoneda, J.cDescri 
                       AS cDesMon, TRIM(J.cDesCor) AS cSimMon, C.nSerial, C.cCodArt, G.cDescri AS cDesArt, G.cUnidad,
                       K.cDescri AS cDesUni, C.nCantid, C.nPrecio, (C.nCantid * C.nPrecio)::NUMERIC(14,4) AS nSubTot,
                       C.cMarca, C.mObserv, A.cForPag, C.cDescri, A.cDniVen, A.cNomVen, A.mObserv AS mObsCot
                FROM E01PCOT A
                INNER JOIN E01MCOT B ON B.cIdCoti = A.cIdCoti
                INNER JOIN E01DCOT C ON C.cCodigo = A.cCodigo
                INNER JOIN S01MPRV D ON D.cNroRuc = A.cNroRuc
                INNER JOIN E01PREQ E ON E.cIdCoti = B.cIdCoti
                INNER JOIN E01MREQ F ON F.cIdRequ = E.cIdRequ
                INNER JOIN E01MART G ON G.cCodArt = C.cCodArt
                LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '096' AND H.cCodigo = A.cTipPre
                LEFT OUTER JOIN V_S01TTAB I ON I.cCodTab = '097' AND I.cCodigo = A.cTipFPa
                LEFT OUTER JOIN V_S01TTAB J ON J.cCodTab = '007' AND J.cCodigo = F.cMoneda
                LEFT OUTER JOIN V_S01TTAB K ON K.cCodTab = '074' AND K.cCodigo = G.cUnidad
                WHERE A.cCodigo = '$lcCodigo' ORDER BY C.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODIGO' => $laTmp[0], 'CIDCOTI' => $laTmp[1], 'CNRORUC' => $laTmp[2], 'CRAZSOC' => $laTmp[3], 
                             'CEMAIL'  => $laTmp[4], 'CNROCEL' => $laTmp[5], 'CDETENT' => $laTmp[6], 'CTIPPRE' => $laTmp[7], 
                             'CDESTPR' => $laTmp[8], 'CTIPFPA' => $laTmp[9], 'CDESFPA' => $laTmp[10],'CTIEVAL' => $laTmp[11],
                             'CLUGAR'  => $laTmp[12],'CMONEDA' => $laTmp[13],'CDESMON' => $laTmp[14],'CSIMMON' => $laTmp[15],
                             'NSERIAL' => $laTmp[16],'CCODART' => $laTmp[17],'CDESART' => $laTmp[18],'CUNIDAD' => $laTmp[19],
                             'CDESUNI' => $laTmp[20],'NCANTID' => $laTmp[21],'NPRECIO' => $laTmp[22],'NSUBTOT' => $laTmp[23],
                             'CMARCA'  => $laTmp[24],'MOBSERV' => $laTmp[25],'CFORPAG' => $laTmp[26],'CDESCRI' => $laTmp[27],
                             'CDNIVEN' => $laTmp[28],'CNOMVEN' => $laTmp[29],'MOBSCOT' => $laTmp[30]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepSolicitudCotizacion($p_oSql) {
      /*
                                                                                          CÓDIGO :   F-LGA-03
         UCSM-ERP                                                                         VERSIÓN:        1.0
                                 RENDICION DE RECIBOS POR HONORARIOS - ALIVIARI                              
                                                                                          FECHA  : 25.10.2017
         OF. LOGISTICA                                                                    PAG    :          1
         ----------------------------------------------------------------------------------------------------
         * RUC               : 10296867531                                  EMAIL   : MESTAS.MANUEL@GMAIL.COM
         * RAZON SOCIAL      : AUCAPURI MESTAS MANUEL JESUS                 CELULAR :               986768664
         * TIEMPO DE ENTREGA : A 03 DIAS ENVIADA LA ORDEN                   MONEDA  :                   SOLES
         * TIPO DE PRECIO    : COMPRA TODO COSTO PRECIO LOCAL (INCLUYE IMP./ENTREGA EN LUGAR SEÑALADO/OTROS) 
         * FORMA DE PAGO     : CREDITO 15 A 30 DIAS RECIBIDA CONFORMIDAD DE FACTURA.
         * TIEMPO DE VALIDEZ : 15 DIAS HABILES                              ID UNICO: 
         * LUGAR DE ENTREGA  : ALMACEN GENERAL 
         ---DETALLE------------------------------------------------------------------------------------------
          #  CODIGO   DESCRIPCION                        UNIDAD       CANTIDAD         PRECIO          TOTAL MARCA            OBSERVACION                             
         -------------------------------------------------------------------------------------------------------------------------------------------------------------
         000 01234567 0123456789012345678901234567890123 012345 999999999.9999 999999999.9999 999999999.9999 012345678901234  0123456789012345678901234567890123456789
         -------------------------------------------------------------------------------------------------------------------------------------------------------------
      */
      $lcIdRequ = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $i = 0;
      $lnTotal = 0;
      foreach ($this->paDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         $this->paDatos[$i] = $laFila;
         $laDatos[] = ['CLINEA' => fxNumber($lnContad, 3, 0) . ' ' . fxString($laFila['CCODART'], 8) . ' ' . fxString($laFila['CDESART'], 34) . ' ' . fxString($laFila['CUNIDAD'], 6) . ' ' . fxNumber($laFila['NCANTID'], 14, 4) . ' ' . fxNumber($laFila['NPRECIO'], 14, 6) . ' ' . fxNumber($laFila['NSUBTOT'], 14, 4) . ' ' . fxString($laFila['CMARCA'], 15) . '  ' . fxString($laFila['MOBSERV'], 40), 'NCANTID' => $lnContad];
         $lnTotal += $laFila['NSUBTOT'];
         $lcDescri = $laFila['CDESCRI'];
         $lmObserv = trim(fxStringTail($laFila['MOBSERV'], 40));
         do {
            if ($lcDescri == '' && $lmObserv == '') break;
            $laDatos[] = ['CLINEA' => '             ' . fxString($lcDescri, 34) . fxString('', 70) . fxString($lmObserv, 40), 'NCANTID' => $lnContad];
            $lcDescri = trim(fxStringTail($lcDescri, 34));
            $lmObserv = trim(fxStringTail($lmObserv, 40));
         } while(true);
         $lnContad += 1;
         $i++;
      }
      $loPdf = new FPDF('landscape','cm','A4');
      $loPdf->SetMargins(1.5, 1, 1.5);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $lnWidth = 0;
      $lnHeight = 0.38;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->SetFont('Courier', 'B' , 7);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('                                                                                                                                                                 CÓDIGO :  F-LGA-03'), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP                                                                                                                                                         VERSIÓN:       1.0'), 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 14);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SOLICITUD DE COTIZACIÓN DE MATERIAL TANGIBLE Y/O BIEN'), 0, 2, 'C');
            $loPdf->SetFont('Courier', 'B' , 7);
            $loPdf->Cell($lnWidth, $lnHeight, 'FECHA  :' . $ldDate, 0, 2, 'R');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('OF. LOGÍSTICA                                                                                                                                                    PÁG    :       ').fxNumber($lnPag, 3, 0), 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 11);
            $loPdf->Cell($lnWidth, $lnHeight, '------------------------------------------------------------------------------------------------------------------', 0, 2, 'L');
            /*$loPdf->Cell($lnWidth, $lnHeight, 'RUC               : '.fxString($this->paDatos[0]['CNRORUC'], 54).' EMAIL   : '.fxString($this->paDatos[0]['CEMAIL'], 27), 0, 2, 'L');*/
            $loPdf->Cell($lnWidth, $lnHeight, 'RUC               : '.fxString($this->paDatos[0]['CNRORUC'], 54), 0, 2, 'L');
            /*$loPdf->Cell($lnWidth, $lnHeight,  utf8_decode('RAZÓN SOCIAL      : ').fxString($this->paDatos[0]['CRAZSOC'], 54).' CELULAR : '.fxString($this->paDatos[0]['CNROCEL'], 27), 0, 2, 'L');*/
            $loPdf->Cell($lnWidth, $lnHeight,  utf8_decode('RAZÓN SOCIAL      : ').fxString($this->paDatos[0]['CRAZSOC'], 84), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'CORREO ELECTRONICO: '.fxString($this->paDatos[0]['CEMAIL'], 54).' CELULAR : '.fxString($this->paDatos[0]['CNROCEL'], 27), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'TIEMPO DE ENTREGA : '.fxString($this->paDatos[0]['CDETENT'], 54).' MONEDA  : '.fxString($this->paDatos[0]['CDESMON'], 27), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'TIPO DE PRECIO    : '.fxString($this->paDatos[0]['CDESTPR'], 92), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'FORMA DE PAGO     : '.fxString($this->paDatos[0]['CFORPAG'], 92), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'TIEMPO DE VALIDEZ : '.fxString($this->paDatos[0]['CTIEVAL'], 54).' ID UNICO: '.fxString($this->paDatos[0]['CCODIGO'], 27), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'LUGAR DE ENTREGA  : '.fxString($this->paDatos[0]['CLUGAR'], 92), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '---DETALLE--------------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode(' #  CODIGO   DESCRIPCIÓN                        UNIDAD       CANTIDAD         PRECIO          TOTAL MARCA            OBSERVACION                             '), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '-------------------------------------------------------------------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->SetFont('Courier', '' , 8);
         $loPdf->Cell($lnWidth, $lnHeight, fxString($laFila['CLINEA'], 157), 0, 2, 'L');
         $lnRow++;
         if ($lnRow == 28 && count($laDatos) != (28*$lnPag)) {
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, '-------------------------------------------------------------------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->SetFont('Courier', 'BI' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, '* VAN ...                      ' . fxNumber($laFila['NCANTID'], 4, 0) . ' items', 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('* La presente cotización tiene ') . fxNumber(count($this->paDatos), 4, 0) . ' items', 0, 2, 'L');
            $llTitulo = true;
         } else {
            $llTitulo = false;
         }
      }
      $loPdf->SetFont('Courier', 'B' , 8);
      $loPdf->Cell($lnWidth, $lnHeight, '-------------------------------------------------------------------------------------------------------------------------------------------------------------', 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight + 0.1, utf8_decode('TOTAL SOLICITUD DE COTIZACIÓN                                                    '). fxString($this->paDatos[0]['CSIMMON'], 3) . ' ' . fxNumber($lnTotal, 14, 4), 0, 2, 'L');
      $loPdf->SetFont('Courier', 'BI' , 8);
      $loPdf->Cell(3.8, $lnHeight, 'DNI DEL VENDEDOR   : ', 0, 0, 'L');
      $loPdf->SetFont('Courier', 'I' , 8);
      $loPdf->Cell($lnWidth, $lnHeight, fxString($this->paDatos[0]['CDNIVEN'], 136), 0, 1, 'L');
      $loPdf->SetFont('Courier', 'BI' , 8);
      $loPdf->Cell(3.8, $lnHeight, 'NOMBRE DEL VENDEDOR: ', 0, 0, 'L');
      $loPdf->SetFont('Courier', 'I' , 8);
      $loPdf->Cell($lnWidth, $lnHeight, fxString($this->paDatos[0]['CNOMVEN'], 136), 0, 1, 'L');
      if ($this->paDatos[0]['MOBSCOT'] != null) {
         $loPdf->SetFont('Courier', 'BI' , 8);
         $loPdf->Cell(3.8, $lnHeight, utf8_decode('OBSERVACIÓN        : '), 0, 0, 'L');
         $loPdf->SetFont('Courier', 'I' , 8);
         $loPdf->Cell($lnWidth, $lnHeight, fxString($this->paDatos[0]['MOBSCOT'], 136), 0, 1, 'L');
         $lmObserv = trim(fxStringTail($this->paDatos[0]['MOBSCOT'], 136));
         do {
            if ($lmObserv == '') break;
            $loPdf->SetFont('Courier', 'I' , 8);
            $loPdf->Cell($lnWidth, $lnHeight, '                      '.fxString($lmObserv, 136), 0, 2, 'L');
            $lmObserv = trim(fxStringTail($lmObserv, 136));
         } while(true);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   // IMPRIME REPORTE DE PROVEEDORES REGISTRADOS POR RUBRO 
   // 2018-11-12 JLF Creación
   public function omReporteProveedoresxRubro() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteProveedoresxRubro($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteProveedoresxRubro($loSql);
      return $llOk;
   }

   protected function mxReporteProveedoresxRubro($p_oSql) {
      $lcTipRub = $this->paData['CTIPRUB'];
      $lcRubro  = $this->paData['CRUBRO'];
      $lcInscri = $this->paData['CINSCRI'];
      $lcSql = "SELECT A.cNroRuc, A.cRazSoc, A.cTipBc1, D.cDescri AS cDesTp1, A.cCtaBc1, A.cTipBc2, E.cDescri AS cDesTp2, A.cCtaBc2, 
                       A.cNroCel, A.cEmail, A.cRegPrv, A.cRecPag, A.dFecPag, A.cRepLeg, A.cCodAnt, A.cSexo, F.cDescri AS cDesSex, 
                       A.cTipPer, G.cDescri AS cDesTPe, EXTRACT(YEAR FROM AGE(dFecPag)) AS nDifAni, EXTRACT(MONTH FROM AGE(dFecPag)) 
                       AS nDifMes, EXTRACT(DAY FROM AGE(dFecPag)) as nDifDia, B.cRubro, C.cDescri AS cDesRub, C.cTipRub, H.cDescri 
                       AS cDesTRu
                FROM S01MPRV A
                INNER JOIN S01DRUB B ON B.cNroRuc = A.cNroRuc
                INNER JOIN S01TRUB C ON C.cRubro = B.cRubro
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '088' AND D.cCodigo = A.cTipBc1
                LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '088' AND E.cCodigo = A.cTipBc2
                LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '003' AND F.cCodigo = A.cSexo
                LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '111' AND G.cCodigo = A.cTipPer
                LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '109' AND H.cCodigo = C.cTipRub";
      if ($lcInscri == 'S') {
         $lcSql .= " WHERE A.cEstado != 'X' AND EXTRACT(year FROM age(A.dFecPag)) < 10 AND CHAR_LENGTH(TRIM(A.cRecPag)) = 11 AND CHAR_LENGTH(TRIM(A.cRegPrv)) = 9";
      } elseif ($lcTipRub != 'TODOS' || $lcRubro != 'TODOS') {
         $lcSql .= " WHERE A.cEstado != 'X'";
      }
      if ($lcTipRub == 'TODOS' && $lcRubro != 'TODOS') {
         $lcSql .= " AND B.cRubro = '$lcRubro'";
      } elseif ($lcTipRub != 'TODOS' && $lcRubro == 'TODOS') {
         $lcSql .= " AND C.cTipRub = '$lcTipRub'";
      } elseif ($lcTipRub != 'TODOS' && $lcRubro != 'TODOS') {
         $lcSql .= " AND B.cRubro = '$lcRubro' AND C.cTipRub = '$lcTipRub'";
      }
      $lcSql .= " ORDER BY C.cTipRub, C.cRubro, nDifAni, A.dFecPag, A.cRazSoc";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CNRORUC' => $laFila[0], 'CRAZSOC' => $laFila[1], 'CTIPBC1' => $laFila[2], 'CDESTP1' => $laFila[3],
                             'CCTABC1' => $laFila[4], 'CTIPBC2' => $laFila[5], 'CDESTP2' => $laFila[6], 'CCTABC2' => $laFila[7],
                             'CNROCEL' => $laFila[8], 'CEMAIL'  => $laFila[9], 'CREGPRV' => $laFila[10],'CRECPAG' => $laFila[11],
                             'DFECPAG' => $laFila[12],'CREPLEG' => $laFila[13],'CCODANT' => $laFila[14],'CSEXO'   => $laFila[15],
                             'CDESSEX' => $laFila[16],'CTIPPER' => $laFila[17],'CDESTPE' => $laFila[18],'NDIFANI' => $laFila[19],
                             'NDIFMES' => $laFila[20],'NDIFDIA' => $laFila[21],'CRUBRO'  => $laFila[22],'CDESRUB' => $laFila[23],
                             'CTIPRUB' => $laFila[24],'CDESTRU' => $laFila[25]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintReporteProveedoresxRubro($p_oSql) {
      // Detalle del reporte
      $lcTipRub = '';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      foreach ($this->laDatos as $laFila) {
         if ($lcTipRub != $laFila['CTIPRUB']) {
            $lcTipRub = $laFila['CTIPRUB'];
            $laDatos[] = ['<b>* ' . $lcTipRub . ' ' . $laFila['CDESTRU'] . fxString('', 50) . '</b>'];
            $lcRubro = $laFila['CRUBRO'];
            $laDatos[] = ['<b> * ' . $lcRubro . ' ' . $laFila['CDESRUB'] . fxString('', 50) . '</b>'];
         }
         if ($lcRubro != $laFila['CRUBRO']) {
            $lcRubro = $laFila['CRUBRO'];
            $laDatos[] = ['<b> * ' . $lcRubro . ' ' . $laFila['CDESRUB'] . fxString('', 50) . '</b>'];
         }
         $laDatos[] = [fxNumber($lnContad, 4, 0).' '.$laFila['CNRORUC'].' '.fxStringFixed($laFila['CRAZSOC'], 45).' '.fxStringFixed($laFila['CCODANT'], 10).' '.fxString($laFila['CTIPBC1'], 2).' '.fxString($laFila['CCTABC1'], 24).' '.fxString($laFila['CTIPBC2'], 2).' '.fxString($laFila['CCTABC2'], 24).'  '.fxNumber($laFila['NDIFANI'], 2, 0).' AÑOS '.fxNumber($laFila['NDIFMES'], 2, 0).' MESES '.fxNumber($laFila['NDIFDIA'], 2, 0).' DÍAS  '];
         $lnContad += 1;
      }
      $lnFont = 8;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1.5, 1.5);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText(utf8_encode('<b>UCSM-ERP  '.fxStringCenter('OFICINA DE LOGÍSTICA', 119).' PAG: ' . fxNumber($lnPag, 5, 0) . '</b>'), 9);
            $loPdf->ezText(utf8_encode('<b>ERP2360   '.fxStringCenter('REPORTE DE PROVEEDORES INSCRITOS POR RUBRO', 119). ' ' . $ldDate . '</b>'), 9);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText(utf8_encode('<b>  #      RUC     RAZÓN SOCIAL                                  CÓDIGO           CUENTA EN SOLES            CUENTA EN DÓLARES      TIEMPO DESDE SU INSCRIPCIÓN</b>'), $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText(utf8_encode('<b>' . $laFila[0] . '</b>'), $lnFont);
         } else {
            $loPdf->ezText(utf8_encode($laFila[0]), $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 55) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME CARGO DE ORDENES ENVIADAS A VICE ADMINISTRATIVO
   // 2019-02-27 JLF Creación
   public function omReporteOrdenesEnviadasViceAdm() {
      $llOk = $this->mxPrintReporteOrdenesEnviadasViceAdm();
      return $llOk;
   }

   protected function mxPrintReporteOrdenesEnviadasViceAdm() {
      /*
      UCSMERP                         UNIVERSIDAD CATÓLICA DE SANTA MARIA                      PAG.:99999
                 OFICINA DE LOGÍSTICA Y CONTRATACIONES - CARGO DE ORDENES DE COMPRA ENTREGADAS           
      Rep9999                                                                                  2019-02-21
      ---------------------------------------------------------------------------------------------------
       #  ORDEN      PROVEEDOR                                        IMPORTE       S/D FECHA    FIRMA    
      ---------------------------------------------------------------------------------------------------
      ---------------------------------------------------------------------------------------------------
      999 OC00044479 A2275 - ASCENSORES Y SERVICIOS PERU SAC              46,728.00 S/. 19-02-21 ________
      999 9999999999 012345678901234567890123456789012345678901234567 99,999,999.00 AAA 19-02-04 ________
      */
      $this->pcFile = 'FILES/R' . rand() . '.pdf';
      $laSuma = 0.00;
      $lcCtaCaj = '*';
      $ldDate = date("Y-m-d"); //FECHA GENERAL DE LA EMISIÓN DEL REPORTE  FORMATO YYYY-MM-DD
      $ldFecha = date("y-m-d"); // FECHA EN LA CUAL SE ENTREGARÁ LA ORDEN DE COMPRA FORMATO YY-MM-DD
      $lnContad = 1;
      foreach ($this->paDatos as $laFila) {
         $lcMoneda = ($laFila['CMONEDA']==='1')? 'S/.':'US$';
         $laDatos[] = [fxNumber($lnContad,3,0).' '.$laFila['CCODANT'].' '.fxString($laFila['CRAZSOC'],48).' '.fxNumber($laFila['NMONORD'],13,2).' '.$lcMoneda.' '.$ldFecha.' ________'];
         $lnContad += 1;
      }
      $lnFont = 9;
      $loPdf = new Cezpdf('A4');
      $loPdf->selectFont('fonts/Courier.afm');
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSMERP                         UNIVERSIDAD CATOLICA DE SANTA MARIA                      PAG.:'.fxNumber($lnPag,5,0).'</b>',$lnFont);
            $loPdf->ezText('<b>           OFICINA DE LOGISTICA Y CONTRATACIONES - CARGO DE ORDENES DE COMPRA ENTREGADAS           </b>',$lnFont);
            $loPdf->ezText('<b>Rep9999                                                                                  '.$ldDate.'</b>',$lnFont);
            $loPdf->ezText('<b>---------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b> #  ORDEN      PROVEEDOR                                        IMPORTE       S/D FECHA    FIRMA   </b>', $lnFont);
            $loPdf->ezText('<b>---------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont,['leading'=>15]);
         $lnRow++;
         $llTitulo = ($lnRow == 45) ? true : false;
      }
      $loPdf->ezText('<b>---------------------------------------------------------------------------------------------------</b>', $lnFont);
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME CARGO DE COMPROBANTES ENVIADOS A CONTABILIDAD
   // 2019-02-27 JLF Creación
   public function omReporteComprobantesEnviadosContabilidadPDF() {
      $llOk = $this->mxPrintReporteComprobantesEnviadosContabilidadPDF();
      return $llOk;
   }

   public function omReporteComprobantesEnviadosContabilidadExcel() {
      $llOk = $this->mxPrintReporteComprobantesEnviadosContabilidadExcel();
      return $llOk;
   }

   protected function mxPrintReporteComprobantesEnviadosContabilidadPDF() {
      /*
      UCSMERP                         UNIVERSIDAD CATÓLICA DE SANTA MARIA                      PAG.:99999
               OFICINA DE LOGISTICA Y CONTRATACIONES - CARGO DE COMPROBANTES DE PAGO ENTREGADOS          
      Rep9999                                                                                  2019-02-21
      ---------------------------------------------------------------------------------------------------
         # NRO COMPROBANTE      TIPO DE DOCUMENTO                     IMPORTE       S/D FECHA    FIRMA   
      ---------------------------------------------------------------------------------------------------
      * OC00044479 - 20600192605 ASCENSORES Y SERVICIOS PERU SAC - 46,728.00 SOLES
         1 0000-00225636073     BOLETOS DE TRANSPORTE AEREO           99,999,999.00 AAA 19-01-01 ________
        99 99999999999999999999 0123456789012345678901234567890123456 99,999,999.00 AAA 19-02-04 ________
      * OC00044479 - 20600192605 ASCENSORES Y SERVICIOS PERU SAC - 46,728.00 SOLES
         1 0000-00225636073     BOLETOS DE TRANSPORTE AEREO           99,999,999.00 AAA 19-01-01 ________
        99 99999999999999999999 0123456789012345678901234567890123456 99,999,999.00 AAA 19-02-04 ________
      */
      $this->pcFile = 'FILES/R' . rand() . '.pdf';
      $laSuma = 0.00;
      $lcCtaCaj = '*';
      $ldDate = date("Y-m-d"); //FECHA GENERAL DE LA EMISIÓN DEL REPORTE  FORMATO YYYY-MM-DD
      $ldFecha = date("y-m-d"); // FECHA EN LA CUAL SE ENTREGARÁ EL COMPROBANTE FORMATO YY-MM-DD
      $lcIdOrde = '*';
      $lnTotOrd = 0; //TOTAL DE ORDENES DE COMPRA
      $lnTotCom = 0; //TOTAL DE FACTURAS
      foreach ($this->paDatos as $laFila) {
         if ($lcIdOrde == '*' || $laFila['CIDORDE']!= $lcIdOrde) {
            $lcMoneda = ($laFila['CMONEDA']==='1')? 'S/.':'US$';
            $laDatos[] = ['<b>* '.$laFila['CCODANT'].' - '.$laFila['CNRORUC'].' '.trim($laFila['CRAZSOC']).' '.$lcMoneda.number_format($laFila['NMONORD'],2,'.',',').' '.trim($laFila['CDESMON']).'</b>'];
            $lcIdOrde = $laFila['CIDORDE'];
            $lnTotOrd += 1;
            $lnConCom = 1;
         }
         $lcMoneda = ($laFila['CMONEDA']==='1')? 'S/.':'US$';
         $laDatos[] = ['  '.fxNumber($lnConCom,2,0).' '.fxString(trim($laFila['CNROCOM']),20).' '.fxString($laFila['CTIPCOM'],37).' '.fxNumber($laFila['NMONTO'],13,2).' '.$lcMoneda.' '.$ldFecha.' ________'];
         $lnConCom += 1;
         $lnTotCom += 1;
      }
      $laDatos[] = [''];
      $laDatos[] = ['---------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['<b>TOTAL ORDENES DE COMPRA: </b>'.$lnTotOrd];
      $laDatos[] = ['<b>TOTAL COMPROBANTES DE PAGO: </b>'.$lnTotCom];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4');
      $loPdf->selectFont('fonts/Courier.afm');
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSMERP                         UNIVERSIDAD CATÓLICA DE SANTA MARIA                      PAG.:'.fxNumber($lnPag,5,0).'</b>',$lnFont);
            $loPdf->ezText('<b>         OFICINA DE LOGISTICA Y CONTRATACIONES - CARGO DE COMPROBANTES DE PAGO ENTREGADOS          </b>',$lnFont);
            $loPdf->ezText('<b>Rep2390                                                                                  '.$ldDate.'</b>',$lnFont);
            $loPdf->ezText('<b>---------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>   # NRO COMPROBANTE      TIPO DE DOCUMENTO                     IMPORTE       S/D FECHA    FIRMA   </b>', $lnFont);
            $loPdf->ezText('<b>---------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont,['leading'=>14]);
         //$loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 50) ? true : false;
      }
      $loPdf->ezText('<b>---------------------------------------------------------------------------------------------------</b>', $lnFont);
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   protected function mxPrintReporteComprobantesEnviadosContabilidadExcel() {
      $loXls = new CXls();
      $loXls->openXlsIO('Erp2390', 'R');
      $i = 3;
      $j = 0;
      foreach ($this->paDatos as $laFila) {
          $i++;
          $j++;
          $loXls->sendXls(0, 'A', $i, $j);
          $loXls->sendXls(0, 'B', $i, $laFila['CCODOLD']);
          $loXls->sendXls(0, 'C', $i, $laFila['CNRORUC']);
          $loXls->sendXls(0, 'D', $i, $laFila['CRAZSOC']);
          $loXls->sendXls(0, 'E', $i, $laFila['CNROCOM']);
          if ($laFila['CMONEDA'] == '1') {
            $loXls->sendXls(0, 'F', $i, 'S/.'.$laFila['NMONTO']);
          } else {
            $loXls->sendXls(0, 'G', $i, 'US$.'.$laFila['NMONTO']);
          }
          $loXls->sendXls(0, 'H', $i, $laFila['CCENCOS']);
          $loXls->sendXls(0, 'I', $i, $laFila['CIDREQU']);
          if ($laFila['CTIPO'] == 'B') {
            $loXls->sendXls(0, 'J', $i, $laFila['CCODANT']);
          } else {
            $loXls->sendXls(0, 'K', $i, $laFila['CCODANT']);
          }
          $loXls->sendXls(0, 'L', $i, $laFila['CCODIOL']);
          $loXls->sendXls(0, 'M', $i, $laFila['CTIPMOV'].' - '.$laFila['CNUMMOV']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // ----------------------------------------------------------
   // Busca centro de costo por descripcion
   // 2019-07-15 FPM Creación
   // ----------------------------------------------------------
   public function omBuscarCentroCosto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxBuscarCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxBuscarCentroCosto($p_oSql) {
      $lcDescri = strtoupper($this->paData['CTEXTO']);
      $llOk = false;
      $lcTexto = '%'.$lcDescri.'%';
      $lcSql = "SELECT cCenCos, cDescri FROM S01TCCO WHERE cDescri LIKE '$lcTexto' ORDER BY cDescri";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'CDESCRI' => $laFila[1]];
         $llOk = true;
      }
      if (!$llOk) {
         $this->pcError = 'NO HAY DATOS PARA MOSTRAR';
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------
   // Consulta actividades por centro de costo
   // 2019-07-15 FPM Creación
   // ----------------------------------------------------------
   public function omActividadesCentroCosto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxActividadesCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxActividadesCentroCosto($p_oSql) {
      $lcSql = "SELECT cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      //echo '<br>'.$lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0])) {
         $this->pcError = 'CENTRO DE COSTO NO EXISTE';
         return false;
      }
      $this->paData = ['CCENCOS' => $this->paData['CCENCOS'], 'CDESCRI' => $laFila[0]];
      $llOk = false;
      $lcSql = "SELECT A.t_cIdActi, B.cDescri, C.cDescri AS cDesAct, B.cAutFin, A.t_nPreApr, A.t_nPreAct, A.t_nMonEje, A.t_nMonCom, A.t_nMonReq, A.t_nSaldo
                FROM F_P02MACT_2('{$this->paData['CCENCOS']}') A
                INNER JOIN P02MACT B ON B.cIdActi = A.t_cIdActi
                LEFT JOIN V_S01TTAB C ON C.cCodTab = '052' AND C.cCodigo = B.cTipAct
                ORDER BY cDesAct, t_cIdActi";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDACTI' => $laFila[0], 'CDESCRI' => $laFila[1], 'CDESACT' => $laFila[2], 'CAUTFIN' => $laFila[3], 'NPREACT' => $laFila[5], 'NMONEJE' => $laFila[6], 'NMONCOM' => $laFila[7], 'NMONREQ' => $laFila[8], 'NSALDO' => $laFila[9]];
         $llOk = true;
      }
      if (!$llOk) {
         $this->pcError = 'NO HAY DATOS PARA MOSTRAR';
         return false;
      }
      return true;
   }
   
   // ----------------------------------------------------------
   // Consulta requerimientos por centro de costo
   // 2019-08-05 FPM Creación
   // ----------------------------------------------------------
   public function omRequerimientosCentroCosto() {
      $llOk = $this->mxValParamRequerimientosCentroCosto();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioCentroCosto($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRequerimientosCentroCosto($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValUsuarioCentroCosto($p_oSql) {
      $lcSql = "SELECT cEstado FROM S01TUSU WHERE cCodUsu = '{$this->paData['CUSUCOD']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $r = $p_oSql->fetch($R1);
      if (!isset($r[0])) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      } elseif ($r[0] != 'A') {
         $this->pcError = 'CODIGO DE USUARIO NO ACTIVO';
         return false;
      }
      //$lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cCenCos IN ('005', 'UNI')"; 
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos IN ('005', 'UNI') AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      $R1 = $p_oSql->omExec($lcSql);
      $r = $p_oSql->fetch($R1);
      if (isset($r[0]) and $r[0] == 'A') {
         return true;
      }
      //$lcSql = "SELECT cEstado FROM S01PCCO WHERE cCodUsu = '{$this->paData['CUSUCOD']}' AND cCenCos = '{$this->paData['CCENCOS']}'";
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      $R1 = $p_oSql->omExec($lcSql);
      $r = $p_oSql->fetch($R1);
      if (!isset($r[0])) {
         $this->pcError = 'USUARIO NO TIENE ASIGNADO EL CENTRO DE COSTO';
         return false;
      } elseif ($r[0] != 'A') {
         $this->pcError = 'USUARIO NO ESTA ACTIVO PARA CENTRO DE COSTO';
         return false;
      }
      return true;
   }

   protected function mxValParamRequerimientosCentroCosto() {
      if (!isset($this->paData['CUSUCOD']) or strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO VACIO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) or strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO VACIO O INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxRequerimientosCentroCosto($p_oSql) {
      $lcSql = "SELECT cDescri FROM S01TCCO WHERE cCenCos = '{$this->paData['CCENCOS']}'";
      //echo '<br>'.$lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0])) {
         $this->pcError = 'CENTRO DE COSTO NO EXISTE';
         return false;
      }
      $this->paData = ['CCENCOS'=> $this->paData['CCENCOS'], 'CDESCRI'=> $laFila[0]];
      $llOk = false;
      $lcSql = "SELECT A.cIdActi, A.cDescri, C.cDescri AS cEstado, A.cPeriod, A.nPreAct, TO_CHAR(A.tModifi, 'YYYY-MM-DD'), A.cUsuCod, B.cNombre FROM P02MACT A
                LEFT OUTER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cUsuCod
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '080' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstado
                WHERE A.cCenCos = '{$this->paData['CCENCOS']}' ORDER BY A.tModifi";
      $R1 = $p_oSql->omExec($lcSql);
      while ($r = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CIDACTI' => $r[0], 'CDESCRI' => $r[1], 'CESTADO' => $r[2], 'CPERIOD' => $r[3], 'NPREACT' => $r[4], 'DFECHA' => $r[5], 'CUSUCOD' => $r[6].' - '.str_replace('/', ' ', $r[7])];
         $llOk = true;
      }
      if (!$llOk) {
         $this->pcError = 'NO HAY DATOS PARA MOSTRAR';
         return false;
      }
      return true;
   }

   // ----------------------------------------------------------
   // Consulta requerimientos por actividades y centro de costo
   // 2019-08-05 FPM Creación
   // ----------------------------------------------------------
   public function omRequerimientosActividades() {
      $llOk = $this->mxValParamRequerimientosActividades();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValUsuarioCentroCosto($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxRequerimientosActividades($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRequerimientosActividades();
      return $llOk;
   }

   protected function mxValParamRequerimientosActividades() {
      if (!isset($this->paData['CUSUCOD']) or strlen($this->paData['CUSUCOD']) != 4) {
         $this->pcError = 'CODIGO DE USUARIO VACIO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) or strlen($this->paData['CCENCOS']) != 3) {
         $this->pcError = 'CENTRO DE COSTO VACIO O INVALIDO';
         return false;
      } elseif (!isset($this->paData['CIDACTI']) or strlen($this->paData['CIDACTI']) != 8) {
         $this->pcError = 'ID DE ACTIVIDAD VACIA O INVALIDA';
         return false;
      }
      return true;
   }

   protected function mxRequerimientosActividades($p_oSql) {
      $laData = $this->paData;
      $lcSql = "SELECT cDescri FROM S01TCCO WHERE cCenCos = '{$laData['CCENCOS']}'";
      //echo '<br>'.$lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      $r = $p_oSql->fetch($R1);
      if (!isset($r[0])) {
         $this->pcError = 'CENTRO DE COSTO NO EXISTE';
         return false;
      }
      $this->paData = ['CCENCOS'=> $laData['CCENCOS'].' - '.$r[0], 'CIDACTI'=> ''];
      $lcSql = "SELECT cDescri FROM P02MACT WHERE cIdActi = '{$laData['CIDACTI']}'";
      //echo '<br>'.$lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      $r = $p_oSql->fetch($R1);
      if (!isset($r[0])) {
         $this->pcError = 'ID DE ACTIVIDAD NO EXISTE';
         return false;
      }
      $this->paData['CIDACTI'] = $laData['CIDACTI'].' - '.$r[0];
      $llOk = false;
      $lcSql = "SELECT cIdRequ, cDesCco, cDesEst, cDesReq, cCodUsu, cNomUsu, cDesTip, SUBSTRING(tGenera, 1, 10), cEstado FROM V_E01MREQ_1 WHERE cIdActi = '{$laData['CIDACTI']}' ORDER BY cIdRequ";
      //echo '<br>'.$lcSql.'<br>';
      $R1 = $p_oSql->omExec($lcSql);
      while ($r = $p_oSql->fetch($R1)) {
         $lcSql = "SELECT cCodArt, cDesArt, cDesEst, cDesUni, nPreArt, nCantid, cDescri FROM V_E01DREQ_1 WHERE cIdRequ = '$r[0]' ORDER BY nSerial";
         //echo '<br>'.$lcSql.'<br>';
         $R2 = $p_oSql->omExec($lcSql);
         while ($r2= $p_oSql->fetch($R2)) {
            $llOk = true;
            $lcEstReq = fxCleanString($r2[2]);
            if ($r[8] == 'X') {
               $lcEstReq = fxCleanString($r[2]);
            }
            $this->laDatos[] = ['CIDREQU' => $r[0], 'CCENCOS' => fxCleanString($r[1]), 'CESTADO' => $r[2], 'CDESREQ' => fxCleanString($r[3]), 'CUSUCOD' => fxCleanString($r[4].' - '.str_replace('/', ' ', $r[5])), 
                                'CDESTIP' => fxCleanString($r[6]), 'DFECHA' => $r[7], 'CCODART'=> $r2[0], 'CESTREQ'=> $lcEstReq, 'CUNIDAD'=> $r2[3], 'NPRECIO'=> $r2[4],
                                'NCANTID' => $r2[5], 'CDESCRI' => fxCleanString($r2[6])];
         }
      }
      if (!$llOk) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRequerimientosActividades() {
      // Detalle del reporte
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF('landscape', 'cm', 'A4');
      $loPdf->SetMargins(1.3, 1.6, 1.3);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $i = 0;
      $lnHeight = 0.4;
      $llTitulo = true;
      $lcIdRequ = '*';
      foreach ($this->laDatos as $laTmp) {
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->SetFont('Courier', 'B' , 10);
            $loPdf->Cell(0, $lnHeight, utf8_decode('UCSM-ERP'.fxStringCenter('CONSULTA REQUERIMIENTOS POR ACTIVIDAD', 104).'PAG: ' . fxNumber($lnPag, 5, 0)), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, utf8_decode('REP9902'.str_repeat(' ', 105).$ldDate), 0, 2, 'L');
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell(0, $lnHeight, str_repeat('-', 153), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, utf8_decode('CENTRO DE COSTO: '.$this->paData['CCENCOS']), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, utf8_decode('ACTIVIDAD      : '.$this->paData['CIDACTI']), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, str_repeat('-', 153), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, utf8_decode(' #  ARTICULO ESTADO          UNIDAD          DESCRIPCION                                                                            CANTIDAD       PRECIO'), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, str_repeat('-', 153), 0, 2, 'L');
            $lnRow = 0;
         }
         if ($lcIdRequ != $laTmp['CIDREQU']) {
            $i = 0;
            $loPdf->SetFont('Courier', 'B' , 8);
            $loPdf->Cell(0, $lnHeight, utf8_decode('* ID.REQ.: '.$laTmp['CIDREQU'].' - '.fxString($laTmp['CDESREQ'], 150).' - '.$laTmp['CESTADO']), 0, 2, 'L');
            $loPdf->Cell(0, $lnHeight, utf8_decode('           '.$laTmp['CESTADO'].' - '.$laTmp['CUSUCOD'].' - '.$laTmp['CDESTIP'].' - '.$laTmp['DFECHA']), 0, 2, 'L');
            $lcIdRequ = $laTmp['CIDREQU'];
            $lnRow += 2;
         }
         $i++;
         $loPdf->SetFont('Courier', '' , 8);
         $loPdf->Cell(0, $lnHeight, utf8_decode(fxNumber($i, 3).' '.$laTmp['CCODART'].' '.fxString($laTmp['CESTREQ'], 15).' '.fxString($laTmp['CUNIDAD'], 15).' '.fxString($laTmp['CDESCRI'], 84).' '.fxNumber($laTmp['NCANTID'], 10, 4).' '.fxNumber($laTmp['NPRECIO']*$laTmp['NCANTID'], 12, 2)), 0, 2, 'L');
         $lnRow++;
         $llTitulo = ($lnRow > 32) ? true : false;
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }
   
   // IMPRIME CARGO DE NOTAS DE INGRESO DEL DIA
   // 2020-02-27 JLF Creación
   public function omReporteNotasIngresoDelDia() {
      $llOk = $this->mxValParamReporteNotasIngresoDelDia();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk1 = $this->mxValUsuarioLogistica($loSql);
      $llOk2 = $this->mxValUsuarioAlmacen($loSql);
      if (!$llOk1 || !$llOk2) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxReporteNotasIngresoDelDia($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteNotasIngresoDelDia();
      return $llOk;
   }

   protected function mxValParamReporteNotasIngresoDelDia() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CNIVEL']) || strlen(trim($this->paData['CNIVEL'])) != 2) {
         $this->pcError = 'NIVEL DE USUARIO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxReporteNotasIngresoDelDia($p_oSql) {
      //TRAER TODOS LOS COMPROBANTES ASOCIADOS A LA BUSQUEDA
      $lcSql = "SELECT A.cNotIng, A.dFecha, A.cGuiRem, A.cDescri, B.cIdKard, B.cTipMov, B.cNumMov, C.cIdOrde, C.cCodAnt, C.nMonto AS nMonOrd, 
                       C.cNroRuc, D.cRazSoc, E.cIdComp, E.cTipCom, E.cNroCom, E.nMonto, E.cMoneda, F.cDescri AS cDesMon, TRIM(F.cDesCor) AS cSimMon 
                  FROM E01MNIN A
                  INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                  INNER JOIN E01MORD C ON C.cIdOrde = A.cIdOrde
                  INNER JOIN S01MPRV D ON D.cNroRuc = C.cNroRuc
                  INNER JOIN E01MFAC E ON E.cIdComp = A.cIdComp
                  LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '007' AND SUBSTRING(F.cCodigo, 1, 1) = E.cMoneda
                  WHERE A.dFecha = NOW()::DATE AND A.cEstado != 'X'
                  ORDER BY C.cCodAnt, B.cNumMov";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY DATOS PARA INMPRIMIR";
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laNotIng[] = ['CNOTING' => $laFila[0], 'DFECHA'  => $laFila[1], 'CGUIREM' => $laFila[2], 'CDESCRI' => $laFila[3], 
                              'CIDKARD' => $laFila[4], 'CTIPMOV' => $laFila[5], 'CNUMMOV' => $laFila[6], 'CIDORDE' => $laFila[7], 
                              'CCODANT' => $laFila[8], 'NMONORD' => $laFila[9], 'CNRORUC' => $laFila[10],'CRAZSOC' => $laFila[11],
                              'CIDCOMP' => $laFila[12],'CTIPCOM' => $laFila[13],'CNROCOM' => $laFila[14],'NMONTO'  => $laFila[15],
                              'CMONEDA' => $laFila[16],'CDESMON' => $laFila[17],'CSIMMON' => $laFila[18]];
      }
      return true;
   }


   protected function mxPrintReporteNotasIngresoDelDia() {
      /*
      UCSMERP                         UNIVERSIDAD CATÓLICA DE SANTA MARIA                      PAG.:99999
                   OFICINA DE LOGÍSTICA Y CONTRATACIONES - CARGO DE NOTAS DE INGRESO DEL DÍA             
      Rep2410                                                                                  2019-02-21
      ---------------------------------------------------------------------------------------------------
        # MOVIMIENTO    GUIA DE REMISION     COMPROBANTE                   IMPORTE MON FECHA      FIRMA  
      ---------------------------------------------------------------------------------------------------
      OC00044479 - 20600192605 ASCENSORES Y SERVICIOS PERU SAC                       46,728.00 SOLES
      9999999999 - 99999999999 0123456789012345678901234567890123456789012345678 99,999,999.00 9999999999
        1 NI-2020000001 F001-00000012        01/F001-00000012        99,999,999.00 AAA 2019-01-01 _______
      999 9999999999999 01234567890123456789 01234567890123456789012 99,999,999.00 AAA 2019-02-04 _______
      OC00044479 - 20600192605 ASCENSORES Y SERVICIOS PERU SAC                       46,728.00 SOLES
      9999999999 - 99999999999 0123456789012345678901234567890123456789012345678 99,999,999.00 9999999999
        1 NI-2020000002 F001-00000014        01/F001-00000014        99,999,999.00 AAA 2019-01-01 _______
      999 9999999999999 01234567890123456789 01234567890123456789012 99,999,999.00 AAA 2019-02-04 _______
      */
      $laSuma = 0.00;
      $ldDate = date("Y-m-d");
      $lcIdOrde = '*';
      $lnTotOrd = 0; //TOTAL DE ORDENES DE COMPRA
      $lnTotIng = 0; //TOTAL DE NOTAS DE INGRESO
      $i = 0;
      $j = 0;
      foreach ($this->laNotIng as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         $this->laNotIng[$i] = $laFila;
         if ($laFila['CIDORDE'] != $lcIdOrde) {
            $laDatos[] = ['CLINEA' => $laFila['CCODANT'] . ' - ' . $laFila['CNRORUC'] . ' ' . fxString(trim($laFila['CRAZSOC']), 49) . ' ' . fxNumber($laFila['NMONORD'], 13, 2) . ' ' . fxString($laFila['CDESMON'], 10), 'CESTFUE' => 'B', 'NTAMFUE' => 9];
            $lcIdOrde = $laFila['CIDORDE'];
            $lnTotOrd += 1;
            $j = 0;
         }
         $laDatos[] = ['CLINEA' => fxNumber(++$j, 3, 0).' '.trim($laFila['CTIPMOV'].'-'.$laFila['CNUMMOV']).' '.fxString($laFila['CGUIREM'],20).' '.fxString($laFila['CTIPCOM'].'/'.$laFila['CNROCOM'],23).' '.fxNumber($laFila['NMONTO'],13,2).' '.fxString($laFila['CSIMMON'],3).' '.$ldDate.' _______', 'CESTFUE' => '', 'NTAMFUE' => 9];
         $lnTotIng += 1;
         $i++;
      }
      $loPdf = new FPDF('portrait','cm','A4');
      $loPdf->SetMargins(1.5, 1, 1.5);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $lnWidth = 0;
      $lnHeight = 0.38;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSMERP                         UNIVERSIDAD CATÓLICA DE SANTA MARIA                      PAG.:'.fxNumber($lnPag,5,0)), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('OFICINA DE LOGISTICA Y CONTRATACIONES - CARGO DE COMPROBANTES DE PAGO ENTREGADOS'), 0, 2, 'C');
            $loPdf->Cell($lnWidth, $lnHeight, 'Rep2410                                                                                  '.$ldDate, 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '---------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('  # MOVIMIENTO    GUIA DE REMISION     COMPROBANTE                   IMPORTE MON FECHA      FIRMA  '), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '---------------------------------------------------------------------------------------------------', 0, 2, 'L');
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->SetFont('Courier', $laFila['CESTFUE'] , $laFila['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, $laFila['CLINEA'], 0, 2, 'L');
         $lnRow++;
         if ($lnRow == 50 && count($laDatos) != (50*$lnPag)) {
            $llTitulo = true;
         } else {
            $llTitulo = false;
         }
      }
      $loPdf->SetFont('Courier', 'B' , 9);
      $loPdf->Cell($lnWidth, $lnHeight, '---------------------------------------------------------------------------------------------------', 0, 2, 'L');
      $loPdf->SetFont('Courier', 'BI' , 9);
      $loPdf->Cell($lnWidth, $lnHeight, 'TOTAL ORDENES DE COMPRA   : '.$lnTotOrd, 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, 'TOTAL COMPROBANTES DE PAGO: '.$lnTotIng, 0, 2, 'L');
      $loPdf->Output('F', $this->pcFile, true);
      return true;  
   }  

   protected function mxPrintMostrarActaTesisPreGrado() {  
      $laDatos[] = null;
      try {
         $loDate = new CDate;
         $fecha_actual = date ("Y-m-d H:i:s");
         $pdf = new FPDF();
         $pdf->SetAutoPageBreak(true, 5);
         $pdf->AddPage('L', array(160, 115));
         $pdf->SetFont('times', 'B', 10);
         $pdf->SetFillColor(232, 232, 232);
         $pdf->SetDrawColor(232, 232, 232);
         $pdf->Image('img/logo_ucsm.png', 70, 8, 20, 17);
         //$pdf->Image('img/proveedores.png', 0, 0, 200, 200);
         $pdf->Ln(17);
         $pdf->Cell(0, 5, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA - MESA DE PARTES'), 0, 2, 'C'); 
         $pdf->Ln(4);
         $pdf->SetFont('times', 'B', 9);
         $pdf->Cell(28, 4,'EXPEDIENTE:', 0, 0, 'L', 1);
         $pdf->Cell(15, 4,'E-000000', 0, 0, 'L', 0);
         $pdf->Ln(4.5);         
         $pdf->Cell(28, 4,'DNI:', 0, 0, 'L', 1);
         $pdf->Cell(15, 4,'70840304', 0, 0, 'L', 0);
         $pdf->Ln(4.5);         
         $pdf->Cell(28, 4,'NOMBRE:', 0, 0, 'L', 1);
         $pdf->Cell(55, 4,'PACHECO REVILLA ALEXANDER ANDREI', 0, 0, 'L', 0);
         $pdf->Ln(4.5);
         $pdf->Cell(28, 4,'TRAMITE:', 0, 0, 'L', 1);
         $pdf->Cell(55, 4,'CARTA DE PRESENTACION', 0, 0, 'L', 0);
         $pdf->Ln(4.5);
         $pdf->Cell(28, 4,'DEPENDENCIA:', 0, 0, 'L', 1);
         $pdf->Cell(55, 4,'OFICINA DE REGISTRO Y ARCHIVO ACADEMICO', 0, 0, 'L', 0);
         $pdf->Ln(4.5);
         $pdf->Cell(28, 4,'CELULAR:', 0, 0, 'L', 1);
         $pdf->Cell(55, 4,'987056331', 0, 0, 'L', 0);
         $pdf->Ln(4.5);
         $pdf->Cell(28, 4,'EMAIL:', 0, 0, 'L', 1);
         $pdf->Cell(55, 4,'askpacheco@gmail.com', 0, 0, 'L', 0);
         $pdf->Ln(4.5);
         $pdf->Cell(28, 4,'FECHA:', 0, 0, 'L', 1);
         $pdf->Cell(55, 4,$fecha_actual, 0, 0, 'L', 0);
         $pdf->Ln(8);
         $pdf->SetFont('times', 'B', 9);
         $pdf->Cell(0, 4, 'RECOJO DE EXPEDIENTE: ', 0, 2, 'L');
         $pdf->Ln(1);
         $pdf->Cell(0, 4, 'Se comunica a los usuarios que la entrega de certificados, constancias y/o', 0, 2, 'L');
         $pdf->Ln(0);
         $pdf->Cell(0, 4, 'cualquier otro documento solo se efectuara;', 0, 2, 'L');
         $pdf->Ln(0);
         $pdf->Cell(0, 4, '- Al titular del tramite.', 0, 2, 'L');
         $pdf->Ln(0);
         $pdf->Cell(0, 4, '- A terceras personas presentando carta poder con firma legalizada.', 0, 2, 'L');
         $pdf->Ln(7);
         $pdf->SetFont('times', 'B', 8);
         $pdf->Cell(0, 4, 'REGISTRADO: 40710950', 0, 2, 'L');
         $lcFile1 = 'FILES/R'.rand().'.png';
         $lcCodDec = 'UCSM - MESA DE PARTES - 2020-06-23';
         QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety = $pdf->GetY();             
         $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
         $pdf->Image($lcFile1, $pdf->GetPageWidth() - 40, 75, 30, 25, 'PNG');
         $pdf->Output('F', $this->pcFile, true);
      }  catch (Exception $e) {
            $this->pcError = 'ERROR AL GENERAR PDF';
            return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------------
   // Reporte constancias por bloque
   // 2019-03-14 MLC Creacion
   // ------------------------------------------------------------------------------
   public function omGenerarReporteConstanciasEnGrupo() {
      $llOk = $this->mxValParamGenerarConstanciasEnGrupo();
      if (!$llOk) {        
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxGenerarConstanciasEnGrupo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValParamGenerarConstanciasEnGrupo() {
      if (!isset($this->paData['paCodTre']) || count($this->paData['paCodTre']) == 0) {
         $this->pcError = "SIN TRÁMITES SELECCIONADOS";
         return false;
      }
      return true;
   }

   protected function mxGenerarConstanciasEnGrupo($p_oSql) {
      try {
         $fecha_actual = date ("Y-m-d");
         $pdf = new FPDF();
         $pdf->AddPage('P','A4');
         $pdf->SetFont('Courier', 'B', 10);
         $pdf->SetAutoPageBreak(true, 5);
      }
      catch (Exception $e) {
         $this->pcError = 'ERROR AL GENERAR PDF CON TUS DATOS';
         return false;
      }
      $i = 1;
      foreach ($this->paData['paCodTre'] as $lcCodTre) {
         $this->paData['pcCodTre'] = $lcCodTre;
         $llOk = $this->mxRecuperarDatosConstancias($p_oSql);
         if (!$llOk) {
            return false;
         }
         $this->mxAñadirPaginaReporteConstancias($pdf);
         $this->mxRevisarConstacias($p_oSql);
         if ($i != count($this->paData['paCodTre'])) {
            $pdf->AddPage();
         }
         $i++;
      }
      $pdf->Output('F', $this->pcFile);
      return true;
   }

   protected function mxRecuperarDatosConstancias($p_oSql) {
      $lcSql = "SELECT A.cCodTre, A.cIdCate, D.cDescri, A.cIdLog, TO_CHAR(A.tFecha, 'YYYY-MM-DD HH24:MI'), A.mDetall, A.cEstado,
                       B.cIdDeud, B.cCodAlu, E.cNombre, C.cNroDni, C.cNroPag, C.nMonto, E.cNroCel, E.cEmail, B.cRecibo, E.cNomUni,
                       E.cNivel, E.cDesNiv, C.cPaquet, TO_CHAR(F.tUltima, 'YYYY-MM-DD HH24:MI')
                FROM B04MTRE A
                INNER JOIN B03DDEU B ON B.cIdLog = A.cIdLog
                INNER JOIN B03MDEU C ON C.cIdDeud = B.cIdDeud
                INNER JOIN B03TDOC D ON D.cIdCate = A.cIdCate
                LEFT OUTER JOIN V_A01MALU E ON E.cCodAlu = B.cCodAlu
                LEFT OUTER JOIN B04DTRE F ON F.cCodTre = A.cCodTre  
                WHERE A.cCodTre = '{$this->paData['pcCodTre']}'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      if (!isset($laFila[0])) {
         $this->pcError = "SIN DETALLE DE TRAMITE";
         return false;
      }
      $this->paDatos = ['CCODTRE' => $laFila[0], 'CIDCATE' => $laFila[1], 'CDESCRI' => $laFila[2], 'CIDLOG'  => $laFila[3], 
                        'TFECHA'  => $laFila[4], 'MDETALL' => json_decode($laFila[5], true), 'CESTADO' => $laFila[6], 
                        'CIDDEUD' => $laFila[7], 'CCODALU' => $laFila[8], 'CNOMBRE' => $laFila[9], 'CNRODNI' => $laFila[10],
                        'CNROPAG' => $laFila[11],'NMONTO'  => $laFila[12],'CNROCEL' => $laFila[13],'CEMAIL'  => $laFila[14],
                        'CRECIBO' => $laFila[15],'CNOMUNI' => $laFila[16],'CNIVEL'  => $laFila[17],'CDESNIV' => $laFila[18],
                        'CPAQUET' => $laFila[19],'TULTIMA' => $laFila[20]];
      return true;
   }

   protected function mxAñadirPaginaReporteConstancias(&$p_pdf) {
      $fecha_actual = date ("Y-m-d");
      $lcUltima = $this->paDatos['TULTIMA'];
      if ($this->paDatos['CPAQUET'] == 'B') {
         $lcPaquet = 'BACHILLER';
      }
      if ($this->paDatos['TULTIMA'] == NULL) {
         $lcUltima = 'SIN REGISTRO';
      }
      $p_pdf->SetFont('Courier', 'B', 10);
      $p_pdf->SetAutoPageBreak(true, 5);
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('UCSM-ERP'), 0, 0, 'L');
      $p_pdf->Cell(0, 0, utf8_decode($fecha_actual), 0, 0, 'R');
      $p_pdf->Ln(10);
      $p_pdf->SetFont('Courier', 'B', 14);
      $p_pdf->Cell(0, 0, utf8_decode(str_repeat(' ', 20).'REPORTE DE CONSTANCIAS'), 0, 0, 'L');
      $p_pdf->SetFont('Courier', 'B', 12);
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode(str_repeat('-',75)), 0, 0, 'L');
      //$p_pdf->Ln(5);
      //$p_pdf->Cell(0, 0, utf8_decode('DATOS DEL ALUMNO'), 0, 0, 'L');
      $p_pdf->SetFont('Courier', '', 11);
      //$p_pdf->Ln(5); 
      //$p_pdf->Cell(0, 0, utf8_decode(str_repeat('-',80)), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Expediente                :  '.'E-'.$this->paDatos['CCODTRE']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Tipo Constancia           :  '.$this->paDatos['CDESCRI']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Fecha Recepción           :  '.$this->paDatos['TFECHA']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Código de Pago            :  '.$this->paDatos['CNROPAG']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Recibo de Pago            :  '.$this->paDatos['CRECIBO']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Monto                     :  '.$this->paDatos['NMONTO']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('DNI del Alumno            :  '.$this->paDatos['CNRODNI']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Nombre del Alumno         :  '.$this->paDatos['CNOMBRE']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Código del Alumno         :  '.$this->paDatos['CCODALU']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->SetY(137);
      $p_pdf->Multicell(189, 5, utf8_decode('Unidad Académica          :  '.$this->paDatos['CNOMUNI']), 0, 'J');
      $p_pdf->Ln(7);
      $p_pdf->Cell(0, 0, utf8_decode('Nivel de Unidad           :  '.$this->paDatos['CDESNIV']), 0, 0, 'L');
      $p_pdf->Ln(10);
      $p_pdf->Cell(0, 0, utf8_decode('Telefono                  :  '.$this->paDatos['CNROCEL']), 0, 0, 'L');
      $p_pdf->Ln(10);
      if (in_array($this->paDatos['CIDCATE'], ['000003','000009','000010','000026'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Semestre Actual           :  '.substr($this->paDatos['MDETALL']['CSEMACT'], 0, -1).'º Semestre'), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000004','000008'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Semestres Solicitados     :  '.join(' - ', $this->paDatos['MDETALL']['MSEMEST'])), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000007','000011','000012','000013','000014','000015'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Año Egreso                :  '.$this->paDatos['MDETALL']['CANOEGR']), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['0000022'])) {
         $p_pdf->Cell(0, 0, utf8_decode('País                      :  '.$this->paDatos['MDETALL']['CPAIS']), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000024','000025'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Grado Académico           :  '.$this->paDatos['MDETALL']['CGRAACA']), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000020'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Fecha revisión de Unidad  :  '.$lcUltima), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Promedio Ponderado        :  '.$this->paDatos['MDETALL']['NPROMED']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Nº Créditos Aprobados     :  '.$this->paDatos['MDETALL']['NCREAPR']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Puesto Ocupado            :  '.$this->paDatos['MDETALL']['NPUESOC']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Total de Alumnos          :  '.$this->paDatos['MDETALL']['NTOTALU']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Año de Ranking            :  '.$this->paDatos['MDETALL']['NRANKIN']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Fecha de Egreso:          :  '.$this->paDatos['MDETALL']['DFECHAR']), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000017'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Fecha revisión de Unidad  :  '.$lcUltima), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Constancia                :  '.$lcPaquet), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000018'])) {
         $p_pdf->Cell(0, 0, utf8_decode('Fecha revisión de Unidad  :  '.$lcUltima), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Título Profesional        :  '.$this->paDatos['MDETALL']['CTITPRO']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->SetY(175);
         $p_pdf->Multicell(189, 5, utf8_decode('Trabajo de Investigación :').utf8_decode($this->paDatos['MDETALL']['CTITINV']), 0, 'J');
         $p_pdf->Ln(7);
         $p_pdf->Cell(0, 0, utf8_decode('Modalidad                 :  '.$this->paDatos['MDETALL']['CMODALI']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Tomo                      :  '.$this->paDatos['MDETALL']['CNROTOM']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Nº de Folio               :  '.$this->paDatos['MDETALL']['NROFOLI']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Fecha de Titulación:      :  '.$this->paDatos['MDETALL']['DFECHAR']), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      if (in_array($this->paDatos['CIDCATE'], ['000001']) AND isset($this->paDatos['MDETALL']['NPUNTAJ']) ) {
         $p_pdf->Cell(0, 0, utf8_decode('Fecha revisión de Unidad  :  '.$lcUltima), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Modalidad de ingreso      :  '.$this->paDatos['MDETALL']['CTIPING']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Año de Admisión           :  '.$this->paDatos['MDETALL']['CANOING']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Modalidad                 :  '.$this->paDatos['MDETALL']['NEXAM']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Orden de Mérito Nº:       :  '.$this->paDatos['MDETALL']['CPUESTO']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Puntaje de Alumno:        :  '.$this->paDatos['MDETALL']['NPUNTAJ']), 0, 0, 'L');
         $p_pdf->Ln(10);
      } 
      if (in_array($this->paDatos['CIDCATE'], ['000001']) AND isset($this->paDatos['MDETALL']['NNUMRES']) ) {
         $p_pdf->Cell(0, 0, utf8_decode('Fecha revisión de Unidad  :  '.$lcUltima), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Modalidad de ingreso      :  '.$this->paDatos['MDETALL']['CMODALI']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Año de admisión           :  '.$this->paDatos['MDETALL']['CADMISI']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Resolución Nº             :  '.$this->paDatos['MDETALL']['NNUMRES']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Año de resolución         :  '.$this->paDatos['MDETALL']['CANORES']), 0, 0, 'L');
         $p_pdf->Ln(10);
         $p_pdf->Cell(0, 0, utf8_decode('Fecha de admisión:        :  '.$this->paDatos['MDETALL']['DFECHAR']), 0, 0, 'L');
         $p_pdf->Ln(10);
      }
      return true;
   }

   protected function mxRevisarConstacias($p_oSql) {
      $lcCodTre = $this->paDatos['CCODTRE'];
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "UPDATE B04MTRE SET cEstado = 'R', cCodUsu = '$lcCodUsu', tModifi = NOW() WHERE cCodTre = '$lcCodTre'";
      $llOk = $p_oSql->omExec($lcSql);
      if (!$llOk) {
         $this->pcError = $p_oSql->pcError;
         return false;
      }
      return true;
   }

   // ------------------------------------------------------------------------------
   // Registro de entrega constancias por bloque
   // 2019-03-18 MLC Creacion
   // ------------------------------------------------------------------------------
   public function omReporteEntregaEnGrupo() {
      $llOk = $this->mxValParamReporteEntregaEnGrupo();
      if (!$llOk) {        
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRecuperarDatosEntregaEnGrupo($loSql);
      if (!$llOk) {
         $loSql->omDisconnect();
         return false;
      }
      $llOk = $this->mxReporteEntregaEnGrupo($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }


   protected function mxValParamReporteEntregaEnGrupo() {
      if (!isset($this->paData['paExpedi']) || count($this->paData['paExpedi']) == 0) {
         $this->pcError = "SIN TRÁMITES POR APROBAR";
         return false;
      }
      return true;
   }

   protected function mxRecuperarDatosEntregaEnGrupo($p_oSql) {
      $laCodTre = implode("','", array_map(function ($entry) {
         return $entry['CCODTRE'];
      }, $this->paData['paExpedi']));
      $lcSql = "SELECT A.cCodTre, C.cNroDni, D.cNombre, C.cUniAca, E.cNomUni, A.mDetall, A.cIdCate, F.cDescri, B.nCosto, B.nCanSem
                FROM B04MTRE A
                INNER JOIN B03DDEU B ON B.cIdLog  = A.cIdLog
                INNER JOIN A01MALU C ON C.cCodAlu = B.cCodAlu
                INNER JOIN S01MPER D ON D.cNroDni = C.cNroDni
                INNER JOIN S01TUAC E ON E.cUniAca = C.cUniAca
                INNER JOIN B03TDOC F ON F.cIdCate = A.cIdCate
                WHERE A.cCodTre IN ('$laCodTre')
                ORDER BY A.mDetall::JSON->>'CNROCER'";
      $R1 = $p_oSql->omExec($lcSql);
      $this->paDatos = [];
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->paDatos[] = ['CCODTRE' => $laFila[0], 'CNRODNI' => $laFila[1],
                             'CNOMBRE' => $laFila[2], 'CUNIACA' => $laFila[3],
                             'CNOMUNI' => $laFila[4], 'MDETALL' => json_decode($laFila[5], true),
                             'CIDCATE' => $laFila[6], 'CDESCRI' => $laFila[7],
                             'NCOSTO'  => $laFila[8], 'NCANSEM' => $laFila[9]];
      }
      if (count($this->paDatos) == 0) {
         $this->pcError = "SIN TRAMITES SELECCIONADOS";
         return false;
      }
      return true;
   }

   protected function mxReporteEntregaEnGrupo($p_oSql) {      
      try {
         $fecha_actual = date ("Y-m-d");
         $pdf = new FPDF();
         $pdf->SetMargins(19,9,30);
         $pdf->SetAutoPageBreak(true, 8);
         $pdf->AddPage('P','A4'); 
         $pdf->SetFont('Courier', '', 6);
         $pdf->Cell(1, 0, utf8_decode('OFICINA DE REGISTRO Y ARCHIVO ACADEMICO'), 0, 0, 'L');
         $pdf->SetFont('Courier', 'B', 14);
         $pdf->Ln(10);
         $llCerti = in_array($this->paDatos[0]['CIDCATE'], ['CCESTU', 'CCCSID', 'CCCSUC']);
         if ($llCerti) {
            $pdf->Cell(1, 0, str_repeat(' ', 16).utf8_decode('REGISTRO DE ENTREGA DE CERTIFICADOS'), 0, 0, 'L');            
         }
         else {
            $pdf->Cell(1, 0, str_repeat(' ', 15).utf8_decode('REGISTRO DE ENTREGA DE CONSTANCIAS'), 0, 0, 'L');
         }
         $pdf->SetFont('Courier', 'B', 8);
         $pdf->Ln(10);
         $pdf->SetX($pdf->GetX() - 2);
         $pdf->Cell(1, 0, utf8_decode('FECHA: '.$fecha_actual), 0, 0, 'L');
         $pdf->Ln(5);
         $pdf->SetX($pdf->GetX() - 2);
         if ($this->paDatos[0]['CIDCATE'] == "CCESTU") {
            //CCODTRE, CNOMBRE, CNOMUNI, CIDCATE, CNROCER, FIRMA, NMONTO, NCANSEM,
            $laFldSiz = [16, 44, 34, 38, 28, 14, 12, 8];
            //CNOMBRE, CNOMUNI, CIDCATE
            $laStrSiz = [25, 19, 21];
         } else {
            //CCODTRE, CNOMBRE, CNOMUNI, CIDCATE, CNROCER, FIRMA
            $laFldSiz = [16, 54, 44, 38, 17, 25];
            //CNOMBRE, CNOMUNI, CIDCATE
            $laStrSiz = [30, 25, 21];
         }
         // CABECERA TABLA
         $pdf->SetFont('Courier', 'B', 8);
         $pdf->cell($laFldSiz[0], 4, utf8_decode('Nº EXP.'), 1, 0, 'L');
         $pdf->cell($laFldSiz[1], 4, utf8_decode('APELLIDOS Y NOMBRES'), 1, 0, 'L');
         $pdf->cell($laFldSiz[2], 4, utf8_decode('U. ACADEMICA'), 1, 0, 'L');
         $pdf->cell($laFldSiz[3], 4, utf8_decode('TIPO DE DOCUMENTO'), 1, 0, 'L');         
         if ($this->paDatos[0]['CIDCATE'] == "CCESTU") {
            $pdf->cell($laFldSiz[6], 4, utf8_decode('MONTO'), 1, 0, 'L');
            $pdf->cell($laFldSiz[7], 4, utf8_decode('SEM.'), 1, 0, 'L');
         }
         $pdf->cell($laFldSiz[4], 4, utf8_decode('Nº '.($llCerti ? 'CERTI.' : 'CONST.')), 1, 0, 'L');
         $pdf->cell(10, 4, utf8_decode('FIRMA'), 1, 1, 'L');
         $pdf->SetFont('Courier', '', 7);
         foreach ($this->paDatos as $laFila) {
            $pdf->SetX($pdf->GetX() - 2);
            $pdf->cell($laFldSiz[0], 4, utf8_decode("E-".$laFila['CCODTRE']), 1, 0, 'L');
            $pdf->cell($laFldSiz[1], 4, utf8_decode(substr($laFila['CNOMBRE'], 0, $laStrSiz[0])), 1, 0, 'L');
            $pdf->cell($laFldSiz[2], 4, utf8_decode(substr($laFila['CNOMUNI'], 0, $laStrSiz[1])), 1, 0, 'L');
            $pdf->cell($laFldSiz[3], 4, utf8_decode(substr($laFila['CDESCRI'], 0, $laStrSiz[2])), 1, 0, 'L');            
            if ($this->paDatos[0]['CIDCATE'] == "CCESTU") {
               $pdf->cell($laFldSiz[6], 4, utf8_decode($laFila['NCOSTO']), 1, 0, 'L');
               $pdf->cell($laFldSiz[7], 4, utf8_decode($laFila['NCANSEM']), 1, 0, 'L');
            }
            $pdf->cell($laFldSiz[4], 4, utf8_decode($laFila['MDETALL']['CNROCER']), 1, 0, 'L');
            $pdf->cell(10, 4, '', 1, 1, 'L');
         }
         $pdf->Ln(15);
         // FIRMA RESPONSABLE
         $lnYPos = $pdf->GetY();
         $lnXPos = $pdf->GetX();
         $pdf->Line($lnXPos, $lnYPos, $lnXPos + 30, $lnYPos);
         $pdf->Ln(3);
         $pdf->SetFont('Courier', 'B', 10);
         $pdf->Cell(1, 0, utf8_decode('FIRMA'), 0, 1, 'L');
         $pdf->Ln(4);
         $pdf->Cell(1, 0, utf8_decode('RESPONSABLE:'), 0, 1, 'L');
         $pdf->Output('F', $this->pcFile);
      } catch (Exception $e) {
         $this->pcError = 'ERROR AL GENERAR PDF';
         return false;
      }
      return true;
   }

   // IMPRIME EVALUACION DE CONFORMIDAD
   // 2020-10-21 JLF Creación
   public function omReporteEvaluacionConformidad() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteEvaluacionConformidad($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteEvaluacionConformidad($loSql);
      return $llOk;
   }

   protected function mxReporteEvaluacionConformidad($p_oSql) {
      $lcIdConf = $this->paData['CIDCONF'];
      $lcSql = "SELECT A.cIdConf, A.cIdOrde, B.cCodAnt, B.dGenera, B.cNroRuc, C.cRazSoc, B.nMonto, B.cMoneda, D.cDescri AS cDesMon,
                       A.cUsuRes, E.cNombre AS cNomRes, A.cTipEva, F.cDesCri AS cDesTip, A.cEstado, G.cDescri AS cDesEst,
                       TO_CHAR(A.tConfor, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tInconf, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tLevant, 'YYYY-MM-DD HH24:MI'),
                       A.nEvalua, A.mObserv, H.cCriter, I.cDescri AS cDesCri, H.nEvalua AS nEvaCri, A.cDescri AS cDesCon
                FROM E01PCON A
                INNER JOIN E01MORD B ON B.cIdOrde = A.cIdOrde
                INNER JOIN S01MPRV C ON C.cNroRuc = B.cNroRuc
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = B.cMoneda
                INNER JOIN V_S01TUSU_1 E ON E.cCodUsu = A.cUsuRes
                LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '270' AND F.cCodigo = A.cTipEva
                LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '271' AND G.cCodigo = A.cEstado
                INNER JOIN E01DCON H ON H.cIdConf = A.cIdConf
                LEFT OUTER JOIN V_S01TTAB I ON I.cCodTab = '272' AND I.cCodigo = H.cCriter
                WHERE A.cIdConf = '$lcIdConf' AND H.cEstado = 'A' ORDER BY H.nSerial";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO HAY DATOS PARA INMPRIMIR";
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDCONF' => $laTmp[0], 'CIDORDE' => $laTmp[1], 'CCODANT' => $laTmp[2], 'DGENERA' => $laTmp[3],
                             'CNRORUC' => $laTmp[4], 'CRAZSOC' => $laTmp[5], 'NMONTO'  => $laTmp[6], 'CMONEDA' => $laTmp[7],
                             'CDESMON' => $laTmp[8], 'CUSURES' => $laTmp[9], 'CNOMRES' => $laTmp[10],'CTIPEVA' => $laTmp[11],
                             'CDESTIP' => $laTmp[12],'CESTADO' => $laTmp[13],'CDESEST' => $laTmp[14],
                             'TCONFOR' => ($laTmp[15] == null)? 'S/D' : $laTmp[15],'TINCONF' => ($laTmp[16] == null)? 'S/D' : $laTmp[16],
                             'TLEVANT' => ($laTmp[17] == null)? 'S/D' : $laTmp[17],'NEVALUA' => $laTmp[18],'MOBSERV' => $laTmp[19],
                             'CCRITER' => $laTmp[20],'CDESCRI' => $laTmp[21],'NEVACRI' => $laTmp[22],'CDESCON' => $laTmp[23]];
      }
      return true;
   }

   protected function mxPrintReporteEvaluacionConformidad() {
      /*
      UCSM-ERP                   DIRECCIÓN DE LOGÍSTICA Y CONTRATACIONES                  PAG.:    1
      00000001                         ACTA DE CONFORMIDAD DE ORDEN                       2020-03-24
      ----------------------------------------------------------------------------------------------
      ORDEN      : OC00000001                                                     FECHA : 2020-01-01
      PROVEEDOR  : 01234567890 - 0123456789012345678901234567890123456789012345678901234567890123456
      MONTO      : 9,999,999.99 SOLES
      EVALUADOR  : 1220 - 01234567890123456789012345678901234567890123456789012345678901234567890123
      ESTADO     : 0123456789 2020-01-01 00:00
      DESCRIPCIÓN: 012345678901234567890123456789012345678901234567890123456789012345678901234567890
      TIPO EVALU.: 012345678901234567890123456789012345678901234567890123456789012345678901234567890
      EVALUACIÓN : EXCELENTE/BUENO/REGULAR/MALO/PÉSIMO
      
      ---DETALLE------------------------------------------------------------------------------------
      # CRITERIO                                                                          EVALUACIÓN
      ----------------------------------------------------------------------------------------------
      1 012345678901234567890123456789012345678901234567890123456789012345678901234567890 EXCELENTE  
      ----------------------------------------------------------------------------------------------
      OBSERVACIONES
      * SIN OBSERVACIONES *
      */
      $laCalifi = ['S/D', 'PÉSIMO', 'MALO', 'REGULAR', 'BUENO', 'EXCELENTE'];
      $laCalifi = array_map("utf8_decode", $laCalifi);
      $ldDate = date("Y-m-d");
      $i = 0;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         $this->laDatos[$i++] = $laFila;
         $laDatos[] = ['CLINEA' => fxNumber(++$j, 1, 0) . ' ' . fxString($laFila['CDESCRI'], 81) . ' ' . fxString($laCalifi[$laFila['NEVACRI']], 10), 'CESTFUE' => '', 'NTAMFUE' => 9];
      }
      $loPdf = new FPDF('landscape','cm','A5');
      $loPdf->SetMargins(1.5, 1, 1.5);
      $loPdf->SetAutoPageBreak(false);
      $loPdf->AddPage();
      $lnPag = 0;
      $lnRow = 0;
      $lnWidth = 0;
      $lnHeight = 0.38;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         // Titulo
         if ($llTitulo) {
            if ($lnPag > 0) {
               $loPdf->AddPage();
            }
            $lnPag++;
            $loPdf->SetFont('Courier', 'B' , 9);
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP                   DIRECCIÓN DE LOGÍSTICA Y CONTRATACIONES                  PÁG.:'.fxNumber($lnPag,5,0)), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, $this->laDatos[0]['CIDCONF'].utf8_decode('                         ACTA DE CONFORMIDAD DE ORDEN                       ').$ldDate, 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'ORDEN      : '.$this->laDatos[0]['CCODANT'].'                                                     FECHA : '.fxString($this->laDatos[0]['DGENERA'], 10), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'PROVEEDOR  : '.$this->laDatos[0]['CNRORUC'].' - '.fxString($this->laDatos[0]['CRAZSOC'], 67), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'MONTO      : '.fxNumber($this->laDatos[0]['NMONTO'], 12, 2).' '.$this->laDatos[0]['CDESMON'], 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'EVALUADOR  : '.$this->laDatos[0]['CUSURES'].' - '.fxString($this->laDatos[0]['CNOMRES'], 74), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'ESTADO     : '.$this->laDatos[0]['CDESEST'].' - '.(($this->laDatos[0]['CESTADO'] == 'C')? $this->laDatos[0]['TCONFOR'] : $this->laDatos[0]['TINCONF']), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DESCRIPCIÓN: '.fxString($this->laDatos[0]['CDESCON'], 81)), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, 'TIPO EVALU.: '.fxString($this->laDatos[0]['CDESTIP'], 81), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('EVALUACIÓN : ').$laCalifi[$this->laDatos[0]['NEVALUA']], 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '---DETALLE------------------------------------------------------------------------------------', 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('# CRITERIO                                                                          EVALUACIÓN'), 0, 2, 'L');
            $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------', 0, 2, 'L');
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->SetFont('Courier', $laFila['CESTFUE'] , $laFila['NTAMFUE']);
         $loPdf->Cell($lnWidth, $lnHeight, $laFila['CLINEA'], 0, 2, 'L');
         $lnRow++;
         $llTitulo = ($lnRow == 17 && count($laDatos) != (17*$lnPag))? true : false;
      }
      $loPdf->SetFont('Courier', 'B' , 9);
      $loPdf->Cell($lnWidth, $lnHeight, '----------------------------------------------------------------------------------------------', 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, 'OBSERVACIONES', 0, 2, 'L');
      $loPdf->SetFont('Courier', '' , 9);
      if (trim($this->laDatos[0]['MOBSERV']) == '') {
         $loPdf->Cell($lnWidth, $lnHeight, '* SIN OBSERVACIONES *', 0, 2, 'L');
      } else {
         $loPdf->Multicell($lnWidth, $lnHeight, trim($this->laDatos[0]['MOBSERV']), 0, 'J');
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   // -----------------------------------------------------------------------
   // Reporte de Expedientes de Concurso de Admision - Escuela de Postgrado
   // Creacion APR 2021-01-12  
   // -----------------------------------------------------------------------
   public function omMostrarReporteExpedientesAdmisionPostgrado() {
      $llOk = $this->mxValParamReporteExpedientesAdmisionPostgrado();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(2);
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxMostrarReporteExpedientesAdmisionPostgrado($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      if ($this->paData['CTIPO'] == '1') {
         $llOk = $this->mxPrintExpedientesAdmisionPostgradoPDF();
      } else {
         $llOk = $this->mxPrintExpedientesAdmisionPostgradoExcel();
      }
      return $llOk;
   }

   protected function mxValParamReporteExpedientesAdmisionPostgrado() {
      if (!isset($this->paData['CTIPO']) || strlen(trim($this->paData['CTIPO'])) != 1 || !in_array($this->paData['CTIPO'], ['1','2'])) {
         $this->pcError = 'TIPO DE GENERACION DE DOCUMENTO INVALIDA';
         return false;
      } elseif (!isset($this->paData['CPERIOD']) || strlen(trim($this->paData['CPERIOD'])) != 6) {
         $this->pcError = 'PERIODO DE CONCURSO DE ADMISIÓN INVALIDA O NO DEFINIDA';
         return false;
      }
      return true;
   }

   protected function mxMostrarReporteExpedientesAdmisionPostgrado($p_oSql) {
      //SOLICITUDES APROBADAS Y SOLICITADAS PARA REPORTE
      $lcSql = "SELECT A.cIdSoli, A.cNroDni, B.cNombre, B.cNroCel, B.cEmail, B.cDirecc, TO_CHAR(A.tRegist, 'YYYY-MM-DD HH24:MI'), C.cNomUni,
                  TRIM(A.cUniPro), TRIM(A.cProPer), D.cDescri, TO_CHAR(A.tRevisi, 'YYYY-MM-DD HH24:MI') 
                  FROM A06MEPG A
                  INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni
                  INNER JOIN S01TUAC C ON C.cUniaca = A.cUniaca
                  LEFT OUTER JOIN S01TTAB D ON D.cCodigo = A.cEstado AND D.cCodTab = '512' 
                  WHERE A.cEstado IN ('S', 'R') AND cPeriod = '{$this->paData['CPERIOD']}'
                  ORDER BY A.cIdSoli ASC";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = "ERROR: LA BASE DE DATOS NO TIENE INFORMACIÓN SOBRE LA SOLICITUD SELECCIONADA";
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)){
         $lcArchiv = (file_exists("/var/www/html/UCSMMTA/EXP/D".$laFila[1]."/G".$laFila[0].".pdf"))? 'S' : 'N';
         $lcArcUrl = "http://apps.ucsm.edu.pe/UCSMMTA/EXP/D".$laFila[1]."/G".$laFila[0].".pdf";
         $this->laDatos[] = ['CIDSOLI' => $laFila[0], 'CNRODNI' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 
                             'CNROCEL' => $laFila[3], 'CEMAIL'  => $laFila[4], 'CDIRECC' => $laFila[5], 'TREGIST' => $laFila[6],
                             'CNOMUNI' => $laFila[7], 'CUNIPRO' => $laFila[8], 'CPROPER' => $laFila[9], 'CESTADO' => $laFila[10],
                             'TREVISI' => $laFila[11],'CARCHIV' => $lcArchiv , 'CENLACE' => $lcArcUrl]; 
      }
      return true;
   }

   protected function mxPrintExpedientesAdmisionPostgradoPDF() {
      $laDatos = [];
      try{
         $pdf=new FPDF();
         $pdf->AddPage('L','A4');  
         $pdf->SetFont('Courier', 'B', 10);
         $pdf->Image('img/logo_trazos.png',10,10,48);
         $pdf->Ln(5);
         $pdf->Cell(0, 0, utf8_decode('EXPEDIENTES DE CONCURSO DE ADMISIÓN '.$this->paData['CPERIOD']), 0, 0, 'C');
         $pdf->Cell(0, 0, utf8_decode('PAG:' . fxNumber($pdf->PageNo(), 6, 0)), 0, 0, 'R');
         $pdf->Ln(5);
         $pdf->Cell(0, 0, utf8_decode(date("Y-m-d")), 0, 0, 'R');
         $pdf->Ln(5);
         $pdf->SetFont('Courier', 'B', 6);   
         $pdf->Cell(1, 0, utf8_decode('EPG1130'), 0, 0, 'L');
         $pdf->SetFont('Courier', 'B', 10);
         $pdf->Cell(0, 0, 'UCSM - ERP', 0, 0, 'R');
         $pdf->Ln(2);
         $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);        
         $pdf->Ln(5);
         $pdf->Cell(1, 0, utf8_decode('#'), 0, 0, 'C');
         $pdf->Cell(14, 0, utf8_decode('ID'), 0, 0, 'C');
         $pdf->Cell(16, 0, utf8_decode('DNI'), 0, 0, 'C');
         $pdf->Cell(75, 0, utf8_decode('Apellidos y Nombres'), 0, 0, 'C');
         $pdf->Cell(14, 0, utf8_decode('Celular'), 0, 0, 'C');
         $pdf->Cell(70, 0, utf8_decode('Correo Electrónico'), 0, 0, 'C');
         $pdf->Cell(31, 0, utf8_decode('F. Solicitud'), 0, 0, 'C');
         $pdf->Cell(36, 0, utf8_decode('F. Revisión'), 0, 0, 'C');
         $pdf->Cell(19, 0, utf8_decode('Estado'), 0, 0, 'C');
         $pdf->Ln(1);
         $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
         $pdf->SetFont('Courier','',9);
         $pdf->Ln(10);  
         $i = $j = 0;
         foreach ($this->laDatos as $laFila){
            $pdf->Cell(2, 0, utf8_decode($j+1), 0, 0, 'R');
            $pdf->Cell(12, 0, utf8_decode($laFila['CIDSOLI']), 0, 0, 'C');
            $pdf->Cell(17, 0, utf8_decode($laFila['CNRODNI']), 0, 0, 'L');
            $pdf->Cell(72, 0, utf8_decode(fxString($laFila['CNOMBRE'], 37)), 0, 0, 'L');
            $pdf->Cell(20, 0, utf8_decode($laFila['CNROCEL']), 0, 0, 'C');
            $pdf->Cell(65, 0, utf8_decode(fxString($laFila['CEMAIL'], 34)), 0, 0, 'L');
            $pdf->Cell(34, 0, utf8_decode($laFila['TREGIST']), 0, 0, 'C');
            $pdf->Cell(34, 0, utf8_decode($laFila['TREVISI']), 0, 0, 'C');
            $pdf->Cell(10, 0, utf8_decode($laFila['CESTADO']), 0, 0, 'L');
            $pdf->Ln(6);
            if ($i >= 20) {
               $pdf->SetFont('Courier', 'B', 10);
               $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
               $pdf->Ln(5);
               $pdf->Ln(5);
               $pdf->Ln(5);
               $pdf->Cell(0, 0, utf8_decode('------------------------------'), 0, 0, 'R');
               $pdf->Ln(5);
               $pdf->Cell(0, 0, utf8_decode('   FIRMA ADMISIÓN POSTGRADO   '), 0, 0, 'R');

               $pdf->AddPage('L','A4');  
               $pdf->SetFont('Courier', 'B', 10);
               $pdf->Image('img/logo_trazos.png',10,10,48);
               $pdf->Ln(5);
               $pdf->Cell(0, 0, utf8_decode('EXPEDIENTES DE CONCURSO DE ADMISIÓN '.$this->paData['CPERIOD']), 0, 0, 'C');
               $pdf->Cell(0, 0, utf8_decode('PAG:' . fxNumber($pdf->PageNo(), 6, 0)), 0, 0, 'R');
               $pdf->Ln(5);
               $pdf->Cell(0, 0, utf8_decode(date("Y-m-d")), 0, 0, 'R');
               $pdf->Ln(5);
               $pdf->SetFont('Courier', 'B', 6);   
               $pdf->Cell(1, 0, utf8_decode('EPG1130'), 0, 0, 'L');
               $pdf->SetFont('Courier', 'B', 10);
               $pdf->Cell(0, 0, 'UCSM - ERP', 0, 0, 'R');
               $pdf->Ln(2);
               $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);        
               $pdf->Ln(5);
               $pdf->Cell(1, 0, utf8_decode('#'), 0, 0, 'C');
               $pdf->Cell(14, 0, utf8_decode('ID'), 0, 0, 'C');
               $pdf->Cell(16, 0, utf8_decode('DNI'), 0, 0, 'C');
               $pdf->Cell(75, 0, utf8_decode('Apellidos y Nombres'), 0, 0, 'C');
               $pdf->Cell(14, 0, utf8_decode('Celular'), 0, 0, 'C');
               $pdf->Cell(70, 0, utf8_decode('Correo Electrónico'), 0, 0, 'C');
               $pdf->Cell(31, 0, utf8_decode('F. Solicitud'), 0, 0, 'C');
               $pdf->Cell(36, 0, utf8_decode('F. Revisión'), 0, 0, 'C');
               $pdf->Cell(19, 0, utf8_decode('Estado'), 0, 0, 'C');
               $pdf->Ln(1);
               $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
               $pdf->SetFont('Courier','',9);
               $pdf->Ln(10); 
               $i = 0;
            }
            $i++;
            $j++;
         }
         $pdf->SetFont('Courier', 'B', 10);
         $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
         $pdf->Ln(5);
         $pdf->Ln(5);
         $pdf->Ln(5);
         $pdf->Cell(0, 0, utf8_decode('------------------------------'), 0, 0, 'R');
         $pdf->Ln(5);
         $pdf->Cell(0, 0, utf8_decode('   FIRMA ADMISIÓN POSTGRADO   '), 0, 0, 'R');
         $pdf->Output('D',"REPORTE EXAMEN DE ADMISION POSTGRADO - ".$this->paData['CPERIOD'].".pdf");
      } catch (Exception $e) {
         $this->pcError = 'ERROR AL GENERAR PDF';
         die;
         return false;
      }
   }

   protected function mxPrintExpedientesAdmisionPostgradoExcel() {
      // Abre hoja de calculo
      $loXls = new CXls();
      $loXls->openXlsIO('Epg1130', 'R');
      // Cabecera
      $loXls->sendXls(0, 'M', 3, date("Y-m-d"));
      $loXls->sendXls(0, 'D', 3, $this->paData['CPERIOD']);
      $i = 5;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         #	CODIGO DE SOLICITUD   DNI   APELLIDOS Y NOMBRES   CELULAR	EMAIL   UNIVERSIDAD DE PROCEDENCIA   CARRERA PROGRAMA A POSTULAR   
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CIDSOLI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNROCEL']);
         $loXls->sendXls(0, 'F', $i, $laFila['CEMAIL']);
         $loXls->sendXls(0, 'G', $i, $laFila['CDIRECC']);
         $loXls->sendXls(0, 'H', $i, $laFila['CNOMUNI']);
         $loXls->sendXls(0, 'I', $i, $laFila['CESTADO']);
         $loXls->sendXls(0, 'J', $i, $laFila['CUNIPRO']);
         $loXls->sendXls(0, 'K', $i, $laFila['CPROPER']);
         $loXls->sendXls(0, 'L', $i, $laFila['TREGIST']);
         $loXls->sendXls(0, 'M', $i, $laFila['TREVISI']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # -------------------------------------------------------------------------------
   # REPORTE DE PAGOS DE PRECATOLICA POR PROYECTO ACADEMICO - DIRECCIÓN DE ADMISIÓN
   # Creacion APR 2022-07-05  
   # -------------------------------------------------------------------------------
   public function omReportePagosAdmisionPrecatolica() {
      $llOk = $this->mxValParamReportePagosAdmisionPrecatolica();
      if (!$llOk) {
          return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxMostrarReportePagosAdmisionPrecatolica($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReportePagosAdmisionPrecatolicaExcel();
      return $llOk;
   }

   protected function mxValParamReportePagosAdmisionPrecatolica() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
          $this->pcError = "USUARIO INVALIDO O NO DEFINIDO";
          return false;
      } elseif (!isset($this->paData['CPROYEC'])) {
         $this->pcError = "PROYECTO/PERIODO INVALIDO O NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxMostrarReportePagosAdmisionPrecatolica($p_oSql) {
      #DATOS PARA CONSULTAR DE PAGO DE PRECATOLICA - PRIMERA CUOTA
      $lcSql = "SELECT A.cCodAlu, B.cNroDni, B.cNombre, A.cProyec, A.dEmisio, A.cCuota, A.nAbono FROM C10DCCT A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodALu 
                  WHERE A.cProyec = '{$this->paData['CPROYEC']}' AND LEFT(A.cDocume, 2) = '11' AND A.cEstado = 'A' AND LEFT(A.cConcep, 3) = 'TE9' AND A.cCuota = '01'
                  ORDER BY B.cNombre, A.cCuota"; 
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR EN LA BUSQUEDA DE ESTUDIANTE PARA VALIDAR PAGOS DE PRECATOLICA";
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         #DATOS PARA CONSULTAR DE PAGO DE PRECATOLICA - SEGUNDA CUOTA
         $lcSql = "SELECT A.dEmisio, A.cCuota, A.nAbono FROM C10DCCT A
                    INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodALu 
                    WHERE A.cProyec = '{$this->paData['CPROYEC']}' AND LEFT(A.cDocume, 2) = '11' AND A.cEstado = 'A' AND LEFT(A.cConcep, 3) = 'TE9' AND A.cCuota = '02' AND A.cCodAlu = '$laTmp[0]'
                    ORDER BY B.cNombre, A.cCuota;";
         $R2 = $p_oSql->omExec($lcSql);
         if ($R2 == false) {
            $this->pcError = "ERROR: NO SE TIENE INFORMACIÓN SOBRE PAGOS DE PRIMERA CUOTA - PRECATOLICA";
            return false;
         }
         $laTmp1 = $p_oSql->fetch($R2);
         $this->laDatos[] = ['CCODALU' => $laTmp[0], 'CNRODNI' => $laTmp[1], 'CNOMBRE' => str_replace('/', ' ', $laTmp[2]),
                             'CPROYEC' => $laTmp[3], 'DEMISI1' => $laTmp[4], 'NCUOTA1' => $laTmp[5], 'NMONTO1' => $laTmp[6],         
                             'DEMISI2' => $laTmp1[0],'NCUOTA2' => $laTmp1[1],'NMONTO2' => $laTmp1[2]];
      }      
      return true;
   }

   protected function mxPrintReportePagosAdmisionPrecatolicaExcel() {
      $loXls = new CXls();
      $loXls->openXlsIO('Cpu1010', 'R');
      # Cabecera
      $loXls->sendXls(0, 'H', 3, date("Y-m-d"));
      $loXls->sendXls(0, 'D', 3, 'Periodo Académico: '.$this->paData['CPROYEC']);
      $i = 6;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CCODALU']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'E', $i, $laFila['DEMISI1']);
         $loXls->sendXls(0, 'F', $i, $laFila['NMONTO1']);
         $loXls->sendXls(0, 'G', $i, $laFila['DEMISI2']);
         $loXls->sendXls(0, 'H', $i, $laFila['NMONTO2']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # -----------------------------------------------------------------------
   # REPORTE DE PAGOS INGRESANTES PARA ESTADISTICAS - DIRECCIÓN DE ADMISIÓN
   # Creacion APR 2022-07-05  
   # -----------------------------------------------------------------------
   public function omReportePagosAdmisionIngresantes() {
      $llOk = $this->mxValParamReportePagosAdmisionIngresantes();
      if (!$llOk) {
          return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      //echo '111';
      $llOk = $this->mxMostrarReportePagosAdmisionIngresantes($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReportePagosAdmisionIngresantesExcel();
      return $llOk;
   }

   protected function mxValParamReportePagosAdmisionIngresantes() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
          $this->pcError = "USUARIO INVALIDO O NO DEFINIDO";
          return false;
      } elseif (!isset($this->paData['CPROYEC'])) {
         $this->pcError = "PROYECTO/PERIODO INVALIDO O NO DEFINIDO";
         return false;
      }
      return true;
   }

   protected function mxMostrarReportePagosAdmisionIngresantes($p_oSql) {
      #DATOS PARA CONSULTAR DE PAGO DE INGRESANTES
      $lcAnio= substr(($this->paData['CPROYEC']), 0,4);
      /*$lcSql = "SELECT A.cCodAlu, B.cNroDni, B.cNombre, B.cNomUni, A.cProyec, A.dEmisio, A.cCuota, SUM(A.nDebito - A.nAbono) FROM C10DCCT A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodALu 
                  WHERE A.cProyec = '{$this->paData['CPROYEC']}' AND LEFT(A.cDocume, 2) = '01' AND A.cEstado = 'A' AND LEFT(A.cConcep, 3) = 'TE0' AND A.cCuota = '01'
                  GROUP BY A.cCodAlu, B.cNroDni, B.cNombre, B.cNomUni, A.cProyec, A.dEmisio, A.cCuota
                  ORDER BY B.cNombre, A.cCuota"; */
      $lcSql = "SELECT A.cCodAlu, B.cNroDni, B.cNombre, B.cNomUni, A.cProyec, A.dEmisio, A.cCuota, SUM(A.nDebito - A.nAbono)
                  FROM C10DCCT A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodALu 
                  WHERE A.cProyec = '{$this->paData['CPROYEC']}' AND LEFT(A.cDocume, 2) IN ('01', '18')  
                  AND A.cEstado = 'A' AND LEFT(A.cConcep, 3) = 'TE0' AND A.cCuota = '01' AND SUBSTRING(A.cCodAlu,1,4) ='{$lcAnio}'
                  GROUP BY A.cCodAlu, B.cNroDni, B.cNombre, B.cNomUni, A.cProyec, A.dEmisio, A.cCuota
                  ORDER BY B.cNombre, A.cCuota "; // 01=pregrado, 18=carreras a distancia
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR EN LA BUSQUEDA DE ESTUDIANTES PARA VALIDAR PAGOS DE INGRESANTES";
         return false;
      }
      //echo '3333';
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODALU' => $laTmp[0], 'CNRODNI' => $laTmp[1], 'CNOMBRE' => str_replace('/', ' ', $laTmp[2]),
                             'CNOMUNI' => $laTmp[3], 'CPROYEC' => $laTmp[4], 'DEMISIO' => $laTmp[5], 'NMONTOP' => $laTmp[7]];
      }    
      return true;
   }

   protected function mxPrintReportePagosAdmisionIngresantesExcel() {
      $loXls = new CXls();
      $loXls->openXlsIO('Cpu1020', 'R');
      # Cabecera
      $loXls->sendXls(0, 'G', 3, date("Y-m-d"));
      $loXls->sendXls(0, 'D', 3, 'Periodo Académico: '.$this->paData['CPROYEC']);
      $i = 5;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CCODALU']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNOMUNI']);
         $loXls->sendXls(0, 'F', $i, $laFila['DEMISIO']);
         $loXls->sendXls(0, 'G', $i, $laFila['NMONTOP']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # -----------------------------------------------------------------
   # REPORTE DE CONCEPTOS DE PAGO A CARGO DE LA DIRECCIÓN DE ADMISIÓN
   # Creacion APR 2022-07-05  
   # -----------------------------------------------------------------
   public function omReporteConceptosDePagoAdmision() {
      $llOk = $this->mxValParamReporteConceptosDePagoAdmision();
      if (!$llOk) {
          return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect(2);
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      $llOk = $this->mxMostrarReporteConceptosDePagoAdmision($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReporteConceptosDePagoAdmisionExcel();
      return $llOk;
   }

   protected function mxValParamReporteConceptosDePagoAdmision() {
      $loDate = new CDate();
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = "USUARIO INVALIDO O NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CIDCATE']) || strlen(trim($this->paData['CIDCATE'])) != 6) {
         $this->pcError = "CONCEPTO DE PAGO INVALIDO O NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['DINICIO']) || !$loDate->mxValDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!isset($this->paData['DFINALI']) || !$loDate->mxValDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL ES MENOR QUE FECHA DE INICIO';
         return false;
      } 
      return true;
   }

   protected function mxMostrarReporteConceptosDePagoAdmision($p_oSql) {
      #DATOS PARA CONSULTAR DE DEUDAS GENERADAS POR CONCEPTO DE PAGO 
      if($this->paData['CIDCATE'] == '000134'){//Quitar en marzo 2023
        $lcTmp=$this->paData['CIDCATE'];
        $this->paData['CIDCATE'] ='000133';
      }
      $lcSql = "SELECT A.cNroDni, C.cNombre, A.cNroPag, A.nMonto, TO_CHAR(A.dFecha, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.dRecepc, 'YYYY-MM-DD HH24:MI') FROM B03MDEU A
                  INNER JOIN B03DDEU B ON B.cIdDeud = A.cIdDeud
                  INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu 
                  WHERE A.dFecha::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' AND B.cIdCate = '{$this->paData['CIDCATE']}' AND A.cEstado <> 'X'
                  ORDER BY C.cNombre"; 
     // echo $lcSql;
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR EN LA BUSQUEDA DE ESTUDIANTES EN EL RANGO DE FECHAS REGISTRADO";
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CNRODNI' => $laTmp[0], 'CNOMBRE' => str_replace('/', ' ', $laTmp[1]), 'CNROPAG' => $laTmp[2], 
                             'NMONTO'  => $laTmp[3], 'DFECHA'  => $laTmp[4], 'DRECEPC' => $laTmp[5]];
      } 
      if($this->paData['CIDCATE'] == '000133'){//Quitar en marzo 2023
        $this->paData['CIDCATE'] = $lcTmp;
      }
      return true;
   }

   protected function mxPrintReporteConceptosDePagoAdmisionExcel() {
      $loXls = new CXls();
      $loXls->openXlsIO('Cpu1030', 'R');
      # Cabecera
      $loXls->sendXls(0, 'G', 3, date("Y-m-d"));
      $loXls->sendXls(0, 'C', 3, 'CONCEPTO: '.$this->paData['CIDCATE']);
      $i = 5;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNROPAG']);
         $loXls->sendXls(0, 'E', $i, $laFila['DFECHA']);
         $loXls->sendXls(0, 'F', $i, $laFila['DRECEPC']);
         $loXls->sendXls(0, 'G', $i, $laFila['NMONTO']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   # -----------------------------------------------------------------------
   # REPORTE DE PAGOS INGRESANTES PARA ESTADISTICAS - DIRECCIÓN DE ADMISIÓN
   # Creacion APR 2022-07-05  
   # -----------------------------------------------------------------------
   public function omReportePagosAdmisionIngresantesDistancia() {
      $llOk = $this->mxValParamReportePagosAdmisionIngresantes();
      if (!$llOk) {
          return false;
      }
      $loSql = new CSql();
      $llOk  = $loSql->omConnect();
      if (!$llOk) {
          $this->pcError = $loSql->pcError;
          return false;
      }
      //echo '111';
      $llOk = $this->mxMostrarReportePagosAdmisionIngresantesDistancia($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
          return false;
      }
      $llOk = $this->mxPrintReportePagosAdmisionIngresantesDistanciaExcel();
      return $llOk;
   }

   protected function mxMostrarReportePagosAdmisionIngresantesDistancia($p_oSql) {
      #DATOS PARA CONSULTAR DE PAGO DE INGRESANTES
      $lcAnio= substr(($this->paData['CPROYEC']), 0,4);
      $lcSql = "SELECT A.cCodAlu, B.cNroDni, B.cNombre, B.cNomUni, A.cProyec, A.dEmisio, A.cCuota, SUM(A.nDebito - A.nAbono)
                  FROM C10DCCT A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodALu 
                  WHERE A.cProyec = '{$this->paData['CPROYEC']}' AND LEFT(A.cDocume, 2) IN ('18')  
                  AND A.cEstado = 'A' AND LEFT(A.cConcep, 3) = 'TE0' AND A.cCuota = '01' AND SUBSTRING(A.cCodAlu,1,4) ='{$lcAnio}'
                  GROUP BY A.cCodAlu, B.cNroDni, B.cNombre, B.cNomUni, A.cProyec, A.dEmisio, A.cCuota
                  ORDER BY B.cNombre, A.cCuota "; // 01=pregrado, 18=carreras a distancia
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = "ERROR EN LA BUSQUEDA DE ESTUDIANTES PARA VALIDAR PAGOS DE INGRESANTES";
         return false;
      }
      //echo '3333';
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODALU' => $laTmp[0], 'CNRODNI' => $laTmp[1], 'CNOMBRE' => str_replace('/', ' ', $laTmp[2]),
                             'CNOMUNI' => $laTmp[3], 'CPROYEC' => $laTmp[4], 'DEMISIO' => $laTmp[5], 'NMONTOP' => $laTmp[7]];
      }    
      return true;
   }

   protected function mxPrintReportePagosAdmisionIngresantesDistanciaExcel() {
      $loXls = new CXls();
      $loXls->openXlsIO('Cpu1020', 'R');
      # Cabecera
      $loXls->sendXls(0, 'G', 3, date("Y-m-d"));
      $loXls->sendXls(0, 'D', 3, 'Periodo Académico: '.$this->paData['CPROYEC']);
      $i = 5;
      $j = 0;
      foreach ($this->laDatos as $laFila) {
         $i++;
         $j++;
         $loXls->sendXls(0, 'A', $i, $j);
         $loXls->sendXls(0, 'B', $i, $laFila['CCODALU']);
         $loXls->sendXls(0, 'C', $i, $laFila['CNRODNI']);
         $loXls->sendXls(0, 'D', $i, $laFila['CNOMBRE']);
         $loXls->sendXls(0, 'E', $i, $laFila['CNOMUNI']);
         $loXls->sendXls(0, 'F', $i, $laFila['DEMISIO']);
         $loXls->sendXls(0, 'G', $i, $laFila['NMONTOP']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }
}
?>
