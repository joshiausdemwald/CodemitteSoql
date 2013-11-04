<?php
namespace Phpforce\Query\AST;


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