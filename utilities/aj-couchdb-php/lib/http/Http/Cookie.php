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
*	@package Http Cookie
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/**
*	Object http cookie.
*	Usage:
*	<code>
*	$cookie = new Cookie;
*	$cookie->name = 'make me some name';
*	$cookie->value = 'very important cookie value';
*	echo 'Name: ' . $cookie->name . ', value: ' . $cookie->value . "\n";
*	</code>
*	Accepted cookie properties are: name, value, domain, path, expires, secure, httponly, max-age and priority.
*	@note Property names are always converted to lowercase by strtolower() in __set() and in setters called by __call().
*	@note Set or get unknown property (ex. 'alue' instead 'value') will trigger php error (trigger_error()) with E_USER_WARNING, getters will return null in this case (but property will be saved).
*	@link http://www.ietf.org/rfc/rfc2109.txt
*	See included howto.
*	@category Http Client
*	@package Http Cookie
*	@version 0.5
*/
class Http_Cookie
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Cookie data in array
	*	@var array
	*/
	protected $_data =
	[
		'name' => '',
		'value' => '',
		'domain' => '',
		'path' => '/',
		'expires' => -1,
		'secure' => false,
		'httponly' => false,
		'max-age' => -1,
		'comment' => '',
		'version' => '',
		'priority' => 'medium'
	];
	
	/**
	*	Cookie object creation time in seconds
	*	@var float
	*/
	protected $_creationTime = 0.0;
	
	/**
	*	Cookie size described in RFC 2019
	*	@var int
	*/
	protected $_size = 0;
	
	/**
	*	Set cookie property (name, value, domain, path, expires, secure, httponly).
	*	Property name is always converted to lowercase with strtolower().
	*	@param string $k Property name
	*	@param string|int|bool $v Property value
	*	@return void
	*/
	public function __set($k, $v)
	{
		$k = strtolower($k);
		if(!isset($this->_data[$k]))
			trigger_error('Set unknown cookie property: ' . $k . ' with value: ' . $v, E_USER_WARNING);
		$this->_data[$k] = $v;
	}
	
	/**
	*	Get cookie property value.
	*	@note Property name is converted to lowercase with strtolower() (in setters).
	*	@param string $k Property value
	*	@return mixed
	*/
	public function __get($k)
	{
		$k = strtolower($k);
		
		if(!isset($this->_data[$k]))
		{
			trigger_error('Get unknown cookie property: ' . $k, E_USER_WARNING);
			return null;
		}
		
		return $this->_data[$k];
	}
	
	/**
	*	Check if property value is set.
	*	Property name is converted to downcase with strtolower().
	*	@param string $k Property name
	*	@return bool True when is set, false otherwise
	*/
	public function __isset($k)
	{
		$k = strtolower($k);
		return isset($this->_data[$k]) && $this->_data[$k];
	}

	/**
	*	Unset property value - not allowed
	*	@param string $k Property name
	*	@access protected
	*	@return void
	*/
	public function __unset($k)
	{
		throw new Http_Cookie_Exception('Unset property is not allowed');
	}
	
	/**
	*	Constructor with optionally set of cookie name, value, domain and path.
	*	Other properties must be set manually (setters).
	*	@param string $name Cookie name
	*	@param string $value Cookie value
	*	@param string $domain Cookie domain
	*	@param string $path Cookie path
	*	@return void
	*/
	public function __construct($name = null, $value = null, $domain = null, $path = null)
	{
		$this->_creationTime = microtime(true);
		
		if($name !== null)
			$this->name = $name;
		
		if($value !== null)
			$this->value = $value;
		
		if($domain !== null)
			$this->domain = $domain;
		
		if($path !== null)
			$this->path = $path;
	}
	
	/**
	*	Get unix time when this object was created
	*	@return float
	*/
	public function getCreationTime()
	{
		return $this->_creationTime;
	}
	
	/**
	*	Setters/Getters - all in one (setName(), setName(), setValue(), getValue(), setPath() etc.)
	*	@param string $method Method name
	*	@param array $args Method arguments
	*	@return mixed
	*/
	public function __call($method, $args)
	{
		$t = substr($method, 0, 3);
		$property = strtolower(substr($method, 3));
		
		switch($t)
		{
			case 'set':
				if(count($args) != 1)
					throw new Http_Cookie_Exception('Setter gets only one argument (value)');
				
				$value = $args[0];
				
				if(!isset($this->_data[$property]))
					trigger_error('Set unknown cookie param: ' . $property . ' with value: ' . $value, E_USER_WARNING);
				
				$this->_data[$property] = $value;
			break;
			
			case 'get':
				if(count($args) != 0)
					throw new Http_Cookie_Exception('Getter gets no any arguments');
				
				if(!isset($this->_data[$property]))
				{
					trigger_error('Get unknown cookie param: ' . $property, E_USER_WARNING);
					return null;
				}
				
				return $this->_data[$property];
			break;
			
			default:
				throw new Http_Cookie_Exception('__call(): Unknown method \'' . $method . '\'. Use: set[property name] or get[property name]');
		}
	}
	
	/**
	*	Get property value of given name
	*	@param string $property Cookie property name
	*	@return mixed
	*/
	public function getProperty($property)
	{
		if(!isset($this->_data[$property]))
		{
			trigger_error('Get unknown cookie param: ' . $property . ' with value: ' . $value, E_USER_WARNING);
			return;
		}
		return $this->_data[$property];
	}
	
	/**
	*	Set cookie size (RFC 2109).
	*	Because we can't calculate size here (perfomance reasons mostly), calculation is (should be) made in 'Set-Cookie' parsing (Http_Cookie_Parser).
	*	@param int $size size in bytes
	*	@return self
	*	@throws Http_Cookie_Exception When given something else than int
	*/
	public function setSize($size)
	{
		if(!is_int($size))
			throw new Http_Cookie_Exception('Int only');
		
		$this->_size = $size;
		return $this;
	}
	
	/**
	*	Get cookie size (RFC 2109)
	*	@return int cookie size in bytes
	*/
	public function getSize()
	{
		return $this->_size;
	}
}