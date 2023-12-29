<?php
   require_once "class/PHPExcel.php";
   require_once 'Libs/phpqrcode/qrlib.php';
   //require_once 'class/RTFTable.php';

//------------------------------------------------------
// Clase Base
//------------------------------------------------------
class CBase {
   public $paData, $paDatos, $pcError;
   protected $laData, $laDatos;

   public function __construct() {
      $this->pcError = $this->paData = $this->paDatos = $this->laData = $this->laDatos = null;
   }
}

//------------------------------------------------------
// Clase para fechas
//------------------------------------------------------
class CDate extends CBase {
   public $date;
   public $days;

   public function valDate($p_dFecha) {
      $laFecha = explode('-', $p_dFecha);
      $llOk = checkdate((int)$laFecha[1], (int)$laFecha[2], (int)$laFecha[0]); 
      if (!$llOk) {
         $this->pcError = 'FORMATO DE FECHA INVALIDA';
      }
      return $llOk;
   }

   public function add($p_dFecha, $p_nDias) {
      $llOk = $this->valDate($p_dFecha);
      if (!$llOk) {
         return false;
      }
      if (!is_int($p_nDias)) {
         $this->pcError = 'PARAMETRO DE DIAS ES INVALIDO';
         return false;
      } elseif ($p_nDias >= 0) {
         $lcDias = ' + '.$p_nDias.' days';
      } else {
         $p_nDias = $p_nDias * (-1);
         $lcDias = ' - '.$p_nDias.' days';
      }
      $this->date = date('Y-m-d', strtotime($p_dFecha.$lcDias));
      return true;
   }
   
   public function diff($p_dFecha1, $p_dFecha2) {
      $llOk = $this->valDate($p_dFecha1);
      if (!$llOk) {
         return false;
      }
      $llOk = $this->valDate($p_dFecha2);
      if (!$llOk) {
         return false;
      }
      $this->days = (strtotime($p_dFecha1) - strtotime($p_dFecha2)) / 86400;
      $this->days = floor($this->days);
     return true;
   }
   
   public function dateText($p_dDate) {
      $llOk = $this->valDate($p_dDate);
      if (!$llOk) {
         return 'Error: '.$p_dDate;
      }
      $laDays = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado');
      $laMonths = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
      $laDate = explode('-', $p_dDate);
      $ldDate = mktime(0, 0, 0, $laDate[1], $laDate[2], $laDate[0]);
      return $laDays[date('w', $ldDate)].', '.date('d', $ldDate).' '.$laMonths[date('m', $ldDate) - 1].' de '.date('Y', $ldDate);
   }

    public function mxvalDate($p_dFecha) {
      $laFecha = explode('-', $p_dFecha);
      $llOk = checkdate((int)$laFecha[1], (int)$laFecha[2], (int)$laFecha[0]); 
      if (!$llOk) {
         $this->pcError = 'FORMATO DE FECHA INVALIDA';
      }
      return $llOk;
   }
   
   public function dateTextMonth($p_dDate) {
      if (strlen($p_dDate) != 2) {
         return 'Error: '.$p_dDate;
      }
      $p_dDate = (int)$p_dDate;
      $laMonths = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
      return $laMonths[$p_dDate-1];
   }

   public function dateSimpleText($p_dDate) {
      $llOk = $this->valDate($p_dDate);
      if (!$llOk) {
         return 'Error: '.$p_dDate;
      }
      $laMonths = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
      $laDate = explode('-', $p_dDate);
      $ldDate = mktime(0, 0, 0, $laDate[1], $laDate[2], $laDate[0]);
      return date('d', $ldDate).' de '.$laMonths[date('m', $ldDate) - 1].' del '.date('Y', $ldDate);
   }

   public function omMonth($p_cMonth) {
      $lnMonth = (int)$p_cMonth;
      $laMonths = array('Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');
      return $laMonths[$lnMonth - 1];
   }

   public function formatDate($p_dDate) {
      $llOk = $this->valDate($p_dDate);
      if (!$llOk) {
         return 'ERR:'.$p_dDate;
      }
      $laMonths = ['ENE', 'FEB', 'MAR', 'ABR', 'MAY', 'JUN', 'JUL', 'AGO', 'SET', 'OCT', 'NOV', 'DIC'];
      $i = intval(substr($p_dDate, 5, 2)) - 1;
      $ldDate = substr($p_dDate, 0, 5).$laMonths[$i].substr($p_dDate, 7, 3);
      return $ldDate;
   }
}

class CXls extends CBase {
   public $pcData = "", $pcFile, $pcFilXls;
   protected $loXls, $lo, $lcFilXls;

   public function __construct() {
      parent::__construct();
      $this->loXls = new PHPExcel();
      $this->lo = PHPExcel_IOFactory::createReader('Excel2007');
   }
   
   public function openXls($p_cFilXls) {
      $this->loXls = $this->lo->load('./Xls/'.$p_cFilXls.'.xlsx');      
      $this->lcFilXls = './Files/R'.rand().'.xlsx';
      $this->pcFilXls = $this->lcFilXls;
   }

   // public function removeColumnXls($p_nSheet,$p_cCol) {
   //    $this->loXls->setActiveSheetIndex($p_nSheet)->removeColumn($p_cCol);
   //    return;
   // }

   // public function removeRowXls($p_nRow) {
   //    $this->loXls->getActiveSheet()->removeRow($p_nRow);
   //    return;
   // }

