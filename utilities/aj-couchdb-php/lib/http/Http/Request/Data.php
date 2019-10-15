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
*	@subpackage Http Request Data
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5.1
*/

/**
*	Http request data - used in Http_Request.
*	See included howto.
*	@category Http Client
*	@package Http Request
*	@subpackage Http Request Data
*	@version 0.5.1
*/
class Http_Request_Data
{
	/**
	*	Version of class
	*	@var string
	*/
	CONST VERSION = '0.5.1';
	
	/**
	*	Http method auto (default)
	*	@var int
	*/
	const METHOD_AUTO = 1;
	
	/**
	*	Http method get
	*	@var int
	*/
	const METHOD_GET = 2;
	
	/**
	*	Http method head
	*	@var int
	*/
	const METHOD_HEAD = 3;
	
	/**
	*	Http method post
	*	@var int
	*/
	const METHOD_POST = 4;
	
	/**
	*	Http method put
	*	@var int
	*/
	const METHOD_PUT = 5;
	
	/**
	*	Http method patch
	*	@link http://tools.ietf.org/html/rfc5789
	*	@var int
	*/
	const METHOD_PATCH = 6;
	
	/**
	*	Http method delete
	*	@var int
	*/
	const METHOD_DELETE = 7;
	
	/**
	*	Http method trace
	*	@var int
	*/
	const METHOD_TRACE = 8;
	
	/**
	*	Http method trace
	*	@var int
	*/
	const METHOD_OPTIONS = 9;
	
	/**
	*	Http method connect
	*	@var int
	*/
	const METHOD_CONNECT = 10;
	
	/**
	*	Http request data
	*/
	protected $_data =
		[
			'url' => '',
			'method' => self::METHOD_AUTO,
			'headers' => [],
			'post' => [],
			'files' => [],
			'rawData' => '',
		];
	
	/**
	*	Http_Request_Data constructor
	*	@param Http_Url|Url|string $v Http url (set by setUrl())
	*	@return void
	*	@throws Http_Request_Data_Exception
	*/
	public function __construct($v)
	{
		$this->setUrl($v);
	}
	
	/**
	*	Set url.
	*	@note Object url will be cloned.
	*	@param Http_Url|Url|string $v Http url
	*	@return self
	*	@throws Http_Request_Data_Exception
	*/
	public function setUrl($v)
	{
		if(is_object($v) && (get_class($v) == 'Http_Url' || get_class($v) == 'Url'))
			$this->_data['url'] = clone $v;
		elseif(is_string($v))
			$this->_data['url'] = new Http_Url($v);
		else
			throw new Http_Request_Data_Exception('Bad url given (must be string or instance of Http_Url or Url)');
		
		return $this;
	}
	
	/**
	*	Clone Url/Http_Url object
	*	@return void
	*/
	public function __clone()
	{
		$this->_data['url'] = clone $this->_data['url'];
	}
	
	/**
	*	Get Url in a string
	*	@return string Url
	*/
	public function getUrl()
	{
		return $this->_data['url']->getUrl();
	}
	
	/**
	*	Get Url with user credentials in a string
	*	@return string Url with user credentials
	*/
	public function getUrlWithUserCredentials()
	{
		return $this->_data['url']->getUrlWithUserCredentials();
	}
	
	/**
	*	Get Url without query in a string
	*	@return string Url with user credentials
	*/
	public function getUrlWithoutQuery()
	{
		return $this->_data['url']->getUrlWithUserCredentials();
	}
	
	/**
	*	Get Url user
	*	@return string Url user
	*/
	public function getUser()
	{
		return $this->_data['url']->getUser();
	}
	
	/**
	*	Get Url password
	*	@return string Url password
	*/
	public function getPass()
	{
		return $this->_data['url']->getPass();
	}
	
	/**
	*	Get parsed url via Url::getParsedUrl()
	*	@return string Url
	*/
	public function getParsedUrl()
	{
		return $this->_data['url']->getParsedUrl();
	}
	
	/**
	*	Get uri by Url::getUri()
	*	@return string Uri
	*/
	public function getUri()
	{
		return $this->_data['url']->getUri();
	}
	
	/**
	*	Get http scheme via Url::getScheme()
	*	@return string
	*/
	public function getScheme()
	{
		return $this->_data['url']->getScheme();
	}
	
	/**
	*	Get transport layer via Url::getTransport()
	*	@return string
	*/
	public function getTransport()
	{
		return $this->_data['url']->getTransport();
	}
	
