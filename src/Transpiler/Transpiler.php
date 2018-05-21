<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Transpiler;


use Ceive\View\Layer\Block;
use Ceive\View\Layer\Holder;
use Ceive\View\Layer\Layer;
use Ceive\View\Layer\LayerManager;
use Ceive\View\Layer\Transpiler\FS\FSGlob;
use Ceive\View\Layer\Transpiler\FS\FSTransfer;
use Ceive\View\Layer\Transpiler\Script\ES6FileGenerator;
use Ceive\View\Layer\Transpiler\Script\FileGenerator;

class Transpiler extends BaseAware{
	
	/** @var  Loader */
	public $loader;
	
	/** @var  Syntax */
	public $syntax;
	
	/**
	 * Путь относительно $base, для скрипта который импортирует все файлы слоев
	 * В этом скрипте происходит импорт каждого слоя
	 * @var string
	 */
	public $entryPoint      = '/main.js';
	
	/**
	 * Путь относительно $base, для скрипта который экспортирует js object of class LayerManager.
	 * Этот скрипт подключается в каждом файле слоя импортируя переменную layerManager
	 * @var string
	 */
	public $layerManagerJs   = '/lm.js';
	
	/**
	 * php object of class LayerManager
	 * @var  LayerManager
	 */
	public $layerManager;
	
	
	/** @var  FSTransfer */
	public $fsTransfer;
	
	public $onMainSave;
	public $onLayerManagerSave;
	
	public $_touched = [];
	
	
	protected $imports = [];
	protected $_state = [];
	
	
	
	public $mlvExtension = 'mlv';
	protected $includeExtensions = [];
	
	
	public function __construct($srcBase, $dstBase, Syntax $syntax = null, LayerManager $layerManager = null){
		
		if(!$syntax){
			$syntax = new Syntax();
		}
		
		if(!$layerManager){
			$layerManager = new LayerManager();
		}
		
		
		$this->syntax = $syntax;
		$this->layerManager = $layerManager;
		
		$this->base = $dstBase;
		$this->fsTransfer = $fs = new FSTransfer($srcBase, $dstBase);
		
		$this->loader = new Loader();
		$this->loader->base = $srcBase;
		
		$fs->setListener(function($src, $dst){
			
			if(is_dir($src) ){
				
				if(!is_dir($dst)){
					
					if(file_exists($dst)){
						throw new \Exception(
							"Could not create dir {$src} in {$dst}, destination path is busy by file or symlink, please check path"
						);
					}
					
					mkdir($dst, 0777, true);
				}
				
			}else{
				
				if(fnmatch( "*.{$this->mlvExtension}" , $src)){
					$dst = FSGlob::path('/', [dirname($dst), pathinfo($dst, PATHINFO_FILENAME).'.js']);
					
					if(file_exists($dst)){
						if(filemtime($src) > filemtime($dst)){
							unlink($dst);
						}else{
							return;
						}
					}
					
					$script = $this->processLayer($this->loader->relative($src));
					$script->path = $dst;
					
					$script->save();//TODO save control;
					
					$this->_touched[pathinfo($src,PATHINFO_EXTENSION)][$dst] = $src;
					
					$this->_state['layersMap'][$script->layer->key] = $dst;
					$this->_state['transferred'][$src] = $dst;
					return;
				}
				
				
				if(file_exists($dst)){
					if(filemtime($src) > filemtime($dst)){
						unlink($dst);
					}else{
						return;
					}
				}
				
				foreach($this->includeExtensions as $extension){
					if(fnmatch("*.{$extension}", $src)){
						$this->_touched[pathinfo($src,PATHINFO_EXTENSION)][$dst] = $src;
						copy($src, $dst);
						$this->_state['transferred'][$src] = $dst;
						break;
					}
				}
			}
			
			
		});
		
		$this->addExtensions(
			'js', 'css', 'json',
			'less', 'scss',
			'jsx', 'ts',
			
			'jpg', 'png', 'jpeg',
			'mp3', 'ogg', 'wav',
			'mp4', '3gp' , 'flv'
		);
		
		
	}
	
	public function addExtensions(...$extensions){
		foreach($extensions as $ext){
			if(is_array($ext)){
				call_user_func_array([$this, 'includeExtensions'], $ext);
			}else{
				$this->includeExtensions[] = $ext;
			}
		}
		return $this;
	}
	
