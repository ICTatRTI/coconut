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
*	@package Http Url
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/**
*	Object Url with rethrows from Url_Exception to the Http_Url_Exception and with http as default scheme (http as default scheme is available also in parsing: parse() and __construct())
*	@category Http Client
*	@package Http Url
*	@version 0.5
*/
class Http_Url extends Url
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Parse string with url (by parse_url())
	*	@note When given string doesn't have scheme, 'http://' will be added to the beginning
	*	@param string $s Url
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function parse($s)
	{
		try
		{
			if($s != '' && $s{0} != '/' && $s{0} != '.')
			if($s{0} != 'h' && substr($s, 0, 7) != 'http://' && substr($s, 0, 8) != 'https://')
				$s = 'http://' . $s;
			
			parent::parse($s);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Get port for making connection - returning port from getPort()/setPort()/parse() when is set, using getservbyname() otherwise
	*	@param string $protocol Protocol for getservbyname() - 'tcp' or 'udp'
	*	@param bool $throw True for throwing Resolver_Exception on error instead returning false
	*	@return int port
	*	@throws Http_Url_Exception
	*/
	public function getPortReal($protocol = 'tcp', $throw = true)
	{
		try
		{
			return parent::getPortReal($protocol, $throw);
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set URL scheme
	*	@param string $v URL scheme
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setScheme($v)
	{
		try
		{
			parent::setScheme($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set host
	*	@param string $v URL host
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setHost($v)
	{
		try
		{
			parent::setHost($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set network port - non-standard port (ex. http://domain.com:81/)
	*	@param int|string|null $v Network port (null or null length string will be changed to the '0')
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setPort($v)
	{
		try
		{
			parent::setPort($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set URL path
	*	@param string $v URL path
	*	@param bool $encode True to encode every char with php function rawurlencode() but without '/' chars (RFC 3986)
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setPath($v, $encode = false)
	{
		try
		{
			parent::setPath($v, $encode);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set URL query
	*	@param string|array $v URL query (array keys and values will be encoded by php function rawurlencode())
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setQuery($v)
	{
		try
		{
			parent::setQuery($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default scheme - used when scheme is not given by setScheme() or in parsed url via parse() or __construct()
	*	@param string $v URL scheme
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultScheme($v)
	{
		try
		{
			parent::setDefaultScheme($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default host - used when host is not given by setHost() or in parsed url via parse() or __construct()
	*	@param string $v URL host
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultHost($v)
	{
		try
		{
			parent::setDefaultHost($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default port - used when port is not given by setPort() or in parsed url via parse() or __construct()
	*	@param int $v URL port
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultPort($v)
	{
		try
		{
			parent::setDefaultPort($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default path - used when path is not given by setPath() or in parsed url via parse() or __construct()
	*	@param string $v URL path
	*	@param bool $encode True to encode every char with php function rawurlencode() but without '/' chars (RFC 3986)
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultPath($v, $encode = false)
	{
		try
		{
			parent::setDefaultPath($v, $encode);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default query - used when query is not given by setQuery() or in parsed url via parse() or __construct()
	*	@param string|array $v URL query (array keys and values will be encoded by php function rawurlencode())
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultQuery($v)
	{
		try
		{
			parent::setDefaultQuery($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default user name
	*	@param string $v user name
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultUser($v)
	{
		try
		{
			parent::setDefaultUser($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
	
	/**
	*	Set default user password
	*	@param string $v User password
	*	@return self
	*	@throws Http_Url_Exception
	*/
	public function setDefaultPass($v)
	{
		try
		{
			parent::setDefaultPass($v);
			return $this;
		}
		catch(Url_Exception $e)
		{
			throw new Http_Url_Exception($e->getMessage());
		}
	}
}