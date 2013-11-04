<?php
namespace Phpforce\Query\Soql\AST;


abstract class Leaf extends Node
{
    /**
     * @var string
     */
    public $value;

    /**
     * @param string|null $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }
} 