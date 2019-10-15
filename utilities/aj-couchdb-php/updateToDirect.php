<?php
/**
 * Created by IntelliJ IDEA.
 * User: vbakalov
 * Date: 4/13/2015
 * Time: 9:59 AM
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

$uuidsCSVFileName = 'input/a_directos_FPum_5_oct_2015.csv';

$outputCSVFileName = 'output/a_directos_FPum_5_oct_2015_processed.csv';
$outputCSVFileNameMissing = 'output/a_directos_FPum_5_oct_2015_notproceed.csv';

$uuidsAry = loadUUIDs($uuidsCSVFileName);

$updatedUUIDs = array();
$missingUUIDs = array();

update2CompleteRegistration($uuidsAry);

print2file($outputCSVFileName, $updatedUUIDs);
print2file($outputCSVFileNameMissing, $missingUUIDs);

echo "Collaterals sucessfully updated";

/**
 * @param $uuidsCSVFileName
 * @return array|null
 */
function loadUUIDs($uuidsCSVFileName) {
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
function update2CompleteRegistration($uuidsAry){
    global $missingUUIDs;

    foreach($uuidsAry as $uuid) {
        echo "Processing ".$uuid.". ";
        // get docid
        $docId = getDocIdByUUID($uuid);
        if ($docId != null) {
        	echo "UUID=".$uuid." OK.\n";
           updateCouchDoc($docId);
        } else{
            echo "UUID=".$uuid." Not processed.\n";
            array_push($missingUUIDs, $uuid);
        }
    }
}

function getDocIdByUUID($uuid)
{
    $docId = null;
    // DEV
//     $req_url = 'http://54.204.20.212:5984/coconut/_design/coconut/_view/byUUIDRegWitCollaterals?key=%22'.$uuid.'%22&include_docs=true';

    // PROD
    $req_url = 'http://107.20.181.244:5984/coconut/_design/coconut/_view/byUUIDRegWitCollaterals?key=%22'.$uuid.'%22&include_docs=true';

    $request = new Http_Request($req_url); // Create request object
    $request->setMethod(Http_Request_Data::METHOD_GET);
    $request->setHeader('Content-Type', 'application/json');
    $response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)
    $str = $response->getBody(); // Get body of http response (__toString() is a alias of it)
    $str = str_replace(array("\n", "\r", "\t"), '', $str);
    $str = trim(str_replace("'", "\"", $str));
    $json = json_decode($str, true);
    $rows = $json['rows'];

    if (count($rows) > 1) {
    	echo  "Has more than one registration form. ";
    	return  null;
    }

    // assume the first row is the registration
    if ( isset($rows[0]) && array_key_exists('id', $rows[0])) {
//     	var_dump($rows[0]);
        if ($rows[0]['doc']['Estecolateralparticipante'] == 'Directo' || $rows[0]['doc']['Estecolateralparticipante'] == 'No' ) {
			echo " Is already Direct. ";
			return null;
        }
        $docId = $rows[0]['id'];
    }

    if ($docId == null) {
    	echo " Not found. ";
    }
    return $docId;
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


function updateCouchDoc($docId){
    global $client, $updatedUUIDs;
    try {
        $doc = $client->getDoc($docId);
        $doc->lastModifiedAt = getCouchCurrentDate();
        $doc->system_updated_col = "true";
        $doc->Estecolateralparticipante = "No";

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