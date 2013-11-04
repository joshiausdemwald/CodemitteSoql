<?php
namespace Phpforce\Query\AST;

class LogicalGroup extends Node
{
    /**
     * @var null|string
     */
    public $logical;

    /**
     * @var LogicalCondition[]
     */
    public $conditions = array();

    /**
     * @param string $logical
     */
    public function __construct($logical = null)
    {
        $this->logical = $logical;
    }
} 