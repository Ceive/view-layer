<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;
use Ceive\View\Layer\Block\BlockDefine;
use Ceive\View\Layer\Block\BlockTarget;


/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Lay
 */
class Lay{
	
	/** @var  null|int */
	protected $level;
	
	/** @var  null|Lay */
	protected $ancestor;
	
	/** @var  null|Lay */
	protected $descendant;
	
	/** @var  null|Composition[] */
	protected $compositions;
	
	/** @var bool  */
	protected $picked = false;
	
	/**
	 * Lay constructor.
	 * @param Composition[]|null $compositions
	 * @param Lay $ancestor
	 */
	public function __construct(array $compositions = null, Lay $ancestor = null){
		
		if($compositions)$this->setCompositions($compositions);
		
		$this->ancestor = $ancestor;
	}
	
	/**
	 * @return int|mixed
	 */
	public function getLevel(){
		if(!isset($this->level)){
			$this->level = $this->ancestor?$this->ancestor->getLevel() + 1:0;
		}
		return $this->level;
	}
	
	/**
	 * @return Lay
	 */
	public function getAncestor(){
		return $this->ancestor;
	}
	
	/**
	 * @return Lay
	 */
	public function getDescendant(){
		return $this->descendant;
	}
	
	
	/**
	 * @return Composition[]
	 */
	public function getCompositions(){
		return $this->compositions?:[];
	}
	
	/**
	 * @param $name
	 * @return BlockHolder[]
	 */
	public function searchHolders($name){
		$holders = [];
		foreach($this->getCompositions() as $composition){
			$holders = array_merge($holders, $composition->findContainHolders($name));
		}
		return $holders;
	}
	
	/**
	 * @param $name
	 * @param bool $delegateToAncestors
	 * @return Composition|null
	 */
	public function getComposition($name, $delegateToAncestors = true){
		$compositions = $this->getCompositions();
		if(isset($compositions[$name])){
			return $compositions[$name];
		}
		if(!$delegateToAncestors){
			return null;
		}
		return $this->ancestor?$this->ancestor->getComposition($name):null;
	}
	
	
	/**
	 * @param $name
	 * @param bool $delegateToAncestors
	 * @return BlockTarget|null
	 */
	public function getCompositionTargetDefine($name, $delegateToAncestors = true){
		$compositions =$this->getCompositions();
		if(isset($compositions[$name]) && ($target = $compositions[$name]->getTarget()) && $target instanceof BlockDefine){
			return $target;
		}
		if(!$delegateToAncestors){
			return null;
		}
		return $this->ancestor?$this->ancestor->getCompositionTargetDefine($name):null;
	}
	
	/**
	 * @return $this
	 */
	protected function pick(){
		if(!$this->picked){
			$this->picked = true;
			foreach($this->getCompositions() as $composition){
				$composition->pick();
			}
		}
		return $this;
	}
	
	/**
	 * @return $this
	 */
	protected function unpick(){
		if($this->picked){
			foreach($this->getCompositions() as $composition){
				$composition->unpick();
			}
			$this->picked = false;
		}
		
		return $this;
	}
	
	/**
	 * @return $this
	 */
	protected function pickChain(){
		$this->pick();
		if($this->ancestor){
			$this->ancestor->pickChain();
		}
		return $this;
	}
	
	/**
	 * @return Layout|null
	 */
	public function getMainLayout(){
		
		$this->pickChain();
		
		$define = $this->getCompositionTargetDefine(':main');
		
		foreach($define->contents as $c){
			if($c instanceof Layout){
				$layout = $c->getRootHoldsLayout();
				if($layout){
					return $layout;
				}
			}
		}
		return null;
	}
	
	/**
	 * @param Composition[] $compositions
	 */
	public function setCompositions(array $compositions){
		$this->compositions = [];
		foreach($compositions as $name => $composition){
			$this->compositions[$name] = $composition;
			$composition->setName($name);
			$composition->setLay($this);
		}
	}
	
}