   // public function getHighestRowXls($p_nSheet) {
   //    $lnHigRow = $this->loXls->setActiveSheetIndex($p_nSheet)->getHighestDataRow();
   //    return $lnHigRow;
   // }
   
   public function sendXls($p_nSheet, $p_cCol, $p_nRow, $p_xValue) {
      $this->loXls->setActiveSheetIndex($p_nSheet)->setCellValue($p_cCol.$p_nRow, $p_xValue);
      return;
   }
   
   public function closeXls() {   
      $lo = PHPExcel_IOFactory::createWriter($this->loXls, 'Excel2007');                        
      $lo->save($this->lcFilXls);
   }

   //Funciones creadas por APH
   public function openXlsRead($p_cFilXls) {
      $this->loXls = $this->lo->load($p_cFilXls);
   }
   // public function getHighestRow($p_nSheet) {
   //    $lxValue = $this->loXls->getSheet($p_nSheet)->getHighestRow();
   //    return $lxValue;
   // }
   // public function getHighestColumn($p_nSheet) {
   //    $lxValue = $this->loXls->getSheet($p_nSheet)->getHighestColumn();
   //    return $lxValue;
   // }
   
   public function cellColor($cells, $color) {
      $this->loXls->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
      'type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => $color)));
   }
   // public function cellBorderColor($cells, $color) {
   //    $this->loXls->getActiveSheet()->getStyle($cells)->getFill()->applyFromArray(array(
   //       'borders' => array(
   //          'allborders' => array(
   //              'style' => PHPExcel_Style_Border::BORDER_THIN,
   //              'color' => array('rgb' => $color)
   //          ))        
   //        ));
   // }

   // public function cellBorder($p_cDesde,$p_cHasta) {
   //    $this->loXls->getActiveSheet()->getStyle($p_cDesde.':'.$p_cHasta)->applyFromArray(array(
   //       'borders' => array(
   //          'allborders' => array(
   //             'style' => PHPExcel_Style_Border::BORDER_THIN
   //          ))        
   //       ));
   // }

   public function cellStyle($p_cDesde, $p_cHasta, $p_llBold, $p_llItalic, $p_nSize, $p_cAliHor, $p_cAliVer, $p_cFuente = 'Arial', $p_cUnderL = 'none', $p_lWrap = false) {
      /* 
      ALINEACION HORIZONTAL 'general','left','right','center','centerContinuous','justify','fill','distributed';
      ALINEACION VERTICAL 'bottom','top','center','justify','distributed';
      SUBRAYADO 'none','double','doubleAccounting','single','singleAccounting'
      */
      $this->loXls->getActiveSheet()->getStyle($p_cDesde.':'.$p_cHasta)->applyFromArray(array(
         'font' => array(
            'bold' => $p_llBold,
            'italic' => $p_llItalic,
            'name' => $p_cFuente,
            'size' => $p_nSize,
            'underline' => $p_cUnderL
         ),
         'alignment'  => array(
            'horizontal' => $p_cAliHor,
            'vertical' => $p_cAliVer,
            'wrap' => $p_lWrap
         ),
      ));
   }
   
   // public function cellColor1($Sheet, $cells, $color) {
   //    $this->loXls->getActiveSheet($Sheet)->getStyle($cells)->getFill()->applyFromArray(array(
   //       'type' => PHPExcel_Style_Fill::FILL_SOLID, 'startcolor' => array('rgb' => $color)));
   // }
   
   // public function cellBold($p_cDesde,$p_cHasta) {
   //    $this->loXls->getActiveSheet()->getStyle($p_cDesde.':'.$p_cHasta)->getFont()->setBold(true);
   // }

   // public function copyContent($p_SheetIndex = 0) {
   //    return $this->loXls->getActiveSheet();
   // }
   
   // public function createSheet($p_SheetName,$p_SheetIndex) {
   //    $this->loXls->createSheet($p_SheetIndex);
   //    $this->loXls->setActiveSheetIndex($p_SheetIndex);
   //    $this->loXls->getActiveSheet()->setTitle($p_SheetName);
   // }
   
   // public function createSheetFormat($p_Format,$p_SheetName,$p_SheetIndex) {    
   //    echo $p_SheetIndex.'-';
   //    if ($p_SheetIndex)
   //    $this->loXls->addExternalSheet($p_Format,$p_SheetIndex);
   //    echo -2;
   //    $this->loXls->setActiveSheetIndex($p_SheetIndex);
   //    $this->loXls->getActiveSheet()->setTitle($p_SheetName);
   // }
   
   public function setActiveSheet($p_nSheet) {
      $this->loXls->setActiveSheetIndex($p_nSheet);
   }
   
   public function getValue($p_nSheet, $p_cCol, $p_nRow) {
      $lcCell = $p_cCol.$p_nRow;
      //$lxValue = $this->loXls->getActiveSheet($p_nSheet)->getCell($lcCell)->getValue();
      $lxValue = $this->loXls->getActiveSheet(1)->getCell($lcCell)->getValue();
      return $lxValue;
   }
   
   public function openXlsIO($p_cFilXls, $p_cPrefij) {
      $this->loXls = $this->lo->load('./Xls/'.$p_cFilXls.'.xlsx');
      $lcFile = $p_cPrefij.rand();
      $this->pcFile = './FILES/'.$lcFile.'.xlsx';
   }
   
   public function closeXlsIO() {
      $lo = PHPExcel_IOFactory::createWriter($this->loXls, 'Excel2007');                        
      $lo->save($this->pcFile);
   }

