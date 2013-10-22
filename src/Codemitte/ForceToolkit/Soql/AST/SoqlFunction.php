<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 13:32
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class SoqlFunction extends Node
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var Alias
     */
    public $alias;

    /**
     * @var array<Field|SoqlFunction>
     */
    public $arguments;

    /**
     * @param string $name
     * @param array<Field|SoqlFunction> $arguments
     * @param null|Alias $alias
     * @param array $arguments
     */
    public function __construct($name, Alias $alias = null, array $arguments = array())
    {
        $this->name = $name;

        $this->alias = $alias;

        $this->arguments = $arguments;
    }
} 