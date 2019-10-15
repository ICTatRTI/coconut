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
*	@package Http Request
*	@subpackage Http Request Data
*	@copyright Copyright (c) 2013-2014 Norbert Kiszka
*	@license http://www.gnu.org/licenses/gpl-2.0.html
*	@version 0.4
*/

/**
*	Http request data file - used in Http_Request_Data
*	@category Http Client
*	@package Http Request
*	@subpackage Http Request Data
*	@version 0.4
*/
class Http_Request_Data_File
{
	/**
	*	Version of class
	*	@var string
	*/
	const VERSION = '0.4';
	
	/**
	*	File data
	*/
	protected $_data =
	[
		'fileName' => '',
		'inputName' => '',
		'content' => ''
	];
	
	/**
	*	Set name of file (ex.: file.txt)
	*	@param string $v File name
	*	@return self
	*	@throws Http_Request_Data_File_Exception
	*/
	public function setFileName($v)
	{
		if(!is_string($v))
			throw new Http_Request_Data_File_Exception('File name must be a string.');
		
		if(!$v)
			throw new Http_Request_Data_File_Exception('Empty file name.');
		
		$this->_data['fileName'] = $v;
		return $this;
	}
	
	/**
	*	Get name of file
	*	@return string File name
	*	@throws Http_Request_Data_File_Exception
	*/
	public function getFileName()
	{
		if(!$this->_data['fileName'])
			throw new Http_Request_Data_File_Exception('Empty file name.');
		
		return $this->_data['fileName'];
	}
	
	/**
	*	Set name for html input
	*	@param string $v Input name
	*	@return self
	*	@throws Http_Request_Data_File_Exception
	*/
	public function setInputName($v)
	{
		if(!is_string($v))
			throw new Http_Request_Data_File_Exception('Input name must be a string.');
		
		if(!$v)
			throw new Http_Request_Data_File_Exception('Empty input name.');
		
		$this->_data['inputName'] = $v;
		
		return $this;
	}
	
	/**
	*	Get name of html input
	*	@throws Http_Request_Data_File_Exception
	*/
	public function getInputName()
	{
		if(!$this->_data['fileName'])
			throw new Http_Request_Data_File_Exception('Empty file name.');
		
		return $this->_data['inputName'];
	}
	
	/**
	*	Set contents of file
	*	@param string $v File contents
	*	@return self
	*/
	public function setContent($v)
	{
		$this->_data['content'] = $v;
		return $this;
	}
	
	/**
	*	Get contents of file
	*	@return string File contents
	*/
	public function getContent()
	{
		return $this->_data['content'];
	}
	
	/**
	*	Get file size in bytes
	*	@return int size of file contents in bytes
	*/
	public function getSize()
	{
		return strlen($this->_data['content']);
	}
}