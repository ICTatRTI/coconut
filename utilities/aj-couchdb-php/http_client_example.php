<?php
/**
*	Http Client
*	Copyright (C) 2013-2014  Norbert Krzysztof Kiszka <norbert at linux dot pl>
*	This program is free software; you can redistribute it and/or modify
*	it under the terms of the GNU General Public License as published by
*	the Free Software Foundation; either version 2 of the License, or
*	(at your option) any later version.
*
*	This program is distributed in the hope that it will be useful,
*	but WITHOUT ANY WARRANTY; without even the implied warranty of
*	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*	GNU General Public License for more details.
*
*	You should have received a copy of the GNU General Public License along
*	with this program; if not, write to the Free Software Foundation, Inc.,
*	51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
* 
*	@category Http Client
*	@package Http Client
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/*
*	Http Client - example of usage - get and print latest entries from service phpclasses (rss) to stdout.
*	Read included howto.
*/

function autoLoader($class)
{
	require_once('lib/http/' . str_replace('_', '/', $class) . '.php');
}

spl_autoload_register('autoLoader');

echo "\nPlease wait. Loading data..."; // it takes some time


$str = "{'rows':[
{'id':'3efddc11f12f529140535a93da514115','key':'Participant Registration-es:true:2015-02-28T12:01:26-05:00','value':null},
{'id':'3efddc11f12f529140535a93da510758','key':'Participant Registration-es:true:2015-02-28T11:54:40-05:00','value':null},
{'id':'3efddc11f12f529140535a93da50d04f','key':'Participant Registration-es:true:2015-02-28T11:50:09-05:00','value':null},
]}";


//$str = "{'id':'3efddc11f12f529140535a93da514115','key':'Participant Registration-es:true:2015-02-28T12:01:26-05:00','value':null}";


/*$str = str_replace(array("\n", "\r", "\t"), '', $str);

$str = trim(str_replace("'", "\"", $str));

$jsontest =json_decode($str, true);

var_dump(json_decode($str, true));



exit(0);*/

// Get lates entries on phpclasses

//$request = new Http_Request('http://localhost:5984/coconut/_design/coconut/getUUID.html'); // Create request object

$req_url = 'http://54.204.20.212:5984/coconut/_design/coconut/_view/resultsByQuestionAndComplete?startkey=%22Participant%20Registration-es%3Atrue%3Az%22&endkey=%22Participant%20Registration-es%3Atrue%22&descending=true&include_docs=false';
//$req_url = 'http://localhost:5984/coconut/_design/coconut/getUUID.html';
//$req_url = 'http://localhost:5984/coconut/_design/coconut/index.html#new/result/Participant Registration-es/provider_id=18&user_name=vbakalov&provider_name=Casa Abierta';

$request = new Http_Request($req_url); // Create request object
$request->setMethod(Http_Request_Data::METHOD_GET);
$request->setHeader('Content-Type', 'application/json');

$response = $request->getResponse(); // Send request and get response object (method send() is called automatically in getResponse() when wasn't before)

$str = $response->getBody(); // Get body of http response (__toString() is a alias of it)


$str = str_replace(array("\n", "\r", "\t"), '', $str);
$str = trim(str_replace("'", "\"", $str));


$json = json_decode($str, true);



// Shortcuts:
// $xml = (new Http_Request('http://feeds.feedburner.com/phpclasses-xml?format=xml'))->getResponse()->getBody()
// $xml = Http_Client::get('feeds.feedburner.com/phpclasses-xml?format=xml')->getBody();
// $xml = Http_Client::get_('feeds.feedburner.com/phpclasses-xml?format=xml');

exit(0);

// -----------------------------

// Parse and output it to stdout

$reader = new XMLReader;

$reader->xml($xml);

echo "\rLatest entries in phpclasses readed from rss. Example code of Http Client (read included html howto) and XMLReader usage.\n\n---------------------------------------------------------------------------------------------------------------------\n\n";

while($reader->read())
{	
	if($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'item')
	{
		$outer = $reader->readOuterXML();
		$outerReader = new XMLReader;
		$outerReader->xml($outer);
		while($outerReader->read())
		{
			if($outerReader->nodeType == XMLReader::ELEMENT)
			{
				switch($outerReader->name)
				{
					case 'title':
						echo 'Title:        ' .  $outerReader->readString() . "\n";
					break;
					
					case 'feedburner:origLink':
						echo 'Direct link:  ' .  $outerReader->readString() . "\n";
						echo "\n---------------------------------------------------------------------------------------------------------------------\n\n";
					break;
				}
				
				
				
			}
		}
	}
}