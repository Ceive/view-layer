<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

use Ceive\View\Layer\BlockType\BlockType;


/**
 * capture
 * cascade
 */

class Block extends AbstractEntity{
	
	/** @var  string */
	public $name;
	
	/** @var  BlockType */
	public $type;
	
	/** @var  Composition  */
	public $composition;
	
	
	/** @var  string */
	public $raw;
	
	/** @var array|null  */
	public $contents = [];
	
	
	/** @var  Holder[] */
	public $holders = [];
	
	/** @var  Holder[]  */
	public $holdersRegistry = [];
	
	/** @var  null|Holder[] */
	protected $_holdsIn;
	
	
	/**
	 * Block constructor.
	 */
	public function __construct(BlockType $type){
		$this->type = $type;
	}
	
	
	/**
	 * @param null|array|string $contents
	 * @param bool $merge
	 * @return $this
	 */
	public function setContents($contents = null, $merge = false){
		
		if(!$merge){
			
			if(!$contents){
				$this->contents = [];
			}else if(!is_array($contents)){
				$this->contents = [$contents];
			}else{
				$this->contents = $contents;
			}
		}else{
			if($contents){
				if(!is_array($contents)){
					$contents = [$contents];
				}
				$this->contents = array_merge($this->contents, $contents);
			}
		}
		return $this;
	}
	
	/**
	 * @return bool
	 */
	public function isPicked(){
		return !empty($this->_holdsIn);
	}
	/**
	 * Pick-up to preferred holder
	 */
	public function pick(){
		if(!$this->isPicked()){
			$layer = $this->composition->layer;
			$holders = $this->_searchHoldersForPick($layer);
			
			foreach($holders as $holder){
				$this->attachToHolder($holder);
			}
		}
		
		
		return $this;
	}
	
	/**
	 * unpick from picked holders
	 */
	public function unpick(){
		if($this->isPicked()){
			$holdsIn = $this->_holdsIn;
			$this->_holdsIn = null;
			foreach($holdsIn as $holder){
				$this->detachFromHolder($holder);
			}
		}
		return $this;
	}
	
	
	/**
	 * @param Layer $layer
	 * @return Holder[]
	 */
	protected function _searchHoldersForPick(Layer $layer){
		return $this->type->searchHoldersForPick($this, $layer);
	}
	
	/**
	 * @param Holder $holder
	 */
	protected function attachToHolder(Holder $holder){
		$this->_holdsIn[] = $holder;
		$this->type->attachToHolder($this, $holder);
	}
	
	/**
	 * @param Holder $holder
	 */
	protected function detachFromHolder(Holder $holder){
		$i = array_search($holder, $this->_holdsIn, true);
		if($i!==false){
			array_splice($this->_holdsIn, $i,1);
		}
		$this->type->detachFromHolder($this, $holder);
	}
	
	
	/**
	 *
	 * @return Holder[]
	 */
	public function getContainHoldersBy($searchName = null){
		// todo с учетом вложенности в композиции composition.holder
		$holders = [];
		foreach($this->holders as $holder){
			if($holder->getPath() === $searchName){
				$holders[] = $holder;
			}
		}
		
		return $holders;
	}
	
	/**
	 * @return int|mixed|null
	 */
	public function getLevel(){
		return $this->composition?$this->composition->layer->getLevel():null;
	}
	
	/**
	 * @return array of Holder[]
	 */
	public function getContainHolders(){
		$holders = [];
		foreach($this->holders as $holder){
			if(!isset($holders[$holder->name])){
				$holders[$holder->name] = [];
			}
			$holders[$holder->name][] = $holder;
		}
		return $holders;
	}
	
	public function getContents(){
		return $this->contents;
	}
	
	/**
	 * @param $name
	 * @param null $identifier
	 * @return Holder
	 */
	public function registerHolder($name, $identifier = null){
		if($identifier!==null){
			$identifier = (string) $identifier;
			if(!isset($this->holdersRegistry[$identifier])){
				$holder = $this->holdersRegistry[$identifier] = new Holder($name);
				$this->holders[] = $holder;
				
				$holder->ownerBlock = $this;
				
			}
			return $this->holdersRegistry[$identifier];
		}else{
			$holder = new Holder($name);
			$this->holders[] = $holder;
			$holder->ownerBlock = $this;
			return $holder;
		}
	}
	
}
