<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;


class SimpleElement implements Element{
	
	/** @var  Element|Layout */
	protected $parent;
	
	protected $content;
	
	public function __construct($content = null){
		$this->content = $content;
	}
	
	/**
	 * @param Element $element
	 * @return $this
	 */
	public function setParent(Element $element){
		$this->parent = $element;
		return $this;
	}
	
	/**
	 * @return Element
	 */
	public function getParent(){
		return $this->parent;
	}
	
	/**
	 * @return Element[]
	 */
	public function getElements(){
		return [];
	}
	/**
	 * @return string
	 */
	public function render(){
		return $this->content;
	}
}

