<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;


/**
 * capture
 * cascade
 */

abstract class Block{
	
	/** @var  Composition  */
	protected $composition;
	
	/** @var  null|BlockHolder[] */
	protected $holdsIn;
	
	/** @var array|null  */
	public $contents = [];
	
	
	/**
	 * Block constructor.
	 * @param null $contents
	 */
	public function __construct($contents = null){
		if(!is_array($contents)){
			if($contents){
				$this->contents = [$contents];
			}
		}else{
			$this->contents = $contents;
		}
	}
	
	/**
	 * @return bool
	 */
	public function isHolds(){
		return $this->holdsIn !== null;
	}
	
	/**
	 * @param Composition $composition
	 * @return $this
	 */
	public function setComposition(Composition $composition){
		$this->composition = $composition;
		return $this;
	}
	
	/**
	 * @return Composition
	 */
	public function getComposition(){
		return $this->composition;
	}
	
	
	/**
	 * @param BlockHolder $holder
	 * @return $this
	 */
	public function addHoldsIn(BlockHolder $holder){
		if(!in_array($holder, $this->holdsIn, true)){
			$this->holdsIn[] = $holder;
		}
		return $this;
	}
	
	/**
	 * @param BlockHolder $holder
	 * @return $this
	 */
	public function removeHoldsIn(BlockHolder $holder){
		if(!$this->holdsIn)return $this;
		if(($i = array_search($holder, $this->holdsIn, true))){
			array_splice($this->holdsIn, $i,1);
		}
		return $this;
	}
	
	/**
	 * Pick-up to preferred holder
	 */
	public function pick(){
		$name = $this->composition->getName();
		$lay = $this->composition->getLay();
		$holders = $this->findHolders($name, $lay);
		foreach($holders as $holder){
			$this->addToHolder($holder);
		}
		return $this;
	}
	
	/**
	 * unpick from picked holders
	 */
	public function unpick(){
		$holdsIn = $this->holdsIn; $this->holdsIn = [];
		foreach($holdsIn as $holder){
			$holder->detachBlock($this);
		}
		return $this;
	}
	
	/**
	 * @param $name
	 * @param Lay $lay
	 * @return BlockHolder[]
	 */
	abstract protected function findHolders($name, Lay $lay);
	
	protected function addToHolder(BlockHolder $holder){
		$this->holdsIn[] = $holder;
	}
	
	/**
	 * @return int|mixed|null
	 */
	public function getLevel(){
		return $this->composition?$this->composition->getLay()->getLevel():null;
	}
	
}
