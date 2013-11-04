<?php
namespace Phpforce\Query\AST;


class Where extends Node
{
    /**
     * @var LogicalGroup
     */
    public $logicalGroup;

    /**
     * @param LogicalGroup $group
     */
    public function __construct(LogicalGroup $group)
    {
        $this->logicalGroup = $group;
    }
} 