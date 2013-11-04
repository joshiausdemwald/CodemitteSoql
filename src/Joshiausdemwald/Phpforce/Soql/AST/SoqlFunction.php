<?php
namespace Joshiausdemwald\Phpforce\Soql\AST;


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
     * @var Field[]|SoqlFunction[]
     */
    public $arguments;

    /**
     * @param string $name
     * @param Field[]|SoqlFunction[] $arguments
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