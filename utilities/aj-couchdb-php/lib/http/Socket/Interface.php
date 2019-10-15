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
*	@version 0.5
*/

/**
*	Socket interface
*	@category Lib
*	@package Socket
*	@version 0.5
*/
interface Socket_Interface
{
	/**
	*	Constructor - set necessary params
	*	@param string $addr Network address
	*	@param int $port Tcp/udp port
	*	@param string $transport Optional: network transport protocol (tcp/udp/ssl/tls etc)
	*	@param int|null $blocking Optional: change deafult blocking mode (0/1)
	*	@param int $timeout Optional: timeout for opening connection/reading in seconds
	*	@return void
	*/
	public function __construct($addr, $port, $transport = null, $blocking = null, $timeout = null);
	
	/**
	*	Connect
	*	@return self
	*/
	public function connect();
	
	/**
	*	Close connection
	*	@return self
	*/
	public function close();
	
	/**
	*	Write into socket
	*	@param string $str Data to write
	*	@return self
	*/
	public function write($str);
	
	/**
	*	Read data from socket
	*	@param int|null $bytes Limit read to bytes or read all when null or 0
	*	@param int|null $timeout Time limit for read in seconds or null to use default - setTimeout()
	*	@param bool $readUntilEndOf If set to true, then reading will be stopped on end of socket data
	*	@return string Readed data
	*/
	public function read($bytes = null, $timeout = null, $readUntilEndOf = false);
	
	/**
	*	Read data from socket until buffer is limited to given bytes or to the end of line.
	*	In other words: reading is limited to (optionally) bytes or a end of line, whatever comes first.
	*	@param int|null $bytes Limit read to bytes, give 0 or null to read until new line only
	*	@param int|null $timeout Time limit for read in seconds, null to use default (setTimeout()), 0 to disable
	*	@param bool $readUntilEndOf If set to true, then reading will be stopped on end of socket data
	*	@return string Readed data
	*/
	public function readLine($bytes = null, $timeout = null, $readUntilEndOf = false);
	
	/**
	*	Set default timeout for connecting, reading and writing
	*	@param int|float|null $v Time in seconds or null to use class default
	*	@return self
	*/
	public function setTimeout($v = null);
	
	/**
	*	Get current timeout setting
	*	@return float|int Timeout in seconds
	*/
	public function getTimeout();
	
	/**
	*	Set blocking mode (0/1)
	*	@param int|null $v 0 or 1 or null to use default
	*	@return self
	*/
	public function setBlocking($v = null);
	
	/**
	*	Get unix time of last socket creation
	*	@return int Unix time of last socket creation
	*/
	public function getConnectionTime();
	
	/**
	*	Destructor to close connection
	*	@return void
	*/
	public function __destruct();
	
	/**
	*	Check if we are connected right now
	*	@return bool True if connection is alive, false otherwise
	*/
	public function isConnected();
	
	/**
	*	Get last socket read time.
	*	Exacly same like getTimeOfLastRead().
	*	@return float Last time of socket reading or 0.0 when not readed yet (0.0 also after connection close)
	*/
	public function getLastReadTime();
	
	/**
	*	Get last socket read time.
	*	Exacly same like getLastReadTime().
	*	@return float Last time of socket reading or 0.0 when not readed yet (0.0 also after connection close)
	*/
	public function getTimeOfLastRead();
	
	/**
	*	Get last socket write time.
	*	Exacly same like getTimeOfLastWrite().
	*	@return float Last time of socket writing or 0.0 when not writed yet (0.0 also after connection close)
	*/
	public function getLastWriteTime();
	
	/**
	*	Get last socket write time.
	*	Exacly same like getLastWriteTime().
	*	@return float Last time of socket writing or 0.0 when not writed yet (0.0 also after connection close)
	*/
	public function getTimeOfLastWrite();
}