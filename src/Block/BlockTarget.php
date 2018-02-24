<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer\Block;


use Ceive\View\Layer\Block;
use Ceive\View\Layer\BlockHolder;
use Ceive\View\Layer\Lay;

abstract class BlockTarget extends Block{
	
	/**
	 * @param $name
	 * @param Lay $lay
	 * @return BlockHolder[]
	 */
	protected function findHolders($name, Lay $lay){
		
		$ancestor = $lay->getAncestor();
		
		$holders = [];
		while($ancestor){
			$holders = $ancestor->searchHolders($name);
			if($holders){
				break;
			}else{
				$ancestor = $ancestor->getAncestor();
			}
		}
		
		return $holders;
	}
	
	protected function addToHolder(BlockHolder $holder){
		parent::addToHolder($holder);
		$holder->addTarget($this);
	}
	
}


