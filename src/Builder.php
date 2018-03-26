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
	
	public $parsed = [];
	
	
	public $directoriesUsageHistory = [];
	
	/**
	 * @param array $definition
	 * @return Lay|null
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
				$layer = $this->parseLayFile($layer, true);
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
	 * @return Layout|null
	 */
	public function parseLayoutFile($path, $enableRelative = false){
		
		if($enableRelative && substr($path, 0, 1) === '/'){
			$path = $this->dirname . $path;
		}
		
		if(file_exists($path)){
			try{
				$content = file_get_contents($path);
				$this->directoriesUsageHistory[] = dirname($path);
				return $this->parseLayout($content);
			}finally{
				array_pop($this->directoriesUsageHistory);
			}
		}
		return null;
	}
	
	/**
	 * @param $path
	 * @param bool $enableRelative
	 * @return Lay|null
	 */
	public function parseLayFile($path, $enableRelative = false){
		
		if($enableRelative && substr($path, 0, 1) === '/'){
			$path = $this->dirname . $path;
		}
		
		if(file_exists($path)){
			try{
				$content = file_get_contents($path);
				$this->directoriesUsageHistory[] = dirname($path);
				return $this->parseLay($content);
			}finally{
				array_pop($this->directoriesUsageHistory);
			}
		}
		return null;
	}
	
	
	/**
	 * @param $content
	 * @return Composition[]|Layout|null
	 */
	public function parse($content){
		$compositions = $this->parseCompositions($content);
		if(!$compositions){
			return $this->parseLayout($content);
		}
		return $compositions;
	}
	
	/**
	 * @param $content
	 * @return Lay|null
	 */
	public function parseLay($content){
		
		if(!$content){
			return null;
		}
		
		$a = $this->parse($content);
		if(is_array($a)){
			$lay = new Lay();
			$lay->setCompositions($a);
		}else if($a instanceof Layout){
			$lay = new Lay();
			$lay->setCompositions([
				':main' => new Composition(new BlockCascade([$a]))
			]);
		}else{
			return null;
		}
		return $lay;
	}
	
	public function getRegex(){
		$regex = /** @lang RegExp */
			'@
		\[(([\w\-]+)(\s*([\w_\-]+)=(\'|")[^\'"\\\\]+\g{-1})*)/\]|
		\[(([\w\-]+)(\s*([\w_\-]+)=(\'|")[^\'"\\\\]+\g{-1})*)\]
			((?R)*)
		\[/\g{-5}\]|
		<script[^>]+>[^<]+</script>|
		<style[^>]+>[^<]+</style>|
		[^\[]+@smx';
		return $regex;
	}
	
	/**
	 * @param $content
	 * @return array|null
	 */
	public function parseCompositions($content){
		
		if(!$content){
			return [];
		}
		
		$regex = $this->getRegex();
		
		$compositions = [];
		
		if(preg_match_all($regex,$content, $matches, PREG_SET_ORDER)){
			
			
			foreach($matches as $m){
				
				$block  = null;
				$name   = null;
				
				switch(true){
					
					case !empty($m[1]):
						$o = isset($m[1])?$m[1]:null;
						list($name, $block) = $this->parseBlock($o, null);
						break;
					case !empty($m[6]):
						$o = $m[6];
						$c = isset($m[11])?$m[11]:null;
						list($name, $block) = $this->parseBlock($o, $c);
				}
				
				
				if($block){
					
					if(!$name){
						$name = ':main';
					}
					
					if(!isset($compositions[$name])){
						$compositions[$name] = [
							'target'    => null,
						    'prepends'  => [],
						    'appends'   => [],
						];
					}
					
					switch(true){
						case $block instanceof BlockAppend; $compositions[$name]['appends'][] = $block; break;
						case $block instanceof BlockPrepend; $compositions[$name]['prepends'][] = $block; break;
						case $block instanceof BlockTarget; $compositions[$name]['target'] = $block; break;
					}
				}
			}
			
		}
		
		foreach($compositions as $name => &$composition){
			if($composition['target'] || $composition['prepends'] || $composition['appends']){
				$composition = new Composition($composition['target'], $composition['prepends'], $composition['appends']);
			}
		}
		
		return $compositions;
	}
	
	/**
	 * @param $content
	 * @return Layout|null
	 */
	public function parseLayout($content){
		
		if(!$content){
			return null;
		}
		
		$regex = $this->getRegex();
		
		$layout = new Layout();
		
		if(preg_match_all($regex,$content, $matches, PREG_SET_ORDER)){
			
			
			foreach($matches as $m){
				
				
				switch(true){
					
					case !empty($m[1]):
						$o = isset($m[1])?$m[1]:null;
						$holder = $this->parseHolder($o, null);
						if($holder){
							$layout->add($holder);
						}
						break;
					case !empty($m[6]):
						$o = $m[6];
						$c = isset($m[11])?$m[11]:null;
						$holder = $this->parseHolder($o, $c);
						if($holder){
							$layout->add($holder);
						}
						break;
					default:
						if(trim($m[0])){
							$layout->add( new SimpleElement(ltrim($m[0], "\r\n")));
						}
						break;
				}
			}
			
		}
		
		return $layout;
	}
	
	
	/**
	 * @return string
	 */
	public function getTagAttributeRegex(){
		return '([\w_\-]+)=(\'|")(\\\\\\\\|\\\\\g{-2}|[^\\\\])*\g{-2}';
	}
	
	/**
	 * @return string
	 */
	public function getTagRegex(){
		return '([\w_\-]+)\s*';
	}
	
	
	/**
	 * @param $definition
	 * @return array [type,name,additions[]]
	 */
	public function parseTag($definition){
		
		$name       = null;
		$attributes = [];
		
		if(preg_match('@([\w_\-]+)\s*(.*)@', $definition, $m)){
			$name = $m[1];
			$attributes = $this->parseAttributes($m[2]);
		}
		return [$name, $attributes];
		
	}
	
	/**
	 * @return mixed
	 */
	public function getCurrentDir(){
		return $this->directoriesUsageHistory?end($this->directoriesUsageHistory):$this->dirname;
	}
	
	/**
	 * @param $definition
	 * @param $content
	 * @return array [name, block]
	 * @throws \Exception
	 */
	public function parseBlock($definition, $content){
		
		list($tag, $attributes) = $this->parseTag($definition);
		
		if($tag !== 'block'){
			return [null,null];
		}
		
		$name = isset($attributes['name'])?$attributes['name']:null;
		$type = isset($attributes['type']) && $attributes['type']?$attributes['type']:null;
		if(!isset($content)){
			$include = isset($attributes['inc'])?$attributes['inc']:null;
			if($include)$content = $this->inc($include);
		}else{
			$content = $this->parseLayout($content);
		}
		
		
		
		switch($type){
			
			case 'prepend':
				$block = new BlockPrepend([$content]);
				break;
			case 'append':
				$block = new BlockAppend([$content]);
				break;
			
			case 'define':
				$block = new BlockDefine([$content]);
				break;
			case 'replace':
				$block = new BlockReplace([$content]);
				break;
			
			case 'cascade':
			case null:
				$block = new BlockCascade([$content]);
				break;
				
				
			default:
				throw new \Exception("Unknown block type '{$type}'");
				break;
			
		}
		
		return [$name, $block];
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
		return $this->parseLayoutFile($path, false);
	}
	
	/**
	 * @param $definition
	 * @param $content
	 * @return BlockHolder|null
	 */
	public function parseHolder($definition, $content){
		
		list($tag, $attributes) = $this->parseTag($definition);
		
		if($tag !== 'holder'){
			return null;
		}
		
		$name = isset($attributes['name'])?$attributes['name']:null;
		$holder = new BlockHolder($name);
		if($content){
			$layout = $this->parseLayout($content);
			$holder->addTarget(new BlockCascade([$layout]));
		}
		
		return $holder;
	}
	
	public function parseAttributes($definition){
		$attributes = [];
		if(preg_match_all('@([\w_\-]+)(?:=(\'|")((?:\\\\\\\\|\\\\\g{-2}|[^\\\\])*?)\g{-2})?@', $definition, $matches, PREG_SET_ORDER)){
			
			
			foreach($matches as $match){
				
				$key = $match[1];
				
				
				if(!isset($match[3])){
					$value = true;
				}else{
					$value = $match[3]==='false'?false:$match[3];
				}
				
				$attributes[$key] = $value;
			}
			
		}
		return $attributes;
		
	}
	
	
	
}


