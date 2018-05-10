<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;
use Ceive\View\Layer\Block\BlockAppend;
use Ceive\View\Layer\Block\BlockCascade;
use Ceive\View\Layer\Block\BlockDefine;
use Ceive\View\Layer\Block\BlockPrepend;
use Ceive\View\Layer\Block\BlockReplace;
use Ceive\View\Layer\Block\BlockTarget;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Builder
 * @package Ceive\View\Layer
 */
class Builder{
	
	public $dirname;
	
	public $directoriesBreadcrumb = [];
	
	protected $filesCache = [];
	
	protected $matchedContents = [];
	
	
	/**
	 * @param array $definition
	 * @return Layer|null
	 */
	public function build(array $definition){
		$lay = null;
		foreach($definition as $item){
			$item = array_replace(
				[
					'layer'  => null,
					'params' => null,
				],
				$item
			);
			
			if(is_array($item['params'])){
				$item['params'] = new Context($item['params']);
			}
			if(!is_array($item['layer'])){
				$item['layer'] = $item['layer'] ? [ $item['layer'] ] : [];
			}
			foreach($item['layer'] as $layer){
				$layer = $this->parseForLay($layer, true);
				if($layer){
					if($item['params']){
						$layer = $layer->setContext($item['params']);
					}
					if($lay){
						$layer->setAncestor($lay);
					}
					$lay = $layer;
				}
			}
		}
		return $lay;
	}
	
	/**
	 * @param $path
	 * @return mixed
	 */
	public function loadFile($path){
		if(!isset($this->filesCache[$path])){
			if(!file_exists($path)){
				$this->filesCache[$path] = false;
			}else{
				try{
					$this->filesCache[$path] = file_get_contents($path);
				}finally{
					array_pop($this->directoriesBreadcrumb);
				}
			}
			
		}
		return $this->filesCache[$path];
	}
	
	/**
	 * @param $content
	 * @param null $open
	 * @param null $close
	 * @return mixed
	 */
	protected function matchContent($content, $open=null, $close = null){
		$open = $open?:'[';
		$close = $close?:']';
		$key = md5($content . "{$open}{$close}");
		if(!isset($this->matchedContents[$key])){
			$regexp = $this->makeRegexp($open,$close);
			if(preg_match_all($regexp, $content, $matches, PREG_SET_ORDER)){
				$this->matchedContents[$key] = $matches;
			}else{
				$this->matchedContents[$key] = false;
			}
		}
		return $this->matchedContents[$key];
	}
	
	
	/**
	 * @return mixed
	 */
	public function getCurrentDir(){
		return $this->directoriesBreadcrumb?end($this->directoriesBreadcrumb):$this->dirname;
	}
	
	/**
	 * @param $path
	 * @return Layout|null
	 */
	public function inc($path){
		if(in_array(substr($path,0,1), [ '/', "\\"])){
			$path = $this->dirname . $path;
		}else{
			$path = $this->getCurrentDir() . '/' . $path;
		}
		return $this->parseForLayout($path, false);
	}
	
	/**
	 * @param $path
	 * @return Layout|null
	 */
	public function parseForLayout($path, $enableRelative = false){
		if($enableRelative && substr($path, 0, 1) === '/'){
			$path = $this->dirname . $path;
		}
		if(($content = $this->loadFile($path))!==false){
			try{
				$this->directoriesBreadcrumb[] = dirname($path);
				return $this->buildLayout($content);
			}finally{
				array_pop($this->directoriesBreadcrumb);
			}
		}
		return null;
	}
	
	/**
	 * @param $path
	 * @param bool $enableRelative
	 * @return Layer|null
	 */
	public function parseForLay($path, $enableRelative = false){
		
		if($enableRelative && substr($path, 0, 1) === '/'){
			$path = $this->dirname . $path;
		}
		
		if(($content = $this->loadFile($path))!==false){
			try{
				$this->directoriesBreadcrumb[] = dirname($path);
				return $this->buildLay($content);
			}finally{
				array_pop($this->directoriesBreadcrumb);
			}
		}
		return null;
	}
	
