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
*	Http client - debug output helper
*	@category Http Client
*	@package Http Client
*	@version 0.5
*/
class Http_Client_Debug
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	Write dbg output to stdout (using echo())
	*	@param string $str String to write to console/log/something_else
	*	@param string $prepend String to prepend on every line
	*	@return void
	*/
	public static function write($str, $prepend = 'http')
	{
		if($str == '')
			return;
		
		$str = str_replace("\r\n", "\n", $str);
		
		echo "\n---\n";
		
		foreach(explode("\n", $str) as $line)
			echo $prepend . ': ' . $line . "\n";
		
		echo "---\n";
	}
}