	/**
	*	Get host name via Url::getHost()
	*	@return string Host name
	*/
	public function getHost()
	{
		return $this->_data['url']->getHost();
	}
	
	/**
	*	Get host name via Url::getHostIdnAscii() (IDN ASCII format)
	*	@return string Host name
	*/
	public function getHostIdnAscii()
	{
		return $this->_data['url']->getHostIdnAscii();
	}
	
	/**
	*	Get port via Url::getPort()
	*	@return int
	*/
	public function getPort()
	{
		return $this->_data['url']->getPort();
	}
	
	/**
	*	Get real port by Url::getPortReal()
	*	@return int
	*/
	public function getPortReal()
	{
		return $this->_data['url']->getPortReal();
	}
	
	/**
	*	Get path by Url::getPath()
	*	@return string
	*/
	public function getPath()
	{
		return $this->_data['url']->getPath();
	}
	
	/**
	*	Get query by Url::getQuery()
	*	@return string Uri query
	*/
	public function getQuery()
	{
		return $this->_data['url']->getQuery();
	}
	
	/**
	*	Set URL scheme (forward into Http_Url/Url)
	*	@param string $v URL scheme
	*	@return self
	*/
	public function setScheme($v)
	{
		$this->_data['url']->setScheme($v);
		return $this;
	}
	
	/**
	*	Set host (forward into Http_Url/Url)
	*	@param string $v URL host
	*	@return self
	*/
	public function setHost($v)
	{
		$this->_data['url']->setHost($v);
		return $this;
	}
	
	/**
	*	Set network port - non-standard port ex.: http://domain.com:81/ (forward into Http_Url/Url)
	*	@param string $v Network port
	*	@return self
	*/
	public function setPort($v)
	{
		$this->_data['url']->setPort($v);
		return $this;
	}
	
	/**
	*	Set URL path (forward into Http_Url/Url)
	*	@param string $v URL path
	*	@return self
	*/
	public function setPath($v)
	{
		$this->_data['url']->setPath($v);
		return $this;
	}
	
	/**
	*	Set URL query (forward into Http_Url/Url)
	*	@param string|array $v URL query
	*	@return self
	*/
	public function setQuery($v)
	{
		$this->_data['url']->setQuery($v);
		return $this;
	}
	
	/**
	*	Set default scheme (forward into Http_Url/Url)
	*	@param string $v URL scheme
	*	@return self
	*/
	public function setDefaultScheme($v)
	{
		$this->_data['url']->setDefaultScheme($v);
		return $this;
	}
	
	/**
	*	Set default host (forward into Http_Url/Url)
	*	@param string $v URL host
	*	@return self
	*/
	public function setDefaultHost($v)
	{
		$this->_data['url']->setDefaultHost($v);
		return $this;
	}
	
	/**
	*	Set default port (forward into Http_Url/Url)
	*	@param int $v URL port
	*	@return self
	*/
	public function setDefaultPort($v)
	{
		$this->_data['url']->setDefaultPort($v);
		return $this;
	}
	
	/**
	*	Set default path (forward into Http_Url/Url)
	*	@param string $v URL path
	*	@return self
	*/
	public function setDefaultPath($v)
	{
		$this->_data['url']->setDefaultPath($v);
		return $this;
	}
	
	/**
	*	Set default query (forward into Http_Url/Url)
	*	@param string $v URL query
	*	@return self
	*/
	public function setDefaultQuery($v)
	{
		$this->_data['url']->setDefaultQuery($v);
		return $this;
	}
	
	/**
	*	Set http method
	*	@param int $v self::METHOD_*
	*	@return int
	*	@throws Http_Request_Data_Exception
	*/
	public function setMethod($v)
	{
		switch($v)
		{
			case static::METHOD_AUTO:
			case static::METHOD_GET:
			case static::METHOD_HEAD:
			case static::METHOD_POST:
			case static::METHOD_PUT:
			case static::METHOD_PATCH:
			case static::METHOD_DELETE:
			case static::METHOD_TRACE:
			case static::METHOD_CONNECT:
				break;
			
			default:
				throw new Http_Request_Data_Exception('Unknown method');
		}
		
		$this->_data['method'] = $v;
		
		return $this;
	}
	
	/**
	*	Get http method
	*	@return int
	*/
	public function getMethod()
	{
		return $this->_data['method'];
	}
	
