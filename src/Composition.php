<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;
use Ceive\View\Layer\Block\BlockAppend;
use Ceive\View\Layer\Block\BlockPrepend;
use Ceive\View\Layer\Block\BlockTarget;


/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Composition
 */
class Composition{
	
	/** @var  Lay */
	protected $lay;
	
	/** @var  string */
	protected $name;
	
	/** @var BlockTarget|null */
	protected $target;
	
	/** @var BlockPrepend[] */
	protected $prepends = [];
	
	/** @var BlockAppend[] */
	protected $appends = [];
	
	protected $picked = false;
	
	/**
	 * @param $name
	 * @return $this
	 */
	public function setName($name){
		$this->name = $name;
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
	 * @return string
	 */
	public function getName(){
		return $this->name;
	}
	
	/**
	 * Composition constructor.
	 * @param BlockTarget $target
	 * @param BlockPrepend[] $prepends
	 * @param BlockAppend[] $appends
	 */
	public function __construct(BlockTarget $target, array $prepends = null, array $appends = null){
		$this->target   = $target;
		$this->prepends = $prepends?:[];
		$this->appends  = $appends?:[];
	}
	
	/**
	 * @return $this
	 */
	public function pick(){
		if(!$this->picked){
			$this->picked = true;
			
			foreach($this->prepends as $block){
				$block->setComposition($this);
				$block->pick();
			}
			if($this->target){
				$this->target->setComposition($this);
				$this->target->pick();
			}
			foreach($this->appends as $block){
				$block->setComposition($this);
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
			
			foreach($this->prepends as $block){
				$block->unpick();
			}
			
			if($this->target){
				$this->target->unpick();
			}
			
			foreach($this->appends as $block){
				$block->unpick();
			}
			
			$this->picked = false;
		}
		
		return $this;
	}
	
	
	/**
	 * @param $name
	 * @return BlockHolder[]
	 */
	public function findContainHolders($name){
		$holders = [];
		
		$compositionName = $this->getName();
		if(substr($name, 0, $cNLen = strlen($compositionName)) === $compositionName){
			$searchName = trim(substr($name, $cNLen),'.');
		}else{
			//if(strpos($name,'.')!==false)
			//return []
			$searchName = trim($name,'.');
		}
		
		if($block = $this->getTarget()){
			
			if($compositionName === $name){
				$holders = array_merge($holders, $this->_searchHoldersIn(null, $block));
			}else{
				$holders = array_merge($holders, $this->_searchHoldersIn($searchName, $block));
			}
			
		}
		foreach($this->prepends as $block){
			$holders = array_merge($holders, $this->_searchHoldersIn($searchName, $block));
		}
		foreach($this->appends as $block){
			$holders = array_merge($holders, $this->_searchHoldersIn($searchName, $block));
		}
		return $holders;
	}
	
	/**
	 * @param $name
	 * @param Block $block
	 * @return BlockHolder[]
	 */
	protected function _searchHoldersIn($name, Block $block){
		$holders = [];
		foreach($block->contents as $content){
			if($content instanceof Layout){
				$holders = array_merge($holders, $content->getContainHoldersBy($name));
			}else if($content instanceof BlockHolder && $content->getPath() === $name){
				$holders[] = $content;
			}
		}
		return $holders;
	}
	
	/**
	 * @return array|BlockAppend[]
	 */
	public function getAppends(){
		return $this->appends;
	}
	
	/**
	 * @return array|BlockPrepend[]
	 */
	public function getPrepends(){
		return $this->prepends;
	}
	
	/**
	 * @return BlockTarget|null
	 */
	public function getTarget(){
		return $this->target;
	}
	
	/**
	 * @return Lay
	 */
	public function getLay(){
		return $this->lay;
	}
	
	
}
