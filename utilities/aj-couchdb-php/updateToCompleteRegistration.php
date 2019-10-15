<?php
/**
 * Created by Claudia Nunez
 * Date: 8/04/2015
 *
 * This script is for completing incomplete Registrations. It expects a list of UUID in a CSV file.
 * The script will report if:
 *  - The UUID does not exists
 *  - The UUID does not have an available registration to be completed
 *  - The UUID already has a completed registration
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

$uuidsCSVFileName = 'input/a_completar_encuestas_sin_registro_2016.csv';

$outputCSVFileName = 'output/a_completar_encuestas_sin_registro_2016_processed.csv';
$outputCSVFileNameMissing = 'output/a_completar_encuestas_sin_registro_2016_missingUUIDs.csv';

$uuidsAry = loadUUIDs($uuidsCSVFileName);

$updatedUUIDs = array();
$missingUUIDs = array();

update2CompleteRegistration($uuidsAry);

print2file($outputCSVFileName, $updatedUUIDs);
print2file($outputCSVFileNameMissing, $missingUUIDs);

echo "Registration sucessfully updated";

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
function update2CompleteRegistration($uuidsAry) {
    global $missingUUIDs;

    foreach($uuidsAry as $uuid) {
        echo "Processing ".$uuid;
        // get docid
        $docId = getDocIdByUUID($uuid);
        //echo "The docID is ".$docId."\n";
        if ($docId != null) {
        	echo ". To update \n";
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
//     $req_url = 'http://54.204.20.212:5984/coconut/_design/coconut/_view/byUUID?key=%22'.$uuid.'%22&include_docs=true';
//     $req_url_if_exits = 'http://54.204.20.212:5984/coconut/_design/coconut/_view/byUUIDRegWitCollaterals?key=%22'.$uuid.'%22&include_docs=true';

    // PROD
    $req_url = 'http://107.20.181.244:5984/coconut/_design/coconut/_view/byUUID?key=%22'.$uuid.'%22&include_docs=true';
    $req_url_if_exits = 'http://107.20.181.244:5984/coconut/_design/coconut/_view/byUUIDRegWitCollaterals?key=%22'.$uuid.'%22&include_docs=true';

    $request = new Http_Request($req_url_if_exits); // Create request object
    $request->setMethod(Http_Request_Data::METHOD_GET);
    $request->setHeader('Content-Type', 'application/json');
    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)
    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)
    $str = str_replace(array("\n", "\r", "\t"), '', $str);
    $str = trim(str_replace("'", "\"", $str));
    $json = json_decode($str, true);

    $rows = $json['rows'];
    $hasRegistration = false;
    foreach($rows as $doc) {
    	if ($doc['doc']['question'] === 'Participant Registration-es')
    		$hasRegistration = true;
       		echo " Has a Registration ALREADY.";
       		return null;
    }

    if (!$hasRegistration) {
	    $request = new Http_Request($req_url); // Create request object
	    $request->setMethod(Http_Request_Data::METHOD_GET);
	    $request->setHeader('Content-Type', 'application/json');
	    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)
	    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)
	    $str = str_replace(array("\n", "\r", "\t"), '', $str);
	    $str = trim(str_replace("'", "\"", $str));
	    $json = json_decode($str, true);

		$rows = $json['rows'];
		$registration = array();
		$lastModifiedTime = array();
    	foreach($rows as $doc) {
	    	if ($doc['doc']['question'] === 'Participant Registration-es')
				array_push($registration, array('question' => $doc['doc']['question'],
											'uuid' => $doc['doc']['uuid'],
											'lastModifiedAt' => $doc['doc']['lastModifiedAt'],
											'id' => $doc['id']
				));
    	}

	    foreach ($registration as $key => $row) {
	    	$lastModifiedTime[$key]  = $row['lastModifiedAt'];
		}
    	array_multisort($lastModifiedTime, SORT_ASC, $registration);
// 		var_dump($surveys);
		$lastRecentlyUpdatedRegistration = end($registration);
		if (!isset($lastRecentlyUpdatedRegistration['id'])) {
			echo ". UUID does not exist, or has no Registration to Complete.";
			return null;
		}
   	 	return $lastRecentlyUpdatedRegistration['id'];
    }


//     if ( isset($rows[0]) && array_key_exists('id', $rows[0])) {
//         // assume the first row is the registration
//         $docId = $rows[0]['id'];
//     }


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