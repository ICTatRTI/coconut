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
*	Http request - cookies proxy (implementation of proxy design pattern between cookie handler object and http request object).
*	See included howto.
*	@category Http Client
*	@package Http Cookie
*	@version 0.5
*/
class Http_Cookie_Proxy implements Http_Cookie_Proxy_Interface
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Data in array
	*	@var array
	*/
	protected $_data = 
	[
		'settingSendCookies' => true, // bool - send matching stored cookies from cookie handler
		'settingReceiveCookies' => true, // bool - receive and store cookies in cookie handler
		'cookieHandler' => null, // Http_Cookie_Handler_Interface - cookie handler object
		'tempCookies' => [] // Cookies only for one or more requests - key is a cookie name, value is a cookie object or false for prevent sending
	];
	
	/**
	*	Constructor with optonally setting of cookie handler object
	*	@param Http_Cookie_Handler_Interface|null $cookiesHandler Cookie handler object or null to use default Http_Cookie_Handler (singleton)
	*	@return void
	*/
	public function __construct(Http_Cookie_Handler_Interface $cookiesHandler = null)
	{
		$this->_data['cookieHandler'] = $cookiesHandler ? $cookiesHandler : Http_Cookie_Handler::getInstance();
		
		$settings = Http_Settings::getInstance();
		$this->_data['settingSendCookies'] =  $settings->sendCookiesByDefault;
		$this->_data['settingReceiveCookies'] =  $settings->receiveCookiesByDefault;
	}
	
	/**
	*	Change cookie handler object
	*	@param Http_Cookie_Handler_Interface|null $cookiesHandler Cookie handler to use in request(s), null to set back to default Http_Cookie_Handler
	*	@return self
	*/
	public function setCookieHandler(Http_Cookie_Handler_Interface $cookiesHandler)
	{
		$this->_data['cookieHandler'] = $cookiesHandler ? $cookiesHandler : Http_Cookie_Handler::getInstace();
		return $this;
	}
	
	/**
	*	Get cookie handler object
	*	@return Http_Cookie_Handler_Interface Cookie handler
	*/
	public function getCookieHandler()
	{
		return $this->_data['cookieHandler'];
	}
	
	/**
	*	Change internall setting 'settingSendCookies'.
	*	Default is set in constructor basing on Http_Settings::$sendCookiesByDefault
	*	@param bool $settingSendCookies True to send cookies, false to not send
	*	@return self
	*/
	public function setSettingSendCookies($settingSendCookies)
	{
		$this->_data['settingSendCookies'] = $settingSendCookies;
		return $this;
	}
	
	/**
	*	Get internall setting 'settingSendCookies'
	*	@return bool
	*	@see setSettingSendCookies()
	*/
	public function getSettingSendCookies()
	{
		return (bool)$this->_data['settingSendCookies'];
	}
	
	/**
	*	Change internall setting 'settingReceiveCookies'.
	*	Default is set in constructor basing on Http_Settings::$receiveCookiesByDefault.
	*	@param bool $settingReceiveCookies True to receive, parse and store cookies (by Http_Cookie_Handler), false to do not
	*	@return self
	*/
	public function setSettingReceiveCookies($settingReceiveCookies)
	{
		$this->_data['settingReceiveCookies'] = $settingReceiveCookies;
		return $this;
	}
	
	/**
	*	Change internall setting 'settingReceiveCookies'.
	*	@param $settingReceiveCookies bool
	*	@return bool
	*	@see setSettingReceiveCookies()
	*/
	public function getSettingReceiveCookies($settingReceiveCookies)
	{
		return (bool)$this->_data['settingReceiveCookies'];
	}
	
	/**
	*	Get cookies for given request.
	*	Cookies will be given from cookie handler (see setCookieHandler()), then will be from here (temporary cookie with same name will replace it).
	*	@param Http_Request $request Http request to get host and other data to get necessary cookies from handler
	*	@throws Http_Cookie_Proxy_Exception
	*	@return array Merged cookies (by array_merge(cookieHandler, tempCookies))
	*/
	public function getCookiesForRequest(Http_Request $request)
	{
		if(!$request)
			throw new Http_Cookie_Proxy_Exception('Null given instead object of Http_Request');
		
		$o = $this->_data['settingSendCookies']
			?
				array_merge
				(
					$this->_data['cookieHandler']->getCookiesForRequest($request),
					$this->_data['tempCookies']
				)
			:
				$this->_data['tempCookies'];
			
		foreach($o as $cookieName => $cookie)
			if($cookie === false) // See disableCookie() - false means disabled
				unset($o[$cookieName]);
			else
				$o[$cookieName] = clone $o[$cookieName]; // Prevent outside modifications of this cookie object
		
		return $o;
	}
	
	/**
	*	Set temporary cookie - will be used in request(s) using this object only (by Http_Request::setCookieProxy() or in constructor)
	*	@note Cookie object will be cloned
	*	@param Http_Cookie $cookie Cookie to temporary use
	*	@return self
	*	@throws Http_Cookie_Proxy_Exception
	*	@see disableCookie()
	*/
	public function setTempCookie(Http_Cookie $cookie)
	{
		if(!$cookie)
			throw new Http_Cookie_Proxy_Exception('Null given instead object of Http_Cookie');
		
		if($cookie->name == '')
			throw new Http_Cookie_Proxy_Exception('Given cookie must have name');
		
		$this->_data['tempCookies'][$cookie->name] = clone $cookie; // Prevent outside modifications of this cookie object
		
		return $this;
	}
	
	/**
	*	Temporary disable cookie from cookie handler (but dont delete it) - just in requests using this cookie proxy.
	*	If temporary cookie exists here (in a cookie proxy) with same name, it will be dropped - if You need to temporary cookie replace (other value or something) use setTempCookie().
	*	@param string|Http_Cookie $name Cookie name or cookie object to get name from it (cookie wih this name will be temporary disabled)
	*	@return self
	*/
	public function disableCookie($name)
	{
		if(is_object($name))
			$name = $name->name;
		$this->_data['tempCookies'][$name] = false;
		return $this;
	}
	
	/**
	*	Forward setCookie() into cookie handler (Http_Cookie_Handler)
	*	@note It will do nothing when settingReceiveCookies is set to false
	*	@param Http_Cookie $cookie Cookie object
	*	@return self
	*	@throws Http_Cookie_Proxy_Exception
	*	@see setSettingReceiveCookies()
	*	@see getSettingReceiveCookies()
	*	@see Http_Settings
	*/
	public function setCookie(Http_Cookie $cookie)
	{
		if(!$cookie)
			throw new Http_Cookie_Proxy_Exception('Null given instead object of Http_Cookie');
		
		if($this->_data['settingReceiveCookies'])
			$this->_data['cookieHandler']->setCookie($cookie);
		
		return $this;
	}
	
	/**
	*	Forward setCookieFromHeaderValue() into cookie handler (Http_Cookie_Handler)
	*	@note It will do nothing when settingReceiveCookies is set to false
	*	@param string $headerValue Value from 'Set-Cookie' header
	*	@param string|Http_Request $host Request host name (for default cookie doamin) or Http_Request object to get it from request object
	*	@return self
	*/
	public function setCookieFromHeaderValue($headerValue, $host)
	{
		$headerValue = trim($headerValue);
		
		if($headerValue == '')
		{
			trigger_error('Null length \'Set-Cookie\' header value', E_USER_WARNING);
			return $this;
		}
		
		if($this->_data['settingReceiveCookies'])
		{
			if(is_object($host))
				$host = $host->getHost();
			$this->_data['cookieHandler']->setCookieFromHeaderValue($headerValue, $host);
		}
		
		return $this;
	}
}