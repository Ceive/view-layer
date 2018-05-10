<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler;


use Ceive\View\Layer\Block;

class Syntax{
	
	
	public function regexpJSX(){
		return <<<PCRE
@((?:"(?:(?:\\\\\\\\|\\\\"|[^"]++)*)"|'(?:(?:\\\\\\\\|\\\\'|[^']++)*)')|[^<]+)
|
<([\w\-\.]+)
  (?: \s+ ((\w+)(?:=(?:
    "((?:\\\\\\\\|\\\\"|[^"]++)*)"|
    '((?:\\\\\\\\|\\\\'|[^']++)*)'|
    \{((?:"(?:(?:\\\\\\\\|\\\\"|[^"]++)*)"|'(?:(?:\\\\\\\\|\\\\'|[^']++)*)')|[^\{\}]+|\{(?-1)*\})\}
  ))? ) )*
>
  ((?R)*)
</\g{-7}>
|
<([\w\-\.]+)
(?: \s+ ((\w+)(?:=(?:
    "((?:\\\\\\\\|\\\\"|[^"]++)*)"|
    '((?:\\\\\\\\|\\\\'|[^']++)*)'|
    \{((?:"(?:(?:\\\\\\\\|\\\\"|[^"]++)*)"|'(?:(?:\\\\\\\\|\\\\'|[^']++)*)')|[^\{\}]+|\{(?-1)*\})\}
  ))? ) )*
/>@mxs
PCRE;
	}
	
	public function regexpBlocks(){
		return <<<PCRE
@(\\\\\{\{:|[^\{\{]++|\{(?!\{:block)) 
| 
\{\{:(block)
  ((?: \s+ (\w+(?:=(?:"((?:\\\\\\\\|\\\\"|[^"]++)*)"|'((?:\\\\\\\\|\\\\'|[^']++)*)'))? ) )*)
:\}\}
  ((?R)*)
\{\{:/\g{-6}:\}\} 
| 
\{\{:(block)((?: \s+ (\w+(?:=(?:"((?:\\\\\\\\|\\\\"|[^"]++)*)"|'((?:\\\\\\\\|\\\\'|[^']++)*)'))? )
)*)/:\}\}@msx
PCRE;
	}
	
	public function regexpHolders(){
		return <<<PCRE
@(\\\\\{\{:|[^\{\{]++|\{(?!\{:holder)) 
| 
\{\{:(holder)
  ((?: \s+ (\w+(?:=(?:"((?:\\\\\\\\|\\\\"|[^"]++)*)"|'((?:\\\\\\\\|\\\\'|[^']++)*)'))? ) )*)
:\}\}
  ((?R)*)
\{\{:/\g{-6}:\}\} 
| 
\{\{:(holder)((?: \s+ (\w+(?:=(?:"((?:\\\\\\\\|\\\\"|[^"]++)*)"|'((?:\\\\\\\\|\\\\'|[^']++)*)'))? )
)*)/:\}\}@msx
PCRE;
	}
	
	public function regexpAttribute(){
		return '@(\w+)(?:=(?:"((?:\\\\\\\\|\\\\"|[^"]++)*)"|\'((?:\\\\\\\\|\\\\\'|[^\']++|)*)\'))?@mxs';
	}
	
	/**
	 * @param $string
	 * @return array|bool
	 */
	public function matchBlocks($string){
		
		if(preg_match_all($this->regexpBlocks(), $string, $matches, PREG_SET_ORDER)){
			$contents = [];
			$plain = null;
			foreach($matches as $match){
				switch(true){
					case !empty($match[1]):
						if(!isset($plain)) $plain = '';
						$plain .= $match[0];
						break;
					case !empty($match[2]):
						if(isset($plain)){
							$contents[] = $plain;
							$plain = null;
						}
						$el = [
							'type'       => $match[2],
							'attributes' => isset($match[3])? $this->_attributes($match[3]): [],
							'children'   => isset($match[7]) ?$match[7]: null,
						];
						
						$contents[] = $el;
						
						break;
					case !empty($match[8]):
						if(isset($plain)){
							$contents[] = $plain;
							$plain = null;
						}
						$el = [
							'type'       => $match[8],
							'attributes' => isset($match[9])? $this->_attributes(trim($match[9])): [],
							'children'   => null,
						];
						$contents[] = $el;
						break;
				}
				
				
			}
			return $contents;
		}
		return false;
	}
	
	/**
	 * @param $string
	 * @return array|null
	 */
	public function matchBlockContent($string){
		if(preg_match_all($this->regexpHolders(), $string, $matches, PREG_SET_ORDER)){
			$contents = [];
			$plain = null;
			foreach($matches as $match){
				switch(true){
					case !empty($match[1]):
						if(!isset($plain)) $plain = '';
						$plain .= $match[0];
						break;
					case !empty($match[2]):
						if(isset($plain)){
							$contents[] = $plain;
							$plain = null;
						}
						
						$el = [
							'type'       => $match[2],
							'attributes' => isset($match[3])? $this->_attributes($match[3]) : [],
							'children'   => isset($match[7])? $match[7] : null,
						];
						
						$contents[] = $el;
						
						break;
					case !empty($match[8]):
						if(isset($plain)){
							$contents[] = $plain;
							$plain = null;
						}
						$el = [
							'type'       => $match[8],
							'attributes' => isset($match[9])? $this->_attributes(trim($match[9])): [],
							'children'   => null,
						];
						$contents[] = $el;
						break;
				}
				
				
			}
			
			return $contents;
		}
		return null;
	}
	
	public function replaceBlockContent($content, callable $fn){
		
		$regexp = $this->regexpHolders();
		return preg_replace_callback($regexp, function($m) use($fn){
			
			switch(true){
				case !empty($m[1]):
					if(!isset($plain)) $plain = '';
					return $fn($m[0], null);
					break;
				case !empty($m[2]):
					if(isset($plain)){
						$contents[] = $plain;
						$plain = null;
					}
					
					$el = [
						'type'       => $m[2],
						'attributes' => isset($m[3])? $this->_attributes($m[3]) : [],
						'children'   => isset($m[7])? $m[7] : null,
					];
					return $fn($m[0], $el);
					break;
				case !empty($m[8]):
					if(isset($plain)){
						$contents[] = $plain;
						$plain = null;
					}
					$el = [
						'type'       => $m[8],
						'attributes' => isset($m[9])? $this->_attributes(trim($m[9])): [],
						'children'   => null,
					];
					return $fn($m[0], $el);
					break;
			}
			return $m[0];
		}, $content);
	}
	
	protected function _attributes($string){
		$attributes = [];
		if(preg_match_all($this->regexpAttribute(), $string, $matches, PREG_SET_ORDER)){
			
			foreach($matches as $match){
				$key = $match[1];
				$value = $match[2] ?: $match[3] ?: null;
				
				
				switch($value){
					case null:
						$value = true;
						break;
					case 'null':
						$value = null;
						break;
					case 'true':
						$value = null;
						break;
					case 'false':
						$value = null;
						break;
					default:
						switch(true){
							case is_numeric($value):
								$value = strpos($value, '.') !== false ? floatval($value) : intval($value);
								break;
							default:
								// удаляем слеши перед кавычками
								if(!empty($match[2])){
									$value = strtr($value, [ '\\"' => '"' ]);
								}else{
									$value = strtr($value, [ '\\\'' => '\'' ]);
								}
								break;
						}
				}
				$attributes[$key] = $value;
			}
		}
		return $attributes;
	}
	
	
}


