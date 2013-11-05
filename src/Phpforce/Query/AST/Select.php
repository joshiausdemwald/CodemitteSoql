<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 05.11.13
 * Time: 14:21
 */

namespace Phpforce\Query\AST;


class Select extends Node
{
    /**
     * @var Field[]|Function[]|Subquery[]
     */
    public $fields = array();

    /**
     * @param array $fields
     */
    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }
} 