<?php
namespace Joshiausdemwald\Phpforce\Soql\AST;


class Typeof extends Node
{
    /**
     * @var Field
     */
    public $fieldname;

    /**
     * @var TypeofCondition[]
     */
    public $whens = array();

    /**
     * @var Field[]
     */
    public $else = array();

    /**
     * @param string $fieldname
     */
    public function __construct($fieldname)
    {
        $this->fieldname = $fieldname;
    }
}