   // public function closeXlsAsCsvIO($p_cFilNom) {
   //    $lo = PHPExcel_IOFactory::createWriter($this->loXls, 'CSV')->setEnclosure('');//->setDelimiter(";")
   //    $this->pcFile = './Jobs/'.$p_cFilNom.'.csv';                        
   //    $lo->save($this->pcFile);
   // }
   
   // public function closeXlsAsCsvWithQuotesIO($p_cFilNom) {
   //    $lo = PHPExcel_IOFactory::createWriter($this->loXls, 'CSV');//->setDelimiter(";")
   //    $this->pcFile = './Jobs/'.$p_cFilNom.'.csv';                        
   //    $lo->save($this->pcFile);
   // }

   // public function openXlsmIO($p_cFilXls, $p_cPrefij) {
   //    $this->loXls = $this->lo->load('./Xls/'.$p_cFilXls.'.xlsm');
   //    $lcFile = $p_cPrefij.rand();
   //    $this->pcFile = './FILES/'.$lcFile.'.xlsm';
   // }
   
   // public function closeXlsIOCuadroComparativo($p_cFile) {
   //    $lo = PHPExcel_IOFactory::createWriter($this->loXls, 'Excel2007');
   //    $this->pcFile = './FILES/'.$p_cFile.'.xlsm';
   //    $lo->save($this->pcFile);
   // }
   
   // public function getColor() {
   //    $lxValue = $this->loXls->getActiveSheet()->getStyle('D2')->getFill()->getStartColor()->getRGB();
   //    return $lxValue;
   // }

   // public function mergeCells($p_nSheet, $p_cDesde, $p_cHasta) {
   //    $this->loXls->setActiveSheetIndex($p_nSheet)->mergeCells($p_cDesde.':'.$p_cHasta);
   // }

   // public function openXlsIOtoHTML($p_cFilXls, $p_cPrefij) {
   //    $this->loXls = $this->lo->load('./Xls/'.$p_cFilXls.'.xlsx');
   //    $lcFile = $p_cPrefij.rand();
   //    $this->pcFile = './FILES/'.$lcFile.'.html';
   // }

   // public function closeXlsIOtoHTML() {
   //    $lo = PHPExcel_IOFactory::createWriter($this->loXls, 'HTML');
   //    $lo->save($this->pcFile);
   // }

   public function setImage($p_nSheet, $p_cCol, $p_nRow, $p_cPath, $p_nX, $p_nY, $p_nWidth, $p_nHeight) {
      $lo = new PHPExcel_Worksheet_Drawing();
      $lo->setPath($p_cPath);
      $lo->setCoordinates($p_cCol.$p_nRow);
      $lo->setOffsetX($p_nX);
      $lo->setOffsetY($p_nY);
      $lo->setWidth($p_nWidth);
      $lo->setHeight($p_nHeight);
      $this->loXls->setActiveSheetIndex($p_nSheet);
      $lo->setWorksheet($this->loXls->getActiveSheet());
   }
}

class CRtf extends CBase {
   public $pcFile, $paArray, $pcFilRet, $pcCodUsu, $paAArray, $pcFileName;
   protected $lcFolXls, $lcFolSal, $lcFilRet, $lcFilInp, $lcTodo, $lp;

   function __construct () {
      parent::__construct();
      $this->paArray = null;
      $this->lcFolXls = './Xls/';
      $this->lcFolSal = './Ficheros/';
   }

