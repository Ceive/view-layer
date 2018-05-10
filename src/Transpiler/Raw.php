<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Raw
 * @package Ceive\View\Layer\Transpiler
 *
 * Pattern: RawValue is object
 *
 */
class Raw{
	
	public $value;
	
	public function __construct($value){
		$this->value = $value;
	}
	
	public function __invoke(){
		return $this->value;
	}
	
	public static function here($value){
		return new Raw($value);
	}
	
}


