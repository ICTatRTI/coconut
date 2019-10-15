<?php
/**
 * Created by IntelliJ IDEA.
 * User: vbakalov
 * Date: 12/9/2014
 * Time: 10:07 AM
 */

$couch_db = "coconut";
$couch_dsn = "http://localhost:5984/";

require_once "./lib/couch.php";
require_once "./lib/couchClient.php";
require_once "./lib/couchDocument.php";

/******* GLOBALS ********/

// open client connection with couchDB
$client = new couchClient($couch_dsn,$couch_db);

//$client->asCouchDocuments();

// load all data by question and complete
$data = loadData();
exit(1);
// load registrations
$regs = loadRegistrations($data);

// print 2 csv
$regsFileName = 'ajexport/Registrations.csv';
$regKeys = array("uuid","provider_id","provider_name","createdAt","lastModifiedAt","user_name","Fecha","Apellido","Apodo","Año"," Sexo","
                    BarrioComunidad","Calleynumero","Casa","Celular","Completado","Direccióndecorreoelectrónico","Día","Mes","
                    Municipio"," Nombre","Nombredepersonadecontacto"," NombredeusuariodeFacebook","Parentescoopersonarelacionada","
                    Provincia","Teléfono"," Estecolateralparticipante","Tieneunadireccióndecorreoelectrónico","
                    TieneunnombredeusuariodeFacebook"," Tieneunnumerocelular"," Tieneunnumerodetelefonoenlacasa");

print2csv($regsFileName, $regs, $regKeys);

function loadData(){
    global $client;

    //$data = $client->getView ( "coconut",  "resultsByQuestionAndComplete");
    $data = $client->getView ( "coconut",  "findParticipantsByProvider");


    return $data;
}

/**
 *
 * Parse documents with key: "Participant Registration-es:true:2014-11-01T17:32:42-04:00".
 *
 * @param $data
 */
function loadRegistrations($data){
    $regs = array();
    $rows = $data->rows;
    $docProcessed = 0;
    foreach($rows as $doc){
        list($question, $isComplete, $yyyy) = explode(":", $doc->key);
        if ($question === "Participant Registration-es" && $isComplete === "true"){
            $regDoc = openDocument($doc->id);
            if ($regDoc == null){
                echo "Something wrong....";
                exit(-1);
            }
            array_push($regs, $regDoc);
            echo $docProcessed."\n";
            $docProcessed++;
        }
    }

    return regs;
}

function openDocument($_id){
    global $client;

    try {
        $doc = $client->getDoc($_id);
        return $doc;

    } catch (Exception $e) {
        if ( $e->code() == 404 ) {
            echo "Document not found\n";
        } else {
            echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        }

        return null;
    }
}

/**
 * Generates the CSV registration file.
 *
 * @param $regsFileName
 * @param $regs
 * @param $regKeys
 */
function print2csv($regsFileName, $regs, $regKeys){
    $file = fopen($regsFileName,"w");
    $line ="";
    $start = true;
    // write header
    foreach($regKeys as $col){
        if ($start){
            $start = false;
            $line = $col;
        }else {
            $line = $line . "," . $col;
        }        
    }
    
    fwrite($file, $line);
    fwrite($file, "\r\n");
    
    foreach ($regs as  $doc) {
        doc2line($doc, $regKeys);
        fwrite($file, $line);
        fwrite($file, "\r\n");
    }
    fclose($file);
}

function doc2line($doc, $keys){
    $line = "";
    $start = true;
    
    foreach($keys as $key){
        if ($start){
            $start = false;
            $line = $doc[$key];
        }else {
            $line = $line . "," . $doc[$key];
        }
    }
    
    return $line;
}

?>