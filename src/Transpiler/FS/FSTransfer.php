<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler\FS;


class FSTransfer{
	
	/** @var  FSGlob */
	public $glob;
	
	public $srcBase;
	public $dstBase;
	
	public $tasks = [];
	
	public $listener;
	
	public function task($pattern, callable $handler){
		$this->tasks[] = [!is_array($pattern)?[$pattern]:$pattern, $handler];
		return $this;
	}
	
	public function setListener(callable $fn = null){
		$this->listener = $fn;
		return $this;
	}
	
	public function __construct($srcBase, $dstBase){
		
		$this->srcBase = $srcBase;
		$this->dstBase = $dstBase;
		
		
		$glob = new FSGlob($this->srcBase);
		$glob->onDir = [$this, 'onDir'];
		$glob->onFile = [$this, 'onFile'];
		$this->glob = $glob;
		
	}
	
	public function onDir($rel, $path){
		$dst = $this->glob->padBase($rel, $this->dstBase);
		if(!$this->listener || (call_user_func($this->listener,  $path, $dst))!==false){
			foreach($this->tasks as list($patterns, $handler)){
				foreach($patterns as $pattern){
					if($pattern === '/'){
						call_user_func($handler, $path, $dst);
						break;
					}
				}
			}
		}
		
		
		
	}
	
	public function onFile($rel, $path){
		
		$dst = $this->glob->padBase($rel, $this->dstBase);
		if(!$this->listener || (call_user_func($this->listener,  $path, $dst))!==false){
			foreach($this->tasks as list($patterns, $handler)){
				foreach($patterns as $pattern){
					if($pattern === '/'){
						continue;
					}
					if(fnmatch($pattern, $rel)){
						if(call_user_func($handler, $path, $dst) === false){
							break(2);
						}
						break;
					}
				}
			}
		}
	}
	
	
	
}


