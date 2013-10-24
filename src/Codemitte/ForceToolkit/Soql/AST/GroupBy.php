<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:04
 */

namespace Codemitte\ForceToolkit\Soql\AST;


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