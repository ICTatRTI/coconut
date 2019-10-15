<?php
/**
*	String library (part of)
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
*	@package String
*	@copyright Copyright (c) 2009-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.1.2
*/

/**
*	Part of string library
*	@category Lib
*	@package String
*	@version 0.1.2
*/
class String
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.1.2';
	
	/**
	*	Generate random string from charset
	*	@param int $howLong Length of string to generate
	*	@param string|null $chars Optional charset, null to use default 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'
	*	@return string
	*	@uses mt_rand()
	*/
	public static function randomString($howLong, $chars = null)
	{
		if($chars === null)
			$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		
		$o = '';
		$t = strlen($chars) - 1;
		for($i = 1; $i <= $howLong; $i++)
			$o .= $chars{mt_rand(0, $t)};
		return $o;
	}
}