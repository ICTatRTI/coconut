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
*	@version 0.5.1
*/

/**
*	Http client - history handler.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Client
*	@version 0.5.1
*/
class Http_Client_History
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Stored history
	*	@var array
	*/
	protected static $_history = [];
	
	/**
	*	Cached request data methods ex.: [2 => 'GET'].
	*	@note 'DATA_METHOD_' is trimmed
	*/
	protected static $_request_data_methods = [];
	
	/**
	*	Get stored history
	*	@return array
	*/
	public static function getHistory()
	{
		return static::$_history;
	}
	
	/**
	*	Init class - prepare array with DATA_METHOD_* constants from Http_Request class into $_request_data_methods
	*	and call FS::defineDirConstants().
	*	@note 'DATA_METHOD_' will be trimmed from beginning
	*	@return void
	*/
	public static function init()
	{
		FS::defineDirConstants();
		
		$constants = (new ReflectionClass('Http_Request'))->getConstants();
		
		foreach($constants as $k => $v)
			if(substr($k, 0, 12) === 'DATA_METHOD_')
				self::$_request_data_methods[$v] = substr($k, 12);
	}
	
	/**
	*	Add request from Http_Client
	*	@param Http_Request_Interface $request
	*	@param int|float $time time when request was writted into socket
	*	@return void
	*/
	public static function addRequest(Http_Request_Interface $request, $time)
	{
		if(!($requestId = $request->getRequestId()))
			throw new Http_Client_History_Exception('Cant add request that wasn\'t sent');
		
		$data_method = $request->getDataMethod();
		
		//$pid = posix_getpid(); // get pid everytime because this process can be a sub-thread (fork or pthread)

        $pid = getmypid();
		$addedRequest = 
		[
			'pid' => $pid,
			'time' => $time,
			'url' => $request->getUrlWithUserCredentials(),
			'addr' => $request->getAddr(),
			'data_method' => static::$_request_data_methods[$data_method],
			'data_method_bitwise' => $data_method,
			'request_line' => $request->getRequestLine(),
			'request_header' => $request->getRequestHeadersString()
		];
		
		static::$_history[$requestId] = $addedRequest;
		
		static::_storeHistoryLog('request', $addedRequest, $requestId);
	}
	
	/**
	*	Add response parser from Http_Client
	*	@param Http_Response_Parser $responseParser
	*	@param int $requestId
	*/
	public static function addResponseParser(Http_Response_Parser $responseParser, $requestId)
	{
		if(!isset(static::$_history[$requestId]))
			throw new Http_Client_History_Exception('WTF? (What a Terrible Failure)');
		
		static::$_history[$requestId] += 
		[
			'status' => $responseParser->getStatus(),
			'status_raw' => $responseParser->getStatusRaw(),
			'response_header' => $responseParser->getHeaderSource()
		];
		
		static::_storeHistoryLog('response', null, $requestId);
	}
	
	/**
	*	Store history file(s) log
	*	@param string $callFrom
	*	@param array $addedRequest
	*	@param int $requestId
	*/
	protected static function _storeHistoryLog($callFrom, $addedRequest, $requestId)
	{
        // VB
        return;

		$settings = Http_Settings::getInstance();
		
		if($callFrom == 'request')
		{
			$historyFileInTxtMicroTime = $settings->historyFileInTxtMicroTime;
			$historyFileInTxtTimeInRFC1036 = $settings->historyFileInTxtTimeInRFC1036;
			
			if(is_string($historyFileInTxtMicroTime) && $historyFileInTxtMicroTime != '')
			{
				$historyFileInTxtMicroTime = static::_prepareHistoryFileName($historyFileInTxtMicroTime);
					
				if(!FS::file_put_contents($historyFileInTxtMicroTime, "{$addedRequest['pid']} $requestId {$addedRequest['addr']} {$addedRequest['time']} {$addedRequest['data_method']} {$addedRequest['url']}\n", FILE_APPEND))
					trigger_error("Saving history file '$historyFileInTxtMicroTime' failed", E_USER_WARNING);
			}
			
			if(is_string($historyFileInTxtTimeInRFC1036) && $historyFileInTxtTimeInRFC1036 != '')
			{
				$historyFileInTxtTimeInRFC1036 = static::_prepareHistoryFileName($historyFileInTxtTimeInRFC1036);
				
				$time = Date::timestampToRfc1036($addedRequest['time']);
				
				if(!FS::file_put_contents($historyFileInTxtTimeInRFC1036, "{$addedRequest['pid']} $requestId {$addedRequest['addr']} $time {$addedRequest['data_method']} {$addedRequest['url']}\n", FILE_APPEND))
					trigger_error("Saving history file '$historyFileInTxtTimeInRFC1036' failed", E_USER_WARNING);
			}
		}
		
		$historyFileSerialized = $settings->historyFileSerialized;
		
		if(is_string($historyFileSerialized) && $historyFileSerialized != '')
		{
			$historyFileSerialized = static::_prepareHistoryFileName($historyFileSerialized);
			
			if(!FS::file_put_contents($historyFileSerialized, serialize(static::$_history)))
				trigger_error("Saving history file '$historyFileSerialized' failed", E_USER_WARNING);
		}
	}
	
	/**
	*	Prepare history file name from setting string to a proper file path
	*	@param string $filePath File path from setting
	*	@return string Target file path
	*/
	protected static function _prepareHistoryFileName($filePath)
	{
		if($filePath{0} != '/')
			$filePath = DIR_VAR_LOG_SLASH . $filePath;
			
		return str_replace('$PID$', getmypid(), $filePath); // get pid everytime because this process can be a sub-thread (fork or pthread)

	}
}