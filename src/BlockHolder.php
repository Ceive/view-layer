<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;


/**
 * TODO: Блоки APPEND и PREPEND Должны закрепляться за исходными, самыми первыми блоками
 */
use Ceive\View\Layer\Block\BlockAppend;
use Ceive\View\Layer\Block\BlockDefine;
use Ceive\View\Layer\Block\BlockPrepend;
use Ceive\View\Layer\Block\BlockTarget;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class BlockHolder
 * @package Ceive\View\Layer
 */
class BlockHolder implements BlockHolderInterface{
	
	/** @var  Layout */
	protected $parent;
	
	/** @var  string */
	protected $name;
	
	
	/** @var  null| Element[] */
	protected $elements;
	
	
	/** @var  BlockTarget */
	protected $definedIn;
	
	/** @var  BlockTarget[] */
	protected $targets   = [];
	
	/** @var  BlockPrepend[] */
	protected $prepends = [];
	
	/** @var  BlockAppend[] */
	protected $appends  = [];
	
	
	/**
	 * BlockHolder constructor.
	 * @param $name
	 * @param Layout $parent
	 */
	public function __construct($name, Layout $parent = null){
		$this->name = $name?:null;
		$this->parent = $parent;
	}
	
	/**
	 * @param Layout|Element $layout
	 * @return $this
	 */
	public function setParent(Element $layout){
		$this->parent = $layout;
		return $this;
	}
	
	/**
	 * @return Layout
	 */
	public function getParent(){
		return $this->parent;
	}
	
	/**
	 * @return string|null
	 */
	public function getName(){
		return $this->name;
	}
	
	/**
	 * @return bool
	 */
	public function isCascade(){
		return $this->name === null;
	}
	
	/**
	 * @param bool $asArray
	 * @return array|string
	 */
	public function getPath($asArray = false){
		$path = [];
		if(($scopeHolder = $this->getHolder())){
			$path = $scopeHolder->getPath(true);
		}
		if($this->name){
			$path[] = $this->name;
		}
		return $asArray===true?$path:implode($asArray?:'.',$path);
	}
	
	/**
	 * @return BlockHolder|Element|null
	 */
	public function getHolder(){
		if($this->parent instanceof Layout){
			return $this->parent->getHolder();
		}else{
			return null;
		}
	}
	
	/**
	 * @return Layout|null
	 */
	public function getRootHoldsLayout(){
		if($this->parent instanceof Layout){
			return $this->parent->getRootHoldsLayout();
		}else{
			return null;
		}
	}
	
	/**
	 * @param BlockPrepend $block
	 * @return $this
	 */
	public function addPrepend(BlockPrepend $block){
		$this->prepends[] = $block;
		return $this;
	}
	
	/**
	 * @param BlockAppend $block
	 * @return $this
	 */
	public function addAppend(BlockAppend $block){
		$this->appends[] = $block;
		return $this;
	}
	
	/**
	 * @param BlockTarget $block
	 * @return $this
	 */
	public function addTarget(BlockTarget $block){
		$this->targets[] = $block;
		return $this;
	}
	
	/**
	 * @param Block $block
	 */
	public function detachBlock(Block $block){
		
	}
	
	/**
	 * @return Element[]
	 */
	public function getElements(){
		if(!isset($this->elements)){
			
			/**
			 * @var $target BlockTarget|null
			 *
			 * @var $definedIn BlockTarget|null
			 * @var $definedLevel int
			 *
			 * @var $prepends   BlockPrepend[]
			 * @var $appends    BlockAppend[]
			 */
			
			$prepends       = [];
			$appends        = [];
			
			$targetIndex    = null;
			$target         = null;
			$definedIn      = null;
			$definedLevel   = 0;
			
			list($levels, $blocks) = $this->_prepareBlocks($this->targets);
			foreach($blocks as $index => $block){
				if(!isset($target)){
					$target = $block;
					$targetIndex = $index;
				}
				if($block instanceof BlockDefine){
					$definedIn = $block;
					$definedLevel = $levels[$index];
					break;
				}
			}
			if(!$definedIn){
				$definedIn = $target;
				if($targetIndex){
					$definedLevel   = $levels[$targetIndex];
				}
			}
			
			
			list($levels, $this->prepends) = $this->_prepareBlocks($this->prepends, SORT_DESC);
			foreach($this->prepends as $index => $block){
				if($levels[$index] >= $definedLevel){
					$prepends[] = $block;
				}
			}
			
			list($levels, $this->appends) = $this->_prepareBlocks($this->appends);
			foreach($this->appends as $index => $block){
				if($levels[$index] >= $definedLevel){
					$appends[] = $block;
				}
			}
			
			
			
			/** @var Block $block */
			$elements = [];
			if($prepends){
				foreach($prepends as $block){
					$elements[] = $block->contents;
				}
			}
			if($target){
				$elements[] = $target->contents;
			}
			if($appends){
				foreach($appends as $block){
					$elements[] = $block->contents;
				}
			}
			
			
			
			if($elements){
				$this->elements = call_user_func_array('array_merge', $elements);
			}else{
				$this->elements = [];
			}
			
			
			foreach($this->elements as $el){
				$el->setParent($this);
			}
			
		}
		return $this->elements;
	}
	
	/**
	 * @param Block[] $blocks
	 * @param int $direction
	 * @return array
	 * @internal param int $sortFlag
	 */
	protected function _prepareBlocks(array $blocks, $direction = SORT_ASC){
		
		if(!$blocks){
			return [[],[]];
		}
		
		$_levels = [];
		foreach($blocks as $index => $block){
			$_levels[$index] = $block->getLevel();
		}
		
		switch(true){
			case $direction === SORT_DESC:
				arsort($_levels, SORT_NUMERIC);
				break;
			case $direction === SORT_ASC:
			default:
				asort($_levels, SORT_NUMERIC);
				break;
		}
		
		return [ $_levels,  array_replace( $_levels, $blocks )];
	}
	
	/**
	 * @return string
	 */
	public function render(){
		$a = [];
		foreach($this->getElements() as $element){
			$a[] = $element->render();
		}
		return implode("\r\n", $a);
	}
}

