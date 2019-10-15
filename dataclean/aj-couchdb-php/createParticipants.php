<?php
/**
 * Created by IntelliJ IDEA.
 *
 * This will read CSV file of participants and create them in the system. It will produce the
 * same file but with updated UUIDs.
 *
 * User: vbakalov
 * Date: 11/11/2014
 * Time: 8:57 AM
 */

$UUIDS_DOC_ID = "cf23a4757eec57188f66c5c92503343c";
$PROVIDER_ID = "6";
$PROVIDER_NAME = "IDDI (Santo Domingo#Distrito Nacional)";

/****** LOCAL HOST *****************/
//$couch_dsn = "http://localhost:5984/";

/****** PRODUCTION **************/
//$couch_dsn = "http://107.20.181.244:5984/";

/****** DEVELOPMENT *****************/
$couch_dsn = "http://54.204.20.212:5984/";

$couch_db = "coconut";

require_once "./lib/couch.php";
require_once "./lib/couchClient.php";
require_once "./lib/couchDocument.php";

// open client connection with couchDB
$client = new couchClient($couch_dsn,$couch_db);

// test update couch doc
//testUpdateCouchDoc($client);


$inputCSVFileName = 'input/testAttendanceDEV1.csv';
$outputCSVFileName = 'output/testAttendanceDEV1_WITH_UUIDS.csv';



$outputCSVAry = array();

$numProcessed = 0;

echo "START PROCESSING.....";

// set timezone to user timezone
date_default_timezone_set("EST");

// loads array of uuids
$uuids = loadUUIDs($client, $UUIDS_DOC_ID);

if($uuids === null){
    echo "ERROR: Could not load uuids...";
    exit(-1);
}

// load CSV file
$inputCSVAry = loadCSV($inputCSVFileName);

if ($inputCSVAry != null) {
    // create participants
    $dataWithUUIDS = createParticipants($inputCSVAry);

    // generate updated with UUIDs CSV output CSV
    print2file($outputCSVFileName, $dataWithUUIDS);
}

if (updateUUIDsDoc($client, $uuids, $UUIDS_DOC_ID) === true){
    echo $numProcessed." RECORDS SUCCESSFULLY PROCESSED....."."/n";
}else{
    echo "ERROR: Could not update uuids...";
    exit(-1);
}







/**
 *
 * Loads unique uuids from  couchDB document.
 *
 * @param $client
 * @param $newVals
 *
*/
function loadUUIDs($client, $docId){
        $uuidsAry = array();


        try {
            $doc = $client->getDoc($docId);
            $uuidsAry = explode(",", $doc->uuids);

        } catch (Exception $e) {
            if ( $e->code() == 404 ) {
                echo "Document not found\n";
            } else {
                echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
            }
            $uuidsAry = null;
        }

        return $uuidsAry;
}


/**
 * Load the input CSV file into array of records.
 *
 * @param $inputCSVFileName
 */
function loadCSV($inputCSVFileName){
    $retAry = array();
    $cntLn = 0;
    $i = 0;

    // load PhenX data dictionary
    $file_handle = fopen($inputCSVFileName, "r");

    if ($file_handle == false)
        return null;

    while (!feof($file_handle) ) {
        $lineAry = array();

        $lineOfText = fgetcsv($file_handle, 2048);

        // skip the header
        if ($cntLn === 0){
            $cntLn++;
            continue;
        }

        if ($lineOfText[0] == null && $lineOfText[1] == null && $lineOfText[2] == null)
            break;

        // load line
        $lineAry['USER_NAME'] = $lineOfText[0];
        $lineAry['REGISTRATION DATE'] = $lineOfText[1];
        $lineAry['NOMBRE'] = $lineOfText[2];
        $lineAry['APELLIDO'] = $lineOfText[3];
        $lineAry['NOMBRE_COMPLETADO'] = $lineOfText[4];
        $lineAry['UUID'] = $lineOfText[5];
        $lineAry['APODO'] = $lineOfText[6];
        $lineAry['CALLE_Y_NUMERO'] = $lineOfText[7];
        $lineAry['PROVINCIA'] = $lineOfText[8];
        $lineAry['MUNICIPIO'] = $lineOfText[9];
        $lineAry['BARRIO'] = $lineOfText[10];
        $lineAry['ES_COLATERAL'] = $lineOfText[11];
        $lineAry['DIA'] = $lineOfText[12];
        $lineAry['MES'] = $lineOfText[13];
        $lineAry['ANO'] = $lineOfText[14];
        $lineAry['SEXO'] = $lineOfText[15];
        $lineAry['CELULAR'] = $lineOfText[16];
        $lineAry['CASA'] = $lineOfText[17];
        $lineAry['CORREO_ELECTONICO'] = $lineOfText[18];
        $lineAry['NOMBRE_DE_ACTIVIDAD'] = $lineOfText[19];
        $lineAry['TIPO'] = $lineOfText[20];
        $lineAry['ADMINISTRATOR_DE_CASSO'] = $lineOfText[21];
        $lineAry['PROGRAMA'] = $lineOfText[22];
        $lineAry['FECHA'] = $lineOfText[23];
        $lineAry['DESCRIPTION'] = $lineOfText[24];

        $retAry[$i++] = $lineAry;
    }

    fclose($file_handle);

    return $retAry;

}

