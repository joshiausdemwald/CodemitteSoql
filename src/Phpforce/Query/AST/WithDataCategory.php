<?php
namespace Phpforce\Query\AST;


class WithDataCategory extends Node
{
    /**
     * @var LogicalUnit
     */
    public $logicalGroup;

    /**
     * @param LogicalUnit $group
     * @param string $type
     */
    public function __construct(LogicalUnit $group)
    {
        $this->logicalGroup = $group;
    }
} 