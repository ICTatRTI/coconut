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
*	@package Http Client settings
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/**
*	Http client settings based on Settings_Abstract.
*	See included howto.
*	Defaults and recommendations:
*		dirStorage:						http/client
*		storageTimeOut:						0
*		httpCacheEnabled:					true
*		httpCacheDefaultMaxAge:					3
*		httpCacheStorageSizeLimit:				41943040 (40MB)
*		httpCacheVarSizeLimit:					163840 (160KB)
*		httpCacheVarSumSizeLimit:				3145728 (3MB)
*		httpCacheUnallowedHosts: 				[]
*		httpCacheUnallowedUrls: 				[]
*		dbgRequestHeaders:					false
*		dbgResponseHeaders:					false
*		maxRedirects:						15
*		sendRefererOnRedirects:					true
*		keepAliveByDefault:					true
*		keepAliveTimeOutOffset:					0.5
*		keepAliveGetResponseOnlyOnDemand:			true
*		keepAliveDefaultTimeOut:				3
*		keepAliveDefaultTimeOut_useEvenWhenSocketWasReaded:	false
*		keepAliveDefaultMax:					8
*		keepAliveDefaultMax_useEvenWhenSocketWasReaded:		false
*		useDefaultHeaders:					true
*		receiveCookiesByDefault:				true
*		sendCookiesByDefault:					true
*		cookieSizeLimit:					16384
*		dropSessionCookiesOnExit:				true
*		historyFileInTxtMicroTime:				'http/client/log_microtime.txt'
*		historyFileInTxtTimeInRFC1036:				'http/client/log.txt'
*		historyFileSerialized:					''
*
*	@category Http Client
*	@package Http Client settings
*	@version 0.5
*/
class Http_Settings_Base extends Settings_Abstract
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Default settings
	*	@var array
	*/
	protected $_defaults = 
	[
		// Storage
		
		'dirStorage' => 'http/client', // [string] Directory to store files (only cookies and http cache right now). Relative (var/storage pointed in root directory - root in this case is upper to the lib) or absolute path. $PID$ will be replaced with current posix pid. Null or empty string to hardly disable storing files (everything will be stored in php variable only - strongly not recommended).
		'storageTimeOut' => 0, // [int] 0 to disable (strongly recommended).
		
		// Http client cache management
		
		'httpCacheEnabled' => true, // [bool] True to enable http caching and storaging (writing and reading), false to disable (available only for GET requests).
		'httpCacheDefaultMaxAge' => 3, // [int|null] Default max-age (max time to reuse response without recheck when server doesn't explain this), 0 or null to always ask for newer data.
		'httpCacheStorageSizeLimit' => 41943040, // [int|null] Limit http response size to store in a file in bytes (41943040 = 40MB). 0 or null to disable storing cache in files.
		'httpCacheVarSizeLimit' => 163840, // [int|null] Limit http response size to store in a variable in bytes (163840 = 160KB). 0 or null to disable.
		'httpCacheVarSumSizeLimit' => 3145728, // [int|null] Limit http responses sumarical size to store in a variable in bytes (3145728 = 3MB). 0 or null to disable.
		'httpCacheUnallowedHosts' => [], // [array] Disable http cache/storage on this host(s). Host name is a value of array element. Empty array to disable.
		'httpCacheUnallowedUrls' => [], // [array] Disable cache/storage on this url(s). Url is a value of array element. Empty array to disable. NOTE: Compared url's doesn't have query.
		
		// Debugging
		
		'dbgRequestHeaders' => false, // [bool] Print request headers to stdout before sent. NOTE: request/response headers are not printed when no request was sent - sometimes Http_Cache  can have unexpired "stored" response - this response will be returned (in a newly created object).
		'dbgResponseHeaders' => false, // [bool] Print resonse headers to stdout after read response. NOTE: Might be printed with delay when 'keepAliveGetResponseOnlyOnDemand' is enabled.
		
		// Redirects (following Location)
		
		'maxRedirects' => 15, // [int] To prevent infinite loop. Increase it only when needed.
		'sendRefererOnRedirects' => true, // [bool] Send Referer header on following Location.
		
		// Pipelining
		
		'keepAliveByDefault' => true, // [bool] Try to use keep-alive. To disable it just once, add this to Your code: $request->setHeader('Connection', 'Close');
		'keepAliveTimeOutOffset' => 0.5, // [int|float] This setting removes some seconds from timeout (Keep-Alive header) - just to be sure. See: http://www.w3.org/Protocols/HTTP/1.1/draft-ietf-http-v11-spec-01.html#Keep-Alive. 0 to disable. Increment this value when Your host client is too slow.
		'getResponseOnlyOnDemand' => true, // [bool] Faster when enabled, but try to disable it on any problems, especially with cookies (if is enabled, cookies will be stored only on calling getResponse())
		'keepAliveDefaultTimeOut' => 3, // [int|float] Default timeout, used only when server doesn't describe it (without 'Keep-Alive' header or not in). 'keepAliveTimeOutOffset' setting will not be used here. 0 or null to disable. Hardly recommended to be enabled with low value on Nginx servers.
		'keepAliveDefaultTimeOut_useEvenWhenSocketWasReaded' => false, // [bool] False to use 'KeepAliveDefaultTimeOut' only when socket is unreaded only (faster way), true to use even if socket was readed after last write (safer way - unreaded response can have header 'Connection: close' and connection will be closed...)
		'keepAliveDefaultMax' => 8, // [int] Default maximal number of requests per persistent connection, used only when server doesn't describe it (without 'Keep-Alive' header or not in). 0 or null to disable. Hardly recommended to be enabled with low value on Nginx servers.
		'keepAliveDefaultMax_useEvenWhenSocketWasReaded' => false, // [bool] False to use 'keepAliveDefaultMax' only when socket is unreaded only (faster way), true to use even if socket was readed after last write (safer way - unreaded response can have header 'Connection: close' and connection will be closed...)
		
		// Headers
		
		'useDefaultHeaders' => true, // [bool] Http_Request sending some headers by default if wasnt set before ('Connection', 'Accept', 'User-Agent', 'Accept-Language', 'Accept-Charset', 'Accept-Encoding', 'Content-Type').
		
		// Cookies
		
		'receiveCookiesByDefault' => true, // [bool] Store received cookies or not. Used in Http_Cookie_Proxy.
		'sendCookiesByDefault' => true, // [bool] Send stored cookies or not. Used in Http_Cookie_Proxy. Anyway, Always You can set 'Cookie' header by hand using Http_Request->setHeader() and send temporary cookie by Http_Cookie_Proxy.
		
		'cookieSizeLimit' => 16384, // [int] http://www.ietf.org/rfc/rfc2109.txt - minimum is 4096 bytes (0 to disable limit)
		
		'dropSessionCookiesOnExit' => false, // [bool] Delete all session cookies on php exiting (exacly, triggered by destructor when enabled)
		
		// Http client history
		
		'historyFileInTxtMicroTime' => 'http/client/log_microtime.txt', // [string] Relative (var/log pointed in root directory - root in this case is upper to the lib) or absolute path to store history in txt format with time in unix timestamp created by microtime(true). Null or empty string to disable it. Log format is: pid(posix) requestId network_address(ip) time(x.y) data_method(with stripped 'DATA_METHOD_') URL. $PID$ will be replaced with current posix pid.
		//'historyFileInTxtMicroTime' => 'http/client/log_$PID$_microtime.txt',
		'historyFileInTxtTimeInRFC1036' => 'http/client/log.txt', // [string] Relative (var/log pointed in root directory - root in this case is upper to the lib) or absolute path to store history in txt format with time in RFC 1036. Null or empty string to disable it. Log format is: pid(posix) requestId network_address(ip) time(RFC 1036: Wdy, DD Mon YY HH:MM:SS +0000) data_method(with stripped 'DATA_METHOD_') URL. $PID$ will be replaced with current posix pid.
		//'historyFileInTxtTimeInRFC1036' => 'http/client/log_$PID$.txt',
		'historyFileSerialized' => '' // [string] Relative (var/log pointed in root directory - root in this case is upper to the lib) or absolute path to store history in serialized file. Null or empty string to disable it. $PID$ will be replaced with current posix pid.
		//'historyFileSerialized' => 'http/client/log_$PID$.ser'
	];
	
	/**
	*	Get value of var (setting)
	*	@param string $var Var (setting) name
	*	@return mixed Var (setting) value
	*	@throws Http_Settings_Exception
	*/
	public function __get($var)
	{
		if(!isset($this->_vars[$var]))
			throw new Http_Settings_Exception('Tried to get unknown setting (\'' . $var . '\')');
		return $this->_vars[$var];
	}
	
	/**
	*	Set variable (setting)
	*	@param string $var Var (setting) name
	*	@param mixed $value New value
	*	@return mixed Setted value
	*	@throws Http_Settings_Exception
	*/
	public function __set($var, $value)
	{
		if(!isset($this->_vars[$var]))
			throw new Http_Settings_Exception('Tried to set unknown setting (\'' . $var . '\')');
		return $this->_vars[$var] = $value;
	}
}