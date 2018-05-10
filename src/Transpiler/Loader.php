<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler;


class Loader extends BaseAware{
	
	/**
	 * Корневая папка относительно которой происходит загрузка
	 * @var string
	 */
	public $base;
	
	/** @var array */
	protected $loadStack = [];
	
	public $extension = 'mlv';
	
	/**
	 * @param $path
	 * @param callable $onLoad
	 * @param bool $absolute if true $path is absolute, else $path is relative from base or dir
	 * @return mixed
	 */
	public function loadWrapped($path, callable $onLoad = null, $absolute = false){
		try{
			
			if(!$absolute){
				$absolute = $this->normalizePath($path);
				//$absolute = $absolute . '.' . $this->extension;
			}else{
				$absolute = $path;
			}
			
			
			
			$this->startLoadingScope($absolute, $path);
			$content = $this->load($absolute);
			if($onLoad){
				return $onLoad($absolute, $content);
			}
			return $content;
		}finally{
			$this->endLoadingScope();
		}
	}
	
	/**
	 * @param $path
	 * @return string
	 * @throws \Exception
	 */
	public function load($path){
		if(!file_exists($path)){
			throw new \Exception("File not exists {$path}");
		}
		$content = file_get_contents($path);
		return $content;
	}
	
	/**
	 * @param $absolutePath
	 * @param $path
	 */
	public function startLoadingScope($absolutePath, $path){
		$this->loadStack[] = [$absolutePath, $path];
	}
	
	/**
	 *
	 */
	public function endLoadingScope(){
		array_pop($this->loadStack);
	}
	
	
	
	/**
	 * @param $src
	 * @param $dest
	 * @param null $restrictBase
	 * @param null $ds
	 * @return array|bool|null|string
	 */
	public function relativePathThroughDots($src, $dest, $restrictBase = null, $ds = null){
		$srcDir = strtr( dirname($src), ['\\' => '/']);
		$destDir = strtr( dirname($dest), ['\\' => '/']);
		if($restrictBase)$restrictBase = strtr( $restrictBase, ['\\' => '/']);
		if(!$ds)$ds = DIRECTORY_SEPARATOR;
		
		$branchPoint = '';
		$cut = 0;
		
		$minLength = min(strlen($srcDir),strlen($destDir));
		
		for($i=0;($srcToken = @$srcDir{$i}) && ($destToken = @$destDir{$i}) && $srcToken === $destToken;$i++){
			$branchPoint.=$srcToken;
			
			if($srcToken == '/'){
				$cut = 0;
			}else{
				$cut++;
			}
			
		}
		
		
		if($cut && $minLength > strlen($branchPoint)){
			$branchPoint = substr($branchPoint, 0, -$cut);
		}
		
		if($restrictBase && substr($branchPoint, 0, strlen($restrictBase)) !== $restrictBase){
			return false;
		}
		
		$srcSuffix = trim(substr($srcDir, strlen($branchPoint)), '/');
		$destSuffix = trim(substr($destDir, strlen($branchPoint)), '/');
		
		$path = $srcSuffix? array_fill( 0, count(explode('/',$srcSuffix)), '..'): null;
		$path = ($path?implode('/', $path):'.') . ($destSuffix? '/' . str_replace(['\\'],['/'],$destSuffix) :'') . '/' . basename($dest);
		
		return $path? strtr( $path, ['\\' => $ds, '/' => $ds]): $path;
	}
	
}


