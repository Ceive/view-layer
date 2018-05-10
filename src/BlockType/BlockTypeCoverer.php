<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\BlockType;


use Ceive\View\Layer\Block;
use Ceive\View\Layer\BlockType\BlockType;
use Ceive\View\Layer\Holder;
use Ceive\View\Layer\Layer;

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class BlockCoverer
 * @package Ceive\View\Layer\Block
 */
abstract class BlockTypeCoverer extends BlockType{
	
	
	/**
	 * @param Block $block
	 * @param Layer $layer
	 * @return array|Holder[]
	 */
	public function searchHoldersForPick(Block $block, Layer $layer){
		
		
		/**
		 * @var $ancestors Layer[]
		 */
		$ancestor = $layer->ancestor;
		
		$holders   = [];
		$ancestors = [];
		
		while($ancestor){
			$ancestors[] = $ancestor;
			$ancestor = $ancestor->ancestor;
		}
		$ancestors = array_reverse($ancestors);
		
		foreach($ancestors as $ancestor){
			$holders = $ancestor->getContainHoldersBy($block->name);
			if($holders){
				break;
			}
		}
		
		return $holders;
	}
	
}
