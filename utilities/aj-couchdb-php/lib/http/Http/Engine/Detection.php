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
*	@version 0.5
*/

/**
*	Http client (browser) engine detection from user-agent string
*	@category Http Client
*	@package Http Client
*	@version 0.5
*/
class Http_Engine_Detection
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Get browser engine name from user-agent string
	*	@param string $ua user-agent string
	*	@return string Lowercase engine name (webkit, gecko, presto, msie, mspie, libwww) or 'unknown' when is unknown
	*/
	public static function getEngineNameFromUA($ua)
	{
		switch(true)
		{
			case static::isWebkit($ua):
				return 'webkit';
			break;
			
			case static::isGecko($ua):
				return 'gecko';
			break;
			
			case static::isPresto($ua):
				return 'presto';
			break;
			
			case static::isMsie($ua):
				return 'msie';
			break;
			
			case static::isMspie($ua):
				return 'mspie';
			break;
			
			case static::isLibwww($ua):
				return 'libwww';
			break;
			
			default:
				return 'unknown';
		}
	}
	
	/**
	*	Check if a given user-agent name is a webkit engine
	*	@param string $ua user-agent string
	*	@return bool True if it's webkit, false otherwise
	*/
	public static function isWebkit($ua)
	{
		return false !== stristr($ua, 'webkit');
	}
	
	/**
	*	Check if a given user-agent name is a gecko engine
	*	@param string $ua user-agent string
	*	@return bool True if it's gecko, false otherwise
	*/
	public static function isGecko($ua)
	{
		return false !== stristr($ua, 'gecko') && false === stristr($ua, 'webkit');
	}
	
	/**
	*	Check if a given user-agent name is a presto engine
	*	@param string $ua user-agent string
	*	@return bool True if it's presto, false otherwise
	*/
	public static function isPresto($ua)
	{
		return false !== stristr($ua, 'presto');
	}
	
	/**
	*	Check if a given user-agent name is a msie (any version) engine
	*	@param string $ua user-agent string
	*	@return bool True if it's msie, false otherwise
	*/
	public static function isMsie($ua)
	{
		return (false !== stristr($ua, 'msie') || false !== stristr($ua, 'microsoft internet explorer')) && false === stristr($ua, 'gecko') && false === stristr($ua, 'webkit');
	}
	
	/**
	*	Check if a given user-agent name is a mspie engine
	*	@param string $ua user-agent string
	*	@return bool True if it's mspie, false otherwise
	*/
	public static function isMspie($ua)
	{
		return false !== stristr($ua, 'mspie');
	}
	
	/**
	*	Check if a given user-agent name is a libwww (lynx) engine
	*	@param string $ua user-agent string
	*	@return bool True if it's libwww, false otherwise
	*/
	public static function isLibwww($ua)
	{
		return false !== stristr($ua, 'libwww');
	}
}