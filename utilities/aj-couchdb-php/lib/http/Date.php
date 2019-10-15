<?php
/**
*	Date library
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
*	@package Date
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.5
*/

/**
*	Date library
*	@category Lib
*	@package Date
*	@version 0.5
*/
class Date
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	@var array
	*/
	protected static $_monthToNum =
	[
		'jan' => 1,
		'feb' => 2,
		'mar' => 3,
		'apr' => 4,
		'may' => 5,
		'jun' => 6,
		'jul' => 7,
		'aug' => 8,
		'sep' => 9,
		'oct' => 10,
		'nov' => 11,
		'dec' => 12,
		'january' => 1,
		'february' => 2,
		'march' => 3,
		'april' => 4,
		// may is already given
		'june' => 6,
		'july' => 7,
		'august' => 8,
		'september' => 9,
		'october' => 10,
		'november' => 11,
		'december' => 12
	];

	/**
	*	Convert month name or short month name to numerical.
	*	First arg is case insensitive (may be november, November, NOVEMBER, nOvember etc...).
	*	@param string $string Month in english
	*	@param bool $leadingZero True to add leading zero
	*	@return int|string Numerically month, string when second arg is true and we are between 1 and 9 (with leading zero)
	*	@throws Date_Exception When not found or when given something else than string
	*/
	public static function monthToNum($string, $leadingZero = false)
	{
		if(!is_string($string))
			throw new Date_Exception('String only');
		
		$string = strtolower($string);
		
		if(!isset(static::$_monthToNum[$string]))
			throw new Date_Exception($string . ' looks like not a month...');
		
		if(!$leadingZero || static::$_monthToNum[$string] > 9)
			return static::$_monthToNum[$string];
		
		return '0' . static::$_monthToNum[$string];
	}
	
	/**
	*	Get time diff from given timezone name to the GMT
	*	@link http://tools.ietf.org/html/rfc822#section-5.1
	*	@param string $string timezone name
	*	@return int diff to the gmt in seconds
	*	@todo UTC
	*/
	public static function getTimeZoneDiff($string)
	{
		switch($string) 
		{
			case 'GMT': // Universal Time
			case 'UT': // Universal Time (UT != UTC)
				//return 0 * 3600;
				return 0;
			break;
			
			case 'EST':// Eastern: - 5
				//return -5 * 3600;
				return -18000;
			break;
			
			case 'EDT':// Eastern: - 4
				//return -4 * 3600;
				return -14400;
			break;
			
			case 'CST':// Central: - 6
				//return -6 * 3600;
				return -21600;
			break;
			
			case 'CDT':// Central: - 5
				//return -5 * 3600;
				return -18000;
			break;
			
			case 'MST':// Mountain: - 7
				//return -7 * 3600;
				return -25200;
			break;
			
			case 'MDT':// Mountain: - 6
				//return -6 * 3600;
				return -25200;
			break;
			
			case 'PST':// Pacific: -8
				//return -8 * 3600;
				return -28800;
			break;
			
			case 'PDT':// Pacific: -7
				//return -7 * 3600;
				return -25200;
			break;
			
			// Military (letters, J is not used):
			
			case 'A':
				//return -1 * 3600;
				return -3600;
			break;
			
			case 'B':
				//return -2 * 3600;
				return -7200;
			break;
			
			case 'C':
				//return -3 * 3600;
				return -10800;
			break;
			
			case 'D':
				//return -4 * 3600;
				return -14400;
			break;
			
			case 'E':
				//return -5 * 3600;
				return -18000;
			break;
			
			case 'F':
				//return -6 * 3600;
				return -21600;
			break;
			
			case 'G':
				//return -7 * 3600;
				return -25200;
			break;
			
			case 'H':
				//return -8 * 3600;
				return -28800;
			break;
			
			case 'I':
				//return -9 * 3600;
				return -32400;
			break;
			
			case 'K':
				//return -10 * 3600;
				return -36000;
			break;
			
			case 'L':
				//return -11 * 3600;
				return -39600;
			break;
			
			case 'M':
				//return -12 * 3600;
				return -43200;
			break;
			
			case 'N':
				//return 1 * 3600;
				return 3600;
			break;
			
			case 'O':
				//return 2 * 3600;
				return 7200;
			break;
			
			case 'P':
				//return 3 * 3600;
				return 10800;
			break;
			
			case 'Q':
				//return 4 * 3600;
				return 14400;
			break;
			
			case 'R':
				//return 5 * 3600;
				return 18000;
			break;
			
			case 'S':
				//return 6 * 3600;
				return 21600;
			break;
			
			case 'T':
				//return 7 * 3600;
				return 25200;
			break;
			
			case 'U':
				//return 8 * 3600;
				return 28800;
			break;
			
			case 'V':
				//return 9 * 3600;
				return 32400;
			break;
			
			case 'W':
				//return 10 * 3600;
				return 3600;
			break;
			
			case 'X':
				//return 11 * 3600;
				return 39600;
			break;
			
			case 'Y':
				//return 12 * 3600;
				return 43200;
			break;
			
			case 'Z':
				//return 0 * 3600;
				return 0;
			break;
			
			default: 
				throw new Date_Exception('Bad timezone name'); 
		}
	}
	
	/**
	*	Parse date in RFC 822 format and some modifications of that.
	*	Works both with valid 'dd mm yy' and with changed to 'dd-mm-yy' (RFC 1123, RFC 1036).
	*	Works with year both 2-digits and 4-digits (in 2-digits will be converted in this way: 10 -> 2010).
	*	Idea borrowed from http://phpsnips.com/130/Parse-RFC822-date#.U1VG3XV_ux4
	*	@note ctime is not supported
	*	@param string $string String with date in RFC 822 format
	*	@return int Unix timestamp in selected timezone
	*	@link http://tools.ietf.org/html/rfc822#section-5.1
	*	@throws Date_Exception
	*/
	public static function parseRfc822Date($string)
	{
		if(!is_string($string))
			throw new Date_Exception('String only');
		
		if(!preg_match('/^(?:(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun), )?(?P<day>\d{1,2})[\040|-](?P<month>Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[\040|-](?P<year>\d{4}) (?P<hour>\d{2}):(?P<minute>\d{2})(?::(?P<second>\d{2}))?[\040]{0,1}(?P<timezone>UT|GMT|EST|EDT|CST|CDT|MST|MDT|PST|PDT|[A-IK-Z]){0,1}[\040]{0,1}(?P<timezone_extra_diff>[+-]\d{4}|[+-]\d{1,2}){0,1}$/', $string, $matches))
			throw new Date_Exception("'$string' is not a valid Rfc822Date format"); 
		
		//if($dt = DateTime::createFromFormat(DateTime::RFC2822, str_replace('-', ' ', $string)))
		//	return $dt->getTimestamp();
		
		if(!isset($matches['timezone']))
			$matches['timezone'] = 'GMT';
		
		$timeDiff = static::getTimeZoneDiff($matches['timezone']);
		
		if(isset($matches['timezone_extra_diff']))
		{
			$t = substr($matches['timezone_extra_diff'], 1);
			$t2 = strlen($t);
			
			if($t2 < 4) // 1 or 2
				$t = (int)$t * 3600;
			else // 4
				$t = ((int)substr($t, 0, 2) * 3600) + ((int)substr($t, 2, 2) * 60);
			
			if($matches['timezone_extra_diff']{0} == '-')
				$timeDiff -= $t;
			else
				$timeDiff += $t;
		}
		
		if(strlen($matches['year']) == 2)
			$matches['year'] = '20' . $matches['year']; // will earth still exist in 2100?
		
		if(false === ($o = gmmktime((int)$matches['hour'], (int)$matches['minute'], (int)$matches['second'], static::monthToNum($matches['month']), (int)$matches['day'], (int)$matches['year']) - $matches['timezone']))
			throw new Date_Exception('gmmktime() failed, most propably given date is wrong'); 
		
		return $o - $timeDiff;
	}
	
	/**
	*	Convert unix timestamp to RFC 1036 format (exacly: Wdy, DD Mon YY HH:MM:SS +0000)
	*	@param int $time Unix timestamp
	*	@return string Date-time in RFC 1036 format
	*	@link http://tools.ietf.org/html/rfc1036
	*	@link http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html
	*	@link http://www.ietf.org/rfc/rfc1123.txt
	*	@link http://www.php.net/manual/en/class.datetime.php#datetime.constants.rfc1036
	*/
	public static function timestampToRfc1036($time)
	{
		return (new DateTime())->setTimestamp($time)->format(DateTime::RFC1036);
	}
	
	/**
	*	Convert unix timestamp to http cookie format (example: Monday, 15-Aug-05 15:52:01 UTC)
	*	@param int $time Unix timestamp
	*	@return string Date-time in http cookie format
	*	@link http://php.net/manual/en/class.datetime.php#datetime.constants.cookie
	*/
	public static function timestampToCookie($time)
	{
		return (new DateTime())->setTimestamp($time)->format(DateTime::COOKIE);
	}
}