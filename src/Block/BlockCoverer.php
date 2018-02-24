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

/**
 * @Author: Alexey Kutuzov <lexus27.khv@gmail.com>
 * Class BlockCoverer
 * @package Ceive\View\Layer\Block
 */
class BlockCoverer extends Block{
	
	
	/**
	 * @param $name
	 * @param Lay $lay
	 * @return BlockHolder[]
	 */
	protected function findHolders($name, Lay $lay){
		/**
		 * @var $ancestors Lay[]
		 */
		
		$ancestor = $lay->getAncestor();
		
		$holders = [];
		$ancestors = [];
		
		while($ancestor){
			$ancestors[] = $ancestor;
			$ancestor = $ancestor->getAncestor();
		}
		$ancestors = array_reverse($ancestors);
		
		foreach($ancestors as $ancestor){
			$holders = $ancestor->searchHolders($name);
			if($holders){
				break;
			}
		}
		
		return $holders;
	}
}
