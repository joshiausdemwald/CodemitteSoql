<?php
namespace Phpforce\Query\Soql\AST;


class GroupBy extends Node
{
    /**
     * @var Field[]
     */
    public $fields = array();

    /**
     * @var null|string
     */
    public $type = null;

    /**
     * @param Field[] $fields
     * @param null|string $type
     */
    public function __construct(array $fields = array(), $type = null)
    {
        $this->fields = $fields;

        $this->type = $type;
    }
}