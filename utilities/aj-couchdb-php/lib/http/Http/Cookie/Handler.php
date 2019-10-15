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
*	Cookie handler to manipulate on cookies.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Cookie
*	@version 0.5
*/
class Http_Cookie_Handler implements Http_Cookie_Handler_Interface
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Stored cookies
	*	@var array
	*/
	protected $_cookies = [];
	
	/**
	*	Singleton instance
	*	@var Http_Cookie_Handler
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
	*	Http_Cookie_Handler constructor - restore cookies
	*	@return void
	*/
	protected function __construct()
	{
		$this->restoreCookies();
	}
	
	/**
	*	Get cookies corresponding for given request (scheme and path)
	*	@param Http_Request $request Request to find host/path/secure matches
	*	@return array Array with cookies for given request (key is a cookie name, value is a cookie object)
	*	@throws Http_Cookie_Handler_Exception
	*	@note Every cookie in returned array is cloned from stored here
	*/
	public function getCookiesForRequest(Http_Request $request)
	{
		$host = $request->getHost();
		$now = microtime(true);
		
		$o = [];
		foreach($this->_cookies as $storedDomain => $storedCookies)
		{
			if(!Http_Cookie_Parser::domainCheck($storedDomain, $host))
				continue;
			
			foreach($storedCookies as $k => $cookie)
			{
				$cookieMaxAge = $cookie->getProperty('max-age');
				
				if
				(
					($cookie->expires > 0 && $cookie->expires < $now)
					|| ($cookieMaxAge > 0 && $now - $cookieMaxAge > $cookie->getCreationTime())
				)
				{
					unset($this->_cookies[$storedDomain][$k]);
					if(!count($this->_cookies[$storedDomain]))
						unset($this->_cookies[$storedDomain]);
					$this->storeCookies(); // Cookie expired, so will be safer to save now new cookie storage (this is not a regular browser, but we want to act this way)
					continue;
				}
				
				if($cookie->secure && $request->getScheme() !== 'https')
					continue;
				
				if($cookie->path != '/')
				if(substr($request->getPath(), 0, strlen($cookie->path)) != $cookie->path) // Teoretically return of $request->getPath() should be cached, but cookie paths are used rarely, so this way should be faster in most cases
					continue;
				
				$n = $cookie->name;
				
				// Store in $o cookies with same names, later we will check which one is more important to select
				if(!isset($o[$n]))
					$o[$n] = [];
				$o[$n][] = $cookie;
			}
		}
		
		foreach($o as $name => $cookies)
		{
			if(count($cookies) == 1) // One cookie with this name, so replace one element array with this cookie
			{
				$o[$name] = $cookies[0];
				continue;
			}
			
			do
			{
				$this->_dropLessImportantCookie($cookies);
			}
			while((count($cookies) > 1));
			
			$o[$name] = clone $cookies[0];
		}
		
		return $o;
	}
	
	/**
	*	Some strange code, but I dont have idea to make this in another way
	*	@throws Http_Cookie_Handler_Exception
	*/
	protected function _dropLessImportantCookie(&$cookies)
	{
		$cookies = array_values($cookies); // Reindex array keys
		
		// In firts of all, check cookie priority, if not given, assume medium
		
		switch($cookies[0]->priority)
		{
			case 'low':
				$cookie_0_priority = 1;
			break;
			
			case 'high':
				$cookie_0_priority = 3;
			break;
			
			default: // 'medium' or something else
				$cookie_0_priority = 2;
		}
		
		switch($cookies[1]->priority)
		{
			case 'low':
				$cookie_1_priority = 1;
			break;
			
			case 'high':
				$cookie_1_priority = 3;
			break;
			
			default: // 'medium' or something else
				$cookie_1_priority = 2;
		}
		
		if($cookie_1_priority > $cookie_0_priority)
		{
			unset($cookies[0]);
			return;
		}
		if($cookie_0_priority > $cookie_1_priority)
		{
			unset($cookies[1]);
			return;
		}
		
		// Now we have same priority, so check if we have other cookie domain
		
		$domain_0 = $cookies[0]->domain;
		$domain_1 = $cookies[1]->domain;
		
		if($domain_0 != $domain_1)
		{
			if($domain_0{0} == '.')
			{
				unset($cookies[0]);
				return;
			}
			if($domain_1{0} == '.')
			{
				unset($cookies[1]);
				return;
			}
		}
		
		/*
		
		// Chose more secured cookie
		
		$secure_0 = $cookies[0]->secure;
		$secure_1 = $cookies[1]->secure;
		
		if($secure_0 != $secure_1)
		{
			if($secure_1)
			{
				unset($cookies[0]);
				return;
			}
			if($secure_0)
			{
				unset($cookies[1]);
				return;
			}
		}
		
		// Chose more appropriate path
		
		$path_0 = $cookies[0]->path;
		$path_1 = $cookies[1]->path;
		
		if($path_0 != $path_1)
		{
			if(strlen($path_0) < strlen($path_1))
			{
				unset($cookies[0]);
				return;
			}
			if(strlen($path_1) < strlen($path_0))
			{
				unset($cookies[1]);
				return;
			}
			trigger_error('Very strange...', E_USER_WARNING);
		}
		
		*/
		
		throw new Http_Cookie_Handler_Exception('Cannot happen... Stored two (or more) cookies with same name and domain'); // is this possible???
	}
	
	/**
	*	Delete cookie for given name and domain
	*	@param string|Http_Cookie $domain Cookie domain or cookie object (to get cookie domain and name from it)
	*	@param string|null $name Cookie name or null to delete all cookies with same domain (null required when given cookie object as first param to delete same cookie)
	*	@return self
	*	@see setCookie()
	*	@throws Http_Cookie_Handler_Exception
	*	@note Given object (in arg) will not be dropped anyway (btw in php its not possible!)
	*/
	public function deleteCookie($domain, $name = null)
	{
		if(is_object($domain))
		{
			if($name !== null)
				throw new Http_Cookie_Handler_Exception('Second arg must be always null when first is a cookie object (read inline docs)');
			if(!($domain instanceof Http_Cookie))
				throw new Http_Cookie_Handler_Exception('What is that???');
			
			$name = $domain->name;
			$domain = $domain->domain;
			
			if($name == '')
				throw new Http_Cookie_Handler_Exception('Given cookie doesnt have name, so what cookie needs to be deleted?');
		}
		
		foreach($this->_cookies as $storedDomain => $storedCookies)
		{
			if($storedDomain != $domain)
				continue;
			
			foreach($storedCookies as $k => $cookie)
			{
				if($name === null)
					unset($this->_cookies[$storedDomain][$k]);
				elseif($cookie->name === $name)
				{
					unset($this->_cookies[$storedDomain][$k]);
					return $this;
				}
			}
		}
		
		$this->storeCookies();
		
		return $this;
	}
	
	/**
	*	Set cookie - store, replace or delete if expired (if you want do delete cookie by hand, use deleteCookie())
	*	@param Http_Cookie $cookie Cookie object to store or replace
	*	@return self
	*	@see deleteCookie()
	*	@throws Http_Cookie_Handler_Exception
	*	@note Cookie object will be cloned
	*/
	public function setCookie(Http_Cookie $cookie)
	{
		$isExpired = $cookie->expires != -1 && $cookie->expires < time();
		
		$requiredDomain = $cookie->domain; // cache it (its a magic property from Http_Cookie::__get())
		$requiredName = $cookie->name;
		
		// we can already have cookie with same name and domain
		foreach($this->_cookies as $storedDomain => $storedCookies)
		{
			if($storedDomain != $requiredDomain)
				continue;
			
			foreach($storedCookies as $k => $storedCookie)
			{
				if($requiredName == $storedCookie->name)
				{
					if($isExpired)
					{
						unset($this->_cookies[$storedDomain][$k]);
						if(!count($this->_cookies[$storedDomain]))
							unset($this->_cookies[$storedDomain]);
					}
					else
						$this->_cookies[$storedDomain][$k] = clone $cookie;
					
					$this->storeCookies();
					return $this;
				}
			}
		}
		
		// Cookie with same domain and with same name doesn't exist, so just add it
		
		if($isExpired) // but not when cookie to add is already expired (happen sometimes)
			return $this;
		
		$domain = $cookie->domain;
		
		if(!isset($this->_cookies[$domain]))
			$this->_cookies[$domain] = [];
		
		$this->_cookies[$domain][] = clone $cookie;
		
		$this->storeCookies();
		
		return $this;
	}
	
	/**
	*	Set cookie from a header value
	*	@param string $str Cookie header value (without 'Set-Cookie: ')
	*	@param Http_Request|string $host Host name from request or request object (for a default cookie domain)
	*	@return self
	*	@uses Http_Cookie_Parser::parseSetCookieHeaderValue()
	*	@uses setCookie()
	*/
	public function setCookieFromHeaderValue($str, $host)
	{
		if(is_object($host))
			$host = $host->getHost();
		
		foreach(Http_Cookie_Parser::parseSetCookieHeaderValue($str, $host) as $cookie)
		{
			$domain = $cookie->domain; // Cache
			if($domain == '.')
				throw new Http_Cookie_Handler_Exception("Security: Host '$host' sent us cookie with domain '.' - ('supercookie' - cookie for every one host)");
			
			if($domain != '')
			if($domain != $host) // Allow to work from com to com (host name doesnt have dot as first char)
			{
				if(Domain_PublicSuffixList::isDomainInTldEffectiveList($domain)) // this methid cuts dot on first char
					throw new Http_Cookie_Handler_Exception("Security: Host '$host' sent us cookie with domain '$domain' - ('supercookie' - cookie for top domain level)");
			}
			
			$this->setCookie($cookie);
		}
		
		return $this;
	}

	/**
	*	Store all cookies in 'storage'
	*	@return self
	*/
	public function storeCookies()
	{
		Http_Storage::getInstance()->cookies = $this->_cookies;
		return $this;
	}
	
	/**
	*	Restore cookies from storage
	*	@return self
	*	@uses Http_Storage
	*/
	public function restoreCookies()
	{
		$this->_cookies = Http_Storage::getInstance()->cookies;
		if(!is_array($this->_cookies))
			$this->_cookies = [];
		return $this;
	}
	
	/**
	*	Drop session cookies that matches given cookie domain name (or from every domain when first arg is null)
	*	@param string|null $selectedDomain cookie domain to drop, or null to drop session cookies with any domain
	*	@return self
	*/
	public function dropSessionCookies($selectedDomain = null)
	{
		if($selectedDomain !== null && !is_string($selectedDomain))
			throw new Http_Cookie_Handler_Exception('String or null only');
			
		foreach($this->_cookies as $storedDomain => $storedCookies)
		{
			if($selectedDomain === null || $storedDomain == $selectedDomain)
			{
				foreach($storedCookies as $k => $cookie)
				{
					if($cookie->expires < 1 || $cookie->getProperty('max-age') < 1)
						unset($this->_cookies[$storedDomain][$k]);
				}
			}
		}
		
		$this->storeCookies();
		
		return $this;
	}
	
	/**
	*	Call storeCookies() in destructor
	*	@return void
	*	@uses storeCookies()
	*/
	public function __destruct()
	{
		if(Http_Settings::getInstance()->dropSessionCookiesOnExit) /// TODO: Make this as an option (setter, getter) in this class (enable/disable or use setting from Http_Settings).
			$this->dropSessionCookies();
		else // storeCookies() is called in dropSessionCookies(), so dont call storeCookies() twice
			$this->storeCookies();
	}
}