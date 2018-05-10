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

class BlockTypePrepend extends BlockTypeCoverer{
	
	/**
	 * @param Block $block
	 * @param Holder $holder
	 */
	public function attachToHolder(Block $block, Holder $holder){
		parent::attachToHolder($block, $holder);
		$holder->addPrepend($block);
	}
	
	/**
	 * @param Block $block
	 * @param Holder $holder
	 */
	public function detachFromHolder(Block $block, Holder $holder){
		parent::detachFromHolder($block, $holder);
		$holder->removePrepend($block);
	}
	
	/**
	 * @param Composition $composition
	 * @param Block $block
	 */
	public function attachToComposition(Composition $composition, Block $block){
		parent::attachToComposition($composition, $block);
		$composition->prepends[] = $block;
	}
	
}
