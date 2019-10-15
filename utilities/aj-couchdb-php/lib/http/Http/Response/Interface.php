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
*	@subpackage Http Client Response
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/**
*	Http client response - interface
*	@category Http Client
*	@package Http Client
*	@subpackage Http Client Response
*	@version 0.5
*/
interface Http_Response_Interface
{
	/**
	*	Constructor - set all data.
	*	Gets data in array (ex. listed below) or object in Http_Response_Parser
	*	<code>
	*	[
	*		'body' => '', // and/or 'bodyRaw'
	*		'headers' => [],
	*		'headerSource' => '',
	*		'status' => 200,
	*		'statusRaw' => ''
	*	]
	*	</code>
	*	@param Http_Response_Parser|array $data
	*	@param Http_Request $request Request object used to get this response
	*	@return void
	*/
	public function __construct($data, Http_Request $request);
	
	/**
	*	Get body of response (without headers)
	*	@return string Http body (content)
	*/
	public function getBody();
	
	/**
	*	Alias of getBody()
	*	@return string Http body (content)
	*	@see getBody()
	*/
	public function __toString();
	
	/**
	*	Get unpacked (unzipped if was) body of response (without headers)
	*	@return string Unpacked http body (content)
	*/
	public function getBodyRaw();
	
	/**
	*	Get array with all headers ex.: ['Content-Length' => '55697', '...' => '..']
	*	@return array Headers
	*/
	public function getHeadersAll();
	
	/**
	*	Get original response headers in string
	*	@return string All http header (http header not http headers)
	*/
	public function getHeaderSource();
	
	/**
	*	Get response header(s) with one name
	*	@param string $name Header name
	*	@param bool $get_all True to get array with headers with this name or false to get first one in string
	*	@return string|array|null Array with headers with given name - ex.: ['Cookie' => '...', 'Cookie' => '...'], or string with header value when second arg is false
	*/
	public function getHeaders($name, $get_all = true);
	
	/**
	*	Get first response header by name
	*	@param string $name Header name
	*	@return string|null Header value or null when was not in response
	*/
	public function getHeader($name);
	
	/**
	*	Get numerical status of response
	*	@return int Http response status (ex.: 200)
	*/
	public function getStatus();
	
	/**
	*	Get raw response status
	*	@return string Http response status (ex.: 200 OK)
	*/
	public function getStatusRaw();
	
	/**
	*	Get request object used to receive this response
	*	@return Http_Request
	*/
	public function getRequest();
	
	/**
	*	Get http url (shortcut to request->getUrl())
	*	@return string Http url
	*/
	public function getUrl();
	
	/**
	*	Get http uri (shortcut to request->getUri())
	*	@return string Http uri (/foo/?aaa=bbb)
	*/
	public function getUri();
	
	/**
	*	Get http path (shortcut to request->getPath())
	*	@return string Http path (/foo/)
	*/
	public function getPath();
	
	/**
	*	Get http query (shortcut to request->getQuery())
	*	@return string Http query (?aaa=bbb)
	*/
	public function getQuery();
	
	/**
	*	Get http scheme (shortcut to request->getScheme())
	*	@return string Http scheme (http or https)
	*/
	public function getScheme();
	
	/**
	*	Get network transport layer (shortcut to request->getTransport())
	*	@return string Network transport
	*/
	public function getTransport();

	/**
	*	Get http host (shortcut to request->getHost())
	*	@see getPort()
	*	@see getHost()
	*	@return string Http host
	*/
	public function getHost();
	
	/**
	*	Get http port (shortcut to request->getPort())
	*	@see getPortReal()
	*	@return int Http port
	*/
	public function getPort();
	
	/**
	*	Get http real port (shortcut to request->getPort())
	*	@see getPort()
	*	@return int Http port
	*/
	public function getPortReal();
	
	/**
	*	Get http network address (shortcut to request->getAddr())
	*	@see getHost()
	*	@return string Network address (resolved when sending request)
	*/
	public function getAddr();
	
	/**
	*	Get http method (shortcut to request->getMethod())
	*	@return int Http_Request_Data::METHOD_GET or Http_Request_Data::METHOD_POST
	*/
	public function getMethod();
	
	/**
	*	Get http post data (shortcut to request->getPost())
	*	@return array Http post
	*/
	public function getPost();
	
	/**
	*	Get requestId from request object
	*	@return int requestId
	*/
	public function getRequestId();
}