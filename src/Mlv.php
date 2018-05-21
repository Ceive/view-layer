<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;


use Ceive\View\Layer\Node\PackageGenerator;
use Ceive\View\Layer\Transpiler\FS\FSGlob;
use Ceive\View\Layer\Transpiler\Script\ES6FileGenerator;
use Ceive\View\Layer\Transpiler\Transpiler;

class Mlv implements \ArrayAccess{
	
	protected $_config;
	
	public $transpiler;
	
	public function __construct(array $config = []){
		
		$this->setConfig(array_replace([
			'appRoot'              =>   null,
			'webRoot'              =>   '@appRoot/web',
			'views.src'            =>   '@appRoot/views',
			'views.main'           =>   'index.html',
			'views.entryPoint'     =>   'index.js',
			'client.src'           =>   '@appRoot/webSrc',
			'client.entryPoint'     =>  'index.js',
			'client.dist'          =>   '@appRoot/web/build',
			'client.buildCmd'      =>   'npm run build',
			'client.jsBundleName'  =>   'bundle.js',
			'client.cssBundleName' =>   'bundle.css',
			
			'html.rootID'         => 'main'
		], $config));
		$this->transpiler = new Transpiler($this['views.src'], $this['client.src'], null, $this->layerManager);
	}
	
	
	public function interpret(){
		// Compile MLV
		$transpiler = $this->transpiler;
		$transpiler->entryPoint     = $this['views.entryPoint'];
		$transpiler->layerManagerJs = 'layerManager.js';
		$transpiler->onMainSave = function(ES6FileGenerator $script){
			$script->header()->code(
				<<<JS
				
import React from 'react';
import ReactDOM from 'react-dom';

window['Mlv'] = layerManager;

class App extends React.Component{
	
	componentDidMount(){
		window['Mlv'].onChainUpdate = () => {
			this.setState({
				actualLayers: layerManager.keys
			});
		};
	}
	
	constructor(props){
		super(props);
		this.state = {
			actualLayers: []
		};
	}
	
	render(){
		return <div className="App">{ layerManager.chain? layerManager.chain.getContents():null }</div>;
	}
}

ReactDOM.render(<App/>, document.getElementById('{$this['html.rootID']}'));
JS
			);
		};
		$transpiler->process();
		if($transpiler->_touched){
			$this->_build();
		}
	}
	
	/**
	 * @param array $layersChain
	 * @return mixed|string
	 */
	public function html(array $layersChain){
		// Attachments to HTML
		
		$jsBundleUrl  = FSGlob::cutBase(
			FSGlob::normalize(FSGlob::p($this['client.dist'],$this['client.jsBundleName']),'/'),
			FSGlob::normalize($this->webRoot,'/')
		);
		
		$cssBundleUrl = FSGlob::cutBase(
			FSGlob::normalize(FSGlob::p($this['client.dist'],$this['client.cssBundleName']),'/'),
			FSGlob::normalize($this->webRoot,'/')
		);
		
		$headAssets = [ "<link rel='stylesheet' href='{$cssBundleUrl}'/>" ];
		
		
		/**
		 * @TODO: Layer.scope integration
		 */
		
		$bodyAssets = [
			"<script src='{$jsBundleUrl}'></script>",
			"<script> 
(function(){ 
	window.Mlv.setup(" . $this->exportSetup($layersChain) . ");
	
	
	// Clean the current script from page for beautiful looking a html page
	// This script is needed only at the beginning when the page is initialized
	document.currentScript.parent.removeChild(document.currentScript);
})() 
</script>"
		];
		
		
		$mainPath = FSGlob::p($this['views.src'], $this['views.main']);
		
		if(in_array( pathinfo($mainPath, PATHINFO_EXTENSION) ,['php','phtml'], true)){
			try{
				ob_start();
				include($mainPath);
			}finally{
				$main = ob_get_clean();
			}
		}else{
			$data = [
				'inBody' => $bodyAssets,
				'inHead' => $headAssets,
			];
			$main = file_get_contents($mainPath);
			$main = preg_replace_callback('@\{(\w+)\}@', function($m) use($data){
				if(!empty($m[1])){
					$key = $m[1];
					if(isset($data[$key])){
						if(is_array($data[$key])){
							return implode($data[$key]);
						}
						return $data[$key];
					}
				}
				return $m[0];
			}, $main);
		}
		return $main;
	}
	public function exportSetup(array $setup){
		return json_encode($setup, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	}
	
	/**
	 * Generate a outside program environment for cmd system
	 * @throws \Exception
	 */
	public function env($force = false){
		$pg = new PackageGenerator($this->appRoot,[
			'appRoot'   => FSGlob::normalize($this->appRoot,'/'),
			'webDir'    => FSGlob::cutBase(FSGlob::normalize($this->webRoot,'/'),FSGlob::normalize($this->appRoot,'/')),
			
			'src'       => $this['client.src'],
			'entry'     => $this['client.entryPoint'],
			
			'dist'      => $this['client.dist'],
			'distJs'    => $this['client.jsBundleName'],
			'distCss'   => $this['client.cssBundleName'],
		]);
		if($force || !$pg->checkExists()){
			$pg->generate();
		}
	}
	
	protected function _build(){
		// Client Build
		$_cwd = getcwd();
		try{
			chdir($this->appRoot);
			$result = exec($this['client.buildCmd'], $output, $code);
			
			if($code!=0){
				throw new \Exception("CMD error: could not execute `{$this['client.buildCmd']}` exit code: {$code}");
			}else{
				
			}
		}finally{
			chdir($_cwd);
		}
		return implode("\n", $output);
	}
	
	public function aliased($key){
		$value = $this[$key];
		if(isset($value)){
			return $this->alias($value);
		}
		return null;
	}
	
	/**
	 * @param $alias
	 * @return mixed
	 */
	public function alias($alias){
		if(is_string($alias)){
			return preg_replace_callback('@\@([a-zA-Z][\w_\.\-]*)@',[$this, '_aliasCallback'] , $alias);
		}
		return $alias;
	}
	
	protected function _aliasCallback($m){
		if(!empty($m[1])){
			if(isset($this[$m[1]])){
				return $this->alias($this[$m[1]]);
			}
		}
		return $m[0];
	}
	
	
	
	
	
	
	
	
	
	
	/**
	 * @param array $config
	 * @param bool $merge
	 * @return $this
	 */
	public function setConfig(array $config = [], $merge = false){
		$this->_config = $merge?array_replace($this->_config, $config):$config;
		return $this;
	}
	
	/**
	 * @param null $key
	 * @param null $default
	 * @return null
	 */
	public function getConfig($key = null, $default = null){
		if(!isset($key)){
			return $this->_config;
		}else{
			if(array_key_exists($key, $this->_config)){
				return $this->_config[$key];
			}
			return $default;
		}
	}
	
	public function __get($key){
		if(array_key_exists($key, $this->_config)){
			return $this->alias($this->_config[$key]);
		}
		return null;
	}
	
	public function __set($key, $value){
		$this->_config[$key] = $value;
	}
	
	public function __isset($key){
		return isset($this->_config[$key]);
	}
	
	public function __unset($key){
		unset($this->_config[$key]);
	}
	
	public function offsetExists($offset){
		return $this->__isset($offset);
	}
	
	public function offsetGet($offset){
		return $this->__get($offset);
	}
	
	public function offsetSet($offset, $value){
		return $this->__set($offset, $value);
	}
	
	public function offsetUnset($offset){
		return $this->__unset($offset);
	}
	
	
	
	
}


