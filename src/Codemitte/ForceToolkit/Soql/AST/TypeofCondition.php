<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:35
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class TypeofCondition extends Node
{
    /**
     * @var string
     */
    public $when;

    /**
     * @var array<Field>
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