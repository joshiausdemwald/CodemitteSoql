<?php
namespace Phpforce\Query\AST;


class TypeofCondition extends Node
{
    /**
     * @var string
     */
    public $when;

    /**
     * @var Field[]
     */
    public $then = array();

    /**
     * @param string $when
     */
    public function __construct($when)
    {
        $this->when = $when;
    }
} 