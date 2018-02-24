<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

/**
 * @Author: Alexey Kutuzov <lexus.1995@mail.ru>
 * Interface Element
 * @package Ceive\View\Layer
 */
interface Element{
	
	/**
	 * @param Element $element
	 * @return $this
	 */
	public function setParent(Element $element);
	
	/**
	 * @return Element
	 */
	public function getParent();
	
	/**
	 * @return Element[]
	 */
	public function getElements();
	
	/**
	 * @return string
	 */
	public function render();
	
}
