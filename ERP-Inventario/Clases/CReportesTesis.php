<?php
// ------------------------------------------------------------------------------
// Genera decretos y dictamenes del proceso de tesis
// 2020-05-27 AACH Creacion
// 2020-05-29 FPM  Actualiza rutas de directorios
// ------------------------------------------------------------------------------
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";
require_once 'Libs/fpdf/fpdf.php';
require_once 'Libs/phpqrcode/qrlib.php';

class CReportesTesis extends CBase
{

  public $paData, $paDatos, $paEstado, $pcFile;
  protected $laData;

  public function __construct()
  {
    parent::__construct();
    $this->paData = $this->laData = $this->paDatos = $this->paEstado = $this->paUniAca = $this->paPeriod = $this->paIdRela = $this->laDatos = null;
    $this->pcFile = 'FILES/R' . rand() . '.pdf';
    print_r(QRcode::png($_GET['code']));
  }

  protected function mxNombrePDF()
  {
    $this->pcFile = 'FILES/R' . rand() . '.pdf';
  }

  protected function mxValUsuario()
  {
    if (!isset($this->paData['CCODUSU']) or (strlen($this->paData['CCODUSU']) != 4)) {
      $this->pcError = "CODIGO DE USUARIO INVALIDO O NO DEFINIDO";
      return false;
    }
    return true;
  }

