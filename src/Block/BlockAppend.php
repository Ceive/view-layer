<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Block;


use Ceive\View\Layer\BlockHolder;

class BlockAppend extends BlockCoverer{
	
	protected function addToHolder(BlockHolder $holder){
		parent::addToHolder($holder);
		$holder->addAppend($this);
	}
	
}