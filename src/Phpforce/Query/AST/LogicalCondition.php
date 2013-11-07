<?php
namespace Phpforce\Query\AST;

class LogicalCondition extends LogicalUnit
{
    /**
     * @var SoqlFunction|string
     */
    public $left;

    /**
     * @var string
     */
    public $operator;

    /**
     * @var mixed
     */
    public $right;

    /**
     * @param SoqlFunction|string $left
     * @param string operator
     * @param mixed $right
     */
    public function __construct($left, $operator, $right)
    {
        $this->left     = $left;

        $this->operator = $operator;

        $this->right    = $right;
    }
} 