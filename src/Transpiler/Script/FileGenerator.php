<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler\Script;


use Ceive\View\Layer\Transpiler\Transpiler;

class FileGenerator implements \ArrayAccess{
	
	public $path;
	
	protected $holdsTo = 'body';
	
	public $header = [];
	
	public $body = [];
	
	/** @var Transpiler  */
	protected $transpiler;
	
	public function __construct($path, Transpiler $manager){
		$this->path = $path;
		$this->transpiler = $manager;
	}
	
	/**
	 * @param $code
	 * @param string $indent
	 * @param null $offset
	 * @return $this
	 */
	public function code($code, $indent = '', $offset = null){
		
		$code = !$indent? $code : ($indent . strtr($code, ["\n" => "\n{$indent}"]));
		
		$this->insert($offset, $code);
		return $this;
	}
	
	public function header(){
		$this->holdsTo = 'header';
		return $this;
	}
	public function body(){
		$this->holdsTo = 'body';
		return $this;
	}
	
	public function save(){
		return (boolean) file_put_contents($this->path, implode("\n", $this->_contents()));
	}
	
	protected function _contents(){
		return array_merge($this->header,[null],$this->body);
	}
	
	public function offsetExists($offset){
		$a = &$this->holdsTo?$this->header:$this->body;
		return isset($a[$offset]);
	}
	
	protected function &_getHoldsArray(){
		switch($this->holdsTo){
			case 'header':
				return $this->header;
				break;
			case 'body':
				return $this->body;
				break;
		}
		$a  = [];
		return $a;
	}
	
	public function insert($offset, $section){
		$a = &$this->_getHoldsArray();
		if(!is_array($section)){
			$section = [$section];
		}
		if($offset===null){
			$offset = count($a);
		}
		array_splice($a, $offset, 0, $section);
		return $this;
	}
	
	public function br(){
		$a = &$this->_getHoldsArray();
		$a[] = null;
		return $this;
	}
	
	public function offsetGet($offset){
		$a = &$this->_getHoldsArray();
		return isset($a[$offset])?$a[$offset]:null;
	}
	
	public function offsetSet($offset, $value){
		$a = &$this->_getHoldsArray();
		if($offset===null)$a[] = $value;
		else $a[$offset] = $value;
	}
	public function offsetUnset($offset){
		$a = &$this->_getHoldsArray();
		unset($a[$offset]);
	}
	
	public function literal($value){
		return "{$value}";
	}
	
}


