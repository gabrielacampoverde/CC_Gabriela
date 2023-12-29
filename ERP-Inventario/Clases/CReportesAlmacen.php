<?php

require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once "Libs/fpdf/fpdf.php";
require_once "class/class.ezpdf.php";

//-------------------------------------
// Reportes Almacen
//-------------------------------------
class CReportesAlmacen extends CBase {

   public $pcFile, $paData, $paDatos, $paProyec, $laRequer, $paCodAlm, $paTipArt, $paGrupArt, $paTipAnt, $paCenCos;
   protected $laDatos, $ldFecSis, $lcPeriod, $ldFecCal, $ldFecCnt, $laIdRequ;

   public function __construct() {
      parent::__construct();
      $this->paDatos = $this->paProyec = $this->laDatos = $this->laRequer = $this->paCtaCaj = $this->paCodAlm = $this->paTipArt = $this->paGrupArt = $this->paTipAnt = $this->paCenCos = null;
      $this->pcFile = 'FILES/R' . rand() . '.pdf';
   }

   protected function mxValDiferenciaFecha() {
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

   public function omCargarAlmacenes() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarAlmacenes($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarAlmacenes($p_oSql) {
      $lcSql = "SELECT cCodAlm, cDescri FROM E03MALM WHERE cCodAlm != '000' ORDER BY cCodAlm";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paCodAlm [] = ['CCODALM' => $laTmp[0], 'CDESCRI' => $laTmp[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY ALMACENES ACTIVOS';
         return false;
      }
      return true;
   }

   // TRAE TIPOS DE ARTICULO
   public function omCargarTipoArticulo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarTipoArticulo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarTipoArticulo($p_oSql) {
      $lcSql = "SELECT CCODIGO, CDESCRI FROM  V_S01TTAB WHERE cCodTab = '082' ORDER BY cCodigo";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paTipArt [] = ['CTIPART' => $laTmp[0], 'CDESCRI' => $laTmp[1]];
      }
      return true;
   }

   //TRAE GRUPO DE ARTICULOS
   public function omCargarGrupoArticulo() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarGrupoArticulo($loSql);
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarGrupoArticulo($p_oSql) {
      $lcSql = "SELECT CCODIGO, CDESCRI FROM  V_S01TTAB WHERE cCodTab = '083' ORDER BY CCODIGO";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paGrupArt [] = ['CGRUART' => $laTmp[0], 'CDESCRI' => $laTmp[1]];
      }
      return true;
   }

   //TRAE CENTROS DE COSTO
   public function omCargarCentroCostos() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarCentroCostos($loSql);
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarCentroCostos($p_oSql) {
      $lcSql = "SELECT cCenCos, cDescri, cCodAnt FROM S01TCCO WHERE cCenCos != '000' ORDER BY cCenCos";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paCenCos [] = ['CCENCOS' => $laTmp[0], 'CDESCRI' => $laTmp[1], 'CCODANT' => $laTmp[2]];
      }
      return true;
   }

   // IMPRIME INVENTARIO POR ALAMCEN - ALMACEN - CREACION - FERNANDO - 2018-05-22
   public function omReporteInventario() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteInventario($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteInventario($loSql);
      return $llOk;
   }

