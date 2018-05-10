<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\BlockType;


use Ceive\View\Layer\Block;
use Ceive\View\Layer\BlockType\BlockType;
use Ceive\View\Layer\Composition;
use Ceive\View\Layer\Holder;
use Ceive\View\Layer\Layer;

abstract class BlockTypeTarget extends BlockType{
	
	/**
	 * @param Block $block
	 * @param Layer $layer
	 * @return array|Holder[]
	 */
	public function searchHoldersForPick(Block $block, Layer $layer){
		
		$ancestor = $layer->ancestor;
		
		$holders = [];
		while($ancestor){
			$holders = $ancestor->getContainHoldersBy($block->name);
			if($holders){
				break;
			}else{
				$ancestor = $ancestor->ancestor;
			}
		}
		
		return $holders;
	}
	
	public function attachToHolder(Block $block, Holder $holder){
		parent::attachToHolder($block, $holder);
		$holder->addTarget($block);
	}
	
	public function detachFromHolder(Block $block, Holder $holder){
		parent::detachFromHolder($block, $holder);
		$holder->removeTarget($block);
	}
	
	public function attachToComposition(Composition $composition, Block $block){
		parent::attachToComposition($composition, $block);
		$composition->target = $block;
	}
	
	
}


