<?php
namespace Phpforce\Query\Soql\AST;


class From extends Leaf
{
    /**
     * @var Alias
     */
    public $alias;

    /**
     * @param string $value
     * @param null|Alias $alias
     */
    public function __construct($value, $alias = null)
    {
        parent::__construct($value);

        $this->alias = $alias;
    }
} 