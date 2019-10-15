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
*	@version 0.5.1
*/

/**
*	DNS resolver for Http Client.
*	Resolver_Exception are rethrowed as Http_Resolver_Exception (extended from Http_Exception).
*	Adapter for gethostbynamel().
*	<code>
*	$resolver = Resolver::getInstance();
*	$resolver->get('domain.com');
*	echo $resolver->getAll('domain.com');
*	</code>
*	@category Http Client
*	@package Http Client
*	@version 0.5.1
*/
class Http_Resolver_Base extends Resolver
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Singleton instance
	*	@var Resolver
	*/
	protected static $_instance;
	
	/**
	*	Get singleton instance
	*	@return self
	*/
	public static function getInstance()
	{
		if(!isset(static::$_instance))
			static::$_instance = new static;
		return static::$_instance;
	}
	
	/**
	*	Resolve first ip adress for host
	*	@param string $name Host name
	*	@param bool $throw True for throwing Http_Resolver_Exception on error instead returning false
	*	@return string Host adress (first adress given from gethostbynamel())
	*/
	public static function get($name, $throw = true)
	{
		try
		{
			return parent::get($name, $throw);
		}
		catch(Resolver_Exception $e)
		{
			throw new Http_Resolver_Exception($e->getMessage()); // rethrow it on another exception (extended from Http_Exception)
		}
	}
	
	/**
	*	Get all adresses coresponding to a given domain
	*	@param string $name Host name
	*	@param bool $throw True for throwing Http_Resolver_Exception on error instead returning false
	*	@return array Array with ip list resolved with gethostbynamel()
	*/
	public static function getAll($name, $throw = true)
	{
		try
		{
			return parent::getAll($name, $throw);
		}
		catch(Resolver_Exception $e)
		{
			throw new Http_Resolver_Exception($e->getMessage()); // rethrow it on another exception (extended from Http_Exception)
		}
	}
}