  // ------------------------------------------------------------------------------
  //  Cambia el estado a Procesado y genera el acta de la tesis
  //  2020-05-11 FLC
  // ------------------------------------------------------------------------------
  public function omMostrarActaTesis()
  {
    $llOk = $this->mxValUsuario();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect(); //      $llOk  = $loSql->omConnect(3);
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxRepMostrarActaTesis($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    if ($this->paDatos['CNIVEL'] == '01') {
      $llOk = $this->mxPrintMostrarActaTesisPreGrado();
    }
    elseif ($this->paDatos['CNIVEL'] == '03') {
      $llOk = $this->mxPrintMostrarActaTesisPostGradoMaestro();
    }
    elseif ($this->paDatos['CNIVEL'] == '04') {
      $llOk = $this->mxPrintMostrarActaTesisPostGradoDoctor();
    }
    elseif (in_array($this->paDatos['CNIVEL'], ['06', '09'])) {
      $llOk = $this->mxPrintMostrarActaActualizacion();
    }
    else {
      $this->pcError = 'ERROR, NIVEL DE LA UNIDAD ACADEMICA NO VALIDO, COMUNICARSE CON LA OFICINA ERP';
      return false;
    }
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxRepMostrarActaTesis($p_oSql)
  {
    $lcIdTesi = $this->paData['CIDTESI'];
    $lcCodUsu = $this->paData['CCODUSU'];
    $lcSql = "UPDATE T02MLIB SET CESTADO = 'B', cUsuCod = '{$lcCodUsu}', tModifi = NOW() WHERE CIDTESI = '{$lcIdTesi}' AND cEstado = 'A'";
    $R1 = $p_oSql->omExec($lcSql);
    if (!$R1) {
      //print_r(2);
      $this->pcError = "NO SE PUDO REGISTRAR EL ACTA DE TESIS";
      return false;
    }
    $lcSql = "SELECT A.cIdLibr, A.cFolio, A.cIdTesi, A.tIniSus, A.tFinSus, C.cNomUni, D.cDescri, F.cDescri, E.mTitulo, C.cDesTit, E.cPrefij, C.cNivel, E.cGraTit, C.cDesGra
                    FROM T02MLIB A
                    INNER JOIN S01TUAC C ON C.CUNIACA = A.CUNIACA
                    INNER JOIN S01TTAB D ON D.CCODIGO = A.CRESULT
                    INNER JOIN T01MTES E ON E.CIDTESI = A.CIDTESI
                    INNER JOIN S01DLAV F ON F.CPREFIJ = E.CPREFIJ AND F.CUNIACA = A.CUNIACA
                    WHERE D.CCODTAB = '250' AND A.CIDTESI = '$lcIdTesi'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->paDatos = ['CIDLIBR' => $laFila[0], 'CFOLIO' => $laFila[1], 'CIDTESI' => $laFila[2], 'TINISUS' => $laFila[3],
      'TFINSUS' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CRESULT' => $laFila[6], 'CESPCIA' => $laFila[7],
      'MTITULO' => $laFila[8], 'CDESTIT' => $laFila[9], 'CPREFIJ' => $laFila[10], 'CNIVEL' => $laFila[11],
      'CGRATIT' => $laFila[12], 'CDESGRA' => $laFila[13]];
    $lcSql = "SELECT C.cNombre, C.cCodAlu, C.cNroDni
                      FROM T01MTES   A
                      INNER JOIN T01DALU   B ON B.cIdTesi = A.cIdTesi
                      INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                      WHERE A.cIdTesi = '$lcIdTesi'";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $this->laData[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1], 'CNRODNI' => $laFila[2]];
    }
    $lcSql = "SELECT  C.cNombre, C.cCodDoc, D.cDescri, F.tFirma
                      FROM T01MTES   A
                      INNER JOIN T01DDOC   B ON B.cIdTesi = A.cIdTesi
                      INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                      INNER JOIN S01TTAB   D ON D.cCodigo = B.cCargo
                      INNER JOIN T02MLIB   E ON E.cIdTesi = A.cIdTesi
                      INNER JOIN T02DLIB   F ON F.cIdLibr = E.cIdLibr AND F.cUsuCod = B.cCodDoc
                      WHERE D.cCodTab = '140' AND A.cIdTesi = '$lcIdTesi' 
                      ORDER BY CASE B.ccargo WHEN 'P' THEN 1 WHEN 'V' THEN 2 when 'S' THEN 3 END";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $this->laDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CDESCRI' => $laFila[2], 'CCODUSU' => $laFila[1], 'TFIRMA' => $laFila[3]];
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisPreGrado()
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $ldIniSus = $this->paDatos['TINISUS'];
      $ldFinSus = $this->paDatos['TFINSUS'];
      $lcUniaca = $this->paDatos['CUNIACA'];
      $lcAluCod = substr($this->laData[0]['CCODALU'], 0, 4);
      $pdf = new FPDF();
      $pdf->SetAutoPageBreak(true, 5);
      $pdf->AddPage('P', 'A4');
      $pdf->SetFont('times', 'B', 8);
      $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
      $pdf->SetFont('times', 'B', 15);
      $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
      $pdf->Cell(0, 2, ' ', 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CNOMUNI']), 0, 2, 'C');
      $pdf->Cell(0, 4, ' ', 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CDESCRI']), 0, 2, 'C');
      $pdf->Cell(0, 4, ' ', 0, 2, 'C');
      $pdf->SetFont('times', 'B', 13);
      $lcActa = "ACTA DE SUSTENTACION PARA OPTAR EL ";
      if ($this->paDatos['CCODIGO'] == 'B0') {
        $lnActa = $lcActa . 'GRADO ACADÉMICO DE ';
      }
      else {
        $lnActa = $lcActa . 'TITULO PROFESIONAL DE ';
      }
      $pdf->Cell(0, 4, utf8_decode($lnActa), 0, 2, 'C');
      $pdf->Cell(0, 1, ' ', 0, 2, 'C');
      $this->paDatos['CGRATIT'] = str_replace($lnActa, '', $this->paDatos['CGRATIT']);
      if ($this->paDatos['CCODIGO'] == 'B0') {
        $pdf->Multicell(0, 4, utf8_decode(strtoupper($this->paDatos['CDESGRA'])), 0, 'C');
      }
      else {
        $pdf->Multicell(0, 4, utf8_decode(strtoupper($this->paDatos['CGRATIT'])), 0, 'C');
      }
      if ($this->paDatos['CPREFIJ'] != 'A' and $this->paDatos['CPREFIJ'] != '*' and !in_array($this->paDatos['CUNIACA'], ['4A', '4E', '73','78'])) { // QUITAR CUNIACA 78 
        if (in_array($this->paDatos['CUNIACA'], ['51']) and $lcAluCod <= '2000') {
          $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C');
        }
        elseif (in_array($this->paDatos['CUNIACA'], ['51']) and $lcAluCod > '2000') {
          $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C');
        }
        elseif (in_array($this->paDatos['CUNIACA'], ['40', '48'])) {
          $pdf->Multicell(0, 4, utf8_decode('ESPECIALIDAD: ' . $this->paDatos['CESPCIA']), 0, 'C');
        }
        else {
          $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $this->paDatos['CESPCIA']), 0, 'C');
        }
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ' ' . substr($ldIniSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria se reunió el jurado:'), 0, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $i = 0;
      $pdf->SetFont('times', 'B', 10);
      foreach ($this->laDatos as $laTmp) {
        $lnEspacio = 11;
        if ($laTmp['CDESCRI'] == 'VOCAL') {
          $lnEspacio = 17;
        }
        elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
          $lnEspacio = 10;
        }
        $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
        $lcFile = 'FILES/R' . rand() . '.png';
        $this->laDatos[$i]['CFILE'] = $lcFile;
        $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
        QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
        $i += 1;
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      if ($this->paDatos['CCODIGO'] == 'B0') {
        $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a)(ita) Egresado (a)(os)(as) en ' . $this->paDatos['CNOMUNI'] . ':'), 0, 2, 'L');
      }
      else {
        $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a) (ita) Bachiller en ' . $this->paDatos['CNOMUNI'] . ':'), 0, 2, 'L');
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      foreach ($this->laData as $laTmp) {
        $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      if ($this->paDatos['CUNIACA'] == '42' or $this->paDatos['CUNIACA'] == '65') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis intitulada:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'B0') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo de investigación titulado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T0') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T1') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis titulada:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T2') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T3') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la balota sorteada:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T4') {
        $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el:'), 0, 'J');
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($this->paDatos['MTITULO']), 0, 'C');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $lcConten = "Con el que desea optar el ";
      if ($this->paDatos['CCODIGO'] == 'B0') {
        $lnConten = $lcConten . 'Grado Académico de: ';
      }
      else {
        $lnConten = $lcConten . 'Título Profesional de:';
      }
      $pdf->Cell(0, 4, utf8_decode($lnConten), 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      if ($this->paDatos['CCODIGO'] == 'B0') {
        $pdf->Cell(0, 4, utf8_decode(strtoupper($this->paDatos['CDESGRA'])), 0, 2, 'C');
      }
      else {
        $pdf->Cell(0, 4, utf8_decode(strtoupper($this->paDatos['CGRATIT'])), 0, 2, 'C');
      }
      if ($this->paDatos['CPREFIJ'] != 'A' and $this->paDatos['CPREFIJ'] != '*' and !in_array($this->paDatos['CUNIACA'], ['4A', '4E', '73','78'])) {//qUITAR CUNIACA 78 
        if (in_array($this->paDatos['CUNIACA'], ['51']) and $lcAluCod <= '2000') {
          $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C');
        }
        elseif (in_array($this->paDatos['CUNIACA'], ['51']) and $lcAluCod > '2000') {
          $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C');
        }
        elseif (in_array($this->paDatos['CUNIACA'], ['40', '48'])) {
          $pdf->Multicell(0, 4, utf8_decode('ESPECIALIDAD: ' . $this->paDatos['CESPCIA']), 0, 'C');
        }
        else {
          $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $this->paDatos['CESPCIA']), 0, 'C');
        }
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      if ($this->paDatos['CCODIGO'] == 'B0') {
        $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo de investigación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T0') {
        $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T1') {
        $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación de la tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T2') {
        $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T3') {
        $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
      }
      elseif ($this->paDatos['CCODIGO'] == 'T4') {
        $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', $lnTamLet);
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
      $pdf->SetFont('times', '', $lnTamLet);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($ldFinSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      foreach ($this->laDatos as $laTmp) {
        $lnTpGety = $pdf->GetY();
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
        $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
        $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
        $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      print_r($this->pcError);
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisSegundaEspecialidad($p_oSql)
  {
    $paDatAca = array('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9', 'M7');
    if (in_array($this->paDatos['CUNIACA'], $paDatAca)) {
      try {
        $loDate = new CDate;
        $lnPag = 0;
        $fecha_actual = date("Y-m-d");
        $ldIniSus = $this->paDatos['TINISUS'];
        $ldFinSus = $this->paDatos['TFINSUS'];
        $pdf = new FPDF();
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        $pdf->Cell(0, 4, utf8_decode('ACTA PARA OPTAR'), 0, 2, 'C');
        $pdf->Cell(0, 1, ' ', 0, 2, 'C');
        $this->paDatos['CGRATIT'] = str_replace('TITULO DE SEGUNDA ESPECIALIDAD EN ', '', $this->paDatos['CGRATIT']);
        $pdf->Multicell(0, 4, utf8_decode('EL TITULO DE SEGUNDA ESPECIALIDAD EN ' . strtoupper($this->paDatos['CGRATIT'])), 0, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ' ' . substr($ldIniSus, 11, 5) . ', en el salón de grados virtual de la Universidad Católica de Santa María se reunió la Comisión de Evaluación conformada por:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laDatos as $laTmp) {
          $lnEspacio = 28;
          if ($laTmp['CDESCRI'] == 'DECANO') {
            $lnEspacio = 59;
          }
          elseif ($laTmp['CDESCRI'] == 'DICTAMINADOR') {
            $lnEspacio = 49;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $this->laDatos[$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Se procedio a la a la Evaluación del Expediente del señor (a) (ita) :'), 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laData as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Quien acogiéndose a lo establecido en el Decreto Supremo N°007-2017-SA, que aprueba el Reglamento de la Ley N°30453, Ley del Sistema Nacional de Residentado Médico (SINAREME), desea obtener el TITULO PROFESIONAL DE SEGUNDA ESPECIALIDAD en: '), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, utf8_decode(strtoupper($this->paDatos['CGRATIT'])), 0, 2, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($this->paDatos['CCODIGO'] == 'S1') {
          $pdf->Cell(0, 4, utf8_decode('Presentando el Trabajo Académico Titulado:'), 0, 2, 'L');
        }
        elseif ($this->paDatos['CCODIGO'] == 'S2') {
          $pdf->Cell(0, 4, utf8_decode('Presentando el Proyecto de investigación Titulado:'), 0, 2, 'L');
        }
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($this->paDatos['MTITULO']), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Habiendo cumplido con las exigencias normativas de la Facultad y, del Reglamento del SINAREME se da por:'), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', $lnTamLet);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Concluido el acto, firmaron:'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($this->laDatos as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Output('F', $this->pcFile, true);
      }
      catch (Exception $e) {
        $this->pcError = 'ERROR AL GENERAR PDF';
        print_r($this->pcError);
        return false;
      }
    }
    else {
      try {
        $loDate = new CDate;
        $lnPag = 0;
        $fecha_actual = date("Y-m-d");
        $ldIniSus = $this->paDatos['TINISUS'];
        $ldFinSus = $this->paDatos['TFINSUS'];
        $pdf = new FPDF();
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CDESCRI']), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        $pdf->Cell(0, 4, utf8_decode('ACTA PARA OPTAR'), 0, 2, 'C');
        $pdf->Cell(0, 1, ' ', 0, 2, 'C');
        $this->paDatos['CGRATIT'] = str_replace('TITULO ', '', $this->paDatos['CGRATIT']);
        $pdf->Multicell(0, 4, utf8_decode('EL TITULO DE SEGUNDA ESPECIALIDAD EN  ' . strtoupper($this->paDatos['CGRATIT'])), 0, 'C');
        /*$pdf->MultiCell(192, 10, utf8_decode('EL TITULO DE '.strtoupper($this->paDatos['CNOMUNI'])), 0, 'C');
         if ($this->paDatos['CPREFIJ'] != 'A' AND $this->paDatos['CPREFIJ'] != '*') {
         if (in_array($this->paDatos['CUNIACA'], ['F6'])){
         $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C'); 
         }
         }*/
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ' ' . substr($ldIniSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria se reunió el jurado:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laDatos as $laTmp) {
          $lnEspacio = 11;
          if ($laTmp['CDESCRI'] == 'VOCAL') {
            $lnEspacio = 17;
          }
          elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
            $lnEspacio = 10;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $this->laDatos[$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a) (ita) :'), 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laData as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($this->paDatos['CCODIGO'] == 'S0') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis titulada:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'S1') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo académico titulado:'), 0, 'J');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($this->paDatos['MTITULO']), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Con el que desea optar el Titulo de SEGUNDA ESPECIALIDAD en:'), 0, 2, 'L');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell(0, 4, utf8_decode(strtoupper($this->paDatos['CGRATIT'])), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($this->paDatos['CCODIGO'] == 'S0') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación de la Tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'S1') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', $lnTamLet);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($ldFinSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($this->laDatos as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Output('F', $this->pcFile, true);
      }
      catch (Exception $e) {
        $this->pcError = 'ERROR AL GENERAR PDF';
        print_r($this->pcError);
        return false;
      }
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisPostGradoMaestro($p_oSql)
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $ldIniSus = $this->paDatos['TINISUS'];
      $ldFinSus = $this->paDatos['TFINSUS'];
      $pdf = new FPDF();
      $pdf->SetAutoPageBreak(true, 5);
      $pdf->AddPage('P', 'A4');
      $pdf->SetFont('times', 'B', 8);
      $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
      $pdf->SetFont('times', 'B', 15);
      $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
      $pdf->Cell(0, 2, ' ', 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode('ESCUELA DE POSTGRADO'), 0, 2, 'C');
      $pdf->Cell(0, 4, ' ', 0, 2, 'C');
      $pdf->SetFont('times', 'B', 13);
      $pdf->Cell(0, 4, utf8_decode('ACTAS DE SUSTENTACION DE TESIS PARA OPTAR'), 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode('EL GRADO ACADEMICO DE MAESTRO'), 0, 2, 'C');
      $this->paDatos['CGRATIT'] = str_replace('TITULO ', '', $this->paDatos['CGRATIT']);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('En fecha ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ' a las ' . substr($ldIniSus, 11, 5) . ' horas, en el salón de grados virtual de la Universidad Católica de Santa María se reunió el jurado:'), 0, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $i = 0;
      $pdf->SetFont('times', 'B', 10);
      foreach ($this->laDatos as $laTmp) {
        $lnEspacio = 11;
        if ($laTmp['CDESCRI'] == 'VOCAL') {
          $lnEspacio = 17;
        }
        elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
          $lnEspacio = 10;
        }
        $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
        $lcFile = 'FILES/R' . rand() . '.png';
        $this->laDatos[$i]['CFILE'] = $lcFile;
        $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
        QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
        $i += 1;
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Para recibir las previas orales del(os) graduando(s):'), 0, 2, 'L');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      foreach ($this->laData as $laTmp) {
        $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('Quien(es) de acuerdo al Reglamento General de Grados y Títulos de la Universidad Católica de Santa María y el Reglamento de Grados Académicos de la Escuela de Postgrado, presentó(aron) la tesis titulada:'), 0, 'J');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      $pdf->Multicell(0, 4, utf8_decode($this->paDatos['MTITULO']), 0, 'C');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Con la cual desea(n) optar el grado académico de:'), 0, 2, 'L');
      $pdf->Ln(5);
      $pdf->SetFont('times', 'B', 10);
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CGRATIT']), 0, 2, 'C');
      if ($this->paDatos['CPREFIJ'] != '*') {
        $pdf->Cell(0, 4, utf8_decode('CON MENCIÓN EN ' . $this->paDatos['CESPCIA']), 0, 2, 'C');
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('Luego de la exposición del graduando y la absolución de las preguntas y observaciones realizadas, el jurado procedió a la deliberación y votación de acuerdo al reglamento; llegando al siguiente resultado:'), 0, 'J');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', $lnTamLet);
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
      $pdf->SetFont('times', '', $lnTamLet);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($ldFinSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      foreach ($this->laDatos as $laTmp) {
        $lnTpGety = $pdf->GetY();
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . ' - ' . $laTmp['CDESCRI']), 0, 'J');
        $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
        $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      print_r($this->pcError);
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisPostGradoDoctor($p_oSql)
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $ldIniSus = $this->paDatos['TINISUS'];
      $ldFinSus = $this->paDatos['TFINSUS'];
      $pdf = new FPDF();
      $pdf->SetAutoPageBreak(true, 5);
      $pdf->AddPage('P', 'A4');
      $pdf->SetFont('times', 'B', 8);
      $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
      $pdf->SetFont('times', 'B', 15);
      $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
      $pdf->Cell(0, 2, ' ', 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode('ESCUELA DE POSTGRADO'), 0, 2, 'C');
      $pdf->Cell(0, 4, ' ', 0, 2, 'C');
      $pdf->SetFont('times', 'B', 13);
      $pdf->Cell(0, 4, utf8_decode('ACTAS DE SUSTENTACION DE TESIS PARA OPTAR EL'), 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode('GRADO ACADEMICO DE DOCTOR'), 0, 2, 'C');
      //$pdf->Cell(0, 1, ' ' , 0, 2, 'C'); 
      $this->paDatos['CGRATIT'] = str_replace('TITULO ', '', $this->paDatos['CGRATIT']);
      //$pdf->Cell(0, 4, utf8_decode('EL TITULO '.strtoupper($this->paDatos['CGRATIT'])), 0, 2, 'C'); 
      if ($this->paDatos['CPREFIJ'] != '*') {
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 2, 'C');
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('En fecha ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ' a las ' . substr($ldIniSus, 11, 5) . ' horas, en el salón de grados virtual de la Universidad Católica de Santa María se reunió el jurado:'), 0, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $i = 0;
      $pdf->SetFont('times', 'B', 10);
      foreach ($this->laDatos as $laTmp) {
        $lnEspacio = 11;
        if ($laTmp['CDESCRI'] == 'VOCAL') {
          $lnEspacio = 17;
        }
        elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
          $lnEspacio = 10;
        }
        $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
        $lcFile = 'FILES/R' . rand() . '.png';
        $this->laDatos[$i]['CFILE'] = $lcFile;
        $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
        QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
        $i += 1;
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Para recibir las previas orales del(os) graduando(s):'), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      foreach ($this->laData as $laTmp) {
        $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
      }
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('Quien(es) de acuerdo al Reglamento General de Grados y Títulos de la Universidad Católica de Santa María y el Reglamento de Grados Académicos de la Escuela de Postgrado, presentó(aron) la tesis titulada:'), 0, 'J');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      $pdf->Multicell(0, 4, utf8_decode($this->paDatos['MTITULO']), 0, 'C');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Con la cual desea(n) optar el grado académico de:'), 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CGRATIT']), 0, 2, 'C');
      $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Multicell(0, 4, utf8_decode('Luego de la exposición del graduando y la absolución de las preguntas y observaciones realizadas, el jurado procedió a la deliberación y votación de acuerdo al reglamento; llegando al siguiente resultado:'), 0, 'J');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->SetFont('times', 'B', 10);
      $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
      $pdf->SetFont('times', '', 10);
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($ldFinSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      foreach ($this->laDatos as $laTmp) {
        $lnTpGety = $pdf->GetY();
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . ' - ' . $laTmp['CDESCRI']), 0, 'J');
        $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
        $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 18, 18, 'PNG');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaActualizacion($p_oSql)
  {
    if (in_array($this->paDatos['CCODIGO'], ['B0', 'T0', 'T1'])) {
      try {
        $loDate = new CDate;
        $lnPag = 0;
        $fecha_actual = date("Y-m-d");
        $ldIniSus = $this->paDatos['TINISUS'];
        $ldFinSus = $this->paDatos['TFINSUS'];
        $lcUniaca = $this->paDatos['CUNIACA'];
        $pdf = new FPDF();
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CNOMUNI']), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CDESCRI']), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        $pdf->Cell(0, 4, utf8_decode('ACTA DE SUSTENTACION PARA OPTAR EL'), 0, 2, 'C');
        $pdf->Cell(0, 1, ' ', 0, 2, 'C');
        $this->paDatos['CGRATIT'] = str_replace('TITULO PROFESIONAL DE ', '', $this->paDatos['CGRATIT']);
        if ($this->paDatos['CCODIGO'] == 'B0') {
          $pdf->Multicell(0, 4, utf8_decode('GRADO ACADÉMICO DE ' . strtoupper($this->paDatos['CDESGRA'])), 0, 'C');
        }
        else {
          $pdf->Multicell(0, 4, utf8_decode('TITULO PROFESIONAL DE ' . strtoupper($this->paDatos['CGRATIT'])), 0, 'C');
        }
        if ($this->paDatos['CPREFIJ'] != 'A' and $this->paDatos['CPREFIJ'] != '*'
        and !in_array($this->paDatos['CUNIACA'], ['4A', '4E', '73'])) {
          if (in_array($this->paDatos['CUNIACA'], ['51'])) {
            $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C');
          }
          else {
            $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $this->paDatos['CESPCIA']), 0, 'C');
          }
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ' ' . substr($ldIniSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria se reunió el jurado:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laDatos as $laTmp) {
          $lnEspacio = 11;
          if ($laTmp['CDESCRI'] == 'VOCAL') {
            $lnEspacio = 17;
          }
          elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
            $lnEspacio = 10;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $this->laDatos[$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($this->paDatos['CCODIGO'] == 'B0') {
          $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a)(ita) Egresado (a)(os)(as) en ' . $this->paDatos['CNOMUNI'] . ':'), 0, 2, 'L');
        }
        else {
          $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a) (ita) Bachiller en ' . $this->paDatos['CNOMUNI'] . ':'), 0, 2, 'L');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laData as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($this->paDatos['CUNIACA'] == '42' or $this->paDatos['CUNIACA'] == '65') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis intitulada:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'B0') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo de investigación titulado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T0') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T1') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis titulada:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T2') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T3') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la balota sorteada:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T4') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el:'), 0, 'J');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($this->paDatos['MTITULO']), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Con el que desea optar el:'), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        if ($this->paDatos['CCODIGO'] == 'B0') {
          $pdf->Cell(0, 4, utf8_decode('GRADO ACADÉMICO DE ' . strtoupper($this->paDatos['CDESGRA'])), 0, 2, 'C');
        }
        else {
          $pdf->Cell(0, 4, utf8_decode('TITULO PROFESIONAL DE ' . strtoupper($this->paDatos['CGRATIT'])), 0, 2, 'C');
        }
        $pdf->SetFont('times', 'B', 10);
        if ($this->paDatos['CPREFIJ'] != 'A' and $this->paDatos['CPREFIJ'] != '*' and !in_array($this->paDatos['CUNIACA'], ['4A', '4E', '73'])) {
          if (in_array($this->paDatos['CUNIACA'], ['51'])) {
            $pdf->Multicell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 'C');
          }
          else {
            $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $this->paDatos['CESPCIA']), 0, 'C');
          }
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($this->paDatos['CCODIGO'] == 'B0') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo de investigación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T0') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T1') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación de la tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T2') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T3') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($this->paDatos['CCODIGO'] == 'T4') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', $lnTamLet);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($ldFinSus, 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($this->laDatos as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Output('F', $this->pcFile, true);
      }
      catch (Exception $e) {
        $this->pcError = 'ERROR AL GENERAR PDF';
        print_r($this->pcError);
        return false;
      }
    }
    else {
      try {
        $loDate = new CDate;
        $lnPag = 0;
        $fecha_actual = date("Y-m-d");
        $ldIniSus = $this->paDatos['TINISUS'];
        $ldFinSus = $this->paDatos['TFINSUS'];
        $pdf = new FPDF();
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $this->paDatos['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('ACTA DE PROCESO DE ACTUALIZACION'), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        //$pdf->Cell(0, 4, utf8_decode('GRADO ACADEMICO DE DOCTOR'), 0, 2, 'C');
        //$pdf->Cell(0, 1, ' ' , 0, 2, 'C'); 
        //$pdf->Cell(0, 4, utf8_decode('EL TITULO '.strtoupper($this->paDatos['CGRATIT'])), 0, 2, 'C'); 
        /*if ($this->paDatos['CPREFIJ'] != '*') {
         $pdf->Cell(0, 4, utf8_decode($this->paDatos['CESPCIA']), 0, 2, 'C'); 
         }  */
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En el local de la Universidad Católica de Santa Maria, siendo las ' . substr($ldIniSus, 11, 5) . ' horas del dia ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ', se reunio el Jurado integrado por los señores docentes:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laDatos as $laTmp) {
          $lnEspacio = 11;
          if ($laTmp['CDESCRI'] == 'VOCAL') {
            $lnEspacio = 17;
          }
          elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
            $lnEspacio = 10;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $this->laDatos[$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Para evaluar el expediente presentado por el (la) Señor (Srta) Bachiller:'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($this->laData as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Quien desa optar el Titulo Profesional de ' . $this->paDatos['CGRATIT'] . ' en concordancia con la Resolucion N° 24212-R-2017 que aprueba el Ciclo de Proceso de Actualización en Contenidos de la Profesión, tendente al logro del Titulo Profesional de:'), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CGRATIT']), 0, 2, 'C');
        if ($this->paDatos['CPREFIJ'] != '*') {
          if (in_array($this->paDatos['CUNIACA'], ['3K'])) {
            $pdf->Cell(0, 4, utf8_decode('ESPECIALIDAD: ' . $this->paDatos['CESPCIA']), 0, 2, 'C');
          }
          else {
            $pdf->Cell(0, 4, utf8_decode('MENCION EN ' . $this->paDatos['CESPCIA']), 0, 2, 'C');
          }
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('POR LO TANTO'), 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Encontrándose conforme el expediente el jurado acordó darle el resultado de:'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, utf8_decode($this->paDatos['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($ldFinSus, 11, 5) . ', se dio por concluido el acto, y firmaron.'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($this->laDatos as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', 10);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . ' - ' . $laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 18, 18, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Output('F', $this->pcFile, true);
      }
      catch (Exception $e) {
        $this->pcError = 'ERROR AL GENERAR PDF';
        print_r($this->pcError);
        return false;
      }
    }
    return true;
  }

  //---------------------------------------------------------------------------------------------------
  // BUSCA LOS DATOS DEL ALUMNO
  // 2020-06-11 FLC
  //---------------------------------------------------------------------------------------------------

  public function omBuscarActaEscuela()
  {
    $llOk = $this->mxValParamBuscarActaEscuela();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxBuscarActaEscuela($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
      return false;
    }
    return $llOk;
  }

  protected function mxValParamBuscarActaEscuela()
  {
    $this->paData['CNOMBRE'] = str_replace(' ', '%', $this->paData['CNOMBRE']);
    $this->paData['CNOMBRE'] = strtoupper($this->paData['CNOMBRE']);
    if (!isset($this->paData['CNOMBRE']) || strlen($this->paData['CNOMBRE']) < 7) {
      $this->pcError = 'DEBE INGRESAR 8 CARACTERES COMO MINIMO';
      return false;
    }
    elseif (is_numeric($this->paData['CNOMBRE']) && strlen($this->paData['CNOMBRE']) != 8 && strlen($this->paData['CNOMBRE']) != 10) {
      $this->pcError = 'DEBE INGRESAR 8 DIGITOS PARA EL DNI O 10 PARA EL CODIGO';
      return false;
    }
    return true;
  }

  protected function mxBuscarActaEscuela($p_oSql)
  {
    if (is_numeric($this->paData['CNOMBRE']) && strlen($this->paData['CNOMBRE']) == 10) {
      $lcSql = "C.cCodAlu = '{$this->paData['CNOMBRE']}'";
    }
    elseif (is_numeric($this->paData['CNOMBRE']) && strlen($this->paData['CNOMBRE']) == 8) {
      $lcSql = "D.cNroDni = '{$this->paData['CNOMBRE']}'";
    }
    else {
      $lcSql = "D.cNombre LIKE '%{$this->paData['CNOMBRE']}%'";
    }
    $lcSql = "SELECT A.cIdTesi, C.cCodAlu, D.cNombre, E.cNomUni
                    FROM T01MTES A 
                    INNER JOIN T02MLIB   B ON B.cIdTesi = A.cIdTesi 
                    INNER JOIN T01DALU   C ON C.cIdTesi = A.cIdTesi 
                    INNER JOIN V_A01MALU D ON D.cCodAlu = C.cCodAlu
                    INNER JOIN S01TUAC   E ON E.cUniAca = A.cUniAca
                    WHERE A.cEstPro = 'K' AND " . $lcSql;
    $RS = $p_oSql->omExec($lcSql);
    $i = 0;
    while ($laFila = $p_oSql->fetch($RS)) {
      $this->laDatos[] = ['CIDTESI' => $laFila[0], 'CCODALU' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]),
        'CNOMUNI' => $laFila[3]];
      $i += 1;
    }
    if ($i == 0) {
      $this->pcError = "EL ACTA NO A SIDO GENERADA";
      return false;
    }
    $this->paData = $this->laDatos;
    return true;
  }

  //---------------------------------------------------------------------------------------------------
  // TRAE LOS DATOS DEL ACTA SELECCIONADA
  // 2020-06-11 FLC
  //---------------------------------------------------------------------------------------------------

  public function omSeleccionarActa()
  {
    $llOk = $this->mxValParamSeleccionarActa();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxSeleccionarActa($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
      return false;
    }
    return $llOk;
  }

  protected function mxValParamSeleccionarActa()
  {
    if (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6) {
      $this->pcError = 'NO SELECCIONO UNA ACTA VALIDA';
      return false;
    }
    return true;
  }

  protected function mxSeleccionarActa($p_oSql)
  {
    //TRALE LA CABECERA
    $lcSql = "SELECT B.cFolio, A.cIdTesi, A.mTitulo, G.cNivel, G.cNomUni, D.cDescri, E.cPrefij, E.cDescri, B.tIniSus, B.tFinSus, h.cdescri
                    FROM T01MTES A 
                    INNER JOIN T02MLIB B ON B.cIdTesi = A.cIdTesi
                    INNER JOIN S01TUAC C ON C.cUniAca = A.cUniAca
                    INNER JOIN S01TTAB D ON D.cCodigo = B.cResult
                    INNER JOIN S01DLAV E ON E.cUniAca = A.cUniAca AND E.cPrefij = B.cPrefij
                    INNER JOIN T01DALU K ON K.cIdTesi = A.cIdTesi 
                    INNER JOIN V_A01MALU F ON F.cCodALu = K.cCodAlu 
                    INNER JOIN S01TUAC G ON G.cUniAca = A.cUniAca 
                    LEFT JOIN t01dtur j ON j.cidtesi = a.cidtesi 
                    LEFT JOIN v_s01ttab h ON h.ccodtab = '143'::bpchar AND h.ccodigo = a.ctipo 
                    LEFT JOIN v_s01ttab i ON i.ccodtab = '142'::bpchar AND i.ccodigo = a.cestpro
                    WHERE D.cCodTab = '250' AND A.cEstPro = 'K' AND A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $RS = $p_oSql->omExec($lcSql);
    $i = 0;
    $laFila = $p_oSql->fetch($RS);
    // DATOS DEL ALUMNO
    $this->paDatos = ['CFOLIO' => $laFila[0], 'CIDTESI' => $laFila[1], 'MTITULO' => $laFila[2], 'CNIVEL' => $laFila[3],
      'CNOMUNI' => $laFila[4], 'CRESULT' => $laFila[5], 'CPREFIJ' => $laFila[6], 'CESPECI' => $laFila[7],
      'TINISUS' => $laFila[8], 'TFINSUS' => $laFila[9], 'CDESTIP' => $laFila[10]];
    $lcSql = "SELECT C.cNombre, C.cCodAlu, C.cNroDni
                      FROM T01MTES   A
                      INNER JOIN T01DALU   B ON B.cIdTesi = A.cIdTesi
                      INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                      WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $this->laData[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1],
        'CNRODNI' => $laFila[2]];
    }
    //DATOS DE LOS JURADOS
    $lcSql = "SELECT  C.cNombre, C.cCodDoc, D.cDescri, F.tFirma
                      FROM T01MTES   A
                      INNER JOIN T01DDOC   B ON B.cIdTesi = A.cIdTesi
                      INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                      INNER JOIN S01TTAB   D ON D.cCodigo = B.cCargo
                      INNER JOIN T02MLIB   E ON E.cIdTesi = A.cIdTesi
                      INNER JOIN T02DLIB   F ON F.cIdLibr = E.cIdLibr AND F.cUsuCod = B.cCodDoc
                      WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND D.cCodTab = '140' AND F.cTipo = B.cCargo
                      ORDER BY CASE B.ccargo WHEN 'P' THEN 1 WHEN 'V' THEN 2 when 'S' THEN 3 END";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $this->laDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CDESCRI' => $laFila[2],
        'CCODDOC' => $laFila[1], 'TFIRMA' => $laFila[3]];
    }
    $this->paAlumnos = $this->laData;
    $this->paJurados = $this->laDatos;
    return true;
  }

  // ------------------------------------------------------------------------------
  //  Cambia el estado a Procesado y genera el acta de la tesis
  //  2020-05-11 FLC
  // ------------------------------------------------------------------------------
  public function omGenerarActaEscuela()
  {
    $llOk = $this->mxValUsuario();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxRepGenerarActaEscuela($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    if ($this->paDatos['CNIVEL'] == '01') {
      $llOk = $this->mxPrintMostrarActaTesisPreGrado();
    }
    elseif ($this->paDatos['CNIVEL'] == '02') {
      $llOk = $this->mxPrintMostrarActaTesisSegundaEspecialidad();
    }
    elseif ($this->paDatos['CNIVEL'] == '03') {
      $llOk = $this->mxPrintMostrarActaTesisPostGradoMaestro();
    }
    elseif ($this->paDatos['CNIVEL'] == '04') {
      $llOk = $this->mxPrintMostrarActaTesisPostGradoDoctor();
    }
    elseif (in_array($this->paDatos['CNIVEL'], ['06', '09'])) {
      $llOk = $this->mxPrintMostrarActaActualizacion();
    }
    else {
      $this->pcError = 'ERROR, NIVEL DE LA UNIDAD ACADEMICA NO VALIDO, COMUNICARSE CON LA OFICINA ERP';
      return false;
    }
    $loSql->omDisconnect();
    //$lcCodiQR = QRcode::png("asd");
    return $llOk;
  }

  protected function mxRepGenerarActaEscuela($p_oSql)
  {
    $lcIdTesi = $this->paData['CIDTESI'];
    $lcCodUsu = $this->paData['CCODUSU'];
    $lcSql = "SELECT A.cIdLibr, A.cFolio, A.cIdTesi, A.tIniSus, A.tFinSus, C.cNomUni, D.cDescri, F.cDescri, E.mTitulo, C.cDesTit, E.cPrefij, C.cNivel, E.cGraTit, A.cUniAca, TRIM(G.cCodigo), G.cDescri, C.cDesGra
                    FROM T02MLIB A
                    INNER JOIN S01TUAC C ON C.CUNIACA = A.CUNIACA
                    INNER JOIN S01TTAB D ON D.CCODIGO = A.CRESULT
                    INNER JOIN T01MTES E ON E.CIDTESI = A.CIDTESI
                    INNER JOIN S01DLAV F ON F.CPREFIJ = E.CPREFIJ AND F.CUNIACA = A.CUNIACA
                    LEFT OUTER JOIN S01TTAB G ON G.CCODIGO = E.CTIPO AND G.CCODTAB = '143'
                    WHERE D.CCODTAB = '250' AND A.CIDTESI = '$lcIdTesi'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->paDatos = ['CIDLIBR' => $laFila[0], 'CFOLIO' => $laFila[1], 'CIDTESI' => $laFila[2], 'TINISUS' => $laFila[3],
      'TFINSUS' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CRESULT' => $laFila[6], 'CESPCIA' => $laFila[7],
      'MTITULO' => $laFila[8], 'CDESTIT' => $laFila[9], 'CPREFIJ' => $laFila[10], 'CNIVEL' => $laFila[11],
      'CGRATIT' => $laFila[12], 'CUNIACA' => $laFila[13], 'CCODIGO' => $laFila[14], 'CDESCRI' => $laFila[15],
      'CDESGRA' => $laFila[16]];
    $lcSql = "SELECT C.cNombre, C.cCodAlu, C.cNroDni
                      FROM T01MTES   A
                      INNER JOIN T01DALU   B ON B.cIdTesi = A.cIdTesi
                      INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                      WHERE A.cIdTesi = '$lcIdTesi'";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $this->laData[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CCODALU' => $laFila[1], 'CNRODNI' => $laFila[2]];
    }
    $lcSql = "SELECT C.cNombre, C.cCodDoc, D.cDescri, F.tFirma
                      FROM T01MTES   A
                      INNER JOIN T01DDOC   B ON B.cIdTesi = A.cIdTesi
                      INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                      INNER JOIN S01TTAB   D ON D.cCodigo = B.cCargo
                      INNER JOIN T02MLIB   E ON E.cIdTesi = A.cIdTesi
                      INNER JOIN T02DLIB   F ON F.cIdLibr = E.cIdLibr AND F.cUsuCod = B.cCodDoc
                      WHERE D.cCodTab = '140' AND A.cIdTesi = '$lcIdTesi' AND F.cTipo = B.cCargo
                      ORDER BY CASE B.ccargo WHEN 'P' THEN 1 WHEN 'V' THEN 2 when 'S' THEN 3 END";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $this->laDatos[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'CDESCRI' => $laFila[2], 'CCODUSU' => $laFila[1], 'TFIRMA' => $laFila[3]];
    }
    return true;
  }

  //---------------------------------------------------------------------
  // Generacion de Reporte Compromiso de Asesor y Dictamen del Borrador
  // 2020-07-09 FLC
  //---------------------------------------------------------------------
  public function omReporteCompromisoAsesorDictamenBorrador()
  {
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxDatosReporte($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $loPdf = new PDF('portrait', 'cm', 'A4');
    $this->mxReporteCompromisoAsesorDictamenBorrador($loPdf);
    if (in_array($this->paData['CNIVEL'], ['03', '04'])) {
      $this->mxReporteDictamenBorradorTesisPostGrado($loPdf);
    }
    else {
      $this->mxReporteDictamenBorradorTesis($loPdf);
    }
    if ($this->paData['CUNIACA'] == '70') {
      $this->paData['FILE'] = '';
    }
    else {
      $this->paData['FILE'] = 'http://apps.ucsm.edu.pe/UCSMERP/' . $this->pcFile;
    }
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxDatosReporte($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cUniAca, A.cEstTes, C.cDescri FROM T01MTES A
                  INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca
                  INNER JOIN V_S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CTIPO' => $laFila[4], 'DFECHA' => '', 'DFECHOR' => ''];
    //
    $lcSql = "SELECT cSigla, cNomUni, cNivel FROM S01TUAC WHERE cUniAca = '{$this->laData['CUNIACA']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData += ['CSIGLA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CNIVEL' => $laFila[2]];
    //Datos de los alumno
    $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroDni, B.cEmail FROM T01DALU A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laAlumno = null;
    while ($laFila = $p_oSql->fetch($R1)) {
      $laAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CNRODNI' => $laFila[2], 'CEMAIL' => $laFila[3]];
    }
    //Datos de los dictaminadores del borrador de Tesis
    $laDocenteD = null;
    $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI') FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego ='C' AND A.cEstado = 'A' ORDER BY A.cCodDoc";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      if ($this->laData['DFECHA'] < $laFila[4]) {
        $this->laData['DFECHA'] = $laFila[4];
        $this->laData['DFECHOR'] = $laFila[5];
      }
      $laDocenteD[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CDESCRI' => $laFila[2], 'CEMAIL' => $laFila[3], 'TDICTAM' => $laFila[4], 'DFECHOR' => $laFila[5]];
    }
    $this->laData['CDICTAM'] = $this->paData['CIDTESI'] . '-C-' . $this->laData['CSIGLA'] . '-' . substr($this->laData['DFECHA'], 0, 4);
    //Datos del Asesor
    $laDocenteA = null;
    $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, B.cNroDni, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI')
                  FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego ='B' AND A.cEstado = 'A'";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $laDocenteA[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CDESCRI' => $laFila[2], 'CEMAIL' => $laFila[3], 'CNRODNI' => $laFila[4], 'TDICTAM' => $laFila[5],
        'DFECHOR' => $laFila[6]];
    }
    $this->paData = $this->laData;
    $this->paAlumno = $laAlumno;
    $this->paDocenteA = $laDocenteA;
    $this->paDocenteD = $laDocenteD;
    return true;
  }

  protected function mxReporteCompromisoAsesor()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 10; //Ancho de la celda
    $lnTamLet = 12; //Ancho de la celda
    //A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage();
    //A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->SetFont('times', 'B', 16);
    $loPdf->Ln($lnParraf);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Multicell($lnWidth, 5, utf8_decode('DECLARACIÓN DE COMPROMISO DE ASESORÍA DE TRABAJOS DE INVESTIGACIÓN, TRABAJOS ACADÉMICOS Y/O TESIS'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paDocente[0]['TDICTAM'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode('Mediante el presente documento doy conformidad y soy responsable de la asesoría de tesis y/o trabajo de investigación y/o trabajo académico cumpliendo las normas vigentes establecidas por la Universidad Católica de Santa María'), 0, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Título:'), 0, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Autor(es):'), 0, 'L');
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNRODNI']), 0, 'C');
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CNOMBRE']), 0, 'C');
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CEMAIL']), 0, 'C');
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Escuela Profesional, Segunda Especialidad, Maestría o Doctorado'), 0, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Datos del Asesor:'), 0, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paDocente as $laFila) {
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CNRODNI']), 0, 'J');
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CCODDOC']), 0, 'J');
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CNOMBRE']), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $loPdf->GetY();
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 4);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  protected function mxReporteDictamenAsesoria()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 8; //Ancho de la celda
    $lnTamLet = 10; //Ancho de la celda
    // A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage();
    // A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 12);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
    $loPdf->Ln($lnParraf);
    if ($this->paData['CPREFIJ'] != 'A' and $this->paData['CPREFIJ'] != '*' and !in_array($this->paData['CUNIACA'], ['4A', '4E', '73'])) {
      if (in_array($this->paData['CUNIACA'], ['51']) and $lcAluCod <= '2000') {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      elseif (in_array($this->paData['CUNIACA'], ['51']) and $lcAluCod > '2000') {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      elseif (in_array($this->paData['CUNIACA'], ['F6'])) {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      else {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN ' . $this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
    }
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CTIPO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN DE ASESORIA'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'BU', $lnTamLet);
    $loPdf->Cell($lnWidth, 4, utf8_decode('Dictamen: ' . $this->paData['CDICTAM']), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el borrador de tesis del expediente ' . substr($this->paData['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Ln(5);
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('El dictamen es: '), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
    $loPdf->Ln($lnParraf * 3);
    // Firma del Primer docente
    $loPdf->SetFont('times', 'B', 10);
    foreach ($this->paDocente as $laFila) {
      $lnTpGety = $loPdf->GetY();
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laFila['CCODDOC'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['TDICTAM'] . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 3);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  protected function mxReporteDictamenAsesoriaPostGrado()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 8; //Ancho de la celda
    $lnTamLet = 10; //Ancho de la celda
    // A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage();
    // A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 12);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CTIPO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN DE ASESORIA'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'BU', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $this->paData['CDICTAM']), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el borrador de tesis del expediente ' . substr($this->paData['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Ln(5);
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('El dictamen es: '), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
    $loPdf->Ln($lnParraf * 3);
    // Firma del Primer docente
    $loPdf->SetFont('times', 'B', 10);
    foreach ($this->paDocente as $laFila) {
      $lnTpGety = $loPdf->GetY();
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laFila['CCODDOC'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['TDICTAM'] . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 3);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  protected function mxReporteDictamenBorradorTesis()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 8; //Ancho de la celda
    $lnTamLet = 10; //Ancho de la celda
    // A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage();
    // A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 12);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
    $loPdf->Ln($lnParraf);
    if ($this->paData['CPREFIJ'] != 'A' and $this->paData['CPREFIJ'] != '*' and !in_array($this->paData['CUNIACA'], ['4A', '4E', '73'])) {
      if (in_array($this->paData['CUNIACA'], ['51']) and $lcAluCod <= '2000') {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      elseif (in_array($this->paData['CUNIACA'], ['51']) and $lcAluCod > '2000') {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      elseif (in_array($this->paData['CUNIACA'], ['F6'])) {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      else {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN ' . $this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
    }
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CTIPO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    //$loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE BORRADOR'), 0, 'C');
    if ($this->paData['CMODAL'] == 'T5'){
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE TRABAJO ACADÉMICO'), 0, 'C');
    } else{
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE BORRADOR'), 0, 'C');
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'BU', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $this->paData['CDICTAM']), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el borrador del expediente ' . substr($this->paData['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Ln(5);
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
    $loPdf->Ln($lnParraf * 3);
    // Firma del Primer docente
    $loPdf->SetFont('times', 'B', 10);
    foreach ($this->paDocente as $laFila) {
      $lnTpGety = $loPdf->GetY();
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laFila['CCODDOC'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['TDICTAM'] . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 3);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  protected function mxReporteDictamenBorradorTesisPostGrado()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 8; //Ancho de la celda
    $lnTamLet = 10; //Ancho de la celda
    // A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage();
    // A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 12);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE BORRADOR DE TESIS'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'BU', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $this->paData['CDICTAM']), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el borrador del expediente ' . substr($this->paData['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Ln(5);
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
    $loPdf->Ln($lnParraf * 3);
    // Firma del Primer docente
    $loPdf->SetFont('times', 'B', 10);
    foreach ($this->paDocente as $laFila) {
      $lnTpGety = $loPdf->GetY();
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laFila['CCODDOC'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['TDICTAM'] . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 3);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  protected function mxReporteDictamenProyectoPlan()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 8; //Ancho de la celda
    $lnTamLet = 10; //Ancho de la celda
    // A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage('P', 'A4');
    // A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 12);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
    $loPdf->Ln($lnParraf);
    if ($this->paData['CPREFIJ'] != 'A' and $this->paData['CPREFIJ'] != '*' and !in_array($this->paData['CUNIACA'], ['4A', '4E', '73'])) {
      if (in_array($this->paData['CUNIACA'], ['51']) and $lcAluCod <= '2000') {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      elseif (in_array($this->paData['CUNIACA'], ['51']) and $lcAluCod > '2000') {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      elseif (in_array($this->paData['CUNIACA'], ['F6'])) {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
      else {
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN ' . $this->paData['CESPCIA']), 0, 'C');
        $loPdf->Ln($lnParraf);
      }
    }
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CTIPO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE PROYECTO / PLAN'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'BU', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $this->paData['CDICTAM']), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el proyecto / plan del expediente ' . substr($this->paData['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Ln(5);
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
    $loPdf->Ln($lnParraf * 2);
    // Firma del Primer docente
    $loPdf->SetFont('times', 'B', 10);
    foreach ($this->paDocente as $laFila) {
      $lnTpGety = $loPdf->GetY();
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laFila['CCODDOC'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['TDICTAM'] . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 3);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  protected function mxReporteDictamenProyectoPlanPostgrado()
  {
    $loDate = new CDate;
    // A partir de este codigo configuro el documento PDF
    $lnWidth = 0; //Ancho de la celda
    $lnHeight = 0.5; //Alto de la celda
    $lnParraf = 8; //Ancho de la celda
    $lnTamLet = 10; //Ancho de la celda
    // A partir de esta linea creo el documento PDF
    $loPdf = new FPDF('P', 'mm', 'A4');
    $loPdf->SetMargins(20, 24, 20);
    $loPdf->AddPage();
    // A partir de este codigo se escribe en el documento
    $loPdf->SetFont('times', '', 10);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 14);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', 12);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE PROYECTO / PLAN DE TESIS'), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'BU', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $this->paData['CDICTAM']), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el proyecto / plan del expediente ' . substr($this->paData['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    foreach ($this->paAlumno as $laFila) {
      $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['CCODALU'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
    }
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, 5, utf8_decode($this->paData['MTITULO']), 0, 'C');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', '', $lnTamLet);
    $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
    $loPdf->Ln($lnParraf);
    $loPdf->SetFont('times', 'B', $lnTamLet);
    $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
    $loPdf->Ln($lnParraf * 3);
    // Firma del Primer docente
    $loPdf->SetFont('times', 'B', 10);
    foreach ($this->paDocente as $laFila) {
      $lnTpGety = $loPdf->GetY();
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laFila['CCODDOC'] . ' - ' . $laFila['CNOMBRE']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
      $lcFile = 'FILES/R' . rand() . '.png';
      $lcCodDc = utf8_decode($laFila['CCODDOC'] . ' ' . $laFila['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laFila['TDICTAM']) . ' ' . $laFila['TDICTAM'] . ' ' . $laFila['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
      $loPdf->Ln($lnParraf * 3);
    }
    $loPdf->Output('D', $this->pcFile, true);
    return true;
  }

  //----------------------------------------------------
  // Generacion de Reporte Dictamen de Proyecto / Plan
  // 2021-01-27 APR
  //----------------------------------------------------
  public function omReporteDictamenProyectoPlan()
  {
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxDatosIDTesiDictamenProyectoPlan($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $llOk = $this->mxDatosReporteDictamenProyectoPlan($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    if (in_array($this->paData['CNIVEL'], ['03', '04'])) {
      $this->mxReporteDictamenProyectoPlanPostgrado($loPdf);
    }
    else {
      $this->mxReporteDictamenProyectoPlan($loPdf);
    }
    $this->paData['FILE'] = $this->pcFile;
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxDatosIDTesiDictamenProyectoPlan($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, B.cEstTes FROM T01DALU A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                    WHERE A.cEstado = 'A' AND B.cEstTes IN ('C', 'D', 'E', 'F','G', 'H', 'I', 'J', 'K') AND A.cCodAlu = '{$this->paData['CCODALU']}'";
    $R1 = $p_oSql->omExec($lcSql);
    if ($R1 == false || $p_oSql->pnNumRow == 0) {
      $this->pcError = "ERROR: TODOS SUS DICTAMINADORES AUN NO HAN APROBADO EL PROYECTO/PLAN, EN ESTE MOMENTO NO SE PUEDE GENERAR EL DOCUMENTO";
      return false;
    }
    $laFila = $p_oSql->fetch($R1);
    if ($this->paData['CESTTES'] > $laFila[1]) {
      $this->pcError = 'EN ESTE MOMENTO NO SE PUEDE GENERAR EL\nDOCUMENTO ';
      return false;
    }
    $this->paData = ['CIDTESI' => $laFila[0]];
    return true;
  }

  protected function mxDatosReporteDictamenProyectoPlan($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, A.mTitulo, B.cUniAca, A.cEstTes, C.cDescri, A.cPrefij, B.cDescri FROM T01MTES A
                    INNER JOIN S01DLAV B ON B.cPrefij = A.cPrefij AND B.cUniaca = A.cUniaca
                    INNER JOIN V_S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CTIPO' => $laFila[4], 'CPREFIJ' => $laFila[5], 'CESPCIA' => $laFila[6], 'DFECHA' => '', 'DFECHOR' => ''];
    //
    $lcSql = "SELECT cSigla, cNomUni, cNivel FROM S01TUAC WHERE cUniAca = '{$this->laData['CUNIACA']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData += ['CSIGLA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CNIVEL' => $laFila[2]];
    //Datos de los alumno
    $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroDni, B.cEmail FROM T01DALU A
                    INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laAlumno = null;
    while ($laFila = $p_oSql->fetch($R1)) {
      $laAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CNRODNI' => $laFila[2], 'CEMAIL' => $laFila[3]];
    }
    $lcSql = "SELECT dFecha FROM T01DDEC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cEstTes = 'B'";
    $R1 = $p_oSql->omExec($lcSql);
    $RS = $p_oSql->fetch($R1);
    if ($RS[0] == '') {
      $this->pcError = 'ERROR, NO SE ENCUENTRA REGISTRO DE DICTAMINADORES EN EL SISTEMA';
      return false;
    }
    //Datos de los dictaminadores del borrador de Tesis
    $laDocenteD = null;
    $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI') FROM T01DDOC A
                    INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                    LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego ='A' AND A.cEstado = 'A' ORDER BY A.cCodDoc";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      if ($this->laData['DFECHA'] < $laFila[4]) {
        $this->laData['DFECHA'] = $laFila[4];
        $this->laData['DFECHOR'] = $laFila[5];
      }
      $laDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CDESCRI' => $laFila[2], 'CEMAIL' => $laFila[3], 'TDICTAM' => $laFila[4], 'DFECHOR' => $laFila[5]];
    }
    $this->laData['CDICTAM'] = $this->paData['CIDTESI'] . '-A-' . $this->laData['CSIGLA'] . '-' . substr($this->laData['DFECHA'], 0, 4);
    //Datos del Asesor
    $this->paData = $this->laData;
    $this->paAlumno = $laAlumno;
    $this->paDocente = $laDocente;
    return true;
  }

  //---------------------------------------------------------------------
  // Generacion de Reporte Compromiso de Asesor
  // 2020-07-09 FLC
  //---------------------------------------------------------------------
  public function omReporteCompromisoAsesor()
  {
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxDatosIDTesiCompromisoAsesor($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $llOk = $this->mxDatosReporteAsesoria($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $this->mxReporteCompromisoAsesor($loPdf);
    $this->paData['FILE'] = $this->pcFile;
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxDatosIDTesiCompromisoAsesor($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, B.cEstTes FROM T01DALU A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                  WHERE A.cEstado = 'A' AND B.cEstTes IN ('E', 'F', 'G', 'H', 'I', 'J', 'K') AND A.cCodAlu = '{$this->paData['CCODALU']}'";
    $R1 = $p_oSql->omExec($lcSql);
    if ($R1 == false || $p_oSql->pnNumRow == 0) {
      $this->pcError = "ERROR: SU ASESORIA AUN NO HA CULMINADO, EN ESTE MOMENTO NO SE PUEDE GENERAR EL DOCUMENTO";
      return false;
    }
    $laFila = $p_oSql->fetch($R1);
    if ($this->paData['CESTTES'] > $laFila[1]) {
      $this->pcError = 'EN ESTE MOMENTO NO SE PUEDE GENERAR EL\nDOCUMENTO ';
      return false;
    }
    $this->paData = ['CIDTESI' => $laFila[0]];
    return true;
  }

  protected function mxDatosReporteAsesoria($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cUniAca, A.cEstTes FROM T01MTES A
                  INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'DFECHA' => '', 'DFECHOR' => ''];
    //
    $lcSql = "SELECT cSigla, cNomUni, cNivel FROM S01TUAC WHERE cUniAca = '{$this->laData['CUNIACA']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData += ['CSIGLA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CNIVEL' => $laFila[2]];
    //Datos de los alumno
    $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroDni, B.cEmail FROM T01DALU A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laAlumno = null;
    while ($laFila = $p_oSql->fetch($R1)) {
      $laAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CNRODNI' => $laFila[2], 'CEMAIL' => $laFila[3]];
    }

    //
    $lcSql = "SELECT dFecha FROM T01DDEC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cEstTes = 'D'";
    $R1 = $p_oSql->omExec($lcSql);
    $RS = $p_oSql->fetch($R1);
    if ($RS[0] == '') {
      $this->pcError = 'ERROR, NO SE ENCUENTRA REGISTRO DE ASESORIA';
      return false;
    }
    //Datos del Asesor
    $laDocente = null;
    $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, B.cNroDni, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI')
                  FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego ='B' AND A.cEstado = 'A'";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $laDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CDESCRI' => $laFila[2], 'CEMAIL' => $laFila[3], 'CNRODNI' => $laFila[4], 'TDICTAM' => $laFila[5],
        'DFECHOR' => $laFila[6]];
    }
    $this->paData = $this->laData;
    $this->paAlumno = $laAlumno;
    $this->paDocente = $laDocente;
    return true;
  }

  //---------------------------------------------------------------
  // GENERACIÓN DE REPORTE DE DCITAMEN DE ASESORIA PARA BACHILLER
  // 2020-07-09 ARP Creación
  //---------------------------------------------------------------
  public function omReporteDictamenAsesoria()
  {
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxDatosIDTesiDictamenAsesoria($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $llOk = $this->mxDatosReporteDictamenAsesoria($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    if (in_array($this->paData['CNIVEL'], ['03', '04'])) {
      $this->mxReporteDictamenAsesoriaPostGrado($loPdf);
    }
    else {
      $this->mxReporteDictamenAsesoria($loPdf);
    }
    $this->paData['FILE'] = $this->pcFile;
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxDatosIDTesiDictamenAsesoria($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, B.cEstTes FROM T01DALU A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                    WHERE A.cEstado = 'A' AND B.cEstTes IN ('E', 'F', 'G', 'H', 'I', 'J', 'K') AND A.cCodAlu = '{$this->paData['CCODALU']}'";
    $R1 = $p_oSql->omExec($lcSql);
    if ($R1 == false || $p_oSql->pnNumRow == 0) {
      $this->pcError = "ERROR: SU ASESORIA AUN NO HA CULMINADO, EN ESTE MOMENTO NO SE PUEDE GENERAR EL DOCUMENTO";
      return false;
    }
    $laFila = $p_oSql->fetch($R1);
    if ($this->paData['CESTTES'] > $laFila[1]) {
      $this->pcError = 'EN ESTE MOMENTO NO SE PUEDE GENERAR EL\nDOCUMENTO ';
      return false;
    }
    $this->paData = ['CIDTESI' => $laFila[0]];
    return true;
  }

  protected function mxDatosReporteDictamenAsesoria($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cUniAca, A.cEstTes, C.cDescri, A.cPrefij, B.cDescri FROM T01MTES A
                    INNER JOIN S01DLAV B ON B.cPrefij = A.cPrefij AND B.cUniaca = A.cUniaca
                    INNER JOIN V_S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CTIPO' => $laFila[4], 'CPREFIJ' => $laFila[5], 'CESPCIA' => $laFila[6], 'DFECHA' => '', 'DFECHOR' => ''];
    //
    $lcSql = "SELECT cSigla, cNomUni, cNivel FROM S01TUAC WHERE cUniAca = '{$this->laData['CUNIACA']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData += ['CSIGLA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CNIVEL' => $laFila[2]];
    //Datos de los alumno
    $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroDni, B.cEmail FROM T01DALU A
                    INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laAlumno = null;
    while ($laFila = $p_oSql->fetch($R1)) {
      $laAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CNRODNI' => $laFila[2], 'CEMAIL' => $laFila[3]];
    }
    //
    $lcSql = "SELECT dFecha FROM T01DDEC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cEstTes = 'D'";
    $R1 = $p_oSql->omExec($lcSql);
    $RS = $p_oSql->fetch($R1);
    if ($RS[0] == '') {
      $this->pcError = 'ERROR, NO SE ENCUENTRA REGISTRO DE ASESOR EN EL SISTEMA';
      return false;
    }
    //Datos de los dictaminadores del borrador de Tesis
    $laDocenteD = null;
    $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI') FROM T01DDOC A
                    INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                    LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego ='B' AND A.cEstado = 'A' ORDER BY A.cCodDoc";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      if ($this->laData['DFECHA'] < $laFila[4]) {
        $this->laData['DFECHA'] = $laFila[4];
        $this->laData['DFECHOR'] = $laFila[5];
      }
      $laDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CDESCRI' => $laFila[2],
        'CEMAIL' => $laFila[3], 'TDICTAM' => $laFila[4], 'DFECHOR' => $laFila[5]];
    }
    $this->laData['CDICTAM'] = $this->paData['CIDTESI'] . '-B-' . $this->laData['CSIGLA'] . '-' . substr($this->laData['DFECHA'], 0, 4);
    //Datos del Asesor
    $this->paData = $this->laData;
    $this->paAlumno = $laAlumno;
    $this->paDocente = $laDocente;
    return true;
  }

  //------------------------------
  // Generacion de dictamen 
  // 2020-09-02 FLC
  //-------------------------------
  public function omReporteDictamenBorrador()
  {
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxDatosIDTesiDictamenBorrador($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $llOk = $this->mxDatosReporteDictamenBorrador($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    if (in_array($this->paData['CNIVEL'], ['03', '04'])) {
      $this->mxReporteDictamenBorradorTesisPostGrado($loPdf);
    }
    else {
      $this->mxReporteDictamenBorradorTesis($loPdf);
    }
    $this->paData['FILE'] = $this->pcFile;
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxDatosIDTesiDictamenBorrador($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    $lcSql = "SELECT A.cIdTesi, B.cEstTes FROM T01DALU A INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi
                    WHERE A.cEstado = 'A' AND B.cEstTes IN ('G', 'H', 'I', 'J', 'K') AND A.cCodAlu = '{$this->paData['CCODALU']}'";
    $R1 = $p_oSql->omExec($lcSql);
    if ($R1 == false || $p_oSql->pnNumRow == 0) {
      $this->pcError = "ERROR: TODOS SUS DICTAMINADORES AUN NO HAN APROBADO EL BORRADOR, EN ESTE MOMENTO NO SE PUEDE GENERAR EL DOCUMENTO";
      return false;
    }
    $laFila = $p_oSql->fetch($R1);
    if ($this->paData['CESTTES'] > $laFila[1]) {
      $this->pcError = 'EN ESTE MOMENTO NO SE PUEDE GENERAR EL\nDOCUMENTO ';
      return false;
    }
    $this->paData = ['CIDTESI' => $laFila[0]];
    return true;
  }

  protected function mxDatosReporteDictamenBorrador($p_oSql)
  {
    $this->paDatos = [];
    //Datos de la Tesis
    /*$lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cUniAca, A.cEstTes, C.cDescri, A.cPrefij, B.cDescri FROM T01MTES A
                  INNER JOIN S01DLAV B ON B.cPrefij = A.cPrefij AND B.cUniaca = A.cUniaca
                  INNER JOIN V_S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";*/
    $lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cUniAca, A.cEstTes, C.cDescri, A.cPrefij, B.cDescri,A.cTipo FROM T01MTES A
                INNER JOIN S01DLAV B ON B.cPrefij = A.cPrefij AND B.cUniaca = A.cUniaca
                INNER JOIN V_S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    /*$this->laData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CTIPO' => $laFila[4], 'CPREFIJ' => $laFila[5], 'CESPCIA' => $laFila[6], 'DFECHA' => '', 'DFECHOR' => ''];*/
    $this->laData = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CTIPO' => $laFila[4], 
                     'CPREFIJ' => $laFila[5], 'CESPCIA' => $laFila[6], 'DFECHA' => '', 'DFECHOR' => '','CMODAL'=>$laFila[7] ];
    //
    $lcSql = "SELECT cSigla, cNomUni, cNivel FROM S01TUAC WHERE cUniAca = '{$this->laData['CUNIACA']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laFila = $p_oSql->fetch($R1);
    $this->laData += ['CSIGLA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CNIVEL' => $laFila[2]];
    //Datos de los alumno
    $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroDni, B.cEmail FROM T01DALU A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}'";
    $R1 = $p_oSql->omExec($lcSql);
    $laAlumno = null;
    while ($laFila = $p_oSql->fetch($R1)) {
      $laAlumno[] = ['CCODALU' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]), 'CNRODNI' => $laFila[2], 'CEMAIL' => $laFila[3]];
    }
    //
    $lcSql = "SELECT dFecha FROM T01DDEC WHERE cIdTesi = '{$this->paData['CIDTESI']}' AND cEstTes = 'F'";
    $R1 = $p_oSql->omExec($lcSql);
    $RS = $p_oSql->fetch($R1);
    if ($RS[0] == '') {
      $this->pcError = 'ERROR, NO SE ENCUENTRA REGISTRO DE DICTAMINADORES EN EL SISTEMA';
      return false;
    }
    //Datos de los dictaminadores del borrador de Tesis
    $laDocenteD = null;
    $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI') FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego ='C' AND A.cEstado = 'A' ORDER BY A.cCodDoc";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      if ($this->laData['DFECHA'] < $laFila[4]) {
        $this->laData['DFECHA'] = $laFila[4];
        $this->laData['DFECHOR'] = $laFila[5];
      }
      $laDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => str_replace('/', ' ', $laFila[1]),
        'CDESCRI' => $laFila[2], 'CEMAIL' => $laFila[3], 'TDICTAM' => $laFila[4], 'DFECHOR' => $laFila[5]];
    }
    $this->laData['CDICTAM'] = $this->paData['CIDTESI'] . '-C-' . $this->laData['CSIGLA'] . '-' . substr($this->laData['DFECHA'], 0, 4);
    //Datos del Asesor
    $this->paData = $this->laData;
    $this->paAlumno = $laAlumno;
    $this->paDocente = $laDocente;
    return true;
  }

  #-----------------------------------------------------------------------------------
  # Generar Libro de Actas General por Escuela Profesional - Modalidad - Especialidad
  # Creacion APR 2021-01-27
  #-----------------------------------------------------------------------------------
  public function omGenerarActaEscuelaGeneral()
  {
    $llOk = $this->mxValUsuario();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxRepGenerarActaEscuelaGeneral($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    if ($this->paDatos[0]['CNIVEL'] == '01') {
      $llOk = $this->mxPrintMostrarActaTesisPreGradoGeneral();
    }
    elseif ($this->paDatos[0]['CNIVEL'] == '02') {
      $llOk = $this->mxPrintMostrarActaTesisSegundaEspecialidadGeneral();
    }
    elseif ($this->paDatos[0]['CNIVEL'] == '03') {
      $llOk = $this->mxPrintMostrarActaTesisPostGradoMaestroGeneral();
    }
    elseif ($this->paDatos[0]['CNIVEL'] == '04') {
      $llOk = $this->mxPrintMostrarActaTesisPostGradoDoctorGeneral();
    }
    elseif (in_array($this->paDatos[0]['CNIVEL'], ['06', '09'])) {
      $llOk = $this->mxPrintMostrarActaActualizacionGeneral();
    }
    else {
      $this->pcError = 'ERROR, NIVEL DE LA UNIDAD ACADEMICA NO VALIDO, COMUNICARSE CON LA OFICINA ERP';
      return false;
    }
    $loSql->omDisconnect();
    return $llOk;
  }

  protected function mxRepGenerarActaEscuelaGeneral($p_oSql)
  {
    $paDatAca = array('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9');
    if (in_array($this->paData['CUNIACA'], $paDatAca)) {
      #VISUALIZAR LAS TESIS CON ACTAS GENERADAS DE SEGUNDA ESPECIALIDAD DE MEDICINA HUMANA
      $lcSql = "SELECT A.cIdTesi FROM T02MLIB A
                  INNER JOIN S01TUAC C ON C.CUNIACA = A.CUNIACA
                  INNER JOIN S01TTAB D ON D.CCODIGO = A.CRESULT
                  INNER JOIN T01MTES E ON E.CIDTESI = A.CIDTESI
                  INNER JOIN S01DLAV F ON F.CPREFIJ = E.CPREFIJ AND F.CUNIACA = A.CUNIACA
                  LEFT OUTER JOIN S01TTAB G ON G.CCODIGO = E.CTIPO AND G.CCODTAB = '143'
                  WHERE D.CCODTAB = '250' AND A.CUNIACA IN ('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9') AND A.CPREFIJ = '{$this->paData['CPREFIJ']}' AND A.CTIPO = '{$this->paData['CMODALI']}'
                    AND A.TINISUS BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' ORDER BY A.CFOLIO";
    }
    else {
      #VISUALIZAR LAS TESIS CON ACTAS GENERADAS DE SEGUNDA ESPECIALIDAD DE MEDICINA HUMANA
      $lcSql = "SELECT A.cIdTesi FROM T02MLIB A
                  INNER JOIN S01TUAC C ON C.CUNIACA = A.CUNIACA
                  INNER JOIN S01TTAB D ON D.CCODIGO = A.CRESULT
                  INNER JOIN T01MTES E ON E.CIDTESI = A.CIDTESI
                  INNER JOIN S01DLAV F ON F.CPREFIJ = E.CPREFIJ AND F.CUNIACA = A.CUNIACA
                  LEFT OUTER JOIN S01TTAB G ON G.CCODIGO = E.CTIPO AND G.CCODTAB = '143'
                  WHERE D.CCODTAB = '250' AND A.CUNIACA = '{$this->paData['CUNIACA']}' AND A.CPREFIJ = '{$this->paData['CPREFIJ']}' AND A.CTIPO = '{$this->paData['CMODALI']}'
                    AND A.TINISUS BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' ORDER BY A.CFOLIO";
    }
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $laData1 = null;
      $laDatos1 = null;
      #TRAER TODOS LOS DATOS DE LA TESIS 
      $lcSql = "SELECT A.cIdLibr, A.cFolio, A.cIdTesi, A.tIniSus, A.tFinSus, C.cNomUni, D.cDescri, F.cDescri, E.mTitulo, C.cDesTit, E.cPrefij, TRIM(C.cNivel), E.cGraTit, A.cUniAca, TRIM(G.cCodigo), G.cDescri, C.cDesGra
                  FROM T02MLIB A
                  INNER JOIN S01TUAC C ON C.CUNIACA = A.CUNIACA
                  INNER JOIN S01TTAB D ON D.CCODIGO = A.CRESULT
                  INNER JOIN T01MTES E ON E.CIDTESI = A.CIDTESI
                  INNER JOIN S01DLAV F ON F.CPREFIJ = E.CPREFIJ AND F.CUNIACA = A.CUNIACA
                  LEFT OUTER JOIN S01TTAB G ON G.CCODIGO = E.CTIPO AND G.CCODTAB = '143'
                  WHERE D.CCODTAB = '250' AND A.CIDTESI = '$laFila[0]'";
      $R2 = $p_oSql->omExec($lcSql);
      $laFila1 = $p_oSql->fetch($R2);
      #TRAER TODOS LOS DATOS DE LOS ESTUDIANTES AUTORES DE LA TESIS
      $lcSql = "SELECT C.cNombre, C.cCodAlu, C.cNroDni FROM T01MTES   A
                  INNER JOIN T01DALU   B ON B.cIdTesi = A.cIdTesi
                  INNER JOIN V_A01MALU C ON C.cCodAlu = B.cCodAlu
                  WHERE A.cIdTesi = '$laFila[0]'";
      $R3 = $p_oSql->omExec($lcSql);
      while ($laFila2 = $p_oSql->fetch($R3)) {
        $laData1[] = ['CNOMBRE' => str_replace('/', ' ', $laFila2[0]), 'CCODALU' => $laFila2[1], 'CNRODNI' => $laFila2[2]];
      }
      #TRAER TODOS LOS DATOS DEL JURADO DE SUSTENTACIÓN
      $lcSql = "SELECT C.cNombre, C.cCodDoc, D.cDescri, F.tFirma
                    FROM T01MTES   A
                    INNER JOIN T01DDOC   B ON B.cIdTesi = A.cIdTesi
                    INNER JOIN V_A01MDOC C ON C.cCodDoc = B.cCodDoc
                    INNER JOIN S01TTAB   D ON D.cCodigo = B.cCargo
                    INNER JOIN T02MLIB   E ON E.cIdTesi = A.cIdTesi
                    INNER JOIN T02DLIB   F ON F.cIdLibr = E.cIdLibr AND F.cUsuCod = B.cCodDoc
                    WHERE D.cCodTab = '140' AND A.cIdTesi = '$laFila[0]' AND F.cTipo = B.cCargo
                    ORDER BY CASE B.ccargo WHEN 'P' THEN 1 WHEN 'V' THEN 2 when 'S' THEN 3 END";
      $R4 = $p_oSql->omExec($lcSql);
      while ($laFila3 = $p_oSql->fetch($R4)) {
        $laDatos1[] = ['CNOMBRE' => str_replace('/', ' ', $laFila3[0]), 'CDESCRI' => $laFila3[2], 'CCODUSU' => $laFila3[1], 'TFIRMA' => $laFila3[3]];
      }
      $this->paDatos[] = ['CIDLIBR' => $laFila1[0], 'CFOLIO' => $laFila1[1], 'CIDTESI' => $laFila1[2], 'TINISUS' => $laFila1[3],
        'TFINSUS' => $laFila1[4], 'CNOMUNI' => $laFila1[5], 'CRESULT' => $laFila1[6], 'CESPCIA' => $laFila1[7],
        'MTITULO' => $laFila1[8], 'CDESTIT' => $laFila1[9], 'CPREFIJ' => $laFila1[10], 'CNIVEL' => $laFila1[11],
        'CGRATIT' => $laFila1[12], 'CUNIACA' => $laFila1[13], 'CCODIGO' => $laFila1[14], 'CDESCRI' => $laFila1[15],
        'CDESGRA' => $laFila1[16], 'ACODALU' => $laData1, 'ACODDOC' => $laDatos1];
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisPreGradoGeneral()
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $pdf = new FPDF();
      foreach ($this->paDatos as $laDatTes) {
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CNOMUNI']), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CDESCRI']), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        $pdf->Cell(0, 4, utf8_decode('ACTA DE SUSTENTACION PARA OPTAR EL'), 0, 2, 'C');
        $pdf->Cell(0, 1, ' ', 0, 2, 'C');
        $laDatTes['CGRATIT'] = str_replace('TITULO PROFESIONAL DE ', '', $laDatTes['CGRATIT']);
        if ($laDatTes['CCODIGO'] == 'B0') {
          $pdf->Multicell(0, 4, utf8_decode('GRADO ACADÉMICO DE ' . strtoupper($laDatTes['CDESGRA'])), 0, 'C');
        }
        else {
          $pdf->Multicell(0, 4, utf8_decode('TITULO PROFESIONAL DE ' . strtoupper($laDatTes['CGRATIT'])), 0, 'C');
        }
        if ($laDatTes['CPREFIJ'] != 'A' and $laDatTes['CPREFIJ'] != '*' and !in_array($laDatTes['CUNIACA'], ['4A', '4E', '73'])) {
          if (in_array($laDatTes['CUNIACA'], ['51']) and $lcAluCod <= '2000') {
            $pdf->Multicell(0, 4, utf8_decode($laDatTes['CESPCIA']), 0, 'C');
          }
          elseif (in_array($laDatTes['CUNIACA'], ['51']) and $lcAluCod > '2000') {
            $pdf->Multicell(0, 4, utf8_decode($laDatTes['CESPCIA']), 0, 'C');
          }
          else {
            $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $laDatTes['CESPCIA']), 0, 'C');
          }
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($laDatTes['TINISUS'], 0, 10)) . ' ' . substr($laDatTes['TINISUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria se reunió el jurado:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($laDatTes['ACODDOC'] as $laTmp) {
          $lnEspacio = 11;
          if ($laTmp['CDESCRI'] == 'VOCAL') {
            $lnEspacio = 17;
          }
          elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
            $lnEspacio = 10;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($laDatTes['CCODIGO'] == 'B0') {
          $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a)(ita) Egresado (a)(os)(as) en ' . $laDatTes['CNOMUNI'] . ':'), 0, 2, 'L');
        }
        else {
          $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a) (ita) Bachiller en ' . $laDatTes['CNOMUNI'] . ':'), 0, 2, 'L');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($laDatTes['ACODALU'] as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($laDatTes['CUNIACA'] == '42' or $laDatTes['CUNIACA'] == '65') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis intitulada:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'B0') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo de investigación titulado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T0') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T1') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis titulada:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T2') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T3') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la balota sorteada:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T4') {
          $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el:'), 0, 'J');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($laDatTes['MTITULO']), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Con el que desea optar el:'), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        if ($laDatTes['CCODIGO'] == 'B0') {
          $pdf->Cell(0, 4, utf8_decode('GRADO ACADÉMICO DE ' . strtoupper($laDatTes['CDESGRA'])), 0, 2, 'C');
        }
        else {
          $pdf->Cell(0, 4, utf8_decode('TITULO PROFESIONAL DE ' . strtoupper($laDatTes['CGRATIT'])), 0, 2, 'C');
        }
        $pdf->SetFont('times', 'B', 10);
        if ($laDatTes['CPREFIJ'] != 'A' and $laDatTes['CPREFIJ'] != '*' and !in_array($laDatTes['CUNIACA'], ['4A', '4E', '73'])) {
          if (in_array($laDatTes['CUNIACA'], ['51'])) {
            $pdf->Multicell(0, 4, utf8_decode($laDatTes['CESPCIA']), 0, 'C');
          }
          else {
            $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $laDatTes['CESPCIA']), 0, 'C');
          }
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        if ($laDatTes['CCODIGO'] == 'B0') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo de investigación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T0') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T1') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación de la tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T2') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T3') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        elseif ($laDatTes['CCODIGO'] == 'T4') {
          $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', $lnTamLet);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($laDatTes['TFINSUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($laDatTes['ACODDOC'] as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisSegundaEspecialidadGeneral()
  {
    $paDatAca = array('L8', 'H1', 'J1', 'M2', 'H3', 'H2', 'L5', 'L7', 'I6', 'H1', 'H7', 'H4', 'I8', 'I4', 'L4', 'H2', 'I5', 'M7', 'M3', 'I7', 'M4', 'O5', 'G9', 'J0', 'L6', 'M5', 'H0', 'L9', 'M7');
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $pdf = new FPDF();
      foreach ($this->paDatos as $laDatTes) {
        if (in_array($laDatTes['CUNIACA'], $paDatAca)) {
          $pdf->SetAutoPageBreak(true, 5);
          $pdf->AddPage('P', 'A4');
          $pdf->SetFont('times', 'B', 8);
          $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
          $pdf->SetFont('times', 'B', 15);
          $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
          $pdf->Cell(0, 2, ' ', 0, 2, 'C');
          $pdf->SetFont('times', 'B', 13);
          $pdf->Cell(0, 4, utf8_decode('ACTA PARA OPTAR'), 0, 2, 'C');
          $pdf->Cell(0, 1, ' ', 0, 2, 'C');
          $laDatTes['CGRATIT'] = str_replace('TITULO DE SEGUNDA ESPECIALIDAD EN ', '', $laDatTes['CGRATIT']);
          $pdf->Multicell(0, 4, utf8_decode('EL TITULO DE SEGUNDA ESPECIALIDAD EN ' . strtoupper($laDatTes['CGRATIT'])), 0, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($laDatTes['TINISUS'], 0, 10)) . ' ' . substr($laDatTes['TINISUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Católica de Santa María se reunió la Comisión de Evaluación conformada por:'), 0, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $i = 0;
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnEspacio = 28;
            if ($laTmp['CDESCRI'] == 'DECANO') {
              $lnEspacio = 59;
            }
            elseif ($laTmp['CDESCRI'] == 'DICTAMINADOR') {
              $lnEspacio = 49;
            }
            $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
            $lcFile = 'FILES/R' . rand() . '.png';
            $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
            $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
            QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
            $i += 1;
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Se procedio a la a la Evaluación del Expediente del señor (a) (ita) :'), 0, 2, 'L');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODALU'] as $laTmp) {
            $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('Quien acogiéndose a lo establecido en el Decreto Supremo N°007-2017-SA, que aprueba el Reglamento de la Ley N°30453, Ley del Sistema Nacional de Residentado Médico (SINAREME), desea obtener el TITULO PROFESIONAL DE SEGUNDA ESPECIALIDAD en: '), 0, 'J');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          $pdf->Cell(0, 4, utf8_decode(strtoupper($laDatTes['CGRATIT'])), 0, 2, 'C');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          if ($laDatTes['CCODIGO'] == 'S1') {
            $pdf->Cell(0, 4, utf8_decode('Presentando el Trabajo Académico Titulado:'), 0, 2, 'L');
          }
          elseif ($laDatTes['CCODIGO'] == 'S2') {
            $pdf->Cell(0, 4, utf8_decode('Presentando el Proyecto de investigación Titulado:'), 0, 2, 'L');
          }
          $pdf->SetFont('times', 'B', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($laDatTes['MTITULO']), 0, 'C');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('Habiendo cumplido con las exigencias normativas de la Facultad y, del Reglamento del SINAREME se da por:'), 0, 'J');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
          $pdf->SetFont('times', '', $lnTamLet);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Concluido el acto, firmaron:'), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnTpGety = $pdf->GetY();
            $pdf->SetFont('times', 'B', $lnTamLet);
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
            $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
            $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          }
        }
        else {
          $pdf->SetAutoPageBreak(true, 5);
          $pdf->AddPage('P', 'A4');
          $pdf->SetFont('times', 'B', 8);
          $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
          $pdf->SetFont('times', 'B', 15);
          $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'C');
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CDESCRI']), 0, 2, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'C');
          $pdf->SetFont('times', 'B', 13);
          $pdf->Cell(0, 4, utf8_decode('ACTA PARA OPTAR'), 0, 2, 'C');
          $pdf->Cell(0, 1, ' ', 0, 2, 'C');
          $laDatTes['CGRATIT'] = str_replace('TITULO ', '', $laDatTes['CGRATIT']);
          $pdf->Multicell(0, 4, utf8_decode('EL TITULO DE SEGUNDA ESPECIALIDAD EN  ' . strtoupper($laDatTes['CGRATIT'])), 0, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($laDatTes['TINISUS'], 0, 10)) . ' ' . substr($laDatTes['TINISUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria se reunió el jurado:'), 0, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $i = 0;
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnEspacio = 11;
            if ($laTmp['CDESCRI'] == 'VOCAL') {
              $lnEspacio = 17;
            }
            elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
              $lnEspacio = 10;
            }
            $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
            $lcFile = 'FILES/R' . rand() . '.png';
            $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
            $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
            QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
            $i += 1;
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a) (ita) :'), 0, 2, 'L');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODALU'] as $laTmp) {
            $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          if ($laDatTes['CCODIGO'] == 'S0') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis titulada:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'S1') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo académico titulado:'), 0, 'J');
          }
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($laDatTes['MTITULO']), 0, 'C');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Con el que desea optar el Titulo de SEGUNDA ESPECIALIDAD en:'), 0, 2, 'L');
          $pdf->Cell(0, 2, ' ', 0, 2, 'C');
          $pdf->SetFont('times', 'B', 10);
          $pdf->Multicell(0, 4, utf8_decode(strtoupper($laDatTes['CGRATIT'])), 0, 'C');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          if ($laDatTes['CCODIGO'] == 'S0') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación de la Tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'S1') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
          $pdf->SetFont('times', '', $lnTamLet);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($laDatTes['TFINSUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnTpGety = $pdf->GetY();
            $pdf->SetFont('times', 'B', $lnTamLet);
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
            $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
            $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          }
        }
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisPostGradoMaestroGeneral()
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $pdf = new FPDF();
      foreach ($this->paDatos as $laDatTes) {
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode('ESCUELA DE POSTGRADO'), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        $pdf->Cell(0, 4, utf8_decode('ACTAS DE SUSTENTACION DE TESIS PARA OPTAR'), 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode('EL GRADO ACADEMICO DE MAESTRO'), 0, 2, 'C');
        $laDatTes['CGRATIT'] = str_replace('TITULO ', '', $laDatTes['CGRATIT']);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En fecha ' . $loDate->dateSimpleText(substr($laDatTes['TINISUS'], 0, 10)) . ' a las ' . substr($laDatTes['TINISUS'], 11, 5) . ' horas, en el salón de grados virtual de la Universidad Católica de Santa María se reunió el jurado:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($laDatTes['ACODDOC'] as $laTmp) {
          $lnEspacio = 11;
          if ($laTmp['CDESCRI'] == 'VOCAL') {
            $lnEspacio = 17;
          }
          elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
            $lnEspacio = 10;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Para recibir las previas orales del(os) graduando(s):'), 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($laDatTes['ACODALU'] as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Quien(es) de acuerdo al Reglamento General de Grados y Títulos de la Universidad Católica de Santa María y el Reglamento de Grados Académicos de la Escuela de Postgrado, presentó(aron) la tesis titulada:'), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell(0, 4, utf8_decode($laDatTes['MTITULO']), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Con la cual desea(n) optar el grado académico de:'), 0, 2, 'L');
        $pdf->Ln(5);
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CGRATIT']), 0, 2, 'C');
        if ($laDatTes['CPREFIJ'] != '*') {
          $pdf->Cell(0, 4, utf8_decode('CON MENCIÓN EN ' . $laDatTes['CESPCIA']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Luego de la exposición del graduando y la absolución de las preguntas y observaciones realizadas, el jurado procedió a la deliberación y votación de acuerdo al reglamento; llegando al siguiente resultado:'), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', $lnTamLet);
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', $lnTamLet);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($laDatTes['TFINSUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($laDatTes['ACODDOC'] as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . ' - ' . $laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaTesisPostGradoDoctorGeneral()
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $pdf = new FPDF();
      foreach ($this->paDatos as $laDatTes) {
        $pdf->SetAutoPageBreak(true, 5);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('times', 'B', 8);
        $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 15);
        $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
        $pdf->Cell(0, 2, ' ', 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode('ESCUELA DE POSTGRADO'), 0, 2, 'C');
        $pdf->Cell(0, 4, ' ', 0, 2, 'C');
        $pdf->SetFont('times', 'B', 13);
        $pdf->Cell(0, 4, utf8_decode('ACTAS DE SUSTENTACION DE TESIS PARA OPTAR EL'), 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode('GRADO ACADEMICO DE DOCTOR'), 0, 2, 'C');
        $laDatTes['CGRATIT'] = str_replace('TITULO ', '', $laDatTes['CGRATIT']);
        if ($laDatTes['CPREFIJ'] != '*') {
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CESPCIA']), 0, 2, 'C');
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('En fecha ' . $loDate->dateSimpleText(substr($laDatTes['TINISUS'], 0, 10)) . ' a las ' . substr($laDatTes['TINISUS'], 11, 5) . ' horas, en el salón de grados virtual de la Universidad Católica de Santa María se reunió el jurado:'), 0, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $i = 0;
        $pdf->SetFont('times', 'B', 10);
        foreach ($laDatTes['ACODDOC'] as $laTmp) {
          $lnEspacio = 11;
          if ($laTmp['CDESCRI'] == 'VOCAL') {
            $lnEspacio = 17;
          }
          elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
            $lnEspacio = 10;
          }
          $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
          $lcFile = 'FILES/R' . rand() . '.png';
          $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
          $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
          QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $i += 1;
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Para recibir las previas orales del(os) graduando(s):'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        foreach ($laDatTes['ACODALU'] as $laTmp) {
          $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
        }
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Quien(es) de acuerdo al Reglamento General de Grados y Títulos de la Universidad Católica de Santa María y el Reglamento de Grados Académicos de la Escuela de Postgrado, presentó(aron) la tesis titulada:'), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Multicell(0, 4, utf8_decode($laDatTes['MTITULO']), 0, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Con la cual desea(n) optar el grado académico de:'), 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CGRATIT']), 0, 2, 'C');
        $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Multicell(0, 4, utf8_decode('Luego de la exposición del graduando y la absolución de las preguntas y observaciones realizadas, el jurado procedió a la deliberación y votación de acuerdo al reglamento; llegando al siguiente resultado:'), 0, 'J');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->SetFont('times', 'B', 10);
        $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($laDatTes['TFINSUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        foreach ($laDatTes['ACODDOC'] as $laTmp) {
          $lnTpGety = $pdf->GetY();
          $pdf->SetFont('times', 'B', 10);
          $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . ' - ' . $laTmp['CDESCRI']), 0, 'J');
          $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
          $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 18, 18, 'PNG');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        }
      }
      $pdf->Cell(0, 4, ' ', 0, 2, 'L');
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      return false;
    }
    return true;
  }

  protected function mxPrintMostrarActaActualizacionGeneral($p_oSql)
  {
    try {
      $loDate = new CDate;
      $lnPag = 0;
      $fecha_actual = date("Y-m-d");
      $pdf = new FPDF();
      foreach ($this->paDatos as $laDatTes) {
        if (in_array($laDatTes['CCODIGO'], ['B0', 'T0', 'T1'])) {
          $pdf->SetAutoPageBreak(true, 5);
          $pdf->AddPage('P', 'A4');
          $pdf->SetFont('times', 'B', 8);
          $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
          $pdf->SetFont('times', 'B', 15);
          $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
          $pdf->Cell(0, 2, ' ', 0, 2, 'C');
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CNOMUNI']), 0, 2, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'C');
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CDESCRI']), 0, 2, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'C');
          $pdf->SetFont('times', 'B', 13);
          $pdf->Cell(0, 4, utf8_decode('ACTA DE SUSTENTACION PARA OPTAR EL'), 0, 2, 'C');
          $pdf->Cell(0, 1, ' ', 0, 2, 'C');
          $laDatTes['CGRATIT'] = str_replace('TITULO PROFESIONAL DE ', '', $laDatTes['CGRATIT']);
          if ($laDatTes['CCODIGO'] == 'B0') {
            $pdf->Multicell(0, 4, utf8_decode('GRADO ACADÉMICO DE ' . strtoupper($laDatTes['CDESGRA'])), 0, 'C');
          }
          else {
            $pdf->Multicell(0, 4, utf8_decode('TITULO PROFESIONAL DE ' . strtoupper($laDatTes['CGRATIT'])), 0, 'C');
          }
          if ($laDatTes['CPREFIJ'] != 'A' and $laDatTes['CPREFIJ'] != '*' and !in_array($laDatTes['CUNIACA'], ['4A', '4E', '73'])) {
            if (in_array($laDatTes['CUNIACA'], ['51'])) {
              $pdf->Multicell(0, 4, utf8_decode($laDatTes['CESPCIA']), 0, 'C');
            }
            else {
              $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $laDatTes['CESPCIA']), 0, 'C');
            }
          }
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('En fecha y hora del ' . $loDate->dateSimpleText(substr($laDatTes['TINISUS'], 0, 10)) . ' ' . substr($laDatTes['TINISUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria se reunió el jurado:'), 0, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $i = 0;
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnEspacio = 11;
            if ($laTmp['CDESCRI'] == 'VOCAL') {
              $lnEspacio = 17;
            }
            elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
              $lnEspacio = 10;
            }
            $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
            $lcFile = 'FILES/R' . rand() . '.png';
            $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
            $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
            QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
            $i += 1;
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          if ($laDatTes['CCODIGO'] == 'B0') {
            $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a)(ita) Egresado (a)(os)(as) en ' . $laDatTes['CNOMUNI'] . ':'), 0, 2, 'L');
          }
          else {
            $pdf->Cell(0, 4, utf8_decode('Para recibir la sustentacion del señor (a) (ita) Bachiller en ' . $laDatTes . ':'), 0, 2, 'L');
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODALU'] as $laTmp) {
            $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          if ($laDatTes['CUNIACA'] == '42' or $laDatTes['CUNIACA'] == '65') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis intitulada:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'B0') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo de investigación titulado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T0') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T1') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la tesis titulada:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T2') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el trabajo informe titulado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T3') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó la balota sorteada:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T4') {
            $pdf->Multicell(0, 4, utf8_decode('Quien de acuerdo al Reglamento específico de Grados y Titulos de la Universidad Catolica de Santa Maria, presentó el:'), 0, 'J');
          }
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          $pdf->Multicell($pdf->GetPageWidth() - 23, 4, utf8_decode($laDatTes['MTITULO']), 0, 'C');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Con el que desea optar el:'), 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          if ($laDatTes['CCODIGO'] == 'B0') {
            $pdf->Cell(0, 4, utf8_decode('GRADO ACADÉMICO DE ' . strtoupper($laDatTes['CDESGRA'])), 0, 2, 'C');
          }
          else {
            $pdf->Cell(0, 4, utf8_decode('TITULO PROFESIONAL DE ' . strtoupper($laDatTes['CGRATIT'])), 0, 2, 'C');
          }
          $pdf->SetFont('times', 'B', 10);
          if ($laDatTes['CPREFIJ'] != 'A' and $laDatTes['CPREFIJ'] != '*' and !in_array($laDatTes['CUNIACA'], ['4A', '4E', '73'])) {
            if (in_array($laDatTes['CUNIACA'], ['51'])) {
              $pdf->Multicell(0, 4, utf8_decode($laDatTes['CESPCIA']), 0, 'C');
            }
            else {
              $pdf->Multicell(0, 4, utf8_decode('CON ESPECIALIDAD EN ' . $laDatTes['CESPCIA']), 0, 'C');
            }
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, utf8_decode(), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          if ($laDatTes['CCODIGO'] == 'B0') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo de investigación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T0') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T1') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación de la tesis, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T2') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación del Trabajo Académico, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T3') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          elseif ($laDatTes['CCODIGO'] == 'T4') {
            $pdf->Multicell(0, 4, utf8_decode('Conducida la sustentación, el jurado procedió a formular las correspondientes preguntas y aclaraciones sobre el tema, realizando a continuación, la votacion respectiva, obteniéndose el siguiente resultado:'), 0, 'J');
          }
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', $lnTamLet);
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
          $pdf->SetFont('times', '', $lnTamLet);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($laDatTes['TFINSUS'], 11, 5) . ', en el salón de grados virtual de la Universidad Catolica de Santa Maria.'), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnTpGety = $pdf->GetY();
            $pdf->SetFont('times', 'B', $lnTamLet);
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE']), 0, 'J');
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CDESCRI']), 0, 'J');
            $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
            $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 20, 20, 'PNG');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          }
        }
        else {
          $pdf->Cell(0, 4, utf8_decode('UCSM-ERP' . fxStringFixed(' ', 220) . 'FOLIO : ' . $laDatTes['CFOLIO']), 0, 2, 'L');
          $pdf->SetFont('times', 'B', 15);
          $pdf->Cell(0, 4, utf8_decode('UNIVERSIDAD CATOLICA DE SANTA MARIA'), 0, 2, 'C');
          $pdf->Cell(0, 2, ' ', 0, 2, 'C');
          $pdf->SetFont('times', 'B', 15);
          $pdf->Cell(0, 4, utf8_decode('ACTA DE PROCESO DE ACTUALIZACION'), 0, 2, 'C');
          $pdf->Cell(0, 4, ' ', 0, 2, 'C');
          $pdf->SetFont('times', 'B', 13);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('En el local de la Universidad Católica de Santa Maria, siendo las ' . substr($laDatTes['TINISUS'], 11, 5) . ' horas del dia ' . $loDate->dateSimpleText(substr($ldIniSus, 0, 10)) . ', se reunio el Jurado integrado por los señores docentes:'), 0, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $i = 0;
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnEspacio = 11;
            if ($laTmp['CDESCRI'] == 'VOCAL') {
              $lnEspacio = 17;
            }
            elseif ($laTmp['CDESCRI'] == 'SECRETARIO') {
              $lnEspacio = 10;
            }
            $pdf->Cell(0, 4, utf8_decode(fxStringFixed($laTmp['CDESCRI'], $lnEspacio) . fxStringFixed(':', 10) . $laTmp['CCODUSU'] . '  -  ' . $laTmp['CNOMBRE']), 0, 2, 'L');
            $lcFile = 'FILES/R' . rand() . '.png';
            $laDatTes['ACODDOC'][$i]['CFILE'] = $lcFile;
            $lcCodDoc = utf8_decode($laTmp['CDESCRI'] . ': ' . $laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . '  ' . $laTmp['TFIRMA']);
            QRcode::png(utf8_encode($lcCodDoc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
            $i += 1;
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Para evaluar el expediente presentado por el (la) Señor (Srta) Bachiller:'), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          foreach ($laDatTes['ACODALU'] as $laTmp) {
            $pdf->Cell(0, 4, utf8_decode($laTmp['CNOMBRE']), 0, 2, 'C');
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Multicell(0, 4, utf8_decode('Quien desa optar el Titulo Profesional de ' . $laDatTes['CGRATIT'] . ' en concordancia con la Resolucion N° 24212-R-2017 que aprueba el Ciclo de Proceso de Actualización en Contenidos de la Profesión, tendente al logro del Titulo Profesional de:'), 0, 'J');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CGRATIT']), 0, 2, 'C');
          if ($laDatTes['CPREFIJ'] != '*') {
            $pdf->Cell(0, 4, utf8_decode('MENCION EN ' . $laDatTes['CESPCIA']), 0, 2, 'C');
          }
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('POR LO TANTO'), 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Encontrándose conforme el expediente el jurado acordó darle el resultado de:'), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->SetFont('times', 'B', 10);
          $pdf->Cell(0, 4, utf8_decode($laDatTes['CRESULT']), 0, 2, 'C');
          $pdf->SetFont('times', '', 10);
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, utf8_decode('Siendo las ' . substr($laDatTes['TFINSUS'], 11, 5) . ', se dio por concluido el acto, y firmaron.'), 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          foreach ($laDatTes['ACODDOC'] as $laTmp) {
            $lnTpGety = $pdf->GetY();
            $pdf->SetFont('times', 'B', 10);
            $pdf->Multicell($pdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp['CCODUSU'] . ' - ' . $laTmp['CNOMBRE'] . ' - ' . $laTmp['CDESCRI']), 0, 'J');
            $lnTpGety = $lnTpGety + (($pdf->GetY() - $lnTpGety) / 2);
            $pdf->Image($laTmp['CFILE'], $pdf->GetPageWidth() - 30, $lnTpGety - 10, 18, 18, 'PNG');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
            $pdf->Cell(0, 4, ' ', 0, 2, 'L');
          }
        }
        $pdf->Cell(0, 4, ' ', 0, 2, 'L');
        $pdf->Output('F', $this->pcFile, true);
      }
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF';
      print_r($this->pcError);
      return false;
    }
    return true;
  }

  # -----------------------------------------------------
  # REPORTE GENERAL PARA DIRECCIÓN DE ESCUELA PROFESIONAL
  # 2022-06-23 APR Creacion 
  # ----------------------------------------------------- 

  public function omReporteGeneralDirectorEscuelaProfesional()
  {
    $llOk = $this->mxValParam();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxValUsuarioUnidaAcademica($loSql);
    if (!$llOk) {
      $loSql->omDisconnect();
      return false;
    }
    $llOk = $this->mxReporteGeneralDirectorEscuelaProfesional($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
      return false;
    }
    $llOk = $this->mxPrintMostrarReporteTesisEscuelaProfesionalPDF();
    return $llOk;
  }

  protected function mxValParam()
  {
    if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4) {
      $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO';
      return false;
    }
    elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3) {
      $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVALIDO';
      return false;
    }
    return true;
  }

  protected function mxValUsuarioUnidaAcademica($p_oSql)
  {
    if ($this->paData['CCENCOS'] == 'UNI') {
      # Si es super-usuario 
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      $R1 = $p_oSql->omExec($lcSql);
      $RS = $p_oSql->fetch($R1);
      if (strlen($RS[0]) == '') {
        return;
      }
      elseif ($RS[0] == 'A') {
        $this->laUniAca[] = '*';
        return true;
      }
    }
    if ($this->paData['CCENCOS'] == '0CP') { //BIBLIOTECA
      # Si es super-usuario 
      $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
      $R1 = $p_oSql->omExec($lcSql);
      $RS = $p_oSql->fetch($R1);
      if (strlen($RS[0]) == '') {
        return;
      }
      elseif ($RS[0] == 'A') {
        $this->laUniAca[] = '*';
        return true;
      }
    }
    if ($this->paData['CCENCOS'] == '08M') {
      # Director Postgrado 
      $lcSql = "SELECT cUniAca FROM S01TUAC WHERE cNivel in ('03','04') OR cUniaca = '99'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
        $this->laUniAca[] = $laFila[0];
      }
    }
    else {
      $lcSql = "SELECT DISTINCT B.cUniAca FROM V_S01PCCO A 
                    INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                    WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cUniAca != '00'";
      $R1 = $p_oSql->omExec($lcSql);
      while ($laFila = $p_oSql->fetch($R1)) {
        $this->laUniAca[] = $laFila[0];
      }
      if (count($this->laUniAca) == 0) {
        $this->pcError = 'USUARIO NO TIENE UNIDADES ACADEMICAS ASIGNADAS';
        return false;
      }
    }
    return true;
  }

  protected function mxReporteGeneralDirectorEscuelaProfesional($p_oSql)
  {
    $lcSql = "SELECT A.cIdTesi, A.mTitulo, A.cUniAca, B.cNomUni, B.cNivel, A.cNewReg, TO_CHAR(A.dEntreg, 'YYYY-mm-dd HH24:MI'), D.cDescri, C.cDescri AS desEst FROM T01MTES A 
              INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
              LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND TRIM(C.cCodigo) = A.cesttes
              LEFT OUTER JOIN V_S01TTAB D ON D.cCodTab = '143' AND D.cCodigo = A.cTipo 
              WHERE A.cEstado != 'X' ORDER BY A.tModifi, A.dEntreg desc";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $laCodEst = null;
      $laCodDoc = null;
      $laCodAse = null;
      $laCodDic = null;
      $laCodJur = null;
      if ($this->laUniAca[0] == '*') {
      }
      else if (!in_array($laFila[2], $this->laUniAca)) {
        continue;
      }
      #TRAER INFORMACIÓN DE ESTUDIANTES AUTORES DE TESIS
      $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroCel, B.cEmailp, B.cEmail FROM T01DALU A 
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cEstado = 'A'";
      $R2 = $p_oSql->omExec($lcSql);
      while ($laTmp1 = $p_oSql->fetch($R2)) {
        $laCodEst[] = ['CCODALU' => $laTmp1[0], 'CNOMALU' => str_replace('/', ' ', $laTmp1[1]), 'CNROCEL' => $laTmp1[2],
          'CEMAILP' => $laTmp1[3], 'CEMAIL' => $laTmp1[4]];
      }
      #INFORMACIÓN DE DICTAMINADORES DE PROYECTO DE TESIS
      $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), C.cDescri
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cCatego = 'A' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $R3 = $p_oSql->omExec($lcSql);
      while ($laTmp2 = $p_oSql->fetch($R3)) {
        $ltDictam = ($laTmp2[3] == '') ? 'S/D' : $laTmp2[3];
        $laCodDoc[] = ['CCODDOC' => $laTmp2[0], 'CNOMDOC' => str_replace('/', ' ', $laTmp2[1]), 'TDECRET' => $laTmp2[2], 'TDICTAM' => $ltDictam, 'CDESCRI' => $laTmp2[4]];
      }
      #INFORMACIÓN DE ASESORIA
      $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), C.cDescri
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cCatego = 'B' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $R4 = $p_oSql->omExec($lcSql);
      while ($laTmp3 = $p_oSql->fetch($R4)) {
        $ltDictam = ($laTmp3[3] == '') ? 'S/D' : $laTmp3[3];
        $laCodAse[] = ['CCODDOC' => $laTmp3[0], 'CNOMDOC' => str_replace('/', ' ', $laTmp3[1]), 'TDECRET' => $laTmp3[2], 'TDICTAM' => $ltDictam, 'CDESCRI' => $laTmp3[4]];
      }
      #INFORMACIÓN DE DICTAMINADORES DE BORRADOR DE TESIS
      $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), C.cDescri
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cCatego = 'C' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $R5 = $p_oSql->omExec($lcSql);
      while ($laTmp4 = $p_oSql->fetch($R5)) {
        $ltDictam = ($laTmp4[3] == '') ? 'S/D' : $laTmp4[3];
        $laCodDic[] = ['CCODDOC' => $laTmp4[0], 'CNOMDOC' => str_replace('/', ' ', $laTmp4[1]), 'TDECRET' => $laTmp4[2], 'TDICTAM' => $ltDictam, 'CDESCRI' => $laTmp4[4]];
      }
      #INFORMACIÓN DE JURADO DE SUSTENTACIÓN DE TESIS
      $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), C.cDescri, D.cDescri
                  FROM T01DDOC A 
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo 
                  INNER JOIN S01TTAB D ON D.cCodigo = A.cResult AND D.cCodTab = '253'
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cCatego = 'D' AND A.cEstado = 'A' ORDER BY A.nSerial";
      $R6 = $p_oSql->omExec($lcSql);
      while ($laTmp5 = $p_oSql->fetch($R6)) {
        $ltDictam = ($laTmp5[3] == '') ? 'S/D' : $laTmp5[3];
        if ($laTmp5[5] == 'APROBADO') {
          $lcDescri = 'FIRMADO';
        }
        else {
          $lcDescri = $laFila[5];
        }
        $laCodJur[] = ['CCODDOC' => $laTmp5[0], 'CNOMDOC' => str_replace('/', ' ', $laTmp5[1]), 'TDECRET' => $laTmp5[2], 'TDICTAM' => $ltDictam, 'CCARGO' => $laTmp5[4], 'CDESCRI' => $lcDescri];
      }
      #VALIDAR DESCRIPCIÓN DE REGLAMENTO
      if ($laFila[5] == '0') {
        $lcDesReg = 'ANTIGUO';
      }
      else {
        $lcDesReg = 'NUEVO';
      }
      $this->laDatos[] = ['CIDTESI' => $laFila[0], 'MTITULO' => $laFila[1], 'CUNIACA' => $laFila[2], 'CNOMUNI' => $laFila[3],
                          'CNIVEL'  => $laFila[4], 'CNEWREG' => $lcDesReg,  'DENTREG' => $laFila[6], 'CMODALI' => $laFila[7],
                          'ACODALU' => $laCodEst,  'ACODDOC' => $laCodDoc,  'ACODASE' => $laCodAse,  'ACODDIC' => $laCodDic,
                          'ACODJUR' => $laCodJur, 'CDESEST'=> $laFila[8]];
    }
    return true;
  }

  protected function mxPrintMostrarReporteTesisEscuelaProfesionalPDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $fecha_actual = date("Y-m-d H:i:s");
      $pdf = new FPDF();
      foreach ($this->laDatos as $laFila) {
        $pdf->AddPage('L', 'A4');
        $pdf->SetFont('Courier', 'B', 10);
        $pdf->Image('img/logo_trazos.png', 10, 10, 48);
        $pdf->Ln(5);
        $pdf->Cell(0, 0, utf8_decode('REPORTE GENERAL DE TESIS PRESENTADAS EN EL SISTEMA'), 0, 0, 'C');
        $pdf->Cell(0, 0, utf8_decode('PAG:' . fxNumber($pdf->PageNo(), 6, 0)), 0, 0, 'R');
        $pdf->Ln(5);
        $pdf->Cell(0, 0, utf8_decode($this->paData['CUSUCOD'] . " - " . $this->paData['CNOMBRE']), 0, 0, 'C');
        $pdf->Cell(0, 0, utf8_decode(date("Y-m-d")), 0, 0, 'R');
        $pdf->Ln(5);
        $pdf->SetFont('Courier', 'B', 6);
        $pdf->Cell(1, 0, utf8_decode('FPG1420'), 0, 0, 'L');
        $pdf->SetFont('Courier', 'B', 10);
        $pdf->Cell(0, 0, 'UCSM - ERP', 0, 0, 'R');
        $pdf->Ln(2);
        $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
        $pdf->Ln(1);
        $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
        $pdf->Ln(4);
        $pdf->SetFont('Courier', 'B', 11);
        $pdf->Cell(34, 5, utf8_decode('*** TESIS ' . $laFila['CIDTESI']), 0, 0, 'L');
        $pdf->Ln(4);
        $pdf->SetFont('Courier', '', 10);
        $pdf->Cell(34, 5, utf8_decode('FECHA DE REGISTRO   : ' . $laFila['DENTREG'] . '                                               REGLAMENTO     : ' . $laFila['CNEWREG']), 0);
        $pdf->Ln(4);
        $pdf->Cell(34, 5, utf8_decode('ESCUELA PROFESIONAL : ' . $laFila['CUNIACA'] . ' - ' . $laFila['CNOMUNI']), 0);
        $pdf->Ln(4);
        $pdf->Cell(34, 5, utf8_decode('MODALIDAD           : ' . fxStringFixed($laFila['CMODALI'],43) . '                    ESTADO         : ' . $laFila['CDESEST']), 0);
        $pdf->Ln(4);
        $pdf->Cell(47, 5, utf8_decode('TITULO              : '), 0, 0, 'L');
        $pdf->Multicell(225, 5, utf8_decode(strtoupper($laFila['MTITULO'])), 0, 'J');
        $pdf->Ln(0);
        foreach ($laFila['ACODALU'] as $laTmp1) {
          $pdf->Cell(0, 5, '---DATOS--------------------------------------------------------------------------------------------------------------------------', 0);
          $pdf->Ln(3);
          $pdf->Cell(34, 5, utf8_decode('CÓDIGO ESTUDIANTE   : ' . $laTmp1['CCODALU'] . '                                                     NÚMERO TELEFÓNICO  : ' . $laTmp1['CNROCEL']), 0);
          $pdf->Ln(4);
          $pdf->Cell(34, 5, utf8_decode('APELLIDOS Y NOMBRES : ' . $laTmp1['CNOMALU']), 0);
          $pdf->Ln(4);
          $pdf->Cell(34, 5, utf8_decode('CORREO INSTITUCIONAL: ' . $laTmp1['CEMAIL']), 0);
          $pdf->Ln(4);
          $pdf->Cell(34, 5, utf8_decode('CORREO PERSONAL     : ' . $laTmp1['CEMAILP']), 0);
          $pdf->Ln(4);
        }
        $pdf->Cell(0, 5, '---DETALLE------------------------------------------------------------------------------------------------------------------------', 0);
        $pdf->Ln(6);
        $pdf->Cell(30, 0, utf8_decode('Etapa'), 0, 0, 'C');
        $pdf->Cell(18, 0, utf8_decode('Código'), 0, 0, 'C');
        $pdf->Cell(107, 0, utf8_decode('Apellidos y Nombres'), 0, 0, 'C');
        $pdf->Cell(38, 0, utf8_decode('Fecha Decreto'), 0, 0, 'C');
        $pdf->Cell(38, 0, utf8_decode('Fecha Dictamen'), 0, 0, 'C');
        $pdf->Cell(25, 0, utf8_decode('Cargo'), 0, 0, 'C');
        $pdf->Cell(20, 0, utf8_decode('Resultado'), 0, 0, 'C');
        $pdf->Ln(1);
        $pdf->Cell(0, 5, '----------------------------------------------------------------------------------------------------------------------------------', 0);
        $pdf->Ln(6);
        foreach ($laFila['ACODDOC'] as $laTmp2) {
          $pdf->Cell(30, 0, utf8_decode('PROYECTO/PLAN'), 0, 0, 'L');
          $pdf->Cell(18, 0, utf8_decode($laTmp2['CCODDOC']), 0, 0, 'C');
          $pdf->Cell(107, 0, utf8_decode($laTmp2['CNOMDOC']), 0, 0, 'L');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDECRET']), 0, 0, 'C');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDICTAM']), 0, 0, 'C');
          $pdf->Cell(25, 0, '', 0, 0, 'C');
          $pdf->Cell(20, 0, utf8_decode($laTmp2['CDESCRI']), 0, 0, 'C');
          $pdf->Ln(4);
        }
        foreach ($laFila['ACODASE'] as $laTmp2) {
          $pdf->Cell(30, 0, utf8_decode('ASESORIA'), 0, 0, 'L');
          $pdf->Cell(18, 0, utf8_decode($laTmp2['CCODDOC']), 0, 0, 'C');
          $pdf->Cell(107, 0, utf8_decode($laTmp2['CNOMDOC']), 0, 0, 'L');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDECRET']), 0, 0, 'C');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDICTAM']), 0, 0, 'C');
          $pdf->Cell(25, 0, '', 0, 0, 'C');
          $pdf->Cell(20, 0, utf8_decode($laTmp2['CDESCRI']), 0, 0, 'C');
          $pdf->Ln(4);
        }
        foreach ($laFila['ACODDIC'] as $laTmp2) {
          $pdf->Cell(30, 0, utf8_decode('BORRADOR'), 0, 0, 'L');
          $pdf->Cell(18, 0, utf8_decode($laTmp2['CCODDOC']), 0, 0, 'C');
          $pdf->Cell(107, 0, utf8_decode($laTmp2['CNOMDOC']), 0, 0, 'L');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDECRET']), 0, 0, 'C');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDICTAM']), 0, 0, 'C');
          $pdf->Cell(25, 0, '', 0, 0, 'C');
          $pdf->Cell(20, 0, utf8_decode($laTmp2['CDESCRI']), 0, 0, 'C');
          $pdf->Ln(4);
        }
        foreach ($laFila['ACODJUR'] as $laTmp2) {
          $pdf->Cell(30, 0, utf8_decode('JURADO'), 0, 0, 'L');
          $pdf->Cell(18, 0, utf8_decode($laTmp2['CCODDOC']), 0, 0, 'C');
          $pdf->Cell(107, 0, utf8_decode($laTmp2['CNOMDOC']), 0, 0, 'L');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDECRET']), 0, 0, 'C');
          $pdf->Cell(38, 0, utf8_decode($laTmp2['TDICTAM']), 0, 0, 'C');
          $pdf->Cell(25, 0, utf8_decode($laTmp2['CCARGO']), 0, 0, 'C');
          $pdf->Cell(20, 0, utf8_decode($laTmp2['CDESCRI']), 0, 0, 'C');
          $pdf->Ln(4);
        }
        $i++;
        $j++;
      }
      $pdf->Output('F', $this->pcFile, true);
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR PDF DE INFORMACIÓN DE TESIS';
      return false;
    }
    return true;
  }

  # -------------------------------------------------------
  # REPORTE GENERAL DECRETOS DONDE DOCENTE ES DICTAMINADOR
  # 2022-07-11 APR Creacion 
  # -------------------------------------------------------

  public function omReporteGenerarlDecretoProyectoDocente()
  {
    $llOk = $this->mxValParaReporteGenerarlDecretoYDictamenes();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxReporteGenerarlDecretoProyectoDocente($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
      return false;
    }
    if ($this->paData['CESTTES'] == 'B') {
      $llOk = $this->mxPrintReporteGenerarlDecretoProyectoDocentePDF();
    } elseif ($this->paData['CESTTES'] == 'D') {
        $llOk = $this->mxPrintReporteGenerarlDecretoAsesorDocentePDF();
    } elseif ($this->paData['CESTTES'] == 'F') {
        $llOk = $this->mxPrintReporteGenerarlDecretoBorradorDocentePDF();
    }
    return $llOk;
  }

  protected function mxValParaReporteGenerarlDecretoYDictamenes()
  {
    $loDate = new CDate();
    if (!isset($this->paData['DINICIO']) || !$loDate->mxValDate($this->paData['DINICIO'])) {
      $this->pcError = 'FECHA INICIAL INVALIDA';
      return false;
    } elseif (!isset($this->paData['DFINALI']) || !$loDate->mxValDate($this->paData['DFINALI'])) {
        $this->pcError = 'FECHA FINAL INVALIDA';
        return false;
    } elseif ($this->paData['DFINALI'] < $this->paData['DINICIO']) {
        $this->pcError = 'FECHA FINAL ES MENOR QUE FECHA DE INICIO';
        return false;
    } elseif (!isset($this->paData['CUSUCOD']) || strlen(trim($this->paData['CUSUCOD'])) != 4) {
        $this->pcError = "USUARIO INVALIDO O NO DEFINIDO";
        return false;
    } elseif (!isset($this->paData['CCENCOS']) || strlen(trim($this->paData['CCENCOS'])) != 3) {
        $this->pcError = "CENTRO DE COSTO INVALIDO O NO DEFINIDO";
        return false;
    } 
    return true;
  }

  protected function mxReporteGenerarlDecretoProyectoDocente($p_oSql)
  {
    $lcSql = "SELECT DISTINCT A.cIdTesi, A.dFecha FROM T01DDEC A
                INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi 
                WHERE A.cEstado != 'X' AND B.cEstado != 'X' AND B.cCodDoc = '{$this->paData['CUSUCOD']}' AND A.cEstTes = '{$this->paData['CESTTES']}' AND B.cCatego = '{$this->paData['CCATEGO']}'
                  AND A.dFecha::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' ORDER BY A.dFecha";
    $R1 = $p_oSql->omExec($lcSql);
    while ($laFila = $p_oSql->fetch($R1)) {
      $laCodEst = null;
      $laCodDoc = null;
      #TRAER INFORMACIÓN DE LA TESIS
      $lcSql = "SELECT A.cIdTesi, A.mTitulo, B.cUniAca, A.cEstTes, A.tDiaSus, C.cDescri, A.cTipo, B.cPrefij, B.cDescri FROM T01MTES A
                  INNER JOIN S01DLAV B ON B.cPrefij = A.cPrefij AND B.cUniaca = A.cUniaca
                  LEFT OUTER JOIN S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cEstado = 'A'";
      $R2 = $p_oSql->omExec($lcSql);
      $laTmp1 = $p_oSql->fetch($R2);
      #TRAER INFORMACIÓN DE ESTUDIANTES AUTORES DE TESIS
      $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroCel, B.cEmailp, B.cEmail FROM T01DALU A 
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cEstado = 'A'";
      $R3 = $p_oSql->omExec($lcSql);
      while ($laTmp2 = $p_oSql->fetch($R3)) {
        $laCodEst[] = ['CCODALU' => $laTmp2[0], 'CNOMALU' => str_replace('/', ' ', $laTmp2[1])];
      }
      #CARGO DE DECANO Y DIRECTOR
      $lcSql = "SELECT cDesDec, cDesDir, cSigla, cNomUni, cNivel, cEmail FROM S01TUAC WHERE cUniAca = '$laTmp1[2]'";
      $R4 = $p_oSql->omExec($lcSql);
      $laTmp3 = $p_oSql->fetch($R4);
      #CARGO DE DECANO Y DIRECTOR
      $lcSql = "SELECT A.cDocen1, B.cNombre, A.cDocen2, C.cNombre, TO_CHAR(A.dFecha, 'YYYY-MM-DD'), TO_CHAR(A.dFecha, 'YYYYMMDDHH24MI') FROM T01DDEC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cDocen1
                  INNER JOIN V_A01MDOC C ON C.cCodDoc = A.cDocen2
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cEstado ='A' AND A.cEstTes = '{$this->paData['CESTTES']}'";
      $R5 = $p_oSql->omExec($lcSql);
      $laTmp4 = $p_oSql->fetch($R5);
      #INFORMACIÓN DE DICTAMINADORES DE PROYECTO DE TESIS
      $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '$laFila[0]' AND A.cCatego = '{$this->paData['CCATEGO']}' AND A.cEstado = 'A'
                  ORDER BY CASE A.ccargo WHEN 'P' THEN 1 WHEN 'V' THEN 2 when 'S' THEN 3 WHEN '*' THEN 4 END, A.nSerial";
      $R6 = $p_oSql->omExec($lcSql);
      while ($laTmp5 = $p_oSql->fetch($R6)) {
        $lcCargo = ($laTmp5[2] == '') ? 'S/CARGO' : $laTmp5[3];
        $laCodDoc[] = ['CCODDOC' => $laTmp5[0], 'CNOMDOC' => str_replace('/', ' ', $laTmp5[1]), 'CCARGO' => $lcCargo];
      }
      if ($this->paData['CESTTES'] == 'B') {
        $lcDictam = $laTmp1[0] . '-1-' . $laTmp3[2] . '-' . substr($laTmp4[4], 0, 4);
      }
      elseif ($this->paData['CESTTES'] == 'D') {
        $lcDictam = $laTmp1[0] . '-2-' . $laTmp3[2] . '-' . substr($laTmp4[4], 0, 4);
      }
      elseif ($this->paData['CESTTES'] == 'F') {
        $lcDictam = $laTmp1[0] . '-3-' . $laTmp3[2] . '-' . substr($laTmp4[4], 0, 4);
      }
      elseif ($this->paData['CESTTES'] == 'I') {
        $lcDictam = $laTmp1[0] . '-4-' . $laTmp3[2] . '-' . substr($laTmp4[4], 0, 4);
      }
      #ARREGLO DE DATOS
      $this->laDatos[] = ['CIDTESI' => $laTmp1[0], 'MTITULO' => $laTmp1[1], 'CUNIACA' => $laTmp1[2], 'CESTTES' => $laTmp1[3],
                          'TDIASUS' => $laTmp1[4], 'CMODALI' => $laTmp1[5], 'CTIPO'   => $laTmp1[6], 'CPREFIJ' => $laTmp1[7],
                          'CESPCIA' => $laTmp1[8], 'CCARDEC' => $laTmp3[0], 'CCARDIR' => $laTmp3[1], 'CSIGLA'  => $laTmp3[2],  
                          'CNOMUNI' => $laTmp3[3], 'CNIVEL'  => $laTmp3[4], 'CDECANO' => $laTmp4[0] . ' - ' . str_replace('/', ' ', $laTmp4[1]),
                          'CDIRECT' => $laTmp4[2] . ' - ' . str_replace('/', ' ', $laTmp4[3]),       'DFECHA' => $laTmp4[4],
                          'DFECHOR' => $laTmp4[5], 'CDICTAM' => $lcDictam,  'ACODALU' => $laCodEst,  'ACODDOC' => $laCodDoc];
    }
    return true;
  }

  protected function mxPrintReporteGenerarlDecretoProyectoDocentePDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este codigo configuro el documento PDF
      $lnWidth = 0; //Ancho de la celda
      $lnHeight = 0.5; //Alto de la celda
      $lnParraf = 8; //Ancho de la celda
      $lnTamLet = 10; //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new FPDF('P', 'mm', 'A4');
      foreach ($this->laDatos as $laFila) {
        $lcCodDir = utf8_decode($laFila['CDIRECT'].' '.$loDate->dateSimpleText($laFila['DFECHA']).' '.$laFila['CDECRET'].' '.$laFila['DFECHOR']);
        $lcCodDec = utf8_decode($laFila['CDECANO'].' '.$loDate->dateSimpleText($laFila['DFECHA']).' '.$laFila['CDECRET'].' '.$laFila['DFECHOR']);
        QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
        QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
        $loPdf->SetMargins(20, 24, 20);
        $loPdf->AddPage('P', 'A4');
        // A partir de este codigo se escribe en el documento
        $loPdf->SetFont('times', 'B', 14);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $loPdf->SetFont('times' ,'B', 12);
        $loPdf->Ln($lnParraf);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C'); 
          $loPdf->Ln($lnParraf);
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C'); 
        } else {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C'); 
        }
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DICTAMINADORES DE PROYECTO / PLAN'), 0, 'C');
        $loPdf->Ln($lnParraf);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
        } else {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CMODALI']), 0, 'C');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($laFila['DFECHA'])), 0, 'R');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'BU' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$laFila['CDICTAM']), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'', $lnTamLet);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Visto el expediente '.$laFila['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J');
        } else {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Visto el expediente '.$laFila['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        if ($laFila['CTIPO'] == 'B0') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T0') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T1') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T2') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T3') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T4') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S0') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de la Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S1') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabjo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S2') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'M0'){
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'D0'){
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de Tesis de (el)(la)(los) Maestro(a)(s)(as):'), 0, 2, '');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        foreach ($laFila['ACODALU'] as $laTmp) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp['CCODALU'].' - '.$laTmp['CNOMALU']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln(4);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose al jurado dictaminador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J');
        $loPdf->SetFont('times', 'B', $lnTamLet );
        $loPdf->Ln($lnParraf);
        foreach ($laFila['ACODDOC'] as $laTmp1) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp1['CCODDOC'].' - '.$laTmp1['CNOMDOC']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln(4);
        $loPdf->SetFont('times', '', $lnTamLet);
        if ($laFila['CTIPO'] == 'B0') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Trabajo de Investigación titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T0') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Trabajo Informe titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T1') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan de Tesis titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T2') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Trabajo Informe titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T3') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar la Balota Sorteada titulada:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T4') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Expediente titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S0') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan de Tesis titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S1') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan del Trabajo Académico titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S2') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan del Trabajo de Investigación titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'M0' || $laFila['CTIPO'] == 'D0'){
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan de Tesis titulado:'), 0, 'J');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['MTITULO']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        if (in_array($laFila['CNIVEL'], ['03','04'])){
          $loPdf->Multicell($lnWidth, 5, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
        } else {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('El Decanato, la Dirección y Secretaría de la Escuela se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
        }
        $loPdf->Ln($lnParraf * 2);
        // Firma del Decano de la facultad
        if(substr($laFila['CDECANO'], 0, 4) != '0000'){
          $lnTpGety=$loPdf->GetY();
          $loPdf->SetFont('times', 'B', 10);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode($laFila['CDECANO']), 0, 'J');
          $loPdf->Ln(5);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode($laFila['CCARDEC']), 0, 'J');
          $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
          $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
          $loPdf->Ln($lnParraf * 3);
        }
        // Firma del director de la escuela profesional
        if(substr($laFila['CDIRECT'], 0, 4) != '0000'){
          $lnTpGety = $loPdf->GetY();
          $loPdf->SetFont('times', 'B', $lnTamLet);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($laFila['CDIRECT'])), 0, 'J');
          $loPdf->Ln(5);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($laFila['CCARDIR'])), 0, 'J');
          $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
          $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
        }
      }
      $loPdf->Output('D', $this->pcFile, true);
      return true;
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR REPORTE GENERAL DE DECRETOS DE PROYECTO/PLAN DE DICTAMINADOR';
      return false;
    }
  }

  protected function mxPrintReporteGenerarlDecretoAsesorDocentePDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este codigo configuro el documento PDF
      $lnWidth = 0; //Ancho de la celda
      $lnHeight = 0.5; //Alto de la celda
      $lnParraf = 8; //Ancho de la celda
      $lnTamLet = 10; //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new FPDF('P', 'mm', 'A4');
      foreach ($this->laDatos as $laFila) {
        $lcCodDir = utf8_decode($laFila['CDIRECT'].' '.$loDate->dateSimpleText($laFila['DFECHA']).' '.$laFila['CDECRET'].' '.$laFila['DFECHOR']);
        $lcCodDec = utf8_decode($laFila['CDECANO'].' '.$loDate->dateSimpleText($laFila['DFECHA']).' '.$laFila['CDECRET'].' '.$laFila['DFECHOR']);
        QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
        QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
        $loPdf->SetMargins(20, 24, 20);
        $loPdf->AddPage('P', 'A4');
        // A partir de este codigo se escribe en el documento
        $loPdf->SetFont('times', 'B', 14);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $loPdf->SetFont('times' ,'B', 12);
        $loPdf->Ln($lnParraf);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C'); 
          $loPdf->Ln($lnParraf);
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C'); 
        } else {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C'); 
        }
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DE ASESOR'), 0, 'C');
        $loPdf->Ln($lnParraf);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
        } else {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CMODALI']), 0, 'C');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($laFila['DFECHA'])), 0, 'R');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'BU' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$laFila['CDICTAM']), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'', $lnTamLet);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Visto el expediente '.$laFila['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J');
        } else {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Visto el expediente '.$laFila['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        if ($laFila['CTIPO'] == 'B0') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T0') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T1') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T2') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T3') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T4') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S0') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S1') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S2') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'D0'){
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'M0' || $laFila['CTIPO'] == 'D0'){
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        foreach ($laFila['ACODALU'] as $laTmp) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp['CCODALU'].' - '.$laTmp['CNOMALU']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln(4);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose como asesor al docente:'), 0, 'J');
        $loPdf->SetFont('times', 'B', $lnTamLet );
        $loPdf->Ln($lnParraf);
        foreach ($laFila['ACODDOC'] as $laTmp1) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp1['CCODDOC'].' - '.$laTmp1['CNOMDOC']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln(4);
        $loPdf->SetFont('times', '', $lnTamLet);
        if ($laFila['CTIPO'] == 'B0') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo de Investigación titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T0') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo Informe titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T1') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T2') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo Informe titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T3') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Balota Sorteada titulada:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T4') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Expediente titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S0') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S1') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo Académico titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S2') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo de Investigación titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'M0' || $laFila['CTIPO'] == 'D0'){
            $loPdf->Multicell($lnWidth, 5, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['MTITULO']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        if (in_array($laFila['CNIVEL'], ['03','04'])){
          $loPdf->Multicell($lnWidth, 5, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
        } else {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('El Decanato, la Dirección y Secretaría de la Escuela se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
        }
        $loPdf->Ln($lnParraf * 2);
        // Firma del Decano de la facultad
        if(substr($laFila['CDECANO'], 0, 4) != '0000'){
          $lnTpGety=$loPdf->GetY();
          $loPdf->SetFont('times', 'B', 10);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode($laFila['CDECANO']), 0, 'J');
          $loPdf->Ln(5);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode($laFila['CCARDEC']), 0, 'J');
          $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
          $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
          $loPdf->Ln($lnParraf * 3);
        }
        // Firma del director de la escuela profesional
        if(substr($laFila['CDIRECT'], 0, 4) != '0000'){
          $lnTpGety = $loPdf->GetY();
          $loPdf->SetFont('times', 'B', $lnTamLet);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($laFila['CDIRECT'])), 0, 'J');
          $loPdf->Ln(5);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($laFila['CCARDIR'])), 0, 'J');
          $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
          $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
        }
      }
      $loPdf->Output('D', $this->pcFile, true);
      return true;
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR REPORTE GENERAL DE DECRETOS DE ASESOR';
      return false;
    }
  }

  protected function mxPrintReporteGenerarlDecretoBorradorDocentePDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este codigo configuro el documento PDF
      $lnWidth = 0; //Ancho de la celda
      $lnHeight = 0.5; //Alto de la celda
      $lnParraf = 8; //Ancho de la celda
      $lnTamLet = 10; //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new FPDF('P', 'mm', 'A4');
      foreach ($this->laDatos as $laFila) {
        $lcCodDir = utf8_decode($laFila['CDIRECT'].' '.$loDate->dateSimpleText($laFila['DFECHA']).' '.$laFila['CDECRET'].' '.$laFila['DFECHOR']);
        $lcCodDec = utf8_decode($laFila['CDECANO'].' '.$loDate->dateSimpleText($laFila['DFECHA']).' '.$laFila['CDECRET'].' '.$laFila['DFECHOR']);
        QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
        QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
        $loPdf->SetMargins(20, 24, 20);
        $loPdf->AddPage('P', 'A4');
        // A partir de este codigo se escribe en el documento
        $loPdf->SetFont('times', 'B', 14);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $loPdf->SetFont('times' ,'B', 12);
        $loPdf->Ln($lnParraf);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C'); 
          $loPdf->Ln($lnParraf);
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C'); 
        } else {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C'); 
        }
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DICTAMINADORES DE BORRADOR'), 0, 'C');
        $loPdf->Ln($lnParraf);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
        } else {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CMODALI']), 0, 'C');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($laFila['DFECHA'])), 0, 'R');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'BU' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$laFila['CDICTAM']), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'', $lnTamLet);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Visto el expediente '.$laFila['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J');
        } else {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Visto el expediente '.$laFila['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        if ($laFila['CTIPO'] == 'B0') {
          $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T0') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T1') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T2') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T3') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'T4') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S0') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S1') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'S2') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
        } elseif ($laFila['CTIPO'] == 'M0' || $laFila['CTIPO'] == 'D0') {
            $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        foreach ($laFila['ACODALU'] as $laTmp) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp['CCODALU'].' - '.$laTmp['CNOMALU']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln(4);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose como asesor al docente:'), 0, 'J');
        $loPdf->SetFont('times', 'B', $lnTamLet );
        $loPdf->Ln($lnParraf);
        foreach ($laFila['ACODDOC'] as $laTmp1) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp1['CCODDOC'].' - '.$laTmp1['CNOMDOC']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln(4);
        $loPdf->SetFont('times', '', $lnTamLet);
        if ($laFila['CTIPO'] == 'B0') {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Trabajo de Investigación titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T0') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo Informe titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T1') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Tesis titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T2') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo Informe titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T3') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de la Balota Sorteada titulada:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'T4') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Expediente titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S0') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Tesis titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S1') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo Académico titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'S2') {
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo de Investigación titulado:'), 0, 'J');
        } elseif ($laFila['CTIPO'] == 'M0' || $laFila['CTIPO'] == 'D0'){
            $loPdf->Multicell($lnWidth, 5, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Tesis titulado:'), 0, 'J');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['MTITULO']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times' ,'B' , $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        if (in_array($laFila['CNIVEL'], ['03','04'])){
          $loPdf->Multicell($lnWidth, 5, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
        } else {
          $loPdf->Multicell($lnWidth, 5, utf8_decode('El Decanato, la Dirección y Secretaría de la Escuela se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
        }
        $loPdf->Ln($lnParraf * 2);
        // Firma del Decano de la facultad
        if(substr($laFila['CDECANO'], 0, 4) != '0000'){
          $lnTpGety=$loPdf->GetY();
          $loPdf->SetFont('times', 'B', 10);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode($laFila['CDECANO']), 0, 'J');
          $loPdf->Ln(5);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode($laFila['CCARDEC']), 0, 'J');
          $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
          $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
          $loPdf->Ln($lnParraf * 3);
        }
        // Firma del director de la escuela profesional
        if(substr($laFila['CDIRECT'], 0, 4) != '0000'){
          $lnTpGety = $loPdf->GetY();
          $loPdf->SetFont('times', 'B', $lnTamLet);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($laFila['CDIRECT'])), 0, 'J');
          $loPdf->Ln(5);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($laFila['CCARDIR'])), 0, 'J');
          $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
          $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
        }
      }
      $loPdf->Output('D', $this->pcFile, true);
      return true;
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR REPORTE GENERAL DE DECRETOS DE BORRADOR DE DOCENTE DICTAMINADOR';
      return false;
    }
  }

  # -----------------------------------------------------------
  # REPORTE GENERAL DE DICTAMENES DONDE DOCENTE ES DICTAMINADOR
  # 2022-07-11 APR Creacion 
  # -----------------------------------------------------------

  public function omReporteGenerarlDictamenesProyectoDocente()
  {
    $llOk = $this->mxValParaReporteGenerarlDecretoYDictamenes();
    if (!$llOk) {
      return false;
    }
    $loSql = new CSql();
    $llOk = $loSql->omConnect();
    if (!$llOk) {
      $this->pcError = $loSql->pcError;
      return false;
    }
    $llOk = $this->mxReporteGenerarlDictamenesProyectoDocente($loSql);
    $loSql->omDisconnect();
    if (!$llOk) {
      return false;
    }
    if ($this->paData['CESTTES'] == 'B') {
      $llOk = $this->mxPrintReporteGenerarlDictamenesProyectoDocentePDF();
    } elseif ($this->paData['CESTTES'] == 'D') {
        $llOk = $this->mxPrintReporteGenerarlDictamenesAsesorDocentePDF();
    } elseif ($this->paData['CESTTES'] == 'F') {
        $llOk = $this->mxPrintReporteGenerarlDictamenesBorradorDocentePDF();
    }
    return $llOk;
  }

  protected function mxReporteGenerarlDictamenesProyectoDocente($p_oSql)
  {
    $lcSql = "SELECT DISTINCT A.cIdTesi, A.dFecha FROM T01DDEC A
                INNER JOIN T01DDOC B ON B.cIdTesi = A.cIdTesi 
                WHERE A.cEstado != 'X' AND B.cEstado != 'X' AND B.cResult = 'A' AND B.cCodDoc = '{$this->paData['CUSUCOD']}' AND A.cEstTes = '{$this->paData['CESTTES']}' AND B.cCatego = '{$this->paData['CCATEGO']}'
                  AND A.dFecha::DATE BETWEEN '{$this->paData['DINICIO']}' AND '{$this->paData['DFINALI']}' ORDER BY A.dFecha";
    $R1 = $p_oSql->omExec($lcSql);
    $i = 0;
    while ($laFila = $p_oSql->fetch($R1)) {
      $laCodEst = null;
      $laCodDoc = null;
      #VERIFICAR SI TESIS TIENE DICTAMEN APROBATORIO
      if ($this->paData['CESTTES'] == 'B') {
        $lcSql = "SELECT cIdTesi FROM T01MTES WHERE cEstado = 'A' AND cEstTes IN ('C','D','E','F','G','H','I','J','K') AND cIdTesi = '$laFila[0]'";
      } elseif ($this->paData['CESTTES'] == 'D'){
          $lcSql = "SELECT cIdTesi FROM T01MTES WHERE cEstado = 'A' AND cEstTes IN ('E','F','G','H','I','J','K') AND cIdTesi = '$laFila[0]'";
      } elseif ($this->paData['CESTTES'] == 'F'){
        $lcSql = "SELECT cIdTesi FROM T01MTES WHERE cEstado = 'A' AND cEstTes IN ('G','H','I','J','K') AND cIdTesi = '$laFila[0]'";
      }
      $R2 = $p_oSql->omExec($lcSql);
      $laTmp1 = $p_oSql->fetch($R2);
      #DATOS DE LA TESIS
      $lcSql = "SELECT A.cIdTesi, A.mTitulo, B.cUniAca, A.cEstTes, C.cDescri, A.cPrefij, B.cDescri FROM T01MTES A
                  INNER JOIN S01DLAV B ON B.cPrefij = A.cPrefij AND B.cUniaca = A.cUniaca
                  INNER JOIN V_S01TTAB C ON C.cCodigo = A.cTipo AND C.cCodTab = '143'
                  WHERE A.cIdTesi = '$laTmp1[0]'";
      $R3 = $p_oSql->omExec($lcSql);
      #print_r($lcSql);
      $laTmp2 = $p_oSql->fetch($R3);
      #INFORMACIÓN DE UNIDAD ACADEMICA
      $lcSql = "SELECT cSigla, cNomUni, cNivel FROM S01TUAC WHERE cUniAca = '$laTmp2[2]'";
      $R4 = $p_oSql->omExec($lcSql);
      $laTmp3 = $p_oSql->fetch($R4);
      #DATOS DE ESTUDIANTES AUTORES DE TESIS
      $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroDni, B.cEmail FROM T01DALU A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                  WHERE A.cIdTesi = '$laTmp1[0]'";
      $R5 = $p_oSql->omExec($lcSql);
      while ($laTmp4 = $p_oSql->fetch($R5)) {
        $laCodEst[] = ['CCODALU' => $laTmp4[0], 'CNOMALU' => str_replace('/', ' ', $laTmp4[1]), 'CNRODNI' => $laTmp4[2], 'CEMAIL' => $laTmp4[3]];
      }
      #VALIDAR SI EXISTE DECRETO
      $lcSql = "SELECT dFecha FROM T01DDEC WHERE cIdTesi = '$laTmp1[0]' AND cEstTes = '{$this->paData['CESTTES']}'";
      $R6 = $p_oSql->omExec($lcSql);
      $laTmp5 = $p_oSql->fetch($R6);
      # DATOS DE DICTAMINADORES DE PROYECTO/PLAN DE TESIS
      $lcSql = "SELECT A.cCodDoc, B.cNombre, C.cDescri, B.cEmail, TO_CHAR(A.tDictam, 'YYYY-MM-DD'), TO_CHAR(A.tDictam, 'YYYYMMDDHH24MI'), B.cNroDni FROM T01DDOC A
                  INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc 
                  LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '140' AND SUBSTRING(C.cCodigo, 1, 1) = A.cCargo
                  WHERE A.cIdTesi = '$laTmp1[0]' AND A.cCatego ='{$this->paData['CCATEGO']}' AND A.cEstado = 'A' ORDER BY A.cCodDoc";
      $R7 = $p_oSql->omExec($lcSql);
      while ($laTmp6 = $p_oSql->fetch($R7)) {
        $laCodDoc[] = ['CCODDOC' => $laTmp6[0], 'CNOMDOC' => str_replace('/', ' ', $laTmp6[1]),  'CDESCRI' => $laFila[2], 
                       'CEMAIL'  => $laTmp6[3], 'TDICTAM' => $laTmp6[4], 'DFECHOR' => $laTmp6[5],'CNRODNI' => $laTmp6[6]];
      }
      # OBTENER FECHA DE DICTAMEN
      $lcSql = "SELECT TO_CHAR(tDictam, 'YYYY-MM-DD') FROM T01DDOC WHERE cIdTesi = '$laTmp1[0]' AND cCatego ='{$this->paData['CCATEGO']}' AND cEstado = 'A' ORDER BY tDictam DESC LIMIT 1";
      $R8 = $p_oSql->omExec($lcSql);
      $ldFecha = $p_oSql->fetch($R8);
      if ($this->paData['CESTTES'] == 'B') {
        $lcDictam = $laTmp1[0].'-A-'.$laTmp3[0].'-'.substr($ldFecha[0], 0, 4);     
      } elseif ($this->paData['CESTTES'] == 'D') {
        $lcDictam = $laTmp1[0].'-B-'.$laTmp3[0].'-'.substr($ldFecha[0], 0, 4);     
      } elseif ($this->paData['CESTTES'] == 'F') {
        $lcDictam = $laTmp1[0].'-C-'.$laTmp3[0].'-'.substr($ldFecha[0], 0, 4);     
      } elseif ($this->paData['CESTTES'] == 'I') {
        $lcDictam = $laTmp1[0].'-D-'.$laTmp3[0].'-'.substr($ldFecha[0], 0, 4);     
      }
      #ARREGLO DE DATOS
      $this->laDatos[] = ['CIDTESI' => $laTmp1[0], 'MTITULO' => $laTmp2[1], 'CUNIACA' => $laTmp2[2], 'CTIPO'   => $laTmp2[3], 
                          'CMODALI' => $laTmp2[4], 'CPREFIJ' => $laTmp2[5], 'CESPCIA' => $laTmp2[6], 'DFECHA'  => $ldFecha[0],   
                          'CSIGLA'  => $laTmp3[0], 'CNOMUNI' => $laTmp3[1], 'CNIVEL'  => $laTmp3[2], 'CDICTAM' => $lcDictam,  
                          'ACODALU' => $laCodEst,  'ACODDOC' => $laCodDoc];
      if ($this->laDatos[$i]['CIDTESI'] == ''){
        unset($this->laDatos[$i]);
      }
      $i = $i + 1;
    }
    return true;
  }
  
  protected function mxPrintReporteGenerarlDictamenesProyectoDocentePDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $lnWidth = 0; //Ancho de la celda
      $lnHeight = 0.5; //Alto de la celda
      $lnParraf = 8; //Ancho de la celda
      $lnTamLet = 10; //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new FPDF('P', 'mm', 'A4');
      foreach ($this->laDatos as $laFila) {
        $loPdf->SetMargins(20, 24, 20);
        $loPdf->AddPage('P', 'A4');
        // A partir de este codigo se escribe en el documento
        $loPdf->SetFont('times', '', 10);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', 14);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', 12);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
        } else { 
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C');
        }
        $loPdf->Ln($lnParraf);
        if ($laFila['CPREFIJ'] != 'A' and $laFila['CPREFIJ'] != '*' and !in_array($laFila['CUNIACA'], ['4A', '4E', '73'])) {
          if (in_array($laFila['CUNIACA'], ['51']) and substr($laFila['ACODALU'][0], 0, 4) <= '2000') {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
          } elseif (in_array($laFila['CUNIACA'], ['51']) and substr($laFila['ACODALU'][0], 0, 4) > '2000') {
              $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CESPCIA']), 0, 'C');
              $loPdf->Ln($lnParraf);
          } elseif (in_array($laFila['CUNIACA'], ['F6'])) {
              $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CESPCIA']), 0, 'C');
              $loPdf->Ln($lnParraf);
          } else {
              $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN ' . $laFila['CESPCIA']), 0, 'C');
              $loPdf->Ln($lnParraf);
          }
        }
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CMODALI']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE PROYECTO / PLAN'), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($laFila['DFECHA'])), 0, 'R');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'BU', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $laFila['CDICTAM']), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el proyecto / plan del expediente ' . substr($laFila['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        foreach ($laFila['ACODALU'] as $laTmp1) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp1['CCODALU'] . ' - ' . $laTmp1['CNOMALU']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['MTITULO']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
        $loPdf->Ln($lnParraf * 2);
        // Firma del Primer docente
        $loPdf->SetFont('times', 'B', 10);
        foreach ($laFila['ACODDOC'] as $laTmp2) {
          $lnTpGety = $loPdf->GetY();
          $loPdf->SetFont('times', 'B', $lnTamLet);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp2['CCODDOC'] . ' - ' . $laTmp2['CNOMDOC']), 0, 'J');
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
          $lcFile = 'FILES/R' . rand() . '.png';
          $lcCodDc = utf8_decode($laTmp2['CCODDOC'] . ' ' . $laTmp2['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laTmp2['TDICTAM']) . ' ' . $laTmp2['TDICTAM'] . ' ' . $laTmp2['DFECHOR']);
          QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
          $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
          $loPdf->Ln($lnParraf * 3);
        }
      }
      $loPdf->Output('D', $this->pcFile, true);
      return true;
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR REPORTE GENERAL DE DICTAMENES DE PROYECTO/PLAN DE DICTAMINADOR';
      return false;
    }
  }

  protected function mxPrintReporteGenerarlDictamenesAsesorDocentePDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $lnWidth = 0; //Ancho de la celda
      $lnHeight = 0.5; //Alto de la celda
      $lnParraf = 8; //Ancho de la celda
      $lnTamLet = 10; //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new FPDF('P', 'mm', 'A4');
      foreach ($this->laDatos as $laFila) {
        $loPdf->SetMargins(20, 24, 20);
        $loPdf->AddPage('P', 'A4');
        // A partir de este codigo se escribe en el documento
        $loPdf->SetFont('times', '', 10);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', 14);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, 5, utf8_decode('DECLARACIÓN DE COMPROMISO DE ASESORÍA DE TRABAJOS DE INVESTIGACIÓN, TRABAJOS ACADÉMICOS Y/O TESIS'), 0, 'C');
        $loPdf->SetFont('times', 'B', 12);
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($laFila['DFECHA'])), 0, 'R');
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, 5, utf8_decode('Mediante el presente documento doy conformidad y soy responsable de la asesoría de tesis y/o trabajo de investigación y/o trabajo académico cumpliendo las normas vigentes establecidas por la Universidad Católica de Santa María'), 0, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Título:'), 0, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['MTITULO']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Autor(es):'), 0, 'L');
        $loPdf->SetFont('times', 'B', $lnTamLet);
        foreach ($laFila['ACODALU'] as $laTmp1) {
          $loPdf->Multicell($lnWidth, 5, utf8_decode($laTmp1['CCODALU'] . ' - ' . $laTmp1['CNRODNI']), 0, 'C');
          $loPdf->Multicell($lnWidth, 5, utf8_decode($laTmp1['CNOMALU']), 0, 'C');
          $loPdf->Multicell($lnWidth, 5, utf8_decode($laTmp1['CEMAIL']), 0, 'C');
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Escuela Profesional, Segunda Especialidad, Maestría o Doctorado'), 0, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Datos del Asesor:'), 0, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        foreach ($laFila['ACODDOC'] as $laTmp2) {
          $loPdf->Multicell($lnWidth, 5, utf8_decode($laTmp2['CNRODNI']), 0, 'J');
          $loPdf->Multicell($lnWidth, 5, utf8_decode($laTmp2['CCODDOC']), 0, 'J');
          $loPdf->Multicell($lnWidth, 5, utf8_decode($laTmp2['CNOMDOC']), 0, 'J');
          $lcFile = 'FILES/R' . rand() . '.png';
          $lcCodDc = utf8_decode($laTmp2['CCODDOC'] . ' ' . $laTmp2['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laTmp2['TDICTAM']) . ' ' . $laTmp2['DFECHOR']);
          QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $lnTpGety = $loPdf->GetY();
          $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
          $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
          $loPdf->Ln($lnParraf * 4);
        }
      }
      $loPdf->Output('D', $this->pcFile, true);
      return true;
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR REPORTE GENERAL DE DICTAMENES DE PROYECTO/PLAN DE DICTAMINADOR';
      return false;
    }
  }

  protected function mxPrintReporteGenerarlDictamenesBorradorDocentePDF()
  {
    $laDatos = [];
    try {
      $loDate = new CDate;
      $lnWidth = 0; //Ancho de la celda
      $lnHeight = 0.5; //Alto de la celda
      $lnParraf = 8; //Ancho de la celda
      $lnTamLet = 10; //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new FPDF('P', 'mm', 'A4');
      foreach ($this->laDatos as $laFila) {
        $loPdf->SetMargins(20, 24, 20);
        $loPdf->AddPage('P', 'A4');
        // A partir de este codigo se escribe en el documento
        $loPdf->SetFont('times', '', 10);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', 14);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', 12);
        if ($laFila['CNIVEL'] == '03' || $laFila['CNIVEL'] == '04') {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
        } else { 
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CNOMUNI']), 0, 'C');
        }
        $loPdf->Ln($lnParraf);
        if ($laFila['CPREFIJ'] != 'A' and $laFila['CPREFIJ'] != '*' and !in_array($laFila['CUNIACA'], ['4A', '4E', '73'])) {
          if (in_array($laFila['CUNIACA'], ['51']) and substr($laFila['ACODALU'][0], 0, 4) <= '2000') {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
          } elseif (in_array($laFila['CUNIACA'], ['51']) and substr($laFila['ACODALU'][0], 0, 4) > '2000') {
              $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CESPCIA']), 0, 'C');
              $loPdf->Ln($lnParraf);
          } elseif (in_array($laFila['CUNIACA'], ['F6'])) {
              $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CESPCIA']), 0, 'C');
              $loPdf->Ln($lnParraf);
          } else {
              $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN ' . $laFila['CESPCIA']), 0, 'C');
              $loPdf->Ln($lnParraf);
          }
        }
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN DE APROBACIÓN DE BORRADOR'), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CMODALI']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, ' . $loDate->dateSimpleText($laFila['DFECHA'])), 0, 'R');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'BU', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: ' . $laFila['CDICTAM']), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el Borrador del expediente ' . substr($laFila['CDICTAM'], 0, 6) . ', presentado por:'), 0, 'J');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        foreach ($laFila['ACODALU'] as $laTmp1) {
          $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laTmp1['CCODALU'] . ' - ' . $laTmp1['CNOMALU']), 0, 'J');
          $loPdf->Ln(5);
        }
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, 5, utf8_decode($laFila['MTITULO']), 0, 'C');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', '', $lnTamLet);
        $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
        $loPdf->Ln($lnParraf);
        $loPdf->SetFont('times', 'B', $lnTamLet);
        $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
        $loPdf->Ln($lnParraf * 2);
        // Firma del Primer docente
        $loPdf->SetFont('times', 'B', 10);
        foreach ($laFila['ACODDOC'] as $laTmp2) {
          $lnTpGety = $loPdf->GetY();
          $loPdf->SetFont('times', 'B', $lnTamLet);
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode($laTmp2['CCODDOC'] . ' - ' . $laTmp2['CNOMDOC']), 0, 'J');
          $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, 4, utf8_decode("DICTAMINADOR"), 0, 'J');
          $lcFile = 'FILES/R' . rand() . '.png';
          $lcCodDc = utf8_decode($laTmp2['CCODDOC'] . ' ' . $laTmp2['CNOMBRE'] . ' ' . $loDate->dateSimpleText($laTmp2['TDICTAM']) . ' ' . $laTmp2['TDICTAM'] . ' ' . $laTmp2['DFECHOR']);
          QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
          $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
          $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 40, $lnTpGety - 10, 20, 20, 'PNG');
          $loPdf->Ln($lnParraf * 2);
        }
      }
      $loPdf->Output('D', $this->pcFile, true);
      return true;
    }
    catch (Exception $e) {
      $this->pcError = 'ERROR AL GENERAR REPORTE GENERAL DE DICTAMENES DE BORRADOR DE DICTAMINADOR';
      return false;
    }
  }
}
?>