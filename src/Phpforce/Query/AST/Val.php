<?php
namespace Phpforce\Query\AST;


class Val extends Node
{
    /**
     * @var mixed
     */
    public $value;

    /**
     * @var string
     */
    public $type;

    /**
     * @param mixed     $value
     * @param string    $type
     */
    public function __construct($value, $type)
    {
        $this->value = $value;

        $this->type  = $type;
    }
} 