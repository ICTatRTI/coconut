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
*	Http request - cookies proxy (interface).
*	Here are the strongly reqired methods only.
*	See included howto.
*	@category Http Client
*	@package Http Cookie
*	@version 0.5
*/
interface Http_Cookie_Proxy_Interface
{
	/**
	*	Get cookies for given request.
	*	Cookies will be given from cookie handler (see setCookieHandler()), then will be from here (temporary cookie with same name will replace it).
	*	@param Http_Request $request Http request to get host and other data to get necessary cookies from handler
	*	@return array Merged cookies (by array_merge(cookieHandler, tempCookies))
	*/
	public function getCookiesForRequest(Http_Request $request);
	
	/**
	*	Forward setCookieFromHeaderValue() into cookie handler (Http_Cookie_Handler)
	*	@param string $headerValue Value from 'Set-Cookie' header
	*	@param string|Http_Request $host Request host name (for default cookie doamin) or Http_Request object to get it from request object
	*	@return self
	*/
	public function setCookieFromHeaderValue($headerValue, $host);
	
	/**
	*	Set temporary cookie
	*	@param Http_Cookie $cookie Cookie to temporary use
	*	@return self
	*/
	public function setTempCookie(Http_Cookie $cookie);
	
	/**
	*	Get internall setting 'settingSendCookies'
	*	@return bool
	*/
	public function getSettingSendCookies();
	
	/**
	*	Temporary disable cookie from cookie handler (but dont delete it) - just in requests using this cookie proxy.
	*	If temporary cookie exists here (in a cookie proxy) with same name, it will be dropped - if You need to temporary cookie replace (other value or something) use setTempCookie().
	*	@param string|Http_Cookie $name Cookie name or cookie object to get name from it (cookie wih this name will be temporary disabled)
	*	@return self
	*/
	public function disableCookie($name);
	
	/**
	*	Forward setCookie() into cookie handler (Http_Cookie_Handler)
	*	@note It will do nothing when settingReceiveCookies is set to false
	*	@param Http_Cookie $cookie Cookie object
	*	@return self
	*/
	public function setCookie(Http_Cookie $cookie);
}