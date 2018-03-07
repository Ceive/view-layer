<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;


/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */
class Layout implements Element{
	
	/** @var  Element */
	protected $parent;
	
	/** @var  Layout[] */
	protected $elements = [];
	
	/** @var  null|BlockHolder[]  */
	protected $_holders;
	
	/** @var  Lay|null */
	protected $lay;
	
	protected $depth;
	
	
	/**
	 * @param Element $layout
	 * @return $this
	 */
	public function add(Element $layout){
		$this->elements[] = $layout;
		$layout->setParent($this);
		return $this;
	}
	
	/**
	 * @param Lay $lay
	 * @return $this
	 */
	public function setLay(Lay $lay){
		$this->lay = $lay;
		return $this;
	}
	
	/**
	 * @return Lay|null
	 */
	public function getLay(){
		return $this->lay;
	}
	
	/**
	 * @return Lay|null
	 */
	public function getTopLay(){
		if($this->lay){
			return $this->lay;
		}
		if($lay = $this->getParentLayout()){
			return $lay->getTopLay();
		}
		return null;
	}
	
	/**
	 * @return int
	 */
	public function getDepth(){
		if(!isset($this->depth)){
			$pLayout = $this->getParentLayout();
			$this->depth = $pLayout?$pLayout->getDepth() + 1: 0 ;
		}
		return $this->depth;
	}
	
	/**
	 * @return bool
	 */
	public function isHolds(){
		return $this->parent instanceof BlockHolder;
	}
	
	/**
	 * @return Layout
	 * @throws \Exception
	 */
	public function getRootHoldsLayout(){
		
		if(!$this->parent || $this->parent instanceof  BlockHolder){
			return $this;
		}
		
		
		if($this->parent instanceof Layout){
			return $this->parent->getRootHoldsLayout();
		}
		
		throw new \Exception('Parent is invalid, '.BlockHolder::class.' or '.Layout::class.' expected');
	}
	
	/**
	 * @return Layout|Element|null
	 */
	public function getParentLayout(){
		if($this->parent instanceof BlockHolder){
			return $this->parent->getParent();
		}
		return $this->parent;
	}
	
	/**
	 * @return BlockHolder|Element|null
	 */
	public function getHolder(){
		if($this->parent instanceof BlockHolder){
			return $this->parent;
		}elseif($this->parent instanceof Layout){
			return $this->parent->getHolder();
		}else{
			return null;
		}
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
		return $this->elements;
	}
	
	/**
	 * @return array[]|BlockHolder[]
	 */
	public function getContainHolders(){
		if(!isset($this->_holders)){
			$this->_holders = [];
			foreach($this->elements as $element){
				if($element instanceof Layout){
					
					foreach($element->getContainHolders() as $name => $holders){
						if(!isset($this->_holders[$name])){
							$this->_holders[$name] = [];
						}
						$this->_holders[$name] = array_merge($this->_holders[$name], $holders);
					}
					
				}elseif($element instanceof BlockHolder){
					$this->_holders[$element->getPath()][] = $element;
				}
			}
			
			foreach($this->_holders as $name => $holders){
				
				/** @var BlockHolder $holder */
				foreach($holders as $holder){
					$inner = $holder->getContainOwnHolders();
					foreach($inner as $innerName => $innerHolders){
						foreach($innerHolders as $innerHolder){
							$this->_holders[$name . '.' . $innerName][] = $innerHolder;
						}
					}
				}
			}
		}
		
		
		
		return $this->_holders;
	}
	
	/**
	 * @param $name
	 * @return BlockHolder[]
	 */
	public function getContainHoldersBy($name){
		$holders = $this->getContainHolders();
		return isset($holders[$name])?$holders[$name]:[];
	}
	
	
	/**
	 * @param Element $element
	 * @return $this
	 */
	public function setParent(Element $element){
		$this->parent= $element;
		return $this;
	}
	
	/**
	 * @return string
	 */
	public function render(){
		$elements = $this->getElements();
		$a = [];
		foreach($elements as $element){
			$a[] = $element->render();
		}
		
		
		$a = implode("\r\n", $a);
		$a =  str_replace("\r\n", "\r\n\t", $a);
		return $a;
	}
	
}

