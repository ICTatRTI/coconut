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
*	@version 0.1
*/

/**
*	Http cookie compiler - compiling (creating) cookie strings.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Cookie
*	@version 0.1
*/
class Http_Cookie_Compiler
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.1';
	
	/**
	*	Generate request 'Cookie' header value from cookies
	*	@note If empty array provided, output will be null length string
	*	@param array $cookies Array with cookie(s) object(s) ([... => Http_Cookie, ... => Http_Cookie])
	*	@return string Request header 'Cookie' value
	*/
	public static function compileCookieHeader(array $cookies)
	{
		$o = '';
		foreach($cookies as $cookie)
		{
			if($o)
				$o .= '; ';
			$o .= $cookie->name . '=' . $cookie->value;
		}
		return $o;
	}
}