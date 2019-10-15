<?php
/**
*	Domain library
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
*	@subpackage Public Suffix List
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.1
*/

/**
*	Domain public suffix list
*	@category Lib
*	@package Domain library
*	@subpackage Public Suffix List
*	@version 0.1
*/
class Domain_PublicSuffixList
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.1'; // every line of this class was hardly tested, anyway I maked version number very low anyway
	
	/**
	*	TLD effective list - source
	*	@var string
	*/
	const TLD_EFFECTIVE_LIST_TXT = 'tld/effective_tld_names.dat';
	
	/**
	*	TLD effective list - php version (array)
	*	@var string
	*/
	const TLD_EFFECTIVE_LIST_PHP = 'tld/effective_tld_names.php';
	
	/**
	*	TLD effective list - serialized version (array)
	*	@var string
	*/
	const TLD_EFFECTIVE_LIST_SER = 'tld/effective_tld_names.ser';
	
	/**
	*	URL to get updated TLD effective list
	*	@var string
	*/
	const TLD_EFFECTIVE_LIST_DOWNLOAD = 'https://publicsuffix.org/list/effective_tld_names.dat'; // used only when expires (update tried once per 30 days)
	
	/**
	*	TLD effective list
	*	@var array
	*	@todo replace entries into keys because key-finding in php is much more faster
	*/
	protected static $_tldEffectiveList = [];
	
	/**
	*	Changed to true when tld effective list is at updating - to prevent infinite loop
	*	@var bool
	*/
	protected static $_tldEffectiveListUnderUpdating = false;
	
	/**
	*	Check if given domain is in TLD effective list.
	*	It can be cookie domain with dot as first char (every dots at start will be cutted).
	*	@param string $domain Domain to check
	*	@return bool True when given domain is listed in TLD effective list, false otherwise (RFC 3492)
	*	@throws Domain_PublicSuffixList_Exception
	*/
	public static function isDomainInTldEffectiveList($domain)
	{
		if(!is_string($domain) || $domain == '')
			throw new Domain_PublicSuffixList_Exception('First argument must be a string and cant be null length');
			
		if(!static::$_tldEffectiveList)
			static::_tldEffectivePrepare();
		
		//if($domain{0} == '.')
		//	$domain = substr($domain, 1);
		$domain = ltrim($domain, '.');
		
		$domainParts = explode('.', $domain);
		
		$domainPartsCount = count($domainParts);
		
		$top = $domainParts[$domainPartsCount-1];
		
		if(!isset(static::$_tldEffectiveList[$top])) // truly top domain doesn't match
			return false;
		
		$domainStrlen = strlen($domain);
		
		foreach(static::$_tldEffectiveList[$top] as $entry)
		{
			if($domain == $entry)
				return true;
			
			if($entry{0} == '!' && substr($entry, 1) == $domain) // exception (!domain-name)
				return false;
			
			if($entry{0} == '*') // exacly: *. (2 chars)
			{
				$entry = substr($entry, 2); // remove '*.'
				
				//$entryParts = explode('.', $entry);
				
				//if(count($entryParts) + 1 != $domainPartsCount) // other count of elements (more or less)
				//	continue;
				
				$t = $domainParts;
				
				array_shift($t);
				
				$t = implode('.', $t);
				
				if($t == $entry)
					return true;
			}
		}
		
		return false;
	}
	
	/**
	*	Prepare TLD effective list
	*	@return void
	*	@throws Domain_PublicSuffixList_Exception
	*/
	protected static function _tldEffectivePrepare()
	{
		// serialized file is used, because its 2 times faster
		
		FS::defineDirConstants();
		
		$phpFile = DIR_VAR_SPOOL_SLASH . static::TLD_EFFECTIVE_LIST_PHP;
		$serFile = DIR_VAR_SPOOL_SLASH . static::TLD_EFFECTIVE_LIST_SER;
		
		$file = DIR_VAR_SPOOL_SLASH . static::TLD_EFFECTIVE_LIST_TXT;
			
		if(!is_file($file))
			throw new Domain_PublicSuffixList_Exception($file . ' doesn\'t exists or is a directory');
		
		if(false === ($t = filemtime($file)))
			throw new Domain_PublicSuffixList_Exception('Something wrong... file exists (or was) but filemtime returned false. File name is: '. $file);
		
		$DatFileUpdated = false;
		
		if($t < time() - 2592000) // 30d * 24h * 3600s
			$DatFileUpdated = static::_tldEffectiveUpdateDatFile();
		
		if($DatFileUpdated || !file_exists($phpFile) || !file_exists($serFile))
		{
			if(!($lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES)))
				throw new Domain_PublicSuffixList_Exception($file . ': read error or empty file');
			
			// Replace all exceptions (!domain-name) to the first, because iterating all of it to find eventual exception loses little more time
			
			$exceptions = [];
			foreach($lines as $k => $line)
			{
				$line = trim($line);
				
				if($line == '') // empty line (I dont have trust to the php engine)
					continue;
				
				if($line{0} == '!')
				{
					$exceptions[] = $line;
					unset($lines[$k]);
				}
			}
			$lines = array_merge($exceptions, $lines);
			
			$php = '<?php return [';
			
			$nl = [];
			
			foreach($lines as $line)
			{
				$line = trim($line);
				
				if(!strlen($line)) // empty line (I dont have trust to the php engine)
					continue;
				
				if(substr($line, 0, 2) == '//') // comment
					continue;
				
				$d = explode('.', $line);
				
				$top = $d[count($d)-1]; // technically top domain (after last dot or when no dots)
				
				// group list with top-domains for faster searching in this array
				
				if(!isset($nl[$top]))
					$nl[$top] = [];
				
				$nl[$top][] = $line;
				
				$php .= '\'' . addcslashes($line, '\'') . "',\n";
			}
			
			foreach($nl as $top => $subdomains) // remove duplicates
				$nl[$top] = array_unique($nl[$top]);
			
			$php = substr($php, 0, strlen($php) - 2); // remove last comma
			
			$php .= '];';
			
			if(!FS::file_put_contents($phpFile, $php))
				trigger_error($phpFile . ' saving failed', E_USER_WARNING);
			if(!FS::file_put_contents($serFile, serialize($nl)))
				trigger_error($serFile . ' saving failed', E_USER_WARNING);
			
			static::$_tldEffectiveList = $nl;
		}
		else
		{
			if(!strlen($t = file_get_contents($serFile)) || !is_array($t = unserialize($t)) || !$t)
				throw new Domain_PublicSuffixList_Exception($serFile . ': unserialize failed');
			
			static::$_tldEffectiveList = $t;
		}
	}
	
	/**
	*	Update tld effective list from given url in static::TLD_EFFECTIVE_LIST_DOWNLOAD
	*	@note We should NOT update this file often than once per day to prevent server abuse
	*	@note On a error, tld effective list should remain unchanged. Next update retry will be after one hour, not earlier.
	*	@return bool True when successfully updated, false otherwise or when already updating (can be called in a loop)
	*/
	protected function _tldEffectiveUpdateDatFile()
	{
		if(static::$_tldEffectiveListUnderUpdating)
			return false;
		
		static::$_tldEffectiveListUnderUpdating = true;
		
		$lastTldEffectiveListRetrieving_file = DIR_VAR_SPOOL_SLASH . 'tld/lastTldEffectiveListRetrieving.dat';
		
		if(($lastTldEffectiveListRetrieving = @file_get_contents($lastTldEffectiveListRetrieving_file)) > 0 && time() - 3600 < $lastTldEffectiveListRetrieving) // prevent retries often than one hour (in a case of previous updating error)
		{
			static::$_tldEffectiveListUnderUpdating = false; // reset to false, because {main} can work even for year (or longer...)
			return false;
		}
		
		FS::file_put_contents($lastTldEffectiveListRetrieving_file, (string)time());
		
		try // throwing any exception from here is unwanted
		{
			$response = (new Http_Request(static::TLD_EFFECTIVE_LIST_DOWNLOAD))->getResponse();
			
			if($response->getStatus() != 200)
			{
				trigger_error('Can\'t update TLD effective list, server returned status: ' . $response->getStatusRaw() . '. Url was: ' . static::TLD_EFFECTIVE_LIST_DOWNLOAD, E_USER_WARNING);
				static::$_tldEffectiveListUnderUpdating = false; // reset to false, because {main} can work even for year (or longer...)
				return false;
			}
			
			if(!strlen($body = $response->getBody()))
			{
				trigger_error('Can\'t update TLD effective list, server returned empty response body. Url was: ' . static::TLD_EFFECTIVE_LIST_DOWNLOAD, E_USER_WARNING);
				static::$_tldEffectiveListUnderUpdating = false; // reset to false, because {main} can work even for year (or longer...)
				return false;
			}
		}
		catch(Exception $e)
		{
			trigger_error('Exception catched on retrieving new TLD effective list. Exception was \'' . get_class($e) . '\' with message: \'' . $e->getMessage() . '\' in file ' . $e->getFile() . ' on line ' . $e->getLine(), E_USER_WARNING);
			static::$_tldEffectiveListUnderUpdating = false; // reset to false, because {main} can work even for year (or longer...)
			return false;
		}
		
		$file = DIR_VAR_SPOOL_SLASH . static::TLD_EFFECTIVE_LIST_TXT;
		
		if(is_file($file))
		{
			// backup it to eventualy restore in a error, or when we need to check older version
			copy($file, $file . '.old_' . ((string)microtime(true)));
		}
		
		if(!FS::file_put_contents($file, $body, LOCK_EX)) // returned content shouldn't be empty
		{
			trigger_error('Saving TLD effective list file failed. File name was: ' . $file, E_USER_WARNING);
			static::$_tldEffectiveListUnderUpdating = false; // reset to false, because {main} can work even for year (or longer...)
			return false;
		}
		
		static::$_tldEffectiveListUnderUpdating = false; // reset to false, because {main} can work even for year (or longer...)
		
		return true; // success
	}
}