   public function omCargarTipoMovimientoAnt() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxCargarTipoMovimientoAnt($loSql);
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxCargarTipoMovimientoAnt($p_oSql) {
      $lcSql = "SELECT CCODIGO, CDESCRI FROM  V_S01TTAB WHERE cCodTab = '106' ORDER BY CCODIGO";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->paTipAnt [] = ['CTIPANT' => $laTmp[0], 'CDESCRI' => $laTmp[1]];
      }
      return true;
   }

   protected function mxReporteInventario($p_oSql) {
      $lcCodAlm = $this->paData['CCODALM'];
      $lcCodAlm1 = $this->paData['CCODALM1'];
      $lctipArt = $this->paData['CTIPART'];
      $lcGrupArt = $this->paData['CGRUPART'];
      $lcSql = "SELECT B.cNombre FROM E03PUSU A INNER JOIN V_S01TUSU_1 B ON B.cCodUsu = A.cCodUsu WHERE A.cCodAlm = '$lcCodAlm'";
      $R1 = $p_oSql->omExec($lcSql);
      $laFila = $p_oSql->fetch($R1);
      $this->paData['CUSUALM'] = (!$laFila)?'SIN ENCARGADO':$laFila[0];
      $lcConsulta = '';
      if ($lctipArt != '*') {
         $lcConsulta = " AND C.cTipArt = '$lctipArt' ";
      }
      if ($lcGrupArt != '*') {
         $lcConsulta = $lcConsulta . " AND C.cGrupo = '$lcGrupArt' ";
      }
      $lcSql = "SELECT A. cCodAlm, A.cCodArt, A.cEstado, A.nStock, A.cUsuCod, B.cDescri AS cDesAlm, B.cDesCor, B.cCenCos,
                 C.cDescri AS cDesArt, C.cUnidad, C.nRefSol, C.nRefDol,  C.cTipArt, C.cGrupo, D.cDescri AS cDesTip, F.cDescri AS cDesGru
                 FROM E03PALM A
                 INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm
                 INNER JOIN E01MART C ON C.cCodArt = A.cCodArt
                 LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '082' AND D.cCodigo = C.cTipArt
                 LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '083' AND F.cCodigo = C.cGrupo
                WHERE A.cCodAlm   BETWEEN '$lcCodAlm' AND '$lcCodAlm1'" . $lcConsulta . " AND A.nStock > 0  ORDER BY A.cCodAlm, C.cTipArt, A.cCodArt  ";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODALM' => $laTmp[0], 'CCODART' => $laTmp[1], 'CESTADO' => $laTmp[2], 'NSTOCK' => $laTmp[3],
             'CUSUCOD' => $laTmp[4], 'CDESALM' => $laTmp[5], 'CDESCOR' => $laTmp[6], 'CCENCOS' => $laTmp[7],
             'CDESART' => $laTmp[8], 'CUNIDAD' => $laTmp[9], 'NREFSOL' => $laTmp[10], 'NREFDOL' => $laTmp[11],
             'CTIPART' => $laTmp[12], 'CGRUPO' => $laTmp[13], 'CDESTIP' => $laTmp[14], 'CDESGRU' => $laTmp[15]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintReporteInventario($p_oSql) {
      // Detalle del reporte
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcCodAlm = $this->laDatos[0]['CCODALM'];
      $lcDesAlm = $this->laDatos[0]['CDESALM'];
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcTipArt = $this->laDatos[0]['CTIPART'];
      $lcDesTip = $this->laDatos[0]['CDESTIP'];
      $lcGrupo = $this->laDatos[0]['CGRUPO'];
      $lcDesGru = $this->laDatos[0]['CDESGRU'];
      $lcNombre = $this->paData['CUSUALM'];
      $laSuma = 0.00;
      $lnSuma = 0.00;
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $lcTipo = '*';
      $lcDesAlm = $this->laDatos[0]['CDESALM'];
      $laDatos[] = ['<b>* ALMACEN: ' . $lcCodAlm . ' ' . $lcDesAlm . '</b>'];
      $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
      $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
      foreach ($this->laDatos as $laFila) {
         if ($lcCodAlm != $laFila['CCODALM']) {
            $lcCodAlm = $laFila['CCODALM'];
            $lcDesAlm = $laFila['CDESALM'];
            $laDatos[] = ['<b>* ALMACEN: ' . $lcCodAlm . ' ' . $lcDesAlm . '</b>'];
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIP'];
            $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcTipArt != $laFila['CTIPART']) {
            $lnContad = 1;
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIP'];
            $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcGrupo != $laFila['CGRUPO']) {
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         $laDatos[] = [fxString($lnContad, 4) . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 46) . ' ' . fxNumber(trim($laFila['NSTOCK']), 14, 4) . ' ' . fxString($laFila['CUNIDAD'], 3) . ' __________'];
         $lnContad += 1;
      }
      $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];

      $laDatos[] = [fxString('', 17) . '_____________________' . fxString('', 18) . '_____________________' . fxString('', 17)];
      $laDatos[] = [fxString('', 19) . fxString($laFila['CDESALM'], 15) . fxString('', 25) . 'Recepcionante' . fxString('', 21)];

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
            $loPdf->ezText('<b>UCSM-ERP                             INVENTARIO ALMACEN                             PAG: ' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ALM1210            '.fxStringCenter($lcNombre, 55).'          ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>');
            $loPdf->ezText('<b> #   COD.ART. DESCRIPCION                                             STOCK UNI INVENTARIO</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            //$loPdf->ezText('<b>* ' .$lcTipArt[$i].' '.$lcDesTip[$i].fxString('', 50). '</b>');
            //$llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 76) ? true : false;
      }

      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME INVENTARIO POR ALAMCEN UNICO - ALMACEN - CREACION - FERNANDO - 2018-05-22
   public function omReporteInventarioUnico() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteInventarioUnico($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteInventarioUnico();
      return $llOk;
   }

   protected function mxReporteInventarioUnico($p_oSql) {
      $lcConsulta = '';
      $lcCodAlm = $this->paData['CCODALM'];
      $lcTipArt = $this->paData['CTIPART'];
      $lcGruArt = $this->paData['CGRUART'];
      if ($lcTipArt != '**') {
         $lcConsulta = " AND C.cTipArt = '$lcTipArt' ";
      }
      if ($lcGruArt != '**') {
         $lcConsulta = $lcConsulta . " AND C.cGrupo = '$lcGruArt' ";
      }
      $lcSql = "SELECT A. cCodAlm, A.cCodArt, A.cEstado, A.nStock, A.cUsuCod, B.cDescri AS cDesAlm, B.cDesCor, B.cCenCos,
                       C.cDescri AS cDesArt, C.cUnidad, C.nRefSol, C.nRefDol,  C.cTipArt, C.cGrupo, D.cDescri AS cDesTip, 
                       F.cDescri AS cDesGru
                FROM E03PALM A
                INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm
                INNER JOIN E01MART C ON C.cCodArt = A.cCodArt
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '082' AND D.cCodigo = C.cTipArt
                LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '083' AND F.cCodigo = C.cGrupo
                WHERE A.cCodAlm = '$lcCodAlm' " . $lcConsulta . " AND A.nStock > 0  ORDER BY A.cCodAlm, C.cTipArt, A.cCodArt  ";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODALM' => $laTmp[0], 'CCODART' => $laTmp[1], 'CESTADO' => $laTmp[2], 'NSTOCK'  => $laTmp[3],
                             'CUSUCOD' => $laTmp[4], 'CDESALM' => $laTmp[5], 'CDESCOR' => $laTmp[6], 'CCENCOS' => $laTmp[7],
                             'CDESART' => $laTmp[8], 'CUNIDAD' => $laTmp[9], 'NREFSOL' => $laTmp[10],'NREFDOL' => $laTmp[11],
                             'CTIPART' => $laTmp[12],'CGRUPO'  => $laTmp[13],'CDESTIP' => $laTmp[14],'CDESGRU' => $laTmp[15]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintReporteInventarioUnico() {
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcCodAlm = $this->laDatos[0]['CCODALM'];
      $lcDesAlm = $this->laDatos[0]['CDESALM'];
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcTipArt = $this->laDatos[0]['CTIPART'];
      $lcDesTip = $this->laDatos[0]['CDESTIP'];
      $lcGrupo  = $this->laDatos[0]['CGRUPO'];
      $lcDesGru = $this->laDatos[0]['CDESGRU'];
      $laSuma = 0.00;
      $lnSuma = 0.00;
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $lcTipo = '*';
      $lcDesAlm = $this->laDatos[0]['CDESALM'];
      $laDatos[] = ['<b>* ALMACEN: ' . $lcCodAlm . ' ' . $lcDesAlm . '</b>'];
      $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
      $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
      foreach ($this->laDatos as $laFila) {
         if ($lcCodAlm != $laFila['CCODALM']) {
            $lcCodAlm = $laFila['CCODALM'];
            $lcDesAlm = $laFila['CDESALM'];
            $laDatos[] = ['<b>* ALMACEN: ' . $lcCodAlm . ' ' . $lcDesAlm . '</b>'];
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIP'];
            $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcTipArt != $laFila['CTIPART']) {
            $lnContad = 1;
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIP'];
            $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcGrupo != $laFila['CGRUPO']) {
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         $laDatos[] = [fxString($lnContad, 4) . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 46) . ' ' . fxNumber(trim($laFila['NSTOCK']), 14, 4) . ' ' . fxString($laFila['CUNIDAD'], 3) . ' __________'];
         $lnContad += 1;
      }
      $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];

      $laDatos[] = [fxString('', 17) . '_____________________' . fxString('', 18) . '_____________________' . fxString('', 17)];
      $laDatos[] = [fxString('', 19) . fxString($laFila['CDESALM'], 15) . fxString('', 25) . 'Recepcionante' . fxString('', 21)];

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
            $loPdf->ezText('<b>UCSM-ERP                             INVENTARIO ALMACEN                             PAG: ' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ALM1210                                                                             ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>');
            $loPdf->ezText('<b> #   COD.ART. DESCRIPCION                                             STOCK UNI INVENTARIO</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            //$loPdf->ezText('<b>* ' .$lcTipArt[$i].' '.$lcDesTip[$i].fxString('', 50). '</b>');
            //$llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 76) ? true : false;
      }

      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

// REPORTE DE MOVIMIENTOS POR ALMACÉN POR FECHA - ANDREA, KIM
   public function omRepMovimientoAlmacenxFecha() {

      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepMovimientoAlmacenxFecha($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepMovimientoAlmacenxFecha($loSql);
      return $llOk;
   }

   protected function mxRepMovimientoAlmacenxFecha($p_oSql) {
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcCodAlm = $this->paData['CCODALM'];
      $lcCodAlm1 = $this->paData['CCODALM1'];
      $lctipArt = $this->paData['CTIPART'];
      $lcGrupArt = $this->paData['CGRUPART'];

      $i = 0;
      $lcConsulta = '';
      if ($lctipArt != '*') {
         $lcConsulta = " AND C.cTipArt = '$lctipArt' ";
      }
      if ($lcGrupArt != '*') {
         $lcConsulta = $lcConsulta . " AND C.cGrupo = '$lcGrupArt' ";
      }
      $lcSql = "SELECT A.cIdKard, A.cCodArt, A.nCantid, A.nCosTo, A.nPreTot, B.cAlmOri, B.cAlmDes, B.dFecha, B.cCodEmp, B.cTipo, B.cEstado, B.cTipMov, B.cNumMov, C.cDescri, D.cDescri AS cDesOri, E.cDescri AS cDesDes, F.cDescri AS cDesTip, C.cTipArt, C.cGrupo, G.cDescri AS cDesTipArt, H.cDescri AS cDesGru FROM E03DKAR A
                 INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                 INNER JOIN E01MART C ON C.cCodArt = A.cCodArt
                 INNER JOIN E03MALM D ON D.cCodAlm = B.cAlmOri
                 INNER JOIN E03MALM E ON E.cCodAlm = B.cAlmDes
                 LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '101'
                 LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '082' AND G.cCodigo = C.cTipArt
                 LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '083' AND H.cCodigo = C.cGrupo
                 WHERE ( B.cAlmOri  BETWEEN '$lcCodAlm' AND '$lcCodAlm1'  OR B.cAlmDes BETWEEN '$lcCodAlm' AND '$lcCodAlm1' ) AND  B.dFecha::DATE >= '$ldInicio' AND B.dFecha::DATE <= '$ldFinali'  " . $lcConsulta . "   ORDER BY B.cAlmOri, B.dFecha, C.cTipArt, A.cCodArt  ";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDKARD' => $laFila[0], 'CCODART' => $laFila[1], 'NCANTID' => $laFila[2],
             'NCOSTO' => $laFila[3], 'NPRETOT' => $laFila[4], 'CALMORI' => $laFila[5], 'CALMDES' => $laFila[6], 'DFECHA' => $laFila[7],
             'CCODEMP' => $laFila[8], 'CTIPO' => $laFila[9], 'CESTADO' => $laFila[10], 'CTIPMOV' => $laFila[11], 'CNUMMOV' => $laFila[12], 'CDESART' => $laFila[13], 'CDESORI' => $laFila[14], 'CDESDES' => $laFila[15], 'CDESTIP' => $laFila[16], 'CTIPART' => $laFila[17], 'CGRUPO' => $laFila[18], 'CDESTIPART' => $laFila[19], 'CDESGRU' => $laFila[20]];
         $i++;
      }
      //ECHO $lcSql; die;
      //PRINT_R($this->laDatos); 
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR CON LOS FILTROS SELECCIONADOS';
         return false;
      }
      return true;
   }

   protected function mxPrintRepMovimientoAlmacenxFecha($p_oSql) {
      /*
        UCSM-ERP                                               MOVIMIENTOS POR ALMACÉN                                               PAG.:    1
        ERP9999                                            DEL: 9999-99-99  AL: 9999-99-99                                           9999-99-99
        ---------------------------------------------------------------------------------------------------------------------------------------
        INGRESOS S/.             SALIDAS S/.
        ALM FECHA MOVIMIENTO    ARTÍCULO  DESCRIPCIÓN                   MARCA        | CANTIDAD        COSTO | CANTIDAD        COSTO | EMPLEADO
        ---------------------------------------------------------------------------------------------------------------------------------------
        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        1        2         3         4
        TOTAL 99 |             99,999.99 |             99,999.99 |

        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        |            ---------- |            ---------- |
        TOTAL 99 |             99,999.99 |             99,999.99 |
        ---------------------------------------------------------------------------------------------------------------------------------------
        9,999 ARTÍCULOS                                                 GENERAL |             99,999.99 |             99,999.99 |
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345
        1         2         3         4         5         6         7         8         9         10        110       120       130
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $lcAlmacen = $this->laDatos[0]['CDESDES'];
      $lcDesAlm = $this->laDatos[0]['CDESDES'];
      $lcCodAlm = $this->laDatos[0]['CALMDES'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcTipArt = $this->laDatos[0]['CTIPART'];
      $lcDesTipArt = $this->laDatos[0]['CDESTIPART'];
      $lcGrupo = $this->laDatos[0]['CGRUPO'];
      $lcDesGru = $this->laDatos[0]['CDESGRU'];
      $ldDate = date('Y-m-d', time());
      $lcTipTrx = '*';
      $lcCodTrx = '*';
      $laDatos[] = ['<b>* ALMACEN: ' . $lcCodAlm . ' ' . $lcDesAlm . '</b>'];
      $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
      $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
      foreach ($this->laDatos as $laFila) {
         if ($lcCodAlm != $laFila['CCODALM']) {
            $lcCodAlm = $laFila['CCODALM'];
            $lcDesAlm = $laFila['CDESALM'];
            $laDatos[] = ['<b>* ALMACEN: ' . $lcCodAlm . ' ' . $lcDesAlm . '</b>'];
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIP'];
            $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcTipArt != $laFila['CTIPART']) {
            $lnContad = 1;
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIP'];
            $laDatos[] = ['<b>  * ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcGrupo != $laFila['CGRUPO']) {
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b>   * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         //$laDatos[] = ['<b>* '.$laFila['CALMORI'].' - '.trim($laFila['CDESORI']).'</b>']; 
         if ($laFila['CTIPO'] == 'S' || $laFila['CTIPO'] == 'T') {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 40) . fxString(' ', 20) . ' ' . fxNumber($laFila['NCANTID'], 9, 2) . ' ' . fxNumber($laFila['NPRETOT'], 9, 2) . '  ' . $laFila['CCODEMP'] . '  ' . fxString($laFila['CDESTIP'], 13)];
            $laSumaSal = $laSumaSal + $laFila['NPRETOT'];
         } else {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 40) . ' ' . fxNumber($laFila['NCANTID'], 9, 2) . ' ' . fxNumber($laFila['NPRETOT'], 9, 2) . fxString(' ', 20) . '  ' . $laFila['CCODEMP'] . '  ' . fxString($laFila['CDESTIP'], 13)];
            $laSumaIng = $laSumaIng + $laFila['NPRETOT'];
         }

         //$laDatos[] = [$laFila['DFECHA'].' '.$laFila['CCODART'].' '. fxString($laFila['CDESART'], 40).' '.fxNumber($laFila['NCANTID'],9,2).' '.fxNumber($laFila['NPRETOT'],9,2).' '.$laFila['CCODEMP']]; 

         $laTmp = $laFila;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['  **TOTAL                                                             ' . fxNumber($laSumaIng, 10, 2) . '           ' . fxNumber($laSumaSal, 10, 2)];
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------'];
      $lnFont = 7;
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
            $loPdf->ezText('<b>UCSM-ERP                                    REPORTE DE MOVIMIENTOS POR ALMACEN                       PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                            DEL: ' . $ldInicio . '  AL: ' . $ldFinali . '                    GENERADO:' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>' . fxString($lcAlmacen, 30) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                                                   INGRESOS S/.        SALIDAS S/. </b>', $lnFont);
            $loPdf->ezText('<b>FECHA MOVIMIENTO    ARTICULO  DESCRIPCION                     CANTIDAD     COSTO  CANTIDAD     COSTO   EMP   TIPO</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 76) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   public function omRepMovimientoAlmacenxFechaUnico() {

      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepMovimientoAlmacenxFechaUnico($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepMovimientoAlmacenxFechaUnico();
      return $llOk;
   }

   protected function mxRepMovimientoAlmacenxFechaUnico($p_oSql) {
      $ldInicio = $this->paData['DINICIO1'];
      $ldFinali = $this->paData['DFINALI1'];
      $lcCodAlm = $this->paData['CCODALM1'];
      $lctipArt = $this->paData['CTIPART1'];
      $lcGrupArt = $this->paData['CGRUPART1'];
      $lcTipAnt = $this->paData['CTIPANT1'];
      $i = 0;
      $lcConsulta = "";
      if ($lctipArt != '*') {
         $lcConsulta = $lcConsulta . " AND C.cTipArt = '$lctipArt' ";
      }
      if ($lcGrupArt != '*') {
         $lcConsulta = $lcConsulta . " AND C.cGrupo = '$lcGrupArt' ";
      }
      if ($lcTipAnt != '*') {
         $lcConsulta = $lcConsulta . " AND B.cTipMov = '$lcTipAnt' ";
      }
      $lcSql = "SELECT A.cIdKard, A.cCodArt, A.nCantid, A.nCosTo, A.nPreTot, B.cAlmOri, B.cAlmDes, B.dFecha, B.cCodEmp, B.cTipo, B.cEstado, B.cTipMov, B.cNumMov, C.cDescri, D.cDescri AS cDesOri, E.cDescri AS cDesDes, F.cDescri AS cDesTip, C.cTipArt, C.cGrupo, G.cDescri AS cDesTipArt, H.cDescri AS cDesGru, I.cDescri AS cDesAlm FROM E03DKAR A
                 INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                 INNER JOIN E01MART C ON C.cCodArt = A.cCodArt
                 INNER JOIN E03MALM D ON D.cCodAlm = B.cAlmOri
                 INNER JOIN E03MALM E ON E.cCodAlm = B.cAlmDes
                 LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '101'
                 LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '082' AND G.cCodigo = C.cTipArt
                 LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '083' AND H.cCodigo = C.cGrupo
                 INNER JOIN E03MALM I ON I.cCodAlm = '$lcCodAlm' 
                 WHERE ( B.cAlmOri = '$lcCodAlm' OR B.cAlmDes = '$lcCodAlm' ) AND  B.dFecha::DATE >= '$ldInicio' AND B.dFecha::DATE <= '$ldFinali'  " . $lcConsulta . " ORDER BY B.dFecha, C.cTipArt, C.cGrupo, A.cCodArt";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDKARD' => $laFila[0], 'CCODART' => $laFila[1], 'NCANTID' => $laFila[2],
             'NCOSTO' => $laFila[3], 'NPRETOT' => $laFila[4], 'CALMORI' => $laFila[5], 'CALMDES' => $laFila[6], 'DFECHA' => $laFila[7],
             'CCODEMP' => $laFila[8], 'CTIPO' => $laFila[9], 'CESTADO' => $laFila[10], 'CTIPMOV' => $laFila[11], 'CNUMMOV' => $laFila[12], 'CDESART' => $laFila[13], 'CDESORI' => $laFila[14], 'CDESDES' => $laFila[15], 'CDESTIP' => $laFila[16], 'CTIPART' => $laFila[17], 'CGRUPO' => $laFila[18], 'CDESTIPART' => $laFila[19], 'CDESGRU' => $laFila[20], 'CDESALM' => $laFila[21]];
         $i++;
      }
      //ECHO $lcSql; die;
      //PRINT_R($this->laDatos); 
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR CON LOS FILTROS SELECCIONADOS';
         return false;
      }
      return true;
   }

   protected function mxPrintRepMovimientoAlmacenxFechaUnico() {
      /*
        UCSM-ERP                                               MOVIMIENTOS POR ALMACÉN                                               PAG.:    1
        ERP9999                                            DEL: 9999-99-99  AL: 9999-99-99                                           9999-99-99
        ---------------------------------------------------------------------------------------------------------------------------------------
        INGRESOS S/.             SALIDAS S/.
        ALM FECHA MOVIMIENTO    ARTÍCULO  DESCRIPCIÓN                   MARCA        | CANTIDAD        COSTO | CANTIDAD        COSTO | EMPLEADO
        ---------------------------------------------------------------------------------------------------------------------------------------
        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        1        2         3         4
        TOTAL 99 |             99,999.99 |             99,999.99 |

        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        |            ---------- |            ---------- |
        TOTAL 99 |             99,999.99 |             99,999.99 |
        ---------------------------------------------------------------------------------------------------------------------------------------
        9,999 ARTÍCULOS                                                 GENERAL |             99,999.99 |             99,999.99 |
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345
        1         2         3         4         5         6         7         8         9         10        110       120       130
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $lcAlmacen = $this->laDatos[0]['CDESALM'];
      $ldInicio = $this->paData['DINICIO1'];
      $ldFinali = $this->paData['DFINALI1'];
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcTipArt = $this->laDatos[0]['CTIPART'];
      $lcDesTip = $this->laDatos[0]['CDESTIPART'];
      $lcGrupo = $this->laDatos[0]['CGRUPO'];
      $lcDesGru = $this->laDatos[0]['CDESGRU'];

      $ldDate = date('Y-m-d', time());
      $lcTipTrx = '*';
      $lcCodTrx = '*';
      $laDatos[] = ['<b>* ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
      $laDatos[] = ['<b> * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
      foreach ($this->laDatos as $laFila) {
         if ($lcTipArt != $laFila['CTIPART']) {
            $lnContad = 1;
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIPART'];
            $laDatos[] = ['<b>* ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b> * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcGrupo != $laFila['CGRUPO']) {
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b> * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         //$laDatos[] = ['<b>* '.$laFila['CALMORI'].' - '.trim($laFila['CDESORI']).'</b>']; 
         if ($laFila['CTIPO'] == 'S' || $laFila['CTIPO'] == 'T') {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 36) . fxString(' ', 20) . ' ' . fxNumber($laFila['NCANTID'], 9, 2) . ' ' . fxNumber($laFila['NPRETOT'], 9, 2) . '  ' . $laFila['CCODEMP']];
            $laSumaSal = $laSumaSal + $laFila['NPRETOT'];
         } else {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 36) . ' ' . fxNumber($laFila['NCANTID'], 9, 2) . ' ' . fxNumber($laFila['NPRETOT'], 9, 2) . fxString(' ', 20) . '  ' . $laFila['CCODEMP']];
            $laSumaIng = $laSumaIng + $laFila['NPRETOT'];
         }

         //$laDatos[] = [$laFila['DFECHA'].' '.$laFila['CCODART'].' '. fxString($laFila['CDESART'], 40).' '.fxNumber($laFila['NCANTID'],9,2).' '.fxNumber($laFila['NPRETOT'],9,2).' '.$laFila['CCODEMP']]; 

         $laTmp = $laFila;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['  **TOTAL                                                             ' . fxNumber($laSumaIng, 10, 2) . '           ' . fxNumber($laSumaSal, 10, 2)];
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------'];
      $lnFont = 7;
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
            $loPdf->ezText('<b>UCSM-ERP                                    REPORTE DE MOVIMIENTOS POR ALMACEN                               PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                            DEL: ' . $ldInicio . '  AL: ' . $ldFinali . '                                  ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>' . fxString($lcAlmacen, 30) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                                                                 INGRESOS S/.        SALIDAS S/. </b>', $lnFont);
            $loPdf->ezText('<b>     FECHA    MOVIMIENTO ARTICULO  DESCRIPCION                          CANTIDAD     COSTO  CANTIDAD     COSTO   EMP   </b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 96) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   /// REPORTE MOVIMIENTOS POR CENTRO DE COSTO FRL
   public function omRepMovimientoCentroDeCosto() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepMovimientoCentroDeCosto($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepMovimientoCentroDeCosto();
      return $llOk;
   }

   protected function mxRepMovimientoCentroDeCosto($p_oSql) {
      $ldInicio = $this->paData['DINICIO2'];
      $ldFinali = $this->paData['DFINALI2'];
      $lcCenCos = $this->paData['CCENCOS'];
      $lctipArt = $this->paData['CTIPART2'];
      $lcGrupArt = $this->paData['CGRUPART2'];
      $lcTipAnt = $this->paData['CTIPANT2'];

      $i = 0;
      $lcConsulta = "";

      if ($lctipArt != '*') {
         $lcConsulta = $lcConsulta . " AND C.cTipArt = '$lctipArt' ";
      }
      if ($lcGrupArt != '*') {
         $lcConsulta = $lcConsulta . " AND C.cGrupo = '$lcGrupArt' ";
      }
      if ($lcTipAnt != '*') {
         $lcConsulta = $lcConsulta . " AND B.cTipMov = '$lcTipAnt' ";
      }
      $lcSql = "SELECT A.cIdKard, A.cCodArt, A.nCantid, A.nCosTo, A.nPreTot, B.cAlmOri, B.cAlmDes, B.dFecha, B.cCodEmp, B.cTipo, B.cEstado, B.cTipMov, B.cNumMov, C.cDescri, D.cDescri AS cDesOri, E.cDescri AS cDesDes, F.cDescri AS cDesTip,
                C.cTipArt, C.cGrupo, G.cDescri AS cDesTipArt, H.cDescri AS cDesGru, B.cCenCos, I.cDescri AS cDesCco FROM E03DKAR A
                 INNER JOIN E03MKAR B ON B.cIdKard = A.cIdKard
                 INNER JOIN E01MART C ON C.cCodArt = A.cCodArt
                 INNER JOIN E03MALM D ON D.cCodAlm = B.cAlmOri
                 INNER JOIN E03MALM E ON E.cCodAlm = B.cAlmDes
                 LEFT OUTER JOIN V_S01TTAB F ON F.cCodigo = B.cTipo AND F.cCodTab = '101'
                 LEFT OUTER JOIN V_S01TTAB G ON G.cCodTab = '082' AND G.cCodigo = C.cTipArt
                 LEFT OUTER JOIN V_S01TTAB H ON H.cCodTab = '083' AND H.cCodigo = C.cGrupo
                 INNER JOIN S01TCCO I ON I.cCenCos = B.cCenCos
                 WHERE B.cCenCos = '$lcCenCos'  AND  B.dFecha::DATE >= '$ldInicio' AND B.dFecha::DATE <= '$ldFinali'  " . $lcConsulta . "    ORDER BY B.dFecha, C.cTipArt, C.cGrupo, A.cCodArt  ";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CIDKARD' => $laFila[0], 'CCODART' => $laFila[1], 'NCANTID' => $laFila[2],
             'NCOSTO' => $laFila[3], 'NPRETOT' => $laFila[4], 'CALMORI' => $laFila[5], 'CALMDES' => $laFila[6], 'DFECHA' => $laFila[7],
             'CCODEMP' => $laFila[8], 'CTIPO' => $laFila[9], 'CESTADO' => $laFila[10], 'CTIPMOV' => $laFila[11], 'CNUMMOV' => $laFila[12],
             'CDESART' => $laFila[13], 'CDESORI' => $laFila[14], 'CDESDES' => $laFila[15], 'CDESTIP' => $laFila[16], 'CTIPART' => $laFila[17],
             'CGRUPO' => $laFila[18], 'CDESTIPART' => $laFila[19], 'CDESGRU' => $laFila[20], 'CCENCOS' => $laFila[21], 'CDESCCO' => $laFila[22]];
         $i++;
      }
      //ECHO $lcSql; die;
      //PRINT_R($this->laDatos); 
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR CON LOS FILTROS SELECCIONADOS';
         return false;
      }
      return true;
   }

   protected function mxPrintRepMovimientoCentroDeCosto() {
      /*
        UCSM-ERP                                               MOVIMIENTOS POR ALMACÉN                                               PAG.:    1
        ERP9999                                            DEL: 9999-99-99  AL: 9999-99-99                                           9999-99-99
        ---------------------------------------------------------------------------------------------------------------------------------------
        INGRESOS S/.             SALIDAS S/.
        ALM FECHA MOVIMIENTO    ARTÍCULO  DESCRIPCIÓN                   MARCA        | CANTIDAD        COSTO | CANTIDAD        COSTO | EMPLEADO
        ---------------------------------------------------------------------------------------------------------------------------------------
        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        1        2         3         4
        TOTAL 99 |             99,999.99 |             99,999.99 |

        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        |            ---------- |            ---------- |
        TOTAL 99 |             99,999.99 |             99,999.99 |
        ---------------------------------------------------------------------------------------------------------------------------------------
        9,999 ARTÍCULOS                                                 GENERAL |             99,999.99 |             99,999.99 |
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345
        1         2         3         4         5         6         7         8         9         10        110       120       130
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $lcCenCos = $this->laDatos[0]['CCENCOS'];
      $ldInicio = $this->paData['DINICIO2'];
      $ldFinali = $this->paData['DFINALI2'];

      $lcCodAlm = $this->laDatos[0]['CDESDES'];
      $lcDesAlm = $this->laDatos[0]['CALMDES'];
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcTipArt = $this->laDatos[0]['CTIPART'];
      $lcDesTip = $this->laDatos[0]['CDESTIPART'];
      $lcGrupo = $this->laDatos[0]['CGRUPO'];
      $lcDesGru = $this->laDatos[0]['CDESGRU'];

      $ldDate = date('Y-m-d', time());
      $lcTipTrx = '*';
      $lcCodTrx = '*';
      $laDatos[] = ['<b>* ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
      $laDatos[] = ['<b> * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
      foreach ($this->laDatos as $laFila) {
         if ($lcTipArt != $laFila['CTIPART']) {
            $lnContad = 1;
            $lcTipArt = $laFila['CTIPART'];
            $lcDesTip = $laFila['CDESTIPART'];
            $laDatos[] = ['<b>* ' . $lcTipArt . ' ' . $lcDesTip . fxString('', 50) . '</b>'];
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b> * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         if ($lcGrupo != $laFila['CGRUPO']) {
            $lcGrupo = $laFila['CGRUPO'];
            $lcDesGru = $laFila['CDESGRU'];
            $laDatos[] = ['<b> * ' . $lcGrupo . ' ' . $lcDesGru . fxString('', 50) . '</b>'];
         }
         //$laDatos[] = ['<b>* '.$laFila['CALMORI'].' - '.trim($laFila['CDESORI']).'</b>']; 
         if ($laFila['CTIPO'] == 'S' || $laFila['CTIPO'] == 'T') {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 36) . fxString(' ', 20) . ' ' . fxNumber($laFila['NCANTID'], 9, 2) . ' ' . fxNumber($laFila['NPRETOT'], 9, 2) . '  ' . $laFila['CCODEMP']];
            $laSumaSal = $laSumaSal + $laFila['NPRETOT'];
         } else {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESART'], 36) . ' ' . fxNumber($laFila['NCANTID'], 9, 2) . ' ' . fxNumber($laFila['NPRETOT'], 9, 2) . fxString(' ', 20) . '  ' . $laFila['CCODEMP']];
            $laSumaIng = $laSumaIng + $laFila['NPRETOT'];
         }

         //$laDatos[] = [$laFila['DFECHA'].' '.$laFila['CCODART'].' '. fxString($laFila['CDESART'], 40).' '.fxNumber($laFila['NCANTID'],9,2).' '.fxNumber($laFila['NPRETOT'],9,2).' '.$laFila['CCODEMP']]; 

         $laTmp = $laFila;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['  **TOTAL                                                             ' . fxNumber($laSumaIng, 10, 2) . '           ' . fxNumber($laSumaSal, 10, 2)];
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------'];
      $lnFont = 7;
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
            $loPdf->ezText('<b>UCSM-ERP                                    REPORTE DE MOVIMIENTOS POR CENTRO DE COSTO                               PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                            DEL: ' . $ldInicio . '  AL: ' . $ldFinali . '                                  ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>' . fxString($lcCenCos, 30) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                                                                 INGRESOS S/.        SALIDAS S/. </b>', $lnFont);
            $loPdf->ezText('<b>     FECHA    MOVIMIENTO ARTICULO  DESCRIPCION                          CANTIDAD     COSTO  CANTIDAD     COSTO   EMP   </b>', $lnFont);
            $loPdf->ezText('<b>-----------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 96) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // Reporte de kardex valorado
   // 2018-05-30 FRL Creacion
   public function omRepKardexValorado() {
      $llOk = $this->mxValParamRepRepKardex();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepRepKardex($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepRepKardex();
      return $llOk;
   }

   protected function mxValParamRepRepKardex() {
      $loDate = new CDate();
      if (!$loDate->mxValDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL ES MENOR QUE FECHA INICIAL';
         return false;
      } elseif (empty($this->paData['CCODALM'])) {
         $this->pcError = 'CODIGO DE ALMACEN NO DEFINIDO';
         return false;
      } elseif (empty($this->paData['CCODART'])) {
         $this->pcError = 'CODIGO DE ARTICULO NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxRepRepKardex($p_oSql) {
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcCodAlm = $this->paData['CCODALM'];
      $lcCodArt = $this->paData['CCODART'];
      $i = 0;
      $lcSql = "SELECT t_cIdKard, t_cCodArt, t_cDesArt, t_cUnidad, t_nCantid, t_nStock, t_nCosto, t_nPreTot, t_nCosSal, t_cAlmOri, 
                       t_cDesOri, t_cAlmDes, t_cDesDes, t_dFecha, t_cCodEmp, t_cNombre, t_cTipo, t_cDesTip, t_cTipMov, t_cNumMov,
                       t_cNroRuc, t_cRazSoc
                       FROM F_E03MKAR_2('$lcCodAlm','$lcCodArt','$ldInicio','$ldFinali')";
      //print_r($lcSql);
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR CON LOS FILTROS SELECCIONADOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)) {
         if (trim($laFila[0]) == 'ERROR') {
            $this->pcError = trim($laFila[2]);
            return false;
         }
         $this->laDatos[] = ['CIDKARD' => $laFila[0], 'CCODART' => $laFila[1], 'CDESART' => $laFila[2], 'CUNIDAD' => $laFila[3],
                             'NCANTID' => $laFila[4], 'NSTOCK'  => $laFila[5], 'NCOSTO'  => $laFila[6], 'NPRETOT' => $laFila[7],
                             'NCOSSAL' => $laFila[8], 'CALMORI' => $laFila[9], 'CDESORI' => $laFila[10],'CALMDES' => $laFila[11],
                             'CDESDES' => $laFila[12],'DFECHA'  => $laFila[13],'CCODEMP' => $laFila[14],'CNOMBRE' => $laFila[15],
                             'CTIPO'   => $laFila[16],'CDESTIP' => $laFila[17],'CTIPMOV' => $laFila[18],'CNUMMOV' => $laFila[19],
                             'CNRORUC' => $laFila[20],'CRAZSOC' => $laFila[21]];
      }
      return true;
   }

   protected function mxPrintRepRepKardex() {
      /*
        UCSM-ERP                                                         KARDEX VALORADO                                                       PAG:     1
        ALM1210                                                  DEL: 2018-05-01  AL: 2018-05-02                                               2018-05-30
        -------------------------------------------------------------------------------------------------------------------------------------------------
        INGRESOS                SALIDAS                SALDOS
        #   FECHA      MOVIMIENTO    NOMBRE/RAZON SOCIAL               CANTIDAD     IMPORTE     CANTIDAD    IMPORTE    CANTIDAD     IMPORTE       COSTO
        -------------------------------------------------------------------------------------------------------------------------------------------------
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345
        1         2         3         4         5         6         7         8         9         10        110       120       130
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $lnSumIngC = 0.00;
      $lcSumSalC = 0.00;
      $lnCanStk = 0.00;
      $lnSumStk = 0.00;
      $lnTotlMov = 0.00;
      $lnContad = 1;
      $lcAlmacen = $this->laDatos[1]['CDESORI'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcCodArt = $this->laDatos[0]['CCODART'];
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcUnidad = $this->laDatos[0]['CUNIDAD'];
      $ldDate = date('Y-m-d', time());
      $lcTipTrx = '*';
      $lcCodTrx = '*'; //2018-01-03 NS-2018000001 ABRIL/RAMIREZ/RICARDO ALONSO 500.00 9.35 587,049.00 10,978.34 0.0187
      foreach ($this->laDatos as $laFila) {
         if ($laFila['CIDKARD'] == '00000000') {
            $laDatos[] = [fxString('', 4) . ' ' . $laFila['DFECHA'] . ' ' . fxString('', 13) . ' ' .
                fxStringFixed('SALDO ANTERIOR AL '.$this->paData['DINICIO'], 31) . fxString(' ', 48) . fxNumber($laFila['NSTOCK'], 12, 2) . 
                fxNumber($laFila['NCOSSAL'], 12, 2) . fxNumber($laFila['NCOSTO'], 12, 4)];
                continue;
         }
         $lnTotlMov ++;
         if ($laFila['CALMDES'] == $this->paData['CCODALM']) {
            ($laFila['CTIPMOV'] == 'NI' || $laFila['CTIPMOV'] == 'GV') ? $ingreso = fxStringFixed($laFila['CRAZSOC'], 31) : $ingreso = fxStringFixed($laFila['CNOMBRE'], 31);
            $laDatos[] = [fxNumber($lnContad, 4, 0) . ' ' . $laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' .
                $ingreso . fxNumber($laFila['NCANTID'], 12, 2) . fxNumber($laFila['NPRETOT'], 12, 2) .
                fxString(' ', 24) . fxNumber($laFila['NSTOCK'], 12, 2) . fxNumber($laFila['NCOSSAL'], 12, 2) .
                fxNumber($laFila['NCOSTO'], 12, 4)];
            $lnSumIngC = $lnSumIngC + $laFila['NCANTID'];
            $laSumaIng = $laSumaIng + $laFila['NPRETOT'];
            $lnContad ++;
         } else {
            $laDatos[] = [fxNumber($lnContad, 4, 0) . ' ' . $laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' .
                fxStringFixed($laFila['CNOMBRE'], 31) . fxString(' ', 24) . fxNumber($laFila['NCANTID'], 12, 2) .
                fxNumber($laFila['NPRETOT'], 12, 2) . fxNumber($laFila['NSTOCK'], 12, 2) . fxNumber($laFila['NCOSSAL'], 12, 2) .
                fxNumber($laFila['NCOSTO'], 12, 4)];
            $lcSumSalC = $lcSumSalC + $laFila['NCANTID'];
            $laSumaSal = $laSumaSal + $laFila['NPRETOT'];
            $lnContad ++;
         }
         $lnCanStk += $laFila['NSTOCK'];
         $lnSumStk += $laFila['NCOSSAL'];
         $laTmp = $laFila;
      }
      //$laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = [fxString('-', 145)];
      $laDatos[] = ['* '. fxNumber($lnTotlMov, 12, 0) . fxString(' MOVIMIENTOS  ', 15) . ' '.fxString('TOTAL ARTICULO '.$lcCodArt, 31) . fxNumber($lnSumIngC, 12, 2) . fxNumber($laSumaIng, 12, 2) . fxNumber($lcSumSalC, 12, 2) . fxNumber($laSumaSal, 12, 2) . fxNumber($this->laDatos[count($this->laDatos) - 1]['NSTOCK'], 12, 2) . fxNumber($this->laDatos[count($this->laDatos) - 1]['NCOSSAL'], 12, 2) . fxNumber($this->laDatos[count($this->laDatos) - 1]['NCOSTO'], 12, 4)];
      $laDatos[] = [fxString('-', 145)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1, 1);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         if ($llTitulo) {
            // Titulo
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                                                         KARDEX VALORADO                                                       PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ALM1210                                                  DEL: ' . $ldInicio . '  AL: ' . $ldFinali . '                                               ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>                                                                         INGRESOS                SALIDAS                SALDOS                   </b>', $lnFont);
            $loPdf->ezText('<b>  #   FECHA      MOVIMIENTO    NOMBRE/RAZON SOCIAL               CANTIDAD     IMPORTE    CANTIDAD     IMPORTE    CANTIDAD     IMPORTE       COSTO</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>' . fxString($lcAlmacen, 30) . '</b>', $lnFont);
            $loPdf->ezText('<b>' . $lcCodArt . ' ' . $lcDesArt . '  UNIDAD:' . $lcUnidad . '</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*') {
            $loPdf->ezText('<b>' . $laFila[0] .'</b>', $lnFont);
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

   protected function mxPrintRepRepKardexOLD($p_oSql) {
      /*
        UCSM-ERP                                               MOVIMIENTOS POR ALMACÉN                                               PAG.:    1
        ERP9999                                            DEL: 9999-99-99  AL: 9999-99-99                                           9999-99-99
        ---------------------------------------------------------------------------------------------------------------------------------------
        INGRESOS S/.             SALIDAS S/.
        ALM FECHA MOVIMIENTO    ARTÍCULO  DESCRIPCIÓN                   MARCA        | CANTIDAD        COSTO | CANTIDAD        COSTO | EMPLEADO
        ---------------------------------------------------------------------------------------------------------------------------------------
        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        1        2         3         4
        TOTAL 99 |             99,999.99 |             99,999.99 |

        99 123456789012345
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        99/99 1234567890123 12345789 1234567890123457890123456789012345678901234 | 999,999.999 99,999.99 | 999,999.999 99,999.99 | 123456
        |            ---------- |            ---------- |
        TOTAL 99 |             99,999.99 |             99,999.99 |
        ---------------------------------------------------------------------------------------------------------------------------------------
        9,999 ARTÍCULOS                                                 GENERAL |             99,999.99 |             99,999.99 |
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345
        1         2         3         4         5         6         7         8         9         10        110       120       130
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $lnSumIngC = 0.00;
      $lcSumSalC = 0.00;
      $lnTotlMov = 0.00;
      $lcAlmacen = $this->laDatos[1]['CDESORI'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcCodArt = $this->laDatos[0]['CCODART'];
      $lcDesArt = $this->laDatos[0]['CDESCRI'];
      $lcUnidad = $this->laDatos[0]['CUNIDAD'];
      $ldDate = date('Y-m-d', time());
      $lcTipTrx = '*';
      $lcCodTrx = '*';
      foreach ($this->laDatos as $laFila) {
         $lnTotlMov ++;
         $lcNombre = fxString($laFila['CNOMBRE'], 24);
         $i = substr_count($lcNombre, 'Ñ');
         $lcNombre = fxString($lcNombre, 24 + $i);
         if ($laFila['CTIPO'] == 'I' || $laFila['CTIPO'] == 'R') {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . $lcNombre . '|' . fxNumber($laFila['NCANTID'], 12, 2) . ' |' . fxNumber($laFila['NPRETOT'], 10, 2) . fxString(' ', 23) . ' |' . fxNumber($laFila['NSTOCK'], 10, 2) . ' |' . fxNumber($laFila['NPRECAL'], 9, 2) . '|' . $laFila['NCOSTO']];
            $lnSumIngC = $lnSumIngC + $laFila['NCANTID'];
            $laSumaIng = $laSumaIng + $laFila['NPRETOT'];
         } else {
            $laDatos[] = [$laFila['DFECHA'] . ' ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . $lcNombre . '|' . fxString(' ', 24) . '|' . fxNumber($laFila['NCANTID'], 12, 2) . '|' . fxNumber($laFila['NPRETOT'], 10, 2) . ' |' . fxNumber($laFila['NSTOCK'], 10, 2) . ' |' . fxNumber($laFila['NPRECAL'], 9, 2) . '|' . $laFila['NCOSTO']];
            $lcSumSalC = $lcSumSalC + $laFila['NCANTID'];
            $laSumaSal = $laSumaSal + $laFila['NPRETOT'];
         }

         //$laDatos[] = [$laFila['DFECHA'].' '.$laFila['CCODART'].' '. fxString($laFila['CDESART'], 40).' '.fxNumber($laFila['NCANTID'],9,2).' '.fxNumber($laFila['NPRETOT'],9,2).' '.$laFila['CCODEMP']]; 

         $laTmp = $laFila;
      }
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = ['  **TOTAL       N0 MOVIMIENTOS  ' . fxNumber($lnTotlMov, 12, 0) . ' | ' . fxNumber($lnSumIngC, 12, 2) . '| ' . fxNumber($laSumaIng, 12, 2) . '| ' . fxNumber($lcSumSalC, 12, 2) . '| ' . fxNumber($laSumaSal, 10, 2) . '| '];
      $laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------'];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1, 1);
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
            $loPdf->ezText('<b>UCSM-ERP                                        KARDEX VALORADO                                                      PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>                                            DEL: ' . $ldInicio . '  AL: ' . $ldFinali . '                                 GENERADO:' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>' . fxString($lcAlmacen, 30) . '</b>', $lnFont);
            $loPdf->ezText('<b>' . $lcCodArt . ' ' . $lcDesArt . '  UNIDAD:' . $lcUnidad . '</b>', $lnFont);
            $loPdf->ezText('<b>                                                           INGRESOS                  SALIDAS                SALDOS             </b>', $lnFont);
            $loPdf->ezText('<b>FECHA      MOVIMIENTO    NOMBRE/RAZON SOCIAL     |    CANTIDAD     IMPORTE|    CANTIDAD   IMPORTE |  CANTIDAD    IMPORTE | COSTO</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 96) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // IMPRIME INVENTARIO POR ALAMCEN - ALMACEN - CREACION - FERNANDO - 2018-05-22
   public function omReporteMovimientoMensual() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteMovimientoMensual($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteMovimientoMensual($loSql);
      return $llOk;
   }

   protected function mxReporteMovimientoMensual($p_oSql) {
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcAlmIni = $this->paData['CALMINI'];
      $lcAlmFin = $this->paData['CALMFIN'];
      $lcTipArt = $this->paData['CTIPART'];
      $lcGruArt = $this->paData['CGRUART'];
      $lcSql = "SELECT t_cCodAlm, t_cDesAlm, t_nSalIni, t_nIngres, t_nConSum, t_nTraHac, t_nTraDes, t_nOtros, t_nSalFin FROM F_E03MKAR_3('$lcAlmIni','$lcAlmFin','$ldInicio','$ldFinali','$lcTipArt','$lcGruArt')";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODALM' => $laTmp[0], 'CDESALM' => $laTmp[1], 'NSALINI' => $laTmp[2], 'NINGRES' => $laTmp[3],
             'NCONSUM' => $laTmp[4], 'NTRAHAC' => $laTmp[5], 'NTRADES' => $laTmp[6], 'NOTROS' => $laTmp[7],
             'NSALFIN' => $laTmp[8]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintReporteMovimientoMensual($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $ldInicio = $this->paData['DINICIO'];
      $lcDesTip = $this->paData['CDESTIP'];
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $loDate = new CDate();
      $date = $loDate->dateText($ldInicio);
      $mes = explode(" ", $date);

      foreach ($this->laDatos as $laFila) {
         //$loPdf->ezText('<b>* ' .$lcTipArt[$i].' '.$lcDesTip[$i].fxString('', 50). '</b>');
         $laDatos[] = [fxString($lnContad, 3) . $laFila['CCODALM'] . ' ' . fxStringFixed($laFila['CDESALM'], 16) . ' ' . fxNumber($laFila['NSALINI'], 14, 2) . ' ' .
             fxNumber($laFila['NINGRES'], 14, 2) . fxNumber($laFila['NCONSUM'], 14, 2) . fxNumber($laFila['NTRAHAC'], 11, 2) . fxNumber($laFila['NTRADES'], 11, 2) . fxNumber($laFila['NOTROS'], 11, 4) .
             fxNumber($laFila['NSALFIN'], 14, 2)];
         $lnContad += 1;
      }
      $laDatos[] = ['<b>-------------------------------------------------------------------------------------------------------------------</b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];

      $lnFont = 7.8;
      $loPdf = new Cezpdf('A4', 'portrait');
      $loPdf->selectFont('fonts/Courier.afm', 6);
      $loPdf->ezSetCmMargins(1, 1, 1, 1);
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
            $loPdf->ezText('<b>UCSM-ERP                     Resumen del Movimiento Mensual de Almacen - Mes de ' . $mes[2] . '                    PAG: ' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ALM1210                                          '.fxStringFixed($lcDesTip,30).'                          ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------</b>');
            $loPdf->ezText('<b>#  ALM DESCRIPCION       Saldo Inicial       Ingresos       Salidas      Hacia      Desde      Otros   Saldo Final</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            //$loPdf->ezText('<b>* ' .$lcTipArt[$i].' '.$lcDesTip[$i].fxString('', 50). '</b>');sfsdf12344567891234567891234  
            //$llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText('<b>' . $laFila[0] . '</b>', $lnFont);
         } else {
            $loPdf->ezText($laFila[0], $lnFont);
         }
         $lnRow++;
         $llTitulo = ($lnRow == 76) ? true : false;
      }

      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // REPORTE DE MOVIMIENTOS POR ALMACÉN - ANDREA
   public function omRepMovimientoDeAlmacen() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepMovimientoDeAlmacen($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepMovimientoDeAlmacen($loSql);
      return $llOk;
   }

   protected function mxRepMovimientoDeAlmacen($p_oSql) {
      $ldInicio = $this->paData['DINICIO3'];
      $ldFinali = $this->paData['DFINALI3'];
      $lcTipMov = $this->paData['CTIPMOV'];
      $lcCodAlm = $this->paData['CCODALM2'];
      $lcTipArt = $this->paData['CTIPART3'];
      $lcGruArt = $this->paData['CGRUART3'];
      $lcSql = "SELECT A.cIdKard, A.cAlmOri, B.cDescri AS cDesOri, A.cAlmDes, C.cDescri AS cDesDes, A.dFecha, A.cCodEmp, A.cTipo,
                       A.cEstado, A.cTipMov, D.cDescri AS cDesTip, A.cNumMov, A.cCencos, E.cDescri AS cDesCco, TRIM(E.cCodAnt) AS cCCoAnt,
                       A.cDescri AS cDesKar
                FROM E03MKAR A
                INNER JOIN E03MALM B ON B.cCodAlm = A.cAlmOri
                INNER JOIN E03MALM C ON C.cCodAlm = A.cAlmDes
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '101' AND D.cCodigo = A.cTipo
                INNER JOIN S01TCCO E ON E.cCenCos = A.cCenCos
                WHERE (A.cAlmOri = '$lcCodAlm' OR A.cAlmDes = '$lcCodAlm') AND A.dFecha BETWEEN '$ldInicio' AND '$ldFinali' 
                      AND A.cEstado != 'X'";
      if ($lcTipMov != '*') {
         $lcSql .= "AND A.cTipMov = '$lcTipMov' ";
      }
      $lcSql .= "ORDER BY A.dFecha, A.cNumMov";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false) {
         $this->pcError = 'ERROR AL CONSULTAR MOVIMIENTOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY MOVIMIENTOS PARA IMPRIMIR CON LOS FILTROS SELECCIONADOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)) {
         if ($laFila[9] == 'NI') {
            $lcSql = "SELECT A.cCodArt, B.cDescri, A.nCantid, CASE WHEN A.nCantid != 0 THEN (A.nMonto/A.nCantid)::NUMERIC(16,6) ELSE 0 END,
                          A.nMonto, B.cUnidad, B.cTipArt, B.cGrupo FROM E01DNIN A ";
         } else {
            $lcSql = "SELECT A.cCodArt, B.cDescri, A.nCantid, A.nCosTo, A.nPreTot, B.cUnidad, B.cTipArt, B.cGrupo FROM E03DKAR A ";
         }
         $lcSql .= "INNER JOIN E01MART B ON B.cCodArt = A.cCodArt WHERE A.cEstado != 'X' ";
         if ($laFila[9] == 'NI') {
            $lcSql .= "AND A.cNotIng IN (SELECT cNotIng FROM E01MNIN WHERE cIdKard = '{$laFila[0]}' AND cEstado != 'X') ";
         } else {
            $lcSql .= "AND A.cIdKard = '{$laFila[0]}' ";
         }
         if ($lcTipArt != '*') {
            $lcSql .= "AND B.cTipArt = '$lcTipArt' ";
         }
         if ($lcGruArt != '*') {
            $lcSql .= "AND B.cGrupo = '$lcGruArt' ";
         }
         $lcSql .= "ORDER BY A.nSerial";
         $R2 = $p_oSql->omExec($lcSql);
         if ($R2 == false) {
            $this->pcError = 'ERROR AL CONSULTAR DETALLE DE MOVIMIENTOS';
            return false;
         }
         while ($laTmp = $p_oSql->fetch($R2)) {
            $this->laDatos[] = ['CIDKARD' => $laFila[0], 'CALMORI' => $laFila[1], 'CDESORI' => $laFila[2], 'CALMDES' => $laFila[3],
                                'CDESDES' => $laFila[4], 'DFECHA'  => $laFila[5], 'CCODEMP' => $laFila[6], 'CTIPO'   => $laFila[7],
                                'CESTADO' => $laFila[8], 'CTIPMOV' => $laFila[9], 'CDESTIP' => $laFila[10],'CNUMMOV' => $laFila[11],
                                'CCENCOS' => $laFila[12],'CDESCCO' => $laFila[13],'CCCOANT' => $laFila[14],'CDESKAR' => $laFila[15],
                                'CCODART' => $laTmp[0],  'CDESCRI' => $laTmp[1],  'NCANTID' => $laTmp[2],  'NCOSTO'  => $laTmp[3],
                                'NPRETOT' => $laTmp[4],  'CUNIDAD' => $laTmp[5],  'CTIPART' => $laTmp[6],  'CGRUPO'  => $laTmp[7]];
         }
      }
      return true;
   }

   protected function mxPrintRepMovimientoDeAlmacen($p_oSql) {
      /*
        UCSM-ERP                                         MOVIMIENTOS POR ALMACÉN                                         PAG.:    1
        ERP9999                                     DEL: 9999-99-99  AL: 9999-99-99                                      9999-99-99
        ---------------------------------------------------------------------------------------------------------------------------
        MOVIMIENTO    AL. ARTICULO DESCRIPCION                             FECHA            CANTIDAD UNI. UNIT.S/.     TOTAL S/.
        ---------------------------------------------------------------------------------------------------------------------------
       * XX-9999999999 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX 9999/99/99   999   XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX         
        99 99XX9999 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX 9999/99/99     999.99   XXX  999,999.9999 999,999.9999
        -------------
        TOTAL MOVIMIENTO XX-9999999999              999,999.9999

       * XX-9999999999 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX 9999/99/99   999   XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX         
        99 99XX9999 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX 9999/99/99     999.99   XXX  999,999.9999 999,999.9999
        -------------
        TOTAL MOVIMIENTO XX-9999999999              999,999.9999

       * XX-9999999999 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX 9999/99/99   999   XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX         
        99 99XX9999 XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX 9999/99/99     999.99   XXX  999,999.9999 999,999.9999
        -------------
        TOTAL MOVIMIENTO XX-9999999999              999,999.9999
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123
        1         2         3         4         5         6         7         8         9         10        110       120
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $ldInicio = $this->paData['DINICIO3'];
      $ldFinali = $this->paData['DFINALI3'];
      $lcCodAlm = $this->paData['CCODALM2'];
      $ldDate = date('Y-m-d', time());
      $lcIdKard = '*';
      $lcTipMov = '';
      $lcNumMov = '';
      $lntotpar = 0;
      $lntotal = 0;
      foreach ($this->laDatos as $laFila) {
         $laFila = array_map("utf8_decode", $laFila);
         if ($lcIdKard == '*') {
            $laDatos[] = ['<b>* ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] .' '. fxStringFixed($laFila['CDESKAR'], 40) . ' ' . $laFila['DFECHA'] . ' ' . fxStringCenter($laFila['CCCOANT'], 8) . ' ' . fxStringFixed($laFila['CDESCCO'], 44) . '</b>'];
            $lcIdKard = $laFila['CIDKARD'];
            $lcTipMov = $laFila['CTIPMOV'];
            $lcNumMov = $laFila['CNUMMOV'];
         } elseif ($lcIdKard != $laFila['CIDKARD']) {
            $laDatos[] = ['<b>                                                                                                               ----------'.'</b>'];
            $laDatos[] = ['<b>                                                                TOTAL MOVIMIENTO ' . $lcTipMov . '-' . $lcNumMov . '              ' . fxNumber($lntotpar, 13, 2).'</b>'];
            $lcTipMov = $laFila['CTIPMOV'];
            $lcNumMov = $laFila['CNUMMOV'];
            $laDatos[] = [''];
            $laDatos[] = ['<b>* ' . $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] .' '. fxStringFixed($laFila['CDESKAR'], 40) . ' ' . $laFila['DFECHA'] . ' ' . fxStringCenter($laFila['CCCOANT'], 8) . ' ' . fxStringFixed($laFila['CDESCCO'], 44) . '</b>'];
            $lcIdKard = $laFila['CIDKARD'];
            $lntotal = $lntotal + $lntotpar;
            $lntotpar = 0;
         }
         $laDatos[] = ['             ' . $lcCodAlm . ' ' . $laFila['CCODART'] . ' ' . fxStringFixed($laFila['CDESCRI'], 43) . ' ' . $laFila['DFECHA'] . '     ' .
             fxString($laFila['NCANTID'], 6) . '   ' . $laFila['CUNIDAD'] . ' ' . fxNumber($laFila['NCOSTO'], 10, 2) . ' ' . fxNumber($laFila['NPRETOT'], 12, 2)];
         $lntotpar = $lntotpar + $laFila['NPRETOT'];
         $laTmp = $laFila;
      }
      $lntotal = $lntotal + $lntotpar;
      $laDatos[] = ['<b>                                                                                                               ----------'.'</b>'];
      $laDatos[] = ['<b>                                                                TOTAL MOVIMIENTO ' . $lcTipMov . '-' . $lcNumMov . '              ' . fxNumber($lntotpar, 13, 2).'</b>'];
      $laDatos[] = [' '];
      $laDatos[] = ['<b>-------------------------------------------------------------------------------------------------------------------------</b>'];
      $laDatos[] = ['<b>                                                                TOTAL GENERAL                               ' . fxNumber($lntotal, 13, 2).'</b>'];
      $lnFont = 7;
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
            $loPdf->ezText('<b>UCSM-ERP                                         MOVIMIENTOS DE ALMACEN                                        PAG.: ' . fxNumber($lnPag, 4, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>ALM1210                                     DEL: ' . $ldInicio . '  AL: ' . $ldFinali . '                                    ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>MOVIMIENTO   AL. ARTICULO DESCRIPCION                         FECHA             CANTIDAD   UNI.   UNIT.S/.   TOTAL S/.   </b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         $loPdf->ezText($laFila[0], $lnFont);
         $lnRow++;
         $llTitulo = ($lnRow == 96 && count($laDatos) != (96*$lnPag)) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   public function omValidarUsuarioAlmacen() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxValidarUsuarioAlmacen($loSql);
      $loSql->omDisconnect();
      return $llOk;
   }

   protected function mxValidarUsuarioAlmacen($p_oSql) {
      //TRAE ALAMCEN DE USUARIO ORIGEN ENCARGADO
      $lcCodUsu = $this->paData['CCODUSU'];
      $lcSql = "SELECT A.cCodAlm, TRIM(B.cDescri) FROM E03PUSU A
               INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm
               WHERE A.cCodUsu = '$lcCodUsu'";
      $RS = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paCodAlm[] = ['CCODALM' => $laFila[0], 'CDESALM' => $laFila[1]];
         $i++;
      }
      if ($i == 0) {
         $this->pcError = "USUARIO NO ESTÁ ENCARGADO DE NINGÚN SUBALMACÉN";
         return false;
      }
      $this->paData['CCODALM'] = $this->paCodAlm[0]['CCODALM'];
      $this->paData['CDESALM'] = $this->paCodAlm[0]['CDESALM'];
      return true;
   }

   // IMPRIME NOTA DE INGRESO - LOGISTICA - CREACION - ALBERTO - 2018-02-27
   public function omPrintNotaSalida() {
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintNotaSalida($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepNotaSalida($loSql);
      return $llOk;
   }

   protected function mxPrintNotaSalida($p_oSql) {
      $lcIdKard = $this->paData['CIDKARD'];
      $lcSql = "SELECT cIdKard, dFecha, TRIM(cAlmOri), TRIM(cAlmDes), TRIM(cDesAlm), TRIM(cAlDesc), TRIM(cCodEmp), cNomEmp, 
                       TRIM(cCodAlu), cNomAlu, cNroBol, cNroTra, cDescri, cCodArt, cDesArt, nCantid, nCosto, cCenCos, cDesCco, 
                       cTipo, cDesTip, cNumMov, cTipMov, cIdRequ
                FROM F_E03MKAR_1('$lcIdKard')";
      $R1 = $p_oSql->omExec($lcSql);
      $i = 0;
      while ($laTmp = $p_oSql->fetch($R1)) {
         if (isset($this->paData['CTIPART'])){
          $lcSql = "SELECT cTipArt FROM E01MART WHERE cCodArt = '$laTmp[13]'";
          $R2 = $p_oSql->omExec($lcSql);
          $llTipArt = $p_oSql->fetch($R2);
          if (TRIM($llTipArt[0]) == TRIM($this->paData['CTIPART'])){
            $this->paDatos[] = ['CIDKARD' => $laTmp[0], 'DFECHA'  => $laTmp[1], 'CALMORI' => $laTmp[2], 'CALMDES' => $laTmp[3],
                                'CDESALM' => $laTmp[4], 'CALDESC' => $laTmp[5], 'CCODEMP' => $laTmp[6], 'CNOMEMP' => $laTmp[7],
                                'CCODALU' => $laTmp[8], 'CNOMALU' => $laTmp[9], 'CNROBOL' => $laTmp[10],'CNROTRA' => $laTmp[11],
                                'CDESCRI' => $laTmp[12],'CCODART' => $laTmp[13],'CDESART' => $laTmp[14],'NCANTID' => $laTmp[15],
                                'NCOSTO'  => $laTmp[16],'CCENCOS' => $laTmp[17],'CDESCCO' => $laTmp[18],'CTIPO'   => $laTmp[19],
                                'CDESTIP' => $laTmp[20],'CNUMMOV' => $laTmp[21],'CTIPMOV' => $laTmp[22],'CIDREQU' => $laTmp[23]];
            $i++;
          }
        } else {
          $this->paDatos[] = ['CIDKARD' => $laTmp[0], 'DFECHA'  => $laTmp[1], 'CALMORI' => $laTmp[2], 'CALMDES' => $laTmp[3],
                                'CDESALM' => $laTmp[4], 'CALDESC' => $laTmp[5], 'CCODEMP' => $laTmp[6], 'CNOMEMP' => $laTmp[7],
                                'CCODALU' => $laTmp[8], 'CNOMALU' => $laTmp[9], 'CNROBOL' => $laTmp[10],'CNROTRA' => $laTmp[11],
                                'CDESCRI' => $laTmp[12],'CCODART' => $laTmp[13],'CDESART' => $laTmp[14],'NCANTID' => $laTmp[15],
                                'NCOSTO'  => $laTmp[16],'CCENCOS' => $laTmp[17],'CDESCCO' => $laTmp[18],'CTIPO'   => $laTmp[19],
                                'CDESTIP' => $laTmp[20],'CNUMMOV' => $laTmp[21],'CTIPMOV' => $laTmp[22],'CIDREQU' => $laTmp[23]];
          $i++;
        }
      }
      if ($i == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintRepNotaSalida($p_oSql) {
      // Ordena arreglo de trabajo
      // Detalle del reporte
      $laSuma = 0.00;
      $lcIdRequ = '*';
      $ldDate = date('Y-m-d', time());
      $lnContad = 1;
      $lcTipo = '*';
      $lcMonTot = 0.00;
      foreach ($this->paDatos as $laFila) {
        /*
            UCSM-ERP                                 NOTA DE SALIDA                              PAG:    1
            ID         : 11111111             FECHA      : 01-01-2018
            NRO. BOLETA: 1111111111           NRO. TRANS.: 1111111111
            ALMACEN    : 111 - DESCRIPCION DEL ALMACEN
            USUARIO    : 1111 - NOMBRE DEL USUARIO O ALUMNO
            DESCRIPCION: DESCRIPCION DEL REQUERIMIENTO
            ----------------------------------------------------------------------------------------------
             #  COD.ART. DESCRIPCION                                                              CANTIDAD
            ----------------------------------------------------------------------------------------------
            ### 00000000 123456789012345678901234567890123456789012345678901234567890123456 9,999,999.0000
        */
         if ($lcIdRequ == '*') {
            if($laFila['CTIPO'] == 'T') {
                $laDatos[] = ['* ALMACEN ORIGEN: ' . $laFila['CALMORI'] . ' - ' . fxString(trim($laFila['CDESALM']), 20) . 'ALMACEN DESTINO: ' . $laFila['CALMDES'] . ' - ' . fxString(trim($laFila['CALDESC']), 20)];
            } else {
                $laDatos[] = ['* CENTRO DE COSTO: ' . $laFila['CCENCOS'] . ' - ' . fxString(trim($laFila['CDESCCO']), 40)];
            }
            if($laFila['CCODEMP'] == '9999') {
               $laDatos[] = ['* ALUMNO: ' . $laFila['CCODALU'] . ' - ' . fxString(trim($laFila['CNOMALU']), 70)];
            }
            else {
               $laDatos[] = ['* EMPLEADO: ' . $laFila['CCODEMP'] . ' - ' . fxString(trim($laFila['CNOMEMP']), 70)];
            }
            if($laFila['CCODALU'] != '0000000000') {
               $laDatos[] = ['* NRO. BOLETA: ' . fxString($laFila['CNROBOL'], 10) . ' NRO. TRANS.:' . fxString($laFila['CNROTRA'], 10)];
            }
            $laDatos[] = ['* DESCRIPCION: ' . $laFila['CDESCRI']];
            $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
            $laDatos[] = ['<b> #  COD.ART.       CANTIDAD DESCRIPCION                                   COS.UNI.   IMPORTE  </b>'];
            $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
            $lcIdRequ = $laFila['CIDKARD'];
            $lcTipo = $laFila['CTIPMOV'];
            $lnContad = 1;
         }
         $lcMonto = $laFila['NCANTID'] * $laFila['NCOSTO'];
         $laDatos[] = [fxNumber($lnContad, 3, 0) . ' ' . $laFila['CCODART']. ' ' . fxNumber(trim($laFila['NCANTID']), 14, 4) . ' ' . fxString($laFila['CDESART'], 40).' '.fxNumber($laFila['NCOSTO'],12, 4).' '.fxNumber($lcMonto,12, 4)];
         $lcMonTot = $lcMonTot + $lcMonto;
         $lnContad += 1;
      }
      $this->paDatos[0]['CTIPMOV'];
      $this->paDatos[0]['CNUMMOV'];
      $this->paDatos[0]['CNUMMOV'];
      $laDatos[] = ['<b>----------------------------------------------------------------------------------------------</b>'];
      $laDatos[] = ['<b>                                                                         TOTAL: '.fxNumber($lcMonTot,12, 4).'</b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      $laDatos[] = ['<b>                                                                                              </b>'];
      if ($lcTipo == 'NS') {
         $laDatos[] = [fxString('', 7) . '_____________________' . fxString('', 8) . '_____________________' . fxString('', 8) . '_____________________' . fxString('', 8)];
         $laDatos[] = [fxString('', 10) . 'Jefe de Unidad' . fxString('', 19) . 'Almacén' . fxString('', 18) . 'Recibí Conforme' . fxString('', 11)];
      } else if ($lcTipo == 'NE' || $lcTipo == 'TX') {
         $laDatos[] = [fxString('', 17) . '_____________________' . fxString('', 18) . '_____________________' . fxString('', 17)];
         $laDatos[] = [fxString('', 19) . 'Encargado Almacén' . fxString('', 24) . 'Recepcionante' . fxString('', 21)];
      } else {
         $laDatos[] = [fxString('', 17) . '_____________________' . fxString('', 18) . '_____________________' . fxString('', 17)];
         $laDatos[] = [fxString('', 20) . 'Almacén General' . fxString('', 25) . 'Recepcionante' . fxString('', 21)];
      }
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
            if ($lcTipo == 'NS') {
               $loPdf->ezText('<b>UCSM-ERP                                 NOTA DE SALIDA                              PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            } else if ($lcTipo == 'TX') {
               $loPdf->ezText('<b>UCSM-ERP                             NOTA DE TRANSFERENCIA                           PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            } else if ($lcTipo == 'NE') {
               $loPdf->ezText('<b>UCSM-ERP                                NOTA DE ENTREGA                              PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            } else if ($lcTipo == 'ND') {
               $loPdf->ezText('<b>UCSM-ERP                               NOTA DE DEVOLUCION                            PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            } else if ($lcTipo == 'NV') {
               $loPdf->ezText('<b>UCSM-ERP                                 NOTA DE VENTA                               PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            } else if ($lcTipo == 'NI') {
               $loPdf->ezText('<b>UCSM-ERP                                NOTA DE INGRESO                              PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            } else {
               $loPdf->ezText('<b>UCSM-ERP                                 NOTA DE SALIDA                              PAG:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            }
            $loPdf->ezText('<b>ALM1150                                                                             ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>----------------------------------------------------------------------------------------------</b>', $lnFont);
            if ($this->paDatos[0]['CIDREQU'] != '00000000') {
               $loPdf->ezText('<b>* MOVIMIENTO: ' . $this->paDatos[0]['CTIPMOV'] . $this->paDatos[0]['CNUMMOV'] . fxString('', 10) . 'REQUERIMIENTO: ' . $this->paDatos[0]['CIDREQU'] . fxString('', 10) .utf8_encode('FECHA EMISIÓN: ') . $this->paDatos[0]['DFECHA'] . '</b>', $lnFont);
            } else {
               $loPdf->ezText('<b>* MOVIMIENTO: ' . $this->paDatos[0]['CTIPMOV'] . $this->paDatos[0]['CNUMMOV'] . fxString('', 43) .utf8_encode('FECHA EMISIÓN: ') . $this->paDatos[0]['DFECHA'] . '</b>', $lnFont);
            }
            //$llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*' || substr($laFila[0], 0, 1) == '-') {
            $loPdf->ezText(utf8_encode('<b>' . $laFila[0] . '</b>'), $lnFont);
         } else {
            $loPdf->ezText(utf8_encode($laFila[0]), $lnFont);
         }
         $lnRow++;
         //$llTitulo = ($lnRow == 73) ? true : false;
         $llTitulo = ($lnRow == 33) ? true : false;
      }
      $pdfcode = $loPdf->ezOutput(1);
      $fp = fopen($this->pcFile, 'wb');
      fwrite($fp, $pdfcode);
      fclose($fp);
      return true;
   }

   // ------------------------------------------------------------------
   // Movimientos por periodo (aaaamm) y Tipo
   // 2019-10-10 JLF Creacion
   // ------------------------------------------------------------------
   public function omReporteMovimientos() {
      $llOk = $this->mxValReporteMovimientos();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxPrintReporteMovimientos();
      return $llOk;
   }

   protected function mxValReporteMovimientos() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCODALM']) || strlen(trim($this->paData['CCODALM'])) != 3) {
         $this->pcError = 'ALMACEN INVALIDO';
         return false;
      } elseif (!isset($this->paData['CTIPMOV']) || strlen(trim($this->paData['CTIPMOV'])) != 2) {
         $this->pcError = 'TIPO DE MOVIMIENTO INVALIDO';
         return false;
      } elseif ($this->paData['CTIPMOV'] == 'TX' && (!isset($this->paData['CALMTRA']) || strlen(trim($this->paData['CALMTRA'])) != 3)) {
         $this->pcError = 'ALMACEN DE TRANSFERENCIA INVALIDO';
         return false;
      } elseif (!isset($this->paDatos) || $this->paDatos == null || count($this->paDatos) == 0) {
         $this->pcError = 'REPORTE NO TIENE DETALLE PARA IMPRIMIR';
         return false;
      }
      return true;
   }

   protected function mxPrintReporteMovimientos() {
      // Abre hoja de calculo
      $loXls = new CXls();
      if ($this->paData['CTIPMOV'] == 'NI') {
         $lcNomArc = 'ALM2';
         $lcUltCol = 'I';
      } elseif ($this->paData['CTIPMOV'] == 'TX') {
         $lcNomArc = 'ALM3';
         $lcUltCol = 'N';
      } else {
         $lcNomArc = 'ALM1';
         $lcUltCol = 'J';
      }
      $loXls->openXlsIO($lcNomArc, 'R');
      // Cabecera
      $lcTitulo = 'REPORTE DE MOVIMIENTOS DE: '.$this->paDatos[0]['CDESMOV'];
      if ($this->paData['CTIPMOV'] == 'TX') {
         $lcTitulo .= ', ENTRE: '.$this->paDatos[0]['CALMORI'].' - '.$this->paDatos[0]['CDESORI'].' Y: '.$this->paDatos[0]['CALMDES'].' - '.$this->paDatos[0]['CDESDES'];
      } elseif ($this->paDatos[0]['CALMORI'] == $this->paData['CCODALM']) {
         $lcTitulo .= ', DE: '.$this->paDatos[0]['CALMORI'].' - '.$this->paDatos[0]['CDESORI'];
      } elseif ($this->paDatos[0]['CALMDES'] == $this->paData['CCODALM']) {
         $lcTitulo .= ', DE: '.$this->paDatos[0]['CALMDES'].' - '.$this->paDatos[0]['CDESDES'];
      }
      $loXls->sendXls(0, 'A', 2, $lcTitulo);
      $loXls->sendXls(0, $lcUltCol, 2, 'FECHA: '.date("Y-m-d"));
      $i = 3;
      foreach ($this->paDatos as $laTmp) {
         $i++;
         $loXls->sendXls(0, 'A', $i, $i - 3);
         $loXls->sendXls(0, 'B', $i, $laTmp['CTIPMOV']);
         $loXls->sendXls(0, 'C', $i, $laTmp['CNUMMOV']);
         $loXls->sendXls(0, 'D', $i, $laTmp['DFECHA']);
         if ($this->paData['CTIPMOV'] == 'NI') {
            $loXls->sendXls(0, 'E', $i, $laTmp['CNRORUC']);
            $loXls->sendXls(0, 'F', $i, str_replace('/', ' ', $laTmp['CRAZSOC']));
            $loXls->sendXls(0, 'G', $i, $laTmp['CCENCOS']);
            $loXls->sendXls(0, 'H', $i, $laTmp['CDESCCO']);
         } elseif ($this->paData['CTIPMOV'] == 'TX') {
            $loXls->sendXls(0, 'E', $i, $laTmp['CALMORI']);
            $loXls->sendXls(0, 'F', $i, $laTmp['CDESORI']);
            $loXls->sendXls(0, 'G', $i, $laTmp['CALMDES']);
            $loXls->sendXls(0, 'H', $i, $laTmp['CDESDES']);
            $loXls->sendXls(0, 'I', $i, $laTmp['CCODEMP']);
            $loXls->sendXls(0, 'J', $i, str_replace('/', ' ', $laTmp['CNOMEMP']));
            $loXls->sendXls(0, 'K', $i, $laTmp['CCENCOS']);
            $loXls->sendXls(0, 'L', $i, $laTmp['CDESCCO']);
         } else {
            $loXls->sendXls(0, 'E', $i, $laTmp['CCODEMP']);
            $loXls->sendXls(0, 'F', $i, str_replace('/', ' ', $laTmp['CNOMEMP']));
            $loXls->sendXls(0, 'G', $i, $laTmp['CCENCOS']);
            $loXls->sendXls(0, 'H', $i, $laTmp['CDESCCO']);
         }
         if ($this->paData['CTIPMOV'] == 'NS') {
            $loXls->sendXls(0, 'I', $i, $laTmp['CIDREQU']);
         } elseif ($this->paData['CTIPMOV'] == 'TX') {
            $loXls->sendXls(0, 'M', $i, $laTmp['CIDREQU']);
         }
         $loXls->sendXls(0, $lcUltCol, $i, $laTmp['CDESCRI']);
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // ------------------------------------------------------------------
   // Reporte de Inventario en Excel
   // 2019-12-06 JLF Creacion
   // ------------------------------------------------------------------
   public function omReporteInventarioEXCEL() {
      $llOk = $this->mxValReporteInventarioEXCEL();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxReporteInventarioEXCEL($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintReporteInventarioEXCEL($loSql);
      return $llOk;
   }

   protected function mxValReporteInventarioEXCEL() {
      if (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
         $this->pcError = 'CODIGO DE USUARIO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
         $this->pcError = 'CENTRO DE COSTO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CALMINI']) || strlen(trim($this->paData['CALMINI'])) != 3) {
         $this->pcError = 'ALMACEN INICIAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['CALMFIN']) || strlen(trim($this->paData['CALMFIN'])) != 3) {
         $this->pcError = 'ALMACEN FINAL INVALIDO';
         return false;
      } elseif (!isset($this->paData['CTIPART']) || strlen(trim($this->paData['CTIPART'])) != 2) {
         $this->pcError = 'TIPO DE ARTICULO INVALIDO';
         return false;
      } elseif (!isset($this->paData['CGRUART']) || strlen(trim($this->paData['CGRUART'])) != 2) {
         $this->pcError = 'GRUPO DE ARTICULO INVALIDO';
         return false;
      }
      return true;
   }

   protected function mxReporteInventarioEXCEL($p_oSql) {
      $lcAlmIni = $this->paData['CALMINI'];
      $lcAlmFin = $this->paData['CALMFIN'];
      $lcTipArt = $this->paData['CTIPART'];
      $lcGruArt = $this->paData['CGRUART'];
      $lcSql = "SELECT A.cCodAlm, A.cCodArt, A.nStock, A.cCodAlm, B.cDescri AS cDesAlm, B.cDesCor, B.cCenCos, C.cDescri AS cDesArt, 
                       C.cUnidad, D.cDescri AS cDesUni, C.cTipArt, E.cDescri AS cDesTip, C.cGrupo, F.cDescri AS cDesGru
                FROM E03PALM A
                INNER JOIN E03MALM B ON B.cCodAlm = A.cCodAlm
                INNER JOIN E01MART C ON C.cCodArt = A.cCodArt
                LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '074' AND D.cCodigo = C.cUnidad
                LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '082' AND E.cCodigo = C.cTipArt
                LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '083' AND F.cCodigo = C.cGrupo
                WHERE A.cCodAlm BETWEEN '$lcAlmIni' AND '$lcAlmFin' AND A.nStock > 0 AND A.cEstado = 'A' ";
      if ($lcTipArt != '**') {
         $lcSql .= "AND C.cTipArt = '$lcTipArt' ";
      }
      if ($lcGruArt != '**') {
         $lcSql .= "AND C.cGrupo = '$lcGruArt' ";
      }
      $lcSql .= "ORDER BY A.cCodAlm, C.cTipArt, C.cGrupo, A.cCodArt";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR';
         return false;
      }
      while ($laTmp = $p_oSql->fetch($R1)) {
         $this->laDatos[] = ['CCODALM' => $laTmp[0], 'CCODART' => $laTmp[1], 'NSTOCK'  => $laTmp[2], 'CCODALM' => $laTmp[3],
                             'CDESALM' => $laTmp[4], 'CDESCOR' => $laTmp[5], 'CCENCOS' => $laTmp[6], 'CDESART' => $laTmp[7],
                             'CUNIDAD' => $laTmp[8], 'CDESUNI' => $laTmp[9], 'CTIPART' => $laTmp[10],'CDESTIP' => $laTmp[11],
                             'CGRUPO'  => $laTmp[12],'CDESGRU' => $laTmp[13]];
      }
      return true;
   }

   protected function mxPrintReporteInventarioEXCEL($p_oSql) {
      // Abre hoja de calculo
      $loXls = new CXls();
      $loXls->openXlsIO('ALM4', 'R');
      // Cabecera
      $lcTitulo = 'REPORTE DE INVENTARIO DE ALMACEN';
      if ($this->paData['CALMINI'] != $this->paData['CALMFIN']) {
         $lcTitulo .= ', ENTRE: '.$this->laDatos[0]['CCODALM'].' - '.$this->laDatos[0]['CDESALM'].' Y: '.$this->laDatos[count($this->laDatos) - 1]['CCODALM'].' - '.$this->laDatos[count($this->laDatos) - 1]['CDESALM'];
      } else {
         $lcTitulo .= ', DE: '.$this->laDatos[0]['CCODALM'].' - '.$this->laDatos[0]['CDESALM'];
      }
      $loXls->sendXls(0, 'A', 2, $lcTitulo);
      $loXls->sendXls(0, 'I', 2, 'FECHA: '.date("Y-m-d"));
      $i = 3;
      $lcCodAlm = '*';
      $lcTipArt = '*';
      $lcGrupo  = '*';
      foreach ($this->laDatos as $laTmp) {
         $i++;
         if ($lcCodAlm != $laTmp['CCODALM']) {
            $lcCodAlm = $laTmp['CCODALM'];
            $lcTipArt = $laTmp['CTIPART'];
            $lcGrupo = $laTmp['CGRUPO'];
            $loXls->cellStyle('A'.$i, 'I'.$i, true, false, 11, 'left', 'center');
            $loXls->mergeCells(0, 'A'.$i, 'I'.$i);
            $loXls->sendXls(0, 'A', $i, 'ALMACEN: '.$lcCodAlm.' - '.$laTmp['CDESALM']);
            $loXls->cellStyle('B'.($i + 1), 'I'.($i + 1), true, true, 10, 'left', 'center');
            $loXls->mergeCells(0, 'B'.($i + 1), 'I'.($i + 1));
            $loXls->sendXls(0, 'B', $i + 1, $lcTipArt.' - '.$laTmp['CDESTIP']);
            $loXls->cellStyle('C'.($i + 2), 'I'.($i + 2), true, true, 10, 'left', 'center');
            $loXls->mergeCells(0, 'C'.($i + 2), 'I'.($i + 2));
            $loXls->sendXls(0, 'C', $i + 2, $lcGrupo.' - '.$laTmp['CDESGRU']);
            $i = $i + 3;
            $lnContad = 0;
         } elseif ($lcTipArt != $laTmp['CTIPART']) {
            $lcTipArt = $laTmp['CTIPART'];
            $lcGrupo = $laTmp['CGRUPO'];
            $loXls->cellStyle('B'.$i, 'I'.$i, true, true, 10, 'left', 'center');
            $loXls->mergeCells(0, 'B'.$i, 'I'.$i);
            $loXls->sendXls(0, 'B', $i, $lcTipArt.' - '.$laTmp['CDESTIP']);
            $loXls->cellStyle('C'.($i + 1), 'I'.($i + 1), true, true, 10, 'left', 'center');
            $loXls->mergeCells(0, 'C'.($i + 1), 'I'.($i + 1));
            $loXls->sendXls(0, 'C', $i + 1, $lcGrupo.' - '.$laTmp['CDESGRU']);
            $i = $i + 2;
         } elseif ($lcGrupo != $laTmp['CGRUPO']) {
            $lcGrupo = $laTmp['CGRUPO'];
            $loXls->cellStyle('C'.$i, 'I'.$i, true, true, 10, 'left', 'center');
            $loXls->mergeCells(0, 'C'.$i, 'I'.$i);
            $loXls->sendXls(0, 'C', $i, $lcGrupo.' - '.$laTmp['CDESGRU']);
            $i = $i + 1;
         }
         $loXls->sendXls(0, 'A', $i, $lnContad + 1);
         $loXls->sendXls(0, 'D', $i, $laTmp['CCODART']);
         $loXls->sendXls(0, 'E', $i, $laTmp['CDESART']);
         $loXls->sendXls(0, 'F', $i, $laTmp['NSTOCK']);
         $loXls->sendXls(0, 'G', $i, $laTmp['CUNIDAD']);
         $loXls->sendXls(0, 'H', $i, $laTmp['CDESUNI']);
         $lnContad++;
      }
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   // Reporte de kardex negativo
   // 2019-12-11 JLF Creacion
   public function omRepKardexNegativo() {
      $llOk = $this->mxValParamRepKardexNegativo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      $llOk = $this->mxRepKardexNegativo($loSql);
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintRepKardexNegativo();
      return $llOk;
   }

   protected function mxValParamRepKardexNegativo() {
      $loDate = new CDate();
      if (!$loDate->mxValDate($this->paData['DINICIO'])) {
         $this->pcError = 'FECHA INICIAL INVALIDA';
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFINALI'])) {
         $this->pcError = 'FECHA FINAL INVALIDA';
         return false;
      } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
         $this->pcError = 'FECHA FINAL ES MENOR QUE FECHA INICIAL';
         return false;
      } elseif (empty($this->paData['CCODALM'])) {
         $this->pcError = 'CODIGO DE ALMACEN NO DEFINIDO';
         return false;
      } elseif (empty($this->paData['CCODART'])) {
         $this->pcError = 'CODIGO DE ARTICULO NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxRepKardexNegativo($p_oSql) {
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcCodAlm = $this->paData['CCODALM'];
      $lcCodArt = $this->paData['CCODART'];
      $i = 0;
      $lcSql = "SELECT t_cIdKard, t_cCodArt, t_cDesArt, t_cUnidad, t_nCantid, t_nStock, t_nCosto, t_nPreTot, t_nCosSal, t_cAlmOri, 
                       t_cDesOri, t_cAlmDes, t_cDesDes, t_dFecha, t_cCodEmp, t_cNombre, t_cTipo, t_cDesTip, t_cTipMov, t_cNumMov,
                       t_cNroRuc, t_cRazSoc
                       FROM F_E03MKAR_4('$lcCodAlm','$ldInicio','$ldFinali')";
      $R1 = $p_oSql->omExec($lcSql);
      if ($R1 == false || $p_oSql->pnNumRow == 0) {
         $this->pcError = 'NO HAY DATOS PARA IMPRIMIR CON LOS FILTROS SELECCIONADOS';
         return false;
      }
      while ($laFila = $p_oSql->fetch($R1)) {
         if (trim($laFila[0]) == 'ERROR') {
            $this->pcError = trim($laFila[2]);
            return false;
         }
         $this->laDatos[] = ['CIDKARD' => $laFila[0], 'CCODART' => $laFila[1], 'CDESART' => $laFila[2], 'CUNIDAD' => $laFila[3],
                             'NCANTID' => $laFila[4], 'NSTOCK'  => $laFila[5], 'NCOSTO'  => $laFila[6], 'NPRETOT' => $laFila[7],
                             'NCOSSAL' => $laFila[8], 'CALMORI' => $laFila[9], 'CDESORI' => $laFila[10],'CALMDES' => $laFila[11],
                             'CDESDES' => $laFila[12],'DFECHA'  => $laFila[13],'CCODEMP' => $laFila[14],'CNOMBRE' => $laFila[15],
                             'CTIPO'   => $laFila[16],'CDESTIP' => $laFila[17],'CTIPMOV' => $laFila[18],'CNUMMOV' => $laFila[19],
                             'CNRORUC' => $laFila[20],'CRAZSOC' => $laFila[21]];
      }
      return true;
   }

   protected function mxPrintRepKardexNegativo() {
      /*
        UCSM-ERP                                                         KARDEX VALORADO                                                       PAG:     1
        ALM1210                                                  DEL: 2018-05-01  AL: 2018-05-02                                               2018-05-30
        -------------------------------------------------------------------------------------------------------------------------------------------------
        INGRESOS                SALIDAS                SALDOS
        #   FECHA      MOVIMIENTO    NOMBRE/RAZON SOCIAL               CANTIDAD     IMPORTE     CANTIDAD    IMPORTE    CANTIDAD     IMPORTE       COSTO
        -------------------------------------------------------------------------------------------------------------------------------------------------
        123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345
        1         2         3         4         5         6         7         8         9         10        110       120       130
       */
      $laSumaIng = 0.00;
      $laSumaSal = 0.00;
      $lnSumIngC = 0.00;
      $lcSumSalC = 0.00;
      $lnCanStk = 0.00;
      $lnSumStk = 0.00;
      $lnTotlMov = 0.00;
      $lnContad = 1;
      $lcDesAlm = ($this->laDatos[0]['CALMORI'] == $this->paData['CCODALM'])? $this->laDatos[0]['CDESORI'] : $this->laDatos[0]['CDESDES'];
      $ldInicio = $this->paData['DINICIO'];
      $ldFinali = $this->paData['DFINALI'];
      $lcCodArt = $this->laDatos[0]['CCODART'];
      $lcDesArt = $this->laDatos[0]['CDESART'];
      $lcUnidad = $this->laDatos[0]['CUNIDAD'];
      $ldDate = date('Y-m-d', time());
      $lcTipTrx = '*';
      $lcCodTrx = '*';
      foreach ($this->laDatos as $laFila) {
         $lnTotlMov ++;
         $lcNombre = ($laFila['CTIPMOV'] == 'NI')? fxStringFixed($laFila['CRAZSOC'], 31) : fxStringFixed($laFila['CNOMBRE'], 31);
         if ($laFila['CALMDES'] == $this->paData['CCODALM']) {
            $laDatos[] = [fxNumber($lnContad, 4, 0) . ' ' . $laFila['CCODART'] . ' ' . fxString2($laFila['CDESART'], 21) . ' ' . $laFila['DFECHA'] . ' ' . 
                          $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . fxNumber($laFila['NCANTID'], 12, 2) . fxNumber($laFila['NPRETOT'], 12, 2) .
                          fxString(' ', 24) . fxNumber($laFila['NSTOCK'], 12, 2) . fxNumber($laFila['NCOSSAL'], 12, 2) . fxNumber($laFila['NCOSTO'], 12, 4)];
            $lnSumIngC = $lnSumIngC + $laFila['NCANTID'];
            $laSumaIng = $laSumaIng + $laFila['NPRETOT'];
            $lnContad ++;
         } else {
            $laDatos[] = [fxNumber($lnContad, 4, 0) . ' ' . $laFila['CCODART'] . ' ' . fxString2($laFila['CDESART'], 21) . ' ' . $laFila['DFECHA'] . ' ' . 
                          $laFila['CTIPMOV'] . '-' . $laFila['CNUMMOV'] . ' ' . fxString(' ', 24) . fxNumber($laFila['NCANTID'], 12, 2) . fxNumber($laFila['NPRETOT'], 12, 2) . 
                          fxNumber($laFila['NSTOCK'], 12, 2) . fxNumber($laFila['NCOSSAL'], 12, 2) . fxNumber($laFila['NCOSTO'], 12, 4)];
            $lcSumSalC = $lcSumSalC + $laFila['NCANTID'];
            $laSumaSal = $laSumaSal + $laFila['NPRETOT'];
            $lnContad ++;
         }
         $lnCanStk += $laFila['NSTOCK'];
         $lnSumStk += $laFila['NCOSSAL'];
         $laTmp = $laFila;
      }
      //$laDatos[] = ['-------------------------------------------------------------------------------------------------------------------------------------------------'];
      $laDatos[] = [fxString('-', 145)];
      $laDatos[] = ['* '. fxNumber($lnTotlMov, 12, 0) . fxString(' MOVIMIENTOS  ', 15) . ' '.fxString('', 31) . fxNumber($lnSumIngC, 12, 2) . fxNumber($laSumaIng, 12, 2) . fxNumber($lcSumSalC, 12, 2) . fxNumber($laSumaSal, 12, 2) . fxNumber($lnCanStk, 12, 2) . fxNumber($lnSumStk, 12, 2) . fxNumber($this->laDatos[count($this->laDatos) - 1]['NCOSTO'], 12, 4)];
      $laDatos[] = [fxString('-', 145)];
      $lnFont = 9;
      $loPdf = new Cezpdf('A4', 'landscape');
      $loPdf->selectFont('fonts/Courier.afm', 10);
      $loPdf->ezSetCmMargins(1, 1, 1, 1);
      $lnPag = 0;
      $lnRow = 0;
      $llTitulo = true;
      foreach ($laDatos as $laFila) {
         if ($llTitulo) {
            // Titulo
            $lnPag++;
            if ($lnPag > 1) {
               $loPdf->ezNewPage();
            }
            $loPdf->ezText('<b>UCSM-ERP                                           KARDEX NEGATIVO DEL '.$ldInicio.' AL '.$ldFinali.'                                        PAG.:' . fxNumber($lnPag, 5, 0) . '</b>', $lnFont);
            $loPdf->ezText('<b>'.$this->paData['CCODALM'].' - '.fxString($lcDesAlm, 30).'                                                                                                   ' . $ldDate . '</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $loPdf->ezText('<b>                                                                         INGRESOS                SALIDAS                SALDOS                   </b>', $lnFont);
            $loPdf->ezText('<b>  #  ARTICULO DESCRIPCION             FECHA     MOVIMIENTO       CANTIDAD     IMPORTE    CANTIDAD     IMPORTE    CANTIDAD     IMPORTE       COSTO</b>', $lnFont);
            $loPdf->ezText('<b>-------------------------------------------------------------------------------------------------------------------------------------------------</b>', $lnFont);
            $llTitulo = false;
            $lnRow = 0;
         }
         if (substr($laFila[0], 0, 1) == '*') {
            $loPdf->ezText('<b>' . $laFila[0] .'</b>', $lnFont);
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

   // REPORTE GUIA DE INGRESO POR PERIODO Y TIPO DE ARTICULO
   // 2021-09-01 WZA Creacion
   public function omRepGuiaDeIngresoPorTipoArticulo() {
      $llOk = $this->mxValParamRepGuiaDeIngresoPorTipoArticulo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      if ($this->paData['CFORMAT'] == 'D' || $this->paData['CFORMAT'] == 'ED'){
        $llOk = $this->mxRepGuiaDeIngresoPorTipoArticuloDetalle($loSql);
      } else {
        $llOk = $this->mxRepGuiaDeIngresoPorTipoArticulo($loSql);  
      }
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      if ($this->paData['CFORMAT'] == 'P'){
        $llOk = $this->mxPrintRepGuiaDeIngresoPorTipoArticuloPDF();
      } elseif ($this->paData['CFORMAT'] == 'E'){
        $llOk = $this->mxPrintRepGuiaDeIngresoPorTipoArticuloExcel();
      } elseif ($this->paData['CFORMAT'] == 'ED'){
        $llOk = $this->mxRepTipoDeGuiaTipoArticuloExcelDetalle();
      } else {
        $llOk = $this->mxPrintRepGuiaDeIngresoPorTipoArticuloDetallePDF();  
      }
      return $llOk;
   }
  
   protected function mxValParamRepGuiaDeIngresoPorTipoArticulo() {
      $loDate = new CDate();
      if (!isset($this->paData['DFECINI']) || empty($this->paData['DFECINI']) || strlen($this->paData['DFECINI']) != 10) {
         $this->pcError = 'FECHA DE INICIO INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['DFECFIN']) || empty($this->paData['DFECFIN']) || strlen($this->paData['DFECFIN']) != 10) {
         $this->pcError = 'FECHA DE FIN INVÁLIDA';
         return false;
      } elseif (empty($this->paData['CCODALM'])) {
         $this->pcError = 'CODIGO DE ALMACEN NO DEFINIDO';
         return false;
      } elseif (empty($this->paData['CTIPART'])) {
         $this->pcError = 'CODIGO DE ARTICULO NO DEFINIDO';
         return false;
      }
      return true;
   }

   protected function mxRepGuiaDeIngresoPorTipoArticulo($p_oSql){
      $lcCodAlm = $this->paData['CCODALM'];
      $lcTipArt = trim($this->paData['CTIPART']);
      $lcBusCom = $this->paData['CBUSCOM'];
      $lcBusCom = str_replace(' ', '%', trim($lcBusCom));
      $lcBusCom = str_replace('#', 'Ñ', trim($lcBusCom));
      $lcWheCos = '';
      if ($this->paData['CUSUCOD'] == '2456'){
         $lcCodAlm = '000';
         $lcWheCos = "AND A.cCenCos IN ('00P','0BZ')";
      }
      if ($lcTipArt == 'T'){
        $lcTipArt = '';
      }
      $lcSql = "SELECT DISTINCT A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, A.cNroRuc, F.cCodAnt, F.cRazSoc, TRIM(E.cCodAnt), E.cDescri,
                  L.cIdOrde, SUM(H.nMonto) as nMonCal, TRIM(N.cNroCom), N.cAsient, M.mObserv, N.dEnvCon, N.cIdComp, N.cIdOrde, N.cTipCom, L.cNoting, N.nTipCam
                  FROM E03MKAR A
                  INNER JOIN E03MALM B ON B.cCodAlm = A.cAlmOri
                  INNER JOIN S01TCCO E ON E.cCenCos = A.cCenCos
                  INNER JOIN S01MPRV F ON F.cNroRuc = A.cNroRuc
                  INNER JOIN E01MNIN L ON L.cIdKard = A.cIdKard
                  LEFT OUTER JOIN E01MORD M ON M.cIdOrde = L.cIdOrde 
                  INNER JOIN E01MFAC N ON N.cIdComp = L.cIdComp AND N.nMonto <> 0
                  LEFT OUTER JOIN E01DFAC H ON H.cIdComp = N.cIdComp
                  INNER JOIN E01MART I ON I.cCodArt = H.cCodArt
                  WHERE A.cTipMov = 'NI' AND (A.cAlmOri = '$lcCodAlm' OR A.cAlmDes = '$lcCodAlm') AND A.dFecha BETWEEN '{$this->paData['DFECINI']}' AND '{$this->paData['DFECFIN']}' 
                  AND H.cEstado <> 'X' AND I.cTipArt = '$lcTipArt' ".$lcWheCos;              
      if ($lcBusCom != '*') {
          $lcSql .= " AND (M.cCodAnt LIKE '%$lcBusCom' OR F.cNroRuc = '$lcBusCom' OR F.cRazSoc LIKE '%$lcBusCom%' OR F.cCodAnt = '$lcBusCom'
                         OR N.cNroCom = '$lcBusCom' OR A.cNumMov LIKE '%$lcBusCom' OR E.cCenCos = '$lcBusCom' OR E.cCodAnt = '$lcBusCom'
                         OR E.cDescri LIKE '%$lcBusCom%') ";
      }
      $lcSql .= "GROUP BY A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, A.cNroRuc, F.cCodAnt, F.cRazSoc, TRIM(E.cCodAnt), E.cDescri,
                 L.cIdOrde, TRIM(N.cNroCom), N.cAsient, M.mObserv, N.dEnvCon, N.cIdComp, N.cIdOrde, N.cTipCom, L.cNoting
                 ORDER BY A.cNumMov ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON MOVIMIENTOS CON LOS PAR�?METROS SELECCIONADOS";
         return false;
      }
      $lcIdOrde = '*';
      while ($laFila = $p_oSql->fetch($RS)) {
          $lcFlag = ($lcIdOrde != $laFila[16]) ? '*' : '';
          $lcIdOrde = $laFila[16];
          $lcSql = "SELECT A.nSerial, TO_CHAR(C.tEntreg, 'YYYY-MM-DD'), C.cAsient FROM D02DCPP A
                      INNER JOIN D02MCCT B ON B.cCtaCte = A.cCtaCte
                      INNER JOIN D02MTRX C ON C.cCodTrx = A.cCodTrx
                      WHERE B.cTipo = 'P' AND A.cEstado = 'B' 
                            AND TRIM(A.cCtaCnt) NOT IN (SELECT TRIM(cCodigo) FROM V_S01TTAB WHERE cCodTab = '024') ";
          if (substr($laFila[2], 0, 1) == 'X' || substr($laFila[2], 0, 1) == '0') {
             $lcSql .= "AND TRIM(B.cCodOld) = '$laFila[7]' ";
          } else {
             $lcSql .= "AND TRIM(B.cCodigo) = '$laFila[4]' ";
          }
          $lcSql .= "AND TRIM(A.cCompro) LIKE '$laFila[17]/%$laFila[11]'";
          $R2 = $p_oSql->omExec($lcSql);
          if ($R2 == false) {
             $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
             return false;
          }
          $laTmp = $p_oSql->fetch($R2);
          $lcPagado = ($laTmp[0] != null && $laTmp[1] != null)? 'SI' : 'NO';
          if ($lcPagado == 'SI' &&  $this->paData['CPAGADO'] == 'S') {
             continue;
          }
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'DFECHA'  => $laFila[1],'CTIPMOV' => $laFila[2],'CNUMMOV' => $laFila[3],
                              'CNRORUC' => $laFila[4], 'CPRVANT' => $laFila[5],'CRAZSOC' => $laFila[6], 'CCCOANT' => $laFila[7],
                              'CNROORD' => $laFila[9], 'NMONTO' => $laFila[10], 'CDESCCO' => $laFila[8], 'CNROCOM' => $laFila[11], 
                              'CASIENT' => TRIM($laFila[12]) == ''? '/': $laFila[12],'MOBSERV' => $laFila[13], 'DENVCON' => $laFila[14],
                              'CIDCOMP' => $laFila[15], 'CIDORDE' => $laFila[16], 'CPAGADO' => $lcPagado, 'DFECPAG' => $laTmp[1],  
                              'CASICAJ' => $laTmp[2],  'CFLAG'   => $lcFlag, 'CNOTING' => $laFila[18], 'NTIPCAM' => $laFila[19]];
      }
      return true;
   }

   protected function mxPrintRepGuiaDeIngresoPorTipoArticuloPDF(){
      $lnMonTot = 0;
      $i = 1;
      /*UCSM-ERP                                                 GUIAS DE INGRESO - PERIODO 202108                                             PAG:     1
        ALM1210                                                        TIPO: MERCADERIAS                                                       2018-05-30
        -------------------------------------------------------------------------------------------------------------------------------------------------
        #    FECHA ING.  NRO. MOVIMIENTO  RAZ. SOC.                                                          NRO. ORDEN    NRO. COM     C.COSTO     VALOR
        999  9999-99-99     9999999999     AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA       9999999    a999-99999999    9AB      999999.99          */
      foreach($this->paDatos as $laFila){
        if ($laFila['NTIPCAM'] != 0){
          $laFila['NMONTO'] = $laFila['NMONTO'] * $laFila['NTIPCAM'];
        }
        $lcLinea = utf8_decode(fxNumber($i, 3, 0).'  '.$laFila['DFECHA'].'    '.fxStringFixed('NI-'.$laFila['CNUMMOV'],10).'     '.fxStringFixed($laFila['CPRVANT'],5).'       '.fxStringFixed($laFila['CNRORUC'],13).' '.fxStringFixed($laFila['CRAZSOC'],50).'  '.fxString($laFila['CNROORD'], 8).'   '.fxStringFixed($laFila['CNROCOM'],13).'    '.fxString($laFila['CCENCOS'],3).'      '.fxStringFixed($laFila['CASIENT'],12).'   '.fxNumber($laFila['NMONTO'], 10, 2));
        $laDatos[] = $lcLinea;
        $i++;
        $lnMonTot = $lnMonTot + $laFila['NMONTO'];
      }
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4');
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->SetFont('Courier', 'B',6);
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'UCSM-ERP                                            GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'].'                                  PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);                        
            $loPdf->Cell(240, 3,'ALM1260                                                                 TIPO: '.$this->paData['CTIPART'].'                                                  '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(5);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________________', 0);  
            $loPdf->Ln(5);
            $loPdf->Cell(0, 3,' #  FECHA ING.  NRO. MOV.  COD. PRV.     RUC      RAZ. SOC.                                 NRO. ORD.  NRO. COM.  C.COSTO   ASIENTO CONT.   VALOR', 0);
            $loPdf->Ln(3);
            $loPdf->Cell(0, 2, '___________________________________________________________________________________________________________________________________________________', 0);  
            $loPdf->Cell(50,2,'',0,1);
            $loPdf->Ln(1);
            $lnRow = 5;          
            $llTitulo = false;
         }
         $loPdf->Ln(1);
         $loPdf->SetFont('Courier', '', 5);
         $loPdf->Cell(240, 5, $lcLinea);
         $loPdf->Ln(4);
         $lnRow++;
         $llTitulo = ($lnRow == 53) ? true : false;
      }
      if ($llTitulo) {
        $lnPag++;
        if ($loPdf->PageNo() > 1){
           $loPdf->AddPage('P', 'A4');  
        }
      }
      $loPdf->SetFont('Courier', 'B', 6);
      $loPdf->Cell(0, 2, '__________________________________________________________________________________________________________________________________________________', 0);  
      $loPdf->Ln(6);
      $loPdf->Cell(0, 3,'                                                                                                                                TOTAL'.fxNumber($lnMonTot,12,2), 0);
      $loPdf->Ln(1);
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxPrintRepGuiaDeIngresoPorTipoArticuloExcel() {
      // Abre hoja de calculo
      $loXls = new CXls();
      $loXls->openXlsIO('RGIX', 'R');
      // Cabecera
      $lcTitulo = 'GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'];
      $loXls->sendXls(0, 'A', 2, $lcTitulo);
      $loXls->sendXls(0, 'I', 2, 'FECHA: '.date("Y-m-d"));
      $lnTotal = 0;
      $i = 4;
      foreach ($this->paDatos as $laTmp) {
        if ($laTmp['NTIPCAM'] != 0){
          $laTmp['NMONTO'] = $laTmp['NMONTO'] * $laTmp['NTIPCAM'];
        }
        $loXls->sendXls(0, 'A', $i, $laTmp['DFECHA']);
        $loXls->sendXls(0, 'B', $i, $laTmp['CNUMMOV']);
        $loXls->sendXls(0, 'C', $i, $laTmp['CRAZSOC']);
        $loXls->sendXls(0, 'D', $i, $laTmp['CNROORD']);
        $loXls->sendXls(0, 'E', $i, TRIM($laTmp['CNROCOM']));
        $loXls->sendXls(0, 'F', $i, $laTmp['CCENCOS']);
        $loXls->sendXls(0, 'G', $i, $laTmp['DENVCON']);
        $loXls->sendXls(0, 'H', $i, TRIM($laTmp['CASIENT']));
        $loXls->sendXls(0, 'I', $i, $laTmp['NMONTO']);
        $lnTotal = $lnTotal + $laTmp['NMONTO'];
        $i++;
      }
      $loXls->cellBold('H'.$i,'I'.$i);
      $loXls->sendXls(0, 'H', $i, 'TOTAL: ');
      $loXls->sendXls(0, 'I', $i, $lnTotal);
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

   protected function mxRepGuiaDeIngresoPorTipoArticuloDetalle($p_oSql){
      $lcCodAlm = $this->paData['CCODALM'];
      $lcTipArt = trim($this->paData['CTIPART']);
      $lcBusCom = $this->paData['CBUSCOM'];
      $lcBusCom = str_replace(' ', '%', trim($lcBusCom));
      $lcBusCom = str_replace('#', 'Ñ', trim($lcBusCom));
      $lcWheCos = '';
      if ($this->paData['CUSUCOD'] == '2456'){
         $lcCodAlm = '000';
         $lcWheCos = "AND A.cCenCos IN ('00P','0BZ')";
      }
      if ($lcTipArt == 'T'){
        $lcTipArt = '';
      }
      $lcSql = "SELECT  A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, A.cNroRuc, F.cCodAnt, F.cRazSoc, TRIM(E.cCodAnt), E.cDescri,
                  L.cIdOrde, SUM(H.nMonto) as nMonCal, TRIM(N.cNroCom), N.cAsient, M.mObserv, N.dEnvCon, N.cIdComp, N.cIdOrde, N.cTipCom, L.cNoting,
                  H.cCodArt, I.cDescri, H.nCantid, ROUND(H.nMonto / H.nCantid,4) AS nCosto, H.nMonto,A.cDescri, I.cUnidad, A.cAlmDes, N.nTipCam
                  FROM E03MKAR A
                  INNER JOIN E03MALM B ON B.cCodAlm = A.cAlmOri
                  INNER JOIN S01TCCO E ON E.cCenCos = A.cCenCos
                  INNER JOIN S01MPRV F ON F.cNroRuc = A.cNroRuc
                  INNER JOIN E01MNIN L ON L.cIdKard = A.cIdKard
                  LEFT OUTER JOIN E01MORD M ON M.cIdOrde = L.cIdOrde 
                  INNER JOIN E01MFAC N ON N.cIdComp = L.cIdComp AND N.nMonto <> 0
                  LEFT OUTER JOIN E01DFAC H ON H.cIdComp = N.cIdComp
                  INNER JOIN E01MART I ON I.cCodArt = H.cCodArt 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '087' AND C.cCodigo = N.cTipCom
                  LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '007' AND D.cCodigo = N.cMoneda
                  WHERE A.cTipMov = 'NI' AND (A.cAlmOri = '$lcCodAlm' OR A.cAlmDes = '$lcCodAlm') AND A.dFecha BETWEEN '{$this->paData['DFECINI']}' AND '{$this->paData['DFECFIN']}' 
                  AND I.cTipArt = '$lcTipArt' AND H.cEstado <> 'X'".$lcWheCos;        
      if ($lcBusCom != '*') {
          $lcSql .= " AND (M.cCodAnt LIKE '%$lcBusCom' OR F.cNroRuc = '$lcBusCom' OR F.cRazSoc LIKE '%$lcBusCom%' OR F.cCodAnt = '$lcBusCom'
                         OR N.cNroCom = '$lcBusCom' OR A.cNumMov LIKE '%$lcBusCom' OR E.cCenCos = '$lcBusCom' OR E.cCodAnt = '$lcBusCom'
                         OR E.cDescri LIKE '%$lcBusCom%') ";
      }
      $lcSql .= "GROUP BY A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, A.cNroRuc, F.cCodAnt, F.cRazSoc, TRIM(E.cCodAnt), E.cDescri,
                 L.cIdOrde, TRIM(N.cNroCom), N.cAsient, M.mObserv, N.dEnvCon, N.cIdComp, N.cIdOrde, N.cTipCom, L.cNoting,
                 H.cCodArt, I.cDescri, H.nCantid, nCosto, H.nMonto,A.cDescri, I.cUnidad, A.cAlmDes
                 ORDER BY A.cNumMov ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON MOVIMIENTOS CON LOS PAR�?METROS SELECCIONADOS";
         return false;
      }
      $lcIdOrde = '*';
      while ($laFila = $p_oSql->fetch($RS)) {
          $lcFlag = ($lcIdOrde != $laFila[16]) ? '*' : '';
          $lcIdOrde = $laFila[16];
          $lcSql = "SELECT A.nSerial, TO_CHAR(C.tEntreg, 'YYYY-MM-DD'), C.cAsient FROM D02DCPP A
                      INNER JOIN D02MCCT B ON B.cCtaCte = A.cCtaCte
                      INNER JOIN D02MTRX C ON C.cCodTrx = A.cCodTrx
                      WHERE B.cTipo = 'P' AND A.cEstado = 'B' 
                            AND TRIM(A.cCtaCnt) NOT IN (SELECT TRIM(cCodigo) FROM V_S01TTAB WHERE cCodTab = '024') ";
          if (substr($laFila[2], 0, 1) == 'X' || substr($laFila[2], 0, 1) == '0') {
             $lcSql .= "AND TRIM(B.cCodOld) = '$laFila[7]' ";
          } else {
             $lcSql .= "AND TRIM(B.cCodigo) = '$laFila[4]' ";
          }
          $lcSql .= "AND TRIM(A.cCompro) LIKE '$laFila[17]/%$laFila[11]'";
          $R2 = $p_oSql->omExec($lcSql);
          if ($R2 == false) {
             $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
             return false;
          }
          $laTmp = $p_oSql->fetch($R2);
          $lcPagado = ($laTmp[0] != null && $laTmp[1] != null)? 'SI' : 'NO';
          if ($lcPagado == 'SI' &&  $this->paData['CPAGADO'] == 'S') {
             continue;
          }
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'DFECHA'  => $laFila[1],'CTIPMOV' => $laFila[2],'CNUMMOV' => $laFila[3],
                              'CNRORUC' => $laFila[4], 'CPRVANT' => $laFila[5],'CRAZSOC' => $laFila[6], 'CCCOANT' => $laFila[7],
                              'CNROORD' => $laFila[9], 'NMONTO' => $laFila[10], 'CDESCCO' => $laFila[8], 'CNROCOM' => $laFila[11], 
                              'CASIENT' => TRIM($laFila[12]) == ''? '/': $laFila[12],'MOBSERV' => $laFila[13], 'DENVCON' => $laFila[14],
                              'CIDCOMP' => $laFila[15], 'CIDORDE' => $laFila[16], 'CPAGADO' => $lcPagado, 'DFECPAG' => $laTmp[1],  
                              'CASICAJ' => $laTmp[2],  'CFLAG'   => $lcFlag, 'CNOTING' => $laFila[18], 'CCODART' => $laFila[19], 
                              'CDESART' => $laFila[20], 'NCANTID' => $laFila[21], 'NCOSUNI' => $laFila[22], 'NPRETOT' => $laFila[23],
                              'CDESMOV' => $laFila[24], 'CUNIDAD' => $laFila[25], 'CALMDES' => $laFila[26], 'NTIPCAM' => $laFila[27]];
      }
      return true;
   }

   protected function mxPrintRepGuiaDeIngresoPorTipoArticuloDetallePDF(){
      $lnMonTot = 0;
      $lnDetTot = 0;
      $lcNumMov = '';
      $lcFlag = true;
      /*UCSM-ERP                                                 GUIAS DE INGRESO - PERIODO 202108                                             PAG:     1
        ALM1210                                                        TIPO: MERCADERIAS                                                       2018-05-30
        -------------------------------------------------------------------------------------------------------------------------------------------------
        #    FECHA ING.  NRO. MOVIMIENTO  RAZ. SOC.                                                          NRO. ORDEN    NRO. COM     C.COSTO     VALOR
        999  9999-99-99     9999999999     AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA       9999999    a999-99999999    9AB      999999.99          */
      foreach($this->paDatos as $laFila){
        if ($laFila['NTIPCAM'] != 0){
          $laFila['NPRETOT'] = $laFila['NPRETOT'] * $laFila['NTIPCAM'];
        }
        if ($lcNumMov !=  $laFila['CNUMMOV']){
            if (!$lcFlag){
                $lcLinea = utf8_decode(fxStringFixed('',112).fxStringFixed('____________',10));
                $laDatos[] = $lcLinea;
                $lcLinea = utf8_decode(fxStringFixed('',72).fxStringFixed('TOTAL MOVIMIENTO '.$lcNumMov,30).'          '.fxNumber($lnDetTot, 10,2));
                $laDatos[] = $lcLinea;
            }
            $lcNumMov = $laFila['CNUMMOV'];
            $lnDetTot = 0;
            $lcLinea = utf8_decode(fxStringCenter('*',3).fxStringFixed($laFila['CNUMMOV'],10).' '.fxStringFixed($laFila['CDESMOV'],52).'      '.$laFila['DFECHA'].'     '.fxStringFixed($laFila['CCENCOS'], 3).'  '.fxStringFixed($laFila['CDESCCO'],20));
            $laDatos[] = $lcLinea;
        }
        $lcLinea = utf8_decode(fxStringFixed('',13).' '.fxStringFixed('001',3).'  '.fxStringFixed($laFila['CCODART'],8).'  '. fxStringFixed($laFila['CDESART'],40).'   '.$laFila['DFECHA'].'  '.fxNumber($laFila['NCANTID'],10,2).'  '.fxStringFixed($laFila['CUNIDAD'],3).' '.fxNumber($laFila['NCOSUNI'],10,2).'  '. fxNumber($laFila['NPRETOT'],10,2));
        $laDatos[] = $lcLinea;
        $lnDetTot = $lnDetTot + $laFila['NPRETOT'];
        $lnMonTot = $lnMonTot + $laFila['NPRETOT'];
        $lcFlag = false;
      }
      $lcLinea = utf8_decode(fxStringFixed('',112).fxStringFixed('____________',10));
      $laDatos[] = $lcLinea;
      $lcLinea = utf8_decode(fxStringFixed('',72).fxStringFixed('TOTAL MOVIMIENTO '.$lcNumMov,30).'          '.fxNumber($lnDetTot, 10,2));
      $laDatos[] = $lcLinea;
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('L', 'A4');
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->SetFont('Courier', 'B', 10);
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'UCSM-ERP                                GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'].'                        PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);  
            $loPdf->Cell(240, 3,'ALM1260                                                     TIPO: '.$this->paData['CTIPART'].'                                        '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(5);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________________________________', 0);  
            $loPdf->Ln(5);
            $loPdf->Cell(0, 3,'    NRO. MOV.  Al. ARTICULO   DESCRIPCION                                 FECHA       CANTIDAD  UNI.   UNI. S/.   TOTAL /S.', 0);
            $loPdf->Ln(3);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________________________________', 0);  
            $loPdf->Cell(50,2,'',0,1);
            $loPdf->Ln(1);
            $lnRow = 5;          
            $llTitulo = false;
         }
         $loPdf->Ln(1);
         $loPdf->SetFont('Courier', '', 10);
         if (substr($lcLinea, 0, 2) == ' *' || substr($lcLinea, -1) == '_' || substr($lcLinea, -50,-45) == 'TOTAL') {
            $loPdf->Ln(1);
            $loPdf->SetFont('Courier', 'B' ,10);
         }
         $loPdf->Cell(240, 5, $lcLinea);
         $loPdf->Ln(4);
         $lnRow++;
         $llTitulo = ($lnRow == 32) ? true : false;
      }
      if ($llTitulo) {
        $lnPag++;
        if ($loPdf->PageNo() > 1){
           $loPdf->AddPage('L', 'A4');  
        }
      }
      $loPdf->SetFont('Courier', 'B', 10);
      $loPdf->Cell(0, 2, '_____________________________________________________________________________________________________________________________', 0);  
      $loPdf->Ln(6);
      $loPdf->Cell(0, 3,'                                                                                                         TOTAL'.fxNumber($lnMonTot,12,2), 0);
      $loPdf->Ln(1);
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   // REPORTE TIPO DE GUIA Y TIPO DE ARTICULO
   // 2021-10-04 WZA Creacion
   public function omRepTipoDeGuiaTipoArticulo() {
      $llOk = $this->mxValParamRepTipoDeGuiaTipoArticulo();
      if (!$llOk) {
         return false;
      }
      $loSql = new CSql();
      $llOk = $loSql->omConnect();
      if (!$llOk) {
         $this->pcError = $loSql->pcError;
         return false;
      }
      if ($this->paData['CFORMAT'] == 'D' || $this->paData['CFORMAT'] == 'ED'){
        $llOk = $this->mxRepTipoDeGuiaTipoArticuloDetalle($loSql);
      } else {
        $llOk = $this->mxRepTipoDeGuiaTipoArticulo($loSql);  
      }
      $loSql->omDisconnect();
      if (!$llOk) {
         return false;
      }
      if ($this->paData['CFORMAT'] == 'P'){
        $llOk = $this->mxRepTipoDeGuiaTipoArticuloPDF();
      } elseif ($this->paData['CFORMAT'] == 'E'){
        $llOk = $this->mxRepTipoDeGuiaTipoArticuloExcel();
      } elseif ($this->paData['CFORMAT'] == 'ED'){
        $llOk = $this->mxRepTipoDeGuiaTipoArticuloExcelDetalle();
      } else {
        $llOk = $this->mxRepTipoDeGuiaTipoArticuloDetallePDF();  
      }
      return $llOk;
   }
  
   protected function mxValParamRepTipoDeGuiaTipoArticulo() {
      $loDate = new CDate();
      if (!isset($this->paData['DFECINI']) || empty($this->paData['DFECINI']) || strlen($this->paData['DFECINI']) != 10) {
         $this->pcError = 'FECHA DE INICIO INVÁLIDA';
         return false;
      } elseif (!isset($this->paData['DFECFIN']) || empty($this->paData['DFECFIN']) || strlen($this->paData['DFECFIN']) != 10) {
         $this->pcError = 'FECHA DE FIN INVÁLIDA';
         return false;
      } elseif (empty($this->paData['CCODALM'])) {
         $this->pcError = 'CODIGO DE ALMACEN NO DEFINIDO';
         return false;
      } elseif (empty($this->paData['CTIPART'])) {
         $this->pcError = 'CODIGO DE ARTICULO NO DEFINIDO';
         return false;
      } elseif (empty($this->paData['CTIPMOV'])) {
        $this->pcError = 'CODIGO DE ARTICULO NO DEFINIDO';
        return false;
      }
      return true;
   }
  
   protected function mxRepTipoDeGuiaTipoArticulo($p_oSql){
      $lcCodAlm = $this->paData['CCODALM'];
      $lcTipArt = trim($this->paData['CTIPART']);
      $lcBusCom = $this->paData['CBUSCOM'];
      $lcBusCom = str_replace(' ', '%', trim($lcBusCom));
      $lcBusCom = str_replace('#', 'Ñ', trim($lcBusCom));
      $lcTipMov = $this->paData['CTIPMOV'];
      $lcWheCos = '';
      if ($this->paData['CUSUCOD'] == '2456'){
         $lcCodAlm = '000';
         $lcWheCos = "AND A.cCenCos IN ('00P','0BZ')";
      }
      if ($lcTipArt == 'T'){
        $lcTipArt = '';
      }
      if ($lcTipMov == 'TO'){
        $lcTipMov = '';
      }
      if ($lcCodAlm == 'TTT'){
        $lcCodAlm = '0';
      } 
      if ($lcTipMov == 'IB'){
        $lcCampos = ', SUM(D.nMonto), F.cNombre';
        $lcInner = 'INNER JOIN E05MADQ C ON C.cIdKard = A.cIdKard 
                    INNER JOIN E05DADQ D ON D.cIdAdqu = C.cIdAdqu
                    INNER JOIN E01MART I ON I.cCodArt = D.cCodArt';
      } else if ($lcTipMov == 'NI'){
        $lcInner = 'INNER JOIN E01MNIN L ON L.cIdKard = A.cIdKard
                    INNER JOIN E01MFAC N ON N.cIdComp = L.cIdComp AND N.nMonto <> 0
                    LEFT OUTER JOIN E01DFAC H ON H.cIdComp = N.cIdComp
                    INNER JOIN E01MART I ON I.cCodArt = H.cCodArt';
                    $lcCampos = ', SUM(H.nMonto), F.cNombre';
      } else {
        $lcInner = 'LEFT OUTER JOIN E03DKAR H ON H.cIdKard = A.cIdKarD
                    INNER JOIN E01MART I ON I.cCodArt = H.cCodArt';
        $lcCampos = ', SUM(H.nPreTot), F.cNombre';
      }
      $lcSql = "SELECT DISTINCT A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, TRIM(E.cCodAnt), E.cDescri, A.cDescri 
                ".$lcCampos."
                FROM E03MKAR A
                INNER JOIN E03MALM B ON B.cCodAlm = A.cAlmOri
                INNER JOIN S01TCCO E ON E.cCenCos = A.cCenCos
                INNER JOIN V_S01TUSU_1 F ON F.cCodUsu = A.cCodEmp
                ".$lcInner." 
                WHERE A.cTipMov LIKE '%$lcTipMov%' AND (A.cAlmOri LIKE '$lcCodAlm%' OR A.cAlmDes LIKE '$lcCodAlm%') AND A.dFecha BETWEEN '{$this->paData['DFECINI']}' AND '{$this->paData['DFECFIN']}' 
                AND A.cEstado <> 'X' AND I.cTipArt LIKE '%$lcTipArt%' ".$lcWheCos;        
      if ($lcBusCom != '*') {
      $lcSql .= " AND (A.cNumMov LIKE '%$lcBusCom' OR E.cCenCos = '$lcBusCom' OR E.cCodAnt = '$lcBusCom'
                     OR E.cDescri LIKE '%$lcBusCom%') ";
      }
      $lcSql .= " GROUP BY A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, TRIM(E.cCodAnt), E.cDescri, A.cDescri, F.cNombre 
                  ORDER BY A.cNumMov ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON MOVIMIENTOS CON LOS PARAMETROS SELECCIONADOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'DFECHA'  => $laFila[1],'CTIPMOV' => $laFila[2],'CNUMMOV' => $laFila[3],
                             'CCCOANT' => $laFila[4], 'CCCODES' => $laFila[5], 'CDESCRI' => $laFila[6], 'NMONTO' => $laFila[7], 
                             'CNOMEMP' => $laFila[8]];
      }
      return true;
   }
  
   protected function mxRepTipoDeGuiaTipoArticuloPDF(){
      $lnMonTot = 0;
      $i = 1;
      /*UCSM-ERP                                                 GUIAS DE INGRESO - PERIODO 202108                                             PAG:     1
        ALM1210                                                        TIPO: MERCADERIAS                                                       2018-05-30
        -------------------------------------------------------------------------------------------------------------------------------------------------
        #    FECHA ING.  NRO. MOVIMIENTO  DESCRIPCON MOV.                                                                     C.COSTO     
        999  9999-99-99     9999999999     AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA        9AB - AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA              */
      foreach($this->paDatos as $laFila){
        $lcLinea = utf8_decode(fxNumber($i, 3, 0).'  '.$laFila['DFECHA'].'   '.fxStringFixed($laFila['CTIPMOV'].'-'.$laFila['CNUMMOV'],13).'     '.fxStringFixed($laFila['CDESCRI'],30).'   '.fxString($laFila['CCENCOS'],3).'-'.fxStringFixed($laFila['CCCODES'],40).'  '.fxNumber($laFila['NMONTO'],10,2));
        $laDatos[] = $lcLinea;
        $lnMonTot = $lnMonTot + $laFila['NMONTO'];
        $i++;
        //$lnMonTot = $lnMonTot + $laFila['NMONTO'];
      }
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4');
      $lcTipArt = $this->paData['CTIPART'];
      if ($lcTipArt == 'T'){
        $lcTipArt = '* ';
      }
      $lcTipMov = $this->paData['CTIPMOV'];
      if ($lcTipArt == 'T'){
        $lcTipArt = '* ';
      }
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('L', 'A4');  
            }
            $loPdf->SetFont('Courier', 'B',7);
            $loPdf->Ln(5);
            $loPdf->Cell(50, 1,'UCSM-ERP                               GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'].'                          PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);  
            $loPdf->Cell(240, 3,'ALM1260                                           TIP. MOV. : '.$this->paData['CTIPMOV'].', TIPO: '.$lcTipArt.'                                         '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(5);
            $loPdf->Cell(0, 2, '______________________________________________________________________________________________________________________________', 0);  
            $loPdf->Ln(5);
            $loPdf->Cell(0, 3,' #    FECHA ING.    NRO. MOV.       DESCRIPCON MOV.                        CENTRO DE COSTO                             MONTO                      ', 0);
            $loPdf->Ln(3);
            $loPdf->Cell(0, 2, '______________________________________________________________________________________________________________________________', 0);  
            $loPdf->Cell(50,2,'',0,1);
            $loPdf->Ln(1);
            $lnRow = 5;          
            $llTitulo = false;
         }
         $loPdf->Ln(1);
         $loPdf->SetFont('Courier', '', 7);
         $loPdf->Cell(240, 5, $lcLinea);
         $loPdf->Ln(4);
         $lnRow++;
         $llTitulo = ($lnRow == 64) ? true : false;
      }
      if ($llTitulo) {
        $lnPag++;
        if ($loPdf->PageNo() > 1){
           $loPdf->AddPage('P', 'A4');  
        }
      }
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(0, 2, '_______________________________________________________________________________________________________________________________', 0);  
      $loPdf->Ln(6);
      $loPdf->Cell(0, 3,'                                                                                                            TOTAL'.fxNumber($lnMonTot,12,2), 0);
      $loPdf->Ln(1);
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }
  
    protected function mxRepTipoDeGuiaTipoArticuloExcel() {
      // Abre hoja de calculo
      $loXls = new CXls();
      $loXls->openXlsIO('RTGX', 'R');
      // Cabecera
      $lcTitulo = 'GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'];
      $loXls->sendXls(0, 'A', 2, $lcTitulo);
      $loXls->sendXls(0, 'E', 2, 'FECHA: '.date("Y-m-d"));
      $lnTotal = 0;
      $i = 4;
      foreach ($this->paDatos as $laTmp) {
        $loXls->sendXls(0, 'A', $i, $laTmp['DFECHA']);
        $loXls->sendXls(0, 'B', $i, $laTmp['CTIPMOV'].'-'.$laTmp['CNUMMOV']);
        $loXls->sendXls(0, 'C', $i, $laTmp['CDESCRI']);
        $loXls->sendXls(0, 'D', $i, $laTmp['CCENCOS'].'-'.$laTmp['CCCODES']);
        $loXls->sendXls(0, 'E', $i, $laTmp['NMONTO']);
        $i++;
        $lnTotal = $lnTotal + $laTmp['NMONTO'];
      }
      $loXls->sendXls(0, 'D', $i, 'TOTAL ');
      $loXls->sendXls(0, 'E', $i, $lnTotal);
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }
  
   protected function mxRepTipoDeGuiaTipoArticuloDetalle($p_oSql){
      $lcCodAlm = $this->paData['CCODALM'];
      $lcTipArt = trim($this->paData['CTIPART']);
      $lcBusCom = $this->paData['CBUSCOM'];
      $lcBusCom = str_replace(' ', '%', trim($lcBusCom));
      $lcBusCom = str_replace('#', 'Ñ', trim($lcBusCom));
      $lcTipMov = $this->paData['CTIPMOV'];
      $lcWheCos = '';
      if ($this->paData['CUSUCOD'] == '2456'){
         $lcCodAlm = '000';
         $lcWheCos = "AND A.cCenCos IN ('00P','0BZ')";
      }
      if ($lcTipArt == 'T'){
        $lcTipArt = '';
      }
      if ($lcTipMov == 'TO'){
        $lcTipMov = '';
      }
      if ($lcCodAlm == 'TTT'){
        $lcCodAlm = '0';
      } 
      if ($lcTipMov == 'IB'){
        $lcCampos = ',D.cCodArt, I.cDescri, D.nCantid, ROUND(ROUND(D.nMonto,4) / D.nCantid,4), ROUND(D.nMonto,4), I.cUnidad, A.cAlmDes';
        $lcInner = 'INNER JOIN E05MADQ C ON C.cIdKard = A.cIdKard 
                    INNER JOIN E05DADQ D ON D.cIdAdqu = C.cIdAdqu
                    INNER JOIN E01MART I ON I.cCodArt = D.cCodArt';
      } else if ($lcTipMov == 'NI'){
        $lcInner = 'INNER JOIN E01MNIN L ON L.cIdKard = A.cIdKard
                    INNER JOIN E01MFAC N ON N.cIdComp = L.cIdComp AND N.nMonto <> 0
                    LEFT OUTER JOIN E01DFAC H ON H.cIdComp = N.cIdComp
                    INNER JOIN E01MART I ON I.cCodArt = H.cCodArt';
        $lcCampos = ',H.cCodArt, I.cDescri, H.nCantid, ROUND(ROUND(H.nMonto,4) / H.nCantid,4), ROUND(H.nMonto,4), I.cUnidad, A.cAlmDes';
      } else {
        $lcInner = 'LEFT OUTER JOIN E03DKAR H ON H.cIdKard = A.cIdKard
                    INNER JOIN E01MART I ON I.cCodArt = H.cCodArt';
        $lcCampos = ',H.cCodArt, I.cDescri, H.nCantid, ROUND(H.nCosto,4), ROUND(ROUND(H.nCosto,4) * H.nCantid,4), I.cUnidad, A.cAlmDes';
      }
      $lcSql = "SELECT A.cCenCos, A.dFecha, A.cTipMov, A.cNumMov, TRIM(E.cCodAnt), E.cDescri, A.cDescri 
                ".$lcCampos."
                FROM E03MKAR A
                INNER JOIN E03MALM B ON B.cCodAlm = A.cAlmOri
                INNER JOIN S01TCCO E ON E.cCenCos = A.cCenCos
                ".$lcInner." 
                WHERE A.cTipMov LIKE '%$lcTipMov%' AND (A.cAlmOri LIKE '$lcCodAlm%' OR A.cAlmDes LIKE '$lcCodAlm%') AND A.dFecha BETWEEN '{$this->paData['DFECINI']}' AND '{$this->paData['DFECFIN']}' 
                AND A.cEstado <> 'X' AND I.cTipArt LIKE '%$lcTipArt%' ".$lcWheCos;        
      if ($lcBusCom != '*') {
      $lcSql .= " AND (A.cNumMov LIKE '%$lcBusCom' OR E.cCenCos = '$lcBusCom' OR E.cCodAnt = '$lcBusCom'
                     OR E.cDescri LIKE '%$lcBusCom%') ";
      }
      $lcSql .= "ORDER BY A.cNumMov, cCodArt ASC";
      $RS = $p_oSql->omExec($lcSql);
      if ($RS == false) {
         $this->pcError = 'ERROR DE EJECUCION DE BASE DE DATOS';
         return false;
      } elseif ($p_oSql->pnNumRow == 0) {
         $this->pcError = "NO SE ENCONTRARON MOVIMIENTOS CON LOS PAR�?METROS SELECCIONADOS";
         return false;
      }
      while ($laFila = $p_oSql->fetch($RS)) {
         $this->paDatos[] = ['CCENCOS' => $laFila[0], 'DFECHA'  => $laFila[1],'CTIPMOV' => $laFila[2],'CNUMMOV' => $laFila[3],
                             'CCCOANT' => $laFila[4], 'CDESCCO' => $laFila[5], 'CCODART' => $laFila[7], 'CDESART' => $laFila[8], 
                             'NCANTID' => $laFila[9], 'NCOSUNI' => $laFila[10], 'NPRETOT' => $laFila[11],'CDESMOV' => $laFila[6], 
                             'CUNIDAD' => $laFila[12],'CALMDES' => $laFila[13]];
      }
      return true;
   }
  
   protected function mxRepTipoDeGuiaTipoArticuloDetallePDF(){
    $lnMonTot = 0;
    $lnDetTot = 0;
    $lcNumMov = '';
    $lcFlag = true;
    /*UCSM-ERP                                                 GUIAS DE INGRESO - PERIODO 202108                                             PAG:     1
      ALM1210                                                        TIPO: MERCADERIAS                                                       2018-05-30
      -------------------------------------------------------------------------------------------------------------------------------------------------
      #    FECHA ING.  NRO. MOVIMIENTO  RAZ. SOC.                                                          NRO. ORDEN    NRO. COM     C.COSTO     VALOR
      999  9999-99-99     9999999999     AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA       9999999    a999-99999999    9AB      999999.99          */
    foreach($this->paDatos as $laFila){
       if ($lcNumMov !=  $laFila['CNUMMOV']){
            if (!$lcFlag){
                $lcLinea = utf8_decode(fxStringFixed('',112).fxStringFixed('____________',10));
                $laDatos[] = $lcLinea;
                $lcLinea = utf8_decode(fxStringFixed('',72).fxStringFixed('TOTAL MOVIMIENTO '.$laFila['CTIPMOV'].'-'.$lcNumMov,30).'          '.fxNumber($lnDetTot, 10,2));
                $laDatos[] = $lcLinea;
            }
            $lcNumMov = $laFila['CNUMMOV'];
            $lnMonTot = $lnMonTot + $lnDetTot;
            $lnDetTot = 0;
            $lcLinea = utf8_decode(fxStringCenter('*',3).fxStringFixed($laFila['CTIPMOV'].'-'.$laFila['CNUMMOV'],13).' '.fxStringFixed($laFila['CDESMOV'],52).'      '.$laFila['DFECHA'].'     '.fxStringFixed($laFila['CCENCOS'], 3).'  '.fxStringFixed($laFila['CDESCCO'],20));
            $laDatos[] = $lcLinea;  
        }
        $lcLinea = utf8_decode(fxStringFixed('',13).' '.fxStringFixed($laFila['CALMDES'],3).'  '.fxStringFixed($laFila['CCODART'],8).'  '. fxStringFixed($laFila['CDESART'],40).'   '.$laFila['DFECHA'].'  '.fxNumber($laFila['NCANTID'],10,2).'  '.fxStringFixed($laFila['CUNIDAD'],3).' '.fxNumber($laFila['NCOSUNI'],10,2).'  '. fxNumber($laFila['NPRETOT'],10,2));
        $laDatos[] = $lcLinea;
        $lnDetTot = $lnDetTot + $laFila['NPRETOT'];
        $lcFlag = false;
      }
      $lnMonTot = $lnMonTot + $lnDetTot;
      $lcLinea = utf8_decode(fxStringFixed('',112).fxStringFixed('____________',10));
      $laDatos[] = $lcLinea;
      $lcLinea = utf8_decode(fxStringFixed('',72).fxStringFixed('TOTAL MOVIMIENTO '.$lcNumMov,30).'          '.fxNumber($lnDetTot, 10,2));
      $laDatos[] = $lcLinea;
      $llTitulo = true;
      $lnRow = $lnPag = 0;
      $ldDate = date('Y-m-d', time());
      $loPdf = new FPDF();
      $loPdf->AddPage('P', 'A4');
      $lcTipArt = $this->paData['CTIPART'];
      if ($lcTipArt == 'T'){
        $lcTipArt = '* ';
      }
      foreach ($laDatos as $lcLinea) {
         if ($llTitulo) {
            $lnPag++;
            if ($loPdf->PageNo() > 1){
               $loPdf->AddPage('P', 'A4');  
            }
            $loPdf->SetFont('Courier', 'B', 7);
            $loPdf->Ln(7);
            $loPdf->Cell(50, 1,'UCSM-ERP                                GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'].'                        PAG.'.fxNumber($lnPag, 6, 0));
            $loPdf->Ln(3);  
            $loPdf->Cell(240, 3,'ALM1260                                                     TIPO: '.$lcTipArt.'                                        '.$ldDate, 0, 0, 'L');
            $loPdf->Ln(5);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________________________________', 0);  
            $loPdf->Ln(5);
            $loPdf->Cell(0, 3,'    NRO. MOV.  Al. ARTICULO   DESCRIPCION                                 FECHA       CANTIDAD  UNI.   UNI. S/.   TOTAL /S.', 0);
            $loPdf->Ln(3);
            $loPdf->Cell(0, 2, '____________________________________________________________________________________________________________________________', 0);  
            $loPdf->Cell(50,2,'',0,1);
            $loPdf->Ln(1);
            $lnRow = 5;          
            $llTitulo = false;
         }
         $loPdf->Ln(1);
         $loPdf->SetFont('Courier', '', 7);
         if (substr($lcLinea, 0, 2) == ' *' || substr($lcLinea, -1) == '_' || substr($lcLinea, -50,-45) == 'TOTAL') {
            $loPdf->SetFont('Courier', 'B' ,7);
         }
         $loPdf->Cell(240, 5, $lcLinea);
         $loPdf->Ln(3);
         $lnRow++;
         $llTitulo = ($lnRow == 64) ? true : false;
      }
      if ($llTitulo) {
        $lnPag++;
        if ($loPdf->PageNo() > 1){
           $loPdf->AddPage('P', 'A4');  
        }
      }
      $loPdf->SetFont('Courier', 'B', 7);
      $loPdf->Cell(0, 2, '_____________________________________________________________________________________________________________________________', 0);  
      $loPdf->Ln(6);
      $loPdf->Cell(0, 3,'                                                                                                         TOTAL'.fxNumber($lnMonTot,12,2), 0);
      $loPdf->Ln(1);
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxRepTipoDeGuiaTipoArticuloExcelDetalle() {
      // Abre hoja de calculo
      $loXls = new CXls();
      $loXls->openXlsIO('RGDX', 'R');
      // Cabecera
      $lcTitulo = 'GUIAS DE INGRESO - PERIODO '.$this->paData['DFECINI'].' - '.$this->paData['DFECFIN'];
      $loXls->sendXls(0, 'A', 2, $lcTitulo);
      $loXls->sendXls(0, 'I', 2, 'FECHA: '.date("Y-m-d"));
      $lnTotal = 0;
      $lnTotDet = 0;
      $i = 4;
      $llflag= true;
      $lcNumMov = '';
      foreach ($this->paDatos as $laTmp) {
        if ($lcNumMov != $laTmp['CNUMMOV']){
          if (!$llflag){
            $i++;
            $loXls->sendXls(0, 'F', $i, 'TOTAL MOVIMIENTO '.$laTmp['CTIPMOV'].'-'.$laTmp['CNUMMOV']);
            $loXls->sendXls(0, 'I', $i, $lnTotDet);
            $i++;$i++;
            $lnTotDet = 0;
          }
          $loXls->sendXls(0, 'A', $i, $laTmp['CTIPMOV'].'-'.$laTmp['CNUMMOV']);
          $loXls->sendXls(0, 'D', $i, $laTmp['CDESMOV']);
          $loXls->sendXls(0, 'E', $i, $laTmp['DFECHA']);
          $loXls->sendXls(0, 'F', $i, $laTmp['CCENCOS'].'-'.$laTmp['CDESCCO']);
          $llflag = false;
          $i++;
        }
        $loXls->sendXls(0, 'B', $i, $laTmp['CALMDES']);
        $loXls->sendXls(0, 'C', $i, $laTmp['CCODART']);
        $loXls->sendXls(0, 'D', $i, $laTmp['CDESART']);
        $loXls->sendXls(0, 'E', $i, $laTmp['DFECHA']);
        $loXls->sendXls(0, 'F', $i, $laTmp['NCANTID']);
        $loXls->sendXls(0, 'G', $i, $laTmp['CUNIDAD']);
        $loXls->sendXls(0, 'H', $i, $laTmp['NCOSUNI']);
        $loXls->sendXls(0, 'I', $i, $laTmp['NPRETOT']);
        $lnTotal = $lnTotal + $laTmp['NPRETOT'];
        $lnTotDet = $lnTotDet + $laTmp['NPRETOT'];
        $lcNumMov = $laTmp['CNUMMOV'];
        $i++;
      }
      $i++;
      $loXls->sendXls(0, 'F', $i, 'TOTAL GENERAL ');
      $loXls->sendXls(0, 'I', $i, $lnTotal);
      $loXls->closeXlsIO();
      $this->pcFile = $loXls->pcFile;
      return true;
   }

}
