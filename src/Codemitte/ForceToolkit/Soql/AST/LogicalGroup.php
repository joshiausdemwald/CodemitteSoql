<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 16:08
 */

namespace Codemitte\ForceToolkit\Soql\AST;

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