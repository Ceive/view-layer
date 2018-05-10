<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;
use Ceive\View\Layer\BlockType\BlockType;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class LayerManager
 * @package Ceive\View\Layer
 */
class LayerManager{
	
	/** @var  BlockType */
	protected $blockTypes = [];
	
	/** @var Layer[] */
	public $layers = [];
	
	/** @var  Layer */
	protected $active;
	
	public function __construct(){
		BlockType::registerDefaults($this);
	}
	
	/**
	 * @param string[]|array[] $chain
	 * [ [key, scope] | key, ..., ..., ...]
	 * @return Layer|null
	 */
	public function setActive(array $chain){
		if(isset($this->active)){
			$layer = $this->active;
			do{
				$layer->reset();
			}while($layer = $layer->ancestor);
			
			$this->active = null;
		}
		
		$active = null;
		foreach($chain as $layerKey){
			
			if(is_array($layerKey)){
				list($layerKey, $scope) = $layerKey;
			}else{
				$scope = [];
			}
			
			if($layer = $this->requireLayer($layerKey)){
				$layer->ancestor = $active;
				$layer->scope = $scope;
				$active = $layer;
			}
		}
		$this->active = $active;
		return $this->active;
	}
	
	/**
	 * @return Layer
	 */
	public function getActive(){
		return $this->active;
	}
	
	
	/**
	 * @param $layerKey
	 * @return Layer
	 * @throws \Exception
	 */
	public function requireLayer($layerKey){
		
		if(!$this->layers[$layerKey]){
			throw new \Exception("Layer {$layerKey} not registered");
		}
		
		return $this->layers[$layerKey];
	}
	
	/**
	 * @param $layerKey
	 * @return Layer
	 */
	public function registerLayer($layerKey){
		if(!isset($this->layers[$layerKey])){
			$layer = $this->layers[$layerKey] = new Layer();
			$layer->key = $layerKey;
			$layer->manager = $this;
		}
		return $this->layers[$layerKey];
	}
	
	/**
	 * @param $typeKey
	 * @param BlockType $type
	 */
	public function registerType($typeKey, BlockType $type){
		$this->blockTypes[$typeKey] = $type;
	}
	
	/**
	 * @param $typeKey
	 * @return BlockType
	 * @throws \Exception
	 */
	public function requireType($typeKey){
		if(!isset($this->blockTypes[$typeKey])){
			throw new \Exception('Required type "'.$typeKey.'" not registered in manager, please register a type or use other registered');
		}
		return $this->blockTypes[$typeKey];
	}
	
}


