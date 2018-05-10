<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;



/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Composition
 */
class Composition extends AbstractEntity{
	
	/** @var  Layer */
	public $layer;
	
	/** @var  string */
	public $name;
	
	
	/** @var Block|null */
	public $target;
	
	/** @var Block[] */
	public $prepends = [];
	
	/** @var Block[] */
	public $appends = [];
	
	/** @var bool  */
	protected $picked = false;
	
	/**
	 * @return bool
	 */
	public function isPicked(){
		return $this->picked;
	}
	
	/**
	 * @return $this
	 */
	public function pick(){
		if(!$this->picked){
			$this->picked = true;
			
			foreach($this->prepends as $block){
				$block->composition = $this;
				$block->pick();
			}
			if($this->target){
				$this->target->composition = $this;
				$this->target->pick();
			}
			foreach($this->appends as $block){
				$block->composition = $this;
				$block->pick();
			}
			
		}
		
		return $this;
	}
	
	/**
	 * @return $this
	 */
	public function unpick(){
		
		if($this->picked){
			$this->picked = false;
			
			foreach($this->prepends as $block){
				$block->unpick();
			}
			
			if($this->target){
				$this->target->unpick();
			}
			
			foreach($this->appends as $block){
				$block->unpick();
			}
		}
		
		return $this;
	}
	
	
	/**
	 * @param $holderName
	 * @return Holder[]
	 */
	public function getContainHoldersBy($holderName = null){
		$holders = [];
		
		$compositionName = $this->name;
		
		$compositionNameLength = strlen($compositionName);
		$startOfName = substr($holderName, 0, $compositionNameLength);
		
		if($startOfName === $compositionName){
			$searchName = trim(substr($holderName, $compositionNameLength),'.');
		}else{
			//if(strpos($name,'.')!==false)
			//return []
			$searchName = trim($holderName,'.');
		}
		
		if($block = $this->target){
			
			if($compositionName === $holderName){
				$holders = array_merge($holders, $block->getContainHoldersBy(null));
			}else{
				$holders = array_merge($holders, $block->getContainHoldersBy($searchName));
			}
			
		}
		foreach($this->prepends as $block){
			$holders = array_merge($holders, $block->getContainHoldersBy($searchName));
		}
		foreach($this->appends as $block){
			$holders = array_merge($holders, $block->getContainHoldersBy($searchName));
		}
		return $holders;
	}
	
	
	/**
	 * @param $type
	 * @return Block
	 */
	public function registerBlock($type){
		
		$type = $this->layer->manager->requireType($type);
		
		$block = new Block($type);
		$block->name = $this->name;
		
		$type->attachToComposition($this, $block);
		
		return $block;
		
	}
	
	
}
