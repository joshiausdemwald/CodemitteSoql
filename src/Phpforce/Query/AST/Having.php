<?php
namespace Phpforce\Query\AST;


class Having extends Node
{
    /**
     * @var LogicalUnit
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