	/**
	 * @param $content
	 * @return Layer|null
	 */
	public function buildLay($content){
		if(!$content){
			return null;
		}
		$a = $this->parse($content);
		if(is_array($a)){
			$lay = new Layer();
			$lay->setCompositions($a);
		}else if($a instanceof Layout){
			$lay = new Layer();
			$lay->setCompositions([
				':main' => new Composition(new BlockCascade([$a]))
			]);
		}else{
			return null;
		}
		return $lay;
	}
	
	
	/**
	 * @param $content
	 * @return Composition[]|Layout|null
	 */
	public function parse($content){
		$compositions = $this->buildCompositions($content);
		if(!$compositions){
			return $this->buildLayout($content);
		}
		return $compositions;
	}
	
	
	/**
	 * @param array|string $content
	 * @return array|null
	 */
	public function buildCompositions($content){
		
		if(!$content){
			return [];
		}
		$elements = null;
		if(is_array($content)){
			$elements = $content;
		}else{
			$matches = $this->matchContent($content);
			if($matches){
				$elements = $this->processElements($matches);
			}
		}
		
		if($elements){
			$blocks = [];
			$_elements = [];
			foreach($elements as $element){
				if($element['type'] === 'tag' && $element['tag'] === 'block'){
					$blocks[] = $element;
				}else{
					$_elements[] = $element;
				}
			}
			$elements = $_elements;
			
			
			$compositions = [];
			$mainName = ':main';
			$mainBlock = null;
			foreach($blocks as $block){
				
				if(!isset($block['attributes']['name'])){
					$block['attributes']['name'] = $mainName;
				}
				$name = $block['attributes']['name'];
				if(!isset($block['attributes']['type'])){
					$block['attributes']['type'] = null;
				}
				$isMain = $name === $mainName;
				
				if(!isset($compositions[$name])){
					$compositions[$name] = [
						'target'    => null,
						'prepends'  => [],
						'appends'   => [],
					];
				}
				
				/** @var Block $block */
				$b = $this->makeBlock($block['attributes']['type']);
				if(!isset($block['content'])){
					if(isset($attributes['inc'])){
						$content = $this->inc($attributes['inc']);
					}else{
						$content = null;
					}
				}else{
					$content = $this->buildLayout($block['content']);
				}
				if($content){
					$b->setContents($content);
				}
				if($isMain){
					$mainBlock = $b;
				}
				
				switch(true){
					case $b instanceof BlockAppend;
						$compositions[$name]['appends'][] = $b;
						break;
					
					case $b instanceof BlockPrepend;
						$compositions[$name]['prepends'][] = $b;
						break;
					
					case $b instanceof BlockTarget;
						$compositions[$name]['target'] = $b;
						break;
				}
				
			}
			
			if(!$blocks){
				return [];
			}
			if($elements){
				if(!$mainBlock){
					$mainBlock = new BlockCascade();
				}
				
				$layout = $this->buildLayout($elements);
				if($layout){
					$mainBlock->setContents([$layout], true);
					if(!isset($compositions[$mainName])){
						$compositions[$mainName] = [
							'target'    => null,
							'prepends'  => [],
							'appends'   => [],
						];
					}
					$compositions[$mainName]['target'] = $mainBlock;
				}
			}
			
			foreach($compositions as $name => &$composition){
				if($composition['target'] || $composition['prepends'] || $composition['appends']){
					$composition = new Composition($composition['target'], $composition['prepends'], $composition['appends']);
				}
			}
			return $compositions;
		}
		return [];
	}
	
	/**
	 * @param $content
	 * @return Layout|null
	 */
	public function buildLayout($content){
		
		$elements = null;
		if(is_array($content)){
			$elements = $content;
		}else{
			$matches = $this->matchContent($content);
			if($matches){
				$elements = $this->processElements($matches);
			}
		}
		if($elements){
			$layout = new Layout();
			$els = [];
			foreach($elements as $element){
				if($element['type'] === 'tag' && $element['tag'] === 'holder'){
					
					$name = isset($element['attributes']['name'])?$element['attributes']['name']:null;
					
					$holder = $this->makeHolder();
					$holder->name = $name;
					if($element['content']){
						$l = $this->buildLayout($element['content']);
						$holder->targets[] = new BlockCascade([$l]);
					}
					$els[] = $holder;
				}else if($element['type'] == 'plain'){
					if(trim($element['content'])){
						$els[] = new SimpleElement(ltrim($element['content'], "\r\n"));
					}
				}
			}
			
			if(!$els){
				return null;
			}
			
			foreach($els as $el){
				$layout->add($el);
			}
			
			return $layout;
		}
		return null;
	}
	
	/**
	 * @param $definition
	 * @return array
	 */
	public function parseAttributes($definition){
		$attributes = [];
		if(preg_match_all($this->makeRegexpAttributes(), $definition, $matches, PREG_SET_ORDER)){
			
			foreach($matches as $match){
				$key = $match[1];
				if(!empty($match[2]) || !empty($match[3])){
					$value = !empty($match[2])?$match[2]:$match[3];
					switch(true){
						case strcasecmp($value,'false')==0: $value = false; break;
						case strcasecmp($value,'true')==0:  $value = true;  break;
						case strcasecmp($value,'null')==0:  $value = null;  break;
						default:
							// удаляем слеши перед кавычками
							if(!empty($match[2])){
								$value = strtr($value, ['\\"' => '"']);
							}else{
								$value = strtr($value, ['\\\'' => '\'']);
							}
							break;
					}
				}else{
					$value = true;
				}
				
				$attributes[$key] = $value;
			}
			
		}
		return $attributes;
		
	}
	
	
	/**
	 * @param $type
	 * @return BlockAppend|BlockCascade|BlockDefine|BlockPrepend|BlockReplace
	 * @throws \Exception
	 */
	public function makeBlock($type){
		
		switch($type){
			
			case 'prepend':
				$block = new BlockPrepend();
				break;
			case 'append':
				$block = new BlockAppend();
				break;
			
			case 'define':
				$block = new BlockDefine();
				break;
			case 'replace':
				$block = new BlockReplace();
				break;
			
			case 'cascade':
			case null:
				$block = new BlockCascade();
				break;
			
			default:
				throw new \Exception("Unknown block type '{$type}'");
				break;
			
		}
		return $block;
	}
	
	/**
	 * @return Holder
	 */
	public function makeHolder(){
		return new Holder();
	}
	
	
	public function processElements(array $matches){
		$elements = [];
		foreach($matches as $m){
			switch(true){
				case !empty($m[1]):
					
					$elements[] = [
						'type' => 'plain',
						'content' => $m[1]
					];
					
					break;
				case !empty($m[2]): // with content
					
					$tagName    = $m[2];
					$attributes = $m[3];
					$content    = $m[8];
					
					$elements[] = [
						'type'       => 'tag',
						'tag'        => $tagName,
						'attributes' => $this->parseAttributes($attributes),
						'content'    => $content,
					];
					
					break;
				case !empty($m[9]): // without content
					
					$tagName    = $m[9];
					$attributes = $m[10];
					$content    = null;
					
					$elements[] = [
						'type'       => 'tag',
						'tag'        => $tagName,
						'attributes' => $this->parseAttributes($attributes),
						'content'    => $content,
					];
					
					break;
			}
		}
		return $elements;
	}
	
}


