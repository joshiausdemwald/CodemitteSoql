<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:04
 */

namespace Codemitte\ForceToolkit\Soql\AST;


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