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

/**
*	Http client - response reader-parser for Http_Client.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Client
*	@version 0.5
*/
class Http_Response_Parser_Base
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Readed http header (http headers)
	*	@var string
	*/
	protected $_headerSorce = '';
	
	/**
	*	Responce http headers
	*	@var array
	*/
	protected $_headers = [];
	
	/**
	*	Raw response status
	*	@var string
	*/
	protected $_statusRaw = '';
	
	/**
	*	Response status in int
	*	@var int
	*/
	protected $_status = '';
	
	/**
	*	Response http protocol (HTTP/1.1 or HTTP/1.0)
	*	@var string
	*/
	protected $_responseProtocol = '';
	
	/**
	*	Response http raw body (without unpacking)
	*	@var string
	*/
	protected $_bodyRaw = '';
	
	/**
	*	Connetion type (parsed from sv response)
	*	@var string
	*/
	protected $_connectionType = 'close';
	
	/**
	*	Content-Enconding header (if founded)
	*	@var string
	*/
	protected $_contentEncoding = '';
	
	/**
	*	Params from Keep-Alive response header (if founded)
	*	@var array
	*/
	protected $_keepAliveParams = [];
	
	/**
	*	Params from Cache-Control/Pragma/Date/Expires response headers (if founded)
	*	@var array
	*/
	protected $_cacheParams = [];
	
	/**
	*	Values from 'Set-Cookie' header(s)
	*	@var array
	*/
	protected $_setCookies = [];
	
	/**
	*	Location header value
	*	@var string
	*/
	protected $_location = '';
	
	/**
	*	Constructor with reader/parser
	*	@param Http_Client_Connection $connection Http connection object
	*	@return void
	*/
	public function __construct(Http_Client_Connection $connection)
	{
		/// All in one method for less execution time
		
		$readed = 0;
		while(true)
		{
			try
			{
				$this->_headerSorce .= $connection->read(1);
			}
			catch(Socket_Exception $e)
			{
				throw new Http_Response_Parser_Exception('Socket throwed an exception with message: ' . $e->getMessage());
			}
			
			++$readed;
			
			if($readed >= 2)
			if
			(
				$this->_headerSorce{$readed - 1} == "\n"
				&&
				(($readed >= 2 && substr($this->_headerSorce, -2) == "\n\n") || ($readed >= 4 && substr($this->_headerSorce, -4) == "\r\n\r\n"))
			)
				break;
		}
		
		if(($this->_headerSorce = rtrim($this->_headerSorce)) == '')
			throw new Http_Response_Parser_Exception('Server returned empty response');
		
		if($this->_headerSorce != ltrim($this->_headerSorce))
			throw new Http_Response_Parser_Exception('White space in first chars of response'); // This is not strongly neccessary, because we use sscanf()
		
		$this->_headers = explode("\n", str_replace("\r\n", "\n", $this->_headerSorce));
		
		$t = array_shift($this->_headers);

		if(3 !== sscanf($t, 'HTTP/%f %i %[^\n]', $t, $t1, $t2) || $t < 1 || $t1 < 100 || $t1 > 999 || $t2 == '')
			throw new Http_Response_Parser_Exception('Looks like not a regular http response');
		
		$this->_responseProtocol = 'HTTP/' . $t;
		$this->_status = $t1;
		$this->_statusRaw = $t1 . ' ' . $t2;
		
		// Parse headers to know how to get body
		
		$responseIsChunked = false;
		$contentLength = -1;
		$connectionClose = true;
		
		$header_transfer_encoding_founded = false;
		$header_content_length_founded = false;
		$header_connection_founded = false;
		$header_content_encoding_founded = false;
		$header_keep_alive_founded = false;
		$header_cache_control_founded = false;
		$header_location_founded = false;
		
		foreach($this->_headers as &$header)
		{
			if(count($t = explode(': ', $header, 2)) !== 2)
				Http_Response_Parser_Exception('Bad response header');
			
			list($name, $value) = $t;
			
			$header = [$name, $value]; // Replace
			
			$name = strtolower($name);
			
			switch($name)
			{
				case 'transfer-encoding':
					if($header_transfer_encoding_founded)
						throw new Http_Response_Parser_Exception('Two or more \'Transfer-Encoding\' headers in response');
					$header_transfer_encoding_founded = true;
					
					if($value == 'chunked')
						$responseIsChunked = true;
					else
						throw new Http_Response_Parser_Exception('Unknown transfer-encoding');
				break;
				
				case 'content-length':
					if($header_content_length_founded)
						throw new Http_Response_Parser_Exception('Two or more \'Content-Length\' headers in response');
					$header_content_length_founded = true;
					
					if(!is_numeric($value) || $value != (int)$value || $value < 0)
						throw new Http_Response_Parser_Exception('Bad Content-Length header in response');
					$contentLength = (int)$value;
				break;
				
				case 'connection':
					if($header_connection_founded)
					{
						trigger_error('Two or more \'Connection\' headers in response', E_USER_WARNING);
						$connectionClose = true;
					}
					$header_connection_founded = true;
					
					switch($value)
					{
						case 'keep-alive':
						case 'Keep-Alive':
						case 'Keep-alive':
							$connectionClose = false;
						break;
						
						case 'close':
						case 'Close':
						break;
						
						default:
							trigger_error('Bad \'Connection\' header in response', E_USER_WARNING);
					}
				break;
				
				case 'content-encoding':
					if($header_content_encoding_founded)
						throw new Http_Response_Parser_Exception('Two or more \'Content-Encoding\' headers in response');
					$header_content_encoding_founded = true;
					
					$this->_contentEncoding = $value;
				break;
				
				case 'keep-alive':
					if($header_keep_alive_founded)
						trigger_error('Two or more \'Keep-Alive\' headers in response', E_USER_WARNING);
					$header_keep_alive_founded = true;
					
					if($value != '')
					foreach(explode(',', $value) as $t)
					{
						if(count($t = explode('=', $t)) == 2)
							$this->_keepAliveParams[trim($t[0])] = trim($t[1]);
					}
				break;
				
				case 'set-cookie':
					if($value == '')
						trigger_error('Null length \'Set-Cookie\' header value', E_USER_WARNING);
					else
						$this->_setCookies[] = $value;
				break;
				
				case 'cache-control':
					if($header_cache_control_founded)
						trigger_error('Two or more \'Cache-Control\' headers in response', E_USER_WARNING);
					$header_cache_control_founded = true;
					
					$this->_cacheParams['cache-control'] = explode(',', str_replace(', ', ',', $value));
				break;
				
				case 'location':
					if($header_location_founded)
						trigger_error('Two or more \'Location\' headers in response', E_USER_WARNING);
					$header_location_founded = true;
					
					$this->_location = $value;
				break;
				
				case 'pragma':
					$this->_cacheParams['pragma'] = $value;
				break;
				
				case 'date':
					$this->_cacheParams['date'] = $value;
				break;
				
				case 'expires':
					$this->_cacheParams['expires'] = $value;
				break;
				
				case 'last-modified':
					$this->_cacheParams['last-modified'] = $value;
				break;
				
				case 'etag':
					$this->_cacheParams['etag'] = $value;
				break;
			}
		}
		
		if(!$connectionClose)
			$this->_connectionType = 'keep-alive';
		
		//if($this->_status == 304)
		//	return; // 304 (should) send empty body, and without headers Content-Length & Content-Encoding
		
		// empty body, so we dont do anything
		if($contentLength == 0)
			return;
		
		if($responseIsChunked)
		{
			$data = '';
			while(true)
			{
				$line = $connection->readLine();
				
				if(strpos($line, ' ') !== false)
					list($chunksize, $chunkext) = explode(' ', $line, 2);
				else
				{
					$chunksize = $line;
					$chunkext  = '';
				}
				
				$chunksize = '0' . trim($chunksize); // additional 0 for sure - without it, hexdec() sometimes can give something bad...
				
				if(!ctype_xdigit($chunksize))
					throw new Http_Response_Parser_Exception('Chunk size in response is not hexadecimal');
				
				$chunksize = hexdec($chunksize);
				
				if($chunksize == 0)
				{
					$connection->readLine(); // read trailing "\r\n"
					return;
				}
				
				$this->_bodyRaw .= substr($connection->read($chunksize + 2), 0, -2); // 2 bytes to remove the "\r\n" before the next chunk
			}
		}
		
		if($contentLength > 0)
		{
			$this->_bodyRaw = $connection->read($contentLength);
			return;
		}
		
		if($connectionClose)
		{
			$this->_bodyRaw = $connection->read();
			return;
		}
		
		//throw new Http_Client_Exception('Looks like not a HTTP response');
	}
	
	/**
	*	Get http header
	*	@return string Http header without new lines on end
	*/
	public function getHeaderSource()
	{
		return $this->_headerSorce;
	}
	
	/**
	*	Get http header sizeof
	*	@return int Http header size in bytes without new lines on end
	*/
	public function getHeaderSizeOf()
	{
		return strlen($this->_headerSorce);
	}
	
	/**
	*	Get http headers
	*	@return array Http headers
	*/
	public function getHeaders()
	{
		return $this->_headers;
	}
	
	/**
	*	Get first response header by name
	*	@param string $name Header name
	*	@return string|null Header value or null when was not in response
	*/
	public function getHeader($name)
	{
		foreach($this->_headers as $v)
			if($v[0] == $name)
				return $v[1];
	}
	
	/**
	*	Get http raw body (without deflating/unziping)
	*	@return int Raw http body
	*	@see getBodyRawSizeOf()
	*	@see getSizeOf()
	*/
	public function getBodyRaw()
	{
		return $this->_bodyRaw;
	}
	
	/**
	*	Get http raw body size in bytes
	*	@return string Raw http body size in bytes
	*	@see getBodyRaw()
	*	@see getSizeOf()
	*/
	public function getBodyRawSizeOf()
	{
		return strlen($this->_bodyRaw);
	}
	
	/**
	*	Get response sizeof (headers + raw body)
	*	@return int Size of headers and raw body in bytes
	*	@see getHeaderSizeOf()
	*	@see getBodyRawSizeOf()
	*/
	public function getSizeOf()
	{
		return strlen($this->_headerSorce) + strlen($this->_bodyRaw);
	}
	
	/**
	*	Get http response status
	*	@return int Http response status
	*/
	public function getStatus()
	{
		return $this->_status;
	}
	
	/**
	*	Get raw http response status
	*	@return string Http response status ex.: 200 OK
	*/
	public function getStatusRaw()
	{
		return $this->_statusRaw;
	}
	
	/**
	*	Get http response protocol
	*	@return string Http response protocol ex.: HTTP/1.1
	*/
	public function getResponseProtocol()
	{
		return $this->_responseProtocol;
	}
	
	/**
	*	Get http connection type close/keep-alive
	*	@return string Http connection type - close or keep-alive
	*/
	public function getConnectionType()
	{
		return $this->_connectionType;
	}
	
	/**
	*	Get http header Content-Enconding
	*	@return string Header value
	*/
	public function getContentEncoding()
	{
		return $this->_contentEncoding;
	}
	
	/**
	*	Get params from Keep-Alive header
	*	@return array Params from Keep-Alive header
	*/
	public function getKeepAliveParams()
	{
		return $this->_keepAliveParams;
	}
	
	/**
	*	Get cache params from response headers
	*	@return array Params from Keep-Alive header
	*/
	public function getCacheParams()
	{
		return $this->_cacheParams;
	}
	
	/**
	*	Get value(s) from 'Set-Cookie' header(s)
	*	@return array Value(s) from 'Set-Cookie' header(s)
	*/
	public function getSetCookieHeadersValues() // yes I know, the name of it is a little creepy
	{
		return $this->_setCookies;
	}
	
	/**
	*	Get value of Location header
	*	@return string Location header value, null length when wasn't in response
	*/
	public function getLocationHeader()
	{
		return $this->_location;
	}
}