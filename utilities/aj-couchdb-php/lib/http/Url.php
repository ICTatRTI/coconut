<?php
/**
*	Url
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
*	@category Lib
*	@package Url
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/**
*	Object Url
*	@category Lib
*	@package Url
*	@version 0.5
*/
class Url
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Url data
	*	@var array
	*/
	protected $_data =
	[
		'parsedUrl' => null,
		'scheme' => '',
		'host' => '',
		'port' => 0,
		'path' => '',
		'query' => '',
		'user' => '',
		'pass' => ''
	];

	/**
	*	Defaults that can be changed on-fly (if some data is not set, then params will be taken from here)
	*	@var array
	*/
	protected $_defaults =
	[
		'parsedUrl' => null,
		'scheme' => '',
		'host' => '',
		'port' => 0,
		'path' => '/',
		'query' => '',
		'user' => '',
		'pass' => ''
	];
	
	/**
	*	Used in clean()
	*	@var array
	*/
	protected $_preData =
	[
		'parsedUrl' => null,
		'scheme' => '',
		'host' => '',
		'port' => 0,
		'path' => '',
		'query' => '',
		'user' => '',
		'pass' => ''
	];
	
	/**
	*	Used in purge()
	*	@var array
	*/
	protected $_preDefaults =
	[
		'scheme' => '',
		'host' => '',
		'port' => 0,
		'path' => '/',
		'query' => '',
		'user' => '',
		'pass' => ''
	];
	
	/**
	*	Clean url data with 'parsedUrl', but without defaults
	*	@return self
	*/
	public function clean()
	{
		$this->_data = $this->_preData;
		return $this;
	}
	
	/**
	*	Clean all data with defaults and with 'parsedUrl'
	*	@return self
	*/
	public function purge()
	{
		$this->clean();
		
		$this->_defaults = $this->_preDefaults;
		return $this;
	}
	
	/**
	*	Constructor with optional url parsing (by parse())
	*	@param string|null $s Optional string with url
	*	@return void
	*	@uses parse()
	*	@throws Url_Exception
	*/
	public function __construct($s = null)
	{
		if($s !== null)
			$this->parse($s);
	}
	
	/**
	*	Parse string with url (by parse_url() php function)
	*	@param string $s Url
	*	@return self
	*	@throws Url_Exception
	*/
	public function parse($s)
	{
		if(!is_string($s))
			throw new Url_Exception('String only');
		if($s == '')
			throw new Url_Exception('Empty string');
		if(!is_array($pu = parse_url($s)))
			throw new Url_Exception('Php parse_url() failed');
		
		$this->clean();
		$this->_data['parsedUrl'] = $s;
		
		if(isset($pu['scheme']))
			$this->setScheme($pu['scheme']);
		if(isset($pu['host']))
			$this->setHost($pu['host']);
		if(isset($pu['port']))
			$this->setPort($pu['port']);
		if(isset($pu['path']))
			$this->setPath($pu['path']);
		if(isset($pu['query']))
			$this->setQuery($pu['query']);
		if(isset($pu['user']))
			$this->setUser($pu['user']);
		if(isset($pu['pass']))
			$this->setPass($pu['pass']);
		
		return $this;
	}
	
	/**
	*	Get parsed url (from parse() or __construct())
	*	@return string
	*/
	public function getParsedUrl()
	{
		return $this->_data['parsedUrl'];
	}
	
	/**
	*	Bulid URL with given data/defaults but without user credentials
	*	@return string URL
	*/
	public function getUrl()
	{
		$scheme = $this->getScheme();
		$port = $this->getPort();
		$query = $this->getQuery();
		return $scheme . ($scheme == '' ? '' : '://') . $this->getHost() . ($port ? ':' . $port : '') . $this->getPath() . ($query == '' ? '': '?' . $query);
	}
	
	/**
	*	Bulid URL with given data/defaults but without query
	*	@return string URL
	*/
	public function getUrlWithoutQuery()
	{
		$scheme = $this->getScheme();
		$port = $this->getPort();
		return $scheme . ($scheme == '' ? '' : '://') . $this->getHost() . ($port ? ':' . $port : '') . $this->getPath();
	}
	
	/**
	*	Bulid URL with given data/defaults - with user credentials (me@password)
	*	@return string URL
	*/
	public function getUrlWithUserCredentials()
	{
		$scheme = $this->getScheme();
		$port = $this->getPort();
		$query = $this->getQuery();
		$user = $this->getUser();
		$pass = $this->getPass();
		$noPass = $pass == '';
		return $scheme . ($scheme == '' ? '' : '://') . $user . ($noPass ? '' : ':' . $pass) . (!$noPass || $user == '' ? '' : '@') . $this->getHost() . ($port ? ':' . $port : '') . $this->getPath() . ($query == '' ? '' : '?' . $query);
	}
	
	/**
	*	Bulid URL with given data/defaults - with user credentials (me@password) and without query
	*	@return string URL
	*/
	public function getUrlWithUserCredentialsAndWithoutQuery()
	{
		$scheme = $this->getScheme();
		$port = $this->getPort();
		$user = $this->getUser();
		$pass = $this->getPass();
		$noPass = $pass == '';
		return $scheme . ($scheme == '' ? '' : '://') . $user . ($noPass ? '' : ':' . $pass) . (!$noPass || $user == '' ? '' : '@') . $this->getHost() . ($port ? ':' . $port : '') . $this->getPath();
	}
	
	/**
	*	Alias of getUrl()
	*	@return string URL
	*	@uses getUrl()
	*	@see getUrl()
	*/
	public function __toString()
	{
		return $this->getUrl();
	}
	
	/**
	*	Alias of getUrl()
	*	@return string URL
	*	@uses getUrl()
	*	@see getUrl()
	*/
	public function __invoke()
	{
		return $this->getUrl();
	}
	
	/**
	*	Bulid URI with given data/defaults
	*	@return string URI
	*/
	public function getUri()
	{
		$query = $this->getQuery();
		return $this->getPath() . ($query == '' ? '' : '?' . $query);
	}
	
	/**
	*	Get scheme (ex. ftp, http, tcp or something else)
	*	@return string Scheme
	*/
	public function getScheme()
	{
		return $this->_data['scheme'] == '' ? $this->_defaults['scheme'] : $this->_data['scheme'];
	}
	
	/**
	*	Get transport layer name corresponding to scheme
	*	@return string Transport network layer, empty string on unknown scheme
	*/
	public function getTransport()
	{
		switch($this->getScheme())
		{
			case 'tcp':
			case 'ftp':
			case 'http':
				return 'tcp';
			case 'udp':
				return 'upd';
			case 'ssl':
			case 'ssh':
			case 'shfs':
			case 'sshfs':
			case 'scp':
			case 'sftp':
			case 'https':
				return 'ssl';
			case 'file':
				return 'file';
			case 'tls':
				return 'tls';
			case 'icmp':
				return 'icmp';
			default:
				return '';
		}
	}
	
	/**
	*	Get host name
	*	@return string Host name
	*/
	public function getHost()
	{
		return $this->_data['host'] == '' ? $this->_defaults['host'] : $this->_data['host'];
	}
	
	/**
	*	Get host name in IDN ASCII format
	*	@return string Host name
	*/
	public function getHostIdnAscii()
	{
		$r = ($this->_data['host'] == '' ? $this->_defaults['host'] : $this->_data['host']);
		
		if(function_exists('idn_to_ascii'))
			return idn_to_ascii($r);
		
		trigger_error('idn_to_ascii() function is not exists. Utf8 to IDNA converting doesnt works. Please install PECL INTL package (>= 1.0.2)', E_USER_WARNING);
		
		return $r;
	}
	
	/**
	*	Get URL port - can be 0 when is not set in url (parse() or setPort())
	*	Use getPortReal() when you need port to create a connection
	*	@return int Port
	*	@see getPortReal()
	*/
	public function getPort()
	{
		return $this->_data['port'] ? $this->_data['port'] : $this->_defaults['port'];
	}
	
	/**
	*	Get port for making connection - returning port from getPort()/setPort()/parse() when is set, uses getservbyname() otherwise
	*	@param string $protocol Protocol for getservbyname() - 'tcp' or 'udp'
	*	@param bool $throw True for throwing Url_Exception on error instead returning false
	*	@return int Network port
	*	@throws Url_Exception
	*/
	public function getPortReal($protocol = 'tcp', $throw = true)
	{
		if($t = $this->getPort())
			return $t;
		
		$t = getservbyname($this->getScheme(), $protocol);
		if($t === false)
		{
			if($throw)
				throw new Url_Exception('Unknown service in url scheme');
			else
				return false;
		}
		return $t;
	}
	
	/**
	*	Get path (without query)
	*	@return string Path
	*/
	public function getPath()
	{
		return $this->_data['path'] == '' ? $this->_defaults['path'] : $this->_data['path'];
	}
	
	/**
	*	Get query from URL (without '?' char)
	*	@return string Query
	*/
	public function getQuery()
	{
		return $this->_data['query'] == '' ? $this->_defaults['query'] : $this->_data['query'];
	}
	
	/**
	*	Get user name from URL
	*	@return string User name
	*/
	public function getUser()
	{
		return $this->_data['user'] == '' ? $this->_defaults['user'] : $this->_data['user'];
	}
	
	/**
	*	Get user password from URL
	*	@return string User password
	*/
	public function getPass()
	{
		return $this->_data['pass'] == '' ? $this->_defaults['pass'] : $this->_data['pass'];
	}
	
	/**
	*	Set URL scheme
	*	@param string $v URL scheme
	*	@return self
	*	@throws Url_Exception
	*/
	public function setScheme($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_data['scheme'] = $v;
		return $this;
	}
	
	/**
	*	Set host
	*	@param string $v URL host
	*	@return self
	*	@throws Url_Exception
	*/
	public function setHost($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_data['host'] = strtolower($v);
		return $this;
	}
	
	/**
	*	Set network port - non-standard port (ex. http://domain.com:81/)
	*	@param int|string|null $v Network port (null or null length string will be changed to the '0')
	*	@return self
	*	@throws Url_Exception
	*/
	public function setPort($v)
	{
		if($v === '' || $v === null)
			$v = 0;
		if(!is_numeric($v))
			throw new Url_Exception('Not numeric port');
		if((int)$v != $v)
			throw new Url_Exception('Int only');
		$v = (int)$v;
		if($v < 0 || $v > 65535)
			throw new Url_Exception('Port cant be negative or more than 65535 (2^16)');
		
		$this->_data['port'] = $v;
		return $this;
	}
	
	/**
	*	Set URL path
	*	@note empty string (null length string) will be replaced to the slash ('/')
	*	@link http://www.faqs.org/rfcs/rfc3986
	*	@param string $v URL path
	*	@param bool $encode True to encode every char with php function rawurlencode() but without '/' chars (RFC 3986)
	*	@return self
	*	@throws Url_Exception
	*/
	public function setPath($v, $encode = false)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		
		if($v == '')
		{
			$this->_data['path'] = '/';
			return $this;
		}
		
		if($encode)
		{
			if($v{0} == '/')
				$v = substr($v, 1);
			$v2 = '';
			foreach(explode('/', $v) as $part)
				$v2 .= '/' . rawurlencode($part);
			
			$this->_data['path'] = $v2;
			return $this;
		}
		
		$this->_data['path'] = $v{0} == '/' ? $v : '/' . $v;
		return $this;
	}
	
	/**
	*	Set URL query
	*	@param string|array $v URL query (array keys and values will be encoded by php function rawurlencode())
	*	@return self
	*	@throws Url_Exception
	*/
	public function setQuery($v)
	{
		if(is_string($v))
			$this->_data['query'] = $v;
		elseif(is_array($v))
		{
			$o = '';
			$added = false;
			foreach($v as $k => $el)
			{
				if($added)
					$o .= '&';
				$o .= rawurlencode($k) . '=' . rawurlencode($el);
				$added = true;
			}
			$this->_data['query'] = $o;
		}
		else
			throw new Url_Exception('String or array only');
		
		return $this;
	}
	
	/**
	*	Set URL user name
	*	@param string $v URL User name
	*	@return self
	*	@throws Url_Exception
	*/
	public function setUser($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_data['user'] = $v;
		return $this;
	}
	
	/**
	*	Set URL user password
	*	@param string $v URL User password
	*	@return self
	*	@throws Url_Exception
	*/
	public function setPass($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_data['pass'] = $v;
		return $this;
	}
	
	/**
	*	Set default scheme - used when scheme is not given by setScheme() or in parsed url via parse() or __construct()
	*	@param string $v URL scheme
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultScheme($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_defaults['scheme'] = $v;
		return $this;
	}
	
	/**
	*	Set default host - used when host is not given by setHost() or in parsed url via parse() or __construct()
	*	@param string $v URL host
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultHost($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_defaults['host'] = strtolower($v);
		return $this;
	}
	
	/**
	*	Set default port - used when port is not given by setPort() or in parsed url via parse() or __construct()
	*	@param int $v URL port
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultPort($v)
	{
		if(!is_numeric($v))
			throw new Url_Exception('Not numeric port');
		if((int)$v != $v)
			throw new Url_Exception('Int only');
		$v = (int)$v;
		if($v < 0 || $v > 65535)
			throw new Url_Exception('Port cant be negative or more than 65535 (2^16)');
		
		$this->_defaults['port'] = $v;
		return $this;
	}
	
	/**
	*	Set default path - used when path is not given by setPath() or in parsed url via parse() or __construct()
	*	@param string $v URL path
	*	@param bool $encode True to encode every char with php function rawurlencode() but without '/' chars (RFC 3986)
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultPath($v, $encode = false)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		
		if($v == '')
		{
			$this->_defaults['path'] = '/';
			return $this;
		}
		
		if($encode)
		{
			if($v{0} == '/')
				$v = substr($v, 1);
			$v2 = '';
			foreach(explode('/', $v) as $part)
				$v2 .= '/' . rawurlencode($part);
			
			$this->_defaults['path'] = $v2;
			return $this;
		}
		
		$this->_defaults['path'] = $v{0} == '/' ? $v : '/' . $v;
		return $this;
	}
	
	/**
	*	Set default query - used when query is not given by setQuery() or in parsed url via parse() or __construct()
	*	@param string|array $v URL query (array keys and values will be encoded by php function rawurlencode())
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultQuery($v)
	{
		if(is_string($v))
			$this->_defaults['query'] = $v;
		elseif(is_array($v))
		{
			$o = '';
			foreach($v as $k => $el)
			{
				if($o)
					$o .= '&';
				$o .= urlencode($k) . '=' . urlencode($el);
			}
			$this->_defaults['query'] = $o;
		}
		if(!is_string($v) && !is_array($v))
			throw new Url_Exception('string or array only');
		return $this;
	}
	
	/**
	*	Set default user name
	*	@param string $v User name
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultUser($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_defaults['user'] = $v;
		return $this;
	}
	
	/**
	*	Set default user password
	*	@param string $v User password
	*	@return self
	*	@throws Url_Exception
	*/
	public function setDefaultPass($v)
	{
		if(!is_string($v))
			throw new Url_Exception('String only');
		$this->_defaults['pass'] = $v;
		return $this;
	}
	
	/**
	*	Get default scheme (ex. ftp, http, tcp or something else)
	*	@return string Scheme
	*/
	public function getDefaultScheme()
	{
		return $this->_defaults['scheme'];
	}
	
	/**
	*	Get default host name
	*	@return string Host name
	*/
	public function getDefaultHost()
	{
		return $this->_defaults['host'];
	}
	
	/**
	*	Get default URL port.
	*	Use getPortReal() when you need port to create conection.
	*	@note Returned value can be 0 when is not set in url (parse() or setPort())
	*	@return int Default port
	*/
	public function getDefaultPort()
	{
		return $this->_defaults['port'];
	}
	
	/**
	*	Get default path (without query)
	*	@return string Default path
	*/
	public function getDefaultPath()
	{
		return $this->_defaults['path'];
	}
	
	/**
	*	Get default URL user name.
	*	@return string Default user name
	*/
	public function getDefaultUser()
	{
		return $this->_defaults['user'];
	}
	
	/**
	*	Get default user password
	*	@return string Default user password
	*/
	public function getDefaultPass()
	{
		return $this->_defaults['pass'];
	}
}