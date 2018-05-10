<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler;


abstract class BaseAware{
	
	public $base;
	
	/**
	 * @param $path
	 * @param bool $ds
	 * @return string
	 */
	public function normalizePath($path, $ds = false){
		if($ds === null) $ds = DIRECTORY_SEPARATOR;
		
		$base = null;
		if(in_array( substr($path,0,1), ["\\",'/'])){
			//path from $this->base;
			$base = $this->base;
		}else{
			//path from $this->dir();
			$base = $this->dir();
		}
		if($ds!==false){
			$base = strtr($base, ['\\' => $ds, '/' => $ds]);
			$path = strtr($path, ['\\' => $ds, '/' => $ds]);
		}
		
		$ds=$ds===false? DIRECTORY_SEPARATOR : $ds ;
		
		return rtrim($base, "\/") . $ds .ltrim($path,"\/");
	}
	
	
	public function dir(){
		return $this->base;
	}
	
	public function relative($absolutePath){
		return ltrim(substr($absolutePath, strlen($this->base)),'\/');
	}
	
	/**
	 * @param $relative
	 * @return string
	 */
	public function absolute($relative){
		return rtrim($this->base, '\/') . DIRECTORY_SEPARATOR . ltrim($relative, '\/');
	}
}


