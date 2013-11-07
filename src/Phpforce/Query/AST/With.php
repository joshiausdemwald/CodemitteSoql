<?php
namespace Phpforce\Query\AST;


class With extends Node
{
    /**
     * @var LogicalGroup
     */
    public $logicalGroup;

    /**
     * @param LogicalGroup $group
     * @param string $type
     */
    public function __construct(LogicalGroup $group)
    {
        $this->logicalGroup = $group;
    }
} 