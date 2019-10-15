<?php
/**
 * Created by IntelliJ IDEA.
 * User: vbakalov
 * Date: 2/17/2015
 * Time: 3:53 PM
 */


function autoLoader($class)
{
    require_once('lib/http/' . str_replace('_', '/', $class) . '.php');
}

spl_autoload_register('autoLoader');



$couch_dsn = "http://107.20.181.244:5984/";

$couch_db = "coconut";

require_once "./lib/couch.php";
require_once "./lib/couchClient.php";
require_once "./lib/couchDocument.php";

// open client connection with couchDB
$client = new couchClient($couch_dsn,$couch_db);

$outputCSVFileName = 'output/updatedSurveys_PROCESSED.csv';
$surveysCSVFileName = 'input/surveysNoprovider2.csv';
$outputCSVAry = array();

echo "\nPlease wait. Processing..."; // it takes some time

$numProcessed = 0;

$surveyAry = loadSurveys($surveysCSVFileName);

// retrieve surveys
//$surveys =  getSurveys();

// retrieve regs by uuid
//$regsByUUD = getRegsByUUID();

// update missing provider id
//processSurveys($client, $surveys, $regsByUUD);

updateSurveys($surveyAry);

//print2file($outputCSVFileName, $outputCSVAry);

echo $numProcessed."DATA SUCCESSFULLY PROCESSED....."."/n";


/**
 * Load the surveys CSV file into array of records.
 *
 *
 * @param $inputCSVFileName
 */
function loadSurveys($surveysCSVFileName){
    $retAry = array();
    $cntLn = 0;
    $i = 0;

    // load PhenX data dictionary
    $file_handle = fopen($surveysCSVFileName, "r");

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

        $lineAry['id'] = $lineOfText[0];
        $lineAry['uuid'] = $lineOfText[1];
        $lineAry['provider_id'] = $lineOfText[2];
        $lineAry['provider_name'] = $lineOfText[3];


        $retAry[$i++] = $lineAry;
    }

    fclose($file_handle);

    return $retAry;

}

function updateSurveys($surveyAry){
    global $client, $numProcessed;

    foreach($surveyAry as $s){
       $status = updateSurvey($client,$s['id'], $s['provider_id'], $s['provider_name']);
        if ($status){
            $numProcessed++;
            echo 'Number processed:'.$numProcessed."\n";

        }else{
            echo 'ERROR for UUID:'.$s['uuid']."\n";
        }
    }
}


/**
 * Retrieves surveys JSON documents from the couchDB.
 *
 * @return mixed
 */

function getSurveys()
{
    $req_url = 'http://localhost:5984/coconut/_design/coconut/_view/resultsByQuestionAndComplete?startkey=%22Participant%20Survey-es%3Atrue%3Az%22&endkey=%22Participant%20Survey-es%3Atrue%22&descending=true&include_docs=false';

    $request = new Http_Request($req_url); // Create request object
    $request->setMethod(Http_Request_Data::METHOD_GET);
    $request->setHeader('Content-Type', 'application/json');

    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)

    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)


    $str = str_replace(array("\n", "\r", "\t"), '', $str);
    $str = trim(str_replace("'", "\"", $str));


    $json = json_decode($str, true);

    return $json;
}



/**
 * Retrieves registrations by UUID from  couchDB.
 *
 * @return mixed
 */
function getRegsByUUID()
{
    $req_url = 'http://localhost:5984/coconut/_design/coconut/_view/byUUIDRegsNoValue';

    $request = new Http_Request($req_url); // Create request object
    $request->setMethod(Http_Request_Data::METHOD_GET);
    $request->setHeader('Content-Type', 'application/json');

    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)

    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)


    $str = str_replace(array("\n", "\r", "\t"), '', $str);
    $str = trim(str_replace("\"{", '{', $str));
    $str = trim(str_replace("}\"", '}', $str));
    $str = trim(str_replace("null", '\"null\"', $str));
    $str = trim(str_replace("\\", '', $str));
    $str = trim(str_replace("\"\"null\"\"", '"null"', $str));


    $json = json_decode($str, true);

    $regsByUUDs = array();

    // from json create array where the index is the uuid and the value is the doc id
    foreach ($json['rows'] as $row){
        if ($row['key'] != "null")
          $regsByUUDs[$row['key']] = $row['id'];
    }

    return $regsByUUDs;
}

/**
 * Update survey provider id and name if missing.
 *
 * @param $client
 * @param $surveys
 * @param $regsByUUD
 */
function processSurveys($client, $surveys, $regsByUUD){
    global $outputCSVAry, $numProcessed;

    $outputCSVLine = 'id, uuid, provider_id, provider_name';

    array_push($outputCSVAry,$outputCSVLine);

    // iterate the docs
    $rows = $surveys['rows'];
    foreach($rows as $r){

        // open the doc to get the uuid
        $docSurvey = openDoc($client, $r['id']);

        echo 'Processing '.$docSurvey->uuid.'/n';

        //check if provider id is undefined
        $providerId = $docSurvey->provider_id;
        if ($providerId == "undefined" || $providerId == null ||  $providerId == '' || $providerId == "null"){
            $surveyUUID = $docSurvey['uuid'];
            if (array_key_exists($surveyUUID, $regsByUUD)){
                // open the reggistration to get the provider info
                $docReg = openDoc($client, $regsByUUD[$surveyUUID]);

                // update the survey with the provider info
               //$docSurvey =  updateSurvey($client,$docSurvey['_id'], $docReg);
               $line =$docSurvey['_id'].','.$docSurvey['uuid'].','.$docSurvey['provider_id'].','.$docSurvey['provider_name'];
               array_push($outputCSVAry, $line);
               $numProcessed++;
            }

        }
    }
}

/**
 *
 * Updates the document with the new values.
 *
 * @param $client
 * @param $newVals
 *
 */
function updateSurvey($client,$surveyDocId, $provider_id, $provider_name){
    $status = true;

    try {
        $doc = $client->getDoc($surveyDocId);

        $doc->lastModifiedAt = getCouchCurrentDate();
        $doc->provider_id = $provider_id;
        $doc->provider_name = $provider_name;
        $doc->provider_updated = "true";


        // update survey document
        $response = $client->storeDoc($doc);


    } catch (Exception $e) {
        if ( $e->code() == 404 ) {
            echo "Document not found\n";
        } else {
            echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        }
        return null;
    }

    return true;
}


/**
 *
 * Open the registration document.
 *
 * @param $client
 * @param $newVals
 *
 */
function openDoc($client,$id){
    $doc=null;

    try {
        $doc = $client->getDoc($id);




    } catch (Exception $e) {
        if ( $e->code() == 404 ) {
            echo "Document not found\n";
        } else {
            echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        }
        return null;
    }

    return $doc;
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


function print2file($outputCSVFileName, $outputCSV){
    $file = fopen($outputCSVFileName,"w");
    foreach ($outputCSV as  $line) {
        fwrite($file, $line);
        fwrite($file, "\r\n");
    }
    fclose($file);
}