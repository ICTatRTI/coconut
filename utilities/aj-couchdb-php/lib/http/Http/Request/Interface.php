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
*	@version 0.5
*/

/**
*	Http request interface - Http Client frontend.
*	See included howto.
*	@category Http Client
*	@package Http Request
*	@version 0.5
*/
interface Http_Request_Interface
{
	/**
	*	Http_Request constructor.
	*	@note If first arg will be Http_Request_Data, then this object will be cloned. Cookies proxy will be also cloned.
	*	@param Http_Request_Data|Http_Url|Url|string $httpRequestData Http_Request_Data or Http_Url or Url or string with url
	*	@param Http_Cookie_Proxy_Interface $cookiesProxy Give cookie proxy object here if You want use other cookie proxy than internally created Http_Cookie_Proxy. If provided, it will be cloned.
	*	@return void
	*/
	public function __construct($httpRequestData, Http_Cookie_Proxy_Interface $cookiesProxy = null);
	
	/**
	*	Clone requestData and cookiesProxy objects and reset requestId.
	*	Use this when you need more same/modified requests to sent.
	*	@note After cloning, You cant get response or prepared requestLine/headers/body - in that case, You must resend it in Http_Client or call prepare() once again
	*	@return void
	*/
	//public function __clone();
	
	/**
	*	Prepare fo sending.
	*	If You want send same or modified request(s), You must clone this object - at anytime.
	*	@note Should be called by Http_Client only, and only once
	*	@return self.
	*	@uses _prepare()
	*/
	public function prepare();
	
	/**
	*	Get request id
	*	@return int Request id or 0 when wasn't sent (exacly, when Http_Request::prepare() was not called)
	*/
	public function getRequestId();
	
	/**
	*	Get method of sending data
	*	@return int
	*/
	public function getDataMethod();
	
	/**
	*	Set method of sending data
	*	@param int|null $v Data method
	*	@return self
	*/
	public function setDataMethod($v);
	
	/**
	*	Get headersOrder option
	*	@return string|array
	*/
	//public function getHeadersOrder();
	
	/**
	*	Set headersOrder option
	*	@param string|array $v
	*/
	//public function setHeadersOrder($v);
	
	/**
	*	Get option of using default headers
	*	@return bool
	*/
	//public function getUseDefaultHeaders();
	
	/**
	*	Enable/disable using default headers
	*	@param bool true to enable, false to disable
	*/
	//public function setUseDefaultHeaders();
	
	/**
	*	Get url by Http_Url::getUrl()
	*	@return string Url
	*/
	public function getUrl();
	
	/**
	*	Get url without query by Http_Url::getUrlWithoutQuery()
	*	@return string Url
	*/
	public function getUrlWithoutQuery();
	
	/**
	*	Get url with user credentials by Http_Url::getUrlWithUserCredentials()
	*	@return string Url
	*/
	public function getUrlWithUserCredentials();
	
	/**
	*	Get url with user credentials and without query by Http_Url::getUrlWithUserCredentialsAndWithoutQuery()
	*	@return string Url
	*/
	public function getUrlWithUserCredentialsAndWithoutQuery();
	
	/**
	*	Get url user by Http_Url::getUser()
	*	@return string Url user
	*/
	public function getUser();
	
	/**
	*	Get url pass by Http_Url::getUser()
	*	@return string Url user
	*/
	public function getPass();
	
	/**
	*	Get parsed url by Http_Url::parse()
	*	@return string Url
	*/
	public function getParsedUrl();
	
	/**
	*	Get uri by Http_Url::getUri()
	*	@return string Uri
	*/
	public function getUri();
	
	/**
	*	Get scheme by Http_Url::getScheme()
	*	@return string Url scheme
	*/
	public function getScheme();
	
	/**
	*	Get transport by Http_Url::getTransport()
	*	@return string Network transport
	*/
	public function getTransport();
	
	/**
	*	Get host by Http_Url::getHost()
	*	@return string Host name
	*/
	public function getHost();
	
	/**
	*	Get resolved network adress.
	*	@return string Target network adress (resolved from host name)
	*	@throws Http_Request_Exception
	*/
	public function getAddr();
	
	/**
	*	Get port by Http_Url::getPort()
	*	@return int
	*/
	public function getPort();
	
	/**
	*	Get real port by Http_Url::getPortReal()
	*	@return int
	*/
	public function getPortReal();
	
	/**
	*	Get path by Http_Url::getPath()
	*	@return string
	*/
	public function getPath();
	
	/**
	*	Get query by Http_Url::getQuery()
	*	@return string Uri query
	*/
	public function getQuery();
	
	/**
	*	Get http method
	*	@return int
	*/
	public function getMethod();
	
	/**
	*	Get request header
	*	@param string $k Header name
	*	@return string Header value
	*/
	public function getHeader($k);
	
