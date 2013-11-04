<?php
namespace Joshiausdemwald\Phpforce\Soql\AST;


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