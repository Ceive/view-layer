<?php

interface Element{
	
	/**
	 * @param Element $element
	 * @return $this
	 */
	public function setParent(Element $element);
	
	/**
	 * @return Element
	 */
	public function getParent();
	
	/**
	 * @return Element[]
	 */
	public function getElements();
	
	/**
	 * @return string
	 */
	public function render();
	
}

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
	 * @throws Exception
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
		return
			"<div>\r\n" .
		    "\t{$a}\r\n" .
		    "</div>";
	}
	
}

/**
 * TODO: Блоки APPEND и PREPEND Должны закрепляться за исходными, самыми первыми блоками
 */
/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class BlockHolder
 */
class BlockHolder implements Element{
	
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
	 * @return Layout
	 */
	public function getParent(){
		return $this->parent;
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
	 * @param Block $block
	 */
	public function detachBlock(Block $block){
		
	}
	
	/**
	 * @param BlockTarget $a
	 * @param BlockTarget $b
	 * @return int
	 */
	protected function _sortCmpFn(BlockTarget $a, BlockTarget $b){
		$a = $a->getLevel();
		$b = $b->getLevel();
		if($a==$b){
			return 0;
		}
		return $a > $b? 1: -1;
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

class BlockTarget extends Block{
	
	/**
	 * @param $name
	 * @param Lay $lay
	 * @return BlockHolder[]
	 */
	protected function findHolders($name, Lay $lay){
		
		$ancestor = $lay->getAncestor();
		
		$holders = [];
		while($ancestor){
			$holders = $ancestor->searchHolders($name);
			if($holders){
				break;
			}else{
				$ancestor = $ancestor->getAncestor();
			}
		}
		
		return $holders;
	}
	
	protected function addToHolder(BlockHolder $holder){
		parent::addToHolder($holder);
		$holder->addTarget($this);
	}
	
}

class BlockCoverer extends Block{
	
	
	/**
	 * @param $name
	 * @param Lay $lay
	 * @return BlockHolder[]
	 */
	protected function findHolders($name, Lay $lay){
		/**
		 * @var $ancestors Lay[]
		 */
		
		$ancestor = $lay->getAncestor();
		
		$holders = [];
		$ancestors = [];
		
		while($ancestor){
			$ancestors[] = $ancestor;
			$ancestor = $ancestor->getAncestor();
		}
		$ancestors = array_reverse($ancestors);
		
		foreach($ancestors as $ancestor){
			$holders = $ancestor->searchHolders($name);
			if($holders){
				break;
			}
		}
		
		return $holders;
	}
}

class BlockDefine extends BlockTarget{
	
}

class BlockReplace extends BlockTarget{
	
}

class BlockCascade extends BlockTarget{
	
}


class BlockAppend extends BlockCoverer{
	
	protected function addToHolder(BlockHolder $holder){
		parent::addToHolder($holder);
		$holder->addAppend($this);
	}
	
}

class BlockPrepend extends BlockCoverer{
	
	protected function addToHolder(BlockHolder $holder){
		parent::addToHolder($holder);
		$holder->addPrepend($this);
	}
	
}

$lay = new Lay([
	
	':main' => new Composition(new BlockDefine([
		
		(new Layout())
			->add(new BlockHolder('header'))
			->add(
				
				(new Layout())
					->add(new SimpleElement(' [ top ] '))
					->add(new BlockHolder(null))
					->add(new SimpleElement(' [ bottom ] '))
				
			)
			->add(new BlockHolder('footer'))
	
	]),null,null)

]);

$lay = new Lay([
	
	'header' => new Composition(new BlockCascade([
		
		(new Layout())
			->add(new BlockHolder(null))
			->add(new SimpleElement(' [ HEAD < ] '))
			->add(new BlockHolder('authorization'))
			->add(new SimpleElement(' [ HEAD > ] '))
	
	]),null,null),
	
	':main' => new Composition(new BlockCascade([
		
		(new Layout())->add(new SimpleElement(' [ cascade ] '))
	    
	]),[
		new BlockPrepend([
			(new Layout())->add(new SimpleElement(' [ prepend ] '))
		])
	],[
		new BlockAppend([
			(new Layout())->add(new SimpleElement(' [ append ] '))
		])
	])

], $lay);


$lay = new Lay([
	
	'header.authorization' => new Composition(new BlockCascade([
		
		(new Layout())
			->add(new SimpleElement(' [ AUTHORIZATION ] '))
	
	]),null,null),
	
	
	'footer' => new Composition(new BlockCascade([
		
		(new Layout())->add(new SimpleElement(' [ footer-target ] '))
	
	]),[
		new BlockPrepend([ (new Layout())->add(new SimpleElement(' [ footer-prepend-L2 ] ')) ])
	],[
		new BlockAppend([ (new Layout())->add(new SimpleElement(' [ footer-append-L2 ] ')) ])
	])

], $lay);

$lay = new Lay([
	
	'header.authorization' => new Composition(new BlockCascade([
		
		(new Layout())
			->add(new SimpleElement(' [ AUTH_CASCADE ] '))
	
	]),null,null),
	
	'header' => new Composition(new BlockCascade([
		
		(new Layout())
			->add(new SimpleElement(' [ HEADER_CASCADE ] '))
	
	]),[
		new BlockPrepend([ (new Layout())->add(new SimpleElement(' [ header-prepend-L3 ] ')) ])
	],[
		new BlockAppend([ (new Layout())->add(new SimpleElement(' [ header-append-L3 ] ')) ])
	]),
	
	'footer' => new Composition(new BlockCascade([
		
		(new Layout())->add(new SimpleElement(' [ footer-target ] '))
	
	]),[
		new BlockPrepend([ (new Layout())->add(new SimpleElement(' [ footer-prepend-L3 ] ')) ])
	],[
		new BlockAppend([ (new Layout())->add(new SimpleElement(' [ footer-append-L3 ] ')) ])
	])

], $lay);

$level = $lay->getLevel();

$layout = $lay->getMainLayout();
$rendered = $layout->render();
echo $rendered;







$y = [
	
	[
		':main' => new Composition(new BlockDefine(),null,null)
	],
	
	[
		':main' => new Composition(new BlockDefine(),null,null)
	],
	
	[
		':main' => new Composition(new BlockDefine(),[new BlockPrepend(),new BlockPrepend(),],[new BlockAppend(),new BlockAppend(),])
	]

];