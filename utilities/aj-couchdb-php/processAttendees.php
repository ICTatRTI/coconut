<?php
/**
 * Created by IntelliJ IDEA.
 *
 * This program will parse input CSV file and depending of the Decision column will mark the documents as
 * complete, duplicate or deleted. To do that it will connect directly to couchDb using the PHP-on-Couch-master
 * library downloaded from GitHub. For more information on this library go here:
 *
 * http://www.ibm.com/developerworks/library/os-php-couchdb/#resources
 *
 *
 * User: vbakalov
 * Date: 10/31/2014
 * Time: 10:45 AM
 */

/****** LOCAL HOST *****************/
//$couch_dsn = "http://localhost:5984/";

/****** PRODUCTION **************/
//$couch_dsn = "http://107.20.181.244:5984/";

/****** DEVELOPMENT *****************/
$couch_dsn = "http://54.204.20.212:5984/";

/****** THESE NEED TO BE ADJUSTED *******/
$USER_NAME = "Digitador RTI";
$PROVIDER_ID = "6";
$PROVIDER_NAME = "IDDI (Santo Domingo#Distrito Nacional)";

$couch_db = "coconut";

require_once "./lib/couch.php";
require_once "./lib/couchClient.php";
require_once "./lib/couchDocument.php";

// open client connection with couchDB
$client = new couchClient($couch_dsn,$couch_db);


$activitiesCSVFileName = 'input/SantoDomingoActivitiesDEVWithDates.csv';
$inputCSVFileName = 'output/testAttendanceDEV2_WITH_UUIDS.csv';
$outputCSVFileName = 'output/testAttendanceDEV2_WITH_UUIDS_PROCESSED.csv';


//load existing in CouchDB activities
$couchActivities = loadCouchActivities();


$outputCSVAry = array();

$numProcessed = 0;

echo "START PROCESSING.....";

// set timezone to user timezone
date_default_timezone_set("EST");

$activitiesAry = loadActivities($activitiesCSVFileName);

$inputCSVAry = loadCSV($inputCSVFileName);

$sortedAry = array();

if ($inputCSVAry != null) {
    $activitiesAttendees = sortByActivityId($inputCSVAry);

    // assign participants to activities
    $status = processActivities($client, $activitiesAttendees, $couchActivities);
    if (!$status)
        exit(-1);

    //$outputCSVAry= updateDocs($inputCSVAry);
    //print2file($outputCSVFileName, $outputCSVAry);
}

echo $numProcessed."DATA SUCCESSFULLY PROCESSED....."."/n";


/**
 * Load the activities CSV file into array of records.
 *
 * [0][[ACTIVITY_NAME][PROVIDER_ID][ACTIVITY_ID]
 *
 * @param $inputCSVFileName
 */
function loadActivities($activitiesCSVFileName){
    $retAry = array();
    $cntLn = 0;
    $i = 0;

    // load PhenX data dictionary
    $file_handle = fopen($activitiesCSVFileName, "r");

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

        $lineAry['SERVICE_NAME'] = $lineOfText[0];
        $lineAry['SERVICE_DATE'] = $lineOfText[1];
        $lineAry['PROGRAM_NAME'] = $lineOfText[2];
        $lineAry['PROVIDER_ID'] = $lineOfText[3];
        $lineAry['ACTIVITY_ID'] = $lineOfText[4];

        $retAry[$i++] = $lineAry;
    }

    fclose($file_handle);

    return $retAry;

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
        $lineAry['UUID'] = $lineOfText[5];
        $lineAry['NOMBRE_DE_ACTIVIDAD'] = $lineOfText[19];
        $lineAry['TIPO'] = $lineOfText[20];
        $lineAry['ADMINISTRATOR_DE_CASSO'] = $lineOfText[21];
        $lineAry['PROGRAMA'] = $lineOfText[22];
        $lineAry['FECHA'] = $lineOfText[23];
        $lineAry['DESCRIPTION'] = $lineOfText[24];

        $activityId = getActivityId($lineAry['NOMBRE_DE_ACTIVIDAD'], $lineAry['FECHA']);
        if ($activityId != "")
            $lineAry['ACTIVITY_ID'] = $activityId;
        else
            $lineAry['ACTIVITY_ID'] = "ACTIVITY_NOT_FOUND";

        $retAry[$i++] = $lineAry;
    }

    fclose($file_handle);

    return $retAry;

}

/**
 *
 * @param $activitiesAttendees
 * @param $activities
 */
function processActivities($client, $activitiesAttendees, $couchActivities){
   foreach($activitiesAttendees as $activityId=>$attendees){
       if ($activityId === "ACTIVITY_NOT_FOUND"){
           continue;
       }else {
           $existingActivity = getActivity($couchActivities, strval($activityId));
           if ($existingActivity == null) {
               $status = createActivity($client, $activityId, $attendees);
               if (!$status){
                   echo "ERROR: when creating activity. Exiting...";
                   return false;
               }
           } else {
               $status = updateActivity($client, $existingActivity, $attendees);
               if (!$status){
                   echo "ERROR: when updating activity. Exiting...";
                   return false;
               }
           }
       }
    }
    return true;
}


/**
 * Create couchDB activity document and assign uuids to this activity.
 *
 */
