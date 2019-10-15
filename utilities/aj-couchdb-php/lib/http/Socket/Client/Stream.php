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
*	@version 0.5.1
*/

/**
*	Socket client adapter using stream_socket_client()
*	@category Lib
*	@package Socket
*	@version 0.5.1
*	@TODO context as a param
*/
class Socket_Client_Stream implements Socket_Interface
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Default timeout for stream_socket_*, read() readLine() and write()
	*	@var int
	*/
	const DEFAULT_SOCKET_TIMEOUT = 60;

	/**
	*	@var resource|false
	*/
	protected $_fp = false;
	
	/**
	*	Network transport
	*	@var string
	*/
	protected $_transport = '';
	
	/**
	*	Network address
	*	@var string
	*/
	protected $_addr = '';
	
	/**
	*	Network port
	*	@var int
	*/
	protected $_port = 0;
	
	/**
	*	Socket functions blocking
	*	@var int
	*/
	protected $_blocking = 0;
	
	/**
	*	Connecting/reading/writing timeout
	*	@var int|float
	*	@ignore
	*/
	protected $_timeout = self::DEFAULT_SOCKET_TIMEOUT;
	
	/**
	*	Time when socket was opened (connected)
	*	@var float
	*/
	protected $_connectionTime = 0.0;
	
	/**
	*	Socket last read time
	*	@var float
	*/
	protected $_lastRead = 0.0;
	
	/**
	*	Socket last write time
	*	@var float
	*/
	protected $_lastWrite = 0.0;
	
	/**
	*	Constructor - set necessary params
	*	@param string $addr Network address
	*	@param int $port Tcp/udp port
	*	@param string $transport Optional: network transport protocol (tcp/udp/ssl/tls etc)
	*	@param int|null $blocking Optional: change deafult blocking mode (0/1)
	*	@param int|float $timeout Optional: timeout for opening connection/reading in seconds
	*	@uses setBlocking()
	*	@uses setTimeout()
	*	@throws Socket_Exception
	*/
	public function __construct($addr, $port, $transport = null, $blocking = null, $timeout = null)
	{
		if($addr == '')
			throw new Socket_Exception('Empty address');
		if(!is_int($port) || $port < 1 || $port > 65535)
			throw new Socket_Exception('Bad port');
		$this->_addr = $addr;
		$this->_port = $port;
		if($transport === null)
			$transport = 'tcp';
		$this->_transport = $transport;
		$this->setBlocking($blocking);
		$this->setTimeout($timeout);
	}
	
	/**
	*	Connect
	*	@return self
	*	@throws Socket_Exception
	*/
	public function connect()
	{
		if($this->_fp)
			throw new Socket_Exception('Already connected');
		
		$context = stream_context_create();
		if($this->_transport == 'ssl')
		{
			stream_context_set_option($context, 'ssl', 'verify_host', true);
			//stream_context_set_option($context, 'ssl', 'cafile', $cert_file);
			//stream_context_set_option($context, 'ssl', 'verify_peer', true);
			stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
		}
		
		if(!$this->_fp = stream_socket_client
		(
			$this->_transport
			. '://' .
			$this->_addr
			. ':' .
			$this->_port,
			$err, $errstr, $this->_timeout, STREAM_CLIENT_CONNECT, $context
		))
			throw new Socket_Exception('stream_socket_client failed with err: ' . $err . ' errstr: ' . $errstr);
			
		$this->_connectionTime = microtime(true);
		$this->setBlocking();
		stream_set_timeout($this->_fp, (int)$this->_timeout);
		return $this;
	}
	
	/**
	*	Close connection
	*	@return self
	*/
	public function close()
	{
		if($this->_fp)
			fclose($this->_fp);
		$this->_fp = false;
		$this->_connectionTime = 0.0;
		$this->_lastRead = 0.0;
		$this->_lastWrite = 0.0;
		return $this;
	}
	
	/**
	*	Write into socket
	*	@param string $str Data to write
	*	@param int|null $timeout Socket write timeout in seconds, null to use adapter default, 0 to disable
	*	@return self
	*/
	public function write($str, $timeout = null)
	{
		if(!is_string($str))
			throw new Socket_Exception('String only');
		
		if(!$this->_fp)
			$this->connect();

		$oldEncoding = null;
		
		if(extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload')))
		{
			$oldEncoding = mb_internal_encoding();
			if(false === mb_internal_encoding('8bit'))
				trigger_error('mb_internal_encoding change to 8bit failed', E_USER_WARNING);
		}
		
		if($timeout === null)
			$timeout = $this->_timeout;
		
		$timeout = max($timeout, 1);
		
		$length = strlen($str);
		$writted = 0;
		$time_start = microtime(true);
		while(($writted < $length))
		{
			if($timeout && $this->_lastWrite - $timeout > $time_start)
			{
                $t=$this->_lastWrite;
				$t = $t - $time_start;
				throw new Socket_Exception("Write timed out ($t/$timeout)");
			}
			
			$ss = $this->stream_select(false, true, false, 0, 2000);
			if($ss < 1)
			{
				usleep(1000);
				continue;
			}
			
			$fw = fwrite($this->_fp, substr($str, $writted));
			$this->_lastWrite = microtime(true);
			
			if($fw !== false && $fw != 0)
				$writted += $fw;
			else
				usleep(2000);
		}
		
		if(!empty($oldEncoding))
			mb_internal_encoding($oldEncoding);
		
		return $this;
	}
	
	/**
	*	Read data from socket
	*	@param int|null $bytes Limit read to bytes or use stream_get_contents when null or 0
	*	@param int|null $timeout Time limit for read in seconds, null to use default (setTimeout()), 0 to disable
	*	@param bool $readUntilEndOf If set to true, then reading will be stopped on end of socket data
	*	@return string readed data
	*	@throws Socket_Exception
	*/
	public function read($bytes = null, $timeout = null, $readUntilEndOf = false)
	{
		if(!$this->_fp)
			throw new Socket_Exception('Not connected!');
		
		if($bytes !== null && ($bytes != (int)$bytes || $bytes < 0))
			throw new Socket_Exception('Wrong length');
		
		$oldEncoding = null;
		
		if(extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload')))
		{
			$oldEncoding = mb_internal_encoding();
			if(false === mb_internal_encoding('8bit'))
				trigger_error('mb_internal_encoding change to 8bit failed', E_USER_WARNING);
		}
		
		if($timeout === null)
			$timeout = $this->_timeout;
		
		if(!$bytes)
			$o = stream_get_contents($this->_fp);
		else
		{
			$o = '';
			$time_start = microtime(true);
			$bytesLeft = $bytes;
			while(true)
			{
				if($timeout > 0)
				{
					if($this->_lastRead - $timeout > $time_start)
					{
                        $t=$this->_lastRead;
						$t = $t - $time_start;
						throw new Socket_Exception("Reading timed out ($t/$timeout)");
					}
				}
				
				$data = fread($this->_fp, $bytesLeft);
				
				$this->_lastRead = microtime(true);
				
				if(!is_string($data))
				{
					usleep(500);
					continue;
				}
				
				$o .= $data;
				
				$bytesLeft -= strlen($data);
				
				if($bytesLeft <= 0)
					break;
				
				if($readUntilEndOf) // we dont need to check feof() because fread stops on end of socket data
					break;
			}
			
		}
		
		if(!empty($oldEncoding))
			mb_internal_encoding($oldEncoding);
		
		return $o;
	}
	
	/**
	*	Read data from socket until buffer is limited to given bytes or to the end of line.
	*	In other words: reading is limited to (optionally) bytes or a end of line, whatever comes first.
	*	@param int|null $bytes Limit read to bytes, give 0 or null to read until new line only
	*	@param int|null $timeout Time limit for read in seconds, null to use default (setTimeout()), 0 to disable
	*	@param bool $readUntilEndOf If set to true, then reading will be stopped on end of socket data
	*	@return string readed data
	*	@throws Socket_Exception
	*/
	public function readLine($bytes = null, $timeout = null, $readUntilEndOf = false)
	{
		if(!$this->_fp)
			throw new Socket_Exception('Not connected!');
		
		if($bytes !== null && ($bytes != (int)$bytes || $bytes < 0))
			throw new Socket_Exception('Wrong length');
		
		$oldEncoding = null;
		
		if(extension_loaded('mbstring') && (2 & ini_get('mbstring.func_overload')))
		{
			$oldEncoding = mb_internal_encoding();
			if(false === mb_internal_encoding('8bit'))
				trigger_error('mb_internal_encoding change to 8bit failed', E_USER_WARNING);
		}
		
		if($timeout === null)
			$timeout = $this->_timeout;
		
		$o = '';
		$bytesLeft = $bytes;
		$time_start = microtime(true);
		while(true)
		{
			if($timeout > 0)
			{
				if($this->_lastRead - $timeout > $time_start)
				{
					$t = $this->_lastRead - $time_start;
					throw new Socket_Exception("Reading timed out ($t/$timeout)");
				}
			}
			
			if($bytes)
			{
				$t = fgets($this->_fp, $bytesLeft);
				$this->_lastRead = microtime(true);
				if(is_string($t))
				{
					$o .= $t;
					$bytesLeft -= strlen($data);
					if($bytesLeft <= 0)
						return $o;
				}
				else
					usleep(500);
			}
			else
			{
				$t = fgets($this->_fp);
				$this->_lastRead = microtime(true);
				if(is_string($t))
					$o .= $t;
				else
					usleep(500);
			}
			
			if($readUntilEndOf && feof($this->_fp))
				return $o;
			
			if(substr($o, -1) == "\n")
				return rtrim($o, "\r\n");
		}
		
		if(!empty($oldEncoding))
			mb_internal_encoding($oldEncoding);
		
		return $o;
	}
	
	/**
	*	Set default timeout for connecting, reading and writing
	*	@param int|float|null $v Time in seconds or null to use class default
	*	@return self
	*	@see getTimeout()
	*/
	public function setTimeout($v = null)
	{
		if($v === null)
			$v = static::DEFAULT_SOCKET_TIMEOUT;
		
		if($v < 1)
		{
			trigger_error('Tried to set timeout lower than one second. Changing it to 1 second'. E_USER_WARNING);
			$v = 1;
		}
		
		$this->_timeout = $v;
		if($this->_fp)
			stream_set_timeout($this->_fp, (int)$v);
		return $this;
	}
	
	/**
	*	Get current timeout setting
	*	@return float|int Timeout in seconds
	*	@see setTimeout()
	*/
	public function getTimeout()
	{
		return $this->_timeout;
	}
	
	/**
	*	Set blocking mode (0/1).
	*	Can be change any time, not deppendig if we are connected or not
	*	@param int|null $v 0 or 1 or null to use default
	*	@return self
	*	@throws Socket_Exception
	*/
	public function setBlocking($v = null)
	{
		if($v === null)
			$v = $this->_blocking;
		$this->_blocking = $v;
		if(!$this->_fp)
			return $this;
		if(!stream_set_blocking($this->_fp, $v))
			throw new Socket_Exception('stream_set_blocking() failed');
		return $this;
	}
	
	/**
	*	Get unix time of last socket creation
	*	@return float Unix time of last socket creation
	*/
	public function getConnectionTime()
	{
		return $this->_connectionTime;
	}
	
	/**
	*	Call stream_select() on socket stream
	*	@param bool $read Set to true to check if we can read
	*	@param bool $write Set to true to check if we can write
	*	@param bool $except Set to true to check if we can read except
	*	@param int $tv_sec tv_sec
	*	@param int $tv_usec tv_usec
	*	@return int|false see http://en.php.net/manual/en/function.stream-select.php
	*	@link http://en.php.net/manual/en/function.stream-select.php
	*	@throws Socket_Exception
	*/
	public function stream_select($read = false, $write = true, $except = false, $tv_sec = 0, $tv_usec = 200)
	{
		if(!$this->_fp)
			throw new Socket_Exception('Cant call stream_select if we are not connected, maybe try to call connect() or write() before?');
		$read = $read ? [$this->_fp] : [];
		$write = $write ? [$this->_fp] : [];
		$except = $except ? [$this->_fp] : [];
		return stream_select($read, $write, $except, $tv_sec, $tv_usec);
	}
	
	/**
	*	Destructor to close connection
	*	@return void
	*/
	public function __destruct()
	{
		$this->close();
	}
	
	/**
	*	Check if we are connected right now
	*	@return bool true if connection is alive, false otherwise
	*/
	public function isConnected()
	{
		return (bool)$this->_fp;
	}
	
	/**
	*	Get last socket read time.
	*	Exacly same like getTimeOfLastRead().
	*	@return float Last time of socket reading or 0.0 when not readed yet (0.0 also after connection close)
	*/
	public function getLastReadTime()
	{
		return $this->_lastRead;
	}
	
	/**
	*	Get last socket read time.
	*	Exacly same like getLastReadTime().
	*	@return float Last time of socket reading or 0.0 when not readed yet (0.0 also after connection close)
	*/
	public function getTimeOfLastRead()
	{
		return $this->_lastRead;
	}
	
	/**
	*	Get last socket write time.
	*	Exacly same like getTimeOfLastWrite().
	*	@return float Last time of socket writing or 0.0 when not writed yet (0.0 also after connection close)
	*/
	public function getLastWriteTime()
	{
		return $this->_lastWrite;
	}
	
	/**
	*	Get last socket write time.
	*	Exacly same like getLastWriteTime().
	*	@return float Last time of socket writing or 0.0 when not writed yet (0.0 also after connection close)
	*/
	public function getTimeOfLastWrite()
	{
		return $this->_lastWrite;
	}
}