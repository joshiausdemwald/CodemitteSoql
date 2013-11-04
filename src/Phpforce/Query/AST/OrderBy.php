<?php
namespace Phpforce\Query\AST;


class OrderBy extends Node
{
    /**
     * @var array
     */
    public $fields;

    /**
     * @param array $fields
     */
    public function __construct(array $fields = array())
    {
        $this->fields = $fields;
    }
}