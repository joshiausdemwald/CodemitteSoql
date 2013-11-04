<?php
namespace Phpforce\Query\Soql\AST;


class Field extends Leaf
{
    /**
     * @var string
     */
    public $alias;

    /**
     * @param null|string $value
     * @param $alias
     */
    public function __construct($value, $alias = null)
    {
        parent::__construct($value);

        $this->alias = $alias;
    }
}