function createActivity($client, $activityId, $activityAttendees){
    global $PROVIDER_ID, $PROVIDER_NAME,  $USER_NAME;

    try {
        $doc = new stdClass();
        $doc->activity_id = strval($activityId);
        $doc->activity_name = getActivityNamebyId($activityId);
        $doc->lastModifiedAt = getCouchCurrentDate();
        $doc->createdAt = getCouchCurrentDate();
        $doc->provider_id = $PROVIDER_ID;
        $doc->provider_name = $PROVIDER_NAME;
        $doc->rti_system_created = "true";
        $doc->question = "Attendance List";
        $doc->collection = "result";
        $doc->user_name = $USER_NAME;

        foreach($activityAttendees as $attendee){
          $uuid =  $attendee['UUID'];
          $doc->$uuid = "true";
        }

        // create document
       // $response = $client->storeDoc($doc);

    } catch (Exception $e) {
        echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        return false;
    }

    return true;

}

function updateActivity($client, $existingActivity, $activityAttendees){
    try {
        $doc = $client->getDoc($existingActivity->id);

        $doc->lastModifiedAt = getCouchCurrentDate();
        $doc->rti_system_updated = "true";

        foreach($activityAttendees as $attendee){
            $uuid =  $attendee['UUID'];
            $doc->$uuid = "true";
        }

        // update document
        $response = $client->storeDoc($doc);

    } catch (Exception $e) {
        echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        return false;
    }

    return true;

}


/**
 *
 */
function getActivity($couchActivities, $activityId){
    $retActivity = null;

    $rows = $couchActivities->rows;
    foreach($rows as $existingActivity){
       if ($existingActivity->key == $activityId){
           $retActivity = $existingActivity;
           break;
       }
   }

    return $retActivity;
}


/**
 * Converts from 9/1/2013 to 2014-01-31 date format.
 *
 * @param $inputDt
 */
function convertDate($inputDt){
    $outputDt = "";

    list($mm, $dd, $yyyy) = explode("/", $inputDt);
    if (strlen($dd) == 1)
        $dd = '0'.$dd;

    if (strlen($mm) == 1)
        $mm = '0'.$mm;

    $outputDt = $yyyy.'-'.$mm.'-'.$dd;

    return trim($outputDt);
}


/**
 * @param $activityName
 * @param $activityDate - the date is in format 9/1/2013
 */
function getActivityId($activityName, $activityDate){
  global $activitiesAry;
  $retActivityId = "";

  $activityName = trim($activityName);

  // Activity date is in format 2014-01-31 so need to convert $activityDate
  $convertedActDt = convertDate($activityDate);


  foreach($activitiesAry as $lineAry){
      $activityName2 = trim($lineAry['SERVICE_NAME']);
      $activityDt2 = trim($lineAry['SERVICE_DATE']);
      if (($activityName2 === $activityName) &&  ($activityDt2 === $convertedActDt)){
          $retActivityId = $lineAry['ACTIVITY_ID'];
          break;
      }
  }

    return $retActivityId;
}


/**
 * @param $activityName
 * @param $activityDate - the date is in format 9/1/2013
 */
function getActivityNameById($inputActivityId){
    global $activitiesAry;
    $retActivityName = "";


    foreach($activitiesAry as $lineAry){
        $activityId = trim($lineAry['ACTIVITY_ID']);

        if ($inputActivityId == $activityId ){
            $retActivityName = $lineAry['SERVICE_NAME'];
            break;
        }
    }

    return trim($retActivityName);
}



/**
 * Return array where first index is activity Id and second is array of activity data.
 *
 * @param $dataAry
 *
 */
function sortByActivityId($dataAry){
    $retAry = array();

    foreach($dataAry as $lineAry){
        if (array_key_exists($lineAry['ACTIVITY_ID'], $retAry)){
            array_push($retAry[$lineAry['ACTIVITY_ID']], $lineAry);
        }else{
            $retAry[$lineAry['ACTIVITY_ID']] = array();
            array_push($retAry[$lineAry['ACTIVITY_ID']], $lineAry);
        }
    }

    return $retAry;
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

function loadCouchActivities(){
    global $client;

    $data = $client->getView ( "coconut",  "findAttendanceByActivity");

    return $data;
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
    $status = true;

    try {
        $doc = new stdClass();
        $doc->_id = "in_the_meantime";
        $doc->lastModifiedAt = getCouchCurrentDate();

        // create document
        $response = $client->storeDoc($doc);

    } catch (Exception $e) {
        echo "Error: ".$e->getMessage()." (errcode=".$e->getCode().")\n";
        $status = false;
    }

    return $status;
}


/**
 *
 * Updates the document with the new values.
 *
 * @param $client
 * @param $newVals
 *
 */
function updateCouchDoc($client, $values){
    $status = true;

    try {
        $doc = $client->getDoc($values['_id']);

        if (array_key_exists('Completado', $values))
            $doc->Completado = "true";
        else
            $doc->question = $values['question'];

        $doc->lastModifiedAt = getCouchCurrentDate();

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

function print2file($outputCSVFileName, $outputCSV){
    $file = fopen($outputCSVFileName,"w");
    foreach ($outputCSV as  $line) {
        fwrite($file, $line);
        fwrite($file, "\r\n");
    }
    fclose($file);
}



?>