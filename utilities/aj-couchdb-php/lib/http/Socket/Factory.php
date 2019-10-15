<?php
/**
*	Socket
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
*	@package Socket
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.4
*/

/**
*	Socket factory
*	@category Lib
*	@package Socket
*	@version 0.4
*/
class Socket_Factory
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.4';
	
	/**
	*	stream_socket_*
	*	@var int
	*/
	const SOCKET_TYPE_STREAM = 1;
	
	/**
	*	Get socket client
	*	@param int $type Bit-wise socket type (adapter) based on this class constants. Can be by-passed by null. Not used right now (TODO).
	*	@param array $params Array with params for adapter constructor. Required keys: addr, port. Optional keys: transport, blocking, timeout.
	*	@return Socket_Interface
	*/
	public static function getClient($type = self::SOCKET_TYPE_STREAM, array $params)
	{
		if($type === null)
			$type = static::SOCKET_TYPE_STREAM;
		return new Socket_Client_Stream($params['addr'], $params['port'], isset($params['transport']) ? $params['transport'] : null, isset($params['blocking']) ? $params['blocking'] : null, isset($params['timeout']) ? $params['timeout'] : null);
	}
}