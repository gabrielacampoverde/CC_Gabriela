<?php
    //header('Access-Control-Allow-Origin: *');
    // -----------------------------------------
    // Webservice Generar datos cobranza
    // 2023-07-31
    // -----------------------------------------
    //error_reporting(E_ALL);
    ini_set('display_errors', 1);
    require_once 'Clases/CBase.php';
    /*header("Content-type: application/json");
    header("Allow: POST,OPTIONS");*/
    /* Handle CORS */
    header("Access-Control-Allow-Origin: *");
    header(
      "Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization, X-Requested-With"
    );
    header("Access-Control-Allow-Methods: POST");
    header("Content-type: application/json");
    header("Allow: POST");
    if ($_SERVER["REQUEST_METHOD"] == "POST"){
        fxConsultasVarias();
    } elseif ($_SERVER["REQUEST_METHOD"] == "OPTIONS") {
        header("HTTP/1.1 200 OK");
        exit();
    } else {
        header("HTTP/1.1 404 BAD REQUEST");
        exit();
    }
   
 
    function fxConsultasVarias(){
        $laRequest = f_ProcesarJSON(file_get_contents("php://input"));
        if ($laRequest == null){
            $laData[] =['ERROR' => 'FORMATO DE JSON NO VALIDO'];
            header("HTTP/1.1 200 OK");
            echo json_encode($laData);
            exit();
        } elseif(!isset($laRequest['CUSUCOD'])){
            $laData[] =['ERROR' => 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'];
            header("HTTP/1.1 200 ok");
            echo json_encode($laData);
            exit();
        }
        $laTmp = ['ID' => 'ERP0011'];
        $sJson = json_encode($laTmp);
        $lcCommand = "python3 ./xpython/CConsultasVarias.py '".$sJson."' 2>&1";
        // print_r($lcCommand);
        // die();
        $lcData = shell_exec($lcCommand);
        // print_r($lcData);
        // die();
        $laArray = json_decode($lcData, true); 
        // print_r($lcData);
        // die();
        if (isset($laArray['ERROR'])) {
            header("HTTP/1.1 200 ok");
            echo json_encode($laArray);
            exit();
        }
        // print_r($laArray);
        header("HTTP/1.1 200 OK");
        echo json_encode($laArray);
        exit();
        return true;
    }


    function f_ProcesarJSON($p_cJSON) {
        $laData = json_decode($p_cJSON,true);
        return $laData;
    }
?>