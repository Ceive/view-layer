<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Node;


class Babel{
	
	public $dirname;
	
	public $config = [];
	
	public $basename = '.babelrc';
	
	public $loaded = false;
	
	public function __construct($dirname){
		$this->dirname = $dirname;
		
		$babelPath = $this->dirname . '/' . $this->basename;
		
		if(file_exists($babelPath)){
			$this->load($babelPath);
		}
		
	}
	
	/**
	 * @param array ...$preset
	 * @return $this
	 */
	public function preset(...$preset){
		foreach($preset as $prst){
			if(is_array($prst)){
				call_user_func_array([$this,'preset'], $prst);
			}else{
				if(!isset($this->config['presets'])){
					$this->config['presets'] = [];
				}
				$this->config['presets'][] = $prst;
				array_unique($this->config['presets']);
			}
		}
		return $this;
	}
	
	/**
	 * @param $name
	 * @param array $options
	 * @return $this
	 */
	public function plugin($name, array $options){
		$this->config['plugins'][] = [$name, $options];
		array_unique($this->config['plugins']);
		return $this;
	}
	
	/**
	 * @param $path
	 * @return $this
	 */
	public function save($path){
		$json = json_encode((object)$this->config,JSON_PRETTY_PRINT);
		file_put_contents($path, $json);
		return $this;
	}
	
	public function load($path){
		if(file_exists($path)){
			$json = file_get_contents($path);
			$json = json_decode($json, true);
			$this->config = $json;
			$this->loaded = true;
		}else{
			throw new \Exception("Not Found: Could not load package.json by '{$path}'");
		}
		return $this;
	}
	
	public function build(){
		$babelPath = $this->dirname . '/' . $this->basename;
		$this->save($babelPath);
	}
	
}


