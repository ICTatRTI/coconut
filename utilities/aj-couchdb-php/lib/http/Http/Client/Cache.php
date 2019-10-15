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
*	Http client - http cache helper.
*	See included howto.
*	@warning This class should be used only by Http_Client class
*	@see Http_Request
*	@category Http Client
*	@package Http Client
*	@version 0.5.1
*/
class Http_Client_Cache
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Filled by Http_Client::__construct()
	*	@var Http_Settings
	*/
	public static $settings = null;
	
	/**
	*	Stored/cached responses or responses info
	*	@var array
	*	@ignore
	*/
	protected static $_requests = [];
	
	/**
	*	Total size of locally stored/cached responses
	*	@var int
	*/
	protected static $_totalVarCacheSize = 0;
	
	/**
	*	Set freshly created responseParser to figure out to cache/store or not to store/cache and do it
	*	@param Http_Response_Parser $responseParser
	*	@param Http_Request_Interface $request
	*	@param float $timeSent
	*	@return void
	*/
	public static function setResponseParser(Http_Response_Parser $responseParser, Http_Request_Interface $request, $timeSent)
	{
		$host = $request->getHost();
		
		if(!self::$settings->httpCacheEnabled)
			return;
		
		foreach(self::$settings->httpCacheUnallowedHosts as $t)
			if($t == $host)
				return;
		
		$urlWithoutQuery = $request->getUrlWithoutQuery();
		
		foreach(self::$settings->httpCacheUnallowedUrls as $t)
			if($t == $urlWithoutQuery)
				return;
		
		do
		{
			$cacheAllowed = true;
			
			$httpCacheVarSizeLimit = self::$settings->httpCacheVarSizeLimit;
			$httpCacheStorageSizeLimit = self::$settings->httpCacheStorageSizeLimit;
			if(($httpCacheMaxAge = self::$settings->httpCacheDefaultMaxAge) <= 0)
				$httpCacheMaxAge = 0;
			
			$size = $responseParser->getSizeOf();
			
			if($size > $httpCacheVarSizeLimit && $size > $httpCacheStorageSizeLimit)
			{
				$cacheAllowed = false;
				break;
			}
			
			if($request->getUser() != '' || $request->getPass() != '') // Don't use cache when using http basic auth
			{
				$cacheAllowed = false;
				break;
			}
			
			$cacheData = $responseParser->getCacheParams();
			
			$minFresh = 0;
			$maxAge = false;
			if(isset($cacheData['cache-control']))
			{
				foreach($cacheData['cache-control'] as $cc)
				{
					if($cc == 'no-store')
					{
						$cacheAllowed = false;
						break;
					}
					
					if($cc == 'no-cache' || $cc == 'must-revalidate')
					{
						$httpCacheMaxAge = 0;
						continue;
					}
					
					if(substr($cc, 0, 8) == 'max-age=')
					{
						$httpCacheMaxAge = $maxAge = (int)substr($cc, 8);
						continue;
					}
					
					if(substr($cc, 0, 8) == 'min-fresh=')
						$minFresh = (int)substr($cc, 10);
				}
			}
			
			if(isset($cacheData['pragma']) && false !== stristr($cacheData['pragma'], 'no-cache'))
				$httpCacheMaxAge = 0;
			
			if(isset($cacheData['date']))
			{
				try
				{
					$timeSent = Date::parseRfc822Date($cacheData['date']);
				}
				catch(Exception $e)
				{
					trigger_error('Cant calculate \'Date\', Date::parseRfc822Date() throwed an exception ' . get_class($e) . ' with message: ' . $e->getMessage(), E_USER_WARNING);
				}
			}
			
			if(isset($cacheData['expires']))
			{
				try
				{
					$delta = Date::parseRfc822Date($cacheData['expires']) - $timeSent;
				}
				catch(Exception $e)
				{
					trigger_error('Cant calculate \'Expires\', Date::parseRfc822Date() throwed an exception ' . get_class($e) . ' with message: ' . $e->getMessage(), E_USER_WARNING);
					unset($cacheData['expires']);
				}
			}
			
			if(!isset($cacheData['last-modified']) && !isset($cacheData['etag']) && !isset($cacheData['expires']))
			{
				$cacheAllowed = false;
				break;
			}
			
			if(!isset($delta))
				$delta = $httpCacheMaxAge;
			elseif($maxAge !== false)
				$delta = min($delta, $maxAge);
		}
		while(false);
		
		$url = $request->getUrl();
		
		$urlBase64 = base64_encode($url);
		
		self::drop($host, $url, $urlBase64); // New response, so drop old one in any case
		
		if(!$cacheAllowed)
			return;
		
		$header = $responseParser->getHeaderSource();
		$bodyRaw = $responseParser->getBodyRaw();
		
		$info =
		[
			'lastModified' => isset($cacheData['last-modified']) ? $cacheData['last-modified'] : '',
			'etag' => isset($cacheData['etag']) ? $cacheData['etag'] : '',
			'timeSent' => $timeSent,
			'size' => $size,
			'delta' => $delta,
			'minFresh' => $minFresh,
			'status' => $responseParser->getStatus(),
			'statusRaw' => $responseParser->getStatusRaw(),
			'urlWithoutQuery' => $urlWithoutQuery, // Cache it to prevent calling getUrlWithoutQuery() many times
		];
		
		// Always store info locally, to prevent too often reading small info files
		
		if(!isset(self::$_requests[$host]))
			self::$_requests[$host] = [];
		
		if(!isset(self::$_requests[$host][$url]))
			self::$_requests[$host][$url] = [];
		
		if($size <= $httpCacheVarSizeLimit)
		{
			self::$_totalVarCacheSize += $size;
			$httpCacheVarSumSizeLimit = self::$settings->httpCacheVarSumSizeLimit;
			if($httpCacheVarSumSizeLimit > 0)
			{
				$sizeDelta = self::$_totalVarCacheSize - $httpCacheVarSumSizeLimit;
				if($sizeDelta > 0)
					self::_makeSpace($sizeDelta, $url);
			}
			
			self::$_requests[$host][$url] = $info;
			self::$_requests[$host][$url]['header'] = $header;
			self::$_requests[$host][$url]['bodyRaw'] = $bodyRaw;
		}
		else
			self::$_requests[$host][$url] = $info;
		
		if($size <= $httpCacheStorageSizeLimit)
		{
			$path = 'cache/' . substr($host, -3) . '/' . $host . '/' . $urlBase64;
			$fileInfo = self::_prepareStorageFilePath($path . '.info');
			if($fileInfo != '') // dirStorage can be disabled in settings
			{
				$fileCache = self::_prepareStorageFilePath($path . '.cache');
				
				if(!FS::file_put_contents($fileInfo, serialize($info), LOCK_EX))
					trigger_error('Saving cache info file failed. File name was: ' . $fileInfo, E_USER_WARNING);
				elseif(!FS::file_put_contents($fileCache, $header . "\n\n" . $bodyRaw, LOCK_EX))
					trigger_error('Saving cache file failed. File name was: ' . $fileCache, E_USER_WARNING);
			}
		}
	}
	
	/**
	*	Get stored response if it is stored and its not expired
	*	@param string $host
	*	@param string $url
	*	@param string $urlBase64
	*	@param Http_Request_Interface $request
	*	@return Http_Response|false Http_Response in a case that response is stored and not expired, false otherwise and on error
	*/
	public static function getStored($host, $url, $urlBase64, $request)
	{
		if(!self::$settings->httpCacheEnabled)
			return false;
		
		if(!($info = self::_getInfo($host, $url, $urlBase64)))
			return false;
		
		if(!self::$settings->httpCacheEnabled)
			return false;
		
		foreach(self::$settings->httpCacheUnallowedHosts as $t)
			if($t == $host)
				return;
		
		if(!($info = self::_getInfo($host, $url, $urlBase64)))
			return false;
		
		foreach(self::$settings->httpCacheUnallowedUrls as $t)
			if($t == $info['urlWithoutQuery'])
				return;
		
		$now = time();
		
		if($info['minFresh'] > 0 && $now - $info['minFresh'] <= $info['timeSent'])
			return false; // Server wants to revalidate response, because min-fresh is not expired
		
		if($now - $info['timeSent'] >= $info['delta'])
			return false; // Delta expired, so revalidate it
		
		if(isset(self::$_requests[$host][$url]['header']))
		{
			$headers = explode("\n", str_replace("\r\n", "\n", self::$_requests[$host][$url]['header']));
			array_shift($headers);
			foreach($headers as &$header)
			{
				$t = explode(': ', $header, 2);
				$header = [$t[0], $t[1]];
			}
			
			return new Http_Response
			(
				[
					'bodyRaw' => self::$_requests[$host][$url]['bodyRaw'],
					'headers' => $headers,
					'headerSource' => self::$_requests[$host][$url]['header'],
					'status' => self::$_requests[$host][$url]['status'],
					'statusRaw' => self::$_requests[$host][$url]['statusRaw']
				],
				$request
			);
		}
		
		$fileCache = self::_prepareStorageFilePath('cache/' . substr($host, -3) . '/' . $host . '/' . $urlBase64 . '.cache');
		
		if(!file_exists($fileCache))
		{
			//trigger_error('Cache file is missing: ' . $fileCache, E_USER_NOTICE);
			return false;
		}
		
		$headerSource = '';
		$headers = [];
		$fp = fopen($fileCache, 'r');
		$firstLine = true;
		while(true)
		{
			if(feof($fp))
			{
				fclose($fp);
				trigger_error('Cache file corrupted: ' . $fileCache, E_USER_WARNING);
				return false;
			}
			
			if(false === ($t = fgets($fp)))
			{
				fclose($fp);
				trigger_error('fgets() failed on reading headers from cache file: ' . $fileCache, E_USER_WARNING);
				return false;
			}
			
			$headerSource .= $t;
			
			$t = rtrim($t);
			
			if($t == '')
				break; // Two '\n' or '\r\n'
			
			if($firstLine) // Request line (ex.: HTTP/1.1 200 OK)
			{
				$firstLine = false;
				continue;
			}
			
			$t2 = explode(': ', rtrim($t), 2);
			$headers[] = [$t2[0], $t2[1]];
		}
		
		if(false === ($bodyRaw = file_get_contents($fileCache, false, null, strlen($headerSource))))
		{
			fclose($fp); // Close after file_get_contents() to secure body reading
			trigger_error('file_get_contents() failed on reading body from cache file: ' . $fileCache, E_USER_WARNING);
			return false;
		}
		fclose($fp); // Close after file_get_contents() to secure body reading
		
		$headerSource = rtrim($headerSource);
		
		if($info['size'] <= self::$settings->httpCacheVarSizeLimit)
		{
			self::$_totalVarCacheSize += $info['size'];
			$httpCacheVarSumSizeLimit = self::$settings->httpCacheVarSumSizeLimit;
			if($httpCacheVarSumSizeLimit > 0)
			{
				$sizeDelta = self::$_totalVarCacheSize - $httpCacheVarSumSizeLimit;
				if($sizeDelta > 0)
					self::_makeSpace($sizeDelta, $url);
			}
			
			self::$_requests[$host][$url]['bodyRaw'] = $bodyRaw;
			self::$_requests[$host][$url]['header'] = $headerSource;
		}
		
		return new Http_Response
		(
			[
				'bodyRaw' => $bodyRaw,
				'headers' => $headers,
				'headerSource' => $headerSource,
				'status' => $info['status'],
				'statusRaw' => $info['statusRaw']
			],
			$request
		);
	}
	
	/**
	*	Get cached request/response data if it is stored and its not expired
	*	@param string $host
	*	@param string $url
	*	@param string $urlBase64
	*	@return array|false Array in a case that response is stored and not expired, false otherwise and on error
	*/
	public static function getCached($host, $url, $urlBase64)
	{
		if(!self::$settings->httpCacheEnabled)
			return false;
		
		foreach(self::$settings->httpCacheUnallowedHosts as $t)
			if($t == $host)
				return;
		
		if(!($info = self::_getInfo($host, $url, $urlBase64)))
			return false;
		
		foreach(self::$settings->httpCacheUnallowedUrls as $t)
			if($t == $info['urlWithoutQuery'])
				return;
		
		if(isset($info['header'])) // Cached by a var
		{
			$headers = explode("\n", str_replace("\r\n", "\n", $info['header']));
			array_shift($headers);
			foreach($headers as &$header)
			{
				$t = explode(': ', $header, 2);
				$header = [$t[0], $t[1]];
			}
			$info['headers'] = $headers;
			return $info;
		}
		
		$fileCache = self::_prepareStorageFilePath('cache/' . substr($host, -3) . '/' . $host . '/' . $urlBase64 . '.cache');
		
		if(!file_exists($fileCache))
			return false; // Something deleted it (other instance or system user)
			
		if(!($fp = fopen($fileCache, 'r')))
			return false;
		
		$headerSource = '';
		$headers = [];
		$firstLine = true;
		while(true)
		{
			if(feof($fp))
			{
				trigger_error('Cache file corrupted: ' . $fileCache, E_USER_WARNING);
				fclose($fp);
				return false;
			}
			
			if(false === ($t = fgets($fp)))
			{
				trigger_error('fgets() failed on reading headers from cache file: ' . $fileCache, E_USER_WARNING);
				fclose($fp);
				return false;
			}
			
			$headerSource .= $t;
			
			$t = rtrim($t);
			
			if($t == '')
				break; // Two '\n' or '\r\n'
			
			if($firstLine) // Request line (ex.: HTTP/1.1 200 OK)
			{
				$firstLine = false;
				continue;
			}
			
			$t2 = explode(': ', rtrim($t), 2);
			$headers[] = [$t2[0], $t2[1]];
		}
		
		if(false === ($bodyRaw = file_get_contents($fileCache, false, null, strlen($headerSource))))
		{
			fclose($fp); // Close after file_get_contents() to secure body reading
			trigger_error('file_get_contents() failed on reading body from cache file: ' . $fileCache, E_USER_WARNING);
			return false;
		}
		fclose($fp); // Close after file_get_contents() to secure body reading
		
		$headerSource = rtrim($headerSource);
		
		if($info['size'] <= self::$settings->httpCacheVarSizeLimit)
		{
			self::$_totalVarCacheSize += $info['size'];
			$httpCacheVarSumSizeLimit = self::$settings->httpCacheVarSumSizeLimit;
			if($httpCacheVarSumSizeLimit > 0)
			{
				$sizeDelta = self::$_totalVarCacheSize - $httpCacheVarSumSizeLimit;
				if($sizeDelta > 0)
					self::_makeSpace($sizeDelta, $url);
			}
			
			self::$_requests[$host][$url]['bodyRaw'] = $bodyRaw;
			self::$_requests[$host][$url]['header'] = $headerSource;
		}
		
		$info['header'] = $headerSource;
		$info['headers'] = $headers;
		$info['bodyRaw'] = $bodyRaw;
		
		return $info;
	}
	
	/**
	*	Helper to get array with request/response info (and eventually with cached data)
	*	@param string $host
	*	@param string $url
	*	@param string $urlBase64
	*	@return array|false
	*/
	protected static function _getInfo($host, $url, $urlBase64)
	{
		if(isset(self::$_requests[$host]) && isset(self::$_requests[$host][$url]))
			return self::$_requests[$host][$url];
		
		$path = 'cache/' . substr($host, -3) . '/' . $host . '/' . $urlBase64;
		
		$fileInfo = self::_prepareStorageFilePath($path . '.info');
		
		if($fileInfo == '' || !file_exists($fileInfo))
			return false;
		
		if(!($info = file_get_contents($fileInfo)))
		{
			trigger_error('Reading cache info file failed. File name was: ' . $fileInfo, E_USER_WARNING);
			return false;
		}
		
		if(!($info = unserialize($info)))
		{
			trigger_error('Unserialize cache info file failed. File name was: ' . $fileInfo, E_USER_WARNING);
			return false;
		}
		
		if(!isset(self::$_requests[$host]))
			self::$_requests[$host] = [];
		
		return self::$_requests[$host][$url] = $info;
	}
	
	/**
	*	Drop stored data (all - from var and files by unlink())
	*	@param string $host
	*	@param string $url
	*	@param string $urlBase64
	*/
	public static function drop($host, $url, $urlBase64)
	{
		if(isset(self::$_requests[$host]) && isset(self::$_requests[$host][$url]))
		{
			unset(self::$_requests[$host][$url]);
			if(!count(self::$_requests[$host]))
				unset(self::$_requests[$host]);
		}
		
		$path = 'cache/' . substr($host, -3) . '/' . $host . '/' . $urlBase64;
		
		$fileInfo = self::_prepareStorageFilePath($path . '.info');
		$fileCache = self::_prepareStorageFilePath($path . '.cache');
		
		if(file_exists($fileInfo))
		if(!unlink($fileInfo))
		{
			//trigger_error('Unlink cache info file failed. File name was: ' . $fileInfo, E_USER_WARNING);
			return;
		}
		
		if(file_exists($fileCache))
		unlink($fileCache);
		//if(!unlink($fileCache))
		//	trigger_error('Unlink cache file failed. File name was: ' . $fileCache, E_USER_WARNING);
	}
	
	/**
	*	Update timings of stored info (when response have 304 status)
	*	@param string $host
	*	@param string $url
	*	@param string $urlBase64
	*	@param Http_Response_Parser $responseParser
	*	@param float $timeSent
	*	@return void
	*/
	public static function updateTimings($host, $url, $urlBase64, Http_Response_Parser $responseParser, $timeSent)
	{
		if(!isset(self::$_requests[$host]) || !isset(self::$_requests[$host][$url]))
			throw new Http_Client_Cache_Exception('Tried to update timings of unexisted cache');
		
		if(($httpCacheMaxAge = self::$settings->httpCacheDefaultMaxAge) <= 0)
			$httpCacheMaxAge = 0;
		
		$cacheData = $responseParser->getCacheParams();
		
		do
		{
			$minFresh = 0;
			$maxAge = false;
			$cacheAllowed = true;
			
			if(isset($cacheData['cache-control']))
			{
				foreach($cacheData['cache-control'] as $cc)
				{
					if($cc == 'no-store')
					{
						$cacheAllowed = false;
						break;
					}
					
					if($cc == 'no-cache' || $cc == 'must-revalidate')
					{
						$httpCacheMaxAge = 0;
						continue;
					}
					
					if(substr($cc, 0, 8) == 'max-age=')
					{
						$httpCacheMaxAge = $maxAge = (int)substr($cc, 8);
						continue;
					}
					
					if(substr($cc, 0, 8) == 'min-fresh=')
						$minFresh = (int)substr($cc, 10);
				}
			}
			
			if(isset($cacheData['date']))
			{
				try
				{
					$timeSent = Date::parseRfc822Date($cacheData['date']);
				}
				catch(Exception $e)
				{
					trigger_error('Cant calculate \'Date\', Date::parseRfc822Date() throwed an exception ' . get_class($e) . ' with message: ' . $e->getMessage(), E_USER_WARNING);
				}
			}
			
			if(isset($cacheData['expires']))
			{
				try
				{
					$delta = Date::parseRfc822Date($cacheData['expires']) - $timeSent;
				}
				catch(Exception $e)
				{
					trigger_error('Cant calculate \'Expires\', Date::parseRfc822Date() throwed an exception ' . get_class($e) . ' with message: ' . $e->getMessage(), E_USER_WARNING);
					unset($cacheData['expires']);
				}
			}
			
			if(!isset($delta))
				$delta = $httpCacheMaxAge;
			elseif($maxAge !== false)
				$delta = min($delta, $maxAge);
		}
		while(false);
		
		if(!$cacheAllowed)
		{
			self::drop($host, $url, $urlBase64);
			return;
		}
		
		$r = & self::$_requests[$host][$url];
		
		$r['timeSent'] = $timeSent;
		$r['delta'] = $delta;
		$r['etag'] = isset($cacheData['etag']) ? $cacheData['etag'] : $r['etag']; // Some versions of nginx sends etag only on revalidate (304 response)
		$r['minFresh'] = $minFresh ? $minFresh : $r['minFresh']; // Updated
		
		$info =
		[
			'lastModified' => $r['lastModified'],
			'etag' => $r['etag'],
			'timeSent' => $timeSent,
			'delta' => $delta,
			'minFresh' => $minFresh,
			'size' => $r['size'],
			'status' => $r['status'],
			'statusRaw' => $r['statusRaw'],
			'urlWithoutQuery' => $r['urlWithoutQuery']
		];
			
		$fileInfo = self::_prepareStorageFilePath('cache/' . substr($host, -3) . '/' . $host . '/' . $urlBase64 . '.info');
		
		if($fileInfo != '')
		if(!FS::file_put_contents($fileInfo, serialize($info)))
			trigger_error('Updating cache info file failed. File name was: ' . $fileInfo, E_USER_WARNING);
	}
	
	/**
	*	Clear old locally cache to make space for new cache (When setting 'httpCacheVarSumSizeLimit' has exceed)
	*	@param int $space size of cache to free in bytes
	*	@param string $ignoreUrl url to omit
	*	@return void
	*/
	protected static function _makeSpace($space, $ignoreUrl)
	{
		$freed = 0;
		foreach(self::$_requests as $host => $urls)
		{
			foreach($urls as $url => $req)
			if($req && $url != $ignoreUrl)
			{
				$freed += $req['size'];
				unset(self::$_requests[$host][$url]);
				if($freed >= $space)
					return;
			}
		}
	}
	
	/**
	*	Prepare file path for storage file
	*	@note Will return empty string when storaging in files is disabled ('dirStorage' setting)
	*	@param string $storageName storage name
	*	@return string proper path to storage file
	*/
	protected static function _prepareStorageFilePath($storageName)
	{
		// Get setting 'dirStorage' everytime because it can be run-time changed
		
		$dirStorage = self::$settings->dirStorage;
		
		$dirStorageStrlen = strlen($dirStorage);
		
		if(!$dirStorageStrlen)
			return '';
		
		if($dirStorage{$dirStorageStrlen - 1} != '/')
			$dirStorage .= '/';
		
		if($dirStorage{0} != '/')
			$dirStorage = DIR_VAR_STORAGE_SLASH . $dirStorage;
		
		return str_replace('$PID$', getmypid(), $dirStorage) . $storageName; // Get pid everytime because this process can be a sub-thread (fork or pthread)

	}
}