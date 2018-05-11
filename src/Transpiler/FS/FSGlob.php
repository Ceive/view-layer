<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler\FS;


/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class FSGlob
 * @package Ceive\View\Layer
 *
 * Как способ обойти одну директирию рекурсивно и сделать что то с файлами в ней.
 * Например перенести.
 * Например копировать.
 * Например обработать и создать файл в другой папке.
 * Например провести через фильтр.
 * Например провести через конвертер.
 *
 */
class FSGlob{
	
	const DS_LIST = '\/';
	const DS = DIRECTORY_SEPARATOR;
	
	public $base;
	public $onFile;
	public $onDir;
	
	//public $srcBase;
	//public $dstBase;
	
	
	public function __construct($base, callable $onFile = null, callable $onDir = null){
		$this->base   = rtrim($base, self::DS_LIST);
		$this->onFile = $onFile;
		$this->onDir  = $onDir;
	}
	
	/**
	 * @param null $dir
	 */
	public function process($dir = null){
		if(!$dir) $dir = $this->base;
		
		foreach(glob($dir . DIRECTORY_SEPARATOR . '*') as $path){
			$rel = $this->cutBase($path, $this->base);
			if(is_dir($path)){
				if($this->onDir($rel, $path)!==false){
					$this->process($path);
				}
			}else{
				$this->onFile($rel, $path);
			}
		}
		
	}
	
	/**
	 * @param $rel - relative from this.base
	 * @param $path
	 * @return mixed|null
	 */
	public function onDir($rel, $path){
		return $this->onDir? call_user_func($this->onDir, $rel, $path): null;
	}
	
	/**
	 * @param $rel - relative from this.base
	 * @param $path
	 * @return mixed|null
	 */
	public function onFile($rel, $path){
		return $this->onFile? call_user_func($this->onFile, $rel, $path): null;
	}
	
	public static function cutBase($abs, $base){
		$len = strlen($base);
		if(substr($abs, 0 , $len) !== $base){
			throw new \Exception('cutBase: Wrong path  "'.$abs.'"; is not contain in base "'.$base.'"');
		}
		return substr($abs,strlen($base));
	}
	
	/**
	 * @param $rel - relative from this.base
	 * @param $base
	 * @return string
	 */
	public static function padBase($rel, $base){
		return self::path(null, [$base, $rel]);
	}
	
	const NORMALIZE_SIMPLE  = 'simple';
	const NORMALIZE_DOUBLES = 'doubles';
	
	
	/**
	 * @param $separator
	 * @param array $segments
	 * @return string
	 */
	public static function p($separator, ...$segments){
		if(!in_array($separator,['\\','/'])){
			array_unshift($segments, $separator);
			return self::path(null, $segments);
		}else{
			return self::path($separator, $segments);
		}
	}
	
	/**
	 * @param $sep
	 * @param array $chunks
	 * @param bool $normalize
	 * @return string
	 */
	public static function path($sep, array $chunks, $normalize = false){
		if(!$sep) $sep= self::DS;
		if($normalize === true) $normalize = null;
		$i=0;
		$chunks = array_filter($chunks);
		$cc = count($chunks);
		foreach($chunks as &$v){
			if($i==0){
				$v = rtrim($v,self::DS_LIST);
			}else if($i>=$cc-1){
				$v = ltrim($v,self::DS_LIST);
			}else{
				$v = trim($v,self::DS_LIST);
			}
			$i++;
		}
		$chunks = implode($sep, $chunks);
		return $normalize===false?$chunks: self::normalize($chunks, $sep, $normalize);
	}
	
	/**
	 * @param $path
	 * @param null $sep
	 * @param null $mode
	 * @return mixed|string
	 */
	public static function normalize($path, $sep = null, $mode = null){
		if(!$sep) $sep= self::DS;
		if($mode === self::NORMALIZE_DOUBLES){
			return preg_replace('@\\+|/+@', $sep, $path);
		}else{
			return strtr($path, [ '\\' => $sep, '/' => $sep]);
		}
		
	}
	
}


