<?php
/**
*	Reusable File Storage
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
*	@package FS
*	@subpackage Reusable File Storage
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.3.2
*/

/**
*	Abstract class to handle simple file storages.
*	Reading is cached by a class property.
*	@note This class uses settings based on a Settings_Abstract extended class object, You must select proper class (extended from a Settings_Abstract) by changing default 'SETTINGS_CLASS' constant.
*	@note Storage name will be used as a file name, with path set by a settings object (see previous note).
*	@note Path to a file (without file name) describes setting named dirStorage (Settings_Abstract).
*	@note Storage timeout in seconds is set by setting named storageTimeOut (Settings_Abstract).
*	<code>
*	$storage->some_stored_content = 'foo';
*	echo $storage->some_stored_content . "\n";
*	</code>
*	@category Lib
*	@package FS
*	@subpackage Reusable File Storage
*	@version 0.3.2
*/
abstract class File_Storage_Abstract
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.3.2';
	
	/**
	*	Singleton settings class to use for get settings in here
	*	@var string
	*/
	const SETTINGS_CLASS = 'Settings_Abstract'; // You must modify it to your needs in extended class (give some class that extends Settings_Abstract and have a 'dirStorage' and 'storageTimeOut' settings)
	
	/**
	*	Stored contents
	*	@var array
	*/
	protected $_storage = [];
	
	/**
	*	Storage settings
	*	@var Settings_Abstract
	*/
	protected $_settings;
	
	/**
	*	@var File_Storage_Abstract
	*/
	protected static $_instance;

	/**
	*	Get singleton instance
	*	@return File_Storage_Abstract
	*/
	public static function getInstance()
	{
		if(!isset(static::$_instance))
			static::$_instance = new static;
		return static::$_instance;
	}
	
	/**
	*	Constructor
	*	@return void
	*/
	protected function __construct()
	{
		$t = static::SETTINGS_CLASS;
		$this->_settings = $t::getInstance();
	}
	
	/**
	*	Write into storage (php variable and a regular file with same name)
	*	@param string $name Storage name - will be exacly same as file name
	*	@param mixed $content Contents to write
	*	@return mixed Writed content
	*/
	public function __set($name, $content)
	{
		$content = [$content, microtime(true)];
		$storageFile = $this->_prepareStorageFilePath($name);
		
		if($storageFile != '')
		if(!FS::file_put_contents($storageFile, serialize($content)))
			trigger_error('Saving storage file failed: ' . $storageFile, E_USER_WARNING);
		
		return $this->_storage[$name] = $content;
	}
	
	/**
	*	Read storage
	*	@param string $name Storage name
	*	@return mixed|null Storage content or null when not founded / expired / on error
	*/
	public function __get($name)
	{
		if(isset($this->_storage[$name]))
		{
			list($content, $creation) = $this->_storage[$name];
			
			if($this->_isTimedOut($creation))
				goto del;
			
			return $content;
		}
		
		$storageFile = $this->_prepareStorageFilePath($name);
		
		if($storageFile != '' && file_exists($storageFile))
		{
			if(!$t = file_get_contents($storageFile))
			{
				trigger_error('Reading storage file failed: ' . $storageFile, E_USER_WARNING);
				return;
			}
			
			if(!$t = unserialize($t))
			{
				trigger_error('Unserialize storage file failed: ' . $storageFile, E_USER_WARNING);
				return;
			}
			
			list($content, $creation) = $t;
			
			if($this->_isTimedOut($creation))
				goto del;
			
			$this->_storage[$name] = $t;
			
			return $content;
		}
		
		del:
			$this->__unset($name);
			return;
	}
	
	/**
	*	Delete storage
	*	@param string $name Storage name to delete
	*	@return void
	*/
	public function __unset($name)
	{
		unset($this->_storage[$name]);
		
		$storageFile = $this->_prepareStorageFilePath($name);
		if($storageFile != '' && file_exists($storageFile) && !unlink($storageFile))
			trigger_error('Unlink storage file failed: ' . $storageFile, E_USER_WARNING);
	}
	
	/**
	*	Get time of last write
	*	@param string $name Storage name
	*	@return float
	*/
	public function getTime($name)
	{
		$this->__get($name); // hack
		
		if(isset($this->_storage[$name]))
			return $this->_storage[$name][1];
	}
	
	/**
	*	Check if a storage is available (writed and doesnt timed out)
	*	@param string $name Storage name
	*	@return bool True when is available
	*/
	public function __isset($name)
	{
		$storageFile = $this->_prepareStorageFilePath($name);
		
		return
			(isset($this->_storage[$name]) && !$this->_isTimedOut($this->_storage[$name][1]))
			||
			($storageFile != '' && file_exists($storageFile) && !$this->_isTimedOut(unserialize(file_get_contents($storageFile))[1]));
	}
	
	/**
	*	Check if storage time is timed-out.
	*	@param float|int $storageTime storage creation unix time-stamp
	*	@return bool True if is timeout, false if not
	*/
	protected function _isTimedOut($storageTime)
	{
		$storageTimeOut = $this->_settings->storageTimeOut; // get every time, because this setting can be run-time changed
		return $storageTimeOut && (microtime(true) > $storageTime + $storageTimeOut);
	}
	
	/**
	*	Prepare file path for storage file
	*	@note Will return empty string when storaging in files is disabled
	*	@param string $storageName Storage name
	*	@return string Proper path to storage file
	*/
	protected function _prepareStorageFilePath($storageName)
	{
		// get setting 'dirStorage' everytime because it can be run-time changed
		
		$dirStorage = $this->_settings->dirStorage;
		
		$dirStorageStrlen = strlen($dirStorage);
		
		if(!$dirStorageStrlen)
			return '';
		
		if($dirStorage{$dirStorageStrlen - 1} != '/')
			$dirStorage .= '/';
		
		if($dirStorage{0} != '/')
			$dirStorage = DIR_VAR_STORAGE_SLASH . $dirStorage;
		
		//return str_replace('$PID$', posix_getpid(), $dirStorage) . $storageName; // get pid everytime because this process can be a sub-thread (fork or pthread)
        return str_replace('$PID$', getmygid(), $dirStorage) . $storageName; // get pid everytime because this process can be a sub-thread (fork or pthread)

	}
}