/**
 * Iterate the array and create missing participants.
 *
 * @param $dataAry
 */
function createParticipants($dataAry){
    global $client,  $numProcessed;
    $createdParticipants = array();
    $dataWithUUIDS = array();
    $idx = 0;

    foreach($dataAry as $valAry) {
        //if uuid exist no ned to create one
        if ($valAry['UUID'] != "") {
            $dataWithUUIDS[$idx++] = $valAry;
            $numProcessed++;
            echo $numProcessed." processed\n";
            continue;
        }

        $uuid = getUUIDfromCreated($createdParticipants, $valAry);
        if ($uuid != null){
            // update uuid and continue
            $valAry['UUID'] = $uuid;
            $dataWithUUIDS[$idx++] = $valAry;
            $numProcessed++;
            echo $numProcessed." processed\n";
            continue;
        }


        // create participant
        $uuid = createParticipant($client, $valAry);

        if ($uuid != null) {
            $valAry['UUID'] = $uuid;
            array_push($createdParticipants, $valAry);
            $dataWithUUIDS[$idx++] = $valAry;
            echo $numProcessed." processed\n";
            $numProcessed++;
        }else{
            echo "ERROR: creating participant. Exiting....";
            exit(-1);
        }

    }

    return $dataWithUUIDS;
}


/**
 * Iterates through the array of created participants and return UUID if found, otherwise null.
 *
 * @param $createdParticipants
 */
function getUUIDfromCreated($createdParticipants, $participantData){
  foreach($createdParticipants as $key => $val) {
      if ($val['NOMBRE'] === $participantData['NOMBRE'] &&
          $val['APELLIDO'] === $participantData['APELLIDO'] &&
          $val['DIA'] === $participantData['DIA'] &&
          $val['MES'] === $participantData['MES'] &&
          $val['ANO'] === $participantData['ANO'] &&
          $val['PROVINCIA'] === $participantData['PROVINCIA'] &&
          $val['MUNICIPIO'] === $participantData['MUNICIPIO'] &&
          $val['BARRIO'] === $participantData['BARRIO']) {

          // found same participant, return uuid
          return $val['UUID'];
      }
  }

    // not found
    return null;
}

/**
 * Date need to  be in format 2014-10-23T16:25:16-04:00.
 */
function getCouchCurrentDate(){
    $retDateStr = "";

    // get current date
    $dt = new DateTime();
    $s1 = $dt->format('Y-m-d H:i:s');
    $s2 = substr($s1, 0, 10);
    $s3 = substr($s1, 11);

    $retDateStr = $s2.'T'.$s3.'-04:00';

    return $retDateStr;
}


/**
 *
 * Create participant.
 *
 * @param $client
 * @param $newVals
 *
 */
