<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CControlTesis extends CBase {
    public $paData, $paDatos;

    protected $paDetalle, $paEstado, $paTipo, $paTipTes, $paError, $paTesis, $paDocente, $paAlumno,
     $paEstPro, $paCargo, $paEstDic, $paUniaca, $paNotificacion, $pcFile, $paDicRol, $paDicCar, $paCatego, $paCondic;

    public function __construct() {
        parent::__construct();
        $this->paData = $this->paDatos = $this->paDetalle = $this->paEstado = $this->paTipo = $this->pcError = 
        $this->paTesis = $this->paDocente = $this->paAlumno = $this->paEstPro = $this->paCargo = $this->paEstDic = 
        $this->paUniaca = $this->paNotificacion = $this->paTipTes = $this->pcFile = $this->paDicRol = $this->paDicCar = 
        $this->laUniAca = $this->paObserv = $this->paCatego = $this->paCondic = null;  
    }

    protected function mxValParam(){ 
        if (!isset($this->paData['CUSUCOD']) || strlen($this->paData['CUSUCOD']) != 4){ 
           $this->pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'; 
           return false; 
        } elseif (!isset($this->paData['CCENCOS']) || strlen($this->paData['CCENCOS']) != 3){ 
           $this->pcError = 'CENTRO DE COSTO NO DEFINIDO O INVALIDO'; 
           return false; 
        } 
        return true; 
    } 
    protected function mxValUsuario($p_oSql){ 
        if ($this->paData['CCENCOS'] == 'UNI'){ 
           # Si es super-usuario 
           $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
           $R1 = $p_oSql->omExec($lcSql); 
           $RS = $p_oSql->fetch($R1); 
           if (strlen($RS[0]) == ''){ 
              return; 
           } elseif ($RS[0] == 'A'){ 
              $this->laUniAca[] = '*'; 
              return true; 
           } 
        } 
        if ($this->paData['CCENCOS'] == '0CP'){ //BIBLIOTECA
           # Si es super-usuario 
           $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
           echo $lcSql;
           $R1 = $p_oSql->omExec($lcSql); 
           $RS = $p_oSql->fetch($R1); 
           if (strlen($RS[0]) == ''){ 
              return; 
           } elseif ($RS[0] == 'A'){ 
              $this->laUniAca[] = '*'; 
              return true; 
           } 
        } 
        if ($this->paData['CCENCOS'] == '08M'){
            # Director Postgrado 
            $lcSql = "SELECT cUniAca FROM S01TUAC WHERE cNivel in ('03','04') OR cUniaca = '99'"; 
            echo $lcSql;
           $R1 = $p_oSql->omExec($lcSql); 
           while ($laFila = $p_oSql->fetch($R1)) {  
              $this->laUniAca[] = $laFila[0]; 
           } 
        } elseif ($this->paData['CUSUCOD'] == '1221'){
            $lcSql = "SELECT cEstado FROM V_S01PCCO WHERE cCenCos = '{$this->paData['CCENCOS']}' AND cCodUsu = '{$this->paData['CUSUCOD']}' AND cModulo = '000'";
            $R1 = $p_oSql->omExec($lcSql); 
            $RS = $p_oSql->fetch($R1); 
            if (strlen($RS[0]) == ''){ 
               return; 
            } elseif ($RS[0] == 'A'){ 
               $this->laUniAca[] = '*'; 
               return true; 
            } 
        } else {
            $lcSql = "SELECT DISTINCT B.cUniAca FROM V_S01PCCO A 
                      INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                      WHERE A.cCodUsu = '{$this->paData['CUSUCOD']}' AND A.cEstado = 'A' AND B.cUniAca != '00'";
            $R1 = $p_oSql->omExec($lcSql); 
            while ($laFila = $p_oSql->fetch($R1)) {  
               $this->laUniAca[] = $laFila[0]; 
            } 
            if (count($this->laUniAca) == 0){ 
                $this->pcError = 'USUARIO NO TIENE UNIDADES ACADEMICAS ASIGNADAS'; 
                return false; 
            } 
         }
        return true; 
    } 

    protected function mxValDirector($p_oSql){
        //if ($this->paData['CUSU'] != 'UNI'||$this->paData['CCENCOS'] != '0BO'||$this->paData['CCENCOS'] != '02V'){ 
        if ($this->paData['CUSUCOD'] !== '3184'&&$this->paData['CUSUCOD'] !== '1221'){ 
            //echo "holaaa";
            //print_r($this->paData['CUSUCOD']);
            //die;
            $lcSql = "SELECT * from S01TUAC B 
                     INNER JOIN V_A01MDOC C ON C.cCodDoc=B.cDocen1
                     INNER JOIN V_A01MDOC D ON D.cCodDoc=B.cDocen2
                    WHERE  (B.cDocen2='{$this->paData['CUSUCOD']}' OR B.cDocen1='{$this->paData['CUSUCOD']}')";
            $R1 = $p_oSql->omExec($lcSql); 
            while ($laFila = $p_oSql->fetch($R1)) {  
                $this->laDirect[] = $laFila[0]; 
            } 
            if (count($this->laDirect) == 0){ 
                $this->pcError = 'USUARIO NO ES DIRECTOR/DECANO'; 
                return false; 
            }
        }
        return true; 
    } 
    # -------------------------------------------------- 
    # Consulta tesis por apellidos y nombres de alumnos 
    # Bandeja de Decano - Director 
    # 2020-06-11 FPM Creacion 
    # -------------------------------------------------- 
    public function omInitDictaminadoresBDT() {  
        $llOk = $this->mxValParam();  
        if (!$llOk) {  
            return false;  
        }  
        $loSql = new CSql();  
        $llOk  = $loSql->omConnect();  
        if (!$llOk) {  
            $this->pcError = $loSql->pcError;  
            return false;  
        }  
        $llOk  = $this->mxValUsuario($loSql);  
        if (!$llOk) {  
            $loSql->omDisconnect();  
            return false;  
        }  
        $llOk = $this->mxInitDictaminadoresBDT($loSql);  
        $loSql->omDisconnect();  
        return $llOk;  
    }  

    protected function mxInitDictaminadoresBDT($p_oSql) {  
        $laData  = []; 
        $laDatos = []; 
        // pendientes de asignacion 
        $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo, F.cDescri FROM T01DALU A 
                    INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                    INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                    INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                    LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                    LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '143' AND F.cCodigo = B.cTipo 
                    WHERE B.cEstTes IN ('E') AND B.cEstado != 'X' AND B.cRefere ='000000'
                    ORDER BY D.cNombre"; 
        //echo $lcSql; 
        $R1 = $p_oSql->omExec($lcSql); 
        while ($laFila = $p_oSql->fetch($R1)) {  
            # Valida si usuario accede a unidad academica de tesis 
            if ($this->laUniAca[0] == '*'){} 
            else if (!in_array($laFila[4], $this->laUniAca)){ 
                continue; 
            } 
            $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
            $laData[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3], 
                        'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst,'CTIPO'   => $laFila[8],
                        'CMODALI' => $laFila[9], 'CFLAG' => $laFila[0].$laFila[4].$laFila[8]]; //(N)
        } 
        $lcSql = "SELECT A.cIdTesi, B.cEstado, D.cNombre, B.mTitulo, B.cUniAca, C.cNomUni, B.cEstTes, E.cDescri, B.cTipo, F.cDescri FROM T01DALU A 
                    INNER JOIN T01MTES B ON B.cIdTesi = A.cIdTesi 
                    INNER JOIN S01TUAC C ON C.cUniAca = B.cUniAca 
                    INNER JOIN V_A01MALU D ON D.cCodAlu = A.cCodAlu 
                    LEFT OUTER JOIN V_S01TTAB E ON E.cCodTab = '252' AND SUBSTRING(E.cCodigo, 1, 1) = B.cEstTes 
                    LEFT OUTER JOIN V_S01TTAB F ON F.cCodTab = '143' AND F.cCodigo = B.cTipo 
                    WHERE B.cEstTes NOT IN ('A','C','E','H','K','N') AND B.cEstado != 'X' AND B.cRefere ='000000'
                    ORDER BY D.cNombre"; 
        $R1 = $p_oSql->omExec($lcSql); 
        while ($laFila = $p_oSql->fetch($R1)) {  
            # Valida si usuario accede a unidad academica de tesis 
            if ($this->laUniAca[0] == '*'){} 
            else if (!in_array($laFila[4], $this->laUniAca)){ 
                continue; 
            } 
            if($laFila[10] =='000000'){
                $lcRefere=0;
            } else{
                $lcRefere=1;
            }
            $lcDesEst =  ($laFila[7] == '')? '[ERR]' : $laFila[7]; 
            $laDatos[] = ['CIDTESI' => $laFila[0], 'CESTADO' => $laFila[1], 'CNOMBRE' => str_replace('/', ' ', $laFila[2]), 'MTITULO' => $laFila[3], 
                        'CUNIACA' => $laFila[4], 'CNOMUNI' => $laFila[5], 'CESTTES' => $laFila[6], 'CDESEST' => $lcDesEst,'CTIPO'   => $laFila[8],
                        'CMODALI' => $laFila[9]]; 
        } 
        $this->paData  = $laData; 
        $this->paDatos = $laDatos; 
        return true;
    }
    # -------------------------------------------------------
    # VISUALIZAR DETALLES ASIGNACION DE DICTAMINADORES BDT
    # 2020-06-11 FPM Creacion 
    # -------------------------------------------------------
    public function omDetallesBDT(){ 
        $llOk = $this->mxValParamDetallesBDT(); 
        if (!$llOk){ 
            return false; 
        } 
        $loSql = new CSql();  
        $llOk = $loSql->omConnect(); 
        if (!$llOk){ 
            $this->pcError = $loSql->pcError; 
            return false; 
        } 
        $llOk = $this->mxDetallesBDT($loSql); 
        $loSql->omDisconnect(); 
        return $llOk; 
    } 

    protected function  mxValParamDetallesBDT(){ 
        $llOk = $this->mxValParam(); 
        if (!$llOk){ 
            return false; 
        } else if (!isset($this->paData['CIDTESI']) || strlen($this->paData['CIDTESI']) != 6){ 
            $this->pcError = 'ID DE TESIS NO DEFINIDO O INVALIDO'; 
            return false; 
        }
        return true; 
    } 

    protected function mxDetallesBDT($p_oSql){ 
        $lcSql = "SELECT A.cIdTesi, A.cEstado, A.mTitulo, A.cUniAca, B.cNomUni, A.cEstTes, C.cDescri, B.cNivel, A.cNewReg, TO_CHAR(A.dEntreg, 'YYYY-mm-dd HH24:MI'), A.cTipo FROM T01MTES A 
                INNER JOIN S01TUAC B ON B.cUniAca = A.cUniAca 
                LEFT OUTER JOIN V_S01TTAB C ON C.cCodTab = '252' AND SUBSTRING(C.cCodigo, 1, 1) = A.cEstTes 
                WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cEstado != 'X'"; 
        $R1 = $p_oSql->omExec($lcSql); 
        $RS = $p_oSql->fetch($R1); 
        if ( $RS[0] == ''){ 
            $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO EXISTE'; 
            return false; 
        } 
        $lcDesEst = ($RS[6] == '')? '[ERR-'.$RS[5].']' : $RS[6]; 
        $laData = ['CIDTESI'=> $RS[0],   'CESTADO'=> $RS[1],'MTITULO'=> $RS[2],'CUNIACA'=> $RS[3],'CNOMUNI'=> $RS[4],'CESTTES'=> $RS[5], 
                'CDESEST'=> $lcDesEst,'ACODALU'=> null,  'ACODDOC'=> null,  'ACODASE'=> null,  'ACODDIC'=> null,  'ACODJUR'=> null, 
                'CNIVEL' => $RS[7],   'CNEWREG'=> $RS[8],'DENTREG'=> $RS[9], 'CTIPO'=> $RS[10], 'AEXPJUR'=> '']; 
        $this->laData = $laData;
        # Alumnos de tesis                
        $laCodAlu = $this->mxAlumnos($p_oSql); 
        if (count($laCodAlu) == 0){ 
            $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE ALUMNOS DEFINIDOS'; 
            return false; 
        } 
        $laData['ACODALU'] = $laCodAlu; 
        $llOk = $this->mxExpedienteEstandar($p_oSql, $laData);
        return $llOk;
    }

    protected function mxExpedienteEstandar($p_oSql, $p_aData) {
        # Dictaminadores de borrador de tesis 
        $laCodAse = $this->mxAsesorTesis($p_oSql); 
        if (count($laCodAse) != 1 AND $p_aData['CNEWREG'] == '1'){ 
            $this->pcError = 'ID DE TESIS ['.$this->paData['CIDTESI'].'] NO TIENE UN ASESOR DEFINIDO'; 
            return false; 
        } 
        $p_aData['ACODASE'] = $laCodAse; 
        //Dictaminadores de tesis
        $laCodDict[] =null;
        $laCodDict = $this->mxDictaminadoresBDT($p_oSql); 
        // Observaciones  
        $lcSql = "SELECT TRIM(B.cNombre), TRIM(A.mObserv), TO_CHAR(A.tModifi,'YYYY-MM-DD') 
                   FROM T01DLOG A 
                   LEFT OUTER JOIN V_S01TUSU_1 B ON B.CCODUSU = A.CUSUCOD 
                   WHERE A.cEstTes IN ('B','D','F','J') AND A.cEstPro = 'N' AND A.cEstado = 'A' AND A.cIdTesi = '{$this->paData['CIDTESI']}' ORDER BY tModifi DESC "; 
        $R1 = $p_oSql->omExec($lcSql); 
        while ($laFila = $p_oSql->fetch($R1)){   
          $this->paObserv[] = ['CNOMBRE' => str_replace('/', ' ', $laFila[0]), 'MOBSERV' => $laFila[1], 'FECHA' => $laFila[2]];   
        } 
        //$p_aData['ACANTES'] = $paCanTes;
        $this->paData = $p_aData; 
        return true; 
    }  

    # Asesor de tesis 
    protected function mxAsesorTesis($p_oSql){ 
        $laCodAse = null; 
        $lcSql = "SELECT A.cCodDoc, B.cNombre, TO_CHAR(A.tDecret, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDictam, 'YYYY-MM-DD HH24:MI'), TO_CHAR(A.tDecret, 'YYYYMMDDHH24MI'), C.cDescri
                FROM T01DDOC A 
                INNER JOIN V_A01MDOC B ON B.cCodDoc = A.cCodDoc
                INNER JOIN S01TTAB C ON C.cCodigo = A.cResult AND C.cCodTab = '253' 
                WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego = 'B' AND A.cEstado = 'A' ORDER BY A.cCodDoc"; 
        $R1 = $p_oSql->omExec($lcSql); 
        while ($laFila = $p_oSql->fetch($R1)) { 
            $ltDictam =  ($laFila[3] == '')? 'S/D' : $laFila[3]; 
            $laCodAse[] = ['CCODDOC'=> $laFila[0], 'CNOMDOC'=> str_replace('/', ' ', $laFila[1]), 'TDECRET'=> $laFila[2], 'TDICTAM'=> $ltDictam, 'DFECHOR'=> $laFila[4], 'CDESCRI'=> $laFila[5]]; 
        } 
        return $laCodAse; 
    } 

    # Estudiantes integrantes de tesis 
    protected function mxAlumnos($p_oSql){ 
        $laCodAlu = null; 
        $lcSql = "SELECT A.cCodAlu, B.cNombre, B.cNroCel, B.cEmailp, B.cEmail, B.cNomUni, B.cNroDni FROM T01DALU A 
                    INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu 
                    WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.CESTADO = 'A'"; 
        $R1 = $p_oSql->omExec($lcSql); 
        while ($laFila = $p_oSql->fetch($R1)) { 
        $laCodAlu[] = ['CCODALU'=> $laFila[0], 'CNOMALU'=> str_replace('/', ' ', $laFila[1]), 'CNROCEL'=> $laFila[2],
                        'CEMAILP'=> $laFila[3], 'CEMAIL' => $laFila[4], 'CNOMUNI'=> $laFila[5],'CNRODNI'=> $laFila[6]]; 
        } 
        return $laCodAlu; 
    }


    protected function mxDictaminadoresBDT($p_oSql) {
        if ($this->laData['CNIVEL'] == '04') {
            $lnDicBDT = 5;
        } else {
            $lnDictBDT = 3;
        }
        
        $i = 0;
        $llOk = false;
        $laDatos = [];
        $lcSql = "SELECT A.cCodDoc, B.cNroDni, A.cCatego FROM T01DDOC A
                  INNER JOIN S01TUSU B ON B.cCodUsu = A.cUsuCod
                  WHERE A.cIdTesi = '{$this->paData['CIDTESI']}' AND A.cCatego IN ('A', 'B') AND A.cEstado = 'A'";
        $R1 = $p_oSql->omExec($lcSql);
        while ($laTmp = $p_oSql->fetch($R1)) {
           if ($laTmp[2] == 'B') {
              $llOk = true;
           } else {
              $i += 1;
           }
           $laDatos[] = ['CCODDOC'=> $laTmp[0], 'CNRODNI'=> $laTmp[1], 'CNOMBRE' => '', 'CCARGO'=> $laTmp[2]];
        }
        if ($i != 2) {
           $this->pcError = 'NO HAY DOS DICTAMINADORES PARA PROYECTO/PLAN DE TESIS';
           return false;
        } elseif (!$llOk) {
           $this->pcError = 'ASESOR NO ESTÁ DEFINIDO';
           return false;
        }
        $i = 0;
        /*$lcSql = "SELECT A.cCodDoc, A.nNumJur, B.cCatego, B.cCondic FROM T01DDAJ A 
                  WHERE A.cUniAca = '{$this->laData['CUNIACA']}' AND
                  B.cCatego IN ('01', '02', '03', '05') AND A.cEstado = 'A' ORDER BY A.nNumJur, B.cCatego";*/
        $lcSql = "SELECT A.cCodDoc, B.cCondic, C.cNroDni FROM T01DDAJ A
                  INNER JOIN A01MDOC B ON B.cCodDoc = A.cCodDoc
                  INNER JOIN V_A01MDOC C ON C.cCodDoc = A.cCodDoc
                  WHERE A.cUniAca = '{$this->laData['CUNIACA']}' AND 
                  B.cCatego IN ('01', '02', '03', '05') AND A.cEstado = 'A' ORDER BY A.nNumTes, B.cCatego";
        $R1 = $p_oSql->omExec($lcSql);
        while ($laTmp = $p_oSql->fetch($R1)) {
           $llFound = false;
           //echo 'ITERACION:'.$i.'<br>';
           foreach ($laDatos as $laTmp1) {
              //echo 'DOCENTE SQL:'.$laTmp[4].'<br>';
              //echo 'DOCENTE ARREGLO:'.$laTmp1['CNRODNI'].'<br>';
              if ($laTmp1['CNRODNI'] == $laTmp[2]) {
                 ///echo '00000';
                 $llFound = true;
                 break;
              }
           }
           if ($llFound) {
              ///echo 'PASS'.$laTmp[4].'<br>';
              continue;
           }
           $r = rand(1, 10);
           if ($laTmp[1] == 'N' and $r <= 7) {
              $laDatos[] = ['CCODDOC'=> $laTmp[0], 'CNRODNI'=> $laTmp[2], 'CNOMBRE' => '','CCARGO'=> '*'];
              break;
              //$i += 1;
           } elseif ($laTmp[1] == 'C' and $r <= 1) {
              $laDatos[] = ['CCODDOC'=> $laTmp[0], 'CNRODNI'=> $laTmp[2], 'CNOMBRE' => '', 'CCARGO'=> '*'];
              break;
           }
        }
        $i = 0;
        foreach ($laDatos as $laTmp) {
           if ($laTmp['CCARGO'] == 'B'){
            continue;
           }
           $lcSql = "SELECT REPLACE(cNombre,'/',' ') FROM V_S01TUSU_1 WHERE cNroDni = '{$laTmp['CNRODNI']}' AND cEstado = 'A'";
           $R1 = $p_oSql->omExec($lcSql);
           $laTmp1 = $p_oSql->fetch($R1);
           $laDatos1[] = ['CCODDOC'=> $laTmp['CCODDOC'], 'CNRODNI'=> $laTmp['CNRODNI'], 'CNOMBRE' => $laTmp1[0]];
           $i++;
        }
        $this->paDatos = $laDatos1;
        return true;
    }


    # -------------------------------------------------------
    # INIT MANTENIMIENTO DE JURADO T01DDAJ
    # 2023-06-14 KMC Creacion 
    # -------------------------------------------------------

    public function onInitMantemientoJurados() {
        $llOk = $this->mxValParam();  
        if (!$llOk) {  
            return false;  
        }  
        $loSql = new CSql();  
        $llOk  = $loSql->omConnect();  
        if (!$llOk) {  
            $this->pcError = $loSql->pcError;  
            return false;  
        }  
        $llOk  = $this->mxValUsuario($loSql);  
        if (!$llOk) {  
            $loSql->omDisconnect();  
            return false;  
        }  
        $llOk  = $this->mxValDirector($loSql);  
        if (!$llOk) {  
            $loSql->omDisconnect();  
            return false;  
        }  
        $llOk = $this->mxInitMantemientoJurados($loSql);  
        $loSql->omDisconnect();  
        return $llOk;  
    }

    protected function mxInitMantemientoJurados($p_oSql) {
        $paCargo  = $paCatego = $paCondic = $paEstado = $paUniDoc = $paDocente = [];
        $lcCenCos = $this->paData['CCENCOS'];
        $lcUsuCod = $this->paData['CUSUCOD'];

        //UNIDADES ACADEMICAS POR DIRECTOR
        $lcSql = "SELECT B.cUniAca,B.cNomuni from S01TUAC B 
                    INNER JOIN V_A01MDOC C ON C.cCodDoc=B.cDocen1
                    INNER JOIN V_A01MDOC D ON D.cCodDoc=B.cDocen2
                    WHERE B.cEstado='A' ";
        
        //if ($lcCenCos === 'UNI'){
        if ($lcUsuCod === '3184'||$lcUsuCod === '1221'){
            $lcSql .= " ORDER BY B.cNivel";
        } else {
            $lcSql .= " AND (B.cDocen2='{$this->paData['CUSUCOD']}' OR B.cDocen1='{$this->paData['CUSUCOD']}') ORDER BY B.cNivel";
        }
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paUniDoc[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1]];
        }
        //TRAER DOCENTES
        $lcSql = "SELECT A.cCodDoc,B.cNombre,A.cCatego,C.cDescri,A.cCondic,D.cDescri FROM  A01MDOC A 
                    INNER JOIN V_S01TUSU_1 B ON B.cCodUsu=A.cCodDoc
                    INNER JOIN V_S01TTAB C ON TRIM(C.cCodigo)=A.cCatego AND C.cCodTab='283'
                    INNER JOIN V_S01TTAB D ON TRIM(D.cCodigo)=A.cCondic AND D.cCodTab='364' 
                    ORDER BY A.cCodDoc";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paDocente[] = ['CCODDOC' => $laFila[0], 'CNOMBRE' => $laFila[1], 'CCATEGO' => $laFila[2], 'CDESCAT' => $laFila[3],
                            'CCONDIC' => $laFila[4], 'CDESCON' => $laFila[5],];
        }
        //TRAER TABLA DE CATEGORIA  --283
        $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '283'";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paCatego[] = ['CCATEGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
        }
        //TRAER TABLA DE CARGOS -- 363
        $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '363'";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paCargo[] = ['CCARGO' => $laFila[0], 'CDESCRI' => $laFila[1]];
        }
        //TRAER TABLA DE CONDICION  -- 364
        $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '364'";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paCondic[] = ['CCONDIC' => $laFila[0], 'CDESCRI' => $laFila[1]];
        }
        //TRAER TABLA DE ESTADO  -- 041
        $lcSql = "SELECT TRIM(cCodigo), cDescri FROM V_S01TTAB WHERE CCODTAB = '041'";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paEstado[] = ['CESTADO' => $laFila[0], 'CDESCRI' => $laFila[1]];
        }
        //TRAER INFORMACION DE T01DDAJ
        $lcSql = "SELECT A.nSerial,A.cUniAca,B.cNomUni,A.cCodDoc,D.cNombre,C.cCatego,E.cDescri,C.cCondic,F.cDescri,
                        A.cCargo,G.cDescri,A.cEstado,I.cDescri,A.nNumTes FROM T01DDAJ A
                    INNER JOIN S01TUAC B ON B.cUniAca=A.cUniAca
                    INNER JOIN A01MDOC C ON C.cCodDoc=A.cCodDoc
                    INNER JOIN V_S01TUSU_1 D ON D.cCodUsu=C.cCodDoc
                    INNER JOIN V_S01TTAB E ON TRIM(E.cCodigo)=C.cCatego AND E.cCodTab='283'
                    INNER JOIN V_S01TTAB F ON TRIM(F.cCodigo)=C.cCondic AND F.cCodTab='364'
                    INNER JOIN V_S01TTAB G ON TRIM(G.cCodigo)=A.cCargo AND G.cCodTab='363'
                    INNER JOIN V_S01TTAB I ON TRIM(I.cCodigo)=A.cEstado AND I.cCodTab='041'";
        //echo $lcSql;
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CUNIACA' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCODDOC' => $laFila[3],
                                'CNOMBRE' => $laFila[4], 'CCATEGO' => $laFila[5], 'CDESCAT' => $laFila[6], 'CCONDIC' => $laFila[7], 
                                'CDESCON' => $laFila[8], 'CCARGO'  => $laFila[9], 'CDESCAR' => $laFila[10],'CESTADO' => $laFila[11],
                                'CDESEST' => $laFila[12],'NNUMTES' => $laFila[13]];
        }
        $this->paData = ['ACATEGO'=> $paCatego, 'ACARGO'=> $paCargo, 'ACONDIC'=> $paCondic, 'AESTADO'=> $paEstado, 'AUNIDOC'=> $paUniDoc, 'ADOCENTE'=> $paDocente];
        return true;
    }

    public function omBuscarJuradosxUnidad() {
        if ($this->paData['CCENCOS'] === '*') {
           $this->pcError = "USUARIO NO TIENE CENTRO DE COSTO DEFINIDO";
           return false;
        }
        $loSql = new CSql();
        $llOk = $loSql->omConnect();
        if (!$llOk) {
           $this->pcError = $loSql->pcError;
           return false;
        }
        $llOk = $this->mxBuscarJuradosxUnidad($loSql); 
        $loSql->omDisconnect();
        return $llOk;
    }
    protected function mxBuscarJuradosxUnidad($p_oSql) {
        $lcUniAca = $this->paData['CUNIACA'];
        $lcSql = "SELECT A.nSerial,A.cUniAca,B.cNomUni,A.cCodDoc,D.cNombre,C.cCatego,E.cDescri,C.cCondic,F.cDescri,
                        A.cCargo,G.cDescri,A.cEstado,I.cDescri,A.nNumTes FROM T01DDAJ A
                    INNER JOIN S01TUAC B ON B.cUniAca=A.cUniAca
                    INNER JOIN A01MDOC C ON C.cCodDoc=A.cCodDoc
                    INNER JOIN V_S01TUSU_1 D ON D.cCodUsu=C.cCodDoc
                    INNER JOIN V_S01TTAB E ON TRIM(E.cCodigo)=C.cCatego AND E.cCodTab='283'
                    INNER JOIN V_S01TTAB F ON TRIM(F.cCodigo)=C.cCondic AND F.cCodTab='364'
                    INNER JOIN V_S01TTAB G ON TRIM(G.cCodigo)=A.cCargo AND G.cCodTab='363'
                    INNER JOIN V_S01TTAB I ON TRIM(I.cCodigo)=A.cEstado AND I.cCodTab='041'
                    WHERE A.cUniAca ='$lcUniAca' ORDER BY A.nNumTes";
        $RS = $p_oSql->omExec($lcSql);
        $i = 0;
        while ($laFila = $p_oSql->fetch($RS)) {  
            $this->paDatos[] = ['NSERIAL' => $laFila[0], 'CUNIACA' => $laFila[1], 'CNOMUNI' => $laFila[2], 'CCODDOC' => $laFila[3],
                                'CNOMBRE' => $laFila[4], 'CCATEGO' => $laFila[5], 'CDESCAT' => $laFila[6], 'CCONDIC' => $laFila[7], 
                                'CDESCON' => $laFila[8], 'CCARGO'  => $laFila[9], 'CDESCAR' => $laFila[10],'CESTADO' => $laFila[11],
                                'CDESEST' => $laFila[12],'NNUMTES' => $laFila[13]];
            $i++;
        }
        return true;
    }

    public function omBuscarMiembro() {
        $loSql = new CSql();
        $llOk = $loSql->omConnect();
        if (!$llOk) {
           $this->pcError = $loSql->pcError;
           return false;
        }
        $llOk = $this->mxBuscarMiembro($loSql);
        $loSql->omDisconnect();
        return $llOk;
     }
  
    protected function mxBuscarMiembro($p_oSql) {
        $lcCodUsu = str_replace(" ","%",strtoupper(trim($this->paData['CCODUSU'])));
        $lcCodUsu = str_replace("Ñ", '_', $lcCodUsu);
        $lcCodUsu = str_replace("Á", '_', $lcCodUsu);
        $lcCodUsu = str_replace("É", '_', $lcCodUsu);
        $lcCodUsu = str_replace("Í", '_', $lcCodUsu);
        $lcCodUsu = str_replace("Ó", '_', $lcCodUsu);
        $lcCodUsu = str_replace("Ú", '_', $lcCodUsu);
        $lcCodUsu = preg_replace('/[^\da-z]/i', '%', $lcCodUsu);
        $lcSql = "SELECT B.cCodUsu,B.cNroDni, B.cNombre FROM A01MDOC A
                    INNER JOIN V_S01TUSU_1 B ON B.cCodUsu=A.cCodDoc 
                    WHERE (B.cCodUsu LIKE '$lcCodUsu' OR B.cNombre LIKE '%$lcCodUsu%' OR B.cNroDni = '$lcCodUsu') ORDER BY cNombre";
        $RS = $p_oSql->omExec($lcSql);
        $i = 0;
        while ($laFila = $p_oSql->fetch($RS)) {
           $this->paDatos[] = ['CCODUSU' => $laFila[0],'CNRODNI' => $laFila[1], 'CNOMBRE' => $laFila[2]];
           $i++;
        }
        if ($i == 0) {
           $this->pcError = "NO HAY DOCENTES QUE COINCIDAN CON LOS PARÁMETROS INGRESADOS";
           return false;
        }
        return true;
    }

    public function omGrabarJurado() {
        $loSql = new CSql();
        $llOk = $loSql->omConnect();
        if (!$llOk) {
           $this->pcError = $loSql->pcError;
           return false;
        }
        $llOk = $this->mxGrabarJurado($loSql);
        if (!$llOk) {
           $loSql->rollback();
        }
        $loSql->omDisconnect();
        return $llOk;
    }
  
    protected function mxGrabarJurado($p_oSql) {
        $lcUniAca=$this->paData['CUNIACA'];
        $lcCodDoc=$this->paData['CCODDOC'];
        $lcSql = "SELECT * FROM T01DDAJ WHERE cUniAca='$lcUniAca' AND cCodDoc='$lcCodDoc'";
        $R1 = $p_oSql->omExec($lcSql);
        if ($p_oSql->pnNumRow > 0) {
            $this->pcError = "ERROR: YA EXISTE EL DOCENTE EN LA UNIDAD ACADEMICA";
            return false; 
        }
        $lcSql = "INSERT INTO T01DDAJ (	cuniaca, ccoddoc, cestado, ccargo, nnumtes, cusucod)
        VALUES ('{$this->paData['CUNIACA']}','{$this->paData['CCODDOC']}', '{$this->paData['CESTADO']}', '{$this->paData['CCARGO']}',0,'{$this->paData['CUSUCOD']}')";
        //echo $lcSql;
        $llOk= $p_oSql->omExec($lcSql);
        if (!$llOk) {
              $this->pcError = 'ERROR AL INSERTAR REGISTRO';
              return false;
        }
        $lcSql = "SELECT MAX(nSerial) FROM T01DDAJ";
        $R1 = $p_oSql->omExec($lcSql);
        $laTmp = $p_oSql->fetch($R1);
        $this->lnSerial =  $laTmp;     
        return true;
    }

    public function omEditarDatosJurado() {
        $loSql = new CSql();
        $llOk = $loSql->omConnect();
        if (!$llOk) {
           $this->pcError = $loSql->pcError;
           return false;
        }
        $llOk = $this->mxEditarDatosJurado($loSql);
        $loSql->omDisconnect();
        return $llOk;  
     }
     protected function mxEditarDatosJurado($p_oSql) { 
        $lcSql = "UPDATE T01DDAJ SET CESTADO = '{$this->paData['CESTADO']}', CCARGO = '{$this->paData['CCARGO']}' WHERE NSERIAL='{$this->paData['NSERIAL']}'";
        //echo $lcSql;
        $llOk = $p_oSql->omExec($lcSql);
        if (!$llOk) {
           $this->pcError = 'ERROR AL GRABAR LOS DATOS';
           return false;
        }
        return true;
     }
  
}
?>
