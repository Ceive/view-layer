<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler\Script;


use Ceive\View\Layer\Transpiler\FS\FSGlob;

class ES6FileGenerator extends FileGenerator{
	
	public $_imports = [];
	
	public $imports = [];
	
	public function import($as, $from, $indent = '', $offset = null){
		$this->_imports[] = [$as, $from, $indent, $offset];
		return $this;
	}
	
	protected function &_getHoldsArray(){
		if($this->holdsTo==='imports'){
			return $this->imports;
		}
		return parent::_getHoldsArray();
	}
	
	protected function _contents(){
		
		foreach($this->_imports as list($as, $from, $indent, $offset)){
			/** @see Raw::__invoke() */
			if(is_callable($from)){
				$from = $from();
			}else{
				$from = $this->transpiler->loader->relativePathThroughDots($this->path, $from);
			}
			$from = FSGlob::normalize($from, '/');
			$as = $as?" {$as} from":'';
			
			$this->holdsTo = 'imports';
			
			$this->code("import{$as} '{$this->literal($from)}'", $indent, $offset);
		}
		
		
		
		
		return array_merge($this->imports, [null], $this->header, [null],$this->body);
	}
	
	
}


