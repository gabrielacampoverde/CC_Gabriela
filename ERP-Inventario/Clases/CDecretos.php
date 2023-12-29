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

class CDecretos extends CBase {
   public $paData, $paDatos, $paCargos , $paIdCate , $paCodUsu, $pcFile, $paAlumno, $paDocente;
   protected $lcCodCntto, $lcPaquet, $lcUltima, $lcDesCor, $lcDesAsu;

   public function __construct() {
      parent::__construct();
      $this->paData = $this->paDatos = $this->paCargos = $this->paCodUsu = null;
      $this->pcFile = 'FILES/R' . rand() . '.pdf';
   }

   // ------------------------------------------------------------------------------
   // IMPRIME DOCUMENTO DE AUTENTICACION DIPLOMA ORIGINAL
   // 2020-05-20 JLF Creacion
   // ------------------------------------------------------------------------------
   public function omGeneraDocumentoAutenticacionDiplomaOriginal($p_cTipAut) {
      $llOk = $this->mxValParamGeneraDocumentoAutenticacionDiplomaOriginal();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintGeneraDocumentoAutenticacionDiplomaOriginal($p_cTipAut);
      return $llOk;
   }
   // ------------------------------------------------------------------------------
   // IMPRIME DOCUMENTO DE DECRETOS (NOMBRAMIENTO ASESOR)(NOMBRAMIENTO JURADOS)
   // 2020-05-23 JLF Creacion
   // 2020-05-25 JFL Actualizado
   // ------------------------------------------------------------------------------
   public function omGeneraDocumentoDecretoPlanTesis($p_cTipDcr){
      $llOk = $this->mxValParamGeneraDocumentoDecretoPlanTesis();
      if (!$llOk) {
         return false;
      }
      $llOk = $this->mxPrintGeneraDocumentoDecretoPlanTesis($p_cTipDcr);
      return $llOk;
   }
   // ------------------------------------------------------------------------------
   // IMPRIME DOCUMENTO DE DICTAMENES (APROBACION DE TESIS)(APROBACION DE BORRADOR DE TESIS)(FIN DE ASESORIA)
   // 2020-05-27 JLF Creacion
   // ------------------------------------------------------------------------------
   public function omGeneraDocumentoDictamenPlanTesis($p_cTipDcr){
      $llOk = $this->mxValParamGeneraDictamenPlanTesis();
      if (!$llOk) {
         return false;
      }
      //$this->paData = $this->paData;  
      $llOk = $this->mxPrintGeneraDocumentoDictamenPlanTesis($p_cTipDcr);
      return $llOk;
   }

   protected function mxValParamGeneraDocumentoAutenticacionDiplomaOriginal() {
      //if (!isset($this->paData['CCODALU'])) {
      //   $this->pcError = "USUARIO NO DEFINIDO";
      //   return false;
      //} elseif (strlen($this->paData['CCODTRE']) != 6) {
      //   $this->pcError = "CODIGO DE TRAMITE INVALIDO";
      //}
      return true;
   }
   protected function mxValParamGeneraDocumentoDecretoPlanTesis() {
      //if (!isset($this->paData['CCODALU'])) {
      //   $this->pcError = "USUARIO NO DEFINIDO";
      //   return false;
      //} elseif (strlen($this->paData['CCODTRE']) != 6) {
      //   $this->pcError = "CODIGO DE TRAMITE INVALIDO";
      //}
      return true;
   }
   protected function mxValParamGeneraDictamenPlanTesis() {
      //if (!isset($this->paData['CCODALU'])) {
      //   $this->pcError = "USUARIO NO DEFINIDO";
      //   return false;
      //} elseif (strlen($this->paData['CCODTRE']) != 6) {
      //   $this->pcError = "CODIGO DE TRAMITE INVALIDO";
      //}
      return true;
   }

