<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;


/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Layer
 */
class Layer extends AbstractEntity{
	
	const COMPOSITION_MAIN = ':main';
	
	/** @var  LayerManager */
	public $manager;
	
	public $key;
	
	/** @var  null|Layer */
	public $ancestor;
	
	/** @var  null|object|array */
	public $scope = [];
	
	/** @var  Composition[] */
	protected $compositions = [];
	
	/** @var bool  */
	protected $_picked = false;
	
	/** @var  null|int */
	protected $_level;
	
	public $raw;
	
	public static function compName($name = null){
		return $name?:Layer::COMPOSITION_MAIN;
	}
	
	/**
	 * @return int|mixed
	 */
	public function getLevel(){
		if(!isset($this->_level)){
			$this->_level = $this->ancestor? $this->ancestor->getLevel() + 1:0;
		}
		return $this->_level;
	}
	
	/**
	 * @param $holderName
	 * @return Holder[]
	 */
	public function getContainHoldersBy($holderName = null){
		$holders = [];
		foreach($this->compositions as $composition){
			$holders = array_merge($holders, $composition->getContainHoldersBy($holderName));
		}
		return $holders;
	}
	
	/**
	 * @param $name
	 * @return Composition
	 */
	public function registerComposition($name){
		$key = $name===null?':main':$name;
		if(!isset($this->compositions[$key])){
			$composition = new Composition();
			$composition->name = $name;
			$composition->layer = $this;
			
			$this->compositions[$key] = $composition;
		}
		return $this->compositions[$key];
	}
	
	/**
	 * @param $name
	 * @param bool $delegateToAncestors
	 * @return Composition|null
	 */
	public function requireComposition($name, $delegateToAncestors = true){
		$key = $name===null?':main':$name;
		$compositions = $this->compositions;
		if(isset($compositions[$key])){
			return $compositions[$key];
		}
		if(!$delegateToAncestors){
			return null;
		}
		return $this->ancestor?$this->ancestor->requireComposition($name):null;
	}
	
	
	/**
	 * @param $name
	 * @param bool $delegateToAncestors
	 * @return Block|null
	 */
	public function requireLikeBlockDefine($name, $delegateToAncestors = true){
		$key = $name===null?':main':$name;
		if(isset($this->compositions[$key])){
			$target = $this->compositions[$key]->target;
		}
		
		if(isset($target)){
			return $target;
		}
		
		if(!$delegateToAncestors){
			return null;
		}
		$composition = $this->ancestor?$this->ancestor->requireLikeBlockDefine($name):null;
		
		
		if(!$composition && isset($target)){
			return $target;
		}
		
		
		return $composition;
	}
	
	/**
	 * @return Block|null
	 */
	public function requireMain(){
		return $this->requireLikeBlockDefine(Layer::COMPOSITION_MAIN);
	}
	
	/**
	 * @return $this
	 */
	protected function pick(){
		if(!$this->_picked){
			$this->_picked = true;
			foreach($this->compositions as $composition){
				$composition->pick();
			}
		}
		return $this;
	}
	
	/**
	 * @return $this
	 */
	protected function unpick(){
		if($this->_picked){
			foreach($this->compositions as $composition){
				$composition->unpick();
			}
			$this->_picked = false;
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
	 * @return null
	 */
	public function getContents(){
		$this->pickChain();
		$define = $this->requireMain();
		return $define->getContents();
	}
	
	public function reset(){
		$this->ancestor = null;
		$this->_level   = null;
		$this->unpick();
	}
	
	
}


