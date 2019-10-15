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
*	@version 0.5.1
*/

/**
*	Http client - connections handler (helper).
*	See included howto.
*	@see Http_Request
*	@category Http Client
*	@package Http Client
*	@version 0.5.1
*/
class Http_Client_Connection_Handler
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.5.1';
	
	/**
	*	Singleton instance
	*	@var Http_Client
	*/
	protected static $_instance = null;

	/**
	*	Http client connections
	*	@var array
	*/
	protected $_connections = [];
	
	/**
	*	Current working connection
	*	@var Http_Client_Connection
	*/
	protected $_current = null;
	
	/**
	*	Address of current working connection
	*	@var string
	*/
	protected $_addr_of_current = '';
	
	/**
	*	Port of current working connection
	*	@var int
	*/
	protected $_port_of_current = 0;
	
	/**
	*	@see getInstance()
	*	@return void
	*/
	protected function __construct()
	{
	}
	
	/**
	*	Get singleton instance
	*	@return self
	*/
	public static function getInstance()
	{
		if(self::$_instance)
			return self::$_instance;
		return self::$_instance = new self;
	}
	
	/**
	*	Create a connection
	*	@param string $addr Network adress
	*	@param int $port Network port
	*	@param string $transport Network transport - optional
	*	@return self
	*	@see select()
	*	@see getCurrent()
	*	@throws Http_Client_Connection_Handler_Exception
	*/
	public function create($addr, $port, $transport = 'tcp')
	{
		if(isset($this->_connections[$addr]) && isset($this->_connections[$addr][$port]))
			throw new Http_Client_Connection_Handler_Exception('You must destroy existing same connection before - destroy() or destroyCurrent()'); // sorry, there must be some order :)
		
		$this->_addr_of_current = $addr;
		$this->_port_of_current = $port;
		
		if(!isset($this->_connections[$addr]))
			$this->_connections[$addr] = [];
		
		$this->_connections[$addr][$port] = $this->_current = new Http_Client_Connection($addr, $port, $transport);
		
		return $this;
	}
	
	/**
	*	Set stored connection as current
	*	@param string $addr Network adress
	*	@param string $port Network port
	*	@return self
	*	@see create()
	*	@see getCurrent()
	*	@see isStored()
	*	@see destroy()
	*	@see destroyCurrent()
	*	@throws Http_Client_Connection_Handler_Exception
	*/
	public function select($addr, $port)
	{
		if(!isset($this->_connections[$addr]) || !isset($this->_connections[$addr][$port]))
			throw new Http_Client_Connection_Handler_Exception('Tried to get unexisted connection');
		
		$this->_current = $this->_connections[$addr][$port];
		$this->_addr_of_current = $addr;
		$this->_port_of_current = $port;
		
		return $this;
	}
	
	/**
	*	Check if we have stored connection object
	*	@param string $addr Network adress
	*	@param string $port Network port
	*	@return bool True if we have connection object with given address and port, false otherwise
	*	@see getCurrent()
	*	@see unset()
	*	@see create()
	*/
	public function isStored($addr, $port)
	{
		return isset($this->_connections[$addr]) && isset($this->_connections[$addr][$port]);
	}
	
	/**
	*	Get current connection object
	*	@return Http_Client_Connection connection object
	*	@see select()
	*	@see unset()
	*	@see destroy()
	*	@see destroyCurrent()
	*	@throws Http_Client_Connection_Handler_Exception
	*/
	public function getCurrent()
	{
		if(!$this->_current)
			throw new Http_Client_Connection_Handler_Exception('Current connection is not set (not selected before by select() or destroyed by destroy())');
		return $this->_current;
	}
	
	/**
	*	Destroy connection object (if you need new, You must create it by create() after this call)
	*	@param string $addr Network adress
	*	@param string $port Network port
	*	@return self
	*	@see select()
	*	@see unset()
	*	@see destroyCurrent()
	*	@throws Http_Client_Connection_Handler_Exception
	*/
	public function destroy($addr, $port)
	{
		if(!isset($this->_connections[$addr]) || !isset($this->_connections[$addr][$port]))
			throw new Http_Client_Connection_Handler_Exception('Tried to destroy unexisted connection');
		
		unset($this->_connections[$addr][$port]);
		
		if(!count($this->_connections[$addr]))
			unset($this->_connections[$addr]);
		
		if($addr == $this->_addr_of_current && $port == $this->_port_of_current)
			$this->_current = null;
		
		return $this;
	}
	
	/**
	*	Destroy current connection object (if you need new, You must create it by create())
	*	@return self
	*	@see destroy()
	*	@throws Http_Client_Connection_Handler_Exception
	*/
	public function destroyCurrent()
	{
		if(!$this->_current)
		{
			//throw new Http_Client_Connection_Handler_Exception('Current connection is not set (not selected before by select() or destroyed by destroy() or destroyCurrent())');
			return $this;
		}
		
		$this->_current = null;
		
		unset($this->_connections[$this->_addr_of_current][$this->_port_of_current]);
		
		if(!count($this->_connections[$this->_addr_of_current]))
			unset($this->_connections[$this->_addr_of_current]);
		
		return $this;
	}
}