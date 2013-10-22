<?php
/**
 * Created by PhpStorm.
 * User: joshi
 * Date: 17.10.13
 * Time: 12:06
 */

namespace Codemitte\ForceToolkit\Soql\AST;


class Field extends Leaf
{
    /**
     * @var string
     */
    public $alias;

    /**
     * @param null|string $value
     * @param $alias
     */
    public function __construct($value, $alias = null)
    {
        parent::__construct($value);

        $this->alias = $alias;
    }
}