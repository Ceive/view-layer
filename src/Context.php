<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Context
 * @package Ceive\View\Layer
 */
class Context extends \stdClass{
	
	public function __construct(array $props){
		foreach($props as $k => $v){
			$this->{$k} = $v;
		}
	}
	
}