   public function omInit() {
      $lcFile1 = $this->lcFolXls.$this->pcFile.'.rtf';
      if (empty($this->pcCodUsu)) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      } elseif (!is_file($lcFile1)) {
         $this->pcError = 'ARCHIVO DE ORIGEN NO EXISTE ['.$lcFile1.']';
         return false;
      }
      if (empty($this->pcFilRet)) {
         $this->pcFilRet = $this->lcFolSal.$this->pcFileName.'.doc';
      }
      $this->lp = fopen($this->pcFilRet, 'w');
      // Lee archivo formato
      $laTexto = file($lcFile1);
      $lnSize = sizeof($laTexto);
      $this->lcTodo = '';
      for ($i = 0;$i < $lnSize; $i++) {
          $this->lcTodo .= $lcTodo.$laTexto[$i];
      }
      return true;
   }

   public function omInicializar() {
      $lcFile1 = $this->lcFolXls.$this->pcFile.'.rtf';
      if (empty($this->pcCodUsu)) {
         $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO';
         return false;
      } elseif (!is_file($lcFile1)) {
         $this->pcError = 'ARCHIVO DE ORIGEN NO EXISTE';
         return false;
      }
      if (empty($this->pcFilRet)) {
         $this->pcFilRet = $this->lcFolSal.$this->pcFile.'_'.$this->pcCodUsu.'.doc';
      }
      $this->lp = fopen($this->pcFilRet, 'w');
      // Lee archivo formato
      $laTexto = file($lcFile1);
      $lnSize = sizeof($laTexto);
      $this->lcTodo = '';
      for ($i = 0;$i < $lnSize; $i++) {
          $this->lcTodo .= $lcTodo.$laTexto[$i];
      }
      return true;
   }

   protected function mxTerminar() {
      fputs($this->lp, $this->lcTodo);
      fclose($this->lp);
      return true;
   }

   public function omGenerar($p_lClose = false) {
      if (!(is_array($this->paArray) and count($this->paArray) > 0)) {
         fclose($this->lp);
         $this->pcError = 'ARREGLO DE DATOS NO DEFINIDO';
         return false;
      }
      // Reemplazo de variables
      foreach ($this->paArray as $lcValor1 => $lcValor2) {
         $lcValor2 = utf8_decode($lcValor2);
         $this->lcTodo = str_replace($lcValor1, $lcValor2, $this->lcTodo);
      }
      if ($p_lClose) {
         $this->mxTerminar();
      }
      return true;
   }

   public function omGenerarArray($p_lClose = false) {
      if (!(is_array($this->paAArray) and count($this->paAArray) > 0)) {
         fclose($this->lp);
         $this->pcError = 'ARREGLO DE DATOS NO DEFINIDO';
         return false;
      }
      foreach ($this->paAArray as $lcValor1 => $lcValor2) {
         $loTabla = new RTFTable($lcValor2[0], $lcValor2[1]);
         if ($lcValor2[3]=='') {
            $loTabla->SetWideColsTable(round(10500/$lcValor2[0]));
         }else {
            for ($k = 0;$k < count($lcValor2[3]);$k++) {
                $loTabla->SetWideColTable($k,$lcValor2[3][$k]);
            }
         }
         //Llenar Tabla cn arreglo pos:2
         for ($i = 0;$i < count($lcValor2[2]);$i++) {
             for ($j = 0;$j < count($lcValor2[2][0]);$j++) {
                 $lcValor2[2][$i][$j] = utf8_decode($lcValor2[2][$i][$j]);
                 if ($j ==0 ) {
                    //Centrado
                    $loTabla->SetElementCell($i,$j,'\\qc '.$lcValor2[2][$i][$j]);
                 }else
                    $loTabla->SetElementCell($i,$j,' '.$lcValor2[2][$i][$j]);
             }
         }
         $this->lcTodo = str_replace($lcValor1,$loTabla->GetTable() ,$this->lcTodo);
      }
      if ($p_lClose) {
         $this->mxTerminar();
      }
      return true;
   }

   protected function mxLeerArchivo() {
      if (!is_file($this->pcFile)) {
         $this->pcError = '<DATA><ERROR>ARCHIVO DE ORIGEN NO EXISTE</ERROR></DATA>';
         return false;
      }
      $laTexto = file($this->pcFile);
      $lnSize = sizeof($laTexto);
      $lcTodo = '';
      for ($i = 0;$i < $lnSize;$i++) {
          $lcTodo = $lcTodo.$laTexto[$i];
      }
      return $lcTodo;
   }

   public function omProcesar() {
      $this->lcFilRet = $this->lcFolSal.$this->pcFile.'_'.$this->pcCodUsu.'.rtf';//-- DEFINIMOS EL NOMBRE DEL NUEVO FICHERO
      $this->pcFile = $this->lcFolXls.$this->pcFile.'.rtf';
      if ($lcTexto = $this->mxLeerArchivo()) {
         $lp = fopen($this->lcFilRet, 'w');
         if (is_array($this->paArray) and count($this->paArray) > 0) {
            foreach($this->paArray as $lcValor1 =>$lcValor2) {//-- REEMPLAZAMOS LAS VARIABLES
               $lcValor2 = utf8_decode($lcValor2);
               $lcTexto = str_replace($lcValor1, $lcValor2 ,$lcTexto);
            }
         }
         fputs($lp, $lcTexto);
         fclose($lp);
         header ('Content-Disposition: attachment;filename = '.$this->lcFilRet.'\n\n');
         header ('Content-Type: application/octet-stream');
         readfile($this->lcFilRet);
      }
   }
}


class CNumeroLetras {
   protected $lcVacio, $lcNegati;

   public function __construct() {
      //parent::__construct();
      $this->lcVacio = '';
      $this->lcNegati = 'Menos';
   }

   public function omNumeroLetras($p_nNumero, $p_cDesMon) {
      $lcSigno = '';
      if (floatVal($p_nNumero) < 0) {
         $lcSigno = $this->lcNegati.' ';
      }
      $lcNumero = number_format($p_nNumero, 2, '.', '');
      // Posicion del punto decimal
      $Pto = strpos($lcNumero, '.');
      if ($Pto === false) {
         $lcEntero = $lcNumero;
         $lcDecima = $this->lcVacio;
      } else {
         $lcEntero = substr($lcNumero, 0, $Pto);
         $lcDecima =  substr($lcNumero, $Pto+1);
      }
      if ($lcEntero == '0' || $lcEntero == $this->lcVacio) {
         $lcNumero = 'Cero ';
      } elseif (strlen($lcEntero) > 7) {
         $lcNumero = $this->SubValLetra(intval(substr($lcEntero, 0,  strlen($lcEntero) - 6)))."Millones " . $this->SubValLetra(intval(substr($lcEntero, -6, 6)));
      } else {
         $lcNumero = $this->SubValLetra(intval($lcEntero));
      }
      if (substr($lcNumero,-9, 9) == "Millones " || substr($lcNumero,-7, 7) == "Millón ") {
         $lcNumero = $lcNumero . "de ";
      }
      if ($lcDecima != $this->lcVacio) {
       $lcNumero = $lcNumero . "CON " . $lcDecima. "/100";
      }
      $lcNumero = $lcNumero . ' ' . $p_cDesMon;
      $letrass=$lcSigno . $lcNumero;
      return ($lcSigno . $letrass);
   }

