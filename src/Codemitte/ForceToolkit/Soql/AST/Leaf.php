<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 21.10.13
 * Time: 16:15
 */

namespace Codemitte\ForceToolkit\Soql\AST;


abstract class Leaf extends Node
{
    /**
     * @var string
     */
    public $value;

    /**
     * @param string|null $value
     */
    public function __construct($value = null)
    {
        $this->value = $value;
    }
} 