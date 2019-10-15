<?php
/**
*	PHP Domain library
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
*	@package Domain library
*	@subpackage Resolver
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5.1
*/

/**
*	DNS resolver.
*	Adapter for gethostbynamel().
*	<code>
*	$resolver = Resolver::getInstance();
*	echo $resolver->get('domain.com');
*	print_r($resolver->getAll('domain.com'));
*	</code>
*	@category Lib
*	@package Domain library
*	@subpackage Resolver
*	@version 0.5.1
*/
class Resolver
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Max age of cache in seconds
	*	@var int
	*/
	const CACHE_MAX_AGE = 3600; // 1h = 3600s
	
	/**
	*	Resolver cache
	*	<code>
	*	[
	*		'domain.com' => ['12.34.56.78', '87.65.43.21'],
	*		'other.domain' => ...
	*	]
	*	</code>
	*	@var array
	*/
	protected static $_cache = [];

	/**
	*	Singleton instance
	*	@var Resolver
	*/
	protected static $_instance;
	
	/**
	*	Get singleton instance
	*	@return Resolver
	*/
	public static function getInstance()
	{
		if(!isset(self::$_instance))
			self::$_instance = new static;
		return self::$_instance;
	}
	
	/**
	*	Constructor
	*	@return void
	*/
	protected function __construct()
	{
		if(!function_exists('idn_to_ascii'))
			trigger_error('idn_to_ascii() function is not exists. Utf8 to IDNA converting doesnt works. Please install PECL INTL package (>= 1.0.2)', E_USER_WARNING);
	}
	
	/**
	*	Cached "gethostbynamel - Get a list of IPv4 addresses corresponding to a given Internet host name"
	*	Use getAll() or get() instead
	*	@param string $name Host name
	*	@return array Array with ip list resolved with gethostbynamel(), empty array will be returned when cant resolve host name
	*/
	public static function resolve($name)
	{
		if(function_exists('idn_to_ascii'))
			$name = idn_to_ascii($name);
		//else
		//	trigger_error('idn_to_ascii() function is not exists. Utf8 to IDNA converting doesnt works. Please install PECL INTL package (>= 1.0.2)', E_USER_WARNING);
		
		if(isset(self::$_cache[$name]))
		{
			if(self::$_cache[$name][1] > time() - self::CACHE_MAX_AGE)
				return self::$_cache[$name][0];
			unset(self::$_cache[$name]);
		}
		
		if(!($t = gethostbynamel($name)))
			return [];
		
		self::$_cache[$name] = [$t, time()];
		
		return $t;
	}
	
	/**
	*	Resolve first ip adress for host
	*	@param string $name Host name
	*	@param bool $throw True for throwing Resolver_Exception on error instead returning false
	*	@return string Host adress (first adress given from gethostbynamel())
	*/
	public static function get($name, $throw = true)
	{
		if($t = self::resolve($name))
			return $t[0];
		
		if($throw)
			throw new Resolver_Exception('Failed to resolve host address for ' . $name);
		else
			return false;
	}
	
	/**
	*	Get all adresses coresponding to a given domain
	*	@param string $name Host name
	*	@param bool $throw True for throwing Resolver_Exception on error instead returning false
	*	@return array Array with ip list resolved with gethostbynamel()
	*/
	public static function getAll($name, $throw = true)
	{
		if($t = self::resolve($name))
			return $t;
		
		if($throw)
			throw new Resolver_Exception('Failed to resolve host address for ' . $name);
		else
			return false;
	}
}