   protected function mxGeneraDocumentoAutenticacionDiplomaOriginal($p_oSql) {
      //RECUPERAR DATOS
      return true;
   }
   // ------------------------------------------------------------------------------
   // Genera documentos autenticatorios: Diploma(copia) o Constancia(copia)
   // p_cTipDoc = { 0 = "Diploma original", 1 = "Copia Diploma original", 2 = "Constancia", 3 = "Copia constancia original"}
   // ------------------------------------------------------------------------------
   protected function mxPrintGeneraDocumentoAutenticacionDiplomaOriginal($p_cTipDoc) {
      if ($p_cTipDoc == 0) {
         $lcTipDoc="Diploma";
         $lcSrcTdc=" original";
      } elseif ($p_cTipDoc == 1) {
         $lcTipDoc="copia de Diploma";
         $lcSrcTdc="";
      } elseif ($p_cTipDoc == 2) {
         $lcTipDoc="Certificado y/o Constancia";
         $lcSrcTdc=" original";
      } else {
         $lcTipDoc="copia de Certificado y/o Constancia";
         $lcSrcTdc="";
      }
      $lcDate = $this->paData['DFECHA'];        //Si se desea obtener la fecha del servidor de utiliza esto: date ("Y-m-d")
      $lcNomAlu = str_replace('/', ' ', $this->paData['CNOMALU']);
      //Configuramos y creamos el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(3, 2, 3);
      $loDate = new CDate();
      $loPdf->AddPage();
      $lnWidth = 0;                       //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Tamano del espacio del parrafo
      //A partir de este codigo escribimos en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->SetFont('times', 'B', 11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('CERTIFICACIÓN OFICIAL'), 0, 2, 'C');
      $loPdf->Ln(2*$lnParraf);
      $loPdf->SetFont('times', 'BU', 11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autenticación de '.$lcTipDoc.$lcSrcTdc), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      //Esta condicional verifica si es copia o documento original
      if ($p_cTipDoc == 0 || $p_cTipDoc == 2) {
         $loPdf->SetFont('times','',11);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La Secretaria General de la Universidad Católica de Santa María de Arequipa que suscribe, certifica:'), 0, 'J');
         $loPdf->Ln($lnParraf);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Que las autoridades que firman el '.$lcTipDoc.' escaneado, adjunto por el interesado, ejercen sus funciones en la fecha de emisión '.
                           'del indicado documento, conforme al siguiente detalle de datos:'), 0, 'J');
         $loPdf->Ln($lnParraf);
      }else {
         if ($p_cTipDoc == 1) 
            $lcTipDoc="Diploma";
         else 
            $lcTipDoc="Certificado y/o Constancia";
         $loPdf->SetFont('times','',11);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La Secretaria General de la Universidad Católica de Santa María de Arequipa que suscribe, certifica la autenticidad de la copia '.
         'escaneada del '.$lcTipDoc.', adjunta por el interesado, conforme al siguiente detalle de datos:'), 0, 'J');
         $loPdf->Ln($lnParraf);
      }
      $loPdf->SetFont('times','B',11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Información del Graduado y/o Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times','',11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nombres y Apellidos: '.$lcNomAlu), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Código del estudiante y/o egresado: '.$this->paData['CCODALU']), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Escuela Profesional/Maestría/Doctorado: '.$this->paData['CNOMUNI']), 0, 2, 'L');
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DNI: '.$this->paData['CNRODNI']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times','B',11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Información del Documento:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times','',11);
      // Esta condicional separa el documento diploma con el de la constancia
      if ($p_cTipDoc==0 || $p_cTipDoc==1) {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Grado Académico/Título Profesional: '.$this->paData['CGRATIT']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Número de Diploma UCSM: '.$this->paData['CNRODIP']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Fecha del Acto Académico: '.$this->paData['DCOLACI']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Número de Resolución: '.$this->paData['CNRORES']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Fecha de Resolución: '.$this->paData['DRESOLU']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Fecha de emisión del Diploma: '.$this->paData['DEMIDIP']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #1: '.$this->paData['CAUTOR1']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #2: '.$this->paData['CAUTOR2']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #3: '.$this->paData['CAUTOR3']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #4: '.$this->paData['CAUTOR4']), 0, 2, 'L');
         $loPdf->Ln($lnParraf);
      } else {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Tipo de documento: '.$this->paData['CTIPDOC']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Número de Certificado o Constancia: '.$this->paData['CNROCER']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #1: '.$this->paData['CAUTOR1']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #2: '.$this->paData['CAUTOR2']), 0, 2, 'L');
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autoridad #3: '.$this->paData['CAUTOR3']), 0, 2, 'L');
         $loPdf->Ln($lnParraf);
      }
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode("Arequipa, ". $loDate->dateSimpleText($lcDate)), 0, 2, 'R');
      $loPdf->Ln($lnParraf * 9);
      $loPdf->SetFont('times' , 'B', 10);
      $loPdf->SetX($loPdf->GetX() + ($loPdf->GetPageWidth() - 6)/2);
      $loPdf->Cell(($loPdf->GetPageWidth() - 6) / 2, $lnHeight, '______________________________________________', 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 6) / 2, $lnHeight, 'Dra. YESENIA MARRON MORALES', 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 6) / 2, $lnHeight, 'SECRETARIA GENERAL', 0, 2, 'C');
      $loPdf->Cell(($loPdf->GetPageWidth() - 6) / 2, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->SetX(3);
      $loPdf->AddPage();
      $loPdf->SetFont('times', 'B', 11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Observaciones:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', 11);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['COBSERV']), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', 11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Instrucciones para la impresión:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetX(3.5);
      $loPdf->SetFont('times', '', 11);
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Imprimir en una misma hoja (anverso y reverso), de lo contrario el documento será inválido."), 0, 'J');
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("La impresión debe ser en papel bond A-4, de color blanco, sin insertar trama o fondo no autorizado por el emisor."), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetX(3);
      $loPdf->SetFont('times', 'B', 11);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Este documento será materia de cancelación en los siguientes supuestos:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetX(3.5);
      $loPdf->SetFont('times', '', 11);
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por alterar o adulterar el documento original, cuya copia ha sido autenticada."), 0, 'J');
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por cambio de nombre y/o apellidos del titular del documento original, cuya copia ha sido autenticada."), 0, 'J');
      if ($p_cTipDoc == 1 || $p_cTipDoc == 3) {
         $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por haber expirado la vigencia de la certificación."), 0, 'J');
      }
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por imprimir, de ser el caso, la autenticación sin observar las formalidades establecidas por el emisor."), 0, 'J');
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por pérdida del original o su deterioro que haga imposible la lectura o verificación de su contenido, que deberá comunicar el interesado al emisor bajo responsabilidad, y quien queda obligado a no hacer uso de la autenticación en tales supuestos."), 0, 'J');
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por decisión del emisor, mediando causal de nulidad."), 0, 'J');
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por solicitud del interesado titular de la autenticación."), 0, 'J');
      $loPdf->MultiCellBlt($loPdf->GetPageWidth() - 6.5, $lnHeight, '-', utf8_decode("Por mandato de la Autoridad Administrativa Competente, Judicial o Legal."), 0, 'J');
      $loPdf->Ln($lnParraf);
      if ($p_cTipDoc == 1 || $p_cTipDoc == 3) {
         $loPdf->SetX(3);
         $loPdf->SetFont('times', '', 11);
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Este documento tiene un plazo de vigencia de seis meses (desde la fecha de su emisión).'), 0, 2, 'L');
         $loPdf->Ln($lnParraf);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }
   
   // ------------------------------------------------------------------------------
   // Genera documentos decretorios: Nombamiento de jurados y asesor
   // p_cTipDoc = { 1 = Nombramiento de jurados, 2 = nombramiento de asesor}
   // ------------------------------------------------------------------------------
   protected function mxPrintGeneraDocumentoDecretoPlanTesis($p_cTipDcr) {
      //Fecha parámetro, se puede coger la fecha del servidor con lo siguiente date("Y-m-d");
      $lcDate = $this->paData['DFECHA'];
      $loDate = new CDate();
      //A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($lcDate).' '.substr(str_shuffle('0123456789'), 0, 6));
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($lcDate).' '.substr(str_shuffle('0123456789'), 0, 6));
      QRcode::png(utf8_encode($lcCodDir), "FILES/qrDir.png", QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDec), "FILES/qrDec.png", QR_ECLEVEL_L, 4, 0, false);
      //A partir de este codigo configuro el documento PDF
      $lnWidth = 0;                       //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait','cm','A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(3, 2, 3);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times','B',14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      if ($p_cTipDcr == 1)
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DE JURADOS'), 0, 'C');
      elseif ($p_cTipDcr == 2) 
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DE ASESOR'), 0, 'C');
      else 
         return false;
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nº'.$this->paData['CDECRET']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto....'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SE DECRETA'), 0, 2, 'C');
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la recepción de Previas orales de (el)(la)(los) Bachiller(es)'), 0, 2, '');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CALUMN1']), 0, 'J');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CALUMN2']), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      if ($p_cTipDcr == 1) {
         $loPdf->SetFont('times', '', $lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose el jurado examinador, el mismo que estará integrado por Profesores de la escuela Profesional de Ingeniería de Sistemas'), 0, 'J');
         $loPdf->SetFont('times', 'B', $lnTamLet );
         $loPdf->Ln($lnParraf);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CDOCEN1']), 0, 'J');
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CDOCEN2']), 0, 'J');
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CDOCEN3']), 0, 'J');
         $loPdf->Ln($lnParraf);
         $loPdf->SetFont('times', '', $lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación de las Tesis Titulada:'), 0, 'J');
         $loPdf->Ln($lnParraf);
      }else {
         $loPdf->SetFont('times', '', $lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose al asesor, mismo que es un Profesor de la escuela Profesional de Ingeniería de Sistemas'), 0, 'J');
         $loPdf->SetFont('times', 'B', $lnTamLet );
         $loPdf->Ln($lnParraf);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CASESOR']), 0, 'J');
         $loPdf->Ln($lnParraf);
         $loPdf->SetFont('times', '', $lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar (al)(los) Bachilleres en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
         $loPdf->Ln($lnParraf);
      }
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CTITULO']), 0, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet );
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El '.$this->paData['CCARDEC'].' la Dirección y Secretaría de la Escuela Profesional de Ingeniería de Sistemas se encargarán del cumplimiento del presente, registrese y comuniquese'), 0, 'J');
      $loPdf->Ln($lnParraf * 3);
      //Generacion de la firma del Director de la escuela profesional
      $lnTpGety = $loPdf->GetY();
      $loPdf->SetFont('times', '', $lnTamLet );
      $loPdf->Multicell($loPdf->GetPageWidth() - 6-2.5, $lnHeight, utf8_decode($this->paData['CDIRECT']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 6-2.5, $lnHeight, utf8_decode($this->paData['CCARDIR']), 0, 'J');
      $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
      $loPdf->Image("FILES/qrDir.png" , $loPdf->GetPageWidth() - 6-2+3, $lnTpGety-1, 2, 2, 'PNG');
      $loPdf->Ln($lnParraf * 3);
      //Generacion de la firma del Decano de la facultad
      $lnTpGety=$loPdf->GetY();
      $loPdf->SetFont('times','',10);
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDECANO']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CCARDEC']), 0, 'L');
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image("FILES/qrDec.png" , $loPdf->GetPageWidth() - 6-2+3, $lnTpGety-1, 2, 2, 'PNG');
      //Export del documento en el archivo seleccionado em la funcion construct
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }
   
   // ------------------------------------------------------------------------------
   // Genera documentos dictaminatorios: Aprobacion de tesis y borrador, fin de asesoria
   // p_cTipAut = { APT = Aprobacion de tesis, ABT = Aprobacion borrado de tesis, FAS = Fin de asesoria}
   // ------------------------------------------------------------------------------
   protected function mxPrintGeneraDocumentoDictamenPlanTesis($p_cTipAut) {
      //Fecha parámetro, se puede coger la fecha del servidor con lo siguiente date("Y-m-d");
      $lcDate = $this->paData['DFECHA'];
      $loDate = new CDate();
      //A partir de este apartado generó el código QR para las firmas del documento
      $qrCodeDoc1 = utf8_decode($this->paData['CDOCEN1'].' '.$loDate->dateSimpleText($lcDate).' '.substr(str_shuffle('0123456789'), 0, 6));
      $qrCodeDoc2 = utf8_decode($this->paData['CDOCEN2'].' '.$loDate->dateSimpleText($lcDate).' '.substr(str_shuffle('0123456789'), 0, 6));
      $qrCodeAse = utf8_decode($this->paData['CASESOR'].' '.$loDate->dateSimpleText($lcDate).' '.substr(str_shuffle('0123456789'), 0, 6));
      QRcode::png(utf8_encode($qrCodeDoc1), "FILES/qrDoc1.png", QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($qrCodeDoc2), "FILES/qrDoc2.png", QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($qrCodeAse), "FILES/qrAse.png", QR_ECLEVEL_L, 4, 0, false);
      //A partir de este codigo configuro el documento PDF
      $lnWidth = 0;                       //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait','cm','A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(3, 2, 3);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times','B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times','B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('FACULTAD DE CIENCIAS FÍSICAS E INFORMALES'), 0, 'C');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE INGENIERÍA DE SISTEMAS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'BU', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('INFORME DICTÁMEN DE '.$this->paData['CTIPDOC']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, 'VISTO', 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      //Valido el tipo de documento, si el tipo no existe entonces el proceso acaba
      if ($p_cTipAut == 'ABT')
         $loPdf->Cell($lnWidth, $lnHeight, 'El Borrador de tesis titulado:', 0, 2, 'L');
      elseif ($p_cTipAut == 'APT') 
         $loPdf->Cell($lnWidth, $lnHeight, 'La tesis titulada:', 0, 2, 'L');
      elseif ($p_cTipAut == 'FAS') 
         $loPdf->Multicell($lnWidth, $lnHeight, 'Se da por culminda la tesis titulada:', 0, 'L');
      else 
         return false;
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CTITULO']), 0, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, 'Presentado por (el)(la)(los) Bachiller(es):', 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CALUMN1']), 0, 'L');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CALUMN2']), 0, 'L');
      $loPdf->Ln($lnParraf);
      //Valido el tipo de documento
      if ($p_cTipAut == 'ABT' || $p_cTipAut == 'APT') {
         $loPdf->SetFont('times', 'B', $lnTamLet);
         $loPdf->Cell($lnWidth, $lnHeight, 'Nestro dictamen es:', 0, 2, 'L');
         $loPdf->Ln($lnParraf);
         $loPdf->SetFont('times', '', $lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CDICTAM']), 0, 'L');
         $loPdf->Ln($lnParraf);
         /*$loPdf->SetFont('times','B',$lnTamLet);
         $loPdf->Cell($lnWidth, $lnHeight, 'Observaciones:', 0, 2, 'L');
         $loPdf->Ln($lnParraf);
         $loPdf->SetFont('times','',$lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['COBSERV']), 0, 'L');*/
      } else {
         $loPdf->SetFont('times', 'B', $lnTamLet);
         $loPdf->Cell($lnWidth, $lnHeight, 'Asesorado por:', 0, 2, 'L');
         $loPdf->Ln($lnParraf);
         $loPdf->SetFont('times', '', $lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CASESOR']), 0, 'L');
         $loPdf->Ln($lnParraf);
         /*$loPdf->SetFont('times','B',$lnTamLet);
         $loPdf->Cell($lnWidth, $lnHeight, 'El asesor recomienda (a)(los) Bachilleres lo siguiente:', 0, 2, 'L');
         $loPdf->Ln($lnParraf);
         $loPdf->SetFont('times','',$lnTamLet);
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['COBSERV']), 0, 'L');*/
      }
      $loPdf->Ln($lnParraf * 2);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode("Arequipa, ". $loDate->dateSimpleText($lcDate)), 0, 2, 'R');
      $loPdf->Ln($lnParraf * 6);
      //Valido el tipo de documento para crear las firmas del dpocumento
      if ($p_cTipAut=='ABT' || $p_cTipAut=='APT'){
         //Firma del Docente 1
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', '', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDOCEN1']), 0, 'J');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image("FILES/qrDoc1.png" , $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 6);
         //Firma del Docente 2
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', '', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDOCEN2']), 0, 'J');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image("FILES/qrDoc2.png" , $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
      }else{
         //Firma del asesor
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', '', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CASESOR']), 0, 'J');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image("FILES/qrAse.png" , $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG'); 
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   // ------------------------------------------------------------------------------
   // Genera decreto de jurado evaluador de tesis
   // 2020-05-29 AACH Creacion
   // ------------------------------------------------------------------------------
   public function omDecretoJuradoTesis() {
      $llOk = $this->mxValParamDecretoJuradoTesis();
      if (!$llOk) {
         return false;
      }
      if ($this->paData['CNIVEL'] == '01') 
         $llOk = $this->mxDecretoJuradoTesisPreGrado(); 
      elseif ($this->paData['CNIVEL'] == '03' or $this->paData['CNIVEL'] == '04') 
         $llOk = $this->mxDecretoJuradoTesisPostGrado(); 
      if (!$llOk) {
         return false;
      }
      return $llOk;
   }

   protected function mxValParamDecretoJuradoTesis() {
      $loDate = new CDate;
      if (!isset($this->paData['CALUMN1'])) {
         $this->pcError = "ALUMNO 1 NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDIRECT'])) {
         $this->pcError = "DIRECTOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDECANO'])) {
         $this->pcError = "DECANO NO DEFINIDO";
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFECHA'])) {
         $this->pcError = "FECHA INVALIDA O NO DEFINIDA";
         return false;
      }
      return true;
   }

   

   

   // ------------------------------------------------------------------------------
   // Genera decreto de jurado dictaminador de borrador de tesis
   // 2020-05-27 AACH Creacion
   // 2020-05-28 FPM  Adecuacion del decreto
   // ------------------------------------------------------------------------------
   public function omDecretoBorradorTesis() {
      /*$llOk = $this->mxValParamDecretoBorradorTesis();
      if (!$llOk) {
         return false;
      }*/
      $llOk = $this->mxDecretoBorradorTesis();
      return $llOk;
   }

   protected function mxValParamDecretoBorradorTesis() {
      $loDate = new CDate;
      if (!isset($this->paData['CALUMN1'])) {
         $this->pcError = "ALUMNO 1 NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDIRECT'])) {
         $this->pcError = "DIRECTOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDECANO'])) {
         $this->pcError = "DECANO NO DEFINIDO";
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFECHA'])) {
         $this->pcError = "FECHA INVALIDA O NO DEFINIDA";
         return false;
      }
      return true;
   }

   

   // ------------------------------------------------------------------------------
   // Genera decreto de jurado dictaminador de borrador de tesis
   // 2020-05-27 AACH Creacion
   // 2020-05-28 FPM  Adecuacion del decreto
   // ------------------------------------------------------------------------------
   public function omDecretoPlanTesis() {
      /*$llOk = $this->mxValParamDecretoBorradorTesis();
      if (!$llOk) {
         return false;
      }*/
      $llOk = $this->mxDecretoPlanTesis();
      return $llOk;
   }

   // ------------------------------------------------------------------------------
   // Genera decreto de jurado dictaminador de borrador de tesis
   // 2020-05-27 AACH Creacion
   // 2020-05-28 FPM  Adecuacion del decreto
   // ------------------------------------------------------------------------------
   public function omDecretoAsesorTesis() {
      /*$llOk = $this->mxValParamDecretoBorradorTesis();
      if (!$llOk) {
         return false;
      }*/
      $llOk = $this->mxDecretoAsesorTesis();
      return $llOk;
   }


   ##-----------------------------
   // ------------------------------------------------------------------------------
   // Genera dictamen aprobacion plan/proyecto de tesis
   // 2020-06-02 AACH Creacion
   // ------------------------------------------------------------------------------
   public function omDictamenAprobacionPDT() {
      /*$llOk = $this->mxValParamDictamenAprobacionPDT();
      if (!$llOk) {
         return false;
      }*/
      $llOk = $this->mxDictamenAprobacionPDT();
      return $llOk;
   }

   protected function mxValParamDictamenAprobacionPDT() {
      $loDate = new CDate;
      if (!isset($this->paData['CALUMN1'])) {
         $this->pcError = "ALUMNO 1 NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CTITULO'])) {
         $this->pcError = "TITULO DE LA TESIS NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDOCEN1'])) {
         $this->pcError = "PRIMER DICTAMINADOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDOCEN2'])) {
         $this->pcError = "SEGUNDO DICTAMINADOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDICTAM'])) {
         $this->pcError = "ID DE DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CNOMUNI'])) {
         $this->pcError = "NOMBRE DE UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CRESULT'])) {
         $this->pcError = "RESULTADO DEL DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['DFECHOR'])) {
         $this->pcError = "FECHA Y HORA DE GENERACION DE PDF DE DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFECHA'])) {
         $this->pcError = "FECHA INVALIDA O NO DEFINIDA";
         return false;
      }
      return true;
   }

   

   // ------------------------------------------------------------------------------
   // Genera documento fin de asesoria
   // 2020-06-02 AACH Creacion
   // ------------------------------------------------------------------------------
   public function omDictamenFinAsesoria() {
      /*$llOk = $this->mxValParamDictamenFinAsesoria();
      if (!$llOk) {
         return false;
      }*/
      $llOk = $this->mxDictamenFinAsesoria();
      return $llOk;
   }

   protected function mxValParamDictamenFinAsesoria() {
      $loDate = new CDate;
      if (!isset($this->paData['CALUMN1'])) {
         $this->pcError = "ALUMNO 1 NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CTITULO'])) {
         $this->pcError = "TITULO DE LA TESIS NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDOCEN1'])) {
         $this->pcError = "PRIMER DICTAMINADOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDICTAM'])) {
         $this->pcError = "ID DE DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CNOMUNI'])) {
         $this->pcError = "NOMBRE DE UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CRESULT'])) {
         $this->pcError = "RESULTADO DEL DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['DFECHOR'])) {
         $this->pcError = "FECHA Y HORA DE GENERACION DE PDF DE DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFECHA'])) {
         $this->pcError = "FECHA INVALIDA O NO DEFINIDA";
         return false;
      }
      return true;
   }

   
  
   // ------------------------------------------------------------------------------
   // Genera dictamen borrador de tesis
   // 2020-06-02 AACH Creacion
   // ------------------------------------------------------------------------------
   public function omDictamenBorradorTesis() {
      /*$llOk = $this->mxValParamDictamenBorradorTesis();
      if (!$llOk) {
         return false;
      }*/
      $llOk = $this->mxDictamenBorradorTesis();
      return $llOk;
   }

   protected function mxValParamDictamenBorradorTesis() {
      $loDate = new CDate;
      if (!isset($this->paData['CALUMN1'])) {
         $this->pcError = "ALUMNO 1 NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CTITULO'])) {
         $this->pcError = "TITULO DE LA TESIS NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDOCEN1'])) {
         $this->pcError = "PRIMER DICTAMINADOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDOCEN2'])) {
         $this->pcError = "SEGUNDO DICTAMINADOR NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CNOMUNI'])) {
         $this->pcError = "NOMBRE DE UNIDAD ACADEMICA NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CDICTAM'])) {
         $this->pcError = "ID DE DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['CRESULT'])) {
         $this->pcError = "RESULTADO DEL DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!isset($this->paData['DFECHOR'])) {
         $this->pcError = "FECHA Y HORA DE GENERACION DE PDF DE DICTAMEN NO DEFINIDO";
         return false;
      } elseif (!$loDate->mxValDate($this->paData['DFECHA'])) {
         $this->pcError = "FECHA INVALIDA O NO DEFINIDA";
         return false;
      }
      return true;
   }

   

   //----------------------------------------------------------------------------------------
   // GENERA EL DOCUMENTO SOLICITADO DECRETOS DE TESIS
   // 2020-06-20 FLC
   //----------------------------------------------------------------------------------------

   public function omDecretos(){
      if ($this->paData['CESTTES'] == 'B') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDecretoPlanTesisPostGrado();
         } else {
            $this->mxDecretoPlanTesis();
         }
      } elseif ($this->paData['CESTTES'] == 'D') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDecretoAsesorTesisPostGrado();
         } else {
            $this->mxDecretoAsesorTesis();
         }
      } elseif ($this->paData['CESTTES'] == 'F') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDecretoBorradorTesisPostGrado();
         } else {
            $this->mxDecretoBorradorTesis();
         }
      } elseif ($this->paData['CESTTES'] == 'I') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDecretoJuradoTesisPostGrado();
         } else {
            $this->mxDecretoJuradoTesisPreGrado(); 
         }
      } else {
         $this->pcError = "DECRETO NO DISPONIBLE";
         return false;
      }
      return true;
   }

   protected function mxDecretoPlanTesis() {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C'); 
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DICTAMINADORES DE PROYECTO / PLAN'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.$this->paData['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan de la Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabjo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto / Plan del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose al jurado dictaminador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Trabajo de Investigación titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Trabajo Informe titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan de Tesis titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Trabajo Informe titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar la Balota Sorteada titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Expediente titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan de Tesis titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan del Trabajo Académico titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto / Plan del Trabajo de Investigación titulado:'), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if (in_array($this->paData['CNIVEL'], ['03','04'])){
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      } else {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El Decanato, la Dirección y Secretaría de la Escuela se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      }
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      $lnTpGety=$loPdf->GetY();
      $loPdf->SetFont('times', 'B', 10);
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDECANO']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CCARDEC']), 0, 'L');
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
      $loPdf->Ln($lnParraf * 4);
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
            $lnTpGety = $loPdf->GetY();
            $loPdf->SetFont('times', 'B', $lnTamLet);
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
            $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
            $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoPlanTesisPostGrado() {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DICTAMINADORES DE PROYECTO DE TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.$this->paData['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos respectivos  del Estatuto de la Universidad Católica de Santa María y el Reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $lcTipAlu = ($this->paData['CNIVEL'] == '03')? 'de (el)(la)(los)  Bachiller(s)' : 'del Maestro';
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto de Tesis '.$lcTipAlu.':'), 0, 2, '');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose al jurado dictaminador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Proyecto de Tesis titulado:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      if(substr($this->paData['CDECANO'],0,4) != '0000'){
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', 'B', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDECANO']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CCARDEC']), 0, 'L');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
         $lnTpGety = $loPdf->GetY();
         $loPdf->SetFont('times', 'B', $lnTamLet);
         $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
         $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
         $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoAsesorTesis() {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C'); 
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DE ASESOR'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.$this->paData['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de la Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose como asesor al docente:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo de Investigación titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo Informe titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo Informe titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Balota Sorteada titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Expediente titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo Académico titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración del Trabajo de Investigación titulado:'), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El Decanato, la Dirección y Secretaría de la Escuela se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      $lnTpGety=$loPdf->GetY();
      $loPdf->SetFont('times', 'B', 10);
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDECANO']), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CCARDEC']), 0, 'L');
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
      $loPdf->Ln($lnParraf * 4);
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
            $lnTpGety = $loPdf->GetY();
            $loPdf->SetFont('times', 'B', $lnTamLet);
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
            $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
            $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoAsesorTesisPostGrado() {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DE ASESOR DE TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.$this->paData['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos respectivos  del Estatuto de la Universidad Católica de Santa María y el Reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar el nombramiento de Asesor de Tesis.'), 0, 2, '');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose como asesor al docente:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El asesor designado en el párrafo anterior se encargará de guiar en el proceso de la elaboración de la Tesis titulada:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      if(substr($this->paData['CDECANO'],0,4) != '0000'){
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', 'B', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CDECANO']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode($this->paData['CCARDEC']), 0, 'L');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
         $lnTpGety = $loPdf->GetY();
         $loPdf->SetFont('times', 'B', $lnTamLet);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
         $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
         $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoBorradorTesis() {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C'); 
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DICTAMINADORES DE BORRADOR'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.$this->paData['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador de Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Borrador del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose al jurado dictaminador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Trabajo de Investigación titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo Informe titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Tesis titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo Informe titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de la Balota Sorteada titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Expediente titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador de Tesis titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo Académico titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designados en el párrafo anterior, se encargarán de evaluar el Borrador del Trabajo de Investigación titulado:'), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El Decanato, la Dirección y Secretaría de la Escuela se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      $lnTpGety=$loPdf->GetY();
      $loPdf->SetFont('times', 'B', 10);
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDECANO'])), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDEC'])), 0, 'L');
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
      $loPdf->Ln($lnParraf * 4);
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
            $lnTpGety = $loPdf->GetY();
            $loPdf->SetFont('times', 'B', $lnTamLet);
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
            $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
            $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoBorradorTesisPostGrado() {
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO DICTAMINADORES DE BORRADOR DE TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.$this->paData['CIDTESI'].' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos respectivos  del Estatuto de la Universidad Católica de Santa María y el Reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $lcTipAlu = ($this->paData['CNIVEL'] == '03')? 'de (el)(la)(los)  Bachiller(s)' : 'del Maestro';
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la revisión del Proyecto de Tesis '.$lcTipAlu.':'), 0, 2, '');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose al jurado dictaminador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Los dictaminadores, designado en el párrafo anterior, se encargarán de evaluar el Borrador de Tesis titulado:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La Dirección y Secretaría de la Escuela de Postgrado se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      if(substr($this->paData['CDECANO'],0,4) != '0000'){
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', 'B', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDECANO'])), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDEC'])), 0, 'L');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      // Firma del director de la escuela profesiltonal
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
         $lnTpGety = $loPdf->GetY();
         $loPdf->SetFont('times', 'B', $lnTamLet);
         $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
         $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
         $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoJuradoTesisPreGrado() {
      //$loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO JURADO DE EVALUACIÓN DE TESIS'), 0, 'C');
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      //$loPdf->SetMargins(3, 2, 3);
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', 'B', 8);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO JURADO DE EVALUACIÓN DE SUSTENTACIÓN'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.substr($this->paData['CDECRET'],0,6).' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos 57 y siguientes del Estatuto de la Universidad Católica de Santa María y el reglamento de la Facultad, en uso de las facultades concedidas, se decreta:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion del Trabajo de Investigación de (el)(la)(los) Egresado(a)(s):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion de la Tesis de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion del Trabajo Informe de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion de la Balota Sorteada de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion del Expediente de (el)(la)(los) Bachiller(es):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion de la Tesis de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion del Trabajo Académico de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la Sustentacion del Trabajo de Investigación de (el)(la)(los) Titulado(a)(s)(as):'), 0, 2, '');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose el jurado examinador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J');
      $loPdf->SetFont('times', 'B', $lnTamLet );
      $loPdf->Ln($lnParraf);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CCARGO'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('LEl jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación del Trabajo de Investigación Titulado: '), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación del Trabajo Informe Titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación de la Tesis Titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación del Trabajo Informe Titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación de la Balota Sorteada titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación del Expediente titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S0') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación de la Tesis titulada:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S1') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación del Trabajo Académico titulado:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'S2') {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación del Trabajo de Investigación titulado:'), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $this->paData['MTITULO'] = str_replace('–', '-', $this->paData['MTITULO']);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La recepción de previas, motivo del presente, deberá realizarse en acto público el día '.$loDate->dateSimpleText($this->paData['TDIASUS']).' a las '.substr($this->paData['TDIASUS'], 11,5).' horas, en el salón virtual.'), 0, 'J');
      $loPdf->SetFont('times', '', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Las respectivas autoridades de la Escuela y/o de la Facultad se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Decano de la facultad
      $lnTpGety=$loPdf->GetY();
      $loPdf->SetFont('times', 'B', 10);
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDECANO'])), 0, 'J');
      $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDEC'])), 0, 'L');
      $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
      $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
      $loPdf->Ln($lnParraf * 4);
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
            $lnTpGety = $loPdf->GetY();
            $loPdf->SetFont('times', 'B', $lnTamLet);
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
            $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
            $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
            $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      // Genera documento pdf
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDecretoJuradoTesisPostGrado() { 
      $loDate = new CDate;
      $lcFile1 = 'FILES/R'.rand().'.png';
      $lcFile2 = 'FILES/R'.rand().'.png';
      // A partir de este apartado generó el código QR para las firmas del documento
      $lcCodDir = utf8_decode($this->paData['CDIRECT'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      $lcCodDec = utf8_decode($this->paData['CDECANO'].' '.$loDate->dateSimpleText($this->paData['DFECHA']).' '.$this->paData['CDECRET'].' '.$this->paData['DFECHOR']);
      QRcode::png(utf8_encode($lcCodDec), $lcFile1, QR_ECLEVEL_L, 4, 0, false);
      QRcode::png(utf8_encode($lcCodDir), $lcFile2, QR_ECLEVEL_L, 4, 0, false);
      // A partir de este codigo configuro el documento PDF 
      $lnWidth  = 0;                      //Ancho de la celda 
      $lnHeight = 0.5;                    //Alto de la celda 
      $lnParraf = 0.35;                   //Ancho de la celda 
      $lnTamLet = 10;                     //Ancho de la celda 
      //A partir de esta linea creo el documento PDF 
      $loPdf = new PDF('portrait', 'cm', 'A4'); 
      $loPdf->AliasNbPages(); 
      //$loPdf->SetMargins(3, 2, 3); 
      $loPdf->SetMargins(2, 1, 2); 
      $loPdf->AddPage(); 
      //A partir de este codigo se escribe en el documento 
      $loPdf->SetFont('times', 'B', 8); 
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L'); 
      $loPdf->SetFont('times', 'B', 14); 
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times' ,'B', 12); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECRETO DE NOMBRAMIENTO JURADO DE EVALUACIÓN DE TESIS'), 0, 'C'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times' ,'', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R'); 
      $loPdf->SetFont('times' ,'BU' , $lnTamLet); 
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('DECRETO: Nro. '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times' ,'', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.substr($this->paData['CDECRET'],0,6).' presentado y en concordancia con lo dispuesto por la Ley 30220, los artículos respectivos  del Estatuto de la Universidad Católica de Santa María y el Reglamento de Grados y Títulos de la Escuela de Postgrado, se decreta:'), 0, 'J'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times', 'B', $lnTamLet); 
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('PRIMERO'), 0, 2, 'C'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times', '', $lnTamLet);
      if ($this->paData['CNIVEL'] == '03') {  
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la sustentación de la Tesis del(a)(os) estudiante(s) de Maestría:'), 0, 2, '');
      }
      else {  
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Autorizar la sustentación de la Tesis del(a)(os) estudiante(s) de Doctorado:'), 0, 2, '');
      } 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times' ,'B' , $lnTamLet); 
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'J');  
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times' ,'B' , $lnTamLet); 
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('SEGUNDO'), 0, 2, 'C'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times', '', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Designándose el jurado examinador, el mismo que estará integrado por los siguientes docentes:'), 0, 'J'); 
      $loPdf->SetFont('times', 'B', $lnTamLet ); 
      $loPdf->Ln($lnParraf); 
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CCARGO'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times', '', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('El jurado examinador, designado en el párrafo anterior se encargará de evaluar la sustentación de la Tesis Titulada:'), 0, 'J'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times', 'B', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times' ,'B' , $lnTamLet); 
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('TERCERO'), 0, 2, 'C'); 
      $loPdf->Ln($lnParraf); 
      $loPdf->SetFont('times', 'B', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('La recepción de previas, motivo del presente, deberá realizarse en acto público el día '.$loDate->dateSimpleText($this->paData['TDIASUS']).' a las '.substr($this->paData['TDIASUS'], 11,5).' horas, en el salón virtual.'), 0, 'J'); 
      $loPdf->SetFont('times', '', $lnTamLet); 
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Las respectivas autoridades de la Escuela y/o de la Facultad se encargarán del cumplimiento del presente, regístrese y comuníquese.'), 0, 'J'); 
      $loPdf->Ln($lnParraf * 3); 
      // Firma del Decano de la facultad
      if(substr($this->paData['CDECANO'],0,4) != '0000'){
         $lnTpGety=$loPdf->GetY();
         $loPdf->SetFont('times', 'B', 10);
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDECANO'])), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDEC'])), 0, 'L');
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile1, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      // Firma del director de la escuela profesional
      if(substr($this->paData['CDIRECT'],0,4) != '0000'){
         $lnTpGety = $loPdf->GetY();
         $loPdf->SetFont('times', 'B', $lnTamLet);
         $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CDIRECT'])), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 6 - 2.5, $lnHeight, utf8_decode(strtoupper($this->paData['CCARDIR'])), 0, 'J');
         $lnTpGety = $lnTpGety + (( $loPdf->GetY() - $lnTpGety ) / 2);
         $loPdf->Image($lcFile2, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety-1, 2, 2, 'PNG');
      }
      $loPdf->Ln($lnParraf * 4); 
      $loPdf->Output('F', $this->pcFile, true); 
      return true; 
   }

   //----------------------------------------------------------------------------------------
   // GENERA EL DOCUMENTO SOLICITADO DICTAMEN DE TESIS
   // 2020-06-20 FLC
   //----------------------------------------------------------------------------------------
   
   public function omDictamenes(){
      if ($this->paData['CESTTES'] == 'C') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDictamenPlanTesisPostGrado();
         } else {
            $this->mxDictamenPlanTesis();
         }
      } elseif ($this->paData['CESTTES'] == 'E') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDictamenFinAsesoriaPostGrado();
         } else {
            $this->mxDictamenFinAsesoria();
         }
      } elseif ($this->paData['CESTTES'] == 'G') {
         if (in_array($this->paData['CNIVEL'], ['03','04'])){
            $this->mxDictamenBorradorTesisPostGrado();
         } else {
            $this->mxDictamenBorradorTesis();
         }
      } else {
         $this->pcError = "DICTAMEN NO DISPONIBLE".$this->paData;
         return false;
      }
      return true;
   }

   protected function mxDictamenPlanTesis() {
      $lcAluCod = substr($this->paAlumno[0]['CCODALU'], 0, 4);
      $loDate = new CDate;
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', '', 10);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      if ($this->paData['CPREFIJ'] != 'A' AND $this->paData['CPREFIJ'] != '*' AND !in_array($this->paData['CUNIACA'], ['4A','4E','73'])) {
         if (in_array($this->paData['CUNIACA'], ['51']) AND $lcAluCod <= '2000'){
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         } elseif (in_array($this->paData['CUNIACA'], ['51']) AND $lcAluCod > '2000'){
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         } elseif (in_array($this->paData['CUNIACA'], ['F6'])){
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         } else {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN '.$this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         }
      }
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DEL PLAN / PROYECTO'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      if ($this->paData['CTIPO'] == 'B0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto / Plan del expediente '.substr($this->paData['CDICTAM'],0,6).' para obtener el Grado de Bachiller, presentado por:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T0') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto / Plan del expediente '.substr($this->paData['CDICTAM'],0,6).' para obtener el Título Profesional, presentado por:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T1') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto / Plan del expediente '.substr($this->paData['CDICTAM'],0,6).' para obtener el Título Profesional, presentado por:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T2') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto / Plan del expediente '.substr($this->paData['CDICTAM'],0,6).' para obtener el Título Profesional, presentado por:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T3') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto / Plan del expediente '.substr($this->paData['CDICTAM'],0,6).' para obtener el Título Profesional, presentado por:'), 0, 'J');
      } elseif ($this->paData['CTIPO'] == 'T4') {
         $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto / Plan del Expediente '.substr($this->paData['CDICTAM'],0,6).' para obtener el Título Profesional, presentado por:'), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
      //$loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Primer docente
      
      $loPdf->SetFont('times', 'B', 10);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode("DICTAMINADOR"), 0, 'J');
         $lcFile = 'FILES/R'.rand().'.png';
         $lcCodDc = utf8_decode($laFila['CCODDOC'].' '.$laFila['CNOMBRE'].' '.$loDate->dateSimpleText($laFila['TDICTAM']).' '.$laFila['DFECHOR']);
         QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety=$loPdf->GetY();
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDictamenPlanTesisPostGrado() {
      $loDate = new CDate;
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      //A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', '', 10);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DEL PLAN - PROYECTO DE TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el Proyecto de tesis del expediente '.substr($this->paData['CDICTAM'],0,6).', presentado por:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
      //$loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CRESULT']), 0, 'C');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Primer docente
      
      $loPdf->SetFont('times', 'B', 10);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode("DICTAMINADOR"), 0, 'J');
         $lcFile = 'FILES/R'.rand().'.png';
         $lcCodDc = utf8_decode($laFila['CCODDOC'].' '.$laFila['CNOMBRE'].' '.$loDate->dateSimpleText($laFila['TDICTAM']).' '.$laFila['DFECHOR']);
         QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety=$loPdf->GetY();
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDictamenFinAsesoria() {
      $loDate = new CDate;
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      // A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', '', 10);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECLARACIÓN DE COMPROMISO DE ASESORÍA DE TRABAJOS DE INVESTIGACIÓN, TRABAJOS ACADÉMICOS Y/O TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.substr($this->paData['CDICTAM'],0,6).', presentado por:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Después de realizada la asesoría, el dictamen del asesor es: '), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
      $loPdf->Ln($lnParraf * 3);
      // Firma del asesor
      $loPdf->SetFont('times', 'B', 10);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode("ASESOR"), 0, 'J');
         $lcFile = 'FILES/R'.rand().'.png';
         $lcCodDc = utf8_decode($laFila['CCODDOC'].' '.$laFila['CNOMBRE'].' '.$loDate->dateSimpleText($laFila['TDICTAM']).' '.$laFila['DFECHOR']);
         QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety=$loPdf->GetY();
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDictamenFinAsesoriaPostGrado() {
      $loDate = new CDate;
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      //A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      // A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', '', 10);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DECLARACIÓN DE COMPROMISO DE ASESORÍA DE TRABAJOS DE INVESTIGACIÓN, TRABAJOS ACADÉMICOS Y/O TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el expediente '.substr($this->paData['CDICTAM'],0,6).', presentado por:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Después de realizada la asesoría, el dictamen del asesor es: '), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
      $loPdf->Ln($lnParraf * 3);
      // Firma del asesor
      $loPdf->SetFont('times', 'B', 10);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode("ASESOR"), 0, 'J');
         $lcFile = 'FILES/R'.rand().'.png';
         $lcCodDc = utf8_decode($laFila['CCODDOC'].' '.$laFila['CNOMBRE'].' '.$loDate->dateSimpleText($laFila['TDICTAM']).' '.$laFila['DFECHOR']);
         QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety=$loPdf->GetY();
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }

   protected function mxDictamenBorradorTesis() {
      //print_r($this->paData);
      $lcAluCod = substr($this->paAlumno[0]['CCODALU'], 0, 4);
      $loDate = new CDate;
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      // A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', '', 10);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CNOMUNI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      //$loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CPREFIJ']), 0, 'C' );
      if ($this->paData['CPREFIJ'] != 'A' AND $this->paData['CPREFIJ'] != '*' AND !in_array($this->paData['CUNIACA'], ['4A','4E','73'])) {
      //if ($this->paData['CPREFIJ'] != 'A' AND $this->paData['CPREFIJ'] != '*' AND !in_array($this->paData['CUNIACA'], ['4A','4E','73','40'])) {
         if (in_array($this->paData['CUNIACA'], ['51']) AND $lcAluCod <= '2000'){
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         } elseif (in_array($this->paData['CUNIACA'], ['51']) AND $lcAluCod > '2000'){
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         } elseif (in_array($this->paData['CUNIACA'], ['F6'])){
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         } else {
            $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('CON ESPECIALIDAD EN '.$this->paData['CESPCIA']), 0, 'C');
            $loPdf->Ln($lnParraf);
         }
      }       
      //$loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN DE APROBACIÓN DE BORRADOR'), 0, 'C');
      if($this->paData['CTIPO'] == 'T5'){
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN DE APROBACIÓN DE TRABAJO ACADÉMICO'), 0, 'C');
      }else{
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN DE APROBACIÓN DE BORRADOR'), 0, 'C');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['CMODALI']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el Borrador del expediente '.substr($this->paData['CDICTAM'],0,6).', presentado por:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
      //$loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Primer docente
      $loPdf->SetFont('times', 'B', 10);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode("DICTAMINADOR"), 0, 'J');
         $lcFile = 'FILES/R'.rand().'.png';
         $lcCodDc = utf8_decode($laFila['CCODDOC'].' '.$laFila['CNOMBRE'].' '.$loDate->dateSimpleText($laFila['TDICTAM']).' '.$laFila['TDICTAM'].' '.$laFila['DFECHOR']);
         QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety=$loPdf->GetY();
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }  

   protected function mxDictamenBorradorTesisPostGrado() {
      $loDate = new CDate;
      // A partir de este codigo configuro el documento PDF
      $lnWidth  = 0;                      //Ancho de la celda
      $lnHeight = 0.5;                    //Alto de la celda
      $lnParraf = 0.35;                   //Ancho de la celda
      $lnTamLet = 10;                     //Ancho de la celda
      // A partir de esta linea creo el documento PDF
      $loPdf = new PDF('portrait', 'cm', 'A4');
      $loPdf->AliasNbPages();
      $loPdf->SetMargins(2, 1, 2);
      $loPdf->AddPage();
      // A partir de este codigo se escribe en el documento
      $loPdf->SetFont('times', '', 10);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UCSM-ERP'), 0, 2, 'L');
      $loPdf->SetFont('times', 'B', 14);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('UNIVERSIDAD CATÓLICA DE SANTA MARÍA'), 0, 2, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B', 12);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('ESCUELA DE POSTGRADO'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('DICTAMEN APROBACIÓN DE BORRADOR DE TESIS'), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Arequipa, '.$loDate->dateSimpleText($this->paData['DFECHA'])), 0, 'R');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'BU' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Dictamen: '.$this->paData['CDICTAM']), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('Visto el borrador de tesis del expediente '.substr($this->paData['CDICTAM'],0,6).', presentado por:'), 0, 'J');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'B' , $lnTamLet);
      foreach ($this->paAlumno as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODALU'].' - '.$laFila['CNOMBRE']), 0, 'J');
      }
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Titulado:'), 0, 2, 'L');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($this->paData['MTITULO']), 0, 'C');
      $loPdf->Ln($lnParraf);
      $loPdf->SetFont('times' ,'' , $lnTamLet);
      $loPdf->Cell($lnWidth, $lnHeight, utf8_decode('Nuestro dictamen es: '), 0, 2, 'L');
      //$loPdf->Ln($lnParraf);
      $loPdf->SetFont('times', 'B', $lnTamLet);
      $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode('APROBADO'), 0, 'C');
      $loPdf->Ln($lnParraf * 3);
      // Firma del Primer docente
      $loPdf->SetFont('times', 'B', 10);
      foreach ($this->paDocente as $laFila) {
         $loPdf->Multicell($lnWidth, $lnHeight, utf8_decode($laFila['CCODDOC'].' - '.$laFila['CNOMBRE']), 0, 'J');
         $loPdf->Multicell($loPdf->GetPageWidth() - 8.5, $lnHeight, utf8_decode("DICTAMINADOR"), 0, 'J');
         $lcFile = 'FILES/R'.rand().'.png';
         $lcCodDc = utf8_decode($laFila['CCODDOC'].' '.$laFila['CNOMBRE'].' '.$loDate->dateSimpleText($laFila['TDICTAM']).' '.$laFila['TDICTAM'].' '.$laFila['DFECHOR']);
         QRcode::png(utf8_encode($lcCodDc), $lcFile, QR_ECLEVEL_L, 4, 0, false);
         $lnTpGety=$loPdf->GetY();
         $lnTpGety = $lnTpGety + (($loPdf->GetY() - $lnTpGety) / 2);
         $loPdf->Image($lcFile, $loPdf->GetPageWidth() - 6 - 2 + 3, $lnTpGety - 1, 2, 2, 'PNG');
         $loPdf->Ln($lnParraf * 4);
      }
      $loPdf->Output('F', $this->pcFile, true);
      return true;
   }  

}

/*class PDF extends FPDF {
   //public $pcRutImg = "img/Fondo3.jpeg";
   public $pcRutImg = "";
   
   function Header() {
      if ($this->pcRutImg != null) {
         $this->Image($this->pcRutImg, 0, 0, 21, 29.7, 'JPG');
      }
      $this->Ln(1);
   }
   
   function MultiCellBlt($w, $h, $blt, $txt, $border=0, $align='J', $fill=false) {
      //Get bullet width including margins
      $blt_width = $this->GetStringWidth($blt)+$this->cMargin*2;
      //Save x
      $bak_x = $this->x;
      //Output bullet
      $this->Cell($blt_width,$h,$blt,0,'',$fill);
      //Output text
      $this->MultiCell($w-$blt_width,$h,$txt,$border,$align,$fill);
      //Restore x
      $this->x = $bak_x;
   }
}*/
?>