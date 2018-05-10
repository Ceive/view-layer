<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

use Ceive\View\Layer\BlockType\BlockType;
use Ceive\View\Layer\BlockType\BlockTypeDefine;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Holder
 * @package Ceive\View\Layer
 */
class Holder extends AbstractEntity{
	
	/** @var  string */
	public $name;
	
	/** @var Holder */
	public $parentHolder;
	
	/** @var  Block */
	public $ownerBlock;
	
	/** @var Block|null */
	public $defaultBlock = null;
	
	/** @var  Block[] */
	public $targets   = [];
	
	/** @var  Block[] */
	public $prepends = [];
	
	/** @var  Block[] */
	public $appends  = [];
	
	
	protected $_actualAttachedBlocks = null;
	
	/** @var   */
	public $contents;
	
	
	/**
	 *
	 * Holder constructor.
	 * @param $name
	 */
	public function __construct($name=null){
		$this->name = $name?:null;
	}
	
	/**
	 * @param bool $asArray
	 * @return array|string
	 */
	public function getPath($asArray = false){
		$path = [];
		
		if($this->ownerBlock instanceof DefaultBlock){
			$holder = $this->ownerBlock->parentHolder;
			$path = $holder->getPath(true);
		}
		
		//if(($scopeHolder = $this->parentHolder)){
		//	$path = $scopeHolder->getPath(true);
		//}
		
		if($this->name){
			$path[] = $this->name;
		}
		
		return $asArray===true?$path:implode($asArray?:'.',$path);
	}
	
	
	/**
	 * @return array
	 */
	public function getContents(){
		if(!isset($this->contents)){
			/** @var Block[] $blocks */
			$blocks = $this->getActualAttachedBlocks(true);
			$this->contents = [];
			foreach($blocks as $block){
				foreach($block->contents as $c){
					$this->contents[] = $c;
				}
			}
		}
		return $this->contents;
	}
	
	/**
	 * Вернет блоки в формате композиции, только те которые актульны для процессинга, а именно учитавая,
	 * что некоторые ранние добавленные блоки перекрыты последующим и самым последним по низходящей в очереди блоком типа define если конечно он есть.
	 * Нисходящая: Высокий уровень > Более низкие уровни
	 * Уровень: Определяется позицией Слоя обладателя у блока
	 * @return array|null [target, prepends: prepends[], appends: appends[]]
	 */
	public function getActualAttachedBlocks($returnAsStack = false){
		if(!isset($this->_actualAttachedBlocks)){
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
				if($block->type instanceof BlockTypeDefine){
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
			
			
			$this->_actualAttachedBlocks = [
				'target'   => $target,
				'appends'  => $appends,
				'prepends' => $prepends,
			];
		}
		
		if($returnAsStack){
			$target     = $this->_actualAttachedBlocks['target'];
			$appends    = $this->_actualAttachedBlocks['appends'];
			$prepends   = $this->_actualAttachedBlocks['prepends'];
			return array_merge($prepends, $this->_actualAttachedBlocks['target']?[$target]:[], $appends);
		}
		
		return $this->_actualAttachedBlocks;
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
	 * @return array of Holder[]
	 */
	public function getContainHolders(){
		$holders = [];
		
		/** @var Block $block */
		foreach($this->getActualAttachedBlocks(true) as $block){
			// Здесь нужно рассмотреть еще тот факт, что в холдерах после процессинга будут блоки и в них далее могут быть холдеры
			// Здесь был перебор из Layout
			$holders = array_merge($holders, $block->getContainHolders());
			
		}
		return $holders;
	}
	
	/**
	 * @param null $holderName
	 * @return array
	 */
	public function getContainHoldersBy($holderName = null){
		$holders = [];
		
		/** @var Block $block */
		foreach($this->getActualAttachedBlocks(true) as $block){
			// Здесь нужно рассмотреть еще тот факт, что в холдерах после процессинга будут блоки и в них далее могут быть холдеры
			// Здесь был перебор из Layout
			$holders = array_merge($holders, $block->getContainHoldersBy($holderName));
			
		}
		return $holders;
	}
	
	/**
	 * @return string
	 */
	public function render(){
		$a = [];
		foreach($this->getContents() as $element){
			$a[] = $element->render();
		}
		return implode("\r\n", $a);
	}
	
	
	
	public function addTarget(Block $block){
		$this->targets[] = $block;
		$this->reset();
		return $this;
	}
	
	public function addAppend(Block $block){
		$this->appends[] = $block;
		$this->reset();
		return $this;
	}
	
	public function addPrepend(Block $block){
		$this->prepends[] = $block;
		$this->reset();
		return $this;
	}
	
	public function removeAppend(Block $block){
		$i = array_search($block, $this->appends, true);
		if($i!==false){
			array_splice($this->appends, $i,1);
			$this->reset();
		}
	}
	
	public function removeTarget(Block $block){
		$i = array_search($block, $this->targets, true);
		if($i!==false){
			array_splice($this->targets, $i,1);
			$this->reset();
		}
	}
	
	public function removePrepend(Block $block){
		$i = array_search($block, $this->prepends, true);
		if($i!==false){
			array_splice($this->prepends, $i,1);
			$this->reset();
		}
	}
	
	public function reset(){
		$this->_actualAttachedBlocks = null;
		$this->contents = null;
	}
	
	/**
	 * @return Block
	 */
	public function registerDefaultBlock($type = null){
		
		if(!$this->defaultBlock){
			
			if(!$type){
				$type =  BlockType::requireBlockType(BlockType::CASCADE);
			}else{
				$type =  $this->ownerBlock->composition->layer->manager->requireType($type);
			}
			
			$block = new DefaultBlock($type);
			
			$block->name = $this->getPath()?:null;
			$block->composition = $this->ownerBlock->composition;
			$block->parentHolder = $this;
			
			$this->defaultBlock = $block;
		}
		return $this->defaultBlock;
		
	}
	
}

