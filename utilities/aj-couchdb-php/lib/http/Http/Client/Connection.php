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
*	Http client - connection helper.
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Client
*	@version 0.5
*/
class Http_Client_Connection
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5';
	
	/**
	*	If we sending next request to same server, wait a while because we dont want to abuse it.
	*	This is a delta in a seconds.
	*	@var float
	*/
	const WRITE_WAIT = 0.01;
	
	/**
	*	Socket object
	*	@var Socket_Interface
	*/
	protected $_socket = null;
	
	/**
	*	Socket network address
	*	@var string
	*/
	protected $_addr = '';
	
	/**
	*	Socket network port
	*	@var int
	*/
	protected $_port = 0;
	
	/**
	*	Socket network transport
	*	@var string
	*/
	protected $_transport = '';
	
	/**
	*	Socket write counter
	*	@var int
	*/
	protected $_writeCount = 0;
	
	/**
	*	Params from 'Keep-Alive' header (set by Http_Response_Parser)
	*	@var array
	*/
	protected $_httpKeepAliveParams = [];
	
	/**
	*	True when socket was written and wasnt readed yet, false otherwise
	*	@var bool
	*/
	protected $_unreadAfterWrite = false;
	
	/**
	*	Create a connection
	*	@param string $addr Network adress
	*	@param int $port Network port
	*	@param string $transport Network transport - optional
	*	@return void
	*/
	public function __construct($addr, $port, $transport = 'tcp')
	{
		$this->_addr = $addr;
		$this->_port = $port;
		$this->_transport = $transport;
		$this->_socket = Socket_Factory::getClient(null, ['addr' => $addr, 'port' => $port, 'transport' => $transport]);
	}
	
	/**
	*	Destroy socket and create new one
	*	@return self
	*/
	public function reconnect()
	{
		$this->_socket = null; // right side in php expression is executed as first and we must destroy socket object before to close system socket
		$this->_socket = Socket_Factory::getClient(null, ['addr' => $this->_addr, 'port' => $this->_port, 'transport' => $this->_transport]);
		$this->_writeCount = 0;
		$this->_unreadAfterWrite = false;
		return $this;
	}
	
	/**
	*	Write into socket.
	*	Socket will be opened when needed
	*	@param string $data Data to write into socket
	*	@return self
	*/
	public function write($data)
	{
		$delta = $this->_socket->getLastWriteTime() - (microtime(true) - static::WRITE_WAIT);
		if($delta > 0)
			usleep($delta * 1000000);
		
		$this->_socket->write($data);
		$this->_unreadAfterWrite = true;
		++$this->_writeCount;
		
		if(isset($this->_httpKeepAliveParams['max']))
			--$this->_httpKeepAliveParams['max'];
		
		return $this;
	}
	
	/**
	*	Read data from socket
	*	@param int|null $bytes Limit read to bytes
	*	@param int|null $timeout Time limit for read in seconds or null to use default
	*	@return string Readed data
	*/
	public function read($bytes = null, $timeout = null)
	{
		$o = $this->_socket->read($bytes, $timeout);
		$this->_unreadAfterWrite = false;
		return $o;
	}
	
	/**
	*	Read data from socket until comes new line or buffer will be filled to given bytes
	*	@param int|null $bytes limit read to bytes
	*	@param int|null $timeout time limit for read in seconds or null to use default
	*	@return string Readed data
	*/
	public function readLine($bytes = null, $timeout = null)
	{
		$o = $this->_socket->readLine($bytes, $timeout);
		$this->_unreadAfterWrite = false;
		return $o;
	}
	
	/**
	*	Get used network transport
	*	@return string Network transport
	*/
	public function getTransport()
	{
		return $this->_transport;
	}
	
	/**
	*	Get last socket read time.
	*	Exacly same like getTimeOfLastRead().
	*	@return float Last time when readed on socket or 0.0 when not readed yet (0.0 also after connection close)
	*/
	public function getLastReadTime()
	{
		return $this->_socket->getLastReadTime();
	}
	
	/**
	*	Get last socket read time.
	*	Exacly same like getLastReadTime().
	*	@return float Last time when readed on socket or 0.0 when not readed yet (0.0 also after connection close)
	*/
	public function getTimeOfLastRead()
	{
		return $this->_socket->getLastReadTime();
	}
	
	/**
	*	Get last socket write time.
	*	Exacly same like getTimeOfLastWrite().
	*	@return float Last time when wrote on socket or 0.0 when not writed yet (0.0 also after connection close)
	*/
	public function getLastWriteTime()
	{
		return $this->_socket->getLastWriteTime();
	}
	
	/**
	*	Get last socket write time.
	*	Exacly same like getLastWriteTime().
	*	@return float Last time when wrote on socket or 0.0 when not writed yet (0.0 also after connection close)
	*/
	public function getTimeOfLastWrite()
	{
		return $this->_socket->getLastWriteTime();
	}
	
	/**
	*	Get socket write count
	*	@return int Times socket was writed (0 when wasn't)
	*/
	public function getWriteCount()
	{
		return $this->_writeCount;
	}
	
	/**
	*	Get unix time of last socket creation
	*	@return float Unix time of last socket creation
	*/
	public function getConnectionTime()
	{
		return $this->_socket->_connectionTime;
	}
	
	/**
	*	Set http keep-alive params from 'Keep-Alive' header.
	*	@param array $params Keep-alive params
	*	@return self
	*/
	public function setHttpKeepAliveParams(array $params)
	{
		$this->_httpKeepAliveParams = $params;
		return $this;
	}
	
	/**
	*	Check if http connection is write-able.
	*	This methods checks params from 'Keep-Alive' header (if available) and return of stream_select() (if available).
	*	@return bool true if socket looks writeable (or not opened yet), false otherwise
	*/
	public function isWriteAble()
	{
		if(!$this->_socket->isConnected())
			return true; // connection will be established after write() call
		
		$settings = Http_Settings::getInstance();
		
		if(isset($this->_httpKeepAliveParams['max']))
		{
			if(!$this->_httpKeepAliveParams['max'])
				return false;
		}
		else
		{
			$keepAliveDefaultMax = $settings->keepAliveDefaultMax;
			if(($this->_unreadAfterWrite || $settings->keepAliveDefaultTimeOut_useEvenWhenSocketWasReaded) && $keepAliveDefaultMax > 0)
			{
				if($this->_writeCount >= $keepAliveDefaultMax)
					return false;
			}
		}
		
		$connectionTime = $this->_socket->getConnectionTime();
		
		if(isset($this->_httpKeepAliveParams['timeout']))
		{
			if(microtime(true) + $settings->keepAliveTimeOutOffset - $connectionTime > $this->_httpKeepAliveParams['timeout'])
				return false;
		}
		else
		{
			$keepAliveDefaultTimeOut = $settings->keepAliveDefaultTimeOut;
			if(($this->_unreadAfterWrite || $settings->keepAliveDefaultMax_useEvenWhenSocketWasReaded) && $keepAliveDefaultTimeOut > 0)
			{
				if(microtime(true) - $connectionTime > $keepAliveDefaultTimeOut)
					return false;
			}
		}
		
		if(method_exists($this->_socket, 'stream_select') && $this->_socket->stream_select(false, true, false) < 1)
			return false;

		return true;
	}
	
	/**
	*	Forward setBlocking() into socket adapter
	*	@param int|null $v 0 or 1 or null to use default
	*	@return self
	*/
	public function setBlocking($v = null)
	{
		$this->_socket->setBlocking($v);
		return $this;
	}
}