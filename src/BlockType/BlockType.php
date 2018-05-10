<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\BlockType;


use Ceive\View\Layer\Block;
use Ceive\View\Layer\Composition;
use Ceive\View\Layer\Holder;
use Ceive\View\Layer\Layer;
use Ceive\View\Layer\LayerManager;

abstract class BlockType{
	
	const CASCADE   = 'cascade';
	const REPLACE   = 'replace';
	const DEFINE    = 'define';
	const PREPEND   = 'prepend';
	const APPEND    = 'append';
	
	public $key;
	
	protected static $defaultTypes = [];
	
	protected static function getDefaultBlockTypes(){
		
		if(!self::$defaultTypes){
			
			self::$defaultTypes = [
				self::CASCADE   => new BlockTypeCascade(),
				self::REPLACE   => new BlockTypeReplace(),
				self::DEFINE    => new BlockTypeDefine(),
				self::PREPEND   => new BlockTypePrepend(),
				self::APPEND    => new BlockTypeAppend(),
			];
			foreach(self::$defaultTypes as $key => $t){
				$t->key = $key;
			}
			
		}
		
		return self::$defaultTypes;
	}
	
	public static function registerDefaults(LayerManager $manager){
		$defaults = self::getDefaultBlockTypes();
		foreach($defaults as $typeKey => $type){
			$manager->registerType($typeKey, $type);
		}
	}
	
	/**
	 * @param $type
	 * @return mixed
	 * @throws \Exception
	 */
	public static function requireBlockType($type){
		$defaults = self::getDefaultBlockTypes();
		if(isset($defaults[$type])){
			return $defaults[$type];
		}
		throw new \Exception("Required block type '{$type}' is not existing in default types");
	}
	
	public function attachToHolder(Block $block, Holder $holder){
		
	}
	
	public function detachFromHolder(Block $block, Holder $holder){
		
	}
	
	public function searchHoldersForPick(Block $block, Layer $layer){
		return [];
	}
	
	public function attachToComposition(Composition $composition, Block $block){
		$block->composition = $composition;
	}
	
}


