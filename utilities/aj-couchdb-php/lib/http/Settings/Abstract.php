<?php
/**
*	Reusable settings class
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
*	@package Settings
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.4
*/

/**
*	Abstract class to handle settings.
*	<code>
*	$settings = MySettings::getInstance();
*	$settings->myImportantSetting = '123';
*	echo $settings->myImportantSetting . "\n";
*	</code>
*	@category Lib
*	@package Settings
*	@version 0.4
*/
abstract class Settings_Abstract
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.4';
	
	/**
	*	Saved settings
	*	@var array
	*/
	protected $_vars = [];
	
	/**
	*	Default settings
	*	@var array
	*/
	protected $_defaults = []; // change it only on extended class
	
	/**
	*	@ignore
	*	@var Settings_Abstract
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
	*	Singleton (protected constructor)
	*	@return void
	*	@uses clean()
	*/
	protected function __construct()
	{
		$this->clean();
	}
	
	/**
	*	Clear values to defaults
	*	@return self
	*/
	public function clean()
	{
		$this->_vars = $this->_defaults;
		return $this;
	}
	
	/**
	*	Drop all values
	*	@return self
	*/
	public function purge()
	{
		$this->_vars = [];
		return $this;
	}
	
	/**
	*	Set variable (setting)
	*	@param string $var Var (setting) name
	*	@param mixed $value New value of setting
	*	@return mixed Value (same as given in second arg)
	*/
	public function __set($var, $value)
	{
		return $this->_vars[$var] = $value;
	}
	
	/**
	*	Get value of var (setting)
	*	@param string $var Var (setting) name
	*	@return mixed Var (setting) value. Null if isn't exists
	*/
	public function __get($var)
	{
		return isset($this->_vars[$var]) ? $this->_vars[$var] : null;
	}
	
	/**
	*	Drop var (setting)
	*	@param string $var Var (setting) name
	*	@return void
	*/
	public function __unset($var)
	{
		unset($this->_vars[$var]);
	}
	
	/**
	*	isset() on var (setting)
	*	@param string $var Var (setting) name
	*	@return bool True if exists, false otherwise
	*/
	public function __isset($var)
	{
		return isset($this->_vars[$var]);
	}
}