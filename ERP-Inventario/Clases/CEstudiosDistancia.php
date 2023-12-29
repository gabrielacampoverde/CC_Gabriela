<?php
require_once "Clases/CBase.php";
require_once "Clases/CSql.php";

class CEstudiosDistancia extends CBase {
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

    protected function mxValUsuarioEstudiosDistancia($p_oSql) {
        // 1JR - DIRECCION DE ESTUDIOS A DISTANCIA Y SEMIPRESENCIALES
        $lcCodUsu = $this->paData['CUSUCOD'];
        $lcCenCos = $this->paData['CCENCOS'];
        if ($lcCenCos == 'UNI'){
            $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCenCos = '$lcCenCos' AND cCodUsu = '$lcCodUsu' AND cModulo = '000' AND cEstado = 'A'";
            //echo $lcSql;
            $RS = $p_oSql->omExec($lcSql);
            if ($RS == false) {
               $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
               return false;
            } else if ($p_oSql->pnNumRow == 0) {
               $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
               return false;
            }
        } else {
            $lcSql = "SELECT cCenCos FROM V_S01PCCO WHERE cCenCos = '1JR' AND cCodUsu = '$lcCodUsu' AND cModulo = '000' AND cEstado = 'A'";
            //echo $lcSql;
            $RS = $p_oSql->omExec($lcSql);
            if ($RS == false) {
                $this->pcError = "ERROR DE EJECUCION DE BASE DE DATOS";
                return false;
            } else if ($p_oSql->pnNumRow == 0) {
                $this->pcError = "ACCESO RESTRINGIDO A USUARIO";
                return false;
            }
        }
        
        return true;
     }

    # -------------------------------------------------------
    # INIT MANTENIMIENTO DE CONSULTA ESTUDIOS A DISTANCIA
    # 2023-09-12 KMC Creacion 
    # -------------------------------------------------------

    public function onInitConsultaEstudiosDistancia() {
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
        $llOk  = $this->mxValUsuarioEstudiosDistancia($loSql);  
        if (!$llOk) {  
            $loSql->omDisconnect();  
            return false;  
        }  
        $llOk = $this->mxInitConsultaEstudiosDistancia($loSql);  
        $loSql->omDisconnect();  
        return $llOk;  
    }

    protected function mxInitConsultaEstudiosDistancia($p_oSql) {
        $paUnidad = $paPeriod = [];
        $lcCenCos = $this->paData['CCENCOS'];
        $lcPeriod = $this->paData['CPERIOD'];
        $lcParame = $this->paData['CPARAME'];
        //UNIDADES ACADEMICAS POR DIRECTOR
        $lcSql = "SELECT A.cUniAca, B.cNomUni FROM S01TCCO A
                    INNER JOIN S01TUAC B ON B.cUniAca=A.cUniAca
                    WHERE A.cClase LIKE 'D0310010%' ORDER BY A.cUniaca ";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paUnidad[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1]];
        }
        //PERIODOS ACTIVOS ESTUDIOS A DISTANCIA
        $lcSql = "SELECT DISTINCT TRIM(CPROYEC) FROM C10DCCT WHERE cEstado = 'A' AND LEFT(cDocume,2) = '18' AND LENGTH(CPROYEC)=6";
        $RS = $p_oSql->omExec($lcSql);
        while ($laFila = $p_oSql->fetch($RS)) {
            $paPeriod[] = ['CPROYEC' => $laFila[0]];
        }
        $this->paData = ['APERIOD'=> $paPeriod, 'AUNIDAD'=> $paUnidad];
        return true;
    }

    public function omBuscarDeudasEstudiosDistancia() {
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
        $llOk  = $this->mxValUsuarioEstudiosDistancia($loSql);  
        if (!$llOk) {  
            $loSql->omDisconnect();  
            return false;  
        }  
        $llOk = $this->mxBuscarDeudasEstudiosDistancia($loSql); 
        $loSql->omDisconnect();
        return $llOk;
    }
    protected function mxBuscarDeudasEstudiosDistancia($p_oSql) {
        $lcUniAca = trim(strtoupper($this->paData['CUNIACA']));
        $lcPeriod = trim(strtoupper($this->paData['CPERIOD']));
        $lcParame = trim(strtoupper($this->paData['CPARAME']));
        $lcParame = str_replace(' ', '%', trim($lcParame));
        $lcParame = str_replace('#', 'Ñ', trim($lcParame));

        $lcSql = "SELECT A.cUniaca, A.cNomuni, R.cCodalu, replace(A.cNombre,'/',' '), R.cProyec, R.nDeuda, A.cNroDni FROM 
                    (SELECT cCodalu, cProyec, SUM(nDebito - nAbono) AS nDeuda FROM C10DCCT  WHERE cEstado = 'A' AND LEFT(cDocume,2) = '18' GROUP BY cCodalu, cProyec) AS R   
                     LEFT JOIN V_A01MALU A ON A.cCodalu = R.cCodalu
                  WHERE R.nDeuda > 0 ";
        $lcSql .= ($lcUniAca!='*') ? " AND A.cUniaca='$lcUniAca'" : " ";
        $lcSql .= ($lcPeriod!='*') ? " AND R.cProyec='$lcPeriod'" : " ";
        $lcSql .= ($lcParame!='') ? " AND (R.cCodalu = '$lcParame' OR A.cNombre LIKE '%$lcParame%' OR A.cNroDni = '$lcParame') " : " ";
        $lcSql .= " ORDER BY A.cUniaca, R.cProyec, R.cCodalu";
        //echo $lcSql;
        $RS = $p_oSql->omExec($lcSql);
        $i = 0;
        while ($laFila = $p_oSql->fetch($RS)) {  
            $this->paDatos[] = ['CUNIACA' => $laFila[0], 'CNOMUNI' => $laFila[1], 'CCODALU' => $laFila[2],
                                'CNOMBRE' => $laFila[3], 'CPROYEC' => $laFila[4], 'NDEUDA' => $laFila[5],'CNRODNI' => $laFila[6]];
            $i++;
        }
        return true;
    }
  
}
?>