   protected function SubValLetra($numero) {
      $Ptr="";
      $n=0;
      $i=0;
      $x ="";
      $Rtn ="";
      $Tem ="";
      $x = trim("$numero");
      $n = strlen($x);
      $Tem = $this->lcVacio;
      $i = $n;
      while($i > 0) {
         $Tem = $this->Parte(intval(substr($x, $n - $i, 1).str_repeat('0', $i - 1)));
         if ($Tem != "Cero") {
            $Rtn .= $Tem . ' ';
         }
         $i = $i - 1;
      }
      //--------------------- GoSub FiltroMil ------------------------------
      $Rtn = str_replace(" Mil Mil", " Un Mil", $Rtn);
      while (1) {
         $Ptr = strpos($Rtn, "Mil ");
         if (!($Ptr===false)) {
            if (! (strpos($Rtn, "Mil ",$Ptr + 1) === false)) {
               $this->ReplaceStringFrom($Rtn, "Mil ", "", $Ptr);
            } else {
               break;
            }
         } else {
            break;
         }
      }
      //--------------------- GoSub FiltroCiento ------------------------------
      $Ptr = -1;
      do {
         $Ptr = strpos($Rtn, "Cien ", $Ptr+1);
         if (!($Ptr===false)) {
            $Tem = substr($Rtn, $Ptr + 5 ,1);
            if ($Tem == "M" || $Tem == $this->lcVacio)
             ;
            else
               $this->ReplaceStringFrom($Rtn, "Cien", "Ciento", $Ptr);
         }
      } while(!($Ptr === false));
      //--------------------- FiltroEspeciales ------------------------------
      $Rtn=str_replace("Diez Un", "Once", $Rtn);
      $Rtn=str_replace("Diez Dos", "Doce", $Rtn);
      $Rtn=str_replace("Diez Tres", "Trece", $Rtn);
      $Rtn=str_replace("Diez Cuatro", "Catorce", $Rtn);
      $Rtn=str_replace("Diez Cinco", "Quince", $Rtn);
      $Rtn=str_replace("Diez Seis", "Dieciseis", $Rtn);
      $Rtn=str_replace("Diez Siete", "Diecisiete", $Rtn);
      $Rtn=str_replace("Diez Ocho", "Dieciocho", $Rtn);
      $Rtn=str_replace("Diez Nueve", "Diecinueve", $Rtn);
      $Rtn=str_replace("Veinte Un", "Veintiun", $Rtn);
      $Rtn=str_replace("Veinte Dos", "Veintidos", $Rtn);
      $Rtn=str_replace("Veinte Tres", "Veintitres", $Rtn);
      $Rtn=str_replace("Veinte Cuatro", "Veinticuatro", $Rtn);
      $Rtn=str_replace("Veinte Cinco", "Veinticinco", $Rtn);
      $Rtn=str_replace("Veinte Seis", "Veintiseís", $Rtn);
      $Rtn=str_replace("Veinte Siete", "Veintisiete", $Rtn);
      $Rtn=str_replace("Veinte Ocho", "Veintiocho", $Rtn);
      $Rtn=str_replace("Veinte Nueve", "Veintinueve", $Rtn);
      //--------------------- FiltroUn ------------------------------
      if (substr($Rtn,0,1) == "M") {
         $Rtn = "Un " . $Rtn;
      }
      //--------------------- Adicionar Y ------------------------------
      for ($i=65; $i<=88; $i++) {
          if ($i != 77) {
             $Rtn=str_replace("a " . Chr($i), "* y " . Chr($i), $Rtn);
          }
      }
      $Rtn=str_replace("*", "a" , $Rtn);
      return($Rtn);
   }


   protected function ReplaceStringFrom(&$x, $OldWrd, $NewWrd, $Ptr) {
      $x = substr($x, 0, $Ptr)  . $NewWrd . substr($x, strlen($OldWrd) + $Ptr);
   }

   protected function Parte($x) {
      $Rtn='';
      $t='';
      $i='';
      Do {
         switch($x) {
            Case 0:  $t = "Cero";break;
            Case 1:  $t = "Un";break;
            Case 2:  $t = "Dos";break;
            Case 3:  $t = "Tres";break;
            Case 4:  $t = "Cuatro";break;
            Case 5:  $t = "Cinco";break;
            Case 6:  $t = "Seis";break;
            Case 7:  $t = "Siete";break;
            Case 8:  $t = "Ocho";break;
            Case 9:  $t = "Nueve";break;
            Case 10: $t = "Diez";break;
            Case 20: $t = "Veinte";break;
            Case 30: $t = "Treinta";break;
            Case 40: $t = "Cuarenta";break;
            Case 50: $t = "Cincuenta";break;
            Case 60: $t = "Sesenta";break;
            Case 70: $t = "Setenta";break;
            Case 80: $t = "Ochenta";break;
            Case 90: $t = "Noventa";break;
            Case 100: $t = "Cien";break;
            Case 200: $t = "Doscientos";break;
            Case 300: $t = "Trescientos";break;
            Case 400: $t = "Cuatrocientos";break;
            Case 500: $t = "Quinientos";break;
            Case 600: $t = "Seiscientos";break;
            Case 700: $t = "Setecientos";break;
            Case 800: $t = "Ochocientos";break;
            Case 900: $t = "Novecientos";break;
            Case 1000: $t = "Mil";break;
            Case 1000000: $t = "Millón";break;
         }
         if ($t == $this->lcVacio) {
            $i = $i + 1;
            $x = $x / 1000;
            if ($x== 0) {
               $i = 0;
            }
         } else {
            break;
         }

      } while($i != 0);
      $Rtn = $t;
      Switch($i) {
         Case 0: $t = $this->lcVacio;break;
         Case 1: $t = " Mil";break;
         Case 2: $t = " Millones";break;
         Case 3: $t = " Billones";break;
      }
      return($Rtn.$t);
   }
}