	/**
	*	Set http header
	*	@param string|int $k Header name
	*	@param string|int $v Header value (empty string will delete header)
	*	@return self
	*	@throws Http_Request_Data_Exception
	*/
	public function setHeader($k, $v)
	{
		if(!is_string($k) && !is_int($k) || $k == '')
			throw new Http_Request_Data_Exception('Header name must be a string or int and can\'t be null length');
		
		if(!is_string($v) && !is_int($v))
			throw new Http_Request_Data_Exception('Header value must be a string or int');
		
		if(false !== strpos($v, "\n"))
			throw new Http_Request_Data_Exception('Header value cant have new line char');
		
		if(false !== strpos($v, "\r"))
			throw new Http_Request_Data_Exception('Header value cant have carriage return char');
		
		if($v != '')
			$this->_data['headers'][$k] = $v;
		else
			unset($this->_data['headers'][$k]);
		
		return $this;
	}
	
	/**
	*	Set http header if wasn't set before
	*	@param string $k Header name
	*	@param string|int $v Header value
	*	@return self
	*/
	public function setHeaderIfNotExist($k, $v)
	{
		if(!is_string($v) && !is_int($v))
			throw new Http_Request_Data_Exception('Header value must be a string.');
		
		if(false !== strpos($v, "\n"))
			throw new Http_Request_Data_Exception('Header value cant have new line char.');
		
		if(false !== strpos($v, "\r"))
			throw new Http_Request_Data_Exception('Header value cant have carriage return char.');
		
		if(!isset($this->_data['headers'][$k]))
			$this->_data['headers'][$k] = $v;
		
		return $this;
	}
	
	/**
	*	Unset (drop) http header
	*	@param string $k Header name
	*	@return self
	*/
	public function unsetHeader($k)
	{
		if(!is_string($k))
			throw new Http_Request_Data_Exception('Header name must be a string.');
		unset($this->_data['headers'][$k]);
		return $this;
	}
	
	/**
	*	Get http header
	*	@param string $k Header name
	*	@return string|false Header value or false when is not exists
	*/
	public function getHeader($k)
	{
		return isset($this->_data['headers'][$k]) ? $this->_data['headers'][$k] : false;
	}
	
	/**
	*	Get http headers
	*	@return array headers
	*/
	public function getHeaders()
	{
		return $this->_data['headers'];
	}
	
	/**
	*	Check if header was set
	*	@param string $k Header name
	*	@return bool
	*/
	public function issetHeader($k)
	{
		return isset($this->_data['headers'][$k]);
	}
	
	/**
	*	Set (overwrite) post data
	*	@param array $v Post data ['name' => value]
	*	@return self
	*/
	public function setPost(array $v)
	{
		$this->_data['post'] = $v;
		return $this;
	}
	
	/**
	*	Set/add one post variable
	*	@param string $k Name of post variable
	*	@param string|int $v Variable value
	*	@return self
	*/
	public function setPostValue($k, $v)
	{
		if(!is_string($v) && !is_int($v))
			throw new Http_Request_Data_Exception('Post value must be a string.');
		
		$this->_data['post'][$k] = $v;
		return $this;
	}
	
	/**
	*	Get post data
	*	@return array
	*/
	public function getPost()
	{
		return $this->_data['post'];
	}
	
	/**
	*	Set (overwrite) files for request (http post)
	*	@param array $v Array with Http_Request_Data_File objects
	*	@return self
	*/
	public function setFiles(array $v)
	{
		$this->_data['files'] = $v;
		return $this;
	}
	
	/**
	*	Set (add) file for request (http post)
	*	@param Http_Request_Data_File $v File
	*	@return self
	*/
	public function setFile(Http_Request_Data_File $v)
	{
		$this->_data['files'][] = $v;
		return $this;
	}
	
	/**
	*	Get files data
	*	@return array
	*/
	public function getFiles()
	{
		return $this->_data['files'];
	}
	
	/**
	*	Set raw data (request body)
	*	@param string $v Raw data
	*	@return self
	*/
	public function setRawData($v)
	{
		$this->_data['rawData'] = $v;
		return $this;
	}
	
	/**
	*	Get raw data
	*	@return string
	*	@see setRawData()
	*/
	public function getRawData()
	{
		return $this->_data['rawData'];
	}
	
	/**
	*	Check if raw data was set
	*	@return bool
	*/
	public function issetRawData()
	{
		return $this->_data['rawData'] != '';
	}
	
	/**
	*	Get strlen (size) of raw data
	*	@see setRawData()
	*	@return int strlen of raw data
	*/
	public function getRawDataSizeOf()
	{
		return strlen($this->_data['rawData']);
	}
}