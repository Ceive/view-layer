<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Node;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class Package
 * @package Ceive\View\Layer\Node
 */
class Package{
	
	public $dirname;
	
	public $config = [];
	
	public $configName = 'package.json';
	
	public $configLoaded = false;
	
	public function __construct($dirname){
		$this->dirname = rtrim($dirname);
		$this->loadJson();
	}
	
	/**
	 *
	 */
	public function loadJson(){
		$jsonPath = $this->getJsonPath();
		if(file_exists($jsonPath)){
			$this->loadConfig($jsonPath);
		}
	}
	
	public function saveJson(){
		$jsonPath = $this->getJsonPath();
		$this->saveConfig($jsonPath);
	}
	
	public function getJsonPath(){
		return rtrim($this->dirname) . DIRECTORY_SEPARATOR . ltrim($this->configName);
	}
	
	
	
	public function initial($name, $description = '', $version = '0.0.1'){
		$this->config['name']          = $name;
		$this->config['description']   = $description;
		$this->config['version']       = $version;
		return $this;
	}
	
	public function setPrivate($private = true){
		$this->config['private'] = $private;
		return $this;
	}
	
	public function script($key, $npmCommand){
		if(!isset($this->config['scripts'])){
			$this->config['scripts'] = [];
		}
		$this->config['scripts'][$key] = $npmCommand;
	}
	
	public function dependency($package, $version = null, $overwrite = false){
		if(!isset($this->config['dependencies'])){
			$this->config['dependencies'] = [];
		}
		
		if($overwrite || !array_key_exists( $package, $this->config['dependencies'])){
			$this->config['dependencies'][$package] = (string)$version;
		}
		
		return $this;
	}
	public function devDependency($package, $version = null, $overwrite = false){
		if(!isset($this->config['devDependencies'])){
			$this->config['devDependencies'] = [];
		}
		
		if($overwrite || !array_key_exists( $package, $this->config['devDependencies'])){
			$this->config['devDependencies'][$package] = (string)$version;
		}
		
		return $this;
	}
	
	/**
	 * @param $path
	 * @return $this
	 */
	public function saveConfig($path){
		$json = json_encode((object)$this->config,JSON_PRETTY_PRINT);
		file_put_contents($path, $json);
		return $this;
	}
	
	public function loadConfig($path){
		if(file_exists($path)){
			$json = file_get_contents($path);
			$json = json_decode($json, true);
			$this->config = $json;
			$this->configLoaded = true;
		}else{
			throw new \Exception("Not Found: Could not load package.json by '{$path}'");
		}
		return $this;
	}
	
	/**
	 * @param $npmCommand
	 * @return string
	 */
	public function npm($npmCommand){
		$output = exec("npm {$npmCommand}");
		
		
		return $output;
	}
	
	public function update(){
		$this->npm('update');
		return $this;
	}
	
	public function install(){
		$this->npm('install');
		return $this;
	}
	
	/**
	 * @return $this
	 */
	public function build(){
		if(!is_dir($this->dirname)){
			mkdir($this->dirname, 0777, true);
		}
		
		$this->saveJson();
		
		if($this->configLoaded && is_dir($this->dirname . '/node_modules') && glob($this->dirname . '/node_modules/*')){
			$this->update();
		}else{
			$this->install();
		}
		
		return $this;
	}
	
}