function createParticipant($client, $values){
    global $uuids, $PROVIDER_ID, $PROVIDER_NAME;

    $uuid = array_pop($uuids);

    if ($uuid === "")
      $uuid = array_pop($uuids);

    if ($uuid == NULL )
        return null;

    try {
        $doc = new stdClass();
        /**** Initialize system fields ****/
        //$doc->_id = "in_the_meantime";
        $doc->lastModifiedAt = getCouchCurrentDate();
        $doc->createdAt = getCouchCurrentDate();
        $doc->uuid = $uuid;
        $doc->provider_id = $PROVIDER_ID;
        $doc->provider_name = $PROVIDER_NAME;
        $doc->rti_system_created = "true";
        $doc->question = "Participant Registration-es";
        $doc->collection = "result";
        $doc->Completado = "true";

        /**** Initialize data fields ****/
        $isColateral = $values['ES_COLATERAL'];

        $pos = strrpos($isColateral, 'S¡');
        if ($pos !== false || (strrpos($isColateral, 'Indirecto') !== false)){
            $doc->Estecolateralparticipante = "Sí";
        }

        $doc->Celular = $values['CELULAR'];
        $doc->Apellido = $values['APELLIDO'];
        $doc->Apodo = $values['APODO'];
        $doc->Año = $values['ANO'];
        $doc->BarrioComunidad = $values['BARRIO'];
        $doc->Calleynumero = $values['CALLE_Y_NUMERO'];
        $doc->Casa = $values['CASA'];


        $email = strtolower($values['CORREO_ELECTONICO']);
        $pos = strrpos($email, 'colateral');
        if ($pos === false){
            $doc->Direccióndecorreoelectrónico = $values['CORREO_ELECTONICO'];
        }

        $doc->Día = $values['DIA'];
        $doc->Fecha = getCouchCurrentDate();
        $doc->Mes = $values['MES'];
        $doc->Municipio = $values['MUNICIPIO'];
        $doc->Nombre = $values['NOMBRE'];
        $doc->Provincia = $values['PROVINCIA'];
        $doc->Sexo = strtoupper($values['SEXO']);

        // the values below are not provided in the input CSV
        $doc->Nombredepersonadecontacto = "";
        $doc->NombredeusuariodeFacebook = "";
        $doc->Parentescoopersonarelacionada = "";
        $doc->Teléfono = "";
        $doc->Tieneunadireccióndecorreoelectrónico = "";
        $doc->TieneunnombredeusuariodeFacebook = "";
        $doc->Tieneunnumerocelular = "";
        $doc->Tieneunnumerodetelefonoenlacasa = "";


        // create document
        $response = $client->storeDoc($doc);

    } catch (Exception $e) {
        echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        return null;
    }

    return $uuid;
}


/**
 *
 * Updates the document with the new values.
 *
 * @param $client
 * @param $newVals
 *
 */
function updateUUIDsDoc($client, $uuidsAry, $docId){
    $status = true;

    try {
        $doc = $client->getDoc($docId);
        $doc->lastModifiedAt = getCouchCurrentDate();

        $commaSeparatedUuids = implode(",", $uuidsAry);
        $doc->uuids = $commaSeparatedUuids;

        $response = $client->storeDoc($doc);
    } catch (Exception $e) {
        if ( $e->code() == 404 ) {
            echo "Document not found\n";
        } else {
            echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        }
        $status = false;
    }

    return $status;
}

/**
 * Output file with updated UUIDs.
 *
 * @param $outputCSVFileName
 * @param $dataAry
 */
function print2file($outputCSVFileName, $dataAry){
    $file = fopen($outputCSVFileName,"w");
    // write header
    fwrite($file, "user name,Registration Date,Nombre,Apellido,Nombre completo,UUID match with Registration Form Database,Apodo,Calle y numero,Provincia,Municipio,Barrio o comunidad,Es colateral? S/N,Dia,Mes,A¤o,Sexo: M/F,Celular,Casa,Correo electronico,Nombre de actividad,Tipo,Administrator de casso,Programa,Fecha,Descripcion");
    fwrite($file, "\r\n");
    foreach ($dataAry as  $lineAry) {
        $line = $lineAry['USER_NAME'].','.$lineAry['REGISTRATION DATE'].','.$lineAry['NOMBRE'].','.$lineAry['APELLIDO'].','.$lineAry['NOMBRE_COMPLETADO']
            .','.$lineAry['UUID'].','.$lineAry['APODO'].','.$lineAry['CALLE_Y_NUMERO'].','.$lineAry['PROVINCIA'].','.$lineAry['MUNICIPIO'].','.$lineAry['BARRIO']
            .','.$lineAry['ES_COLATERAL'].','.$lineAry['DIA'].','.$lineAry['MES'].','.$lineAry['ANO'].','.$lineAry['SEXO'].','.$lineAry['CELULAR']
            .','.$lineAry['CASA'].','.$lineAry['CORREO_ELECTONICO'].',"'.$lineAry['NOMBRE_DE_ACTIVIDAD']
            .'","'.$lineAry['TIPO'].'","'.$lineAry['ADMINISTRATOR_DE_CASSO'].'","'.$lineAry['PROGRAMA'].'",'.$lineAry['FECHA'].',"'.$lineAry['DESCRIPTION'].'"';


        fwrite($file, $line);
        fwrite($file, "\r\n");
    }
    fclose($file);
}




?>