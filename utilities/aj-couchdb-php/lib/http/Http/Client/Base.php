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
*	Http client (base class) - communication "frontend".
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Client
*	@version 0.5.1
*/
class Http_Client_Base
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Http settings handler
	*	@var Http_Settings
	*/
	public $settings = null;
	
	/**
	*	Singleton instance
	*	@var Http_Client
	*/
	protected static $_instance = null;
	
	/**
	*	Connections handler
	*	@var Http_Client_Connection_Handler
	*/
	protected $_connectionHandler = null;
	
	/**
	*	Sent requests/responses (array with Http_Client_RequestHandler instances)
	*	@var array
	*/
	protected $_requests = [];
	
	/**
	*	Stored responses readed from Http_Client_Cache
	*	@var array
	*/
	protected $_stored = [];
	
	/**
	*	Protected constructor
	*	@see getInstance()
	*	@return void
	*/
	protected function __construct()
	{
		Http_Client_History::init();
		$this->settings = Http_Client_Cache::$settings = Http_Settings::getInstance();
		$this->_connectionHandler = Http_Client_Connection_Handler::getInstance();
	}
	
	/**
	*	Get singleton instance
	*	@return self
	*/
	public static function getInstance()
	{
		// self instead static (late state binding), because in my case, php engine tries to use lower class (Http_Request) instead this one. WHY???
		if(self::$_instance)
			return self::$_instance;
		return self::$_instance = new self;
	}
	
	/**
	*	Send request using Http_Request
	*	Use Http_Request::send() instead
	*	@param Http_Request_Interface $request Http request object
	*	@return Http_Request_Interface Request object after execution prepare()
	*	@throws Http_Client_Exception
	*/
	public function send(Http_Request_Interface $request)
	{
		if($request->getRequestId())
			throw new Http_Client_Exception('Cant resend request (clone request object before or use new one)');
		
		$request->secureRequestData();
		
		$t = $request->issetHeader('Connection'); // Calling function/method in php takes some time so cache it.
		$keepAlive = $t ? (strtolower($request->getHeader('Connection')) === 'keep-alive' ? true : false) : $this->settings->keepAliveByDefault; // `keep-alive` only when this header is correct, `close` otherwise
		
		$urlWithUserCredentials = $request->getUrlWithUserCredentials();
		$urlWithUserCredentials_base64 = base64_encode($urlWithUserCredentials);
		
		$host = $request->getHost();
		$addr = $request->getAddr();
		$port = $request->getPortReal();
		
		$requestHandler = new Http_Client_RequestHandler;
		
		$tRequest = clone $request; // If anybody have a better idea, please let me know
		$tRequest->prepare();
		$readingCacheAllowed = ($tRequest->getDataMethod() == Http_Request::DATA_METHOD_GET);
		unset($tRequest);
		
		if($readingCacheAllowed && $storedResponse = Http_Client_Cache::getStored($host, $urlWithUserCredentials, $urlWithUserCredentials_base64, $request))
		{
			$request->prepare();
			$requestHandler->requestId = $request->getRequestId();
			$requestHandler->storedResponse = $storedResponse;
			
			$sHost = $request->getScheme() . '://' . $host; // technically this is stupid, but sometimes...
			
			if(!isset($this->_stored[$sHost]))
				$this->_stored[$sHost] = [];
			
			if(!isset($this->_stored[$sHost][$port]))
				$this->_stored[$sHost][$port] = [];
			
			$this->_stored[$sHost][$port][] = $requestHandler;
			
			return $request;
		}
		
		$requestHandler->url = $urlWithUserCredentials;
		$requestHandler->url_base64 = $urlWithUserCredentials_base64;
		
		$requestHandler->askedForIfModifiedSince = false;
		$requestHandler->askedForIfNoneMatch = false;
		
		// If cached response is available, then get it all, because it can expire between send() and getResponse() or cache file can be deleted. Then send revalidate request and return cached or new response
		if($readingCacheAllowed && $cached = Http_Client_Cache::getCached($host, $urlWithUserCredentials, $urlWithUserCredentials_base64))
		{
			if($cached['lastModified'] != '')
			{
				$request->setHeaderIfNotExist('If-Modified-Since', $cached['lastModified']);
				$requestHandler->askedForIfModifiedSince = true;
			}
			
			if($cached['etag'] != '')
			{
				$request->setHeaderIfNotExist('If-None-Match', $cached['etag']);
				$requestHandler->askedForIfModifiedSince = true;
			}
			
			unset($cached['lastModified']);
			unset($cached['etag']);
			
			$requestHandler->cached = $cached;
		}
		
		$request->prepare();
		$requestId = $requestHandler->requestId = $request->getRequestId();
		$requestHandler->request = $request;
		$requestHandler->host = $host;
		
		// Handle connection
		
		$transport = $request->getTransport();
		
		$connectionHandler = $this->_connectionHandler;
		$createNewConnection = false;
		if($connectionHandler->isStored($addr, $port))
		{
			$currentConnection = $connectionHandler->select($addr, $port)->getCurrent();
			
			if(!$keepAlive)
				$createNewConnection = true;
			elseif($currentConnection->getTransport() !== $transport) // change transport on same address and port (maybe somebody needs this sometime)
				$createNewConnection = true;
			elseif(!$currentConnection->isWriteAble())
				$createNewConnection = true;
			
			if($createNewConnection)
			{
				$this->_readAll($addr, $port, $currentConnection); // get/parse/store waiting response(s)
				
				$currentConnection = $connectionHandler
					->destroyCurrent() // destroy prevoiously selected current connection
					->create($addr, $port, $transport) // create new one with other transport
					->select($addr, $port) // select it as current
					->getCurrent(); // and get it
			}
		}
		else
		{
			$currentConnection = $connectionHandler->create($addr, $port, $transport)->select($addr, $port)->getCurrent();
			$createNewConnection = true;
		}
		
		// Create request string and send it
		
		$requestString = $request->getRequestLine() . "\n" . $request->getRequestHeadersString();
		
		if($this->settings->dbgRequestHeaders)
			Http_Client_Debug::write('Request id / url: ' . $requestId . ' / ' . $urlWithUserCredentials . "\n" . $requestString, 'Request');
		
		$requestString .= "\n\n" . $request->getRequestBody();
		
		if(!isset($this->_requests[$addr]))
			$this->_requests[$addr] = [];
		
		if(!isset($this->_requests[$addr][$port]))
			$this->_requests[$addr][$port] = [];
		
		$currentConnection->setBlocking(1)->write($requestString); // send request (write into socket).
		
		$this->_requests[$addr][$port][] = $requestHandler;
		
		$timeSent = $currentConnection->getLastWriteTime();
		
		Http_Client_History::addRequest($request, $timeSent);
		
		$requestHandler->timeSent = $timeSent;
		$requestHandler->unreaded = true;
		
		$currentConnection->setBlocking((int) ! $keepAlive);
		
		if(!$this->settings->getResponseOnlyOnDemand || ($keepAlive && $createNewConnection))
			$this->_readAll($addr, $port, $currentConnection); // get/parse/store waiting response(s)
		
		return $request;
	}
	
	/**
	*	Get Http_Response for given request id
	*	@note Use Http_Request_Interface::getResponse() instead
	*	@param Http_Request_Interface $request Instance of Http_Request_Interface previously used (or not used) to sent.
	*	@param bool $followLocation Follow Location header when true
	*	@return Http_Response Http_Response corresponding to request id
	*	@throws Http_Client_Exception
	*/
	public function getResponse(Http_Request_Interface $request, $followLocation = true)
	{
		if(!$request)
			throw new Http_Client_Exception('First arg can\'t be null');
		
		if(!$request->getRequestId())
			$request = $this->send($request);
		
		return $this->_getResponse($request, $followLocation, 0);
	}
	
	/**
	*	Get Http_Response for given request id
	*	@param Http_Request_Interface $request Instance of request object previously used to sent.
	*	@param bool $followLocation follow Location header when true
	*	@param int $nesting Used internally only to count followed requests (redirects limit)
	*	@return Http_Response corresponding to request id, but it can be truly new one if followed location.
	*	@throws Http_Client_Exception
	*/
	protected function _getResponse(Http_Request_Interface $request, $followLocation, $nesting)
	{
		$host = $request->getHost();
		$port = $request->getPortReal();
		$scheme = $request->getScheme();
		$requestId = $request->getRequestId();
		
		$sHost = $scheme . '://' . $host;
		if(isset($this->_stored[$sHost]) && isset($this->_stored[$sHost][$port]))
		{
			foreach($this->_stored[$sHost][$port] as $k => $r)
			{
				if($r->requestId == $requestId)
				{
					unset($this->_stored[$sHost][$port][$k]);
					
					if(!count($this->_stored[$sHost][$port]))
						unset($this->_stored[$sHost][$port]);
					if(!count($this->_stored[$sHost]))
						unset($this->_stored[$sHost]);
					
					return $r->storedResponse;
				}
			}
		}
		
		$requestHandler = $this->_getRequestHandler($requestId, $request->getAddr(), $port);
		
		if(isset($requestHandler->cached) && $requestHandler->status === 304)
		{
			$cached = $requestHandler->cached;
			$headers = explode("\n", str_replace("\r\n", "\n", $cached['header']));
			array_shift($headers);
			foreach($headers as &$header)
			{
				$t = explode(': ', $header, 2);
				$header = [$t[0], $t[1]];
			}
			
			return new Http_Response
			(
				[
					'bodyRaw' => $cached['bodyRaw'],
					'headerSource' => $cached['header'],
					'headers' => $headers,
					'status' => $cached['status'],
					'statusRaw' => $cached['statusRaw']
				],
				$request,
				$requestHandler->timeSent,
				$requestHandler->timeReceived
			);
		}
		
		$responseParser = $requestHandler->responseParser;
		
		if($followLocation && ($location = $responseParser->getLocationHeader()) != '')
		{
			$maxRedirects = $this->settings->maxRedirects;
			if($nesting > $maxRedirects)
				throw new Http_Client_Exception("Too many redirects (limit is $maxRedirects)");
			
			$url = new Url($location);
			$url->setDefaultScheme($scheme);
			$url->setDefaultHost($host);
			if($url->getHost() == $host)
			{
				$url->setDefaultUser($request->getUser());
				$url->setDefaultPass($request->getPass());
			}
			
			$newRequest = new Http_Request($url);
			
			$newRequest->setCookieProxy($request->_getCookieProxy());
			
			if($this->settings->sendRefererOnRedirects)
				$newRequest->setHeader('Referer', $request->getUrl());
			
			return $this->_getResponse($newRequest->send(), true, ++$nesting);
		}
		
		return new Http_Response($responseParser, $request, $requestHandler->timeSent, $requestHandler->timeReceived);
	}
	
	/**
	*	Get Http_Client_RequestHandler for given requestId
	*	@param int $requestId Http request id (Http_Request)
	*	@param string $addr Network address
	*	@param int|string Network port
	*	@return Http_Client_RequestHandler
	*	@throws Http_Client_Exception
	*/
	protected function _getRequestHandler($requestId, $addr, $port)
	{
		$connectionHandler = $this->_connectionHandler;
		$currentConnection = false;
		
		$c = 0;
		$ret = null;
		$requestsCount = count($this->_requests[$addr][$port]);
		foreach($this->_requests[$addr][$port] as $k => $r)
		{
			++$c;
			
			if($r->unreaded)
			{
				$r->unreaded = false;
				
				if(!$currentConnection)
					$currentConnection = $connectionHandler->getCurrent();
				
				$r->responseParser = new Http_Response_Parser($currentConnection);
				
				$request = $r->request;
				$requestId = $r->requestId;
				
				if($this->settings->dbgResponseHeaders)
					Http_Client_Debug::write('Request id / url: ' . $requestId . ' / ' . $request->getUrlWithUserCredentials() . "\n" . $r->responseParser->getHeaderSource(), 'Response');
				
				Http_Client_History::addResponseParser($r->responseParser, $requestId);
				
				if($keepAliveParams = $r->responseParser->getKeepAliveParams())
					$connectionHandler->getCurrent()->setHttpKeepAliveParams($keepAliveParams);
				
				$request->setCookiesFromResponseHeadersValues($r->responseParser->getSetCookieHeadersValues());
				
				$status = $r->status = $r->responseParser->getStatus();
				
				if($status === 304 && ($r->askedForIfModifiedSince || $r->askedForIfNoneMatch))
					Http_Client_Cache::updateTimings($r->host, $r->url, $r->url_base64, $r->responseParser, $r->timeSent);
				elseif($status === 200 && $request->getDataMethod() === Http_Request::DATA_METHOD_GET && $r->responseParser->getBodyRawSizeOf())
					Http_Client_Cache::setResponseParser($r->responseParser, $request, $r->timeSent);
				
				$r->timeReceived = $currentConnection->getLastReadTime();
				
				if($r->responseParser->getConnectionType() == 'close')
				{
					if($requestsCount > 1 && $c < $requestsCount)
						trigger_error('Connection closed in the middle... Consider lowering settings named \'keepAliveDefaultTimeOut\', \'keepAliveDefaultMax\' or try to enable settings \'keepAliveDefaultTimeOut_useEvenWhenSocketWasReaded\', \'keepAliveDefaultMax_useEvenWhenSocketWasReaded\'. In last, or to be very sure, disable \'getResponseOnlyOnDemand\'. Reading next unreaded responses from this socket (' . $addr . ':' . $port . ') can throw an exception', E_USER_WARNING);
					$connectionHandler->destroyCurrent(); // destroy a "current" connection
					unset($currentConnection);
				}
			}
			
			if($r->requestId == $requestId)
			{
				unset($this->_requests[$addr][$port][$k]);
				
				if(!count($this->_requests[$addr][$port]))
					unset($this->_requests[$addr][$port]);
			
				if(!count($this->_requests[$addr]))
					unset($this->_requests[$addr]);
				
				return $r;
			}
		}
		throw new Http_Client_Exception('Should not happen... requestHandler not found'); // if You encountered this without php error triggered before, please let me know. To be sure, disable setting 'getResponseOnlyOnDemand' or try to change other keep-alive settings.
	}
	
	/**
	*	Read all waiting responses for given address and port
	*	@param string $addr Network address, which was used to sent
	*	@param int $port Network port, which was used to sent
	*	@param Http_Client_Connection $currentConnection
	*	@return void
	*/
	protected function _readAll($addr, $port, $currentConnection)
	{
		if(!isset($this->_requests[$addr]) || !isset($this->_requests[$addr][$port]))
			return;
		
		$requestsCount = count($this->_requests[$addr][$port]);
		$c = 0;
		foreach($this->_requests[$addr][$port] as $r)
		{
			++$c;
			
			if($r->unreaded)
			{
				$r->unreaded = false;
				$request = $r->request;
				$requestId = $r->requestId;
				$responseParser = $r->responseParser = new Http_Response_Parser($currentConnection, $request);
				
				if($this->settings->dbgResponseHeaders)
					Http_Client_Debug::write('Request id / url: ' . $requestId . ' / ' . $r->url . "\n" . $responseParser->getHeaderSource(), 'Response');
				
				Http_Client_History::addResponseParser($responseParser, $requestId);
				
				$keepAliveParams = $responseParser->getKeepAliveParams();
				if($keepAliveParams)
					$currentConnection->setHttpKeepAliveParams($keepAliveParams);
				
				$request->setCookiesFromResponseHeadersValues($responseParser->getSetCookieHeadersValues());
				
				$status = $r->status = $r->responseParser->getStatus();
				
				if($status === 304 && ($r->askedForIfModifiedSince || $r->askedForIfNoneMatch))
					Http_Client_Cache::updateTimings($r->host, $r->url, $r->url_base64, $responseParser, $r->timeSent);
				elseif($status === 200 && $r->request->getDataMethod() === Http_Request::DATA_METHOD_GET && $responseParser->getBodyRawSizeOf())
					Http_Client_Cache::setResponseParser($responseParser, $request, $r->timeSent);
				
				$r->timeReceived = $currentConnection->getLastReadTime();
				
				if($responseParser->getConnectionType() == 'close')
				{
					if($requestsCount > 1 && $c < $requestsCount)
						trigger_error('Connection closed in the middle... Consider lowering settings named \'keepAliveDefaultTimeOut\', \'keepAliveDefaultMax\' or try to enable settings \'keepAliveDefaultTimeOut_useEvenWhenSocketWasReaded\', \'keepAliveDefaultMax_useEvenWhenSocketWasReaded\'. In last, or to be very sure, disable \'getResponseOnlyOnDemand\'. Reading next unreaded responses from this socket (' . $addr . ':' . $port . ') can throw an exception', E_USER_WARNING);
					$this->_connectionHandler->destroyCurrent();
					return; // maybe getResponse() want be called on next "waiting responses" (which currently are not exists), or maybe will be. But returning in this case will be little smarter, than reading closed and empty socket...
				}
			}
		}
	}
	
	/**
	*	Get all waiting responses in destructor because some responses can send us cookies, and we dont want to loose it (are we?).
	*	@return void
	*/
	public function __destruct()
	{
		$this->readAllWaitingResponses();
	}
	
	/**
	*	Read all waiting (not yet readed from socket) responses.
	*	@param string $addr If provided, will read only all from this network address, if null given, will read from all addresses
	*	@param int $port If provided, will read only all from this network port, if null given, will read from all ports
	*	@see Http_Settings
	*	@return self
	*/
	public function readAllWaitingResponses($addr = null, $port = null)
	{
		$connectionHandler = $this->_connectionHandler;
		foreach($this->_requests as $a_k => $a)
		{
			if($addr === null || $a_k === $addr)
			{
				foreach($a as $p_k => $p)
				{
					if($port === null || $p_k === $port)
					{
						if($connectionHandler->isStored($a_k, $p_k))
							$this->_readAll($a_k, $p_k, $connectionHandler->getCurrent());
					}
				}
			}
		}
		return $this;
	}
	
	/**
	*	__sleep()
	*	@return void
	*/
	public function __sleep()
	{
		$this->readAllWaitingResponses();
		
		foreach($this->_requests as $a_k => $a)
		{
			foreach($a as $p_k => $p)
			{
				if($connectionHandler->isStored($a_k, $p_k))
					$connectionHandler->destroy($a_k, $p_k);
			}
		}
	}
	
	/**
	*	Shortcut to make GET requests
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@return Http_Response
	*/
	public static function get($httpRequestData)
	{
		return (new Http_Request($httpRequestData))->getResponse();
	}
	
	/**
	*	Shortcut to make GET requests (returning response body)
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@return string
	*/
	public static function get_($httpRequestData)
	{
		return (new Http_Request($httpRequestData))->getResponse()->getBody();
	}
	
	/**
	*	Shortcut to make HEAD requests
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@return Http_Response
	*/
	public static function head($httpRequestData)
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_HEAD)->getResponse();
	}
	
	/**
	*	Shortcut to make POST requests
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param array $post post data in array ex.: ['input_firstName' => 'Chris']
	*	@return Http_Response
	*/
	public static function post($httpRequestData, array $post = [])
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_POST)->setPost($post)->getResponse();
	}
	
	/**
	*	Shortcut to make POST requests (returning response body)
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param array $post post data in array ex.: ['input_firstName' => 'Chris']
	*	@return string
	*/
	public static function post_($httpRequestData, array $post = [])
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_POST)->setPost($post)->getResponse()->getBody();
	}
	
	/**
	*	Shortcut to make PUT requests
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param string $data PUT data (contents of a file)
	*	@return Http_Response
	*/
	public static function put($httpRequestData, $data = '')
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_PUT)->setRawData($data)->getResponse();
	}
	
	/**
	*	Shortcut to make PUT requests (returning response body)
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param string $data PUT data (contents of a file)
	*	@return string
	*/
	public static function put_($httpRequestData, $data = '')
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_PUT)->setRawData($data)->getResponse()->getBody();
	}
	
	/**
	*	Shortcut to make PATCH requests
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param string $post POST data
	*	@return Http_Response
	*/
	public static function patch($httpRequestData, array $post = [])
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_PATCH)->setPost($post)->getResponse();
	}
	
	/**
	*	Shortcut to make PATCH requests (returning response body)
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param string $post POST data
	*	@return string
	*/
	public static function patch_($httpRequestData, array $post = [])
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_PATCH)->setPost($post)->getResponse()->getBody();
	}
	
	/**
	*	Shortcut to make DELETE requests
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param string $post POST data
	*	@return Http_Response
	*/
	public static function delete($httpRequestData, array $post = [])
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_DELETE)->setPost($post)->getResponse();
	}
	
	/**
	*	Shortcut to make DELETE requests (returning response body)
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto) - same as in Http_Request::__construct()
	*	@param string $post POST data
	*	@return string
	*/
	public static function delete_($httpRequestData, array $post = [])
	{
		return (new Http_Request($httpRequestData))->setMethod(Http_Request_Data::METHOD_DELETE)->setPost($post)->getResponse()->getBody();
	}
}