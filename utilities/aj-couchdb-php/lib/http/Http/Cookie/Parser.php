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
*	Http cookie parser - parsing cookie strings.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Cookie
*	@version 0.5
*/
class Http_Cookie_Parser
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	// Commented out from v0.4 because is not needed anymore (was used in old parseCookieExpires() - also still exist commented out)
	// const TIME_ZONE_DEFAULT = 2; // GMT +2
	
	/**
	*	Parse Set-Cookie response header from server
	*	@param string $header Set-Cookie header value (value only)
	*	@param string $domainDefault Default domain - host name from http request, will be used as a cookie domain when domain in header is not given
	*	@return array New Http_Cookie objects based on data parsed from given string (see included link in this inline doc)
	*	@link http://stackoverflow.com/questions/11533867/set-cookie-header-with-multiple-cookies
	*/
	public static function parseSetCookieHeaderValue($header, $domainDefault)
	{
		if(!is_string($header) || !is_string($domainDefault))
			throw new Http_Cookie_Parser_Exception('String only');
		
		if(!$header || !$domainDefault)
			throw new Http_Cookie_Parser_Exception('Empty string');
		
		$CookieSizeLimit = Http_Settings::getInstance()->cookieSizeLimit;
			
		if($CookieSizeLimit && $CookieSizeLimit < 4096) // 0 disables limit
			trigger_error('cookieSizeLimit in Http_Settings is less than 4096 bytes, this is not compatible with RFC 2109', E_USER_WARNING);
		
		$cookiesInSetCookie = explode(';,', $header); /// TODO: Make this FULLY RFC 2109 compatible
		$o = [];
		foreach($cookiesInSetCookie as $oneCookieInSetCookie)
		{
			$size = strlen($oneCookieInSetCookie);
			
			if(!$size)
				throw new Http_Cookie_Parser_Exception('Empty cookie string'); // ,,
			
			if($CookieSizeLimit && $size > $CookieSizeLimit) // 0 disables limit
				throw new Http_Cookie_Parser_Exception('Cookie size exceed ' . $limit . ' bytes limit (RFC 2109)');
			
			$t = explode('=', $oneCookieInSetCookie, 2);
			
			if(2 !== count($t))
				throw new Http_Cookie_Parser_Exception('Missing \'=\' after cookie name in Set-Cookie header');
			
			$cookie = new Http_Cookie;
			$cookie->setSize($size);
			$cookie->name = $t[0];
			$t = explode(';', $t[1]);
			$cookie->value = array_shift($t);
			
			$domain = '';
			
			if(is_array($t) && $t) // ; char is not required
			foreach($t as $v)
			{
				$v = trim($v);
				
				if($v == '')
					continue; // How much ';' can be?
				
				$t2 = count($t = explode('=', $v));
				
				if($t2 !== 2 && $t2 !== 1)
					throw new Http_Cookie_Parser_Exception('Bad cookie header');
				
				$param = strtolower(trim($t[0]));
				$value = isset($t[1]) ? trim($t[1]) : true;
				
				$cookie->$param = $param == 'expires' ? static::parseCookieExpires($value) : $value;
				
				if($param == 'domain' && isset($t[1]))
					$domain = $value;
			}
			
			$cookie->domain = $domain == '' ? $domainDefault : $domain;
			
			$o[] = $cookie;
		}
		
		return $o;
	}
	
	/**
	*	Check if requested host is matched in cookie domain
	*	@param string $domain Domain from cookie
	*	@param string $host Host from http request
	*	@return bool True when cookie domain is correct for given host, false otherwise
	*	@throws Cookie_Parser_Exception
	*/
	public static function domainCheck($domain, $host)
	{
		if(!is_string($domain) || !is_string($host))
			throw new Cookie_Parser_Exception('Both args must be string only');
		
		if($domain != '')
		{
			if($domain{0} == '.')
			{
				if($domain == '.') // Dont allow cookie to work in every domain
				{
					trigger_error('\'.\' cookie domain - not allowed to work', E_USER_WARNING);
					return false;
				}
				if(substr($domain, 1) == $host) // .aaa.pl -> aaa.pl = aaa.pl
					return true;
				$domain_len = strlen($domain);
				if(strlen($host) > $domain_len)
				if(substr($host, -$domain_len) == $domain) // bbb.aaa.pl -> .aaa.pl
					return true;
			}
			elseif($domain == $host)
				return true;
		}
		else
			trigger_error('Null length cookie domain', E_USER_WARNING); // Cookie without domain in sv response should always have set requested host as domain
		return false;
	}
	
	/**
	*	Parse cookie expires from string to the unix time
	*	@param string $value Input value from a Set-Cookie header (without `Set-Cookie: ` and without `name=value;`)
	*	@param int|null $timeZone GMT time zone (2 means +2 and -2 means -2), null to use default from self::TIME_ZONE_DEFAULT
	*	@return int Unix-time of cookie expire time
	*/
	public static function parseCookieExpires($value)
	{
		if(!is_string($value))
			throw new Http_Cookie_Parser_Exception('String only');
		
		if($value == '')
			throw new Http_Cookie_Parser_Exception('Empty string given...');
		
		return Date::parseRfc822Date($value);
	}
	
	// Rewrited in v0.5 to use Date::parseRfc822Date()
	/*
	*	Parse cookie expires from string to the unix time
	*	@param string $value Input value from a Set-Cookie header (without `Set-Cookie: ` and without `name=value;`)
	*	@param int|null $timeZone GMT time zone (2 means +2 and -2 means -2), null to use default from self::TIME_ZONE_DEFAULT
	*	@return int Unix-time of cookie expire time
	*/
	/*public static function parseCookieExpires($value, $timeZone = null)
	{
		if(!is_string($value))
			throw new Http_Cookie_Parser_Exception('string only');
		if($timeZone !== null && !is_int($timeZone))
			throw new Http_Cookie_Parser_Exception('time zone in int/null only');
		if(substr($value, -4) != ' GMT')
			throw new Http_Cookie_Parser_Exception('GMT handling only by now');
		
		$value = substr($value, 5, -4);
		$value = str_replace(array('-', ':'), ' ', $value);
		
		if(count(explode(' ', $value)) != 6)
			throw new Http_Cookie_Parser_Exception('Expires string looks bad');
		
		if(sscanf($value, '%02d %s %02d %02d %02d %02d', $day, $month, $year, $hour, $minute, $second) !== 6)
			throw new Http_Cookie_Parser_Exception('sscanf failed');
		
		$month = Date::monthToNum($month);
		
		$time = mktime($hour, $minute, $second, $month, $day, $year);
		
		$time += ($timeZone === null ? static::TIME_ZONE_DEFAULT : $timeZone) * 3600;
		
		return $time;
	}*/
}