class CUploadFile extends CBase {
   public $paData, $paFile;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paFile = null;
   }

   public function omUploadFile() {
      $llOk = $this->mxValParamUploadFile();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxUploadFile();
      return true;
   }

   protected function mxValParamUploadFile() {
      if (!isset($this->paFile)) {
         $this->pcerror = 'ARREGLO [FILE] NO DEFINIDO';
         return false;
      }
      if (!isset($this->paData['PATH'])) {
         $this->paData['PATH'] = './';
      } 
      return true;
   }
   
   protected function mxUploadFile() {
      // Verifica que no haya error
      if ($this->paFile['error'] != 0) {
         $this->pcError = 'ERROR AL SUBIR ARCHIVO';
         return false;
      }
      // Verifica tamaño
      if (isset($this->paData['PNTAMANO'])) {
         if ($this->paFile['size'] > $this->paData['PNTAMANO']) {
            $this->pcError = "TAMAÑO DEL ARCHIVO [{$this->paFile['name']}] ES MAYOR QUE [{$this->paData['PNTAMANO']}]. NO SE PUDO SUBIR";
            return false;
         }
      }
      $this->paData['CFILEUP'] = $this->paData['PATH'].uniqid('', true).'.'.strtolower(pathinfo($this->paFile['name'], PATHINFO_EXTENSION));
      move_uploaded_file($this->paFile['tmp_name'], $this->paData['CFILEUP']);
      return true;
   }  
}

function fxAlert($p_Message) {
   echo "<script type=\"text/javascript\">";
   echo "alert('$p_Message')";
   echo "</script>";  
}

function fxHeader($p_cLocation, $p_cMensaje = '') {
   if (empty($p_cMensaje)) {
      $lcScript = "window.location='$p_cLocation';";
   } else {
      $lcScript = "alert('$p_cMensaje');window.location='$p_cLocation';";
      //$lcScript = "window.location='$p_cLocation';alert('$p_cMensaje');";
   }
   echo '<script>'.$lcScript.'</script>';
}

function right($lcCadena, $count) {
   return substr($lcCadena, ($count * -1));
}

function left($lcCadena, $count) {
   return substr($lcCadena, 0, $count);
}

function fxNumber($p_nNumero, $p_nLength, $p_nDecimal) {
   $lcNumero = number_format($p_nNumero, $p_nDecimal, '.', ',');
   $lcCadena = str_repeat(' ', $p_nLength).$lcNumero;
   return right($lcCadena, $p_nLength);
}
        
function fxString($p_cCadena, $p_nLength) {
   #$i = substr_count($p_cCadena, 'Ñ');
   $lcCadena = $p_cCadena.str_repeat(' ', $p_nLength);
   #$lcCadena = substr($lcCadena, 0, $p_nLength + $i);
   $lcCadena = substr($lcCadena, 0, $p_nLength);
   return $lcCadena;
}

function fxStringf($p_cCadena, $p_nLength) {
   $i = substr_count($p_cCadena, 'Ñ');
   $lcCadena = $p_cCadena.str_repeat(' ', $p_nLength);
   $lcCadena = substr($lcCadena, 0, $p_nLength + $i);
   return $lcCadena;
}

function fxInitSession() {
   if (!(isset($_SESSION["GCNOMBRE"]) and isset($_SESSION["GCCODUSU"]) and isset($_SESSION['GCCENCOS']))) {
      if (isset($_GET['dir'])) {
         $_SESSION["URL"] = $_GET['dir'];
      }
      return false;
   }
   return true;
}

function fxInitSessionInvitados() {
   if (!(isset($_SESSION["GCNRODNI"]) and isset($_SESSION["GCCODUSU"]) and isset($_SESSION["GCNOMBRE"]))) {
      if (isset($_GET['dir'])) {
         $_SESSION["URL"] = $_GET['dir'];
      }
      return false;
   }
   return true;
}

function fxInitSessionP() {
   if (!(isset($_SESSION["GCNRORUC"]) and isset($_SESSION["GCRAZSOC"]))) {
      return false;
   }
   return true;
}

function fxInitSessionEventos(){
   if (!(isset($_SESSION["GCNOMBRE"]) and isset($_SESSION['GCNRODNI']))) {
      return false;
   }
   return true;
}

function fxInitSessionPT() {
   if (!(isset($_SESSION["GCNOMBRE"]) and isset($_SESSION['GCNRODNI']) and isset($_SESSION['GCCODALU']) and isset($_SESSION['GACODALU']))) {
      return false;
   }
   return true;
}

function fxInitSessionTesoreriaCobranza() {
   if (!(isset($_SESSION["GCNOMBRE"]) and isset($_SESSION["GCCODUSU"]) and isset($_SESSION["GCNIVEL"]) and isset($_SESSION["CORIGEN"]))) {
      return false;
   }
   return true;
}

function fxInitSessionTramites() {
   if (!(isset($_SESSION["GADATA"]["CCODALU"]) and isset($_SESSION['GADATA']['CNRODNI']) and isset($_SESSION['GADATA']['CUNIACA']))) {
      return false;
   }
   return true;
}

