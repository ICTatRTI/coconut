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
*	@package Http Request
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5.1
*/

/**
*	Http request - Http Client frontend.
*	See included howto.
*	@category Http Client
*	@package Http Request
*	@version 0.5.1
*/
class Http_Request_Base implements Http_Request_Interface
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Default user-agent
	*	@var string
	*/
	const UA_DEFAULT = 'HTTP(s) norbert_ramzes php object client v0.5.1';
	
	/**
	*	Default headers order - used when cant figure correct order.
	*	'ascending', 'descending' or array key from _headersOrderTable.
	*	@var string
	*/
	const DEFAULT_HEADERS_ORDER = 'webkit';
	
	// How to send data:
	
	/**
	*	Method of sending data - auto (default) - in this case, data method will be chosed automatically basing on setPost(), setFile() and setRawData()
	*	@var int
	*/
	const DATA_METHOD_AUTO = 1;
	
	/**
	*	Method of sending data - http get - typically when setPost() was not used
	*	@var int
	*/
	const DATA_METHOD_GET = 2;
	
	/**
	*	Method of sending data - http head (same as GET but sv sends headers only)
	*	@var int
	*/
	const DATA_METHOD_HEAD = 3;
	
	/**
	*	Method of sending data - http post - set when setPost() was used
	*	Content-Type header will be set to application/x-www-form-urlencoded by setHeaderIfNotExist()
	*	@var int
	*/
	const DATA_METHOD_POST = 4; // POST + application/x-www-form-urlencoded
	
	/**
	*	Method of sending data - http post + raw data (like a HTTP PUT but its a POST).
	*	Will be triggered automatically when You add header 'X-HTTP-Method-Override' with value 'PUT' and selected method will be DATA_METHOD_AUTO (default).
	*	Raw data can be set with setRawData().
	*	Content-Type header will be set to application/octet-stream by setHeaderIfNotExist()
	*	@var int
	*/
	const DATA_METHOD_POST_RAW = 5; // POST + raw data + application/octet-stream
	
	/**
	*	Method of sending data - http post + files - when setFile() was used
	*	@var int
	*/
	const DATA_METHOD_POST_MULTIPART = 6; // POST + multipart/form-data
	
	/**
	*	Method of sending data - http put
	*	@var int
	*/
	const DATA_METHOD_PUT = 7;
	
	/**
	*	Method of sending data - http patch
	*	@link http://tools.ietf.org/html/rfc5789
	*	@var int
	*/
	const DATA_METHOD_PATCH = 8;
	
	/**
	*	Method of sending data - http delete
	*	@var int
	*/
	const DATA_METHOD_DELETE = 9;
	
	/**
	*	Method of sending data - http trace
	*	@var int
	*/
	const DATA_METHOD_TRACE = 10;
	
	/**
	*	Method of sending data - http trace
	*	@var int
	*/
	const DATA_METHOD_OPTIONS = 11;
	
	/**
	*	Method of sending data - http connect
	*	@var int
	*/
	const DATA_METHOD_CONNECT = 12;
	
	/**
	*	Request ID
	*	@var int
	*/
	protected $_requestId = 0;
	
	/**
	*	Request id's for create truly unique
	*	@var array
	*/
	protected static $_requests = [];
	
	/**
	*	Array with order of headers
	*	@var array
	*/
	protected $_headersOrderTable = // Host header should be first every time
	[
		'webkit'	=> ['Host', 'Connection', 'Content-Length', 'Cache-Control', 'Accept', 'Origin', 'User-Agent', 'Content-Type', 'Accept-Encoding', 'Accept-Language', 'Accept-Charset', 'Cookie', 'Referer'],
		'gecko'		=> ['Host', 'User-Agent', 'Accept', 'Accept-Language', 'Accept-Encoding', 'Connection', 'X-HTTP-Method-Override', 'Content-Type', 'Referer', 'Content-Length', 'Cookie', 'Pragma', 'Cache-Control']
	];
	
	/**
	*	Default headers.
	*	Every one is set by setHeaderIfNotExist()
	*	@note If You have any problems, try to change 'Connection' to 'Close' and 'Accept-Encoding' to 'deflate'
	*	@note Default headers can be disabled in Http_Settings
	*	@note In v0.5 default 'Connection' header is set in prepare() depending on setting 'keepAliveByDefault'
	*	@var array
	*/
	protected $_defaultHeaders = // webkit
	[
		//'Connection' => 'Keep-Alive',
		//'Connection' => 'Close',
		'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'User-Agent' => self::UA_DEFAULT,
		'Accept-Language' => 'pl-PL,pl;q=0.8,en-US;q=0.6,en;q=0.4',
		'Accept-Charset' => 'utf-8,ISO-8859-2;q=0.7,*;q=0.3',
		'Accept-Encoding' => 'gzip, deflate'
	];
	
	/**
	*	Data
	*	@var array
	*/
	protected $_data =
	[
		'requestData' => null, // requestData object (Http_Request_Data)
		'requestDataSecured' => null, // secured requestData object - in __clone() will be backed up
		'cookieProxy' => null, // cookie proxy object (Http_Cookie_Proxy by default)
		'options' => // defaults
		[
			'dataMethod' => self::DATA_METHOD_AUTO, // see constants
			'headersOrder' => 'auto', // auto, ascending, descending, gecko, webkit or array with headers names
			'useDefaultHeaders' => true // use default headers or not (self::_defaultHeaders)
		],
		'httpBoundary' => '', // used only in post with files (DATA_METHOD_POST_MULTIPART)
		'preparedRequestLine' => '', // prepared request line to sent (ex.: GET /file.htm?a=b HTTP/1.1)
		'preparedHeaders' => '', // prepared request headers to sent
		'preparedBody' => '', // prepared request body to sent
		'addr' => '', // resolved network address
		'response' => null // Http_Response if getResponse() was called previously
	];
	
	/**
	*	Http_Request constructor.
	*	@note If first arg will be Http_Request_Data, then this object will be cloned. Cookies proxy will be also cloned.
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url (see included howto)
	*	@param Http_Cookie_Proxy_Interface $cookiesProxy Give cookie proxy object here if You want use other cookie proxy than internally created instance of Http_Cookie_Proxy. If provided, it will be cloned.
	*	@return void
	*/
	public function __construct($httpRequestData, Http_Cookie_Proxy_Interface $cookiesProxy = null)
	{
		if(is_string($httpRequestData) || (is_object($httpRequestData) && (get_class($httpRequestData) == 'Url' || get_class($httpRequestData) == 'Http_Url')))
			$this->_data['requestData'] = new Http_Request_Data($httpRequestData);
		elseif(is_object($httpRequestData) && get_class($httpRequestData) == 'Http_Request_Data')
			$this->_data['requestData'] = clone $httpRequestData;
		else
			throw new Http_Request_Exception('Bad first argument for constructor');
		
		if($cookiesProxy)
			$this->_data['cookieProxy'] = clone $cookiesProxy; // on null, object will be created in prepare() - for resources saving (time and mem)
	}
	
	/**
	*	Clone requestData and cookiesProxy objects and reset requestId.
	*	Use this when you need more same/modified requests to sent.
	*	@note After cloning, You cant get response or prepared requestLine/headers/body - in that case, You must resend it in Http_Client or call prepare() once again
	*	@return void
	*/
	public function __clone()
	{
		if($this->_data['cookieProxy'])
			$this->_data['cookieProxy'] = clone $this->_data['cookieProxy']; // cloning cookie handler (keeped by cookie proxy)
		
		if($this->_data['requestDataSecured'])
		{
			$this->_data['requestData'] = $this->_data['requestDataSecured'];
			$this->_data['requestDataSecured'] = null;
		}
		else
			$this->_data['requestData'] = clone $this->_data['requestData'];
		
		$this->_requestId = 0;
		
		$this->_data['httpBoundary'] = '';
		$this->_data['preparedRequestLine'] = '';
		$this->_data['preparedHeaders'] = '';
		$this->_data['preparedBody'] = '';
		$this->_data['response'] = null;
	}
	
	/**
	*	Prepare fo sending.
	*	If You want send same or modified request(s), You must clone this object - at anytime.
	*	@note Should be called by Http_Client only, and only once
	*	@return self.
	*	@throws Http_Request_Exception
	*/
	public function prepare()
	{
		if($this->_requestId)
			throw new Http_Request_Exception('prepare() can be called once only! You can clone this object and then call prepare() once again - should be done only by Http_Client');
		
		if(!$this->_data['requestDataSecured'])
			$this->_data['requestDataSecured'] = clone $this->_data['requestData']; // will be backed up in __clone()
		
		$this->_prepare();
		
		// generate unique requestId
		while(true)
		{
			$requestId = mt_rand(1000000, 9999999);
			if(!isset(static::$_requests[$requestId]))
				break;
		}
		static::$_requests[$requestId] = $this->_requestId = $requestId;
		
		return $this;
	}
	
	/**
	*	Get request id
	*	@return int Request id or 0 when wasn't sent (exacly, when Http_Request::prepare() was not called)
	*/
	public function getRequestId()
	{
		return $this->_requestId;
	}
	
	/**
	*	Get method of sending data
	*	@return int Int correspodning to DATA_METHOD_* class constants
	*/
	public function getDataMethod()
	{
		return $this->_data['options']['dataMethod'];
	}

	/**
	*	Set method of sending data
	*	@param int|null $v Data method from DATA_METHOD_* constants or null to use default
	*	@return self
	*	@see DATA_METHOD_AUTO
	*	@see DATA_METHOD_GET
	*	@see DATA_METHOD_HEAD
	*	@see DATA_METHOD_POST
	*	@see DATA_METHOD_POST_RAW
	*	@see DATA_METHOD_POST_MULTIPART
	*	@see DATA_METHOD_PUT
	*	@see DATA_METHOD_PATCH
	*	@see DATA_METHOD_DELETE
	*	@see DATA_METHOD_TRACE
	*	@see DATA_METHOD_OPTIONS
	*	@see DATA_METHOD_CONNECT
	*	@throws Http_Request_Exception
	*/
	public function setDataMethod($v)
	{
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot set data send method - request already prepared (already sent)');
		
		if($v === null)
			$v = static::DATA_METHOD_AUTO;
		
		switch($v)
		{
			case static::DATA_METHOD_AUTO:
			case static::DATA_METHOD_GET:
			case static::DATA_METHOD_HEAD:
			case static::DATA_METHOD_POST:
			case static::DATA_METHOD_POST_RAW:
			case static::DATA_METHOD_POST_MULTIPART:
			case static::DATA_METHOD_PUT:
			case static::DATA_METHOD_PATCH:
			case static::DATA_METHOD_DELETE:
			case static::DATA_METHOD_TRACE:
			case static::DATA_METHOD_OPTIONS:
			case static::DATA_METHOD_CONNECT:
				break;
			
			default:
				throw new Http_Request_Exception('Unknown data method');
		}
		
		$this->_data['options']['dataMethod'] = $v;
		
		return $this;
	}
	
	/**
	*	Get headersOrder option
	*	@return string|array auto, ascending, descending, gecko, webkit or array with headers names
	*/
	public function getHeadersOrder()
	{
		return $this->_data['options']['headersOrder'];
	}
	
	/**
	*	Set headersOrder option
	*	@note If array given, Host must (should) be first, if not, php error will be triggered and 'Host' header will be replaced to beginning
	*	@param string|array $set auto, ascending, descending, gecko, webkit or array with headers names
	*	@return self
	*/
	public function setHeadersOrder($set)
	{
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot change headers order - request already prepared');
		
		if(is_array($set))
		{
			if(!count($set))
				throw new Http_Request_Exception('Empty array given');
			$set = array_values($set);
			if($set[0] != 'Host')
			{
				trigger_error('Host header must be always first in headersOrder. Fixing it...', E_USER_WARNING);
				foreach($set as $k => $v)
				{
					if($v == 'Host')
					{
						unset($set[$k]);
						break;
					}
				}
				array_unshift($set, 'Host');
			}
			
		}
		elseif(is_string($set))
		{
			if($set != 'ascending' && $set != 'descending' && !isset($yhis->_headersOrderTable))
				throw new Http_Request_Exception('Cannot change headers order - request already prepared');
		}
		
		$this->_data['options']['headersOrder'] = $set;
		return $this;
	}
	
	/**
	*	Get option of using default headers
	*	@return bool
	*/
	public function getUseDefaultHeaders()
	{
		return $this->_data['options']['useDefaultHeaders'];
	}
	
	/**
	*	Enable/disable using default headers
	*	@param bool True to enable, false to disable
	*/
	public function setUseDefaultHeaders()
	{
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot set "useDefaultHeaders" - request already prepared');
		$this->_data['options']['useDefaultHeaders'] = $v;
		return $this;
	}
	
	/**
	*	Get url by Http_Url::getUrl()
	*	@return string Url
	*/
	public function getUrl()
	{
		return $this->_data['requestData']->getUrl();
	}
	
	/**
	*	Get url without query by Http_Url::getUrlWithoutQuery()
	*	@return string Url
	*/
	public function getUrlWithoutQuery()
	{
		return $this->_data['requestData']->getUrlWithoutQuery();
	}
	
	/**
	*	Get url with user credentials by Http_Url::getUrlWithUserCredentials()
	*	@return string Url
	*/
	public function getUrlWithUserCredentials()
	{
		return $this->_data['requestData']->getUrlWithUserCredentials();
	}
	
	/**
	*	Get url with user credentials and without query by Http_Url::getUrlWithUserCredentialsAndWithoutQuery()
	*	@return string Url
	*/
	public function getUrlWithUserCredentialsAndWithoutQuery()
	{
		return $this->_data['requestData']->getUrlWithUserCredentialsAndWithoutQuery();
	}
	
	/**
	*	Get url user by Http_Url::getUser()
	*	@return string Url user
	*/
	public function getUser()
	{
		return $this->_data['requestData']->getUser();
	}
	
	/**
	*	Get url pass by Http_Url::getUser()
	*	@return string Url user password
	*/
	public function getPass()
	{
		return $this->_data['requestData']->getPass();
	}
	
	/**
	*	Get parsed url by Http_Url::parse()
	*	@return string Url
	*/
	public function getParsedUrl()
	{
		return $this->_data['requestData']->getParsedUrl();
	}
	
	/**
	*	Get uri by Http_Url::getUri()
	*	@return string Uri
	*/
	public function getUri()
	{
		return $this->_data['requestData']->getUri();
	}
	
	/**
	*	Get scheme by Http_Url::getScheme()
	*	@return string Url scheme
	*/
	public function getScheme()
	{
		return $this->_data['requestData']->getScheme();
	}

	/**
	*	Get transport by Http_Url::getTransport()
	*	@return string Network transport
	*/
	public function getTransport()
	{
		return $this->_data['requestData']->getTransport();
	}
	
	/**
	*	Get host by Http_Url::getHost()
	*	@return string Host name
	*/
	public function getHost()
	{
		return $this->_data['requestData']->getHost();
	}
	
	/**
	*	Get host by Http_Url::getHostIdnAscii() (IDN ASCII format)
	*	@return string Host name
	*/
	public function getHostIdnAscii()
	{
		return $this->_data['requestData']->getHostIdnAscii();
	}
	
	/**
	*	Get resolved network adress.
	*	@return string Target network adress (resolved from host name)
	*	@throws Http_Request_Exception
	*/
	public function getAddr()
	{
		return $this->_data['addr'] != '' ? $this->_data['addr'] : $this->_data['addr'] = Http_Resolver::getInstance()->get($this->_data['requestData']->getHost());
	}
	
	/**
	*	Get port by Http_Url::getPort()
	*	@return int
	*/
	public function getPort()
	{
		return $this->_data['requestData']->getPort();
	}
	
	/**
	*	Get real port by Http_Url::getPortReal()
	*	@return int
	*/
	public function getPortReal()
	{
		return $this->_data['requestData']->getPortReal();
	}
	
	/**
	*	Get path by Http_Url::getPath()
	*	@return string
	*/
	public function getPath()
	{
		return $this->_data['requestData']->getPath();
	}
	
	/**
	*	Get query by Http_Url::getQuery()
	*	@return string Uri query
	*/
	public function getQuery()
	{
		return $this->_data['requestData']->getQuery();
	}
	
	/**
	*	Get http method
	*	@return int
	*/
	public function getMethod()
	{
		return $this->_data['requestData']->getMethod();
	}
	
	/**
	*	Get request header
	*	@param string $k Header name
	*	@return string Header value
	*/
	public function getHeader($k)
	{
		return $this->_data['requestData']->getHeader($k);
	}
	
	/**
	*	Get request headers
	*	@return array
	*/
	public function getHeaders()
	{
		return $this->_data['requestData']->getHeaders();
	}
	
	/**
	*	Check if header was set
	*	@param string $k Header name
	*	@return bool
	*/
	public function issetHeader($k)
	{
		return $this->_data['requestData']->issetHeader($k);
	}
	
	/**
	*	Get post data
	*	@return array
	*/
	public function getPost()
	{
		return $this->_data['requestData']->getPost();
	}
	
	/**
	*	Get request files (typically sent with post data)
	*	@return array
	*/
	public function getFiles()
	{
		return $this->_data['requestData']->getFiles();
	}
	
	/**
	*	Get raw data
	*	@return string
	*/
	public function getRawData()
	{
		return $this->_data['requestData']->getRawData();
	}
	
	/**
	*	Check if raw data was set
	*	@return bool
	*/
	public function issetRawData()
	{
		return $this->_data['requestData']->issetRawData();
	}
	
	/**
	*	Get length of raw data
	*	@return int
	*/
	public function getRawDataSizeOf()
	{
		return $this->_data['requestData']->getRawDataSizeOf();
	}
	
	/**
	*	Forward calls (setters) to Http_Request_Data.
	*	Http_Request_Exception will be throwed when method is not found or when trying to use setter after sending request.
	*	@param string $name Method name
	*	@param array $args Args
	*	@return self
	*	@throws Http_Request_Exception
	*/
	public function __call($name, $args)
	{
		if($this->_requestId && substr($name, 0, 3) == 'set')
		{
			//$t = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
			//if(!isset($t[2]) || substr($t[2]['class'], 0, 11) != 'Http_Client') // only one allowed
				throw new Http_Request_Exception('Cannot forward setter to the Http_Request_Data (' . $name . ') - request already prepared');
		}
		if(!method_exists($this->_data['requestData'], $name))
			throw new Http_Request_Exception(get_class($this) . ' and Http_Request_Data doesnt have method called ' . $name);
		call_user_func_array(array($this->_data['requestData'], $name), $args);
		// object Http_Request_Data is hidden
		return $this;
	}
	
	/**
	*	Prepare request-line, headers and body for sending
	*	@return void
	*	@used-by prepare()
	*	@throws Http_Request_Exception
	*/
	protected function _prepare()
	{
		$settings = Http_Settings::getInstance();
		
		if($settings->useDefaultHeaders)
		{
			foreach($this->_defaultHeaders as $k => $def)
				$this->_data['requestData']->setHeaderIfNotExist($k, $def);
			$this->_data['requestData']->setHeaderIfNotExist('Connection', $settings->keepAliveByDefault ? 'Keep-Alive' : 'Close');
		}
		
		$user = $this->_data['requestData']->getUser();
		$pass = $this->_data['requestData']->getPass();
		
		if($user != '' || $pass != '')
			$this->_data['requestData']->setHeaderIfNotExist('Authorization', 'Basic ' . base64_encode($user . ':' . $pass));
		
		$t = $this->_data['requestData']->getPort();
		
		$this->_data['requestData']->setHeaderIfNotExist('Host', $this->_data['requestData']->getHost() . ($t ? ':' . $t : ''));
		
		if( ! $this->_data['requestData']->issetHeader('Cookie')) // somebody may need that
		{
			if(!$this->_data['cookieProxy'])
				$this->_data['cookieProxy'] = new Http_Cookie_Proxy;
			
			//if($this->_data['cookieProxy']->getSettingSendCookies())
			{
				if(($t = Http_Cookie_Compiler::compileCookieHeader($this->_data['cookieProxy']->getCookiesForRequest($this))) != '')
					$this->_data['requestData']->setHeader('Cookie', $t);
			}
		}
		
		$files = $this->_data['requestData']->getFiles();
		$post = $this->_data['requestData']->getPost();
		
		// chose dataMethod if is leaved to default
		$dataMethod = &$this->_data['options']['dataMethod'];
		
		$requestDataMethod = $this->_data['requestData']->getMethod();
		
		if($dataMethod == static::DATA_METHOD_AUTO)
		{
			if($requestDataMethod != Http_Request_Data::METHOD_AUTO)
			{
				switch($requestDataMethod)
				{
					case Http_Request_Data::METHOD_GET:
						$dataMethod = static::DATA_METHOD_GET;
					break;
					
					case Http_Request_Data::METHOD_HEAD:
						$dataMethod = static::DATA_METHOD_HEAD;
					break;
					
					case Http_Request_Data::METHOD_POST:
						$dataMethod = $files ? static::DATA_METHOD_POST_MULTIPART : static::DATA_METHOD_POST;
					break;
					
					case Http_Request_Data::METHOD_PUT:
						$dataMethod = static::DATA_METHOD_PUT;
					break;
					
					case Http_Request_Data::METHOD_PATCH:
						$dataMethod = static::DATA_METHOD_PATCH;
					break;
					
					case Http_Request_Data::METHOD_DELETE:
						$dataMethod = static::DATA_METHOD_DELETE;
					break;
					
					case Http_Request_Data::METHOD_TRACE:
						$dataMethod = static::DATA_METHOD_TRACE;
					break;
					
					case Http_Request_Data::METHOD_OPTIONS:
						$dataMethod = static::DATA_METHOD_OPTIONS;
					break;
					
					case Http_Request_Data::METHOD_CONNECT:
						$dataMethod = static::DATA_METHOD_CONNECT;
					break;
					
					default:
						throw new Http_Request_Exception('Unknown method from requestData');
				}
			}
			else
			{
				if($this->_data['requestData']->getHeader('X-HTTP-Method-Override') === 'PUT' || $this->_data['requestData']->getRawDataSizeOf())
				{
					$this->_data['requestData']->setMethod(Http_Request_Data::METHOD_POST);
					$dataMethod = static::DATA_METHOD_POST_RAW;
				}
				elseif($files)
				{
					$this->_data['requestData']->setMethod(Http_Request_Data::METHOD_POST);
					$dataMethod = static::DATA_METHOD_POST_MULTIPART;
				}
				elseif($post || $this->_data['requestData']->getMethod() == Http_Request_Data::METHOD_POST)
				{
					$this->_data['requestData']->setMethod(Http_Request_Data::METHOD_POST);
					$dataMethod = static::DATA_METHOD_POST;
				}
				else
				{
					$this->_data['requestData']->setMethod(Http_Request_Data::METHOD_GET);
					$dataMethod = static::DATA_METHOD_GET;
				}
			}
		}
		
		// prepare http request body
		$body = '';
		switch($dataMethod)
		{
			case static::DATA_METHOD_GET:
				$method = 'GET';
			break;
			
			case static::DATA_METHOD_HEAD:
				$method = 'HEAD';
			break;
			
			case static::DATA_METHOD_POST:
				$method = 'POST';
				
				$t_added = false;
				foreach($post as $k => $v)
				{
					if($t_added)
						$body .= '&';
					$body .= urlencode($k) . '=' . urlencode($v);
					$t_added = true;
				}
				
				if($settings->useDefaultHeaders)
					$this->_data['requestData']->setHeaderIfNotExist('Content-Type', 'application/x-www-form-urlencoded');
			break;
			
			case static::DATA_METHOD_POST_MULTIPART:
				$method = 'POST';
				
				$boundary = $this->_data['httpBoundary'] = ($this->getEngineNameFromUA() == 'webkit' ? '----WebKitFormBoundary' : '') . (String::randomString(16));
				
				foreach($post as $k => $v)
				{
					$body .= "--$boundary\n";
					$body .= "Content-Disposition: form-data; name=\"$k\"\n\n$v\n";
				}
				$body .= "--$boundary\n";
				
				$finfo = new finfo(FILEINFO_MIME);
				foreach($files as $f)
				{
					$body .= 'Content-Disposition: form-data; name="' . $f->getInputName() . '"; filename="' . $f->getFileName() . "\"\n";
					$body .= 'Content-Type: ';
					$contentType = strstr($finfo->buffer($f->getContent()), '; ', true);
					$body .= $contentType == '' ?  'binary' : $contentType;
					$body .= "\nContent-Transfer-Encoding: binary\n\n" . $f->getContent() . "\n--$boundary--\n";
				}
				unset($finfo);
				
				if($settings->useDefaultHeaders)
					$this->_data['requestData']->setHeaderIfNotExist('Content-Type', 'multipart/form-data; boundary=' . $boundary);
			break;
			
			case static::DATA_METHOD_POST_RAW:
				$method = 'POST';
				
				$body = $this->getRawData();
				
				if($settings->useDefaultHeaders && $body != '')
					$this->_data['requestData']->setHeaderIfNotExist('Content-Type', 'application/octet-stream');
			break;
			
			case static::DATA_METHOD_PUT:
				$method = 'PUT';
				
				$body = $this->getRawData();
				
				if($settings->useDefaultHeaders && $body != '')
					$this->_data['requestData']->setHeaderIfNotExist('Content-Type', 'application/octet-stream'); /// TODO: finfo
			break;
			
			case static::DATA_METHOD_PATCH:
				$method = 'PATCH';
				
				if($this->_data['requestData']->getRawDataSizeOf())
					$body = $this->_data['requestData']->getRawData();
				else
				{
					$t_added = false;
					foreach($post as $k => $v)
					{
						if($t_added)
							$body .= '&';
						$body .= urlencode($k) . '=' . urlencode($v);
						$t_added = true;
					}
				}
				
				if($settings->useDefaultHeaders && $body != '')
					$this->_data['requestData']->setHeaderIfNotExist('Content-Type', 'application/x-www-form-urlencoded');
			break;
			
			case static::DATA_METHOD_DELETE:
				$method = 'DELETE';
				
				if($this->_data['requestData']->getRawDataSizeOf())
					$body = $this->_data['requestData']->getRawData();
				else
				{
					$t_added = false;
					foreach($post as $k => $v)
					{
						if($t_added)
							$body .= '&';
						$body .= urlencode($k) . '=' . urlencode($v);
						$t_added = true;
					}
				}
				
				if($settings->useDefaultHeaders && $body != '')
					$this->_data['requestData']->setHeaderIfNotExist('Content-Type', 'application/x-www-form-urlencoded');
			break;
			
			case static::DATA_METHOD_TRACE:
				$method = 'TRACE';
				$body = $this->getRawData();
			break;
			
			case static::DATA_METHOD_OPTIONS:
				$method = 'OPTIONS';
			break;
			
			case static::DATA_METHOD_CONNECT:
				$method = 'CONNECT';
			break;
			
			default:
				throw new Http_Request_Exception('Cannot happen... Did You miss something?');
		}
		
		if($body != '') // if we sending request body (post, post+files or raw/put) we need to inform server with length of it
			$this->_data['requestData']->setHeaderIfNotExist('Content-Length', (string)strlen($body));
		
		$this->_data['preparedBody'] = $body;
		
		// prepare headers string and request line
		
		$headersOrder = $this->_data['options']['headersOrder'];
		
		if($headersOrder === 'auto')
		{
			if
			(
				($t = $this->getEngineNameFromUA()) != ''
				&& isset($this->_headersOrderTable[$t])
			)
				$headersOrder = $this->_headersOrderTable[$t];
			else
			{
				if(static::DEFAULT_HEADERS_ORDER == 'ascending' || static::DEFAULT_HEADERS_ORDER == 'descending')
					$headersOrder = static::DEFAULT_HEADERS_ORDER;
				elseif(isset($this->_headersOrderTable[static::DEFAULT_HEADERS_ORDER]))
					$headersOrder = $this->_headersOrderTable[static::DEFAULT_HEADERS_ORDER];
				else
				{
					trigger_error('DEFAULT_HEADERS_ORDER is not valid (valid values are: ascending, descending or key from _headersOrderTable), selecting ascending', E_USER_WARNING);
					$headersOrder = 'ascending';
				}
			}
		}
		
		$headers = $this->_data['requestData']->getHeaders();
		
		switch($headersOrder)
		{
			case 'ascending':
				ksort($headers, SORT_NATURAL);
				
				$t = $headers['Host'];
				unset($headers['Host']);
				$headers = ['Host' => $t] + $headers;
			break;
			
			case 'descending':
				krsort($headers, SORT_NATURAL);
				
				$t = $headers['Host'];
				unset($headers['Host']);
				$headers = ['Host' => $t] + $headers;
			break;
			
			default:
				if(is_array($headersOrder))
				{
					if(reset($headersOrder) != 'Host')
					{
						trigger_error('Host header must be always first in headersOrder. Fixing it...', E_USER_WARNING);
						foreach($headersOrder as $k => $v)
						{
							if($v == 'Host')
							{
								unset($headersOrder[$k]);
								break;
							}
						}
						array_unshift($headersOrder, 'Host');
					}
					
					ksort($headers, SORT_NATURAL); // sort all headers ascending, because not all set headers can be in $headersOrder
					
					$t = [];
					foreach($headersOrder as $ho)
					{
						if(isset($headers[$ho]))
						{
							$t[$ho] = $headers[$ho];
							unset($headers[$ho]);
						}
					}
					//$headers = array_merge($t, $headers);
					$headers = $t + $headers;
				}
				else
					throw new Http_Request_Exception('Bad headersOrder');
		}
		
		$addNewLine = false;
		foreach($headers as $name => $value)
		{
			if($addNewLine)
				$this->_data['preparedHeaders'] .= "\n";
			$this->_data['preparedHeaders'] .= $name . ': ' . $value;
			$addNewLine = true;
		}
		
		$this->_data['preparedRequestLine'] = $method . ' ' . $this->_data['requestData']->getUri() . ' HTTP/1.1';
	}
	
	/**
	*	Secure request data for cloning (backed up in __clone())
	*	@used-by Http_Client
	*	@return self
	*/
	public function secureRequestData()
	{
		$this->_data['requestDataSecured'] = clone $this->_data['requestData'];
		return $this;
	}
	
	/**
	*	Get created boundary in prepare() for multipart/form-data request (used only on DATA_METHOD_POST_MULTIPART).
	*	@note Will return empty string before calling prepare() and on other data methods than DATA_METHOD_POST_MULTIPART
	*	@return string Boundary string
	*	@todo Create unique boundary
	*/
	public function getBoundary()
	{
		return $this->_data['httpBoundary'];
	}
	
	/**
	*	Get first line of http request (without ending \\n)
	*	@return string Http request line (ex.: GET /?action=foo HTTP/1.1)
	*	@throws Http_Request_Exception
	*/
	public function getRequestLine()
	{
		if(!$this->_data['preparedRequestLine'])
			throw new Http_Request_Exception('Empty request line (did You call prepare() before?)');
		return $this->_data['preparedRequestLine'];
	}
	
	/**
	*	Get request headers in string
	*	@return string Request headers
	*	@throws Http_Request_Exception
	*/
	public function getRequestHeadersString()
	{
		if($this->_data['preparedHeaders'] == '')
			throw new Http_Request_Exception('Empty request headers string (did You call prepare() before?)');
		return $this->_data['preparedHeaders'];
	}
	
	/**
	*	Get request body
	*	@return string Http request body
	*/
	public function getRequestBody()
	{
		if(!$this->_requestId)
			throw new Http_Request_Exception('Did You call prepare() before?');
		
		return $this->_data['preparedBody']; // request body can be empty, even if we do POST or PUT
	}
	
	/**
	*	Get browser engine name (in lowercase) correspodning to given user-agent string
	*	@param string|null $ua user-agent or null to use set/default user-agent
	*	@return string String described in Http_Engine_Detection::getEngineNameFromUA()
	*/
	public function getEngineNameFromUA($ua = null)
	{
		if($ua === null)
			if(!$ua = $this->_data['requestData']->getHeader('User-Agent'))
				$ua = $this->_data['options']['defaultHeaders']['User-Agent'];
		
		$engine = Http_Engine_Detection::getEngineNameFromUA($ua);
		
		//return $engine == 'unknown' ? 'webkit' : $engine;
		return $engine;
	}
	
	/**
	*	Send this request via Http_Client
	*	@return self
	*/
	public function send()
	{
		return Http_Client::getInstance()->send($this);
	}
	
	/**
	*	Get response for this request.
	*	@note If wasnt sent before, method send() will be called automatically
	*	@param bool $followLocation True to follow redirects (Location header)
	*	@return Http_Response
	*/
	public function getResponse($followLocation = true)
	{
		if(!$this->_requestId)
			$this->send();
		return $this->_data['response'] ? $this->_data['response'] : $this->_data['response'] = Http_Client::getInstance()->getResponse($this, $followLocation);
	}
	
	/**
	*	Get response for this request (alias of getResponse()).
	*	@note If wasnt sent before, method send() will be called automatically
	*	@param bool $followLocation True to follow redirects (Location header)
	*	@return Http_Response
	*/
	public function __invoke($followLocation = true)
	{
		// copied, not forwarded for execution time saving
		if(!$this->_requestId)
			$this->send();
		return $this->_data['response'] ? $this->_data['response'] : $this->_data['response'] = Http_Client::getInstance()->getResponse($this, $followLocation);
	}
	
	/**
	*	Alias of getResponse()->getBody()
	*	@return string
	*/
	public function __toString()
	{
		if(!$this->_requestId)
			$this->send();
		return $this->_data['response'] ? $this->_data['response']->getBody() : $this->_data['response'] = Http_Client::getInstance()->getResponse($this)->getBody();
	}
	
	/**
	*	Set cookies from response header(s) value(s).
	*	It will forward all to the cookie proxy (in setCookieFromValueOfResponseHeader())
	*	@param array $cookieHeaders Array with values of response 'Set-Cookie' header(s) value(s)
	*	@return self
	*	@uses setCookieFromHeaderValue()
	*/
	public function setCookiesFromResponseHeadersValues(array $cookieHeaders)
	{
		foreach($cookieHeaders as $v)
			$this->setCookieFromHeaderValue($v);
		return $this;
	}
	
	/**
	*	Store cookie from 'Set-Cookie' header value.
	*	It will forward all to the cookie proxy.
	*	@param string $headerValue value of 'Set-Cookie' header
	*	@return self
	*	@see Http_Cookie_Proxy::setCookieFromHeaderValue()
	*/
	public function setCookieFromHeaderValue($headerValue)
	{
		$this->_data['cookieProxy']->setCookieFromHeaderValue($headerValue, $this);
		return $this;
	}
	
	/**
	*	Set temporary cookie.
	*	@note it will be forwarded into internally keeped instance of Http_Cookie_Proxy_Interface
	*	@param Http_Cookie $cookie Cookie to temporary use
	*	@return self
	*	@see Http_Cookie_Proxy
	*/
	public function setTempCookie(Http_Cookie $cookie)
	{
		if(!$cookie)
			throw new Http_Request_Exception('Null given instead instance of Http_Cookie');
		
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot set temporary cookie - request already prepared (already sent)');
		
		if(!$this->_data['cookieProxy'])
			$this->_data['cookieProxy'] = new Http_Cookie_Proxy;
		
		$this->_data['cookieProxy']->setTempCookie($cookie);
		
		return $this;
	}
	
	/**
	*	Temporary disable cookie - forward call into cookie proxy
	*	If temporary cookie exists here (in a cookie proxy) with same name, it will be dropped - if You need to temporary cookie replace (other value or something) use setTempCookie().
	*	@param string|Http_Cookie $name Cookie name or cookie object to get name from it (cookie wih this name will be temporary disabled)
	*	@return self
	*/
	public function disableCookie($name)
	{
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot temporary disable cookie - request already prepared (already sent)');
		
		if(!$this->_data['cookieProxy'])
			$this->_data['cookieProxy'] = new Http_Cookie_Proxy;
		
		$this->_data['cookieProxy']->disableCookie($name);
		
		return $this;
	}
	
	/**
	*	Search for cookie names that should be sent in this request (for given host and path)
	*	@param array $names Cookie names
	*	@return array Cookie objects with given names if founded
	*/
	public function cookiesSearch(array $names)
	{
		if(!$this->_data['cookieProxy'])
			$this->_data['cookieProxy'] = new Http_Cookie_Proxy;
		
		$ret = [];
		foreach($this->_data['cookieProxy']->getCookiesForRequest($this) as $n => $c)
		{
			foreach($names as $nn)
				if($nn == $n)
					$ret[$n] = clone $c;
		}
		
		return $ret;
	}
	
	/**
	*	Internal usage only
	*	@return Http_Cookie_Proxy_Interface
	*	@used-by Http_Client
	*	@ignore
	*/
	public function _getCookieProxy()
	{
		return $this->_data['cookieProxy'];
	}
	
	/**
	*	Change cookie proxy (see included howto)
	*	@param Http_Cookie_Proxy_Interface|null $proxy Http cookie proxy. Give null to use default.
	*	@return self
	*	@throws Http_Request_Exception
	*/
	public function setCookieProxy(Http_Cookie_Proxy_Interface $proxy = null)
	{
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot change cookie proxy - request already prepared (already sent)');
		
			$this->_data['cookieProxy'] = $proxy ? clone $proxy : null; // on null, instance of Http_Cookie_Proxy will be created on demand
		
		return $this;
	}
	
	/**
	*	Forward setCookie() into cookie proxy
	*	@param Http_Cookie $cookie Cookie object
	*	@return self
	*	@throws Http_Request_Exception
	*/
	public function setCookie(Http_Cookie $cookie)
	{
		if($this->_requestId)
			throw new Http_Request_Exception('Cannot set cookie - request already prepared (already sent)');
		
		if(!$this->_data['cookieProxy'])
			$this->_data['cookieProxy'] = new Http_Cookie_Proxy;
		
		$this->_data['cookieProxy']->setCookie($cookie);
		
		return $this;
	}
}