	/**
	 * @param $mlvExtension
	 * @return $this
	 */
	public function setMlvExtension($mlvExtension){
		$this->mlvExtension = $mlvExtension;
		return $this;
	}
	
	/** @var  ES6FileGenerator */
	protected $mainScript;
	/** @var  ES6FileGenerator */
	protected $layerManagerScript;
	
	
	public function hasAffectedExtensions(array $extensions){
		foreach($extensions as $ext){
			if(isset($this->_touched[$ext])){
				return true;
			}
		}
		return false;
	}
	
	public function process($clear = false){
		try{
			if(!$this->_loadState() || $clear){
				$this->clear();
			}
			$this->_touched = [];
			$fs = $this->fsTransfer;
			$fs->glob->process();
			
			
			
			$forceUpdate = false;
			foreach($this->imports as $path){
				if(!isset($this->_state['imported'][$path])){
					$forceUpdate = true;
				}
			}
			
			if($forceUpdate || $this->_touched){
				
				// layerManager instance export
				$this->layerManagerScript = $managerScript = new ES6FileGenerator($this->getLayerManagerJs(), $this);
				$managerScript
					->import('Mlv, { LayerManager }', FSGlob::p(dirname(dirname(__DIR__)), 'Mlv'))
					->body()
					->code('let layerManager = new LayerManager();')
					->code('export default layerManager;');
				
				// Main script (ENTRY POINT)
				$this->mainScript = $mainScript = new ES6FileGenerator( $this->getEntryPoint() , $this);
				$mainScript->import('layerManager', $this->getLayerManagerJs() );
				
				
				foreach($this->imports as $path){
					if(file_exists($path)){
						
						$this->_state['imported'][$path] = true;
						if(isset($this->_state['transferred'][$path])){
							$path = $this->_state['transferred'][$path];
						}
						
						$mainScript->import(null, $path );
					}else{
						unset($this->_state['imported'][$path]);
						
						if(!isset($this->_state['transferred'][$path])){
							unset($this->_state['transferred'][$path]);
						}
						
						throw new \Exception("Import '{$path}' file not exists.");
						
					}
				}
				
				
				foreach($this->_state['layersMap'] as $key => $path){
					if(file_exists($path)){
						$mainScript->import(null, $path );
					}else{
						unset($this->_state['layersMap'][$key]);
					}
				}
				
				
				$mainScript
					->body()
					->code('export default layerManager;');
				
				$this->onMainSave($mainScript);
				$this->onLayerManagerSave($managerScript);
				
				$this->_touched['js'][$managerScript->path] = false;
				$this->_touched['js'][$mainScript->path]    = false;
				
				$managerScript->save();
				$mainScript->save();
			}
		}finally{
			$this->_saveState();
		}
		
	}
	
	protected function _loadState(){
		
		$mlvLock = $this->base . DIRECTORY_SEPARATOR . 'mlv.lock';
		$isExists = file_exists($mlvLock);
		if($isExists){
			$data = file_get_contents($mlvLock);
			$data = json_decode($data, true);
		}else{
			
			$data = [];
		}
		
		$this->_state = array_replace([
			'imported'     => [],
			'layersMap'     => [],
			'transferred'   => [],
		],$data);
		
		
		return $isExists;
	}
	