function fxInitSessionContratos(){
    if (!(isset($_SESSION["GCNOMBRE"]) and isset($_SESSION['GCNRODNI']))) {
       return false;
    }
    return true;
}

function fxSubstrCount($p_cString) {
   $i = substr_count($p_cString, 'Á');
   $i += substr_count($p_cString, 'É');
   $i += substr_count($p_cString, 'Í');
   $i += substr_count($p_cString, 'Ó');
   $i += substr_count($p_cString, 'Ú');
   $i += substr_count($p_cString, 'Ñ');
   $i += substr_count($p_cString, 'ñ');
   $i += substr_count($p_cString, 'º');
   $i += substr_count($p_cString, '§');
   $i += substr_count($p_cString, 'ø');
   $i += substr_count($p_cString, 'á');
   $i += substr_count($p_cString, 'é');
   $i += substr_count($p_cString, 'í');
   $i += substr_count($p_cString, 'ó');
   $i += substr_count($p_cString, 'ú');
   $i += substr_count($p_cString, 'Ü');
   $i += substr_count($p_cString, '°');
   return $i;
}

function fxStringFixed($p_cString, $p_nLenght) {
   //$lcString = fxString($p_cString, $p_nLenght);
   $i= fxSubstrCount($p_cString);
   $lcString = fxString($p_cString, $p_nLenght + $i);
   return $lcString;
}

function fxStringTail($p_cString, $p_nIndex) {
   $lcString = $p_cString;
   $lcString = substr($lcString, $p_nIndex);
   return $lcString;
}

function fxString2($p_cString, $p_nLenght) {
   $lcString = utf8_decode($p_cString);
   $lcString = $lcString.str_repeat(' ', $p_nLenght);
   $lcString = substr($lcString, 0, $p_nLenght);
   return $lcString;
}

function fxStringTail2($p_cString, $p_nIndex) {
   $lcString = utf8_decode($p_cString);
   $lcString = substr($lcString, $p_nIndex);
   return $lcString;
}

function fxStringCenter($p_cString, $p_nLenght) {
   $lcString = str_pad($p_cString, $p_nLenght, ' ', STR_PAD_BOTH);
   $lcString = fxStringFixed($lcString, $p_nLenght);
   return $lcString;
}

function fxDocumento($FilRet){
   $lcTxt = "<script type=text/javascript>";
   $lcTxt .= "window.open('$FilRet','_blank', 'toolbar=yes, scrollbars=yes, resizable=yes, width=950, height=650');";
   $lcTxt .= "</script>";
   echo $lcTxt;
}

function fxDescargar($pcFilRet,$pcName = null){
   if (!$pcName) {
      $pcName = $position = substr($pcFilRet,strripos($pcFilRet, '/')+1);
   }
   $lcTxt = "<script type=text/javascript>";
   $lcTxt .= "var link = document.createElement('a');";
   $lcTxt .= "link.href = '$pcFilRet';";
   $lcTxt .= "link.download = '$pcName';";
   $lcTxt .= "link.click();";
   $lcTxt .= "</script>";
   echo $lcTxt;
}

function fxGenerarQR($p_cUrl,$p_cFolder,$p_cNombre){
   $lcDirecc = $p_cFolder.$p_cNombre.'.png';
   $errorCorrectionLevel = 'M'; 
   $matrixPointSize = 7;
   $lcCodiQR = QRcode::png($p_cUrl, $lcDirecc, $errorCorrectionLevel, $matrixPointSize, 2);
   return $lcDirecc;
}

function fxSubirImagenJPG($p_oFile,$p_cFolder,$p_cNombre){
   $lcDir = 'img/'.$p_cFolder;
   $tmp_name = $p_oFile["tmp_name"];
   $lcType = pathinfo($p_oFile['name'], PATHINFO_EXTENSION);
   if ($lcType != 'jpg') {
      return false;
   }
   return move_uploaded_file($tmp_name, $lcDir.'/'.$p_cNombre.'.jpg');
}

function fxSubirPDF($p_oFile,$p_cFolder,$p_cNombre){
   $lcDir = 'Docs/'.$p_cFolder;
   $tmp_name = $p_oFile["tmp_name"];
   $lcType = pathinfo($p_oFile['name'], PATHINFO_EXTENSION);
   if ($lcType != 'pdf') {
      return false;
   }
   return move_uploaded_file($tmp_name, $lcDir.'/'.$p_cNombre.'.pdf');
}

function fxSubirCSV($p_oFile,$p_cFolder,$p_cNombre){
   $tmp_name = $p_oFile["tmp_name"];
   $lcType = pathinfo($p_oFile['name'], PATHINFO_EXTENSION);
   if ($lcType != 'csv') {
      return false;
   }
   return move_uploaded_file($tmp_name, $p_cFolder.'/'.$p_cNombre.'.csv');
}

function fxSubirXls($p_oFile,$p_cFolder,$p_cNombre){
   $lcDir = 'Docs/'.$p_cFolder;
   $tmp_name = $p_oFile["tmp_name"];
   $lcType = pathinfo($p_oFile['name'], PATHINFO_EXTENSION);
   if ($lcType != 'xls' && $lcType != 'xlsx') {
      return false;
   }
   return move_uploaded_file($tmp_name, $lcDir.'/'.$p_cNombre.'.'.$lcType);
}

