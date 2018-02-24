<?php
/**
 * @Creator Alexey Kutuzov <lexus27.khv@gmail.com>
 * @Author: Alexey Kutuzov <lexus27.khv@gmai.com>
 * @Project: ceive.view-layer
 */

namespace Ceive\View\Layer;

use Ceive\View\Layer\Block\BlockAppend;
use Ceive\View\Layer\Block\BlockPrepend;
use Ceive\View\Layer\Block\BlockTarget;

/**
 * @Author: Alexey Kutuzov <lexus.1995@mail.ru>
 * Interface BlockHolderInterface
 * @package Ceive\View\Layer
 */
interface BlockHolderInterface extends Element{
	
	/**
	 * @return string|null
	 */
	public function getName();
	
	/**
	 * @return bool
	 */
	public function isCascade();
	
	/**
	 * @param bool $asArray
	 * @return array|string
	 */
	public function getPath($asArray = false);
	
	/**
	 * @return BlockHolder|Element|null
	 */
	public function getHolder();
	
	/**
	 * @return Layout|null
	 */
	public function getRootHoldsLayout();
	
	/**
	 * @param BlockPrepend $block
	 * @return $this
	 */
	public function addPrepend(BlockPrepend $block);
	
	/**
	 * @param BlockAppend $block
	 * @return $this
	 */
	public function addAppend(BlockAppend $block);
	
	/**
	 * @param BlockTarget $block
	 * @return $this
	 */
	public function addTarget(BlockTarget $block);
}

