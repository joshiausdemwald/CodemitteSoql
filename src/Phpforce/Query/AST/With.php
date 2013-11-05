<?php
namespace Phpforce\Query\AST;


class With extends Node
{
    /**
     * @var LogicalGroup
     */
    public $logicalGroup;

    /**
     * @param string $what
     *
     * @param LogicalGroup $group
     */
    public function __construct(LogicalGroup $group)
    {
        $this->logicalGroup = $group;
    }
} 