	/**
	*	Get request headers
	*	@return array
	*/
	public function getHeaders();
	
	/**
	*	Check if header was set
	*	@param string $k Header name
	*	@return bool
	*/
	public function issetHeader($k);
	
	/**
	*	Get post data
	*	@return array
	*/
	public function getPost();
	
	/**
	*	Get request files (typically sent with post data)
	*	@return array
	*/
	public function getFiles();
	
	/**
	*	Get raw data
	*	@return string
	*/
	public function getRawData();
	
	/**
	*	Check if raw data was set
	*	@return bool
	*/
	public function issetRawData();
	
	/**
	*	Get length of raw data
	*	@return int
	*/
	public function getRawDataSizeOf();
	
	/**
	*	Forward calls (setters) to Http_Request_Data
	*	Http_Request_Exception will be throwed when method is not found or when trying to use setter after sending request
	*	@param string $name Method name
	*	@param array $args Args
	*	@return self
	*	@throws Http_Request_Exception
	*/
	//public function __call($name, $args);
	
	/**
	*	Create/get boundary for multipart/form-data request (used only on DATA_METHOD_POST_MULTIPART).
	*	Should be called after prepare() only. But is not prohibited.
	*	@return string Boundary string
	*/
	//public function getBoundary();
	
	/**
	*	Secure request data for cloning (backed up in __clone())
	*	@return self
	*/
	public function secureRequestData();
	
	/**
	*	Get first line of http request (without ending \\n)
	*	@return string Http request line (ex.: GET /?action=foo HTTP/1.1)
	*	@throws Http_Request_Exception
	*/
	public function getRequestLine();
	
	/**
	*	Get request headers in string
	*	@return string Request headers
	*	@throws Http_Request_Exception
	*/
	public function getRequestHeadersString();
	
	/**
	*	Get request body
	*	@return string Http request body
	*/
	public function getRequestBody();
	
	/**
	*	Get browser engine name correspodning to given user-agent
	*	@param string|null $ua user-agent or null to use set/default header
	*	@return string gecko or webkit
	*/
	public function getEngineNameFromUA($ua = null);
	
	/**
	*	Send this request via Http_Client
	*	@return self
	*/
	public function send();
	
	/**
	*	Get response for this request.
	*	@note If wasnt sent before, method send() will be called automatically
	*	@param bool $followLocation True to follow redirects (Location header)
	*	@return Http_Response
	*/
	public function getResponse($followLocation = true);
	
	/**
	*	Get response for this request (alias of getResponse()).
	*	@note If wasnt sent before, method send() will be called automatically
	*	@param bool $followLocation True to follow redirects (Location header)
	*	@return Http_Response
	*/
	public function __invoke($followLocation = true);
	
	/**
	*	Alias of getResponse()->getBody()
	*	@return string
	*/
	public function __toString();
	
	/**
	*	Set cookies from response header(s) value(s).
	*	@param array $cookieHeaders Array with values of response 'Set-Cookie' header(s) value(s)
	*	@return self
	*/
	public function setCookiesFromResponseHeadersValues(array $cookieHeaders);
	
	/**
	*	Store cookie from 'Set-Cookie' header value
	*	@param string $headerValue Value of 'Set-Cookie' header
	*	@return self
	*/
	public function setCookieFromHeaderValue($headerValue);
	
	/**
	*	Set temporary cookie.
	*	@note It will be forwarded into internally keeped instance of Http_Cookie_Proxy_Interface
	*	@param Http_Cookie $cookie Cookie to temporary use
	*	@return self
	*/
	public function setTempCookie(Http_Cookie $cookie);
	
	/**
	*	Temporary disable cookie - forward call into cookie proxy.
	*	If temporary cookie exists here (in a cookie proxy) with same name, it will be dropped - if You need to temporary cookie replace (other value or something) use setTempCookie().
	*	@param string|Http_Cookie $name Cookie name or cookie object to get name from it (cookie wih this name will be temporary disabled)
	*	@return self
	*/
	public function disableCookie($name);
	
	/**
	*	Search for cookie names that should be sent in this request (for given host and path)
	*	@param array $names Cookie names
	*	@return array Cookie objects with given names if founded
	*/
	public function cookiesSearch(array $names);
	
	/**
	*	Internal usage only
	*	@return Http_Cookie_Proxy_Interface
	*	@used-by Http_Client
	*/
	public function _getCookieProxy();
	
	/**
	*	Change cookie proxy
	*	@param Http_Cookie_Proxy_Interface|null $proxy Http cookie proxy. Give null to use default.
	*	@return self
	*/
	public function setCookieProxy(Http_Cookie_Proxy_Interface $proxy = null);
	
	/**
	*	Forward setCookie() into cookie proxy
	*	@param Http_Cookie $cookie Cookie object
	*	@return self
	*/
	public function setCookie(Http_Cookie $cookie);
}