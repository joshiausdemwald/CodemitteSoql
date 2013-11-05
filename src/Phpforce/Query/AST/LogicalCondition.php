<?php
namespace Phpforce\Query\AST;


use Codemitte\ForceToolkit\Soql\AST\Functions;

class LogicalCondition extends Node
{
    /**
     * For instance "DATA CATEGORY"
     *
     * @var string $type
     */
    public $type;

    /**
     * @var Functions\SoqlFunction|string
     */
    public $left;

    public $operator;

    public $right;

    public $logical;

    /**
     * @param SoqlFunction|string $left
     * @param string operator
     * @param mixed $right
     * @param string|null $logical
     * @param string|null $type
     */
    public function __construct($left, $operator, $right, $logical = null, $type = null)
    {
        $this->left     = $left;

        $this->operator = $operator;

        $this->right    = $right;

        $this->logical  = $logical;

        $this->type     = $type;
    }
} 