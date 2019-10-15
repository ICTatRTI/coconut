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
*	Cookie handler to manipulate on cookies - interface.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Cookie
*	@version 0.5
*/
interface Http_Cookie_Handler_Interface
{
	/**
	*	Get singleton instance
	*	@return self
	*/
	public static function getInstance();
	
	/**
	*	Get cookies corresponding for given request (scheme and path)
	*	@param Http_Request $request Request to find host/path/secure matches
	*	@return array Array with cookies for given request (key is a cookie name, value is a cookie object)
	*	@note Every cookie in returned array is cloned from stored here
	*/
	public function getCookiesForRequest(Http_Request $request);
	
	/**
	*	Delete cookie for given name and domain
	*	@param string|Http_Cookie $domain Cookie domain or cookie object (to get cookie domain and name from it)
	*	@param string|null $name Cookie name or null to delete all cookies with same domain (null required when given cookie object as first param to delete same cookie)
	*	@return self
	*	@see setCookie()
	*	@note Given object (in arg) will not be dropped anyway (btw in php its not possible!)
	*/
	public function deleteCookie($domain, $name = null);
	
	/**
	*	Set cookie - store, replace or delete if expired (if you want do delete cookie by hand, use deleteCookie())
	*	@param Http_Cookie $cookie Cookie object to store or replace
	*	@return self
	*	@see deleteCookie()
	*	@note Cookie object will be cloned internally
	*/
	public function setCookie(Http_Cookie $cookie);
	
	/**
	*	Set cookie from a header value
	*	@param string $str Cookie header value (without 'Set-Cookie: ')
	*	@param Http_Request|string $host Host name from request or request object (for a default cookie domain)
	*	@return self
	*/
	public function setCookieFromHeaderValue($str, $host);
	
	/**
	*	Drop session cookies that matches given cookie domain name (or from every domain when first arg is null)
	*	@param string|null $selectedDomain Cookie domain to drop, or null to drop session cookies with any domain
	*	@return self
	*/
	public function dropSessionCookies($selectedDomain = null);
}