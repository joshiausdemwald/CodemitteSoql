<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:07
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class Typeof extends Node
{
    /**
     * @var Field
     */
    public $fieldname;

    /**
     * @var array<TypeofCondition>
     */
    public $whens = array();

    /**
     * @var array<Field>
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