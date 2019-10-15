<?php
/**
 * Created by Claudia Nunez
 * Date: 7/20/2015
 *
 * This script is for completing incomplete surveys in a list of seperated values of UUID.
 * The script will report if:
 *  - The UUID does not exists
 *  - The UUID does not have an available survey to be completed
 *  - The UUID already has a completed survey
 */

function autoLoader($class)
{
    require_once('lib/http/' . str_replace('_', '/', $class) . '.php');
}

spl_autoload_register('autoLoader');


/****** PRODUCTION **************/
$couch_dsn = "http://107.20.181.244:5984/";

/****** DEVELOPMENT *****************/
// $couch_dsn = "http://54.204.20.212:5984/";

date_default_timezone_set("America/Santo_Domingo");
$couch_db = "coconut";

require_once "./lib/couch.php";
require_once "./lib/couchClient.php";
require_once "./lib/couchDocument.php";

// open client connection with couchDB
$client = new couchClient($couch_dsn,$couch_db);

$uuidsCSVFileName = 'input/encuestas_para_completar_Casa_Abierta_Nov1_2016.csv';

$outputCSVFileName = 'output/encuestas_para_completar_Casa_Abierta_Nov1_2016_complete.csv';
$outputCSVFileNameMissing = 'output/encuestas_para_completar_ICasa_Abierta_Nov1_2016_incomplete.csv';

$uuidsAry = loadUUIDs($uuidsCSVFileName);

$updatedUUIDs = array();
$missingUUIDs = array();

update2CompleteSurvey($uuidsAry);

print2file($outputCSVFileName, $updatedUUIDs);
print2file($outputCSVFileNameMissing, $missingUUIDs);

echo "Surveys sucessfully updated";

/**
 * @param $uuidsCSVFileName
 * @return array|null
 */
function loadUUIDs($uuidsCSVFileName){
    $retAry = array();
    $cntLn = 0;
    $i = 0;

    // load uuids
    $file_handle = fopen($uuidsCSVFileName, "r");

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

        $uuid = $lineOfText[0];
        if ($uuid == "")
            break;
        // remove the leading '?' character
        //$retAry[$i++] = substr($uuid,1);
        $retAry[$i++] = $uuid;
    }

    fclose($file_handle);

    return $retAry;

}

/**
 * @param $uuidsAry
 */
function update2CompleteSurvey($uuidsAry) {
    global $missingUUIDs;

    foreach($uuidsAry as $uuid) {
        echo "Processing ".$uuid;
        // get docid
        $docId = getDocIdByUUID($uuid);
        //echo "The docID is ".$docId."\n";
        if ($docId != null) {
        	echo ". To update\n";
           updateCouchDoc($docId);
        } else{
            echo " NOT PROCESSED.\n";
            array_push($missingUUIDs, $uuid);
        }
    }


}

function getDocIdByUUID($uuid)
{
    $docId = null;
    // DEV
    $req_url = 'http://54.204.20.212:5984/coconut/_design/coconut/_view/byUUID?key=%22'.$uuid.'%22&include_docs=true';
    $req_url_if_exits = 'http://54.204.20.212:5984/coconut/_design/coconut/_view/byUUIDForReportActions?key=%22'.$uuid.'%22&include_docs=true';

    // PROD
//     $req_url = 'http://107.20.181.244:5984/coconut/_design/coconut/_view/byUUID?key=%22'.$uuid.'%22&include_docs=true';
//     $req_url_if_exits = 'http://107.20.181.244:5984/coconut/_design/coconut/_view/byUUIDForReportActions?key=%22'.$uuid.'%22&include_docs=true';

    $request = new Http_Request($req_url_if_exits); // Create request object
    $request->setMethod(Http_Request_Data::METHOD_GET);
    $request->setHeader('Content-Type', 'application/json');
    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)
    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)
    $str = str_replace(array("\n", "\r", "\t"), '', $str);
    $str = trim(str_replace("'", "\"", $str));
    $json = json_decode($str, true);

    $rows = $json['rows'];
    $hasSurvey = false;
    foreach($rows as $doc) {
    	if ($doc['doc']['question'] === 'Participant Survey-es')
    		$hasSurvey = true;
       		echo " Has a survey ALREADY.";
       		return null;
    }

    if (!$hasSurvey) {
	    $request = new Http_Request($req_url); // Create request object
	    $request->setMethod(Http_Request_Data::METHOD_GET);
	    $request->setHeader('Content-Type', 'application/json');
	    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)
	    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)
	    $str = str_replace(array("\n", "\r", "\t"), '', $str);
	    $str = trim(str_replace("'", "\"", $str));
	    $json = json_decode($str, true);

		$rows = $json['rows'];
		$surveys = array();
		$lastModifiedTime = array();
    	foreach($rows as $doc) {
	    	if ($doc['doc']['question'] === 'Participant Survey-es')
				array_push($surveys, array('question' => $doc['doc']['question'],
											'uuid' => $doc['doc']['uuid'],
											'lastModifiedAt' => $doc['doc']['lastModifiedAt'],
											'id' => $doc['id']
				));
    	}

	    foreach ($surveys as $key => $row) {
	    	$lastModifiedTime[$key]  = $row['lastModifiedAt'];
		}
    	array_multisort($lastModifiedTime, SORT_ASC, $surveys);
// 		var_dump($surveys);
		$lastRecentlyUpdatedSurvey = end($surveys);
		if (!isset($lastRecentlyUpdatedSurvey['id'])) {
			echo ". UUID does not exist, or has no Survey to Complete.";
			return null;
		}
   	 	return $lastRecentlyUpdatedSurvey['id'];
    }

}

/**
 * Date need to  be in format 2014-10-23T16:25:16-04:00.
 */
function getCouchCurrentDate() {
    $retDateStr = "";

    // get current date
    $dt = new DateTime();
    $s1 = $dt->format('Y-m-d H:i:s');
    $s2 = substr($s1, 0, 10);
    $s3 = substr($s1, 11);

    $retDateStr = $s2.'T'.$s3.'-04:00';

    return $retDateStr;
}


function updateCouchDoc($docId) {
    global $client, $updatedUUIDs;
    try {
        $doc = $client->getDoc($docId);

        $doc->lastModifiedAt = getCouchCurrentDate();
        $doc->system_updated_col = "true";
        $doc->Completado = "true";

        // update document
        $response = $client->storeDoc($doc);

    } catch (Exception $e) {
        echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        return false;
    }

    array_push($updatedUUIDs,$doc->uuid);
    return true;

}


function print2file($outputCSVFileName, $udatedUUIDs){

    $file = fopen($outputCSVFileName,"w");
    foreach ($udatedUUIDs as  $uuid) {
        fwrite($file, $uuid);
        fwrite($file, "\r\n");
    }
    fclose($file);
}

?>