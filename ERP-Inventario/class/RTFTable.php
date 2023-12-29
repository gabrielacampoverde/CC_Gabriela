<?php
  class RTFTable {
    private $NumRows = 1;
    private $NumCols = 1;
    private $Cells = array();
    private $BasicFormatCells = array();
    private $OtherFormatCells = array();
    private $MergedVertCells = array();
    private $MergedHorzCells = array();
    private $ElementCells = array();
    private $WideColCells = array();
    private $TablEspMd = 0;
    private $TablPosic = 0;//Position
    private $TablAligH = 1;//Alineacion = Center(Center, Left, Right)0xdefect
    private $TablEdgeT = 1;//Border Type = Single
    private $TablThick = 1; //Grosor
    private $TablColor = 1;
    private $CellAligV = 0;//Alineacion Vertical = Top(Top, Center, Botton)
    private $CellEdgeT = 2;//Border Type = Single
    private $CellThick = 1; //Grosor
    private $CellColor;
    private $WideCols = 2100;//1200 defecto
    private $BasicFormat = "";
    private $OtherFormat = "";

    private $aBorde = array(
      '\\brdrnone', //No border.
      '\\brdrs', //Single-thickness border.
      '\\brdrth', //Double-thickness border.
      '\\brdrsh', //Shadowed border.
      '\\brdrdb', //Double border.
      '\\brdrdot', // Dotted border.
      '\\brdrdash', //Dashed border.
      '\\brdrinset', //Inset border.
      '\\brdroutset'  //Outset border.
    );//Arreglo de Tipos de Borde

    private $aAlign = array(
      "",//  Dejar Igual
      "\\qc",//  Centrar
      "\\ql",//  Izquierda
      "\\qr",//  Derecha
      "\\qj",//  Justificar
      "\\qd" //  Distribuir
    );//Tipo De AlineaciÃ³n Horizontal de Texto

    private $aAliHT = array(
      '\\trqc', //Centers a table row with respect to its containing column.
      '\\trql', //Left-justifies a table row with respect to its containing column.
      '\\trqr'  //Right-justifies a table row with respect to its containing column.
    );//Tipo De AlineaciÃ³n Horizontal de Tabla

    private $aAliVC = array(
      "\\clvertalt", //Text is top-aligned in cell (the default).
      "\\clvertalc", //Text is centered vertically in cell.
      "\\clvertalb"  //Text is bottom-aligned in cell.
    );//Tipo De AlineaciÃ³n Vertical de Celda

    private $aMrgVC = array(
      "", //Ninguna Mezcla.
      "\\clvmgf", //The first cell in a range of table cells to be vertically merged
      "\\clvmrg"  //Contents of the table cell are vertically merged with those of the preceding cell
    );//Tipo De UniÃ³n Vertical de Celda

    public function __construct($NumCols, $NumRows) {
      $this->SetRowsCols($NumCols, $NumRows);
    }

    public function SetRowsCols($NumCols, $NumRows) {
      $this->NumRows=$NumRows;
      $this->NumCols=$NumCols;
      $this->Cells[$this->NumCols][$this->NumRows];
      $this->BasicFormatCells[$this->NumCols][$this->NumRows];
      $this->OtherFormatCells[$this->NumCols][$this->NumRows];
      $this->MergedVertCells[$this->NumCols][$this->NumRows];
      $this->MergedHorzCells[$this->NumCols][$this->NumRows];
      $this->ElementCells[$this->NumCols][$this->NumRows];
      $this->WideColCells[$this->NumCols];
      for($i = 0; $i < $this->NumCols;$i++){
        for($j = 0; $j < $this->NumRows;$j++){
          $this->Cells[$i][$j] = "";
          $this->BasicFormatCells[$i][$j] = "";
          $this->OtherFormatCells[$i][$j] = "";
          $this->MergedVertCells[$i][$j] = 0;
          $this->MergedHorzCells[$i][$j] = 0;
          $this->ElementCells[$i][$j] = 0;
        }
        $this->WideColCells[$i] = 0;
      }
    }

    public function SetFormatTable($EspMd,$Posic,$AligH,$EdgeT,$Thick,$Color) {
      $this->TablEspMd = $EspMd;
      $this->TablPosic = $Posic;
      $this->TablAligH = $AligH;
      $this->TablEdgeT = $EdgeT;
      $this->TablThick = $Thick;
      $this->TablColor = $Color;
    }

    private function GetHeaderTable() {
      $sEspMd = "\\trgaph".$this->TablEspMd;
      $sPosic = "\\trleft".$this->TablPosic;
      global $aAliHT;
      $sHdT = "\n\\par\\ltrrow\\trowd".$sEspMd.$sPosic.$this->aAliHT[$this->TablAligH];
      global $aBorde;
      $sBorde = ($this->TablEdgeT==0)?"":$this->aBorde[$this->TablEdgeT];//Tipo de Borde
      $sHdT = ($this->TablEdgeT==0)?$sHdT." \n":$sHdT."\\trhdr \n";
      $sGrosr = ($this->TablThick==0)?"":"\\brdrw".$this->TablThick;
      $sColor = ($this->TablColor==0)?"":"\\brdrcf".$this->TablColor;
      //BordesDeTabla
      $sTop = "\\trbrdrt".$sBorde.$sGrosr.$sColor." ";//Table row border top
      $sBot = "\\trbrdrb".$sBorde.$sGrosr.$sColor." ";//Table row border bottom
      $sLft = "\\trbrdrl".$sBorde.$sGrosr.$sColor." ";//Table row border left
      $sRgt = "\\trbrdrr".$sBorde.$sGrosr.$sColor." ";//Table row border right
      $sHHz = "\\trbrdrh".$sBorde.$sGrosr.$sColor." ";//Table row border horizontal (inside).
      $sHVt = "\\trbrdrv".$sBorde.$sGrosr.$sColor." ";//Table row border vertical (inside).
      $sTablIH = $sHdT.$sTop.$sLft.$sBot.$sRgt."\n".$sHHz.$sHVt."\n";
      return $sTablIH;
    }

    private function SetNewRowTable() {
      $sEspMd = "\\trgaph".$this->TablEspMd;
      $sPosic = "\\trleft".$this->TablPosic;
      global $aAliHT;
      $sHdT = "\n\\ltrrow\\trowd".$sEspMd.$sPosic.$this->aAliHT[$this->TablAligH];
      $sHdT = $this->GetEndTable().$sHdT." \n";
      return $sHdT;
    }

    private function GetFooterTable() {
      //Fin de Cabecera de Tabla;
      $sTablFH = "\n\\pard\\intbl\\pard\\plain \n";
      return $sTablFH;
    }

    public function SetFormatCellsTable($AligV,$EdgeT,$Thick,$Color) {
      $this->CellAligV = $AligV;
      $this->CellEdgeT = $EdgeT;
      $this->CellThick = $Thick;
      $this->CellColor = $Color;
    }

    public function SetWideColsTable($WideCols) {
      $this->WideCols = $WideCols;
      for($i = 0; $i<$this->NumCols;$i++){
        $this->WideColCells[$i] = $this->WideCols;
      }
    }

    public function SetWideColTable($Col,$WideCols) {
      if($Col < $this->NumCols){
        $this->WideColCells[$Col] = $WideCols;
      }
    }

    public function SetBasicFormatTextTable($Align, $CFrnt, $CFond, $Fuente, $Talla) {
      //Formatos Necesarios
      $sCFrnt = "\\cf".$CFrnt;
      $sCFond = "\\highlight".$CFond;
      $sFuente = "\\f".$Fuente;
      $sTalla = "\\fs".(2*$Talla);
      $this->BasicFormat = $this->aAlign[$Align].$sCFrnt.$sCFond.$sFuente.$sTalla."\n";
    }

    public function SetOtherFormatTextTable($Negrt,$Italc,$Sbryd) {
      //Aplicar o Quitar Formatos Especiales
      $sNegrt = ($Negrt==0)?"\\b0":"\\b";//  Negrita
      $sItalc = ($Italc==0)?"\\i0":"\\i";//  ItÃ¡lica
      $sSbryd = ($Sbryd==0)?"\\ul0":"\\ul";// Subrayar
      $sFrmtSP = $sNegrt.$sItalc.$sSbryd."\n";
      $this->OtherFormat = $sFrmtSP;
    }

    public function SetBasicFormatTextCell($Col, $Row, $Align, $CFrnt, $CFond, $Fuente, $Talla) {
      if($Col<$this->NumCols && $Row<$this->NumRows){
        $sCFrnt = "\\cf".$CFrnt;
        $sCFond = "\\highlight".$CFond;
        $sFuente = "\\f".$Fuente;
        $sTalla = "\\fs".(2*$Talla);
        $this->BasicFormatCells[$Col][$Row] = $this->aAlign[$Align].$sCFrnt.$sCFond.$sFuente.$sTalla."\n";
      }
    }

    public function SetOtherFormatTextCell($Col, $Row, $Negrt,$Italc,$Sbryd) {
      if($Col<$this->NumCols && $Row<$this->NumRows){
        $sNegrt = ($Negrt==0)?"\\b0":"\\b";//  Negrita
        $sItalc = ($Italc==0)?"\\i0":"\\i";//  ItÃ¡lica
        $sSbryd = ($Sbryd==0)?"\\ul0":"\\ul";// Subrayar
        $this->OtherFormatCells[$Col][$Row] = $sNegrt.$sItalc.$sSbryd."\n";
      }
    }

    public function SetMergeVertCell($Col, $Row, $Value) {
      if($Col<$this->NumCols && $Row<$this->NumRows){
        $this->MergedVertCells[$Col][$Row] = $Value;
      }
    }

    public function SetMergeHorzCell($Col, $Row, $Value) {
      if($Col<$this->NumCols && $Row<$this->NumRows){
        $this->MergedHorzCells[$Col][$Row] = $Value;
      }
    }

    public function SetElementCell($Col, $Row, $Value) {
      if($Col<$this->NumCols && $Row<$this->NumRows){
        $this->Cells[$Col][$Row] = $Value;
        $this->ElementCells[$Col][$Row] = 1;
      }
    }

    public function SetTextCell($Col, $Row, $Value) {
      if($Col<$this->NumCols && $Row<$this->NumRows){
        $this->Cells[$Col][$Row] = $Value;
        $this->ElementCells[$Col][$Row] = 0;
      }
    }

    private function GetHeaderCell($Col, $Row){
      $sBorde = $this->CellEdgeT==0?"":$this->aBorde[$this->CellEdgeT];
      $sGrosr = $this->CellThick==0?"":"\\brdrw".$this->TablThick;
      $sColor = $this->CellColor==0?"":"\\brdrcf".$this->CellColor;
      $sTop = "\\clbrdrt".$sBorde.$sGrosr.$sColor." ";//Top table cell border
      $sBot = "\\clbrdrb".$sBorde.$sGrosr.$sColor." ";//Bottom table cell border
      $sLft = "\\clbrdrl".$sBorde.$sGrosr.$sColor." ";//Left row border left
      $sRgt = "\\clbrdrr".$sBorde.$sGrosr.$sColor." ";//Right table cell border
      $Width = 0;
      for ($i = 0; $i <= $Col;$i++){
        if ($this->WideColCells[$i]==0){
          $Width += $this->WideCols;
        } else {
          $Width += $this->WideColCells[$i];
        }
      }
      if ($this->MergedHorzCells[$Col][$Row]==1){
        for($i = $Col; $i < $this->NumCols;$i++){
          if($this->MergedHorzCells[$i][$Row]==2){
            if ($this->WideColCells[$i]==0){
              $Width += $this->WideCols;
            } else {
              $Width += $this->WideColCells[$i];
            }
          }
          if($this->MergedHorzCells[$i][$Row]==0) {
            $i = $this->NumRows;
          }
        }
      }
      $sLimit = "\\cellx".$Width." \n";
      $Merged = $this->MergedVertCells[$Col][$Row];
      $sCeldaH = $this->aMrgVC[$Merged].$this->aAliVC[$this->CellAligV].$sTop.$sLft.$sBot.$sRgt.$sLimit;
      if($this->MergedHorzCells[$Col][$Row]==2){
        $sCeldaH = "";
      }
      return $sCeldaH;
    }

    private function GetTextCell($Col, $Row) {
      $TextFormattedCell = "\\pard\\plain\\intbl\\ltrpar";

      if (!$this->BasicFormatCells[$Col][$Row]=="")
        $TextFormattedCell .= $this->BasicFormatCells[$Col][$Row];
      else
        $TextFormattedCell .= $this->BasicFormat;

      if (!$this->OtherFormatCells[$Col][$Row]=="")
        $TextFormattedCell .= $this->OtherFormatCells[$Col][$Row];
      else
        $TextFormattedCell .= $this->OtherFormat;
      $TextFormattedCell .= $this->Cells[$Col][$Row];
      $TextFormattedCell .= "\\cell \n";
      if($this->MergedHorzCells[$Col][$Row]==2){
        $TextFormattedCell = "";
      }
      return $TextFormattedCell;
    }

    private function GetElementCell($Col, $Row) {
      $TextFormattedCell = "\\pard\\plain\\intbl\\ltrpar";
      $TextFormattedCell .= $this->Cells[$Col][$Row];
      $TextFormattedCell .= "\\cell \n";
      if($this->MergedHorzCells[$Col][$Row]==2){
        $TextFormattedCell = "";
      }
      return $TextFormattedCell;
    }

    private function GetEndTable() {
      $sTablFF = "\n\\row\\pard \n";
      return $sTablFF;
    }

    public function GetTable(){
      $StringTable = "";
      if($this->NumRows>0){
        $StringTable .= $this->GetHeaderTable();
        for($i = 0; $i<$this->NumCols;$i++){
          $StringTable .= $this->GetHeaderCell($i,0);
        }
        $StringTable .= $this->GetFooterTable();

        for($i = 0; $i<$this->NumCols;$i++){
          if ($this->ElementCells[$i][0]==0){
            $StringTable .= $this->GetTextCell($i,0);
          } else {
            $StringTable .= $this->GetElementCell($i,0);
          }
        }

        if($this->NumRows>1){
          for ($j = 1; $j<$this->NumRows;$j++){
            $StringTable .= $this->SetNewRowTable();
            for($i = 0; $i<$this->NumCols;$i++){
              $StringTable .= $this->GetHeaderCell($i,$j);
            }
            $StringTable .= $this->GetFooterTable();

            for($i = 0; $i<$this->NumCols;$i++){
              if ($this->ElementCells[$i][$j]==0){
                $StringTable .= $this->GetTextCell($i,$j);
              } else {
                $StringTable .= $this->GetElementCell($i,$j);
              }
            }
          }
        }
        $StringTable .= $this->GetEndTable();
      }
      return $StringTable;
    }
}
?>