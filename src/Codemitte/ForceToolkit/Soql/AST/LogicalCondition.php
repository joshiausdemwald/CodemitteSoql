<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 23:09
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class LogicalCondition extends Node
{
    /**
     * @var SoqlFunction|string
     */
    public $left;

    public $operator;

    public $right;

    public $logical;

    public function __construct($left, $operator, $right, $logical = null)
    {
        $this->left = $left;

        $this->operator = $operator;

        $this->right = $right;

        $this->logical = $logical;
    }
} 