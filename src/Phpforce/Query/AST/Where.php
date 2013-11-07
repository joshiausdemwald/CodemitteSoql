<?php
namespace Phpforce\Query\AST;


class Where extends Node
{
    /**
     * @var LogicalGroup
     */
    public $logicalGroup;

    /**
     * @param LogicalUnit $group
     */
    public function __construct(LogicalUnit $group)
    {
        $this->logicalGroup = $group;
    }
} 