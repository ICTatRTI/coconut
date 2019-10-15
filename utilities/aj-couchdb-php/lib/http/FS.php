<?php
/**
*	File-System operations library
*	Copyright (C) 2009-2014  Norbert Krzysztof Kiszka <norbert at linux dot pl>
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
*	@copyright Copyright (c) 2009-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.2
*/

/**
*	File-System operations library
*	@category Lib
*	@package FS
*	@version 0.2
*/
class FS
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.2';
	
	/**
	*	Default chmod for directories
	*	@var int octal
	*/
	const CHMOD_DIR_DEFAULT = 0700;
	
	/**
	*	Default chmod for files
	*	@var int octal
	*/
	const CHMOD_FILE_DEFAULT = 0600;
	
	/**
	*	Define php constants for folders
	*	@return void
	*/
	public static function defineDirConstants()
	{
		if(defined('DIR_LIB'))
			return;
		
		define('DIR_LIB', __DIR__);
		define('DIR_LIB_SLASH', DIR_LIB . '/');
		
		define('DIR_ROOT', realpath(DIR_LIB_SLASH . '../'));
		define('DIR_ROOT_SLASH', DIR_ROOT . '/');
		
		define('DIR_VAR', realpath(DIR_LIB_SLASH . '../var'));
		define('DIR_VAR_SLASH', DIR_VAR . '/');
		
		define('DIR_VAR_SPOOL', DIR_VAR_SLASH . 'spool');
		define('DIR_VAR_SPOOL_SLASH', DIR_VAR_SPOOL . '/');
		
		define('DIR_VAR_LOG', DIR_VAR_SLASH . 'log');
		define('DIR_VAR_LOG_SLASH', DIR_VAR_LOG . '/');
		
		define('DIR_VAR_STORAGE', DIR_VAR_SLASH . 'storage');
		define('DIR_VAR_STORAGE_SLASH', DIR_VAR_STORAGE . '/');
	}
	
	/**
	*	Make a given path in fs (recursive mkdir)
	*	@note Given path must be absolute
	*	@param string $path Path to create (ex.: /home/user/path/to/create/recursively)
	*	@return bool True on success, false on error
	*	@throws FS_Exception
	*/
	public static function preparePath($path)
	{
		if(!is_string($path))
			throw new FS_Exception('String only');
		
		if($path == '')
			throw new FS_Exception('Empty string given');
		
		if($path{0} != '/')
			throw new FS_Exception("Given path '$path' is not a absolute path");
		
		if(is_dir($path))
			if(is_writable($path))
				return true;
			else
			{
				if(!chmod($path, static::CHMOD_DIR_DEFAULT))
				{
					trigger_error('chmod failed on ' . $path);
					return false;
				}
				return true;
			}
			
		if(is_file($path))
		{
			trigger_error("Can not create path '$path', given path points to a regular file", E_USER_WARNING);
			return false;
		}
		
		$dd = explode('/', $path);
		array_shift($dd);
		$vv = '/';

		foreach($dd as $v)
		{
			$nd = $vv . $v;
			
			if(is_file($nd))
			{
				trigger_error("Error on creating path '$path', part of given path points to a regular file ($nd)", E_USER_WARNING);
				return false;
			}
			
			if(!is_dir($nd))
			if(!mkdir($nd))
			{
				trigger_error("mkdir('$nd') failed when creating path '$path'", E_USER_WARNING);
				return false;
			}
			
			$vv .= $v . '/';
		}
		
		return true;
	}
	
	/**
	*	Prepare path for a file (file path)
	*	@note Given path must be absolute
	*	@note File will not be created
	*	@note If file already exists, will return true, and touch() will not be executed (modification time will be unchanged)
	*	@param string $filePath Path to a file
	*	@return bool True on success, false on error
	*	@uses preparePath()
	*	@throws FS_Exception
	*/
	public static function preparePathForGivenFilePath($filePath)
	{
		if(!is_string($filePath))
			throw new FS_Exception('String only');
		
		if($filePath == '')
			throw new FS_Exception('Empty string given');
		
		if($filePath{0} != '/')
			throw new FS_Exception("Given file path '$filePath' is not a absolute path");
		
		if(is_file($filePath))
			if(is_writable($filePath))
				return true;
			else
			{
				if(!chmod($filePath, static::CHMOD_FILE_DEFAULT))
				{
					trigger_error('chmod failed on ' . $filePath);
					return false;
				}
				return true;
			}
			
		return static::preparePath(dirname($filePath));
	}
	
	/**
	*	Proxy to a php function file_put_contents() with previous preparing path if needed (recursive mkdir)
	*	@param string $filename
	*	@param mixed $data
	*	@param int $flags
	*	@param resource $context
	*	@return int|false
	*	@uses preparePathForGivenFilePath()
	*/
	public static function file_put_contents($filename, $data, $flags = 0, $context = null)
	{
		static::preparePathForGivenFilePath($filename); // better to not check of return from it, because somebody wants "normal" php error from file_put_contents()
		return file_put_contents($filename, $data, $flags, $context);
	}
}