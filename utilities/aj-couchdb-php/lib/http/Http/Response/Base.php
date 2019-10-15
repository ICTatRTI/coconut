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
*	Http client response
*	@category Http Client
*	@package Http Client
*	@subpackage Http Client Response
*	@version 0.5
*/
class Http_Response_Base implements Http_Response_Interface
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Response data
	*	@var array
	*/
	protected $_data =
	[
		'body' => '',
		'bodyRaw' => '',
		'headers' => [],
		'headerSource' => '',
		'status' => 0,
		'statusRaw' => 'MISSING',
		'request' => null // request object
	];
	
	/**
	*	Time when request was wroted to the socket
	*	@var float
	*/
	protected $_timeSent = 0.0;
	
	/**
	*	Time when request was readed from the socket
	*	@var float
	*/
	protected $_timeReceived = 0.0;
	
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
	*	@param int|float $timeSent Time when request was wroted to the socket
	*	@param int|float $timeReceived Time when request was readed from the socket
	*	@return void
	*/
	public function __construct($data, Http_Request $request, $timeSent = 0, $timeReceived = 0)
	{
		if(!$data)
			throw new Http_Response_Exception('Empty data');
		if(!($data instanceof Http_Response_Parser) && !is_array($data))
			throw new Http_Response_Exception('data is not an array and not instance of Http_Response_Parser');
		
		if(is_array($data))
		{
			if(!isset($data['body']))
				$data['body'] = '';
			elseif(!isset($data['bodyRaw']))
				$data['bodyRaw'] = '';
			else
				throw new Http_Response_Exception('Missing body/bodyRaw');
			
			if(!isset($data['headers']))
				throw new Http_Response_Exception('Missing headers');
			if(!isset($data['headerSource']))
				throw new Http_Response_Exception('Missing headerSource');
			if(!isset($data['status']))
				throw new Http_Response_Exception('Missing status');
			if(!isset($data['statusRaw']))
				throw new Http_Response_Exception('Missing statusRaw');
			
			$this->_data = $data;
		}
		else
		{
			$this->_data['headers'] = $data->getHeaders();
			$this->_data['headerSource'] = $data->getHeaderSource();
			$this->_data['status'] = $data->getStatus();
			$this->_data['statusRaw'] = $data->getStatusRaw();
			$this->_data['bodyRaw'] = $data->getBodyRaw();
		}
		
		if($request === null)
			throw new Http_Response_Exception('Request not provided');
		
		$this->_data['request'] = $request;
		
		$this->_timeSent = $timeSent;
		$this->_timeReceived = $timeReceived;
	}
	
	/**
	*	Get body of response (without headers)
	*	@return string Http body (content)
	*/
	public function getBody()
	{
		if($this->_data['body'] != '')
			return $this->_data['body'];
		if($this->_data['bodyRaw'] == '')
			return '';
		
		($enc = $this->getHeader('Content-Encoding')) || ($enc = $this->getHeader('content-encoding'));
		
		//$mbOldEncoding = mb_internal_encoding();
        $mbOldEncoding = false;
		//mb_internal_encoding('8bit');

		if($enc == 'gzip')
			$this->_data['body'] = $this->decodeGzip($this->_data['bodyRaw']);
		elseif($enc == 'deflate')
			$this->_data['body'] = $this->decodeDeflate($this->_data['bodyRaw']);
		else
			$this->_data['body'] = $this->_data['bodyRaw'];
		
		if($mbOldEncoding)
			mb_internal_encoding($mbOldEncoding);
		
		return $this->_data['body'];
	}
	
	/**
	*	Alias of getBody()
	*	@return string Http body (content)
	*	@see getBody()
	*/
	public function __toString()
	{
		return $this->getBody();
	}
	
	/**
	*	Get unpacked (unzipped if was) body of response (without headers)
	*	@return string Unpacked http body (content)
	*/
	public function getBodyRaw()
	{
		return $this->_data['bodyRaw'];
	}
	
	/**
	*	Get array with all headers ex.: ['Content-Length' => '55697', '...' => '..']
	*	@return array Headers
	*/
	public function getHeadersAll()
	{
		return $this->_data['headers'];
	}
	
	/**
	*	Get original response headers in string
	*	@return string All http header (http header not http headers)
	*/
	public function getHeaderSource()
	{
		return $this->_data['headerSource'];
	}
	
	/**
	*	Get response header(s) with one name
	*	@param string|null $name Header name (null to get all headers)
	*	@param bool $get_all True to get array with headers with this name or false to get first one in string
	*	@return string|array|null Array with headers with given name - ex.: ['Cookie' => '...', 'Cookie' => '...'], or string with header value when second arg is false. Null when header was not in response.
	*/
	public function getHeaders($name, $get_all = true)
	{
		if($name === null)
			return $this->_data['headerSource'];
		
		if($get_all)
		{
			$ret = [];
			foreach($this->_data['headers'] as $v)
				if($v[0] == $name)
					$ret[] = $v;
			return $ret;
		}
		
		foreach($this->_data['headers'] as $v)
			if($v[0] == $name)
				return $v[1];
	}
	
	/**
	*	Get first response header by name
	*	@param string $name Header name
	*	@return string|null Header value or null when was not in response
	*/
	public function getHeader($name)
	{
		foreach($this->_data['headers'] as $v)
			if($v[0] == $name)
				return $v[1];
	}
	
	/**
	*	Get numerical status of response
	*	@return int Http response status (ex.: 200)
	*/
	public function getStatus()
	{
		return $this->_data['status'];
	}
	
	/**
	*	Get raw response status
	*	@return string Http response status (ex.: 200 OK)
	*/
	public function getStatusRaw()
	{
		return $this->_data['statusRaw'];
	}
	
	/**
	*	Based (and little modified) on http://pear.php.net/package/HTTP_Request2 (class HTTP_Request2_Response)
	*	@param string $data Encoded http body with gzip (Content-Encoding: gzip)
	*	@return string Decoded body
	*	@throws Http_Response_Exception
	*/
	public function decodeGzip($data)
	{
		if($t = @gzdecode($data))
			return $t;

		$length = strlen($data);

		// If it doesn't look like gzip-encoded data, don't bother
		if(18 > $length || strcmp(substr($data, 0, 2), "\x1f\x8b"))
			throw new Http_Response_Exception('Looks like not gzip encoded...');
		
		if(!function_exists('gzinflate'))
			throw new Http_Response_Exception('Unable to decode body: gzip extension not available');
		
		$method = ord(substr($data, 2, 1));

		if(8 != $method)
			throw new Http_Response_Exception('Error parsing gzip header: unknown compression method');
		
		$flags = ord(substr($data, 3, 1));
		
		if($flags & 224)
			throw new Http_Response_Exception('Error parsing gzip header: reserved bits are set');
		
		// header is 10 bytes minimum. may be longer, though.
		$headerLength = 10;
		
		// extra fields, need to skip 'em
		if($flags & 4)
		{
			if($length - $headerLength - 2 < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$extraLength = unpack('v', substr($data, 10, 2));

			if($length - $headerLength - 2 - $extraLength[1] < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$headerLength += $extraLength[1] + 2;
		}

		// file name, need to skip that
		if($flags & 8)
		{
			if($length - $headerLength - 1 < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$filenameLength = strpos(substr($data, $headerLength), chr(0));

			if(false === $filenameLength || $length - $headerLength - $filenameLength - 1 < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$headerLength += $filenameLength + 1;
		}
		
		// comment, need to skip that also
		if($flags & 16)
		{
			if($length - $headerLength - 1 < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$commentLength = strpos(substr($data, $headerLength), chr(0));

			if (false === $commentLength || $length - $headerLength - $commentLength - 1 < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$headerLength += $commentLength + 1;
		}
		
		// have a CRC for header. let's check
		if($flags & 2)
		{
			if($length - $headerLength - 2 < 8)
				throw new Http_Response_Exception('Error parsing gzip header: data too short');

			$crcReal   = 0xffff & crc32(substr($data, 0, $headerLength));
			$crcStored = unpack('v', substr($data, $headerLength, 2));
			
			if($crcReal != $crcStored[1])
				throw new Http_Response_Exception('Header CRC check failed');

			$headerLength += 2;
		}
		
		// unpacked data CRC and size at the end of encoded data
		$t = unpack('V2', substr($data, -8));
		$dataCrc  = $t[1];
		$dataSize = $t[2];

		// finally, call the gzinflate() function
		// don't pass $dataSize to gzinflate, see bugs #13135, #14370
		$unpacked = gzinflate(substr($data, $headerLength, -8));

		if($unpacked === false)
			throw new Http_Response_Exception('gzinflate() call failed');
		if($dataSize != strlen($unpacked))
			throw new Http_Response_Exception('Data size check failed');
		if((0xffffffff & $dataCrc) != (0xffffffff & crc32($unpacked)))
			throw new Http_Response_Exception('Data CRC check failed');
		
		return $unpacked;
	}
	
	/**
	*	Decodes the message-body encoded by deflate
	*	Based on http://pear.php.net/package/HTTP_Request2 (class HTTP_Request2_Response)
	*	@param string $data deflate-encoded data
	*	@return string Decoded body
	*	@throws Http_Response_Exception
	*/
	public static function decodeDeflate($data)
	{
		if(!function_exists('gzuncompress'))
			throw new Http_Response_Exception('Unable to decode body: gzip extension not available');

		// RFC 2616 defines 'deflate' encoding as zlib format from RFC 1950,
		// while many applications send raw deflate stream from RFC 1951.
		// We should check for presence of zlib header and use gzuncompress() or
		// gzinflate() as needed. See bug #15305
		$header = unpack('n', substr($data, 0, 2));
		return (0 == $header[1] % 31) ? gzuncompress($data) : gzinflate($data);
	}
	
	/**
	*	Get request object used to receive this response
	*	@return Http_Request
	*	@see getRequestId()
	*/
	public function getRequest()
	{
		return $this->_data['request'];
	}
	
	/**
	*	Get http url (shortcut to getRequest()->getUrl())
	*	@return string Http url
	*/
	public function getUrl()
	{
		return $this->_data['request']->getUrl();
	}
	
	/**
	*	Get Url with user credentials (shortcut to getRequest()->getUrlWithUserCredentials())
	*	@return string Url with user credentials
	*/
	public function getUrlWithUserCredentials()
	{
		return $this->_data['request']->getUrlWithUserCredentials();
	}
	
	/**
	*	Get url user (shortcut to getRequest()->getUser())
	*	@return string Url user
	*/
	public function getUser()
	{
		return $this->_data['request']->getUser();
	}
	
	/**
	*	Get url pass (shortcut to getRequest()->getPass())
	*	@return string Url user pass
	*/
	public function getPass()
	{
		return $this->_data['request']->getPass();
	}
	
	/**
	*	Get http uri (shortcut to getRequest()->getUri())
	*	@return string Http uri (/foo/?aaa=bbb)
	*/
	public function getUri()
	{
		return $this->_data['request']->getUri();
	}
	
	/**
	*	Get http path (shortcut to getRequest()->getPath())
	*	@return string Http path (/foo/)
	*/
	public function getPath()
	{
		return $this->_data['request']->getPath();
	}
	
	/**
	*	Get http query (shortcut to getRequest()->getQuery())
	*	@return string Http query (?aaa=bbb)
	*/
	public function getQuery()
	{
		return $this->_data['request']->getQuery();
	}
	
	/**
	*	Get http scheme (shortcut to getRequest()->getScheme())
	*	@return string Http scheme (http or https)
	*/
	public function getScheme()
	{
		return $this->_data['request']->getScheme();
	}
	
	/**
	*	Get network transport layer (shortcut to getRequest()->getTransport())
	*	@return string Network transport
	*/
	public function getTransport()
	{
		return $this->_data['request']->getTransport();
	}
	
	/**
	*	Get http host (shortcut to getRequest()->getHost())
	*	@see getPort()
	*	@see getHost()
	*	@return string Http host
	*/
	public function getHost()
	{
		return $this->_data['request']->getHost();
	}
	
	/**
	*	Get http port (shortcut to getRequest()->getPort())
	*	@see getPortReal()
	*	@return int Http port
	*/
	public function getPort()
	{
		return $this->_data['request']->getPort();
	}
	
	/**
	*	Get http real port (shortcut to getRequest()->getPort())
	*	@see getPort()
	*	@return int Http port
	*/
	public function getPortReal()
	{
		return $this->_data['request']->getPortReal();
	}
	
	/**
	*	Get http network address (shortcut to getRequest()->getAddr())
	*	@see getHost()
	*	@return string Network address (resolved when sending request)
	*/
	public function getAddr()
	{
		return $this->_data['request']->getAddr();
	}
	
	/**
	*	Get http method (shortcut to getRequest()->getMethod())
	*	@return int Http_Request_Data::METHOD_GET or Http_Request_Data::METHOD_POST
	*/
	public function getMethod()
	{
		return $this->_data['request']->getMethod();
	}
	
	/**
	*	Get http post data (shortcut to getRequest()->getPost())
	*	@return array Http post
	*/
	public function getPost()
	{
		return $this->_data['request']->getPost();
	}
	
	/**
	*	Get requestId from request object (shortcut to getRequest()->getRequestId())
	*	@return int requestId
	*	@see getRequest()
	*/
	public function getRequestId()
	{
		return $this->_data['request']->getRequestId();
	}
	
	/**
	*	Get time when request was wroted to the socket
	*	@return float
	*/
	public function getTimeSent()
	{
		return $this->_timeSent;
	}
	
	/**
	*	Get time when request was readed from the socket
	*	@return float
	*/
	public function getTimeReceived()
	{
		return $this->_timeReceived;
	}
}