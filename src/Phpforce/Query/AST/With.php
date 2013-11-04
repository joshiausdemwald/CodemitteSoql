<?php
namespace Phpforce\Query\Soql\AST;


class With extends Node
{
    const DATA_CATEGORY = 'DATA CATEGORY';

    /**
     * Current: "DATA CATEGORY" only
     * @var string
     */
    public $what;

    /**
     * @var LogicalGroup
     */
    public $logicalGroup;

    /**
     * @param string $what
     * @param LogicalGroup $group
     */
    public function __construct(LogicalGroup $group, $what = null)
    {
        $this->what = $what;

        $this->logicalGroup = $group;
    }
} 