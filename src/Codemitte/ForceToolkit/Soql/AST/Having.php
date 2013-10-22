<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 21.10.13
 * Time: 12:12
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class Having extends Node
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