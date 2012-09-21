<?php
namespace Everzet\Jade\Node;
class MixinNode extends Node {
	public $parm;
	public $func;
	protected $block;
	public function __construct($function_name, $parm, $line) {
		parent::__construct($line);
		$this->parm = $parm;
		$this->func = $function_name;
	}

	/**
	 * Set block node.
	 *
	 * @param   BlockNode   $node   child node
	 */
	public function setBlock(BlockNode $node)
	{
		$this->block = $node;
	}

	/**
	 * Return block node.
	 *
	 * @return  BlockNode
	 */
	public function getBlock()
	{
		return $this->block;
	}

}
