<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Node;


class JsonConfig implements \ArrayAccess{
	
	protected $_path;
	
	protected $_loaded = false;
	
	protected $_data = [];
	protected $_saved = [];
	protected $_isObject = true;
	
	public function __construct($path, $isObject = true){
		$this->_path = $path;
		$this->_data = [];
		$this->_isObject = $isObject;
		$this->load();
	}
	
	public function isLoaded(){
		return $this->_loaded;
	}
	
	public function isDirty(){
		return $this->_saved!=$this->_data;
	}
	
	public function getPath(){
		return $this->_path;
	}
	
	public function getData(){
		return $this->_data;
	}
	
	public function save(){
		if($this->_saved!=$this->_data){
			$this->_saved = $this->_data;
			$data = $this->_isObject? (object)$this->_data:$this->_data;
			file_put_contents($this->_path, json_encode($data,JSON_PRETTY_PRINT));
		}
		return $this;
	}
	
	public function load(){
		if(file_exists($this->_path)){
			if(!$this->_data = @json_decode(file_get_contents($this->_path))){
				$msg = json_last_error_msg();
				throw new \Exception("Error on load and parse JSON config in '{$this->_path}': '{$msg}'");
			}
			$this->_loaded = true;
		}else{
			$this->_data = [];
		}
		return $this;
	}
	
	public function __set($name, $value){
		$this->_data[$name] = $value;
		return $value;
	}
	
	public function __get($name){
		return array_key_exists($name, $this->_data)?$this->_data[$name]:null;
	}
	public function __isset($name){
		return isset($this->_data[$name]);
	}
	public function __unset($name){
		unset($this->_data[$name]);
	}
	
	public function offsetExists($offset){
		return $this->__isset($offset);
	}
	
	public function offsetGet($offset){
		return $this->__get($offset);
	}
	
	public function offsetSet($offset, $value){
		if($offset === null){
			$this->_data[] = $value;
		}else{
			$this->__set($offset, $value);
		}
		return $value;
	}
	
	public function offsetUnset($offset){
		$this->__unset($offset);
	}
}