function fxSubirXlsTmp($p_oFile,$p_cNombre){
   $lcDir = 'Xls/';
   $tmp_name = $p_oFile["tmp_name"];
   $lcType = pathinfo($p_oFile['name'], PATHINFO_EXTENSION);
   if ($lcType != 'xls' && $lcType != 'xlsx') {
      return false;
   }
   return move_uploaded_file($tmp_name, $lcDir.'/'.$p_cNombre.'.'.$lcType);
}

 function array_change_key_case_recursive($arr){
   return array_map(function($item){
      if(is_array($item))
            $item = array_change_key_case_recursive($item);
      return $item;
   },array_change_key_case($arr, CASE_UPPER));
}

function validateDate($date, $format = 'Y-m-d H:i:s') {
   $d = DateTime::createFromFormat($format, $date);
   return var_dump($d && $d->format($format) == $date);
}

function validateDateTime($dateStr, $format)
{
    date_default_timezone_set('UTC');
    $date = DateTime::createFromFormat($format, $dateStr);
    return $date && ($date->format($format) === $dateStr);
}

function fxCleanString($p_cCadena) {
   $lcCadena = str_replace('Ñ', 'N', $p_cCadena);
   $lcCadena = str_replace('Á', 'A', $lcCadena);
   $lcCadena = str_replace('É', 'E', $lcCadena);
   $lcCadena = str_replace('Í', 'I', $lcCadena);
   $lcCadena = str_replace('Ó', 'O', $lcCadena);
   $lcCadena = str_replace('Ú', 'U', $lcCadena);
   $lcCadena = str_replace('á', 'A', $lcCadena);
   $lcCadena = str_replace('é', 'E', $lcCadena);
   $lcCadena = str_replace('í', 'I', $lcCadena);
   $lcCadena = str_replace('ó', 'O', $lcCadena);
   $lcCadena = str_replace('ú', 'U', $lcCadena);
   $lcCadena = str_replace('"', '', $lcCadena);
   $lcCadena = str_replace("'", "", $lcCadena);
   return $lcCadena;
}

function fxInitSessionPTV2() {
   if (!(isset($_SESSION["GCNOMBRE"]) and isset($_SESSION['GCNRODNI']))) {
      return false;
   }
   return true;
}

//Invoca Python
function fxInvocaPython($p_cCommand) { 
   $lcData = shell_exec($p_cCommand);
   if (is_null($lcData)) {
      $lcData = '{"ERROR":"ERROR EN SHELL_EXEC"}';
   }
   $laArray = json_decode($lcData, true);
   if (is_null($laArray)) {
      $laArray = ['ERROR'=>'ERROR EN JSON'];
   }
   return $laArray;
}

function fxExecuteShellCommand($p_cCommand) {
   $lcCommand = str_replace('"', '\"', $p_cCommand);   // Solo en windows
   $lcJson = shell_exec($lcCommand);
   try {
      $laData = json_decode($lcJson, true);
   } catch (Exception $e) {
      $laData = null;
   }
   return $laData;
}

function fxCorrelativo($p_cCodigo) {
   $lcCodigo = $p_cCodigo;
   $i = strlen($p_cCodigo) - 1;
   while ($i >= 0) {
       $lcDigito = substr($p_cCodigo, $i, 1);
       if ($lcDigito == '9') {
          $lcDigito = 'A';
       } elseif ($lcDigito < '9') {
          $lcDigito = strval(intval($lcDigito) + 1);
       } elseif ($lcDigito < 'Z') {
          $lcDigito = chr(ord($lcDigito) + 1);
       } elseif ($lcDigito == 'Z') {
          $lcDigito = '0';
       }
       $lcCodigo[$i] = $lcDigito;
       if ($lcDigito != '0') {
          break;
       }
       $i--;
   }
   return $lcCodigo;
}

function fxAlertNew($p_Message, $p_IdAlert, $p_nTimer = 2000) {
   if($p_IdAlert===1){
      fxAlertExito($p_Message, $p_nTimer);
   } elseif($p_IdAlert===0){
      fxAlertError($p_Message);
   } elseif($p_IdAlert===2){
      fxAlertExitoAceptar($p_Message);
   } elseif($p_IdAlert===3){
      fxAlertErrorAceptar($p_Message);
   }
}

function fxAlertExito($p_Message, $p_nTimer) {
   echo "<script type=\"text/javascript\">";
   echo "Swal.fire({";
   echo "icon: 'success',";
   echo "title: '$p_Message',";
   echo "showConfirmButton: false,";
   echo "timer: $p_nTimer";
   echo "})";
   echo "</script>"; 
}

function fxAlertExitoAceptar($p_Message) {
   echo "<script type=\"text/javascript\">";
   echo "Swal.fire({";
   echo "icon: 'success',";
   echo "title: '$p_Message',";
   echo "showConfirmButton: true";
   echo "})";
   echo "</script>"; 
}

function fxAlertError($p_Message) {
   echo "<script type=\"text/javascript\">";
   echo "Swal.fire({";
   echo "icon: 'error',";
   echo "title: '$p_Message',";
   echo "showConfirmButton: false,";
   echo "timer: 2000";
   echo "})";
   echo "</script>";  
}

function fxAlertErrorAceptar($p_Message) {
   echo "<script type=\"text/javascript\">";
   echo "Swal.fire({";
   echo "icon: 'error',";
   echo "title: '$p_Message',";
   echo "showConfirmButton: true";
   echo "})";
   echo "</script>";  
}
?>