	protected function _saveState(){
		$mlvLock = $this->base . DIRECTORY_SEPARATOR . 'mlv.lock';
		file_put_contents($mlvLock, json_encode($this->_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
	}
	
	
	public function onMainSave(ES6FileGenerator $script){
		if(is_callable($this->onMainSave)){
			call_user_func($this->onMainSave, $script);
		}
	}
	public function onLayerManagerSave(ES6FileGenerator $script){
		if(is_callable($this->onLayerManagerSave)){
			call_user_func($this->onLayerManagerSave, $script);
		}
	}
	/**
	 * @param null $dir
	 * @return $this
	 */
	public function clear($dir = null){
		if(!$dir){
			$dir = $this->fsTransfer->dstBase;
		}
		foreach(glob(FSGlob::p($dir, '*' )) as $path){
			if(is_dir($path)){
				$this->clear($path);
			}else{
				unlink($path);
			}
		}
		return $this;
	}
	
	/**
	 * @return ES6FileGenerator
	 */
	public function makeLayerGenerator(){
		$script = new ES6FileGenerator(null, $this);
		$script
			->import('React', Raw::here('react'))
			->import('layerManager', $this->getLayerManagerJs() );
		
		return $script;
	}
	
	public function getLayerManagerJs(){
		return $this->fsTransfer->glob->padBase($this->layerManagerJs, $this->fsTransfer->dstBase);
	}
	
	public function getEntryPoint(){
		return $this->fsTransfer->glob->padBase($this->entryPoint, $this->fsTransfer->dstBase);
	}
	
	/**
	 * @param $path
	 * @return ES6FileGenerator
	 */
	public function processLayer($path){
		return $this->loader->loadWrapped($path, function($absolute, $content) use($path){
			$relative = $this->loader->relative($absolute);
			$relative = ltrim( FSGlob::path('/', [dirname($relative) , pathinfo($relative, PATHINFO_FILENAME)], true), '.\/');
			
			$layer = $this->layerManager->registerLayer($relative);
			
			$script = $this->makeLayerGenerator();
			$script->layer = $layer;
			
			$this->composeLayer($script, $layer, $content, ['file' => FSGlob::normalize($absolute,'/')]);
			
			return $script;
		});
	}
	
	/**
	 * Layer
	 * @param FileGenerator $output
	 * @param Layer $layer
	 * @param $raw
	 * @param null $source
	 */
	public function composeLayer(FileGenerator $output, Layer $layer, $raw, $source = null){
		if(!$source){
			$source = $layer->source;
		}
		if(!$raw){
			$raw = $layer->raw;
		}
		
		$layer->params = array_replace_recursive([
			'defaults' => [
				'name' => null,
				'type' => 'cascade',
			],
			'aliases' => [],
		],$layer->params);
		
		$params = $layer->params;
		$contents = $this->syntax->matchBlocks($raw);
		
		$outside = [];
		
		
		
		if(!$contents){
			$composition = $layer->registerComposition(null);
			
			$block = $composition->registerBlock('cascade');
			$block->params = [];
			$block->source = $source;
			
			$blockCode = $this->composeBlock($block, $raw, $source);
			$output->body()->code($blockCode);
		}else{
			
			
			foreach($contents as $c){
				switch(true){
					case is_string($c):
						$outside[] = $c;
						break;
					case is_array($c):
						$defaults = [ 'name' => null, 'type' => null, ];
						
						$attributes = array_replace($defaults, $c['attributes']);
						$children = $c['children'];
						
						
						$rawName = $attributes['name'] ?: $params['defaults']['name'];
						
						$aliases = isset($params['aliases'][$rawName]) ? $params['aliases'][$rawName] : [];
						
						
						$name = isset($aliases['name']) ? $aliases['name'] : $rawName;
						$type = $attributes['type'] ?: $params['defaults']['type'];
						if(isset($aliases['type'])){
							$type = $aliases['type'];
						}
						
						$composition = $layer->registerComposition($name);
						
						$block = $composition->registerBlock($type);
						$block->params = array_diff_key($attributes, $defaults);
						$block->source = $source;
						
						if(isset($block->params['inc'])){
							$children = $this->inc($block->params['inc']);
						}
						
						
						$blockCode = $this->composeBlock($block, $children, $source);
						$output->body()->code($blockCode,'');
						
						break;
				}
			}
			
			if($outside){
				$outside = implode("\n", $outside);
				$output->header()
					->code(null,null)
					->code($outside);
				
			}
			
		}
		
		$output->header()->code("let layer = layerManager.registerLayer({$this->literalValue($layer->key)});");
	}
	
	public function inc($path){
		return $this->loader->loadWrapped( $path . '.' . $this->mlvExtension );
	}
	
	public function prepareScopeKeys($keys){
		$_ignored = ['this', 'class', 'let', 'const', 'typeof', 'instanceof', 'undefined', 'false', 'true', 'null'];
		
		$keys = array_filter($keys, function($k)use($_ignored){
			return is_numeric($k) || in_array($k, $_ignored, true);
		});
		
		return $keys;
	}
	
	/**
	 * Layer > Block
	 * @param Block $block
	 * @param null $raw
	 * @param null $source
	 * @param string $indent
	 * @return string
	 */
	public function composeBlock(Block $block, $raw = null, $source = null, $indent = ''){
		if(!$source) $source = $block->source;
		if(!$raw) $raw = $block->raw;
		
		$scopeKeys = implode(', ', $this->prepareScopeKeys(array_keys($block->composition->layer->scope)));
		
		$componentCode = $this->composeContent($block, $raw, $source, '			');
		
		$code = <<<JSX
/** Block
 * @Name: {$block->name}
 * @Type: {$block->type->key}
 * @Source: {$this->literalValue((object) $source, ' * ')}
 */
(($) => {
	$.composition   = $.layer.registerComposition({$this->literalValue($block->name)});
	$.block         = $.composition.registerBlock({$this->literalValue($block->type->key)});
	$.block.source 	= {$this->literalValue((object) $source, '	')};

	{$this->composeHolders($block->holdersRegistry, '	')}

	$.block.getContents = function(){
		return ((scope) => {
			for (let p in scope){
			  if(scope.hasOwnProperty(p))
			    eval("var " + p + " = scope[p];");
			}
			{$componentCode}
		})( this.composition && this.composition.layer ? this.composition.layer.scope : {} );
	};
	$.block.getContents = $.block.getContents.bind($.block);

})( { layer } );
JSX;
		
		return $this->_indent($code, $indent);
	}
	
	
	/**
	 *
	 * Обработка контента блока, или дэфолт блока холдера
	 *
	 * Layer > Block > content
	 * @param Block $block
	 * @param null $raw
	 * @param null $source
	 * @param null $indent
	 * @return null|string
	 * @throws \Exception
	 */
	public function composeContent(Block $block, $raw = null, $source = null, $indent = null){
		$id = 0;
		
		if(!$source) $source = $block->source;
		if(!$raw) $raw = $block->raw;
		if(!$raw){
			return "return [];";
		}
		$content = $this->syntax->replaceBlockContent($raw,function($all, $el = null) use (&$holders, &$id, $block, $source){
			if($el){
				$id++;
				$defaultAttributes = [ 'name' => null, 'type' => null ];
				$attributes = array_replace($defaultAttributes, $el['attributes']);
				$name = $attributes['name'];
				
				$holder = $block->registerHolder($name, $id);
				$holder->params = array_diff_key($attributes, $defaultAttributes);
				$holder->source = $source;
				
				if($el['children']){
					$default = $holder->registerDefaultBlock($attributes['type']);
					$default->raw = $el['children'];
				}
				$block->contents[] = $holder;
				return '{ ' . $this->_exprHolderGetContents($id) . ' }';
			}else{
				$block->contents[] = $all;
			}
			return $all;
		});
		if( preg_match_all($this->syntax->regexpJSX(), $content, $matches) ){
			
			$elements = array_map(function($v){return trim($v);}, $matches[0]);
			$elements = array_filter($elements);
			
			$content = "return " . ($elements ? "[\n\t" . implode(",\n\t", $elements)."\n]" : "[]") . ';';
		}else{
			throw new \Exception("Bad content in {$source['file']}");
		}
		
		return $this->_indent($content, $indent);
	}
	
	
	/**
	 * Layer > Block > content > Holder
	 * @param array $registry
	 * @param string $indent
	 * @return string
	 */
	public function composeHolders(array $registry, $indent = ''){
		$_holders = [];
		foreach($registry as $id => $holder){
			
			if($holder->defaultBlock){
				$_holders[] = $this->composeCascadeHolder($holder, $id);
			}else{
				$_holders[] = $this->composeEmptyHolder($holder, $id);
			}
		}
		return $this->_indent(implode("\r\n", $_holders), $indent);
	}
	
	/**
	 * @param Holder $holder
	 * @param $id
	 * @param null $source
	 * @return string
	 */
	public function composeCascadeHolder(Holder $holder, $id, $source = null){
		
		
		$block = $holder->defaultBlock;
		$scopeKeys = implode(', ', array_keys($holder->ownerBlock->composition->layer->scope));
		
		
		if(!$source) $source = $block->source;
		$sourceJSON = json_encode((object) $source, JSON_PRETTY_PRINT);
		
		$componentCode = $this->composeContent($block, null,null, '			');
		
		$code = <<<JSX
{$this->_exprHolderDefine($id)} = (($) => {
	let holder = $.block.registerHolder({$this->literalValue($holder->name)}, {$id});
	$.block 	    = holder.registerDefaultBlock();
	$.block.source 	= {$sourceJSON};
	
	{$this->composeHolders($block->holdersRegistry, '	')}
	
	$.block.getContents = function(){
		return (({ {$scopeKeys} }) => {
			{$componentCode}
		})( this.composition && this.composition.layer ? this.composition.layer.scope : {} );
	};
	$.block.getContents = $.block.getContents.bind($.block);
	return holder;
})( { block: $.block } );
JSX;
		
		return $code;
	}
	
	
	/**
	 * @param Holder $holder
	 * @param $id
	 * @param array $source
	 * @return string
	 */
	public function composeEmptyHolder(Holder $holder, $id, $source = []){
		$code = <<<JSX
{$this->_exprHolderDefine($id)} = $.block.registerHolder({$this->literalValue($holder->name)}, {$id});
JSX;
		return $code;
	}
	
	/**
	 * @param $id
	 * @return string
	 */
	protected function _exrHolderVar($id){
		return "__holder_{$id}";
	}
	
	/**
	 * @param $id
	 * @return string
	 */
	protected function _exprHolderDefine($id){
		return "let {$this->_exrHolderVar($id)}";
	}
	
	/**
	 * @param $id
	 * @return string
	 */
	protected function _exprHolderGetContents($id){
		return "{$this->_exrHolderVar($id)}.getContents()";
	}
	
	
	
	
	protected function _indent($content, $indent){
		return strtr(strtr($this->deleteTabs($content), [ "\r\n" => "\n" ]), [ "\n" => "\n{$indent}" ]);
	}
	
	public function deleteTabs($content){
		$minTabs = null;
		$c = explode("\n", $content);
		
		foreach($c as $_){
			if(preg_match("@^[\n\t ]+@", $_, $m)){
				$tabs = 0;
				$chars = $m[0];
				for($i=0;$i<strlen($chars); $i++){
					$char = $chars{$i};
					if($char == "\t"){
						$tabs+= 4;
					}else{
						$tabs+= 1;
					}
				}
				if($minTabs === null || $minTabs > $tabs){
					$minTabs = $tabs;
				}
				if($minTabs<=0){
					$minTabs = 0;
					break;
				}
			}else{
				$minTabs = 0;
				break;
			}
		}
		if($minTabs){
			$full = ceil($minTabs / 4);
			$remains = $minTabs % 4;
			
			$regexFull = "(    |\t)";
			$regexRemains = "( )";
			$regexp = str_repeat($regexFull, $full) . ($remains?str_repeat($regexRemains, $remains):'');
			
			return preg_replace("@\n{$regexp}@", "\n", $content);
		}
		return $content;
	}
	
	public function literalValue($value, $indent = ''){
		
		switch(true){
			case true === $value:
				return 'true';
			case false === $value:
				return 'false';
			case null === $value:
				return 'null';
			default:
				switch(true){
					case is_integer($value):
					case is_float($value):
						return (string) $value;
					case is_string($value):
						return '"' . addcslashes($value, '"') . '"';
					case is_object($value):
						
						$p = [];
						foreach(get_object_vars($value) as $k => $v){
							
							$_key = preg_match('@^[A-z][\w_]+$@smx', $k) ? $k : '"' . addcslashes($k, '"') . '" ';
							$_value = $this->literalValue($v, $indent . "\t");
							
							$p[] = "{$_key}: {$_value}";
						}
						/*
						$value = json_encode($value, JSON_PRETTY_PRINT);
						return strtr($value, ["\n" => "\n{$indent}"]);
						*/
						return "{\r\n{$indent}" . implode(",\r\n{$indent}", $p) . "{$indent}}";
						
						break;
					case is_array($value):
						$object = false;
						foreach($value as $k => $v){
							if(!is_integer($k)){
								$object = true;
								break;
							}
						}
						if($object){
							$p = [];
							foreach($value as $k => $v){
								$_key = preg_match('@^[A-z][\w_]+$@smx', $k) ? $k : '"' . addcslashes($k, '"') . '" ';
								$_value = $this->literalValue($value, $indent . "\t");
								
								$p[] = "{$_key}: {$_value}";
							}
							return $p ? "{\r\n{$indent}" . implode(",\r\n{$indent}", $p) . "{$indent}}" : "{}";
						}else{
							$p = [];
							foreach($value as $v){
								$p[] = $this->literalValue($v, $indent . "\t");
							}
							return $p ? "[\r\n{$indent}" . implode(",\r\n{$indent}", $p) . "{$indent}]" : "[]";
						}
						
						
						break;
				}
		}
	}
	
	
}


